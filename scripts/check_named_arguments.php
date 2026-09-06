<?php

declare(strict_types=1);

/**
 * Pojmenovaný argument, který konstruktor nezná, PHP odmítne až za běhu
 * hláškou „Unknown named parameter". V ukázkách knihy se taková neshoda
 * pozná až tehdy, když čtenář spustí právě tu větev — a u kompenzací
 * ságy to znamená tichou zprávu v dead-letter frontě.
 *
 * Kontrola porovnává `new Trida(jmeno: …)` s deklarací konstruktoru téže
 * třídy. Třídy stejného jména z různých kapitol se řeší zvlášť: hlásí se
 * jen tehdy, když parametr nezná ŽÁDNÁ z jejich variant.
 */

$chapters = glob(__DIR__ . '/../content/chapters/*.md');

/** @var array<string, list<array<string>>> $ctors */
$ctors = [];

foreach ($chapters as $file) {
    $source = file_get_contents($file);

    preg_match_all(
        '/\b(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)[^{]*\{(.*?)\n\}/s',
        $source,
        $classes,
        PREG_SET_ORDER,
    );

    foreach ($classes as [$whole, $name, $body]) {
        if (!preg_match('/function __construct\s*\((.*?)\)\s*[:{]/s', $body, $ctor)) {
            continue;
        }

        preg_match_all('/\$(\w+)/', $ctor[1], $params);
        $ctors[$name][] = $params[1];
    }
}

$problems = [];

foreach ($chapters as $file) {
    $source = file_get_contents($file);

    // Volání uvnitř atributů (#[ApiResource(operations: [new Post(...)])])
    // míří na třídy frameworku, ne na modely z knihy.
    $source = preg_replace('/(?m)^\s*#\[.*$/', '', $source);

    // Rekurzivní vzor, aby se argumenty načetly i s vnořenými závorkami.
    preg_match_all(
        '/\bnew\s+(\w+)\s*(\((?:[^()]++|(?2))*+\))/s',
        $source,
        $calls,
        PREG_SET_ORDER,
    );

    foreach ($calls as [$whole, $class, $args]) {
        $args = substr($args, 1, -1); // ořezat vnější závorky
        if (!isset($ctors[$class])) {
            continue; // třídu kniha nedefinuje, to hlídá jiná kontrola
        }

        // Vnořená volání nesou vlastní pojmenované argumenty; odstraní se,
        // aby se nepřipsaly vnější třídě.
        $ownArgs = preg_replace('/\bnew\s+\w+\s*\([^()]*\)/s', 'NESTED', $args);

        preg_match_all('/(?m)^\s*(\w+):\s/', $ownArgs, $named);

        foreach ($named[1] as $argument) {
            $known = false;

            foreach ($ctors[$class] as $variant) {
                if (in_array($argument, $variant, true)) {
                    $known = true;
                    break;
                }
            }

            if (!$known) {
                $problems[$class . '::' . $argument] = sprintf(
                    '%s → new %s(… %s: …), konstruktor zná: %s',
                    basename($file),
                    $class,
                    $argument,
                    implode(', ', array_unique(array_merge(...$ctors[$class]))),
                );
            }
        }
    }
}

if ($problems === []) {
    echo "OK – pojmenované argumenty odpovídají konstruktorům\n";
    exit(0);
}

echo "CHYBA – volání pojmenuje parametr, který konstruktor nemá:\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
echo "\nPHP to odmítne až za běhu: „Unknown named parameter\".\n";

exit(1);
