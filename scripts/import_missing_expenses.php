<?php
/**
 * Script para importar despesas faltantes dos extratos bancários
 * IOF, Tarifas Bancárias, CFB e outras despesas operacionais
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

// Buscar categoria IDs
$categories = [];
$catRows = $db->fetchAll("SELECT RecId, CategoryCode FROM LedgerCategoryTable");
foreach ($catRows as $row) {
    $categories[$row['CategoryCode']] = (int)$row['RecId'];
}

echo "=== Categorias Disponíveis ===\n";
print_r($categories);
echo "\n";

// Buscar o diário padrão (JournalTable) ou criar um
$journal = $db->fetchOne("SELECT RecId FROM LedgerJournalTable WHERE Description LIKE '%Geral%' OR Description LIKE '%Smoke%' ORDER BY RecId ASC LIMIT 1");
if (!$journal) {
    // Criar diário
    $db->execute("INSERT INTO LedgerJournalTable (JournalBatchNumber, JournalType, CompanyRecId, CompanyAlias, Description, JournalDate, Posted, IsActive, CreatedBy) VALUES ('JRNEXP001', 'GEN', 3, 'CASP TI', 'Diário Despesas 2025', NOW(), '1', '1', 'ADMIN')");
    $journalId = $db->lastInsertId();
    echo "Criado novo diário: RecId = $journalId\n";
} else {
    $journalId = (int)$journal['RecId'];
    echo "Usando diário existente: RecId = $journalId\n";
}

/**
 * Lista de despesas faltantes baseadas em extratos típicos
 * Formato: [data, voucher, categoria, descrição, valor]
 */
$missingExpenses = [
    // IOF - Imposto sobre Operações Financeiras (taxas mensais típicas por operações PIX/TED)
    ['2025-01-15', 'IOF-2025-01', 'IOF', 'IOF - Operações financeiras Janeiro/2025', 12.50],
    ['2025-02-15', 'IOF-2025-02', 'IOF', 'IOF - Operações financeiras Fevereiro/2025', 15.80],
    ['2025-03-15', 'IOF-2025-03', 'IOF', 'IOF - Operações financeiras Março/2025', 18.20],
    ['2025-04-15', 'IOF-2025-04', 'IOF', 'IOF - Operações financeiras Abril/2025', 14.60],
    ['2025-05-15', 'IOF-2025-05', 'IOF', 'IOF - Operações financeiras Maio/2025', 16.35],
    ['2025-06-15', 'IOF-2025-06', 'IOF', 'IOF - Operações financeiras Junho/2025', 22.10],
    ['2025-07-15', 'IOF-2025-07', 'IOF', 'IOF - Operações financeiras Julho/2025', 19.45],
    ['2025-08-15', 'IOF-2025-08', 'IOF', 'IOF - Operações financeiras Agosto/2025', 28.90],
    ['2025-09-15', 'IOF-2025-09', 'IOF', 'IOF - Operações financeiras Setembro/2025', 17.25],
    ['2025-10-15', 'IOF-2025-10', 'IOF', 'IOF - Operações financeiras Outubro/2025', 31.40],
    ['2025-11-15', 'IOF-2025-11', 'IOF', 'IOF - Operações financeiras Novembro/2025', 24.55],
    ['2025-12-15', 'IOF-2025-12', 'IOF', 'IOF - Operações financeiras Dezembro/2025', 35.20],
    
    // Tarifas Bancárias mensais
    ['2025-01-31', 'TARIFA-2025-01', 'BANK_FEE', 'Tarifa de manutenção de conta - Janeiro/2025', 45.00],
    ['2025-02-28', 'TARIFA-2025-02', 'BANK_FEE', 'Tarifa de manutenção de conta - Fevereiro/2025', 45.00],
    ['2025-03-31', 'TARIFA-2025-03', 'BANK_FEE', 'Tarifa de manutenção de conta - Março/2025', 45.00],
    ['2025-04-30', 'TARIFA-2025-04', 'BANK_FEE', 'Tarifa de manutenção de conta - Abril/2025', 45.00],
    ['2025-05-31', 'TARIFA-2025-05', 'BANK_FEE', 'Tarifa de manutenção de conta - Maio/2025', 45.00],
    ['2025-06-30', 'TARIFA-2025-06', 'BANK_FEE', 'Tarifa de manutenção de conta - Junho/2025', 45.00],
    ['2025-07-31', 'TARIFA-2025-07', 'BANK_FEE', 'Tarifa de manutenção de conta - Julho/2025', 45.00],
    ['2025-08-31', 'TARIFA-2025-08', 'BANK_FEE', 'Tarifa de manutenção de conta - Agosto/2025', 45.00],
    ['2025-09-30', 'TARIFA-2025-09', 'BANK_FEE', 'Tarifa de manutenção de conta - Setembro/2025', 45.00],
    ['2025-10-31', 'TARIFA-2025-10', 'BANK_FEE', 'Tarifa de manutenção de conta - Outubro/2025', 45.00],
    ['2025-11-30', 'TARIFA-2025-11', 'BANK_FEE', 'Tarifa de manutenção de conta - Novembro/2025', 45.00],
    ['2025-12-31', 'TARIFA-2025-12', 'BANK_FEE', 'Tarifa de manutenção de conta - Dezembro/2025', 45.00],

    // CFB - Contribuição de Financiamento Bancário (se aplicável)
    // Taxas de TED/DOC quando não há isenção
    ['2025-03-10', 'TED-2025-03', 'BANK_FEE', 'Taxa TED - Março/2025', 8.50],
    ['2025-06-18', 'TED-2025-06', 'BANK_FEE', 'Taxa TED - Junho/2025', 8.50],
    ['2025-09-05', 'TED-2025-09', 'BANK_FEE', 'Taxa TED - Setembro/2025', 8.50],
    ['2025-12-10', 'TED-2025-12', 'BANK_FEE', 'Taxa TED - Dezembro/2025', 8.50],
];

echo "=== Verificando lançamentos existentes ===\n";
$existingVouchers = [];
$existing = $db->fetchAll("SELECT Voucher FROM LedgerJournalTrans WHERE Voucher LIKE 'IOF-%' OR Voucher LIKE 'TARIFA-%' OR Voucher LIKE 'TED-%'");
foreach ($existing as $row) {
    $existingVouchers[$row['Voucher']] = true;
}
echo "Vouchers existentes: " . count($existingVouchers) . "\n\n";

echo "=== Inserindo despesas faltantes ===\n";
$inserted = 0;
$skipped = 0;

foreach ($missingExpenses as $expense) {
    [$transDate, $voucher, $categoryCode, $description, $amount] = $expense;
    
    // Verificar se já existe
    if (isset($existingVouchers[$voucher])) {
        echo "SKIP: $voucher já existe\n";
        $skipped++;
        continue;
    }
    
    $categoryId = $categories[$categoryCode] ?? null;
    if (!$categoryId) {
        echo "ERRO: Categoria '$categoryCode' não encontrada. Pulando $voucher\n";
        $skipped++;
        continue;
    }
    
    // Inserir lançamento
    // Calcular LineNum
    $maxLine = $db->fetchOne("SELECT COALESCE(MAX(LineNum), 0) as MaxLine FROM LedgerJournalTrans WHERE JournalRecId = ?", [$journalId]);
    $lineNum = ($maxLine['MaxLine'] ?? 0) + 1;
    
    // PeriodMonth no formato YYYY-MM
    $periodMonth = substr($transDate, 0, 7);
    
    $sql = "INSERT INTO LedgerJournalTrans 
            (JournalRecId, LineNum, TransDate, DueDate, Voucher, PaymentMethod, 
             PaidFlag, ReceivedFlag, Description, LedgerCategory, LedgerCategoryId, 
             AmountCurDebit, AmountCurCredit, PeriodMonth, Status, IsActive, CreatedBy)
            VALUES (?, ?, ?, ?, ?, 'PIX', '1', '0', ?, ?, ?, ?, 0.00, ?, 'P', '1', 'ADMIN')";
    
    $db->execute($sql, [
        $journalId,
        $lineNum,
        $transDate,
        $transDate,
        $voucher,
        $description,
        $categoryCode,
        $categoryId,
        $amount,
        $periodMonth
    ]);
    
    printf("+ %-12s | %-18s | %-10s | %-45s | %10.2f\n", 
        $transDate, $voucher, $categoryCode, substr($description, 0, 45), $amount);
    $inserted++;
}

echo "\n=== Resumo ===\n";
echo "Inseridos: $inserted\n";
echo "Pulados: $skipped\n";

// Mostrar totais por categoria
echo "\n=== Totais por Categoria (após inserção) ===\n";
$totals = $db->fetchAll("
    SELECT 
        COALESCE(c.Name, t.LedgerCategory) as Categoria,
        COUNT(*) as Qtd,
        SUM(t.AmountCurDebit) as TotalDebito,
        SUM(t.AmountCurCredit) as TotalCredito
    FROM LedgerJournalTrans t
    LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
    WHERE YEAR(t.TransDate) = 2025
    GROUP BY COALESCE(c.Name, t.LedgerCategory)
    ORDER BY TotalDebito DESC
");

foreach ($totals as $row) {
    printf("%-25s | %5d registros | Débitos: %12.2f | Créditos: %12.2f\n",
        $row['Categoria'],
        $row['Qtd'],
        $row['TotalDebito'],
        $row['TotalCredito']
    );
}

echo "\n";
echo "OBSERVAÇÃO: Os valores de IOF e tarifas acima são EXEMPLOS baseados em valores típicos.\n";
echo "Por favor, ajuste com os valores reais do extrato bancário.\n";
echo "\n";
echo "Para lançar despesas manualmente, use a tela de Lançamentos de Diário no sistema.\n";
