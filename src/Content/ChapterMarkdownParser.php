<?php

declare(strict_types=1);

namespace App\Content;

use App\Content\Block\CalloutRenderer;
use App\Content\Block\CodeBlockRenderer;
use App\Content\Block\DiagramRenderer;
use App\Content\Block\FaqRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Yaml\Yaml;

final class ChapterMarkdownParser
{
    private MarkdownConverter $converter;

    public function __construct(
        private readonly CalloutRenderer $callout,
        private readonly DiagramRenderer $diagram,
        private readonly CodeBlockRenderer $code,
        private readonly FaqRenderer $faq,
    ) {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new AttributesExtension());
        $env->addExtension(new TableExtension());
        $env->addRenderer(Heading::class, new ChapterHeadingRenderer());
        $this->converter = new MarkdownConverter($env);
    }

    public function parse(string $filePath): ParsedChapter
    {
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            throw new \RuntimeException("Cannot read: {$filePath}");
        }

        [$yamlStr, $markdown] = $this->splitFrontmatter($raw);
        $frontmatter = ChapterFrontmatter::fromArray(Yaml::parse($yamlStr));

        $html = $this->renderMarkdown($markdown);
        $html = $this->wrapSections($html);

        return new ParsedChapter($frontmatter, $html);
    }

    private function renderMarkdown(string $markdown): string
    {
        $blocks = [];
        $processed = $this->extractTopLevelBlocks($markdown, $blocks);

        $html = $this->converter->convert($processed)->getContent();

        foreach ($blocks as $idx => $block) {
            $rendered = $this->renderBlock($block);
            $html = str_replace("<div data-block=\"{$idx}\"></div>", $rendered, $html);
        }

        return $html;
    }

    /**
     * Depth-aware parser that extracts :::type{attrs}...body...::: blocks.
     * Handles nested ::: blocks (e.g. :::code inside :::callout).
     */
    private function extractTopLevelBlocks(string $markdown, array &$blocks): string
    {
        $lines  = explode("\n", $markdown);
        $output = [];
        $i      = 0;
        $n      = count($lines);

        while ($i < $n) {
            $line = $lines[$i];

            if (preg_match('/^:::(\w+)(?:\{([^}]*)\})?$/', $line, $m)) {
                $type      = $m[1];
                $attrs     = $m[2] ?? '';
                $depth     = 1;
                $bodyLines = [];
                $i++;

                while ($i < $n) {
                    $inner = $lines[$i];
                    if (preg_match('/^:::(\w+)/', $inner)) {
                        $depth++;
                        $bodyLines[] = $inner;
                    } elseif ($inner === ':::') {
                        $depth--;
                        if ($depth === 0) {
                            $i++;
                            break;
                        }
                        $bodyLines[] = $inner;
                    } else {
                        $bodyLines[] = $inner;
                    }
                    $i++;
                }

                $idx      = count($blocks);
                $blocks[] = ['type' => $type, 'attrs' => $attrs, 'body' => implode("\n", $bodyLines)];
                $output[] = '';
                $output[] = "<div data-block=\"{$idx}\"></div>";
                $output[] = '';
            } else {
                $output[] = $line;
                $i++;
            }
        }

        return implode("\n", $output);
    }

    private function renderBlock(array $block): string
    {
        // Blocks with a body use renderMarkdown() so nested ::: blocks are processed.
        $markdownToHtml = fn(string $md): string => $this->renderMarkdown($md);

        return match ($block['type']) {
            'callout' => $this->callout->render($block['attrs'], $block['body'], $markdownToHtml),
            'diagram' => $this->diagram->render($block['attrs']),
            'code'    => $this->code->render($block['attrs'], trim($block['body'])),
            'faq'     => $this->faq->render($block['attrs'], $block['body']),
            default   => "<!-- unknown block: {$block['type']} -->",
        };
    }

    private function wrapSections(string $html): string
    {
        // Wrap each h2 with id="X-heading" class="h-section" in <section>.
        // Only matches h2 elements rendered by ChapterHeadingRenderer (have class="h-section").
        // Skips h2 elements from block renderers (e.g. FAQ) which lack class="h-section".
        $sectionCount = 0;
        $result = preg_replace_callback(
            '/<h2 id="([^"]+)-heading" class="h-section"/',
            static function (array $m) use (&$sectionCount): string {
                $sectionCount++;
                return '</section><section id="' . $m[1] . '" aria-labelledby="' . $m[1] . '-heading">'
                    . '<h2 id="' . $m[1] . '-heading" class="h-section"';
            },
            $html,
        );

        if ($sectionCount === 0) {
            return $html;
        }

        // Remove spurious </section> injected before the first <section>
        $firstSection = strpos($result, '<section ');
        if ($firstSection !== false) {
            $before = substr($result, 0, $firstSection);
            $after  = substr($result, $firstSection);
            $before = str_replace('</section>', '', $before);
            $result = $before . $after;
        }

        return $result . '</section>';
    }

    private function splitFrontmatter(string $content): array
    {
        if (!str_starts_with($content, '---')) {
            return ['', $content];
        }
        $end = strpos($content, "\n---", 3);
        if ($end === false) {
            return ['', $content];
        }
        return [
            substr($content, 4, $end - 4),
            ltrim(substr($content, $end + 4)),
        ];
    }
}
