<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DashboardControllerTest
 *
 * Test cases for admin dashboard component
 *
 * @package App\Tests\Admin
 */
class DashboardControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load dashboard page
     *
     * @return void
     */
    public function testLoadDashboardPage(): void
    {
        $this->client->request('GET', '/admin/dashboard');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | dashboard');
        $this->assertSelectorExists('main[class="admin-page"]');
        $this->assertSelectorExists('img[alt="profile_picture"]');
        $this->assertSelectorExists('span[class="role-line"]');
        $this->assertSelectorTextContains('#wrarning-box', 'Warnings');
        $this->assertSelectorTextContains('body', 'Visitors info');
        $this->assertSelectorTextContains('.card-title', 'Logs');
        $this->assertSelectorTextContains('body', 'Messages');
        $this->assertSelectorTextContains('body', 'Visitors');
        $this->assertSelectorExists('button[class="logout-link menu-button"]');
        $this->assertSelectorExists('span[class="menu-text"]');
        $this->assertSelectorExists('div[class="sidebar"]');
        $this->assertSelectorExists('a[class="s-menu-button"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
