---
route: anti_patterns
path: /anti-vzory
title: Anti-vzory a typické chyby v DDD
page_title: "Anti-vzory a typické chyby v DDD | DDD Symfony"
meta_description: "Nejčastější anti-vzory v Domain-Driven Designu a jak se jim vyhnout: anémický model, Primitive Obsession, god aggregate, sdílená DB napříč kontexty."
meta_keywords: "DDD anti-vzory, anémický doménový model, anemic domain model, Primitive Obsession, God Aggregate, sdílená databáze, Bounded Context, doménové události, immutable events, over-engineering, Ubiquitous Language, DDD chyby, Symfony DDD"
og_type: article
published: "2025-04-24"
modified: "2026-05-04"
breadcrumb_name: Anti-vzory
schema_type: TechArticle
schema_headline: "Anti-vzory a typické chyby v DDD"
chapter_number: "21"
category: Praxe
deck: "Přehled nejčastějších anti-vzorů a typických chyb při implementaci Domain-Driven Design: anémický doménový model, Primitive Obsession, příliš velký agregát, sdílená databáze napříč Bounded Contexts, mutovatelné události a over-engineering."
reading_time: 35
difficulty: 2
github_examples: null
---

## 21.01 Úvodem: Proč znát anti-vzory {#uvodem}

Tato kapitola je **katalog kódových a modelovacích anti-vzorů** v DDD. Pro
**provozní/infrastrukturní třenice** (Doctrine, Messenger, ACL k externím API, Symfony Form vs.
Command) viz [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli). Pro **rozhodovací rámec**,
jestli DDD vůbec použít, viz [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

Domain-Driven Design při návrhu softwaru přináší strukturu a vyjadřovací sílu, ale jeho složitost přináší řadu úskalí. Praxe ukazuje, že týmy začínající s DDD opakovaně narážejí na stejné chyby – i přesto, že teorii dobře rozumí. Anti-vzory je proto potřeba znát stejně dobře jako vzory samotné. Definice termínů použitých v této kapitole (entita, hodnotový objekt, agregát, bounded context) najdete v kapitole [Základní koncepty DDD](/zakladni-koncepty).

Anti-vzor je přístup, který vypadá správně – nebo k němu vývojáři přirozeně sklouznou – ale narušuje principy DDD a způsobuje dlouhodobé problémy s udržovatelností, testovatelností a výkonem.

:::callout{type="note"}
### Klasifikace typických chyb v DDD {#klasifikace-heading}

Chyby při implementaci DDD lze rozdělit do tří kategorií:

- **Strategické chyby** – špatně definované Bounded Contexts, ignorování Ubiquitous Language, sdílená databáze napříč kontexty. Tyto chyby mají nejzávažnější dopad, protože ovlivňují celkovou architekturu systému.
- **Taktické chyby** – anémický doménový model, příliš velké agregáty, Primitive Obsession. Tyto chyby se projevují na úrovni doménového modelu a narušují objektově orientované principy.
- **Implementační chyby** – doménová logika v infrastrukturní vrstvě, mutovatelné události, over-engineering. Tyto chyby vznikají při konkrétní implementaci a jsou obvykle nejsnáze opravitelné.
:::

## 21.02 Anti-vzor: Anémický doménový model (Anemic Domain Model) {#anemicky-domenovy-model}

Anémický doménový model je pravděpodobně nejrozšířenějším anti-vzorem v objektově orientovaném vývoji obecně, a v DDD zvláště. Termín popularizoval Martin Fowler ve svém článku z roku 2003 [[1]](https://martinfowler.com/bliki/AnemicDomainModel.html). V této situaci doménové třídy (entity, agregáty) slouží pouze jako datové kontejnery. Obsahují výhradně gettery a settery a veškerá doménová logika je přesunuta do servisní vrstvy.

:::diagram{fig="22.2-A" title="Anémický vs. bohatý doménový model – kde sedí logika" src="images/diagrams/22_anti_patterns/anemic_vs_rich.svg"}
:::

:::callout{type="note"}
### Proč je anémický model problém? {#anemicky-definice-heading}

- **Porušení zapouzdření (encapsulation)** – základní princip OOP říká, že data a chování, které na nich operuje, by měly být společně. Anémický model toto porušuje tím, že data jsou v entitě, ale logika je jinde.
- **Ztráta modelu jako abstrakce domény** – pokud entity obsahují pouze data, model přestává vyjadřovat chování domény a stává se pouhým datovým schématem přeloženým do tříd. Doménový expert by v takovém modelu nerozeznal žádné doménové procesy ani pravidla, pouze strukturu dat – model tak ztrácí svůj komunikační a dokumentační přínos.
- **Duplicita logiky** – doménová pravidla rozptýlená do service tříd vedou k jejich kopírování na více místech, protože není jasné kanonické místo pro logiku.
- **Obtížná testovatelnost** – testování logiky v servisní vrstvě vyžaduje mockování závislostí, zatímco doménová logika v entitě je testovatelná izolovaně bez jakýchkoli závislostí.
:::

:::callout{type="warn"}
### Špatně: Anémická entita User a servisní vrstva s logikou {#anemicky-spatny-heading}

V tomto příkladu entita `User` neobsahuje žádnou doménovou logiku – pouze gettery a settery. Veškerá logika je přesunuta do `UserService`, což vede k anémickému modelu.
:::

:::callout{type="anti"}
### Příklad: Anémická entita User (špatně)

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Entita je pouze datový kontejner

namespace App\UserManagement\Domain\Model;

class User
{
    private string $id;
    private string $email;
    private string $status;
    private ?string $verificationToken;
    private \DateTimeImmutable $createdAt;

    public function getId(): string { return $this->id; }
    public function setId(string $id): void { $this->id = $id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $token): void { $this->verificationToken = $token; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): void { $this->createdAt = $dt; }
}

// ŠPATNĚ: Doménová logika v servisní třídě
class UserService
{
    public function activateUser(User $user, string $token): void
    {
        if ($user->getStatus() !== 'pending') {
            throw new \DomainException('User is not pending activation.');
        }
        if ($user->getVerificationToken() !== $token) {
            throw new \DomainException('Invalid verification token.');
        }
        $user->setStatus('active');
        $user->setVerificationToken(null);
    }

    public function deactivateUser(User $user): void
    {
        if ($user->getStatus() !== 'active') {
            throw new \DomainException('User is not active.');
        }
        $user->setStatus('inactive');
    }
}
:::
:::

:::callout{type="note"}
### Správně: Entita User s bohatou doménovou logikou {#anemicky-spravny-heading}

Správný přístup přesouvá doménovou logiku přímo do entity. Entita sama zajišťuje své invarianty a vystavuje doménově orientované metody místo holých setterů.
:::

:::callout{type="pattern"}
### Příklad: Bohatá entita User (správně)

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/UserStatus.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}

final class VerificationToken
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
:::

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Entita obsahuje doménovou logiku

namespace App\UserManagement\Domain\Model;

use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserStatus;
use App\UserManagement\Domain\ValueObject\VerificationToken;
use App\UserManagement\Domain\Event\UserRegisteredEvent;
use App\UserManagement\Domain\Event\UserActivatedEvent;
use App\UserManagement\Domain\Event\UserDeactivatedEvent;

class User
{
    private readonly UserId $id;
    private readonly Email $email;
    private UserStatus $status;
    private ?VerificationToken $verificationToken;
    private readonly \DateTimeImmutable $createdAt;
    private array $domainEvents = [];

    private function __construct(
        UserId $id,
        Email $email,
        VerificationToken $verificationToken
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->status = UserStatus::PENDING;
        $this->verificationToken = $verificationToken;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function register(UserId $id, Email $email): self
    {
        $token = VerificationToken::generate();
        $user = new self($id, $email, $token);
        $user->domainEvents[] = new UserRegisteredEvent($id, $email);
        return $user;
    }

    public function activate(VerificationToken $token): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Uživatel není ve stavu čekající na aktivaci.');
        }
        if (!$this->verificationToken->equals($token)) {
            throw new \DomainException('Neplatný ověřovací token.');
        }
        $this->status = UserStatus::ACTIVE;
        $this->verificationToken = null;
        $this->domainEvents[] = new UserActivatedEvent($this->id);
    }

    public function deactivate(): void
    {
        if (!$this->status->isActive()) {
            throw new \DomainException('Lze deaktivovat pouze aktivního uživatele.');
        }
        $this->status = UserStatus::INACTIVE;
        $this->domainEvents[] = new UserDeactivatedEvent($this->id);
    }

    public function id(): UserId { return $this->id; }
    public function email(): Email { return $this->email; }
    public function status(): UserStatus { return $this->status; }

    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
:::
:::

Hlavním rozdílem je, že správná entita vystavuje doménově orientované metody (`activate()`, `deactivate()`, `register()`) namísto generických setterů. Entita sama garantuje své invarianty – nikdo zvenčí nemůže entitu dostat do nekonzistentního stavu.

## 21.03 Anti-vzor: Primitive Obsession (posedlost primitivy) {#primitive-obsession}

Primitive Obsession je anti-vzor, při němž vývojáři používají primitivní datové typy (`string`, `int`, `float`) tam, kam patří hodnotové objekty (Value Objects). Tento anti-vzor je zákeřný, protože primitiva působí na první pohled přímočaře, ale vedou k závažným problémům.

:::callout{type="note"}
### Problémy způsobené Primitive Obsession {#primitive-problemy-heading}

- **Ztráta validace** – primitivní `string` může obsahovat jakoukoliv hodnotu, zatímco hodnotový objekt `Email` garantuje, že vždy obsahuje platnou e-mailovou adresu.
- **Chybějící sémantika** – typ `string` neříká nic o tom, co hodnota reprezentuje. `Email`, `PhoneNumber` nebo `PostalCode` jsou sémanticky bohaté.
- **Záměna identifikátorů** – používání `int` pro všechna ID vede k tomu, že typový systém PHP ani IDE nemohou odhalit záměnu `$orderId` a `$userId` – obě jsou jen `int`.
- **Rozptýlená validační logika** – bez hodnotových objektů se validace opakuje na každém místě, kde se s hodnotou pracuje.
:::

:::callout{type="warn"}
### Špatně: Primitiva místo Value Objects {#primitive-spatny-heading}

Níže uvedený kód používá primitivní typy pro e-mail, peněžní částku a identifikátory. Typový systém PHP neodhalí záměnu `$orderId` za `$userId`, protože obojí je `int`.
:::

:::callout{type="anti"}
### Příklad: Primitive Obsession (špatně)

:::code{language="php" filename="src/Order.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Primitiva místo hodnotových objektů

class Order
{
    private int $id;
    private int $userId;      // int, stejný typ jako $id - záměna je možná!
    private string $email;    // libovolný string, bez validace
    private float $amount;    // float pro peníze - nebezpečné kvůli zaokrouhlování
    private string $currency; // string "CZK", "EUR"... bez omezení

    public function __construct(
        int $id,
        int $userId,
        string $email,
        float $amount,
        string $currency
    ) {
        // Validace (pokud vůbec existuje) je rozptýlena do konstruktoru
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        // ... a opakuje se na každém dalším místě, kde se s hodnotami pracuje
        $this->id = $id;
        $this->userId = $userId;
        $this->email = $email;
        $this->amount = $amount;
        $this->currency = $currency;
    }
}

// Typový systém PHP neodhalí tuto chybu:
$orderId = 42;
$userId = 17;
processOrder($userId, $orderId); // Záměna parametrů - a PHP si nestěžuje!
:::
:::

:::callout{type="note"}
### Správně: Value Objects nesoucí sémantiku a validaci {#primitive-spravny-heading}

Hodnotové objekty zapouzdřují validaci, zabraňují záměně ID různých entit a nesou doménovou sémantiku.
:::

:::callout{type="pattern"}
### Příklad: Value Objects (správně)

:::code{language="php" filename="src/OrderManagement/Domain/ValueObject/Email.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Hodnotové objekty s validací a sémantikou

namespace App\OrderManagement\Domain\ValueObject;

final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" není platná e-mailová adresa.', $value)
            );
        }
        $this->value = $normalized;
    }

    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}

enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';

    public function equals(self $other): bool
    {
        return $this === $other;
    }
}

final class Money
{
    private readonly int $amountInCents; // Celé číslo - žádné problémy s plovoucí desetinnou čárkou
    private readonly Currency $currency;

    public function __construct(int $amountInCents, Currency $currency)
    {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException('Částka nemůže být záporná.');
        }
        $this->amountInCents = $amountInCents;
        $this->currency = $currency;
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new \DomainException('Nelze sčítat částky v různých měnách.');
        }
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function amountInCents(): int { return $this->amountInCents; }
    public function currency(): Currency { return $this->currency; }
}

// Silně typované identifikátory - záměna je odhalena typovým systémem
final class OrderId
{
    public function __construct(private readonly string $value)
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw new \InvalidArgumentException('Neplatný formát UUID pro OrderId.');
        }
    }
    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
}

final class UserId
{
    public function __construct(private readonly string $value) { /* stejná validace */ }
    public function value(): string { return $this->value; }
}

// Nyní typový systém PHP odhalí záměnu:
function processOrder(OrderId $orderId, UserId $userId): void { /* ... */ }

$orderId = new OrderId('a1b2c3d4-...');
$userId  = new UserId('e5f6g7h8-...');
processOrder($userId, $orderId); // PHP TypeError: Argument #1 must be of type OrderId
:::
:::

## 21.04 Anti-vzor: Příliš velký agregát (God Aggregate) {#prilis-velky-agregat}

Agregát navrhujeme kolem transakční konzistence – tedy kolem nejmenší skupiny objektů, která musí být vždy v konzistentním stavu. Příliš velký agregát (někdy označovaný jako „God Aggregate“) sdružuje příliš mnoho entit a logiky do jednoho celku. Tím porušuje princip jedné odpovědnosti a způsobuje řadu závažných problémů.

:::diagram{fig="22.4-A" title="God Aggregate vs. správně rozdělené agregáty propojené přes ID" src="images/diagrams/22_anti_patterns/god_aggregate.svg"}
:::

:::callout{type="note"}
### Problémy způsobené příliš velkým agregátem {#agregat-problemy-heading}

- **Výkonnostní problémy** – načtení celého agregátu z databáze je pomalé, pokud obsahuje stovky nebo tisíce podřízených entit (např. všechny položky objednávky zákazníka za celý rok).
- **Problémy s konkurencí (concurrency)** – agregát je zamčen jako celek při každé změně. Velký agregát znamená větší pravděpodobnost konfliktů při souběžném přístupu.
- **Těsné provázání (tight coupling)** – příliš mnoho entit uvnitř jednoho agregátu ztěžuje nezávislý vývoj a testování.
- **Narušení Bounded Context hranic** – god agregát bývá příznakem špatně definovaných hranic kontextů.
:::

:::callout{type="warn"}
### Špatně: God Aggregate obsahující příliš mnoho entit {#agregat-spatny-heading}

Následující příklad ukazuje agregát `Customer`, který neúměrně sdružuje objednávky, adresy, platební karty i recenze – to vše jako přímé součásti jednoho agregátu.
:::

:::callout{type="anti"}
### Příklad: Příliš velký agregát (špatně)

:::code{language="php" filename="src/Customer.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: God Aggregate - příliš mnoho odpovědností

class Customer
{
    private CustomerId $id;
    private string $name;
    private Email $email;

    /** @var Order[] */
    private array $orders = [];        // Celá historie objednávek

    /** @var Address[] */
    private array $addresses = [];     // Všechny adresy zákazníka

    /** @var CreditCard[] */
    private array $creditCards = [];   // Platební karty

    /** @var ProductReview[] */
    private array $reviews = [];       // Recenze produktů zákazníkem

    /** @var WishlistItem[] */
    private array $wishlistItems = []; // Přání zákazníka

    // Při načtení zákazníka z DB musíme načíst vše - tisíce záznamů!
    // Při update zákazníka zamkneme celou tuto strukturu.
    // Přidání nové objednávky vyžaduje celý agregát v paměti.
}
:::
:::

:::callout{type="note"}
### Správně: Malé agregáty s jasnou transakční hranicí {#agregat-spravny-heading}

Agregáty by měly být navrhovány kolem skutečné transakční potřeby. Zákazník a jeho objednávky jsou samostatné agregáty – objednávku lze vytvořit, aniž by bylo nutné načíst celou historii zákazníka.
:::

:::callout{type="pattern"}
### Příklad: Správně rozdělené agregáty

:::code{language="php" filename="src/OrderManagement/Domain/Model/Customer.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Malé agregáty s jednoznačnou odpovědností

namespace App\OrderManagement\Domain\Model;

use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\CustomerId;
use App\OrderManagement\Domain\ValueObject\ProductId;
use App\OrderManagement\Domain\ValueObject\Address;
use App\OrderManagement\Domain\ValueObject\OrderStatus;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\Currency;
use App\OrderManagement\Domain\ValueObject\Email;
use App\OrderManagement\Domain\ValueObject\WishlistId;
use App\OrderManagement\Domain\Event\OrderPlacedEvent;

// Agregát 1: Customer - pouze identita a kontaktní údaje
class Customer
{
    private readonly CustomerId $id;
    private string $name;
    private Email $email;

    // Zákazník obsahuje jen to, co je součástí jeho identity.
    // Adresa pro doručení je součástí objednávky, ne zákazníka.
}

// Agregát 2: Order - transakční hranice pro jednu objednávku
final class Order
{
    private readonly OrderId $id;
    private readonly CustomerId $customerId; // Pouze reference - ne celý Customer objekt!
    private Address $shippingAddress;
    private OrderStatus $status;

    /** @var OrderItem[] */
    private array $items = [];
    private readonly \DateTimeImmutable $placedAt;
    private array $domainEvents = [];

    public function __construct(
        OrderId $id,
        CustomerId $customerId,
        Address $shippingAddress
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->shippingAddress = $shippingAddress;
        $this->status = OrderStatus::DRAFT;
        $this->placedAt = new \DateTimeImmutable();
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::DRAFT) {
            throw new \DomainException('Položky lze přidat pouze k objednávce ve stavu Draft.');
        }
        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
    }

    public function place(): void
    {
        if (empty($this->items)) {
            throw new \DomainException('Nelze potvrdit prázdnou objednávku.');
        }
        $this->status = OrderStatus::PLACED;
        $this->domainEvents[] = new OrderPlacedEvent($this->id, $this->customerId);
    }

    public function totalAmount(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $carry, OrderItem $item) => $carry->add($item->subtotal()),
            Money::zero(Currency::CZK)
        );
    }
}

// Agregát 3: Wishlist - zcela oddělená doménová odpovědnost
class Wishlist
{
    private readonly WishlistId $id;
    private readonly CustomerId $customerId;
    /** @var WishlistItem[] */
    private array $items = [];
}
:::
:::

Pravidlo pro navrhování agregátů zní: *agregát by měl být co nejmenší, aby zachoval invarianty (doménová pravidla) platné v jedné transakci*. Pokud změna jednoho objektu nevyžaduje konzistentní změnu druhého ve stejné transakci, patří do různých agregátů.

## 21.05 Anti-vzor: Sdílená databáze napříč Bounded Contexts {#sdilena-databaze}

Jeden z nejzávažnějších strategických anti-vzorů nastává, když různé Bounded Contexts sdílejí stejné databázové tabulky nebo přistupují přímo k datům jiného kontextu. I když se to na počátku jeví jako pragmatické řešení, vede to k těsnému provázání, které znemožňuje nezávislý vývoj a nasazení jednotlivých kontextů.

:::callout{type="warn"}
### Špatně: Přímý přístup ke sdíleným tabulkám {#sdilena-db-spatne-heading}

Kontexty *OrderManagement* a *Billing* přímo přistupují ke stejné tabulce `users`. Změna schématu tabulky v jednom kontextu okamžitě ovlivní druhý.
:::

:::callout{type="anti"}
### Příklad: Sdílená databáze (špatně)

:::code{language="php" filename="src/OrderManagement/Infrastructure/Repository/DoctrineOrderRepository.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: OrderManagement context přímo dotazuje tabulku users z UserManagement kontextu

namespace App\OrderManagement\Infrastructure\Repository;

use App\OrderManagement\Domain\ValueObject\CustomerId;
use App\OrderManagement\Domain\ValueObject\OrderId;
use Doctrine\DBAL\Connection;

class DoctrineOrderRepository
{
    public function __construct(private Connection $connection) {}

    public function findOrdersWithUserDetails(CustomerId $customerId): array
    {
        // Přímý JOIN na tabulku z jiného Bounded Context!
        return $this->connection->executeQuery(
            'SELECT o.*, u.email, u.billing_address, u.vat_number
             FROM orders o
             JOIN users u ON o.user_id = u.id   -- tabulka patří do UserManagement kontextu!
             WHERE o.customer_id = :id',
            ['id' => $customerId->value()]
        )->fetchAllAssociative();
    }
}

// Billing context dělá totéž:
namespace App\Billing\Infrastructure;

class InvoiceGenerator
{
    public function generate(OrderId $orderId): Invoice
    {
        // Opět přímý přístup k tabulce orders z OrderManagement kontextu!
        $data = $this->db->query(
            'SELECT o.total, u.billing_address, u.vat_number
             FROM orders o JOIN users u ON o.user_id = u.id
             WHERE o.id = :id',
            ['id' => $orderId->value()]
        );
        // ...
    }
}
:::
:::

:::callout{type="note"}
### Správně: Izolovaná data s Anti-Corruption Layer {#sdilena-db-spravne-heading}

Každý Bounded Context vlastní svá data. Komunikace mezi kontexty probíhá přes definované rozhraní (Anti-Corruption Layer, doménové události nebo explicitní API), nikoliv přes přímý přístup do databáze.
:::

:::callout{type="pattern"}
### Příklad: Izolované kontexty s ACL (správně)

:::code{language="php" filename="src/Billing/Domain/Port/CustomerDataProvider.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Každý kontext vlastní svá data a komunikuje přes definované rozhraní

// OrderManagement kontext si ukládá pouze to, co potřebuje pro svou logiku.
// Billing údaje zákazníka získává přes Anti-Corruption Layer.

namespace App\Billing\Domain\Port;

use App\Billing\Domain\ValueObject\Address;
use App\Billing\Domain\ValueObject\CustomerId;
use App\Billing\Domain\ValueObject\VatNumber;

// Port (rozhraní) - Billing kontext definuje, co potřebuje vědět o zákazníkovi
interface CustomerDataProvider
{
    public function getBillingDataForCustomer(CustomerId $customerId): CustomerBillingData;
}

// CustomerBillingData je DTO specifické pro Billing kontext - ne User entita!
final class CustomerBillingData
{
    public function __construct(
        public readonly string $fullName,
        public readonly Address $billingAddress,
        public readonly ?VatNumber $vatNumber,
    ) {}
}

// Infrastrukturní adapter - implementace v Billing kontextu, volá UserManagement přes API
namespace App\Billing\Infrastructure\Adapter;

use App\Billing\Domain\Port\CustomerBillingData;
use App\Billing\Domain\Port\CustomerDataProvider;
use App\Billing\Domain\ValueObject\Address;
use App\Billing\Domain\ValueObject\CustomerId;
use App\Billing\Domain\ValueObject\VatNumber;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpUserManagementAdapter implements CustomerDataProvider
{
    public function __construct(private readonly HttpClientInterface $httpClient) {}

    public function getBillingDataForCustomer(CustomerId $customerId): CustomerBillingData
    {
        $response = $this->httpClient->request(
            'GET',
            "/internal/users/{$customerId->value()}/billing"
        );
        $data = $response->toArray();

        return new CustomerBillingData(
            fullName: $data['full_name'],
            billingAddress: Address::fromArray($data['billing_address']),
            vatNumber: isset($data['vat_number']) ? new VatNumber($data['vat_number']) : null,
        );
    }
}
:::
:::

Alternativou k synchronnímu HTTP volání je asynchronní komunikace přes doménové události. Billing kontext může naslouchat události `CustomerBillingDataUpdated` a lokálně si ukládat kopii potřebných dat (tzv. *Read Model projection*). Tím se eliminuje synchronní závislost za cenu eventuální konzistence.

## 21.06 Anti-vzor: Mutovatelné doménové události {#mutovatelne-udalosti}

Doménová událost reprezentuje fakt, který se stal v minulosti – a minulost nelze změnit. Události musí být striktně **immutable** (neměnné). Mutovatelná událost je konceptuální rozpor: pokud lze událost po vytvoření změnit, ztrácí svou sémantickou hodnotu jako historický záznam.

Mutovatelné události navíc způsobují praktické problémy při event sourcingu, auditních logách a při komunikaci mezi Bounded Contexts. Přijímající kontext totiž předpokládá, že obdrží konzistentní a neměnná data.

:::callout{type="warn"}
### Špatně: Mutovatelná událost s veřejnými settery {#udalosti-spatne-heading}

Veřejné settery a chybějící `readonly` semantika umožňují modifikaci události po jejím vzniku, čímž narušují integritu historického záznamu.
:::

:::callout{type="anti"}
### Příklad: Mutovatelná událost (špatně)

:::code{language="php" filename="src/OrderPlacedEvent.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Mutovatelná doménová událost

class OrderPlacedEvent
{
    private string $orderId;
    private string $customerId;
    private float $totalAmount;
    private \DateTime $occurredAt; // Mutovatelný DateTime!

    // Veřejné settery - událost lze po vytvoření libovolně měnit
    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function setTotalAmount(float $amount): void
    {
        $this->totalAmount = $amount; // Měnit celkovou částku události? Nonsens!
    }

    public function setOccurredAt(\DateTime $dt): void
    {
        $this->occurredAt = $dt; // Čas vzniku události by měl být fixní
    }

    public function getOrderId(): string { return $this->orderId; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getOccurredAt(): \DateTime { return $this->occurredAt; }
}
:::
:::

:::callout{type="note"}
### Správně: Immutable událost s readonly properties {#udalosti-spravne-heading}

Správná doménová událost je vytvořena jednou, nastavena v konstruktoru a poté nelze žádnou její vlastnost změnit. PHP 8.1+ `readonly` properties jsou pro to přesně určeným nástrojem.
:::

:::callout{type="pattern"}
### Příklad: Immutable doménová událost (správně)

:::code{language="php" filename="src/OrderManagement/Domain/Event/OrderPlacedEvent.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Immutable doménová událost s readonly properties (PHP 8.1+)

namespace App\OrderManagement\Domain\Event;

use App\OrderManagement\Domain\ValueObject\CustomerId;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\OrderId;

final class OrderPlacedEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly OrderId $orderId,
        public readonly CustomerId $customerId,
        public readonly Money $totalAmount,
        public readonly int $itemCount,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
        // Všechny hodnoty jsou nastaveny jednou v konstruktoru.
        // Neexistují žádné settery - událost je neměnná.
    }

    // Jediné metody jsou readonly accessory (nebo přímý přístup k readonly properties)
    public function orderId(): OrderId { return $this->orderId; }
    public function customerId(): CustomerId { return $this->customerId; }
    public function totalAmount(): Money { return $this->totalAmount; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

// Alternativa pro starší PHP: final class s private properties a bez setterů
final class OrderCancelledEvent
{
    private readonly OrderId $orderId;
    private readonly string $reason;
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct(OrderId $orderId, string $reason)
    {
        $this->orderId = $orderId;
        $this->reason = $reason;
        $this->occurredAt = new \DateTimeImmutable();
        // Žádné settery - zapouzdření zajišťuje immutabilitu
    }

    public function orderId(): OrderId { return $this->orderId; }
    public function reason(): string { return $this->reason; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
:::
:::

## 21.07 Anti-vzor: Doménová logika v infrastrukturní vrstvě {#logika-v-infrastrukture}

DDD striktně odděluje doménovou vrstvu od infrastrukturní. Infrastrukturní vrstva (Doctrine repozitáře, Symfony Forms, kontrolery, event listenery) by měla být tenká a delegovat veškerou doménovou logiku do doménové vrstvy. Pokud se doménová pravidla začnou objevovat v infrastrukturních třídách, dochází k narušení architekturních hranic a ke vzniku skryté, těžko testovatelné logiky.

:::callout{type="warn"}
### Špatně: Doménová logika v Doctrine repozitáři {#infra-spatne-heading}

Repozitář by měl pouze ukládat a načítat agregáty. Jakákoliv doménová logika (výpočty, aplikace doménových pravidel, stavové přechody) v repozitáři je anti-vzor.
:::

:::callout{type="anti"}
### Příklad: Doménová logika v repozitáři a kontroleru (špatně)

:::code{language="php" filename="src/UserManagement/Infrastructure/Repository/DoctrineUserRepository.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Doménová logika v Doctrine repozitáři

namespace App\UserManagement\Infrastructure\Repository;

use Doctrine\ORM\EntityRepository;

class DoctrineUserRepository extends EntityRepository
{
    public function activateUser(string $userId, string $token): void
    {
        $user = $this->find($userId);

        // Doménová logika přímo v repozitáři - ŠPATNĚ!
        if ($user->getStatus() !== 'pending') {
            throw new \RuntimeException('User is not pending.');
        }
        if ($user->getToken() !== $token) {
            throw new \RuntimeException('Invalid token.');
        }
        $user->setStatus('active');
        $user->setToken(null);
        $user->setActivatedAt(new \DateTime());

        // Repozitář volá flush - to by měla řídit aplikační vrstva
        $this->getEntityManager()->flush();
    }
}

// ŠPATNĚ: Doménová logika v Symfony kontroleru
class UserController extends AbstractController
{
    public function activate(Request $request, string $userId): Response
    {
        $user = $this->userRepository->find($userId);
        $token = $request->query->get('token');

        // Doménová logika v kontroleru!
        if (empty($token) || strlen($token) !== 32) {
            return $this->json(['error' => 'Invalid token format'], 400);
        }
        if ($user->getCreatedAt() < new \DateTime('-24 hours')) {
            // Expirace tokenu - doménové pravidlo patří do domény, ne do kontroleru!
            $user->setStatus('expired');
            $this->entityManager->flush();
            return $this->json(['error' => 'Token expired'], 400);
        }
        // ...
    }
}
:::
:::

:::callout{type="note"}
### Správně: Tenká infrastruktura, bohatá doménová vrstva {#infra-spravne-heading}

Kontroler a repozitář jsou tenké orchestrátory. Doménová logika žije v doménové entitě nebo doménové službě.
:::

:::callout{type="pattern"}
### Příklad: Správné vrstvení – logika v doméně (správně)

:::code{language="php" filename="src/UserManagement/Infrastructure/Repository/DoctrineUserRepository.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Doménová logika v doménové entitě (viz sekci o anémickém modelu)
// Repozitář je pouze tenký adaptér pro persistenci

namespace App\UserManagement\Infrastructure\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(User $user): void
    {
        $this->em->persist($user);
        // Flush je řízen aplikační vrstvou (Unit of Work), ne repozitářem
    }

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id->value());
    }
}

// SPRÁVNĚ: Aplikační vrstva (Command Handler) orkestruje, doména rozhoduje
namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\Exception\UserNotFoundException;
use App\UserManagement\Domain\ValueObject\VerificationToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ActivateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly EntityManagerInterface $em,
        private readonly MessageBusInterface $eventBus
    ) {}

    public function __invoke(ActivateUserCommand $command): void
    {
        $user = $this->users->findById(new UserId($command->userId));
        if ($user === null) {
            throw new UserNotFoundException($command->userId);
        }

        // Doménová logika je v entitě - handler pouze orkestruje
        $user->activate(VerificationToken::fromString($command->token));

        $this->em->flush(); // Flush patří do aplikační vrstvy

        foreach ($user->releaseDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}

// SPRÁVNĚ: Tenký Symfony kontroler
class UserController extends AbstractController
{
    public function activate(Request $request, string $userId): Response
    {
        $this->commandBus->dispatch(new ActivateUserCommand(
            userId: $userId,
            token: $request->query->getString('token'),
        ));

        return $this->json(['status' => 'activated']);
    }
}
:::
:::

## 21.08 Anti-vzor: Over-engineering u jednoduchých aplikací {#over-engineering}

DDD není vhodné pro každý projekt. Eric Evans sám upozorňuje, že DDD přináší největší přidanou hodnotu u **komplexních domén se složitou doménovou logikou**. Pro jednoduché CRUD aplikace, administrativní nástroje nebo prototypy je plnohodnotné DDD překombinované – přináší vysokou počáteční složitost bez odpovídajícího přínosu.

:::callout{type="note"}
### Příznaky over-engineeringu v DDD kontextu {#overeng-příznaky-heading}

- Agregáty, Value Objects a Events pro doménu, kde skutečně stačí jednoduchý formulář a databázová tabulka (CRUD).
- Více než 5 architekturních vrstev pro aplikaci, jejíž doménová logika se vejde na jednu stránku A4.
- CQRS s event sourcingem pro systém, který nemá požadavky na auditní logy ani na komplexní reporting.
- Tým tráví více času navrhováním architektury než implementací obchodní hodnoty.
- Přidání nové funkce vyžaduje úpravu desítek souborů v různých vrstvách, i když jde o triviální změnu.
:::

:::callout{type="warn"}
### Začněte minimálně, složitost přidávejte podle potřeby {#overeng-warning-heading}

Začněte s minimálním přístupem – aktivní záznamy, service třídy nebo MVC bez DDD. DDD prvky přidávejte inkrementálně, jakmile se doménová složitost začne projevovat. Refaktoring od menšího ke složitějšímu je mnohem méně nákladný než odstraňování zbytečné složitosti z přenavržené architektury.

Vhodné indikátory pro zavedení DDD: *složitá doménová pravidla, která se neustále mění*; *více doménových expertů s odlišnými pohledy na problém*; *systém, u nějž se předpokládá dlouhodobý vývoj a vysoká míra změn v doménové logice*.
:::

:::callout{type="pattern"}
### Příklad: Kdy použít DDD a kdy ne

:::code{language="bash" filename="snippet.sh"}
# DDD je vhodné pro:
✔ E-commerce platforma s komplexními pravidly pro slevy, zásoby, dopravu
✔ Bankovní systém s regulatorními požadavky a složitou finanční logikou
✔ ERP systém se vzájemně propojenými doménovými procesy
✔ Pojišťovací systém s komplexními výpočty pojistného

# DDD je překombinované pro:
✗ Blog nebo CMS (kategorie, příspěvky, komentáře - čistý CRUD)
✗ Jednoduchý e-shop s desítkami produktů a základními objednávkami
✗ Interní admin panel pro správu číselníků
✗ Prototyp nebo MVP s nejistou doménovou logikou
✗ Microservice s jednou jasnou a stabilní odpovědností
:::
:::

## 21.09 Anti-vzor: Ignorování Ubiquitous Language {#missing-ubiquitous-language}

Jedním ze základních pilířů DDD je Ubiquitous Language – společný jazyk sdílený vývojáři, doménovými experty a všemi zainteresovanými stranami. Tento jazyk používáme konzistentně v kódu, dokumentaci, testech i v komunikaci. Ignorování tohoto principu způsobuje, že tatáž doménová entita nese různé názvy na různých místech. Výsledkem jsou nedorozumění, chyby a ztráta doménového vhledu v kódu.

:::callout{type="warn"}
### Špatně: Různé názvy pro stejný koncept {#ubiq-spatne-heading}

Doménový expert mluví o *Pojistníkovi*, databáze má tabulku `clients`, backendový kód používá `User`, frontend říká *Account* a API endpoint je `/customers`. Každá vrstva mluví jiným jazykem.
:::

:::callout{type="anti"}
### Příklad: Nekonzistentní pojmenování (špatně)

:::code{language="php" filename="src/User.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Tatáž doménová entita má různé názvy na různých místech

// Databázová tabulka: "clients"
// Doménový expert: "Pojistník" (PolicyHolder)
// Backendový kód:
class User { /* ... */ }         // Proč User? Systém je pro pojišťovnu!
class Customer { /* ... */ }     // Jiný název ve stejném projektu
class Account { /* ... */ }      // Třetí název v jiném modulu

// API endpoint: GET /api/clients/{id}

// Doctrine entita:
#[ORM\Entity]
#[ORM\Table(name: 'clients')]
class User { /* ... */ }  // Třída "User", tabulka "clients" - zmatek

// Metody v kódu:
function getCustomerById(int $id): User { /* ... */ }   // Vrací User, bere customer
function findUser(int $clientId): Customer { /* ... */ } // Bere client, vrací Customer

// Výsledek: vývojář musí neustále překládat mezi vrstvami místo práce na doménové logice
:::
:::

:::callout{type="note"}
### Správně: Konzistentní jazyk napříč všemi vrstvami {#ubiq-spravne-heading}

Ubiquitous Language vyžaduje investici: vývojáři musí naslouchat doménovým expertům, porozumět jejich terminologii a tu pak konzistentně přenést do kódu. Výsledkem je kód, který doménový expert může číst a rozumět mu.
:::

:::callout{type="pattern"}
### Příklad: Konzistentní Ubiquitous Language (správně)

:::code{language="php" filename="src/Insurance/Domain/Model/PolicyHolder.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Jednotný jazyk pojišťovací domény napříč všemi vrstvami

// Doménový expert: "Pojistník" → kód: PolicyHolder
// Doménový expert: "Pojistná smlouva" → kód: InsurancePolicy
// Doménový expert: "Pojistné plnění" → kód: Claim
// Doménový expert: "Pojistná událost" → kód: InsuredEvent

namespace App\Insurance\Domain\Model;

use App\Insurance\Domain\ValueObject\BirthNumber;
use App\Insurance\Domain\ValueObject\ContactDetails;
use App\Insurance\Domain\ValueObject\Money;
use App\Insurance\Domain\ValueObject\PersonName;
use App\Insurance\Domain\ValueObject\PolicyHolderId;
use App\Insurance\Domain\ValueObject\RiskProfile;

// Třídy pojmenovány přesně podle doménového slovníku:
class PolicyHolder
{
    private readonly PolicyHolderId $id;
    private PersonName $fullName;
    private BirthNumber $birthNumber; // Specifický pojišťovací identifikátor
    private ContactDetails $contactDetails;

    public function fileClaimFor(InsuredEvent $event): Claim
    {
        // Metoda pojmenována jazykem domény - doménový expert rozumí!
        return Claim::open($this->id, $event);
    }
}

class InsurancePolicy
{
    public function calculatePremium(RiskProfile $riskProfile): Money
    {
        // Název metody je přímo z doménového slovníku pojišťovny
        return $this->basePremium->adjustFor($riskProfile);
    }

    public function isValidForEvent(InsuredEvent $event): bool
    {
        // Doménový expert okamžitě rozumí, co tato metoda dělá
        return $this->validFrom <= $event->occurredAt()
            && $this->validTo >= $event->occurredAt();
    }
}

// Databázová tabulka: policy_holders (ne "users" ani "clients")
// API endpoint: POST /api/policy-holders/{id}/claims
// Testy: "When a policy holder files a claim for an insured event..."
:::
:::

:::callout{type="note"}
### Doménový slovník jako živý artefakt {#ubiq-mapa-heading}

Udržujte živý glosář (tzv. *doménový slovník*), který mapuje pojmy z doménového jazyka na odpovídající třídy, metody a databázové struktury v kódu. Slovník musí být dostupný všem členům týmu a pravidelně aktualizovaný.

- **Pojistník** → třída `PolicyHolder`, tabulka `policy_holders`
- **Pojistná smlouva** → třída `InsurancePolicy`, tabulka `insurance_policies`
- **Pojistná událost** → třída `InsuredEvent`, event `InsuredEventOccurred`
- **Pojistné plnění** → třída `Claim`, tabulka `claims`
- **Pojistné (částka)** → Value Object `Premium`

Ubiquitous Language není jen o pojmenování tříd – zahrnuje také pojmenování metod, proměnných, databázových sloupců, API endpointů, chybových zpráv a testovacích scénářů. Čím konzistentnější jazyk, tím přímočařejší mapování mezi požadavky doménového experta a implementací.
:::

Znalost těchto anti-vzorů pomáhá udržet kvalitu doménového modelu po celý životní cyklus projektu. Vaughn Vernonova kniha *Implementing Domain-Driven Design* se anti-vzorům věnuje podrobně na praktických příkladech – viz [doporučené zdroje](/zdroje).

:::faq{}
- question: Co je anémický doménový model a jak ho poznat?
  answer: 'Anémický model vypadá na první pohled jako DDD – obsahuje třídy s názvy agregátů, entit a hodnotových objektů. Veškerá logika je ale přesunutá do služeb. Typickým znakem jsou gettery a settery jako jediné metody a třídy bez jakéhokoli pravidla uvnitř. Doménová logika končí ve „Service“ třídách, které manipulují s daty zvenku. Výsledkem je procedurální kód balený do objektových fasád. Detailní rozbor v <a href="#anemicky-domenovy-model">sekci Anémický doménový model</a>.'
- question: Proč je Primitive Obsession problém?
  answer: 'Primitive Obsession znamená používání primitivních typů (<code>string</code>, <code>int</code>, <code>float</code>) tam, kde patří doménový pojem. Místo typu <code>Email</code> se předává <code>string</code>, místo <code>Money</code> dvojice <code>float</code>. Důsledkem je, že validace a pravidla se opakují v každém místě volání, nebo se zapomínají. Hodnotový objekt s jedním místem validace tyto duplicity odstraňuje a typ dává kontext, co daná hodnota reprezentuje. Rozbor a příklady v <a href="#primitive-obsession">sekci Primitive Obsession</a>.'
- question: Jak poznat, že je agregát příliš velký?
  answer: 'Typické příznaky God Aggregate jsou tři. Agregát obsahuje desítky vnitřních entit. Jeho načtení zabere stovky SQL dotazů. Nebo souběžné operace nad různými částmi narážejí na optimistické zamykání. Pokud dvě metody agregátu řeší vzájemně nezávislá pravidla a nesdílejí invariant, pravděpodobně jde o dva samostatné agregáty. Hranice agregátu má kopírovat hranice transakční konzistence – nic víc. Praktický příklad refaktoringu v <a href="#prilis-velky-agregat">sekci Příliš velký agregát</a>.'
- question: Proč je sdílená databáze mezi Bounded Contexts problém?
  answer: 'Sdílená databáze formálně drží data pohromadě, ale fakticky ruší hranice mezi Bounded Contexts. Změna schématu v jednom kontextu může rozbít druhý, pojmy se mísí a model jednoho týmu začíná záviset na modelu druhého. Správné řešení je, aby každý Bounded Context vlastnil svá data a komunikace probíhala přes definované rozhraní (API, události), nikoli přes sdílenou tabulku. Podrobný rozbor v <a href="#sdilena-databaze">sekci Sdílená databáze napříč Bounded Contexts</a>.'
- question: Musí být doménová událost neměnná?
  answer: 'Ano. Doménová událost popisuje něco, co se již stalo – <code>OrderPlaced</code>, <code>PaymentReceived</code> – a minulost nelze měnit. Událost bez setterů, s neměnnými atributy a časovým razítkem vytvořeným při konstrukci je bezpečné sdílet mezi handlery, persistovat v event store a použít pro zpětnou rekonstrukci stavu. Mutovatelná událost vede k race condition, nedeterministickému zpracování a nekonzistentnímu auditu. Viz <a href="#mutovatelne-udalosti">sekci Mutovatelné doménové události</a>.'
:::
