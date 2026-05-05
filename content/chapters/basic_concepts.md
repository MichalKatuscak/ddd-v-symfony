---
route: basic_concepts
path: /zakladni-koncepty
title: Základní koncepty DDD
page_title: "Základní koncepty Domain-Driven Design | DDD Symfony"
meta_description: "Základní stavební kameny taktického DDD: entity, hodnotové objekty, agregáty, repozitáře, doménové události a služby – s ukázkami v PHP 8.4+ a Symfony 8."
meta_keywords: "DDD koncepty, entity, hodnotové objekty, value objects, kořeny agregátů, aggregate roots, doménové služby, repozitáře, doménové události, Symfony implementace"
og_type: article
published: "2025-04-24"
modified: "2026-04-28"
breadcrumb_name: Základní koncepty
schema_type: TechArticle
schema_headline: "Základní koncepty Domain-Driven Design"
chapter_number: "06"
category: Základy
deck: "Domain-Driven Design nabízí sadu stavebních bloků, které pomáhají převést znalosti o doméně do strukturovaného softwarového modelu. Každý z těchto konceptů řeší konkrétní problém – od vymezení hranic mezi částmi systému přes zachycení identity objektů až po komunikaci mezi komponentami."
reading_time: 18
difficulty: 2
github_examples: Chapter03_BasicConcepts
---

## 06.01 Ohraničené kontexty (Bounded Contexts) {#bounded-contexts}

Doména větší aplikace nikdy nemluví jediným jazykem. Slovo „zákazník“ má v marketingu
jiný význam než ve fakturaci a v expedici jiný než v reklamacích. Ohraničený kontext
je explicitně vymezená oblast, uvnitř které platí jeden konzistentní model a jeden
slovník [[1]](https://martinfowler.com/bliki/BoundedContext.html). Různé kontexty mají
různé modely – to je záměr, ne nedostatek.

:::diagram{fig="06.1-A" title="Ohraničené kontexty" src="images/diagrams/5_bounded_contexts/diagram.svg"}
:::

:::code{language="bash" filename="src/ (struktura)"}
src/
├── OrderManagement/           # Ohraničený kontext: Správa objednávek
│   ├── Domain/
│   │   ├── Model/
│   │   │   ├── Order.php
│   │   │   ├── OrderItem.php
│   │   │   └── OrderStatus.php
│   │   ├── ValueObject/
│   │   │   ├── OrderId.php
│   │   │   └── Money.php
│   │   ├── Event/
│   │   │   └── OrderCreated.php
│   │   └── Repository/
│   │       └── OrderRepository.php
│   └── Application/
│       ├── Command/
│       │   ├── CreateOrder.php
│       │   └── CreateOrderHandler.php
│       └── Query/
│           ├── GetOrder.php
│           └── GetOrderHandler.php
└── UserManagement/            # Ohraničený kontext: Správa uživatelů
    ├── Domain/
    │   ├── Model/
    │   │   ├── User.php
    │   │   └── UserStatus.php
    │   ├── ValueObject/
    │   │   ├── UserId.php
    │   │   └── Email.php
    │   ├── Event/
    │   │   └── UserRegistered.php
    │   └── Repository/
    │       └── UserRepository.php
    └── Application/
        ├── Command/
        │   ├── RegisterUser.php
        │   └── RegisterUserHandler.php
        └── Query/
            ├── GetUser.php
            └── GetUserHandler.php
:::

`OrderManagement` a `UserManagement` jsou ve výše uvedené ukázce dva oddělené kontexty.
Každý má svůj model a jazyk. `OrderManagement` reprezentuje uživatele pouze
jako `UserId`; `UserManagement` ho modeluje jako plnohodnotnou entitu `User`.
Kompletní příklad rozdělení reálného systému do pěti bounded contexts je v kapitole
[Případová studie – Doménová analýza](/pripadova-studie#discovery).

## 06.02 Všudypřítomný jazyk (Ubiquitous Language) {#ubiquitous-language}

Pokud vývojář říká „uživatel“, produktový manažer „klient“ a obchod „lead“, mluví o téže
osobě třemi termíny. Tři termíny znamenají tři různé představy o jejím chování. Všudypřítomný
jazyk tuto trhlinu uzavírá: vývojáři a doménoví experti se domluví na jediném slovníku, který
pak důsledně používají v kódu, dokumentaci i běžné konverzaci
[[2]](https://martinfowler.com/bliki/UbiquitousLanguage.html).

:::diagram{fig="06.2-A" title="Všudypřítomný jazyk" src="images/diagrams/4_ubiquitous_language/diagram.svg"}
:::

:::callout{type="note"}
### Příklad: Všudypřítomný jazyk v e-commerce doméně

Slovník e-shopu obvykle obsahuje pojmy jako produkt, kategorie, košík, zákazník,
objednávka, platba a dodání. Některé jsou triviální, jiné nesou hodně doménové
váhy a vyžadují přesnou definici:

- **Košík** je dočasná kolekce produktů, které si zákazník vybral. Není to ještě objednávka, lze ho opustit a vrátit se k němu.
- **Objednávka** je potvrzený nákup s adresou a platebními údaji. Existuje až po explicitním kroku zákazníka.
- **Zákazník** je osoba, která nakupuje – v tomto jazyce nikdy ne „uživatel“.
- Katalog, kategorie, produkt, platba a dodání mají v tomto kontextu významy odpovídající běžnému užívání.
:::

Tyto pojmy se objevují stejně v kódu, dokumentaci i e-mailu od PM. Pokud kód mluví
o `Customer` a produktový tým o „uživateli“, slovník selhal a je potřeba ho srovnat.

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
    private readonly UserId $id;
    private string $name;
    private Email $email;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(UserId $id, string $name, Email $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
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
}
:::

`User` je v ukázce entita definovaná `UserId`. Uživatel může změnit jméno
i e-mail, identifikátor zůstává stejný.

## 06.04 Hodnotové objekty (Value Objects) {#value-objects}

Dva e-maily se stejným textem nejsou „dvě adresy“ – je to jedna a tatáž hodnota.
Hodnotový objekt je doménový pojem, který identifikuje sám sebe celou svou hodnotou,
ne odděleným ID [[3]](https://www.domainlanguage.com/ddd/). Z toho plynou dvě
vlastnosti: neměnnost (immutable) a rovnost po hodnotě, ne po referenci.

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/Email.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
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

`Email` v ukázce nese pouze normalizovaný řetězec a metodu `equals()`. Žádné
ID, žádné settery. Dva e-maily se shodují právě tehdy, když mají stejnou hodnotu.

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

use App\OrderManagement\Domain\ValueObject\Currency;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\ProductId;
use App\OrderManagement\Domain\ValueObject\UserId;

final class Order
{
    private readonly OrderId $id;
    private readonly UserId $userId;
    private array $items = [];
    private OrderStatus $status;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(OrderId $id, UserId $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->status = OrderStatus::CREATED;
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
        if ($this->status !== OrderStatus::CREATED) {
            throw new \DomainException('Cannot add items to a non-created order');
        }

        $this->items[] = new OrderItem($this->id, $productId, $quantity, $price);
    }

    public function removeItem(ProductId $productId): void
    {
        if ($this->status !== OrderStatus::CREATED) {
            throw new \DomainException('Cannot remove items from a non-created order');
        }

        $this->items = array_filter($this->items, function (OrderItem $item) use ($productId) {
            return !$item->productId()->equals($productId);
        });
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::CREATED) {
            throw new \DomainException('Cannot confirm a non-created order');
        }

        if (empty($this->items)) {
            throw new \DomainException('Cannot confirm an empty order');
        }

        $this->status = OrderStatus::CONFIRMED;
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::CREATED && $this->status !== OrderStatus::CONFIRMED) {
            throw new \DomainException('Cannot cancel a non-created or non-confirmed order');
        }

        $this->status = OrderStatus::CANCELLED;
    }

    public function totalAmount(): Money
    {
        $total = new Money(0, Currency::CZK);

        foreach ($this->items as $item) {
            $total = $total->add($item->unitPrice()->multiply($item->quantity()));
        }

        return $total;
    }

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
    private readonly OrderId $orderId;
    private readonly ProductId $productId;
    private readonly int $quantity;
    private readonly Money $unitPrice;

    public function __construct(OrderId $orderId, ProductId $productId, int $quantity, Money $unitPrice)
    {
        if ($quantity <= 0) {
            throw new \DomainException('Množství musí být kladné.');
        }

        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    public function productId(): ProductId { return $this->productId; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): Money { return $this->unitPrice; }
}
:::

`Order` v ukázce je kořen agregátu a drží kolekci `OrderItem` objektů. Vnější
volání jdou výhradně přes metody na `Order`; vlastní `OrderItem` zvenku
nikdo neinstancuje ani nemění.

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
    case CREATED   = 'created';
    case CONFIRMED = 'confirmed';
    case SHIPPED   = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
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

:::code{language="php" filename="src/OrderManagement/Domain/Service/PaymentService.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Service;

use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\Model\OrderStatus;
use App\OrderManagement\Domain\Model\Payment;
use App\OrderManagement\Domain\ValueObject\PaymentId;
use App\OrderManagement\Domain\ValueObject\PaymentMethod;

class PaymentService
{
    public function processPayment(Order $order, PaymentMethod $paymentMethod): Payment
    {
        if ($order->status() !== OrderStatus::CONFIRMED) {
            throw new \DomainException('Cannot process payment for a non-confirmed order');
        }

        return new Payment(
            new PaymentId(),
            $order->id(),
            $order->totalAmount(),
            $paymentMethod
        );
    }
}
:::

`PaymentService` v ukázce zapouzdřuje logiku zpracování platby a vrací nový objekt
`Payment`. Repozitář ani databázi nezná – uložení vraceného agregátu řeší volající
vrstva (Application Service nebo Command Handler). Třídy `Payment`, `PaymentId`
a `PaymentMethod` patří do doménového modelu plateb a řídí se stejnými principy jako
ostatní entity a hodnotové objekty v této kapitole.

:::callout{type="note"}
### Kdy doménová služba vs. metoda na agregátu? {#service-vs-aggregate-heading}

Výpočet celkové částky (`totalAmount()`) je metodou přímo
na agregátu `Order`, protože pracuje výhradně s jeho daty. Doménová služba
je vhodná tehdy, když logika:

- Přesahuje hranice jednoho agregátu a koordinuje více z nich.
- Vyžaduje znalost, která nepatří do žádné konkrétní entity ani agregátu.
- Reprezentuje doménový proces, nikoli stav (např. zpracování platby).

`PaymentService` je oprávněná jako služba, protože
*vytváří nový agregát* (`Payment`) na základě dat jiného agregátu
(`Order`) – tato koordinace nepatří do žádného z nich.
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

final class OrderCreatedEvent
{
    private readonly OrderId $orderId;
    private readonly UserId $userId;
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct(OrderId $orderId, UserId $userId)
    {
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
:::

`OrderCreatedEvent` v ukázce nese tři údaje: které objednávky se týká, kterého
uživatele a kdy k vytvoření došlo. Tolik stačí příjemcům, aby na změnu mohli
reagovat bez dalšího dotazu zpět do `OrderManagement`. Domain Events tvoří základ
pro dvě architektonické techniky: oddělení čtení a zápisu v [CQRS](/cqrs) a uložení
stavu jako sekvence událostí v [Event Sourcingu](/event-sourcing).

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
