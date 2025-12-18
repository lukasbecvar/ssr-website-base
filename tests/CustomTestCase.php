<?php

namespace App\Tests;

use DateTime;
use App\Entity\User;
use App\Manager\AuthManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CustomTestCase
 *
 * Custom test case class that extends the WebTestCase class
 *
 * @package App\Tests
 */
class CustomTestCase extends WebTestCase
{
    /**
     * Simulate user login
     *
     * @param KernelBrowser $client The KernelBrowser instance
     *
     * @return void
     */
    public function simulateLogin(KernelBrowser $client, string $role = 'Owner'): void
    {
        // create a mock user
        $user = new User();
        $user->setUsername('test_username');
        $user->setPassword(password_hash('test_password', PASSWORD_BCRYPT));
        $user->setRole($role);
        $user->setIpAddress('127.0.0.1');
        $user->setToken('zbjNNyuudM3HQGWe6xqWwjyncbtZB22D');
        $user->setRegistedTime(new DateTime());
        $user->setLastLoginTime(null);
        $user->setProfilePic('image');
        $user->setVisitorId(1);

        // create a mock of AuthManager
        $authManager = $this->createMock(AuthManager::class);

        // simulate user login
        $authManager->method('isUserLogedin')->willReturn(true);

        // simulate user role
        $authManager->method('getUserRole')->willReturn($role);

        // mock test user repository
        $authManager->method('getUserRepository')->willReturn($user);

        // replace actual auth manager with mocked
        $client->getContainer()->set(AuthManager::class, $authManager);
    }

    /**
     * Allow registration component
     *
     * @param KernelBrowser $client The KernelBrowser instance
     * @param bool $allow The allow registration flag
     *
     * @return void
     */
    public function allowRegistration(KernelBrowser $client, bool $allow = true): void
    {
        $authManagerMock = $this->createMock(AuthManager::class);
        $authManagerMock->method('isRegisterPageAllowed')->willReturn($allow);
        $client->getContainer()->set(AuthManager::class, $authManagerMock);
    }
}
