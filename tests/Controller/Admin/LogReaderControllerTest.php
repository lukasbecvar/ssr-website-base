<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use App\Controller\Admin\LogReaderController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class LogReaderControllerTest
 *
 * Test cases for log reader component
 *
 * @package App\Tests\Admin
 */
#[CoversClass(LogReaderController::class)]
class LogReaderControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load log reader page
     *
     * @return void
     */
    public function testLogReaderPage(): void
    {
        $this->client->request('GET', '/admin/logs?page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | logs');
        $this->assertAnySelectorTextContains('li', 'Logs reader');
        $this->assertAnySelectorTextContains('.card-header', 'Basic info');
        $this->assertSelectorExists('div[class="table-responsive"]');
        $this->assertSelectorExists('table[class="table table-dark text-nowrap table-hover custom-header"]');
        $this->assertSelectorExists('th[scope="col"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load log reader page when ip address not found
     *
     * @return void
     */
    public function testLogReaderPageWhenIpAddressNotFound(): void
    {
        $this->client->request('GET', '/admin/logs/whereip?page=1&ip=1001');

        // assert response
        $this->assertAnySelectorTextContains('h2', 'No relative logs were found');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load log reader page when ip address with success response
     *
     * @return void
     */
    public function testLogReaderPageWhenIpAddressWithSuccessResponse(): void
    {
        $this->client->request('GET', '/admin/logs/whereip?page=1&ip=127.0.0.1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | logs');
        $this->assertAnySelectorTextContains('li', 'Logs reader');
        $this->assertAnySelectorTextContains('.card-header', 'Basic info');
        $this->assertSelectorExists('div[class="table-responsive"]');
        $this->assertSelectorExists('table[class="table table-dark text-nowrap table-hover custom-header"]');
        $this->assertSelectorExists('th[scope="col"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load delete logs confirmation page
     *
     * @return void
     */
    public function testLoadDeleteLogsConfirmationPage(): void
    {
        $this->client->request('GET', '/admin/logs/delete?page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | confirmation');
        $this->assertAnySelectorTextContains('p', 'Are you sure you want to delete all logs?');
        $this->assertAnySelectorTextContains('button', 'Yes');
        $this->assertAnySelectorTextContains('a', 'No');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test set all logs status to readed
     *
     * @return void
     */
    public function testSetAllLogsStatusToReaded(): void
    {
        $this->client->request('POST', '/admin/logs/readed/all', [
            'csrf_token' => $this->getCsrfToken($this->client)
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
