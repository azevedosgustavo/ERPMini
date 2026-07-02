<?php

class ServiceInvoiceController extends BaseController
{
    private $invoiceModel;

    public function __construct()
    {
        $this->invoiceModel = new CustInvoiceModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->invoiceModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $this->requireAuth();
            $record = $this->invoiceModel->findById($id);

            if (!$record) {
                $this->failure('Invoice not found.', 404);
                return;
            }

            $this->success($record);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function openForJournal()
    {
        try {
            $this->requireAuth();
            $this->success($this->invoiceModel->allOpenForJournal());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $recordId = $this->invoiceModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recordId], 'Service invoice created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->invoiceModel->update($id, $this->getJsonInput(), $this->currentUserId());
            $this->success([], 'Service invoice updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->invoiceModel->delete($id);
            $this->success([], 'Service invoice deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}