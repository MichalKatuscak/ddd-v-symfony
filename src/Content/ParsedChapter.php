<?php

declare(strict_types=1);

namespace App\Content;

final readonly class ParsedChapter
{
    public function __construct(
        public ChapterFrontmatter $frontmatter,
        public string $html,
    ) {}
}
