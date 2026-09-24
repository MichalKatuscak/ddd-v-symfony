---
route: lesser_known_patterns
path: /mene-zname-vzory
title: 'Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules'
page_title: "Specification, Factory, Domain Service, Module | DDD Symfony"
meta_description: "Čtyři doplňkové taktické vzory DDD: Specification pro kompozici pravidel, Domain Service, Factory pro složitý vznik agregátu a Module. S ukázkami v Symfony 8."
meta_keywords: "specification pattern, domain service, factory, module, DDD, taktický design, Eric Evans, Vernon, PoEAA, phparkitect, Symfony 8, PHP 8.4, Doctrine criteria, double dispatch, ubiquitous language, anémický model"
og_type: article
published: "2026-04-29"
modified: 2026-09-23
breadcrumb_name: Doplňující taktické vzory
schema_type: TechArticle
schema_headline: "Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules"
chapter_number: "08"
category: Taktika
deck: 'Vedle entit, value objektů a agregátů obsahuje Evansova kniha čtyři další taktické vzory, které programátoři často přeskočí: <strong>Specifications</strong> jako prvotřídní booleovská logika, <strong>Domain Services</strong> pro chování bez přirozeného vlastníka, <strong>Factories</strong> pro komplexní vznik agregátů a <strong>Modules</strong> jako vědomá organizace kódu. Tato kapitola je jejich detailní průvodce v Symfony 8 a PHP 8.4 – s ukázkami kódu, anti-vzory a srovnávacími tabulkami.'
reading_time: 37
difficulty: 3
github_examples: Chapter12_LesserPatterns
---

Kapitola [Základní koncepty DDD](/zakladni-koncepty) probrala **entity**, **hodnotové
objekty**, **agregáty** a jen stručně **doménové služby** a **factories**. Evans přitom
službám, factories, specifikacím a modulům věnuje v částech II a III desítky stran. Průvodci je přeskakují nebo je
zaměňují s jinými vzory. Tato kapitola ukazuje, kdy jsou užitečné, jak je zapsat v PHP 8.4
a co stojí jejich špatné použití.

Všechny čtyři vzory pocházejí přímo z Evansovy knihy. **Specification** (kap. 9) dělá
z doménových predikátů skládatelné objekty. **Domain Service** (kap. 5) zachytává logiku
bez přirozeného vlastníka mezi entitami a hodnotovými objekty. **Factory** zapouzdřuje
vznik agregátů se složitými invarianty (kap. 6; u Vernona kap. 11). **Module** (rovněž
kap. 5) organizuje kód podle Ubiquitous Language.

## 08.01 Proč tyto vzory přehlížíme {#proc-prehlizime}

Většina online průvodců o DDD končí u agregátu. Vývojář, který umí odlišit entitu
od hodnotového objektu a rozumí invariantům, má pocit, že taktický design ovládá.
Specification, Domain Service, Factory a Module mu pak připadají jako nadbytečná
abstrakce, protože totéž jde napsat `if`-em, statickou metodou nebo prostým balíčkem
v `src/`. Ta intuice klame.

Malý projekt se bez těchto vzorů obejde. V netriviální doméně, tedy tam, kde se DDD
vyplácí, jejich absence vede k bobtnání agregátů, anémii modelu a duplikaci pravidel.
Kód pak přestává odrážet strukturu domény. Vzory přitom na sebe navazují, jak ukazuje
sekce [08.06](#vztahy).

:::callout{type="note"}
### Co od kapitoly očekávat {#prehled-heading}

Každý vzor prochází stejnými čtyřmi otázkami: co přesně je podle Evanse a Vernona,
kdy se použije, kdy ne (anti-vzory a over-engineering) a jak vypadá v Symfony 8
a PHP 8.4. U Domain Service přibývá srovnávací tabulka s Application a Infrastructure
Service. Závěr kapitoly shrnuje anti-vzory a odkazuje na související kapitoly.
:::

## 08.02 Specification Pattern {#specification}

### Co to je {#spec-definice}

**Specification** je samostatný objekt, který zapouzdřuje jeden booleovský
predikát nad doménovým objektem – typicky odpověď na otázku „splňuje tento agregát
konkrétní pravidlo?“. Minimální rozhraní:

:::code{language="php" filename="src/SharedKernel/Domain/Specification/Specification.php (jádro vzoru)"}
interface Specification
{
    public function isSatisfiedBy(mixed $candidate): bool;
}
:::

Rozhraní je triviální, rozhodnutí za ním ne. Každé pravidlo doménového jazyka dostane
vlastní třídu s mluvícím jménem: *„zákazník je premium“*, *„objednávka má nárok na dopravu
zdarma“*, *„faktura je po splatnosti“*. Pravidlo přestává být kombinací `if`-ů v service
vrstvě a stává se **pojmenovaným prvkem Ubiquitous Language**.

Vzor formálně popsali Evans a Fowler v pracovním papíru *Specifications*
[[martinfowler.com]](https://martinfowler.com/apsupp/spec.pdf) z roku 1997; Evans ho později
zařadil do *Domain-Driven Design* (2003), kapitoly 9 *Making Implicit Concepts Explicit*.
Východisko je v obou textech stejné: pravidlo, které se v doméně opakuje, si zaslouží
vlastní jméno a vlastní typ.

Jedna poznámka ke zdrojům. V destilovaném *DDD Reference* (2015) už Specification není.
Evans do něj z taktických stavebních bloků zařadil Entities, Value Objects, Domain Events,
Services, Modules, Aggregates, Repositories a Factories. Vzor tedy nepřeskakuje jen praxe;
vypadl i z autorova vlastního souhrnu.

### Kdy použít {#spec-kdy}

Původní papír pojmenovává tři použití vzoru. **Selection** vybírá podmnožinu objektů podle
kritéria a umí výběr kdykoliv obnovit. **Validation** ověřuje, že objekt je pro daný účel
vhodný. **Construction-to-order** popisuje, jak má objekt vypadat, aniž řeší, jak takový
objekt vyrobit; z popisu se dá kandidát sestavit na zakázku. V Symfony projektu se tato
použití objevují ve čtyřech typických situacích:

1. **Komplexní doménová pravidla, která se mají skládat.** Pokud se
   v různých částech aplikace vrací tentýž motiv v jiné kombinaci – někde
   „*premium AND v EU*“, jinde „*premium OR má slevový kód*“ –
   kompozice pomocí Specification ušetří duplikaci a udrží pravidla
   konzistentní.
2. **Pravidla použitelná v doméně i v repozitáři.** Tatáž specifikace
   odpoví na „*splňuje tento konkrétní objekt pravidlo?*“ (in-memory predikát)
   a zároveň vrátí z databáze všechny objekty, které pravidlo splňují (query). Obě podoby pravidla (PHP i SQL/Doctrine
   DQL) drží pohromadě v jedné třídě; **double-dispatch** přijde ke slovu
   při předání specifikace repozitáři (viz
   [Double-dispatch do Doctrine](#spec-doctrine)).
3. **Pravidla, která se skládají za běhu.** Promo kód má v admin UI
   podmínky *„platí pro nákupy > 1000 Kč v ČR a SK, kromě výprodejového zboží“*.
   V doméně se reprezentuje jako instance `AndSpecification` složená z N pod-pravidel
   čitelných z databáze.
4. **Pravidlo validace agregátu.** Agregát nekontroluje všechno sám v setterech,
   ale deleguje na specifikaci, kterou jde číst i testovat samostatně.

Papír k tomu přidává tři implementační strategie, které se liší cenou. *Hard Coded Specification* je jedna třída na jedno pravidlo, bez parametrů –
levná, ale roste s počtem pravidel. *Parameterized Specification* skládá pravidlo za běhu
z hodnot; přesně to dělá bod 3 s promo kódem čteným z databáze. *Composite Specification*
přidává uzly `and`, `or`, `not` a čte pravidlo jako výraz. Evans s Fowlerem u ní uvádějí
i cenu: kompozit je pružný bez spousty specializovaných tříd, ale vyžaduje investici
do frameworku. Zbytek sekce ten framework ukazuje, aby bylo vidět, co investice obnáší.

### Kdy NE {#spec-kdy-ne}

Specification má nezanedbatelnou cenu: každé pravidlo znamená novou třídu, nový soubor
a nový test. Nehodí se pro:

- Triviální podmínky, které se vyskytují **jednou** a obsahují
  **jeden if**: `if ($order->totalAmount()->amountInCents > 100_000)`
  nepotřebuje vlastní třídu.
- Pravidla, která jsou ve skutečnosti **součástí invariantu Aggregate**
  (a tedy patří přímo do něj jako privátní metoda).
- Konfigurační a technické příznaky – Specification má reprezentovat
  *doménové* pravidlo, ne podmínku *„má feature flag enabled“*.

Papír Evanse a Fowlera má vlastní sekci *When Not to Use Specification* a její kritérium je
ostřejší než výčet výše. Jestliže objekt reprezentuje skutečnou entitu domény místo toho,
aby kladl podmínky na jinou, případně jen hypotetickou entitu, vzor tam nepatří. Autoři to
ilustrují dvojicí *Route* a *Route Specification*: trasa je věc, kterou doména zná a která
má identitu. Specifikace trasy jen popisuje, jaká trasa by vyhovovala – žádná taková zatím
existovat nemusí.

:::callout{type="warn"}
### Anti-vzor: Specification pro každé porovnání {#spec-anti-heading}

Kdo vzor právě objevil, často vytvoří třídy `OrderTotalGreaterThanSpecification`,
`OrderTotalLessThanSpecification` a `OrderTotalEqualsSpecification` s jedním řádkem
kódu v každé. Je to over-engineering: jména přestanou být doménová, stanou se z nich
obecné predikáty a doména se z kódu přestane dát vyčíst.

Rozdíl je v rovině, ve které třída stojí. Doménové jméno má nést *výsledná* specifikace –
ta, kterou předáváte dál a která odpovídá na celou otázku. Generické parametrizované listy
uvnitř kompozitu legitimní jsou; Evans s Fowlerem u *Composite Specification* přesně takové
uzly předpokládají a jejich vlastní ukázkou je `MaximumTemperatureSpecification`. Problém
nastane, až generický predikát vyleze ven a stane se rozhraním, kterým se doména ptá.
:::

### Skladba pomocí kombinátorů {#spec-diagram}

Síla vzoru je ve **skládání** přes booleovské kombinátory `and`, `or` a `not`.
Místo klubka `if`-ů a `else`-ů vznikne algebraický výraz nad pojmenovanými atomy.
Hierarchie tříd:

:::diagram{fig="08.2-A" title="Specification Pattern: kompozice booleovské logiky" src="images/diagrams/16_lesser_patterns/specification_compose.svg"}
:::

### Interface a abstraktní kompozit {#spec-interface}

Plné rozhraní vystavuje všechny tři kombinátory. Rozšiřuje jednometodovou verzi
z úvodu sekce a ve zbytku kapitoly ji nahrazuje. Abstraktní třída pak kombinátory
implementuje přes `AndSpecification`, `OrSpecification` a `NotSpecification`:

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

Konkrétní specifikace kombinátory neimplementují, dodá je abstraktní třída:

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

Na kostře stojí tři konkrétní pravidla z kontextu Ordering. Každé nese mluvící doménové
jméno a kombinátory `and`/`or`/`not` dědí.

Specifikace čtou z agregátu `totalAmount()`, `customerId` a `shippingAddress`. První dvě
má kanonický `Order` z [Návrhu agregátu](/navrh-agregatu#symfony-doctrine), třetí ne.
Tamní kapitola ukazuje `ShippingAddress` jen jako embeddable hodnotový objekt a agregát
ji nenese. Příklady zde počítají s objednávkou rozšířenou o vlastnost
`public readonly ShippingAddress $shippingAddress`.

:::code{language="php" filename="src/Ordering/Domain/Specification/EligibleForFreeShipping.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Specification;

use App\Ordering\Domain\Model\Order;
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

        $total = $candidate->totalAmount();

        return $total->currency === $this->threshold->currency
            && $total->amountInCents >= $this->threshold->amountInCents;
    }
}
:::

:::code{language="php" filename="src/Ordering/Domain/Specification/InEUCountry.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Specification;

use App\Ordering\Domain\Model\Order;
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
            $candidate->shippingAddress->countryCode,
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

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\CustomerId;
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
            if ($blocked->equals($candidate->customerId)) {
                return false;
            }
        }

        return true;
    }
}
:::

Volání `assert($candidate instanceof Order)` v ukázkách zužuje typ pro statickou analýzu,
ne pro běh. S `zend.assertions=-1` se v produkci vůbec nezkompiluje, takže runtime pojistka
to není. Skutečnou kontrolu dělá typový parametr `@template T` spolu s PHPStan nebo Psalm,
které kompozici hlídají staticky.

### Kompozice v aplikační vrstvě {#spec-compose}

Marketingová akce *„doprava zdarma pro nákupy nad 1000 Kč v EU, kromě zákazníků
na blacklistu“* je trojice atomických specifikací spojená kombinátorem `and`. Místo
trojnásobně vnořeného `if`-u vznikne jeden čitelný výraz:

:::code{language="php" filename="src/Ordering/Application/Service/FreeShippingPolicy.php + Application/BlacklistRegistry.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Service;

use App\Ordering\Application\BlacklistRegistry;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Specification\EligibleForFreeShipping;
use App\Ordering\Domain\Specification\InEUCountry;
use App\Ordering\Domain\Specification\NotInBlacklist;
use App\SharedKernel\Domain\Currency;
use App\SharedKernel\Domain\Money;

final class FreeShippingPolicy
{
    public function __construct(private readonly BlacklistRegistry $blacklist) {}

    // Politika odpovídá, nemění stav. Co s nárokem udělat (nulové dopravné,
    // slevový řádek), rozhoduje handler checkoutu, který ji volá.
    public function isEligible(Order $order): bool
    {
        // 1000 Kč v haléřích – Money drží částku jako celé číslo.
        $promo = (new EligibleForFreeShipping(new Money(100_000, Currency::CZK)))
            ->and(new InEUCountry())
            ->and(new NotInBlacklist($this->blacklist->all()));

        return $promo->isSatisfiedBy($order);
    }
}

// --- src/Ordering/Application/BlacklistRegistry.php ---
namespace App\Ordering\Application;

use App\Ordering\Domain\ValueObject\CustomerId;

// Port: seznam zákazníků na blacklistu dodává Infrastructure vrstva
// (fraud detection, ručně vedený seznam). Specifikace dostává hotový list.
interface BlacklistRegistry
{
    /** @return list<CustomerId> */
    public function all(): array;
}
:::

V testu jde pravidlo rozložit na atomy a ověřit každý zvlášť. Když produktový tým
rozhodne, že se blacklist nemá kontrolovat, z kompozice zmizí jeden řádek a nikdo
nemusí pročítat sevřený `if` hluboko v service vrstvě.

Politika vrací `bool` a agregát nechává na pokoji. Výsledek spotřebuje handler
checkoutu: nulové dopravné dosadí do výpočtu ceny, nebo ho zapíše jako slevový řádek.
Kanonický `Order` tak nepotřebuje žádnou metodu navíc a specifikace zůstává čistým
dotazem nad stavem.

### Dva vzory z papíru, které se neujaly {#spec-subsumption}

Papír *Specifications* obsahuje dva navazující vzory, které se do knih ani do PHP praxe
nedostaly. Oba přitom řeší otázku, na kterou `isSatisfiedBy()` odpovědět neumí.

**Subsumption** srovnává specifikace mezi sebou místo specifikace s kandidátem. Metoda
`isGeneralizationOf()` odpoví, zda je jedno pravidlo obecnější než druhé. Používá se
u párování nabídky s poptávkou, kde obě strany popisujete specifikací a konkrétní objekt
zatím neexistuje.

**Partially Satisfied Specification** přidává `remainderUnsatisfiedBy()`, která vrátí
zbytkovou specifikaci – tedy to, co ještě zbývá splnit. Uživatel místo `false` dostane
odpověď „chybí doručovací adresa v EU“.

Metoda patří do rozhraní `Specification`, ne jen do kompozitu: `AndSpecification`
ji volá na svých potomcích, které zná jen jako `Specification`. `CompositeSpecification`
dodá výchozí tělo, takže listové specifikace nic dopisovat nemusí:

:::code{language="php" filename="src/SharedKernel/Domain/Specification/Specification.php + CompositeSpecification.php (rozšíření o zbytkovou specifikaci)"}
// Rozhraní si ponechává and/or/not ze sekce 08.02; přibývá jen pátá metoda.
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

    /**
     * Vrátí specifikaci popisující, co kandidát ještě nesplnil,
     * nebo null, pokud splnil všechno.
     *
     * @param T $candidate
     * @return Specification<T>|null
     */
    public function remainderUnsatisfiedBy(mixed $candidate): ?Specification;
}


abstract class CompositeSpecification implements Specification
{
    // Výchozí implementace pro listy i kompozity
    public function remainderUnsatisfiedBy(mixed $candidate): ?Specification
    {
        return $this->isSatisfiedBy($candidate) ? null : $this;
    }
}
:::

:::code{language="php" filename="src/SharedKernel/Domain/Specification/AndSpecification.php (doplněk)"}
public function remainderUnsatisfiedBy(mixed $candidate): ?Specification
{
    $left = $this->left->remainderUnsatisfiedBy($candidate);
    $right = $this->right->remainderUnsatisfiedBy($candidate);

    if ($left !== null && $right !== null) {
        return new self($left, $right);
    }

    return $left ?? $right;
}
:::

Metodu musí implementovat každý kombinátor a u `or` a `not` už odpověď není
jednoznačná. Přínos je stejně zřejmý: formulář nebo API vrátí důvod zamítnutí odvozený
z pravidla, které rozhodlo, místo ručně psané hlášky, která se časem rozejde s logikou.

### Double-dispatch do Doctrine {#spec-doctrine}

Specifikace slouží i ve **druhé roli** – jako parametr dotazu do repozitáře. Místo
metody `findEligibleForFreeShippingInEU(): array`, která by přibývala s každou novou
kombinací pravidel, dostane repozitář *libovolnou* specifikaci, převede ji na dotaz
a vrátí výsledek. Přístupu se říká **double-dispatch**: specifikace nese pravidlo,
repozitář ví, jak ho přeložit do persistence.

Rozhoduje jediná věc: co specifikace vrací. Mutovat předaný `QueryBuilder` se nabízí,
ale vede do slepé uličky: metoda s návratovým typem `void` nejde skládat, takže `or`
a `not` se přeložit nedají. Doctrine na to má vlastní mezireprezentaci. `Doctrine\Common\Collections\Criteria`
staví výrazy přes `Criteria::expr()` a nabízí `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `in`,
`notIn`, `contains`, `startsWith`, `endsWith`, `isNull`, `memberOf` a kombinátory
`andX`, `orX`, `not`. Specifikace tedy vrací **výraz**, ne vedlejší efekt:

:::code{language="php" filename="src/SharedKernel/Domain/Specification/QuerySpecification.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Specification;

use Doctrine\Common\Collections\Expr\Expression;

/**
 * Specifikace, která umí své pravidlo vyjádřit jako Doctrine výraz.
 * Implementuje double-dispatch: specifikace zná pravidlo,
 * repozitář ví, jak výraz spustit nad databází.
 *
 * @template T
 * @extends Specification<T>
 */
interface QuerySpecification extends Specification
{
    public function toExpression(): Expression;
}
:::

:::code{language="php" filename="src/Ordering/Domain/Specification/EligibleForFreeShipping.php (rozšířená verze)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Specification;

use App\Ordering\Domain\Model\Order;
use App\SharedKernel\Domain\Money;
use App\SharedKernel\Domain\Specification\CompositeSpecification;
use App\SharedKernel\Domain\Specification\QuerySpecification;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;

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

        $total = $candidate->totalAmount();

        return $total->currency === $this->threshold->currency
            && $total->amountInCents >= $this->threshold->amountInCents;
    }

    public function toExpression(): Expression
    {
        return Criteria::expr()->andX(
            Criteria::expr()->eq('totalCurrency', $this->threshold->currency->value),
            Criteria::expr()->gte('totalAmount', $this->threshold->amountInCents),
        );
    }
}
:::

Dotazová podoba předpokládá, že objednávka drží celkovou částku v mapovaných vlastnostech
`totalAmount` a `totalCurrency`. Kanonický `Order` součet počítá z položek za běhu
a `Criteria` pracuje jen s mapovanými vlastnostmi, takže bez takové denormalizace dotaz
nemá nad čím filtrovat.

Repozitář pak vystaví obecnou metodu `match()`. `Doctrine\ORM\EntityRepository` implementuje
rozhraní `Selectable`, takže `Criteria` umí spustit přímo:

:::code{language="php" filename="src/Ordering/Infrastructure/Repository/DoctrineOrderRepository.php (výřez: match přes specifikaci)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Repository;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use App\SharedKernel\Domain\Specification\QuerySpecification;
use Doctrine\Common\Collections\Criteria;
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
        $criteria = new Criteria($spec->toExpression());

        return array_values(
            $this->em->getRepository(Order::class)->matching($criteria)->toArray(),
        );
    }
}
:::

Tatáž `Criteria` funguje i nad `ArrayCollection` v paměti, protože rozhraní `Selectable`
implementuje kolekce stejně jako repozitář. Když dotaz potřebuje join nebo řazení přes
vazbu, výraz se vloží do `QueryBuilder`u přes `addCriteria()` a zbytek dotazu zůstane
ruční. Join vede přes asociaci uvnitř agregátu; na zákazníka objednávka odkazuje jen
přes `CustomerId`:

:::code{language="php" filename="src/Ordering/Infrastructure/Repository/DoctrineOrderRepository.php (fragment)"}
/**
 * @param QuerySpecification<Order> $spec
 * @return list<Order>
 */
public function matchWithItems(QuerySpecification $spec): array
{
    return $this->em->createQueryBuilder()
        ->select('o', 'i')
        ->from(Order::class, 'o')
        ->join('o.items', 'i')
        ->addCriteria(new Criteria($spec->toExpression()))
        ->getQuery()
        ->getResult();
}
:::

Obě role specifikace (in-memory predikát i překlad do dotazu) sedí v jedné třídě.
Když se PHP a databázová podoba začnou rozcházet, je to při code review vidět
na jedné obrazovce. Soulad ale nic nevynucuje – jde o dvě nezávislé implementace
téhož pravidla. Pojistkou je kontraktní test: nad stejnou sadou testovacích dat ověří, že `isSatisfiedBy()`
označí tytéž objekty, jaké `match()` vrátí z databáze. Když se obě verze rozejdou,
test selže dřív než produkce.

Cestu od repozitáře s příliš mnoha metodami přes Doctrine `Criteria` ke specifikacím
popsal Benjamin Eberlei v textu *On Taming Repository Classes in Doctrine* (2013). Jeho
specifikace upravují předaný `QueryBuilder`, tedy variantu, kterou tato sekce kvůli
skládání opouští; z jeho článku vychází i balíček `happyr/doctrine-specification`.
Kévin Gomez na něj navázal textem *On Taming Repository Classes in Doctrine… Among
other things* (7. 2. 2015).

### Limity: co Criteria unese a co ne {#spec-query-kombinatory}

Protože specifikace vrací výraz, kombinátory se přeloží stejně přímočaře jako predikát:
`AndSpecification` na `andX`, `OrSpecification` na `orX`, `NotSpecification` na `not`.

:::code{language="php" filename="src/SharedKernel/Domain/Specification/AndSpecification.php (doplněk)"}
// Deklarace třídy se rozšíří o rozhraní, jinak ji match() na vstupu odmítne:
// final class AndSpecification extends CompositeSpecification implements QuerySpecification

public function toExpression(): Expression
{
    if (!$this->left instanceof QuerySpecification
        || !$this->right instanceof QuerySpecification
    ) {
        throw new \LogicException(
            'Do dotazu lze přeložit jen kompozici QuerySpecification.',
        );
    }

    return Criteria::expr()->andX(
        $this->left->toExpression(),
        $this->right->toExpression(),
    );
}
:::

Kombinátor implementuje `QuerySpecification`, jeho operandy ale query specifikacemi
být nemusí. Typová kontrola na vstupu `match()` proto do listů nedosáhne a rozpor se ozve
až běhovou výjimkou. Výměnou za to jde skládat i `or` a `not`, ne jen konjunkci.

Zmizí i past s názvy parametrů: `Criteria` si placeholdery generuje sama, takže dvě
pod-specifikace se stejnou hodnotou prahu se navzájem nepřepíšou.

Skutečné limity leží jinde. `Criteria` porovnává v `eq` a `neq` striktně, takže
srovnání instancí `DateTimeImmutable` se chová jinak než v SQL. Vlastní DQL funkce, joiny,
agregace ani poddotazy vyjádřit nejdou. Pole ve výrazu odkazují na vlastnosti entity, ne na
sloupce, takže pravidlo nad vazbou se do výrazu nedostane bez `addCriteria()` a ručního
joinu. Pro takový dotaz zůstává správnou volbou vlastní repozitářová metoda
(`findOrdersEligibleForPromo()`), která pravidlo zapíše v DQL přímo a kontraktním testem
se sváže s in-memory specifikací.

Celý framework z této sekce existuje i hotový. `happyr/doctrine-specification` má přes
900 tisíc instalací, podporuje Doctrine ORM 3 a repozitář rozšiřuje o `match()`; pravidla
se skládají přes `Spec::andX()` a `Spec::orX()`. Filtrování navíc odděluje
od modifikátorů výsledku (řazení, hydratace), což ruční kostra z této kapitoly neumí.
Ukázky výše vysvětlují, co balíček dělá uvnitř. V projektu, kde specifikace nejsou
předmětem výuky, je balíček levnější volbou.

Pro hluboký teoretický základ vzoru: Evans, E., *Domain-Driven Design* (2003),
kapitola 9 *Making Implicit Concepts Explicit*; Evans & Fowler, pracovní
papír *Specifications* (1997), dostupný na martinfowler.com.
Praktická aplikace na agregátech: Vernon, V., *Implementing Domain-Driven Design*
(2013). K Doctrine části: Gomez, K., *On Taming Repository Classes in Doctrine… Among
other things* (2015) a dokumentace `doctrine/collections` k `Criteria` a `Selectable`.

## 08.03 Domain Services {#domain-services}

Doménovou službu zavádějí [Základní koncepty](/zakladni-koncepty#domain-services)
a [Implementace v Symfony](/implementace-v-symfony) ukazuje, jak ji zaregistrovat
v containeru. Tato sekce řeší rozhodovací kritéria: kdy má služba vzniknout, kdy jde jen
o logiku vytrženou z entity a kudy vede hranice vůči Application vrstvě.

### Co to je {#ds-definice}

**Domain Service** je bezstavový objekt s doménovou logikou, která **nemá přirozeného
vlastníka** mezi entitami a hodnotovými objekty modelu. Eric Evans v kapitole 5
*Domain-Driven Design* (2003) shrnuje kritérium do tří bodů: operace se týká doménového
konceptu, ale (1) nepatří do žádné entity ani hodnotového objektu jako jejich přirozená
metoda, (2) její rozhraní tvoří jiné prvky doménového modelu a (3) nemá vlastní stav.

Signálem je operace, kterou by žádná entita nemohla vlastnit, aniž by musela příliš
vědět o té druhé.

### Kdy použít {#ds-kdy}

Evansovým příkladem je **Funds Transfer**, převod peněz mezi dvěma účty. Ani jeden
účet nezná ten druhý a ani jeden není přirozeným vlastníkem operace; převod je doménový
pojem sám o sobě. Podobně **pricing engine** počítá cenu objednávky z cenových pravidel,
segmentu zákazníka, košíku a kupónu a žádný z těchto objektů výpočet nevlastní. Stejnou
povahu má **credit scoring**: odpověď na *„má tento zákazník nárok na úvěr X?“* vzniká
kombinací několika faktorů.

Čtvrtým typickým případem je **koordinátor dvou agregátů** – operace nad dvěma agregáty,
z nichž žádný nesmí znát detaily druhého. Pokud oba mění, naráží na pravidlo „jeden
agregát na transakci“ (viz poznámku za ukázkou níže).

### Kdy NE {#ds-kdy-ne}

Domain Service je v DDD nejčastěji zneužívaný vzor. Vývojáři zvyklí na klasickou
vrstvenou architekturu reflexivně založí `OrderService`, `CustomerService`
a `InvoiceService`, přesunou do nich logiku z entit a vyrobí
[anémický doménový model](/anti-vzory).

Než Domain Service vznikne, projděte tři kontrolní otázky:

1. **Patří tato operace přirozeně do nějaké Entity?** (= je to chování
   nad jednou identitou, agregát ji může bez cizí pomoci provést) – pokud ano,
   *nepatří* do Domain Service.
2. **Je to skutečně doménová operace, nebo aplikační?** Domain Service
   obsahuje doménová pravidla. Application Service koordinuje
   (transakce, autorizace, eventy). Pokud byste v „doménové“ service museli
   volat `EntityManager->flush()`, jde o Application Service.
3. **Není to spíš infrastrukturní detail?** Posílání e-mailu, hash hesla,
   čtení z externího API – to nejsou doménové operace, ale infrastruktura.

### Příklad: MoneyTransferService {#ds-priklad}

Bankovní převod ze zdrojového účtu na cílový. Logika nepatří do `$from` (nezná `$to`)
ani do `$to` (nezná `$from`), je to doménová operace bez přirozeného vlastníka:

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
 * Bezstavová – mezi voláními nic nedrží, mění jen agregáty, které dostane.
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

        if ($from->currency() !== $to->currency()) {
            throw new \DomainException(
                'Currency mismatch – use FxTransferService for cross-currency transfers.',
            );
        }

        $from->withdraw($amount, $reference, $when);
        $to->deposit($amount, $reference, $when);
    }
}
:::

„Opravdovou“ Domain Service poznáte podle tří rysů:

1. **Žádný stav** – třída nedrží mezi voláními nic vlastního. Pracuje s objekty,
   které dostane v parametrech.
2. **Žádné perzistenční volání** – `$from->withdraw()` a
   `$to->deposit()` mutují stav agregátů, ale ukládat je bude až
   Application Service nebo command handler. Domain Service nikdy nevolá
   `$em->flush()`.
3. **Vyhazuje doménové výjimky** – `InsufficientFunds`,
   `\DomainException` – ne `\RuntimeException` nebo HTTP
   status kódy.

Ukázka se drží Evansova výkladu: služba mění oba účty a handler, který ji volá, je uloží
jednou transakcí. Kapitola [Návrh agregátu](/navrh-agregatu#transactional-consistency)
tentýž převod uvádí jako anti-vzor, protože porušuje pravidlo „jeden agregát na
transakci“. Obstojí tam, kde tým vědomě volí jednu z výjimek popsaných v sekci
[Kdy se vodítko poruší](/navrh-agregatu#breaking-the-rule). Jinak si doménová služba
ponechá jen rozhodnutí (dostatek prostředků, shoda měn) a samotný převod rozloží sága
na dvě transakce.

První rys se často zpřísňuje na „doménová služba nesmí mít v konstruktoru repozitář“.
Jako pravidlo to neobstojí a zdroje se v něm rozcházejí. Vladimir Khorikov rozlišuje *pure*
a *impure* doménovou službu: druhá sáhne do vnějšího systému, protože bez toho doménové
rozhodnutí nepadne. Matthias Noback umísťuje rozhraní repozitáře do Domain vrstvy právě
proto, že s ním doménový kód pracovat má. Vernonova námitka, na kterou se v této debatě
odkazuje nejčastěji, navíc míří na injektování repozitáře do **agregátu**, ne do služby.

Praktické vodítko proto není zákaz. Než služba dostane repozitář, vyplatí se zvážit,
jestli jí data nemá dodat volající. Služba, která si data načítá sama, přebírá kus
orchestrace a její test přestane být čistě jednotkový. Když data jinak získat nejde,
typicky u pravidla nad celou kolekcí, je závislost na doménovém rozhraní přijatelná
a služba zůstává doménová. Rozhoduje, jestli třída obsahuje doménové pravidlo, ne počet
parametrů jejího konstruktoru.

:::callout{type="warn"}
### Anti-vzor: Application Service vydávaný za Domain Service {#ds-anti-heading}

Nejčastější chyba: třída v `Domain/Service/`, která v konstruktoru přijímá
`EntityManager`, `OrderRepository` a `EventDispatcher` a v jedné metodě načte agregát
z DB, upraví ho, uloží a publikuje event. Jde o **Application Service v přestrojení**.
Hranice mezi doménou (pravidla) a aplikací (orchestrace use case) zmizela. Doménový
model pak nejde testovat bez DB a Symfony containeru a refaktoring výrazně zdraží.
:::

### Domain Service vs. Application Service vs. Infrastructure Service {#ds-srovnani}

Třída se sufixem `Service` se v kódu objeví téměř vždy. Liší se tím, kterou ze tří
rolí plní. Tabulka shrnuje rozdíly, na které se vyplatí ptát v code review:

| Aspekt | Domain Service | Application Service | Infrastructure Service |
|---|---|---|---|
| Účel | Doménová logika bez přirozeného vlastníka | Koordinace use case (transakce, autorizace, eventy) | Technická integrace (DB, e-mail, externí API) |
| Vrstva | Domain | Application | Infrastructure |
| Závislosti | Doménové typy a doménová rozhraní (Entity, VO, jiné Domain Services) | Repozitáře, Event Bus, Domain Services, Authorization | HTTP klienti, knihovny (Mailer, Stripe SDK), filesystem |
| Stav | Stateless | Stateless (jednorázový handler) | Často stateless, ale může držet connection pool |
| Volá perzistenci? | Ne | Ano (přes repozitář) | Ano (sama je perzistencí) |
| Vyhazuje výjimky | Doménové (`InsufficientFunds`) | Aplikační (`UnauthorizedException`, validation) | Infrastrukturní (`ConnectionException`) |
| Příklad jména | `MoneyTransferService`, `PricingService` | `PlaceOrderHandler`, `RegisterUserHandler` | `SymfonyMailer`, `StripePaymentGateway` |
| Test | Pure unit, bez Symfony kernel | Unit s mockovanými repozitáři | Integrační (kontrakt s reálným systémem) |
| Sufix v PHP | `*Service` (volitelně) | `*Handler`, `*UseCase` | `*Gateway`, `*Adapter`, `*Client` |

Jednotný sufix `*Service` smaže rozdíl mezi třemi rolemi z tabulky. V Application vrstvě
se proto v praxi používá `*Handler` nebo `*UseCase`. Doménová služba nese sufix *Service*
jen tehdy, když zdůrazňuje „operaci bez vlastníka“; často je lepší přímo doménové jméno
bez sufixu (`FundsTransfer`, `PricingEngine`).

:::callout{type="pattern"}
### Praktický tip: hraniční případ {#ds-tip-heading}

Když nejde jednoznačně rozhodnout, zda je třída Domain, nebo Application Service,
obvykle **míchá obě role**. Rozdělte ji: doménovou
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

Named constructor a rekonstituce se objevují už v [Základních konceptech](/zakladni-koncepty#aggregates)
jako součást výkladu agregátu. Tato sekce je bere jako samostatný vzor: kdy stačí
statická metoda, kdy je potřeba zvláštní třída a kam taková třída patří ve struktuře modulu.

### Co to je {#fac-definice}

**Factory** v terminologii DDD zapouzdřuje **složitou logiku vzniku agregátu nebo
hodnotového objektu** tam, kde konstruktor nestačí. Eric Evans
v kapitole 6 *Domain-Driven Design* (2003) doporučuje přesunout odpovědnost za
vytváření složitých objektů a agregátů na samostatný objekt, zvlášť když vznik
vyžaduje pravidla nebo polymorfismus.

Většině agregátů konstruktor stačí. Factory se hodí, když:

- Vznik agregátu vyžaduje validaci, kterou nelze provést až *po* konstrukci
  (např. *„objednávka založená rovnou s položkami (`placePhysical()` níže) musí mít
  alespoň jednu, jinak nevznikne“*).
- Vznik je polymorfní – z různých vstupů vznikají různé pod-typy stejného agregátu
  (například `Order::placePhysical()` vs. `Order::placeDigital()`).
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
- **Factory pro každý objekt v doméně** – over-engineering. Factory vzniká
  podle potřeby, ne plošně.

### Vzor 1: Static method factory (preferovaný) {#fac-static}

V PHP 8.4 má factory nejčastěji podobu statické pojmenované konstrukční metody
na samotném agregátu (named constructor). Konstruktor je privátní a ven vedou jen
pojmenované vstupní body s doménovým významem:

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (varianta s továrnami)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Exception\EmptyOrderException;
use App\SharedKernel\Domain\AggregateRoot;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;

final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items;

    /** @param list<OrderItem> $items */
    private function __construct(
        public readonly OrderId $id,
        public readonly CustomerId $customerId,
        array $items,
        private readonly OrderType $type,
        // Zde čas vzniku objednávky. V kanonickém modelu z Návrhu agregátu
        // nese placedAt čas potvrzení a plní ho až confirm().
        private readonly \DateTimeImmutable $placedAt,
    ) {
        $this->items = $items;
        // Konstruktor jen plní stav. Eventy zaznamenávají factory metody –
        // konstruktorem prochází i reconstitute(), která žádný event vyvolat nesmí.
    }

    // Továrna vedle kanonického Order::place(OrderId, CustomerId). Přebírá
    // rovnou seznam položek, aby invariant platil už při vzniku. Kanonická
    // placeWithItems() s primitivními řádky je v kapitole o outboxu.
    /**
     * Vznik objednávky s fyzickým zbožím – protějšek placeDigital() níže.
     *
     * @param list<OrderItem> $items
     */
    public static function placePhysical(
        CustomerId $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): self {
        if (count($items) === 0) {
            throw EmptyOrderException::cannotBePlaced();
        }

        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: $items,
            type: OrderType::Physical,
            placedAt: $placedAt,
        );
        $order->record(new OrderPlaced($order->id, $customerId));

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
            throw EmptyOrderException::cannotBePlaced();
        }

        $order = new self(
            id: OrderId::generate(),
            customerId: $customerId,
            items: array_map(static fn (DigitalItem $i): OrderItem => $i->toOrderItem(), $items),
            type: OrderType::Digital,
            placedAt: $placedAt,
        );
        $order->record(new OrderPlaced($order->id, $customerId));

        return $order;
    }

    /**
     * Vznik z importu – odlišná validace, zákazníka neidentifikuje přes CustomerId,
     * ale přes externí klíč, který se uvnitř naváže na guest CustomerId.
     */
    public static function fromImport(
        ImportedOrderRow $row,
        CustomerLookup $lookup,
        \DateTimeImmutable $placedAt,
    ): self {
        $customerId = $lookup->byEmail($row->customerEmail) ?? $lookup->guestId();
        $items = ImportedItems::map($row->items);

        return self::placePhysical($customerId, $items, $placedAt);
    }
}
:::

Signatura `placePhysical()` přebírá rovnou seznam položek, aby šlo ukázat invariant
„objednávka bez položky nevznikne“ vynucený už při vzniku. Kanonický `Order` v této knize položky
přidává metodou `addItem(ProductId $productId, int $quantity, Money $unitPrice)` a prázdnou
objednávku dovolí; invariant pak hlídá `confirm()`. Stejnou cestou jde i kanonická továrna
`placeWithItems(CustomerId $customerId, array $items)` s primitivními řádky, kterou zavádí
kapitola [Outbox Pattern](/outbox-pattern#order-aggregate-heading): položky přidá přes
`addItem()` a objednávku hned potvrdí. Obě varianty jsou obhajitelné; volba mezi nimi
rozhoduje, zda agregát smí existovat v rozpracovaném stavu.

Tři výhody static method factory oproti samostatné Factory class:

1. **Doménové jméno**. `Order::place()` nebo
   `Order::placeDigital()` nese sémantiku, kterou
   `new Order(...)` postrádá.
2. **Privátní konstruktor**. Žádný kód mimo agregát nevytvoří
   `Order` cestou, která obejde validaci. Invariant hlídá jazyk,
   ne disciplína.
3. **Víc cest ke vzniku bez dědičnosti**. `Order::placeDigital()` a
   `Order::fromImport()` mají různé vstupy a různá pravidla, ale
   výstup je stejný typ.

### Vzor 2: Factory class (když potřebujete DI) {#fac-class}

Statická metoda přestává stačit, když vznik agregátu potřebuje **injektované
závislosti** (repozitáře, externí služby, konfiguraci). Jednu službu jí volající
předá parametrem, jak ukazuje `fromImport()` s `CustomerLookup`. Jakmile jsou ale
závislosti tři a každý volající by je musel shánět sám, přehlednější je samostatná
Factory class, které je dodá container:

:::code{language="php" filename="src/Ordering/Domain/Factory/OrderFromCartFactory.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Factory;

use App\Ordering\Domain\Cart\CartId;
use App\Ordering\Domain\Cart\CartRepository;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Service\PricingService;
use App\Ordering\Domain\ValueObject\CustomerId;
use Psr\Clock\ClockInterface;

/**
 * Factory class – vznik objednávky z košíku vyžaduje
 * načtení košíku a aplikaci aktuálního pricingu.
 * Závislosti dodá container, volající je nemusí shánět.
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

        return Order::placePhysical(
            customerId: $customer,
            items: $pricedItems,
            placedAt: $this->clock->now(),
        );
    }
}
:::

Factory class **uvnitř volá** `Order::placePhysical()`. Invariant „aspoň 1 položka“
nepřebírá, ten zůstává v named constructoru agregátu. Factory řeší jen *sestavení
vstupních dat*.

Trojice `CartRepository`, `PricingService` a `ClockInterface` v konstruktoru vypadá jako
rozpor se sekcí 08.03, kde repozitář posouval třídu blíž k Application vrstvě. Rozřešení
dává *DDD Reference*: factory sama nemusí mít v modelu žádnou odpovědnost, a přesto je
součástí doménového návrhu. Nemodeluje doménový pojem, jen sestavuje agregát podle
doménových pravidel. Proto smí sáhnout po repozitáři a zůstat přitom v `Domain/Factory/`.
Hranice se posune jinam: jakmile by factory začala výsledek ukládat nebo publikovat
událost, je z ní command handler.

:::callout{type="pattern"}
### Atribuce: co říká Vernon a co Verraes {#vernon-rule-heading}

Vaughn Vernon věnuje factories kapitolu 11 *Implementing Domain-Driven Design* (2013)
a člení ji na *Factories in the Domain Model*, *Factory Method on Aggregate Root*
a *Factory on Service*. Už pořadí je doporučení: factory metoda na agregátním kořeni
stojí v popředí, samostatná factory na úrovni service přichází až jako druhá možnost.
O to se opírá konvence knihy: `Order::place()` místo zvláštní třídy `OrderFactory`,
dokud si ji nevynutí spolupráce více agregátů.

Jedno se ale Vernonovi připsat nedá. Jeho *Factory Method on Aggregate Root* je
instanční metoda existujícího agregátu, která vyrábí **jiný** agregát – `Forum` vytvoří
`Discussion`, `Product` vytvoří `BacklogItem`. Vzor „privátní konstruktor plus statická
`::place()`“ je konvence PHP komunity a jejím nejcitovanějším zdrojem je Mathias Verraes,
*Named Constructors in PHP* (2014). Verraes vychází z toho, že PHP dovolí jediný
konstruktor na třídu, a privátní konstruktor doporučuje kvůli volnosti refaktorovat
vnitřek třídy, aniž se dotknete volajících.

V PHP 8.4 z toho plyne: privátní konstruktor a statické `::place()`,
`::placeDigital()`, `::fromImport()`. Factory class až tehdy, když
vznik potřebuje `HttpClient`, `Clock`, repozitář nebo doménovou službu.
:::

### Reconstitution: zvláštní případ Factory {#fac-reconstitute}

Třetím typem factory je **reconstitution** – obnovení agregátu z perzistence.
S Doctrine ji obstará hydrator. Event Sourcing nebo vlastní mapper ale potřebují
factory, která **invarianty nekontroluje**, protože obnovovaný stav validací prošel
už při vzniku:

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (fragment)"}
/**
 * Rekonstituce ze stavu načteného z DB / event streamu.
 * Tento pojmenovaný konstruktor nekontroluje invarianty –
 * obnovovaný stav je z definice platný, jinak by se nedostal do persistence.
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

Proto také `OrderPlaced` zaznamenávají factory metody (`::place()`, `::placePhysical()`),
ne konstruktor.
Rekonstituce nesmí mít vedlejší efekty: obnovuje stav, žádná doménová událost se
nestala. Kdyby event zaznamenával konstruktor, každé načtení agregátu z databáze
by znovu vyprodukovalo `OrderPlaced` a odběratelé by tutéž objednávku „umístili“
při každém čtení.

Pojmenování `::reconstitute()` a PHPDoc `@internal` signalizují, že tato cesta vzniku
je vyhrazena infrastruktuře. Doménový handler,
který by ji volal místo `::place()`, by porušil invariant agregátu.

Pro detail: Evans, E., *Domain-Driven Design* (2003), kapitola 6
*The Life Cycle of a Domain Object*; Vernon, V., *Implementing Domain-Driven
Design* (2013), kapitola 11 *Factories*; Verraes, M., *Named Constructors in PHP*
(2014). Souvisejí kapitoly:
[Základní koncepty – Agregáty](/zakladni-koncepty#aggregates),
[Event Sourcing](/event-sourcing) (reconstitution z event
streamu).

## 08.05 Modules {#modules}

### Co to je {#mod-definice}

**Module** je v Evansově terminologii **vědomé členění kódu do balíčků pojmenovaných
podle Ubiquitous Language**. Namespace je jen nástroj; podstatou vzoru je princip, že
struktura balíčků odráží doménový jazyk, ne technické vrstvy ani použité knihovny.
Evans mu věnuje samostatnou pasáž v kapitole 5 *Domain-Driven Design* (2003) a moduly
chápe jako vyjádření hrubší struktury modelu.

*DDD Reference* to formuluje ostřeji. Modul je součástí modelu, jeho jméno patří do
Ubiquitous Language a má obsahovat kohezní sadu pojmů. Z toho plyne důsledek, který se
v praxi přeskakuje: pokud modularita nevede k nízké provázanosti mezi moduly, řešením je
změnit model, ne přitvrdit pravidla v phparkitectu. Vysoký coupling mezi moduly je nález
o doméně, ne o konfiguraci nástroje.

V Symfony 8 a PHP 8.4 to konkrétně znamená:

- **PSR-4 namespace + uspořádání složek** podle
  [Bounded Contextů](/zakladni-koncepty#bounded-contexts). Výchozí mapování
  `App\` na `src/` na to stačí, viz [PSR-4, autoload a services.yaml](#mod-composer).
- **Publikované rozhraní modulu**, tedy úzká množina typů, přes kterou do něj
  vstupuje okolí.
- **Architecture testing**, který zkontroluje, že žádný kód
  v `App\Billing\` nesahá přímo do `App\Ordering\`.

### Modul jako Bounded Context {#mod-bc}

Nejčastěji se vzor uplatní jako **1 modul = 1 Bounded Context**:

:::code{language="bash" filename="Adresářová struktura podle Modules vzoru"}
src/
  Ordering/                                  ← MODULE = Bounded Context
    Domain/
      Model/
        Order.php                            ← Aggregate Root
        OrderItem.php
      Repository/
        OrderRepository.php                  ← Interface
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
        EmptyOrderException.php
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
      Repository/
        DoctrineOrderRepository.php
      Doctrine/
        Type/OrderIdType.php
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

Příbuzné členění po funkcích rozebírá sekce
[Vertical Slice Architecture](/architektonicke-styly#vertical-slice). Pro tuto kapitolu
stačí jedno pozorování: nejvyšší úroveň adresářů ukazuje doménovou mapu projektu
(Ordering, Billing, SharedKernel), ne technické složky Twig/Doctrine/Service.

### Anti-vzor: type packaging {#mod-anti}

:::callout{type="warn"}
### Anti-vzor: `src/Entity/`, `src/Service/`, `src/Repository/` {#type-pack-heading}

Symfony skeleton a MakerBundle zakládají složky podle technické role: `src/Entity/`
pro entity, `src/Repository/` pro repozitáře, `src/Controller/` pro kontrolery.
Týmy k nim přidávají `src/Service/` pro „všechno ostatní“. Tomuto členění se říká
**type packaging** a v netriviálních doménách škodí:

- **Skrývá doménu**. Z adresářů nepoznáte, jestli jde o e-shop, banku, nebo systém
  pojišťovny; vidíte jen entity a controllery.
- **Vynucuje horizontální vrstvy**. Změna jednoho doménového pravidla
  často znamená editovat soubory v pěti složkách místo v jednom modulu.
- **Eroze modularity**. `OrderEntity` a `InvoiceEntity`
  sedí vedle sebe, takže není zřejmé, že nesmí přímo komunikovat.

Type packaging má opodstatnění ve *velmi malých* aplikacích, kde doména prakticky
neexistuje (CRUD nad jedním objektem), a v ukázkových repozitářích pro výuku
jednotlivých Symfony komponent. Doménově bohaté aplikaci škodí.
:::

### PSR-4, autoload a services.yaml {#mod-composer}

Rozšířená představa je, že modulová struktura vyžaduje vlastní PSR-4 kořen pro každý modul.
Nevyžaduje. PSR-4 mapuje *prefix* namespace na *základní adresář* a zbytek namespace překládá
na podadresáře. Při výchozím symfonním mapování `"App\\": "src/"` se tedy třída
`App\Ordering\Domain\Model\Order` hledá v `src/Ordering/Domain/Model/Order.php` – přesně tam, kam ji
modulová struktura klade. Do `composer.json` sahat nemusíte a struktura z předchozí ukázky
funguje bez jediné změny.

Vlastní kořen na modul má smysl ve dvou situacích. Buď moduly nesedí pod `src/`, nebo se
z modulu má časem stát samostatný composer balíček s vlastním `composer.json`. Zápis pak
vypadá takto:

:::code{language="json" filename="composer.json (fragment) – jen pro moduly mimo src/"}
{
    "autoload": {
        "psr-4": {
            "App\\Ordering\\":     "modules/ordering/src/",
            "App\\Billing\\":      "modules/billing/src/",
            "App\\SharedKernel\\": "src/SharedKernel/"
        }
    }
}
:::

Po takové úpravě je potřeba spustit `composer dump-autoload`.

Podobný mýtus se drží u `config/services.yaml`. Výchozí konfigurace registruje `App\`
s `resource: '../src/'`, takže služby uvnitř modulů se autoregistrují samy. Controller
dědící `AbstractController` dostane tag `controller.service_arguments` od `autoconfigure: true`,
takže ani ten se vypisovat nemusí. Výčet per modul je tedy volba, ne nutnost.

Vyplatí se ve chvíli, kdy modulům nastavujete jiná `_defaults`, nebo když chcete doménovou
vrstvu z containeru vyloučit:

:::code{language="yaml" filename="config/services.yaml (fragment)"}
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/*/Domain/'
            - '../src/SharedKernel/Domain/'

    # Doménové služby a factory třídy do containeru patří, zbytek doménové vrstvy ne.
    App\Ordering\Domain\Service\:
        resource: '../src/Ordering/Domain/Service/'

    App\Ordering\Domain\Factory\:
        resource: '../src/Ordering/Domain/Factory/'
:::

Výluka drží agregáty, hodnotové objekty a specifikace mimo container. Nikdo je
neinjektuje; kdyby je někdo injektoval, zachází s nimi špatně. Doménové služby a factory
třídy typu `OrderFromCartFactory` se registrují zvlášť, protože ty se injektují.

Vyloučený adresář ale vypadne i z automatického aliasování rozhraní. Porty jako
`OrderRepository` leží právě tam, takže jejich alias na implementaci je nutné zapsat
ručně – rozebírá to [kapitola o architektonických stylech](/architektonicke-styly#hexagonal-symfony-di-heading).

### Kontrakt modulu {#mod-kontrakt}

Evansův Module z roku 2003 stojí na kohezi pojmů a nízké provázanosti mezi moduly.
Dnešní praxe pod hlavičkou *modulárního monolitu* přidává třetí požadavek.
Kamil Grzybek popisuje modul jako vertikální řez byznysem se třemi vlastnostmi:
nezávislost a zaměnitelnost, úplnost (obsahuje vše potřebné k dodání funkce)
a dobře definované rozhraní, přes které se do modulu vstupuje.

Třetí vlastnost je posun oproti roku 2003. Nestačí zakázat cizí import; modul má
vystavit úzkou množinu typů, které smí volat okolí, a zbytek nechat interní. Prakticky
to znamená složku `Ordering/PublicApi/` s command a query rozhraními plus publikované
události, a architektonické pravidlo, že z jiného modulu se smí importovat jedině odtud.
Oproti pouhému zákazu importu tak refaktoring uvnitř modulu nikoho dalšího nezasáhne,
protože se nedotkne ničeho, co soused vidí.

Modulární monolit jako celek rozebírá kapitola
[DDD a microservices](/ddd-a-microservices#modular-monolith): kdy se vyplatí, jak z něj
později odejít a jaká pravidla mu nastavit.

### Architecture testing: hranice vynucené v CI {#mod-phparkitect}

Konvence sama nestačí – vývojáři pod tlakem zapomenou, že `App\Billing\` nesmí
volat `App\Ordering\`. Pravidlo proto vynucuje **test**, který běží v CI a při
porušení shodí build. Princip je u všech nástrojů stejný: pravidla závislostí leží
jako definice verzované vedle kódu a pipeline je kontroluje při každém commitu.
Pro modulový projekt z této kapitoly jde typicky o tři pravidla:

1. `App\Ordering` nesmí záviset na `App\Billing`, `App\Inventory` ani
   `App\Shipping` – integrace mezi BC probíhá výhradně přes integrační události
   ([Outbox](/outbox-pattern)).
2. `App\Ordering\Domain` nesmí importovat nic ze `Symfony`, z běhové části
   Doctrine ORM (`EntityManager`, `QueryBuilder`) ani z vlastní Application
   a Infrastructure vrstvy. Vědomé výjimky jsou dvě: mapovací atributy
   `Doctrine\ORM\Mapping`, které kniha dává přímo na doménové třídy (viz
   [volba mappingu](/implementace-v-symfony#mapping-volba-heading)), a knihovna
   `doctrine/collections`, na které stojí kolekce agregátu i `Criteria`
   ve specifikacích.
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

Do zavedeného projektu se nástroj nasazuje přes *baseline*. Porušení z vygenerovaného
seznamu build neshodí, takže pravidla platí hned a starý dluh se odbourává postupně.
Bez baseline skončí první spuštění stovkami chyb a tým nástroj vypne.

Druhou možností je [Deptrac](https://github.com/deptrac/deptrac), který
vrstvy a povolené závislosti popisuje v konfiguračním souboru. Kompletní Deptrac
konfiguraci pro DDD projekt včetně zapojení do CI ukazuje kapitola
[Testování DDD](/testovani-ddd#architektonicke-testy). Zápis pravidel se
mezi nástroji liší, tři pravidla výše vyjádří oba.

:::callout{type="pattern"}
### Bez architektonických testů je Modules jen přání {#phparkitect-tip-heading}

Bez vynucení v CI modulární organizace nevydrží. Stačí pár sprintů pod tlakem hot-fixů
a moduly se zase provážou; návrat je pak samostatný refaktoring, ne úprava jednoho
souboru. Nasaďte phparkitect nebo `deptrac` **od prvního commitu** a udržujte zelený
build. Stojí to jeden konfigurační soubor v repozitáři a hranice vydrží, i když přijde
pátý nový vývojář, který Evanse nečetl.

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

Tabulka shrnuje, jak čtyři vzory této kapitoly souvisejí s agregátem, doménovou
událostí a Bounded Contextem:

| Vzor | Vztah k Aggregate | Vztah k Domain Event | Vztah k Bounded Context |
|---|---|---|---|
| Specification | Validuje invariant agregátu nebo filtruje seznam agregátů | Pravidlo, které spustí event (např. *OrderEligibleForFreeShipping*) | Pravidlo žije uvnitř BC; sdílí se jen kostra vzoru v SharedKernelu |
| Domain Service | Koordinuje 2+ agregáty bez toho, aby je propojila závislostí | Volá agregáty, které pak emitují events | Žije uvnitř BC; cross-BC koordinace patří do Application Service / Saga |
| Factory | Tvoří agregát s validovaným počátečním stavem | Při vzniku obvykle nahraje první event (*OrderPlaced*) | Žije uvnitř BC; Factory pro cross-BC objekty neexistuje |
| Module | Seskupuje všechny agregáty BC do jednoho balíčku | Definuje hranici, přes kterou putují events (Outbox) | 1 modul = 1 BC (preferovaná aplikace) |

Poslední sloupec u Specification si žádá vysvětlení. Kostru vzoru (rozhraní,
`CompositeSpecification` a tři kombinátory) sdílejí všechny kontexty přes `SharedKernel`.
Konkrétní pravidlo `EligibleForFreeShipping` naopak patří jednomu kontextu a jinde
by nemělo význam. Sdílí se mechanismus, ne pravidlo.

Agregát uvnitř používá **specifikace** pro invarianty, vzniká přes **factory** (named
constructor) a s dalšími agregáty spolupracuje přes **doménovou službu**. Celá skupina
žije v jednom **modulu**, který odpovídá Bounded Contextu.

## 08.07 Anti-vzory souhrn {#antivzory}

Rychlá reference pro code review. Nápravu každého anti-vzoru rozebírá příslušná sekce výše.

| Anti-vzor | Symptom | Náprava |
|---|---|---|
| Specification jako 1-line if | `OrderTotalGreaterThanSpecification` s jediným porovnáním | Inlinujte podmínku; Specification má reprezentovat celou doménovou otázku |
| Specification reimplementující SQL | Specifikace má dvě **nezávislé** verze pravidla – jedna v PHP, druhá v DQL, každá jinde | Držte obě podoby v jedné třídě (`QuerySpecification`) a jistěte je kontraktním testem |
| „*Service“ všude | `OrderService`, `CustomerService` obsahuje doménovou logiku, kterou by měla obsahovat Entity | Přesuňte logiku do Entity; Domain Service jen pro operace bez vlastníka |
| Application Service vydávaný za Domain Service | Doménová Service má v konstruktoru `EntityManager` a volá `flush()` | Rozdělte na Domain Service (logika) + Application Handler (orchestrace) |
| Factory pro každý objekt | U každé třídy v doméně existuje samostatná Factory class | Static method (named constructor) v agregátu; Factory class jen pokud nutně potřebujete DI |
| Veřejný konstruktor agregátu | Vně agregátu lze volat `new Order(...)` a obejít validaci | Privátní konstruktor + `::place()` / `::reconstitute()` |
| Type packaging (`src/Entity/`, `src/Service/`) | Adresářová struktura ukazuje technologii, ne doménu | Přejděte na 1 modul = 1 BC; vynuťte phparkitect |
| Modules bez architektonických testů | Konvence existují, ale nikdo je nekontroluje – eroze při prvním hot-fix tlaku | Nasaďte phparkitect/deptrac do CI od prvního commitu |
| Cross-BC import bez ACL | `App\Billing\Invoice` přímo importuje `App\Ordering\Order` | Integrace přes domain events (Outbox); v cílovém BC mapper na lokální typ |

Detailní rozbor doménových anti-vzorů – anémický model, transaction script, „Big
Ball of Mud“ – najdete v kapitole
[Anti-vzory v DDD](/anti-vzory).

## 08.08 Shrnutí {#summary}

Specifications, Domain Services, Factories a Modules jsou čtyři vzory z Evansova
taktického katalogu, které praktičtí průvodci vynechávají. Bez nich agregáty bobtnají,
doménový model chudne a struktura projektu zakrývá doménu.

- **Specification Pattern** proměňuje booleovská doménová pravidla
  v prvotřídní objekty s mluvícími jmény. Kombinátory `and`,
  `or`, `not` skládají pravidla bez vnořených `if`-ů,
  double-dispatch drží PHP i dotazovou podobu pravidla (Doctrine `Criteria`) v jedné třídě.
- **Domain Services** zachytávají doménovou logiku, která nepatří
  do žádné entity ani hodnotového objektu. Jsou bezstavové, žijí v Domain vrstvě
  a nevolají perzistenci. Když se zamění s Application nebo Infrastructure Service,
  logika odteče z entit a model zanémií.
- **Factories** řeší komplexní vznik agregátu. Preferovaná forma je
  named constructor (statická metoda na agregátu) s privátním konstruktorem.
  Samostatná Factory class přichází na řadu, jen když potřebujete DI závislosti.
- **Modules** organizují kód podle Ubiquitous Language, ne podle
  technických vrstev. V Symfony 8 na to stačí výchozí PSR-4 mapování `App\` na `src/`.
  Skutečnou cenu má až publikované rozhraní modulu a vynucení hranic v CI
  přes phparkitect nebo deptrac.

Přeskočená vrstva tím nekončí. Evansova kapitola *Supple Design* obsahuje dalších
osm vzorů: Intention-Revealing Interfaces, Side-Effect-Free Functions, Assertions,
Standalone Classes, Closure of Operations, Declarative Design, Drawing on Established
Formalisms a Conceptual Contours. Kniha je systematicky nepokrývá, protože jde o vzory
na úrovni jednotlivých metod a signatur, ne stavebních bloků modelu. Pro pokračování
v taktickém designu jsou dalším čtením.

Čtyři vzory kapitoly společně drží agregát v rozumné velikosti, doménu oddělenou
od infrastruktury a projekt čitelný i po roce vývoje. Nasazují se postupně. Na první
iteraci stačí *1 modul = 1 BC*, named constructor pro 2–3 hlavní agregáty a Domain
Service tam, kde dosud byla „*Service“ bez vlastníka. Specifikace se vyplatí ve chvíli,
kdy se objeví druhá nebo třetí kombinace téhož pravidla.

Jak se agregáty chovají při tisících transakcí za sekundu, kde má DDD overhead
a jak ho minimalizovat, ukazuje kapitola
[Read modely, projekce a výkon](/vykonnostni-aspekty). Kapitola
[Anti-vzory v DDD](/anti-vzory) doplňuje detail
u anémického modelu, který v sekci 08.03 padl jen krátce.

:::faq{}
- question: 'Kdy přesně se vyplatí Specification Pattern?'
  answer: 'Vyplatí se, když stejné nebo příbuzné pravidlo potřebujete na nejméně dvou místech, případně ho uplatňujete v doméně i v repozitáři přes double-dispatch. Pravidlo použité jednou a o jednom řádku kódu nepotřebuje samostatnou třídu, patří inline. Hlavní test: má pravidlo doménové jméno, které tým používá v debatách (<em>premium customer</em>, <em>eligible for free shipping</em>)? Pokud ano, Specification tomu jménu dá kód. Třída pojmenovaná <code>OrderTotalGreaterThanSpec</code> je jen operátor a patří zpět do inline ifu. Detail v <a href="#spec-kdy">sekci Specification – Kdy použít</a>.'
- question: 'Má Domain Service mít stav?'
  answer: 'Ne. Domain Service je z definice <strong>stateless</strong> – žádné instance variables měnící se mezi voláními, žádný interní cache, žádný čítač. Se stavem se ztrácí idempotence a bezpečnost při souběhu. Závislosti jsou ale jiné téma než stav a odpověď na ně kategorická není: <code>Mailer</code> nebo HTTP klient službu skutečně posouvají do Application či Infrastructure vrstvy, u repozitáře se zdroje rozcházejí. Khorikov připouští <em>impure</em> doménovou službu, Noback umísťuje rozhraní repozitáře přímo do Domain vrstvy. Vodítko: nejdřív zvažte, jestli data nemá dodat volající; když je jinak nezískáte, závislost na doménovém rozhraní je přijatelná. Detail v <a href="#ds-priklad">sekci MoneyTransferService</a> a <a href="#ds-srovnani">srovnávací tabulce</a>.'
- question: 'Factory metoda nebo Factory class – jak se rozhodnout?'
  answer: 'Výchozí volbou je <strong>named constructor</strong> (statická metoda na agregátu). Vernon (2013) staví v kapitole 11 <em>Factories</em> do popředí factory metodu na agregátním kořeni a samostatnou factory řeší až jako druhou možnost na úrovni service. PHP podobu s privátním konstruktorem popsal Mathias Verraes v textu <em>Named Constructors in PHP</em> (2014). Samostatná Factory class přichází na řadu, když vznik agregátu vyžaduje DI závislosti – typicky <code>CartRepository</code>, <code>PricingService</code>, <code>ClockInterface</code>, externí lookup. Jednu službu jde statické metodě předat parametrem, s několika závislostmi by je ale musel shánět každý volající. Factory class bez jediné DI závislosti, která jen volá <code>new Order(...)</code>, je redundantní vrstva a patří smazat. Detail v <a href="#fac-class">sekci Factory class</a>.'
- question: 'Jak vynutit hranice mezi Moduly v PHP projektu?'
  answer: 'Samotná konvence nevydrží – vývojář pod tlakem „udělej rychle“ přidá cross-BC import za pět minut. Spolehlivé vynucení vyžaduje <strong>nástroj v CI</strong>: <a href="https://github.com/phparkitect/arkitect" target="_blank" rel="noopener">phparkitect</a> nebo <a href="https://github.com/deptrac/deptrac" target="_blank" rel="noopener">deptrac</a>. Pravidla typu „App\\Ordering nesmí závisět na App\\Billing“ nebo „App\\Ordering\\Domain nesmí volat EntityManager“ při porušení shodí CI build. Stojí to jeden konfigurační soubor a modulární organizace přežije i pátého nového vývojáře. Detail v <a href="#mod-phparkitect">sekci Architecture testing</a>.'
- question: 'Jak má vypadat namespace třídy, která sedí na hranici dvou Bounded Contextů?'
  answer: 'V čistém DDD <strong>žádná třída na hranici dvou BC nesedí</strong>. Takový případ signalizuje, že hranice je špatně nakreslená nebo že potřebujete <a href="/context-mapping">Anti-Corruption Layer</a> (ACL). Konkrétní řešení: v každém BC žije <em>vlastní</em> typ s vlastním namespace. <code>App\\Ordering\\Domain\\ValueObject\\CustomerId</code> v Ordering kontextu, <code>App\\Billing\\Domain\\ValueObject\\CustomerId</code> v Billing kontextu, případně mapování přes events. Pokud opravdu existuje univerzální koncept (<code>Money</code>, <code>Currency</code>, <code>Country</code>), patří do <strong>SharedKernel</strong> – ale tento balíček musí být explicitně malý, stabilní a s dohodou všech týmů. Souvisí <a href="#mod-bc">Modul jako Bounded Context</a>.'
- question: 'Můžu Specification a Domain Service kombinovat?'
  answer: 'Ano, v praxi se kombinují často. Domain Service obvykle koordinuje 2+ agregáty a jedno z rozhodnutí přitom nese Specification – typicky „může tato objednávka projít k expedici?“ = kompozice <code>HasBeenPaid AND ItemsInStock AND NotInBlacklist</code>. Domain Service tu specifikaci instancuje a volá <code>isSatisfiedBy()</code>, podle výsledku zavolá metodu na agregátu. Vzory se vzájemně doplňují: Specification je <em>pravidlo</em>, Domain Service je <em>akce</em>, která pravidlo aplikuje na 2+ agregáty. Detail v <a href="#vztahy">sekci 08.06 Vztah těchto vzorů</a>.'
:::
