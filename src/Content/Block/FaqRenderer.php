<?php

declare(strict_types=1);

namespace App\Content\Block;

use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

final readonly class FaqRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs, string $body): string
    {
        $parsed = $this->parseAttrs($attrs);
        $items = Yaml::parse(trim($body));

        $params = ['items' => $items];
        if (!empty($parsed['heading'])) {
            $params['heading'] = $parsed['heading'];
        }

        return $this->twig->render('_partials/faq.html.twig', $params);
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
