<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Size;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Exception\ValidationErrorException;
use App\Service\CategoryLevelsBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/size", name="admin.size.")
 */
class SizeController extends AbstractController
{
    private EntityManagerInterface $em;
    private CategoryLevelsBuilder $categoryLevelsBuilder;

    public function __construct(EntityManagerInterface $em, CategoryLevelsBuilder $categoryLevelsBuilder)
    {
        $this->em = $em;
        $this->categoryLevelsBuilder = $categoryLevelsBuilder;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     */
    public function list(Request $request): Response
    {
        $categoryLevels = $this->categoryLevelsBuilder->getCategoryLevels();

        return $this->render('admin/size/list.html.twig', [
            'title' => 'Manage Sizes',
            'entityType' => 'size',
            'categoryLevels' => $categoryLevels,
        ]);
    }

    /**
     * @Route(path="/", methods={"POST"}, name="list.form")
     */
    public function listForm(Request $request): Response
    {
        try {
            $categoryId = $request->get('categoryId');

            if (!$categoryId) {
                throw new BadRequestException('No category id provided');
            }

            $categoryId = (int)$categoryId;

            /**
             * @var Category $category ;
             */
            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                throw new NotFoundException('Such a category does not exist');
            }

            $sizesByCategoryOrderedBySort = $category->getSizesOrderedBySort();

            $sizesData = [];

            /**
             * @var Size $size ;
             */
            foreach ($sizesByCategoryOrderedBySort as $size) {
                $arr = [];
                $arr['id'] = $size->getId();
                $arr['title'] = $size->getTitle();
                $arr['sortOrder'] = $size->getSortOrder();
                $sizesData[] = $arr;
            }

            return new Response(json_encode($sizesData), 200);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @Route(path="/add", methods={"POST"}, name="add.action")
     */
    public function addAction(Request $request): Response
    {
        try {
            $response = [
                'errors' => []
            ];

            $sizeTitle = $request->get('size');

            if (!$sizeTitle) {
                throw new BadRequestException('No size title provided');
            }

            $categoryId = $request->get('categoryId');

            if (!$categoryId) {
                throw new BadRequestException('No category id provided');
            }

            $categoryId = (int)$categoryId;

            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                throw new NotFoundException('Such a category does not exist');
            }

            $sortOrder = $request->get('sortOrder');

            if (!isset($sortOrder)) {
                throw new BadRequestException('No sorting number field provided in the request');
            }

            $sortOrder = (int)$sortOrder;

            $lowerCaseTitle = mb_strtolower($sizeTitle);

            $normalizedTitle = str_replace(' ', '', $lowerCaseTitle);

            /**
             * @var Size $sizeByNormalizedTitle
             */
            $sizeByNormalizedTitle = $this->em->getRepository(Size::class)->findOneBy(['normalizedTitle' => $normalizedTitle]);

            /**
             * @var Size $sizeBySortOrder
             */
            $sizeBySortOrder = $this->em->getRepository(Size::class)->findOneBy(['sortOrder' => $sortOrder]);

            if ($sizeByNormalizedTitle) {
                $sizeByNormTitleCategories = $sizeByNormalizedTitle->getCategories();

                if ($sizeByNormTitleCategories->contains($category)) {
                    $response['errors'][] = 'Such a size already exists in the current category';
                }
            }

            if ($sizeBySortOrder) {
                $clause1 = !$sizeByNormalizedTitle;

                $clause2 = $sizeByNormalizedTitle && $sortOrder !== (int)$sizeByNormalizedTitle->getSortOrder();

                if ($clause1 || $clause2) {
                    $response['errors'][] = 'A size with such a sorting order already exists';
                }
            }

            if ($response['errors']) {
                return new Response(json_encode($response));
            }

            $size = $sizeByNormalizedTitle;

            if (!$sizeByNormalizedTitle) {
                $size = new Size($sizeTitle);

                if ($sortOrder) {
                    $size->setSortOrder($sortOrder);
                }

                $this->em->persist($size);
            }

            $this->em->getRepository(Category::class)->find($categoryId)->addSize($size);

            $this->em->flush();

            $addedSizeId = $size->getId();

            $addedSizeTitle = $size->getTitle();

            $addedSizeSortOrder = $size->getSortOrder();

            $sizeData = [
                'id' => $addedSizeId,
                'title' => $addedSizeTitle,
                'sortOrder' => $addedSizeSortOrder,
            ];

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
     * @Route(path="/edit", methods={"POST"}, name="edit.action")
     */
    public function editAction(Request $request): Response
    {
        try {
            $response = [
                'errors' => []
            ];

            $sizeTitle = $request->get('sizeTitle');

            if (!$sizeTitle) {
                throw new BadRequestException('No size title provided');
            }

            $sizeId = $request->get('sizeId');

            if (!$sizeId) {
                throw new BadRequestException('No size id provided');
            }

            $sizeId = (int)$sizeId;

            $sortOrder = $request->get('sortOrder');

            if (!isset($sortOrder)) {
                throw new BadRequestException('No sorting number field provided in the request');
            }

            $sortOrder = (int)$sortOrder;

            $lowerCaseTitle = mb_strtolower($sizeTitle);

            $normalizedTitle = str_replace(' ', '', $lowerCaseTitle);

            /**
             * @var Size $currentSize ;
             */
            $currentSize = $this->em->getRepository(Size::class)->find($sizeId);

            if (!$currentSize) {
                throw new NotFoundException('Such a size does not exist');
            }

            /**
             * @var Size $sizeByNormalizedTitle
             */
            $sizeByNormalizedTitle = $this->em->getRepository(Size::class)->findOneBy(['normalizedTitle' => $normalizedTitle]);

            /**
             * @var Size $sizeBySortOrder
             */
            $sizeBySortOrder = $this->em->getRepository(Size::class)->findOneBy(['sortOrder' => $sortOrder]);

            if ($sizeByNormalizedTitle) {
                $sizeByNormalizedTitleId = $sizeByNormalizedTitle->getId();

                if ($sizeId !== (int)$sizeByNormalizedTitleId) {
                    $response['errors'][] = 'Such a size already exists';
                }
            }

            if ($sizeBySortOrder) {
                $sizeBySortOrderId = $sizeBySortOrder->getId();

                if ($sizeId !== (int)$sizeBySortOrderId) {
                    $response['errors'][] = 'A size with such a sorting order already exists';
                }
            }

            if ($response['errors']) {
                return new Response(json_encode($response));
            }

            $currentSize->setTitle($sizeTitle);

            $currentSize->setNormalizedTitle($normalizedTitle);

            $sortOrder = $sortOrder ?: null;

            $currentSize->setSortOrder($sortOrder);

            $this->em->flush();

            $currentSizeTitle = $currentSize->getTitle();

            $currentSizeSortOrder = $currentSize->getSortOrder();

            $currentSizeData = [
                'title' => $currentSizeTitle,
                'sortOrder' => $currentSizeSortOrder,
            ];

            return new Response(json_encode($currentSizeData), 200);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request): Response
    {
        $id = (int)$request->get('id');

        if (!$id) {
            throw new BadRequestException('The id is not provided');
        }

        $categoryId = (int)$request->get('categoryId');

        if (!$categoryId) {
            throw new BadRequestException('The category id is not provided');
        }

        /**
         * @var Size $size
         */
        $size = $this->em->getRepository(Size::class)->find($id);

        if (!$size) {
            throw new NotFoundException('Such a size does not exist');
        }

        $sizeCategories = $size->getCategories();

        /**
         * @var Category $currentCategory ;
         */
        $currentCategory = $this->em->getRepository(Category::class)->find($categoryId);

        if (!$currentCategory) {
            throw new NotFoundException('Such a category does not exist');
        }

        $productsExist = $this->em->getRepository(Size::class)->checkProductsBySizeAndCategoryIds($id, $categoryId);

        if ($productsExist) {
            throw new ValidationErrorException('The size has associated product(s) in this category. Delete the products first');
        }

        $currentCategorySizes = $currentCategory->getSizes();

        if ($sizeCategories->contains($currentCategory)) {
            $sizeCategories->removeElement($currentCategory);
        }

        if ($currentCategorySizes->contains($size)) {
            $currentCategorySizes->removeElement($size);
        }

        $this->em->flush();

        return new Response('Successfully deleted the size', 200);
    }
}