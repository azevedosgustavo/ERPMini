<?php

class BaseModel
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    protected function now()
    {
        return date('Y-m-d H:i:s');
    }

    protected function dateTimeOrNow($value)
    {
        if (!$value) {
            return $this->now();
        }

        if (strlen($value) === 10) {
            return $value . ' 00:00:00';
        }

        return date('Y-m-d H:i:s', strtotime($value));
    }

    protected function normalizeFlag($value, $default = '0')
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return ($value === '1' || $value === 1 || $value === true) ? '1' : '0';
    }

    protected function normalizeAmount($value)
    {
        return number_format((float) $value, 2, '.', '');
    }

    protected function generateNumber($tableName, $fieldName, $prefix, $padding = 5)
    {
        $sequence = $this->db->fetchOne(
            'SELECT RecId, NextNumber, FormatMask FROM SysNumberSequenceTable WHERE ObjectCode = ? AND IsActive = "1" LIMIT 1',
            [strtoupper($prefix)]
        );

        if ($sequence) {
            $nextNumber = (int) $sequence['NextNumber'];
            $formatted = $this->formatSequenceValue($sequence['FormatMask'], $nextNumber);

            $this->db->execute(
                'UPDATE SysNumberSequenceTable SET CurrentNumber = ?, NextNumber = ?, ModifiedDateTime = ? WHERE RecId = ?',
                [$nextNumber, $nextNumber + 1, $this->now(), (int) $sequence['RecId']]
            );

            return $formatted;
        }

        $row = $this->db->fetchOne('SELECT MAX(RecId) AS MaxId FROM ' . $tableName);
        $nextNumber = $row && $row['MaxId'] ? ((int) $row['MaxId']) + 1 : 1;

        return $prefix . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);
    }

    private function formatSequenceValue($formatMask, $number)
    {
        $mask = trim((string) $formatMask);

        if ($mask === '') {
            return (string) $number;
        }

        if (preg_match('/#+/', $mask, $matches, PREG_OFFSET_CAPTURE)) {
            $hashRun = $matches[0][0];
            $position = $matches[0][1];
            $padded = str_pad((string) $number, strlen($hashRun), '0', STR_PAD_LEFT);

            return substr_replace($mask, $padded, $position, strlen($hashRun));
        }

        return $mask . $number;
    }
}