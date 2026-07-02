<?php

class ReportModel extends BaseModel
{
    /**
     * Relatório de Contas a Receber
     * Mostra todas as faturas ativas de clientes com filtros de status e vencimento
     */
    public function accountsReceivable($filters)
    {
        $conditions = ['ci.IsActive = "1"'];
        $params = [];

        if (!empty($filters['Status'])) {
            $conditions[] = 'ci.Status = ?';
            $params[] = strtoupper(substr(trim($filters['Status']), 0, 1));
        }

        if (!empty($filters['DueDateFrom'])) {
            $conditions[] = 'ci.DueDate >= ?';
            $params[] = $this->dateTimeOrNow($filters['DueDateFrom']);
        }

        if (!empty($filters['DueDateTo'])) {
            $conditions[] = 'ci.DueDate <= ?';
            $params[] = $this->dateTimeOrNow($filters['DueDateTo']);
        }

        if (!empty($filters['CustAccount'])) {
            $conditions[] = 'ci.CustAccount = ?';
            $params[] = trim($filters['CustAccount']);
        }

        $sql = 'SELECT ci.InvoiceId, ci.InvoiceNumber, ci.CustAccount, dp.Name AS CustomerName, 
                       DATE_FORMAT(ci.InvoiceDate, "%Y-%m-%d") AS InvoiceDate, 
                       DATE_FORMAT(ci.DueDate, "%Y-%m-%d") AS DueDate, 
                       ci.Status, ci.TotalAmount, ci.TaxAmount, ci.DeductionAmount
                FROM CustInvoiceJour ci
                LEFT JOIN DirPartyTable dp ON dp.RecId = ci.PartyId
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY ci.DueDate ASC, ci.InvoiceDate ASC';

        $rows = $this->db->fetchAll($sql, $params);
        return $this->withGrandTotal($rows, 'TotalAmount');
    }

    /**
     * Relatório de Contas a Pagar
     * Mostra apenas pedidos de compra (sem duplicar com diários de pagamento)
     * Diários de pagamento são apenas registros de pagamento, não despesas adicionais
     */
    public function accountsPayable($filters)
    {
        $conditions = ['p.IsActive = "1"'];
        $params = [];

        if (!empty($filters['Status'])) {
            $conditions[] = 'p.Status = ?';
            $params[] = strtoupper(substr(trim($filters['Status']), 0, 1));
        }

        if (!empty($filters['DueDateFrom'])) {
            $conditions[] = 'p.DueDate >= ?';
            $params[] = $this->dateTimeOrNow($filters['DueDateFrom']);
        }

        if (!empty($filters['DueDateTo'])) {
            $conditions[] = 'p.DueDate <= ?';
            $params[] = $this->dateTimeOrNow($filters['DueDateTo']);
        }

        if (!empty($filters['VendAccount'])) {
            $conditions[] = 'p.VendAccount = ?';
            $params[] = trim($filters['VendAccount']);
        }

        $rows = $this->db->fetchAll(
            'SELECT p.PurchId AS DocumentId, p.PurchNumber AS DocumentNumber, 
                    d.Name AS VendorName, 
                    DATE_FORMAT(p.PurchDate, "%Y-%m-%d") AS PurchDate, 
                    DATE_FORMAT(p.DueDate, "%Y-%m-%d") AS DueDate, 
                    p.Status, p.TotalAmount AS Amount,
                    CASE p.PurchType WHEN "S" THEN "Servico" ELSE "Material" END AS PurchType
             FROM PurchTable p
             INNER JOIN DirPartyTable d ON d.RecId = p.PartyId
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY p.DueDate ASC, p.PurchDate ASC',
            $params
        );

        return $this->withGrandTotal($rows, 'Amount');
    }

    /**
     * Relatório de Faturamento por Período
     */
    public function billingByPeriod($filters)
    {
        $conditions = ['ci.IsActive = "1"'];
        $params = [];

        if (!empty($filters['FromDate'])) {
            $conditions[] = 'ci.InvoiceDate >= ?';
            $params[] = $this->dateTimeOrNow($filters['FromDate']);
        }

        if (!empty($filters['ToDate'])) {
            $conditions[] = 'ci.InvoiceDate <= ?';
            $params[] = $this->dateTimeOrNow($filters['ToDate']);
        }

        if (!empty($filters['Status'])) {
            $conditions[] = 'ci.Status = ?';
            $params[] = strtoupper(substr(trim($filters['Status']), 0, 1));
        }

        $rows = $this->db->fetchAll(
            'SELECT ci.InvoiceId, ci.InvoiceNumber, ci.CustAccount, dp.Name AS CustomerName, 
                    DATE_FORMAT(ci.InvoiceDate, "%Y-%m-%d") AS InvoiceDate, 
                    DATE_FORMAT(ci.DueDate, "%Y-%m-%d") AS DueDate, 
                    ci.Status, ci.TotalAmount
             FROM CustInvoiceJour ci
             LEFT JOIN DirPartyTable dp ON dp.RecId = ci.PartyId
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY ci.InvoiceDate ASC',
            $params
        );

        return $this->withGrandTotal($rows, 'TotalAmount');
    }

    /**
     * Relatório de Faturamento por Cliente
     */
    public function billingByCustomer($filters)
    {
        $conditions = ['ci.IsActive = "1"'];
        $params = [];

        // CustAccount é opcional - se não informado, agrupa por cliente
        if (!empty($filters['CustAccount'])) {
            $conditions[] = 'ci.CustAccount = ?';
            $params[] = trim($filters['CustAccount']);
        }

        if (!empty($filters['FromDate'])) {
            $conditions[] = 'ci.InvoiceDate >= ?';
            $params[] = $this->dateTimeOrNow($filters['FromDate']);
        }

        if (!empty($filters['ToDate'])) {
            $conditions[] = 'ci.InvoiceDate <= ?';
            $params[] = $this->dateTimeOrNow($filters['ToDate']);
        }

        // Se nenhum cliente selecionado, retornar resumo por cliente
        if (empty($filters['CustAccount'])) {
            $rows = $this->db->fetchAll(
                'SELECT ci.CustAccount, dp.Name AS CustomerName, 
                        COUNT(*) AS TotalInvoices,
                        SUM(ci.TotalAmount) AS TotalAmount,
                        MIN(DATE_FORMAT(ci.InvoiceDate, "%Y-%m-%d")) AS FirstInvoice,
                        MAX(DATE_FORMAT(ci.InvoiceDate, "%Y-%m-%d")) AS LastInvoice
                 FROM CustInvoiceJour ci
                 LEFT JOIN DirPartyTable dp ON dp.RecId = ci.PartyId
                 WHERE ' . implode(' AND ', $conditions) . '
                 GROUP BY ci.CustAccount, dp.Name
                 ORDER BY SUM(ci.TotalAmount) DESC',
                $params
            );
        } else {
            $rows = $this->db->fetchAll(
                'SELECT ci.InvoiceId, ci.InvoiceNumber, ci.CustAccount, dp.Name AS CustomerName, 
                        DATE_FORMAT(ci.InvoiceDate, "%Y-%m-%d") AS InvoiceDate, 
                        DATE_FORMAT(ci.DueDate, "%Y-%m-%d") AS DueDate, 
                        ci.Status, ci.TotalAmount
                 FROM CustInvoiceJour ci
                 LEFT JOIN DirPartyTable dp ON dp.RecId = ci.PartyId
                 WHERE ' . implode(' AND ', $conditions) . '
                 ORDER BY ci.InvoiceDate ASC',
                $params
            );
        }

        return $this->withGrandTotal($rows, 'TotalAmount');
    }

    /**
     * Relatório de Despesas por Período
     * Mostra compras (PurchTable) + despesas do diário (impostos, taxas, operacionais)
     */
    public function expensesByPeriod($filters)
    {
        $fromDate = !empty($filters['FromDate']) ? $this->dateTimeOrNow($filters['FromDate']) : '2000-01-01';
        $toDate = !empty($filters['ToDate']) ? $this->dateTimeOrNow($filters['ToDate']) : '2099-12-31';
        $vendAccount = !empty($filters['VendAccount']) ? trim($filters['VendAccount']) : null;
        $purchType = !empty($filters['PurchType']) ? strtoupper(substr(trim($filters['PurchType']), 0, 1)) : null;
        $categoryId = !empty($filters['LedgerCategoryId']) ? (int) $filters['LedgerCategoryId'] : null;
        $categoryCode = !empty($filters['Category']) ? strtoupper(trim($filters['Category'])) : null;
        
        $rows = [];
        
        // 1. Compras (PurchTable) - apenas se não filtrar por categoria de diário
        if (!$categoryId && (!$categoryCode || $categoryCode === 'COMPRA' || $categoryCode === 'PURCHASE' || $categoryCode === 'ALL')) {
            $condPurch = ['p.IsActive = "1"', 'p.PurchDate >= ?', 'p.PurchDate <= ?'];
            $paramsPurch = [$fromDate, $toDate];
            
            if ($vendAccount) {
                $condPurch[] = 'p.VendAccount = ?';
                $paramsPurch[] = $vendAccount;
            }
            if ($purchType) {
                $condPurch[] = 'p.PurchType = ?';
                $paramsPurch[] = $purchType;
            }
            
            $purchases = $this->db->fetchAll(
                'SELECT p.PurchId AS DocumentId, p.PurchNumber AS DocumentNumber, 
                        d.Name AS VendorName, 
                        DATE_FORMAT(p.PurchDate, "%Y-%m-%d") AS TransDate, 
                        p.TotalAmount AS Amount, p.Status,
                        CASE p.PurchType WHEN "S" THEN "Serviço" ELSE "Material" END AS ExpenseType,
                        "Compra" AS CategoryName
                 FROM PurchTable p
                 INNER JOIN DirPartyTable d ON d.RecId = p.PartyId
                 WHERE ' . implode(' AND ', $condPurch),
                $paramsPurch
            );
            $rows = array_merge($rows, $purchases);
        }
        
        // 2. Despesas do diário (impostos, taxas bancárias, operacionais, etc.) - usando LedgerCategoryId
        $condJour = ['t.IsActive = "1"', 'j.Posted = "1"', 't.AmountCurDebit > 0', 't.TransDate >= ?', 't.TransDate <= ?'];
        $paramsJour = [$fromDate, $toDate];
        
        if ($categoryId) {
            $condJour[] = 't.LedgerCategoryId = ?';
            $paramsJour[] = $categoryId;
        } elseif ($categoryCode && !in_array($categoryCode, ['COMPRA', 'PURCHASE', 'ALL'])) {
            $condJour[] = '(c.CategoryCode = ? OR UPPER(t.LedgerCategory) = ?)';
            $paramsJour[] = $categoryCode;
            $paramsJour[] = $categoryCode;
        }
        
        $journalExpenses = $this->db->fetchAll(
            'SELECT t.Voucher AS DocumentId, t.Voucher AS DocumentNumber,
                    t.Description AS VendorName,
                    DATE_FORMAT(t.TransDate, "%Y-%m-%d") AS TransDate,
                    t.AmountCurDebit AS Amount,
                    "P" AS Status,
                    COALESCE(c.Name, 
                        CASE t.LedgerCategory 
                            WHEN "TAX" THEN "Imposto"
                            WHEN "OPERATING" THEN "Operacional"
                            WHEN "PROFIT_WITHDRAWAL" THEN "Retirada de Lucro"
                            WHEN "BANK_FEE" THEN "Tarifa Bancária"
                            WHEN "IOF" THEN "IOF"
                            WHEN "MISC_EXPENSE" THEN "Despesa Diversa"
                            ELSE t.LedgerCategory
                        END
                    ) AS ExpenseType,
                    COALESCE(c.Name, t.LedgerCategory) AS CategoryName
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE ' . implode(' AND ', $condJour),
            $paramsJour
        );
        $rows = array_merge($rows, $journalExpenses);
        
        // Ordenar por data
        usort($rows, function($a, $b) {
            return strcmp($a['TransDate'], $b['TransDate']);
        });
        
        return $this->withGrandTotal($rows, 'Amount');
    }

    /**
     * Resumo Financeiro Mensal
     * Calcula totais mensais de faturamento e despesas SEM duplicação
     * - Faturamento = CustInvoiceJour
     * - Despesas = PurchTable + Impostos de diários
     */
    public function financialSummary($filters)
    {
        $fromDate = !empty($filters['FromDate']) ? $this->dateTimeOrNow($filters['FromDate']) : '2000-01-01 00:00:00';
        $toDate = !empty($filters['ToDate']) ? $this->dateTimeOrNow($filters['ToDate']) : '2099-12-31 23:59:59';
        $opening = $this->resolveOpeningBalanceContext($fromDate, $toDate);

        // Faturamento de faturas
        $invoiceRevenue = $this->db->fetchAll(
            'SELECT DATE_FORMAT(InvoiceDate, "%Y-%m") AS Month, SUM(TotalAmount) AS Amount
             FROM CustInvoiceJour
             WHERE IsActive = "1" AND InvoiceDate >= ? AND InvoiceDate <= ?
             GROUP BY DATE_FORMAT(InvoiceDate, "%Y-%m")',
            [$fromDate, $toDate]
        );

        // Outros recebimentos sem fatura de serviço
        $otherReceipts = $this->db->fetchAll(
            'SELECT DATE_FORMAT(t.TransDate, "%Y-%m") AS Month, SUM(t.AmountCurCredit) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate <= ?
               AND t.AmountCurCredit > 0
               AND t.ServiceInvoiceRecId IS NULL
               AND (c.CategoryCode = "OTHER_RECEIPT"
                    OR (t.LedgerCategoryId IS NULL AND UPPER(t.LedgerCategory) IN ("OTHER_RECEIPT", "OUTROS_RECEBIMENTOS")))
             GROUP BY DATE_FORMAT(t.TransDate, "%Y-%m")',
            [$fromDate, $toDate]
        );

        // Despesas de compras
        $purchExpenses = $this->db->fetchAll(
            'SELECT DATE_FORMAT(PurchDate, "%Y-%m") AS Month, SUM(TotalAmount) AS Amount
             FROM PurchTable
             WHERE IsActive = "1" AND PurchDate >= ? AND PurchDate <= ?
             GROUP BY DATE_FORMAT(PurchDate, "%Y-%m")',
            [$fromDate, $toDate]
        );

        // Despesas de diários (impostos, operacionais, tarifas bancárias, IOF, etc.)
        // Inclui TODAS as categorias de despesa: TAX, OPERATING, BANK_FEE, IOF, MISC_EXPENSE
        $journalExpenses = $this->db->fetchAll(
            'SELECT DATE_FORMAT(t.TransDate, "%Y-%m") AS Month, SUM(t.AmountCurDebit) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             WHERE t.IsActive = "1" AND j.Posted = "1" 
               AND t.TransDate >= ? AND t.TransDate <= ?
               AND t.AmountCurDebit > 0
             GROUP BY DATE_FORMAT(t.TransDate, "%Y-%m")',
            [$fromDate, $toDate]
        );

        // Consolidar dados por mês
        $months = [];
        foreach ($invoiceRevenue as $row) {
            $m = $row['Month'];
            if (!isset($months[$m])) {
                $months[$m] = ['Month' => $m, 'TotalBilling' => 0, 'OtherReceipts' => 0, 'TotalExpenses' => 0];
            }
            $months[$m]['TotalBilling'] += (float) $row['Amount'];
        }
        foreach ($otherReceipts as $row) {
            $m = $row['Month'];
            if (!isset($months[$m])) {
                $months[$m] = ['Month' => $m, 'TotalBilling' => 0, 'OtherReceipts' => 0, 'TotalExpenses' => 0];
            }
            $months[$m]['OtherReceipts'] += (float) $row['Amount'];
            $months[$m]['TotalBilling'] += (float) $row['Amount'];
        }
        foreach ($purchExpenses as $row) {
            $m = $row['Month'];
            if (!isset($months[$m])) {
                $months[$m] = ['Month' => $m, 'TotalBilling' => 0, 'OtherReceipts' => 0, 'TotalExpenses' => 0];
            }
            $months[$m]['TotalExpenses'] += (float) $row['Amount'];
        }
        foreach ($journalExpenses as $row) {
            $m = $row['Month'];
            if (!isset($months[$m])) {
                $months[$m] = ['Month' => $m, 'TotalBilling' => 0, 'OtherReceipts' => 0, 'TotalExpenses' => 0];
            }
            $months[$m]['TotalExpenses'] += (float) $row['Amount'];
        }

        // Ordenar e calcular saldo
        ksort($months);
        $rows = [];
        foreach ($months as $data) {
            $data['NetBalance'] = $data['TotalBilling'] - $data['TotalExpenses'];
            $rows[] = $data;
        }

        if (empty($rows)) {
            $rows[] = [
                'Month' => substr($fromDate, 0, 7),
                'TotalBilling' => 0,
                'OtherReceipts' => 0,
                'TotalExpenses' => 0,
                'NetBalance' => 0
            ];
        }

        $netMovement = $this->sumField($rows, 'NetBalance');

        return [
            'rows' => $rows,
            'totals' => [
                'TotalBilling' => $this->sumField($rows, 'TotalBilling'),
                'OtherReceipts' => $this->sumField($rows, 'OtherReceipts'),
                'TotalExpenses' => $this->sumField($rows, 'TotalExpenses'),
                'NetBalance' => $netMovement,
                'OpeningBalance' => $opening['baseBalance'],
                'ClosingBalance' => $opening['baseBalance'] + $netMovement
            ]
        ];
    }

    /**
     * Demonstração de Resultado do Exercício (DRE)
     * Relatório em português com cálculos corretos e SEM duplicação
     * Usa LedgerCategoryId para classificação
     */
    public function profitAndLoss($filters)
    {
        list($fromDate, $toDate) = $this->resolvePeriod($filters);
        $opening = $this->resolveOpeningDreBalanceContext($fromDate, $toDate);

        // Receita de outros recebimentos (sem fatura de serviço)
        $otherReceipts = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurCredit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate <= ?
               AND t.AmountCurCredit > 0
               AND t.ServiceInvoiceRecId IS NULL
               AND (c.CategoryCode = "OTHER_RECEIPT"
                    OR (t.LedgerCategoryId IS NULL AND UPPER(t.LedgerCategory) IN ("OTHER_RECEIPT", "OUTROS_RECEBIMENTOS")))',
            [$fromDate, $toDate]
        );

        // 1. Receita Bruta = Faturas de serviço
        $invoiceRevenue = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount, 
                    IFNULL(SUM(TaxAmount), 0) AS TaxAmount, 
                    IFNULL(SUM(DeductionAmount), 0) AS DeductionAmount
             FROM CustInvoiceJour
             WHERE IsActive = "1" AND InvoiceDate >= ? AND InvoiceDate <= ?',
            [$fromDate, $toDate]
        );

        // 2. Impostos sobre faturamento (deduções na NF)
        $invoiceTaxes = (float) $invoiceRevenue['TaxAmount'] + (float) $invoiceRevenue['DeductionAmount'];

        // 3. Impostos de diários (INSS, Simples, etc.) - usando LedgerCategoryId
        $taxJournal = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1" 
               AND t.TransDate >= ? AND t.TransDate <= ? 
               AND (c.CategoryCode IN ("TAX", "DEDUCTION") OR UPPER(t.LedgerCategory) IN ("TAX", "DEDUCTION", "IMPOSTO", "IMPOSTOS"))',
            [$fromDate, $toDate]
        );

        // 4. Custo dos Serviços Prestados = Compras de serviço
        $serviceCost = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM PurchTable
             WHERE IsActive = "1" AND PurchType = "S" AND PurchDate >= ? AND PurchDate <= ?',
            [$fromDate, $toDate]
        );

        // 5. Custo de Materiais/Produtos
        $materialCost = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM PurchTable
             WHERE IsActive = "1" AND PurchType = "I" AND PurchDate >= ? AND PurchDate <= ?',
            [$fromDate, $toDate]
        );

        // 6. Despesas Operacionais (pro-labore, tarifas bancárias, IOF, etc.) - SEM retirada de lucro
        $operatingExpenses = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1" 
               AND t.TransDate >= ? AND t.TransDate <= ? 
               AND (c.CategoryCode IN ("OPERATING", "BANK_FEE", "IOF", "MISC_EXPENSE") 
                    OR (t.LedgerCategoryId IS NULL AND UPPER(t.LedgerCategory) IN ("OPERATING", "BANK_FEE", "IOF", "MISC_EXPENSE", "OPERACIONAL")))',
            [$fromDate, $toDate]
        );

        // 7. Retirada de Lucro Antecipada (distribuição aos sócios)
        $profitWithdrawal = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1" 
               AND t.TransDate >= ? AND t.TransDate <= ? 
               AND c.CategoryCode = "PROFIT_WITHDRAWAL"',
            [$fromDate, $toDate]
        );

        // Cálculos do DRE
        $invoiceOnlyRevenue = (float) $invoiceRevenue['Amount'];
        $otherReceiptAmount = (float) $otherReceipts['Amount'];
        $grossRevenue = $invoiceOnlyRevenue + $otherReceiptAmount;
        $totalTaxes = $invoiceTaxes + (float) $taxJournal['Amount'];
        $netRevenue = $grossRevenue - $totalTaxes;
        $servicesCost = (float) $serviceCost['Amount'];
        $materialsCost = (float) $materialCost['Amount'];
        $totalCosts = $servicesCost + $materialsCost;
        $grossProfit = $netRevenue - $totalCosts;
        $operExpenses = (float) $operatingExpenses['Amount'];
        $netIncome = $grossProfit - $operExpenses;
        $withdrawals = (float) $profitWithdrawal['Amount'];
        $retainedEarnings = $this->normalizeAmount($netIncome - $withdrawals);
        $openingBalance = $this->normalizeAmount((float) $opening['baseBalance']);
        $closingBalance = $this->normalizeAmount($openingBalance + $retainedEarnings);

        // Estrutura do DRE em português
        $rows = [
            ['LineKey' => 'RECEITA_FATURAS', 'LineName' => 'Receita de Faturas de Serviço', 'Amount' => $invoiceOnlyRevenue, 'LineType' => 'header'],
            ['LineKey' => 'OUTROS_RECEBIMENTOS', 'LineName' => 'Outros Recebimentos (sem fatura)', 'Amount' => $otherReceiptAmount, 'LineType' => 'header'],
            ['LineKey' => 'RECEITA_BRUTA', 'LineName' => '(=) Receita Bruta', 'Amount' => $grossRevenue, 'LineType' => 'subtotal'],
            ['LineKey' => 'IMPOSTOS', 'LineName' => '(-) Impostos e Deduções', 'Amount' => -$totalTaxes, 'LineType' => 'deduction'],
            ['LineKey' => 'RECEITA_LIQUIDA', 'LineName' => '(=) Receita Líquida', 'Amount' => $netRevenue, 'LineType' => 'subtotal'],
            ['LineKey' => 'CUSTO_SERVICOS', 'LineName' => '(-) Custo dos Serviços', 'Amount' => -$servicesCost, 'LineType' => 'cost'],
            ['LineKey' => 'CUSTO_MATERIAIS', 'LineName' => '(-) Custo de Materiais', 'Amount' => -$materialsCost, 'LineType' => 'cost'],
            ['LineKey' => 'LUCRO_BRUTO', 'LineName' => '(=) Lucro Bruto', 'Amount' => $grossProfit, 'LineType' => 'subtotal'],
            ['LineKey' => 'DESPESAS_OPERACIONAIS', 'LineName' => '(-) Despesas Operacionais', 'Amount' => -$operExpenses, 'LineType' => 'expense'],
            ['LineKey' => 'LUCRO_LIQUIDO', 'LineName' => '(=) Lucro Líquido', 'Amount' => $netIncome, 'LineType' => 'subtotal'],
            ['LineKey' => 'RETIRADA_LUCRO', 'LineName' => '(-) Retirada de Lucro Antecipada', 'Amount' => -$withdrawals, 'LineType' => 'withdrawal'],
            ['LineKey' => 'LUCRO_RETIDO', 'LineName' => '(=) Lucro Retido / Disponível', 'Amount' => $retainedEarnings, 'LineType' => 'total'],
            ['LineKey' => 'SALDO_INICIAL', 'LineName' => 'Saldo Inicial do Período', 'Amount' => $openingBalance, 'LineType' => 'subtotal'],
            ['LineKey' => 'SALDO_FINAL', 'LineName' => 'Saldo Final do Período', 'Amount' => $closingBalance, 'LineType' => 'total']
        ];

        return [
            'rows' => $rows,
            'totals' => [
                'ReceitaBruta' => $grossRevenue,
                'ReceitaFaturas' => $invoiceOnlyRevenue,
                'OutrosRecebimentos' => $otherReceiptAmount,
                'Impostos' => $totalTaxes,
                'ReceitaLiquida' => $netRevenue,
                'CustoServicos' => $servicesCost,
                'CustoMateriais' => $materialsCost,
                'LucroBruto' => $grossProfit,
                'DespesasOperacionais' => $operExpenses,
                'LucroLiquido' => $netIncome,
                'RetiradaLucro' => $withdrawals,
                'LucroRetido' => $retainedEarnings,
                'OpeningBalance' => $openingBalance,
                'ClosingBalance' => $closingBalance
            ],
            'period' => [
                'from' => substr($fromDate, 0, 10),
                'to' => substr($toDate, 0, 10)
            ]
        ];
    }

    private function resolveOpeningDreBalanceContext($fromDate, $toDate)
    {
        $company = $this->db->fetchOne(
            'SELECT InitialBalance, InitialBalanceDate
             FROM CompanyInfo
             WHERE IsActive = "1"
             ORDER BY IsDefault DESC, RecId ASC
             LIMIT 1'
        );

        $openingValue = $company && $company['InitialBalance'] !== null ? (float) $company['InitialBalance'] : 0.0;
        $openingDate = ($company && !empty($company['InitialBalanceDate'])) ? ($company['InitialBalanceDate'] . ' 00:00:00') : null;

        if (!$openingDate || $openingDate > $toDate) {
            return ['baseBalance' => 0.0];
        }

        if ($openingDate >= $fromDate) {
            return ['baseBalance' => $openingValue];
        }

        // Carry-forward rule: opening balance of current period = closing balance of previous period.
        $previousRetained = $this->calculateDreRetainedBetween($openingDate, $fromDate);
        return ['baseBalance' => $openingValue + $previousRetained];
    }

    private function calculateDreRetainedBetween($fromDate, $toDateExclusive)
    {
        $otherReceipts = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurCredit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate < ?
               AND t.AmountCurCredit > 0
               AND t.ServiceInvoiceRecId IS NULL
               AND (c.CategoryCode = "OTHER_RECEIPT"
                    OR (t.LedgerCategoryId IS NULL AND UPPER(t.LedgerCategory) IN ("OTHER_RECEIPT", "OUTROS_RECEBIMENTOS")))',
            [$fromDate, $toDateExclusive]
        );

        $invoiceRevenue = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount,
                    IFNULL(SUM(TaxAmount), 0) AS TaxAmount,
                    IFNULL(SUM(DeductionAmount), 0) AS DeductionAmount
             FROM CustInvoiceJour
             WHERE IsActive = "1" AND InvoiceDate >= ? AND InvoiceDate < ?',
            [$fromDate, $toDateExclusive]
        );

        $taxJournal = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate < ?
               AND (c.CategoryCode IN ("TAX", "DEDUCTION") OR UPPER(t.LedgerCategory) IN ("TAX", "DEDUCTION", "IMPOSTO", "IMPOSTOS"))',
            [$fromDate, $toDateExclusive]
        );

        $serviceCost = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM PurchTable
             WHERE IsActive = "1" AND PurchType = "S" AND PurchDate >= ? AND PurchDate < ?',
            [$fromDate, $toDateExclusive]
        );

        $materialCost = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM PurchTable
             WHERE IsActive = "1" AND PurchType = "I" AND PurchDate >= ? AND PurchDate < ?',
            [$fromDate, $toDateExclusive]
        );

        $operatingExpenses = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate < ?
               AND (c.CategoryCode IN ("OPERATING", "BANK_FEE", "IOF", "MISC_EXPENSE")
                    OR (t.LedgerCategoryId IS NULL AND UPPER(t.LedgerCategory) IN ("OPERATING", "BANK_FEE", "IOF", "MISC_EXPENSE", "OPERACIONAL")))',
            [$fromDate, $toDateExclusive]
        );

        $profitWithdrawal = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN LedgerCategoryTable c ON t.LedgerCategoryId = c.RecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate < ?
               AND (c.CategoryCode = "PROFIT_WITHDRAWAL"
                    OR (t.LedgerCategoryId IS NULL AND UPPER(t.LedgerCategory) IN ("PROFIT_WITHDRAWAL", "RETIRADA_LUCRO")))',
            [$fromDate, $toDateExclusive]
        );

        $grossRevenue = (float) $invoiceRevenue['Amount'] + (float) $otherReceipts['Amount'];
        $totalTaxes = (float) $invoiceRevenue['TaxAmount'] + (float) $invoiceRevenue['DeductionAmount'] + (float) $taxJournal['Amount'];
        $totalCosts = (float) $serviceCost['Amount'] + (float) $materialCost['Amount'];
        $operExpenses = (float) $operatingExpenses['Amount'];
        $withdrawals = (float) $profitWithdrawal['Amount'];

        return $this->normalizeAmount($grossRevenue - $totalTaxes - $totalCosts - $operExpenses - $withdrawals);
    }

    /**
     * Relatório de Impostos por Período
     */
    public function taxesByPeriod($filters)
    {
        $conditions = ['t.IsActive = "1"', 'j.Posted = "1"', 't.AmountCurDebit > 0'];
        $params = [];

        if (!empty($filters['FromDate'])) {
            $conditions[] = 't.TransDate >= ?';
            $params[] = $this->dateTimeOrNow($filters['FromDate']);
        }

        if (!empty($filters['ToDate'])) {
            $conditions[] = 't.TransDate <= ?';
            $params[] = $this->dateTimeOrNow($filters['ToDate']);
        }

        if (!empty($filters['TaxTypeId'])) {
            $conditions[] = 't.TaxTypeId = ?';
            $params[] = (int) $filters['TaxTypeId'];
        }

        $rows = $this->db->fetchAll(
            'SELECT DATE_FORMAT(t.TransDate, "%Y-%m-%d") AS TransDate,
                    t.Voucher, t.Description, 
                    IFNULL(tt.Name, t.LedgerCategory) AS TaxType,
                    t.AmountCurDebit AS Amount, t.PeriodMonth
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             LEFT JOIN TaxTypeTable tt ON tt.RecId = t.TaxTypeId
             WHERE ' . implode(' AND ', $conditions) . '
               AND UPPER(t.LedgerCategory) IN ("TAX", "DEDUCTION", "IMPOSTO", "IMPOSTOS")
             ORDER BY t.TransDate ASC',
            $params
        );

        return $this->withGrandTotal($rows, 'Amount');
    }

    /**
     * Relatório de Cash Flow (Fluxo de Caixa)
     */
    public function cashFlow($filters)
    {
        $fromDate = !empty($filters['FromDate']) ? $this->dateTimeOrNow($filters['FromDate']) : '2000-01-01 00:00:00';
        $toDate = !empty($filters['ToDate']) ? $this->dateTimeOrNow($filters['ToDate']) : '2099-12-31 23:59:59';
        $opening = $this->resolveOpeningBalanceContext($fromDate, $toDate);

        // Recebimentos (faturas pagas)
        $receipts = $this->db->fetchAll(
            'SELECT DATE_FORMAT(t.TransDate, "%Y-%m") AS Month, SUM(t.AmountCurCredit) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
                         WHERE t.IsActive = "1" AND j.Posted = "1" AND j.JournalType = "REC"
               AND t.TransDate >= ? AND t.TransDate <= ?
               AND t.AmountCurCredit > 0
             GROUP BY DATE_FORMAT(t.TransDate, "%Y-%m")',
            [$fromDate, $toDate]
        );

        // Pagamentos (compras pagas)
        $payments = $this->db->fetchAll(
            'SELECT DATE_FORMAT(t.TransDate, "%Y-%m") AS Month, SUM(t.AmountCurDebit) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             WHERE t.IsActive = "1" AND j.Posted = "1" AND j.JournalType = "PAY"
               AND t.TransDate >= ? AND t.TransDate <= ?
               AND t.AmountCurDebit > 0
             GROUP BY DATE_FORMAT(t.TransDate, "%Y-%m")',
            [$fromDate, $toDate]
        );

        // Consolidar
        $months = [];
        foreach ($receipts as $row) {
            $m = $row['Month'];
            if (!isset($months[$m])) {
                $months[$m] = ['Month' => $m, 'Receipts' => 0, 'Payments' => 0];
            }
            $months[$m]['Receipts'] += (float) $row['Amount'];
        }
        foreach ($payments as $row) {
            $m = $row['Month'];
            if (!isset($months[$m])) {
                $months[$m] = ['Month' => $m, 'Receipts' => 0, 'Payments' => 0];
            }
            $months[$m]['Payments'] += (float) $row['Amount'];
        }

        ksort($months);
        $rows = [];
        $runningBalance = $opening['baseBalance'];
        foreach ($months as $data) {
            $data['NetCashFlow'] = $data['Receipts'] - $data['Payments'];
            $runningBalance += $data['NetCashFlow'];
            $data['RunningBalance'] = $runningBalance;
            $rows[] = $data;
        }

        if (empty($rows)) {
            $rows[] = [
                'Month' => substr($fromDate, 0, 7),
                'Receipts' => 0,
                'Payments' => 0,
                'NetCashFlow' => 0,
                'RunningBalance' => $runningBalance
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'Receipts' => $this->sumField($rows, 'Receipts'),
                'Payments' => $this->sumField($rows, 'Payments'),
                'NetCashFlow' => $this->sumField($rows, 'NetCashFlow'),
                'OpeningBalance' => $opening['baseBalance'],
                'RunningBalance' => $runningBalance
            ]
        ];
    }

    private function resolveOpeningBalanceContext($fromDate, $toDate)
    {
        $company = $this->db->fetchOne(
            'SELECT InitialBalance, InitialBalanceDate
             FROM CompanyInfo
             WHERE IsActive = "1"
             ORDER BY IsDefault DESC, RecId ASC
             LIMIT 1'
        );

        $openingValue = $company && $company['InitialBalance'] !== null ? (float) $company['InitialBalance'] : 0.0;
        $openingDate = ($company && !empty($company['InitialBalanceDate'])) ? ($company['InitialBalanceDate'] . ' 00:00:00') : null;

        // If opening date is outside the report horizon, ignore it.
        if (!$openingDate || $openingDate > $toDate) {
            return ['baseBalance' => 0.0];
        }

        if ($openingDate >= $fromDate) {
            return ['baseBalance' => $openingValue];
        }

        // Add net movement between opening date and period start to keep running balance consistent.
        $beforeFromReceipts = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurCredit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             WHERE t.IsActive = "1" AND j.Posted = "1" AND t.TransDate >= ? AND t.TransDate < ?
               AND t.AmountCurCredit > 0',
            [$openingDate, $fromDate]
        );

        $beforeFromPayments = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS Amount
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             WHERE t.IsActive = "1" AND j.Posted = "1" AND t.TransDate >= ? AND t.TransDate < ?
               AND t.AmountCurDebit > 0',
            [$openingDate, $fromDate]
        );

        $baseBalance = $openingValue + (float) $beforeFromReceipts['Amount'] - (float) $beforeFromPayments['Amount'];
        return ['baseBalance' => $baseBalance];
    }

    private function withGrandTotal($rows, $fieldName)
    {
        return [
            'rows' => $rows,
            'totals' => [
                $fieldName => $this->sumField($rows, $fieldName)
            ]
        ];
    }

    private function sumField($rows, $fieldName)
    {
        $total = 0;
        foreach ($rows as $row) {
            $total += isset($row[$fieldName]) ? (float) $row[$fieldName] : 0;
        }
        return $total;
    }

    private function resolvePeriod($filters)
    {
        if (!empty($filters['PeriodMonth'])) {
            $month = trim($filters['PeriodMonth']);
            $year = (int) substr($month, 0, 4);
            $mon = (int) substr($month, 5, 2);
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $mon, $year);
            return [
                $month . '-01 00:00:00',
                $month . '-' . $lastDay . ' 23:59:59'
            ];
        }

        // Se tem período anual
        if (!empty($filters['Year'])) {
            $year = (int) trim($filters['Year']);
            return [
                $year . '-01-01 00:00:00',
                $year . '-12-31 23:59:59'
            ];
        }

        $fromDate = !empty($filters['FromDate']) ? $this->dateTimeOrNow($filters['FromDate']) : date('Y') . '-01-01 00:00:00';
        $toDate = !empty($filters['ToDate']) ? $this->dateTimeOrNow($filters['ToDate']) : date('Y') . '-12-31 23:59:59';

        return [$fromDate, $toDate];
    }
}