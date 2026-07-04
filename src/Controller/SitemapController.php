<?php

declare(strict_types=1);

namespace App\Controller;

use App\Sitemap\SitemapBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SitemapController extends AbstractController
{
    public function __construct(
        private readonly SitemapBuilder $builder,
        private readonly CacheInterface $cache,
    ) {}

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $urls = $this->cache->get('sitemap_v1', function (ItemInterface $item): array {
            $item->expiresAfter(86400);

            return $this->builder->build();
        });

        $base = $request->getSchemeAndHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>' . htmlspecialchars($base . $url['loc'], ENT_XML1) . "</loc>\n";
            if ($url['lastmod'] !== null) {
                $xml .= '        <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1) . "</lastmod>\n";
            }
            $xml .= '        <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xml .= '        <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "    </url>\n";
        }
        $xml .= '</urlset>' . "\n";

        $response = new Response($xml, Response::HTTP_OK, ['Content-Type' => 'application/xml; charset=UTF-8']);
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
