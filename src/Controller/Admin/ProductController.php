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
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        try {
            $title = $request->get('title');

            if (!$title) {
                throw new \BadRequestException('No title provided');
            }

            $productCode = (int)$request->get('productCode');

            if (!$productCode) {
                throw new \BadRequestException('No product code provided');
            }

            $productCodeExists = $this->em->getRepository(Product::class)->findOneBy(['productCode' => $productCode]);

            if ($productCodeExists) {
                throw new \ValidationErrorException('A product with such a product code already exists');
            }

            $price = $request->get('price');

            if (!$price) {
                throw new \BadRequestException('No price provided');
            }

            $price = (float)$price;

            $details = $request->get('productDetails') ?: null;

            $categoryId = (int)$request->get('categoryId');

            if (!$categoryId) {
                throw new \BadRequestException('No category id provided');
            }

            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                throw new \NotFoundException('Category not found');
            }

            $sizeIds = $request->get('sizes');

            if (!$sizeIds) {
                throw new \BadRequestException('No size id(s) provided');
            }

            $sizes = [];

            foreach ($sizeIds as &$sizeId) {
                $sizeId = (int)$sizeId;

                $size = $this->em->getRepository(Size::class)->find($sizeId);

                if (!$size) {
                    throw new \NotFoundException('Size not found');
                }

                $sizes[] = $size;
            }

            $brandId = $request->get('brandId') ? (int)$request->get('brandId') : null;

            $brand = null;

            if ($brandId) {
                $brand = $this->em->getRepository(Brand::class)->find($brandId);

                if (!$brand) {
                    throw new \NotFoundException('Brand not found');
                }
            }

            $lookAfterMe = $request->get('lookAfterMe') ?: null;

            $aboutMe = $request->get('aboutMe') ?: null;

            $imageNames = ['image', 'image1', 'image2', 'image3'];

            $imageData = [];

            foreach ($imageNames as $imageName) {
                /**
                 * @var UploadedFile $image
                 */
                $image = $request->files->get($imageName) ?: null;

                if ($image) {
                    $this->validateImage($image);

                    $uniqueName = uniqid() . '.' . $image->getClientOriginalExtension();

                    $directory = './upload/product/';

                    $destination = $directory . $uniqueName;

                    $arr = [];

                    $arr['object'] = $image;

                    $arr['directory'] = $directory;

                    $arr['uniqueName'] = $uniqueName;

                    $arr['destination'] = $destination;

                    $imageData[$imageName] = $arr;

                } else {
                    $imageData[$imageName] = null;
                }
            }

            $product = new Product($productCode, $price, $title, $category);

            $product->setBrand($brand);

            $product->setProductDetails($details);

            $product->setAboutMe($aboutMe);

            $product->setLookAfterMe($lookAfterMe);

            foreach ($sizes as $size) {
                $product->addSize($size);
            }

            foreach ($imageData as $image => $data) {
                if (!$data) {
                    continue;
                }

                if ($image === 'image') {
                    $product->setImage($data['destination']);

                    $data['object']->move($data['directory'], $data['uniqueName']);
                }

                if ($image === 'image1') {
                    $product->setImage1($data['destination']);

                    $data['object']->move($data['directory'], $data['uniqueName']);
                }

                if ($image === 'image2') {
                    $product->setImage2($data['destination']);

                    $data['object']->move($data['directory'], $data['uniqueName']);
                }

                if ($image === 'image3') {
                    $product->setImage3($data['destination']);

                    $data['object']->move($data['directory'], $data['uniqueName']);
                }
            }

            $this->em->persist($product);

            $this->em->flush();

            return $this->redirectToRoute('admin.product.list');

        } catch (\BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (\NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\ValidationErrorException $e) {
            return new Response($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
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
            $categoryId = $request->get('categoryId') ?: null;

            $categoryId = (int)$categoryId;

            /**
             * @var Category $category
             */
            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                $sizeData = [];

                return new Response(json_encode($sizeData), 200);
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

    private function validateImage(UploadedFile $image)
    {
        $extensions = ['png', 'jpeg', 'jpg', 'gif'];

        $extension = $image->getClientOriginalExtension();

        if (!in_array($extension, $extensions)) {
            throw new \ValidationErrorException("File extension should be: 'png', 'jpeg', 'jpg', 'gif'");
        }

        //$size = $image->getMaxFilesize();
    }


}