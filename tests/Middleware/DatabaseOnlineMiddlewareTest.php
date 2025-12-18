<?php

namespace Tests\Middleware;

use Exception;
use App\Manager\ErrorManager;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use App\Middleware\DatabaseOnlineMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseOnlineMiddlewareTest
 *
 * Test cases for database online middleware
 *
 * @package App\Tests\Middleware
 */
class DatabaseOnlineMiddlewareTest extends TestCase
{
    private DatabaseOnlineMiddleware $middleware;
    private ErrorManager & MockObject $errorManagerMock;
    private Connection & MockObject $doctrineConnectionMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->doctrineConnectionMock = $this->createMock(Connection::class);

        // create database online middleware instance
        $this->middleware = new DatabaseOnlineMiddleware(
            $this->errorManagerMock,
            $this->doctrineConnectionMock
        );
    }

    /**
     * Test check database connection when database is online
     *
     * @return void
     */
    public function testCheckDatabaseConnectionWhenDatabaseIsOnline(): void
    {
        // expect query execute
        $this->doctrineConnectionMock->expects($this->once())->method('executeQuery')->with('SELECT 1');

        // expect error handling not to be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test check database connection when database is offline
     *
     * @return void
     */
    public function testCheckDatabaseConnectionWhenDatabaseIsOffline(): void
    {
        // mock connection exception
        $exceptionMessage = 'Connection refused';
        $this->doctrineConnectionMock->expects($this->once())
            ->method('executeQuery')->with('SELECT 1')->willThrowException(new Exception($exceptionMessage));

        // expect error handling call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'database connection error: ' . $exceptionMessage,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested middleware
        $this->middleware->onKernelRequest();
    }
}
