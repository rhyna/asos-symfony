<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\TopMenuBuilder;
use Twig\Extension\AbstractExtension;

class HelpersExtension extends AbstractExtension
{
    private TopMenuBuilder $topMenuBuilder;

    public function __construct(TopMenuBuilder $topMenuBuilder)
    {
        $this->topMenuBuilder = $topMenuBuilder;
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('buildOptGroups', [$this, 'buildOptGroupsFunc']),
            new \Twig\TwigFunction('stringToInt', [$this, 'stringToInteger']),
            new \Twig\TwigFunction('getMenuConfig', [$this, 'getMenuConfig']),
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

    public function getMenuConfig(): array
    {
        return $this->topMenuBuilder->getMenuConfig();
    }
}