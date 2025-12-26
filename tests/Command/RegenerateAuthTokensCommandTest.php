<?php

namespace App\Tests\Command;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Command\RegenerateAuthTokensCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RegenerateAuthTokensCommandTest
 *
 * Test cases for regenerate auth tokens command
 *
 * @package App\Tests\Command
 */
#[CoversClass(RegenerateAuthTokensCommand::class)]
class RegenerateAuthTokensCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private RegenerateAuthTokensCommand $command;
    private AuthManager & MockObject $authManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->authManagerMock = $this->createMock(AuthManager::class);

        // create command instance
        $this->command = new RegenerateAuthTokensCommand($this->authManagerMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command when response is failure
     *
     * @return void
     */
    public function testExecuteCommandWhenResponseIsFailure(): void
    {
        // mock regenerate users tokens
        $this->authManagerMock->expects($this->once())->method('regenerateUsersTokens')->willReturn([
            'status' => false,
            'message' => 'Error message'
        ]);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Process error: Error message', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when response is success
     *
     * @return void
     */
    public function testExecuteCommandWhenResponseIsSuccess(): void
    {
        // mock regenerate users tokens
        $this->authManagerMock->expects($this->once())->method('regenerateUsersTokens')->willReturn([
            'status' => true
        ]);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('All tokens is regenerated', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
