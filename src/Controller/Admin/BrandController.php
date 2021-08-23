<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Service\Pagination\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/brand", name="admin.brand.")
 */
class BrandController extends AbstractController
{
    private EntityManagerInterface $em;
    private PaginationService $paginationService;

    public function __construct(EntityManagerInterface $em, PaginationService $paginationService)
    {
        $this->em = $em;
        $this->paginationService = $paginationService;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Brand::class);

        //$brands = $repository->findBy(array(), array('title' => 'ASC'));

        $totalBrands = $repository->countBrandList();

        $page = $request->get('page');

        if (!$page || (string)(int)$page !== $page) {
            $page = 1;
        }

        $page = (int)$page;

        $pagination = $this->paginationService->calculate($page, 10, $totalBrands);

        $brands = $repository->getBrandList($pagination->limit, $pagination->offset);

        return $this->render('admin/brand/list.html.twig',
            [
                'brands' => $brands,
                'title' => 'Brand List',
                'entityType' => 'brand',
                'pagination' => $pagination,
                'page' => $page,
            ]);
    }

    /**
     * @Route(path="/add", methods={"GET"}, name="add.form")
     */
    public function addForm(Request $request): Response
    {
        return $this->render('admin/brand/form.html.twig',
            [
                'title' => 'Add Brand',
            ]);
    }

    /**
     * @Route(path="/add", methods={"POST"}, name="add.action")
     */
    public function addAction(Request $request): Response
    {
        $title = (string)$request->get('title');

        $descriptionWomen = (string)$request->get('descriptionWomen') ?: null;

        $descriptionMen = (string)$request->get('descriptionMen') ?: null;

        $brand = new Brand($title);

        $brand->setDescriptionWomen($descriptionWomen);

        $brand->setDescriptionMen($descriptionMen);

        $this->em->persist($brand);

        $this->em->flush();

        return $this->redirectToRoute('admin.brand.list');

    }

    /**
     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
     */
    public function editForm(Request $request): Response
    {
        $id = (int)$request->get('id');

        $brand = $this->em->getRepository(Brand::class)->find($id);

        return $this->render('admin/brand/form.html.twig',
            [
                'brand' => $brand,
                'title' => 'Edit Brand',
            ]);
    }

    /**
     * @Route(path="/edit/{id}", methods={"POST"}, name="edit.action")
     */
    public function editAction(Request $request): Response
    {
        $id = (int)$request->get('id');

        $brand = $this->em->getRepository(Brand::class)->find($id);

        $title = (string)$request->get('title');

        $descriptionWomen = (string)$request->get('descriptionWomen') ?: null;

        $descriptionMen = (string)$request->get('descriptionMen') ?: null;

        $brand->setTitle($title);

        $brand->setDescriptionWomen($descriptionWomen);

        $brand->setDescriptionMen($descriptionMen);

        $this->em->flush();

        return $this->redirectToRoute('admin.brand.list');
    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request): Response
    {
        $id = $request->get('id');

        try {
            if (!$id) {
                throw new \BadRequestException('The id is not provided');
            }

            $id = (int)$id;

            $brand = $this->em->getRepository(Brand::class)->find($id);

            if (!$brand) {
                throw new \NotFoundException('Such a brand does not exist');
            }

//            $hasProducts = $brand->checkBrandProducts($conn);
//
//            if ($hasProducts) {
//                throw new ValidationErrorException("Cannot delete a brand that has products linked to it. <br> Delete the products first");
//            }

            $this->em->remove($brand);

            $this->em->flush();

            return new Response('Successfully deleted the brand', 200);

        } catch (\BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (\NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}