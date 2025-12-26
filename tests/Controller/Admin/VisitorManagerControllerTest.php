<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Admin\VisitorManagerController;

/**
 * Class VisitorManagerControllerTest
 *
 * Test cases for visitor manager component
 *
 * @package App\Tests\Admin
 */
#[CoversClass(VisitorManagerController::class)]
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
        $this->assertSelectorExists('div[class="table-responsive center"]');
        $this->assertAnySelectorTextContains('th', 'First site');
        $this->assertAnySelectorTextContains('th', 'Browser');
        $this->assertAnySelectorTextContains('th', 'OS');
        $this->assertAnySelectorTextContains('th', 'Address');
        $this->assertAnySelectorTextContains('th', 'Status');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load ip info page
     *
     * @return void
     */
    public function testLoadIpInfoPage(): void
    {
        $this->client->request('GET', '/admin/visitors/ipinfo?page=1&ip=127.0.0.1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | visitors');
        $this->assertSelectorTextContains('body', 'IP Information: 127.0.0.1');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load delete confirmation page
     *
     * @return void
     */
    public function testLoadDeleteConfirmationPage(): void
    {
        $this->client->request('GET', '/admin/visitors/delete?page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | confirmation');
        $this->assertAnySelectorTextContains('p', 'Are you sure you want to delete all visitors?');
        $this->assertAnySelectorTextContains('button', 'Yes');
        $this->assertAnySelectorTextContains('a', 'No');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load ban visitor page
     *
     * @return void
     */
    public function testLoadBanVisitorPage(): void
    {
        $this->client->request('GET', '/admin/visitors/ban?page=1&id=2');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | confirmation');
        $this->assertSelectorTextContains('body', 'Are you sure you want to ban this visitor?');
        $this->assertSelectorExists('form[name="ban_form"]');
        $this->assertSelectorExists('textarea[name="ban_form[ban_reason]"]');
        $this->assertSelectorExists('button:contains("Ban")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test ban visitor with success response
     *
     * @return void
     */
    public function testBanVisitorWithSuccessResponse(): void
    {
        $this->client->request('POST', '/admin/visitors/ban?page=1&id=2', [
            'ban_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'ban_form'),
                'ban_reason' => 'testing ban',
            ],
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/visitors?page=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test unban visitor with success response
     *
     * @return void
     */
    public function testUnbanVisitorWithSuccessResponse(): void
    {
        $this->client->request('POST', '/admin/visitors/unban?page=1&id=2', [
            'csrf_token' => $this->getCsrfToken($this->client),
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/visitors?page=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
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
        $this->assertAnySelectorTextNotContains('span', 'Browser');
        $this->assertAnySelectorTextNotContains('span', 'Country');
        $this->assertAnySelectorTextNotContains('span', 'City');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
