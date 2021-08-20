<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\QueryBuilder;
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

    private function getCategoryListQueryBuilder(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select("distinct c.id");

        $qb->leftJoin("c.parent", 'cp');

        $qb->where("c.rootMenCategory = 0 and c.rootWomenCategory = 0");

        return $qb;
    }

    public function getCategoryList(array $whereClauses, int $limit, int $offset): array
    {
        $qb = $this->getCategoryListQueryBuilder();

        $qb->addSelect("c.title, cp.id as parentId, cp.title as parentTitle, cp1.title as rootCategory");

        $qb->leftJoin('cp.parent', 'cp1');

        foreach ($whereClauses as $clause) {
            $qb->andWhere($clause);
        }

        $qb->addOrderBy('cp.id', 'ASC');

        $qb->addOrderBy('c.id', 'ASC');

        $qb->setMaxResults($limit);

        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    public function countCategoriesInList(array $whereClauses): int
    {
        $qb = $this->getCategoryListQueryBuilder();

        foreach ($whereClauses as $clause) {
            $qb->andWhere($clause);
        }

        return count($qb->getQuery()->getResult());
    }

//    public function countCategoriesInList(string $where, array $params)
//    {
//        $sql = <<<SQL
//                select count(distinct c.id)
//                from category c
//                where c.root_men_category = 0
//                and c.root_women_category = 0
//                %s
//        SQL;
//
//        $stmt = $this->prepareFilterStatement($sql, $where, $params);
//
//        $result = $stmt->executeQuery();
//
//        return $result->fetchOne();
//    }
//
//    private function prepareFilterStatement(string $sql, string $where, array $params): Statement
//    {
//        $newSQL = sprintf($sql, $where);
//
//        $stmt = $this->_em->getConnection()->prepare($newSQL);
//
//        foreach ($params as $key => $value) {
//            if ($key === 'ids') {
//                $stmt->bindValue('ids', implode(',', $value));
//            }
//        }
//
//        return $stmt;
//    }
}