<?php

namespace App\Tests\Controller;

use App\Tests\CustomTestCase;
use App\Controller\AntilogController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AntilogControllerTest
 *
 * Test cases for antilog component
 *
 * @package App\Tests
 */
#[CoversClass(AntilogController::class)]
class AntilogControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test enable antilog when user is unauthorized
     *
     * @return void
     */
    public function testEnableAntiLogWhenUserIsUnauthorized(): void
    {
        // simulate login
        $this->simulateLogin($this->client, 'User');

        $this->client->request('POST', '/antilog/5369362536', [
            'csrf_token' => $this->getCsrfToken($this->client)
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test enable antilog when user is authorized
     *
     * @return void
     */
    public function testEnableAntiLogWhenUserIsAuthorized(): void
    {
        // simulate login
        $this->simulateLogin($this->client, 'Admin');

        $this->client->request('POST', '/antilog/5369362536', [
            'csrf_token' => $this->getCsrfToken($this->client)
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/logs');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
