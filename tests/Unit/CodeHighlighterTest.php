<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Content\CodeHighlighter;
use PHPUnit\Framework\TestCase;

final class CodeHighlighterTest extends TestCase
{
    private CodeHighlighter $highlighter;

    protected function setUp(): void
    {
        $this->highlighter = new CodeHighlighter();
    }

    public function testHighlightsPhpKeywords(): void
    {
        $html = $this->highlighter->highlight("<?php\nreturn true;", 'php');

        self::assertStringContainsString('hljs-keyword', $html);
        self::assertStringContainsString('<span class="ln">', $html);
    }

    public function testEveryLineHasBalancedSpans(): void
    {
        // Víceřádkový komentář — hljs ho obalí jedním spanem přes více řádků,
        // po rozdělení musí být každý řádek sám o sobě vyvážený.
        $code = "<?php\n/**\n * Víceřádkový\n * komentář\n */\nreturn 1;";
        $html = $this->highlighter->highlight($code, 'php');

        foreach (explode('<span class="ln">', $html) as $chunk) {
            if ($chunk === '') {
                continue;
            }
            // Chunk vznikl odříznutím otevíracího <span class="ln">, takže
            // closing tagů musí být přesně o jeden víc než otevíracích.
            self::assertSame(
                substr_count($chunk, '<span') + 1,
                substr_count($chunk, '</span>'),
                "Nevyvážené spany v řádku: {$chunk}",
            );
        }
    }

    public function testMarksHighlightedLines(): void
    {
        $html = $this->highlighter->highlight("a\nb\nc", 'plaintext', [2]);

        self::assertSame(1, substr_count($html, 'ln ln-hl'));
        self::assertStringContainsString('<span class="ln-num">2</span>', $html);
    }

    public function testUnknownLanguageFallsBackToEscapedText(): void
    {
        $html = $this->highlighter->highlight('<b>tag</b>', 'neexistujici-jazyk');

        self::assertStringNotContainsString('<b>', $html);
        self::assertStringContainsString('&lt;b&gt;', $html);
    }

    public function testAliasesMatchClientSideRegistration(): void
    {
        foreach (['twig', 'html', 'shell', 'text'] as $alias) {
            $html = $this->highlighter->highlight('x', $alias);
            self::assertStringContainsString('<span class="ln">', $html, "alias {$alias}");
        }
    }

    public function testEmptyLinesRenderAsNbsp(): void
    {
        $html = $this->highlighter->highlight("a\n\nb", 'plaintext');

        self::assertStringContainsString('<span class="ln-text">&nbsp;</span>', $html);
    }
}
