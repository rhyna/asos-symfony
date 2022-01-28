<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class TopMenuBrandListBuilder
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getBrandList(array $categoryIds): array
    {
        $result = [];

        $brandIds = [];

        foreach ($categoryIds as $categoryId) {
            $fetchedResult = $this->em->getRepository(Product::class)->getProductBrandsByCategories($brandIds, $categoryId);

            if ($fetchedResult) {
                $brandIds[] = $fetchedResult['brandId'];

                $result['data'][] = $fetchedResult;

                $result['ids'] = $brandIds;
            }
        }

        return $result;
    }
}