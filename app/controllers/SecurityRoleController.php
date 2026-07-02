<?php

class SecurityRoleController extends BaseController
{
    private $roleModel;

    public function __construct()
    {
        $this->roleModel = new SecurityRoleModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->roleModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAdmin();
            $payload = $this->getJsonInput();
            $roleId = $this->roleModel->create($payload, $this->currentUserId());
            $this->success(['RecId' => $roleId], 'Role created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAdmin();
            $this->roleModel->update($id, $this->getJsonInput());
            $this->success([], 'Role updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAdmin();
            $this->roleModel->delete($id);
            $this->success([], 'Role deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}