<?php

class PurchTableModel extends BaseModel
{
    public function all($purchType = null)
    {
        $params = [];
        $sql = 'SELECT p.*, d.Name AS VendorName
                FROM PurchTable p
                INNER JOIN DirPartyTable d ON d.RecId = p.PartyId
                WHERE p.IsActive = "1"';

        if ($purchType !== null) {
            $sql .= ' AND p.PurchType = ?';
            $params[] = $this->normalizePurchType($purchType);
        }

        $sql .= ' ORDER BY p.PurchDate DESC, p.PurchId DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function findById($id, $purchType = null)
    {
        $params = [(int) $id];
        $sql = 'SELECT p.*, d.Name AS VendorName
                FROM PurchTable p
                INNER JOIN DirPartyTable d ON d.RecId = p.PartyId
                WHERE p.RecId = ?';

        if ($purchType !== null) {
            $sql .= ' AND p.PurchType = ?';
            $params[] = $this->normalizePurchType($purchType);
        }

        $sql .= ' LIMIT 1';

        $purchase = $this->db->fetchOne($sql, $params);

        if (!$purchase) {
            return null;
        }

        $purchase['Lines'] = $this->db->fetchAll(
            'SELECT l.*, i.Name AS ItemName, s.Name AS ServiceName
             FROM PurchLine l
             LEFT JOIN InventTable i ON i.ItemId = l.ItemId
             LEFT JOIN ServiceCodeTable s ON s.RecId = l.ServiceCodeId
             WHERE l.PurchRecId = ?
             ORDER BY l.LineNum ASC',
            [(int) $id]
        );
        $purchase['BillingAmount'] = $this->calculateGrossAmount($purchase['Lines']);
        $purchase['TotalAmount'] = $this->calculatePurchaseTotal($purchase['Lines'], isset($purchase['DeductionAmount']) ? $purchase['DeductionAmount'] : 0);

        return $purchase;
    }

    public function create($data, $createdBy, $purchType = null)
    {
        $vendor = $this->db->fetchOne('SELECT VendAccount, PartyId FROM VendTable WHERE VendAccount = ? LIMIT 1', [trim($data['VendAccount'])]);
        $company = $this->resolveCompany(isset($data['CompanyRecId']) ? $data['CompanyRecId'] : null);

        if (!$vendor) {
            throw new Exception('Vendor account not found.');
        }

        if (!$company) {
            throw new Exception('Default company not found.');
        }

        $normalizedType = $this->normalizePurchType(isset($data['PurchType']) ? $data['PurchType'] : $purchType);
        $lines = $this->normalizeLines(isset($data['Lines']) ? $data['Lines'] : [], $normalizedType);

        $this->db->beginTransaction();

        try {
            $purchId = $this->generateNumber('PurchTable', 'PurchId', 'PUR');
            $purchNumber = isset($data['PurchNumber']) && trim($data['PurchNumber']) !== '' ? trim($data['PurchNumber']) : $purchId;
            $deductionAmount = $this->normalizeAmount(isset($data['DeductionAmount']) ? $data['DeductionAmount'] : 0);
            $now = $this->now();
            $purchaseRecId = $this->db->insert(
                'INSERT INTO PurchTable (PurchId, PurchType, CompanyRecId, CompanyAlias, VendAccount, PartyId, PurchNumber, PurchDate, DueDate, TotalAmount, DeductionAmount, Status, Notes, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $purchId,
                    $normalizedType,
                    (int) $company['RecId'],
                    $company['Alias'],
                    $vendor['VendAccount'],
                    (int) $vendor['PartyId'],
                    $purchNumber,
                    $this->dateTimeOrNow($data['PurchDate']),
                    $this->dateTimeOrNow($data['DueDate']),
                    $this->calculatePurchaseTotal($lines, $deductionAmount),
                    $deductionAmount,
                    isset($data['Status']) ? strtoupper(substr(trim($data['Status']), 0, 1)) : 'O',
                    isset($data['Notes']) ? trim($data['Notes']) : '',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $now,
                    $now,
                    $createdBy
                ]
            );

            $this->syncLines($purchaseRecId, $lines, $createdBy, $normalizedType);
            $this->db->commit();
            return $purchaseRecId;
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update($id, $data, $createdBy, $purchType = null)
    {
        $purchase = $this->findById($id, $purchType);

        if (!$purchase) {
            throw new Exception('Purchase order not found.');
        }

        $vendor = $this->db->fetchOne('SELECT VendAccount, PartyId FROM VendTable WHERE VendAccount = ? LIMIT 1', [trim($data['VendAccount'])]);
        $company = $this->resolveCompany(isset($data['CompanyRecId']) ? $data['CompanyRecId'] : null);

        if (!$vendor) {
            throw new Exception('Vendor account not found.');
        }

        if (!$company) {
            throw new Exception('Default company not found.');
        }

        $normalizedType = $this->normalizePurchType(isset($data['PurchType']) ? $data['PurchType'] : ($purchType !== null ? $purchType : $purchase['PurchType']));
        $lines = $this->normalizeLines(isset($data['Lines']) ? $data['Lines'] : [], $normalizedType);
        $deductionAmount = $this->normalizeAmount(isset($data['DeductionAmount']) ? $data['DeductionAmount'] : 0);

        $this->db->beginTransaction();

        try {
            $this->db->execute(
                'UPDATE PurchTable SET PurchType = ?, CompanyRecId = ?, CompanyAlias = ?, VendAccount = ?, PartyId = ?, PurchNumber = ?, PurchDate = ?, DueDate = ?, TotalAmount = ?, DeductionAmount = ?, Status = ?, Notes = ?, IsActive = ?, ModifiedDateTime = ? WHERE RecId = ?',
                [
                    $normalizedType,
                    (int) $company['RecId'],
                    $company['Alias'],
                    $vendor['VendAccount'],
                    (int) $vendor['PartyId'],
                    trim($data['PurchNumber']),
                    $this->dateTimeOrNow($data['PurchDate']),
                    $this->dateTimeOrNow($data['DueDate']),
                    $this->calculatePurchaseTotal($lines, $deductionAmount),
                    $deductionAmount,
                    isset($data['Status']) ? strtoupper(substr(trim($data['Status']), 0, 1)) : 'O',
                    isset($data['Notes']) ? trim($data['Notes']) : '',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->now(),
                    (int) $id
                ]
            );

            $this->db->execute('DELETE FROM PurchLine WHERE PurchRecId = ?', [(int) $id]);
            $this->syncLines((int) $id, $lines, $createdBy, $normalizedType);
            $this->db->commit();
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE PurchTable SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }

    private function syncLines($purchaseRecId, $lines, $createdBy, $purchType)
    {
        $lineNumber = 1;

        foreach ($lines as $line) {
            $quantity = isset($line['Quantity']) ? (float) $line['Quantity'] : 0;
            $unitPrice = isset($line['UnitPrice']) ? (float) $line['UnitPrice'] : 0;
            $lineAmount = isset($line['LineAmount']) ? (float) $line['LineAmount'] : ($quantity * $unitPrice);
            $itemId = $purchType === 'S' ? null : trim($line['ItemId']);
            $serviceCodeId = $purchType === 'S' ? (int) $line['ServiceCodeId'] : null;

            $this->db->insert(
                'INSERT INTO PurchLine (PurchRecId, LineNum, ItemId, ServiceCodeId, Description, Quantity, UnitPrice, LineAmount, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (int) $purchaseRecId,
                    $lineNumber,
                    $itemId,
                    $serviceCodeId,
                    isset($line['Description']) ? trim($line['Description']) : '',
                    $this->normalizeAmount($quantity),
                    $this->normalizeAmount($unitPrice),
                    $this->normalizeAmount($lineAmount),
                    $this->now(),
                    $this->now(),
                    $createdBy
                ]
            );

            $lineNumber++;
        }
    }

    private function calculateGrossAmount($lines)
    {
        $total = 0;

        foreach ($lines as $line) {
            $quantity = isset($line['Quantity']) ? (float) $line['Quantity'] : 0;
            $unitPrice = isset($line['UnitPrice']) ? (float) $line['UnitPrice'] : 0;
            $total += isset($line['LineAmount']) ? (float) $line['LineAmount'] : ($quantity * $unitPrice);
        }

        return $this->normalizeAmount($total);
    }

    private function calculatePurchaseTotal($lines, $deductionAmount = 0)
    {
        $grossAmount = $this->calculateGrossAmount($lines);
        $netAmount = max(0, (float) $grossAmount - (float) $this->normalizeAmount($deductionAmount));

        return $this->normalizeAmount($netAmount);
    }

    private function normalizePurchType($value)
    {
        $normalized = strtoupper(substr(trim((string) $value), 0, 1));
        return $normalized === 'S' ? 'S' : 'I';
    }

    private function normalizeLines($lines, $purchType)
    {
        $normalizedLines = [];

        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $description = isset($line['Description']) ? trim($line['Description']) : '';
            $quantity = isset($line['Quantity']) ? (float) $line['Quantity'] : 0;
            $unitPrice = isset($line['UnitPrice']) ? (float) $line['UnitPrice'] : 0;
            $lineAmount = isset($line['LineAmount']) && trim((string) $line['LineAmount']) !== '' ? (float) $line['LineAmount'] : ($quantity * $unitPrice);

            if ($purchType === 'S') {
                $serviceCodeId = isset($line['ServiceCodeId']) ? (int) $line['ServiceCodeId'] : 0;

                if ($serviceCodeId <= 0 && $description === '' && $quantity <= 0 && $unitPrice <= 0 && $lineAmount <= 0) {
                    continue;
                }

                if ($serviceCodeId <= 0) {
                    throw new Exception('Service code is required for service purchase lines.');
                }

                $serviceCode = $this->db->fetchOne('SELECT RecId FROM ServiceCodeTable WHERE RecId = ? LIMIT 1', [$serviceCodeId]);
                if (!$serviceCode) {
                    throw new Exception('Service code not found.');
                }

                $normalizedLines[] = [
                    'ItemId' => '',
                    'ServiceCodeId' => $serviceCodeId,
                    'Description' => $description,
                    'Quantity' => $quantity,
                    'UnitPrice' => $unitPrice,
                    'LineAmount' => $lineAmount
                ];
                continue;
            }

            $itemId = isset($line['ItemId']) ? trim($line['ItemId']) : '';

            if ($itemId === '' && $description === '' && $quantity <= 0 && $unitPrice <= 0 && $lineAmount <= 0) {
                continue;
            }

            if ($itemId === '') {
                throw new Exception('Item is required for material purchase lines.');
            }

            $item = $this->db->fetchOne('SELECT ItemId FROM InventTable WHERE ItemId = ? LIMIT 1', [$itemId]);
            if (!$item) {
                throw new Exception('Item not found.');
            }

            $normalizedLines[] = [
                'ItemId' => $itemId,
                'ServiceCodeId' => 0,
                'Description' => $description,
                'Quantity' => $quantity,
                'UnitPrice' => $unitPrice,
                'LineAmount' => $lineAmount
            ];
        }

        return $normalizedLines;
    }

    private function resolveCompany($companyRecId = null)
    {
        if ($companyRecId) {
            $company = $this->db->fetchOne('SELECT RecId, Alias FROM CompanyInfo WHERE RecId = ? AND IsActive = "1" LIMIT 1', [(int) $companyRecId]);
            if ($company) {
                return $company;
            }
        }

        $company = $this->db->fetchOne('SELECT RecId, Alias FROM CompanyInfo WHERE IsActive = "1" AND IsDefault = "1" LIMIT 1');
        if ($company) {
            return $company;
        }

        return $this->db->fetchOne('SELECT RecId, Alias FROM CompanyInfo WHERE IsActive = "1" ORDER BY RecId ASC LIMIT 1');
    }
}