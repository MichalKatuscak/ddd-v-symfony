<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Sitemap\SitemapBuilder;
use PHPUnit\Framework\TestCase;

final class SitemapBuilderTest extends TestCase
{
    public function testBuildCoversChaptersHubsAndMetaPages(): void
    {
        $builder = new SitemapBuilder(dirname(__DIR__, 2));
        $urls    = $builder->build();
        $locs    = array_column($urls, 'loc');

        self::assertSame('/', $locs[0]);
        self::assertContains('/zaklady', $locs);
        self::assertContains('/co-je-ddd', $locs);
        self::assertContains('/predmluva', $locs);
        self::assertContains('/ddd-a-umela-inteligence', $locs);
        self::assertContains('/security-policy', $locs);
        self::assertSame(count($locs), count(array_unique($locs)));

        $chapterCount = count(glob(dirname(__DIR__, 2) . '/content/chapters/*.md') ?: []);
        // homepage + 7 hubů + kapitoly + 5 meta stránek
        self::assertCount(1 + 7 + $chapterCount + 5, $urls);

        foreach ($urls as $url) {
            self::assertMatchesRegularExpression('/^0\.\d$|^1\.0$/', $url['priority']);
            if ($url['lastmod'] !== null) {
                self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $url['lastmod'], $url['loc']);
            }
        }
    }
}
