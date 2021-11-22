<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    private function getProductListQB(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select("distinct p.id");

        $qb->leftJoin("p.brand", "b");

        $qb->join("p.category", "c");

        $qb->join('p.sizes', 's');

        return $qb;
    }

    public function getProductList(string $select, array $join, array $where, array $order, int $limit, int $offset): array
    {
//        $qb = $this->getProductListQB();

        $qb = $this->createQueryBuilder('p');

        $qb->select("distinct p.id");

        if ($select) {
            $qb->addSelect($select);

//            foreach ($select as $clause) {
//                $qb->addSelect($clause);
//            }
        }

//        $qb->addSelect(
//            "p.title,
//            p.productCode,
//            p.price,
//            b.id as brandId,
//            b.title as brandTitle,
//            c.id as categoryId,
//            c.title as categoryTitle,
//            p.image");

        if ($where) {
            foreach ($where as $i => $clause) {
                if ($i === 0) {
                    $qb->where($clause);
                } else {
                    $qb->andWhere($clause);
                }
            }
        }

        if ($join) {
            foreach ($join as $clause) {
                if ($clause['type'] === 'join') {
                    $qb->join($clause['clause'], $clause['alias']);
                }

                if ($clause['type'] === 'leftJoin') {
                    $qb->leftJoin($clause['clause'], $clause['alias']);
                }

            }
        }

        if ($order) {
            $qb->orderBy($order[0], $order[1]);
        }

        $qb->setMaxResults($limit);

        $qb->setFirstResult($offset);

        $dd = $qb->getDQL();

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countProductsInList(array $join, array $where): int
    {
//        $qb = $this->getProductListQB();

        $qb = $this->createQueryBuilder('p');

//        $qb->select("distinct p.id");

        $qb->select('count(distinct p.id)');

        foreach ($where as $clause) {
            $qb->andWhere($clause);
        }

        if ($join) {
            foreach ($join as $clause) {
                if ($clause['type'] === 'join') {
                    $qb->join($clause['clause'], $clause['alias']);
                }

                if ($clause['type'] === 'leftJoin') {
                    $qb->leftJoin($clause['clause'], $clause['alias']);
                }

            }
        }

        $dd = $qb->getDQL();

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getProductBrandsByCategories(array $categoryIds): array
    {
        $result = [];

        $brandIds = [];

        foreach ($categoryIds as $categoryId) {
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

            $fetchedResult = $qb->getQuery()->getOneOrNullResult();

            if ($fetchedResult) {
                $brandIds[] = $fetchedResult['brandId'];

                $result['data'][] = $fetchedResult;

                $result['ids'] = $brandIds;
            }
        }

        return $result;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
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

    public function getProductsBySearchWords(array $searchWordIds): array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select("p.id, p.title, p.image, p.price");

        foreach ($searchWordIds as $i => $id) {
            $qb->join("p.searchWords", "sw$i");

            if ($i === 0) {
                $qb->where("sw$i.id = $id");

            } else {
                $qb->andWhere("sw$i.id = $id");
            }
        }

        return $qb->getQuery()->getResult();
    }
}