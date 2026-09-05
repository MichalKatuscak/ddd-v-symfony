---
route: event_sourcing
path: /event-sourcing
title: Event Sourcing v DDD a Symfony
page_title: "Event Sourcing v DDD a Symfony | DDD Symfony"
meta_description: "Event Sourcing v DDD a Symfony 8: Event Store, projekce, snapshoty, upcasting, Outbox pattern a praktická řešení idempotence i rebuild projekcí."
meta_keywords: "Event Sourcing, DDD, Domain-Driven Design, Symfony, Event Store, Aggregate, Projection, Outbox pattern, Snapshot, CQRS, doménové události, PHP, immutabilita, event stream, Symfony Messenger, idempotence, eventual consistency, upcasting, event versioning, projection rebuild, dual-write problem"
og_type: article
published: "2025-04-24"
modified: "2026-09-05"
breadcrumb_name: Event Sourcing
schema_type: TechArticle
schema_headline: "Event Sourcing v DDD a Symfony"
chapter_number: "13"
category: Vzory
deck: 'Event Sourcing v kontextu Domain-Driven Design a Symfony – implementace Event Store, event-sourcovaných agregátů, projekcí, Outbox patternu, snapshottingu a verzování událostí. Včetně praktických problémů: idempotence projektorů, rebuild projekcí a eventual consistency.'
reading_time: 45
difficulty: 4
github_examples: Chapter06_EventSourcing
---

## 13.01 Co je Event Sourcing? {#co-je-event-sourcing}

Tradiční CRUD persistence má slepou skvrnu: při každé změně přepíše předchozí stav a veškerá
historie se nenávratně ztrácí. Event Sourcing (ES) ukládá stav systému jako **sekvenci
neměnných událostí**, jež k němu
vedly [[1]](https://martinfowler.com/eaaDev/EventSourcing.html).
Každá změna stavu domény je zaznamenána jako samostatná, pojmenovaná událost se svými daty.
Aktuální stav agregátu pak vzniká *přehráním* (replay) těchto událostí od počátku.

Fowler sám princip formuluje jako *„Event Sourcing ensures that all changes to application
state are stored as a sequence of events“* [[1]](https://martinfowler.com/eaaDev/EventSourcing.html).
Namísto jediného řádku v databázové tabulce, který je při každé změně přepisován, existuje append-only log
všech událostí, jež kdy na agregátu nastaly.

Fowlerova formulace z roku 2005 je široká. Sedne na ni auditní log i stream processing.
Verraes ji o čtrnáct let později zúžil [[3]](https://verraes.net/2019/08/eventsourcing-state-from-events-vs-events-as-state/).
Systém je podle něj event-sourced tehdy, když jediným zdrojem pravdy je uložená historie
událostí. A k tomu přidává podmínku, kterou Fowler nemá: z téže historie se musí vynucovat
pravidla pro nové události. Tím se Event Sourcing odděluje od pouhého logování změn – log,
ze kterého se nikdo nerozhoduje, je archiv, ne zdroj pravdy.

### Porovnání s tradiční CRUD persistencí

V klasickém CRUD přístupu drží tabulka pouze aktuální stav entity – jakmile se hodnota změní,
předchozí je pryč. Event Sourcing zapisuje každou změnu jako nový řádek event logu, takže
žádná informace se nikdy nepřepisuje ani nemaže.

:::callout{type="pattern"}
### CRUD vs. Event Sourcing – přehled {#crud-vs-es-heading}

| Vlastnost | CRUD (tradiční) | Event Sourcing |
|---|---|---|
| Co se ukládá | Aktuální stav entity | Sekvence událostí (změn) |
| Auditní log | Vyžaduje další implementaci | Zabudován ve struktuře |
| Obnova stavu | Přímé čtení z tabulky | Replay event streamu |
| Temporální dotazy | Obtížné / nemožné | Přirozené (replay do libovolného bodu) |
| Složitost implementace | Nízká až střední | Vysoká |
| Výkon čtení | Závisí na schématu (rychlé pro jednoduché tabulky, pomalé pro mnoho JOINů) | Závisí na strategii (rychlé přes denormalizované projekce, pomalé bez snapshotů) |
:::

:::callout{type="note"}
### Pojmy Event Sourcingu: {#es-pojmy-heading}

- **Event (Událost)** – Neměnný záznam o tom, co se v doméně přihodilo, vyjádřený v minulém čase (např. `OrderPlaced`, `PaymentReceived`). Obsahuje všechna data potřebná k rekonstrukci změny stavu.
- **Event Store** – Specializované append-only úložiště pro události. Události se do něj pouze přidávají. Každá událost patří do event streamu konkrétního agregátu.
- **Aggregate ([Agregát](/zakladni-koncepty#aggregates))** – V kontextu ES je agregát rekonstruován přehráním všech událostí ze svého event streamu. Každá mutace stavu agregátu produkuje novou událost místo přímé modifikace atributů.
- **Projection (Projekce)** – Read model sestavený z událostí. Projekce transformují event stream do podoby vhodné pro konkrétní dotazy (query) – například denormalizovaná tabulka pro přehled objednávek.
- **Snapshot** – Periodicky ukládaný snímek aktuálního stavu agregátu, který slouží jako zkratka při replay. Umožňuje přehrát pouze události novější než poslední snapshot místo celého event streamu od počátku.
:::

## 13.02 Vztah k CQRS {#vztah-k-cqrs}

Event Sourcing a [CQRS](/cqrs) jsou dva samostatné
vzory [[2]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf).
**Nejsou totéž** – lze aplikovat CQRS bez Event Sourcingu a naopak ES bez CQRS. V doménově orientovaných systémech
aplikací se ale obvykle objevují společně.

Důvod je technický: Event Sourcing produkuje události jako základní artefakt
persistence a CQRS potřebuje způsob, jak aktualizovat read modely při každé změně write strany.
Události tuto propagaci pokrývají bez další infrastruktury – write side uloží událost do Event Store,
read side ji přečte a aktualizuje projekci.

Symetrie mezi oběma vzory ale neplatí. CQRS nad klasickou ORM persistencí je běžná
konfigurace bez zvláštních nároků. Event Sourcing bez CQRS možný je, jenže write model
se pak nedá dotazovat ničím jiným než čtením streamu podle ID. Dotaz „vypiš všechny
uživatele se jménem Greg“ nad event streamem nesestavíte, jak upozorňuje už Young
[[2]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf). Read stranu proto
postavíte tak jako tak – a tím se dostáváte ke CQRS oklikou.

:::callout{type="note"}
### Datový tok v architektuře ES + CQRS: {#es-cqrs-tok-heading}

1. Uživatel odešle **Command** (např. `PlaceOrderCommand`).
2. Command Handler načte agregát přehráním jeho event streamu z Event Store.
3. Agregát validuje command a produkuje jednu nebo více **Domain Events**.
4. Nové události jsou uloženy do **Event Store** (append).
5. **Event Bus** (Symfony Messenger) distribuuje události odběratelům.
6. **Projectors** přijmou události a aktualizují **Read Models**.
7. Uživatel následně dotazuje read model přes **Query** – čte z optimalizované projekce.
:::

:::callout{type="pattern"}
### Zásadní rozdíl mezi ES a CQRS {#es-cqrs-rozdil-heading}

**CQRS** odděluje zápis od čtení – jde o organizační vzor zodpovědností.
**Event Sourcing** řeší něco jiného: jak stav fyzicky ukládat.
Při jejich kombinaci ES zásobuje CQRS read side daty – každá událost o změně je současně
vstupem pro aktualizaci projekcí.
:::

## 13.03 Kdy použít Event Sourcing {#kdy-pouzit}

Event Sourcing přidává konkrétní možnosti – auditní log, replay, temporální dotazy – výměnou
za vyšší složitost infrastruktury i kódu. Před zavedením stojí za úvahu,
zda v daném kontextu přínosy převažují nad náklady na implementaci a provoz. Tato sekce dává rozhodovací rámec;
zbytek kapitoly rozebírá implementaci.

### Vhodné případy užití

- **Auditní log jako doménový požadavek** – Finanční systémy, zdravotnické záznamy nebo jakákoli doména, kde je zákonná povinnost uchovávat kompletní historii změn. Auditní log v ES vychází přímo z formátu úložiště – nepotřebuje samostatnou implementaci.
- **Komplexní doménová logika s bohatými stavovými přechody** – Agregáty procházejí mnoha stavy, každý přechod má svou sémantiku a musí být rekonstruovatelný. Typicky: objednávkové systémy, workflow enginy, bankovní transakce.
- **Temporální dotazy** – Potřeba „přehrát“ stav systému k libovolnému bodu v minulosti (debugging, analýza, „what-if“ scénáře). U ES stačí replay eventů do daného timestampu.
- **Event-driven integrace** – Systém produkuje události, které konzumují jiné bounded contexts nebo externí systémy. ES zajišťuje, že se žádná událost neztratí. Ven se ale publikuje jen vybraná podmnožina událostí, ne interní stream agregátu – viz [Interní a publikované události](#interni-a-publikovane-udalosti).
- **CQRS s vysokou čtecí zátěží** – ES umožňuje vybudovat libovolný počet optimalizovaných read modelů z jednoho event streamu, aniž by bylo nutné měnit write model.

### Nevhodné případy užití

- **Jednoduché CRUD aplikace** – Pokud doménová logika spočívá v základních operacích Create/Read/Update/Delete bez složitých stavových přechodů, ES přináší jen zbytečnou složitost.
- **Systémy orientované převážně na reporting** – Pokud je primárním požadavkem rychlé čtení a agregace dat (BI, analytics), jsou vhodnější klasická DW řešení nebo OLAP databáze.
- **Prototypy a MVP** – Rychlá validace produktového nápadu nepotřebuje složitou infrastrukturu. ES lze přidat do zralého systému inkrementálně, pokud se ukáže potřeba – viz [Migrace z CRUD](/migrace-z-crud).
- **Týmy bez zkušeností s ES** – Implementace Event Sourcingu bez předchozí zkušenosti přináší vysoké riziko chyb v kritické infrastruktuře (Event Store, serializace, versioning). Začíná se typicky menším bounded contextem jako experimentem.

:::callout{type="warn"}
### Varování: Event Sourcing výrazně zvyšuje složitost systému {#es-warning-heading}

Event Sourcing CRUD nenahrazuje. Cenu zaplatíte na všech úrovních:
**infrastruktura** (Event Store, event bus, snapshot store),
**doménový model** (apply metody, immutabilita událostí, verzování schémat),
**testování** ([given/when/then scénáře](/testovani-ddd) s event streamy) a
**provoz** (migrace schémat událostí, rebuildy projekcí, monitoring lag asynchronních
projektorů). Podrobněji o výkonnostních dopadech pojednává kapitola
[Výkonnostní aspekty](/vykonnostni-aspekty).

Event Sourcing se nenasazuje paušálně na celou aplikaci. V DDD se ES nasazuje
**selektivně na bounded contexts**, kde se vrátí investice – typicky Core Domain
s komplexní doménovou logikou. Ostatní kontexty mohou nadále používat klasickou CRUD persistenci.
Časté chyby při zavádění ES shrnuje kapitola [Anti-vzory](/anti-vzory).
:::

### Broker není Event Store {#broker-neni-event-store}

Kafka, RabbitMQ ani Redis Streams roli Event Store nezastanou, i když se v nich události
také objevují v pořadí. Dudycz uvádí tři technické důvody
[[4]](https://event-driven.io/en/event_streaming_is_not_event_sourcing/). Brokery neumí
optimistic concurrency na úrovni streamu, takže nemají čím ochránit invariant agregátu
proti souběžnému zápisu. Čtení jednoho streamu za účelem rekonstrukce agregátu je v nich
buď nespolehlivé, nebo drahé. A retenční model je stavěný na průtok, ne na trvalé uložení –
data po nastavené době mizí, což je u zdroje pravdy nepřijatelné.

Rozdělení rolí je tedy jednoznačné: Event Store drží historii a vynucuje nad ní pravidla,
broker ji rozváží konzumentům. Zbytek kapitoly proto používá Symfony Messenger a RabbitMQ
výhradně jako přepravu, nikdy jako úložiště.

### Hotové knihovny, nebo vlastní store? {#hotove-knihovny-heading}

Padne-li rozhodnutí pro Event Sourcing, zbývá volba implementace. PHP ekosystém nabízí
několik knihoven s odlišnou váhou i filozofií. Následující přehled popisuje stav k září
2026 a stárne rychleji než zbytek kapitoly – aktuální kondici balíčku si před volbou
ověřte na Packagistu.

- **EventSauce** – malé jádro, srozumitelná dokumentace, žádná vazba na framework. Doctrine repozitář zpráv i outbox dodává v samostatných balíčcích, takže vlastní kód píšete hlavně kolem DI a Messengeru.
- **patchlevel/event-sourcing** – jediná z uvedených knihoven s bundlem, který deklaruje podporu Symfony 8. Přináší hotové subscriptions, snapshoty, upcasting i crypto-shredding. Při preferenci hotového řešení pro Symfony je to dnes první, na co se podívat.
- **Ecotone** – ne knihovna, ale celý framework s ES, CQRS a ságami. Má výraznou filozofii postavenou na message-driven architektuře; přijímáte ji vcelku, ne po částech. Řada 2.0 je zatím v beta verzi.
- **prooph** – řada 7.x je udržovaná a používaná, doprovodný Symfony bundle se ale od roku 2024 nehnul a končí u Symfony 7. Integraci pro Symfony 8 si napíšete sami.

Broadway do tohoto seznamu už nepatří. V srpnu 2026 vyšla verze 3.0.1 označená jako
`abandoned` a repozitář byl archivován. Pro nové projekty odpadá; v existujících kódových
základnách zůstává jako zátěž k odstranění.

Vlastní minimalistický store má smysl ve třech situacích. Při učení, kdy chcete vidět
principy bez vrstvy cizích abstrakcí. Při požadavku na plnou kontrolu nad schématem
a serializací. A u malé domény s několika typy událostí, kde by knihovna byla větší než
problém. Tato kniha staví vlastní store z prvního důvodu – ukázky slouží k výuce principů,
nikoli jako náhrada prověřené knihovny v produkci.

## 13.04 Doménové události jako základ Event Sourcingu {#domenove-udalosti}

V Event Sourcingu jsou doménové události (Domain Events) zdrojem pravdy o stavu systému – nejen
notifikací o vedlejších efektech, jako je tomu u událostí v Doctrine ORM aplikaci. Tomu odpovídají
i přísnější požadavky na jejich tvar:

První dva požadavky se týkají tvaru samotné třídy. Událost je po vytvoření neměnná – veškeré properties jsou read-only, nastavené v konstruktoru. A musí jít serializovat do trvalého formátu (JSON, MessagePack…) a deserializovat zpět bez ztráty informace.

Zbylé tři míří na obsah a životní cyklus:

- schéma události se v čase vyvíjí – stará data v Event Store je třeba udržet čitelná, typicky upcastingem (transformací starých verzí na aktuální),
- název vyjadřuje fakt v minulém čase: `UserRegistered`, `OrderPlaced`, `PaymentFailed`,
- data musí být dost granulární na to, aby z události šel rekonstruovat stav bez přístupu k externím zdrojům.

:::callout{type="pattern"}
### PHP: Bázová třída DomainEvent a konkrétní třída UserRegistered {#domain-event-php-heading}

:::code{language="php" filename="src/SharedKernel/Domain/Event/DomainEvent.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Event;

use DateTimeImmutable;

/**
 * Společná bázová třída pro doménové události. Identita a čas jsou
 * public readonly vlastnosti (přímý přístup `$event->eventId`,
 * `$event->occurredAt`); serializaci do Event Store a zpět řeší metody.
 * Všechny potomky jsou immutabilní value objekty.
 */
abstract class DomainEvent
{
    public function __construct(
        /** Unikátní identifikátor události (UUID v7). */
        public readonly string $eventId,
        /** Čas vzniku události - vždy UTC. */
        public readonly DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Název události sloužící k jejímu uložení a vyhledání v Event Store.
     * Konvence: FQCN nebo krátký slug ve tvaru "user.registered".
     */
    abstract public function eventType(): string;

    /**
     * Verze schématu payloadu - klíčová pro upcasting starých událostí.
     * Nové verze události inkrementují toto číslo.
     */
    abstract public function schemaVersion(): int;

    /**
     * Serializace do pole pro uložení do Event Store.
     * Musí obsahovat všechna data potřebná k rekonstrukci stavu
     * VČETNĚ eventId a occurredAt - identita události je součástí payloadu.
     *
     * @return array<string, mixed>
     */
    abstract public function toPayload(): array;

    /**
     * Rekonstrukce události z payloadu načteného z Event Store.
     * Nesmí generovat nové UUID ani čas - obojí přebírá z payloadu.
     *
     * @param array<string, mixed> $payload
     */
    abstract public static function fromPayload(array $payload): static;
}
:::
*src/SharedKernel/Domain/Event/DomainEvent.php*

:::code{language="php" filename="src/Identity/Domain/Event/UserRegistered.php"}
<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * Událost emitovaná po úspěšné registraci uživatele.
 * Immutabilní - všechny properties jsou readonly.
 */
final class UserRegistered extends DomainEvent
{
    private function __construct(
        string $eventId,
        DateTimeImmutable $occurredAt,
        public readonly string $userId,
        public readonly string $email,
        public readonly string $fullName,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    /**
     * Named constructor pro PRVNÍ vytvoření události v doménovém kódu.
     * Pouze zde se generuje nové UUID a aktuální čas.
     */
    public static function create(string $userId, string $email, string $fullName): self
    {
        return new self(
            eventId:    (string) Uuid::v7(),
            occurredAt: new DateTimeImmutable('now', new \DateTimeZone('UTC')),
            userId:     $userId,
            email:      $email,
            fullName:   $fullName,
        );
    }

    /**
     * Rekonstrukce z Event Store: identita a čas se PŘEBÍRAJÍ z payloadu.
     * Negenerovat zde nové UUID - rozbilo by to idempotenci přes event_id.
     *
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): static
    {
        return new self(
            eventId:    $payload['eventId'],
            // Payload nenese offset, proto UTC uvádíme explicitně -
            // jinak by čas převzal default timezone serveru.
            occurredAt: new DateTimeImmutable($payload['occurredAt'], new \DateTimeZone('UTC')),
            userId:     $payload['userId'],
            email:      $payload['email'],
            fullName:   $payload['fullName'],
        );
    }

    public function eventType(): string
    {
        return 'identity.user_registered';
    }

    public function schemaVersion(): int
    {
        return 1; // při změně schématu inkrementujeme a vytvoříme upcaster
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'eventId'    => $this->eventId,
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s.u'), // vždy UTC (viz create())
            'userId'     => $this->userId,
            'email'      => $this->email,
            'fullName'   => $this->fullName,
        ];
    }
}
:::
*src/Identity/Domain/Event/UserRegistered.php*
:::

Na rozdělení `create()` / `fromPayload()` stojí celá idempotence systému. Kdyby konstruktor
generoval `eventId` a `occurredAt` při každém vytvoření instance, dostala by tatáž uložená
událost po deserializaci nové UUID a nový čas. Tracking tabulka zpracovaných událostí by
duplicitní doručení nepoznala a rebuild projekcí by pracoval s jinými časy, než jaké platily
při zápisu. Identita události proto vzniká právě jednou – v `create()` – a payload ji nese
s sebou; sloupce `event_id` a `occurred_on` v tabulce slouží už jen jako indexovaná metadata.
Čas události se ukládá i parsuje v UTC explicitně. Formát payloadu offset nenese,
takže by jinak deserializace na serveru s odlišnou default timezone časy posunula.

:::callout{type="note"}
### Jeden čas nestačí {#cas-udalosti-heading}

`occurredAt` v bázové třídě je čas zápisu do systému. Doménový fakt ale mohl nastat jindy.
Fowler oba časy pojmenovává *record time* a *actual time*
[[5]](https://martinfowler.com/eaaDev/timeNarrative.html), Verraes totéž rozvádí pod
názvem multi-temporal events [[6]](https://verraes.net/2022/03/multi-temporal-events/).
Dokud událost vzniká přímo z uživatelské akce, oba časy splývají a rozdíl nikoho netrápí.

Rozejdou se ve chvíli, kdy fakt nastal jinde a k vám dorazil později: noční import
bankovních transakcí z předchozího dne, zpětné zadání havárie, integrace s pomalým
externím systémem. Doménový čas pak patří do payloadu pod vlastním jménem
(`depositedAt`, `crashedAt`, `placedAt`) a z `occurredAt` zbývá infrastrukturní údaj.
Projekce v následující sekci plní sloupce `placed_at` a `shipped_at` z `occurredAt` –
u objednávek zadaných online to sedí, u importovaných dat by šlo o chybu.
:::

:::callout{type="warn"}
### GDPR a osobní údaje v Event Store {#gdpr-es-heading}

Event Store je append-only, a proto do event payloadu nepatří citlivé údaje (hesla, tokeny,
rodná čísla) v otevřené podobě. Jak právo na výmaz řeší referenční přístup a crypto-shredding,
rozebírá sekce [GDPR a immutable Event Store](#gdpr-event-store-heading).
:::

Pro `eventType()` se osvědčil formát `<bounded_context>.<past_tense_verb_noun>`, například
`ordering.order_placed` nebo `payment.payment_received`. Tato konvence usnadňuje
routing událostí v Symfony Messenger a jejich filtrování v Event Store.

### Interní a publikované události {#interni-a-publikovane-udalosti}

Události v Event Store slouží dvěma různým účelům a smíchat je stojí draho. Interní událost
je jednotka stavu agregátu. Její tvar se mění spolu s doménovým modelem a nikdo mimo
bounded context o něm nemá vědět. Publikovaná (integrační) událost je naopak veřejné
rozhraní kontextu – smlouva, na které staví cizí týmy.

Verraes k tomu formuluje pravidlo: veřejná je malá vybraná podmnožina událostí,
všechno ostatní zůstává ve výchozím stavu privátní
[[7]](https://verraes.net/2019/05/patterns-for-decoupling-distsys-explicit-public-events/).
Bez tohoto rozdělení se vnitřní struktura kontextu stává jeho API. Každé přejmenování pole
v agregátu je pak breaking change pro konzumenty a refaktoring interního modelu přestává
být možný. Většina bolesti, kterou řeší sekce [Verzování událostí](#verzovani-udalosti), má
původ právě zde.

Prakticky to znamená dvě sady tříd. Interní události zůstávají v `Domain\Event` a čtou
je pouze agregát a vlastní projekce. Publikované události žijí v samostatném namespace
(typicky `Application\IntegrationEvent`), mají stabilní `eventType`, vlastní verzování
a překlad z interní události do publikované obstará explicitní mapper. Cena je jedna
vrstva navíc; protihodnotou je možnost měnit doménový model bez koordinace s okolím.

## 13.05 Implementace Event Store {#event-store}

Event Store je append-only databázové úložiště pro všechny doménové události. Každý záznam
nese jednu událost s jejím kontextem – ke kterému agregátu patří, v jaké verzi streamu a kdy
nastala. Záznamy se **nikdy nepřepisují ani nemažou**.

### Struktura tabulky Event Store

:::callout{type="pattern"}
### SQL: Migrace tabulky `event_store` (MySQL/MariaDB) {#event-store-sql-heading}

:::code{language="sql" filename="migrations/snippet.sql"}
CREATE TABLE event_store (
    id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    event_id      CHAR(36)         NOT NULL COMMENT 'UUID v7 události - globálně unikátní',
    aggregate_id  CHAR(36)         NOT NULL COMMENT 'UUID agregátu (vlastníka streamu)',
    aggregate_type VARCHAR(255)    NOT NULL COMMENT 'FQCN nebo slug agregátu, napr. ordering.order',
    event_type    VARCHAR(255)     NOT NULL COMMENT 'Typ události, napr. ordering.order_placed',
    payload       JSON             NOT NULL COMMENT 'Serializovaná data události',
    -- DEFAULT jako výraz v závorkách vyžaduje MySQL 8.0.13+.
    -- MariaDB mapuje JSON na LONGTEXT s kontrolou JSON_VALID; default zvládá od 10.2.
    metadata      JSON             NOT NULL DEFAULT ('{}') COMMENT 'Korelační ID, causation ID, user ID…',
    schema_version SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Verze schématu payloadu - pro upcasting',
    version       INT UNSIGNED     NOT NULL COMMENT 'Pořadové číslo ve streamu agregátu (od 1)',
    occurred_on   DATETIME(6)      NOT NULL COMMENT 'UTC čas vzniku události',

    PRIMARY KEY (id),
    UNIQUE KEY uq_event_id (event_id),
    -- Optimistic locking: dvojice (aggregate_id, version) musí být unikátní
    UNIQUE KEY uq_aggregate_version (aggregate_id, version),
    KEY idx_aggregate_id (aggregate_id),
    KEY idx_aggregate_type (aggregate_type),
    KEY idx_event_type (event_type),
    KEY idx_occurred_on (occurred_on)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Append-only store všech doménových událostí';
:::
*migrations/snippet.sql*
:::

Sloupec `version` nese **optimistic locking**. Před zápisem nové události command handler
přečte poslední verzi streamu agregátu. Pokud mezitím jiný proces zapsal událost se stejnou
verzí, databáze při insertu vyvolá výjimku z porušení unikátního indexu `uq_aggregate_version`.
Souběžné zápisy se tak detekují bez pesimistického zamykání řádků.

Existuje i druhá varianta. Young pracuje vedle tabulky událostí ještě s tabulkou agregátů,
která nese aktuální verzi streamu denormalizovaně
[[2]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf). Konflikt se pak
kontroluje předem, proti uložené hodnotě, a chybová hláška vzniká v aplikačním kódu.
Cenou je jeden zápis navíc při každém commitu a nutnost udržet obě tabulky v jedné
transakci. Unikátní index žádný další zápis nepotřebuje, zato konflikt zjistí až
z výjimky, kterou je nutné přeložit na `ConcurrencyException` – přesně to dělá
následující implementace.

:::callout{type="pattern"}
### PHP: Interface EventStore a Doctrine implementace {#event-store-php-heading}

:::code{language="php" filename="src/Infrastructure/EventSourcing/EventStore.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use App\SharedKernel\Domain\Event\DomainEvent;

interface EventStore
{
    /**
     * Uloží nové události do event streamu agregátu.
     *
     * @param DomainEvent[] $events
     * @param int           $expectedVersion Verze posledního uloženého eventu - slouží
     *                                        pro optimistic locking. Použijte 0 pro nový agregát.
     *
     * @throws ConcurrencyException Pokud $expectedVersion neodpovídá skutečné verzi streamu.
     */
    public function append(
        string $aggregateId,
        string $aggregateType,
        array $events,
        int $expectedVersion,
    ): void;

    /**
     * Načte celý event stream agregátu (nebo od dané verze pro snapshot support).
     *
     * @return EventEnvelope[]
     */
    public function loadStream(
        string $aggregateId,
        int $fromVersion = 1,
    ): array;

    /**
     * Načte všechny události z celého Event Store (pro rebuild projekcí).
     * Vrací generátor pro paměťově efektivní iteraci nad miliony záznamů.
     *
     * @return \Generator<EventEnvelope>
     */
    public function loadAll(int $batchSize = 500): \Generator;
}
:::
*src/Infrastructure/EventSourcing/EventStore.php*

:::code{language="php" filename="src/Infrastructure/EventSourcing/DoctrineEventStore.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use App\SharedKernel\Domain\Event\DomainEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;

final class DoctrineEventStore implements EventStore
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventSerializer $serializer,
    ) {}

    /**
     * @param DomainEvent[] $events
     */
    public function append(
        string $aggregateId,
        string $aggregateType,
        array $events,
        int $expectedVersion,
    ): void {
        $version = $expectedVersion;

        $this->connection->beginTransaction();

        try {
            foreach ($events as $event) {
                $version++;

                $this->connection->insert('event_store', [
                    'event_id'       => $event->eventId,
                    'aggregate_id'   => $aggregateId,
                    'aggregate_type' => $aggregateType,
                    'event_type'     => $event->eventType(),
                    'payload'        => json_encode($event->toPayload(), JSON_THROW_ON_ERROR),
                    'metadata'       => '{}',
                    'schema_version' => $event->schemaVersion(),
                    'version'        => $version,
                    'occurred_on'    => $event->occurredAt->format('Y-m-d H:i:s.u'),
                ]);
            }

            $this->connection->commit();
        } catch (UniqueConstraintViolationException $e) {
            $this->connection->rollBack();
            throw new ConcurrencyException(
                "Concurrency conflict for aggregate {$aggregateId} at version {$version}.",
                previous: $e,
            );
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return EventEnvelope[]
     */
    public function loadStream(string $aggregateId, int $fromVersion = 1): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT event_type, payload, schema_version, version, occurred_on
               FROM event_store
              WHERE aggregate_id = :aggregateId
                AND version >= :fromVersion
           ORDER BY version ASC',
            ['aggregateId' => $aggregateId, 'fromVersion' => $fromVersion],
        );

        return array_map(
            fn(array $row) => $this->serializer->deserialize($row),
            $rows,
        );
    }

    /**
     * Iteruje přes celý Event Store v dávkách - paměťově efektivní pro rebuild projekcí.
     *
     * @return \Generator<EventEnvelope>
     */
    public function loadAll(int $batchSize = 500): \Generator
    {
        $lastId = 0;

        do {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, event_type, payload, schema_version, version, occurred_on
                   FROM event_store
                  WHERE id > :lastId
               ORDER BY id ASC
                  LIMIT :limit',
                ['lastId' => $lastId, 'limit' => $batchSize],
                ['limit' => ParameterType::INTEGER],
            );

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                yield $this->serializer->deserialize($row);
            }
        } while (count($rows) === $batchSize);
    }
}
:::
*src/Infrastructure/EventSourcing/DoctrineEventStore.php*
:::

Parametr `:limit` v metodě `loadAll()` má explicitní typ `ParameterType::INTEGER`.
Bez něj DBAL hodnotu naváže jako řetězec a MySQL výraz `LIMIT '500'` odmítne jako
syntaktickou chybu.

## 13.06 Agregát s Event Sourcingem {#aggregate-s-es}

V klasickém DDD agregát mění svůj stav přímou modifikací vlastních atributů. V Event Sourcingu
**každá změna stavu prochází přes doménovou událost**. Metody agregátu nemodifikují atributy
přímo – nahrají událost a teprve její aplikace na stav vyvolá změnu.

Výsledkem je, že agregát obsahuje dvě sady metod:

- **Mutační metody** (veřejné rozhraní agregátu) – validují invarianty, rozhodují, která událost nastane, a volají interní metodu pro nahrání události (typicky `recordEvent()`). Jméno se záměrně liší od `record()` ve stavově ukládaném [AggregateRoot](/zakladni-koncepty#aggregate-root-lifecycle) – zde metoda událost navíc aplikuje na stav a inkrementuje verzi streamu.
- **`apply*()` metody** (private/protected) – přijmou konkrétní typ události a aplikují změnu na interní stav. Tyto metody jsou volány jak při nahrávání nové události, tak při replay z Event Store.

Pro testování to znamená vzor **given/when/then** – given jsou historické události, when je
volání metody na agregátu, then jsou nově emitované události. Podrobně v kapitole
[Testování DDD kódu](/testovani-ddd).

:::callout{type="pattern"}
### PHP: Base class EventSourcedAggregate {#es-aggregate-base-heading}

:::code{language="php" filename="src/SharedKernel/Domain/EventSourcedAggregate.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

use App\SharedKernel\Domain\Event\DomainEvent;

abstract class EventSourcedAggregate
{
    /** @var DomainEvent[] Události nahrané v aktuální transakci - čekají na uložení. */
    private array $recordedEvents = [];

    private int $version = 0;

    /**
     * Nahrajeme novou událost: aplikujeme ji na stav, zapamatujeme ji pro persistenci
     * a inkrementujeme verzi streamu - nezbytné pro optimistic locking.
     */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->applyEvent($event);
        $this->recordedEvents[] = $event;
        $this->version++;
    }

    /**
     * Přehrajeme historické události z Event Store (bez přidávání do $recordedEvents).
     *
     * @param DomainEvent[] $events
     */
    public static function reconstituteFromEvents(array $events): static
    {
        $aggregate = new static();

        foreach ($events as $event) {
            $aggregate->applyEvent($event);
            $aggregate->version++;
        }

        return $aggregate;
    }

    /**
     * Přehraje dodatečné události na existující instanci (pro snapshot support).
     *
     * @param DomainEvent[] $events
     */
    public function replayEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->applyEvent($event);
            $this->version++;
        }
    }

    /**
     * Obnoví verzi streamu při rekonstrukci ze snapshotu.
     * Bez obnovení by verze začínala na nule a optimistic locking
     * by při prvním uložení selhal na konfliktu.
     */
    protected function restoreVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * Dynamické dispatchování na apply*() metody podle třídy události.
     * Konvence: apply + ShortClassName, napr. applyOrderPlaced().
     * apply*() metody v podtřídách MUSÍ být protected (ne private),
     * jinak je PHP nemůže volat z kontextu této nadtřídy.
     */
    private function applyEvent(DomainEvent $event): void
    {
        $method = 'apply' . (new \ReflectionClass($event))->getShortName();

        if (!method_exists($this, $method)) {
            throw new \LogicException(
                sprintf('Aggregate %s must implement %s().', static::class, $method)
            );
        }

        $this->$method($event);
    }

    /** @return DomainEvent[] */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }

    public function version(): int
    {
        return $this->version;
    }
}
:::
*src/SharedKernel/Domain/EventSourcedAggregate.php*
:::

U event-sourced agregátu se stav rekonstruuje replayem a vnitřní properties zůstávají
privátní – metody `apply*` je opakovaně přepisují. Gettery na konci třídy proto
nahrazují `public private(set)` z kapitoly [Návrh agregátu](/navrh-agregatu); jde
o záměrnou odchylku od konvence zbytku knihy.

:::callout{type="pattern"}
### PHP: Order agregát s Event Sourcingem {#es-order-aggregate-heading}

:::code{language="php" filename="src/Ordering/Domain/Order.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain;

use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderItemAdded;
use App\Ordering\Domain\Event\OrderShipped;
use App\Ordering\Domain\Exception\EmptyOrderException;
use App\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\SharedKernel\Domain\EventSourcedAggregate;

final class Order extends EventSourcedAggregate
{
    private string $orderId;
    private string $customerId;
    private OrderStatus $status;

    /** @var OrderItem[] */
    private array $items = [];

    private ?string $trackingNumber = null;

    // Statická továrna - vytvoří objednávku ve stavu Draft
    public static function place(string $orderId, string $customerId): self
    {
        $order = new self();
        $order->recordEvent(OrderPlaced::create($orderId, $customerId));

        return $order;
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Items can only be added to draft orders.');
        }

        $this->recordEvent(OrderItemAdded::create($this->orderId, $item));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Only draft orders can be confirmed.');
        }
        if (empty($this->items)) {
            throw new EmptyOrderException('Cannot confirm an empty order.');
        }

        $this->recordEvent(OrderConfirmed::create($this->orderId));
    }

    public function ship(string $trackingNumber): void
    {
        if ($this->status !== OrderStatus::Confirmed) {
            throw new InvalidOrderStateTransitionException('Only confirmed orders can be shipped.');
        }

        $this->recordEvent(OrderShipped::create($this->orderId, $trackingNumber));
    }

    // --- apply* metody - MUSÍ být protected (ne private), aby je base class mohla volat dynamicky ---
    // --- Obsahují POUZE změnu interního stavu, žádnou doménovou logiku ---

    protected function applyOrderPlaced(OrderPlaced $event): void
    {
        $this->orderId    = $event->orderId;
        $this->customerId = $event->customerId;
        $this->items      = [];
        $this->status     = OrderStatus::Draft;
    }

    protected function applyOrderItemAdded(OrderItemAdded $event): void
    {
        $this->items[] = $event->item;
    }

    protected function applyOrderConfirmed(OrderConfirmed $event): void
    {
        $this->status = OrderStatus::Confirmed;
    }

    protected function applyOrderShipped(OrderShipped $event): void
    {
        $this->status         = OrderStatus::Shipped;
        $this->trackingNumber = $event->trackingNumber;
    }

    // Gettery pro aplikační vrstvu
    public function orderId(): string         { return $this->orderId; }
    public function status(): OrderStatus     { return $this->status; }
    public function trackingNumber(): ?string { return $this->trackingNumber; }
}
:::
*src/Ordering/Domain/Order.php*
:::

Položku agregát drží jako neměnný záznam. Musí být serializovatelná, protože cestuje
uvnitř události do Event Store a zpátky:

:::code{language="php" filename="src/Ordering/Domain/OrderItem.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain;

final readonly class OrderItem
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public int $unitPriceInCents,
    ) {}
}
:::

Identifikátory jsou zde primitivní řetězce, ne hodnotové objekty jako ve zbytku knihy.
Je to druhá záměrná odchylka této kapitoly: událost se serializuje do Event Store
a zpět, takže primitivní tvar drží ukázku čitelnou bez vrstvy převodních typů.
V produkci hodnotové objekty zůstávají, převod obstará serializer.

### Načítání agregátu z event streamu (replay)

Repozitář pro event-sourcovaný agregát neprovádí SELECT do tabulky entit. Místo toho načte
event stream z Event Store a předá jej statické tovární metodě `reconstituteFromEvents()`.
Výsledný agregát má přesně takový stav, jaký odpovídá historii jeho událostí.

:::callout{type="warn"}
### Replay nesmí volat ven {#replay-gateways-heading}

Metody `apply*()` mění výhradně interní stav. Jakmile by kterákoli z nich odeslala e-mail,
strhla platbu nebo zavolala externí API, každá rekonstrukce agregátu by tu akci provedla
znovu. Na stejné riziko upozorňuje už Fowler u gateways k okolním systémům
[[1]](https://martinfowler.com/eaaDev/EventSourcing.html): brána musí vědět, že běží
replay, a v tom režimu ven nesmí sáhnout.

Replay v této kapitole probíhá na třech místech – při rekonstituci agregátu, při rebuildu
projekce a při načtení ze snapshotu. Obrana je organizační: vedlejší efekty patří do
handlerů nad publikovanými událostmi, nikdy do `apply*()` metod a nikdy do projektorů,
které rebuild spouští znovu.
:::

:::diagram{fig="13.6-A" title="Replay agregátu z event streamu" src="images/diagrams/14_event_sourcing/event_store_replay.svg"}
:::

:::callout{type="pattern"}
### PHP: EventSourced repozitář pro Order agregát {#es-repo-heading}

:::code{language="php" filename="src/Infrastructure/Ordering/EventSourcedOrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Ordering;

use App\Ordering\Domain\Order;
use App\Infrastructure\EventSourcing\EventStore;
use App\Infrastructure\EventSourcing\EventSerializer;

final class EventSourcedOrderRepository
{
    private const AGGREGATE_TYPE = 'ordering.order';

    public function __construct(
        private readonly EventStore $eventStore,
        private readonly EventSerializer $serializer,
    ) {}

    public function load(string $orderId): Order
    {
        $envelopes = $this->eventStore->loadStream($orderId);

        if (empty($envelopes)) {
            throw new \DomainException("Order {$orderId} not found.");
        }

        $events = array_map(
            fn($envelope) => $this->serializer->toEvent($envelope),
            $envelopes,
        );

        return Order::reconstituteFromEvents($events);
    }

    public function save(Order $order): void
    {
        $newEvents = $order->releaseEvents();

        if (empty($newEvents)) {
            return;
        }

        // expectedVersion = aktuální verze PŘED novými událostmi
        $expectedVersion = $order->version() - count($newEvents);

        $this->eventStore->append(
            $order->orderId(),
            self::AGGREGATE_TYPE,
            $newEvents,
            $expectedVersion,
        );
    }
}
:::
*src/Infrastructure/Ordering/EventSourcedOrderRepository.php*
:::

## 13.07 Projekce (Projections) {#projekce}

Event Store je append-only a neumí ad-hoc dotazy typu „všechny objednávky zákazníka X
s celkovou hodnotou nad 1000 Kč“. Pro takové dotazy vznikají vedle něj **projekce** –
denormalizované read modely budované z event streamu specificky pro tvar dotazů aplikace.

:::diagram{fig="13.7-A" title="Tok eventu z agregátu do read modelu přes projektor" src="images/diagrams/14_event_sourcing/projection_lifecycle.svg"}
:::

### Synchronní vs. asynchronní projekce

- **Synchronní projekce** – Projekce se aktualizuje přímo v téže transakci jako zápis události. Garantuje konzistenci dat v okamžiku odpovědi na command, ale zvyšuje latenci zápisu a zavádí těsnou vazbu mezi write a read stranou.
- **Asynchronní projekce** – Události jsou po uložení do Event Store zařazeny do fronty (Symfony Messenger + transport jako RabbitMQ nebo Redis). Projektor je konzument, který zpracovává zprávy nezávisle. Read model je v krátkém časovém okně nekonzistentní (eventual consistency), ale write side je rychlejší a oddělená.

### Projekce jako pozice ve streamu {#pozice-projekce}

Užitečnější model asynchronní projekce než „handler, kterému chodí zprávy“ je
*catch-up subscription*: projekce si drží **pozici** (checkpoint) ve streamu a od ní
čte dál. Odsud plynou tři provozní vlastnosti. Restart projektoru není událost –
pokračuje se od uložené pozice. Rebuild znamená nastavit pozici na nulu a nechat projekci
dojet historii. A rozdíl mezi poslední zapsanou událostí a pozicí projekce je *lag*,
jediné číslo, které o zdraví read strany opravdu vypovídá; patří do monitoringu.

Sekce [Praktické problémy projekcí](#prakticke-problemy-projekci) používá checkpoint
tabulku k idempotenci, tedy k odpovědi na otázku „zpracoval jsem už tuhle událost?“.
Jde o odvozenou roli téhož záznamu. Primární je pozice. Stejný model pozice ve streamu
používají i [process managery a ságy](/sagy-a-process-managery), které nad event streamem
neudržují read model, ale rozpracovaný proces.

:::callout{type="pattern"}
### PHP: OrderSummaryProjection a asynchronní projektor {#projekce-php-heading}

:::code{language="php" filename="src/Infrastructure/Ordering/Projection/OrderSummaryProjector.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Ordering\Projection;

use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderItemAdded;
use App\Ordering\Domain\Event\OrderShipped;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Projector budující tabulku order_summary z doménových událostí.
 *
 * Každá metoda handle*() odpovídá jednomu typu události a je registrována
 * jako samostatný Messenger handler atributem #[AsMessageHandler] na úrovni metody.
 */
final class OrderSummaryProjector
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    #[AsMessageHandler]
    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $this->connection->insert('order_summary', [
            'order_id'      => $event->orderId,
            'customer_id'   => $event->customerId,
            'status'        => 'draft',
            'item_count'    => 0,
            'total_amount'  => 0,
            'placed_at'     => $event->occurredAt->format('Y-m-d H:i:s'),
            'shipped_at'    => null,
            'tracking_no'   => null,
        ]);
    }

    #[AsMessageHandler]
    public function handleOrderItemAdded(OrderItemAdded $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_summary
                SET item_count   = item_count + 1,
                    total_amount = total_amount + :price
              WHERE order_id = :orderId',
            ['price' => $event->item->unitPrice(), 'orderId' => $event->orderId],
        );
    }

    #[AsMessageHandler]
    public function handleOrderConfirmed(OrderConfirmed $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_summary SET status = :status WHERE order_id = :orderId',
            ['status' => 'confirmed', 'orderId' => $event->orderId],
        );
    }

    #[AsMessageHandler]
    public function handleOrderShipped(OrderShipped $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_summary
                SET status      = :status,
                    shipped_at  = :shippedAt,
                    tracking_no = :trackingNo
              WHERE order_id = :orderId',
            [
                'status'     => 'shipped',
                'shippedAt'  => $event->occurredAt->format('Y-m-d H:i:s'),
                'trackingNo' => $event->trackingNumber,
                'orderId'    => $event->orderId,
            ],
        );
    }
}
:::
*src/Infrastructure/Ordering/Projection/OrderSummaryProjector.php*
:::

Asynchronní doručování událostí projektorům přes Symfony Messenger vyžaduje nastavený
transport a routing v `config/packages/messenger.yaml`:

:::callout{type="pattern"}
### YAML: Konfigurace Symfony Messenger pro asynchronní projekce {#messenger-yaml-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml"}
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    auto_setup: true

        routing:
            # Všechny doménové události routujeme na async transport
            'App\Ordering\Domain\Event\OrderPlaced':    async
            'App\Ordering\Domain\Event\OrderItemAdded': async
            'App\Ordering\Domain\Event\OrderConfirmed': async
            'App\Ordering\Domain\Event\OrderShipped':   async
:::
*config/packages/messenger.yaml*
:::

Výčet tříd v `routing` je u Event Sourcingu položka, na kterou se zapomíná. Typů událostí
rychle přibývá a chybějící řádek se projeví až tím, že projekce mlčí. Od Symfony 7.2 lze
routing připsat rovnou k události atributem `#[AsMessage('async')]` a YAML seznam zrušit.
Konfigurace se pak nemůže rozejít s doménovým modelem, protože žije v téže třídě.

Projekce lze **přebudovat** (rebuild) přehráním celého Event Store od začátku. Při změně
doménových požadavků stačí vytvořit novou projekci a přehrát historii. CRUD systémy tuto
možnost nemají – historická data v nich už nejsou k dispozici.

Odkud se berou samotné typy událostí, je otázka pro doménu, ne pro infrastrukturu.
Nejrychleji je odhalí workshop popsaný v kapitole [Event Storming](/event-storming) –
oranžové lístky s doménovými událostmi jsou přímými kandidáty na obsah event streamu.

## 13.08 Event Store jako outbox {#outbox}

Předchozí sekce ukazovala projektory jako Messenger handlery, které dostávají doménové
události z asynchronní fronty. Implicitně jsme předpokládali, že se událost po zápisu
do Event Store spolehlivě dostane do message brokeru. V produkci to bez další infrastruktury
neplatí. Zápis do databáze a publikace do brokeru jsou dvě nezávislé operace; spadne-li
proces mezi nimi, událost je uložená, ale ke konzumentům nikdy nedorazí. Tento *dual-write
problem*, jeho varianty i obecné řešení s kompletním kódem rozebírá kapitola
[Outbox Pattern](/outbox-pattern). Zde jen to, co je na Event Sourcingu specifické:
druhá tabulka není potřeba.

### Relay čte přímo z event_store {#es-outbox-heading}

Tabulka `event_store` splňuje vlastnosti outbox tabulky sama o sobě. Je to append-only
log a každý záznam vzniká ve stejné transakci jako odpovídající doménová změna. Přidává
se **relay worker**, který čte nové řádky podle `id` a posílá je do Messengeru.
Pozici posledního publikovaného řádku si ukládá do checkpoint tabulky, takže po restartu
pokračuje tam, kde skončil. Samotný checkpoint ovšem nestačí. Kvůli gap problému
popsanému v následujícím calloutu ho musíte doplnit o některou z mitigací: překryv
s deduplikací, výběr přes `FOR UPDATE SKIP LOCKED`, nebo CDC.
Implementace relay – polling worker pod supervisord, nebo
varianta s CDC – je shodná s běžným outboxem, viz
[Relay process – dvě varianty](/outbox-pattern#relay).

:::callout{type="warn"}
### Auto-increment negarantuje pořadí commitů {#auto-increment-gap-heading}

Hodnota `id` se přiděluje při INSERTu, ne při commitu. Transakce s nižším `id` proto
může commitnout později než souběžná transakce s vyšším. Relay, který mezitím posunul
checkpoint za vyšší `id`, opožděný řádek už nikdy nepřečte a událost se tiše ztratí.
Mitigace jsou tři. Polling s překryvem, tedy čtení i kusu historie za checkpointem,
v kombinaci s deduplikací u konzumentů. Výběr nepublikovaných řádků přes
`FOR UPDATE SKIP LOCKED` místo checkpointu. Nebo logical decoding nad transakčním logem
(Debezium), který události čte v pořadí commitů.
:::

### Záruky doručení a jejich důsledky {#outbox-zaruky-heading}

Outbox dává **at-least-once** doručení uvnitř jednoho kanálu. Konkrétně:

- **At-least-once:** pokud relay spadne mezi dispatchem a updatem checkpointu, stejná událost se po restartu publikuje znovu. Konzumenti musí být idempotentní – přesně tak, jak ukazuje následující sekce u projektorů.
- **Pořadí:** relay publikuje vzestupně podle `id`. Uvnitř streamu jednoho agregátu to odpovídá pořadí verzí, takže projektor uvidí `OrderPlaced` před `OrderShipped`. Napříč agregáty pořadí zajištěno není a kvůli gap problému popsanému výše nejde o spolehlivé globální pořadí commitů.
- **Latence:** mezi commitem události a jejím doručením k projektoru vzniká okno odpovídající polling intervalu relay. V praxi 100 ms až 1 s; nižší latenci dává výstupní transport, který umí push (např. PostgreSQL `LISTEN/NOTIFY` nebo Debezium).

Push variantu nad PostgreSQL nemusíte psát sami. Doctrine transport Symfony Messengeru
používá `LISTEN/NOTIFY` sám od verze 7.1 a ovládá se volbou `use_notify` v konfiguraci
transportu. Relay pak nečeká na tik pollingu, ale probudí ho notifikace z databáze.
Vlastní implementace má smysl jen tam, kde relay čte přímo `event_store` a Messenger
v této roli nefiguruje.

## 13.09 Praktické problémy projekcí {#prakticke-problemy-projekci}

Předchozí sekce ukázaly, jak projekci vybudovat a jak události spolehlivě doručit. V praxi
se ale objevují problémy, které z jednoduchých ukázek nejsou patrné. Tato sekce pokrývá
nejčastější z nich – idempotenci, chybové stavy, rebuild a eventual consistency z pohledu
uživatelského rozhraní.

### Idempotence projektorů

Asynchronní transport (RabbitMQ, Redis Streams, Amazon SQS) garantuje doručení zprávy
**alespoň jednou** (at-least-once delivery). Zpráva se proto může doručit opakovaně – po
timeoutu, restartu workeru nebo síťovém výpadku. Pokud projektor není idempotentní, opakované
zpracování způsobí poškozená data: duplicitní řádky, zdvojené částky, nekonzistentní počty.

Idempotenci lze zajistit dvěma způsoby: **upsert** (INSERT … ON DUPLICATE KEY UPDATE)
místo prostého INSERT, nebo **tracking tabulka** již zpracovaných událostí.
U tracking tabulky musí záznam checkpointu a zápis projekce proběhnout v jedné
databázové transakci – atomicita obou zápisů je podstatou idempotence. Pád workeru
mezi nimi by jinak událost tiše ztratil.

:::callout{type="pattern"}
### PHP: Idempotentní projektor s tracking tabulkou {#idempotent-projector-heading}

:::code{language="php" filename="src/Infrastructure/Ordering/Projection/IdempotentOrderProjector.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Ordering\Projection;

use App\Ordering\Domain\Event\OrderPlaced;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Idempotentní projektor: před zpracováním ověří, zda událost
 * již nebyla zpracována, pomocí tabulky projection_checkpoint.
 *
 * Pozn.: Atribut #[AsMessageHandler] na třídě registruje __invoke() jako handler.
 * Pro projektory zpracovávající více typů událostí použijte atribut
 * na jednotlivých metodách - viz OrderSummaryProjector výše.
 */
#[AsMessageHandler]
final class IdempotentOrderProjector
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(OrderPlaced $event): void
    {
        // Checkpoint i projekce běží v jedné transakci. Pád workeru mezi
        // oběma zápisy by jinak zanechal checkpoint bez projekce
        // a opakované doručení by událost tiše přeskočilo.
        $this->connection->transactional(function () use ($event): void {
            // Atomická kontrola + záznam: INSERT IGNORE vrátí 0 affected rows
            // pokud eventId již existuje → událost byla již zpracována.
            $affected = $this->connection->executeStatement(
                'INSERT IGNORE INTO projection_checkpoint (event_id, projection_name, processed_at)
                 VALUES (:eventId, :projection, NOW(6))',
                ['eventId' => $event->eventId, 'projection' => 'order_summary'],
            );

            if ($affected === 0) {
                return; // Duplicitní doručení - přeskočíme
            }

            // Vlastní projekční logika
            $this->connection->insert('order_summary', [
                'order_id'     => $event->orderId,
                'customer_id'  => $event->customerId,
                'status'       => 'draft',
                'item_count'   => 0,
                'total_amount' => 0,
                'placed_at'    => $event->occurredAt->format('Y-m-d H:i:s'),
            ]);
        });
    }
}
:::
*src/Infrastructure/Ordering/Projection/IdempotentOrderProjector.php*
:::

:::callout{type="pattern"}
### SQL: Tabulka `projection_checkpoint` pro tracking zpracovaných událostí {#checkpoint-ddl-heading}

:::code{language="sql" filename="migrations/snippet.sql"}
CREATE TABLE projection_checkpoint (
    event_id        CHAR(36)     NOT NULL COMMENT 'UUID události - odkaz na event_store.event_id',
    projection_name VARCHAR(100) NOT NULL COMMENT 'Název projekce, napr. order_summary',
    processed_at    DATETIME(6)  NOT NULL COMMENT 'Čas zpracování',

    PRIMARY KEY (event_id, projection_name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracking tabulka pro idempotentní projektory - zabraňuje duplicitnímu zpracování';
:::
*migrations/snippet.sql*
:::

:::callout{type="note"}
### Alternativa: upsert bez tracking tabulky {#idempotence-tip-heading}

Pro projekce, kde je výsledkem jediný řádek na agregát (typicky summary tabulky), je jednodušší
použít `INSERT … ON DUPLICATE KEY UPDATE`. Tracking tabulka se vyplatí, když jedna
událost aktualizuje více tabulek nebo řádků a potřebujete garantovat, že se celá operace provede
právě jednou.
:::

:::callout{type="warn"}
### Out-of-order doručení: UPDATE bez řádku se tiše ztratí {#out-of-order-heading}

At-least-once transport negarantuje ani pořadí. Dorazí-li `OrderItemAdded` dřív než
`OrderPlaced`, projektor provede UPDATE na řádek, který ještě neexistuje – příkaz projde,
ovlivní nula řádků a událost zmizí bez jediné chyby v logu. Obranou je upsert, který chybějící
řádek založí, nebo sloupec s verzí v read modelu: projektor událost aplikuje jen tehdy, když
její verze navazuje na uloženou, jinak ji vrátí do fronty k pozdějšímu zpracování.
:::

### Chybové stavy a retry strategie

Projektor může selhat z mnoha důvodů: dočasná nedostupnost databáze, neplatný payload
u staré události bez upcasteru, nebo bug v projekční logice. Symfony Messenger nabízí
dvě hlavní mechaniky pro řešení:

- **Retry transport** – zpráva se po selhání automaticky vrátí do fronty s exponenciálním backoffem (výchozí: 3 pokusy s násobičem 2, `max_delay` 0, tedy bez stropu, a jitter 0,1, který opakování rozprostře v čase).
- **Failed transport (dead letter queue)** – po vyčerpání retry pokusů se zpráva přesune do samostatné fronty, kde čeká na manuální zásah. Nedojde ke ztrátě události ani k zablokování zbytku fronty.

:::callout{type="pattern"}
### YAML: Kompletní konfigurace Messenger s retry a dead letter queue {#messenger-retry-heading}

Následující konfigurace rozšiřuje [základní nastavení](#messenger-yaml-heading) z předchozí
sekce o retry strategii a failed transport:

:::code{language="yaml" filename="config/packages/messenger.yaml"}
framework:
    messenger:
        # Failed transport - sem padají zprávy po vyčerpání retry pokusů
        failure_transport: failed

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000        # 1 sekunda
                    multiplier: 2      # exponenciální backoff: 1s, 2s, 4s
                    max_delay: 60000   # max 60 sekund mezi pokusy

            failed:
                dsn: 'doctrine://default?queue_name=failed'

        routing:
            'App\Ordering\Domain\Event\OrderPlaced':    async
            'App\Ordering\Domain\Event\OrderItemAdded': async
            'App\Ordering\Domain\Event\OrderConfirmed': async
            'App\Ordering\Domain\Event\OrderShipped':   async
:::
*config/packages/messenger.yaml*
:::

Pro diagnostiku a opětovné zpracování selhalých zpráv slouží příkazy Symfony Messenger:

- `bin/console messenger:failed:show` – zobrazí zprávy v dead letter queue
- `bin/console messenger:failed:retry` – pokusí se zprávy znovu zpracovat
- `bin/console messenger:failed:remove {id}` – odstraní neplatnou zprávu

Při běžném provozu se hodí přepínače, které z těchto příkazů udělají nástroj pro triáž.
`messenger:failed:show --stats` vypíše počty podle tříd zpráv, takže jedna vadná projekce
je vidět na první pohled; `--class-filter` pak omezí výpis i retry na jediný typ události.
Hromadný úklid po opravené chybě zvládne `messenger:failed:remove --all`. Pro projektory
s dlouho běžícími dávkami existuje od Symfony 7.3 `messenger:consume --keepalive`, který
transportu průběžně hlásí, že se na zprávě pracuje, a zabrání předčasnému redelivery.
Při vysokém průtoku událostí snižuje režii `--fetch-size` (Symfony 8.1), který si z transportu
bere zprávy po dávkách místo po jedné.

### Rebuild projekcí

Možnost přebudovat projekci od začátku je v Event Sourcingu praktická obrana proti chybám
v projekční logice. V provozu jde ale o netriviální operaci. Rebuild musí běžet odděleně
od normálního provozu projektoru, stará data se musí korektně odstranit a po dokončení musí
projekce odpovídat aktuálnímu stavu Event Store.

:::callout{type="pattern"}
### PHP: Symfony konzolový příkaz pro rebuild projekce {#rebuild-command-heading}

:::code{language="php" filename="src/Infrastructure/EventSourcing/Console/RebuildProjectionCommand.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing\Console;

use App\Infrastructure\EventSourcing\EventStore;
use App\Infrastructure\EventSourcing\EventSerializer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:projection:rebuild',
    description: 'Přebuduje projekci přehráním všech událostí z Event Store.',
)]
final class RebuildProjectionCommand extends Command
{
    /** @var array<string, array{projector: callable, table: string}> Registr projektorů dle názvu */
    private array $projectors;

    /**
     * @param iterable<string, object> $projectors Symfony tagged_iterator
     * @param array<string, string>    $projectionTables Mapa: název projekce → název tabulky
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EventStore $eventStore,
        private readonly EventSerializer $serializer,
        iterable $projectors,
        array $projectionTables,
    ) {
        parent::__construct();
        foreach ($projectors as $name => $projector) {
            $this->projectors[$name] = [
                'projector' => $projector,
                'table'     => $projectionTables[$name] ?? throw new \InvalidArgumentException(
                    "Projekce '{$name}' nemá definovanou tabulku v \$projectionTables.",
                ),
            ];
        }
    }

    protected function configure(): void
    {
        $this->addArgument('projection', InputArgument::REQUIRED, 'Název projekce k přebudování');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('projection');

        if (!isset($this->projectors[$name])) {
            $io->error("Projekce '{$name}' neexistuje. Dostupné: " . implode(', ', array_keys($this->projectors)));
            return Command::FAILURE;
        }

        $config = $this->projectors[$name];
        $table  = $config['table'];

        $io->warning("Rebuild smaže data projekce '{$name}' (tabulka '{$table}') a přehraje celý Event Store.");

        // 1. Smazat stávající data projekce - název tabulky pochází z whitelistu,
        //    nikoli z uživatelského vstupu, takže nehrozí SQL injection.
        $this->connection->executeStatement("TRUNCATE TABLE {$table}");

        // 2. Vymazat checkpoint záznamy pro tuto projekci
        $this->connection->executeStatement(
            'DELETE FROM projection_checkpoint WHERE projection_name = :name',
            ['name' => $name],
        );

        // 3. Přehrát všechny události z Event Store. Dispatch podle konvence
        //    handle{NázevUdálosti}() - události, pro které projektor
        //    handler nemá, se přeskočí.
        $projector = $config['projector'];
        $count = 0;
        $batchSize = 500;

        foreach ($this->eventStore->loadAll($batchSize) as $envelope) {
            $event  = $this->serializer->toEvent($envelope);
            $method = 'handle' . (new \ReflectionClass($event))->getShortName();

            if (!method_exists($projector, $method)) {
                continue;
            }

            $projector->$method($event);
            $count++;

            if ($count % $batchSize === 0) {
                $io->text("Zpracováno {$count} událostí…");
            }
        }

        $io->success("Projekce '{$name}' přebudována. Celkem {$count} událostí.");
        return Command::SUCCESS;
    }
}
:::
*src/Infrastructure/EventSourcing/Console/RebuildProjectionCommand.php*
:::

:::callout{type="warn"}
### Pozor: rebuild v produkci {#rebuild-warning-heading}

Před spuštěním rebuildu v produkci **zastavte asynchronní workery**
(`messenger:consume`), jinak worker a rebuild příkaz souběžně zapisují
do stejné projekce. Po dokončení rebuildu workery opět spusťte. U projekcí s miliony
událostí zvažte zpracování po menších dávkách a monitoring paměti.

Odstávka projekce je ale pro systém s SLA těžko obhajitelná. Provozní alternativou je
**blue/green rebuild**: nová projekce se staví do stínové tabulky (`order_summary_new`),
zatímco původní dál obsluhuje dotazy. Rebuild dojede historii, pak už jen dobírá živé
události, a jakmile je lag prakticky nulový, přepne se čtení na novou tabulku –
přejmenováním, přepnutím pohledu nebo změnou konfigurace. Stará tabulka zůstane pár dní
jako pojistka pro rollback. Cena je dvojnásobek místa po dobu přepnutí.
:::

### Eventual consistency a uživatelské rozhraní

Asynchronní projekce vytváří časové okno, typicky milisekundy až jednotky sekund, kdy uživatel
akci provedl, ale read model ji ještě nezobrazuje. Po kliknutí na „Potvrdit“ svítí na výpisu
stále „Draft“. Nejde o bug, nýbrž o vlastnost architektury. Strategie pro UI – optimistickou
aktualizaci, potvrzovací stránku, polling či SSE – rozebírá sekce
[Eventual Consistency v praxi](/cqrs#eventual-consistency) v kapitole CQRS.

:::callout{type="note"}
### Synchronní projekce jako pragmatický kompromis {#ec-note-heading}

Nemá-li aplikace vysokou zátěž na write straně a je-li latence zápisu přijatelná, je
legitimní začít se **synchronními projekcemi**. Na asynchronní se přejde teprve ve chvíli,
kdy se aktualizace v transakci stane úzkým hrdlem. V raných fázích projektu tak odpadnou
problémy s eventual consistency.
:::

## 13.10 Snapshotting {#snapshotting}

Se stárnutím systému rostou event streamy agregátů. Agregát s tisíci událostmi vyžaduje
při každém zpracování commandu načíst a přehrát celý ten objem řádků z databáze. Výkonnostní problém
se v provozu objeví dřív, než tým očekává.

Vzor **snapshotting** uchová aktuální stav agregátu v pravidelných intervalech – po každých
N událostech nebo časově. Při příštím načtení repozitář vyhledá poslední snapshot a z Event
Store dotáhne jen události novější než tento snapshot. Young pro tuto konstrukci používá
název *Rolling Snapshot* a zdůrazňuje její povahu
[[2]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf): jde o denormalizaci
stavu agregátu k danému okamžiku, tedy o výkonnostní heuristiku, nikoli o zdroj pravdy.
Zdrojem pravdy zůstává event stream a snapshot lze kdykoli zahodit a postavit znovu.

:::diagram{fig="13.10-A" title="Snapshot strategie: zhuštěný stav místo plného replay" src="images/diagrams/14_event_sourcing/snapshot_strategy.svg"}
:::

### Kdy vytvářet snapshots

- Poté, co replay agregátu začne měřitelně zpomalovat – práh závisí na doméně, typicky se pohybuje od stovek po tisíce událostí.
- Periodicky (např. jednou denně) pro agregáty s vysokou frekvencí událostí.
- Na vyžádání – jako optimalizační krok po migraci nebo importu dat.

:::callout{type="pattern"}
### PHP: Snapshot třída a repozitář se snapshot podporou {#snapshot-php-heading}

:::code{language="php" filename="src/Infrastructure/EventSourcing/Snapshot.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use DateTimeImmutable;

/**
 * Snapshot uchovává serializovaný stav agregátu v konkrétní verzi event streamu.
 */
final class Snapshot
{
    public function __construct(
        public readonly string $aggregateId,
        public readonly string $aggregateType,
        public readonly int $version,
        public readonly array $state,       // serializovaný stav agregátu
        public readonly DateTimeImmutable $takenAt,
    ) {}
}
:::
*src/Infrastructure/EventSourcing/Snapshot.php*

:::code{language="php" filename="src/Infrastructure/Ordering/SnapshottingOrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Ordering;

use App\Ordering\Domain\Order;
use App\Infrastructure\EventSourcing\EventStore;
use App\Infrastructure\EventSourcing\Snapshot;
use App\Infrastructure\EventSourcing\SnapshotStore;
use App\Infrastructure\EventSourcing\EventSerializer;

final class SnapshottingOrderRepository
{
    private const AGGREGATE_TYPE    = 'ordering.order';
    private const SNAPSHOT_INTERVAL = 50; // snapshot každých 50 událostí

    public function __construct(
        private readonly EventStore    $eventStore,
        private readonly SnapshotStore $snapshotStore,
        private readonly EventSerializer $serializer,
    ) {}

    public function load(string $orderId): Order
    {
        // 1. Pokusíme se načíst snapshot
        $snapshot = $this->snapshotStore->findLatest($orderId, self::AGGREGATE_TYPE);

        if ($snapshot !== null) {
            // 2a. Máme snapshot - přehrajeme pouze události novější než snapshot
            $fromVersion = $snapshot->version + 1;
            $aggregate   = Order::reconstituteFromSnapshot($snapshot->state, $snapshot->version);
        } else {
            // 2b. Nemáme snapshot - přehrajeme celý event stream od začátku
            $fromVersion = 1;
            $aggregate   = null;
        }

        $envelopes = $this->eventStore->loadStream($orderId, $fromVersion);

        if (empty($envelopes) && $aggregate === null) {
            throw new \DomainException("Order {$orderId} not found.");
        }

        if (!empty($envelopes)) {
            $events = array_map(
                fn($e) => $this->serializer->toEvent($e),
                $envelopes,
            );

            if ($aggregate !== null) {
                $aggregate->replayEvents($events);
            } else {
                $aggregate = Order::reconstituteFromEvents($events);
            }
        }

        return $aggregate;
    }

    public function save(Order $order): void
    {
        $newEvents = $order->releaseEvents();

        if (empty($newEvents)) {
            return;
        }

        $expectedVersion = $order->version() - count($newEvents);

        $this->eventStore->append(
            $order->orderId(),
            self::AGGREGATE_TYPE,
            $newEvents,
            $expectedVersion,
        );

        // Automatické snapshotování
        if ($order->version() % self::SNAPSHOT_INTERVAL === 0) {
            $this->snapshotStore->save(new Snapshot(
                aggregateId:   $order->orderId(),
                aggregateType: self::AGGREGATE_TYPE,
                version:       $order->version(),
                state:         $order->toSnapshot(),
                takenAt:       new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            ));
        }
    }
}
:::
*src/Infrastructure/Ordering/SnapshottingOrderRepository.php*
:::

Aby byl snapshotting funkční, musí agregát implementovat metody `toSnapshot(): array`
(serializace aktuálního stavu) a statickou `reconstituteFromSnapshot(array $state, int $version): static`
(deserializace). Na rozdíl od `reconstituteFromEvents()` tato metoda nevytváří apply*()
volání – přímo nastaví properties z uloženého snímku a přes `restoreVersion()`
z base class obnoví verzi streamu. Bez obnovené verze by optimistic locking při
prvním uložení selhal. Formát snapshotu se proto musí vyvíjet spolu s doménovým modelem.

:::callout{type="warn"}
### Invalidace snapshotů při změně schématu {#snapshot-invalidation-heading}

Při změně struktury agregátu (nové properties, přejmenování, změna typů) se staré snapshoty
stanou neplatné – deserializace vrátí nekompletní nebo chybný stav. Řešení:
buď přidejte k snapshotu číslo verze a implementujte migraci (analogicky k upcasterům),
nebo starší snapshoty invalidujte (smažte) a nechte repozitář přehrát celý
event stream. U agregátů s krátkými streamy (desítky událostí) je invalidace dostatečná;
u dlouhých streamů (tisíce událostí) se vyplatí migrace.
Více o výkonnostních dopadech viz [Výkonnostní aspekty](/vykonnostni-aspekty).
:::

## 13.11 Verzování událostí (Event Versioning) {#verzovani-udalosti}

Události v Event Store jsou **permanentní** – jednou uložené zůstávají ve své podobě natrvalo.
Doménový model se přitom v čase vyvíjí: přibývají atributy, mění se struktura dat, původní
pole se rozdělují nebo slučují. Otázka tedy zní: **jak přečíst starou událost novým kódem?**

Odpověď je **event versioning** – strategie, která zachovává zpětnou čitelnost starých
událostí i po změně jejich schématu. Nejrozšířenějším vzorem je **upcasting**: při
deserializaci se starší verze payloadu transformuje na aktuální formát, takže doménový model
pracuje pouze s nejnovější verzí. Nejpodrobněji téma zpracovává Young v knize *Versioning
in an Event Sourced System* [[8]](https://leanpub.com/esversioning), odkud pochází i většina
dnes používané terminologie – weak schema, copy and replace, double write.

O tom, kolik práce verzování obnáší, rozhoduje z velké části rozdělení popsané v sekci
[Interní a publikované události](#interni-a-publikovane-udalosti). U interní události
jste jediným konzumentem a stačí upcaster. U publikované měníte smlouvu s cizími týmy
a bez přechodného období se neobejdete.

### Proč je verzování nezbytné

- **Append-only princip** – Události v Event Store nelze měnit. Pokud změníte schéma události, stará data zůstávají v původním formátu navždy.
- **Replay a projekce** – Při přebudování projekcí nebo replay agregátu se přehrávají *všechny* historické události, včetně těch z prvních verzí systému.
- **Dlouhověkost systému** – Event-sourcovaný systém může běžet roky. Za tu dobu se doménové požadavky změní mnohokrát a schémata událostí se musejí vyvíjet spolu s nimi.

### Vzor Upcaster

Upcaster je objekt, který transformuje payload události z jedné verze do následující.
Upcasters se řetězí: pokud existuje událost ve verzi 1 a aktuální verze je 3, proběhne
transformace v1 → v2 → v3. Upcasting se provádí **při čtení** (deserializaci),
nikoli při zápisu – původní data v Event Store zůstávají nedotčena.

:::callout{type="pattern"}
### PHP: Interface EventUpcaster {#upcaster-interface-heading}

:::code{language="php" filename="src/Infrastructure/EventSourcing/Versioning/EventUpcaster.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing\Versioning;

/**
 * Upcaster transformuje payload události ze starší verze na novější.
 * Každý upcaster je zodpovědný za přesně jeden přechod verze (např. v1 → v2).
 */
interface EventUpcaster
{
    /**
     * Typ události, na který se upcaster vztahuje (např. "identity.user_registered").
     */
    public function eventType(): string;

    /**
     * Zdrojová verze payloadu, kterou tento upcaster transformuje.
     */
    public function fromVersion(): int;

    /**
     * Cílová verze payloadu po transformaci.
     */
    public function toVersion(): int;

    /**
     * Transformuje payload ze zdrojové verze na cílovou.
     *
     * @param array<string, mixed> $payload Data události ve zdrojové verzi.
     * @return array<string, mixed> Data události v cílové verzi.
     */
    public function upcast(array $payload): array;
}
:::
*src/Infrastructure/EventSourcing/Versioning/EventUpcaster.php*
:::

### Konkrétní příklad: rozdělení pole `fullName`

Představme si reálnou situaci: při spuštění systému událost `UserRegistered` obsahovala
pole `fullName` (celé jméno jako jeden řetězec). Později se objevil požadavek rozlišit
křestní jméno a příjmení – vznikla verze 2 se dvěma poli `firstName`
a `lastName`. V Event Store ale stále existují tisíce událostí v1 s polem `fullName`.

:::callout{type="pattern"}
### PHP: Upcaster pro UserRegistered v1 → v2 {#upcaster-impl-heading}

:::code{language="php" filename="src/Infrastructure/Identity/Versioning/UserRegisteredV1ToV2Upcaster.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Versioning;

use App\Infrastructure\EventSourcing\Versioning\EventUpcaster;

/**
 * Transformuje UserRegistered v1 (fullName) na v2 (firstName + lastName).
 *
 * Strategie rozdělení: první slovo je firstName, zbytek lastName.
 * Pokud jméno obsahuje pouze jedno slovo, lastName se nastaví na prázdný řetězec.
 */
final readonly class UserRegisteredV1ToV2Upcaster implements EventUpcaster
{
    public function eventType(): string
    {
        return 'identity.user_registered';
    }

    public function fromVersion(): int
    {
        return 1;
    }

    public function toVersion(): int
    {
        return 2;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function upcast(array $payload): array
    {
        $fullName = $payload['fullName'] ?? '';
        $parts    = explode(' ', trim($fullName), 2);

        $payload['firstName'] = $parts[0];
        $payload['lastName']  = $parts[1] ?? '';

        // Odstraníme původní pole - v2 schéma jej již nepoužívá
        unset($payload['fullName']);

        return $payload;
    }
}
:::
*src/Infrastructure/Identity/Versioning/UserRegisteredV1ToV2Upcaster.php*
:::

:::callout{type="pattern"}
### PHP: UpcasterChain – řetězení upcasterů při deserializaci {#upcaster-chain-heading}

:::code{language="php" filename="src/Infrastructure/EventSourcing/Versioning/UpcasterChain.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing\Versioning;

/**
 * Řetězí upcasters a transformuje payload z libovolné historické verze
 * na aktuální verzi. Upcasters se aplikují postupně: v1 → v2 → v3 → …
 */
final readonly class UpcasterChain
{
    /** @var array<string, array<int, EventUpcaster>> Klíč = eventType, vnitřní klíč = fromVersion */
    private array $upcasters;

    /**
     * @param EventUpcaster[] $upcasters
     */
    public function __construct(array $upcasters)
    {
        $map = [];

        foreach ($upcasters as $upcaster) {
            $map[$upcaster->eventType()][$upcaster->fromVersion()] = $upcaster;
        }

        $this->upcasters = $map;
    }

    /**
     * Aplikuje všechny relevantní upcasters na payload.
     *
     * @param string              $eventType      Typ události (např. "identity.user_registered").
     * @param int                 $schemaVersion  Verze payloadu uloženého v Event Store.
     * @param array<string, mixed> $payload        Původní payload z Event Store.
     * @return array<string, mixed> Transformovaný payload v aktuální verzi.
     */
    public function upcast(string $eventType, int $schemaVersion, array $payload): array
    {
        if (!isset($this->upcasters[$eventType])) {
            return $payload;
        }

        $version = $schemaVersion;

        while (isset($this->upcasters[$eventType][$version])) {
            $upcaster = $this->upcasters[$eventType][$version];
            $payload  = $upcaster->upcast($payload);
            $version  = $upcaster->toVersion();
        }

        return $payload;
    }
}
:::
*src/Infrastructure/EventSourcing/Versioning/UpcasterChain.php*
:::

`UpcasterChain` se integruje do `EventSerializer`. Při deserializaci se
z uloženého záznamu přečte `event_type` a `schema_version` a payload projde řetězem
upcasterů. Konstruktor aktuální třídy události pak dostane už jen transformovaná data.

:::callout{type="note"}
### Weak vs. strong schema strategie {#schema-strategie-heading}

Pro tvar payloadu existují dva přístupy, které se v praxi míchají:

- **Weak schema (slabé schéma)** – Payload je uložen jako volný JSON bez formální definice. Upcasters transformují data ad-hoc. Výhodou je flexibilita a rychlost vývoje; nevýhodou je, že chyby v transformaci se projeví až za běhu a je obtížné ověřit konzistenci napříč verzemi.
- **Strong schema (silné schéma)** – Každá verze události má explicitně definované schéma (např. pomocí JSON Schema nebo PHP třídy s validací). Upcaster pak transformuje mezi dvěma dobře definovanými strukturami. Výhodou je vyšší bezpečnost a možnost automatického testování kompatibility; nevýhodou je vyšší režie při každé změně schématu.

Pro většinu projektů se osvědčí **kombinace obou přístupů**. Silné schéma dostanou
kritické události v Core Doméně: finanční transakce, stavy objednávek. Slabé schéma stačí
pro notifikace, logy aktivit a další události v podpůrných kontextech.
:::

### Změny, které upcasting neřeší {#breaking-changes-heading}

Upcasting předpokládá, že stará data lze deterministicky přeložit na nový formát.
Některé změny tuto vlastnost nemají:

- **Sémantická změna pole.** `Order.shippingPrice` původně zahrnoval DPH,
  od v3 ho neobsahuje. Stará data nelze správně přeložit – DPH sazba
  v okamžiku vystavení objednávky není v eventu uložená. Upcaster může jen
  *předpokládat* (např. konstantní 21 %), což je nepřesné a generuje
  reporty s chybnými čísly.
- **Event splitting.** Původní `OrderPlaced` obsahoval `customerData`
  inline. V nové verzi se rozděluje na `OrderPlaced` + `CustomerSnapshotted`
  (samostatný event). Upcaster by musel vytvořit *druhý* event z prvního,
  což porušuje princip „1 fyzický event v Event Store = 1 logický fakt“.
- **Event merging.** Dva eventy `ItemAdded` + `ItemQuantityChanged` se
  v nové doméně spojí do jednoho `ItemUpserted`. Upcasting jdoucí jednou
  cestou nestačí – potřebujete agregátní transformaci napříč streamem.
- **Sémantický bug v doménové logice.** Stará data byla validní podle
  starého modelu, ale ten model byl chybný. Replay přes opravený kód
  vyhodí výjimky.

Čtyři možné cesty, podle závažnosti:

:::callout{type="pattern"}
### Strategie 1: Copy-and-replace stream {#copy-replace-heading}

Spustí se one-time migrace, která čte starý stream, transformuje events
v PHP kódu (žádný upcaster, plnohodnotná migrace) a zapíše do **nového** streamu
(`order_v2`). Starý stream zůstává jako audit trail, ale doménový kód
ho ignoruje.

```text
order_v1 (frozen, audit only)
    │
    ▼ migration script
order_v2 (active)
```

Cena: doba běhu migrace (může to být hodiny u velkých streamů), nutnost double-write
během přechodného období (aplikace zapisuje do obou streamů, dokud migrace neskončí).
:::

:::callout{type="pattern"}
### Strategie 2: Multi-version event store {#multi-version-heading}

V Event Store fyzicky koexistují **obě verze** schémat. Repozitář při rekonstrukci agregátu
rozhodne podle sloupce `schema_version`, který stream číst. Nově vzniklé agregáty
zapisují v2, staré dál ve v1. Na nový tvar se agregát přepne teprve při příští
doménové operaci (lazy migration).

Cena: doménový kód musí umět obsloužit obě verze (větvení v factory metodách).
Vhodné pokud breaking change ovlivňuje jen malou část streamů.
:::

:::callout{type="pattern"}
### Strategie 3: Compensating event {#compensating-event-heading}

Místo přepisování historie se vloží **nová událost**, která starý fakt
opravuje:

```text
v1: OrderPlaced(price=100, includedVAT=true)
v2: PriceCorrectedDueToVATBug(orderId, originalPrice=100, correctedNet=82.64)
v3: ... (další eventy pracují s opravenou hodnotou)
```

Doménový kód při replay aplikuje obě události a stav konverguje na správnou
hodnotu. Audit trail je explicitní – stará data jsou zachována, oprava je
samostatný fakt.

Cena: doménový model získává „šum“ event typů, které řeší minulé bugy.
Po pár letech provozu tvoří historické opravy znatelnou část event typů.

Vzor má i starší jméno. Young jej vede jako *Reversal Transaction*
a odvozuje ho z pravidla, že v event-sourced systému neexistuje operace delete
[[2]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf). Zrušení se modeluje
jako další transakce, která stav vrátí zpět, ale stopu po původním faktu ponechá.
:::

:::callout{type="pattern"}
### Strategie 4: Double write {#double-write-heading}

Nejméně invazivní cesta u publikovaných událostí. Po přechodné období zapisuje aplikace
obě verze schématu vedle sebe: starou pro stávající konzumenty, novou pro ty, kdo už
migrovali. Konzumenti se stěhují svým tempem, žádná koordinovaná odstávka není potřeba.
Když poslední z nich přejde na novou verzi, zápis staré se vypne a upcaster pro historii
zůstane.

```text
t0:  OrderPlaced v1  ──▶ konzumenti A, B, C
t1:  OrderPlaced v1 + v2 (double write)
t2:  konzument C migrován ──▶ v2
t3:  OrderPlaced v2  ──▶ konzumenti A, B, C
```

Cena: po dobu přechodu dvojnásobný objem zápisů a riziko, že se obě verze rozejdou.
Zápis obou variant proto patří do jednoho místa – do mapperu z interní události na
publikovanou, ne do doménového kódu. Strategii popisuje Young v samostatné kapitole
knihy o verzování [[8]](https://leanpub.com/esversioning).
:::

:::callout{type="note"}
### Import legacy dat: migrační události {#migracni-udalosti-heading}

Kdo nasazuje Event Sourcing na existující systém, řeší tutéž otázku: co s daty, u kterých
historie neexistuje? Zákazník v databázi je, ale událost, která ho založila, nikdy nevznikla.
Pokušení je dopsat chybějící historii zpětně a tvářit se, že tam vždycky byla. Tím se ovšem
do streamu dostanou vymyšlené fakty a auditní hodnota logu padá.

Verraes pro tuto situaci zavádí *migration events* v takzvaném ghost contextu
[[9]](https://verraes.net/2019/06/eventsourcing-patterns-migration-events-ghost-context/).
Import se modeluje jako samostatná událost pojmenovaná terminologií starého systému –
`LegacyCustomerWasImported`, ne `CustomerRegistered`. Stream pak čestně říká, co se
skutečně stalo: k tomuto dni jsme převzali stav odjinud. Postup migrace po kontextech
rozebírá kapitola [Migrace z CRUD](/migrace-z-crud).
:::

### Stream archivation a storage tiering {#archivation-heading}

Agregáty s dlouhým životním cyklem (`UserAccount`, `Subscription`, `LedgerAccount`) nasbírají
za roky provozu desítky až stovky tisíc událostí. Aktivní Event Store tabulka
roste, dotazy se zpomalují a snapshoty musí vznikat častěji.

Standardní řešení: **storage tiering** podle stáří streamu.

- **Hot tier** (primární databáze, ať už MySQL nebo PostgreSQL) – události za posledních 90 dní, dotazy < 10 ms.
- **Warm tier** (čtecí replika nebo tatáž databáze na pomalejším disku) – události 90 dní – 2 roky.
  Hydration sahá sem jen pro forenzní dotazy nebo plný replay projekce.
- **Cold tier** (S3, Glacier, on-prem object storage) – události starší než 2 roky.
  Pouze pro čtení; přístup k němu vyžadují jen auditní reporty a compliance.

Implementace: každou noc se spustí job, který
přesune `event_store` řádky starší než N dní do `event_store_archive` tabulky
(nebo přímo do S3 jako Parquet). Repozitář při hydration **ve výchozím nastavení cold tier nečte** – pokud agregát potřebuje plný replay, operátor jej explicitně obnoví
ze snapshotu novějšího, než je hranice cold tieru. Pro audit dotazy funguje zvlášť query
service, který umí číst všechny tři tiers.

:::callout{type="warn"}
### GDPR a immutable Event Store {#gdpr-event-store-heading}

Event Store je z definice append-only, ale GDPR požaduje právo na výmaz (čl. 17).
Pro osobní údaje je bezpečnější cestou **referenční přístup**, u Verraese vedený jako
*forgettable payloads*
[[10]](https://verraes.net/2019/05/eventsourcing-patterns-forgettable-payloads/).
Event nese jen identifikátor subjektu. Samotná PII leží v odděleném úložišti mimo event
stream a maže se běžným DELETE. Struktura ani auditní hodnota streamu tím neutrpí
a smazaný údaj je opravdu pryč.

Druhou možností je **crypto-shredding**
[[11]](https://verraes.net/2019/05/eventsourcing-patterns-throw-away-the-key/). Každý
subjekt má v separátní tabulce `subject_keys` symetrický klíč, kterým se při serializaci
zašifrují PII pole (`email`, `name`, `address`); zbytek payloadu zůstává čitelný. Po žádosti
o výmaz se klíč zničí a z auditu zbude „uživatel #42 provedl akci v čase T“ bez možnosti
identifikace. Verraes k tomu ale uvádí námitku právníka Harrisona J. Browna: zašifrovaný
osobní údaj je pořád osobní údaj, bez ohledu na to, kdo drží klíč. Přidává i technickou
výhradu – šifra, která je dnes neprolomitelná, jí za deset let být nemusí.

Doporučení tedy zní: pro osobní údaje sáhněte nejdřív po referenčním přístupu,
crypto-shredding zůstává pro obchodně citlivá data, kde zákonná povinnost výmazu
nehrozí. Kapitola popisuje technické možnosti, nikoli právní stav – posouzení konkrétního
zpracování patří právníkovi, ne architektovi.
:::

:::faq{}
- question: Co je Event Sourcing?
  answer: 'Event Sourcing je přístup k persistenci stavu, při kterém se neukládá aktuální snímek dat, ale append-only sekvence neměnných událostí, které k aktuálnímu stavu vedly. Aktuální stav agregátu vzniká přehráním těchto událostí od počátku, což poskytuje úplný audit trail a možnost zpětně rekonstruovat jakýkoli stav v čase. Platí princip „current state is derived from the history of events“: event log se pouze rozšiřuje o nové záznamy. Viz <a href="#co-je-event-sourcing">úvodní sekci</a>.'
- question: Jaký je vztah mezi Event Sourcingem a CQRS?
  answer: 'Event Sourcing a CQRS jsou dva samostatné vzory, které se často kombinují. CQRS funguje i s klasickou ORM persistencí a ES lze zavést i bez formálního rozdělení na write a read modely. Symetrie ale neplatí: write model event-sourced systému se nedá dotazovat jinak než podle ID, takže read stranu postavíte tak jako tak. V praxi se však hodí dohromady, protože ES přirozeně vede k oddělení zápisu (event store) a čtení (projekce do read modelů) – což je přesně myšlenka CQRS. Více v <a href="#vztah-k-cqrs">sekci Vztah k CQRS</a>.'
- question: Co je Event Store a k čemu slouží?
  answer: 'Event Store je specializované append-only úložiště, které persistuje doménové události jednotlivých agregátů chronologicky seřazené. Typicky poskytuje dotazy na event stream konkrétního agregátu pro jeho rekonstrukci a globální dotaz pro čtení událostí všemi projekcemi. Základní metody jsou <code>append(streamId, events)</code> a <code>readStream(streamId)</code>; pokročilejší řešení zahrnují optimistické zamykání verzí a publikování událostí do event busu. Implementačně může jít o specializovaný produkt (KurrentDB, který se do prosince 2024 jmenoval EventStoreDB), o PHP knihovnu nad relační databází (EventSauce, patchlevel/event-sourcing, prooph), nebo o vlastní minimalistickou nadstavbu, jakou staví tato kapitola. Detailní rozbor v <a href="#event-store">sekci Implementace Event Store</a>.'
- question: Co jsou projekce v Event Sourcingu?
  answer: 'Projekce je proces, který naslouchá událostem z event store a buduje z nich read modely – denormalizované datové struktury určené pro rychlé dotazy. Projekce bývá jednoúčelová: každý read model má obvykle vlastní projekci, která ho od začátku nebo od posledního zpracovaného offsetu udržuje aktuální. Projekce lze kdykoli přebudovat (rebuild) přehráním událostí od počátku, čímž se bezpečně opravují chyby v read modelech. Praktický příklad v <a href="#projekce">sekci Projekce</a>.'
- question: K čemu slouží snapshotting v Event Sourcingu?
  answer: 'Snapshotting je technika, při které se periodicky ukládá serializovaný stav agregátu, aby se při jeho rekonstrukci nemuselo přehrávat celé event history od začátku. Při načtení se vezme poslední snapshot a aplikují se pouze události, které nastaly po něm. Snapshoty řeší výkonnostní problém dlouhých streamů, typicky u agregátů s řádově tisíci událostí – pro krátké streamy jsou zbytečné a přidávají operační komplexitu. Podrobný rozbor v <a href="#snapshotting">sekci Snapshotting</a>.'
- question: Kdy se vyplatí Event Sourcing nasadit?
  answer: 'Event Sourcing se vyplatí tam, kde je historie změn sama o sobě doménově cenná – finanční systémy, sklady, auditované procesy, regulovaná odvětví – nebo kde je třeba rekonstruovat stav v libovolném bodě minulosti. Nevhodný je pro prototypy, MVP a prosté CRUD aplikace. Nasazuje se zpravidla selektivně na jeden bounded context, nikoli plošně na celou aplikaci. Rozhodovací kritéria v <a href="#kdy-pouzit">sekci Kdy použít Event Sourcing</a>.'
:::
