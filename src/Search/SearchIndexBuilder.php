<?php

declare(strict_types=1);

namespace App\Search;

use App\Catalog\Chapters;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Sestaví statický vyhledávací index z obsahu knihy. Bez databáze a bez
 * externího nástroje: čte frontmatter a nadpisy přímo z Markdownu kapitol
 * a termíny z glosáře. Výsledek je pole položek, které controller vrací
 * jako JSON a klient filtruje na straně prohlížeče.
 *
 * Položka: {t: titulek, x: kontext, u: URL, g: skupina}.
 */
final class SearchIndexBuilder
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    /**
     * @return list<array{t:string, x:string, u:string, g:string}>
     */
    public function build(): array
    {
        $entries = [];

        // Route → kategorie (tag) z katalogu, pro označení skupiny ve výsledcích.
        $tagByRoute = [];
        foreach ([...Chapters::all(), ...Chapters::extras()] as $c) {
            $tagByRoute[$c['route']] = $c['tag'];
        }

        foreach (glob($this->projectDir . '/content/chapters/*.md') ?: [] as $file) {
            $raw = (string) file_get_contents($file);
            [$yamlStr, $markdown] = $this->splitFrontmatter($raw);
            if ($yamlStr === '') {
                continue;
            }
            $fm = Yaml::parse($yamlStr);
            $route = $fm['route'] ?? null;
            $path  = $fm['path'] ?? null;
            $title = trim((string) ($fm['title'] ?? ''));
            if (!$route || !$path || $title === '') {
                continue;
            }

            $group = $tagByRoute[$route] ?? 'Kapitola';

            // Kapitola jako celek.
            $deck = trim(strip_tags((string) ($fm['deck'] ?? $fm['meta_description'] ?? '')));
            $entries[] = [
                't' => $title,
                'x' => $deck,
                'u' => $path,
                'g' => $group,
            ];

            // Sekce kapitoly: `## NN.MM Titulek {#kotva}`.
            if (preg_match_all('/^##\s+(.+?)\s*\{#([a-z0-9-]+)\}\s*$/mu', $markdown, $m, PREG_SET_ORDER)) {
                foreach ($m as $h) {
                    $headingText = preg_replace('/^[A-Za-z\d]+\.\d+\s+/', '', trim($h[1]));
                    $entries[] = [
                        't' => $headingText,
                        'x' => $title,
                        'u' => $path . '#' . $h[2],
                        'g' => $group,
                    ];
                }
            }
        }

        // Glosářové termíny ze statického markupu glossary.html.twig.
        foreach ($this->glossaryTerms() as $term) {
            $entries[] = $term;
        }

        return $entries;
    }

    /**
     * @return list<array{t:string, x:string, u:string, g:string}>
     */
    private function glossaryTerms(): array
    {
        $file = $this->projectDir . '/templates/ddd/glossary.html.twig';
        if (!is_file($file)) {
            return [];
        }
        $html = (string) file_get_contents($file);

        $terms = [];
        // Termíny mají buď `<dt><strong>Pojem</strong> <span class="term-en">(EN)</span></dt>`
        // nebo kompaktní `<dt><dfn>Pojem</dfn></dt>` (sekce „false friends", id ff-*).
        $pattern = '/class="glossary-entry"\s+id="([a-z0-9-]+)">\s*<dt>\s*<(?:strong|dfn)>([^<]+)<\/(?:strong|dfn)>'
            . '(?:\s*<span class="term-en">\(([^)]+)\))?/u';
        if (preg_match_all($pattern, $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $g) {
                $cs = trim($g[2]);
                $en = isset($g[3]) ? trim($g[3]) : '';
                $terms[] = [
                    't' => $en !== '' ? "{$cs} ({$en})" : $cs,
                    'x' => 'Glosář',
                    'u' => '/glosar#' . $g[1],
                    'g' => 'Glosář',
                ];
            }
        }

        return $terms;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitFrontmatter(string $content): array
    {
        if (!str_starts_with($content, '---')) {
            return ['', $content];
        }
        $end = strpos($content, "\n---", 3);
        if ($end === false) {
            return ['', $content];
        }
        return [
            substr($content, 4, $end - 4),
            ltrim(substr($content, $end + 4)),
        ];
    }
}
