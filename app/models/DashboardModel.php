<?php

class DashboardModel extends BaseModel
{
    public function summary()
    {
        $yearStart = date('Y-01-01 00:00:00');
        $yearEnd = date('Y-12-31 23:59:59');

        // Movimentação do ano corrente.
        // Apenas invoices NÃO lançadas em journals (para evitar dupla contagem com manualRevenue)
        $billing = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS TotalBilling
             FROM CustInvoiceJour
             WHERE IsActive = "1" AND InvoiceDate >= ? AND InvoiceDate <= ?
               AND RecId NOT IN (
                   SELECT DISTINCT ServiceInvoiceRecId
                   FROM LedgerJournalTrans
                   WHERE IsActive = "1" AND ServiceInvoiceRecId IS NOT NULL
               )',
            [$yearStart, $yearEnd]
        );

        $expenses = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS TotalExpenses
             FROM PurchTable
             WHERE IsActive = "1" AND PurchDate >= ? AND PurchDate <= ?',
            [$yearStart, $yearEnd]
        );

        $manualExpenses = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurDebit), 0) AS TotalManualExpenses
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate <= ?',
            [$yearStart, $yearEnd]
        );

        $manualRevenue = $this->db->fetchOne(
            'SELECT IFNULL(SUM(t.AmountCurCredit), 0) AS TotalManualRevenue
             FROM LedgerJournalTrans t
             INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
             WHERE t.IsActive = "1" AND j.Posted = "1"
               AND t.TransDate >= ? AND t.TransDate <= ?',
            [$yearStart, $yearEnd]
        );

        // Saldo inicial (carry-forward) = aberto em anos anteriores.
        // Exclui invoices já lançadas em journals
        $openingReceivable = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM CustInvoiceJour
             WHERE IsActive = "1" AND Status = "O" AND InvoiceDate < ?
               AND RecId NOT IN (
                   SELECT DISTINCT ServiceInvoiceRecId
                   FROM LedgerJournalTrans
                   WHERE IsActive = "1" AND ServiceInvoiceRecId IS NOT NULL
               )',
            [$yearStart]
        );

        $openingPayable = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM PurchTable
             WHERE IsActive = "1" AND Status = "O" AND PurchDate < ?',
            [$yearStart]
        );

        // Aberto do ano corrente.
        // Exclui invoices já lançadas em journals
        $currentOpenReceivable = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM CustInvoiceJour
             WHERE IsActive = "1" AND Status = "O" AND InvoiceDate >= ? AND InvoiceDate <= ?
               AND RecId NOT IN (
                   SELECT DISTINCT ServiceInvoiceRecId
                   FROM LedgerJournalTrans
                   WHERE IsActive = "1" AND ServiceInvoiceRecId IS NOT NULL
               )',
            [$yearStart, $yearEnd]
        );

        $currentOpenPayable = $this->db->fetchOne(
            'SELECT IFNULL(SUM(TotalAmount), 0) AS Amount
             FROM PurchTable
             WHERE IsActive = "1" AND Status = "O" AND PurchDate >= ? AND PurchDate <= ?',
            [$yearStart, $yearEnd]
        );

        $totalBilling = (float) $billing['TotalBilling'] + (float) $manualRevenue['TotalManualRevenue'];
        $totalExpenses = (float) $expenses['TotalExpenses'] + (float) $manualExpenses['TotalManualExpenses'];
        $openReceivable = (float) $openingReceivable['Amount'] + (float) $currentOpenReceivable['Amount'];
        $openPayable = (float) $openingPayable['Amount'] + (float) $currentOpenPayable['Amount'];

        // Resultado do ano corrente + saldo inicial (a receber - a pagar) do ano anterior.
        $openingNet = (float) $openingReceivable['Amount'] - (float) $openingPayable['Amount'];
        $netBalance = $openingNet + ($totalBilling - $totalExpenses);

        return [
            'Year' => date('Y'),
            'TotalBilling' => $totalBilling,
            'TotalExpenses' => $totalExpenses,
            'OpenReceivable' => $openReceivable,
            'OpenPayable' => $openPayable,
            'OpeningReceivable' => (float) $openingReceivable['Amount'],
            'OpeningPayable' => (float) $openingPayable['Amount'],
            'NetBalance' => $netBalance
        ];
    }
}