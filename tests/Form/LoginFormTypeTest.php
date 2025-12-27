<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Util\AppUtil;
use App\Form\LoginFormType;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

/**
 * Class LoginFormTypeTest
 *
 * Test cases for login form
 *
 * @package App\Tests\Form
 */
class LoginFormTypeTest extends TypeTestCase
{
    private AppUtil & MockObject $appUtil;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->appUtil->method('getEnvValue')->willReturn('prod');

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        // create instance of form type with the mocked dependency
        $type = new LoginFormType($this->appUtil);

        // register type instances with PreloadedExtension
        return [
            new PreloadedExtension([$type], []),
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
            'username' => 'admin',
            'password' => 'secret',
            'remember' => true
        ];

        // init login form
        $user = new User();
        $form = $this->factory->create(LoginFormType::class, $user);
        $form->submit($formData);

        // assert form is synchronized and user data is mapped
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('admin', $user->getUsername());
        $this->assertEquals('secret', $user->getPassword());

        // create form view
        $view = $form->createView();
        $children = $view->children;

        // assert form fields are mapped in view
        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
