<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalog\VideoCourse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class VideoCourseController extends AbstractController
{
    public function __construct(private readonly VideoCourse $course) {}

    #[Route('/videokurz', name: 'video_course')]
    public function course(): Response
    {
        return $this->render('video/course.html.twig', [
            'title'    => 'Videokurz Domain-Driven Design v Symfony',
            'course'   => $this->course,
            'trailer'  => $this->course->find('00'),
            'syllabus' => $this->course->syllabus(),
        ]);
    }

    #[Route('/videokurz/{ep}', name: 'video_episode', requirements: ['ep' => '\d\d[a-z]?'])]
    public function episode(string $ep): Response
    {
        $episode = $this->course->find($ep);
        if ($episode === null || !$episode['released']) {
            throw new NotFoundHttpException(sprintf('Díl %s videokurzu zatím není zveřejněný.', $ep));
        }

        return $this->render('video/episode.html.twig', [
            'title'      => $episode['title'],
            'course'     => $this->course,
            'e'          => $episode,
            'transcript' => $this->course->transcript($ep),
            'neighbors'  => $this->course->neighbors($ep),
        ]);
    }
}
