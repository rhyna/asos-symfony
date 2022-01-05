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
     * @Route(path="/add", methods={"GET"}, name="add.form")
     */
    public function addForm(Request $request): Response
    {
        $repository = $this->em->getRepository(BannerPlace::class);

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
    public function addAction(Request $request): Response
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
            $repository = $this->em->getRepository(BannerPlace::class);

            $bannerPlace = $repository->find($bannerPlaceId);
        }

        $banner->setBannerPlace($bannerPlace);

        $this->em->persist($banner);

        $this->em->flush();

        $image->move($imageDirectory, $imageUniqueName);

        return $this->redirectToRoute('admin.banner.list');
    }

    /**
     * @Route(path="/add-symfony-form", methods={"GET", "POST"}, name="add-symfony-form")
     */
    public function addSymfonyFormAction(Request $request): Response
    {
        // теперь в реквесте все отрисованные спец функциями поля будут иметь имена banner_add_form[НАЗВАНИЕ ПОЛЯ]

        // создание объекта для наполнения
        // объект можно наполнить прямо тут, если это форма редактирования
        $dto = new BannerDto();
        // создание формы из конфигурации
        $form = $this->createForm(BannerFormType::class, $dto);
        // проверка:
        // - была ли отправлена форма вообще. Это проверяется, если в request есть поля формы с именами вроде banner_add_form[НАЗВАНИЕ ПОЛЯ]
        // - валидации
        // - наполняется дто
        $form->handleRequest($request);

        // проверка, была ли форма отправлена или это отрисовка формы
        if (!$form->isSubmitted()) {
            return $this->renderForm('admin/banner/symfony-form.html.twig', [
                'form' => $form,
                'title' => 'Add Banner',
            ]);
        }

        // провера валидности формы
        if (!$form->isValid()) {
            // отрисовка ошибок
            return $this->renderForm('admin/banner/symfony-form.html.twig', [
                'form' => $form,
                'title' => 'Add Banner',
            ]);
        }

        // переливание данных из дто в ентити

        $imageUniqueName = uniqid() . '.' . $dto->image->getClientOriginalExtension();
        $imageDirectory = './upload/banner/';
        $imageDestination = $imageDirectory . $imageUniqueName;

        $banner = new Banner($imageDestination, $dto->link);
        $banner->setTitle($dto->title);
        $banner->setDescription($dto->description);
        $banner->setButtonLabel($dto->buttonLabel);
        $banner->setBannerPlace($dto->bannerPlace); // тут уже будет энтити, а не айди

        // запись в БД
        $this->em->persist($banner);
        $this->em->flush();

        $dto->image->move($imageDirectory, $imageUniqueName);

        return $this->redirectToRoute('admin.banner.list');
    }

    /**
     * requirements в параметрах ниже - это валидация параметров зашитых в урле. \d+ означает, что мы ожидаем
     * число (\d) и оно должно быть длиной больше нуля символов (+)
     * @Route(path="/edit-symfony-form/{id}", methods={"GET", "POST"}, requirements={"id"="\d+"}, name="edit-symfony-form")
     * @throws NotFoundException
     */
    public function editSymfonyFormAction(Request $request): Response
    {
        /** @var Banner $banner */
        $banner = $this->em->getRepository(Banner::class)->find($request->get('id'));
        if (!$banner) {
            throw new NotFoundException('banner is not found');
        }

        $dto = new BannerDto();
        $dto->bannerPlace = $banner->getBannerPlace();
        $dto->link = $banner->getLink();
        $dto->title = $banner->getTitle();
        $dto->description = $banner->getDescription();
        $dto->buttonLabel = $banner->getButtonLabel();

        $form = $this->createForm(BannerFormType::class, $dto);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderForm('admin/banner/symfony-form.html.twig', [
                'bannerImage' => $banner->getImage(),
                'form' => $form,
                'title' => 'Add Banner',
            ]);
        }

        // переливание данных из дто в ентити

        if ($dto->image) {
            $imageUniqueName = uniqid() . '.' . $dto->image->getClientOriginalExtension();
            $imageDirectory = './upload/banner/';
            $imageDestination = $imageDirectory . $imageUniqueName;
            $dto->image->move($imageDirectory, $imageUniqueName);

            $this->fileSystem->remove($banner->getImage());
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

        return $this->redirectToRoute('admin.banner.list');
    }

    /**
     * @Route(path="/edit/{id}", methods={"GET"}, name="edit.form")
     */
    public function editForm(Request $request): Response
    {
        $id = (int)$request->get('id');

        $repository = $this->em->getRepository(Banner::class);

        $banner = $repository->find($id);

        $repository = $this->em->getRepository(BannerPlace::class);

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
    public function editAction(Request $request): Response
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

        $banner = $this->em->getRepository(Banner::class)->find($bannerId);

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

            $repository = $this->em->getRepository(BannerPlace::class);

            $bannerPlace = $repository->find($bannerPlaceId);
        }

        $banner->setBannerPlace($bannerPlace);

        $this->em->flush();

        if ($image) {
            $image->move($imageDirectory, $imageUniqueName);

            $this->fileSystem->remove($previousImage);
        }

        return $this->redirectToRoute('admin.banner.list');
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

            $this->fileSystem->remove($bannerImage);

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