<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Exception\SystemErrorException;
use App\Exception\ValidationErrorException;
use App\Form\BrandForm\BrandFormType;
use App\Form\BrandForm\BrandDto;
use App\Service\PageDeterminerService;
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
    private PageDeterminerService $pageDeterminerService;

    public function __construct(EntityManagerInterface $em,
                                PaginationService      $paginationService,
                                PageDeterminerService  $pageDeterminerService)
    {
        $this->em = $em;
        $this->paginationService = $paginationService;
        $this->pageDeterminerService = $pageDeterminerService;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     * @throws SystemErrorException
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Brand::class);

        $totalBrands = $repository->countBrandList();

        $page = $this->pageDeterminerService->determinePage();

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

//    /**
//     * @Route(path="/add", methods={"GET"}, name="add.form")
//     */
//    public function addForm(Request $request): Response
//    {
//        return $this->render('admin/brand/form.html.twig',
//            [
//                'title' => 'Add Brand',
//            ]);
//    }

//    /**
//     * @Route(path="/add", methods={"POST"}, name="add.action")
//     */
//    public function addAction(Request $request): Response
//    {
//        $title = (string)$request->get('title');
//
//        $descriptionWomen = (string)$request->get('descriptionWomen') ?: null;
//
//        $descriptionMen = (string)$request->get('descriptionMen') ?: null;
//
//        $brand = new Brand($title);
//
//        $brand->setDescriptionWomen($descriptionWomen);
//
//        $brand->setDescriptionMen($descriptionMen);
//
//        $this->em->persist($brand);
//
//        $this->em->flush();
//
//        return $this->redirectToRoute('admin.brand.list');
//
//    }

    /**
     * @Route(path="/add", methods={"GET", "POST"}, name="add")
     */
    public function addFormAndAction(Request $request): Response
    {
        $dto = new BrandDto();

        $form = $this->createForm(BrandFormType::class, $dto);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderForm('admin/brand/form.html.twig', [
                'form' => $form,
                'title' => 'Add Brand',
            ]);
        }

        $brand = new Brand($dto->title);

        $brand->setDescriptionWomen($dto->descriptionWomen);

        $brand->setDescriptionMen($dto->descriptionMen);

        $this->em->persist($brand);

        $this->em->flush();

        return $this->redirectToRoute('admin.brand.edit', ['id' => $brand->getId()]);
    }

    /**
     * @Route(path="/edit/{id}", methods={"GET", "POST"}, requirements={"id"="\d+"}, name="edit")
     * @throws NotFoundException
     */
    public function editFormAndAction(Request $request): Response
    {
        $id = $request->get('id');

        /** @var Brand $brand */
        $brand = $this->em->getRepository(Brand::class)->find($id);

        if (!$brand) {
            throw new NotFoundException('Brand not found', 404);
        }

        $dto = new BrandDto();

        $dto->title = $brand->getTitle();

        $dto->descriptionMen = $brand->getDescriptionMen();

        $dto->descriptionWomen = $brand->getDescriptionWomen();

        $form = $this->createForm(BrandFormType::class, $dto);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderForm('admin/brand/form.html.twig', [
                'form' => $form,
                'title' => 'Edit Brand',
            ]);
        }

        $brand->setTitle($dto->title);

        $brand->setDescriptionWomen($dto->descriptionWomen);

        $brand->setDescriptionMen($dto->descriptionMen);

        $this->em->flush();

        return $this->redirectToRoute('admin.brand.edit', ['id' => $brand->getId()]);
    }

//    /**
//     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
//     */
//    public function editForm(Request $request): Response
//    {
//        $id = (int)$request->get('id');
//
//        $brand = $this->em->getRepository(Brand::class)->find($id);
//
//        return $this->render('admin/brand/form.html.twig',
//            [
//                'brand' => $brand,
//                'title' => 'Edit Brand',
//            ]);
//    }
//
//    /**
//     * @Route(path="/edit/{id}", methods={"POST"}, name="edit.action")
//     * @throws NotFoundException
//     */
//    public function editAction(Request $request): Response
//    {
//        $id = (int)$request->get('id');
//
//        $brand = $this->em->getRepository(Brand::class)->find($id);
//
//        if (!$brand) {
//            throw new NotFoundException('Brand not found');
//        }
//
//        $title = (string)$request->get('title');
//
//        $descriptionWomen = (string)$request->get('descriptionWomen') ?: null;
//
//        $descriptionMen = (string)$request->get('descriptionMen') ?: null;
//
//        $brand->setTitle($title);
//
//        $brand->setDescriptionWomen($descriptionWomen);
//
//        $brand->setDescriptionMen($descriptionMen);
//
//        $this->em->flush();
//
//        return $this->redirectToRoute('admin.brand.list');
//    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request): Response
    {
        $id = $request->get('id');

        try {
            if (!$id) {
                throw new BadRequestException('The id is not provided');
            }

            $id = (int)$id;

            /**
             * @var Brand $brand
             */
            $brand = $this->em->getRepository(Brand::class)->find($id);

            if (!$brand) {
                throw new NotFoundException('Such a brand does not exist');
            }

            $productsByBrand = $brand->getProducts();

            if ($productsByBrand->count()) {
                throw new ValidationErrorException('This brand has product(s). Delete the product(s) first');
            }

            $this->em->remove($brand);

            $this->em->flush();

            return new Response('Successfully deleted the brand', 200);

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
     * @Route(path="/add-from-product", methods={"POST"}, name="add-from-product.action")
     */
    public function addFromProductAction(Request $request): Response
    {
        try {
            $title = $request->get('title');

            if (!$title) {
                throw new BadRequestException('Brand title not provided');
            }

            $descriptionWomen = $request->get('descriptionWomen') ?: null;

            $descriptionMen = $request->get('descriptionMen') ?: null;

            $brand = new Brand($title);

            $brand->setDescriptionMen($descriptionMen);

            $brand->setDescriptionWomen($descriptionWomen);

            $this->em->persist($brand);

            $this->em->flush();

            $brandData = [
                'id' => $brand->getId(),
                'title' => $brand->getTitle(),
            ];

            return new Response(json_encode($brandData), 200);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}