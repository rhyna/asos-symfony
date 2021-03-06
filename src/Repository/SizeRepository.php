<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Size;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class SizeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Size::class);
    }

    private function getUniqueSizesOfProductsQB(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select("distinct s.id, s.title, s.sortOrder");

        $qb->join("s.products", "p");

        return $qb;
    }

    public function getUniqueSizesOfProductsByCategory(int $categoryId): array
    {
        $qb = $this->getUniqueSizesOfProductsQB();

        $qb->join("p.category", "c");

        $qb->where("c.id = :categoryId");

        $qb->orderBy('s.sortOrder', 'asc');

        $qb->setParameter('categoryId', $categoryId);

        return $qb->getQuery()->getArrayResult();
    }

    public function getUniqueSizesOfProductsByCategoryAndBrand(int $brandId, array $categoryIds): array
    {
        $qb = $this->getUniqueSizesOfProductsQB();

        $qb->join("p.category", "c");

        $qb->join("p.brand", "b");

        $qb->where("c.id in (:categoryIds)");

        $qb->andWhere("b.id = :brandId");

        $qb->orderBy('s.sortOrder', 'asc');

        $qb->setParameter('brandId', $brandId);

        $qb->setParameter('categoryIds', $categoryIds);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function checkProductsBySizeAndCategoryIds(int $sizeId, int $categoryId): int
    {
        $qb = $this->createQueryBuilder('s');

        $qb->join('s.products', 'sp');

        $qb->join('sp.category', 'pc');

        $qb->join('pc.parent', 'pcp');

        $qb->select("count(sp.id)");

        $qb->where("s.id = :sizeId");

        $qb->andWhere("pcp.id = :categoryId");

        $qb->setParameter('sizeId', $sizeId);

        $qb->setParameter('categoryId', $categoryId);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}