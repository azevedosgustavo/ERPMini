<?php

class CustomerController extends BaseController
{
    private $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustTableModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->customerModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $this->requireAuth();
            $record = $this->customerModel->findById($id);

            if (!$record) {
                $this->failure('Customer not found.', 404);
                return;
            }

            $this->success($record);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $recordId = $this->customerModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recordId], 'Customer created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->customerModel->update($id, $this->getJsonInput(), $this->currentUserId());
            $this->success([], 'Customer updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->customerModel->delete($id);
            $this->success([], 'Customer deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}