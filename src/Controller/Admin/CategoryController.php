<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route(path="/admin/category", name="admin.category.")
 */
class CategoryController extends AbstractController
{

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     */
    public function list(Request $request, EntityManagerInterface $em): Response
    {
        $repository = $em->getRepository(Category::class);

        $categoryLevels = $repository->getRootCategories();

        $categoryLevels = $repository->getFirstLevelCategories($categoryLevels);

        $categoryLevels = $repository->getSecondLevelCategories($categoryLevels);

        $repository = $em->getRepository(Category::class);

        $categoriesByGender = [];

        foreach ($categoryLevels as $gender) {
            foreach ($gender['childCategory1'] as $category) {
                $data = [];

                $data['id'] = $category['id'];

                $data['title'] = $category['title'];

                $categoriesByGender[$gender['title']][] = $data;
            }
        }

        $params = [];

        $whereClauses = [];

        $ids = $request->get('ids');

        if ($ids) {
            $params['ids'] = $ids;

            $whereClauses[] = 'FIND_IN_SET(c.parent_id, :ids)';
        }

        $where = 'and ' . implode(' and ', $whereClauses);

        if (!$whereClauses) {
            $where = '';
        }

        $totalCategoriesInList = $repository->countCategoriesInList($where, $params);

        $categoryListIds = $repository->getCategoryListIds($params, $where);

        $categories = $repository->getCategoryList($categoryListIds);

        $page = $request->get('page');

        if (!$page || (string)(int)$page !== $page) {
            $page = 1;
        }

        $page = (int)$page;

        return $this->render('admin/category/list.html.twig', [
            'categories' => $categories,
            'title' => 'Category List',
            'entityType' => 'category',
            'categoriesByGender' => $categoriesByGender,
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