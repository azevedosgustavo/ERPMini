<?php
require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

$linkFile = __DIR__ . '/carga_2025_invoice_links.json';
if (!file_exists($linkFile)) {
    throw new RuntimeException('Link file not found: ' . $linkFile);
}

$links = json_decode(file_get_contents($linkFile), true);
if (!is_array($links)) {
    throw new RuntimeException('Invalid link file JSON');
}

echo "=== LINK CARGA 2025 RECEIPT JOURNAL ===\n";

$journal = $db->fetchOne(
    'SELECT RecId, JournalBatchNumber, Description
     FROM LedgerJournalTable
     WHERE IsActive = "1" AND JournalType = "REC" AND Description = ?
     ORDER BY RecId DESC LIMIT 1',
    ['Carga 2025 - Faturamento Recebido']
);

if (!$journal) {
    throw new RuntimeException('Receipt journal not found: Carga 2025 - Faturamento Recebido');
}

echo 'Journal: ' . $journal['JournalBatchNumber'] . ' (RecId ' . $journal['RecId'] . ')' . PHP_EOL;

$updated = 0;
$notFound = 0;

foreach ($links as $voucher => $data) {
    $line = $db->fetchOne(
        'SELECT RecId FROM LedgerJournalTrans
         WHERE JournalRecId = ? AND IsActive = "1" AND Voucher = ?
         LIMIT 1',
        [(int) $journal['RecId'], $voucher]
    );

    if (!$line) {
        $notFound++;
        continue;
    }

    $db->execute(
        'UPDATE LedgerJournalTrans
         SET ServiceInvoiceRecId = ?, CustAccount = ?, ModifiedDateTime = ?
         WHERE RecId = ?',
        [
            (int) $data['ServiceInvoiceRecId'],
            $data['CustAccount'],
            date('Y-m-d H:i:s'),
            (int) $line['RecId']
        ]
    );
    $updated++;
}

echo 'LINKS_TOTAL=' . count($links) . PHP_EOL;
echo 'LINES_UPDATED=' . $updated . PHP_EOL;
echo 'LINES_NOT_FOUND=' . $notFound . PHP_EOL;

$check = $db->fetchOne(
    'SELECT COUNT(*) AS C
     FROM LedgerJournalTrans
     WHERE JournalRecId = ? AND IsActive = "1" AND ServiceInvoiceRecId IS NOT NULL',
    [(int) $journal['RecId']]
);

echo 'LINES_WITH_SERVICEINVOICE=' . (int) $check['C'] . PHP_EOL;
echo "Done.\n";
