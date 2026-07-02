<?php

class LedgerJournalModel extends BaseModel
{
    public function all($journalType = 'GEN')
    {
        return $this->db->fetchAll(
            'SELECT * FROM LedgerJournalTable WHERE IsActive = "1" AND JournalType = ? ORDER BY JournalDate DESC, JournalBatchNumber DESC',
            [$this->normalizeJournalType($journalType)]
        );
    }

    public function findById($id, $journalType = null)
    {
        if ($journalType === null) {
            $journal = $this->db->fetchOne('SELECT * FROM LedgerJournalTable WHERE RecId = ? AND IsActive = "1" LIMIT 1', [(int) $id]);
        } else {
            $journal = $this->db->fetchOne(
                'SELECT * FROM LedgerJournalTable WHERE RecId = ? AND JournalType = ? AND IsActive = "1" LIMIT 1',
                [(int) $id, $this->normalizeJournalType($journalType)]
            );
        }

        if (!$journal) {
            return null;
        }

        $journal['Lines'] = $this->db->fetchAll(
            'SELECT * FROM LedgerJournalTrans WHERE JournalRecId = ? AND IsActive = "1" ORDER BY LineNum ASC',
            [(int) $id]
        );

        return $journal;
    }

    public function create($data, $createdBy, $journalType = 'GEN')
    {
        $this->db->beginTransaction();

        try {
            $normalizedType = $this->normalizeJournalType($journalType);
            $company = $this->resolveCompany(isset($data['CompanyRecId']) ? $data['CompanyRecId'] : null);
            $batchNumber = $this->generateNumber('LedgerJournalTable', 'JournalBatchNumber', $this->resolveJournalPrefix($normalizedType));
            $lines = isset($data['Lines']) ? $data['Lines'] : [];
            $this->validatePostingRules($normalizedType, $this->normalizeFlag(isset($data['Posted']) ? $data['Posted'] : '0', '0'), $lines);

            if (!$company) {
                throw new Exception('Default company not found.');
            }

            $now = $this->now();
            $journalRecId = $this->db->insert(
                'INSERT INTO LedgerJournalTable (JournalBatchNumber, JournalType, CompanyRecId, CompanyAlias, Description, JournalDate, Posted, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $batchNumber,
                    $normalizedType,
                    (int) $company['RecId'],
                    $company['Alias'],
                    trim($data['Description']),
                    $this->dateTimeOrNow($data['JournalDate']),
                    $this->normalizeFlag(isset($data['Posted']) ? $data['Posted'] : '0', '0'),
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $now,
                    $now,
                    $createdBy
                ]
            );

            $this->syncLines($journalRecId, $lines, $createdBy);
            $this->db->commit();
            return $journalRecId;
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update($id, $data, $createdBy, $journalType = 'GEN')
    {
        $normalizedType = $this->normalizeJournalType($journalType);
        $journal = $this->findById($id, $normalizedType);
        $company = $this->resolveCompany(isset($data['CompanyRecId']) ? $data['CompanyRecId'] : null);

        if (!$journal) {
            throw new Exception('Journal not found.');
        }

        if (!$company) {
            throw new Exception('Default company not found.');
        }

        if ($this->normalizeFlag(isset($journal['Posted']) ? $journal['Posted'] : '0', '0') === '1') {
            throw new Exception('Posted journals cannot be changed.');
        }

        $lines = isset($data['Lines']) ? $data['Lines'] : [];
        $this->validatePostingRules($normalizedType, $this->normalizeFlag(isset($data['Posted']) ? $data['Posted'] : '0', '0'), $lines);

        $this->db->beginTransaction();

        try {
            $this->db->execute(
                'UPDATE LedgerJournalTable SET CompanyRecId = ?, CompanyAlias = ?, Description = ?, JournalDate = ?, Posted = ?, IsActive = ?, ModifiedDateTime = ? WHERE RecId = ?',
                [
                    (int) $company['RecId'],
                    $company['Alias'],
                    trim($data['Description']),
                    $this->dateTimeOrNow($data['JournalDate']),
                    $this->normalizeFlag(isset($data['Posted']) ? $data['Posted'] : '0', '0'),
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->now(),
                    (int) $id
                ]
            );

            // Keep audit history by deactivating previous rows instead of hard deleting.
            $this->db->execute('UPDATE LedgerJournalTrans SET IsActive = "0", ModifiedDateTime = ? WHERE JournalRecId = ?', [$this->now(), (int) $id]);
            $this->syncLines((int) $id, $lines, $createdBy);
            $this->db->commit();
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function delete($id, $journalType = 'GEN')
    {
        $journal = $this->findById($id, $journalType);

        if (!$journal) {
            throw new Exception('Journal not found.');
        }

        if ($this->normalizeFlag(isset($journal['Posted']) ? $journal['Posted'] : '0', '0') === '1') {
            throw new Exception('Posted journals cannot be deleted.');
        }

        $this->db->beginTransaction();

        try {
            $this->db->execute(
                'UPDATE LedgerJournalTable SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
                [$this->now(), (int) $id]
            );
            $this->db->execute(
                'UPDATE LedgerJournalTrans SET IsActive = "0", ModifiedDateTime = ? WHERE JournalRecId = ?',
                [$this->now(), (int) $id]
            );
            $this->db->commit();
            return true;
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function syncLines($journalRecId, $lines, $createdBy)
    {
        $lineNumber = 1;

        foreach ($lines as $line) {
            $transDate = $this->dateTimeOrNow($line['TransDate']);
            $periodMonth = isset($line['PeriodMonth']) && trim($line['PeriodMonth']) !== ''
                ? trim($line['PeriodMonth'])
                : date('Y-m', strtotime($transDate));

            $resolvedCategory = $this->resolveLedgerCategory($line);

            $this->db->insert(
                'INSERT INTO LedgerJournalTrans (JournalRecId, LineNum, TransDate, DueDate, Voucher, TaxTypeId, BankAccountId, VendAccount, CustAccount, ServiceInvoiceRecId, PurchRecId, PaymentMethod, PaymentDate, PaidFlag, ReceivedFlag, Description, LedgerCategory, LedgerCategoryId, AmountCurDebit, AmountCurCredit, PeriodMonth, Status, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (int) $journalRecId,
                    $lineNumber,
                    $transDate,
                    $this->dateTimeOrNow(isset($line['DueDate']) ? $line['DueDate'] : $transDate),
                    isset($line['Voucher']) ? trim($line['Voucher']) : '',
                    !empty($line['TaxTypeId']) ? (int) $line['TaxTypeId'] : null,
                    !empty($line['BankAccountId']) ? (int) $line['BankAccountId'] : null,
                    isset($line['VendAccount']) && trim($line['VendAccount']) !== '' ? trim($line['VendAccount']) : null,
                    isset($line['CustAccount']) && trim($line['CustAccount']) !== '' ? trim($line['CustAccount']) : null,
                    !empty($line['ServiceInvoiceRecId']) ? (int) $line['ServiceInvoiceRecId'] : null,
                    !empty($line['PurchRecId']) ? (int) $line['PurchRecId'] : null,
                    isset($line['PaymentMethod']) ? trim($line['PaymentMethod']) : '',
                    !empty($line['PaymentDate']) ? $this->dateTimeOrNow($line['PaymentDate']) : null,
                    $this->normalizeFlag(isset($line['PaidFlag']) ? $line['PaidFlag'] : '0', '0'),
                    $this->normalizeFlag(isset($line['ReceivedFlag']) ? $line['ReceivedFlag'] : '0', '0'),
                    isset($line['Description']) ? trim($line['Description']) : '',
                    $resolvedCategory['code'],
                    $resolvedCategory['id'],
                    $this->normalizeAmount(isset($line['AmountCurDebit']) ? $line['AmountCurDebit'] : 0),
                    $this->normalizeAmount(isset($line['AmountCurCredit']) ? $line['AmountCurCredit'] : 0),
                    $periodMonth,
                    isset($line['Status']) ? strtoupper(substr(trim($line['Status']), 0, 1)) : 'O',
                    $this->normalizeFlag(isset($line['IsActive']) ? $line['IsActive'] : '1', '1'),
                    $this->now(),
                    $this->now(),
                    $createdBy
                ]
            );

            $lineNumber++;
        }
    }

    private function resolveLedgerCategory($line)
    {
        $defaultCode = 'OPERATING';

        if (!empty($line['LedgerCategoryId'])) {
            $categoryId = (int) $line['LedgerCategoryId'];
            $row = $this->db->fetchOne(
                'SELECT RecId, CategoryCode FROM LedgerCategoryTable WHERE RecId = ? AND IsActive = "1" LIMIT 1',
                [$categoryId]
            );
            if ($row) {
                return ['id' => (int) $row['RecId'], 'code' => $row['CategoryCode']];
            }
        }

        if (!empty($line['LedgerCategory'])) {
            $categoryCode = strtoupper(trim($line['LedgerCategory']));
            $row = $this->db->fetchOne(
                'SELECT RecId, CategoryCode FROM LedgerCategoryTable WHERE CategoryCode = ? AND IsActive = "1" LIMIT 1',
                [$categoryCode]
            );
            if ($row) {
                return ['id' => (int) $row['RecId'], 'code' => $row['CategoryCode']];
            }
            return ['id' => null, 'code' => $categoryCode];
        }

        $default = $this->db->fetchOne(
            'SELECT RecId, CategoryCode FROM LedgerCategoryTable WHERE CategoryCode = ? AND IsActive = "1" LIMIT 1',
            [$defaultCode]
        );
        if ($default) {
            return ['id' => (int) $default['RecId'], 'code' => $default['CategoryCode']];
        }

        return ['id' => null, 'code' => $defaultCode];
    }

    private function normalizeJournalType($value)
    {
        $normalized = strtoupper(substr(trim((string) $value), 0, 3));
        return in_array($normalized, ['GEN', 'TAX', 'PAY', 'REC']) ? $normalized : 'GEN';
    }

    private function resolveJournalPrefix($journalType)
    {
        if ($journalType === 'TAX') return 'TAXJ';
        if ($journalType === 'PAY') return 'PAYJ';
        if ($journalType === 'REC') return 'RECJ';
        return 'JRN';
    }

    private function validatePostingRules($journalType, $postedFlag, $lines)
    {
        if ($postedFlag !== '1') {
            return;
        }

        if ($journalType === 'TAX' || $journalType === 'PAY') {
            foreach ($lines as $line) {
                if ($this->normalizeFlag(isset($line['PaidFlag']) ? $line['PaidFlag'] : '0', '0') !== '1') {
                    throw new Exception('Journal posting requires all lines marked as paid.');
                }
            }
        }

        if ($journalType === 'REC') {
            foreach ($lines as $line) {
                if ($this->normalizeFlag(isset($line['ReceivedFlag']) ? $line['ReceivedFlag'] : '0', '0') !== '1') {
                    throw new Exception('Journal posting requires all lines marked as received.');
                }
            }
        }
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