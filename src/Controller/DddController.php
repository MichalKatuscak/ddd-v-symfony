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
        return $this->redirectToRoute('horizontal_vs_vertical', [], 301);
    }

    #[Route('/implementace-v-symfony', name: 'implementation_in_symfony')]
    public function implementationInSymfony(): Response
    {
        return $this->render('ddd/implementation_in_symfony.html.twig', [
            'title' => 'Implementace DDD v Symfony 8',
        ]);
    }

    #[Route('/cqrs', name: 'cqrs')]
    public function cqrs(): Response
    {
        return $this->render('ddd/cqrs.html.twig', [
            'title' => 'CQRS v Symfony 8',
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

    #[Route('/testovani-ddd', name: 'testing_ddd')]
    public function testingDdd(): Response
    {
        return $this->render('ddd/testing_ddd.html.twig', [
            'title' => 'Testování DDD kódu v Symfony',
        ]);
    }

    #[Route('/event-sourcing', name: 'event_sourcing')]
    public function eventSourcing(): Response
    {
        return $this->render('ddd/event_sourcing.html.twig', [
            'title' => 'Event Sourcing v DDD a Symfony',
        ]);
    }

    #[Route('/sagy-a-process-managery', name: 'sagas')]
    public function sagas(): Response
    {
        return $this->render('ddd/sagas.html.twig', [
            'title' => 'Ságy a Process Managery',
        ]);
    }

    #[Route('/anti-vzory', name: 'anti_patterns')]
    public function antiPatterns(): Response
    {
        return $this->render('ddd/anti_patterns.html.twig', [
            'title' => 'Anti-vzory a typické chyby v DDD',
        ]);
    }

    #[Route('/vykonnostni-aspekty', name: 'performance_aspects')]
    public function performanceAspects(): Response
    {
        return $this->render('ddd/performance_aspects.html.twig', [
            'title' => 'Výkonnostní aspekty DDD v Symfony',
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

    #[Route('/context-mapping', name: 'context_mapping')]
    public function contextMapping(): Response
    {
        return $this->render('ddd/context_mapping.html.twig', [
            'title' => 'Context Mapping – vztahy mezi Bounded Contexts',
        ]);
    }

    #[Route('/architektonicke-styly', name: 'architectural_styles')]
    public function architecturalStyles(): Response
    {
        return $this->render('ddd/architectural_styles.html.twig', [
            'title' => 'Architektonické styly: Hexagonal, Onion, Clean',
        ]);
    }

    #[Route('/outbox-pattern', name: 'outbox_pattern')]
    public function outboxPattern(): Response
    {
        return $this->render('ddd/outbox_pattern.html.twig', [
            'title' => 'Outbox Pattern – spolehlivé publikování doménových eventů',
        ]);
    }

    #[Route('/mene-zname-vzory', name: 'lesser_known_patterns')]
    public function lesserKnownPatterns(): Response
    {
        return $this->render('ddd/lesser_known_patterns.html.twig', [
            'title' => 'Méně známé taktické vzory: Specifications, Domain Services, Factories, Modules',
        ]);
    }

    #[Route('/event-storming', name: 'event_storming')]
    public function eventStorming(): Response
    {
        return $this->render('ddd/event_storming.html.twig', [
            'title' => 'Event Storming a Domain Storytelling',
        ]);
    }

    #[Route('/autorizace-v-ddd', name: 'authorization_in_ddd')]
    public function authorizationInDdd(): Response
    {
        return $this->render('ddd/authorization_in_ddd.html.twig', [
            'title' => 'Autorizace v DDD na Symfony',
        ]);
    }

    #[Route('/ddd-a-microservices', name: 'microservices_and_ddd')]
    public function microservicesAndDdd(): Response
    {
        return $this->render('ddd/microservices_and_ddd.html.twig', [
            'title' => 'DDD a microservices – Bounded Context jako service boundary',
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
