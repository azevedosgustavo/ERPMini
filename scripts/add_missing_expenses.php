<?php
/**
 * Script para adicionar despesas faltantes do extrato bancário
 * 
 * Categorias de lançamento:
 * - TAX: Impostos (INSS, Simples Nacional, DARF)
 * - BANK_FEE: Tarifas bancárias (CFB, TAR, etc.)
 * - IOF: Imposto sobre Operações Financeiras
 * - OPERATING: Despesas operacionais (Pro-labore, Lucros, etc.)
 * - MISC_EXPENSE: Despesas diversas não identificadas
 * 
 * REGRA: Tributos Federais DARF Numerado = INSS (TaxTypeId = 1)
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

/**
 * DESPESAS A SEREM ADICIONADAS
 * Preencha os arrays abaixo com as despesas faltantes do extrato
 * 
 * Formato:
 * [
 *     'date' => 'YYYY-MM-DD',
 *     'voucher' => 'Identificador do extrato',
 *     'description' => 'Descrição do extrato',
 *     'amount' => valor em decimal,
 *     'category' => 'BANK_FEE' | 'IOF' | 'TAX' | 'MISC_EXPENSE',
 *     'taxTypeId' => null ou 1 (INSS) ou 2 (Simples)
 * ]
 */

$despesasFaltantes = [
    // === EXEMPLO DE TARIFAS BANCÁRIAS ===
    // [
    //     'date' => '2025-01-15',
    //     'voucher' => 'CFB-0115',
    //     'description' => 'Tarifa Pacote de Serviços',
    //     'amount' => 29.90,
    //     'category' => 'BANK_FEE',
    //     'taxTypeId' => null
    // ],
    
    // === EXEMPLO DE IOF ===
    // [
    //     'date' => '2025-01-20',
    //     'voucher' => 'IOF-0120',
    //     'description' => 'IOF sobre operação de crédito',
    //     'amount' => 15.50,
    //     'category' => 'IOF',
    //     'taxTypeId' => null
    // ],
    
    // === EXEMPLO DE DARF NUMERADO (INSS) ===
    // [
    //     'date' => '2025-01-25',
    //     'voucher' => 'DARF-12345',  
    //     'description' => 'Tributos Federais DARF Numerado - INSS',
    //     'amount' => 303.60,
    //     'category' => 'TAX',
    //     'taxTypeId' => 1  // 1 = INSS
    // ],
    
    // === EXEMPLO DE DESPESA DESCONHECIDA ===
    // [
    //     'date' => '2025-01-30',
    //     'voucher' => 'PAG-0130',
    //     'description' => 'Pagamento não identificado - ref. extrato linha 45',
    //     'amount' => 150.00,
    //     'category' => 'MISC_EXPENSE',
    //     'taxTypeId' => null
    // ],
];

// ============================================================================
// ADICIONE SUAS DESPESAS ABAIXO
// ============================================================================

// Tarifas bancárias C6 Bank (exemplo - ajuste conforme seu extrato)
// $despesasFaltantes[] = [
//     'date' => '2025-MM-DD',
//     'voucher' => 'CFB-MMDD',
//     'description' => 'Tarifa Mensal - C6 Business',
//     'amount' => 0.00,
//     'category' => 'BANK_FEE',
//     'taxTypeId' => null
// ];

// ============================================================================
// PROCESSAMENTO - NÃO MODIFICAR
// ============================================================================

if (empty($despesasFaltantes)) {
    echo "=============================================================\n";
    echo "NENHUMA DESPESA PARA ADICIONAR\n";
    echo "=============================================================\n\n";
    echo "Para adicionar despesas faltantes do extrato:\n\n";
    echo "1. Edite este arquivo: scripts/add_missing_expenses.php\n";
    echo "2. Preencha o array \$despesasFaltantes com os dados do extrato\n";
    echo "3. Execute novamente este script\n\n";
    echo "Categorias disponíveis:\n";
    echo "  - BANK_FEE: Tarifas bancárias (CFB, pacotes, etc.)\n";
    echo "  - IOF: Imposto sobre Operações Financeiras\n";
    echo "  - TAX: Impostos (DARF = use taxTypeId=1 para INSS)\n";
    echo "  - MISC_EXPENSE: Despesas diversas/desconhecidas\n\n";
    echo "TaxTypeIds:\n";
    echo "  - 1 = INSS (usar para DARF numerado)\n";
    echo "  - 2 = Simples Nacional\n";
    exit(0);
}

// Obter próximo JournalRecId
$lastJournal = $db->fetchOne("SELECT MAX(RecId) as maxId FROM LedgerJournalTable");
$journalRecId = ($lastJournal['maxId'] ?? 0) + 1;

// Criar cabeçalho do diário
$now = date('Y-m-d H:i:s');
$db->execute("
    INSERT INTO LedgerJournalTable (RecId, JournalCode, Description, JournalDate, Status, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
    VALUES (?, ?, ?, ?, 'P', '1', ?, ?, 'IMPORT')
", [$journalRecId, 'IMPORT-' . date('Ymd-His'), 'Importação de despesas do extrato bancário', $now, $now, $now]);

echo "=============================================================\n";
echo "ADICIONANDO DESPESAS FALTANTES\n";
echo "=============================================================\n\n";
echo "JournalRecId: $journalRecId\n\n";

$totalAdicionado = 0;
$lineNum = 0;

foreach ($despesasFaltantes as $despesa) {
    $lineNum++;
    
    $periodMonth = substr($despesa['date'], 0, 7);
    
    $db->execute("
        INSERT INTO LedgerJournalTrans (
            JournalRecId, LineNum, TransDate, DueDate, Voucher, 
            TaxTypeId, BankAccountId, VendAccount, CustAccount, 
            ServiceInvoiceRecId, PurchRecId, PaymentMethod, PaymentDate, 
            PaidFlag, ReceivedFlag, Description, LedgerCategory, 
            AmountCurDebit, AmountCurCredit, PeriodMonth, Status, 
            IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, 1, NULL, NULL,
            NULL, NULL, 'BANK', ?,
            '1', '0', ?, ?,
            ?, 0.00, ?, 'P',
            '1', ?, ?, 'IMPORT'
        )
    ", [
        $journalRecId,
        $lineNum,
        $despesa['date'],
        $despesa['date'],
        $despesa['voucher'],
        $despesa['taxTypeId'],
        $despesa['date'],
        $despesa['description'],
        $despesa['category'],
        $despesa['amount'],
        $periodMonth,
        $now,
        $now
    ]);
    
    $totalAdicionado += $despesa['amount'];
    
    printf("+ %-12s | %-15s | %12.2f | %s\n",
        $despesa['date'],
        $despesa['category'],
        $despesa['amount'],
        $despesa['description']
    );
}

echo "\n=============================================================\n";
echo "RESUMO\n";
echo "=============================================================\n";
echo "Total de lançamentos: $lineNum\n";
echo "Valor total adicionado: R$ " . number_format($totalAdicionado, 2, ',', '.') . "\n";

// Mostrar novo resumo
echo "\n=== NOVO RESUMO DE DESPESAS 2025 ===\n";
$totals = $db->fetchOne("
    SELECT 
        SUM(AmountCurDebit) as total_debits,
        SUM(AmountCurCredit) as total_credits
    FROM LedgerJournalTrans 
    WHERE YEAR(TransDate) = 2025
");

$byCategory = $db->fetchAll("
    SELECT LedgerCategory, SUM(AmountCurDebit) as total
    FROM LedgerJournalTrans 
    WHERE YEAR(TransDate) = 2025 AND AmountCurDebit > 0
    GROUP BY LedgerCategory
");

echo "Total Débitos (Saídas): R$ " . number_format($totals['total_debits'], 2, ',', '.') . "\n";
echo "Por categoria:\n";
foreach ($byCategory as $cat) {
    echo "  - {$cat['LedgerCategory']}: R$ " . number_format($cat['total'], 2, ',', '.') . "\n";
}
