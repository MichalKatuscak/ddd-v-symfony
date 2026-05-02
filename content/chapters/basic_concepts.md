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

Ohraničený kontext je explicitní hranice, ve které je model platný
[[1]](https://martinfowler.com/bliki/BoundedContext.html).
Uvnitř ohraničeného kontextu existuje konzistentní model a všudypřítomný jazyk.
Různé ohraničené kontexty mají různé modely a jazyky – to je záměr, ne nedostatek.

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

V tomto příkladu jsou `OrderManagement` a `UserManagement` dva ohraničené
kontexty.
Každý kontext má svůj vlastní model a jazyk. `OrderManagement` reprezentuje uživatele pouze
jako `UserId`; `UserManagement` ho modeluje jako plnohodnotnou entitu `User`.
Kompletní příklad rozdělení reálného systému do pěti bounded contexts je v kapitole
[Případová studie – Doménová analýza](/pripadova-studie#discovery).

## 06.02 Všudypřítomný jazyk (Ubiquitous Language) {#ubiquitous-language}

Všudypřítomný jazyk je společný jazyk používaný vývojáři a doménovými experty
[[2]](https://martinfowler.com/bliki/UbiquitousLanguage.html).
Používá se v kódu, dokumentaci i v běžné komunikaci a pomáhá překonávat bariéry mezi vývojáři
a doménovými experty.

:::diagram{fig="06.2-A" title="Všudypřítomný jazyk" src="images/diagrams/4_ubiquitous_language/diagram.svg"}
:::

:::callout{type="note"}
### Příklad: Všudypřítomný jazyk v e-commerce doméně

V e-commerce doméně by všudypřítomný jazyk mohl zahrnovat pojmy jako:

- **Košík (Cart)** – Dočasná kolekce produktů, které si zákazník vybral k nákupu.
- **Objednávka (Order)** – Potvrzený nákup zákazníka, který obsahuje produkty, dodací adresu a platební informace.
- **Katalog (Catalog)** – Kolekce všech produktů dostupných k prodeji.
- **Zákazník (Customer)** – Osoba, která nakupuje produkty.
- **Produkt (Product)** – Položka, která je dostupná k prodeji.
- **Kategorie (Category)** – Skupina souvisejících produktů.
- **Platba (Payment)** – Transakce, kterou zákazník platí za objednávku.
- **Dodání (Shipping)** – Proces doručení objednávky zákazníkovi.
:::

Tyto pojmy se používají konzistentně v kódu, dokumentaci i v komunikaci mezi vývojáři a doménovými
experty. Pokud se hovoří o osobě, která nakupuje produkty, používá se důsledně termín
„zákazník“ – nikoli obecné „uživatel“.

## 06.03 Entity {#entities}

Entita je objekt, který je definován svou identitou, nikoli svými atributy
[[3]](https://www.domainlanguage.com/ddd/).
Entity mají životní cyklus a mohou se v průběhu času měnit,
ale jejich identita zůstává stejná.

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

V tomto příkladu je `User` entita definovaná identitou (`UserId`).
Uživatel může změnit své jméno nebo e-mail,
ale jeho identita zůstává stejná.

## 06.04 Hodnotové objekty (Value Objects) {#value-objects}

Hodnotové objekty jsou objekty definované svými atributy, nikoli identitou
[[3]](https://www.domainlanguage.com/ddd/).
Hodnotové objekty jsou neměnné (immutable) a porovnávají se hodnotou, nikoli referencí.

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

V tomto příkladu je `Email` hodnotový objekt definovaný svou hodnotou. E-mailová
adresa je neměnná a nemá žádnou identitu.
Dva e-maily se považují za stejné, pokud mají stejnou hodnotu.

## 06.05 Agregáty (Aggregates) {#aggregates}

Agregát je skupina souvisejících objektů, která tvoří jednu transakční hranici konzistence
[[3]](https://www.domainlanguage.com/ddd/).
Každý agregát má kořen agregátu (Aggregate Root),
který je jediným vstupním bodem pro veškeré vnější interakce s agregátem. Špatně zvolená velikost
agregátu patří mezi nejčastější chyby v DDD – velkoobjemové „God Agregaty“ rozebírá kapitola
[Anti-vzory a typické chyby](/anti-vzory).

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

V tomto příkladu je `Order` agregát, který obsahuje kolekci `OrderItem` objektů.
`Order` je kořenem agregátu (Aggregate Root)
a poskytuje metody pro manipulaci s položkami objednávky.

:::callout{type="note"}
### PHP 8.1+ Enum pro stavové typy {#enum-poznamka-heading}

Od PHP 8.1 je pro jednoduché stavové typy (jako `OrderStatus`) idiomatické
použít nativní `enum` místo tradičního hodnotového objektu:

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

**Kdy enum, kdy plný Value Object?** Enum je vhodný pro konečnou množinu stavů
bez další logiky. Plný Value Object (třída) je lepší volbou, pokud typ obsahuje validaci,
výpočty nebo kompozici více hodnot (např. `Money`, `Email`,
`DateRange`).
:::

## 06.06 Repozitáře (Repositories) {#repositories}

Repozitář je objekt, který poskytuje rozhraní pro přístup k agregátům. Repozitáře skrývají detaily
persistence a poskytují
doménově orientované rozhraní pro přístup k datům.

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

V tomto příkladu je `OrderRepository` rozhraní, které definuje metody pro ukládání a načítání
objednávek.
Konkrétní implementace tohoto rozhraní by mohla používat Doctrine ORM nebo jiný mechanismus persistence.
Praktickou implementaci repozitáře v Symfony 8 popisuje kapitola
[Implementace v Symfony 8](/implementace-v-symfony).

## 06.07 Doménové služby (Domain Services) {#domain-services}

Doménová služba je objekt, který poskytuje doménovou logiku, která nepatří přirozeně do žádné entity
nebo hodnotového objektu.
Doménové služby jsou bezstavové a pracují s entitami a hodnotovými objekty.

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

V tomto příkladu je `PaymentService` doménová služba, která zapouzdřuje doménovou logiku
zpracování plateb a vytváří objekt `Payment`. Doménová služba je bezstavová a
neobsahuje repozitáře – persistence vraceného objektu je zodpovědností volající vrstvy
(Application Service nebo Command Handler).
Třídy `Payment`, `PaymentId` a `PaymentMethod` jsou součástí doménového modelu plateb. Jejich implementace následuje stejné principy jako ostatní entity a hodnotové objekty v této kapitole.

:::callout{type="note"}
### Kdy doménová služba vs. metoda na agregátu? {#service-vs-aggregate-heading}

Výpočet celkové částky (`totalAmount()`) je metodou přímo
na agregátu `Order`, protože pracuje výhradně s jeho daty. Doménová služba
je vhodná tehdy, když logika:

- Přesahuje hranice jednoho agregátu (koordinuje více agregátů).
- Vyžaduje znalost, která nepatří do žádného konkrétního agregátu.
- Reprezentuje doménový proces, nikoli stav (např. zpracování platby).

`PaymentService` je oprávněná jako služba, protože
*vytváří nový agregát* (`Payment`) na základě dat jiného agregátu
(`Order`) – tato koordinace nepatří do žádného z nich.
:::

## 06.08 Doménové události (Domain Events) {#domain-events}

Doménová událost je neměnný záznam o skutečnosti, která nastala v doméně a má pro doménové experty
význam. Události jsou vyjádřeny minulým časem a musí obsahovat všechna data potřebná
k popisu dané změny stavu – nespoléhají na pozdější dotazování.

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

V tomto příkladu je `OrderCreatedEvent` doménová událost, která reprezentuje vytvoření nové
objednávky.
Tato událost obsahuje informace o tom, která objednávka byla vytvořena, pro kterého uživatele a kdy
k tomu došlo. Domain Events tvoří základ pro dvě architektonické techniky: oddělení čtení
a zápisu v [CQRS](/cqrs) a uložení stavu jako sekvence událostí v
[Event Sourcingu](/event-sourcing).

:::faq{}
- question: Jaký je rozdíl mezi Entitou a Value Objectem?
  answer: 'Entita má jednoznačnou identitu (ID), která ji odlišuje od ostatních instancí i tehdy, sdílejí-li stejné atributy – dva uživatelé se stejným jménem a e-mailem jsou stále dvě různé entity. Value Object identitu nemá a porovnává se podle hodnot všech svých atributů – typické příklady jsou <code>Money</code>, <code>Address</code>, <code>Email</code>. Entitu lze v čase měnit, Value Object je zpravidla neměnný. Srovnání obou konceptů v <a href="#entities">sekci o Entitách</a> a <a href="#value-objects">sekci o Value Objects</a>.'
- question: K čemu slouží Hodnotový objekt (Value Object)?
  answer: 'Hodnotový objekt zapouzdřuje doménový koncept, který je definován pouze svými hodnotami, nikoli identitou – například peněžní částka s měnou, rozsah kalendářních dní nebo e-mailová adresa. Umožňuje přesunout pravidla platnosti a doménové chování blízko dat, která popisují, a eliminuje tzv. Primitive Obsession (používání primitivních typů tam, kde patří doménový pojem). Neměnnost Value Objectu zjednodušuje uvažování o kódu i paralelním přístupu. Více v <a href="#value-objects">sekci o Hodnotových objektech</a>.'
- question: Co je Agregát a proč je jeho hranice důležitá?
  answer: 'Agregát je shluk doménových objektů, které se mění jako jeden celek – přístup k jeho vnitřním částem vede výhradně přes kořenovou entitu (Aggregate Root). Hranice agregátu je zároveň hranicí transakční konzistence: co je uvnitř, musí být po každé operaci ve validním stavu. Správně vymezený agregát brání porušení doménových invariantů a ulehčuje rozhodování o tom, co lze měnit souběžně. Podrobný rozbor v <a href="#aggregates">sekci o Agregátech</a>.'
- question: Jakou roli má Repozitář v DDD?
  answer: 'Repozitář poskytuje doménové vrstvě rozhraní podobné kolekci pro ukládání a načítání agregátů, aniž by doména musela znát konkrétní persistenční technologii. Pro kód v doménové vrstvě vypadá repozitář jako in-memory kolekce objektů; skutečné uložení do databáze probíhá v infrastrukturní vrstvě, která rozhraní implementuje. Díky tomu lze testovat doménu proti in-memory repozitáři a nahradit úložiště bez zásahu do doménových pravidel. Více v <a href="#repositories">sekci o Repozitářích</a>.'
- question: Kdy použít Doménovou službu místo metody na Entitě?
  answer: 'Doménová služba se použije, když operace přirozeně nepatří žádné Entitě ani Value Objectu – koordinuje více agregátů, komunikuje s externím systémem nebo počítá nad kolekcí objektů. Pokud lze chování přirozeně umístit do metody Entity, má vždy přednost. Doménová služba není datový transfer objekt ani aplikační koordinátor – drží doménovou logiku bez stavu. Rozbor a typické případy užití v <a href="#domain-services">sekci o Doménových službách</a>.'
- question: Co je Doménová událost a k čemu slouží?
  answer: 'Doménová událost je neměnný záznam o tom, že se v doméně stalo něco podstatného – například „objednávka byla potvrzena“ nebo „platba byla přijata“. Události umožňují oddělit části systému, které reagují na změny, od částí, které změny vyvolávají: místo přímého volání se publikuje událost a zájemci ji zpracují. V DDD tvoří události také základ pro Event Sourcing a pro komunikaci mezi Bounded Contexty. Detailní rozbor v <a href="#domain-events">sekci o Doménových událostech</a>.'
:::
