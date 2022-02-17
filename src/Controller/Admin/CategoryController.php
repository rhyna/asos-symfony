<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Exception\SystemErrorException;
use App\Exception\ValidationErrorException;
use App\Form\CategoryForm\CategoryDto;
use App\Form\CategoryForm\CategoryFormType;
use App\Service\CategoryLevelsBuilder;
use App\Service\PageDeterminerService;
use App\Service\Pagination\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @Route(path="/admin/category", name="admin.category.")
 */
class CategoryController extends AbstractController
{
    private PaginationService $paginationService;
    private EntityManagerInterface $em;
    private Filesystem $fileSystem;
    private PageDeterminerService $pageDeterminerService;
    private CategoryLevelsBuilder $categoryLevelsBuilder;

    public function __construct(PaginationService      $paginationService,
                                EntityManagerInterface $em,
                                Filesystem             $fileSystem,
                                PageDeterminerService  $pageDeterminerService,
                                CategoryLevelsBuilder  $categoryLevelsBuilder)
    {
        $this->paginationService = $paginationService;
        $this->em = $em;
        $this->fileSystem = $fileSystem;
        $this->pageDeterminerService = $pageDeterminerService;
        $this->categoryLevelsBuilder = $categoryLevelsBuilder;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     * @throws SystemErrorException
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Category::class);

        $categoriesByGender = [];

        $categoryLevels = $this->categoryLevelsBuilder->getCategoryLevels();

        foreach ($categoryLevels as $gender) {
            foreach ($gender['childCategory1'] as $category) {
                $data = [];

                $data['id'] = $category['id'];

                $data['title'] = $category['title'];

                $categoriesByGender[$gender['title']][] = $data;
            }
        }

        $whereClauses = [];

        $ids = $request->get('ids');

        if ($ids) {
            $arr['ids']['clause'] = "cp.id IN (:ids)";

            $arr['ids']['parameter'] = $ids;

            $whereClauses['ids'] = $arr['ids'];
        }

        $page = $this->pageDeterminerService->determinePage();

        $totalItems = $repository->countCategoriesList($whereClauses);

        $pagination = $this->paginationService->calculate($page, 10, $totalItems);

        $categories = $repository->getCategoryList($whereClauses, $pagination->limit, $pagination->offset);

        return $this->render('admin/category/list.html.twig', [
            'categories' => $categories,
            'title' => 'Category List',
            'entityType' => 'category',
            'categoriesByGender' => $categoriesByGender,
            'pagination' => $pagination,
            'page' => $page,
        ]);
    }

    /**
     * @Route(path="/add", methods={"GET", "POST"}, name="add")
     */
    public function addFormAndAction(Request $request): Response
    {
        $dto = new CategoryDto();

        $form = $this->createForm(CategoryFormType::class, $dto);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderForm('admin/category/form.html.twig', [
                'form' => $form,
                'title' => 'Add Category',
            ]);
        }

        $category = new Category($dto->title, $dto->parentCategory);

        $imageDestination = null;

        $imageDirectory = '';

        $imageUniqueName = '';

        if ($dto->image) {
            $imageUniqueName = uniqid() . '.' . $dto->image->getClientOriginalExtension();

            $imageDirectory = '/upload/category/';

            $imageDestination = $imageDirectory . $imageUniqueName;
        }

        $category->setImage($imageDestination);

        $category->setDescription($dto->description);

        $this->em->persist($category);

        $this->em->flush();

        if ($dto->image) {
            $dto->image->move($this->getParameter('public_dir') . $imageDirectory, $imageUniqueName);
        }

        return $this->redirectToRoute('admin.category.edit', ['id' => $category->getId()]);
    }

    /**
     * @Route(path="/edit/{id}", methods={"GET", "POST"}, name="edit")
     * @throws NotFoundException
     */
    public function editFormAndAction(Request $request): Response
    {
        /**
         * @var Category $category
         */
        $category = $this->em->getRepository(Category::class)->find($request->get('id'));

        if (!$category) {
            throw new NotFoundException('Category not found');
        }

        $dto = new CategoryDto();

        $dto->title = $category->getTitle();

        $dto->description = $category->getDescription();

        $dto->parentCategory = $category->getParent();

        $form = $this->createForm(CategoryFormType::class, $dto);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderForm('admin/category/form.html.twig', [
                'form' => $form,
                'title' => 'Edit Category',
                'category' => $category,
            ]);
        }

        $category->setTitle($dto->title);

        $category->setDescription($dto->description);

        $category->setParent($dto->parentCategory);

        if ($dto->image) {
            $imageUniqueName = uniqid() . '.' . $dto->image->getClientOriginalExtension();

            $imageDirectory = '/upload/category/';

            $imageDestination = $imageDirectory . $imageUniqueName;

            $previousImage = $category->getImage();

            $category->setImage($imageDestination);

            $dto->image->move($this->getParameter('public_dir') . $imageDirectory, $imageUniqueName);

            if ($previousImage) {
                $this->fileSystem->remove($this->getParameter('public_dir') . $previousImage);
            }
        }

        $this->em->flush();

        return $this->redirectToRoute('admin.category.edit', ['id' => $category->getId()]);
    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request): Response
    {
        $id = (int)$request->get('id');

        try {
            if (!$id) {
                throw new BadRequestException('The id is not provided');
            }

            /**
             * @var Category $category
             */
            $category = $this->em->getRepository(Category::class)->find($id);

            if (!$category) {
                throw new NotFoundException('Such a category does not exist');
            }

            $categoryImage = $category->getImage();

            $childCategories = $category->getChildren();

            if ($childCategories->count()) {
                throw new ValidationErrorException('There are child category/ies in this category. Delete the child category/ies first');
            }

            $productsByCategory = $category->getProducts();

            if ($productsByCategory->count()) {
                throw new ValidationErrorException('There are product(s) in this category. Delete the product(s) first');
            }

            $sizesByCategory = $category->getSizes();

            if ($sizesByCategory->count()) {
                throw new ValidationErrorException('There are size(s) in this category. Delete the size(s) first');
            }

            $this->em->remove($category);

            $this->em->flush();

            if ($categoryImage) {
                $this->fileSystem->remove($this->getParameter('public_dir') . $categoryImage);
            }

            return new Response('Successfully deleted the category', 200);

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

    /**
     * @Route(path="/delete-image", methods={"POST"}, name="delete-image.action")
     */
    public function deleteImageAction(Request $request): Response
    {
        try {
            $repository = $this->em->getRepository(Category::class);

            $categoryId = (int)$request->get('id');

            if (!$categoryId) {
                throw new BadRequestException('No category id provided');
            }

            $category = $repository->find($categoryId);

            if (!$category) {
                throw new NotFoundException('Such a category does not exist');
            }

            $imageToDelete = $category->getImage();

            $category->setImage(null);

            $this->em->flush();

            if ($imageToDelete) {
                $this->fileSystem->remove($this->getParameter('public_dir') . $imageToDelete);
            }

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