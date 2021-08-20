<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;

class HelpersExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('buildOptGroups', [$this, 'buildOptGroupsFunc']),
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
}