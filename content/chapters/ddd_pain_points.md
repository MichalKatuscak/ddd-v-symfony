---
route: ddd_pain_points
path: /ddd-v-praxi-kde-to-boli
title: DDD v praxi – kde to bolí
page_title: "DDD v praxi – kde to bolí | DDD Symfony"
meta_description: "Dvacet reálných bolestivých míst v DDD: transakce přes agregáty, Doctrine mapping, Outbox pattern, idempotence Messenger handlerů, ACL, Strangler Fig."
meta_keywords: "DDD problémy, Doctrine transakce agregáty, Outbox pattern Symfony, Messenger debugging, idempotence handler, validace DDD, Anti-Corruption Layer PHP, strangler fig pattern, Symfony Form Command, API Platform agregát"
og_type: article
published: "2026-03-26"
modified: "2026-05-04"
breadcrumb_name: DDD v praxi – kde to bolí
schema_type: TechArticle
schema_headline: "DDD v praxi – kde to bolí"
chapter_number: "20"
category: Praxe
deck: "Katalog 20 reálných bolestivých míst při implementaci DDD v PHP a Symfony: transakce přes agregáty, Doctrine mapping, Outbox pattern, debugging Messengeru, validace, Anti-Corruption Layer, přesvědčení managementu a další."
reading_time: 35
difficulty: 4
---

Předchozí kapitoly pokryly teorii i pokročilé vzory: od
[základních stavebních bloků](/zakladni-koncepty) přes
[CQRS](/cqrs) a
[Event Sourcing](/event-sourcing) až po
[Ságy a Process Managery](/sagy-a-process-managery).
V praxi se implementace DDD střetává s řadou problémů, na které standardní DDD literatura
většinou neupozorňuje. Architektonické principy narážejí na realitu frameworku, databáze,
asynchronní infrastruktury i týmové dynamiky.

Tato kapitola je **katalog 20 reálných provozních problémů**, se kterými se setkávají týmy
implementující DDD v PHP a Symfony. Zaměřuje se na třenice s konkrétní technologií: Doctrine
Unit of Work, Symfony Messenger, Outbox pattern, autorizace, race conditions. Pro každý problém
najdete: popis situace, analýzu příčiny a doporučené řešení – tam kde je to výmluvné, s ukázkou kódu.

Pro úhel **kódových a modelovacích anti-vzorů** (anémický model, Primitive Obsession, God
Aggregate, sdílená databáze napříč BC) viz [Anti-vzory](/anti-vzory). Pro **rozhodovací rámec**,
jestli DDD vůbec použít, viz [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

## 20.01 A – Doctrine vs. doménový model {#doctrine}

Doctrine ORM je bohatý nástroj, ale jeho interní model (Unit of Work, Identity Map, lazy loading)
je stavěný pro jednoduchý CRUD. Bohaté doménové modely s ním přicházejí do konfliktu na šesti
místech.

### A1. Transakce přes agregáty a Doctrine Unit of Work {#a1-transakce}

**Problém:** DDD říká, že jedna transakce smí měnit nejvýše jeden agregát.
Praxe ale přináší situace, kde potřebujete atomicky uložit změny ve dvou agregátech
zároveň – například přesunout objednávku do stavu *Transferred* a zároveň
potvrdit skladovou rezervaci. Doctrine sdílí jeden `EntityManager`
(a tím jeden Unit of Work) přes celou aplikaci; jeden `flush()` commituje
vše, co EM sleduje.

**Příčina:** Doctrine Unit of Work je *session-scoped* – drží
identity map všech načtených entit a při `flush()` uloží všechny změny
najednou v jediné databázové transakci. Pro CRUD to dává smysl, pro DDD to znamená,
že neúmyslně načtená entita z jiného agregátu může být commitnuta společně s vaší
záměrnou změnou.

**Řešení:** Application Service funguje jako explicitní transakční hranice.
Pokud váš use case vyžaduje změnu dvou agregátů atomicky a nemůžete použít
[Outbox](/event-sourcing#outbox) + [Sagu](/sagy-a-process-managery), zavolejte
explicitně `beginTransaction()` / `commit()` v Application Service. Oba repozitáře
volejte v téže transakci. Toto je **přijatelná výjimka z pravidla jeden agregát =
jedna transakce** za předpokladu, že oba agregáty leží ve stejném Bounded Context
a stejné databázi.

:::callout{type="pattern"}
#### PHP: Application Service jako transakční hranice {#a1-code-heading}

:::code{language="php" filename="src/Warehouse/Application/Service/ConfirmTransferService.php"}
<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Service;

use App\Ordering\Domain\Repository\OrderRepository;
use App\Warehouse\Domain\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ConfirmTransferService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ReservationRepository $reservations,
        private readonly EntityManagerInterface $em,
    ) {}

    public function execute(ConfirmTransferCommand $command): void
    {
        $this->em->beginTransaction();
        try {
            $order       = $this->orders->get($command->orderId);
            $reservation = $this->reservations->get($command->reservationId);

            $order->markAsTransferred();
            $reservation->confirmFor($order->id());

            $this->orders->save($order);
            $this->reservations->save($reservation);

            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
:::
:::

:::callout{type="note"}
Pokud oba agregáty nesdílejí databázi (nebo jsou v různých Bounded Contexts),
použijte místo transakce
[Outbox pattern](/event-sourcing#outbox) nebo Sagu.
Atomická cross-context transakce je architektonický zápach.
:::

### A2. „Špinavý“ EntityManager a nechtěné změny {#a2-spinavy-em}

**Problém:** V read-heavy akcích (příprava dat pro API response, sestavení
read modelu) načtete entitu z databáze, provedete výpočet, ale *neuložíte nic*.
Přesto se při prvním `flush()` kdekoli v requestu (třeba v jiné části aplikace)
commitují změny do databáze. Důvod: nenápadně jste modifikovali entitu, kterou
Doctrine stále sleduje.

**Příčina:** Doctrine Identity Map zapamatuje každý načtený objekt
a při `flush()` porovnává aktuální stav se snapshoty uloženými při
načtení (*change tracking*). Volání getterů, které interně modifikují stav
(lazy-init kolekce, computed fields), může způsobit detekci „změny“.

**Řešení – tři přístupy podle situace:**

| Situace | Řešení |
|---|---|
| Read model v jednom requestu | `$em->detach($entity)` po načtení – EM přestane entitu sledovat (dostupné v ORM 2.x i 3.x; pozn.: `merge()` bylo naopak v ORM 3.x odstraněno) |
| Komplexní read queries | Použijte `HYDRATE_ARRAY` nebo raw SQL přes `$em->getConnection()` – EM nehydratuje objekty |
| Celý controller je read-only | Injektujte separátní `EntityManager` nakonfigurovaný jako read-only (second EM v Symfony) |

### A3. Mapping složitých Value Objects {#a3-value-objects}

**Problém:** Doctrine `#[Embedded]` funguje dobře pro jednoduché
VO (jméno + příjmení → dva sloupce). Limity narazíte v několika případech:
polymorfní VO (různé typy cen), nullable VO v kolekcích, VO s vlastní serializační
logikou (Money = integer + string). Stejně tak u VO, které se mapují na jiný datový
typ než default (enum, JSONB, custom SQL type).

**Řešení – Custom Doctrine Type:** Implementujte `Type`
z `Doctrine\DBAL\Types`. Typ definuje, jak se PHP objekt serializuje
do SQL hodnoty a zpět. Zaregistrujte typ v `config/packages/doctrine.yaml`.

:::callout{type="pattern"}
#### PHP: Custom Type pro Money Value Object {#a3-code-heading}

:::code{language="php" filename="src/SharedKernel/Infrastructure/Doctrine/Type/MoneyType.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Doctrine\Type;

use App\SharedKernel\Domain\ValueObject\Money;
use App\SharedKernel\Domain\ValueObject\Currency;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class MoneyType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(50)'; // formát: "12345_CZK"
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }
        [$amount, $currencyCode] = explode('_', (string) $value, 2);

        return new Money((int) $amount, new Currency($currencyCode));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        /** @var Money $value */
        return $value->amountInCents() . '_' . $value->currency()->code();
    }
}
:::
:::

Typ zaregistrujte v `config/packages/doctrine.yaml`:

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
doctrine:
    dbal:
        types:
            money: App\SharedKernel\Infrastructure\Doctrine\Type\MoneyType
:::

Poté ho použijte v entitě:

:::code{language="php" filename="snippet.php"}
#[ORM\Column(type: 'money', nullable: true)]
private ?Money $price = null;
:::

:::callout{type="note"}
Pro **polymorfní VO** (různé typy platby: karta, hotovost, voucher)
zvažte místo dědičnosti **Value Object s diskriminátorem**.
Typ uložte jako enum do jednoho sloupce a detaily jako JSON do druhého.
Tím se vyhnete discriminator map, která je pro VO těžkopádná.
:::

### A4. Lazy loading vs. bohaté agregáty {#a4-lazy-loading}

**Problém:** Doctrine ve výchozím nastavení načítá asociace lazy – místo skutečného
objektu vloží do property proxy třídu, která se inicializuje až při prvním přístupu.
Bohaté agregáty (metody jako `totalPrice()`, `items()`) mohou neúmyslně spouštět
lazy load *mimo otevřenou transakci* nebo *po detach()*. Výsledkem je výjimka
`UninitializedLazyObjectException` (PHP 8.4 lazy objects) nebo
`ORMInvalidArgumentException` v starších verzích Doctrine ORM.

**Příčina:** Lazy proxy je infrastrukturní koncept – doménový model
o ní neví a nesmí vědět. Bohužel, pokud Doctrine vloží proxy na místo
`OrderItems`, doménová metoda `$order->items()`
v sobě implicitně spoléhá na aktivní databázové připojení.

**Řešení – podle složitosti situace:**

| Situace | Řešení |
|---|---|
| Kolekce vždy potřebná s agregátem | `fetch: 'EAGER'` na asociaci – načte v jednom JOIN |
| Kolekce potřebná jen někdy | Repozitář nabídne dvě metody: `get()` (lazy) a `getWithItems()` (EAGER JOIN) |
| Serializace / JSON response | Nikdy neserializujte agregát přímo – sestavte DTO z načtených dat uvnitř transakce |

### A5. Identity generation – kdy a kde {#a5-identity}

**Problém:** Doctrine standardně generuje ID v databázi
(`SEQUENCE`, `AUTO_INCREMENT`). Nově vytvořený agregát nemá ID, dokud není
persistován a flushed. Tím se porušuje doménový invariant: každý agregát musí
mít identitu od okamžiku vzniku.

**Příčina:** Databázové generování ID šetří jednu round-trip pro získání ID, ale váže
vznik identity na infrastrukturu. Doménový model by neměl vědět o databázi; identita
patří do domény.

**Řešení:** Generujte UUID v doméně, v konstruktoru agregátu.
Doctrine nakonfigurujte s `strategy: 'NONE'` – ID předáváte sami,
Doctrine ho jen uloží.

:::callout{type="pattern"}
#### PHP: UUID v konstruktoru agregátu (PHP 8.4 + Symfony Uid) {#a5-code-heading}

:::code{language="php" filename="src/Ordering/Domain/ValueObject/OrderId.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final class OrderId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self((string) Uuid::v7()); // UUIDv7 - time-sortable
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid OrderId: {$value}");
        }
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

// V agregátu:
final class Order
{
    private OrderId $id;

    public function __construct(CustomerId $customerId)
    {
        $this->id = OrderId::generate(); // identita vzniká v doméně
        // ...
    }
}
:::
:::

Doctrine mapping pro UUID ID:

:::code{language="php" filename="snippet.php"}
#[ORM\Id]
#[ORM\Column(type: 'string', length: 36)]
#[ORM\GeneratedValue(strategy: 'NONE')] // Doctrine ID nepřiřazuje
private string $id;
:::

### A6. Polymorfismus a discriminator map {#a6-polymorfismus}

**Problém:** Potřebujete modelovat hierarchii – například různé typy
doručení (`HomeDelivery`, `PickupPoint`, `LockerDelivery`).
Doctrine nabízí `InheritanceType::SINGLE_TABLE` nebo
`JOINED` s discriminator map. Jenže: přidání nového subtypu vyžaduje
úpravu anotace na *rodičovské* třídě, a discriminator map je zapsána v kódu
jako statický seznam – narušuje Open/Closed Principle.

**Řešení – dvě alternativy:**

| Přístup | Kdy použít | Nevýhoda |
|---|---|---|
| **Value Object místo dědičnosti** | Varianty se liší jen daty, ne chováním | Složitý switch pro chování |
| **Flat table + Custom Type** | Varianty mají odlišné chování | JSON sloupec pro detaily ztrácí typovou bezpečnost |
| **Discriminator map (Doctrine default)** | Málo variant, stabilní hierarchie | Rigidní, narušuje OCP |

Pro většinu DDD scénářů se osvědčuje **Value Object s type fieldem**:
jeden enum sloupec pro typ, jeden JSON sloupec pro specifická data varianty.
Logika se přesouvá do doménových metod, které přijímají VO jako parametr –
ne do dědičnosti.

## 20.02 B – Asynchronní infrastruktura {#async}

Symfony Messenger a asynchronní fronty přinášejí distribuovanou komunikaci –
a s ní distribuované problémy: zprávy se ztrácejí, doručují dvakrát, přicházejí
v nesprávném pořadí. Tato sekce pokrývá čtyři nejčastější bolesti.

### B1. Outbox pattern – zaručené doručení doménových událostí {#b1-outbox}

**Problém:** Uložíte agregát (`flush()` proběhne úspěšně),
ale před tím, než stihnete odeslat doménovou událost do Messengeru, server spadne.
Událost se ztratí – databáze je konzistentní, ale žádný subscriber ji nikdy
nezpracuje. Platba proběhla, ale sklad nebyl upozorněn.

**Příčina:** `flush()` a `$bus->dispatch()` jsou dvě separátní operace bez atomické záruky.
Neexistuje způsob, jak je zabalit do jedné transakce – databáze a message broker jsou různé systémy.

**Řešení – Outbox pattern:** Místo přímého odeslání do brokeru
uložte událost do `outbox` tabulky *ve stejné databázové transakci*
jako agregát. Separátní worker pak z tabulky čte a odešle zprávy do Messengeru.
Atomicita je garantována databázovou transakcí; at-least-once doručení zajišťuje worker.

:::callout{type="pattern"}
#### PHP: OutboxEvent entita a OutboxPublisher service {#b1-code-heading}

:::code{language="php" filename="src/SharedKernel/Infrastructure/Outbox/OutboxEvent.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Outbox;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_events')]
final class OutboxEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $eventType;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    public function __construct(string $eventType, array $payload)
    {
        $this->eventType = $eventType;
        $this->payload   = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function markAsPublished(): void
    {
        $this->publishedAt = new \DateTimeImmutable();
    }

    public function isPublished(): bool { return $this->publishedAt !== null; }
    public function eventType(): string  { return $this->eventType; }
    public function payload(): array     { return $this->payload; }
}
:::
:::

Důležitý detail: outbox záznamy musí být persistovány *uvnitř* téže transakce
jako agregát. Listener musí reagovat na událost `onFlush` (ještě před
commitem) – nikoliv na `postFlush`, který se volá *po* commitu
transakce a tedy mimo ni. Použití `postFlush` s voláním dalšího
`flush()` by navíc způsobilo nekonečnou rekurzi.

:::code{language="php" filename="src/OutboxEventListener.php"}
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final class OutboxEventListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getEntityManager(); // getObjectManager() odstraněno v ORM 3.x
        $uow = $em->getUnitOfWork();

        // Projdeme nové i změněné entity a sebereme doménové události
        foreach ([...$uow->getScheduledEntityInsertions(), ...$uow->getScheduledEntityUpdates()] as $entity) {
            if (!$entity instanceof HasDomainEvents) {
                continue;
            }
            foreach ($entity->releaseDomainEvents() as $event) {
                $outbox = new OutboxEvent(get_class($event), $this->serializer->normalize($event));
                $em->persist($outbox);
                // Outbox entitu musíme ručně přidat do Unit of Work - jsme uvnitř onFlush
                $uow->computeChangeSet($em->getClassMetadata(OutboxEvent::class), $outbox);
            }
        }
        // Žádný další flush() - outbox záznamy jsou součástí probíhající transakce
    }
}
:::

:::callout{type="note"}
Symfony Messenger nabízí vlastní **Doctrine Transport**,
který ukládá zprávy do databáze a garantuje at-least-once doručení bez nutnosti
vlastního Outbox kódu. Zvažte jeho použití jako alternativu před implementací
vlastního Outbox patternu.
:::

### B2. Debugging ztracené zprávy v Messengeru {#b2-debugging}

**Problém:** Zpráva odešla do async fronty. Worker běží.
Handler ale nikdy nezavolal. Jak zjistit, kde zpráva skončila?

**Postup debuggingu:**

**1. Zkontrolujte failed transport:**

:::code{language="bash" filename="snippet.sh"}
php bin/console messenger:failed:show
:::

Pokud je zpráva zde, zobrazí se s chybou. Znovu ji zpracujte:

:::code{language="bash" filename="snippet.sh"}
php bin/console messenger:failed:retry
:::

**2. Zapněte verbose logging:** V `config/packages/monolog.yaml`
přidejte handler pro `messenger` channel na úroveň `debug`.
Každý dispatch, receive a zpracování se zaloguje.

**3. Correlation ID middleware:** Přidejte vlastní middleware, který
přiřadí každé zprávě UUID a loguje ho při dispatch i při receive. Pak hledáte
v logu podle ID.

:::callout{type="pattern"}
#### PHP: Middleware pro Correlation ID logging {#b2-code-heading}

:::code{language="bash" filename="snippet.sh"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Uid\Uuid;

// Vlastní Stamp - musí implementovat StampInterface
final class CorrelationIdStamp implements \Symfony\Component\Messenger\Stamp\StampInterface
{
    public function __construct(public readonly string $correlationId) {}
}

final class CorrelationIdMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(CorrelationIdStamp::class)
            ?? new CorrelationIdStamp((string) Uuid::v7());

        $this->logger->info('Messenger: processing message', [
            'correlation_id'  => $stamp->correlationId,
            'message_class'   => $envelope->getMessage()::class,
        ]);

        return $stack->next()->handle(
            $envelope->with($stamp),
            $stack,
        );
    }
}
:::
:::

Zaregistrujte middleware v `config/packages/messenger.yaml`:

:::code{language="bash" filename="snippet.sh"}
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - App\SharedKernel\Infrastructure\Messenger\CorrelationIdMiddleware
:::

### B3. Idempotence handlerů {#b3-idempotence}

**Problém:** Messenger garantuje *at-least-once* doručení –
nikoli exactly-once. Pokud worker zprávu zpracuje, ale před potvrzením (ack)
spadne, broker zprávu znovu doručí. Handler ji zpracuje podruhé. Výsledkem může
být dvojitá platba, duplicitní objednávka nebo zdvojený email.

**Řešení – Idempotency Middleware s deduplikační tabulkou:**
Každá zpráva nese `IdempotencyStamp` s unikátním klíčem
(vygenerovaným při prvním odeslání). Middleware před zpracováním zkontroluje
databázovou tabulku – pokud klíč existuje, zprávu přeskočí.

:::callout{type="pattern"}
#### PHP: IdempotencyMiddleware {#b3-code-heading}

:::code{language="php" filename="src/SharedKernel/Infrastructure/Messenger/CorrelationIdStamp.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Messenger;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

// Vlastní Stamp nesoucí idempotency klíč
final class IdempotencyStamp implements StampInterface
{
    public function __construct(public readonly string $key) {}
}

final class IdempotencyMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Connection $connection) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(IdempotencyStamp::class);

        if ($stamp === null) {
            return $stack->next()->handle($envelope, $stack); // zpráva bez klíče: vždy zpracuj
        }

        $alreadyProcessed = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM processed_messages WHERE idempotency_key = ?',
            [$stamp->key],
        );

        if ($alreadyProcessed) {
            return $envelope; // duplikát - přeskočit bez zpracování
        }

        $result = $stack->next()->handle($envelope, $stack);

        $this->connection->insert('processed_messages', [
            'idempotency_key' => $stamp->key,
            'processed_at'    => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return $result;
    }
}
:::
:::

:::callout{type="note"}
Tabulka `processed_messages` poroste bez omezení. Přidejte
pravidelný cleanup (cron) nebo `TTL` index pro automatické mazání
starých záznamů. Obvyklá retence je 7–30 dní – doba, po které broker
přestane doručovat retries.
:::

:::callout{type="warn"}
**TOCTOU race condition:** Kód výše obsahuje závodní podmínku –
dvě paralelní instance workeru mohou obě vidět, že záznam neexistuje
a obě zprávu zpracovat. Pořadí SELECT + zpracování + INSERT navíc znamená,
že při výjimce v handleru se klíč nezapíše a zpráva se zkusí znovu.
To je správné chování, ale odhaluje jiný problém: pokud INSERT provedeme
*před* zpracováním, selhání handleru zanechá klíč zapsaný
a zpráva nebude nikdy zopakována (ztracená zpráva).

Bezpečné řešení: proveďte zpracování a INSERT do deduplikační tabulky
**v téže databázové transakci**. Při selhání handleru transakce
selže celá (klíč se nevloží) a Messenger zprávu zopakuje:

:::code{language="yaml" filename="config/packages/messenger.yaml"}
$this->connection->beginTransaction();
try {
    // Unique constraint na idempotency_key zabrání duplicitě na DB úrovni
    $this->connection->insert('processed_messages', [
        'idempotency_key' => $stamp->key,
        'processed_at'    => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
    $result = $stack->next()->handle($envelope, $stack);
    $this->connection->commit();
    return $result;
} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
    $this->connection->rollBack();
    return $envelope; // duplicitní zpráva - přeskočit
} catch (\Throwable $e) {
    $this->connection->rollBack(); // handler selhal - klíč se nezapíše, Messenger zopakuje
    throw $e;
}
:::
:::

### B4. Ordering zpráv – zpráva B dorazí před A {#b4-ordering}

**Problém:** Máte dva workery zpracovávající stejnou frontu paralelně.
Obě události `OrderPlaced` a `OrderShipped` jsou odeslány za sebou,
ale `OrderShipped` zpracuje jiný worker rychleji. Handler se pokusí označit
objednávku jako odeslanou, jenže objednávka ještě neexistuje (nebo je ve špatném
stavu).

**Řešení – tři přístupy podle kontextu:**

| Přístup | Kdy použít | Trade-off |
|---|---|---|
| **Optimistický retry** | Závislost je krátkodobá (ms) | Handler hodí výjimku → Messenger retry s `DelayStamp` |
| **Jeden worker na agregát** | Ordering je kritický | Nižší throughput, ale garantované pořadí per-aggregate |
| **Inbox buffer** | Komplexní závislosti | Handler uloží zprávu do „inbox“ tabulky a zpracuje ji až po splnění podmínek |

:::callout{type="note"}
**Pozor:** Pro ordering problémy *nepoužívejte*
`UnrecoverableMessageHandlingException` – ta zprávu
**přeskočí retry strategii** a zprávu okamžitě přesune do failed transport.
Správný přístup je hodit **standardní výjimku**; Messenger zprávu
odloží do retry fronty s exponential backoff. Pokud po vyčerpání všech retries
stále selhává, teprve pak skončí v failed transport – kde ji lze prozkoumat
a znovu odeslat.
:::

## 20.03 C – Modelování {#modelovani}

Správné doménové modelování je obtížnější než implementace – vyžaduje disciplínu
v rozhodnutích, která se zdají triviální, dokud nezpůsobí problém.

### C1. Kde žije validace {#c1-validace}

**Problém:** Validace je rozeseta na třech místech: Symfony Validator
(anotace na DTO), Application Service (doménové podmínky) a doménový konstruktor
(invarianty). Výsledkem je buď duplicita (stejná pravidla na dvou místech),
nebo díry (pravidlo chybí na jednom místě).

| Typ validace | Kde patří | Příklad |
|---|---|---|
| **Formátová validace** | API / formulářová vrstva (Symfony Validator) | Email musí být validní formát, číslo musí být kladné |
| **Doménový invariant** | Konstruktor / metoda agregátu nebo VO | Množství nesmí být nulové, cena nesmí být záporná |
| **Doménová politika** | Domain Service nebo Application Service | Zákazník nesmí mít více než 5 otevřených objednávek |
| **Databázová unikátnost** | Databázový unique constraint + Application Service check | Email zákazníka musí být unikátní v systému |

**Hlavní pravidlo:** Doménový invariant vždy vynucujte v doméně.
Nespoléhejte na validaci ve vyšší vrstvě – doménový objekt může být sestaven
i z jiného místa (CLI command, test, import). Symfony Validator je
*první linie obrany* pro uživatelský vstup, nikoli náhrada doménové validace.

### C2. Stavový automat bez anémického modelu {#c2-stavy}

**Problém:** Objednávka prochází stavy: *Draft → Placed → Paid →
Shipped → Delivered → Cancelled*. Anémický přístup: `$order->setStatus('shipped')`
– stav se změní bez guard conditions, bez side effectů, bez kontroly, zda přechod
dává smysl.

**Řešení:** Explicitní metody pro každý přechod. Metoda ověřuje,
zda je přechod validní (guard condition), provede změnu stavu a zaregistruje
doménovou událost.

:::code{language="php" filename="src/SharedKernel/Infrastructure/Messenger/IdempotencyStamp.php"}
final class Order
{
    private OrderStatus $status = OrderStatus::Draft;

    public function place(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new \DomainException("Objednávku lze odeslat pouze ve stavu Draft.");
        }
        $this->status = OrderStatus::Placed;
        $this->record(new OrderPlaced($this->id));
    }

    public function ship(TrackingNumber $trackingNumber): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new \DomainException("Objednávku lze expedovat pouze po zaplacení.");
        }
        $this->status         = OrderStatus::Shipped;
        $this->trackingNumber = $trackingNumber;
        $this->record(new OrderShipped($this->id, $trackingNumber));
    }
}
:::

:::callout{type="note"}
**Symfony Workflow** může spravovat přechody stavů – ale jako
*infrastrukturní helper*, nikoli jako součást doménového modelu.
Doménový objekt nesmí záviset na `WorkflowInterface`. Voter / Controller
může použít Workflow pro UI logiku; doménová metoda ověřuje invariant sama.
:::

### C3. Anti-Corruption Layer k externím API {#c3-acl}

**Problém:** Stripe vrací `\Stripe\Charge`, Ares vrací
XML nebo pole, Fakturoid vrací vlastní DTO. Pokud tato data z externích systémů
prosakují přímo do doménového kódu, změna externího API = změna doménového modelu.

**Řešení – Port & Adapter (Hexagonální architektura):**
Doménový model definuje **Port** (interface) popisující, co potřebuje
od externího systému – v doménových pojmech. Infrastrukturní vrstva implementuje
**Adapter**, který přeloží externí API do doménového rozhraní.

:::callout{type="pattern"}
#### PHP: Port v doméně + Adapter v infrastruktuře {#c3-code-heading}

:::code{language="php" filename="src/PaymentGateway.php"}
<?php

// Port - v doméně (App\Payment\Domain\Port)
interface PaymentGateway
{
    /** @throws PaymentFailedException */
    public function charge(Money $amount, PaymentToken $token): PaymentId;

    /** @throws RefundFailedException */
    public function refund(PaymentId $id, Money $amount): void;
}

// Adapter - v infrastruktuře (App\Payment\Infrastructure\Stripe)
final class StripePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly \Stripe\StripeClient $stripe) {}

    public function charge(Money $amount, PaymentToken $token): PaymentId
    {
        try {
            $charge = $this->stripe->charges->create([
                'amount'   => $amount->amountInCents(),
                'currency' => strtolower($amount->currency()->code()),
                'source'   => $token->value(),
            ]);
            return PaymentId::fromString($charge->id);
        } catch (\Stripe\Exception\CardException $e) {
            throw new PaymentFailedException($e->getMessage(), previous: $e);
        }
    }

    public function refund(PaymentId $id, Money $amount): void
    {
        $this->stripe->refunds->create([
            'charge' => $id->toString(),
            'amount' => $amount->amountInCents(),
        ]);
    }
}
:::
:::

Doménový kód pracuje pouze s `PaymentGateway` rozhraním – nic neví
o Stripe. Výměna platební brány (Stripe → Adyen) vyžaduje pouze nový Adapter,
doménový kód se nemění.

### C4. Ubiquitous Language drift {#c4-language}

**Problém:** Po šesti měsících vývoje kód mluví jiným jazykem než
doménový expert. V kódu je `Invoice`, zákazník říká „faktura“,
účetní systém zná „Bill“. Třída `Order` pokrývá pojmy, které
doména rozděluje na „nabídku“, „objednávku“ a „smlouvu“. Vývojáři si
přestávají být jisti, co třída modeluje.

**Příčina:** Ubiquitous Language není statický artefakt – vyvíjí se
s pochopením domény. Bez aktivní správy kód zaostává za aktuálním chápáním.

**Opatření – čtyři praktiky:**

1. **Doménový glosář v repozitáři** (`docs/glossary.md`) –
   živý dokument, kde každý pojem má definici, synonyma a odkaz na třídu v kódu.
   Aktualizuje se při každém přejmenování.

2. **Architecture Decision Records (ADR)** – při každém záměrném
   přejmenování konceptu zapište ADR s důvodem. Budoucí vývojář pochopí, proč
   `Contract` nahradil `Order`.

3. **Event Storming jako pravidelná aktivita** – ne jednorázový workshop
   na začátku projektu, ale čtvrtletní revize s doménovými experty.

4. **Living documentation přes testy** – BDD-style popis v testech
   (`it_places_an_order_when_items_are_in_stock()`) tvoří čitelnou dokumentaci
   aktuálního chování.

## 20.04 D – Symfony-specifické třenice {#symfony}

Symfony je rozsáhlý framework, ale některé jeho konvence cílí na CRUD aplikace.
Tato sekce popisuje tři místa, kde framework-first přístup koliduje s DDD.

### D1. Symfony Form vs. Command {#d1-form}

**Problém:** `FormType` ve Symfony chce mutable objekt,
který hydratuje daty z requestu. Application Command by naopak měl být immutable
DTO sestaven z validovaných dat. Tyto dva světy se obtížně kombinují bez toho,
aby `FormInterface` pronikl do aplikační vrstvy.

**Řešení:** Form mapuje na **plain mutable DTO**
(formulářový objekt), Application Service pak sestaví immutable Command.
Žádná ze dvou vrstev neví o existenci té druhé.

:::code{language="php" filename="src/PlaceOrderFormData.php"}
// 1. Formulářový objekt - mutable, framework-friendly
final class PlaceOrderFormData
{
    public string $customerId = '';
    public array  $items      = [];
}

// 2. FormType pracuje s formulářovým objektem
$form = $this->createForm(PlaceOrderType::class, new PlaceOrderFormData());
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    /** @var PlaceOrderFormData $data */
    $data = $form->getData();

    // 3. Controller sestaví Command - immutable, doménově typovaný
    $command = new PlaceOrderCommand(
        customerId: CustomerId::fromString($data->customerId),
        items: array_map(
            fn($i) => new OrderItemDto($i['productId'], (int) $i['quantity']),
            $data->items,
        ),
    );

    $this->commandBus->dispatch($command);
}
:::

`PlaceOrderCommand` je readonly PHP class – doménový kód s ní pracuje
bez jakékoli závislosti na Symfony Form komponentě.

### D2. API Platform vs. doménové agregáty {#d2-api-platform}

**Problém:** API Platform ve výchozím nastavení očekává přímý přístup
k Doctrine entitám – čte a zapisuje je pomocí vestavěných Provider a Processor.
Agregáty ale nechceme serializovat přímo (interní stav by pronikl do API)
ani nechat API Platform je modifikovat bez Application Service.

**Řešení:** Vystavte API Platform **API Resource DTO**
(ne agregát) a implementujte vlastní `StateProvider`
a `StateProcessor`, které fungují jako adaptéry k Application Services.

:::callout{type="pattern"}
#### PHP: StateProcessor jako adapter k Application Service {#d2-code-heading}

:::code{language="php" filename="src/Ordering/Infrastructure/ApiPlatform/OrderResource.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Ordering\Application\Command\PlaceOrderCommand;
use Symfony\Component\Messenger\MessageBusInterface;

// API resource DTO - nikdy agregát
#[ApiResource(operations: [new Post(processor: PlaceOrderProcessor::class)])]
final class OrderResource
{
    public string $customerId;
    public array  $items;
    // Pouze to, co API má vidět
}

// StateProcessor - tenká vrstva
final class PlaceOrderProcessor implements ProcessorInterface
{
    public function __construct(private readonly MessageBusInterface $commandBus) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderResponse
    {
        /** @var OrderResource $data */
        // ID generujeme před dispatchem - dispatch() vrací Envelope, ne doménový objekt
        $orderId = OrderId::generate();

        $command = new PlaceOrderCommand(
            orderId: $orderId,
            customerId: CustomerId::fromString($data->customerId),
            items: $data->items,
        );

        $this->commandBus->dispatch($command);

        return new OrderResponse($orderId->toString());
    }
}
:::
:::

### D3. Security Voter vs. doménová oprávnění {#d3-voter}

**Problém:** Business pravidla přístupu jsou součástí domény.
Příklad: „objednávku může zrušit zákazník nebo admin, ale pouze do 24 hodin
od vytvoření a pouze pokud ještě nebyla expedována“. Symfony Security Voter
žije v infrastrukturní vrstvě a závisí na frameworku. Pokud logiku napíšete
přímo ve Voteru, stane se netestovatelnou bez Symfony kontejneru.

**Řešení:** Voter funguje jako **tenký adaptér**,
který deleguje rozhodnutí na doménovou metodu agregátu. Doménová metoda je
čistá funkce – testovatelná bez frameworku.

:::callout{type="pattern"}
#### PHP: Voter jako tenký adaptér + doménová metoda {#d3-code-heading}

:::code{language="php" filename="src/Order.php"}
<?php

declare(strict_types=1);

// Doménová metoda v agregátu - testovatelná bez frameworku
// Aktuální čas je parametr (ne wall-clock) - metoda je deterministická a snadno testovatelná
final class Order
{
    public function canBeCancelledBy(UserId $userId, \DateTimeImmutable $now): bool
    {
        if ($this->status === OrderStatus::Shipped || $this->status === OrderStatus::Delivered) {
            return false;
        }
        $withinWindow = $this->placedAt > $now->modify('-24 hours');

        return $withinWindow && $this->customerId->equals($userId);
    }
}

// Voter - pouze adaptér, žádná doménová logika
final class OrderVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'ORDER_CANCEL' && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof SecurityUser) {
            return false;
        }

        /** @var Order $subject */
        return $subject->canBeCancelledBy(UserId::fromString($user->getId()), new \DateTimeImmutable());
    }
}
:::
:::

## 20.05 E – Organizace a tým {#tym}

DDD selže ne proto, že by byl technicky špatný – ale proto, že tým ho nepochopil,
management ho nepodpořil nebo znalosti zůstaly u jednoho člověka.

### E1. Business case pro DDD refaktoring {#e1-management}

**Problém:** Management vidí náklady refaktoringu (čas, riziko),
ale ne benefity. „Přepsat to do DDD“ zní jako technická čistota bez obchodní hodnoty.
Vývojáři neumí výhody přeložit do jazyka, který rozhodující osoby slyší.

**Jak argumentovat – měřitelné metriky:**

| Metrika | Jak měřit | Co říká managementu |
|---|---|---|
| **Time-to-feature** | Průměrná doba od zadání po produkci (JIRA, Linear) | Refaktoring → kratší cyklus = rychlejší obchodní reakce |
| **Bug rate per modul** | Počet bugů na 1000 řádků kódu (SonarQube) | Moduly po DDD refaktoringu mají nižší bug rate |
| **Onboarding time** | Čas, než nový vývojář dělá první commit do modulu | Explicitní doménový model = kratší onboarding |
| **Regression rate** | % ticketů označených jako regression | Dobře ohraničené agregáty = méně neúmyslných side effectů |

**Taktika:** Nezačínejte argumentem „náš kód je špatný“.
Začněte konkrétní obchodní bolestí: „Přidání nového způsobu platby trvá 3 týdny
a vždy způsobí regression v objednávkovém modulu. Níže je uvedena příčina a způsob řešení.“

### E2. Postupné zavedení – strangler fig pattern {#e2-strangler}

**Problém:** Big-bang rewrite – přepsání celé aplikace do DDD najednou –
téměř vždy selže. Trvá déle než odhadnuto, tým ztrácí motivaci, byznys se nedočká
nových funkcí. A přitom původní aplikace musí dál žít.

**Řešení – strangler fig pattern:** Identifikujte jeden modul
s nejvyšší změnovou frekvencí (highest-churn), nejčastějšími bugy nebo největší
obchodní hodnotou. Implementujte právě ten modul v DDD. Zbytek aplikace zůstane
beze změny.

**Postup v Symfony projektu:**

1. **Identifikujte modul:** `git log --stat | grep "files changed" | sort -rn | head -20`
   – soubory s nejvíce změnami za posledních 6 měsíců jsou nejlepší kandidáti.

2. **Vytvořte fasádu** přes legacy kód: nový DDD kód volá legacy
   přes interface (ACL vzor). Legacy kód o novém DDD ví co nejméně.

3. **Feature flag:** Pro každý nový modul zapněte DDD implementaci
   pomocí feature flagu. Při problémech okamžitě rollback na legacy.

4. **Opakujte** pro další modul, dokud legacy nevyschne.

:::callout{type="note"}
Strangler fig neznamená, že legacy kód a DDD kód sdílejí databázové tabulky.
Nový modul má vlastní tabulky; data z legacy se migrují postupně,
případně se synchronizují přes events nebo cron job.
:::

### E3. Knowledge silos a bus factor {#e3-silos}

**Problém:** Doménový model je komplexní – a po roce vývoje
mu rozumí dobře jen jeden člověk. Pokud tento člověk onemocní, odejde nebo
je přetížen, tým stojí. Onboarding nového vývojáře trvá měsíce.
Bus factor = 1 je pro projekt kritické riziko.

**Opatření – čtyři praktiky:**

1. **Living documentation přes testy:** Pojmenování testů ve stylu
   `it_cannot_ship_order_that_is_not_paid()` tvoří čitelný katalog
   doménových pravidel. Kdo čte testy, pochopí doménový model bez vývojáře.

2. **Architecture Decision Records (ADR):** Každé netriviální
   rozhodnutí (proč Saga místo 2PC, proč Value Object místo entity, proč
   tento Bounded Context takto ohraničený) zapište do `docs/adr/`.
   Budoucí vývojář pochopí kontext bez „senior kolegy“.

3. **Event Storming jako týmová aktivita:** Modelování domény
   musí probíhat v celém týmu, ne v hlavě jednoho architekta. Pravidelné
   (čtvrtletní) Event Storming sessions sdílejí znalosti a odhalují nekonzistence.

4. **Doménový glosář v repozitáři:** Živý dokument,
   kde každý vývojář může hledat, co `FulfillmentContext` znamená,
   jaké jsou jeho agregáty a na jaké Bounded Contexts navazuje.

:::faq{}
- question: Proč tradiční Doctrine mapování komplikuje čistý doménový model?
  answer: 'Doctrine očekává klasické PHP třídy s veřejnými nebo reflektovanými atributy, zatímco DDD agregát vyžaduje neměnnost, privátní settery a invarianty vynucené v konstruktoru. Konflikt zahrnuje identifikaci přes generované ID (Doctrine) oproti identitě v doméně (DDD), problém „špinavého“ EntityManageru při dlouhých transakcích a omezení typů pro hodnotové objekty. Pragmatická výchozí volba je nechat atributy přímo na agregátu (jsou to metadata, ne chování) a používat Doctrine custom typy pro hodnotové objekty. Pokud chcete striktně oddělenou doménu, jděte cestou <a href="/implementace-v-symfony#persisted-object-pattern">Persisted Object Pattern</a> – samostatný persistence model + mapper. Detail v <a href="#doctrine">sekci Doctrine vs. doménový model</a>.'
- question: Jak řešit Outbox Pattern pro spolehlivé doručení doménových událostí?
  answer: 'Outbox ukládá doménové události do lokální tabulky ve stejné transakci jako změnu agregátu, čímž se zabrání ztrátě událostí při pádu mezi commitem a publikací. Samostatný proces (relay) pak outbox tabulku čte a publikuje události do message busu nebo externího systému. Kombinace s idempotentními konzumenty zajišťuje at-least-once doručení bez duplicit na straně zpracování. Praktický příklad v <a href="#b1-outbox">sekci Outbox Pattern</a>.'
- question: Jak vysvětlit přínos DDD managementu, když první iterace zpomaluje?
  answer: 'Doporučený postup je přiznat krátkodobý náklad a explicitně vyčíslit dlouhodobý přínos: nižší počet regresních chyb, rychlejší onboarding, menší náklady na přidávání nových funkcí po překročení zlomu. Hodí se kombinovat s měřitelnými cíli (lead time, change failure rate) a s pilotním Bounded Contextem, který doručí první výsledky za 3–6 měsíců. Bez sponzorství na úrovni managementu investice do DDD zpravidla neprojde. Rozbor strategie komunikace v <a href="#e1-management">sekci Management</a>.'
- question: Jak udržet Ubiquitous Language, aby časem neutrpěl drift?
  answer: 'Ubiquitous Language zaniká, když se kód a řeč doménových expertů začnou rozcházet – v kódu je „Invoice“, zákazník říká „faktura“. Prevence vyžaduje pravidelný review kódu proti slovníku, ADR při jeho změně a glosář v repozitáři jako živý dokument. Drift se projeví, jakmile nová funkce zavádí pojem, který doménový expert nezná – v ten moment je nutné buď ustoupit, nebo jazyk společně upravit. Detailní rozbor v <a href="#c4-language">sekci Ubiquitous Language drift</a>.'
- question: Jak přežít paralelní existenci staré CRUD části a nové DDD vrstvy?
  answer: 'Strangler Fig pattern umožňuje oba stavy držet v jedné aplikaci: staré CRUD moduly zůstávají v provozu, nové funkce vznikají v DDD stylu a propojení řeší Anti-Corruption Layer. Výzvou je sdílená databáze, autentizace a uživatelský stav. Pragmatické řešení: postupně migrovat podle Bounded Contextu, ne podle modulu, a explicitně přijmout, že projekt bude mít smíšený stav po 12–24 měsíců. Viz <a href="#e2-strangler">sekci Strangler pattern</a>.'
:::
