<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Entity\Product;
use App\Entity\SearchWord;
use Doctrine\ORM\EntityManagerInterface;
use markfullmer\porter2\Porter2;

class SearchService
{
    public EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function normalizeString(string $string): array
    {
        $string = str_replace('<', ' <', $string); // adding space before each tag

        $string = strip_tags($string); // removing php and html tags

        $string = strtolower($string); // converting to lowercase

        $string = str_replace("&nbsp;", '', $string);

        $string = str_replace("‘", '', $string);

        $string = str_replace("’", '', $string);

        $string = html_entity_decode($string); // decoding html entities (&nbsp; etc.)

        $array = str_word_count($string, 1, "0123456789"); // splitting string into array removing special characters

        $result = [];

        foreach ($array as $item) {
            $result[] = Porter2::stem($item); // getting basic form of each word
        }

        return $result;
    }

    public function normalizeSearchWords(array $searchWordData): array
    {
        $normalizedSearchWordData = [];

        foreach ($searchWordData as $item) {
            $normalizedSearchWordData[] = $this->normalizeString((string)$item);
        }

        $normalizedSearchWords = [];

        foreach ($normalizedSearchWordData as $arr) {
            foreach ($arr as $item) {
                $normalizedSearchWords[] = $item;
            }

        }

        return array_unique($normalizedSearchWords);
    }

    public function findUnusedSearchWordsToDelete(Product $product): array
    {
        $currentSearchWords = $product->getSearchWords();

        $currentSearchWordIds = [];

        foreach ($currentSearchWords as $searchWord) {
            $currentSearchWordIds[] = $searchWord->getId();
        }

        $searchWordRepository = $this->em->getRepository(SearchWord::class);

        $productId = $product->getId();

        $usedSearchWords = $searchWordRepository->checkUsedSearchWords(implode(',', $currentSearchWordIds), $productId);

        $usedSearchWordIds = [];

        foreach ($usedSearchWords as $searchWord) {
            $usedSearchWordIds[] = (int)$searchWord['id'];
        }

        return array_diff($currentSearchWordIds, $usedSearchWordIds);
    }
}