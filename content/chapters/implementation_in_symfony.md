---
route: implementation_in_symfony
path: /implementace-v-symfony
title: Implementace DDD v Symfony 8
page_title: "Implementace Domain-Driven Design v Symfony 8 | DDD Symfony"
meta_description: "Mapování DDD konceptů na Symfony 8: adresářová struktura podle Bounded Context, Doctrine ORM, Messenger, services.yaml a Doctrine custom types pro VO."
meta_keywords: "DDD v Symfony, implementace DDD, Symfony 8, bounded contexts, vertikální slice architektura, entity v Symfony, hodnotové objekty v PHP, agregáty, repozitáře Doctrine, doménové služby, PHP 8.4"
og_type: article
published: "2025-04-24"
modified: "2026-06-09"
breadcrumb_name: Implementace v Symfony
schema_type: TechArticle
schema_headline: "Implementace Domain-Driven Design v Symfony 8"
chapter_number: "10"
category: Architektura
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
implementace v Symfony: Doctrine atributy s custom typy pro hodnotové objekty,
optimistický zámek a generování doménových událostí. Kapitola
[Anti-vzory](/anti-vzory) pak ukazuje produkční kvalitu kódu s vlastními
výjimkami, factory metodami a plnou validací invariantů.
:::

:::callout{type="note"}
### Mapping volba: atributy jako výchozí přístup {#mapping-volba-heading}

Tento průvodce používá **Doctrine atributy přímo na doménových třídách**
(`#[ORM\Entity]`, `#[ORM\Column]`). Argumentem proti je porušení
*Dependency Inversion* – doména „ví“ o Doctrine. V praxi jde o metadata,
ne o chování: třída se chová stejně, pouze nese popisek pro mapper. Symfony Maker,
oficiální dokumentace i drtivá většina open-source Symfony projektů používá atributy.

Pokud chcete striktně oddělenou doménu, korektní cesta není XML mapping (taky
„znečištěné“, jen jiným formátem), ale **Persisted Object Pattern** – samostatná
persistence třída + mapper na doménový agregát. Detail v sekci
[Persisted Object Pattern – čistá DDD varianta](#persisted-object-pattern).
:::

## 10.01 Kde končí DDD a kde začíná Symfony {#ddd-vs-symfony-boundary}

Následující diagram ukazuje hranici mezi čistým DDD kódem (zelená oblast) a Symfony infrastrukturou
(oranžová oblast). Vše v zelené oblasti je čistý PHP bez závislosti na frameworku –
testovatelné v izolaci, přenositelné mezi projekty. Symfony vrstva implementuje kontrakty
definované doménou (repository interface, event dispatch) a zajišťuje HTTP, persistenci a messaging.

:::diagram{fig="10.1-A" title="Hranice mezi DDD a Symfony" src="images/diagrams/3_implementation_in_symfony/boundary.svg"}
:::

Směr závislostí je určující: Symfony závisí na DDD (implementuje jeho rozhraní), nikdy naopak.
Doménová vrstva neimportuje žádný Symfony namespace. Díky tomu lze Doctrine nahradit
jiným ORM nebo Messenger jiným bus systémem, aniž by se dotklo doménové logiky.

## 10.02 Struktura projektu {#project-structure}

Vertikální slice architektura v Symfony 8 organizuje strukturu projektu podle Bounded Contexts (ohraničených kontextů). Každý kontext drží svou doménu, infrastrukturu i feature složky pohromadě. Příklad:

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

Závislosti mezi kontexty procházejí přes Application vrstvu nebo události – nikdy přes přímý import doménových tříd cizího kontextu.

:::diagram{fig="10.2-A" title="Struktura projektu s Bounded Contexts" src="images/diagrams/3_implementation_in_symfony/diagram.svg"}
:::

:::callout{type="note"}
### Hlavní principy správné struktury DDD projektu:

- **Izolace domén** – Každá doména (Bounded Context) má svůj vlastní model, který odráží její specifické potřeby a jazyk.
- **Ubiquitous Language** – Jazyk kontextu se promítá do kódu; tým ho používá konzistentně od názvů tříd po dokumentaci.
- **Jasné hranice** – Definované hranice mezi doménami pomáhají vývojářům pochopit, kde končí jedna doména a začíná druhá.
- **Minimalizace závislostí** – Kontexty drží své modely odděleně. Změna v jednom by neměla nutit úpravu druhého.
:::

:::callout{type="warn"}
### Časté chyby při implementaci DDD

Časté chyby při implementaci DDD v Symfony:

- **Umístění všech doménových modelů do sdílené složky** – Každá doména patří do svého kontextu, ne do `Shared/`.
- **Sdílení doménových modelů mezi doménami** – Cesta k datům z cizího kontextu vede přes Anti-Corruption Layer nebo Domain Events.
- **Příliš mnoho závislostí mezi doménami** – Cross-context import doménových tříd je signál chybějící Anti-Corruption Layer.
- **Ignorování Ubiquitous Language** – Kód, dokumentace i komunikace v týmu používají stejné výrazy.
:::

## 10.03 Implementace entit {#entities}

Vstupní bod do agregátu je **kořen agregátu** – třída dědí z bázové `AggregateRoot`,
konstruktor je `private` a vznik probíhá přes pojmenovanou factory metodu
(`User::register()`, `Order::place()`). To zaručuje, že nelze vytvořit
agregát v nekonzistentním stavu. Definice entity je v kapitole
[Základní koncepty](/zakladni-koncepty); tato sekce řeší její podobu v Symfony.

:::callout{type="pattern"}
### Příklad: kořen agregátu User {#entity-example-heading}

:::code{language="php" filename="src/SharedKernel/Domain/AggregateRoot.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

abstract class AggregateRoot
{
    /** @var list<object> */
    private array $domainEvents = [];

    final protected function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    final public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
:::

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Model;

use App\SharedKernel\Domain\AggregateRoot;
use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserName;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'user_id')]
    public readonly UserId $id;

    #[ORM\Embedded(class: UserName::class)]
    private UserName $name;

    #[ORM\Column(type: 'email_vo', unique: true)]
    private Email $email;

    #[ORM\Embedded(class: HashedPassword::class)]
    private readonly HashedPassword $hashedPassword;

    #[ORM\Column(type: 'datetime_immutable')]
    public readonly \DateTimeImmutable $createdAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    private function __construct(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->hashedPassword = $hashedPassword;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function register(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
    ): self {
        $user = new self($id, $name, $email, $hashedPassword);
        $user->record(new UserRegistered($id, $email, $user->createdAt));

        return $user;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function rename(UserName $newName): void
    {
        if ($this->name->equals($newName)) {
            return;
        }

        $this->name = $newName;
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->email->equals($newEmail)) {
            return;
        }

        $this->email = $newEmail;
    }
}
:::
:::

Detaily implementace:

- **`extends AggregateRoot`, bez `final`.** Bázová třída poskytuje `record()`
  a `releaseEvents()` – sdílené chování pro všechny agregáty, ne duplicitní
  kopii v každé entitě. `final` patří hodnotovým objektům; entity mapované
  Doctrine zůstávají ne-final, protože lazy ghost proxy z entity dědí
  (rozbor v kapitole [Návrh agregátu](/navrh-agregatu)).
- **Privátní konstruktor + factory `register()`.** Jediná legální cesta vytvoření.
  Kdyby přibyla další kategorie (importovaný uživatel z LDAP), přidá se další
  factory, ne přepínač uvnitř konstruktoru. Událost `UserRegistered` se nahrává
  ve factory, ne v konstruktoru – konstruktorem prochází i rekonstituce
  uloženého agregátu a ta žádnou událost vyvolat nesmí.
- **VO uloženy přímo, ne jako primitivy.** `UserId`, `Email`, `UserName`
  a `HashedPassword` jsou typy vlastností. Doctrine je hydratuje přes custom typy
  (`user_id`, `email_vo`) nebo `#[ORM\Embedded]`. Žádné re-validace v getterech.
- **`#[ORM\Version]` pro optimistický zámek.** Souběžná modifikace agregátu
  skončí výjimkou `OptimisticLockException`, kterou aplikační vrstva přeloží na retry.
- **Názvy metod z Ubiquitous Language.** `rename()` místo `setName()`,
  `changeEmail()` místo `updateEmail()`. Doménový jazyk, ne CRUD slovník.

:::callout{type="note"}
### Proč VO ukládáme přímo, ne jako primitivy {#doctrine-hydration-heading}

V dřívějších verzích tohoto průvodce se v entitě VO ukládaly jako string a getter
vracel `new UserId($this->id)`. Důvod byl Doctrine hydration: Doctrine při čtení
z DB nastavuje vlastnosti přímo, bez konstruktoru, takže `UserId` jako typ vlastnosti
by skončilo na TypeError.

Doctrine ORM 3 to ale řeší přes **custom DBAL types** (`UserIdType`, `EmailType`)
a `#[ORM\Embedded]`. Při načítání Doctrine sám zavolá custom type, který
vyrobí instanci VO, a vlastnost dostane správný objektový typ. Kód agregátu pak
pracuje výhradně s typovými hodnotami, bez re-konstrukce při každém volání getteru.

Detaily a registrace v sekci [Doctrine custom types](#doctrine-custom-types).
:::

## 10.04 Implementace hodnotových objektů {#value-objects}

V Symfony 8 se hodnotový objekt zapisuje jako `final readonly` PHP třída.
Validace patří do konstruktoru, rovnost se počítá z hodnot, ne z identity.
Detailní rozbor sémantiky VO je v kapitole [Základní koncepty](/zakladni-koncepty):

:::callout{type="pattern"}
### Příklad: hodnotový objekt Email {#value-object-example-heading}

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
            throw new \InvalidArgumentException(
                sprintf('Neplatný formát e-mailu: "%s".', $value),
            );
        }
    }

    public static function fromUserInput(string $raw): self
    {
        // Vstupy z formulářů normalizujeme zde (lowercase, trim).
        // Konstruktor se nedotýká – chrání invariant „dvě instance se stejnou
        // hodnotou jsou rovnocenné".
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
:::

:::callout{type="warn"}
### Limity `FILTER_VALIDATE_EMAIL` {#email-validate-limits-heading}

PHP `FILTER_VALIDATE_EMAIL` ověřuje syntaxi podle zjednodušeného RFC 5322.
Drobnosti, které je dobré znát:

- **Odmítá i některé technicky platné adresy** – `a@b` (doména bez tečky,
  např. `user@localhost`) neprojde, přestože RFC ji připouští.
- **Nepouští IDN domény** (`uživatel@české-domény.cz`)
  bez explicitního převodu přes `idn_to_ascii()`.
- **Neověřuje existenci schránky.** Validní syntaxe ≠ doručitelná adresa.

V doménové vrstvě tedy validujeme **syntakticky**. Pravdivost potvrdí až
e-mail s ověřovacím odkazem (out-of-band proces), který v doméně modeluje
agregát `EmailVerification` nebo událost `EmailVerificationRequested`.
Pro pokročilejší syntaktickou validaci existuje knihovna
[`egulias/email-validator`](https://github.com/egulias/EmailValidator),
kterou používá i Symfony Validator pod kapotou.
:::

:::callout{type="pattern"}
### Příklad: hodnotový objekt UserName s vlastními invarianty {#user-name-vo-heading}

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/UserName.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class UserName
{
    public const MIN_LENGTH = 2;
    public const MAX_LENGTH = 100;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH)]
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        $length = mb_strlen($trimmed);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'Jméno musí mít %d–%d znaků (zadáno %d).',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
                $length,
            ));
        }

        $this->value = $trimmed;
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

`UserName` ukazuje plnou cenu hodnotového objektu: invariant „jméno není prázdné
a má rozumnou délku“ je vynucen typem. Volající kód nemá šanci vložit prázdný
string – pokud by to zkusil, dostane výjimku v konstruktoru, ne až v repozitáři.
`#[ORM\Embeddable]` říká Doctrine, že VO se ukládá jako sloupec ve stejné tabulce
jako vlastník (žádná samostatná tabulka pro VO).

Třetí typ hodnotového objektu je identita agregátu. Generuje se v aplikaci,
ne v databázi – handler tak zná ID ještě před uložením:

:::callout{type="pattern"}
### Příklad: UserId s generováním přes symfony/uid {#user-id-vo-heading}

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/UserId.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class UserId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('Neplatné UserId: "%s".', $value),
            );
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
:::
:::

`Uuid::v7()` z balíčku `symfony/uid` vrací časově řaditelné UUID, vhodné
jako primární klíč (sekvenční zápisy nedrobí B-tree index). Stejný vzor platí
pro `OrderId` nebo `PaymentId`; hodnotu vždy zpřístupňuje public readonly
property `$value`, žádná metoda `value()`.

## 10.05 Implementace repozitářů {#repositories}

Repozitář se v Symfony 8 dělí na dvojici: rozhraní v doméně + Doctrine implementace v infrastruktuře. Doménový kód se opírá pouze o rozhraní, výměna persistence se odehraje v jediném souboru:

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
use Psr\EventDispatcher\EventDispatcherInterface;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();

        // Doménové eventy publikujeme až po flushi – listenery už vidí
        // uložený stav. Limity tohoto vzoru popisuje callout níže.
        foreach ($user->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id->value);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email->value]);
    }
}
:::
:::

`DoctrineUserRepository` implementuje doménové rozhraní `UserRepository` přes Doctrine ORM.
`save()` uloží agregát a hned poté vypustí nashromážděné doménové události –
`releaseEvents()` frontu zároveň vyprázdní, takže opakované volání nic
nedoručí dvakrát. Místo `EventDispatcher` může události přebírat Messenger;
mechanika zůstává stejná.

:::callout{type="warn"}
### Limit vzoru „dispatch po flushi“ {#event-dispatch-heading}

Mezi `flush()` a `dispatch()` může proces spadnout: OOM kill, deploy restart,
výpadek brokera. Agregát pak v databázi je, ale událost se nikdy nedoručí –
z pohledu ostatních kontextů se registrace neudála. Pro vývoj a méně důležité
události je tento vzor přijatelný. Jakmile na události závisí jiný Bounded
Context nebo platební tok, produkčním řešením je Outbox Pattern: událost se
zapíše do stejné DB transakce jako agregát a samostatný worker ji doručí
s retry. Detail v kapitole [Outbox Pattern](/outbox-pattern).
:::

:::callout{type="warn"}
### Dvojí transakce: repozitář vs. middleware {#double-transaction-heading}

Nabízí se obalit tělo `save()` ještě vlastní transakcí přes
`wrapInTransaction()`. Pokud ale command bus
používá `doctrine_transaction` middleware (viz [aplikační služby](#application-services)),
vzniknou dvě vrstvy transakcí: middleware otevře vnější, repozitář vnitřní
přes savepoint. Commit point přestane být zřejmý a rollback vnitřní vrstvy
nezruší vnější zápisy. Transakci má vlastnit jedna vrstva – doporučená volba
je middleware na command busu, který obalí celý handler. Repozitář pak jen
volá `persist()` a `flush()`, vlastní transakce neotevírá.

S middlewarem se ale mění význam „dispatch po flushi“ výše: `flush()` zapíše
SQL, commit provede až middleware po doběhnutí handleru. Synchronní dispatch
za flushem tedy běží uvnitř otevřené transakce – a pokud se po něm transakce
odvolá, listenery už reagovaly na událost, která se nikdy nestala. Spolehlivé
řešení je opět [Outbox Pattern](/outbox-pattern): událost se commituje spolu
s agregátem.
:::

## 10.06 Persisted Object Pattern – čistá DDD varianta {#persisted-object-pattern}

Pokud trváte na tom, že doménová vrstva nesmí obsahovat ani metadata
o persistenci, korektní cesta není XML mapping (také „znečištěné“, jen jiným
formátem), ale **Persisted Object Pattern**. Jde o variantu vzoru *Data Mapper* (Fowler, *PoEAA*, 2002);
v DDD kontextu ji rozebírá Vladimir Khorikov v sérii blogpostů „Persistence model“ a Vaughn Vernon v *IDDD*, kap. 12.

Idea: doménová třída zůstane POPO bez atributů. Vedle ní v infrastrukturní
vrstvě existuje samostatná persistence třída se všemi Doctrine atributy.
Dva mappery (one-way každým směrem) překládají mezi nimi.

:::callout{type="pattern"}
### Příklad: doména POPO + persistence model + mapper {#persisted-object-example-heading}

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php (POPO – bez atributů)"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Model;

use App\SharedKernel\Domain\AggregateRoot;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserName;

final class User extends AggregateRoot
{
    private function __construct(
        public readonly UserId $id,
        private UserName $name,
        private Email $email,
        private readonly HashedPassword $hashedPassword,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function register(/* ... */): self { /* ... */ }
    public static function reconstitute(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
        \DateTimeImmutable $createdAt,
    ): self {
        // Speciální factory pro mapper – obnovuje agregát z perzistovaného stavu
        // bez vyhazování doménových událostí.
        return new self($id, $name, $email, $hashedPassword, $createdAt);
    }

    // doménové operace ...
}
:::

:::code{language="php" filename="src/UserManagement/Infrastructure/Persistence/Doctrine/UserPersistenceModel.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserPersistenceModel
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    public string $id;

    #[ORM\Column(type: 'string', length: 100)]
    public string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $email;

    #[ORM\Column(type: 'string', length: 255)]
    public string $passwordHash;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    public int $version = 1;
}
:::

:::code{language="php" filename="src/UserManagement/Infrastructure/Persistence/Doctrine/UserMapper.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Persistence\Doctrine;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserName;

final class UserMapper
{
    public function toDomain(UserPersistenceModel $row): User
    {
        return User::reconstitute(
            new UserId($row->id),
            new UserName($row->name),
            new Email($row->email),
            HashedPassword::fromHash($row->passwordHash),
            $row->createdAt,
        );
    }

    public function toPersistence(User $user): UserPersistenceModel
    {
        $model = new UserPersistenceModel();
        $model->id = $user->id->value;
        $model->name = (string) $user->name();
        $model->email = $user->email()->value;
        $model->passwordHash = $user->hashedPassword()->value;
        $model->createdAt = $user->createdAt;

        return $model;
    }
}
:::
:::

:::callout{type="note"}
### Cena pure varianty {#persisted-object-tradeoffs-heading}

Persisted Object Pattern drží doménu úplně mimo ORM. Žádný atribut, žádný `use
Doctrine\…`, žádná stopa po infrastruktuře. Cena:

- **2× kód.** Doménová třída + persistence model + mapper. Pro každý agregát.
- **Mapování VO ručně.** Custom typy z hlavní cesty zde nepoužijete – musí to dělat
  mapper. U 5+ VO se kód mapperu rozrůstá.
- **Riziko driftu.** Když přibude pole v doméně, musí přibýt v persistence modelu
  i v mapperech. Žádný compiler to nehlídá.
- **Optimistický zámek je řešení navíc.** `#[ORM\Version]` je v persistence modelu;
  doména `User` musí přijmout `version` jako parametr `reconstitute()`, nebo
  se spolehnout na infrastrukturu, že verzi sleduje sama.

Doporučení: použít Persisted Object **jen v kontextech, kde je oddělení
opravdu důležité** (Core Domain s vysokou hodnotou, dlouhodobá údržba, plán
na výměnu persistence). Pro většinu Bounded Contextů jsou atributy přijatelný kompromis.
:::

V dalších příkladech v tomto průvodci pokračujeme s atributy přímo na agregátech.
Persisted Object Pattern dále nerozvíjíme – principy jsou identické, jen vyžadují
explicitní mapper na každý agregát.

## 10.07 Doctrine custom types pro Value Objects {#doctrine-custom-types}

Sekce [Implementace entit](#entities) deklaruje vlastnosti přímo typu `Email`
nebo `UserId`. Tuto hydrataci zajišťuje **Doctrine custom type** – konvertor
mezi databázovým primitivem a hodnotovým objektem. Zde je jeho implementace
a registrace.

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

        return $value instanceof Email ? $value->value : (string) $value;
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
            user_id:
                class: App\UserManagement\Infrastructure\Doctrine\Type\UserIdType
:::

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php (použití typu)"}
<?php

// Atribut odkazuje na registrovaný typ jménem:
#[ORM\Column(type: 'email_vo', unique: true)]
private Email $email;
:::
:::

XML mapping (`User.orm.xml`) dokáže totéž bez atributů ve třídě, doménu od ORM
ale neoddělí – jen přesune metadata do jiného formátu. Kdo chce striktní oddělení,
najde řešení v sekci [Persisted Object Pattern](#persisted-object-pattern).

:::callout{type="note"}
### Kdy se bez custom type obejdete

Custom type je v tomto průvodci výchozí cesta – entita pracuje přímo s VO,
bez re-konstrukce v getterech. Primitivní ukládání (string vlastnost, getter
vrací `new Email($this->email)`) ušetří jednu třídu na typ. Hodí se pro prototyp
nebo první kontakt s DDD; s rostoucím počtem VO se vyplatí přejít na custom typy.
:::

## 10.08 PHP 8.1+ Enums pro stavové typy {#php-enums}

Stav objednávky, role uživatele, priorita úkolu – konečné množiny hodnot, které se dřív modelovaly konstantami ve třídě,
mají od PHP 8.1 nativní typ: enum. Překlep ani neznámý stav neprojde už při kompilaci.

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
        $this->id = $id->value;
        $this->status = OrderStatus::DRAFT;
        $this->createdAt = new \DateTimeImmutable();

        $this->record(new OrderCreated($id));
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

        $this->record(new OrderStatusChanged(
            new OrderId($this->id),
            $oldStatus,
            $newStatus
        ));
    }

    private function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return object[]
     */
    public function releaseEvents(): array
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

Obecné pravidlo: pokud typ má konečný, předem známý počet hodnot a nepotřebuje složitou vnitřní logiku, je enum správná volba. Pokud typ obsahuje libovolné hodnoty, validaci nebo výpočty, je namístě hodnotový objekt.
:::

:::callout{type="warn"}
### Doctrine a PHP enums

Doctrine ORM 2.11+ a 3.x nativně podporují PHP enums. Stačí atribut
`#[ORM\Column(enumType: OrderStatus::class)]` na vlastnosti – Doctrine
konvertuje hodnotu mezi enumem a databázovým sloupcem automaticky.
Pro backed enums se ukládá backing value (`string` nebo `int`).
:::

:::callout{type="note"}
### Kdy sáhnout po symfony/workflow {#workflow-note-heading}

Ruční automat v enumu není jediná možnost – Symfony nabízí komponentu Workflow.
Ta přidává vizualizaci stavového grafu (`workflow:dump` → Graphviz), guard eventy
napojené na služby (Voter, feature flag) a audit trail přechodů. Vyplatí se
u procesů s mnoha stavy, které potřebuje vidět i ne-vývojář. V doménovém modelu
bývá ruční automat čistší: komponenta tahá závislost na frameworku do domény
a přesouvá přechodová pravidla z agregátu do YAML konfigurace. Enum s `match`
drží pravidla tam, kde je vynucuje typový systém.
:::

## 10.09 Doménové služby (a kdy je *nepoužít*) {#domain-services}

Doménová služba zapouzdřuje pravidlo, které **přirozeně nepatří žádnému agregátu
ani hodnotovému objektu** – typicky operaci nad dvěma a více agregáty
(`MoneyTransferService` mezi dvěma účty) nebo bezstavový výpočet vyžadující
externí zdroj (kurzovní převod, kalkulace daně podle jurisdikce).

**Před sáhnutím po doménové službě stojí vždy jedna otázka: nepatří to do agregátu?**
Pravidlo „lze platit jen confirmed objednávku“ je čistý invariant agregátu `Order` –
jen `Order` zná svůj stav a jen on smí ten stav měnit. Domain service na to
je anti-vzor, který oslabuje agregát a vede k anemickému modelu.

:::callout{type="anti"}
### Anti-vzor: doménová služba pro invariant jednoho agregátu {#anti-payment-service-heading}

:::code{language="php" filename="src/OrderManagement/Domain/Service/PaymentService.php (ANTI-VZOR)"}
<?php

// ANTI-VZOR: pravidlo „lze platit jen confirmed objednávku" je invariant
// agregátu Order, ne odpovědnost externí služby.
final class PaymentService
{
    public function processPayment(Order $order, Money $amount, PaymentMethod $pm): Payment
    {
        if ($order->status() !== OrderStatus::CONFIRMED) {
            throw new \DomainException('Cannot process payment for a non-confirmed order');
        }

        return new Payment(PaymentId::generate(), $order->id(), $amount, $pm);
    }
}
:::

Co se tu pokazilo:

- **Invariant uniká agregátu.** `Order` neví, že někdo kontroluje jeho stav
  zvenčí. Když přibude nový stav (`REFUNDED`), musíte sáhnout do servicu,
  ne do agregátu.
- **Anemický model.** `Order` má getter `status()` jako veřejné API,
  což je signál, že vnitřní stav je manipulovatelný zvenčí.
- **Otevřená cesta k inkonzistenci.** Nikdo nezabrání druhé službě, aby
  obešla pravidlo a vytvořila `Payment` přímo.
:::

:::callout{type="pattern"}
### Správně: invariant uvnitř agregátu, factory metoda na výsledek {#payment-aggregate-heading}

:::code{language="php" filename="src/OrderManagement/Domain/Model/Order.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Domain\Model;

use App\OrderManagement\Domain\Event\PaymentRecorded;
use App\OrderManagement\Domain\Exception\InvalidOrderStateTransitionException;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\OrderStatus;
use App\OrderManagement\Domain\ValueObject\PaymentId;
use App\OrderManagement\Domain\ValueObject\PaymentMethod;

final class Order extends AggregateRoot
{
    // ... id, status, items, factory method `place()` viz dříve ...

    public function recordPayment(Money $amount, PaymentMethod $method): Payment
    {
        if ($this->status !== OrderStatus::CONFIRMED) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                'PAID',
            );
        }

        if (!$amount->equals($this->totalAmount())) {
            throw new \DomainException('Payment amount does not match order total.');
        }

        $this->status = OrderStatus::PAID;

        $payment = Payment::record(PaymentId::generate(), $this->id, $amount, $method);

        $this->record(new PaymentRecorded($this->id, $payment->id(), $amount));

        return $payment;
    }
}
:::
:::

`Order::recordPayment()` zapouzdřuje **pravidlo i přechod stavu** uvnitř agregátu.
Jediný způsob, jak vytvořit `Payment` pro danou objednávku, vede přes tuto metodu –
což znamená, že invariant „platit lze jen confirmed objednávku“ je vynucen
typovým systémem, ne nadějí, že někdo zavolá správnou službu. Aplikační handler
pak má triviální koordinační roli. Pojmy command a handler vysvětluje
[sekce o aplikačních službách](#application-services), podrobně kapitola [CQRS](/cqrs):

:::callout{type="pattern"}
### Aplikační handler nad agregátem {#payment-handler-heading}

:::code{language="php" filename="src/OrderManagement/Application/Command/RecordPaymentHandler.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Application\Command;

use App\OrderManagement\Domain\Repository\OrderRepository;
use App\OrderManagement\Domain\Repository\PaymentRepository;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\PaymentMethod;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordPaymentHandler
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly PaymentRepository $payments,
    ) {}

    public function __invoke(RecordPayment $cmd): void
    {
        $order = $this->orders->get(OrderId::fromString($cmd->orderId));

        $payment = $order->recordPayment(
            Money::fromAmount($cmd->amount, $cmd->currency),
            PaymentMethod::from($cmd->method),
        );

        $this->orders->save($order);
        $this->payments->save($payment);
    }
}
:::
:::

:::callout{type="note"}
### Kdy doménová služba *opravdu* dává smysl {#kdy-domain-service-heading}

Doménová služba je správná volba ve třech přesně vymezených případech:

- **Operace nad 2+ agregáty.** Klasický `MoneyTransferService::transfer($from, $to, $amount)`
  – pravidlo „součet zůstatků je konstantní“ se týká dvou účtů a nepatří jednomu
  ani druhému. (Pozor: stejně se ukládá v jedné transakci na jeden agregát –
  viz [agregát = transakční hranice](/navrh-agregatu#transactional-consistency).)
- **Bezstavový výpočet s externí znalostí.** Daňová sazba podle jurisdikce a typu
  zboží, převod měn podle aktuálního kurzu. Logika je čistě doménová, ale
  vstupy přicházejí zvenčí.
- **Generická doménová operace bez přirozeného vlastníka.** „Vyčisti expirované
  rezervace starší než X dnů“ – akce nad množinou agregátů, kde žádný z nich
  není přirozený vlastník pravidla.

Ve všech ostatních případech: pravidlo patří do agregátu, hodnotového objektu nebo
specifikace ([Specification Pattern](#specification-pattern)).
:::

## 10.10 Specification Pattern {#specification-pattern}

Specification Pattern (Eric Evans, *DDD*, kap. 9) zapouzdřuje doménové pravidlo
do samostatného objektu s jedinou metodou `isSatisfiedBy()`. Pravidlo „objednávka
je způsobilá k expedici“ pak existuje na jednom místě – stejná specifikace slouží
validaci v agregátu, filtrování kolekcí i výběru v repozitáři. Malá pravidla se
skládají kombinátory `and()`, `or()` a `not()` do složitějších, bez kopírování
podmínek po kódu.

Plný výklad včetně implementace v PHP, kombinátorů a double-dispatch napojení
na Doctrine najdete v kapitole
[Specification Pattern](/mene-zname-vzory#specification).

## 10.11 Implementace doménových událostí {#domain-events}

Doménová událost je fakt minulého času: registrace proběhla, platba byla zaznamenána. Kód ji v Symfony 8 modeluje jako neměnnou PHP třídu, kterou agregát publikuje při změně stavu:

:::callout{type="pattern"}
### Příklad: Implementace doménové události v Symfony 8 {#domain-event-example-heading}

:::code{language="php" filename="src/UserManagement/Domain/Event/UserRegistered.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Event;

use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;

final readonly class UserRegistered
{
    public string $userId;
    public string $email;

    public function __construct(
        UserId $userId,
        Email $email,
        public \DateTimeImmutable $occurredAt,
    ) {
        // Událost nese primitivy – serializuje se bez závislosti na VO třídách.
        $this->userId = $userId->value;
        $this->email = $email->value;
    }
}
:::
:::

`UserRegistered` nese minimum potřebné pro obnovu kontextu: ID uživatele, e-mail a čas registrace.
Listenery i externí konzumenti z těchto tří hodnot poskládají reakci, aniž by sahali zpět do `UserRepository`.
Konstruktor odpovídá volání `record(new UserRegistered(...))` ve factory
`User::register()` v sekci [Implementace entit](#entities).

:::callout{type="note"}
### Symfony EventDispatcher vs. Messenger pro doménové události {#dispatcher-vs-messenger-heading}

Symfony nabízí dva mechanismy pro „něco se stalo“:

- **EventDispatcher** (`EventDispatcherInterface`) – synchronní,
  in-process. Listenery se provedou okamžitě v témž PHP požadavku, ve sdíleném
  paměťovém prostoru. Bez serializace, bez síťové cesty tam a zpět.
- **Messenger** (`MessageBusInterface`) – může být synchronní i asynchronní.
  Podporuje transporty (RabbitMQ, Redis, Doctrine outbox), retry strategii
  a serializaci zprávy. Příjemce může běžet v jiném procesu, jiném serveru.

**Volba podle role příjemce:**

- **In-context, in-request listenery** (read model uvnitř téhož kontextu,
  audit log, cache invalidace uvnitř téhož commitu) → **EventDispatcher**.
  Žádná serializace, listenery vidí stejný `EntityManager`, stejnou transakci.
- **Cross-context komunikace** (publikace události mimo Bounded Context, kterou
  zpracuje jiný kontext / služba / projekce) → **Messenger**. Zpráva dorazí
  do brokera, jiný kontext si ji odebere. Spolehlivé doručení napříč kontexty
  zajišťuje v produkci [Outbox Pattern](/outbox-pattern).

**Anti-vzor:** používat Messenger jako náhradu za EventDispatcher uvnitř téhož
kontextu, protože „je to flexibilnější“. Cena: každá zpráva projde JSON serializací,
ztráta typů, ztráta transakční koheze, nutnost správy transportů. Mechanismus se volí
podle hranice, kterou událost překračuje – ne podle hypotetické budoucí potřeby.
:::

## 10.12 Strategie zpracování chyb v DDD {#error-handling}

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
- **Statické factory metody** (`cannotTransition()`) drží vytváření výjimek čitelné a konzistentní.
- **Nepropagujte infrastrukturní výjimky** do doménové vrstvy – repozitáře by je měly zachytit a přeložit na doménové výjimky.
- Kontrolery by měly zachytávat doménové výjimky a **překládat je na HTTP odpovědi** (400, 404, 409).
:::

## 10.13 Implementace aplikačních služeb {#application-services}

Tato sekce poprvé skládá dohromady trojici command – handler – bus, proto
krátké vysvětlení pojmů. **Command** je neměnný objekt popisující záměr:
„zaregistruj uživatele s tímto jménem a e-mailem“. Nemá chování, nese jen data
use case. **Handler** je třída, která command vykoná – načte agregáty, zavolá
doménovou metodu, uloží výsledek.

**Command bus** oba spojuje. Volající předá command busu (`MessageBusInterface`
ze Symfony Messenger) a ten najde příslušný handler podle typu zprávy. Mezi
dispatch a handler se navíc vkládají middleware: `validation` spustí Symfony
Validator nad commandem, `doctrine_transaction` obalí handler databázovou
transakcí (podrobně v kapitole [CQRS](/cqrs)).

Průvodce používá bus už zde, protože je to idiomatická Symfony cesta: kontroler
nezná handler, jen popis záměru. Stejný command lze později zpracovat asynchronně
bez zásahu do volajícího kódu. Plný výklad včetně oddělených busů pro commandy
a queries přináší kapitola [CQRS](/cqrs).

Aplikační služba má tedy v Symfony 8 podobu command nebo query handleru. Načte agregáty přes repozitář,
zavolá doménovou metodu a zapíše výsledek – žádná doménová pravidla v ní nežijí:

:::callout{type="pattern"}
### Příklad: Implementace command handleru v Symfony 8 {#command-handler-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUser.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUser
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 12)]
        public string $password,
    ) {}
}
:::

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUserHandler.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use App\UserManagement\Domain\Exception\DuplicateEmailException;
use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserName;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = Email::fromUserInput($command->email);

        $user = User::register(
            UserId::generate(),
            new UserName($command->name),
            $email,
            HashedPassword::fromPlainText($command->password),
        );

        try {
            $this->userRepository->save($user);
        } catch (UniqueConstraintViolationException $e) {
            // Spoléháme na DB unique constraint na sloupci `email`. Aplikační check
            // přes findByEmail() je vůči souběžným registracím nedostatečný (TOCTOU
            // race – dvě paralelní volání oba projdou check a oba uloží).
            throw DuplicateEmailException::with($email, $e);
        }
    }
}
:::
:::

:::callout{type="warn"}
### Race condition v naivní variantě s `findByEmail()` {#register-race-heading}

V dřívějších verzích tohoto průvodce handler zjišťoval unikátnost přes
`findByEmail()` před `save()`. To je **TOCTOU race**: dvě paralelní
registrace se stejným e-mailem oba projdou checkem (databáze ještě neviděla zápis
toho druhého) a oba úspěšně uloží. Výsledek: dva uživatelé se stejným e-mailem.

Bezpečné řešení má dvě vrstvy:

- **DB unique constraint** na sloupci `email`. Druhý INSERT
  vyhodí `UniqueConstraintViolationException`. Toto je jediná
  garance napříč souběžnými requesty.
- **Překlad na doménovou výjimku** v command handleru (nebo lépe v repozitáři),
  aby aplikační vrstva nemusela znát infrastrukturní typy.

Aplikační check přes `findByEmail()` můžete ponechat *navíc* pro hezčí
chybovou hlášku v běžném (ne-souběžném) případu – ale **nikdy jako jedinou ochranu**.
:::

:::callout{type="pattern"}
### Příklad: doménová výjimka s factory metodou {#duplicate-email-exception-heading}

:::code{language="php" filename="src/UserManagement/Domain/Exception/DuplicateEmailException.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Exception;

use App\UserManagement\Domain\ValueObject\Email;

final class DuplicateEmailException extends \DomainException
{
    public static function with(Email $email, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Uživatel s e-mailem "%s" již existuje.', $email->value),
            previous: $previous,
        );
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
final readonly class GetUserProfileHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function __invoke(GetUserProfile $query): ?UserProfileViewModel
    {
        $user = $this->userRepository->findById(new UserId($query->userId));

        if ($user === null) {
            return null;
        }

        return new UserProfileViewModel(
            id: $user->id->value,
            name: (string) $user->name(),
            email: $user->email()->value,
            createdAt: $user->createdAt,
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
  Atributy `#[Assert\Email]`, `#[Assert\NotBlank]` patří přímo
  na command třídy. Tato validace chrání doménovou vrstvu před neplatnými vstupy.
- **Doménová validace (doménová vrstva)** – doménová pravidla, která vynucují
  entity, agregáty a value objects: „uživatel s tímto e-mailem již existuje“,
  „objednávku nelze potvrdit bez položek“. Tato validace je součástí doménového modelu
  a Symfony Validator na ní nesmí záviset.

**Pravidlo:** Symfony Validator řeší *syntaktickou* validaci (formát),
doménová vrstva řeší *sémantickou* validaci (doménová pravidla).
:::

## 10.14 Implementace kontrolerů {#controllers}

Kontroler je adapter mezi HTTP a aplikační vrstvou. Smí: validovat formát vstupu,
transformovat ho na command/query, dispatchovat, přeložit doménovou výjimku
na HTTP odpověď. Nesmí: nést doménová pravidla, volat repozitáře přímo,
manipulovat s agregáty.

Symfony nabízí od verze 6.3 `#[MapRequestPayload]`, který deserializuje a validuje
JSON požadavek přímo do typového commandu. Pro klasické HTML formuláře pak existuje
varianta `#[MapRequestPayload(acceptFormat: 'form')]` nebo Symfony Form.

:::callout{type="pattern"}
### Příklad: kontroler s MapRequestPayload (JSON API) {#controller-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Controller/RegistrationController.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Controller;

use App\UserManagement\Domain\Exception\DuplicateEmailException;
use App\UserManagement\Registration\Command\RegisterUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterUser $command,
    ): Response {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            foreach ($e->getWrappedExceptions() as $wrapped) {
                if ($wrapped instanceof DuplicateEmailException) {
                    return new JsonResponse(
                        ['error' => $wrapped->getMessage()],
                        Response::HTTP_CONFLICT,
                    );
                }
            }

            throw $e;
        }

        return new JsonResponse(['status' => 'created'], Response::HTTP_CREATED);
    }
}
:::
:::

`MapRequestPayload` převezme deserializaci, validaci přes Symfony Validator
(atributy `#[Assert\…]` na commandu) i překlad chyby validace na HTTP 422.
Kontroler tak má jen tři odpovědnosti: dispatch, mapování doménových výjimek
na HTTP, návrat odpovědi.

:::callout{type="warn"}
### Messenger balí výjimky {#handler-failed-exception-heading}

Častá past: `catch (DuplicateEmailException)` kolem `dispatch()` nikdy nechytí
nic. Synchronní Messenger každou výjimku z handleru zabalí do
`HandlerFailedException` – původní typ se na catch blok nepropaguje. Zabalené
výjimky zpřístupňuje `getWrappedExceptions()`; je jich pole, protože jedna
zpráva může mít víc handlerů. Kontroler proto chytá obálku, projde zabalené
výjimky a na známé doménové typy reaguje HTTP odpovědí. Vše ostatní pošle dál –
ticho po neznámé chybě by maskovalo skutečné selhání. Kdo nechce iteraci
opakovat v každém kontroleru, napíše dekorátor command busu, který první
zabalenou výjimku rozbalí a vyhodí znovu. Ani `HandleTrait` rozbalení
neprovádí – obálku vrací stejně jako přímý dispatch.
:::

:::callout{type="note"}
### Symfony Form patří nad command, ne nad entitu {#form-nad-commandem-heading}

Adresáře `Form/` ve [struktuře projektu](#project-structure) drží FormType
pro HTML formuláře. Zásadní rozhodnutí je `data_class`: formulář se váže
na command (DTO), nikdy na doménovou entitu. Form komponenta totiž nastavuje
vlastnosti napřímo a obchází factory metody i invarianty agregátu – rozepsaný
formulář by držel `User` v nekonzistentním stavu. Tok je stejný jako u JSON
API: Form naplní `RegisterUser`, kontroler ho dispatchne, handler teprve
vytvoří agregát. U readonly commandu s konstruktorem poslouží `empty_data`
callback, který instanci složí z odeslaných polí. Validace zůstává na
`#[Assert\…]` atributech commandu, formulář ji přebírá automaticky.
:::

:::callout{type="note"}
### Symfony idiomy: `#[AsAlias]` pro repozitáře {#symfony-idiomy-asalias}

Místo aliasování v `services.yaml` můžete od Symfony 6.3+ použít atribut
`#[AsAlias]` přímo na implementaci:

:::code{language="php" filename="src/UserManagement/Infrastructure/Repository/DoctrineUserRepository.php (s AsAlias)"}
<?php

use App\UserManagement\Domain\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: UserRepository::class)]
final class DoctrineUserRepository implements UserRepository
{
    // ...
}
:::

DI Container automaticky zaregistruje `DoctrineUserRepository` jako alias na
rozhraní `UserRepository`. `services.yaml` zůstane čistý, závislosti zůstanou
v jednom souboru s implementací. Pro většinu projektů je to preferovaná cesta.
:::

Kontroler je tenký, takže těžiště testů leží pod ním. Agregáty se testují jako
čistý PHP bez kernelu. Aplikační handlery, které se opírají o repozitář,
pokrývá kernel test s testovací databází – jen reálná DB ověří unique
constraint a transakční chování, in-memory mock je negarantuje. Konkrétní
testy po vrstvách rozebírá kapitola [Testování DDD](/testovani-ddd).

Mimo kontroler zůstává i autorizace; má vlastní kapitolu
[Autorizace v DDD](/autorizace-v-ddd). Stručně:
otázku „smí tento uživatel vykonat tento use case na tomto objektu“ řeší
use-case vrstva přes Symfony Voter, zatímco doménové invarianty zůstávají
v agregátu. Kapitola zavádí čtyřvrstvý rámec od HTTP firewallu po pravidla
na úrovni polí a ukazuje, proč doménová pravidla do Voteru nepatří.

## 10.15 Dependency Injection a autowiring {#dependency-injection}

DI Container v Symfony 8 váže rozhraní z doménové vrstvy na konkrétní implementaci v infrastruktuře.
Konfigurace určuje, kterou třídu autowiring injektuje, když handler typuje na `UserRepository`:

:::callout{type="pattern"}
### Příklad: Konfigurace služeb v Symfony 8 {#dependency-injection-example-heading}

:::code{language="yaml" filename="config/services.yaml"}
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

    # Alias rozhraní → implementace. Klíč je FQN rozhraní, hodnota je @-reference
    # na existující službu (Symfony si DoctrineUserRepository zaregistruje sama
    # přes autowiring výše). Tím vznikne JEDNA instance, na kterou se odkazují obě jména.
    App\UserManagement\Domain\Repository\UserRepository: '@App\UserManagement\Infrastructure\Repository\DoctrineUserRepository'
    App\OrderManagement\Domain\Repository\OrderRepository: '@App\OrderManagement\Infrastructure\Repository\DoctrineOrderRepository'

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

:::callout{type="warn"}
### Pozor: alias `@...` vs. nová služba `class: ...` {#alias-vs-class-heading}

Drobný rozdíl v syntaxi `services.yaml`, dramatický rozdíl v chování:

- `App\…\UserRepository: '@App\…\DoctrineUserRepository'` – **alias**.
  Kontejner použije existující službu pod druhým jménem. Jedna instance, dvě jména.
- `App\…\UserRepository: { class: App\…\DoctrineUserRepository }` – **nová služba**
  pod klíčem rozhraní. Vznikne *druhá* instance `DoctrineUserRepository` – dva
  EntityManagery, dvě sady listenerů, dva separátní stavy. Při autowiringu
  může vznikat zmatek, kterou instanci `MessageBus` injektuje.

V Symfony 6.3+ je idiomatičtější forma atribut `#[AsAlias]` přímo na implementaci –
viz [Symfony idiomy: `#[AsAlias]`](#symfony-idiomy-asalias). Konfigurace v YAML
se hodí, když implementace patří do jiného balíčku, který nemůžete upravit.
:::

Alias zajistí, že Symfony DI Container injektuje stejnou instanci `DoctrineUserRepository`
všude, kde závislost typuje na `UserRepository`. Doménové modely, hodnotové objekty
a události z auto-registrace vylučujeme – nejsou to služby, ale data.

### Autowiring s oddělenými Bounded Contexts {#autowiring-bounded-contexts}

Ve větších projektech s více Bounded Contexts se autowiring konfiguruje pro každý kontext samostatně.
Každý kontext dostane vlastní blok v `services.yaml` – hranice se tak promítne i do service containeru.

:::callout{type="pattern"}
### Příklad: Samostatný autowiring pro každý Bounded Context {#autowiring-bc-example-heading}

:::code{language="yaml" filename="config/services.yaml"}
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

    App\UserManagement\Domain\Repository\UserRepository: '@App\UserManagement\Infrastructure\Repository\DoctrineUserRepository'

    # ──────────────────────────────────────────────────
    # Bounded Context: OrderManagement
    # ──────────────────────────────────────────────────
    App\OrderManagement\:
        resource: '../src/OrderManagement/'
        exclude:
            - '../src/OrderManagement/Domain/Model/'
            - '../src/OrderManagement/Domain/ValueObject/'
            - '../src/OrderManagement/Domain/Event/'

    App\OrderManagement\Domain\Repository\OrderRepository: '@App\OrderManagement\Infrastructure\Repository\DoctrineOrderRepository'

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

Každý kontext má vlastní blok konfigurace, takže hranice jsou čitelné i na úrovni infrastruktury. Exclude pravidla se dají nastavit pro každý kontext zvlášť – jeden má doménové služby, jiný ne. Při přesunu kontextu do samostatného balíčku nebo microservice stačí odebrat příslušný blok z `services.yaml`, a nechtěný import třídy z cizího kontextu se pozná přímo v konfiguraci.
:::

:::callout{type="note"}
### Co patří do sdílené složky (Shared)? {#shared-folder-heading}

Do sdílené složky by měly patřit pouze skutečně sdílené komponenty, které nemají specifický doménový význam:

- Abstraktní třídy pro ID, Entity, ValueObject
- Utility pro práci s datem a časem
- Obecné výjimky
- Infrastrukturní komponenty používané napříč doménami

Doménové modely, hodnotové objekty a repozitáře patří do svých Bounded Contextů, ne do `Shared/`.
:::

:::faq{}
- question: Kam v Symfony projektu patří doménová vrstva a proč ji držet odděleně?
  answer: 'Doménová vrstva se umisťuje do samostatného adresáře – typicky <code>src/Domain/</code> s podsložkami pro jednotlivé Bounded Contexty – odděleně od kontrolerů, Doctrine mapování a infrastruktury. Izolace umožňuje testovat a refaktorovat model bez závislosti na Symfony životním cyklu a dovoluje přenést doménu i do jiného technologického stacku. Viz <a href="#project-structure">sekci Struktura projektu</a>.'
- question: Jak mapovat agregát v Doctrine bez toho, aby doména závisela na ORM?
  answer: 'V tomto průvodci používáme Doctrine atributy přímo na agregátu jako pragmatickou výchozí volbu – jsou to metadata, ne chování. Pokud trváte na čisté doméně bez stop ORM, korektní řešení je <strong>Persisted Object Pattern</strong> (Vladimir Khorikov; Vernon, <em>IDDD</em>, kap. 12): doménová třída zůstane POPO, vedle ní v infrastruktuře existuje samostatná persistence třída s atributy a mapper mezi nimi. Detail v <a href="#persisted-object-pattern">sekci Persisted Object Pattern – čistá DDD varianta</a>.'
- question: Jak odlišit Aplikační službu od Doménové služby?
  answer: 'Doménová služba drží čistou doménovou logiku, která přirozeně nepatří žádnému agregátu ani hodnotovému objektu – je bezstavová a nekomunikuje s infrastrukturou. Aplikační služba naopak orchestruje use case: přijme vstup z kontroleru, načte agregáty přes repozitář, zavolá doménovou logiku a předá výsledek k persistenci. Aplikační služba nikdy neobsahuje doménová pravidla, pouze posloupnost kroků. Podrobný rozbor v <a href="#application-services">sekci Aplikační služby</a> a <a href="#domain-services">Doménové služby</a>.'
- question: Mají doménové operace vyhazovat výjimky, nebo vracet Result typ?
  answer: 'V PHP a Symfony ekosystému jsou výjimky dominantní cestou. Při porušení invariantu agregát vyhodí konkrétní doménovou výjimku (například <code>InsufficientFundsException</code>). Aplikační vrstva ji přeloží na HTTP odpověď nebo zprávu uživateli. Result/Either typ je v PHP možný, ale přidává složitost bez odpovídajícího přínosu. Kontrolery zachytávají jen doménové podtypy, nikdy obecnou <code>Exception</code>. Rozbor variant v <a href="#error-handling">sekci Strategie zpracování chyb</a>.'
- question: Kdy použít Doctrine Custom Type pro Value Object?
  answer: 'Doctrine Custom Type se hodí tam, kde se hodnotový objekt ukládá jako jednoduchá hodnota v jednom sloupci – peněžní částka, e-mail, URL, vlastní identifikátor. Custom Type přeloží hodnotový objekt při zápisu do primitivu a při čtení ho zpět rekonstruuje. Doménový kód pak pracuje vždy s typovým objektem. Pro hodnotové objekty složené z více sloupců je vhodnější <code>embeddable</code> mapování. Detailní rozbor v <a href="#doctrine-custom-types">sekci Doctrine custom types pro Value Objects</a>.'
:::
