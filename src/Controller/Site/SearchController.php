<?php

declare(strict_types=1);

namespace App\Controller\Site;

use App\Entity\Product;
use App\Entity\SearchWord;
use App\Service\PageDeterminerService;
use App\Service\Pagination\PaginationDto;
use App\Service\Pagination\PaginationService;
use App\Service\Search\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    private EntityManagerInterface $em;
    private SearchService $searchService;
    private PaginationService $paginationService;
    private PageDeterminerService $pageDeterminerService;

    public function __construct(EntityManagerInterface $em,
                                SearchService          $searchService,
                                PaginationService      $paginationService,
                                PageDeterminerService  $pageDeterminerService)
    {
        $this->em = $em;
        $this->searchService = $searchService;
        $this->paginationService = $paginationService;
        $this->pageDeterminerService = $pageDeterminerService;
    }

    /**
     * @Route("/search", name="search")
     * @throws \SystemErrorException
     */
    public function search(Request $request): Response
    {
        $query = $request->get('query');

        $breadcrumbs = [
            [
                'title' => 'Home',
                'url' => '/'
            ],
            [
                'title' => 'Search results',
                'url' => ''
            ],

        ];

        if (!$query) {
            return $this->render("site/search-no-result.html.twig", [
                'title' => "You searched: '$query' | ASOS",
                'gender' => 'women',
                'breadcrumbs' => $breadcrumbs,
                'query' => $query,
                'error' => 'The search query is empty',
            ]);
        }

        $normalizedQueryArray = $this->searchService->normalizeString($query);

        $searchRepository = $this->em->getRepository(SearchWord::class);

        $searchWordIds = $searchRepository->getSearchWordIds($normalizedQueryArray);

        if (!$searchWordIds) {
            return $this->render("site/search-no-result.html.twig", [
                'title' => "You searched: '$query' | ASOS",
                'gender' => 'women',
                'breadcrumbs' => $breadcrumbs,
                'query' => $query,
                'error' => 'No products matching the search query',
            ]);
        }

        $whereClauses = [];

        $joinClauses = [];

        foreach ($searchWordIds as $i => $id) {
            $arr = [
                'clause' => "p.searchWords",
                'alias' => "sw$i"
            ];

            $joinClauses[] = $arr;

            $whereClauses[] = "sw$i.id = $id";
        }

        $page = $this->pageDeterminerService->determinePage();

        $productRepository = $this->em->getRepository(Product::class);

        $totalProducts = $productRepository->countProductsInList($whereClauses, $joinClauses);

        $pagination = $this->paginationService->calculate($page, 12, $totalProducts);

        $products = $productRepository->getProductList($whereClauses, $joinClauses, [], $pagination->limit, $pagination->offset);

        return $this->render("site/search.html.twig", [
            'title' => "You searched: '$query' | ASOS",
            'gender' => 'women',
            'breadcrumbs' => $breadcrumbs,
            'products' => $products,
            'page' => $page,
            'pagination' => $pagination,
            'query' => $query,
        ]);
    }
}