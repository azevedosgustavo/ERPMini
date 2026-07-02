<?php

class SysLabelTextModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll(
            'SELECT l.RecId, l.LabelKey, l.LanguageId, lg.Name AS LanguageName, l.TextValue, l.IsActive, l.CreatedDateTime, l.ModifiedDateTime, l.CreatedBy
             FROM SysLabelText l
             INNER JOIN SysLanguage lg ON lg.LanguageId = l.LanguageId
             ORDER BY l.LabelKey ASC, l.LanguageId ASC'
        );
    }

    public function labelsByLanguage($languageId)
    {
        $rows = $this->db->fetchAll(
            'SELECT LabelKey, TextValue
             FROM SysLabelText
             WHERE LanguageId = ? AND IsActive = "1"',
            [$languageId]
        );

        $result = [];

        foreach ($rows as $row) {
            $result[$row['LabelKey']] = $row['TextValue'];
        }

        return $result;
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['LabelKey']),
                trim($data['LanguageId']),
                trim($data['TextValue']),
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
            'UPDATE SysLabelText SET LabelKey = ?, LanguageId = ?, TextValue = ?, IsActive = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                trim($data['LabelKey']),
                trim($data['LanguageId']),
                trim($data['TextValue']),
                $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                $this->now(),
                (int) $id
            ]
        );
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE SysLabelText SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}