<?php

class Connection
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance instanceof mysqli) {
            return self::$instance;
        }

        $config = $GLOBALS['app_config']['db'];
        $connection = mysqli_init();

        if (!$connection) {
            throw new Exception('Database initialization failed.');
        }

        mysqli_options($connection, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        $connected = @mysqli_real_connect(
            $connection,
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            (int) $config['port']
        );

        if (!$connected) {
            throw new Exception('Database connection failed: ' . mysqli_connect_error());
        }

        mysqli_set_charset($connection, 'utf8');
        self::$instance = $connection;

        return self::$instance;
    }
}