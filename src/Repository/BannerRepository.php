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

    public function getAllBannersSortedByPlaceAlias(): array
    {
        $qb = $this->createQueryBuilder('b'); // алиас для таблицы баннер

        $qb->leftJoin('b.bannerPlace', 'bp');

        $qb->orderBy('bp.alias', 'ASC');

        return $qb->getQuery()->getResult();
    }
}