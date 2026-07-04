<?php

declare(strict_types=1);

namespace App\Sitemap;

use App\Catalog\Chapters;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Sestavuje seznam URL pro sitemap.xml z katalogu kapitol (Chapters)
 * a frontmatteru .md souborů (path, modified). Statické stránky
 * (huby, glosář, …) jsou definované zde – přidání kapitoly do
 * content/chapters/ se v sitemapě projeví bez dalšího zásahu.
 */
final class SitemapBuilder
{
    /** Huby: group v katalogu → URL path. */
    private const HUBS = [
        'basics'       => '/zaklady',
        'tactics'      => '/takticke-vzory',
        'architecture' => '/architektura',
        'patterns'     => '/vzory',
        'practice'     => '/praxe',
        'synthesis'    => '/synteza',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    /**
     * @return list<array{loc:string, lastmod:?string, changefreq:string, priority:string}>
     */
    public function build(): array
    {
        $chapters = $this->chapterFrontmatters();

        $latestOverall = null;
        $latestByGroup = [];
        $groupByRoute  = [];
        foreach (Chapters::all() as $c) {
            $groupByRoute[$c['route']] = $c['group'];
        }
        foreach ($chapters as $route => $fm) {
            $date = $fm['lastmod'];
            if ($date === null) {
                continue;
            }
            if ($latestOverall === null || $date > $latestOverall) {
                $latestOverall = $date;
            }
            $group = $groupByRoute[$route] ?? null;
            if ($group !== null && ($latestByGroup[$group] ?? '') < $date) {
                $latestByGroup[$group] = $date;
            }
        }

        $urls   = [];
        $urls[] = $this->url('/', $latestOverall, 'monthly', '1.0');

        foreach (self::HUBS as $group => $path) {
            $urls[] = $this->url($path, $latestByGroup[$group] ?? $latestOverall, 'monthly', '0.9');
        }
        $urls[] = $this->url('/reference', $latestOverall, 'monthly', '0.9');

        // Kapitoly v pořadí katalogu; soubory mimo katalog (extras) na konec.
        $emitted = [];
        foreach (Chapters::all() as $c) {
            $fm = $chapters[$c['route']] ?? null;
            if ($fm === null) {
                continue;
            }
            $priority = $c['group'] === 'preface' ? '0.6' : '0.8';
            $urls[]   = $this->url($fm['path'], $fm['lastmod'], 'monthly', $priority);
            $emitted[$c['route']] = true;
        }
        foreach ($chapters as $route => $fm) {
            if (!isset($emitted[$route])) {
                $urls[] = $this->url($fm['path'], $fm['lastmod'], 'monthly', '0.6');
            }
        }

        $urls[] = $this->url('/glosar', $latestOverall, 'monthly', '0.6');
        $urls[] = $this->url('/cheat-sheet', $latestOverall, 'monthly', '0.6');
        $urls[] = $this->url('/zdroje', $latestOverall, 'monthly', '0.6');
        $urls[] = $this->url('/o-autorovi', null, 'monthly', '0.7');
        $urls[] = $this->url('/security-policy', null, 'yearly', '0.3');

        return $urls;
    }

    /**
     * @return array{loc:string, lastmod:?string, changefreq:string, priority:string}
     */
    private function url(string $loc, ?string $lastmod, string $changefreq, string $priority): array
    {
        return ['loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => $changefreq, 'priority' => $priority];
    }

    /**
     * Frontmatter všech kapitol: route → path + lastmod (modified, fallback published).
     *
     * @return array<string, array{path:string, lastmod:?string}>
     */
    private function chapterFrontmatters(): array
    {
        $result = [];
        foreach (glob($this->projectDir . '/content/chapters/*.md') ?: [] as $file) {
            $raw = file_get_contents($file);
            if ($raw === false || !str_starts_with($raw, '---')) {
                continue;
            }
            $raw = str_replace(["\r\n", "\r"], "\n", $raw);
            $end = strpos($raw, "\n---", 3);
            if ($end === false) {
                continue;
            }
            $data = Yaml::parse(substr($raw, 4, $end - 4));
            if (!isset($data['route'], $data['path'])) {
                continue;
            }
            $result[(string) $data['route']] = [
                'path'    => (string) $data['path'],
                'lastmod' => $this->normalizeDate($data['modified'] ?? $data['published'] ?? null),
            ];
        }

        return $result;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value)) {
            return date('Y-m-d', $value);
        }

        return (string) $value;
    }
}
