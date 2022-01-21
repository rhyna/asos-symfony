<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\SearchWord;
use App\Entity\Size;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Exception\SystemErrorException;
use App\Exception\ValidationErrorException;
use App\Service\Filter\CategoryFilterService;
use App\Service\PageDeterminerService;
use App\Service\Pagination\PaginationService;
use App\Service\Search\SearchService;
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
    private PageDeterminerService $pageDeterminerService;
    private SearchService $searchService;
    private CategoryFilterService $categoryFilterService;

    public function __construct(PaginationService      $paginationService,
                                EntityManagerInterface $em,
                                Filesystem             $fileSystem,
                                PageDeterminerService  $pageDeterminerService,
                                SearchService          $searchService,
                                CategoryFilterService  $categoryFilterService)
    {
        $this->paginationService = $paginationService;
        $this->em = $em;
        $this->fileSystem = $fileSystem;
        $this->pageDeterminerService = $pageDeterminerService;
        $this->searchService = $searchService;
        $this->categoryFilterService = $categoryFilterService;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     * @throws SystemErrorException
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Product::class);

        $select = 'p.title, p.productCode, p.price, p.image, 
                c.id as categoryId, c.title as categoryTitle, 
                b.id as brandId, b.title as brandTitle';

        $where = [];

        $join = [
            [
                'clause' => 'p.category',
                'alias' => 'c',
                'type' => 'join',
            ],
            [
                'clause' => 'p.brand',
                'alias' => 'b',
                'type' => 'leftJoin',
            ],

        ];

        $order = [];

        $page = $this->pageDeterminerService->determinePage();

        $categoryIds = $request->get('categories');

        if ($categoryIds) {
            $categoryIds = implode(",", $categoryIds);

            $where[] = "c.id in ($categoryIds)";
        }

        $brandIds = $request->get('brands');

        if ($brandIds) {
            $brandIds = implode(",", $brandIds);

            $where[] = "b.id in ($brandIds)";
        }

        $sort = $request->get('sort');

        if ($sort === 'price-asc') {
            $order = ["p.price", "ASC"];
        }

        if ($sort === 'price-desc') {
            $order = ["p.price", "DESC"];
        }

        $totalProducts = $repository->countProductsInList($join, $where);

        $pagination = $this->paginationService->calculate($page, 10, $totalProducts);

        $products = $repository->getProductList($select, $join, $where, $order, $pagination->limit, $pagination->offset);

        $brandsData = $this->em->getRepository(Brand::class)->getAllBrandsIdAndTitle();

        $categoriesByGender = $this->categoryFilterService->getCategoriesByGender();

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
     * @throws NotFoundException
     * @throws BadRequestException
     * @throws ValidationErrorException
     */
    private function getProductDataForAddAndEdit(Request $request): array
    {
        $title = $request->get('title');

        if (!$title) {
            throw new BadRequestException('No title provided');
        }

        $productCode = (int)$request->get('productCode');

        if (!$productCode) {
            throw new BadRequestException('No product code provided');
        }

        $price = $request->get('price');

        if (!$price) {
            throw new BadRequestException('No price provided');
        }

        $price = (float)$price;

        $details = $request->get('productDetails') ?: null;

        $categoryId = (int)$request->get('categoryId');

        if (!$categoryId) {
            throw new BadRequestException('No category id provided');
        }

        /**
         * @var Category $category
         */
        $category = $this->em->getRepository(Category::class)->find($categoryId);

        if (!$category) {
            throw new NotFoundException('Category not found');
        }

        $sizeIds = $request->get('sizes');

        if (!$sizeIds) {
            throw new BadRequestException('No size id(s) provided');
        }

        $sizes = $this->getSizeArray($sizeIds);

        $brand = $this->getProductBrand($request);

        $lookAfterMe = $request->get('lookAfterMe') ?: null;

        $aboutMe = $request->get('aboutMe') ?: null;

        $imageData = $this->getImageData($request);

        $searchWordData = [
            $title,
            $category->getTitle(),
            $category->getParent()->getTitle(),
            $category->getParent()->getParent()->getTitle(),
            $details,
            $brand ? $brand->getTitle() : null,
        ];

        $normalizedSearchWords = $this->searchService->normalizeSearchWords($searchWordData);

        return [
            'title' => $title,
            'productCode' => $productCode,
            'price' => $price,
            'details' => $details,
            'category' => $category,
            'sizes' => $sizes,
            'brand' => $brand,
            'lookAfterMe' => $lookAfterMe,
            'aboutMe' => $aboutMe,
            'imageData' => $imageData,
            'normalizedSearchWords' => $normalizedSearchWords,
        ];
    }

    /**
     * @Route(path="/add", methods={"POST"}, name="add.action")
     */
    public function addAction(Request $request): Response
    {
        try {
            $productData = $this->getProductDataForAddAndEdit($request);

            $productCodeExists = $this->em->getRepository(Product::class)->findOneBy(['productCode' => $productData['productCode']]);

            if ($productCodeExists) {
                throw new ValidationErrorException('A product with such a product code already exists');
            }

            $product = new Product($productData['productCode'], $productData['price'], $productData['title'], $productData['category']);

            $product->setBrand($productData['brand']);

            $product->setProductDetails($productData['details']);

            $product->setAboutMe($productData['aboutMe']);

            $product->setLookAfterMe($productData['lookAfterMe']);

            foreach ($productData['sizes'] as $size) {
                $product->addSize($size);
            }

            foreach ($productData['imageData'] as $image => $data) {
                if (!$data) {
                    continue;
                }

                if ($image === 'image') {
                    $product->setImage($data['destination']);

                    $data['object']->move($this->getParameter('public_dir') . $data['directory'], $data['uniqueName']);
                }

                if ($image === 'image1') {
                    $product->setImage1($data['destination']);

                    $data['object']->move($this->getParameter('public_dir') . $data['directory'], $data['uniqueName']);
                }

                if ($image === 'image2') {
                    $product->setImage2($data['destination']);

                    $data['object']->move($this->getParameter('public_dir') . $data['directory'], $data['uniqueName']);
                }

                if ($image === 'image3') {
                    $product->setImage3($data['destination']);

                    $data['object']->move($this->getParameter('public_dir') . $data['directory'], $data['uniqueName']);
                }
            }

            foreach ($productData['normalizedSearchWords'] as $word) {
                $searchWord = $this->em->getRepository(SearchWord::class)->findOneBy(['word' => $word]);

                if (!$searchWord) {
                    $searchWord = new SearchWord($word);
                }

                $product->addSearchWord($searchWord);

                $this->em->persist($searchWord);
            }

            $this->em->persist($product);

            $this->em->flush();

            return $this->redirectToRoute('admin.product.edit.form', ['id' => $product->getId()]);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (ValidationErrorException $e) {
            return new Response($e->getMessage(), 422);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    private function validateProductImage(array $image): bool
    {
        $extensions = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];

        $errorMessages = [];

        $imageErrors = [];

        if ($image['size'] > 1000000) {
            $errorMessages[] = 'A file size can be 1 Mb max';
        }

        if (!in_array($image['type'], $extensions)) {
            $errorMessages[] = 'The file is not an image. Eligible extensions: png, jpeg, jpg, gif';
        }

        if ($errorMessages) {
            $imageErrors[$image['name']] = $errorMessages;
        }

        return $imageErrors ? false : true;
    }

    /**
     * @throws NotFoundException
     */
    private function getSizeArray(array $sizeIds): array
    {
        $sizes = [];

        foreach ($sizeIds as $sizeId) {
            $size = $this->em->getRepository(Size::class)->find((int)$sizeId);

            if (!$size) {
                throw new NotFoundException('Size not found');
            }

            $sizes[] = $size;
        }

        return $sizes;
    }

    /**
     * @throws NotFoundException
     */
    private function getProductBrand(Request $request): ?Brand
    {
        $brandId = $request->get('brandId') ? (int)$request->get('brandId') : null;

        $brand = null;

        if ($brandId) {
            /**
             * @var Brand $brand
             */
            $brand = $this->em->getRepository(Brand::class)->find($brandId);

            if (!$brand) {
                throw new NotFoundException('Brand not found');
            }
        }

        return $brand;
    }

    /**
     * @throws ValidationErrorException
     */
    private function getImageData(Request $request): array
    {
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

                $directory = '/upload/product/';

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

        return $imageData;
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
     * @Route(path="/edit/{id}", methods={"POST"}, name="edit.action")
     */
    public function editAction(Request $request): Response
    {
        try {
            $id = (int)$request->get('id');

            if (!$id) {
                throw new BadRequestException('No id provided');
            }

            /**
             * @var Product $product
             */
            $product = $this->em->getRepository(Product::class)->find($id);

            if (!$product) {
                throw new BadRequestException('Product not found');
            }

            $productData = $this->getProductDataForAddAndEdit($request);

            /**
             * @var Product $productByProductCode
             */
            $productByProductCode = $this->em->getRepository(Product::class)->findOneBy(['productCode' => $productData['productCode']]);

            if ($productByProductCode) {
                $productByProductCodeId = (int)$productByProductCode->getId();

                if ($productByProductCodeId !== $id) {
                    throw new ValidationErrorException('A product with such a product code already exists');
                }
            }

            $product->setTitle($productData['title']);

            $product->setProductCode($productData['productCode']);

            $product->setPrice($productData['price']);

            $product->setCategory($productData['category']);

            $product->setBrand($productData['brand']);

            $product->setProductDetails($productData['details']);

            $product->setAboutMe($productData['aboutMe']);

            $product->setLookAfterMe($productData['lookAfterMe']);

            $product->deleteSizes();

            foreach ($productData['sizes'] as $size) {
                $product->addSize($size);
            }

            $unusedSearchWordIds = $this->searchService->findUnusedSearchWordsToDelete($product);

            $product->deleteSearchWords();

            $searchWordRepository = $this->em->getRepository(SearchWord::class);

            $searchWordRepository->deleteUnusedSearchWords($unusedSearchWordIds);

            foreach ($productData['normalizedSearchWords'] as $word) {
                $searchWord = $searchWordRepository->findOneBy(['word' => $word]);

                if (!$searchWord) {
                    $searchWord = new SearchWord($word);
                }

                $product->addSearchWord($searchWord);

                $this->em->persist($searchWord);
            }

            foreach ($productData['imageData'] as $image => $data) {
                if (!$data) {
                    continue;
                }

                if ($image === 'image') {
                    $prevImage = $product->getImage();

                    $product->setImage($data['destination']);

                    $this->moveNewImageAndRemoveOld($data, $prevImage);
                }

                if ($image === 'image1') {
                    $prevImage = $product->getImage1();

                    $product->setImage1($data['destination']);

                    $this->moveNewImageAndRemoveOld($data, $prevImage);
                }

                if ($image === 'image2') {
                    $prevImage = $product->getImage2();

                    $product->setImage2($data['destination']);

                    $this->moveNewImageAndRemoveOld($data, $prevImage);
                }

                if ($image === 'image3') {
                    $prevImage = $product->getImage3();

                    $product->setImage3($data['destination']);

                    $this->moveNewImageAndRemoveOld($data, $prevImage);
                }
            }

            $this->em->flush();

            return $this->redirectToRoute('admin.product.edit.form', ['id' => $product->getId()]);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (ValidationErrorException $e) {
            return new Response($e->getMessage(), 422);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    private function moveNewImageAndRemoveOld(array $data, ?string $prevImage): void
    {
        $data['object']->move($this->getParameter('public_dir') . $data['directory'], $data['uniqueName']);

        if ($prevImage) {
            $this->fileSystem->remove($this->getParameter('public_dir') . $prevImage);
        }
    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request): Response
    {
        try {
            $id = (int)$request->get('id');

            if (!$id) {
                throw new BadRequestException('No product id provided');
            }

            /**
             * @var Product $product
             */
            $product = $this->em->getRepository(Product::class)->find($id);

            if (!$product) {
                throw new NotFoundException('Product not found');
            }

            $image = $product->getImage();

            $image1 = $product->getImage1();

            $image2 = $product->getImage2();

            $image3 = $product->getImage3();

            $images = [$image, $image1, $image2, $image3];

            $searchWordsToDelete = $this->searchService->findUnusedSearchWordsToDelete($product);

            $this->em->remove($product);

            $this->em->flush();

            foreach ($images as $image) {
                if ($image) {
                    $this->fileSystem->remove($this->getParameter('public_dir') . $image);
                }
            }

            $this->em->getRepository(SearchWord::class)->deleteUnusedSearchWords($searchWordsToDelete);

            return new Response('Successfully deleted the product', 200);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
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
                return new Response(json_encode([]), 200);
            }

            /**
             * @var Category $parentCategory
             */
            $parentCategory = $category->getParent();

            if (!$parentCategory) {
                throw new NotFoundException('Parent category not found');
            }

            $sizes = $parentCategory->getSizesOrderedBySort();

            $sizeData = [];

            /**
             * @var Size $size
             */
            foreach ($sizes as $size) {
                $arr = [
                    'id' => $size->getId(),
                    'title' => $size->getTitle(),
                ];

                $sizeData[] = $arr;
            }

            return new Response(json_encode($sizeData), 200);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @throws ValidationErrorException
     */
    private function validateImage(UploadedFile $image)
    {
        $extensions = ['png', 'jpeg', 'jpg', 'gif', 'jfif'];

        $extension = $image->getClientOriginalExtension();

        if (!in_array($extension, $extensions)) {
            throw new ValidationErrorException("File extension should be: 'png', 'jpeg', 'jpg', 'gif'");
        }

        //$size = $image->getMaxFilesize();
    }

    /**
     * @Route(path="/delete-image", methods={"POST"}, name="delete-image.action")
     */
    public function deleteImageAction(Request $request): Response
    {
        try {
            $repository = $this->em->getRepository(Product::class);

            $productId = (int)$request->get('id');

            if (!$productId) {
                throw new BadRequestException('No product id provided');
            }

            /**
             * @var Product $product
             */
            $product = $repository->find($productId);

            if (!$product) {
                throw new NotFoundException('Product not found');
            }

            $imageName = $request->get('image');

            if (!$imageName) {
                throw new NotFoundException('Image not found');
            }

            if ($imageName === 'image') {
                $prevImage = $product->getImage();

                if ($prevImage) {
                    $this->fileSystem->remove($this->getParameter('public_dir') . $prevImage);
                }

                $product->setImage(null);
            }

            if ($imageName === 'image1') {
                $prevImage = $product->getImage1();

                if ($prevImage) {
                    $this->fileSystem->remove($this->getParameter('public_dir') . $prevImage);
                }

                $product->setImage1(null);

            }

            if ($imageName === 'image2') {
                $prevImage = $product->getImage2();

                if ($prevImage) {
                    $this->fileSystem->remove($this->getParameter('public_dir') . $prevImage);
                }

                $product->setImage2(null);
            }

            if ($imageName === 'image3') {

                $prevImage = $product->getImage3();

                if ($prevImage) {
                    $this->fileSystem->remove($this->getParameter('public_dir') . $prevImage);
                }

                $product->setImage3(null);
            }

            $this->em->flush();

            return new Response();

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }

}