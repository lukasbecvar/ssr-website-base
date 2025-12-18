<?php

namespace App\Tests\Controller\Public;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ContactControllerTest
 *
 * Test cases for contact page
 *
 * @package App\Tests\Public
 */
class ContactControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load contact page
     *
     * @return void
     */
    public function testLoadContactPage(): void
    {
        $this->client->request('GET', '/contact');

        // assert response
        $this->assertSelectorExists('a[class="nav-link"]');
        $this->assertSelectorExists('form[name="contact_form"]');
        $this->assertSelectorExists('input[name="contact_form[name]"]');
        $this->assertSelectorExists('input[name="contact_form[email]"]');
        $this->assertSelectorExists('textarea[name="contact_form[message]"]');
        $this->assertSelectorExists('button:contains("Submit message")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
