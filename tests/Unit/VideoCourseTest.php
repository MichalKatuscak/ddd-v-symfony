<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Catalog\VideoCourse;
use PHPUnit\Framework\TestCase;

final class VideoCourseTest extends TestCase
{
    public function testPublishedMomentGatesRelease(): void
    {
        self::assertFalse(VideoCourse::isPast(null));
        self::assertFalse(VideoCourse::isPast(''));
        self::assertTrue(VideoCourse::isPast('2026-01-01'));
        self::assertTrue(VideoCourse::isPast((new \DateTimeImmutable('-1 minute'))->format(\DATE_ATOM)));
        // Naplánovaný díl se ukáže až v okamžiku zveřejnění na YouTube.
        self::assertFalse(VideoCourse::isPast((new \DateTimeImmutable('+1 hour'))->format(\DATE_ATOM)));
        self::assertFalse(VideoCourse::isPast('2999-01-01'));
    }

    public function testIsoPublished(): void
    {
        self::assertNull(VideoCourse::isoPublished(null));
        self::assertSame('2026-10-01T12:00:00+02:00', VideoCourse::isoPublished('2026-10-01'));
        self::assertSame('2026-10-02T07:00:00+02:00', VideoCourse::isoPublished('2026-10-02T07:00:00+02:00'));
    }
}
