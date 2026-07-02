<?php

class InventTableModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll('SELECT * FROM InventTable ORDER BY Name ASC');
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO InventTable (ItemId, Name, Description, UnitOfMeasure, ItemType, SalesPrice, CostPrice, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->generateNumber('InventTable', 'ItemId', 'ITEM'),
                trim($data['Name']),
                isset($data['Description']) ? trim($data['Description']) : '',
                isset($data['UnitOfMeasure']) ? trim($data['UnitOfMeasure']) : 'UN',
                $this->normalizeItemType($data['ItemType']),
                $this->normalizeAmount(isset($data['SalesPrice']) ? $data['SalesPrice'] : 0),
                $this->normalizeAmount(isset($data['CostPrice']) ? $data['CostPrice'] : 0),
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
            'UPDATE InventTable SET Name = ?, Description = ?, UnitOfMeasure = ?, ItemType = ?, SalesPrice = ?, CostPrice = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                trim($data['Name']),
                isset($data['Description']) ? trim($data['Description']) : '',
                isset($data['UnitOfMeasure']) ? trim($data['UnitOfMeasure']) : 'UN',
                $this->normalizeItemType($data['ItemType']),
                $this->normalizeAmount(isset($data['SalesPrice']) ? $data['SalesPrice'] : 0),
                $this->normalizeAmount(isset($data['CostPrice']) ? $data['CostPrice'] : 0),
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
            'UPDATE InventTable SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }

    private function normalizeItemType($value)
    {
        $normalized = strtoupper(substr(trim($value), 0, 1));
        return $normalized === 'S' ? 'S' : 'I';
    }
}