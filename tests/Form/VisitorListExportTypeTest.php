<?php

namespace App\Tests\Form;

use App\Form\VisitorListExportType;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

/**
 * Class VisitorListExportTypeTest
 *
 * Test cases for visitor list export form
 *
 * @package App\Tests\Form
 */
class VisitorListExportTypeTest extends TypeTestCase
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
        $formData = [
            'filter' => 'W', // last week
            'format' => 'PDF'
        ];

        // init visitor list export form
        $form = $this->factory->create(VisitorListExportType::class);
        $form->submit($formData);

        // assert form is synchronized and form is valid
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        // assert form data is mapped
        $data = $form->getData();
        $this->assertEquals('W', $data['filter']);
        $this->assertEquals('PDF', $data['format']);
    }
}
