---
route: aggregate_design
path: /navrh-agregatu
title: Návrh agregátu
page_title: "Návrh agregátu v DDD: hranice a transakce | DDD Symfony"
meta_description: "Kde vést hranici agregátu, aby projekt obstál v provozu. Pravidla z Vernonovy Effective Aggregate Design, mapování v Doctrine ORM a problém hot aggregates."
meta_keywords: "aggregate design, návrh agregátu, hranice agregátu, transakční konzistence, eventual consistency, optimistický zámek, invarianty, Vaughn Vernon, Doctrine, Symfony 8, hot aggregate, large collection, snapshot, Domain-Driven Design"
og_type: article
published: "2026-04-30"
modified: "2026-07-08"
breadcrumb_name: Návrh agregátu
schema_type: TechArticle
schema_headline: "Návrh agregátu v DDD: hranice, invarianty, transakce"
chapter_number: "07"
category: Taktika
deck: "Hranice agregátu rozhoduje o transakční konzistenci, velikosti zámků a o tom, zda projekt obstojí v provozu. Tato kapitola shrnuje pravidla z Vernonovy trilogie <em>Effective Aggregate Design</em>, ukazuje konkrétní mapování v Doctrine ORM a věnuje se obtížným tématům: large-collection problem, hot aggregates, snapshoty v Event Sourcingu, partitioning a strategie referencování napříč agregáty."
reading_time: 35
difficulty: 4
---

Agregát je nejnáročnější taktický vzor v DDD, protože jeho hranice je kompromis mezi
konzistencí, výkonem a škálovatelností. Eric Evans ho popsal v šesté kapitole své knihy z roku
2003 [[1]](https://www.dddcommunity.org/book/evans_2003/).
Vaughn Vernon mu věnoval třídílnou esej (2011) [[2]](https://www.dddcommunity.org/library/vernon_2011/)
a celou desátou kapitolu *Implementing Domain-Driven Design* (2013)
[[3]](https://www.informit.com/store/implementing-domain-driven-design-9780321834577).
Vlad Khononov v knize *Learning Domain-Driven Design* (2021) shrnuje praktická vodítka
z dalšího desetiletí provozu [[4]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/).
Tato kapitola navazuje na [Základní koncepty](/zakladni-koncepty) a předchází
[CQRS](/cqrs), [Event Sourcing](/event-sourcing)
a [Ságy](/sagy-a-process-managery).

## 07.01 Proč existují agregáty {#why-aggregates}

Agregát je skupina doménových objektů, která je pro vnější svět nedělitelnou jednotkou
konzistence. Eric Evans ho zavedl jako odpověď na dvě otázky, které objektově orientovaný
model neřeší sám od sebe. První: *kdo je zodpovědný za vymáhání invariantů*.
Druhá: *co se uloží v jedné transakci*. Vstupním bodem do agregátu je **kořen agregátu**
(aggregate root); ostatní objekty uvnitř hranice nesmí být pro zbytek aplikace přímo dostupné.

Bez explicitní hranice doménový model degraduje dvěma směry. Buď se objektový graf rozroste
a pokrývá celou doménu jediným transakčním kontextem (typicky přes obousměrné OneToMany
relace v Doctrine), což přináší zámky a deadlocky. Nebo se naopak rozpadne na anemicky
tenké objekty, u nichž nikdo nevymáhá invarianty a logika se rozteče po službách. Agregát
tyto dva extrémy řeší kompromisem: malá konzistentní jednotka plus jasné pravidlo, jak se mění.

Hranice ale neurčuje jen transakci. Evans ji v *DDD Reference* (2015)
[[8]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf)
spojuje i s rozmístěním: agregát má zůstat pohromadě na jednom serveru, zatímco různé
agregáty smí být rozprostřené mezi uzly. Kde vede hranice agregátu, tam později vede
i hranice shardu nebo služby.

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
výkon a kde příliš malé rozbily invarianty. Vernon je nenazývá pravidly, ale *rules of
thumb*, tedy vodítky. Rozdíl není kosmetický: ke každému z nich sám uvádí situace, kdy se
poruší. Čtyři vodítka, která doporučuje aplikovat v pořadí:

1. **Modelujte skutečné invarianty uvnitř konzistenční hranice.** Pokud pravidlo
   musí platit v každý okamžik (například „součet položek faktury se rovná celkové ceně“),
   patří dovnitř jednoho agregátu. Pokud pravidlo smí být porušené po několik sekund
   (například „uživateli s podpisem smlouvy se odešle vítací e-mail“), eventual consistency stačí.
2. **Navrhujte malé agregáty.** Výchozí volba je agregát s jediným kořenovým
   objektem a několika hodnotovými objekty. Větší agregát potřebuje konkrétní obhajobu
   invariantem, ne pohodlí ORM nebo mentální setrvačnost vrstveného CRUD. Vernon to
   podkládá číslem z projektu, který analyzoval: zhruba 70 % agregátů tvořil samotný
   kořen s několika hodnotovými objekty, zbývajících 30 % mělo dvě až tři entity celkem.
3. **Reference mezi agregáty pouze přes identitu.** Místo objektové reference
   uložte `OrderId`, `CustomerId`. Doctrine asociace mezi agregáty
   je signál, že někde chybí hranice nebo že eventual consistency čeká na zavedení.
4. **Eventual consistency mimo hranici – po otázce, čí je to práce.** Změnu napříč
   agregáty řešte doménovou událostí a samostatnou transakcí. „Když se X stane v agregátu A,
   sága upraví agregát B.“ Dovětek o čí práci Vernon do formulace pravidla přidal ve třetím
   dílu série a rozebírá ho sekce [Invarianty](#invariants).

Khononov v *Learning DDD* (2021) dodává páté pravidlo, které z Vernonových implicitně
plyne, ale vyplatí se ho formulovat výslovně: **jedna databázová transakce mění právě jeden
agregát.** Potřeba commitnout změny ve více agregátech je podle něj signálem špatně vedené
hranice. Objeví-li se v jednom command handleru dvě volání `save()` na různé repozitáře,
stojí za to hranice prověřit: buď mají vzniknout dva commandy, nebo jde o ságu, tedy
o dvoufázový proces s vlastní transakcí pro každý krok.

## 07.03 Invarianty jako východisko návrhu {#invariants}

Hranici agregátu nelze odvodit z databázového schématu, ER diagramu ani z existujícího kódu.
Začíná se identifikací invariantů – pravidel, která musí platit v každý okamžik, jinak je
doménový model nekonzistentní. Pojetí invariantu jako predikátu pochází z Design by
Contract: Bertrand Meyer ho v *Object-Oriented Software Construction* (1997)
[[9]](https://www.informit.com/store/object-oriented-software-construction-9780136291558)
definuje jako podmínku, která platí před každou veřejnou operací objektu i po ní. Vernon tomu dává užší doménové čtení:
invariant je byznys pravidlo, které musí být konzistentní pořád, a když se o invariantech
mluví v souvislosti s agregátem, myslí se konzistence transakční. Typické zdroje:

- **Sumační pravidla.** Součet položek odpovídá celkové ceně. Počet
  rezervovaných míst nepřekračuje kapacitu. Bilance debetů a kreditů je nulová.
- **Stavové přechody.** Fakturu ve stavu `PAID` nelze vrátit
  do stavu `DRAFT`. Objednávku po `SHIPPED` nelze stornovat
  bez kompenzační operace.
- **Existenční pravidla.** Faktura musí mít alespoň jednu položku. Tým
  musí mít alespoň jednoho administrátora.
- **Kvantitativní limity.** Maximální počet účastníků v týmu. Limit slevy
  v procentech z ceny objednávky. Maximální výše úvěru pro daný kreditní rating.
- **Vzájemné závislosti polí.** Pokud je `type = SUBSCRIPTION`,
  `renewalDate` nesmí být null. Pokud je `shippingMethod = PICKUP`,
  `address` může být null.

Pro každý invariant rozhoduje jedna otázka: *musí být porušení nemožné v každý okamžik,
nebo stačí, aby bylo opraveno se zpožděním?* První kategorie definuje hranici
agregátu. Druhá patří mimo ni a řeší ji sága nebo process manager (kapitola
[Ságy a Process Managery](/sagy-a-process-managery)).

Odpověď se hledá špatně, dokud se ptáme na techniku. Vernon proto přebírá od Evanse
vodítko, které míří na uživatele: *čí je to práce udržet ta data konzistentní?*
Pokud ji má odvést uživatel, který use case spouští, patří pravidlo do jedné transakce
a tedy do jednoho agregátu. Pokud ji má odvést jiný uživatel nebo systém sám, stačí
eventual consistency. Otázka funguje proto, že odhalí skutečné invarianty domény místo
těch, které vypadají jako invarianty jen kvůli tvaru databázového schématu. Tímto sítem
projde každé pravidlo ze seznamu výše dřív, než vznikne první náčrt hranic.

:::callout{type="pattern"}
**Postup objevení invariantů**

1. Z [Event Stormingu](/event-storming) vyberte všechny
   Hot Spot sticky (purpurové/červené) a policy sticky (lila).
2. Pro každé pravidlo zformulujte větu „v každý okamžik musí platit, že …“.
   Pokud věta nedává smysl bez slova „eventuálně“, je to kandidát na ságu.
3. Nakreslete předběžný graf entit. Spojte invarianty s entitami, kterých se týkají.
4. Hranicí agregátu obkreslete shluky entit, které sdílejí jeden invariant.
   Shluky bez sdíleného invariantu jsou samostatné agregáty.
5. Otestujte hranici otázkou „kolik řádků se načte při nejhorším scénáři?“.
   Odhad spočítejte metodou z kroku 4 v sekci [Postup návrhu](#workflow);
   kolekce, jejíž růst nic neomezuje, je signál příliš velkého agregátu.
:::

## 07.04 Velikost agregátu a její dopady {#aggregate-size}

Velký agregát vypadá bezpečně: „raději víc v jedné transakci než riziko nekonzistence“. V praxi
ale platí opak. Tři důvody:

- **Konkurence.** Větší agregát = větší zámek = více konfliktů mezi uživateli.
  Pokud `Project` drží všechny `Task`y, dvě paralelní úpravy úkolů
  si konkurují, i když spolu věcně nesouvisejí. V e-shopovém kontextu má jeden zákazník typicky
  jednu objednávku v rozpracovaném stavu, takže `Order` jako agregát s desítkami
  `OrderItem` je v pořádku. Naproti tomu `Project` s tisícem
  `Task` dává každému členovi týmu šanci na konflikt s každým jiným.
- **Paměť a IO.** Při načtení agregátu se hydratuje celá hranice.
  `Project` s tisícem úkolů znamená tisíc řádků v každé operaci, i když
  měníme jediný úkol. V Doctrine to navíc zhoršují asociace s lazy loadingem, které
  generují N+1 dotazů.
- **Kompozitní invarianty.** Velký agregát obsahuje pravidla, která spolu věcně
  nesouvisejí. Každá změna musí projít validací všech naráz a režie roste
  s počtem chráněných invariantů.

Praktická heuristika: pokud nemáte konkrétní invariant, který by si *vynutil* vzájemnou
přítomnost dvou entit v jedné transakci, jsou to dva agregáty. „Pohodlí“ Doctrine asociace
není doménový důvod.

:::callout{type="anti"}
**Anti-vzor: God Aggregate**

**God Aggregate** je agregát, do kterého se postupně přidaly všechny
entity, jež s kořenem *nějak* souvisejí. Příznak: kořen má 10+ asociací, načtení
jednoho agregátu generuje desítky JOIN, jakákoli operace způsobuje velký commit.

Náprava: pro každý sloupec v entitě uvnitř agregátu odpovězte „mění se jeho hodnota
pouze ve stejné transakci jako kořen, nebo i samostatně?“. Sloupce s nezávislým životním
cyklem patří do separátního agregátu a referencují se přes ID.
:::

## 07.05 Transakční konzistence: jeden agregát na transakci {#transactional-consistency}

Pravidlo „jeden agregát na transakci“ je jedno z nejpřísnějších v DDD a v Symfony projektech
se porušuje nejčastěji. Důvody pravidla:

- Transakční hranice je kontrakt. Pokud spolu dva agregáty mění stav v jedné transakci,
  prakticky se z nich stává jeden agregát – jen rozdělený do dvou tříd.
- Atomická úprava napříč agregáty znemožňuje pozdější rozdělení do microservices nebo
  jiného Bounded Contextu. Hranice agregátu je hranice škálování.
- Optimistický zámek (`#[ORM\Version]` v Doctrine) hlídá jednu instanci agregátu, a to
  jen v rozsahu změn, které se dotknou pole na kořeni (viz sekce
  [Mapování v Doctrine](#symfony-doctrine)). Snaha pokrýt jím dva agregáty najednou končí
  u pesimistického zámku, který snižuje propustnost a zvyšuje riziko deadlocku.
- Helland v *Life Beyond Distributed Transactions*
  [[5]](https://queue.acm.org/detail.cfm?id=3025012)
  ukazuje, že distributed transactions (XA, two-phase commit) v praxi nefungují udržitelně.
  Jeho *entity* je kolekce dat, kterou lze atomicky změnit uvnitř, ale nikdy ne napříč
  hranicemi. Tutéž hranici popsal šest let před Vernonem a nezávisle na DDD.

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
            //   2) deadlock při souběžných transferech (A→B vs. B→A),
            //   3) tuto třídu nelze rozdělit na microservices,
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
        // Pokud by paralelně přišel jiný InitiateTransfer, druhý dostane
        // OptimisticLockException a celá operace se může zopakovat.
    }
}
:::

:::diagram{fig="07.5-A" title="Tok transakce: jeden agregát na transakci + sága" src="images/diagrams/21_aggregate_design/transaction_flow.svg"}
:::

### Kdy se vodítko poruší {#breaking-the-rule}

Vernon k pravidlu připojil sekci *Reasons To Break the Rules* a jmenuje v ní čtyři
situace, ve kterých zkušený tým commitne víc agregátů najednou – vždy s vědomím, co za to
platí.

1. **Pohodlí uživatelského rozhraní.** Formulář zakládá dávku instancí naráz.
   Pokud je vytvoření dávky sémanticky totéž jako opakované vytvoření po jedné,
   je porušení bez následků.
2. **Chybějící technický mechanismus.** Projekt nemá messaging, plánovač ani vlákna,
   takže eventual consistency nemá čím doručit.
3. **Vynucené globální transakce.** Podnikové prostředí předepíše 2PC a rozhodnutí
   leží mimo tým.
4. **Výkon dotazů.** Občas se vyplatí držet přímou referenci na jiný agregát, protože
   dohledání přes repozitář by dotaz zdražilo.

K tomu Vernon zavádí pojem **user-aggregate affinity**: rozhoduje, kolik uživatelů sahá
na tutéž množinu instancí ve stejný okamžik. Pracuje-li na nich v daném okamžiku jeden
jediný, riziko konfliktu je nízké a porušení levné. Sdílí-li je celý tým, roste cena
každého takového ústupku.

Khononov k tomu přidává diagnostiku, ne zákaz: potřeba commitnout změny ve více agregátech
signalizuje špatně vedenou transakční hranici. Prověření hranice tedy předchází rozhodnutí,
zda jde opravdu o jednu ze čtyř výjimek. Vernon celou sérii uzavírá poznámkou, že se pro
porušení vodítek nehledají výmluvy.

## 07.06 Eventual consistency mezi agregáty {#eventual-consistency}

Eventual consistency vyvolává obavy v týmech, které přicházejí z monolitické CRUD aplikace.
V praxi nahrazuje transakci napříč agregáty čtyřmi explicitními kroky:

1. Kořen agregátu A vykoná operaci a publikuje doménovou událost (např. `OrderPlaced`).
2. Outbox Pattern (kapitola [Outbox](/outbox-pattern)) zajistí, že
   událost se spolehlivě dostane do message brokera, i když selže jiný komponent.
3. Handler nebo sága přijme událost a v *separátní* transakci modifikuje agregát B.
4. Pokud krok 3 selže, sága vykoná kompenzaci nebo retry; doména je explicitně připravena
   na chvilkovou nekonzistenci.

Rozhodující otázka: *jak dlouho smí nekonzistence trvat?* Odpověď nepatří vývojáři,
ale doménovému expertovi, a bývá velkorysejší, než se čeká. Vernon k tomu píše, že experti
běžně připustí štědrý počet sekund, minut, hodin, někdy i dnů. Vystavení faktury po dokončení
objednávky snese minuty; propagace změny adresy do druhotných kontextů také. Teprve procesy,
u kterých expert žádné zpoždění nepřipustí, jsou kandidáty na *jeden* agregát, ne na ságu.

:::callout{type="warn"}
**Pozor na uživatelskou zkušenost**

Eventual consistency má v back-endu jasná řešení (outbox, sága), ale vyžaduje pozornost
v UI. Pokud uživatel zadá objednávku a čeká stránku „Objednávka přijata“, nesmí ji vidět
dříve, než ji vidí read model.

Tři osvědčené přístupy:

- **Wait-and-poll:** command vrátí ID, UI dotazuje read model
  s krátkým retry (max. 2–3 s), případně fallback na „zpracovává se“.
- **Optimistic update:** UI okamžitě zobrazí očekávaný stav s indikátorem
  „pending“. Po potvrzení (event z back-endu přes WebSocket) se indikátor odstraní.
- **Read your writes:** command vrátí výsledný read model přímo
  v odpovědi (synchronně dohledá projekci během request lifecyclu). Funguje
  pro málo distribuované systémy, kde projekce běží vedle write modelu.
:::

Klasickým příkladem je e-commerce checkout. Místo „v jedné transakci uložit objednávku,
srazit zásoby a poslat e-mail“ se proces rozdělí na tři kroky. Agregát `Order` uloží
objednávku a publikuje `OrderPlaced` event. Sága `InventoryReservationSaga` ve své
transakci sníží zásoby v agregátu `InventoryItem`. Potvrzovací e-mail pak odešle
`OrderConfirmationEmailSaga`, opět v samostatné transakci. Pokud rezervace zásob selže (zboží mezitím vyprodáno),
`OrderCanceledDueToOutOfStock` event spustí kompenzaci a stornuje objednávku.

## 07.07 Reference přes identitu, ne přes objekty {#references-by-id}

Třetí Vernonovo vodítko zní: mezi agregáty se odkazujte přes identifikátor (Value Object
typu `OrderId`, `CustomerId`), ne přes objektovou referenci. Důvody:

- Objektová reference svádí k řetězené úpravě „`$order->getCustomer()->changeAddress(...)`“ –
  v jediné transakci tak měníme dva agregáty. Programátor často ani neví, že to dělá.
- Lazy loading u Doctrine sice teoreticky odděluje načtení, prakticky ale skrývá, že druhý
  agregát musí být v paměti, aby se dotaz vykonal. Při souběžném přístupu vzniká skrytý zámek.
- Identifikátorová reference funguje stejně na monolitu, modulárním monolitu i na microservices.
  Migrace mezi těmito tvary nasazení nevyžaduje refaktoring doménového modelu, jen výměnu
  `CustomerRepository::get()` za HTTP volání.
- Identifikátor je serializovatelný. Doménová událost, která ho nese, se přenáší přes message
  broker beze ztráty informace.

Výjimku připouští i sám Vernon. Ve třetím dílu série jeho tým kvůli režii dotazů zvolí přímou
lazy-loaded referenci na cizí agregát a mapování k ní přizpůsobí. Hranici mezi výjimkou
a chybou drží jediná podmínka: taková reference slouží ke čtení. Jakmile se přes ni volá
doménová metoda cizího agregátu, transakce se rozlezla přes hranici a výhoda je pryč.
Evans ve stejném duchu připouští předání reference na vnitřní člen agregátu ven, ale jen
pro jedinou operaci – tedy bez uchování a bez zápisu.

:::code{language="php" filename="src/Ordering/Domain/Order/OrderId.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Order;

use Symfony\Component\Uid\Uuid;

final readonly class OrderId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('OrderId must be a valid UUID');
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

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
:::

:::code{language="php" filename="src/Ordering/Domain/Order/Order.php" highlights="22,29,30,34,35,36,59,60,61,62,63,64,65"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Order;

use App\Catalog\Domain\Product\ProductId;
use App\Customers\Domain\Customer\CustomerId;
use App\SharedKernel\Domain\Money;
use App\SharedKernel\Domain\AggregateRoot;

class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items = [];

    private OrderStatus $status;
    private ShippingAddress $shippingAddress;

    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId, // POZOR: ID, ne objekt Customer
        ShippingAddress $shippingAddress,
    ) {
        $this->status = OrderStatus::Draft;
        $this->shippingAddress = $shippingAddress;
    }

    // Invariant „objednávka má alespoň jednu položku“ vymáhá signatura:
    // bez první položky objednávka nevznikne.
    public static function place(
        CustomerId $customerId,
        ShippingAddress $shippingAddress,
        ProductId $productId,
        int $quantity,
        Money $unitPrice,
    ): self {
        $order = new self(OrderId::generate(), $customerId, $shippingAddress);
        $order->addItem($productId, $quantity, $unitPrice);

        $order->record(new OrderPlaced(
            $order->id,
            $order->customerId,
            $order->totalAmount(),
            new \DateTimeImmutable(),
        ));

        return $order;
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException(
                "items can be added only to a draft order, current state: {$this->status->value}"
            );
        }

        // INVARIANT: jedna položka na produkt – sčítáme quantity, neduplikujeme
        foreach ($this->items as $existing) {
            if ($existing->productId->equals($productId)) {
                $existing->increaseQuantity($quantity);
                return;
            }
        }

        $this->items[] = new OrderItem(
            OrderItemId::generate(),
            $productId,
            $quantity,
            $unitPrice,
        );
    }

    public function ship(ShipmentId $shipmentId): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new InvalidOrderStateTransitionException(
                "only paid orders can be shipped, current state: {$this->status->value}"
            );
        }

        $this->status = OrderStatus::Shipped;
        $this->record(new OrderShipped($this->id, $shipmentId, new \DateTimeImmutable()));
    }

    public function totalAmount(): Money
    {
        // place() prázdnou objednávku nepustí; guard kryje budoucí refaktoring,
        // aby součet nikdy nevracel tichou nulu v natvrdo zvolené měně.
        if ($this->items === []) {
            throw new EmptyOrderException();
        }

        $total = $this->items[0]->subtotal(); // měnu určuje první položka

        foreach (array_slice($this->items, 1) as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }
}
:::

Konstruktor je `private`: vznik agregátu řídí
statická factory metoda `place()`. Invariant „objednávka musí mít alespoň jednu položku“
vymáhá už její signatura – bez první položky objednávka nevznikne a runtime kontrola
je zbytečná. `customerId` je hodnotový objekt, ne reference na entitu.
Stavový přechod `ship()` je jediný způsob, jak změnit `status`;
`OrderStatus` se nikdy nenastavuje setterem zvenčí. Volání `record()` ukládá
událost do interní fronty bázové třídy `AggregateRoot`; vyzvednutí přes
`releaseEvents()` po flushi popisuje
[lifecycle sekce v Základních konceptech](/zakladni-koncepty#aggregate-root-lifecycle).

Zapouzdření stavu od PHP 8.4 podporuje i jazyk sám – asymetrickou viditelností:

:::code{language="php" filename="src/Ordering/Domain/Order/Order.php (výřez)"}
class Order extends AggregateRoot
{
    public private(set) OrderStatus $status;

    public function ship(ShipmentId $shipmentId): void
    {
        // ... kontrola stavu ...
        $this->status = OrderStatus::Shipped; // zápis jen uvnitř třídy
    }
}
:::

Vlastnost `public private(set)` přečte kdokoli bez getteru (`$order->status`),
zapsat ji smí jen kód uvnitř třídy. Getter `status()` tím odpadá a stavové
přechody zůstávají jediným místem zápisu.

Stavové přechody tvoří uzavřený graf a ten musí být vymodelovaný celý. Každá
doménová operace odpovídá hraně grafu; cesty, které v grafu chybí, nejsou jen „ještě
neimplementované“ – jsou explicitně zakázané. Životní cyklus agregátu `Order`
ilustruje následující diagram:

:::diagram{fig="07.7-A" title="Stavový diagram agregátu Order" src="images/diagrams/21_aggregate_design/order_states.svg"}
:::

## 07.08 Mapování v Symfony 8 a Doctrine ORM 3 {#symfony-doctrine}

Doctrine ORM je v Symfony projektech výchozí volba a v jeho konfiguraci se nejčastěji
rozhoduje, zda bude agregátní model čistý, nebo se rozplyne. Vernon v IDDD probírá agregát
v kapitole 10 a jeho perzistenci v kapitole 12 „Repositories“. Šest pravidel pro Doctrine
ORM 3, na která pak navazuje výčet toho, co za vás Doctrine nevymůže:

- **Asociace pouze uvnitř agregátu.** `OneToMany` a `ManyToOne`
  používejte jen mezi entitami v hranici stejného agregátu. Reference na cizí agregát
  je vlastnost typu `CustomerId`, namapovaná jako custom Doctrine type.
- **Repository per agregát.** Jeden repozitář na jeden agregát. Repozitář vrací
  pouze kořen, nikdy vnitřní entity. `get()`, `save()`, případně
  několik specializovaných metod – ne obecné `findBy` z `EntityRepository`.
- **Optimistický zámek na kořeni.** `#[ORM\Version]` sloupec na kořeni
  agregátu. Souběžná změna kořene skončí výjimkou `OptimisticLockException`, kterou
  aplikační vrstva překládá na retry nebo na uživatelskou chybu. Změny vnitřních entit
  ale sám o sobě nepokryje – viz odstavec o verzování níže.
- **Doménové eventy přes outbox.** Eventy publikované agregátem se ve stejné
  transakci ukládají do outbox tabulky. Samostatný worker je odesílá do Messenger transportu
  (kapitola [Outbox](/outbox-pattern)).
- **Bez kaskádování přes hranici.** `cascade={"persist","remove"}`
  mezi agregáty je skrytá transakce. Kaskáda je v pořádku jen uvnitř agregátu pro vlastní entity.
- **Embedded value objects.** Hodnotové objekty s více poli (Money, Address)
  mapujte přes `#[ORM\Embedded]`. Žádné samostatné tabulky pro VO.

:::code{language="php" filename="src/SharedKernel/Infrastructure/Doctrine/Type/OrderIdType.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Doctrine\Type;

use App\Ordering\Domain\Order\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class OrderIdType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // 36 znaků = RFC 4122 zápis UUID, který vrací OrderId::generate()
        return $platform->getStringTypeDeclarationSQL(['length' => 36, 'fixed' => true]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderId
    {
        return $value === null ? null : OrderId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof OrderId ? $value->value : null;
    }
}
:::

Třída záměrně nemá metodu `getName()` – DBAL 4 ji odstranil. Jméno typu
(`order_id`) určuje výhradně klíč v konfiguraci `doctrine.dbal.types` níže
a pod stejným jménem na typ odkazuje atribut `#[ORM\Column(type: 'order_id')]`.
Spolu s `getName()` zmizela i metoda `requiresSQLCommentHint()`, takže DBAL už
u vlastního typu nezanechá ve schématu komentář. Na porovnávání schématu to nemá
vliv: `AbstractPlatform::columnsEqual()` srovnává vygenerovanou SQL deklaraci, ne
PHP typ, a `CHAR(36)` z `OrderIdType` odpovídá introspektovanému sloupci. Prázdné
migrace z toho nevznikají. Cenu za zmizelý komentář zaplatíte jinde: dva vlastní
typy nad stejnou SQL deklarací už od sebe schema diff nerozezná, takže záměnu
`order_id` za `customer_id` v mapování migrace neodhalí.

:::code{language="php" filename="src/Ordering/Domain/Order/Order.php (mapování)" highlights="22,32,33,34,35,36,37,38,39,41,42,43,44,45"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Order;

use App\Customers\Domain\Customer\CustomerId;
use App\SharedKernel\Domain\AggregateRoot;
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

    // Mapování mění typ kolekce: místo pole list<OrderItem>
    // z čisté doménové varianty vyžaduje Doctrine Collection.
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

    private function __construct(/* ... */) { /* ... */ }

    // ... factory metody, doménové operace ...
}
:::

:::callout{type="note"}
**Entita mapovaná Doctrine může být `final`**

Na starším stacku to neplatilo: Doctrine generovala proxy třídu, která z entity
dědila, takže `final` skončil chybou při jejím vytvoření. Od PHP 8.4 a Doctrine
ORM 3.4 se pro lazy loading používají nativní lazy objekty a ty žádnou podtřídu
nevytvářejí – ghost je instancí téže třídy. V DoctrineBundle 3, který jde se
Symfony 8, jsou nativní lazy objekty zapnuté vždy a vypnout je nelze.

Omezení tedy padlo a `final` u entity projde. Kdo udržuje projekt na starším
Symfony, počítá s ním dál.
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
        // flush a commit řídí doctrine_transaction middleware command busu.
        // Při flushi se uloží kořen + vnitřní entity (OrderItem) díky
        // cascade={"persist"}; Doctrine vyhodí OptimisticLockException,
        // pokud se @Version mezitím změnila.
    }

    // ŽÁDNÉ findAll(), findBy(), žádné metody pro čtení vnitřních entit.
    // Read modely jsou samostatné (CQRS, kapitola 12).
}
:::

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            order_id:    App\SharedKernel\Infrastructure\Doctrine\Type\OrderIdType
            customer_id: App\SharedKernel\Infrastructure\Doctrine\Type\CustomerIdType
            product_id:  App\SharedKernel\Infrastructure\Doctrine\Type\ProductIdType
            money:       App\SharedKernel\Infrastructure\Doctrine\Type\MoneyType

    orm:
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

### Co Doctrine nevymůže {#doctrine-limits}

Šest pravidel výše vypadá jako konfigurace. Ve skutečnosti jsou to konvence, které nikdo
nekontroluje – Matthias Noback k tomu sepsal podrobný výčet třecích ploch mezi Doctrine
a agregátem [[12]](https://matthiasnoback.nl/2018/06/doctrine-orm-and-ddd-aggregates/).
Dvě z pravidel se v provozu lámou tiše.

První je verzování. `#[ORM\Version]` zvýší verzi jen tehdy, když se změnilo pole na kořeni.
Změna vnitřní entity, typicky `OrderItem`, se do verze `Order`u nepromítne. Dva požadavky,
z nichž každý upraví jinou položku téže objednávky, projdou oba a invariant „součet položek
se rovná celkové ceně“ se rozpadne, aniž kdokoli dostane `OptimisticLockException`.
Doctrine na to nemá ekvivalent JPA konstanty `OPTIMISTIC_FORCE_INCREMENT`; požadavek na ni
je otevřený od roku 2013 [[10]](https://github.com/doctrine/orm/issues/3620). Obejít to lze
třemi způsoby. Doménová metoda kořene se při každé změně potomka dotkne vlastního pole
(přepočtená `totalAmount` nebo `updatedAt`), což je řešení, které navíc dává doménový smysl.
Druhá cesta je explicitní `$em->lock($order, LockMode::OPTIMISTIC, $expectedVersion)`
s verzí, kterou drží klient. Třetí je pesimistický zámek, tedy `LockMode::PESSIMISTIC_WRITE`,
za cenu propustnosti.

Druhá trhlina je hranice transakce. `EntityManager::flush()` commitne **všechny** špinavé
entity ve své Unit of Work, ne jen agregát, který handler načetl. Repozitář z ukázky výše
tedy pravidlo „jeden agregát na transakci“ nevynucuje. Pokud handler cestou sáhne na cizí
agregát a jen mu změní vlastnost, Doctrine ho uloží zároveň – bez varování. Vynutí to jen
kázeň, code review a architektonický test (kapitola
[Testování DDD](/testovani-ddd#architektonicke-testy)). Kdo chce hranici doménového modelu
oddělit od perzistentního úplně, sáhne po Persisted Object Pattern
([Implementace v Symfony 8](/implementace-v-symfony#persisted-object-pattern)): doménová
třída zůstane bez ORM atributů a mapování obstará samostatná persistence třída s mapperem.
Cenou je vrstva navíc, výhodou to, že Doctrine přestane ovlivňovat tvar agregátu.

## 07.09 Pokročilá témata: large collection, hot aggregate, snapshoty {#advanced}

### Large-collection problem {#large-collection}

Klasický anti-vzor: agregát `Project` drží `OneToMany` kolekci úkolů.
S desítkami úkolů je to v pořádku, s tisíci je to neúnosné – každé načtení agregátu hydratuje
celou kolekci, každé přidání položky způsobí flush všech úkolů. Nabízejí se tři východiska,
seřazená od nejčistšího po nejvíc kompromisní:

- **Rozdělit agregát.** `Project` a `Task` se stanou samostatnými agregáty
  a `Task` nese jen `ProjectId` jako referenci. Invariant „úkol patří
  k existujícímu projektu“ pak nevymáhá Doctrine asociace, ale command handler přes
  `ProjectExistsSpecification` – před založením úkolu ověří, že projekt existuje.
- **Doctrine extra-lazy collection.** `fetch: 'EXTRA_LAZY'` umožní
  `$project->getTasks()->count()` bez načtení kolekce, případně
  `$project->getTasks()->matching($criteria)`. Použitelné, pokud agregát
  kolekci skutečně potřebuje pro invarianty (např. limit počtu úkolů na projekt).
  Jedna past: neinicializovaná kolekce kritérium přeloží do SQL, načtená ho
  vyhodnotí v paměti nad už zhydratovanými objekty. S backed enumem v kritériu
  vyjdou obě cesty stejně, se surovou databázovou hodnotou (`'open'` místo
  `TaskStatus::Open`) ne – nad načtenou kolekcí neprojde porovnání s enumem
  a výsledek je prázdný. Do kritéria patří enum, ne řetězec.
- **Agregát jako hranice služby.** Kolekci nahradí služba pracující s agregátem,
  která invariant ověří dotazem v repozitáři. Funguje, ale signalizuje špatnou hranici.

### Hot aggregate {#hot-aggregate}

Hot aggregate je agregát, na který souběžně sahá mnoho uživatelů (nákupní košík během
Black Friday, sportovní výsledek, hra v reálném čase). Optimistický zámek selhává –
většina transakcí spadne na `OptimisticLockException`, retry trvá, uživatelská
zkušenost se hroutí.

Absolutní hranice v transakcích za sekundu neexistuje. Pravděpodobnost konfliktu určuje
součin frekvence zápisů na jednu instanci a doby, po kterou transakce drží stav. Agregát
s deseti zápisy za sekundu a transakcí o délce jedné milisekundy je klidný; tentýž agregát
s transakcí trvající dvě stě milisekund konflikty vyrábí. Měří se tedy obojí a teprve
z toho vychází rozhodnutí. Přístupy:

- **Rozdělit agregát na menší.** Místo `Stadium` s tisícem sedaček
  vznikne `Section` s desítkami. Souběžné transakce se rozprostřou.
- **Přepnout na Event Sourcing.** ES eliminuje race condition na update – každý
  event je append-only. Konflikty řeší stream version (kapitola
  [Event Sourcing](/event-sourcing)).
- **Single-writer pattern.** Agregát existuje v paměti jediného procesu (actor
  model, Akka, Orleans). Symfony to nativně neumí; alternativou je Messenger se směrováním
  přes konzistentní hash a single consumer per aggregate ID.
- **Přijmout eventual consistency uvnitř.** Například u čítačů
  (*like count*) je přesný stav nedůležitý – stačí zpožděná replikace s nepřesností
  v řádech sekund.

### Snapshoty v Event Sourcingu {#es-snapshots}

U Event-Sourced agregátů má rebuild stavu z eventů složitost O(N). Snapshot ukládá
serializovaný stav agregátu po N eventech; při načtení se stav rekonstruuje od posledního
snapshotu a navrch se aplikuje zbývající ocas streamu. Práh N se měří, neodhaduje: závisí
na velikosti eventů i na tom, kolik replay reálně stojí. Dlouhý stream je navíc častěji
příznakem hranice, která patří jinam, než skutečné potřeby snapshotu.

Pro návrh agregátu jsou důležité tři věci. Snapshot není autoritativní stav, jen
optimalizace – když se serializace nepovede, stav se sestaví znovu od začátku streamu.
Jeho verzování musí být kompatibilní s verzováním eventů, takže změna schématu stavu
znamená invalidaci starých snapshotů. A snapshot store zůstává oddělený od event store,
plněný procesem na pozadí; snapshot zapsaný přímo do event logu je nucen být vždy na
poslední verzi, čímž si u vytížených agregátů vyrábí vlastní smyčku konfliktů.
Implementaci rozebírá kapitola [Event Sourcing](/event-sourcing).

### Partitioning a multi-tenancy {#partitioning}

Pro návrh agregátu má multi-tenancy jediný, zato tvrdý důsledek: `tenantId` je součást
identity agregátu, ne filtr přilepený k dotazu. Repozitář přijímá dvojici
`(tenantId, aggregateId)` a operace jednoho tenanta nikdy nesáhne na instanci jiného.

Volba databázové topologie na tomto závěru nic nemění. Doctrine `SQLFilter`
s `tenant_id = :current_tenant` je nejlevnější a nativním SQL obejitelný, schema per
tenant izoluje víc za cenu cross-schema reportů, databáze per tenant izoluje nejvíc
a stojí nejvíc provozně. Rozhodnutí patří do infrastruktury; hranice agregátu zůstává
ve všech třech případech stejná.

## 07.10 Strategie referencování napříč agregáty {#reference-strategies}

Reference přes ID je jasné pravidlo, ale typů ID je víc a každý má dopad na schéma a výkon.

- **UUID v4 (random).** Náhodná, distribuovaně generovatelná, neuhodnutelná.
  Nevýhoda: insertion order není seřazen, což zhoršuje I/O pattern u clustered indexů (MySQL/InnoDB).
- **UUID v7 (případně ULID).** Časově řazené, generovatelné distribuovaně bez
  koordinace, řadí se podle času vzniku. **Doporučená volba** pro většinu nových projektů.
  `Uuid::v7()` i ULID (`Symfony\Component\Uid\Ulid`) nabízí balíček `symfony/uid`.
- **Sekvenční integer.** Krátký, lidsky čitelný, rychlý. Nevýhody: vyžaduje
  centrální generátor (DB sekvence), prozrazuje řád a počet entit, špatně se merguje
  z více DB (microservices).
- **Composite ID.** `(tenantId, naturalId)`. Vhodné pro multi-tenancy.
  Nevýhoda: každá tabulka má dvousloupcový PK, JOIN podmínky jsou složitější.
- **Natural key.** Hodnota z domény (ISBN, IČO, e-mail). Funguje, dokud doména
  hodnotu nezmění. **Nedoporučujeme** – domény své „přirozené klíče“ mění
  častěji, než se zdá.

:::callout{type="pattern"}
**Doporučení**

Pro nové Symfony projekty je výchozí volbou UUID v7. Je časově řazené, standardizované
(RFC 9562), kompatibilní s primárními klíči MySQL i PostgreSQL a Symfony
ho podporuje v základu balíčku `symfony/uid` (`Uuid::v7()`). ULID zůstává alternativou,
když je žádoucí kratší Crockford base32 zápis (26 znaků vs. 36); měření dopadu obou
formátů na index rozebírá kapitola
[Výkonnostní aspekty](/vykonnostni-aspekty#uuid-vs-integer).
Komplikované referenční schéma typu „tenantId + naturalId“ zaveďte teprve
tehdy, když máte konkrétní multi-tenancy požadavek.
:::

## 07.11 Postup návrhu krok za krokem {#workflow}

Návrh agregátu je disciplinovaný proces, ne kreslení tříd v IDE. Následující sedmikrokový
postup je autorský; kroky 4 a 5 přebírají Vernonovu metodu odhadu, zbytek vychází
z praxe na Symfony projektech:

1. **Sepište invarianty.** Z Event Stormingu, doménových workshopů nebo
   rozhovorů s experty vytáhněte všechna pravidla. Každé zformulujte jako větu „v každý
   okamžik musí platit, že …“. Pravidla, která neprojdou („eventuálně musí platit“), odložte
   – budou to ságy.
2. **Seskupte invarianty.** Pravidla, která sdílejí stejné entity, jsou
   kandidáti na společný agregát. Co spolu věcně nesouvisí, patří jinam.
3. **Identifikujte kořen.** Pro každou skupinu invariantů vyberte jednu entitu,
   která je „vstupní branou“. Typicky ta s nejvyšší doménovou autoritou („Order“ vs. „OrderItem“).
4. **Odhadněte velikost tužkou na papíře.** Vernon to předvádí na backlog itemu:
   dvanáctidenní sprint, dvanáct tasků na jeden backlog item a dvanáct záznamů
   o přeodhadu. Celkem nejvýš pětadvacet objektů, tedy malý agregát. Stejný výpočet
   pro vlastní doménu zabere půl hodiny, v horším případě hodinu, a nahradí dojem
   horním odhadem růstu.
5. **Odhadněte konkurenci.** Kolik zápisů za sekundu dopadne v peaku na *jednu* instanci
   agregátu a jak dlouho drží transakce stav? Součin obou čísel rozhoduje o tom, zda máte
   hot aggregate a zda sáhnete po některé z technik z 07.09.
6. **Definujte commandy a eventy.** Pro každý use case napište command (vstup),
   doménovou metodu na agregátu (chování) a event (výstup). Eventy nahrávejte explicitně
   metodou `record()`; celý cyklus record/release popisuje
   [lifecycle sekce v Základních konceptech](/zakladni-koncepty#aggregate-root-lifecycle).
7. **Code review proti checklistu.** Sekce 07.13 níže má checklist s 12 body.
   Pokud agregát na jakýkoli odpoví „ne“, návrh není hotový.

Reálný příklad postupu na agregátech `Project` a `Task` najdete v kapitole
[Případová studie](/pripadova-studie). Konkrétně v sekcích, kde stejný postup
aplikujeme na netriviální doménu správy projektů.

### Aggregate Design Canvas {#design-canvas}

Pro workshopové prostředí existuje hotový formulář. **Aggregate Design Canvas** od skupiny
ddd-crew [[11]](https://github.com/ddd-crew/aggregate-design-canvas) má devět polí: název,
popis, stavové přechody, vymáhané invarianty, korektivní politiky, obsluhované commandy,
vytvářené eventy, propustnost a velikost. Šíří se pod licencí CC BY 4.0, takže ho lze
upravit pro vlastní tým.

Dvě pole nemá žádná z klasických knih. **Corrective Policies** popisují, co se stane, když
hranici *záměrně* uvolníme – kompenzace přestává být důsledkem selhání a stává se součástí
návrhu. **Throughput** a **Size** nutí odhadnout frekvenci commandů, počet souběžných
klientů, tempo růstu a životnost instance; je to Vernonova metoda z kroků 4 a 5 povýšená
na standardní kolonku.

Canvas se plní přímo nad výstupem [Event Stormingu](/event-storming): commandy, eventy
a policy sticky se z něj přenášejí, hot spoty se stávají kandidáty na invarianty.

## 07.12 Typické chyby {#anti-patterns}

- **Velký agregát kvůli pohodlí ORM.** „Když už máme `OneToMany`,
  dáme tam i objednávku.“ Asociace jsou nástroj mapování, ne vodítko pro hranici.
- **Sága tam, kde má být agregát.** Pokud invariant musí platit okamžitě, sága
  ho neudržuje. Pravidlo „pojistka nikdy nesmí být zaplacena bez podepsané smlouvy“
  nesnese několik sekund čekání – patří do agregátu.
- **Doménová logika v read modelu.** Read model je projekce, ne místo, kde
  žijí invarianty. Pravidla patří do write modelu, projekce jen reaguje.
- **Domain Event jako notifikace mezi vrstvami.** Event není mechanismus
  pro „když se agregát změní, smaž cache“. Eventy jsou doménová fakta, ne infrastrukturní
  signály. Cache invalidaci řešte v projekci, která event konzumuje.

Několik dalších chyb má společného jmenovatele: obcházení kořene.
`$order->getItems()->add(...)` mění kolekci mimo agregát – z pohledu vnějšku má být
immutable a přidání položky jde výhradně metodou na kořeni. Totéž porušení hranice
předvádí `OrderItemRepository::get(itemId)`: vnitřní entita se ven nepředává k uchování
ani k modifikaci a její „samostatná“ identita patří do read modelu, ne do write modelu.
Evans připouští, že reference na vnitřní člen ven vyjde – ale jen pro jedinou operaci,
bez uložení do pole a bez zápisu skrz ni. Příbuzným
vzorem je anemic aggregate s public settery – pokud má agregát pro každou vlastnost
`get/set`, je to data structure, ne agregát, a stavové přechody musí být metody
vyjadřující doménový záměr (`place()`, `ship()`, `cancel()`).

Variantou téhož na vyšší úrovni je sdílený stav přes službu. Pomocná „`OrderService`“,
která zasahuje do dvou agregátů, je skrytá transakce; pokud služba vykoná
`$em->flush()`, jste v anti-vzoru.

## 07.13 Checklist návrhu agregátu {#checklist}

1. Sepsal jsem invarianty v ubiquitous language (slova z domény, ne z kódu).
2. U každého invariantu vím, zda musí platit okamžitě, nebo eventuálně.
3. Hranice agregátu obklopuje invarianty kategorie „okamžitě“.
4. Na kořeni je optimistický zámek (`#[ORM\Version]` nebo ekvivalent) a každá
   doménová metoda, která mění vnitřní entitu, se dotkne i pole na kořeni.
5. Reference na cizí agregát jsou identifikátorové (Value Object), ne objektové.
6. Žádná Doctrine asociace nepřekračuje hranici agregátu.
7. Repozitář vrací jen kořen; vnitřní entita se ven nepředává k uchování ani
   k modifikaci.
8. Změny napříč agregáty řeší sága nebo process manager, ne sdílená transakce –
   a pokud přesto sdílená transakce zůstává, je pojmenovaný důvod proč.
9. UI počítá s eventual consistency tam, kde ji doména používá.
10. Kaskádové operace existují jen uvnitř agregátu.
11. Stavové přechody jsou metody vyjadřující doménový záměr, ne settery.
12. Identifikátor kořene je Value Object s validací (nikoli holý `string`/`int`).

## 07.14 Další četba {#further-reading}

- Eric Evans, *Domain-Driven Design: Tackling Complexity in the Heart of Software*, kap. 6 „The Life Cycle of a Domain Object“ (sekce Aggregates) (Addison-Wesley, 2003) [[1]](https://www.dddcommunity.org/book/evans_2003/).
- Vaughn Vernon, *Effective Aggregate Design*, Part I–III (2011) [[2]](https://www.dddcommunity.org/library/vernon_2011/) – kanonický text o pravidlech návrhu agregátu, na který odkazuje téměř každá pozdější DDD kniha.
- Vaughn Vernon, *Implementing Domain-Driven Design*, kap. 10 „Aggregates“ a kap. 12 „Repositories“ (Addison-Wesley, 2013) [[3]](https://www.informit.com/store/implementing-domain-driven-design-9780321834577).
- Vlad Khononov, *Learning Domain-Driven Design*, kap. 6 „Tackling Complex Business Logic“ a kap. 7 „Modeling the Dimension of Time“ (O'Reilly, 2021) [[4]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/).
- Pat Helland, *Life Beyond Distributed Transactions: an Apostate's Opinion*, ACM Queue (2007, reprint 2016) [[5]](https://queue.acm.org/detail.cfm?id=3025012).
- Martin Fowler, *DDD_Aggregate* (bliki) [[6]](https://martinfowler.com/bliki/DDD_Aggregate.html).
- Greg Young, *CQRS Documents* (2010) [[7]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf) – relevantní především kapitoly o Event Sourcingu a snapshotech.
- Eric Evans, *Domain-Driven Design Reference: Definitions and Pattern Summaries* (2015) [[8]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) – volně dostupné PDF s kondenzovanou definicí agregátu, včetně spojení hranice s distribucí.
- Bertrand Meyer, *Object-Oriented Software Construction*, 2. vydání (Prentice Hall, 1997) [[9]](https://www.informit.com/store/object-oriented-software-construction-9780136291558) – původní formulace invariantu v Design by Contract.
- doctrine/orm, issue #3620 „OPTIMISTIC_FORCE_INCREMENT“ (otevřeno 2013) [[10]](https://github.com/doctrine/orm/issues/3620) – doklad, že Doctrine verzi kořene při změně potomka nezvyšuje.
- ddd-crew, *Aggregate Design Canvas* (CC BY 4.0) [[11]](https://github.com/ddd-crew/aggregate-design-canvas) – devítipolový formulář pro modelovací workshopy.
- Matthias Noback, *Doctrine ORM and DDD aggregates* (2018) [[12]](https://matthiasnoback.nl/2018/06/doctrine-orm-and-ddd-aggregates/) – nejpodrobnější výčet třecích ploch mezi Doctrine a hranicí agregátu v PHP.
- V této příručce navazují kapitoly [CQRS](/cqrs), [Event Sourcing](/event-sourcing), [Ságy a Process Managery](/sagy-a-process-managery), [Outbox Pattern](/outbox-pattern) a [Případová studie](/pripadova-studie), kde uvidíte aplikaci postupu na konkrétní doméně.

:::faq{}
- question: Jak velký má být agregát?
  answer: 'Tak velký, aby obsahoval všechny invarianty, které musí platit okamžitě, a ne větší. Výchozí volba je agregát s jedním kořenovým objektem a několika hodnotovými objekty plus volitelně několika vnitřními entitami. Větší agregát potřebuje konkrétní obhajobu invariantem, ne pohodlí ORM. Vernon u projektu, který analyzoval, napočítal zhruba 70 % agregátů tvořených jen kořenem s hodnotovými objekty a 30 % se dvěma až třemi entitami. Velikost se odhaduje tužkou na papíře: kolik potomků instance nasbírá za dobu svého života. Kolekce, jejíž růst nic neomezuje, patří ven z agregátu, nebo alespoň na <code>EXTRA_LAZY</code> s filtrováním v repozitáři. Detail v <a href="#aggregate-size">sekci Velikost agregátu</a>.'
- question: Proč nelze měnit dva agregáty v jedné transakci?
  answer: 'Technicky to lze a výchozí odpověď zní „nedělejte to“. Hranice agregátu je zároveň hranice konzistence a hranice škálování: zámek napříč agregáty snižuje propustnost, souběžné transakce plodí deadlocky a kód se později nedá rozdělit. Potřeba commitnout dva agregáty najednou je hlavně diagnóza – nejspíš je špatně vedená hranice. Vernon ale jmenuje čtyři situace, kdy je porušení legitimní (dávkové zakládání z UI, chybějící messaging, vynucené globální transakce, výkon dotazů); rozebírá je <a href="#breaking-the-rule">sekce Kdy se vodítko poruší</a>. Detail v <a href="#transactional-consistency">sekci Transakční konzistence</a>, alternativní řešení v <a href="#eventual-consistency">sekci Eventual consistency</a>.'
- question: Co je eventual consistency a kdy ji použít?
  answer: 'Eventual consistency znamená, že stav dvou agregátů je konzistentní se zpožděním, ne okamžitě. Jak dlouhé zpoždění je přijatelné, určí doménový expert – Vernon připomíná, že experti běžně připustí sekundy, minuty, hodiny i dny. Použijte ji všude, kde invariant nemusí platit v každý okamžik – například „po vystavení objednávky se zákazníkovi pošle e-mail“ nebo „při změně adresy v Customer agregátu se upraví doručovací adresa v rozpracovaných objednávkách“. Implementačně: agregát A publikuje doménový event, sága ho přijme a v separátní transakci modifikuje agregát B. Pravidla, která musí platit okamžitě (například „bilance debetů a kreditů je nulová“), patří do jednoho agregátu. Detail v <a href="#eventual-consistency">sekci Eventual consistency</a>.'
- question: Jak v Doctrine ORM 3 namapovat referenci na jiný agregát?
  answer: 'Jako jednoduchý sloupec s vlastním Doctrine typem (<code>order_id</code>, <code>customer_id</code>), který konvertuje mezi databázovou hodnotou a Value Objectem (<code>OrderId</code>, <code>CustomerId</code>). Žádná <code>ManyToOne</code> asociace mezi agregáty. Doctrine asociace ponechte jen pro entity uvnitř stejného agregátu (typicky <code>OneToMany</code> z kořene na vnitřní entity s <code>cascade=["persist", "remove"]</code> a <code>orphanRemoval=true</code>). Hodnotové objekty s více poli (Money, Address) mapujte přes <code>#[ORM\\Embedded]</code>. Detail v <a href="#symfony-doctrine">sekci Mapování v Doctrine ORM 3</a>.'
- question: Co je hot aggregate a jak poznat, že ho mám?
  answer: 'Hot aggregate je agregát, na který se souběžně sahá z mnoha transakcí (nákupní košík během Black Friday, sportovní výsledek, hra v reálném čase, čítač lajků na virálním příspěvku). Příznak v provozu: většina commandů selže s <code>OptimisticLockException</code>, retry trvá sekundy, latence stoupá, uživatelská zkušenost se hroutí. Absolutní práh v transakcích za sekundu neexistuje: riziko konfliktu určuje součin frekvence zápisů na jednu instanci a doby, po kterou transakce drží stav. Měří se proto obojí. Detail příznaků a rozhodovací logika v <a href="#hot-aggregate">sekci Hot aggregate</a>.'
- question: Jak hot aggregate vyřešit?
  answer: 'Čtyři strategie podle povahy domény. <strong>Rozdělení na menší</strong> – místo <code>Stadium</code> s tisícem sedaček modelujte <code>Section</code> s desítkami; souběžné transakce se rozprostřou. <strong>Event Sourcing</strong> – append-only operace eliminují konflikt na update, konflikty řeší stream version (kapitola <a href="/event-sourcing">Event Sourcing</a>). <strong>Single-writer pattern</strong> – agregát existuje v paměti jediného procesu, v Symfony přes Messenger se směrováním konzistentním hashem. <strong>Eventual consistency uvnitř</strong> – pro nekritické hodnoty (<em>like count</em>) periodicky replikujte. Volba závisí na povaze invariantu; vodítko v <a href="#hot-aggregate">sekci Hot aggregate</a>.'
- question: Jaký identifikátor zvolit pro nový agregát?
  answer: 'Pro nové Symfony projekty doporučujeme UUID v7 (<code>Uuid::v7()</code>, balíček <code>symfony/uid</code>). Časově řazená generace zlepšuje I/O pattern v MySQL/InnoDB oproti UUID v4, distribuované vytváření odstraňuje potřebu centrálního generátoru a formát je standardizovaný v RFC 9562. ULID (<code>Symfony\\Component\\Uid\\Ulid</code>) je alternativa se srovnatelnými vlastnostmi a kratším zápisem (26 znaků vs. 36). Sekvenční integery volte jen pro specifický důvod (lidsky čitelné číslo objednávky). Přirozené klíče (e-mail, IČO) <strong>nedoporučujeme</strong> – domény mění své „přirozené klíče“ častěji, než se zdá. Srovnání všech pěti strategií v <a href="#reference-strategies">sekci Strategie referencování</a>.'
- question: Jak rychle ověřit, že hranice agregátu je správně?
  answer: 'Tři rychlé kontroly. (1) <strong>Test invariantu</strong>: existuje pravidlo, které by se porušilo, kdybyste agregát rozdělili na dva? (2) <strong>Test velikosti</strong>: umíte spočítat horní mez počtu potomků, které instance za svůj život nasbírá? (3) <strong>Test reference</strong>: ven z agregátu se odkazujete jen přes ID, ne přes objektovou referenci? Pokud na všechny tři odpovídáte „ano“, hranice je nejspíš správná. Plný checklist s 12 body v <a href="#checklist">sekci Checklist</a>, sedmikrokový postup návrhu v <a href="#workflow">sekci Postup návrhu</a>.'
:::
