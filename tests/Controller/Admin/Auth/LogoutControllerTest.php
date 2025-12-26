<?php

namespace App\Tests\Controller\Admin\Auth;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Admin\Auth\LogoutController;

/**
 * Class LogoutControllerTest
 *
 * Test cases for auth logout component
 *
 * @package App\Tests\Admin\Auth
 */
#[CoversClass(LogoutController::class)]
class LogoutControllerTest extends CustomTestCase
{
    /**
     * Test user logout redirect to login page
     *
     * @return void
     */
    public function testUserLogoutRedirectToLoginPage(): void
    {
        $client = static::createClient();

        // logout request
        $client->request('POST', '/logout', [
            'csrf_token' => $this->getCsrfToken($client)
        ]);

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertBrowserNotHasCookie('login-token-cookie');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
