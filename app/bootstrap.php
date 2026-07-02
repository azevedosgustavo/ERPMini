<?php

date_default_timezone_set('America/Sao_Paulo');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$GLOBALS['app_config'] = require __DIR__ . '/config/config.php';

spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/core/',
        __DIR__ . '/models/',
        __DIR__ . '/controllers/'
    ];

    foreach ($directories as $directory) {
        $filePath = $directory . $className . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }
});