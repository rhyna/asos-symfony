<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Size;
use App\Service\Pagination\PaginationService;
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

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     */
    public function list(Request $request): Response
    {
        $categoryLevels = $this->em->getRepository(Category::class)->getCategoryLevels();

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
                throw new \BadRequestException('No category id provided');
            }

            $categoryId = (int)$categoryId;

            /**
             * @var Category $category ;
             */
            $category = $this->em->getRepository(Category::class)->find($categoryId);

            if (!$category) {
                throw new \NotFoundException('Such a category does not exist');
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

        } catch (\BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (\NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
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