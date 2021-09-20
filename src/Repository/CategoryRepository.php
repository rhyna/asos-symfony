<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    private function getRootCategories(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.title', 'c.id');

        $qb->where('c.parent is null');

        return $qb->getQuery()->getResult();
    }

    private function getFirstLevelCategories(array $categoryLevels): array
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

    private function getSecondLevelCategories(array $categoryLevels): array
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

    public function getCategoryLevels(): array
    {
        $categoryLevels = $this->getRootCategories();

        $categoryLevels = $this->getFirstLevelCategories($categoryLevels);

        return $this->getSecondLevelCategories($categoryLevels);
    }

    private function getCategoryListQB(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select("distinct c.id");

        $qb->leftJoin("c.parent", 'cp');

        $qb->where("c.rootMenCategory = 0 and c.rootWomenCategory = 0");

        return $qb;
    }

    public function getCategoryList(array $whereClauses, int $limit, int $offset): array
    {
        $qb = $this->getCategoryListQB();

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

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countCategoriesList(array $whereClauses): int
    {
        $qb = $this->getCategoryListQB();

        $qb->select("count(distinct c.id)");

        foreach ($whereClauses as $clause) {
            $qb->andWhere($clause);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}