<?php

declare(strict_types=1);

namespace App\Form\BannerForm;

use App\Entity\BannerPlace;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;

class BannerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['banner']) {
            $builder->add('image', FileType::class, [
                'required' => false,
                'constraints' => [
                    new Image([
                        'mimeTypes' => [
                            'image/*'
                        ],
                    ]),
                    new File([
                        'maxSize' => '20M',
                    ]),
                ]
            ]);
        } else {
            $builder->add('image', FileType::class, [
                'required' => true,
                'constraints' => [
                    new NotNull(),
                    new Image([
                        'mimeTypes' => [
                            'image/*'
                        ],
                    ]),
                    new File([
                        'maxSize' => '20M',
                    ]),
                ]
            ]);
        }

        $builder->add('banner-place', EntityType::class, [
            'label' => 'Banner place',
            'required' => false,
            'placeholder' => 'NO PLACE (Banner not posted yet)',
            'property_path' => 'bannerPlace',
            'class' => BannerPlace::class,
            'choice_label' => 'title', // название поле в BannerPlace для отображения в селекте
            'constraints' => [
                new UniqueEntity([
                    'entityClass' => BannerPlace::class,
                    'fields' => 'id',
                ]),
            ]
        ]);
        $builder->add('link', TextType::class, [
            'required' => true,
            'label' => 'Link',
            'constraints' => [
                new NotNull(),
            ]
        ]);
        $builder->add('title', TextType::class, [
            'required' => false,
            'label' => 'Title',
            'constraints' => [
            ]
        ]);
        $builder->add('description', TextareaType::class, [
            'required' => false,
            'label' => 'Description',
            'constraints' => [
            ]
        ]);
        $builder->add('button-label', TextType::class, [
            'required' => false,
            'label' => 'Button label',
            'property_path' => 'buttonLabel',
            'constraints' => [
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'banner' => null,
        ]);

        parent::configureOptions($resolver);
    }
}