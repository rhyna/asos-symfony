<?php

declare(strict_types=1);

namespace App\Service\Pagination;

class PaginationService
{
    /**
     * @throws \SystemErrorException
     */
    public function calculate(int $page, int $itemsPerPage, int $totalItems): PaginationDto
    {
        $dto = new PaginationDto();

        $dto->limit = $itemsPerPage;

        $page = filter_var($page, FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 1,
                'min_range' => 1,
            ]
        ]);

        $totalPages = ceil($totalItems / $itemsPerPage);

        if ($totalPages === false) {
            throw new \SystemErrorException();
        }

        $dto->totalPages = (int)$totalPages;

        if ($page > 1) {
            $dto->previous = $page - 1;
        }

        if ($page < $dto->totalPages) {
            $dto->next = $page + 1;
        }

        $dto->offset = $dto->limit * ($page - 1);

        $this->setTokenAndBaseUrl($dto);

        return $dto;
    }

    public function setTokenAndBaseUrl(PaginationDto $dto)
    {
        $token = '&';

        $data = parse_url($_SERVER['REQUEST_URI']);

        $path = $data['path'];

        $query = '';

        if (isset($data['query'])) {
            $query = $data['query'];
        }

        parse_str($query, $queryArray);

        $onlyPageQuery = count($queryArray) === 1 && array_key_exists('page', $queryArray);

        if (!$query || $onlyPageQuery) {
            $token = '?';
        }

        unset($queryArray['page']);

        $newQuery = http_build_query($queryArray);

        $baseUrl = $path;

        if ($newQuery) {
            $baseUrl = $path . '?' . $newQuery;
        }

        $dto->token = $token;

        $dto->baseUrl = $baseUrl;
    }
}