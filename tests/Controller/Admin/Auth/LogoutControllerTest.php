<?php

namespace App\Tests\Controller\Admin\Auth;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class LogoutControllerTest
 *
 * Test cases for auth logout component
 *
 * @package App\Tests\Admin\Auth
 */
class LogoutControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test user logout redirect to login page
     *
     * @return void
     */
    public function testUserLogoutRedirectToLoginPage(): void
    {
        $this->client->request('POST', '/logout', [
            'csrf_token' => $this->getCsrfToken($this->client)
        ]);

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertBrowserNotHasCookie('login-token-cookie');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
