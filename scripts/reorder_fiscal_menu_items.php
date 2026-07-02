<?php

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$now = date('Y-m-d H:i:s');

echo "=== Reordenando Menus do Grupo FISCAL ===\n\n";

// Obter o ID do grupo FISCAL
$fiscalGroup = $db->fetchOne("SELECT RecId FROM SysMenuGroup WHERE GroupCode = 'FISCAL'");
if (!$fiscalGroup) {
    echo "ERRO: Grupo FISCAL não encontrado\n";
    exit(1);
}
$fiscalGroupId = (int) $fiscalGroup['RecId'];

// Mapa de ViewKey para SequenceNo na nova ordem
$menuOrder = [
    'journals' => 1,           // 1 - Diário Gerais
    'tax-journals' => 2,       // 2 - Diário de Imposto
    'tax-types' => 3,          // 3 - Tipo de Imposto
    'service-codes' => 4,      // 4 - Códigos de Serviço
    'ledger-categories' => 5   // 5 - Categorias de Lançamento
];

echo "Atualizando sequência dos menus:\n\n";

foreach ($menuOrder as $viewKey => $seq) {
    $item = $db->fetchOne(
        "SELECT RecId, MenuCode FROM SysMenuItem WHERE GroupId = ? AND ViewKey = ?",
        [$fiscalGroupId, $viewKey]
    );

    if ($item) {
        $db->execute(
            "UPDATE SysMenuItem SET SequenceNo = ?, ModifiedDateTime = ? WHERE RecId = ?",
            [$seq, $now, (int) $item['RecId']]
        );
        
        $labels = [
            'journals' => 'Diário Gerais',
            'tax-journals' => 'Diário de Imposto',
            'tax-types' => 'Tipo de Imposto',
            'service-codes' => 'Códigos de Serviço',
            'ledger-categories' => 'Categorias de Lançamento'
        ];
        
        echo "$seq. {$labels[$viewKey]} (ViewKey: {$viewKey})\n";
    } else {
        echo "⚠️  AVISO: Menu com ViewKey '{$viewKey}' não encontrado no grupo FISCAL\n";
    }
}

echo "\n✅ Menus do grupo Fiscal reordenados com sucesso!\n";
echo "\nNova ordem:\n";
echo "1 - Diário Gerais\n";
echo "2 - Diário de Imposto\n";
echo "3 - Tipo de Imposto\n";
echo "4 - Códigos de Serviço\n";
echo "5 - Categorias de Lançamento\n";
