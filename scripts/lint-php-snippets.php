#!/usr/bin/env php
<?php

/**
 * Syntaktická kontrola všech PHP ukázek v kapitolách.
 *
 * Vytáhne bloky :::code{language="php" ...} z content/chapters/*.md a každý
 * zvlášť protáhne `php -l`. Fragmenty bez otevírací značky <?php ji dostanou
 * automaticky. Selhání vypíše jako "kapitola.md:řádek (filename): chyba".
 *
 * Použití:
 *   php scripts/lint-php-snippets.php              # celá kniha
 *   php scripts/lint-php-snippets.php cqrs.md ...  # jen vybrané kapitoly
 *   php scripts/lint-php-snippets.php -v           # vypsat i OK bloky
 *
 * Záměrně nefunkční ukázky lze vyřadit v $ignore níže (kapitola + filename
 * atribut bloku). Exit kód: 0 = vše OK, 1 = aspoň jedna chyba.
 */

declare(strict_types=1);

$chaptersDir = dirname(__DIR__) . '/content/chapters';

// Bloky, které syntaktickou kontrolou projít nemají (a víme proč).
// Klíč: basename kapitoly, hodnota: seznam substringů filename atributu.
$ignore = [
    // (zatím prázdné)
];

$verbose = in_array('-v', $argv, true);
$files = array_values(array_filter(array_slice($argv, 1), fn ($a) => $a !== '-v'));
if ($files === []) {
    $files = array_map('basename', glob($chaptersDir . '/*.md') ?: []);
}

$total = 0;
$failed = 0;
$skipped = 0;
$tmp = tempnam(sys_get_temp_dir(), 'snippet');

foreach ($files as $file) {
    $path = $chaptersDir . '/' . basename($file);
    if (!is_file($path)) {
        fwrite(STDERR, "Soubor nenalezen: $path\n");
        exit(2);
    }

    $lines = file($path);
    $inBlock = false;
    $snippet = [];
    $startLine = 0;
    $blockFilename = '';

    foreach ($lines as $i => $line) {
        if (!$inBlock && preg_match('/^:::code\{language="php"(?:.*filename="([^"]*)")?/', $line, $m)) {
            $inBlock = true;
            $snippet = [];
            $startLine = $i + 2; // první řádek kódu
            $blockFilename = $m[1] ?? '';
            continue;
        }
        if ($inBlock && trim($line) === ':::') {
            $inBlock = false;
            $total++;

            $label = $blockFilename !== '' ? " ($blockFilename)" : '';
            foreach ($ignore[basename($file)] ?? [] as $needle) {
                if ($blockFilename !== '' && str_contains($blockFilename, $needle)) {
                    $skipped++;
                    if ($verbose) {
                        echo "SKIP  " . basename($file) . ":$startLine$label\n";
                    }
                    continue 2;
                }
            }

            $raw = implode('', $snippet);
            $code = str_contains($raw, '<?php') ? $raw : "<?php\n" . $raw;

            file_put_contents($tmp, $code);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $rc);

            // Druhý pokus: výřez těla třídy (metody/properties bez obalu class).
            if ($rc !== 0 && !str_contains($raw, '<?php')) {
                file_put_contents($tmp, "<?php\nclass __SnippetFragment {\n" . $raw . "\n}");
                exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out2, $rc2);
                if ($rc2 === 0) {
                    $rc = 0;
                    if ($verbose) {
                        echo "OK*   " . basename($file) . ":$startLine$label (výřez těla třídy)\n";
                    }
                }
                $out2 = [];
            }

            if ($rc !== 0) {
                $failed++;
                // Přepočet řádku chyby z temp souboru na řádek v .md
                $msg = trim(implode(' ', $out));
                $msg = str_replace($tmp, '', $msg);
                if (preg_match('/on line (\d+)/', $msg, $lm)) {
                    $offset = str_contains(implode('', $snippet), '<?php') ? 0 : 1;
                    $mdLine = $startLine + (int) $lm[1] - 1 - $offset;
                    $msg = preg_replace('/on line \d+/', "→ " . basename($file) . ":$mdLine", $msg);
                }
                echo "FAIL  " . basename($file) . ":$startLine$label\n      $msg\n";
            } elseif ($verbose) {
                echo "OK    " . basename($file) . ":$startLine$label\n";
            }
            $out = [];
            continue;
        }
        if ($inBlock) {
            $snippet[] = $line;
        }
    }
}

unlink($tmp);

echo "\nBloků: $total, chyb: $failed, přeskočeno: $skipped\n";
exit($failed > 0 ? 1 : 0);
