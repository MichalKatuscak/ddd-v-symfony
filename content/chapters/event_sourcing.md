---
route: event_sourcing
path: /event-sourcing
title: Event Sourcing v DDD a Symfony
page_title: "Event Sourcing v DDD a Symfony | DDD Symfony"
meta_description: "Event Sourcing v DDD a Symfony 8: Event Store, projekce, snapshoty, upcasting, Outbox pattern a praktická řešení idempotence i rebuild projekcí."
meta_keywords: "Event Sourcing, DDD, Domain-Driven Design, Symfony, Event Store, Aggregate, Projection, Outbox pattern, Snapshot, CQRS, doménové události, PHP, immutabilita, event stream, Symfony Messenger, idempotence, eventual consistency, upcasting, event versioning, projection rebuild, dual-write problem"
og_type: article
published: "2025-04-24"
modified: "2026-05-03"
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
neměnných událostí**, jež k danému stavu
vedly [[1]](https://martinfowler.com/eaaDev/EventSourcing.html).
Každá změna stavu domény je zaznamenána jako samostatná, pojmenovaná událost se svými daty.
Aktuální stav agregátu pak vzniká *přehráním* (replay) těchto událostí od počátku.

Princip lze vyjádřit větou: ***current state is derived from the history of events***.
Namísto jediného řádku v databázové tabulce, který je při každé změně přepisován, existuje append-only log
všech událostí, jež kdy na agregátu nastaly.

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
- **Event Store** – Specializované append-only úložiště pro události. Události se do něj pouze přidávají; nikdy se neupravují ani nemažou. Každá událost patří do event streamu konkrétního agregátu.
- **Aggregate ([Agregát](/zakladni-koncepty#aggregates))** – V kontextu ES je agregát rekonstruován přehráním všech událostí ze svého event streamu. Každá mutace stavu agregátu produkuje novou událost místo přímé modifikace atributů.
- **Projection (Projekce)** – Read model sestavený z událostí. Projekce transformují event stream do podoby vhodné pro konkrétní dotazy (query) – například denormalizovaná tabulka pro přehled objednávek.
- **Snapshot** – Periodicky ukládaný snímek aktuálního stavu agregátu, který slouží jako zkratka při replay. Umožňuje přehrát pouze události novější než poslední snapshot místo celého event streamu od počátku.
:::

## 13.02 Vztah k CQRS {#vztah-k-cqrs}

Event Sourcing a [CQRS](/cqrs) jsou dva samostatné
vzory [[2]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf).
**Nejsou totéž** – lze aplikovat CQRS bez Event Sourcingu a naopak ES bez CQRS. V praxi DDD
aplikací se ale obvykle objevují společně.

Důvod je technický: Event Sourcing produkuje události jako základní artefakt
persistence a CQRS potřebuje způsob, jak aktualizovat read modely při každé změně write strany.
Události tuto propagaci pokrývají bez další infrastruktury – write side uloží událost do Event Store,
read side ji přečte a aktualizuje projekci.

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
**Event Sourcing** je vzor persistence: říká, jak ukládat stav.
Při jejich kombinaci ES zásobuje CQRS read side daty – každá událost o změně je současně
vstupem pro aktualizaci projekcí.
:::

## 13.03 Doménové události jako základ Event Sourcingu {#domenove-udalosti}

V Event Sourcingu jsou doménové události (Domain Events) zdrojem pravdy o stavu systému – nejen
notifikací o vedlejších efektech, jako je tomu u událostí v Doctrine ORM aplikaci. Tomu odpovídají
i přísnější požadavky na jejich tvar:

- **Immutabilita** – Po vytvoření nelze událost měnit. Veškeré její properties jsou read-only, nastavené v konstruktoru.
- **Serializovatelnost** – Událost musí být serializovatelná do trvalého formátu (JSON, MessagePack…) a deserializovatelná zpět bez ztráty informace.
- **Verzování** – Schéma události se v čase může vyvíjet. Stará data v Event Store je třeba udržet čitelná, typicky pomocí upcastingu (transformace starých verzí na aktuální).
- **Pojmenování v minulém čase** – Události vyjadřují fakta, která již nastala: `UserRegistered`, `OrderPlaced`, `PaymentFailed`.
- **Dostatečná granularita dat** – Událost musí obsahovat veškerá data potřebná k tomu, aby z ní bylo možné rekonstruovat stav, aniž by byl nutný přístup k externím zdrojům.

:::callout{type="pattern"}
### PHP: Interface DomainEvent a konkrétní třída UserRegistered {#domain-event-php-heading}

:::code{language="php" filename="src/Shared/Domain/Event/DomainEvent.php"}
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;

/**
 * Společný interface pro všechny doménové události.
 * Všechny implementace musí být immutabilní value objekty.
 */
interface DomainEvent
{
    /** Unikátní identifikátor události (UUID v4). */
    public function eventId(): string;

    /** Čas vzniku události - vždy UTC. */
    public function occurredOn(): DateTimeImmutable;

    /**
     * Název události sloužící k jejímu uložení a vyhledání v Event Store.
     * Konvence: FQCN nebo krátký slug ve tvaru "user.registered".
     */
    public function eventType(): string;

    /**
     * Verze schématu payloadu - klíčová pro upcasting starých událostí.
     * Nové verze události inkrementují toto číslo.
     */
    public function schemaVersion(): int;

    /**
     * Serializace do pole pro uložení do Event Store.
     * Musí obsahovat všechna data potřebná k rekonstrukci stavu.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;
}
:::
*src/Shared/Domain/Event/DomainEvent.php*

:::code{language="php" filename="src/Identity/Domain/Event/UserRegistered.php"}
<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Událost emitovaná po úspěšné registraci uživatele.
 * Immutabilní - všechny properties jsou readonly.
 */
final class UserRegistered implements DomainEvent
{
    private readonly string $eventId;
    private readonly DateTimeImmutable $occurredOn;

    public function __construct(
        private readonly string $userId,
        private readonly string $email,
        private readonly string $fullName,
    ) {
        $this->eventId    = Uuid::uuid4()->toString();
        $this->occurredOn = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
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
            'userId'         => $this->userId,
            'email'          => $this->email,
            'fullName'       => $this->fullName,
        ];
    }

    // --- Gettery (pro použití v aplikační vrstvě) ---

    public function userId(): string  { return $this->userId; }
    public function email(): string   { return $this->email; }
    public function fullName(): string { return $this->fullName; }
}
:::
*src/Identity/Domain/Event/UserRegistered.php*
:::

:::callout{type="warn"}
### GDPR a osobní údaje v Event Store {#gdpr-es-heading}

Event Store je append-only – události nelze upravovat ani mazat. Při návrhu událostí
proto **nikdy neukládejte citlivé údaje** (hesla, tokeny, rodná čísla)
přímo do event payloadu. Tyto údaje patří do separátního úložiště s možností smazání.

Pro splnění práva na výmaz (GDPR čl. 17) existují dva hlavní přístupy:

- **Crypto-shredding** – osobní údaje v eventech jsou šifrovány klíčem specifickým pro daného uživatele. Při žádosti o výmaz se smaže klíč, čímž se data stanou nečitelnými.
- **Referenční přístup** – event obsahuje pouze ID uživatele, osobní údaje jsou uloženy v separátní tabulce s možností DELETE.
:::

Konvence pojmenování událostí by měla být konzistentní napříč celým projektem. Doporučený formát pro
`eventType()` je `<bounded_context>.<past_tense_verb_noun>`, například
`ordering.order_placed` nebo `payment.payment_received`. Tato konvence usnadňuje
routing událostí v Symfony Messenger a jejich filtrování v Event Store.

## 13.04 Implementace Event Store {#event-store}

Event Store je append-only databázové úložiště pro všechny doménové události. Každý záznam
nese jednu událost s jejím kontextem – ke kterému agregátu patří, v jaké verzi streamu a kdy
nastala. Záznamy se **nikdy nepřepisují ani nemažou**.

### Struktura tabulky Event Store

:::callout{type="pattern"}
### SQL: Migrace tabulky `event_store` (MySQL/MariaDB) {#event-store-sql-heading}

:::code{language="sql" filename="migrations/snippet.sql"}
CREATE TABLE event_store (
    id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    event_id      CHAR(36)         NOT NULL COMMENT 'UUID v4 události - globálně unikátní',
    aggregate_id  CHAR(36)         NOT NULL COMMENT 'UUID agregátu (vlastníka streamu)',
    aggregate_type VARCHAR(255)    NOT NULL COMMENT 'FQCN nebo slug agregátu, napr. ordering.order',
    event_type    VARCHAR(255)     NOT NULL COMMENT 'Typ události, napr. ordering.order_placed',
    payload       JSON             NOT NULL COMMENT 'Serializovaná data události',
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

:::callout{type="pattern"}
### PHP: Interface EventStore a Doctrine implementace {#event-store-php-heading}

:::code{language="php" filename="src/Infrastructure/EventSourcing/EventStore.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use App\Shared\Domain\Event\DomainEvent;

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

use App\Shared\Domain\Event\DomainEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

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
                    'event_id'       => $event->eventId(),
                    'aggregate_id'   => $aggregateId,
                    'aggregate_type' => $aggregateType,
                    'event_type'     => $event->eventType(),
                    'payload'        => json_encode($event->toPayload(), JSON_THROW_ON_ERROR),
                    'metadata'       => '{}',
                    'schema_version' => $event->schemaVersion(),
                    'version'        => $version,
                    'occurred_on'    => $event->occurredOn()->format('Y-m-d H:i:s.u'),
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

## 13.05 Agregát s Event Sourcingem {#aggregate-s-es}

V klasickém DDD agregát mění svůj stav přímou modifikací vlastních atributů. V Event Sourcingu
**každá změna stavu prochází přes doménovou událost**. Metody agregátu nemodifikují atributy
přímo – nahrají událost a teprve její aplikace na stav vyvolá změnu.

Výsledkem je, že agregát obsahuje dvě sady metod:

- **Mutační metody** (veřejné rozhraní agregátu) – validují invarianty, rozhodují, která událost nastane, a volají interní metodu pro nahrání události (typicky `recordEvent()`).
- **`apply*()` metody** (private/protected) – přijmou konkrétní typ události a aplikují změnu na interní stav. Tyto metody jsou volány jak při nahrávání nové události, tak při replay z Event Store.

Pro testování to znamená vzor **given/when/then** – given jsou historické události, when je
volání metody na agregátu, then jsou nově emitované události. Podrobně v kapitole
[Testování DDD kódu](/testovani-ddd).

:::callout{type="pattern"}
### PHP: Base class EventSourcedAggregate {#es-aggregate-base-heading}

:::code{language="php" filename="src/Shared/Domain/EventSourcedAggregate.php"}
<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Event\DomainEvent;

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
     * Dynamické dispatchování na apply*() metody podle třídy události.
     * Konvence: apply + ShortClassName, napr. applyOrderCreated().
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
    public function releaseDomainEvents(): array
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
*src/Shared/Domain/EventSourcedAggregate.php*
:::

:::callout{type="pattern"}
### PHP: Order agregát s Event Sourcingem {#es-order-aggregate-heading}

:::code{language="php" filename="src/Ordering/Domain/Order.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain;

use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Event\OrderCreated;
use App\Ordering\Domain\Event\OrderItemAdded;
use App\Ordering\Domain\Event\OrderShipped;
use App\Shared\Domain\EventSourcedAggregate;

final class Order extends EventSourcedAggregate
{
    private string $orderId;
    private string $customerId;
    private OrderStatus $status;

    /** @var OrderItem[] */
    private array $items = [];

    private ?string $trackingNumber = null;

    // Statická továrna - vytvoří objednávku ve stavu Draft
    public static function create(string $orderId, string $customerId): self
    {
        $order = new self();
        $order->recordEvent(new OrderCreated($orderId, $customerId));

        return $order;
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new \DomainException('Items can only be added to draft orders.');
        }

        $this->recordEvent(new OrderItemAdded($this->orderId, $item));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new \DomainException('Only draft orders can be confirmed.');
        }
        if (empty($this->items)) {
            throw new \DomainException('Cannot confirm an empty order.');
        }

        $this->recordEvent(new OrderConfirmed($this->orderId));
    }

    public function ship(string $trackingNumber): void
    {
        if ($this->status !== OrderStatus::Confirmed) {
            throw new \DomainException('Only confirmed orders can be shipped.');
        }

        $this->recordEvent(new OrderShipped($this->orderId, $trackingNumber));
    }

    // --- apply* metody - MUSÍ být protected (ne private), aby je base class mohla volat dynamicky ---
    // --- Obsahují POUZE změnu interního stavu, žádnou doménovou logiku ---

    protected function applyOrderCreated(OrderCreated $event): void
    {
        $this->orderId    = $event->orderId();
        $this->customerId = $event->customerId();
        $this->items      = [];
        $this->status     = OrderStatus::Draft;
    }

    protected function applyOrderItemAdded(OrderItemAdded $event): void
    {
        $this->items[] = $event->item();
    }

    protected function applyOrderConfirmed(OrderConfirmed $event): void
    {
        $this->status = OrderStatus::Confirmed;
    }

    protected function applyOrderShipped(OrderShipped $event): void
    {
        $this->status         = OrderStatus::Shipped;
        $this->trackingNumber = $event->trackingNumber();
    }

    // Gettery pro aplikační vrstvu
    public function orderId(): string         { return $this->orderId; }
    public function status(): OrderStatus     { return $this->status; }
    public function trackingNumber(): ?string { return $this->trackingNumber; }
}
:::
*src/Ordering/Domain/Order.php*
:::

### Načítání agregátu z event streamu (replay)

Repozitář pro event-sourcovaný agregát neprovádí SELECT do tabulky entit. Místo toho načte
event stream z Event Store a předá jej statické tovární metodě `reconstituteFromEvents()`.
Výsledný agregát má přesně takový stav, jaký odpovídá historii jeho událostí.

:::diagram{fig="14.5-A" title="Replay agregátu z event streamu" src="images/diagrams/14_event_sourcing/event_store_replay.svg"}
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
        $newEvents = $order->releaseDomainEvents();

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

## 13.06 Projekce (Projections) {#projekce}

Event Store je append-only a neumí ad-hoc dotazy typu „všechny objednávky zákazníka X
s celkovou hodnotou nad 1000 Kč“. Pro takové dotazy vznikají vedle něj **projekce** –
denormalizované read modely budované z event streamu specificky pro tvar dotazů aplikace.

:::diagram{fig="14.6-A" title="Tok eventu z agregátu do read modelu přes projektor" src="images/diagrams/14_event_sourcing/projection_lifecycle.svg"}
:::

### Synchronní vs. asynchronní projekce

- **Synchronní projekce** – Projekce se aktualizuje přímo v téže transakci jako zápis události. Garantuje konzistenci dat v okamžiku odpovědi na command, ale zvyšuje latenci zápisu a zavádí těsnou vazbu mezi write a read stranou.
- **Asynchronní projekce** – Události jsou po uložení do Event Store zařazeny do fronty (Symfony Messenger + transport jako RabbitMQ nebo Redis). Projector je konzument, který zpracovává zprávy nezávisle. Read model je v krátkém časovém okně nekonzistentní (eventual consistency), ale write side je rychlejší a oddělená.

:::callout{type="pattern"}
### PHP: OrderSummaryProjection a asynchronní Projector {#projekce-php-heading}

:::code{language="php" filename="src/Infrastructure/Ordering/Projection/OrderSummaryProjector.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Ordering\Projection;

use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Event\OrderCreated;
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
    public function __invoke(OrderCreated $event): void
    {
        $this->connection->insert('order_summary', [
            'order_id'      => $event->orderId(),
            'customer_id'   => $event->customerId(),
            'status'        => 'draft',
            'item_count'    => 0,
            'total_amount'  => 0,
            'placed_at'     => $event->occurredOn()->format('Y-m-d H:i:s'),
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
            ['price' => $event->item()->unitPrice(), 'orderId' => $event->orderId()],
        );
    }

    #[AsMessageHandler]
    public function handleOrderConfirmed(OrderConfirmed $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_summary SET status = :status WHERE order_id = :orderId',
            ['status' => 'confirmed', 'orderId' => $event->orderId()],
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
                'shippedAt'  => $event->occurredOn()->format('Y-m-d H:i:s'),
                'trackingNo' => $event->trackingNumber(),
                'orderId'    => $event->orderId(),
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
            'App\Ordering\Domain\Event\OrderCreated':    async
            'App\Ordering\Domain\Event\OrderItemAdded': async
            'App\Ordering\Domain\Event\OrderConfirmed': async
            'App\Ordering\Domain\Event\OrderShipped':   async
:::
*config/packages/messenger.yaml*
:::

Projekce lze **přebudovat** (rebuild) přehráním celého Event Store od začátku. Při změně
doménových požadavků stačí vytvořit novou projekci a přehrát historii. CRUD systémy tuto
možnost nemají – historická data v nich už nejsou k dispozici.

## 13.07 Outbox a transakční doručování událostí {#outbox}

Předchozí sekce ukazovala projektory jako Messenger handlery, které dostávají doménové
události z asynchronní fronty. Implicitně jsme předpokládali, že se událost po zápisu
do Event Store spolehlivě dostane do message brokeru. V produkci to bez další infrastruktury
neplatí. Zápis do databáze a publikace zprávy do brokeru jsou dvě nezávislé operace a nelze
je obalit jedinou transakcí. V literatuře se tento problém označuje jako
*dual-write problem* a řeší ho **Outbox
pattern** [[1]](https://microservices.io/patterns/data/transactional-outbox.html).

### Dual-write problém {#dual-write-heading}

Představte si pořadí kroků v `save()` metodě repozitáře, který nejprve commitne
transakci do Event Store a hned poté volá `$bus->dispatch($event)`:

- Pokud server spadne *mezi* commitem a dispatchem, událost je v databázi, ale nikdy se nedostane k projektorům či externím konzumentům. Read modely se rozejdou se stavem write strany.
- Pokud naopak provedete dispatch *před* commitem a transakce se rollbackuje, konzumenti zpracují událost, která se nikdy nestala. Vznikají duplicity, které se obtížně dohledávají.
- Při restartu workeru, dočasné nedostupnosti brokeru nebo síťovém partition se chyba projeví latentně až po hodinách provozu.

Outbox pattern problém přesouvá tam, kde si s ním databáze poradí: událost se zapíše do
téže transakce jako doménová změna a samostatný proces (relay) ji následně přečte a publikuje
do brokeru. Atomicitu zápisu hlídá databáze, doručení do brokeru zajišťuje relay
s mechanismem at-least-once.

### Event Store jako outbox {#es-outbox-heading}

V Event Sourcingu už tabulka `event_store` sama o sobě splňuje všechny vlastnosti outbox
tabulky. Je append-only, má auto-increment `id` pro globální uspořádání zápisů a každý záznam
je zapsán ve stejné transakci jako odpovídající doménová změna. Druhá tabulka tedy nepřibývá –
stačí přidat **relay worker**, který sleduje nové řádky a posílá je do Messengeru.

Worker si pamatuje pozici posledního publikovaného řádku v jednoduché checkpoint tabulce.
Při restartu pokračuje od uloženého `id`, takže ani opakovaný start nevynechá
ani neduplikuje událost na úrovni publikace (duplicitu na straně konzumentů řeší idempotence
popsaná v následující sekci).

Spuštění relay v produkci řešte buď cron jobem volajícím `publishPending()`
v krátkém intervalu (např. po sekundě), nebo dlouho běžícím procesem v supervisoru, který
mezi iteracemi spí podobný interval. Lock `FOR UPDATE` na řádku
`outbox_position` garantuje, že více souběžných instancí relay nebude publikovat
stejné události paralelně.

### Záruky doručení a jejich důsledky {#outbox-zaruky-heading}

Outbox dává **at-least-once** doručení uvnitř jednoho kanálu se zachovaným
globálním pořadím podle `event_store.id`. Konkrétně:

- **At-least-once:** pokud relay spadne mezi dispatchem a updatem checkpointu, stejná událost se po restartu publikuje znovu. Konzumenti musí být idempotentní – přesně tak, jak ukazuje následující sekce u projektorů.
- **Globální pořadí:** události jsou publikovány vzestupně podle `id`, takže projektor uvidí `OrderCreated` před `OrderShipped`. Pořadí napříč streamy různých agregátů ale není zajištěno – pokud ho projekce potřebuje, musí ho odvodit z `occurred_on` nebo korelačního ID.
- **Latence:** mezi commitem události a jejím doručením k projektoru vzniká okno odpovídající polling intervalu relay. V praxi 100 ms až 1 s; pro nižší latenci přepněte na výstupní transport, který umí push (např. PostgreSQL `LISTEN/NOTIFY` nebo Debezium).

:::callout{type="note"}
Outbox pattern je užitečný i mimo Event Sourcing. V čistě Doctrine ORM aplikaci lze
outbox řešit jako separátní tabulku, do níž zapisujete událost přímo z Doctrine
`onFlush` listeneru – tedy v téže transakci jako agregát. Praktickou
implementaci s kompletním kódem najdete v
[DDD v praxi – B1: Outbox pattern](/ddd-v-praxi-kde-to-boli#b1-outbox).
Před vlastní implementací zvažte také **Doctrine Transport** v Symfony Messenger,
který ukládá zprávy do databáze a poskytuje at-least-once doručení bez vlastního relay kódu.
:::

## 13.08 Praktické problémy projekcí {#prakticke-problemy-projekci}

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

:::callout{type="pattern"}
### PHP: Idempotentní projektor s tracking tabulkou {#idempotent-projector-heading}

:::code{language="php" filename="src/Infrastructure/Ordering/Projection/IdempotentOrderProjector.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Ordering\Projection;

use App\Ordering\Domain\Event\OrderCreated;
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

    public function __invoke(OrderCreated $event): void
    {
        // Atomická kontrola + záznam: INSERT IGNORE vrátí 0 affected rows
        // pokud eventId již existuje → událost byla již zpracována.
        $affected = $this->connection->executeStatement(
            'INSERT IGNORE INTO projection_checkpoint (event_id, projection_name, processed_at)
             VALUES (:eventId, :projection, NOW(6))',
            ['eventId' => $event->eventId(), 'projection' => 'order_summary'],
        );

        if ($affected === 0) {
            return; // Duplicitní doručení - přeskočíme
        }

        // Vlastní projekční logika
        $this->connection->insert('order_summary', [
            'order_id'     => $event->orderId(),
            'customer_id'  => $event->customerId(),
            'status'       => 'draft',
            'item_count'   => 0,
            'total_amount' => 0,
            'placed_at'    => $event->occurredOn()->format('Y-m-d H:i:s'),
        ]);
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

### Chybové stavy a retry strategie

Projektor může selhat z mnoha důvodů: dočasná nedostupnost databáze, neplatný payload
u staré události bez upcasteru, nebo bug v projekční logice. Symfony Messenger nabízí
dvě hlavní mechaniky pro řešení:

- **Retry transport** – zpráva se po selhání automaticky vrátí do fronty s exponenciálním backoffem (výchozí: 3 pokusy s násobičem 2).
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
            'App\Ordering\Domain\Event\OrderCreated':    async
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
     * @param iterable<string, callable> $projectors Symfony tagged_iterator
     * @param array<string, string>      $projectionTables Mapa: název projekce → název tabulky
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

        // 3. Přehrát všechny události z Event Store
        $projector = $config['projector'];
        $count = 0;
        $batchSize = 500;

        foreach ($this->eventStore->loadAll($batchSize) as $envelope) {
            $event = $this->serializer->toEvent($envelope);
            $projector($event);
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
událostí zvažte dávkové zpracování s `--batch-size` a monitoring paměti.
:::

### Eventual consistency a uživatelské rozhraní

Asynchronní projekce vytváří časové okno (typicky milisekundy až jednotky sekund), kdy uživatel
provede akci – například potvrdí objednávku – ale read model ještě nemá aktualizovaná data.
Po kliknutí na „Potvrdit“ se na výpisu může objevit stále „Draft“.

Není to bug, ale vlastnost eventual consistency. V UI ji lze adresovat třemi
zaběhnutými způsoby:

- **Optimistická aktualizace UI** – Frontend po úspěšné odpovědi na command okamžitě zobrazí očekávaný stav (např. „Potvrzeno“), aniž čeká na aktualizaci projekce. Nejčastější řešení.
- **Potvrzovací stránka** – Po provedení akce přesměrovat uživatele na stránku, která nezávisí na projekci (např. „Objednávka č. X byla potvrzena“), místo okamžitého návratu na výpis.
- **Polling / SSE** – Frontend periodicky dotazuje API nebo naslouchá Server-Sent Events, dokud projekce nedorazí do požadovaného stavu.

:::callout{type="note"}
### Synchronní projekce jako pragmatický kompromis {#ec-note-heading}

Pokud vaše aplikace nemá vysokou zátěž na write straně a latence zápisu je přijatelná,
je legitimní začít se **synchronními projekcemi** a na asynchronní přejít
až ve chvíli, kdy se synchronní aktualizace stane úzkým hrdlem. Vyhýbáte se tak problémům
s eventual consistency v raných fázích projektu.
:::

## 13.09 Snapshotting {#snapshotting}

Se stárnutím systému rostou event streamy agregátů. Agregát s tisíci událostmi vyžaduje
při každém command handleru načtení a přehrání tisíce řádků z databáze. Výkonnostní problém
se v provozu objeví dřív, než tým čeká.

Vzor **snapshotting** uchová aktuální stav agregátu v pravidelných intervalech – po každých
N událostech nebo časově. Při příštím načtení repozitář vyhledá poslední snapshot a z Event
Store dotáhne jen události novější než tento snapshot.

:::diagram{fig="14.9-A" title="Snapshot strategie: zhuštěný stav místo plného replay" src="images/diagrams/14_event_sourcing/snapshot_strategy.svg"}
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
            $aggregate   = Order::reconstituteFromSnapshot($snapshot->state);
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
        $newEvents = $order->releaseDomainEvents();

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
(serializace aktuálního stavu) a statickou `reconstituteFromSnapshot(array $state): static`
(deserializace). Na rozdíl od `reconstituteFromEvents()` tato metoda nevytváří apply*()
volání – přímo nastaví properties z uloženého snímku. Je proto nezbytné zajistit, aby se formát
snapshotu vyvíjel v souladu se změnami doménového modelu.

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

## 13.10 Verzování událostí (Event Versioning) {#verzovani-udalosti}

Události v Event Store jsou **permanentní** – jednou uložené se nemažou ani nepřepisují.
Doménový model se přitom v čase vyvíjí: přibývají atributy, mění se struktura dat, původní
pole se rozdělují nebo slučují. Otázka tedy zní: **jak přečíst starou událost novým kódem?**

Odpověď je **event versioning** – strategie, která zachovává zpětnou čitelnost starých
událostí i po změně jejich schématu. Nejrozšířenějším vzorem je **upcasting**: při
deserializaci se starší verze payloadu transformuje na aktuální formát, takže doménový model
pracuje pouze s nejnovější verzí.

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

V praxi se `UpcasterChain` integruje do `EventSerializer`: při deserializaci
se z uloženého záznamu přečte `event_type` a `schema_version`, payload projde
řetězem upcasterů a teprve výsledná transformovaná data se předají konstruktoru aktuální třídy události.

:::callout{type="note"}
### Weak vs. strong schema strategie {#schema-strategie-heading}

Pro tvar payloadu existují dva přístupy, které se v praxi míchají:

- **Weak schema (slabé schéma)** – Payload je uložen jako volný JSON bez formální definice. Upcasters transformují data ad-hoc. Výhodou je flexibilita a rychlost vývoje; nevýhodou je, že chyby v transformaci se projeví až za běhu a je obtížné ověřit konzistenci napříč verzemi.
- **Strong schema (silné schéma)** – Každá verze události má explicitně definované schéma (např. pomocí JSON Schema nebo PHP třídy s validací). Upcaster pak transformuje mezi dvěma dobře definovanými strukturami. Výhodou je vyšší bezpečnost a možnost automatického testování kompatibility; nevýhodou je vyšší režie při každé změně schématu.

Pro většinu projektů je rozumným kompromisem **kombinace obou přístupů**: silné schéma
pro kritické události v Core Doméně (finanční transakce, stavy objednávek) a slabé schéma
pro méně kritické události v podpůrných kontextech (notifikace, logy aktivit).
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

Tři možné cesty, podle závažnosti:

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
během přechodného období (aplikace zapisuje do obou streamů, dokud migrace nedokončí).
:::

:::callout{type="pattern"}
### Strategie 2: Multi-version event store {#multi-version-heading}

V Event Store fyzicky koexistují **obě verze** schémat. Repozitář při hydration
vybere podle `aggregate_version` mark, který stream číst. Nově vzniklé agregáty
zapisují v3, staré dál ve v1. Přepnutí na nový tvar nastane teprve při příští
domain operation (lazy migration).

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
Po pár letech provozu je 5–10 % event types historických oprav.
:::

### Stream archivation a storage tiering {#archivation-heading}

Long-lived agregáty (`UserAccount`, `Subscription`, `LedgerAccount`) generují
po letech provozu desítky až stovky tisíc eventů. Aktivní Event Store tabulka
roste, queries pomalují, snapshots musí být časté.

Standardní řešení: **storage tiering** podle stáří streamu.

- **Hot tier** (PostgreSQL master) – události za posledních 90 dní, dotazy < 10 ms.
- **Warm tier** (Postgres replica nebo Doctrine on slow disk) – události 90 dní – 2 roky.
  Hydration sahá sem jen pro forensické dotazy nebo plný replay projekce.
- **Cold tier** (S3, Glacier, on-prem object storage) – události starší než 2 roky.
  Read-only, accessed jen pro auditní reporty a compliance.

Implementace: každou noc se spustí job, který
přesune `event_store` řádky starší než N dní do `event_store_archive` tabulky
(nebo přímo do S3 jako Parquet). Repozitář při hydration **ve výchozím nastavení cold tier nečte** – pokud agregát potřebuje plný replay, operátor explicitně rehydratuje
ze snapshotu novějšího než cold cutoff. Pro audit dotazy funguje zvlášť query
service, který umí číst všechny tři tiers.

:::callout{type="warn"}
### GDPR a immutable Event Store {#gdpr-event-store-heading}

Event Store je per definitionem append-only, ale GDPR požaduje právo na výmaz
(article 17). Konflikt řeší **crypto shredding**: osobní údaje v eventech
jsou zašifrovány klíčem per-subject. Po žádosti o výmaz se zničí klíč,
události zůstávají, ale jsou nečitelné.

Implementace v PHP: každý subject (uživatel) má v separátní tabulce
`subject_keys` symetrický klíč. Doménová událost při serializaci
zašifruje PII pole (`email`, `name`, `address`) tímto klíčem; zbytek
payloadu zůstává čitelný (audit trail funguje). Smazání klíče = právo na
zapomnění, audit zachycen na úrovni „uživatel #42 učinil akci v čase T“,
ale identifikace uživatele není možná.

Detail v sekci [GDPR a osobní údaje v Event Store](#gdpr-es-heading).
:::

## 13.11 Kdy použít Event Sourcing {#kdy-pouzit}

Event Sourcing přidává konkrétní možnosti – auditní log, replay, temporální dotazy – výměnou
za vyšší složitost infrastruktury i kódu. Před jeho zavedením zvažte, zda v daném kontextu
přínosy převažují nad náklady na implementaci a provoz.

### Vhodné případy užití

- **Auditní log jako doménový požadavek** – Finanční systémy, zdravotnické záznamy nebo jakákoli doména, kde je zákonná povinnost uchovávat kompletní historii změn. Auditní log v ES vychází přímo z formátu úložiště – nepotřebuje samostatnou implementaci.
- **Komplexní doménová logika s bohatými stavovými přechody** – Agregáty procházejí mnoha stavy, každý přechod má svou sémantiku a musí být rekonstruovatelný. Typicky: objednávkové systémy, workflow enginy, bankovní transakce.
- **Temporální dotazy** – Potřeba „přehrát“ stav systému k libovolnému bodu v minulosti (debugging, analýza, „what-if“ scénáře). U ES stačí replay eventů do daného timestampu.
- **Event-driven integrace** – Systém produkuje události, které konzumují jiné bounded contexts nebo externí systémy. ES zajišťuje, že žádná událost nebude ztracena – Event Store je zdrojem pravdy pro integraci.
- **CQRS s vysokou čtecí zátěží** – ES umožňuje vybudovat libovolný počet optimalizovaných read modelů z jednoho event streamu, aniž by bylo nutné měnit write model.

### Nevhodné případy užití

- **Jednoduché CRUD aplikace** – Pokud doménová logika spočívá v základních operacích Create/Read/Update/Delete bez složitých stavových přechodů, ES přináší jen zbytečnou složitost.
- **Systémy orientované převážně na reporting** – Pokud je primárním požadavkem rychlé čtení a agregace dat (BI, analytics), jsou vhodnější klasická DW řešení nebo OLAP databáze.
- **Prototypy a MVP** – Rychlá validace produktového nápadu nepotřebuje složitou infrastrukturu. ES lze přidat do zralého systému inkrementálně, pokud se ukáže potřeba – viz [Migrace z CRUD](/migrace-z-crud).
- **Týmy bez zkušeností s ES** – Implementace Event Sourcingu bez předchozí zkušenosti přináší vysoké riziko chyb v kritické infrastruktuře (Event Store, serializace, versioning). Doporučuje se začít s menším bounded contextem jako experimentem.

:::callout{type="warn"}
### Varování: Event Sourcing výrazně zvyšuje složitost systému {#es-warning-heading}

Event Sourcing CRUD nenahrazuje. Cenu zaplatíte na všech úrovních:
**infrastruktura** (Event Store, event bus, snapshot store),
**doménový model** (apply metody, immutabilita událostí, verzování schémat),
**testování** ([given/when/then scénáře](/testovani-ddd) s event streamy) a
**provoz** (migrace schémat událostí, rebuildy projekcí, monitoring lag asynchronních
projektorů). Podrobněji o výkonnostních dopadech pojednává kapitola
[Výkonnostní aspekty](/vykonnostni-aspekty).

Nepoužívejte Event Sourcing paušálně pro celou aplikaci. V DDD se ES nasazuje
**selektivně na bounded contexts**, kde se vrátí investice – typicky Core Domain
s komplexní doménovou logikou. Ostatní kontexty mohou nadále používat klasickou CRUD persistenci.
Časté chyby při zavádění ES shrnuje kapitola [Anti-vzory](/anti-vzory).
:::

:::faq{}
- question: Co je Event Sourcing?
  answer: 'Event Sourcing je přístup k persistenci stavu, při kterém se neukládá aktuální snímek dat, ale append-only sekvence neměnných událostí, které k aktuálnímu stavu vedly. Aktuální stav agregátu vzniká přehráním těchto událostí od počátku, což poskytuje úplný audit trail a možnost zpětně rekonstruovat jakýkoli stav v čase. Platí princip „current state is derived from the history of events“: nic se v event logu nikdy nepřepisuje ani nemaže. Viz <a href="#co-je-event-sourcing">úvodní sekci</a>.'
- question: Jaký je vztah mezi Event Sourcingem a CQRS?
  answer: 'Event Sourcing a CQRS jsou dva nezávislé vzory, které se často kombinují. Každý z nich lze zavést samostatně: CQRS funguje i s klasickou ORM persistencí, ES lze implementovat i bez rozdělení na write a read modely. V praxi se však hodí dohromady, protože ES přirozeně vede k oddělení zápisu (event store) a čtení (projekce do read modelů) – což je přesně myšlenka CQRS. Více v <a href="#vztah-k-cqrs">sekci Vztah k CQRS</a>.'
- question: Co je Event Store a k čemu slouží?
  answer: 'Event Store je specializované append-only úložiště, které persistuje doménové události jednotlivých agregátů chronologicky seřazené. Typicky poskytuje dotazy na event stream konkrétního agregátu pro jeho rekonstrukci a globální dotaz pro čtení událostí všemi projekcemi. Základní metody jsou <code>append(streamId, events)</code> a <code>readStream(streamId)</code>; pokročilejší řešení zahrnují optimistické zamykání verzí a publikování událostí do event busu. Implementačně může jít o specializovaný produkt (EventStoreDB, Marten) nebo nadstavbu nad relační databází. Detailní rozbor v <a href="#event-store">sekci Implementace Event Store</a>.'
- question: Co jsou projekce v Event Sourcingu?
  answer: 'Projekce je proces, který naslouchá událostem z event store a buduje z nich read modely – denormalizované datové struktury určené pro rychlé dotazy. Projekce bývá jednoúčelová: každý read model má obvykle vlastní projekci, která ho od začátku nebo od posledního zpracovaného offsetu udržuje aktuální. Projekce lze kdykoli přebudovat (rebuild) přehráním událostí od počátku, čímž se bezpečně opravují chyby v read modelech. Praktický příklad v <a href="#projekce">sekci Projekce</a>.'
- question: K čemu slouží snapshotting v Event Sourcingu?
  answer: 'Snapshotting je technika, při které se periodicky ukládá serializovaný stav agregátu, aby se při jeho rekonstrukci nemuselo přehrávat celé event history od začátku. Při načtení se vezme poslední snapshot a aplikují se pouze události, které nastaly po něm. Snapshoty řeší výkonnostní problém dlouhých streamů, typicky u agregátů s řádově tisíci událostí – pro krátké streamy jsou zbytečné a přidávají operační komplexitu. Podrobný rozbor v <a href="#snapshotting">sekci Snapshotting</a>.'
- question: Kdy se vyplatí Event Sourcing nasadit?
  answer: 'Event Sourcing se vyplatí tam, kde je historie změn sama o sobě doménově cenná – finanční systémy, sklady, auditované procesy, regulovaná odvětví – nebo kde je třeba rekonstruovat stav v libovolném bodě minulosti. Nevhodný je pro prototypy, MVP a prosté CRUD aplikace. Nasazuje se zpravidla selektivně na jeden bounded context, nikoli plošně na celou aplikaci. Rozhodovací kritéria v <a href="#kdy-pouzit">sekci Kdy použít Event Sourcing</a>.'
:::
