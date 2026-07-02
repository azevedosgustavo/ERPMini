<?php

class LocalizationController extends BaseController
{
    private $languageModel;
    private $labelModel;

    public function __construct()
    {
        $this->languageModel = new SysLanguageModel();
        $this->labelModel = new SysLabelTextModel();
    }

    public function languages()
    {
        try {
            $this->success($this->languageModel->allActive());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function labels()
    {
        try {
            $languageId = isset($_GET['languageId']) && trim($_GET['languageId']) !== ''
                ? trim($_GET['languageId'])
                : 'PT-BR';
            $this->success($this->labelModel->labelsByLanguage($languageId));
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function indexLabelTexts()
    {
        try {
            $this->requireAdmin();
            $this->success($this->labelModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function storeLabelText()
    {
        try {
            $this->requireAdmin();
            $recId = $this->labelModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recId], 'Label created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function updateLabelText($id)
    {
        try {
            $this->requireAdmin();
            $this->labelModel->update($id, $this->getJsonInput());
            $this->success([], 'Label updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroyLabelText($id)
    {
        try {
            $this->requireAdmin();
            $this->labelModel->delete($id);
            $this->success([], 'Label removed successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}