<?php

namespace App\Tests\Controller\Admin;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AccountSettingsControllerTest
 *
 * Test cases for account settings component
 *
 * @package App\Tests\Admin
 */
class AccountSettingsControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load account settings table page
     *
     * @return void
     */
    public function testLoadAccountSettingsTablePage(): void
    {
        $this->client->request('GET', '/admin/account/settings');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | settings');
        $this->assertSelectorTextContains('.card-header', 'Account settings');
        $this->assertSelectorTextContains('body', 'Profile Picture');
        $this->assertSelectorTextContains('body', 'Username');
        $this->assertSelectorTextContains('body', 'Password');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load account settings change profile picture form
     *
     * @return void
     */
    public function testLoadAccountSettingsChangePicForm(): void
    {
        $this->client->request('GET', '/admin/account/settings/pic');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | settings');
        $this->assertSelectorTextContains('.card-header', 'Change profile image');
        $this->assertSelectorTextContains('.input-button', 'Save picture');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load account settings change username form
     *
     * @return void
     */
    public function testLoadAccountSettingsChangeUsernameForm(): void
    {
        $this->client->request('GET', '/admin/account/settings/username');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | settings');
        $this->assertSelectorTextContains('.card-header', 'Change username');
        $this->assertSelectorTextContains('button', 'Change username');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit account settings change username form with empty username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeUsernameFormWithEmptyUsername(): void
    {
        $this->client->request('POST', '/admin/account/settings/username', [
            'username_change_form' => [
                'username' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('.card-header', 'Change username');
        $this->assertSelectorTextContains('button', 'Change username');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
    }

    /**
     * Test submit account settings change username form with short username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeUsernameFormWithShortUsername(): void
    {
        $this->client->request('POST', '/admin/account/settings/username', [
            'username_change_form' => [
                'username' => 'a'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('.card-header', 'Change username');
        $this->assertSelectorTextContains('button', 'Change username');
        $this->assertSelectorTextContains('li:contains("Your username should be at least 4 characters")', 'Your username should be at least 4 characters');
    }

    /**
     * Test submit account settings change username form with long username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeUsernameFormWithLongUsername(): void
    {
        $this->client->request('POST', '/admin/account/settings/username', [
            'username_change_form' => [
                'username' => 'awfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeewawfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeew'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('li:contains("This value is too long. It should have 50 characters or less.")', 'This value is too long. It should have 50 characters or less.');
    }

    /**
     * Test submit account settings change username form with correct username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeUsernameFormWithCorrectUsername(): void
    {
        $this->client->request('POST', '/admin/account/settings/username', [
            'username_change_form' => [
                'username' => 'testing_username'
            ]
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/account/settings');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load account settings change password form
     *
     * @return void
     */
    public function testLoadAccountSettingsChangePasswordForm(): void
    {
        $this->client->request('GET', '/admin/account/settings/password');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin | settings');
        $this->assertSelectorTextContains('.card-header', 'Change password');
        $this->assertSelectorExists('form[name="password_change_form"]');
        $this->assertSelectorExists('input[name="password_change_form[password]"]');
        $this->assertSelectorExists('input[name="password_change_form[repassword]"]');
        $this->assertSelectorExists('button:contains("Change password")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit account settings change password form with empty password
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangePasswordFormWithEmptyPassword(): void
    {
        $this->client->request('POST', '/admin/account/settings/password', [
            'password_change_form' => [
                'password' => '',
                'repassword' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('.card-header', 'Change password');
        $this->assertSelectorTextContains('button', 'Change password');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
        $this->assertSelectorTextContains('li:contains("Please enter a repassword")', 'Please enter a repassword');
    }

    /**
     * Test submit account settings change password form with no-matching passwords
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangePasswordFormWithNoMatchPasswords(): void
    {
        $this->client->request('POST', '/admin/account/settings/password', [
            'password_change_form' => [
                'password' => 'testing_password_1',
                'repassword' => 'testing_password_2'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('body', 'Your passwords is not match!');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit account settings change password form with short password
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangePasswordFormWithShortPassword(): void
    {
        // build post request
        $this->client->request('POST', '/admin/account/settings/password', [
            'password_change_form' => [
                'password' => 'a',
                'repassword' => 'a'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('.card-header', 'Change password');
        $this->assertSelectorTextContains('button', 'Change password');
        $this->assertSelectorTextContains('li:contains("Your password should be at least 8 characters")', 'Your password should be at least 8 characters');
        $this->assertSelectorTextContains('li:contains("Your password should be at least 8 characters")', 'Your password should be at least 8 characters');
    }

    /**
     * Test submit account settings change password form with long password
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangePasswordFormWithLongPassword(): void
    {
        $this->client->request('POST', '/admin/account/settings/password', [
            'password_change_form' => [
                'password' => 'awfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeewawfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeew',
                'repassword' => 'awfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeewawfeewfawfeewfawfeewfawfeewfawfeewfawfeewfawfeew'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('li:contains("This value is too long. It should have 50 characters or less.")', 'This value is too long. It should have 50 characters or less.');
    }

    /**
     * Test submit account settings change password form with correct password
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangePasswordFormWithCorrectPassword(): void
    {
        $this->client->request('POST', '/admin/account/settings/password', [
            'password_change_form' => [
                'password' => 'testing_password_1',
                'repassword' => 'testing_password_1'
            ]
        ]);

        // assert response
        $this->assertResponseRedirects('/admin/account/settings');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
