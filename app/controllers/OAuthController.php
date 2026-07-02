<?php

class OAuthController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new SysUserInfoModel();
    }

    public function token()
    {
        try {
            $payload = $this->getJsonInput();
            $grantType = isset($payload['grant_type']) ? trim((string) $payload['grant_type']) : '';

            if ($grantType === 'password_secure') {
                $this->passwordSecureGrant($payload);
                return;
            }

            if ($grantType === 'refresh_token') {
                $this->refreshGrant($payload);
                return;
            }

            $this->failure('Unsupported grant_type.', 400);
        } catch (Exception $exception) {
            $this->failure($exception->getMessage(), 500);
        }
    }

    private function transportKeyBinary()
    {
        $transportKey = isset($GLOBALS['app_config']['oauthCredentialKey'])
            ? (string) $GLOBALS['app_config']['oauthCredentialKey']
            : '';

        if ($transportKey === '') {
            throw new Exception('Credential transport key is not configured.');
        }

        // Shared key is reduced to a fixed 32-byte AES key through SHA-256.
        return hash('sha256', $transportKey, true);
    }

    private function decryptSecureField($cipherBase64, $ivBase64)
    {
        $cipherRaw = base64_decode((string) $cipherBase64, true);
        $ivRaw = base64_decode((string) $ivBase64, true);

        if ($cipherRaw === false || $ivRaw === false || strlen($ivRaw) !== 16) {
            return null;
        }

        $plainText = openssl_decrypt(
            $cipherRaw,
            'aes-256-cbc',
            $this->transportKeyBinary(),
            OPENSSL_RAW_DATA,
            $ivRaw
        );

        return $plainText === false ? null : $plainText;
    }

    private function authenticatePasswordUser($username, $password)
    {
        $username = trim((string) $username);
        $password = (string) $password;

        if ($username === '' || $password === '') {
            $this->failure('Invalid secure credentials payload.', 422);
            return;
        }

        $user = $this->userModel->findByEmail($username);

        if (!$user) {
            $this->failure('Invalid credentials.', 401);
            return;
        }

        if ($user['IsActive'] !== '1' || $user['IsBlocked'] === '1') {
            $this->failure('User is blocked or inactive.', 403);
            return;
        }

        $expectedHash = md5($GLOBALS['app_config']['fixedSalt'] . $password);

        if ($expectedHash !== $user['PasswordHash']) {
            $this->failure('Invalid credentials.', 401);
            return;
        }

        $authUser = [
            'RecId' => $user['RecId'],
            'UserId' => $user['UserId'],
            'UserName' => $user['UserName'],
            'Email' => $user['Email'],
            'RoleId' => $user['RoleId'],
            'RoleCode' => $user['RoleCode'],
            'RoleName' => $user['RoleName'],
            'LanguageId' => $user['LanguageId']
        ];

        $this->success(Auth::issueTokens($authUser), 'Token issued.');
    }

    private function passwordSecureGrant($payload)
    {
        $usernameCipher = isset($payload['username_cipher']) ? $payload['username_cipher'] : '';
        $usernameIv = isset($payload['username_iv']) ? $payload['username_iv'] : '';
        $passwordCipher = isset($payload['password_cipher']) ? $payload['password_cipher'] : '';
        $passwordIv = isset($payload['password_iv']) ? $payload['password_iv'] : '';

        if (
            trim((string) $usernameCipher) === '' ||
            trim((string) $usernameIv) === '' ||
            trim((string) $passwordCipher) === '' ||
            trim((string) $passwordIv) === ''
        ) {
            $this->failure('username_cipher, username_iv, password_cipher and password_iv are required.', 422);
            return;
        }

        $username = $this->decryptSecureField($usernameCipher, $usernameIv);
        $password = $this->decryptSecureField($passwordCipher, $passwordIv);

        if ($username === null || $password === null) {
            $this->failure('Invalid secure credentials.', 401);
            return;
        }

        $this->authenticatePasswordUser($username, $password);
    }

    private function refreshGrant($payload)
    {
        $refreshToken = isset($payload['refresh_token']) ? trim((string) $payload['refresh_token']) : '';

        if ($refreshToken === '') {
            $this->failure('refresh_token is required.', 422);
            return;
        }

        $tokens = Auth::refreshTokens($refreshToken);

        if (!$tokens) {
            $this->failure('Invalid refresh_token.', 401);
            return;
        }

        $this->success($tokens, 'Token refreshed.');
    }
}
