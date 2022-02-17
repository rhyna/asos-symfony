<?php

declare(strict_types=1);

namespace App\Controller\Site;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Size;
use App\Exception\NotFoundException;
use App\Service\PageDeterminerService;
use App\Service\Pagination\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BrandCatalogController extends AbstractController
{
    private EntityManagerInterface $em;
    private PaginationService $paginationService;
    private PageDeterminerService $pageDeterminerService;

    public function __construct(EntityManagerInterface $em, PaginationService $paginationService, PageDeterminerService $pageDeterminerService)
    {
        $this->em = $em;
        $this->paginationService = $paginationService;
        $this->pageDeterminerService = $pageDeterminerService;
    }

    /**
     * @Route("/{gender}/brand/{id}", name="brand")
     */
    public function brand(Request $request): Response
    {
        $id = (int)$request->get('id');

        $gender = $request->get('gender');

        /**
         * @var Brand $brand
         */
        $brand = $this->em->getRepository(Brand::class)->find($id);

        if (!$brand) {
            throw new NotFoundException('No brand found');
        }

        $description = '';

        if ($gender === 'women') {
            $description = $brand->getDescriptionWomen();
        }

        if ($gender === 'men') {
            $description = $brand->getDescriptionMen();
        }

        $brandTitle = $brand->getTitle();

        $categoryRepository = $this->em->getRepository(Category::class);

        $rootSubCategoryIds = $categoryRepository->getRootSubCategories($gender);

        $page = $this->pageDeterminerService->determinePage();

        $select = "p.title, p.price, p.image";

        $join = [
            [
                'clause' => 'p.brand',
                'alias' => 'b',
                'type' => 'leftJoin',
            ],
            [
                'clause' => 'p.category',
                'alias' => 'c',
                'type' => 'join',
            ],
        ];

        $where = [];

        $order = [];

        $where['id']['clause'] = "b.id = :id";

        $where['id']['parameter'] = $id;

        $sizeIds = $request->get('sizes');

        if ($sizeIds) {
            $arr['sizeIds']['clause'] = "s.id IN (:sizeIds)";

            $arr['sizeIds']['parameter'] = $sizeIds;

            $where['sizeIds'] = $arr['sizeIds'];

            $join[] = [
                'clause' => 'p.sizes',
                'alias' => 's',
                'type' => 'join',
            ];
        }

        $categoryIds = $request->get('categories');

        if ($categoryIds) {
            $arr['categoryIds']['clause'] = "c.id IN (:categoryIds)";

            $arr['categoryIds']['parameter'] = $categoryIds;

            $where['categoryIds'] = $arr['categoryIds'];
        }

        if (!$categoryIds) {
            $arr['categoryIds']['clause'] = "c.id IN (:categoryIds)";

            $arr['categoryIds']['parameter'] = $rootSubCategoryIds;

            $where['categoryIds'] = $arr['categoryIds'];
        }

        $sort = $request->get('sort');

        if ($sort === 'price-asc') {
            $order = ["p.price", "ASC"];
        }

        if ($sort === 'price-desc') {
            $order = ["p.price", "DESC"];
        }

        $categoryConfig = $categoryRepository->getRootSubCategoriesByBrand($id, $gender);

        $categoryIds = [];

        foreach ($categoryConfig as $data) {
            $categoryIds[] = $data['id'];
        }

        $sizeConfig = $this->em->getRepository(Size::class)->getUniqueSizesOfProductsByCategoryAndBrand($id, $categoryIds);

        $productRepository = $this->em->getRepository(Product::class);

        $totalProducts = $productRepository->countProductsInList($join, $where);

        $pagination = $this->paginationService->calculate($page, 12, $totalProducts);

        $products = $productRepository->getProductList($select, $join, $where, $order, $pagination->limit, $pagination->offset);

        $breadcrumbs = [
            [
                'title' => $gender,
                'url' => $this->generateUrl($gender),

            ],
            [
                'title' => "All $gender brands",
                'url' => $this->generateUrl('all-brands', ['gender' => $gender]),
            ],
            [
                'title' => $brandTitle,
                'url' => '',
            ],
        ];

        return $this->render('site/catalog.html.twig', [
            'entity' => $brand,
            'entityType' => 'brand',
            'title' => ucwords("$gender $brandTitle | ASOS"),
            'description' => $description,
            'gender' => $gender,
            'categoryConfig' => $categoryConfig,
            'sizeConfig' => $sizeConfig,
            'products' => $products,
            'pagination' => $pagination,
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * @Route("/{gender}/brands", name="all-brands")
     */
    public function allBrands(Request $request): Response
    {
        try {
            $gender = $request->get('gender');

            $categoryRepository = $this->em->getRepository(Category::class);

            $categoryIdsByGender = $categoryRepository->getRootSubCategories($gender);

            $brandRepository = $this->em->getRepository(Brand::class);

            $brandsByGender = $brandRepository->getBrandsByGender($categoryIdsByGender);

            $breadcrumbs = [
                [
                    'title' => $gender,
                    'url' => $this->generateUrl($gender),

                ],
                [
                    'title' => "All $gender Brands",
                    'url' => "",
                ],
            ];

            return $this->render('site/all-brands.html.twig', [
                'title' => "All $gender brands | ASOS",
                'gender' => $gender,
                'breadcrumbs' => $breadcrumbs,
                'brandsByGender' => $brandsByGender,
            ]);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}