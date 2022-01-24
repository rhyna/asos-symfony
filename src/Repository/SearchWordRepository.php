<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SearchWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SearchWordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchWord::class);
    }

    public function getSearchWordIds(array $query): array
    {
         $qb = $this->createQueryBuilder('sw');

         $query = "'" . implode("', '", $query) . "'";

         $qb->select("sw.id");

         $qb->where("sw.word in ($query)");

         $fetchedResult = $qb->getQuery()->getResult();

         return array_column($fetchedResult, 'id');
    }

    public function checkUsedSearchWords(string $searchWordIds, int $productId): array
    {
        $qb = $this->createQueryBuilder('sw');

        $qb->select("distinct sw.id");

        $qb->join("sw.products", "swp");

        $qb->where("sw.id in ($searchWordIds)");

        $qb->andWhere("swp.id != $productId");

        return $qb->getQuery()->getScalarResult();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteUnusedSearchWords(array $unusedSearchWordIds): void
    {
        $unusedSearchWordIds = implode(',', $unusedSearchWordIds);

        $sql = "delete from search_word sw where sw.id in ($unusedSearchWordIds)";

        $this->_em->getConnection()->executeQuery($sql);
    }
}