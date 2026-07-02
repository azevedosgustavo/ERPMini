<?php

class ReportController extends BaseController
{
    private $reportModel;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
    }

    public function accountsReceivable()
    {
        $this->handleReport('accountsReceivable');
    }

    public function accountsPayable()
    {
        $this->handleReport('accountsPayable');
    }

    public function billingByPeriod()
    {
        $this->handleReport('billingByPeriod');
    }

    public function billingByCustomer()
    {
        $this->handleReport('billingByCustomer');
    }

    public function expensesByPeriod()
    {
        $this->handleReport('expensesByPeriod');
    }

    public function financialSummary()
    {
        $this->handleReport('financialSummary');
    }

    public function profitAndLoss()
    {
        $this->handleReport('profitAndLoss');
    }

    public function taxesByPeriod()
    {
        $this->handleReport('taxesByPeriod');
    }

    public function cashFlow()
    {
        $this->handleReport('cashFlow');
    }

    private function handleReport($methodName)
    {
        try {
            $this->requireAuth();
            $this->success($this->reportModel->$methodName($_GET));
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}