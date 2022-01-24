<?php

declare(strict_types=1);

namespace App\Service\Filter;

use App\Entity\Category;
use App\Service\CategoryLevelsBuilder;
use Doctrine\ORM\EntityManagerInterface;

class CategoryFilterService
{
    private EntityManagerInterface $em;
    private CategoryLevelsBuilder $categoryLevelsBuilder;

    public function __construct(EntityManagerInterface $em, CategoryLevelsBuilder $categoryLevelsBuilder)
    {
        $this->em = $em;
        $this->categoryLevelsBuilder = $categoryLevelsBuilder;
    }

    public function getCategoriesByGender(): array
    {
        $categoryLevels = $this->categoryLevelsBuilder->getCategoryLevels();

        $categoriesByGender = [];

        foreach ($categoryLevels as $root) {
            $categoriesByGender[$root['title']] = $this->getFlatListOfSubcategories($root['childCategory1']);
        }

        return $categoriesByGender;
    }

    private function getFlatListOfSubcategories(array $parentCategories): array
    {
        $result = [];

        foreach ($parentCategories as $level1) {
            foreach ($level1['childCategory2'] as $level2) {
                $array = [];
                $array['id'] = $level2['id'];
                $array['title'] = $level2['title'];
                $array['parentCategoryTitle'] = $level2['parentTitle'];
                $result[] = $array;
            }
        }

        return $result;
    }
}