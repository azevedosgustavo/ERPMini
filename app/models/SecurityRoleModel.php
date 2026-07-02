<?php

class SecurityRoleModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll('SELECT * FROM SecurityRole ORDER BY Name ASC');
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO SecurityRole (RoleCode, Name, Description, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                strtoupper(trim($data['RoleCode'])),
                trim($data['Name']),
                trim($data['Description']),
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
            'UPDATE SecurityRole SET RoleCode = ?, Name = ?, Description = ?, IsActive = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                strtoupper(trim($data['RoleCode'])),
                trim($data['Name']),
                trim($data['Description']),
                $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                $this->now(),
                (int) $id
            ]
        );
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE SecurityRole SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}