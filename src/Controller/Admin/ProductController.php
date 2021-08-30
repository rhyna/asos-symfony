<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
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

        $products = $repository->getProductList($whereClauses, $order, 10000, 0);

        return $this->render('admin/product/list.html.twig', [
            'categories' => $products,
            'title' => 'Product List',
            'entityType' => 'product',
            //'pagination' => $pagination,
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