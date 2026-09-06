---
route: performance_aspects
path: /vykonnostni-aspekty
title: Read modely, projekce a výkon
page_title: "Read modely, projekce a výkon | DDD Symfony"
meta_description: "Read modely, projekce a výkon v DDD se Symfony a Doctrine: N+1 problém, hranice agregátů, projekce přes CQRS, snapshoty a cachování read modelů."
meta_keywords: "DDD výkon, Doctrine ORM optimalizace, N+1 problém, lazy loading, JOIN FETCH, DQL, CQRS read model, UUID ULID, Doctrine Identity Map, Unit of Work, batch zpracování, Symfony Cache, Blackfire profiling, agregát hranice"
og_type: article
published: "2025-04-24"
modified: 2026-09-07
breadcrumb_name: Výkonnostní aspekty
schema_type: TechArticle
schema_headline: "Read modely, projekce a výkon"
chapter_number: "16"
category: Vzory
deck: "Read modely, projekce a výkon v Domain-Driven Design se Symfony a Doctrine ORM – řešení N+1 problému, hranice agregátů, budování projekcí přes CQRS, snapshoty a cache read modelů."
reading_time: 36
difficulty: 4
github_examples: null
---

## 16.01 Výkon v kontextu DDD {#uvodem}

Pověst pomalého DDD se opírá o anekdoty místo měření. Výkonnostní problémy
přicházejí ze špatné implementace: příliš velkých agregátů, nevhodného lazy loadingu,
absence read modelu. Doménový model rychlou aplikaci nevylučuje.

:::callout{type="note"}
### DDD vs. výkon: mýty a realita

- **Mýtus:** DDD je vždy pomalejší než anémický model (anemic domain model). **Realita:** Doménové třídy jsou obyčejné PHP objekty a běhovou režii navíc nepřidávají. Režie pochází z persistence: kolik dat se načte a kolika dotazy. Tu lze ladit nezávisle na tvaru modelu.
- **Mýtus:** Agregáty způsobují zbytečné JOIN operace. **Realita:** Za nadbytečnými JOINy stojí špatně vedená hranice, ne agregát jako vzor. Příliš široký kořen tahá z databáze data, která volající nepoužije.
- **Mýtus:** Doctrine ORM je pro DDD pomalý. **Realita:** Doctrine nabízí DQL, nativní dotazy, extra lazy loading, query cache i result cache. Úzké místo obvykle nevzniká v ORM, ale ve způsobu, jakým ho aplikace používá.
:::

Výkon se stává kritickým ve třech scénářích: u aplikací s **desítkami propojených
agregátů**, u **velkých agregátů** s kolekcemi tisíců položek a u systémů s vysokou
frekvencí čtení. Poslední skupina navíc potřebuje odezvu v desítkách milisekund.

:::callout{type="warn"}
### Zlaté pravidlo optimalizace

**Nikdy neoptimalizujte naslepo.** Každá optimalizace musí být podložena měřením.
Předčasná optimalizace (premature optimization) vede k zbytečně složitému kódu, který řeší neexistující
problémy. Nejprve profilujte, identifikujte skutečné úzké místo a teprve potom optimalizujte.
Donald Knuth to vyjádřil takto: *„We should forget about small efficiencies, say about 97% of
the time: premature optimization is the root of all evil. Yet we should not pass up our
opportunities in that critical 3%.“* Zkracuje se obvykle na prostřední větu, čímž se ztratí
obojí: podmínka i pointa. Knuth optimalizaci nezakazuje, vymezuje, kde má smysl.
[[1]](https://dl.acm.org/doi/10.1145/356635.356640)
:::

Kapitola má dvě poloviny a zaměnit je se nevyplácí. Sekce 16.02, 16.03, 16.06 a 16.08 mluví
o **write straně**: o agregátu, Unit of Work a dávkovém zápisu. Limitem je tam konzistence,
takže žádná optimalizace nesmí porušit invariant. Sekce 16.04, 16.07 a část 16.09 patří
**read straně**: dotazu, projekci, cache a replice. Invariant tam nikdo nedrží, a data se proto
smějí denormalizovat, duplikovat i vracet zastaralá. Rada platná na jedné straně na druhé často škodí.

## 16.02 N+1 problém a lazy loading v Doctrine {#n-plus-1-problem}

N+1 je typický anti-vzor, který produkuje každý ORM bez explicitní fetch strategie. Aplikace provede
jeden dotaz pro seznam entit a poté pro každou z nich načte asociovaná data zvlášť.
Celkem tedy N+1 SQL příkazů místo jednoho či dvou.

:::callout{type="note"}
### Přesná definice N+1 problému

Pokud načteme N agregátů `Order` a každý agregát obsahuje kolekci `OrderItem`
mapovanou jako lazy asociace, Doctrine odloží načtení položek do okamžiku prvního přístupu.
Iterace přes všechny objednávky a přístup k jejich položkám způsobí N samostatných SELECT dotazů
nad tabulkou `order_item`, jeden pro každou objednávku.
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
    foreach ($order->items() as $item) {
        echo $item->productId->value . ': ' . $item->quantity;
    }
}
:::
:::

Pro kolekce (OneToMany, ManyToMany) Doctrine ve výchozím stavu používá **lazy loading**:
kolekce zůstává neinicializovaná, dokud k ní kód poprvé nepřistoupí.
V situacích, kdy kolekci vůbec nepoužijeme, je to výhoda. Při iteraci přes mnoho agregátů
to ale plodí výše popsaný N+1 problém.

### Řešení 1: EXTRA_LAZY kolekce

Doctrine nabízí strategii `EXTRA_LAZY` pro kolekce. Standardní lazy loading načte při prvním
přístupu celou kolekci. EXTRA_LAZY místo toho vyřídí `count()`, `contains()`
nebo `slice()` přímými SQL dotazy a do paměti kolekci vůbec nenačte.

:::callout{type="pattern"}
### Konfigurace EXTRA_LAZY v PHP atributech (Doctrine)

*Atributy `#[ORM\Entity]` přímo na agregátu jsou v tomto průvodci výchozí volba (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)). Pro čistou DDD variantu existuje [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern), tedy samostatný persistence model a mapper.*

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (výřez: mapování kolekce)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
final class Order
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

### Řešení 2: Fetch join v DQL pro eager loading

Pokud víme předem, že budeme iterovat přes kolekce, je efektivnější fetch join v DQL:
alias joinované asociace se přidá do klauzule SELECT (`SELECT o, i`). Doctrine pak načte
agregát včetně asociovaných objektů v jediném SQL dotazu s LEFT JOIN nebo INNER JOIN.

:::callout{type="pattern"}
### Příklad: fetch join v DQL a Query Builderu

:::code{language="php" filename="src/Ordering/Infrastructure/Repository/OrderQueryRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Repository;

use App\Ordering\Domain\Model\Order;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Čtecí strana. Rozhraní `OrderRepository` z kapitoly
 * [Základní koncepty](/zakladni-koncepty#repositories) tahle třída záměrně
 * neimplementuje: dotazy pro obrazovky do doménového rozhraní nepatří.
 * Agregát načítá a ukládá `DoctrineOrderRepository` z téže kapitoly.
 */
final class OrderQueryRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Načte objednávky včetně položek v jediném SQL dotazu (fetch join).
     * Vhodné pro iteraci a export - eliminuje N+1 problém.
     *
     * @return Order[]
     */
    public function findAllWithItems(): array
    {
        // Fetch join: alias i je v SELECT, Doctrine hydratuje kolekci v jednom dotazu.
        // LEFT, ne INNER: vnitřní join by objednávky bez položek ze seznamu vypustil.
        return $this->em->createQuery(
            'SELECT o, i
             FROM App\Ordering\Domain\Model\Order o
             LEFT JOIN o.items i
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

Při použití fetch joinu s paginací (`setMaxResults()`, `setFirstResult()`)
Doctrine žádné varování nevypíše. LIMIT se aplikuje na řádky SQL výsledku, ve kterých se
kořenová entita kvůli joinu opakuje. Hydratace pak tiše vrátí méně entit nebo oříznuté kolekce.
Řešením je `Doctrine\ORM\Tools\Pagination\Paginator`, který nejdřív vybere identifikátory
kořenových entit pro danou stránku a teprve pak k nim načte data, případně nativní SQL
s vlastním mapováním výsledků.

`Paginator` má ale svou cenu. Ve výchozím nastavení vydá tři dotazy: `COUNT` s `DISTINCT`,
poddotaz s identifikátory kořenových entit pro danou stránku a finální `WHERE IN`.
Parametr konstruktoru `$fetchJoinCollection: false` sníží počet na dva a je namístě tam,
kde dotaz joinuje jen to-one asociace. `DISTINCT` v počítacím dotazu lze potlačit hintem
`Paginator::HINT_ENABLE_DISTINCT`. Konstanta patří třídě `Paginator`, nikoli `Query`.
Agregační funkce v SELECT se s `fetchJoinCollection: true` chovají nepředvídatelně;
pro dotaz se `SUM()` nebo `COUNT()` je lepší napsat vlastní počítací dotaz.

### Řešení 3: eager fetch po dávkách {#eager-fetch-batch-heading}

U kolekce mapované jako `fetch: 'EAGER'` Doctrine nevydá JOIN. Kolekce načte druhým dotazem,
který obslouží několik kořenových entit najednou. Velikost dávky má výchozí hodnotu 100
a nastavuje ji `Configuration::setEagerFetchBatchSize(int $batchSize = 100)`. Z N+1 dotazů
se tak stane `N/100 + 1` a mechanismus běží i bez explicitní konfigurace.

Háček je v tom, že `EAGER` v mapování platí globálně, i pro dotazy, které asociaci vůbec
nepotřebují. Dokumentace ORM proto doporučuje řešit N+1 primárně fetch joinem v DQL.
Eager mapování zůstává poslední volbou pro asociace, které se načítají prakticky vždy.

### Hluboké stránkování a keyset paging {#keyset-paging-heading}

`OFFSET` databáze neumí přeskočit; musí projít a zahodit všechny předchozí řádky. Na první
stránce je to neměřitelné, na pětitisící stránce je stejný dotaz o řád pomalejší. Druhý
problém je drift: mezi dvěma požadavky přibude záznam, seznam se posune a čtenář uvidí
na další stránce položku, kterou už četl.

Keyset paging (seek metoda) místo offsetu použije hodnoty poslední položky předchozí stránky
jako predikát. Podmínka musí obsahovat celý `ORDER BY` včetně unikátního sloupce, jinak se
na hranici stránky ztratí záznamy se shodným časem.

:::callout{type="pattern"}
### Příklad: keyset paging nad tabulkou read modelu

:::code{language="sql" filename="doc/keyset-paging.sql"}
-- První stránka
SELECT id, created_at, total_amount_in_cents
FROM order_summary
ORDER BY created_at DESC, id DESC
LIMIT 20;

-- Další stránka: klíč poslední položky předchozí stránky
SELECT id, created_at, total_amount_in_cents
FROM order_summary
WHERE (created_at, id) < (:lastCreatedAt, :lastId)
ORDER BY created_at DESC, id DESC
LIMIT 20;

-- Index musí pokrýt celý ORDER BY, jinak databáze řadí v paměti
CREATE INDEX idx_order_summary_feed
    ON order_summary (created_at DESC, id DESC);
:::
:::

Read model má vlastní tabulku, takže index se navrhuje přímo k dotazu, který ji čte, ne ke
schématu domény. To je praktický rozdíl oproti write straně, kde indexy slouží hlavně
vyhledání agregátu podle identity. Cenou keyset pagingu je ztráta skoku na libovolnou stránku:
seek metoda umí „další“ a „předchozí“, nikoli „stránka 137“.

:::callout{type="note"}
### Lazy loading v PHP 8.4 a Doctrine ORM 3 {#lazy-objects-heading}

Lazy inicializace přestala být záležitostí generovaných proxy tříd. PHP 8.4 přidalo lazy
objekty přímo do jazyka (`ReflectionClass::newLazyGhost()`, `newLazyProxy()`,
`ReflectionProperty::skipLazyInitialization()`) a DoctrineBundle 3 je má zapnuté napevno.
Pro doménový kód to má jeden viditelný důsledek: nativní lazy objekt z entity nedědí,
takže mapovaná entita smí být `final`. Chování `EXTRA_LAZY` ani fetch joinu se tím nemění –
jde o způsob, jakým vzniká instance, ne o strategii načítání kolekce.
:::

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
### Příklad: kdy velký agregát opravdu zabolí

:::code{language="php" filename="snippet.php"}
<?php
// find() sám o sobě položky nenačte - kolekce je lazy a zůstane
// neinicializovaná. Cena přijde až ve chvíli, kdy na ni kdokoli sáhne:
// jeden SELECT s 1000 řádky a skokový nárůst paměti. U hlavičky
// objednávky je to zbytečná práce, kterou snadno vyvolá i šablona.

$order = $this->orders->get($orderId);

echo $order->orderNumber;
echo $order->createdAt->format('d.m.Y');
echo $order->customerId->value; // agregáty se odkazují přes ID, ne přes objekt

// Tohle je ten drahý řádek, ne find() výše:
echo $order->itemCount();
:::
:::

### Řešení: rozdělení agregátu a specializované repozitářní metody

Prvním krokem je kriticky přezkoumat, zda `OrderItem` skutečně musí být součástí
agregátu `Order`, nebo zda jde o samostatný agregát s odkazem na `OrderId`.
Rozhoduje invariant. Pokud objednávka nedrží žádné pravidlo přes celou kolekci
(limit počtu položek, minimální hodnota košíku), kolekce v agregátu nemá co dělat.
Její vyčlenění je pak oprava návrhu, ne výkonnostní trik.

Rozdíl je v roli, kterou výkon hraje. Jako **signál** špatně vedené hranice je legitimním
podnětem: pomalé načítání ukazuje na kolekci, která nikdy součástí invariantu nebyla.
Jako **důvod** rozbít invariant legitimní není. Odpovědí tam zůstává read model,
ne přesun pravidla mimo agregát. Podrobně rozebírá velikost agregátu sekce
[Velikost agregátu a její dopady](/navrh-agregatu#aggregate-size).

:::callout{type="pattern"}
### Příklad: specializované repozitářní metody pro různé kontexty

:::code{language="php" filename="src/Ordering/Infrastructure/Repository/DoctrineOrderRepository.php (výřez: hlavička bez položek)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Repository;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\OrderId;
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
            'SELECT o, i FROM App\Ordering\Domain\Model\Order o
             LEFT JOIN o.items i
             WHERE o.id = :id'
        )
            ->setParameter('id', $id->value)
            ->getOneOrNullResult();
    }
}
:::
:::

Pravidlo zní: **hranice agregátu vede přes doménové invarianty**, výkonnostní
požadavky se řeší jinde. Evans i Vernon vedou hranici stejně, tedy přes pravidlo, které musí
platit v jedné transakci. Když výkon tlačí proti doménovému modelu, odpovědí je read model
(viz následující sekci), ne porušení doménové integrity.

## 16.04 Optimalizace read modelu (CQRS) {#read-model-optimalizace}

Oddělení write side (operace přes agregáty) od read side (dotazy do prezentace) je nejsilnější
páka na výkonnostní problémy v DDD. Read side doménové objekty nepotřebuje a vrací rovnou
strukturu dat pro UI nebo API klienta.

Zároveň je to páka s nejvyšší cenou. Martin Fowler o CQRS píše, že *„for most systems CQRS
adds risky complexity“*. Případy, které viděl, popisuje spíš jako zdroj potíží než
jako záchranu. Nasazovat jej doporučuje na jednotlivý bounded context, nikdy plošně
[[2]](https://martinfowler.com/bliki/CQRS.html). Kompletní seznam kompromisů drží sekce
[Výzvy a omezení CQRS](/cqrs#challenges). Než tedy dotaz skončí v samostatném read modelu,
vyplatí se projít levnější stupně.

:::callout{type="note"}
### Žebříček eskalace před sáhnutím po projekci {#eskalace-heading}

1. **Index k dotazu.** Nejlevnější zásah, žádná změna kódu. `EXPLAIN ANALYZE` řekne, jestli chybí.
2. **Dotaz mimo ORM.** DBAL vrátí pole místo hydratovaných entit; odpadá Identity Map i dirty checking.
3. **Read replica.** Čtení jde na repliku, zápisy zůstávají na primary. Model se nemění, přibývá replikační zpoždění.
4. **Reporting databáze.** Fowlerův starší vzor [[3]](https://martinfowler.com/bliki/ReportingDatabase.html): kopie provozních dat přeuspořádaná pro dotazy. Normalizovat ji netřeba, protože se jen čte.
5. **Materializovaný pohled.** V PostgreSQL uložený výsledek dotazu, který se obnovuje příkazem `REFRESH MATERIALIZED VIEW`. Varianta `CONCURRENTLY` neblokuje čtení, ale vyžaduje nad pohledem unikátní index.
6. **Asynchronní projekce.** Vlastní tabulka plněná událostmi. Nejvyšší volnost tvaru dat, nejvyšší provozní náklad: rebuild, idempotence, měření zpoždění.
:::

:::callout{type="note"}
### Zásady read modelu v CQRS

- Query handlery **nepoužívají doménové repozitáře**. Přistupují přímo k databázi přes DQL nebo nativní SQL.
- Výsledkem je **DTO (Data Transfer Object)** nebo plain PHP array, nikdy doménový objekt.
- Read model může být **denormalizovaný**, s daty už předpřipravenými pro konkrétní view.
- Read side lze **nezávisle cachovat** bez ohrožení doménové konzistence.
:::

:::callout{type="pattern"}
### Příklad: QueryHandler vracející DTO přes DQL

:::code{language="php" filename="src/Ordering/Application/Query/OrderSummaryDTO.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query;

use Doctrine\ORM\EntityManagerInterface;
use App\Ordering\Domain\ValueObject\OrderStatus;
use App\SharedKernel\Domain\Currency;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// DTO a handler v jednom souboru jsou zhuštění pro ukázku - PSR-4 vyžaduje samostatné soubory.

final class OrderSummaryDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $orderNumber,
        public readonly string $customerId,
        // Stav i měna jsou v kanonickém modelu enumy, ne řetězce. DQL NEW
        // předá to, co je namapované, takže `string` by tu skončilo na
        // TypeError - a kdo si je namapuje jako prostý string, rozejde si
        // čtecí stranu s hodnotovými objekty ze Základních konceptů.
        public readonly OrderStatus $status,
        public readonly int    $itemCount,
        public readonly int    $totalAmountInCents,
        public readonly Currency $currency,
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
            'SELECT NEW App\Ordering\Application\Query\OrderSummaryDTO(
                o.id,
                o.orderNumber,
                o.customerId,
                o.status,
                COUNT(i.id),
                o.totalAmount.amountInCents,
                o.totalAmount.currency,
                o.createdAt
             )
             FROM App\Ordering\Domain\Model\Order o
             LEFT JOIN o.items i
             WHERE o.status IN (:statuses)
             GROUP BY o.id, o.orderNumber, o.customerId, o.status,
                      o.totalAmount.amountInCents, o.totalAmount.currency, o.createdAt
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

Dotaz se drží jednoho agregátu a zákazníka nese jen jako `customerId`. Jméno zákazníka leží
v jiném agregátu, a DQL by pro něj potřebovala mapovanou asociaci `Order` → `Customer`, kterou
kniha na write straně zakazuje (viz [Reference přes identitu](/navrh-agregatu#references-by-id)).
Na read straně je spojení obou tabulek v pořádku. Dělá se ale o patro níž, v SQL nad
tabulkami, ne přes namapovaný objektový graf. Následující ukázka to předvádí.

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
            WHERE o.status = 'delivered'
              -- Polouzavřený interval. BETWEEN nad timestamp sloupcem s datem
              -- bez času by uřízl celý poslední den.
              AND o.created_at >= :from
              AND o.created_at <  :to
            GROUP BY c.id, c.first_name, c.last_name, TO_CHAR(o.created_at, 'YYYY-MM')
            -- Řadí se podle výrazu, ne podle aliasu: ten je ::text,
            -- takže by se '999' seřadilo za '10000'.
            ORDER BY month DESC, SUM(oi.unit_price_amount * oi.quantity) DESC
        ";

        return $this->em
            ->createNativeQuery($sql, $rsm)
            ->setParameter('from', $from->format('Y-m-d H:i:s'))
            ->setParameter('to',   $to->format('Y-m-d H:i:s'))
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

- Identitu generuje doména, takže agregát je kompletní před persistencí.
- Pro distribuované systémy se hodí právě proto, že centrální generátor ID není potřeba.
- UUID lze bezpečně přenášet do API bez rizika enumeration útoků (na rozdíl od sekvenčních integerů).
- Event sourcing: událost nese ID agregátu, který ještě neexistuje v databázi.

### Výkonnostní dopady UUID

- **Index fragmentace:** UUID v4 jsou náhodné, takže nové záznamy padají na náhodné pozice v B-tree indexu. Index se fragmentuje a INSERT zpomaluje.
- **Větší velikost:** UUID zabírá 16 bajtů (binárně) nebo 36 znaků (textově) oproti 4–8 bajtům pro integer. Index je širší a I/O operací přibývá.
- **Problém s cizími klíči:** Každý FK odkazující na UUID agregát nese 16 bajtů místo 4.
:::

### ULID jako kompromis

ULID (Universally Unique Lexicographically Sortable Identifier) a UUID verze 6/7 (ordered UUID)
řeší problém fragmentace indexů tím, že jsou **monotónně rostoucí**. Nové hodnoty
jsou vždy větší než předchozí a vkládají se na konec B-tree indexu. Chování je stejné
jako u auto-increment integeru, ale se zachováním globální unikátnosti bez centrálního generátoru.
Tento průvodce používá UUID v7; ULID je alternativa s kratším Crockford base32 zápisem
(26 znaků vs. 36).

:::callout{type="pattern"}
### Příklad: Použití symfony/uid (UUID v7)

:::code{language="php" filename="src/SharedKernel/Domain/ValueObject/OrderId.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

// Dvě třídy v jednom souboru jsou zhuštění pro ukázku - PSR-4 vyžaduje samostatné soubory.

/**
 * Hodnotový objekt pro identitu objednávky - používá UUID v7 pro výkon.
 * UUID v7 je časově řazené a monotónně rostoucí - přátelské k B-tree indexům.
 */
final readonly class OrderId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid UUID.', $value)
            );
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
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

// Stejná strategie pro identitu uživatele
final readonly class UserId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid UUID.', $value)
            );
        }
    }

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
### Doctrine mapování pro UUID

*Atributy `#[ORM\Entity]` přímo na agregátu jsou v tomto průvodci výchozí volba (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)). Pro čistou DDD variantu existuje [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern), tedy samostatný persistence model a mapper. Ukázka níže je jiná varianta mapování `Order` než v [sekci N+1](#n-plus-1-problem): ID zde má nativní typ `Uuid`, nikoli `string`.*

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (mapování pro čtecí stranu)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\SharedKernel\Domain\ValueObject\OrderId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
final class Order
{
    #[ORM\Id]
    // Symfony Bridge registruje 'uuid' typ - ukládá jako BINARY(16) nebo nativní uuid v PostgreSQL.
    // Při načtení z DB typ hydratuje objekt Uuid, property proto musí mít typ Uuid.
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private readonly Uuid $id;

    #[ORM\Column(type: 'string', length: 50)]
    private readonly string $orderNumber;

    public function __construct(OrderId $id, string $orderNumber)
    {
        $this->id          = Uuid::fromString($id->value);
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

Doctrine ORM implementuje vzor Identity Map (Martin Fowler, *Patterns of Enterprise Application
Architecture*, Addison-Wesley, 2003).
Každý spravovaný objekt (managed entity) je v jednom `EntityManager`u uložen v paměti pod svým
identifikátorem. Pokud načtete tentýž agregát dvakrát přes `find()` podle ID, Doctrine podruhé
vrátí objekt z paměti bez SQL dotazu. Opakovaný DQL dotaz SQL provede, ale při hydrataci
vrátí existující instanci z Identity Map, nikoli novou kopii.

:::callout{type="note"}
### Identity Map a Unit of Work – co to znamená pro DDD

- **Konzistence v requestu:** Všechny části kódu vidí tentýž stav agregátu, takže nekonzistentní kopie nevzniknou.
- **Jedno místo změn:** Změny agregátu sleduje Unit of Work a při `flush()` je synchronizuje do databáze. Není třeba explicitně volat `save()` pro každou změnu.
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
use App\SharedKernel\Domain\Currency;
use App\SharedKernel\Domain\Money;
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
            $product = Product::create(
                ProductId::generate(),
                $row['name'],
                $row['sku'],
                new Money((int) $row['price_in_cents'], Currency::from($row['currency'])),
            );

            // persist přidá objekt do Identity Map, ale SQL zatím nevydá
            $this->em->persist($product);

            if (++$i % self::BATCH_SIZE === 0) {
                // flush() vydá nahromaděné INSERTy. Doctrine je neslučuje
                // do jednoho víceřádkového příkazu - sto entit znamená sto
                // INSERTů. Úspora je v paměti a v dirty checkingu, ne v počtu
                // dotazů.
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

Velikost dávky je empirická hodnota, ne konstanta z dokumentace. Doctrine doporučuje začít
u malých čísel (řádově desítky) a měřit; optimum závisí na šířce řádku, počtu indexů
a nastavení databáze. Sto řádků v ukázce je výchozí bod, ne cíl.

:::callout{type="warn"}
### Pozor na clear() a detached entity

Po zavolání `$this->em->clear()` jsou **všechny** spravované entity odpojeny
(stav *detached*). Doctrine při přístupu k nim žádnou výjimku nevyhazuje. Lazy proxy
se podle potřeby doinicializuje dalším dotazem do databáze. Nebezpečí je tišší:
změny na odpojených objektech `flush()` mlčky ignoruje a odpojená entita nalezená
v asociaci nově persistovaného objektu shodí flush s `ORMInvalidArgumentException`.
Ujistěte se, že po `clear()` nepracujete s referencemi na dříve spravované objekty.

ORM 3 tuhle past zúžilo: `clear()` už nepřijímá argument, takže selektivní vyčištění jedné
třídy skončilo a volání vždy odpojí celou Identity Map. Zmizely i `EntityManager::merge()`
a `UnitOfWork::merge()`, kterými se dřív odpojená entita vracela do správy. Odpojený objekt
se tedy znovu načítá přes `find()`, nikoli slučuje.
:::

### Read-only entity a rozsah dirty checkingu {#read-only-entity-heading}

Dirty checking při každém `flush()` porovnává aktuální stav spravovaných objektů s jejich
snímkem z okamžiku načtení. U referenčních dat je to čistá ztráta. Číselníky, katalog ani
sazebník se během jednoho běhu nemění. Doctrine na to má dvě páky:
`#[ORM\Entity(readOnly: true)]` označí celou třídu a `UnitOfWork::markReadOnly()` jednu
instanci pro daný běh. Změny takového objektu `flush()` do databáze nezapíše.

Druhou pákou je strategie sledování změn. Výchozí `DEFERRED_IMPLICIT` prochází při `flush()`
všechny spravované objekty; alternativní strategie rozsah zužují za cenu explicitnějšího kódu
v entitách. Pro běžný web request je výchozí nastavení v pořádku, v dávkovém běhu stojí za zvážení.

## 16.07 Caching v DDD architektuře {#cachovani}

Caching v DDD má jednu vstupní otázku: **co cachovat**? Pravidlo: cache patří na výsledky,
které jsou výpočetně nebo I/O nákladné a v čase se nemění (nebo se mění předvídatelně).
Doménová logika do cache klíče nepatří. Cache slouží infrastruktuře, ne doménovým rozhodnutím.

:::callout{type="note"}
### Co cachovat a co ne

Do cache patří výsledky read modelu (DTO), reportovací dotazy, odpovědi externích API a výpočetně náročné projekce. Nepatří tam aktuální stav agregátů, které se právě mění (způsobí dirty reads), ani výsledky, jejichž neaktuálnost by vyvolala doménové nekonzistence. Výsledek doménové logiky nepatří nikdy ani do cache klíče. Sleva se například při sestavování klíče nevypočítává.
:::

### Query cache a result cache v Doctrine

Doctrine nabízí dvě úrovně cachování SQL dotazů:

- **Query cache:** cachuje přeložený DQL → SQL. DQL parsing je relativně nákladný; query cache eliminuje opakované parsování pro identické DQL dotazy. Překlad DQL na SQL se v čase nemění, takže tuto cache není třeba invalidovat.
- **Result cache:** cachuje výsledky SQL dotazu. Zapíná se na konkrétním dotazu metodou `enableResultCache(?int $lifetime, ?string $resultCacheId)`. Vhodná pro read-heavy dotazy s řízenou dobou platnosti.

Result cache se neinvaliduje doménovou událostí, ale klíčem. Buď dotazu předáte vlastní
`$resultCacheId` a ten po změně dat smažete přes `$em->getConfiguration()->getResultCache()`,
nebo klíč odvodíte od dat (například z času poslední změny) a starý záznam necháte vypršet.
Bez pojmenovaného klíče si Doctrine vygeneruje hash z SQL a parametrů, který v aplikačním kódu
nemáte jak zopakovat. Takový dotaz lze zneplatnit jen vypršením TTL.

Vedle toho existuje Second Level Cache, která cachuje samotné entity a kolekce v režimech
`READ_ONLY`, `NONSTRICT_READ_WRITE` a `READ_WRITE`. Dokumentace ji stále označuje za
experimentální a tento průvodce ji nepoužívá: cache na úrovni read modelu řeší totéž
srozumitelněji a bez vazby na životní cyklus entit.

:::callout{type="pattern"}
### Příklad: cache read modelu v query handleru

:::code{language="php" filename="src/UserManagement/Application/Query/GetUserProfileHandler.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Query;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\UserManagement\Profile\Query\GetUserProfile;

// View a handler v jednom souboru jsou zhuštění pro ukázku - PSR-4 vyžaduje samostatné soubory.

/**
 * Read model profilu - immutabilní DTO se skalárními hodnotami.
 * Bezpečně serializovatelný do cache.
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
        private TagAwareCacheInterface $cache,
    ) {}

    public function __invoke(GetUserProfile $query): ?UserProfileView
    {
        // Cache Contracts: jedno volání místo isHit()/set()/save(),
        // callback se spustí jen při minutí cache
        return $this->cache->get(
            'user_profile_' . $query->userId,
            function (ItemInterface $item) use ($query): ?UserProfileView {
                $item->expiresAfter(self::TTL);
                // Tag pokrývá všechny pohledy odvozené od jednoho uživatele
                $item->tag(['user_' . $query->userId]);

                $row = $this->connection->fetchAssociative(
                    'SELECT u.id, u.name, u.email, COUNT(o.id) AS order_count
                       FROM users u
                  LEFT JOIN orders o ON o.customer_id = u.id
                      WHERE u.id = :id
                   GROUP BY u.id',
                    ['id' => $query->userId],
                );

                return $row
                    ? new UserProfileView(
                        userId: $row['id'],
                        name: $row['name'],
                        email: $row['email'],
                        orderCount: (int) $row['order_count'],
                    )
                    : null;
            },
            // beta > 0 zapne pravděpodobnostní předčasné přepočítání
            beta: 1.0,
        );
    }
}
:::
:::

Cache drží hotový ViewModel, ne doménový agregát. Serializace agregátu je křehká:
po deserializaci vznikne objekt odpojený od Unit of Work (detached), lazy proxy asociací
přestanou fungovat a obejde se Identity Map. DTO se skalárními hodnotami tyto problémy nemá –
přesně podle zásady z calloutu výše: do cache patří výsledky read modelu, ne stav agregátů.

Ukázka používá Cache Contracts (`Symfony\Contracts\Cache\CacheInterface`), ne holé PSR-6.
Rozdíl není jen v délce kódu. Contracts drží po dobu výpočtu zámek, takže při vypršení
záznamu přepočítá hodnotu jen jeden proces a ostatní počkají. Parametr `$beta` k tomu přidá
pravděpodobnostní předčasnou expiraci: čím blíž je záznam konci platnosti, tím větší šance,
že ho jeden náhodně vybraný požadavek přepočítá dřív, než vyprší. Obojí brání cache stampede,
kdy po expiraci horkého klíče spustí tentýž dotaz stovky souběžných požadavků najednou.
Holé PSR-6 přes `CacheItemPoolInterface` zůstává pro interoperabilitu s knihovnami třetích stran.

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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsEventListener(event: UserEmailChanged::class)]
final class InvalidateUserCacheOnEmailChanged
{
    public function __construct(
        private TagAwareCacheInterface $cache
    ) {}

    public function __invoke(UserEmailChanged $event): void
    {
        // Jedno volání zneplatní všechny pohledy označené tímto tagem:
        // profil, položku v seznamu i detail objednávek daného uživatele
        $this->cache->invalidateTags(['user_' . $event->userId->value]);
    }
}
:::
:::

Jedna doménová událost typicky zneplatňuje víc odvozených pohledů, ne jeden klíč. Právě proto
listener maže tag, nikoli položku: seznam, detail i dlaždice na dashboardu nesou stejný tag
a zmizí jedním voláním. Tagy vyžadují pool s podporou tagování: v konfiguraci `framework.cache`
buď `tags: true`, nebo adaptér `cache.adapter.redis_tag_aware`. Ručně se pak dá skupina
zneplatnit i z konzole příkazem `cache:pool:invalidate-tags`.

## 16.08 Bulk operace a hromadné zpracování {#bulk-operace}

Standardní DDD postup funguje pro jednotlivé agregáty: načíst agregát, aplikovat doménovou
logiku, zavolat `flush()`. Pro hromadné operace (import tisíců záznamů,
hromadná aktualizace stavů, migrace dat) je tento přístup neefektivní. Každý cyklus přidá
agregát do Identity Map a dirty checking při `flush()` prochází všechny spravované objekty.
Bez průběžného `clear()` proto roste spotřeba paměti i doba jednoho `flush()`: každý další
zápis porovnává větší množinu objektů než ten předchozí.

### DQL bulk UPDATE a DELETE – bypass Identity Map

Pro hromadné aktualizace, kde není potřeba procházet doménovou logiku, nabízí Doctrine možnost
provést `UPDATE` nebo `DELETE` přímo přes DQL. Tyto operace obcházejí
Identity Map a Unit of Work, protože jde o přímé SQL příkazy přeložené z DQL. **Nevýhoda:**
po DQL bulk operaci jsou spravované entity v Identity Map nekonzistentní se stavem v databázi.

Obvyklá rada zní zavolat `clear()`. V ORM 3 ale `clear()` argument nepřijímá, takže odpojí
úplně všechno, včetně rozdělané práce volajícího. Bezpečnější pořadí je pustit bulk operaci
dřív, než se do entity manageru cokoli načte, nebo ji provést nad samostatným entity managerem
vyhrazeným pro údržbové úlohy. `clear()` na konci handleru je pak pojistka, ne oprava.

:::callout{type="pattern"}
### Příklad: hromadná změna stavu přes DQL UPDATE

:::code{language="php" filename="src/Ordering/Infrastructure/Command/BulkUpdateOrderStatusHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Command;

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
            'UPDATE App\Ordering\Domain\Model\Order o
             SET o.status = :newStatus
             WHERE o.status = :oldStatus
               AND o.createdAt < :before'
        )
            ->setParameter('newStatus', $command->newStatus)
            ->setParameter('oldStatus', $command->oldStatus)
            ->setParameter('before', $command->before)
            ->execute();

        // Handler nic jiného nenačítá, proto je clear() na konci bezpečný
        $this->em->clear();

        return $affectedRows;
    }
}
:::
:::

Dávkový zápis přes `persist()`/`flush()`/`clear()` ukazuje handler ze sekce
[Identity Map a Unit of Work](#doctrine-identity-map); stejný postup platí i pro import.
Ještě poznámka k ladění: `setSQLLogger()` je od DBAL 3.2 deprecated
a DBAL 4 ho odstranil. Logování se vypíná konfigurací (`doctrine.dbal.logging: false`)
nebo odebráním logovacího middleware, ne programaticky uprostřed dávky.

### Čtení velkých výsledků po jednom {#streamovane-cteni-heading}

Zápis je jen půlka dávkové úlohy. Druhá půlka je čtení: `getResult()` zhmotní celý výsledek
do pole, takže milion řádků skončí v paměti PHP naráz. `AbstractQuery::toIterable()` vrací
`Traversable` a hydratuje po jednom řádku; metoda `iterate()`, kterou znají uživatelé ORM 2,
v ORM 3 zanikla. Platí u ní dvě omezení:

- `toIterable()` **nelze kombinovat s fetch joinem kolekce**. Jeden agregát se v SQL výsledku roztáhne do několika řádků a hydratace po řádcích je nedokáže složit dohromady. Naráží to přímo na doporučení ze [sekce o N+1](#n-plus-1-problem). V dávce se fetch join kolekcí nepoužívá.
- Objekty vydané iterací zůstávají spravované. Bez průběžného `clear()` se ušetří jen paměť za pole výsledků, ne za Identity Map.

Když stačí data, ne agregáty, je přímočařejší DBAL: `Connection::iterateAssociative()`
(a sourozenci `iterateNumeric()`, `iterateKeyValue()`, `iterateAssociativeIndexed()`,
`iterateColumn()`) vrací `Traversable` polí a ORM se do cesty vůbec neplete. Klientská knihovna
databáze si přitom může výsledek bufferovat mimo paměť PHP, takže `memory_get_usage()` úsporu
nemusí ukázat; spolehlivější je sledovat spotřebu celého procesu.

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

Odesláním zpráv práce nekončí, jen se přesune k workerům. PHP proces, který běží hodiny,
paměť postupně nasčítá, proto se worker spouští s limity a nechává se restartovat:
`messenger:consume async --memory-limit=128M --time-limit=3600 --limit=1000`. Restart řídí
supervisor nebo systemd, ne aplikace. Počet souběžných workerů má strop v databázi.
Každý drží vlastní spojení, takže deset workerů nad primary znamená deset dalších
připojení. Souvislost s poolingem rozebírá sekce [Read replicy a connection pooling](#replicy-pooling-heading).

## 16.09 Provozní výkonové vzory {#provozni-vzory}

Předchozí sekce řeší výkon na úrovni jednoho dotazu nebo jednoho agregátu. Jakmile
aplikace běží 24/7 s reálnou zátěží, narážíte na třídu problémů, které lokální profiling
neukáže: souběžnost více klientů, omezení databáze jako sdíleného zdroje a operační
omezení Doctrine ve více procesech.

### Hot aggregates a optimistic lock thrash {#hot-aggregates-heading}

**Hot aggregate** je agregát, který současně mění mnoho klientů. Klasické
příklady: globální `Inventory` jednoho produktu při rozjezdu kampaně, `Tournament`
agregát s 1000 účastníky, kteří všichni paralelně potvrdí účast, nebo `BankAccount`
firmy s tisíci transakcí denně.

:::diagram{fig="16.9-A" title="Optimistic lock thrash: 3 souběžné modifikace, 2 retry" src="images/diagrams/17_performance/hot_aggregate_thrash.svg"}
:::

S `#[ORM\Version]` (optimistický zámek) vede souběžná modifikace k výjimkám
`OptimisticLockException`. Dokud jsou konflikty výjimečné, je retry levný: druhý pokus
projde. Jakmile konfliktů přibude, roste podíl práce, která končí zahozením. Systém
**degraduje na sériový provoz** a smyčka load → modify → save → conflict → retry spotřebuje
víc kapacity než samotné zpracování. Throughput klesá, latence stoupá.

Kdy zlom nastane, žádné univerzální číslo neurčuje. Rozhoduje délka transakce, počet klientů
a to, jak často míří na tentýž agregát. Změřit to ale jde: podíl `OptimisticLockException`
na počtu pokusů o zápis daného agregátu je metrika, kterou má smysl sledovat trvale.

Strategie řešení rozebírá sekce [Hot aggregate](/navrh-agregatu#hot-aggregate);
z pohledu provozu jsou podstatné tři, v tomto pořadí:

- **Re-design hranic agregátu.** Pokud je `Inventory` hot, není to často
  jeden agregát, ale **N samostatných agregátů per warehouse + sklad pool**.
  Jeden agregát na region/sku/sklad. Konflikty pak nejsou „mezi všemi klienty“,
  ale „mezi klienty stejné lokace“.
- **Eventual consistency místo strong.** Místo „strhni 1 ks z `Inventory` synchronně“
  se publikuje event `ItemReserved(productId, qty)` a agregát ho zpracuje
  asynchronně přes ságu. Konflikty řeší sága přes kompenzaci, ne optimistic lock.
- **CRDT / counter-only agregáty.** Pokud doménová operace je čistý increment
  (`view_count`, `like_count`), nepotřebujete celý agregát, stačí
  Postgres `UPDATE counters SET n = n + 1 WHERE id = ?`. Není to typické DDD,
  ale u skutečně komutativních operací je to legitimní řešení.

:::callout{type="warn"}
### Anti-vzor: pessimistic lock místo redesignu {#anti-pessimistic-lock-heading}

Když optimistic lock generuje konflikty, lákavé řešení je runtime zámek
`$em->find(Order::class, $id, LockMode::PESSIMISTIC_WRITE)`. Databáze drží zámek
`SELECT FOR UPDATE` a další klient čeká. Konflikty zmizí, ale výsledek je horší: klienti se
serializují na úrovni databáze místo aplikace, zámky drží přes celou transakci
(včetně síťové komunikace s app serverem), pravděpodobnost deadlocku roste.
Pessimistic lock zakryje příznak, ne příčinu. Pokud je agregát hot,
**hranice je špatně**.
:::

### Partitioning velkých tabulek {#partitioning-heading}

PostgreSQL declarative partitioning je standardní řešení pro tabulky, které přerostly
paměť serveru a kde se aktivně mění jen poslední část (typicky podle `created_at`):

- **`orders` dělená po měsících** – aktivní partition drží jen poslední měsíc,
  takže se i s indexy vejde do cache. Staré partitions (read-only) mohou
  být na pomalejším disku nebo v archivu.
- **`audit_log` dělená po dnech** – `DROP PARTITION` po retention period
  je atomický a nezamyká aktivní tabulku.
- **`projection_*` tabulky** s vysokým write rate.

Pro DDD má partitioning jeden důsledek navíc: **agregátní reference přes ID
musí být kompozitní** (id + partition key, např. `created_at`). Pokud doména
zná jen `OrderId`, partition lookup vyžaduje plný scan napříč partitions
(pomalé). Standardní řešení: zahrnout `created_at` (nebo derivovaný měsíc)
do hodnotového objektu `OrderId`, aby ho repozitář uměl použít pro partition pruning.

:::callout{type="note"}
### Kdy partition použít {#partitioning-kdy-heading}

- Tabulka roste lineárně s časem (audit, outbox, orders, eventy).
- Naprostá většina dotazů se týká posledních dní nebo měsíců.
- Mazání starých dat vyžaduje compliance, GDPR nebo retenční politika.
- Aktivní část tabulky se přestala vejít do cache databáze a index nad celou tabulkou už zpomaluje zápis.

**Nehodí se** pro tabulky, které se celé vejdou do paměti. Partitioning tam přidá operační
složitost bez měřitelného přínosu. Konkrétní práh v řádcích ani gigabajtech nemá smysl
uvádět: rozhoduje poměr velikosti aktivní části k dostupné paměti, ne absolutní číslo.
:::

### Read replicy a connection pooling {#replicy-pooling-heading}

V CQRS architektuře jsou read modely vhodný kandidát pro **read replicy**:
samostatnou databázi (nebo Postgres streaming replicu), na kterou jdou všechny
queries. Write model zůstává na primary. Důsledky pro DDD kód:

:::diagram{fig="16.9-B" title="Routing: write na primary, read na replicu, replikační lag" src="images/diagrams/17_performance/read_replica_routing.svg"}
:::

- **Repozitář write strany** drží `EntityManagerInterface` namapovaný na primary.
- **Query handler read strany** drží separátní `Connection` nebo
  `EntityManager` namapovaný na replicu (`doctrine.orm.read_entity_manager`).
- **Replikační lag** znamená, že dotaz na repliku nemusí hned po `save()` na primary
  vidět změnu. Je to stejný „read your writes“ problém jako u eventual consistency. Vzor řešení viz
  [CQRS – eventual consistency v UI](/cqrs#eventual-consistency). Velikost lagu je vlastnost
  konkrétního nasazení, ne konstanta; Postgres ji vydá jako `pg_last_xact_replay_timestamp()`
  a patří do monitoringu vedle latence dotazů.

Nejlevnější obranou proti „read your writes“ je routing, ne kód domény: po zápisu se relace
na krátkou dobu přilepí na primary a čte odtud. Sticky routing řeší přesně tu chvíli, kdy
uživatel odešle formulář a hned nato vidí výpis. Alternativa přes pozici zápisu v HTTP hlavičce
je popsaná v sekci [Read-your-writes na úrovni HTTP](/cqrs#read-your-writes-http).

Identifikátor `doctrine.orm.read_entity_manager` platí jen tehdy, když je entity manager
tohoto jména nakonfigurovaný. Symfony pro každý manager vygeneruje službu
`doctrine.orm.<název>_entity_manager` a autowiring se řídí jménem parametru:

:::callout{type="pattern"}
### Konfigurace druhého entity manageru pro read stranu

:::code{language="yaml" filename="config/packages/doctrine.yaml (výřez: druhý entity manager)"}
doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                url: '%env(resolve:DATABASE_URL)%'
            read:
                url: '%env(resolve:DATABASE_READ_URL)%'   # replica
    orm:
        default_entity_manager: default
        entity_managers:
            default:
                connection: default
                mappings:
                    Order:
                        type: attribute
                        dir: '%kernel.project_dir%/src/Order/Domain/Model'
                        prefix: 'App\Ordering\Domain\Model'
            read:
                connection: read
                mappings:
                    ReadModel:
                        type: attribute
                        dir: '%kernel.project_dir%/src/Order/Infrastructure/ReadModel'
                        prefix: 'App\Ordering\Infrastructure\ReadModel'

# Query handler pak vezme repliku podle jména parametru:
# public function __construct(private EntityManagerInterface $readEntityManager) {}
:::
:::

Asociace mezi entitami různých managerů Doctrine nepodporuje. Pro read model je to spíš
výhoda: donutí to psát dotazy nad tabulkami, ne nad objektovým grafem přes hranice agregátů.

Connection pooling je ortogonální problém. PHP-FPM model „1 worker = 1 PHP proces
= 1 DB connection“ se nasčítá: 4 aplikační pody × 100 PHP-FPM workerů
= 400 spojení na primary, tedy čtyřnásobek výchozího `max_connections = 100`
v Postgresu.
Standardní řešení: **PgBouncer / RDS Proxy** mezi aplikací a DB, transaction
pooling mode. Pozor: transaction pooling sám o sobě prepared statements nepodporuje,
a Doctrine je používá. Řešením je buď session pooling (méně efektivní), nebo PgBouncer
od verze 1.21 s `max_prepared_statements` > 0. Volbu zavedla právě 1.21 s výchozí
hodnotou 0, tedy vypnuto; zapnutá ve výchozím stavu (200) je až od 1.24. Na starším
PgBounceru je proto nutné ji nastavit explicitně. Ten si prepare
od klienta zachytí, přidělí mu interní jméno a na backendu ho v případě potřeby připraví
znovu. Podmínka: musí jít o prepared statements vedené protokolem databáze, tedy
`PQprepare`/`PQexecPrepared` v libpq. Statementy emulované na straně klienta se do LRU cache
nedostanou. Konfigurační volba `prepared_statements = true` neexistuje.

### Projekce v provozu: zpoždění a rebuild {#projekce-provoz-heading}

Jak projekci napsat, ukazují kapitoly [CQRS](/cqrs#read-model-optimalizace) a
[Event Sourcing](/event-sourcing#projekce). Provozní půlka začíná až tam, kde ty končí:
projekce běží asynchronně, takže mezi zápisem a jeho zobrazením je vždy nějaké zpoždění.

Měřit se dá přímo. Projektor si drží checkpoint, tedy pozici poslední zpracované události.
Proti němu stojí hlava streamu. Rozdíl obou hodnot je **projection lag** a dá se vyjádřit
dvojím způsobem: počtem nezpracovaných událostí a stářím té nejstarší z nich. První číslo
říká, kolik práce zbývá, druhé, co uvidí uživatel. Do monitoringu patří obojí, protože
projekce zaseknutá na jedné chybné události má lag v událostech malý a ve vteřinách rostoucí.

Rebuild je druhá provozní situace, kterou je lepší naplánovat dřív, než nastane. Přehrání
celého streamu do prázdné tabulky trvá tím déle, čím delší stream je, a po celou dobu není
projekce použitelná. Osvědčený postup:

1. Nová tabulka nebo schéma vedle stávající projekce, plnění z počátku streamu (blue/green).
2. Čtení mezitím obsluhuje stará tabulka, dokud nová nedožene hlavu streamu.
3. Přepnutí je přejmenování tabulky nebo změna konfigurace čtecí strany, tedy operace v řádu milisekund.
4. Stará tabulka zůstává ještě jeden provozní den jako záchranná cesta zpět.

Rebuild čte a zapisuje naplno, takže bez omezení dokáže zahltit tutéž databázi, ze které
čtou uživatelé. Řešení jsou dvě: pustit rebuild proti replice a zapisovat do oddělené
instance, nebo dávku brzdit. Pevná velikost dávky a krátká pauza mezi nimi drží zatížení
pod kontrolou za cenu delšího běhu.

Poslední otázka bývá, kdy projekci naopak zrušit. Pokud dotaz nad write modelem doběhne
v jednotkách milisekund a data se čtou jednou za den, projekce přidává provozní náklad
bez užitku. Tabulka, kterou nikdo nečte, ale kterou udržuje projektor, je čistá ztráta.

### Snapshotting v Event Sourcingu (přehled) {#snapshotting-prehled-heading}

Při Event Sourcingu (kapitola [Event Sourcing](/event-sourcing)) je rebuild stavu
agregátu lineární s počtem eventů. Pro agregát s 100 eventy je to okamžité; pro
1000 eventů to začíná být znát; pro 100k+ eventů (long-lived agregát jako
`UserAccount` po letech provozu) je hydration nepoužitelná.

**Snapshot** je zhuštěný stav agregátu uložený periodicky:

- Po každých N eventech se uloží `Snapshot{aggregateId, version, state}`. Hodnoty kolem 50 až 100 jsou rozšířená pracovní heuristika, ne naměřené optimum. To závisí na velikosti stavu a poměru čtení k zápisu.
- Při hydration se načte poslední snapshot + jen eventy *novější* než snapshot version.
- Tradeoff: rychlejší read, ale snapshot tabulka roste a její struktura je vázaná
  na konkrétní verzi agregátu (schema evolution problém, viz
  [Event Sourcing – verzování](/event-sourcing#verzovani-udalosti)).

Detailní implementace včetně Symfony kódu je v sekci
[Event Sourcing – Snapshotting](/event-sourcing#snapshotting). V kontextu výkonu
si pamatujte: **snapshot není výchozí volba, ale úniková páka pro dlouho žijící
agregáty**. Většina DDD agregátů má desítky eventů za celý životní cyklus a snapshotting
nepotřebuje.

## 16.10 Profiling DDD aplikací {#profiling}

Úzké místo nepoznáte bez měření. Pro PHP/Symfony jsou po ruce čtyři vrstvy nástrojů:
vývojový profiler, produkční profiling, programatický logger SQL dotazů a komponenta Stopwatch.
Ta poslední je nejlevnější: běží bez rozšíření i v produkci a změří věci, které Profiler nevidí –
třeba dobu jednoho běhu projektoru nebo zpracování jedné Messenger zprávy.

### Symfony Profiler (Web Debug Toolbar)

Ve vývojovém prostředí odhaluje N+1 a pomalé dotazy nejdřív Symfony Profiler
(aktivní při `APP_ENV=dev`). Panel **Doctrine** zobrazuje:

- Celkový počet SQL dotazů za request. Nadměrné číslo signalizuje N+1 problém.
- Dobu trvání každého dotazu; pomalé dotazy vyžadují index nebo přepis.
- Kompletní SQL s parametry, takže dotaz jde rovnou vyzkoušet v databázovém klientovi.
- Stack trace pro každý dotaz, který ukáže, která část kódu dotaz vydala.

### Doctrine query logging

V dev prostředí pokrývá počítání dotazů panel Doctrine v Profileru; zapíná ho
`doctrine.dbal.logging: true`. Vlastní middleware má smysl jinde. V integračním testu, kde na počet dotazů míří
aserce („načtení seznamu objednávek nesmí vydat víc než dva dotazy“). Nebo při
ladění dávky, která v Profileru vůbec neskončí.

:::callout{type="pattern"}
### Kostra middleware pro počítání dotazů

:::code{language="php" filename="src/SharedKernel/Infrastructure/Doctrine/QueryCountingMiddleware.php + CountingConnection.php + CountingStatement.php"}
<?php

declare(strict_types=1);

// Od DBAL 3.2 se logging provádí přes Middleware; SQLLogger je deprecated a DBAL 4 ho odstranil.

namespace App\SharedKernel\Infrastructure\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class QueryCountingMiddleware implements Middleware
{
    private int $queryCount = 0;

    public function wrap(Driver $driver): Driver
    {
        return new class ($driver, $this) extends AbstractDriverMiddleware {
            public function __construct(Driver $driver, private readonly QueryCountingMiddleware $counter)
            {
                parent::__construct($driver);
            }

            /** @param array<string, mixed> $params */
            public function connect(array $params): DriverConnection
            {
                return new CountingConnection(parent::connect($params), $this->counter);
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

/**
 * Obalové třídy jsou mechanická delegace; počítá se v nich jediné místo,
 * kudy dotaz projde. Bez nich middleware nic nenapočítá a aserce na počet
 * dotazů projde vždy - i po odstranění fetch joinu.
 */
final class CountingConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        DriverConnection $connection,
        private readonly QueryCountingMiddleware $counter,
    ) {
        parent::__construct($connection);
    }

    public function prepare(string $sql): Statement
    {
        return new CountingStatement(parent::prepare($sql), $this->counter);
    }

    public function query(string $sql): Result
    {
        $this->counter->increment();

        return parent::query($sql);
    }

    public function exec(string $sql): int|string
    {
        $this->counter->increment();

        return parent::exec($sql);
    }
}

final class CountingStatement extends AbstractStatementMiddleware
{
    public function __construct(
        Statement $statement,
        private readonly QueryCountingMiddleware $counter,
    ) {
        parent::__construct($statement);
    }

    public function execute(): Result
    {
        $this->counter->increment();

        return parent::execute();
    }
}
:::
:::

Middleware se registruje jako služba se štítkem `doctrine.middleware`. V testu pak stačí
zavolat `reset()` před scénářem a `getQueryCount()` po něm. Aserce na počet dotazů odhalí
regresi typu „někdo odstranil fetch join“ dřív než produkční monitoring.

### Blackfire.io pro produkční profiling

Pro profiling v produkčním nebo stagingovém prostředí se v PHP používá Blackfire.io.
Blackfire zachytí kompletní call graph každého requestu nebo CLI příkazu, včetně přesného
měření doby trvání, počtu volání a paměťové stopy pro každou funkci. Umožňuje psát *výkonnostní testy*
(Blackfire Builds) jako součást CI/CD pipeline a tím předcházet výkonnostním regresím.

:::callout{type="pattern"}
### Interpretace SQL dotazů v Symfony Profileru – praktický postup

1. Otevřete Symfony Profiler panel **Doctrine** a seřaďte dotazy podle doby trvání.
2. Vezměte dvě čísla, ne jedno: nejpomalejší dotaz a součet času stráveného v databázi za celý request. Sto rychlých dotazů po dvou milisekundách bolí stejně jako jeden dvousetmilisekundový, ale řeší se jinak – první je N+1, druhý chybějící index.
3. U podezřelého dotazu zkopírujte SQL a spusťte `EXPLAIN ANALYZE` v databázi. Práh 100 ms je pracovní konvence, ne hranice daná měřením.
4. Hledejte `Seq Scan` (PostgreSQL) nebo `Full Table Scan` (MySQL/MariaDB), které signalizují chybějící index.
5. Zkontrolujte, zda se opakují strukturálně stejné dotazy lišící se pouze parametrem – typický příznak N+1 problému.
6. Pro N+1 přidejte fetch join (alias asociace v SELECT) do příslušného repozitáře nebo přepište dotaz na read model (DTO).
:::

### Runtime optimalizace Symfony {#runtime-optimalizace-heading}

Jedna skupina zásahů se doménového modelu nedotkne vůbec a přitom zrychlí každý request:
nastavení běhového prostředí. Symfony pro produkci doporučuje OPcache s předehřátím
(`opcache.preload` mířící na `config/preload.php`), vypnutou kontrolu časových razítek
(`opcache.validate_timestamps=0`), dostatečnou `opcache.memory_consumption`
a `opcache.max_accelerated_files` nad počtem souborů projektu. K tomu patří zvětšená realpath
cache (`realpath_cache_size`, `realpath_cache_ttl`), autoloader vygenerovaný přes
`composer dump-autoload --no-dev --classmap-authoritative` a v konfiguraci kontejneru
`.container.dumper.inline_factories: true`.

Žádná z těchto voleb nezmění ani řádek doménového kódu. Než tedy padne rozhodnutí rozdělit agregát
kvůli výkonu, vyplatí se ověřit, že aplikace v produkci vůbec běží s předehřátým OPcache.
Jinak měření vypovídá o čemkoli jiném než o doménovém modelu.

:::callout{type="warn"}
### Co měřit, než začnete optimalizovat

Optimalizujte pouze podle naměřených dat. Čtyři čísla, která tato kapitola používá
a která mají smysl sledovat trvale:

- **Počet SQL dotazů na request** – skokový nárůst znamená N+1, ne pomalou databázi.
- **Celkový čas strávený v databázi na request** – doplňuje předchozí číslo a odděluje „hodně dotazů“ od „jeden pomalý“.
- **Projection lag** – počet nezpracovaných událostí a stáří té nejstarší.
- **Spotřeba paměti workeru mezi restarty** – roste-li lineárně s počtem zpráv, chybí `clear()`.

Každá optimalizace zvyšuje složitost kódu. Pokud profiler ukazuje, že daný kód problém
nezpůsobuje, ponechte jej v čitelné, doménově srozumitelné podobě.
:::

Tři páky výkonu v DDD: hranice agregátů, read model a profiling. Pořadí, ve kterém je řešit,
je opačné – nejdřív měřit, pak oddělit read od write přes CQRS, pak doladit hranice agregátů
a eliminovat N+1. Pokračováním je kapitola
[Testování DDD](/testovani-ddd).

:::faq{}
- question: Zpomaluje DDD aplikaci oproti CRUD?
  answer: 'Samotné DDD výkon nesnižuje. Doménové třídy jsou čistý PHP bez runtime režie. Zpomalení nastává, když je špatně navržený agregát (načte víc dat, než je třeba). Další příčinou je chybějící read model v CQRS nebo nesprávné použití Doctrine lazy loadingu, které vede k N+1 dotazům. Explicitní hranice naopak optimalizaci usnadňují: je zřejmé, co se načítá kvůli invariantu a co jen kvůli zobrazení. Viz <a href="#uvodem">sekci Výkon v kontextu DDD</a>.'
- question: Jak v DDD řešit N+1 problém s agregáty?
  answer: 'N+1 vzniká, když se pro načtený rodičovský objekt doplňkově dotazuje na každý vnitřní prvek. První volbou je fetch join v DQL (<code>SELECT o, i FROM Order o JOIN o.items i</code>) v metodě repozitáře. Pro čtení dat do UI bývá ještě přímočařejší denormalizovaný read model, který ORM lazy loading vynechá úplně. Až poslední volbou je <code>fetch: ''EAGER''</code> v mapování: u kolekcí nevydá JOIN, ale druhý dotaz po dávkách (výchozí velikost 100), a platí globálně i pro dotazy, které asociaci nepotřebují. Rozbor řešení v <a href="#n-plus-1-problem">sekci N+1 problém</a>.'
- question: Má velikost agregátu vliv na výkon?
  answer: 'Ano, zásadně. Příliš velký agregát načítá při každé operaci desítky vnitřních entit a vede k častým konfliktům optimistického zamykání. Správně zvolený agregát drží jen to, co musí být konzistentní v jedné transakci. Když dvě části agregátu nesdílejí invariant, jde zpravidla o dva samostatné agregáty. Rozdělení zvýší paralelismus i rychlost operací. Podrobný rozbor v <a href="#agregat-hranice">sekci Agregát a výkon</a>.'
- question: Jak optimalizovat read model v CQRS?
  answer: 'Read model se navrhuje přímo pro daný dotaz. Denormalizované tabulky odpovídají tvaru UI, nikoli doménovému modelu. Typické optimalizace jsou dedikované indexy pro konkrétní filtry, materializované projekce místo JOIN dotazů nad write modelem nebo replikace read modelu na jiný datový stroj (Elasticsearch, Redis). Read model lze rebuildnout z událostí, takže změna schématu nevyžaduje klasickou migraci. Detailní rozbor v <a href="#read-model-optimalizace">sekci Optimalizace read modelu</a>.'
- question: Je lepší UUID, nebo integer primární klíč z pohledu výkonu?
  answer: 'Integer klíč je rychlejší v indexech a zabírá méně místa, ale vyžaduje auto-increment generovaný databází. UUID umožňuje vygenerovat identitu v doméně bez round-tripu do DB, a přesně to DDD vyžaduje: agregát dostane ID před persistencí. Výkonový rozdíl závisí na databázovém stroji, šířce indexu a poměru zápisů ke čtení, takže obecné číslo neexistuje. U UUID v7 ale odpadá hlavní nevýhoda náhodných UUID, tedy fragmentace B-tree indexu. Pro DDD se UUID doporučuje. Srovnání obou variant v <a href="#uuid-vs-integer">sekci UUID vs. integer primární klíče</a>.'
:::
