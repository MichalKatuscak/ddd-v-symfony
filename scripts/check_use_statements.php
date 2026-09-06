<?php

declare(strict_types=1);

/**
 * Hlídá nevyřešená jména tříd v PHP ukázkách kapitol.
 *
 * V souboru s deklarovaným namespace se nekvalifikované jméno třídy hledá
 * v tom namespace. Chybějící `use` proto neshodí `php -l`, ale kontejner
 * nebo autoloader až za běhu - hláškou "class was not found". Kniha na to
 * najela třikrát: DuplicateEmailException, OrderStatus a UpcasterChain.
 *
 * Za vyřešené se považuje jméno, které je importované, definované v témže
 * bloku, dostupné v témž namespace odjinud z knihy, nebo psané s vedoucím
 * zpětným lomítkem.
 */

$files = array_slice($argv, 1);
if ($files === []) {
    $files = glob(__DIR__ . '/../content/chapters/*.md') ?: [];
}

/** Bloky, které nejsou opsatelný soubor, ale náčrt nebo pseudokód. */
const SKIP_FILENAMES = [
    'Symfony / PHP draft Ordering BC',
];

/**
 * Vědomé výjimky ve tvaru "soubor|jméno".
 * Shared kernel je samostatný balíček s vlastním namespace; Currency v něm
 * je sourozenec Money, ne kanonická třída z App\SharedKernel\Domain.
 */
const SKIP_REFS = [
    'shared-kernel/src/Money/Money.php|Currency',
];

/** Vestavěná jména PHP, která se v ukázkách používají bez lomítka. */
const GLOBALS_OK = [
    'Closure', 'Generator', 'Throwable', 'Stringable', 'Countable',
    'Traversable', 'Iterator', 'IteratorAggregate', 'ArrayAccess', 'JsonSerializable',
    'DateTimeImmutable', 'DateTimeInterface', 'DateTime', 'DateTimeZone', 'DateInterval',
];

function blocks(string $file): array
{
    $md = file_get_contents($file);
    preg_match_all('/:::code\{language="php"([^}]*)\}\n(.*?)\n:::/s', $md, $m, PREG_SET_ORDER);

    $out = [];
    foreach ($m as $set) {
        preg_match('/filename="([^"]*)"/', $set[1], $f);
        $out[] = ['filename' => $f[1] ?? '?', 'code' => $set[2]];
    }

    return $out;
}

/** Odstraní komentáře a řetězce - jména v nich nejsou reference. */
function stripNoise(string $code): string
{
    $code = preg_replace('#/\*.*?\*/#s', '', $code);
    $code = preg_replace('#//[^\n]*#', '', $code);
    $code = preg_replace('#^\s*\#[^\n]*$#m', '', $code);
    $code = preg_replace("#'(?:\\\\.|[^'\\\\])*+'#", "''", $code);
    $code = preg_replace('#"(?:\\\\.|[^"\\\\])*+"#', '""', $code);

    return $code;
}

function namespaceOf(string $code): ?string
{
    return preg_match('/^namespace\s+([\w\\\\]+);/m', $code, $m) ? $m[1] : null;
}

function declaredIn(string $code): array
{
    preg_match_all(
        '/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
        $code,
        $m,
    );

    return $m[1];
}

// 1. Co kniha zná: co sama definuje a co kdekoli importuje.
//    Mapa krátké jméno => množina plných jmen. Jméno, které kniha nezná
//    vůbec, je doprovodná třída ze stejného namespace (Input/Output DTO,
//    sourozenec ukázky) a chybějící import u ní nehrozí.
$defined = [];
$known   = [];
foreach ($files as $file) {
    foreach (blocks($file) as $b) {
        $ns = namespaceOf($b['code']);
        foreach (declaredIn($b['code']) as $name) {
            $fqcn = $ns === null ? $name : $ns . '\\' . $name;
            $defined[$fqcn] = true;
            $known[$name][$fqcn] = true;
        }

        preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $b['code'], $u, PREG_SET_ORDER);
        foreach ($u as $set) {
            $short = $set[2] ?? substr(strrchr('\\' . $set[1], '\\'), 1);
            $known[$short][$set[1]] = true;
        }
    }
}

// 2. Reference, které se nikam nevážou.
$problems = [];
foreach ($files as $file) {
    $chapter = basename($file, '.md');

    foreach (blocks($file) as $b) {
        if (in_array($b['filename'], SKIP_FILENAMES, true)) {
            continue;
        }

        // Blok může nést víc souborů za sebou. Každý má vlastní namespace
        // i vlastní importy, takže se musí posuzovat zvlášť - jinak
        // kontrola přiřkne reference z druhého souboru namespace prvního.
        $segments = preg_split('/(?=^namespace\s+[\w\\\\]+;)/m', $b['code'], -1, PREG_SPLIT_NO_EMPTY);

        foreach ($segments as $segment) {
            $ns = namespaceOf($segment);
            if ($ns === null) {
                continue;
            }

            preg_match_all('/^use\s+(?:function\s+)?([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $segment, $u, PREG_SET_ORDER);
            $imported = [];
            foreach ($u as $set) {
                $alias = $set[2] ?? '';
                $imported[$alias !== '' ? $alias : substr(strrchr('\\' . $set[1], '\\'), 1)] = true;
            }

            $local = array_flip(declaredIn($segment));
            $code  = stripNoise($segment);

            // Vedoucí zpětné lomítko referenci vyřeší samo, proto (?<![\\\w]).
            // Typové pozice jsou v ukázkách nejčastější a nejdřív mi unikly:
            // chybějící `use` na typu parametru php -l neuvidí a kontejner
            // spadne až při sestavování služby.
            $patterns = [
                '/(?<![\\\\\w$>])new\s+([A-Z]\w*)\s*\(/',
                '/(?<![\\\\\w$>])([A-Z]\w*)::/',
                '/(?:extends|implements)\s+([A-Z]\w*)/',
                '/instanceof\s+([A-Z]\w*)/',
                '/catch\s*\(\s*([A-Z]\w*)/',
                '/(?<![\\\\\w$>])\??([A-Z]\w*)\s+\$\w+/',
                '/\)\s*:\s*\??([A-Z]\w*)/',
            ];

            $refs = [];
            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $code, $found, PREG_SET_ORDER);
                foreach ($found as $set) {
                    $refs[] = $set[1];
                }
            }

            $seen = [];
            foreach ($refs as $name) {
                if ($name === '' || isset($seen[$name])) {
                    continue;
                }
                if (isset($imported[$name]) || isset($local[$name])) {
                    continue;
                }
                if (in_array($name, GLOBALS_OK, true) || isset($defined[$ns . '\\' . $name])) {
                    continue;
                }
                // Třída ze stejného namespace se importovat nemusí, i když
                // ji kniha ukazuje jen jako import z jiného souboru.
                if (isset($known[$name][$ns . '\\' . $name])) {
                    continue;
                }
                // Jméno, které kniha nikde nezná, je sourozenec ze stejného
                // namespace - tam se import nepíše a chyba to není.
                if (!isset($known[$name])) {
                    continue;
                }
                if (in_array($b['filename'] . '|' . $name, SKIP_REFS, true)) {
                    continue;
                }

                $seen[$name] = true;
                $problems[] = [$chapter, $b['filename'], $ns, $name, implode(', ', array_keys($known[$name]))];
            }
        }
    }
}

if ($problems === []) {
    echo "OK – všechna jména tříd v ukázkách se dají vyřešit\n";
    exit(0);
}

echo "CHYBA – nekvalifikované jméno třídy bez importu:\n\n";
foreach ($problems as [$chapter, $filename, $ns, $name, $candidates]) {
    printf("  %-24s %s\n      %s hledané v %s\n      kniha zná: %s\n", $chapter, $filename, $name, $ns, $candidates);
}
printf("\nCelkem: %d. Bez `use` se jméno hledá ve vlastním namespace a selže až za běhu.\n", count($problems));
exit(1);
