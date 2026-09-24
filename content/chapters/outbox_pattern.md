---
route: outbox_pattern
path: /outbox-pattern
title: 'Outbox Pattern – spolehlivé publikování událostí'
page_title: "Outbox Pattern: spolehlivé doručení eventů | DDD Symfony"
meta_description: "Transactional Outbox a Idempotent Inbox v Symfony 8 a Doctrine: spolehlivé doručení událostí a konec dual-write problému. Podle Pata Hellanda."
meta_keywords: "Outbox Pattern, Transactional Outbox, Inbox Pattern, Idempotency, Dual-write problem, Pat Helland, Chris Richardson, Symfony Messenger, Doctrine, at-least-once, exactly-once, RabbitMQ, eventy, CDC, Debezium"
og_type: article
published: "2026-04-29"
modified: 2026-09-24
breadcrumb_name: Outbox Pattern
schema_type: TechArticle
schema_headline: "Outbox Pattern – spolehlivé publikování událostí"
chapter_number: "15"
category: Vzory
deck: 'Typická chyba: zapíšete <code>Order</code> do databáze, vzápětí se rozbije RabbitMQ, ale order tam zůstane bez události <code>OrderPlaced</code>. Subscribeři se o objednávce nedozvědí. Outbox Pattern řeší tento <em>dual-write problem</em> na úrovni jedné DB transakce; jeho dvojče Inbox Pattern řeší deduplikaci na straně subscriberů. V Symfony 8 je to jeden Doctrine entity manager, jeden Messenger transport a zhruba 80 řádků kódu.'
reading_time: 46
difficulty: 4
github_examples: Chapter11_OutboxPattern
---

V kapitolách o [CQRS](/cqrs), [Event Sourcingu](/event-sourcing)
a [ságách](/sagy-a-process-managery) se opakovaně objevil stejný předpoklad:
když agregát po commitu publikuje událost ven z kontextu, **spolehlivě dorazí
do message brokeru** a odtud k subscriberům. Ten předpoklad neplatí. Mezi
zápisem do databáze a dispatchem do Messenger transportu stojí síťový skok a dva
nezávislé systémy, z nichž každý může selhat samostatně. Důsledkem je *dual-write
problem*, jeden z nejčastějších zdrojů tichých nekonzistencí v event-driven
architekturách.

**Transactional Outbox Pattern** je standardní řešení dual-write problému.
Katalogovou definici vzoru formuloval Chris Richardson; Pat Helland k němu
v práci *Life Beyond Distributed Transactions* (2007) dodává rámec: odmítnutí
distribuovaných transakcí a požadavek na idempotentního příjemce. Protějšek na
straně subscriberů se v katalozích jmenuje **Idempotent Consumer**, starším
názvem *Idempotent Receiver*; tato kapitola pro něj používá pracovní jméno
**Idempotent Inbox**, protože stojí symetricky proti outboxu.

Kapitola ukazuje schéma outbox tabulky s povinným indexem, implementaci s Doctrine
ORM a Symfony Messenger a dvě kanonické varianty relay procesu: Polling
Publisher a Transaction Log Tailing. Následuje provoz (outbox lag, kompakce,
dead-letter queue), migrační postup pro existující projekt a srovnání
s alternativami.

## 15.01 Dual-write problem {#dual-write}

Nejjednodušší implementace publikování vypadá nevinně: po doménové operaci se stav
zapíše do databáze a událost se rovnou dispatchne na message bus. Takový kód projde
code review bez poznámek – dokud se v produkci nezačnou hromadit ztracené události a stížnosti subscriberů typu „*vidím v API objednávku
12345, ale event `OrderPlaced` mi nikdy nedorazil*“.

:::callout{type="warn"}
### Naivní implementace publikování – anti-vzor {#naive-publish-heading}

:::code{language="php" filename="src/Ordering/Application/Handler/PlaceOrderHandlerNaive.php" highlights="22,25"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\PlaceOrder;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Ordering\Domain\ValueObject\CustomerId;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class PlaceOrderHandlerNaive
{
    public function __construct(
        private OrderRepository $orders,
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(PlaceOrder $command): void
    {
        $order = Order::placeWithItems(
                CustomerId::fromString($command->customerId),
                $command->items,
            );

        // 1) Zápis do DB – pod doctrine_transaction se commitne
        //    až po návratu handleru, tedy po kroku 2.
        $this->orders->save($order);

        // 2) Publish do brokeru (samostatný systém, samostatná chyba).
        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
:::
:::

**Krok 1 a krok 2 jsou dvě nezávislé transakce ve dvou různých systémech.**
Stačí mezi nimi jakákoliv chyba: síťový timeout, pád workeru, restart aplikace,
výpadek brokera, OOM kill PHP procesu. Systém pak skončí v jednom ze dvou
nesymetrických nekonzistentních stavů:

- **Zápis do DB prošel, dispatch do brokera ne.** Order existuje v databázi,
  ale event `OrderPlaced` se nikdy neodeslal. Subscriber kontext (Payment,
  Warehouse, Notifications) o objednávce *neví*. Zákazník ji vidí v API,
  ale platba se nestrhne, sklad nezarezervuje, e-mail nepřijde. Jde o nejhorší
  scénář: doménová událost se ztratí a v logu po ní nezůstane žádná stopa.
- **Dispatch do brokera prošel, zápis do DB ne.** Pod middlewarem
  `doctrine_transaction` je publish před commitem výchozí pořadí, ne chyba
  někoho, kdo kód přehází: middleware commitne až po návratu handleru. Commit
  pak může selhat *po* dispatchi, třeba kvůli optimistickému zámku. Subscribery dostanou event o objednávce, která fakticky
  neexistuje. Read model si přidá řádek, Payment se pokusí strhnout peníze za
  neexistující order, Notifications odešle e-mail s odkazem na 404. Vznikne „phantom
  event“ – událost, která se ve zdrojové DB *nestala*.

Oba scénáře porušují atomicitu napříč dvěma systémy a v event-driven architekturách
nejsou vzácné. Pat Helland problém pojmenoval v práci
*Life Beyond Distributed Transactions: An Apostate's Opinion* (2007). Jakmile
transakce přesahuje hranici jednoho úložiště, databáze atomicitu nezaručí
a musí ji obnovit aplikační logika. Slovo *outbox* ale v paperu
nepadne. Tabulku a relay proces popsal až Chris Richardson v knize
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
by šlo RabbitMQ a PostgreSQL zapojit do jedné XA transakce a problém by zmizel.
Praxe je jiná:

- **Běžné brokery XA nepodporují.** RabbitMQ distribuované XA transakce
  neimplementuje. Kafka má od verze 0.11 vlastní transakce, ty ale platí jen uvnitř
  Kafky; jako XA resource manager pro cizí koordinátor nevystupuje. Redis Streams
  nic takového nenabízejí. U cloudových služeb (AWS SNS/SQS, Google Pub/Sub)
  XA nepřipadá v úvahu. Závazek na XA-only infrastrukturu vážně omezuje
  volbu technologií.
- **XA je drahé.** Účastníci drží zámky po celou dobu obou fází a propustnost
  výrazně klesá. Helland v citovaném paperu odmítá 2PC především kvůli
  dostupnosti: protokol blokuje, jakmile je některý uzel nedostupný, a jeho
  křehkost vytváří nepřijatelný tlak na dostupnost celku.
- **Single point of failure.** Koordinátor 2PC je kritické místo;
  jeho selhání mezi fázemi prepare a commit zanechá účastníky v *in-doubt*
  stavu, kdy nejde rollbacknout ani commitnout. Pomůže jen manuální zásah.
- **Těsné provázání porušuje autonomii Bounded Contexts.** XA vyžaduje,
  aby všichni účastníci sdíleli koordinátora. To odporuje samostatné
  nasaditelnosti kontextů, se kterou počítá
  [DDD](/zakladni-koncepty#bounded-contexts)
  i [architektura mikroslužeb](/ddd-a-microservices).

Outbox Pattern tato omezení obchází: **nepotřebuje globálního koordinátora ani
XA transport**. Vystačí si s jednou ACID transakcí v DB, kterou aplikace už má
pro persistenci agregátu.
:::

*Citace:
Helland, P. **Life Beyond Distributed Transactions: An Apostate's Opinion**,
CIDR (2007); Richardson, C. **Microservices Patterns**, Manning (2018),
kapitola 3 – Transactional messaging; Microservices.io –
[Pattern: Transactional Outbox](https://microservices.io/patterns/data/transactional-outbox.html).*

## 15.02 Transactional Outbox – princip {#princip}

Místo dispatchu do brokera se **událost zapíše do tabulky `outbox`** ve stejné databázi,
kde žije doménový stav, a to *uvnitř stejné DB transakce* jako úprava agregátu.
Buď se zapíše obojí (order i jeho event), nebo nic (rollback celé transakce).
Atomicita je zpátky: oba zápisy leží v jediné ACID transakci jedné databáze,
ne ve dvou různých systémech.

Samostatný proces (**relay worker**, někdy nazývaný *publisher*
nebo *dispatcher*) tabulku asynchronně polluje. Vybírá řádky se stavem
`pending`, publikuje je do skutečného message brokeru a po úspěšném publishi
je označí jako `sent`. Tok má čtyři fáze:

:::diagram{fig="15.2-A" title="Transactional Outbox – čtyři fáze publikování" src="images/diagrams/14_outbox/outbox_flow.svg"}
:::

1. **Fáze 1 – doménová transakce.** Application handler v jedné Doctrine
   transakci uloží agregát i odpovídající outbox řádky.
2. **Fáze 2 – polling outboxu.** Relay worker periodicky (např. každých
   100 ms) selectuje pending řádky z outboxu, seřazené podle `occurred_at`.
   Výsledkem je best-effort FIFO, ne garantované pořadí. Proč, rozebírá
   [sekce 15.05](#relay-ordering-heading).
3. **Fáze 3 – publish do brokeru.** Pro každý řádek relay publikuje event
   do brokera a po obdržení ACK řádek označí jako `sent`. Obě operace neběží
   v jedné transakci. Pokud relay spadne mezi nimi, řádek zůstane `pending`
   a po restartu se publikace zopakuje. Z toho plyne garance at-least-once
   delivery, rozebraná níže.
4. **Fáze 4 – konzumace subscriberem.** Subscriber dostane delivery,
   zpracuje ji idempotentně (typicky přes [Inbox Pattern](#inbox)) a
   ackne brokerovi.

:::callout{type="note"}
### Garance Outbox Pattern: at-least-once delivery {#at-least-once-heading}

Outbox samotný garantuje **at-least-once delivery**: každá doménová
událost se k subscriberům dostane *alespoň jednou*, případně i víckrát.
Konkrétní scénář duplikace: relay úspěšně publikuje event do brokera
(broker poslal ACK, event je trvale uložen), ale spadne dřív,
než stihne zapsat `UPDATE outbox SET status='sent'`. Po restartu vidí
řádek pořád jako `pending` a publikuje ho znovu. Subscriber tak dostane
stejný event dvakrát.

Je to *záměrná* volba: možná duplikace je cena za to, že se žádný
event neztratí. **Exactly-once delivery v distribuovaných systémech
obecně neexistuje**: příjemce a odesílatel se nad ztrátovým kanálem nikdy
neshodnou na tom, že zpráva dorazila právě jednou.
Dosažitelný je *exactly-once efekt* na straně subscribera,
o který se stará [Idempotent Inbox](#inbox).
:::

## 15.03 Schéma `outbox` tabulky a Doctrine mapping {#schema}

Outbox tabulka má jedenáct sloupců. Každý řeší konkrétní provozní problém, který se
bez něj projeví až pod produkční zátěží.

Entita níže nese Doctrine atributy a sedí v namespace `App\Outbox\Domain`. Jde
o pragmatickou zkratku. Outbox je infrastrukturní vzor; kdo drží přísné vrstvení
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

        // Nejdřívější čas dalšího pokusu. Bez něj relay opakuje okamžitě.
        #[ORM\Column(type: 'datetime_immutable')]
        public \DateTimeImmutable $availableAt = new \DateTimeImmutable(),

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
        // Bez odkladu vezme další iterace relaye řádek okamžitě znovu
        // a všech pět pokusů se vyčerpá během jediné vteřiny.
        $this->availableAt = new \DateTimeImmutable(
            sprintf('+%d seconds', 2 ** $this->attempts),
        );
        $this->lastError = $error;
    }

    public static function fromIntegrationEvent(
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
| `message_type` | VARCHAR(255) | FQCN integrační události (např. `App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent`). Relay podle něj namapuje payload zpět na PHP třídu. |
| `aggregate_type` | VARCHAR(255) | Typ agregátu, který událost vydal (`Order`, `Invoice`). Debezium podle tohoto sloupce routuje do Kafka topiců, viz [15.05](#relay-cdc-heading). |
| `aggregate_id` | VARCHAR(64) | ID konkrétní instance agregátu. Slouží jako klíč zprávy: události jednoho agregátu skončí ve stejné partition, a tím ve správném pořadí. |
| `payload` | JSON / JSONB | Serializovaný stav události. JSONB v Postgresu je preferovaný – umožňuje indexovat jednotlivá pole pro debugging. |
| `status` | VARCHAR(16) | Stavový enum: `pending` (čeká na publish), `sent` (úspěšně publikováno), `failed` (po N pokusech vzdáno, vyžaduje manuální resolve). |
| `occurred_at` | TIMESTAMPTZ | Čas vzniku události v doménové transakci. Slouží pro řazení v relayi (best-effort FIFO) a pro výpočet outbox lagu. |
| `attempts` | INT | Počet neúspěšných pokusů o publish. Po dosažení prahu (typicky 5) řádek přechází do `failed` a opouští hot path. |
| `sent_at` | TIMESTAMPTZ NULL | Vyplněno při přechodu do `sent`. Používá se pro kompakci (mazání starších `sent` řádků). |
| `last_error` | TEXT NULL | Poslední chyba publishe – důležité pro rozbor incidentu. |
| `available_at` | TIMESTAMPTZ | Čas, odkdy relay smí řádek znovu vzít. `markFailed()` ho posouvá exponenciálně, takže trvale selhávající zpráva nepálí pokusy v každém cyklu. |

:::callout{type="warn"}
### Povinný index `(status, occurred_at)` {#index-status-time-heading}

V reálných implementacích se na něj často zapomíná. Bez kompozitního
indexu `(status, occurred_at)` dělá relay **full table scan**
při každém polling cyklu. Při outboxu s milionem historických `sent`
řádků a 100 `pending` se každých 100 ms prochází milion
řádků. CPU databáze vyskočí k 100 % a polling lag prudce roste.

Index je **kompozitní** přesně v tomto pořadí: nejdřív
`status` (vysoká selektivita: `pending` řádky jsou typicky
méně než 0,1 % tabulky), pak `occurred_at` (umožní `ORDER BY`
bez sortu). Plánovač dotazů Postgresu pak relay query odbavuje jako
*Index Scan using idx_outbox_status_time*, řádově v jednotkách milisekund.
:::

:::callout{type="pattern"}
### SQL: Doctrine migrace pro outbox tabulku {#migration-heading}

:::code{language="php" filename="migrations/Version20260429120000.php" highlights="52,53"}
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

    // DDL níž je pro MySQL/MariaDB. Přenositelně ho napsat nejde:
    // #[ORM\Column(type: 'uuid')] se mapuje na BINARY(16) v MySQL,
    // BLOB v SQLite a nativní UUID v PostgreSQLu. Ruční migrace se proto
    // musí psát pro cílovou platformu – nebo, spolehlivěji, nechat
    // vygenerovat přes `doctrine:migrations:diff` z namapované entity.
    // Výchozí hodnoty (status, attempts) drží entita, ne DEFAULT klauzule;
    // jinak se schéma a mapování rozejdou a schema:validate hlásí rozpor.
    //
    // Pozor na přesnost časových sloupců: typ datetime_immutable v DBAL 4
    // vytvoří DATETIME bez (6) a čas zapíše bez mikrosekund. Ručně psané
    // DATETIME(6) by nepomohlo, zlomky sekundy by do něj nikdy nedorazily.
    // Relay tedy řadí s přesností na sekundy. Subsekundové řazení potřebuje
    // vlastní DBAL typ s formátem 'Y-m-d H:i:s.u' a sloupcem DATETIME(6).
    // Komentář stojí zde, ne uvnitř CREATE TABLE – SQL komentáře v DDL
    // rozhodí introspekci schématu.
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox (
                id                BINARY(16)    NOT NULL,
                message_type      VARCHAR(255)  NOT NULL,
                aggregate_type    VARCHAR(255)  NOT NULL,
                aggregate_id      VARCHAR(64)   NOT NULL,
                payload           JSON          NOT NULL,
                status            VARCHAR(16)   NOT NULL,
                occurred_at       DATETIME      NOT NULL,
                attempts          INT           NOT NULL,
                available_at      DATETIME      NOT NULL,
                sent_at           DATETIME      DEFAULT NULL,
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
typem `UUID`, `DATETIME` typem `TIMESTAMPTZ` a `JSON` typem `JSONB`;
klauzule `ENGINE` a `CHARSET` odpadají. Na SQLite se `BINARY(16)` mapuje na `BLOB`
a `JSON` na `CLOB`. Takové ruční přepisování udělá `doctrine:migrations:diff`
spolehlivěji. Ukázka slouží k pochopení struktury, ne ke kopírování napříč
platformami. Pozor také na SQL komentáře uvnitř `CREATE TABLE`: introspekci
SQLite rozhodí a schéma se pak hlásí jako rozejité.

Po migraci spusťte `php bin/console doctrine:migrations:migrate` a ověřte,
že index existuje:
`SHOW INDEXES FROM outbox WHERE Key_name = 'idx_outbox_status_time'`
(MySQL) nebo
`SELECT * FROM pg_indexes WHERE indexname = 'idx_outbox_status_time'`
(PostgreSQL). Index se při refaktoringu schématu často ztratí, proto se vyplatí
regresní test v CI, který jeho existenci kontroluje.

## 15.04 Aggregate publikuje, handler ukládá do outboxu {#aggregate-publishes}

Agregát **nezná infrastrukturu**: o RabbitMQ ani outbox tabulce neví nic.
Vydává jen seznam doménových událostí, které z právě provedené operace
plynou. Application handler ten seznam vezme a zařadí do outbox tabulky *v téže
transakci*, ve které ukládá samotný agregát.

:::callout{type="pattern"}
### PHP: Agregát Order produkuje doménové události {#order-aggregate-heading}

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (výřez – továrna pro outbox)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\OrderStatus;
use App\Ordering\Domain\ValueObject\ProductId;
use App\SharedKernel\Domain\AggregateRoot;
use App\SharedKernel\Domain\Currency;
use App\SharedKernel\Domain\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

// Výřez, ne celá třída: nové jsou jen továrna placeWithItems() a getter
// items(). Vlastnosti $items, $status a konstruktor tu stojí pro kontext, ať je
// vidět, odkud se položky berou; do třídy z kapitoly o návrhu agregátu
// se nekopírují, jinak to skončí na „Cannot redeclare Order::__construct()“.
//
// placeWithItems() je druhá kanonická továrna knihy vedle Order::place().
// Kapitola o méně známých vzorech ukazuje na Factory vlastní variantu
// placePhysical(); ta do kanonického modelu nepatří.
class Order extends AggregateRoot
{
    /** @var Collection<int, OrderItem> */
    private Collection $items;

    public private(set) OrderStatus $status;

    // Stejný konstruktor jako ve zbytku knihy; položky přibývají metodou.
    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
    ) {
        $this->status = OrderStatus::Draft;
        $this->items = new ArrayCollection();
    }

    /**
     * Druhá továrna vedle kanonického Order::place(OrderId, CustomerId);
     * seznam položek potřebuje integrační událost.
     *
     * Přebírá primitivní řádky z commandu, ne hotové OrderItem: ty vzniknout
     * zvenčí nemohou, protože jejich konstruktor vyžaduje už existující Order.
     *
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public static function placeWithItems(CustomerId $customerId, array $items): self
    {
        // place() nahraje OrderPlaced jako první událost, stejně jako
        // placeWithFirstItem() v kapitole o návrhu agregátu.
        $order = self::place(OrderId::generate(), $customerId);

        foreach ($items as $item) {
            $order->addItem(
                ProductId::fromString($item['productId']),
                $item['quantity'],
                new Money($item['unitPriceInCents'], Currency::CZK),
            );
        }

        // Objednávka přišla kompletní – opouští Draft hned, jinak by na ni
        // sága nemohla zavolat markPaid() a uvázla by v prvním kroku.
        $order->confirm();

        // Od tohohle okamžiku nad objednávkou běží proces. Zámek uvolní
        // až sága, ať skončí úspěchem nebo kompenzací.
        $order->lockForSaga();

        return $order;
    }

    // addItem(), totalAmount() a items() viz kapitola
    // [Návrh agregátu](/navrh-agregatu); handler je potřebuje k sestavení
    // payloadu integrační události.
    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items->toArray();
    }
}
:::
:::

:::callout{type="pattern"}
### PHP: Integrační událost OrderPlacedIntegrationEvent {#domain-event-heading}

Nejde o tutéž třídu jako doménová `OrderPlaced` ze [Základních konceptů](/zakladni-koncepty#domain-events).
Ta nese hodnotové objekty a zůstává uvnitř kontextu. Do outboxu jde **integrační** událost:
samé primitivy, aby přežila serializaci, plus `eventId` pro deduplikaci na straně příjemce.
Jedna třída pro obojí nestačí. Doménový tvar se bez převodních typů serializovat
nedá a integrační tvar by do domény zatáhl `array` místo `OrderItem`.

:::code{language="php" filename="src/Ordering/Application/IntegrationEvent/OrderPlacedIntegrationEvent.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\IntegrationEvent;

use Symfony\Component\Uid\Uuid;

/**
 * Integrační událost – neměnná, serializovatelná, nese pouze
 * data nutná pro subscribery. Včetně vlastního event_id pro
 * deduplikaci v Inboxu.
 */
final readonly class OrderPlacedIntegrationEvent
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $customerId,
        /** @var list<array{productId: string, quantity: int, unitPriceInCents: int}> */
        public array $items,
        public int $totalAmountCents,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
:::
:::

:::callout{type="pattern"}
### PHP: PlaceOrderHandler – atomický zápis order + outbox {#place-order-handler-heading}

Command je prosté DTO s primitivy. Přichází z HTTP vrstvy, kde hodnotové objekty
ještě neexistují:

:::code{language="php" filename="src/Ordering/Application/Command/PlaceOrder.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlaceOrder
{
    /**
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public function __construct(
        #[Assert\Uuid]
        public string $customerId,

        // Bez těchhle pravidel dojde na kontrolu až v hodnotovém objektu
        // uvnitř agregátu. Samotné atributy ale HTTP status neurčují:
        // ValidationFailedException z Messengeru žádný nenese, takže ji
        // musí odchytit kontroler (viz 12.12) a přeložit na 422. Jinak
        // vybublá jako 500, jen z jiného místa.
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Collection([
                // NotBlank tu není navíc: Assert\Uuid prázdný řetězec
                // propustí bez porušení, takže by chybějící productId
                // spadlo až v hodnotovém objektu jako 500.
                'productId' => [new Assert\NotBlank(), new Assert\Uuid()],
                'quantity' => [new Assert\Positive()],
                'unitPriceInCents' => [new Assert\PositiveOrZero()],
            ]),
        ])]
        public array $items,
    ) {}
}
:::


:::code{language="php" filename="src/Ordering/Application/Handler/PlaceOrderHandler.php" highlights="29,30,36,37,38,39,40,41,42,43,44,45,56,79,80,81"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\PlaceOrder;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Outbox\Application\DomainEventSerializer;
use App\Outbox\Application\OutboxRepository;
use App\Outbox\Domain\OutboxMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Ordering\Domain\Model\OrderItem;
use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Event\OrderItemAdded;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Ordering\Domain\ValueObject\CustomerId;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
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
            $order = Order::placeWithItems(
                CustomerId::fromString($command->customerId),
                $command->items,
            );

            $this->orders->save($order);

            // Doménová událost se do outboxu nedává přímo: nese hodnotové
            // objekty, které by se serializovaly jako {"value":"01a0…"}.
            // Na hranici kontextu se překládá na integrační tvar.
            foreach ($order->releaseEvents() as $event) {
                // placeWithItems() nahraje víc událostí: place() OrderPlaced,
                // addItem() OrderItemAdded a confirm() OrderConfirmed. Integrační
                // tvar má jen OrderPlaced – nese celou objednávku včetně
                // položek, takže odběratelům v jiných kontextech stačí sama.
                // Dílčí události zůstávají uvnitř kontextu Ordering; neznámá
                // událost je chyba v překladu, ne něco k tichému přeskočení.
                $integrationEvent = match (true) {
                    $event instanceof OrderItemAdded,
                    $event instanceof OrderConfirmed => null,
                    $event instanceof OrderPlaced => new OrderPlacedIntegrationEvent(
                        eventId: Uuid::v7(),
                        orderId: $event->orderId->value,
                        customerId: $event->customerId->value,
                        items: array_map(
                            static fn (OrderItem $i): array => [
                                'productId' => $i->productId->value,
                                'quantity' => $i->quantity,
                                'unitPriceInCents' => $i->unitPrice->amountInCents,
                            ],
                            $order->items(),
                        ),
                        totalAmountCents: $order->totalAmount()->amountInCents,
                        occurredAt: $event->occurredAt,
                    ),
                    default => throw new \LogicException(
                        'Chybí překlad pro ' . $event::class,
                    ),
                };

                if ($integrationEvent === null) {
                    continue;
                }

                $this->outbox->store(
                    OutboxMessage::fromIntegrationEvent(
                        $integrationEvent,
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

`$this->em->wrapInTransaction(...)` otevře transakci, vykoná callback, na konci
flushne a commitne. Když uvnitř callbacku vyletí výjimka, transakci rollbackne. Stejně
funguje Messenger middleware `doctrine_transaction`, který zabalí
celý handler do jedné transakce. Kanonický `messenger.yaml` z [kapitoly o CQRS](/cqrs#messenger-config-heading)
ho na `command.bus` má, takže tam `wrapInTransaction` v handleru přebývá. Zůstane
z něj vnořený savepoint a nejasno, kde se vlastně commituje.
Ukázka ho drží, aby byl vzor čitelný i bez znalosti konfigurace
sběrnic; ve vlastním projektu si vyberte jedno místo.

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

Doctrine adapter je krátký, ale dvě místa v něm přehlédne skoro každý. `store()`
nesmí flushovat, protože transakci drží aplikační wrapper. `fetchPending()`
musí filtrovat i podle `availableAt`, jinak backoff z `markFailed()` nic neznamená.

:::code{language="php" filename="src/Outbox/Infrastructure/DoctrineOutboxRepository.php"}
<?php

declare(strict_types=1);

namespace App\Outbox\Infrastructure;

use App\Outbox\Application\OutboxRepository;
use App\Outbox\Domain\OutboxMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineOutboxRepository implements OutboxRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function store(OutboxMessage $message): void
    {
        // Žádný flush. Zpráva musí odejít do DB ve stejné transakci
        // jako změna agregátu – o commit se stará volající.
        $this->em->persist($message);
    }

    public function fetchPending(int $limit = 100): array
    {
        return $this->em->createQuery(
            "SELECT m FROM " . OutboxMessage::class . " m
              WHERE m.status = 'pending' AND m.availableAt <= :now
           ORDER BY m.occurredAt ASC"
        )
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults($limit)
            ->getResult();
    }

    public function markSent(Uuid $id): void
    {
        $this->em->find(OutboxMessage::class, $id)?->markSent(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function markFailed(Uuid $id, string $error): void
    {
        // Zde flush naopak patří: relay běží mimo doménovou transakci
        // a výsledek pokusu musí být vidět, i když další zpráva spadne.
        $this->em->find(OutboxMessage::class, $id)?->markFailed($error);
        $this->em->flush();
    }
}
:::

## 15.05 Relay process – dvě varianty {#relay}

Outbox tabulka sama nic nepublikuje. Potřebuje relay proces, který v určité
kadenci vybírá pending řádky a posílá je do brokera. Katalog microservices.io pro to
zná dva pojmenované vzory. **Polling Publisher** čte outbox tabulku dotazem
a jeho jediná, zato podstatná přednost zní: funguje nad libovolnou SQL databází.
**Transaction Log Tailing** místo dotazu čte transakční log databáze, tedy Postgres WAL
nebo MySQL binlog. První vzor se realizuje jako Symfony Console command, druhý jako
Debezium konektor nad Kafkou.

### Varianta A: Polling Publisher (Symfony Console command) {#relay-polling-heading}

Polling worker je obyčejný Symfony Console command, který ve vnitřní smyčce volá
`fetchPending()`, publikuje řádky a označí je jako `sent`.
Spouští se ze `supervisord`, `systemd` nebo Kubernetes
Deploymentu jako trvale běžící proces. Smyčka má časový limit; po jeho
vypršení se proces čistě ukončí a process manager ho nastartuje znovu.
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
use Symfony\Component\DependencyInjection\Attribute\Target;
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
        // #[Target] vybírá konkrétní sběrnici. Bez něj přijde default_bus,
        // tedy command.bus – doménová událost tam nemá handler, Messenger ji
        // po vyčerpání retry zahodí a outbox tím ztratí smysl.
        #[Target('event.bus')]
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

                    // eventId pro deduplikaci v Inboxu cestuje v payloadu
                    // (OrderPlacedIntegrationEvent::$eventId) – stamp není potřeba.
                    $this->bus->dispatch(
                        $message,
                        // Jméno transportu musí existovat v messenger.yaml;
                        // neplatné skončí hláškou „sender is not in the senders
                        // locator“ a řádek se označí failed až po vyčerpání pokusů.
                        [new TransportNamesStamp(['async_events'])],
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

Zpětný převod obstará `OutboxMessageFactory`. Nejde o čistě mechanický opak
serializeru: denormalizace potřebuje znát cílovou třídu, a proto se opírá o whitelist.
Ten slouží i jako bezpečnostní opatření. Bez něj by o tom, jakou třídu aplikace vytvoří,
rozhodoval `message_type` z databáze:

:::code{language="php" filename="src/Outbox/Application/OutboxMessageFactory.php"}
<?php

declare(strict_types=1);

namespace App\Outbox\Application;

use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Outbox\Domain\OutboxMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class OutboxMessageFactory
{
    /**
     * Whitelist typů, které smí relay vytvořit. Nový integrační event
     * znamená nový řádek zde – jinak skončí v dead-letter, ne v aplikaci.
     *
     * @var array<string, class-string>
     */
    private const ALLOWED = [
        OrderPlacedIntegrationEvent::class => OrderPlacedIntegrationEvent::class,
    ];

    public function __construct(
        private DenormalizerInterface $denormalizer,
    ) {}

    public function reconstitute(OutboxMessage $message): object
    {
        $class = self::ALLOWED[$message->messageType] ?? null;

        if ($class === null) {
            throw new \RuntimeException(
                sprintf('Neznámý message_type "%s" v outboxu.', $message->messageType),
            );
        }

        return $this->denormalizer->denormalize($message->payload, $class, 'json');
    }
}
:::

Integrační událost se denormalizuje bez potíží právě proto, že nese samé primitivy.
Doménová událost s hodnotovými objekty by tu skončila hláškou
*„Cannot create an instance of `OrderId` from serialized data because its constructor
requires the following parameters to be present: `$value`“*. I proto se přes hranici
posílá integrační tvar.

:::callout{type="warn"}
### Po Doctrine výjimce je EntityManager zavřený {#closed-em-heading}

Výpis výše má jednu past, kterou odhalí až produkce. `catch (\Throwable $e)`
volá `markFailed()` nad týmž EntityManagerem. Pokud výjimku vyhodila Doctrine,
je EM po rollbacku zavřený a `markFailed()` skončí na
`EntityManagerClosedException`. Worker spadne v prvním cyklu, ve kterém
selže databáze, přestože kód vypadá, že chyby ošetřuje.

Dokumentace ORM je v tom jednoznačná: další unit of work po výjimce patří novému
EntityManageru. V `catch` bloku je proto potřeba nejdřív zavolat
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
startsecs=2                 ; proces běží hodinu, start je tedy vždy „úspěšný“
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
způsobí **double publish**. Každý event se odešle dvakrát ve stejnou chvíli,
zátěž brokera roste lineárně s počtem replik a Inbox musí odfiltrovat víc duplicit.

Jakmile jeden worker přestane stačit, sáhněte po
`SELECT ... FOR UPDATE SKIP LOCKED` v Postgresu nebo MySQL 8. Každý
worker si pak zarezervuje vlastní batch řádků. Jeden PHP proces zvládne řádově stovky až nízké tisíce zpráv za sekundu.
Na každou dělá deserializaci, publish s čekáním na ACK a UPDATE řádku,
takže výsledek určuje latence brokera a databáze, ne PHP.
Univerzální hodnota neexistuje; konkrétní číslo je potřeba změřit na vlastní
konfiguraci.
:::

### Varianta B: CDC / Debezium {#relay-cdc-heading}

**Change Data Capture** (CDC) je kanonicky *Transaction Log Tailing*: místo
aplikačního polleru čte Postgres WAL (*Write-Ahead Log*) nebo MySQL binlog
a streamuje každý `INSERT` do outbox tabulky přímo do Kafky. Standardním nástrojem
je [Debezium](https://debezium.io), plugin pro Kafka Connect, který
funguje jako logický replikační odběratel databáze.

Aplikace zapíše řádek do `outbox`, Debezium ten `INSERT` uvidí
v transakčním logu, vytvoří Kafka record a pošle ho do odpovídajícího topicu.
Řádek se pak už nemění, tabulka funguje jako append-only log.

| Aspekt | Polling Publisher (A) | Transaction Log Tailing / Debezium (B) |
|---|---|---|
| Latence | 50–500 ms (polling interval) | jednotky až desítky ms (push z WAL) |
| Operační složitost | 1× console command + supervisor | Kafka + Kafka Connect + Debezium konektor + monitoring 4 procesů |
| Volba brokera | Libovolný (RabbitMQ, SQS, Redis, Doctrine async) | Pouze Kafka (resp. Pulsar, Kinesis přes adaptér) |
| Scale-out | stovky až nízké tisíce zpráv/s na worker, lineárně s replikami přes SKIP LOCKED | dáno Kafkou, o dva řády výš |
| Garance pořadí | Best-effort podle `occurred_at` | Per-partition podle `aggregate_id` |
| Provozní riziko | Zaseknutý worker = rostoucí lag | Zaseknutý konektor drží replikační slot a WAL se hromadí na disku primární databáze |
| Doporučeno pro | Běžný Symfony projekt | Multi-tenant SaaS, finanční systémy, IoT |

Kniha dál pracuje s variantou A. Typickému Symfony projektu dá dostatečnou
spolehlivost bez Kafka stacku, který by jinak přibyl jen kvůli outboxu.
Debezium se vyplatí teprve tehdy, když už v produkci běží zhruba pět Kafka
konzumentů a outbox lag začíná být úzkým hrdlem.

Konfiguračně jde o Kafka Connect konektor (REST API, nebo deklarativně přes
Strimzi operator). Jádrem je transformace **Outbox Event Router**
(`io.debezium.transforms.outbox.EventRouter`). Ta má vlastní představu
o schématu tabulky, kterou je dobré znát dřív, než se konektor nasadí:

- Routuje podle sloupce `aggregatetype`, ne podle typu události. Výchozí topic
  je `outbox.event.<hodnota aggregatetype>`, pro hodnotu `Order` tedy
  `outbox.event.Order`, nikoli topic pojmenovaný po třídě `OrderPlaced`.
- Klíčem Kafka zprávy je `aggregateid`. Právě odtud plyne pořadí uvnitř partition.
- Sloupec `id` cestuje jako hlavička zprávy a dokumentace ho nabízí přímo
  k deduplikaci na straně konzumenta.
- SMT automaticky odfiltruje `DELETE` operace nad outbox tabulkou. Kanonický
  Debezium model proto řádek vloží a hned smaže; sloupec `status` v něm vůbec
  nefiguruje.

Tvrzení „na aplikační straně se nic nemění“ tedy neplatí. Schéma z [15.03](#schema)
má sloupce `aggregate_type` a `aggregate_id` s podtržítkem a navíc stavový model.
Přechod na variantu B proto znamená buď přejmenovat sloupce podle výchozího
očekávání SMT, nebo přemapovat volby `route.by.field` a `table.field.event.*`.
Rozhodnutí čeká i stavový sloupec: buď `status` zůstane kvůli auditní stopě
a konektor ho bude ignorovat, nebo tabulka přejde na insert-and-delete
model, který nepotřebuje kompakci.

Pro Postgres k tomu přibude logická replikace. Výchozí `plugin.name` konektoru
je `decoderbufs`, který vyžaduje serverové rozšíření; `pgoutput` je v Postgresu
od verze 10 nativní, a proto v praxi častější. Logická replikace vyžaduje `wal_level = logical`
a pro uživatele konektoru privilegium `CREATE` kvůli vytvoření publikace.

*Citace: Debezium dokumentace –
[Outbox Event Router](https://debezium.io/documentation/reference/stable/transformations/outbox-event-router.html)
(Red Hat, 2019+).*

### Doctrine transport jako outbox bez vlastní tabulky {#doctrine-transport-outbox-heading}

Symfony Messenger nabízí třetí cestu bez vlastní outbox tabulky
i bez relay commandu. Transport `doctrine://default` ukládá zprávy do tabulky
`messenger_messages` ve **stejné databázi**, kde žije doménový stav. Atomicitu
zajišťuje middleware `doctrine_transaction` na **command busu**: transakce,
kterou middleware otevře kolem command handleru, obalí uložení agregátu
i dispatch eventu na doctrine transport. Podmínkou je, že transport používá
totéž DB spojení jako doménový stav, tedy `default` entity manager. Dual-write
problém tím mizí: buď se commitne order i zpráva, nebo nic. Worker
`messenger:consume async_events` pak zprávu vyzvedne a zpracuje. Do externího brokera
ji sám nepřepošle; na to je potřeba vlastní handler nebo relay z předchozích sekcí.

:::callout{type="pattern"}
### YAML: Routing eventu na Doctrine transport {#doctrine-transport-routing-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez – plná konfigurace v kapitole o CQRS)" highlights="6,10,13"}
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction   # transakce handleru obalí agregát i dispatch eventu

        transports:
            async_events:
                dsn: 'doctrine://default'    # totéž spojení jako doménový stav (default EM)

        routing:
            # Handler dispatchne integrační tvar, ne doménovou událost.
            App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent: async_events
:::
:::

V této variantě `PlaceOrderHandler` integrační událost do outboxu neukládá. Rovnou ji
dispatchne a routing ji pošle na doctrine transport:

:::code{language="php" filename="src/Ordering/Application/Handler/PlaceOrderHandler.php (výřez – varianta s doctrine transportem)"}
// Místo $this->outbox->store(OutboxMessage::fromIntegrationEvent(...)):
$this->eventBus->dispatch($integrationEvent);
:::

Symfony dokumentace tuhle konfiguraci nikde nenazývá outboxem; slovo v ní nepadne.
Atomicita ale reálně platí. `DoctrineTransactionMiddleware` otevře transakci nad
spojením entity manageru, spustí handler, pak flushne a commitne. `Connection::send()`
doctrine transportu je prostý `INSERT` nad týmž spojením a vlastní izolovanou
transakci neotevírá. Zápis zprávy i flush agregátu proto commitnou společně.

:::callout{type="warn"}
### Dvě konfigurace, které atomicitu tiše ruší {#doctrine-transport-traps-heading}

**`DispatchAfterCurrentBusStamp`.** Podle docblocku `DispatchAfterCurrentBusMiddleware`
platí: je-li middleware registrovaný před `doctrine_transaction`, odbaví se
sub-dispatchnuté zprávy s tímto stampem až po commitu Doctrine transakce.
Dokumentace přitom `dispatch_after_current_bus` doporučuje registrovat právě
před `doctrine_transaction`. Kdo tuhle radu zkombinuje s doctrine transportem
v roli outboxu, vrátí si dual-write: zpráva odchází mimo transakci, která
uložila agregát.

**`auto_setup`.** Transport si tabulku `messenger_messages` vytváří sám jen tehdy,
když na spojení neběží transakce. Uvnitř transakce `TableNotFoundException`
propadne dál a handler spadne. Tabulku proto vytvořte migrací a `auto_setup`
vypněte. Dokumentace to nezávisle doporučuje i pro běžný produkční provoz.
:::

Daň za pohodlí je trojí. Formát uložené zprávy je svázaný s Messengerem: payload
serializuje envelope i se stampy, takže ho mimo Symfony nikdo rozumně nepřečte.
Auditovatelnost a retence jsou horší než u vlastní outbox tabulky: zpracované
řádky worker maže, žádný stav `sent`, žádné `last_error`, žádná historie pro
rozbor incidentu. A schéma tabulky určuje Messenger; migrace ho jen
přebírá.

Pro menší systémy je to přesto nejjednodušší správná volba: dual-write je
vyřešený, kód se omezí na konfiguraci a jeden worker. Vlastní outbox tabulka
se vyplatí, až když potřebujete auditní stopu, řízenou retenci nebo publish
do brokera mimo Messenger.

### Pořadí zpráv: best-effort, ne garance {#relay-ordering-heading}

`ORDER BY occurred_at` sugeruje víc, než dokáže splnit. Katalog microservices.io
u Polling Publisheru uvádí drawback „tricky to publish events in order“ a v této
implementaci se sejdou hned tři důvody. Hodnota `occurred_at` vzniká v PHP procesu,
takže napříč instancemi podléhá odchylce hodin. Při shodné hodnotě není pořadí
definované vůbec. Se sekundovou přesností sloupce (viz komentář
v [migraci](#migration-heading)) přitom shody nejsou výjimkou. A relay publikuje
řádek po řádku, takže selhání uprostřed batche pustí pozdější událost před dřívější.

Spolehlivé pořadí lze držet jen per agregát a jen tehdy, když ho nese klíč zprávy.
Proto je ve schématu `aggregate_id`. V Kafce z něj plyne partition, uvnitř které
je pořadí garantované. Globální pořadí napříč agregáty neexistuje a doménová
logika na něm stavět nesmí.

Filtru `status = 'pending'` se naopak netýká *gap problém*, který popisuje kapitola
[Event Sourcing](/event-sourcing#auto-increment-gap-heading). Ten trápí relay,
který si drží checkpoint na auto-increment ID: transakce s nižším ID může commitnout
později a relay ji za posunutým checkpointem už nepřečte. Outbox tabulka se stavovým
sloupcem checkpoint nemá. Řádek je viditelný teprve po commitu a zůstane `pending`,
dokud ho relay nepublikuje. Opožděný commit se objeví v některém dalším cyklu.

## 15.06 Idempotent Inbox – strana subscribera {#inbox}

Outbox dává at-least-once delivery, takže subscriber **musí** počítat s tím,
že stejný event dostane víckrát. Když je vedlejší efekt handleru neidempotentní (typicky
`UPDATE counter SET value = value + 1`), duplicita se okamžitě projeví jako
chybný stav read modelu. Zákazník vidí 200 Kč na účtu místo 100 Kč, počet
objednávek je dvojnásobný, e-mail dorazí dvakrát.

Řešení má v katalozích dvě jména. microservices.io vede vzor jako **Idempotent
Consumer** a doporučuje tabulku zpracovaných zpráv s kompozitním klíčem
`(subscriberId, messageID)`. Starší je **Idempotent Receiver** z *Enterprise
Integration Patterns* (Hohpe & Woolf, 2003): příjemce navržený tak, aby tutéž
zprávu snesl vícekrát. Dál v kapitole používáme pracovní jméno **Idempotent Inbox**,
protože stojí symetricky proti outboxu.

Realizace je zrcadlem outboxu: tabulka `inbox` v databázi subscribera
s kompozitním UNIQUE constraintem na dvojici `(event_id, consumer)`. Před
zpracováním eventu handler zkontroluje, zda je daná dvojice už v inboxu. Pokud ano,
ackne brokerovi a skončí. Pokud ne, provede svou logiku a v *téže transakci*
vloží nový řádek do inboxu. UNIQUE constraint slouží jako pojistka proti
race condition.

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

Entita `InboxMessage` slouží k mapování schématu, zápis ale jde záměrně přes DBAL,
ne přes ORM. Jediným úkolem inboxu je zapsat dvojici `(eventId, consumer)`
pod unikátním indexem a na to Unit of Work není potřeba:

:::code{language="php" filename="src/Inbox/Infrastructure/DbalInboxRepository.php"}
<?php

declare(strict_types=1);

namespace App\Inbox\Infrastructure;

use App\Inbox\Application\InboxRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final readonly class DbalInboxRepository implements InboxRepository
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function isProcessed(Uuid $eventId, string $consumer): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM inbox WHERE event_id = :id AND consumer = :consumer',
            ['id' => (string) $eventId, 'consumer' => $consumer],
        );
    }

    public function markProcessed(Uuid $eventId, string $consumer): void
    {
        // UniqueConstraintViolationException se zde záměrně nechytá.
        // Při souběhu musí shodit celou transakci subscribera i s vedlejším
        // efektem; Messenger zprávu zopakuje a podruhé ji zastaví
        // isProcessed(). Spolknutá výjimka by na MySQL nechala commitnout
        // i duplicitní efekt a UNIQUE by přestal být pojistkou.
        $this->connection->insert('inbox', [
            // InboxMessage má vlastní PK bez #[ORM\GeneratedValue],
            // takže ho musí dodat zapisující strana.
            'id'           => (string) Uuid::v7(),
            'event_id'     => (string) $eventId,
            'consumer'     => $consumer,
            'processed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
:::
:::

:::callout{type="pattern"}
### PHP: OrderPlacedReadModelUpdater s inbox checkem {#read-model-updater-heading}

Port do read modelu je úzký. Subscriber jím jen zapisuje, nikdy nečte:

:::code{language="php" filename="src/Reporting/Application/ReadModelStore.php"}
<?php

declare(strict_types=1);

namespace App\Reporting\Application;

interface ReadModelStore
{
    /**
     * Upsert – zpracování téže události podruhé nesmí nic pokazit.
     *
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public function upsertOrderRow(
        string $orderId,
        string $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): void;
}
:::

Implementace portu je jediný DBAL příkaz. Tenhle read model patří `Reportingu`
a liší se od `order_dashboard` z kapitoly o CQRS: dashboard sleduje
stav objednávky, reporting drží její položky. Oba odebírají `OrderPlacedIntegrationEvent`
a to je v pořádku. Jedna událost běžně živí několik projekcí, každou v jejím kontextu.

:::code{language="php" filename="src/Reporting/Infrastructure/DbalReadModelStore.php"}
<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Application\ReadModelStore;
use Doctrine\DBAL\Connection;

final readonly class DbalReadModelStore implements ReadModelStore
{
    public function __construct(private Connection $connection) {}

    public function upsertOrderRow(
        string $orderId,
        string $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): void {
        $this->connection->executeStatement(
            'INSERT INTO reporting_orders (order_id, customer_id, items, placed_at)
             VALUES (:orderId, :customerId, :items, :placedAt)
             ON CONFLICT (order_id) DO UPDATE SET
                items = excluded.items, customer_id = excluded.customer_id',
            [
                'orderId'    => $orderId,
                'customerId' => $customerId,
                'items'      => json_encode($items, JSON_THROW_ON_ERROR),
                'placedAt'   => $placedAt->format('Y-m-d H:i:s'),
            ],
        );
    }
}
:::

Podmínku na `updated_at` tenhle upsert nepotřebuje: řádek plní jediná událost, takže
se nemá s čím předběhnout. Tabulka vzniká migrací a do `schema_filter` patří ze stejného
důvodu jako `order_dashboard`:

:::code{language="sql" filename="migrations/Version20260906120000.php (výřez)"}
-- PostgreSQL. Primární klíč je zároveň cíl ON CONFLICT (order_id) v upsertu výše.
CREATE TABLE reporting_orders (
    order_id    UUID         NOT NULL,
    customer_id UUID         NOT NULL,
    items       JSONB        NOT NULL,
    placed_at   TIMESTAMP(0) NOT NULL,
    PRIMARY KEY (order_id)
);

-- Reporting se ptá po objednávkách zákazníka, ne po jedné objednávce.
CREATE INDEX idx_reporting_customer ON reporting_orders (customer_id, placed_at);
:::

:::code{language="php" filename="src/Reporting/Application/Subscriber/OrderPlacedReadModelUpdater.php" highlights="28,29,30,31,48"}
<?php

declare(strict_types=1);

namespace App\Reporting\Application\Subscriber;

use App\Inbox\Application\InboxRepository;
use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Reporting\Application\ReadModelStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// Projekce jde před ságou ze stejného důvodu jako dashboard v kapitole
// o CQRS – na synchronní sběrnici rozhoduje pořadí.
#[AsMessageHandler(bus: 'event.bus', priority: 10)]
final readonly class OrderPlacedReadModelUpdater
{
    private const string CONSUMER = 'reporting.order_placed';

    public function __construct(
        private InboxRepository $inbox,
        private ReadModelStore $readModel,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(OrderPlacedIntegrationEvent $event): void
    {
        $this->em->wrapInTransaction(function () use ($event): void {
            // 1) Kontrola idempotence – duplikát se ackne bez vedlejšího efektu.
            if ($this->inbox->isProcessed($event->eventId, self::CONSUMER)) {
                return;
            }

            // 2) Vlastní logika subscribera – typicky upsert read modelu.
            $this->readModel->upsertOrderRow(
                orderId: $event->orderId,
                customerId: $event->customerId,
                items: $event->items,
                placedAt: $event->occurredAt,
            );

            // 3) Záznam do inboxu v téže transakci.
            // UNIQUE constraint je pojistka proti race condition:
            // když dva workery dostanou stejný event paralelně,
            // druhý dostane UniqueConstraintViolationException, transakce
            // se vrátí a Messenger zprávu zopakuje – podruhé už zastaví
            // isProcessed() v kroku 1.
            $this->inbox->markProcessed($event->eventId, self::CONSUMER);
        });
    }
}
:::
:::

Sloupec `consumer` má v inbox tabulce svůj důvod. Tentýž `event_id`
zpracovává víc subscriberů (Reporting, Notifications, Search index) a každý
si potřebuje vést *vlastní* stav „už zpracováno“. Bez sloupce
`consumer` by druhý subscriber narazil na UNIQUE constraint prvního a event by
nikdy nezpracoval. UNIQUE je proto kompozitní `(event_id, consumer)`,
ne jen `event_id`.

:::callout{type="note"}
### Exactly-once efekt vs. exactly-once delivery {#exactly-once-effect-heading}

Marketingové materiály brokerů občas slibují „exactly-once delivery“. Taková
garance **v distribuovaném systému neexistuje**. Doručení přes nespolehlivý
kanál potvrzuje příjemce zprávou, která se sama může ztratit, takže odesílatel
nikdy neví, zda posílat znovu. Outbox s Inboxem poskytují
*exactly-once efekt na straně subscribera*. Zpráva může do
brokera dorazit a opustit ho víckrát, ale vedlejší efekt v téže databázi (úprava read
modelu) proběhne *právě jednou*. Záznam v inboxu a efekt se totiž commitnou jednou
transakcí. U externích efektů (e-mail, platba) Inbox duplicitu jen zmenší. Když proces
spadne po odeslání a před commitem, efekt se zopakuje. Právě jednou ho zajistí až
idempotence příjemce, typicky `Idempotency-Key` u platební brány
(viz [Idempotence na hranici HTTP API](#idempotency-api)).

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

Inbox roste stejně jako outbox, jen se na to snáz zapomene. Každá zpracovaná zpráva
v něm nechá řádek a nic ho nemaže. Po roce provozu se z pojistky proti duplicitám
může stát největší tabulka v databázi subscribera.

Horní hranici retence určuje doba, po kterou může broker zprávu ještě doručit:
maximální TTL zprávy plus nejdelší retry okno relay procesu. Řádek starší než
tento součet už nemá co deduplikovat. Obvykle se drží 30 dní – bezpečně nad běžným nastavením obou lhůt.
Maže se stejným batch cronem jako outbox.
Kdo retenci zvolí kratší než reálné retry okno, otevře si díru: opožděná zpráva
projde jako nová.

## 15.07 Provozní aspekty {#provoz}

Ve vývojovém prostředí outbox funguje bez údržby. Produkce přinese čtyři
provozní otázky: jak měřit lag, jak držet tabulku malou, co s trvale selhávajícími
řádky a jak monitorovat, že se na něco nezapomnělo.

### Outbox lag {#outbox-lag-heading}

**Outbox lag** je doba, kterou event stráví ve stavu `pending`, než ho relay
pošle do brokera. Jako alarmová metrika slouží stáří nejstaršího pending řádku.

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

Tyto metriky exportujte do Promethea (`outbox_pending_seconds`,
`outbox_pending_count`) a v Grafaně nad nimi postavte alert. **Kritický
práh bývá 30 sekund.** Když ho lag překročí, něco se zaseklo:
relay worker padl, broker je nedostupný, DB má 100% CPU. Při normálním provozu
je medián lagu pod 1 sekundou.

### Kompakce outbox tabulky {#kompakce-heading}

Outbox tabulka roste lineárně s počtem publikovaných událostí. Bez kompakce po roce
provozu obsahuje miliony historických řádků. Ty zpomalují i indexované dotazy
a zbytečně zabírají disk. Standardní strategie **maže řádky ve stavu `sent`
starší než N dní**, kde N je obvykle 7 až 30 podle compliance požadavků.

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
        // Údržbový příkaz běží z cronu potichu, takže chyba se neohlásí
        // hláškou, ale až nabobtnalou tabulkou. Proto tvar, který projde
        // na MySQL, PostgreSQL i SQLite. Odvozená tabulka t obchází dvě
        // omezení MySQL: LIMIT přímo v IN (SELECT …) odmítá a mazanou
        // tabulku nedovolí číst v poddotazu.
        $threshold = new \DateTimeImmutable('-30 days');

        $deleted = $this->connection->executeStatement(
            'DELETE FROM outbox WHERE id IN (
                 SELECT id FROM (
                     SELECT id FROM outbox
                      WHERE status = \'sent\' AND sent_at < :threshold
                      LIMIT 10000
                 ) t
             )',
            ['threshold' => $threshold->format('Y-m-d H:i:s')],
        );

        $output->writeln(sprintf('[outbox-cleanup] deleted %d rows', $deleted));

        return Command::SUCCESS;
    }
}
:::
:::

`LIMIT 10000` je tam záměrně: mazání jde po dávkách, ne jediným `DELETE FROM
outbox`. Velký delete běží jako jedna dlouhá transakce, drží zámky na obrovském
počtu řádků a může blokovat produkční INSERTy z handlerů. Cron ho spouští každých
5 minut a 10 000 řádků za běh stačí na realistické workloady (cca 3 mil. eventů/den).

Nabízející se zápis `DELETE … WHERE sent_at < NOW() - INTERVAL 30 DAY LIMIT 10000`
je kratší, ale funguje jen v MySQL a MariaDB. Postgres i SQLite ho odmítnou,
a protože cron běží potichu, chyba se projeví až rostoucí tabulkou. Hranice
se proto počítá v PHP a batch se vymezuje poddotazem nad `id`.

### Dead-letter queue pro permanentní selhání {#dlq-heading}

Některé eventy se nepublikují nikdy. Třída integrační události se změnila a starý
payload už nejde denormalizovat, `message_type` chybí ve whitelistu
`OutboxMessageFactory` nebo je payload poškozený. Po N pokusech (typicky 5)
je `OutboxMessage::markFailed()` přepne do stavu `failed`. S takovými řádky se
zachází takto:

- **Vyčlenit z hot pathy** – relay je už nezkouší publikovat.
- **Hlasitě upozornit** – alert `outbox_failed_total > 0`.
- **Mít na ně CLI nástroj** – `app:outbox:retry-failed` nebo
  ruční SQL update statusu zpět na `pending` po opravě příčiny.
- **Nikdy nemazat automaticky.** Failed řádek dokládá nedoručený doménový
  event a má zůstat evidovaný i po týdnech.

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
během sekund se UPDATE na `sent` → po N dnech DELETE), historie se nikdy nečte.
Výchozí nastavení autovacuum v PostgreSQL na takový profil **není dimenzované**
a po několika dnech provozu se objeví index bloat:

- UPDATE statusu vytvoří novou verzi řádku a stará zůstane jako mrtvá (MVCC).
  Mění se indexovaný sloupec `status`, takže HOT update nepřipadá v úvahu
  a nová verze dostane i nové položky v indexech.
- DELETE při kompakci přidá další mrtvé řádky.
- Výchozí práh autovacuum (`autovacuum_vacuum_scale_factor = 0.2`)
  čeká, než se nasbírá 20 % mrtvých řádků. Při tisících zápisů za sekundu
  to trvá minuty.
- Mezitím index `(status, occurred_at)` nabobtná na násobky původní velikosti,
  selecty se zpomalují a lag stoupá.

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

-- Pravidelně sledujte index bloat. pg_stat_user_indexes má sloupce
-- relname a indexrelname (tablename/indexname patří pohledu pg_indexes).
SELECT
    schemaname,
    relname      AS tablename,
    indexrelname AS indexname,
    pg_size_pretty(pg_relation_size(indexrelid)) AS index_size,
    idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname = 'public' AND relname = 'outbox';

-- REINDEX CONCURRENTLY když index naroste přes 2× očekávané velikosti:
REINDEX INDEX CONCURRENTLY idx_outbox_status_time;
:::
:::

### Partitioning při vysokém objemu (PostgreSQL) {#partitioning-heading}

Při trvale vysokém objemu, tedy v řádu tisíců událostí za sekundu, se single-table
outbox stává provozním úzkým hrdlem. PostgreSQL declarative partitioning podle
`occurred_at` přináší:

- **Rychlé mazání starých dat** přes `DETACH PARTITION` a `DROP TABLE` místo
  `DELETE`. Odpadá dlouhá mazací transakce a doba nezávisí na počtu řádků
  (O(1) místo O(n)). Běžný `DETACH` potřebuje exkluzivní zámek rodičovské
  tabulky; `DETACH PARTITION … CONCURRENTLY` (od PostgreSQL 14) vystačí se slabším.
- **Cílené vacuum** – autovacuum pracuje per-partition, takže staré partice,
  do kterých se už nezapisuje, ho téměř nezaměstnávají.
- **Lokalitu indexu** – aktivní partition obsahuje jen poslední hodiny eventů,
  index je malý a vejde se do RAM.

:::callout{type="pattern"}
### SQL: Outbox jako daily-partitioned tabulka {#partitioning-sql-heading}

:::code{language="sql" filename="snippet.sql"}
-- Hlavní tabulka jako partitioned parent.
CREATE TABLE outbox (
    id             UUID NOT NULL,
    message_type   VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    aggregate_id   VARCHAR(64) NOT NULL,
    payload        JSONB NOT NULL,
    status         VARCHAR(16) NOT NULL,
    occurred_at    TIMESTAMPTZ NOT NULL,
    attempts       INT NOT NULL,
    available_at   TIMESTAMPTZ NOT NULL,
    sent_at        TIMESTAMPTZ,
    last_error     TEXT,
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

Sloupce odpovídají schématu z [15.03](#schema), včetně chybějících DEFAULT klauzulí.
Liší se jen primární klíč: PostgreSQL u partitioned tabulky vyžaduje, aby obsahoval
sloupec, podle kterého se dělí.
:::

Provozní automatizace: rozšíření [pg_partman](https://github.com/pgpartman/pg_partman)
spravuje vznik nových partitions i mazání starých přes cron. Pro MySQL existuje
nativní `PARTITION BY RANGE` se stejným efektem, ale bez pg_partman ekvivalentu –
správa je manuální.

### Distributed relay – multi-instance {#distributed-relay-heading}

Singleton polling worker (`replicas: 1` v Kubernetes) je nejjednodušší
konfigurace. Má ale dvě slabiny. První je **single point of failure**: worker
spadne nebo zamrzne a lag roste, dokud ho Kubernetes nerestartuje. Druhá je
**omezená propustnost**, protože jeden PHP proces odbaví řádově stovky až nízké
tisíce zpráv za sekundu.

Pro produkci s vyšším objemem nebo vyšším HA požadavkem se nabízejí dvě cesty:

**Cesta 1 – leader election přes Redis/etcd.** Běží víc workerů, ale publikuje
jen jeden, „leader“. Když leader spadne, převezme jeho roli jiný nejpozději
po vypršení lease (v ukázce 10 s). Výsledkem je HA bez double publish,
propustnost se ale nezvýší – dispatchuje pořád jen jeden worker.

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

        // Lease už někdo drží. Pokud tato instance, prodlouží se TTL.
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

Worker volá `acquireOrRenew()` každé 3 sekundy (TTL 10 s dává rezervu pro síťové
zpoždění). Dokud metoda vrací `false`, worker stojí. Jakmile při některém tiku
vrátí `true`, stal se leaderem a začne dispatchovat. Pozor: zpracování batche musí
**doběhnout dřív, než lease vyprší**, nebo si ho worker musí během batche průběžně
obnovovat. Jinak lease převezme nový leader, začne dispatchovat řádky, které starý
worker ještě publikuje, a vznikne double publish.

**Cesta 2 – `SELECT … FOR UPDATE SKIP LOCKED`.** Více workerů běží paralelně a každý
si zarezervuje vlastní batch řádků. Žádný leader, žádný single point of failure.

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
zůstává zachovaná. `SKIP LOCKED` podporuje PostgreSQL od verze 9.5 i MySQL 8.
Cenou je pořadí: eventy téhož agregátu mohou vyjít mimo pořadí, když je
zpracovávají různé workery v různých batchích. Pokud na pořadí subscriberovi záleží,
rozdělí se outbox podle `aggregate_id` (například hash modulo počet workerů)
a každý worker zpracovává jen svou část.

### Backpressure – co když broker nestíhá {#backpressure-heading}

Když Kafka nebo RabbitMQ nestíhá přijímat (síťová chyba, plný disk brokera, volba
partition leadera), relay dostává na publish timeout nebo chybu. Outbox řádky
zůstávají `pending` a kupí se. Produkční INSERTy se přitom nebrzdí: kdo začne
blokovat aplikační vrstvu, šíří výpadek brokera do core domény.

Výpadek brokera je jiný druh chyby než nepublikovatelná zpráva ze sekce
o [dead-letter queue](#dlq-heading). `markFailed()` z [15.03](#schema) by při půlhodinovém
výpadku vyčerpal pět pokusů zhruba za minutu a přesunul do `failed` i zdravé
řádky. Chybu spojení s brokerem proto relay do `attempts` nezapočítává: přeruší
cyklus a čeká s backoffem na úrovni celého workeru.

Standardní vzor má čtyři složky. Worker po neúspěšném publishi přechází na
exponential backoff a čeká 1 s, 2 s, 4 s, maximálně 30 s. Mezitím loguje
`outbox_publish_errors_total`. Alert hlídá rychlost růstu pending:
`delta(outbox_pending_count[5m]) > 10000` signalizuje, že zápis převyšuje
odběr a broker nestíhá. Kapacitně musí databáze absorbovat 30 minut
výpadku brokera; při 1k events/s to je 1,8 mil. řádků navíc, tedy rozpočet
na disk a vacuum. A u eventů s nízkou prioritou (audit, metriky), které
ztrátu snesou, lze při dlouhodobém backpressure zvážit řízený sampling.
Doménové eventy (`OrderPlaced`) ale zahodit nelze.

## 15.08 Anti-vzory {#antivzory}

Outbox má jednoduché schéma, přesto se v code review opakují stále tytéž chyby.
Ruší jeho garance a vracejí systém k dual-write problému.

:::callout{type="warn"}
### Publish napřímo z metody agregátu {#anti-direct-publish-heading}

Některé framework wrappery (Laravel events, Symfony EventDispatcher nad DB
entitami) lákají k „*fire-and-forget*“ stylu přímo z metody agregátu.
Jakmile event letí do brokera ještě před commitem doménové transakce, je
dual-write zpět. Příčinou bývá sync transport, pořadí middleware nebo explicitní
`$bus->dispatch()`. Smyslem outboxu je, že event jde
**do téže DB transakce** jako doménový stav.
:::

:::callout{type="warn"}
### Outbox bez UNIQUE constraintu na `id` / inbox bez UNIQUE na `(event_id, consumer)` {#anti-no-unique-heading}

U outboxu drží unikátnost `id` primární klíč. Duplicitu z opakovaného handleru
ale nezachytí, protože každý pokus vygeneruje nové UUID; tu odfiltruje až `eventId`
v inboxu. Tam musí unikátnost vynutit kompozitní UNIQUE `(event_id, consumer)`.
Bez něj projdou dva souběžné workery kontrolou `isProcessed()` současně, oba zapíšou
řádek a vedlejší efekt proběhne dvakrát. Kontrola v aplikaci souběh nezachytí,
zachytí ho jen constraint v databázi. UNIQUE je technický invariant, ne dekorace.
:::

:::callout{type="warn"}
### Inbox check a vedlejší efekt ne v jedné transakci {#anti-inbox-no-tx-heading}

Klasická chyba: `if ($inbox->isProcessed($id)) return;` běží v autocommit
režimu, vedlejší efekt na read modelu také a teprve *potom* se vloží řádek do inboxu.
Mezi kontrolu a insert ale může proklouznout druhý paralelní worker, pro kterého
je zpráva pořád „nová“, a update zduplikuje. Řešením je **celý handler obalit
do `wrapInTransaction`** a UNIQUE constraint na inboxu nechat jako pojistku.
:::

:::callout{type="warn"}
### Read model bez idempotentní logiky {#anti-no-idempotent-side-effect-heading}

I se správným inboxem nemusí být vedlejší efekt uvnitř transakce sám o sobě
idempotentní. Klasický příklad: `UPDATE counter SET value = value + 1`
pro každý `OrderPlaced`. Když se inbox check někdy vypne
(např. při reinicializaci), counter naskočí o víc. Bezpečnější je
`UPSERT` / `INSERT ... ON CONFLICT DO UPDATE` místo inkrementace
a counter dopočítávat agregací v reportovacích dotazech, ne držet jako
materializovaný stav.
:::

:::callout{type="warn"}
### Více paralelních relay workerů bez koordinace {#anti-multiple-relays-heading}

Spustit `app:outbox:dispatch` ve dvou containerech najednou bez
`SELECT ... FOR UPDATE SKIP LOCKED` nebo bez leader electionu znamená,
že obě repliky vidí stejné `pending` řádky a publikují je dvakrát.
Inbox duplicity odchytí, ale broker i databáze nesou zbytečnou zátěž.
Pravidlo: *jeden relay singleton, nebo SKIP LOCKED.*
:::

:::callout{type="warn"}
### Publish před commitem, ne v doctrine_transaction middleware {#anti-publish-before-commit-heading}

Volání `$bus->dispatch()` před tím, než `EntityManager::flush()` opravdu zapíše
do DB, je dual-write přímo z učebnice. Bez middlewaru `doctrine_transaction`
totiž kolem handleru žádná transakce nevzniká: platí běžný autocommit režim
DBAL spojení, ve kterém si každý `flush()` obalí jen vlastní zápisy. Handler
proto obalte explicitně do `wrapInTransaction` všude tam, kde middleware
v `messenger.yaml` aktivní nemáte. Obojí zároveň se nedělá: dvě vrstvy transakce
nad sebou drží savepoint a maskují, kde se doopravdy commituje.

Pravidlo se přitom týká jen zápisů, které opouštějí proces. Doménová událost poslaná
na synchronní `event.bus` uvnitř téže transakce dual-write není. Posluchač běží nad
stejným spojením, a spadne-li transakce, zmizí i jeho zápis. Rozhodující je cíl, ne
okamžik: co jde do brokera nebo cizí služby, musí projít outboxem; co zůstává
v procesu, sběrnici stačí.
:::

## 15.09 Migrace existujícího projektu – krok za krokem {#migrace}

Osmnáct měsíců starý Symfony projekt se stovkou handlerů a publishem po flushi
se na outbox nepřevádí big-bang refaktorem. Outbox přibývá handler po handleru,
vedle stávajícího chování, a starý kód mizí teprve tehdy, když nový prokazatelně
funguje.

### Krok 1: Přidat outbox tabulku a entitu {#migrace-krok-1-heading}

Vytvořte migraci podle sekce [15.03](#schema), spusťte
`doctrine:migrations:migrate`, nasaďte do produkce. **Tabulku zatím
nikdo nepoužívá**, riziko regrese je nulové. Ověřte, že migrace skutečně
vytvořila kompozitní index `idx_outbox_status_time`, ne jen jednosloupcový.

### Krok 2: Refactor jednoho handleru {#migrace-krok-2-heading}

Vyberte jeden hlavní handler – typicky `PlaceOrderHandler` nebo cokoli,
kde dual-write nejvíc bolí. Přidejte do něj `wrapInTransaction` a místo
`$bus->dispatch($event)` volejte `$outbox->store(OutboxMessage::fromIntegrationEvent(...))`
s integračním tvarem události (viz [15.04](#aggregate-publishes)).
Staré `$bus->dispatch()` *zatím nemažte* – legacy subscribeři, kteří
poslouchají na sync transportu, by přestali fungovat.

### Krok 3: Přidat inbox subscriberům jeden po druhém {#migrace-krok-3-heading}

Pro každý subscriber kontextu vytvořte `inbox` tabulku, refaktorujte handler
podle sekce [15.06](#inbox). Jde o nejdelší krok migrace (typicky týdny),
ale paralelizovatelný napříč týmy – každý kontext si Inbox přidává nezávisle.
Inbox musí stát dřív než relay. Jinak by subscribeři mezi nasazením relaye
a vlastního Inboxu dostávali každou událost dvakrát a dvakrát ji zpracovali.

### Krok 4: Nasadit relay command {#migrace-krok-4-heading}

Až mají Inbox všichni subscribeři událostí z refaktorovaného handleru,
implementujte `OutboxDispatchCommand` ze sekce [15.05](#relay) a nasaďte ho
pod supervisorem. Dokud je aktivní i legacy publish, dostane broker *obě* verze
každé události a Inbox druhou kopii zahodí. Podmínkou je, že legacy dispatch
z kroku 2 nese stejné `eventId` jako řádek v outboxu. Jinak Inbox obě kopie
nespáruje.

### Krok 5: Vypnout legacy publish {#migrace-krok-5-heading}

Až relay běží stabilně, smažte v handleru původní `$bus->dispatch()`;
doručení událostí pak zůstává jen na outboxu. **Jde o riskantní krok** – během
prvních dnů sledujte outbox lag a počet duplicit zachycených inboxem. Když něco selhává,
revert pull requestu vrátí změnu během pěti minut.

### Krok 6: Měřit a tunit {#migrace-krok-6-heading}

Po měsíci provozu projděte metriky: jaký je medián lagu, jakým tempem roste tabulka,
kolik řádků skončilo ve `failed`, kolik duplicit Inbox odchytil. Podle těchto
čísel se ladí polling interval relay procesu, batch limit, retence cleanupu
a prahy alertů. Outbox není „set-and-forget“ – vyžaduje občasnou provozní údržbu.

:::callout{type="warn"}
### Před produkčním nasazením {#migrace-warning-heading}

Migrace na Outbox je **data-changing** operace. Před produkcí ji
otestujte ve *staging* prostředí s reálnou velikostí dat (kopie
produkční DB) a ověřte, že:

- relay worker vydrží 24 h bez restartu;
- v lagu nejsou „špičky“, které by signalizovaly contention na DB;
- cleanup command netrvá déle než pollingový interval (jinak blokuje DB);
- vypnutí legacy publishu nerozbije konzistenci subscriberů
  (porovnejte na stagingu read model před a po).
:::

## 15.10 Shrnutí {#summary}

Outbox Pattern stojí na tabulce navíc, jednom Symfony commandu a úpravě jednoho
application handleru. Výměnou vyřadí celou třídu chyb (ztracené eventy, phantom eventy),
které by se jinak dohledávaly v logech až po incidentu. Výsledná garance
je at-least-once delivery událostí přes libovolný message broker,
bez XA/2PC a bez speciální cloudové služby.

Idempotent Inbox je nutný protějšek na straně subscribera. Bez něj se duplikace
z outboxu propíše do read modelů a vedlejších efektů a zisk z outboxu se ztratí.
Outbox s Inboxem dávají *exactly-once efekt*: každý event se v read modelu
projeví právě jednou, i když broker dodá zprávu vícekrát.

### Srovnání s alternativami {#alternativy-heading}

Outbox není jediná odpověď na dual-write. Ostatní cesty mají užší záběr nebo vyšší cenu.

| Řešení | Jak řeší dual-write | Kdy se hodí |
|---|---|---|
| Transactional Outbox | Zápis události do téže DB transakce, publikuje relay | Výchozí volba všude, kde agregát žije v ACID databázi |
| Event Sourcing | Událost *je* stav, druhý zápis neexistuje | Když se pro doménu vyplatí i zbytek modelu, ne jen kvůli doručení |
| Listen-to-yourself | Aplikace nejdřív publikuje, DB zapíše až konzument vlastní zprávy | Když je broker spolehlivější než vlastní DB a čtení smí být opožděné |
| Synchronní volání | Dual-write nevzniká, kontexty se volají přímo | Malý systém bez asynchronní integrace; platí se autonomií kontextů |
| 2PC / XA | Distribuovaná transakce nad DB i brokerem | Prakticky nikdy – viz [15.01](#dual-write) |

Kombinace se nevylučují. Event-sourcovaný kontext outbox tabulku nepotřebuje,
protože event store ji zastane, ale Inbox na straně konzumenta potřebuje pořád.

Hlavní body pro praxi:

- Outbox je **tabulka v téže DB jako doménový stav** – jinak ztrácí smysl.
- Doctrine entita potřebuje `#[ORM\Index(columns: ['status', 'occurred_at'])]`,
  bez něj relay dělá full table scan při každém pollingu.
- Atomicitu orderu a outbox řádků garantuje jedna transakce: middleware
  `doctrine_transaction`, nebo `$em->wrapInTransaction(...)` v handleru.
- Polling Publisher pod supervisorem stačí pro téměř každý Symfony projekt;
  Transaction Log Tailing přes Debezium pouze pro Kafka-native systémy
  s vysokým objemem, a i tam za cenu jiného schématu tabulky.
- Inbox tabulka má UNIQUE `(event_id, consumer)` – sloupec consumer je
  klíč pro multi-subscriber scénáře.
- Monitoring outbox lagu, počtu odeslaných a selhaných zpráv a inbox duplicit je nezbytný.
- Migrace existujícího projektu je inkrementální – handler po handleru, kontext
  po kontextu, nikdy big-bang.

Outbox Pattern navazuje na vzory z předchozích kapitol. V
[CQRS](/cqrs) zajišťuje spolehlivé doručení eventů z command
side do read side. V [Event Sourcingu](/event-sourcing) roli outboxu přebírá
event store a relay nahrazují projekce, které ho čtou.
V [ságách](/sagy-a-process-managery) garantuje doručení událostí
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
  answer: 'Pro běžný Symfony projekt zvolte Polling Publisher (varianta A). Operační režie je minimální (jeden Symfony command pod supervisorem) a latence pod 1 sekundou je dostatečná pro typické obchodní scénáře (objednávky, platby, notifikace). Debezium / CDC se vyplatí, až když máte (a) Kafkovou infrastrukturu už nasazenou, (b) latenční požadavek pod 50 ms, (c) objem nad 10 000 events/s, (d) tým, který má zkušenost s Kafka Connect. Jinak zaplatíte několikanásobnou provozní složitost za malý přínos. Detail v <a href="#relay">sekci 15.05</a>.'
- question: 'Co když používáme NoSQL databázi (MongoDB, Cassandra, DynamoDB)?'
  answer: 'Pokud váš agregát žije v NoSQL bez ACID transakcí napříč více dokumenty (Cassandra, raná verze MongoDB), klasický Outbox Pattern nefunguje – atomicita zápisu order + event mezi dvěma collections není garantovaná. Možnosti: (1) MongoDB 4.0+ má multi-document transakce, takže Outbox lze, (2) DynamoDB nabízí TransactWriteItems, takže Outbox jde, (3) Cassandra ACID transakce nemá (logged batch zaručí jen, že se nakonec provedou všechny zápisy, bez izolace) – používá se Change Data Capture nebo event sourcing s eventy uloženými přímo v dokumentu agregátu. Volba úložiště pro doménový stav rozhoduje, zda lze Outbox vůbec implementovat.'
- question: 'Jak velký dělat batch v relayi?'
  answer: 'Standardně 100 řádků za polling cyklus. Interval 100 ms platí jen pro prázdný outbox: dokud jsou pending řádky, relay jede bez pauzy a propustnost určí latence brokera a databáze. Pokud lag stoupá nad 5 sekund a CPU brokera má rezervu, zvyšte limit na 500. U batche nad 1 000 narazíte na DB serializaci updateů – místo jednoho velkého batche práci rozdělte na víc workerů se SELECT ... FOR UPDATE SKIP LOCKED. Hlavní pravidlo: nejdřív měřit, pak ladit, ne „na cit“.'
- question: 'Vyplatí se Outbox v monolitu?'
  answer: 'Ano, vyplatí – protože dual-write problem nevzniká až mezi mikroservisami, ale mezi <em>libovolnými dvěma transakčními systémy</em>. Monolitická aplikace publikující eventy do RabbitMQ/Redis Streams má přesně stejný problém jako mikroservis: transakce databáze je oddělená od potvrzení brokera. Pokud váš monolit už má event-driven kontexty (Symfony Messenger s async transportem, Spatie Laravel events, ...), Outbox se vyplatí stejně jako v mikroservisách. Jediný případ, kdy ho nepotřebujete, je <em>striktně synchronní</em> monolit, kde publish neexistuje a všechno teče v jedné HTTP transakci.'
- question: 'Co dělat při dlouhodobém výpadku brokera?'
  answer: 'Outbox jako celek je <strong>self-healing</strong>: když broker leží 30 minut, relay worker dostává timeout/connection refused, řádky zůstávají ve stavu pending, jejich počet i lag rostou – ale aplikační handlery dál zapisují události (jen do DB). Po obnovení brokera relay během několika minut vyšle backlog, lag se vrátí k normálu a subscribeři dorovnají stav. Potřeba je: (a) alert na lag &gt; 30 s, aby tým o výpadku věděl, (b) dostatek místa v DB na nahromaděné pending řádky (typicky není problém, řádky jsou
    malé), (c) kompakce, která nemaže <code>pending</code> řádky, jen <code>sent</code> starší než N dní, (d) relay, který chybu spojení s brokerem nezapočítává do pokusů řádku – jinak by výpadek přesunul zdravé zprávy do <code>failed</code> (viz <a href="#backpressure-heading">Backpressure</a>).'
- question: 'Musím použít UUID/ULID, nebo stačí AUTO_INCREMENT?'
  answer: 'Použijte UUID v7 (případně ULID), ne AUTO_INCREMENT. Důvody: (1) UUID v7 je globálně unikátní napříč instancemi DB – nehrozí kolize při replikaci, restore z backupu nebo migraci. (2) Nese časovou složku, takže ID koreluje s pořadím vytvoření – užitečné pro debugging a pro indexové scany. (3) Klient ho může vygenerovat předem a poslat jako event_id v Idempotency-Key headeru. (4) AUTO_INCREMENT komplikuje sharding a multi-region nastavení. Symfony Uid komponenta poskytuje pohodlné API: <code>Uuid::v7()</code> v entitě stačí.'
:::
