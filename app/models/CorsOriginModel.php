<?php

class CorsOriginModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll(
            'SELECT RecId, Origin, Description, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy
             FROM SysCorsOrigin
             ORDER BY Origin ASC'
        );
    }

    public function allActive()
    {
        return $this->db->fetchAll(
            'SELECT Origin FROM SysCorsOrigin WHERE IsActive = "1"'
        );
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO SysCorsOrigin (Origin, Description, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                trim($data['Origin']),
                isset($data['Description']) ? trim($data['Description']) : '',
                $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                $now,
                $now,
                $createdBy
            ]
        );
    }

    public function update($id, $data)
    {
        return $this->db->execute(
            'UPDATE SysCorsOrigin SET Origin = ?, Description = ?, IsActive = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                trim($data['Origin']),
                isset($data['Description']) ? trim($data['Description']) : '',
                $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                $this->now(),
                (int) $id
            ]
        );
    }

    public function delete($id)
    {
        return $this->db->execute(
            'DELETE FROM SysCorsOrigin WHERE RecId = ?',
            [(int) $id]
        );
    }
}
