<?php

class DocuAttachmentController extends BaseController
{
    private $attachmentModel;

    public function __construct()
    {
        $this->attachmentModel = new DocuAttachmentModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $entityName = isset($_GET['entity']) ? trim($_GET['entity']) : '';
            $recordRecId = isset($_GET['recordId']) ? (int) $_GET['recordId'] : 0;
            $lineEntity = isset($_GET['lineEntity']) ? trim($_GET['lineEntity']) : '';
            $lineId = isset($_GET['lineId']) ? (int) $_GET['lineId'] : 0;

            $this->success($this->attachmentModel->allByEntity($entityName, $recordRecId, $lineEntity, $lineId));
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $recId = $this->attachmentModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $recId], 'Attachment saved successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $this->requireAuth();
            $attachment = $this->attachmentModel->findById($id);

            if (!$attachment) {
                $this->failure('Attachment not found.', 404);
                return;
            }

            $this->success($attachment);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->attachmentModel->delete($id);
            $this->success([], 'Attachment removed successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}
