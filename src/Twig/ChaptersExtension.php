<?php

declare(strict_types=1);

namespace App\Twig;

use App\Catalog\Chapters;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ChaptersExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ddd_chapters',          static fn(): array => Chapters::all()),
            new TwigFunction('ddd_extras',            static fn(): array => Chapters::extras()),
            new TwigFunction('ddd_chapters_by_group', static fn(string $group): array => Chapters::byGroup($group)),
        ];
    }
}
