<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class LogReaderControllerTest
 *
 * Test cases for log reader component
 *
 * @package App\Tests\Admin
 */
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
        $this->assertSelectorTextContains('body', 'Logs reader');
        $this->assertSelectorTextContains('body', 'Basic info');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load log delete confirmation
     *
     * @return void
     */
    public function testLoadLogDeleteConfirmation(): void
    {
        $this->client->request('GET', '/admin/logs/delete');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | confirmation');
        $this->assertSelectorTextContains('body', 'Are you sure you want to delete all logs?');
        $this->assertSelectorTextContains('body', 'Yes');
        $this->assertSelectorTextContains('body', 'No');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
