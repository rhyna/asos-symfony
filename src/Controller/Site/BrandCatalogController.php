<?php

declare(strict_types=1);

namespace App\Controller\Site;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Size;
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

    public function __construct(EntityManagerInterface $em, PaginationService $paginationService)
    {
        $this->em = $em;
        $this->paginationService = $paginationService;
    }

    /**
     * @Route("/{gender}/brand/{id}", name="brand")
     */
    public function brand(Request $request): Response
    {
        try {
            $id = (int)$request->get('id');

            if (!$id) {
                throw new \BadRequestException('No id provided');
            }

            $gender = $request->get('gender');

            /**
             * @var Brand $brand
             */
            $brand = $this->em->getRepository(Brand::class)->find($id);

            if (!$brand) {
                throw new \NotFoundException('No brand found');
            }

            $description = '';

            if ($gender === 'women') {
                $description = $brand->getDescriptionWomen();
            }

            if ($gender === 'men') {
                $description = $brand->getDescriptionMen();
            }

            $brandTitle = $brand->getTitle();

            $page = $request->get('page');

            if (!$page || (string)(int)$page !== $page) {
                $page = 1;
            }

            $page = (int)$page;

            $whereClauses = [];

            $order = [];

            $whereClauses[] = "b.id = $id";

            $sizeIds = $request->get('sizes');

            if ($sizeIds) {
                $sizeIds = implode(",", $sizeIds);

                $whereClauses[] = "s.id in ($sizeIds)";
            }

            $categoryIds = $request->get('categories');

            if ($categoryIds) {
                $categoryIds = implode(",", $categoryIds);

                $whereClauses[] = "c.id in ($categoryIds)";
            }

            $sort = $request->get('sort');

            if ($sort === 'price-asc') {
                $order = ["p.price", "ASC"];
            }

            if ($sort === 'price-desc') {
                $order = ["p.price", "DESC"];
            }

            $categoryRepository = $this->em->getRepository(Category::class);

            $categoryConfig = $categoryRepository->getWomenCategoriesByBrand($id);

            $categoryIds = [];

            foreach ($categoryConfig as $data) {
                $categoryIds[] = $data['id'];
            }

            $categoryIds = implode(',', $categoryIds);

            $sizeConfig = $this->em->getRepository(Size::class)->getUniqueSizesOfProductsByCategoryAndBrand($id, $categoryIds);

            $productRepository = $this->em->getRepository(Product::class);

            $totalProducts = $productRepository->countProductList($whereClauses);

            $pagination = $this->paginationService->calculate($page, 10, $totalProducts);

            $products = $productRepository->getProductList($whereClauses, $order, $pagination->limit, $pagination->offset);

            $breadcrumbs = [
                [
                    'title' => $gender,
                    'url' => $this->generateUrl($gender),

                ],
                [
                    'title' => "All $gender brands",
                    'url' => '/'
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

        } catch (\BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (\NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}