<?php
declare(strict_types=1);

/*
 * Audit cross-reference odkazů a kotev v content/chapters/*.md.
 *
 * - Načte path: a všechny {#kotva} z každé kapitoly
 * - Najde [text](/path#anchor) odkazy, ověří, že path existuje a kotva v něm taky
 * - Hlásí broken external anchors a duplicitní kotvy uvnitř jedné kapitoly
 *
 * Externí URL (http://, https://) i čistě fragmentové (#xy bez /path) ignoruje.
 */

$root = __DIR__ . '/..';
$contentDir = $root . '/content/chapters';
$twigDir = $root . '/templates/ddd';

if (!is_dir($contentDir)) {
    fwrite(STDERR, "content/chapters not found\n");
    exit(2);
}

/** @var array<string, array{path:string, route:string, anchors:array<string,int>, file:string}> $chapters */
$chapters = [];
$pathIndex = [];

foreach (glob($contentDir . '/*.md') as $file) {
    $body = file_get_contents($file);

    // Frontmatter
    if (!preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $body, $fm)) {
        fwrite(STDERR, "no frontmatter: $file\n");
        continue;
    }
    preg_match('/^path:\s*(.+)$/m', $fm[1], $p);
    preg_match('/^route:\s*(.+)$/m', $fm[1], $r);

    $path = trim($p[1] ?? '', "\"' \t");
    $route = trim($r[1] ?? '', "\"' \t");

    // Anchors
    $anchors = [];
    if (preg_match_all('/\{#([a-z0-9-]+)\}/', $body, $m)) {
        foreach ($m[1] as $a) {
            $anchors[$a] = ($anchors[$a] ?? 0) + 1;
        }
    }
    // Doplň automatické kotvy z headingů (## 11.05 Implementace... {#repositories})
    // jsou již v {#…} formátu. Custom rendery mohou přidávat z h2 textu, ale to nebudeme
    // simulovat — preferujeme explicitní {#kotva}.

    $chapters[basename($file)] = [
        'path' => $path,
        'route' => $route,
        'anchors' => $anchors,
        'file' => $file,
    ];
    if ($path !== '') {
        $pathIndex[$path] = basename($file);
    }
}

// Doplnit i statické twig stránky (cheat_sheet, glossary) jejich kotvami
foreach (glob($twigDir . '/*.html.twig') as $file) {
    $name = basename($file, '.html.twig');
    $body = file_get_contents($file);
    $anchors = [];
    if (preg_match_all('/\bid="([a-z0-9_-]+)"/', $body, $m)) {
        foreach ($m[1] as $a) {
            $anchors[$a] = 1;
        }
    }
    // Mapování souboru na URL: glossary.html.twig → /glosar atd. – pro audit
    // přidáme známé statické cesty z DddController
    static $twigPaths = null;
    if ($twigPaths === null) {
        $ctrl = file_get_contents(__DIR__ . '/../src/Controller/DddController.php');
        preg_match_all("/Route\\('([^']+)',\\s*name:\\s*'([^']+)'/", $ctrl, $m);
        $twigPaths = array_combine($m[2], $m[1]);
    }
    if (isset($twigPaths[$name]) && !isset($pathIndex[$twigPaths[$name]])) {
        $chapters['twig:' . $name] = [
            'path' => $twigPaths[$name],
            'route' => $name,
            'anchors' => $anchors,
            'file' => $file,
        ];
        $pathIndex[$twigPaths[$name]] = 'twig:' . $name;
    }
}

// Audit odkazů
$brokenLinks = [];
$duplicateAnchors = [];
$brokenWithinSelf = [];

foreach ($chapters as $key => $ch) {
    $body = file_get_contents($ch['file']);

    // duplicity
    foreach ($ch['anchors'] as $a => $count) {
        if ($count > 1) {
            $duplicateAnchors[] = sprintf('%s: kotva #%s definována %d×', $ch['path'], $a, $count);
        }
    }

    // [text](/path[#anchor])
    if (preg_match_all('/\[[^\]]*\]\((\/[a-z0-9\/-]+)(?:#([a-z0-9-]+))?\)/i', $body, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $linkPath = $match[1];
            $linkAnchor = $match[2] ?? null;

            if (!isset($pathIndex[$linkPath])) {
                $brokenLinks[] = sprintf('%s → %s%s   (cesta neexistuje)',
                    $ch['path'], $linkPath, $linkAnchor ? '#' . $linkAnchor : '');
                continue;
            }
            if ($linkAnchor !== null) {
                $targetKey = $pathIndex[$linkPath];
                if (!isset($chapters[$targetKey]['anchors'][$linkAnchor])) {
                    $brokenLinks[] = sprintf('%s → %s#%s   (kotva neexistuje v cílové stránce)',
                        $ch['path'], $linkPath, $linkAnchor);
                }
            }
        }
    }

    // [text](#self-anchor)
    if (preg_match_all('/\[[^\]]*\]\(#([a-z0-9-]+)\)/i', $body, $m)) {
        foreach ($m[1] as $linkAnchor) {
            if (!isset($ch['anchors'][$linkAnchor])) {
                $brokenWithinSelf[] = sprintf('%s → #%s   (kotva neexistuje uvnitř téže kapitoly)',
                    $ch['path'], $linkAnchor);
            }
        }
    }

    // <a href="…"> v callout/FAQ HTML blocích
    if (preg_match_all('/<a\s+href=["\'](\/[a-z0-9\/-]+)(?:#([a-z0-9-]+))?["\']/i', $body, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $linkPath = $match[1];
            $linkAnchor = $match[2] ?? null;
            if (!isset($pathIndex[$linkPath])) {
                $brokenLinks[] = sprintf('%s → %s%s   (HTML href, cesta neexistuje)',
                    $ch['path'], $linkPath, $linkAnchor ? '#' . $linkAnchor : '');
                continue;
            }
            if ($linkAnchor !== null) {
                $targetKey = $pathIndex[$linkPath];
                if (!isset($chapters[$targetKey]['anchors'][$linkAnchor])) {
                    $brokenLinks[] = sprintf('%s → %s#%s   (HTML href, kotva neexistuje)',
                        $ch['path'], $linkPath, $linkAnchor);
                }
            }
        }
    }
    if (preg_match_all('/<a\s+href=["\']#([a-z0-9-]+)["\']/i', $body, $m)) {
        foreach ($m[1] as $linkAnchor) {
            if (!isset($ch['anchors'][$linkAnchor])) {
                $brokenWithinSelf[] = sprintf('%s → #%s   (HTML href, kotva neexistuje uvnitř kapitoly)',
                    $ch['path'], $linkAnchor);
            }
        }
    }
}

// Output
$totalIssues = count($brokenLinks) + count($duplicateAnchors) + count($brokenWithinSelf);

echo "Audit kotev a cross-reference odkazů\n";
echo str_repeat('=', 60) . "\n";
echo sprintf("Kapitol: %d   Definovaných kotev: %d\n",
    count($chapters),
    array_sum(array_map(fn($c) => count($c['anchors']), $chapters))
);
echo "\n";

if ($brokenLinks) {
    echo "BROKEN cross-chapter odkazy (" . count($brokenLinks) . "):\n";
    foreach ($brokenLinks as $b) echo "  - $b\n";
    echo "\n";
}

if ($brokenWithinSelf) {
    echo "BROKEN in-chapter odkazy (" . count($brokenWithinSelf) . "):\n";
    foreach ($brokenWithinSelf as $b) echo "  - $b\n";
    echo "\n";
}

if ($duplicateAnchors) {
    echo "DUPLICITNÍ kotvy (" . count($duplicateAnchors) . "):\n";
    foreach ($duplicateAnchors as $b) echo "  - $b\n";
    echo "\n";
}

if ($totalIssues === 0) {
    echo "OK – všechny odkazy a kotvy validní.\n";
    exit(0);
}

echo "Celkem problémů: $totalIssues\n";
exit(1);
