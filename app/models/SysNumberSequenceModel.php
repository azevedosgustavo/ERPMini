<?php

class SysNumberSequenceModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll('SELECT * FROM SysNumberSequenceTable ORDER BY ObjectCode ASC');
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO SysNumberSequenceTable (ObjectCode, ObjectName, CurrentNumber, NextNumber, FormatMask, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                strtoupper(trim($data['ObjectCode'])),
                trim($data['ObjectName']),
                isset($data['CurrentNumber']) ? (int) $data['CurrentNumber'] : 0,
                isset($data['NextNumber']) ? (int) $data['NextNumber'] : 1,
                trim($data['FormatMask']),
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
            'UPDATE SysNumberSequenceTable SET ObjectCode = ?, ObjectName = ?, CurrentNumber = ?, NextNumber = ?, FormatMask = ?, IsActive = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                strtoupper(trim($data['ObjectCode'])),
                trim($data['ObjectName']),
                isset($data['CurrentNumber']) ? (int) $data['CurrentNumber'] : 0,
                isset($data['NextNumber']) ? (int) $data['NextNumber'] : 1,
                trim($data['FormatMask']),
                $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                $this->now(),
                (int) $id
            ]
        );
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE SysNumberSequenceTable SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}
