<?php

namespace App\Controller;

use App\Catalog\Chapters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DddController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('ddd/index.html.twig', [
            'title' => 'Architektura DDD v Symfony 8',
            'chapters' => Chapters::all(),
            'extras' => Chapters::extras(),
        ]);
    }

    #[Route('/zaklady', name: 'hub_basics')]
    public function hubBasics(): Response
    {
        return $this->render('ddd/hub_basics.html.twig', [
            'title' => 'Základy DDD – rozcestník',
            'hub_chapters' => Chapters::byGroup('basics'),
        ]);
    }

    #[Route('/strategie', name: 'hub_strategic_redirect')]
    public function hubStrategicRedirect(): Response
    {
        return $this->redirectToRoute('hub_basics', [], 301);
    }

    #[Route('/takticke-vzory', name: 'hub_tactics')]
    public function hubTactics(): Response
    {
        return $this->render('ddd/hub_tactics.html.twig', [
            'title' => 'Taktické modelování – rozcestník',
            'hub_chapters' => Chapters::byGroup('tactics'),
        ]);
    }

    #[Route('/architektura', name: 'hub_architecture')]
    public function hubArchitecture(): Response
    {
        return $this->render('ddd/hub_architecture.html.twig', [
            'title' => 'Architektura a implementace – rozcestník',
            'hub_chapters' => Chapters::byGroup('architecture'),
        ]);
    }

    #[Route('/vzory', name: 'hub_patterns')]
    public function hubPatterns(): Response
    {
        return $this->render('ddd/hub_patterns.html.twig', [
            'title' => 'Pokročilé vzory a infrastruktura – rozcestník',
            'hub_chapters' => Chapters::byGroup('patterns'),
        ]);
    }

    #[Route('/praxe', name: 'hub_practice')]
    public function hubPractice(): Response
    {
        return $this->render('ddd/hub_practice.html.twig', [
            'title' => 'Praxe a provoz – rozcestník',
            'hub_chapters' => Chapters::byGroup('practice'),
        ]);
    }

    #[Route('/synteza', name: 'hub_synthesis')]
    public function hubSynthesis(): Response
    {
        return $this->render('ddd/hub_synthesis.html.twig', [
            'title' => 'Syntéza – rozcestník',
            'hub_chapters' => Chapters::byGroup('synthesis'),
        ]);
    }

    #[Route('/reference', name: 'hub_reference')]
    public function hubReference(): Response
    {
        return $this->render('ddd/hub_reference.html.twig', [
            'title' => 'Reference DDD – rozcestník',
            'hub_chapters' => [],
            'extras' => Chapters::extras(),
        ]);
    }

    #[Route('/horizontalni-vs-vertikalni', name: 'horizontal_vs_vertical_redirect')]
    public function horizontalVsVerticalRedirect(): Response
    {
        return $this->redirectToRoute('architectural_styles', [], 301);
    }

    #[Route('/vertikalni-slice', name: 'vertical_slice_redirect')]
    public function verticalSliceRedirect(): Response
    {
        return $this->redirectToRoute('architectural_styles', [], 301);
    }

    #[Route('/zdroje', name: 'resources')]
    public function resources(): Response
    {
        return $this->render('ddd/resources.html.twig', [
            'title' => 'Zdroje a další četba',
        ]);
    }

    #[Route('/security-policy', name: 'security_policy')]
    public function securityPolicy(): Response
    {
        return $this->render('ddd/security_policy.html.twig', [
            'title' => 'Bezpečnostní zásady',
        ]);
    }

    #[Route('/glosar', name: 'glossary')]
    public function glossary(): Response
    {
        return $this->render('ddd/glossary.html.twig', [
            'title' => 'Glosář DDD terminologie',
        ]);
    }

    #[Route('/o-autorovi', name: 'about')]
    public function about(): Response
    {
        return $this->render('ddd/about.html.twig', [
            'title' => 'O autorovi',
        ]);
    }

    #[Route('/cheat-sheet', name: 'cheat_sheet')]
    public function cheatSheet(): Response
    {
        return $this->render('ddd/cheat_sheet.html.twig', [
            'title' => 'DDD Cheat Sheet',
        ]);
    }
}
