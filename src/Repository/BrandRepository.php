<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    private function getBrandListSortedByTitleQB(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('br');

        $qb->orderBy('br.title', 'ASC');

        return $qb;
    }

    public function getBrandListSortedByTitle(): array
    {
        $qb = $this->getBrandListSortedByTitleQB();

        return $qb->getQuery()->getResult();
    }

    public function getBrandList(int $limit, int $offset): array
    {
        $qb = $this->getBrandListSortedByTitleQB();

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

    public function getAllBrandsIdAndTitle(): array
    {
        $qb = $this->createQueryBuilder('br');

        $qb->select("br.id, br.title");

        $qb->orderBy("br.title", "asc");

        return $qb->getQuery()->getResult();
    }

    public function getBrandsByGender(array $categoryIdsByGender): array
    {
        $qb = $this->createQueryBuilder('br');

        $qb->select("br.id, br.title");

        $qb->join("br.products", 'p');

        $qb->where("p.category in (:categoryIdsByGender)");

        $qb->orderBy("br.title", "asc");

        $qb->setParameter('categoryIdsByGender', $categoryIdsByGender);

        $fetchedResult = $qb->getQuery()->getResult();

        return array_unique($fetchedResult, SORT_REGULAR);
    }

    public function getBrandsTitleAndIdByCategory(int $categoryId): array
    {
        $qb = $this->createQueryBuilder('br');

        $qb->select("br.id, br.title");

        $qb->join("br.products", "p");

        $qb->where("p.category = :categoryId");

        $qb->setParameter('categoryId', $categoryId);

        return $qb->getQuery()->getResult();
    }
}