<?php
require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== APPLY COMPANY OPENING BALANCE ===\n";

$columns = [];
$res = $conn->query("SHOW COLUMNS FROM CompanyInfo");
while ($row = $res->fetch_assoc()) {
    $columns[$row['Field']] = true;
}

if (!isset($columns['InitialBalance'])) {
    $conn->query("ALTER TABLE CompanyInfo ADD COLUMN InitialBalance DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER MainLogoBase64");
    echo "Added column: InitialBalance\n";
} else {
    echo "Column already exists: InitialBalance\n";
}

if (!isset($columns['InitialBalanceDate'])) {
    $conn->query("ALTER TABLE CompanyInfo ADD COLUMN InitialBalanceDate DATE NULL AFTER InitialBalance");
    echo "Added column: InitialBalanceDate\n";
} else {
    echo "Column already exists: InitialBalanceDate\n";
}

$initialBalance = 21067.69;
$initialDate = '2025-01-01';

$db->execute(
    'UPDATE CompanyInfo
     SET InitialBalance = ?, InitialBalanceDate = ?, ModifiedDateTime = ?
     WHERE IsActive = "1"',
    [$initialBalance, $initialDate, date('Y-m-d H:i:s')]
);

echo "Updated active companies with InitialBalance=21067.69 and InitialBalanceDate=2025-01-01\n";

$rows = $db->fetchAll('SELECT RecId, Alias, InitialBalance, InitialBalanceDate FROM CompanyInfo WHERE IsActive = "1" ORDER BY IsDefault DESC, RecId ASC');
foreach ($rows as $r) {
    printf("Company RecId:%d | %s | Balance:%.2f | Date:%s\n", $r['RecId'], $r['Alias'], $r['InitialBalance'], $r['InitialBalanceDate']);
}

echo "Done.\n";
