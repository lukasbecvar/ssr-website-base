<?php

namespace App\Form;

use App\Entity\User;
use App\Util\AppUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Class LoginFormType
 *
 * Form provides admin accounts authenticator
 *
 * @extends AbstractType<User>
 *
 * @package App\Form
 */
class LoginFormType extends AbstractType
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
    }

    /**
     * Build user login form
     *
     * @param FormBuilderInterface<User|null> $builder The form builder
     * @param array<string> $options The options for building the form
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // set default value (for dev env)
        $defaultValue = $this->appUtil->getEnvValue('APP_ENV') === 'dev' ? 'test' : null;

        $builder
            ->add('username', TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'text-input',
                    'placeholder' => 'Username',
                    'value' => $defaultValue
                ],
                'mapped' => true,
                'translation_domain' => false,
                'constraints' => new NotBlank(message: 'Please enter a username')
            ])
            ->add('password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'text-input',
                    'placeholder' => 'Password',
                    'value' => $defaultValue
                ],
                'mapped' => true,
                'translation_domain' => false,
                'constraints' => new NotBlank(message: 'Please enter a password')
            ])
            ->add('remember', CheckboxType::class, [
                'label' => 'Remember me',
                'attr' => [
                    'class' => 'checkbox'
                ],
                'translation_domain' => false,
                'required' => false,
                'mapped' => false
            ])
        ;
    }

    /**
     * Configure options for this form
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
