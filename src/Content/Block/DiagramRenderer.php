<?php

declare(strict_types=1);

namespace App\Content\Block;

use Twig\Environment;

final readonly class DiagramRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs): string
    {
        $parsed = $this->parseAttrs($attrs);

        return $this->twig->render('_partials/diagram.html.twig', $parsed);
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
