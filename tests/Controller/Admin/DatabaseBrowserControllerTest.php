<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DatabaseBrowserControllerTest
 *
 * Test cases for database browser component
 *
 * @package App\Tests\Admin
 */
class DatabaseBrowserControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load database browser table list
     *
     * @return void
     */
    public function testLoadDatabaseBrowserTableList(): void
    {
        $this->client->request('GET', '/admin/database');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | database');
        $this->assertSelectorTextContains('.database-section-title', 'Select table');
        $this->assertSelectorExists('a[class="db-browser-select-link"]');
        $this->assertSelectorTextContains('body', 'users');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load database table data browser
     *
     * @return void
     */
    public function testLoadDatabaseTableDataBrowser(): void
    {
        $this->client->request('GET', '/admin/database/table?table=users&page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | database');
        $this->assertSelectorNotExists('i[class="fa-arrow-left"]');
        $this->assertSelectorNotExists('i[class="fa-trash"]');
        $this->assertSelectorNotExists('i[class="fa-trash"]');
        $this->assertSelectorNotExists('i[class="selector-button"]');
        $this->assertSelectorTextContains('body', 'users');
        $this->assertSelectorTextContains('body', '#');
        $this->assertSelectorTextContains('body', 'username');
        $this->assertSelectorTextContains('body', 'password');
        $this->assertSelectorTextContains('body', 'role');
        $this->assertSelectorTextContains('body', 'ip_address');
        $this->assertSelectorTextContains('body', 'token');
        $this->assertSelectorTextContains('body', 'registed_time');
        $this->assertSelectorTextContains('body', 'last_login_time');
        $this->assertSelectorTextContains('body', 'profile_pic');
        $this->assertSelectorTextContains('body', 'visitor_id');
        $this->assertSelectorTextContains('body', 'Edit');
        $this->assertSelectorTextContains('body', 'X');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load add database record form
     *
     * @return void
     */
    public function testLoadAddDatabaseRecordForm(): void
    {
        $this->client->request('GET', '/admin/database/add?table=users&page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | database');
        $this->assertSelectorTextContains('body', 'New row');
        $this->assertSelectorTextContains('.card-header', 'Add new: users');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load edit database record form
     *
     * @return void
     */
    public function testLoadEditDatabaseRecordForm(): void
    {
        $this->client->request('GET', '/admin/database/edit?table=users&page=1&id=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | database');
        $this->assertSelectorTextContains('body', 'Row editor');
        $this->assertSelectorTextContains('.card-header', 'Edit users · Row 1');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
