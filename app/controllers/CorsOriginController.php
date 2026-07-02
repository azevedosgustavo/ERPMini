<?php

class CorsOriginController extends BaseController
{
    private $corsOriginModel;

    public function __construct()
    {
        $this->corsOriginModel = new CorsOriginModel();
    }

    public function index()
    {
        try {
            $this->requireAdmin();
            $this->success($this->corsOriginModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAdmin();
            $payload = $this->getJsonInput();

            if (empty(trim($payload['Origin'] ?? ''))) {
                $this->failure('Origin is required.', 422);
                return;
            }

            $recId = $this->corsOriginModel->create($payload, $this->currentUserId());
            $this->success(['RecId' => $recId], 'CORS origin created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAdmin();
            $payload = $this->getJsonInput();

            if (empty(trim($payload['Origin'] ?? ''))) {
                $this->failure('Origin is required.', 422);
                return;
            }

            $this->corsOriginModel->update($id, $payload);
            $this->success([], 'CORS origin updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAdmin();
            $this->corsOriginModel->delete($id);
            $this->success([], 'CORS origin removed successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}
