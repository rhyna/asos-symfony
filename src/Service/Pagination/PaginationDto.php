<?php

declare(strict_types=1);

namespace App\Service\Pagination;

class PaginationDto
{
    public int $limit = 0;
    public int $offset = 0;
    public int $previous = 0;
    public int $next = 0;
    public float $totalPages = 0;
    public string $token = '';
    public string $baseUrl = '';
}