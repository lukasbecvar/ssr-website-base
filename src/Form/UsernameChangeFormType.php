<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;

/**
 * Class UsernameChangeFormType
 *
 * Form provides changing username in the account settings
 *
 * @extends AbstractType<User>
 *
 * @package App\Form
 */
class UsernameChangeFormType extends AbstractType
{
    /**
     * Build username change form
     *
     * @param FormBuilderInterface<User|null> $builder The form builder
     * @param array<string> $options The options for building the form
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('username', TextType::class, [
            'label' => false,
            'attr' => [
                'class' => 'text-input',
                'autocomplete' => 'username',
                'placeholder' => 'Username'
            ],
            'mapped' => true,
            'constraints' => new Sequentially([
                new NotBlank(message: 'Please enter a username'),
                new Length(min: 4, minMessage: 'Your username should be at least {{ limit }} characters', max: 50)
            ]),
            'translation_domain' => false
        ]);
    }

    /**
     * Configure options for username change form
     *
     * @param OptionsResolver $resolver The resolver for the form options
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
