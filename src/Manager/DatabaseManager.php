<?php

namespace App\Manager;

use Exception;
use App\Util\AppUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManager
 *
 * Manager for CRUD database operations
 *
 * @package App\Manager
 */
class DatabaseManager
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private Connection $connection;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        Connection $connection,
        AuthManager $authManager,
        ErrorManager $errorManager,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->connection = $connection;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Get list of tables in database
     *
     * @return array<string> The list of tables if successful, otherwise null
     */
    public function getTables(): ?array
    {
        try {
            // fetch table names directly from database
            $tables = $this->connection->fetchFirstColumn('SHOW TABLES');

            // log table list view event
            $this->logManager->log(
                name: 'database',
                message: $this->authManager->getUsername() . ' viewed database list'
            );

            return $tables;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get tables list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get columns of a specific table
     *
     * @param string $tableName The name of the table
     *
     * @return array<string> The list of column names
     */
    public function getTableColumns(string $tableName): array
    {
        $table = null;
        $columns = [];
        $schema = $this->connection->createSchemaManager()->introspectSchema();

        // get data
        try {
            $table = $schema->getTable($tableName);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get columns from table: ' . $tableName . ', ' . $e->getMessage(),
                code: Response::HTTP_NOT_FOUND
            );
        }

        foreach ($table->getColumns() as $column) {
            $columns[] = $column->getObjectName()->toString();
        }

        return $columns;
    }

    /**
     * Get table columns with their types and foreign key information
     *
     * @param string $tableName The name of table
     *
     * @return array<array<string, mixed>> The list of columns with their names and types
     */
    public function getTableColumnsWithTypes(string $tableName): array
    {
        $columns = [];
        $foreignKeys = $this->getForeignKeys($tableName);
        $foreignKeyRelationships = $this->getForeignKeyRelationships($tableName);

        try {
            // get column information directly from database
            $sql = "SHOW COLUMNS FROM " . $this->connection->getDatabasePlatform()->quoteSingleIdentifier($tableName);
            $columnInfo = $this->connection->fetchAllAssociative($sql);

            foreach ($columnInfo as $column) {
                $fieldName = $column['Field'];
                $columnData = [
                    'name' => $fieldName,
                    'type' => strtoupper($column['Type']),
                    'isForeignKey' => in_array($fieldName, $foreignKeys),
                    'nullable' => $column['Null'] === 'YES'
                ];

                // add foreign key relationship info if available
                if (isset($foreignKeyRelationships[$fieldName])) {
                    $columnData['referencedTable'] = $foreignKeyRelationships[$fieldName]['referenced_table'];
                    $columnData['referencedColumn'] = $foreignKeyRelationships[$fieldName]['referenced_column'];
                }

                $columns[] = $columnData;
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get columns from table: ' . $tableName . ', ' . $e->getMessage(),
                code: Response::HTTP_NOT_FOUND
            );
        }

        return $columns;
    }

    /**
     * Get foreign key relationships for a table
     *
     * @param string $tableName The name of table
     *
     * @return array<string, array<string, string>> The foreign key relationships
     */
    private function getForeignKeyRelationships(string $tableName): array
    {
        $relationships = [];

        try {
            $sql = "
                SELECT 
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND TABLE_SCHEMA = DATABASE()
            ";

            $result = $this->connection->fetchAllAssociative($sql, [$tableName]);

            foreach ($result as $row) {
                $relationships[$row['COLUMN_NAME']] = [
                    'referenced_table' => $row['REFERENCED_TABLE_NAME'],
                    'referenced_column' => $row['REFERENCED_COLUMN_NAME']
                ];
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get foreign key relationships: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $relationships;
    }

    /**
     * Get foreign key columns for a table (backward compatibility)
     *
     * @param string $tableName The name of table
     *
     * @return array<string> The list of foreign key column names
     */
    private function getForeignKeys(string $tableName): array
    {
        return array_keys($this->getForeignKeyRelationships($tableName));
    }

    /**
     * Get data from a specific database table
     *
     * @param string $tableName The name of the table
     *
     * @return array<mixed> The array of table data
     */
    public function getTableData(string $tableName, bool $log = true): array
    {
        $data = [];

        // escape name from sql query
        $tableName = $this->connection->getDatabasePlatform()->quoteSingleIdentifier($tableName);

        // get data
        try {
            $data = $this->connection->executeQuery('SELECT * FROM ' . $tableName)->fetchAllAssociative();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get data from table: ' . $tableName . ', ' . $e->getMessage(),
                code: Response::HTTP_NOT_FOUND
            );
        }

        // log table data view event
        if ($log) {
            $this->logManager->log(
                name: 'database',
                message: $this->authManager->getUsername() . ' viewed database table: ' . $tableName
            );
        }

        return $data;
    }

    /**
     * Get data from a specific database table with pagination
     *
     * @param string $tableName The name of the database table
     * @param int $page The page number for pagination (default is 1)
     * @param bool $log Indicates whether to log the action (default is true)
     * @param bool $raw Whether to return raw data without decryption (default is false)
     *
     * @return array<mixed> The array of data from the specified table
     */
    public function getTableDataByPage(string $tableName, int $page = 1, bool $log = true, bool $raw = false): array
    {
        $data = [];
        $itemsPerPage = (int) $this->appUtil->getEnvValue('ITEMS_PER_PAGE');

        // escape name from sql query
        $tableName = $this->connection->getDatabasePlatform()->quoteSingleIdentifier($tableName);

        // calculate the offset based on the page number
        $offset = ($page - 1) * $itemsPerPage;

        // get data with LIMIT and OFFSET
        try {
            $query = 'SELECT * FROM ' . $tableName . ' LIMIT ' . $itemsPerPage . ' OFFSET ' . $offset;
            $data = $this->connection->executeQuery($query)->fetchAllAssociative();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get data from table: ' . $tableName . ', ' . $e->getMessage(),
                code: Response::HTTP_NOT_FOUND
            );
        }

        // log table data view event
        if ($log) {
            $this->logManager->log(
                name: 'database',
                message: $this->authManager->getUsername() . ' viewed database table: ' . $tableName
            );
        }

        // decrypt database data (specify table names)
        $decryptedTables = ["`inbox_messages`", "`users`"];

        // build new data array (decrypt aes data)
        if (in_array($tableName, $decryptedTables)) {
            $decryptedData = [];
            foreach ($data as $value) {
                $arr = [];
                foreach ($value as $key => $val) {
                    if ($raw == true) {
                        $arr[$key] = $val;
                    } else {
                        $arr[$key] = (
                            $key === 'message' ||
                            $key === 'profile_pic' ||
                            $key === 'token' ||
                            $key === 'password'
                        ) ? '[encrypted-data]' : $val;
                    }
                }
                array_push($decryptedData, $arr);
            }
            return $decryptedData;
        }

        return $data;
    }

    /**
     * Get data from a specific row of a database table
     *
     * @param string $tableName The name of the table
     * @param int $id The unique identifier of the record row
     *
     * @return array<mixed> The array of data from the specified row
     */
    public function selectRowData(string $tableName, int $id): array
    {
        $data = [];
        try {
            $queryBuilder = $this->connection->createQueryBuilder();
            $queryBuilder->select('*')->from($tableName)
                ->where('id = :id')->setParameter('id', $id);

            $statement = $this->connection->executeQuery($queryBuilder->getSQL(), $queryBuilder->getParameters());

            $data = $statement->fetchAllAssociative();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get data from table: ' . $tableName . ', ' . $e->getMessage(),
                code: Response::HTTP_NOT_FOUND
            );
        }
        return $data[0] ?? [];
    }

    /**
     * Add new row to specific database table
     *
     * @param string $tableName The name of the database table
     * @param array<string> $columns The array of column names
     * @param array<mixed> $values The array of values corresponding to the columns
     *
     * @return void
     */
    public function addNew(string $tableName, array $columns, array $values): void
    {
        // validate foreign keys before insert
        $this->validateForeignKeys($tableName, $columns, $values);

        // process values for boolean columns
        $processedValues = $this->processBooleanValues($tableName, $columns, $values);

        // create placeholders for prepared statement
        $columnPlaceholders = array_fill(0, count($columns), '?');
        $columnList = implode(', ', $columns);
        $columnPlaceholderList = implode(', ', $columnPlaceholders);

        // construct the SQL query
        $sql = "INSERT INTO `$tableName` ($columnList) VALUES ($columnPlaceholderList)";

        try {
            // execute query
            $this->connection->executeQuery($sql, $processedValues);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error insert new row into: ' . $tableName . ', ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log row add event
        $this->logManager->log(
            name: 'database',
            message: $this->authManager->getUsername() . ' inserted new row to table: ' . $tableName
        );
    }

    /**
     * Validate foreign key constraints before insert
     *
     * @param string $tableName The name of the table
     * @param array<string> $columns The array of column names
     * @param array<mixed> $values The array of values corresponding to the columns
     *
     * @return void
     */
    private function validateForeignKeys(string $tableName, array $columns, array $values): void
    {
        // get foreign key information
        $sql = "
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ";

        try {
            $foreignKeys = $this->connection->fetchAllAssociative($sql, [$tableName]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get foreign keys: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        foreach ($foreignKeys as $fk) {
            $columnName = $fk['COLUMN_NAME'];
            $referencedTable = $fk['REFERENCED_TABLE_NAME'];
            $referencedColumn = $fk['REFERENCED_COLUMN_NAME'];

            // find the index of this column in the input arrays
            $columnIndex = array_search($columnName, $columns);

            if ($columnIndex !== false) {
                $value = $values[$columnIndex];

            // validate that the referenced value exists
                if ($value !== null && $value !== '' && $value !== 'false' && $value !== '0') {
                    $countSql = "SELECT COUNT(*) FROM " . $referencedTable . " WHERE " . $referencedColumn . " = ?";
                    $exists = $this->connection->fetchOne($countSql, [$value]);

                    if ($exists == 0) {
                        throw new Exception(
                            "Foreign key constraint violation: Value '{$value}' does not exist in table '{$referencedTable}' column '{$referencedColumn}'"
                        );
                    }
                }
            }
        }
    }

    /**
     * Delete row from table
     *
     * @param string $tableName The name of the table
     * @param string $id The ID of the row to delete
     *
     * @return void
     */
    public function deleteRowFromTable(string $tableName, string $id): void
    {
        // refine foreign key relationships
        $foreignKeys = [
            'visitors' => [
                'users' => 'visitor_id',
                'inbox_messages' => 'visitor_id',
                'logs' => 'visitor_id'
            ]
        ];

        // ensure foreign key columns are nullable (Must be done BEFORE transaction because DDL causes implicit commit)
        if (isset($foreignKeys[$tableName])) {
            foreach ($foreignKeys[$tableName] as $refTable => $refColumn) {
                // this will autocommit any open transaction
                $this->connection->executeStatement("ALTER TABLE {$refTable} MODIFY {$refColumn} INT NULL");
            }
        }

        // start transaction to ensure atomicity
        $this->connection->beginTransaction();

        try {
            // disable foreign key checks and perform deletion
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

            if ($id == 'all') {
                // handle "delete all" with soft cascade
                if (isset($foreignKeys[$tableName])) {
                    foreach ($foreignKeys[$tableName] as $refTable => $refColumn) {
                        // set to NULL
                        $this->connection->executeStatement("
                            UPDATE {$refTable} SET {$refColumn} = NULL 
                            WHERE {$refColumn} IS NOT NULL
                        ");
                        $this->logManager->log(
                            name: 'database',
                            message: 'Soft cascade: Set NULL in ' . $refTable . '.' . $refColumn . ' before deleting all ' . $tableName
                        );
                    }
                }

                $sql = 'DELETE FROM ' . $tableName . ' WHERE 1=1';
                $this->connection->executeStatement($sql);
            } else {
                // handle single row delete with soft cascade
                if (isset($foreignKeys[$tableName])) {
                    foreach ($foreignKeys[$tableName] as $refTable => $refColumn) {
                        $this->connection->executeStatement("
                            UPDATE {$refTable} SET {$refColumn} = NULL 
                            WHERE {$refColumn} = ?
                        ", [$id]);
                        $this->logManager->log(
                            name: 'database',
                            message: 'Soft cascade: Set NULL in ' . $refTable . '.' . $refColumn . ' where ' . $refColumn . ' = ' . $id
                        );
                    }
                }

                $sql = 'DELETE FROM ' . $tableName . ' WHERE id = :id';
                $params = ['id' => $id];
                $this->connection->executeStatement($sql, $params);
            }

            // commit transaction
            $this->connection->commit();
        } catch (Exception $e) {
            // rollback changes on error
            $this->connection->rollBack();
            throw $e;
        } finally {
            // always re-enable foreign key checks
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // reset auto increment index (must be done after transaction commit because it causes implicit commit)
        if ($id == 'all') {
            $sqlIndexReset = 'ALTER TABLE ' . $tableName . ' AUTO_INCREMENT = 1';
            $this->connection->executeStatement($sqlIndexReset);
        }

        // log row delete event
        $this->logManager->log(
            name: 'database',
            message: 'Row deleted from table: ' . $tableName . ' with id: ' . $id . ' (soft cascade applied)'
        );
    }

    /**
     * Update specific value in record in database table
     *
     * @param string $tableName The name of the table in which the value will be updated
     * @param string $row The column name for which the value will be updated
     * @param string|int|null $value The new value to be set
     * @param int $id The unique identifier of the row
     *
     * @return void
     */
    public function updateValue(string $tableName, string $row, string|int|null $value, int $id): void
    {
        // validate foreign key before update
        $this->validateSingleForeignKey($tableName, $row, $value);

        // process value for boolean columns
        $processedValue = $this->processBooleanValue($tableName, $row, $value);

        // query builder
        $query = "UPDATE $tableName SET $row = :value WHERE id = :id";

        try {
            $this->connection->executeStatement($query, [
                'value' => $processedValue,
                'id' => $id
            ]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to update value: ' . $value . ' in: ' . $tableName . ' id: ' . $id . ', error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log row update event
        $this->logManager->log(
            name: 'database',
            message: $this->authManager->getUsername() . ': edited ' . $row . ' -> ' . $value . ', in table: ' . $tableName
        );
    }

    /**
     * Validate single foreign key constraint before update
     *
     * @param string $tableName The name of the table
     * @param string $columnName The column name being updated
     * @param string|int|null $value The new value
     *
     * @return void
     */
    private function validateSingleForeignKey(string $tableName, string $columnName, string|int|null $value): void
    {
        // skip validation for null or empty values
        if ($value === null || $value === '') {
            return;
        }

        // check if this column is a foreign key
        $sql = "
            SELECT 
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ? 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ";

        try {
            $fkInfo = $this->connection->fetchAssociative($sql, [$tableName, $columnName]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get foreign key info: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($fkInfo) {
            $referencedTable = $fkInfo['REFERENCED_TABLE_NAME'];
            $referencedColumn = $fkInfo['REFERENCED_COLUMN_NAME'];

            // validate that the referenced value exists
            $countSql = "SELECT COUNT(*) FROM " . $referencedTable . " WHERE " . $referencedColumn . " = ?";
            $exists = $this->connection->fetchOne($countSql, [$value]);

            if ($exists == 0) {
                throw new Exception(
                    "Foreign key constraint violation: Value '{$value}' does not exist in table '{$referencedTable}' column '{$referencedColumn}'"
                );
            }
        }
    }

    /**
     * Get count total number of rows in table
     *
     * @param string $tableName The name of the table
     *
     * @return int The total number of records
     */
    public function countTableData(string $tableName): int
    {
        return count($this->getTableData($tableName, false));
    }

    /**
     * Get count number of rows on a specific page of a table
     *
     * @param string $tableName The name of the table
     * @param int $page The page number
     *
     * @return int The number of rows on the page
     */
    public function countTableDataByPage(string $tableName, int $page): int
    {
        return count($this->getTableDataByPage($tableName, $page, false));
    }

    /**
     * Get entity table name
     *
     * @param string $entityClass The entity class
     *
     * @return string The entity table name
     */
    public function getEntityTableName(string $entityClass): string
    {
        if (!class_exists($entityClass)) {
            $this->errorManager->handleError(
                msg: 'entity class not found: ' . $entityClass,
                code: Response::HTTP_NOT_FOUND
            );
        }

        $metadata = $this->entityManager->getClassMetadata($entityClass);
        return $metadata->getTableName();
    }

    /**
     * Truncate table in a specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @return void
     */
    public function tableTruncate(string $dbName, string $tableName): void
    {
        // truncate table query
        $sql = 'TRUNCATE TABLE ' . $dbName . '.' . $tableName;

        try {
            // execute truncate table query
            $this->connection->executeStatement($sql);

            // log truncate table event
            $this->logManager->log(
                name: 'database',
                message: 'truncated table: ' . $tableName . ' in database:' . $dbName
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error truncating table: ' . $e->getMessage() . ' in database: ' . $dbName,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Process boolean values for TINYINT/BOOLEAN columns
     *
     * @param string $tableName The table name
     * @param array<string> $columns The column names
     * @param array<mixed> $values The values to process
     *
     * @return array<mixed> The processed values
     */
    private function processBooleanValues(string $tableName, array $columns, array $values): array
    {
        $processedValues = [];
        $columnTypes = $this->getTableColumnsWithTypes($tableName);

        foreach ($values as $index => $value) {
            $columnName = $columns[$index];
            $columnType = null;

            // find column type
            foreach ($columnTypes as $colInfo) {
                if ($colInfo['name'] === $columnName) {
                    $columnType = $colInfo['type'];
                    break;
                }
            }

            // return null directly for nullable fields
            if ($value === null) {
                $processedValues[] = null;
                continue;
            }

            // process boolean values
            if ($columnType && (str_contains($columnType, 'TINYINT') || str_contains($columnType, 'BOOLEAN'))) {
                // convert string values to proper boolean/int values
                if ($value === 'true' || $value === '1' || $value === 1) {
                    $processedValues[] = 1;
                } elseif ($value === 'false' || $value === '0' || $value === 0) {
                    $processedValues[] = 0;
                } else {
                    // for any other value, convert to boolean
                    $processedValues[] = (bool)$value ? 1 : 0;
                }
            } else {
                $processedValues[] = $value;
            }
        }

        return $processedValues;
    }

    /**
     * Process a single boolean value for TINYINT/BOOLEAN columns
     *
     * @param string $tableName The table name
     * @param string $columnName The column name
     * @param string|int|null $value The value to process
     *
     * @return int|string|null The processed value
     */
    private function processBooleanValue(string $tableName, string $columnName, string|int|null $value): int|string|null
    {
        $columnTypes = $this->getTableColumnsWithTypes($tableName);
        $columnType = null;

        // find column type
        foreach ($columnTypes as $colInfo) {
            if ($colInfo['name'] === $columnName) {
                $columnType = $colInfo['type'];
                break;
            }
        }

        // return null directly for nullable fields
        if ($value === null) {
            return null;
        }

        // process boolean values
        if ($columnType && (str_contains($columnType, 'TINYINT') || str_contains($columnType, 'BOOLEAN'))) {
            // convert string values to proper boolean/int values
            if ($value === 'true' || $value === '1' || $value === 1) {
                return 1;
            } elseif ($value === 'false' || $value === '0' || $value === 0) {
                return 0;
            } else {
                // for any other value, convert to boolean
                return (bool)$value ? 1 : 0;
            }
        }

        return $value;
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
                return $value ? 'true' : '';

            default:
                return (string)$value;
        }
    }

    /**
     * Convert HTML input value to database format
     *
     * @param mixed $value The value to convert
     * @param string $dbType The database column type
     *
     * @return mixed The converted value
     */
    public function convertValueForDatabase($value, string $dbType)
    {
        // return null directly for nullable fields
        if ($value === null) {
            return null;
        }

        // extract base type from type with length
        $baseType = preg_replace('/\([^)]*\)/', '', strtoupper($dbType));

        switch ($baseType) {
            case 'DATETIME':
            case 'TIMESTAMP':
                // convert datetime-local format "2024-12-24T15:30" to MySQL DATETIME "2024-12-24 15:30:00"
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
                    return str_replace('T', ' ', $value) . ':00';
                }
                // if it's already in correct format, return as is
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                    return $value;
                }
                break;

            case 'TIME':
                // convert time format "15:30" to MySQL TIME "15:30:00"
                if (preg_match('/^\d{2}:\d{2}$/', $value)) {
                    return $value . ':00';
                }
                break;

            case 'BOOLEAN':
            case 'TINYINT(1)':
            case 'TINYINT':
                return in_array(strtolower($value), ['true', '1', 'yes', 'on']) ? 1 : 0;

            default:
                return $value;
        }

        return $value;
    }

    /**
     * Validate data type against expected database type
     *
     * @param mixed $value The value to validate
     * @param string $expectedType The expected database type (e.g., "INT", "VARCHAR(255)", "DATETIME")
     *
     * @return bool True if valid, false otherwise
     */
    public function validateDataType($value, string $expectedType): bool
    {
        // extract base type from type with length (e.g., "VARCHAR(255)" -> "VARCHAR")
        $baseType = preg_replace('/\([^)]*\)/', '', strtoupper($expectedType));

        switch ($baseType) {
            case 'INT':
            case 'INTEGER':
            case 'TINYINT':
            case 'SMALLINT':
            case 'MEDIUMINT':
            case 'BIGINT':
                return is_numeric($value) && (int)$value == $value;

            case 'FLOAT':
            case 'DOUBLE':
            case 'DECIMAL':
                return is_numeric($value);

            case 'VARCHAR':
            case 'CHAR':
            case 'TEXT':
            case 'LONGTEXT':
            case 'MEDIUMTEXT':
            case 'TINYTEXT':
                // check maximum length for VARCHAR/CHAR
                if (preg_match('/\((\d+)\)/', $expectedType, $matches)) {
                    $maxLength = (int)$matches[1];
                    return strlen($value) <= $maxLength;
                }
                return is_string($value);

            case 'DATE':
                return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);

            case 'DATETIME':
            case 'TIMESTAMP':
                // accept both MySQL DATETIME format and datetime-local format
                return (bool)preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ||
                       (bool)preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value);

            case 'TIME':
                // accept both MySQL TIME format (HH:MM:SS) and HTML time format (HH:MM)
                return (bool)preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) ||
                       (bool)preg_match('/^\d{2}:\d{2}$/', $value);

            case 'BOOLEAN':
            case 'TINYINT(1)':
            case 'TINYINT':
                return in_array(strtolower($value), ['0', '1', 'true', 'false', 'yes', 'no', 'on', 'off']);

            case 'JSON':
                // basic JSON validation
                json_decode($value);
                return json_last_error() === JSON_ERROR_NONE;

            default:
                // for unknown types, accept any value
                return true;
        }
    }

    /**
     * Check if a value is empty with special handling for TINYINT/BOOLEAN fields
     *
     * @param mixed $value The value to check
     * @param string $type The database column type
     *
     * @return bool True if the value is considered empty
     */
    public function isEmptyValue(mixed $value, string $type): bool
    {
        // special handling for TINYINT/BOOLEAN fields
        if (str_contains($type, 'TINYINT') || str_contains($type, 'BOOLEAN')) {
            return !isset($value);
        }

        // for all other fields, use standard empty() check
        return empty($value);
    }
}
