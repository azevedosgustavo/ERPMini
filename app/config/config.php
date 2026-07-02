<?php

return [
    'appName' => 'CASPTI Mini ERP',
    'fixedSalt' => 'CASPTI_FIXED_SALT_2026',
    'oauthSecret' => getenv('MINIERP_OAUTH_SECRET') ?: 'CASPTI_OAUTH2_SECRET_CHANGE_ME',
    'oauthCredentialKey' => getenv('MINIERP_OAUTH_CREDENTIAL_KEY') ?: 'CASPTI_OAUTH_CREDENTIAL_KEY_CHANGE_ME',
    'oauthAccessTokenTtl' => (int) (getenv('MINIERP_OAUTH_ACCESS_TTL') ?: 3600),
    'oauthRefreshTokenTtl' => (int) (getenv('MINIERP_OAUTH_REFRESH_TTL') ?: 1209600),
    'db' => [
        'host' => getenv('MINIERP_DB_HOST') ?: 'erpminiprod.mysql.dbaas.com.br',
        'port' => getenv('MINIERP_DB_PORT') ?: '3306',
        'database' => getenv('MINIERP_DB_NAME') ?: 'erpminiprod',
        'username' => getenv('MINIERP_DB_USER') ?: 'erpminiprod',
        'password' => getenv('MINIERP_DB_PASSWORD') ?: 'asasa1@dfs5S'
    ]
];