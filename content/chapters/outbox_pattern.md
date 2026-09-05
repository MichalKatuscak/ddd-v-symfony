---
route: outbox_pattern
path: /outbox-pattern
title: 'Outbox Pattern – spolehlivé publikování doménových eventů'
page_title: "Outbox Pattern: spolehlivé doručení eventů | DDD Symfony"
meta_description: "Transactional Outbox a Idempotent Inbox v Symfony 8 a Doctrine: spolehlivé doručení doménových eventů a konec dual-write problému. Podle Pata Hellanda."
meta_keywords: "Outbox Pattern, Transactional Outbox, Inbox Pattern, Idempotency, Dual-write problem, Pat Helland, Chris Richardson, Symfony Messenger, Doctrine, at-least-once, exactly-once, RabbitMQ, eventy, CDC, Debezium"
og_type: article
published: "2026-04-29"
modified: "2026-07-08"
breadcrumb_name: Outbox Pattern
schema_type: TechArticle
schema_headline: "Outbox Pattern – spolehlivé publikování doménových eventů"
chapter_number: "15"
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

**Transactional Outbox Pattern** je standardní řešení dual-write problému.
Katalogovou definici vzoru formuloval Chris Richardson; Pat Helland k němu
v práci *Life Beyond Distributed Transactions* (2007) dodává rámec – odmítnutí
distribuovaných transakcí a požadavek na idempotentního příjemce. Protějšek na
straně subscriberů se v katalozích jmenuje **Idempotent Consumer**, starším
názvem *Idempotent Receiver*; tato kapitola pro něj používá pracovní jméno
**Idempotent Inbox**, protože stojí symetricky proti outboxu.

Dál projdeme schéma outbox tabulky s povinným indexem, implementaci s Doctrine ORM
a Symfony Messenger, dvě kanonické varianty relay procesu (Polling Publisher
a Transaction Log Tailing) a operační aspekty – outbox lag, kompakci,
dead-letter queue. Závěr patří migračnímu postupu pro existující projekt
a srovnání s alternativami.

## 15.01 Dual-write problem {#dual-write}

Nejjednodušší implementace publikování doménové události vypadá nevinně: po dokončení
doménové operace zapíšeme stav do databáze a pak rovnou dispatchneme událost na message
bus. Takový kód projde code review bez poznámek – dokud se v produkci nezačnou hromadit
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
pád workeru, restart aplikace, výpadek brokera, OOM kill PHP procesu – skončíme
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
problém pojmenoval. Jakmile transakce přesahuje hranici jednoho úložiště,
atomicita je iluze; obnovit ji musí aplikační logika. Slovo *outbox* ale v paperu
nepadne – tabulku a relay proces popsal až Chris Richardson v knize
*Microservices Patterns* (2018, kapitola 3) a v katalogu microservices.io.
Jeho formulace řešení zní: odesílatel nejdřív uloží zprávu do databáze ve stejné
transakci, která mění doménové entity, a samostatný proces ji teprve pak posílá
do brokera. Jako alternativu katalog uvádí event sourcing.

:::callout{type="note"}
### Proč ne Two-Phase Commit (2PC / XA)? {#2pc-heading}

Distribuované databáze a některé brokery nabízejí protokol
**Two-Phase Commit** (2PC), implementovaný typicky přes XA. V první fázi
(*prepare*) se všichni účastníci ptají, zda mohou commitnout; ve druhé fázi
(*commit*) koordinátor rozhodne o globálním commitu nebo rollbacku. Teoreticky
bychom mohli RabbitMQ a PostgreSQL zapojit do jedné XA transakce a problém by zmizel.
Praxe je ale jiná:

- **Běžné brokery XA nepodporují.** RabbitMQ distribuované XA transakce
  neimplementuje. Kafka má od verze 0.11 vlastní transakce, ty ale platí uvnitř
  Kafky – jako XA resource manager pro cizí koordinátor nevystupuje. Redis Streams
  o něčem takovém neuvažují. U cloudových služeb (AWS SNS/SQS, Google Pub/Sub)
  je XA definitivně mimo hru. Závazek na XA-only infrastrukturu vážně omezuje
  volbu technologií.
- **XA je drahé.** Účastníci drží zámky po celou dobu obou fází –
  propustnost klesá řádově. Helland v citovaném paperu odmítá 2PC především kvůli
  dostupnosti: protokol blokuje, jakmile je některý uzel nedostupný, a jeho
  křehkost vytváří nepřijatelný tlak na dostupnost celku.
- **Single point of failure.** Koordinátor 2PC je kritické místo;
  jeho selhání mezi fázemi prepare a commit zanechá účastníky v *in-doubt*
  stavu, kdy ani nelze rollbacknout, ani commitnout. Je třeba manuální zásah –
  ve tři hodiny ráno.
- **Těsné provázání porušuje autonomii Bounded Contexts.** XA vyžaduje,
  aby všichni účastníci sdíleli koordinátora. To přímo odporuje principu
  samostatně nasaditelných kontextů, který je jádrem
  [DDD](/zakladni-koncepty#bounded-contexts)
  i [mikroslužeb](/ddd-a-microservices).

Outbox Pattern obchází tato omezení tím, že **nepotřebuje globálního koordinátora ani
XA transport**: vystačí si s jednou ACID transakcí v DB, kterou už máte
pro persistenci agregátu.
:::

*Citace:
Helland, P. **Life Beyond Distributed Transactions: An Apostate's Opinion**,
CIDR (2007); Richardson, C. **Microservices Patterns**, Manning (2018),
kapitola 3 – Transactional messaging; Microservices.io –
[Pattern: Transactional Outbox](https://microservices.io/patterns/data/transactional-outbox.html).*

## 15.02 Transactional Outbox – princip {#princip}

Místo dispatchu do brokera **zapíšeme událost do tabulky `outbox`** ve stejné databázi,
kde žije doménový stav. Zápis proběhne *uvnitř stejné DB transakce* jako úprava agregátu.
Buď se tedy zapíše obojí (order i jeho event), nebo se nezapíše nic (rollback
celé transakce). Atomicita je obnovena – oba zápisy jsou v jediném ACID kontextu
jedné databáze, ne ve dvou různých systémech.

Samostatný proces (**relay worker**, někdy nazývaný *publisher*
nebo *dispatcher*) tabulku asynchronně polluje. Vybírá řádky se stavem
`pending` a publikuje je do skutečného message brokeru. Po úspěšném publishi
řádek označí jako `sent`. Tok má čtyři jasně oddělené fáze:

:::diagram{fig="15.2-A" title="Transactional Outbox – čtyři fáze publikování" src="images/diagrams/14_outbox/outbox_flow.svg"}
:::

1. **Fáze 1 – doménová transakce.** Application handler v jedné Doctrine
   transakci uloží agregát i odpovídající outbox řádky. Buď oboje, nebo nic.
2. **Fáze 2 – polling outboxu.** Relay worker periodicky (např. každých
   100 ms) selectuje pending řádky z outboxu, seřazené podle `occurred_at`.
   Výsledkem je best-effort FIFO, ne garantované pořadí – proč, rozebírá
   [sekce 15.05](#relay-ordering-heading).
3. **Fáze 3 – publish do brokeru.** Pro každý řádek relay publikuje event
   do brokera a po obdržení ACK řádek označí jako `sent`. Obě operace nejsou
   v jedné transakci – pokud spadne mezi nimi, řádek zůstane `pending`
   a po restartu se publikace zopakuje. Z toho plyne garance at-least-once
   delivery, rozebraná níže.
4. **Fáze 4 – konzumace subscriberem.** Subscriber dostane delivery,
   zpracuje ji idempotentně (typicky přes [Inbox Pattern](#inbox)) a
   ackne brokerovi.

:::callout{type="note"}
### Garance Outbox Pattern: at-least-once delivery {#at-least-once-heading}

Outbox samotný garantuje **at-least-once delivery** – každá doménová
událost se k subscriberům dostane *alespoň jednou*, ale může se stát, že
i víckrát. Konkrétní scénář duplikace: relay úspěšně publikuje event do brokera
(broker poslal ACK, event je trvale uložen). Relay ale spadne *před* tím,
než stihne zapsat `UPDATE outbox SET status='sent'`. Po restartu vidí
řádek pořád jako `pending` a publikuje ho znovu. Subscriber tak dostane
stejný event dvakrát.

Toto je *záměrná* volba: přijímáme možnost duplikace výměnou za to, že žádný
event neztratíme. **Exactly-once delivery v distribuovaných systémech
obecně neexistuje**: příjemce a odesílatel se nad ztrátovým kanálem nikdy
neshodnou na tom, že zpráva dorazila právě jednou.
Co lze v praxi dosáhnout, je *exactly-once efekt* na straně subscribera –
a o to se postará [Idempotent Inbox](#inbox).
:::

## 15.03 Schéma `outbox` tabulky a Doctrine mapping {#schema}

Outbox tabulka má deset sloupců; každý řeší konkrétní provozní problém, který se
bez něj projeví až pod produkční zátěží.

Entita níže nese Doctrine atributy a sedí v namespace `App\Outbox\Domain` –
pragmatická volba. Outbox je infrastrukturní vzor; kdo drží přísné vrstvení
podle kapitoly [Architektonické styly](/architektonicke-styly#hexagonal),
umístí tabulkovou entitu do `Infrastructure`.

:::callout{type="pattern"}
### PHP: Doctrine entita OutboxMessage {#outbox-message-entity-heading}

:::code{language="php" filename="src/Outbox/Domain/OutboxMessage.php" highlights="11,12"}
<?php

declare(strict_types=1);

namespace App\Outbox\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'outbox')]
#[ORM\Index(columns: ['status', 'occurred_at'], name: 'idx_outbox_status_time')]
class OutboxMessage
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid', unique: true)]
        public Uuid $id,

        /** Plně kvalifikovaný název třídy doménové události. */
        #[ORM\Column(type: 'string', length: 255)]
        public string $messageType,

        /** Typ agregátu, který událost vydal – routovací klíč pro CDC. */
        #[ORM\Column(type: 'string', length: 255)]
        public string $aggregateType,

        /** ID agregátu – klíč partition, drží pořadí per agregát. */
        #[ORM\Column(type: 'string', length: 64)]
        public string $aggregateId,

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

    public static function fromDomainEvent(
        object $event,
        string $aggregateType,
        string $aggregateId,
        callable $serializer,
    ): self {
        return new self(
            id: Uuid::v7(),
            messageType: $event::class,
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            payload: $serializer($event),
        );
    }
}
:::
:::

### Význam jednotlivých sloupců {#vyznam-sloupcu-heading}

| Sloupec | Typ | Účel |
|---|---|---|
| `id` | UUID v7 (16 B) | Primární klíč a pořadí řádků pro polling. Deduplikaci nenese – tu zajišťuje `eventId` v payloadu události (viz Inbox). |
| `message_type` | VARCHAR(255) | FQCN doménové události (např. `App\Ordering\Domain\Event\OrderPlaced`). Relay podle něj namapuje payload zpět na PHP třídu. |
| `aggregate_type` | VARCHAR(255) | Typ agregátu, který událost vydal (`Order`, `Invoice`). Debezium podle tohoto sloupce routuje do Kafka topiců, viz [15.05](#relay-cdc-heading). |
| `aggregate_id` | VARCHAR(64) | ID konkrétní instance agregátu. Slouží jako klíč zprávy: události jednoho agregátu skončí ve stejné partition, a tím ve správném pořadí. |
| `payload` | JSON / JSONB | Serializovaný stav události. JSONB v Postgresu je preferovaný – umožňuje indexovat jednotlivá pole pro debugging. |
| `status` | VARCHAR(16) | Stavový enum: `pending` (čeká na publish), `sent` (úspěšně publikováno), `failed` (po N pokusech vzdáno, vyžaduje manuální resolve). |
| `occurred_at` | TIMESTAMPTZ | Čas vzniku události v doménové transakci. Slouží pro řazení v relayi (best-effort FIFO) a pro výpočet outbox lagu. |
| `attempts` | INT | Počet neúspěšných pokusů o publish. Po dosažení prahu (typicky 5) řádek přechází do `failed` a opouští hot path. |
| `sent_at` | TIMESTAMPTZ NULL | Vyplněno při přechodu do `sent`. Používá se pro kompakci (mazání starších `sent` řádků). |
| `last_error` | TEXT NULL | Poslední chyba publishe – důležité pro rozbor incidentu. |

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

:::code{language="php" filename="migrations/Version20260429120000.php" highlights="35,36,37"}
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
                aggregate_type    VARCHAR(255)  NOT NULL,
                aggregate_id      VARCHAR(64)   NOT NULL,
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

Migrace cílí na MySQL/MariaDB. PostgreSQL varianta nahradí `BINARY(16)`
typem `UUID`, `DATETIME(6)` typem `TIMESTAMPTZ` a `JSON` typem `JSONB`;
klauzule `ENGINE` a `CHARSET` odpadají.

Po migraci spusťte `php bin/console doctrine:migrations:migrate` a ověřte,
že index existuje:
`SHOW INDEXES FROM outbox WHERE Key_name = 'idx_outbox_status_time'`
(MySQL) nebo
`SELECT * FROM pg_indexes WHERE indexname = 'idx_outbox_status_time'`
(PostgreSQL). V CI doporučujeme přidat regresní test, který tento index kontroluje –
při refaktoringu schématu se totiž často ztratí.

## 15.04 Aggregate publikuje, handler ukládá do outboxu {#aggregate-publishes}

Agregát v DDD **nezná infrastrukturu** – neví nic o Doctrine, RabbitMQ ani outbox
tabulce. Jeho výstupem je seznam doménových událostí jako důsledek právě provedené
operace. Application handler ten seznam vezme a zařadí do outbox tabulky *v téže
transakci*, ve které ukládá samotný agregát.

:::callout{type="pattern"}
### PHP: Agregát Order produkuje doménové události {#order-aggregate-heading}

:::code{language="php" filename="src/Ordering/Domain/Order.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain;

use App\Ordering\Domain\Event\OrderPlaced;
use App\SharedKernel\Domain\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Order extends AggregateRoot
{
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
            id: new OrderId((string) Uuid::v7()),
            customerId: $customerId,
            items: $items,
        );

        $order->record(new OrderPlaced(
            eventId: Uuid::v7(),
            orderId: $order->id->value,
            customerId: $customerId->value,
            items: array_map(fn (OrderItem $i) => $i->toArray(), $items),
            occurredAt: new \DateTimeImmutable(),
        ));

        return $order;
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

use Symfony\Component\Uid\Uuid;

/**
 * Doménová událost – neměnná, serializovatelná, nese pouze
 * data nutná pro subscribery. Včetně vlastního event_id pro
 * deduplikaci v Inboxu.
 */
final readonly class OrderPlaced
{
    public function __construct(
        public Uuid $eventId,
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

:::code{language="php" filename="src/Ordering/Application/Handler/PlaceOrderHandler.php" highlights="29,30,36,37,38,39,40,41,42,43,44,45"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\PlaceOrder;
use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderId;
use App\Ordering\Domain\OrderRepository;
use App\Outbox\Application\DomainEventSerializer;
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
                        aggregateType: 'Order',
                        aggregateId: $order->id->value,
                        serializer: $this->serializer->serialize(...),
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
use Symfony\Component\Uid\Uuid;

interface OutboxRepository
{
    public function store(OutboxMessage $message): void;

    /** @return list<OutboxMessage> */
    public function fetchPending(int $limit = 100): array;

    public function markSent(Uuid $id): void;

    public function markFailed(Uuid $id, string $error): void;
}
:::
:::

Doctrine adapter rozhraní je mechanický – konstruktor přijímá
`EntityManagerInterface`, `store()` volá `persist()`
(NIKOLI `flush()` – flush patří aplikačnímu transakčnímu wrapperu),
`fetchPending()` sestaví DQL `SELECT m FROM OutboxMessage m WHERE
m.status = 'pending' ORDER BY m.occurredAt ASC` a omezí výsledek voláním
`$query->setMaxResults($limit)`; `markSent()`
a `markFailed()` volají `$m->markSent()`,
respektive `$m->markFailed()` a následně flushnou. Plný výpis vynecháváme
– adapter je čistě průchozí.

## 15.05 Relay process – dvě varianty {#relay}

Outbox tabulka sama o sobě nic nepublikuje – potřebuje relay proces, který v určité
kadenci vybírá pending řádky a posílá je do brokera. Katalog microservices.io pro to
zná dva pojmenované vzory. **Polling Publisher** čte outbox tabulku dotazem
a jeho jediná, zato podstatná přednost zní: funguje nad libovolnou SQL databází.
**Transaction Log Tailing** místo dotazu čte transakční log databáze, tedy Postgres WAL
nebo MySQL binlog. V praxi se první realizuje jako Symfony Console command, druhý jako
Debezium konektor nad Kafkou.

### Varianta A: Polling Publisher (Symfony Console command) {#relay-polling-heading}

Polling worker je obyčejný Symfony Console command, který ve vnitřní smyčce volá
`fetchPending()`, publikuje řádky a označí je jako `sent`.
Spouští se ze `supervisord`, `systemd` nebo Kubernetes
Deploymentu jako trvale běžící proces. Smyčka má časový limit – po jeho
doběhnutí se proces čistě ukončí a process manager ho nastartuje znovu.
Stejný vzor používá `messenger:consume --time-limit`; periodický restart
drží pod kontrolou paměť dlouho běžícího PHP procesu.

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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

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

    protected function configure(): void
    {
        $this->addOption(
            'time-limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Po kolika sekundách se proces ukončí (process manager ho nastartuje znovu).',
            3600,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deadline = time() + (int) $input->getOption('time-limit');

        while (time() < $deadline) {
            $batch = $this->outbox->fetchPending(limit: 100);

            if ($batch === []) {
                usleep(100_000); // 100 ms polling interval

                continue;
            }

            foreach ($batch as $row) {
                try {
                    $message = $this->factory->reconstitute($row);

                    // event_id pro Inbox dedup cestuje v payloadu události
                    // (OrderPlaced::$eventId) – žádný stamp není potřeba.
                    $this->bus->dispatch(
                        $message,
                        [new TransportNamesStamp(['async'])],
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
        }

        return Command::SUCCESS; // čistý exit – supervisord startuje znovu
    }
}
:::
:::

Třída `OutboxMessageFactory` je protějšek serializeru: metoda `reconstitute()`
podle `messageType` namapuje JSON payload z outbox řádku zpět na instanci
doménové události. Plný výpis vynecháváme – jde o mechanický opak
`DomainEventSerializer`.

:::callout{type="warn"}
### Po Doctrine výjimce je EntityManager zavřený {#closed-em-heading}

Výpis výše má jednu past, kterou odhalí až produkce. `catch (\Throwable $e)`
volá `markFailed()` nad týmž EntityManagerem. Pokud výjimku vyhodila Doctrine,
je EM po rollbacku zavřený a `markFailed()` skončí na
`EntityManagerClosedException`. Worker spadne v prvním cyklu, ve kterém
selže databáze – přitom kód vypadá, že chyby ošetřuje.

Dokumentace ORM k tomu říká jasně: další unit of work po výjimce patří novému
EntityManageru. V praxi to znamená v `catch` bloku nejdřív zavolat
`$this->registry->resetManager()` a teprve pak zapsat stav řádku, nebo si
pro stavové updaty držet oddělené DBAL spojení mimo ORM.
:::

:::callout{type="pattern"}
### Konfigurace supervisord pro outbox dispatch {#supervisor-heading}

:::code{language="bash" filename="/etc/supervisor/conf.d/outbox-dispatch.conf"}
; /etc/supervisor/conf.d/outbox-dispatch.conf
[program:outbox-dispatch]
command=php /var/www/app/bin/console app:outbox:dispatch --time-limit=3600
autostart=true
autorestart=true
startsecs=2                 ; proces běží hodinu, start je tedy vždy "úspěšný"
stopwaitsecs=10
stdout_logfile=/var/log/outbox-dispatch.log
stderr_logfile=/var/log/outbox-dispatch.err
user=www-data
numprocs=1                  ; jediný worker – vyhneme se duplicitnímu pollingu
process_name=%(program_name)s

; Command polluje ve vnitřní smyčce (100 ms interval) a po hodině
; (--time-limit=3600) se sám čistě ukončí. autorestart=true ho pak
; nastartuje znovu – stejný vzor jako u messenger:consume --time-limit.
:::
:::

:::callout{type="warn"}
### Pozor: relay musí být **jediný worker** {#single-worker-heading}

Polling worker spouštějte vždy jako **singleton** (`numprocs=1`
v supervisoru, `replicas: 1` v Kubernetes Deploymentu, případně leader
election přes Redis lock). Dva paralelní workery, kteří selectují stejnou outbox tabulku,
způsobí **double publish** – každý event se odešle dvakrát ve stejnou chvíli,
zátěž brokera roste lineárně s počtem replik a Inbox musí vybalancovat víc duplicit.

Jakmile jeden worker přestane stačit, sáhněte po
`SELECT ... FOR UPDATE SKIP LOCKED` v Postgresu nebo MySQL 8 – každý
worker si pak zarezervuje vlastní batch řádků. Řádově zvládne jeden PHP proces
jednotky tisíc zpráv za sekundu; na každou dělá deserializaci, publish s čekáním
na ACK a UPDATE řádku, takže výsledek určuje latence brokera a databáze, ne PHP.
Konkrétní číslo změřte na vlastní konfiguraci, žádná univerzální hodnota
neexistuje.
:::

### Varianta B: CDC / Debezium {#relay-cdc-heading}

**Change Data Capture** (CDC) je kanonicky *Transaction Log Tailing*: místo
aplikačního polleru čte Postgres WAL (*Write-Ahead Log*) nebo MySQL binlog
a streamuje každý `INSERT` do outbox tabulky přímo do Kafky. Standardním nástrojem
je [Debezium](https://debezium.io) – Kafka Connect plugin, který
funguje jako logický replikační odběratel databáze.

Tok je následující: aplikace zapíše řádek do `outbox`, Debezium ten `INSERT` uvidí
v transakčním logu, vytvoří Kafka record a pošle ho do odpovídajícího topicu.
Řádek se pak už nemění, tabulka funguje jako append-only log.

| Aspekt | Polling Publisher (A) | Transaction Log Tailing / Debezium (B) |
|---|---|---|
| Latence | 50–500 ms (polling interval) | jednotky až desítky ms (push z WAL) |
| Operační složitost | 1× console command + supervisor | Kafka + Kafka Connect + Debezium konektor + monitoring 4 procesů |
| Volba brokera | Libovolný (RabbitMQ, SQS, Redis, Doctrine async) | Pouze Kafka (resp. Pulsar, Kinesis přes adaptér) |
| Scale-out | jednotky tisíc zpráv/s na worker, lineárně s replikami přes SKIP LOCKED | dáno Kafkou, o dva řády výš |
| Garance pořadí | Best-effort podle `occurred_at` | Per-partition podle `aggregate_id` |
| Provozní riziko | Zaseknutý worker = rostoucí lag | Zaseknutý konektor drží replikační slot a WAL se hromadí na disku primární databáze |
| Doporučeno pro | Běžný Symfony projekt | Multi-tenant SaaS, finanční systémy, IoT |

V této knize budeme dál pracovat s variantou A – pro typický Symfony projekt
vyváží spolehlivost a operační režii v poměru, který nepřidává Kafka stack
jen kvůli outboxu. Debezium se vyplatí teprve tehdy, když máte už *pět produkčních
Kafka konzumentů* a outbox lag začíná být úzkým hrdlem.

Konfiguračně jde o Kafka Connect konektor (REST API, nebo deklarativně přes
Strimzi operator). Jádrem je transformace **Outbox Event Router**
(`io.debezium.transforms.outbox.EventRouter`). Ta má vlastní představu
o schématu tabulky a stojí za to ji znát dřív, než konektor nasadíte:

- Routuje podle sloupce `aggregatetype`, ne podle typu události. Výchozí topic
  je `outbox.event.<hodnota aggregatetype>` – pro hodnotu `Order` tedy
  `outbox.event.Order`, nikoli topic pojmenovaný po třídě `OrderPlaced`.
- Klíčem Kafka zprávy je `aggregateid`. Právě odtud plyne pořadí uvnitř partition.
- Sloupec `id` cestuje jako hlavička zprávy a dokumentace ho nabízí přímo
  k deduplikaci na straně konzumenta.
- SMT automaticky odfiltruje `DELETE` operace nad outbox tabulkou. Kanonický
  Debezium model proto řádek vloží a hned smaže; sloupec `status` v něm vůbec
  nefiguruje.

Tvrzení „na aplikační straně se nic nemění“ tedy neplatí. Schéma z [15.03](#schema)
má sloupce `aggregate_type` a `aggregate_id` s podtržítkem a navíc stavový model,
takže přechod na variantu B znamená buď přejmenovat sloupce podle výchozího
očekávání SMT, nebo přemapovat volby `route.by.field` a `table.field.event.*`.
Rozhodnout se musíte i u stavu: buď `status` ponecháte kvůli auditní stopě
a smíříte se s tím, že ho konektor ignoruje, nebo přejdete na insert-and-delete
model, který nepotřebuje kompakci.

Pro Postgres se k tomu přidá logická replikace. Výchozí `plugin.name` konektoru
je `decoderbufs`, který vyžaduje serverové rozšíření; `pgoutput` je v Postgresu
od verze 10 nativní, a proto v praxi častější volba. Vyžaduje `wal_level = logical`
a pro uživatele konektoru privilegium `CREATE` kvůli vytvoření publikace.

*Citace: Debezium dokumentace –
[Outbox Event Router](https://debezium.io/documentation/reference/stable/transformations/outbox-event-router.html)
(Red Hat, 2019+).*

### Doctrine transport jako outbox bez vlastní tabulky {#doctrine-transport-outbox-heading}

Symfony Messenger nabízí třetí cestu, která nevyžaduje vlastní outbox tabulku
ani relay command. Transport `doctrine://default` ukládá zprávy do tabulky
`messenger_messages` ve **stejné databázi**, kde žije doménový stav. Atomicitu
zajišťuje middleware `doctrine_transaction` na **command busu**: transakce,
kterou middleware otevře kolem command handleru, obalí uložení agregátu
i dispatch eventu na doctrine transport. Podmínkou je, že transport používá
totéž DB spojení jako doménový stav – tedy `default` entity manager. Dual-write
problém tím mizí – buď se commitne order i zpráva, nebo nic. Worker
`messenger:consume async` pak zprávu vyzvedne a zpracuje, případně přepošle dál.

:::callout{type="pattern"}
### YAML: Routing eventu na Doctrine transport {#doctrine-transport-routing-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml" highlights="6,10,13"}
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction   # transakce handleru obalí agregát i dispatch eventu

        transports:
            async:
                dsn: 'doctrine://default'    # totéž spojení jako doménový stav (default EM)

        routing:
            App\Ordering\Domain\Event\OrderPlaced: async
:::
:::

Symfony dokumentace tuhle konfiguraci nikde nenazývá outboxem; slovo v ní nepadne.
Atomicita ale reálně platí. `DoctrineTransactionMiddleware` otevře transakci nad
spojením entity manageru, spustí handler, pak flushne a commitne. `Connection::send()`
doctrine transportu je prostý `INSERT` nad týmž spojením a vlastní izolovanou
transakci neotevírá. Zápis zprávy i flush agregátu proto commitnou společně.

:::callout{type="warn"}
### Dvě konfigurace, které atomicitu tiše ruší {#doctrine-transport-traps-heading}

**`DispatchAfterCurrentBusStamp`.** Docblock `DispatchAfterCurrentBusMiddleware`
říká přímo, že registrovat ho před `doctrine_transaction` znamená, že
sub-dispatchnuté zprávy s tímto stampem se odbaví až po commitu Doctrine transakce.
Dokumentace přitom `dispatch_after_current_bus` doporučuje registrovat právě
před `doctrine_transaction`. Kdo tuhle radu zkombinuje s doctrine transportem
v roli outboxu, vrátí si dual-write – zpráva odchází mimo transakci, která
uložila agregát.

**`auto_setup`.** Transport si tabulku `messenger_messages` vytváří sám jen tehdy,
když na spojení neběží transakce. Uvnitř transakce `TableNotFoundException`
propadne dál a handler spadne. Tabulku proto vytvořte migrací a `auto_setup`
vypněte – dokumentace to nezávisle doporučuje i pro běžný produkční provoz.
:::

Daň za pohodlí je trojí. Formát uložené zprávy je svázaný s Messengerem – payload
serializuje envelope i se stampy, takže ho mimo Symfony nikdo rozumně nepřečte.
Auditovatelnost a retence jsou horší než u vlastní outbox tabulky: zpracované
řádky worker maže, žádný stav `sent`, žádné `last_error`, žádná historie pro
rozbor incidentu. A nad schématem tabulky nemáte kontrolu – definuje ho
Messenger, ne vaše migrace.

Pro menší systémy je to přesto nejjednodušší správná volba: dual-write je
vyřešený, kód se omezí na konfiguraci a jeden worker. Vlastní outbox tabulka
se vyplatí, až když potřebujete auditní stopu, řízenou retenci nebo publish
do brokera mimo Messenger.

### Pořadí zpráv: best-effort, ne garance {#relay-ordering-heading}

`ORDER BY occurred_at` sugeruje víc, než dokáže splnit. Katalog microservices.io
u Polling Publisheru uvádí drawback „tricky to publish events in order“ a v této
implementaci se sejdou hned tři důvody. Hodnota `occurred_at` vzniká v PHP procesu,
takže napříč instancemi podléhá odchylce hodin. Při shodné hodnotě není pořadí
definované vůbec. A relay publikuje řádek po řádku, takže selhání uprostřed batche
pustí pozdější událost před dřívější.

Spolehlivé pořadí lze držet jen per agregát a jen tehdy, když ho nese klíč zprávy –
proto je ve schématu `aggregate_id`. V Kafce z něj plyne partition, uvnitř které
je pořadí garantované. Napříč agregáty žádné globální pořadí nečekejte a nestavte
na něm doménovou logiku.

Filtru `status = 'pending'` se naopak netýká *gap problém*, který popisuje kapitola
[Event Sourcing](/event-sourcing#auto-increment-gap-heading). Ten trápí relay,
který si drží checkpoint na auto-increment ID: transakce s nižším ID může commitnout
později a relay ji za posunutým checkpointem už nepřečte. Outbox tabulka se stavovým
sloupcem checkpoint nemá. Řádek je viditelný teprve po commitu a zůstane `pending`,
dokud ho relay nepublikuje – opožděný commit se prostě objeví v některém dalším cyklu.

## 15.06 Idempotent Inbox – strana subscribera {#inbox}

Outbox dává at-least-once delivery, takže subscriber **musí** počítat s tím,
že stejný event dostane víckrát. Pokud je vedlejší efekt handleru ne-idempotentní (typicky
`UPDATE counter SET value = value + 1`), duplicita se okamžitě projeví jako
chybný stav read modelu – zákazník vidí 200 Kč na účtu místo 100 Kč, počet objednávek
je dvojnásobný, e-mail dorazí 2×.

Řešení má v katalozích dvě jména. microservices.io vede vzor jako **Idempotent
Consumer** a doporučuje tabulku zpracovaných zpráv s kompozitním klíčem
`(subscriberId, messageID)`. Starší je **Idempotent Receiver** z *Enterprise
Integration Patterns* (Hohpe & Woolf, 2003): příjemce navržený tak, aby tutéž
zprávu snesl vícekrát. Dál v kapitole používáme pracovní jméno **Idempotent Inbox**,
protože stojí symetricky proti outboxu.

Realizace je doplněk k outboxu: tabulka `inbox` v databázi subscribera
s kompozitním UNIQUE constraintem na dvojici `(event_id, consumer)`. Před
zpracováním eventu handler zkontroluje, zda je daná dvojice už v inboxu; pokud ano, ackne brokerovi a skončí. Pokud ne,
zpracuje doménovou logiku a v *téže transakci* vloží nový řádek do inboxu.
UNIQUE constraint je pojistka proti race condition.

:::diagram{fig="15.6-A" title="Idempotent Inbox – deduplikace na straně subscribera" src="images/diagrams/14_outbox/inbox_idempotency.svg"}
:::

:::callout{type="pattern"}
### PHP: Doctrine entita InboxMessage {#inbox-message-entity-heading}

:::code{language="php" filename="src/Inbox/Domain/InboxMessage.php" highlights="11,12"}
<?php

declare(strict_types=1);

namespace App\Inbox\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'inbox')]
#[ORM\UniqueConstraint(name: 'uniq_inbox_event_consumer', columns: ['event_id', 'consumer'])]
class InboxMessage
{
    public function __construct(
        /** Surrogate PK – deduplikaci nese kompozitní UNIQUE výše. */
        #[ORM\Id]
        #[ORM\Column(type: 'uuid', unique: true)]
        public Uuid $id,

        #[ORM\Column(type: 'uuid')]
        public Uuid $eventId,

        #[ORM\Column(type: 'string', length: 64)]
        public string $consumer,

        #[ORM\Column(type: 'datetime_immutable')]
        public \DateTimeImmutable $processedAt = new \DateTimeImmutable(),
    ) {}

    public static function record(Uuid $eventId, string $consumer): self
    {
        return new self(id: Uuid::v7(), eventId: $eventId, consumer: $consumer);
    }
}
:::
:::

:::callout{type="pattern"}
### PHP: Rozhraní InboxRepository {#inbox-repository-heading}

:::code{language="php" filename="src/Inbox/Application/InboxRepository.php"}
<?php

declare(strict_types=1);

namespace App\Inbox\Application;

use Symfony\Component\Uid\Uuid;

interface InboxRepository
{
    public function isProcessed(Uuid $eventId, string $consumer): bool;

    public function markProcessed(Uuid $eventId, string $consumer): void;
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

Marketingové materiály brokerů občas slibují „exactly-once delivery“. **Tato
garance neexistuje v žádném distribuovaném systému** – doručení přes nespolehlivý
kanál potvrzuje příjemce zprávou, která se sama může ztratit, takže odesílatel
nikdy neví, zda posílat znovu. Co Outbox+Inbox dohromady
poskytují, je *exactly-once efekt na straně subscribera*. Zpráva může do
brokera dorazit a opustit ho víckrát, ale vedlejší efekt (úprava read modelu, odeslání
e-mailu, strhnutí platby) proběhne *právě jednou*.

Helland v paperu z roku 2007 tutéž myšlenku shrnuje stručně: svět doručuje
at-least-once a teprve aplikace vytváří dojem exactly-once.
:::

:::callout{type="note"}
### Idempotence na hranici HTTP API {#idempotency-api}

Duplicitní zápisy vznikají i o vrstvu výš, mimo broker: klient při timeoutu
zopakuje `POST /orders` a server vytvoří dvě objednávky. To už není práce
pro outbox ani inbox, ale pro HTTP vrstvu. Standardním řešením je hlavička
`Idempotency-Key` podle [specifikace Stripe](https://docs.stripe.com/api/idempotent_requests),
kterou přebírá i IETF draft
[draft-ietf-httpapi-idempotency-key-header](https://datatracker.ietf.org/doc/draft-ietf-httpapi-idempotency-key-header/).
Deduplikaci na úrovni Messenger handlerů rozebírá kapitola
[DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli#b3-idempotence).
:::

### Retence inbox tabulky {#inbox-retention-heading}

Inbox roste stejně jako outbox, jen o něm nikdo nemluví. Každá zpracovaná zpráva
v něm nechá řádek a nic ho nemaže. Po roce provozu je z pojistky proti duplicitám
největší tabulka v databázi subscribera.

Horní hranici retence určuje doba, po kterou může broker zprávu ještě doručit:
maximální TTL zprávy plus nejdelší retry okno relay procesu. Řádek starší než
tento součet už nemá co deduplikovat. V praxi se drží 30 dní, což je bezpečně
nad běžnou konfigurací obojího, a maže se stejným batch cronem jako outbox.
Kdo retenci zvolí kratší než reálné retry okno, otevře si díru: opožděná zpráva
projde jako nová.

## 15.07 Provozní aspekty {#provoz}

Outbox ve *vývojovém* prostředí funguje, jak má. V produkci ale narazíte na čtyři
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
### PHP: Kompakce outbox tabulky – MySQL (Symfony command) {#cleanup-command-heading}

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

PostgreSQL: syntaxe `DELETE ... LIMIT` (i `INTERVAL 30 DAY`) je MySQL/MariaDB
specifikum, Postgres ji nezná. Batch se vymezí poddotazem nad `id`, případně
`ctid`: `DELETE FROM outbox WHERE id IN (SELECT id FROM outbox WHERE
status = 'sent' AND sent_at < now() - interval '30 days' LIMIT 10000)`.

### Dead-letter queue pro permanentní selhání {#dlq-heading}

Některé eventy se nikdy nepublikují: schema změna v subscriberu, kterou nikdo
nevyřešil, broken payload (NaN v JSON), poison message, který shodí libovolného
consumera. Po N attempts (typicky 5) je `OutboxMessage::markFailed()`
přepne do stavu `failed`. Tyto řádky chceme:

- **Vyčlenit z hot pathy** – relay je už nezkouší publikovat.
- **Hlasitě upozornit** – alert `outbox_failed_total > 0`.
- **Mít na ně CLI nástroj** – `app:outbox:retry-failed` nebo
  ruční SQL update statusu zpět na `pending` po opravě subscribera.
- **Nikdy nemazat automaticky** – failed řádek je důkaz nedoručeného
  doménového eventu a chcete ho mít evidovaný i po týdnu.

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

Outbox má specifický I/O profil: vysoký INSERT rate, krátký životní cyklus (řádek vznikne →
během sekund se UPDATE na `sent` → po N dnech DELETE), nikdy se nečte historie.
PostgreSQL standardní autovacuum tuning na takový profil **není dimenzovaný**
a po několika dnech provozu narážíte na index bloat:

- INSERT vytváří mrtvé řádky v tabulce i v indexech (kvůli MVCC).
- UPDATE statusu vytváří další verze řádku.
- Standardní autovacuum threshold (`autovacuum_vacuum_scale_factor = 0.2`)
  čeká, než se nasbírá 20 % mrtvých řádků – při tisících zápisů za sekundu
  to je řád minut.
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
REINDEX INDEX CONCURRENTLY idx_outbox_status_time;
:::
:::

### Partitioning při vysokém objemu (PostgreSQL) {#partitioning-heading}

Při trvale vysokém objemu, tedy v řádu tisíců událostí za sekundu, se single-table
outbox stává provozním úzkým hrdlem. PostgreSQL declarative partitioning podle
`occurred_at` umožňuje:

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
    message_type VARCHAR(255) NOT NULL,
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

Singleton polling worker (`replicas: 1` v Kubernetes) je nejjednodušší konfigurace, ale
má dvě slabiny: **single point of failure** (worker spadne → lag roste, dokud
ho `livenessProbe` nerestartuje) a **omezenou propustnost** (jeden PHP proces
odbaví řádově jednotky tisíc zpráv za sekundu).

Pro produkci s vyšším objemem nebo vyšším HA požadavkem se nabízí dvě cesty:

**Cesta 1 – leader election přes Redis/etcd.** Více workerů běží, ale jen jeden
je „leader“ a publikuje. Když leader spadne, do 5 s ho nahradí jiný. Důsledek:
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
        // SET key value NX EX ttl – atomický „acquire if not exists, with TTL“.
        $result = $this->redis->set(
            self::LEASE_KEY,
            $this->instanceId,
            'EX',
            self::LEASE_TTL_SECONDS,
            'NX',
        );
        // Predis vrací objekt Response\Status, ne řetězec – porovnává se přes cast.
        if ((string) $result === 'OK') {
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
začne dispatchovat – nový leader. Pozor: processing batch musí **doběhnout dřív,
než TTL lease vyprší** – nebo si worker musí lease během batche průběžně obnovovat.
Jinak lease převezme nový leader a začne dispatchovat řádky, které starý worker
ještě publikuje → double publish.

**Cesta 2 – `SELECT … FOR UPDATE SKIP LOCKED`.** Více workerů paralelně, každý
si zarezervuje vlastní batch řádků. Žádný leader, žádný single point of failure,
škáluje se lineárně s počtem worker replik.

:::diagram{fig="15.7-A" title="Distributed relay – 4 workery paralelně přes SKIP LOCKED" src="images/diagrams/14_outbox/distributed_relay.svg"}
:::

:::callout{type="pattern"}
### SQL: Concurrent dispatch přes SKIP LOCKED {#skip-locked-heading}

:::code{language="sql" filename="snippet.sql"}
BEGIN;

-- Worker si zarezervuje 100 pending řádků. Ostatní workery uvidí jen ty,
-- které tento worker NEzamknul.
SELECT id, message_type, payload, occurred_at
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

Propustnost pak roste zhruba lineárně s počtem workerů a **at-least-once** garance
zůstává zachovaná. PostgreSQL od verze 9.5 (`SKIP LOCKED`) i MySQL 8
to podporují. Cena: nutnost koordinace pořadí (eventy ze stejného agregátu
se mohou publikovat out-of-order, pokud workery zpracovávají různé batche).
Pokud subscriber pořadí potřebuje, partition outbox na `aggregate_id` a každý
worker řízeně zpracovává jen vlastní partition.

### Backpressure – co když broker nestíhá {#backpressure-heading}

Když Kafka/RabbitMQ nestíhá přijímat (síťová chyba, broker disk full, partition
leader election), relay worker dostává timeout/error na publish. Outbox řádky
zůstávají `pending`, kupí se. **Nezasahujte do produkčních INSERTů** – jakmile
začnete blokovat aplikační vrstvu, šíříte výpadek brokera do core domény.

Standardní vzor má čtyři složky. Worker po failed publish přechází na
exponential backoff – čeká 1 s, 2 s, 4 s, maximálně 30 s – a mezitím loguje
`outbox_publish_errors_total`. Alert hlídá rychlost růstu pending:
`delta(outbox_pending_count[5m]) > 10000` signalizuje, že produce převyšuje
consume a broker nestíhá. Kapacitně musí databáze absorbovat 30 minut
brokerového výpadku; při 1k events/s to je 1,8 mil. řádků navíc, tedy rozpočet
na disk a vacuum. A u low-priority eventů (audit, metrics), které jsou
tolerantní ke ztrátě, lze při sustained backpressure zvážit řízený sampling –
doménové eventy (`OrderPlaced`) ale zahodit nelze, ty musí dorazit.

## 15.08 Anti-vzory {#antivzory}

Outbox má jednoduché schéma, a právě proto kolem něj v code review padají stále
stejné chyby, které ruší jeho garance a vrací systém k dual-write problému. Níže
jsou ty nejčastější.

:::callout{type="warn"}
### Publish napřímo z metody agregátu {#anti-direct-publish-heading}

Některé framework wrappery (Laravel events, Symfony EventDispatcher nad DB
entitami) lákají k „*fire-and-forget*“ stylu přímo z metody agregátu.
Pokud event letí do brokera ještě před commitem doménové transakce – ať už
kvůli sync transportu, kvůli middleware pořadí nebo kvůli explicitnímu
`$bus->dispatch()` – máme dual-write zpět. Smysl outboxu je v tom, že event jde
**do téže DB transakce** jako doménový stav.
:::

:::callout{type="warn"}
### Outbox bez UNIQUE constraintu na `id` / inbox bez UNIQUE na `(event_id, consumer)` {#anti-no-unique-heading}

Řádek bez UNIQUE může být v race condition zapsán dvakrát (relay padá uprostřed
INSERTu, retry přijde s týmž UUID). Bez UNIQUE constraintu DB to dovolí
a relay pak publikuje *dvojí* verzi téže události. UNIQUE má roli technického
invariantu, ne dekorativního detailu.
:::

:::callout{type="warn"}
### Inbox check a vedlejší efekt ne v jedné transakci {#anti-inbox-no-tx-heading}

Klasická chyba: `if ($inbox->isProcessed($id)) return;` se provede
v autocommit režimu, vedlejší efekt na read modelu se provede také v autocommit režimu
a teprve *potom* se vloží řádek do inboxu. Mezi check a insert ale může
prolézt druhý paralelní worker, který stejný check provede jako „nový“ a zduplikuje
update. Řešením je **celý handler obalit do `wrapInTransaction`**
a UNIQUE constraint na inboxu jako pojistka.
:::

:::callout{type="warn"}
### Read model bez idempotentní logiky {#anti-no-idempotent-side-effect-heading}

I se správným inboxem se může stát, že vedlejší efekt uvnitř transakce nebyl dotažen
do idempotentního stavu. Klasický příklad: `UPDATE counter SET value = value + 1`
pro každý `OrderPlaced` – pokud kdy v budoucnu vypneme inbox check
(např. při reinicializaci), counter naskočí o víc. Doporučení: pokud možno preferovat
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

Volání `$bus->dispatch()` před tím, než `EntityManager::flush()` opravdu zapíše
do DB, je dual-write přímo z učebnice. Bez middlewaru `doctrine_transaction`
totiž kolem handleru žádná transakce nevzniká: platí běžný autocommit režim
DBAL spojení, ve kterém si každý `flush()` obalí jen vlastní zápisy. Handler
proto obalte explicitně do `wrapInTransaction` všude tam, kde middleware
v `messenger.yaml` aktivní nemáte.
:::

## 15.09 Migrace existujícího projektu – krok za krokem {#migrace}

Jak na Outbox, když máte 18 měsíců starý Symfony projekt, sto handlerů a publish-after-flush
už běží někde v útrobách? Postup je inkrementální, ne big-bang refaktor.
Outbox přidáváte handler po handleru, vedle stávajícího chování, a starý kód odstraňujete
teprve když nový jistě funguje.

### Krok 1: Přidat outbox tabulku a entitu {#migrace-krok-1-heading}

Vytvořte migraci podle sekce [15.03](#schema), spusťte
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

Implementujte `OutboxDispatchCommand` ze sekce [15.05](#relay)
a nasaďte pod supervisorem. V tomto bodě může worker už publikovat eventy
z outboxu – pokud máte legacy publish dál aktivní, broker dostane *obě* verze.
Subscribery ale ještě nemají Inbox, takže duplicitu nikdo neodchytí.

### Krok 4: Přidat inbox subscriberům jeden po druhém {#migrace-krok-4-heading}

Pro každý subscriber kontextu vytvořte `inbox` tabulku, refaktorujte handler
podle sekce [15.06](#inbox). Toto je nejdelší krok migrace (typicky týdny),
ale paralelizovatelný napříč týmy – každý kontext si Inbox přidává nezávisle.

### Krok 5: Vypnout legacy publish {#migrace-krok-5-heading}

Až mají všichni subscribery inbox, smažete v handleru původní `$bus->dispatch()`
a doručení doménových eventů zůstává jen na outboxu. **Jde o riskantní krok** – během
prvních dnů sledujte outbox lag a inbox dedupy. Pokud něco selhává, revert pull requestu
vrátí změnu během pěti minut.

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

## 15.10 Shrnutí {#summary}

Outbox Pattern stojí na tabulce navíc, jednom Symfony commandu a úpravě jednoho
application handleru. Výměnou vyřadí celou třídu chyb (ztracené eventy, fantom eventy),
které byste jinak ladili reaktivně ve tři ráno z logů. Garance, kterou tím získáte,
je at-least-once delivery doménových událostí napříč libovolným message brokerem –
bez závislosti na XA, bez 2PC, bez speciální cloud služby.

Idempotent Inbox je nutný protějšek na straně subscribera. Bez něj se duplikace
z outboxu propíše do read modelů a side-effectů, čímž ztratíme to, co jsme outboxem
získali. Kombinace Outbox + Inbox dohromady poskytuje *exactly-once efekt* –
každý event se v read modelu projeví právě jednou, i když broker dodá zprávu vícekrát.

### Srovnání s alternativami {#alternativy-heading}

Outbox není jediná odpověď na dual-write. Ostatní cesty mají užší záběr nebo vyšší cenu.

| Řešení | Jak řeší dual-write | Kdy dává smysl |
|---|---|---|
| Transactional Outbox | Zápis události do téže DB transakce, publikuje relay | Výchozí volba všude, kde agregát žije v ACID databázi |
| Event Sourcing | Událost *je* stav, druhý zápis neexistuje | Když se pro doménu vyplatí i zbytek modelu, ne jen kvůli doručení |
| Listen-to-yourself | Aplikace nejdřív publikuje, DB zapíše až konzument vlastní zprávy | Když je broker spolehlivější než vlastní DB a čtení smí být opožděné |
| Synchronní volání | Dual-write nevzniká, kontexty se volají přímo | Malý systém bez asynchronní integrace; platí se autonomií kontextů |
| 2PC / XA | Distribuovaná transakce nad DB i brokerem | Prakticky nikdy – viz [15.01](#dual-write) |

Kombinace se nevylučují. Event-sourcovaný kontext outbox tabulku nepotřebuje,
protože event store ji zastane, ale Inbox na straně konzumenta potřebuje pořád.

Hlavní body pro praxi:

- Outbox je **tabulka v téže DB jako doménový stav** – jinak nedává smysl.
- Doctrine entita potřebuje `#[ORM\Index(columns: ['status', 'occurred_at'])]`,
  bez něj relay dělá full table scan při každém pollingu.
- `$em->wrapInTransaction(...)` v handleru garantuje atomicitu order +
  outbox řádky.
- Polling Publisher pod supervisorem stačí pro téměř každý Symfony projekt;
  Transaction Log Tailing přes Debezium pouze pro Kafka-native systémy
  s vysokým objemem, a i tam za cenu jiného schématu tabulky.
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
  answer: 'Pro běžný Symfony projekt zvolte Polling Publisher (varianta A). Operační režie je minimální (jeden Symfony command pod supervisorem) a latence pod 1 sekundou je dostatečná pro typické obchodní scénáře (objednávky, platby, notifikace). Debezium / CDC se vyplatí, až když máte (a) Kafkovou infrastrukturu už nasazenou, (b) latenční požadavek pod 50 ms, (c) objem nad 10 000 events/s, (d) tým, který má zkušenost s Kafka Connect. Jinak zaplatíte multinásobnou operační složitost za marginální benefit. Detail v <a href="#relay">sekci 15.05</a>.'
- question: 'Co když používáme NoSQL databázi (MongoDB, Cassandra, DynamoDB)?'
  answer: 'Pokud váš agregát žije v NoSQL bez ACID transakcí napříč více dokumenty (Cassandra, raná verze MongoDB), klasický Outbox Pattern nefunguje – atomicita zápisu order + event mezi dvěma collections není garantovaná. Možnosti: (1) MongoDB 4.0+ má multi-document transakce, takže Outbox lze, (2) DynamoDB nabízí TransactWriteItems, takže Outbox jde, (3) Cassandra nemá multi-row atomicitu – používá se Change Data Capture nebo jednodokumentové event sourcing s eventy embedded v agregátu. Volba úložiště pro doménový stav rozhoduje, zda lze Outbox vůbec implementovat.'
- question: 'Jak velký dělat batch v relayi?'
  answer: 'Standardně 100 řádků za polling cyklus s intervalem 100 ms. Sama kadence tedy dovolí 1 000 zpráv za sekundu na jeden worker; skutečné číslo určí latence brokera a databáze. Pokud lag stoupá nad 5 sekund a CPU brokera má rezervu, zvyšte limit na 500 nebo zkraťte interval na 50 ms. U batch nad 1 000 narazíte na DB serializaci updateů – místo jednoho velkého batche pak rozdělte na víc workerů s SELECT ... FOR UPDATE SKIP LOCKED. Hlavní pravidlo: měřte před tunováním, ne tunujte „na cit“.'
- question: 'Vyplatí se Outbox v monolitu?'
  answer: 'Ano, vyplatí – protože dual-write problem nevzniká až mezi mikroservisami, ale mezi <em>libovolnými dvěma transakčními systémy</em>. Monolitická aplikace publikující eventy do RabbitMQ/Redis Streams má přesně stejný problém jako mikroservis: DB ACID je oddělený od ACK message brokera. Pokud váš monolit už má event-driven kontexty (Symfony Messenger s async transportem, Spatie Laravel events, ...), Outbox se vyplatí stejně jako v mikroservisách. Jediný případ, kdy ho nepotřebujete, je <em>striktně synchronní</em> monolit, kde publish neexistuje a všechno teče v jedné HTTP transakci.'
- question: 'Co dělat při dlouhodobém výpadku brokera?'
  answer: 'Outbox jako celek je <strong>self-healing</strong>: když broker leží 30 minut, relay worker dostává timeout/connection refused, řádky zůstávají ve stavu pending, počet vzroste, lag exploduje – ale aplikační handlery dál zapisují doménové eventy (jen do DB). Po obnovení brokera relay během několika minut vyšle backlog, lag se vrátí k normálu, subscribery dohrabou stav. Co je třeba: (a) alert na lag &gt; 30 s aby tým o výpadku věděl, (b) dostatek místa v DB na nahromaděné pending řádky (typicky není problém – řádky jsou malé), (c) kompakce nesmí mazat <code>pending</code> řádky – mazat lze jen <code>sent</code> starší než N dní. Pokud broker chybí déle než N dní, máte dost času škálovat dispatch capacity nebo migrovat na alternativní broker.'
- question: 'Musím použít UUID/ULID, nebo stačí AUTO_INCREMENT?'
  answer: 'Použijte UUID v7 (případně ULID), ne AUTO_INCREMENT. Důvody: (1) UUID v7 je globálně unikátní napříč instancemi DB – nehrozí kolize při replikaci, restore z backupu nebo migraci. (2) Nese časový komponent, takže ID koreluje s pořadím vytvoření – užitečné pro debugging a pro indexové scany. (3) Klient ho může vygenerovat předem a poslat jako event_id v Idempotency-Key headeru. (4) AUTO_INCREMENT komplikuje sharding a multi-region nastavení. Symfony Uid komponenta poskytuje pohodlné API: <code>Uuid::v7()</code> v entitě stačí.'
:::
