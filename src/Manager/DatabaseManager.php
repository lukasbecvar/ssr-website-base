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
        $tablesList = [];
        $tables = null;

        try {
            // get tables list
            $schemaManager = $this->connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get tables list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log table list view event
        $this->logManager->log('database', $this->authManager->getUsername() . ' viewed database list');

        // create tables list
        foreach ($tables as $table) {
            array_push($tablesList, $table);
        }

        return $tablesList;
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
                value: $this->authManager->getUsername() . ' viewed database table: ' . $tableName
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
                value: $this->authManager->getUsername() . ' viewed database table: ' . $tableName
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
        // create placeholders for prepared statement
        $columnPlaceholders = array_fill(0, count($columns), '?');
        $columnList = implode(', ', $columns);
        $columnPlaceholderList = implode(', ', $columnPlaceholders);

        // construct the SQL query
        $sql = "INSERT INTO `$tableName` ($columnList) VALUES ($columnPlaceholderList)";

        try {
            // execute query
            $this->connection->executeQuery($sql, $values);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error insert new row into: ' . $tableName . ', ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log row add event
        $this->logManager->log(
            name: 'database',
            value: $this->authManager->getUsername() . ' inserted new row to table: ' . $tableName
        );
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
        if ($id == 'all') {
            $sql = 'DELETE FROM ' . $tableName . ' WHERE id=id';
            $this->connection->executeStatement($sql);

            $sqlIndexReset = 'ALTER TABLE ' . $tableName . ' AUTO_INCREMENT = 1';
            $this->connection->executeStatement($sqlIndexReset);
        } else {
            $sql = 'DELETE FROM ' . $tableName . ' WHERE id = :id';
            $params = ['id' => $id];
            $this->connection->executeStatement($sql, $params);
        }

        // log row delete event
        $this->logManager->log(
            name: 'database',
            value: $this->authManager->getUsername() . ' deleted row: ' . $id . ', table: ' . $tableName
        );
    }

    /**
     * Update specific value in record in database table
     *
     * @param string $tableName The name of the table in which the value will be updated
     * @param string $row The column name for which the value will be updated
     * @param string $value The new value to be set
     * @param int $id The unique identifier of the row
     *
     * @return void
     */
    public function updateValue(string $tableName, string $row, string $value, int $id): void
    {
        // query builder
        $query = "UPDATE $tableName SET $row = :value WHERE id = :id";

        try {
            $this->connection->executeStatement($query, [
                'value' => $value,
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
            value: $this->authManager->getUsername() . ': edited ' . $row . ' -> ' . $value . ', in table: ' . $tableName
        );
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
                value: 'truncated table: ' . $tableName . ' in database:' . $dbName
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error truncating table: ' . $e->getMessage() . ' in database: ' . $dbName,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
