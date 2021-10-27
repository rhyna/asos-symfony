<?php

declare(strict_types=1);

namespace App\Controller\Site;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request): Response
    {
        return $this->redirectToRoute('women');
    }

    /**
     * @Route("/women", name="women")
     */
    public function homeWomen(Request $request): Response
    {
        return $this->render('site/index.html.twig', [
        ]);
    }
}