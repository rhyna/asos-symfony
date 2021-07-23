<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Brand;
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
    /**
     * @Route(path="/", methods={"GET"}, name="list")
     */
    public function list(Request $request, EntityManagerInterface $em): Response
    {
        $repository = $em->getRepository(Brand::class);

        $brands = $repository->findBy(array(), array('title' => 'ASC'));

        return $this->render('admin/brand/list.html.twig',
            [
                'brands' => $brands,
                'title' => 'Brand List',
                'entityType' => 'brand',
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
    public function addAction(Request $request, EntityManagerInterface $em): Response
    {
        $title = (string)$request->get('title');

        $descriptionWomen = (string)$request->get('descriptionWomen') ?: null;

        $descriptionMen = (string)$request->get('descriptionMen') ?: null;

        $brand = new Brand($title);

        $brand->setDescriptionWomen($descriptionWomen);

        $brand->setDescriptionMen($descriptionMen);

        $em->persist($brand);

        $em->flush();

        return $this->redirectToRoute('admin.brand.list');

    }

    /**
     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
     */
    public function editForm(Request $request, EntityManagerInterface $em): Response
    {
        $id = (int)$request->get('id');

        $brand = $em->getRepository(Brand::class)->find($id);

        return $this->render('admin/brand/form.html.twig',
            [
                'brand' => $brand,
                'title' => 'Edit Brand',
            ]);
    }

    /**
     * @Route(path="/edit/{id}", methods={"POST"}, name="edit.action")
     */
    public function editAction(Request $request, EntityManagerInterface $em): Response
    {
        $id = (int)$request->get('id');

        $brand = $em->getRepository(Brand::class)->find($id);

        $title = (string)$request->get('title');

        $descriptionWomen = (string)$request->get('descriptionWomen') ?: null;

        $descriptionMen = (string)$request->get('descriptionMen') ?: null;

        $brand->setTitle($title);

        $brand->setDescriptionWomen($descriptionWomen);

        $brand->setDescriptionMen($descriptionMen);

        $em->flush();

        return $this->redirectToRoute('admin.brand.list');
    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->get('id');

        try {
            if (!$id) {
                throw new \BadRequestException('The id is not provided');
            }

            $id = (int)$id;

            $brand = $em->getRepository(Brand::class)->find($id);

            if (!$brand) {
                throw new \NotFoundException('Such a brand does not exist');
            }

//            $hasProducts = $brand->checkBrandProducts($conn);
//
//            if ($hasProducts) {
//                throw new ValidationErrorException("Cannot delete a brand that has products linked to it. <br> Delete the products first");
//            }

            $em->remove($brand);

            $em->flush();

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