<?php

namespace App\Tests\Controller\Admin\Auth;

use App\Tests\CustomTestCase;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class RegisterControllerTest
 *
 * Test cases for auth register component
 *
 * @package App\Tests\Admin\Auth
 */
class RegisterControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load register page when registration is not allowed
     *
     * @return void
     */
    public function testLoadRegisterPageWhenRegistrationIsNotAllowed(): void
    {
        // simulate registration not allowed
        $this->allowRegistration($this->client, false);

        // load register page
        $this->client->request('GET', '/register');

        // assert response
        $this->assertSelectorNotExists('.form-title');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load register page when registration is allowed
     *
     * @return void
     */
    public function testLoadRegisterPageWhenRegistrationIsAllowed(): void
    {
        // simulate registration allowed
        $this->allowRegistration($this->client);

        // load register page
        $this->client->request('GET', '/register');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | Login');
        $this->assertSelectorTextContains('.login-card-title', 'Create administrator account');
        $this->assertSelectorExists('form[name="register_form"]');
        $this->assertSelectorExists('input[name="register_form[username]"]');
        $this->assertSelectorExists('input[name="register_form[password]"]');
        $this->assertSelectorExists('input[name="register_form[re-password]"]');
        $this->assertSelectorExists('button:contains("Create account")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with empty fields
     *
     * @return void
     */
    public function testSubmitRegisterFormWithEmptyFields(): void
    {
        // simulate registration allowed
        $this->allowRegistration($this->client, true);

        // submit register form
        $this->client->request('POST', '/register', [
            'register_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'register_form'),
                'username' => '',
                'password' => '',
                're-password' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
        $this->assertSelectorTextContains('li:contains("Please enter a password again")', 'Please enter a password again');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with low length fields
     *
     * @return void
     */
    public function testSubmitRegisterFormWithLowLengthFields(): void
    {
        // simulate registration allowed
        $this->allowRegistration($this->client, true);

        // submit register form
        $this->client->request('POST', '/register', [
            'register_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'register_form'),
                'username' => 'a',
                'password' => 'a',
                're-password' => 'a'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('li:contains("Your username should be at least 4 characters")', 'Your username should be at least 4 characters');
        $this->assertSelectorTextContains('li:contains("Your password should be at least 8 characters")', 'Your password should be at least 8 characters');
        $this->assertSelectorTextContains('li:contains("Your password again should be at least 8 characters")', 'Your password again should be at least 8 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with high length fields
     *
     * @return void
     */
    public function testSubmitRegisterFormWithHighLengthFields(): void
    {
        // simulate registration allowed
        $this->allowRegistration($this->client, true);

        // submit register form
        $this->client->request('POST', '/register', [
            'register_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'register_form'),
                'username' => 'awfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeewawfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeew',
                'password' => 'awfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeewawfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeew',
                're-password' => 'awfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeewawfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeew'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('li:contains("This value is too long. It should have 50 characters or less.")', 'This value is too long. It should have 50 characters or less.');
        $this->assertSelectorTextContains('li:contains("This value is too long. It should have 80 characters or less.")', 'This value is too long. It should have 80 characters or less.');
        $this->assertSelectorTextContains('li:contains("This value is too long. It should have 80 characters or less.")', 'This value is too long. It should have 80 characters or less.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with passwords not match
     *
     * @return void
     */
    public function testSubmitRegisterFormWithNotMatchPasswords(): void
    {
        // simulate registration allowed
        $this->allowRegistration($this->client, true);

        // submit register form
        $this->client->request('POST', '/register', [
            'register_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'register_form'),
                'username' => 'testing_username',
                'password' => 'testing_password_1',
                're-password' => 'testing_password_2'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('body', 'Your passwords dont match');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with valid inputs
     *
     * @return void
     */
    public function testSubmitRegisterFormWithValidInputs(): void
    {
        // simulate registration allowed
        $this->allowRegistration($this->client, true);

        // submit register form
        $this->client->request('POST', '/register', [
            'register_form' => [
                'csrf_token' => $this->getCsrfToken($this->client, 'register_form'),
                'username' => ByteString::fromRandom(16)->toString(),
                'password' => 'testing_password_1',
                're-password' => 'testing_password_1'
            ]
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
