<?php

class SysUserInfoModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll(
            'SELECT u.RecId, u.UserId, u.UserName, u.Email, u.RoleId, r.RoleCode, r.Name AS RoleName, u.LanguageId, sl.Name AS LanguageName, u.IsActive, u.IsBlocked, u.CreatedDateTime, u.ModifiedDateTime, u.CreatedBy
             FROM SysUserInfo u
             INNER JOIN SecurityRole r ON r.RecId = u.RoleId
             LEFT JOIN SysLanguage sl ON sl.LanguageId = u.LanguageId
             ORDER BY u.UserName ASC'
        );
    }

    public function findByEmail($email)
    {
        return $this->db->fetchOne(
            'SELECT u.*, r.RoleCode, r.Name AS RoleName, sl.Name AS LanguageName
             FROM SysUserInfo u
             INNER JOIN SecurityRole r ON r.RecId = u.RoleId
             LEFT JOIN SysLanguage sl ON sl.LanguageId = u.LanguageId
             WHERE u.Email = ? LIMIT 1',
            [trim($email)]
        );
    }

    public function findById($id)
    {
        return $this->db->fetchOne(
            'SELECT u.RecId, u.UserId, u.UserName, u.Email, u.RoleId, r.RoleCode, r.Name AS RoleName, u.LanguageId, sl.Name AS LanguageName, u.IsActive, u.IsBlocked, u.CreatedDateTime, u.ModifiedDateTime, u.CreatedBy
             FROM SysUserInfo u
             INNER JOIN SecurityRole r ON r.RecId = u.RoleId
             LEFT JOIN SysLanguage sl ON sl.LanguageId = u.LanguageId
             WHERE u.RecId = ? LIMIT 1',
            [(int) $id]
        );
    }

    public function create($data, $createdBy)
    {
        $now = $this->now();
        $password = isset($data['Password']) && $data['Password'] !== '' ? $data['Password'] : 'ChangeMe@123';
        $hash = md5($GLOBALS['app_config']['fixedSalt'] . $password);
        $userId = $this->generateNumber('SysUserInfo', 'UserId', 'USR');

        return $this->db->insert(
            'INSERT INTO SysUserInfo (UserId, UserName, Email, PasswordHash, RoleId, LanguageId, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                trim($data['UserName']),
                trim($data['Email']),
                $hash,
                (int) $data['RoleId'],
                isset($data['LanguageId']) && trim($data['LanguageId']) !== '' ? trim($data['LanguageId']) : 'PT-BR',
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
        $user = $this->findById($id);

        if (!$user) {
            throw new Exception('User not found.');
        }

        $hash = isset($data['Password']) && trim($data['Password']) !== ''
            ? md5($GLOBALS['app_config']['fixedSalt'] . trim($data['Password']))
            : $this->db->fetchOne('SELECT PasswordHash FROM SysUserInfo WHERE RecId = ?', [(int) $id])['PasswordHash'];

        return $this->db->execute(
            'UPDATE SysUserInfo SET UserName = ?, Email = ?, PasswordHash = ?, RoleId = ?, LanguageId = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                trim($data['UserName']),
                trim($data['Email']),
                $hash,
                (int) $data['RoleId'],
                isset($data['LanguageId']) && trim($data['LanguageId']) !== '' ? trim($data['LanguageId']) : 'PT-BR',
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
            'UPDATE SysUserInfo SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}