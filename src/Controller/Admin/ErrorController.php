<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Exception\AsosException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ErrorController extends AbstractController
{
    public function handleException(\Throwable $exception, LoggerInterface $logger): Response
    {
        if ($exception instanceof NotFoundHttpException) {
            // $this->render('path-to-view', [], new Response('', 404));

            return new Response('404', 404); // сделать рендер вьюхи в папке templates/_errors/
        }
        if ($exception instanceof AccessDeniedHttpException || $exception instanceof UnauthorizedHttpException) {
            return new Response('403/401'); // сделать рендер вьюхи в папке templates/_errors/
        }
        if ($exception instanceof AsosException) {
            return new Response($exception->getMessage()); // сделать рендер вьюхи в папке templates/_errors/
        }

        return new Response($exception->getMessage(), 500);
    }
}