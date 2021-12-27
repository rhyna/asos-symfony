<?php

declare(strict_types=1);

namespace App\Service\Banner;

class BannerService
{
    public function getHotCategorySmallBannersByGender(array $bannersByGender): array
    {
        $hotCategorySmallBanners = [];

        foreach ($bannersByGender as $key => $banner) {
            $isHotCategorySmall = strpos($key, 'hot_category_small');

            if ($isHotCategorySmall !== false) {
                $hotCategorySmallBanners[$banner['alias']] = $banner;
            }
        }

        return $hotCategorySmallBanners;
    }

    public function getHotCategoryBigBannersByGender(array $bannersByGender): array
    {
        $hotCategoryBigBanners = [];

        foreach ($bannersByGender as $key => $banner) {
            $isHotCategoryBig = strpos($key, 'hot_category_big');

            if ($isHotCategoryBig !== false) {
                $hotCategoryBigBanners[$banner['alias']] = $banner;
            }
        }

        return $hotCategoryBigBanners;
    }

    public function getTrendingBrandsBannersByGender(array $bannersByGender): array
    {
        $trendingBrands = [];

        foreach ($bannersByGender as $key => $banner) {
            $isTrendingBrand = strpos($key, 'trending_brand');

            if ($isTrendingBrand !== false) {
                $trendingBrands[$banner['alias']] = $banner;
            }
        }

        return $trendingBrands;
    }
}