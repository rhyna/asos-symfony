<?php

declare(strict_types=1);

namespace App\Controller\Admin;

class PaginatorController
{
    public function __construct(int $page, int $itemsPerPage, int $totalItems)
    {
        $this->limit = $itemsPerPage;

        $page = filter_var($page, FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 1,
                'min_range' => 1,
            ]
        ]);

        $this->totalPages = ceil($totalItems / $itemsPerPage);

        if ($page > 1) {
            $this->previous = $page - 1;
        }

        if ($page < $this->totalPages) {
            $this->next = $page + 1;
        }

        $this->offset = $this->limit * ($page - 1);
    }
}