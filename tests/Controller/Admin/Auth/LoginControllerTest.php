<?php

namespace App\Tests\Controller\Admin\Auth;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Controller\Admin\Auth\LoginController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class LoginControllerTest
 *
 * Test cases for auth login component
 *
 * @package App\Tests\Admin\Auth
 */
#[CoversClass(LoginController::class)]
class LoginControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load login page when user is already logged in
     *
     * @return void
     */
    public function testLoadLoginPageWhenUserIsAlreadyLoggedIn(): void
    {
        // simulate user login
        $this->simulateLogin($this->client);

        // send request to login page
        $this->client->request('GET', '/login');

        // assert response (redirect to dashboard)
        $this->assertResponseRedirects('/admin/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load login page
     *
     * @return void
     */
    public function testLoadLoginPage(): void
    {
        $this->client->request('GET', '/login');

        // assert response
        $this->assertSelectorTextContains('h1', 'Dashboard login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorExists('button:contains("Sign in")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login form with empty fields
     *
     * @return void
     */
    public function testSubmitLoginFormWithEmptyFields(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // set form inputs
        $form = $crawler->selectButton('Sign in')->form();
        $form['login_form[username]'] = '';
        $form['login_form[password]'] = '';

        // submit form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h1', 'Dashboard login');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login form with incorrect credentials
     *
     * @return void
     */
    public function testSubmitLoginFormWithIncorrectCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // set form inputs
        $form = $crawler->selectButton('Sign in')->form();
        $form['login_form[username]'] = 'username_1234_848481';
        $form['login_form[password]'] = 'password_1234_231622';

        // submit form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h1', 'Dashboard login');
        $this->assertSelectorTextContains('body', 'Incorrect username or password');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login form with incorrect username
     *
     * @return void
     */
    public function testSubmitLoginFormWithIncorrectUsername(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // set form inputs
        $form = $crawler->selectButton('Sign in')->form();
        $form['login_form[username]'] = 'username_1234_848481';
        $form['login_form[password]'] = 'test_password';

        // submit form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h1', 'Dashboard login');
        $this->assertSelectorTextContains('body', 'Incorrect username or password');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login form with incorrect password
     *
     * @return void
     */
    public function testSubmitLoginFormWithIncorrectPassword(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // set form inputs
        $form = $crawler->selectButton('Sign in')->form();
        $form['login_form[username]'] = 'test_username';
        $form['login_form[password]'] = 'password_1234_231622';

        // submit form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h1', 'Dashboard login');
        $this->assertSelectorTextContains('body', 'Incorrect username or password');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login form with valid credentials
     *
     * @return void
     */
    public function testSubmitLoginFormWithValidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // set form inputs
        $form = $crawler->selectButton('Sign in')->form();
        $form['login_form[username]'] = 'test';
        $form['login_form[password]'] = 'test';

        // submit form
        $this->client->submit($form);

        // assert response (redirect to dashboard)
        $this->assertResponseRedirects('/admin/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        // follow redirect (for check if is valid)
        $crawler = $this->client->followRedirect();

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
