<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
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

    public function getFirstLevelCategories(array $categoryLevels): array
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

    public function getSecondLevelCategories(array $categoryLevels): array
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

    public function getCategoryList(array $ids): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->leftJoin('c.parent', 'cp');

        $qb->where('c.rootWomenCategory = 0 and c.rootMenCategory = 0');

        if ($ids) {
            $idString = implode(',', $ids);

            $qb->andWhere("c.id IN ($idString)");
        }

        $qb->addOrderBy('cp.id', 'ASC');

        $qb->addOrderBy('c.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getCategoryListIds(array $params, string $where): array
    {
//        try {
        $sql = <<<SQL
                    select distinct c.id, 
                    c.title, 
                    c.parent_id as parentId, 
                    pc.title as parentTitle,
                    pc1.title as rootCategory 
                    from category c 
                    left join category pc 
                    on pc.id = c.parent_id 
                    left join category pc1 
                    on pc1.id = pc.parent_id
                    where c.root_men_category = 0 
                    and c.root_women_category = 0 
                    %s
                    order by c.parent_id asc, 
                    c.id asc
            SQL;

        $stmt = $this->prepareFilterStatement($sql, $where, $params);

        $result = $stmt->executeQuery();

        return array_column($result->fetchAllAssociative(), 'id');

//        } catch (\Throwable $e) {
//
//        }
    }

    public function countCategoriesInList(string $where, array $params)
    {
        $sql = <<<SQL
                select count(distinct c.id)
                from category c
                where c.root_men_category = 0 
                and c.root_women_category = 0 
                %s
        SQL;

        $stmt = $this->prepareFilterStatement($sql, $where, $params);

        $result = $stmt->executeQuery();

        return $result->fetchOne();
    }

    public function prepareFilterStatement(string $sql, string $where, array $params): Statement
    {
        $newSQL = sprintf($sql, $where);

        $stmt = $this->_em->getConnection()->prepare($newSQL);

        foreach ($params as $key => $value) {
            if ($key === 'ids') {
                $stmt->bindValue('ids', implode(',', $value));
            }
        }

        return $stmt;
    }
}