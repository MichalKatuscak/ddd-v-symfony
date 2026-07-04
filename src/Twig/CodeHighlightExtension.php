<?php

declare(strict_types=1);

namespace App\Twig;

use App\Content\CodeHighlighter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class CodeHighlightExtension extends AbstractExtension
{
    public function __construct(private readonly CodeHighlighter $highlighter) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'code_lines',
                fn(string $code, string $language, array $highlights = []): string
                    => $this->highlighter->highlight($code, $language, $highlights),
                ['is_safe' => ['html']],
            ),
        ];
    }
}
