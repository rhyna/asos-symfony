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

        return $qb;
    }

    public function getProductList(array $whereClauses, array $order, int $limit, int $offset): array
    {
        $qb = $this->getProductListQB();

        $qb->addSelect(
            "p.title, 
            p.productCode, 
            p.price, 
            b.id as brandId, 
            b.title as brandTitle,
            c.id as categoryId, 
            c.title as categoryTitle, 
            p.image");

        $qb->leftJoin("p.brand", "b");

        $qb->join("p.category", "c");

        if ($whereClauses) {
            foreach ($whereClauses as $i => $clause) {
                if ($i === 0) {
                    $qb->where($clause);
                } else {
                    $qb->andWhere($clause);
                }
            }
        }

        if ($order) {
            $qb->orderBy($order[0], $order[1]);
        }

        $qb->setMaxResults($limit);

        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }
}