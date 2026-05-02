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

        // Pre-process :::type{attrs}\nbody\n::: blocks
        $processed = preg_replace_callback(
            '/^:::(\w+)(?:\{([^}]*)\})?\n(.*?)^:::/ms',
            function (array $matches) use (&$blocks): string {
                $idx = count($blocks);
                $blocks[] = ['type' => $matches[1], 'attrs' => $matches[2] ?? '', 'body' => $matches[3]];
                // Block-level HTML div: CommonMark won't wrap it in <p>
                return "\n\n<div data-block=\"{$idx}\"></div>\n\n";
            },
            $markdown,
        );

        $html = $this->converter->convert($processed)->getContent();

        foreach ($blocks as $idx => $block) {
            $rendered = $this->renderBlock($block);
            $html = str_replace("<div data-block=\"{$idx}\"></div>", $rendered, $html);
        }

        return $html;
    }

    private function renderBlock(array $block): string
    {
        $markdownToHtml = fn(string $md): string => $this->converter->convert($md)->getContent();

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
        // Wrap each h2 with id="X-heading" in <section id="X" aria-labelledby="X-heading">
        $sectionCount = 0;
        $result = preg_replace_callback(
            '/<h2 id="([^"]+)-heading"/',
            static function (array $m) use (&$sectionCount): string {
                $sectionCount++;
                return '</section><section id="' . $m[1] . '" aria-labelledby="' . $m[1] . '-heading">'
                    . '<h2 id="' . $m[1] . '-heading"';
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
