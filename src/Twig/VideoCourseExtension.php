<?php

declare(strict_types=1);

namespace App\Twig;

use App\Catalog\VideoCourse;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Videokurz v šablonách: ddd_video_course() pro celý katalog,
 * ddd_chapter_videos(route) pro blok s díly v kapitole a na rozcestnících.
 */
final class VideoCourseExtension extends AbstractExtension
{
    public function __construct(private readonly VideoCourse $course) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ddd_video_course',   fn(): VideoCourse => $this->course),
            new TwigFunction('ddd_chapter_videos', fn(string $route): array => $this->course->forChapter($route)),
        ];
    }
}
