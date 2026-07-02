<?php

class ProductController extends BaseController
{
    private $productModel;

    public function __construct()
    {
        $this->productModel = new InventTableModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->productModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $recordId = $this->productModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recordId], 'Product created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->productModel->update($id, $this->getJsonInput());
            $this->success([], 'Product updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->productModel->delete($id);
            $this->success([], 'Product deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}