<?php

declare(strict_types=1);

namespace App\Controller\Site;

use App\Entity\Product;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductPageController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/product/{id}", name="product")
     */
    public function product(Request $request): Response
    {
        try {
            $id = (int)$request->get('id');

            if (!$id) {
                throw new BadRequestException('No id provided');
            }

            /**
             * @var Product $product
             */
            $product = $this->em->getRepository(Product::class)->find($id);

            if (!$product) {
                throw new NotFoundException('No product found');
            }

            $isRootMen = $product->getCategory()->getParent()->getParent()->isRootMenCategory();

            $isRootWomen = $product->getCategory()->getParent()->getParent()->isRootWomenCategory();

            $gender = '';

            if ($isRootMen) {
                $gender = 'men';
            }

            if ($isRootWomen) {
                $gender = 'women';
            }

            $brand = $product->getBrand();

            $brandDescription = '';

            if ($brand) {
                if ($gender === 'men') {
                    $brandDescription = $brand->getDescriptionMen();
                }

                if ($gender === 'women') {
                    $brandDescription = $brand->getDescriptionWomen();
                }
            }

            $images = array_filter([
                $product->getImage(),
                $product->getImage1(),
                $product->getImage2(),
                $product->getImage3(),
            ]);

            $categoryTitle = $product->getCategory()->getTitle();

            $categoryId = $product->getCategory()->getId();

            $productTitle = $product->getTitle();

            $productSizes = $this->em->getRepository(Product::class)->getProductSizesSortedByOrder($id);

            $breadcrumbs = [
                [
                    'title' => $gender,
                    'url' => $this->generateUrl($gender),

                ],
                [
                    'title' => $categoryTitle,
                    'url' => $this->generateUrl('category', ['id' => $categoryId, 'gender' => $gender]),
                ],
                [
                    'title' => $productTitle,
                    'url' => '',
                ],
            ];

            return $this->render('site/product.html.twig', [
                'title' => $productTitle . ' | ASOS',
                'product' => $product,
                'breadcrumbs' => $breadcrumbs,
                'brandDescription' => $brandDescription,
                'images' => $images,
                'sizes' => $productSizes,
                'gender' => $gender,
            ]);

        } catch (BadRequestException $e) {
            return new Response($e->getMessage(), 400);

        } catch (NotFoundException $e) {
            return new Response($e->getMessage(), 404);

        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}