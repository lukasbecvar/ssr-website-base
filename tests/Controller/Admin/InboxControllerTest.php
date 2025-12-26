<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use App\Controller\Admin\InboxController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class InboxControllerTest
 *
 * Test cases for inbox component
 *
 * @package App\Tests\Admin
 */
#[CoversClass(InboxController::class)]
class InboxControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load inbox page
     *
     * @return void
     */
    public function testLoadInboxPage(): void
    {
        $this->client->request('GET', '/admin/inbox');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | inbox');
        $this->assertSelectorExists('div[class="inbox-container"]');
        $this->assertSelectorExists('div[class="inbox-message-info"]');
        $this->assertSelectorExists('a[class="inbox-message-name"]');
        $this->assertSelectorExists('a[class="inbox-message-ip"]');
        $this->assertSelectorExists('span[class="inbox-message-time"]');
        $this->assertAnySelectorTextContains('.inbox-message-text', 'test message 4');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test close inbox message with nonexistent message
     *
     * @return void
     */
    public function testCloseInboxMessageWithNonexistentMessage(): void
    {
        $this->client->request('POST', '/admin/inbox/close', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'page' => 1,
            'id' => 1001
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test close inbox message with success response
     *
     * @return void
     */
    public function testCloseInboxMessageWithSuccessResponse(): void
    {
        $this->client->request('POST', '/admin/inbox/close', [
            'csrf_token' => $this->getCsrfToken($this->client),
            'page' => 1,
            'id' => 1
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/inbox?page=1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
