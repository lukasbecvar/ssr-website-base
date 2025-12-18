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
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * Class RegisterFormType
 *
 * Form provides registering new admin users
 *
 * @extends AbstractType<User>
 *
 * @package App\Form
 */
class RegisterFormType extends AbstractType
{
    /**
     * Build registration form
     *
     * @param FormBuilderInterface<User|null> $builder The form builder
     * @param array<string> $options The options for building the form
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
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
            ])
            ->add('password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'text-input',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Password'
                ],
                'mapped' => true,
                'constraints' => new Sequentially([
                    new NotBlank(message: 'Please enter a password'),
                    new Length(min: 8, minMessage: 'Your password should be at least {{ limit }} characters', max: 80)
                ]),
                'translation_domain' => false
            ])
            ->add('re-password', PasswordType::class, [
                'label' => false,
                'mapped' => false,
                'attr' => [
                    'type' => 'password',
                    'class' => 'text-input',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Password again'
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message: 'Please enter a password again'),
                    new Length(min: 8, minMessage: 'Your password again should be at least {{ limit }} characters', max: 80)
                ]),
                'translation_domain' => false
            ])
        ;
    }

    /**
     * Configure options for registration form
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
