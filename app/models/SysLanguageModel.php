<?php

class SysLanguageModel extends BaseModel
{
    public function allActive()
    {
        return $this->db->fetchAll(
            'SELECT LanguageId, Name
             FROM SysLanguage
             WHERE IsActive = "1"
             ORDER BY LanguageId ASC'
        );
    }
}