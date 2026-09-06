---
route: microservices_and_ddd
path: /ddd-a-microservices
title: DDD a microservices – Bounded Context jako service boundary
page_title: "DDD a microservices: hranice Bounded Contextu | DDD Symfony"
meta_description: "Kdy Bounded Context = microservice a kdy stačí modular monolith. Jak se vyhnout distributed monolithu – podle Sama Newmana a Chrise Richardsona."
meta_keywords: "DDD, microservices, Bounded Context, modular monolith, distributed monolith, Symfony 8, Symfony Messenger, integration event, service boundary, Sam Newman, Chris Richardson, strangler fig, service mesh, saga"
og_type: article
published: "2026-04-29"
modified: 2026-09-06
breadcrumb_name: DDD a microservices
schema_type: TechArticle
schema_headline: "DDD a microservices – Bounded Context jako service boundary"
chapter_number: "19"
category: Praxe
deck: "Slogan BC = microservice je polopravda. Bounded Context je logická hranice modelu; microservice je fyzická hranice deploymentu. Kapitola o tom, kdy mapování 1:1 dává smysl, kdy modular monolith poráží microservices a jak rozeznat distributed monolith včas."
reading_time: 34
difficulty: 4
github_examples: null
---

V [kapitole o základních konceptech](/zakladni-koncepty#bounded-contexts) jsme zavedli **Bounded Context** jako jasně ohraničenou oblast, ve které platí jeden konzistentní doménový model a jeden Ubiquitous Language. V [Context Mappingu](/context-mapping) jsme rozebrali, jak různé Bounded Contexts spolu komunikují (Customer-Supplier, Conformist, Anti-Corruption Layer, Open Host Service, Published Language). Kapitola o [ságách a Process Managerech](/sagy-a-process-managery) pak přidala koordinaci doménového procesu napříč více Bounded Contexts, tedy kompenzace místo distribuovaných transakcí.

Tato kapitola odpovídá na otázku, kterou si dříve nebo později položí každý tým: **jak se z těchto logických hranic stanou fyzické nasazovací jednotky?** Konkrétně: jak se Bounded Context mapuje na microservice a kdy ne. Pokrývá tři často přehlížené pravdy. Mapování 1:1 (BC = service) je jen jedna ze tří možností. Pro většinu týmů je *modular monolith* rozumnější výchozí bod. Microservices špatně navržené jsou horší než monolit, kterému se snaží uniknout.

## 19.01 Mýtus „microservice = Bounded Context“ {#mytus}

V komunitě DDD a microservices koluje v různých variantách slogan: *„Each microservice should be one Bounded Context.“* Kořen má v článku Jamese Lewise a Martina Fowlera *Microservices* (2014), kde se mluví o *přirozené korelaci* mezi hranicí služby a hranicí kontextu. Korelace se cestou ztvrdla v rovnici. Závěr se nabízí sám. DDD vymezuje hranici modelu, microservices hranici nasazení, tak proč obojí neztotožnit. Praxe ale tento závěr nepotvrzuje. Slogan je **polopravda**, která vede k chybným architektonickým rozhodnutím častěji než ke správným.

Podstatné je rozlišit dvě úrovně, které slogan slévá do jedné. Bounded Context je **logická hranice modelu**: vymezuje území, kde platí jeden konzistentní výklad pojmů, jeden Ubiquitous Language a jedna sada invariantů. Microservice je naproti tomu **fyzická hranice deploymentu**: jeden sestavovaný artefakt, jeden běžící proces, jedna databáze, jeden odpovědný tým. Tyto dvě úrovně se mohou, ale nemusí překrývat.

Sam Newman v knize *Building Microservices, 2nd ed.* (2021) tuto distinkci zdůrazňuje opakovaně. V kapitole 2 píše, že Bounded Context je silným kandidátem na service boundary. Rozhodnutí, zda kontext skutečně dostane vlastní nasazovací jednotku, závisí na faktorech jako velikost týmu, rozdílné potřeby škálování, různý release cyklus a operační kapacita organizace. Chris Richardson v knize *Microservices Patterns* (2018) v kapitole 2 popisuje stejné rozhodnutí jako „decomposition by business capability“ a zdůrazňuje, že rozdělení musí mít doménový důvod, ne čistě technický.

Eric Evans, autor pojmu Bounded Context, se na věc dívá z opačné strany. V přednášce *DDD & Microservices: At Last, Some Boundaries!* (QCon London 2016) tvrdí, že logické rozdělení uvnitř jednoho procesu v praxi neobstojí. Nic ho nevynucuje a hranice se postupně rozpouští. Samostatná nasazovací jednotka dodá hranici fyzickou vynutitelnost, kterou modul sám o sobě nemá. Vztah je tedy podle Evanse **umožnění, ne rovnost**: microservice je jeden ze způsobů, jak hranici kontextu ubránit, ne její definice. Tato kapitola s Evansovou diagnózou souhlasí a odpovídá na ni jinak. Hranice jde vynutit i uvnitř monolitu, [statickou kontrolou v CI](#phparkitect-heading).

:::callout{type="note"}
### Pravda místo sloganu {#mytus-pravda-heading}

Bounded Context a microservice nejsou totéž. Existují tři varianty mapování. **1:1** (jeden BC = jedna service) je cílový stav, když pro něj platí konkrétní podmínky. **1:N**, tedy jeden BC rozdělený do více services, bývá chyba. **N:1** (více BC ve stejném deployable artefaktu) je modular monolith. Každá varianta má svůj kontext, ve kterém je správná.

Slogan „BC = microservice“ je užitečný jen jako *výchozí hypotéza*, kterou tým ověřuje organizačními a technickými fakty. Není to architektonický příkaz.
:::

Ještě jedno nedorozumění zaslouží upozornění: pojem „Bounded Context“ se v komunitě někdy používá volněji než ho definoval Eric Evans. Někdy se jím myslí pouhý modul, jindy celá produktová doména. Vaughn Vernon v knize *Implementing Domain-Driven Design* (2013) v kapitole 2 striktně připomíná, že Bounded Context je **jazyková hranice**. Uvnitř jednoho BC má každý termín jediný význam. Pokud o stejném pojmu (například „Customer“) mluví dva týmy odlišně, jsou to dva Bounded Contexts, bez ohledu na to, zda běží v jednom či dvou Symfony procesech.

:::diagram{fig="19.1-A" title="Tři scénáře mapování Bounded Context ↔ Service" src="images/diagrams/20_microservices/bc_to_service.svg"}
Tři scénáře: 1 BC = 1 service (správně oddělený microservice), 1 BC = N services (přehnané dělení, typicky distributed monolith) a N BC = 1 service (modular monolith). Volba mezi nimi je organizační, ne technická.
:::

Tabulka níže shrnuje, čím se Bounded Context a microservice liší a v jaké rovině se rozhoduje. Tento rozdíl drží napříč dalšími sekcemi. Většina anti-vzorů v této kapitole vzniká z toho, že tým plete jednu úroveň s druhou.

| Aspekt | Bounded Context | Microservice |
|---|---|---|
| Hranice | Logická – model a jazyk | Fyzická – proces, deploy, DB |
| Definici zavedli | Evans 2003, Vernon 2013 | Lewis & Fowler 2014, Newman 2015 |
| Vlastník | Tým doménových expertů + vývojářů | Stream-aligned team (Skelton & Pais 2019) |
| Mění se kvůli | Změně doménového modelu | Škálování, release cyklu, provoz |
| Existuje i v monolitu | Ano, vždy – jako modul | Ne, monolit je jeden deployment |

## 19.02 Kdy 1 BC = 1 service (cílový stav) {#bc-jedna-service}

Mapování 1:1 mezi Bounded Contextem a microservice se v komunitě běžně podává jako defaultní cíl a v určitých situacích dává smysl. Automatické pravidlo to ale není. Platí jen tam, kde tým splní konkrétní organizační a technické předpoklady. Sam Newman tyto předpoklady v knize *Building Microservices, 2nd ed.* shrnuje pod hlavičkou „information hiding“ a „autonomy“: service má smysl tehdy, když ji lze měnit, nasazovat a škálovat nezávisle na zbytku systému. Pojem *information hiding* zavádí hned v kapitole 1 a definuje ho prostě. Skrýt uvnitř komponenty co nejvíc, vystavit navenek co nejmíň. Na hledání hranic ho aplikuje v kapitole 2.

### Kdy rozdělit BC do vlastní service {#kdy-rozdelit-heading}

Konkrétní podmínky, které mluví pro vlastní nasazovací jednotku:

- **Vlastní stream-aligned tým** – kontext má dedikovaný tým, který má autonomii nad backlogem, release cyklem a operačními rozhodnutími. Bez toho je vlastní service jen administrativní zátěž navíc. Detail v [Team Topologies](/team-topologies) (Skelton & Pais 2019).
- **Vlastní data** – kontext drží svá data v oddělené databázi (nebo alespoň v oddělených tabulkách s vlastním schema ownerem). Ostatní kontexty na ně nesahají přímo, ale jen přes API nebo události. Sdílená databáze napříč services je určujícím znakem *distributed monolithu* (viz sekci 19.04).
- **Nezávislý release cyklus** – kontext lze nasadit bez současného nasazení jiných kontextů. Pokud změna v service A vyžaduje současnou změnu v service B, nejde o dvě služby – tým má jednu nasazovací jednotku rozdělenou do dvou procesů.
- **Rozdílné potřeby škálování** – kontext má řádově jiný objem zpracování (např. catalog s velkým read trafficem vs. ordering s nízkým, ale transakčně náročným) nebo jiné latency požadavky. Rozdělení umožní horizontálně škálovat jen ten, který to potřebuje.
- **Rozdílný stack nebo runtime** – kontext potřebuje jiné runtime parametry (jiná PHP verze, jiné dependencies, jiné memory limity) nebo dokonce jiný jazyk. Vzácné, ale legitimní.
- **Rozdílný compliance režim** – kontext zpracovává citlivá data (PCI DSS, GDPR speciální kategorie), která mají striktní oddělení od ostatního systému. Network isolation a samostatný audit trail jsou přirozenějším řešením, když kontext žije ve vlastní service.

### Příklad: e-shop se čtyřmi services {#priklad-eshop-heading}

Středně velká e-commerce platforma s 30 inženýry rozdělenými do čtyř stream-aligned týmů identifikovala během [Event Storming](/event-storming) workshopu čtyři Bounded Contexty:

- **Catalog** – produktový katalog, search, kategorie, atributy. Read-heavy, malé write operace, agresivní cache. Tým 8 lidí.
- **Ordering** – košík, objednávky, stav, refundy z doménového pohledu. Transakční, citlivý na latenci, tvrdá konzistence. Tým 9 lidí.
- **Payment** – integrace platebních bran, autorizace, capture, recurring payments, refundy z technického pohledu. PCI DSS scope, audit trail. Tým 6 lidí.
- **Shipping** – integrace s dopravci, sledování, doručovací okna. Eventual konzistence s ordering, dlouhý write cyklus (hodiny i dny). Tým 7 lidí.

Každý z těchto kontextů má vlastní tým, vlastní DB schema, vlastní release cyklus a měřitelně jiné potřeby škálování. Rozhodnutí mít čtyři Symfony aplikace (catalog-svc, ordering-svc, payment-svc, shipping-svc) je v této organizační realitě obhajitelné. Komunikují asynchronně přes [Outbox](/outbox-pattern) a [ságu](/sagy-a-process-managery) typu „Place Order“.

Zopakujme podstatné slovo z předchozího odstavce: **obhajitelné**. Microservices nejsou jen „lepší architektura“. Jsou architektonickým rozhodnutím s kompromisy: vyšší operační složitost, potřeba distributed tracing, eventual consistency všude, kde dříve byla ACID transakce. Tato kapitola tyto kompromisy probírá v dalších sekcích.

### Jak velká má být jedna service {#velikost-service-heading}

Na otázku po velikosti dává použitelnou odpověď Zhamak Dehghani v článku *How to break a Monolith into Microservices* (2018) heslem **„macro first, then micro“**. Začít u větších služeb postavených kolem jednoho doménového konceptu. Dělit je dál teprve tehdy, až je na to tým provozně připravený. Pořadí je podstatné. Sloučit dvě předimenzované služby je refaktoring; rozpletení sítě dvaceti nano-services je projekt na čtvrtletí.

Druhá polovina odpovědi je Newmanovo information hiding. Dobře zvolená hranice skrývá hodně a vystavuje málo. Poznáte ji podle toho, že běžná změna se odehraje uvnitř jedné služby a nikoho jiného se nedotkne. Počet řádků kódu o kvalitě hranice neříká nic.

:::callout{type="pattern"}
### Heuristika 1:1 – kdy ji uplatnit {#bc-1-1-heuristika-heading}

Pokud z předchozího seznamu zaškrtnete **čtyři a více** bodů, je kontext kandidátem na vlastní service. E-shop výše splnil u každého kontextu čtyři body, Payment pět. Body „rozdílný stack“ a „compliance režim“ jsou přitom vzácné; na plných šest nečekejte. Práh čtyř bodů je heuristika této knihy, ne údaj převzatý ze zdroje.

Při **třech a méně** zůstaňte v [modular monolithu](#modular-monolith). Rozdělení vás bude stát víc, než ušetří. Microservices nejsou cílem, ale nástrojem.
:::

## 19.03 Kdy zvolit modular monolith {#modular-monolith}

Modular monolith je jeden nasazovaný celek (jedna Symfony aplikace, jedna databáze, jeden proces), uvnitř kterého žije **více Bounded Contexts jako modulů** s vynucenými hranicemi. Zvenku vypadá jako klasický monolit; uvnitř má disciplínu, kterou byste jinak vynucovali přes service boundary.

Proč o něm mluvit v kapitole o microservices? Pro většinu týmů, které začínají s DDD, je to rozumný výchozí bod. Martin Fowler v článku *MonolithFirst* (2015) argumentuje, že microservices předčasně rozdělují systém, jehož hranice ještě nejsou ustálené. Tím vznikají technické dluhy, které se těžce rozplétají. Sam Newman v *Building Microservices, 2nd ed.* (kap. 3) tento postoj přejímá a explicitně jako výchozí strategii doporučuje monolith-first nebo modular monolith-first.

Konsensus to ale není, a je poctivé to říct. Fowler sám u *MonolithFirst* přiznává, že pro pevný závěr nemá dost doložených případů. Šest dní po vydání textu publikoval Fowler na svém webu opačný názor Stefana Tilkova (*Don't start with a monolith*, 2015). Rozdělit existující monolit je podle Tilkova extrémně těžké: jeho části si mezitím vytvoří závislosti přes sdílené knihovny, databázi a doménové objekty. Kdo tedy ví, že cílí na microservices, měl by podle Tilkova začít rovnou u nich.

Spor rozhoduje jedna proměnná: zda se hranice uvnitř monolitu vynucují, nebo jen doporučují. Tilkovova námitka popisuje monolit bez kontroly hranic a tam platí do puntíku. S pravidly v CI, která zabrání tomu, aby jeden kontext sáhl do vnitřností druhého, monolit tuto vlastnost neztrácí. Přesně proto na následujících stránkách stojí sekce o phparkitectu.

### Kdy zvolit modular monolith {#kdy-modular-heading}

Konkrétní indikátory, podle kterých modular monolith poráží microservices:

- **Malá organizace** – pod ~30 lidí na celém produktu. Není dost stream-aligned týmů na to, aby každý microservice měl dedikovaného vlastníka. Rozdělení do services pak vede k tomu, že jeden tým spravuje pět services a stráví polovinu týdne přepínáním kontextu.
- **Nestabilní hranice** – produkt je v rané fázi a Bounded Contexty ještě procházejí iteracemi. Refaktor hranice uvnitř monolithu je triviální (přesun souborů a tříd); refaktor přes síťovou hranici dvou services je migrace dat, koordinovaný release a Anti-Corruption Layer.
- **Podobné potřeby škálování všech kontextů** – pokud catalog, ordering i shipping mají podobný objem a profil, není co odděleně škálovat. Horizontální škálování celého monolithu je operačně levnější než škálování čtyř services.
- **Nemáte operační platformu pro N services** – žádný Kubernetes, žádný service mesh, žádné centralizované logging a tracing. Bez nich budou microservices fungovat technicky, ale ladění incidentů bude noční můra. Více v [sekci o provozu](#ops).
- **Nízká operační kapacita** – orientační práh: do platformy (CI/CD, observability, deployments, incident response) jde méně než ~30 % vývojové kapacity. Newman opakovaně připomíná, že microservices přesouvají komplexitu do provozu. Pokud na provoz nemáte lidi, modular monolith vás chrání před zhoršením produktivity.

### Modular monolith v Symfony 8 {#modular-monolith-symfony-heading}

V Symfony se modular monolith přirozeně realizuje strukturou adresářů pod `src/`. Každý Bounded Context dostává vlastní namespace a vlastní podadresář, typicky se třemi vrstvami (Domain, Application, Infrastructure) kvůli souladu s [vertikálním řezem](/architektonicke-styly#vertical-slice):

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
└── SharedKernel/                 # Malá sdílená podmnožina doménového modelu
    ├── Domain/
    │   └── ValueObject/
    │       ├── Money.php
    │       └── Currency.php
    └── Application/
:::

Všimněte si dvou detailů. `SharedKernel` drží **malou sdílenou podmnožinu doménového modelu**, tedy hodnotové objekty jako `Money` a `Currency`, na jejichž výkladu se všechny kontexty shodly. Nepatří sem agregáty ani doménové eventy jednotlivých kontextů; jejich sdílení porušuje definici Bounded Contextu. Změna Shared Kernelu vyžaduje souhlas všech vlastníků. To je drahé, a proto musí zůstat malý. Celý vzor rozebírá [Context Mapping](/context-mapping#shared-kernel). Druhý detail: každý BC má vlastní `Application/IntegrationEvent/`, kam mapuje příchozí události z jiných kontextů. Stejný princip použijeme v sekci 19.08 i mezi separátními services.

### Vynucení hranic přes phparkitect {#phparkitect-heading}

Adresářová struktura sama o sobě nestačí. Vývojář pod tlakem deadlinu si do `App\Ordering\Domain` klidně přidá `use App\Billing\Infrastructure\StripeClient;` a hranice je porušená. Disciplínu drží **automatická kontrola** v CI. Pro PHP slouží [phparkitect](https://github.com/phparkitect/arkitect), statický analyzátor pravidel architektury.

:::callout{type="pattern"}
### PHP: Pravidla pro modular monolith v phparkitect {#phparkitect-rules-heading}

:::code{language="php" filename="phparkitect.php" highlights="16,17,18,19,20,21,47,48,49,50,51"}
<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__ . '/src');

    // Kontexty se vyjmenují jednou; pravidla se z nich odvodí pro každý.
    // Ruční výčet by jinak nechal nové kontexty bez subjektu - a pravidlo,
    // které nemá subjekt, nehlásí nic.
    $contexts = ['Ordering', 'Billing', 'Catalog', 'Shipping'];
    $rules = [];

    foreach ($contexts as $context) {
        $foreign = array_values(array_diff($contexts, [$context]));

        // Pravidlo 1: kontext nesmí znát Infrastructure jiných kontextů.
        // Komunikace probíhá přes Application interface nebo events.
        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces("App\\{$context}"))
            ->should(new NotDependsOnTheseNamespaces(
                array_map(static fn (string $bc): string => "App\\{$bc}\\Infrastructure", $foreign),
            ))
            ->because(
                'Kontexty spolu mluví přes events nebo Application interface, ne přes '
                . 'Infrastructure. Sdílená Infrastructure = distributed monolith po rozdělení.'
            );

        // Pravidlo 2: Domain vrstva nesmí znát žádný jiný kontext.
        // Ani jeho Application - Domain je nejvíc izolovaná.
        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces("App\\{$context}\\Domain"))
            ->should(new NotDependsOnTheseNamespaces(
                array_map(static fn (string $bc): string => "App\\{$bc}", $foreign),
            ))
            ->because(
                'Doménová vrstva je čistá - nezná ostatní kontexty ani jejich Application '
                . 'vrstvu. Cross-BC integrace patří do Application/IntegrationEvent.'
            );

        // Pravidlo 3: doménové eventy zůstávají uvnitř svého kontextu.
        // Subscriber v jiném kontextu má vlastní IntegrationEvent DTO.
        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces("App\\{$context}"))
            ->should(new NotDependsOnTheseNamespaces(
                array_map(static fn (string $bc): string => "App\\{$bc}\\Domain\\Event", $foreign),
            ))
            ->because(
                'Billing nesmí použít App\Ordering\Domain\Event\OrderPlaced přímo. Místo toho '
                . 'má App\Billing\Application\IntegrationEvent\OrderPlacedReceived. Bez tohoto '
                . 'pravidla vznikne po rozdělení monolithu sdílená library = distributed monolith.'
            );
    }

    // SharedKernel je pro všechny; nesmí ale záviset na žádném kontextu.
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\SharedKernel'))
        ->should(new NotDependsOnTheseNamespaces(
            array_map(static fn (string $bc): string => "App\\{$bc}", $contexts),
        ))
        ->because('Shared Kernel je společný základ, ne obousměrná závislost.');

    $config->add($classSet, ...$rules);
};
:::
:::

Podstatné je, že subjektem pravidla je **každý** kontext. Ručně psaná trojice pravidel jen pro dva kontexty nechá zbytek bez dozoru: průlom z Catalogu do Orderingu projde, protože Catalog není subjektem ničeho. Odvození ze seznamu tuhle díru zavře a nový kontext stačí přidat do pole. Kdo chce jít dál, otočí polaritu na `DependsOnlyOnTheseNamespaces`, tedy whitelist, který cizí závislost odmítne i bez výčtu.

Pravidlo se spouští v CI jako součást běžné kontroly kvality:

:::code{language="json" filename="composer.json (výřez)"}
{
    "scripts": {
        "phparkitect": "phparkitect check --config=phparkitect.php",
        "ci": [
            "@phparkitect",
            "@phpstan",
            "@phpunit"
        ]
    }
}
:::

:::code{language="yaml" filename=".github/workflows/ci.yml (výřez)"}
- name: Architecture rules
  run: composer phparkitect
:::

S tímto pravidlem můžete bezpečně zůstat v monolithu měsíce a roky. Hranice mezi BC jsou vynucené stejně tvrdě, jako by byly za HTTP/AMQP, ale platíte za ně řádově méně operační složitosti.

### Kdy z modular monolithu odejít {#kdy-z-monolithu-heading}

Modular monolith je výchozí stav, který v určitém bodě některé BC opustí. Indikátory, že jeden konkrétní modul je připraven na vlastní service:

1. Modul má výrazně jiný profil zátěže, typicky read-heavy modul (catalog, search) nebo modul s nepravidelnými špičkami (notifications, batch reporting).
2. Modul má vlastní stream-aligned tým, který chce vlastní release cyklus a má operační kapacitu se postarat o samostatný runtime.
3. Modul potřebuje compliance isolation (PCI DSS, GDPR speciální data).
4. Hranice modulu se posledních ~6 měsíců neměnila. Model je stabilní a refaktor přes síťovou hranici nehrozí.

Postup migrace probíráme detailně v [sekci 19.09 (Strangler Fig)](#migrace).

## 19.04 Distributed Monolith – anti-vzor {#distributed-monolith}

Distributed monolith má **vnější tvar microservices** (N samostatných services, N deploymentů, N repozitářů, N týmů), ale **vnitřní coupling monolithu** (sdílená databáze, synchronní volání všude, coordinated release, sdílená library s doménovými typy). Platíte všechny náklady microservices a nedostáváte žádnou z jejich výhod. Je to nejdražší známý způsob, jak provozovat monolit.

Vzniká dvěma cestami, které jsou často nerozlišitelné. **První cesta**: tým rozdělil monolit do services dříve, než identifikoval Bounded Contexty. Hranice mezi services jsou tedy náhodné (typicky podle technické vrstvy nebo podle CRUD entit), ne podle domény. Services musí mezi sebou komunikovat o všem a coupling je vrstevně rozprostřený. **Druhá cesta**: tým rozdělil správně podle BC, ale neověřil, že každá service má skutečnou autonomii. Sdílela databázi „pro jednoduchost“, sdílela library s doménovými typy „aby se neopakoval kód“, sdílela deployment pipeline „aby release byl atomický“.

:::callout{type="anti"}
### 5 příznaků distributed monolithu {#distributed-monolith-priznaky-heading}

Pokud vám sedí dva a více těchto bodů, máte distributed monolith:

1. **Sdílené databázové schéma napříč services.** Service A i service B čtou (nebo dokonce zapisují) do stejných tabulek. Změna schématu jednoho zlomí druhý. Newman je v tomto bodě z celé knihy nejostřejší: sdílení databází je podle něj to nejhorší, co pro nezávislou nasazovatelnost můžete udělat. Rozlišujte přitom sdílenou *instanci* od sdíleného *schématu*. Jeden databázový server s oddělenými schématy a jediným vlastníkem u každého z nich je legitimní a levný mezikrok; jedno schéma se dvěma zapisovateli není. Kanonický rozbor anti-vzoru včetně opravy přes Anti-Corruption Layer je v [Anti-vzorech](/anti-vzory#sdilena-databaze).
2. **Synchronní HTTP/gRPC volání mezi services v každém request flow.** Vyřízení jednoho user requestu vyžaduje 5–10 vnořených volání. Latence je součet všech volání; dostupnost je součinem dostupností všech volaných služeb; failure jednoho znamená failure celého řetězce.
3. **Coupled deployment.** Změnu API service A nelze nasadit, dokud současně nenasadíte service B, která konzumuje to API. „Release je atomický“, „máme deployment train“ – to jsou eufemizmy pro coupled deploy. Sam Newman: pokud nelze service nasadit samostatně, není to microservice.
4. **End-to-end test vyžaduje všechny services.** Test jednoho user flow nelze spustit bez toho, abyste měli runtime všech N services (lokálně přes docker-compose, v CI přes test environment). Žádná service není testovatelná v izolaci.
5. **Sdílená library s doménovými typy.** Existuje balíček `company/domain-shared`, který obsahuje třídy jako `OrderPlaced`, `Money`, `CustomerId` používané všemi services. Změna v balíčku vynucuje současný release všech services. Coupling je tu silný stejně jako v monolithu, jen se schovává za package version.
:::

### Proč je horší než monolith {#proc-distributed-monolith-heading}

Pokud máte coupling jako monolith a operační režii jako microservices, dostáváte to nejhorší z obou světů. Konkrétně:

- **Latence.** Vnitřní volání monolithu je volání funkce (~µs); volání mezi services je síťová cesta tam a zpět (~ms) plus serializace, deserializace a validace. Rozdíl tří řádů na každé volání. Při 10 vnořených voláních narostou mikrosekundy na desítky milisekund.
- **Availability.** Pokud každá service má 99,9% uptime, řetězec deseti services má 99,0 %, tedy desetinásobně větší nedostupnost.
- **Debugging.** Trace jednoho requestu prochází N services. Bez distributed tracing je incident skoro nedohledatelný. S ním je drahý.
- **Refaktoring.** Přesunutí pole z jedné entity do jiné je v monolithu refaktoring v IDE. Mezi services je to migrace dat, change API smluv, koordinovaný deploy a období dual-write.
- **Testovací prostředí.** Místo `composer install && vendor/bin/phpunit` potřebujete docker-compose s deseti kontejnery a 32 GB RAM.

Detailní rozbor obecných anti-vzorů, které k distributed monolithu vedou (microservices first, shared DB, sync everywhere), najdete v [kapitole 21 – Anti-vzory DDD](/anti-vzory).

### Hybridní topologie – mix monolitu a extraktů {#hybridni-topologie-heading}

Reálné systémy zřídka spadají do jedné z čistých kategorií „monolit“ vs. „microservices“.
Nejčastěji se objevuje **hybridní topologie**: jeden modulární monolit jako *core*
plus 1–3 extrahované services pro kontexty, které mají jasný důvod existovat samostatně.

:::diagram{fig="19.4-A" title="Hybridní topologie – core monolit + 2 extrakty s důvody" src="images/diagrams/20_microservices/hybrid_topology.svg"}
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

Hybrid je legitimní cíl, ne přechodný stav „dokud nedokončíme migraci na microservices“.
Důvody, proč zůstat hybridní dlouhodobě:

- **Core domény jsou úzce provázané** mezi sebou (Catalog ↔ Ordering ↔ Customer)
  a refaktor se v monolitu dělá v IDE; rozdělení do tří services přidává Anti-Corruption
  Layer pro každý cross-context dotaz.
- **Periferní kontexty mají jasný operační důvod** být samostatně (Payment kvůli
  compliance, Reporting kvůli load profile, Public API kvůli SLA).
- **Tým neunese N+1 services** – core monolith vlastní jeden tým, každý extrakt
  přidá operační dluh.

### De-microservicing – návrat k monolitu {#de-microservicing-heading}

Návrat od několika služeb k jednomu procesu není kacířství. Protipozice vůči
dělení za každou cenu existuje po celou dobu trvání trendu: Fowler ji formuloval
v *MonolithFirst* už v roce 2015, Newman ve stejném roce v textu *Microservices
For Greenfield?* a DHH v *The Majestic Monolith* (2016). Nové není přesvědčení,
ale počet týmů, které mají za sebou dekádu provozu a mohou rozhodnutí
revidovat s vlastními daty.

Nejcitovanějším příkladem je **Amazon Prime Video** (květen 2023). Kolem něj se
ale nabalila historka, která s původním textem souhlasí jen zčásti:

- **Co tým skutečně popsal.** Komponenta Video Quality Analysis běžela jako
  serverless pipeline. Orchestraci zajišťovaly AWS Step Functions, výpočet běžel
  v Lambda funkcích a videosnímky se mezitím ukládaly do S3. Účet rostl na orchestraci a na Tier-1
  voláních do S3. Sloučením komponent do jednoho procesu na ECS mezisklad odpadl,
  data tečou v paměti a infrastrukturní náklady klesly o více než 90 %.
- **Jaký to mělo rozsah.** Jedna komponenta jednoho týmu. Ne Prime Video jako
  produkt a rozhodně ne obrat architektonické strategie Amazonu. Článek vyšel na
  blogu Prime Video Tech, který mezitím zanikl. Původní adresa dnes přesměrovává
  na aboutamazon.com, takže se text cituje přes archiv a reprinty.
- **Jak to čte ten, kdo microservices zpopularizoval.** Adrian Cockcroft
  (Netflix, později VP v AWS) na vlnu reakcí odpověděl textem *So many bad takes*.
  Jeho námitka je jednoduchá: šlo o dotažení serverless prototypu do produkční
  podoby, tedy o běžný krok ve vývoji jedné služby, ne o důkaz proti
  microservices obecně.

Použitelný závěr z případu vyvodil Werner Vogels v článku *Monoliths are not
dinosaurs* (2023): jedno řešení pro všechno neexistuje a architekturu je třeba
revidovat pokaždé, když se změní zátěžový profil. Prime Video u sebe provozuje
obojí: live sports streaming jako distribuovaný workflow a monitoring jako jeden
proces. To je něco jiného než „microservices selhaly“.

Symptomy, které mluví pro de-microservicing:

- **Latenční zátěž,** která neodpovídá síťové latenci mezi services. Často znamená,
  že interakce by měla být volání funkce v jednom procesu, ne síťový skok.
- **Refaktor kontextu vyžaduje současné změny v 3+ services.** Hranici tým zvolil
  špatně; refaktor v monolitu je triviální.
- **Inženýrská kapacita > 50 % na operační platformu.** Tým udržuje Kubernetes,
  service mesh a distributed tracing místo toho, aby pracoval na produktu.
- **Provozní náklady na infrastrukturu rostou disproporčně k objemu.** AWS Lambda
  + API Gateway + DynamoDB napříč 20 services stojí řádově víc než srovnatelný
  EC2 monolit.
- **Incident MTTR > 60 minut.** Distributed tracing není dost na to, aby tým
  rychle identifikoval kořenovou příčinu v N services.

Postup de-microservicingu je opačný k extraction patternu z 19.09:

:::callout{type="pattern"}
### Postup návratu z microservices do monolitu {#de-microservicing-postup-heading}

1. **Audit BC hranic.** Které services reálně mají vlastní team/data/release/scaling?
   Které se rozdělily předčasně?
2. **Strangler v opačném směru.** Místo extrakce z monolitu se konsoliduje *do*
   monolitu. Začínáte u nejvíce provázané services s nejnižším operačním přínosem.
3. **Replikace doménového kódu.** Service A se stane modulem `App\Catalog\` v monolithu.
   Eventy, které dříve šly přes broker, jdou interním EventDispatcherem.
4. **Migrace dat.** Databáze services se buď konsolidují do schémat monolitu, nebo se
   nová modulární data získávají z bývalé service DB jako read-only zdroj během přechodu.
5. **Vyřazení service.** Po N týdnech souběžného běhu se původní service odstaví,
   smazat lze až po čas pro forenzní kontrolu (typicky 90 dní).

Záleží i na tom, jak se rozhodnutí pojmenuje: **de-microservicing je legitimní architektonická volba**, ne selhání.
Reaguje na změnu kontextu (tým se zmenšil, profil zátěže se vyrovnal,
operační kapacita klesla). Poctivé pojmenování pomáhá. Nekomunikujte to jako „regression“, ale jako
„consolidation“.
:::

### Náklady microservices – co se vlastně platí {#naklady-heading}

Sam Newman v *Building Microservices, 2nd ed.* (kap. 1, sekce „Microservice Pain Points“) shrnuje nákladové oblasti,
které se v rozhodování o microservices často přehlíží. Kategorie níže jsou jeho.
Konkrétní čísla nikoli. Závisejí na cloudu, regionu, mzdové hladině a na tom,
kolik si tým provozuje sám. Tabulka proto říká, *co* platíte a *čím* to roste,
ne kolik to stojí:

| Kategorie | Co se v ní platí | Čím roste |
|---|---|---|
| Platformní tým | Provoz orchestrátoru, service mesh, observability stacku | Počtem services a prostředí |
| Cloud infrastruktura | Více databází, message broker, load balancery, NAT | Počtem services |
| Observability | Licence APM nástrojů, retence logů a trace | Počtem hostů, metrik a spanů |
| CI/CD | N pipeline místo jedné, integrační testy přes docker-compose | Počtem services a frekvencí releasů |
| Onboarding | Pochopit topologii, deployment a ladění napříč services | Složitostí topologie |
| Incident response | Delší cesta ke kořenové příčině napříč services | Hloubkou volacích řetězců |

:::callout{type="note"}
### Ilustrativní scénář: co rozpočet unese {#naklady-scenar-heading}

Modelový start-up s 30 inženýry. Vlastní platformní tým o třech lidech,
observability stack na komerční licenci a cloud overhead proti jedné aplikaci
dohromady vycházejí řádově na jednotky milionů korun ročně navíc oproti
modulárnímu monolitu. Číslo je odhad této knihy, ne zjištění z měření. Dosaďte
si vlastní mzdové náklady a ceník poskytovatele.

Jestli vyjde pět milionů nebo deset, není to hlavní. Rozhoduje, že položka
existuje, opakuje se každý rok a někdo ji zaplatí. Argument „microservices jsou
prostě lepší“ na ni odpověď nemá.
:::

## 19.05 Kontrakt mezi services – komunikace a data {#kontrakt}

Jakmile máte dvě services, musíte se rozhodnout, jak spolu komunikují. Nabízejí se dva základní interakční vzory. **Synchronní** jde přes REST, gRPC nebo SOAP; **asynchronní** přes message broker (RabbitMQ, Kafka, NATS, AWS SNS/SQS). Většina reálných systémů kombinuje obojí. Volba mezi nimi určuje výsledné coupling, latenci a availability.

### Synchronní volání – kdy {#sync-kdy-heading}

- **Query (read), kde volající potřebuje odpověď během request flow.** Frontend potřebuje detail produktu pro vykreslení stránky; `catalog-svc` ho vrátí přes REST. Bez odpovědi nemůže pokračovat.
- **Validace, která blokuje další krok.** Před uložením objednávky musí `ordering-svc` ověřit u `catalog-svc`, že produkt existuje a je dostupný. Volání musí být sync, jinak riskujete, že uložíte objednávku na neexistující produkt.
- **Latence-sensitive operace.** Detekce podvodů v reálném čase, autorizace platby, rate limit check.
- **Idempotentní lookup.** Neměnné nebo zřídka měnící se data, kde latence sítě nevadí a kde pomůže cache.

### Asynchronní eventy – kdy {#async-kdy-heading}

- **State changes (write), kde volající nepotřebuje vědět, co dál.** Po uložení objednávky publikuje `ordering-svc` event `OrderPlaced`. `billing-svc`, `shipping-svc` a `notification-svc` ho zpracují, kdy mohou. Volající čeká jen na lokální commit.
- **Cross-BC reakce, kde jednotlivé BC nemají závislost na výsledku.** Saga zpracovává krok po kroku přes eventy + commands; každý krok je nezávislý.
- **Operace, která může bezpečně probíhat se zpožděním.** Generování faktury, odeslání e-mailu, aktualizace search indexu, generování sitemapy.
- **Multi-subscriber broadcast.** Jeden event konzumuje N nezávislých subscriberů; publisher o nich nemusí vědět.

### Pravidlo „async-first“ {#async-first-pravidlo-heading}

Chris Richardson v *Microservices Patterns* (kap. 3) formuluje doporučení: **přednost má asynchronní messaging**, sync jen tam, kde je to objektivně nutné. Důvody:

- Asynchronní subscriber lze restartovat, opakovat, rozdělit do replik. Sync volající čeká a buď dostane odpověď, nebo timeout. Zotavení žádné.
- Asynchronní messaging má lepší časové oddělení: subscriber může být dočasně nedostupný a publisher to neví. Při sync volání je publisher přímo závislý na uptime volaného.
- Asynchronní toky lépe škálují: fronta zpráv se hromadí a worker ji konzumuje vlastním tempem; sync flow se musí škálovat synchronně a end-to-end.
- Asynchronní tok přirozeněji zapadá do [Event Storming](/event-storming) modelu, protože doménové eventy jsou stejně jednotkou domény.

Asynchronní komunikaci mezi services obstará v Symfony Messenger (transport AMQP nebo Redis) v kombinaci s [Outbox patternem](/outbox-pattern), aby se event zapsal atomicky spolu s doménovým stavem. Detail v sekci [19.08 Symfony konkrétně](#symfony).

| Aspekt | Sync (REST/gRPC) | Async (eventy) |
|---|---|---|
| Coupling v čase | Tight – volající čeká | Loose – subscriber může být offline |
| Latence vnímaná uživatelem | Součet všech sync volání | Latence lokálního zápisu |
| Availability | Součin uptime všech volaných | Jen lokální uptime + broker |
| Backpressure | Caller dostane HTTP 503 | Fronta se hromadí, worker dotahuje |
| Refactoring API | Coordinated release volajícího + volaného | Subscriber má vlastní integration event DTO |
| Testovatelnost | Vyžaduje WireMock / Pact / mock | Stačí dispatch eventu do test handleru |

### Kolik dat nese událost {#kolik-dat-heading}

Osa sync/async je jen polovina rozhodnutí. Druhá polovina zní: co se do události vejde. Martin Fowler v článku *What do you mean by „Event-Driven“?* (2017) rozlišuje dva režimy, které se v praxi pletou.

**Event notification** oznamuje, že se něco stalo, a nic víc. Zpráva nese identifikátor a čas; kdo chce detaily, musí si je dotáhnout zpětným voláním. Publisher zůstává hubený a smlouva mezi službami je minimální, ale příjemce má znovu synchronní závislost na tom, kdo událost poslal, jen posunutou o krok dál.

**Event-carried state transfer** nese v události všechna data, která příjemce potřebuje. Zpětné volání odpadá, subscriber si z proudu událostí staví vlastní kopii dat a přežije výpadek publishera. Platí se za to duplikací dat a širší smlouvou. Každé pole v payloadu je závazek, který někdo konzumuje.

Ukázka v sekci 19.08 patří do druhé kategorie. `OrderPlacedReceived` nese `orderId`, `customerId`, částku i měnu, takže `billing-svc` vystaví fakturu bez jediného dotazu zpět do `ordering-svc`. Volba mezi oběma režimy patří udělat vědomě a pro jeden směr integrace pak držet jeden z nich. Míchání obou v jednom toku končí tím, že nikdo neví, která data jsou autoritativní.

### Vlastnictví dat – database per service {#data-ownership-heading}

Rozdělit kód je ta snadnější polovina. Těžší je rozdělit data. Chris Richardson vede *Database per Service* jako samostatný vzor: každá service vlastní své schéma a nikdo jiný do něj nesahá, ani na čtení. Vlastnictví tu znamená právo měnit schéma bez koordinace s kýmkoli dalším. Jakmile do tabulky čte druhá service, právo zaniká a s ním i nezávislý deploy.

Cena vzoru se ukáže ve chvíli, kdy potřebujete data ze dvou services najednou. `JOIN` přes hranici neexistuje a nahrazují ho tři možnosti:

1. **API composition.** Volající si vyžádá kus dat z každé service a složí je v paměti. Funguje pro malé výsledky. Na stránkování a řazení přes hranici se rozpadá – setřídit dvě stránky ze dvou zdrojů znamená stáhnout obě celé.
2. **Read model plněný událostmi.** Service si z příchozích integračních událostí staví vlastní denormalizovaný pohled a čte výhradně ze svého. Dotazy jsou rychlé, cenou je eventual consistency a duplikace. Mechanismus i jeho úskalí rozebírá [kapitola o CQRS](/cqrs).
3. **Samostatný analytický sklad.** Reporting a exporty do provozní databáze nepatří. Data se replikují do skladu, který vlastní reporting a který nikdo z provozních služeb nedotazuje.

Duplikovaná data potřebují pravidlo, jinak se z nich stane druhý zdroj pravdy. Kopie v cizí službě je projekce s jasným vlastníkem: jeden kontext data vytváří a publikuje, ostatní si drží read-only pohled a nikdy ho nemění za zády vlastníka. Pokud dvě services tvrdí, že vlastní totéž pole, hranice mezi nimi je špatně vedená a žádný integrační vzor to nespraví.

## 19.06 Distribuované transakce – Saga, ne 2PC {#distribuovane-transakce}

Jakmile doménový proces překročí hranici jednoho Bounded Contextu (a v microservices architektuře tedy hranici jedné service), musíte řešit otázku **konzistence napříč services**. ACID transakce, na kterou jste zvyklí v jedné databázi, v distribuovaném prostředí přestává platit. Klasickou odpovědí kdysi býval *Two-Phase Commit* (2PC, XA transactions). V microservices architektuře je 2PC **prakticky nepoužitelný**.

### Proč ne 2PC v microservices {#proc-ne-2pc-heading}

2PC předpokládá globálního koordinátora a účastníky s XA podporou. HTTP, AMQP ani externí REST API nic z toho nenabízejí. Účastníci navíc drží zámky během obou fází a při pádu koordinátora uvíznou v *in-doubt* stavu. Podrobný rozbor důvodů (chybějící XA podpora brokerů, cena zámků, single point of failure, porušení autonomie kontextů) najdete v kapitole [Outbox Pattern](/outbox-pattern#2pc-heading).

### Saga jako odpověď {#saga-heading}

Místo jedné velké distribuované transakce saga rozdělí proces na sérii **lokálních transakcí**. Každou commitne jedna service do své databáze. Mezi kroky se posílají eventy nebo commands přes message broker. Pokud některý krok selže, saga provede **kompenzační akce** pro všechny předchozí úspěšné kroky. Je to sémantické vrácení, ne ACID rollback.

Saga existuje ve dvou variantách:

- **Choreografie** – každá service reaguje na eventy ostatních services. Žádný centrální orchestrátor; flow je implicitní v eventech. Vhodné pro jednoduché ságy s 2–3 kroky.
- **Orchestrace** – centrální Process Manager (saga aggregate) drží stav celého procesu a posílá commands jednotlivým services. Vhodné pro komplexní ságy s mnoha kroky, podmínkami, timeouty a retry logikou.

Detailní implementaci ság v Symfony 8 (kompenzace, idempotence, choreografie vs. orchestrace, timeouty, paralelní kroky) probírá samostatná [kapitola 14 – Ságy a Process Managery](/sagy-a-process-managery). Pro účely této kapitoly stačí vědět dvě věci. Saga je **v DDD kontextu doporučovaný mechanismus pro distribuované transakce v microservices**. Daní za to je eventual consistency a kompenzační logika.

:::callout{type="note"}
### Saga vs. 2PC – shrnutí {#saga-vs-2pc-heading}

2PC se snaží zachovat ACID model přes hranici sítě a v praxi to končí blokádou nebo in-doubt stavem. Saga ACID opouští. Akceptuje, že systém je dočasně nekonzistentní a že se konzistence obnoví přes sémantické kompenzace. Pro doménové experty je to často přirozenější model než 2PC. Doménový proces v reálném světě (objednávka, platba, expedice) vždy běží jako sekvence kroků s explicitní undo strategií, ne jako jeden atomický commit.
:::

## 19.07 Service mesh, observability, provoz {#ops}

Microservices jsou především **operační problém**, ne programátorský. Tým, který přejde z monolithu na deset services, najednou řeší věci, které dříve obstarával operační systém a Symfony framework. Routing mezi procesy, retry, circuit breaking, mTLS, distribuovaný debug, service discovery, centralizované logy, rate limiting. Každá z těchto věcí má svůj nástroj a svou cenu. Dohromady tvoří stack, který někdo musí provozovat.

Minimum pojmenoval Martin Fowler už v roce 2014 v textu *MicroservicePrerequisites*: rychlé provisionování prostředí, základní monitoring a rychlý deployment aplikace, k tomu organizační posun směrem k DevOps. Jeho doporučení je jednoznačné: kdo tyto schopnosti nemá, má si je vybudovat dřív, než pustí microservices do produkce. Zbytek této sekce Fowlerovo minimum jen rozepisuje do dnešních nástrojů.

### Service mesh {#service-mesh-heading}

Service mesh (Istio, Linkerd, Consul Connect, AWS App Mesh) je infrastrukturní vrstva, která řeší cross-cutting concerns mezi services. Konkrétně jde o tyto oblasti:

- **mTLS** – vzájemná autentizace přes TLS bez nutnosti manuálního managementu certifikátů.
- **Retry a circuit breaking** – automatické opakování a otevření okruhu při opakovaných failure.
- **Rate limiting** – ochrana proti přetížení a zneužití na úrovni síťové vrstvy.
- **Traffic shaping** – canary deploy, A/B testing s percentage routing, blue/green přepínání.
- **Observability** – latence, error rate, throughput per service edge bez instrumentace v aplikačním kódu.

Implementačně bývá service mesh sidecar: každý pod má vedle aplikačního kontejneru sidecar proxy (Envoy, linkerd-proxy), která zachytává všechen network traffic a aplikuje politiku mesh. Konfigurace se ovládá přes control plane (istiod, linkerd-control).

**Kdy service mesh:** 10+ services, multi-team organizace, požadavek na mTLS bez ruční práce, potřeba pokročilé traffic management (canary, blue/green s percentage routing). **Kdy ne:** 3–5 services, malý tým, žádný Kubernetes. Provozní režie převáží přínosy.

### Observability – three pillars {#observability-heading}

V monolithu stačila kombinace strukturovaných logů a metrik. V microservices přibývá třetí pilíř, distributed tracing. Centralizovaně se pak musí řešit všechny tři:

- **Logs** – centralizované log aggregation (ELK / Loki / CloudWatch). Každý log line musí mít `trace_id` a `service_name`, jinak časovou řadu událostí napříč services nesložíte.
- **Metrics** – Prometheus + Grafana, nebo cloudový ekvivalent (Datadog, NewRelic). Standardní metriky (RED – rate, errors, duration) per service a per endpoint.
- **Traces** – OpenTelemetry + Jaeger / Tempo / Honeycomb. Jeden user request se trasuje napříč všemi services, každý skok má span. Bez toho je ladění nemožné. Pět services a dvacet logů v incidentu nedá dohromady jednu časovou řadu.

### Service discovery a deployment {#service-discovery-heading}

- **Service registry / discovery** – Consul, Kubernetes service / DNS, AWS Cloud Map. Services nemohou spoléhat na statické IP adresy. Potřebují resolver, který v runtime vrátí adresu instance.
- **Container orchestration** – Kubernetes je de facto standard. Bez něj (nebo bez ekvivalentu jako Nomad, ECS) nelze realisticky provozovat víc než pár services. Kubernetes sám je netriviální a jeho provoz je vlastní specializace.
- **CI/CD per service** – každá service má vlastní pipeline, vlastní release schedule, vlastní rollback. Sdílená pipeline = coordinated release = distributed monolith.
- **Schema registry** – pro events přes broker (zejména Kafka) potřebujete schema registry (Confluent, AWS Glue), který verzuje schéma eventů a kontroluje kompatibilitu.

### Contract testing {#contract-testing-heading}

Na příznak číslo 4 z předchozí sekce, tedy že end-to-end test vyžaduje všechny services, existuje kanonická odpověď: **consumer-driven contracts**. Konzument zapíše, co od poskytovatele očekává (jaká pole, jaké typy, jaké stavové kódy), a z toho vznikne kontrakt. Poskytovatel ho pak ověřuje ve své vlastní pipeline, bez běžícího konzumenta. Testovací sada přestane potřebovat runtime všech N services a rozpadne se na N nezávislých sad.

Nejznámější nástroj je Pact. Jeho podpora v PHP je slabší než v Javě nebo JavaScriptu, takže si připravte víc práce s napojením než v jazycích, ze kterých vzor pochází. Alternativa dostupná hned: udržovat schéma integračních událostí jako verzovaný artefakt a v CI kontrolovat, že payload z publishera projde deserializací u každého subscribera. Pokrytí je užší, ale zachytí přesně tu chybu, která v provozu bolí nejvíc. Obecnou strategii testování rozebírá [kapitola o testování](/testovani-ddd).

:::callout{type="warn"}
### Operační pravidlo {#ops-pravidlo-heading}

Pokud nemáte **všechno** z tohoto seznamu (orchestrátor, centralizované logging, distributed tracing, service discovery, CI/CD per service), je modular monolith rozumnější volba. Microservices bez observability nejsou microservices, ale nesouvislé Symfony aplikace, které se vzájemně nekoordinovaně volají.

Přechod z modular monolithu na microservices je primárně **investice do operační platformy**, ne do architektonického refaktoru. Proto má smysl, až když tým má operační kapacitu na to budovat platformu nebo má rozpočet na managed služby (EKS / GKE / AWS Fargate / Datadog).
:::

## 19.08 Symfony konkrétně – kdy a jak {#symfony}

Symfony 8 obslouží obě architektury, modular monolith i microservices, bez zásadní změny kódu v doménové vrstvě. Rozdíl je v **routing konfiguraci Messenger**: ve stejném monolithu všechny eventy a commands směřujete na `sync` transport (přímé volání) nebo na lokální `async` transport (doctrine/redis) s workerem; přes hranici dvou services je směrujete na `amqp` transport, který fyzicky publikuje zprávu do RabbitMQ.

### Modular monolith v Symfony {#symfony-monolith-heading}

V monolithu jsou všechny BC ve stejném Symfony procesu. Cross-BC integrace probíhá přes Domain Events + Symfony Event Dispatcher (jeden DI kontejner, přímý handler) nebo přes Symfony Messenger se `sync` transportem (in-process command bus pattern). Doménový event se v jednom BC dispatchne, handler v druhém BC ho přijme, namapuje na **vlastní integration event DTO** a spustí lokální command. Hranice je čistě v kódu, vynucená phparkitect.

Nejlevněji se cross-BC integrace v monolithu udělá takto. Application Layer publikujícího BC vystaví rozhraní (port), které implementuje konzument. Žádné HTTP, žádný broker. Pokud později rozdělíte BC do services, port zůstane stejný, jen se za ním objeví HTTP klient nebo Messenger.

### Microservice v Symfony {#symfony-microservice-heading}

V microservice architektuře je každý BC vlastní Symfony aplikace s vlastním `composer.json`, `config/`, `src/`, vlastní DB. Cross-service integrace probíhá **výhradně asynchronně** přes Symfony Messenger s AMQP transportem (RabbitMQ) v kombinaci s [Outbox patternem](/outbox-pattern) v publisheru a *Inbox idempotency* v subscriberovi.

Pravidlo: **publisher a subscriber *nesdílejí* PHP třídu eventu.** Publisher má svůj doménový event v `App\Ordering\Domain\Event\OrderPlaced` uvnitř ordering-svc kódu. Subscriber v billing-svc má vlastní `App\Billing\Application\IntegrationEvent\OrderPlacedReceived`, samostatnou třídu, která se naplní z deserializovaného AMQP payloadu. Důvody jsou v sekci 19.04 (sdílená library s doménovými typy = distributed monolith).

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

            # outbox transport pro eventy mezi services
            # používá Doctrine pro atomicitu zápisu eventu se zápisem domény
            events_out:
                dsn: 'doctrine://default?queue_name=outbox_events'
                # Vlastní serializer, ne symfony_serializer: wire formát je
                # součástí kontraktu a nesmí kopírovat tvar doménové třídy.
                serializer: 'App\Ordering\Infrastructure\Messaging\OutboundEventSerializer'
                options:
                    queue_name: 'outbox_events'

            # AMQP transport, kam vlastní relay publikuje zprávy z outboxu
            amqp_out:
                dsn: '%env(AMQP_DSN)%'
                options:
                    exchange:
                        name: 'domain_events'
                        type: 'topic'
                        # Fallback pro zprávy bez explicitního klíče. Relay nastavuje
                        # skutečný routing key per zpráva přes AmqpStamp
                        # (např. new AmqpStamp('ordering.cancelled')).
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

Outbox transport zapisuje event do tabulky ve stejné DB transakci jako doménový commit. Symfony Messenger ovšem žádný vestavěný relay mezi dvěma transporty nemá. Zprávy z `events_out` do `amqp_out` samy nepřetečou. Relay je vlastní kód: console command nebo handler, který záznamy z outbox tabulky čte a jejich payload publikuje na AMQP transport. Implementaci relay procesu včetně pollingu a ošetření duplicit popisuje [Outbox Pattern](/outbox-pattern#relay).

Subscriber service má zrcadlovou konfiguraci: AMQP transport pro příchozí zprávy, vlastní mapping na integration event DTO a lokální command bus pro spuštění reakce:

:::callout{type="pattern"}
### YAML: Messenger config – subscriber (billing-svc) {#messenger-subscriber-heading}

:::code{language="yaml" filename="billing-svc/config/packages/messenger.yaml" highlights="25,26,27,28,29"}
# config/packages/messenger.yaml v billing-svc
framework:
    messenger:
        default_bus: command.bus

        # Selhané zprávy nezahazujeme. Jdou do vlastního transportu
        # s výchozím serializerem, odkud je lze prohlédnout a přehrát.
        failure_transport: failed_events

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
                # Serializer výše je decode-only. Retry na tomto transportu by
                # envelope odeslal znovu přes týž sender, tedy přes encode(),
                # a spadl by. Proto nula pokusů a rovnou failure transport.
                retry_strategy:
                    max_retries: 0

            failed_events:
                dsn: 'doctrine://default?queue_name=failed_events'

            # lokální async pro vnitřní commands
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'

        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction

        # Žádný routing blok: routing určuje, kam se zprávy ODESÍLAJÍ.
        # Příjem řeší worker `php bin/console messenger:consume events_in`;
        # serializer výše mapuje payload na NAŠE vlastní integration event DTO,
        # ne na App\Ordering\Domain\Event\OrderPlaced.
        # Vlastní DTO = vlastní lifecycle, vlastní validace, vlastní version compat.
:::
:::

`IntegrationEventSerializer` je customní serializer, který namapuje deserializovaný AMQP payload (typicky JSON s `event_type` diskriminátorem) na konkrétní třídu integration eventu. Zde se subscriber rozhoduje, jak payload interpretovat, a to ne podle PHP typu, ale podle `event_type` stringu v hlavičce. Tím se publisher a subscriber *plně oddělí na úrovni kódu*.

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
 * Tím je oddělený lifecycle obou services. Publisher může nasadit
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

Ten `event_type` musí ale někdo nastavit. Výchozí `messenger.transport.symfony_serializer`
na straně vydavatele ho neposílá: do hlaviček dá `type` s plným jménem PHP třídy
a doménovou událost serializuje tak, jak je – tedy `Money` jako vnořený objekt.
Konzument z téhle sekce čeká ploché `totalAmountCents` a hlavičku `event_type`,
takže na výchozím serializeru dostane `Missing event_type header`. Vydavatel proto
potřebuje vlastní serializer, který doménovou událost přeloží do dohodnutého tvaru:

:::code{language="php" filename="ordering-svc/src/Infrastructure/Messaging/OutboundEventSerializer.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Messaging;

use App\Ordering\Domain\Event\OrderPlaced;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Encode-only: převádí doménovou událost na dohodnutý wire formát.
 * Tvar payloadu je součástí Published Language, ne detail implementace,
 * takže se nesmí měnit spolu s doménovou třídou.
 */
final class OutboundEventSerializer implements SerializerInterface
{
    /** @param array<string, mixed> $encodedEnvelope */
    public function decode(array $encodedEnvelope): Envelope
    {
        throw new \LogicException('OutboundEventSerializer is encode-only.');
    }

    /** @return array<string, mixed> */
    public function encode(Envelope $envelope): array
    {
        $event = $envelope->getMessage();

        if (!$event instanceof OrderPlaced) {
            throw new \LogicException($event::class . ' nemá dohodnutý wire formát.');
        }

        return [
            'body' => json_encode([
                'eventId'          => $event->eventId,
                'occurredAt'       => $event->occurredAt->format(\DateTimeInterface::ATOM),
                'orderId'          => (string) $event->orderId,
                'customerId'       => (string) $event->customerId,
                'totalAmountCents' => $event->total->amountInCents,
                'currency'         => $event->total->currency->value,
            ], JSON_THROW_ON_ERROR),
            'headers' => ['event_type' => 'ordering.order_placed'],
        ];
    }
}
:::

Custom serializer dělá překlad mezi binárním AMQP payloadem a konkrétní integration event třídou podle `event_type` hlavičky. Toto je jediné místo v subscriberu, kde se „dotýkáte“ formátu publishera. Změny v doménovém eventu publishera vás zasáhnou jen zde. Zbytek kódu pracuje s vaší vlastní třídou.

Vzor má jméno. Dvojice „vlastní DTO plus překladová vrstva na hranici“ je [Anti-Corruption Layer](/context-mapping#acl) z Context Mappingu, jen realizovaný přes serializer místo přes klientskou třídu. Ostatní vztahy z Context Map mají v microservices architektuře stejně přímočarý protějšek:

| Vztah z Context Map | Mechanismus mezi services |
|---|---|
| Anti-Corruption Layer | Vlastní integration event DTO a serializer, který na něj překládá cizí payload |
| Open Host Service + Published Language | Veřejné API nebo publikované schéma událostí se zpětnou kompatibilitou |
| Conformist | Přebírání schématu publishera beze změny, typicky u externí služby |
| Separate Ways | Žádná integrace – data se pořizují znovu, protože překlad by stál víc |

:::code{language="php" filename="billing-svc/src/Infrastructure/Messaging/IntegrationEventSerializer.php" highlights="22,23,24,25"}
<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Messaging;

use App\Billing\Application\IntegrationEvent\OrderCancelledReceived;
use App\Billing\Application\IntegrationEvent\OrderPlacedReceived;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final readonly class IntegrationEventSerializer implements SerializerInterface
{
    /**
     * Mapping event_type hlavičky -> integration event třída.
     * Když publisher přidá nový event_type, doplníme tu řádek;
     * dokud nedoplníme, zpráva spadne do dead-letter exchange.
     *
     * Selhání dekódování MUSÍ být MessageDecodingFailedException.
     * Jen tu receiver zachytí a pošle do failure pipeline; jiná výjimka
     * shodí worker, zpráva zůstane neackovaná a po restartu se vrátí
     * znovu - nekonečná smyčka nad jedinou vadnou zprávou.
     */
    private const TYPE_MAP = [
        'ordering.placed' => OrderPlacedReceived::class,
        'ordering.cancelled' => OrderCancelledReceived::class,
    ];

    public function decode(array $encodedEnvelope): Envelope
    {
        $headers = $encodedEnvelope['headers'] ?? [];
        $eventType = $headers['event_type']
            ?? throw new MessageDecodingFailedException('Missing event_type header');

        $targetClass = self::TYPE_MAP[$eventType] ?? throw new MessageDecodingFailedException(
            sprintf('Unknown event_type: %s', $eventType)
        );

        try {
            $payload = json_decode($encodedEnvelope['body'], true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MessageDecodingFailedException('Body is not valid JSON', 0, $e);
        }

        // Mapping payloadu z publishera na náš subscriber-side DTO.
        // Každý typ eventu nese jiná pole, proto se tu větví. Defenzivní –
        // žádná pole z payloadu, která bychom nepoužívali.
        $message = match ($targetClass) {
            OrderPlacedReceived::class => new OrderPlacedReceived(
                eventId: $payload['eventId'],
                occurredAt: $payload['occurredAt'],
                orderId: $payload['orderId'],
                customerId: $payload['customerId'],
                totalAmountCents: $payload['totalAmountCents'],
                currency: $payload['currency'] ?? 'EUR',
            ),
            OrderCancelledReceived::class => new OrderCancelledReceived(
                eventId: $payload['eventId'],
                occurredAt: $payload['occurredAt'],
                orderId: $payload['orderId'],
                reason: $payload['reason'] ?? null,
            ),
        };

        return new Envelope($message);
    }

    public function encode(Envelope $envelope): array
    {
        // Subscriber encode neprovádí – to dělá publisher na své straně.
        // Důsledek pro konfiguraci: transport s tímto serializerem nesmí mít
        // retry_strategy, protože retry posílá envelope zpět přes encode().
        // Selhání proto řeší failure_transport s výchozím serializerem.
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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
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
        // Rychlá zkratka pro zjevné duplikáty. Autoritativní není –
        // mezi kontrolou a zápisem může projít paralelní doručení.
        // Detail v /outbox-pattern#inbox
        if ($this->inbox->wasProcessed($event->eventId)) {
            return;
        }

        // eventId putuje do commandu. Handler commandu zapíše záznam do inboxu
        // ve stejné transakci jako fakturu; o transakci se stará
        // doctrine_transaction middleware na command.bus. Unikátní index nad
        // eventId duplikát odmítne a celá transakce se vrátí zpět.
        try {
            $this->commandBus->dispatch(new CreateInvoiceForOrder(
                eventId: $event->eventId,
                orderId: $event->orderId,
                customerId: $event->customerId,
                amountCents: $event->totalAmountCents,
                currency: $event->currency,
            ));
        } catch (HandlerFailedException $e) {
            // Bez tohohle catche skončí legitimní duplikát ve failed transportu.
            // Rollback je správný, ale zprávu je potřeba potvrdit, ne opakovat.
            foreach ($e->getWrappedExceptions() as $wrapped) {
                if (!$wrapped instanceof UniqueConstraintViolationException) {
                    throw $e;
                }
            }
        }
    }
}
:::

Tímto vzorem dosáhneme čtyř důležitých vlastností:

- **Žádný shared code mezi services** – billing-svc nemá ve svém `composer.json` žádný balíček, který by definoval třídy ordering-svc.
- **Verzování payloadu** – publisher přidá pole, subscriber pole zatím nezná, no-op. Žádný coordinated release.
- **Idempotence** – duplicitní doručení (failover brokeru, restart workera) se neprojeví. *Inbox* tabulka v billing-svc drží zpracované `eventId`. Kontrola v handleru integrační události je jen zkratka pro zjevné duplikáty; autoritativní je unikátní index nad `eventId` a zápis do inboxu ve stejné transakci jako doménová změna. Kontrola oddělená od zápisu paralelní duplikát nezastaví. Odmítnutý duplikát ale handler musí zachytit a zprávu potvrdit; jinak skončí ve failure transportu, přestože se systém zachoval správně.
- **Testovatelnost** – handler se testuje s `new OrderPlacedReceived(...)`, bez síťového stacku.

### Ekonomika microservices v PHP {#php-ekonomika-heading}

Literatura o microservices vznikla v prostředí dlouhoběžících runtime: JVM, .NET, Node. PHP v klasickém režimu php-fpm počítá jinak a několik doporučení tím posouvá. Ta část kalkulace v knihách o microservices chybí, protože ji jejich autoři nepotřebovali.

Aplikace je *shared-nothing*: bootstrapuje se při každém requestu a mezi requesty si nedrží ani in-process cache, ani connection pool. Každá extrahovaná služba tedy platí vlastní bootstrap na vlastním requestu. Samo o sobě je to drobnost, která se ale násobí počtem skoků v řetězci.

Podstatnější je chování synchronního volání. Node nebo Go čekají na odpověď neblokujícím I/O prakticky zadarmo. Naproti tomu php-fpm child zůstává po celou dobu round-tripu obsazený a nikoho jiného neobslouží. Počet childů je pevný, takže fan-out do tří služeb spotřebuje trojnásobek konkurenční kapacity volajícího. Anti-vzor „synchronní orchestrace všeho přes REST“ ze sekce 19.10 je proto v PHP dražší než v prostředí, ze kterého pochází zdrojová literatura.

Dvě mitigace:

- **Souběžné requesty přes `HttpClient`.** Symfony `HttpClient` streamuje odpovědi, takže tři volání lze poslat najednou a čekat na ně současně. Sériové `->getContent()` třikrát za sebou sčítá latence zbytečně.
- **Worker módy.** FrankenPHP, RoadRunner nebo Swoole drží aplikaci v paměti mezi requesty. Bootstrap odpadá a cena synchronního volání klesá. Daň: služby s request-scoped stavem musí implementovat `Symfony\Contracts\Service\ResetInterface`, jinak stav prosákne do dalšího requestu.

Opačným směrem působí to, že nasazovací jednotka je v PHP levná. Není co kompilovat, není warm-up JVM, image se sestaví za minuty. Technicky extrahovat službu je v PHP snazší než v Javě. Celý náklad tedy leží v provozu. Hlavní tezi této kapitoly to spíš posiluje.

A provoz má v Symfony konkrétní podobu. Každá služba, která konzumuje zprávy, potřebuje vlastní sadu workerů: `messenger:consume` pod Supervisorem nebo systemd, `--memory-limit` a `--time-limit` proti narůstající spotřebě paměti a `messenger:stop-workers` v každém deploy skriptu. Pět služeb znamená pět takových konfigurací a pět míst, kde se dá na restart workerů po deployi zapomenout. Messenger na provoz nabízí i dva novější přepínače: `--keepalive` brání předčasnému redelivery u dlouho běžících handlerů a `--fetch-size` snižuje počet dotazů do transportu při dávkovém zpracování.

## 19.09 Postupná migrace monolit → microservices {#migrace}

Většinu reálných systémů nepostavíte jako microservices na zelené louce. Postavíte je jako monolit, ten doroste do bolesti, a pak se zeptáte, kterou část máte rozdělit. Mottem sekce je heslo Sama Newmana z *Building Microservices, 2nd ed.*: **„don't do a big-bang rewrite“**. Velká přepisovací migrace selhává mnohem častěji, než se plánuje.

### Strangler Fig pattern {#strangler-fig-heading}

Strangler Fig (Martin Fowler, 2004; pod tímto názvem od roku 2019) nahrazuje systém po částech místo najednou. Před monolit se postaví fasáda (proxy, edge gateway), která postupně přesměrovává provoz jednotlivých Bounded Contextů na nově extrahované services. Funkcionalita během přechodu existuje dvakrát a přepíná se na úrovni routingu, takže rollback zůstává kdykoli možný. Po stabilizaci se mrtvý kód v monolithu smaže a iteruje se dalším kontextem.

Fowlerova současná verze textu přidává pojem *transitional architecture*: fasáda, dvojí zápis a routovací vrstva jsou dočasné lešení, které do cílového stavu nepatří a po migraci se bourá. Kdo si ho ponechá „pro jistotu“, zůstane s trvale složitější topologií, než jakou potřebuje.

Plný výklad vzoru je v kapitole [Migrace z CRUD](/migrace-z-crud#strangler-fig): princip koexistence staré a nové části, struktura projektu i srovnání s přímým přepisem. Tato sekce se soustředí na to, co je specifické pro extrakci do samostatných services: pořadí fází a kritéria, kdy zastavit.

### Tři fáze migrace v praxi {#3-faze-heading}

Doporučená postupná cesta pro Symfony tým, který dnes má monolit bez explicitních hranic:

#### Fáze 1: Modular monolith (3–12 měsíců) {#faze-1-heading}

**Cíl:** identifikovat Bounded Contexty a vynutit jejich hranice *uvnitř* jednoho deployu. Nemigruje se nikam – refaktoruje se struktura.

- Provést [Event Storming](/event-storming) nebo Domain Storytelling s doménovými experty. Identifikovat BC.
- Reorganizovat `src/` do `src/<BC>/` struktury. Každý BC má vlastní Domain / Application / Infrastructure.
- Zavést phparkitect pravidla a v CI je vynucovat. Bez tohoto kroku jsou hranice fiktivní.
- Cross-BC integraci převést na Domain Events + Symfony Messenger (sync transport zatím).
- Identifikovat schema ownership, tedy která tabulka patří kterému BC. Pokud jedna tabulka patří dvěma BC, máte tam buď Shared Kernel (řídký), nebo nesprávné hranice.

#### Fáze 2: Strangler Fig – první extrakce (1–3 měsíce na první service) {#faze-2-heading}

**Cíl:** vytáhnout první BC do samostatné service. Vyberte ten, který má největší důvod (typicky read-heavy modul s odlišným profilem zátěže nebo modul s compliance isolation).

- Postavte fasádu (Symfony API gateway, nginx routing, AWS API Gateway) před monolit.
- Postavte nový Symfony projekt jako samostatnou service. Zkopírujte (nikdy ne `git mv`) kód cílového BC z monolithu.
- Migrace dat: postupně replikovat tabulky cílového BC do nové DB. Nastane období dual-write, kdy píší monolit i nová service. Postupně přepnout čtecí provoz. Dual-write je zde vědomá výjimka: [kapitola o Outboxu](/outbox-pattern#dual-write) ho popisuje jako problém, protože zápis do dvou úložišť není atomický. Při migraci je únosný jako řízený a časově omezený stav, s jedním úložištěm jako zdrojem pravdy a s pravidelnou rekonciliací, která rozdíly najde a srovná. Trvalé řešení z něj nedělejte.
- Cross-BC eventy nahradit AMQP transportem v Messenger. Subscriber side má vlastní integration event DTO (sekce 19.08).
- Po stabilizaci smažte zbytky cílového BC z monolithu.

#### Fáze 3: Iterace nebo zastavení {#faze-3-heading}

**Cíl:** rozhodnout, zda pokračovat dalším BC, nebo zastavit a žít s hybridní architekturou (monolith + 1–2 services). Hybrid je **legitimní cíl**, ne dočasná fáze. Mnoho úspěšných systémů nikdy nedojede do plně microservices architektury, protože k tomu nemají důvod.

- Změřit, zda první extrakce splnila očekávání (lepší škálování, rychlejší release, lepší vlastnictví). Pokud ne, zastavit a analyzovat proč.
- Pokračovat dalším BC, který má jasné odůvodnění.
- Investovat průběžně do operační platformy. Bez ní každá další extrakce zhoršuje produktivitu.

:::callout{type="anti"}
### Nikdy: big-bang rewrite {#migrace-warning-heading}

Přepis na zelené louce s plánovaným přepnutím „za 18 měsíců“ končí zpravidla nedokončeným novým systémem vedle stárnoucího monolithu. Typický průběh tohoto selhání a důvody, proč k němu dochází, rozebírá [Migrace z CRUD](/migrace-z-crud#big-bang-warning-heading).
:::

## 19.10 Anti-vzory v microservices a DDD {#antivzory}

Pět nejčastějších anti-vzorů, na které tým narazí při kombinaci DDD a microservices. Každý má konkrétní symptom a konkrétní opravu.

### 1. Microservices first (před identifikací BC) {#antivzor-1-heading}

**Symptom:** tým rozdělil monolit do services dříve, než provedl Event Storming nebo Domain Storytelling. Hranice services odpovídají technickým vrstvám (auth-svc, user-svc, db-svc) nebo CRUD entitám (order-svc, customer-svc, product-svc), ne doménovým kontextům.

**Důsledek:** doménový proces musí pro vyřízení projít napříč pěti až deseti services. Synchronní coupling všude. Je to distributed monolith z definice.

**Oprava:** zastavit, identifikovat skutečné BC přes Event Storming, mapovat aktuální services na cílové BC. Často zjistíte, že 3 stávající services patří do jednoho BC. Sloučit je do modular monolithu a teprve pak řešit, zda BC má dostat vlastní service.

### 2. Sdílená databáze napříč services {#antivzor-2-heading}

**Symptom:** service A i service B čtou (nebo dokonce zapisují) do stejných tabulek. „Jednoduchá integrace“, „atomicita“, „není čas dělat to správně“.

**Důsledek:** jakákoli změna schématu zlomí všechny services, které tabulku konzumují. Žádná service nemá vlastnictví dat. Refactoring databáze je migrační utrpení.

**Oprava:** data dělit podle BC. Cross-BC čtení nahradit API call (sync) nebo replikací přes eventy (async, eventually consistent). Žádný cross-BC join na DB úrovni. Kanonickou podobu anti-vzoru s ukázkami kódu popisují [Anti-vzory](/anti-vzory#sdilena-databaze).

### 3. Synchronní orchestrace všeho přes REST {#antivzor-3-heading}

**Symptom:** každá doménová operace je řetězec sync HTTP volání. Vyřízení objednávky: ordering volá payment, payment volá fraud-detection, fraud-detection volá ai-scoring, ai-scoring volá customer, ... Jeden user request = 12 vnořených HTTP volání.

**Důsledek:** kumulativní latence v sekundách, availability v součinu, retry storm při výpadcích.

**Oprava:** aplikovat *async-first* pravidlo (sekce 19.05). State changes přes eventy, validační lookups přes sync s cache, žádné synchronní side-effecty (sync save) přes hranici service. Pro koordinaci procesů použít [ságu](/sagy-a-process-managery).

### 4. Jeden deployment artefakt pro N services {#antivzor-4-heading}

**Symptom:** CI/CD pipeline sestavuje všechny services společně. Release schedule je centralizovaný („máme deployment train“, „release window v úterý“). Změnu v jedné service nelze nasadit bez ostatních.

**Důsledek:** všechny services musí být kompatibilní v každém okamžiku. Žádné přepínání funkcí, žádný gradual rollout, žádný rychlý rollback. Coupled deploy je definující znak distributed monolithu.

**Oprava:** každá service má vlastní pipeline, vlastní release cyklus, vlastní rollback. Cross-service kompatibilita se řeší verzováním schématu a verzováním integration eventů (subscriber přijímá starší i novější verzi).

### 5. Nano-services {#antivzor-5-heading}

**Symptom:** service o 50 řádcích kódu, vlastní deploy, vlastní DB. „Single responsibility principle“ aplikované na nasazovací jednotku. Sto services pro produkt s 30 inženýry.

**Důsledek:** operační režie 100x. Každá service potřebuje monitoring, alerty, CI/CD, runtime, znalostní bázi, pohotovostní rotaci. Tým 30 lidí má na service 0,3 inženýra. Nikdo nemá hluboké vlastnictví, všichni „udržují“.

**Oprava:** agregovat blízce příbuzné services do jedné, typicky sloučit do BC, do kterého patří. „Microservice“ znamená *samostatně nasazovatelnou jednotku*, ne „malou service“. Velikost je vedlejší. Sam Newman v *Building Microservices, 2nd ed.* staví hranice services na information hidingu, provázanosti a soudržnosti, tedy na doméně, ne na technické gymnastice.

Obecnější rozbor anti-vzorů v DDD (nejen microservices) najdete v [kapitole 21 – Anti-vzory](/anti-vzory).

## 19.11 Shrnutí {#summary}

Vztah mezi Bounded Contextem a microservice nelze redukovat na jednu rovnici. Bounded Context je **logická hranice modelu**, microservice je **fyzická hranice deploymentu**. Mapování mezi nimi je 1:1, 1:N nebo N:1 a každá varianta má kontext, ve kterém je správná. Slogan „BC = microservice“ je užitečný jako výchozí hypotéza, ne jako architektonický příkaz.

Rozhodnutí, které tato kapitola nese, je jediné: kde vede hranice mezi procesy.
Pro tým do třiceti lidí ji nejlevněji uhlídá modular monolith. Hranice mezi BC vynutí
phparkitect stejně tvrdě, jen za zlomek operační složitosti. Samostatnou
service si BC zaslouží tehdy, když má vlastní stream-aligned tým, vlastní data,
nezávislý release cyklus, jiné potřeby škálování nebo compliance isolation; tři a méně
zaškrtnutých bodů znamená zůstat v monolitu.

Když už hranice vede přes síť, drží ji tři pravidla. Data mají vlastníka, ne
spolubydlícího: jedno schéma se dvěma zapisovateli je porucha, dotaz přes hranici řeší
API composition, read model plněný událostmi nebo analytický sklad. Komunikace je
async-first. Sync zůstává jen pro queries v request flow a blokující validace, protože
tight temporal coupling ubere z microservices přesně to, kvůli čemu vznikly. A konzistenci
napříč službami zajišťuje [saga s kompenzacemi](/sagy-a-process-managery), ne 2PC, které
v tomhle stacku není použitelné.

Symfony Messenger obojí svět obslouží: sync transport uvnitř monolitu, AMQP transport
s Outboxem mezi službami. Jedno pravidlo je přitom nepřekročitelné: publisher a subscriber
nesdílejí PHP třídu události, subscriber má vlastní integration event DTO. Sdílená knihovna
s doménovými typy je totiž první ze tří příznaků distributed monolithu, vedle sdílené
databáze a synchronních volání všude. Ten je horší než monolit, se kterým jste začínali.

Cesta ven proto vede přes Strangler Fig: jeden BC v čase, s fasádou a obdobím dual-write.
Big-bang rewrite zpravidla selže. A pokud nemáte orchestrátor, distributed tracing, service
discovery a CI/CD per service, je odpověď na otázku po hranici stejně modular monolith.
Microservices jsou především operační problém.

Microservice je optimalizace, kterou si zasloužíte, až když ji potřebujete.

## 19.12 Další četba {#further-reading}

- [Sam Newman – *Building Microservices, 2nd ed.* (O'Reilly, 2021)](https://samnewman.io/books/building_microservices_2nd_edition/). Kanonická kniha o microservices. Kapitoly 1–2 pro hranice services, kapitola 3 pro monolith-first strategii i pro migraci, kapitoly 4–6 pro integraci.
- [Chris Richardson – *Microservices Patterns* (Manning, 2018)](https://microservices.io/book). Praktická kniha plná konkrétních patternů. Kapitola 2 (decomposition by business capability), kapitola 3 (interprocess communication), kapitola 4 (sagas), kapitola 13 (refaktoring monolithu).
- [Vaughn Vernon – *Implementing Domain-Driven Design* (Addison-Wesley, 2013)](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577). Kapitola 2 pro Bounded Context jako jazykovou hranici, kapitola 3 pro Context Maps, kapitola 8 pro Domain Events napříč BC.
- [James Lewis & Martin Fowler – *Microservices* (2014)](https://martinfowler.com/articles/microservices.html). Text, který pojem ustavil. Devět charakteristik architektury a formulace o „přirozené korelaci“ mezi hranicí služby a hranicí kontextu, ze které vznikl slogan rozebíraný v sekci 19.01.
- [Martin Fowler – *MonolithFirst* (2015)](https://martinfowler.com/bliki/MonolithFirst.html). Krátký esej, který formuloval doporučení nezačínat na zelené louce s microservices.
- [Stefan Tilkov – *Don't start with a monolith* (2015)](https://martinfowler.com/articles/dont-start-monolith.html). Protipozice publikovaná na Fowlerově vlastním webu šest dní po *MonolithFirst*. Čtěte obojí, ne jen jedno.
- [Zhamak Dehghani – *How to break a Monolith into Microservices* (2018)](https://martinfowler.com/articles/break-monolith-into-microservices.html). Heslo „macro first, then micro“ a nejčastější chyba migrace: postavit novou službu a nezrušit původní cestu v monolitu.
- [Martin Fowler – *What do you mean by „Event-Driven“?* (2017)](https://martinfowler.com/articles/201701-event-driven.html). Event notification, event-carried state transfer, event sourcing a CQRS jako čtyři různé věci pod jedním názvem.
- [Werner Vogels – *Monoliths are not dinosaurs* (2023)](https://www.allthingsdistributed.com/2023/05/monoliths-are-not-dinosaurs.html). Rámec k případu Prime Video: architektura se reviduje, když se změní zátěžový profil.
- [Martin Fowler – *Strangler Fig Application* (2004, přejmenováno 2019)](https://martinfowler.com/bliki/StranglerFigApplication.html). Originální popis migrační strategie použitelný pro každý legacy systém. Současná verze textu přidává *transitional architecture*, tedy dočasné lešení, které se po migraci bourá.
- [Matthew Skelton & Manuel Pais – *Team Topologies* (IT Revolution, 2019)](https://www.amazon.com/Team-Topologies-Organizing-Business-Technology/dp/1942788819). Stream-aligned teams, enabling teams, complicated subsystem teams, platform teams. Klíč k tomu, aby microservices měly smysl organizačně.
- [Martin Fowler – *Microservice Trade-Offs* (2015)](https://martinfowler.com/articles/microservice-trade-offs.html). Co získáte a co ztrácíte.

:::faq{}
- question: Kolik je správná velikost jednoho microservice?
  answer: 'Velikost není primární kritérium, tím je autonomní deployovatelnost. Microservice je správně velký tehdy, když ho jeden stream-aligned tým dokáže měnit, nasazovat a provozovat samostatně. To může být 500 řádků kódu nebo 50 000. Sam Newman v <em>Building Microservices, 2nd ed.</em> doporučuje, aby velikost vznikala z domény (jeden Bounded Context nebo logická část), ne z technického ideálu „malé service“. Detail v <a href="#bc-jedna-service">sekci 19.02</a> a v anti-vzoru <a href="#antivzor-5-heading">nano-services</a>.'
- question: Můžu mít 2 Bounded Contexty v jedné microservice?
  answer: 'Ano, a často je to správné rozhodnutí. Je to definice <strong>modular monolithu</strong> nebo malého „mikro-monolithu“. Pokud dva BC sdílejí stream-aligned tým a podobné potřeby škálování, jejich provozování ve dvou samostatných services je operační overhead bez benefitu. Hlavní podmínka: hranice mezi BC <em>uvnitř</em> service musí být vynucená kódem (typicky phparkitect pravidly). Pokud se obejdou, máte nestrukturovaný monolit, ne modular monolith. Detail v <a href="#modular-monolith">sekci 19.03</a>.'
- question: Kdy přejít z monolithu na microservices?
  answer: 'Když máte konkrétní bolest, kterou microservices skutečně řeší: typicky odlišné potřeby škálování jednoho modulu, různé compliance režimy nebo organizační oddělení (různé stream-aligned týmy s různými release cykly). Bez konkrétní bolesti je přechod čistá ztráta. Získáte operační složitost a žádnou hodnotu navíc. Postup vždy přes Strangler Fig (postupná extrakce 1 BC v čase), nikdy big-bang rewrite. Detail v <a href="#migrace">sekci 19.09</a>.'
- question: Co je BFF (Backend For Frontend) a kam patří v DDD?
  answer: 'BFF je vzor, ve kterém má každý frontend (web, mobile, partner API) <em>vlastní</em> backend agregátor, který volá downstream microservices a sestavuje view-model přesně přizpůsobený danému klientovi. V DDD terminologii je to typicky <strong>Open Host Service</strong> (OHS) s <strong>Published Language</strong>, doplněný Anti-Corruption Layerem proti volaným službám, viz <a href="/context-mapping#ohs">Open Host Service</a> a <a href="/context-mapping#published-language">Published Language</a>. BFF nepatří do žádného doménového Bounded Contextu; je to integrační vrstva, vlastní BC sám o sobě (typicky „Web Frontend BC“).'
- question: GraphQL Federation jako náhrada microservices integrace?
  answer: 'GraphQL Federation umožňuje, aby více services vystavilo svou část schématu a aby gateway (Apollo Router) je sloučila do jednoho schema z pohledu klienta. Pro <em>read</em> operace přes microservices odstíní klienta od fyzického rozdělení. Pro <em>write</em> operace federation neřeší distribuované transakce; pořád potřebujete <a href="/sagy-a-process-managery">ságu</a>. Doporučení: federation jako read fasáda, nikoli jako náhrada eventem řízené architektury.'
- question: Které service vlastní data o customerovi napříč BC?
  answer: 'Žádná „centrální“ Customer service. Každý Bounded Context má vlastní pohled na customer, který odpovídá jeho jazyku a kontextu. Ordering vidí <code>Customer</code> jako adresu pro doručení a platební preferenci, Billing jako fakturačního partnera s VAT IDs, Support jako entitu s historií ticketů. Stejné <code>customerId</code>, různé modely. Martin Fowler pro tuto situaci v bliki <em>BoundedContext</em> (2014) používá označení <em>polysemic concept</em>: jeden pojem s odlišným významem v každém kontextu a s explicitním mapováním mezi nimi. Pokud se rozhodnete jeden BC označit za „source of truth“ pro identitu zákazníka, ostatní BC od něj přebírají jen <code>customerId</code> a vlastní atributy si modelují samy. Detail v <a href="/context-mapping">Context Mappingu</a>.'
:::
