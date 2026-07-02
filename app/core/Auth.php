<?php

class Auth
{
    private static function config($key, $default = null)
    {
        return isset($GLOBALS['app_config'][$key]) ? $GLOBALS['app_config'][$key] : $default;
    }

    private static function base64UrlEncode($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($value)
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value, true);
    }

    private static function tokenSecret()
    {
        return (string) self::config('oauthSecret', self::config('fixedSalt', 'CASPTI_MINIERP_SECRET'));
    }

    private static function accessTokenTtl()
    {
        return (int) self::config('oauthAccessTokenTtl', 3600);
    }

    private static function refreshTokenTtl()
    {
        return (int) self::config('oauthRefreshTokenTtl', 1209600);
    }

    private static function sign($header, $payload)
    {
        return self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, self::tokenSecret(), true));
    }

    private static function encodeToken($claims)
    {
        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::base64UrlEncode(json_encode($claims));
        $signature = self::sign($header, $payload);

        return $header . '.' . $payload . '.' . $signature;
    }

    private static function decodeToken($token)
    {
        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        $expected = self::sign($headerEncoded, $payloadEncoded);

        if (!hash_equals($expected, $signatureEncoded)) {
            return null;
        }

        $decodedPayload = self::base64UrlDecode($payloadEncoded);

        if ($decodedPayload === false) {
            return null;
        }

        $claims = json_decode($decodedPayload, true);

        if (!is_array($claims)) {
            return null;
        }

        if (!isset($claims['exp']) || (int) $claims['exp'] < time()) {
            return null;
        }

        return $claims;
    }

    private static function buildUserPayload($user)
    {
        return [
            'RecId' => (int) $user['RecId'],
            'UserId' => (string) $user['UserId'],
            'UserName' => (string) $user['UserName'],
            'Email' => (string) $user['Email'],
            'RoleId' => (int) $user['RoleId'],
            'RoleCode' => (string) $user['RoleCode'],
            'RoleName' => (string) $user['RoleName'],
            'LanguageId' => (string) (isset($user['LanguageId']) && $user['LanguageId'] !== '' ? $user['LanguageId'] : 'PT-BR')
        ];
    }

    private static function claimsToUser($claims)
    {
        $requiredKeys = ['RecId', 'UserId', 'UserName', 'Email', 'RoleId', 'RoleCode', 'RoleName', 'LanguageId'];

        foreach ($requiredKeys as $key) {
            if (!isset($claims[$key])) {
                return null;
            }
        }

        return [
            'RecId' => (int) $claims['RecId'],
            'UserId' => (string) $claims['UserId'],
            'UserName' => (string) $claims['UserName'],
            'Email' => (string) $claims['Email'],
            'RoleId' => (int) $claims['RoleId'],
            'RoleCode' => (string) $claims['RoleCode'],
            'RoleName' => (string) $claims['RoleName'],
            'LanguageId' => (string) $claims['LanguageId']
        ];
    }

    public static function issueTokens($user)
    {
        $now = time();
        $baseClaims = self::buildUserPayload($user);

        $accessClaims = $baseClaims;
        $accessClaims['token_type'] = 'access';
        $accessClaims['iat'] = $now;
        $accessClaims['exp'] = $now + self::accessTokenTtl();

        $refreshClaims = $baseClaims;
        $refreshClaims['token_type'] = 'refresh';
        $refreshClaims['iat'] = $now;
        $refreshClaims['exp'] = $now + self::refreshTokenTtl();

        return [
            'access_token' => self::encodeToken($accessClaims),
            'token_type' => 'Bearer',
            'expires_in' => self::accessTokenTtl(),
            'refresh_token' => self::encodeToken($refreshClaims),
            'refresh_expires_in' => self::refreshTokenTtl()
        ];
    }

    public static function refreshTokens($refreshToken)
    {
        $claims = self::decodeToken($refreshToken);

        if (!$claims || !isset($claims['token_type']) || $claims['token_type'] !== 'refresh') {
            return null;
        }

        $user = self::claimsToUser($claims);

        if (!$user) {
            return null;
        }

        return self::issueTokens($user);
    }

    public static function bearerToken()
    {
        $header = '';

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['Authorization'])) {
            $header = $_SERVER['Authorization'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $header = $headers['Authorization'];
            }
        }

        if (!is_string($header) || stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        return trim(substr($header, 7));
    }

    public static function user()
    {
        $token = self::bearerToken();

        if (!$token) {
            return null;
        }

        $claims = self::decodeToken($token);

        if (!$claims || !isset($claims['token_type']) || $claims['token_type'] !== 'access') {
            return null;
        }

        return self::claimsToUser($claims);
    }

    public static function check()
    {
        return self::user() !== null;
    }

    public static function login($user)
    {
        return self::issueTokens($user);
    }

    public static function logout()
    {
        return true;
    }

    public static function isAdmin()
    {
        $user = self::user();
        return $user && isset($user['RoleCode']) && $user['RoleCode'] === 'ADMIN';
    }
}