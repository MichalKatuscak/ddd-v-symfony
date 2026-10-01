<?php

declare(strict_types=1);

namespace App\Catalog;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Videokurz ke knize: katalog dílů z content/videokurz.yaml (generuje
 * video/scripts/web-katalog.mjs) a přepisy z content/videokurz/prepisy/.
 *
 * Díl je „vydaný“, jakmile má YouTube ID i datum zveřejnění. Nevydané díly
 * web ukazuje jen v osnově kurzu jako připravované – bez přehrávače a bez
 * vlastní stránky (soukromé video by se v přehrávači nespustilo).
 *
 * @phpstan-type Moment array{t:int, label:string, title:string}
 * @phpstan-type Episode array{
 *     ep:string, chapter:string, title:string, lead:?string, sections:?string,
 *     duration:?int, durationLabel:?string, isoDuration:?string,
 *     youtube:?string, published:?string, publishedIso:?string, released:bool,
 *     moments:list<Moment>, chapterN:?string, chapterTitle:?string, group:string
 * }
 */
final class VideoCourse
{
    /** Části kurzu = hub skupiny knihy, v pořadí osnovy. */
    public const GROUPS = [
        'preface'      => ['title' => 'Úvod ke kurzu',                    'hub' => null],
        'basics'       => ['title' => 'Úvod a strategie',                 'hub' => 'hub_basics'],
        'tactics'      => ['title' => 'Taktické modelování',              'hub' => 'hub_tactics'],
        'architecture' => ['title' => 'Architektura a implementace',      'hub' => 'hub_architecture'],
        'patterns'     => ['title' => 'Pokročilé vzory a infrastruktura', 'hub' => 'hub_patterns'],
        'practice'     => ['title' => 'Praxe a provoz',                   'hub' => 'hub_practice'],
        'synthesis'    => ['title' => 'Syntéza',                          'hub' => 'hub_synthesis'],
    ];

    /** @var array{playlist:string, channel:string, episodes:list<Episode>}|null */
    private ?array $data = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    public function playlistUrl(): string
    {
        return 'https://www.youtube.com/playlist?list=' . $this->load()['playlist'];
    }

    public function channelUrl(): string
    {
        return $this->load()['channel'];
    }

    /** @return list<Episode> */
    public function all(): array
    {
        return $this->load()['episodes'];
    }

    /** @return list<Episode> */
    public function released(): array
    {
        return array_values(array_filter($this->all(), static fn(array $e): bool => $e['released']));
    }

    /** @return Episode|null */
    public function find(string $ep): ?array
    {
        foreach ($this->all() as $e) {
            if ($e['ep'] === $ep) {
                return $e;
            }
        }

        return null;
    }

    /**
     * Díly ke kapitole knihy (a, b…). Prázdné pole, když kapitola nemá
     * žádný vydaný díl – blok v kapitole se pak vůbec neukáže.
     *
     * @return list<Episode>
     */
    public function forChapter(string $route): array
    {
        $episodes = array_values(array_filter($this->all(), static fn(array $e): bool => $e['chapter'] === $route));
        foreach ($episodes as $e) {
            if ($e['released']) {
                return $episodes;
            }
        }

        return [];
    }

    /**
     * Osnova po částech knihy.
     *
     * @return list<array{key:string, title:string, hub:?string, episodes:list<Episode>}>
     */
    public function syllabus(): array
    {
        $parts = [];
        foreach (self::GROUPS as $key => $g) {
            $episodes = array_values(array_filter($this->all(), static fn(array $e): bool => $e['group'] === $key));
            if ($episodes !== []) {
                $parts[] = ['key' => $key, 'title' => $g['title'], 'hub' => $g['hub'], 'episodes' => $episodes];
            }
        }

        return $parts;
    }

    /**
     * Předchozí a další vydaný díl.
     *
     * @return array{prev:?array, next:?array}
     */
    public function neighbors(string $ep): array
    {
        $released = $this->released();
        foreach ($released as $i => $e) {
            if ($e['ep'] === $ep) {
                return ['prev' => $released[$i - 1] ?? null, 'next' => $released[$i + 1] ?? null];
            }
        }

        return ['prev' => null, 'next' => null];
    }

    /** Součet délek vydaných dílů v sekundách. */
    public function releasedDuration(): int
    {
        return array_sum(array_map(static fn(array $e): int => $e['duration'] ?? 0, $this->released()));
    }

    /**
     * Přepis dílu po kapitolách videa (komentář ze scénáře; autor ho namlouvá doslova).
     *
     * @return list<array{t:int, label:string, title:string, paragraphs:list<string>}>
     */
    public function transcript(string $ep): array
    {
        $file = $this->projectDir . '/content/videokurz/prepisy/' . $ep . '.md';
        if (!preg_match('/^\d\d[a-z]?$/', $ep) || !is_file($file)) {
            return [];
        }

        $sections = [];
        $current = null;
        foreach (preg_split('/\n{2,}/', trim((string) file_get_contents($file))) ?: [] as $block) {
            if (preg_match('/^## (\d+) (.+)$/', $block, $m)) {
                if ($current !== null) {
                    $sections[] = $current;
                }
                $current = ['t' => (int) $m[1], 'label' => self::clock((int) $m[1]), 'title' => $m[2], 'paragraphs' => []];
            } elseif ($current !== null) {
                $current['paragraphs'][] = $block;
            }
        }
        if ($current !== null) {
            $sections[] = $current;
        }

        return $sections;
    }

    /**
     * Je okamžik zveřejnění už za námi? `published` je datum (YYYY-MM-DD, půlnoc
     * pražského času) nebo datum s časem (2026-10-02T07:00:00+02:00) – díl
     * naplánovaný na YouTube se na webu ukáže sám ve stejnou chvíli.
     */
    public static function isPast(mixed $published): bool
    {
        if ($published === null || $published === '') {
            return false;
        }
        if (is_int($published)) { // YAML převádí neuvozovkované datum na timestamp
            return $published <= time();
        }
        $at = date_create_immutable((string) $published, new \DateTimeZone('Europe/Prague'));

        return $at !== false && $at <= new \DateTimeImmutable('now');
    }

    /** ISO 8601 okamžik zveřejnění pro schema.org (datum bez času = poledne). */
    public static function isoPublished(?string $published): ?string
    {
        if ($published === null || $published === '') {
            return null;
        }
        $hasTime = str_contains($published, 'T');
        $at = date_create_immutable($hasTime ? $published : $published . ' 12:00', new \DateTimeZone('Europe/Prague'));

        return $at !== false ? $at->format(\DATE_ATOM) : null;
    }

    /** „7:47“ z počtu sekund. */
    public static function clock(int $seconds): string
    {
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    /** ISO 8601 délka pro schema.org („PT7M47S“). */
    public static function isoDuration(int $seconds): string
    {
        return sprintf('PT%dM%dS', intdiv($seconds, 60), $seconds % 60);
    }

    /** @return array{playlist:string, channel:string, episodes:list<Episode>} */
    private function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $raw = Yaml::parseFile($this->projectDir . '/content/videokurz.yaml');
        $chapters = [];
        foreach (Chapters::all() as $c) {
            $chapters[$c['route']] = $c;
        }

        $episodes = [];
        foreach ($raw['episodes'] as $e) {
            $chapter = $chapters[$e['chapter']] ?? null;
            $duration = isset($e['duration']) ? (int) $e['duration'] : null;
            $episodes[] = [
                'ep'            => (string) $e['ep'],
                'chapter'       => (string) $e['chapter'],
                'title'         => (string) $e['title'],
                'lead'          => $e['lead'] ?? null,
                'sections'      => $e['sections'] ?? null,
                'duration'      => $duration,
                'durationLabel' => $duration !== null ? self::clock($duration) : null,
                'isoDuration'   => $duration !== null ? self::isoDuration($duration) : null,
                'youtube'       => $e['youtube'] ?? null,
                'published'     => isset($e['published']) ? (string) $e['published'] : null,
                'publishedIso'  => self::isoPublished(isset($e['published']) ? (string) $e['published'] : null),
                'released'      => !empty($e['youtube']) && self::isPast($e['published'] ?? null),
                'moments'       => array_map(
                    static fn(array $m): array => ['t' => (int) $m['t'], 'label' => self::clock((int) $m['t']), 'title' => (string) $m['title']],
                    $e['chapters'] ?? [],
                ),
                'chapterN'      => $chapter['n'] ?? null,
                'chapterTitle'  => $chapter['t'] ?? null,
                'group'         => $chapter['group'] ?? 'preface',
            ];
        }

        return $this->data = [
            'playlist' => (string) $raw['playlist'],
            'channel'  => (string) $raw['channel'],
            'episodes' => $episodes,
        ];
    }
}
