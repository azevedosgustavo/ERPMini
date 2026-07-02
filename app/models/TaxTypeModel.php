<?php

class TaxTypeModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll('SELECT * FROM TaxTypeTable WHERE IsActive = "1" ORDER BY Name ASC');
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO TaxTypeTable (TaxTypeCode, Name, Description, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->generateNumber('TaxTypeTable', 'TaxTypeCode', 'TAXT'),
                trim($data['Name']),
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
            'UPDATE TaxTypeTable SET Name = ?, Description = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                trim($data['Name']),
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
            'UPDATE TaxTypeTable SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}
