---
route: lesser_known_patterns
path: /mene-zname-vzory
title: 'Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules'
page_title: "Specification, Factory, Domain Service, Module | DDD Symfony"
meta_description: "Čtyři doplňkové taktické vzory DDD: Specification pro kompozici pravidel, Domain Service, Factory pro složitý vznik agregátu a Module. S ukázkami v Symfony 8."
meta_keywords: "specification pattern, domain service, factory, module, DDD, taktický design, Eric Evans, Vernon, PoEAA, phparkitect, Symfony 8, PHP 8.4, Doctrine criteria, double dispatch, ubiquitous language, anémický model"
og_type: article
published: "2026-04-29"
modified: "2026-06-13"
breadcrumb_name: Doplňující taktické vzory
schema_type: TechArticle
schema_headline: "Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules"
chapter_number: "08"
category: Taktika
deck: 'Vedle entit, value objektů a agregátů obsahuje Evansova kniha čtyři další taktické vzory, které programátoři často přeskočí: <strong>Specifications</strong> jako prvotřídní booleovská logika, <strong>Domain Services</strong> pro chování bez přirozeného vlastníka, <strong>Factories</strong> pro komplexní vznik agregátů a <strong>Modules</strong> jako vědomá organizace kódu. Tato kapitola je jejich detailní průvodce v Symfony 8 a PHP 8.4 – s ukázkami kódu, anti-vzory a srovnávacími tabulkami.'
reading_time: 28
difficulty: 3
github_examples: Chapter12_LesserPatterns
---

V kapitole [Základní koncepty DDD](/zakladni-koncepty) jsme prošli
čtyři pilíře taktického designu: **Entity**, **Value Object**,
**Aggregate** a stručně i **Domain Service** a **Factory**.
Eric Evans jim věnuje v částech II a III desítky stran. Vývojáři je v průvodcích přeskakují
nebo si je pletou s jinými vzory. Tato kapitola jim vrací plný význam: kdy jsou užitečné,
jak je zapsat v PHP 8.4 a jaká rizika přinášejí při špatném použití.

Čtyři vzory, všechny ukotvené přímo v Evansově knize: **Specification Pattern** (kap. 9) – kompozice doménových predikátů jako prvotřídních objektů. **Domain Services** (kap. 5) zachytávají logiku bez přirozeného vlastníka mezi Entitami a Value Objekty. **Factories** zapouzdřují vznik agregátů se složitými invarianty (kap. 6; u Vernona kap. 11). A **Modules** (rovněž kap. 5) – vědomá organizace kódu podle Ubiquitous Language.

## 08.01 Proč tyto vzory přehlížíme {#proc-prehlizime}

Většina online průvodců o DDD končí někde u Aggregate. Vývojář, který se právě naučil
odlišovat Entity od Value Objektu a chápe význam invariantů, má pocit, že už ovládá
„taktický design“. Specification, Domain Service, Factory a Module se mu pak jeví jako
„nadbytečná abstrakce“. To, co dělají, lze přece napsat i jinak: `if`-em,
statickou metodou nebo prostým balíčkem v `src/`. Intuice je to chybná.

V malých projektech bez těchto vzorů přežijete. Jenže tam, kde je doména
netriviální – tedy přesně tam, kde DDD platí – chybějící vzory způsobují bobtnání agregátů,
anémii modelu a duplikaci pravidel. Kód přestává odrážet doménovou strukturu projektu.
Evansovy čtyři vzory tvoří provázanou sadu. Vyřazením jednoho oslabíte ostatní.

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

Začneme vzorem, který bývá v komunitě nejčastěji přehlížen, přestože mu Evans věnoval
podstatnou část deváté kapitoly – **Specification Pattern**.

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

Rozhraní vypadá triviálně, ale stojí za ním celá architektonická volba. Každé pravidlo
doménového jazyka – *„zákazník je premium“*, *„objednávka má nárok na dopravu zdarma“*,
*„faktura je po splatnosti“* – dostane vlastní třídu s mluvícím jménem. Pravidlo
přestává být kombinací `if`-ů uvnitř service vrstvy a stává se
**jmenovaným prvkem Ubiquitous Language**.

Vzor formálně popsali Evans a Fowler v pracovním papíru *Specifications*
[[martinfowler.com]](https://martinfowler.com/apsupp/spec.pdf); Evans ho později zařadil do
*Domain-Driven Design* (2003), kapitoly 9 *Making Implicit Concepts Explicit*.
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
   **double-dispatch** – obě podoby pravidla (PHP i SQL/Doctrine DQL)
   drží pohromadě v jedné třídě.
3. **Pravidla, která se skládají za běhu.** Promo kód má v admin UI
   podmínky *„platí pro nákupy > 1000 Kč v ČR a SK, kromě výprodejového zboží“*.
   V doméně se reprezentuje jako instance `AndSpecification` složená z N pod-pravidel
   čitelných z databáze.
4. **Pravidla validace agregátu.** Místo aby Aggregate sám kontroloval
   všechny invarianty v setterech, deleguje na specifikaci, která je čitelná samostatně
   i testovatelná v izolaci.

### Kdy NE {#spec-kdy-ne}

Specification je vzor s nezanedbatelnou cenou: každé pravidlo = nová třída, nový soubor,
nový test. Nehodí se pro:

- Triviální podmínky, které se vyskytují **jednou** a obsahují
  **jeden if**: `if ($order->total()->amount > 1000)`
  nepotřebuje vlastní třídu.
- Pravidla, která jsou ve skutečnosti **součástí invariantu Aggregate**
  (a tedy patří přímo do něj jako privátní metoda).
- Konfigurační a technické příznaky – Specification má reprezentovat
  *doménové* pravidlo, ne podmínku *„má feature flag enabled“*.

:::callout{type="warn"}
### Anti-vzor: Specification pro každé porovnání {#spec-anti-heading}

Začátečníci po objevení vzoru často propadnou „efektu kladiva“ a vytvoří
třídy `OrderTotalGreaterThanSpecification`, `OrderTotalLessThanSpecification`,
`OrderTotalEqualsSpecification` – každá obsahuje jeden řádek kódu.
To je over-engineering: ztrácíte čitelnost domény, protože jména přestanou být
doménová a stanou se z nich obecné predikáty. Specification má reprezentovat
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

Na kostře postavíme tři konkrétní pravidla z Ordering kontextu. Každé nese mluvící doménové
jméno a kombinátory `and`/`or`/`not` dědí automaticky:

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
use App\SharedKernel\Domain\Specification\CompositeSpecification;

/**
 * Doručovací adresa objednávky se nachází v členské zemi EU.
 * Seznam zemí je součástí pravidla – specifikace nepotřebuje
 * žádný vstup zvenčí.
 *
 * @extends CompositeSpecification<Order>
 */
final class InEUCountry extends CompositeSpecification
{
    private const array EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        return in_array(
            $candidate->shippingAddress()->country()->code(),
            self::EU_COUNTRIES,
            true,
        );
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

Marketingová akce *„doprava zdarma pro nákupy nad 1000 Kč v EU, kromě zákazníků
na blacklistu“* je trojice atomických specifikací spojená kombinátorem `and`. Vznikne
jedna čitelná řádka místo trojnásobně vnořeného `if`-u:

:::code{language="php" filename="src/Ordering/Application/CommandHandler/ApplyFreeShippingHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\CommandHandler;

use App\Ordering\Domain\Order;
use App\Ordering\Domain\Specification\EligibleForFreeShipping;
use App\Ordering\Domain\Specification\InEUCountry;
use App\Ordering\Domain\Specification\NotInBlacklist;
use App\SharedKernel\Domain\Money;

final class ApplyFreeShippingHandler
{
    public function __construct(private readonly BlacklistRegistry $blacklist) {}

    public function __invoke(Order $order): void
    {
        $promo = (new EligibleForFreeShipping(Money::czk(100_000))) // 1000 Kč v haléřích
            ->and(new InEUCountry())
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
           ->setParameter('threshold', $this->threshold->amount);
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

Obě role specifikace (in-memory predikát i překladač do query) sedí v jedné třídě,
takže když se PHP a DQL podoba začnou rozcházet, je to při code review vidět na jedné obrazovce.
Nic ho ale nevynucuje – jde o dvě nezávislé implementace téhož pravidla. Pojistkou
je kontraktní test: nad stejnou sadou testovacích dat ověří, že `isSatisfiedBy()`
označí tytéž objekty, jaké `match()` vrátí z databáze. Když se obě verze rozejdou,
test selže dřív než produkce.

### Limity skládání: kombinátory a DQL {#spec-query-kombinatory}

Kombinátory `and`/`or`/`not` z úvodu sekce implementují jen `Specification`,
ne `QuerySpecification`. Složená specifikace proto do DQL přeložit nejde –
`match()` ji odmítne už typovou kontrolou. Nabízejí se dvě cesty. Buď skládání
omezíte na in-memory použití a repozitáři předáváte pouze atomické specifikace.
Nebo překlad doplníte i pro kombinátory; u `AndSpecification` stačí přeložit obě
strany, protože `andWhere()` připojuje podmínky konjunkcí:

:::code{language="php" filename="src/SharedKernel/Domain/Specification/AndSpecification.php (s překladem do DQL)"}
final class AndSpecification extends CompositeSpecification implements QuerySpecification
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

    public function asDoctrineCriteria(QueryBuilder $qb, string $alias): void
    {
        if (!$this->left instanceof QuerySpecification
            || !$this->right instanceof QuerySpecification
        ) {
            throw new \LogicException(
                'Do DQL lze přeložit jen kompozici QuerySpecification.',
            );
        }

        $this->left->asDoctrineCriteria($qb, $alias);
        $this->right->asDoctrineCriteria($qb, $alias);
    }
}
:::

Jedna past zůstává i u konjunkce: názvy parametrů. Dvě pod-specifikace, které
obě zavolají `setParameter('threshold', ...)`, se tiše přepíšou – vyhraje
poslední hodnota a dotaz vrátí špatné výsledky bez jediné chyby. Atomické
specifikace proto parametry pojmenovávají s unikátním prefixem nebo suffixem
(`largeOrder_threshold`), ne generickým jménem.

U `OrSpecification` a `NotSpecification` už tak levně nevyjdete. Podmínky nelze
jen řadit za sebe – musíte skládat výrazové fragmenty přes
`$qb->expr()`. V tom bodě se generický překlad
přestává vyplácet: pro složitější dotaz je čitelnější vlastní query metoda
repozitáře (`findOrdersEligibleForPromo()`), která pravidlo zapíše v DQL přímo
a kontraktním testem se sváže s in-memory specifikací.

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
Entity ani Value Objektu jako její přirozená metoda, (2) její rozhraní je definováno
pomocí jiných prvků doménového modelu a (3) nemá vlastní stav.

Existuje tedy operace *X*, ale žádná Entita ji nemůže vlastnit, aniž by musela
znát příliš mnoho o druhé. To je signál pro Domain Service.

### Kdy použít {#ds-kdy}

Klasické příklady, na kterých Evans i Vernon vzor demonstrují:

Vezměme **Funds Transfer**, převod peněz mezi dvěma účty. Patří do agregátu
`Account`? Ani jeden z účtů nezná ten druhý a ani jeden není přirozeným vlastníkem
operace. Jde o doménový koncept sám o sobě. Podobně **pricing engine** počítá cenu
objednávky z pricing pravidel, segmentu zákazníka, košíku a kupónu – žádný z těchto
objektů není přirozeným vlastníkem výpočtu. Stejnou povahu má i **credit scoring**:
odpověď na *„má tento zákazník nárok na úvěr X?“* vzniká kombinací několika faktorů.

A čtvrtý typický případ je **koordinátor dvou agregátů** – operace, která mění stav
dvou agregátů zároveň, kde žádný z nich nesmí znát detaily druhého (autonomie agregátů).

### Kdy NE {#ds-kdy-ne}

Domain Service je v DDD vzor, který se zneužívá nejčastěji.
Vývojáři navyklí na klasickou layered architecture vytvoří
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
   (transakce, autorizace, eventy). Pokud byste musel v „doménové“ service
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
 * Stateless – bez instance variables, bez vedlejších efektů na kolaborátorech.
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

V kódu se třída se sufixem `Service` vyskytne téměř vždy.
Liší se jen v tom, kterou ze tří rolí hraje. Následující srovnávací tabulka shrnuje
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

Pojmenování všech tříd sufixem `*Service` smaže rozdíl mezi třemi rolemi z tabulky.
V Application vrstvě se proto v praxi přechází na `*Handler` nebo `*UseCase`. Doménová
Service má sufix *Service* jen tehdy, když pomáhá zdůraznit „operace bez vlastníka“.
V mnoha doménách i u Domain Service zvolíme přímo doménové jméno
(`FundsTransfer`, `PricingEngine`) bez sufixu.

:::callout{type="pattern"}
### Praktický tip: hraniční případ {#ds-tip-heading}

Když nedokážete jednoznačně rozhodnout, zda je třída Domain nebo Application Service,
obvykle to znamená, že **míchá obě role**. Rozdělte ji: doménovou
logiku do Domain Service v `Domain/Service/`, koordinaci do command
handleru v `Application/CommandHandler/`. Test pro Domain Service
ať proběhne bez Symfony kernelu – pokud nemůže, zbyl tam infrastrukturní leak.
:::

Tematicky souvisí: [Základní koncepty – Doménové služby](/zakladni-koncepty#domain-services), [Anti-vzor: Anemic Domain
Model](/anti-vzory), [CQRS – Application Handler](/cqrs).

Citace: Evans, E., *Domain-Driven Design* (2003), kapitola 5
*A Model Expressed in Software*; Vernon, V., *Implementing Domain-Driven
Design* (2013), kapitola 7.

## 08.04 Factories {#factories}

### Co to je {#fac-definice}

**Factory** v terminologii DDD je zapouzdření **komplexní logiky vzniku
agregátu nebo Value Objektu**, kde standardní konstruktor nestačí. Eric Evans
v kapitole 6 *Domain-Driven Design* (2003) doporučuje přesunout odpovědnost za
vytváření složitých objektů a agregátů na samostatný objekt, zvlášť když vznik
vyžaduje pravidla nebo polymorfismus.

Standardní konstruktor stačí pro většinu agregátů. Factory je řešení pro
situace, kdy:

- Vznik agregátu vyžaduje validaci, kterou nelze provést až *po* konstrukci
  (např. *„nový Order musí mít alespoň 1 položku, jinak agregát neexistuje“*).
- Vznik je polymorfní – z různých vstupů vznikají různé pod-typy stejného agregátu
  (například `Order::physical()` vs. `Order::digital()`).
- Vznik vyžaduje externí lookup – z REST API přijde surový e-mail, Factory ho převede
  na `CustomerId` přes `CustomerLookup`.
- Mapování z DTO/raw payload je natolik spletité, že by zaplevelilo konstruktor
  doménového objektu detaily transportní vrstvy.

### Kdy NE {#fac-kdy-ne}

Většinu objektů jde přímočaře vytvořit konstruktorem. Factory má smysl teprve tehdy, když
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
        // Konstruktor jen plní stav. Eventy zaznamenávají factory metody –
        // konstruktorem prochází i reconstitute(), která žádný event vyvolat nesmí.
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

        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: $items,
            type: OrderType::Physical,
            placedAt: $placedAt,
        );
        $order->record(new OrderPlaced($order->id, $customerId, $placedAt));

        return $order;
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

        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: array_map(static fn (DigitalItem $i): OrderItem => $i->toOrderItem(), $items),
            type: OrderType::Digital,
            placedAt: $placedAt,
        );
        $order->record(new OrderPlaced($order->id, $customerId, $placedAt));

        return $order;
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

Vaughn Vernon v kapitole 11 *Implementing Domain-Driven Design* (2013) preferuje
factory metodu na samotném agregátu; samostatnou Factory class doporučuje až tehdy,
když vznik nutně potřebuje závislosti, které nelze poskytnout parametrem. Důvod je
prostý: statická metoda na agregátu
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

Proto také `OrderPlaced` zaznamenává factory metoda `::place()`, ne konstruktor.
Rekonstituce nesmí mít vedlejší efekty: obnovuje stav, žádná doménová událost se
nestala. Kdyby event zaznamenával konstruktor, každé načtení agregátu z databáze
by znovu vyprodukovalo `OrderPlaced` a odběratelé by tutéž objednávku „umístili“
při každém čtení.

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
do balíčků pojmenovaných podle Ubiquitous Language**. Není to PHP feature, není
to namespace – je to *princip*, který říká: *„rozhraní balíčků vašeho kódu má
odrážet doménový jazyk, ne technické vrstvy a ne použité knihovny.“*

Evans věnoval Modules samostatnou pasáž v kapitole 5 *Domain-Driven
Design* (2003). Moduly chápe jako vyjádření hrubší struktury modelu:
členění balíčků má vycházet z doménového jazyka, ne z technické
organizace kódu.

V Symfony 8 a PHP 8.4 to konkrétně znamená:

- **PSR-4 namespace + uspořádání složek** podle
  [Bounded Contextů](/zakladni-koncepty#bounded-contexts).
- **`composer.json` autoload sekce**, která mapuje namespace
  `App\Ordering\` na `src/Ordering/` – ne na
  `src/` jako ve výchozím Symfony skeletu.
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

Této organizaci se v komunitě říká také *vertical slicing* – viz sekci
[Vertical Slice Architecture](/architektonicke-styly#vertical-slice),
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
`composer.json`. Výchozí Symfony nastavení mapuje `App\` na
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
controllery v `App\Controller\`. Pro modulové uspořádání přesuňte
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

### Architecture testing: hranice vynucené v CI {#mod-phparkitect}

Konvence sama o sobě nestačí – vývojáři pod tlakem zapomenou, že
`App\Billing\` nesmí volat `App\Ordering\`. Řešení: **vynutit
pravidlo testem**, který běží v CI a při porušení shodí build. Princip
je u všech nástrojů stejný: pravidla závislostí zapíšete jako definice
verzované vedle kódu a pipeline je kontroluje při každém commitu.
Pro modulový projekt z této kapitoly jde typicky o tři pravidla:

1. `App\Ordering` nesmí záviset na `App\Billing`, `App\Inventory` ani
   `App\Shipping` – integrace mezi BC probíhá výhradně přes domain events
   ([Outbox](/outbox-pattern)).
2. `App\Ordering\Domain` nesmí importovat nic z `Doctrine`, `Symfony`
   ani z vlastní Application a Infrastructure vrstvy – doména zůstává
   framework-agnostic.
3. `App\Ordering\Application` nesmí znát `App\Ordering\Infrastructure` –
   orchestrace závisí na rozhraní z Domain, ne na adaptéru.

Pro PHP existují dva zavedené nástroje.
[phparkitect](https://github.com/phparkitect/arkitect) zapisuje pravidla
jako PHP definice (fluent API nad množinou tříd) v souboru
`phparkitect.php` v kořeni projektu:

:::code{language="bash" filename="Instalace a spuštění"}
composer require --dev phparkitect/phparkitect
vendor/bin/phparkitect check
:::

Druhou možností je [Deptrac](https://github.com/deptrac/deptrac), který
vrstvy a povolené závislosti popisuje v YAML souboru. Kompletní Deptrac
konfiguraci pro DDD projekt včetně zapojení do CI najdete v kapitole
[Testování DDD](/testovani-ddd#architektonicke-testy). Zápis pravidel se
mezi nástroji liší, tři pravidla výše vyjádří oba.

:::callout{type="pattern"}
### Bez architektonických testů je Modules jen přání {#phparkitect-tip-heading}

Modulární organizace bez vynucení v CI se rozpadá. Stačí 6 měsíců a hot-fix tlak –
refaktoring zpět je pak týdenní práce. Doporučujeme **od prvního commitu** nasadit phparkitect (nebo
`deptrac`, alternativa) a udržovat zelený build. Náklad je nízký
(jeden YAML/PHP soubor v repu), přínos vysoký – modul zůstává modulem,
i když do projektu přijde pátý nový vývojář, který Evansův text nikdy nečetl.

Dokumentace: [github.com/phparkitect/arkitect](https://github.com/phparkitect/arkitect);
alternativa [deptrac/deptrac](https://github.com/deptrac/deptrac).
:::

Souvisí: [Horizontální vs. vertikální
dělení](/architektonicke-styly#vertical-slice), [Context Mapping](/context-mapping),
[Implementace v Symfony](/implementace-v-symfony),
[Outbox Pattern](/outbox-pattern) (komunikace mezi moduly přes events).

Citace: Evans, E., *Domain-Driven Design* (2003), kapitola 5 *A Model
Expressed in Software*, sekce *Modules*; Vernon, V., *Implementing
Domain-Driven Design* (2013), kapitola 9 *Modules*; phparkitect
dokumentace, [github.com/phparkitect/arkitect](https://github.com/phparkitect/arkitect).

## 08.06 Vztah těchto vzorů ke zbytku DDD {#vztahy}

Čtyři vzory této kapitoly se prolínají s ostatními taktickými vzory.
Tabulka shrnuje, jak každý z nich sedí do triády Aggregate / Domain Event /
Bounded Context:

| Vzor | Vztah k Aggregate | Vztah k Domain Event | Vztah k Bounded Context |
|---|---|---|---|
| Specification | Validuje invariant agregátu nebo filtruje seznam agregátů | Pravidlo, které spustí event (např. *OrderEligibleForFreeShipping*) | Žije uvnitř BC; obvykle se nesdílí mezi BC |
| Domain Service | Koordinuje 2+ agregáty bez toho, aby je propojila závislostí | Volá agregáty, které pak emitují events | Žije uvnitř BC; cross-BC koordinace patří do Application Service / Saga |
| Factory | Tvoří agregát s validovaným počátečním stavem | Při vzniku obvykle emituje first event (*OrderPlaced*) | Žije uvnitř BC; Factory pro cross-BC objekty neexistuje |
| Module | Seskupuje všechny agregáty BC do jednoho balíčku | Definuje hranici, přes kterou putují events (Outbox) | 1 modul = 1 BC (preferovaná aplikace) |

Hlavní vztah: **Agregát uvnitř používá Specifications** pro invarianty,
**vzniká přes Factory** (named constructor), **spolupracuje s 2+ jinými
agregáty přes Domain Service**, a celá ta skupina **žije v jednom
Module**, který odpovídá Bounded Contextu. Vzory se vzájemně předpokládají.
Když jeden vyřežete a zbytek používáte samostatně, přínos všech klesne.

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

Specifications, Domain Services, Factories a Modules tvoří druhou polovinu
Evansova taktického katalogu. Praktické průvodce je vynechávají, ale bez nich
agregáty bobtnají, doménový model upadá do anémie a organizace projektu zatemňuje
doménovou strukturu.

- **Specification Pattern** proměňuje booleovská doménová pravidla
  v prvotřídní objekty s mluvícími jmény. Kombinátory `and`,
  `or`, `not` umožňují skládání bez vnořených `if`-ů,
  double-dispatch drží PHP i DQL podobu pravidla v jedné třídě.
- **Domain Services** zachytávají doménovou logiku, která nepatří
  do žádné Entity ani Value Objektu. Jsou stateless, žijí v Domain vrstvě a nesmí
  volat perzistenci. Jejich častá záměna s Application a Infrastructure
  Service je nejčastější příčinou anémického modelu.
- **Factories** řeší komplexní vznik agregátu. Preferovaná forma je
  named constructor (statická metoda na agregátu) s privátním konstruktorem.
  Samostatná Factory class přichází na řadu, jen když potřebujete DI závislosti.
- **Modules** organizují kód podle Ubiquitous Language, ne podle
  technických vrstev. V Symfony 8 se realizují PSR-4 namespace + `composer.json`
  mapováním na adresáře. Vynucení hranic patří do CI přes phparkitect/deptrac.

Společně drží agregát v rozumné velikosti, doménu oddělenou od infrastruktury
a projekt čitelný po roce vývoje. Nasazují se postupně, po jednom.
První iterace stačí: *1 modul = 1 BC*, named constructor pro 2–3 hlavní
agregáty, Domain Service tam, kde jste dosud měli „*Service“ bez
vlastníka. Specifications dávají smysl ve chvíli, kdy se objeví druhá nebo třetí kombinace
téhož pravidla.

V další kapitole se podíváme na [výkonové
aspekty DDD](/vykonnostni-aspekty): jak se agregáty chovají při tisících transakcí za sekundu, kde má
DDD overhead a jak ho minimalizovat. Kapitola
[Anti-vzory v DDD](/anti-vzory) doplňuje detail
u anémického modelu, který v sekci 08.03 padl jen krátce.

:::faq{}
- question: 'Kdy přesně se vyplatí Specification Pattern?'
  answer: 'Vyplatí se, když stejné nebo příbuzné pravidlo potřebujete na nejméně dvou místech, případně ho uplatňujete v doméně i v repozitáři přes double-dispatch. Pokud pravidlo používáte jednou a obsahuje jeden řádek kódu, je samostatná třída over-engineering – inlinujte ho. Hlavní test: má pravidlo doménové jméno, které tým používá v debatách (<em>premium customer</em>, <em>eligible for free shipping</em>)? Pokud ano, Specification jeho jménu dá kód. Pokud byste třídu pojmenovali <code>OrderTotalGreaterThanSpec</code>, je to jen operátor – vraťte se k inline ifu. Detail v <a href="#spec-kdy">sekci Specification – Kdy použít</a>.'
- question: 'Má Domain Service mít stav?'
  answer: 'Ne. Domain Service je z definice <strong>stateless</strong> – žádné instance variables, žádný interní cache, žádný čítač. Pokud by Domain Service držela stav, ztratí se idempotence a souběžnost. Jediné, co Domain Service smí mít v konstruktoru, jsou jiné stateless služby (typicky další Domain Service nebo immutable hodnota). Vše ostatní (repozitáře, ClockInterface, Mailer) ji posouvá do Application nebo Infrastructure vrstvy. Detail v <a href="#ds-priklad">sekci MoneyTransferService</a> a <a href="#ds-srovnani">srovnávací tabulce</a>.'
- question: 'Factory metoda nebo Factory class – jak se rozhodnout?'
  answer: 'Defaultně volte <strong>named constructor</strong> (statická metoda na agregátu). Vernon (2013) ho výslovně preferuje. K samostatné Factory class přejděte teprve tehdy, když vznik agregátu nutně vyžaduje DI závislosti – typicky <code>CartRepository</code>, <code>PricingService</code>, <code>ClockInterface</code>, externí lookup. Statická metoda totiž tyto závislosti nemůže přijímat bez service locatoru, který je sám anti-vzor. Pokud Factory class neobsahuje žádnou DI závislost a jen volá <code>new Order(...)</code>, je to redundantní vrstva – smazat. Detail v <a href="#fac-class">sekci Factory class</a>.'
- question: 'Jak vynutit hranice mezi Moduly v PHP projektu?'
  answer: 'Konvence sama o sobě se rozpadá – vývojáři pod tlakem „udělej rychle“ přepíšou cross-BC import za 5 minut. Spolehlivé vynucení vyžaduje <strong>nástroj v CI</strong>: <a href="https://github.com/phparkitect/arkitect" target="_blank" rel="noopener">phparkitect</a> nebo <a href="https://github.com/deptrac/deptrac" target="_blank" rel="noopener">deptrac</a>. Definujete pravidla typu „App\\Ordering nesmí závisět na App\\Billing“, „App\\Ordering\\Domain nesmí znát Doctrine“, a CI build selže při porušení. Náklad je jeden konfigurační soubor, zisk je jistota, že modulární organizace přežije i pátého nového vývojáře. Detail v <a href="#mod-phparkitect">sekci Architecture testing</a>.'
- question: 'Jak má vypadat namespace třídy, která sedí na hranici dvou Bounded Contextů?'
  answer: 'V čistém DDD <strong>žádná třída na hranici dvou BC nesedí</strong>. Pokud objevíte takový případ, je to signál, že hranice je špatně nakreslená nebo že potřebujete <a href="/context-mapping">Anti-Corruption Layer</a> (ACL). Konkrétní řešení: v každém BC žije <em>vlastní</em> typ s vlastním namespace. <code>App\\Ordering\\Domain\\CustomerId</code> v Ordering kontextu, <code>App\\Billing\\Domain\\CustomerId</code> v Billing kontextu, případně mapování přes events. Pokud opravdu existuje univerzální koncept (<code>Money</code>, <code>Currency</code>, <code>Country</code>), patří do <strong>SharedKernel</strong> – ale tento balíček musí být explicitně malý, stabilní a s dohodou všech týmů. Souvisí <a href="#mod-bc">Modul jako Bounded Context</a>.'
- question: 'Můžu Specification a Domain Service kombinovat?'
  answer: 'Ano, a v praxi to často děláte. Domain Service obvykle koordinuje 2+ agregáty, kde jedno z rozhodnutí je vyjádřeno jako Specification – typicky „může tato objednávka projít k expedici?“ = kompozice <code>HasBeenPaid AND ItemsInStock AND NotInBlacklist</code>. Domain Service tu specifikaci instancuje a volá <code>isSatisfiedBy()</code>, podle výsledku zavolá metodu na agregátu. Vzory se vzájemně doplňují: Specification je <em>pravidlo</em>, Domain Service je <em>akce</em>, která pravidlo aplikuje na 2+ agregáty. Detail v <a href="#vztahy">sekci 08.06 Vztah těchto vzorů</a>.'
:::
