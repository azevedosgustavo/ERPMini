<?php

class BaseController
{
    protected function jsonResponse($payload, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    protected function success($data = [], $message = 'OK')
    {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function failure($message, $statusCode = 400)
    {
        $this->jsonResponse([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    protected function getJsonInput()
    {
        $rawBody = file_get_contents('php://input');

        if (!$rawBody) {
            return [];
        }

        $decoded = json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function requireAuth()
    {
        if (!Auth::check()) {
            $this->failure('Authentication required.', 401);
            exit;
        }
    }

    protected function requireAdmin()
    {
        $this->requireAuth();

        if (!Auth::isAdmin()) {
            $this->failure('Administrator access required.', 403);
            exit;
        }
    }

    protected function currentUserId()
    {
        $user = Auth::user();
        return $user ? $user['UserId'] : 'SYSTEM';
    }
}