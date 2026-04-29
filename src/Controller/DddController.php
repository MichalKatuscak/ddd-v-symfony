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
            'title' => 'Základy DDD — rozcestník',
            'hub_chapters' => Chapters::byGroup('basics'),
        ]);
    }

    #[Route('/strategie', name: 'hub_strategic')]
    public function hubStrategic(): Response
    {
        return $this->render('ddd/hub_strategic.html.twig', [
            'title' => 'Strategický DDD — rozcestník',
            'hub_chapters' => Chapters::byGroup('strategic'),
        ]);
    }

    #[Route('/vzory', name: 'hub_patterns')]
    public function hubPatterns(): Response
    {
        return $this->render('ddd/hub_patterns.html.twig', [
            'title' => 'Taktické vzory DDD — rozcestník',
            'hub_chapters' => Chapters::byGroup('patterns'),
        ]);
    }

    #[Route('/praxe', name: 'hub_practice')]
    public function hubPractice(): Response
    {
        return $this->render('ddd/hub_practice.html.twig', [
            'title' => 'DDD v praxi — rozcestník',
            'hub_chapters' => Chapters::byGroup('practice'),
        ]);
    }

    #[Route('/reference', name: 'hub_reference')]
    public function hubReference(): Response
    {
        return $this->render('ddd/hub_reference.html.twig', [
            'title' => 'Reference DDD — rozcestník',
            'hub_chapters' => Chapters::byGroup('reference'),
            'extras' => Chapters::extras(),
        ]);
    }

    #[Route('/co-je-ddd', name: 'what_is_ddd')]
    public function whatIsDdd(): Response
    {
        return $this->render('ddd/what_is_ddd.html.twig', [
            'title' => 'Co je Domain-Driven Design?',
        ]);
    }

    #[Route('/horizontalni-vs-vertikalni', name: 'horizontal_vs_vertical_redirect')]
    public function horizontalVsVerticalRedirect(): Response
    {
        return $this->redirectToRoute('horizontal_vs_vertical', [], 301);
    }

    #[Route('/vertikalni-slice', name: 'horizontal_vs_vertical')]
    public function horizontalVsVertical(): Response
    {
        return $this->render('ddd/horizontal_vs_vertical.html.twig', [
            'title' => 'Vertikální slice architektura vs. Tradiční DDD',
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

    #[Route('/migrace-z-crud', name: 'migration_from_crud')]
    public function migrationFromCrud(): Response
    {
        return $this->render('ddd/migration_from_crud.html.twig', [
            'title' => 'Migrace z CRUD architektury na DDD',
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

    #[Route('/ddd-v-praxi-kde-to-boli', name: 'ddd_pain_points')]
    public function dddPainPoints(): Response
    {
        return $this->render('ddd/ddd_pain_points.html.twig', [
            'title' => 'DDD v praxi — kde to bolí',
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

    #[Route('/kdy-nepouzivat-ddd', name: 'when_not_to_use_ddd')]
    public function whenNotToUseDdd(): Response
    {
        return $this->render('ddd/when_not_to_use_ddd.html.twig', [
            'title' => 'Kdy DDD nepoužívat — upřímně',
        ]);
    }

    #[Route('/ddd-a-umela-inteligence', name: 'ddd_ai')]
    public function dddAi(): Response
    {
        return $this->render('ddd/ddd_ai.html.twig', [
            'title' => 'DDD a umělá inteligence — co říkají autority',
        ]);
    }

    #[Route('/o-autorovi', name: 'about')]
    public function about(): Response
    {
        return $this->render('ddd/about.html.twig', [
            'title' => 'O autorovi',
        ]);
    }

    #[Route('/subdomeny', name: 'subdomains')]
    public function subdomains(): Response
    {
        return $this->render('ddd/subdomains.html.twig', [
            'title' => 'Subdomény: Core, Supporting, Generic',
        ]);
    }

    #[Route('/context-mapping', name: 'context_mapping')]
    public function contextMapping(): Response
    {
        return $this->render('ddd/context_mapping.html.twig', [
            'title' => 'Context Mapping — vztahy mezi Bounded Contexts',
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
            'title' => 'Outbox Pattern — spolehlivé publikování doménových eventů',
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

    #[Route('/team-topologies', name: 'team_topologies')]
    public function teamTopologies(): Response
    {
        return $this->render('ddd/team_topologies.html.twig', [
            'title' => 'Conway\'s Law a Team Topologies',
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
            'title' => 'DDD a microservices — Bounded Context jako service boundary',
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
