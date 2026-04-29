# Rozšíření knihy DDD v Symfony — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Doplnit do knihy 9 chybějících kapitol (strategický DDD, discovery, produkční vzory, Symfony-specific praxe), 1 novou referenční stránku „Cheat sheet", rozšíření glosáře o dvě nové sekce a kuchařku do migračního článku — tak, aby kniha pokryla i tu druhou polovinu DDD canonu, kterou dosud řeší jen jako zmínky.

**Architecture:** Pure content addition — 9 nových Twig šablon (~1500–2200 řádků každá), 1 nová šablona „cheat sheet", 10 nových PlantUML diagramů s rendrovanými SVG, jeden hub může dostat nový subgroup. Aktualizace `Chapters.php` (centrální katalog), `base.html.twig` (route arrays pro highlight nav), úpravy 12 existujících šablon o cross-linky a rozšíření glosáře. Bez DB, bez build kroku, bez testů.

**Tech Stack:** Twig 3.8+, Symfony 8 (PHP 8.4) routing přes PHP atributy, JSON-LD schema.org, ARIA atributy, `_partials/article_head.html.twig|article_toc|callout|diagram|faq|chapter_nav|github_examples`, PlantUML 1.2024+ s `templates/diagrams/theme.iuml`, post-processed SVG (Inter font).

---

## Architektonická rozhodnutí

### A1. Číslování kapitol a skupiny

**Decision:** Vložit nové kapitoly do existujících skupin podle tématu. Display číslo `n` v `Chapters.php` přečíslovat všude (1–25), aby pořadí zůstalo logické. Route names (= URL) zůstanou stejné u existujících kapitol — žádné 301 redirecty.

**Final ordering** (`Chapters::all()` po dokončení):

| n | route | tag | group | nová? |
|---|-------|-----|-------|-------|
| 01 | `what_is_ddd` | Základy | basics | — |
| 02 | `basic_concepts` | Základy | basics | — |
| 03 | `subdomains` | Strategie | strategic | **NOVÁ** |
| 04 | `context_mapping` | Strategie | strategic | **NOVÁ** |
| 05 | `horizontal_vs_vertical` | Základy | basics | — |
| 06 | `architectural_styles` | Základy | basics | **NOVÁ** (Hexagonal/Onion/Clean) |
| 07 | `implementation_in_symfony` | Základy | basics | — |
| 08 | `cqrs` | Vzory | patterns | — |
| 09 | `event_sourcing` | Vzory | patterns | — |
| 10 | `sagas` | Vzory | patterns | — |
| 11 | `outbox_pattern` | Vzory | patterns | **NOVÁ** |
| 12 | `lesser_known_patterns` | Vzory | patterns | **NOVÁ** (Specifications, DS, Factories, Modules) |
| 13 | `performance_aspects` | Vzory | patterns | — |
| 14 | `event_storming` | Praxe | practice | **NOVÁ** |
| 15 | `team_topologies` | Praxe | practice | **NOVÁ** (Conway's Law) |
| 16 | `authorization_in_ddd` | Praxe | practice | **NOVÁ** |
| 17 | `microservices_and_ddd` | Praxe | practice | **NOVÁ** |
| 18 | `practical_examples` | Praxe | practice | — |
| 19 | `testing_ddd` | Praxe | practice | — |
| 20 | `migration_from_crud` | Praxe | practice | — |
| 21 | `ddd_pain_points` | Praxe | practice | — |
| 22 | `anti_patterns` | Praxe | practice | — |
| 23 | `when_not_to_use_ddd` | Praxe | practice | — |
| 24 | `case_study` | Praxe | practice | — |
| 25 | `ddd_ai` | Reference | reference | — |

**Nová skupina `strategic`** vznikne pro Subdomény + Context Mapping. Důvod: jsou to čistě strategická témata; rozcestník `Základy` by byl neúnosně přeplněný a smíchaný (tady končí filozofie, tady začínají vztahy mezi BC).

**Hub stránky:** Vznikne nový `hub_strategic` (`/strategie`). `hub_basics`, `hub_patterns`, `hub_practice` se rozšíří automaticky přes `Chapters::byGroup()`. `base.html.twig` dostane pátou top-nav položku „Strategie".

**Extras (mimo kapitoly):** `glossary`, `resources`, **nové** `cheat_sheet`.

### A2. Diagramy

Každá nová kapitola dostane **alespoň jeden hlavní PlantUML diagram**. Cesty: `templates/diagrams/<N>_<topic>/<name>.puml` + rendered `.svg`. Číslování složek pokračuje od 11 (po stávajících 1–10 a 15).

**Render:** Lokální PlantUML CLI s `theme.iuml` includem. Po renderu spustit `scripts/postprocess-svg.sh` (přepíše font na Inter) — pokud existuje; pokud ne, jednoduchý `sed` post-process podle stávajících SVG.

### A3. Konzistence — pravidla pro každou novou kapitolu

Každá nová kapitola **MUSÍ** obsahovat:

1. `{% extends 'base.html.twig' %}` + standardní hlavičku bloky `title` / `meta_description` / `meta_keywords` / `og_type=article` / `article_published_time` / `article_modified_time` / `breadcrumb_name`
2. `structured_data` blok s JSON-LD `TechArticle` (vzor: `templates/ddd/what_is_ddd.html.twig:16-46`)
3. `_partials/article_head.html.twig` s vyplněnými `chapter_number`, `category`, `title`, `deck`, `reading_time`, `difficulty`, `published`, `last_updated`, `author`
4. `_partials/article_toc.html.twig`
5. **Volitelně** `_partials/github_examples.html.twig` — pouze pokud existuje odpovídající složka v repu DDD-Symfony příkladů. **NEVKLÁDAT prázdný odkaz.**
6. Sekce `<section id="..." aria-labelledby="...-heading"><h2 id="...-heading" class="h-section"><span class="h-num">XX.NN</span> Title</h2>...</section>`
7. **Minimálně 3** callouty (`note`, `pattern`, `warn`, `tip`) přes `_partials/callout.html.twig`
8. **Minimálně 1** diagram přes `_partials/diagram.html.twig`
9. FAQ sekce přes `_partials/faq.html.twig` (4–6 otázek odpovídajících na Google "People Also Ask" formulace)
10. `_partials/chapter_nav.html.twig` na konci `<div class="art-body">`
11. **Externí citace** — minimálně 4 odkazy na primární zdroje (Evans, Vernon, Fowler, Brandolini, Cockburn, Skelton/Pais, atd.) ve formátu `<a href="..." target="_blank" rel="noopener">[N]</a>`

### A4. Cross-cutting consistency — co aktualizovat při KAŽDÉ nové kapitole

Tento checklist se opakuje pro každou kapitolu (v plánu je explicitně rozepsaný v Tasku „Cross-references" pro každou):

- `src/Catalog/Chapters.php` — přidat řádek, přečíslovat
- `src/Controller/DddController.php` — přidat route + action + (pro novou skupinu) hub action
- `templates/base.html.twig` — přidat route do `_basics_routes` / `_patterns_routes` / `_practice_routes` / `_strategic_routes` (nové). Přidat top-nav položku „Strategie" pokud ještě není.
- `templates/ddd/glossary.html.twig` — přidat **každý nový pojem** jako `<div class="glossary-entry" id="term-...">` s definicí + odkazem na novou kapitolu
- `templates/ddd/resources.html.twig` — pokud kapitola odkazuje na knihu/zdroj, který tam ještě není, přidat
- Cross-linky v 2–4 existujících kapitolách (uvedeno u každé nové kapitoly konkrétně)
- `templates/ddd/index.html.twig` — pokud zobrazuje celkový počet kapitol/odhad času, aktualizovat (ověřit při task 1)

### A5. Worktree

Doporučuje se spustit celou implementaci v worktree (`docs-rozsireni-knihy`):

```bash
git worktree add ../ddd-v-symfony.docs-rozsireni rozsireni-knihy
```

Důvod: 9 kapitol × ~3 dny každá = několik týdnů práce; mezitím může na `main` přistát jiný drobný fix. Před exekucí použít skill `superpowers:using-git-worktrees`.

---

## Master File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Modify | `src/Catalog/Chapters.php` | Přečíslování + 9 nových řádků + nová skupina `strategic` + nový extras item `cheat_sheet` |
| Modify | `src/Controller/DddController.php` | 9 nových akcí + 1 nový hub (`hubStrategic`) + 1 extras (`cheatSheet`) |
| Modify | `templates/base.html.twig` | Top-nav „Strategie" položka, `_strategic_routes` array, footer columns rozšíření |
| Create | `templates/ddd/hub_strategic.html.twig` | Nový hub pro group `strategic` (kapitoly 03–04) |
| Modify | `templates/ddd/hub_basics.html.twig` | Aktualizace `hub_meta` (čas, počet kapitol), případně `hub_part` |
| Modify | `templates/ddd/hub_patterns.html.twig` | Stejně — nárůst z 4 na 6 kapitol |
| Modify | `templates/ddd/hub_practice.html.twig` | Nárůst ze 7 na 11 kapitol — dle UI to může vyžadovat novou sekci `hub_part 2` (split na „Discovery a tým" + „Implementace a evoluce") |
| Modify | `templates/ddd/hub_reference.html.twig` | Přidání cheat sheetu mezi extras |
| Modify | `templates/ddd/index.html.twig` | Hero / overview — celkový počet kapitol, případně rozšíření kategorií |
| Create | `templates/ddd/subdomains.html.twig` | Kap. 03 — Core / Supporting / Generic subdomény |
| Create | `templates/ddd/context_mapping.html.twig` | Kap. 04 — Context Map a 8 vztahů mezi BC |
| Create | `templates/ddd/architectural_styles.html.twig` | Kap. 06 — Hexagonal / Onion / Clean Architecture |
| Create | `templates/ddd/outbox_pattern.html.twig` | Kap. 11 — Transactional Outbox + Inbox + Idempotency |
| Create | `templates/ddd/lesser_known_patterns.html.twig` | Kap. 12 — Specifications, Domain Services, Factories, Modules |
| Create | `templates/ddd/event_storming.html.twig` | Kap. 14 — Event Storming + Domain Storytelling |
| Create | `templates/ddd/team_topologies.html.twig` | Kap. 15 — Conway's Law + Team Topologies |
| Create | `templates/ddd/authorization_in_ddd.html.twig` | Kap. 16 — Voters, ACL na agregátu, policy-based |
| Create | `templates/ddd/microservices_and_ddd.html.twig` | Kap. 17 — BC ↔ service boundary, integration events |
| Create | `templates/ddd/cheat_sheet.html.twig` | Extras — pattern decision tree + Symfony↔DDD lookup |
| Modify | `templates/ddd/glossary.html.twig` | +25–30 nových termínů, nová sekce „Slovní pasti" + „Symfony↔DDD mapping" |
| Modify | `templates/ddd/migration_from_crud.html.twig` | Nová sekce „Refactoring kuchařka — krátké recepty" |
| Modify | `templates/ddd/what_is_ddd.html.twig` | Cross-linky na Subdomains + Context Mapping + Architectural Styles |
| Modify | `templates/ddd/basic_concepts.html.twig` | Cross-linky na Lesser Known Patterns (Domain Service, Factory, Specification) |
| Modify | `templates/ddd/horizontal_vs_vertical.html.twig` | Cross-link na Architectural Styles |
| Modify | `templates/ddd/cqrs.html.twig` | Cross-link na Outbox |
| Modify | `templates/ddd/event_sourcing.html.twig` | Cross-link na Outbox + Sagas (existuje) |
| Modify | `templates/ddd/sagas.html.twig` | Cross-link na Outbox + Microservices |
| Modify | `templates/ddd/ddd_pain_points.html.twig` | Cross-linky na Outbox, Authorization, Microservices |
| Modify | `templates/ddd/anti_patterns.html.twig` | Cross-link na Lesser Known (anémické modely) |
| Modify | `templates/ddd/when_not_to_use_ddd.html.twig` | Cross-link na Microservices („nemáte BC") |
| Modify | `templates/ddd/case_study.html.twig` | Cross-link na Subdomains + Context Mapping (case study už používá context map) |
| Modify | `templates/ddd/practical_examples.html.twig` | Cross-link na Cheat Sheet |
| Modify | `templates/ddd/resources.html.twig` | Přidat: Skelton & Pais — Team Topologies; Cockburn — Hexagonal; Brandolini — Event Storming book |
| Create | `templates/diagrams/11_subdomains/core_supporting_generic.puml` + `.svg` | Diagram pro Kap. 03 |
| Create | `templates/diagrams/12_context_mapping/context_map_patterns.puml` + `.svg` | Diagram pro Kap. 04 |
| Create | `templates/diagrams/12_context_mapping/acl_anatomy.puml` + `.svg` | Druhý diagram — anatomie ACL |
| Create | `templates/diagrams/13_architectural_styles/hexagonal_vs_onion.puml` + `.svg` | Diagram pro Kap. 06 |
| Create | `templates/diagrams/14_outbox/outbox_flow.puml` + `.svg` | Diagram pro Kap. 11 |
| Create | `templates/diagrams/14_outbox/inbox_idempotency.puml` + `.svg` | Druhý — idempotent inbox |
| Create | `templates/diagrams/16_lesser_patterns/specification_compose.puml` + `.svg` | Diagram pro Kap. 12 |
| Create | `templates/diagrams/17_event_storming/big_picture_levels.puml` + `.svg` | Diagram pro Kap. 14 |
| Create | `templates/diagrams/18_team_topologies/conway_inverse.puml` + `.svg` | Diagram pro Kap. 15 |
| Create | `templates/diagrams/19_authorization/policy_layers.puml` + `.svg` | Diagram pro Kap. 16 |
| Create | `templates/diagrams/20_microservices/bc_to_service.puml` + `.svg` | Diagram pro Kap. 17 |

---

## Phases

| Fáze | Obsah | Dependency |
|------|-------|-----------|
| **0** | Refactor `Chapters.php` na novou strukturu (skupina `strategic`, přečíslování), nová skupina v `base.html.twig` + `hub_strategic` skeleton | žádná |
| **1** | Strategický DDD: Subdomény, Context Mapping, Architectural Styles | Fáze 0 |
| **2** | Discovery a tým: Event Storming, Team Topologies | Fáze 1 (Subdomény musí existovat — Event Storming je odkazuje) |
| **3** | Produkční vzory: Outbox, Lesser Known Patterns, Microservices | Fáze 1 (Context Mapping musí existovat — Microservices je odkazují) |
| **4** | Symfony-specific: Authorization | Fáze 1 (cross-link na Subdomény pro Core / Supporting domain prioritizaci permissions) |
| **5** | Reference & glue: Cheat Sheet, Glossary expansion (False Friends + Symfony↔DDD), Refactoring kuchařka v Migration | Fáze 1–4 (Cheat Sheet shrnuje vše předchozí) |
| **6** | Cross-cutting consistency sweep: link audit, FAQ aktualizace, hubs final tuning | Fáze 5 |

**Nezávislost:** Fáze 1 → Fáze 2,3,4 mohou běžet paralelně (různé soubory). Fáze 5 a 6 sekvenčně.

---

## PHASE 0 — Příprava: nová skupina `strategic` + přečíslování katalogu

### Task 0.1: Refactor `Chapters.php` na novou strukturu

**Files:**
- Modify: `src/Catalog/Chapters.php` (celý soubor)

- [ ] **Step 1: Přepsat metodu `Chapters::all()` na novou tabulku 1–25 s novými route a skupinou `strategic`**

Nahradit pole vrácené z `all()` celkovou tabulkou podle [A1]. Důležité: PRO ZATÍM PŘIDAT JEN ŘÁDKY, jejichž route už existuje (existující kapitoly) + placeholdery pro nové route s **TODO** flagem komentem `// FÁZE X — kapitola se přidá v Tasku Y.Z` u každé. **Nepřidávat řádek, jehož route ještě není v `DddController`**, jinak `path()` v partialech crashne.

Reálná hodnota tohoto kroku je tedy **přečíslování existujících 16 řádků a změna group hodnot kde je třeba** + přejmenování stávajícího `n='03' horizontal_vs_vertical` na `n='05'` atd. Nové kapitoly se přidají v jejich vlastních taskách.

Konkrétní mezistav (obsahuje pouze existující route s novým `n`):

```php
public static function all(): array
{
    return [
        ['n' => '01', 'route' => 'what_is_ddd',               't' => 'Co je Domain-Driven Design',     'd' => 'Filozofie, Ubiquitous Language, Bounded Context',         'time' => 12, 'lvl' => 1, 'tag' => 'Základy',    'group' => 'basics'],
        ['n' => '02', 'route' => 'basic_concepts',            't' => 'Základní koncepty DDD',          'd' => 'Entity · Value Objects · Agregáty · Repozitáře · Events', 'time' => 18, 'lvl' => 2, 'tag' => 'Základy',    'group' => 'basics'],
        // 03 subdomains — přidá Task 1.1
        // 04 context_mapping — přidá Task 1.2
        ['n' => '05', 'route' => 'horizontal_vs_vertical',    't' => 'Vertikální slice architektura',  'd' => 'Slicing podle feature, ne podle vrstvy',                  'time' => 12, 'lvl' => 2, 'tag' => 'Základy',    'group' => 'basics'],
        // 06 architectural_styles — přidá Task 1.3
        ['n' => '07', 'route' => 'implementation_in_symfony', 't' => 'Implementace v Symfony 8',       'd' => 'Struktura projektu, Messenger, DI, Doctrine',             'time' => 35, 'lvl' => 3, 'tag' => 'Základy',    'group' => 'basics'],
        ['n' => '08', 'route' => 'cqrs',                      't' => 'CQRS',                           'd' => 'Oddělení čtení a zápisu přes Messenger komponentu',       'time' => 35, 'lvl' => 3, 'tag' => 'Vzory',      'group' => 'patterns'],
        ['n' => '09', 'route' => 'event_sourcing',            't' => 'Event Sourcing',                 'd' => 'Stav aplikace jako sekvence doménových událostí',         'time' => 45, 'lvl' => 4, 'tag' => 'Vzory',      'group' => 'patterns'],
        ['n' => '10', 'route' => 'sagas',                     't' => 'Ságy a Process Managery',        'd' => 'Long-running procesy, kompenzace, eventually consistent', 'time' => 40, 'lvl' => 4, 'tag' => 'Vzory',      'group' => 'patterns'],
        // 11 outbox_pattern — přidá Task 3.1
        // 12 lesser_known_patterns — přidá Task 3.2
        ['n' => '13', 'route' => 'performance_aspects',       't' => 'Výkonnostní aspekty',            'd' => 'Snapshoty, projekce, cache, read-model optimalizace',     'time' => 30, 'lvl' => 4, 'tag' => 'Vzory',      'group' => 'patterns'],
        // 14 event_storming — přidá Task 2.1
        // 15 team_topologies — přidá Task 2.2
        // 16 authorization_in_ddd — přidá Task 4.1
        // 17 microservices_and_ddd — přidá Task 3.3
        ['n' => '18', 'route' => 'practical_examples',        't' => 'Praktické příklady',             'd' => 'E-shop, fakturace, inventory — minimal end-to-end',       'time' => 30, 'lvl' => 3, 'tag' => 'Praxe',      'group' => 'practice'],
        ['n' => '19', 'route' => 'testing_ddd',               't' => 'Testování DDD',                  'd' => 'Unit · Integration · BDD · contract testy agregátů',      'time' => 30, 'lvl' => 3, 'tag' => 'Praxe',      'group' => 'practice'],
        ['n' => '20', 'route' => 'migration_from_crud',       't' => 'Migrace z CRUD',                 'd' => 'Strangler Fig Pattern — postupný přechod bez stopu',      'time' => 25, 'lvl' => 3, 'tag' => 'Praxe',      'group' => 'practice'],
        ['n' => '21', 'route' => 'ddd_pain_points',           't' => 'DDD v praxi – kde to bolí',      'd' => '20 reálných problémů: Doctrine, ACL, strangler fig…',     'time' => 35, 'lvl' => 4, 'tag' => 'Praxe',      'group' => 'practice'],
        ['n' => '22', 'route' => 'anti_patterns',             't' => 'Anti-vzory a typické chyby',     'd' => 'Anemic model, smart UI, leaky abstractions',              'time' => 35, 'lvl' => 2, 'tag' => 'Praxe',      'group' => 'practice'],
        ['n' => '23', 'route' => 'when_not_to_use_ddd',       't' => 'Kdy DDD nepoužívat',             'd' => '7 situací, kdy DDD přinese víc škody než užitku',         'time' => 14, 'lvl' => 2, 'tag' => 'Praxe',      'group' => 'practice'],
        ['n' => '24', 'route' => 'case_study',                't' => 'Případová studie',               'd' => 'Systém pro správu projektů v DDD a CQRS, krok za krokem', 'time' => 50, 'lvl' => 4, 'tag' => 'Praxe',      'group' => 'practice'],
        ['n' => '25', 'route' => 'ddd_ai',                    't' => 'DDD a umělá inteligence',        'd' => 'Eric Evans · Fowler · Beck · DHH o vztahu DDD a AI',      'time' => 20, 'lvl' => 1, 'tag' => 'Reference', 'group' => 'reference'],
    ];
}
```

- [ ] **Step 2: Doplnit `extras()` o cheat sheet (placeholder, kapitola se přidá v Tasku 5.1)**

Zatím komentář, samotná položka se odkomentuje až v Tasku 5.1, aby `path('cheat_sheet')` neselhalo.

```php
public static function extras(): array
{
    return [
        ['route' => 'glossary',  't' => 'Glosář', 'd' => 'Definice klíčových DDD termínů',    'tag' => 'Reference'],
        ['route' => 'resources', 't' => 'Zdroje', 'd' => 'Knihy, blogy, videa, kurzy, repos', 'tag' => 'Reference'],
        // ['route' => 'cheat_sheet', 't' => 'Cheat sheet', 'd' => 'Pattern decision tree + Symfony↔DDD mapping', 'tag' => 'Reference'], // FÁZE 5 Task 5.1
    ];
}
```

- [ ] **Step 3: Smoke test — projít všechny rozcestníky**

```bash
symfony server:start -d
curl -fs http://127.0.0.1:8000/ > /dev/null && echo "homepage OK"
curl -fs http://127.0.0.1:8000/zaklady > /dev/null && echo "hub_basics OK"
curl -fs http://127.0.0.1:8000/vzory > /dev/null && echo "hub_patterns OK"
curl -fs http://127.0.0.1:8000/praxe > /dev/null && echo "hub_practice OK"
curl -fs http://127.0.0.1:8000/reference > /dev/null && echo "hub_reference OK"
```

Expected: každá URL vrátí 200, žádné Twig errory v `var/log/dev.log`. Display čísla u kapitol musí být po reload nové (01, 02, 05, 07, 08, 09, 10, 13, 18–25).

- [ ] **Step 4: Commit**

```bash
git add src/Catalog/Chapters.php
git commit -m "refactor(catalog): přečíslování kapitol pro vložení 9 nových (mezery 03–04, 06, 11–12, 14–17)"
```

### Task 0.2: Nová skupina `strategic` + hub stránka

**Files:**
- Modify: `src/Controller/DddController.php`
- Create: `templates/ddd/hub_strategic.html.twig`
- Modify: `templates/base.html.twig`

- [ ] **Step 1: Přidat `hubStrategic()` action do `DddController`**

Vložit za `hubBasics()` (řádek 28):

```php
    #[Route('/strategie', name: 'hub_strategic')]
    public function hubStrategic(): Response
    {
        return $this->render('ddd/hub_strategic.html.twig', [
            'title' => 'Strategický DDD — rozcestník',
            'hub_chapters' => Chapters::byGroup('strategic'),
        ]);
    }
```

- [ ] **Step 2: Vytvořit `templates/ddd/hub_strategic.html.twig`**

Stejný vzor jako `hub_basics.html.twig`, ale:
- `block title`: `Strategický DDD – rozcestník | DDD Symfony 8`
- `block meta_description`: `Strategická vrstva Domain-Driven Design: identifikace subdomén, context mapping a architektonické styly. Než začnete kódit, rozhodněte správně.`
- `block breadcrumb_name`: `Strategie`
- `hub_eyebrow`: `PŘÍRUČKA · ROZCESTNÍK · STRATEGIE`
- `hub_h_main`: `Strategický DDD,`
- `hub_h_em`: `než nakreslíte první třídu.`
- `hub_deck`: `Tato sekce řeší <strong>kde</strong> a <strong>proč</strong> — identifikaci jádra domény, vztahy mezi bounded contexty a architektonický styl, do kterého se taktické vzory budou vrtat. Bez ní jsou Entity, VO a Agregáty náhodný shluk objektů bez kontextu.`
- `hub_meta`: stejný formát, ale s počtem 2–3 kapitol
- `hub_part`: `PART I.5`
- `hub_part_title`: `Subdomény · vztahy · architektonický styl`
- `hub_part_sub`: `co řešit dřív než kód`

- [ ] **Step 3: Zaregistrovat novou skupinu v `base.html.twig`**

V `templates/base.html.twig:53-56` přidat řádek pro `_strategic_routes` a aktualizovat top-nav. **Důležité:** všechny route v `_strategic_routes` musí už existovat v `DddController` v okamžiku merge — protože tato fáze ještě nemá kapitoly 03–04, pole bude **prázdné kromě hubu samotného**:

```twig
{%- set _basics_routes = ['hub_basics', 'what_is_ddd', 'basic_concepts', 'horizontal_vs_vertical', 'implementation_in_symfony'] -%}
{%- set _strategic_routes = ['hub_strategic'] -%}
{%- set _patterns_routes = ['hub_patterns', 'cqrs', 'event_sourcing', 'sagas', 'performance_aspects'] -%}
{%- set _practice_routes = ['hub_practice', 'practical_examples', 'testing_ddd', 'migration_from_crud', 'ddd_pain_points', 'anti_patterns', 'when_not_to_use_ddd', 'case_study'] -%}
{%- set _reference_routes = ['hub_reference', 'ddd_ai', 'glossary', 'resources'] -%}
```

A v `topnav-center` (řádek 78–82) vložit za `Základy`:

```twig
<a class="navlink {% if _route in _strategic_routes %}active{% endif %}" href="{{ path('hub_strategic') }}"{% if _route == 'hub_strategic' %} aria-current="page"{% elseif _route in _strategic_routes %} aria-current="true"{% endif %}>Strategie</a>
```

Stejně do `nav-drawer` (`<li>` po `Základy`) a do footer-cols (po Základech), kde se vypisují kapitoly skupiny — přidá se `_foot_strategic = ddd_chapters_by_group('strategic')` a `<div class="foot-a-col">` se seznamem.

Pokud Twig funkce `ddd_chapters_by_group` ještě neumí `'strategic'` (pravděpodobně umí, jelikož je to jen pass-through na `Chapters::byGroup($group)` — viz funkční signature v Catalogu), bude to fungovat automaticky a vrátí prázdné pole. Pole prázdné → footer column se schová pomocí `{% if _foot_strategic|length > 0 %}` wrapperu.

- [ ] **Step 4: Smoke test**

```bash
curl -fs http://127.0.0.1:8000/strategie > /dev/null && echo "hub_strategic OK"
```

Hub stránka musí renderovat (zatím prázdný grid kapitol).

- [ ] **Step 5: Commit**

```bash
git add src/Controller/DddController.php templates/ddd/hub_strategic.html.twig templates/base.html.twig
git commit -m "feat(nav): nová skupina Strategie — hub_strategic + nav položka"
```

---

## PHASE 1 — Strategický DDD (3 kapitoly)

### Task 1.1: Kapitola 03 — Subdomény (Core / Supporting / Generic)

**Files:**
- Create: `templates/ddd/subdomains.html.twig`
- Create: `templates/diagrams/11_subdomains/core_supporting_generic.puml` + `.svg`
- Modify: `src/Controller/DddController.php`
- Modify: `src/Catalog/Chapters.php`
- Modify: `templates/base.html.twig` (přidat route do `_strategic_routes`)
- Modify: `templates/ddd/glossary.html.twig` (3 nové termíny)
- Modify: `templates/ddd/what_is_ddd.html.twig` (cross-link)
- Modify: `templates/ddd/when_not_to_use_ddd.html.twig` (cross-link — „nemáte core domain → DDD nepřináší hodnotu")
- Modify: `templates/ddd/case_study.html.twig` (cross-link — případová studie už dělí na BC, doplnit subdoménový pohled)

**Goal kapitoly:** Naučit čtenáře identifikovat **Core / Supporting / Generic** subdomény a vyvodit z toho strategické rozhodnutí: kam investovat modelovací úsilí, co kupovat off-the-shelf, co outsourcovat. Pro DDD v Symfony to znamená: kde dělat plný taktický design vs. kde stačí Doctrine CRUD nebo SaaS.

- [ ] **Step 1: Přidat route + action**

Do `DddController.php` (za `basicConcepts()`):

```php
    #[Route('/subdomeny', name: 'subdomains')]
    public function subdomains(): Response
    {
        return $this->render('ddd/subdomains.html.twig', [
            'title' => 'Subdomény: Core, Supporting, Generic',
        ]);
    }
```

- [ ] **Step 2: Aktualizovat `Chapters::all()`**

Odkomentovat / vložit řádek `n='03'`:

```php
['n' => '03', 'route' => 'subdomains', 't' => 'Subdomény: Core, Supporting, Generic', 'd' => 'Kde investovat modelovací úsilí, co koupit, co outsourcovat', 'time' => 18, 'lvl' => 2, 'tag' => 'Strategie', 'group' => 'strategic'],
```

- [ ] **Step 3: Přidat route do `_strategic_routes` v `base.html.twig`**

```twig
{%- set _strategic_routes = ['hub_strategic', 'subdomains'] -%}
```

- [ ] **Step 4: Vytvořit hlavní diagram — `templates/diagrams/11_subdomains/core_supporting_generic.puml`**

```plantuml
@startuml
!include ../theme.iuml

title E-shop: subdoménové členění a investice

rectangle "Core Domain" as core $ACCENT {
  rectangle "Pricing & Promotions" as pricing
  rectangle "Personalized Recommendations" as recom
  note right of pricing
    KONKURENČNÍ VÝHODA
    plný DDD taktický design
    in-house, seniorní tým
  end note
}

rectangle "Supporting Subdomain" as supp {
  rectangle "Order Management" as order
  rectangle "Inventory" as inv
  note right of order
    Nutné pro byznys, ale ne diferenciátor
    DDD light + Doctrine ORM
    junior–medior tým
  end note
}

rectangle "Generic Subdomain" as gen {
  rectangle "Authentication" as auth
  rectangle "Payments (Stripe)" as pay
  rectangle "Email delivery (SES)" as email
  note right of auth
    Komodita
    SaaS / off-the-shelf knihovna
    minimum custom kódu
  end note
}

core -[hidden]down- supp
supp -[hidden]down- gen
@enduml
```

Render: `plantuml -tsvg templates/diagrams/11_subdomains/core_supporting_generic.puml` → vytvoří `.svg`. Spustit `scripts/postprocess-svg.sh templates/diagrams/11_subdomains/core_supporting_generic.svg` (pokud existuje skript).

- [ ] **Step 5: Vytvořit `templates/ddd/subdomains.html.twig` — kompletní obsah podle osnovy**

Soubor extends `base.html.twig` se standardní hlavičkou (viz [A3]). Bloky:

- `title`: `Subdomény: Core, Supporting, Generic — kde investovat | DDD Symfony`
- `meta_description`: `Strategický DDD: identifikace Core, Supporting a Generic subdomén. Naučte se rozhodnout, kde nasadit plný taktický design a kde stačí Doctrine CRUD nebo SaaS.`
- `meta_keywords`: `Core Domain, Supporting Subdomain, Generic Subdomain, strategický DDD, subdoména, Eric Evans, business strategy, build vs buy, Symfony`
- `article_published_time`: `2026-04-29`
- `article_modified_time`: `2026-04-29`
- `breadcrumb_name`: `Subdomény`

`article_head` partial:
- `chapter_number: '03'`, `category: 'Strategie'`, `reading_time: 18`, `difficulty: 2`
- `deck`: „Než vytvoříte první Aggregate, rozhodněte, kde to vůbec dává smysl. Subdomény jsou Evansův strategický filtr: tři kategorie, které určují, kolik úsilí, jakou seniority a jaký technologický stack si konkrétní část aplikace zaslouží."

**Sekce kapitoly:**

1. `03.01 Proč subdomény předcházejí všemu ostatnímu` (`id="proc-subdomeny"`)
   - Otevírací odstavec: vývojářský reflex „naimplementuju to celé pořádně" je drahý a marný — ne každá část aplikace si zaslouží stejnou hloubku modelování. Evans (2003, kap. 14) zavádí Core / Supporting / Generic jako filtr.
   - 2. odstavec: souvislost s Bounded Context — BC je *implementační* hranice, subdoména je *byznysová* hranice. 1 BC může být celá subdoména, ale 1 subdoména může být rozdělena do více BC (např. Core „Pricing" v BC „Catalog" + BC „Checkout").
   - Citace: Evans 2003 kap. 14; Vernon 2013 kap. 2; Khononov *Learning DDD* (2021) kap. 1.
   - Cross-link na `{{ path('what_is_ddd') }}#strategic-design` a `{{ path('basic_concepts') }}#bounded-contexts`.

2. `03.02 Tři kategorie subdomén` (`id="tri-kategorie"`)
   - Krátký úvod, pak `<dl>` se třemi `<dt>/<dd>` páry:
     - **Core Domain** — definice, kritérium („pokud z toho zítra ustoupíte, ztratíte konkurenční výhodu"), důsledky pro tým/stack
     - **Supporting Subdomain** — definice, kritérium („potřebné pro provoz, ale nikdo nás kvůli tomu nenajme"), důsledky
     - **Generic Subdomain** — definice, kritérium („řešení existuje 30 let, kupuje se / volně dostupné"), důsledky
   - Diagram: vložit `templates/diagrams/11_subdomains/core_supporting_generic.svg` přes `_partials/diagram.html.twig` s `fig: '03.2-A'`, `title: 'E-shop: subdoménové členění a investice'`.
   - Callout typu `pattern`: tabulka Core/Supporting/Generic × (úsilí, seniorita, technologie, vlastnictví IP).

3. `03.03 Jak rozpoznat Core Domain — pětibodový test` (`id="rozpoznat-core"`)
   - Test (každý bod 2–3 věty):
     1. „Pokud bychom to outsourcovali, můžeme i tak prodávat hlavní produkt?" — pokud ANO, není to Core.
     2. „Existuje tržní benchmark / standard?" — pokud ANO, je to Generic.
     3. „Píšeme to už podruhé jinak než konkurence?" — pokud ANO, je to pravděpodobně Core.
     4. „Mluví o tom CEO / VP product na týdenní bázi?" — pokud ANO, Core indicator.
     5. „Plánujeme tady experimentovat / měnit pravidla často?" — pokud ANO, Core (proto musí být v rukou seniorního týmu).
   - Callout typu `tip`: „Pokud test říká, že máte 5 Core domén, něco je špatně — Core je z definice malé a vzácné."

4. `03.04 Anti-vzor: ‚všechno je Core'` (`id="vsechno-core-antipattern"`)
   - 2–3 odstavce o tom, proč týmy mají sklon prohlásit vše za Core (psychologická investice, ego), a co to dělá s rozpočtem a deadliny.
   - Callout typu `warn`: konkrétní příklad „startup, který implementoval custom auth → pak musel řešit GDPR / WebAuthn / SSO a nešlo to dohnat. Měli koupit Auth0 / Keycloak.".
   - Cross-link na `{{ path('when_not_to_use_ddd') }}`.

5. `03.05 Mapování subdomén na Bounded Contexts` (`id="subdomeny-na-bc"`)
   - Klíčové vztahy 1:1, 1:N, N:1.
   - **Tabulka v HTML** (3 sloupce: subdoména, BC(s), poznámka) na příkladu e-shopu:
     | Subdoména | BC | Vztah |
     | Pricing (Core) | Catalog BC, Checkout BC | 1:N — sdílené pravidlo „cena" v obou kontextech |
     | Order Mgmt (Supporting) | Ordering BC | 1:1 |
     | Identity (Generic) | sdílí Auth0, vystupuje jako External BC | 1:1 přes ACL |
   - Cross-link na **Kapitolu 04 Context Mapping** (až bude existovat — momentálně zatím forward link s tip boxem „připravujeme").

6. `03.06 Subdomény v Symfony — co to znamená pro strukturu projektu` (`id="symfony-implications"`)
   - **Code sample:** struktura `src/` rozdělená podle subdomén (ne podle vrstev):
     ```
     src/
       Core/Pricing/             ← plný DDD: Aggregate, VO, DomainEvent
         Domain/
         Application/
         Infrastructure/
       Supporting/Ordering/      ← lehký DDD: minimal Aggregate, hodně Doctrine ORM
         Domain/
         Application/
         Infrastructure/
       Generic/Auth/             ← bridge na Auth0, žádný custom Aggregate
         Adapter/Auth0Client.php
         Adapter/Auth0UserProvider.php
     ```
   - Code sample 2: `composer.json` s `"autoload": { "psr-4": { "App\\Core\\": "src/Core/", "App\\Supporting\\": "src/Supporting/", "App\\Generic\\": "src/Generic/" } }`.
   - Callout typu `pattern`: „Tato struktura násilně vynucuje strategické rozhodnutí — junior nemůže ‚omylem' přidat custom třídu do `Generic/Auth/`."
   - Cross-link na `{{ path('implementation_in_symfony') }}#projektova-struktura`.

7. `03.07 Subdomény a sourcing strategie (build / buy / partner)` (`id="sourcing")
   - Krátká matice rozhodování:
     - Core → BUILD in-house (seniorní tým, vlastní IP)
     - Supporting → BUILD lehce, NEBO BUY (pokud existuje hotové řešení s 80% pokrytím)
     - Generic → BUY / RENT / OPEN-SOURCE (off-the-shelf)
   - Příklad (zákaznická VS interní perspektiva): u SaaS firmy je „CRM" generic, u CRM startupu je „CRM" core.

8. `03.08 Evoluce subdomén v čase` (`id="evoluce"`)
   - Subdomény se posouvají:
     - Z Generic do Core (Stripe se stal Core pro Stripe — bývalý Generic v jejich pohledu)
     - Z Core do Supporting (po komoditizaci trhu — DropBox jádro postupně komoditizováno cloud storage providery)
     - Sledovat to a re-evaluovat každých 12–18 měsíců.
   - Callout `note`: „Strategický audit subdomén = pravidelný workshop, ne jednorázové cvičení."

9. `03.09 Praktický postup — krok za krokem` (`id="postup"`)
   - 5-bodový kontrolní seznam:
     1. Vypsat všechny „capability" / use-case („objednat zboží", „získat doporučení", „přihlásit se").
     2. U každé položky odpovědět na pětibodový test (sekce 03.03).
     3. Seskupit do subdomén.
     4. Pro každou subdoménu rozhodnout sourcing.
     5. Zapsat do **Domain Vision Statement** (1 stránka A4) — kdo, co, proč, kdy.
   - Code sample: ukázka Domain Vision Statementu (markdown / HTML, ~15 řádků):
     ```markdown
     # Pricing — Core Domain
     ## What
     Dynamický pricing s personalizovanými promo kódy.
     ## Why core
     Konkurenti používají statický pricing. Dynamic pricing je 18 % marže navíc.
     ## Investment
     Tým: 3 senior PHP devs + 1 data scientist. In-house, žádné SaaS.
     ## Bounded contexts
     - Catalog (read model)
     - Checkout (write model + validation)
     ## Off-limits
     Žádný outsourcing. Žádný low-code. Žádný junior bez code review od leadu.
     ```

10. `03.10 Shrnutí` (`id="summary"`)
    - 4 body: Core = vzácné, Supporting = většina, Generic = kupuj, mapování na BC je 1:N.

11. **FAQ** (4–6 otázek):
    - Co je rozdíl mezi subdoménou a bounded contextem?
    - Můžu změnit klasifikaci subdomény?
    - Jak rozhodnout, jestli `Identity` je Core nebo Generic?
    - Kolik subdomén je „normální"?
    - Co když nemáme žádný Core domain?

- [ ] **Step 6: Doplnit glosář — 3 nové termíny**

V `templates/ddd/glossary.html.twig` v sekci „Strategické vzory DDD" přidat (po existujícím `term-bounded-context`):

```html
<div class="glossary-entry" id="term-core-domain">
    <dt><dfn>Core Domain</dfn> <span class="glossary-en">(jádrová doména)</span></dt>
    <dd>
        <p>Subdoména, která představuje hlavní konkurenční výhodu organizace — to, kvůli čemu zákazníci platí. Investuje se do ní nejvíce modelovacího úsilí, vlastní se interně a obsazuje se nejzkušenějšími lidmi. Evans (2003) ji označuje jako jediné místo, kde má smysl plný taktický DDD design.</p>
        <p class="glossary-related">Související: <a href="#term-supporting-subdomain">Supporting Subdomain</a>, <a href="#term-generic-subdomain">Generic Subdomain</a>. Detail: <a href="{{ path('subdomains') }}#tri-kategorie">Subdomény — Tři kategorie</a>.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-supporting-subdomain">
    <dt><dfn>Supporting Subdomain</dfn> <span class="glossary-en">(podpůrná subdoména)</span></dt>
    <dd>
        <p>Subdoména nezbytná pro běh businessu, ale nepředstavující diferenciátor. Implementuje se interně s nižší mírou DDD investice — typicky lehký taktický design nebo Doctrine ORM CRUD s minimální doménovou logikou.</p>
        <p class="glossary-related">Detail: <a href="{{ path('subdomains') }}#tri-kategorie">Subdomény</a>.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-generic-subdomain">
    <dt><dfn>Generic Subdomain</dfn> <span class="glossary-en">(generická subdoména)</span></dt>
    <dd>
        <p>Subdoména řešená komoditizovaně — autentizace, posílání e-mailů, fakturace dle standardních pravidel. Standardní nákupní strategie: SaaS, open-source, knihovna třetí strany. Custom kód je v této kategorii anti-vzorem.</p>
        <p class="glossary-related">Detail: <a href="{{ path('subdomains') }}#sourcing">Subdomény — Sourcing strategie</a>.</p>
    </dd>
</div>
```

- [ ] **Step 7: Cross-linky v existujících kapitolách**

V `templates/ddd/what_is_ddd.html.twig` v sekci `01.04 Strategický design` (řádky ~155–196), za odrážku „Bounded Context" doplnit novou odrážku NA ZAČÁTEK seznamu:

```twig
<li>
    <strong>Subdomény (Core / Supporting / Generic)</strong> – Strategická klasifikace, která určuje, kam investovat modelovací úsilí. Detail v kapitole <a href="{{ path('subdomains') }}">Subdomény: Core, Supporting, Generic</a>.
</li>
```

V `templates/ddd/when_not_to_use_ddd.html.twig` v sekci, kde se diskutuje „nemáte komplexní doménu", doplnit:

```twig
<p>Pokud po průchodu pětibodovým testem v <a href="{{ path('subdomains') }}#rozpoznat-core">kapitole o subdoménách</a> nezůstane žádná Core doména, plné DDD pravděpodobně nestojí za náklady — zvažte CRUD + servisní vrstvu.</p>
```

V `templates/ddd/case_study.html.twig` na začátku, kde se představuje doména, doplnit větu odkazující na subdoménový pohled:

```twig
<p>V této případové studii vědomě označujeme <em>správu projektů a úkolů</em> jako Core, <em>fakturaci</em> jako Supporting a <em>autentizaci</em> jako Generic — viz <a href="{{ path('subdomains') }}">kapitolu o subdoménách</a> pro kritéria klasifikace.</p>
```

- [ ] **Step 8: Verifikace**

```bash
curl -fs http://127.0.0.1:8000/subdomeny > /dev/null && echo "page OK"
curl -s http://127.0.0.1:8000/subdomeny | grep -c '"@type": "TechArticle"' # → 1 (JSON-LD)
curl -s http://127.0.0.1:8000/subdomeny | grep -c 'Core Domain' # → ≥ 5
curl -s http://127.0.0.1:8000/strategie | grep -c 'Subdomény' # → ≥ 1 (hub karta)
php bin/console cache:clear --env=dev
```

Manuální vizuální kontrola pomocí Playwright MCP: navštívit `/subdomeny`, ověřit:
- TOC zobrazuje 10 sekcí
- Diagram `03.2-A` je viditelný a má funkční zoom toolbar
- FAQ se rozbaluje
- Breadcrumb v `<head>` JSON-LD obsahuje "Subdomény"
- Chapter nav prev/next: `← 02 Základní koncepty` / `Další → 04 Context Mapping` (pokud kap. 04 už existuje, jinak `→ 05 Vertikální slice`)

- [ ] **Step 9: Commit**

```bash
git add templates/ddd/subdomains.html.twig templates/diagrams/11_subdomains/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/what_is_ddd.html.twig templates/ddd/when_not_to_use_ddd.html.twig templates/ddd/case_study.html.twig
git commit -m "feat(content): kapitola 03 — Subdomény: Core, Supporting, Generic"
```

---

### Task 1.2: Kapitola 04 — Context Mapping

**Files:**
- Create: `templates/ddd/context_mapping.html.twig`
- Create: `templates/diagrams/12_context_mapping/context_map_patterns.puml` + `.svg`
- Create: `templates/diagrams/12_context_mapping/acl_anatomy.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig`
- Modify: `templates/ddd/glossary.html.twig` (8 nových termínů — všechny vztahy)
- Modify: `templates/ddd/what_is_ddd.html.twig` (cross-link)
- Modify: `templates/ddd/case_study.html.twig` (kapitola už používá context_map.svg, doplnit forward link)
- Modify: `templates/ddd/subdomains.html.twig` (forward link doplnit, byl tam tip „připravujeme")

**Goal:** Hloubkové vysvětlení **Context Mapu** a **8 vztahů mezi BC** (Partnership, Customer/Supplier, Conformist, ACL, Open Host Service, Published Language, Shared Kernel, Separate Ways) s implementačními příklady v Symfony Messenger.

- [ ] **Step 1: Route + action**

```php
    #[Route('/context-mapping', name: 'context_mapping')]
    public function contextMapping(): Response
    {
        return $this->render('ddd/context_mapping.html.twig', [
            'title' => 'Context Mapping — vztahy mezi Bounded Contexts',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '04', 'route' => 'context_mapping', 't' => 'Context Mapping', 'd' => 'Partnership · Customer/Supplier · Conformist · ACL · OHS · PL · SK · Separate Ways', 'time' => 28, 'lvl' => 3, 'tag' => 'Strategie', 'group' => 'strategic'],
```

- [ ] **Step 3: Aktualizovat `_strategic_routes`**

```twig
{%- set _strategic_routes = ['hub_strategic', 'subdomains', 'context_mapping'] -%}
```

- [ ] **Step 4: Diagram 1 — `context_map_patterns.puml`**

Hlavní mapa s 5 BC a všemi 8 typy vztahů:

```plantuml
@startuml
!include ../theme.iuml

title Context Map: 5 Bounded Contexts a všech 8 typů vztahů

rectangle "Catalog BC" as catalog $ACCENT
rectangle "Pricing BC" as pricing $ACCENT
rectangle "Ordering BC" as ordering
rectangle "Billing BC" as billing
rectangle "Identity BC\n(Auth0)" as identity #555555

' Partnership (Catalog ↔ Pricing — společný release)
catalog <-> pricing : <b>Partnership</b>\n(společný release)

' Shared Kernel (Catalog ↔ Pricing sdílí Money VO)
note bottom of pricing
  <b>Shared Kernel:</b>
  Money, Currency
end note

' Customer / Supplier (Ordering = customer, Catalog = supplier)
catalog -> ordering : <b>Customer/Supplier</b>\nproduktové ID + cena

' ACL (Ordering chrání před Billing legacy)
ordering -down-> billing : <b>ACL</b>\nOrderPlaced → Invoice
note right of billing
  Billing = legacy
  ACL chrání Ordering model
end note

' Conformist (Billing musí akceptovat Ordering eventy beze změn)
billing -up-> ordering : <i>(Conformist v opačném směru\npokud chybí ACL)</i>

' Open Host Service + Published Language (Identity)
identity -[#F0A456]-> ordering : <b>Open Host Service</b>\nREST + JWT (PL)
identity -[#F0A456]-> billing : <b>Open Host Service</b>

' Separate Ways (Marketing — zde zobrazený samostatně)
rectangle "Marketing BC\n(SendGrid)" as marketing #555555
note left of marketing
  <b>Separate Ways:</b>
  žádná integrace,
  vlastní mailing list
end note

@enduml
```

- [ ] **Step 5: Diagram 2 — `acl_anatomy.puml`**

Sekvenční diagram anatomie ACL (translator pattern):

```plantuml
@startuml
!include ../theme.iuml

title Anti-Corruption Layer: anatomie překladu Legacy → Domain

participant "Legacy Billing\n(SOAP)" as legacy
box "Ordering BC" #11141A
    participant "ACL\nLegacyBillingTranslator" as acl
    participant "Order\nAggregate" as order
end box

legacy -> acl : <i>InvoiceCreatedSoapResponse</i>\n{customerNumber, amountCents, ...}
note right of acl
  <b>3 odpovědnosti ACL:</b>
  1. Schema mapping (DTO → VO)
  2. Concept translation (customerNumber → CustomerId)
  3. Anti-corruption (filtrace neplatných stavů)
end note
acl -> acl : map(amountCents) → Money(EUR, 100.00)
acl -> acl : map(customerNumber) → CustomerId(uuid)
acl -> acl : if invalid → throw IncomingMessageRejected
acl -> order : applyInvoicePaid(InvoicePaidEvent)
@enduml
```

- [ ] **Step 6: Šablona — celá kapitola**

Hlavička standardní (viz [A3]):
- `title`: `Context Mapping — 8 vztahů mezi Bounded Contexts | DDD Symfony`
- `meta_description`: `Strategický DDD: Context Map a 8 vztahů mezi Bounded Contexts (Partnership, Customer/Supplier, Conformist, ACL, OHS, PL, Shared Kernel, Separate Ways) s ukázkami v Symfony 8.`
- `meta_keywords`: `Context Map, Context Mapping, Bounded Context, Anti-Corruption Layer, ACL, Open Host Service, Published Language, Shared Kernel, Customer Supplier, Conformist, Partnership, Separate Ways, Symfony Messenger`
- `breadcrumb_name`: `Context Mapping`

`article_head`: `chapter_number: '04'`, `category: 'Strategie'`, `reading_time: 28`, `difficulty: 3`, deck: „Bounded Context vám definuje hranici. Context Mapping vám definuje, co se na té hranici děje. Osm pojmenovaných vztahů, které popisují všechny způsoby, jak spolu BC komunikují — od těsné spolupráce po úmyslnou separaci."

**Sekce:**

1. `04.01 Co je Context Map a proč ji nakreslit` (`id="co-je-context-map"`)
   - Definice (Evans 2003 kap. 14): Context Map je vizuální + textová dokumentace všech BC v systému a vztahů mezi nimi. Není to UML class diagram — je to organizační mapa.
   - Proč: bez ní implicitní vztahy → integrační bugy, plíživé sdílení modelů (Big Ball of Mud).
   - **Vložit hlavní diagram** `context_map_patterns.svg` přes `_partials/diagram.html.twig` (`fig: '04.1-A'`).
   - Cross-link na `{{ path('basic_concepts') }}#bounded-contexts`.

2. `04.02 Osm typů vztahů — přehled` (`id="osm-typu-prehled"`)
   - **Tabulka v HTML** se sloupci: Vztah | Symetrický? | Kupplig | Použití | Kdo o něm rozhoduje
     | Partnership | Symetrický | Vysoký | Společné business cíle | Oba týmy |
     | Shared Kernel | Symetrický | Vysoký | Shared codebase modul | Oba týmy souhlasem |
     | Customer/Supplier | Asymetrický | Střední | Upstream poskytuje, downstream konzumuje | Upstream rozhoduje, downstream prioritizuje |
     | Conformist | Asymetrický | Střední | Downstream přijímá upstream model | Vynucené (downstream nemá vliv) |
     | Anti-Corruption Layer | Asymetrický | Nízký | Downstream chrání svůj model | Downstream rozhoduje |
     | Open Host Service | Asymetrický | Nízký | Upstream stabilizuje protokol | Upstream rozhoduje |
     | Published Language | Asymetrický | Nízký | Stabilizovaný formát zpráv | Upstream + standardy |
     | Separate Ways | — | Žádný | Žádná integrace | Strategické rozhodnutí |
   - Po tabulce krátké rozhodovací pravidlo: „Když downstream nemá kontrolu, ale chce chránit svůj model → ACL. Když má kontrolu → Customer/Supplier. Když vůbec nezáleží → Conformist."

3. `04.03 Partnership` (`id="partnership"`)
   - Definice + kdy: dva týmy, jeden release cyklus, sdílené cíle.
   - Příklad: Catalog BC + Pricing BC u jednoho startupu.
   - Symfony detail: monorepo, společné `composer.json`, společné nasazení.
   - Anti-pattern: Partnership jako default („nemáme čas se domluvit, takže to budeme dělat dohromady") — vede k Big Ball of Mud.
   - Callout `warn`: „Partnership = nákladná spolupráce. Použít jen když oba týmy explicitně podepíší ‚padáme nebo letíme spolu'."

4. `04.04 Shared Kernel` (`id="shared-kernel"`)
   - Definice: malý modul kódu sdílený mezi 2+ BC. Změna vyžaduje souhlas všech vlastníků.
   - **Code sample** — společný `SharedKernel\Money` package:
     ```php
     // shared-kernel/src/Money/Money.php
     namespace App\SharedKernel\Money;
     final readonly class Money
     {
         public function __construct(public int $amountCents, public Currency $currency) {}
         public function add(self $other): self { /* ... */ }
     }
     ```
   - Symfony detail: `composer.json` s lokálním path repo: `"repositories": [{"type": "path", "url": "shared-kernel/"}]`. Verzování přes git tag.
   - Anti-pattern: SK roste — kdykoliv je to „někdy užitečné". Pravidlo: SK musí být malý, neměnný a recenzovaný oběma týmy. Pokud roste → rozdělit na vlastní BC s OHS.

5. `04.05 Customer / Supplier` (`id="customer-supplier"`)
   - Definice: upstream (supplier) poskytuje, downstream (customer) konzumuje. Downstream má hlas (může požádat o feature), ale supplier rozhoduje o release.
   - Příklad: Catalog (supplier) → Ordering (customer). Ordering říká „potřebujeme `availableStock` v product DTO", Catalog se rozhodne kdy to dodá.
   - **Code sample** — Symfony Messenger external transport (downstream konzumuje upstream eventy):
     ```yaml
     # config/packages/messenger.yaml (downstream Ordering BC)
     framework:
         messenger:
             transports:
                 from_catalog: '%env(CATALOG_AMQP_DSN)%'
             routing:
                 'App\Ordering\Application\Event\ProductPriceChanged': from_catalog
     ```
   - Důležité: Customer/Supplier vyžaduje **stabilní kontrakt** — typicky kombinováno s OHS + PL.

6. `04.06 Conformist` (`id="conformist"`)
   - Definice: downstream se VZDÁ vlastního modelu a přijímá upstream model 1:1. Žádný překlad.
   - Kdy: upstream je natolik silný, že nemá smysl bojovat (typicky externí dodavatel, regulátor).
   - Příklad: Reporting BC, který přijímá Stripe payment objekty 1:1, bez transformace.
   - Trade-off: úspora překladu, ale celý downstream model je rukojmí upstreamu.
   - Callout `warn`: „Conformist je krátkodobá úleva s dlouhodobou cenou. Když upstream udělá breaking change, lámeš se s ním."

7. `04.07 Anti-Corruption Layer (ACL)` (`id="acl"`)
   - Definice: izolační vrstva mezi downstream modelem a cizím (legacy / externí) modelem. Překládá oběma směry, filtruje, validuje.
   - **Vložit diagram** `acl_anatomy.svg` (`fig: '04.7-A'`).
   - **Code sample** — kompletní `LegacyBillingTranslator`:
     ```php
     namespace App\Ordering\Infrastructure\Acl;

     final class LegacyBillingTranslator
     {
         public function __construct(private LegacyBillingClient $soap) {}

         public function translateInvoicePaid(InvoicePaidSoapResponse $r): InvoicePaidEvent
         {
             if ($r->amountCents < 0) {
                 throw new IncomingMessageRejected('Negative amount from legacy');
             }
             return new InvoicePaidEvent(
                 invoiceId: InvoiceId::fromLegacy($r->invoiceNumber),
                 paidAt:    new \DateTimeImmutable($r->paidAtIso),
                 amount:    Money::ofCents($r->amountCents, Currency::EUR),
             );
         }
     }
     ```
   - 3 odpovědnosti ACL: schema mapping, concept translation, anti-corruption (validace).
   - Antivzor: ACL „prosakuje" — downstream model začne obsahovat cizí pojmy. Pravidlo: ACL je single-purpose třída, ne layer s desítkami metod sdílejících state.
   - Cross-link na `{{ path('migration_from_crud') }}` (Strangler Fig používá ACL).

8. `04.08 Open Host Service (OHS)` (`id="ohs"`)
   - Definice: upstream definuje stabilní, dokumentovaný protokol pro mnoho downstream konzumentů.
   - Implementace v Symfony: typicky REST API (api-platform) nebo gRPC, plus formální schema (OpenAPI / .proto).
   - **Code sample** — minimální OHS endpoint s versioning:
     ```php
     #[Route('/api/v1/products/{id}', name: 'api_product_get', methods: ['GET'])]
     public function getProduct(string $id): JsonResponse
     {
         return $this->json($this->queryBus->ask(new GetProductQuery($id)),
                            context: ['groups' => ['ohs.v1']]);
     }
     ```
   - OHS se MUSÍ verzovat. Bez versioning to není OHS.

9. `04.09 Published Language (PL)` (`id="published-language"`)
   - Definice: dobře dokumentovaný formát, který si můžou všichni číst nezávisle. Často SCHEMA (JSON Schema, Avro, Protobuf).
   - Vztah k OHS: OHS je *kanál*, PL je *formát*.
   - Příklady standardů: CloudEvents, AsyncAPI, JSON Schema.
   - **Code sample** — JSON Schema pro `OrderPlaced` event publikovaný do RabbitMQ:
     ```json
     {
       "$schema": "https://json-schema.org/draft/2020-12/schema",
       "$id": "https://example.com/events/order-placed-v1.json",
       "title": "OrderPlaced",
       "type": "object",
       "required": ["eventId", "orderId", "customerId", "totalAmount", "currency", "occurredAt"],
       "properties": {
         "eventId":     { "type": "string", "format": "uuid" },
         "orderId":     { "type": "string", "format": "uuid" },
         "customerId":  { "type": "string", "format": "uuid" },
         "totalAmount": { "type": "integer", "minimum": 0 },
         "currency":    { "type": "string", "pattern": "^[A-Z]{3}$" },
         "occurredAt":  { "type": "string", "format": "date-time" }
       }
     }
     ```

10. `04.10 Separate Ways` (`id="separate-ways"`)
    - Definice: dva BC vědomě nejsou integrované. Přijímáme duplikát dat / proces místo integrace, protože integrace by byla dražší.
    - Příklad: Marketing posílá maily přes vlastní SendGrid list, který nesynchronizuje s Identity BC. Když se zákazník odhlásí v Identity, marketing pořád může poslat (s legal opt-out v patičce). Ne ideální, ale levné.
    - Kdy je to volba: low-value integrace + high-effort sync. Strategické rozhodnutí, ne lenost.

11. `04.11 Praktický postup — jak nakreslit Context Map za 90 minut` (`id="postup"`)
    - 5 kroků workshopu:
      1. Vyjmenovat všechny BC (sticky note každý).
      2. Pro každou dvojici BC, která spolu interaguje: nakreslit šipku, pojmenovat vztah z 8 typů.
      3. Označit upstream (U) a downstream (D) směr.
      4. Identifikovat „nebezpečné" vztahy (Conformist, Big Ball of Mud) → akční položky.
      5. Vyfotit, vložit do `docs/context-map.png` v repu, owner = architekt.
    - Tipový callout: „Context Map ZASTARÁVÁ. Přepište ji při každé větší architektonické změně. Datum + verze v patičce povinné."

12. `04.12 Anti-vzor: Big Ball of Mud` (`id="big-ball-of-mud"`)
    - Definice (Foote & Yoder 1997): nedokumentovaný, namixovaný systém, kde každý BC „nějak" mluví s každým, sdílí DB tabulky, sdílí entity. Anti-pattern Context Mappingu.
    - Cross-link na `{{ path('anti_patterns') }}`.

13. `04.13 Shrnutí` (`id="summary"`)
    - 5 bullet points: Context Map = mapa vztahů; 8 vztahů; ACL je nejčastěji potřebný; OHS+PL pro stabilitu; Big Ball of Mud = znamená „ještě jsme nedělali Context Map".

14. **FAQ** (4–6):
    - Jak často aktualizovat Context Map?
    - Můžu mít více než 1 typ vztahu mezi 2 BC?
    - ACL vs. Adapter — jaký rozdíl?
    - Co dělat, když si všimnu Conformist vztahu, který tam neměl být?
    - Je Context Map součást „Architecture Decision Record" (ADR)?
    - Jak Context Map kreslit v textu (ne nástrojem)?

- [ ] **Step 7: Doplnit glosář — 8 nových termínů**

V `templates/ddd/glossary.html.twig` v sekci „Strategické vzory DDD" přidat (po `term-context-map`):

Pro každý ze 7 vztahů (Partnership, Shared Kernel, Customer/Supplier — pravděpodobně už existuje, Conformist, ACL — existuje, OHS — existuje, PL — existuje, Separate Ways) zkontrolovat existenci a doplnit chybějící. Pro každý:

```html
<div class="glossary-entry" id="term-partnership">
    <dt><dfn>Partnership</dfn> <span class="glossary-en">(partnerství)</span></dt>
    <dd>
        <p>Symetrický vztah mezi dvěma Bounded Contexts, jejichž týmy sdílí cíl a release cyklus. Jeden tým neuspěje, pokud druhý neuspěje. Vyžaduje vědomé rozhodnutí, ne náhodu.</p>
        <p class="glossary-related">Detail: <a href="{{ path('context_mapping') }}#partnership">Context Mapping — Partnership</a>.</p>
    </dd>
</div>
```

A obdobně pro `term-shared-kernel`, `term-customer-supplier`, `term-conformist`, `term-acl` (pokud chybí), `term-open-host-service`, `term-published-language`, `term-separate-ways`. Definice 2–3 věty + odkaz na sekci.

- [ ] **Step 8: Cross-linky**

V `templates/ddd/what_is_ddd.html.twig` v sekci `01.04 Strategický design`, za bullet o Context Map, doplnit:

```twig
<li>Detail osmi vztahů mezi BC (Partnership, ACL, OHS, …) a praktického kreslení Context Mapy je v samostatné kapitole <a href="{{ path('context_mapping') }}">Context Mapping</a>.</li>
```

V `templates/ddd/case_study.html.twig` u sekce, kde se vkládá `15_case_study/context_map.svg`, doplnit větu pod diagram:

```twig
<p>Význam jednotlivých šipek a typů vztahů (ACL, OHS, Customer/Supplier) je vysvětlen v kapitole <a href="{{ path('context_mapping') }}">Context Mapping</a>.</p>
```

V `templates/ddd/subdomains.html.twig` (vznikla v Tasku 1.1) u sekce `03.05 Mapování subdomén na Bounded Contexts` nahradit „připravujeme" tip linkem na `{{ path('context_mapping') }}`.

- [ ] **Step 9: Verifikace**

```bash
curl -fs http://127.0.0.1:8000/context-mapping > /dev/null && echo "page OK"
curl -s http://127.0.0.1:8000/context-mapping | grep -c 'Anti-Corruption Layer' # → ≥ 5
curl -s http://127.0.0.1:8000/context-mapping | grep -c 'Open Host Service' # → ≥ 4
curl -s http://127.0.0.1:8000/glosar | grep -c 'term-partnership\|term-shared-kernel\|term-conformist' # → ≥ 3
```

Vizuální (Playwright): zkontrolovat oba diagramy, FAQ accordion, chapter-nav.

- [ ] **Step 10: Commit**

```bash
git add templates/ddd/context_mapping.html.twig templates/diagrams/12_context_mapping/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/what_is_ddd.html.twig templates/ddd/case_study.html.twig templates/ddd/subdomains.html.twig
git commit -m "feat(content): kapitola 04 — Context Mapping (8 vztahů mezi Bounded Contexts)"
```

---

### Task 1.3: Kapitola 06 — Architektonické styly: Hexagonal, Onion, Clean

**Files:**
- Create: `templates/ddd/architectural_styles.html.twig`
- Create: `templates/diagrams/13_architectural_styles/hexagonal_vs_onion.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig`
- Modify: `templates/ddd/glossary.html.twig` (Hexagonal už zmíněný — rozšířit; přidat Onion, Clean)
- Modify: `templates/ddd/horizontal_vs_vertical.html.twig` (cross-link)
- Modify: `templates/ddd/implementation_in_symfony.html.twig` (cross-link na styly)

**Goal:** Srovnat **Hexagonal (Ports & Adapters), Onion, Clean Architecture** vůči klasickému layered DDD a vertical slice. Ukázat, že DDD je *modelovací* technika, kterou *všechny* architektonické styly podporují — výběr stylu řeší odlišnou otázku než výběr DDD.

- [ ] **Step 1: Route + action**

```php
    #[Route('/architektonicke-styly', name: 'architectural_styles')]
    public function architecturalStyles(): Response
    {
        return $this->render('ddd/architectural_styles.html.twig', [
            'title' => 'Architektonické styly: Hexagonal, Onion, Clean',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '06', 'route' => 'architectural_styles', 't' => 'Architektonické styly', 'd' => 'Hexagonal · Onion · Clean Architecture vs. Layered a Vertical Slice', 'time' => 22, 'lvl' => 3, 'tag' => 'Základy', 'group' => 'basics'],
```

A přidat do `_basics_routes`:

```twig
{%- set _basics_routes = ['hub_basics', 'what_is_ddd', 'basic_concepts', 'horizontal_vs_vertical', 'architectural_styles', 'implementation_in_symfony'] -%}
```

- [ ] **Step 3: Diagram — `hexagonal_vs_onion.puml`**

Diagram porovnávající 4 styly side-by-side (4 panely):

```plantuml
@startuml
!include ../theme.iuml

title Čtyři architektonické styly aplikované na DDD

' === Layered (klasická vrstvená) ===
package "1. Layered (klasická)" {
    rectangle "Presentation" as l_pres
    rectangle "Application" as l_app
    rectangle "Domain" as l_dom $ACCENT
    rectangle "Infrastructure" as l_inf
    l_pres -down-> l_app
    l_app -down-> l_dom
    l_dom -down-> l_inf
    note right of l_inf
      <i>Doménové třídy závisí na Doctrine.</i>
      Vrstva pod nimi.
    end note
}

' === Hexagonal (Ports & Adapters) ===
package "2. Hexagonal (Cockburn 2005)" {
    rectangle "Domain Core" as h_core $ACCENT
    rectangle "Inbound Port\n<<interface>>" as h_in
    rectangle "Outbound Port\n<<interface>>" as h_out
    rectangle "HTTP Adapter" as h_http
    rectangle "Doctrine Adapter" as h_doc
    h_http -right-> h_in
    h_in -right-> h_core
    h_core -right-> h_out
    h_out -right-> h_doc
    note bottom of h_core
      Doména závisí jen na portech.
      Adaptéry jsou plug-in.
    end note
}

' === Onion (Palermo 2008) ===
package "3. Onion" {
    rectangle "Domain Model" as o_dom $ACCENT
    rectangle "Domain Services" as o_ds
    rectangle "Application Services" as o_as
    rectangle "UI / Infra" as o_ui
    o_ui -down-> o_as
    o_as -down-> o_ds
    o_ds -down-> o_dom
    note right of o_dom
      Závislosti vždy směřují DOVNITŘ.
      Doména nezná infra.
    end note
}

' === Clean (Martin 2012) ===
package "4. Clean (Martin)" {
    rectangle "Entities" as c_ent $ACCENT
    rectangle "Use Cases" as c_uc
    rectangle "Interface\nAdapters" as c_ia
    rectangle "Frameworks &\nDrivers" as c_fd
    c_fd -left-> c_ia
    c_ia -left-> c_uc
    c_uc -left-> c_ent
    note bottom of c_ent
      4 koncentrické vrstvy.
      Dependency Rule = jednosměrná.
    end note
}

@enduml
```

- [ ] **Step 4: Šablona — celá kapitola**

Hlavička:
- `title`: `Hexagonal, Onion, Clean Architecture — co si vybrat | DDD Symfony`
- `meta_description`: `Srovnání architektonických stylů (Layered, Hexagonal, Onion, Clean) v kontextu DDD a Symfony. Praktický návod, kdy který volit a jaký dopad to má na strukturu Symfony projektu.`
- `meta_keywords`: `Hexagonal Architecture, Ports and Adapters, Onion Architecture, Clean Architecture, Layered Architecture, Vertical Slice, DDD, Symfony, Cockburn, Palermo, Martin, Dependency Rule`
- `breadcrumb_name`: `Architektonické styly`

`article_head`: `chapter_number: '06'`, `category: 'Základy'`, `reading_time: 22`, `difficulty: 3`, deck: „DDD vám říká *co* modelovat. Architektonický styl říká *kam* to modelované strčit. Čtyři škol — klasická vrstvená, Hexagonální (Cockburn), Onion (Palermo), Clean (Martin) — a vertikální slice jako pátá. Kapitola srovnává jejich odlišnosti, podobnosti a co vybrat v Symfony 8 projektu."

**Sekce:**

1. `06.01 Proč architektonický styl není totéž co DDD` (`id="proc-styl"`)
   - Otevírací: častý zmatek — „přejdeme na DDD" mnohdy znamená „přejdeme na Hexagonal". Jsou to ortogonální rozhodnutí.
   - Diagram osy: vodorovná osa „modelovací technika" (CRUD ↔ DDD), svislá osa „uspořádání kódu" (layered ↔ hexagonal ↔ vertical slice).
   - Důležité: DDD lze dělat v Layered i v Hexagonal i v Vertical Slice. Architektonický styl ovlivňuje *kompozici a testovatelnost*, ne *modelovací metodu*.

2. `06.02 Layered (klasická vrstvená)` (`id="layered"`)
   - Definice: Presentation / Application / Domain / Infrastructure jako svislé vrstvy.
   - **Code sample** — typická Symfony struktura:
     ```
     src/
       Controller/      ← Presentation
       Service/         ← Application
       Entity/          ← Domain (s Doctrine anotacemi! → leak)
       Repository/      ← Infrastructure
     ```
   - Problém: Doctrine anotace na doménových třídách vytváří závislost na infrastruktuře.
   - Kdy se hodí: jednoduché aplikace, junior tým, velmi čitelná struktura.
   - Anti-pattern v DDD: anémické entity díky tomu, že entita je „jenom data + Doctrine".

3. `06.03 Hexagonal Architecture (Ports & Adapters, Cockburn 2005)` (`id="hexagonal"`)
   - Definice: doména komunikuje s vnějším světem **jen přes porty (interfaces)**. Adaptéry implementují porty.
   - 2 typy portů: **Inbound (driving)** — co aplikace umí; **Outbound (driven)** — co aplikace potřebuje.
   - **Code sample** — outbound port + adapter:
     ```php
     // Domain — port (interface)
     namespace App\Ordering\Domain\Port;
     interface OrderRepository
     {
         public function get(OrderId $id): ?Order;
         public function save(Order $order): void;
     }

     // Infrastructure — Doctrine adapter
     namespace App\Ordering\Infrastructure\Adapter;
     final class DoctrineOrderRepository implements OrderRepository
     {
         public function __construct(private EntityManagerInterface $em) {}
         public function get(OrderId $id): ?Order { /* ... */ }
         public function save(Order $order): void { /* ... */ }
     }
     ```
   - Symfony specifika: Service Container automaticky binduje interface → implementaci přes `#[Autowire]` nebo `services.yaml`.
   - **Diagram** — vložit `hexagonal_vs_onion.svg` (`fig: '06.3-A'`).

4. `06.04 Onion Architecture (Palermo 2008)` (`id="onion"`)
   - Definice: 4 koncentrické vrstvy: Domain Model → Domain Services → Application Services → UI/Infra. **Dependency Rule:** závislosti vždy směřují dovnitř.
   - Rozdíl proti Hexagonal: Onion explicitně rozlišuje Domain Services a Application Services jako 2 vrstvy. Hexagonal je topologicky jednodušší.
   - Code sample: ukázka Domain Service vs. Application Service rozhraní.
   - Kdy: domény s hodně doménovými službami (např. risk scoring, pricing engine).

5. `06.05 Clean Architecture (Robert C. Martin 2012)` (`id="clean"`)
   - Definice: Onion + explicitně pojmenované „Use Cases" jako vlastní vrstva. 4 prstence: Entities, Use Cases, Interface Adapters, Frameworks & Drivers.
   - Co Clean přidává: vyzdvihuje use case jako prvotřídní koncept (≈ Application Service v DDD = Use Case).
   - **Code sample** — Use Case jako třída + Request/Response DTOs:
     ```php
     final readonly class PlaceOrderRequest {
         public function __construct(
             public string $customerId,
             public array $items,
         ) {}
     }
     final readonly class PlaceOrderResponse {
         public function __construct(public string $orderId) {}
     }
     final class PlaceOrderUseCase
     {
         public function __construct(
             private OrderRepository $orders,
             private CustomerRepository $customers,
         ) {}
         public function execute(PlaceOrderRequest $req): PlaceOrderResponse { /* ... */ }
     }
     ```
   - Vztah k CQRS: PlaceOrderUseCase ≈ CommandHandler. Detail v `{{ path('cqrs') }}`.

6. `06.06 Vertical Slice Architecture` (`id="vertical-slice"`)
   - Krátká rekapitulace (detail v `{{ path('horizontal_vs_vertical') }}`).
   - Klíčový rozdíl: VS organizuje kód podle *feature*, ne podle *vrstvy*. Hexagonal/Onion/Clean jsou *vrstvové*. VS lze kombinovat s Hexagonal (každý slice má svoje porty).

7. `06.07 Praktické srovnání — co si vybrat v Symfony 8` (`id="srovnani"`)
   - **Tabulka** rozhodovací matice:
     | Faktor | Layered | Hexagonal | Onion | Clean | Vertical Slice |
     | Křivka učení | nízká | střední | střední | vysoká | nízká |
     | Junior friendly | ✓✓✓ | ✓ | ✓ | ✗ | ✓✓ |
     | Test isolation | nízká | vysoká | vysoká | vysoká | střední |
     | Doctrine integrace | tight | loose (přes adapter) | loose | loose | flexibilní |
     | Doporučená velikost | <50 endpointů | 50–500 | 100+ | enterprise | 50–500 |
   - Doporučení autora: **Hexagonal + Vertical Slice combo** je nejlepší default pro Symfony 8 středně velký projekt s DDD.

8. `06.08 Hybridní přístup — Hexagonal core, Layered okraje` (`id="hybrid"`)
   - Realistický postup: Core Domain (z `{{ path('subdomains') }}`) → plný Hexagonal; Supporting → Layered s lehkým DDD; Generic → tenký adapter na SaaS.
   - Code sample: jak vypadá `src/` s tímto rozložením.

9. `06.09 Anti-vzory` (`id="antivzory"`)
   - „Hexagonal kult" — every CRUD endpoint dostane port + adapter, mass over-engineering.
   - „Domain leakage" — Doctrine anotace na doménových třídách (klasický Layered problém přenesený do Hexagonal).
   - „Anemic Hexagonal" — porty + adaptéry jsou krásně oddělené, ale za nimi sedí anémické entity.
   - Cross-link na `{{ path('anti_patterns') }}`.

10. `06.10 Shrnutí` (`id="summary"`)
    - 4 body.

11. **FAQ:** Hexagonal vs. Onion — jaký je praktický rozdíl? · Můžu použít Hexagonal bez DDD? · Jak migrovat z Layered na Hexagonal? · Co je ‚Ports' přesně? · Vyplatí se Clean v malé Symfony aplikaci?

- [ ] **Step 5: Glosář — Onion, Clean (Hexagonal už existuje, rozšířit cross-link)**

Přidat do glosáře (sekce „Architektonické vzory"):

```html
<div class="glossary-entry" id="term-onion-architecture">
    <dt><dfn>Onion Architecture</dfn></dt>
    <dd>
        <p>Architektonický styl (Jeffrey Palermo, 2008) organizující kód do koncentrických vrstev s pravidlem „závislosti směřují dovnitř". Doménový model je jádro, kolem něj domain services, application services, UI/infrastruktura na okraji.</p>
        <p class="glossary-related">Detail: <a href="{{ path('architectural_styles') }}#onion">Architektonické styly</a>.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-clean-architecture">
    <dt><dfn>Clean Architecture</dfn></dt>
    <dd>
        <p>Robert C. Martin (2012) zobecnil Hexagonal a Onion do čtyřprstencového modelu Entities → Use Cases → Interface Adapters → Frameworks & Drivers. Vyzdvihuje Use Case jako prvotřídní koncept.</p>
        <p class="glossary-related">Detail: <a href="{{ path('architectural_styles') }}#clean">Architektonické styly</a>.</p>
    </dd>
</div>
```

A v existujícím `term-hexagonal-architecture` doplnit do `glossary-related`:
`Praktický výběr stylu: <a href="{{ path('architectural_styles') }}">Architektonické styly</a>.`

- [ ] **Step 6: Cross-linky**

V `templates/ddd/horizontal_vs_vertical.html.twig` v úvodu doplnit:

```twig
<p>Vertikální slicing je jen jeden ze čtyř způsobů, jak organizovat kód. Pro srovnání s Hexagonal, Onion a Clean Architecture viz kapitolu <a href="{{ path('architectural_styles') }}">Architektonické styly</a>.</p>
```

V `templates/ddd/implementation_in_symfony.html.twig` v sekci o struktuře projektu doplnit odkaz na styly.

- [ ] **Step 7: Verifikace**

```bash
curl -fs http://127.0.0.1:8000/architektonicke-styly > /dev/null && echo "page OK"
curl -s http://127.0.0.1:8000/architektonicke-styly | grep -c 'Hexagonal\|Onion\|Clean Architecture' # → ≥ 10
```

- [ ] **Step 8: Commit**

```bash
git add templates/ddd/architectural_styles.html.twig templates/diagrams/13_architectural_styles/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/horizontal_vs_vertical.html.twig templates/ddd/implementation_in_symfony.html.twig
git commit -m "feat(content): kapitola 06 — Architektonické styly (Hexagonal, Onion, Clean)"
```

---

## PHASE 2 — Discovery a tým (2 kapitoly)

### Task 2.1: Kapitola 14 — Event Storming + Domain Storytelling

**Files:**
- Create: `templates/ddd/event_storming.html.twig`
- Create: `templates/diagrams/17_event_storming/big_picture_levels.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig` (`_practice_routes`)
- Modify: `templates/ddd/glossary.html.twig` (Event Storming, Domain Storytelling, Pivotal Event, Hot Spot)
- Modify: `templates/ddd/event_sourcing.html.twig` (cross-link — discovery technique před implementací)
- Modify: `templates/ddd/migration_from_crud.html.twig` (cross-link — Event Storming jako vstupní krok migrace)
- Modify: `templates/ddd/resources.html.twig` (Brandolini: *Introducing EventStorming*; Hofer/Schwentner: *Domain Storytelling*)

**Goal:** Dát čtenáři **prakticky proveditelný workshop** Event Stormingu (3 úrovně: Big Picture, Process, Design) a Domain Storytellingu jako alternativy. Nepsat o tom abstraktně — popsat, co kupujete, jak připravit místnost / Miro, jak řídit, jaký výstup.

- [ ] **Step 1: Route + action**

```php
    #[Route('/event-storming', name: 'event_storming')]
    public function eventStorming(): Response
    {
        return $this->render('ddd/event_storming.html.twig', [
            'title' => 'Event Storming a Domain Storytelling',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '14', 'route' => 'event_storming', 't' => 'Event Storming a Domain Storytelling', 'd' => 'Workshopy pro objevení domény před první řádkou kódu', 'time' => 25, 'lvl' => 2, 'tag' => 'Praxe', 'group' => 'practice'],
```

A do `_practice_routes` v `base.html.twig` na začátek (logicky před zbytkem praxe):

```twig
{%- set _practice_routes = ['hub_practice', 'event_storming', 'team_topologies', 'authorization_in_ddd', 'microservices_and_ddd', 'practical_examples', 'testing_ddd', 'migration_from_crud', 'ddd_pain_points', 'anti_patterns', 'when_not_to_use_ddd', 'case_study'] -%}
```

- [ ] **Step 3: Diagram — `big_picture_levels.puml`**

Tři úrovně Event Stormingu jako tři rovnoběžné svislé sloupce s notační legendou:

```plantuml
@startuml
!include ../theme.iuml

title Tři úrovně Event Stormingu (Brandolini)

' === Legenda barev ===
legend top
  | Barva | Notace | Význam |
  |<#FFB300>| Domain Event | Co se stalo (minulý čas) |
  |<#1976D2>| Command | Záměr (rozkaz) |
  |<#FFEB3B>| Actor | Kdo to udělal |
  |<#9E9E9E>| External System | Mimo doménu |
  |<#E91E63>| Hot Spot | Otázka / problém |
  |<#7CB342>| Policy | Reaktivní pravidlo |
endlegend

package "1. Big Picture\n(2-4h, ~10 lidí)" {
    rectangle "OrderPlaced" as e1 #FFB300
    rectangle "PaymentReceived" as e2 #FFB300
    rectangle "ShipmentDispatched" as e3 #FFB300
    rectangle "OrderCancelled" as e4 #FFB300
    rectangle "Co když platba selže?" as h1 #E91E63
    e1 -right-> e2
    e2 -right-> e3
    e2 .down. h1
}

package "2. Process Level\n(4-8h)" {
    rectangle "Customer" as a1 #FFEB3B
    rectangle "PlaceOrder" as c1 #1976D2
    rectangle "OrderPlaced" as e5 #FFB300
    rectangle "Send confirmation\nemail" as p1 #7CB342
    rectangle "Stripe" as ext1 #9E9E9E
    a1 -right-> c1
    c1 -right-> e5
    e5 -right-> p1
    e5 -right-> ext1
}

package "3. Design Level\n(per Bounded Context)" {
    rectangle "Order Aggregate\n+ invariants" as ag1 #FF9800
    rectangle "ConfirmOrder cmd\n→ Order.confirm()" as cmd1 #1976D2
    rectangle "OrderConfirmed event" as e6 #FFB300
    cmd1 -right-> ag1
    ag1 -right-> e6
}

@enduml
```

- [ ] **Step 4: Šablona — kompletní obsah**

Hlavička:
- `title`: `Event Storming a Domain Storytelling — workshop pro objevení domény | DDD Symfony`
- `meta_description`: `Praktický návod na Event Storming (Brandolini) a Domain Storytelling: jak připravit, vést a vyhodnotit workshop, na jehož konci máte BC, eventy a první kandidáty na agregáty.`
- `meta_keywords`: `Event Storming, Domain Storytelling, Alberto Brandolini, Stefan Hofer, Henning Schwentner, Domain Discovery, DDD workshop, Big Picture, Process Level, Design Level`
- `breadcrumb_name`: `Event Storming`

`article_head`: `chapter_number: '14'`, `category: 'Praxe'`, `reading_time: 25`, `difficulty: 2`, deck: „Před první řádkou kódu byste měli odejít od počítače. Event Storming Alberta Brandoliniho a Domain Storytelling Hofera & Schwentnera jsou dvě nejprověřenější workshopové techniky, jak v jedné místnosti dostat do shody vývojáře s doménovými experty. Návod, který v Symfony projektu skutečně funguje."

**Sekce:**

1. `14.01 Proč workshop, proč ne čtení dokumentace` (`id="proc-workshop"`)
   - Otevírací: dokumentace zachycuje *záznam* domény, ne její *aktuální chápání*. Doménový expert si protiřečí — to je signál, ne bug. Workshop tu kontradikci VIDÍ a řeší.
   - Cross-link na `{{ path('what_is_ddd') }}#strategic-design` (Ubiquitous Language).

2. `14.02 Event Storming — co to je a co umí` (`id="event-storming-co"`)
   - Definice (Brandolini 2013): kolaborativní modelovací technika, kde účastníci v reálném čase pokládají oranžové sticky notes s **doménovými událostmi v minulém čase** na časovou osu.
   - Tři úrovně: Big Picture (strategický), Process Level (operační), Design Level (taktický).
   - **Vložit diagram** (`fig: '14.2-A'`).
   - Citace: Brandolini *Introducing EventStorming* (2021); Vernon, **DDD Distilled** (2016) kap. 7.

3. `14.03 Notace — barvy a tvary` (`id="notace"`)
   - Tabulka:
     | Barva | Tvar | Význam |
     | Oranžová | sticky | Domain Event (`OrderPlaced`) |
     | Modrá | sticky | Command (`PlaceOrder`) |
     | Žlutá | sticky | Actor / Aktér |
     | Šedá | sticky | External System |
     | Růžová | sticky | Hot Spot — otázka, kontroverze |
     | Zelená | sticky | Policy / Reactive logic |
     | Fialová | sticky | Bounded Context boundary |
   - Důležité pravidlo: **events v minulém čase**. „Order placed", ne „Place order". Jazykově to nutí mluvit o tom, co se stalo, ne co bychom rádi.

4. `14.04 Big Picture workshop — návod krok za krokem` (`id="big-picture"`)
   - **Příprava:** 4-8 m dlouhá zeď nebo Miro frame, oranžové stickies, fixy, 6-12 účastníků (devs, doménoví experti, PM, jeden facilitátor).
   - **Časování:** 2-4 hodiny.
   - **Postup:**
     1. (10 min) Všichni dostanou stejně oranžových stickies, píšou eventy, které je napadnou. Lepí na zeď bez pořadí.
     2. (30 min) **Time enforcement** — facilitátor přesouvá eventy doleva (raně) doprava (později). Vznikne timeline.
     3. (30 min) **Pivotal Events** — facilitátor identifikuje 3-5 zlomových bodů (např. „CustomerRegistered", „OrderPlaced", „PaymentSettled") a označí je. Bounded Contexty se hledají kolem nich.
     4. (45 min) **Hot Spots** — kdykoliv je něco kontroverzní, růžová sticky s otázkou. Nebrání se diskuse, jen zaznamenává.
     5. (30 min) **Bounded Contexty** — fialovými stickies / čarami označit hranice. Tipicky 3-7 BC.
     6. (15 min) Foto, transkripce, rozeslání.
   - Callout `tip`: „Facilitátor NESMÍ být tech lead. Tech lead má názor, který by potlačil doménové experty."

5. `14.05 Process Level — jeden BC, hlubší detail` (`id="process-level"`)
   - Přidává: Commands, Actors, Policies, External Systems, Read Models.
   - Příklad sekvence: `Customer (actor) → PlaceOrder (command) → OrderPlaced (event) → "Send confirmation email" (policy) → SendGrid (external)`.
   - Output: kandidáti na **Application Services** a **Sagy / Process Managery** (cross-link na `{{ path('sagas') }}`).

6. `14.06 Design Level — pro každý BC zvlášť` (`id="design-level"`)
   - Přidává Aggregate boundaries: pro každý command kontrolovat, který Aggregate ho obsluhuje a jaké invarianty drží.
   - Output: první draft tříd v Symfony — Aggregate roots, doménové eventy, Application Services.
   - Code sample (mapping z workshop output do PHP):
     ```php
     // Z workshop:  PlaceOrder (cmd) → Order Aggregate → OrderPlaced (event)
     namespace App\Ordering\Application\Command;
     final readonly class PlaceOrderCommand
     {
         public function __construct(
             public CustomerId $customerId,
             public array $items,
         ) {}
     }
     namespace App\Ordering\Domain;
     final class Order
     {
         public static function place(CustomerId $cust, array $items): self { /* ... */ }
         public function release(): array { return $this->releasedEvents; }
     }
     namespace App\Ordering\Application\Handler;
     final class PlaceOrderHandler
     {
         public function __invoke(PlaceOrderCommand $c): OrderId { /* ... */ }
     }
     ```

7. `14.07 Domain Storytelling — alternativa pro malé týmy` (`id="domain-storytelling"`)
   - Definice (Hofer & Schwentner 2021): namalujte příběh o tom, jak doménový expert pracuje, použitím standardizované piktogramové notace.
   - Notace: Actor, Work Object, Activity (spojnice s číslem v pořadí), Annotation.
   - Kdy zvolit DS místo ES: 2-5 lidí, hodina času, hluboká diskuse o jednom konkrétním procesu (ne celý systém).
   - Tooling: papír / Miro / aplikace egon.io (open source).
   - Příklad konkrétní story (HTML strukturovaný `<ol>` s 5-6 kroky).

8. `14.08 Anti-vzory workshopů` (`id="anti-vzory"`)
   - „Nesmíme udělat workshop, doménoví experti nemají čas" — bez expertů se to nedělá. Najít byť 90 minut.
   - „Začneme rovnou Design Level" — bez Big Picture se opomenou klíčová BC.
   - „Workshop facilituje senior dev" — podsouvá technický pohled.
   - „Zápis = Word dokument" — ztratí se vizuální struktura. Foto/Miro export povinné.
   - „Po workshopu se to nezapíše do kódu" — komentáře v `Order.php` typu „workshop 2025-04-29: pivotal event = OrderConfirmed".

9. `14.09 Po workshopu — co s výstupem` (`id="po-workshopu"`)
   - 4 výstupy do repa:
     1. Foto / Miro link → `docs/discovery/<datum>/board.png`.
     2. Seznam BC → aktualizovaný Context Map (`docs/context-map.png`).
     3. Seznam doménových eventů → `docs/discovery/<datum>/events.md` (jeden řádek na event, jako reference pro budoucí PR).
     4. Hot Spots → tickety v issue trackeru (1 hot spot = 1 ticket).
   - Cross-link na `{{ path('context_mapping') }}` a `{{ path('subdomains') }}`.

10. `14.10 Pravidelné re-stormingy` (`id="re-storming"`)
    - Doména se vyvíjí — workshop opakovat 1× za 6 měsíců nebo při velkém produktovém rozhodnutí.
    - Diff předchozí mapy a nové = priorita pro refaktoring.

11. `14.11 Shrnutí` (`id="summary"`)

12. **FAQ:** Kolik lidí na workshop? · Online vs. offline ES? · Jak vést Hot Spots? · Kdo platí — produkt nebo engineering? · Co když doménoví experti nemluví anglicky / na `Order placed` říkají „posrání zákazníka"? · Když máme jen sólo developer + PM, dá se ES dělat ve dvou?

- [ ] **Step 5: Glosář — 4 nové termíny**

```html
<div class="glossary-entry" id="term-domain-storytelling">
    <dt><dfn>Domain Storytelling</dfn></dt>
    <dd>
        <p>Workshopová technika (Stefan Hofer & Henning Schwentner, 2021) pro mapování doménových procesů piktogramovou notací (actor, work object, activity). Alternativa k Event Stormingu pro malé týmy a hluboké procesy.</p>
        <p class="glossary-related">Detail: <a href="{{ path('event_storming') }}#domain-storytelling">Event Storming a Domain Storytelling</a>.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-pivotal-event">
    <dt><dfn>Pivotal Event</dfn></dt>
    <dd>
        <p>V Event Stormingu zlomový doménový event, kolem kterého se přirozeně sdružuje skupina dalších eventů a často indikuje hranici Bounded Contextu. Typicky 3–5 pivotal eventů na celý systém.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-hot-spot">
    <dt><dfn>Hot Spot</dfn></dt>
    <dd>
        <p>V Event Stormingu označuje kontroverzní bod — otázka, kterou účastníci nedokáží okamžitě rozhodnout. Nebrání se diskuze, jen se zaznamenává jako růžová sticky a později řeší jako samostatný issue.</p>
    </dd>
</div>
```

A rozšířit existující `term-event-storming` o link na novou kapitolu.

- [ ] **Step 6: Cross-linky**

V `templates/ddd/event_sourcing.html.twig` v úvodní sekci doplnit:

```twig
<p>Než se pustíte do Event Sourcingu, potřebujete vědět, <em>jaké</em> eventy modelovat. Doporučený postup je workshop Event Storming — viz kapitolu <a href="{{ path('event_storming') }}">Event Storming a Domain Storytelling</a>.</p>
```

V `templates/ddd/migration_from_crud.html.twig` u sekce o úvodním kroku migrace:

```twig
<p>První krok migrace z CRUD na DDD je vždy <strong>Big Picture Event Storming</strong> — viz <a href="{{ path('event_storming') }}#big-picture">návod</a>. Bez něj se nedá určit, kde leží Bounded Contexty, a Strangler Fig nemá co stranglovat.</p>
```

V `templates/ddd/resources.html.twig` přidat do sekce knih:

```html
<li><a href="https://leanpub.com/introducing_eventstorming" target="_blank" rel="noopener">Alberto Brandolini — Introducing EventStorming (2021)</a></li>
<li><a href="https://domainstorytelling.org/" target="_blank" rel="noopener">Stefan Hofer & Henning Schwentner — Domain Storytelling (2021)</a></li>
<li><a href="https://egon.io/" target="_blank" rel="noopener">egon.io — open-source nástroj pro Domain Storytelling</a></li>
```

- [ ] **Step 7: Verifikace**

```bash
curl -fs http://127.0.0.1:8000/event-storming > /dev/null && echo "page OK"
curl -s http://127.0.0.1:8000/event-storming | grep -c 'Brandolini\|Hofer\|Schwentner' # → ≥ 3
```

- [ ] **Step 8: Commit**

```bash
git add templates/ddd/event_storming.html.twig templates/diagrams/17_event_storming/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/event_sourcing.html.twig templates/ddd/migration_from_crud.html.twig templates/ddd/resources.html.twig
git commit -m "feat(content): kapitola 14 — Event Storming a Domain Storytelling"
```

---

### Task 2.2: Kapitola 15 — Conway's Law a Team Topologies

**Files:**
- Create: `templates/ddd/team_topologies.html.twig`
- Create: `templates/diagrams/18_team_topologies/conway_inverse.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig`
- Modify: `templates/ddd/glossary.html.twig` (Conway's Law, Team Topologies, Stream-aligned team, Platform team, Enabling team, Complicated subsystem team)
- Modify: `templates/ddd/context_mapping.html.twig` (cross-link — Context Map ≈ Team Topology)
- Modify: `templates/ddd/subdomains.html.twig` (cross-link — Core domain → stream-aligned team)
- Modify: `templates/ddd/resources.html.twig` (Skelton & Pais — Team Topologies)

**Goal:** Vysvětlit, **proč organizační struktura týmu zrcadlí architekturu**, jak DDD Bounded Contexts a Team Topologies (Skelton & Pais 2019) spolu souvisejí, a jak vědomě navrhnout týmy tak, aby podporovaly žádanou architekturu (Inverse Conway Maneuver).

- [ ] **Step 1: Route + action**

```php
    #[Route('/team-topologies', name: 'team_topologies')]
    public function teamTopologies(): Response
    {
        return $this->render('ddd/team_topologies.html.twig', [
            'title' => 'Conway\'s Law a Team Topologies',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '15', 'route' => 'team_topologies', 't' => 'Conway\'s Law a Team Topologies', 'd' => 'Inverse Conway Maneuver — týmová struktura jako vědomé rozhodnutí', 'time' => 22, 'lvl' => 2, 'tag' => 'Praxe', 'group' => 'practice'],
```

- [ ] **Step 3: Diagram — `conway_inverse.puml`**

Levá strana: klasický Conway (organizační struktura → architektura). Pravá strana: Inverse Conway (chtěná architektura → reorganizace týmů).

```plantuml
@startuml
!include ../theme.iuml

title Conway vs. Inverse Conway Maneuver

' === Levá: klasický Conway ===
package "Conway's Law (původní)" {
    rectangle "Org chart\n3 týmy:\nFrontend / Backend / DBA" as org1
    rectangle "Software\n3 monolity:\nUI app / API / DB views" as sw1
    org1 -down-> sw1 : <b>vede k</b>
    note bottom of sw1
      Architektura kopíruje
      komunikační hranice týmů.
      I když to není optimální.
    end note
}

' === Pravá: Inverse Conway ===
package "Inverse Conway Maneuver" {
    rectangle "Cílová architektura:\n4 Bounded Contexts\n(Catalog, Ordering,\nBilling, Identity)" as sw2 $ACCENT
    rectangle "Reorganizace na 4 stream-aligned\ntýmy + 1 Platform team\n(observability, CI/CD)" as org2
    sw2 -down-> org2 : <b>diktuje</b>
    note bottom of org2
      Nejdřív rozhodneme architekturu,
      pak postavíme týmy tak, aby ji
      přirozeně produkovaly.
    end note
}

org1 -[hidden]right- sw2

@enduml
```

- [ ] **Step 4: Šablona — celá kapitola**

Hlavička:
- `title`: `Conway's Law a Team Topologies — týmová struktura v DDD | DDD Symfony`
- `meta_description`: `Bounded Context není jen architektonický artefakt — je to týmová hranice. Conway's Law, Team Topologies (Skelton & Pais), Inverse Conway Maneuver a praktické tipy pro rozdělení týmů kolem DDD.`
- `breadcrumb_name`: `Team Topologies`

`article_head`: `chapter_number: '15'`, `category: 'Praxe'`, `reading_time: 22`, `difficulty: 2`, deck: „Když Conway v roce 1967 napsal ‚systém kopíruje komunikační strukturu organizace, která ho stvořila', popisoval gravitační zákon softwarového designu. DDD Bounded Contexts dávají smysl jen tehdy, když mapují na týmy — jinak vznikají falešné hranice. Kapitola o tom, jak vědomě navrhnout týmy kolem domény."

**Sekce:**

1. `15.01 Conway's Law — gravitační zákon softwarové architektury` (`id="conway-law"`)
   - Citace Conway (1967): „Any organization that designs a system... will produce a design whose structure is a copy of the organization's communication structure."
   - 3 případy z praxe: tým rozdělený podle vrstev → Layered Architecture; tým rozdělený podle produktu → microservices; tým bez hranic → Big Ball of Mud.
   - **Vložit diagram** (`fig: '15.1-A'`).

2. `15.02 Bounded Context = týmová hranice` (`id="bc-team-boundary"`)
   - Pravidlo Vernon (2013): jeden Bounded Context = jeden tým. Sdílený BC mezi více týmy → Conway interferuje, vznikne Big Ball of Mud nebo Shared Kernel s režií.
   - Naopak jeden tým může vlastnit více BC (typické pro malé organizace).
   - Cross-link na `{{ path('context_mapping') }}#shared-kernel`.

3. `15.03 Team Topologies — 4 typy týmů (Skelton & Pais 2019)` (`id="team-topologies-typy"`)
   - **Stream-aligned team** — vlastník end-to-end value streamu (typicky 1 BC). 5-9 lidí. Rozhoduje a deliveruje samostatně.
   - **Platform team** — poskytuje *self-service platform* pro stream-aligned týmy (CI/CD, observability, K8s, šablony pro nové BC).
   - **Enabling team** — pomáhá stream-aligned týmům zvládnout novou technologii / techniku (např. „naučte je BDD" nebo „zaveďte CQRS"). Time-boxed angažmá.
   - **Complicated-subsystem team** — vlastní algoritmicky náročnou doménu, kterou by stream-aligned tým nezvládl (real-time risk engine, ML scoring).
   - Tabulka mapování na DDD subdomény:
     | Subdoména | Typ týmu | Důvod |
     | Core | Stream-aligned (1 BC) nebo Complicated-subsystem | konkurenční výhoda |
     | Supporting | Stream-aligned (sdílí BC s jiným týmem) | nediferencuje, ale potřebuje |
     | Generic | Žádný vlastní tým — Platform team integruje SaaS | komodita |

4. `15.04 Tři interakční módy mezi týmy` (`id="interakcni-mody"`)
   - **Collaboration** — dva týmy společně řeší problém. Časově omezené, vede ke Shared Kernelu nebo objevu, že to mají být jeden tým.
   - **X-as-a-Service** — jeden tým konzumuje druhý jako black-box. Mapuje na Customer/Supplier z Context Mappingu.
   - **Facilitating** — Enabling team pomáhá Stream-aligned týmu naučit se něco nové. Krátkodobě.
   - Důležité: každý vztah mezi týmy MUSÍ být jeden z těchto 3 módů. „Volné vztahy" = Big Ball of Mud na týmové úrovni.

5. `15.05 Inverse Conway Maneuver` (`id="inverse-conway"`)
   - Pokud Conway říká „struktura kopíruje organizaci", Inverse Conway říká „pokud chceme jinou strukturu, MUSÍME změnit organizaci".
   - 4 kroky:
     1. Definovat cílovou architekturu (Context Map z Tasku 1.2).
     2. Spočítat, kolik BC = kolik stream-aligned týmů potřebujeme.
     3. Re-org: rozpustit horizontal teams (Frontend/Backend/DBA), poskládat vertikální stream-aligned teams.
     4. Vyřešit Platform team (typicky 1 napříč organizací).
   - Reálná pasť: re-org JE bolestivý a politický. Pokud management nepodporuje, Inverse Conway selže.

6. `15.06 Cognitive Load — limit pro velikost týmu/BC` (`id="cognitive-load"`)
   - Skelton & Pais koncept: **Cognitive Load** týmu má 3 typy (intrinsic, extraneous, germane). Tým je efektivní, jen pokud nepřekročí kapacitu.
   - Pravidlo: 1 tým by neměl vlastnit více BC, než kolik mu cognitive load dovolí.
   - Praktická heuristika: ≤2 BC na 5–9 lidí. Pokud má tým 4+ BC, je to signál pro split.
   - Code sample: jak změřit (jednoduchá rubrika v Markdownu — 1 stránka A4).

7. `15.07 Praktické scénáře` (`id="scenare"`)
   - **Startup 5 lidí, 1 produkt:** 1 stream-aligned team, vlastní 2-3 BC. Žádný Platform team — využijte hosted služby (Heroku, Vercel, AWS Amplify).
   - **Scale-up 20 lidí, 1 produkt s rostoucí komplexitou:** 2 stream-aligned týmy podle BC + 1 mini-Platform team na CI/CD.
   - **Enterprise 200+ lidí, 10+ BC:** Plná Team Topologies struktura. Stream-aligned teams per BC + Platform + Enabling + Complicated-subsystem (kde to dává smysl).

8. `15.08 Anti-vzory` (`id="antivzory"`)
   - „Sdílíme repozitář" — sdílený monorepo bez modul boundaries → jeden tým má veto na changes druhého. Conway problém.
   - „Frontend / Backend / Mobile" týmy — vede ke Layered Architecture i u mikroservisních záměrů.
   - „Centrum výkonnosti" (Center of Excellence) místo Enabling teamu — CoE typicky drží control point, Enabling se rozpustí po předání.
   - „Platform team jako gatekeeper" — Platform musí být *self-service*, ne ticketové fronty.
   - Cross-link na `{{ path('anti_patterns') }}`.

9. `15.09 Komunikace s managementem — jak prodat re-org` (`id="management"`)
   - Argumenty, které fungují: **lead time** se zkrátí (KPI), **change failure rate** se sníží (KPI z DORA metrik).
   - Argumenty, které nefungují: „je to elegantnější" / „Eric Evans by to chtěl".
   - Citace Westrum (2014) o organizační kultuře: pathological / bureaucratic / generative — Team Topologies funguje jen v generative.

10. `15.10 Shrnutí` (`id="summary"`)

11. **FAQ:** Co když máme jediný tým? · Můžu mít 1 tým, který vlastní 5 BC? · Jak jsou Team Topologies a Spotify Model spřízněné? · Vyplatí se to v 50-člověké firmě? · Co dělat, když management nesouhlasí s re-orgem?

- [ ] **Step 5: Glosář — 6 termínů**

```html
<div class="glossary-entry" id="term-conway-law">
    <dt><dfn>Conway's Law</dfn></dt>
    <dd>
        <p>Empirický princip Melvina Conwaye (1967): „Jakákoli organizace, která navrhuje systém, vytvoří takový design, jehož struktura kopíruje komunikační strukturu této organizace." Důsledek: změna architektury bez změny týmové struktury je krátkodobě úspěšná.</p>
        <p class="glossary-related">Detail: <a href="{{ path('team_topologies') }}#conway-law">Team Topologies</a>.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-team-topologies">
    <dt><dfn>Team Topologies</dfn></dt>
    <dd>
        <p>Manuál Matthew Skeltona a Manuela Paise (2019) pro vědomý návrh týmových struktur v softwarových organizacích. Definuje 4 typy týmů (stream-aligned, platform, enabling, complicated-subsystem) a 3 interakční módy (collaboration, X-as-a-service, facilitating).</p>
    </dd>
</div>
<div class="glossary-entry" id="term-stream-aligned-team">
    <dt><dfn>Stream-aligned team</dfn></dt>
    <dd>
        <p>Tým vlastnící end-to-end value stream — typicky jeden Bounded Context. Má autonomii rozhodovat a deliverovat změny. Velikost 5–9 lidí (Dunbar number, Two-Pizza rule).</p>
    </dd>
</div>
<div class="glossary-entry" id="term-platform-team">
    <dt><dfn>Platform team</dfn></dt>
    <dd>
        <p>Tým poskytující self-service platformu (CI/CD, observability, K8s, internal developer platform) pro stream-aligned týmy. Klíčové: musí být <em>self-service</em>, ne ticketová fronta.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-enabling-team">
    <dt><dfn>Enabling team</dfn></dt>
    <dd>
        <p>Time-boxed tým specialistů, který pomáhá stream-aligned týmu osvojit si novou technologii / techniku (např. „naučte je TDD", „zaveďte CQRS"). Po předání se rozpustí — nesmí se stát Center of Excellence.</p>
    </dd>
</div>
<div class="glossary-entry" id="term-inverse-conway-maneuver">
    <dt><dfn>Inverse Conway Maneuver</dfn></dt>
    <dd>
        <p>Vědomá reorganizace týmů tak, aby produkovala požadovanou architekturu. Inverze Conway's Law: pokud architektura kopíruje organizaci, stačí změnit organizaci a architektura se přizpůsobí.</p>
    </dd>
</div>
```

- [ ] **Step 6: Cross-linky**

V `templates/ddd/context_mapping.html.twig` v sekci `04.01 Co je Context Map` doplnit:

```twig
<p>Context Map není jen architektonický artefakt — je to <strong>týmová mapa</strong>. Každý vztah mezi BC implikuje vztah mezi týmy. Detail v kapitole <a href="{{ path('team_topologies') }}">Conway's Law a Team Topologies</a>.</p>
```

V `templates/ddd/subdomains.html.twig` v sekci `03.06 Subdomény v Symfony` přidat odkaz:

```twig
<p>Subdoménová klasifikace by se měla projevit i v týmové struktuře — Core domain dostane stream-aligned tým s nejlepšími lidmi, Generic je odpovědnost Platform teamu. Detail v <a href="{{ path('team_topologies') }}#team-topologies-typy">Team Topologies</a>.</p>
```

V `templates/ddd/resources.html.twig`:

```html
<li><a href="https://teamtopologies.com/book" target="_blank" rel="noopener">Matthew Skelton & Manuel Pais — Team Topologies (2019)</a></li>
<li><a href="http://www.melconway.com/Home/Committees_Paper.html" target="_blank" rel="noopener">Melvin Conway — How Do Committees Invent? (1967)</a></li>
```

- [ ] **Step 7: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/team-topologies > /dev/null && echo "OK"
git add templates/ddd/team_topologies.html.twig templates/diagrams/18_team_topologies/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/context_mapping.html.twig templates/ddd/subdomains.html.twig templates/ddd/resources.html.twig
git commit -m "feat(content): kapitola 15 — Conway's Law a Team Topologies"
```

---

## PHASE 3 — Produkční vzory (3 kapitoly)

### Task 3.1: Kapitola 11 — Outbox Pattern

**Files:**
- Create: `templates/ddd/outbox_pattern.html.twig`
- Create: `templates/diagrams/14_outbox/outbox_flow.puml` + `.svg`
- Create: `templates/diagrams/14_outbox/inbox_idempotency.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig`
- Modify: `templates/ddd/glossary.html.twig` (Outbox, Inbox, Idempotency Key, At-least-once delivery, Exactly-once semantics)
- Modify: `templates/ddd/cqrs.html.twig` (cross-link)
- Modify: `templates/ddd/event_sourcing.html.twig` (cross-link — sjednotit zmínky outboxu)
- Modify: `templates/ddd/sagas.html.twig` (cross-link — saga používá outbox pro reliable command publishing)
- Modify: `templates/ddd/ddd_pain_points.html.twig` (zúžit existující outbox sekci na pointer + krátké shrnutí, plné vysvětlení odkazem do nové kapitoly)

**Goal:** Plné vysvětlení **Transactional Outbox Pattern** + jeho dual partner **Idempotent Inbox Pattern** s konkrétní implementací nad Symfony Messenger + Doctrine, postup migrace existujícího kódu na outbox a srovnání s alternativami (CDC / Debezium).

- [ ] **Step 1: Route + action**

```php
    #[Route('/outbox-pattern', name: 'outbox_pattern')]
    public function outboxPattern(): Response
    {
        return $this->render('ddd/outbox_pattern.html.twig', [
            'title' => 'Outbox Pattern — spolehlivé publikování doménových eventů',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '11', 'route' => 'outbox_pattern', 't' => 'Outbox Pattern', 'd' => 'Spolehlivé publikování eventů — eliminace dual-write problému', 'time' => 28, 'lvl' => 4, 'tag' => 'Vzory', 'group' => 'patterns'],
```

A `_patterns_routes`:

```twig
{%- set _patterns_routes = ['hub_patterns', 'cqrs', 'event_sourcing', 'sagas', 'outbox_pattern', 'lesser_known_patterns', 'performance_aspects'] -%}
```

- [ ] **Step 3: Diagram 1 — `outbox_flow.puml`**

Sekvenční diagram celého toku publish:

```plantuml
@startuml
!include ../theme.iuml

title Transactional Outbox: 4 fáze publish

participant "PlaceOrderHandler" as h
database "DB\n(orders + outbox)" as db
participant "Outbox Relay\n(messenger:consume)" as relay
queue "RabbitMQ" as mq
participant "Subscriber BC" as sub

== Fáze 1: business transakce ==
h -> db : BEGIN
h -> db : INSERT INTO orders (...)
h -> db : INSERT INTO outbox (eventId, payload, status='pending')
h -> db : COMMIT
note right of db
  Atomicky: order i outbox row.
  Nelze ztratit jeden bez druhého.
end note

== Fáze 2: relay polluje outbox ==
relay -> db : SELECT * FROM outbox WHERE status='pending' LIMIT 100
db -> relay : 100 rows

== Fáze 3: publish do brokera ==
loop pro každý row
  relay -> mq : publish(eventId, payload)
  mq -> relay : ack
  relay -> db : UPDATE outbox SET status='sent' WHERE id=...
end

== Fáze 4: subscriber konzumuje ==
mq -> sub : delivery
sub -> sub : process (idempotent)
sub -> mq : ack
@enduml
```

- [ ] **Step 4: Diagram 2 — `inbox_idempotency.puml`**

Idempotent inbox (deduplication):

```plantuml
@startuml
!include ../theme.iuml

title Idempotent Inbox: deduplikace na straně subscribera

queue "Broker" as mq
participant "Subscriber Handler" as h
database "Inbox table\n(processed_event_ids)" as inbox
database "Read Model" as rm

mq -> h : delivery (eventId=X)
h -> inbox : SELECT * WHERE event_id=X
alt eventId X už zpracován
  inbox -> h : found
  h -> mq : ack (skip processing)
  note right of h
    At-least-once + dedup
    = exactly-once effect
  end note
else eventId X nový
  inbox -> h : not found
  h -> rm : BEGIN
  h -> rm : update read model
  h -> inbox : INSERT (event_id=X)
  h -> rm : COMMIT
  h -> mq : ack
end
@enduml
```

- [ ] **Step 5: Šablona — kompletní obsah**

Hlavička:
- `title`: `Outbox Pattern — spolehlivé publikování doménových eventů v Symfony | DDD Symfony`
- `meta_description`: `Transactional Outbox + Idempotent Inbox v Symfony 8 a Doctrine: jak zajistit at-least-once delivery doménových eventů, eliminovat dual-write problém a co o tom říká Pat Helland a Chris Richardson.`
- `meta_keywords`: `Outbox Pattern, Transactional Outbox, Inbox Pattern, Idempotency, Dual-write problem, Pat Helland, Chris Richardson, Symfony Messenger, Doctrine, at-least-once, exactly-once, RabbitMQ, eventy, CDC, Debezium`
- `breadcrumb_name`: `Outbox Pattern`

`article_head`: `chapter_number: '11'`, `category: 'Vzory'`, `reading_time: 28`, `difficulty: 4`, deck: „Klasický bug: zapíšete order do DB, pak se vám rozbije RabbitMQ, ale order tam zůstane bez `OrderPlaced` eventu. Subscribery o něm nikdy neví. Outbox Pattern řeší tento dual-write problem na úrovni jedné DB transakce; jeho dvojče Inbox Pattern řeší deduplikaci u subscriberů. V Symfony 8 je to jeden Doctrine entity manager, jeden Messenger transport a 80 řádků kódu."

**Sekce:**

1. `11.01 Dual-write problem` (`id="dual-write"`)
   - Co se může pokazit:
     - Zápis do DB → rollback brokera → DB je daleko, broker zaostal
     - Zápis do brokera → DB transakce selže → broker odeslal event, který se „nestal"
   - Proč ne 2PC (Two-Phase Commit) — broker / message queue typicky nepodporují XA, navíc je to drahé.
   - Citace: Pat Helland *Life Beyond Distributed Transactions* (2007); Richardson *Microservices Patterns* (2018) kap. 3.

2. `11.02 Transactional Outbox — princip` (`id="princip"`)
   - Idea: zapsat event do **stejné DB**, ve které je business state, do tabulky `outbox`. Vše v jedné DB transakci.
   - Asynchronně relay process tabulku polluje a publikuje do brokera, pak označuje řádky jako `sent`.
   - **Vložit diagram 1** (`fig: '11.2-A'`).
   - Garance: at-least-once delivery (může být víc než jednou — pokud relay padne mezi publish a UPDATE).

3. `11.03 Schéma `outbox` tabulky a Doctrine mapping` (`id="schema"`)
   - **Code sample** — Doctrine entity / migration:
     ```php
     namespace App\Outbox\Domain;

     #[ORM\Entity]
     #[ORM\Table(name: 'outbox')]
     #[ORM\Index(columns: ['status', 'occurred_at'], name: 'idx_outbox_status_time')]
     class OutboxMessage
     {
         public function __construct(
             #[ORM\Id]
             #[ORM\Column(type: 'ulid', unique: true)]
             public Ulid $id,
             #[ORM\Column(type: 'string', length: 255)]
             public string $messageType,   // např. "App\Ordering\Domain\Event\OrderPlaced"
             #[ORM\Column(type: 'json')]
             public array $payload,
             #[ORM\Column(type: 'string', length: 16)]
             public string $status = 'pending',  // pending | sent | failed
             #[ORM\Column(type: 'datetime_immutable')]
             public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
             #[ORM\Column(type: 'integer')]
             public int $attempts = 0,
             #[ORM\Column(type: 'datetime_immutable', nullable: true)]
             public ?\DateTimeImmutable $sentAt = null,
         ) {}
     }
     ```
   - SQL pro `php bin/console doctrine:migrations:diff` — kontrola, že INDEX `idx_outbox_status_time` skutečně vznikne.

4. `11.04 Aggregate publikuje, handler ukládá do outboxu` (`id="aggregate-publishes"`)
   - **Code sample** — Aggregate:
     ```php
     namespace App\Ordering\Domain;

     final class Order
     {
         /** @var list<DomainEvent> */
         private array $releasedEvents = [];

         public static function place(CustomerId $cust, array $items): self {
             $o = new self(new OrderId(Ulid::generate()), $cust, $items);
             $o->releasedEvents[] = new OrderPlaced($o->id, $cust, $items, new \DateTimeImmutable());
             return $o;
         }

         /** @return list<DomainEvent> */
         public function releaseEvents(): array {
             $events = $this->releasedEvents;
             $this->releasedEvents = [];
             return $events;
         }
     }
     ```
   - **Code sample** — Application Handler s outbox zápisem:
     ```php
     final class PlaceOrderHandler
     {
         public function __construct(
             private OrderRepository $orders,
             private OutboxRepository $outbox,
             private EntityManagerInterface $em,
         ) {}

         public function __invoke(PlaceOrderCommand $c): OrderId
         {
             $this->em->wrapInTransaction(function () use ($c) {
                 $order = Order::place($c->customerId, $c->items);
                 $this->orders->save($order);
                 foreach ($order->releaseEvents() as $event) {
                     $this->outbox->store(OutboxMessage::fromDomainEvent($event));
                 }
                 return $order->id;
             });
         }
     }
     ```
   - **Klíčový bod:** `wrapInTransaction` zaručuje, že buď zapíšeme order + outbox, nebo nic.

5. `11.05 Relay process — dvě varianty` (`id="relay"`)
   - **Varianta A: Polling worker** — Symfony command:
     ```php
     #[AsCommand(name: 'app:outbox:dispatch')]
     final class OutboxDispatchCommand extends Command
     {
         public function __construct(
             private OutboxRepository $outbox,
             private MessageBusInterface $bus,
         ) { parent::__construct(); }

         protected function execute(InputInterface $i, OutputInterface $o): int
         {
             foreach ($this->outbox->fetchPending(limit: 100) as $msg) {
                 try {
                     $this->bus->dispatch($msg->toMessage(), [new TransportNamesStamp(['async'])]);
                     $this->outbox->markSent($msg->id);
                 } catch (\Throwable $e) {
                     $this->outbox->markFailed($msg->id, $e->getMessage());
                 }
             }
             return Command::SUCCESS;
         }
     }
     ```
     Spouští se z `supervisord` nebo `cron` v 1s intervalu, nebo `systemd` timer.
   - **Varianta B: CDC (Change Data Capture) — Debezium** — pokročilé, mimo Symfony. Stručně: Debezium čte WAL/binlog Postgres/MySQL a streamuje změny do Kafky. Outbox tabulka je pak „jen" tabulka, která je čtena automaticky. Trade-off: vyšší ops nároky, ale exactly-once efekt.

6. `11.06 Idempotent Inbox — strana subscribera` (`id="inbox"`)
   - Problém: outbox dává at-least-once, takže subscriber může dostat duplicitní eventy.
   - Řešení: tabulka `inbox` se sloupcem `event_id` (UUID/ULID), unique index. Před zpracováním check, po zpracování insert v jedné transakci.
   - **Vložit diagram 2** (`fig: '11.6-A'`).
   - **Code sample** — handler s inbox checkem:
     ```php
     final class OrderPlacedReadModelUpdater
     {
         public function __construct(
             private InboxRepository $inbox,
             private ReadModelStore $rm,
             private EntityManagerInterface $em,
         ) {}

         public function __invoke(OrderPlaced $e): void
         {
             $this->em->wrapInTransaction(function () use ($e) {
                 if ($this->inbox->isProcessed($e->eventId)) {
                     return; // duplicate — ack and skip
                 }
                 $this->rm->upsertOrderRow($e);
                 $this->inbox->markProcessed($e->eventId);
             });
         }
     }
     ```

7. `11.07 Idempotency Key v API` (`id="idempotency-api"`)
   - Stripe pattern: klient posílá `Idempotency-Key` header, server uloží request hash + odpověď. Pokud klient retry-uje, vrátí cached response.
   - Code sample: HTTP middleware / Controller decorator pro Symfony.
   - Důležité: idempotency key má TTL (typicky 24-48h) a hash se bere ze zájmu requestu, ne z URL.

8. `11.08 Provozní aspekty` (`id="provoz"`)
   - **Outbox lag** — metric: jak dlouho se průměrný event flaká v `pending`. Alert > 30s.
   - **Outbox table growth** — kompakce: po 30 dnech `DELETE FROM outbox WHERE status='sent' AND sent_at < NOW() - INTERVAL '30 days'`. Cron, ne real-time.
   - **Failed messages** — DLQ (dead-letter queue) tabulka pro `status='failed'` po N attempts. Manuální resolve.
   - **Index** — povinný `idx_outbox_status_time` (z migrace v `11.03`). Bez něj plný scan.

9. `11.09 Anti-vzory` (`id="antivzory"`)
   - „Publish napřímo z aggregate metody" — bez transakce s DB → dual-write.
   - „Outbox bez UNIQUE constraintu na event_id" — duplikáty se zapíšou víckrát.
   - „Inbox bez transakce" — race condition: 2 worker processy zpracují stejný event paralelně.
   - „Read model bez idempotency" — duplikáty způsobí inkonzistenci (např. zdvojený counter).

10. `11.10 Migrace existujícího projektu — krok za krokem` (`id="migrace"`)
    - 1. Přidat `outbox` tabulku.
    - 2. V handleru přesunout publish do téže transakce, zapsat do outboxu místo přímo do bus.
    - 3. Nastavit relay command + supervisor.
    - 4. Postupně přidat inbox pro každého subscribera.
    - 5. Měřit lag, ověřit, že žádný event neuniká.

11. `11.11 Shrnutí` (`id="summary"`)

12. **FAQ:** Outbox vs. CDC / Debezium — co kdy? · Co když používáme NoSQL? · Jak velký dělat batch v relayi? · Vyplatí se Outbox v monolitu? · Co dělat při výpadku brokera dlouhodobě?

- [ ] **Step 6: Glosář — 5 termínů**

Outbox/Inbox/Idempotency Key/At-least-once/Exactly-once. Pro každý definice 2-3 věty + odkaz.

- [ ] **Step 7: Cross-linky**

V `templates/ddd/cqrs.html.twig` v sekci o publishi eventů doplnit:

```twig
<p>V produkční CQRS systému by se eventy nikdy neměly publikovat přímo z aggregate metody — vždy přes <a href="{{ path('outbox_pattern') }}">Outbox Pattern</a>, který garantuje atomicitu zápisu state + event.</p>
```

V `templates/ddd/event_sourcing.html.twig` u sekce o publish doménových eventů — sjednotit existující 9 zmínek outboxu na 1 cross-link.

V `templates/ddd/sagas.html.twig` u sekce o reliable command publishing — odkaz na outbox.

V `templates/ddd/ddd_pain_points.html.twig` zkrátit existující sekci o outboxu (21 zmínek) na 2-3 odstavce + cross-link na novou kapitolu. Pravidlo: pain_points je *přehled* problémů, ne *manuál*.

- [ ] **Step 8: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/outbox-pattern > /dev/null && echo "OK"
curl -s http://127.0.0.1:8000/outbox-pattern | grep -c 'wrapInTransaction\|OutboxMessage\|InboxRepository' # → ≥ 5

git add templates/ddd/outbox_pattern.html.twig templates/diagrams/14_outbox/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/cqrs.html.twig templates/ddd/event_sourcing.html.twig templates/ddd/sagas.html.twig templates/ddd/ddd_pain_points.html.twig
git commit -m "feat(content): kapitola 11 — Outbox Pattern + Idempotent Inbox"
```

---

### Task 3.2: Kapitola 12 — Méně známé taktické vzory (Specifications, Domain Services, Factories, Modules)

**Files:**
- Create: `templates/ddd/lesser_known_patterns.html.twig`
- Create: `templates/diagrams/16_lesser_patterns/specification_compose.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig`
- Modify: `templates/ddd/glossary.html.twig` (Specification, Domain Service, Application Service, Factory, Module — některé už existují, doplnit cross-link na novou kapitolu)
- Modify: `templates/ddd/basic_concepts.html.twig` (zkrácená zmínka + cross-link na detail)
- Modify: `templates/ddd/anti_patterns.html.twig` (cross-link — anémické modely vs. Domain Services)

**Goal:** Hloubkové vysvětlení 4 taktických vzorů, které jsou v knize jen letmo zmíněné: **Specification Pattern**, **Domain Services**, **Factories**, **Modules**. Každý s jasnou definicí, kdy použít, kdy NE, code sample v Symfony 8 / PHP 8.4.

- [ ] **Step 1: Route + action**

```php
    #[Route('/mene-zname-vzory', name: 'lesser_known_patterns')]
    public function lesserKnownPatterns(): Response
    {
        return $this->render('ddd/lesser_known_patterns.html.twig', [
            'title' => 'Méně známé taktické vzory: Specifications, Domain Services, Factories, Modules',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '12', 'route' => 'lesser_known_patterns', 't' => 'Méně známé taktické vzory', 'd' => 'Specification · Domain Service · Factory · Module — kdy a jak', 'time' => 28, 'lvl' => 3, 'tag' => 'Vzory', 'group' => 'patterns'],
```

- [ ] **Step 3: Diagram — `specification_compose.puml`**

Class diagram skládání specifikací:

```plantuml
@startuml
!include ../theme.iuml

title Specification Pattern: kompozice booleovské logiky

interface "Specification<T>" as spec {
  +isSatisfiedBy(T): bool
  +and(Specification<T>): Specification<T>
  +or(Specification<T>): Specification<T>
  +not(): Specification<T>
}

class "AndSpecification<T>" as andSpec {
  -left: Specification<T>
  -right: Specification<T>
  +isSatisfiedBy(T): bool
}

class "OrSpecification<T>" as orSpec
class "NotSpecification<T>" as notSpec

class "PremiumCustomer" as ps
class "MinimumOrderTotal" as mot
class "InEU" as ineu

spec <|.. andSpec
spec <|.. orSpec
spec <|.. notSpec
spec <|.. ps
spec <|.. mot
spec <|.. ineu

note right of ps
  Doménové specifikace
  vyjadřují business pravidla
  jako prvotřídní objekty.
end note

note bottom of andSpec
  Kompozice = and/or/not
  → libovolné booleovské
  výrazy bez if-else stromů.
end note
@enduml
```

- [ ] **Step 4: Šablona — celá kapitola**

Hlavička:
- `title`: `Specifications, Domain Services, Factories, Modules — kompletně | DDD Symfony`
- `meta_description`: `Čtyři často přehlížené taktické vzory DDD: Specification Pattern (kompozice business pravidel), Domain Services (logika mimo entity), Factories (komplexní vznik aggregate), Modules (Eric Evans organization). Praktická implementace v Symfony.`
- `breadcrumb_name`: `Méně známé taktické vzory`

`article_head`: `chapter_number: '12'`, `category: 'Vzory'`, `reading_time: 28`, `difficulty: 3`, deck: „Vedle entit, value objektů a agregátů obsahuje Evansova kniha čtyři další taktické vzory, které programátoři často přeskočí: Specifications jako prvotřídní booleovská logika, Domain Services pro chování bez přirozeného vlastníka, Factories pro komplexní vznik a Modules jako vědomá organizace kódu. Tato kapitola je jejich detailní průvodce v Symfony 8 stack."

**Sekce:**

1. `12.01 Proč tyto vzory přehlížíme` (`id="proc-prehlizime"`)
   - Krátké úvodní pozorování: tutoriály opakují Entity/VO/Aggregate, ale Specifications/Domain Services/Factories vypadají jako „extra složitost". Pravda je opačná — bez nich agregáty bobtnají.

2. `12.02 Specification Pattern` (`id="specification"`)
   - **2.1 Co to je:** prvotřídní objekt zapouzdřující booleovský predikát. `Specification::isSatisfiedBy(T): bool`.
   - **2.2 Kdy použít:**
     - Komplexní business pravidla, která se mají skládat (`isPremium AND livesInEU`)
     - Pravidla použitelná v doméně (validate) i v repozitáři (query) — tzv. **double-dispatch**
     - Pravidla, která se mění za běhu (např. promo kód = specifikace složená z N pravidel)
   - **2.3 Kdy NE:** triviální podmínky (1 if-statement). Anti-pattern: každé porovnání = vlastní `Specification` třída.
   - **Vložit diagram** (`fig: '12.2-A'`).
   - **Code sample 1** — interface + composite:
     ```php
     namespace App\Shared\Domain\Specification;

     /** @template T */
     interface Specification
     {
         /** @param T $candidate */
         public function isSatisfiedBy(mixed $candidate): bool;

         /** @param Specification<T> $other @return Specification<T> */
         public function and(self $other): self;
     }

     /** @template T @implements Specification<T> */
     abstract class CompositeSpecification implements Specification
     {
         public function and(Specification $other): Specification
         {
             return new AndSpecification($this, $other);
         }
     }

     /** @template T @extends CompositeSpecification<T> */
     final class AndSpecification extends CompositeSpecification
     {
         public function __construct(private Specification $left, private Specification $right) {}
         public function isSatisfiedBy(mixed $c): bool {
             return $this->left->isSatisfiedBy($c) && $this->right->isSatisfiedBy($c);
         }
     }
     ```
   - **Code sample 2** — doménová specifikace:
     ```php
     namespace App\Ordering\Domain\Specification;

     /** @extends CompositeSpecification<Order> */
     final class EligibleForFreeShipping extends CompositeSpecification
     {
         public function __construct(private Money $threshold) {}
         public function isSatisfiedBy(mixed $order): bool
         {
             assert($order instanceof Order);
             return $order->total()->isGreaterThanOrEqual($this->threshold);
         }
     }
     ```
   - **Code sample 3** — kompozice + použití v handleru:
     ```php
     $promo = (new EligibleForFreeShipping(Money::eur(100)))
         ->and(new InEUCountry($order->shippingAddress->country))
         ->and(new NotInBlacklist($order->customerId));
     if ($promo->isSatisfiedBy($order)) { /* ... */ }
     ```
   - **Code sample 4** — double-dispatch do Doctrine query (`Specification::asDoctrineCriteria()`):
     ```php
     interface QuerySpecification extends Specification
     {
         public function asDoctrineCriteria(QueryBuilder $qb, string $alias): void;
     }
     ```

3. `12.03 Domain Services` (`id="domain-services"`)
   - **3.1 Co to je:** stateless objekt obsahující doménovou logiku, která **nemá přirozeného vlastníka** mezi Entity / VO / Aggregate.
   - **3.2 Kdy použít:** typické příklady — funds transfer (potřebuje 2 účty), pricing engine (čte pricing rules + customer + cart), credit scoring.
   - **3.3 Kdy NE:** `OrderService`, `CustomerService`, `*Service` jako default = anémie domény. Jestli logika přirozeně patří do Entity, patří tam.
   - **Code sample** — `MoneyTransferService`:
     ```php
     namespace App\Banking\Domain\Service;

     final class MoneyTransferService
     {
         public function transfer(Account $from, Account $to, Money $amount, \DateTimeImmutable $when): void
         {
             // Logika nepatří ani do $from (nezná $to), ani do $to (nezná $from).
             // → Domain Service
             $from->withdraw($amount, $when);
             $to->deposit($amount, $when);
         }
     }
     ```
   - **Anti-pattern:** Application Service vydávaný za Domain Service. Rozdíl: Domain Service obsahuje *doménovou logiku* (pravidla); Application Service *koordinuje* (transakce, autorizaci, posílá eventy).
   - Tabulka rozdílů Domain Service vs. Application Service vs. Infrastructure Service.

4. `12.04 Factories` (`id="factories"`)
   - **4.1 Co to je:** zapouzdření **komplexní logiky vzniku** Aggregate / VO. Standardní konstruktor je triviální; Factory je pro případy, kdy vznik vyžaduje pravidla, validace, externí lookup.
   - **4.2 Kdy použít:**
     - Aggregate vzniká z příchozího payloadu, který je „surový" (z REST API) — Factory ho mapuje na doménové typy
     - Aggregate má neprivátní invariant při vzniku (např. „nový Order musí mít alespoň 1 položku, alespoň jedno z položek musí být v stocku")
     - Polymorfní vznik (různé pod-typy podle vstupních dat)
   - **4.3 Kdy NE:** `OrderFactory::create()` je pojmenovaný Factory, ale ve skutečnosti je to obyčejný konstruktor.
   - **Code sample 1** — static method factory (preferovaný styl pro PHP 8.4):
     ```php
     final class Order
     {
         private function __construct(...) {}

         public static function place(CustomerId $cust, array $items): self
         {
             if (count($items) === 0) {
                 throw new InvalidArgumentException('Order must have at least 1 item');
             }
             return new self(...);
         }

         public static function fromImport(ImportedOrderRow $row, CustomerLookup $lookup): self
         {
             $cust = $lookup->resolve($row->customerEmail) ?? CustomerId::guest();
             return self::place($cust, ImportedItems::map($row->items));
         }
     }
     ```
   - **Code sample 2** — Factory class (samostatná) když vznik potřebuje DI závislosti:
     ```php
     final class OrderFromCartFactory
     {
         public function __construct(
             private CartRepository $carts,
             private PricingService $pricing,
         ) {}

         public function fromCart(CartId $cartId, CustomerId $cust): Order
         {
             $cart = $this->carts->get($cartId);
             $items = $this->pricing->priceItems($cart->items, $cust);
             return Order::place($cust, $items);
         }
     }
     ```
   - Pravidlo (Vernon 2013): static method preferred. Class Factory jen když potřebuješ DI.

5. `12.05 Modules` (`id="modules"`)
   - **5.1 Co to je:** vědomá organizace kódu do balíčků pojmenovaných podle Ubiquitous Language. Ne podle vrstev (Entity/Service/Repo), ne podle technologie (Doctrine/Twig).
   - **5.2 Co to znamená v Symfony 8:** PSR-4 namespace organizace + `composer.json` + folder layout.
   - **Code sample** — projekt po Modules organizaci:
     ```
     src/
       Ordering/                          ← MODULE = Bounded Context
         Domain/
           Order.php
           OrderRepository.php (interface)
           Specification/EligibleForFreeShipping.php
           Service/PricingService.php
         Application/
           Command/PlaceOrderCommand.php
           Handler/PlaceOrderHandler.php
         Infrastructure/
           Doctrine/DoctrineOrderRepository.php
           Http/OrderController.php
       Billing/
         Domain/
           Invoice.php
           ...
       SharedKernel/
         Money/
         Currency/
     ```
   - **Anti-pattern „type packaging":** `src/Entity/`, `src/Repository/`, `src/Service/`. Vytváří horizontální slicing (viz `{{ path('horizontal_vs_vertical') }}`), které nemodeluje doménu.
   - **Code sample** — `composer.json` autoload:
     ```json
     {
       "autoload": {
         "psr-4": {
           "App\\Ordering\\": "src/Ordering/",
           "App\\Billing\\": "src/Billing/",
           "App\\SharedKernel\\": "src/SharedKernel/"
         }
       }
     }
     ```
   - Architecture testing: pro vynucení Module hranic použít `phparkitect` — ukázka rule:
     ```php
     Rule::allClasses()
         ->that(new ResideInOneOfTheseNamespaces('App\Ordering'))
         ->should(new NotDependsOnAnyOfTheseNamespaces(['App\Billing']))
         ->because('Ordering BC nesmí přímo závisettak na Billing BC, integrace přes events.');
     ```

6. `12.06 Vztah těchto vzorů ke zbytku DDD` (`id="vztahy"`)
   - Mind-mapa:
     - Aggregate → uvnitř používá Specifications pro invarianty
     - Aggregate → vzniká přes Factory
     - Domain Service → koordinuje 2+ Aggregate
     - Modules → seskupují všechny předchozí

7. `12.07 Anti-vzory souhrn` (`id="antivzory"`)
   - Specification jako 1-line-if (over-engineering)
   - „*Service" sufix bez rozlišení Domain/Application/Infrastructure
   - Factory pro každý objekt
   - `src/Entity/`, `src/Service/` packaging
   - Cross-link na `{{ path('anti_patterns') }}`.

8. `12.08 Shrnutí` (`id="summary"`)

9. **FAQ:** Kdy přesně se vyplatí Specification? · Domain Service má mít state? · Factory metoda nebo Factory class? · Jak vynutit Module hranice? · Jak má vypadat namespace třídy, která sedí na hranici 2 BC?

- [ ] **Step 5: Glosář — rozšíření existujících + nové**

Pro `term-specifikace`, `term-domain-service` (přidat pokud chybí), `term-application-service`, `term-factory`, `term-module` doplnit cross-link na novou kapitolu. Přidat ty, které chybí.

- [ ] **Step 6: Cross-linky**

V `templates/ddd/basic_concepts.html.twig` u sekcí o Domain Service / Factory zkrátit a přidat:

```twig
<p>Hloubkové vysvětlení s code samples a anti-vzory v kapitole <a href="{{ path('lesser_known_patterns') }}">Méně známé taktické vzory</a>.</p>
```

V `templates/ddd/anti_patterns.html.twig` u anti-vzoru „Anemic Domain Model" doplnit:

```twig
<p>Pokud máte „Service" kdekoliv v názvu třídy, ověřte: jde o doménovou logiku (Domain Service), aplikační koordinaci (Application Service), nebo infra (Infrastructure Service)? Detail v <a href="{{ path('lesser_known_patterns') }}#domain-services">Domain Services</a>.</p>
```

- [ ] **Step 7: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/mene-zname-vzory > /dev/null && echo "OK"
git add templates/ddd/lesser_known_patterns.html.twig templates/diagrams/16_lesser_patterns/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/basic_concepts.html.twig templates/ddd/anti_patterns.html.twig
git commit -m "feat(content): kapitola 12 — Méně známé taktické vzory (Specifications, Domain Services, Factories, Modules)"
```

---

### Task 3.3: Kapitola 17 — DDD a microservices

**Files:**
- Create: `templates/ddd/microservices_and_ddd.html.twig`
- Create: `templates/diagrams/20_microservices/bc_to_service.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig`
- Modify: `templates/ddd/glossary.html.twig` (Modular Monolith, Distributed Monolith, Service Mesh, Saga vs. Microservice transaction)
- Modify: `templates/ddd/sagas.html.twig` (cross-link)
- Modify: `templates/ddd/context_mapping.html.twig` (cross-link — kapitola 17 implementuje Context Map fyzicky)
- Modify: `templates/ddd/when_not_to_use_ddd.html.twig` (cross-link — „chcete microservices ale nemáte BC" je důvod)
- Modify: `templates/ddd/resources.html.twig` (Newman: *Building Microservices*; Richardson: *Microservices Patterns*)

**Goal:** Vysvětlit vztah **Bounded Context ↔ microservice boundary**, kdy je BC = service a kdy není, jak vypadá monolit-first migrace na microservices, anti-pattern „distributed monolith" a kdy zvolit modular monolith místo microservices.

- [ ] **Step 1: Route + action**

```php
    #[Route('/ddd-a-microservices', name: 'microservices_and_ddd')]
    public function microservicesAndDdd(): Response
    {
        return $this->render('ddd/microservices_and_ddd.html.twig', [
            'title' => 'DDD a microservices — Bounded Context jako service boundary',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '17', 'route' => 'microservices_and_ddd', 't' => 'DDD a microservices', 'd' => 'BC jako service boundary · modular monolith · distributed monolith', 'time' => 30, 'lvl' => 4, 'tag' => 'Praxe', 'group' => 'practice'],
```

- [ ] **Step 3: Diagram — `bc_to_service.puml`**

3 scénáře vedle sebe: 1 BC = 1 service (ideál); 1 BC = N services (zbytečně rozdělené); N BC = 1 service (modular monolith / shared service):

```plantuml
@startuml
!include ../theme.iuml

title 3 mapování BC ↔ Service: kdy které

package "Scénář 1: 1 BC = 1 service\n(ideální microservice)" {
    rectangle "Ordering BC" as bc1 $ACCENT
    cloud "ordering-service\n(deploy unit)" as svc1
    bc1 -down-> svc1
    note bottom of svc1
      Stream-aligned tým,
      vlastní DB, vlastní deploy.
    end note
}

package "Scénář 2: 1 BC = N services\n(přehnané dělení)" {
    rectangle "Ordering BC" as bc2 $ACCENT
    cloud "order-create-svc" as svc2a
    cloud "order-update-svc" as svc2b
    cloud "order-search-svc" as svc2c
    bc2 -down-> svc2a
    bc2 -down-> svc2b
    bc2 -down-> svc2c
    note bottom of svc2c
      <b>Anti-pattern</b>
      Každý CRUD endpoint = service.
      Distributed monolith.
    end note
}

package "Scénář 3: N BC = 1 service\n(modular monolith)" {
    rectangle "Ordering BC" as bc3a
    rectangle "Billing BC" as bc3b
    rectangle "Inventory BC" as bc3c
    cloud "monolith\n(1 deploy unit, 3 modules)" as svc3
    bc3a -down-> svc3
    bc3b -down-> svc3
    bc3c -down-> svc3
    note bottom of svc3
      Architektonicky oddělené,
      nasazované jako 1.
      Ideální pro malé týmy.
    end note
}
@enduml
```

- [ ] **Step 4: Šablona — celá kapitola**

Hlavička:
- `title`: `DDD a microservices — Bounded Context jako service boundary | DDD Symfony`
- `meta_description`: `Mapování DDD Bounded Context na microservice. Kdy BC = service, kdy modular monolith, jak se vyhnout distributed monolithu. Sam Newman, Chris Richardson, Symfony 8 a Messenger.`
- `breadcrumb_name`: `DDD a microservices`

`article_head`: `chapter_number: '17'`, `category: 'Praxe'`, `reading_time: 30`, `difficulty: 4`, deck: „‚Microservice je tak velký, jak velký je jeden Bounded Context' — slogan, který polopravda. Bounded Context je *logická* hranice; microservice je *fyzická*. Kapitola o tom, kdy mapování 1:1 dává smysl, kdy modular monolith poráží microservices a jak rozeznat distributed monolith včas."

**Sekce:**

1. `17.01 Mýtus „microservice = Bounded Context"` (`id="mytus"`)
   - Mýtus: každý BC by měl být vlastní microservice.
   - Pravda: BC je logická hranice modelu. Microservice je deployment unit. Mapování může být 1:1, 1:N nebo N:1.
   - **Vložit diagram** (`fig: '17.1-A'`).
   - Citace: Newman *Building Microservices* (2021, 2nd ed.) kap. 1; Richardson *Microservices Patterns* (2018) kap. 2.

2. `17.02 Kdy 1 BC = 1 service` (`id="bc-jedna-service"`)
   - Když BC má vlastní stream-aligned team (z `{{ path('team_topologies') }}`).
   - Když BC má vlastní data, vlastní release cyklus, různé scaling potřeby od ostatních.
   - Příklad: e-shop s Catalog, Ordering, Payment, Shipping jako 4 separátní servisy.

3. `17.03 Kdy modular monolith` (`id="modular-monolith"`)
   - Když organizace má < ~30 lidí na celém produktu.
   - Když všechny BC mají podobné scaling potřeby.
   - Když operační overhead microservices (CI/CD, observability, service mesh, deployments) by spotřeboval > 30 % engineering kapacity.
   - **Code sample** — modular monolith struktura v Symfony 8 (`src/<BC>/`) s vynucenými hranicemi přes `phparkitect`:
     ```php
     // tests/Architecture/ModularMonolithRulesTest.php
     final class ModularMonolithRules
     {
         public function rules(): array
         {
             return [
                 Rule::allClasses()->that(new ResideInOneOfTheseNamespaces('App\Ordering'))
                     ->should(new NotDependsOnAnyOfTheseNamespaces(['App\Billing\Infrastructure']))
                     ->because('Ordering komunikuje s Billing jen přes events nebo Application interface, ne přes Infrastructure.'),
             ];
         }
     }
     ```
   - Kdy z modular monolithu rozdělit: když jeden modul potřebuje výrazně jiný stack / scale / release cyklus.

4. `17.04 Distributed Monolith — anti-pattern` (`id="distributed-monolith"`)
   - Definice: máte N service, ale chovají se jako jeden — sdílí DB, sdílí kontrakt, deploy je nutné dělat současně.
   - 5 příznaků:
     1. Sdílená DB napříč servicy
     2. Synchronní HTTP volání mezi servicy v každém request flow
     3. Když změníte API service A, musíte deploynout B současně
     4. Test celého flow vyžaduje všech N servisů
     5. Sdílený deployable artefakt (např. shared library s doménovými typy)
   - Cross-link na `{{ path('anti_patterns') }}`.

5. `17.05 Kontrakt mezi services — sync vs. async` (`id="kontrakt"`)
   - **Sync (REST/gRPC):** vhodné pro query (read), které čekají odpověď. Latency-sensitive operace.
   - **Async (eventy přes broker):** vhodné pro state changes (write). Loose coupling, lze restartovat subscribera.
   - Pravidlo (Richardson): *async-first*. Sync použít jen tam, kde je odpověď nutná pro request flow.
   - Cross-link na `{{ path('outbox_pattern') }}`.

6. `17.06 Distribuované transakce — Saga, ne 2PC` (`id="distribuovane-transakce"`)
   - Cross-link na `{{ path('sagas') }}`.
   - Stručně: 2PC nepoužitelné pro microservices (žádný shared coordinator, blocking, performance). Saga = orchestrované nebo choreografované sekvence + kompenzace.

7. `17.07 Service mesh, observability, ops` (`id="ops"`)
   - **Service mesh** (Istio, Linkerd) — řeší cross-cutting concerns: mTLS, retries, circuit breaking. Ne každý projekt potřebuje.
   - **Distributed tracing** — OpenTelemetry, Jaeger. Bez něj debug v microservices = peklo.
   - **Service registry / discovery** — Consul, Kubernetes service.
   - Pravidlo: pokud nemáte všechno z výše uvedeného, modular monolith je rozumnější.

8. `17.08 Symfony konkrétně — kdy a jak` (`id="symfony"`)
   - Modular monolith v Symfony: jeden Symfony projekt, `src/<BC>/`, integrace přes Messenger sync transport.
   - Microservice v Symfony: každý service je vlastní Symfony aplikace, integrace přes Messenger AMQP transport + outbox.
   - **Code sample** — Messenger config pro cross-service event:
     ```yaml
     # service A (publisher)
     framework:
         messenger:
             transports:
                 events_out: '%env(EVENTS_OUT_DSN)%'
             routing:
                 'App\Ordering\Domain\Event\OrderPlaced': events_out

     # service B (subscriber)
     framework:
         messenger:
             transports:
                 events_in: '%env(EVENTS_IN_DSN)%'
             routing:
                 'App\Billing\Application\IntegrationEvent\OrderPlacedReceived': events_in
     ```
   - **Důležité:** publisher a subscriber NEsdílejí PHP třídu eventu. Subscriber má vlastní *integration event* DTO, nikoli `use App\Ordering\Domain\Event\OrderPlaced`. Jinak shared library = distributed monolith.

9. `17.09 Postupná migrace monolit → microservices` (`id="migrace"`)
   - 1. Nejdřív modular monolith (vynucené hranice mezi BC).
   - 2. Strangler Fig — extrahovat 1 BC do separate service (typicky ten s nejvíc samostatným scaling potřebou).
   - 3. Iterovat. Nemigrovat všechno.
   - Cross-link na `{{ path('migration_from_crud') }}`.

10. `17.10 Anti-vzory` (`id="antivzory"`)
    - Microservices first (před BC discovery)
    - Sdílená DB napříč servicy
    - Synchronní orchestrace všeho přes REST
    - Jeden deploy artefakt pro N services
    - „Nano-services" — 50 řádků kódu, vlastní deploy

11. `17.11 Shrnutí` (`id="summary"`)

12. **FAQ:** Kolik je „microservice"? · Můžu mít 2 BC v 1 service? · Kdy přejít z monolithu? · Co vrstva BFF / API Gateway? · GraphQL federation pro DDD? · Které service vlastní data o customer napříč BC?

- [ ] **Step 5: Glosář — 4 termíny**

Modular Monolith, Distributed Monolith, Service Mesh, Integration Event (na rozdíl od Domain Event) — definice 2-3 věty + cross-link.

- [ ] **Step 6: Cross-linky**

V `templates/ddd/sagas.html.twig` u úvodní sekce doplnit:

```twig
<p>Sagy jsou nezbytné v okamžiku, kdy doménová transakce překračuje hranici Bounded Contextu — typicky napříč microservices. Detail v <a href="{{ path('microservices_and_ddd') }}">DDD a microservices</a>.</p>
```

V `templates/ddd/context_mapping.html.twig` v sekci o OHS doplnit:

```twig
<p>OHS + PL implementačně typicky znamená REST API mezi dvěma microservices. Kdy mapovat BC na microservice a kdy zůstat v modular monolithu, řeší <a href="{{ path('microservices_and_ddd') }}">samostatná kapitola</a>.</p>
```

V `templates/ddd/when_not_to_use_ddd.html.twig` přidat odrážku:

```twig
<li>Chcete microservices, ale nemáte identifikované Bounded Contexty — bez nich nemáte hranice, kde rozdělit. Detail v <a href="{{ path('microservices_and_ddd') }}#mytus">DDD a microservices</a>.</li>
```

V `templates/ddd/resources.html.twig`:

```html
<li><a href="https://samnewman.io/books/building_microservices_2nd_edition/" target="_blank" rel="noopener">Sam Newman — Building Microservices, 2nd ed. (2021)</a></li>
<li><a href="https://microservices.io/book" target="_blank" rel="noopener">Chris Richardson — Microservices Patterns (2018)</a></li>
```

- [ ] **Step 7: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/ddd-a-microservices > /dev/null && echo "OK"
git add templates/ddd/microservices_and_ddd.html.twig templates/diagrams/20_microservices/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/sagas.html.twig templates/ddd/context_mapping.html.twig templates/ddd/when_not_to_use_ddd.html.twig templates/ddd/resources.html.twig
git commit -m "feat(content): kapitola 17 — DDD a microservices"
```

---

## PHASE 4 — Symfony specific (1 kapitola)

### Task 4.1: Kapitola 16 — Autorizace v DDD na Symfony

**Files:**
- Create: `templates/ddd/authorization_in_ddd.html.twig`
- Create: `templates/diagrams/19_authorization/policy_layers.puml` + `.svg`
- Modify: `src/Controller/DddController.php`, `src/Catalog/Chapters.php`, `templates/base.html.twig`
- Modify: `templates/ddd/glossary.html.twig` (Voter, Authorization, Policy-based, Attribute-based, Aggregate-level permissions, RBAC, ABAC)
- Modify: `templates/ddd/ddd_pain_points.html.twig` (zúžit existující voter sekci, odkaz do nové kapitoly)
- Modify: `templates/ddd/anti_patterns.html.twig` (cross-link — anti-pattern „authorization v controlleru")

**Goal:** Odpovědět na *jednu* nejčastější otázku v Symfony DDD: **„kde má sedět autorizace?"**. 4 vrstvy (Edge / Use Case / Aggregate / Field), kdy kterou volit, jak to namapovat na Symfony Voters, jak nezamořit doménu Symfony Security komponentou.

- [ ] **Step 1: Route + action**

```php
    #[Route('/autorizace-v-ddd', name: 'authorization_in_ddd')]
    public function authorizationInDdd(): Response
    {
        return $this->render('ddd/authorization_in_ddd.html.twig', [
            'title' => 'Autorizace v DDD na Symfony',
        ]);
    }
```

- [ ] **Step 2: `Chapters::all()` row**

```php
['n' => '16', 'route' => 'authorization_in_ddd', 't' => 'Autorizace v DDD', 'd' => 'Voters · ACL na agregátu · policy-based · ABAC v Symfony 8', 'time' => 25, 'lvl' => 3, 'tag' => 'Praxe', 'group' => 'practice'],
```

- [ ] **Step 3: Diagram — `policy_layers.puml`**

4 koncentrické vrstvy autorizace s přiřazením, co kde:

```plantuml
@startuml
!include ../theme.iuml

title 4 vrstvy autorizace v DDD aplikaci

rectangle "1. Edge\n(API gateway / firewall)" as edge {
}
rectangle "2. Use Case\n(Application Service / Voter)" as uc {
}
rectangle "3. Aggregate\n(invarianty + permissions)" as agg $ACCENT {
}
rectangle "4. Field\n(read-time projekce)" as field {
}

edge -down-> uc : "kdo? je přihlášený?"
uc -down-> agg : "smí vykonat use case?"
agg -down-> field : "v rámci use case smí na konkrétní data?"

note right of edge
  Hrubé: anonymous vs. authenticated.
  HTTP, ne doménový kód.
  Symfony: <i>access_control</i>, <i>JWT firewall</i>.
end note

note right of uc
  Symfony Voter / VoterInterface.
  „Smí customer X stornovat order Y?"
  Doménová pravidla, ale mimo aggregate.
end note

note right of agg
  Aggregate sám rozhoduje.
  „Order může cancelovat jen vlastník."
  Throw ForbiddenDomainException.
end note

note right of field
  Read model filtrace.
  Sloupec audit_log vidí jen admin.
  Twig if + Voter, NEBO query filter.
end note
@enduml
```

- [ ] **Step 4: Šablona — celá kapitola**

Hlavička:
- `title`: `Autorizace v DDD na Symfony — Voters, ACL na agregátu, policy-based | DDD Symfony`
- `meta_description`: `Kde má sedět autorizační logika v DDD aplikaci v Symfony 8? Edge, use case, aggregate, field — 4 vrstvy s konkrétními ukázkami Voterů, doménových exceptions a policy-based přístupu.`
- `meta_keywords`: `Autorizace, Authorization, Symfony Voter, RBAC, ABAC, Policy-based, ACL, Aggregate permissions, DDD Symfony 8, Security, Doctrine, Owner-based`
- `breadcrumb_name`: `Autorizace v DDD`

`article_head`: `chapter_number: '16'`, `category: 'Praxe'`, `reading_time: 25`, `difficulty: 3`, deck: „Tři roky v DDD řeším pořád stejnou otázku: *‚smí to ten uživatel udělat?'* — patří do controlleru, do voteru, do aggregate, nebo někam jinam? Kapitola dává konkrétní 4-vrstvový framework: Edge, Use Case, Aggregate, Field. Každá vrstva odpovídá jinou otázku a používá jiný Symfony nástroj."

**Sekce:**

1. `16.01 Tři chyby s autorizací, které vidím v každém review` (`id="tri-chyby"`)
   - Chyba 1: vše v controlleru (`if ($user->getId() !== $order->customerId) throw new AccessDenied;`).
   - Chyba 2: vše v Voteru, doména nezná autorizaci (Aggregate API umí cokoli, security je „natažená přes" zvenku).
   - Chyba 3: autorizace na úrovni databázových řádků (Doctrine filtry), ale doména si neví rady, kdy je něco zakázané.
   - Diagnóza: chybějící framework, kde co umístit.

2. `16.02 Čtyři vrstvy autorizace` (`id="ctyri-vrstvy"`)
   - **Vložit diagram** (`fig: '16.2-A'`).
   - Tabulka:
     | Vrstva | Otázka | Symfony nástroj | Příklad |
     | Edge | Je přihlášený? | `access_control`, JWT firewall | `/admin/*` jen pro `ROLE_ADMIN` |
     | Use Case | Smí vykonat use case na tomhle objektu? | `Voter` | „Smí Petr cancelnout order #42?" |
     | Aggregate | Doména sama rozhoduje | doménový check + exception | „Order lze cancelnout jen 24h od vytvoření" |
     | Field | Smí vidět konkrétní pole? | Twig + Voter, query filter | „audit_log sloupec vidí jen admin" |

3. `16.03 Edge — Symfony firewall a access_control` (`id="edge"`)
   - Hrubé pravidlo „kdo může vůbec do API". Anonymous vs. authenticated. Případně role-based pro hrubě dělené sekce (`/admin/*`).
   - Code sample: `config/packages/security.yaml` ukázka.
   - Důležité: Edge **nezná doménová pravidla**. „Customer X smí na tenhle order" je use case-level, ne edge-level.

4. `16.04 Use Case — Symfony Voter` (`id="use-case-voter"`)
   - Voter = idiomatický Symfony nástroj. 1 use case = 1 Voter (nebo 1 voter podporující N atributů na 1 entitě).
   - **Code sample** — `OrderVoter` s atributy `VIEW`, `CANCEL`, `REFUND`:
     ```php
     namespace App\Ordering\Infrastructure\Security;

     final class OrderVoter extends Voter
     {
         public const VIEW = 'order.view';
         public const CANCEL = 'order.cancel';
         public const REFUND = 'order.refund';

         protected function supports(string $attribute, mixed $subject): bool
         {
             return in_array($attribute, [self::VIEW, self::CANCEL, self::REFUND], true)
                 && $subject instanceof Order;
         }

         protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
         {
             $user = $token->getUser();
             if (!$user instanceof AppUser) { return false; }

             return match ($attribute) {
                 self::VIEW   => $this->canView($subject, $user),
                 self::CANCEL => $this->canCancel($subject, $user),
                 self::REFUND => $user->hasRole('ROLE_REFUND_AGENT'),
             };
         }

         private function canView(Order $o, AppUser $u): bool
         {
             return $u->customerId()->equals($o->customerId()) || $u->hasRole('ROLE_ADMIN');
         }

         private function canCancel(Order $o, AppUser $u): bool
         {
             return $u->customerId()->equals($o->customerId());
         }
     }
     ```
   - V Application Handleru:
     ```php
     final class CancelOrderHandler
     {
         public function __construct(
             private OrderRepository $orders,
             private AuthorizationCheckerInterface $auth,
         ) {}

         public function __invoke(CancelOrderCommand $c): void
         {
             $order = $this->orders->getOrFail($c->orderId);
             if (!$this->auth->isGranted(OrderVoter::CANCEL, $order)) {
                 throw new AccessDeniedDomainException('Cancel not allowed');
             }
             $order->cancel(reason: $c->reason, when: new \DateTimeImmutable());
             $this->orders->save($order);
         }
     }
     ```
   - Voter se ptá `AuthorizationCheckerInterface` (rozhraní), které je v doménově-aware Application Service v pořádku importovat. Doména samotná to nesmí.

5. `16.05 Aggregate-level — doména sama rozhoduje` (`id="aggregate-level"`)
   - Některá pravidla **nelze** rozumně dát do Voteru, protože vyžadují znalost doménového stavu, který Voter nemá natáhnout: timing, stav agregátu, business invarianty.
   - Pravidlo (autor): pokud lze pravidlo zformulovat v jazyce *use case + uživatel + entita*, jde do Voteru. Pokud vyžaduje *aggregate state + business rule*, jde do Aggregate.
   - **Code sample** — pravidla uvnitř Aggregate:
     ```php
     final class Order
     {
         public function cancel(string $reason, \DateTimeImmutable $when): void
         {
             if ($this->status !== OrderStatus::PLACED) {
                 throw new InvalidOrderStateException('Cancel allowed only for PLACED orders');
             }
             if ($when->getTimestamp() - $this->placedAt->getTimestamp() > 86400) {
                 throw new CancellationWindowExpiredException();
             }
             $this->status = OrderStatus::CANCELLED;
             $this->releasedEvents[] = new OrderCancelled($this->id, $reason, $when);
         }
     }
     ```
   - Tady NENÍ otázka „smí Petr": tu řeší Voter v `16.04`. Tady je otázka „dá se tohle vůbec udělat?".

6. `16.06 Field-level — read model filtrace` (`id="field-level"`)
   - Příklad: order detail vidí customer, ale `audit_log` sloupec vidí jen admin.
   - 2 přístupy:
     - **Twig if** — view-level: `{% if is_granted('ORDER_AUDIT', order) %}…{% endif %}`. Jednoduché, ale data leak (data jsou v response, jen schované).
     - **Query filter** — read model vrátí různé DTO podle role. Bez data leaku.
   - Code sample obou.

7. `16.07 Policy-based přístup (ABAC)` (`id="policy-based"`)
   - Když pravidla rostou, přejít z RBAC (role-based) na ABAC (attribute-based).
   - **Code sample** — Symfony `PolicyEvaluator`:
     ```php
     interface Policy
     {
         public function name(): string;
         /** @return list<Rule> */
         public function rules(): array;
     }

     final class CancelOrderPolicy implements Policy
     {
         public function rules(): array
         {
             return [
                 new Rule('subject.customerId == user.customerId', 'Pouze vlastník'),
                 new Rule('subject.status == "PLACED"', 'Order musí být PLACED'),
                 new Rule('subject.placedAt + 24h > now()', 'V cancellation window'),
             ];
         }
     }
     ```
   - Poznámka: pro plný ABAC zvážit OPA (Open Policy Agent) — externí policy engine. Mimo rozsah Symfony, vhodné pro velké organizace.

8. `16.08 Multi-tenancy — owner kontext` (`id="multi-tenancy"`)
   - Speciální případ ABAC: vícenájemnost (různé organizace v jedné aplikaci).
   - 3 strategie: row-based (sloupec `tenant_id` všude), schema-based (DB schema per tenant), DB-based (DB per tenant).
   - Doctrine filter:
     ```php
     final class TenantFilter extends SQLFilter
     {
         public function addFilterConstraint(ClassMetadata $tm, $alias): string
         {
             if (!$tm->reflClass->implementsInterface(TenantAware::class)) { return ''; }
             return "$alias.tenant_id = " . $this->getParameter('tenant_id');
         }
     }
     ```
   - Důležité: tenant context musí být nastaven v každém request kernelu, např. listener:
     ```php
     #[AsEventListener(KernelEvents::REQUEST, priority: 100)]
     final class TenantContextListener
     {
         public function __invoke(RequestEvent $e): void
         {
             $tenantId = $e->getRequest()->attributes->get('_tenant_id');
             $this->em->getFilters()->enable('tenant')->setParameter('tenant_id', $tenantId);
         }
     }
     ```

9. `16.09 Test pyramide pro autorizaci` (`id="testing"`)
   - **Aggregate-level pravidla:** unit testy bez framework (čistá doména).
   - **Voter:** unit test Voteru s mock `TokenInterface`.
   - **End-to-end:** Symfony WebTestCase, ověří integraci.
   - Cross-link na `{{ path('testing_ddd') }}`.

10. `16.10 Anti-vzory` (`id="antivzory"`)
    - „Autorizace v controlleru" — duplicate napříč endpointy, neprověří handler volaný z Messengeru.
    - „Voter, který fetchne aggregate z DB" — duplicate query, race condition.
    - „Voter == Aggregate logic" — zopakovaná pravidla.
    - „Symfony `User` natažený do doménového Aggregate" — Symfony Security komponenta v doméně.
    - Cross-link na `{{ path('anti_patterns') }}`.

11. `16.11 Shrnutí` (`id="summary"`)
    - 4-bulleted: 4 vrstvy, RBAC pro hrubé, ABAC pro jemné, doménová pravidla v aggregate.

12. **FAQ:** Jeden Voter na entitu nebo víc? · Voter má načítat z DB? · ROLE_USER vs. attribute-based? · Co když máme 100 různých rolí? · Symfony Security komponenta v doméně? · Audit log autorizačních rozhodnutí — kam ukládat?

- [ ] **Step 5: Glosář — 4 termíny**

Voter, RBAC, ABAC, Multi-tenancy. Definice 2-3 věty + cross-link.

- [ ] **Step 6: Cross-linky**

V `templates/ddd/ddd_pain_points.html.twig` — existuje 9 zmínek Voteru, zúžit do jedné sekce „Autorizace" se 2-3 odstavci + cross-link na novou kapitolu.

V `templates/ddd/anti_patterns.html.twig` u sekce o „business logice v controllerech" doplnit:

```twig
<p>Mezi tuto chybu spadá i autorizační logika v controlleru. Detailní rozlišení 4 vrstev autorizace v <a href="{{ path('authorization_in_ddd') }}">samostatné kapitole</a>.</p>
```

- [ ] **Step 7: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/autorizace-v-ddd > /dev/null && echo "OK"
git add templates/ddd/authorization_in_ddd.html.twig templates/diagrams/19_authorization/ src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/glossary.html.twig templates/ddd/ddd_pain_points.html.twig templates/ddd/anti_patterns.html.twig
git commit -m "feat(content): kapitola 16 — Autorizace v DDD na Symfony"
```

---

## PHASE 5 — Reference & glue

### Task 5.1: Cheat Sheet (nová extras stránka)

**Files:**
- Create: `templates/ddd/cheat_sheet.html.twig`
- Modify: `src/Controller/DddController.php` (akce `cheatSheet`)
- Modify: `src/Catalog/Chapters.php` (odkomentovat řádek v `extras()`)
- Modify: `templates/base.html.twig` (`_reference_routes` rozšířit o `cheat_sheet`)
- Modify: `templates/ddd/practical_examples.html.twig` (cross-link)
- Modify: `templates/ddd/hub_reference.html.twig` (zkontrolovat — zobrazí se automaticky přes `extras`)

**Goal:** Jednostránková referenční karta — **rozhodovací strom** + tabulky pro rychlou navigaci celou knihou. Žádný nový obsah, jen agregace + hyperlinky.

- [ ] **Step 1: Action**

```php
    #[Route('/cheat-sheet', name: 'cheat_sheet')]
    public function cheatSheet(): Response
    {
        return $this->render('ddd/cheat_sheet.html.twig', [
            'title' => 'DDD Cheat Sheet',
        ]);
    }
```

- [ ] **Step 2: `Chapters::extras()` — odkomentovat**

```php
['route' => 'cheat_sheet', 't' => 'Cheat sheet', 'd' => 'Pattern decision tree + Symfony↔DDD mapping', 'tag' => 'Reference'],
```

- [ ] **Step 3: `_reference_routes` v `base.html.twig`**

```twig
{%- set _reference_routes = ['hub_reference', 'ddd_ai', 'glossary', 'resources', 'cheat_sheet'] -%}
```

- [ ] **Step 4: Šablona — `templates/ddd/cheat_sheet.html.twig`**

Hlavička:
- `title`: `DDD Cheat Sheet — pattern decision tree, Symfony↔DDD mapping | DDD Symfony`
- `meta_description`: `Jednostránková reference: kdy který DDD vzor, jak se Symfony konstrukty mapují na DDD pojmy a slovní pasti, kterým se vyhnout. Hyperlinky na všechny detailní kapitoly.`
- `meta_keywords`: `DDD cheat sheet, decision tree, Symfony DDD mapping, false friends, taktické vzory, strategické vzory`
- `breadcrumb_name`: `Cheat Sheet`
- `og_type`: `article`

`article_head`: `category: 'Reference'`, deck: „Jednostránková navigace celou knihou. Když si pamatujete jen pojem, najděte ho tady — je tu hyperlink na detail. Když si nejste jisti, který vzor použít, projděte rozhodovací strom. Tato stránka je úmyslně bez příkladů — všechno najdete v odkazovaných kapitolách."

**Sekce:**

1. `cs.01 Rozhodovací strom — co použít, kdy?` (`id="decision-tree"`)
   - Tabulka „mám problém X → použij Y → detail Z":
     | Mám | Použij | Detail |
     | Komplexní doménu, která je core-businessu | Plný DDD taktický + strategický | `{{ path('what_is_ddd') }}`, `{{ path('subdomains') }}` |
     | 2+ Bounded Contexts, které spolu komunikují | Context Map | `{{ path('context_mapping') }}` |
     | Legacy systém, který musím konzumovat | Anti-Corruption Layer (ACL) | `{{ path('context_mapping') }}#acl` |
     | Long-running proces s kompenzacemi | Saga / Process Manager | `{{ path('sagas') }}` |
     | Stav je sekvence událostí | Event Sourcing | `{{ path('event_sourcing') }}` |
     | Oddělené čtení a zápis | CQRS | `{{ path('cqrs') }}` |
     | Zápis state + publish event atomicky | Outbox Pattern | `{{ path('outbox_pattern') }}` |
     | Skládatelné business pravidlo | Specification Pattern | `{{ path('lesser_known_patterns') }}#specification` |
     | Logika, která nepatří do entity | Domain Service | `{{ path('lesser_known_patterns') }}#domain-services` |
     | Komplexní vznik aggregate | Factory | `{{ path('lesser_known_patterns') }}#factories` |
     | Workshop pro objevení domény | Event Storming | `{{ path('event_storming') }}` |
     | Reorganizace týmů kolem BC | Team Topologies | `{{ path('team_topologies') }}` |
     | Autorizace na úrovni use case | Symfony Voter | `{{ path('authorization_in_ddd') }}#use-case-voter` |
     | Migrace z CRUD postupně | Strangler Fig | `{{ path('migration_from_crud') }}` |

2. `cs.02 Symfony ↔ DDD mapping (rychlá tabulka)` (`id="symfony-mapping"`)
   - Tabulka:
     | Symfony konstrukt | DDD pojem | Poznámka |
     | `Controller` | adapter (HTTP boundary) | NEPATŘÍ tam doménová logika |
     | `MessageHandler` | Application Service / Use Case | jeden command = jeden handler |
     | `EventSubscriber` (Doctrine/Kernel) | Infrastructure | NIKOLI Domain Event Handler |
     | Domain Event Handler (custom) | Domain Service nebo Application Service | reaguje na doménový event |
     | `Entity` (s ORM) | Aggregate Root nebo Entity (DDD) | Doctrine anotace = leak; preferuj separated mapping |
     | `Repository` (Symfony) | Repository (DDD) | interface v doméně, implementace v infra |
     | `Voter` | use case authorization | NE doménové invarianty |
     | `Service` (autowire) | Application / Domain / Infra Service | rozlišuj typ! |
     | `Form` | input mapping | nevolat aggregate metody přímo |
     | `Symfony Messenger transport` | Integration channel | sync = in-process; async = cross-BC |

3. `cs.03 False friends — slovní pasti` (`id="false-friends"`)
   - Tabulka:
     | Pojem | Co znamená v DDD | Co znamená v Symfony / obecně | Tip |
     | ACL | Anti-Corruption Layer | Access Control List | V DDD obvykle to první |
     | Repository | Doménový kolekce-like přístup k aggregátům | Doctrine `EntityRepository` třída | DDD Repository je interface, Doctrine implementace |
     | Service | Stateless objekt (Domain/Application/Infra) | Symfony service (= cokoli v DI) | Vždy kvalifikuj typ |
     | Event | Doménová událost (`OrderPlaced`) | Symfony EventDispatcher event | Symfony event ≠ Domain event |
     | Entity | Objekt s identitou v doméně | Doctrine Entity (ORM mapped) | DDD Entity může být uvnitř Aggregate |
     | Aggregate | Konzistentní cluster objektů | (Symfony nemá tento termín) | — |
     | Domain | Oblast businessu | doctrine `Domain` (rare) / Twig domain | obvykle DDD |

4. `cs.04 Reading paths — kdo si má co přečíst` (`id="reading-paths"`)
   - 4 cesty:
     - **Junior PHP devloper, první kontakt s DDD:** `01 → 02 → 05 → 13` (Co je DDD → Koncepty → Vertical Slice → Performance)
     - **Architekt na novém projektu:** `03 → 04 → 06 → 14 → 15 → 17` (Subdomény → Context Map → Architectural Styles → Event Storming → Team Topologies → Microservices)
     - **Migrace existujícího Symfony projektu:** `01 → 02 → 14 → 20 → 22` (Co je DDD → Koncepty → Event Storming → Migrace → Anti-vzory)
     - **Tech lead před release:** `08 → 09 → 10 → 11 → 19` (CQRS → ES → Sagas → Outbox → Testing)

5. `cs.05 Externí zdroje — must-read` (`id="must-read"`)
   - Tabulka 5 knih + 3 talks (cross-link na `{{ path('resources') }}` pro plnou bibliografii).

- [ ] **Step 5: Cross-linky**

V `templates/ddd/practical_examples.html.twig` na konec přidat:

```twig
<p>Pro rychlou orientaci, který vzor pro jaký problém, použijte <a href="{{ path('cheat_sheet') }}">DDD Cheat Sheet</a>.</p>
```

- [ ] **Step 6: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/cheat-sheet > /dev/null && echo "OK"
curl -fs http://127.0.0.1:8000/reference | grep -c 'Cheat sheet' # → ≥ 1 (objeví se v hub extras grid)

git add templates/ddd/cheat_sheet.html.twig src/Catalog/Chapters.php src/Controller/DddController.php templates/base.html.twig templates/ddd/practical_examples.html.twig
git commit -m "feat(content): cheat sheet — pattern decision tree + Symfony↔DDD mapping"
```

---

### Task 5.2: Glosář — sekce „Slovní pasti / False Friends" + „Symfony ↔ DDD mapping"

**Files:**
- Modify: `templates/ddd/glossary.html.twig` (přidat 2 nové sekce na konec)

**Goal:** Doplnit glosář o dvě nové top-level sekce, které dříve neměly kde sedět: false-friends slovník a kompletní Symfony↔DDD mapping (rozšíření tabulky z cheat sheetu).

- [ ] **Step 1: Najít konec poslední sekce v `glossary.html.twig`**

Otevřít soubor, najít poslední `</section>` před `</div>` (`<div class="art-body">` close). Tam vložit:

- [ ] **Step 2: Přidat sekci „Slovní pasti / False Friends"**

```twig
<section id="false-friends" aria-labelledby="false-friends-heading">
    <h2 id="false-friends-heading" class="h-section"><span class="h-num">XX</span> Slovní pasti — false friends</h2>

    <div class="note" role="note" aria-labelledby="false-friends-intro-heading">
        <h3 id="false-friends-intro-heading">Proč to vzniká</h3>
        <p>
            DDD termíny se sémanticky překrývají s mnoha pojmy z PHP/Symfony světa, ze Symfony bezpečnosti, z Doctrine, ze Spring/Java kultury. Stejné slovo má v různých kontextech různý význam — a běžný code review tu kontradikci přehlíží. Tato sekce explicitně rozplete pasti, které vidím v každém DDD projektu.
        </p>
    </div>

    <dl class="glossary-list">
        <div class="glossary-entry" id="ff-acl">
            <dt><dfn>ACL</dfn></dt>
            <dd>
                <p><strong>V DDD:</strong> Anti-Corruption Layer — překládací vrstva mezi BC a cizím modelem. <strong>Mimo DDD:</strong> Access Control List — autorizační seznam (kdo smí na co). V DDD literatuře se „ACL" vždy myslí to první. <a href="{{ path('context_mapping') }}#acl">Detail</a>.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-repository">
            <dt><dfn>Repository</dfn></dt>
            <dd>
                <p><strong>V DDD:</strong> doménová abstrakce, interface v `App\<BC>\Domain` deklarující `get()`/`save()` agregátu, jako by žil v paměti. <strong>V Symfony/Doctrine:</strong> `Doctrine\ORM\EntityRepository` — třída s `findBy`/`findAll`. Pravidlo: DDD Repository je vždy interface; Doctrine `EntityRepository` je vždy implementace v `App\<BC>\Infrastructure`.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-service">
            <dt><dfn>Service</dfn></dt>
            <dd>
                <p><strong>V DDD:</strong> Domain Service (doménová pravidla bez vlastníka), Application Service (use case koordinace), Infrastructure Service (porty/adaptéry). <strong>V Symfony:</strong> jakákoli třída v DI kontejneru. Pravidlo: nikdy nepojmenovávejte třídu `FooService` bez kvalifikace, jaký typ.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-event">
            <dt><dfn>Event</dfn></dt>
            <dd>
                <p><strong>V DDD:</strong> Domain Event (`OrderPlaced` — fakt, který nastal v doméně). <strong>V Symfony:</strong> objekt předávaný `EventDispatcher`u — typicky framework / kernel události (`KernelEvents::REQUEST`). Třídy se nemají sdílet — Domain Events nesmí extends `Symfony\Contracts\EventDispatcher\Event`, jinak doména závisí na frameworku.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-entity">
            <dt><dfn>Entity</dfn></dt>
            <dd>
                <p><strong>V DDD:</strong> objekt s identitou (Customer, Order). Může být kořenem agregátu nebo uvnitř agregátu. <strong>V Doctrine:</strong> třída s `#[ORM\Entity]` anotací mapovaná na databázový řádek. DDD Entity může (ale nemusí) být současně Doctrine Entity.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-domain">
            <dt><dfn>Domain</dfn></dt>
            <dd>
                <p><strong>V DDD:</strong> oblast byznysu, kterou software modeluje (Ordering, Billing, Catalog). <strong>V Twig/i18n:</strong> translation domain (`messages`, `validators`). Mimo DDD je „Domain" v PHP velmi vzácné, takže matení bývá jen v Twig.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-controller">
            <dt><dfn>Controller</dfn></dt>
            <dd>
                <p><strong>V Symfony:</strong> třída obsluhující HTTP request. <strong>V DDD pojmosloví:</strong> infrastructure adapter — patří do `App\<BC>\Infrastructure\Http\`. Nikoli do Application a už vůbec ne do Domain.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-handler">
            <dt><dfn>Handler</dfn></dt>
            <dd>
                <p><strong>V Symfony Messenger:</strong> třída implementující business logiku spuštěnou message busem. Tipicky `#[AsMessageHandler]`. <strong>V DDD:</strong> Application Service (Use Case) — pokud zpracovává Command; Domain Event Handler — pokud reaguje na Domain Event. Stejná třída, různé pojmenování v různém kontextu.</p>
            </dd>
        </div>

        <div class="glossary-entry" id="ff-aggregate">
            <dt><dfn>Aggregate</dfn></dt>
            <dd>
                <p><strong>V DDD:</strong> shluk objektů, které se mění atomicky. <strong>V SQL/reportech:</strong> agregační funkce (`SUM`, `COUNT`). Pojmy se neprolínají, ale junior dev to často mate.</p>
            </dd>
        </div>
    </dl>
</section>
```

(`XX` se nahradí finálním číslem podle počtu existujících sekcí — projít glossary a zjistit.)

- [ ] **Step 3: Přidat sekci „Symfony ↔ DDD mapping"**

```twig
<section id="symfony-ddd-mapping" aria-labelledby="symfony-ddd-mapping-heading">
    <h2 id="symfony-ddd-mapping-heading" class="h-section"><span class="h-num">YY</span> Symfony ↔ DDD mapping (rozšířená tabulka)</h2>

    <div class="note">
        <p>Krátká verze této tabulky je v <a href="{{ path('cheat_sheet') }}#symfony-mapping">Cheat Sheetu</a>. Tato je rozšířená — s odkazy na konkrétní kapitoly a anti-vzory.</p>
    </div>

    <table class="glossary-table">
        <thead>
            <tr>
                <th>Symfony konstrukt</th>
                <th>DDD pojem</th>
                <th>Kde to v projektu sedí</th>
                <th>Detail / anti-vzor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>Controller</code> (HTTP)</td>
                <td>Infrastructure adapter</td>
                <td><code>App\&lt;BC&gt;\Infrastructure\Http\</code></td>
                <td><a href="{{ path('architectural_styles') }}#hexagonal">Hexagonal — Adapter</a> · <a href="{{ path('anti_patterns') }}">Anti-vzor: business logika v controlleru</a></td>
            </tr>
            <tr>
                <td><code>#[AsMessageHandler]</code> handler</td>
                <td>Application Service / Use Case</td>
                <td><code>App\&lt;BC&gt;\Application\Handler\</code></td>
                <td><a href="{{ path('cqrs') }}">CQRS</a></td>
            </tr>
            <tr>
                <td><code>EntityRepository</code> (Doctrine)</td>
                <td>Repository — implementace</td>
                <td><code>App\&lt;BC&gt;\Infrastructure\Doctrine\Doctrine&lt;X&gt;Repository.php</code></td>
                <td>Interface je v doméně!</td>
            </tr>
            <tr>
                <td><code>Voter</code></td>
                <td>Use case authorization</td>
                <td><code>App\&lt;BC&gt;\Infrastructure\Security\</code></td>
                <td><a href="{{ path('authorization_in_ddd') }}#use-case-voter">Autorizace — Voter</a></td>
            </tr>
            <tr>
                <td><code>Form</code></td>
                <td>Input mapping (DTO)</td>
                <td><code>App\&lt;BC&gt;\Infrastructure\Http\Form\</code></td>
                <td>Form `getData()` → Command DTO, ne přímo Aggregate</td>
            </tr>
            <tr>
                <td>Twig template</td>
                <td>View / Read Model</td>
                <td><code>templates/&lt;bc&gt;/</code></td>
                <td>Read model, ne write model</td>
            </tr>
            <tr>
                <td>Messenger transport (sync)</td>
                <td>In-process command/event bus</td>
                <td>monolith</td>
                <td>—</td>
            </tr>
            <tr>
                <td>Messenger transport (AMQP)</td>
                <td>Integration event channel</td>
                <td>cross-BC nebo cross-service</td>
                <td><a href="{{ path('outbox_pattern') }}">Outbox</a> + <a href="{{ path('microservices_and_ddd') }}">Microservices</a></td>
            </tr>
            <tr>
                <td><code>Console Command</code></td>
                <td>Driving adapter (alternativa HTTP)</td>
                <td><code>App\&lt;BC&gt;\Infrastructure\Cli\</code></td>
                <td>Volá stejné Application Services jako HTTP Controller</td>
            </tr>
            <tr>
                <td>Doctrine Migration</td>
                <td>Schema evolution</td>
                <td><code>migrations/</code></td>
                <td>Žádný DDD ekvivalent — čistě infrastruktura</td>
            </tr>
            <tr>
                <td><code>EventSubscriber</code> (Doctrine `postPersist`)</td>
                <td>Infrastructure (NE Domain Event Handler)</td>
                <td><code>App\&lt;BC&gt;\Infrastructure\Doctrine\EventListener\</code></td>
                <td>Anti-vzor: doménová pravidla v Doctrine listeneru</td>
            </tr>
        </tbody>
    </table>
</section>
```

- [ ] **Step 4: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/glosar | grep -c 'false-friends\|symfony-ddd-mapping' # → ≥ 2
curl -fs http://127.0.0.1:8000/glosar | grep -c 'Anti-Corruption Layer' # → ≥ 6 (existujících + nové)

git add templates/ddd/glossary.html.twig
git commit -m "docs(glossary): nové sekce — false friends + Symfony↔DDD mapping"
```

---

### Task 5.3: Refactoring kuchařka v `migration_from_crud.html.twig`

**Files:**
- Modify: `templates/ddd/migration_from_crud.html.twig` (nová sekce)
- Modify: `templates/ddd/anti_patterns.html.twig` (cross-link)

**Goal:** Doplnit do migrační kapitoly **konkrétní recepty** — krátké, akční. Né „migrace celého projektu", ale „mám tuhle situaci, jaké jsou kroky 1, 2, 3".

- [ ] **Step 1: Najít vhodné místo pro novou sekci**

Otevřít `templates/ddd/migration_from_crud.html.twig`. Najít sekci o závěru / shrnutí (typicky předposlední). Před ní vložit novou sekci.

- [ ] **Step 2: Přidat sekci**

```twig
<section id="refactoring-kuchařka" aria-labelledby="refactoring-kuchařka-heading">
    <h2 id="refactoring-kuchařka-heading" class="h-section"><span class="h-num">XX.NN</span> Refactoring kuchařka — krátké recepty</h2>

    <p>
        Strangler Fig je strategický pohled na celou migraci. V denní praxi narazíte na opakující se mikrosituace. Tato kuchařka obsahuje 8 nejčastějších, každá ve formátu <em>„symptomy → krok 1, 2, 3"</em>.
    </p>

    <h3 id="recept-anemic-entita-heading">Recept 1: Mám anémickou Doctrine entitu</h3>
    <p><strong>Symptomy:</strong> entita má jen gettery/settery, vší logika v Service třídě.</p>
    <ol>
        <li>Identifikuj invarianty entity (co nesmí být porušeno).</li>
        <li>Pro každý invariant najdi metodu v `*Service`, která ho dnes drží.</li>
        <li>Přesuň metodu do entity, getter/setter na private nebo zruš.</li>
        <li>Service se stane tenkým koordinátorem (Application Service) — jen volá entitu, transakce, eventy.</li>
        <li>Cross-link: <a href="{{ path('anti_patterns') }}">Anti-vzor: Anemic Domain Model</a>, <a href="{{ path('lesser_known_patterns') }}#domain-services">Domain Services vs. Application Services</a>.</li>
    </ol>

    <h3 id="recept-doctrine-anotace-heading">Recept 2: Doctrine anotace v doménové třídě</h3>
    <p><strong>Symptomy:</strong> `App\Domain\Order` má `#[ORM\Entity]`, doména závisí na Doctrine.</p>
    <ol>
        <li>Vytvoř separated mapping — XML/YAML schema v `App\<BC>\Infrastructure\Doctrine\Mapping\`. Doménová třída zůstane bez anotací.</li>
        <li>Přesuň `setOrder*Repository*Configuration()` do `services.yaml`.</li>
        <li>Otestuj: `composer require --dev phpat/phpat` a přidej rule `App\Domain\* nesmí závisět na Doctrine\*`.</li>
    </ol>

    <h3 id="recept-id-string-heading">Recept 3: Mám primitivní ID jako `string` / `int`</h3>
    <p><strong>Symptomy:</strong> `Order::$id: string`, kdekoli se předává jen `string`.</p>
    <ol>
        <li>Zaveď VO `OrderId` (`final readonly class OrderId { public function __construct(public Ulid $value) {} }`).</li>
        <li>Doctrine custom type pro `OrderId` (mapping z DB string ↔ VO).</li>
        <li>Postupně refaktoruj signature napříč handlery. PHPStan na úrovni 8 odhalí každý zapomenutý `string`.</li>
    </ol>

    <h3 id="recept-shared-tabulka-heading">Recept 4: Sdílím Doctrine tabulku napříč BC</h3>
    <p><strong>Symptomy:</strong> tabulka `users` se používá v Ordering BC i Billing BC, oba ji modify.</p>
    <ol>
        <li>Identify owning BC (typicky Identity).</li>
        <li>Ostatní BC ji nesmí modify — jen READ. Přesuň reads do read-modelů (každý BC má vlastní projekci).</li>
        <li>Modify nahraď voláním Identity API (sync HTTP nebo async event publishing s outbox).</li>
        <li>Cross-link: <a href="{{ path('outbox_pattern') }}">Outbox Pattern</a>.</li>
    </ol>

    <h3 id="recept-business-logika-controlleru-heading">Recept 5: Business logika v controlleru</h3>
    <p><strong>Symptomy:</strong> 200-řádkový controller s if-else stromem doménových rozhodnutí.</p>
    <ol>
        <li>Vytvoř `Command` DTO + `CommandHandler` v Application vrstvě.</li>
        <li>Controller se zúží na: validate input → dispatch command → vrátit response.</li>
        <li>Authorization přesun do Voteru (cross-link <a href="{{ path('authorization_in_ddd') }}">Autorizace</a>).</li>
    </ol>

    <h3 id="recept-aggregate-bobtna-heading">Recept 6: Aggregate bobtná (1000+ řádků)</h3>
    <p><strong>Symptomy:</strong> `Order` má 30 metod a 15 polí.</p>
    <ol>
        <li>Najdi pole, která se mění nezávisle (různé invarianty, různé use cases).</li>
        <li>Zvážit split na 2 agregáty (např. `Order` + `OrderShipment`). Spojí je sdílené `OrderId`, žádná silná reference.</li>
        <li>Specifikační logiku vyextrahovat do `Specification` tříd (cross-link <a href="{{ path('lesser_known_patterns') }}#specification">Specifications</a>).</li>
    </ol>

    <h3 id="recept-event-publish-uvnitr-heading">Recept 7: `eventDispatcher->dispatch()` uvnitř doménové metody</h3>
    <p><strong>Symptomy:</strong> Aggregate volá Symfony `EventDispatcher` přímo.</p>
    <ol>
        <li>Aggregate uchová eventy v `private array $releasedEvents`.</li>
        <li>Aplikační handler po `repository->save()` volá `$order->releaseEvents()` a publikuje (přes outbox).</li>
        <li>Doména ztratí závislost na Symfony EventDispatcheru. Test je čistý.</li>
        <li>Cross-link: <a href="{{ path('outbox_pattern') }}#aggregate-publishes">Outbox — Aggregate publikuje</a>.</li>
    </ol>

    <h3 id="recept-fields-jako-stav-heading">Recept 8: Stav je sloupec `string $status`</h3>
    <p><strong>Symptomy:</strong> `Order::$status: string`, podmínky všude `if ($order->status === 'PLACED')`.</p>
    <ol>
        <li>Zaveď enum (PHP 8.1+): `enum OrderStatus: string { case PLACED = 'placed'; case CANCELLED = 'cancelled'; }`.</li>
        <li>Aggregate metody dělají transitions: `$this->status = OrderStatus::CANCELLED`.</li>
        <li>Pro komplexní transition rules zvažit State Machine (Symfony Workflow component nebo doménová reprezentace).</li>
    </ol>
</section>
```

- [ ] **Step 3: Cross-link v `anti_patterns.html.twig`**

U sekcí o jednotlivých anti-vzorech (anemic, primitive obsession, business v controlleru) doplnit:

```twig
<p>Praktický recept na refactoring viz <a href="{{ path('migration_from_crud') }}#refactoring-kucha%C5%99ka">Refactoring kuchařka</a>.</p>
```

- [ ] **Step 4: Verifikace + commit**

```bash
curl -fs http://127.0.0.1:8000/migrace-z-crud > /dev/null && echo "OK"
curl -s http://127.0.0.1:8000/migrace-z-crud | grep -c 'recept-anemic-entita\|recept-doctrine-anotace' # → ≥ 2

git add templates/ddd/migration_from_crud.html.twig templates/ddd/anti_patterns.html.twig
git commit -m "docs(migration): refactoring kuchařka — 8 krátkých receptů"
```

---

## PHASE 6 — Cross-cutting consistency sweep

Po Fázi 5 jsou všechny kapitoly + glue zhotovené. Tato fáze zajistí, že kniha jako celek **drží** — žádné mrtvé linky, konzistentní data v hubech, aktualizovaný footer, FAQ otázky neopakují odpovědi z jiných kapitol.

### Task 6.1: Audit interních linků

**Files:**
- Read: všechny `templates/ddd/*.html.twig`

- [ ] **Step 1: Stáhnout sitemapu a zjistit, jestli odkazované routes existují**

```bash
symfony server:start -d
# Stáhnout sitemap (Symfony nemá vestavěný — vygenerovat ad-hoc):
php bin/console debug:router | awk 'NR>2 {print $2}' | sort -u > /tmp/routes.txt

# Najít všechny path() volání v templates:
grep -rohE "path\('([a-z_]+)'" templates/ddd/ | sed -E "s/path\('([^']+)'/\1/" | sort -u > /tmp/used.txt

# Diff — co se používá, ale neexistuje:
comm -23 /tmp/used.txt /tmp/routes.txt
```

Expected: prázdný výstup. Pokud něco vypadne, zjistit, kterou kapitolou je to způsobené, a doplnit chybějící cross-link target nebo opravit odkaz.

- [ ] **Step 2: Audit anchor linků**

Pro každý `path('foo') ~ '#anchor'` ověřit, že `anchor` v cílovém template existuje:

```bash
grep -rohE "path\('([a-z_]+)'\)\}\}#([a-z0-9-]+)" templates/ddd/ \
  | sed -E "s/path\('([^']+)'\)\}\}#(.*)/\1 \2/" \
  | sort -u > /tmp/anchors.txt

while read route anchor; do
    file="templates/ddd/${route}.html.twig"
    if ! grep -qE "id=\"${anchor}\"" "$file" 2>/dev/null; then
        echo "MISSING: $route#$anchor"
    fi
done < /tmp/anchors.txt
```

Expected: prázdný výstup. Pokud něco chybí, oprava na straně cílové kapitoly (přidat `id`) nebo zdroje (opravit anchor).

- [ ] **Step 3: Commit (jen pokud byly úpravy)**

```bash
git add templates/ddd/
git commit -m "fix(content): audit interních linků — opravené anchor cíle"
```

### Task 6.2: Audit JSON-LD a breadcrumbs

**Files:**
- Read: všechny `templates/ddd/*.html.twig` (kromě hubů a `_partials/`)

- [ ] **Step 1: Ověřit, že každá kapitola má `block structured_data` s `TechArticle`**

```bash
for f in templates/ddd/*.html.twig; do
    if [[ "$f" == *hub_* ]] || [[ "$f" == *index* ]]; then continue; fi
    if ! grep -q "TechArticle" "$f"; then
        echo "MISSING TechArticle: $f"
    fi
done
```

- [ ] **Step 2: Ověřit, že každá kapitola má `block breadcrumb_name`**

```bash
for f in templates/ddd/*.html.twig; do
    if [[ "$f" == *hub_* ]] || [[ "$f" == *index* ]]; then continue; fi
    if ! grep -q "block breadcrumb_name" "$f"; then
        echo "MISSING breadcrumb: $f"
    fi
done
```

- [ ] **Step 3: Validovat JSON-LD pro každou novou kapitolu přes Schema.org Validator**

Manuální (Playwright):
- Pro každou novou kapitolu (`/subdomeny`, `/context-mapping`, ...) navštívit https://validator.schema.org/#url=<URL>
- Ověřit, že `TechArticle` validuje bez warnings.

- [ ] **Step 4: Commit (jen pokud byly úpravy)**

```bash
git add templates/ddd/
git commit -m "fix(seo): doplněné chybějící JSON-LD a breadcrumbs"
```

### Task 6.3: Aktualizace `index.html.twig` (homepage)

**Files:**
- Modify: `templates/ddd/index.html.twig`

- [ ] **Step 1: Zkontrolovat, jestli homepage zobrazuje fixní počty**

Otevřít `templates/ddd/index.html.twig`, najít kde se počítá počet kapitol / čas. Pokud je hardcoded číslo (`{{ 16 }}` nebo `16 kapitol`), nahradit dynamickým počítáním přes `Chapters::all()|length` nebo Twig funkci `ddd_chapters()|length`.

- [ ] **Step 2: Pokud má homepage hero text odkazující na konkrétní oblasti („tactical patterns", „strategic patterns") — sjednotit s novou strukturou**

Přidat odkaz na novou skupinu `Strategie` (hub `hub_strategic`), pokud na hlavní stránce existuje sekce s odkazy na hub stránky.

- [ ] **Step 3: Aktualizovat lead paragraph / hero copy**

Pokud je kniha označena jako „16 kapitol", změnit na `25 kapitol` (nebo dynamicky `{{ ddd_chapters()|length }} kapitol`).

- [ ] **Step 4: Commit**

```bash
git add templates/ddd/index.html.twig
git commit -m "docs(homepage): aktualizace počtu kapitol a sjednocení s novou strukturou"
```

### Task 6.4: Footer + hub_practice split (volitelné)

**Files:**
- Modify: `templates/base.html.twig` (footer)
- Modify: `templates/ddd/hub_practice.html.twig`

- [ ] **Step 1: Footer — zkontrolovat, že `_foot_practice_top|slice(0, 4)` stále dává smysl**

Po fázi 5 má `practice` skupina 11 kapitol. Footer aktuálně zobrazuje top 4 + „Více →". Zvážit zvýšení slice na 6, nebo nechat 4. Rozhodnutí: nechat 4 (kompaktnost), `Více →` zaktualizuje počet automaticky.

- [ ] **Step 2: Footer Strategie sloupec**

Přidat 5. sloupec (po Základech) pro `_foot_strategic`. Layout dnes je 4-column grid — pravděpodobně bude třeba upravit CSS na 5-column nebo umístit Strategie pod Základy ve společném sloupci.

Pragmaticky: Strategie bude vlastní mini-section v existujícím sloupci „Základy" (Strategie a Základy se logicky doplňují). Sloupec přejmenovat na „Filosofie & strategie", obsahuje Základy + Strategie.

```twig
<div class="foot-a-col">
    <h3><a href="{{ path('hub_basics') }}">Základy</a></h3>
    <ul class="foot-a-list">
        {% for c in _foot_basics %}
            <li><a href="{{ path(c.route) }}"><span class="foot-a-num">{{ c.n }}</span><span>{{ c.t }}</span></a></li>
        {% endfor %}
    </ul>
    {% if _foot_strategic|length > 0 %}
        <h3 style="margin-top:1.5rem"><a href="{{ path('hub_strategic') }}">Strategie</a></h3>
        <ul class="foot-a-list">
            {% for c in _foot_strategic %}
                <li><a href="{{ path(c.route) }}"><span class="foot-a-num">{{ c.n }}</span><span>{{ c.t }}</span></a></li>
            {% endfor %}
        </ul>
    {% endif %}
</div>
```

- [ ] **Step 3: hub_practice — zvážit split do 2 sub-částí**

Když má skupina `practice` 11 kapitol, jeden velký grid může vypadat přeplněně. Zvážit split:
- Část A „Discovery a tým": event_storming, team_topologies, authorization_in_ddd
- Část B „Implementace a evoluce": microservices_and_ddd, practical_examples, testing_ddd, migration_from_crud, ddd_pain_points, anti_patterns, when_not_to_use_ddd, case_study

Pokud `_partials/hub.html.twig` umí jen jeden grid, rozšířit partial o volitelnou druhou sekci. Alternativně: nechat jeden velký grid a zatím to neřešit.

Rozhodnutí: **odložit do separátního ticketu**. Cílem této fáze je integrita, ne re-design hubu.

- [ ] **Step 4: Commit**

```bash
git add templates/base.html.twig
git commit -m "docs(footer): nový sub-sloupec Strategie pod Základy"
```

### Task 6.5: Ověřit chapter_nav prev/next napříč novými kapitolami

**Files:**
- Read: `src/Catalog/Chapters.php` (final version)

- [ ] **Step 1: Spustit smoke test, který projde každou kapitolu a ověří prev/next link**

```bash
for route in what_is_ddd basic_concepts subdomains context_mapping horizontal_vs_vertical architectural_styles implementation_in_symfony cqrs event_sourcing sagas outbox_pattern lesser_known_patterns performance_aspects event_storming team_topologies authorization_in_ddd microservices_and_ddd practical_examples testing_ddd migration_from_crud ddd_pain_points anti_patterns when_not_to_use_ddd case_study ddd_ai; do
    url="http://127.0.0.1:8000/$(php bin/console debug:router $route 2>/dev/null | awk '/Path/ {print $3}')"
    curl -fs "$url" > /dev/null && echo "$route OK" || echo "$route FAIL"
done
```

Expected: 25× OK.

- [ ] **Step 2: Vizuální (Playwright)**

Navigovat začátek (`what_is_ddd`) a klikat na „Další →" 24× — projít všechny kapitoly v pořadí. Ověřit:
- Žádný 404
- Display číslo na článku se inkrementuje (01 → 25)
- Breadcrumb v `<head>` JSON-LD obsahuje správný `name`

### Task 6.6: FAQ deduplication audit

**Files:**
- Read: všechny `templates/ddd/*.html.twig`

- [ ] **Step 1: Zkontrolovat, že FAQ otázky v různých kapitolách neopakují přesně stejné odpovědi**

Manuálně projít FAQ ve 9 nových kapitolách. Pravidlo:
- „Co je Bounded Context?" je legitimní v `what_is_ddd` i v `basic_concepts`, ale odpověď v `basic_concepts` musí jít *hlouběji*.
- Pokud jsou přesně stejné, zkrátit jeden link na druhý.

- [ ] **Step 2: Aktualizovat existující FAQ, kde čtenáři teď nabídneme novou kapitolu**

V `templates/ddd/what_is_ddd.html.twig` v existujícím FAQ:
- Q: „Kdy se DDD nevyplatí použít?" — odpověď už odkazuje na `when_not_to_use_ddd`. Doplnit i odkaz na `subdomains` (pětibodový test).
- Q: „Co je Bounded Context?" — doplnit odkaz na `context_mapping` pro vztahy mezi BC.

V `templates/ddd/basic_concepts.html.twig`:
- Q: „Co je Domain Service?" (pokud je) — odkaz na novou `lesser_known_patterns#domain-services`.

### Task 6.7: Final smoke test — celá kniha

- [ ] **Step 1: Crawler / link checker**

Pokud je v projektu `wget` nebo `linkchecker`:

```bash
wget --spider --recursive --no-verbose --no-parent --reject-regex '\?' \
     --exclude-directories=/build,/api,/_profiler \
     http://127.0.0.1:8000/ 2>&1 | grep -E 'broken|404'
```

Expected: žádné broken linky.

- [ ] **Step 2: Playwright — manuální check 5 typických cest**

1. Homepage → Strategie hub → Subdomény → klik na cross-link Context Mapping → klik na FAQ otázku → zpět na hub.
2. Homepage → Vzory hub → Outbox Pattern → klik na anchor `#schema` → ověřit že kotva funguje.
3. Homepage → Reference hub → Cheat Sheet → klik na rozhodovací strom položku → cílová kapitola se otevře.
4. Glosář → false-friends → ACL false friend → klik na `Detail` → otevře se Context Mapping.
5. Migrace → Refactoring kuchařka → klik na recept 1 → klik na anti_patterns cross-link.

- [ ] **Step 3: Cache clear, fresh build smoke**

```bash
php bin/console cache:clear --env=prod
php bin/console assets:install --env=prod
symfony server:stop && symfony server:start -d
# spustit smoke test ze Stepu 1 znovu na prod profilu
```

- [ ] **Step 4: Commit (zakončující — pokud byly drobnosti)**

```bash
git add -A
git commit -m "chore: final cross-cutting consistency sweep"
```

---

## Self-Review Checklist

Po dokončení této fáze 6 znovu projít plán s čerstvým pohledem:

### Spec coverage

| Požadavek z user briefingu | Pokrytí (task) |
|----------------------------|----------------|
| Strategický DDD: Subdomény (Core/Supporting/Generic) | Task 1.1 |
| Strategický DDD: Context Mapping (8 vztahů) | Task 1.2 |
| Hexagonal / Onion / Clean Architecture | Task 1.3 |
| Event Storming + Domain Storytelling | Task 2.1 |
| Conway's Law / Team Topologies | Task 2.2 |
| Outbox Pattern (vlastní kapitola) | Task 3.1 |
| Méně známé taktické vzory (Specifications, Domain Services, Factories, Modules) | Task 3.2 |
| DDD a microservices | Task 3.3 |
| Autorizace v DDD na Symfony | Task 4.1 |
| Cheat sheet / one-pager | Task 5.1 |
| „False friends" sekce | Task 5.2 |
| Symfony↔DDD mapping | Task 5.2 (rozšířená tabulka) + Task 5.1 (krátká verze) |
| Refactoring kuchařka | Task 5.3 |
| „Nezapomělo nic doupravit" / „celkově zapadlo" | Phase 6 (1–7) |
| Nejkvalitnější ukázky kódů | každý task má min. 2-4 code samples |
| Diagramy | každá nová kapitola má min. 1 PlantUML diagram (10 nových) |
| Konzistentní s obsahem | A3 (povinný checklist), A4 (cross-cutting), Phase 6 (audit) |

### Placeholder scan

Žádné z následujícího se v plánu neobjevuje:
- ❌ „TBD" / „TODO" / „později"
- ❌ „add appropriate error handling"
- ❌ „write tests for the above"
- ❌ „similar to Task N"
- ❌ Steps bez konkrétního kódu / příkazu

Výjimky (vědomé):
- ✓ Prioritní `XX` / `YY` placeholders v glossary section číslech (Task 5.2) — ty se vyřeší při implementaci podle aktuálního počtu sekcí. Engineer to musí přečíst a doplnit.
- ✓ V cheat_sheet a glossary `n="XX"` placeholders — totéž.

### Type consistency

Konzistence názvů napříč tasky:
- `Order` aggregate používán ve více kapitolách s konzistentní API (`Order::place()`, `Order::cancel()`, `Order::releaseEvents()`). ✓
- `OrderId` jako VO ve všech příkladech. ✓
- `OutboxMessage` schéma identické v Task 3.1 i v křížových odkazech. ✓
- Route names — žádné kolize: `subdomains`, `context_mapping`, `architectural_styles`, `outbox_pattern`, `lesser_known_patterns`, `event_storming`, `team_topologies`, `authorization_in_ddd`, `microservices_and_ddd`, `cheat_sheet`. ✓
- URL slug konvence: kebab-case s českými/anglickými výrazy podle existujícího vzoru. ✓
- Group names: `basics`, `strategic` (nová), `patterns`, `practice`, `reference`. ✓

### Estimated effort

- 9 nových kapitol × 6–10 hodin čisté psané kapitoly = 60–90 hodin
- 10 PlantUML diagramů × 1 hodina = 10 hodin
- Cross-cutting (Phase 0, 6) = 8 hodin
- Glossary expansion (Task 5.2) = 4 hodiny
- Cheat Sheet (Task 5.1) = 3 hodiny
- Refactoring kuchařka (Task 5.3) = 3 hodiny
- **Celkem: ~90–120 hodin** = 12–15 pracovních dnů full-time

Realistický kalendář: 6–8 týdnů při 50% zatížení mimo běžnou práci.

---

## Execution Handoff

Plán dokončen a uložen do `docs/superpowers/plans/2026-04-29-rozsireni-knihy-ddd.md`. Dvě možnosti exekuce:

**1. Subagent-Driven (doporučeno pro takhle dlouhý plán)** — dispatchuju čerstvý subagent na každý task, mezi tasky review, rychlá iterace. Doporučeno zejména pro Tasky 1.1, 1.2, 1.3, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1 (každá kapitola samostatný subagent v paralelní sérii s checkpoint mezi fázemi).

**2. Inline Execution** — exekuovat tasky v této session pomocí `superpowers:executing-plans`, batch execution s checkpointy. Vhodné pro Phase 0 a Phase 6 (kratší, hodně malých změn).

**Doporučení autora plánu:** rozdělit na 3 vlny:
- **Vlna 1** (Phase 0 + Phase 1) — strategické základy, sekvenčně subagent-driven, 1 vlna ~3 týdny.
- **Vlna 2** (Phase 2, 3, 4 paralelně) — discovery + production patterns, paralelní subagenti, 1 vlna ~2 týdny.
- **Vlna 3** (Phase 5 + 6) — glue + sweep, inline, 1 vlna ~1 týden.

Před spuštěním Vlny 1 je vhodné založit feature branch + worktree:

```bash
git checkout -b rozsireni-knihy
git worktree add ../ddd-v-symfony.rozsireni rozsireni-knihy
# přepnutí do worktree
```

**Která vlna nebo task má začít?**




