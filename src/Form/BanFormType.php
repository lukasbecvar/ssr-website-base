<?php

namespace App\Form;

use App\Entity\Visitor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Class BanFormType
 *
 * Form provides visitor ban functionality
 *
 * @extends AbstractType<Visitor>
 *
 * @package App\Form
 */
class BanFormType extends AbstractType
{
    /**
     * Build form for ban visitor
     *
     * @param FormBuilderInterface<Visitor|null> $builder The form builder
     * @param array<string> $options The options for building the form
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('ban_reason', TextareaType::class, [
            'label' => false,
            'attr' => [
                'class' => 'text-input',
                'maxlength' => 120
            ],
            'translation_domain' => false,
            'required' => false,
            'mapped' => true
        ]);
    }

    /**
     * Configure options for ban form
     *
     * @param OptionsResolver $resolver The resolver for the form options
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Visitor::class
        ]);
    }
}
