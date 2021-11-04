<?php

declare(strict_types=1);

namespace App\Controller\Site;

use App\Entity\Banner;
use App\Service\Banner\BannerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    private EntityManagerInterface $em;

    private BannerService $bannerService;

    public function __construct(EntityManagerInterface $em, BannerService $bannerService)
    {
        $this->em = $em;
        $this->bannerService = $bannerService;
    }

    /**
     * @Route("/", name="index")
     */
    public function index(Request $request): Response
    {
        return $this->redirectToRoute('women');
    }

    /**
     * @Route("/women", name="women")
     */
    public function homeWomen(Request $request): Response
    {
        $repository = $this->em->getRepository(Banner::class);

        $banners = $repository->getBannersByGender('women');

        $hotCategorySmallBanners = $this->bannerService->getHotCategorySmallBannersByGender($banners);

        $hotCategoryBigBanners = $this->bannerService->getHotCategoryBigBannersByGender($banners);

        $trendingBrandsBanners = $this->bannerService->getTrendingBrandsBannersByGender($banners);

        return $this->render('site/women.html.twig', [
            'bigTopBanner' => $banners['big_top_banner'],
            'fullWidthBanner' => $banners['full_width_banner'],
            'hotCategoryBigBanners' => $hotCategoryBigBanners,
            'hotCategorySmallBanners' => $hotCategorySmallBanners,
            'trendingBrandsBanners' => $trendingBrandsBanners,
            'gender' => 'women',
        ]);
    }

    /**
     * @Route("/men", name="men")
     */
    public function homeMen(Request $request): Response
    {
        $repository = $this->em->getRepository(Banner::class);

        $banners = $repository->getBannersByGender('men');

        $hotCategorySmallBanners = $this->bannerService->getHotCategorySmallBannersByGender($banners);

        $hotCategoryBigBanners = $this->bannerService->getHotCategoryBigBannersByGender($banners);

        $trendingBrandsBanners = $this->bannerService->getTrendingBrandsBannersByGender($banners);

        return $this->render('site/men.html.twig', [
            'bigTopBanner' => $banners['big_top_banner'],
            'hotCategoryBigBanners' => $hotCategoryBigBanners,
            'hotCategorySmallBanners' => $hotCategorySmallBanners,
            'trendingBrandsBanners' => $trendingBrandsBanners,
            'gender' => 'men',
        ]);
    }
}