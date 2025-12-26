<?php

namespace App\Tests\Command;

use Exception;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
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
#[CoversClass(ValidateVisitorsStructureCommand::class)]
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
        // mock fetchOne to simulate no duplicates and valid structure
        $this->doctrineConnectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(0, 1, 10, 10);

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
        // mock fetchOne to simulate duplicates
        $this->doctrineConnectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(5, 1, 10, 10, 10);

        // expect executeQuery calls
        $this->doctrineConnectionMock->expects($this->exactly(3))->method('executeQuery');

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
        $this->doctrineConnectionMock->method('fetchOne')->willThrowException(new Exception('Database error'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // assert result
        $this->assertStringContainsString('Process error: Database error', $this->commandTester->getDisplay());
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when reorganization is needed
     *
     * @return void
     */
    public function testExecuteCommandWhenReorganizationNeeded(): void
    {
        // mock fetchOne to simulate missing ids
        $this->doctrineConnectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(0, 1, 3, 2, 2, 2);

        // mock fetchAllAssociative for current visitors
        $this->doctrineConnectionMock->method('fetchAllAssociative')->willReturn([
            ['id' => 1],
            ['id' => 3]
        ]);

        // expected executeQuery calls
        $this->doctrineConnectionMock->expects($this->exactly(10))->method('executeQuery');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();
        $normalizedOutput = preg_replace('/\s+/', ' ', $output);

        // assert command output
        $this->assertStringContainsString('Missing IDs in sequence have been reorganized', $normalizedOutput);
        $this->assertStringContainsString('Foreign keys updated in 1 records', $normalizedOutput);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when sequence does not start at 1
     *
     * @return void
     */
    public function testExecuteCommandWhenSequenceDoesNotStartAtOne(): void
    {
        // mock fetchOne to simulate missing ids
        $this->doctrineConnectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(0, 5, 6, 2, 6, 2);

        // mock fetchAllAssociative for current visitors (ids 5 and 6)
        $this->doctrineConnectionMock->method('fetchAllAssociative')->willReturn([
            ['id' => 5],
            ['id' => 6]
        ]);

        // expect executeQuery calls
        $this->doctrineConnectionMock->expects($this->exactly(13))->method('executeQuery');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();
        $normalizedOutput = preg_replace('/\s+/', ' ', $output);

        // assert result
        $this->assertStringContainsString('4 missing ID(s) at the beginning of sequence have been reorganized', $normalizedOutput);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
