---
route: ddd_pain_points
path: /ddd-v-praxi-kde-to-boli
title: DDD v praxi – kde to bolí
page_title: "DDD v praxi – kde to bolí | DDD Symfony"
meta_description: "Dvacet reálných bolestivých míst v DDD: transakce přes agregáty, Doctrine mapping, Outbox pattern, idempotence Messenger handlerů, ACL, Strangler Fig."
meta_keywords: "DDD problémy, Doctrine transakce agregáty, Outbox pattern Symfony, Messenger debugging, idempotence handler, validace DDD, Anti-Corruption Layer PHP, strangler fig pattern, Symfony Form Command, API Platform agregát"
og_type: article
published: "2026-03-26"
modified: 2026-09-24
breadcrumb_name: DDD v praxi – kde to bolí
schema_type: TechArticle
schema_headline: "DDD v praxi – kde to bolí"
chapter_number: "20"
category: Praxe
deck: "Katalog 20 reálných bolestivých míst při implementaci DDD v PHP a Symfony: transakce přes agregáty, Doctrine mapping, Outbox pattern, debugging Messengeru, validace, Anti-Corruption Layer, přesvědčení managementu a další."
reading_time: 27
difficulty: 4
github_examples: null
---

Předchozí kapitoly pokryly teorii i pokročilé vzory: od
[základních stavebních bloků](/zakladni-koncepty) přes
[CQRS](/cqrs) a
[Event Sourcing](/event-sourcing) až po
[Ságy a Process Managery](/sagy-a-process-managery).
Implementace DDD naráží na problémy, které učebnice zpravidla nechávají stranou:
architektonické principy se střetávají s realitou frameworku, databáze,
asynchronní infrastruktury i týmové dynamiky.

Kapitola je **katalog 20 reálných provozních problémů**, se kterými se setkávají týmy
implementující DDD v PHP a Symfony. Zaměřuje se na třenice s konkrétní technologií: Doctrine
Unit of Work, Symfony Messenger, Outbox pattern, autorizace, race conditions. U většiny problémů
najdete popis situace, příčinu a doporučené řešení, kde to pomůže, i s ukázkou kódu.

Pro úhel **kódových a modelovacích anti-vzorů** (anémický model, Primitive Obsession, God
Aggregate, sdílená databáze napříč BC) viz [Anti-vzory](/anti-vzory). Pro **rozhodovací rámec**,
jestli DDD vůbec použít, viz [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

## 20.01 A – Doctrine vs. doménový model {#doctrine}

Doctrine ORM má vlastní interní model: Unit of Work, Identity Map, lazy loading. Doménový
model s neměnnými hodnotovými objekty, privátními konstruktory a invarianty na něj naráží
na šesti místech.

### A1. Transakce přes agregáty a Doctrine Unit of Work {#a1-transakce}

**Problém:** Vernonovo vodítko říká, že jedna transakce mění nejvýše jeden agregát.
Praxe ale přináší situace, kdy je potřeba atomicky uložit změny ve dvou agregátech
naráz. Například uzavřít skladový převod (stav *Transferred*) a zároveň
potvrdit rezervaci zboží, kterou převod naplňuje. Doctrine sdílí jeden `EntityManager`
(a tím jeden Unit of Work) přes celou aplikaci; jeden `flush()` commituje
vše, co EM sleduje.

**Příčina:** Doctrine Unit of Work žije po celý request (nebo zpracování jedné zprávy). Drží
identity map všech načtených entit a při `flush()` uloží všechny změny
najednou v jediné databázové transakci. Pro CRUD je to pohodlné. V DDD to znamená,
že neúmyslně změněná entita z jiného agregátu se commitne spolu s vaší
záměrnou změnou.

**Řešení:** Application Service funguje jako explicitní transakční hranice.
Pokud use case vyžaduje atomickou změnu dvou agregátů a nelze použít
[Outbox Pattern](/outbox-pattern) + [ságu](/sagy-a-process-managery), obalte obě změny
jednou transakcí. Doctrine k tomu nabízí `wrapInTransaction()`, které dokumentace
doporučuje před ručním `beginTransaction()` / `commit()` právě proto, aby vývojář
nezapomněl na rollback. Jde o **přijatelnou výjimku z pravidla jeden agregát =
jedna transakce**, pokud oba agregáty leží ve stejném Bounded Contextu
a ve stejné databázi. Kdy je taková výjimka obhajitelná a kdy jde o špatně vedenou hranici
agregátu, rozebírá kapitola [Návrh agregátů](/navrh-agregatu).

:::callout{type="pattern"}
#### PHP: Application Service jako transakční hranice {#a1-code-heading}

:::code{language="php" filename="src/Warehouse/Application/Service/ConfirmTransferService.php"}
<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Service;

use App\Warehouse\Domain\Repository\ReservationRepository;
use App\Warehouse\Domain\Repository\TransferOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ConfirmTransferService
{
    public function __construct(
        private readonly TransferOrderRepository $transfers,
        private readonly ReservationRepository $reservations,
        private readonly EntityManagerInterface $em,
    ) {}

    public function execute(ConfirmTransferCommand $command): void
    {
        // wrapInTransaction() drží rollback i commit; vlastní try/catch není potřeba
        $this->em->wrapInTransaction(function () use ($command): void {
            // Oba agregáty patří do kontextu Warehouse a sdílejí databázi.
            $transfer    = $this->transfers->get($command->transferOrderId);
            $reservation = $this->reservations->get($command->reservationId);

            $transfer->markAsTransferred();
            $reservation->confirmFor($transfer->id);

            $this->transfers->save($transfer);
            $this->reservations->save($reservation);
        });
    }
}
:::
:::

:::callout{type="warn"}
**EntityManager je po neúspěšném `flush()` zavřený.** Doctrine transakci rollbackne
a `EntityManager` uzavře; jakákoli další práce s ním skončí výjimkou. Odchycení
výjimky o úroveň výš problém neřeší: volající drží nepoužitelný objekt.
Dokumentace je v tomto jednoznačná: další unit of work po výjimce vyžaduje nový
`EntityManager`. V Symfony ho vrátí `ManagerRegistry::resetManager()`. Prakticky to
znamená, že request, ve kterém `flush()` selhal, už nemá co zachraňovat. Logujte
a nechte ho spadnout.
:::

:::callout{type="note"}
Pokud oba agregáty nesdílejí databázi (nebo jsou v různých Bounded Contexts),
použijte místo transakce
[Outbox Pattern](/outbox-pattern) nebo ságu.
Atomická cross-context transakce je architektonický zápach.
:::

### A2. „Špinavý“ EntityManager a nechtěné změny {#a2-spinavy-em}

**Problém:** V read-heavy akcích (příprava dat pro API response, sestavení
read modelu) načtete entitu z databáze, spočítáte nad ní hodnotu, ale *neuložíte nic*.
Přesto první `flush()` kdekoli v requestu (třeba v jiné části aplikace)
zapíše změny do databáze. Entitu, kterou Doctrine stále sleduje, jste totiž nenápadně
změnili.

**Příčina:** Doctrine Identity Map si pamatuje každý načtený objekt
a při `flush()` porovnává aktuální stav se snapshoty uloženými při
načtení (*change tracking*). Getter, který interně mění stav (například uloží
dopočítanou hodnotu do mapované vlastnosti), Doctrine vyhodnotí jako změnu.

**Řešení:** tři přístupy podle situace.

| Situace | Řešení |
|---|---|
| Read model v jednom requestu | `$em->detach($entity)` po načtení – EM přestane entitu sledovat (dostupné v ORM 2.x i 3.x; pozn.: `merge()` bylo naopak v ORM 3.x odstraněno) |
| Komplexní read queries | Použijte `HYDRATE_ARRAY` nebo raw SQL přes `$em->getConnection()` – EM nehydratuje objekty |
| Celý controller je read-only | Dotazy s hintem `Query::HINT_READ_ONLY`, případně entity mapované jako `#[ORM\Entity(readOnly: true)]` – UnitOfWork je při `flush()` nekontroluje. Read-only EntityManager Doctrine nemá |

ORM 3 přitom zrušil obvyklý únikový manévr. Argumenty `flush($entity)` a `clear($entityName)`
jsou pryč a obě metody je tiše ignorují, protože přebytečný argument uživatelské metody
PHP nehlásí. To je horší než chyba: `clear('Order')` vypadá jako cílené odpojení, ale odpojí
celou Identity Map. „Uložím jen tenhle agregát“ dnes vyjádřit nelze, `flush()` vždy
commituje celý Unit of Work, a každá nechtěně sledovaná entita tím zdražuje.

### A3. Mapping složitých Value Objects {#a3-value-objects}

**Problém:** Doctrine `#[Embedded]` funguje dobře pro jednoduché
VO (jméno + příjmení → dva sloupce). Na limity narazí polymorfní VO (různé typy cen),
nullable VO v kolekcích, VO s vlastní serializační logikou (Money = integer + string)
a VO mapované na jiný datový typ než výchozí (enum, JSONB, custom SQL type).

**Řešení:** Custom Doctrine Type. Implementujte `Type`
z `Doctrine\DBAL\Types`; typ definuje, jak se PHP objekt převede
na SQL hodnotu a zpět.

:::callout{type="pattern"}
#### PHP: Custom Type pro Money Value Object {#a3-code-heading}

:::code{language="php" filename="src/SharedKernel/Infrastructure/Doctrine/Type/MoneyType.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Doctrine\Type;

use App\SharedKernel\Domain\Money;
use App\SharedKernel\Domain\Currency;
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

        return new Money((int) $amount, Currency::from($currencyCode));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        /** @var Money $value */
        return $value->amountInCents . '_' . $value->currency->value;
    }
}
:::
:::

Typ zaregistrujte v `config/packages/doctrine.yaml`:

:::code{language="yaml" filename="config/packages/doctrine.yaml (výřez: custom typ)"}
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

:::callout{type="warn"}
Ukázka slévá dvě dimenze do jednoho sloupce a tím obětuje dotazovatelnost: nad
`"12345_CZK"` neuděláte `SUM()`, `ORDER BY` ani index podle částky. Pro `Money` samotné
je proto obvykle lepší `#[Embedded]` se dvěma sloupci, nebo custom typ zapisující do dvou
sloupců. Jednosloupcová serializace dává smysl až u hodnot, které se do skalárních sloupců
rozložit nedají a v SQL se nad nimi stejně nefiltruje.
:::

:::callout{type="note"}
Pro **polymorfní VO** (různé typy platby: karta, hotovost, voucher)
se místo dědičnosti hodí **Value Object s diskriminátorem**:
typ jako enum v jednom sloupci, detaily jako JSON ve druhém.
Odpadne tak discriminator map, která je pro VO těžkopádná.
:::

### A4. Lazy loading a doménové metody {#a4-lazy-loading}

Doctrine ve výchozím nastavení načítá asociace lazy: do property vloží proxy, která se
inicializuje až při prvním přístupu. Doménová metoda jako `totalPrice()`
nebo `items()` o tom nic neví a implicitně spoléhá na aktivní databázové připojení.
Když záznam, na který proxy ukazuje, mezitím z databáze zmizel, inicializace selže
s `EntityNotFoundException`. Odpojení entity samo chybu nevyvolá. Inicializátor proxy
stav v UnitOfWork nekontroluje a data dočte přes persister, dokud je spojení otevřené.
Platí to pro klasické proxy třídy i pro nativní lazy objekty PHP 8.4.

Nativním lazy objektům se přitom nevyhnete. Od ORM 3.5 je jejich vypnutí na PHP 8.4+
deprecated a ve verzi 4.0 zmizí úplně; `Configuration::enableNativeLazyObjects(true)`
je cílový stav. Kdo dnes staví chování na detailech vygenerovaných proxy tříd, opírá se
o odcházející implementaci.

Lazy proxy je infrastrukturní koncept. Doménový model o ní vědět nesmí, jenže ji
v paměti nese. O způsobu načtení proto rozhoduje až repozitář nebo
konkrétní query.

**Řešení podle složitosti situace:**

| Situace | Řešení |
|---|---|
| Kolekce potřebná jen někdy | Repozitář nabídne dvě metody: `get()` (lazy) a `getWithItems()` s fetch joinem v DQL – ten JOIN skutečně vydá |
| Kolekce vždy potřebná s agregátem | `fetch: 'EAGER'` na asociaci – druhý dotaz pro kolekce všech rodičů najednou, ne N+1, ale ani JOIN |
| Serializace / JSON response | Nikdy neserializujte agregát přímo – sestavte DTO z načtených dat uvnitř transakce |

Pořadí řádků v tabulce není náhodné. Fetch join v DQL je první volba, protože platí jen
pro konkrétní dotaz. `fetch: 'EAGER'` v mapování působí globálně a u to-many asociací
nedělá to, co většina lidí čeká. JOIN vydává jen u to-one asociací, a i tam si Doctrine
vyhrazuje volbu mezi LEFT JOIN a druhým dotazem. Podrobněji k volbě strategie viz
[Výkonnostní aspekty](/vykonnostni-aspekty).

### A5. Identity generation – kdy a kde {#a5-identity}

**Problém:** Doctrine standardně generuje ID v databázi
(`SEQUENCE`, `AUTO_INCREMENT`). Nově vytvořený agregát nemá ID, dokud ho Doctrine
nepersistuje a neflushne. Tím padá doménový invariant: každý agregát musí
mít identitu od okamžiku vzniku.

**Příčina:** Databázové generování ID je pohodlné, ale váže
vznik identity na infrastrukturu. Doménový model by neměl vědět o databázi; identita
patří do domény.

**Řešení:** Identitu vyrobte dřív, než agregát vznikne, a předejte ji do továrny.
Kniha používá tvar `Order::place(OrderId $id, CustomerId $customerId)`: `OrderId` si
generuje UUID sám, agregát ho jen přijme. Doctrine se nakonfiguruje bez generátoru,
ID mu předáváte hotové. Tři kapitoly přidávají na téže třídě další továrnu, protože bez ní
by nešlo ukázat jejich téma. [Návrh agregátu](/navrh-agregatu) má
`placeWithFirstItem()`, kde invariant „objednávka má aspoň jednu položku“ vymáhá už
signatura. [Outbox](/outbox-pattern) má `placeWithItems()`, protože seznam položek
potřebuje v payloadu události. [Doplňující vzory](/mene-zname-vzory) mají `placePhysical()` a `placeDigital()`
se stejným principem, jen pod jmény, která říkají, jaký druh objednávky továrna vyrábí. Vždycky jde
o jiné jméno, ne o jinou verzi `place()`. Dvě neslučitelné signatury jedné metody
by v reálném projektu vedle sebe neobstály. Základ zůstává stejný: identita
a vlastník vznikají mimo agregát a vstupují do továrny.

:::callout{type="pattern"}
#### PHP: identita předaná do továrny (PHP 8.4 + Symfony Uid) {#a5-code-heading}

:::code{language="php" filename="src/Ordering/Domain/ValueObject/OrderId.php + Model/Order.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class OrderId
{
    public function __construct(public string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid OrderId: {$value}");
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::v7()); // UUIDv7 - time-sortable
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

// src/Ordering/Domain/Model/Order.php – agregát žije v Model, ne ve ValueObject
namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;
use App\SharedKernel\Domain\AggregateRoot;

final class Order extends AggregateRoot
{
    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
    ) {}

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }
}

/*
 * Volající drží identitu ještě před uložením. Řádky patří do handleru;
 * na úrovni souboru by je PHP vykonalo při autoloadu třídy.
 *
 * $orderId    = OrderId::generate();
 * $customerId = CustomerId::fromString($command->customerId);
 * $order      = Order::place($orderId, $customerId);
 */
:::
:::

Konstruktor zůstává čistý, protože ho volá i rekonstituce z databáze. Událost vzniká
v továrně, viz [životní cyklus Aggregate Root](/zakladni-koncepty#aggregate-root-lifecycle).

Doctrine mapping pro UUID ID. Ukázka mapuje primitivní string; hodnotový objekt
`OrderId` se na sloupec převádí custom Doctrine typem, viz
[sekci A3](#a3-value-objects):

:::code{language="php" filename="snippet.php"}
#[ORM\Id]
#[ORM\Column(type: 'string', length: 36)]
// Bez #[ORM\GeneratedValue] Doctrine ID nepřiřazuje;
// strategy: 'NONE' je podle dokumentace totéž, jen upovídaněji
private string $id;
:::

:::callout{type="note"}
Existuje i třetí varianta rozdělení odpovědnosti: identitu vydává repozitář metodou
`nextIdentity()`. Matthias Noback ji obhajuje vztahem, který mezi repozitářem a identitou
skutečně je: repozitář spravuje entity, tedy i jejich identitu. Praktický rozdíl je malý,
volající stále drží ID před uložením. Kniha zůstává u generování v hodnotovém objektu,
protože nevyžaduje injektovat repozitář tam, kde stačí `OrderId::generate()`.
:::

### A6. Polymorfismus a discriminator map {#a6-polymorfismus}

**Problém:** Potřebujete modelovat hierarchii, například různé typy
doručení (`HomeDelivery`, `PickupPoint`, `LockerDelivery`).
Doctrine nabízí `InheritanceType::SINGLE_TABLE` nebo
`JOINED` s discriminator map. Cena za to je konkrétní, ne principiální. Mapa musí být
zapsaná na kořenové entitě, takže nový subtyp znamená zásah do třídy, která o něm nemá
důvod vědět. U SINGLE_TABLE navíc každý sloupec specifický pro jednu variantu musí být
nullable pro všechny ostatní, u JOINED platíte JOIN při každém čtení. Na jeho dopad na výkon
dokumentace výslovně upozorňuje. A protože jde o schéma, každý přírůstek hierarchie
znamená migraci databáze, ne jen novou třídu.

**Řešení:** dvě alternativy k výchozí discriminator map.

| Přístup | Kdy použít | Nevýhoda |
|---|---|---|
| **Value Object místo dědičnosti** | Varianty se liší jen daty, ne chováním | Složitý switch pro chování |
| **Flat table + Custom Type** | Varianty mají odlišné chování | JSON sloupec pro detaily ztrácí typovou bezpečnost |
| **Discriminator map (Doctrine default)** | Málo variant, stabilní hierarchie | Migrace schématu při každé variantě, nullable sloupce |

Pro většinu DDD scénářů se osvědčuje **Value Object s type fieldem**:
jeden enum sloupec pro typ, jeden JSON sloupec pro specifická data varianty.
Logika se přesouvá do doménových metod, které přijímají VO jako parametr,
ne do dědičnosti.

Ani tahle volba není zadarmo. Switch nad enumem nezmizí, jen se přestěhuje
z dědičnosti do doménové metody. A co uložíte do JSON sloupce, tím přestanete
filtrovat, indexovat a agregovat v SQL. Volba tedy zní: platit migrací schématu,
nebo dotazovatelností.

## 20.02 B – Asynchronní infrastruktura {#async}

Symfony Messenger a asynchronní fronty přinášejí distribuovanou komunikaci
a s ní distribuované problémy: zprávy se ztrácejí, doručují dvakrát, přicházejí
v nesprávném pořadí. Následují čtyři nejčastější bolesti.

### B1. Outbox pattern – zaručené doručení doménových událostí {#b1-outbox}

**Problém:** Uložíte agregát (`flush()` proběhne úspěšně),
ale před tím, než stihnete odeslat doménovou událost do Messengeru, server spadne.
Událost se ztratí. Databáze je konzistentní, ale žádný subscriber ji nikdy
nezpracuje. Platba proběhla, ale sklad nebyl upozorněn.

**Příčina:** `flush()` a `$bus->dispatch()` jsou dvě separátní operace bez atomické
záruky. Zabalit je do jedné transakce nelze, databáze a message broker jsou různé systémy.

**Řešení:** událost uložit do `outbox` tabulky ve stejné transakci jako agregát
a odeslání nechat na odděleném procesu. Atomicitu pak drží databázová transakce.

Schéma tabulky, dvě varianty relay procesu, idempotentní inbox na straně příjemce,
provozní metriky i postup migrace existujícího projektu rozebírá samostatná kapitola
[Outbox pattern](/outbox-pattern).

:::callout{type="note"}
Vlastní implementaci často nahradí **Doctrine Transport** v Symfony Messengeru.
Ukládá zprávy do databáze a garantuje at-least-once doručení bez vlastního kódu. Atomicky
s agregátem ale jen tehdy, když se zpráva odešle uvnitř téže transakce a přes totéž spojení.
Doručuje navíc jen handlerům, které transport samy čtou. Do externího brokera zprávy
nepřeposílá, na to je potřeba relay z kapitoly Outbox Pattern.
:::

### B2. Debugging ztracené zprávy v Messengeru {#b2-debugging}

**Problém:** Zpráva odešla do async fronty. Worker běží.
Handler se ale nikdy nezavolal. Jak zjistit, kde zpráva skončila?

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

:::code{language="php" filename="src/SharedKernel/Infrastructure/Messenger/CorrelationIdMiddleware.php"}
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

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez: correlation ID)"}
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - App\SharedKernel\Infrastructure\Messenger\CorrelationIdMiddleware
:::

### B3. Idempotence handlerů {#b3-idempotence}

**Problém:** Messenger garantuje doručení *at-least-once*,
nikoli exactly-once. Pokud worker zprávu zpracuje, ale před potvrzením (ack)
spadne, broker zprávu znovu doručí. Handler ji zpracuje podruhé. Výsledkem může
být dvojitá platba, duplicitní objednávka nebo zdvojený email.

**Řešení:** Idempotency Middleware s deduplikační tabulkou.
Každá zpráva nese `IdempotencyStamp` s klíčem odvozeným z byznys události, například
`payment.capture:{orderId}`. Middleware před zpracováním zkontroluje
databázovou tabulku. Když klíč existuje, zprávu přeskočí.

Na slově „odvozeným“ celý mechanismus stojí. UUID vygenerované při odeslání se jako
idempotency klíč nehodí: dvojí odeslání téže logické události vyrobí dva různé klíče
a obě zpracování proběhnou. Klíč musí zůstat
stabilní napříč všemi odesláními téhož logického příkazu. Rozdíl je praktický. Náhodné UUID
ošetří duplicitu z retry brokeru, ale ne dvojklik uživatele. A ten přijde častěji.

:::callout{type="pattern"}
#### PHP: IdempotencyMiddleware {#b3-code-heading}

:::code{language="php" filename="src/SharedKernel/Infrastructure/Messenger/IdempotencyMiddleware.php"}
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

    // Klíč je funkcí byznys události, ne času odeslání.
    // Dvojí dispatch téhož příkazu vyrobí tentýž klíč.
    public static function forOperation(string $operation, string $aggregateId): self
    {
        return new self($operation . ':' . $aggregateId);
    }
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
            // Duplikát se přeskočí. Obálka se vrací bez HandledStamp,
            // takže volající na synchronní sběrnici, který si výsledek
            // vytahuje přes HandledStamp, dostane null. Middleware míří
            // na asynchronní příkazy, kde návratovou hodnotu nikdo nečte.
            return $envelope;
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

Odesílatel stamp připojí z dat, která má v ruce:

:::code{language="php" filename="snippet.php"}
$this->commandBus->dispatch(
    new CapturePaymentCommand($orderId),
    [IdempotencyStamp::forOperation('payment.capture', $orderId->value)],
);
:::

:::callout{type="note"}
Tabulka `processed_messages` poroste bez omezení. Přidejte
pravidelný úklid starých záznamů (cron nebo Symfony Scheduler). Retenci odvoďte od brokeru: záznam musí přežít nejdelší dobu, po kterou
může dorazit opakované doručení téže zprávy.
:::

:::callout{type="warn"}
**TOCTOU race condition:** Kód výše obsahuje závodní podmínku.
Dvě paralelní instance workeru mohou obě vidět, že záznam neexistuje,
a obě zprávu zpracovat. Pořadí SELECT + zpracování + INSERT navíc znamená,
že při výjimce v handleru se klíč nezapíše a zpráva se zkusí znovu.
To je správně. Přesunout INSERT *před* zpracování ale nejde: selhání handleru
by zanechalo klíč zapsaný a zpráva by se už nikdy nezopakovala (ztracená zpráva).

Bezpečné řešení: proveďte zpracování a INSERT do deduplikační tabulky
**v téže databázové transakci**. Při selhání handleru transakce
selže celá (klíč se nevloží) a Messenger zprávu zopakuje:

:::code{language="php" filename="snippet.php"}
$this->connection->beginTransaction();

// Catch kryje POUZE insert. Kdyby obepínal i handler, unique violation
// zevnitř domény (duplicitní e-mail) by se vydávala za duplicitní zprávu
// a Messenger by ji potvrdil - zpráva by zmizela.
try {
    // Unique constraint na idempotency_key zabrání duplicitě na DB úrovni
    $this->connection->insert('processed_messages', [
        'idempotency_key' => $stamp->key,
        'processed_at'    => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
    $this->connection->rollBack();
    return $envelope; // duplicitní zpráva - přeskočit
}

try {
    $result = $stack->next()->handle($envelope, $stack);
    $this->connection->commit();
    return $result;
} catch (\Throwable $e) {
    $this->connection->rollBack(); // handler selhal - klíč se nezapíše, Messenger zopakuje
    throw $e;
}
:::
:::

### B4. Ordering zpráv – zpráva B dorazí před A {#b4-ordering}

**Problém:** Máte dva workery zpracovávající stejnou frontu paralelně.
Obě události `OrderPlaced` a `OrderShipped` odejdou za sebou,
ale `OrderShipped` zpracuje jiný worker rychleji. Handler se pokusí označit
objednávku jako odeslanou, jenže objednávka ještě neexistuje (nebo je ve špatném
stavu).

**Řešení:** tři přístupy podle kontextu.

| Přístup | Kdy použít | Kompromis |
|---|---|---|
| **Optimistický retry** | Závislost je krátkodobá (ms) | Handler hodí `RecoverableMessageHandlingException` s `retryDelay` → Messenger zprávu odloží |
| **Jeden worker na agregát** | Pořadí je kritické | Nižší throughput, ale garantované pořadí per-aggregate |
| **Inbox buffer** | Komplexní závislosti | Handler uloží zprávu do „inbox“ tabulky a zpracuje ji až po splnění podmínek |

`RecoverableMessageHandlingException` přijímá parametr `retryDelay` a přebije tím
nakonfigurovanou strategii. Pro chybějící závislost je to přesnější nástroj než obecná
výjimka: čekáte řádově stovky milisekund, ne exponenciální backoff počítaný pro výpadek
externí služby.

Garance pořadí ale nakonec drží transport, ne kód handleru. FIFO nabízí jen některé
brokery a zpravidla za cenu propustnosti nebo omezení na jednu skupinu zpráv. Ověřte,
co váš transport skutečně slibuje, dřív než na pořadí postavíte doménovou logiku.
Zdravější cesta je pořadí nepotřebovat. Handler, který snese zprávy v libovolném sledu,
nemá co rozbít.

:::callout{type="note"}
**Pozor:** Na problémy s pořadím se *nehodí*
`UnrecoverableMessageHandlingException`. Ta
**obchází retry strategii** a zprávu okamžitě přesune do failed transportu.
Zpráva, která přišla brzy, přitom není nezpracovatelná. Patří sem **standardní výjimka**
nebo `RecoverableMessageHandlingException`; po nich Messenger zprávu odloží do retry fronty.
Ve failed transportu skončí až po vyčerpání všech pokusů; tam ji lze prozkoumat
a znovu odeslat.
:::

Zpoždění se nezastaví na hranici workeru. Uživatel, který právě odeslal objednávku
a hned nato vidí přehled bez ní, čte důsledek téhož mechanismu. Co s tím v rozhraní,
rozebírá [Eventual Consistency v praxi](/cqrs#eventual-consistency): potvrzení akce
místo čtení z read modelu, optimistické vykreslení a explicitní stav „zpracovává se“
jsou tři obvyklé odpovědi. Rozhodnutí patří do návrhu obrazovky, ne do handleru.

## 20.03 C – Modelování {#modelovani}

Modelovací rozhodnutí se zdají triviální, dokud nezpůsobí problém v produkci.
Čtyři pasti, které se vracejí nejčastěji.

### C1. Kde žije validace {#c1-validace}

**Problém:** Validace je rozeseta na třech místech: Symfony Validator
(atributy na DTO), Application Service (doménové podmínky) a doménový konstruktor
(invarianty). Výsledkem je buď duplicita (stejná pravidla na dvou místech),
nebo díry (pravidlo chybí na jednom místě).

| Typ validace | Kde patří | Příklad |
|---|---|---|
| **Formátová validace** | API / formulářová vrstva (Symfony Validator) | Email musí být validní formát, číslo musí být kladné |
| **Doménový invariant** | Konstruktor / metoda agregátu nebo VO | Množství nesmí být nulové, cena nesmí být záporná |
| **Doménová politika** | Domain Service nebo Application Service | Zákazník nesmí mít více než 5 otevřených objednávek |
| **Databázová unikátnost** | Databázový unique constraint + Application Service check | Email zákazníka musí být unikátní v systému |

**Hlavní pravidlo:** Doménový invariant se vynucuje vždy v doméně;
validace ve vyšší vrstvě ho nenahradí. Doménový objekt vzniká i jinde:
v CLI příkazu, v testu, při importu. Symfony Validator je
*první linie obrany* pro uživatelský vstup, nikoli náhrada doménové validace.

### C2. Stavový automat bez anémického modelu {#c2-stavy}

Objednávka prochází stavy *Draft → Confirmed → Paid → Shipped → Delivered*, s možností
zrušení do určitého bodu.
Anémický přístup `$order->setStatus('shipped')` přepíše hodnotu bez guard conditions
a bez kontroly, jestli přechod dává smysl. Doména ztrácí pravidla, která ji definují.

Explicitní metoda pro každý přechod ověří, jestli je přechod
platný, změní stav a zaznamená doménovou událost. Tři kroky v jedné
metodě, žádný setter navenek. Holý setter je typickým projevem anémického modelu;
obecný rozbor najdete v [Anti-vzorech](/anti-vzory#anemicky-domenovy-model).

:::code{language="php" filename="snippet.php"}
// Zkrácená podoba Order z kapitoly Návrh agregátu: továrnu place(),
// položky a idempotentní větve výřez vynechává.
class Order extends AggregateRoot
{
    private OrderStatus $status = OrderStatus::Draft;

    public function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
    ) {
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Confirmed->value,
            );
        }
        $this->status = OrderStatus::Confirmed;
        $this->record(new OrderConfirmed($this->id, $this->customerId, new \DateTimeImmutable()));
    }

    public function ship(ShipmentId $shipmentId): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Shipped->value,
            );
        }
        $this->status = OrderStatus::Shipped;
        $this->record(new OrderShipped($this->id, $shipmentId, new \DateTimeImmutable()));
    }
}
:::

:::callout{type="note"}
**Symfony Workflow** může spravovat přechody stavů, ale jako
*infrastrukturní helper*, nikoli jako součást doménového modelu.
Doménový objekt nesmí záviset na `WorkflowInterface`. Voter / Controller
může použít Workflow pro UI logiku; doménová metoda ověřuje invariant sama.
Oficiální stanovisko Symfony k tomuto rozdělení neexistuje. Návrh doplnit do dokumentace
návod na Workflow v DDD správci v roce 2019 odmítli s tím, že dokumentace DDD nevysvětluje
(`symfony/symfony-docs#10819`).
:::

### C3. Anti-Corruption Layer k externím API {#c3-acl}

**Problém:** Stripe vrací `\Stripe\PaymentIntent`, ARES vrací
JSON, Fakturoid vlastní DTO. Pokud tato data z externích systémů
prosakují přímo do doménového kódu, změna externího API = změna doménového modelu.
Vzor jako takový, včetně jeho místa na Context Mapě, rozebírá
[Anti-Corruption Layer](/context-mapping#acl); zde jde o jeho podobu v PHP.

**Řešení:** Ports & Adapters, tedy hexagonální architektura.
Doménový model definuje **Port** (interface), který v doménových pojmech popisuje,
co od externího systému potřebuje. Infrastrukturní vrstva k němu doplní
**Adapter**, který přeloží externí API do doménového rozhraní.

:::callout{type="pattern"}
#### PHP: Port v doméně + Adapter v infrastruktuře {#c3-code-heading}

:::code{language="php" filename="snippet.php"}
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
            // Token nese ID PaymentMethod (pm_…), které vytvořil Stripe.js.
            $intent = $this->stripe->paymentIntents->create([
                'amount'                   => $amount->amountInCents,
                'currency'                 => strtolower($amount->currency->value),
                'payment_method'           => $token->value,
                'payment_method_types'     => ['card'],
                'confirm'                  => true,
                'error_on_requires_action' => true,
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            throw new PaymentFailedException($e->getMessage(), previous: $e);
        }

        if ($intent->status !== 'succeeded') {
            throw new PaymentFailedException(sprintf('Platba skončila ve stavu „%s“.', $intent->status));
        }

        return PaymentId::fromString($intent->id);
    }

    public function refund(PaymentId $id, Money $amount): void
    {
        $this->stripe->refunds->create([
            'payment_intent' => $id->value,
            'amount'         => $amount->amountInCents,
        ]);
    }
}
:::
:::

Doménový kód pracuje jen s rozhraním `PaymentGateway` a o Stripe neví nic.
Výměna platební brány (Stripe → Adyen) si vyžádá jen nový Adapter.
Adapter volá Payment Intents API. Starší Charges API se `source` tokenem Stripe
pro nové integrace nepovoluje. Ověření 3D Secure, které si banka může vyžádat,
ukázkový adapter nezvládne a platbu odmítne. Plná integrace ho řeší na klientovi
přes Stripe.js; pro ilustraci ACL to podstatné není.

### C4. Ubiquitous Language drift {#c4-language}

**Problém:** Po šesti měsících vývoje kód mluví jiným jazykem než
doménový expert. V kódu je `Invoice`, zákazník říká „faktura“,
účetní systém zná „Bill“. Třída `Order` pokrývá pojmy, které
doména rozděluje na „nabídku“, „objednávku“ a „smlouvu“. Vývojáři si
přestávají být jisti, co třída modeluje.

**Příčina:** Ubiquitous Language se vyvíjí s pochopením domény, není to jednou
zapsaný artefakt. Bez aktivní správy kód zaostává za aktuálním chápáním.

**Opatření:** čtyři praktiky.

1. **Doménový glosář v repozitáři** (`docs/glossary.md`) –
   živý dokument, kde každý pojem má definici, synonyma a odkaz na třídu v kódu.
   Aktualizuje se při každém přejmenování.

2. **Architecture Decision Records (ADR)** – při každém záměrném
   přejmenování konceptu zapište ADR s důvodem. Budoucí vývojář pochopí, proč
   `Contract` nahradil `Order`.

3. **Event Storming jako pravidelná aktivita** – ne jednorázový workshop
   na začátku projektu, ale čtvrtletní revize s doménovými experty.

4. **Živá dokumentace přes testy** – BDD-style popis v testech
   (`it_places_an_order_when_items_are_in_stock()`) tvoří čitelnou dokumentaci
   aktuálního chování.

## 20.04 D – Symfony-specifické třenice {#symfony}

Symfony konvence cílí převážně na CRUD. Následují tři místa, kde framework-first přístup
koliduje s doménovým modelem nejviditelněji.

### D1. Symfony Form vs. Command {#d1-form}

**Problém:** Výchozí chování `FormType` je hydratace existujícího objektu přes settery
nebo veřejné property. Application Command má být readonly DTO s povinnými argumenty
konstruktoru. Tvrzení „Symfony Form immutable objekty neumí“ je ale dnes nepřesné:
dokumentace popisuje volbu `empty_data` jako closure, která objekt vyrobí a předá mu
odeslané hodnoty do konstruktoru. Command tedy jde naplnit přímo z formuláře.

Zbývá otázka, kde má vzniknout. Když Command plní formulář, začne tvar
aplikačního příkazu kopírovat tvar obrazovky. S druhým vstupním kanálem
(API, CLI, import) se to projeví.

**Řešení:** Form mapuje na **plain mutable DTO**
(formulářový objekt), controller pak z validovaných dat sestaví immutable Command.
Formulář o Commandu neví a Command o formuláři také ne. Cesta přes `empty_data` se hodí
tam, kde je formulář jediný vstup a mezikrok by byl jen opisem.

:::code{language="php" filename="snippet.php"}
// 1. Formulářový objekt - mutable, kompatibilní s frameworkem
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
        // Identitu přiděluje aplikace, ne databáze - viz sekce A5.
        orderId: OrderId::generate(),
        customerId: CustomerId::fromString($data->customerId),
        items: array_map(
            fn($i) => new OrderItemDto($i['productId'], (int) $i['quantity']),
            $data->items,
        ),
    );

    $this->commandBus->dispatch($command);
}
:::

`PlaceOrderCommand` je readonly PHP třída. Doménový kód s ní pracuje
bez jakékoli závislosti na komponentě Symfony Form.

### D2. API Platform vs. doménové agregáty {#d2-api-platform}

**Problém:** API Platform ve výchozím nastavení očekává přímý přístup
k Doctrine entitám: čte je a zapisuje vestavěnými providery a processory.
Agregáty ale nechceme serializovat přímo (interní stav by pronikl do API)
ani nechat API Platform je modifikovat bez Application Service.

**Řešení:** Vystavte API Platform **API Resource DTO**
(ne agregát) a implementujte vlastní `StateProvider`
a `StateProcessor`, které fungují jako adaptéry k Application Services.

:::callout{type="pattern"}
#### PHP: StateProcessor jako adapter k Application Service {#d2-code-heading}

:::code{language="php" filename="src/Ordering/Application/Command/PlaceOrderCommand.php (varianta s vlastním orderId)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;

/**
 * Proti kanonickému PlaceOrder z kapitoly o Outboxu nese navíc orderId:
 * identitu tu určuje volající ještě před dispatchem, protože dispatch()
 * vrací Envelope, ne doménový objekt.
 */
final readonly class PlaceOrderCommand
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public array $items,
    ) {}
}
:::

:::code{language="php" filename="src/Ordering/Infrastructure/ApiPlatform/OrderResource.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Ordering\Application\Command\PlaceOrderCommand;
use App\Ordering\Application\Dto\OrderResponse;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;
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

        // Příkaz nese i orderId, protože identitu tu určuje volající;
        // kanonický PlaceOrder z kapitoly o Outboxu ji naopak generuje
        // v agregátu a vrací přes HandledStamp.
        $command = new PlaceOrderCommand(
            orderId: $orderId,
            customerId: CustomerId::fromString($data->customerId),
            items: $data->items,
        );

        $this->commandBus->dispatch($command);

        return new OrderResponse($orderId->value);
    }
}
:::
:::

### D3. Security Voter vs. doménová oprávnění {#d3-voter}

**Problém:** Business pravidla přístupu jsou součástí domény.
Příklad: „objednávku může zrušit zákazník, ale pouze do 24 hodin
od vytvoření a pouze pokud ještě nebyla expedována“. Symfony Security Voter
žije v infrastrukturní vrstvě a závisí na frameworku. Pokud logiku napíšete
přímo do Voteru, její test potřebuje bezpečnostní token a další objekty frameworku.

**Řešení:** Voter funguje jako tenký adaptér, který deleguje rozhodnutí na doménovou
metodu agregátu. Doménová metoda na frameworku nezávisí a testuje se bez něj.

Kde přesně která kontrola leží, rozebírá kapitola
[Autorizace v DDD](/autorizace-v-ddd), včetně toho, proč Voter sám nestačí
a co patří přímo do agregátu.

## 20.05 E – Organizace a tým {#tym}

Projekty, které DDD opustí, málokdy ztroskotají na technice. Evans to v *DDD Reference*
shrnuje bez příkras: řada projektů modeluje, a přesto z toho nakonec nic nemá. Důvody,
které tomu obvykle předcházejí, jsou organizační: tým vzor nepochopí, management k němu
nedá mandát, znalost zůstane v hlavě jednoho seniora. Následující tři sekce jsou psané
jako zkušenost, ne jako měření; citovatelná data o opuštění DDD neexistují.

### E1. Business case pro DDD refaktoring {#e1-management}

**Problém:** Management vidí náklady refaktoringu (čas, riziko),
ale ne benefity. „Přepsat to do DDD“ zní jako technická čistota bez obchodní hodnoty.
Vývojáři neumí výhody přeložit do jazyka, který rozhodující osoby slyší.

**Jak argumentovat:** měřitelnými metrikami.

| Metrika | Jak měřit | Proč ji sledovat |
|---|---|---|
| **Change lead time** | Doba od commitu po nasazení do produkce (definice DORA) | Srovnatelná napříč týmy, management ji zná |
| **Change fail rate** | % nasazení, která si vyžádají hotfix nebo rollback | Ukazuje, jestli změny v modulu drží |
| **Regression rate** | % ticketů označených jako regression | Nejblíž bolesti „opravíme jedno, rozbije se druhé“ |
| **Onboarding time** | Čas, než nový vývojář dělá první commit do modulu | Měří srozumitelnost modelu, ne jeho čistotu |

Dvě z těch metrik (change lead time a change fail rate) pocházejí ze sady DORA, která
má dnes pět položek a slouží jako sdílený slovník pro dodávku softwaru. Měřte je před refaktoringem a po něm, ale zdržte
se slibu, že klesnou kvůli DDD. Žádná studie souvislost mezi architektonickým stylem
a chybovostí nedoložila a metrika „bugů na tisíc řádků“ je u refaktoringu zavádějící
sama o sobě: mění se jí jmenovatel. Čísla tedy nesou váhu jako společný jazyk s byznysem,
ne jako důkaz.

**Taktika:** Nezačínejte argumentem „náš kód je špatný“,
ale konkrétní obchodní bolestí. *Ilustrativní scénář:* „Přidání nového způsobu platby
trvá tři týdny a pokaždé způsobí regression v objednávkovém modulu.“ Následuje příčina
a návrh řešení. Čísla si dosaďte vlastní; půjčené odhady rozhodovatel prohlédne.

### E2. Postupné zavedení – strangler fig pattern {#e2-strangler}

**Problém:** Přepis celé aplikace do DDD najednou zpravidla selže:
trvá déle, než se odhadovalo, tým ztrácí motivaci a byznys se nedočká nových funkcí.
Původní aplikace přitom musí dál žít. Proč big-bang rewrite končí špatně, rozebírá
[varování v kapitole Migrace z CRUD na DDD](/migrace-z-crud#big-bang-warning-heading).

**Řešení:** strangler fig pattern. Vyberte jeden modul s nejvyšší změnovou
frekvencí (highest-churn), nejčastějšími bugy nebo největší obchodní hodnotou
a implementujte v DDD právě ten. Zbytek aplikace zůstává beze změny. S novým kódem
komunikuje přes fasádu (ACL vzor) a feature flag umožňuje okamžitý rollback na legacy.
Po stabilizaci se postup opakuje s dalším modulem, dokud legacy nevyschne.

Kompletní postup popisuje kapitola [Migrace z CRUD na DDD](/migrace-z-crud): analýzu
domény, extrakci doménové vrstvy, charakterizační testy i odhady náročnosti.

### E3. Knowledge silos a bus factor {#e3-silos}

**Problém:** Doménový model je komplexní a po roce vývoje
mu dobře rozumí jen jeden člověk. Když onemocní, odejde nebo se přetíží,
tým stojí. Onboarding nového vývojáře trvá měsíce.
Bus factor = 1 je pro projekt kritické riziko.

**Opatření:** Proti nízkému bus factoru pomáhají dvě praktiky cílené přímo na sdílení vlastnictví:

1. **Párové programování nad doménovým modelem:** Změny v agregátech
   a doménových pravidlech procházejí ve dvojici. Znalost se přenáší průběžně,
   ne až při odchodu autora.

2. **Rotace vlastnictví modulů:** Žádný Bounded Context nemá trvale jen
   jednoho správce. Periodická rotace nutí tým rozumět více částem systému.

Ostatní nástroje jsou tytéž jako proti driftu Ubiquitous Language: doménový glosář
v repozitáři, ADR u netriviálních rozhodnutí, pravidelný Event Storming a živá
dokumentace přes testy (viz [Ubiquitous Language drift](#c4-language)).

:::faq{}
- question: Proč tradiční Doctrine mapování komplikuje čistý doménový model?
  answer: 'Doctrine hydratuje objekty reflexí a změny sleduje přes Unit of Work, zatímco DDD agregát chrání invarianty v konstruktoru a doménových metodách a hodnotové objekty jsou neměnné. Konflikt zahrnuje identifikaci přes generované ID (Doctrine) oproti identitě v doméně (DDD), problém „špinavého“ EntityManageru při dlouhých transakcích a omezení typů pro hodnotové objekty. Pragmatická výchozí volba je nechat atributy přímo na agregátu (jsou to metadata, ne chování) a používat Doctrine custom typy pro hodnotové objekty. Pokud chcete striktně oddělenou doménu, jděte cestou <a href="/implementace-v-symfony#persisted-object-pattern">Persisted Object Pattern</a>: samostatný persistence model a mapper. Detail v <a href="#doctrine">sekci Doctrine vs. doménový model</a>.'
- question: Jak řešit Outbox Pattern pro spolehlivé doručení doménových událostí?
  answer: 'Outbox ukládá doménové události do lokální tabulky ve stejné transakci jako změnu agregátu, čímž se zabrání ztrátě událostí při pádu mezi commitem a publikací. Samostatný proces (relay) pak outbox tabulku čte a publikuje události do message busu nebo externího systému. Kombinace s idempotentními konzumenty zajišťuje at-least-once doručení bez duplicit na straně zpracování. Shrnutí v <a href="#b1-outbox">sekci Outbox pattern</a>, plný výklad v kapitole <a href="/outbox-pattern">Outbox Pattern</a>.'
- question: Jak vysvětlit přínos DDD managementu, když první iterace zpomaluje?
  answer: 'Doporučený postup je přiznat krátkodobý náklad a explicitně vyčíslit dlouhodobý přínos: nižší počet regresních chyb, rychlejší onboarding, menší náklady na přidávání nových funkcí po překročení zlomu. Hodí se kombinovat s měřitelnými cíli (lead time, change failure rate) a s pilotním Bounded Contextem. Kdy přijdou první výsledky, závisí na velikosti kontextu a zkušenosti týmu; řádově jde o měsíce, ne o týdny, a slibovat konkrétní číslo dopředu se nevyplácí. Bez sponzorství na úrovni managementu investice do DDD zpravidla neprojde. Rozbor strategie komunikace v <a href="#e1-management">sekci Business case pro DDD refaktoring</a>.'
- question: Jak udržet Ubiquitous Language, aby časem neutrpěl drift?
  answer: 'Ubiquitous Language zaniká, když se kód a řeč doménových expertů začnou rozcházet: v kódu je „Invoice“, zákazník říká „faktura“. Prevence vyžaduje pravidelnou revizi kódu proti slovníku, ADR při jeho změně a glosář v repozitáři jako živý dokument. Drift se projeví, jakmile nová funkce zavádí pojem, který doménový expert nezná. Tehdy je namístě buď ustoupit, nebo jazyk společně upravit. Detailní rozbor v <a href="#c4-language">sekci Ubiquitous Language drift</a>.'
- question: Jak přežít paralelní existenci staré CRUD části a nové DDD vrstvy?
  answer: 'Strangler Fig pattern umožňuje oba stavy držet v jedné aplikaci: staré CRUD moduly zůstávají v provozu, nové funkce vznikají v DDD stylu a propojení řeší Anti-Corruption Layer. Výzvou je sdílená databáze, autentizace a uživatelský stav. Pragmatické řešení: migrovat postupně po jednotlivých Bounded Contextech a explicitně přijmout, že smíšený stav vydrží dlouho. U netriviálního systému jde řádově o roky, ne o jedno kvartální plánování. Viz <a href="#e2-strangler">sekci Postupné zavedení</a>.'
:::
