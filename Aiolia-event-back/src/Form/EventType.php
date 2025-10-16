<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Concert de Jazz à Antananarivo'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => EventCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner une catégorie',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire']),
                ],
            ])
            ->add('shortDescription', TextType::class, [
                'label' => 'Description courte',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Une courte description (max 500 caractères)',
                    'maxlength' => 500
                ],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description complète',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Décrivez votre événement en détails...'
                ],
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Antananarivo'
                ],
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse complète',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Adresse détaillée'
                ],
                'required' => false,
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: -18.8792',
                    'step' => '0.00000001'
                ],
                'required' => false,
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 47.5079',
                    'step' => '0.00000001'
                ],
                'required' => false,
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La date de début est obligatoire']),
                ],
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La date de fin est obligatoire']),
                ],
            ])
            ->add('timezone', TextType::class, [
                'label' => 'Fuseau horaire',
                'attr' => ['class' => 'form-control'],
                'data' => 'Indian/Antananarivo',
            ])
            ->add('totalCapacity', IntegerType::class, [
                'label' => 'Capacité totale',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nombre de places disponibles',
                    'min' => 1
                ],
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => 'draft',
                    'Publié' => 'published',
                    'En cours' => 'ongoing',
                    'Terminé' => 'completed',
                    'Annulé' => 'cancelled',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Événement en vedette',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}

