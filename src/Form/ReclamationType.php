<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez le sujet de votre réclamation',
                    'minlength' => 5,
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le sujet est obligatoire',
                    ]),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Ce texte est trop court. Il doit contenir 5 caractères ou plus.',
                        'maxMessage' => 'Ce texte est trop long. Il doit contenir 255 caractères ou moins.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Décrivez votre problème en détail...',
                    'rows' => 6,
                    'minlength' => 5,
                    'class' => 'ckeditor-enable',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description est obligatoire',
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'Ce texte est trop court. Il doit contenir 5 caractères ou plus.',
                    ]),
                ],
                'ckeditor' => true,
                'ckeditor_config' => 'default',
            ])
            // Les champs user, status et date sont gérés par le contrôleur
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
            'attr' => [
                'novalidate' => 'novalidate', // Disable HTML5 validation to use Symfony validation
            ],
        ]);
    }
} 