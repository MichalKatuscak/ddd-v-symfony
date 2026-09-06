<?php

declare(strict_types=1);

/**
 * Messenger ověřuje třídy z `routing:` při kompilaci kontejneru, takže jméno,
 * které v knize nikde nevzniká, shodí čtenáři i `cache:clear`. Tahle kontrola
 * to najde dřív než on.
 */

$chapters = glob(__DIR__ . '/../content/chapters/*.md');
$sources = [];

foreach ($chapters as $file) {
    $sources[$file] = file_get_contents($file);
}

$all = implode("\n", $sources);

// Třídy, které kniha někde definuje.
preg_match_all(
    '/\b(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|enum)\s+([A-Z]\w+)/',
    $all,
    $m,
);
$defined = array_flip($m[1]);

$problems = [];

foreach ($sources as $file => $source) {
    // Zakomentované řádky neřešíme – ty se do projektu nedostanou.
    preg_match_all(
        "/^[ \t]*'?(App\\\\[A-Za-z\\\\]+)'?:\s*(async\w*|sync)\b/m",
        $source,
        $matches,
        PREG_SET_ORDER,
    );

    foreach ($matches as $match) {
        $fqcn = $match[1];
        $short = substr($fqcn, strrpos($fqcn, '\\') + 1);

        if (!isset($defined[$short])) {
            $problems[] = sprintf('%s → %s', basename($file), $fqcn);
        }
    }
}

if ($problems === []) {
    echo "OK – všechny třídy v messenger routingu kniha definuje\n";
    exit(0);
}

echo "CHYBA – routing jmenuje třídy, které kniha nikde nedefinuje:\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
echo "\nMessenger je ověřuje při kompilaci kontejneru, takže tohle shodí i cache:clear.\n";

exit(1);
