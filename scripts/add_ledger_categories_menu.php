<?php
/**
 * Script para adicionar menu de Categorias de Lançamento
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

$now = date('Y-m-d H:i:s');

// Buscar grupo GENERAL
$generalGroup = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'GENERAL'");
if (!$generalGroup) {
    echo "ERRO: Grupo GENERAL não encontrado\n";
    exit(1);
}
$groupId = $generalGroup['RecId'];

echo "=== Adicionando Menu de Categorias de Lançamento ===\n";

// Verificar se já existe
$existing = $db->fetchOne("SELECT RecId FROM SysMenuItem WHERE MenuCode = 'GEN_LEDGER_CATEGORIES'");

if ($existing) {
    $db->execute(
        "UPDATE SysMenuItem SET ViewKey = 'ledger-categories', LabelKey = 'menu.general.ledgercategories', 
         SequenceNo = 23, IsActive = '1', ModifiedDateTime = ? WHERE MenuCode = 'GEN_LEDGER_CATEGORIES'",
        [$now]
    );
    echo "ATUALIZADO: GEN_LEDGER_CATEGORIES -> ledger-categories\n";
} else {
    $db->execute(
        "INSERT INTO SysMenuItem (GroupId, ParentMenuId, MenuCode, LabelKey, ViewKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
         VALUES (?, 0, 'GEN_LEDGER_CATEGORIES', 'menu.general.ledgercategories', 'ledger-categories', 23, '1', ?, ?, 'SYSTEM')",
        [$groupId, $now, $now]
    );
    echo "INSERIDO: GEN_LEDGER_CATEGORIES -> ledger-categories\n";
}

// Adicionar labels
$labels = [
    'menu.general.ledgercategories' => ['PT-BR' => 'Categorias de Lançamento', 'EN-US' => 'Ledger Categories'],
    'filter.ledgerCategory' => ['PT-BR' => 'Categoria', 'EN-US' => 'Category'],
    'ledgerCategory.title' => ['PT-BR' => 'Categorias de Lançamento', 'EN-US' => 'Ledger Categories'],
    'ledgerCategory.categoryCode' => ['PT-BR' => 'Código', 'EN-US' => 'Code'],
    'ledgerCategory.categoryName' => ['PT-BR' => 'Nome', 'EN-US' => 'Name'],
    'ledgerCategory.categoryType' => ['PT-BR' => 'Tipo', 'EN-US' => 'Type'],
    'ledgerCategory.description' => ['PT-BR' => 'Descrição', 'EN-US' => 'Description'],
    'ledgerCategory.type.expense' => ['PT-BR' => 'Despesa', 'EN-US' => 'Expense'],
    'ledgerCategory.type.receipt' => ['PT-BR' => 'Receita', 'EN-US' => 'Receipt'],
    'ledgerCategory.type.neutral' => ['PT-BR' => 'Neutro', 'EN-US' => 'Neutral'],
];

echo "\n=== Atualizando Labels ===\n";

foreach ($labels as $labelKey => $translations) {
    foreach ($translations as $lang => $text) {
        $existingLabel = $db->fetchOne(
            "SELECT RecId FROM SysLabelText WHERE LabelKey = ? AND LanguageId = ?",
            [$labelKey, $lang]
        );
        
        if ($existingLabel) {
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
echo "Menu de Categorias de Lançamento adicionado!\n\n";

echo "=== Onde lançar IOF e Tarifas Bancárias ===\n";
echo "No sistema ERPMini, os lançamentos de despesas são feitos através de:\n\n";
echo "1. Menu: GERAL > Diário Geral (journals)\n";
echo "   - Use para lançamentos manuais de IOF, tarifas, pro-labore, retiradas\n";
echo "   - Selecione a categoria correta (IOF, BANK_FEE, OPERATING, etc.)\n\n";
echo "2. Menu: GERAL > Diário de Impostos (tax-journals)\n";
echo "   - Para lançamentos específicos de impostos (INSS, Simples Nacional)\n\n";
echo "3. Menu: GERAL > Categorias de Lançamento (ledger-categories)\n";
echo "   - Gerencia as categorias disponíveis para classificar os lançamentos\n";
echo "   - Categorias atuais: TAX, OPERATING, PROFIT_WITHDRAWAL, BANK_FEE, IOF, MISC_EXPENSE, RECEIPT, DEDUCTION\n\n";
echo "4. Menu: CONTAS A PAGAR > Pedidos de Compra (purchase-orders)\n";
echo "   - Para compras de fornecedores (plano de saúde, etc.)\n\n";
