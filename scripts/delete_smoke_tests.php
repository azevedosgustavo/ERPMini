<?php
/**
 * Delete smoke test entries:
 * - TrId:2 (SMOKE-JRN, Active:1) → soft delete
 * - Journal RECJ00002 (RecId:2) + all its transactions → soft delete
 * - Journal PAYJ00003 (RecId:3) + TrId:4 → soft delete
 * - Rename JRN00001 "Journal Smoke" → "Lançamentos Gerais 2025"
 */
require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== DELETE SMOKE TESTS ===\n\n";

// 1. Soft delete TrId:2 (SMOKE-JRN still active)
$res = $conn->query("UPDATE LedgerJournalTrans SET IsActive = '0' WHERE RecId = 2 AND Voucher = 'SMOKE-JRN'");
echo "TrId:2 (SMOKE-JRN) deactivated: " . ($conn->affected_rows > 0 ? "YES ({$conn->affected_rows} row)" : "already inactive or not found") . "\n";

// 2. Soft delete all transactions in journals 2 and 3
$res = $conn->query("UPDATE LedgerJournalTrans SET IsActive = '0' WHERE JournalRecId IN (2, 3)");
echo "Transactions in journals 2 & 3 deactivated: {$conn->affected_rows} row(s)\n";

// 3. Soft delete journals 2 and 3
$res = $conn->query("UPDATE LedgerJournalTable SET IsActive = '0' WHERE RecId IN (2, 3)");
echo "Journals RECJ00002 & PAYJ00003 deactivated: {$conn->affected_rows} row(s)\n";

// 4. Rename journal 1 from "Journal Smoke" to "Lançamentos Gerais 2025"
$stmt = $conn->prepare("UPDATE LedgerJournalTable SET Description = ? WHERE RecId = 1 AND Description = 'Journal Smoke'");
$desc = 'Lançamentos Gerais 2025';
$stmt->bind_param('s', $desc);
$stmt->execute();
echo "Journal JRN00001 renamed: " . ($stmt->affected_rows > 0 ? "YES" : "already renamed or not found") . "\n";
$stmt->close();

echo "\n=== VERIFICATION ===\n";

// Show remaining active journals
$rows = $conn->query("SELECT RecId, JournalBatchNumber, JournalType, Description, IsActive FROM LedgerJournalTable ORDER BY RecId");
echo "\nAll journals:\n";
while ($r = $rows->fetch_assoc()) {
    $status = $r['IsActive'] == '1' ? 'ACTIVE' : 'INACTIVE';
    printf("RecId:%-3s | %-12s | %s | %-40s | %s\n",
        $r['RecId'], $r['JournalBatchNumber'], $r['JournalType'], $r['Description'], $status);
}

// Show remaining active smoke-related transactions
$rows = $conn->query("SELECT RecId, Voucher, IsActive FROM LedgerJournalTrans WHERE Voucher LIKE '%SMOKE%' OR Voucher = ''");
echo "\nSmoke/empty transactions:\n";
while ($r = $rows->fetch_assoc()) {
    printf("TrId:%-3s | Voucher:%-20s | Active:%s\n", $r['RecId'], $r['Voucher'], $r['IsActive']);
}

echo "\nDone.\n";
