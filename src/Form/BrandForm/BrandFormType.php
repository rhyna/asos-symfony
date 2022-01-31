<?php

declare(strict_types=1);

namespace App\Form\BrandForm;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

class BrandFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('title', TextType::class, [
            'label' => 'Title',
            'required' => true,
            'constraints' => [
                new NotNull(),
            ]
        ]);

        $builder->add('descriptionMen', TextareaType::class, [
            'label' => 'Description (men)',
            'required' => false,
            'constraints' => [
            ],
        ]);

        $builder->add('descriptionWomen', TextareaType::class, [
            'label' => 'Description (women)',
            'required' => false,
            'constraints' => [
//                new Type('string'),
            ],
        ]);
    }
}