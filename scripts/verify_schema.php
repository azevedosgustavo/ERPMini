<?php

require_once __DIR__ . '/../app/bootstrap.php';

try {
    $connection = Connection::getInstance();
    $database = $GLOBALS['app_config']['db']['database'];

    $tables = [];
    $stmt = mysqli_prepare(
        $connection,
        'SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name'
    );
    mysqli_stmt_bind_param($stmt, 's', $database);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $tableName);

    while (mysqli_stmt_fetch($stmt)) {
        $tables[] = $tableName;
    }

    mysqli_stmt_close($stmt);

    $userCount = 0;
    $roleCount = 0;

    $userResult = mysqli_query($connection, 'SELECT COUNT(*) AS Qty FROM SysUserInfo');
    if ($userResult) {
        $row = mysqli_fetch_assoc($userResult);
        $userCount = (int) $row['Qty'];
    }

    $roleResult = mysqli_query($connection, 'SELECT COUNT(*) AS Qty FROM SecurityRole');
    if ($roleResult) {
        $row = mysqli_fetch_assoc($roleResult);
        $roleCount = (int) $row['Qty'];
    }

    echo 'SCHEMA_STATUS=OK' . PHP_EOL;
    echo 'TABLE_COUNT=' . count($tables) . PHP_EOL;
    echo 'TABLES=' . implode(',', $tables) . PHP_EOL;
    echo 'ROLE_COUNT=' . $roleCount . PHP_EOL;
    echo 'SYSUSER_COUNT=' . $userCount . PHP_EOL;
    exit(0);
} catch (Exception $exception) {
    echo 'SCHEMA_STATUS=FAILED' . PHP_EOL;
    echo 'ERROR=' . $exception->getMessage() . PHP_EOL;
    exit(1);
}
