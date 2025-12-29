<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Class ArticleFormType
 *
 * Form for article creation and edition
 *
 * @extends AbstractType<Article>
 *
 * @package App\Form
 */
class ArticleFormType extends AbstractType
{
    /**
     * Build article form
     *
     * @param FormBuilderInterface<Article|null> $builder The form builder
     * @param array<string> $options The options for building the form
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'text-input',
                    'placeholder' => 'Enter article title'
                ],
                'empty_data' => '',
                'constraints' => new NotBlank(message: 'Please enter a title')
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Published' => 'published',
                    'Draft' => 'draft',
                    'Hidden' => 'hidden',
                ],
                'attr' => [
                    'class' => 'text-input'
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'class' => 'text-input',
                    'placeholder' => 'Enter article content...',
                    'rows' => 15
                ],
                'empty_data' => '',
                'constraints' => new NotBlank(message: 'Content cannot be empty')
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Article',
                'attr' => [
                    'class' => 'form-button submit'
                ]
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
            'data_class' => Article::class
        ]);
    }
}
