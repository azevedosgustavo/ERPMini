<?php
require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$conn = $db->getConnection();
$now = date('Y-m-d H:i:s');

// Load credentials from environment variables
$newEmail = getenv('DEFAULT_ADMIN_EMAIL') ?: 'admin@admin';
$newPassword = getenv('DEFAULT_ADMIN_PASSWORD') ?: 'admin';
$newUserName = getenv('DEFAULT_ADMIN_USERNAME') ?: 'Admin';
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

    echo "[SUCCESS] FISCAL menu group configured\n";

    echo "\n=== STEP 2: RESETTING ADMIN USER ===\n";

    $adminRole = $db->fetchOne("SELECT RecId FROM SecurityRole WHERE RoleCode = 'ADMIN' LIMIT 1");
    if (!$adminRole) {
        throw new Exception("ADMIN role not found in database");
    }
    $adminRoleId = (int) $adminRole['RecId'];

    $userId = mt_rand(100000, 999999);

    $passwordHash = md5($GLOBALS['app_config']['fixedSalt'] . $newPassword);

    $db->execute(
        "INSERT INTO SysUserInfo (UserId, UserName, Email, PasswordHash, RoleId, LanguageId, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy)
         VALUES (?, ?, ?, ?, ?, ?, '1', '0', ?, ?, 'SYSTEM')
         ON DUPLICATE KEY UPDATE PasswordHash = VALUES(PasswordHash), Email = VALUES(Email), UserName = VALUES(UserName), ModifiedDateTime = VALUES(ModifiedDateTime)",
        [$userId, $newUserName, $newEmail, $passwordHash, $adminRoleId, $newLanguageId, $now, $now]
    );

    echo "Admin user configured: $newUserName ($newEmail)\n";

    echo "\n=== STEP 3: CONFIGURING ADMIN MENUS ===\n";

    $menuGroups = [
        'OPERACAO' => 'menu.group.operacao',
        'CONFIG' => 'menu.group.config',
        'FISCAL' => 'menu.group.fiscal',
        'RELATORIOS' => 'menu.group.relatorios',
    ];

    foreach ($menuGroups as $groupCode => $labelKey) {
        $group = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = ? LIMIT 1", [$groupCode]);
        if ($group) {
            $groupId = (int) $group['RecId'];
            $db->execute(
                "INSERT INTO SecurityRoleMenuGroup (RoleId, MenuGroupId, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                 VALUES (?, ?, '1', ?, ?, 'SYSTEM')
                 ON DUPLICATE KEY UPDATE IsActive = '1', ModifiedDateTime = VALUES(ModifiedDateTime)",
                [$adminRoleId, $groupId, $now, $now]
            );
            echo "Menu group $groupCode granted to ADMIN\n";
        }
    }

    mysqli_commit($conn);
    echo "\n[SUCCESS] Menu and user reset completed successfully.\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
