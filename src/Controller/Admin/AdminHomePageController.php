<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminHomePageController extends AbstractController
{
    /**
     * @Route("/admin/home", name="admin. home.page")
     */
    public function index(Request $request): Response
    {
        return $this->render('admin/index.html.twig', [
        ]);
    }
}