<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TypeBilletPriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prixDeBase', NumberType::class, [
                'label' => 'Nouveau prix (' . ($options['devise'] ?? 'MGA') . ')',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'step' => '0.01',
                    'min' => '0.01',
                ],
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le prix est obligatoire',
                    ]),
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'Le prix doit être supérieur à 0',
                    ]),
                ],
            ])
            ->add('raison', TextareaType::class, [
                'label' => 'Raison du changement (promotion, ajustement, etc.)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Ex: Mise en promotion -20%',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer la modification',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'devise' => 'MGA',
        ]);
    }
}

