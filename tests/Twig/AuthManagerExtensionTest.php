<?php

namespace App\Tests\Twig;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Twig\AuthManagerExtension;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AuthManagerExtensionTest
 *
 * Test cases for auth manager twig extension
 *
 * @package App\Tests\Twig
 */
class AuthManagerExtensionTest extends TestCase
{
    private AuthManager & MockObject $authManager;
    private AuthManagerExtension $authManagerExtension;

    protected function setUp(): void
    {
        // mock auth manager
        $this->authManager = $this->createMock(AuthManager::class);

        // create extension instance
        $this->authManagerExtension = new AuthManagerExtension($this->authManager);
    }

    /**
     * Test get functions
     *
     * @return void
     */
    public function testGetFunctions(): void
    {
        // call tested method
        $functions = $this->authManagerExtension->getFunctions();

        // assert result
        $this->assertCount(3, $functions);

        // verify function names and callables
        $this->assertEquals('getUserPic', $functions[0]->getName());
        $this->assertEquals([$this->authManager, 'getUserProfilePic'], $functions[0]->getCallable());
        $this->assertEquals('getUsername', $functions[1]->getName());
        $this->assertEquals([$this->authManager, 'getUsername'], $functions[1]->getCallable());
        $this->assertEquals('getUserRole', $functions[2]->getName());
        $this->assertEquals([$this->authManager, 'getUserRole'], $functions[2]->getCallable());
    }
}
