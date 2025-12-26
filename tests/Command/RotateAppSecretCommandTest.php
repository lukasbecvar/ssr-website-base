<?php

namespace App\Tests\Command;

use Exception;
use App\Util\AppUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\MessagesManager;
use App\Command\RotateAppSecretCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RotateAppSecretCommandTest
 *
 * Test cases for execute secret key rotation command
 *
 * @package App\Tests\Command
 */
#[CoversClass(RotateAppSecretCommand::class)]
class RotateAppSecretCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private AppUtil & MockObject $appUtil;
    private RotateAppSecretCommand $command;
    private MessagesManager & MockObject $messagesManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->messagesManager = $this->createMock(MessagesManager::class);

        // initialize command instance
        $this->command = new RotateAppSecretCommand($this->appUtil, $this->messagesManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command successfully
     *
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        // mock old APP_SECRET
        $this->appUtil->expects($this->once())->method('getEnvValue')->with('APP_SECRET')->willReturn('old-secret-value');

        // mock generateKey
        $this->appUtil->expects($this->once())->method('generateKey')->with(16)->willReturn('new-secret-value');

        // expect update env value to be called
        $this->appUtil->expects($this->once())->method('updateEnvValue')->with('APP_SECRET', 'new-secret-value');

        // expect re-encrypt messages to be called
        $this->messagesManager->expects($this->once())->method('reEncryptMessages')->with('old-secret-value', 'new-secret-value');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('APP_SECRET has been rotated successfully', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with exception
     *
     * @return void
     */
    public function testExecuteThrowsException(): void
    {
        // mock generateKey to throw exception
        $this->appUtil->method('generateKey')->willThrowException(new Exception('Something went wrong'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Error during rotation: Something went wrong', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when re-encrypt messages throws exception
     *
     * @return void
     */
    public function testExecuteWhenReEncryptMessagesThrowsException(): void
    {
        // mock getEnvValue for old secret
        $this->appUtil->expects($this->once())->method('getEnvValue')->with('APP_SECRET')->willReturn('old-secret');

        // mock generateKey
        $this->appUtil->expects($this->once())->method('generateKey')->with(16)->willReturn('new-secret');

        // mock updateEnvValue
        $this->appUtil->expects($this->once())->method('updateEnvValue')->with('APP_SECRET', 'new-secret');

        // mock reEncryptMessages to throw an exception
        $this->messagesManager->expects($this->once())->method('reEncryptMessages')
            ->willThrowException(new Exception('Re-encryption failed'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Error during rotation: Re-encryption failed', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
