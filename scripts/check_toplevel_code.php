<?php

declare(strict_types=1);

/**
 * Hlídá spustitelný kód na úrovni souboru v ukázkách, které deklarují třídu.
 *
 * Ukázka uložená pod jménem z `filename=` se stane běžným PHP souborem.
 * Příkazy mimo třídu PHP vykoná při autoloadu - tedy ve chvíli, kdy si
 * kdokoli vyžádá kteroukoli třídu z toho souboru. Kniha na to najela
 * třikrát: OrderBuilder v kapitole o testování, OrderId v kapitole
 * o bolestech a Email v kapitole o anti-vzorech, kde demo položilo
 * celou aplikaci hláškou o špatném typu argumentu.
 *
 * `php -l` je na to slepý: soubor je syntakticky v pořádku.
 */

$files = array_slice($argv, 1);
if ($files === []) {
    $files = glob(__DIR__ . '/../content/chapters/*.md') ?: [];
}

function stripComments(string $code): string
{
    $code = preg_replace('#/\*.*?\*/#s', '', $code);

    return preg_replace('#//[^\n]*#', '', $code);
}

$problems = [];

foreach ($files as $file) {
    $chapter = basename($file, '.md');

    preg_match_all(
        '/:::code\{language="php"([^}]*)\}\n(.*?)\n:::/s',
        file_get_contents($file),
        $blocks,
        PREG_SET_ORDER,
    );

    foreach ($blocks as $block) {
        preg_match('/filename="([^"]*)"/', $block[1], $f);
        $filename = $f[1] ?? '?';
        $code     = $block[2];

        // Zajímají jen bloky, které se ukládají jako třída v namespace.
        if (!preg_match('/^namespace\s/m', $code)) {
            continue;
        }
        if (!preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s/m', $code)) {
            continue;
        }

        $depth     = 0;
        $seenClass = false;

        foreach (explode("\n", stripComments($code)) as $number => $line) {
            $trimmed = trim($line);

            if ($depth === 0
                && preg_match('/^(?:final |abstract |readonly )*(?:class|interface|trait|enum)\s+\w/', $trimmed)
            ) {
                $seenClass = true;
            }

            if ($depth === 0 && $seenClass && $trimmed !== '') {
                $isExecutable = preg_match('/^\$\w+\s*=/', $trimmed)
                    || preg_match('/^[a-z_]\w*\s*\(/', $trimmed)
                    || preg_match('/^[A-Z]\w*::/', $trimmed);

                if ($isExecutable) {
                    $problems[] = [$chapter, $filename, $number + 1, $trimmed];
                }
            }

            $depth += substr_count($line, '{') - substr_count($line, '}');
        }
    }
}

if ($problems === []) {
    echo "OK – žádná ukázka nespouští kód na úrovni souboru\n";
    exit(0);
}

echo "CHYBA – spustitelný kód mimo třídu v souboru, který třídu deklaruje:\n\n";
foreach ($problems as [$chapter, $filename, $line, $code]) {
    printf("  %-22s %s\n      řádek %d: %s\n", $chapter, $filename, $line, $code);
}
echo "\nPHP to vykoná při autoloadu. Ukázku zabalte do komentáře nebo do metody.\n";
exit(1);
