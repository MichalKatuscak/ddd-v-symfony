<?php

declare(strict_types=1);

namespace App\Content;

use Highlight\Highlighter;

/**
 * Server-side zvýraznění syntaxe přes scrivo/highlight.php (port highlight.js).
 * Produkuje stejné hljs-* třídy jako dřívější klientský highlight.js, takže
 * hljs-theme.css zůstává beze změny. Výstup zároveň dělí kód na řádky
 * (.ln > .ln-num + .ln-text) – dřív to za běhu dělal code-block.js.
 */
final class CodeHighlighter
{
    /** Stejné aliasy, jaké registroval klientský highlight.js v app.js. */
    private const ALIASES = [
        'html'  => 'xml',
        'twig'  => 'xml',
        'shell' => 'bash',
        'text'  => 'plaintext',
    ];

    private Highlighter $highlighter;

    public function __construct()
    {
        $this->highlighter = new Highlighter();
    }

    /**
     * @param list<int> $highlightLines 1-based čísla řádků se zvýrazněním
     */
    public function highlight(string $code, string $language, array $highlightLines = []): string
    {
        $lang = self::ALIASES[$language] ?? $language;

        try {
            $value = $this->highlighter->highlight($lang, $code)->value;
        } catch (\Throwable) {
            // Neznámý jazyk – kód se vypíše bez zvýraznění, jen escapovaný.
            $value = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $lines = $this->splitBalancedLines($value);

        // Trailing prázdné řádky nezobrazujeme (parita s dřívějším JS chováním).
        while (count($lines) > 1 && trim(strip_tags(end($lines))) === '') {
            array_pop($lines);
        }

        $out = '';
        foreach ($lines as $i => $line) {
            $num  = $i + 1;
            $hl   = in_array($num, $highlightLines, true) ? ' ln-hl' : '';
            $text = trim(strip_tags($line)) === '' ? '&nbsp;' : $line;
            $out .= '<span class="ln' . $hl . '">'
                . '<span class="ln-num">' . $num . '</span>'
                . '<span class="ln-text">' . $text . '</span>'
                . '</span>';
        }

        return $out;
    }

    /**
     * Rozdělí HTML z highlighteru na řádky tak, aby každý řádek byl sám o sobě
     * validní: spany otevřené přes víc řádků (např. víceřádkový komentář) se
     * na konci řádku uzavřou a na dalším znovu otevřou.
     *
     * @return list<string>
     */
    private function splitBalancedLines(string $html): array
    {
        $result = [];
        $open   = [];

        foreach (explode("\n", $html) as $line) {
            $prefix = implode('', $open);

            preg_match_all('/<span[^>]*>|<\/span>/', $line, $m);
            foreach ($m[0] as $tag) {
                if ($tag === '</span>') {
                    array_pop($open);
                } else {
                    $open[] = $tag;
                }
            }

            $result[] = $prefix . $line . str_repeat('</span>', count($open));
        }

        return $result;
    }
}
