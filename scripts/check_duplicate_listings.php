<?php

declare(strict_types=1);

/**
 * Dvě ukázky pod stejným jménem souboru jsou past: čtenář neví, kterou opsat,
 * a slepení obou končí na „Cannot redeclare". Kniha na to má konvenci —
 * rozlišující dovětek v názvu bloku, například „(výřez)" nebo „(anti-vzor)".
 * Tahle kontrola hlídá, že se na něj nezapomnělo.
 */

/**
 * Kontrola se vědomě omezuje na kapitoly, podle kterých se staví projekt.
 * Jinde je opakované jméno běžné (anti-vzor vedle správné verze) a konfigurace
 * se napříč knihou skládá záměrně, což kapitoly říkají v textu.
 */
const BUILDABLE = [
    'practical_examples.md',
    'implementation_in_symfony.md',
    'basic_concepts.md',
    'aggregate_design.md',
    'outbox_pattern.md',
    'cqrs.md',
    'sagas.md',
    'authorization_in_ddd.md',
    'event_sourcing.md',
    'testing_ddd.md',
    'architectural_styles.md',
    'lesser_known_patterns.md',
    'anti_patterns.md',
    'migration_from_crud.md',
    'case_study.md',
];

/** Zástupné názvy, které neoznačují konkrétní soubor v projektu. */
const PLACEHOLDERS = ['snippet', 'terminál', 'struktura'];

$chapters = array_filter(
    glob(__DIR__ . '/../content/chapters/*.md'),
    static fn (string $path): bool => in_array(basename($path), BUILDABLE, true),
);

/** @var array<string, list<string>> $listings */
$listings = [];

foreach ($chapters as $file) {
    $source = file_get_contents($file);

    preg_match_all(
        '/^:::code\{[^}]*filename="([^"]+)"/m',
        $source,
        $matches,
        PREG_SET_ORDER,
    );

    foreach ($matches as $match) {
        $filename = $match[1];

        // Dovětek v závorce je vědomé rozlišení – ten blok neřešíme.
        if (str_contains($filename, '(')) {
            continue;
        }

        // Blok se dvěma soubory v názvu („A.php + B.php") taky.
        if (str_contains($filename, ' + ')) {
            continue;
        }

        // Konfigurace se v knize skládá z víc bloků – kapitoly to říkají.
        if (str_starts_with($filename, 'config/')) {
            continue;
        }

        foreach (PLACEHOLDERS as $placeholder) {
            if (str_contains($filename, $placeholder)) {
                continue 2;
            }
        }

        $listings[$filename][] = basename($file);
    }
}

$problems = [];

foreach ($listings as $filename => $chaptersUsing) {
    if (count($chaptersUsing) > 1) {
        $problems[$filename] = $chaptersUsing;
    }
}

if ($problems === []) {
    echo "OK – žádné dvě ukázky nesdílejí jméno souboru bez rozlišení\n";
    exit(0);
}

echo "CHYBA – tytéž soubory ukazuje víc bloků bez rozlišujícího dovětku:\n";
foreach ($problems as $filename => $chaptersUsing) {
    echo sprintf("  - %s  (%s)\n", $filename, implode(', ', $chaptersUsing));
}
echo "\nDoplňte do názvu bloku dovětek – „(výřez)\", „(anti-vzor)\", „(mapování)\" –\n";
echo "nebo bloky sjednoťte. Čtenář jinak neví, kterou verzi opsat.\n";

exit(1);
