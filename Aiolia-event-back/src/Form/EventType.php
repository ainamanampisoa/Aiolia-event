<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\EventType as EventTypeEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Concert de Jazz à Antananarivo',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                ],
            ])
            ->add('categoriePrincipale', EntityType::class, [
                'label' => 'Catégorie',
                'class' => EventCategory::class,
                'choice_label' => 'label',
                'placeholder' => 'Sélectionner une catégorie',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire']),
                ],
            ])
            ->add('typeEvenement', EntityType::class, [
                'label' => 'Type d\'événement',
                'class' => EventTypeEntity::class,
                'choice_label' => 'label',
                'placeholder' => 'Sélectionner un type',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'Résumé',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Résumé court de l\'événement (max 500 caractères)',
                    'maxlength' => 500,
                    'rows' => 3,
                ],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description complète',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Décrivez votre événement en détails...',
                ],
                'required' => false,
            ])
            ->add('commenceLe', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'style' => 'display: none;'],
                'required' => false,
                'mapped' => false,
            ])
            ->add('seTermineLe', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'style' => 'display: none;'],
                'required' => false,
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}

