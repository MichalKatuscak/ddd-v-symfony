<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Catalog\VideoCourse;
use App\Twig\ChaptersExtension;
use PHPUnit\Framework\TestCase;

final class ChaptersExtensionTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = (getenv('TMPDIR') ?: sys_get_temp_dir()) . '/chext_' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/content/chapters', 0777, true);
        file_put_contents($this->dir . '/content/chapters/a.md', "---\nmodified: \"2026-09-15\"\n---\ntext\n");
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/content/*.*') ?: []);
        array_map('unlink', glob($this->dir . '/content/chapters/*') ?: []);
        rmdir($this->dir . '/content/chapters');
        rmdir($this->dir . '/content');
        rmdir($this->dir);
    }

    public function testReleasedEpisodeNewerThanChaptersWins(): void
    {
        self::assertSame('listopad 2026', $this->render([
            ['2026-11-02', 'abc'],
            ['2999-01-01', 'future'],
        ]));
    }

    public function testFutureAndUnreleasedEpisodesAreIgnored(): void
    {
        self::assertSame('září 2026', $this->render([
            ['2999-01-01', 'future'],
            ['2026-11-02', null],
        ]));
    }

    /** @param list<array{string, ?string}> $episodes [published, youtube] */
    private function render(array $episodes): string
    {
        $yaml = "playlist: p\nchannel: c\nepisodes:\n";
        foreach ($episodes as $i => [$published, $youtube]) {
            $yaml .= "  - ep: \"0$i\"\n    chapter: \"preface\"\n    title: \"T\"\n    published: \"$published\"\n";
            $yaml .= $youtube !== null ? "    youtube: \"$youtube\"\n" : '';
        }
        file_put_contents($this->dir . '/content/videokurz.yaml', $yaml);

        $ext = new ChaptersExtension($this->dir, new VideoCourse($this->dir));
        foreach ($ext->getFunctions() as $fn) {
            if ($fn->getName() === 'ddd_last_modified') {
                return ($fn->getCallable())();
            }
        }
        self::fail('function missing');
    }
}
