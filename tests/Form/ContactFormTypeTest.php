<?php

namespace App\Tests\Form;

use App\Entity\Message;
use App\Form\ContactFormType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class ContactFormTypeTest
 *
 * Test cases for contact form
 *
 * @package App\Tests\Form
 */
class ContactFormTypeTest extends TypeTestCase
{
    /**
     * Test submit valid data
     *
     * @return void
     */
    public function testSubmitValidData(): void
    {
        // form valid data
        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, this is a message.',
            'websiteIN' => ''
        ];

        // init contact form
        $message = new Message();
        $form = $this->factory->create(ContactFormType::class, $message);
        $form->submit($formData);

        // assert form is synchronized
        $this->assertTrue($form->isSynchronized());

        // assert form data is mapped
        $this->assertEquals('John Doe', $message->getName());
        $this->assertEquals('john@example.com', $message->getEmail());
        $this->assertEquals('Hello, this is a message.', $message->getMessage());

        // create form view
        $view = $form->createView();
        $children = $view->children;

        // assert form fields are mapped in view
        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    /**
     * Test honey pot field not mapped
     *
     * @return void
     */
    public function testHoneyPotFieldNotMapped(): void
    {
        // form invalid data (triggered honey-pot)
        $formData = [
            'name' => 'Spam Bot',
            'email' => 'bot@spam.com',
            'message' => 'I am a robot.',
            'websiteIN' => 'http://malicious-site.com'
        ];

        // init contact form
        $message = new Message();
        $form = $this->factory->create(ContactFormType::class, $message);
        $form->submit($formData);

        // assert form is synchronized
        $this->assertTrue($form->isSynchronized());

        // entity should be updated with mapped fields
        $this->assertEquals('Spam Bot', $message->getName());
    }
}
