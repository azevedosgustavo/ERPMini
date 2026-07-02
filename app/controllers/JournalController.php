<?php

class JournalController extends BaseController
{
    private $journalModel;

    public function __construct()
    {
        $this->journalModel = new LedgerJournalModel();
    }

    public function index()
    {
        $this->indexByType('GEN');
    }

    public function indexTax()
    {
        $this->indexByType('TAX');
    }

    public function indexPayment()
    {
        $this->indexByType('PAY');
    }

    public function indexReceipt()
    {
        $this->indexByType('REC');
    }

    public function show($id)
    {
        $this->showByType($id, 'GEN');
    }

    public function showTax($id)
    {
        $this->showByType($id, 'TAX');
    }

    public function showPayment($id)
    {
        $this->showByType($id, 'PAY');
    }

    public function showReceipt($id)
    {
        $this->showByType($id, 'REC');
    }

    public function store()
    {
        $this->storeByType('GEN');
    }

    public function storeTax()
    {
        $this->storeByType('TAX');
    }

    public function storePayment()
    {
        $this->storeByType('PAY');
    }

    public function storeReceipt()
    {
        $this->storeByType('REC');
    }

    public function update($id)
    {
        $this->updateByType($id, 'GEN');
    }

    public function updateTax($id)
    {
        $this->updateByType($id, 'TAX');
    }

    public function updatePayment($id)
    {
        $this->updateByType($id, 'PAY');
    }

    public function updateReceipt($id)
    {
        $this->updateByType($id, 'REC');
    }

    public function destroy($id)
    {
        $this->destroyByType($id, 'GEN');
    }

    public function destroyTax($id)
    {
        $this->destroyByType($id, 'TAX');
    }

    public function destroyPayment($id)
    {
        $this->destroyByType($id, 'PAY');
    }

    public function destroyReceipt($id)
    {
        $this->destroyByType($id, 'REC');
    }

    private function indexByType($journalType)
    {
        try {
            $this->requireAuth();
            $this->success($this->journalModel->all($journalType));
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function showByType($id, $journalType)
    {
        try {
            $this->requireAuth();
            $record = $this->journalModel->findById($id, $journalType);

            if (!$record) {
                $this->failure('Journal not found.', 404);
                return;
            }

            $this->success($record);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function storeByType($journalType)
    {
        try {
            $this->requireAuth();
            $recordId = $this->journalModel->create($this->getJsonInput(), $this->currentUserId(), $journalType);
            $this->success(['RecId' => $recordId], 'Journal created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function updateByType($id, $journalType)
    {
        try {
            $this->requireAuth();
            $this->journalModel->update($id, $this->getJsonInput(), $this->currentUserId(), $journalType);
            $this->success([], 'Journal updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function destroyByType($id, $journalType)
    {
        try {
            $this->requireAuth();
            $this->journalModel->delete($id, $journalType);
            $this->success([], 'Journal deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}