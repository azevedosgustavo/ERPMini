<?php

class NumberSequenceController extends BaseController
{
    private $numberSequenceModel;

    public function __construct()
    {
        $this->numberSequenceModel = new SysNumberSequenceModel();
    }

    public function index()
    {
        try {
            $this->requireAdmin();
            $this->success($this->numberSequenceModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAdmin();
            $recId = $this->numberSequenceModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recId], 'Number sequence created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAdmin();
            $this->numberSequenceModel->update($id, $this->getJsonInput());
            $this->success([], 'Number sequence updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAdmin();
            $this->numberSequenceModel->delete($id);
            $this->success([], 'Number sequence deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}
