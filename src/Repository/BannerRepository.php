<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Banner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BannerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Banner::class);
    }

    public function getBannersList(int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('b');

        $qb->leftJoin('b.bannerPlace', 'bp');

        $qb->orderBy('bp.alias', 'ASC');

        $qb->setMaxResults($limit);

        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countBannerList(): int
    {
        $qb = $this->createQueryBuilder('b');

        $qb->select("count(b)");

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}