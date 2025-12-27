<?php

namespace App\Tests\Form;

use App\Entity\Visitor;
use App\Form\BanFormType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class BanFormTypeTest
 *
 * Test cases for ban form
 *
 * @package App\Tests\Form
 */
class BanFormTypeTest extends TypeTestCase
{
    /**
     * Test submit valid data
     *
     * @return void
     */
    public function testSubmitValidData(): void
    {
        $formData = [
            'ban_reason' => 'Spamming'
        ];

        // create visitor
        $visitor = new Visitor();
        $form = $this->factory->create(BanFormType::class, $visitor);

        // submit form
        $form->submit($formData);

        // assert form is synchronized and visitor has ban reason
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('Spamming', $visitor->getBanReason());

        // create form view
        $view = $form->createView();
        $children = $view->children;

        // assert form fields are mapped in view
        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
