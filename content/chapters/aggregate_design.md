---
route: aggregate_design
path: /navrh-agregatu
title: Návrh agregátu
page_title: "Návrh agregátu v DDD: hranice, invarianty, transakce | DDD Symfony"
meta_description: "Praktický průvodce návrhem agregátu v Domain-Driven Design: kde vést hranici, jak velký agregát skutečně potřebujete, jeden agregát na transakci, eventual consistency, optimistický zámek, mapování v Doctrine ORM, large-collection problem a hot aggregates."
meta_keywords: "aggregate design, návrh agregátu, hranice agregátu, transakční konzistence, eventual consistency, optimistický zámek, invarianty, Vaughn Vernon, Doctrine, Symfony 8, hot aggregate, large collection, snapshot, Domain-Driven Design"
og_type: article
published: "2026-04-30"
modified: "2026-04-30"
breadcrumb_name: Návrh agregátu
schema_type: TechArticle
schema_headline: "Návrh agregátu v DDD: hranice, invarianty, transakce"
chapter_number: "07"
category: Taktika
deck: "Hranice agregátu rozhoduje o transakční konzistenci, velikosti zámků a o tom, zda projekt obstojí v provozu. Tato kapitola shrnuje pravidla z Vernonovy trilogie <em>Effective Aggregate Design</em>, ukazuje konkrétní mapování v Doctrine ORM a věnuje se obtížným tématům, která většina příruček mlčky přeskočí: large-collection problem, hot aggregates, snapshoty v Event Sourcingu, partitioning a strategie referencování napříč agregáty."
reading_time: 35
difficulty: 4
---

Agregát je nejnáročnější taktický vzor v DDD, protože jeho hranice je kompromis mezi
konzistencí, výkonem a škálovatelností. Eric Evans mu věnoval šestou kapitolu své knihy z roku
2003 [[1]](https://www.dddcommunity.org/book/evans_2003/),
Vaughn Vernon třídílnou esej z roku 2011 [[2]](https://www.dddcommunity.org/library/vernon_2011/)
a celou desátou kapitolu *Implementing Domain-Driven Design* z roku 2013
[[3]](https://www.informit.com/store/implementing-domain-driven-design-9780321834577).
Vlad Khononov v knize *Learning Domain-Driven Design* (2021) shrnuje praktická vodítka
z dalšího desetiletí provozu [[4]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/).
Tato kapitola navazuje na [Základní koncepty](/zakladni-koncepty) a předchází
[CQRS](/cqrs), [Event Sourcing](/event-sourcing)
a [Ságy](/sagy-a-process-managery).

## 07.01 Proč existují agregáty {#why-aggregates}

Agregát je shluk doménových objektů, které jsou pro vnější svět nedělitelnou jednotkou
konzistence. Eric Evans ho zavedl jako odpověď na dvě otázky, které objektově orientovaný
model neřeší sám od sebe. První: *kdo je zodpovědný za vymáhání invariantů*.
Druhá: *co se uloží v jedné transakci*. Vstupním bodem do agregátu je **kořen agregátu**
(aggregate root); ostatní objekty uvnitř hranice nesmí být pro zbytek aplikace přímo dostupné.

Bez explicitní hranice doménový model degraduje dvěma směry. Buď se objektový graf rozroste
a pokrývá celou doménu jediným transakčním kontextem (typicky přes obousměrné OneToMany
relace v Doctrine), což přináší zámky a deadlocky. Nebo se naopak rozpadne na anemicky
tenké objekty, u nichž nikdo nevymáhá invarianty a logika se rozteče po službách. Agregát
tyto dva extrémy řeší kompromisem: malá konzistentní jednotka plus jasné pravidlo, jak se mění.

:::callout{type="note"}
Agregát definuje hranici jedné transakce. Co je uvnitř, mění se společně a okamžitě
konzistentně. Co je vně, mění se eventuálně konzistentně přes doménové události.
Rozhodnutí o hranici je tedy zároveň rozhodnutím o výkonu, dostupnosti a uživatelské
zkušenosti. Pat Helland v eseji *Life Beyond Distributed Transactions* (2007)
[[5]](https://queue.acm.org/detail.cfm?id=3025012)
ukázal, že tento kompromis je v distribuovaných systémech nevyhnutelný – DDD jen dává
jeho doménovou interpretaci.
:::

:::diagram{fig="07.1-A" title="Hranice agregátu Order vs. Customer" src="images/diagrams/21_aggregate_design/aggregate_boundary.svg"}
:::

## 07.02 Čtyři pravidla podle Vaughna Vernona {#vernon-rules}

Vaughn Vernon shrnul nejčastější pasti návrhu agregátu do série tří esejů
*Effective Aggregate Design* z roku 2011 [[2]](https://www.dddcommunity.org/library/vernon_2011/).
Doporučení vycházejí z analýzy reálných projektů, kde příliš velké agregáty zablokovaly
výkon a kde příliš malé rozbily invarianty. Čtyři pravidla, která doporučuje aplikovat v pořadí:

1. **Modelujte true invarianty uvnitř konzistenční hranice.** Pokud pravidlo
   musí platit v každý okamžik (například „součet položek faktury se rovná celkové ceně"),
   patří dovnitř jednoho agregátu. Pokud pravidlo smí být porušené po několik sekund
   (například „uživateli s podpisem smlouvy se odešle vítací e-mail"), eventual consistency stačí.
2. **Navrhujte malé agregáty.** Výchozí volba je agregát s jediným kořenovým
   objektem a několika hodnotovými objekty. Větší agregát potřebuje konkrétní obhajobu
   invariantem, ne pohodlí ORM nebo mentální setrvačnost vrstveného CRUD.
3. **Reference mezi agregáty pouze přes identitu.** Místo objektové reference
   uložte `OrderId`, `CustomerId`. Doctrine asociace mezi agregáty
   je signál, že někde chybí hranice nebo že eventual consistency čeká na zavedení.
4. **Eventual consistency mimo hranici.** Změnu napříč agregáty řešte doménovou
   událostí a samostatnou transakcí. „Když se X stane v agregátu A, sága upraví agregát B."
   Toto je jediná správná cesta, jak několik agregátů koordinovat.

Khononov v *Learning DDD* (2021) dodává páté pravidlo, které z Vernonových implicitně
plyne, ale stojí za výslovné formulování: **jeden command modifikuje právě jeden agregát.**
Pokud se v jednom command handleru objeví dvě volání `save()` na různé repozitáře,
buď chybí hranice (mají to být dva commandy), nebo chybí sága (má to být dvoufázový proces).

## 07.03 Invarianty jako východisko návrhu {#invariants}

Hranici agregátu nelze odvodit z databázového schématu, ER diagramu ani z existujícího kódu.
Začíná se identifikací invariantů – pravidel, která musí platit v každý okamžik, jinak je
doménový model nekonzistentní. Cockburn ve své práci *Use Cases, Ten Years Later* (2002)
ukázal, že invarianty jsou ve skutečnosti predikáty na vstupech a výstupech operací; v DDD
se přesouvají z dokumentace do typového systému jazyka. Typické zdroje invariantů:

- **Sumační pravidla.** Součet položek odpovídá celkové ceně. Počet
  rezervovaných míst nepřekračuje kapacitu. Bilance debetů a kreditů je nulová.
- **Stavové přechody.** Faktura ve stavu `PAID` nelze vrátit
  do stavu `DRAFT`. Objednávka po `SHIPPED` nelze stornovat
  bez kompenzační operace.
- **Existenční pravidla.** Faktura musí mít alespoň jednu položku. Tým
  musí mít alespoň jednoho administrátora.
- **Kvantitativní limity.** Maximální počet účastníků v týmu. Limit slevy
  v procentech z ceny objednávky. Maximální výše úvěru pro daný kreditní rating.
- **Vzájemné závislosti polí.** Pokud je `type = SUBSCRIPTION`,
  `renewalDate` nesmí být null. Pokud je `shippingMethod = PICKUP`,
  `address` může být null.

Pro každý invariant odpovězte na otázku: *musí být porušení nemožné v každý okamžik,
nebo stačí, aby bylo opraveno do několika sekund?* První kategorie definuje hranici
agregátu. Druhá patří mimo ni a řeší ji sága nebo process manager (kapitola
[Ságy a Process Managery](/sagy-a-process-managery)).

:::callout{type="pattern"}
**Postup objevení invariantů**

1. Z [Event Stormingu](/event-storming) vyberte všechny
   červené sticky (Hot Spots) a žluté sticky (pravidla).
2. Pro každé pravidlo zformulujte větu „v každý okamžik musí platit, že …".
   Pokud věta nedává smysl bez slova „eventuálně", je to kandidát na ságu.
3. Nakreslete předběžný graf entit. Spojte invarianty s entitami, kterých se týkají.
4. Hranicí agregátu obkreslete shluky entit, které sdílejí jeden invariant.
   Shluky bez sdíleného invariantu jsou samostatné agregáty.
5. Otestujte hranici otázkou „kolik dotazů musí proběhnout pro načtení agregátu
   v nejhorším případě?". Více než stovky řádků z DB znamená příliš velký agregát.
:::

## 07.04 Velikost agregátu a její dopady {#aggregate-size}

Velký agregát zní bezpečně – „raději víc v jedné transakci než riziko nekonzistence". V praxi
ale platí opak. Tři důvody:

- **Konkurence.** Větší agregát = větší zámek = více konfliktů mezi uživateli.
  Pokud `Project` drží všechny `Task`y, dvě paralelní úpravy úkolů
  si konkurují, i když spolu věcně nesouvisejí. V e-shopovém kontextu má jeden zákazník typicky
  jednu objednávku v rozpracovaném stavu, takže `Order` jako agregát s desítkami
  `OrderItem` je v pořádku. Naproti tomu `Project` s tisícem
  `Task` dává každému členovi týmu šanci na konflikt s každým jiným.
- **Paměť a IO.** Při načtení agregátu se vyhydrátuje celá hranice.
  `Project` s tisícem úkolů znamená tisíc řádků v každé operaci, i když
  měníme jediný úkol. V Doctrine to navíc zhoršují asociace s lazy loadingem, které
  generují N+1 dotazů.
- **Kompozitní invarianty.** Velký agregát zákonitě obsahuje pravidla, která
  spolu nesouvisejí. Jakákoli změna jedné části vyžaduje ověření všech invariantů – nárůst
  složitosti je kvadratický. Čím více pravidel agregát chrání, tím složitější je každá
  operace – při každé změně je nutné ověřit všechny invarianty najednou.

Praktická heuristika: pokud nemáte konkrétní invariant, který by si *vynutil* vzájemnou
přítomnost dvou entit v jedné transakci, jsou to dva agregáty. „Pohodlí" Doctrine asociace
není doménový důvod.

:::callout{type="anti"}
**Anti-vzor: God Aggregate**

**God Aggregate** je agregát, do kterého se postupně přidaly všechny
entity, jež s kořenem *nějak* souvisejí. Příznak: kořen má 10+ asociací, načtení
jednoho agregátu generuje desítky JOIN, jakákoli operace způsobuje velký commit.

Náprava: pro každý sloupec v entitě uvnitř agregátu odpovězte „mění se jeho hodnota
pouze ve stejné transakci jako kořen, nebo i samostatně?". Sloupce s nezávislým životním
cyklem patří do separátního agregátu a referencují se přes ID.
:::

## 07.05 Transakční konzistence: jeden agregát na transakci {#transactional-consistency}

Pravidlo „jeden agregát na transakci" je jedno z nejpřísnějších v DDD a v Symfony projektech
se porušuje nejčastěji. Důvody pravidla:

- Transakční hranice je kontrakt. Pokud spolu dva agregáty mění stav v jedné transakci,
  prakticky se z nich stává jeden agregát – jen rozdělený do dvou tříd.
- Atomická úprava napříč agregáty znemožňuje pozdější rozdělení do microservices nebo
  jiného Bounded Contextu. Hranice agregátu je hranice škálování.
- Optimistický zámek (`#[ORM\Version]` v Doctrine) chrání jeden agregát; rozšíření
  na více agregátů končí v pesimistickém zámku, který výrazně snižuje výkon a zvyšuje pravděpodobnost
  deadlocků.
- Helland v *Life Beyond Distributed Transactions*
  [[5]](https://queue.acm.org/detail.cfm?id=3025012)
  ukazuje, že distributed transactions (XA, two-phase commit) v praxi nefungují udržitelně –
  jediný udržitelný přístup je „one entity per transaction", což je přesně Vernonovo pravidlo.

V Symfony 8 to znamená: `EntityManager::flush()` uvnitř command handleru by měl
ukládat změny *jednoho* agregátu. Změna v dalším agregátu patří do separátního
handleru, spuštěného přes Messenger po publikaci doménové události.

:::code{language="php" filename="src/Banking/Application/TransferMoneyHandler.php (ANTI-VZOR)" highlights="22,23,24,25,26"}
<?php

declare(strict_types=1);

namespace App\Banking\Application;

use App\Banking\Domain\Account\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;

// ANTI-VZOR: transakce přes dva agregáty
final class TransferMoneyHandler
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(TransferMoney $cmd): void
    {
        $this->em->wrapInTransaction(function () use ($cmd): void {
            $source = $this->accounts->get($cmd->sourceId);
            $target = $this->accounts->get($cmd->targetId);

            $source->withdraw($cmd->amount);  // změna agregátu A
            $target->deposit($cmd->amount);   // změna agregátu B

            // Doctrine flush() commitne obojí atomicky.
            // Vypadá to bezpečně, ale ve skutečnosti:
            //   1) zámek napříč dvěma agregáty zabíjí škálování,
            //   2) deadlock při concurrent transferech (A→B vs. B→A),
            //   3) tato třída nelze rozdělit na microservices,
            //   4) chybí auditní stopa o pokusu o převod (selhání = nic se nestalo).
        });
    }
}
:::

:::code{language="php" filename="src/Banking/Application/InitiateTransferHandler.php" highlights="21,22,23,24,25"}
<?php

declare(strict_types=1);

namespace App\Banking\Application;

use App\Banking\Domain\Account\AccountRepository;
use App\Banking\Domain\Transfer\TransferId;

// SPRÁVNĚ: jeden agregát na transakci, sága přes doménovou událost
final class InitiateTransferHandler
{
    public function __construct(
        private readonly AccountRepository $accounts,
    ) {}

    public function __invoke(InitiateTransfer $cmd): void
    {
        $source = $this->accounts->get($cmd->sourceId);

        // Withdraw publikuje event MoneyWithdrawn(transferId, sourceId, targetId, amount).
        // Druhý handler (TransferSaga) reaguje a v separátní transakci provede deposit
        // na cílovém účtu, případně kompenzaci (refund) při selhání.
        $source->withdraw($cmd->amount, $cmd->targetId, $cmd->transferId);

        $this->accounts->save($source);
        // Optimistický zámek na $source brání souběžným withdraw.
        // Pokud by paralelně přišel jiný TransferMoney, druhý dostane
        // OptimisticLockException a celá operace se může zopakovat.
    }
}
:::

:::diagram{fig="07.5-A" title="Tok transakce: jeden agregát na transakci + sága" src="images/diagrams/21_aggregate_design/transaction_flow.svg"}
:::

## 07.06 Eventual consistency mezi agregáty {#eventual-consistency}

Eventual consistency je strašák u týmů, které z monolitické CRUD aplikace přecházejí k DDD.
V praxi je to nástroj, který nahrazuje transakci napříč agregáty čtyřmi explicitními kroky:

1. Kořen agregátu A vykoná operaci a publikuje doménovou událost (např. `OrderPlaced`).
2. Outbox Pattern (kapitola [Outbox](/outbox-pattern)) zajistí, že
   událost se spolehlivě dostane do message brokera, i když selže jiný komponent.
3. Handler nebo sága přijme událost a v *separátní* transakci modifikuje agregát B.
4. Pokud krok 3 selže, sága vykoná kompenzaci nebo retry; doména je explicitně připravena
   na chvilkovou nekonzistenci.

Otázka zní: *jak dlouho smí nekonzistence trvat?* Většina byznys procesů snese
řádově sekundy (vystavení faktury po dokončení objednávky, propagace změny adresy do druhotných
kontextů). Procesy, které sekundy nesnesou, jsou kandidáty na *jeden* agregát, ne na ságu.

:::callout{type="warn"}
**Pozor na uživatelskou zkušenost**

Eventual consistency je prostá v back-endu, ale vyžaduje pozornost ve UI. Pokud uživatel
zadá objednávku a čeká stránku „Objednávka přijata", nesmí ji vidět dříve, než ji vidí
read model.

Tři osvědčené přístupy:

- **Wait-and-poll:** command vrátí ID, UI dotazuje read model
  s krátkým retry (max. 2–3 s), případně fallback na „zpracovává se".
- **Optimistic update:** UI okamžitě zobrazí očekávaný stav s indikátorem
  „pending". Po potvrzení (event z back-endu přes WebSocket) se indikátor odstraní.
- **Read your writes:** command vrátí výsledný read model přímo
  v odpovědi (synchronně dohledá projekci během request lifecyclu). Funguje
  pro málo distribuované systémy, kde projekce běží vedle write modelu.
:::

Klasickým příkladem je e-commerce checkout. Místo „v jedné transakci uložit objednávku,
srazit zásoby a poslat e-mail" se rozdělí na: agregát `Order` uloží objednávku
a publikuje `OrderPlaced` event; sága `InventoryReservationSaga` ve své
transakci sníží zásoby v agregátu `InventoryItem`; další sága
`OrderConfirmationEmailSaga` v separátní transakci vystaví e-mail. Pokud rezervace
zásob selže (zboží mezitím vyprodáno), `OrderCanceledDueToOutOfStock` event spustí
kompenzaci a stornuje objednávku.

## 07.07 Reference přes identitu, ne přes objekty {#references-by-id}

Třetí Vernonovo pravidlo zní: mezi agregáty se odkazujte jen přes identifikátor (Value Object
typu `OrderId`, `CustomerId`), nikdy přes objektovou referenci. Důvody:

- Objektová reference svádí k řetězené úpravě „`$order->getCustomer()->changeAddress(...)`" –
  v jediné transakci tak měníme dva agregáty. Programátor často ani neví, že to dělá.
- Lazy loading u Doctrine sice teoreticky odděluje načtení, prakticky ale skrývá, že druhý
  agregát musí být v paměti, aby se dotaz vykonal. Při concurrent přístupu vzniká skrytý zámek.
- Identifikátorová reference funguje stejně na monolitu, modulárním monolitu i na microservices.
  Migrace mezi těmito tvary nasazení nevyžaduje refaktoring doménového modelu, jen výměnu
  `CustomerRepository::get()` za HTTP volání.
- Identifikátor je serializovatelný. Doménová událost, která ho nese, se přenáší přes message
  broker beze ztráty informace.

:::code{language="php" filename="src/Ordering/Domain/Order/OrderId.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Order;

use Symfony\Component\Uid\Ulid;

final readonly class OrderId
{
    public function __construct(
        private string $value,
    ) {
        if (!Ulid::isValid($value)) {
            throw new \InvalidArgumentException('OrderId must be a valid ULID');
        }
    }

    public static function generate(): self
    {
        return new self((new Ulid())->toString());
    }

    public static function fromString(string $value): self
    {
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
:::

:::code{language="php" filename="src/Ordering/Domain/Order/Order.php" highlights="21,56,57,58,59,60,73,74,75,76,77,78,79,80"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Order;

use App\Customers\Domain\Customer\CustomerId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Money;

final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items = [];

    private OrderStatus $status;

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId, // POZOR: ID, ne objekt Customer
        ShippingAddress $shippingAddress,
    ) {
        $this->status = OrderStatus::Draft;
        $this->shippingAddress = $shippingAddress;
    }

    public static function place(
        CustomerId $customerId,
        ShippingAddress $shippingAddress,
        OrderItemDraft ...$drafts,
    ): self {
        if ($drafts === []) {
            throw new EmptyOrderException();
        }

        $order = new self(OrderId::generate(), $customerId, $shippingAddress);

        foreach ($drafts as $draft) {
            $order->addItem($draft);
        }

        $order->record(new OrderPlaced(
            $order->id,
            $order->customerId,
            $order->totalAmount(),
            new \DateTimeImmutable(),
        ));

        return $order;
    }

    public function ship(ShipmentId $shipmentId): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new InvalidStateTransition(
                "only paid orders can be shipped, current state: {$this->status->value}"
            );
        }

        $this->status = OrderStatus::Shipped;
        $this->record(new OrderShipped($this->id, $shipmentId, new \DateTimeImmutable()));
    }

    public function totalAmount(): Money
    {
        return array_reduce(
            $this->items,
            static fn(Money $sum, OrderItem $item) => $sum->add($item->subtotal()),
            Money::zero(Currency::CZK),
        );
    }

    private function addItem(OrderItemDraft $draft): void
    {
        // INVARIANT: jedna položka na produkt – sčítáme quantity, neduplikujeme
        foreach ($this->items as $existing) {
            if ($existing->productId->equals($draft->productId)) {
                $existing->increaseQuantity($draft->quantity);
                return;
            }
        }

        $this->items[] = OrderItem::fromDraft(OrderItemId::generate(), $draft);
    }
}
:::

Konstruktor je `private`: vznik agregátu řídí
statická factory metoda `place()`, která vymáhá invariant „objednávka musí mít
alespoň jednu položku". `customerId` je hodnotový objekt, ne reference na entitu.
Stavový přechod `ship()` je jediný způsob, jak změnit `status`;
`OrderStatus` se nikdy nenastavuje setterem zvenčí.

Stavové přechody tvoří uzavřený graf, který musí být explicitně vymodelovaný. Každá
doménová operace odpovídá hraně grafu; cesty, které v grafu chybí, nejsou jen „nezatím
implementované" – jsou explicitně zakázané. Životní cyklus agregátu `Order`
ilustruje následující diagram:

:::diagram{fig="07.7-A" title="Stavový diagram agregátu Order" src="images/diagrams/21_aggregate_design/order_states.svg"}
:::

## 07.08 Mapování v Symfony 8 a Doctrine ORM 3 {#symfony-doctrine}

Doctrine ORM je v Symfony projektech defaultní volba a v jeho konfiguraci se nejčastěji
rozhoduje, zda bude agregátní model čistý, nebo se rozplyne. Vernon v IDDD věnuje této otázce
celou kapitolu 12. Pravidla pro Doctrine ORM 3:

- **Asociace pouze uvnitř agregátu.** `OneToMany` a `ManyToOne`
  používejte jen mezi entitami v hranici stejného agregátu. Reference na cizí agregát
  je vlastnost typu `CustomerId`, namapovaná jako custom Doctrine type.
- **Repository per agregát.** Jeden repozitář na jeden agregát. Repozitář vrací
  pouze kořen, nikdy vnitřní entity. `get()`, `save()`, případně
  několik specializovaných metod – ne obecné `findBy` z `EntityRepository`.
- **Optimistický zámek na kořeni.** `#[ORM\Version]` sloupec na kořeni
  agregátu. Concurrent modification vyhází `OptimisticLockException`, kterou
  aplikační vrstva překládá na retry nebo na uživatelskou chybu.
- **Doménové eventy přes outbox.** Eventy publikované agregátem se ve stejné
  transakci ukládají do outbox tabulky. Samostatný worker je odesílá do Messenger transportu
  (kapitola [Outbox](/outbox-pattern)).
- **Bez kaskádování přes hranici.** `cascade={"persist","remove"}`
  mezi agregáty je skrytá transakce. Kaskáda je v pořádku jen uvnitř agregátu pro vlastní entity.
- **Embedded value objects.** Hodnotové objekty s více poli (Money, Address)
  mapujte přes `#[ORM\Embedded]`. Žádné samostatné tabulky pro VO.

:::code{language="php" filename="src/Shared/Infrastructure/Doctrine/Type/OrderIdType.php"}
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Ordering\Domain\Order\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class OrderIdType extends Type
{
    public const NAME = 'order_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 26, 'fixed' => true]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderId
    {
        return $value === null ? null : OrderId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof OrderId ? $value->toString() : null;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
:::

:::code{language="php" filename="src/Ordering/Domain/Order/Order.php (mapování)" highlights="22,32,33,34,35,36,37,38,39,41,42,43,44,45"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Order;

use App\Customers\Domain\Customer\CustomerId;
use App\Shared\Domain\AggregateRoot;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'order_id')]
    public readonly OrderId $id;

    #[ORM\Column(type: 'customer_id')]
    public readonly CustomerId $customerId; // ID, ne ManyToOne na Customer entitu

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Embedded(class: ShippingAddress::class)]
    private ShippingAddress $shippingAddress;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(
        mappedBy: 'order',
        targetEntity: OrderItem::class,
        cascade: ['persist', 'remove'], // OK: kaskáda uvnitř agregátu
        orphanRemoval: true,
    )]
    private Collection $items;

    // POZOR: žádné ManyToOne na Customer – jen CustomerId.
    // Žádné ManyToOne na Product – jen ProductId v OrderItem.

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    private function __construct(...) { /* ... */ }

    // ... factory metody, doménové operace ...
}
:::

:::code{language="php" filename="src/Ordering/Infrastructure/Doctrine/DoctrineOrderRepository.php" highlights="33,34,35,36,37,38,39"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Doctrine;

use App\Ordering\Domain\Order\Order;
use App\Ordering\Domain\Order\OrderId;
use App\Ordering\Domain\Order\OrderNotFoundException;
use App\Ordering\Domain\Order\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function get(OrderId $id): Order
    {
        $order = $this->em->find(Order::class, $id);

        if ($order === null) {
            throw OrderNotFoundException::withId($id);
        }

        return $order;
    }

    public function save(Order $order): void
    {
        $this->em->persist($order);
        $this->em->flush();
        // Flush() uloží kořen + vnitřní entity (OrderItem)
        // díky cascade={"persist"} a Doctrine vyhodí OptimisticLockException,
        // pokud se @Version mezitím změnila.
    }

    // ŽÁDNÉ findAll(), findBy(), žádné metody pro čtení vnitřních entit.
    // Read modely jsou samostatné (CQRS, kapitola 13).
}
:::

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            order_id:    App\Shared\Infrastructure\Doctrine\Type\OrderIdType
            customer_id: App\Shared\Infrastructure\Doctrine\Type\CustomerIdType
            product_id:  App\Shared\Infrastructure\Doctrine\Type\ProductIdType
            money:       App\Shared\Infrastructure\Doctrine\Type\MoneyType

    orm:
        auto_generate_proxy_classes: '%kernel.debug%'
        enable_lazy_ghost_objects: true   # Doctrine ORM 3: výchozí pro nové projekty
        identity_generation_preferences:
            Doctrine\DBAL\Platforms\PostgreSQLPlatform: identity
        mappings:
            Ordering:
                type: attribute
                dir: '%kernel.project_dir%/src/Ordering/Domain'
                prefix: 'App\Ordering\Domain'
                is_bundle: false
            # ... další BC ...
:::

## 07.09 Pokročilá témata: large collection, hot aggregate, snapshoty {#advanced}

### Large-collection problem {#large-collection}

Klasický anti-vzor: agregát `Project` drží `OneToMany` kolekci úkolů.
S desítkami úkolů je to v pohodě, s tisíci je to katastrofa – každý load agregátu načte
všechny úkoly, každý add způsobí flush celé kolekce. Khononov pro tento případ definuje
tři strategie [[4]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/):

- **Rozdělit agregát.** `Project` a `Task` jsou samostatné
  agregáty. `Task` obsahuje `ProjectId`. Invariant „úkol patří
  k existujícímu projektu" se vymáhá v command handleru přes `ProjectExistsSpecification`,
  ne přes referenci v Doctrine.
- **Doctrine extra-lazy collection.** `fetch: 'EXTRA_LAZY'` umožní
  `$project->getTasks()->count()` bez načtení kolekce, případně
  `$project->getTasks()->matching($criteria)`. Použitelné, pokud agregát
  kolekci skutečně potřebuje pro invarianty (např. limit počtu úkolů na projekt).
- **Aggregate as service boundary.** Vnitřní kolekci nahradit aggregát-aware
  službou, která dotazem v repozitáři ověřuje invariant. Funguje, ale je to znamení, že
  hranice je špatně.

### Hot aggregate {#hot-aggregate}

Hot aggregate je agregát, který souběžně modifikuje mnoho uživatelů (nákupní košík během
Black Friday, sportovní výsledek, real-time hra). Optimistický zámek selhává – většina
transakcí spadne na `OptimisticLockException`, retry trvá, uživatelská zkušenost
je nepoužitelná. Přístupy:

- **Rozdělit agregát na menší.** Místo `Stadium` s tisícem sedaček
  modelujte `Section` s desítkami. Souběžné transakce se rozprostřou.
- **Přepnout na Event Sourcing.** ES eliminuje race condition na update – každý
  event je append-only. Konflikty řeší stream version (kapitola
  [Event Sourcing](/event-sourcing)).
- **Single-writer pattern.** Agregát existuje v paměti jediného procesu (actor
  model, Akka, Orleans). Symfony to nativně neumí; alternativou je Messenger s deduplicací
  přes konzistentní hash a single consumer per aggregate ID.
- **Přijmout eventual consistency uvnitř.** Například u čítačů
  (*like count*) je přesný stav nedůležitý – stačí zpožděná replikace s nepřesností
  v řádech sekund.

### Snapshoty v Event Sourcingu {#es-snapshots}

U Event-Sourced agregátů je rebuild stavu z eventů O(N) v počtu eventů. Pro agregáty
s 10 000+ eventy je to neúnosné. Snapshot ukládá serializovaný stav agregátu po každých
N eventech (typicky 100); při načtení se stav rekonstruuje od posledního snapshotu a navrch
se aplikují zbývající eventy.
Důležité detaily:

- Snapshot není autoritativní stav – jen optimalizace. Pokud serializace selže, rebuildujte
  od začátku streamu.
- Versioning snapshotu musí být kompatibilní s versioningem eventů; při změně schématu
  stavu invalidujte staré snapshoty.
- Snapshot store je oddělený od event store – agregát si snapshot „pamatuje" přes vlastní
  `SnapshotRepository`.

### Partitioning a multi-tenancy {#partitioning}

V multi-tenant prostředí se agregát typicky partitionuje podle `tenantId` – každý
tenant má své instance agregátů a operace přes tenanty jsou zakázány. Implementace:

- **Filtr na tabulkové úrovni.** Doctrine `SQLFilter` vynucuje
  `tenant_id = :current_tenant` v každém dotazu. Bezpečné, ale lze obejít
  native SQL.
- **Schema per tenant.** Každý tenant má vlastní DB schema. Bezpečnější,
  ale operace přes tenanty (např. agregátní reporty) vyžadují cross-schema dotazy.
- **DB per tenant.** Maximální izolace, ale operativně náročné. Vhodné pro
  regulované obory (zdravotnictví, finance).

U všech tří přístupů platí: `tenantId` je součást identity agregátu. Repozitář
přijímá `(tenantId, aggregateId)` a operace jednoho tenantu nikdy neovlivní jiného.

## 07.10 Strategie referencování napříč agregáty {#reference-strategies}

Reference přes ID je jasné pravidlo, ale typů ID je víc a každý má dopad na schéma a výkon.

- **UUID v4 (random).** Náhodná, distribuovaně generovatelná, neuhodnutelná.
  Nevýhoda: insertion order není seřazen, což zhoršuje I/O pattern u clustered indexů (MySQL/InnoDB).
- **ULID nebo UUID v7.** Time-ordered, distribuovaně generovatelná, řadí
  se podle času vzniku. **Doporučená volba** pro většinu nových projektů.
  Symfony 5.2+ nabízí `Symfony\Component\Uid\Ulid` prostřednictvím balíčku
  `symfony/uid`; `Uuid::v7()` přibylo v Symfony 6.2.
- **Sekvenční integer.** Krátký, lidsky čitelný, rychlý. Nevýhody: vyžaduje
  centrální generátor (DB sekvence), prozrazuje řád a počet entit, špatně se merguje
  z více DB (microservices).
- **Composite ID.** `(tenantId, naturalId)`. Vhodné pro multi-tenancy.
  Nevýhoda: každá tabulka má dvousloupcový PK, JOIN podmínky jsou složitější.
- **Natural key.** Hodnota z domény (ISBN, IČO, e-mail). Funguje, dokud doména
  hodnotu nezmění. **Nedoporučujeme** – domény své „přirozené klíče" mění
  častěji, než se zdá.

:::callout{type="pattern"}
**Doporučení**

Pro nové Symfony projekty zvolte ULID jako výchozí volbu. Časově řazená, kompatibilní
s MySQL/PostgreSQL primary keys, krátký zápis (26 znaků vs. 36 u UUID), Symfony to umí
z krabice. Komplikované referenční schéma typu „tenantId + naturalId" zaveďte teprve
tehdy, když máte konkrétní multi-tenancy požadavek.
:::

## 07.11 Postup návrhu krok za krokem {#workflow}

Návrh agregátu není kreslení tříd v IDE – je to disciplinovaný proces. Doporučený postup
v sedmi krocích, který vychází z Vernonovy metodiky a praktických zkušeností:

1. **Sepište invarianty.** Z Event Stormingu, doménových workshopů nebo
   rozhovorů s experty vytáhněte všechna pravidla. Každé zformulujte jako větu „v každý
   okamžik musí platit, že …". Pravidla, která neprojdou („eventuálně musí platit"), odložte
   – budou to ságy.
2. **Skupinujte invarianty.** Pravidla, která sdílejí stejné entity, jsou
   kandidáti na společný agregát. Pravidla, která spolu nemají nic společného, patří jinam.
3. **Identifikujte kořen.** Pro každou skupinu invariantů vyberte jednu entitu,
   která je „vstupní branou". Typicky ta s nejvyšší doménovou autoritou („Order" vs. „OrderItem").
4. **Otestujte velikost.** Spočítejte: kolik řádků DB se načte při `get()`?
   Více než stovky – příliš velké. Pokud načítáte *desítky*, jste v pořádku.
5. **Otestujte konkurenci.** Kolik souběžných změn agregátu očekáváte v peak
   provozu? Více než 5–10 transakcí za sekundu na jednu instanci agregátu = hot aggregate,
   potřebujete jednu z technik z 07.09.
6. **Definujte commandy a eventy.** Pro každý use case napište command (vstup),
   doménovou metodu na agregátu (chování) a event (výstup). Eventy publikujte explicitně
   metodou `record()`.
7. **Code review proti checklistu.** Sekce 07.12 níže má checklist s 12 body.
   Pokud agregát na jakýkoli odpoví „ne", návrh není hotový.

Reálný příklad postupu na agregátech `Project` a `Task` najdete v kapitole
[Případová studie](/pripadova-studie) – konkrétně v sekcích o Project agregátu
a Task agregátu, kde vidíte aplikaci stejného postupu na netriviální doméně správy projektů.

## 07.12 Typické chyby {#anti-patterns}

- **Velký agregát kvůli pohodlí ORM.** „Když už máme `OneToMany`,
  dáme tam i objednávku." Asociace jsou nástroj mapování, ne vodítko pro hranici.
- **Smazání transakcí přes ságu pro jednoduchá pravidla.** Pokud invariant
  musí platit okamžitě, sága ho neudržuje. Pravidlo „pojistka nikdy nesmí být zaplacena
  bez podepsané smlouvy" nesnese několik sekund čekání – patří do agregátu.
- **Vystavený mutátor uvnitř agregátu.** `$order->getItems()->add(...)`
  obchází kořen. Kolekce by měla být immutable z pohledu vnějšku; přidávání položky jde
  výhradně metodou na kořeni.
- **Sdílený stav přes službu.** Pomocná „`OrderService`", která
  zasahuje do dvou agregátů, je skrytá transakce. Pokud služba vykoná
  `$em->flush()`, jste v anti-vzoru.
- **Doménová logika v read modelu.** Read model je projekce, ne místo, kde
  žijí invarianty. Pravidla patří do write modelu, projekce jen reaguje.
- **Anemic aggregate s public settery.** Pokud má agregát pro každou vlastnost
  `get/set`, je to data structure, ne agregát. Stavové přechody musí být metody
  vyjadřující doménový záměr (`place()`, `ship()`, `cancel()`).
- **Repozitář vracející vnitřní entity.** `OrderItemRepository::get(itemId)`
  je porušení hranice. Vnitřní entity jsou dosažitelné jen přes kořen; jejich „samostatná"
  identita patří do read modelu, ne do write modelu.
- **Domain Event jako notifikace mezi vrstvami.** Event není mechanismus
  pro „když se aggregát změní, smaž cache". Eventy jsou doménová fakta, ne infrastrukturní
  signály. Cache invalidaci řešte v projekci, která event konzumuje.

## 07.13 Checklist návrhu agregátu {#checklist}

1. Sepsal jsem invarianty v ubiquitous language (slova z domény, ne z kódu).
2. U každého invariantu vím, zda musí platit okamžitě, nebo eventuálně.
3. Hranice agregátu obklopuje invarianty kategorie „okamžitě".
4. Na kořeni je optimistický zámek (`#[ORM\Version]` nebo ekvivalent).
5. Reference na cizí agregát jsou identifikátorové (Value Object), ne objektové.
6. Žádná Doctrine asociace nepřekračuje hranici agregátu.
7. Repozitář vrací jen kořen; vnitřní entity nejsou veřejně dostupné.
8. Změny napříč agregáty řeší sága nebo process manager, ne sdílená transakce.
9. UI počítá s eventual consistency tam, kde ji doména používá.
10. Kaskádové operace existují jen uvnitř agregátu.
11. Stavové přechody jsou metody vyjadřující doménový záměr, ne settery.
12. Identifikátor kořene je Value Object s validací (nikoli holý `string`/`int`).

## 07.14 Další četba {#further-reading}

- Eric Evans, *Domain-Driven Design: Tackling Complexity in the Heart of Software*, kap. 6 „Aggregates" (Addison-Wesley, 2003) [[1]](https://www.dddcommunity.org/book/evans_2003/).
- Vaughn Vernon, *Effective Aggregate Design*, Part I–III (2011) [[2]](https://www.dddcommunity.org/library/vernon_2011/) – kanonický text o pravidlech návrhu agregátu, na který odkazuje téměř každá pozdější DDD kniha.
- Vaughn Vernon, *Implementing Domain-Driven Design*, kap. 10 „Aggregates" a kap. 12 „Repositories" (Addison-Wesley, 2013) [[3]](https://www.informit.com/store/implementing-domain-driven-design-9780321834577).
- Vlad Khononov, *Learning Domain-Driven Design*, kap. 8 „Architectural Patterns" a kap. 10 „Event-Sourced Domain Model" (O'Reilly, 2021) [[4]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/).
- Pat Helland, *Life Beyond Distributed Transactions: an Apostate's Opinion*, ACM Queue (2007, reprint 2017) [[5]](https://queue.acm.org/detail.cfm?id=3025012).
- Martin Fowler, *DDD_Aggregate* (bliki) [[6]](https://martinfowler.com/bliki/DDD_Aggregate.html).
- Greg Young, *CQRS Documents* (2010) [[7]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf) – relevantní především kapitoly o Event Sourcingu a snapshotech.
- V této příručce navazují kapitoly [CQRS](/cqrs), [Event Sourcing](/event-sourcing), [Ságy a Process Managery](/sagy-a-process-managery), [Outbox Pattern](/outbox-pattern) a [Případová studie](/pripadova-studie), kde uvidíte aplikaci postupu na konkrétní doméně.

:::faq{}
- question: Jak velký má být agregát?
  answer: 'Tak velký, aby obsahoval všechny invarianty, které musí platit okamžitě, a ne větší. Výchozí volba je agregát s jedním kořenovým objektem a několika hodnotovými objekty plus volitelně několika vnitřními entitami. Větší agregát potřebuje konkrétní obhajobu invariantem, ne pohodlí ORM. Praktická heuristika: pokud načtení agregátu z DB generuje stovky řádků, je příliš velký – buď ho rozdělte, nebo přepněte vnitřní kolekce na <code>EXTRA_LAZY</code> s explicitním filtrováním přes <code>Criteria</code>. Detail v <a href="#aggregate-size">sekci Velikost agregátu</a>.'
- question: Proč nelze měnit dva agregáty v jedné transakci?
  answer: 'Technicky to lze, ale je to anti-vzor. Hranice agregátu je zároveň hranice konzistence a hranice škálování. Pokud dva agregáty pravidelně mění stav společně, je to signál, že buď tvoří jeden agregát, nebo je mezi nimi sága. Konkrétní důvody: zámek napříč agregáty zabíjí škálování, deadlocky se vyrojí při concurrent transakcích, kód nelze rozdělit do microservices, optimistický zámek přestane fungovat. Detail v <a href="#transactional-consistency">sekci Transakční konzistence</a>, alternativní řešení v <a href="#eventual-consistency">sekci Eventual consistency</a>.'
- question: Co je eventual consistency a kdy ji použít?
  answer: 'Eventual consistency znamená, že stav dvou agregátů je konzistentní s krátkým zpožděním (typicky sekundy), ne okamžitě. Použijte ji všude, kde invariant nemusí platit v každý okamžik – například „po vystavení objednávky se zákazníkovi pošle e-mail" nebo „při změně adresy v Customer agregátu se upraví doručovací adresa v rozpracovaných objednávkách". Implementačně: agregát A publikuje doménový event, sága ho přijme a v separátní transakci modifikuje agregát B. Pravidla, která musí platit okamžitě (například „bilance debetů a kreditů je nulová"), patří do jednoho agregátu. Detail v <a href="#eventual-consistency">sekci Eventual consistency</a>.'
- question: Jak v Doctrine ORM 3 namapovat referenci na jiný agregát?
  answer: 'Jako jednoduchý sloupec s vlastním Doctrine typem (<code>order_id</code>, <code>customer_id</code>), který konvertuje mezi databázovou hodnotou a Value Objectem (<code>OrderId</code>, <code>CustomerId</code>). Žádná <code>ManyToOne</code> asociace mezi agregáty. Doctrine asociace ponechte jen pro entity uvnitř stejného agregátu (typicky <code>OneToMany</code> z kořene na vnitřní entity s <code>cascade=["persist", "remove"]</code> a <code>orphanRemoval=true</code>). Hodnotové objekty s více poli (Money, Address) mapujte přes <code>#[ORM\\Embedded]</code>. Detail v <a href="#symfony-doctrine">sekci Mapování v Doctrine ORM 3</a>.'
- question: Co je hot aggregate a jak poznat, že ho mám?
  answer: 'Hot aggregate je agregát, na který se souběžně sahá z mnoha transakcí (nákupní košík během Black Friday, sportovní výsledek, real-time hra, čítač lajků na virálním příspěvku). Příznak v provozu: většina commandů selže s <code>OptimisticLockException</code>, retry trvá vteřiny, latence stoupá, uživatelská zkušenost se hroutí. Diagnosticky: pokud peak provoz překročí ~5 souběžných změn za sekundu na jednu instanci agregátu, jste v ohrožení. Detail příznaků a rozhodovací logika v <a href="#hot-aggregate">sekci Hot aggregate</a>.'
- question: Jak hot aggregate vyřešit?
  answer: 'Čtyři strategie podle povahy domény. <strong>Rozdělení na menší</strong> – místo <code>Stadium</code> s tisícem sedaček modelujte <code>Section</code> s desítkami; souběžné transakce se rozprostřou. <strong>Event Sourcing</strong> – append-only operace eliminují konflikt na update, konflikty řeší stream version (kapitola <a href="/event-sourcing">Event Sourcing</a>). <strong>Single-writer pattern</strong> – agregát existuje v paměti jediného procesu, v Symfony přes Messenger s deduplicací konzistentním hashem. <strong>Eventual consistency uvnitř</strong> – pro nekritické hodnoty (<em>like count</em>) periodicky replikujte. Volba závisí na povaze invariantu; vodítko v <a href="#hot-aggregate">sekci Hot aggregate</a>.'
- question: Jaký identifikátor zvolit pro nový agregát?
  answer: 'Pro nové Symfony projekty doporučujeme ULID (<code>Symfony\\Component\\Uid\\Ulid</code>, balíček <code>symfony/uid</code> dostupný od Symfony 5.2). Časově řazená generace zlepšuje I/O pattern v MySQL/InnoDB oproti UUID v4, distribuované vytváření odstraňuje potřebu centrálního generátoru, zápis je kratší (26 znaků vs. 36 u UUID), formát je čitelný v lidských logech. UUID v7 má srovnatelné vlastnosti a stává se standardem (RFC 9562). Sekvenční integery volte jen pro specifický důvod (lidsky čitelné číslo objednávky). Přirozené klíče (e-mail, IČO) <strong>nedoporučujeme</strong> – domény mění své „přirozené klíče" častěji, než se zdá. Srovnání všech pěti strategií v <a href="#reference-strategies">sekci Strategie referencování</a>.'
- question: Jak rychle ověřit, že hranice agregátu je správně?
  answer: 'Tři rychlé kontroly. (1) <strong>Test invariantu</strong>: existuje pravidlo, které by se porušilo, kdybyste agregát rozdělili na dva? (2) <strong>Test velikosti</strong>: načtení z DB vrací desítky, ne stovky řádků? (3) <strong>Test reference</strong>: ven z agregátu se odkazujete jen přes ID, ne přes objektovou referenci? Pokud na všechny tři odpovídáte „ano", hranice je nejspíš správná. Plný checklist s 12 body v <a href="#checklist">sekci Checklist</a>, sedmikrokový postup návrhu v <a href="#workflow">sekci Postup návrhu</a>.'
:::
