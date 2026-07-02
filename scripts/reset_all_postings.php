<?php
require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$now = date('Y-m-d H:i:s');

echo "=== RESET ALL POSTINGS (soft delete) ===\n";

$steps = [
    ['sql' => 'UPDATE DocuAttachment SET IsActive = "0", ModifiedDateTime = ? WHERE EntityName IN ("service-invoices", "purchase-orders-materials", "purchase-orders-services", "journals", "payment-journals", "receipt-journals", "tax-journals") AND IsActive = "1"', 'params' => [$now], 'label' => 'Attachments (transaction entities)'],
    ['sql' => 'UPDATE LedgerJournalTrans SET IsActive = "0", ModifiedDateTime = ? WHERE IsActive = "1"', 'params' => [$now], 'label' => 'LedgerJournalTrans'],
    ['sql' => 'UPDATE LedgerJournalTable SET IsActive = "0", Posted = "0", ModifiedDateTime = ? WHERE IsActive = "1"', 'params' => [$now], 'label' => 'LedgerJournalTable'],
    ['sql' => 'UPDATE CustInvoiceJour SET IsActive = "0", Status = "C", ModifiedDateTime = ? WHERE IsActive = "1"', 'params' => [$now], 'label' => 'CustInvoiceJour'],
    ['sql' => 'UPDATE PurchTable SET IsActive = "0", Status = "C", ModifiedDateTime = ? WHERE IsActive = "1"', 'params' => [$now], 'label' => 'PurchTable'],
];

foreach ($steps as $step) {
    $db->execute($step['sql'], $step['params']);
    $count = $db->fetchOne('SELECT ROW_COUNT() AS C');
    echo $step['label'] . ': ' . (int)$count['C'] . " row(s) updated\n";
}

// Keep cadastro/master data intact; only operational postings are deactivated.
$totals = [
    'Active invoices' => $db->fetchOne('SELECT COUNT(*) AS C FROM CustInvoiceJour WHERE IsActive = "1"')['C'],
    'Active purchases' => $db->fetchOne('SELECT COUNT(*) AS C FROM PurchTable WHERE IsActive = "1"')['C'],
    'Active journals' => $db->fetchOne('SELECT COUNT(*) AS C FROM LedgerJournalTable WHERE IsActive = "1"')['C'],
    'Active trans' => $db->fetchOne('SELECT COUNT(*) AS C FROM LedgerJournalTrans WHERE IsActive = "1"')['C'],
    'Active customers' => $db->fetchOne('SELECT COUNT(*) AS C FROM CustTable WHERE IsActive = "1"')['C'],
    'Active vendors' => $db->fetchOne('SELECT COUNT(*) AS C FROM VendTable WHERE IsActive = "1"')['C'],
    'Active products' => $db->fetchOne('SELECT COUNT(*) AS C FROM InventTable WHERE IsActive = "1"')['C'],
    'Active service codes' => $db->fetchOne('SELECT COUNT(*) AS C FROM ServiceCodeTable WHERE IsActive = "1"')['C'],
];

echo "\n=== POST-RESET CHECK ===\n";
foreach ($totals as $label => $value) {
    echo $label . ': ' . (int)$value . "\n";
}

echo "Done.\n";
