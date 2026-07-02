<?php
/**
 * Script para atualizar o menu de relatórios com os novos relatórios
 * - Adiciona Impostos por Período
 * - Adiciona Fluxo de Caixa
 * - Atualiza labels em português
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

// Pegar o ID do grupo REPORTS
$reportsGroup = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'REPORTS'");
if (!$reportsGroup) {
    echo "ERRO: Grupo REPORTS não encontrado\n";
    exit(1);
}
$groupId = $reportsGroup['RecId'];

// Array de relatórios com seus ViewKeys e Labels
$reports = [
    ['code' => 'REP_ACCOUNTS_RECEIVABLE', 'label' => 'report.accountsReceivable.title', 'view' => 'accountsReceivable', 'seq' => 1],
    ['code' => 'REP_ACCOUNTS_PAYABLE', 'label' => 'report.accountsPayable.title', 'view' => 'accountsPayable', 'seq' => 2],
    ['code' => 'REP_BILLING_PERIOD', 'label' => 'report.billingByPeriod.title', 'view' => 'billingByPeriod', 'seq' => 3],
    ['code' => 'REP_BILLING_CUSTOMER', 'label' => 'report.billingByCustomer.title', 'view' => 'billingByCustomer', 'seq' => 4],
    ['code' => 'REP_EXPENSES_PERIOD', 'label' => 'report.expensesByPeriod.title', 'view' => 'expensesByPeriod', 'seq' => 5],
    ['code' => 'REP_TAXES_PERIOD', 'label' => 'report.taxesByPeriod.title', 'view' => 'taxesByPeriod', 'seq' => 6],
    ['code' => 'REP_FINANCIAL_SUMMARY', 'label' => 'report.financialSummary.title', 'view' => 'financialSummary', 'seq' => 7],
    ['code' => 'REP_CASH_FLOW', 'label' => 'report.cashFlow.title', 'view' => 'cashFlow', 'seq' => 8],
    ['code' => 'REP_PROFIT_LOSS', 'label' => 'report.profitAndLoss.title', 'view' => 'profitAndLoss', 'seq' => 9],
];

// Labels em português e inglês
$labels = [
    'report.accountsReceivable.title' => ['PT-BR' => 'Contas a Receber', 'EN-US' => 'Accounts Receivable'],
    'report.accountsPayable.title' => ['PT-BR' => 'Contas a Pagar', 'EN-US' => 'Accounts Payable'],
    'report.billingByPeriod.title' => ['PT-BR' => 'Faturamento por Período', 'EN-US' => 'Billing by Period'],
    'report.billingByCustomer.title' => ['PT-BR' => 'Faturamento por Cliente', 'EN-US' => 'Billing by Customer'],
    'report.expensesByPeriod.title' => ['PT-BR' => 'Despesas por Período', 'EN-US' => 'Expenses by Period'],
    'report.taxesByPeriod.title' => ['PT-BR' => 'Impostos por Período', 'EN-US' => 'Taxes by Period'],
    'report.financialSummary.title' => ['PT-BR' => 'Resumo Financeiro', 'EN-US' => 'Financial Summary'],
    'report.cashFlow.title' => ['PT-BR' => 'Fluxo de Caixa', 'EN-US' => 'Cash Flow'],
    'report.profitAndLoss.title' => ['PT-BR' => 'DRE - Demonstração de Resultado', 'EN-US' => 'Profit and Loss Statement'],
    
    // Labels de filtros
    'filter.customer' => ['PT-BR' => 'Cliente', 'EN-US' => 'Customer'],
    'filter.vendor' => ['PT-BR' => 'Fornecedor', 'EN-US' => 'Vendor'],
    'filter.dueDateFrom' => ['PT-BR' => 'Vencimento De', 'EN-US' => 'Due Date From'],
    'filter.dueDateTo' => ['PT-BR' => 'Vencimento Até', 'EN-US' => 'Due Date To'],
    'filter.fromDate' => ['PT-BR' => 'Data Inicial', 'EN-US' => 'From Date'],
    'filter.toDate' => ['PT-BR' => 'Data Final', 'EN-US' => 'To Date'],
    'filter.status' => ['PT-BR' => 'Status', 'EN-US' => 'Status'],
    'filter.year' => ['PT-BR' => 'Ano', 'EN-US' => 'Year'],
    'filter.periodMonth' => ['PT-BR' => 'Mês', 'EN-US' => 'Month'],
    'filter.purchType' => ['PT-BR' => 'Tipo de Compra', 'EN-US' => 'Purchase Type'],
    'filter.taxType' => ['PT-BR' => 'Tipo de Imposto', 'EN-US' => 'Tax Type'],
    
    // Labels de DRE
    'dre.description' => ['PT-BR' => 'Descrição', 'EN-US' => 'Description'],
    'dre.amount' => ['PT-BR' => 'Valor (R$)', 'EN-US' => 'Amount'],
    'dre.grossRevenue' => ['PT-BR' => 'Receita Bruta', 'EN-US' => 'Gross Revenue'],
    'dre.netIncome' => ['PT-BR' => 'Lucro Líquido', 'EN-US' => 'Net Income'],
    
    // Labels de colunas
    'column.Month' => ['PT-BR' => 'Mês', 'EN-US' => 'Month'],
    'column.TotalBilling' => ['PT-BR' => 'Total Faturado', 'EN-US' => 'Total Billing'],
    'column.TotalExpenses' => ['PT-BR' => 'Total Despesas', 'EN-US' => 'Total Expenses'],
    'column.NetBalance' => ['PT-BR' => 'Saldo Líquido', 'EN-US' => 'Net Balance'],
    'column.LineName' => ['PT-BR' => 'Descrição', 'EN-US' => 'Description'],
    'column.Amount' => ['PT-BR' => 'Valor', 'EN-US' => 'Amount'],
    'column.DocumentId' => ['PT-BR' => 'Documento', 'EN-US' => 'Document'],
    'column.DocumentNumber' => ['PT-BR' => 'Número', 'EN-US' => 'Number'],
    'column.VendorName' => ['PT-BR' => 'Fornecedor', 'EN-US' => 'Vendor'],
    'column.CustomerName' => ['PT-BR' => 'Cliente', 'EN-US' => 'Customer'],
    'column.PurchDate' => ['PT-BR' => 'Data Compra', 'EN-US' => 'Purchase Date'],
    'column.PurchType' => ['PT-BR' => 'Tipo', 'EN-US' => 'Type'],
    'column.InvoiceId' => ['PT-BR' => 'ID Fatura', 'EN-US' => 'Invoice ID'],
    'column.InvoiceNumber' => ['PT-BR' => 'Número NF', 'EN-US' => 'Invoice Number'],
    'column.InvoiceDate' => ['PT-BR' => 'Data Emissão', 'EN-US' => 'Invoice Date'],
    'column.DueDate' => ['PT-BR' => 'Vencimento', 'EN-US' => 'Due Date'],
    'column.TotalAmount' => ['PT-BR' => 'Valor Total', 'EN-US' => 'Total Amount'],
    'column.Status' => ['PT-BR' => 'Status', 'EN-US' => 'Status'],
    'column.TransDate' => ['PT-BR' => 'Data', 'EN-US' => 'Date'],
    'column.Voucher' => ['PT-BR' => 'Voucher', 'EN-US' => 'Voucher'],
    'column.TaxType' => ['PT-BR' => 'Tipo Imposto', 'EN-US' => 'Tax Type'],
    'column.PeriodMonth' => ['PT-BR' => 'Competência', 'EN-US' => 'Period'],
    'column.Receipts' => ['PT-BR' => 'Recebimentos', 'EN-US' => 'Receipts'],
    'column.Payments' => ['PT-BR' => 'Pagamentos', 'EN-US' => 'Payments'],
    'column.NetCashFlow' => ['PT-BR' => 'Fluxo Líquido', 'EN-US' => 'Net Cash Flow'],
    'column.RunningBalance' => ['PT-BR' => 'Saldo Acumulado', 'EN-US' => 'Running Balance'],
    'column.TotalInvoices' => ['PT-BR' => 'Total de Faturas', 'EN-US' => 'Total Invoices'],
    'column.FirstInvoice' => ['PT-BR' => 'Primeira Fatura', 'EN-US' => 'First Invoice'],
    'column.LastInvoice' => ['PT-BR' => 'Última Fatura', 'EN-US' => 'Last Invoice'],
    'column.CustAccount' => ['PT-BR' => 'Conta Cliente', 'EN-US' => 'Customer Account'],
    
    // Options
    'option.select' => ['PT-BR' => '-- Selecione --', 'EN-US' => '-- Select --'],
];

$now = date('Y-m-d H:i:s');

echo "=== Atualizando Menu de Relatórios ===\n";

// Inserir/atualizar itens de menu
foreach ($reports as $report) {
    $existing = $db->fetchOne("SELECT RecId FROM SysMenuItem WHERE MenuCode = ?", [$report['code']]);
    
    if ($existing) {
        $db->execute(
            "UPDATE SysMenuItem SET ViewKey = ?, LabelKey = ?, SequenceNo = ?, IsActive = '1', ModifiedDateTime = ? WHERE MenuCode = ?",
            [$report['view'], $report['label'], $report['seq'], $now, $report['code']]
        );
        echo "ATUALIZADO: {$report['code']} -> {$report['view']}\n";
    } else {
        $db->execute(
            "INSERT INTO SysMenuItem (GroupId, ParentMenuId, MenuCode, LabelKey, ViewKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
             VALUES (?, 0, ?, ?, ?, ?, '1', ?, ?, 'SYSTEM')",
            [$groupId, $report['code'], $report['label'], $report['view'], $report['seq'], $now, $now]
        );
        echo "INSERIDO: {$report['code']} -> {$report['view']}\n";
    }
}

echo "\n=== Atualizando Labels ===\n";

// Inserir/atualizar labels
foreach ($labels as $labelKey => $translations) {
    foreach ($translations as $lang => $text) {
        $existing = $db->fetchOne(
            "SELECT RecId FROM SysLabelText WHERE LabelKey = ? AND LanguageId = ?",
            [$labelKey, $lang]
        );
        
        if ($existing) {
            $db->execute(
                "UPDATE SysLabelText SET TextValue = ?, ModifiedDateTime = ? WHERE LabelKey = ? AND LanguageId = ?",
                [$text, $now, $labelKey, $lang]
            );
        } else {
            $db->execute(
                "INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                 VALUES (?, ?, ?, '1', ?, ?, 'SYSTEM')",
                [$labelKey, $lang, $text, $now, $now]
            );
        }
    }
    echo "LABEL: $labelKey\n";
}

echo "\n=== Concluído ===\n";
echo "Menu de relatórios atualizado com sucesso!\n";
