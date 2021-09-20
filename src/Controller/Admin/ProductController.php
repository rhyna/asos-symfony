<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Size;
use App\Service\Pagination\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/product", name="admin.product.")
 */
class ProductController extends AbstractController
{
    private PaginationService $paginationService;
    private EntityManagerInterface $em;
    private Filesystem $fileSystem;

    public function __construct(PaginationService $paginationService, EntityManagerInterface $em, Filesystem $fileSystem)
    {
        $this->paginationService = $paginationService;
        $this->em = $em;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     * @throws \SystemErrorException
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Product::class);

        $whereClauses = [];

        $order = [];

        $page = $request->get('page');

        if (!$page || (string)(int)$page !== $page) {
            $page = 1;
        }

        $page = (int)$page;

        $categoryIds = $request->get('categories');

        if ($categoryIds) {
            $categoryIds = implode(",", $categoryIds);

            $whereClauses[] = "c.id in ($categoryIds)";
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

        $totalProducts = $repository->countProductList($whereClauses);

        $pagination = $this->paginationService->calculate($page, 10, $totalProducts);

        $products = $repository->getProductList($whereClauses, $order, $pagination->limit, $pagination->offset);

        $brandsData = $this->em->getRepository(Brand::class)->getAllBrandsIdAndTitle();

        $categoryLevels = $this->em->getRepository(Category::class)->getCategoryLevels();

        $categoriesByGender = [];

        foreach ($categoryLevels as $root) {
            $categoriesByGender[$root['title']] = [];

            foreach ($root['childCategory1'] as $level1) {
                foreach ($level1['childCategory2'] as $level2) {
                    $array = [];
                    $array['id'] = $level2['id'];
                    $array['title'] = $level2['title'];
                    $array['parentCategoryTitle'] = $level2['parentTitle'];
                    $categoriesByGender[$root['title']][] = $array;
                }
            }
        }

        return $this->render('admin/product/list.html.twig', [
            'products' => $products,
            'title' => 'Product List',
            'entityType' => 'product',
            'pagination' => $pagination,
            'page' => $page,
            'brandsData' => $brandsData,
            'categoriesByGender' => $categoriesByGender,
        ]);
    }

    /**
     * @Route(path="/add", methods={"GET"}, name="add.form")
     */
    public function addForm(Request $request): Response
    {
        $categoryLevels = $this->em->getRepository(Category::class)->getCategoryLevels();

        $brands = $this->em->getRepository(Brand::class)->getBrandListSortedByTitle();

        return $this->render('admin/product/form.html.twig', [
            'title' => 'Add Product',
            'mode' => 'add-product',
            'categoryLevels' => $categoryLevels,
            'sizeIds' => [],
            'brands' => $brands,
            'images' => [
                'image' => '',
                'image1' => '',
                'image2' => '',
                'image3' => '',
            ],
        ]);
    }

    /**
     * @Route(path="/add", methods={"POST"}, name="add.action")
     */
    public function addAction(Request $request): Response
    {

    }

    /**
     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
     */
    public function editForm(Request $request): Response
    {
        $categoryLevels = $this->em->getRepository(Category::class)->getCategoryLevels();

        $brands = $this->em->getRepository(Brand::class)->getBrandListSortedByTitle();

        $id = (int)$request->get('id');

        /**
         * @var Product $product ;
         */
        $product = $this->em->getRepository(Product::class)->find($id);

        $sizes = $product->getSizes();

        $sizeIds = [];

        /**
         * @var Size $size
         */
        foreach ($sizes as $size) {
            $sizeId = $size->getId();

            $sizeIds[] = $sizeId;
        }

        return $this->render('admin/product/form.html.twig', [
            'title' => 'Edit Product',
            'mode' => 'edit-product',
            'product' => $product,
            'categoryLevels' => $categoryLevels,
            'sizeIds' => $sizeIds,
            'brands' => $brands,
            'images' => [
                'image' => $product->getImage(),
                'image1' => $product->getImage1(),
                'image2' => $product->getImage2(),
                'image3' => $product->getImage3(),
            ],
        ]);
    }

    /**
     * @Route(path="/edit", methods={"POST"}, name="edit.action")
     */
    public function editAction(Request $request): Response
    {

    }

    /**
     * @Route(path="/delete", methods={"GET"}, name="delete.form")
     */
    public function deleteForm(Request $request): Response
    {

    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request): Response
    {

    }

    /**
     * @Route(path="/product-sizes", methods={"POST"}, name="product-sizes")
     */
    public function getProductSizes(Request $request): Response
    {
        try {
            $categoryId = (int)$request->get('categoryId');

            if (!$categoryId) {
                throw new \BadRequestException('Category id not provided');
            }

            /**
             * @var Category $category
             */
            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                throw new \NotFoundException('Category not found');
            }

            /**
             * @var Category $parentCategory
             */
            $parentCategory = $category->getParent();

            if (!$parentCategory) {
                throw new \NotFoundException('Parent category not found');
            }

            $sizes = $parentCategory->getSizesOrderedBySort();

            $sizeData = [];

            /**
             * @var Size $size
             */
            foreach ($sizes as $size) {
                $arr = [];

                $arr['id'] = $size->getId();

                $arr['title'] = $size->getTitle();

                $sizeData[] = $arr;
            }

            return new Response(json_encode($sizeData), 200);

        } catch (\BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (\NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }

}