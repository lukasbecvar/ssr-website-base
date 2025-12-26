<?php

namespace App\Tests\Controller\Admin\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NonAuthRedirectTest
 *
 * Test for redirect non-authenticated users to login page for admin page routes
 *
 * @package App\Tests\Controller\Auth
 */
class NonAuthRedirectTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Auth required routes list
     *
     * @return array<array<string,string>>
     */
    private const ROUTES = [
        'admin_init' => [
            ['method' => 'GET', 'url' => '/admin'],
            ['method' => 'GET', 'url' => '/register'],
        ],
        'admin_dashboard' => [
            ['method' => 'GET', 'url' => '/admin/dashboard']
        ],
        'admin_inbox' => [
            ['method' => 'GET', 'url' => '/admin/inbox'],
            ['method' => 'POST', 'url' => '/admin/inbox/close']
        ],
        'admin_logs' => [
            ['method' => 'GET', 'url' => '/admin/logs'],
            ['method' => 'GET', 'url' => '/admin/logs/delete'],
            ['method' => 'GET', 'url' => '/admin/logs/whereip'],
            ['method' => 'POST', 'url' => '/admin/logs/readed/all']
        ],
        'admin_database' => [
            ['method' => 'GET', 'url' => '/admin/database'],
            ['method' => 'GET', 'url' => '/admin/database/add'],
            ['method' => 'GET', 'url' => '/admin/database/edit'],
            ['method' => 'GET', 'url' => '/admin/database/table'],
            ['method' => 'POST', 'url' => '/admin/database/delete']
        ],
        'admin_visitor_manager' => [
            ['method' => 'GET', 'url' => '/admin/visitors'],
            ['method' => 'POST', 'url' => '/admin/visitors/ban'],
            ['method' => 'POST', 'url' => '/admin/visitors/unban'],
            ['method' => 'GET', 'url' => '/admin/visitors/delete'],
            ['method' => 'GET', 'url' => '/admin/visitors/ipinfo'],
            ['method' => 'GET', 'url' => '/admin/visitors/metrics'],
            ['method' => 'GET', 'url' => '/admin/visitors/download']
        ],
        'admin_account_settings' => [
            ['method' => 'GET', 'url' => '/admin/account/settings'],
            ['method' => 'GET', 'url' => '/admin/account/settings/pic'],
            ['method' => 'GET', 'url' => '/admin/account/settings/username'],
            ['method' => 'GET', 'url' => '/admin/account/settings/password'],
            ['method' => 'POST', 'url' => '/admin/account/settings/reset-token']
        ]
    ];

    /**
     * Admin routes list provider
     *
     * @return array<int,array<int,string>>
     */
    public static function provideAdminUrls(): array
    {
        $urls = [];
        foreach (self::ROUTES as $category => $routes) {
            foreach ($routes as $route) {
                $urls[] = [$route['method'], $route['url']];
            }
        }
        return $urls;
    }

    /**
     * Test requests to admin routes that require authentication
     *
     * @param string $method The HTTP method
     * @param string $url The admin route URL
     *
     * @return void
     */
    #[DataProvider('provideAdminUrls')]
    public function testNonAuthRedirect(string $method, string $url): void
    {
        $this->client->request($method, $url);

        // assert response
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
