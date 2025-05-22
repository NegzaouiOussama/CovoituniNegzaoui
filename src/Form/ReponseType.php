<?php

namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu de la réponse',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre réponse à la réclamation',
                    'minlength' => 10,
                    'maxlength' => 5000,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le contenu de la réponse est obligatoire']),
                    new Length([
                        'min' => 10,
                        'max' => 5000,
                        'minMessage' => 'La réponse doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La réponse ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'help' => 'Entrez votre réponse à la réclamation',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
        ]);
    }
} 