<?php
require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$conn = $db->getConnection();
$now = date('Y-m-d H:i:s');

$newEmail = 'gustavo.azevedo@caspti.com.br';
$newPassword = '@ME89336@';
$newUserName = 'Gustavo Azevedo';
$newLanguageId = 'PT-BR';

mysqli_begin_transaction($conn);

try {
    echo "=== STEP 1: MENU FISCAL ===\n";

    $fiscalGroup = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'FISCAL' LIMIT 1");

    if ($fiscalGroup) {
        $fiscalGroupId = (int) $fiscalGroup['RecId'];
        echo "FISCAL group already exists (RecId={$fiscalGroupId})\n";
    } else {
        $maxSeq = $db->fetchOne("SELECT IFNULL(MAX(SequenceNo), 0) AS MaxSeq FROM SysMenuGroup");
        $nextSeq = ((int) $maxSeq['MaxSeq']) + 1;

        $db->execute(
            "INSERT INTO SysMenuGroup (GroupCode, LabelKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
             VALUES ('FISCAL', 'menu.group.fiscal', ?, '1', ?, ?, 'SYSTEM')",
            [$nextSeq, $now, $now]
        );

        $inserted = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'FISCAL' LIMIT 1");
        $fiscalGroupId = (int) $inserted['RecId'];
        echo "FISCAL group created (RecId={$fiscalGroupId})\n";
    }

    $labelRows = [
        ['menu.group.fiscal', 'PT-BR', 'Fiscal'],
        ['menu.group.fiscal', 'EN-US', 'Fiscal'],
    ];

    foreach ($labelRows as $labelRow) {
        $db->execute(
            "INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
             VALUES (?, ?, ?, '1', ?, ?, 'SYSTEM')
             ON DUPLICATE KEY UPDATE TextValue = VALUES(TextValue), IsActive = '1', ModifiedDateTime = VALUES(ModifiedDateTime)",
            [$labelRow[0], $labelRow[1], $labelRow[2], $now, $now]
        );
    }

    $menuRows = $db->fetchAll(
        "SELECT RecId, MenuCode, ViewKey
         FROM SysMenuItem
         WHERE ViewKey IN ('journals', 'tax-journals')"
    );

    if (!$menuRows) {
        throw new Exception('Menu items journals/tax-journals were not found.');
    }

    $seqBaseRow = $db->fetchOne("SELECT IFNULL(MAX(SequenceNo), 0) AS MaxSeq FROM SysMenuItem WHERE GroupId = ?", [$fiscalGroupId]);
    $nextMenuSeq = ((int) $seqBaseRow['MaxSeq']) + 1;

    foreach ($menuRows as $menuRow) {
        $db->execute(
            "UPDATE SysMenuItem
             SET GroupId = ?, ParentMenuId = 0, SequenceNo = ?, IsActive = '1', ModifiedDateTime = ?
             WHERE RecId = ?",
            [$fiscalGroupId, $nextMenuSeq, $now, (int) $menuRow['RecId']]
        );

        echo "Moved {$menuRow['MenuCode']} ({$menuRow['ViewKey']}) to FISCAL seq {$nextMenuSeq}\n";
        $nextMenuSeq++;
    }

    echo "\n=== STEP 2: RESET USERS ===\n";

    $adminRole = $db->fetchOne("SELECT RecId FROM SecurityRole WHERE RoleCode = 'ADMIN' LIMIT 1");
    if (!$adminRole) {
        throw new Exception('ADMIN role not found in SecurityRole.');
    }
    $adminRoleId = (int) $adminRole['RecId'];

    $beforeCount = $db->fetchOne("SELECT COUNT(*) AS Cnt FROM SysUserInfo");
    $deleted = $db->execute("DELETE FROM SysUserInfo");

    $userId = 'USR00001';
    $numberSeq = $db->fetchOne("SELECT RecId, NextNumber FROM SysNumberSequenceTable WHERE ObjectCode = 'USR' LIMIT 1");
    if ($numberSeq) {
        $nextNumber = (int) $numberSeq['NextNumber'];
        if ($nextNumber <= 0) {
            $nextNumber = 1;
        }
        $userId = sprintf('USR%05d', $nextNumber);
        $db->execute(
            "UPDATE SysNumberSequenceTable SET CurrentNumber = ?, NextNumber = ?, ModifiedDateTime = ? WHERE RecId = ?",
            [$nextNumber, $nextNumber + 1, $now, (int) $numberSeq['RecId']]
        );
    }

    $passwordHash = md5($GLOBALS['app_config']['fixedSalt'] . $newPassword);

    $db->execute(
        "INSERT INTO SysUserInfo (UserId, UserName, Email, PasswordHash, RoleId, LanguageId, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy)
         VALUES (?, ?, ?, ?, ?, ?, '1', '0', ?, ?, 'SYSTEM')",
        [$userId, $newUserName, $newEmail, $passwordHash, $adminRoleId, $newLanguageId, $now, $now]
    );

    $finalUser = $db->fetchOne(
        "SELECT UserId, UserName, Email, IsActive, IsBlocked FROM SysUserInfo WHERE Email = ? LIMIT 1",
        [$newEmail]
    );

    if (!$finalUser) {
        throw new Exception('New user was not created.');
    }

    mysqli_commit($conn);

    echo "Users before reset: {$beforeCount['Cnt']}\n";
    echo "Users deleted: {$deleted}\n";
    echo "New user created: {$finalUser['Email']} ({$finalUser['UserId']})\n";
    echo "\nDONE\n";
} catch (Exception $exception) {
    mysqli_rollback($conn);
    fwrite(STDERR, "ERROR: " . $exception->getMessage() . "\n");
    exit(1);
}
