<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\String\ByteString;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Admin\DatabaseBrowserController;

/**
 * Class DatabaseBrowserControllerTest
 *
 * Test cases for database browser component
 *
 * @package App\Tests\Admin
 */
#[CoversClass(DatabaseBrowserController::class)]
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
        $this->assertAnySelectorTextContains('.database-table-name', 'users');
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
        $this->assertSelectorTextContains('body', 'registered_time');
        $this->assertSelectorTextContains('body', 'last_login_time');
        $this->assertSelectorTextContains('body', 'profile_pic');
        $this->assertSelectorTextContains('body', 'visitor_id');
        $this->assertSelectorTextContains('body', 'Edit');
        $this->assertSelectorTextContains('body', 'X');
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
        $this->assertAnySelectorTextContains('h3', 'Edit Record');
        $this->assertAnySelectorTextContains('strong', 'users');
        $this->assertSelectorExists('span[class="field-name"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load edit database record form with nonexistent row
     *
     * @return void
     */
    public function testLoadEditDatabaseRecordFormWithNonexistentRow(): void
    {
        $this->client->request('GET', '/admin/database/edit?table=users&page=1&id=1001');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test submit edit database record with empty field
     *
     * @return void
     */
    public function testSubmitEditDatabaseRecordWithEmptyField(): void
    {
        $this->client->request('POST', '/admin/database/edit?table=users&page=1&id=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'username' => '',
            'password' => '$argon2id$v=19$m=131072,t=4,p=4$YjFHYjFpSjdnN0czUElGdA$Xg2TPtEnt9Ud/HsY3TDnXfl6FkFj9dYKBdo8+89iDAw',
            'role' => 'Owner',
            'ip_address' => '127.0.0.1',
            'token' => 'zHKrsWUjWZGJfi2dkpAEKrkkEpW2LHn2',
            'registered_time' => '2023-03-22T12:00',
            'last_login_time' => '2025-12-26T14:32',
            'profile_pic' => 'non-pic',
            'visitor_id' => 1001,
            'submitEdit' => 'Edit',
        ]);

        // assert response
        $this->assertAnySelectorTextContains('h3', 'Edit Record');
        $this->assertAnySelectorTextContains('strong', 'users');
        $this->assertSelectorExists('span[class="field-name"]');
        $this->assertAnySelectorTextContains('.admin-alert-message', 'username is empty');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit edit database record with invalid field
     *
     * @return void
     */
    public function testSubmitEditDatabaseRecordWithInvalidField(): void
    {
        $this->client->request('POST', '/admin/database/edit?table=users&page=1&id=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'username' => 'test',
            'password' => '$argon2id$v=19$m=131072,t=4,p=4$YjFHYjFpSjdnN0czUElGdA$Xg2TPtEnt9Ud/HsY3TDnXfl6FkFj9dYKBdo8+89iDAw',
            'role' => 'Owner',
            'ip_address' => '127.0.0.1',
            'token' => 'zHKrsWUjWZGJfi2dkpAEKrkkEpW2LHn2',
            'registered_time' => '2023-03-22T12:00',
            'last_login_time' => '2025-12-26T14:32',
            'profile_pic' => 'non-pic',
            'visitor_id' => 'greregeg',
            'submitEdit' => 'Edit',
        ]);

        // assert response
        $this->assertAnySelectorTextContains('h3', 'Edit Record');
        $this->assertAnySelectorTextContains('strong', 'users');
        $this->assertSelectorExists('span[class="field-name"]');
        $this->assertAnySelectorTextContains('.admin-alert-message', 'Invalid data type for visitor_id. Expected: INT');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit edit database record with nonexistent foreign key
     *
     * @return void
     */
    public function testSubmitEditDatabaseRecordWithNonexistentForeignKey(): void
    {
        $this->client->request('POST', '/admin/database/edit?table=users&page=1&id=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'username' => 'test',
            'password' => '$argon2id$v=19$m=131072,t=4,p=4$YjFHYjFpSjdnN0czUElGdA$Xg2TPtEnt9Ud/HsY3TDnXfl6FkFj9dYKBdo8+89iDAw',
            'role' => 'Owner',
            'ip_address' => '127.0.0.1',
            'token' => 'zHKrsWUjWZGJfi2dkpAEKrkkEpW2LHn2',
            'registered_time' => '2023-03-22T12:00',
            'last_login_time' => '2025-12-26T14:32',
            'profile_pic' => 'non-pic',
            'visitor_id' => 4854841515,
            'submitEdit' => 'Edit',
        ]);

        // assert response
        $this->assertAnySelectorTextContains('h3', 'Edit Record');
        $this->assertAnySelectorTextContains('strong', 'users');
        $this->assertSelectorExists('span[class="field-name"]');
        $this->assertAnySelectorTextContains('.admin-alert-message', "Foreign key constraint violation: Value '4854841515' does not exist in table 'visitors' column 'id'");
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit edit database record with success response
     *
     * @return void
     */
    public function testSubmitEditDatabaseRecordWithSuccessResponse(): void
    {
        $this->client->request('POST', '/admin/database/edit?table=users&page=1&id=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'username' => 'test',
            'password' => '$argon2id$v=19$m=131072,t=4,p=4$YjFHYjFpSjdnN0czUElGdA$Xg2TPtEnt9Ud/HsY3TDnXfl6FkFj9dYKBdo8+89iDAw',
            'role' => 'Owner',
            'ip_address' => '127.0.0.1',
            'token' => 'zHKrsWUjWZGJfi2dkpAEKrkkEpW2LHn2',
            'registered_time' => '2023-03-22T12:00',
            'last_login_time' => '2025-12-26T14:32',
            'profile_pic' => 'non-pic',
            'visitor_id' => 1001,
            'submitEdit' => 'Edit',
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/database/table?table=users&page=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load add new record form
     *
     * @return void
     */
    public function testLoadAddNewRecordForm(): void
    {
        $this->client->request('GET', '/admin/database/add?table=users&page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | database');
        $this->assertAnySelectorTextContains('h3', 'Add New Record');
        $this->assertAnySelectorTextContains('strong', 'users');
        $this->assertSelectorExists('span[class="field-name"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit add new record with empty field
     *
     * @return void
     */
    public function testSubmitAddNewRecordWithEmptyField(): void
    {
        $this->client->request('POST', '/admin/database/add?table=users&page=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'username' => '',
            'password' => '$argon2id$v=19$m=131072,t=4,p=4$YjFHYjFpSjdnN0czUElGdA$Xg2TPtEnt9Ud/HsY3TDnXfl6FkFj9dYKBdo8+89iDAw',
            'role' => 'Owner',
            'ip_address' => '127.0.0.1',
            'token' => 'zHKrsWUjWZGJfi2dkpAEKrkkEpW2LHn2',
            'registered_time' => '2023-03-22T12:00',
            'last_login_time' => '2025-12-26T14:32',
            'profile_pic' => 'non-pic',
            'visitor_id' => 1001,
            'submitSave' => 'SAVE',
        ]);

        // assert response
        $this->assertAnySelectorTextContains('.admin-alert-message', 'username is empty');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit add new record with invalid field
     *
     * @return void
     */
    public function testSubmitAddNewRecordWithInvalidField(): void
    {
        $this->client->request('POST', '/admin/database/add?table=users&page=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'username' => 'test',
            'password' => '$argon2id$v=19$m=131072,t=4,p=4$YjFHYjFpSjdnN0czUElGdA$Xg2TPtEnt9Ud/HsY3TDnXfl6FkFj9dYKBdo8+89iDAw',
            'role' => 'Owner',
            'ip_address' => '127.0.0.1',
            'token' => 'zHKrsWUjWZGJfi2dkpAEKrkkEpW2LHn2',
            'registered_time' => '2023-03-22T12:00',
            'last_login_time' => '2025-12-26T14:32',
            'profile_pic' => 'non-pic',
            'visitor_id' => 'greregeg',
            'submitSave' => 'SAVE',
        ]);

        // assert response
        $this->assertAnySelectorTextContains('.admin-alert-message', 'Invalid data type for visitor_id. Expected: INT');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit add new record with success response
     *
     * @return void
     */
    public function testSubmitAddNewRecordWithSuccessResponse(): void
    {
        $this->client->request('POST', '/admin/database/add?table=users&page=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'username' => ByteString::fromRandom(16)->toString(),
            'password' => '$argon2id$v=19$m=131072,t=4,p=4$YjFHYjFpSjdnN0czUElGdA$Xg2TPtEnt9Ud/HsY3TDnXfl6FkFj9dYKBdo8+89iDAw',
            'role' => 'Owner',
            'ip_address' => '127.0.0.1',
            'token' => ByteString::fromRandom(32)->toString(),
            'registered_time' => '2023-03-22T12:00',
            'last_login_time' => '2025-12-26T14:32',
            'profile_pic' => 'non-pic',
            'visitor_id' => 1001,
            'submitSave' => 'SAVE',
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/database/table?table=users&page=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test delete record when row not found
     *
     * @return void
     */
    public function testDeleteRecordWhenRowNotFound(): void
    {
        $this->client->request('POST', '/admin/database/delete?table=users&page=1&id=1001', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'table' => 'visitors',
            'id' => 94949494,
            'page' => 1
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test delete record with success response
     *
     * @return void
     */
    public function testDeleteRecordWithSuccessResponse(): void
    {
        $this->client->request('POST', '/admin/database/delete?table=users&page=1&id=1', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'table' => 'visitors',
            'id' => 1001,
            'page' => 1
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/database/table?table=users&page=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
