<?php

declare(strict_types=1);

namespace App\Catalog;

final class Chapters
{
    /**
     * @return list<array{n:string, route:string, t:string, d:string, time:int, lvl:int, tag:string, group:string}>
     */
    public static function all(): array
    {
        return [
            // Předmluva
            ['n' => '00', 'route' => 'preface',                   't' => 'Předmluva',                      'd' => 'Pro koho je kniha, co pokrývá, jak číst podle role',     'time' => 8,  'lvl' => 1, 'tag' => 'Úvod',        'group' => 'preface'],

            // Hub 1 – Úvod a strategie
            ['n' => '01', 'route' => 'what_is_ddd',               't' => 'Co je Domain-Driven Design',     'd' => 'Filozofie, Ubiquitous Language, Bounded Context',         'time' => 12, 'lvl' => 1, 'tag' => 'Základy',     'group' => 'basics'],
            ['n' => '02', 'route' => 'subdomains',                't' => 'Subdomény: Core, Supporting, Generic', 'd' => 'Kde investovat modelovací úsilí, co koupit, co outsourcovat', 'time' => 18, 'lvl' => 2, 'tag' => 'Základy',     'group' => 'basics'],
            ['n' => '03', 'route' => 'context_mapping',           't' => 'Bounded Context a Context Mapping', 'd' => 'Partnership · Customer/Supplier · Conformist · ACL · OHS · PL · SK', 'time' => 28, 'lvl' => 3, 'tag' => 'Základy', 'group' => 'basics'],
            ['n' => '04', 'route' => 'event_storming',            't' => 'Event Storming a Domain Storytelling', 'd' => 'Workshopy pro objevení domény před první řádkou kódu', 'time' => 25, 'lvl' => 2, 'tag' => 'Základy',     'group' => 'basics'],
            ['n' => '05', 'route' => 'team_topologies',           't' => 'Conway\'s Law a Team Topologies', 'd' => 'Inverse Conway Maneuver – týmová struktura jako rozhodnutí', 'time' => 22, 'lvl' => 2, 'tag' => 'Základy',     'group' => 'basics'],

            // Hub 2 – Taktické modelování
            ['n' => '06', 'route' => 'basic_concepts',            't' => 'Základní koncepty DDD',          'd' => 'Entity · Value Objects · Agregáty · Repozitáře · Events', 'time' => 18, 'lvl' => 2, 'tag' => 'Taktika',     'group' => 'tactics'],
            ['n' => '07', 'route' => 'aggregate_design',          't' => 'Návrh agregátu',                  'd' => 'Hranice agregátu, transakční konzistence, invarianty, eventual consistency', 'time' => 30, 'lvl' => 4, 'tag' => 'Taktika',     'group' => 'tactics'],
            ['n' => '08', 'route' => 'lesser_known_patterns',     't' => 'Doplňující taktické vzory',      'd' => 'Specification · Domain Service · Factory · Module',       'time' => 28, 'lvl' => 3, 'tag' => 'Taktika',     'group' => 'tactics'],

            // Hub 3 – Architektura a implementace
            ['n' => '09', 'route' => 'architectural_styles',      't' => 'Architektonické styly',          'd' => 'Hexagonal · Onion · Clean Architecture vs. Layered',      'time' => 22, 'lvl' => 3, 'tag' => 'Architektura', 'group' => 'architecture'],
            ['n' => '10', 'route' => 'horizontal_vs_vertical',    't' => 'Vertikální slice architektura',  'd' => 'Slicing podle feature, ne podle vrstvy',                  'time' => 12, 'lvl' => 2, 'tag' => 'Architektura', 'group' => 'architecture'],
            ['n' => '11', 'route' => 'implementation_in_symfony', 't' => 'Implementace v Symfony 8',       'd' => 'Struktura projektu, Messenger, DI, Doctrine',             'time' => 35, 'lvl' => 3, 'tag' => 'Architektura', 'group' => 'architecture'],
            ['n' => '12', 'route' => 'authorization_in_ddd',      't' => 'Autorizace v DDD',               'd' => 'Voters · ACL na agregátu · policy-based · ABAC v Symfony 8', 'time' => 25, 'lvl' => 3, 'tag' => 'Architektura', 'group' => 'architecture'],

            // Hub 4 – Pokročilé vzory a infrastruktura
            ['n' => '13', 'route' => 'cqrs',                      't' => 'CQRS',                           'd' => 'Oddělení čtení a zápisu přes Messenger komponentu',       'time' => 35, 'lvl' => 3, 'tag' => 'Vzory',       'group' => 'patterns'],
            ['n' => '14', 'route' => 'event_sourcing',            't' => 'Event Sourcing',                 'd' => 'Stav aplikace jako sekvence doménových událostí',         'time' => 45, 'lvl' => 4, 'tag' => 'Vzory',       'group' => 'patterns'],
            ['n' => '15', 'route' => 'sagas',                     't' => 'Ságy a Process Managery',        'd' => 'Long-running procesy, kompenzace, eventually consistent', 'time' => 40, 'lvl' => 4, 'tag' => 'Vzory',       'group' => 'patterns'],
            ['n' => '16', 'route' => 'outbox_pattern',            't' => 'Outbox Pattern',                 'd' => 'Spolehlivé publikování eventů – eliminace dual-write',    'time' => 28, 'lvl' => 4, 'tag' => 'Vzory',       'group' => 'patterns'],
            ['n' => '17', 'route' => 'performance_aspects',       't' => 'Read modely, projekce a výkon', 'd' => 'Snapshoty, projekce, cache, read-model optimalizace',     'time' => 30, 'lvl' => 4, 'tag' => 'Vzory',       'group' => 'patterns'],

            // Hub 5 – Praxe a provoz
            ['n' => '18', 'route' => 'testing_ddd',               't' => 'Testování DDD',                  'd' => 'Unit · Integration · BDD · contract testy agregátů',      'time' => 30, 'lvl' => 3, 'tag' => 'Praxe',       'group' => 'practice'],
            ['n' => '19', 'route' => 'migration_from_crud',       't' => 'Migrace z CRUD',                 'd' => 'Strangler Fig Pattern – postupný přechod bez stopy',      'time' => 25, 'lvl' => 3, 'tag' => 'Praxe',       'group' => 'practice'],
            ['n' => '20', 'route' => 'microservices_and_ddd',     't' => 'DDD a microservices',            'd' => 'BC jako service boundary · modular monolith · distributed monolith', 'time' => 30, 'lvl' => 4, 'tag' => 'Praxe',       'group' => 'practice'],
            ['n' => '21', 'route' => 'ddd_pain_points',           't' => 'DDD v praxi – kde to bolí',      'd' => '20 reálných problémů: Doctrine, ACL, strangler fig…',     'time' => 35, 'lvl' => 4, 'tag' => 'Praxe',       'group' => 'practice'],
            ['n' => '22', 'route' => 'anti_patterns',             't' => 'Anti-vzory a typické chyby',     'd' => 'Anemic model, smart UI, leaky abstractions',              'time' => 35, 'lvl' => 2, 'tag' => 'Praxe',       'group' => 'practice'],
            ['n' => '23', 'route' => 'when_not_to_use_ddd',       't' => 'Kdy DDD nepoužívat',             'd' => '7 situací, kdy DDD přinese víc škody než užitku',         'time' => 14, 'lvl' => 2, 'tag' => 'Praxe',       'group' => 'practice'],

            // Hub 6 – Syntéza
            ['n' => '24', 'route' => 'practical_examples',        't' => 'Praktické příklady',             'd' => 'E-shop, fakturace, inventory – minimal end-to-end',       'time' => 30, 'lvl' => 3, 'tag' => 'Syntéza',     'group' => 'synthesis'],
            ['n' => '25', 'route' => 'case_study',                't' => 'Případová studie',               'd' => 'Systém pro správu projektů v DDD a CQRS, krok za krokem', 'time' => 50, 'lvl' => 4, 'tag' => 'Syntéza',     'group' => 'synthesis'],
        ];
    }

    /**
     * @return list<array{route:string, t:string, d:string, tag:string}>
     */
    public static function extras(): array
    {
        return [
            ['route' => 'glossary',    't' => 'Glosář',                    'd' => 'Definice klíčových DDD termínů',                          'tag' => 'Reference'],
            ['route' => 'cheat_sheet', 't' => 'Cheat sheet',               'd' => 'Pattern decision tree + Symfony↔DDD mapping + reading paths', 'tag' => 'Reference'],
            ['route' => 'resources',   't' => 'Zdroje',                    'd' => 'Knihy, blogy, videa, kurzy, repos',                       'tag' => 'Reference'],
            ['route' => 'ddd_ai',      't' => 'DDD a umělá inteligence',   'd' => 'Eric Evans · Fowler · Beck · DHH o vztahu DDD a AI',      'tag' => 'Reference'],
        ];
    }

    /**
     * @return list<array{n:string, route:string, t:string, d:string, time:int, lvl:int, tag:string, group:string}>
     */
    public static function byGroup(string $group): array
    {
        return array_values(array_filter(self::all(), static fn(array $c): bool => $c['group'] === $group));
    }

    /**
     * @return array{prev:?array{n:string,route:string,t:string,tag:string},next:?array{n:string,route:string,t:string,tag:string}}
     */
    public static function neighbors(string $route): array
    {
        $all = self::all();
        $idx = null;
        foreach ($all as $i => $c) {
            if ($c['route'] === $route) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            return ['prev' => null, 'next' => null];
        }
        $project = static fn(array $c): array => [
            'n' => $c['n'], 'route' => $c['route'], 't' => $c['t'], 'tag' => $c['tag'],
        ];
        return [
            'prev' => $idx > 0 ? $project($all[$idx - 1]) : null,
            'next' => $idx < count($all) - 1 ? $project($all[$idx + 1]) : null,
        ];
    }
}
