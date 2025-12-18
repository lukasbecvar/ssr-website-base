<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class VisitorManagerControllerTest
 *
 * Test cases for visitor manager component
 *
 * @package App\Tests\Admin
 */
class VisitorManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load visitor manager page
     *
     * @return void
     */
    public function testLoadVisitorManagerPage(): void
    {
        $this->client->request('GET', '/admin/visitors?page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | visitors');
        $this->assertSelectorTextContains('body', 'Online visitors');
        $this->assertSelectorTextContains('body', 'Banned visitors');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load visitor manager delete page
     *
     * @return void
     */
    public function testLoadvisitorExportPage(): void
    {
        $this->client->request('GET', '/admin/visitors/download');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | visitors export');
        $this->assertSelectorTextContains('body', 'Download visitors data');
        $this->assertSelectorTextContains('body', 'Export data');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load visitor manager delete page
     *
     * @return void
     */
    public function testLoadvisitorMetricsPage(): void
    {
        $this->client->request('GET', '/admin/visitors/metrics');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | visitors');
        $this->assertSelectorTextContains('body', 'Browser');
        $this->assertSelectorTextContains('body', 'Country');
        $this->assertSelectorTextContains('body', 'City');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
