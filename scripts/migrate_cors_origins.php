<?php

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

// 1. Create table
$db->execute(
    "CREATE TABLE IF NOT EXISTS SysCorsOrigin (
        RecId            INT          NOT NULL AUTO_INCREMENT,
        Origin           VARCHAR(255) NOT NULL,
        Description      VARCHAR(500) NOT NULL DEFAULT '',
        IsActive         CHAR(1)      NOT NULL DEFAULT '1',
        CreatedDateTime  DATETIME,
        ModifiedDateTime DATETIME,
        CreatedBy        VARCHAR(50),
        CONSTRAINT PK_SysCorsOrigin PRIMARY KEY (RecId)
    )"
);
echo "Table SysCorsOrigin created/verified.\n";

// 2. Seed default origins
$now = date('Y-m-d H:i:s');

$defaults = [
    ['origin' => '127.0.0.1',        'description' => 'Servidor local de desenvolvimento'],
    ['origin' => 'www.caspti.com.br', 'description' => 'Site de producao CASPTI'],
];

foreach ($defaults as $entry) {
    $existing = $db->fetchOne('SELECT RecId FROM SysCorsOrigin WHERE Origin = ? LIMIT 1', [$entry['origin']]);

    if (!$existing) {
        $db->insert(
            'INSERT INTO SysCorsOrigin (Origin, Description, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$entry['origin'], $entry['description'], '1', $now, $now, 'SYSTEM']
        );
        echo "  Inserted CORS origin: {$entry['origin']}\n";
    } else {
        echo "  Origin already exists: {$entry['origin']}\n";
    }
}

// 3. Add menu item under SECURITY group
$securityGroup = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'SECURITY' LIMIT 1");

if (!$securityGroup) {
    echo "ERROR: SECURITY menu group not found. Skipping menu registration.\n";
} else {
    $securityGroupId = (int) $securityGroup['RecId'];

    $existing = $db->fetchOne("SELECT RecId FROM SysMenuItem WHERE MenuCode = 'CORS_ORIGINS' LIMIT 1");

    if (!$existing) {
        $maxSeq = $db->fetchOne('SELECT MAX(SequenceNo) AS MaxSeq FROM SysMenuItem WHERE GroupId = ?', [$securityGroupId]);
        $nextSeq = ($maxSeq && $maxSeq['MaxSeq'] !== null) ? (int) $maxSeq['MaxSeq'] + 10 : 10;

        $db->insert(
            'INSERT INTO SysMenuItem (GroupId, ParentMenuId, MenuCode, LabelKey, ViewKey, SequenceNo, IsActive)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$securityGroupId, 0, 'CORS_ORIGINS', 'menu.cors.origins', 'cors-origins', $nextSeq, '1']
        );
        echo "  Menu item CORS_ORIGINS created in SECURITY group (seq {$nextSeq}).\n";
    } else {
        echo "  Menu item CORS_ORIGINS already exists.\n";
    }
}

// 4. Seed labels for PT-BR
$labels = [
    'menu.cors.origins'          => 'Origens CORS',
    'module.corsorigins.title'   => 'Origens CORS',
    'module.corsorigins.subtitle'=> 'Gerencie as origens permitidas para chamadas de API via CORS.',
];

foreach ($labels as $key => $value) {
    $existing = $db->fetchOne(
        "SELECT RecId FROM SysLabelText WHERE LabelKey = ? AND LanguageId = 'PT-BR' LIMIT 1",
        [$key]
    );

    if (!$existing) {
        $db->insert(
            "INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime)
             VALUES (?, 'PT-BR', ?, '1', ?, ?)",
            [$key, $value, $now, $now]
        );
        echo "  Label inserted: {$key}\n";
    } else {
        echo "  Label exists: {$key}\n";
    }
}

echo "\nMigration completed successfully.\n";
