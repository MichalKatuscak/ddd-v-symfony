#!/usr/bin/env php
<?php
/**
 * Konvertuje plain fenced code bloky (```php ... ```) v MD souborech
 * na :::code{language="..." filename="..."} bloky.
 *
 * Metadata (filename, language) extrahuje z původních Twig šablon.
 * Párovani: sequenční – 1. fenced blok v MD = 1. code_block include v Twig.
 */

$chapters = [
    'basic_concepts'        => '/zakladni-koncepty',
    'subdomains'            => '/subdomeny',
    'context_mapping'       => '/context-mapping',
    'horizontal_vs_vertical'=> '/vertikalni-slice',
    'aggregate_design'      => '/navrh-agregatu',
    'cqrs'                  => '/cqrs',
    'event_sourcing'        => '/event-sourcing',
    'sagas'                 => '/sagy-a-process-managery',
    'architectural_styles'  => '/architektonicke-styly',
    'authorization_in_ddd'  => '/autorizace-v-ddd',
    'microservices_and_ddd' => '/ddd-a-microservices',
    'ddd_pain_points'       => '/ddd-v-praxi-kde-to-boli',
    'event_storming'        => '/event-storming',
    'implementation_in_symfony' => '/implementace-v-symfony',
    'when_not_to_use_ddd'   => '/kdy-nepouzivat-ddd',
    'lesser_known_patterns' => '/mene-zname-vzory',
    'migration_from_crud'   => '/migrace-z-crud',
    'outbox_pattern'        => '/outbox-pattern',
    'practical_examples'    => '/prakticke-priklady',
    'case_study'            => '/pripadova-studie',
    'anti_patterns'         => '/anti-vzory',
    'team_topologies'       => '/team-topologies',
    'testing_ddd'           => '/testovani-ddd',
    'performance_aspects'   => '/vykonnostni-aspekty',
];

$twigDir = '/home/michal/Work/ddd-v-symfony/templates/ddd';
$mdDir   = '/home/michal/Work/ddd-v-symfony/.worktrees/markdown-chapters/content/chapters';

$dryRun = in_array('--dry-run', $argv);
$onlyChapter = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--chapter=')) {
        $onlyChapter = substr($arg, 10);
    }
}

foreach ($chapters as $chapter => $url) {
    if ($onlyChapter && $chapter !== $onlyChapter) {
        continue;
    }

    $twigFile = "{$twigDir}/{$chapter}.html.twig";
    $mdFile   = "{$mdDir}/{$chapter}.md";

    if (!file_exists($twigFile)) {
        echo "[SKIP] {$chapter}: Twig soubor nenalezen\n";
        continue;
    }
    if (!file_exists($mdFile)) {
        echo "[SKIP] {$chapter}: MD soubor nenalezen\n";
        continue;
    }

    $twigContent = file_get_contents($twigFile);
    $mdContent   = file_get_contents($mdFile);

    // Extrahovat metadata code bloků z Twig
    $twigBlocks = extractTwigCodeBlocks($twigContent);

    if (empty($twigBlocks)) {
        echo "[OK] {$chapter}: žádné code bloky v Twig\n";
        continue;
    }

    // Konvertovat plain fenced bloky v MD
    [$newMd, $converted, $skipped] = convertMdCodeBlocks($mdContent, $twigBlocks, $chapter);

    if ($converted === 0) {
        echo "[OK] {$chapter}: žádná konverze potřeba\n";
        continue;
    }

    echo "[CONV] {$chapter}: {$converted} bloků konvertováno, {$skipped} přeskočeno\n";

    if (!$dryRun) {
        file_put_contents($mdFile, $newMd);
    }
}

/**
 * Extrahuje pole [{filename, language, highlights}] z Twig šablony.
 */
function extractTwigCodeBlocks(string $twig): array
{
    $blocks = [];

    // Najít všechny include '_partials/code_block.html.twig' with { ... }
    // Včetně variant: {% include %} nebo {% set x %}{% include %}
    $pattern = '/include\s+\'_partials\/code_block\.html\.twig\'\s+with\s+\{([^}]+)\}/s';
    preg_match_all($pattern, $twig, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $attrs = $match[1];

        $filename   = extractAttr($attrs, 'filename');
        $language   = extractAttr($attrs, 'language');
        $highlights = extractHighlights($attrs);

        $blocks[] = [
            'filename'   => $filename ?? '',
            'language'   => $language ?? 'php',
            'highlights' => $highlights,
        ];
    }

    return $blocks;
}

function extractAttr(string $attrs, string $name): ?string
{
    // filename: 'hodnota' nebo filename: "hodnota"
    if (preg_match("/{$name}:\s*'([^']*)'/", $attrs, $m)) {
        return $m[1];
    }
    if (preg_match("/{$name}:\s*\"([^\"]*)\"/", $attrs, $m)) {
        return $m[1];
    }
    return null;
}

function extractHighlights(string $attrs): array
{
    // highlights: [1, 2, 3] nebo highlights: []
    if (preg_match('/highlights:\s*\[([^\]]*)\]/', $attrs, $m)) {
        $inner = trim($m[1]);
        if ($inner === '') {
            return [];
        }
        return array_map('intval', explode(',', $inner));
    }
    return [];
}

/**
 * Konvertuje plain fenced code bloky v MD na :::code bloky.
 * Vrací [newContent, convertedCount, skippedCount].
 */
function convertMdCodeBlocks(string $md, array $twigBlocks, string $chapter): array
{
    $lines       = explode("\n", $md);
    $output      = [];
    $twigIdx     = 0;
    $converted   = 0;
    $skipped     = 0;
    $i           = 0;
    $inSpecial   = 0; // hloubka ::: bloků (callout, diagram, faq)

    while ($i < count($lines)) {
        $line = $lines[$i];

        // Sledovat ::: bloky (ne code - ty mají jazyk)
        if (preg_match('/^:::(\w+)/', $line, $m) && $m[1] !== 'code') {
            $inSpecial++;
            $output[] = $line;
            $i++;
            continue;
        }
        if ($line === ':::' && $inSpecial > 0) {
            $inSpecial--;
            $output[] = $line;
            $i++;
            continue;
        }

        // Detekovat zahájení fenced code bloku
        if (preg_match('/^```(\w*)$/', $line, $m)) {
            $lang     = $m[1] ?: 'text';
            $codeLinesArr = [];
            $i++;

            // Sbírat obsah code bloku
            while ($i < count($lines) && $lines[$i] !== '```') {
                $codeLinesArr[] = $lines[$i];
                $i++;
            }
            $i++; // přeskočit uzavírací ```

            $codeBody = implode("\n", $codeLinesArr);

            // Jsou ještě Twig bloky k párování?
            if ($twigIdx < count($twigBlocks)) {
                $meta = $twigBlocks[$twigIdx];
                $twigIdx++;
                $converted++;

                $finalLang = $meta['language'] ?: $lang;
                $filename  = $meta['filename'];
                $hl        = $meta['highlights'];

                // Sestavit :::code blok
                $attrs = "language=\"{$finalLang}\"";
                if ($filename !== '') {
                    $attrs .= " filename=\"{$filename}\"";
                }
                if (!empty($hl)) {
                    $attrs .= ' highlights="' . implode(',', $hl) . '"';
                }

                $output[] = ":::code{{$attrs}}";
                $output[] = $codeBody;
                $output[] = ':::';
            } else {
                // Žádný Twig blok – ponechat jako fenced
                $skipped++;
                echo "  [WARN] {$chapter}: fenced blok #{$twigIdx} bez Twig metadat (lang={$lang})\n";
                $output[] = "```{$lang}";
                foreach ($codeLinesArr as $cl) {
                    $output[] = $cl;
                }
                $output[] = '```';
            }

            continue;
        }

        $output[] = $line;
        $i++;
    }

    if ($twigIdx < count($twigBlocks)) {
        echo "  [WARN] {$chapter}: " . (count($twigBlocks) - $twigIdx) . " Twig bloků bez shody v MD\n";
    }

    return [implode("\n", $output), $converted, $skipped];
}
