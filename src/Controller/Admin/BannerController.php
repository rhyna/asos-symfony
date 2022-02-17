<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Banner;
use App\Entity\BannerPlace;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Exception\SystemErrorException;
use App\Form\BannerForm\BannerFormType;
use App\Form\BannerForm\BannerDto;
use App\Service\PageDeterminerService;
use App\Service\Pagination\PaginationService;
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
    private EntityManagerInterface $em;
    private Filesystem $fileSystem;
    private PaginationService $paginationService;
    private PageDeterminerService $pageDeterminerService;

    public function __construct(EntityManagerInterface $em,
                                Filesystem             $fileSystem,
                                PaginationService      $paginationService,
                                PageDeterminerService  $pageDeterminerService)
    {
        $this->em = $em;
        $this->fileSystem = $fileSystem;
        $this->paginationService = $paginationService;
        $this->pageDeterminerService = $pageDeterminerService;
    }

    /**
     * @Route(path="/", methods={"GET"}, name="list")
     * @throws SystemErrorException
     */
    public function list(Request $request): Response
    {
        $repository = $this->em->getRepository(Banner::class);

        $page = $this->pageDeterminerService->determinePage();

        $totalBanners = $repository->countBannerList();

        $pagination = $this->paginationService->calculate($page, 10, $totalBanners);

        $banners = $repository->getBannersList($pagination->limit, $pagination->offset);

        return $this->render('admin/banner/list.html.twig',
            [
                'banners' => $banners,
                'title' => 'Banner List',
                'entityType' => 'banner',
                'pagination' => $pagination,
                'page' => $page,
            ]);
    }

    /**
     * @Route(path="/add", methods={"GET", "POST"}, name="add")
     */
    public function addFormAndAction(Request $request): Response
    {
        $dto = new BannerDto();

        $form = $this->createForm(BannerFormType::class, $dto);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->renderForm('admin/banner/form.html.twig', [
                'form' => $form,
                'title' => 'Add Banner',
            ]);
        }

        if (!$form->isValid()) {
            return $this->renderForm('admin/banner/form.html.twig', [
                'form' => $form,
                'title' => 'Add Banner',
            ]);
        }

        $imageUniqueName = uniqid() . '.' . $dto->image->getClientOriginalExtension();

        $imageDirectory = '/upload/banner/';

        $imageDestination = $imageDirectory . $imageUniqueName;

        $banner = new Banner($imageDestination, $dto->link);

        $banner->setTitle($dto->title);

        $banner->setDescription($dto->description);

        $banner->setButtonLabel($dto->buttonLabel);

        $banner->setBannerPlace($dto->bannerPlace);

        $this->em->persist($banner);

        $this->em->flush();

        $dto->image->move($this->getParameter('public_dir') . $imageDirectory, $imageUniqueName);

        return $this->redirectToRoute('admin.banner.edit', ['id' => $banner->getId()]);
    }

    /**
     * requirements в параметрах ниже - это валидация параметров зашитых в урле. \d+ означает, что мы ожидаем
     * число (\d) и оно должно быть длиной больше нуля символов (+)
     * @Route(path="/edit/{id}", methods={"GET", "POST"}, requirements={"id"="\d+"}, name="edit")
     * @throws NotFoundException
     */
    public function editFormAndAction(Request $request): Response
    {
        /** @var Banner $banner */
        $banner = $this->em->getRepository(Banner::class)->find($request->get('id'));

        if (!$banner) {
            throw new NotFoundException('Banner not found');
        }

        $dto = new BannerDto();

        $dto->bannerPlace = $banner->getBannerPlace();

        $dto->link = $banner->getLink();

        $dto->title = $banner->getTitle();

        $dto->description = $banner->getDescription();

        $dto->buttonLabel = $banner->getButtonLabel();

        $form = $this->createForm(BannerFormType::class, $dto, ['banner' => $banner]);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderForm('admin/banner/form.html.twig', [
                'bannerImage' => $banner->getImage(),
                'form' => $form,
                'title' => 'Edit Banner',
            ]);
        }

        if ($dto->image) {
            $imageUniqueName = uniqid() . '.' . $dto->image->getClientOriginalExtension();

            $imageDirectory = '/upload/banner/';

            $imageDestination = $imageDirectory . $imageUniqueName;

            $dto->image->move($this->getParameter('public_dir') . $imageDirectory, $imageUniqueName);

            $this->fileSystem->remove($this->getParameter('public_dir') . $banner->getImage());
        }

        $banner->setTitle($dto->title);

        $banner->setLink($dto->link);

        $banner->setDescription($dto->description);

        $banner->setButtonLabel($dto->buttonLabel);

        $banner->setBannerPlace($dto->bannerPlace);

        if (isset($imageDestination)) {
            $banner->setImage($imageDestination);
        }

        $this->em->flush();

        return $this->redirectToRoute('admin.banner.edit', ['id' => $banner->getId()]);
    }

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

            $banner = $this->em->getRepository(Banner::class)->find($id);

            if (!$banner) {
                throw new NotFoundException('Such a banner does not exist');
            }

            $bannerImage = $banner->getImage();

            $this->em->remove($banner);

            $this->em->flush();

            $this->fileSystem->remove($this->getParameter('public_dir') . $bannerImage);

            return new Response('Successfully deleted the banner', 200);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}