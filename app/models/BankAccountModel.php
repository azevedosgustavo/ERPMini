<?php

class BankAccountModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll('SELECT * FROM BankAccountTable WHERE IsActive = "1" ORDER BY BankName ASC, AccountNumber ASC');
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO BankAccountTable (BankName, AccountNumber, AccountDigit, Description, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['BankName']),
                trim($data['AccountNumber']),
                isset($data['AccountDigit']) ? trim($data['AccountDigit']) : '',
                isset($data['Description']) ? trim($data['Description']) : '',
                $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                $this->normalizeFlag(isset($data['IsBlocked']) ? $data['IsBlocked'] : '0', '0'),
                $now,
                $now,
                $createdBy
            ]
        );
    }

    public function update($id, $data)
    {
        return $this->db->execute(
            'UPDATE BankAccountTable SET BankName = ?, AccountNumber = ?, AccountDigit = ?, Description = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                trim($data['BankName']),
                trim($data['AccountNumber']),
                isset($data['AccountDigit']) ? trim($data['AccountDigit']) : '',
                isset($data['Description']) ? trim($data['Description']) : '',
                $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                $this->normalizeFlag(isset($data['IsBlocked']) ? $data['IsBlocked'] : '0', '0'),
                $this->now(),
                (int) $id
            ]
        );
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE BankAccountTable SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}
