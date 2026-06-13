<?php

declare(strict_types=1);

namespace App\Twig;

use App\Catalog\Chapters;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ChaptersExtension extends AbstractExtension
{
    /** Český nominativ názvů měsíců, index 1–12. */
    private const MONTHS_CS = [
        1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen',
        'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec',
    ];

    /** Memoizace v rámci requestu – skenuje se jen jednou. */
    private ?string $lastModified = null;

    public function __construct(private readonly string $projectDir) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ddd_chapters',          static fn(): array => Chapters::all()),
            new TwigFunction('ddd_extras',            static fn(): array => Chapters::extras()),
            new TwigFunction('ddd_chapters_by_group', static fn(string $group): array => Chapters::byGroup($group)),
            new TwigFunction('ddd_chapter_neighbors', static fn(string $route): array => Chapters::neighbors($route)),
            new TwigFunction('ddd_last_modified',     fn(): string => $this->lastModified()),
        ];
    }

    /**
     * Nejnovější `modified` napříč kapitolami, formátováno jako „měsíc rok"
     * (např. „červen 2026"). Zdrojem je frontmatter .md souborů.
     */
    private function lastModified(): string
    {
        if ($this->lastModified !== null) {
            return $this->lastModified;
        }

        $latest = null;
        foreach (glob($this->projectDir . '/content/chapters/*.md') ?: [] as $file) {
            $raw = file_get_contents($file);
            if ($raw === false) {
                continue;
            }
            if (preg_match('/^modified:\s*"?(\d{4})-(\d{2})-(\d{2})"?/m', $raw, $m)) {
                $date = $m[1] . $m[2] . $m[3]; // YYYYMMDD pro lexikografické porovnání
                if ($latest === null || $date > $latest) {
                    $latest = $date;
                }
            }
        }

        if ($latest === null) {
            return $this->lastModified = '';
        }

        $month = (int) substr($latest, 4, 2);
        $year  = substr($latest, 0, 4);

        return $this->lastModified = self::MONTHS_CS[$month] . ' ' . $year;
    }
}
