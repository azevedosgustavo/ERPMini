<?php

class CustInvoiceModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll(
            'SELECT i.*, p.Name AS CustomerName
             FROM CustInvoiceJour i
             INNER JOIN DirPartyTable p ON p.RecId = i.PartyId
             WHERE i.IsActive = "1"
             ORDER BY i.InvoiceDate DESC, i.InvoiceId DESC'
        );
    }

    public function findById($id)
    {
        $invoice = $this->db->fetchOne(
            'SELECT i.*, p.Name AS CustomerName
             FROM CustInvoiceJour i
             INNER JOIN DirPartyTable p ON p.RecId = i.PartyId
             WHERE i.RecId = ? LIMIT 1',
            [(int) $id]
        );

        if (!$invoice) {
            return null;
        }

        $invoice['Lines'] = $this->db->fetchAll(
            'SELECT t.*, s.ServiceCode, s.Name AS ServiceName
             FROM CustInvoiceTrans t
             INNER JOIN ServiceCodeTable s ON s.RecId = t.ServiceCodeId
             WHERE t.InvoiceRecId = ?
             ORDER BY t.LineNum ASC',
            [(int) $id]
        );

        $invoice['BillingAmount'] = $this->calculateGrossAmount($invoice['Lines']);
        $invoice['TotalAmount'] = $this->calculateInvoiceTotal($invoice['Lines'], isset($invoice['DeductionAmount']) ? $invoice['DeductionAmount'] : 0);

        return $invoice;
    }

    public function create($data, $createdBy)
    {
        $customer = $this->db->fetchOne('SELECT CustAccount, PartyId FROM CustTable WHERE CustAccount = ? LIMIT 1', [trim($data['CustAccount'])]);
        $company = $this->resolveCompany(isset($data['CompanyRecId']) ? $data['CompanyRecId'] : null);

        if (!$customer) {
            throw new Exception('Customer account not found.');
        }

        if (!$company) {
            throw new Exception('Default company not found.');
        }

        $this->db->beginTransaction();

        try {
            $invoiceId = $this->generateNumber('CustInvoiceJour', 'InvoiceId', 'INV');
            $invoiceNumber = isset($data['InvoiceNumber']) && trim($data['InvoiceNumber']) !== '' ? trim($data['InvoiceNumber']) : $invoiceId;
            $taxAmount = $this->normalizeAmount(isset($data['TaxAmount']) ? $data['TaxAmount'] : 0);
            $deductionAmount = $this->normalizeAmount(isset($data['DeductionAmount']) ? $data['DeductionAmount'] : 0);
            $lines = $this->normalizeLines(isset($data['Lines']) ? $data['Lines'] : []);
            $totalAmount = $this->calculateInvoiceTotal($lines, $deductionAmount);
            $now = $this->now();

            $invoiceRecId = $this->db->insert(
                'INSERT INTO CustInvoiceJour (InvoiceId, CompanyRecId, CompanyAlias, CustAccount, PartyId, InvoiceNumber, InvoiceDate, DueDate, TotalAmount, TaxAmount, DeductionAmount, IsInternationalReplacement, InternationalInvoiceNumber, InternationalInvoiceSeries, InternationalInvoiceDate, Status, Notes, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $invoiceId,
                    (int) $company['RecId'],
                    $company['Alias'],
                    $customer['CustAccount'],
                    (int) $customer['PartyId'],
                    $invoiceNumber,
                    $this->dateTimeOrNow($data['InvoiceDate']),
                    $this->dateTimeOrNow($data['DueDate']),
                    $totalAmount,
                    $taxAmount,
                    $deductionAmount,
                    $this->normalizeFlag(isset($data['IsInternationalReplacement']) ? $data['IsInternationalReplacement'] : '0', '0'),
                    isset($data['InternationalInvoiceNumber']) ? trim($data['InternationalInvoiceNumber']) : '',
                    isset($data['InternationalInvoiceSeries']) ? trim($data['InternationalInvoiceSeries']) : '',
                    !empty($data['InternationalInvoiceDate']) ? $this->dateTimeOrNow($data['InternationalInvoiceDate']) : null,
                    isset($data['Status']) ? strtoupper(substr(trim($data['Status']), 0, 1)) : 'O',
                    isset($data['Notes']) ? trim($data['Notes']) : '',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $now,
                    $now,
                    $createdBy
                ]
            );

            $this->syncLines($invoiceRecId, $lines, $createdBy);
            $this->db->commit();

            return $invoiceRecId;
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update($id, $data, $createdBy)
    {
        $invoice = $this->findById($id);

        if (!$invoice) {
            throw new Exception('Invoice not found.');
        }

        $customer = $this->db->fetchOne('SELECT CustAccount, PartyId FROM CustTable WHERE CustAccount = ? LIMIT 1', [trim($data['CustAccount'])]);
        $company = $this->resolveCompany(isset($data['CompanyRecId']) ? $data['CompanyRecId'] : null);

        if (!$customer) {
            throw new Exception('Customer account not found.');
        }

        if (!$company) {
            throw new Exception('Default company not found.');
        }

        $this->db->beginTransaction();

        try {
            $lines = $this->normalizeLines(isset($data['Lines']) ? $data['Lines'] : []);
            $deductionAmount = $this->normalizeAmount(isset($data['DeductionAmount']) ? $data['DeductionAmount'] : 0);
            $this->db->execute(
                'UPDATE CustInvoiceJour SET CompanyRecId = ?, CompanyAlias = ?, CustAccount = ?, PartyId = ?, InvoiceNumber = ?, InvoiceDate = ?, DueDate = ?, TotalAmount = ?, TaxAmount = ?, DeductionAmount = ?, IsInternationalReplacement = ?, InternationalInvoiceNumber = ?, InternationalInvoiceSeries = ?, InternationalInvoiceDate = ?, Status = ?, Notes = ?, IsActive = ?, ModifiedDateTime = ? WHERE RecId = ?',
                [
                    (int) $company['RecId'],
                    $company['Alias'],
                    $customer['CustAccount'],
                    (int) $customer['PartyId'],
                    trim($data['InvoiceNumber']),
                    $this->dateTimeOrNow($data['InvoiceDate']),
                    $this->dateTimeOrNow($data['DueDate']),
                    $this->calculateInvoiceTotal($lines, $deductionAmount),
                    $this->normalizeAmount(isset($data['TaxAmount']) ? $data['TaxAmount'] : 0),
                    $deductionAmount,
                    $this->normalizeFlag(isset($data['IsInternationalReplacement']) ? $data['IsInternationalReplacement'] : '0', '0'),
                    isset($data['InternationalInvoiceNumber']) ? trim($data['InternationalInvoiceNumber']) : '',
                    isset($data['InternationalInvoiceSeries']) ? trim($data['InternationalInvoiceSeries']) : '',
                    !empty($data['InternationalInvoiceDate']) ? $this->dateTimeOrNow($data['InternationalInvoiceDate']) : null,
                    isset($data['Status']) ? strtoupper(substr(trim($data['Status']), 0, 1)) : 'O',
                    isset($data['Notes']) ? trim($data['Notes']) : '',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->now(),
                    (int) $id
                ]
            );

            $this->db->execute('DELETE FROM CustInvoiceTrans WHERE InvoiceRecId = ?', [(int) $id]);
            $this->syncLines((int) $id, $lines, $createdBy);
            $this->db->commit();
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE CustInvoiceJour SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }

    private function syncLines($invoiceRecId, $lines, $createdBy)
    {
        $lineNumber = 1;

        foreach ($this->normalizeLines($lines) as $line) {
            $this->db->insert(
                'INSERT INTO CustInvoiceTrans (InvoiceRecId, LineNum, ServiceCodeId, Description, LineAmount, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (int) $invoiceRecId,
                    $lineNumber,
                    (int) $line['ServiceCodeId'],
                    isset($line['Description']) ? trim($line['Description']) : '',
                    $this->normalizeAmount(isset($line['LineAmount']) ? $line['LineAmount'] : 0),
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
            $total += (float) (isset($line['LineAmount']) ? $line['LineAmount'] : 0);
        }

        return $this->normalizeAmount($total);
    }

    private function calculateInvoiceTotal($lines, $deductionAmount = 0)
    {
        $grossAmount = $this->calculateGrossAmount($lines);
        $netAmount = max(0, (float) $grossAmount - (float) $this->normalizeAmount($deductionAmount));

        return $this->normalizeAmount($netAmount);
    }

    private function normalizeLines($lines)
    {
        if (!is_array($lines)) {
            return [];
        }

        return array_values(array_filter($lines, function ($line) {
            return is_array($line) && (
                !empty($line['ServiceCodeId']) ||
                trim(isset($line['Description']) ? (string) $line['Description'] : '') !== '' ||
                (float) (isset($line['LineAmount']) ? $line['LineAmount'] : 0) > 0
            );
        }));
    }

    public function allOpenForJournal()
    {
        return $this->db->fetchAll(
            'SELECT i.RecId, i.InvoiceId, i.CustAccount, i.InvoiceNumber,
                    i.InvoiceDate, i.DueDate, i.TotalAmount,
                    p.Name AS CustomerName
             FROM CustInvoiceJour i
             INNER JOIN DirPartyTable p ON p.RecId = i.PartyId
             WHERE i.IsActive = "1"
               AND i.Status = "O"
               AND i.RecId NOT IN (
                   SELECT DISTINCT t.ServiceInvoiceRecId
                   FROM LedgerJournalTrans t
                   WHERE t.IsActive = "1" AND t.ServiceInvoiceRecId IS NOT NULL
               )
             ORDER BY i.InvoiceDate ASC, i.InvoiceId ASC'
        );
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