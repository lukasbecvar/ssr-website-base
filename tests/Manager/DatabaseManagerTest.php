<?php

namespace App\Tests\Manager;

use Exception;
use App\Entity\User;
use App\Util\AppUtil;
use RuntimeException;
use Doctrine\DBAL\Result;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Column;
use App\Manager\DatabaseManager;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Class DatabaseManagerTest
 *
 * Test cases for DatabaseManager
 *
 * @package App\Tests\Manager
 */
class DatabaseManagerTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private DatabaseManager $databaseManager;
    private LogManager & MockObject $logManager;
    private Connection & MockObject $connection;
    private AuthManager & MockObject $authManager;
    private ErrorManager & MockObject $errorManager;
    private EntityManagerInterface & MockObject $entityManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->logManager = $this->createMock(LogManager::class);
        $this->connection = $this->createMock(Connection::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // init database manager instance
        $this->databaseManager = new DatabaseManager(
            $this->appUtil,
            $this->logManager,
            $this->connection,
            $this->authManager,
            $this->errorManager,
            $this->entityManager
        );
    }

    /**
     * Test getTables success
     *
     * @return void
     */
    public function testGetTablesSuccess(): void
    {
        $tables = ['users', 'logs'];
        $this->connection->method('fetchFirstColumn')->with('SHOW TABLES')->willReturn($tables);
        $this->authManager->method('getUsername')->willReturn('admin');

        // expect log event
        $this->logManager->expects($this->once())->method('log');

        // call tested method
        $result = $this->databaseManager->getTables();

        // assert result
        $this->assertEquals($tables, $result);
    }

    /**
     * Test getTables exception
     *
     * @return void
     */
    public function testGetTablesException(): void
    {
        // simulate db error
        $this->connection->method('fetchFirstColumn')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to get tables list'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        )->willThrowException(new RuntimeException('Terminated'));
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->getTables();
    }

    /**
     * Test getTableColumns success
     *
     * @return void
     */
    public function testGetTableColumnsSuccess(): void
    {
        $tableName = 'users';
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schema = $this->createMock(Schema::class);
        $table = $this->createMock(Table::class);
        $column = $this->createMock(Column::class);
        $name = $this->createMock(Name::class);

        $this->connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('introspectSchema')->willReturn($schema);
        $schema->method('getTable')->with($tableName)->willReturn($table);
        $table->method('getColumns')->willReturn([$column]);
        $column->method('getObjectName')->willReturn($name);
        $name->method('toString')->willReturn('username');

        // call tested method
        $result = $this->databaseManager->getTableColumns($tableName);

        // assert result
        $this->assertEquals(['username'], $result);
    }

    /**
     * Test getTableColumns exception (table not found)
     *
     * @return void
     */
    public function testGetTableColumnsTableNotFound(): void
    {
        $tableName = 'non_existent_table';
        $schema = $this->createMock(Schema::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $this->connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('introspectSchema')->willReturn($schema);

        // simulate table not found
        $schema->method('getTable')->with($tableName)->willThrowException(new Exception('Table not found'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to get columns from table'),
            $this->equalTo(Response::HTTP_NOT_FOUND)
        )->willThrowException(new RuntimeException('Terminated'));
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->getTableColumns($tableName);
    }

    /**
     * Test getTableColumnsWithTypes success
     *
     * @return void
     */
    public function testGetTableColumnsWithTypesSuccess(): void
    {
        // expected result
        $expected = [
            ['name' => 'id', 'type' => 'INT(11)', 'nullable' => false, 'isForeignKey' => false],
            ['name' => 'username', 'type' => 'VARCHAR(255)', 'nullable' => false, 'isForeignKey' => false],
            ['name' => 'visitor_id', 'type' => 'INT(11)', 'nullable' => true, 'isForeignKey' => true, 'referencedTable' => 'visitors', 'referencedColumn' => 'id']
        ];

        $tableName = 'users';
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        // mock fetchAllAssociative for SHOW COLUMNS and getForeignKeyRelationships
        $this->connection->expects($this->exactly(3))->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            // first call: getForeignKeyRelationships (from getForeignKeys)
            [
                ['COLUMN_NAME' => 'visitor_id', 'REFERENCED_TABLE_NAME' => 'visitors', 'REFERENCED_COLUMN_NAME' => 'id']
            ],
            // second call: getForeignKeyRelationships (direct call for $foreignKeyRelationships)
            [
                ['COLUMN_NAME' => 'visitor_id', 'REFERENCED_TABLE_NAME' => 'visitors', 'REFERENCED_COLUMN_NAME' => 'id']
            ],
            // third call: SHOW COLUMNS
            [
                ['Field' => 'id', 'Type' => 'int(11)', 'Null' => 'NO'],
                ['Field' => 'username', 'Type' => 'varchar(255)', 'Null' => 'NO'],
                ['Field' => 'visitor_id', 'Type' => 'int(11)', 'Null' => 'YES']
            ]
        );

        // call tested method
        $result = $this->databaseManager->getTableColumnsWithTypes($tableName);

        // assert result
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getTableData success (with logging)
     *
     * @return void
     */
    public function testGetTableDataSuccessWithLogging(): void
    {
        $tableName = 'users';
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([['id' => 1]]);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')->with('SELECT * FROM `users`')->willReturn($resultMock);

        // expect log event
        $this->authManager->method('getUsername')->willReturn('admin');
        $this->logManager->expects($this->once())->method('log');

        // call tested method
        $result = $this->databaseManager->getTableData($tableName, true);

        // assert result
        $this->assertEquals([['id' => 1]], $result);
    }

    /**
     * Test getTableData success (without logging)
     *
     * @return void
     */
    public function testGetTableDataSuccessWithoutLogging(): void
    {
        $tableName = 'users';
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([['id' => 1]]);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')->with('SELECT * FROM `users`')->willReturn($resultMock);

        // expect log event
        $this->logManager->expects($this->never())->method('log');

        // call tested method
        $result = $this->databaseManager->getTableData($tableName, false);

        // assert result
        $this->assertEquals([['id' => 1]], $result);
    }

    /**
     * Test getTableData exception
     *
     * @return void
     */
    public function testGetTableDataException(): void
    {
        $tableName = 'non_existent';
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        // simulate db error
        $this->connection->method('executeQuery')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to get data from table'),
            $this->equalTo(Response::HTTP_NOT_FOUND)
        )->willThrowException(new RuntimeException('Terminated'));
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->getTableData($tableName);
    }

    /**
     * Test getTableDataByPage success (with logging, no raw, decrypted)
     *
     * @return void
     */
    public function testGetTableDataByPageSuccessDecrypted(): void
    {
        // expect result
        $expected = [
            ['id' => 1, 'username' => 'testuser', 'password' => '[encrypted-data]', 'token' => '[encrypted-data]']
        ];

        $page = 2;
        $itemsPerPage = 10;
        $tableName = 'users';
        $this->appUtil->method('getEnvValue')->with('ITEMS_PER_PAGE')->willReturn((string)$itemsPerPage);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([
            ['id' => 1, 'username' => 'testuser', 'password' => 'hashed', 'token' => 'tokenval']
        ]);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')->with('SELECT * FROM `users` LIMIT 10 OFFSET 10')
            ->willReturn($resultMock);

        // expect log event
        $this->authManager->method('getUsername')->willReturn('admin');
        $this->logManager->expects($this->once())->method('log');

        // call tested method
        $result = $this->databaseManager->getTableDataByPage($tableName, $page, true, false);

        // assert result
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getTableDataByPage success (with logging, raw)
     *
     * @return void
     */
    public function testGetTableDataByPageSuccessRaw(): void
    {
        // expect result
        $expected = [
            ['id' => 1, 'username' => 'testuser', 'password' => 'hashed', 'token' => 'tokenval']
        ];

        $page = 1;
        $itemsPerPage = 10;
        $tableName = 'users';
        $this->appUtil->method('getEnvValue')->with('ITEMS_PER_PAGE')->willReturn((string)$itemsPerPage);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([
            ['id' => 1, 'username' => 'testuser', 'password' => 'hashed', 'token' => 'tokenval']
        ]);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')->with('SELECT * FROM `users` LIMIT 10 OFFSET 0')
            ->willReturn($resultMock);

        // expect log event
        $this->authManager->method('getUsername')->willReturn('admin');
        $this->logManager->expects($this->once())->method('log');

        // call tested method
        $result = $this->databaseManager->getTableDataByPage($tableName, $page, true, true);

        // assert result
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getTableDataByPage exception
     *
     * @return void
     */
    public function testGetTableDataByPageException(): void
    {
        $page = 1;
        $tableName = 'users';
        $this->appUtil->method('getEnvValue')->with('ITEMS_PER_PAGE')->willReturn('10');

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        // simulate db error
        $this->connection->method('executeQuery')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to get data from table'),
            $this->equalTo(Response::HTTP_NOT_FOUND)
        )->willThrowException(new RuntimeException('Terminated'));
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->getTableDataByPage($tableName, $page);
    }

    /**
     * Test selectRowData success
     *
     * @return void
     */
    public function testSelectRowDataSuccess(): void
    {
        $id = 1;
        $tableName = 'users';
        $qb = $this->createMock(QueryBuilder::class);

        $this->connection->method('createQueryBuilder')->willReturn($qb);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getSQL')->willReturn('SELECT * FROM users WHERE id = :id');
        $qb->method('getParameters')->willReturn(['id' => $id]);
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([['id' => 1, 'username' => 'test']]);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')->with('SELECT * FROM users WHERE id = :id', ['id' => $id])
            ->willReturn($resultMock);

        // call tested method
        $result = $this->databaseManager->selectRowData($tableName, $id);

        // assert result
        $this->assertEquals(['id' => 1, 'username' => 'test'], $result);
    }

    /**
     * Test selectRowData not found
     *
     * @return void
     */
    public function testSelectRowDataNotFound(): void
    {
        $id = 99;
        $tableName = 'users';
        $qb = $this->createMock(QueryBuilder::class);

        $this->connection->method('createQueryBuilder')->willReturn($qb);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getSQL')->willReturn('SELECT * FROM users WHERE id = :id');
        $qb->method('getParameters')->willReturn(['id' => $id]);

        // simulate no data found
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([]);
        $this->connection->expects($this->once())->method('executeQuery')->willReturn($resultMock);

        // expect error handling
        $result = $this->databaseManager->selectRowData($tableName, $id);

        // assert result
        $this->assertEquals([], $result);
    }

    /**
     * Test selectRowData exception
     *
     * @return void
     */
    public function testSelectRowDataException(): void
    {
        $id = 1;
        $tableName = 'users';
        $qb = $this->createMock(QueryBuilder::class);

        $this->connection->method('createQueryBuilder')->willReturn($qb);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getSQL')->willReturn('SELECT * FROM users WHERE id = :id');
        $qb->method('getParameters')->willReturn(['id' => $id]);

        // simulate db error
        $this->connection->method('executeQuery')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to get data from table'),
            $this->equalTo(Response::HTTP_NOT_FOUND)
        )->willThrowException(new RuntimeException('Terminated'));
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->selectRowData($tableName, $id);
    }

    /**
     * Test addNewRow success
     *
     * @return void
     */
    public function testAddNewRow(): void
    {
        $tableName = 'test_table';
        $columns = ['col1', 'col2'];
        $values = ['val1', 'val2'];

        $this->connection->expects($this->exactly(4))->method('fetchAllAssociative')->willReturn([]);
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnArgument(0);
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')
            ->with("INSERT INTO `test_table` (col1, col2) VALUES (?, ?)", ['val1', 'val2']);

        // expect log event
        $this->authManager->method('getUsername')->willReturn('admin');
        $this->logManager->expects($this->once())->method('log');

        // call tested method
        $this->databaseManager->addNew($tableName, $columns, $values);
    }

    /**
     * Test processBooleanValues logic
     *
     * @return void
     */
    public function testProcessBooleanValues(): void
    {
        $tableName = 'test_table';
        $columns = ['is_active', 'is_admin', 'name', 'count'];
        $values = ['true', '0', 'John', 5];

        // mock column types
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        $this->connection->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            [], // getForeignKeyRelationships (1)
            [], // getForeignKeyRelationships (2)
            [   // SHOW COLUMNS
                ['Field' => 'is_active', 'Type' => 'tinyint(1)', 'Null' => 'NO'],
                ['Field' => 'is_admin', 'Type' => 'boolean', 'Null' => 'NO'],
                ['Field' => 'name', 'Type' => 'varchar(255)', 'Null' => 'NO'],
                ['Field' => 'count', 'Type' => 'int', 'Null' => 'NO']
            ]
        );

        // call tested method
        $processed = $this->databaseManager->processBooleanValues($tableName, $columns, $values);

        // assert result
        $this->assertSame(1, $processed[0]);
        $this->assertSame(0, $processed[1]);
        $this->assertSame('John', $processed[2]);
        $this->assertSame(5, $processed[3]);
    }

    /**
     * Test addNew validation of foreign keys violation
     *
     * @return void
     */
    public function testAddNewForeignKeyViolation(): void
    {
        $tableName = 'users';
        $columns = ['visitor_id'];
        $values = [999];

        // mock fetchAllAssociative for validateForeignKeys
        $this->connection->expects($this->once())->method('fetchAllAssociative')->willReturn([[
            'COLUMN_NAME' => 'visitor_id',
            'REFERENCED_TABLE_NAME' => 'visitors',
            'REFERENCED_COLUMN_NAME' => 'id'
        ]]);

        // mock fetchOne to return 0 (violation)
        $this->connection->expects($this->once())->method('fetchOne')->willReturn(0);

        // expect error handling
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Foreign key constraint violation");

        // call tested method
        $this->databaseManager->addNew($tableName, $columns, $values);
    }

    /**
     * Test deleteRowFromTable (single row)
     *
     * @return void
     */
    public function testDeleteRowFromTableSingle(): void
    {
        $tableName = 'users';
        $id = '5';

        // expect transaction
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->exactly(3))->method('executeStatement');
        $this->connection->expects($this->once())->method('commit');

        // expect log event
        $this->logManager->expects($this->once())->method('log');

        // call tested method
        $this->databaseManager->deleteRowFromTable($tableName, $id);
    }

    /**
     * Test deleteRowFromTable (delete all)
     *
     * @return void
     */
    public function testDeleteRowFromTableAll(): void
    {
        $tableName = 'logs';
        $id = 'all';

        $this->connection->expects($this->once())->method('beginTransaction');
        // expecting foreign key disable, delete all, commit, enable foreign key, reset AI
        $this->connection->expects($this->exactly(4))->method('executeStatement');
        $this->connection->expects($this->once())->method('commit');

        // call tested method
        $this->databaseManager->deleteRowFromTable($tableName, $id);
    }

    /**
     * Test deleteRowFromTable with foreign keys and cascade (soft)
     *
     * @return void
     */
    public function testDeleteRowFromTableWithSoftCascade(): void
    {
        $id = '10';
        $tableName = 'visitors';

        // simulate successful execution
        $this->connection->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->atLeastOnce())->method('beginTransaction');
        $this->connection->expects($this->atLeastOnce())->method('commit');

        // expect ALTER TABLE statements for users, inbox_messages, logs, then SET FOREIGN_KEY_CHECKS = 0, UPDATEs, DELETE, SET FOREIGN_KEY_CHECKS = 1
        $this->connection->expects($this->exactly(9))->method('executeStatement');

        // expect log event
        $this->logManager->expects($this->once())->method('log');

        // call tested method
        $this->databaseManager->deleteRowFromTable($tableName, $id);
    }

    /**
     * Test deleteRowFromTable exception
     *
     * @return void
     */
    public function testDeleteRowFromTableException(): void
    {
        // mock transaction and simulate db error
        $this->connection->method('beginTransaction');
        $this->connection->expects($this->atLeastOnce())->method('executeStatement')->willThrowException(new Exception('DB Fail'));
        $this->connection->expects($this->once())->method('rollBack');

        // expect error handling
        $this->expectException(Exception::class);

        // call tested method
        $this->databaseManager->deleteRowFromTable('users', '1');
    }

    /**
     * Test updateValue success
     *
     * @return void
     */
    public function testUpdateValueSuccess(): void
    {
        $id = 1;
        $row = 'username';
        $value = 'new_name';
        $tableName = 'users';

        // mock fetchAllAssociative for getTableColumnsWithTypes (3 calls)
        $this->connection->expects($this->exactly(3))->method('fetchAllAssociative')->willReturn([]);

        // mock fetchAssociative for validateSingleForeignKey (no FK info)
        $this->connection->expects($this->once())->method('fetchAssociative')->willReturn(false);
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnArgument(0);
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        // expect query execution
        $this->connection->expects($this->once())->method('executeStatement')->with(
            "UPDATE $tableName SET $row = :value WHERE id = :id",
            ['value' => $value, 'id' => $id]
        );

        // expect log event
        $this->logManager->expects($this->once())->method('log');
        $this->authManager->method('getUsername')->willReturn('admin');

        // call tested method
        $this->databaseManager->updateValue($tableName, $row, $value, $id);
    }

    /**
     * Test updateValue with foreign key violation
     *
     * @return void
     */
    public function testUpdateValueForeignKeyViolation(): void
    {
        $id = 1;
        $value = 999;
        $row = 'visitor_id';
        $tableName = 'users';

        // expect fetch not to be called
        $this->connection->expects($this->never())->method('fetchAllAssociative');

        // mock fetchAssociative for validateSingleForeignKey (FK_INFO)
        $this->connection->expects($this->once())->method('fetchAssociative')->willReturn([
            'REFERENCED_TABLE_NAME' => 'visitors',
            'REFERENCED_COLUMN_NAME' => 'id'
        ]);

        // mock fetchOne for FK validation: simulate non-existent ID
        $this->connection->expects($this->once())->method('fetchOne')->willReturn(0);
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnArgument(0);
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        // expect error handling
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Foreign key constraint violation: Value '999' does not exist in table 'visitors' column 'id'");

        // call tested method
        $this->databaseManager->updateValue($tableName, $row, $value, $id);
    }

    /**
     * Test updateValue exception (DB error)
     *
     * @return void
     */
    public function testUpdateValueException(): void
    {
        $id = 1;
        $row = 'username';
        $value = 'new_name';
        $tableName = 'users';

        // mock fetchAllAssociative for getTableColumnsWithTypes (3 calls)
        $this->connection->expects($this->exactly(3))->method('fetchAllAssociative')->willReturn([]);

        // mock fetchAssociative for validateSingleForeignKey (no FK info)
        $this->connection->expects($this->once())->method('fetchAssociative')->willReturn(false);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnArgument(0);
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        // simulate db error
        $this->connection->expects($this->once())->method('executeStatement')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to update value'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        )->willThrowException(new RuntimeException('Terminated'));
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->updateValue($tableName, $row, $value, $id);
    }

    /**
     * Test countTableData
     *
     * @return void
     */
    public function testCountTableData(): void
    {
        $tableName = 'users';
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([['id' => 1], ['id' => 2]]);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')->willReturn($resultMock);

        // call tested method
        $result = $this->databaseManager->countTableData($tableName);

        // assert result
        $this->assertEquals(2, $result);
    }

    /**
     * Test countTableDataByPage
     *
     * @return void
     */
    public function testCountTableDataByPage(): void
    {
        $page = 1;
        $itemsPerPage = 10;
        $tableName = 'users';
        $this->appUtil->method('getEnvValue')->with('ITEMS_PER_PAGE')->willReturn((string)$itemsPerPage);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn("`$tableName`");
        $this->connection->method('getDatabasePlatform')->willReturn($platform);

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([['id' => 1], ['id' => 2]]);

        // expect query execution
        $this->connection->expects($this->once())->method('executeQuery')->willReturn($resultMock);

        // call tested method
        $result = $this->databaseManager->countTableDataByPage($tableName, $page);

        // assert result
        $this->assertEquals(2, $result);
    }

    /**
     * Test getEntityTableName success
     *
     * @return void
     */
    public function testGetEntityTableNameSuccess(): void
    {
        $entityClass = User::class;
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('users');

        // expect get class metadata
        $this->entityManager->expects($this->once())->method('getClassMetadata')->with($entityClass)->willReturn($metadata);

        // call tested method
        $result = $this->databaseManager->getEntityTableName($entityClass);

        // assert result
        $this->assertEquals('users', $result);
    }

    /**
     * Test getEntityTableName not found
     *
     * @return void
     */
    public function testGetEntityTableNameNotFound(): void
    {
        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')
            ->with($this->stringContains('entity class not found'), $this->equalTo(Response::HTTP_NOT_FOUND))
            ->willThrowException(new RuntimeException('Terminated'));

        // expect exception
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->getEntityTableName('NonExistentClass');
    }

    /**
     * Test tableTruncate success
     *
     * @return void
     */
    public function testTableTruncateSuccess(): void
    {
        $dbName = 'test_db';
        $tableName = 'logs';

        // expect query execution
        $this->connection->expects($this->once())->method('executeStatement')
            ->with('TRUNCATE TABLE ' . $dbName . '.' . $tableName);

        // expect log event
        $this->logManager->expects($this->once())->method('log');
        $this->authManager->method('getUsername')->willReturn('admin');

        // call tested method
        $this->databaseManager->tableTruncate($dbName, $tableName);
    }

    /**
     * Test tableTruncate exception
     *
     * @return void
     */
    public function testTableTruncateException(): void
    {
        $dbName = 'test_db';
        $tableName = 'logs';

        // simulate db error
        $this->connection->method('executeStatement')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error truncating table'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        )->willThrowException(new RuntimeException('Terminated'));
        $this->expectException(RuntimeException::class);

        // call tested method
        $this->databaseManager->tableTruncate($dbName, $tableName);
    }

    /**
     * Test getInputTypeFromDbType
     *
     * @return void
     */
    public function testGetInputTypeFromDbType(): void
    {
        $this->assertEquals('date', $this->databaseManager->getInputTypeFromDbType('DATE'));
        $this->assertEquals('time', $this->databaseManager->getInputTypeFromDbType('TIME'));
        $this->assertEquals('number', $this->databaseManager->getInputTypeFromDbType('INT'));
        $this->assertEquals('number', $this->databaseManager->getInputTypeFromDbType('BIGINT'));
        $this->assertEquals('textarea', $this->databaseManager->getInputTypeFromDbType('TEXT'));
        $this->assertEquals('checkbox', $this->databaseManager->getInputTypeFromDbType('BOOLEAN'));
        $this->assertEquals('number', $this->databaseManager->getInputTypeFromDbType('TINYINT(1)'));
        $this->assertEquals('text', $this->databaseManager->getInputTypeFromDbType('VARCHAR(255)'));
        $this->assertEquals('text', $this->databaseManager->getInputTypeFromDbType('UNKNOWN_TYPE'));
        $this->assertEquals('number', $this->databaseManager->getInputTypeFromDbType('DECIMAL(10,2)'));
        $this->assertEquals('datetime-local', $this->databaseManager->getInputTypeFromDbType('DATETIME'));
    }

    /**
     * Test formatValueForInput
     *
     * @return void
     */
    public function testFormatValueForInput(): void
    {
        $this->assertEquals('', $this->databaseManager->formatValueForInput('', 'text'));
        $this->assertEquals('', $this->databaseManager->formatValueForInput(null, 'text'));
        $this->assertEquals('', $this->databaseManager->formatValueForInput(0, 'checkbox'));
        $this->assertEquals('true', $this->databaseManager->formatValueForInput(1, 'checkbox'));
        $this->assertEquals('test', $this->databaseManager->formatValueForInput('test', 'text'));
        $this->assertEquals('12:30', $this->databaseManager->formatValueForInput('12:30:45', 'time'));
        $this->assertEquals('2024-01-01', $this->databaseManager->formatValueForInput('2024-01-01 12:00:00', 'date'));
        $this->assertEquals('2024-01-01T12:30', $this->databaseManager->formatValueForInput('2024-01-01 12:30:00', 'datetime-local'));
    }

    /**
     * Test convertValueForDatabase
     *
     * @return void
     */
    public function testConvertValueForDatabase(): void
    {
        $this->assertNull($this->databaseManager->convertValueForDatabase(null, 'INT'));
        $this->assertEquals(1, $this->databaseManager->convertValueForDatabase('true', 'BOOLEAN'));
        $this->assertEquals(0, $this->databaseManager->convertValueForDatabase('false', 'TINYINT(1)'));
        $this->assertEquals('12:30:00', $this->databaseManager->convertValueForDatabase('12:30', 'TIME'));
        $this->assertEquals('some_text', $this->databaseManager->convertValueForDatabase('some_text', 'VARCHAR'));
        $this->assertEquals('2024-01-01 12:30:00', $this->databaseManager->convertValueForDatabase('2024-01-01T12:30', 'DATETIME'));
        $this->assertEquals('2024-01-01 12:30:00', $this->databaseManager->convertValueForDatabase('2024-01-01 12:30:00', 'DATETIME'));
    }

    /**
     * Test validateDataType
     *
     * @return void
     */
    public function testValidateDataType(): void
    {
        $this->assertTrue($this->databaseManager->validateDataType(123, 'INT'));
        $this->assertFalse($this->databaseManager->validateDataType('abc', 'INT'));
        $this->assertTrue($this->databaseManager->validateDataType(12.34, 'FLOAT'));
        $this->assertTrue($this->databaseManager->validateDataType('12:30', 'TIME'));
        $this->assertTrue($this->databaseManager->validateDataType('true', 'BOOLEAN'));
        $this->assertTrue($this->databaseManager->validateDataType('0', 'TINYINT(1)'));
        $this->assertTrue($this->databaseManager->validateDataType('12:30:00', 'TIME'));
        $this->assertTrue($this->databaseManager->validateDataType('2024-01-01', 'DATE'));
        $this->assertTrue($this->databaseManager->validateDataType('test', 'VARCHAR(10)'));
        $this->assertFalse($this->databaseManager->validateDataType('invalid json', 'JSON'));
        $this->assertTrue($this->databaseManager->validateDataType('{"key":"value"}', 'JSON'));
        $this->assertTrue($this->databaseManager->validateDataType('any value', 'UNKNOWN_TYPE'));
        $this->assertTrue($this->databaseManager->validateDataType('2024-01-01T12:30', 'DATETIME'));
        $this->assertTrue($this->databaseManager->validateDataType('2024-01-01 12:30:00', 'DATETIME'));
        $this->assertFalse($this->databaseManager->validateDataType('long_text_exceeding_limit', 'VARCHAR(10)'));
    }

    /**
     * Test isEmptyValue
     *
     * @return void
     */
    public function testIsEmptyValue(): void
    {
        $this->assertTrue($this->databaseManager->isEmptyValue(0, 'INT'));
        $this->assertTrue($this->databaseManager->isEmptyValue(null, 'TEXT'));
        $this->assertTrue($this->databaseManager->isEmptyValue('', 'VARCHAR'));
        $this->assertFalse($this->databaseManager->isEmptyValue(1, 'BOOLEAN'));
        $this->assertFalse($this->databaseManager->isEmptyValue(0, 'TINYINT(1)'));
        $this->assertTrue($this->databaseManager->isEmptyValue(null, 'TINYINT(1)'));
        $this->assertFalse($this->databaseManager->isEmptyValue('hello', 'VARCHAR'));
    }
}
