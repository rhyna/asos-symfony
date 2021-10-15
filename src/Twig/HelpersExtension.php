<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;

class HelpersExtension extends AbstractExtension
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('buildOptGroups', [$this, 'buildOptGroupsFunc']),
            new \Twig\TwigFunction('stringToInt', [$this, 'stringToInteger']),
            new \Twig\TwigFunction('getCategoryLevels', [$this, 'getCategoryLevels'])
        ];
    }

    public function buildOptGroupsFunc($settings): array
    {
        $optGroups = [];

        foreach ($settings['optGroups'] as $optGroup) {
            $optGroups[$optGroup['parentCategoryTitle']] = [];
        }

        foreach ($settings['optGroups'] as $optGroup) {
            $optGroups[$optGroup['parentCategoryTitle']][] = [
                'id' => $optGroup['id'],
                'title' => $optGroup['title']
            ];
        }

        return $optGroups;
    }

    public function stringToInteger(string $string): int
    {
        return (int)$string;
    }

    public function getCategoryLevels(): array
    {
        $repository = $this->em->getRepository(Category::class);

        return $repository->getCategoryLevels();
    }
}