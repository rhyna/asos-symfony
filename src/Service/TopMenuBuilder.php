<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class TopMenuBuilder
{
    private CategoryLevelsBuilder $categoryLevelsBuilder;
    private EntityManagerInterface $em;
    private TopMenuBrandListBuilder $topMenuBrandListBuilder;

    public function __construct(CategoryLevelsBuilder $categoryLevelsBuilder,
                                EntityManagerInterface $em,
                                TopMenuBrandListBuilder $topMenuBrandListBuilder)
    {
        $this->categoryLevelsBuilder = $categoryLevelsBuilder;
        $this->em = $em;
        $this->topMenuBrandListBuilder = $topMenuBrandListBuilder;
    }

    public function getMenuConfig(): array
    {
        $config = $this->categoryLevelsBuilder->getCategoryLevels();

        return array_map(function ($root) {
            $productRepository = $this->em->getRepository(Product::class);

            $config1 = [];

            $config1['flag'] = strtolower($root['title']);

            $categoryIdsForAllBrandsMenu = [];

            foreach ($root['childCategory1'] as $firstLevel) {
                $config1['categories'][$firstLevel['title']]['subCategories'] = $firstLevel['childCategory2'];

                $secondLevelCategoryIds = [];

                foreach ($firstLevel['childCategory2'] as $secondLevel) {
                    $secondLevelCategoryIds[] = (int)$secondLevel['id'];
                }

                $brandInfo = $this->topMenuBrandListBuilder->getBrandList($secondLevelCategoryIds);

                $config1['categories'][$firstLevel['title']]['brandsData'] = $brandInfo;

                $config1['categories'][$firstLevel['title']]['subCategoryIds'] = $secondLevelCategoryIds;

                $previewCategoryIds = array_slice($secondLevelCategoryIds, 0, 2);

                $previewCategories = [];

                foreach ($previewCategoryIds as $id) {
                    $previewCategory = $productRepository->getPreviewCategory($id);

                    if ($previewCategory) {
                        $previewCategories[] = $previewCategory;
                    }
                }

                $config1['categories'][$firstLevel['title']]['previewCategories'] = $previewCategories;

                for ($i = 0; $i <= 3; $i++) {
                    if (isset($secondLevelCategoryIds[$i])) {
                        $categoryIdsForAllBrandsMenu[] = $secondLevelCategoryIds[$i];
                    }
                }
            }

            $config1['brands'] = $this->topMenuBrandListBuilder->getBrandList($categoryIdsForAllBrandsMenu);

            return $config1;

        }, $config);
    }
}