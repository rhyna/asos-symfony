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
use Symfony\Component\Filesystem\Filesystem;

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
                'entityType' => 'banner'
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
                'title' => 'Add Banner',
            ]);
    }

    /**
     * @Route(path="/add", methods={"POST"}, name="add.action")
     */
    public function addAction(Request $request, EntityManagerInterface $em): Response
    {
        /** @var UploadedFile $image */
        $image = $request->files->get('image');

        $imageUniqueName = uniqid() . '.' . $image->getClientOriginalExtension();

        $imageDirectory = './upload/banner/';

        $imageDestination = $imageDirectory . $imageUniqueName;

        $bannerPlaceId = (int)$request->get('banner-place') ?: null;

        $link = (string)$request->get('link');

        $title = (string)$request->get('title') ?: null;

        $description = (string)$request->get('description') ?: null;

        $buttonLabel = (string)$request->get('button-label') ?: null;

        $banner = new Banner($imageDestination, $link);

        $banner->setTitle($title);

        $banner->setDescription($description);

        $banner->setButtonLabel($buttonLabel);

        $bannerPlace = null;

        if ($bannerPlaceId) {
            $repository = $em->getRepository(BannerPlace::class);

            $bannerPlace = $repository->find($bannerPlaceId);
        }

        $banner->setBannerPlace($bannerPlace);

        $em->persist($banner);

        $em->flush();

        $image->move($imageDirectory, $imageUniqueName);

        return $this->redirectToRoute('admin.banner.list');
    }

    /**
     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
     */
    public function editForm(Request $request, EntityManagerInterface $em): Response
    {
        $id = (int)$request->get('id');

        $repository = $em->getRepository(Banner::class);

        $banner = $repository->find($id);

        $repository = $em->getRepository(BannerPlace::class);

        $bannerPlaces = $repository->findAll();

        return $this->render('admin/banner/form.html.twig',
            [
                'banner' => $banner,
                'bannerPlaces' => $bannerPlaces,
                'title' => 'Edit Banner',
            ]);
    }

    /**
     * @Route(path="/edit/{id}", methods={"POST"}, name="edit.action")
     */
    public function editAction(Request $request, EntityManagerInterface $em, Filesystem $fileSystem): Response
    {
        /** @var UploadedFile $image */
        $image = $request->files->get('image');

        $imageDestination = null;

        if ($image) {

            $imageUniqueName = uniqid() . '.' . $image->getClientOriginalExtension();

            $imageDirectory = './upload/banner/';

            $imageDestination = $imageDirectory . $imageUniqueName;
        }

        $bannerPlaceId = (int)$request->get('banner-place') ?: null;

        $link = (string)$request->get('link');

        $title = (string)$request->get('title') ?: null;

        $description = (string)$request->get('description') ?: null;

        $buttonLabel = (string)$request->get('button-label') ?: null;

        $bannerId = (int)$request->get('id');

        $banner = $em->getRepository(Banner::class)->find($bannerId);


        $banner->setLink($link);

        $banner->setTitle($title);

        $banner->setDescription($description);

        $banner->setButtonLabel($buttonLabel);

        $previousImage = $banner->getImage();

        if ($imageDestination) {
            $banner->setImage($imageDestination);
        }

        $bannerPlace = null;

        if ($bannerPlaceId) {

            $repository = $em->getRepository(BannerPlace::class);

            $bannerPlace = $repository->find($bannerPlaceId);
        }

        $banner->setBannerPlace($bannerPlace);

        $em->flush();

        if ($image) {
            $image->move($imageDirectory, $imageUniqueName);
        }

        $fileSystem->remove($previousImage);

        return $this->redirectToRoute('admin.banner.list');
    }

    /**
     * @Route(path="/delete", methods={"POST"}, name="delete.action")
     */
    public function deleteAction(Request $request, EntityManagerInterface $em, Filesystem $fileSystem): Response
    {
        $id = $request->get('id');

        try {
            if (!$id) {
                throw new \BadRequestException('The id is not provided');
            }

            $id = (int)$id;

            $banner = $em->getRepository(Banner::class)->find($id);

            if (!$banner) {
                throw new \NotFoundException('Such a banner does not exist');
            }

            $bannerImage = $banner->getImage();

            $em->remove($banner);

            $em->flush();

            $fileSystem->remove($bannerImage);

            return new Response('Successfully deleted the banner', 200);

        } catch (\BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (\NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}