<?php

class Database
{
    private $connection;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function beginTransaction()
    {
        mysqli_autocommit($this->connection, false);
    }

    public function commit()
    {
        mysqli_commit($this->connection);
        mysqli_autocommit($this->connection, true);
    }

    public function rollBack()
    {
        mysqli_rollback($this->connection);
        mysqli_autocommit($this->connection, true);
    }

    public function fetchAll($sql, $params = [])
    {
        $statement = $this->prepareAndExecute($sql, $params);
        $rows = $this->fetchRowsFromStatement($statement);

        mysqli_stmt_close($statement);
        return $rows;
    }

    public function fetchOne($sql, $params = [])
    {
        $statement = $this->prepareAndExecute($sql, $params);
        $rows = $this->fetchRowsFromStatement($statement);
        mysqli_stmt_close($statement);

        return !empty($rows) ? $rows[0] : null;
    }

    public function execute($sql, $params = [])
    {
        $statement = $this->prepareAndExecute($sql, $params);
        $affectedRows = mysqli_stmt_affected_rows($statement);
        mysqli_stmt_close($statement);

        return $affectedRows;
    }

    public function insert($sql, $params = [])
    {
        $statement = $this->prepareAndExecute($sql, $params);
        mysqli_stmt_close($statement);

        return mysqli_insert_id($this->connection);
    }

    private function prepareAndExecute($sql, $params)
    {
        $statement = mysqli_prepare($this->connection, $sql);

        if (!$statement) {
            throw new Exception('SQL prepare failed: ' . mysqli_error($this->connection));
        }

        if (!empty($params)) {
            $types = '';
            $values = [];

            foreach ($params as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }

                $values[] = $value;
            }

            $bindParameters = [$statement, $types];

            foreach ($values as $index => $value) {
                $bindParameters[] = &$values[$index];
            }

            call_user_func_array('mysqli_stmt_bind_param', $bindParameters);
        }

        if (!mysqli_stmt_execute($statement)) {
            $message = mysqli_stmt_error($statement);
            mysqli_stmt_close($statement);
            throw new Exception('SQL execution failed: ' . $message);
        }

        return $statement;
    }

    private function fetchRowsFromStatement($statement)
    {
        $metadata = mysqli_stmt_result_metadata($statement);
        $rows = [];

        if (!$metadata) {
            return $rows;
        }

        $fields = [];
        $row = [];
        $bindParameters = [];

        while ($field = mysqli_fetch_field($metadata)) {
            $fields[] = $field->name;
            $row[$field->name] = null;
            $bindParameters[] = &$row[$field->name];
        }

        call_user_func_array('mysqli_stmt_bind_result', array_merge([$statement], $bindParameters));

        while (mysqli_stmt_fetch($statement)) {
            $record = [];

            foreach ($fields as $fieldName) {
                $record[$fieldName] = $row[$fieldName];
            }

            $rows[] = $record;
        }

        mysqli_free_result($metadata);
        return $rows;
    }
}