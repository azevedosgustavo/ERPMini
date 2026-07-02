<?php

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$now = date('Y-m-d H:i:s');

echo "=== Adicionando Labels para Exportação e Impressão ===\n\n";

// Labels para exportação e impressão
$labels = [
    'button.exportpdf' => [
        'PT-BR' => 'Exportar PDF',
        'EN-US' => 'Export PDF'
    ],
    'button.exportexcel' => [
        'PT-BR' => 'Exportar Excel',
        'EN-US' => 'Export Excel'
    ],
    'button.print' => [
        'PT-BR' => 'Imprimir',
        'EN-US' => 'Print'
    ],
    'toast.pdf.exported' => [
        'PT-BR' => 'PDF exportado com sucesso!',
        'EN-US' => 'PDF exported successfully!'
    ],
    'toast.excel.exported' => [
        'PT-BR' => 'Excel exportado com sucesso!',
        'EN-US' => 'Excel exported successfully!'
    ],
    'toast.report.nodata' => [
        'PT-BR' => 'Nenhum relatório disponível para exportação.',
        'EN-US' => 'No report available for export.'
    ],
    'table.totals' => [
        'PT-BR' => 'Totais',
        'EN-US' => 'Totals'
    ]
];

$inserted = 0;

foreach ($labels as $labelKey => $translations) {
    foreach ($translations as $lang => $text) {
        $existing = $db->fetchOne(
            "SELECT RecId FROM SysLabelText WHERE LabelKey = ? AND LanguageId = ?",
            [$labelKey, $lang]
        );

        if ($existing) {
            // Apenas exibir que já existe
            echo "✓ $labelKey ($lang)\n";
        } else {
            $db->execute(
                "INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                 VALUES (?, ?, ?, '1', ?, ?, 'SYSTEM')",
                [$labelKey, $lang, $text, $now, $now]
            );
            echo "✓ $labelKey ($lang) - INSERIDO\n";
            $inserted++;
        }
    }
}

echo "\n✅ Labels adicionados! ($inserted novos labels)\n";
echo "\nTodos os relatórios agora têm os botões de:\n";
echo "- Exportar PDF\n";
echo "- Exportar Excel\n";
echo "- Imprimir\n";
