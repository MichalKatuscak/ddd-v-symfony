---
route: basic_concepts
path: /zakladni-koncepty
title: Základní koncepty DDD
page_title: "Základní koncepty Domain-Driven Design | DDD Symfony"
meta_description: "Základní stavební kameny taktického DDD: entity, hodnotové objekty, agregáty, repozitáře, doménové události a služby – s ukázkami v PHP 8.4+ a Symfony 8."
meta_keywords: "DDD koncepty, entity, hodnotové objekty, value objects, kořeny agregátů, aggregate roots, doménové služby, repozitáře, doménové události, Symfony implementace"
og_type: article
published: "2025-04-24"
modified: "2026-06-09"
breadcrumb_name: Základní koncepty
schema_type: TechArticle
schema_headline: "Základní koncepty Domain-Driven Design"
chapter_number: "06"
category: Taktika
deck: "Domain-Driven Design nabízí sadu stavebních bloků, které pomáhají převést znalosti o doméně do strukturovaného softwarového modelu. Každý z těchto konceptů řeší konkrétní problém – od vymezení hranic mezi částmi systému přes zachycení identity objektů až po komunikaci mezi komponentami."
reading_time: 18
difficulty: 2
github_examples: Chapter03_BasicConcepts
---

## 06.01 Ohraničené kontexty (Bounded Contexts) {#bounded-contexts}

Slovo „zákazník“ znamená v marketingu něco jiného než ve fakturaci. Tým, který oba
významy spojí do jedné třídy, skončí u modelu plného polí, z nichž polovina v daném
použití nedává smysl. Ohraničený kontext je explicitně vymezená oblast, uvnitř které
platí jeden konzistentní model a jeden slovník
[[1]](https://martinfowler.com/bliki/BoundedContext.html) – různé kontexty proto mají
různé modely, a to záměrně. Jde o strategické téma: celkový rámec podává kapitola
[Co je DDD](/co-je-ddd), vztahy a integraci mezi kontexty rozebírá
[Context Mapping](/context-mapping). Rozdělení reálného systému do pěti kontextů ukazuje
[Případová studie](/pripadova-studie#discovery). Tato kapitola s kontexty dál pracuje
jen jako s hranicí, uvnitř které žijí taktické stavební bloky.

:::diagram{fig="06.1-A" title="Ohraničené kontexty" src="images/diagrams/5_bounded_contexts/diagram.svg"}
:::

## 06.02 Všudypřítomný jazyk (Ubiquitous Language) {#ubiquitous-language}

Pokud kód mluví o `Customer` a produktový tým o „uživateli“, každý rozhovor nad
zadáním začíná překladem – a právě v překladu se ztrácejí významy. Všudypřítomný
jazyk je jednotný slovník, na kterém se vývojáři domluví s doménovými experty
a který pak důsledně platí v kódu, dokumentaci i běžné konverzaci
[[2]](https://martinfowler.com/bliki/UbiquitousLanguage.html).
Proč jazyk vzniká a jak se buduje, popisuje kapitola [Co je DDD](/co-je-ddd); kde jeden
jazyk končí a začíná druhý, určuje hranice kontextu z [Context Mappingu](/context-mapping).

:::diagram{fig="06.2-A" title="Všudypřítomný jazyk" src="images/diagrams/4_ubiquitous_language/diagram.svg"}
:::

## 06.03 Entity {#entities}

Co odlišuje uživatele se stejným jménem a stejným e-mailem? Identita. Entita je
doménový objekt, který nese vlastní identifikátor a zachovává si ho po celý život
[[3]](https://www.domainlanguage.com/ddd/). Atributy se v čase mění – jméno, adresa,
e-mail – identita zůstává.

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Model;

use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;

class User
{
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        private readonly UserId $id,
        private string $name,
        private Email $email,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
:::

`User` je v ukázce entita definovaná `UserId`. Uživatel může změnit jméno
i e-mail, identifikátor zůstává stejný.

### Rovnost entit {#entity-equality}

Dvě entity jsou totožné právě tehdy, když mají stejné ID – proto `equals()`
porovnává výhradně identifikátory. Porovnání operátorem `==` se nehodí: srovnává
všechny vlastnosti najednou. Tentýž uživatel načtený dvakrát z databáze sice
projde, ale jakmile jedna z instancí změní e-mail, `==` ji označí za jinou
entitu – identita se přitom nezměnila. Operátor `===` zase porovnává
identitu instance v paměti. Stejný agregát načtený ve dvou různých kontextech
(dva requesty, deserializace ze zprávy) jsou dvě instance, a `===` tedy vrátí
`false`, i když jde o tutéž doménovou entitu.

## 06.04 Hodnotové objekty (Value Objects) {#value-objects}

Dva e-maily se stejným textem nejsou „dvě adresy“ – je to jedna a tatáž hodnota.
Hodnotový objekt je doménový pojem, který identifikuje sám sebe celou svou hodnotou,
ne odděleným ID [[3]](https://www.domainlanguage.com/ddd/). Z toho plynou dvě
vlastnosti: neměnnost (immutable) a rovnost po hodnotě, ne po referenci.

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/Email.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }
    }

    public static function fromUserInput(string $raw): self
    {
        // Normalizace vstupu (lowercase, trim) patří sem, ne do konstruktoru.
        return new self(mb_strtolower(trim($raw)));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
:::

`Email` v ukázce drží jediný řetězec jako `public readonly` vlastnost – getter
by jen přidával šum. Formát hlídá konstruktor, normalizaci vstupu z formulářů
obstará pojmenovaná factory `fromUserInput()`. Žádné ID, žádné settery: dva
e-maily se shodují právě tehdy, když mají stejnou hodnotu. Třída je `final
readonly` – hodnotový objekt nikdo nedědí ani nemění po vytvoření.

### Validace: kde jaká výjimka {#vo-validation}

Konvence této knihy rozlišuje dvě úrovně validace. Porušení *formátu* hodnoty
(neplatný e-mail, záporná částka, řetězec, který není UUID) hlásí konstruktor
hodnotového objektu výjimkou `\InvalidArgumentException`. Takové porušení je
programátorská chyba nebo nevalidní vstup, který měla zachytit už vstupní vrstva.
Porušení *byznys pravidla* (potvrzení prázdné objednávky, platba nepotvrzené
objednávky) hlásí agregát doménovou výjimkou dědící z `\DomainException` –
typicky pojmenovanou třídou jako `InvalidOrderStateTransitionException`.
Hierarchii výjimek po vrstvách rozebírá kapitola
[Implementace v Symfony 8](/implementace-v-symfony#error-handling).

## 06.05 Agregáty (Aggregates) {#aggregates}

Objednávka má položky, dodací adresu, stav a celkovou částku. Změnit položku znamená
přepočítat částku; zrušit objednávku znamená překontrolovat stav. Pokud tato pravidla
nepatří jednomu strážci, rozsypou se. Agregát je právě tento strážce – skupina objektů,
které se mění jako jeden celek a tvoří jednu transakční hranici konzistence
[[3]](https://www.domainlanguage.com/ddd/). Vstup do agregátu vede výhradně přes kořen
(Aggregate Root). Špatně zvolená velikost patří mezi nejčastější chyby v DDD; přerostlé
„God Aggregates“ rozebírá kapitola [Anti-vzory a typické chyby](/anti-vzory).

:::code{language="php" filename="src/OrderManagement/Domain/Model/Order.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Model;

use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\ProductId;
use App\OrderManagement\Domain\ValueObject\UserId;

class Order
{
    /** @var list<OrderItem> */
    private array $items = [];

    private OrderStatus $status;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        private readonly OrderId $id,
        private readonly UserId $userId,
    ) {
        $this->status = OrderStatus::Created;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function addItem(ProductId $productId, int $quantity, Money $price): void
    {
        if ($this->status !== OrderStatus::Created) {
            throw new \DomainException('Cannot add items to a non-created order');
        }

        $this->items[] = new OrderItem($this->id, $productId, $quantity, $price);
    }

    public function removeItem(ProductId $productId): void
    {
        if ($this->status !== OrderStatus::Created) {
            throw new \DomainException('Cannot remove items from a non-created order');
        }

        $this->items = array_values(array_filter(
            $this->items,
            static fn (OrderItem $item): bool => !$item->productId()->equals($productId),
        ));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Created) {
            throw new \DomainException('Cannot confirm a non-created order');
        }

        if ($this->items === []) {
            throw new \DomainException('Cannot confirm an empty order');
        }

        $this->status = OrderStatus::Confirmed;
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::Created && $this->status !== OrderStatus::Confirmed) {
            throw new \DomainException('Cannot cancel a non-created or non-confirmed order');
        }

        $this->status = OrderStatus::Cancelled;
    }

    public function totalAmount(): Money
    {
        if ($this->items === []) {
            throw new \DomainException('Cannot calculate total of an empty order');
        }

        $total = $this->items[0]->unitPrice()->multiply($this->items[0]->quantity());

        foreach (array_slice($this->items, 1) as $item) {
            $total = $total->add($item->unitPrice()->multiply($item->quantity()));
        }

        return $total;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
:::

:::code{language="php" filename="src/OrderManagement/Domain/Model/OrderItem.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Model;

use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\ProductId;

class OrderItem
{
    public function __construct(
        private readonly OrderId $orderId,
        private readonly ProductId $productId,
        private readonly int $quantity,
        private readonly Money $unitPrice,
    ) {
        if ($quantity <= 0) {
            throw new \DomainException('Množství musí být kladné.');
        }
    }

    public function productId(): ProductId { return $this->productId; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): Money { return $this->unitPrice; }
}
:::

`Order` v ukázce je kořen agregátu a drží kolekci `OrderItem` objektů. Vnější
volání jdou výhradně přes metody na `Order`; vlastní `OrderItem` zvenku
nikdo neinstancuje ani nemění. Výpočet `totalAmount()` přebírá měnu z položek:
sčítání začíná u první z nich a `Money::add()` při nesouladu měn vyhodí výjimku,
takže objednávka se smíšenými měnami neprojde tiše. `OrderItem` je zde záměrně
zjednodušený na neměnný záznam. Plnou verzi s chováním – metodou
`increaseQuantity()` pro invariant „jedna položka na produkt“ – ukazuje kapitola
[Návrh agregátu](/navrh-agregatu#references-by-id).

:::callout{type="note"}
### PHP 8.1+ Enum pro stavové typy {#enum-poznamka-heading}

Pro konečnou množinu stavů typu `OrderStatus` se od PHP 8.1 obvykle volí nativní
`enum` místo plnohodnotného hodnotového objektu:

:::code{language="php" filename="src/OrderManagement/Domain/Model/OrderStatus.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Model;

enum OrderStatus: string
{
    case Created   = 'created';
    case Confirmed = 'confirmed';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
:::

**Kdy enum, kdy plný Value Object?** Enum stačí pro konečnou množinu stavů
bez další logiky. Vlastní třída je lepší tam, kde typ nese validaci, výpočty
nebo kompozici více hodnot – `Money`, `Email`, `DateRange`.
:::

## 06.06 Repozitáře (Repositories) {#repositories}

Doménová vrstva by neměla vědět, jestli agregát žije v PostgreSQL, MongoDB,
nebo v paměti. Repozitář je rozhraní, které tuto neznalost umožňuje – pro doménu
vypadá jako kolekce agregátů v paměti, skutečné uložení řeší implementace
v infrastrukturní vrstvě.

:::code{language="php" filename="src/OrderManagement/Domain/Repository/OrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Repository;

use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\UserId;

interface OrderRepository
{
    public function save(Order $order): void;

    public function findById(OrderId $id): ?Order;

    public function findByUserId(UserId $userId): array;
}
:::

`OrderRepository` v ukázce definuje metody pro ukládání a načítání objednávek.
Implementaci si volí infrastruktura – nejčastěji Doctrine ORM, ale stejně dobře
in-memory varianta pro testy. Praktickou implementaci v Symfony 8 popisuje kapitola
[Implementace v Symfony 8](/implementace-v-symfony).

## 06.07 Doménové služby (Domain Services) {#domain-services}

Některá pravidla nepatří jednomu agregátu ani jednomu hodnotovému objektu –
koordinují více objektů nebo zachycují proces, který nemá vlastníka. Doménová
služba je bezstavové místo, kam takovou logiku umístit. Nedrží stav, nemá
životní cyklus, jen pracuje s entitami a hodnotovými objekty.

:::code{language="php" filename="src/OrderManagement/Domain/Service/ShippingFeeService.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Service;

use App\OrderManagement\Domain\Model\Customer;
use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\ValueObject\Currency;
use App\OrderManagement\Domain\ValueObject\Money;

final class ShippingFeeService
{
    private const int FREE_SHIPPING_FROM_ITEMS = 5;
    private const int FLAT_FEE_CENTS = 99_00;

    public function feeFor(Order $order, Customer $customer): Money
    {
        $freeShipping = $customer->isVip()
            || count($order->items()) >= self::FREE_SHIPPING_FROM_ITEMS;

        return $freeShipping
            ? Money::zero(Currency::CZK)
            : new Money(self::FLAT_FEE_CENTS, Currency::CZK);
    }
}
:::

Pravidlo „doprava zdarma pro VIP zákazníky a velké objednávky“ čte data dvou
agregátů: `Customer` a `Order`. Nepatří ani jednomu z nich – `Customer` o dopravném
nic neví a `Order` nezná věrnostní status zákazníka. `ShippingFeeService` proto obě
znalosti spojuje na jednom místě, bez stavu a bez závislosti na repozitáři či databázi.

:::callout{type="note"}
### Kdy doménová služba vs. metoda na agregátu? {#service-vs-aggregate-heading}

Výpočet celkové částky (`totalAmount()`) je metodou přímo
na agregátu `Order`, protože pracuje výhradně s jeho daty. Doménová služba
je vhodná tehdy, když logika:

- Přesahuje hranice jednoho agregátu a koordinuje více z nich.
- Vyžaduje znalost, která nepatří do žádné konkrétní entity ani agregátu.
- Reprezentuje doménový proces, nikoli stav.
:::

:::callout{type="anti"}
### Časté zneužití: „PaymentService“ {#payment-service-anti-heading}

Rozšířený omyl je doménová služba `PaymentService`, která zkontroluje stav
objednávky a vytvoří `Payment`. Ani jedna z těchto dvou odpovědností službě
nepatří. Kontrola „platit lze jen potvrzenou objednávku“ je invariant agregátu
`Order` (rozbor v kapitole
[Implementace v Symfony 8](/implementace-v-symfony#domain-services)).
A samotná tvorba `Payment` z dat objednávky je Factory – nejčastěji statická
factory metoda:

:::code{language="php" filename="src/OrderManagement/Domain/Model/Payment.php (výřez)"}
public static function forOrder(Order $order, PaymentMethod $method): self
{
    return new self(PaymentId::generate(), $order->id(), $order->totalAmount(), $method);
}
:::

Identifikátor vytváří `PaymentId::generate()`, uvnitř postavené na `Uuid::v7()`
z balíčku `symfony/uid`. Vzor Factory podrobně rozebírá kapitola
[Doplňující taktické vzory](/mene-zname-vzory#factories).
:::

## 06.08 Doménové události (Domain Events) {#domain-events}

„Objednávka byla potvrzena.“ „Platba byla přijata.“ Doménová událost je
neměnný záznam o věci, která se v doméně stala a o které doménoví experti chtějí
vědět. Název je vždy v minulém čase. Událost obsahuje všechna data potřebná k popisu
změny – nespoléhá na pozdější dotazování zdrojového agregátu.

:::code{language="php" filename="src/OrderManagement/Domain/Event/OrderCreatedEvent.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Event;

use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\UserId;

final readonly class OrderCreatedEvent
{
    public \DateTimeImmutable $occurredAt;

    public function __construct(
        public OrderId $orderId,
        public UserId $userId,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
:::

`OrderCreatedEvent` v ukázce nese tři údaje: které objednávky se týká, kterého
uživatele a kdy k vytvoření došlo. Tolik stačí příjemcům, aby na změnu mohli
reagovat bez dalšího dotazu zpět do `OrderManagement`. Vlastnosti jsou veřejné
a `readonly` – událost je neměnný záznam, gettery by jen přidávaly šum.
Domain Events tvoří základ
pro dvě architektonické techniky: oddělení čtení a zápisu v [CQRS](/cqrs) a uložení
stavu jako sekvence událostí v [Event Sourcingu](/event-sourcing).

## 06.09 Agregát a doménové události: lifecycle {#aggregate-root-lifecycle}

Kdo událost vytvoří a kdy se dostane k příjemcům? Odpověď má dvě části. Agregát
událost *zaznamená* ve chvíli, kdy se změna stane – uvnitř doménové metody.
Aplikační vrstva ji *publikuje* až poté, co se změna uložila. Mezi oběma kroky
drží události bázová třída kořene agregátu:

:::code{language="php" filename="src/SharedKernel/Domain/AggregateRoot.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

abstract class AggregateRoot
{
    /** @var list<object> */
    private array $recordedEvents = [];

    protected function record(object $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
:::

Agregát `Order` ze [sekce o agregátech](#aggregates) z této třídy dědí a volá
`record()` ve svých doménových metodách:

:::code{language="php" filename="src/OrderManagement/Domain/Model/Order.php (výřez)"}
class Order extends AggregateRoot
{
    // ... vlastnosti a metody ze sekce 06.05 ...

    public static function create(OrderId $id, UserId $userId): self
    {
        $order = new self($id, $userId);
        $order->record(new OrderCreatedEvent($id, $userId));

        return $order;
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Created) {
            throw new \DomainException('Cannot confirm a non-created order');
        }

        if ($this->items === []) {
            throw new \DomainException('Cannot confirm an empty order');
        }

        $this->status = OrderStatus::Confirmed;
        $this->record(new OrderConfirmedEvent($this->id));
    }
}
:::

`OrderConfirmedEvent` je analogická událost k `OrderCreatedEvent` z předchozí
sekce. Druhou polovinu životního cyklu obstará command handler: uloží agregát
a teprve potom vyzvedne nahrané události přes `releaseEvents()`:

:::code{language="php" filename="src/OrderManagement/Application/Command/CreateOrderHandler.php (výřez)"}
$order = Order::create(OrderId::generate(), $userId);

$this->orders->save($order); // flush proběhne uvnitř repozitáře

foreach ($order->releaseEvents() as $event) {
    $this->eventBus->dispatch($event);
}
:::

Pořadí je závazné. Publikace před flushem by příjemcům oznámila změnu, kterou
databáze mohla odmítnout. Dispatch po flushi má ovšem také slabinu: pád procesu
mezi uložením a publikací znamená ztracenou událost. Plné zapojení do Symfony
(repozitář, event bus přes Messenger) popisuje kapitola
[Implementace v Symfony 8](/implementace-v-symfony#domain-events); spolehlivé
publikování přes transakční outbox řeší [Outbox Pattern](/outbox-pattern).

:::faq{}
- question: Jaký je rozdíl mezi Entitou a Value Objectem?
  answer: 'Entita má jednoznačnou identitu (ID), která ji odlišuje od ostatních instancí i tehdy, sdílejí-li stejné atributy – dva uživatelé se stejným jménem a e-mailem jsou stále dvě různé entity. Value Object identitu nemá a porovnává se podle hodnot všech svých atributů – typické příklady jsou <code>Money</code>, <code>Address</code>, <code>Email</code>. Entitu lze v čase měnit, Value Object je zpravidla neměnný. Srovnání obou konceptů v <a href="#entities">sekci o Entitách</a> a <a href="#value-objects">sekci o Value Objects</a>.'
- question: K čemu slouží Hodnotový objekt (Value Object)?
  answer: 'Hodnotový objekt zapouzdřuje doménový koncept, který je definován pouze svými hodnotami, nikoli identitou – například peněžní částka s měnou, rozsah kalendářních dní nebo e-mailová adresa. Umožňuje přesunout pravidla platnosti a doménové chování blízko dat, která popisují, a eliminuje tzv. Primitive Obsession (používání primitivních typů tam, kde patří doménový pojem). Neměnnost Value Objectu zjednodušuje uvažování o kódu i paralelním přístupu. Více v <a href="#value-objects">sekci o Hodnotových objektech</a>.'
- question: Co je Agregát a proč je jeho hranice důležitá?
  answer: 'Agregát je skupina doménových objektů, které se mění jako jeden celek – přístup k jeho vnitřním částem vede výhradně přes kořenovou entitu (Aggregate Root). Hranice agregátu je zároveň hranicí transakční konzistence: co je uvnitř, musí být po každé operaci ve validním stavu. Správně vymezený agregát brání porušení doménových invariantů a ulehčuje rozhodování o tom, co lze měnit souběžně. Podrobný rozbor v <a href="#aggregates">sekci o Agregátech</a>.'
- question: Jakou roli má Repozitář v DDD?
  answer: 'Repozitář poskytuje doménové vrstvě rozhraní podobné kolekci pro ukládání a načítání agregátů, aniž by doména musela znát konkrétní persistenční technologii. Pro kód v doménové vrstvě vypadá repozitář jako in-memory kolekce objektů; skutečné uložení do databáze probíhá v infrastrukturní vrstvě, která rozhraní implementuje. Díky tomu lze testovat doménu proti in-memory repozitáři a nahradit úložiště bez zásahu do doménových pravidel. Více v <a href="#repositories">sekci o Repozitářích</a>.'
- question: Kdy použít Doménovou službu místo metody na Entitě?
  answer: 'Doménová služba se použije, když operace přirozeně nepatří žádné Entitě ani Value Objectu – koordinuje více agregátů, komunikuje s externím systémem nebo počítá nad kolekcí objektů. Pokud lze chování přirozeně umístit do metody Entity, má vždy přednost. Doménová služba není datový transfer objekt ani aplikační koordinátor – drží doménovou logiku bez stavu. Rozbor a typické případy užití v <a href="#domain-services">sekci o Doménových službách</a>.'
- question: Co je Doménová událost a k čemu slouží?
  answer: 'Doménová událost je neměnný záznam o tom, že se v doméně stalo něco podstatného – například „objednávka byla potvrzena“ nebo „platba byla přijata“. Události umožňují oddělit části systému, které reagují na změny, od částí, které změny vyvolávají: místo přímého volání se publikuje událost a zájemci ji zpracují. V DDD tvoří události také základ pro Event Sourcing a pro komunikaci mezi Bounded Contexty. Detailní rozbor v <a href="#domain-events">sekci o Doménových událostech</a>.'
:::
