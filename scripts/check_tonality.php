<?php
declare(strict_types=1);

/*
 * Audit tonality podle CLAUDE.md zakázaného slovníku.
 *
 * Hledá:
 *  - Marketing/hype přídavná jména (mocný, výkonný, elegantní, robustní, …)
 *  - Filler fráze („je důležité si uvědomit", „hraje klíčovou roli", „v rámci", …)
 *  - Typografii: em dash (—), anglické uvozovky ""
 *
 * Hlásí lokace, ne automaticky neopravuje – některé výskyty jsou legitimní
 * (citace, anti-vzor, code snippet).
 */

$root = __DIR__ . '/..';
$targets = array_merge(
    glob($root . '/content/chapters/*.md'),
    glob($root . '/templates/ddd/*.html.twig'),
);

// Slovník: regex => kategorie
$patterns = [
    // ── MARKETING / HYPE ─────────────────────────────────────
    '\bmocn(?:ý|á|é|ého|ou|ým|ými|ější|ějších)\b' => 'hype',
    '\bvýkonn(?:ý|á|é|ého|ou|ým|ými|ější|ějších)\b' => 'hype',
    '\belegantn(?:í|ího|ímu|ím|ími|ější|ějších)\b' => 'hype',
    '\brobustn(?:í|ího|ímu|ím|ími|ější|ějších)\b' => 'hype',
    '\brevolu[čc]n(?:í|ího|ímu|ím|ími)\b' => 'hype',
    '\bprůlomov(?:ý|á|é|ého|ou|ým|ými)\b' => 'hype',
    '\bmodern(?:í|ího|ímu|ím|ími|ější|ějších)\b' => 'hype',
    '\bperfektn(?:í|ího|ímu|ím|ími)\b' => 'hype',
    '\bideáln(?:í|ího|ímu|ím|ími)\b' => 'hype',
    '\boptimáln(?:í|ího|ímu|ím|ími)\b' => 'hype',
    '\bbezproblémov(?:ý|á|é|ého|ou|ým|ými)\b' => 'hype',
    '\bhladk(?:ý|á|é|ého|ou|ým|ými|é|ých)\b' => 'hype',
    '\bjednoduše\b' => 'hype',
    '\bsnadno\b' => 'hype',
    'cutting[- ]edge' => 'hype',
    'state[- ]of[- ]the[- ]art' => 'hype',
    'game[- ]changer' => 'hype',
    'seamless\w*' => 'hype',
    '\bbest practice\b' => 'hype',
    'na další úroveň' => 'hype',
    'plně využít potenciál' => 'hype',
    'nov(?:á|é) éra' => 'hype',

    // ── FILLER ────────────────────────────────────────────────
    'je důležité si uvědomit' => 'filler',
    'je důležité (?:po)?znamenat' => 'filler',
    'hraje (?:klíčovou|důležitou|významnou|zásadní) roli' => 'filler',
    'stojí za zmínku' => 'filler',
    'je třeba (?:po)?znamenat' => 'filler',
    'jak jsme již zmínili' => 'filler',
    '\bv rámci\b' => 'filler',
    'klíčov(?:ý|á|é|ého|ou|ým|ými|ých)\b(?!\.| pojmem| invariant| myšlen| poznatk| sdělen| pravidl| termín| princip| krit)' => 'filler-klicovy',
    's ohledem na' => 'filler',
    'co se týče' => 'filler',
    'v neposlední řadě' => 'filler',
    '\bsamozřejmě\b' => 'filler',
    '\bpochopitelně\b' => 'filler',
    '\blogicky\b' => 'filler',
    '\bzcela\b' => 'filler',
    '\bnaprosto\b' => 'filler',
    '\babsolutně\b' => 'filler',
    'jinými slovy' => 'filler',
    'celkově vzato' => 'filler',
    'není pochyb o tom' => 'filler',
    '\bjak víme\b' => 'filler',

    // ── TYPOGRAFIE ────────────────────────────────────────────
    '—' => 'typo-emdash',
];

$ignoreInside = ['```', ':::code'];

$results = [];

foreach ($targets as $file) {
    $body = file_get_contents($file);
    $lines = explode("\n", $body);

    // Vynechat code bloky (```…``` a :::code{…}…::::)
    $inCodeBlock = false;
    $inFenceBlock = false;

    foreach ($lines as $i => $line) {
        $lineNum = $i + 1;

        // Detekce začátku/konce code bloků
        if (preg_match('/^```/', trim($line))) {
            $inFenceBlock = !$inFenceBlock;
            continue;
        }
        if (preg_match('/^:::code\b/', trim($line))) {
            $inCodeBlock = true;
            continue;
        }
        if ($inCodeBlock && preg_match('/^:::\s*$/', trim($line))) {
            $inCodeBlock = false;
            continue;
        }
        if ($inCodeBlock || $inFenceBlock) {
            continue;
        }

        // Vynechat řádky obsahující jen frontmatter klíče (route:, path:, atd.)
        if (preg_match('/^\s*(route|path|title|page_title|meta_description|meta_keywords|og_type|published|modified|breadcrumb_name|schema_type|schema_headline|chapter_number|category|deck|reading_time|difficulty|github_examples):/i', $line)) {
            // Pokračujeme – kontrolujeme i tady, ale zaznamenáme, že je to frontmatter
            // Frontmatter vlastně obsahuje uživatelský text (deck, page_title), takže zachovat
        }

        foreach ($patterns as $pattern => $category) {
            // U em dashe: zkontrolovat skutečný znak, ne regex
            if ($pattern === '—') {
                if (str_contains($line, '—')) {
                    $results[] = [
                        'file' => str_replace($root . '/', '', $file),
                        'line' => $lineNum,
                        'category' => $category,
                        'match' => '—',
                        'context' => trim(mb_substr($line, max(0, mb_strpos($line, '—') - 30), 60)),
                    ];
                }
                continue;
            }

            if (preg_match_all('/' . $pattern . '/iu', $line, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as $match) {
                    [$matchText, $offset] = $match;
                    $context = mb_substr($line, max(0, $offset - 20), 70);
                    $results[] = [
                        'file' => str_replace($root . '/', '', $file),
                        'line' => $lineNum,
                        'category' => $category,
                        'match' => $matchText,
                        'context' => trim($context),
                    ];
                }
            }
        }
    }
}

// Output: agregace po kategoriích a souborech
$byCategory = [];
$byFile = [];
foreach ($results as $r) {
    $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + 1;
    $byFile[$r['file']][$r['category']] = ($byFile[$r['file']][$r['category']] ?? 0) + 1;
}

echo "Audit tonality – zakázaný slovník z CLAUDE.md\n";
echo str_repeat('=', 60) . "\n\n";

echo "Souhrn po kategoriích:\n";
ksort($byCategory);
foreach ($byCategory as $cat => $n) {
    echo sprintf("  %-15s %d\n", $cat, $n);
}
echo "\nCelkem nálezů: " . count($results) . "\n\n";

echo "Top 10 souborů s nejvíce nálezy:\n";
$fileTotals = array_map('array_sum', $byFile);
arsort($fileTotals);
$top = array_slice($fileTotals, 0, 10, true);
foreach ($top as $file => $n) {
    $cats = $byFile[$file];
    $catStr = implode(' ', array_map(fn($k, $v) => "$k:$v", array_keys($cats), $cats));
    echo sprintf("  %-50s %3d   (%s)\n", $file, $n, $catStr);
}

// Detail per match argumentu
$detail = $argv[1] ?? null;
if ($detail !== null) {
    echo "\n\nDetail nálezů ($detail):\n";
    foreach ($results as $r) {
        if ($r['category'] === $detail || $r['file'] === $detail || $r['match'] === $detail) {
            echo sprintf("  %s:%d  [%s]  %s    \"%s\"\n",
                $r['file'], $r['line'], $r['category'], $r['match'], $r['context']);
        }
    }
}

echo "\nTip: php scripts/check_tonality.php hype                 # výpis nálezů kategorie\n";
echo "     php scripts/check_tonality.php content/chapters/X.md # výpis nálezů v souboru\n";

exit(count($results) === 0 ? 0 : 1);
