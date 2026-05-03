---
route: microservices_and_ddd
path: /ddd-a-microservices
title: DDD a microservices – Bounded Context jako service boundary
page_title: "DDD a microservices – Bounded Context jako service boundary | DDD Symfony"
meta_description: "Mapování DDD Bounded Context na microservice. Kdy BC = service, kdy modular monolith, jak se vyhnout distributed monolithu. Sam Newman, Chris Richardson, Symfony 8 a Messenger."
meta_keywords: "DDD, microservices, Bounded Context, modular monolith, distributed monolith, Symfony 8, Symfony Messenger, integration event, service boundary, Sam Newman, Chris Richardson, strangler fig, service mesh, saga"
og_type: article
published: "2026-04-29"
modified: "2026-04-29"
breadcrumb_name: DDD a microservices
schema_type: TechArticle
schema_headline: "DDD a microservices – Bounded Context jako service boundary"
chapter_number: "20"
category: Praxe
deck: "Slogan BC = microservice je polopravda. Bounded Context je logická hranice modelu; microservice je fyzická hranice deploymentu. Kapitola o tom, kdy mapování 1:1 dává smysl, kdy modular monolith poráží microservices a jak rozeznat distributed monolith včas."
reading_time: 30
difficulty: 4
github_examples: null
---

V [kapitole o základních konceptech](/zakladni-koncepty#bounded-contexts) jsme zavedli **Bounded Context** jako jasně ohraničenou oblast, ve které platí jeden konzistentní doménový model a jeden Ubiquitous Language. V [Context Mappingu](/context-mapping) jsme rozebrali, jak různé Bounded Contexts spolu komunikují (Customer-Supplier, Conformist, Anti-Corruption Layer, Open Host Service, Published Language). V kapitole o [ságách a Process Managerech](/sagy-a-process-managery) jsme ukázali, jak koordinovat doménový proces napříč více Bounded Contexts pomocí kompenzací místo distribuovaných transakcí.

Tato kapitola odpovídá na otázku, kterou si dříve nebo později položí každý tým: **jak se z těchto logických hranic stanou fyzické nasazovací jednotky?** Konkrétně: jak se Bounded Context mapuje na microservice – a kdy ne. Pokrývá tři často přehlížené pravdy. Mapování 1:1 (BC = service) je jen jedna ze tří možností. Pro většinu týmů je *modular monolith* rozumnější výchozí bod. Microservices špatně navržené jsou horší než monolit, kterému se snaží uniknout.

## 20.01 Mýtus „microservice = Bounded Context“ {#mytus}

V komunitě DDD a microservices koluje v různých variantách slogan: *„Each microservice should be one Bounded Context.“* Slogan má zdánlivou logiku a historické opodstatnění. DDD definuje hranici modelu, microservices definuje hranici nasazení; přirozeným zjednodušením je obojí ztotožnit. Praxe ale tento závěr nepotvrzuje. Slogan je **polopravda**, která vede k chybným architektonickým rozhodnutím častěji než ke správným.

Podstatné je rozlišit dvě úrovně, které slogan slévá do jedné. Bounded Context je **logická hranice modelu**: definuje, kde platí jeden konzistentní výklad pojmů, jeden Ubiquitous Language a jeden set invariantů. Microservice je **fyzická hranice deploymentu**: definuje, co se buildí jako jeden artefakt, nasazuje jako jeden proces, vlastní jednu databázi a má jeden tým, který za ni odpovídá. Tyto dvě úrovně se mohou – ale nemusí – překrývat.

Sam Newman v knize *Building Microservices, 2nd ed.* (2021) tuto distinkci zdůrazňuje opakovaně. V kapitole 2 píše, že Bounded Context představuje silný kandidát pro service boundary. Rozhodnutí, zda kontext skutečně dostane vlastní deployment unit, závisí na faktorech jako velikost týmu, rozdílné scaling potřeby, různý release cyklus a operační kapacita organizace. Chris Richardson v knize *Microservices Patterns* (2018) v kapitole 2 popisuje stejné rozhodnutí jako „decomposition by business capability“ a zdůrazňuje, že rozdělení musí mít doménový důvod, ne čistě technický.

:::callout{type="note"}
### Pravda místo sloganu {#mytus-pravda-heading}

Bounded Context a microservice nejsou totéž. Existují tři varianty mapování. **1:1** (jeden BC = jedna service) – cílový stav, když pro něj platí konkrétní podmínky. **1:N** (jeden BC rozdělený do více servis) – typicky špatně. **N:1** (více BC ve stejném deployable artefaktu) – modular monolith. Každá varianta má svůj kontext, ve kterém je správná.

Slogan „BC = microservice“ je užitečný jen jako *výchozí hypotéza*, kterou tým ověřuje organizačními a technickými fakty. Není to architektonický příkaz.
:::

Ještě jedno nedorozumění zaslouží upozornění: pojem „Bounded Context“ se v komunitě někdy používá volněji než ho definoval Eric Evans. Někdy se jím myslí pouhý modul, jindy celá produktová doména. Vaughn Vernon v knize *Implementing Domain-Driven Design* (2013) v kapitole 2 striktně připomíná, že Bounded Context je **jazyková hranice**. Uvnitř jednoho BC má každý termín jediný význam. Pokud o stejném pojmu (například „Customer“) mluví dva týmy odlišně, jsou to dva Bounded Contexts – bez ohledu na to, zda běží v jednom či dvou Symfony procesech.

:::diagram{fig="20.1-A" title="Tři scénáře mapování Bounded Context ↔ Service" src="images/diagrams/20_microservices/bc_to_service.svg"}
Tři scénáře: 1 BC = 1 service (správně oddělený microservice), 1 BC = N services (přehnané dělení, typicky distributed monolith) a N BC = 1 service (modular monolith). Volba mezi nimi není technická, ale organizační.
:::

Tabulka níže shrnuje, čím se Bounded Context a microservice liší a v jaké rovině se rozhoduje. Tento rozdíl si při čtení dalších sekcí pamatujte. Většina anti-vzorů v této kapitole vzniká z toho, že tým plete jednu úroveň s druhou.

| Aspekt | Bounded Context | Microservice |
|---|---|---|
| Hranice | Logická – model a jazyk | Fyzická – proces, deploy, DB |
| Definice z knihy | Evans 2003, Vernon 2013 | Newman 2021, Richardson 2018 |
| Vlastník | Tým doménových expertů + vývojářů | Stream-aligned team (Skelton & Pais 2019) |
| Mění se kvůli | Změně doménového modelu | Scaling, release cyklu, ops |
| Existuje i v monolitu | Ano, vždy – jako modul | Ne, monolit je jeden deployment |

## 20.02 Kdy 1 BC = 1 service (cílový stav) {#bc-jedna-service}

Mapování 1:1 mezi Bounded Contextem a microservice je v komunitě často prezentováno jako defaultní cíl a v určitých situacích dává smysl. Nepřijímejte ho jako automatické pravidlo, ale ověřte si, že platí konkrétní organizační a technické předpoklady. Sam Newman tyto předpoklady v knize *Building Microservices, 2nd ed.* shrnuje pod hlavičkou „information hiding“ a „autonomy“: service má smysl tehdy, když ji lze měnit, nasazovat a škálovat nezávisle na zbytku systému.

### Kdy rozdělit BC do vlastní service {#kdy-rozdelit-heading}

Konkrétní podmínky, které mluví pro vlastní deployment unit:

- **Vlastní stream-aligned tým** – kontext má dedikovaný tým, který má autonomii nad backlogem, release cyklem a operačními rozhodnutími. Bez toho je vlastní service jen administrativní zátěž navíc. Detail v [Team Topologies](/team-topologies) (Skelton & Pais 2019).
- **Vlastní data** – kontext drží svá data v oddělené databázi (nebo alespoň v oddělených tabulkách s vlastním schema ownerem). Ostatní kontexty na ně nesahají přímo, ale jen přes API nebo události. Sdílená databáze napříč servisy je určujícím znakem *distributed monolithu* – viz sekci 20.04.
- **Nezávislý release cyklus** – kontext lze deployovat bez současného deployu jiných kontextů. Pokud změna v service A vyžaduje současnou změnu v service B, lepšími hranicemi se to neřeší. Tým má jednu deployment unit a jen si ji rozdělil na dvě procesní role.
- **Rozdílné scaling potřeby** – kontext má řádově jiný objem zpracování (např. catalog s velkým read trafficem vs. ordering s nízkým, ale transakčně náročným) nebo jiné latency požadavky. Rozdělení umožní horizontálně škálovat jen ten, který to potřebuje.
- **Rozdílný stack nebo runtime** – kontext potřebuje jiné runtime parametry (jiná PHP verze, jiné dependencies, jiné memory limity) nebo dokonce jiný jazyk. Vzácné, ale legitimní.
- **Rozdílný compliance režim** – kontext zpracovává citlivá data (PCI DSS, GDPR speciální kategorie), která mají striktní oddělení od ostatního systému. Network isolation a samostatný audit trail jsou přirozenějším řešením, když kontext žije ve vlastní service.

### Příklad: e-shop se čtyřmi servisami {#priklad-eshop-heading}

Středně velká e-commerce platforma s 30 inženýry rozdělenými do čtyř stream-aligned týmů identifikovala během [Event Storming](/event-storming) workshopu čtyři Bounded Contexty:

- **Catalog** – produktový katalog, search, kategorie, atributy. Read-heavy, malé write operace, agresivní cache. Tým 8 lidí.
- **Ordering** – košík, objednávky, stav, refundy z doménového pohledu. Transakční, latency-sensitive, tvrdá konzistence. Tým 9 lidí.
- **Payment** – integrace platebních bran, autorizace, capture, recurring payments, refundy z technického pohledu. PCI DSS scope, audit trail. Tým 6 lidí.
- **Shipping** – integrace s dopravci, sledování, doručovací okna. Eventual konzistence s ordering, dlouhý write cyklus (hodiny i dny). Tým 7 lidí.

Každý z těchto kontextů má vlastní tým, vlastní DB schema, vlastní release cyklus a měřitelně jiné scaling potřeby. Rozhodnutí mít čtyři Symfony aplikace (catalog-svc, ordering-svc, payment-svc, shipping-svc) je v této organizační realitě obhajitelné. Komunikují asynchronně přes [Outbox](/outbox-pattern) a [ságu](/sagy-a-process-managery) typu „Place Order“.

Zopakujme podstatné slovo z předchozího odstavce: **obhajitelné**. Microservices nejsou jen „lepší architektura“. Jsou architektonickým rozhodnutím s kompromisy – vyšší operační složitost, potřeba distributed tracing, eventual consistency všude, kde dříve byla ACID transakce. Tato kapitola tyto kompromisy probírá v dalších sekcích.

:::callout{type="pattern"}
### Heuristika 1:1 – kdy ji uplatnit {#bc-1-1-heuristika-heading}

Pokud z předchozího seznamu zaškrtnete **všech šest** bodů (vlastní tým, vlastní data, nezávislý release, různé scaling, různý stack/compliance), je 1:1 mapování silně doporučitelné.

Pokud zaškrtnete **tři až pět**, zvažte, zda dvě sousední kontexty raději neudržet v jednom deployu jako moduly – split vás bude stát víc, než ušetří. Pokud zaškrtnete **méně než tři**, zůstaňte v [modular monolithu](#modular-monolith). Microservices nejsou cílem, ale nástrojem.
:::

## 20.03 Kdy zvolit modular monolith {#modular-monolith}

Modular monolith je architektonický styl, ve kterém jeden deployable artefakt (jedna Symfony aplikace, jedna databáze, jeden proces) interně obsahuje **více Bounded Contexts jako moduly** s vynucenými hranicemi. Z venku vypadá jako klasický monolit; uvnitř má disciplínu, kterou byste jinak vynucovali přes service boundary.

Proč o něm mluvit v kapitole o microservices? Pro většinu týmů, které začínají s DDD, je to rozumný výchozí bod. Martin Fowler v článku *MonolithFirst* (2015) argumentuje, že microservices předčasně rozdělují systém, jehož hranice ještě nejsou ustálené. Tím vznikají technické dluhy, které se těžce rozplétají. Sam Newman v *Building Microservices, 2nd ed.* (kap. 3) tento postoj přejímá a explicitně jako výchozí strategii doporučuje monolith-first nebo modular monolith-first.

### Kdy zvolit modular monolith {#kdy-modular-heading}

Konkrétní indikátory, podle kterých modular monolith poráží microservices:

- **Malá organizace** – pod ~30 lidí na celém produktu. Není dost stream-aligned týmů na to, aby každý microservice měl dedikovaného vlastníka. Rozdělení do servis pak vede k tomu, že jeden tým spravuje pět servis a strávil polovinu týdne přepínáním kontextu.
- **Nestabilní hranice** – produkt je v rané fázi a Bounded Contexty ještě procházejí iteracemi. Refaktor hranice uvnitř monolithu je triviální (přesun souborů a tříd); refaktor přes síťovou hranici dvou servis je migrace dat, koordinovaný release a Anti-Corruption Layer.
- **Podobné scaling potřeby všech kontextů** – pokud catalog, ordering i shipping mají podobný objem a profil, není co odděleně škálovat. Horizontální škálování celého monolithu je operačně levnější než škálování čtyř servis.
- **Nemáte operační platformu pro N servisů** – žádný Kubernetes, žádný service mesh, žádné centralizované logging a tracing. Bez nich budou microservices fungovat technicky, ale ladění incidentů bude noční můra. Více v [sekci o ops](#ops).
- **Operační kapacita < 30 % engineering kapacity** – Newman radí, že přechod na microservices má smysl jen tehdy, když organizace investuje výraznou část kapacity do platformy (CI/CD, observability, deployments, incident response). Pokud na to nemáte lidi, modular monolith vás chrání před zhoršením produktivity.

### Modular monolith v Symfony 8 {#modular-monolith-symfony-heading}

V Symfony se modular monolith přirozeně realizuje strukturou adresářů pod `src/`. Každý Bounded Context dostává vlastní namespace a vlastní podadresář – typicky se třemi vrstvami (Domain, Application, Infrastructure) kvůli souladu s [vertikálním řezem](/vertikalni-slice):

:::code{language="bash" filename="Struktura src/ – modular monolith"}
src/
├── Catalog/                      # Bounded Context: Catalog
│   ├── Domain/
│   │   ├── Model/
│   │   │   ├── Product.php
│   │   │   └── Category.php
│   │   ├── Event/
│   │   │   └── ProductPriceChanged.php
│   │   └── Repository/
│   ├── Application/
│   │   ├── Command/
│   │   ├── Query/
│   │   └── Handler/
│   └── Infrastructure/
│       ├── Persistence/
│       └── Http/
│
├── Ordering/                     # Bounded Context: Ordering
│   ├── Domain/
│   │   ├── Model/
│   │   │   └── Order.php
│   │   ├── Event/
│   │   │   └── OrderPlaced.php
│   │   └── Repository/
│   ├── Application/
│   │   ├── Command/
│   │   ├── IntegrationEvent/
│   │   │   └── ProductPriceChangedReceived.php
│   │   └── Handler/
│   └── Infrastructure/
│
├── Billing/                      # Bounded Context: Billing
│   ├── Domain/
│   ├── Application/
│   │   └── IntegrationEvent/
│   │       └── OrderPlacedReceived.php
│   └── Infrastructure/
│
└── SharedKernel/                 # Sdílené technické typy (NE doménové)
    ├── Domain/
    │   └── ValueObject/
    │       ├── Money.php
    │       └── Currency.php
    └── Application/
:::

Všimněte si dvou důležitých detailů. Za prvé: `SharedKernel` obsahuje pouze technické typy (Money, UUID, Result), **nikdy doménové entity**. Sdílení doménových entit napříč BC porušuje samotnou definici Bounded Contextu. Za druhé: každý BC má vlastní `Application/IntegrationEvent/`, kam mapuje příchozí události z jiných kontextů – stejný princip, který v sekci 20.08 použijeme i mezi separátními servisami.

### Vynucení hranic přes phparkitect {#phparkitect-heading}

Adresářová struktura sama o sobě nestačí. Vývojář pod tlakem deadlinu si do `App\Ordering\Domain` klidně přidá `use App\Billing\Infrastructure\StripeClient;` a hranice je porušená. Disciplínu **vynucujte automaticky** v CI. Pro PHP slouží [phparkitect](https://github.com/phparkitect/arkitect) – statický analyzátor pravidel architektury.

:::callout{type="pattern"}
### PHP: Pravidla pro modular monolith v phparkitect {#phparkitect-rules-heading}

:::code{language="php" filename="phparkitect.php" highlights="16,17,18,19,20,21,47,48,49,50,51"}
<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnAnyOfTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__ . '/src');

    // Pravidlo 1: Ordering nesmí znát Infrastructure jiných BC.
    // Komunikace probíhá přes Application interface nebo events.
    $orderingIsolation = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Ordering'))
        ->should(new NotDependsOnAnyOfTheseNamespaces([
            'App\Billing\Infrastructure',
            'App\Catalog\Infrastructure',
            'App\Shipping\Infrastructure',
        ]))
        ->because(
            'Ordering komunikuje s ostatními BC jen přes events nebo Application interface, '
            . 'ne přes Infrastructure. Sdílení Infrastructure = distributed monolith po rozdělení.'
        );

    // Pravidlo 2: Domain vrstva nesmí znát žádný jiný BC.
    // Ani jeho Application – Domain je nejvíc izolovaná.
    $domainIsolation = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Ordering\Domain'))
        ->should(new NotDependsOnAnyOfTheseNamespaces([
            'App\Billing',
            'App\Catalog',
            'App\Shipping',
        ]))
        ->because(
            'Doménová vrstva BC je čistá – nezná ostatní BC ani jejich Application vrstvu. '
            . 'Cross-BC integrace patří do Application/IntegrationEvent.'
        );

    // Pravidlo 3: Doménové eventy zůstávají uvnitř svého BC.
    // Subscriber v jiném BC má vlastní IntegrationEvent DTO.
    $eventsArePrivate = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Billing'))
        ->should(new NotDependsOnAnyOfTheseNamespaces([
            'App\Ordering\Domain\Event',
        ]))
        ->because(
            'Billing nesmí použít App\Ordering\Domain\Event\OrderPlaced přímo. '
            . 'Místo toho má App\Billing\Application\IntegrationEvent\OrderPlacedReceived. '
            . 'Bez tohoto pravidla po rozdělení monolithu vznikne sdílená library = distributed monolith.'
        );

    $config->add($classSet, $orderingIsolation, $domainIsolation, $eventsArePrivate);
};
:::
:::

Pravidlo se spouští v CI jako součást běžné kontroly kvality:

:::code{language="yaml" filename="composer.json + CI"}
# composer.json scripts
"scripts": {
    "phparkitect": "phparkitect check --config=phparkitect.php",
    "ci": [
        "@phparkitect",
        "@phpstan",
        "@phpunit"
    ]
}

# .github/workflows/ci.yml
- name: Architecture rules
  run: composer phparkitect
:::

S tímto pravidlem můžete bezpečně zůstat v monolithu měsíce a roky. Hranice mezi BC jsou vynucené stejně tvrdě, jako by byly za HTTP/AMQP – ale platíte za ně řádově méně operační složitosti.

### Kdy z modular monolithu odejít {#kdy-z-monolithu-heading}

Modular monolith není trvalý cíl, ale výchozí stav, který v určitém bodě některé BC opustí. Indikátory, že jeden konkrétní modul je připraven na vlastní service:

1. Modul má výrazně jiný scaling profil – typicky read-heavy modul (catalog, search) nebo modul s nepravidelnými špičkami (notifications, batch reporting).
2. Modul má vlastní stream-aligned tým, který chce vlastní release cyklus a má operační kapacitu se postarat o samostatný runtime.
3. Modul potřebuje compliance isolation (PCI DSS, GDPR speciální data).
4. Hranice modulu se posledních ~6 měsíců neměnila – model je stabilní, refaktor přes síťovou hranici nehrozí.

Postup migrace probíráme detailně v [sekci 20.09 (Strangler Fig)](#migrace).

## 20.04 Distributed Monolith – anti-pattern {#distributed-monolith}

Distributed monolith je systém, který má **vnější tvar microservices** (N samostatných servisů, N deploymentů, N repozitářů, N týmů), ale **vnitřní coupling monolithu** (sdílená databáze, synchronní volání všude, coordinated release, sdílená library s doménovými typy). Sam Newman ho označuje za nejhorší ze všech architektur – máte všechny náklady microservices a žádnou jejich výhodu.

Vzniká dvěma cestami, které jsou často nerozlišitelné. **První cesta**: tým rozdělil monolit do servis dříve, než identifikoval Bounded Contexty. Hranice mezi servisami jsou tedy náhodné (typicky podle technické vrstvy nebo podle CRUD entit), ne podle domény. Servisy musí mezi sebou komunikovat o všem a coupling je vrstevně rozprostřený. **Druhá cesta**: tým rozdělil správně podle BC, ale neověřil, že každá service má skutečnou autonomii – sdílela databázi „pro jednoduchost“, sdílela library s doménovými typy „aby se neopakoval kód“, sdílela deployment pipeline „aby release byl atomický“.

:::callout{type="anti"}
### 5 příznaků distributed monolithu {#distributed-monolith-priznaky-heading}

Pokud vám sedí dva a více těchto bodů, máte distributed monolith:

1. **Sdílená databáze napříč servisami.** Service A i service B čtou (nebo dokonce zapisují) do stejných tabulek. Změna schématu jednoho zlomí druhý. Toto je nejjasnější příznak – Newman ho označuje za *„the single greatest cause of distributed monolith“*.
2. **Synchronní HTTP/gRPC volání mezi servisami v každém request flow.** Vyřízení jednoho user requestu vyžaduje 5–10 vnořených volání. Latence je součet všech volání; availability je součin všech availabilities; failure jednoho znamená failure celého řetězce.
3. **Coupled deployment.** Změnu API service A nelze nasadit, dokud současně nedeployujete service B, která konzumuje to API. „Release je atomický“, „máme deployment train“ – to jsou eufemizmy pro coupled deploy. Sam Newman: pokud nelze service deployovat samostatně, není to microservice.
4. **End-to-end test vyžaduje všechny servisy.** Test jednoho user flow nelze spustit bez toho, abyste měli runtime všech N servis (lokálně přes docker-compose, v CI přes test environment). Žádná service není testovatelná v izolaci.
5. **Sdílená library s doménovými typy.** Existuje balíček `company/domain-shared`, který obsahuje třídy jako `OrderPlaced`, `Money`, `CustomerId` používané všemi servisami. Změna v balíčku vynucuje současný release všech servis. Coupling je tu silný stejně jako v monolithu – jen se schovává za package version.
:::

### Proč je horší než monolith {#proc-distributed-monolith-heading}

Pokud máte coupling jako monolith a operační režii jako microservices, dostáváte to nejhorší z obou světů. Konkrétně:

- **Latence.** Vnitřní volání monolithu je function call (~µs); volání mezi servisami je síťový round-trip (~ms) plus serializace, deserializace a validace. Při 10 vnořených voláních je rozdíl 4 řády.
- **Availability.** Pokud každá service má 99,9% uptime, řetězec deseti servis má 99,0 % – desetinásobně větší nedostupnost.
- **Debugging.** Trace jednoho requestu prochází N servisami. Bez distributed tracing je incident skoro nelovitelný. S ním je drahý.
- **Refactoring.** Přesunutí pole z jedné entity do jiné je v monolithu refactoring v IDE. Mezi servisami je to migrace dat, change API smluv, koordinovaný deploy a období dual-write.
- **Testovací prostředí.** Místo `composer install && vendor/bin/phpunit` potřebujete docker-compose s deseti kontejnery a 32 GB RAM.

Detailní rozbor obecných anti-vzorů, které k distributed monolithu vedou (microservices first, shared DB, sync everywhere), najdete v [kapitole 22 – Anti-vzory DDD](/anti-vzory).

### Hybridní topologie – mix monolitu a extraktů {#hybridni-topologie-heading}

Reálné systémy zřídka spadají do jedné z čistých kategorií „monolit" vs. „microservices".
V praxi se objevuje **hybridní topologie**: jeden modulární monolit jako *core*
plus 1–3 extrahované servisy pro kontexty, které mají jasný důvod existovat samostatně.

:::diagram{fig="20.4-A" title="Hybridní topologie - core monolit + 2 extrakty s důvody" src="images/diagrams/20_microservices/hybrid_topology.svg"}
:::

Typický scénář:

```text
┌──────────────────────────────────┐    ┌─────────────────┐
│ Modulární monolit (core)         │ ◄──┤ Reporting svc   │
│  ├── Catalog                     │    │ (read-heavy,    │
│  ├── Ordering                    │    │  separátní DB)  │
│  └── Customer                    │    └─────────────────┘
└──────────────────────────────────┘
        ▲                    │
        │                    ▼
┌─────────────────┐    ┌──────────────────┐
│ Public API      │    │ Payment svc      │
│ (rate limiting, │    │ (PCI scope,      │
│  versioning)    │    │  audit isolation)│
└─────────────────┘    └──────────────────┘
```

Hybrid je legitimní cíl – ne přechodný stav „dokud nedokončíme migraci na microservices".
Důvody, proč zůstat hybridní dlouhodobě:

- **Core domény jsou tightly coupled** mezi sebou (Catalog ↔ Ordering ↔ Customer)
  a refaktor se v monolitu dělá v IDE; rozdělení do tří servis přidává Anti-Corruption
  Layer pro každý cross-context dotaz.
- **Periferní kontexty mají jasný operační důvod** být samostatně (Payment kvůli
  compliance, Reporting kvůli load profile, Public API kvůli SLA).
- **Tým neunese N+1 servisů** – core monolith vlastní jeden tým, každý extrakt
  přidá operační dluh.

### De-microservicing – návrat k monolitu {#de-microservicing-heading}

Trend „microservices za každou cenu" z let 2014–2018 se po dekádě provozu obrátil.
Adrian Cockcroft (bývalý VP Cloud Architecture v Netflixu, kde microservices vznikly)
v rozhovorech od roku 2022 explicitně varuje před prematurálním splitem.
Případ **Amazon Prime Video** (2023, oficiální článek na Amazon engineering blog)
popsal, jak tým vrátil video monitoring stack z microservices do monolitu –
ušetřil **90 % infrastrukturních nákladů** a zlepšil scaling.

Symptomy, které mluví pro de-microservicing:

- **Latence stresses,** která neodpovídá cross-service síťové latenci. Často znamená,
  že interakce by měla být in-process function call, ne network hop.
- **Refaktor kontextu vyžaduje současné změny v 3+ servisách.** Hranice byla špatně
  zvolená; refaktor v monolitu je triviální.
- **Inženýrská kapacita > 50 % na operační platformu.** Tým udržuje Kubernetes,
  service mesh, distributed tracing místo aby pracoval na produktu.
- **Provozní náklady na infrastrukturu rostou disproporčně k objemu.** AWS Lambda
  + API Gateway + DynamoDB napříč 20 servisami stojí 10× co srovnatelný EC2
  monolit.
- **Incident MTTR > 60 minut.** Distributed tracing není dost na to, aby tým
  rychle identifikoval root cause v N servisách.

Postup de-microservicingu je opačný k extraction patternu z 20.09:

:::callout{type="pattern"}
### Postup návratu z microservices do monolitu {#de-microservicing-postup-heading}

1. **Audit BC hranic.** Které servisy reálně mají vlastní team/data/release/scaling?
   Které byly rozděleny předčasně?
2. **Strangler v opačném směru.** Místo extrakce z monolitu se konsoliduje *do*
   monolitu. Začínáte u nejvíce coupled servisy s nejnižším operačním přínosem.
3. **Replikace doménového kódu.** Servisa A se stane modulem `App\Catalog\` v monolithu.
   Eventy, které dříve šly přes broker, jdou interním EventDispatcherem.
4. **Migrace dat.** Databáze servis se buď konsolidují do schémat monolitu, nebo se
   nová modulární data získávají z bývalé service DB jako read-only zdroj během přechodu.
5. **Vyřazení servisy.** Po N týdnech double-running se původní servis odstaví,
   smazat lze až po čas pro forensic review (typicky 90 dní).

Klíčový princip: **de-microservicing není failure**, je to legitimní architektonická
volba reagující na změnu kontextu (tým se zmenšil, scaling profil se vyrovnal,
operační kapacita klesla). Honest naming pomáhá – nekomunikujte to jako „regression"
ale jako „consolidation".
:::

### Náklady microservices – realistický rozpočet {#naklady-heading}

Sam Newman v *Building Microservices, 2nd ed.* (kap. 16) uvádí nákladové kategorie,
které se v rozhodování o microservices často přehlíží. Pro středně velký projekt
(10–20 servis) jsou tyto nákladové položky **každoročně se opakující**:

| Kategorie | Rozsah ročně | Poznámka |
|---|---|---|
| Platform team | 2–4 FTE | Provoz Kubernetes, service mesh, observability stack |
| Cloud infrastruktura navíc | +30–80 % vs. monolit | Více DB, message broker, load balancery, NAT |
| Observability tools | $20k–$200k | Datadog/NewRelic licence rostou s počtem hostů a metrik |
| CI/CD navíc | +50 % build minutes | N pipelines místo jedné, integration testy přes docker-compose |
| Onboarding nových inženýrů | +1–2 týdny | Pochopit topologii, deployment, debugging across services |
| Incident response | +30 % MTTR | Distributed tracing zjednodušuje, ale 5 servis ≠ 1 service |

Kalkulace pro start-up s 30 inženýry: vlastní platform tým (3 FTE × 80 000 USD/rok
= 240k) + observability stack (60k) + cloud overhead (40k vs. monolit) ≈ **340k USD/rok**
operačního dluhu navíc proti modulárnímu monolithu. To je rozhodovací číslo, které
patří do diskuse, ne „microservices jsou prostě lepší".

## 20.05 Kontrakt mezi services – sync vs. async {#kontrakt}

Jakmile máte dvě servisy, musíte se rozhodnout, jak spolu komunikují. Existují dva základní vzory – **synchronní** (REST, gRPC, SOAP) a **asynchronní** (events přes message broker – RabbitMQ, Kafka, NATS, AWS SNS/SQS). Většina reálných systémů kombinuje obojí. Volba pro každý konkrétní interakční vzor není kosmetická – určuje výsledné coupling, latenci a availability.

### Synchronní volání – kdy {#sync-kdy-heading}

- **Query (read), kde caller potřebuje odpověď během request flow.** Frontend potřebuje detail produktu pro vykreslení stránky; `catalog-svc` ho vrátí přes REST. Bez odpovědi nemůže pokračovat.
- **Validace, která blokuje další krok.** Před uložením objednávky musí `ordering-svc` ověřit u `catalog-svc`, že produkt existuje a je dostupný. Volání musí být sync, jinak riskujete, že uložíte objednávku na neexistující produkt.
- **Latence-sensitive operace.** Real-time check fraud detection, autorizace platby, rate limit check.
- **Idempotentní lookup.** Neměnné nebo zřídka měnící se data, kde latence sítě je akceptovatelná a kde je možné použít cache.

### Asynchronní eventy – kdy {#async-kdy-heading}

- **State changes (write), kde caller nepotřebuje vědět, co dál.** Po uložení objednávky publishuje `ordering-svc` event `OrderPlaced`. `billing-svc`, `shipping-svc` a `notification-svc` ho zpracují, kdy mohou. Caller čeká jen na lokální commit.
- **Cross-BC reakce, kde jednotlivé BC nemají závislost na výsledku.** Saga zpracovává krok po kroku přes eventy + commands; každý krok je nezávislý.
- **Operace, která může bezpečně probíhat se zpožděním.** Generování faktury, odeslání e-mailu, aktualizace search indexu, generování sitemapy.
- **Multi-subscriber broadcast.** Jeden event konzumuje N nezávislých subscriberů; publisher o nich nemusí vědět.

### Pravidlo „async-first“ {#async-first-pravidlo-heading}

Chris Richardson v *Microservices Patterns* (kap. 3) formuluje doporučení: **preferujte asynchronní messaging**, sync použijte jen tam, kde je to objektivně nutné. Důvody:

- Asynchronní subscriber lze restartovat, retryovat, rozdělit do replik. Sync caller čeká a buď dostane odpověď, nebo timeout – bez zotavení.
- Asynchronní messaging má lepší časové oddělení: subscriber může být dočasně nedostupný a publisher to neví. Při sync volání je publisher přímo závislý na uptime callee.
- Asynchronní toky lépe škálují: fronta zpráv se hromadí a worker ji konzumuje vlastním tempem; sync flow se musí škálovat synchronně a end-to-end.
- Asynchronní tok přirozeněji zapadá do [Event Storming](/event-storming) modelu – doménové eventy jsou stejně jednotkou domény.

Praktická implementace asynchronní cross-service komunikace v Symfony probíhá přes Symfony Messenger (transport AMQP nebo Redis), v kombinaci s [Outbox patternem](/outbox-pattern) kvůli atomicitě zápisu eventu se zápisem doménového stavu. Detail v sekci [20.08 Symfony konkrétně](#symfony).

| Aspekt | Sync (REST/gRPC) | Async (eventy) |
|---|---|---|
| Coupling v čase | Tight – caller čeká | Loose – subscriber může být offline |
| Latence vnímaná uživatelem | Součet všech sync volání | Latence lokálního zápisu |
| Availability | Součin uptime všech callee | Jen lokální uptime + broker |
| Backpressure | Caller dostane HTTP 503 | Fronta se hromadí, worker dotahuje |
| Refactoring API | Coordinated release callera + callee | Subscriber má vlastní integration event DTO |
| Testovatelnost | Vyžaduje WireMock / Pact / mock | Stačí dispatch eventu do test handleru |

## 20.06 Distribuované transakce – Saga, ne 2PC {#distribuovane-transakce}

Jakmile doménový proces překročí hranici jednoho Bounded Contextu (a v microservices architektuře tedy hranici jedné service), musíte řešit otázku **konzistence napříč servisami**. ACID transakce, na kterou jste zvyklí v jedné databázi, v distribuovaném prostředí přestává platit. Klasickou odpovědí kdysi býval *Two-Phase Commit* (2PC, XA transactions). V microservices architektuře je 2PC **prakticky nepoužitelný**.

### Proč ne 2PC v microservices {#proc-ne-2pc-heading}

- **Žádný sdílený koordinátor.** 2PC vyžaduje globálního koordinátora, který má visibility do všech transakčních managerů (XA RM). V microservices každý servis má svůj DB a žádný globální transakční manažer neexistuje. HTTP a AMQP transportní protokoly nemají XA hooks.
- **Blocking.** Účastníci 2PC drží zámky během obou fází. Při latenci sítě v desítkách milisekund a více účastnících je to blokáda celé doménové transakce na stovky milisekund. Throughput katastrofálně klesá.
- **Single point of failure.** Pokud koordinátor 2PC spadne mezi prepare a commit fází, účastníci jsou v *in-doubt* stavu – nevědí, zda mají commitnout nebo rollbacknout. Zotavení vyžaduje manuální zásah operátora.
- **Nekompatibilita s heterogenními store.** 2PC funguje mezi RDBMS s XA podporou. Externí REST API platebních bran, NoSQL úložiště, message brokery – žádný z těchto „resource managerů“ XA nepodporuje.
- **Porušuje autonomii servisů.** 2PC vyžaduje, aby všichni účastníci sdíleli transakční protokol a aby koordinátor měl právo je všechny současně zablokovat. To je opak autonomního deploye a runtime, které jsou definujícím rysem microservices.

### Saga jako odpověď {#saga-heading}

Místo jedné velké distribuované transakce saga rozdělí proces na sérii **lokálních transakcí**. Každou commitne jedna service do své databáze. Mezi kroky se posílají eventy nebo commands přes message broker. Pokud některý krok selže, saga provede **kompenzační akce** pro všechny předchozí úspěšné kroky – sémantické vrácení, ne ACID rollback.

Saga existuje ve dvou variantách:

- **Choreografie** – každá service reaguje na eventy ostatních servisů. Žádný centrální orchestrátor; flow je implicitní v eventech. Vhodné pro jednoduché ságy s 2–3 kroky.
- **Orchestrace** – centrální Process Manager (saga aggregate) drží stav celého procesu a posílá commands jednotlivým servisám. Vhodné pro komplexní ságy s mnoha kroky, podmínkami, timeouty a retry logikou.

Detailní implementaci ság v Symfony 8 (kompenzace, idempotence, choreografie vs. orchestrace, timeouty, paralelní kroky) probírá samostatná [kapitola 15 – Ságy a Process Managery](/sagy-a-process-managery). Pro účely této kapitoly stačí rozumět, že saga je **v DDD kontextu doporučovaný mechanismus pro distribuované transakce v microservices** a že daní za to je eventual consistency a kompenzační logika.

:::callout{type="note"}
### Saga vs. 2PC – shrnutí {#saga-vs-2pc-heading}

2PC se snaží zachovat ACID model přes hranici sítě a v praxi to končí blokádou nebo in-doubt stavem. Saga ACID opouští – místo toho akceptuje, že systém je dočasně nekonzistentní a konzistence se obnoví přes sémantické kompenzace. Pro doménové experty je to často přirozenější model než 2PC. Doménový proces v reálném světě (objednávka, platba, expedice) vždy běží jako sekvence kroků s explicitní undo strategií, ne jako jeden atomický commit.
:::

## 20.07 Service mesh, observability, ops {#ops}

Microservices nejsou primárně programátorský problém – jsou **operační problém**. Tým, který přejde z monolithu na deset servis, najednou musí řešit věci, které dříve obstarával operační systém a Symfony framework: routing mezi procesy, retry, circuit breaking, mTLS, distribuovaný debug, service discovery, centralizované logy, rate limiting. Každá z těchto věcí má svůj nástroj a svou cenu. Dohromady tvoří stack, který někdo musí provozovat.

### Service mesh {#service-mesh-heading}

Service mesh (Istio, Linkerd, Consul Connect, AWS App Mesh) je infrastrukturní vrstva, která řeší cross-cutting concerns mezi servisami: **mTLS** (vzájemná autentizace přes TLS bez nutnosti manuálního managementu certifikátů), **retry a circuit breaking** (automatické opakování a otevření okruhu při opakovaných failure), **rate limiting**, **traffic shaping** (canary deploy, A/B testing na úrovni síťové vrstvy), **observability** (latence, error rate, throughput per service edge).

Implementačně bývá service mesh sidecar: každý pod má vedle aplikačního kontejneru sidecar proxy (Envoy, linkerd-proxy), která zachytává všechen network traffic a aplikuje politiku mesh. Konfigurace se ovládá přes control plane (istiod, linkerd-control).

**Kdy service mesh:** 10+ servis, multi-team organizace, požadavek na mTLS bez ruční práce, potřeba pokročilé traffic management (canary, blue/green s percentage routing). **Kdy ne:** 3–5 servis, malý tým, žádný Kubernetes – provozní režie převáží přínosy.

### Observability – three pillars {#observability-heading}

V monolithu stačila kombinace strukturovaných logů a metrik. V microservices přibývá třetí pilíř – distributed tracing – a všechny tři se musí řešit centralizovaně:

- **Logs** – centralizované log aggregation (ELK / Loki / CloudWatch). Každý log line musí mít `trace_id` a `service_name`, jinak není možné poskládat časovou řadu událostí napříč servisami.
- **Metrics** – Prometheus + Grafana, nebo cloudový ekvivalent (Datadog, NewRelic). Standardní metriky (RED – rate, errors, duration) per service a per endpoint.
- **Traces** – OpenTelemetry + Jaeger / Tempo / Honeycomb. Jeden user request se trasuje napříč všemi servisami, každý hop má span. Bez tohoto je ladění nemožné – pět servis a dvacet logů v incidentu nedávají dohromady jednu časovou řadu.

### Service discovery a deployment {#service-discovery-heading}

- **Service registry / discovery** – Consul, Kubernetes service / DNS, AWS Cloud Map. Servisy nemohou spoléhat na statické IP adresy – potřebují resolver, který v runtime vrátí adresu instance.
- **Container orchestration** – Kubernetes je de facto standard. Bez něj (nebo bez ekvivalentu jako Nomad, ECS) nelze realisticky provozovat víc než pár servis. Kubernetes sám je netriviální – jeho provoz je vlastní specializace.
- **CI/CD per service** – každá service má vlastní pipeline, vlastní release schedule, vlastní rollback. Sdílená pipeline = coordinated release = distributed monolith.
- **Schema registry** – pro events přes broker (zejména Kafka) potřebujete schema registry (Confluent, AWS Glue), který verzuje schéma eventů a kontroluje kompatibilitu.

:::callout{type="warn"}
### Operační pravidlo {#ops-pravidlo-heading}

Pokud nemáte **všechno** z tohoto seznamu (orchestrátor, centralizované logging, distributed tracing, service discovery, CI/CD per service), je modular monolith rozumnější volba. Microservices bez observability nejsou microservices – jsou to nesouvislé Symfony aplikace, které se vzájemně nekoordinovaně volají.

Přechod z modular monolithu na microservices je primárně **investice do operační platformy**, ne do architektonického refaktoru. Proto má smysl, až když tým má operační kapacitu na to budovat platformu nebo má rozpočet na managed služby (EKS / GKE / AWS Fargate / Datadog).
:::

## 20.08 Symfony konkrétně – kdy a jak {#symfony}

Symfony 8 dokáže obsloužit obě architektury – modular monolith i microservices – bez zásadní změny kódu vrstvy domény. Rozdíl je v **routing konfiguraci Messenger**: ve stejném monolithu všechny eventy a commands směřujete na `sync` transport (function call) nebo na lokální `async` (in-memory worker); přes hranici dvou servis je směrujete na `amqp` transport, který fyzicky publishne zprávu do RabbitMQ.

### Modular monolith v Symfony {#symfony-monolith-heading}

V monolithu jsou všechna BC ve stejném Symfony procesu. Cross-BC integrace probíhá přes Domain Events + Symfony Event Dispatcher (jeden DI kontejner, přímý handler) nebo přes Symfony Messenger se `sync` transportem (in-process command bus pattern). Doménový event se v jednom BC dispatchne, handler v druhém BC ho přijme, namapuje na **vlastní integration event DTO** a spustí lokální command. Hranice je čistě v kódu, vynucená phparkitect.

Nejjednodušší realizace cross-BC integrace v monolithu: Application Layer publikujícího BC vystaví rozhraní (port), které implementuje konzument. Žádné HTTP, žádný broker. Pokud později rozdělíte BC do servisů, port zůstane stejný – jen se za ním objeví HTTP klient nebo Messenger.

### Microservice v Symfony {#symfony-microservice-heading}

V microservice architektuře je každý BC vlastní Symfony aplikace s vlastním `composer.json`, `config/`, `src/`, vlastní DB. Cross-service integrace probíhá **výhradně asynchronně** přes Symfony Messenger s AMQP transportem (RabbitMQ) v kombinaci s [Outbox patternem](/outbox-pattern) v publisheru a *Inbox idempotency* v subscriberovi.

Pravidlo: **publisher a subscriber *nesdílejí* PHP třídu eventu.** Publisher má svůj doménový event v `App\Ordering\Domain\Event\OrderPlaced` uvnitř ordering-svc kódu. Subscriber v billing-svc má vlastní `App\Billing\Application\IntegrationEvent\OrderPlacedReceived` – samostatnou třídu, která se naplní z deserializovaného AMQP payloadu. Důvody jsou v sekci 20.04 (sdílená library s doménovými typy = distributed monolith).

:::callout{type="pattern"}
### YAML: Messenger config – publisher (ordering-svc) {#messenger-publisher-heading}

:::code{language="yaml" filename="ordering-svc/config/packages/messenger.yaml" highlights="37,38,39,40"}
# config/packages/messenger.yaml v ordering-svc
framework:
    messenger:
        # default bus pro lokální commands uvnitř BC
        default_bus: command.bus

        transports:
            # lokální async transport pro in-service commands
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    multiplier: 2

            # outbox transport pro cross-service eventy
            # používá Doctrine pro atomicitu zápisu eventu se zápisem domény
            events_out:
                dsn: 'doctrine://default?queue_name=outbox_events'
                serializer: 'messenger.transport.symfony_serializer'
                options:
                    queue_name: 'outbox_events'

            # AMQP transport, kam outbox relay přepisuje zprávy
            amqp_out:
                dsn: '%env(AMQP_DSN)%'
                options:
                    exchange:
                        name: 'domain_events'
                        type: 'topic'
                        default_publish_routing_key: 'ordering.placed'

        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction

            event.bus:
                default_middleware:
                    allow_no_handlers: true

        routing:
            # CRITICAL: publishneme náš VLASTNÍ doménový event,
            # ne sdílenou třídu. AMQP payload je serializovaný DTO.
            'App\Ordering\Domain\Event\OrderPlaced': events_out
:::
:::

Outbox transport zapisuje event do tabulky v stejné DB transakci jako doménový commit (Doctrine Outbox). Externí relay (worker přes `messenger:consume`) potom polluje outbox table a publishuje payload do AMQP exchange. Detail v [kapitole o Outbox patternu](/outbox-pattern).

Subscriber service má zrcadlovou konfiguraci – AMQP transport pro příchozí zprávy, vlastní mapping na integration event DTO, lokální command bus pro spuštění reakce:

:::callout{type="pattern"}
### YAML: Messenger config – subscriber (billing-svc) {#messenger-subscriber-heading}

:::code{language="yaml" filename="billing-svc/config/packages/messenger.yaml" highlights="38,39,40,41"}
# config/packages/messenger.yaml v billing-svc
framework:
    messenger:
        default_bus: command.bus

        transports:
            # AMQP transport pro INPUT eventy
            # konzumuje zprávy z exchange 'domain_events'
            events_in:
                dsn: '%env(AMQP_DSN)%'
                options:
                    exchange:
                        name: 'domain_events'
                        type: 'topic'
                    queues:
                        billing_ordering_events:
                            binding_keys:
                                - 'ordering.placed'
                                - 'ordering.cancelled'
                serializer: 'App\Billing\Infrastructure\Messaging\IntegrationEventSerializer'
                retry_strategy:
                    max_retries: 5
                    multiplier: 2

            # lokální async pro vnitřní commands
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'

        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction

        routing:
            # CRITICAL: routujeme NA NAŠI vlastní třídu IntegrationEvent,
            # ne na App\Ordering\Domain\Event\OrderPlaced
            # Vlastní DTO = vlastní lifecycle, vlastní validace, vlastní version compat.
            'App\Billing\Application\IntegrationEvent\OrderPlacedReceived': events_in
            'App\Billing\Application\IntegrationEvent\OrderCancelledReceived': events_in
:::
:::

`IntegrationEventSerializer` je customní serializer, který namapuje deserializovaný AMQP payload (typicky JSON s `event_type` diskriminátorem) na konkrétní třídu integration eventu. Zde je místo, kde se subscriber rozhoduje, jak interpretovat payload – ne podle PHP typu, ale podle `event_type` stringu v hlavičce. Tím je publisher a subscriber *plně oddělený na úrovni kódu*.

:::callout{type="pattern"}
### PHP: Integration event DTO v subscriberovi {#integration-event-class-heading}

:::code{language="php" filename="billing-svc/src/Application/IntegrationEvent/OrderPlacedReceived.php"}
<?php

declare(strict_types=1);

namespace App\Billing\Application\IntegrationEvent;

/**
 * Subscriber-side integration event.
 *
 * NEZÁVISLÁ třída. Není to App\Ordering\Domain\Event\OrderPlaced.
 * Když publisher přidá pole do svého doménového eventu, NÁŠ
 * IntegrationEvent se nezmění, dokud nepřepíšeme deserializer.
 *
 * Tím je oddělený lifecycle obou servisů. Publisher může deployovat
 * novou verzi domény bez current release subscribera.
 */
final readonly class OrderPlacedReceived
{
    public function __construct(
        public string $eventId,
        public string $occurredAt,
        public string $orderId,
        public string $customerId,
        public int $totalAmountCents,
        public string $currency,
    ) {}
}
:::
:::

Custom serializer dělá překlad mezi binárním AMQP payloadem a konkrétní integration event třídou podle `event_type` hlavičky. Toto je jediné místo v subscriberu, kde se „dotýkáte“ formátu publishera. Změny v doménovém eventu publishera vás zasáhnou jen zde – zbytek kódu pracuje s vaší vlastní třídou.

:::code{language="php" filename="billing-svc/src/Infrastructure/Messaging/IntegrationEventSerializer.php" highlights="22,23,24,25"}
<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Messaging;

use App\Billing\Application\IntegrationEvent\OrderCancelledReceived;
use App\Billing\Application\IntegrationEvent\OrderPlacedReceived;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final readonly class IntegrationEventSerializer implements SerializerInterface
{
    /**
     * Mapping event_type hlavičky -> integration event třída.
     * Když publisher přidá nový event_type, doplníme tu řádek;
     * dokud nedoplníme, zpráva spadne do dead-letter exchange.
     */
    private const TYPE_MAP = [
        'ordering.placed' => OrderPlacedReceived::class,
        'ordering.cancelled' => OrderCancelledReceived::class,
    ];

    public function decode(array $encodedEnvelope): Envelope
    {
        $headers = $encodedEnvelope['headers'] ?? [];
        $eventType = $headers['event_type'] ?? throw new \RuntimeException('Missing event_type header');

        $targetClass = self::TYPE_MAP[$eventType] ?? throw new \RuntimeException(
            sprintf('Unknown event_type: %s', $eventType)
        );

        $payload = json_decode($encodedEnvelope['body'], true, flags: JSON_THROW_ON_ERROR);

        // Mapping payloadu z publishera (App\Ordering\Domain\Event\OrderPlaced)
        // na náš subscriber-side DTO. Defenzivní – žádná pole z payloadu,
        // která bychom nepoužívali.
        $message = new $targetClass(
            eventId: $payload['eventId'],
            occurredAt: $payload['occurredAt'],
            orderId: $payload['orderId'],
            customerId: $payload['customerId'],
            totalAmountCents: $payload['totalAmountCents'],
            currency: $payload['currency'] ?? 'EUR',
        );

        return new Envelope($message);
    }

    public function encode(Envelope $envelope): array
    {
        // Subscriber neencodes – to dělá publisher na své straně.
        throw new \LogicException('IntegrationEventSerializer is decode-only.');
    }
}
:::

### Handler integration eventu {#symfony-handler-heading}

Jakmile máte deserializaci, handler je už standardní Messenger handler. Konvertuje příchozí IntegrationEvent na lokální command do vlastního BC:

:::code{language="php" filename="billing-svc/src/Application/Handler/OrderPlacedReceivedHandler.php"}
<?php

declare(strict_types=1);

namespace App\Billing\Application\Handler;

use App\Billing\Application\Command\CreateInvoiceForOrder;
use App\Billing\Application\IntegrationEvent\OrderPlacedReceived;
use App\Billing\Infrastructure\Idempotency\InboxRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class OrderPlacedReceivedHandler
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private InboxRepository $inbox,
    ) {}

    public function __invoke(OrderPlacedReceived $event): void
    {
        // Idempotence – pokud už jsme tento eventId zpracovali, no-op.
        // Detail v outbox_pattern#inbox-idempotency
        if ($this->inbox->wasProcessed($event->eventId)) {
            return;
        }

        $this->commandBus->dispatch(new CreateInvoiceForOrder(
            orderId: $event->orderId,
            customerId: $event->customerId,
            amountCents: $event->totalAmountCents,
            currency: $event->currency,
        ));

        $this->inbox->markProcessed($event->eventId);
    }
}
:::

Tímto vzorem dosáhneme čtyř důležitých vlastností:

- **Žádný shared code mezi servisami** – billing-svc nemá ve svém `composer.json` žádný balíček, který by definoval třídy ordering-svc.
- **Verzování payloadu** – publisher přidá pole, subscriber pole zatím nezná, no-op. Žádný coordinated release.
- **Idempotence** – duplicitní doručení (failover broker, restart workera) se neprojeví. *Inbox* tabulka v billing-svc drží zpracované `eventId`.
- **Testovatelnost** – handler se testuje s `new OrderPlacedReceived(...)`, bez síťového stacku.

## 20.09 Postupná migrace monolit → microservices {#migrace}

Tato sekce je pro týmy, které dnes mají monolit a uvažují, kam dál. Většinu reálných systémů nepostavíte jako microservices na zelené louce. Postavíte je jako monolit, ten doroste do bolesti, a pak se zeptáte, kterou část máte rozdělit. Mottem sekce je heslo Sama Newmana z *Building Microservices, 2nd ed.*: **„don't do a big-bang rewrite“**. Velká přepisovací migrace v 9 z 10 případů selže.

### Strangler Fig pattern {#strangler-fig-heading}

Vzor pojmenoval Martin Fowler v roce 2004 – *Strangler Fig Application*. Inspirace je z přírody: stromový fíkus se postupně omotá kolem hostitelského stromu, vyšle vlastní kořeny a postupně původní strom nahradí. Aplikováno na software:

1. Postavíte fasádu (proxy, routing layer, edge gateway) před monolit, která zatím všechno přesměruje do monolithu.
2. Vyberete jeden Bounded Context, který odejde jako první. Postavíte vedle monolithu nový microservice s touto funkcionalitou.
3. Fasáda začne pro daný BC routovat na nový microservice místo monolithu. Funkcionalita je dvakrát – v monolithu (vypnuta) i v servise (aktivní).
4. Po stabilizaci se dead code v monolithu smaže.
5. Iterujete dalším BC.

### Tři fáze migrace v praxi {#3-faze-heading}

Doporučená postupná cesta pro Symfony tým, který dnes má monolit bez explicitních hranic:

#### Fáze 1: Modular monolith (3–12 měsíců) {#faze-1-heading}

**Cíl:** identifikovat Bounded Contexty a vynutit jejich hranice *uvnitř* jednoho deployu. Nemigruje se nikam – refaktoruje se struktura.

- Provést [Event Storming](/event-storming) nebo Domain Storytelling s doménovými experty. Identifikovat BC.
- Reorganizovat `src/` do `src/<BC>/` struktury. Každý BC má vlastní Domain / Application / Infrastructure.
- Zavést phparkitect pravidla a v CI je vynucovat. Bez tohoto kroku jsou hranice fiktivní.
- Cross-BC integraci převést na Domain Events + Symfony Messenger (sync transport zatím).
- Identifikovat schema ownership – která tabulka patří kterému BC. Pokud jedna tabulka patří dvěma BC, máte tam buď Shared Kernel (řídký), nebo nesprávné hranice.

#### Fáze 2: Strangler Fig – první extrakce (1–3 měsíce na první service) {#faze-2-heading}

**Cíl:** vytáhnout první BC do samostatné service. Vyberte ten, který má největší důvod (typicky read-heavy modul s odlišným scaling profilem nebo modul s compliance isolation).

- Postavte fasádu (Symfony API gateway, nginx routing, AWS API Gateway) před monolit.
- Postavte nový Symfony projekt jako samostatnou service. Skopírujte (nikdy ne `git mv`) kód cílového BC z monolithu.
- Migrace dat: postupně replikovat tabulky cílového BC do nové DB. Období dual-write – monolit i nová service obě píší. Postupně cutover read traffic.
- Cross-BC eventy nahradit AMQP transportem v Messenger. Subscriber side má vlastní integration event DTO (sekce 20.08).
- Po stabilizaci smažte zbytky cílového BC z monolithu.

#### Fáze 3: Iterace nebo zastavení {#faze-3-heading}

**Cíl:** rozhodnout, zda pokračovat dalším BC, nebo zastavit a žít s hybridní architekturou (monolith + 1–2 servisy). Hybridní stav je **legitimní cílový stav**, ne dočasná fáze. Mnoho úspěšných systémů nikdy nedojede do plně microservices architektury – protože nemají důvod.

- Změřit, zda první extrakce splnila očekávání (lepší scaling, rychlejší release, lepší ownership). Pokud ne, zastavit a analyzovat proč.
- Pokračovat dalším BC, který má jasné odůvodnění.
- Investovat průběžně do operační platformy – bez ní každá další extrakce zhoršuje produktivitu.

:::callout{type="anti"}
### Nikdy: big-bang rewrite {#migrace-warning-heading}

Pravidelně se vracející selhání: tým se rozhodne postavit nové microservices na zelené louce, starý monolith zatím udržovat, a po 18 měsících přepnout. Co se stane:

- Po 6 měsících je nová architektura na 30 % funkcionality monolithu, ale monolith mezitím získal novou funkcionalitu, takže rozdíl narůstá.
- Nikdy není dobrý čas na cutover – produkční nápor, regulatorní změna, audity.
- Tým je rozdělený na „starý“ a „nový“; znalosti chybí na obou stranách.
- Po 18 měsících se projekt zastaví a obě verze zůstávají v produkci. Distributed monolith v nejhorší podobě.

Strangler Fig je drahý, ale zvládnutelný – každá fáze přináší měřitelnou hodnotu. Big-bang nepřináší nic, dokud nedojede až úplně. A většinou nedojede.
:::

Detailnější rozbor migrace z CRUD na DDD (která je úzce propojená s migrací na microservices) najdete v [kapitole 19 – Migrace z CRUD na DDD](/migrace-z-crud).

## 20.10 Anti-vzory v microservices a DDD {#antivzory}

Pět nejčastějších anti-vzorů, na které tým narazí při kombinaci DDD a microservices. Každý má konkrétní symptom a konkrétní opravu.

### 1. Microservices first (před identifikací BC) {#antivzor-1-heading}

**Symptom:** tým rozdělil monolit do servis dříve, než provedl Event Storming nebo Domain Storytelling. Hranice servis odpovídají technickým vrstvám (auth-svc, user-svc, db-svc) nebo CRUD entitám (order-svc, customer-svc, product-svc), ne doménovým kontextům.

**Důsledek:** doménový proces musí pro vyřízení projít napříč pěti až deseti servisami. Synchronní coupling všude. Je to distributed monolith z definice.

**Oprava:** zastavit, identifikovat skutečné BC přes Event Storming, mapovat aktuální servisy na cílové BC. Často zjistíte, že 3 stávající servisy patří do jednoho BC – sloučit je do modular monolithu, pak teprve řešit, zda BC má dostat vlastní service.

### 2. Sdílená databáze napříč servisami {#antivzor-2-heading}

**Symptom:** service A i service B čtou (nebo dokonce zapisují) do stejných tabulek. „Jednoduchá integrace“, „atomicita“, „není čas dělat to správně“.

**Důsledek:** jakákoli změna schématu zlomí všechny servisy, které tabulku konzumují. Žádná service nemá ownership nad daty. Refactoring databáze je migrační utrpení.

**Oprava:** data dělit podle BC. Cross-BC čtení nahradit API call (sync) nebo replikací přes eventy (async, eventually consistent). Žádný cross-BC join na DB úrovni.

### 3. Synchronní orchestrace všeho přes REST {#antivzor-3-heading}

**Symptom:** každá doménová operace je řetězec sync HTTP volání. Vyřízení objednávky: ordering volá payment, payment volá fraud-detection, fraud-detection volá ai-scoring, ai-scoring volá customer, ... Jeden user request = 12 vnořených HTTP volání.

**Důsledek:** kumulativní latence v sekundách, availability v součinu, retry storm při výpadcích.

**Oprava:** aplikovat *async-first* pravidlo (sekce 20.05). State changes přes eventy, validační lookups přes sync s cache, žádné synchronní side-effecty (sync save) přes hranici service. Pro koordinaci procesů použít [ságu](/sagy-a-process-managery).

### 4. Jeden deployment artefakt pro N servisů {#antivzor-4-heading}

**Symptom:** CI/CD pipeline buildí všechny servisy společně. Release schedule je centralizovaný („máme deployment train“, „release window v úterý“). Změnu v jedné servise nelze deploynout bez ostatních.

**Důsledek:** všechny servisy musí být kompatibilní v každém okamžiku. Žádná feature toggleability, žádný gradual rollout, žádný rychlý rollback. Coupled deploy je definující znak distributed monolithu.

**Oprava:** každá service má vlastní pipeline, vlastní release cyklus, vlastní rollback. Cross-service kompatibilita se řeší schema versioning a integration event verzováním (subscriber přijímá starší i novější verzi).

### 5. Nano-services {#antivzor-5-heading}

**Symptom:** service o 50 řádcích kódu, vlastní deploy, vlastní DB. „Single responsibility principle“ aplikované na deployment unit. Sto servis pro produkt s 30 inženýry.

**Důsledek:** operační režie 100x. Každá service potřebuje monitoring, alerty, CI/CD, runtime, knowledge base, pager rotation. Tým 30 lidí má na servis 0,3 inženýra. Nikdo nemá ownership hluboce, všichni „udržují“.

**Oprava:** agregovat blízce příbuzné servisy do jedné – typicky sloučit do BC, do kterého patří. „Microservice“ není „malá service“ – je to *samostatně nasazovatelná jednotka*. Velikost je vedlejší. Sam Newman v *Building Microservices, 2nd ed.* kapitole 4 explicitně doporučuje, aby velikost service vznikala z domény, ne z technické gymnastiky.

Obecnější rozbor anti-vzorů v DDD (nejen microservices) najdete v [kapitole 22 – Anti-vzory](/anti-vzory).

## 20.11 Shrnutí {#summary}

Vztah mezi Bounded Contextem a microservice nelze redukovat na jednu rovnici. Bounded Context je **logická hranice modelu**, microservice je **fyzická hranice deploymentu**; mapování mezi nimi je 1:1, 1:N nebo N:1, a každá varianta má svůj kontext, ve kterém je správná. Slogan „BC = microservice“ je užitečný jako výchozí hypotéza, ne jako architektonický příkaz.

Hlavní doporučení této kapitoly:

- **Modular monolith jako default** – pro většinu týmů (≤30 lidí) je modular monolith s vynucenými hranicemi přes phparkitect rozumnější výchozí stav než microservices na zelené louce. Hranice mezi BC tam jsou vynucené stejně tvrdě, ale platíte za ně řádově méně operační složitosti.
- **1 BC = 1 service jen tehdy, když má smysl** – vlastní stream-aligned tým, vlastní data, nezávislý release cyklus, různé scaling potřeby, případně compliance isolation. Pokud zaškrtnete tři a méně z těchto bodů, zůstaňte v monolithu.
- **Distributed monolith je horší než monolith** – sdílená DB, synchronní volání všude, coupled deploy, sdílená library s doménovými typy. Pět příznaků, dva a víc znamená, že máte distributed monolith. Nejdražší architektonická chyba v microservices architektuře.
- **Sync vs. async – async-first** – sync jen pro queries v request flow a pro blokující validace; všechno ostatní eventy přes broker. Tight temporal coupling je největší ztráta hodnoty microservices.
- **Distribuované transakce – saga, ne 2PC** – 2PC nepoužitelné v microservices stacku. Saga (choreografie pro jednoduché, orchestrace pro komplexní) plus kompenzace. Detail v [kapitole 15](/sagy-a-process-managery).
- **Symfony Messenger umí obojí** – sync transport pro modular monolith, AMQP transport s Outbox patternem pro cross-service eventy. Publisher a subscriber *nesdílejí* PHP třídu eventu; subscriber má vlastní integration event DTO. Bez tohoto pravidla po rozdělení monolithu vznikne sdílená library = distributed monolith.
- **Migrace přes Strangler Fig, ne big-bang** – postupně, jeden BC v čase, s fasádou a obdobím dual-write. Big-bang rewrite v 9 z 10 případů selže.
- **Microservices jsou primárně operační problém** – bez orchestrátoru, distributed tracingu, service discovery a CI/CD per service je modular monolith rozumnější.

Stručně: nezačínejte microservices. Začněte modular monolithem s explicitními BC a vynucenými hranicemi. Microservice je optimalizace, kterou si zasloužíte, až když ji potřebujete.

## 20.12 Další četba {#further-reading}

- [Sam Newman – *Building Microservices, 2nd ed.* (O'Reilly, 2021)](https://samnewman.io/books/building_microservices_2nd_edition/). Kanonická kniha o microservices. Kapitoly 1–4 pro hranice servis, kapitola 3 pro monolith-first strategii, kapitoly 5–7 pro integraci, kapitola 14 pro migraci.
- [Chris Richardson – *Microservices Patterns* (Manning, 2018)](https://microservices.io/book). Praktická kniha plná konkrétních patternů. Kapitola 2 (decomposition by business capability), kapitola 3 (interprocess communication), kapitola 4 (sagas), kapitola 13 (refactoring monolithu).
- [Vaughn Vernon – *Implementing Domain-Driven Design* (Addison-Wesley, 2013)](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577). Kapitola 2 pro Bounded Context jako jazykovou hranici, kapitola 3 pro Context Maps, kapitola 8 pro Domain Events napříč BC.
- [Martin Fowler – *MonolithFirst* (2015)](https://martinfowler.com/bliki/MonolithFirst.html). Krátký esej, který formuloval doporučení nezačínat na zelené louce s microservices.
- [Martin Fowler – *Strangler Fig Application* (2004)](https://martinfowler.com/bliki/StranglerFigApplication.html). Originální popis migrační strategie použitelný pro každý legacy systém.
- [Matthew Skelton & Manuel Pais – *Team Topologies* (IT Revolution, 2019)](https://www.amazon.com/Team-Topologies-Organizing-Business-Technology/dp/1942788819). Stream-aligned teams, enabling teams, complicated subsystem teams, platform teams. Klíč k tomu, aby microservices měly smysl organizačně.
- [Martin Fowler – *Microservice Trade-Offs* (2015)](https://martinfowler.com/articles/microservice-trade-offs.html). Co získáte a co ztrácíte.

:::faq{}
- question: Kolik je správná velikost jednoho microservice?
  answer: 'Velikost není primární kritérium – autonomní deployovatelnost je. Microservice je správně velký tehdy, když ho jeden stream-aligned tým dokáže měnit, nasazovat a provozovat samostatně. To může být 500 řádků kódu nebo 50 000. Sam Newman v <em>Building Microservices, 2nd ed.</em> doporučuje, aby velikost vznikala z domény (jeden Bounded Context nebo logická část), ne z technického ideálu „malé service“. Detail v <a href="#bc-jedna-service">sekci 20.02</a> a v anti-vzoru <a href="#antivzor-5-heading">nano-services</a>.'
- question: Můžu mít 2 Bounded Contexty v jedné microservice?
  answer: 'Ano, a často je to správné rozhodnutí – to je definice <strong>modular monolithu</strong> nebo malého „mikro-monolithu“. Pokud dva BC sdílejí stream-aligned tým a podobné scaling potřeby, jejich provozování ve dvou samostatných servisách je operační overhead bez benefitu. Hlavní podmínka: hranice mezi BC <em>uvnitř</em> servise musí být vynucená kódem (typicky phparkitect pravidly). Pokud se obejdou, máte unstructured monolith, ne modular monolith. Detail v <a href="#modular-monolith">sekci 20.03</a>.'
- question: Kdy přejít z monolithu na microservices?
  answer: 'Když máte konkrétní bolest, kterou microservices skutečně řeší – typicky odlišné scaling potřeby jednoho modulu, různé compliance režimy nebo organizační oddělení (různé stream-aligned týmy s různými release cykly). Bez konkrétní bolesti je přechod čistá ztráta – získáte operační složitost, žádnou hodnotu navíc. Postup vždy přes Strangler Fig (postupná extrakce 1 BC v čase), nikdy big-bang rewrite. Detail v <a href="#migrace">sekci 20.09</a>.'
- question: Co je BFF (Backend For Frontend) a kam patří v DDD?
  answer: 'BFF je vzor, ve kterém má každý frontend (web, mobile, partner API) <em>vlastní</em> backend agregátor, který volá downstream microservisy a sestavuje view-model přesně přizpůsobený danému klientovi. V DDD terminologii je to typicky <strong>Open Host Service</strong> (OHS) s <strong>Published Language</strong>, doplněný Anti-Corruption Layerem proti volaným službám – viz <a href="/context-mapping#ohs">Open Host Service</a> a <a href="/context-mapping#published-language">Published Language</a>. BFF nepatří do žádného doménového Bounded Contextu; je to integrační vrstva, vlastní BC sám o sobě (typicky „Web Frontend BC“).'
- question: GraphQL Federation jako náhrada microservices integrace?
  answer: 'GraphQL Federation umožňuje, aby více servisů vystavilo svou část schématu a aby gateway (Apollo Router) je sloučila do jednoho schema z pohledu klienta. Pro <em>read</em> operace přes microservices odstíní klienta od fyzického rozdělení. Pro <em>write</em> operace federation neřeší distribuované transakce; pořád potřebujete <a href="/sagy-a-process-managery">ságu</a>. Doporučení: federation jako read fasáda, nikoli jako náhrada eventem řízené architektury.'
- question: Které service vlastní data o customerovi napříč BC?
  answer: 'Žádná „centrální“ Customer service. Každý Bounded Context má vlastní pohled na customer, který odpovídá jeho jazyku a kontextu – Ordering vidí <code>Customer</code> jako adresu pro doručení a platební preferenci, Billing jako fakturačního partnera s VAT IDs, Support jako entitu s historií ticketů. Stejné <code>customerId</code>, různé modely. Tomu se říká <em>polysemic concept</em> v Context Mappingu. Pokud se rozhodnete jeden BC označit za „source of truth“ pro identitu zákazníka, ostatní BC od něj přebírají jen <code>customerId</code> a vlastní attributy si modelují samy. Detail v <a href="/context-mapping">Context Mappingu</a>.'
:::
