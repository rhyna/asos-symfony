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

    public function __construct(PaginationService      $paginationService,
                                EntityManagerInterface $em,
                                Filesystem             $fileSystem,
                                PageDeterminerService  $pageDeterminerService)
    {
        $this->paginationService = $paginationService;
        $this->em = $em;
        $this->fileSystem = $fileSystem;
        $this->pageDeterminerService = $pageDeterminerService;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     * @throws SystemErrorException
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Category::class);

        $categoriesByGender = [];

        $categoryLevels = $repository->getCategoryLevels();

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
            $ids = implode(',', $ids);

            $whereClauses[] = "cp.id IN ($ids)";
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

//    /**
//     * @Route(path="/add", methods={"GET"}, name="add.form")
//     */
//    public function addForm(Request $request): Response
//    {
//        $repository = $this->em->getRepository(Category::class);
//
//        $categoryLevels = $repository->getCategoryLevels();
//
//        return $this->render('admin/category/form.html.twig', [
//            'title' => 'Add Category',
//            'categories' => $categoryLevels,
//        ]);
//    }
//
//    /**
//     * @Route(path="/add", methods={"POST"}, name="add.action")
//     */
//    public function addAction(Request $request): Response
//    {
//        $title = (string)$request->get('title');
//
//        $parentCategoryId = (int)$request->get('parent');
//
//        $repository = $this->em->getRepository(Category::class);
//
//        $parent = $repository->find($parentCategoryId);
//
//        $description = (string)$request->get('description') ?: null;
//
//        $image = $request->files->get('image') ?: null;
//
//        $imageDestination = null;
//
//        if ($image) {
//            $imageUniqueName = uniqid() . '.' . $image->getClientOriginalExtension();
//
//            $imageDirectory = './upload/category/';
//
//            $imageDestination = $imageDirectory . $imageUniqueName;
//        }
//
//        $category = new Category($title, $parent);
//
//        $category->setDescription($description);
//
//        $category->setImage($imageDestination);
//
//        $this->em->persist($category);
//
//        $this->em->flush();
//
//        if ($image) {
//            $image->move($imageDirectory, $imageUniqueName);
//        }
//
//        return $this->redirectToRoute('admin.category.list');
//    }

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

//    /**
//     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
//     */
//    public function editForm(Request $request): Response
//    {
//        $repository = $this->em->getRepository(Category::class);
//
//        $categoryLevels = $repository->getCategoryLevels();
//
//        $id = (int)$request->get('id');
//
//        $category = $repository->find($id);
//
//        return $this->render('admin/category/form.html.twig', [
//            'title' => 'Edit Category',
//            'categories' => $categoryLevels,
//            'category' => $category,
//            'entityType' => 'category',
//        ]);
//    }

//    /**
//     * @Route(path="/edit/{id}", methods={"POST"}, name="edit.action")
//     */
//    public function editAction(Request $request): Response
//    {
//        $repository = $this->em->getRepository(Category::class);
//
//        $id = (int)$request->get('id');
//
//        $title = (string)$request->get('title');
//
//        $parentCategoryId = (int)$request->get('parent');
//
//        $parent = $repository->find($parentCategoryId);
//
//        $description = (string)$request->get('description') ?: null;
//
//        $image = $request->files->get('image') ?: null;
//
//        $category = $repository->find($id);
//
//        $category->setTitle($title);
//
//        $category->setParent($parent);
//
//        $category->setDescription($description);
//
//        $previousImage = $category->getImage();
//
//        if ($image) {
//            $imageUniqueName = uniqid() . '.' . $image->getClientOriginalExtension();
//
//            $imageDirectory = './upload/category/';
//
//            $imageDestination = $imageDirectory . $imageUniqueName;
//
//            $category->setImage($imageDestination);
//        }
//
//        $this->em->flush();
//
//        if ($image) {
//            $image->move($imageDirectory, $imageUniqueName);
//
//            $this->fileSystem->remove($previousImage);
//        }
//
//        return $this->redirectToRoute('admin.category.list');
//    }

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

            $this->fileSystem->remove($this->getParameter('public_dir') . $categoryImage);

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

            $this->fileSystem->remove($this->getParameter('public_dir') . $imageToDelete);

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