<?php

declare(strict_types=1);

/**
 * `$event->shipmentId->value` na vlastnosti, kterou třída deklaruje jako
 * `string`, PHP nahlásí až za běhu jako „Attempt to read property on string".
 * V ságové cestě to znamená zaseklý proces a zprávu v dead-letter frontě.
 *
 * Kontrola typ určí z deklarace parametru metody: v
 * `onShipmentCreated(ShipmentCreated $event)` se `$event` váže na třídu,
 * jejíž vlastnosti kniha zná.
 */

$chapters = glob(__DIR__ . '/../content/chapters/*.md');
$sources = array_map('file_get_contents', $chapters);
$all = implode("\n", $sources);

/** @var array<string, array<string, string>> $props  třída => vlastnost => typ */
$props = [];

preg_match_all(
    '/\b(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)[^{]*\{(.*?)\n\}/s',
    $all,
    $classes,
    PREG_SET_ORDER,
);

foreach ($classes as [$whole, $class, $body]) {
    preg_match_all(
        '/public\s+(?:readonly\s+)?(?:private\(set\)\s+)?(\??\w+)\s+\$(\w+)/',
        $body,
        $declared,
        PREG_SET_ORDER,
    );

    foreach ($declared as [$_, $type, $name]) {
        // Víc variant téže třídy napříč knihou: string vyhrává jen tehdy,
        // když ho neurčuje jinde hodnotový objekt.
        if (!isset($props[$class][$name]) || $props[$class][$name] === 'string') {
            $props[$class][$name] = ltrim($type, '?');
        }
    }
}

$problems = [];

foreach ($chapters as $i => $file) {
    // Vazba proměnné na typ platí jen uvnitř jedné ukázky. Napříč souborem
    // by se $event z jedné ukázky pletl s $event z jiné.
    preg_match_all('/^:::code\{[^}]*\}\n(.*?)\n:::\s*$/ms', $sources[$i], $blocks);

    foreach ($blocks[1] as $block) {
        preg_match_all('/function\s+\w+\s*\(([^)]*)\)/s', $block, $signatures);

        $bound = [];
        foreach ($signatures[1] as $params) {
            preg_match_all(
                '/((?:\??[A-Za-z_]\w*(?:\s*\|\s*)?)+)\s+\$(\w+)/s',
                preg_replace('/\s*\n\s*/', ' ', $params),
                $pairs,
                PREG_SET_ORDER,
            );

            foreach ($pairs as [$_, $typeExpr, $var]) {
                foreach (preg_split('/\s*\|\s*/', trim($typeExpr)) as $type) {
                    $type = ltrim($type, '?');
                    if ($type !== '' && ctype_upper($type[0])) {
                        $bound[$var][$type] = true;
                    }
                }
            }
        }

        preg_match_all('/\$(\w+)->(\w+)->value\b/', $block, $uses, PREG_SET_ORDER);

        foreach ($uses as [$whole, $var, $prop]) {
            if (!isset($bound[$var])) {
                continue;
            }

            // Union typ váže proměnnou na víc tříd. Hlásí se jen tehdy, když
            // je vlastnost string ve VŠECH, které ji vůbec deklarují.
            $declaring = [];

            foreach (array_keys($bound[$var]) as $class) {
                if (isset($props[$class][$prop])) {
                    $declaring[$class] = $props[$class][$prop];
                }
            }

            if ($declaring === [] || array_values(array_unique($declaring)) !== ['string']) {
                continue;
            }

            $problems[implode(',', array_keys($declaring)) . '::' . $prop] = sprintf(
                '%s → %s, ale %s je string',
                basename($file),
                $whole,
                implode(', ', array_map(
                    static fn (string $c): string => $c . '::$' . $prop,
                    array_keys($declaring),
                )),
            );
        }
    }
}

if ($problems === []) {
    echo "OK – ->value se nečte z vlastností typu string\n";
    exit(0);
}

echo "CHYBA – ->value na vlastnosti, kterou třída deklaruje jako string:\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
echo "\nPHP to nahlásí až za běhu: „Attempt to read property on string\".\n";

exit(1);
