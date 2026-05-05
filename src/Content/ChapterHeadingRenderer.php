<?php

declare(strict_types=1);

namespace App\Content;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class ChapterHeadingRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string
    {
        assert($node instanceof Heading);

        $level = $node->getLevel();
        $attrs = $node->data->get('attributes', []);
        $innerHtml = $childRenderer->renderNodes($node->children());

        if ($level !== 2 || empty($attrs['id'])) {
            $tag = "h{$level}";
            $attrStr = $this->renderAttrs(array_diff_key($attrs, ['id' => '']));
            if (!empty($attrs['id'])) {
                $attrStr = 'id="' . htmlspecialchars($attrs['id']) . '"' . ($attrStr ? ' ' . $attrStr : '');
            }
            return "<{$tag}" . ($attrStr ? " {$attrStr}" : '') . ">{$innerHtml}</{$tag}>\n";
        }

        $sectionId = $attrs['id'];
        $headingId = $sectionId . '-heading';

        // Extract prefix like "01.01", "P.01" or "cs.01"
        $text = $innerHtml;
        if (preg_match('/^([A-Za-z\d]+\.\d+)\s+(.+)$/s', $innerHtml, $m)) {
            $text = '<span class="h-num">' . $m[1] . '</span> ' . $m[2];
        }

        return '<h2 id="' . $headingId . '" class="h-section">' . $text . "</h2>\n";
    }

    private function renderAttrs(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $k => $v) {
            $parts[] = htmlspecialchars($k) . '="' . htmlspecialchars((string) $v) . '"';
        }
        return implode(' ', $parts);
    }
}
