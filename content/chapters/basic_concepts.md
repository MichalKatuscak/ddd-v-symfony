---
route: basic_concepts
path: /zakladni-koncepty
title: Základní koncepty DDD
page_title: "Základní koncepty Domain-Driven Design | DDD Symfony"
meta_description: "Základní stavební kameny taktického DDD: entity, hodnotové objekty, agregáty, repozitáře, doménové události a služby – s ukázkami v PHP 8.4+ a Symfony 8."
meta_keywords: "DDD koncepty, entity, hodnotové objekty, value objects, kořeny agregátů, aggregate roots, doménové služby, repozitáře, doménové události, Symfony implementace"
og_type: article
published: "2025-04-24"
modified: 2026-09-06
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
[[1]](https://martinfowler.com/bliki/BoundedContext.html). Různé kontexty proto mají
různé modely, a to záměrně. Jde o strategické téma. Celkový rámec podává kapitola
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
doménový objekt, který nese vlastní identifikátor a zachovává si ho po celý život.
Evans v *DDD Reference* mluví o objektech, jež drží nit kontinuity a identity napříč
celým životním cyklem [[3]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf).
Jméno, adresa i e-mail se přitom mohou měnit – identita zůstává.

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php (bez perzistence)"}
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

`User` je v ukázce entita, jejíž identitu určuje `UserId`. Uživatel může změnit jméno
i e-mail, identifikátor zůstává stejný.

### Rovnost entit {#entity-equality}

Dvě entity jsou totožné právě tehdy, když mají stejné ID. Proto `equals()`
porovnává výhradně identifikátory. Porovnání operátorem `==` se nehodí, protože
srovnává všechny vlastnosti najednou. Tentýž uživatel načtený dvakrát z databáze
sice projde, ale jakmile jedna z instancí změní e-mail, `==` ji označí za jinou
entitu – identita se přitom nezměnila. Operátor `===` zase porovnává
identitu instance v paměti. Stejný agregát načtený ve dvou různých kontextech
(dva requesty, deserializace ze zprávy) existuje jako dvě instance. `===` proto
vrátí `false`, i když jde o tutéž doménovou entitu.

### Vznik identity {#entity-identity}

Ukázka `User` dostane `UserId` konstruktorem a neřeší, odkud se vzal. Vernon
v *Implementing Domain-Driven Design* (2013) vypisuje čtyři cesty, kterými identita
vzniká: hodnotu dodá uživatel (User Provides Identity), vygeneruje ji aplikace
(Application Generates Identity), vygeneruje ji persistence (Persistence Mechanism
Generates Identity), nebo ji přiřadí jiný ohraničený kontext (Another Bounded Context
Assigns Identity) [[4]](https://www.informit.com/store/implementing-domain-driven-design-9780321834577).

Tato kniha volí druhou z nich. Důvod je praktický: agregát, který identifikátor dostane
až od databáze, ho při vzniku nemá k dispozici. Nemůže tedy zaznamenat událost o svém
vzniku ani se na sebe odkázat z jiné agregátní hranice. Matthias Noback dochází ke
stejnému závěru a doporučuje ID vytvořit dřív, než objekt vznikne
[[5]](https://matthiasnoback.nl/2018/05/when-and-where-to-determine-the-id-of-an-entity/).
Identifikátory v této knize proto vznikají přes `Uuid::v7()` z balíčku `symfony/uid`;
dokumentace tuto verzi doporučuje kvůli lepší entropii a chronologickému řazení
[[6]](https://symfony.com/doc/current/components/uid.html).

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/UserId.php (základní podoba)"}
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
            throw new \InvalidArgumentException('UserId must be a valid UUID');
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

    // Doctrine převádí identitu na řetězec při každém persist().
    // Bez __toString() spadne už uložení – viz kapitola o implementaci.
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

Ostatní identifikátory v knize mají stejný tvar a liší se jen jménem a chybovou hláškou.
Kniha je používá průběžně, proto je uvádíme pohromadě:

:::code{language="php" filename="src/Ordering/Domain/ValueObject/OrderId.php + CustomerId.php + ProductId.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class CustomerId
{
    public function __construct(public string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('CustomerId must be a valid UUID');
        }
    }

    public static function generate(): self { return new self((string) Uuid::v7()); }

    public static function fromString(string $value): self { return new self($value); }

    public function equals(self $other): bool { return $this->value === $other->value; }
}

final readonly class ProductId
{
    public function __construct(public string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('ProductId must be a valid UUID');
        }
    }

    public static function generate(): self { return new self((string) Uuid::v7()); }

    public static function fromString(string $value): self { return new self($value); }

    public function equals(self $other): bool { return $this->value === $other->value; }
}
:::

Opakování je záměrné. Sdílený předek by sice ušetřil řádky, ale zároveň by dovolil předat
`ProductId` tam, kde se čeká `CustomerId` – a právě tomu mají typované identifikátory
zabránit. `OrderId` má identický tvar, plnou verzi ukazuje kapitola
[Návrh agregátu](/navrh-agregatu#references-by-id).

Přirozený identifikátor je legitimní alternativa. Evans obě možnosti výslovně připouští:
identita může přijít zvenčí, nebo jde o umělou hodnotu vytvořenou systémem pro systém
[[3]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf).
Rodné číslo, ISBN i IČO se ovšem mění a recyklují – kdo je použije jako primární identitu
agregátu, zdědí všechny výjimky, které k nim patří. Bezpečnější je držet umělé ID
a přirozený klíč vést jako běžný atribut s unikátním indexem.

## 06.04 Hodnotové objekty (Value Objects) {#value-objects}

Dva e-maily se stejným textem nejsou „dvě adresy“ – je to jedna a tatáž hodnota.
Hodnotový objekt je doménový pojem, který identifikuje sám sebe celou svou hodnotou,
ne odděleným ID [[3]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf).
Z toho plynou dvě vlastnosti: neměnnost (immutable) a rovnost po hodnotě, ne po referenci.
Druhý důvod pro hodnotové objekty je pragmatický. Rozsypaná primitiva `string $email`,
`int $priceInCents` a `string $currency` jsou code smell, který Fowler pojmenoval
Primitive Obsession
[[7]](https://martinfowler.com/books/refactoring.html); ukázky před opravou a po ní má
kapitola [Anti-vzory a typické chyby](/anti-vzory#primitive-obsession).

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/Email.php (základní podoba)"}
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

`Email` v ukázce drží jediný řetězec jako `public readonly` vlastnost, protože
getter by jen přidával šum. Formát hlídá konstruktor, normalizaci vstupu
z formulářů obstará pojmenovaná factory `fromUserInput()`. Žádné ID, žádné
settery: dva e-maily se shodují právě tehdy, když mají stejnou hodnotu. Třída je
`final readonly`, takže hodnotový objekt nikdo nedědí ani nemění po vytvoření.

:::callout{type="note"}
### Co `readonly` stojí {#readonly-cost-heading}

`readonly` vlastnost přijme hodnotu jen jednou a jen z rozsahu deklarující třídy.
U `Email` s jediným polem to nevadí. U objektu s pěti poli to znamená, že každá
metoda typu `withCurrency()` musí vypsat `new self(...)` se všemi poli. PHP 8.3
povolilo reinicializaci uvnitř `__clone()`
[[8]](https://wiki.php.net/rfc/readonly_amendments), PHP 8.5 přidalo `clone with`;
kniha cílí na PHP 8.4, takže druhá možnost je zatím poznámka na okraj. Tvrdší je
druhé omezení. Property hooks jsou s `readonly` neslučitelné, jak manuál říká přímo
[[9]](https://www.php.net/manual/en/language.oop5.property-hooks.php). Validace
v hooku a `readonly` se tedy vylučují a tato kniha volí `readonly`.
:::

### Money a Currency {#money}

`Email` drží jedinou hodnotu. Druhý hodnotový objekt, se kterým kniha pracuje napříč
kapitolami, jich skládá víc. `Money` spojuje částku a měnu do pojmu, který nejde
rozpojit.

:::code{language="php" filename="src/SharedKernel/Domain/Money.php + Currency.php"}
<?php

declare(strict_types=1);

// Peníze používá Ordering, Billing i Pricing – proto Shared Kernel,
// stejně jako AggregateRoot, ne doménová složka jednoho kontextu.
namespace App\SharedKernel\Domain;

enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';
}

final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public Currency $currency,
    ) {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative');
        }
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
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

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException(
                "Cannot subtract {$other->currency->value} from {$this->currency->value}"
            );
        }

        return new self($this->amountInCents - $other->amountInCents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amountInCents * $factor, $this->currency);
    }

    /** Procentní podíl. Sazby jsou celá procenta, dělení zaokrouhluje nahoru. */
    public function percentage(int $percent): self
    {
        return new self(intdiv($this->amountInCents * $percent + 99, 100), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }
}
:::

Částka je celé číslo v haléřích. `float` by do peněz vnesl chyby zaokrouhlení, které
se projeví až na faktuře. Měnu drží string-backed enum, takže záměna `'czk'` za `'CZK'`
nepřipadá v úvahu. Sčítání dvou různých měn skončí výjimkou, což je doménové pravidlo,
ne chyba volajícího. Jakmile tentýž pojem potřebuje víc kontextů, patří `Money` do
Shared Kernelu (tuto variantu ukazuje [Context Mapping](/context-mapping#shared-kernel)).

### Validace: kde jaká výjimka {#vo-validation}

Konvence této knihy rozlišuje dvě úrovně validace. Porušení *formátu* hodnoty
(neplatný e-mail, záporná částka, řetězec, který není UUID) hlásí konstruktor
hodnotového objektu výjimkou `\InvalidArgumentException`. Takové porušení je
programátorská chyba nebo nevalidní vstup, který měla zachytit už vstupní vrstva.
Porušení *byznys pravidla* (potvrzení prázdné objednávky, platba nepotvrzené
objednávky) hlásí agregát doménovou výjimkou dědící z `\DomainException`,
typicky pojmenovanou třídou jako `InvalidOrderStateTransitionException`.
Hierarchii výjimek po vrstvách rozebírá kapitola
[Implementace v Symfony 8](/implementace-v-symfony#error-handling).

Obě pravidla stojí na jedné pozici: objekt se nesmí ocitnout v nevalidním stavu ani
na okamžik. Vladimir Khorikov ji nazývá always-valid domain model
[[10]](https://enterprisecraftsmanship.com/posts/always-valid-domain-model/).
Jde o volbu, ne o samozřejmost – protipól posouvá validaci do vstupní vrstvy
a doménový objekt nechává „hloupý“. Kniha drží první variantu, protože jen tak je
konstruktor zárukou platnosti.

## 06.05 Agregáty (Aggregates) {#aggregates}

Objednávka má položky, dodací adresu, stav a celkovou částku. Změnit položku znamená
přepočítat částku; zrušit objednávku znamená překontrolovat stav. Pokud tato pravidla
nepatří jednomu strážci, rozsypou se. Agregát je právě tento strážce, tedy skupina
objektů, které se mění jako jeden celek a sdílejí jednu hranici invariantů
[[3]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf).
Vstup do agregátu vede výhradně přes kořen (Aggregate Root). Ztotožnění této hranice
s hranicí transakce je Evansovo doporučení, ne součást definice. Uvnitř agregátu se
pravidla vynucují synchronně, přes hranici se změny šíří asynchronně. Pravidlo „jeden
agregát na transakci“ z toho odvozuje kapitola
[Návrh agregátu](/navrh-agregatu#transactional-consistency). Špatně zvolená velikost
patří mezi nejčastější chyby v DDD; přerostlé „God Aggregates“ rozebírá kapitola
[Anti-vzory a typické chyby](/anti-vzory).

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (bez perzistence)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\Exception\EmptyOrderException;
use App\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\SharedKernel\Domain\Money;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\ProductId;
use App\Ordering\Domain\ValueObject\OrderStatus;

class Order
{
    /** @var list<OrderItem> */
    private array $items = [];

    private OrderStatus $status;
    private readonly \DateTimeImmutable $createdAt;

    private function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
    ) {
        $this->status = OrderStatus::Draft;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        return new self($id, $customerId);
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw InvalidOrderStateTransitionException::notAllowedInState(
                'přidání položky',
                $this->status->value,
            );
        }

        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
    }

    public function removeItem(ProductId $productId): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Cannot remove items from a draft order only');
        }

        $this->items = array_values(array_filter(
            $this->items,
            static fn (OrderItem $item): bool => !$item->productId()->equals($productId),
        ));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Cannot confirm a non-created order');
        }

        if ($this->items === []) {
            throw EmptyOrderException::cannotConfirm();
        }

        $this->status = OrderStatus::Confirmed;
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::Draft && $this->status !== OrderStatus::Confirmed) {
            throw new InvalidOrderStateTransitionException('Cannot cancel a shipped or cancelled order');
        }

        $this->status = OrderStatus::Cancelled;
    }

    public function totalAmount(): Money
    {
        if ($this->items === []) {
            throw EmptyOrderException::cannotBePlaced();
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

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::Confirmed;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
:::

:::code{language="php" filename="src/Ordering/Domain/Model/OrderItem.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\SharedKernel\Domain\Money;
use App\Ordering\Domain\ValueObject\ProductId;

class OrderItem
{
    public function __construct(
        private readonly ProductId $productId,
        private readonly int $quantity,
        private readonly Money $unitPrice,
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Množství musí být kladné.');
        }
    }

    public function productId(): ProductId { return $this->productId; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): Money { return $this->unitPrice; }
}
:::

`Order` v ukázce je kořen agregátu a drží kolekci `OrderItem` objektů. Konstruktor je
privátní a instance vzniká pojmenovanou factory `Order::place()`; nikdo tak nevyrobí
objednávku bez zákazníka a bez počátečního stavu. Vnější volání jdou výhradně přes
metody na `Order`, vlastní `OrderItem` zvenku nikdo neinstancuje ani nemění.
Každé porušené pravidlo hlásí pojmenovaná výjimka – `InvalidOrderStateTransitionException`
pro nepovolený přechod stavu, `EmptyOrderException` pro prázdnou objednávku. Volající se
tak může rozhodnout podle typu, ne podle textu zprávy. Výpočet `totalAmount()`
přebírá měnu z položek. Sčítání začíná u první z nich a `Money::add()` při
nesouladu vyhodí výjimku. Objednávka kombinující dvě měny tak neprojde tiše.
Události agregát zatím nezaznamenává. Předka `AggregateRoot` a volání `record()`
doplní [sekce o životním cyklu](#aggregate-root-lifecycle). `OrderItem` je zde
záměrně zjednodušený na neměnný záznam bez odkazu zpět na objednávku; identitu mu
uvnitř agregátu stačí dát produkt. Plnou verzi ukazuje kapitola
[Návrh agregátu](/navrh-agregatu#references-by-id), včetně metody
`increaseQuantity()` pro invariant „jedna položka na produkt“.

Tato podoba `Order` je záměrně bez perzistence: položky drží obyčejné pole a po třídě
není ani jedna Doctrine anotace. Model tak jde číst bez znalosti ORM. Verze, kterou
opisujete do projektu, je ta z kapitoly [Návrh agregátu](/navrh-agregatu#references-by-id).
Má stejné metody, ale `Collection` místo pole, mapování a `OrderItem` s odkazem zpět
na objednávku; jinak by Doctrine neměla co zapsat do cizího klíče.

:::callout{type="note"}
### Enum pro stavové typy {#enum-poznamka-heading}

Pro konečnou množinu stavů typu `OrderStatus` se obvykle volí nativní
`enum` místo plnohodnotného hodnotového objektu:

:::code{language="php" filename="src/Ordering/Domain/ValueObject/OrderStatus.php (základní podoba)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

enum OrderStatus: string
{
    case Draft     = 'draft';
    case Confirmed = 'confirmed';
    case Paid      = 'paid';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
:::

**Kdy enum, kdy plný Value Object?** Enum stačí pro uzavřený výčet stavů
bez další logiky. Vlastní třída je lepší tam, kde typ nese validaci, výpočty
nebo kompozici více hodnot, jako jsou `Money`, `Email` a `DateRange`.
:::

## 06.06 Repozitáře (Repositories) {#repositories}

Doménová vrstva by neměla vědět, jestli agregát žije v PostgreSQL, MongoDB,
nebo v paměti. Repozitář je rozhraní, které tuto neznalost umožňuje. Pro doménu
vypadá jako kolekce agregátů v paměti, skutečné uložení řeší implementace
v infrastrukturní vrstvě. Vzor pochází z katalogu *Patterns of Enterprise Application
Architecture*, kde ho Edward Hieatt a Rob Mee popsali jako prostředníka mezi doménou
a mapováním dat, který se navenek tváří jako kolekce
[[11]](https://martinfowler.com/eaaCatalog/repository.html).

:::code{language="php" filename="src/Ordering/Domain/Repository/OrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Repository;

use App\Ordering\Domain\Exception\OrderNotFoundException;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\OrderId;

interface OrderRepository
{
    public function save(Order $order): void;

    /** @throws OrderNotFoundException když objednávka neexistuje */
    public function get(OrderId $id): Order;
}
:::

`OrderRepository` je záměrně úzký: uložit agregát a načíst ho podle identity. Dotazy typu
„všechny objednávky zákazníka“ do něj nepatří. Obsluhuje je read model, jak rozvádí
kapitola [CQRS](/cqrs). Chybějící objednávka je chyba volajícího, ne prázdný výsledek,
proto `get()` vrací `Order` a hází výjimku místo `null`.
Implementaci si volí infrastruktura – nejčastěji Doctrine ORM, ale stejně dobře
in-memory varianta pro testy. Praktickou implementaci v Symfony 8 popisuje kapitola
[Implementace v Symfony 8](/implementace-v-symfony).

Tři pravidla oddělují repozitář od obyčejné servisní třídy nad databází:

1. Jeden repozitář na kořen agregátu, ne na každou entitu. `OrderItem` vlastní
   repozitář nemá, načítá se a ukládá jako součást objednávky.
2. Rozhraní vrací sestavené agregáty, ne řádky ani asociativní pole. Tím se repozitář
   liší od DAO, které mluví v pojmech tabulek a nabízí nad nimi CRUD.
3. Dotazy pro obrazovky sem nepatří. Pro ně vede samostatná cesta, kterou rozebírá
   [CQRS](/cqrs).

Metoda `save()` je vědomá odchylka od původní formulace. Vernon rozlišuje dvě podoby.
Collection-oriented repozitář se chová jako kolekce (`add()`, `remove()`) a spoléhá
na to, že persistence sleduje změny sama. Persistence-oriented varianta se `save()`
přichází na řadu tam, kde úložiště změny nesleduje
[[4]](https://www.informit.com/store/implementing-domain-driven-design-9780321834577).
Doctrine změny sleduje, takže by první podoba obstála. Explicitní `save()` je přesto
čitelnější: v kódu je vidět, kde se zápis odehrává.

## 06.07 Doménové služby (Domain Services) {#domain-services}

Některá pravidla nepatří jednomu agregátu ani jednomu hodnotovému objektu.
Koordinují více objektů nebo zachycují proces, který nemá vlastníka. Takovou
logiku přebírá doménová služba. Nedrží stav, nemá životní cyklus, jen pracuje
s entitami a hodnotovými objekty.

:::code{language="php" filename="src/Ordering/Domain/Service/ShippingFeeService.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Service;

use App\Ordering\Domain\Model\Customer;
use App\Ordering\Domain\Model\Order;
use App\SharedKernel\Domain\Currency;
use App\SharedKernel\Domain\Money;

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

Evans mluví o službě jako o samostatném rozhraní. Ukázka žádné nezavádí, protože
implementace je jediná a předčasná abstrakce by přidala jen soubor navíc.
Přibude-li druhý způsob výpočtu nebo potřeba službu v testu nahradit, lze rozhraní
`ShippingFeeCalculator` doplnit kdykoli.

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
factory metoda. Přebírá identifikátor a částku, ne celý agregát `Order`.
Mezi agregáty putují identifikátory a hodnotové objekty, nikdy reference.

:::code{language="php" filename="src/Ordering/Domain/Model/Payment.php (výřez)"}
public static function forOrder(OrderId $orderId, Money $amount, PaymentMethod $method): self
{
    return new self(PaymentId::generate(), $orderId, $amount, $method);
}
:::

Identifikátor vytváří `PaymentId::generate()`, uvnitř postavené na `Uuid::v7()`
z balíčku `symfony/uid`. Vzor Factory podrobně rozebírá kapitola
[Doplňující taktické vzory](/mene-zname-vzory#factories).
:::

## 06.08 Doménové události (Domain Events) {#domain-events}

„Objednávka byla potvrzena.“ „Platba byla přijata.“ Doménová událost je neměnný
záznam o věci, která se v doméně stala a o které doménoví experti chtějí vědět.
Evans k tomu dodává, že událost obvykle nese časové razítko a identitu zúčastněných
entit [[3]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf).
Název je proto vždy v minulém čase – popisuje hotovou věc, ne příkaz.

:::code{language="php" filename="src/Ordering/Domain/Event/OrderPlaced.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;

final readonly class OrderPlaced
{
    public \DateTimeImmutable $occurredAt;

    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
:::

`OrderPlaced` v ukázce nese tři údaje: které objednávky se týká, kterého zákazníka
a kdy vznikla. Vlastnosti jsou veřejné a `readonly`, protože událost je neměnný
záznam a příjemci ji jen čtou.

Kolik dat do události patří, je rozhodnutí, ne pravidlo. Tenká událost nese
identifikátory a zbytek si příjemce dotáhne sám; tlustá veze celý stav a příjemce se
už na nic ptát nemusí. Druhou podobu Fowler pojmenoval Event-Carried State Transfer
a řadí ji vedle prosté notifikace, Event Sourcingu a CQRS jako jednu ze čtyř variant
event-driven architektury
[[12]](https://martinfowler.com/articles/201701-event-driven.html). Uvnitř jednoho
kontextu se osvědčí tenká varianta, jakou ukazuje `OrderPlaced`: příjemce má
k agregátu přístup a duplikovaná data by se dřív nebo později rozešla.

Hranice kontextu rozděluje události na doménové a integrační. Doménová událost mluví
jazykem `Ordering` a zůstává uvnitř. Integrační je kontrakt pro cizí kontexty
a mění se jen tak rychle, jak její příjemci snesou
[[13]](https://devblogs.microsoft.com/cesardelatorre/domain-events-vs-integration-events-in-domain-driven-design-and-microservices-architectures/).
Poslat `OrderPlaced` ven proto znamená zveřejnit vnitřní model se vším, co z toho
plyne. Překlad na stabilní kontrakt řeší
[Published Language](/context-mapping#published-language). Spolehlivé doručení
a idempotenci na straně příjemce řeší [Outbox Pattern](/outbox-pattern#inbox),
protože Messenger doručuje at-least-once.

Domain Events tvoří základ pro dvě architektonické techniky: oddělení čtení a zápisu
v [CQRS](/cqrs) a uložení stavu jako sekvence událostí
v [Event Sourcingu](/event-sourcing).

## 06.09 Agregát a doménové události: lifecycle {#aggregate-root-lifecycle}

Kdo událost vytvoří a kdy se dostane k příjemcům? Odpověď má dvě části. Agregát
událost *zaznamená* ve chvíli, kdy se změna stane – uvnitř doménové metody.
Aplikační vrstva ji *publikuje* až poté, co se změna uložila. Mezi oběma kroky
drží události bázová třída kořene agregátu:

:::code{language="php" filename="src/SharedKernel/Domain/AggregateRoot.php (základní podoba)"}
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

Agregát `Order` ze [sekce o agregátech](#aggregates) z této třídy dědí. Výřez ukazuje
jeho `place()`, `addItem()` a `confirm()` doplněné o volání `record()`:

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (výřez)"}
class Order extends AggregateRoot
{
    // ... vlastnosti a metody ze sekce 06.05 ...

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        // ... kontrola stavu ze sekce 06.05 ...
        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
        $this->record(new OrderItemAdded($this->id, $productId, $quantity));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Cannot confirm a non-created order');
        }

        if ($this->items === []) {
            throw EmptyOrderException::cannotConfirm();
        }

        $this->status = OrderStatus::Confirmed;
        $this->record(new OrderConfirmed($this->id));
    }
}
:::

`OrderConfirmed` je analogická událost k `OrderPlaced` z předchozí sekce. Volání
`record()` stojí v named constructoru a v doménových metodách, nikdy v `__construct`.
Na vině je reconstitution, tedy sestavení agregátu z uložených dat. Doctrine při
hydrataci konstruktor obchází, ruční `Order::reconstitute()` ho ale volá – a kdyby
v něm `record()` byl, každé načtení objednávky by znovu ohlásilo její vznik.
Reconstitution jako zvláštní typ factory rozebírají
[Doplňující taktické vzory](/mene-zname-vzory#fac-reconstitute).

Druhou polovinu životního cyklu obstará command handler. Uloží agregát
a teprve potom vyzvedne nahrané události přes `releaseEvents()`:

:::code{language="php" filename="src/Ordering/Application/Command/CreateOrderHandler.php (výřez)"}
$order = Order::place(OrderId::generate(), $customerId);

$this->orders->save($order); // jen persist agregátu
$this->em->flush();          // zápis do DB; transakci vlastní aplikační vrstva

foreach ($order->releaseEvents() as $event) {
    $this->eventBus->dispatch($event);
}
:::

Pod middlewarem `doctrine_transaction` je situace jiná. Transakci otevře před
handlerem a commituje ji až po jeho návratu, takže `flush()` sám nic nepotvrzuje
a dispatch běží uvnitř otevřené transakce. Nasazení middlewaru proto vyžaduje
[Outbox](/outbox-pattern), ne dispatch přímo z handleru.

Toto pořadí volí kniha záměrně, není to jediná možnost. Publikace před flushem by
příjemcům oznámila změnu, kterou databáze mohla odmítnout. Dispatch po flushi zase
o událost přijde, když proces spadne mezi uložením a publikací. Zadarmo není ani
jedna varianta.

Druhý tábor události odesílá uvnitř transakce. Jimmy Bogard to opírá o argument, že
vedlejší efekty patří do téže logické transakce jako změna, která je vyvolala
[[14]](https://lostechies.com/jimmybogard/2014/05/13/a-better-domain-events-pattern/).
Symfony pro takové odložení nabízí middleware `dispatch_after_current_bus` a stamp
`DispatchAfterCurrentBusStamp`, který doručení posune až za konec aktuálního handleru
[[15]](https://symfony.com/doc/current/messenger/dispatch_after_current_bus.html).
Riziko ztracené události odstraní až transakční outbox: událost i změna agregátu
se zapíšou jednou transakcí. Plné zapojení do Symfony (repozitář, event bus přes
Messenger) popisuje kapitola
[Implementace v Symfony 8](/implementace-v-symfony#domain-events), outbox potom
[Outbox Pattern](/outbox-pattern).

:::faq{}
- question: Jaký je rozdíl mezi Entitou a Value Objectem?
  answer: 'Entita má jednoznačnou identitu (ID), která ji odlišuje od ostatních instancí i tehdy, sdílejí-li stejné atributy. Dva uživatelé se stejným jménem a e-mailem jsou stále dvě různé entity. Value Object identitu nemá a porovnává se podle hodnot všech svých atributů – typické příklady jsou <code>Money</code>, <code>Address</code>, <code>Email</code>. Entitu lze v čase měnit, Value Object je zpravidla neměnný. Srovnání obou konceptů v <a href="#entities">sekci o Entitách</a> a <a href="#value-objects">sekci o Value Objects</a>.'
- question: K čemu slouží Hodnotový objekt (Value Object)?
  answer: 'Hodnotový objekt zapouzdřuje doménový koncept, který určují pouze jeho hodnoty, nikoli identita – například peněžní částka s měnou, rozsah kalendářních dní nebo e-mailová adresa. Umožňuje přesunout pravidla platnosti a doménové chování blízko dat, která popisují, a eliminuje tzv. Primitive Obsession (používání primitivních typů tam, kde patří doménový pojem). Neměnnost Value Objectu zjednodušuje uvažování o kódu i paralelním přístupu. Více v <a href="#value-objects">sekci o Hodnotových objektech</a>.'
- question: Co je Agregát a proč je jeho hranice důležitá?
  answer: 'Agregát je skupina doménových objektů, které se mění jako jeden celek. Přístup k jeho vnitřním částem vede výhradně přes kořenovou entitu (Aggregate Root). Hranice agregátu bývá zároveň hranicí transakční konzistence: co je uvnitř, musí být po každé operaci ve validním stavu. Správně vymezený agregát brání porušení doménových invariantů a ulehčuje rozhodování o tom, co lze měnit souběžně. Podrobný rozbor v <a href="#aggregates">sekci o Agregátech</a>.'
- question: Jakou roli má Repozitář v DDD?
  answer: 'Repozitář poskytuje doménové vrstvě rozhraní podobné kolekci pro ukládání a načítání agregátů, aniž by doména musela znát konkrétní persistenční technologii. Pro kód v doménové vrstvě vypadá repozitář jako in-memory kolekce objektů; skutečné uložení do databáze probíhá v infrastrukturní vrstvě, která rozhraní implementuje. Díky tomu lze testovat doménu proti in-memory repozitáři a nahradit úložiště bez zásahu do doménových pravidel. Více v <a href="#repositories">sekci o Repozitářích</a>.'
- question: Kdy použít Doménovou službu místo metody na Entitě?
  answer: 'Doménová služba se hodí tam, kde operace přirozeně nepatří žádné Entitě ani Value Objectu. Koordinuje více agregátů, komunikuje s externím systémem nebo počítá nad kolekcí objektů. Pokud lze chování přirozeně umístit do metody Entity, má vždy přednost. Doménová služba není datový transfer objekt ani aplikační koordinátor; drží doménovou logiku bez stavu. Rozbor a typické případy užití v <a href="#domain-services">sekci o Doménových službách</a>.'
- question: Co je Doménová událost a k čemu slouží?
  answer: 'Doménová událost je neměnný záznam o tom, že se v doméně stalo něco podstatného – například „objednávka byla potvrzena“ nebo „platba byla přijata“. Události umožňují oddělit části systému, které reagují na změny, od částí, které změny vyvolávají: místo přímého volání se publikuje událost a zájemci ji zpracují. V DDD tvoří události také základ pro Event Sourcing a pro komunikaci mezi Bounded Contexty. Detailní rozbor v <a href="#domain-events">sekci o Doménových událostech</a>.'
:::
