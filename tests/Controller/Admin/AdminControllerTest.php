<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AdminControllerTest
 *
 * Test cases for admin init controller
 *
 * @package App\Tests\Admin
 */
class AdminControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test redirect to dashboard page when user is logged in
     *
     * @return void
     */
    public function testRedirectToDashboardPageWhenUserIsLoggedIn(): void
    {
        // simulate login
        $this->simulateLogin($this->client);

        // load admin init page
        $this->client->request('GET', '/admin');

        // assert response
        $this->assertTrue($this->client->getResponse()->isRedirect('/admin/dashboard'));
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test redirect to login page when user is not logged in
     *
     * @return void
     */
    public function testRedirectToLoginPageWhenUserIsNotLoggedIn(): void
    {
        // load admin init page
        $this->client->request('GET', '/admin/database');

        // assert response
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
