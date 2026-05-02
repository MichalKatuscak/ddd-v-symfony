<?php

declare(strict_types=1);

namespace App\Content;

final readonly class ChapterFrontmatter
{
    public function __construct(
        public string $route,
        public string $path,
        public string $title,
        public string $pageTitle,
        public string $metaDescription,
        public string $metaKeywords,
        public string $ogType,
        public ?string $published,
        public ?string $modified,
        public string $breadcrumbName,
        public string $schemaType,
        public string $schemaHeadline,
        public ?string $chapterNumber,
        public ?string $category,
        public ?string $deck,
        public ?int $readingTime,
        public ?int $difficulty,
        public ?string $githubExamples,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            route: $data['route'],
            path: $data['path'],
            title: $data['title'],
            pageTitle: $data['page_title'] ?? ($data['title'] . ' | DDD Symfony'),
            metaDescription: $data['meta_description'],
            metaKeywords: $data['meta_keywords'],
            ogType: $data['og_type'] ?? 'article',
            published: isset($data['published']) ? self::normalizeDate($data['published']) : null,
            modified: isset($data['modified']) ? self::normalizeDate($data['modified']) : null,
            breadcrumbName: $data['breadcrumb_name'],
            schemaType: $data['schema_type'] ?? 'TechArticle',
            schemaHeadline: $data['schema_headline'] ?? $data['title'],
            chapterNumber: $data['chapter_number'] ?? null,
            category: $data['category'] ?? null,
            deck: $data['deck'] ?? null,
            readingTime: isset($data['reading_time']) ? (int) $data['reading_time'] : null,
            difficulty: isset($data['difficulty']) ? (int) $data['difficulty'] : null,
            githubExamples: $data['github_examples'] ?? null,
        );
    }

    private static function normalizeDate(mixed $value): string
    {
        if (is_int($value)) {
            return date('Y-m-d', $value);
        }
        return (string) $value;
    }
}
