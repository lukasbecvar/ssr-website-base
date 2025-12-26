<?php

namespace App\Tests\Controller\Public;

use App\Tests\CustomTestCase;
use App\Controller\Public\ContactController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class ContactControllerTest
 *
 * Test cases for contact page
 *
 * @package App\Tests\Public
 */
#[CoversClass(ContactController::class)]
class ContactControllerTest extends CustomTestCase
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

    /**
     * Test submit contact form with name field empty
     *
     * @return void
     */
    public function testSubmitFormFail(): void
    {
        $this->client->request('POST', '/contact', [
            'contact_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'contact_form'),
                'name' => '',
                'email' => 'test@example.com',
                'message' => 'This is a test message.',
                'websiteIN' => ''
            ],
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('body', 'Please enter your username');
    }

    /**
     * Test submit contact form with success response
     *
     * @return void
     */
    public function testSubmitFormSuccess(): void
    {
        $this->client->request('POST', '/contact', [
            'contact_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'contact_form'),
                'name' => 'Test User',
                'email' => 'test@example.com',
                'message' => 'This is a test message.',
                'websiteIN' => ''
            ],
        ]);

        // assert response
        $this->assertResponseRedirects('/contact?status=ok');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
