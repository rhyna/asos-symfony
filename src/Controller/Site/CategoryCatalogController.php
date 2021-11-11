<?php

declare(strict_types=1);

namespace App\Controller\Site;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Size;
use App\Service\Pagination\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryCatalogController extends AbstractController
{
    private EntityManagerInterface $em;
    private PaginationService $paginationService;

    public function __construct(EntityManagerInterface $em, PaginationService $paginationService)
    {
        $this->em = $em;
        $this->paginationService = $paginationService;
    }

    /**
     * @Route("/{gender}/category/{id}", name="category")
     */
    public function category(Request $request): Response
    {
        try {
            $categoryId = (int)$request->get('id');

            if (!$categoryId) {
                throw new \BadRequestException('No id provided');
            }

            $gender = $request->get('gender');

            /**
             * @var Category $category
             */
            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                throw new \NotFoundException('No category found');
            }

            $categoryTitle = $category->getTitle();

            $categoryDescription = $category->getDescription();

            $productsByCategory = $category->getProducts();

            $brandConfig = [];

            /**
             * @var Product $product
             */
            foreach ($productsByCategory as $product) {
                $brandData = [];

                $brand = $product->getBrand();

                if ($brand) {
                    $brandId = $product->getBrand()->getId();

                    $brandTitle = $product->getBrand()->getTitle();

                    $brandData['id'] = $brandId;

                    $brandData['title'] = $brandTitle;

                    $brandConfig[] = $brandData;
                }
            }

            $sizeConfig = $this->em->getRepository(Size::class)->getUniqueSizesOfProductsByCategory($categoryId);

            $page = $request->get('page');

            if (!$page || (string)(int)$page !== $page) {
                $page = 1;
            }

            $page = (int)$page;

            $whereClauses = [];

            $order = [];

            $whereClauses[] = "c.id = $categoryId";

            $sizeIds = $request->get('sizes');

            if ($sizeIds) {
                $sizeIds = implode(",", $sizeIds);

                $whereClauses[] = "s.id in ($sizeIds)";
            }

            $brandIds = $request->get('brands');

            if ($brandIds) {
                $brandIds = implode(",", $brandIds);

                $whereClauses[] = "b.id in ($brandIds)";
            }

            $sort = $request->get('sort');

            if ($sort === 'price-asc') {
                $order = ["p.price", "ASC"];
            }

            if ($sort === 'price-desc') {
                $order = ["p.price", "DESC"];
            }

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
                    'title' => $categoryTitle,
                    'url' => $this->generateUrl('category', ['gender' => $gender, 'id' => $categoryId]),
                ],
            ];

            return $this->render('site/catalog.html.twig', [
                'entity' => $category,
                'entityType' => 'category',
                'title' => ucwords("$gender $categoryTitle | ASOS"),
                'description' => $categoryDescription,
                'gender' => $gender,
                'brandConfig' => $brandConfig,
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