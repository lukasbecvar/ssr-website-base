<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\PasswordChangeFormType;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

/**
 * Class PasswordChangeFormTypeTest
 *
 * Test cases for password change form
 *
 * @package App\Tests\Form
 */
class PasswordChangeFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * Test submit valid data
     *
     * @return void
     */
    public function testSubmitValidData(): void
    {
        // form valid data
        $formData = [
            'password' => 'newpassword123',
            'repassword' => 'newpassword123'
        ];

        // init password change form
        $user = new User();
        $form = $this->factory->create(PasswordChangeFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is valid
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals('newpassword123', $user->getPassword());
    }

    /**
     * Test submit invalid password too short
     *
     * @return void
     */
    public function testSubmitInvalidPasswordTooShort(): void
    {
        // form invalid data
        $formData = [
            'password' => 'short',
            'repassword' => 'short'
        ];

        // init password change form
        $user = new User();
        $form = $this->factory->create(PasswordChangeFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form['password']->getErrors()->count() > 0);
    }

    /**
     * Test submit invalid password too long
     *
     * @return void
     */
    public function testSubmitInvalidPasswordTooLong(): void
    {
        // form invalid data
        $formData = [
            'password' => str_repeat('a', 51), // too long (max 50)
            'repassword' => str_repeat('a', 51),
        ];

        // init password change form
        $user = new User();
        $form = $this->factory->create(PasswordChangeFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertFalse($form->isValid());
        $this->assertTrue($form['password']->getErrors()->count() > 0);
    }
}
