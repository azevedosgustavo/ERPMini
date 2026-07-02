<?php

class TaxTypeController extends BaseController
{
    private $taxTypeModel;

    public function __construct()
    {
        $this->taxTypeModel = new TaxTypeModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->taxTypeModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $recId = $this->taxTypeModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recId], 'Tax type created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->taxTypeModel->update($id, $this->getJsonInput());
            $this->success([], 'Tax type updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->taxTypeModel->delete($id);
            $this->success([], 'Tax type removed successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}
