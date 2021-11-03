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

    public function getUniqueSizesOfProductsByCategory(int $categoryId): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select("distinct s.id, s.title");

        $qb->join("s.products", "p");

        $qb->join("p.category", "c");

        $qb->where("c.id = $categoryId");

        $dd = $qb->getDQL();

        return $qb->getQuery()->getArrayResult();
    }
}