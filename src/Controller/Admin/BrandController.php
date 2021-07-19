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