<?php

class AuthController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new SysUserInfoModel();
    }

    public function login()
    {
        $this->failure('Use /api/oauth/token with OAuth2 grant_type=password_secure.', 400);
    }

    public function logout()
    {
        // OAuth2 logout is handled client-side by removing bearer tokens.
        Auth::logout();
        $this->success([], 'Logout successful.');
    }

    public function me()
    {
        if (!Auth::check()) {
            $this->failure('Not authenticated.', 401);
            return;
        }

        $this->success(Auth::user());
    }
}