<?php

declare(strict_types=1);

namespace App\Service\Pagination;

use App\Exception\SystemErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationService
{
    private Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @throws SystemErrorException
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
            throw new SystemErrorException();
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

        $query = $this->request->query->all();

        $baseUrl = $query['_url'];

        unset($query['_url']);

        $onlyPageQuery = count($query) === 1 && array_key_exists('page', $query);

        if (!$query || $onlyPageQuery) {
            $token = '?';
        }

        unset($query['page']);

        $newQuery = http_build_query($query);

        if ($newQuery) {
            $baseUrl .= '?' . $newQuery;
        }

        $dto->token = $token;

        $dto->baseUrl = $baseUrl;
    }
}