<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Service\Pagination\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/category", name="admin.category.")
 */
class CategoryController extends AbstractController
{
    private PaginationService $paginationService;
    private EntityManagerInterface $em;

    public function __construct(PaginationService $paginationService, EntityManagerInterface $em)
    {
        $this->paginationService = $paginationService;
        $this->em = $em;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Category::class);

        $categoryLevels = $repository->getRootCategories();

        $categoryLevels = $repository->getFirstLevelCategories($categoryLevels);

        $categoryLevels = $repository->getSecondLevelCategories($categoryLevels);

        $repository = $this->em->getRepository(Category::class);

        $categoriesByGender = [];

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

        $page = $request->get('page');

        if (!$page || (string)(int)$page !== $page) {
            $page = 1;
        }

        $page = (int)$page;

        $totalItems = $repository->countCategoriesInList($whereClauses);

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
     * @Route(path="/add", methods={"GET"}, name="add.form")
     */
    public function addForm(Request $request): Response
    {

    }

    /**
     * @Route(path="/add", methods={"POST"}, name="add.action")
     */
    public function addAction(Request $request): Response
    {

    }

    /**
     * @Route(path="/edit", methods={"GET"}, name="edit.form")
     */
    public function editForm(Request $request): Response
    {

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

}