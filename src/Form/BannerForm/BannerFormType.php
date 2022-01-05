<?php

declare(strict_types=1);

namespace App\Form\BannerForm;

use App\Entity\BannerPlace;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;

class BannerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // спец штуки для загрузки файлов https://symfonycasts.com/screencast/symfony-uploads
        $builder->add('image', FileType::class, [
            'required' => true,
            'constraints' => [
                new NotNull(), // todo решить c image required, если у баннера уже есть id и картинка
                new Image([
                    'minWidth' => 200,
                ]),
                new File([
                    'maxSize' => '20M',
                ]),
            ]
        ]);
        // оно само подсосет из репозитория BannerPlace все баннерплейсы
        // если нужны не все, то можно тут написать специальный запрос
        $builder->add('banner-place', EntityType::class, [
            'label' => 'Banner place',
            'required'   => false, // чтобы отрисовать плейсхолдер
            'placeholder' => 'NO PLACE (Banner not posted yet)',
            'property_path' => 'bannerPlace',
            'class' => BannerPlace::class,
            'choice_label' => 'title', // название поле в BannerPlace для отображения в селекте
            'constraints' => [

            ]
        ]);
        $builder->add('link', TextType::class, [
            'label' => 'Link',
            'constraints' => [
                new NotNull(),
                //new Url(),
            ]
        ]);
        $builder->add('title', TextType::class, [
            'label' => 'Title',
            'constraints' => [
                new NotNull(),
                new Length(null, 3, 10),
            ]
        ]);
        $builder->add('description', TextareaType::class, [
            'label' => 'Description',
            'constraints' => [
                new NotNull(),
                new Length(null, 3, 10),
            ]
        ]);
        $builder->add('button-label', TextType::class, [
            'property_path' => 'buttonLabel',
            'constraints' => [
                new NotNull(),
            ]
        ]);
    }
}