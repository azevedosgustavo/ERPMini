<?php
require_once __DIR__ . '/../app/bootstrap.php';
$db = new Database();

echo "=== JOURNALS (todos) ===\n";
$journals = $db->fetchAll("SELECT RecId, JournalBatchNumber, JournalType, Description, JournalDate, Posted FROM LedgerJournalTable ORDER BY RecId");
foreach ($journals as $j) {
    printf("RecId:%-4d | %-12s | %3s | %-40s | %s | Posted:%s\n",
        $j['RecId'], $j['JournalBatchNumber'], $j['JournalType'],
        substr($j['Description'], 0, 40), substr($j['JournalDate'], 0, 10), $j['Posted']);
}

echo "\n=== TRANS por journal ===\n";
$trans = $db->fetchAll("
    SELECT t.RecId, t.JournalRecId, j.JournalBatchNumber, j.Description as JDesc, 
           t.Voucher, t.TransDate, t.Description, t.AmountCurDebit, t.AmountCurCredit, t.LedgerCategory, t.IsActive
    FROM LedgerJournalTrans t
    JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
    ORDER BY t.JournalRecId, t.LineNum");
foreach ($trans as $t) {
    printf("TrId:%-4d | Jrn:%-4d %-12s | %-12s | %s | %-35s | D:%8.2f C:%8.2f | Cat:%-15s | Active:%s\n",
        $t['RecId'], $t['JournalRecId'], $t['JournalBatchNumber'],
        $t['Voucher'], substr($t['TransDate'],0,10), substr($t['Description'],0,35),
        $t['AmountCurDebit'], $t['AmountCurCredit'], $t['LedgerCategory'], $t['IsActive']);
}

echo "\n=== PURCH TABLE 2025 ===\n";
$purches = $db->fetchAll("SELECT RecId, PurchId, PurchNumber, VendAccount, PurchDate, TotalAmount, Status FROM PurchTable WHERE YEAR(PurchDate)=2025 ORDER BY PurchDate");
foreach ($purches as $p) {
    printf("RecId:%-4d | %-15s | %-20s | %-12s | %s | %9.2f | %s\n",
        $p['RecId'], $p['PurchId'], substr($p['PurchNumber'],0,20), $p['VendAccount'], substr($p['PurchDate'],0,10), $p['TotalAmount'], $p['Status']);
}

echo "\n=== TOTAL DEBIT/CREDIT POR JOURNAL ===\n";
$totals = $db->fetchAll("
    SELECT j.RecId, j.JournalBatchNumber, j.JournalType, j.Description,
           SUM(t.AmountCurDebit) as TotalDebit, SUM(t.AmountCurCredit) as TotalCredit, COUNT(*) as Lines
    FROM LedgerJournalTable j
    LEFT JOIN LedgerJournalTrans t ON t.JournalRecId = j.RecId AND t.IsActive='1'
    GROUP BY j.RecId ORDER BY j.RecId");
foreach ($totals as $r) {
    printf("RecId:%-3d %-12s %3s | %-35s | Deb:%10.2f Cred:%10.2f Lines:%d\n",
        $r['RecId'], $r['JournalBatchNumber'], $r['JournalType'],
        substr($r['Description'],0,35), $r['TotalDebit'], $r['TotalCredit'], $r['Lines']);
}
