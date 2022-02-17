<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Exception\AsosException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ErrorController extends AbstractController
{
    public function handleException(\Throwable $exception, LoggerInterface $logger): Response
    {
        if ($exception instanceof NotFoundHttpException) {
             return $this->render('_errors/error-page.html.twig', [
                 'title' => 'Error 404',
                 'header' => 'Error 404 - Page not found',
                 'message' => 'Something went wrong. The page you requested has not been found.',
             ], new Response('', 404));
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return $this->render('_errors/error-page.html.twig', [
                 'title' => 'Error 403',
                 'header' => 'Error 403 - Access denied',
                 'message' => 'Something went wrong. You don\'t have a right to view this page.',
             ], new Response('', 403));
        }

        if ($exception instanceof UnauthorizedHttpException || ($exception instanceof HttpException && $exception->getStatusCode() === 401)) {
            return $this->render('_errors/error-page.html.twig', [
                 'title' => 'Error 401',
                 'header' => 'Error 401 - Unauthorized',
                 'message' => 'Something went wrong. Access denied to unauthorized users.',
             ], new Response('', 401));
        }

        if ($exception instanceof AsosException) {
            return $this->render('_errors/error-page.html.twig', [
                 'title' => 'Error ' . $exception->getCode(),
                 'header' => 'Error ' . $exception->getCode(),
                 'message' => $exception->getMessage(),
             ], new Response('', $exception->getCode()));
        }

        return new Response($exception->getMessage(), 500);
    }
}