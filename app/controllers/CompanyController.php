<?php

class CompanyController extends BaseController
{
    private $companyModel;

    public function __construct()
    {
        $this->companyModel = new CompanyModel();
    }

    public function index()
    {
        try {
            $this->requireAdmin();
            $this->success($this->companyModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAdmin();
            $recId = $this->companyModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recId], 'Company created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAdmin();
            $this->companyModel->update($id, $this->getJsonInput(), $this->currentUserId());
            $this->success([], 'Company updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAdmin();
            $this->companyModel->delete($id);
            $this->success([], 'Company removed successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}
