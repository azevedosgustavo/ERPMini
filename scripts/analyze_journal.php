<?php
/**
 * Analyze journal transactions to find missing entries
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

echo "=== JOURNAL TRANS 2025 ===\n\n";

$journals = $db->fetchAll("SELECT TransDate, Voucher, LedgerCategory, Description, 
               AmountCurDebit, AmountCurCredit, TaxTypeId, VendAccount, CustAccount, PurchRecId
        FROM LedgerJournalTrans 
        WHERE YEAR(TransDate) = 2025 
        ORDER BY TransDate");

$totalDebit = 0;
$totalCredit = 0;

foreach ($journals as $row) {
    printf("%-12s | %-20s | %-15s | %-40s | %12.2f | %12.2f | Tax:%s Vend:%s Cust:%s Purch:%s\n",
        $row['TransDate'],
        substr($row['Voucher'], 0, 20),
        substr($row['LedgerCategory'] ?? '', 0, 15),
        substr($row['Description'], 0, 40),
        $row['AmountCurDebit'],
        $row['AmountCurCredit'],
        $row['TaxTypeId'] ?? '-',
        $row['VendAccount'] ?? '-',
        $row['CustAccount'] ?? '-',
        $row['PurchRecId'] ?? '-'
    );
    $totalDebit += $row['AmountCurDebit'];
    $totalCredit += $row['AmountCurCredit'];
}

echo "\n";
echo "Total Débito: " . number_format($totalDebit, 2, ',', '.') . "\n";
echo "Total Crédito: " . number_format($totalCredit, 2, ',', '.') . "\n";

echo "\n\n=== PURCH TABLE 2025 ===\n\n";

$purchases = $db->fetchAll("SELECT PurchDate, PurchId, VendAccount, PurchType, TotalAmount, Status 
        FROM PurchTable 
        WHERE YEAR(PurchDate) = 2025 
        ORDER BY PurchDate");

$totalPurch = 0;
foreach ($purchases as $row) {
    printf("%-12s | %-15s | %-15s | %-20s | %12.2f | %s\n",
        $row['PurchDate'],
        $row['PurchId'],
        $row['VendAccount'],
        $row['PurchType'],
        $row['TotalAmount'],
        $row['Status']
    );
    $totalPurch += $row['TotalAmount'];
}

echo "\n";
echo "Total Compras: " . number_format($totalPurch, 2, ',', '.') . "\n";

echo "\n\n=== CUST INVOICE JOUR 2025 ===\n\n";

$invoices = $db->fetchAll("SELECT InvoiceDate, InvoiceId, CustAccount, TotalAmount, TaxAmount, DeductionAmount, Status 
        FROM CustInvoiceJour 
        WHERE YEAR(InvoiceDate) = 2025 
        ORDER BY InvoiceDate");

$totalInv = 0;
$totalTax = 0;
$totalDeduct = 0;
foreach ($invoices as $row) {
    printf("%-12s | %-15s | %-15s | %12.2f | Tax: %8.2f | Ded: %8.2f | %s\n",
        $row['InvoiceDate'],
        $row['InvoiceId'],
        $row['CustAccount'],
        $row['TotalAmount'],
        $row['TaxAmount'],
        $row['DeductionAmount'],
        $row['Status']
    );
    $totalInv += $row['TotalAmount'];
    $totalTax += $row['TaxAmount'];
    $totalDeduct += $row['DeductionAmount'];
}

echo "\n";
echo "Total Faturas (Bruto): " . number_format($totalInv, 2, ',', '.') . "\n";
echo "Total Impostos: " . number_format($totalTax, 2, ',', '.') . "\n";
echo "Total Deduções: " . number_format($totalDeduct, 2, ',', '.') . "\n";
echo "Total Líquido: " . number_format($totalInv - $totalTax - $totalDeduct, 2, ',', '.') . "\n";

echo "\n\n=== RESUMO ===\n";
echo "Receitas (Faturas Cliente Bruto): " . number_format($totalInv, 2, ',', '.') . "\n";
echo "Receitas (Líquido após impostos): " . number_format($totalInv - $totalTax - $totalDeduct, 2, ',', '.') . "\n";
echo "Compras (PurchTable): " . number_format($totalPurch, 2, ',', '.') . "\n";
echo "Journal Débitos (Saídas): " . number_format($totalDebit, 2, ',', '.') . "\n";
echo "Journal Créditos (Entradas): " . number_format($totalCredit, 2, ',', '.') . "\n";
echo "\n";
echo "Saldo = Receitas - Compras - Journal Débitos = " . number_format($totalInv - $totalPurch - $totalDebit, 2, ',', '.') . "\n";

// Check what LedgerCategories exist
echo "\n\n=== LEDGER CATEGORIES ===\n";
$types = $db->fetchAll("SELECT LedgerCategory, COUNT(*) as cnt, SUM(AmountCurDebit) as debits, SUM(AmountCurCredit) as credits 
        FROM LedgerJournalTrans 
        WHERE YEAR(TransDate) = 2025 
        GROUP BY LedgerCategory");
        
foreach ($types as $row) {
    printf("%-25s | %5d registros | Débitos: %12.2f | Créditos: %12.2f\n",
        $row['LedgerCategory'] ?: '(vazio)',
        $row['cnt'],
        $row['debits'],
        $row['credits']
    );
}

// Check Tax Types used
echo "\n\n=== TAX TYPES USADOS ===\n";
$taxes = $db->fetchAll("SELECT t.TaxTypeName, COUNT(*) as cnt, SUM(j.AmountCurDebit) as debits 
        FROM LedgerJournalTrans j
        LEFT JOIN TaxTypeTable t ON j.TaxTypeId = t.RecId
        WHERE YEAR(j.TransDate) = 2025 AND j.TaxTypeId IS NOT NULL
        GROUP BY j.TaxTypeId, t.TaxTypeName");
        
foreach ($taxes as $row) {
    printf("%-30s | %5d registros | Total: %12.2f\n",
        $row['TaxTypeName'] ?? '(desconhecido)',
        $row['cnt'],
        $row['debits']
    );
}
