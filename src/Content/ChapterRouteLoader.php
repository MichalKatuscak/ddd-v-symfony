<?php

declare(strict_types=1);

namespace App\Content;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

final class ChapterRouteLoader implements LoaderInterface
{
    private bool $loaded = false;

    public function __construct(private readonly string $projectDir) {}

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \RuntimeException('ChapterRouteLoader can only be loaded once.');
        }
        $this->loaded = true;

        $collection = new RouteCollection();
        $dir = $this->projectDir . '/content/chapters';

        if (!is_dir($dir)) {
            return $collection;
        }

        foreach (glob($dir . '/*.md') as $file) {
            $raw = file_get_contents($file);
            [$yaml] = $this->splitFrontmatter($raw);
            $data = Yaml::parse($yaml);

            $route = new Route(
                path: $data['path'],
                defaults: [
                    '_controller' => 'App\Controller\ChapterController::show',
                    '_file' => basename($file),
                ],
            );
            $collection->add($data['route'], $route);
        }

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'chapter_content';
    }

    public function getResolver(): LoaderResolverInterface
    {
        throw new \LogicException('Not used.');
    }

    public function setResolver(LoaderResolverInterface $resolver): void {}

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
