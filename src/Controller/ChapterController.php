<?php

declare(strict_types=1);

namespace App\Controller;

use App\Content\ChapterMarkdownParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ChapterController extends AbstractController
{
    public function __construct(private readonly ChapterMarkdownParser $parser) {}

    public function show(string $_file): Response
    {
        $path = $this->getParameter('kernel.project_dir') . '/content/chapters/' . $_file;
        $chapter = $this->parser->parse($path);

        return $this->render('chapter.html.twig', [
            'chapter' => $chapter,
        ]);
    }
}
