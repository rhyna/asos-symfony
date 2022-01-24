<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class CategoryLevelsBuilder
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getCategoryLevels(): array
    {
        $categoryLevels = $this->em->getRepository(Category::class)->getRootCategories();

        $categoryLevels = $this->getFirstLevelCategories($categoryLevels);

        return $this->getSecondLevelCategories($categoryLevels);
    }

    private function getFirstLevelCategories($categoryLevels)
    {
        foreach ($categoryLevels as &$categoryLevel) {
            $categoryLevel['childCategory1'] = $this->em
                ->getRepository(Category::class)
                ->getSubCategories((int)$categoryLevel['id']);
        }

        return $categoryLevels;
    }

    private function getSecondLevelCategories($categoryLevels)
    {
        foreach ($categoryLevels as &$categoryLevel) {
            foreach ($categoryLevel['childCategory1'] as &$childCategory) {
                $childCategory['childCategory2'] = $this->em
                    ->getRepository(Category::class)
                    ->getSubCategories((int)$childCategory['id']);
            }
        }

        return $categoryLevels;
    }
}