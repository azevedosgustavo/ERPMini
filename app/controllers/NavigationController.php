<?php

class NavigationController extends BaseController
{
    private $menuModel;

    public function __construct()
    {
        $this->menuModel = new SysMenuModel();
    }

    public function menu()
    {
        try {
            $this->requireAuth();
            $user = Auth::user();
            $languageId = isset($user['LanguageId']) && $user['LanguageId'] !== '' ? $user['LanguageId'] : 'PT-BR';
            $roleCode = isset($user['RoleCode']) ? $user['RoleCode'] : null;
            $this->success($this->menuModel->getMenuTree($languageId, $roleCode));
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }
}