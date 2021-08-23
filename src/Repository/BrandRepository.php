<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    public function getBrandList(int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('br');

        $qb->orderBy('br.title', 'ASC');

        $qb->setMaxResults($limit);

        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countBrandList(): int
    {
        $qb = $this->createQueryBuilder('br');

        $qb->select("count(br)");

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}