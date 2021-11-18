<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SearchWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

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

         $result = [];

         foreach ($fetchedResult as $item) {
             $result[] = $item['id'];
         }

         return $result;
    }
}