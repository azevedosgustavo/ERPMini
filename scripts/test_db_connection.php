<?php

require_once __DIR__ . '/../app/bootstrap.php';

$startedAt = microtime(true);

try {
    $connection = Connection::getInstance();
    $elapsedMs = (int) ((microtime(true) - $startedAt) * 1000);
    $hostInfo = mysqli_get_host_info($connection);
    $serverInfo = mysqli_get_server_info($connection);

    echo "DATABASE CONNECTION: SUCCESS" . PHP_EOL;
    echo "Host: " . $GLOBALS['app_config']['db']['host'] . ':' . $GLOBALS['app_config']['db']['port'] . PHP_EOL;
    echo "Database: " . $GLOBALS['app_config']['db']['database'] . PHP_EOL;
    echo "User: " . $GLOBALS['app_config']['db']['username'] . PHP_EOL;
    echo "HostInfo: " . $hostInfo . PHP_EOL;
    echo "ServerVersion: " . $serverInfo . PHP_EOL;
    echo "ElapsedMs: " . $elapsedMs . PHP_EOL;
    exit(0);
} catch (Exception $exception) {
    $elapsedMs = (int) ((microtime(true) - $startedAt) * 1000);

    echo "DATABASE CONNECTION: FAILED" . PHP_EOL;
    echo "Host: " . $GLOBALS['app_config']['db']['host'] . ':' . $GLOBALS['app_config']['db']['port'] . PHP_EOL;
    echo "Database: " . $GLOBALS['app_config']['db']['database'] . PHP_EOL;
    echo "User: " . $GLOBALS['app_config']['db']['username'] . PHP_EOL;
    echo "Error: " . $exception->getMessage() . PHP_EOL;
    echo "ElapsedMs: " . $elapsedMs . PHP_EOL;
    exit(1);
}
