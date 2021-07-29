<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function getRootCategories(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.title', 'c.id');

        $qb->where('c.parent is null');

        return $qb->getQuery()->getResult();
    }

    public function getFirstLevelCategories($categoryLevels)
    {
        foreach ($categoryLevels as &$categoryLevel) {
            $qb = $this->createQueryBuilder('c');

            $qb->select('c.title', 'c.id', 'cp.id as parentId', 'cp.title as parentTitle');

            $qb->leftJoin('c.parent', 'cp');

            $qb->where('c.parent = ' . $categoryLevel['id']);

            $categoryLevel['childCategory1'] = $qb->getQuery()->getResult();
        }


        return $categoryLevels;
    }

    public function getSecondLevelCategories($categoryLevels): array
    {
        foreach ($categoryLevels as &$categoryLevel) {
            foreach ($categoryLevel['childCategory1'] as &$childCategory) {
                $qb = $this->createQueryBuilder('c');

                $qb->select('c.title', 'c.id', 'cp.id as parentId', 'cp.title as parentTitle');

                $qb->leftJoin('c.parent', 'cp');

                $qb->where('c.parent = ' . $childCategory['id']);

                $childCategory['childCategory2'] = $qb->getQuery()->getResult();
            }
        }

        return $categoryLevels;
    }

    public function getCategoriesList(): array
    {
//        $sql = "select distinct c.id,
//                    c.title,
//                    c.parent_id as parentId,
//                    pc.title as parentTitle,
//                    pc1.title as rootCategory
//                    from category c
//                    left join category pc
//                    on pc.id = c.parent_id
//                    left join category pc1
//                    on pc1.id = pc.parent_id
//                    where c.root_men_category = 0
//                    and c.root_women_category = 0
//                    %s
//                    order by c.parent_id asc,
//                    c.id asc
//                    limit :limit
//                    offset :offset";
//
//        $qb = $this->createQueryBuilder('c');
//
//        $qb->select('c.id',
//            'c.title',
//            'cp.id as parentId',
//            'cp.title as parentTitle',
//            'cp1.title as rootCategoryTitle');
//
//        $qb->leftJoin('c.parent', 'cp', 'with', 'c.id = cp.id');
//
//        $qb->leftJoin('cp.parent', 'cp1', 'with', 'cp.id = cp1.id');
//
//        $qb->where('c.rootWomenCategory = 0 and c.rootMenCategory = 0');
//
//        $qb->addOrderBy('cp.id', 'ASC');
//
//        $qb->addOrderBy('c.id', 'ASC');

        $qb = $this->createQueryBuilder('c');

        $qb->leftJoin('c.parent', 'cp');

        $qb->where('c.rootWomenCategory = 0 and c.rootMenCategory = 0');

        $qb->addOrderBy('cp.id', 'ASC');

        $qb->addOrderBy('c.id', 'ASC');

        return $qb->getQuery()->getResult();
    }
}