<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function getProductList(string $select, array $join, array $where, array $order, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select("distinct p.id");

        $qb->addSelect($select);

        foreach ($where as $i => $clause) {
            if ($i === 0) {
                $qb->where($clause);
            } else {
                $qb->andWhere($clause);
            }
        }

        foreach ($join as $clause) {
            if ($clause['type'] === 'join') {
                $qb->join($clause['clause'], $clause['alias']);
            }

            if ($clause['type'] === 'leftJoin') {
                $qb->leftJoin($clause['clause'], $clause['alias']);
            }

        }

        if ($order) {
            $qb->orderBy($order[0], $order[1]);
        }

        $qb->setMaxResults($limit);

        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countProductsInList(array $join, array $where): int
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('count(distinct p.id)');

        foreach ($where as $clause) {
            $qb->andWhere($clause);
        }

        foreach ($join as $clause) {
            if ($clause['type'] === 'join') {
                $qb->join($clause['clause'], $clause['alias']);
            }

            if ($clause['type'] === 'leftJoin') {
                $qb->leftJoin($clause['clause'], $clause['alias']);
            }

        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getProductBrandsByCategories(array $brandIds, int $categoryId): ?array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select("b.id as brandId, b.title as brandTitle, p.image as productImage, cp.id as parentId");

        $qb->join('p.brand', 'b');

        $qb->join('p.category', 'c');

        $qb->join('c.parent', 'cp');

        $qb->where("c.id = $categoryId");

        $qb->andWhere('p.image is not null');

        if ($brandIds) {
            $qb->andWhere("b.id not in (:brandIds)");
            $qb->setParameter(':brandIds', $brandIds);
        }

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getPreviewCategory(int $categoryId): ?array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select("c.id, c.title, c.image");

        $qb->join('p.category', 'c');

        $qb->where("c.id = $categoryId");

        $qb->andWhere('c.image is not null');

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getProductSizesSortedByOrder(int $productId): array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->join('p.sizes', 's');

        $qb->select('s.id, s.title');

        $qb->where("p.id = $productId");

        $qb->orderBy('s.sortOrder', 'asc');

        return $qb->getQuery()->getResult();
    }
}