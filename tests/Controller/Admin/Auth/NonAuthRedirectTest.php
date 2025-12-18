<?php

namespace App\Tests\Controller\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NonAuthRedirectTest
 *
 * Test redirect non authenticated users to login page for admin page routes
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
     * @return array<array<string>>
     */
    private const ROUTES = [
        'admin' => [
            '/admin',
            'register',
            '/admin/dashboard'
        ],
        'database_browser' => [
            '/admin/database',
            '/admin/database/add',
            '/admin/database/edit',
            '/admin/database/table',
            '/admin/database/delete'
        ],
        'admin_inbox' => [
            '/admin/inbox',
            '/admin/inbox/close'
        ],
        'admin_logs' => [
            '/admin/logs',
            '/admin/logs/delete',
            '/admin/logs/whereip',
            '/admin/logs/readed/all'
        ],
        'admin_visitors' => [
            '/admin/visitors',
            '/admin/visitors/ban',
            '/admin/visitors/unban',
            '/admin/visitors/delete',
            '/admin/visitors/metrics',
            '/admin/visitors/download'
        ],
        'account_settings' => [
            '/admin/account/settings',
            '/admin/account/settings/pic',
            '/admin/account/settings/username',
            '/admin/account/settings/password'
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
                $urls[] = [$route];
            }
        }
        return $urls;
    }

    /**
     * Test non authenticated requests to admin routes redirect to login page
     *
     * @param string $url The admin route URL
     *
     * @return void
     */
    #[DataProvider('provideAdminUrls')]
    public function testNonAuthenticatedRequestsToAdminRoutesRedirectToLogin(string $url): void
    {
        $this->client->request('GET', $url);

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
