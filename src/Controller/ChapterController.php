<?php

declare(strict_types=1);

namespace App\Controller;

use App\Content\ChapterMarkdownParser;
use App\Content\ParsedChapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ChapterController extends AbstractController
{
    public function __construct(
        private readonly ChapterMarkdownParser $parser,
        private readonly CacheInterface $cache,
    ) {}

    public function show(string $_file): Response
    {
        $path = $this->getParameter('kernel.project_dir') . '/content/chapters/' . $_file;

        // Parsování + server-side highlight není zadarmo; výsledek se cachuje.
        // Klíč obsahuje mtime — editace .md souboru cache obchází sama od sebe.
        $mtime   = is_file($path) ? (int) filemtime($path) : 0;
        $chapter = $this->cache->get(
            'chapter_' . md5($_file) . '_' . $mtime,
            function (ItemInterface $item) use ($path): ParsedChapter {
                $item->expiresAfter(7 * 86400);

                return $this->parser->parse($path);
            },
        );

        return $this->render('chapter.html.twig', [
            'chapter' => $chapter,
        ]);
    }
}
