<?php

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

$updates = [
    ['GENERAL', 1],
    ['AR', 2],
    ['AP', 3],
    ['FISCAL', 4],
    ['REPORTS', 5],
    ['SYSADMIN', 6]
];

echo "=== Reordenando Menus ===\n\n";

foreach ($updates as [$code, $seq]) {
    $db->execute('UPDATE SysMenuGroup SET SequenceNo = ? WHERE GroupCode = ?', [$seq, $code]);
    echo "✓ $code -> SequenceNo $seq\n";
}

echo "\n✅ Menus reordenados com sucesso!\n";
