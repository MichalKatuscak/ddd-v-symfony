---
route: outbox_pattern
path: /outbox-pattern
title: 'Outbox Pattern – spolehlivé publikování doménových eventů'
page_title: "Outbox Pattern – spolehlivé publikování doménových eventů v Symfony | DDD Symfony"
meta_description: "Transactional Outbox + Idempotent Inbox v Symfony 8 a Doctrine: jak zajistit at-least-once delivery doménových eventů, eliminovat dual-write problém a co o tom říká Pat Helland a Chris Richardson."
meta_keywords: "Outbox Pattern, Transactional Outbox, Inbox Pattern, Idempotency, Dual-write problem, Pat Helland, Chris Richardson, Symfony Messenger, Doctrine, at-least-once, exactly-once, RabbitMQ, eventy, CDC, Debezium"
og_type: article
published: "2026-04-29"
modified: "2026-04-29"
breadcrumb_name: Outbox Pattern
schema_type: TechArticle
schema_headline: "Outbox Pattern – spolehlivé publikování doménových eventů"
chapter_number: "16"
category: Vzory
deck: 'Typická chyba: zapíšete <code>Order</code> do databáze, vzápětí se rozbije RabbitMQ, ale order tam zůstane bez události <code>OrderPlaced</code>. Subscribeři se o objednávce nedozvědí. Outbox Pattern řeší tento <em>dual-write problem</em> na úrovni jedné DB transakce; jeho dvojče Inbox Pattern řeší deduplikaci na straně subscriberů. V Symfony 8 je to jeden Doctrine entity manager, jeden Messenger transport a zhruba 80 řádků kódu.'
reading_time: 28
difficulty: 4
github_examples: Chapter11_OutboxPattern
---

V kapitolách o [CQRS](/cqrs), [Event Sourcingu](/event-sourcing)
a [ságách](/sagy-a-process-managery) jsme opakovaně narazili na stejný předpoklad:
když agregát po commitu publikuje doménovou událost, tato událost se **spolehlivě dostane
do message brokeru** a odtud k subscriberům. Tento předpoklad je ovšem zrádný. Mezi
zápisem do databáze a dispatchem do Messenger transportu je síťový skok a dva nezávislé
systémy – a každý z nich může selhat samostatně. Důsledkem je *dual-write problem*,
jeden z nejčastějších zdrojů tichých nekonzistencí v event-driven architekturách.

**Transactional Outbox Pattern** je kanonické řešení dual-write problému;
jeho symetrický protějšek **Idempotent Inbox Pattern** zajišťuje deduplikaci
na straně subscriberů. V dalších sekcích si projdeme původ vzoru v práci Pata Hellanda
*Life Beyond Distributed Transactions* (2007), schéma outbox tabulky s povinným
indexem, kompletní implementaci s Doctrine ORM a Symfony Messenger, dvě varianty relay
procesu (polling worker vs. CDC / Debezium) a operační aspekty – outbox lag, kompakce,
dead-letter queue. V závěru přidáme migrační postup pro existující projekt a srovnání
s alternativami.

## 16.01 Dual-write problem {#dual-write}

Nejjednodušší implementace publikování doménové události vypadá nevinně: po dokončení
doménové operace zapíšeme stav do databáze a pak rovnou dispatchneme událost na message
bus. Code review takový kód projde bez poznámek – dokud se v produkci nezačnou hromadit
ztracené události a stížnosti subscriberů typu „*vidím v API objednávku
12345, ale event `OrderPlaced` mi nikdy nedorazil*“.

:::callout{type="warn"}
### Naivní implementace publikování – anti-vzor {#naive-publish-heading}

:::code{language="php" filename="src/Ordering/Application/Handler/PlaceOrderHandlerNaive.php" highlights="22,25"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\PlaceOrder;
use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class PlaceOrderHandlerNaive
{
    public function __construct(
        private OrderRepository $orders,
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(PlaceOrder $command): void
    {
        $order = Order::place($command->customerId, $command->items);

        // 1) Zápis do DB (commit Doctrine).
        $this->orders->save($order);

        // 2) Publish do brokeru (samostatný systém, samostatná chyba).
        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
:::
:::

Problém je v tom, že **krok 1 a krok 2 jsou dvě nezávislé transakce ve dvou
různých systémech**. Jakmile mezi nimi dojde k jakékoliv chybě – síťový timeout,
crash workeru, restart aplikace, výpadek brokera, OOM kill PHP procesu – skončíme
v jednom ze dvou nesymetrických nekonzistentních stavů:

- **DB write succeeded, broker dispatch failed.** Order existuje v databázi,
  ale event `OrderPlaced` se nikdy neodeslal. Subscriber kontext (Payment,
  Warehouse, Notifications) o objednávce *neví*. Zákazník ji vidí v API,
  ale platba se nestrhne, sklad nezarezervuje, e-mail nepřijde. Tichá ztráta
  doménové události. Nejhorší scénář, protože v logu nezůstane žádná stopa
  „chybějící“ události.
- **Broker dispatch succeeded, DB write failed.** Vyskytne se, pokud někdo
  otočí pořadí (publish před commit) nebo pokud commit selže *po* dispatchi
  kvůli optimistickému locku. Subscribery dostanou event o objednávce, která fakticky
  neexistuje. Read model si přidá řádek, Payment se pokusí strhnout peníze za
  neexistující order, Notifications odešle e-mail s odkazem na 404. „Phantom event“,
  který se ve zdrojové DB *nestal*.

Oba scénáře jsou klasická porušení atomicity napříč dvěma systémy a v event-driven
architekturách jsou pravidlem, ne výjimkou. Pat Helland v práci
*Life Beyond Distributed Transactions: An Apostate's Opinion* (2007) tento
problém formuloval explicitně. Jakmile transakce přesahuje hranici jednoho úložiště,
atomicita je iluze. Aplikační logika ji musí explicitně obnovit.
Chris Richardson na něj navázal v knize *Microservices Patterns* (2018, kapitola 3),
kde Outbox Pattern popisuje jako *jediné* doporučované řešení dual-write problému
v mikroslužbách bez nadbytečné distribuované transakce.

:::callout{type="note"}
### Proč ne Two-Phase Commit (2PC / XA)? {#2pc-heading}

Distribuované databáze a některé brokery nabízejí protokol
**Two-Phase Commit** (2PC), implementovaný typicky přes XA. V první fázi
(*prepare*) se všichni účastníci ptají, zda mohou commitnout; ve druhé fázi
(*commit*) koordinátor rozhodne o globálním commitu nebo rollbacku. Teoreticky
bychom mohli RabbitMQ a PostgreSQL zapojit do jedné XA transakce a problém by zmizel.
Praxe je ale jiná:

- **Většina dnešních brokerů XA nepodporuje.** RabbitMQ má částečnou
  podporu přes plugin, Kafka nemá XA vůbec, Redis Streams ani neuvažuje. Jakmile
  použijete cloudovou verzi (AWS SNS/SQS, Google Pub/Sub), XA je definitivně mimo
  hru. Závazek na XA-only brokery vážně omezuje volbu infrastruktury.
- **XA je drahé.** Účastníci drží zámky po celou dobu obou fází –
  propustnost klesá řádově. Helland v citovaném paperu shrnuje: „*2PC je
  daň z každé operace, kterou platíte, i když se nikdy nic nerozbije*“.
- **Single point of failure.** Koordinátor 2PC je kritické místo;
  jeho selhání mezi fázemi prepare a commit zanechá účastníky v *in-doubt*
  stavu, kdy ani nelze rollbacknout, ani commitnout. Je třeba manuální zásah –
  v 3 hodiny ráno.
- **Těsné provázání porušuje autonomii Bounded Contexts.** XA vyžaduje,
  aby všichni účastníci sdíleli koordinátora. To přímo odporuje principu
  samostatně nasaditelných kontextů, který je jádrem
  [DDD](/zakladni-koncepty#bounded-contexts)
  i [mikroslužeb](/ddd-a-microservices).

Outbox Pattern je **levnější, spolehlivější a infrastrukturně neutrální**
náhrada XA: vystačíme si s jednou ACID transakcí v jedné DB, kterou stejně používáme
pro persistenci agregátu.
:::

*Citace:
Helland, P. **Life Beyond Distributed Transactions: An Apostate's Opinion**,
CIDR (2007); Richardson, C. **Microservices Patterns**, Manning (2018),
kapitola 3 – Transactional messaging; Microservices.io –
[Pattern: Transactional Outbox](https://microservices.io/patterns/data/transactional-outbox.html).*

## 16.02 Transactional Outbox – princip {#princip}

Princip Outbox Pattern je prostý: **nepublikujeme událost přímo do
brokera, ale zapíšeme ji do tabulky `outbox`** ve stejné databázi,
kde žije doménový stav. Zápis proběhne *uvnitř stejné DB transakce* jako úprava agregátu.
Buď se tedy úspěšně zapíše obojí (order i jeho event), nebo se nezapíše nic (rollback
celé transakce). Atomicita je obnovena – oba zápisy jsou v jediném ACID kontextu
jedné databáze, ne ve dvou různých systémech.

Samostatný proces (**relay worker**, někdy nazývaný *publisher*
nebo *dispatcher*) tabulku asynchronně polluje. Vybírá řádky se stavem
`pending` a publikuje je do skutečného message brokeru. Po úspěšném publishi
řádek označí jako `sent`. Tok má čtyři jasně oddělené fáze:

:::diagram{fig="16.2-A" title="Transactional Outbox – čtyři fáze publikování" src="images/diagrams/14_outbox/outbox_flow.svg"}
:::

1. **Fáze 1 – doménová transakce.** Application handler v jedné Doctrine
   transakci uloží agregát i odpovídající outbox řádky. Buď oboje, nebo nic.
2. **Fáze 2 – polling outboxu.** Relay worker periodicky (např. každých
   100 ms) selectuje pending řádky z outboxu, seřazené podle `occurred_at`,
   aby zachoval kauzální pořadí uvnitř jedné DB.
3. **Fáze 3 – publish do brokeru.** Pro každý řádek relay publishne event
   do brokera a po obdržení ACK řádek označí jako `sent`. Obě operace nejsou
   v jedné transakci – pokud crashne mezi nimi, řádek zůstane `pending`
   a po restartu se publishne znovu. Z toho plyne základní garance:
4. **Fáze 4 – konzumace subscriberem.** Subscriber dostane delivery,
   zpracuje ji idempotentně (typicky přes [Inbox Pattern](#inbox)) a
   ackne brokerovi.

:::callout{type="note"}
### Garance Outbox Pattern: at-least-once delivery {#at-least-once-heading}

Outbox samotný garantuje **at-least-once delivery** – každá doménová
událost se k subscriberům dostane *alespoň jednou*, ale může se stát, že
i víckrát. Konkrétní scénář duplikace: relay úspěšně publishne event do brokera
(broker poslal ACK, event je trvale uložen). Relay ale crashne *před* tím,
než stihne zapsat `UPDATE outbox SET status='sent'`. Po restartu vidí
řádek pořád jako `pending` a publishne ho znovu. Subscriber tak dostane
stejný event dvakrát.

Toto je *záměrná* volba: přijímáme možnost duplikace výměnou za to, že žádný
event neztratíme. **Exactly-once delivery v distribuovaných systémech
obecně neexistuje** (viz Two Generals' Problem, FLP impossibility).
Co lze v praxi dosáhnout, je *exactly-once efekt* na straně subscribera –
a o to se postará [Idempotent Inbox](#inbox).
:::

## 16.03 Schéma `outbox` tabulky a Doctrine mapping {#schema}

Outbox tabulka má málo sloupců, ale **každý z nich je nezbytný**.
Vynechání kteréhokoli z nich vede k provozním problémům, které se projeví až pod zátěží.
Začneme schématem entity, projdeme význam jednotlivých sloupců a vysvětlíme, proč musí
existovat kompozitní index `(status, occurred_at)`.

:::callout{type="pattern"}
### PHP: Doctrine entita OutboxMessage {#outbox-message-entity-heading}

:::code{language="php" filename="src/Outbox/Domain/OutboxMessage.php" highlights="11,12"}
<?php

declare(strict_types=1);

namespace App\Outbox\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'outbox')]
#[ORM\Index(columns: ['status', 'occurred_at'], name: 'idx_outbox_status_time')]
class OutboxMessage
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'ulid', unique: true)]
        public Ulid $id,

        /** Plně kvalifikovaný název třídy doménové události. */
        #[ORM\Column(type: 'string', length: 255)]
        public string $messageType,

        /** Serializovaný payload události (JSON_UNESCAPED_UNICODE). */
        #[ORM\Column(type: 'json')]
        public array $payload,

        /** pending | sent | failed */
        #[ORM\Column(type: 'string', length: 16)]
        public string $status = 'pending',

        #[ORM\Column(type: 'datetime_immutable')]
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),

        #[ORM\Column(type: 'integer')]
        public int $attempts = 0,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        public ?\DateTimeImmutable $sentAt = null,

        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $lastError = null,
    ) {}

    public function markSent(\DateTimeImmutable $now): void
    {
        $this->status = 'sent';
        $this->sentAt = $now;
        $this->lastError = null;
    }

    public function markFailed(string $error): void
    {
        $this->attempts += 1;
        $this->status = $this->attempts >= 5 ? 'failed' : 'pending';
        $this->lastError = $error;
    }

    public static function fromDomainEvent(object $event, callable $serializer): self
    {
        return new self(
            id: new Ulid(),
            messageType: $event::class,
            payload: $serializer($event),
        );
    }
}
:::
:::

### Význam jednotlivých sloupců {#vyznam-sloupcu-heading}

| Sloupec | Typ | Účel |
|---|---|---|
| `id` | ULID (16 B) | Unikátní identifikátor řádku – slouží zároveň jako **event_id** pro deduplikaci na straně subscribera (viz Inbox). |
| `message_type` | VARCHAR(255) | FQCN doménové události (např. `App\Ordering\Domain\Event\OrderPlaced`). Relay podle něj namapuje payload zpět na PHP třídu. |
| `payload` | JSON / JSONB | Serializovaný stav události. JSONB v Postgresu je preferovaný – umožňuje indexovat jednotlivá pole pro debugging. |
| `status` | VARCHAR(16) | Stavový enum: `pending` (čeká na publish), `sent` (úspěšně publishnuto), `failed` (po N pokusech vzdáno, vyžaduje manuální resolve). |
| `occurred_at` | TIMESTAMPTZ | Čas vzniku události v doménové transakci. Slouží pro řazení v relayi (FIFO uvnitř jedné DB) a pro výpočet outbox lagu. |
| `attempts` | INT | Počet neúspěšných pokusů o publish. Po překročení prahu (typicky 5) řádek přechází do `failed` a opouští hot path. |
| `sent_at` | TIMESTAMPTZ NULL | Vyplněno při přechodu do `sent`. Používá se pro kompakci (mazání starších `sent` řádků). |
| `last_error` | TEXT NULL | Poslední chyba publishe – důležité pro postmortem. |

:::callout{type="warn"}
### Povinný index `(status, occurred_at)` {#index-status-time-heading}

Detail, na který se v reálných implementacích zapomíná: bez kompozitního
indexu `(status, occurred_at)` dělá relay **full table scan**
při každém polling cyklu. Při outboxu s milionem historických `sent`
řádků a 100 `pending` se každých 100 ms scanuje milion
řádků. DB CPU vystřelí k 100 % a polling lag exploduje.

Index je **kompozitní** přesně v tomto pořadí – nejdřív
`status` (vysoká selektivita: `pending` řádky jsou typicky
méně než 0,1 % tabulky), pak `occurred_at` (umožní `ORDER BY`
bez sortu). Plánovač dotazů Postgresu pak relay query odbavuje jako
*Index Scan using idx_outbox_status_time*, řádově v jednotkách milisekund.
:::

:::callout{type="pattern"}
### SQL: Doctrine migrace pro outbox tabulku {#migration-heading}

:::code{language="php" filename="migrations/Version20260429120000.php" highlights="33,34,35"}
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Outbox table for Transactional Outbox Pattern';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox (
                id                BINARY(16)    NOT NULL,
                message_type      VARCHAR(255)  NOT NULL,
                payload           JSON          NOT NULL,
                status            VARCHAR(16)   NOT NULL DEFAULT 'pending',
                occurred_at       DATETIME(6)   NOT NULL,
                attempts          INT           NOT NULL DEFAULT 0,
                sent_at           DATETIME(6)   DEFAULT NULL,
                last_error        TEXT          DEFAULT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_outbox_status_time
                ON outbox (status, occurred_at)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox');
    }
}
:::
:::

Po migraci spusťte `php bin/console doctrine:migrations:migrate` a ověřte,
že index existuje:
`SHOW INDEXES FROM outbox WHERE Key_name = 'idx_outbox_status_time'`
(MySQL) nebo
`SELECT * FROM pg_indexes WHERE indexname = 'idx_outbox_status_time'`
(PostgreSQL). V CI doporučujeme přidat regresní test, který tento index kontroluje –
při refactoringu schématu se totiž často ztratí.

## 16.04 Aggregate publikuje, handler ukládá do outboxu {#aggregate-publishes}

Princip DDD říká, že agregát **nezná infrastrukturu** – neví
nic o Doctrine, RabbitMQ ani outbox tabulce. Agregát pouze produkuje seznam doménových
událostí, které jsou důsledkem doménové operace. Application handler je pak vezme
a zařadí do outbox tabulky *v téže transakci*, ve které ukládá agregát samotný.

:::callout{type="pattern"}
### PHP: Agregát Order produkuje doménové události {#order-aggregate-heading}

:::code{language="php" filename="src/Ordering/Domain/Order.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain;

use App\Ordering\Domain\Event\OrderPlaced;
use Symfony\Component\Uid\Ulid;

final class Order
{
    /** @var list<object> */
    private array $releasedEvents = [];

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
        /** @var list<OrderItem> */
        private array $items,
    ) {}

    /**
     * @param list<OrderItem> $items
     */
    public static function place(CustomerId $customerId, array $items): self
    {
        $order = new self(
            id: new OrderId(new Ulid()),
            customerId: $customerId,
            items: $items,
        );

        $order->releasedEvents[] = new OrderPlaced(
            eventId: new Ulid(),
            orderId: $order->id->value(),
            customerId: $customerId->value(),
            items: array_map(fn (OrderItem $i) => $i->toArray(), $items),
            occurredAt: new \DateTimeImmutable(),
        );

        return $order;
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->releasedEvents;
        $this->releasedEvents = [];

        return $events;
    }
}
:::
:::

:::callout{type="pattern"}
### PHP: Doménová událost OrderPlaced {#domain-event-heading}

:::code{language="php" filename="src/Ordering/Domain/Event/OrderPlaced.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

use Symfony\Component\Uid\Ulid;

/**
 * Doménová událost – neměnná, serializovatelná, nese pouze
 * data nutná pro subscribery. Včetně vlastního event_id pro
 * deduplikaci v Inboxu.
 */
final readonly class OrderPlaced
{
    public function __construct(
        public Ulid $eventId,
        public string $orderId,
        public string $customerId,
        /** @var list<array{sku: string, quantity: int, priceCents: int}> */
        public array $items,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
:::
:::

:::callout{type="pattern"}
### PHP: PlaceOrderHandler – atomický zápis order + outbox {#place-order-handler-heading}

:::code{language="php" filename="src/Ordering/Application/Handler/PlaceOrderHandler.php" highlights="29,30,38,39,40,41,42,43,44"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\PlaceOrder;
use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderId;
use App\Ordering\Domain\OrderRepository;
use App\Outbox\Application\OutboxRepository;
use App\Outbox\Domain\OutboxMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OutboxRepository $outbox,
        private EntityManagerInterface $em,
        private DomainEventSerializer $serializer,
    ) {}

    public function __invoke(PlaceOrder $command): OrderId
    {
        // wrapInTransaction garantuje atomicitu:
        // buď se zapíše order i všechny outbox řádky, nebo nic.
        return $this->em->wrapInTransaction(function () use ($command): OrderId {
            $order = Order::place($command->customerId, $command->items);

            $this->orders->save($order);

            foreach ($order->releaseEvents() as $event) {
                $this->outbox->store(
                    OutboxMessage::fromDomainEvent(
                        $event,
                        $this->serializer->serialize(...),
                    ),
                );
            }

            return $order->id;
        });
    }
}
:::
:::

Pozornost si zaslouží volání `$this->em->wrapInTransaction(...)`. Tato metoda
Doctrine EntityManageru otevře transakci, vykoná callback, na konci flushne a commitne;
pokud kdekoliv uvnitř callbacku letí výjimka, transakci automaticky rollbackne. Stejně
funguje i Symfony Messenger middleware `doctrine_transaction`, který zabalí
celý handler do jedné transakce – pokud ho v `messenger.yaml` máte, můžete
`wrapInTransaction` vynechat.

:::callout{type="pattern"}
### PHP: DomainEventSerializer – neutrální převod na JSON {#serializer-heading}

:::code{language="php" filename="src/Outbox/Application/DomainEventSerializer.php"}
<?php

declare(strict_types=1);

namespace App\Outbox\Application;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class DomainEventSerializer
{
    public function __construct(
        private NormalizerInterface $normalizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serialize(object $event): array
    {
        $payload = $this->normalizer->normalize($event, 'json');

        if (!is_array($payload)) {
            throw new \RuntimeException(
                sprintf('Domain event %s did not normalize to array.', $event::class),
            );
        }

        return $payload;
    }
}
:::
:::

:::callout{type="pattern"}
### PHP: Rozhraní OutboxRepository {#repo-interface-heading}

:::code{language="php" filename="src/Outbox/Application/OutboxRepository.php"}
<?php

declare(strict_types=1);

namespace App\Outbox\Application;

use App\Outbox\Domain\OutboxMessage;
use Symfony\Component\Uid\Ulid;

interface OutboxRepository
{
    public function store(OutboxMessage $message): void;

    /** @return list<OutboxMessage> */
    public function fetchPending(int $limit = 100): array;

    public function markSent(Ulid $id): void;

    public function markFailed(Ulid $id, string $error): void;
}
:::
:::

Implementace rozhraní pomocí Doctrine je triviální – konstruktor přijímá
`EntityManagerInterface`, `store()` volá `persist()`
(NIKOLI `flush()` – flush patří aplikačnímu transakčnímu wrapperu),
`fetchPending()` sestaví DQL `SELECT m FROM OutboxMessage m WHERE
m.status = 'pending' ORDER BY m.occurredAt ASC` a omezí výsledek voláním
`$query->setMaxResults($limit)`; `markSent()`
a `markFailed()` volají `$m->markSent()`,
respektive `$m->markFailed()` a následně flushnou. Plný výpis vynecháváme
– jde o mechanickou adapter třídu.

## 16.05 Relay process – dvě varianty {#relay}

Outbox tabulka sama o sobě nic nepublikuje – potřebuje relay proces, který v určité
kadenci vybírá pending řádky a posílá je do brokera. V praxi se používají dvě varianty:
**polling worker** v aplikačním procesu (jednodušší, vhodné pro 99 %
projektů) a **CDC / Debezium** (mimo aplikaci, vhodné pro masivní škálu
nebo polyglot infrastrukturu).

### Varianta A: Polling worker (Symfony Console command) {#relay-polling-heading}

Polling worker je obyčejný Symfony Console command, který v nekonečné smyčce volá
`fetchPending()`, publishne řádky a označí je jako `sent`.
Spouští se z `supervisord`, `systemd` timeru nebo Kubernetes
Deploymentu jako trvale běžící proces.

:::callout{type="pattern"}
### PHP: OutboxDispatchCommand {#dispatch-command-heading}

:::code{language="php" filename="src/Outbox/Infrastructure/Console/OutboxDispatchCommand.php"}
<?php

declare(strict_types=1);

namespace App\Outbox\Infrastructure\Console;

use App\Outbox\Application\OutboxRepository;
use App\Outbox\Application\OutboxMessageFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

#[AsCommand(
    name: 'app:outbox:dispatch',
    description: 'Polluje outbox tabulku a publikuje pending eventy do brokera.',
)]
final class OutboxDispatchCommand extends Command
{
    public function __construct(
        private readonly OutboxRepository $outbox,
        private readonly MessageBusInterface $bus,
        private readonly OutboxMessageFactory $factory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batch = $this->outbox->fetchPending(limit: 100);

        if ($batch === []) {
            return Command::SUCCESS;
        }

        foreach ($batch as $row) {
            try {
                $message = $this->factory->reconstitute($row);

                $this->bus->dispatch(
                    $message,
                    [
                        new TransportNamesStamp(['async']),
                        // event_id propagujeme do brokera –
                        // subscriber ho použije pro Inbox dedup.
                        new TransportMessageIdStamp((string) $row->id),
                    ],
                );

                $this->outbox->markSent($row->id);
            } catch (\Throwable $e) {
                $this->outbox->markFailed($row->id, $e->getMessage());

                $output->writeln(sprintf(
                    '<error>[outbox] %s – %s</error>',
                    $row->id,
                    $e->getMessage(),
                ));
            }
        }

        $output->writeln(sprintf('[outbox] dispatched %d messages', count($batch)));

        return Command::SUCCESS;
    }
}
:::
:::

:::callout{type="pattern"}
### Konfigurace supervisord pro outbox dispatch {#supervisor-heading}

:::code{language="bash" filename="/etc/supervisor/conf.d/outbox-dispatch.conf"}
; /etc/supervisor/conf.d/outbox-dispatch.conf
[program:outbox-dispatch]
command=php /var/www/app/bin/console app:outbox:dispatch
autostart=true
autorestart=true
startsecs=2
stopwaitsecs=10
stdout_logfile=/var/log/outbox-dispatch.log
stderr_logfile=/var/log/outbox-dispatch.err
user=www-data
numprocs=1                  ; jediný worker – vyhneme se duplicitnímu pollingu
process_name=%(program_name)s

; Loop: command běží 1× za invocation, supervisord ho restartuje
; cca každých 100 ms díky autorestart=true a startsecs=2.
; Alternativně použijte vnitřní while(true) + sleep(0.1) v commandu.
:::
:::

:::callout{type="warn"}
### Pozor: relay musí být **jediný worker** {#single-worker-heading}

Polling worker zásadně spouštějte jako **singleton** (`numprocs=1`
v supervisoru, `replicas: 1` v Kubernetes Deploymentu, případně leader
election přes Redis lock). Dva paralelní workery selectující stejnou outbox tabulku
způsobí **double publish** – každý event se odešle dvakrát ve stejnou chvíli,
kapacita brokera roste lineárně s počtem replik a Inbox musí vybalancovat víc duplicit.

Pokud potřebujete horizontální škálování dispatchu (>10k events/s), použijte
`SELECT ... FOR UPDATE SKIP LOCKED` v Postgresu nebo MySQL 8 a každý
worker si zarezervuje vlastní batch řádků. Limit při LIMITu 100 a SKIP LOCKED
zvládne až ~30k events/s na běžném DB instanci.
:::

### Varianta B: CDC / Debezium {#relay-cdc-heading}

**Change Data Capture** (CDC) je výrazně odlišné řešení: místo aplikačního
polleru číst Postgres WAL (*Write-Ahead Log*) nebo MySQL binlog a streamovat
každý `INSERT` do outbox tabulky přímo do Kafky. Standardním nástrojem je
[Debezium](https://debezium.io) – Kafka Connect plugin, který
funguje jako logický replikační odběratel databáze.

Tok je následující: aplikace zapíše do `outbox` jako obvykle (žádný kód se
nemění). Debezium vidí `INSERT` v WAL, vytvoří Kafka record a pošle do
odpovídajícího topicu (typicky `outbox.event.OrderPlaced`). Outbox řádek
v DB *není ničím* updatován – je čistě immutable log a kompakce probíhá řízeně
cron jobem.

| Aspekt | Polling worker (A) | Debezium / CDC (B) |
|---|---|---|
| Latence | 50–500 ms (polling interval) | 1–50 ms (push z WAL) |
| Operační složitost | 1× console command + supervisor | Kafka + Kafka Connect + Debezium konektor + monitoring 4 procesů |
| Volba brokera | Libovolný (RabbitMQ, SQS, Redis, Doctrine async) | Pouze Kafka (resp. Pulsar, Kinesis přes adaptér) |
| Scale-out | Až ~30k events/s (1 worker, SKIP LOCKED) | Statisíce events/s (dáno Kafkou) |
| Garance pořadí | Per-tabulka (ORDER BY occurred_at) | Per-partition (Debezium routuje podle PK) |
| Doporučeno pro | 99 % Symfony projektů | Multi-tenant SaaS, finanční systémy, IoT |

V této knize budeme dál pracovat s variantou A (polling worker) – pro typický Symfony
projekt je to ten správný kompromis mezi spolehlivostí a operační režií. Debezium se
vyplatí teprve tehdy, když máte už *pět produkčních Kafka konzumentů* a outbox lag
začíná být úzkým hrdlem.

Pro úplnost ukázka, jak vypadá konfigurace Debezium konektoru pro Postgres outbox
tabulku. Nasazuje se do Kafka Connectu jako JSON přes REST API, ekvivalentní YAML
pro deklarativní deploy (Strimzi operator, ArgoCD) je:

:::callout{type="pattern"}
### YAML: Debezium konektor pro outbox tabulku {#debezium-config-heading}

:::code{language="yaml" filename="kafka-connect/debezium-outbox-connector.yaml" highlights="25,30,31,32"}
# kafka-connect/debezium-outbox-connector.yaml
# Strimzi KafkaConnector custom resource pro Debezium 2.x.
apiVersion: kafka.strimzi.io/v1beta2
kind: KafkaConnector
metadata:
  name: ordering-outbox-connector
  labels:
    strimzi.io/cluster: kafka-connect
spec:
  class: io.debezium.connector.postgresql.PostgresConnector
  tasksMax: 1
  config:
    # Připojení k Postgres (logická replikace zapnutá v postgresql.conf:
    # wal_level=logical, max_replication_slots=4).
    database.hostname: pg-primary.internal
    database.port: 5432
    database.user: debezium
    database.password: ${secrets:debezium-pg-pwd}
    database.dbname: ordering
    database.server.name: ordering
    plugin.name: pgoutput
    slot.name: debezium_outbox

    # Snímat pouze tabulku outbox – ne celou DB.
    table.include.list: public.outbox

    # Outbox Event Router transformace: čte řádky outboxu a routuje je
    # do Kafka topiců podle sloupce message_type. Hlavní rys Debezia
    # pro outbox use-case (DBZ-1063+).
    transforms: outbox
    transforms.outbox.type: io.debezium.transforms.outbox.EventRouter
    transforms.outbox.table.field.event.id: id
    transforms.outbox.table.field.event.key: aggregate_id
    transforms.outbox.table.field.event.type: message_type
    transforms.outbox.table.field.event.payload: payload
    transforms.outbox.route.by.field: message_type
    transforms.outbox.route.topic.replacement: outbox.${routedByValue}

    # Po přečtení řádku Debezium ho NEUPDATUJE. Outbox tabulka je
    # immutable log; mazání starých řádků řeší samostatný cron job.
    tombstones.on.delete: false
:::
:::

Hlavní části jsou `transforms.outbox` (Debezium Outbox Event Router,
[DBZ-1063](https://debezium.io/documentation/reference/stable/transformations/outbox-event-router.html)),
která čte řádky outbox tabulky a směruje je do Kafka topiců podle `message_type`,
a `plugin.name: pgoutput` pro Postgres logickou replikaci. Žádný kód na
aplikační straně se proti variantě A nemění – handler dál zapisuje do outbox tabulky
v DB transakci, jen dispatcher je nahrazen Debezium konektorem.

*Citace: Debezium dokumentace –
[Outbox Event Router](https://debezium.io/documentation/reference/stable/transformations/outbox-event-router.html)
(Red Hat, 2019+).*

## 16.06 Idempotent Inbox – strana subscribera {#inbox}

Outbox dává at-least-once delivery, takže subscriber **musí** počítat s tím,
že stejný event dostane víckrát. Pokud je side-effect handleru ne-idempotentní (typicky
`UPDATE counter SET value = value + 1`), duplicita se okamžitě projeví jako
chybný stav read modelu – zákazník vidí 200 Kč na účtě místo 100 Kč, počet objednávek
je dvojnásobný, e-mail dorazí 2×.

**Idempotent Inbox Pattern** řeší tuto situaci doplňkem k outboxu – tabulkou
`inbox` v databázi subscribera se sloupcem `event_id` a UNIQUE
constraintem. Před zpracováním eventu handler zkontroluje, zda je daný event_id už
v inboxu; pokud ano, ackne brokerovi a skončí. Pokud ne, zpracuje doménovou logiku
a v *téže transakci* vloží nový řádek do inboxu. UNIQUE constraint je pojistka
proti race condition.

:::diagram{fig="16.6-A" title="Idempotent Inbox – deduplikace na straně subscribera" src="images/diagrams/14_outbox/inbox_idempotency.svg"}
:::

:::callout{type="pattern"}
### PHP: Doctrine entita InboxMessage {#inbox-message-entity-heading}

:::code{language="php" filename="src/Inbox/Domain/InboxMessage.php" highlights="11,12"}
<?php

declare(strict_types=1);

namespace App\Inbox\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'inbox')]
#[ORM\UniqueConstraint(name: 'uniq_inbox_event_id', columns: ['event_id'])]
class InboxMessage
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'ulid', unique: true)]
        public Ulid $eventId,

        #[ORM\Column(type: 'string', length: 64)]
        public string $consumer,

        #[ORM\Column(type: 'datetime_immutable')]
        public \DateTimeImmutable $processedAt = new \DateTimeImmutable(),
    ) {}
}
:::
:::

:::callout{type="pattern"}
### PHP: Rozhraní InboxRepository {#inbox-repository-heading}

:::code{language="php" filename="src/Inbox/Application/InboxRepository.php"}
<?php

declare(strict_types=1);

namespace App\Inbox\Application;

use Symfony\Component\Uid\Ulid;

interface InboxRepository
{
    public function isProcessed(Ulid $eventId, string $consumer): bool;

    public function markProcessed(Ulid $eventId, string $consumer): void;
}
:::
:::

:::callout{type="pattern"}
### PHP: OrderPlacedReadModelUpdater s inbox checkem {#read-model-updater-heading}

:::code{language="php" filename="src/Reporting/Application/Subscriber/OrderPlacedReadModelUpdater.php" highlights="27,28,29,30,44"}
<?php

declare(strict_types=1);

namespace App\Reporting\Application\Subscriber;

use App\Inbox\Application\InboxRepository;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Reporting\Application\ReadModelStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class OrderPlacedReadModelUpdater
{
    private const string CONSUMER = 'reporting.order_placed';

    public function __construct(
        private InboxRepository $inbox,
        private ReadModelStore $readModel,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(OrderPlaced $event): void
    {
        $this->em->wrapInTransaction(function () use ($event): void {
            // 1) Idempotency check – duplikát ackneme bez side-effectu.
            if ($this->inbox->isProcessed($event->eventId, self::CONSUMER)) {
                return;
            }

            // 2) Aplikace doménové logiky – typicky upsert read modelu.
            $this->readModel->upsertOrderRow(
                orderId: $event->orderId,
                customerId: $event->customerId,
                items: $event->items,
                placedAt: $event->occurredAt,
            );

            // 3) Mark processed v téže transakci.
            // UNIQUE constraint je pojistka proti race condition:
            // pokud dva workery dostanou stejný event paralelně,
            // druhý dostane UniqueConstraintViolationException
            // a Messenger retry-uje – podruhé už narazí na branch isProcessed=true.
            $this->inbox->markProcessed($event->eventId, self::CONSUMER);
        });
    }
}
:::
:::

Sloupec `consumer` v inbox tabulce není zanedbatelný: jeden a tentýž
event_id mohou zpracovávat různí subscribery (Reporting, Notifications, Search index)
a každý si potřebuje vést *vlastní* stav „už jsem to zpracoval“. Bez sloupce
consumer by druhý subscriber narazil na UNIQUE constraint prvního a nikdy by event
nezpracoval. UNIQUE proto definujeme jako kompozitní `(event_id, consumer)`,
ne jen `event_id`.

:::callout{type="note"}
### Exactly-once efekt vs. exactly-once delivery {#exactly-once-effect-heading}

Marketing materiály brokerů občas slibují „exactly-once delivery“. **Tato
garance neexistuje v žádném distribuovaném systému** – viz Two Generals'
Problem nebo papery Lamporta a Lynchové. Co Outbox+Inbox dohromady
poskytují, je *exactly-once efekt na straně subscribera*. Zpráva může do
brokera dorazit a opustit ho víckrát, ale side-effect (úprava read modelu, odeslání
e-mailu, strhnutí platby) proběhne *právě jednou*.

Helland v paperu z roku 2007 to formuluje úsporně: *„The world is at-least-once;
the application makes it look like exactly-once.“*
:::

## 16.07 Idempotency Key v HTTP API {#idempotency-api}

Outbox řeší idempotenci uvnitř systému (broker → subscriber); ale stejný problém vzniká
i o úroveň výš, na hranici HTTP API. Klient (mobilní aplikace, JS frontend, partnerská
integrace) může request retry-ovat při timeoutu – a server tak může dostat dva identické
`POST /orders` a vytvořit dvě objednávky.

[Stripe](https://docs.stripe.com/api/idempotent_requests)
popularizoval **Idempotency Key** jako standardní řešení a jeho
[specifikace](https://docs.stripe.com/api/idempotent_requests)
je dnes de-facto referencí pro REST API (převzala ji např. PayPal, Shopify, Square,
IETF draft [draft-ietf-httpapi-idempotency-key-header](https://datatracker.ietf.org/doc/draft-ietf-httpapi-idempotency-key-header/)).
Klient pošle v hlavičce `Idempotency-Key` UUID a server si první request uloží
do cache (Redis nebo DB tabulka `http_idempotency`) spolu s odpovědí. Všechny
další requesty se stejným klíčem vrátí cached response – bez znovuvytvoření objednávky.

:::callout{type="pattern"}
### PHP: IdempotencyKeyListener (Symfony Kernel listener) {#idempotency-listener-heading}

:::code{language="php" filename="src/Http/Idempotency/IdempotencyKeyListener.php"}
<?php

declare(strict_types=1);

namespace App\Http\Idempotency;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class IdempotencyKeyListener
{
    private const string HEADER = 'Idempotency-Key';
    private const int TTL_SECONDS = 86_400; // 24 hodin

    public function __construct(
        private CacheInterface $cache,
    ) {}

    #[AsEventListener(event: RequestEvent::class, priority: 32)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $key = $request->headers->get(self::HEADER);

        if ($key === null || !$this->isMutating($request->getMethod())) {
            return;
        }

        $cacheKey = $this->cacheKey($key, $request->getPathInfo());

        $cached = $this->cache->get($cacheKey, function (ItemInterface $item): null {
            $item->expiresAfter(self::TTL_SECONDS);

            return null;
        });

        if ($cached instanceof Response) {
            $event->setResponse($cached);
        }
    }

    #[AsEventListener(event: ResponseEvent::class)]
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $key = $request->headers->get(self::HEADER);

        if ($key === null || !$this->isMutating($request->getMethod())) {
            return;
        }

        $response = $event->getResponse();

        if ($response->getStatusCode() >= 500) {
            return; // 5xx necachujeme – klient ať retry-uje.
        }

        $cacheKey = $this->cacheKey($key, $request->getPathInfo());

        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($response): Response {
            $item->expiresAfter(self::TTL_SECONDS);

            return $response;
        });
    }

    private function isMutating(string $method): bool
    {
        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], strict: true);
    }

    private function cacheKey(string $key, string $path): string
    {
        return 'idem.' . hash('xxh128', $path . '|' . $key);
    }
}
:::
:::

Detaily, na které se často zapomíná:

- **TTL idempotency klíče typicky 24–48 h.** Delší okno znamená větší
  riziko, že klient po náhodné kolizi UUID dostane jinou response, než čeká.
  Stripe používá 24 h.
- **Cache key nesmí být jen klíč sám** – kombinujeme ho s cestou
  (`$path . '|' . $key`), aby tentýž klient s tímtéž klíčem na různých
  endpointech (`/orders` vs. `/refunds`) nesdílel cache.
- **5xx odpovědi necachujeme.** 500 znamená server-side chybu, klient
  má právo zkusit znovu. Caching 500 by zablokoval recovery na 24 hodin.
- **Hash z payloadu** (volitelně). Striktní implementace porovnává
  ještě body requestu – pokud klient pošle stejný klíč s jiným tělem, je to
  programátorská chyba a server vrací 422. Pro většinu projektů stačí klíč + cesta.

*Citace:
[Stripe API Reference – Idempotent Requests](https://docs.stripe.com/api/idempotent_requests)
(kanonická specifikace);
[IETF draft-ietf-httpapi-idempotency-key-header](https://datatracker.ietf.org/doc/draft-ietf-httpapi-idempotency-key-header/)
(probíhající standardizace HTTP header).*

## 16.08 Provozní aspekty {#provoz}

Outbox v *development* prostředí funguje, jak má. V produkci ale narazíte na čtyři
operační otázky: jak měřit lag, jak držet tabulku malou, co s permanentně failovanými
řádky a jak monitorovat, že se na něco nezapomnělo.

### Outbox lag {#outbox-lag-heading}

**Outbox lag** je čas, který stráví průměrný event ve stavu
`pending`, než ho relay pošle do brokera.

:::callout{type="pattern"}
### SQL: Měření outbox lagu {#lag-query-heading}

:::code{language="sql" filename="snippet.sql"}
-- Aktuální lag: nejstarší pending event v sekundách.
SELECT
    EXTRACT(EPOCH FROM (NOW() - MIN(occurred_at))) AS oldest_pending_seconds,
    COUNT(*) AS pending_count
FROM outbox
WHERE status = 'pending';

-- Histogram lagu za posledních 24 h (Postgres).
SELECT
    width_bucket(
        EXTRACT(EPOCH FROM (sent_at - occurred_at)),
        0, 60, 12
    ) AS bucket,
    COUNT(*) AS events
FROM outbox
WHERE sent_at > NOW() - INTERVAL '24 hours'
  AND status = 'sent'
GROUP BY bucket
ORDER BY bucket;
:::
:::

Tyto metriky exportujte do Prometheu (`outbox_pending_seconds`,
`outbox_pending_count`) a v Grafaně postavte alert: **kritický
práh typicky 30 sekund**. Pokud lag překročí tuto hranici, něco se zaseklo –
relay worker padl, broker je nedostupný, DB má 100% CPU. Při normálním provozu
je medián lagu pod 1 sekundou.

### Kompakce outbox tabulky {#kompakce-heading}

Outbox tabulka roste lineárně s počtem doménových eventů. Bez kompakce po roce
provozu obsahuje miliony historických řádků, což zpomaluje i indexované dotazy
a zbytečně okupuje disk. Standardní strategie: **mažeme řádky, které jsou
ve stavu `sent` a starší než N dní** – kde N je obvykle 7 až 30
podle compliance požadavků.

:::callout{type="pattern"}
### PHP: Kompakce outbox tabulky (Symfony command) {#cleanup-command-heading}

:::code{language="php" filename="src/Outbox/Infrastructure/Console/OutboxCleanupCommand.php"}
<?php

declare(strict_types=1);

namespace App\Outbox\Infrastructure\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:outbox:cleanup',
    description: 'Smaže sent outbox řádky starší než 30 dní.',
)]
final class OutboxCleanupCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deleted = $this->connection->executeStatement(<<<'SQL'
            DELETE FROM outbox
            WHERE status = 'sent'
              AND sent_at < NOW() - INTERVAL 30 DAY
            LIMIT 10000
        SQL);

        $output->writeln(sprintf('[outbox-cleanup] deleted %d rows', $deleted));

        return Command::SUCCESS;
    }
}
:::
:::

`LIMIT 10000` je tam záměrně – chceme batch delete, ne `DELETE FROM
outbox` jediným SQL příkazem. Velký delete drží zámky na celé tabulce, což
blokuje produkční INSERT z handlerů. Cron ho spouští každých 5 minut – 10 000 řádků
za běh stačí na realistické workloady (cca 3 mil. eventů/den).

### Dead-letter queue pro permanentní selhání {#dlq-heading}

Některé eventy se nikdy nepublishnou: schema změna v subscriberu, kterou nikdo
nevyřešil, broken payload (NaN v JSON), poison message, který shodí libovolného
consumera. Po N attempts (typicky 5) je `OutboxMessage::markFailed()`
přepne do stavu `failed`. Tyto řádky chceme:

- **Vyčlenit z hot pathy** – relay je už nezkouší publishovat.
- **Hlasitě upozornit** – alert `outbox_failed_total > 0`.
- **Mít na ně CLI nástroj** – `app:outbox:retry-failed` nebo
  ruční SQL update statusu zpět na `pending` po opravě subscribera.
- **Nikdy nemazat automaticky** – failed řádek je důkaz nedoručeného
  doménového eventu a chce ho mít evidovaný i po týdnu.

:::callout{type="note"}
### Monitorovací checklist (Prometheus + Grafana) {#monitoring-heading}

- `outbox_pending_seconds` – gauge, alert > 30 s.
- `outbox_pending_count` – gauge, alert > 10 000.
- `outbox_failed_total` – counter, alert > 0.
- `outbox_dispatched_total` – counter (rate per second).
- `inbox_duplicate_total` – counter, vysoké hodnoty signalizují,
  že relay padá mezi publishem a UPDATEm.
- `inbox_processed_total` – counter, srovnatelný s
  `outbox_dispatched_total`.
:::

### Vacuum a index bloat (PostgreSQL) {#vacuum-heading}

Outbox má specifický I/O profil: vysoký INSERT rate, krátký lifecycle (řádek vznikne →
během sekund se UPDATE na `sent` → po N dnech DELETE), nikdy se nečte historie.
PostgreSQL standardní autovacuum tunning na takový profil **není dimenzovaný**
a po několika dnech provozu narážíte na index bloat:

- INSERT vytváří mrtvé řádky v tabulce i v indexech (kvůli MVCC).
- UPDATE statusu vytváří další verze řádku.
- Standardní autovacuum threshold (`autovacuum_vacuum_scale_factor = 0.2`)
  čeká, než se nasbírá 20 % mrtvých řádků – při 5 000 events/s to je řád minut.
- Mezitím index `(status, occurred_at)` nabobtná na 10× původní velikost,
  selecty pomalují, lag stoupá.

Standardní opatření: **per-table vacuum tuning**.

:::callout{type="pattern"}
### SQL: Per-table autovacuum pro outbox {#vacuum-tuning-heading}

:::code{language="sql" filename="snippet.sql"}
ALTER TABLE outbox SET (
    autovacuum_vacuum_scale_factor = 0.05,    -- vacuum už při 5 % mrtvých řádků
    autovacuum_vacuum_threshold = 1000,       -- minimum 1000 mrtvých řádků
    autovacuum_analyze_scale_factor = 0.02,
    autovacuum_vacuum_cost_limit = 2000       -- vyšší rozpočet → rychleji dokončí
);

-- Pravidelně sledujte index bloat:
SELECT
    schemaname, tablename, indexname,
    pg_size_pretty(pg_relation_size(indexrelid)) AS index_size,
    idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes
JOIN pg_class ON pg_class.oid = indexrelid
WHERE schemaname = 'public' AND tablename = 'outbox';

-- REINDEX CONCURRENTLY když index naroste přes 2× očekávané velikosti:
REINDEX INDEX CONCURRENTLY outbox_status_occurred_at_idx;
:::
:::

### Partitioning při vysokém objemu {#partitioning-heading}

Při sustained 5k+ events/s je single-table outbox provozní úzké hrdlo. PostgreSQL
declarative partitioning podle `occurred_at` umožňuje:

- **Rychlé mazání starých dat** přes `DROP PARTITION` místo `DELETE` –
  nemá zámky na celé tabulce, runtime O(1) místo O(n).
- **Cílené vacuum** – autovacuum operuje per-partition, takže staré (read-only)
  partice se nevakuují vůbec.
- **Index lokalita** – aktivní partition obsahuje jen poslední hodiny eventů,
  index je malý a vlézá do RAM.

:::callout{type="pattern"}
### SQL: Outbox jako daily-partitioned tabulka {#partitioning-sql-heading}

:::code{language="sql" filename="snippet.sql"}
-- Hlavní tabulka jako partitioned parent.
CREATE TABLE outbox (
    id           UUID NOT NULL,
    event_type   VARCHAR(255) NOT NULL,
    payload      JSONB NOT NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'pending',
    occurred_at  TIMESTAMPTZ NOT NULL,
    sent_at      TIMESTAMPTZ,
    attempts     INT NOT NULL DEFAULT 0,
    last_error   TEXT,
    PRIMARY KEY (id, occurred_at)
) PARTITION BY RANGE (occurred_at);

-- Partition na den (vytváří pg_partman nebo cron).
CREATE TABLE outbox_2026_05_03 PARTITION OF outbox
    FOR VALUES FROM ('2026-05-03') TO ('2026-05-04');

-- Index na pending řádky – jen v aktivních partitions.
CREATE INDEX outbox_2026_05_03_pending_idx
    ON outbox_2026_05_03 (occurred_at)
    WHERE status = 'pending';

-- Cleanup = atomicky odpojit a smazat starou partition.
ALTER TABLE outbox DETACH PARTITION outbox_2026_04_01;
DROP TABLE outbox_2026_04_01;
:::
:::

Provozní automatizace: rozšíření [pg_partman](https://github.com/pgpartman/pg_partman)
spravuje vznik nových partitions i mazání starých přes cron. Pro MySQL existuje
nativní `PARTITION BY RANGE` se stejným efektem, ale bez pg_partman ekvivalentu –
správa je manuální.

### Distributed relay – multi-instance {#distributed-relay-heading}

Singleton polling worker (`replicas: 1` v Kubernetes) je nejjednodušší setup, ale
má dvě slabiny: **single point of failure** (worker spadne → lag roste, dokud
`livenessProbe` ho nerestartuje) a **omezenou propustnost** (jeden PHP proces
zvládne ~5k events/s na consumer-grade hardware).

Pro produkci s vyšším objemem nebo vyšším HA požadavkem se nabízí dvě cesty:

**Cesta 1 – leader election přes Redis/etcd.** Více workerů běží, ale jen jeden
je „leader" a publikuje. Když leader spadne, do 5 s ho nahradí jiný. Důsledek:
HA bez double publish, ale pořád jen jeden worker dispatchuje (nezvyšuje propustnost).

:::callout{type="pattern"}
### PHP: Leader election přes Redis SET NX EX {#leader-election-heading}

:::code{language="php" filename="src/Outbox/Infrastructure/Worker/LeaderElection.php"}
<?php

declare(strict_types=1);

namespace App\Outbox\Infrastructure\Worker;

use Predis\ClientInterface;

final class LeaderElection
{
    private const LEASE_KEY = 'outbox:relay:leader';
    private const LEASE_TTL_SECONDS = 10;

    public function __construct(
        private readonly ClientInterface $redis,
        private readonly string $instanceId, // např. POD_NAME z Kubernetes
    ) {}

    public function acquireOrRenew(): bool
    {
        // SET key value NX EX ttl – atomický „acquire if not exists, with TTL".
        $result = $this->redis->set(
            self::LEASE_KEY,
            $this->instanceId,
            'EX',
            self::LEASE_TTL_SECONDS,
            'NX',
        );
        if ($result === 'OK') {
            return true; // získán nový lease
        }

        // Lease drží někdo. Jsme to my? Pokud ano, prodlouž TTL.
        $current = $this->redis->get(self::LEASE_KEY);
        if ($current === $this->instanceId) {
            $this->redis->expire(self::LEASE_KEY, self::LEASE_TTL_SECONDS);
            return true;
        }

        return false;
    }
}
:::
:::

Worker volá `acquireOrRenew()` každé 3 sekundy (TTL 10 s dává buffer pro síťové
zpoždění). Když vrátí `false`, worker stojí. Když ji při následujícím tiku vrátí `true`,
začne dispatchovat – nový leader. Pozor: lease musí mít **kratší TTL než
processing batch**, jinak by leader mohl dokončit batch, který už dispatchuje
nový leader → double publish.

**Cesta 2 – `SELECT … FOR UPDATE SKIP LOCKED`.** Více workerů paralelně, každý
si zarezervuje vlastní batch řádků. Žádný leader, žádný single point of failure,
škáluje se lineárně s počtem worker replik.

:::diagram{fig="16.8-A" title="Distributed relay - 4 workery paralelně přes SKIP LOCKED" src="images/diagrams/14_outbox/distributed_relay.svg"}
:::

:::callout{type="pattern"}
### SQL: Concurrent dispatch přes SKIP LOCKED {#skip-locked-heading}

:::code{language="sql" filename="snippet.sql"}
BEGIN;

-- Worker si zarezervuje 100 pending řádků. Ostatní workery uvidí jen ty,
-- které tento worker NEzamknul.
SELECT id, event_type, payload, occurred_at
FROM outbox
WHERE status = 'pending'
ORDER BY occurred_at
LIMIT 100
FOR UPDATE SKIP LOCKED;

-- Worker řádky publikuje do brokera, pak:
UPDATE outbox
SET status = 'sent', sent_at = NOW()
WHERE id = ANY($1);  -- pole ID právě publikovaných

COMMIT;
:::
:::

Důsledek: 4 workery × 5k events/s = 20k events/s propustnost při zachování
**at-least-once** garance. PostgreSQL od verze 9.5 (`SKIP LOCKED`) i MySQL 8
to podporují. Cena: nutnost koordinace pořadí (events ze stejného agregátu
mohou být publishovány out-of-order, pokud workery zpracovávají různé batche).
Pokud subscriber pořadí potřebuje, partition outbox na `aggregate_id` a každý
worker řízeně zpracovává jen vlastní partition.

### Backpressure – co když broker nestíhá {#backpressure-heading}

Když Kafka/RabbitMQ nestíhá přijímat (síťová chyba, broker disk full, partition
leader election), relay worker dostává timeout/error na publish. Outbox řádky
zůstávají `pending`, kupí se. **Nezasahujte do produkčních INSERTů** – jakmile
začnete blokovat aplikační vrstvu, šíříte výpadek brokera do core domény.

Standardní vzor:

- **Worker exponential backoff** – po failed publish čeká 1 s, 2 s, 4 s,
  max 30 s. Mezitím loguje `outbox_publish_errors_total`.
- **Alert na rychlost růstu pending** – `delta(outbox_pending_count[5m]) > 10000`
  signalizuje, že produce > consume → broker nestíhá.
- **Capacity planning na DB** – outbox musí umět absorbovat 30 minut
  brokerového výpadku. Při 1k events/s to je 1.8M řádků navíc – rozpočet
  na disk a vacuum.
- **Zvážit sampling pro low-priority eventy** – některé eventy (audit, metrics)
  jsou tolerantní ke ztrátě. Při sustained backpressure můžete řízeně dropnout.
  Doménové eventy (`OrderPlaced`) ale **nikdy** – ty musí dorazit.

## 16.09 Anti-vzory {#antivzory}

Outbox vypadá triviálně, ale provázejí ho klasické chyby, které ruší
jeho garance a vrací systém zpět k dual-write problému. Následující seznam shrnuje
ty, které se v reálných code review opakují.

:::callout{type="warn"}
### Publish napřímo z metody agregátu {#anti-direct-publish-heading}

Některé framework wrappery (Laravel events, Symfony EventDispatcher nad DB
entitami) lákají k „*fire-and-forget*“ stylu přímo z metody agregátu.
Pokud event letí do brokera ještě před commitem doménové transakce – ať už
kvůli sync transportu, kvůli middleware pořadí nebo kvůli explicitnímu
`$bus->dispatch()` – máme dual-write zpět. Outbox je celý smysl
v tom, že event jde **do té samé DB transakce** jako doménový stav.
:::

:::callout{type="warn"}
### Outbox bez UNIQUE constraintu na `id` / inbox bez UNIQUE na `(event_id, consumer)` {#anti-no-unique-heading}

Řádek bez UNIQUE může být v race condition zapsán dvakrát (relay padá uprostřed
INSERTu, retry přijde s tímtéž ULIDem). Bez UNIQUE constraintu DB to dovolí
a pak relay publishne *dvojí* verzi téže události. UNIQUE je technický
invariant – ne nice-to-have.
:::

:::callout{type="warn"}
### Inbox check a side-effect ne v jedné transakci {#anti-inbox-no-tx-heading}

Klasická chyba: `if ($inbox->isProcessed($id)) return;` se provede
v autocommit režimu, side-effect na read modelu se provede taky v autocommit režimu
a teprve *potom* se vloží řádek do inboxu. Mezi check a insert ale může
prolézt druhý paralelní worker, který stejný check provede jako „nový“ a zduplikuje
update. Řešením je **celý handler obalit do `wrapInTransaction`**
a UNIQUE constraint na inboxu jako pojistka.
:::

:::callout{type="warn"}
### Read model bez idempotentní logiky {#anti-no-idempotent-side-effect-heading}

I se správným inboxem se může stát, že side-effect uvnitř transakce nebyl dotažen
do idempotentního stavu. Klasický příklad: `UPDATE counter SET value = value + 1`
pro každý `OrderPlaced` – pokud kdy v budoucnu vypneme inbox check
(např. při reseedingu), counter naskočí o víc. Doporučení: pokud možno preferovat
`UPSERT` / `INSERT ... ON CONFLICT DO UPDATE` nad inkrementálními
patterny, a counter dopočítávat z agregace v report queries, ne držet jako materializovaný
stav.
:::

:::callout{type="warn"}
### Více paralelních relay workerů bez koordinace {#anti-multiple-relays-heading}

Spustit `app:outbox:dispatch` ve dvou containerech najednou bez
`SELECT ... FOR UPDATE SKIP LOCKED` nebo bez leader electionu znamená,
že obě repliky vidí stejné `pending` řádky a publishnou je dvojmo.
Inbox to dokáže odchytit, ale generuje to zbytečnou zátěž na broker i na DB.
Pravidlo: *jeden relay singleton, nebo SKIP LOCKED.*
:::

:::callout{type="warn"}
### Publish před commitem, ne v doctrine_transaction middleware {#anti-publish-before-commit-heading}

`messenger:dispatch` volání před tím, než `EntityManager::flush()` opravdu zapíše do DB, je dual-write v učebnicové podobě. Pokud nemáte v
`messenger.yaml` aktivní middleware `doctrine_transaction`,
**vždy** obalujte handler explicitně do `wrapInTransaction`.
Výchozí bus chování v Symfony 8 je *auto-commit per dispatch*, ne per
handler – častý zdroj chyb.
:::

## 16.10 Migrace existujícího projektu – krok za krokem {#migrace}

Jak na Outbox, když máte 18 měsíců starý Symfony projekt, sto handlerů a jakési
publish-after-flush už tam někde je? Postup je inkrementální, ne big-bang refactor.
Outbox přidáváte handler po handleru, vedle stávajícího chování, a starý kód odstraňujete
teprve když nový jistě funguje.

### Krok 1: Přidat outbox tabulku a entitu {#migrace-krok-1-heading}

Vytvořte migraci podle sekce [16.03](#schema), spusťte
`doctrine:migrations:migrate`, nasaďte do produkce. **Tabulka zatím
nikdo nepoužívá** – žádné riziko regresí. Důležité: ověřte, že migrace skutečně
vytvořila kompozitní index `idx_outbox_status_time`, ne jen single-column.

### Krok 2: Refactor jednoho handleru {#migrace-krok-2-heading}

Vyberte jeden hlavní handler – typicky `PlaceOrderHandler` nebo cokoli,
kde dual-write nejvíc bolí. Přidejte do něj `wrapInTransaction` a místo
`$bus->dispatch($event)` volejte `$outbox->store(OutboxMessage::fromDomainEvent($event))`.
*Nemažte* ještě staré `$bus->dispatch()` – pokud máte legacy subscribery,
kteří poslouchají na sync transportu, ti by přestali fungovat.

### Krok 3: Nasadit relay command {#migrace-krok-3-heading}

Implementujte `OutboxDispatchCommand` ze sekce [16.05](#relay)
a deploynete pod supervisorem. V tomto bodě může worker už publishovat eventy
z outboxu – pokud máte legacy publish dál aktivní, broker dostane *obě* verze.
Subscribery ale ještě nemají Inbox, takže duplicitu řeší... přesně, neřeší.

### Krok 4: Přidat inbox subscriberům jeden po druhém {#migrace-krok-4-heading}

Pro každý subscriber kontextu vytvořte `inbox` tabulku, refactor handler
podle sekce [16.06](#inbox). Toto je nejdelší krok migrace (typicky týdny),
ale paralelizovatelný napříč týmy – každý kontext si Inbox přidává nezávisle.

### Krok 5: Vypnout legacy publish {#migrace-krok-5-heading}

Až mají všichni subscribery inbox, smažete v handleru původní `$bus->dispatch()`
a spoléháte výhradně na outbox. **Jde o riskantní krok** – během prvních
dnů sledujte outbox lag a inbox dedupy. Pokud něco selhává, pull-request reverter má
návrat zpět během 5 minut.

### Krok 6: Měřit a tunit {#migrace-krok-6-heading}

Po měsíci provozu projděte metriky: jaký je medián lagu, jakým tempem roste tabulka,
kolik řádků skončilo ve `failed`, kolik duplicit Inbox odchytil. Z těchto
čísel se dá vyladit polling interval relay procesu, batch limit, cleanup retention
a alert prahy. Outbox není „set-and-forget“ – vyžaduje občasnou provozní údržbu.

:::callout{type="warn"}
### Před produkčním nasazením {#migrace-warning-heading}

Migrace na Outbox je **data-changing** operace. Před produkcí ji
otestujte ve *staging* prostředí, které má reálnou velikost dat (kopie
produkčního DB), a ověřte:

- relay worker vydrží 24 h bez restartu;
- v lagu nejsou „špičky“, které by signalizovaly contention na DB;
- cleanup command netrvá déle než pollingový interval (jinak blokuje DB);
- vypnutí legacy publishu při zachované konzistenci subscriberů
  (proveďte na staging a porovnejte read model před a po).
:::

## 16.11 Shrnutí {#summary}

Outbox Pattern má **přímočarou implementaci a měřitelný provozní přínos**: vyřeší
celou třídu chyb (ztracené eventy, fantom eventy), které jinak musíte ladit reaktivně
ve tři ráno z logů. Cena je tabulka navíc, jeden Symfony command a úprava jednoho
application handleru. Garance, kterou tím získáte, je at-least-once delivery doménových
událostí napříč libovolným message brokerem – bez závislosti na XA, bez 2PC, bez
speciální cloud služby.

Idempotent Inbox je nutný protějšek na straně subscribera. Bez něj se duplikace
z outboxu propíše do read modelů a side-effectů, čímž ztratíme to, co jsme outboxem
získali. Kombinace Outbox + Inbox dohromady poskytují *exactly-once efekt* –
každý event se v read modelu projeví právě jednou, i když broker dodá zprávu vícekrát.

Hlavní body pro praxi:

- Outbox je **tabulka v téže DB jako doménový stav** – jinak nedává smysl.
- Doctrine entita s `#[ORM\Index(columns: ['status', 'occurred_at'])]`
  je nepostradatelný detail.
- `$em->wrapInTransaction(...)` v handleru garantuje atomicitu order +
  outbox řádky.
- Polling worker pod supervisorem stačí pro 99 % Symfony projektů; CDC/Debezium
  pouze pro Kafka-native systémy s vysokým objemem.
- Inbox tabulka má UNIQUE `(event_id, consumer)` – sloupec consumer je
  klíč pro multi-subscriber scénáře.
- Monitoring outbox lagu, dispatched/failed counters a inbox duplicit je nezbytné.
- Migrace existujícího projektu je inkrementální – handler po handleru, kontext
  po kontextu, nikdy big-bang.

Outbox Pattern přirozeně navazuje na vzory z předchozích kapitol. V
[CQRS](/cqrs) řeší spolehlivost publishu eventů z command
side do read side. V [Event Sourcingu](/event-sourcing) je
jeho rozšíření čisté – event store funguje jako outbox, projekce čte jako relay.
V [ságách](/sagy-a-process-managery) garantuje doručení doménových eventů
i příkazů mezi kontexty, takže sága se nikdy „nezasekne“ kvůli ztracené zprávě.

*Doporučená literatura k prohloubení:
Helland, P. – **Life Beyond Distributed Transactions**, CIDR (2007);
Richardson, C. – **Microservices Patterns**, Manning (2018), kap. 3 a 4;
Kleppmann, M. – **Designing Data-Intensive Applications**, O'Reilly (2017),
kap. 11 (Stream Processing);
[microservices.io](https://microservices.io/patterns/data/transactional-outbox.html)
– Pattern: Transactional Outbox.*

:::faq{}
- question: 'Outbox vs. CDC / Debezium – co kdy?'
  answer: 'Pro 99 % Symfony projektů zvolte polling worker (varianta A). Operační režie je minimální (jeden Symfony command pod supervisorem) a latence pod 1 sekundou je dostatečná pro typické obchodní scénáře (objednávky, platby, notifikace). Debezium / CDC se vyplatí, až když máte (a) Kafkovou infrastrukturu už nasazenou, (b) latenční požadavek pod 50 ms, (c) objem nad 10 000 events/s, (d) tým, který má zkušenost s Kafka Connect. Jinak zaplatíte multinásobnou operační složitost za marginální benefit. Detail v <a href="#relay">sekci 16.05</a>.'
- question: 'Co když používáme NoSQL databázi (MongoDB, Cassandra, DynamoDB)?'
  answer: 'Pokud váš agregát žije v NoSQL bez ACID transakcí napříč více dokumenty (Cassandra, raná verze MongoDB), klasický Outbox Pattern nefunguje – atomicita zápisu order + event mezi dvěma collections není garantovaná. Možnosti: (1) MongoDB 4.0+ má multi-document transakce, takže Outbox lze, (2) DynamoDB nabízí TransactWriteItems, takže Outbox jde, (3) Cassandra nemá multi-row atomicitu – používá se Change Data Capture nebo jednodokumentové event sourcing s eventy embedded v agregátu. Volba úložiště pro doménový stav rozhoduje, zda lze Outbox vůbec implementovat.'
- question: 'Jak velký dělat batch v relayi?'
  answer: 'Standardně 100 řádků za polling cyklus s intervalem 100 ms. To dává teoretický throughput 1 000 events/s na jeden worker, což pokryje drtivou většinu workloadů. Pokud lag stoupá nad 5 sekund a CPU brokera má rezervu, zvyšte limit na 500 nebo zkraťte interval na 50 ms. U batch nad 1 000 narazíte na DB serializaci updateů – místo jednoho velkého batche pak rozdělte na víc workerů s SELECT ... FOR UPDATE SKIP LOCKED. Hlavní pravidlo: měřte před tunováním, ne tunujte „na cit“.'
- question: 'Vyplatí se Outbox v monolitu?'
  answer: 'Ano, vyplatí – protože dual-write problem nevzniká až mezi mikroslužbami, ale mezi <em>libovolnými dvěma transakčními systémy</em>. Monolitická aplikace publikující eventy do RabbitMQ/Redis Streams má přesně stejný problém jako mikroslužba: DB ACID je oddělený od ACK message brokera. Pokud váš monolit už má event-driven kontexty (Symfony Messenger s async transportem, Spatie Laravel events, ...), Outbox se vyplatí stejně jako v mikroslužbách. Jediný případ, kdy ho nepotřebujete, je <em>striktně synchronní</em> monolit, kde publish neexistuje a všechno teče v jedné HTTP transakci.'
- question: 'Co dělat při dlouhodobém výpadku brokera?'
  answer: 'Outbox jako celek je <strong>self-healing</strong>: když broker leží 30 minut, relay worker dostává timeout/connection refused, řádky zůstávají ve stavu pending, počet vzroste, lag exploduje – ale aplikační handlery dál zapisují doménové eventy (jen do DB). Po obnovení brokera relay během několika minut vyšle backlog, lag se vrátí k normálu, subscribery dohrabou stav. Co je třeba: (a) alert na lag &gt; 30 s aby tým o výpadku věděl, (b) dostatek místa v DB na nahromaděné pending řádky (typicky není problém – řádky jsou malé), (c) kompakce ne-mazat pending stará než N dní, jen sent. Pokud broker chybí déle než N dní, máte dost času škálovat dispatch capacity nebo migrovat na alternativní broker.'
- question: 'Musím použít UUID/ULID, nebo stačí AUTO_INCREMENT?'
  answer: 'Použijte ULID (nebo UUIDv7), ne AUTO_INCREMENT. Důvody: (1) ULID je globálně unikátní napříč instancemi DB – nehrozí kolize při replikaci, restore z backupu nebo migraci. (2) ULID nese časový komponent, takže ID koreluje s pořadím vytvoření – užitečné pro debugging a pro indexové scany. (3) ULID je předvídatelný klientem, který může poslat event_id v Idempotency-Key headeru. (4) AUTO_INCREMENT komplikuje sharding a multi-region setupy. Symfony Uid komponenta poskytuje pohodlné API: <code>new Ulid()</code> v entitě stačí.'
:::
