<?php

declare(strict_types=1);

/**
 * FAQ bloky jsou YAML, ne próza. Nezalomená dvojtečka v otázce nebo ztracené
 * odsazení u víceřádkové odpovědi shodí celou stránku na 500 – a projeví se
 * to až vykreslením kapitoly, ne při editaci textu.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$problems = [];

foreach (glob(__DIR__ . '/../content/chapters/*.md') as $file) {
    $source = file_get_contents($file);

    if (!preg_match_all('/^:::faq\{[^}]*\}\n(.*?)\n:::\s*$/ms', $source, $blocks)) {
        continue;
    }

    foreach ($blocks[1] as $index => $yaml) {
        try {
            $parsed = Yaml::parse($yaml);
        } catch (Throwable $e) {
            $problems[] = sprintf(
                '%s, blok %d: %s',
                basename($file),
                $index + 1,
                $e->getMessage(),
            );

            continue;
        }

        if (!is_array($parsed)) {
            $problems[] = sprintf('%s, blok %d: výsledkem není seznam otázek', basename($file), $index + 1);

            continue;
        }

        foreach ($parsed as $position => $item) {
            if (!is_array($item) || !isset($item['question'], $item['answer'])) {
                $problems[] = sprintf(
                    '%s, blok %d, položka %d: chybí `question` nebo `answer`',
                    basename($file),
                    $index + 1,
                    $position + 1,
                );
            }
        }
    }
}

if ($problems === []) {
    echo "OK – všechny FAQ bloky se parsují jako YAML\n";
    exit(0);
}

echo "CHYBA – FAQ blok se nedá načíst:\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
echo "\nStránka kapitoly by skončila na 500.\n";

exit(1);
