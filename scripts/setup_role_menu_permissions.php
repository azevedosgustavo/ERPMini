<?php

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$now = date('Y-m-d H:i:s');

echo "=== Criando Sistema de Permissões de Menu por Role ===\n\n";

// 1. Criar tabela de permissões
echo "1. Criando tabela RoleMenuPermission...\n";

$createTableSQL = "
CREATE TABLE IF NOT EXISTS RoleMenuPermission (
    RecId INT NOT NULL AUTO_INCREMENT,
    RoleId INT NOT NULL,
    MenuGroupId INT NOT NULL,
    IsVisible CHAR(1) NOT NULL DEFAULT '1',
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_RoleMenuPermission (RoleId, MenuGroupId),
    KEY IX_RoleMenuPermission_RoleId (RoleId),
    KEY IX_RoleMenuPermission_MenuGroupId (MenuGroupId),
    CONSTRAINT FK_RoleMenuPermission_SecurityRole FOREIGN KEY (RoleId) REFERENCES SecurityRole (RecId),
    CONSTRAINT FK_RoleMenuPermission_SysMenuGroup FOREIGN KEY (MenuGroupId) REFERENCES SysMenuGroup (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

try {
    $db->execute($createTableSQL);
    echo "   ✓ Tabela criada\n";
} catch (Exception $e) {
    echo "   ✓ Tabela já existe\n";
}

// 2. Obter IDs de roles e menu groups
echo "\n2. Obtendo IDs de roles e menu groups...\n";

$roles = $db->fetchAll("SELECT RecId, RoleCode FROM SecurityRole WHERE IsActive = '1'");
$menuGroups = $db->fetchAll("SELECT RecId, GroupCode FROM SysMenuGroup WHERE IsActive = '1'");

if (empty($roles) || empty($menuGroups)) {
    echo "   ERRO: Roles ou MenuGroups não encontrados\n";
    exit(1);
}

$roleMap = [];
foreach ($roles as $role) {
    $roleMap[$role['RoleCode']] = (int) $role['RecId'];
}

$menuGroupMap = [];
foreach ($menuGroups as $group) {
    $menuGroupMap[$group['GroupCode']] = (int) $group['RecId'];
}

// 3. Configurar permissões
echo "\n3. Configurando permissões:\n";

// ADMIN pode ver TUDO
echo "   - ADMIN: acesso total a todos os menus\n";
foreach ($menuGroupMap as $groupCode => $groupId) {
    $db->execute(
        "INSERT INTO RoleMenuPermission (RoleId, MenuGroupId, IsVisible, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
         VALUES (?, ?, '1', '1', ?, ?, 'SYSTEM')
         ON DUPLICATE KEY UPDATE IsVisible = '1', ModifiedDateTime = VALUES(ModifiedDateTime)",
        [$roleMap['ADMIN'], $groupId, $now, $now]
    );
}

// USER não pode ver SYSADMIN
echo "   - USER: bloqueado de menu Administração de Sistemas (SYSADMIN)\n";
foreach ($menuGroupMap as $groupCode => $groupId) {
    $visible = ($groupCode === 'SYSADMIN') ? '0' : '1';
    $db->execute(
        "INSERT INTO RoleMenuPermission (RoleId, MenuGroupId, IsVisible, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
         VALUES (?, ?, ?, '1', ?, ?, 'SYSTEM')
         ON DUPLICATE KEY UPDATE IsVisible = ?, ModifiedDateTime = VALUES(ModifiedDateTime)",
        [$roleMap['USER'], $groupId, $visible, $now, $now, $visible]
    );
    if ($visible === '0') {
        echo "     ✗ {$groupCode}\n";
    } else {
        echo "     ✓ {$groupCode}\n";
    }
}

echo "\n✅ Sistema de permissões de menu criado e configurado com sucesso!\n";
echo "\nResumo:\n";
echo "- ADMIN: pode acessar todos os menus\n";
echo "- USER: não pode ver o menu 'Administração de Sistemas'\n";
