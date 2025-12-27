<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\RegisterFormType;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

/**
 * Class RegisterFormTypeTest
 *
 * Test cases for register form
 *
 * @package App\Tests\Form
 */
class RegisterFormTypeTest extends TypeTestCase
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
            'username' => 'testuser',
            'password' => 'password123',
            're-password' => 'password123'
        ];

        // init register form
        $user = new User();
        $form = $this->factory->create(RegisterFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is valid
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid()); // verify validation passes
        $this->assertEquals('testuser', $user->getUsername());
    }

    /**
     * Test submit invalid username short
     *
     * @return void
     */
    public function testSubmitInvalidUsernameShort(): void
    {
        // form invalid data
        $formData = [
            'username' => 'usr', // too short (min 4)
            'password' => 'password123',
            're-password' => 'password123'
        ];

        // init register form
        $user = new User();
        $form = $this->factory->create(RegisterFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form['username']->getErrors()->count() > 0);
    }

    /**
     * Test submit invalid username long
     *
     * @return void
     */
    public function testSubmitInvalidUsernameLong(): void
    {
        // form invalid data
        $formData = [
            'username' => str_repeat('a', 51), // too long (max 50)
            'password' => 'password123',
            're-password' => 'password123'
        ];

        // init register form
        $user = new User();
        $form = $this->factory->create(RegisterFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertFalse($form->isValid());
        $this->assertTrue($form['username']->getErrors()->count() > 0);
    }

    /**
     * Test submit invalid password short
     *
     * @return void
     */
    public function testSubmitInvalidPasswordShort(): void
    {
        // form invalid data
        $formData = [
            'username' => 'testuser',
            'password' => 'short', // too short (min 8)
            're-password' => 'short'
        ];

        // init register form
        $user = new User();
        $form = $this->factory->create(RegisterFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertFalse($form->isValid());
        $this->assertTrue($form['password']->getErrors()->count() > 0);
    }

    /**
     * Test submit invalid password long
     *
     * @return void
     */
    public function testSubmitInvalidPasswordLong(): void
    {
        // form invalid data
        $formData = [
            'username' => 'testuser',
            'password' => str_repeat('a', 81), // too long (max 80)
            're-password' => str_repeat('a', 81)
        ];

        // init register form
        $user = new User();
        $form = $this->factory->create(RegisterFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertFalse($form->isValid());
        $this->assertTrue($form['password']->getErrors()->count() > 0);
    }
}
