#!/usr/bin/env php
<?php
// Převede kapitolu z interního formátu na Pandoc-kompatibilní Markdown.
//
// Klíčová transformace: :::type{attrs}...body...::: bloky jsou převedeny
// na raw HTML přímo v PHP – Pandoc tak parsuje jen čistý Markdown bez
// fenced divů, které mají kvadratické chování při parsování.

declare(strict_types=1);

$file = null;
$target = 'epub';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--target=')) {
        $target = substr($arg, 9);
        if (!in_array($target, ['epub', 'pdf'], true)) {
            fwrite(STDERR, "Neznámý target: {$target}. Povolené: epub, pdf\n");
            exit(1);
        }
        continue;
    }
    if ($file === null) {
        $file = $arg;
    }
}
if ($file === null) {
    fwrite(STDERR, "Použití: preprocess.php [--target=pdf|epub] <soubor.md>\n");
    exit(1);
}

$content = file_get_contents($file);
if ($content === false) {
    fwrite(STDERR, "Nelze číst: {$file}\n");
    exit(1);
}

// ── Oddělit YAML front matter ──────────────────────────────────────────────
$title = '';
if (str_starts_with($content, '---')) {
    $end = strpos($content, "\n---", 3);
    if ($end !== false) {
        $frontmatter = substr($content, 4, $end - 4);
        $content     = ltrim(substr($content, $end + 4));
        if (preg_match('/^title:\s*["\']?(.+?)["\']?\s*$/m', $frontmatter, $m)) {
            $title = trim($m[1], '"\'');
        }
    }
}

if ($title !== '') {
    $content = "# {$title}\n\n" . $content;
}

// ── Escapovat backslash v nadpisech (Pandoc → LaTeX bug) ──────────────────
// Markdown `\M` v section title → neescapované `\Money` v LaTeXu.
// Zdvojený backslash `\\` → `\textbackslash` v LaTeXu.
$content = preg_replace_callback(
    '/^(#{1,6} .+)$/m',
    static fn(array $m): string => str_replace('\\', '\\\\', $m[1]),
    $content,
);

// ── Zpracovat :::type{attrs}...body...::: bloky ───────────────────────────
$content = processBlocks($content, $target);

echo $content;

// ─────────────────────────────────────────────────────────────────────────

function parseAttrs(string $attrs): array
{
    $result = [];
    preg_match_all('/(\w+)="([^"]*)"/', $attrs, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $result[$m[1]] = $m[2];
    }
    return $result;
}

function escapeHtml(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function processBlocks(string $markdown, string $target = 'epub'): string
{
    $lines  = explode("\n", $markdown);
    $output = [];
    $i      = 0;
    $n      = count($lines);

    while ($i < $n) {
        $line = $lines[$i];

        if (preg_match('/^:::(\w+)(?:\{([^}]*)\})?$/', $line, $m)) {
            $type      = $m[1];
            $attrs     = parseAttrs($m[2] ?? '');
            $depth     = 1;
            $bodyLines = [];
            $i++;

            while ($i < $n) {
                $inner = $lines[$i];
                if (preg_match('/^:::\w+/', $inner)) {
                    $depth++;
                    $bodyLines[] = $inner;
                } elseif ($inner === ':::') {
                    $depth--;
                    if ($depth === 0) { $i++; break; }
                    $bodyLines[] = $inner;
                } else {
                    $bodyLines[] = $inner;
                }
                $i++;
            }

            $body = implode("\n", $bodyLines);
            $rendered = renderBlock($type, $attrs, $body, $target);

            $output[] = '';
            $output[] = $rendered;
            $output[] = '';
        } else {
            $output[] = $line;
            $i++;
        }
    }

    return implode("\n", $output);
}

function renderBlock(string $type, array $attrs, string $body, string $target): string
{
    return match ($type) {
        'callout'  => renderCallout($attrs, $body, $target),
        'diagram'  => renderDiagram($attrs, $target),
        'faq'      => renderFaq($body, $target),
        'code'     => renderCode($attrs, $body, $target),
        default    => "<!-- unknown block: {$type} -->",
    };
}

function renderCallout(array $attrs, string $body, string $target): string
{
    $ctype = $attrs['type'] ?? 'note';
    $cssType = $ctype === 'warn' ? 'warning' : $ctype;

    $icons = [
        'note'      => 'ℹ',
        'warning'   => '⚠',
        'warn'      => '⚠',
        'tip'       => '💡',
        'important' => '★',
        'pattern'   => '◆',
    ];
    $icon = $icons[$ctype] ?? 'ℹ';

    $innerBody = processBlocks($body, $target);

    if ($target === 'pdf') {
        // Pandoc fenced div – Lua filter `pdf_callout.lua` ho obalí do
        // typst `#block(...)` s barevným pruhem a pozadím podle typu.
        // Tělo zůstává jako pandoc Blocks → headings, bold, code, listy
        // se uvnitř parsují normálně.
        // Vyšší fence pro případ vnořených fenced divs uvnitř těla.
        $fence = ':::::::';
        $bodyTrim = trim($innerBody);
        return "{$fence} callout-{$cssType}\n{$bodyTrim}\n{$fence}";
    }

    return "<div class=\"callout callout-{$cssType}\">\n"
        . "<p class=\"callout-icon\">{$icon}</p>\n\n"
        . $innerBody . "\n"
        . "</div>";
}

function renderDiagram(array $attrs, string $target): string
{
    $src   = $attrs['src']   ?? '';
    $title = $attrs['title'] ?? '';
    $fig   = $attrs['fig']   ?? '';

    if ($src === '') {
        return "<!-- diagram bez src -->";
    }

    $caption = $fig !== '' ? "Diagram {$fig}: {$title}" : $title;
    $figId   = 'fig-' . str_replace('.', '-', $fig);

    if ($target === 'pdf') {
        // Typst hledá obrázky relativně ke spuštění pandocu (CWD). Cesty
        // z webu začínají `images/` a fyzicky leží v `public/images/`.
        $pdfSrc = $src;
        if (str_starts_with($pdfSrc, 'images/')) {
            $pdfSrc = 'public/' . $pdfSrc;
        }
        $captionEsc = str_replace('*', '\\*', $caption);

        // Prázdný nebo chybějící SVG soubor by typst odmítl. Místo crashe
        // emitujeme jen popisek a upozorníme na stderr.
        $exists = is_file($pdfSrc) && filesize($pdfSrc) > 0;
        if (!$exists) {
            fwrite(STDERR, "  ⚠  Prázdný/chybějící diagram: {$pdfSrc} ({$caption}) – v PDF nahrazen popiskem\n");
            return "*{$captionEsc}* — *(diagram chybí)*";
        }

        // Pandoc v typst writeru obaluje samostatný image do `#figure(...)`
        // s automatickou hlavičkou „Figure N“. Caption pak bere z alt textu.
        // Šířka 100 % zabrání přetékání širokých SVG přes okraj stránky.
        $altCaption = str_replace([']', '['], ['\]', '\['], $caption);
        return "![{$altCaption}]({$pdfSrc}){#{$figId} width=100%}";
    }

    $escapedSrc   = escapeHtml($src);
    $escapedTitle = escapeHtml($title);
    $captionHtml  = escapeHtml($caption);

    return "<figure class=\"diagram-figure\" id=\"{$figId}\">\n"
        . "<img src=\"{$escapedSrc}\" alt=\"{$escapedTitle}\" class=\"diagram-img\">\n"
        . "<figcaption><em>{$captionHtml}</em></figcaption>\n"
        . "</figure>";
}

function renderFaq(string $body, string $target): string
{
    // Tělo má YAML strukturu:
    //   - question: Otázka?
    //     answer: 'Odpověď, případně víceřádková.'
    // (Backwards compat: starší formát `**Q?**\nA` zachováme.)
    $items = parseFaqItems($body);

    if ($target === 'pdf') {
        $out = ["**Často kladené dotazy**", ''];
        foreach ($items as [$q, $a]) {
            $out[] = '**' . trim($q) . '**';
            $out[] = '';
            // Pro PDF přepíšeme inline HTML na markdown ekvivalenty,
            // typst by jinak HTML zahodil.
            $out[] = htmlInlineToMarkdown(trim($a));
            $out[] = '';
        }
        return implode("\n", $out);
    }

    $html = "<h3 class=\"faq-heading\">Často kladené dotazy</h3>\n<div class=\"faq\">\n<dl>\n";
    foreach ($items as [$q, $a]) {
        $html .= "<dt>" . escapeHtml(trim($q)) . "</dt>\n";
        $html .= "<dd>" . trim($a) . "</dd>\n";
    }
    return $html . "</dl>\n</div>";
}

/**
 * @return list<array{0:string,1:string}> seznam párů [question, answer]
 */
function parseFaqItems(string $body): array
{
    $items = [];
    $lines = explode("\n", $body);
    $cur   = null;
    $field = null; // 'question' | 'answer' | null

    $flush = static function() use (&$cur, &$items): void {
        if ($cur !== null && $cur['q'] !== '') {
            $items[] = [$cur['q'], $cur['a']];
        }
        $cur = null;
    };

    foreach ($lines as $line) {
        // - question: ...
        if (preg_match('/^- question:\s*(.*)$/u', $line, $m)) {
            $flush();
            $cur = ['q' => trim($m[1]), 'a' => ''];
            $field = 'question';
            continue;
        }
        // (indent)answer: '...'
        if ($cur !== null && preg_match('/^\s+answer:\s*(.*)$/u', $line, $m)) {
            $val = $m[1];
            // Odstranit case 'answer: ''text''' zachovává single quotes okolo
            $val = trim($val);
            if (str_starts_with($val, "'") && str_ends_with($val, "'") && strlen($val) >= 2) {
                $val = substr($val, 1, -1);
                // Escapovaný apostrof v YAML single-quoted: '' → '
                $val = str_replace("''", "'", $val);
            } elseif (str_starts_with($val, "'")) {
                // Otevřený single-quoted blok pokračuje na dalších řádcích
                $val = substr($val, 1);
            }
            $cur['a'] = $val;
            $field = 'answer';
            continue;
        }
        // Pokračovací řádek odpovědi
        if ($cur !== null && $field === 'answer') {
            $trimmed = ltrim($line);
            // Konec single-quoted bloku
            if (str_ends_with($trimmed, "'") && !str_ends_with($trimmed, "''")) {
                $trimmed = substr($trimmed, 0, -1);
                $cur['a'] .= "\n" . str_replace("''", "'", $trimmed);
                $field = null;
                continue;
            }
            $cur['a'] .= "\n" . str_replace("''", "'", $trimmed);
            continue;
        }
        // Backwards compat: **Question?**
        if (preg_match('/^\*\*(.+?)\*\*\s*$/u', $line, $m)) {
            $flush();
            $cur = ['q' => trim($m[1]), 'a' => ''];
            $field = 'answer';
            continue;
        }
        if ($cur !== null && $field === 'answer' && trim($line) !== '') {
            $cur['a'] .= ($cur['a'] === '' ? '' : "\n") . $line;
        }
    }
    $flush();

    return $items;
}

function htmlInlineToMarkdown(string $s): string
{
    // <code>x</code> → `x` (zachováme `<` v obsahu jako `\<`)
    $s = preg_replace_callback('/<code>(.*?)<\/code>/su', static fn(array $m): string => '`' . $m[1] . '`', $s);
    // <strong>x</strong> a <b>x</b> → **x**
    $s = preg_replace('/<\/?(?:strong|b)>/u', '**', $s);
    // <em>x</em> a <i>x</i> → *x*
    $s = preg_replace('/<\/?(?:em|i)>/u', '*', $s);
    // <a href="X">label</a> → [label](X)
    $s = preg_replace_callback(
        '/<a\s+href="([^"]*)"[^>]*>(.*?)<\/a>/su',
        static fn(array $m): string => '[' . $m[2] . '](' . $m[1] . ')',
        $s,
    );
    // HTML entity, které se mohou objevit v citacích.
    $s = strtr($s, [
        '&amp;'  => '&',
        '&lt;'   => '\\<',
        '&gt;'   => '>',
        '&quot;' => '"',
        '&#39;'  => "'",
    ]);
    return $s;
}

function renderCode(array $attrs, string $body, string $target): string
{
    $lang = $attrs['lang'] ?? $attrs['language'] ?? '';

    if ($target === 'pdf') {
        // Native fenced code – pandoc předá typstu jako #raw().
        // Ošetřit potkání ``` v těle: použít delší fence.
        $fence = '```';
        while (str_contains($body, $fence)) {
            $fence .= '`';
        }
        $info = $lang !== '' ? $lang : '';
        $filename = $attrs['filename'] ?? '';
        $header = '';
        if ($filename !== '') {
            // Filename: tučný + monospace, vizuálně oddělen od kódu.
            // Backticks chrání `<placeholdery>` a uvozovky před parserem.
            $cFence = '`';
            while (str_contains($filename, $cFence)) {
                $cFence .= '`';
            }
            // Šipka „►“ je v DejaVu Serif dostupná, na rozdíl od emoji 📄.
            $header = "**► {$cFence}{$filename}{$cFence}**\n\n";
        }
        return "{$header}{$fence}{$info}\n{$body}\n{$fence}";
    }

    $class = $lang !== '' ? " class=\"language-{$lang}\"" : '';
    $escaped = escapeHtml($body);
    return "<pre><code{$class}>{$escaped}</code></pre>";
}
