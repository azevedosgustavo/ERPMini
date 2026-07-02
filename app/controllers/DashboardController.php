<?php

class DashboardController extends BaseController
{
    private $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    public function summary()
    {
        try {
            $this->requireAuth();
            $this->success($this->dashboardModel->summary());
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}