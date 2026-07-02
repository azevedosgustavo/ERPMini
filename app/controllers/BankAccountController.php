<?php

class BankAccountController extends BaseController
{
    private $bankAccountModel;

    public function __construct()
    {
        $this->bankAccountModel = new BankAccountModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->bankAccountModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $recId = $this->bankAccountModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recId], 'Bank account created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->bankAccountModel->update($id, $this->getJsonInput());
            $this->success([], 'Bank account updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->bankAccountModel->delete($id);
            $this->success([], 'Bank account removed successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}
