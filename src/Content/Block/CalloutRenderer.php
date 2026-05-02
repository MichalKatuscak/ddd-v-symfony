<?php

declare(strict_types=1);

namespace App\Content\Block;

use Twig\Environment;

final readonly class CalloutRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs, string $bodyMarkdown, callable $markdownToHtml): string
    {
        $parsed = $this->parseAttrs($attrs);
        $type = $parsed['type'] ?? 'note';
        $label = $parsed['label'] ?? null;
        $bodyHtml = $markdownToHtml($bodyMarkdown);

        $params = ['type' => $type, 'body' => $bodyHtml];
        if ($label !== null) {
            $params['label'] = $label;
        }

        return $this->twig->render('_partials/callout.html.twig', $params);
    }

    private function parseAttrs(string $attrs): array
    {
        preg_match_all('/(\w+)="([^"]*)"/', $attrs, $m, PREG_SET_ORDER);
        $result = [];
        foreach ($m as $match) {
            $result[$match[1]] = $match[2];
        }
        return $result;
    }
}
