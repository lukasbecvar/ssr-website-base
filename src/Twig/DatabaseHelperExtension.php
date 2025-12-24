<?php

namespace App\Twig;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

/**
 * DatabaseHelperExtension - provides helper functions for database forms
 */
class DatabaseHelperExtension extends AbstractExtension
{
    /**
     * Get functions provided by this extension
     *
     * @return array<TwigFunction> List of functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getInputTypeFromDbType', [$this, 'getInputTypeFromDbType']),
            new TwigFunction('formatValueForInput', [$this, 'formatValueForInput']),
        ];
    }

    /**
     * Get HTML input type based on database column type
     *
     * @param string $dbType The database column type (e.g., "INT", "VARCHAR(255)", "DATETIME")
     *
     * @return string The HTML input type (e.g., "text", "number", "datetime-local", "date", "time")
     */
    public function getInputTypeFromDbType(string $dbType): string
    {
        // extract base type from type with length (e.g., "VARCHAR(255)" -> "VARCHAR")
        $baseType = preg_replace('/\([^)]*\)/', '', strtoupper($dbType));

        switch ($baseType) {
            case 'INT':
            case 'INTEGER':
            case 'TINYINT':
            case 'SMALLINT':
            case 'MEDIUMINT':
            case 'BIGINT':
                return 'number';

            case 'FLOAT':
            case 'DOUBLE':
            case 'DECIMAL':
                return 'number';

            case 'DATE':
                return 'date';

            case 'DATETIME':
            case 'TIMESTAMP':
                return 'datetime-local';

            case 'TIME':
                return 'time';

            case 'BOOLEAN':
            case 'TINYINT(1)':
                return 'checkbox';

            case 'TEXT':
            case 'LONGTEXT':
            case 'MEDIUMTEXT':
            case 'TINYTEXT':
                return 'textarea';

            case 'VARCHAR':
            case 'CHAR':
            default:
                return 'text';
        }
    }

    /**
     * Format database value for HTML input based on input type
     *
     * @param mixed $value The database value
     * @param string $inputType The HTML input type
     *
     * @return string The formatted value for the input
     */
    public function formatValueForInput($value, string $inputType): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        switch ($inputType) {
            case 'datetime-local':
                // convert DATETIME "2024-12-24 15:30:00" to "2024-12-24T15:30"
                if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2})$/', $value, $matches)) {
                    return $matches[1] . 'T' . substr($matches[2], 0, 5);
                }
                return $value;

            case 'date':
                // ensure date format
                return date('Y-m-d', strtotime($value));

            case 'time':
                // ensure time format, remove seconds
                if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $value, $matches)) {
                    return $matches[1];
                }
                return $value;

            case 'checkbox':
                // handle TINYINT/BOOLEAN values properly
                if ($value === 1 || $value === '1' || $value === true || $value === 'true') {
                    return 'true';
                }
                return '';

            default:
                return (string)$value;
        }
    }
}
