<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class VisitorListExportType
 *
 * Form for configure visitor export filter
 *
 * @extends AbstractType<mixed>
 *
 * @package App\Form
 */
class VisitorListExportType extends AbstractType
{
    /**
     * Build visitor exporter form
     *
     * @param FormBuilderInterface<mixed> $builder The form builder
     * @param array<string> $options The options for building the form
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filter', ChoiceType::class, [
                'choices' => [
                    'Last Hour' => 'H',
                    'Last Day' => 'D',
                    'Last Week' => 'W',
                    'Last Month' => 'M',
                    'Last Year' => 'Y',
                    'All Time' => 'ALL'
                ],
                'label' => 'Select Time Period: ',
                'placeholder' => 'Select a time period',
                'required' => true
            ])
            ->add('format', ChoiceType::class, [
                'choices' => [
                    'PDF' => 'PDF',
                    'EXCEL' => 'EXCEL'
                ],
                'label' => 'Select export format: ',
                'placeholder' => 'Select export format',
                'required' => true
            ])
        ;
    }

    /**
     * Configure options for visitor exporter form
     *
     * @param OptionsResolver $resolver The resolver for the form options
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null
        ]);
    }
}
