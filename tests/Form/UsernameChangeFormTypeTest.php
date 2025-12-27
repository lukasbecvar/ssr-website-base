<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\UsernameChangeFormType;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

/**
 * Class UsernameChangeFormTypeTest
 *
 * Test cases for username change form
 *
 * @package App\Tests\Form
 */
class UsernameChangeFormTypeTest extends TypeTestCase
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
            'username' => 'new_username'
        ];

        // init username change form
        $user = new User();
        $form = $this->factory->create(UsernameChangeFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is valid
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals('new_username', $user->getUsername());
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
            'username' => 'usr'
        ];

        // init username change form
        $user = new User();
        $form = $this->factory->create(UsernameChangeFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertFalse($form->isValid());
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
            'username' => str_repeat('a', 51) // too long (max 50)
        ];

        // init username change form
        $user = new User();
        $form = $this->factory->create(UsernameChangeFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and form is invalid
        $this->assertFalse($form->isValid());
        $this->assertTrue($form['username']->getErrors()->count() > 0);
    }
}
