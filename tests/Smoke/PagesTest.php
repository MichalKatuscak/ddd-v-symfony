<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke testy: každá veřejná stránka vrací 200 a vypadá jako HTML stránka.
 * Kapitoly se berou z content/chapters/*.md, takže nová kapitola je pokrytá
 * automaticky.
 */
final class PagesTest extends WebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function staticPaths(): iterable
    {
        $paths = [
            '/',
            '/zaklady', '/takticke-vzory', '/architektura', '/vzory', '/praxe', '/synteza', '/reference',
            '/glosar', '/cheat-sheet', '/zdroje', '/o-autorovi', '/security-policy',
        ];
        foreach ($paths as $path) {
            yield $path => [$path];
        }
    }

    /** @return iterable<string, array{string}> */
    public static function chapterPaths(): iterable
    {
        foreach (self::chapterPathsFromContent() as $path) {
            yield $path => [$path];
        }
    }

    #[DataProvider('staticPaths')]
    #[DataProvider('chapterPaths')]
    public function testPageRenders(string $path): void
    {
        $client = self::createClient();
        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('main#content');
    }

    public function testUnknownPathReturns404(): void
    {
        $client = self::createClient();
        $client->request('GET', '/tahle-stranka-neexistuje');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLegacyUrlsRedirectPermanently(): void
    {
        $client = self::createClient();
        foreach (['/strategie', '/vertikalni-slice', '/horizontalni-vs-vertikalni'] as $legacy) {
            $client->request('GET', $legacy);
            self::assertResponseStatusCodeSame(301, $legacy);
        }
    }

    public function testSearchIndexIsValidJson(): void
    {
        $client = self::createClient();
        $client->request('GET', '/search-index.json');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertNotEmpty($data);
    }

    public function testSitemapContainsEveryChapterAndNoRedirects(): void
    {
        $client = self::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $client->getResponse()->getContent();
        $doc = simplexml_load_string($xml);
        self::assertNotFalse($doc, 'sitemap.xml není validní XML');

        $locs = [];
        foreach ($doc->url as $url) {
            $locs[] = parse_url((string) $url->loc, PHP_URL_PATH) ?? '/';
        }

        self::assertSame(count($locs), count(array_unique($locs)), 'sitemapa obsahuje duplicitní URL');

        foreach (self::chapterPathsFromContent() as $path) {
            self::assertContains($path, $locs, "kapitola {$path} chybí v sitemapě");
        }

        // 301 redirecty do sitemapy nepatří.
        foreach (['/strategie', '/vertikalni-slice', '/horizontalni-vs-vertikalni'] as $legacy) {
            self::assertNotContains($legacy, $locs);
        }
    }

    /** @return list<string> */
    private static function chapterPathsFromContent(): array
    {
        $paths = [];
        foreach (glob(dirname(__DIR__, 2) . '/content/chapters/*.md') ?: [] as $file) {
            $raw = (string) file_get_contents($file);
            if (preg_match('/^path:\s*"?(\/[^"\s]*)"?/m', $raw, $m)) {
                $paths[] = $m[1];
            }
        }

        return $paths;
    }
}
