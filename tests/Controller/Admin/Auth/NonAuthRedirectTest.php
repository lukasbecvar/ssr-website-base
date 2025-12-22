<?php

namespace App\Tests\Controller\Auth;

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
        'admin' => [
            ['method' => 'GET', 'url' => '/admin'],
            ['method' => 'GET', 'url' => '/register'],
            ['method' => 'GET', 'url' => '/admin/dashboard']
        ],
        'database_browser' => [
            ['method' => 'GET', 'url' => '/admin/database'],
            ['method' => 'GET', 'url' => '/admin/database/add'],
            ['method' => 'GET', 'url' => '/admin/database/edit'],
            ['method' => 'GET', 'url' => '/admin/database/table'],
            ['method' => 'POST', 'url' => '/admin/database/delete']
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
        'admin_visitors' => [
            ['method' => 'GET', 'url' => '/admin/visitors'],
            ['method' => 'GET', 'url' => '/admin/visitors'],
            ['method' => 'GET', 'url' => '/admin/visitors'],
            ['method' => 'POST', 'url' => '/admin/visitors/ban'],
            ['method' => 'POST', 'url' => '/admin/visitors/unban'],
            ['method' => 'GET', 'url' => '/admin/visitors/delete'],
            ['method' => 'GET', 'url' => '/admin/visitors/metrics'],
            ['method' => 'GET', 'url' => '/admin/visitors/download']
        ],
        'account_settings' => [
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
