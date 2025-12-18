<?php

namespace App\Tests\Command;

use Exception;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use App\Command\ValidateVisitorsStructureCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ValidateVisitorsStructureCommandTest
 *
 * Test cases for validate visitors structure command
 *
 * @package App\Tests\Command
 */
class ValidateVisitorsStructureCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ValidateVisitorsStructureCommand $command;
    private Connection & MockObject $doctrineConnectionMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->doctrineConnectionMock = $this->createMock(Connection::class);

        // create command instance
        $this->command = new ValidateVisitorsStructureCommand($this->doctrineConnectionMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command when no duplicates are found
     *
     * @return void
     */
    public function testExecuteCommandWhenNoDuplicatesFound(): void
    {
        // mock fetchOne to simulate no duplicates
        $this->doctrineConnectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(0);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // assert result
        $this->assertStringContainsString('No validation or reorganization needed', $this->commandTester->getDisplay());
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when duplicates are found
     *
     * @return void
     */
    public function testExecuteCommandWhenDuplicatesFound(): void
    {
        // mock fetchOne to simulate duplicates and max id
        $this->doctrineConnectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(5, 10);

        // expect executeQuery calls
        $this->doctrineConnectionMock->expects($this->exactly(4))->method('executeQuery');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // assert result
        $this->assertStringContainsString('5 duplicate record(s) have been deleted', $this->commandTester->getDisplay());
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when exception is thrown
     *
     * @return void
     */
    public function testExecuteCommandWhenExceptionIsThrown(): void
    {
        // mock fetchOne to throw an exception
        $this->doctrineConnectionMock->method('fetchOne')->willThrowException(
            new Exception('Database error')
        );

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // assert result
        $this->assertStringContainsString('Process error: Database error', $this->commandTester->getDisplay());
        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
