<?php

declare(strict_types=1);

namespace App\Form\CategoryForm;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotNull;

class CategoryFormType extends AbstractType
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

        $builder->add('parentCategory', EntityType::class, [
            'label' => 'Parent Category',
            'required'   => false,
            'placeholder' => 'Please choose category',
            'class' => Category::class,
            'choice_label' => function (Category $choice) {
                if ($choice->getParent() !== null) {
                    return '-- ' . $choice->getTitle();
                }

                return $choice->getTitle();
            },
            'choice_attr' => function (Category $choice) {
                if ($choice->getParent() === null) {
                    return ['style' => 'font-weight: bold;'];
                }

                return [];
            },
            'constraints' => [
                new NotNull(),
            ],
            'query_builder' => function (CategoryRepository $repository) {
                $women = $repository->findOneBy([
                    'rootWomenCategory' => true
                ]);
                $men = $repository->findOneBy([
                    'rootMenCategory' => true
                ]);

                $listOrderedProperly = $repository
                    ->createQueryBuilder('c')
                    ->select('c.id')
                    ->addSelect('(
                        case
                            when c.rootWomenCategory = 1 then 1
                            when c.parent = :women then 2
                            when c.rootMenCategory = 1 then 3
                            when c.parent = :men then 4
                            else 5
                        end
                    ) as rank')
                    ->setParameter('women', $women)
                    ->setParameter('men', $men)
                    ->andWhere('c.rootWomenCategory = 1 OR c.rootMenCategory = 1 OR c.parent = :women OR c.parent = :men')
                    ->addOrderBy('rank', 'ASC')
                    ->addOrderBy('c.title', 'ASC')
                    ->getQuery()
                    ->getScalarResult();

                $ids = array_column($listOrderedProperly, 'id');

                return $repository
                    ->createQueryBuilder('c')
                    ->setParameter('ids', $ids)
                    ->andWhere("c.id in (:ids)")
                    ->addOrderBy("FIELD(c.id, :ids)");
            }
        ]);

        $builder->add('image', FileType::class, [
            'required' => false,
            'constraints' => [
                new Image([
                    'mimeTypes' => [
                        'image/*'
                    ],
                    'maxSize' => '5M',
                    'minWidth' => 200
                ])
            ]
        ]);

        $builder->add('description', TextareaType::class, [
            'label' => 'Description',
            'required' => false,
            'constraints' => [
            ]
        ]);
    }
}