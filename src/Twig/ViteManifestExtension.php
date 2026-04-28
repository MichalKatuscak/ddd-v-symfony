<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Mapuje vstupní cesty (např. assets/fonts/inter-latin.woff2) na hashované
 * vybudované URL přes Vite manifest. Umožňuje emit <link rel="preload"> pro
 * konkrétní fonty bez závislosti na hash vygenerovaném buildem.
 */
final class ViteManifestExtension extends AbstractExtension
{
    private string $manifestPath;
    private ?array $manifest = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        string $publicDir,
    ) {
        $this->manifestPath = $publicDir . '/build/.vite/manifest.json';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_asset', [$this, 'getAssetUrl']),
            new TwigFunction('vite_font_preloads', [$this, 'getFontPreloads'], ['is_safe' => ['html']]),
        ];
    }

    public function getAssetUrl(string $sourcePath): ?string
    {
        $manifest = $this->loadManifest();
        if (!isset($manifest[$sourcePath]['file'])) {
            return null;
        }
        return '/build/' . $manifest[$sourcePath]['file'];
    }

    /**
     * Vrátí <link rel="preload"> tagy pro daný seznam font cest. Cesty mimo
     * manifest jsou tiše přeskočeny.
     *
     * @param list<string> $sourcePaths
     */
    public function getFontPreloads(array $sourcePaths): string
    {
        $tags = [];
        foreach ($sourcePaths as $path) {
            $url = $this->getAssetUrl($path);
            if ($url === null) {
                continue;
            }
            $tags[] = sprintf(
                '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>',
                htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            );
        }
        return implode("\n    ", $tags);
    }

    private function loadManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }
        if (!is_file($this->manifestPath)) {
            return $this->manifest = [];
        }
        $json = file_get_contents($this->manifestPath);
        if ($json === false) {
            return $this->manifest = [];
        }
        $data = json_decode($json, true);
        return $this->manifest = is_array($data) ? $data : [];
    }
}
