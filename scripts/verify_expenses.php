<?php
/**
 * Script para verificar e adicionar despesas faltantes no sistema
 * - Tarifas bancárias (CFB, IOF, etc.)
 * - Pagamentos diversos
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

echo "=== TIPOS DE IMPOSTOS ===\n\n";
$taxes = $db->fetchAll("SELECT RecId, TaxTypeCode, Name FROM TaxTypeTable ORDER BY RecId");
foreach ($taxes as $row) {
    printf("%3d | %-20s | %s\n", $row['RecId'], $row['TaxTypeCode'], $row['Name']);
}

echo "\n\n=== CATEGORIAS LEDGER USADAS ===\n";
$categories = $db->fetchAll("SELECT DISTINCT LedgerCategory FROM LedgerJournalTrans");
foreach ($categories as $row) {
    echo "- " . $row['LedgerCategory'] . "\n";
}

echo "\n\n=== CONTAS BANCÁRIAS ===\n";
$banks = $db->fetchAll("SELECT RecId, BankName, AccountNumber FROM BankAccountTable");
foreach ($banks as $row) {
    printf("%3d | %-30s | %s\n", $row['RecId'], $row['BankName'], $row['AccountNumber']);
}

// Listar todos os registros do journal com mais detalhes para identificar o que está faltando
echo "\n\n=== JOURNAL DETALHADO 2025 (DÉBITOS - SAÍDAS) ===\n";
$journals = $db->fetchAll("
    SELECT j.TransDate, j.Voucher, j.LedgerCategory, j.Description, 
           j.AmountCurDebit, t.Name as TaxName
    FROM LedgerJournalTrans j
    LEFT JOIN TaxTypeTable t ON j.TaxTypeId = t.RecId
    WHERE YEAR(j.TransDate) = 2025 AND j.AmountCurDebit > 0
    ORDER BY j.TransDate
");

$byCategory = [];
foreach ($journals as $row) {
    $cat = $row['LedgerCategory'];
    if (!isset($byCategory[$cat])) {
        $byCategory[$cat] = ['items' => [], 'total' => 0];
    }
    $byCategory[$cat]['items'][] = $row;
    $byCategory[$cat]['total'] += $row['AmountCurDebit'];
}

foreach ($byCategory as $cat => $data) {
    echo "\n>>> $cat (Total: " . number_format($data['total'], 2, ',', '.') . ") <<<\n";
    foreach ($data['items'] as $row) {
        printf("  %-12s | %-25s | %12.2f | %s\n",
            substr($row['TransDate'], 0, 10),
            substr($row['Voucher'], 0, 25),
            $row['AmountCurDebit'],
            $row['Description']
        );
    }
}

echo "\n\n=== RESUMO DETALHADO ===\n";
echo "Categorias encontradas:\n";
foreach ($byCategory as $cat => $data) {
    echo "  - $cat: " . number_format($data['total'], 2, ',', '.') . " (" . count($data['items']) . " registros)\n";
}

// Get all journals for year 2025 to see total
$totals = $db->fetchOne("
    SELECT 
        SUM(AmountCurDebit) as total_debits,
        SUM(AmountCurCredit) as total_credits
    FROM LedgerJournalTrans 
    WHERE YEAR(TransDate) = 2025
");

echo "\n\nTotal Débitos (Saídas): " . number_format($totals['total_debits'], 2, ',', '.') . "\n";
echo "Total Créditos (Entradas): " . number_format($totals['total_credits'], 2, ',', '.') . "\n";

// Contar faturas de cliente
$invTotal = $db->fetchOne("SELECT SUM(TotalAmount) as total FROM CustInvoiceJour WHERE YEAR(InvoiceDate) = 2025");
echo "Total Faturas Cliente: " . number_format($invTotal['total'], 2, ',', '.') . "\n";

// Contar compras
$purchTotal = $db->fetchOne("SELECT SUM(TotalAmount) as total FROM PurchTable WHERE YEAR(PurchDate) = 2025");
echo "Total Compras: " . number_format($purchTotal['total'], 2, ',', '.') . "\n";

echo "\n\n=== O QUE ESTÁ FALTANDO ===\n";
echo "Com base na análise, estão faltando as seguintes despesas típicas de extrato bancário:\n";
echo "- CFB (Tarifa de Cheque Especial)\n";
echo "- IOF (Imposto sobre Operações Financeiras)\n";
echo "- Tarifas bancárias gerais\n";
echo "- Pagamentos diversos não identificados\n";
echo "\nPara adicionar estes lançamentos ausentes, precisamos:\n";
echo "1. Obter o extrato bancário real com os valores\n";
echo "2. Criar entradas no LedgerJournalTrans com LedgerCategory apropriada\n";
echo "3. Categorias sugeridas: BANK_FEE, IOF, MISC_PAYMENT\n";

echo "\n";
