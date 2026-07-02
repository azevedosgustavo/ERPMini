<?php
/**
 * Script para reorganizar menus - Criar grupo Fiscal em Administração de Sistemas
 * Move: Códigos de Serviço, Categorias de Lançamento, Tipos de Impostos
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

$now = date('Y-m-d H:i:s');

echo "=== Criando grupo FISCAL ===\n";

// Verificar se o grupo FISCAL já existe
$fiscalGroup = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'FISCAL'");

if (!$fiscalGroup) {
    // Inserir grupo FISCAL após SYSADMIN (SequenceNo = 2)
    $db->execute(
        "INSERT INTO SysMenuGroup (GroupCode, LabelKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
         VALUES ('FISCAL', 'menu.group.fiscal', 2, '1', ?, ?, 'SYSTEM')",
        [$now, $now]
    );
    $inserted = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'FISCAL' LIMIT 1");
    $fiscalGroupId = (int) $inserted['RecId'];
    echo "INSERIDO: Grupo FISCAL (RecId: $fiscalGroupId)\n";
    
    // Atualizar sequência dos outros grupos
    $db->execute("UPDATE SysMenuGroup SET SequenceNo = SequenceNo + 1 WHERE GroupCode NOT IN ('SYSADMIN', 'FISCAL') AND SequenceNo >= 2");
} else {
    $fiscalGroupId = $fiscalGroup['RecId'];
    echo "Grupo FISCAL já existe (RecId: $fiscalGroupId)\n";
}

// Labels do grupo
$groupLabels = [
    'menu.group.fiscal' => ['PT-BR' => 'Fiscal', 'EN-US' => 'Fiscal'],
];

foreach ($groupLabels as $labelKey => $translations) {
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
}
echo "Labels do grupo adicionados\n";

echo "\n=== Movendo itens de menu para grupo FISCAL ===\n";

// Itens a mover para FISCAL
$itemsToMove = [
    'GEN_SERVICES' => ['newCode' => 'FISCAL_SERVICE_CODES', 'seq' => 1],
    'GEN_LEDGER_CATEGORIES' => ['newCode' => 'FISCAL_LEDGER_CATEGORIES', 'seq' => 2],
    'GEN_TAX_TYPES' => ['newCode' => 'FISCAL_TAX_TYPES', 'seq' => 3],
];

foreach ($itemsToMove as $oldCode => $config) {
    $item = $db->fetchOne("SELECT RecId, ViewKey, LabelKey FROM SysMenuItem WHERE MenuCode = ?", [$oldCode]);
    
    if ($item) {
        // Atualizar o item existente para o novo grupo
        $db->execute(
            "UPDATE SysMenuItem SET GroupId = ?, MenuCode = ?, SequenceNo = ?, ModifiedDateTime = ? WHERE RecId = ?",
            [$fiscalGroupId, $config['newCode'], $config['seq'], $now, $item['RecId']]
        );
        echo "MOVIDO: $oldCode -> {$config['newCode']} (Grupo FISCAL, Seq {$config['seq']})\n";
    } else {
        echo "AVISO: Item $oldCode não encontrado\n";
    }
}

// Atualizar labels dos menus movidos
$menuLabels = [
    'menu.fiscal.servicecodes' => ['PT-BR' => 'Códigos de Serviço', 'EN-US' => 'Service Codes'],
    'menu.fiscal.ledgercategories' => ['PT-BR' => 'Categorias de Lançamento', 'EN-US' => 'Ledger Categories'],
    'menu.fiscal.taxtypes' => ['PT-BR' => 'Tipos de Impostos', 'EN-US' => 'Tax Types'],
];

echo "\n=== Atualizando Labels ===\n";

foreach ($menuLabels as $labelKey => $translations) {
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

// Atualizar os LabelKeys dos itens movidos
$db->execute("UPDATE SysMenuItem SET LabelKey = 'menu.fiscal.servicecodes' WHERE MenuCode = 'FISCAL_SERVICE_CODES'");
$db->execute("UPDATE SysMenuItem SET LabelKey = 'menu.fiscal.ledgercategories' WHERE MenuCode = 'FISCAL_LEDGER_CATEGORIES'");
$db->execute("UPDATE SysMenuItem SET LabelKey = 'menu.fiscal.taxtypes' WHERE MenuCode = 'FISCAL_TAX_TYPES'");

echo "\n=== Estrutura Final ===\n";

$groups = $db->fetchAll("SELECT RecId, GroupCode, LabelKey, SequenceNo FROM SysMenuGroup WHERE IsActive = '1' ORDER BY SequenceNo");
foreach ($groups as $group) {
    echo "\n[{$group['GroupCode']}] ({$group['LabelKey']})\n";
    
    $items = $db->fetchAll(
        "SELECT MenuCode, ViewKey, LabelKey, SequenceNo FROM SysMenuItem WHERE GroupId = ? AND IsActive = '1' ORDER BY SequenceNo",
        [$group['RecId']]
    );
    
    foreach ($items as $item) {
        echo "  - {$item['MenuCode']} -> {$item['ViewKey']}\n";
    }
}

echo "\n=== Concluído ===\n";
