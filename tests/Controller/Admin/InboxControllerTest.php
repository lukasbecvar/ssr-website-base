<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class InboxControllerTest
 *
 * Test cases for inbox component
 *
 * @package App\Tests\Admin
 */
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
        $this->client->request('GET', '/admin/inbox?page=1');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | inbox');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
