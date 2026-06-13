---
route: performance_aspects
path: /vykonnostni-aspekty
title: Read modely, projekce a výkon
page_title: "Read modely, projekce a výkon | DDD Symfony"
meta_description: "Read modely, projekce a výkon v DDD se Symfony a Doctrine: N+1 problém, hranice agregátů, projekce přes CQRS, snapshoty a cachování read modelů."
meta_keywords: "DDD výkon, Doctrine ORM optimalizace, N+1 problém, lazy loading, JOIN FETCH, DQL, CQRS read model, UUID ULID, Doctrine Identity Map, Unit of Work, batch zpracování, Symfony Cache, Blackfire profiling, agregát hranice"
og_type: article
published: "2025-04-24"
modified: "2026-06-13"
breadcrumb_name: Výkonnostní aspekty
schema_type: TechArticle
schema_headline: "Read modely, projekce a výkon"
chapter_number: "16"
category: Vzory
deck: "Read modely, projekce a výkon v Domain-Driven Design se Symfony a Doctrine ORM – řešení N+1 problému, hranice agregátů, budování projekcí přes CQRS, snapshoty a cache read modelů."
reading_time: 30
difficulty: 4
---

## 16.01 Výkon v kontextu DDD {#uvodem}

Pověst pomalého DDD se opírá o anekdoty místo měření. Výkonnostní problémy
přicházejí ze špatné implementace: příliš velkých agregátů, nevhodného lazy loadingu,
absence read modelu. Doménový model rychlou aplikaci nevylučuje.

:::callout{type="note"}
### DDD vs. výkon: mýty a realita

- **Mýtus:** DDD je vždy pomalejší než anémický model (anemic domain model). **Realita:** Správně navržené DDD s CQRS a optimalizovanými repozitáři je srovnatelně rychlé, protože read side nepotřebuje vůbec načítat doménové objekty.
- **Mýtus:** Agregáty způsobují zbytečné JOIN operace. **Realita:** Problém nastává při špatně definovaných hranicích agregátů – příliš velký agregát načítá zbytečná data.
- **Mýtus:** Doctrine ORM je pomalý pro DDD. **Realita:** Doctrine nabízí sadu nástrojů (DQL, native queries, extra lazy loading, query cache, result cache), které při správném použití odstraňují výkonnostní úzká místa.
:::

Výkon se stává kritickým ve třech scénářích: aplikace s **desítkami propojených agregátů**,
**velké agregáty** s kolekcemi tisíců položek a systémy s vysokou frekvencí čtení
a požadavky na odezvu v desítkách milisekund.

:::callout{type="warn"}
### Zlaté pravidlo optimalizace

**Nikdy neoptimalizujte naslepo.** Každá optimalizace musí být podložena měřením.
Předčasná optimalizace (premature optimization) vede k zbytečně složitému kódu, který řeší neexistující
problémy. Nejprve profilujte, identifikujte skutečné úzké místo a teprve potom optimalizujte.
Donald Knuth to vyjádřil takto: *„Premature optimization is the root of all evil.“*
[[1]](https://dl.acm.org/doi/10.1145/356635.356640)
:::

## 16.02 N+1 problém a lazy loading v Doctrine {#n-plus-1-problem}

N+1 je typický anti-vzor, který produkuje každý ORM bez explicitní fetch strategie. Aplikace provede
1 dotaz pro načtení seznamu entit a poté pro každou entitu další dotaz pro načtení asociovaných dat.
Celkem tedy N+1 SQL dotazů místo 1–2 dotazů.

:::callout{type="note"}
### Přesná definice N+1 problému

Pokud načteme N agregátů `Order` a každý agregát obsahuje kolekci `OrderItem`
mapovanou jako lazy asociace, Doctrine odloží načtení položek do okamžiku prvního přístupu.
Iterace přes všechny objednávky a přístup k jejich položkám způsobí N samostatných SELECT dotazů
nad tabulkou `order_item` – jeden pro každou objednávku.
:::

:::callout{type="pattern"}
### Příklad: kód způsobující N+1 problém

:::code{language="php" filename="snippet.php"}
<?php
// Tento kód způsobí N+1 problém!
// 1 dotaz: SELECT * FROM `order`
$orders = $this->orderRepository->findAll();

foreach ($orders as $order) {
    // Každá iterace způsobí 1 SELECT z order_item - celkem N dalších dotazů
    foreach ($order->getItems() as $item) {
        echo $item->getProductName() . ': ' . $item->getQuantity();
    }
}
:::
:::

Pro kolekce (OneToMany, ManyToMany) Doctrine ve výchozím stavu používá **lazy loading**:
kolekce zůstává neinicializovaná, dokud k ní kód poprvé nepřistoupí.
V situacích, kdy kolekci vůbec nepoužijeme, je to výhoda. Při iteraci přes mnoho agregátů
to ale plodí výše popsaný N+1 problém.

### Řešení 1: EXTRA_LAZY kolekce

Doctrine nabízí strategii `EXTRA_LAZY` pro kolekce. Na rozdíl od standardního lazy loadingu,
který načte celou kolekci při prvním přístupu, EXTRA_LAZY umožňuje provádět operace jako
`count()`, `contains()` nebo `slice()` přímými SQL dotazy
bez načtení celé kolekce do paměti.

:::callout{type="pattern"}
### Konfigurace EXTRA_LAZY v PHP atributech (Doctrine)

*Atributy `#[ORM\Entity]` přímo na agregátu jsou v tomto průvodci výchozí volba (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)). Pro čistou DDD variantu existuje [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern) – samostatný persistence model a mapper.*

:::code{language="php" filename="src/Order/Domain/Model/Order.php"}
<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class Order // ne final – Doctrine proxy z entity dědí
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private readonly string $id;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(
        targetEntity: OrderItem::class,
        mappedBy: 'order',
        fetch: 'EXTRA_LAZY',
        cascade: ['persist', 'remove']
    )]
    private Collection $items;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->items = new ArrayCollection();
    }

    public function countItems(): int
    {
        // S EXTRA_LAZY provede SELECT COUNT(*) - bez načtení všech položek
        return $this->items->count();
    }
}
:::
:::

### Řešení 2: JOIN FETCH v DQL pro eager loading

Pokud víme předem, že budeme iterovat přes kolekce, je efektivnější použít DQL s klauzulí
`JOIN FETCH`. Doctrine pak načte agregát včetně asociovaných objektů v jediném SQL
dotazu s LEFT JOIN nebo INNER JOIN.

:::callout{type="pattern"}
### Příklad: JOIN FETCH v DQL a Query Builderu

:::code{language="php" filename="src/Order/Infrastructure/Repository/DoctrineOrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Repository;

use App\Order\Domain\Model\Order;
use App\Order\Domain\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Načte objednávky včetně položek v jediném SQL dotazu (JOIN FETCH).
     * Vhodné pro iteraci a export - eliminuje N+1 problém.
     *
     * @return Order[]
     */
    public function findAllWithItems(): array
    {
        // DQL s JOIN FETCH - Doctrine provede LEFT JOIN a hydratuje kolekci
        return $this->em->createQuery(
            'SELECT o, i
             FROM App\Order\Domain\Model\Order o
             JOIN FETCH o.items i
             WHERE o.status = :status'
        )
            ->setParameter('status', 'confirmed')
            ->getResult();
    }

    /**
     * Alternativa přes Query Builder s addSelect()
     *
     * @return Order[]
     */
    public function findRecentWithItemsAndProduct(): array
    {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->addSelect('i')          // eager load položek
            ->addSelect('p')          // eager load produktů přes položky
            ->from(Order::class, 'o')
            ->leftJoin('o.items', 'i')
            ->leftJoin('i.product', 'p')
            ->where('o.createdAt > :since')
            ->setParameter('since', new \DateTimeImmutable('-30 days'))
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
:::
:::

Při použití `JOIN FETCH` s paginací (`setMaxResults()`, `setFirstResult()`)
Doctrine vypíše varování a provede paginaci v paměti (in-memory pagination), ne na úrovni SQL.
Řešením je stránkovat přes identifikátory a teprve pak načíst data, nebo použít nativní SQL
s vlastním mapováním výsledků.

## 16.03 Agregát a výkon: správné určení hranic {#agregat-hranice}

Agregát drží konzistenční hranici: invarianty platí uvnitř jednoho agregátu.
Pokud hranici nakreslíte příliš široce, agregát při každém načtení tahá z databáze
rozsáhlý objektový graf, i když potřebujete jen malou část dat.

:::callout{type="note"}
### Příznak příliš velkého agregátu

- Načtení agregátu trvá neúměrně dlouho, i když používáme jen jeho kořen.
- Kolekce asociovaných entit obsahují stovky nebo tisíce záznamů.
- ORM lazy loading způsobuje N+1 v jiných částech systému.
- Různé use-case scénáře potřebují různé podmnožiny dat agregátu.
:::

:::callout{type="pattern"}
### Příklad: problematický Order agregát s 1000 OrderItems

:::code{language="php" filename="snippet.php"}
<?php
// PROBLÉM: Každé načtení Order způsobí SELECT s 1000 řádky z order_item,
// i když chceme jen zobrazit hlavičku objednávky (číslo, datum, zákazník).

$order = $this->orderRepository->findById($orderId);

// Pouze toto potřebujeme - ale agregát načetl 1000 položek zbytečně
echo $order->getOrderNumber();
echo $order->getCreatedAt()->format('d.m.Y');
echo $order->getCustomer()->getFullName();
:::
:::

### Řešení: rozdělení agregátu a specializované repozitářní metody

Prvním krokem je kriticky přezkoumat, zda `OrderItem` skutečně musí být součástí
agregátu `Order`, nebo zda jde o samostatný agregát s odkazem na `OrderId`.
V e-commerce doméně bývá správné mít `Order` jako kořen agregátu s přímým přístupem
pouze k metadatům (číslo, datum, stav, celková cena). `OrderItem` pak tvoří samostatný
agregát odkazující na `OrderId`.

:::callout{type="pattern"}
### Příklad: specializované repozitářní metody pro různé kontexty

:::code{language="php" filename="src/Order/Infrastructure/Repository/DoctrineOrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Repository;

use App\Order\Domain\Model\Order;
use App\Order\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Načte pouze hlavičku objednávky (bez položek) - pro seznam objednávek.
     * Doctrine neinicializuje kolekci items díky lazy loadingu.
     */
    public function findHeaderById(OrderId $id): ?Order
    {
        // Tato metoda vrátí Order, jehož kolekce items zůstane neinicializovaná,
        // dokud k ní explicitně nepřistoupíme.
        return $this->em->find(Order::class, $id->value);
    }

    /**
     * Načte objednávku s položkami - pouze pro detailní zobrazení nebo zpracování.
     */
    public function findWithItemsById(OrderId $id): ?Order
    {
        return $this->em->createQuery(
            'SELECT o, i FROM App\Order\Domain\Model\Order o
             JOIN FETCH o.items i
             WHERE o.id = :id'
        )
            ->setParameter('id', $id->value)
            ->getOneOrNullResult();
    }
}
:::
:::

Pravidlo zní: **hranice agregátu vede přes doménové invarianty**, výkonnostní
požadavky se řeší jinde. Když výkon tlačí proti doménovému modelu, odpovědí je read model
(viz sekci CQRS), ne porušení doménové integrity.

## 16.04 Optimalizace read modelu (CQRS) {#read-model-optimalizace}

Oddělení write side (operace přes agregáty) od read side (dotazy do prezentace) je hlavní
páka pro výkonnostní problémy v DDD. Read side doménové objekty nepotřebuje – vrací rovnou
strukturu dat pro UI nebo API klienta.

:::callout{type="note"}
### Zásady read modelu v CQRS

- Query handlery **nepoužívají doménové repozitáře** – přistupují přímo k databázi přes DQL nebo nativní SQL.
- Výsledkem je **DTO (Data Transfer Object)** nebo plain PHP array – žádné doménové objekty.
- Read model může být **denormalizovaný** – data jsou již předpřipravena pro konkrétní view.
- Read side lze **nezávisle cachovat** bez ohrožení doménové konzistence.
:::

:::callout{type="pattern"}
### Příklad: QueryHandler vracející DTO přes DQL

:::code{language="php" filename="src/Order/Application/Query/OrderSummaryDTO.php"}
<?php

declare(strict_types=1);

namespace App\Order\Application\Query;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class OrderSummaryDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $orderNumber,
        public readonly string $customerName,
        public readonly string $status,
        public readonly int    $itemCount,
        public readonly string $totalAmount,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}

#[AsMessageHandler]
final class GetOrderSummaryListHandler
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * @return OrderSummaryDTO[]
     */
    public function __invoke(GetOrderSummaryList $query): array
    {
        // DQL NEW expression - Doctrine hydratuje přímo do DTO
        // bez vytváření spravovaných doménových entit
        $dtos = $this->em->createQuery(
            'SELECT NEW App\Order\Application\Query\OrderSummaryDTO(
                o.id,
                o.orderNumber,
                CONCAT(c.firstName, \' \', c.lastName),
                o.status,
                COUNT(i.id),
                CONCAT(o.totalAmount.amount, \' \', o.totalAmount.currency),
                o.createdAt
             )
             FROM App\Order\Domain\Model\Order o
             JOIN o.customer c
             LEFT JOIN o.items i
             WHERE o.status IN (:statuses)
             GROUP BY o.id, o.orderNumber, c.firstName, c.lastName,
                      o.status, o.totalAmount.amount, o.totalAmount.currency, o.createdAt
             ORDER BY o.createdAt DESC'
        )
            ->setParameter('statuses', $query->statuses)
            ->setMaxResults($query->limit)
            ->setFirstResult($query->offset)
            ->getResult();

        return $dtos;
    }
}
:::
:::

### Doctrine NativeQuery pro komplexní reportovací dotazy

DQL pokrývá většinu dotazů, ale pro složité reportovací dotazy (agregace, window funkce, CTE)
nestačí. Doctrine umožňuje spouštět nativní SQL dotazy s vlastním mapováním
výsledků přes `ResultSetMapping`.

:::callout{type="pattern"}
### Příklad: NativeQuery s ResultSetMapping pro reportovací dotaz

:::code{language="php" filename="src/Reporting/Infrastructure/Query/SalesReportQueryService.php"}
<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

final class SalesReportQueryService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Vrací měsíční obrat po zákaznících - komplexní agregace přes nativní SQL.
     * Syntaxe: PostgreSQL (TO_CHAR, ::text cast). Pro MySQL použijte DATE_FORMAT() a CAST().
     *
     * @return array<int, array{customer_id: string, customer_name: string, month: string, revenue: string}>
     */
    public function getMonthlySalesByCustomer(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        // Mapujeme scalar výsledky (ne entity) - žádný overhead doménových objektů
        $rsm->addScalarResult('customer_id',   'customer_id',   'string');
        $rsm->addScalarResult('customer_name', 'customer_name', 'string');
        $rsm->addScalarResult('month',         'month',         'string');
        $rsm->addScalarResult('revenue',       'revenue',       'string');

        $sql = "
            SELECT
                c.id                                          AS customer_id,
                CONCAT(c.first_name, ' ', c.last_name)        AS customer_name,
                TO_CHAR(o.created_at, 'YYYY-MM')              AS month,
                SUM(oi.unit_price_amount * oi.quantity)::text AS revenue
            FROM \"order\" o
            JOIN customer c  ON c.id = o.customer_id
            JOIN order_item oi ON oi.order_id = o.id
            WHERE o.status = 'completed'
              AND o.created_at BETWEEN :from AND :to
            GROUP BY c.id, c.first_name, c.last_name, TO_CHAR(o.created_at, 'YYYY-MM')
            ORDER BY month DESC, revenue DESC
        ";

        return $this->em
            ->createNativeQuery($sql, $rsm)
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to',   $to->format('Y-m-d'))
            ->getScalarResult();
    }
}
:::
:::

## 16.05 UUID vs. integer primární klíče {#uuid-vs-integer}

Agregát musí znát svoji identitu už před uložením do databáze. `AggregateId` se generuje
v doménovém kódu bez databázové sekvence nebo auto-increment hodnoty. Pro distribuované
systémy, event sourcing a paralelní vytváření agregátů to není volba, ale podmínka.

:::callout{type="note"}
### Výhody UUID pro DDD

- Identita je generována v doméně – agregát je kompletní před persistencí.
- Vhodné pro distribuované systémy – žádné centrální generování ID.
- UUID lze bezpečně přenášet do API bez rizika enumeration útoků (na rozdíl od sekvenčních integerů).
- Event sourcing: událost nese ID agregátu, který ještě neexistuje v databázi.

### Výkonnostní dopady UUID

- **Index fragmentace:** UUID v4 jsou náhodné – nové záznamy jsou vkládány na náhodné pozice v B-tree indexu, což způsobuje fragmentaci a zpomalení INSERT operací.
- **Větší velikost:** UUID zabírá 16 bajtů (binárně) nebo 36 znaků (textově) oproti 4–8 bajtům pro integer – větší index, více I/O operací.
- **Problém s cizími klíči:** Každý FK odkazující na UUID agregát nese 16 bajtů místo 4.
:::

### ULID jako kompromis

ULID (Universally Unique Lexicographically Sortable Identifier) a UUID verze 6/7 (ordered UUID)
řeší problém fragmentace indexů tím, že jsou **monotónně rostoucí**. Nové hodnoty
jsou vždy větší než předchozí a vkládají se na konec B-tree indexu. Chování je stejné
jako u auto-increment integeru, ale se zachováním globální unikátnosti bez centrálního generátoru.

:::callout{type="pattern"}
### Příklad: Použití symfony/uid (ULID a UUID v7)

:::code{language="php" filename="src/Shared/Domain/ValueObject/OrderId.php"}
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Hodnotový objekt pro identitu objednávky - používá ULID pro výkon.
 * ULID je lexikograficky řaditelný a monotónně rostoucí - přátelský k B-tree indexům.
 */
final class OrderId
{
    private function __construct(
        public readonly string $value
    ) {}

    public static function generate(): self
    {
        return new self((string) new Ulid());
    }

    public static function fromString(string $value): self
    {
        if (!Ulid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid ULID.', $value)
            );
        }
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

// Pro UUID v7 (ordered) - alternativa k ULID
final class UserId
{
    private function __construct(
        public readonly string $value
    ) {}

    public static function generate(): self
    {
        // UUID v7 - time-based, monotónně rostoucí, RFC 9562 kompatibilní
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
:::
:::

:::callout{type="pattern"}
### Doctrine mapování pro ULID a UUID

*Atributy `#[ORM\Entity]` přímo na agregátu jsou v tomto průvodci výchozí volba (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)). Pro čistou DDD variantu existuje [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern) – samostatný persistence model a mapper.*

:::code{language="php" filename="src/Order/Domain/Model/Order.php"}
<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Shared\Domain\ValueObject\OrderId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class Order // ne final – Doctrine proxy z entity dědí
{
    #[ORM\Id]
    // Symfony Bridge registruje 'ulid' typ - ukládá jako BINARY(16) nebo UUID v PostgreSQL.
    // Při načtení z DB typ hydratuje objekt Ulid, property proto musí mít typ Ulid.
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private readonly Ulid $id;

    #[ORM\Column(type: 'string', length: 50)]
    private readonly string $orderNumber;

    public function __construct(OrderId $id, string $orderNumber)
    {
        $this->id          = Ulid::fromString($id->value);
        $this->orderNumber = $orderNumber;
    }

    public function id(): OrderId
    {
        return OrderId::fromString((string) $this->id);
    }
}
:::
:::

## 16.06 Doctrine Identity Map a Unit of Work {#doctrine-identity-map}

Doctrine ORM implementuje vzor Identity Map (Martin Fowler, *Patterns of Enterprise Application Architecture*).
Každý spravovaný objekt (managed entity) je v jednom `EntityManager`u uložen v paměti pod svým
identifikátorem. Pokud načtete tentýž agregát dvakrát, Doctrine vrátí tentýž PHP objekt z paměti
bez opakovaného SQL dotazu.

:::callout{type="note"}
### Identity Map a Unit of Work – co to znamená pro DDD

- **Konzistence v requestu:** Všechny části kódu vidí tentýž stav agregátu – žádné nekonzistentní kopie.
- **Jedno místo změn:** Změny agregátu jsou sledovány Unit of Work a při `flush()` jsou synchronizovány do databáze. Není třeba explicitně volat `save()` pro každou změnu.
- **Automatická detekce změn (dirty checking):** Doctrine porovnává aktuální stav entit s jejich původním stavem (snapshot) a generuje UPDATE pouze pro skutečně změněné atributy.
:::

### Problém s batch zpracováním

Identity Map počítá s typickým web requestem: jednotky až desítky agregátů.
Hromadné zpracování (import, migrace, reporty) sype do Identity Map tisíce objektů,
které tam zůstávají po celou dobu běhu. Spotřeba paměti roste (*memory leak*)
a dirty checking se zpomaluje, protože Doctrine musí procházet stále větší množinu
spravovaných objektů.

:::callout{type="pattern"}
### Příklad: správné clearování Entity Manageru při batch operacích

:::code{language="php" filename="src/Import/Application/Command/ImportProductsHandler.php"}
<?php

declare(strict_types=1);

namespace App\Import\Application\Command;

use App\Product\Domain\Model\Product;
use App\Product\Domain\ValueObject\ProductId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportProductsHandler
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function __invoke(ImportProducts $command): void
    {
        $i = 0;

        foreach ($command->productRows as $row) {
            $product = new Product(
                ProductId::generate(),
                $row['name'],
                $row['sku'],
                $row['price']
            );

            // persist přidá objekt do Identity Map, ale SQL zatím nevydá
            $this->em->persist($product);

            if (++$i % self::BATCH_SIZE === 0) {
                // flush() vydá batch INSERT/UPDATE do databáze
                $this->em->flush();
                // clear() uvolní Identity Map - PHP GC může objekty uvolnit z paměti
                // POZOR: po clear() jsou dříve spravované objekty odpojeny (detached)
                $this->em->clear();
            }
        }

        // Zpracování zbývajících záznamů po posledním batch
        $this->em->flush();
        $this->em->clear();
    }
}
:::
:::

:::callout{type="warn"}
### Pozor na clear() a detached entity

Po zavolání `$this->em->clear()` jsou **všechny** spravované entity odpojeny
(stav *detached*). Jakýkoli pokus o přístup k jejich lazy-loaded asociacím vyvolá výjimku
`LazyInitializationException`. Ujistěte se, že po `clear()` nepracujete
s referencemi na dříve spravované objekty.
:::

## 16.07 Caching v DDD architektuře {#cachovani}

Caching v DDD má jednu vstupní otázku: **co cachovat**? Pravidlo: cache patří na výsledky,
které jsou výpočetně nebo I/O nákladné a v čase se nemění (nebo se mění předvídatelně).
Doménová logika do cache klíče nepatří – cache slouží infrastruktuře, ne doménovým rozhodnutím.

:::callout{type="note"}
### Co cachovat a co ne

- **Vhodné pro cache:** výsledky read modelu (DTO), výsledky reportovacích dotazů, výsledky volání externích API, výpočetně náročné projekce.
- **Nevhodné pro cache:** aktuální stav agregátů, které jsou právě modifikovány (způsobí dirty reads), výsledky, jejichž neaktuálnost by způsobila doménové nekonzistence.
- **Nikdy:** nezahrnujte výsledek doménové logiky do cache klíče (např. nevypočítávejte slevu při sestavování cache klíče).
:::

### Query cache a result cache v Doctrine

Doctrine nabízí dvě úrovně cachování SQL dotazů:

- **Query cache:** cachuje přeložený DQL → SQL. DQL parsing je relativně nákladný; query cache eliminuje opakované parsování pro identické DQL dotazy. Výsledky se nemění.
- **Result cache:** cachuje výsledky SQL dotazu. Musí být explicitně nakonfigurován a invalidován při změnách dat. Vhodný pro read-heavy dotazy s řízenou dobou platnosti.

:::callout{type="pattern"}
### Příklad: cache read modelu v query handleru

:::code{language="php" filename="src/UserManagement/Application/Query/GetUserProfileHandler.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Query;

use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Read model profilu - immutabilní DTO se skalárními hodnotami.
 * Bezpečně serializovatelný do PSR-6 cache.
 */
final readonly class UserProfileView
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public int    $orderCount,
    ) {}
}

#[AsMessageHandler]
final class GetUserProfileHandler
{
    private const TTL = 300; // 5 minut

    public function __construct(
        private Connection             $connection,
        private CacheItemPoolInterface $cache,
    ) {}

    public function __invoke(GetUserProfile $query): ?UserProfileView
    {
        $item = $this->cache->getItem('user_profile_' . $query->userId);

        if ($item->isHit()) {
            return $item->get();
        }

        $row = $this->connection->fetchAssociative(
            'SELECT u.id, u.name, u.email, COUNT(o.id) AS order_count
               FROM users u
          LEFT JOIN orders o ON o.customer_id = u.id
              WHERE u.id = :id
           GROUP BY u.id',
            ['id' => $query->userId],
        );

        $view = $row
            ? new UserProfileView(
                userId: $row['id'],
                name: $row['name'],
                email: $row['email'],
                orderCount: (int) $row['order_count'],
            )
            : null;

        $item->set($view)->expiresAfter(self::TTL);
        $this->cache->save($item);

        return $view;
    }
}
:::
:::

Cache drží hotový ViewModel, ne doménový agregát. Serializace agregátu do PSR-6
je křehká: po deserializaci vznikne objekt odpojený od Unit of Work (detached),
lazy proxy asociací přestanou fungovat a obejde se Identity Map. DTO se skalárními
hodnotami tyto problémy nemá – přesně podle zásady z calloutu výše: do cache patří
výsledky read modelu, ne stav agregátů.

### Cache invalidace při doménových událostech

Cache se v DDD nejlépe invaliduje nasloucháním doménovým událostem. Když agregát
změní stav (publikuje doménovou událost), Event Listener invaliduje příslušné cache záznamy.
Cache invalidace se tím stává součástí doménového toku, nikoli ad-hoc voláním rozptýleným po kódu.

:::callout{type="pattern"}
### Cache invalidace přes Symfony EventDispatcher

:::code{language="php" filename="src/UserManagement/Infrastructure/EventListener/InvalidateUserCacheOnEmailChanged.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\EventListener;

use App\UserManagement\Domain\Event\UserEmailChanged;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UserEmailChanged::class)]
final class InvalidateUserCacheOnEmailChanged
{
    public function __construct(
        private CacheItemPoolInterface $cache
    ) {}

    public function __invoke(UserEmailChanged $event): void
    {
        // Invalidace cachovaného read modelu - klíč z příkladu výše
        $this->cache->deleteItem('user_profile_' . $event->userId->value);
    }
}
:::
:::

## 16.08 Bulk operace a hromadné zpracování {#bulk-operace}

Standardní DDD postup – načti agregát, aplikuj doménovou logiku, zavolej `flush()`
– funguje pro zpracování jednotlivých agregátů. Pro hromadné operace (import tisíců záznamů,
hromadná aktualizace stavů, migrace dat) je tento přístup neefektivní. Každý cyklus načítá
a spravuje jeden agregát, dirty checking zpracovává celou Identity Map. Celkový čas zpracování
roste lineárně s počtem záznamů.

### DQL bulk UPDATE a DELETE – bypass Identity Map

Pro hromadné aktualizace, kde není potřeba procházet doménovou logiku, nabízí Doctrine možnost
provést `UPDATE` nebo `DELETE` přímo přes DQL. Tyto operace obcházejí
Identity Map a Unit of Work – jsou to přímé SQL příkazy přeložené z DQL. **Nevýhoda:**
po DQL bulk operaci mohou být spravované entity v Identity Map nekonzistentní se stavem v databázi.
Je nutné zavolat `clear()`.

:::callout{type="pattern"}
### Příklad: efektivní hromadný import s Doctrine

:::code{language="php" filename="src/Order/Infrastructure/Command/BulkUpdateOrderStatusHandler.php"}
<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class BulkUpdateOrderStatusHandler
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Hromadná změna stavu objednávek přes DQL UPDATE - jeden SQL příkaz.
     * Nevyužívá doménovou logiku agregátu - vhodné jen pro migrační/admin operace.
     */
    public function __invoke(BulkUpdateOrderStatus $command): int
    {
        $affectedRows = $this->em->createQuery(
            'UPDATE App\Order\Domain\Model\Order o
             SET o.status = :newStatus
             WHERE o.status = :oldStatus
               AND o.createdAt < :before'
        )
            ->setParameter('newStatus', $command->newStatus)
            ->setParameter('oldStatus', $command->oldStatus)
            ->setParameter('before', $command->before)
            ->execute();

        // Po DQL UPDATE je Identity Map zastaralá - musíme ji vyčistit
        $this->em->clear();

        return $affectedRows;
    }
}

// ---
// Příklad: batch INSERT přes persist/flush s clear() po každém batch
final class BatchProductImportHandler
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function __invoke(BatchImportProducts $command): void
    {
        $counter = 0;
        // Poznámka: Doctrine DBAL 3+ odstranil setSQLLogger() - debug logging
        // se vypíná konfigurací (doctrine.dbal.logging: false) nebo odebráním
        // logovacího middleware z services.yaml, ne programaticky.

        foreach ($command->rows as $row) {
            $product = Product::create(
                ProductId::generate(),
                $row['name'],
                Money::of($row['price'], $row['currency'])
            );

            $this->em->persist($product);

            if (++$counter % self::BATCH_SIZE === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();
    }
}
:::
:::

### Symfony Messenger pro asynchronní hromadné zpracování

Tisíce záznamů se v jednom PHP procesu synchronně nezpracovávají. Práci je vhodné rozdělit
na menší úlohy zasílané přes Symfony Messenger na asynchronní transport
(RabbitMQ, Redis Streams, Amazon SQS). Každá zpráva zpracuje jeden nebo malý batch agregátů.
Paměťové nároky a doba zpracování jedné zprávy jsou pak předvídatelné.

:::callout{type="pattern"}
### Rozložení bulk importu přes Symfony Messenger

:::code{language="php" filename="src/Import/Application/Command/StartProductImportHandler.php"}
<?php

declare(strict_types=1);

namespace App\Import\Application\Command;

use Symfony\Component\Messenger\MessageBusInterface;

// 1. Controller nebo CLI příkaz rozdělí vstupní data na chunky
final class StartProductImportHandler
{
    private const CHUNK_SIZE = 100;

    public function __construct(
        private MessageBusInterface $commandBus
    ) {}

    public function __invoke(StartProductImport $command): void
    {
        // Každých 100 řádků odešleme jako samostatnou zprávu
        foreach (array_chunk($command->rows, self::CHUNK_SIZE) as $chunk) {
            $this->commandBus->dispatch(new ImportProductChunk($chunk));
        }
        // Messenger Worker zpracuje každou zprávu nezávisle
        // - žádný memory leak, paralelizovatelné přes více workerů
    }
}
:::
:::

## 16.09 Provozní výkonové vzory {#provozni-vzory}

Předchozí sekce řeší výkon na úrovni jednoho dotazu nebo jednoho agregátu. Jakmile
aplikace běží 24/7 s reálnou zátěží, narážíte na třídu problémů, které lokální profiling
neukáže: souběžnost více klientů, omezení databáze jako sdíleného zdroje a operační
omezení Doctrine ve více procesech.

### Hot aggregates a optimistic lock thrash {#hot-aggregates-heading}

**Hot aggregate** je agregát, který je modifikován mnoha klienty současně. Klasické
příklady: globální `Inventory` jednoho produktu při rozjezdu kampaně, `Tournament`
agregát s 1000 účastníky, kteří všichni paralelně potvrdí účast, nebo `BankAccount`
firmy s tisíci transakcí denně.

:::diagram{fig="16.9-A" title="Optimistic lock thrash: 3 souběžné modifikace, 2 retry" src="images/diagrams/17_performance/hot_aggregate_thrash.svg"}
:::

S `#[ORM\Version]` (optimistický zámek) souběžná modifikace vyhází
`OptimisticLockException`. Při nízké souběžnosti (5 % konfliktů) je retry levný.
Při hot aggregate (50–80 % konfliktů) systém **degraduje na sériový provoz**:
worker dělá retry → load → modify → save → conflict → retry. Throughput klesne
o řád, latence stoupne.

Tři strategie, podle pořadí preference:

- **Re-design hranic agregátu.** Pokud je `Inventory` hot, není to často
  jeden agregát, ale **N samostatných agregátů per warehouse + sklad pool**.
  Jeden agregát na region/sku/sklad. Konflikty pak nejsou „mezi všemi klienty“,
  ale „mezi klienty stejné lokace“.
- **Eventual consistency místo strong.** Místo „strhni 1 ks z `Inventory` synchronně“
  publikuj `ItemReserved(productId, qty)` event a agregát ho zpracuje
  asynchronně přes saga. Konflikty řeší sága přes kompenzaci, ne optimistic lock.
- **CRDT / counter-only agregáty.** Pokud doménová operace je čistý increment
  (`view_count`, `like_count`), nepotřebujete celý agregát – stačí
  Postgres `UPDATE counters SET n = n + 1 WHERE id = ?`. To není „obvykle DDD“,
  ale je to validní u skutečně commutative operací.

:::callout{type="warn"}
### Anti-vzor: pessimistic lock místo redesignu {#anti-pessimistic-lock-heading}

Když optimistic lock generuje konflikty, lákavé řešení je
`#[ORM\Lock(LockMode::PESSIMISTIC_WRITE)]` – databáze drží `SELECT FOR UPDATE`
zámek a další klient čeká. Konflikty zmizí, ale výsledek je horší: klienti se
serializují na úrovni databáze místo aplikace, zámky drží přes celou transakci
(včetně síťové komunikace s app serverem), pravděpodobnost deadlocku roste.
Pessimistic lock zakryje příznak, ne příčinu. Pokud je agregát hot,
**hranice je špatně**.
:::

### Partitioning velkých tabulek {#partitioning-heading}

PostgreSQL declarative partitioning je standardní řešení pro tabulky s 50M+ řádky,
kde aktivně se mění jen poslední část (typicky podle `created_at`):

- **`orders` partitioned po měsících** – aktivní partition za poslední měsíc
  drží 1M řádků, vlézá do RAM, indexy malé. Staré partitions (read-only) můžou
  být na pomalejším disku nebo v archivu.
- **`audit_log` partitioned po dnech** – `DROP PARTITION` po retention period
  je atomický a nezamyká aktivní tabulku.
- **`projection_*` tabulky** s vysokým write rate.

Pro DDD má partitioning jeden důsledek navíc: **agregátní reference přes ID
musí být kompozitní** (id + partition key, např. `created_at`). Pokud doména
zná jen `OrderId`, partition lookup vyžaduje plný scan napříč partitions
(slow). Standardní řešení: zahrnout `created_at` (nebo derivovaný měsíc)
do hodnotového objektu `OrderId`, aby ho repozitář uměl použít pro partition pruning.

:::callout{type="note"}
### Kdy partition použít {#partitioning-kdy-heading}

- Tabulka roste lineárně s časem (audit, outbox, orders, eventy).
- 90 % dotazů se týká poslední X dní/měsíců.
- Drop staré dat je vyžadovaný (compliance, GDPR, retention).
- Velikost tabulky překročí 50 mil. řádků nebo 50 GB.

**Nepoužívejte** pro malé tabulky (< 10 mil.) – přidává operační složitost
bez měřitelného přínosu.
:::

### Read replicy a connection pooling {#replicy-pooling-heading}

V CQRS architektuře bývají read modely vhodný kandidát pro **read replicy** –
samostatná databáze (nebo Postgres streaming replica), na kterou jdou všechny
queries, zatímco write model zůstává na primary. Důsledky pro DDD kód:

:::diagram{fig="16.9-B" title="Routing: write na primary, read na replicu, replikační lag" src="images/diagrams/17_performance/read_replica_routing.svg"}
:::

- **Repozitář write strany** drží `EntityManagerInterface` namapovaný na primary.
- **Query handler read strany** drží separátní `Connection` nebo
  `EntityManager` namapovaný na replicu (`doctrine.orm.read_entity_manager`).
- **Replikační lag** (typicky 10–100 ms) znamená, že po `save()` na primary
  query na replicu nemusí ihned vidět změnu – stejný „read your writes“
  problém jako u eventual consistency. Vzor řešení viz
  [CQRS – eventual consistency v UI](/cqrs#eventual-consistency).

Connection pooling je ortogonální problém. PHP-FPM model „1 worker = 1 PHP proces
= 1 DB connection“ se nasčítá: 100 PHP-FPM workerů × 4 DB pody × 10 read replicas
= 4000 connections, což překročí výchozí `max_connections = 100` v Postgresu.
Standardní řešení: **PgBouncer / RDS Proxy** mezi aplikací a DB, transaction
pooling mode. Pozor: transaction pooling **nepodporuje prepared statements**
(Doctrine používá), takže potřebujete buď session pooling (méně efektivní),
nebo PgBouncer ve verzi 1.21+ s `prepared_statements = true`.

### Snapshotting v Event Sourcingu (přehled) {#snapshotting-prehled-heading}

Při Event Sourcingu (kapitola [Event Sourcing](/event-sourcing)) je rebuild stavu
agregátu lineární s počtem eventů. Pro agregát s 100 eventy je to instant; pro
1000 eventů to začíná být znát; pro 100k+ eventů (long-lived agregát jako
`UserAccount` po letech provozu) je hydration nepoužitelná.

**Snapshot** je zhuštěný stav agregátu uložený periodicky:

- Po každých N eventech (typicky 50–100) se uloží `Snapshot{aggregateId, version, state}`.
- Při hydration se načte poslední snapshot + jen eventy *novější* než snapshot version.
- Tradeoff: rychlejší read, ale snapshot tabulka roste a její struktura je vázaná
  na konkrétní verzi agregátu (schema evolution problém – viz
  [Event Sourcing – verzování](/event-sourcing#verzovani-udalosti)).

Detailní implementace včetně Symfony kódu je v sekci
[Event Sourcing – Snapshotting](/event-sourcing#snapshotting). V kontextu výkonu
si pamatujte: **snapshot není výchozí volba, ale úniková páka pro dlouho žijící
agregáty**. Většina DDD agregátů má desítky eventů za celý životní cyklus a snapshotting
nepotřebuje.

## 16.10 Profiling DDD aplikací {#profiling}

Úzké místo nepoznáte bez měření. Pro PHP/Symfony jsou v ruce tři vrstvy nástrojů:
vývojový profiler, produkční profiling a programatický logger SQL dotazů.

### Symfony Profiler (Web Debug Toolbar)

Ve vývojovém prostředí odhaluje N+1 a pomalé dotazy nejdřív Symfony Profiler
(aktivní při `APP_ENV=dev`). Panel **Doctrine** zobrazuje:

- Celkový počet SQL dotazů za request – nadměrný počet dotazů signalizuje N+1 problém.
- Dobu trvání každého dotazu – pomalé dotazy vyžadují indexování nebo přepis.
- Kompletní SQL s parametry – umožňuje přímé testování v databázovém klientovi.
- Stack trace pro každý dotaz – identifikuje, která část kódu dotaz vydala.

### Doctrine query logging

Pro programatické zachycení SQL dotazů (např. v integračních testech nebo při ladění batch operací)
lze Doctrine konfigurovat s vlastním SQL loggerem.

:::callout{type="pattern"}
### Programatické zachycení SQL dotazů přes Doctrine Middleware

:::code{language="php" filename="src/Shared/Infrastructure/Doctrine/QueryCountingMiddleware.php"}
<?php
// V Doctrine DBAL 3+ se logging provádí přes Middleware (ne SQLLogger)
// config/packages/doctrine.yaml

// doctrine:
//   dbal:
//     logging: true   # aktivuje vestavěný logger v dev prostředí

// Pro vlastní middleware:
namespace App\Shared\Infrastructure\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

final class QueryCountingMiddleware implements Middleware
{
    private int $queryCount = 0;

    public function wrap(Driver $driver): Driver
    {
        $middleware = $this;

        return new class($driver, $middleware) extends \Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware {
            public function __construct(
                Driver $wrappedDriver,
                private QueryCountingMiddleware $middleware
            ) {
                parent::__construct($wrappedDriver);
            }

            public function connect(array $params): \Doctrine\DBAL\Driver\Connection
            {
                return new class(parent::connect($params), $this->middleware)
                    implements \Doctrine\DBAL\Driver\Connection
                {
                    public function __construct(
                        private \Doctrine\DBAL\Driver\Connection $inner,
                        private QueryCountingMiddleware $middleware
                    ) {}

                    public function prepare(string $sql): \Doctrine\DBAL\Driver\Statement
                    {
                        $this->middleware->increment();
                        return $this->inner->prepare($sql);
                    }

                    public function query(string $sql): \Doctrine\DBAL\Driver\Result
                    {
                        $this->middleware->increment();
                        return $this->inner->query($sql);
                    }

                    public function exec(string $sql): int|string
                    {
                        $this->middleware->increment();
                        return $this->inner->exec($sql);
                    }

                    // Zbývající metody delegují na $this->inner
                    public function lastInsertId(): int|string { return $this->inner->lastInsertId(); }
                    public function beginTransaction(): void { $this->inner->beginTransaction(); }
                    public function commit(): void { $this->inner->commit(); }
                    public function rollBack(): void { $this->inner->rollBack(); }
                    public function getNativeConnection(): mixed { return $this->inner->getNativeConnection(); }
                    public function getServerVersion(): string { return $this->inner->getServerVersion(); }
                };
            }
        };
    }

    public function increment(): void
    {
        $this->queryCount++;
    }

    public function reset(): void
    {
        $this->queryCount = 0;
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }
}
:::
:::

### Blackfire.io pro produkční profiling

Pro profiling v produkčním nebo stagingovém prostředí se v PHP používá Blackfire.io.
Blackfire zachytí kompletní call graph každého requestu nebo CLI příkazu – s přesným měřením
doby trvání, počtu volání a paměťové stopy pro každou funkci. Umožňuje psát *výkonnostní testy*
(Blackfire Builds) jako součást CI/CD pipeline a tím předcházet výkonnostním regresím.

:::callout{type="pattern"}
### Interpretace SQL dotazů v Symfony Profileru – praktický postup

1. Otevřete Symfony Profiler panel **Doctrine** a seřaďte dotazy podle doby trvání.
2. Dotazy trvající déle než 100 ms jsou kandidáty pro optimalizaci – zkopírujte SQL a spusťte `EXPLAIN ANALYZE` v databázi.
3. Hledejte `Seq Scan` (PostgreSQL) nebo `Full Table Scan` (MySQL/MariaDB) – signalizují chybějící index.
4. Zkontrolujte, zda se opakují strukturálně stejné dotazy lišící se pouze parametrem – typický příznak N+1 problému.
5. Pro N+1 přidejte `JOIN FETCH` do příslušného repozitáře nebo přepište dotaz na read model (DTO).
:::

:::callout{type="warn"}
### Varování: neprovádějte předčasnou optimalizaci

Optimalizujte **pouze** na základě naměřených dat. Každá optimalizace – přidání cache,
přepsání DQL na nativní SQL, rozdělení agregátu – zvyšuje složitost kódu a ztěžuje budoucí
údržbu. Pokud profiler ukazuje, že daný kód nezpůsobuje výkonnostní problém, ponechte jej
v čitelné, doménově srozumitelné podobě. Výkonnostní optimalizace bez měření je prací naslepo
a pravidelně vede k regresi v jiných částech systému.
:::

Tři páky výkonu v DDD: hranice agregátů, read model a profiling. Pořadí, ve kterém je řešit,
je opačné – nejdřív měřit, pak oddělit read od write přes CQRS, pak doladit hranice agregátů
a eliminovat N+1. Pokračováním je kapitola
[Testování DDD](/testovani-ddd).

:::faq{}
- question: Zpomaluje DDD aplikaci oproti CRUD?
  answer: 'Samotné DDD výkon nesnižuje – doménové třídy jsou čistý PHP bez runtime režie. Zpomalení nastává, když je špatně navržený agregát (načte víc dat, než je třeba). Další příčinou je chybějící read model v CQRS nebo nesprávné použití Doctrine lazy loadingu, které vede k N+1 dotazům. Při správném návrhu je DDD aplikace srovnatelná s CRUD a lépe optimalizovatelná díky explicitním hranicím. Viz <a href="#uvodem">sekci Výkon v kontextu DDD</a>.'
- question: Jak v DDD řešit N+1 problém s agregáty?
  answer: 'N+1 vzniká, když se pro načtený rodičovský objekt doplňkově dotazuje na každý vnitřní prvek. Řešení v Doctrine má tři úrovně: <code>fetch="EAGER"</code> u mapování, fetch join v DQL (<code>SELECT o, i FROM Order o JOIN o.items i</code>) v repository metodě, nebo denormalizovaný read model v CQRS. Pro čtení dat do UI bývá read model nejpřímočařejší – eliminuje ORM lazy loading úplně. Pro write operace stačí správný fetch join při načtení agregátu. Rozbor řešení v <a href="#n-plus-1-problem">sekci N+1 problém</a>.'
- question: Má velikost agregátu vliv na výkon?
  answer: 'Ano, zásadně. Příliš velký agregát vede k načítání desítek vnitřních entit při každé operaci a k častým konfliktům optimistického zamykání. Správně zvolený agregát drží jen to, co musí být konzistentní v jedné transakci. Když dvě části agregátu nesdílejí invariant, jde zpravidla o dva samostatné agregáty – to zvyšuje paralelismus i rychlost operací. Podrobný rozbor v <a href="#agregat-hranice">sekci Agregát a výkon</a>.'
- question: Jak optimalizovat read model v CQRS?
  answer: 'Read model se navrhuje přímo pro daný dotaz – denormalizované tabulky odpovídají tvaru UI, nikoli doménovému modelu. Typické optimalizace jsou dedikované indexy pro konkrétní filtry, materializované projekce místo JOIN dotazů nad write modelem nebo replikace read modelu na jiný datový stroj (Elasticsearch, Redis). Read model lze rebuildnout z událostí, takže změna schématu nevyžaduje klasickou migraci. Detailní rozbor v <a href="#read-model-optimalizace">sekci Optimalizace read modelu</a>.'
- question: Je lepší UUID, nebo integer primární klíč z pohledu výkonu?
  answer: 'Integer klíč je rychlejší v indexech a zabírá méně místa, ale vyžaduje auto-increment generovaný databází. UUID umožňuje vygenerovat identitu v doméně bez round-tripu do DB, což DDD vyžaduje – agregát dostane ID před persistencí. Výkonový rozdíl je v řádu jednotek procent a v praxi je pohlcen vyšší přehledností doménového kódu. Pro DDD se UUID doporučuje. Srovnání obou variant v <a href="#uuid-vs-integer">sekci UUID vs. integer primární klíče</a>.'
:::
