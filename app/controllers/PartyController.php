<?php

class PartyController extends BaseController
{
    private $partyModel;

    public function __construct()
    {
        $this->partyModel = new DirPartyModel();
    }

    public function index()
    {
        try {
            $this->requireAuth();
            $this->success($this->partyModel->all());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $this->requireAuth();
            $party = $this->partyModel->findById($id);

            if (!$party) {
                $this->failure('Party not found.', 404);
                return;
            }

            $this->success($party);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $this->requireAuth();
            $partyId = $this->partyModel->create($this->getJsonInput(), $this->currentUserId());
            $this->success(['RecId' => $partyId], 'Party created successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $this->requireAuth();
            $this->partyModel->update($id, $this->getJsonInput(), $this->currentUserId());
            $this->success([], 'Party updated successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->requireAuth();
            $this->partyModel->delete($id);
            $this->success([], 'Party deleted successfully.');
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}