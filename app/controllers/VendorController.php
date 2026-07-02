<?php

class VendorController extends BaseController
{
    private $vendorModel;

    public function __construct()
    {
        $this->vendorModel = new VendTableModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->vendorModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $this->requireAuth();
            $record = $this->vendorModel->findById($id);

            if (!$record) {
                $this->failure('Vendor not found.', 404);
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
            $recordId = $this->vendorModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recordId], 'Vendor created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->vendorModel->update($id, $this->getJsonInput(), $this->currentUserId());
            $this->success([], 'Vendor updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->vendorModel->delete($id);
            $this->success([], 'Vendor deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}