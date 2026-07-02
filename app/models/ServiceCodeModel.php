<?php

class ServiceCodeModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll('SELECT * FROM ServiceCodeTable ORDER BY Name ASC');
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO ServiceCodeTable (ServiceCode, Name, Description, DefaultPrice, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->generateNumber('ServiceCodeTable', 'ServiceCode', 'SRV'),
                trim($data['Name']),
                isset($data['Description']) ? trim($data['Description']) : '',
                $this->normalizeAmount(isset($data['DefaultPrice']) ? $data['DefaultPrice'] : 0),
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
            'UPDATE ServiceCodeTable SET Name = ?, Description = ?, DefaultPrice = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                trim($data['Name']),
                isset($data['Description']) ? trim($data['Description']) : '',
                $this->normalizeAmount(isset($data['DefaultPrice']) ? $data['DefaultPrice'] : 0),
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
            'UPDATE ServiceCodeTable SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}