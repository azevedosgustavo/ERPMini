<?php
/**
 * Script para criar tabela de categorias de lançamento e migrar dados existentes
 * 
 * Categorias:
 * - TAX: Impostos (INSS, Simples, DARF)
 * - OPERATING: Despesas operacionais (Pro-labore)
 * - PROFIT_WITHDRAWAL: Retirada de lucro/dividendos
 * - BANK_FEE: Tarifas bancárias
 * - IOF: Imposto sobre Operações Financeiras
 * - MISC_EXPENSE: Despesas diversas
 * - RECEIPT: Recebimentos
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();

echo "=================================================================\n";
echo "CRIANDO TABELA DE CATEGORIAS DE LANÇAMENTO\n";
echo "=================================================================\n\n";

// 1. Criar tabela LedgerCategoryTable
echo "1. Criando tabela LedgerCategoryTable...\n";
try {
    $db->execute("
        CREATE TABLE IF NOT EXISTS LedgerCategoryTable (
            RecId INT NOT NULL AUTO_INCREMENT,
            CategoryCode VARCHAR(30) NOT NULL,
            Name VARCHAR(100) NOT NULL,
            Description VARCHAR(255) NOT NULL DEFAULT '',
            CategoryType CHAR(1) NOT NULL DEFAULT 'E' COMMENT 'E=Expense, R=Receipt, N=Neutral',
            IsActive CHAR(1) NOT NULL DEFAULT '1',
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_LedgerCategoryTable_CategoryCode (CategoryCode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
    echo "   OK - Tabela criada\n";
} catch (Exception $e) {
    echo "   INFO - Tabela já existe ou erro: " . $e->getMessage() . "\n";
}

// 2. Inserir categorias padrão
echo "\n2. Inserindo categorias padrão...\n";
$now = date('Y-m-d H:i:s');

$categories = [
    ['TAX', 'Impostos', 'Impostos federais, estaduais e municipais (INSS, Simples, DARF, etc.)', 'E'],
    ['OPERATING', 'Despesas Operacionais', 'Pro-labore e despesas operacionais da empresa', 'E'],
    ['PROFIT_WITHDRAWAL', 'Retirada de Lucro', 'Distribuição de lucros e dividendos aos sócios', 'E'],
    ['BANK_FEE', 'Tarifas Bancárias', 'Tarifas de serviços bancários (pacotes, transferências, etc.)', 'E'],
    ['IOF', 'IOF', 'Imposto sobre Operações Financeiras', 'E'],
    ['MISC_EXPENSE', 'Despesas Diversas', 'Despesas não classificadas em outras categorias', 'E'],
    ['RECEIPT', 'Recebimentos', 'Recebimentos de clientes e outras entradas', 'R'],
    ['DEDUCTION', 'Deduções', 'Deduções em notas fiscais e outros documentos', 'E'],
];

foreach ($categories as $cat) {
    try {
        $existing = $db->fetchOne("SELECT RecId FROM LedgerCategoryTable WHERE CategoryCode = ?", [$cat[0]]);
        if (!$existing) {
            $db->execute(
                "INSERT INTO LedgerCategoryTable (CategoryCode, Name, Description, CategoryType, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                 VALUES (?, ?, ?, ?, '1', ?, ?, 'SYSTEM')",
                [$cat[0], $cat[1], $cat[2], $cat[3], $now, $now]
            );
            echo "   + {$cat[0]}: {$cat[1]}\n";
        } else {
            echo "   = {$cat[0]}: já existe\n";
        }
    } catch (Exception $e) {
        echo "   ! {$cat[0]}: " . $e->getMessage() . "\n";
    }
}

// 3. Adicionar coluna LedgerCategoryId na LedgerJournalTrans (se não existir)
echo "\n3. Adicionando coluna LedgerCategoryId...\n";
try {
    // Verificar se coluna existe
    $cols = $db->fetchAll("SHOW COLUMNS FROM LedgerJournalTrans LIKE 'LedgerCategoryId'");
    if (empty($cols)) {
        $db->execute("
            ALTER TABLE LedgerJournalTrans 
            ADD COLUMN LedgerCategoryId INT NULL AFTER LedgerCategory
        ");
        echo "   OK - Coluna adicionada\n";
    } else {
        echo "   = Coluna já existe\n";
    }
} catch (Exception $e) {
    echo "   INFO - " . $e->getMessage() . "\n";
}

// 4. Migrar dados existentes
echo "\n4. Migrando categorias existentes...\n";

// Mapear categorias texto para IDs
$categoryMap = [];
$allCats = $db->fetchAll("SELECT RecId, CategoryCode FROM LedgerCategoryTable");
foreach ($allCats as $cat) {
    $categoryMap[strtoupper($cat['CategoryCode'])] = $cat['RecId'];
}

// Atualizar registros existentes
$journals = $db->fetchAll("SELECT RecId, LedgerCategory, Description FROM LedgerJournalTrans WHERE LedgerCategoryId IS NULL");
$updated = 0;

foreach ($journals as $journal) {
    $oldCat = strtoupper(trim($journal['LedgerCategory']));
    $desc = strtolower($journal['Description']);
    $newCatId = null;
    
    // Determinar categoria correta
    if (strpos($desc, 'retirada de lucro') !== false || strpos($desc, 'distribuição de lucro') !== false || strpos($desc, 'dividendo') !== false) {
        $newCatId = $categoryMap['PROFIT_WITHDRAWAL'] ?? null;
    } elseif (strpos($desc, 'pro-labore') !== false || strpos($desc, 'prolabore') !== false) {
        $newCatId = $categoryMap['OPERATING'] ?? null;
    } elseif ($oldCat === 'TAX' || strpos($desc, 'inss') !== false || strpos($desc, 'simples') !== false || strpos($desc, 'darf') !== false) {
        $newCatId = $categoryMap['TAX'] ?? null;
    } elseif ($oldCat === 'OPERATING') {
        $newCatId = $categoryMap['OPERATING'] ?? null;
    } elseif ($oldCat === 'BANK_FEE' || strpos($desc, 'tarifa') !== false) {
        $newCatId = $categoryMap['BANK_FEE'] ?? null;
    } elseif ($oldCat === 'IOF') {
        $newCatId = $categoryMap['IOF'] ?? null;
    } elseif ($oldCat === 'RECEIPT' || $journal['LedgerCategory'] === '' && $journal['AmountCurCredit'] > 0) {
        $newCatId = $categoryMap['RECEIPT'] ?? null;
    } else {
        // Tentar mapear direto pelo código
        $newCatId = $categoryMap[$oldCat] ?? $categoryMap['MISC_EXPENSE'] ?? null;
    }
    
    if ($newCatId) {
        $db->execute(
            "UPDATE LedgerJournalTrans SET LedgerCategoryId = ? WHERE RecId = ?",
            [$newCatId, $journal['RecId']]
        );
        $updated++;
    }
}

echo "   OK - $updated registros atualizados\n";

// 5. Verificar registros de retirada de lucro
echo "\n5. Verificando retiradas de lucro...\n";
$withdrawals = $db->fetchAll("
    SELECT j.TransDate, j.Voucher, j.Description, j.AmountCurDebit, c.Name as CategoryName
    FROM LedgerJournalTrans j
    LEFT JOIN LedgerCategoryTable c ON j.LedgerCategoryId = c.RecId
    WHERE j.LedgerCategoryId = ?
    ORDER BY j.TransDate
", [$categoryMap['PROFIT_WITHDRAWAL'] ?? 0]);

if (!empty($withdrawals)) {
    echo "   Retiradas de lucro encontradas:\n";
    $totalWithdrawal = 0;
    foreach ($withdrawals as $w) {
        printf("   - %-12s | R$ %12.2f | %s\n", 
            substr($w['TransDate'], 0, 10), 
            $w['AmountCurDebit'], 
            $w['Description']
        );
        $totalWithdrawal += $w['AmountCurDebit'];
    }
    echo "   Total: R$ " . number_format($totalWithdrawal, 2, ',', '.') . "\n";
} else {
    echo "   Nenhuma retirada de lucro categorizada ainda\n";
}

// 6. Resumo das categorias
echo "\n6. Resumo por categoria:\n";
$summary = $db->fetchAll("
    SELECT c.CategoryCode, c.Name, COUNT(*) as cnt, 
           SUM(j.AmountCurDebit) as debits, SUM(j.AmountCurCredit) as credits
    FROM LedgerJournalTrans j
    LEFT JOIN LedgerCategoryTable c ON j.LedgerCategoryId = c.RecId
    GROUP BY j.LedgerCategoryId, c.CategoryCode, c.Name
    ORDER BY c.CategoryCode
");

foreach ($summary as $s) {
    printf("   %-20s | %3d reg | Débitos: %12.2f | Créditos: %12.2f\n",
        $s['Name'] ?? '(não categorizado)',
        $s['cnt'],
        $s['debits'] ?? 0,
        $s['credits'] ?? 0
    );
}

echo "\n=================================================================\n";
echo "MIGRAÇÃO CONCLUÍDA\n";
echo "=================================================================\n";
