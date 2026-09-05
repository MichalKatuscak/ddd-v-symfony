---
route: anti_patterns
path: /anti-vzory
title: Anti-vzory a typické chyby v DDD
page_title: "Anti-vzory a typické chyby v DDD | DDD Symfony"
meta_description: "Nejčastější anti-vzory v Domain-Driven Designu a jak se jim vyhnout: anémický model, Primitive Obsession, god aggregate, sdílená DB napříč kontexty."
meta_keywords: "DDD anti-vzory, anémický doménový model, anemic domain model, Primitive Obsession, God Aggregate, sdílená databáze, Bounded Context, doménové události, immutable events, over-engineering, Ubiquitous Language, DDD chyby, Symfony DDD"
og_type: article
published: "2025-04-24"
modified: "2026-09-05"
breadcrumb_name: Anti-vzory
schema_type: TechArticle
schema_headline: "Anti-vzory a typické chyby v DDD"
chapter_number: "21"
category: Praxe
deck: "Přehled nejčastějších anti-vzorů a typických chyb při implementaci Domain-Driven Design: anémický doménový model, Primitive Obsession, příliš velký agregát, sdílená databáze napříč Bounded Contexts, mutovatelné události a over-engineering."
reading_time: 38
difficulty: 2
github_examples: null
---

## 21.01 Úvodem: Proč znát anti-vzory {#uvodem}

Tato kapitola je **katalog kódových a modelovacích anti-vzorů** v DDD. Pro
**provozní/infrastrukturní třenice** (Doctrine, Messenger, ACL k externím API, Symfony Form vs.
Command) viz [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli). Pro **rozhodovací rámec**,
jestli DDD vůbec použít, viz [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

DDD nabízí strukturu pro modelování domény, ale s tou strukturou přicházejí specifická úskalí. Týmy začínající s DDD opakovaně narážejí na stejné chyby, i když teorii rozumějí. Anti-vzory je proto potřeba znát stejně dobře jako vzory samotné. Definice termínů použitých v této kapitole (entita, hodnotový objekt, agregát, bounded context) najdete v kapitole [Základní koncepty DDD](/zakladni-koncepty).

Anti-vzor je přístup, ke kterému vývojáři přirozeně sklouznou. Vypadá správně, ale narušuje principy DDD a dlouhodobě podkopává udržovatelnost, testovatelnost i výkon. Každá sekce níže proto nese rozpoznávací znak – větu, podle které zjistíte, jestli se problém týká vašeho kódu – a hranici, za kterou už kritizovaný postup chybou není.

Nejznámější anti-vzor DDD zde nenajdete. Big Ball of Mud, tedy oblast bez rozeznatelných hranic, patří ke Context Mappingu, protože se dá vědomě ohraničit a nechat být; rozebírá jej [sekce 03.12](/context-mapping#big-ball-of-mud).

:::callout{type="note"}
### Klasifikace typických chyb v DDD {#klasifikace-heading}

Chyby při implementaci DDD spadají do tří kategorií podle toho, kde vznikají a kolik stojí jejich náprava. Sekce této kapitoly jsou k nim přiřazeny níže.

- **Strategické chyby** – špatně definované Bounded Contexts, ignorování Ubiquitous Language, sdílená databáze napříč kontexty (21.05, 21.09). Dopad je nejzávažnější, protože strategické chyby ovlivňují celkovou architekturu systému.
- **Taktické chyby** – anémický doménový model (21.02), Primitive Obsession (21.03), příliš velké agregáty (21.04). Projevují se na úrovni doménového modelu a narušují objektově orientované principy.
- **Implementační chyby** – mutovatelné události (21.06), doménová logika v infrastrukturní vrstvě (21.07), over-engineering (21.08). Vznikají při konkrétní implementaci a obvykle se opravují nejsnáz.
:::

## 21.02 Anti-vzor: Anémický doménový model (Anemic Domain Model) {#anemicky-domenovy-model}

Anémický model patří k nejčastějším anti-vzorům objektově orientovaného vývoje a v DDD zvlášť bolí. Termín popularizoval Martin Fowler v článku z roku 2003 [[1]](https://martinfowler.com/bliki/AnemicDomainModel.html). Doménové třídy (entity, agregáty) v něm slouží pouze jako datové kontejnery. Obsahují výhradně gettery a settery a veškerá doménová logika je přesunuta do servisní vrstvy.

Fowler považoval argument „porušuje se zapouzdření“ za příliš slabý a připojil druhý, nákladový: anémický model nese veškeré náklady doménového modelu, aniž by přinášel jeho užitek. Zaplatíte mapování na databázi, obalování hodnot a rozpad kódu do vrstev, a dostanete datovou strukturu, kterou by obsloužil obyčejný `SELECT`. Účet bez protihodnoty je jádro problému, ne nedodržená poučka o OOP.

**Rozpoznávací znak.** Vaughn Vernon k tomu v *Implementing Domain-Driven Design* (2013) nabízí diagnostický test dvou otázek. Volně přeloženo: má vaše entita jen gettery a settery, a žije pravidlo, které s jejími daty pracuje, v cizí třídě? Dvě „ano“ znamenají anémii. Test je použitelnější než definice, protože ho pustíte na konkrétní soubor.

:::diagram{fig="21.2-A" title="Anémický vs. bohatý doménový model – kde sedí logika" src="images/diagrams/22_anti_patterns/anemic_vs_rich.svg"}
:::

:::callout{type="note"}
### Proč je anémický model problém? {#anemicky-definice-heading}

- **Porušení zapouzdření (encapsulation)** – základní princip OOP říká, že data a chování, které na nich operuje, by měly být společně. Anémický model toto porušuje tím, že data jsou v entitě, ale logika je jinde.
- **Ztráta modelu jako abstrakce domény** – pokud entity obsahují pouze data, model přestává vyjadřovat chování domény a stává se pouhým datovým schématem přeloženým do tříd. Doménový expert by v takovém modelu nerozeznal žádné doménové procesy ani pravidla, pouze strukturu dat – model tak ztrácí svůj komunikační a dokumentační přínos.
- **Duplicita logiky** – doménová pravidla rozptýlená do service tříd vedou k jejich kopírování na více místech, protože není jasné kanonické místo pro logiku.
- **Testy potřebují víc lešení** – pravidlo přesunuté do služby se testuje přes celou tuto službu a přes všechno, co má v konstruktoru. Entita bez závislostí se otestuje jedním `new` a jedním voláním. Výhoda ale není bezpodmínečná: dobře navržená aplikační služba se testuje bez potíží a agregát, který ke svému rozhodnutí potřebuje kolaboranty, ji ztrácí také.
:::

Anti-vzorem není servisní vrstva jako taková. Fowler v témže článku Service Layer výslovně obhajuje a odmítá jen to, aby v ní žila *veškerá* doménová logika. Rozlišit je proto potřeba tři věci, které se v projektech jmenují stejně:

1. **Aplikační služba** orkestruje: načte agregát, zavolá na něm jednu metodu, uloží výsledek, odešle události. Vlastní doménové pravidlo neobsahuje a je legitimní.
2. **Doménová služba** nese pravidlo, které nepatří jedinému agregátu – výpočet přes několik agregátů nebo politiku s externím vstupem. Je to řádný stavební blok, viz [doménové služby](/zakladni-koncepty#domain-services).
3. **„God service“** drží pravidla patřící entitám, které samy nemají žádné chování. Teprve to je anémický model.

:::callout{type="warn"}
### Špatně: Anémická entita User a servisní vrstva s logikou {#anemicky-spatny-heading}

Entita `User` nese jen gettery a settery; veškerá doménová logika sedí v `UserService`.
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

// ŠPATNĚ: Veškerá doménová logika v servisní třídě
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
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}

final class VerificationToken
{
    private function __construct(
        public readonly string $value,
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
use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\Event\UserActivated;
use App\UserManagement\Domain\Event\UserDeactivated;
use App\UserManagement\Domain\Exception\InvalidVerificationTokenException;
use App\UserManagement\Domain\Exception\UserAlreadyActivatedException;
use App\UserManagement\Domain\Exception\UserNotActiveException;
use App\SharedKernel\Domain\AggregateRoot;

final class User extends AggregateRoot
{
    private readonly UserId $id;
    private readonly Email $email;
    private UserStatus $status;
    private ?VerificationToken $verificationToken;
    private readonly \DateTimeImmutable $createdAt;

    private function __construct(
        UserId $id,
        Email $email,
        VerificationToken $verificationToken
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->status = UserStatus::Pending;
        $this->verificationToken = $verificationToken;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function register(UserId $id, Email $email): self
    {
        $token = VerificationToken::generate();
        $user = new self($id, $email, $token);
        $user->record(new UserRegistered($id, $email));
        return $user;
    }

    public function activate(VerificationToken $token): void
    {
        if (!$this->status->isPending()) {
            throw UserAlreadyActivatedException::forUser($this->id);
        }
        if (!$this->verificationToken->equals($token)) {
            throw InvalidVerificationTokenException::forUser($this->id);
        }
        $this->status = UserStatus::Active;
        $this->verificationToken = null;
        $this->record(new UserActivated($this->id));
    }

    public function deactivate(): void
    {
        if (!$this->status->isActive()) {
            throw UserNotActiveException::forUser($this->id);
        }
        $this->status = UserStatus::Inactive;
        $this->record(new UserDeactivated($this->id));
    }

    public function id(): UserId { return $this->id; }
    public function email(): Email { return $this->email; }
    public function status(): UserStatus { return $this->status; }
}
:::
:::

Rozdíl je v tom, že správná entita vystavuje doménově orientované metody (`activate()`, `deactivate()`, `register()`) místo generických setterů. Entita sama garantuje své invarianty – nikdo zvenčí ji nedostane do nekonzistentního stavu.

:::callout{type="note"}
### Getter a setter už nejsou spolehlivý příznak {#php84-priznaky-heading}

Fowlerova diagnóza z roku 2003 se opírala o tvar kódu: dvojice `getX()` / `setX()` znamenala datový kontejner. PHP 8.4 tento signál oslabuje. Asymetrická viditelnost `public private(set) UserStatus $status` [[4]](https://wiki.php.net/rfc/asymmetric-visibility-v2) vystaví vlastnost ke čtení a zápis nechá jen uvnitř třídy; property hooks [[5]](https://www.php.net/manual/en/language.oop5.property-hooks.php) odstraní většinu ručně psaných přístupových metod. Entita bez jediného getteru proto může být stejně anémická jako ta s dvaceti.

Příznakem zůstává **veřejný zápis stavu bez doménového jména**. Řádek `$user->status = UserStatus::Active;` v aplikační vrstvě je totéž co `setStatus('active')`, jen kratší. Hodnotové objekty této knihy hooky nepoužijí: s `readonly` vlastnostmi je zkombinovat nelze [[5]](https://www.php.net/manual/en/language.oop5.property-hooks.php).
:::

### Kdy anémický model chyba není {#anemicky-kdy-nevadi}

Fowler sám připouští, že doménový model není vždy nejlepší nástroj, a odkazuje na Transaction Script [[2]](https://martinfowler.com/eaaCatalog/transactionScript.html) – řádný vzor z *Patterns of Enterprise Application Architecture*, který organizuje logiku po procedurách, jednu na požadavek. V doméně s pěti pravidly bývá procedura čitelnější než šest tříd okolo ní. Volba mezi vzory patří k rozhodnutí o typu subdomény, viz [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd#hybrid-subdomain).

Druhou výhradu přináší funkcionální škola. Mark Seemann ukazuje, že zapouzdření není totéž co metoda na objektu: stejnou garanci dá typ, který nelze zkonstruovat do neplatného stavu, plus modul funkcí nad ním [[3]](https://blog.ploeh.dk/2022/10/24/encapsulation-in-functional-programming/). Data od chování oddělit lze. Co oddělit nelze, je validace od dat – záznam s veřejnými poli, který kdokoli naplní čímkoli, je anémický v tom škodlivém smyslu, i kdyby funkce nad ním byly sebelépe napsané.

**Hranice pravidla.** Anémický model je chyba tehdy, když platíte cenu doménového modelu bez jeho přínosu. Rozhodli jste se pro doménový model? Pak v něm mají být pravidla. Rozhodli jste se pro Transaction Script? Pak žádnou anémii neřešíte, jen to rozhodnutí musíte umět pojmenovat a nevydávat adresář `Domain/` za doménový model.

## 21.03 Anti-vzor: Primitive Obsession (posedlost primitivy) {#primitive-obsession}

Primitive Obsession nastává, když vývojáři používají primitivní datové typy (`string`, `int`, `float`) tam, kam patří hodnotové objekty (Value Objects). Primitiva působí na první pohled přímočaře, ale vedou k závažným problémům.

**Rozpoznávací znak.** Najděte si validaci e-mailu ve svém projektu a spočítejte, na kolika místech stojí. Tři výskyty téhož `filter_var()` nad toutéž hodnotou znamenají, že hodnota chce vlastní typ.

:::callout{type="note"}
### Problémy způsobené Primitive Obsession {#primitive-problemy-heading}

Primitivní `string` může obsahovat jakoukoliv hodnotu; hodnotový objekt `Email` garantuje platnou e-mailovou adresu. Primitivům navíc chybí sémantika – typ `string` neříká nic o tom, co hodnota reprezentuje, zatímco `Email`, `PhoneNumber` nebo `PostalCode` sémantiku nesou. Používání `int` pro všechna ID vede k záměnám: typový systém PHP ani IDE neodhalí prohozené `$orderId` a `$userId`, obojí je jen `int`. A bez hodnotových objektů se validace opakuje na každém místě, kde se s hodnotou pracuje.
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

:::code{language="php" filename="src/Ordering/Domain/ValueObject/Email.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Hodnotové objekty s validací a sémantikou

namespace App\Ordering\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class Email
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" není platná e-mailová adresa.', $value)
            );
        }
    }

    public static function fromUserInput(string $raw): self
    {
        // Normalizace (lowercase, trim) patří sem, ne do konstruktoru.
        return new self(mb_strtolower(trim($raw)));
    }

    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}

enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';
}

// Kanonická podoba Money i s metodou zero() je v kapitole Základní koncepty.
// Zde slouží jen jako protipól k float + string výše.
final readonly class Money
{
    public function __construct(
        public int $amountInCents, // Celé číslo - žádná plovoucí desetinná čárka
        public Currency $currency,
    ) {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException(
                'Money cannot be negative; direction belongs to the operation.'
            );
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException(
                "Cannot add {$this->currency->value} and {$other->currency->value}"
            );
        }

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }
}

// Silně typované identifikátory - záměna je odhalena typovým systémem
final readonly class OrderId
{
    public function __construct(public string $value)
    {
        // Uuid::isValid ze symfony/uid přijímá všechny verze UUID -
        // identifikátory v této knize vznikají přes Uuid::v7(), kterou
        // by regex omezený na verzi 4 odmítl.
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Neplatný formát UUID pro OrderId.');
        }
    }
    public function equals(self $other): bool { return $this->value === $other->value; }
}

final readonly class UserId
{
    public function __construct(public string $value) { /* stejná validace */ }
}

// Nyní typový systém PHP odhalí záměnu:
function processOrder(OrderId $orderId, UserId $userId): void { /* ... */ }

$orderId = new OrderId('018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03');
$userId  = new UserId('02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746');
processOrder($userId, $orderId); // PHP TypeError: Argument #1 must be of type OrderId
:::
:::

`Money` odmítá zápornou částku záměrně. Směr pohybu nese operace, ne částka: dobropis je `refund()`, ne záporná suma, takže se znaménko nemůže cestou ztratit. Plnou definici uvádí [sekce 06.04](/zakladni-koncepty#value-objects); ukázka výše je zkrácená na to, co odlišuje hodnotový objekt od `float` a `string`.

Hodnotový objekt má i svou cenu. Vyplatí se tam, kde hodnota splní alespoň jednu ze tří podmínek: nese vlastní pravidla platnosti (`Email`, `BirthNumber`), má vlastní operace (`Money::add()`), nebo hrozí její záměna s jinou hodnotou téhož primitivního typu (`OrderId` proti `UserId`). Evans totéž říká obráceně – jako hodnotový objekt klasifikujte prvek modelu, u kterého vás zajímají pouze jeho atributy a logika [[6]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf).

**Hranice pravidla.** Pole `note`, `description` nebo `internalComment` žádnou z podmínek nesplňuje. Třída `Note` obalující `string` bez jediného pravidla je přesně ta ceremonie, před kterou varuje [sekce 21.08](#over-engineering). Kritériem tedy není počet primitiv v kódu, ale duplikovaná validace a riziko záměny.

## 21.04 Anti-vzor: Příliš velký agregát (God Aggregate) {#prilis-velky-agregat}

Agregát navrhujeme kolem transakční konzistence – tedy kolem nejmenší skupiny objektů, kterou je třeba měnit společně v jedné transakci. Příliš velký agregát (tzv. „God Aggregate“) sdružuje pod jeden kořen entity a logiku, které k sobě transakčně nepatří. Tím porušuje princip jedné odpovědnosti a způsobuje problémy popsané níže. Vernon pro tentýž jev používá střízlivější název *large-cluster aggregate*; komunita se drží dramatičtějšího „God“.

**Rozpoznávací znak.** Podívejte se na poslední přidání položky do kolekce uvnitř agregátu. Pokud kvůli jednomu novému řádku načítáte tisíc existujících, je hranice agregátu vedená podle asociací, ne podle invariantů.

:::diagram{fig="21.4-A" title="God Aggregate vs. správně rozdělené agregáty propojené přes ID" src="images/diagrams/22_anti_patterns/god_aggregate.svg"}
:::

:::callout{type="note"}
### Problémy způsobené příliš velkým agregátem {#agregat-problemy-heading}

- **Výkonnostní problémy** – načtení celého agregátu z databáze je pomalé, pokud obsahuje stovky nebo tisíce podřízených entit (např. všechny položky objednávky zákazníka za celý rok).
- **Problémy s konkurencí (concurrency)** – agregát je zamčen jako celek při každé změně. Velký agregát znamená větší pravděpodobnost konfliktů při souběžném přístupu.
- **Těsné provázání (tight coupling)** – příliš mnoho entit uvnitř jednoho agregátu ztěžuje nezávislý vývoj a testování.

A nakonec hranice. God agregát bývá příznakem špatně definovaných hranic kontextů – tam, kde do jednoho celku spadne víc, než kam sahá jeden Bounded Context.
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

:::code{language="php" filename="src/Ordering/Domain/Model/Customer.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Malé agregáty s jednoznačnou odpovědností

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\ProductId;
use App\Ordering\Domain\ValueObject\Address;
use App\Ordering\Domain\ValueObject\OrderStatus;
use App\SharedKernel\Domain\Money;
use App\Ordering\Domain\ValueObject\Email;
use App\Ordering\Domain\ValueObject\WishlistId;
use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Exception\EmptyOrderException;
use App\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\SharedKernel\Domain\AggregateRoot;

// Agregát 1: Customer - pouze identita a kontaktní údaje
final class Customer
{
    private readonly CustomerId $id;
    private string $name;
    private Email $email;

    // Zákazník obsahuje jen to, co je součástí jeho identity.
    // Adresa pro doručení je součástí objednávky, ne zákazníka.

    public function changeEmail(Email $newEmail): void
    {
        if ($this->email->equals($newEmail)) {
            return; // Idempotentní změna, žádný přechod stavu
        }
        $this->email = $newEmail;
    }
}

// Agregát 2: Order - transakční hranice pro jednu objednávku
final class Order extends AggregateRoot
{
    private readonly OrderId $id;
    private readonly CustomerId $customerId; // Pouze reference - ne celý Customer objekt!
    private Address $shippingAddress;
    private OrderStatus $status;

    /** @var OrderItem[] */
    private array $items = [];
    private readonly \DateTimeImmutable $placedAt;

    private function __construct(
        OrderId $id,
        CustomerId $customerId,
        Address $shippingAddress
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->shippingAddress = $shippingAddress;
        $this->status = OrderStatus::Draft;
        $this->placedAt = new \DateTimeImmutable();
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException(
                'Položky lze přidat pouze k objednávce ve stavu Draft.'
            );
        }
        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
    }

    public function confirm(): void
    {
        if ($this->items === []) {
            throw new EmptyOrderException();
        }
        $this->status = OrderStatus::Confirmed;
        $this->record(new OrderConfirmed(
            $this->id,
            $this->customerId,
            $this->totalAmount(),
            count($this->items),
        ));
    }

    public function totalAmount(): Money
    {
        // Měna plyne z položek objednávky, ne z konstanty zapsané v agregátu.
        // Pokud se položky v měně rozejdou, ohlásí to Money::add().
        $rest = $this->items;
        $first = array_shift($rest);
        if ($first === null) {
            throw new EmptyOrderException();
        }

        return array_reduce(
            $rest,
            fn(Money $carry, OrderItem $item) => $carry->add($item->subtotal()),
            $first->subtotal(),
        );
    }
}

// Agregát 3: Wishlist - zcela oddělená doménová odpovědnost
final class Wishlist
{
    private readonly WishlistId $id;
    private readonly CustomerId $customerId;
    /** @var WishlistItem[] */
    private array $items = [];

    public function add(ProductId $productId): void
    {
        foreach ($this->items as $item) {
            if ($item->productId()->equals($productId)) {
                return; // Invariant: každý produkt je v seznamu nejvýš jednou
            }
        }
        $this->items[] = new WishlistItem($productId);
    }
}
:::
:::

Pravidlo pochází z Vernonovy série *Effective Aggregate Design* [[7]](https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_1.pdf): agregát drží kořen a nezbytné minimum atributů a hodnotových vlastností, nic víc. Vernon k němu dodává větu, kterou katalogy anti-vzorů obvykle vynechávají – agregáty jsou hranice konzistence, ne výsledek snahy navrhnout graf objektů. Pokud změna jednoho objektu nevyžaduje konzistentní změnu druhého ve stejné transakci, patří do různých agregátů.

**Hranice pravidla.** Zmenšovat lze i příliš. Vernon pojmenovává obě selhání: agregát složený pro pohodlí kompozice je moc velký, agregát rozebraný na jednotlivé entity zase přestane chránit skutečné invarianty. Druhá chyba se hledá hůř, protože se neprojeví na výkonu, ale až nekonzistentními daty.

V Doctrine bývá nejčastější příčinou velkého agregátu samotné mapování. Asociace `OneToMany` popisuje vztah v databázi, ne transakční hranici: z toho, že objednávka *má* položky, neplyne, že zákazník má vlastnit svou historii objednávek. Vodítkem je invariant, který musí platit po každém commitu, nikoli tvar schématu.

## 21.05 Anti-vzor: Sdílená databáze napříč Bounded Contexts {#sdilena-databaze}

Sdílená databáze napříč Bounded Contexts patří mezi nejzávažnější strategické anti-vzory. Nastává, když různé kontexty sdílejí stejné databázové tabulky nebo přistupují přímo k datům jiného kontextu. Na počátku to vypadá pragmaticky, ale vede k těsnému provázání, které blokuje nezávislý vývoj a nasazení jednotlivých kontextů.

**Rozpoznávací znak.** Projděte migrace jednoho kontextu a hledejte tabulku, kterou vlastní jiný tým. Druhý příznak je provozní: nasazení kontextu A vyžaduje koordinaci s týmem kontextu B, přestože se jejich kód nikde nepotkává.

**Hranice pravidla.** Chybou není jedna databázová instance, ale sdílené schéma a dotaz vedený přes hranici. Modulární monolit běžně běží nad jednou databází s oddělenými schématy a vlastnictvím tabulek na úrovni modulu, a to je v pořádku. Legitimní zůstávají i další případy: Shared Kernel s explicitně dohodnutým vlastníkem, read-only replika pro reporting a analytický kontext, který čte data mimo doménový model. Anti-vzor začíná ve chvíli, kdy jeden kontext čte zápisový model druhého a spoléhá se na jeho tvar.

:::callout{type="warn"}
### Špatně: Přímý přístup ke sdíleným tabulkám {#sdilena-db-spatne-heading}

Kontexty *Ordering* a *Billing* přímo přistupují ke stejné tabulce `users`. Změna schématu tabulky v jednom kontextu okamžitě ovlivní druhý.
:::

:::callout{type="anti"}
### Příklad: Sdílená databáze (špatně)

:::code{language="php" filename="src/Ordering/Infrastructure/Repository/DoctrineOrderRepository.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Ordering context přímo dotazuje tabulku users z UserManagement kontextu

namespace App\Ordering\Infrastructure\Repository;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;
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
            ['id' => $customerId->value]
        )->fetchAllAssociative();
    }
}

// Billing context dělá totéž:
namespace App\Billing\Infrastructure;

class InvoiceGenerator
{
    public function __construct(private Connection $db) {}

    public function generate(OrderId $orderId): Invoice
    {
        // Opět přímý přístup k tabulce orders z Ordering kontextu!
        $data = $this->db->executeQuery(
            'SELECT o.total, u.billing_address, u.vat_number
             FROM orders o JOIN users u ON o.user_id = u.id
             WHERE o.id = :id',
            ['id' => $orderId->value]
        )->fetchAssociative();
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

// Ordering kontext si ukládá pouze to, co potřebuje pro svou logiku.
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
            "/internal/users/{$customerId->value}/billing"
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

Synchronní HTTP adaptér z ukázky výše není jediná možnost a pro [modulární monolit](/ddd-a-microservices) bývá tou nejdražší. V úvahu připadají tři cesty a každá má svou cenu.

1. **Volání přes rozhraní v procesu.** Kontext B vystaví port, kontext A ho volá přímo, bez sítě. Hranice zůstane zachovaná, latence žádná nepřibude. Cenou je společné nasazení a disciplína, aby se z portu nestal průchod do cizího modelu.
2. **Synchronní HTTP nebo gRPC.** Nutnost, jakmile kontexty běží odděleně. Zaplatíte latencí, nedostupností upstreamu ve chvíli vlastního provozu a nutností řešit timeouty i náhradní chování.
3. **Asynchronní replikace přes události.** Billing naslouchá události `CustomerBillingDataUpdated` a drží si lokální kopii potřebných dat (*read model projection*). Synchronní závislost mizí a čtení má ze všech tří variant nejnižší latenci. Cenou je eventuální konzistence a kód pro doplnění dat konzumentovi, který se připojí později. Spolehlivé publikování řeší [Outbox Pattern](/outbox-pattern).

## 21.06 Anti-vzor: Mutovatelné doménové události {#mutovatelne-udalosti}

Doménová událost popisuje fakt, který se v minulosti stal. Evans ji proto označuje za zpravidla **neměnnou** (immutable), protože jde o záznam něčeho minulého [[6]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf). Ono „zpravidla“ je na místě: doplnit metadata při publikování zprávy je běžné, změnit částku v `OrderPlaced` je konceptuální rozpor. Událost, kterou lze po vytvoření přepsat, ztrácí hodnotu historického záznamu.

Mutovatelné události navíc způsobují praktické problémy při event sourcingu, auditních logách a při komunikaci mezi Bounded Contexts. Přijímající kontext totiž předpokládá, že obdrží konzistentní a neměnná data.

**Rozpoznávací znak.** Otevřete třídu události a hledejte setter nebo `\DateTime` bez `Immutable`. Obojí znamená, že minulost lze v tomto systému přepsat.

Praxe pracuje se dvěma časovými razítky. `occurredAt` říká, kdy se věc stala v doméně; `recordedAt`, kdy ji systém zapsal. U události vzniklé z uživatelské akce obě hodnoty splývají, u importu historických dat nebo u zpětného storna se rozejdou třeba o týdny. Jedno razítko generované v konstruktoru na takový případ nestačí.

:::callout{type="warn"}
### Špatně: Mutovatelná událost s veřejnými settery {#udalosti-spatne-heading}

Veřejné settery a chybějící `readonly` semantika umožňují modifikaci události po jejím vzniku, čímž narušují integritu historického záznamu.
:::

:::callout{type="anti"}
### Příklad: Mutovatelná událost (špatně)

:::code{language="php" filename="src/OrderPlaced.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Mutovatelná doménová událost

class OrderPlaced
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

Správná doménová událost je vytvořena jednou, nastavena v konstruktoru a poté nelze žádnou její vlastnost změnit. `readonly` properties jsou pro to přesně určeným nástrojem.
:::

:::callout{type="pattern"}
### Příklad: Immutable doménová událost (správně)

:::code{language="php" filename="src/Ordering/Domain/Event/OrderPlaced.php"}
<?php

declare(strict_types=1);

// SPRÁVNĚ: Immutable doménová událost s readonly properties

namespace App\Ordering\Domain\Event;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\SharedKernel\Domain\Money;
use App\Ordering\Domain\ValueObject\OrderId;

final readonly class OrderPlaced
{
    public \DateTimeImmutable $recordedAt;

    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public Money $totalAmount,
        public int $itemCount,
        // Kdy se to stalo v doméně. U běžné akce je to teď, u importu historie ne.
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
        // Kdy to zapsal systém - údaj patří infrastruktuře, ne doméně.
        $this->recordedAt = new \DateTimeImmutable();
        // Všechny hodnoty jsou nastaveny jednou v konstruktoru.
        // Neexistují žádné settery - událost je neměnná.
        // Čte se přímo přes readonly properties ($event->orderId), accessory nejsou potřeba.
    }
}
:::
:::

Neměnnost instance přitom neřeší verzování schématu. Jakmile událost přežije nasazení, které jí přidá pole, potřebujete upcasting nebo verzovaný název typu; obojí rozebírá kapitola [Event Sourcing](/event-sourcing).

**Příbuzný anti-vzor: událost jako aplikační hook.** Názvy `CacheShouldBeInvalidated` nebo `EmailNeedsToBeSent` nepopisují fakt, ale příkaz převlečený do minulého času. Verraes třídí zprávy na příkazy, dotazy a informace [[8]](https://verraes.net/2015/01/messaging-flavours/) a záměna kategorií je jádrem problému. Doménová událost říká, co se v doméně stalo, a nezajímá se, kdo na ni zareaguje. Jakmile její jméno obsahuje instrukci pro infrastrukturu, jde o příkaz, ne o událost.

## 21.07 Anti-vzor: Doménová logika v infrastrukturní vrstvě {#logika-v-infrastrukture}

DDD odděluje doménovou vrstvu od infrastrukturní. Infrastrukturní vrstva (Doctrine repozitáře, Symfony Forms, kontrolery, event listenery) má být tenká a delegovat veškerou doménovou logiku do doménové vrstvy. Doménová pravidla v infrastrukturních třídách narušují hranice vrstev a vytvářejí skrytou, těžko testovatelnou logiku.

**Rozpoznávací znak.** Otevřete libovolnou třídu v adresáři `Infrastructure/` a hledejte podmínku, která se ptá na doménový stav. Řádek `if ($user->getStatus() !== 'pending')` v repozitáři je pravidlo, ne persistence.

:::callout{type="warn"}
### Špatně: Doménová logika v Doctrine repozitáři {#infra-spatne-heading}

Repozitář má agregáty pouze ukládat a načítat. Jakákoliv doménová logika (výpočty, aplikace doménových pravidel, stavové přechody) v repozitáři je anti-vzor. Ukázka níže vychází z třídy, kterou vygeneruje MakerBundle: `ServiceEntityRepository` je v Symfony výchozí volba, a tak do ní pravidla zabloudí nejčastěji. Doctrine ORM 3 navíc vyžaduje, aby repozitář registrovaný přes `repositoryClass` dědil od `EntityRepository` – tím spíš se vyplatí doménové rozhraní od Doctrine oddělit, jak ukazuje následující dvojice.
:::

:::callout{type="anti"}
### Příklad: Doménová logika v repozitáři a kontroleru (špatně)

:::code{language="php" filename="src/UserManagement/Infrastructure/Repository/DoctrineUserRepository.php"}
<?php

declare(strict_types=1);

// ŠPATNĚ: Doménová logika v Doctrine repozitáři

namespace App\UserManagement\Infrastructure\Repository;

use App\UserManagement\Domain\Model\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
use App\UserManagement\Domain\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserRepository implements UserRepository
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(User $user): void
    {
        $this->em->persist($user);
        // Flush je řízen aplikační vrstvou (Unit of Work), ne repozitářem
    }

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id->value);
    }
}

// SPRÁVNĚ: Aplikační vrstva (Command Handler) orkestruje, doména rozhoduje
namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\Exception\UserNotFoundException;
use App\UserManagement\Domain\ValueObject\VerificationToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ActivateUserHandler
{
    public function __construct(
        private readonly UserRepository $users,
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

        foreach ($user->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}

// SPRÁVNĚ: Tenký Symfony kontroler
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    public function __construct(private readonly MessageBusInterface $commandBus) {}

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

Handler odesílá doménové události rovnou na `MessageBusInterface`, a právě zde vede hranice, kterou lze přehlédnout. Doménová událost je vnitřní věc kontextu, integrační událost je veřejný kontrakt vůči okolí. Jakmile obojí sdílí jednu sběrnici, kdokoli si na doménovou událost pověsí handler a její tvar se tím stane veřejným API, které už nelze měnit. Oddělení obou vrstev i spolehlivé publikování ven rozebírá kapitola [Outbox Pattern](/outbox-pattern).

Hranice vrstev se navíc dají vynutit nástrojem, ne jen dohodou v code review. Nástroje `deptrac` [[9]](https://packagist.org/packages/deptrac/deptrac) a PHPArkitect [[10]](https://packagist.org/packages/phparkitect/phparkitect) čtou statickou strukturu kódu a v CI zastaví build, který ji poruší. Užitečné minimum je jediné pravidlo: `App\*\Domain` nesmí odkazovat na `Doctrine\*` ani `Symfony\*`. Jeden řádek konfigurace nahradí opakovanou diskusi u každého pull requestu. Pozor jen na název balíčku, původní `qossmic/deptrac` je opuštěný ve prospěch `deptrac/deptrac`.

## 21.08 Anti-vzor: Over-engineering u jednoduchých aplikací {#over-engineering}

Anti-vzorem zde není samotné DDD, ale jeho ceremonie bez komplexní domény. Agregáty, Value Objects a doménové události obalují prosté řádky v databázi, pro které stačí formulář a tabulka. Typické příznaky: tým tráví více času architekturou než obchodní hodnotou a triviální změna prochází desítkami souborů napříč vrstvami.

Méně nákladná cesta začíná minimálním přístupem a přidává DDD prvky, až když se doménová složitost skutečně projeví. Celý rozhodovací rámec – sedm situací, kdy DDD vynechat, alternativy a rozhodovací strom – rozebírá kapitola [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

**Hranice pravidla.** Ceremonie sama o sobě chyba není. V jádrové subdoméně je to investice, která se vrátí při každé změně pravidel. Chybou je stejná ceremonie v podpůrné subdoméně, kde se za rok nezmění nic než sazba DPH.

## 21.09 Anti-vzor: Ignorování Ubiquitous Language {#missing-ubiquitous-language}

Když selže Ubiquitous Language, tatáž doménová entita nese různé názvy na různých místech. Společný jazyk vývojářů a doménových expertů přestane platit a vývojář víc překládá mezi vrstvami, než modeluje doménu. Výsledkem jsou nedorozumění, chyby a ztráta doménového vhledu v kódu.

**Rozpoznávací znak.** Nechte doménového experta popsat jeden běžný případ a poznamenejte si každé slovo, které v kódu nenajdete nebo které tam znamená něco jiného. Délka seznamu je mírou driftu.

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
// Pozn.: koláž ukázek z různých míst projektu, ne jeden skutečný soubor

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

Ubiquitous Language vyžaduje investici: vývojáři musí naslouchat doménovým expertům, porozumět jejich terminologii a tu pak konzistentně přenést do kódu. Výsledný kód pak doménový expert přečte a rozumí mu.
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

Glosář mapuje pojmy doménového jazyka na třídy, metody a databázové struktury. V pojišťovací doméně vypadá takto: **Pojistník** → `PolicyHolder` a tabulka `policy_holders`, **Pojistná smlouva** → `InsurancePolicy` a `insurance_policies`, **Pojistná událost** → `InsuredEvent` a událost `InsuredEventOccurred`, **Pojistné plnění** → `Claim` a `claims`.

Jazyk nekončí u jmen tříd. Patří do něj i metody, sloupce v databázi, API endpointy, chybové hlášky a názvy testů. Jak takový slovník udržet živý a jaké další praktiky brání driftu jazyka, rozebírá sekce [Ubiquitous Language drift](/ddd-v-praxi-kde-to-boli#c4-language).
:::

## 21.10 Shrnutí: anti-vzor, znak, alternativa {#shrnuti}

| Anti-vzor | Podle čeho ho poznáte | Realistická alternativa | Víc v knize |
|---|---|---|---|
| Anémický model | Entita má jen gettery a settery, pravidlo nad nimi žije v cizí třídě | Pravidla do entity, nebo přiznaný Transaction Script v jednoduché subdoméně | [22.09](/kdy-nepouzivat-ddd#hybrid-subdomain) |
| Primitive Obsession | Tatáž validace téže hodnoty na třech místech | Hodnotový objekt tam, kde má hodnota pravidla, operace nebo hrozí záměna | [06.04](/zakladni-koncepty#value-objects) |
| Příliš velký agregát | Kolekci načítáte jen kvůli přidání jedné položky | Rozdělit podle invariantů, reference přes ID | [07.04](/navrh-agregatu#aggregate-size) |
| Sdílená databáze | Migrace jednoho kontextu sahá na tabulku jiného týmu | Port a adaptér, replikace přes události, oddělená schémata v monolitu | [03](/context-mapping) |
| Mutovatelná událost | Událost má setter nebo mutovatelný `DateTime` | `readonly` vlastnosti, `occurredAt` i `recordedAt` | [15](/outbox-pattern) |
| Logika v infrastruktuře | Podmínka nad doménovým stavem v adresáři `Infrastructure/` | Pravidlo do entity, orchestrace do handleru, hranice do CI | [20.01](/ddd-v-praxi-kde-to-boli#doctrine) |
| Over-engineering | Triviální změna prochází desítkami souborů | Míru DDD volit podle typu subdomény | [22](/kdy-nepouzivat-ddd) |
| Drift jazyka | Expert použije slovo, které v kódu není | Glosář v repozitáři, revize jmen u každé nové funkce | [20.03](/ddd-v-praxi-kde-to-boli#modelovani) |

Tabulka má jedno společné čtení. Žádný z uvedených anti-vzorů nevzniká z neznalosti vzorů, ale z pohodlí: každý je krok, který v daném týdnu ušetří práci a účet za něj přijde o rok později. Proto je užitečnější znát rozpoznávací znak než definici.

Anémickému doménovému modelu se obšírně věnuje Vaughn Vernon v *Implementing Domain-Driven Design* (2013), odkud pochází i test dvou otázek z [úvodu sekce 21.02](#anemicky-domenovy-model). Další tituly uvádějí [doporučené zdroje](/zdroje).

:::faq{}
- question: Co je anémický doménový model a jak ho poznat?
  answer: 'Anémický model vypadá na první pohled jako DDD – obsahuje třídy s názvy agregátů, entit a hodnotových objektů. Veškerá logika je ale přesunutá do služeb. Typickým znakem jsou gettery a settery jako jediné metody a třídy bez jakéhokoli pravidla uvnitř. Doménová logika končí ve „Service“ třídách, které manipulují s daty zvenku. Výsledkem je procedurální kód balený do objektových fasád. Detailní rozbor v <a href="#anemicky-domenovy-model">sekci Anémický doménový model</a>.'
- question: Je anémický model vždy chyba?
  answer: 'Ne. Fowler sám v článku o anémickém modelu píše, že doménový model není vždy nejlepší nástroj, a odkazuje na Transaction Script. V doméně s několika málo pravidly je procedura na jeden případ užití čitelnější než vrstva tříd okolo ní. Anémický model je chyba tehdy, když platíte cenu doménového modelu – mapování, obalování hodnot, rozpad do vrstev – a nedostáváte za ni žádný přínos. Rozbor obou stran sporu v <a href="#anemicky-kdy-nevadi">sekci Kdy anémický model chyba není</a>.'
- question: Proč je Primitive Obsession problém?
  answer: 'Primitive Obsession znamená používání primitivních typů (<code>string</code>, <code>int</code>, <code>float</code>) tam, kde patří doménový pojem. Místo typu <code>Email</code> se předává <code>string</code>, místo <code>Money</code> dvojice <code>float</code>. Důsledkem je, že validace a pravidla se opakují v každém místě volání, nebo se zapomínají. Hodnotový objekt s jedním místem validace tyto duplicity odstraňuje a typ dává kontext, co daná hodnota reprezentuje. Rozbor a příklady v <a href="#primitive-obsession">sekci Primitive Obsession</a>.'
- question: Jak poznat, že je agregát příliš velký?
  answer: 'Typické příznaky God Aggregate jsou tři. Agregát obsahuje desítky vnitřních entit. Jeho načtení zabere stovky SQL dotazů. Nebo souběžné operace nad různými částmi narážejí na optimistické zamykání. Pokud dvě metody agregátu řeší vzájemně nezávislá pravidla a nesdílejí invariant, pravděpodobně jde o dva samostatné agregáty. Hranice agregátu má kopírovat hranice transakční konzistence – nic víc. Praktický příklad refaktoringu v <a href="#prilis-velky-agregat">sekci Příliš velký agregát</a>.'
- question: Proč je sdílená databáze mezi Bounded Contexts problém?
  answer: 'Sdílená databáze formálně drží data pohromadě, ale fakticky ruší hranice mezi Bounded Contexts. Změna schématu v jednom kontextu může rozbít druhý, pojmy se mísí a model jednoho týmu začíná záviset na modelu druhého. Správné řešení je, aby každý Bounded Context vlastnil svá data a komunikace probíhala přes definované rozhraní (API, události), nikoli přes sdílenou tabulku. Podrobný rozbor v <a href="#sdilena-databaze">sekci Sdílená databáze napříč Bounded Contexts</a>.'
- question: Musí být doménová událost neměnná?
  answer: 'Ano. Doménová událost popisuje něco, co se již stalo – <code>OrderPlaced</code>, <code>PaymentReceived</code> – a minulost nelze měnit. Událost bez setterů, s neměnnými atributy a časovým razítkem vytvořeným při konstrukci je bezpečné sdílet mezi handlery, persistovat v event store a použít pro zpětnou rekonstrukci stavu. Mutovatelná událost vede k race condition, nedeterministickému zpracování a nekonzistentnímu auditu. Viz <a href="#mutovatelne-udalosti">sekci Mutovatelné doménové události</a>.'
:::
