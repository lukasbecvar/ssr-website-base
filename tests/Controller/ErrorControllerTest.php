<?php

namespace App\Tests\Controller;

use App\Manager\ErrorManager;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ErrorControllerTest
 *
 * Test cases for error handling
 *
 * @package App\Tests
 */
#[CoversClass(ErrorManager::class)]
class ErrorControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load default error page
     *
     * @return void
     */
    public function testLoadDefaultErrorPage(): void
    {
        $this->client->request('GET', '/error');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: unknown');
        $this->assertSelectorTextContains('.error-page-msg', 'Unknown error, please contact the service administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load banned error page (block return unknown error)
     *
     * @return void
     */
    public function testBlockBannedErrorPage(): void
    {
        $this->client->request('GET', '/error?code=banned');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: unknown');
        $this->assertSelectorTextContains('.error-page-msg', 'Unknown error, please contact the service administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load maintenance error page (block return unknown error)
     *
     * @return void
     */
    public function testBlockMaintenanceErrorPage(): void
    {
        $this->client->request('GET', '/error?code=maintenance');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: unknown');
        $this->assertSelectorTextContains('.error-page-msg', 'Unknown error, please contact the service administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test error for Bad Request (400)
     *
     * @return void
     */
    public function testLoad400ErrorPage(): void
    {
        $this->client->request('GET', '/error?code=400');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: Bad request');
        $this->assertSelectorTextContains('.error-page-msg', 'Request error');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test error for Unauthorized (401)
     *
     * @return void
     */
    public function testLoad401ErrorPage(): void
    {
        $this->client->request('GET', '/error?code=401');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: Unauthorized');
        $this->assertSelectorTextContains('.error-page-msg', 'You do not have permission to access this page');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test error for Forbidden (403)
     *
     * @return void
     */
    public function testLoad403ErrorPage(): void
    {
        $this->client->request('GET', '/error?code=403');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: Forbidden');
        $this->assertSelectorTextContains('.error-page-msg', 'You do not have permission to access this page');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test error for Page Not Found (404)
     *
     * @return void
     */
    public function testLoad404ErrorPage(): void
    {
        $this->client->request('GET', '/error?code=404');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: Page not found');
        $this->assertSelectorTextContains('.error-page-msg', 'Error this page was not found');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test error for Too Many Requests (429)
     *
     * @return void
     */
    public function testLoad429ErrorPage(): void
    {
        $this->client->request('GET', '/error?code=429');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: Too Many Requests');
        $this->assertSelectorTextContains('body', 'Too Many Requests');
        $this->assertSelectorTextContains('body', 'Please wait and try again later');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test error for Internal Server Error (500)
     *
     * @return void
     */
    public function testLoad500ErrorPage(): void
    {
        $this->client->request('GET', '/error?code=500');

        // assert response
        $this->assertSelectorTextContains('title', 'Error: Internal Server Error');
        $this->assertSelectorTextContains('.error-page-msg', 'The server encountered an unexpected condition that prevented it from fulfilling the reques');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
