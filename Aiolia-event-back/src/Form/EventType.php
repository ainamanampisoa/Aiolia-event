<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\EventTag;
use App\Entity\EventPaymentMethod;
use App\Entity\EventAccessibility;
use App\Entity\EventLanguage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
            ->add('primaryCategory', EntityType::class, [
                'label' => 'Catégorie',
                'class' => EventCategory::class,
                'choice_label' => 'label',
                'placeholder' => 'Sélectionner une catégorie',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire']),
                ],
            ])
            ->add('subtitle', TextType::class, [
                'label' => 'Sous-titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Sous-titre de l\'événement'
                ],
                'required' => false,
            ])
            ->add('summary', TextType::class, [
                'label' => 'Résumé',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Résumé court de l\'événement (max 500 caractères)',
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
            ->add('venueNameText', TextType::class, [
                'label' => 'Nom du lieu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Le Grand Café de la Gare'
                ],
                'required' => false,
            ])
            ->add('fullAddress', TextareaType::class, [
                'label' => 'Adresse complète',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Adresse détaillée de l\'événement'
                ],
                'required' => false,
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'La date de début est obligatoire']),
                ],
            ])
            ->add('endsAt', DateTimeType::class, [
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
            ->add('capacity', IntegerType::class, [
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
            ->add('youtubeUrl', TextType::class, [
                'label' => 'URL YouTube',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.youtube.com/watch?v=...'
                ],
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'label' => 'Tags',
                'class' => EventTag::class,
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('paymentMethodsData', ChoiceType::class, [
                'label' => 'Modes de paiement',
                'choices' => EventPaymentMethod::METHODS,
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('accessibilitiesData', ChoiceType::class, [
                'label' => 'Accessibilité',
                'choices' => EventAccessibility::TYPES,
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('languagesData', ChoiceType::class, [
                'label' => 'Langues',
                'choices' => EventLanguage::LANGUAGES,
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('photos', FileType::class, [
                'label' => 'Photos de l\'événement',
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-control'
                ],
            ])
        ;

        // Écouter les événements du formulaire pour gérer les relations
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $eventEntity = $event->getData();
            $form = $event->getForm();

            // Gérer les modes de paiement
            $paymentMethodsData = $form->get('paymentMethodsData')->getData();
            if ($paymentMethodsData) {
                foreach ($eventEntity->getPaymentMethods() as $pm) {
                    $eventEntity->removePaymentMethod($pm);
                }
                foreach ($paymentMethodsData as $method) {
                    $pm = new EventPaymentMethod();
                    $pm->setPaymentMethod($method);
                    $pm->setEvent($eventEntity);
                    $eventEntity->addPaymentMethod($pm);
                }
            }

            // Gérer l'accessibilité
            $accessibilitiesData = $form->get('accessibilitiesData')->getData();
            if ($accessibilitiesData) {
                foreach ($eventEntity->getAccessibilities() as $acc) {
                    $eventEntity->removeAccessibility($acc);
                }
                foreach ($accessibilitiesData as $type) {
                    $acc = new EventAccessibility();
                    $acc->setAccessibilityType($type);
                    $acc->setEvent($eventEntity);
                    $eventEntity->addAccessibility($acc);
                }
            }

            // Gérer les langues
            $languagesData = $form->get('languagesData')->getData();
            if ($languagesData) {
                foreach ($eventEntity->getLanguages() as $lang) {
                    $eventEntity->removeLanguage($lang);
                }
                foreach ($languagesData as $langCode) {
                    $lang = new EventLanguage();
                    $lang->setLanguageCode($langCode);
                    $lang->setEvent($eventEntity);
                    $eventEntity->addLanguage($lang);
                }
            }
        });

        // Pré-remplir les données si on édite
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $eventEntity = $event->getData();
            $form = $event->getForm();

            if ($eventEntity && $eventEntity->getId()) {
                // Pré-remplir les modes de paiement
                $paymentMethods = [];
                foreach ($eventEntity->getPaymentMethods() as $pm) {
                    $paymentMethods[] = $pm->getPaymentMethod();
                }
                $form->get('paymentMethodsData')->setData($paymentMethods);

                // Pré-remplir l'accessibilité
                $accessibilities = [];
                foreach ($eventEntity->getAccessibilities() as $acc) {
                    $accessibilities[] = $acc->getAccessibilityType();
                }
                $form->get('accessibilitiesData')->setData($accessibilities);

                // Pré-remplir les langues
                $languages = [];
                foreach ($eventEntity->getLanguages() as $lang) {
                    $languages[] = $lang->getLanguageCode();
                }
                $form->get('languagesData')->setData($languages);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}

