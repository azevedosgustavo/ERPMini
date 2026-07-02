<?php
require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

echo "=== ENSURE OTHER_RECEIPT CATEGORY ===\n";

$existing = $db->fetchOne(
    'SELECT RecId, CategoryCode, Name, CategoryType
     FROM LedgerCategoryTable
     WHERE CategoryCode = ?
     LIMIT 1',
    ['OTHER_RECEIPT']
);

if ($existing) {
    echo "Category already exists: RecId {$existing['RecId']} | {$existing['CategoryCode']} | {$existing['Name']}\n";
} else {
    $now = date('Y-m-d H:i:s');
    $db->insert(
        'INSERT INTO LedgerCategoryTable (CategoryCode, Name, Description, CategoryType, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
         VALUES (?, ?, ?, ?, "1", ?, ?, ?)',
        [
            'OTHER_RECEIPT',
            'Outros Recebimentos',
            'Recebimentos sem fatura de serviço para contas a receber.',
            'R',
            $now,
            $now,
            'SYSTEM'
        ]
    );
    echo "Category created: OTHER_RECEIPT\n";
}

echo "Done.\n";
