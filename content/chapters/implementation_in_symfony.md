---
route: implementation_in_symfony
path: /implementace-v-symfony
title: Implementace DDD v Symfony 8
page_title: "Implementace Domain-Driven Design v Symfony 8 | DDD Symfony"
meta_description: "Mapování DDD konceptů na Symfony 8: adresářová struktura podle Bounded Context, Doctrine ORM, Messenger, services.yaml a Doctrine custom types pro VO."
meta_keywords: "DDD v Symfony, implementace DDD, Symfony 8, bounded contexts, vertikální slice architektura, entity v Symfony, hodnotové objekty v PHP, agregáty, repozitáře Doctrine, doménové služby, PHP 8.4"
og_type: article
published: "2025-04-24"
modified: "2026-05-03"
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
implementace v Symfony: Doctrine atributy s custom typy pro hodnotové objekty,
optimistický zámek a generování doménových událostí. Kapitola
[Anti-vzory](/anti-vzory) pak ukazuje produkční kvalitu kódu s custom
výjimkami, factory metodami a plnou validací invariantů.
:::

:::callout{type="note"}
### Mapping volba: atributy jako default {#mapping-volba-heading}

Tento průvodce používá **Doctrine atributy přímo na doménových třídách**
(`#[ORM\Entity]`, `#[ORM\Column]`). Argumentem proti je porušení
*Dependency Inversion* – doména „ví" o Doctrine. V praxi je ten import metadata,
ne chování: třída se chová stejně, pouze nese popisek pro mapper. Symfony Maker,
oficiální dokumentace i drtivá většina open-source Symfony projektů používá atributy.

Pokud chcete striktně oddělenou doménu, korektní cesta není XML mapping (taky
„znečištěné", jen jiným formátem), ale **Persisted Object Pattern** – samostatná
persistence třída + mapper na doménový agregát. Detail v sekci
[Persisted Object Pattern – pure DDD varianta](#persisted-object-pattern).
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

Entita v DDD je objekt s jedinečnou, přetrvávající identitou. Vstupní bod do agregátu
je **kořen agregátu** – třída `final`, dědí z bázové `AggregateRoot`,
konstruktor je `private` a vznik probíhá přes pojmenovanou factory metodu
(`User::register()`, `Order::place()`). To zaručuje, že nelze vytvořit
agregát v nekonzistentním stavu.

:::callout{type="pattern"}
### Příklad: kořen agregátu User {#entity-example-heading}

:::code{language="php" filename="src/Shared/Domain/AggregateRoot.php"}
<?php

declare(strict_types=1);

namespace App\Shared\Domain;

abstract class AggregateRoot
{
    /** @var list<object> */
    private array $domainEvents = [];

    final protected function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    final public function releaseDomainEvents(): array
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

use App\Shared\Domain\AggregateRoot;
use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserName;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User extends AggregateRoot
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

        $this->record(new UserRegistered($id, $email, $this->createdAt));
    }

    public static function register(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
    ): self {
        return new self($id, $name, $email, $hashedPassword);
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

Co zde stojí za pozornost:

- **`final` + `extends AggregateRoot`.** `AggregateRoot` poskytuje `record()`
  a `releaseDomainEvents()` – sdílené chování pro všechny agregáty, ne duplicitní
  kopii v každé entitě. `final` zabraňuje dědění (entita s subklasou nezachová
  invarianty kořene).
- **Privátní konstruktor + factory `register()`.** Jediná legální cesta vytvoření.
  Kdyby přibyla další kategorie (importovaný uživatel z LDAP), přidá se další
  factory, ne přepínač uvnitř konstruktoru.
- **VO uloženy přímo, ne jako primitivy.** `UserId`, `Email`, `UserName`
  a `HashedPassword` jsou typy vlastností. Doctrine je hydratuje přes custom typy
  (`user_id`, `email_vo`) nebo `#[ORM\Embedded]`. Žádné re-validace v getterech.
- **`#[ORM\Version]` pro optimistický zámek.** Souběžné modifikace agregátu
  vyhází `OptimisticLockException`, kterou aplikační vrstva přeloží na retry.
- **Method names z Ubiquitous Language.** `rename()` místo `setName()`,
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

## 11.04 Implementace hodnotových objektů {#value-objects}

Hodnotový objekt v DDD nemá identitu – je definován svými atributy. Je neměnný,
validuje se v konstruktoru a dvě instance se stejnými atributy jsou rovnocenné.
V Symfony 8 se implementuje jako `final readonly` PHP třída:

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

- **Akceptuje technicky platné, ale podivné adresy** – `a@b` (bez TLD)
  s `FILTER_FLAG_EMAIL_UNICODE` nepustí, ale s defaultním nastavením ano.
- **Nepouští IDN domény** (`uživatel@české-domény.cz`)
  bez explicitního převodu přes `idn_to_ascii()`.
- **Neověřuje existenci schránky.** Validní syntaxe ≠ doručitelná adresa.

V doménové vrstvě tedy validujeme **syntakticky**. Pravdivost ověří až
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
a má rozumnou délku" je vynucen typem. Volající kód nemá šanci vložit prázdný
string – pokud by to zkusil, dostane výjimku v konstruktoru, ne až v repozitáři.
`#[ORM\Embeddable]` říká Doctrine, že VO se ukládá jako sloupec ve stejné tabulce
jako vlastník (žádná samostatná tabulka pro VO).

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

use App\Shared\Infrastructure\Outbox\OutboxRecorder;
use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OutboxRecorder $outbox,
    ) {}

    public function save(User $user): void
    {
        $this->em->wrapInTransaction(function () use ($user): void {
            $this->em->persist($user);

            // Doménové eventy uložíme do outbox tabulky ve STEJNÉ transakci jako
            // agregát. Tím získáme atomicitu „state + event" – buď oboje, nebo nic.
            // Worker (Symfony Messenger consumer) je z outboxu vyzobává a dispatchuje
            // do reálného transportu. Detail viz kapitola „Outbox Pattern".
            foreach ($user->releaseDomainEvents() as $event) {
                $this->outbox->record($event);
            }

            $this->em->flush();
        });
    }

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email->value()]);
    }
}
:::
:::

`DoctrineUserRepository` implementuje doménové rozhraní `UserRepository` přes Doctrine ORM.
`save()` zapisuje agregát i jeho události uvnitř jedné transakce – stav a publikace
události tak nemůžou divergovat. `OutboxRecorder` je tenká utilita, která serializuje
event do tabulky `outbox`; samostatný worker ji čte a doručuje do Messenger transportu.
Podrobnosti v kapitole [Outbox Pattern](/outbox-pattern).

:::callout{type="warn"}
### Proč ne přímý dispatch po flush? {#event-dispatch-heading}

Naivní varianta zapíše agregát přes `flush()` a pak iteruje přes `eventBus->dispatch($event)`.
Vypadá nevinně, ale má dvě skryté chyby:

- **Atomicita selhává.** Pokud `dispatch()` selže (Messenger transport je
  nedostupný, RabbitMQ down, síťová chyba), agregát už je v databázi, ale událost ne.
  Z pohledu volajících kontextů se „registrace neudála" – přitom uživatel reálně existuje.
- **Pořadí transakcí.** `flush()` provede UPDATE/INSERT, ale Doctrine *v některých
  konfiguracích* nezavře transakci přímo v něm (např. uvnitř `wrapInTransaction`).
  Dispatch před commitem vidí změny, které ostatní procesy ještě ne. Race condition.

Outbox pattern obojí řeší: událost je v stejné DB transakci jako agregát, takže
buď doručíme obojí (v pořádku), nebo nic (rollback). Worker doručuje v separátní
transakci s retry strategií. **Tohle je doporučená produkční varianta a v dalších
příkladech v této knize ji používáme jako default.**
:::

## 11.06 Persisted Object Pattern – pure DDD varianta {#persisted-object-pattern}

Pokud trváte na tom, že doménová vrstva nesmí obsahovat ani metadata
o persistenci, korektní cesta není XML mapping (taky „znečištěné", jen jiným
formátem), ale **Persisted Object Pattern** – varianta vzoru *Data Mapper* (Fowler, *PoEAA*, 2002),
kterou v DDD kontextu rozebírá Vlad Khorikov v sérii blogpostů „Persistence model" a Vaughn Vernon v *IDDD*, kap. 6.

Idea: doménová třída zůstane POPO bez atributů. Vedle ní v infrastrukturní
vrstvě existuje samostatná persistence třída se všemi Doctrine atributy.
Dva mappery (one-way každým směrem) překládají mezi nimi.

:::callout{type="pattern"}
### Příklad: doména POPO + persistence model + mapper {#persisted-object-example-heading}

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php (POPO – bez atributů)"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Model;

use App\Shared\Domain\AggregateRoot;
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
        $model->id = $user->id->value();
        $model->name = (string) $user->name();
        $model->email = $user->email()->value;
        $model->passwordHash = $user->hashedPassword()->value();
        $model->createdAt = $user->createdAt;

        return $model;
    }
}
:::
:::

:::callout{type="note"}
### Cena pure varianty {#persisted-object-tradeoffs-heading}

Persisted Object Pattern je **opravdu** čistá doména – žádné atributy, žádné stopy
ORM, žádné `use Doctrine\…`. Cena:

- **2× kód.** Doménová třída + persistence model + mapper. Pro každý agregát.
- **Mapování VO ručně.** Custom typy z hlavní cesty zde nepoužiješ – musí to dělat
  mapper. U 5+ VO se kód mapperu rozrůstá.
- **Riziko driftu.** Když přibude pole v doméně, musí přibýt v persistence modelu
  i v mapperech. Žádný compiler to nehlídá.
- **Optimistický zámek je řešení navíc.** `#[ORM\Version]` je v persistence modelu;
  doména `User` musí přijmout `version` jako parametr `reconstitute()` nebo
  spoléhat na infrastruktuře, že verzi sleduje sama.

Doporučení: použít Persisted Object **jen v kontextech, kde je oddělení
opravdu důležité** (Core Domain s vysokou hodnotou, dlouhodobá údržba, plán
na výměnu persistence). Pro většinu Bounded Contextů jsou atributy přijatelný kompromis.
:::

V dalších příkladech v tomto průvodci pokračujeme s atributy přímo na agregátech.
Persisted Object Pattern dále nerozvíjíme – principy jsou identické, jen vyžadují
explicitní mapper na každý agregát.

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

## 11.09 Doménové služby (a kdy je *nepoužít*) {#domain-services}

Doménová služba zapouzdřuje pravidlo, které **přirozeně nepatří žádnému agregátu
ani hodnotovému objektu** – typicky operaci nad dvěma a více agregáty
(`MoneyTransferService` mezi dvěma účty) nebo bezstavový výpočet vyžadující
externí zdroj (kurzovní převod, kalkulace daně podle jurisdikce).

**Než sáhnete po doménové službě, ptejte se nejdřív: nepatří to do agregátu?**
Pravidlo „lze platit jen confirmed objednávku" je čistý invariant agregátu `Order` –
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
což znamená, že invariant „platit lze jen confirmed objednávku" je vynucen
typovým systémem, ne nadějí, že někdo zavolá správnou službu. Aplikační handler
pak má triviální koordinační roli:

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
  – pravidlo „součet zůstatků je konstantní" se týká dvou účtů a nepatří jednomu
  ani druhému. (Pozor: stejně se ukládá v jedné transakci na jeden agregát –
  viz [agregát = transakční hranice](/navrh-agregatu#transactional-consistency).)
- **Bezstavový výpočet s externí znalostí.** Daňová sazba podle jurisdikce a typu
  zboží, převod měn podle aktuálního kurzu. Logika je čistě doménová, ale
  vstupy přicházejí zvenčí.
- **Generická doménová operace bez přirozeného vlastníka.** „Vyčisti expirované
  rezervace starší než X dnů" – akce nad množinou agregátů, kde žádný z nich
  není přirozený vlastník pravidla.

Ve všech ostatních případech: pravidlo patří do agregátu, hodnotového objektu nebo
specifikace ([Specification Pattern](#specification-pattern)).
:::

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

Symfony nabízí dva mechanismy pro „něco se stalo":

- **EventDispatcher** (`EventDispatcherInterface`) – synchronní,
  in-process. Listenery se provedou okamžitě v témž PHP požadavku, ve sdíleném
  paměťovém prostoru. Bez serializace, bez network round-tripu.
- **Messenger** (`MessageBusInterface`) – může být synchronní i asynchronní.
  Podporuje transporty (RabbitMQ, Redis, Doctrine outbox), retry strategii
  a sériovou serializaci zprávy. Příjemce může běžet v jiném procesu, jiném serveru.

**Volba podle role příjemce:**

- **In-context, in-request listenery** (read model uvnitř téhož kontextu,
  audit log, cache invalidace uvnitř téhož commitu) → **EventDispatcher**.
  Žádná serializace, listenery vidí stejný `EntityManager`, stejnou transakci.
- **Cross-context komunikace** (publikace události mimo Bounded Context, kterou
  zpracuje jiný kontext / služba / projekce) → **Messenger** přes outbox.
  Zpráva přejde DB transakci, dorazí do brokera, jiný kontext si ji odebere.

V praxi to znamená: agregát publikuje doménovou událost do svého `domainEvents` pole.
Repozitář při `save()` zapíše událost do outbox tabulky. Z outboxu pak worker
*synchronně* (přes EventDispatcher) doručí lokálním listenerům uvnitř téhož kontextu
a *asynchronně* (přes Messenger transport) propustí ven pro ostatní kontexty.

**Anti-vzor:** používat Messenger jako náhradu za EventDispatcher uvnitř téhož
kontextu, protože „je to flexibilnější". Cena: každá zpráva projde JSON serializací,
ztráta typů, ztráta transakční koheze, nutnost správy transportů. Zvolte mechanismus
podle hranice, kterou událost překračuje – ne podle hypotetické budoucí potřeby.
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
            sprintf('Uživatel s e-mailem "%s" již existuje.', $email->value()),
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
            id: $user->id->value(),
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

Kontroler je adapter mezi HTTP a aplikační vrstvou. Smí: validovat formát vstupu,
transformovat ho na command/query, dispatchovat, přeložit doménovou výjimku
na HTTP odpověď. Nesmí: nést doménová pravidla, volat repozitáře přímo,
manipulovat s agregáty.

Symfony 7+ nabízí `#[MapRequestPayload]`, který deserializuje a validuje
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
        } catch (DuplicateEmailException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
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

:::callout{type="pattern"}
### Příklad: kernel test command handleru {#kernel-test-heading}

DDD agregáty se testují jako čistý PHP. Aplikační handlery, které se opírají
o repozitář, se nejlépe testují jako **kernel test** s in-memory implementací
repozitáře nebo s testovací databází (Doctrine SQLite v `KERNEL_TEST` env).

:::code{language="php" filename="tests/UserManagement/Registration/RegisterUserHandlerTest.php"}
<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Registration;

use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Command\RegisterUserHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RegisterUserHandlerTest extends KernelTestCase
{
    public function test_registers_new_user(): void
    {
        $container = static::getContainer();
        $handler = $container->get(RegisterUserHandler::class);
        $repo = $container->get(UserRepository::class);

        $handler(new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@novak.cz',
            password: 'tajne-heslo-1234',
        ));

        $user = $repo->findByEmail(\App\UserManagement\Domain\ValueObject\Email::fromUserInput('jan@novak.cz'));
        self::assertNotNull($user);
        self::assertSame('Jan Novák', (string) $user->name());
    }

    public function test_rejects_duplicate_email(): void
    {
        $handler = static::getContainer()->get(RegisterUserHandler::class);

        $handler(new RegisterUser(
            name: 'První',
            email: 'duplicate@test.cz',
            password: 'tajne-heslo-1234',
        ));

        $this->expectException(\App\UserManagement\Domain\Exception\DuplicateEmailException::class);

        $handler(new RegisterUser(
            name: 'Druhý',
            email: 'duplicate@test.cz',
            password: 'jine-tajne-heslo-1234',
        ));
    }
}
:::
:::

Použití skutečného DB transportu (SQLite v testech, PostgreSQL v CI) garantuje,
že unique constraint, transakční chování a outbox skutečně fungují.
In-memory mock repozitáře tyto vlastnosti negarantuje.

:::callout{type="pattern"}
### Symfony Voter pro autorizaci nad agregátem {#voter-heading}

Autorizace „kdo smí volat tuto akci" patří do prezentační/aplikační vrstvy, ne do agregátu.
Symfony nabízí Voter API – idiomatické místo, kde se ptát „má aktuální uživatel právo
udělat X s tímto agregátem":

:::code{language="php" filename="src/OrderManagement/Infrastructure/Security/OrderVoter.php"}
<?php

declare(strict_types=1);

namespace App\OrderManagement\Infrastructure\Security;

use App\OrderManagement\Domain\Model\Order;
use App\UserManagement\Domain\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoter extends Voter
{
    public const VIEW = 'ORDER_VIEW';
    public const CANCEL = 'ORDER_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL], true)
            && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$subject instanceof Order) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $subject->customerId->equals($user->id->asCustomerId()),
            self::CANCEL => $subject->customerId->equals($user->id->asCustomerId())
                && $subject->status->canBeCancelled(),
            default => false,
        };
    }
}
:::

Voter pak volá kontroler před dispatchem commandu:

:::code{language="php" filename="(controller)"}
$this->denyAccessUnlessGranted(OrderVoter::CANCEL, $order);
$this->commandBus->dispatch(new CancelOrder($order->id->value()));
:::

Voter chrání **právo na akci**. Doménový invariant „lze stornovat jen objednávku
ve stavu A" je v `Order::cancel()` metodě – Voter ho nedubluje, jen zabraňuje
volání, které by stejně skončilo `DomainException`.
:::

## 11.15 Dependency Injection a autowiring {#dependency-injection}

Dependency Injection odděluje závislosti a umožňuje testování bez reálné infrastruktury.
Symfony 8 poskytuje DI Container pro konfiguraci služeb:

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
  EntityManagery, dva sady listenerů, dva separátní stavy. Při autowiringu
  může vznikat zmatek, kterou instanci `MessageBus` injektuje.

V Symfony 6.3+ je idiomatičtější forma atribut `#[AsAlias]` přímo na implementaci –
viz [Symfony idiomy: `#[AsAlias]`](#symfony-idiomy-asalias). Konfigurace v YAML
se hodí, když implementace patří do jiného balíčku, který nemůžete upravit.
:::

Alias zajistí, že Symfony DI Container injektuje stejnou instanci `DoctrineUserRepository`
všude, kde závislost typuje na `UserRepository`. Doménové modely, hodnotové objekty
a události z auto-registrace vylučujeme – nejsou to služby, ale data.

### Autowiring s oddělenými Bounded Contexts {#autowiring-bounded-contexts}

Ve větších projektech s více Bounded Contexts konfigurujte autowiring pro každý kontext samostatně.
Každý kontext dostane vlastní blok v `services.yaml`, čímž ohraničíme kontext i na úrovni service containeru.

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
  answer: 'V tomto průvodci používáme Doctrine atributy přímo na agregátu jako pragmatický default – jsou to metadata, ne chování. Pokud trváte na čisté doméně bez stop ORM, korektní řešení je <strong>Persisted Object Pattern</strong> (Khononov, <em>Learning DDD</em>): doménová třída zůstane POPO, vedle ní v infrastruktuře existuje samostatná persistence třída s atributy a mapper mezi nimi. Detail v <a href="#persisted-object-pattern">sekci Persisted Object Pattern – pure DDD varianta</a>.'
- question: Jak odlišit Aplikační službu od Doménové služby?
  answer: 'Doménová služba drží čistou doménovou logiku, která přirozeně nepatří žádnému agregátu ani hodnotovému objektu – je bezstavová a nekomunikuje s infrastrukturou. Aplikační služba naopak orchestruje use case: přijme vstup z kontroleru, načte agregáty přes repozitář, zavolá doménovou logiku a předá výsledek k persistenci. Aplikační služba nikdy neobsahuje doménová pravidla, pouze posloupnost kroků. Podrobný rozbor v <a href="#application-services">sekci Aplikační služby</a> a <a href="#domain-services">Doménové služby</a>.'
- question: Mají doménové operace vyhazovat výjimky, nebo vracet Result typ?
  answer: 'V PHP a Symfony ekosystému jsou výjimky dominantní cestou. Při porušení invariantu agregát vyhodí konkrétní doménovou výjimku (například <code>InsufficientFundsException</code>). Aplikační vrstva ji přeloží na HTTP odpověď nebo zprávu uživateli. Result/Either typ je v PHP možný, ale přidává složitost bez odpovídajícího přínosu. Kontrolery zachytávají jen doménové podtypy, nikdy obecnou <code>Exception</code>. Rozbor variant v <a href="#error-handling">sekci Strategie zpracování chyb</a>.'
- question: Kdy použít Doctrine Custom Type pro Value Object?
  answer: 'Doctrine Custom Type se hodí tam, kde se hodnotový objekt ukládá jako jednoduchá hodnota v jednom sloupci – peněžní částka, e-mail, URL, vlastní identifikátor. Custom Type přeloží hodnotový objekt při zápisu do primitivu a při čtení ho zpět rekonstruuje. Doménový kód pak pracuje vždy s typovým objektem. Pro hodnotové objekty složené z více sloupců je vhodnější <code>embeddable</code> mapování. Detailní rozbor v <a href="#doctrine-custom-types">sekci Doctrine custom types pro Value Objects</a>.'
:::
