<?php

class ServiceCodeController extends BaseController
{
    private $serviceCodeModel;

    public function __construct()
    {
        $this->serviceCodeModel = new ServiceCodeModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->serviceCodeModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $recordId = $this->serviceCodeModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recordId], 'Service code created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->serviceCodeModel->update($id, $this->getJsonInput());
            $this->success([], 'Service code updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->serviceCodeModel->delete($id);
            $this->success([], 'Service code deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}