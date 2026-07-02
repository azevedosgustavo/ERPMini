<?php

require_once __DIR__ . '/../app/bootstrap.php';

try {
    $connection = Connection::getInstance();
    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    $statements = explode(';', $schema);

    foreach ($statements as $statement) {
        $trimmed = trim($statement);

        if ($trimmed === '') {
            continue;
        }

        if (!mysqli_query($connection, $trimmed)) {
            throw new Exception('Schema execution failed: ' . mysqli_error($connection));
        }
    }

    echo "Schema executed successfully." . PHP_EOL;
} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}