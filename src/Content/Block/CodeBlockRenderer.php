<?php

declare(strict_types=1);

namespace App\Content\Block;

use Twig\Environment;

final readonly class CodeBlockRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs, string $body): string
    {
        $parsed = $this->parseAttrs($attrs);
        $params = [
            'filename' => $parsed['filename'] ?? '',
            'language' => $parsed['language'] ?? 'php',
            'code'     => $body,
        ];
        if (!empty($parsed['highlights'])) {
            $params['highlights'] = array_map('intval', explode(',', $parsed['highlights']));
        }

        return $this->twig->render('_partials/code_block.html.twig', $params);
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
