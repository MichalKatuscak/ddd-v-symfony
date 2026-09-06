<?php

declare(strict_types=1);

/**
 * Hlídá statická volání na třídy, které kniha sama definuje.
 *
 * `Foo::bar()` na neexistující metodu neshodí `php -l`; PHP to odmítne až
 * za běhu hláškou "Call to undefined method". V knize se tak dostaly do textu
 * CustomerId::guest(), VerificationToken::valid() a DuplicateEmailException::forEmail(),
 * přestože kanonické třídy takové továrny nemají.
 *
 * Třídy se párují přes plné jméno (namespace + importy), ne přes krátké -
 * kniha záměrně ukazuje víc variant téhož `Order` a krátká jména kolidují.
 * Definice téže třídy z různých kapitol se slučují, protože ukázky bývají
 * výřezy a metoda může být zavedená jinde.
 */

$files = array_slice($argv, 1);
if ($files === []) {
    $files = glob(__DIR__ . '/../content/chapters/*.md') ?: [];
}

/** Metody, které PHP dodává samo. */
const BUILTIN = ['class', 'from', 'tryFrom', 'cases'];

/**
 * Vestavěné výjimky PHP. Jejich API je uzavřené, takže dědit z nich
 * neomlouvá volání neexistující statické továrny - a právě tak se do knihy
 * dostalo DuplicateEmailException::forEmail().
 */
const PHP_EXCEPTIONS = [
    'Throwable', 'Exception', 'Error', 'ErrorException',
    'LogicException', 'RuntimeException', 'DomainException',
    'InvalidArgumentException', 'OutOfRangeException', 'OutOfBoundsException',
    'LengthException', 'RangeException', 'OverflowException', 'UnderflowException',
    'UnexpectedValueException', 'BadFunctionCallException', 'BadMethodCallException',
    'JsonException', 'TypeError', 'ValueError',
];

const EXCEPTION_API = [
    '__construct', '__toString', 'getMessage', 'getCode', 'getFile',
    'getLine', 'getTrace', 'getTraceAsString', 'getPrevious',
];

function blocks(string $file): array
{
    preg_match_all(
        '/:::code\{language="php"([^}]*)\}\n(.*?)\n:::/s',
        file_get_contents($file),
        $m,
        PREG_SET_ORDER,
    );

    $out = [];
    foreach ($m as $set) {
        preg_match('/filename="([^"]*)"/', $set[1], $f);
        $out[] = ['filename' => $f[1] ?? '?', 'code' => $set[2]];
    }

    return $out;
}

function stripNoise(string $code): string
{
    $code = preg_replace('#/\*.*?\*/#s', '', $code);
    $code = preg_replace('#//[^\n]*#', '', $code);
    $code = preg_replace("#'(?:\\\\.|[^'\\\\])*+'#", "''", $code);
    $code = preg_replace('#"(?:\\\\.|[^"\\\\])*+"#', '""', $code);

    return $code;
}

/** Rozdělí blok na segmenty podle deklarace namespace. */
function segments(string $code): array
{
    $parts = preg_split('/(?=^namespace\s+[\w\\\\]+;)/m', $code, -1, PREG_SPLIT_NO_EMPTY);

    $out = [];
    foreach ($parts as $part) {
        if (preg_match('/^namespace\s+([\w\\\\]+);/m', $part, $m)) {
            $out[] = ['ns' => $m[1], 'code' => $part];
        }
    }

    return $out;
}

function importsOf(string $segment): array
{
    preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $segment, $m, PREG_SET_ORDER);

    $out = [];
    foreach ($m as $set) {
        $short = $set[2] ?? substr(strrchr('\\' . $set[1], '\\'), 1);
        $out[$short] = $set[1];
    }

    return $out;
}

/** Plné jméno pro krátkou referenci v daném segmentu. */
function resolve(string $name, string $ns, array $imports): string
{
    if (str_starts_with($name, '\\')) {
        return ltrim($name, '\\');
    }

    return $imports[$name] ?? $ns . '\\' . $name;
}

// 1. Co která třída umí. Klíč je plné jméno, hodnoty se slučují napříč kapitolami.
$methods = [];
$parents = [];
$isEnum  = [];

foreach ($files as $file) {
    foreach (blocks($file) as $b) {
        foreach (segments($b['code']) as $seg) {
            $imports = importsOf($seg['code']);

            preg_match_all(
                '/^\s*(?:final\s+|abstract\s+|readonly\s+)*(class|interface|trait|enum)\s+(\w+)([^{]*)\{/m',
                $seg['code'],
                $decls,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
            );

            foreach ($decls as $i => $d) {
                $kind  = $d[1][0];
                $short = $d[2][0];
                $fqcn  = $seg['ns'] . '\\' . $short;
                $start = $d[0][1] + strlen($d[0][0]);
                $end   = $decls[$i + 1][0][1] ?? strlen($seg['code']);
                $body  = substr($seg['code'], $start, $end - $start);

                if ($kind === 'enum') {
                    $isEnum[$fqcn] = true;
                }

                if (preg_match('/extends\s+([\w\\\\]+)/', $d[3][0], $e)) {
                    $parents[$fqcn] = resolve($e[1], $seg['ns'], $imports);
                }

                preg_match_all('/function\s+(\w+)\s*\(/', $body, $fn);
                foreach ($fn[1] as $name) {
                    $methods[$fqcn][$name] = true;
                }

                preg_match_all('/^\s*case\s+(\w+)/m', $body, $cs);
                foreach ($cs[1] as $name) {
                    $methods[$fqcn][$name] = true;
                }
            }
        }
    }
}

function knows(string $fqcn, string $method, array $methods, array $parents, array $seen = []): bool
{
    if (isset($seen[$fqcn])) {
        return false;
    }
    $seen[$fqcn] = true;

    if (isset($methods[$fqcn][$method])) {
        return true;
    }

    $parent = $parents[$fqcn] ?? null;

    if ($parent === null) {
        return false;
    }

    // Vestavěná výjimka PHP: API známe, takže se dá rozhodnout.
    if (in_array($parent, PHP_EXCEPTIONS, true)) {
        return in_array($method, EXCEPTION_API, true);
    }

    // Jiný rodič mimo knihu (vendor) se ověřit nedá - volání propustíme.
    if (!isset($methods[$parent]) && !isset($parents[$parent])) {
        return true;
    }

    return knows($parent, $method, $methods, $parents, $seen);
}

// 2. Volání.
$problems = [];
foreach ($files as $file) {
    $chapter = basename($file, '.md');

    foreach (blocks($file) as $b) {
        foreach (segments($b['code']) as $seg) {
            $imports = importsOf($seg['code']);
            $code    = stripNoise($seg['code']);

            preg_match_all('/(?<![\\\\\w$>])([A-Z]\w*)::(\w+)\s*\(/', $code, $calls, PREG_SET_ORDER);

            foreach ($calls as $call) {
                [$all, $short, $method] = $call;

                if (in_array($method, BUILTIN, true) || $short === 'self' || $short === 'static') {
                    continue;
                }

                $fqcn = resolve($short, $seg['ns'], $imports);

                // Třídu, kterou kniha nedefinuje, nemáme s čím porovnat.
                if (!isset($methods[$fqcn])) {
                    continue;
                }
                if (isset($isEnum[$fqcn]) && in_array($method, BUILTIN, true)) {
                    continue;
                }
                if (knows($fqcn, $method, $methods, $parents)) {
                    continue;
                }

                $key = $chapter . '|' . $b['filename'] . '|' . $fqcn . '|' . $method;
                $problems[$key] = [$chapter, $b['filename'], $fqcn, $method];
            }
        }
    }
}

if ($problems === []) {
    echo "OK – statická volání odpovídají třídám, které kniha definuje\n";
    exit(0);
}

echo "CHYBA – volaná statická metoda na třídě z knihy neexistuje:\n\n";
foreach ($problems as [$chapter, $filename, $fqcn, $method]) {
    printf("  %-24s %s\n      %s::%s()\n", $chapter, $filename, $fqcn, $method);
}
printf("\nCelkem: %d. PHP to odmítne až za běhu: „Call to undefined method\".\n", count($problems));
exit(1);
