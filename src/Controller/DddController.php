<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DddController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('ddd/index.html.twig', [
            'title' => 'Architektura DDD v Symfony 7',
        ]);
    }

    #[Route('/co-je-ddd', name: 'what_is_ddd')]
    public function whatIsDdd(): Response
    {
        return $this->render('ddd/what_is_ddd.html.twig', [
            'title' => 'Co je Domain-Driven Design?',
        ]);
    }

    #[Route('/horizontalni-vs-vertikalni', name: 'horizontal_vs_vertical')]
    public function horizontalVsVertical(): Response
    {
        return $this->render('ddd/horizontal_vs_vertical.html.twig', [
            'title' => 'Horizontální vs. Vertikální DDD',
        ]);
    }

    #[Route('/zakladni-koncepty', name: 'basic_concepts')]
    public function basicConcepts(): Response
    {
        return $this->render('ddd/basic_concepts.html.twig', [
            'title' => 'Základní koncepty DDD',
        ]);
    }

    #[Route('/implementace-v-symfony', name: 'implementation_in_symfony')]
    public function implementationInSymfony(): Response
    {
        return $this->render('ddd/implementation_in_symfony.html.twig', [
            'title' => 'Implementace DDD v Symfony 7',
        ]);
    }

    #[Route('/cqrs', name: 'cqrs')]
    public function cqrs(): Response
    {
        return $this->render('ddd/cqrs.html.twig', [
            'title' => 'CQRS v Symfony 7',
        ]);
    }

    #[Route('/prakticke-priklady', name: 'practical_examples')]
    public function practicalExamples(): Response
    {
        return $this->render('ddd/practical_examples.html.twig', [
            'title' => 'Praktické příklady',
        ]);
    }

    #[Route('/pripadova-studie', name: 'case_study')]
    public function caseStudy(): Response
    {
        return $this->render('ddd/case_study.html.twig', [
            'title' => 'Případová studie',
        ]);
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
}
