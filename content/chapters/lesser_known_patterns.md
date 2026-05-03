---
route: lesser_known_patterns
path: /mene-zname-vzory
title: 'Méně známé taktické vzory: Specifications, Domain Services, Factories, Modules'
page_title: "Specifications, Domain Services, Factories, Modules – kompletně | DDD Symfony"
meta_description: "Čtyři často přehlížené taktické vzory DDD: Specification Pattern (kompozice doménových pravidel), Domain Services (logika mimo entity), Factories (komplexní vznik aggregate), Modules (Eric Evans organization). Praktická implementace v Symfony 8 a PHP 8.4."
meta_keywords: "specification pattern, domain service, factory, module, DDD, taktický design, Eric Evans, Vernon, PoEAA, phparkitect, Symfony 8, PHP 8.4, Doctrine criteria, double dispatch, ubiquitous language, anémický model"
og_type: article
published: "2026-04-29"
modified: "2026-04-29"
breadcrumb_name: Méně známé taktické vzory
schema_type: TechArticle
schema_headline: "Méně známé taktické vzory: Specifications, Domain Services, Factories, Modules"
chapter_number: "08"
category: Vzory
deck: 'Vedle entit, value objektů a agregátů obsahuje Evansova kniha čtyři další taktické vzory, které programátoři často přeskočí: <strong>Specifications</strong> jako prvotřídní booleovská logika, <strong>Domain Services</strong> pro chování bez přirozeného vlastníka, <strong>Factories</strong> pro komplexní vznik agregátů a <strong>Modules</strong> jako vědomá organizace kódu. Tato kapitola je jejich detailní průvodce v Symfony 8 a PHP 8.4 – s ukázkami kódu, anti-vzory a srovnávacími tabulkami.'
reading_time: 28
difficulty: 3
github_examples: Chapter12_LesserPatterns
---

V kapitole [Základní koncepty DDD](/zakladni-koncepty) jsme prošli
čtyři pilíře taktického designu: **Entity**, **Value Object**,
**Aggregate** a stručně i **Domain Service** a **Factory**.
Eric Evans jim věnuje v částech II a III desítky stran. V průvodcích je vývojáři přeskakují
nebo si je pletou s jinými vzory. Cílem této kapitoly je vrátit jim plný význam: kdy jsou skutečně užitečné,
jak je zapsat v PHP 8.4 a jaká rizika přinášejí, pokud je použijeme špatně.

Čtyři vzory, každý s vlastní kapitolou v Evansově knize:
**Specification Pattern** (kap. 9) – kompozice doménových predikátů jako prvotřídních objektů.
**Domain Services** (kap. 5) – logika bez přirozeného vlastníka mezi Entitami a Value Objekty.
**Factories** (kap. 6; Vernon kap. 11) – zapouzdření vzniku agregátů se složitými invarianty.
**Modules** (kap. 5) – vědomá organizace kódu podle ubiquitous language.

## 08.01 Proč tyto vzory přehlížíme {#proc-prehlizime}

Většina online průvodců o DDD končí někde u Aggregate. Programátor, který se právě naučil
odlišovat Entity od Value Objektu a chápe význam invariantů, má pocit, že už ovládá
„taktický design“. Specification, Domain Service, Factory a Module se mu pak jeví jako
„nadbytečnou abstrakci“. Vždyť to, co dělají, lze napsat i jinak: *if*-em,
statickou metodou nebo prostým balíčkem v `src/`. Tato intuice je klamná
a kapitola ukáže proč.

V malých projektech opravdu bez těchto vzorů přežijete. Jenže tam, kde je doména
netriviální – tedy přesně tam, kde DDD platí – chybějící vzory způsobují bobtnání agregátů,
anémii modelu a duplikaci pravidel. Kód přestává odrážet doménovou strukturu projektu.
Evans těmto vzorům věnuje desítky stran z dobrého důvodu: jsou součástí ucelené sady, která drží
pohromadě.

:::callout{type="note"}
### Co od kapitoly očekávat {#prehled-heading}

Pro každý ze čtyř vzorů projdeme čtyři otázky: **(1) Co to přesně je** v
Evansově/Vernonově definici. **(2) Kdy ho použít** – typické příklady.
**(3) Kdy NE** – anti-vzory a over-engineering. **(4) Jak ho
implementovat** v Symfony 8 + PHP 8.4 s konkrétním kódem. U vzorů, kde
se uplatňují srovnávací rozdíly (Domain vs. Application Service), doplníme tabulku.
Na konci kapitoly najdete shrnutí anti-vzorů a křížové odkazy na související části
knihy.
:::

Začneme vzorem, který bývá v komunitě nejčastěji přehlížen, přestože Evans mu věnoval
celou samostatnou kapitolu – **Specification Pattern**.

## 08.02 Specification Pattern {#specification}

### Co to je {#spec-definice}

**Specification** je prvotřídní objekt, který zapouzdřuje jeden booleovský
predikát nad doménovým objektem – typicky odpověď na otázku tvaru „splňuje tento agregát
konkrétní pravidlo?“. Minimální rozhraní vypadá takto:

:::code{language="php" filename="src/SharedKernel/Domain/Specification/Specification.php"}
interface Specification
{
    public function isSatisfiedBy(mixed $candidate): bool;
}
:::

Zdánlivě banální. Právě tato banalita je síla. Každé pravidlo doménového jazyka
– *„zákazník je premium“*, *„objednávka má nárok na dopravu zdarma“*,
*„faktura je po splatnosti“* – dostane vlastní třídu s mluvícím jménem. Pravidlo
přestává být kombinací `if`-ů uvnitř service vrstvy a stává se
**jmenovaným prvkem ubiquitous language**.

Eric Evans vzor poprvé formálně popsal v *Domain-Driven Design* (2003), kapitole 9
s názvem *Making Implicit Concepts Explicit*. Evans a Fowler ho dříve rozpracovali
v pracovním papíru *Specifications*
[[martinfowler.com]](https://martinfowler.com/apsupp/spec.pdf).
Společný motiv: pravidla, která se v doméně objevují
opakovaně, si zaslouží vlastní jméno a vlastní typ.

### Kdy použít {#spec-kdy}

1. **Komplexní doménová pravidla, která se mají skládat.** Pokud máte
   v různých částech aplikace rekombinace téhož motivu – někde
   „*premium AND v EU*“, jinde „*premium OR má slevový kód*“ –
   kompozice pomocí Specification ušetří duplikaci a udrží pravidla
   konzistentní.
2. **Pravidla použitelná jak v doméně, tak v repozitáři.** Jedna a tatáž
   specifikace musí umět odpovědět na otázku „*splňuje tento konkrétní objekt
   pravidlo?*“ (in-memory predikát) i „*vrať mi z databáze všechny objekty,
   které pravidlo splňují?*“ (query). Tomuto se říká
   **double-dispatch** a vyhnete se tím duplikaci pravidla mezi PHP
   kódem a SQL/Doctrine DQL.
3. **Pravidla, která se skládají za běhu.** Promo kód, který má v admin UI
   podmínky *„platí pro nákupy > 1000 Kč v ČR a SK, kromě výprodejového zboží“*,
   se v doméně reprezentuje jako instance `AndSpecification` složená z N pod-pravidel
   čitelných z databáze.
4. **Pravidla validace agregátu.** Místo aby Aggregate sám kontroloval
   všechny invarianty v setterech, deleguje na specifikaci, která je čitelná samostatně
   i testovatelná v izolaci.

### Kdy NE {#spec-kdy-ne}

Specification je vzor s nezanedbatelnou cenou: každé pravidlo = nová třída, nový soubor,
nový test. Nepoužívejte ho pro:

- Triviální podmínky, které se vyskytují **jednou** a obsahují
  **jeden if**: `if ($order->total->amount > 1000)`
  nepotřebuje vlastní třídu.
- Pravidla, která jsou ve skutečnosti **součástí invariantu Aggregate**
  (a tedy patří přímo do něj jako privátní metoda).
- Konfigurační a technické flagy – Specification má reprezentovat
  *doménové* pravidlo, ne podmínku *„má feature flag enabled“*.

:::callout{type="warn"}
### Anti-vzor: Specification pro každé porovnání {#spec-anti-heading}

Začátečníci po objevení vzoru často propadnou „efektu kladiva“ a vytvoří
třídy `OrderTotalGreaterThanSpecification`, `OrderTotalLessThanSpecification`,
`OrderTotalEqualsSpecification` – každá obsahuje jeden řádek kódu.
To je over-engineering: ztrácíte čitelnost domény, protože jména přestanou být
doménová a stanou se z nich obecné prepozice. Specification má reprezentovat
*celou doménovou otázku*, ne jednotlivý operátor.
:::

### Skladba pomocí kombinátorů {#spec-diagram}

Vzor těží z toho, že specifikace lze **skládat** pomocí
booleovských kombinátorů `and`, `or`, `not`. Místo
klubka `if`-ů a `else`-ů zapíšete pravidlo jako algebraický
výraz nad pojmenovanými atomy. Třídní hierarchie vypadá následovně:

:::diagram{fig="08.2-A" title="Specification Pattern: kompozice booleovské logiky" src="images/diagrams/16_lesser_patterns/specification_compose.svg"}
:::

### Interface a abstraktní kompozit {#spec-interface}

Začneme rozhraním, které vystaví všechny tři kombinátory, a abstraktní třídou, která je
implementuje pomocí AndSpecification, OrSpecification, NotSpecification:

:::code{language="php" filename="src/SharedKernel/Domain/Specification/Specification.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Specification;

/**
 * Doménová specifikace – prvotřídní objekt zapouzdřující booleovský predikát.
 *
 * @template T
 */
interface Specification
{
    /** @param T $candidate */
    public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * @param Specification<T> $other
     * @return Specification<T>
     */
    public function and(self $other): self;

    /**
     * @param Specification<T> $other
     * @return Specification<T>
     */
    public function or(self $other): self;

    /** @return Specification<T> */
    public function not(): self;
}
:::

Aby každá konkrétní specifikace nemusela kombinátory implementovat sama, abstraktní
třída je dodá zdarma:

:::code{language="php" filename="src/SharedKernel/Domain/Specification/CompositeSpecification.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Specification;

/**
 * @template T
 * @implements Specification<T>
 */
abstract class CompositeSpecification implements Specification
{
    /** @param T $candidate */
    abstract public function isSatisfiedBy(mixed $candidate): bool;

    public function and(Specification $other): Specification
    {
        return new AndSpecification($this, $other);
    }

    public function or(Specification $other): Specification
    {
        return new OrSpecification($this, $other);
    }

    public function not(): Specification
    {
        return new NotSpecification($this);
    }
}
:::

:::code{language="php" filename="src/SharedKernel/Domain/Specification/AndSpecification.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Specification;

/**
 * @template T
 * @extends CompositeSpecification<T>
 */
final class AndSpecification extends CompositeSpecification
{
    /**
     * @param Specification<T> $left
     * @param Specification<T> $right
     */
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            && $this->right->isSatisfiedBy($candidate);
    }
}
:::

:::code{language="php" filename="src/SharedKernel/Domain/Specification/OrSpecification.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Specification;

/**
 * @template T
 * @extends CompositeSpecification<T>
 */
final class OrSpecification extends CompositeSpecification
{
    /**
     * @param Specification<T> $left
     * @param Specification<T> $right
     */
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            || $this->right->isSatisfiedBy($candidate);
    }
}
:::

:::code{language="php" filename="src/SharedKernel/Domain/Specification/NotSpecification.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Specification;

/**
 * @template T
 * @extends CompositeSpecification<T>
 */
final class NotSpecification extends CompositeSpecification
{
    /** @param Specification<T> $inner */
    public function __construct(private readonly Specification $inner) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return !$this->inner->isSatisfiedBy($candidate);
    }
}
:::

### Doménová specifikace {#spec-domain}

S kostrou hotovou ukážeme konkrétní doménové specifikace. Jedná se o skutečná doménová
pravidla z Ordering kontextu – všimněte si, že každá z nich nese mluvící doménové jméno
a dědí kombinátory `and`/`or`/`not` automaticky:

:::code{language="php" filename="src/Ordering/Domain/Specification/EligibleForFreeShipping.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Specification;

use App\Ordering\Domain\Order;
use App\SharedKernel\Domain\Money;
use App\SharedKernel\Domain\Specification\CompositeSpecification;

/**
 * Objednávka má nárok na dopravu zdarma, pokud její celková hodnota
 * dosahuje nebo přesahuje stanovený limit.
 *
 * @extends CompositeSpecification<Order>
 */
final class EligibleForFreeShipping extends CompositeSpecification
{
    public function __construct(private readonly Money $threshold) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        return $candidate->total()->isGreaterThanOrEqual($this->threshold);
    }
}
:::

:::code{language="php" filename="src/Ordering/Domain/Specification/InEUCountry.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Specification;

use App\Ordering\Domain\Order;
use App\SharedKernel\Domain\Country;
use App\SharedKernel\Domain\Specification\CompositeSpecification;

/**
 * Doručovací adresa objednávky se nachází v zemi EU.
 *
 * @extends CompositeSpecification<Order>
 */
final class InEUCountry extends CompositeSpecification
{
    public function __construct(private readonly Country $country) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        return $this->country->isInEU()
            && $candidate->shippingAddress()->country()->equals($this->country);
    }
}
:::

:::code{language="php" filename="src/Ordering/Domain/Specification/NotInBlacklist.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Specification;

use App\Ordering\Domain\Order;
use App\SharedKernel\Domain\CustomerId;
use App\SharedKernel\Domain\Specification\CompositeSpecification;

/**
 * Zákazník není uveden na doménovém blacklistu (např. fraud detection).
 *
 * @extends CompositeSpecification<Order>
 */
final class NotInBlacklist extends CompositeSpecification
{
    /** @param list<CustomerId> $blacklist */
    public function __construct(private readonly array $blacklist) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        foreach ($this->blacklist as $blocked) {
            if ($blocked->equals($candidate->customerId())) {
                return false;
            }
        }

        return true;
    }
}
:::

### Kompozice v aplikační vrstvě {#spec-compose}

Zde se ukazuje skutečná hodnota vzoru. Komplexní marketingová akce
*„doprava zdarma pro nákupy nad 1000 Kč v EU, kromě zákazníků na blacklistu“*
je trojice atomických specifikací spojená kombinátorem `and`. Vznikne
jedna čitelná řádka místo trojnásobně vnořeného `if`-u:

:::code{language="php" filename="src/Ordering/Application/CommandHandler/ApplyFreeShippingHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\CommandHandler;

use App\Ordering\Domain\Order;
use App\Ordering\Domain\Specification\EligibleForFreeShipping;
use App\Ordering\Domain\Specification\InEUCountry;
use App\Ordering\Domain\Specification\NotInBlacklist;
use App\SharedKernel\Domain\Country;
use App\SharedKernel\Domain\Money;

final class ApplyFreeShippingHandler
{
    public function __construct(private readonly BlacklistRegistry $blacklist) {}

    public function __invoke(Order $order): void
    {
        $promo = (new EligibleForFreeShipping(Money::czk(100_000))) // 1000 Kč v haléřích
            ->and(new InEUCountry($order->shippingAddress()->country()))
            ->and(new NotInBlacklist($this->blacklist->all()));

        if ($promo->isSatisfiedBy($order)) {
            $order->markEligibleForFreeShipping();
        }
    }
}
:::

Pravidlo lze v testu rozložit na atomy a ověřit každý zvlášť. Když produktový tým
rozhodne, že na blacklist se nově dívat nemá, smažete jeden řádek z kompozice – bez
nutnosti pročítat sevřený `if` uvnitř komplexní service vrstvy.

### Double-dispatch do Doctrine {#spec-doctrine}

Specifikace je užitečná i ve **druhé roli** – jako parametr query do
repozitáře. Místo metody `findEligibleForFreeShippingInEU(): array`, kterou
byste pro každou novou kombinaci pravidel přidávali, dostane repozitář *jakoukoliv*
specifikaci, převede ji na DQL/SQL kritérium a vrátí výsledek. Tomuto přístupu se
říká **double-dispatch**: specifikace nese pravidlo, repozitář ví, jak ho
přeložit do persistence.

:::code{language="php" filename="src/SharedKernel/Domain/Specification/QuerySpecification.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Specification;

use Doctrine\ORM\QueryBuilder;

/**
 * Specifikace, která ví, jak se převést na Doctrine kritérium.
 * Implementuje double-dispatch: specifikace zná své pravidlo,
 * repozitář ví, jak ho aplikovat na QueryBuilder.
 *
 * @template T
 * @extends Specification<T>
 */
interface QuerySpecification extends Specification
{
    public function asDoctrineCriteria(QueryBuilder $qb, string $alias): void;
}
:::

:::code{language="php" filename="src/Ordering/Domain/Specification/EligibleForFreeShipping.php (rozšířená verze)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Specification;

use App\Ordering\Domain\Order;
use App\SharedKernel\Domain\Money;
use App\SharedKernel\Domain\Specification\CompositeSpecification;
use App\SharedKernel\Domain\Specification\QuerySpecification;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends CompositeSpecification<Order>
 * @implements QuerySpecification<Order>
 */
final class EligibleForFreeShipping extends CompositeSpecification implements QuerySpecification
{
    public function __construct(private readonly Money $threshold) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        return $candidate->total()->isGreaterThanOrEqual($this->threshold);
    }

    public function asDoctrineCriteria(QueryBuilder $qb, string $alias): void
    {
        $qb->andWhere(sprintf('%s.totalAmount >= :threshold', $alias))
           ->setParameter('threshold', $this->threshold->amount());
    }
}
:::

Repozitář pak vystaví obecnou metodu `match()`, která přijme jakoukoliv
`QuerySpecification` a přeloží ji na DQL:

:::code{language="php" filename="src/Ordering/Infrastructure/Doctrine/DoctrineOrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Doctrine;

use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderRepository;
use App\SharedKernel\Domain\Specification\QuerySpecification;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /**
     * @param QuerySpecification<Order> $spec
     * @return list<Order>
     */
    public function match(QuerySpecification $spec): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o');

        $spec->asDoctrineCriteria($qb, 'o');

        return $qb->getQuery()->getResult();
    }
}
:::

Tímto způsobem vyřešíte klasický problém *„jak se má pravidlo aplikovat jen jednou –
v PHP nebo v SQL?“*. Specifikace je **zdroj pravdy**. Obě její role
(in-memory predikát i query překladač) sedí v jedné třídě a nelze
je oddělit.

Pro hluboký teoretický základ vzoru: Evans, E., *Domain-Driven Design* (2003),
kapitola 9 *Making Implicit Concepts Explicit*; Evans & Fowler, pracovní
papír *Specifications* (1997), dostupný na martinfowler.com.
Praktická aplikace na agregátech: Vernon, V., *Implementing Domain-Driven Design*
(2013).

## 08.03 Domain Services {#domain-services}

### Co to je {#ds-definice}

**Domain Service** je stateless objekt obsahující doménovou logiku, která
**nemá přirozeného vlastníka** mezi Entitami a Value Objekty daného
modelu. Eric Evans v kapitole 5 *Domain-Driven Design* (2003) shrnuje
kritérium do tří bodů: operace se týká doménového konceptu, ale (1) nepatří do žádné
Entity ani Value Objektu jako její přirozená metoda, (2) operuje nad více doménovými
objekty a (3) nemá vlastní stav.

Jinak řečeno: existuje operace *X*, ale žádná Entita ji nemůže vlastnit, aniž by
musela znát příliš mnoho o druhé. To je signál pro Domain Service.

### Kdy použít {#ds-kdy}

Klasické příklady, na kterých Evans i Vernon vzor demonstrují:

- **Funds Transfer** – převod peněz mezi dvěma účty. Patří do agregátu
  `Account`? Ani jeden z účtů nezná ten druhý. Ani jeden není přirozeným
  vlastníkem operace. Jde o doménový koncept sám o sobě.
- **Pricing engine** – výpočet ceny objednávky. Závisí na pricing
  pravidlech, segmentu zákazníka, košíku, kupónu. Žádný z těchto entit není
  přirozeným vlastníkem výpočtu.
- **Credit scoring** – výpočet skóre žadatele o úvěr.
  Hledá se odpověď na *„má tento zákazník nárok na úvěr X?“* kombinací několika
  faktorů.
- **Coordinator dvou agregátů** – operace, která mění stav dvou agregátů
  zároveň, kde žádný z nich nesmí znát detaily druhého (autonomie agregátů).

### Kdy NE {#ds-kdy-ne}

Domain Service je zároveň **nejvíce zneužívaný taktický vzor v DDD**.
Programátoři navyklí na klasickou layered architecture mají sklon vytvořit
`OrderService`, `CustomerService`, `InvoiceService` jako
první reflex – a všechnu logiku z Entit přesunou tam, čímž si vyrobí
[anémický doménový model](/anti-vzory).

Pokud tedy uvažujete o Domain Service, vždy si nejdřív položte trojici kontrolních
otázek:

1. **Patří tato operace přirozeně do nějaké Entity?** (= je to chování
   nad jednou identitou, agregát ji může bez cizí pomoci provést) – pokud ano,
   *nepatří* do Domain Service.
2. **Je to skutečně doménová operace, nebo aplikační?** Domain Service
   obsahuje doménová pravidla. Application Service koordinuje
   (transakce, autorizace, eventy). Pokud byste musel ve „doménové“ service
   volat `EntityManager->flush()` – je to Application Service.
3. **Není to spíš infrastrukturní detail?** Posílání e-mailu, hash hesla,
   čtení z externího API – to nejsou doménové operace, ale infrastruktura.

### Příklad: MoneyTransferService {#ds-priklad}

Klasický bankovní příklad – převod peněz ze zdrojového účtu na cílový. Logika nepatří
do `$from` (nezná `$to`), ani do `$to` (nezná
`$from`). Je to doménová operace bez přirozeného vlastníka:

:::code{language="php" filename="src/Banking/Domain/Service/MoneyTransferService.php"}
<?php

declare(strict_types=1);

namespace App\Banking\Domain\Service;

use App\Banking\Domain\Account;
use App\Banking\Domain\Exception\InsufficientFunds;
use App\Banking\Domain\TransferReference;
use App\SharedKernel\Domain\Money;

/**
 * Domain Service – převod peněz mezi dvěma účty.
 *
 * Operace nepatří do žádného z účtů, protože jeden z nich nesmí znát
 * druhý: agregáty jsou autonomní. Jde o doménovou logiku (validace
 * dostupnosti prostředků, kontrola limitu), nikoliv o aplikační koordinaci.
 *
 * Stateless – bez instance variables, bez side-effectů na kolaborátorech.
 */
final class MoneyTransferService
{
    public function transfer(
        Account $from,
        Account $to,
        Money $amount,
        TransferReference $reference,
        \DateTimeImmutable $when,
    ): void {
        if (!$from->canWithdraw($amount, $when)) {
            throw InsufficientFunds::onAccount($from->id(), $amount);
        }

        if (!$from->currency()->equals($to->currency())) {
            throw new \DomainException(
                'Currency mismatch – use FxTransferService for cross-currency transfers.',
            );
        }

        $from->withdraw($amount, $reference, $when);
        $to->deposit($amount, $reference, $when);
    }
}
:::

Všimněte si tří rysů, podle kterých poznáte „opravdovou“ Domain Service:

1. **Žádný stav** – třída nemá konstruktorové závislosti na repozitářích
   ani `EntityManager`. Pracuje pouze s objekty, které dostane
   v parametrech.
2. **Žádné perzistenční volání** – `$from->withdraw()` a
   `$to->deposit()` mutují stav agregátů, ale ukládat je bude až
   Application Service nebo command handler. Domain Service nikdy nevolá
   `$em->flush()`.
3. **Vyhazuje doménové výjimky** – `InsufficientFunds`,
   `\DomainException` – ne `\RuntimeException` nebo HTTP
   status kódy.

:::callout{type="warn"}
### Anti-vzor: Application Service vydávaný za Domain Service {#ds-anti-heading}

Nejčastější chyba: třída v `Domain/Service/`, která ve svém
konstruktoru přijímá `EntityManager`, `OrderRepository`,
`EventDispatcher` a v jedné metodě dělá načtení agregátu z DB,
úpravu, perzistenci a publikaci eventu. To není Domain Service – to je
**Application Service v přestrojení**. Ztratili jste hranici mezi
doménou (doménová logika) a aplikací (orchestrace use case). Důsledek:
doménový model nelze testovat bez DB a Symfony containeru, refaktoring je
výrazně dražší.
:::

### Domain Service vs. Application Service vs. Infrastructure Service {#ds-srovnani}

V kódu se *nějaká* „Service“ třída vyskytne téměř vždy. Otázkou je,
kterou ze tří odlišných rolí daná Service hraje. Následující srovnávací tabulka shrnuje
rozdíly, na které se v code review ptáme:

| Aspekt | Domain Service | Application Service | Infrastructure Service |
|---|---|---|---|
| Účel | Doménová logika bez přirozeného vlastníka | Koordinace use case (transakce, autorizace, eventy) | Technická integrace (DB, e-mail, externí API) |
| Vrstva | Domain | Application | Infrastructure |
| Závislosti | Pouze doménové typy (Entity, VO, jiné Domain Services) | Repozitáře, Event Bus, Domain Services, Authorization | HTTP klienti, knihovny (Mailer, Stripe SDK), filesystem |
| Stav | Stateless | Stateless (jednorázový handler) | Často stateless, ale může držet connection pool |
| Volá perzistenci? | Ne | Ano (přes repozitář) | Ano (sama je perzistencí) |
| Vyhazuje výjimky | Doménové (`InsufficientFunds`) | Aplikační (`UnauthorizedException`, validation) | Infrastrukturní (`ConnectionException`) |
| Příklad jména | `MoneyTransferService`, `PricingService` | `PlaceOrderHandler`, `RegisterUserHandler` | `SymfonyMailer`, `StripePaymentGateway` |
| Test | Pure unit, bez Symfony kernel | Unit s mockovanými repozitáři | Integrační (kontrakt s reálným systémem) |
| Sufix v PHP | `*Service` (volitelně) | `*Handler`, `*UseCase` | `*Gateway`, `*Adapter`, `*Client` |

Tabulka ukazuje, proč je problematické pojmenovat vše na `*Service` –
a proč se v současném DDD upouští od sufixu *Service*
u Application vrstvy ve prospěch *Handler* nebo *UseCase*. Doménová
Service má sufix *Service* jen tehdy, když pomáhá zdůraznit „operace bez vlastníka“.
V mnoha doménách dokonce i u Domain Service zvolíme přímo doménové jméno
(`FundsTransfer`, `PricingEngine`) bez sufixu.

:::callout{type="pattern"}
### Praktický tip: hraniční případ {#ds-tip-heading}

Když nedokážete jednoznačně rozhodnout, zda je třída Domain nebo Application Service,
obvykle to znamená, že **míchá obě role**. Rozdělte ji: doménovou
logiku do Domain Service v `Domain/Service/`, koordinaci do command
handleru v `Application/CommandHandler/`. Test pro Domain Service
ať proběhne bez Symfony kernelu – pokud nemůže, zbyl tam infrastrukturní leak.
:::

Tématicky souvisí: [Základní koncepty – Doménové služby](/zakladni-koncepty#domain-services), [Anti-vzor: Anemic Domain
Model](/anti-vzory), [CQRS – Application Handler](/cqrs).

Citace: Evans, E., *Domain-Driven Design* (2003), kapitola 5
*A Model Expressed in Software*; Vernon, V., *Implementing Domain-Driven
Design* (2013), kapitola 7.

## 08.04 Factories {#factories}

### Co to je {#fac-definice}

**Factory** v terminologii DDD je zapouzdření **komplexní logiky vzniku
agregátu nebo Value Objektu**, kde standardní konstruktor nestačí. Eric Evans
v kapitole 6 *Domain-Driven Design* (2003) píše:
*„Vytváření složených objektů by mělo být odděleno od jejich provozu, tím spíše,
když jejich vznik vyžaduje pravidla nebo polymorfismus.“*

Standardní konstruktor stačí pro většinu agregátů. Factory je řešení pro
situace, kdy:

- Vznik agregátu vyžaduje validaci, kterou nelze provést až *po* konstrukci
  (např. *„nový Order musí mít alespoň 1 položku, jinak agregát neexistuje“*).
- Vznik je polymorfní – z různých vstupů vznikají různé pod-typy stejného agregátu
  (například `Order::physical()` vs. `Order::digital()`).
- Vznik vyžaduje externí lookup – z REST API přijde surový e-mail, Factory ho převede
  na `CustomerId` přes `CustomerLookup`.
- Mapování z DTO/raw payload je natolik nekonstantní, že by zaplevelilo konstruktor
  doménového objektu detaily transportní vrstvy.

### Kdy NE {#fac-kdy-ne}

Většinu objektů můžete přímočaře vytvořit konstruktorem. Factory přidávejte teprve když
konstruktor začne být nepřehledný:

- **Triviální vznik** – `OrderFactory::create($cust, $items)`,
  která interně volá `new Order(...)` a jinak nic. To není Factory,
  to je redundantní vrstva.
- **Service Locator pattern** – `$factory->create('Order', [...])`
  s magickým rozhodováním podle stringu. Ztrácíte typovou bezpečnost.
- **Factory pro každý objekt v doméně** – over-engineering. DDD říká
  *„Factory podle potřeby“*, ne *„Factory pro všechno“*.

### Vzor 1: Static method factory (preferovaný) {#fac-static}

V PHP 8.4 je preferovanou formou Factory statická pojmenovaná
konstrukční metoda na samotném agregátu (named constructor). Konstruktor je privátní,
publikujete pouze pojmenované entry pointy s doménovou sémantikou:

:::code{language="php" filename="src/Ordering/Domain/Order.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain;

use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Exception\EmptyOrder;
use App\SharedKernel\Domain\AggregateRoot;
use App\SharedKernel\Domain\CustomerId;

final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items;

    /** @param list<OrderItem> $items */
    private function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        array $items,
        private readonly OrderType $type,
        private readonly \DateTimeImmutable $placedAt,
    ) {
        $this->items = $items;
        $this->recordEvent(new OrderPlaced($id, $customerId, $placedAt));
    }

    /**
     * Standardní vznik objednávky se zbožím.
     *
     * @param list<OrderItem> $items
     */
    public static function place(
        CustomerId $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): self {
        if (count($items) === 0) {
            throw EmptyOrder::cannotBePlaced();
        }

        return new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: $items,
            type: OrderType::Physical,
            placedAt: $placedAt,
        );
    }

    /**
     * Polymorfní vznik – pouze digitální obsah, jiná pravidla
     * (žádná dopravní adresa, instantní doručení).
     *
     * @param list<DigitalItem> $items
     */
    public static function placeDigital(
        CustomerId $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): self {
        if (count($items) === 0) {
            throw EmptyOrder::cannotBePlaced();
        }

        return new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: array_map(static fn (DigitalItem $i): OrderItem => $i->toOrderItem(), $items),
            type: OrderType::Digital,
            placedAt: $placedAt,
        );
    }

    /**
     * Vznik z importu – odlišná validace, neidentifikuje zákazníka přes CustomerId,
     * ale přes externí key, který se uvnitř naváže na guest CustomerId.
     */
    public static function fromImport(
        ImportedOrderRow $row,
        CustomerLookup $lookup,
        \DateTimeImmutable $placedAt,
    ): self {
        $customerId = $lookup->byEmail($row->customerEmail) ?? CustomerId::guest();
        $items = ImportedItems::map($row->items);

        return self::place($customerId, $items, $placedAt);
    }
}
:::

Tři výhody static method factory oproti samostatné Factory class:

1. **Doménové jméno**. `Order::place()` nebo
   `Order::placeDigital()` nese sémantiku, kterou
   `new Order(...)` postrádá.
2. **Privátní konstruktor**. Žádný kód mimo agregát nesmí
   `Order` vytvořit cestou, která obejde validaci. Compiler-friendly
   invariant.
3. **Polymorfismus zdarma**. `Order::placeDigital()` a
   `Order::fromImport()` mají různé vstupy a různá pravidla, ale
   výstup je stejný typ.

### Vzor 2: Factory class (když potřebujete DI) {#fac-class}

Statická metoda nestačí v jediné situaci: když vznik agregátu potřebuje
**injektované závislosti** (repozitáře, externí services, konfiguraci).
Statická metoda nemůže DI přijímat bez service locatoru. Pak se přechází na
samostatnou Factory class:

:::code{language="php" filename="src/Ordering/Domain/Factory/OrderFromCartFactory.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Factory;

use App\Ordering\Domain\Cart\CartId;
use App\Ordering\Domain\Cart\CartRepository;
use App\Ordering\Domain\Order;
use App\Ordering\Domain\Pricing\PricingService;
use App\SharedKernel\Domain\CustomerId;
use Psr\Clock\ClockInterface;

/**
 * Factory class – vznik objednávky z košíku vyžaduje
 * načtení košíku a aplikaci aktuálního pricingu.
 * Static method by tyto závislosti nemohla převzít.
 */
final class OrderFromCartFactory
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly PricingService $pricing,
        private readonly ClockInterface $clock,
    ) {}

    public function fromCart(CartId $cartId, CustomerId $customer): Order
    {
        $cart = $this->carts->getById($cartId);

        if ($cart->isEmpty()) {
            throw new \DomainException('Cannot place order from empty cart.');
        }

        $pricedItems = $this->pricing->priceItems($cart->items(), $customer);

        return Order::place(
            customerId: $customer,
            items: $pricedItems,
            placedAt: $this->clock->now(),
        );
    }
}
:::

Všimněte si, že Factory class **uvnitř volá** `Order::place()` –
nepřebírá zodpovědnost za invariant „aspoň 1 položka“, ten zůstává v named
constructor agregátu. Factory řeší pouze *orchestraci vstupních dat*.

:::callout{type="pattern"}
### Pravidlo (Vernon 2013): static method preferred {#vernon-rule-heading}

Vaughn Vernon ve své knize *Implementing Domain-Driven Design* (2013), kapitola 11,
formuluje pravidlo: **„Preferujte named constructor. Po samostatné
Factory class sáhněte teprve tehdy, když vznik nutně potřebuje DI závislosti, kterou nelze
poskytnout parametrem.“** Důvod je prostý: statická metoda na agregátu
drží invarianty pohromadě s kódem agregátu. Samostatná Factory je rozdělí na dva
soubory a vystavuje agregát public konstruktoru, což oslabuje invariant „nelze vytvořit
nevalidní stav“.

Aplikováno na PHP 8.4: privátní konstruktor + statické `::place()`,
`::resume()`, `::fromImport()`. Factory class jen tehdy, když
potřebujete `EntityManager`, `HttpClient`, `Clock`
nebo doménovou službu.
:::

### Reconstitution: zvláštní případ Factory {#fac-reconstitute}

Třetí typ factory, s nímž se setkáte, je **reconstitution** –
rekonstrukce agregátu z perzistence. Doctrine to dělá za vás (přes hydrator), ale pokud
máte Event Sourcing nebo custom mapper, potřebujete factory, která **nevolá
invarianty** (rekonstruovaný agregát už invariant prošel kdysi v minulosti):

:::code{language="php" filename="src/Ordering/Domain/Order.php (fragment)"}
/**
 * Rekonstituce ze stavu načteného z DB / event streamu.
 * Tento pojmenovaný konstruktor neaplikuje invarianty –
 * rekonstruovaný stav je z definice valid, jinak by se nedostal do persistence.
 *
 * @internal Smí volat pouze infrastruktura repozitáře.
 *
 * @param list<OrderItem> $items
 */
public static function reconstitute(
    OrderId $id,
    CustomerId $customerId,
    array $items,
    OrderType $type,
    \DateTimeImmutable $placedAt,
): self {
    return new self($id, $customerId, $items, $type, $placedAt);
}
:::

Pojmenování `::reconstitute()` a PHPDoc `@internal` jasně
signalizují, že tato cesta vzniku je vyhrazena pro infrastrukturu. Doménový handler,
který by ji volal místo `::place()`, by porušil invariant agregátu.

Pro detail: Evans, E., *Domain-Driven Design* (2003), kapitola 6
*The Life Cycle of a Domain Object*; Vernon, V., *Implementing Domain-Driven
Design* (2013), kapitola 11 *Factories*. Souvisejí kapitoly:
[Základní koncepty – Agregáty](/zakladni-koncepty#aggregates),
[Event Sourcing](/event-sourcing) (reconstitution z event
streamu).

## 08.05 Modules {#modules}

### Co to je {#mod-definice}

**Module** je v Evansově terminologii **vědomá organizace kódu
do balíčků pojmenovaných podle ubiquitous language**. Není to PHP feature, není
to namespace – je to *princip*, který říká: *„rozhraní balíčků vašeho kódu má
odrážet doménový jazyk, ne technické vrstvy a ne použité knihovny.“*

Evans věnoval Modules celou samostatnou pasáž v kapitole 5 *Domain-Driven
Design* (2003). Citace: *„Modules in DDD are a way of expressing the higher-level
structure of a model ... Modules should reflect the domain language, not the technical
organization of code.“*

V Symfony 8 a PHP 8.4 to konkrétně znamená:

- **PSR-4 namespace + folder layout** uspořádané podle
  [Bounded Contextů](/zakladni-koncepty#bounded-contexts).
- **`composer.json` autoload sekce**, která mapuje namespace
  `App\Ordering\` na `src/Ordering/` – ne na
  `src/` jako v default Symfony skeletonu.
- **Architecture testing**, který zkontroluje, že žádný kód
  v `App\Billing\` přímo nedotahuje do `App\Ordering\`.

### Modul jako Bounded Context {#mod-bc}

Nejčastěji se vzor uplatní jako **1 modul = 1 Bounded Context**.
Projekt strukturovaný tímto způsobem vypadá takto:

:::code{language="bash" filename="Adresářová struktura podle Modules vzoru"}
src/
  Ordering/                                  ← MODULE = Bounded Context
    Domain/
      Order.php                              ← Aggregate Root
      OrderRepository.php                    ← Interface
      OrderItem.php
      Specification/
        EligibleForFreeShipping.php
        InEUCountry.php
      Service/
        PricingService.php                   ← Domain Service
      Factory/
        OrderFromCartFactory.php
      Event/
        OrderPlaced.php
      Exception/
        EmptyOrder.php
    Application/
      Command/
        PlaceOrderCommand.php
      CommandHandler/
        PlaceOrderHandler.php
      Query/
        ListOrdersQuery.php
      QueryHandler/
        ListOrdersHandler.php
    Infrastructure/
      Doctrine/
        DoctrineOrderRepository.php
        OrderMapping.orm.xml
      Http/
        OrderController.php
      Messenger/
        OrderPlacedSubscriber.php
  Billing/                                   ← Jiný BC = jiný modul
    Domain/
      Invoice.php
      ...
    Application/
      ...
    Infrastructure/
      ...
  SharedKernel/                              ← Sdílený jazyk a typy
    Domain/
      Money.php
      Currency.php
      Country.php
      AggregateRoot.php
      Specification/
        Specification.php
        CompositeSpecification.php
        AndSpecification.php
        OrSpecification.php
        NotSpecification.php
        QuerySpecification.php
:::

Této organizaci se v komunitě říká také *vertical slicing* – viz kapitolu
[Horizontální vs. vertikální dělení](/vertikalni-slice),
která jí věnuje detailní rozbor. Pro účely této kapitoly stačí pozorování: *shora
vidíte doménovou mapu projektu* (Ordering, Billing, SharedKernel), a ne technický
chaos složek Twig/Doctrine/Service.

### Anti-vzor: type packaging {#mod-anti}

:::callout{type="warn"}
### Anti-vzor: `src/Entity/`, `src/Service/`, `src/Repository/` {#type-pack-heading}

Symfony skeleton historicky zaváděl složky podle technické role: `src/Entity/`
pro entity, `src/Repository/` pro repozitáře, `src/Controller/`
pro kontrolery, `src/Service/` pro „všechno ostatní“. Tato organizace má
název **type packaging** a v netriviálních doménách je
problematická:

- **Skrývá doménu**. Z adresářů nepoznáte, čím se aplikace zabývá –
  je to e-shop, banka, IS pro pojišťovnu? Vidíte pouze, že to má entity a controllery.
- **Vynucuje horizontální vrstvy**. Změna jednoho doménového pravidla
  často vyžaduje editovat soubor v 5 různých složkách, místo jednoho modulu.
- **Eroze modularity**. `OrderEntity` a `InvoiceEntity`
  sedí vedle sebe, takže není zřejmé, že nesmí přímo komunikovat.

Type packaging má své opodstatnění: ve *velmi malých* aplikacích, kde doména
prakticky neexistuje (CRUD nad jedním objektem), nebo v ukázkových repozitářích
pro výuku jednotlivých Symfony komponent. V doménově bohaté aplikaci je třeba se mu
vyhnout.
:::

### composer.json autoload {#mod-composer}

Aby PSR-4 namespace odpovídala adresářové struktuře, upravte
`composer.json`. Default Symfony nastavení mapuje `App\` na
`src/`, ale chceme každý modul s vlastním kořenem:

:::code{language="json" filename="composer.json (fragment)"}
{
    "name": "your-org/your-app",
    "type": "project",
    "require": {
        "php": ">=8.4",
        "symfony/framework-bundle": "^8.0",
        "doctrine/orm": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "App\\Ordering\\":     "src/Ordering/",
            "App\\Billing\\":      "src/Billing/",
            "App\\Inventory\\":    "src/Inventory/",
            "App\\Shipping\\":     "src/Shipping/",
            "App\\SharedKernel\\": "src/SharedKernel/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Tests\\Ordering\\":  "tests/Ordering/",
            "App\\Tests\\Billing\\":   "tests/Billing/"
        }
    }
}
:::

Po úpravě spusťte `composer dump-autoload`. Symfony skeleton očekává
controllery v `App\Controller\`. Pro Modules layout přesuňte
controllery do `App\Ordering\Infrastructure\Http\` a upravte
`config/services.yaml`:

:::code{language="yaml" filename="config/services.yaml (fragment)"}
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Auto-registrace všech služeb v každém modulu, v jejich Infrastructure
    # a Application vrstvách. Doménová vrstva je bez auto-konfigurace
    # – doménové objekty žijí mimo container.
    App\Ordering\Application\:
        resource: '../src/Ordering/Application/'
    App\Ordering\Infrastructure\:
        resource: '../src/Ordering/Infrastructure/'
    App\Billing\Application\:
        resource: '../src/Billing/Application/'
    App\Billing\Infrastructure\:
        resource: '../src/Billing/Infrastructure/'
    # ...

    # Controllery z modulů – Symfony je standardně hledá v App\Controller\
    App\Ordering\Infrastructure\Http\:
        resource: '../src/Ordering/Infrastructure/Http/'
        tags: ['controller.service_arguments']
:::

### Architecture testing s phparkitect {#mod-phparkitect}

Konvence sama o sobě nestačí – vývojáři pod tlakem zapomenou, že
`App\Billing\` nesmí volat `App\Ordering\`. Řešení: **vynutit
pravidlo testem**. Pro PHP existuje knihovna
[phparkitect](https://github.com/phparkitect/arkitect),
která spouští architektonické asercie v CI:

:::code{language="bash" filename="Instalace"}
composer require --dev phparkitect/phparkitect
:::

:::code{language="php" filename="phparkitect.php"}
<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnAnyOfTheseNamespaces;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__ . '/src');

    // Pravidlo 1: Ordering BC nesmí přímo závisět na Billing BC.
    // Integrace musí probíhat přes events (publish/subscribe),
    // nikdy přímým voláním třídy z druhého modulu.
    $orderingIsolated = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\\Ordering'))
        ->should(new NotDependsOnAnyOfTheseNamespaces([
            'App\\Billing',
            'App\\Inventory',
            'App\\Shipping',
        ]))
        ->because(
            'Ordering BC je autonomní – integrace s ostatními BC '
          . 'probíhá výhradně přes domain events (Outbox).',
        );

    // Pravidlo 2: Doménová vrstva nesmí znát infrastrukturu.
    // Žádný import z Doctrine, Symfony HTTP, Mailer, Messenger atd.
    $domainPure = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\\Ordering\\Domain'))
        ->should(new NotDependsOnAnyOfTheseNamespaces([
            'Doctrine',
            'Symfony',
            'App\\Ordering\\Infrastructure',
            'App\\Ordering\\Application',
        ]))
        ->because(
            'Domain layer musí být framework-agnostic; '
          . 'porty se definují jako interface a implementují v Infrastructure.',
        );

    // Pravidlo 3: Application vrstva nesmí znát Infrastructure detaily.
    $applicationCleanArch = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\\Ordering\\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\\Ordering\\Infrastructure',
            'Doctrine\\ORM',
        ]))
        ->because(
            'Application orchestrace závisí na port (interface) z Domain, '
          . 'ne na adapteru z Infrastructure.',
        );

    $config
        ->add($classSet, $orderingIsolated, $domainPure, $applicationCleanArch);
};
:::

:::code{language="bash" filename="CI run"}
# CI runner spustí pravidla a selže build, pokud došlo k porušení.
vendor/bin/phparkitect check --config=phparkitect.php

# Doporučujeme zařadit do CI workflow před fázi "tests":
#   - composer install
#   - vendor/bin/phparkitect check
#   - vendor/bin/phpstan analyse
#   - vendor/bin/phpunit
:::

:::callout{type="pattern"}
### Bez architektonických testů je Modules jen přání {#phparkitect-tip-heading}

Modulární organizace bez vynucení v CI se rozpadá. Stačí 6 měsíců a hot-fix tlak –
refaktoring zpět je pak týdenní práce. Doporučujeme **od prvního commitu** nasadit phparkitect (nebo
`deptrac`, alternativa) a udržovat zelený build. Náklad je nízký
(jeden YAML/PHP soubor v repu), přínos vysoký – modul zůstává modulem,
i když do projektu přijde pátý nový vývojář, který Evansův text nikdy nečetl.

Dokumentace: [phparkitect.com](https://phparkitect.com/);
alternativa [qossmic/deptrac](https://github.com/qossmic/deptrac).
:::

Souvisí: [Horizontální vs. vertikální
dělení](/vertikalni-slice), [Context Mapping](/context-mapping),
[Implementace v Symfony](/implementace-v-symfony),
[Outbox Pattern](/outbox-pattern) (komunikace mezi moduly přes events).

Citace: Evans, E., *Domain-Driven Design* (2003), kapitola 5 *A Model
Expressed in Software*, sekce *Modules*; Vernon, V., *Implementing
Domain-Driven Design* (2013), kapitola 9 *Modules*; phparkitect
dokumentace, [phparkitect.com](https://phparkitect.com/).

## 08.06 Vztah těchto vzorů ke zbytku DDD {#vztahy}

Čtyři vzory této kapitoly nejsou izolované; **vzájemně se podporují**
a skládají s ostatními taktickými vzory do soudržného celku. Mind-mapa vztahů:

| Vzor | Vztah k Aggregate | Vztah k Domain Event | Vztah k Bounded Context |
|---|---|---|---|
| Specification | Validuje invariant agregátu nebo filtruje seznam agregátů | Pravidlo, které spustí event (např. *OrderEligibleForFreeShipping*) | Žije uvnitř BC; obvykle se nesdílí mezi BC |
| Domain Service | Koordinuje 2+ agregáty bez toho, aby je propojila závislostí | Volá agregáty, které pak emitují events | Žije uvnitř BC; cross-BC koordinace patří do Application Service / Saga |
| Factory | Tvoří agregát s validovaným počátečním stavem | Při vzniku obvykle emituje first event (*OrderPlaced*) | Žije uvnitř BC; Factory pro cross-BC objekty neexistuje |
| Module | Seskupuje všechny agregáty BC do jednoho balíčku | Definuje hranici, přes kterou putují events (Outbox) | 1 modul = 1 BC (preferovaná aplikace) |

Hlavní vztah: **Agregát uvnitř používá Specifications** pro invarianty,
**vzniká přes Factory** (named constructor), **spolupracuje s 2+ jinými
agregáty přes Domain Service**, a celá ta skupina **žije v jednom
Module**, který odpovídá Bounded Contextu. To je vzájemně provázaný design,
který nelze správně používat po jednom – proto Evans věnuje všem čtyřem vzorům
samostatné kapitoly.

## 08.07 Anti-vzory souhrn {#antivzory}

Pro rychlou referenci v code review zde shrneme nejčastější anti-vzory, které
v týmu uvidíte. Každý z nich má protilék uvedený v příslušné sekci výše.

| Anti-vzor | Symptom | Náprava |
|---|---|---|
| Specification jako 1-line if | `OrderTotalGreaterThanSpecification` s jediným porovnáním | Inlinujte podmínku; Specification má reprezentovat celou doménovou otázku |
| Specification reimplementující SQL | Specifikace má dvě **nezávislé** verze pravidla – jedno v PHP, druhé v DQL | Použijte double-dispatch (`QuerySpecification`); jedno pravidlo, dva výklady |
| „*Service“ všude | `OrderService`, `CustomerService` obsahuje doménovou logiku, kterou by měla obsahovat Entity | Přesuňte logiku do Entity; Domain Service jen pro operace bez vlastníka |
| Application Service vydávaný za Domain Service | Doménová Service má v konstruktoru `EntityManager` a volá `flush()` | Rozdělte na Domain Service (logika) + Application Handler (orchestrace) |
| Factory pro každý objekt | U každé třídy v doméně existuje samostatná Factory class | Static method (named constructor) v agregátu; Factory class jen pokud nutně potřebujete DI |
| Veřejný konstruktor agregátu | Vně agregátu lze volat `new Order(...)` a obejít validaci | Privátní konstruktor + `::place()` / `::reconstitute()` |
| Type packaging (`src/Entity/`, `src/Service/`) | Adresářová struktura ukazuje technologii, ne doménu | Přejděte na 1 modul = 1 BC; vynuťte phparkitect |
| Modules bez architektury testů | Konvence existují, ale nikdo je nekontroluje – eroze za 6 měsíců | Nasaďte phparkitect/deptrac do CI od prvního commitu |
| Cross-BC import bez ACL | `App\Billing\Invoice` přímo importuje `App\Ordering\Order` | Integrace přes domain events (Outbox); v cílovém BC mapper na lokální typ |

Detailní rozbor doménových anti-vzorů – anémický model, transaction script, „Big
Ball of Mud“ – najdete v kapitole
[Anti-vzory v DDD](/anti-vzory).

## 08.08 Shrnutí {#summary}

Čtyři vzory této kapitoly – Specifications, Domain Services, Factories, Modules –
bývají v praktických průvodcích přehlíženy. Evans jim věnuje desítky stran z dobrého důvodu.
Jsou součástí ucelené sady taktického DDD. Bez nich agregáty bobtnají, doménový model
anemizuje a organizace projektu zatemňuje doménovou strukturu.

- **Specification Pattern** proměňuje booleovská doménová pravidla
  v prvotřídní objekty s mluvícími jmény. Kombinátory `and`,
  `or`, `not` umožňují skládání bez vnořených `if`-ů,
  double-dispatch eliminuje duplikaci pravidla mezi PHP a Doctrine.
- **Domain Services** zachytávají doménovou logiku, která nepatří
  do žádné Entity ani Value Objektu. Jsou stateless, žijí v Domain vrstvě a nesmí
  volat perzistenci. Jejich častá záměna s Application a Infrastructure
  Service je nejčastější příčinou anémického modelu.
- **Factories** řeší komplexní vznik agregátu. Preferovaná forma je
  named constructor (statická metoda na agregátu) s privátním konstruktorem.
  Samostatná Factory class přichází na řadu, jen když potřebujete DI závislosti.
- **Modules** organizují kód podle ubiquitous language, ne podle
  technických vrstev. V Symfony 8 se realizují PSR-4 namespace + `composer.json`
  mapováním na adresáře. Vynucení hranic patří do CI přes phparkitect/deptrac.

Tyto čtyři vzory dohromady udrží agregát štíhlý, doménu výraznou a projekt
čitelný i po roce vývoje. Neimplementují se najednou – nasazení je iterativní.
První iterace stačí: *1 modul = 1 BC*, named constructor pro 2–3 hlavní
agregáty, Domain Service tam, kde jste dosud měli „*Service“ bez
vlastníka. Specifications nasazujte tehdy, když vidíte druhou nebo třetí kombinaci
téhož pravidla.

V další kapitole se podíváme na [výkonové
aspekty DDD](/vykonnostni-aspekty): jak se agregáty chovají při tisících transakcí za sekundu, kde má
DDD overhead a jak ho minimalizovat. Kapitola
[Anti-vzory v DDD](/anti-vzory) doplňuje detail
u anémického modelu, který v sekci 08.03 padl jen krátce.

:::faq{}
- question: 'Kdy přesně se vyplatí Specification Pattern?'
  answer: 'Vyplatí se, když stejné nebo příbuzné pravidlo potřebujete na nejméně dvou místech, případně ho potřebujete v doméně i v repozitáři přes double-dispatch. Pokud pravidlo používáte jednou a obsahuje jeden řádek kódu, je samostatná třída over-engineering – inlinujte ho. Hlavní test: má pravidlo doménové jméno, které tým používá v debatách (<em>premium customer</em>, <em>eligible for free shipping</em>)? Pokud ano, Specification jeho jménu dá kód. Pokud byste třídu pojmenovali <code>OrderTotalGreaterThanSpec</code>, je to jen operátor – vraťte se k inline ifu. Detail v <a href="#spec-kdy">sekci Specification – Kdy použít</a>.'
- question: 'Má Domain Service mít stav?'
  answer: 'Ne. Domain Service je z definice <strong>stateless</strong> – žádné instance variables, žádný interní cache, žádný čítač. Pokud by Domain Service držela stav, ztratí se idempotence a souběžnost. Jediné, co Domain Service smí mít v konstruktoru, jsou jiné stateless služby (typicky další Domain Service nebo immutable hodnota). Vše ostatní (repozitáře, ClockInterface, Mailer) ji posouvá do Application nebo Infrastructure vrstvy. Detail v <a href="#ds-priklad">sekci MoneyTransferService</a> a <a href="#ds-srovnani">srovnávací tabulce</a>.'
- question: 'Factory metoda nebo Factory class – jak se rozhodnout?'
  answer: 'Defaultně volte <strong>named constructor</strong> (statická metoda na agregátu). Vernon (2013) ho výslovně preferuje. K samostatné Factory class přejděte teprve tehdy, když vznik agregátu nutně vyžaduje DI závislosti – typicky <code>CartRepository</code>, <code>PricingService</code>, <code>ClockInterface</code>, externí lookup. Statická metoda totiž tyto závislosti nemůže přijímat bez service locatoru, který je sám anti-vzor. Pokud Factory class neobsahuje žádnou DI závislost a jen volá <code>new Order(...)</code>, je to redundantní vrstva – smazat. Detail v <a href="#fac-class">sekci Factory class</a>.'
- question: 'Jak vynutit hranice mezi Moduly v PHP projektu?'
  answer: 'Konvence sama o sobě se rozpadá – vývojáři pod tlakem „udělej rychle“ přepíšou cross-BC import za 5 minut. Spolehlivé vynucení vyžaduje <strong>nástroj v CI</strong>: <a href="https://phparkitect.com/" target="_blank" rel="noopener">phparkitect</a> nebo <a href="https://github.com/qossmic/deptrac" target="_blank" rel="noopener">deptrac</a>. Definujete pravidla typu „App\\Ordering nesmí závisět na App\\Billing“, „App\\Ordering\\Domain nesmí znát Doctrine“, a CI build selže při porušení. Náklad je jeden konfigurační soubor, zisk je výrazná záruka, že modulární organizace přežije i pátého nového vývojáře. Detail v <a href="#mod-phparkitect">sekci Architecture testing</a>.'
- question: 'Jak má vypadat namespace třídy, která sedí na hranici dvou Bounded Contextů?'
  answer: 'V čistém DDD <strong>žádná třída na hranici dvou BC nesedí</strong>. Pokud objevíte takový případ, je to signál, že hranice je špatně nakreslená nebo že potřebujete <a href="/context-mapping">Anti-Corruption Layer</a> (ACL). Konkrétní řešení: v každém BC žije <em>vlastní</em> typ s vlastním namespace. <code>App\\Ordering\\Domain\\CustomerId</code> v Ordering kontextu, <code>App\\Billing\\Domain\\CustomerId</code> v Billing kontextu, případně mapování přes events. Pokud opravdu existuje univerzální koncept (<code>Money</code>, <code>Currency</code>, <code>Country</code>), patří do <strong>SharedKernel</strong> – ale tento balíček musí být explicitně malý, stabilní a s dohodou všech týmů. Cross-link <a href="#mod-bc">Modul jako Bounded Context</a>.'
- question: 'Můžu Specification a Domain Service kombinovat?'
  answer: 'Ano, a v praxi to často děláte. Domain Service obvykle koordinuje 2+ agregáty, kde jedno z rozhodnutí je vyjádřeno jako Specification – typicky „může tato objednávka projít k expedici?“ = kompozice <code>HasBeenPaid AND ItemsInStock AND NotInBlacklist</code>. Domain Service tu specifikaci instancuje a volá <code>isSatisfiedBy()</code>, podle výsledku zavolá metodu na agregátu. Vzory se vzájemně doplňují: Specification je <em>pravidlo</em>, Domain Service je <em>akce</em>, která pravidlo aplikuje na 2+ agregáty. Detail v <a href="#vztahy">sekci 08.06 Vztah těchto vzorů</a>.'
:::
