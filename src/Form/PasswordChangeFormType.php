<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * Class PasswordChangeFormType
 *
 * Form provides changing user password in the account settings
 *
 * @extends AbstractType<User>
 *
 * @package App\Form
 */
class PasswordChangeFormType extends AbstractType
{
    /**
     * Build password change form
     *
     * @param FormBuilderInterface<User|null> $builder The form builder
     * @param array<string> $options The options for building the form
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'text-input',
                    'autocomplete' => 'password',
                    'placeholder' => 'password'
                ],
                'mapped' => true,
                'constraints' => new Sequentially([
                    new NotBlank(message: 'Please enter a password'),
                    new Length(min: 8, minMessage: 'Your password should be at least {{ limit }} characters', max: 50)
                ]),
                'translation_domain' => false
            ])
            ->add('repassword', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'text-input',
                    'autocomplete' => 'repassword',
                    'placeholder' => 're password'
                ],
                'mapped' => false,
                'constraints' => new Sequentially([
                    new NotBlank(message: 'Please enter a repassword'),
                    new Length(min: 8, minMessage: 'Your password should be at least {{ limit }} characters', max: 50)
                ]),
                'translation_domain' => false
            ])
        ;
    }

    /**
     * Configure options for password change form
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
