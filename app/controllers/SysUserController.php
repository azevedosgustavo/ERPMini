<?php

class SysUserController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new SysUserInfoModel();
    }

    public function index()
    {
        try {
            $this->requireAdmin();
            $this->success($this->userModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAdmin();
            $userId = $this->userModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $userId], 'User created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAdmin();
            $this->userModel->update($id, $this->getJsonInput());
            $this->success([], 'User updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAdmin();
            $this->userModel->delete($id);
            $this->success([], 'User deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}