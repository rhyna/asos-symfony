<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PageDeterminerService
{
    private Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function determinePage(): int
    {
        $page = $this->request->get('page');

        if (!$page || (string)(int)$page !== $page) {
            $page = 1;
        }

        return (int)$page;
    }
}