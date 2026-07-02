<?php

require_once __DIR__ . '/../app/bootstrap.php';

try {
    $connection = Connection::getInstance();
    $database = $GLOBALS['app_config']['db']['database'];

    $statement = mysqli_prepare(
        $connection,
        'SELECT COUNT(*) AS Qty FROM information_schema.tables WHERE table_schema = ?'
    );

    mysqli_stmt_bind_param($statement, 's', $database);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $quantity);
    mysqli_stmt_fetch($statement);
    mysqli_stmt_close($statement);

    echo 'TABLE_COUNT=' . (int) $quantity . PHP_EOL;
    exit(0);
} catch (Exception $exception) {
    echo 'TABLE_COUNT_CHECK_FAILED=' . $exception->getMessage() . PHP_EOL;
    exit(1);
}
