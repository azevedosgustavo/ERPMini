<?php

class PurchaseOrderController extends BaseController
{
    private $purchaseModel;

    public function __construct()
    {
        $this->purchaseModel = new PurchTableModel();
    }

    public function index()
    {
        $this->handleIndex();
    }

    public function indexMaterials()
    {
        $this->handleIndex('I');
    }

    public function indexServices()
    {
        $this->handleIndex('S');
    }

    public function show($id)
    {
        $this->handleShow($id);
    }

    public function showMaterial($id)
    {
        $this->handleShow($id, 'I');
    }

    public function showService($id)
    {
        $this->handleShow($id, 'S');
    }

    public function store()
    {
        $this->handleStore();
    }

    public function storeMaterial()
    {
        $this->handleStore('I');
    }

    public function storeService()
    {
        $this->handleStore('S');
    }

    public function update($id)
    {
        $this->handleUpdate($id);
    }

    public function updateMaterial($id)
    {
        $this->handleUpdate($id, 'I');
    }

    public function updateService($id)
    {
        $this->handleUpdate($id, 'S');
    }

    public function destroy($id)
    {
        $this->handleDestroy($id);
    }

    public function destroyMaterial($id)
    {
        $this->handleDestroy($id, 'I');
    }

    public function destroyService($id)
    {
        $this->handleDestroy($id, 'S');
    }

    private function handleIndex($purchType = null)
    {
        try {
            $this->requireAuth();
            $this->success($this->purchaseModel->all($purchType));
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function handleShow($id, $purchType = null)
    {
        try {
            $this->requireAuth();
            $record = $this->purchaseModel->findById($id, $purchType);

            if (!$record) {
                $this->failure('Purchase order not found.', 404);
                return;
            }

            $this->success($record);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function handleStore($purchType = null)
    {
        try {
            $this->requireAuth();
            $recordId = $this->purchaseModel->create($this->getJsonInput(), $this->currentUserId(), $purchType);
            $this->success(['RecId' => $recordId], 'Purchase order created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function handleUpdate($id, $purchType = null)
    {
        try {
            $this->requireAuth();
            $this->purchaseModel->update($id, $this->getJsonInput(), $this->currentUserId(), $purchType);
            $this->success([], 'Purchase order updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function handleDestroy($id, $purchType = null)
    {
        try {
            $this->requireAuth();

            if ($purchType !== null && !$this->purchaseModel->findById($id, $purchType)) {
                $this->failure('Purchase order not found.', 404);
                return;
            }

            $this->purchaseModel->delete($id);
            $this->success([], 'Purchase order deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}