<?php

declare(strict_types=1);

namespace App\Controller\Site;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Size;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\PageDeterminerService;
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
    private PageDeterminerService $pageDeterminerService;

    public function __construct(EntityManagerInterface $em,
                                PaginationService      $paginationService,
                                PageDeterminerService  $pageDeterminerService)
    {
        $this->em = $em;
        $this->paginationService = $paginationService;
        $this->pageDeterminerService = $pageDeterminerService;
    }

    /**
     * @Route("/{gender}/category/{id}", name="category")
     */
    public function category(Request $request): Response
    {
        try {
            $categoryId = (int)$request->get('id');

            if (!$categoryId) {
                throw new BadRequestException('No id provided');
            }

            $gender = $request->get('gender');

            /**
             * @var Category $category
             */
            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                throw new NotFoundException('No category found');
            }

            $categoryTitle = $category->getTitle();

            $categoryDescription = $category->getDescription();

            $brandConfig = $this->em->getRepository(Brand::class)->getBrandsTitleAndIdByCategory($categoryId);

            $sizeConfig = $this->em->getRepository(Size::class)->getUniqueSizesOfProductsByCategory($categoryId);

            $page = $this->pageDeterminerService->determinePage();

            $select = "p.title, p.price, p.image";

            $join = [
                [
                    'clause' => 'p.category',
                    'alias' => 'c',
                    'type' => 'join',
                ],
            ];

            $where = [];

            $order = [];

            $where[] = "c.id = $categoryId";

            $sizeIds = $request->get('sizes');

            if ($sizeIds) {
                $sizeIds = implode(",", $sizeIds);

                $where[] = "s.id in ($sizeIds)";

                $join[] = [
                    'clause' => 'p.sizes',
                    'alias' => 's',
                    'type' => 'join',
                ];
            }

            $brandIds = $request->get('brands');

            if ($brandIds) {
                $brandIds = implode(",", $brandIds);

                $where[] = "b.id in ($brandIds)";

                $join[] = [
                    'clause' => 'p.brand',
                    'alias' => 'b',
                    'type' => 'leftJoin',
                ];
            }

            $sort = $request->get('sort');

            if ($sort === 'price-asc') {
                $order = ["p.price", "ASC"];
            }

            if ($sort === 'price-desc') {
                $order = ["p.price", "DESC"];
            }

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

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}