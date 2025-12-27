<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\ProfilePicChangeFormType;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

/**
 * Class ProfilePicChangeFormTypeTest
 *
 * Test cases for profile pic change form
 *
 * @package App\Tests\Form
 */
class ProfilePicChangeFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * Test submit no file
     *
     * @return void
     */
    public function testSubmitNoFile(): void
    {
        // form invalid data
        $formData = [
            'profile-pic' => ''
        ];

        // init profile pic change form
        $user = new User();
        $form = $this->factory->create(ProfilePicChangeFormType::class, $user);
        $form->submit($formData);

        // should fail because of NotBlank constraint
        $this->assertFalse($form->isValid());
    }
}
