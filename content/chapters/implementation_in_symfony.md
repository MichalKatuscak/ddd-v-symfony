---
route: implementation_in_symfony
path: /implementace-v-symfony
title: Implementace DDD v Symfony 8
page_title: "Implementace Domain-Driven Design v Symfony 8 | DDD Symfony"
meta_description: "Mapování DDD konceptů na Symfony 8: adresářová struktura podle Bounded Context, Doctrine ORM, Messenger, services.yaml a Doctrine custom types pro VO."
meta_keywords: "DDD v Symfony, implementace DDD, Symfony 8, bounded contexts, vertikální slice architektura, entity v Symfony, hodnotové objekty v PHP, agregáty, repozitáře Doctrine, doménové služby, PHP 8.4"
og_type: article
published: "2025-04-24"
modified: "2026-04-28"
breadcrumb_name: Implementace v Symfony
schema_type: TechArticle
schema_headline: "Implementace Domain-Driven Design v Symfony 8"
chapter_number: "11"
category: Základy
deck: 'Praktický překlad DDD konceptů do Symfony 8: jak strukturovat projekt podle Bounded Contextů, jak persistovat agregáty přes Doctrine, jak konfigurovat Messenger a kdy sáhnout po Doctrine custom types pro hodnotové objekty.'
reading_time: 35
difficulty: 3
github_examples: Chapter04_Implementation
---

:::callout{type="pattern"}
### Evoluce příkladů napříč průvodcem

Kódové příklady v tomto průvodci záměrně přibývají na komplexitě. Kapitola
[Základní koncepty](/zakladni-koncepty) zjednodušuje příklady
na minimum, aby ilustrovala čistý koncept. Tato kapitola přidává reálné aspekty
implementace v Symfony: oddělení doménových objektů od Doctrine mappingu, ukládání
hodnotových objektů jako primitivních typů kvůli Doctrine hydration a generování
doménových událostí. Kapitola [Anti-vzory](/anti-vzory)
pak ukazuje produkční kvalitu kódu s custom výjimkami, factory metodami a plnou
validací invariantů.
:::

## 11.01 Kde končí DDD a kde začíná Symfony {#ddd-vs-symfony-boundary}

Následující diagram ukazuje hranici mezi čistým DDD kódem (zelená oblast) a Symfony infrastrukturou
(oranžová oblast). Vše v zelené oblasti je čistý PHP bez závislosti na frameworku –
testovatelné v izolaci, přenositelné mezi projekty. Symfony vrstva implementuje kontrakty
definované doménou (repository interface, event dispatch) a zajišťuje HTTP, persistenci a messaging.

:::diagram{fig="11.1-A" title="Hranice mezi DDD a Symfony" src="images/diagrams/3_implementation_in_symfony/boundary.svg"}
:::

Směr závislostí je určující: Symfony závisí na DDD (implementuje jeho rozhraní), nikdy naopak.
Doménová vrstva neimportuje žádný Symfony namespace. Díky tomu lze Doctrine nahradit
jiným ORM nebo Messenger jiným bus systémem, aniž by se dotklo doménové logiky.

## 11.02 Struktura projektu {#project-structure}

Při implementaci DDD s vertikální slice architekturou v Symfony 8 organizujte strukturu projektu podle Bounded Contexts (ohraničených kontextů). Příklad správné struktury:

:::callout{type="pattern"}
### Příklad: Správná struktura projektu pro DDD s vertikální slice architekturou v Symfony 8

:::code{language="bash" filename="snippet.sh"}
src/
├── UserManagement/             # Bounded Context: Správa uživatelů
│   ├── Domain/                 # Doménová vrstva pro UserManagement
│   │   ├── Model/              # Doménové modely
│   │   │   └── User.php
│   │   ├── ValueObject/        # Hodnotové objekty
│   │   │   ├── UserId.php
│   │   │   └── Email.php
│   │   ├── Event/              # Doménové události
│   │   │   └── UserRegistered.php
│   │   └── Repository/         # Repozitáře (rozhraní)
│   │       └── UserRepository.php
│   ├── Infrastructure/         # Infrastruktura pro UserManagement
│   │   └── Repository/         # Implementace repozitářů
│   │       └── DoctrineUserRepository.php
│   ├── Registration/           # Feature: Registrace uživatelů
│   │   ├── Command/            # Commands
│   │   │   ├── RegisterUser.php
│   │   │   └── RegisterUserHandler.php
│   │   ├── Controller/         # Controllers
│   │   │   └── RegistrationController.php
│   │   ├── Form/               # Forms
│   │   │   └── RegistrationFormType.php
│   │   └── View/               # Views
│   │       └── registration.html.twig
│   └── Profile/                # Feature: Profil uživatele
│       ├── Query/              # Queries
│       │   ├── GetUserProfile.php
│       │   └── GetUserProfileHandler.php
│       ├── Controller/         # Controllers
│       │   └── ProfileController.php
│       ├── Form/               # Forms
│       │   └── ProfileFormType.php
│       └── View/               # Views
│           └── profile.html.twig
├── OrderManagement/           # Bounded Context: Správa objednávek
│   ├── Domain/                # Doménová vrstva pro OrderManagement
│   │   ├── Model/             # Doménové modely
│   │   │   ├── Order.php
│   │   │   └── OrderItem.php
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── OrderId.php
│   │   │   └── Money.php
│   │   ├── Event/             # Doménové události
│   │   │   └── OrderCreated.php
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── OrderRepository.php
│   ├── Infrastructure/        # Infrastruktura pro OrderManagement
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineOrderRepository.php
│   ├── Checkout/              # Feature: Pokladna
│   │   ├── Command/           # Commands
│   │   │   ├── CreateOrder.php
│   │   │   └── CreateOrderHandler.php
│   │   ├── Controller/        # Controllers
│   │   │   └── CheckoutController.php
│   │   ├── Form/              # Forms
│   │   │   └── CheckoutFormType.php
│   │   └── View/              # Views
│   │       └── checkout.html.twig
│   └── OrderHistory/          # Feature: Historie objednávek
│       ├── Query/             # Queries
│       │   ├── GetOrderHistory.php
│       │   └── GetOrderHistoryHandler.php
│       ├── Controller/        # Controllers
│       │   └── OrderHistoryController.php
│       └── View/              # Views
│           └── order_history.html.twig
└── Shared/                    # Skutečně sdílené komponenty
    ├── Domain/                # Sdílená doménová logika
    │   └── ValueObject/       # Sdílené hodnotové objekty
    │       └── Id.php         # Abstraktní ID
    └── Infrastructure/        # Sdílená infrastruktura
        └── Persistence/       # Sdílené komponenty pro persistenci
            └── Doctrine/
                └── Mapping/
                    └── MappingTrait.php
:::
:::

Struktura organizuje kód podle ohraničených kontextů (Bounded Contexts) a funkcí (features). Každý ohraničený kontext má vlastní doménovou vrstvu s modely, hodnotovými objekty, událostmi a repozitáři. Závislosti mezi kontexty procházejí přes Application vrstvu nebo události – nikdy přes přímý import doménových tříd cizího kontextu.

:::diagram{fig="11.2-A" title="Struktura projektu s Bounded Contexts" src="images/diagrams/3_implementation_in_symfony/diagram.svg"}
:::

:::callout{type="note"}
### Hlavní principy správné struktury DDD projektu:

- **Izolace domén** – Každá doména (Bounded Context) má svůj vlastní model, který odráží její specifické potřeby a jazyk.
- **Ubiquitous Language** – Každá doména může mít svůj vlastní jazyk, který tým konzistentně používá v kódu.
- **Jasné hranice** – Definované hranice mezi doménami pomáhají vývojářům pochopit, kde končí jedna doména a začíná druhá.
- **Minimalizace závislostí** – Domény by měly být co nejvíce nezávislé, aby změna v jedné doméně neovlivnila jinou doménu.
:::

:::callout{type="warn"}
### Časté chyby při implementaci DDD

Při implementaci DDD v Symfony se vyvarujte těchto častých chyb:

- **Umístění všech doménových modelů do sdílené složky** – Každá doména by měla mít své vlastní modely.
- **Sdílení doménových modelů mezi doménami** – Pokud potřebujete sdílet data mezi doménami, použijte Anti-Corruption Layer nebo Domain Events.
- **Příliš mnoho závislostí mezi doménami** – Domény by měly být co nejvíce nezávislé.
- **Ignorování Ubiquitous Language** – Používejte konzistentní jazyk v kódu, dokumentaci a komunikaci.
:::

## 11.03 Implementace entit {#entities}

Entita v DDD je objekt s jedinečnou, přetrvávající identitou. V Symfony 8 se implementuje jako běžná PHP třída:

:::callout{type="pattern"}
### Příklad: Implementace entity v Symfony 8 {#entity-example-heading}

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Model;

use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;

class User
{
    private readonly string $id;
    private string $name;
    private string $email;
    private readonly string $hashedPassword;
    private readonly \DateTimeImmutable $createdAt;

    /** @var object[] */
    private array $domainEvents = [];

    private function __construct(UserId $id, string $name, Email $email, HashedPassword $password)
    {
        $this->id = $id->value();
        $this->name = $name;
        $this->email = $email->value();
        $this->hashedPassword = $password->value();
        $this->createdAt = new \DateTimeImmutable();

        $this->recordEvent(new UserRegistered($id, $email));
    }

    public static function register(UserId $id, string $name, Email $email, HashedPassword $password): self
    {
        return new self($id, $name, $email, $password);
    }

    public function id(): UserId
    {
        return new UserId($this->id);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return new Email($this->email);
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email->value();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return object[]
     */
    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
:::
:::

V tomto příkladu je `User` entita definovaná svou identitou (`UserId`). Entita neobsahuje žádné Doctrine atributy – doménová vrstva musí zůstat nezávislá na infrastruktuře. Mapování na databázi řeší externí XML soubor (viz sekci [Separace Doctrine mapování](#doctrine-xml-mapping)). Neměnné vlastnosti (`$id`, `$createdAt`) jsou označeny jako `readonly`. Entity také generují doménové události,
které pole `$domainEvents` drží a později uvolní ke zpracování.

:::callout{type="note"}
### Proč ukládáme hodnotové objekty jako primitivní typy? {#doctrine-hydration-heading}

Entita interně ukládá `UserId` a `Email` jako
`string` (ne jako hodnotové objekty): `$this->id = $id->value()`.
Gettery pak vrací nové instance VO: `return new UserId($this->id)`.

Tento kompromis je nutný kvůli **Doctrine hydration** – Doctrine při načítání
entity z databáze nastavuje vlastnosti přímo, bez volání konstruktoru. Kdyby vlastnost
měla typ `UserId`, Doctrine by do ní vložil `string` z databáze,
což by vedlo na TypeError. Alternativou je **Doctrine custom type**
(viz sekci [Doctrine custom types](#doctrine-custom-types)), který automaticky
konvertuje mezi primitivním typem a hodnotovým objektem.
:::

## 11.04 Implementace hodnotových objektů {#value-objects}

Hodnotový objekt v DDD nemá identitu – je definován svými atributy. V Symfony 8 se implementuje jako neměnná PHP třída:

:::callout{type="pattern"}
### Příklad: Implementace hodnotového objektu v Symfony 8 {#value-object-example-heading}

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
:::

`Email` je hodnotový objekt definovaný svou hodnotou. Je neměnný (vlastnost `$value` je `readonly`) a nemá identitu.
Dva e-maily se stejnou hodnotou jsou rovnocenné. Stejně jako entity, ani hodnotové objekty neobsahují Doctrine atributy – mapování řeší externí XML soubor.

## 11.05 Implementace repozitářů {#repositories}

Repozitáře v DDD poskytují rozhraní pro přístup k agregátům. V Symfony 8 se implementují repozitáře jako rozhraní a jejich implementace:

:::callout{type="pattern"}
### Příklad: Implementace repozitáře v Symfony 8 {#repository-example-heading}

:::code{language="php" filename="src/UserManagement/Domain/Repository/UserRepository.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;
}
:::

:::code{language="php" filename="src/UserManagement/Infrastructure/Repository/DoctrineUserRepository.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $eventBus
    ) {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        foreach ($user->releaseDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }

    public function findById(UserId $id): ?User
    {
        return $this->entityManager->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email->value()]);
    }
}
:::
:::

V tomto příkladu je `UserRepository` rozhraní, které definuje metody pro ukládání a načítání uživatelů.
`DoctrineUserRepository` je implementace tohoto rozhraní, která používá Doctrine ORM pro persistenci.

:::callout{type="warn"}
### Dispatch událostí a transakční bezpečnost {#event-dispatch-heading}

Příklad výše dispatchuje události **po** volání
`flush()`. Pokud dispatch selže (např. Messenger transport je nedostupný),
data se sice persistovala, ale události se nezpracovaly – dochází k nekonzistenci.

V produkčních systémech existují dva spolehlivější přístupy:

- **Outbox pattern** – události se uloží do databázové tabulky `outbox`
  v téže transakci jako agregát. Separátní worker poté události přečte a dispatchuje.
  Tím se zaručí atomicita. Podrobněji viz
  [Outbox a transakční doručování událostí](/event-sourcing#outbox)
  a recept v [DDD v praxi – B1](/ddd-v-praxi-kde-to-boli#b1-outbox).
- **Doctrine lifecycle events** – Doctrine volá `postFlush`
  po dokončení `flush()`, ale **před commitem transakce**, pokud
  používáte explicitní transakce. Pro dispatch po úspěšném commitu použijte
  `postTransactionCommit` event (Doctrine ORM 2.14+) nebo vlastní
  middleware v Symfony Messenger.
:::

## 11.06 Separace Doctrine mapování pomocí XML {#doctrine-xml-mapping}

V předchozích příkladech jste si všimli, že doménové entity a hodnotové objekty neobsahují žádné Doctrine atributy (`#[ORM\Entity]`, `#[ORM\Column]` apod.).
To je záměrné. V čistém DDD přístupu doménová vrstva nemá závislost na infrastruktuře, včetně ORM. Doctrine atributy (dříve anotace) zavádějí přímou vazbu
mezi doménovým modelem a persistenční vrstvou, a tím porušují princip **Dependency Inversion**.

:::callout{type="warn"}
### Proč Doctrine atributy nepatří do doménové vrstvy?

- **Porušení Dependency Inversion Principle** – doménová vrstva (vysokoúrovňový modul) závisí na Doctrine ORM (nízkoúrovňový modul). Správný směr závislosti je opačný.
- **Znečištění doménového modelu** – `use Doctrine\ORM\Mapping as ORM;` v doménové entitě znamená, že doména „ví“ o databázi. Čistá doména by měla být POPO (Plain Old PHP Object).
- **Obtížná záměnitelnost** – pokud chcete změnit persistenční mechanismus (např. z MySQL na MongoDB nebo Event Store), musíte upravovat doménové entity.
- **Komplikace testování** – unit testy doménových entit nepotřebují Doctrine, ale atributy vytvářejí nepříjemnou závislost při autoloadingu.
:::

Řešením je **XML mapování**. Mapovací soubory se umístí do konfiguračního adresáře
mimo doménovou vrstvu:

:::callout{type="pattern"}
### Příklad: Struktura souborů pro XML mapování {#xml-mapping-structure-heading}

:::code{language="bash" filename="snippet.sh"}
config/
└── doctrine/
    ├── UserManagement/
    │   └── User.orm.xml
    └── OrderManagement/
        ├── Order.orm.xml
        └── OrderItem.orm.xml
:::
:::

:::callout{type="pattern"}
### Příklad: XML mapování entity Order {#xml-mapping-example-heading}

:::code{language="xml" filename="snippet.xml"}
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\OrderManagement\Domain\Model\Order" table="orders">
        <id name="id" type="string" length="36">
            <generator strategy="NONE"/>
        </id>

        <field name="customerId" type="string" length="36" column="customer_id"/>
        <field name="status" type="string" length="20" column="status"/>
        <field name="createdAt" type="datetime_immutable" column="created_at"/>

        <one-to-many field="items" target-entity="App\OrderManagement\Domain\Model\OrderItem" mapped-by="order">
            <cascade>
                <cascade-persist/>
                <cascade-remove/>
            </cascade>
        </one-to-many>
    </entity>

</doctrine-mapping>
:::
:::

:::callout{type="pattern"}
### Příklad: Konfigurace Doctrine pro XML mapování v Symfony {#xml-mapping-config-heading}

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
# config/packages/doctrine.yaml
doctrine:
    orm:
        mappings:
            UserManagement:
                type: xml
                dir: '%kernel.project_dir%/config/doctrine/UserManagement'
                prefix: App\UserManagement\Domain\Model
                alias: UserManagement
                is_bundle: false
            OrderManagement:
                type: xml
                dir: '%kernel.project_dir%/config/doctrine/OrderManagement'
                prefix: App\OrderManagement\Domain\Model
                alias: OrderManagement
                is_bundle: false
:::
:::

:::callout{type="note"}
### Výhody XML mapování pro DDD

- **Čistá doménová vrstva** – entity jsou prosté PHP objekty (POPO) bez jakýchkoliv závislostí na frameworku či ORM.
- **Validace schématem** – XML soubory lze validovat proti XSD schématu Doctrine, čímž se snižuje riziko chyb v mapování.
- **Oddělení odpovědností** – mapování je infrastrukturní záležitost a patří do infrastrukturní vrstvy, nikoliv do domény.
- **Záměna persistence bez zásahu do domény** – při změně persistenčního mechanismu stačí vyměnit mapovací soubory, doménové entity zůstanou nedotčeny.
:::

## 11.07 Doctrine custom types pro Value Objects {#doctrine-custom-types}

Alternativou k ukládání hodnotových objektů jako primitivních typů (viz
[implementace entit](#entities)) je **Doctrine custom type**.
Ten automaticky konvertuje mezi primitivním databázovým typem a doménovým hodnotovým objektem.
Entita pak může mít vlastnosti přímo typu `Email` nebo `UserId`.

:::callout{type="pattern"}
### Příklad: Doctrine custom type pro Email {#custom-type-example-heading}

:::code{language="php" filename="src/UserManagement/Infrastructure/Doctrine/Type/EmailType.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Doctrine\Type;

use App\UserManagement\Domain\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class EmailType extends StringType
{
    public const NAME = 'email_vo';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Email
    {
        if ($value === null) {
            return null;
        }

        return new Email((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Email ? $value->value() : (string) $value;
    }

}
:::
:::

:::callout{type="pattern"}
### Registrace custom type v Symfony {#custom-type-registration-heading}

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            email_vo:
                class: App\UserManagement\Infrastructure\Doctrine\Type\EmailType
:::

:::code{language="xml" filename="snippet.xml"}
<!-- config/doctrine/UserManagement/User.orm.xml -->
<field name="email" type="email_vo" length="255" column="email"/>
:::
:::

:::callout{type="note"}
### Kdy použít custom type vs. primitivní ukládání?

- **Custom type** – čistší doménový model, entita pracuje přímo s VO. Vhodné pro value objects používané na mnoha místech.
- **Primitivní ukládání** – jednodušší, méně kódu. Vhodné pro projekty s méně value objects nebo při začátku s DDD.
:::

## 11.08 PHP 8.1+ Enums pro stavové typy {#php-enums}

Od PHP 8.1 jsou k dispozici nativní výčtové typy (enums). V DDD se hodí pro stavové hodnotové objekty
s konečnou množinou hodnot – například stav objednávky, stav úkolu nebo roli uživatele. Dříve se tyto stavy modelovaly jako konstanty ve třídách
nebo jako plnohodnotné hodnotové objekty. Nativní enums nabízejí typovou bezpečnost přímo na úrovni jazyka.

:::callout{type="pattern"}
### Příklad: Backed enum pro stav objednávky {#enum-example-heading}

:::code{language="php" filename="src/OrderManagement/Domain/ValueObject/OrderStatus.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\ValueObject;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    /**
     * Vrátí stavy, do kterých je možné z aktuálního stavu přejít.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PAID, self::CANCELLED],
            self::PAID => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
:::
:::

:::callout{type="pattern"}
### Příklad: Použití enum v doménové entitě {#enum-usage-heading}

:::code{language="php" filename="src/OrderManagement/Domain/Model/Order.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Model;

use App\OrderManagement\Domain\Event\OrderCreated;
use App\OrderManagement\Domain\Event\OrderStatusChanged;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\OrderStatus;

final class Order
{
    private readonly string $id;
    private OrderStatus $status;
    private readonly \DateTimeImmutable $createdAt;

    /** @var object[] */
    private array $domainEvents = [];

    public function __construct(OrderId $id)
    {
        $this->id = $id->value();
        $this->status = OrderStatus::DRAFT;
        $this->createdAt = new \DateTimeImmutable();

        $this->recordEvent(new OrderCreated($id));
    }

    public function id(): OrderId
    {
        return new OrderId($this->id);
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function transitionTo(OrderStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \DomainException(sprintf(
                'Nelze přejít ze stavu "%s" do stavu "%s".',
                $this->status->value,
                $newStatus->value
            ));
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        $this->recordEvent(new OrderStatusChanged(
            new OrderId($this->id),
            $oldStatus,
            $newStatus
        ));
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return object[]
     */
    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
:::
:::

:::callout{type="note"}
### Kdy použít enum a kdy plnohodnotný hodnotový objekt?

- **Enum** – pro jednoduché konečné stavy, kde hodnota je jedna z pevně daných variant: `OrderStatus`, `UserRole`, `TaskPriority`, `Currency`. Enums podporují metody, takže lze zapouzdřit i přechodovou logiku (viz `allowedTransitions()`).
- **Plnohodnotný hodnotový objekt (Value Object)** – pro komplexní typy, které vyžadují validaci, formátování nebo aritmetiku: `Money` (částka + měna + zaokrouhlování), `Email` (validace formátu), `Address` (více polí), `DateRange` (interval s logikou překrývání).

Obecné pravidlo: pokud typ má konečný, předem známý počet hodnot a nepotřebuje složitou vnitřní logiku, je enum správná volba. Pokud typ obsahuje libovolné hodnoty, validaci nebo výpočty, použijte hodnotový objekt.
:::

:::callout{type="warn"}
### Doctrine a PHP enums

Doctrine ORM 2.11+ a 3.x nativně podporují PHP enums. Při použití XML mapování stačí u pole uvést `type="string"` a `enumType="App\OrderManagement\Domain\ValueObject\OrderStatus"`.
Doctrine automaticky konvertuje hodnotu mezi PHP enum a databázovým sloupcem. Pro backed enums se ukládá backing value (`string` nebo `int`).
:::

## 11.09 Implementace doménových služeb {#domain-services}

Doménové služby v DDD poskytují doménovou logiku, která nepatří přirozeně do žádné entity nebo hodnotového objektu.
V Symfony 8 se implementují doménové služby jako běžné PHP třídy:

:::callout{type="pattern"}
### Příklad: Implementace doménové služby v Symfony 8 {#domain-service-example-heading}

:::code{language="php" filename="src/OrderManagement/Domain/Service/PaymentService.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Service;

use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\Model\Payment;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\OrderStatus;
use App\OrderManagement\Domain\ValueObject\PaymentId;
use App\OrderManagement\Domain\ValueObject\PaymentMethod;

class PaymentService
{
    public function processPayment(Order $order, Money $amount, PaymentMethod $paymentMethod): Payment
    {
        if ($order->status() !== OrderStatus::CONFIRMED) {
            throw new \DomainException('Cannot process payment for a non-confirmed order');
        }

        return new Payment(
            new PaymentId(),
            $order->id(),
            $amount,
            $paymentMethod
        );
    }
}
:::
:::

V tomto příkladu je `PaymentService` doménová služba, která zapouzdřuje doménovou logiku
zpracování plateb a vytváří objekt `Payment`. Doménová služba je bezstavová a
neobsahuje repozitáře – persistence vraceného objektu je zodpovědností volající vrstvy
(Application Service nebo Command Handler).

## 11.10 Specification Pattern {#specification-pattern}

Specification Pattern (Eric Evans, *DDD*, kap. 9) zapouzdřuje doménová pravidla a podmínky
do samostatných, znovu použitelných objektů. Specifikace odpovídá na otázku „splňuje tento objekt
dané kritérium?“ a lze ji použít pro validaci, filtrování i vyhledávání.

:::callout{type="pattern"}
### Příklad: Specification Pattern v PHP {#specification-example-heading}

:::code{language="php" filename="src/Shared/Domain/Specification/Specification.php"}
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

/**
 * Generický interface pro Specification Pattern.
 * @template T
 */
interface Specification
{
    /** @param T $candidate */
    public function isSatisfiedBy(mixed $candidate): bool;
}
:::

:::code{language="php" filename="src/OrderManagement/Domain/Specification/OrderEligibleForShipping.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Specification;

use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Specification\Specification;

/**
 * Specifikace: objednávka je způsobilá k expedici.
 * V produkčním kódu by specifikace ověřovala více podmínek
 * (platba přijata, dodací adresa vyplněna, skladová dostupnost).
 * @implements Specification<Order>
 */
final class OrderEligibleForShipping implements Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate->status() === OrderStatus::PAID;
    }
}
:::

:::code{language="php" filename="src/OrderManagement/Domain/Service/ShippingService.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Service;

use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\Specification\OrderEligibleForShipping;
use App\OrderManagement\Domain\ValueObject\OrderStatus;

// Použití specifikace v doménové službě:

final class ShippingService
{
    public function __construct(
        private readonly OrderEligibleForShipping $shippingSpec,
    ) {}

    public function shipOrder(Order $order): void
    {
        if (!$this->shippingSpec->isSatisfiedBy($order)) {
            throw new \DomainException('Objednávka nesplňuje podmínky pro expedici.');
        }

        $order->transitionTo(OrderStatus::SHIPPED);
    }
}
:::
:::

:::callout{type="note"}
### Kdy použít Specification Pattern?

- **Validace** – ověření, zda doménový objekt splňuje doménové pravidlo.
- **Selekce** – filtrování kolekcí objektů podle kritéria.
- **Vytváření na zakázku** – zajištění, že nově vytvořený objekt splňuje invarianty.
- **Kombinace pravidel** – specifikace lze skládat pomocí `AndSpecification`, `OrSpecification`, `NotSpecification`.
:::

## 11.11 Implementace doménových událostí {#domain-events}

Doménové události v DDD reprezentují něco, co se stalo v doméně. V Symfony 8 se implementují doménové události jako neměnné PHP třídy:

:::callout{type="pattern"}
### Příklad: Implementace doménové události v Symfony 8 {#domain-event-example-heading}

:::code{language="php" filename="src/UserManagement/Domain/Event/UserRegistered.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Event;

use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;

class UserRegistered
{
    private readonly string $userId;
    private readonly string $email;
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct(UserId $userId, Email $email)
    {
        $this->userId = $userId->value();
        $this->email = $email->value();
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function userId(): UserId
    {
        return new UserId($this->userId);
    }

    public function email(): Email
    {
        return new Email($this->email);
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
:::
:::

V tomto příkladu je `UserRegistered` doménová událost, která reprezentuje registraci nového uživatele.
Tato událost obsahuje informace o tom, který uživatel byl registrován, jaký má e-mail a kdy se to stalo.

:::callout{type="note"}
### Symfony EventDispatcher vs. Messenger pro doménové události {#dispatcher-vs-messenger-heading}

Symfony nabízí dva mechanismy pro práci s událostmi – každý pro jiný účel:

- **EventDispatcher** (`EventDispatcherInterface`) – synchronní,
  in-process. Všechny listenery se provedou okamžitě v témž PHP požadavku.
  Vhodné pro: side effects v téže transakci (aktualizace cache, logování).
- **Messenger** (`MessageBusInterface`) – může být synchronní
  i asynchronní. Podporuje transporty (RabbitMQ, Redis, Doctrine), retry strategii
  a sériovou serializaci. Vhodné pro: komunikaci mezi Bounded Contexts, asynchronní
  projekce, notifikace.

**Doporučení:** Pro doménové události v DDD preferujte **Messenger**,
protože umožňuje pozdější přechod na asynchronní zpracování bez změny kódu producenta.
EventDispatcher používejte pro Symfony-specifické události (kernel events, form events).
:::

## 11.12 Strategie zpracování chyb v DDD {#error-handling}

V DDD se výjimky liší podle vrstvy, ve které vznikají. Každá vrstva
má jiné odpovědnosti a jiný typ chyb:

:::callout{type="note"}
### Typy výjimek podle vrstvy {#exception-types-heading}

- **Doménové výjimky** – porušení doménových pravidel a invariantů.
  Vyhazuje je doménový model (entity, agregáty, value objects).
  Příklady: `OrderCannotBeConfirmedException`,
  `InsufficientFundsException`, `InvalidEmailException`.
- **Aplikační výjimky** – chyby na úrovni use case.
  Vyhazují je command/query handlery.
  Příklady: `UserNotFoundException`,
  `DuplicateEmailException`.
- **Infrastrukturní výjimky** – technické chyby (databáze, síť, souborový systém).
  Vznikají v infrastrukturní vrstvě a zachytává je aplikační vrstva.
  Příklady: `ConnectionException`, `TimeoutException`.
:::

:::callout{type="pattern"}
### Příklad: Vlastní doménová výjimka {#custom-exception-heading}

:::code{language="php" filename="src/OrderManagement/Domain/Exception/InvalidOrderStateTransitionException.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Exception;

/**
 * Výjimka vyhazovaná při porušení pravidel přechodu stavu objednávky.
 */
final class InvalidOrderStateTransitionException extends \DomainException
{
    public static function cannotTransition(string $from, string $to): self
    {
        return new self(sprintf(
            'Nelze přejít ze stavu "%s" do stavu "%s".',
            $from,
            $to,
        ));
    }
}
:::
:::

:::callout{type="warn"}
### Doporučení pro výjimky v DDD

- Doménové výjimky by měly **dědit z `\DomainException`** – tím signalizují, že jde o porušení doménového pravidla, ne o technickou chybu.
- Používejte **statické factory metody** (`cannotTransition()`) pro čitelné a konzistentní vytváření výjimek.
- **Nepropagujte infrastrukturní výjimky** do doménové vrstvy – repozitáře by je měly zachytit a přeložit na doménové výjimky.
- Kontrolery by měly zachytávat doménové výjimky a **překládat je na HTTP odpovědi** (400, 404, 409).
:::

## 11.13 Implementace aplikačních služeb {#application-services}

Aplikační služby v DDD koordinují aplikační aktivity a delegují práci doménové vrstvě. V Symfony 8 se implementují aplikační služby
jako command a query handlery:

:::callout{type="pattern"}
### Příklad: Implementace command handleru v Symfony 8 {#command-handler-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUser.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

class RegisterUser
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password
    ) {
    }
}
:::

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUserHandler.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RegisterUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(RegisterUser $command): void
    {
        $email = new Email($command->email);

        if ($this->userRepository->findByEmail($email)) {
            throw new \DomainException('User with this email already exists');
        }

        $user = User::register(
            new UserId(),
            $command->name,
            $email,
            HashedPassword::fromPlainText($command->password)
        );

        $this->userRepository->save($user);
    }
}
:::
:::

:::callout{type="pattern"}
### Příklad: Implementace query handleru v Symfony 8 {#query-handler-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/Query/GetUserProfile.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Query;

class GetUserProfile
{
    public function __construct(
        public readonly string $userId
    ) {
    }
}
:::

:::code{language="php" filename="src/UserManagement/Profile/Query/GetUserProfileHandler.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Query;

use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetUserProfileHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(GetUserProfile $query): ?UserProfileViewModel
    {
        $user = $this->userRepository->findById(new UserId($query->userId));

        if (!$user) {
            return null;
        }

        return new UserProfileViewModel(
            $user->id()->value(),
            $user->name(),
            $user->email()->value(),
            $user->createdAt()
        );
    }
}
:::
:::

`RegisterUserHandler` a `GetUserProfileHandler` jsou aplikační služby (command a query handlery).
Koordinují use case a delegují doménovou logiku na entitu nebo doménovou službu.

:::callout{type="note"}
### Kde validovat: Symfony Validator vs. doménová validace {#validace-kde-heading}

V DDD existují dva druhy validace, každý na jiné vrstvě:

- **Symfony Validator (aplikační vrstva)** – validace vstupních dat
  na úrovni Commands a Queries: formát e-mailu, délka jména, povinná pole.
  Používejte atributy `#[Assert\Email]`, `#[Assert\NotBlank]`
  přímo na command třídách. Tato validace chrání doménovou vrstvu před neplatnými vstupy.
- **Doménová validace (doménová vrstva)** – doménová pravidla, která vynucují
  entity, agregáty a value objects: „uživatel s tímto e-mailem již existuje“,
  „objednávku nelze potvrdit bez položek“. Tato validace je součástí doménového modelu
  a Symfony Validator na ní nesmí záviset.

**Pravidlo:** Symfony Validator řeší *syntaktickou* validaci (formát),
doménová vrstva řeší *sémantickou* validaci (doménová pravidla).
:::

## 11.14 Implementace kontrolerů {#controllers}

Kontrolery v DDD jsou součástí prezentační vrstvy a zodpovídají za interakci s uživatelem. V Symfony 8 se implementují kontrolery
jako běžné Symfony kontrolery:

:::callout{type="pattern"}
### Příklad: Implementace kontroleru v Symfony 8 {#controller-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Controller/RegistrationController.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Controller;

use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, MessageBusInterface $commandBus): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $command = new RegisterUser(
                $data['name'],
                $data['email'],
                $data['password']
            );

            try {
                $commandBus->dispatch($command);

                $this->addFlash('success', 'Your account has been created. You can now log in.');

                return $this->redirectToRoute('app_login');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('@UserManagement/Registration/View/registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
:::
:::

V tomto příkladu je `RegistrationController` kontroler, který zpracovává registraci uživatele.
Kontroler vytváří formulář, zpracovává požadavek a odesílá příkaz `RegisterUser` přes command bus.

## 11.15 Dependency Injection a autowiring {#dependency-injection}

Dependency Injection odděluje závislosti a umožňuje testování bez reálné infrastruktury.
Symfony 8 poskytuje DI Container pro konfiguraci služeb:

:::callout{type="pattern"}
### Příklad: Konfigurace služeb v Symfony 8 {#dependency-injection-example-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml"}
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Registrace všech služeb v adresáři src/
    App\:
        resource: '../src/'
        exclude:
            - '../src/Kernel.php'
            - '../src/*/Domain/Model/'
            - '../src/*/Domain/ValueObject/'
            - '../src/*/Domain/Event/'

    # Explicitní konfigurace repozitářů pro UserManagement doménu
    App\UserManagement\Domain\Repository\UserRepository:
        class: App\UserManagement\Infrastructure\Repository\DoctrineUserRepository

    # Explicitní konfigurace repozitářů pro OrderManagement doménu
    App\OrderManagement\Domain\Repository\OrderRepository:
        class: App\OrderManagement\Infrastructure\Repository\DoctrineOrderRepository

    # Messenger busy se konfigurují v config/packages/messenger.yaml, nikoli zde:
    # framework:
    #     messenger:
    #         buses:
    #             command.bus:
    #                 middleware:
    #                     - validation
    #                     - doctrine_transaction
    #             query.bus:
    #                 middleware:
    #                     - validation
:::
:::

Repozitáře konfigurujeme explicitně, aby Symfony DI Container bindoval rozhraní na konkrétní implementaci.
Doménové modely, hodnotové objekty a události z auto-registrace vylučujeme – nejsou to služby, ale data.

### Autowiring s oddělenými Bounded Contexts {#autowiring-bounded-contexts}

Ve větších projektech s více Bounded Contexts konfigurujte autowiring pro každý kontext samostatně.
Každý kontext dostane vlastní blok v `services.yaml`, čímž ohraničíme kontext i na úrovni service containeru.

:::callout{type="pattern"}
### Příklad: Samostatný autowiring pro každý Bounded Context {#autowiring-bc-example-heading}

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # ──────────────────────────────────────────────────
    # Bounded Context: UserManagement
    # ──────────────────────────────────────────────────
    App\UserManagement\:
        resource: '../src/UserManagement/'
        exclude:
            - '../src/UserManagement/Domain/Model/'
            - '../src/UserManagement/Domain/ValueObject/'
            - '../src/UserManagement/Domain/Event/'

    App\UserManagement\Domain\Repository\UserRepository:
        class: App\UserManagement\Infrastructure\Repository\DoctrineUserRepository

    # ──────────────────────────────────────────────────
    # Bounded Context: OrderManagement
    # ──────────────────────────────────────────────────
    App\OrderManagement\:
        resource: '../src/OrderManagement/'
        exclude:
            - '../src/OrderManagement/Domain/Model/'
            - '../src/OrderManagement/Domain/ValueObject/'
            - '../src/OrderManagement/Domain/Event/'

    App\OrderManagement\Domain\Repository\OrderRepository:
        class: App\OrderManagement\Infrastructure\Repository\DoctrineOrderRepository

    # ──────────────────────────────────────────────────
    # Shared: sdílené komponenty napříč kontexty
    # ──────────────────────────────────────────────────
    App\Shared\:
        resource: '../src/Shared/'
        exclude:
            - '../src/Shared/Domain/ValueObject/'
:::
:::

:::callout{type="note"}
### Výhody odděleného autowiringu pro Bounded Contexts

- **Explicitní hranice** – každý bounded context má svůj vlastní blok konfigurace, což jasně dokumentuje hranice kontextů i na úrovni infrastruktury.
- **Granulární exclude pravidla** – můžete pro každý kontext vyloučit jiné adresáře (např. jeden kontext může mít doménové služby, jiný ne).
- **Snazší refaktoring** – při přesunu bounded contextu do samostatného balíčku (bundle) nebo microservice stačí odebrat příslušný blok z `services.yaml`.
- **Prevence nechtěných závislostí** – pokud kontext A omylem importuje třídu z kontextu B, lze to odhalit v konfiguraci.
:::

:::callout{type="note"}
### Co patří do sdílené složky (Shared)? {#shared-folder-heading}

Do sdílené složky by měly patřit pouze skutečně sdílené komponenty, které nemají specifický doménový význam:

- Abstraktní třídy pro ID, Entity, ValueObject
- Utility pro práci s datem a časem
- Obecné výjimky
- Infrastrukturní komponenty používané napříč doménami

Doménové modely, hodnotové objekty a repozitáře by měly být umístěny v příslušných doménách, nikoli ve sdílené složce.
:::

:::faq{}
- question: Kam v Symfony projektu patří doménová vrstva a proč ji držet odděleně?
  answer: 'Doménová vrstva se umisťuje do samostatného adresáře – typicky <code>src/Domain/</code> s podsložkami pro jednotlivé Bounded Contexty – odděleně od kontrolerů, Doctrine mapování a infrastruktury. Izolace umožňuje testovat a refaktorovat model bez závislosti na Symfony životním cyklu a dovoluje přenést doménu i do jiného technologického stacku. Viz <a href="#project-structure">sekci Struktura projektu</a>.'
- question: Jak mapovat agregát v Doctrine bez toho, aby doména závisela na ORM?
  answer: 'Doctrine ORM 3 podporuje mapování entit pomocí XML souborů držených mimo PHP třídy. Doménová třída tak nenese žádné atributy frameworku a zůstává čistým POPO (Plain Old PHP Object). Mapovací soubor popisuje, jak se agregát ukládá a rekonstruuje, zatímco model může samostatně žít i při výměně ORM. Praktický příklad v <a href="#doctrine-xml-mapping">sekci Separace Doctrine mapování pomocí XML</a>.'
- question: Jak odlišit Aplikační službu od Doménové služby?
  answer: 'Doménová služba drží čistou doménovou logiku, která přirozeně nepatří žádnému agregátu ani hodnotovému objektu – je bezstavová a nekomunikuje s infrastrukturou. Aplikační služba naopak orchestruje use case: přijme vstup z kontroleru, načte agregáty přes repozitář, zavolá doménovou logiku a předá výsledek k persistenci. Aplikační služba nikdy neobsahuje doménová pravidla, pouze posloupnost kroků. Podrobný rozbor v <a href="#application-services">sekci Aplikační služby</a> a <a href="#domain-services">Doménové služby</a>.'
- question: Mají doménové operace vyhazovat výjimky, nebo vracet Result typ?
  answer: 'V PHP a Symfony ekosystému jsou výjimky dominantní cestou. Při porušení invariantu agregát vyhodí konkrétní doménovou výjimku (například <code>InsufficientFundsException</code>). Aplikační vrstva ji přeloží na HTTP odpověď nebo zprávu uživateli. Result/Either typ je v PHP možný, ale přidává složitost bez odpovídajícího přínosu. Kontrolery zachytávají jen doménové podtypy, nikdy obecnou <code>Exception</code>. Rozbor variant v <a href="#error-handling">sekci Strategie zpracování chyb</a>.'
- question: Kdy použít Doctrine Custom Type pro Value Object?
  answer: 'Doctrine Custom Type se hodí tam, kde se hodnotový objekt ukládá jako jednoduchá hodnota v jednom sloupci – peněžní částka, e-mail, URL, vlastní identifikátor. Custom Type přeloží hodnotový objekt při zápisu do primitivu a při čtení ho zpět rekonstruuje. Doménový kód pak pracuje vždy s typovým objektem. Pro hodnotové objekty složené z více sloupců je vhodnější <code>embeddable</code> mapování. Detailní rozbor v <a href="#doctrine-custom-types">sekci Doctrine custom types pro Value Objects</a>.'
:::
