<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Banner;
use App\Entity\BannerPlace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/banner", name="admin.banner.")
 */
class BannerController extends AbstractController
{
    /**
     * @Route(path="/", methods={"GET"}, name="list")
     */
    public function list(Request $request, EntityManagerInterface $em): Response
    {
        $repository = $em->getRepository(Banner::class);

        $banners = $repository->getAllBannersSortedByPlaceAlias();

        return $this->render('admin/banner/list.html.twig',
            [
                'banners' => $banners,
                'title' => 'Banner List',
            ]);
    }

    /**
     * @Route(path="/add", methods={"GET"}, name="add.form")
     */
    public function addForm(Request $request, EntityManagerInterface $em): Response
    {
        $repository = $em->getRepository(BannerPlace::class);
        $bannerPlaces = $repository->findAll();

        return $this->render('admin/banner/form.html.twig',
            [
                'bannerPlaces' => $bannerPlaces,
                'title' => 'Add Banner Form',
            ]);
    }

    /**
     * @Route(path="/add", methods={"POST"}, name="add.action")
     */
    public function addAction(Request $request): Response
    {
        /** @var UploadedFile $image */
        $image = $request->files->get('image');
        $image->move('./upload/banner', uniqid() . '.' . $image->getClientOriginalExtension());

        echo '<pre>';
        var_dump($request->files->get('image'));
        exit;
    }

    /**
     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
     */
    public function editForm(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->get('id');
        $repository = $em->getRepository(Banner::class);
        $banner = $repository->find($id);

        $repository = $em->getRepository(BannerPlace::class);
        $bannerPlaces = $repository->findAll();

        return $this->render('admin/banner/form.html.twig',
            [
                'banner' => $banner,
                'bannerPlaces' => $bannerPlaces,
                'title' => 'Edit Banner Form',
            ]);
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