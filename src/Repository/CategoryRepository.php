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

    public function getRootCategories(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.title', 'c.id');

        $qb->where('c.parent is null');

        return $qb->getQuery()->getResult();
    }

    public function getSubCategories(int $parentCategoryLevelId): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.title', 'c.id', 'cp.id as parentId', 'cp.title as parentTitle', 'c.image');

        $qb->leftJoin('c.parent', 'cp');

        $qb->where('c.parent = :parentCategoryLevelId');

        $qb->setParameter('parentCategoryLevelId', $parentCategoryLevelId);

        return $qb->getQuery()->getResult();
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

//        foreach ($whereClauses as $clause) {
//            $qb->andWhere($clause);
//        }

        foreach ($whereClauses as $key => $clause) {
            $qb->andWhere($clause['clause']);

            $qb->setParameter($key, $clause['parameter']);
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

        foreach ($whereClauses as $key => $clause) {
            $qb->andWhere($clause['clause']);

            $qb->setParameter($key, $clause['parameter']);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function getRootSubCategories(string $gender): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->join("c.parent", "cp");

        $qb->join("cp.parent", "cp1");

        $qb->select("c.id");

        if ($gender === 'women') {
            $qb->where("cp1.rootWomenCategory = true");
        }

        if ($gender === 'men') {
            $qb->where("cp1.rootMenCategory = true");
        }

        $fetchedResult = $qb->getQuery()->getResult();

        return array_column($fetchedResult, 'id');
    }

    public function getRootSubCategoriesByBrand(int $brandId, string $gender): array
    {
        $rootSubCategoryIds = $this->getRootSubCategories($gender);

        //$rootSubCategories = implode(',', $rootSubCategoryIds);

        $qb = $this->createQueryBuilder('c');

        $qb->select("c.id, c.title, cp.title as parentCategoryTitle");

        $qb->join("c.products", "p");

        $qb->join("c.parent", "cp");

        $qb->where("p.brand = :brandId");

        $qb->andWhere("c.id in (:rootSubCategoryIds)");

        $qb->setParameter('brandId', $brandId);

        $qb->setParameter('rootSubCategoryIds', $rootSubCategoryIds);

        $qb->orderBy('c.title', 'asc');

        return $qb->getQuery()->getResult();
    }
}