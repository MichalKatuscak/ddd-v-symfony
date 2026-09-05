---
route: when_not_to_use_ddd
path: /kdy-nepouzivat-ddd
title: Kdy DDD nepoužívat – upřímně
page_title: "Kdy DDD nepoužívat – upřímně | DDD Symfony"
meta_description: "Sedm situací, kdy DDD nepoužívat – s alternativami, ukázkami kódu a rozhodovacím stromem pro PHP vývojáře, kteří nechtějí zavádět zbytečnou komplexitu."
meta_keywords: "kdy nepoužívat DDD, DDD nevhodné projekty, DDD alternativy, DDD limity, DDD CRUD, DDD startup, DDD malý tým, rozhodovací strom DDD"
og_type: article
published: "2026-03-26"
modified: "2026-07-08"
breadcrumb_name: Kdy DDD nepoužívat
schema_type: TechArticle
schema_headline: "Kdy DDD nepoužívat – upřímně"
chapter_number: "22"
category: Praxe
deck: "7 konkrétních situací, kdy DDD nepoužívat – s alternativami, ukázkami kódu a rozhodovacím stromem. Upřímný průvodce pro PHP vývojáře, kteří nechtějí zavádět zbytečnou komplexitu."
reading_time: 14
difficulty: 2
---

Tato kapitola je **rozhodovací rámec**: kdy DDD nasadit a kdy ne. Pro **detailní katalog
kódových anti-vzorů**, kdy už DDD nasadíte, ale uděláte chyby, viz [Anti-vzory](/anti-vzory).
Pro **provozní třenice** s Doctrine, Messenger a Symfony, kdy DDD je správně nasazen, ale
infrastruktura bolí, viz [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli).

DDD není architektura pro každý projekt. Špatně zvolená aplikace DDD přidává vrstvy abstrakce,
zpomaluje vývoj a frustruje tým – aniž by přinesla cokoliv hodnotného.
Tato kapitola říká přímo, kdy DDD vynechat a co místo toho použít.

## 22.01 Rozhodovací strom: kolik DDD dát které části systému? {#rozhodovaci-strom}

Otázka „mám použít DDD?“ je špatně položená. Jeden systém běžně obsahuje část, která rozhoduje
o konkurenceschopnosti, část, bez které to nejede, ale nikoho nezajímá, a část, která se dá koupit.
Každá si zaslouží jinou odpověď. Strom níže se proto ptá na **jednu subdoménu**, ne na projekt,
a prochází se za každou zvlášť.

Postupuje ve třech krocích:

1. **Dá se subdoména pořídit hotová?** Účetnictví, rozesílání e-mailů, platební brána. Pokud ano,
   je to Generic – kupte ji. DDD tu řeší jen integraci, typicky Anti-Corruption Layer na hranici.
2. **Odlišuje vás od konkurence?** Pokud ne, je to Supporting. Plný taktický návrh se vyplatí jen
   při splnění tří podmínek: tým ho zvládá, model je inovativní a má vydržet roky. Jinak stačí
   lehký model nebo rovnou CRUD – hlavně to musí zaznít nahlas a nevydávejte adresář `Domain/`
   za doménový model.
3. **Je to Core Domain?** Pak zbývají dvě brány, které rozhodují o načasování, ne o vhodnosti:
   přístup k doménovým expertům a stabilita domény.

:::diagram{fig="22.1-A" title="Rozhodovací strom: kolik DDD dát této subdoméně" src="images/diagrams/9_when_not_to_use_ddd/diagram.svg"}
:::

Klasifikaci subdomén rozvádí [kapitola o subdoménách](/subdomeny#tri-kategorie), hybridní přístup
uvnitř jednoho systému pak [sekce 22.09](#hybrid-subdomain).

Napříč kapitolou platí ještě jedno rozlišení. „Nepoužít DDD“ znamená v praxi téměř vždy
„nesahat po taktických vzorech“: agregátech, doménových událostech, repozitářích.
Ubiquitous Language, hranice kontextů a mapování domény se vyplatí i tam, kde se plný
doménový model nevyplatí. Sedm situací níže mluví o taktické vrstvě, pokud není řečeno jinak.

## 22.02 1. CRUD admin a jednoduchý backoffice {#crud-admin}

Aplikace, kde uživatel vytvoří záznam, upraví ho a smaže. Formulář mapuje 1:1 na tabulku.
Žádná doménová logika – jen persistence.

DDD zde přidá agregáty, repozitáře, doménové události a value objekty pro věci,
které jsou přirozeně jen řádky v databázi. Ukázka níže to vyčísluje: šest tříd místo
jedné a žádná přidaná hodnota.

Eric Evans to u vzoru Core Domain říká bez obalu:
*The harsh reality is that not all parts of the design are going to be equally refined.
Priorities must be set.*
Propracovaný model patří tam, kde je **komplexní doménová logika**. CRUD operace
ji nemají – jsou to čtení a zápisy dat bez doménových pravidel.

:::callout{type="pattern"}
#### Srovnání: DDD vs. jednoduchý přístup pro CRUD admin {#crud-compare-heading}

:::code{language="php" filename="src/ArticleTitle.php"}
<?php
// ❌ Over-engineered DDD přístup pro prostý CRUD
// -- 6 tříd pro jednu operaci --

final class ArticleTitle {                          // Value Object
    public function __construct(public readonly string $value) {
        if (strlen($value) === 0) throw new \InvalidArgumentException('...');
    }
}

final class Article {                               // Aggregate Root
    private string $id;
    private ArticleTitle $title;
    private array $domainEvents = [];

    public static function create(ArticleTitle $title): self { /* ... */ }
    public function rename(ArticleTitle $newTitle): void {
        $this->title = $newTitle;
        $this->domainEvents[] = new ArticleRenamed($this->id, $newTitle);
    }
}

interface ArticleRepository { /* ... */ }           // Repository interface
final class DoctrineArticleRepository { /* ... */ } // Repository implementation
final class RenameArticleCommand { /* ... */ }      // Command
final class RenameArticleHandler {                  // Command Handler
    public function __invoke(RenameArticleCommand $cmd): void { /* ... */ }
}
:::

:::code{language="php" filename="src/Controller/Admin/ArticleCrudController.php"}
<?php
// ✅ Jednoduchý přístup - EasyAdmin CRUD controller
// -- 1 třída, hotovo --

namespace App\Controller\Admin;

use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Název článku'),
        ];
    }
}
:::
:::

:::callout{type="note"}
**Doporučené alternativy:**

- **EasyAdmin 5** – pro backoffice a CMS adminy. Konfigurací, ne kódem; běží na Symfony 8 a pracuje nad Doctrine ORM entitami.
- **API Platform** – když má z CRUDu vzniknout API. Atributy nad Doctrine entitou, zbytek obstará bundle.
- **Symfony Forms + Doctrine Entity přímo v controlleru** – pro jednoduchý CRUD bez doménové logiky.

Doménový model zavádíte tehdy, když máte doménu. CRUD admin doménu nemá.
Pozor na záměnu s pojmem
<a href="https://martinfowler.com/bliki/AnemicDomainModel.html" target="_blank" rel="noopener">Anemic Domain Model</a>
od Martina Fowlera: ten popisuje doménovou logiku přesunutou do servisní vrstvy
místo do modelu. Over-engineering CRUDu je jiný problém – DDD ceremonie nad
doménou, která žádnou logiku nemá.
:::

## 22.03 2. Startup – doména se mění každý sprint {#startup}

Hledáte product-market fit. Co dnes je objednávka, zítra je subscription. Zákazník se přes
noc změní v partnera. Ubiquitous Language nelze vybudovat, pokud doménový model
ještě neexistuje.

DDD předpokládá, že doméně rozumíte dost dobře na to, abyste ji modelovali. Ve fázi hledání
to neplatí. Každý refaktoring agregátů a [bounded contextů](/zakladni-koncepty)
vás zpomaluje a vývojové iterace se soustředí na architekturu místo na hodnotu pro zákazníka.

Strategické DDD nástroje – zejména
[Event Storming](/event-storming) a [Context Mapping](/context-mapping) – ve fázi hledání
naopak pomáhají. Dávají jména tomu, čemu ještě nerozumíte.
Co nedává smysl, je taktické DDD (agregáty, doménové události, repozitáře) pro model,
který se příští týden změní od základů.

:::callout{type="pattern"}
#### Startup realita: pivot za 2 týdny {#startup-compare-heading}

:::code{language="php" filename="src/Order.php"}
<?php
// ❌ Taktické DDD pro nestabilní doménu - za 2 týdny přepíšete všechno
// Bounded Context "Orders" s agregáty, events, repositories...

final class Order {                                 // Aggregate Root
    private OrderId $id;
    private CustomerId $customerId;                 // ← za 2 týdny: PartnerId
    private OrderStatus $status;                    // ← za 2 týdny: SubscriptionStatus
    /** @var OrderLine[] */
    private array $lines;                           // ← za 2 týdny: neexistuje

    public function place(): void { /* domain events, invariants... */ }
}

// + OrderPlaced event, OrderRepository interface, PlaceOrderHandler...
// Celý tento kód bude za 2 týdny v koši.
:::

:::code{language="php" filename="src/Order.php"}
<?php
// ✅ Flat MVC - pivot je levný
// Entity se změní, controller se upraví, hotovo.

#[ORM\Entity]
class Order {
    #[ORM\Id, ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $customerName;   // příští sprint: partnerName - 1 rename

    #[ORM\Column(length: 50)]
    private string $status = 'new'; // příští sprint: subscriptionStatus - 1 rename
}
:::
:::

:::callout{type="note"}
**Doporučené alternativy:**

- **Flat MVC s Doctrine Entities** – rychlé iterace, změny jsou levné.
- Až se doména stabilizuje (3–6 měsíců provozu), teprve pak zvažte zavedení DDD vzorů selektivně – viz [Migrace z CRUD na DDD](/migrace-z-crud).
- Strategické nástroje DDD (Event Storming, Context Mapping) je vhodné zavést od začátku – pomáhají rychleji porozumět doméně.
:::

## 22.04 3. Malý tým bez doménových expertů {#small-team}

DDD stojí na spolupráci vývojářů s lidmi, kteří doméně rozumí – zákazníci, produktoví manažeři,
analytici. Bez nich modelujete doménu sami, z hlavy, bez zpětné vazby.

Vznikne model podle vývojářovy představy, ne podle toho, jak byznys skutečně běží.
To je přesně opak toho, k čemu DDD slouží. Vaughn Vernon v *Implementing Domain-Driven Design*
zdůrazňuje, že bez spolupráce s doménovými experty se Ubiquitous Language stává jen technickým žargonem.

**Zásadní rozdíl oproti bodu 7:** Zde doménové experty nemáte v týmu (malý tým = vývojáři
dělají všechno). V bodě 7 experti existují, ale doména samotná je nejasná – nikdo neví, co modelovat.
Řešení je odlišné: zde potřebujete lidi, tam potřebujete znalosti.

:::callout{type="note"}
**Doporučené alternativy:**

Vrstvená architektura (Controller → Service → Repository) drží kód strukturovaný bez přílišné abstrakce. DDD zaveďte až ve chvíli, kdy máte přístup k doménovým expertům a čas na [Event Storming](/event-storming).
:::

## 22.05 4. Data pipeline, ETL a reportovací systémy {#data-pipeline}

Systém načítá data z externích zdrojů, transformuje je a ukládá nebo reportuje.
Žádná doménová pravidla, žádné invarianty, žádná doménová logika.
Jde o přesun a transformaci dat – ne o modelování domény.

Agregáty chrání invarianty. Pokud žádné nemáte, zbude z agregátu prázdný obal a režie
navíc. Evans definuje agregát jako *cluster of associated objects that we treat as a unit
for the purpose of data changes* a v *DDD Reference* k tomu doplňuje, že vlastnosti
a invarianty se definují pro celek a jejich vynucení patří kořeni. Pipeline nemá co
vynucovat: pravidla žijí v transformačním kroku, ne ve stavu objektu.

:::callout{type="note"}
**Doporučené alternativy:**

- **Servisní vrstva s obyčejnými PHP objekty** – jednoduché třídy pro transformaci, bez agregátů.
- **Symfony Messenger** pro asynchronní zpracování pipeline kroků – bez režie DDD. Viz [kapitola o CQRS](/cqrs) pro inspiraci, jak Messenger používat v praxi.
- Pro větší pipeline existuje v PHP hotový nástroj: `flow-php/etl` nabízí DataFrame API nad CSV, JSON, XML, Parquet i relační databází. Vlastní transformační vrstvu tak často psát nemusíte.
:::

## 22.06 5. Projekt s životností kratší než rok {#short-lived}

Interní nástroj, landing page, jednorázová migrace, prototyp pro demo zákazníkovi.
Kód napíšete, použijete a zahodíte.

DDD investice se vrátí na projektech, které žijí roky a rostou. Na krátkodobých projektech
tým zaplatí cenu DDD (čas, komplexita, učební křivka), aniž by kdy sklidil
výhody (udržovatelnost, schopnost rozvíjet se).

**Proč zrovna rok?** Hranice „jeden rok“ není absolutní – je to orientační bod
založený na praxi. DDD vyžaduje počáteční investici: modelování domény, budování
Ubiquitous Language, návrh agregátů a bounded contextů. Tato investice se typicky začíná
vracet ve chvíli, kdy projekt povyroste a tým začne těžit z čistých doménových hranic.
U projektů, které skončí do roka, se to nestihne.

Jde o autorský odhad, ne o měřenou veličinu. Fowler klade v
<a href="https://martinfowler.com/bliki/DesignStaminaHypothesis.html" target="_blank" rel="noopener">Design Stamina Hypothesis</a>
hranici návratnosti návrhu podstatně níž – spíš týdny než měsíce – a sám dodává, že jde
o hypotézu, protože produktivitu ani kvalitu návrhu měřit neumíme. Rozdíl je v předmětu.
Fowler mluví o návrhu obecně, zde jde o plný taktický aparát DDD, který má vyšší učební
křivku i vyšší vstupní režii.

Vernon v *Domain-Driven Design Distilled* to obrací k volbě, kam úsilí namířit: žádná
organizace nemůže vynikat ve všem, takže si musí pečlivě vybrat, v čem vyniknout chce.
Kde tahle volba nepadne na váš projekt, nevyplatí se do něj investovat plný taktický návrh.

:::callout{type="note"}
**Doporučené alternativy:**

- **Prostý Symfony controller + Doctrine** – nejkratší cesta od požadavku k funkčnímu kódu.
- Pokud projekt nečekaně vyroste, refaktorovat z prostého kódu na DDD je snazší než vysvětlovat, proč krátkodobý projekt má 40 tříd. Postup najdete v [Migraci z CRUD](/migrace-z-crud).
:::

## 22.07 6. Tým DDD nezná a čas na učení není {#no-knowledge}

DDD vyžaduje, aby tým rozuměl konceptům –
[aggregates, bounded contexts, domain events, repositories](/zakladni-koncepty).
Špatně pochopené DDD je horší než žádné DDD: produkuje pseudo-DDD kód,
který má přidanou komplexitu bez architektonických výhod. Jak takový kód
vypadá v detailu, ukazuje katalog
[Anti-vzory](/anti-vzory#anemicky-domenovy-model).

„Naučíme se za pochodu“ na produkčním projektu s deadlinem je recept na technický dluh,
který bude bolet roky.

:::callout{type="pattern"}
#### Pseudo-DDD: vypadá jako DDD, ale není {#pseudo-ddd-heading}

:::code{language="php" filename="src/OrderAggregate.php"}
<?php
// ❌ Pseudo-DDD - tým „zavedl DDD“ bez pochopení
// Výsledek: Doctrine entity přejmenovaná na „Aggregate“, setter zůstal

final class OrderAggregate  // ← jen přejmenovaná Entity, ne skutečný agregát
{
    private int $id;
    private string $status;

    // Setter - agregát nemá chránit invarianty, jen přepisuje data
    public function setStatus(string $status): void
    {
        $this->status = $status;  // žádná validace, žádná doménová pravidla
    }

    // „Repository“ je jen přejmenovaný EntityRepository
    // „Command“ je jen přejmenovaný DTO bez handleru
    // „Domain Event“ se dispatchuje, ale nikdo ho neposlouchá
}
:::

:::code{language="php" filename="src/Order.php"}
<?php
// ✅ Správné DDD - agregát chrání invarianty

final class Order extends AggregateRoot
{
    private OrderId $id;
    private OrderStatus $status;

    public function cancel(Clock $clock): void
    {
        if ($this->status->isShipped()) {
            throw new OrderAlreadyShipped($this->id);
        }
        if ($this->status->isCancelled()) {
            throw new OrderAlreadyCancelled($this->id);
        }

        $this->status = OrderStatus::Cancelled;
        $this->record(new OrderCancelled($this->id, $clock->now()));
    }
    // Žádné settery - stav se mění jen přes explicitní doménové operace
}
:::
:::

:::callout{type="note"}
**Doporučené alternativy:**

- Klasická architektura, kterou tým zná dobře – srozumitelný kód je vždy lepší než „správná“ architektura, které nikdo nerozumí.
- DDD zaveďte na vedlejším projektu nebo v ohraničené části systému jako experiment, pak přenášejte zkušenosti postupně. Evans pro to má jméno: **Bubble Context** – malý kontext oddělený Anticorruption Layerem, který nevyžaduje velký závazek k DDD (*Getting Started with DDD When Surrounded by Legacy Systems*, 2013).
- Jako odrazový můstek se osvědčil Vernon: *Domain-Driven Design Distilled* – nejstručnější úvod do DDD konceptů.
:::

## 22.08 7. Doména je nejasná, experti nejsou k dispozici {#unclear-domain}

Zákazník neví, co chce. Požadavky jsou vágní. Doménový expert buď neexistuje, nebo
nemá čas spolupracovat. Modelujete pak bez pevného základu.

**Zásadní rozdíl oproti bodu 3:** V bodě 3 chybí lidé – máte malý tým bez přístupu
k expertům, ale doména může být jasná (pojišťovnictví, e-commerce...). Zde je problém v tom,
že **nikdo doménu nechápe** – ani potenciální experti. Požadavky se teprve formují,
pojmy nejsou ustálené, doménová pravidla se mění s každou schůzkou.

DDD bez znalosti domény je jen přejmenování tříd. „Order“, „Customer“, „Product“ –
vypadá to jako DDD, ale model neodráží skutečnou doménu. Za rok, až doménu pochopíte,
přepíšete stejně všechno.

:::callout{type="note"}
**Doporučené alternativy:**

- **Event Storming napřed** – než napíšete první řádek kódu, zmapujte doménu se stakeholdery. Bez toho DDD nemá co modelovat. Více o Event Stormingu v kapitole [Event Storming](/event-storming).
- Pokud Event Storming není možný, začněte s jednoduchým kódem a DDD zaveďte retrospektivně, až doménu pochopíte – viz [Migrace z CRUD na DDD](/migrace-z-crud).
:::

## 22.09 Hybrid podle typu subdomény – DDD tam, kde dává smysl {#hybrid-subdomain}

Volba „celé DDD ano, nebo celé ne“ málokdy odpovídá realitě projektu. Khononov v *Learning DDD* (2021) prosazuje architekturu **podle typu subdomény**:
každý Bounded Context dostane tolik DDD, kolik odpovídá jeho [kategorii subdomény](/subdomeny#tri-kategorie):

| Typ subdomény | Architektonický styl | Důvod |
|---|---|---|
| **Core Domain** | Plné DDD (taktické + strategické vzory, agregáty, eventy) | Konkurenční výhoda, komplexní pravidla, vysoký ROI investice do modelu |
| **Supporting Subdomain** | Lehké DDD (entity + repository, žádné agregáty) nebo Active Record | Pravidla existují, ale nejsou diferenciační. Plné DDD je over-engineering. |
| **Generic Subdomain** | CRUD nebo SaaS (auth, notifikace) | Nepřináší konkurenční výhodu, kupte nebo použijte hotové řešení. |

Active Record v tabulce pochází z prostředí, kde ho framework nabízí. Doctrine ORM 3 je
Data Mapper a Active Record nemá. Praktickým ekvivalentem je Doctrine entita s veřejnými
settery, kterou obsluhuje tenká servisní třída – vědomě anemický model pro kontext, kde
se doménový model nevyplatí. Volbě stylu podle kontextu se věnuje kapitola
[Architektonické styly](/architektonicke-styly).

Konkrétně: pojišťovna má **Core** Underwriting (DDD ano), **Supporting** Customer
Management (lehké DDD), **Generic** Notifikace (CRUD nebo SaaS jako Twilio).
Plné DDD ve všech třech kontextech znamená trojnásobnou režii, přičemž ve dvou ze tří
se investice nevrátí.

V Symfony projektu se to projevuje strukturou monolitu, kde
`src/Underwriting/` má plnou DDD strukturu (Domain/Application/Infrastructure
+ agregáty + eventy), `src/Customer/` má jednodušší rozdělení Entity + Repository,
a `src/Notification/` je čistý EasyAdmin nad Doctrine entitami nebo dokonce
externí service.

### Pět otázek na složitost domény {#complexity-heuristics}

Typ subdomény se nepozná z organigramu. Khononov k tomu nabízí otázky, které se dají
položit přímo na sezení s doménovým expertem:

- Popisují experti systém v CRUD termínech („založíme zákazníka, upravíme objednávku“)? Pak je logika nejspíš jednoduchá.
- Točí se pravidla hlavně kolem validace vstupu? Také jednoduché.
- Jsou v doméně netriviální výpočty a algoritmy? Složité.
- Existují invarianty, které musí platit vždy a napříč několika objekty? Složité.
- Kolik scénářů má jedna operace a jak vysoká by byla cyklomatická složitost jejího zápisu?

Hraniční případ má vlastní pravidlo: pokud je Supporting subdoména složitá a ta složitost
má obchodní důvod, jde nejspíš o Core subdoménu v přestrojení. Legitimní je i opačný extrém.
Projekt nemusí mít žádnou Core subdoménu, a pak se plný doménový model nevyplatí nikde.

:::callout{type="warn"}
### Migration cost paradox {#migration-paradox-heading}

Standardní rozhodnutí je „nový projekt → DDD od začátku, legacy → nech být“.
Reálný kontext bývá složitější: legacy CRUD kód přerůstá v komplexní doménu,
ale migrace celého kódu na DDD je nereálná. Nastává **migration cost paradox**:

- Udržovat anemický legacy stojí každý rok určitou částku a ta s časem roste.
- Big-bang rewrite na DDD stojí násobek ročních nákladů najednou a nese riziko regrese.
- Postupná migrace přes [Strangler Fig](/migrace-z-crud) vyjde levněji než rewrite a rozloží
  se v čase, zato po celou dobu ubírá produktivitu.

Kdy je migrace na DDD ekonomicky výhodná: pouze když očekávaný přínos za
zbývající životnost systému převýší cenu migrace s dostatečnou rezervou. Pro projekt s ETA 1–2 roky před koncem životnosti
je migrace obchodní rozhodnutí, ne technické.

Odhad ceny je přitom nejslabší část rozvahy. Praktičtější jsou tři heuristiky.
Verraes rozhoduje podle kvality původního návrhu: co bylo navrženo dobře, se opravuje;
co špatně, se nahrazuje. Fowler přidává podmínku vlastnictví – o obětování
architektury rozhoduje tým, který ji napsal. A Spolsky připomíná, co se přepisem od nuly
zahazuje: ne kód, ale roky nasbíraných oprav chyb.

Rozhodnutí navíc nedrží jen technické faktory. Systém může být během migrace
nahrazen po akvizici, sloučen s platformou kupujícího nebo odstaven při změně
strategie – a odvedená práce padne bez ohledu na to, jak dobře byla udělaná.
:::

### Pseudo-DDD – varování před cargo cultem {#pseudo-ddd-cargo-cult-heading}

Nejhorší výsledek není „nepoužít DDD“. Je to **pseudo-DDD**: tým má adresářovou
strukturu DDD (`Domain/`, `Application/`, `Infrastructure/`), používá slovník
DDD ve standupech, ale doménový model je anemický CRUD. Symptomy:

- Agregáty mají gettery, settery a žádné chování.
- Doménové eventy se publikují, ale žádný handler na ně neposlouchá ve smyslu
  doménové logiky – jen logování nebo audit.
- Bounded Contexts existují jako adresáře, ale tým je přejmenoval z původního
  technického dělení (`UserModule/` → `UserBoundedContext/`).
- Code review diskuse jsou o „je toto správné DDD“ místo „chrání tato změna
  invariant“.

Pseudo-DDD má všechny náklady DDD (víc kódu, učební křivka) a žádný přínos
(invarianty nejsou chráněné, doména není modelovaná). Tuhle bilanci popsal Fowler už
u anemického modelu: nese všechny náklady doménového modelu, aniž by přinesl jakýkoli
z jeho užitků. Pseudo-DDD je jeho ceremoniální varianta. V tomto stavu je **honest
CRUD lepší volba** – přiznejte si, že doména komplexní logiku nemá, a zjednodušte.

Evans má pro otázku „kde uvnitř systému nemodelovat“ vlastní pojmenované odpovědi.
[Big Ball of Mud](/context-mapping#big-ball-of-mud) říká, že kolem nepořádku se nakreslí
hranice, pojmenuje se a sofistikované modelování se dovnitř nepouští.
[Separate Ways](/context-mapping#separate-ways) řeší druhý případ: integrace stojí víc,
než kolik přinese, takže kontexty zůstanou nepropojené a každý si najde vlastní
jednoduché řešení. Obojí je strategické rozhodnutí, ne rezignace.

Detail v [kapitole o anti-vzorech](/anti-vzory#anemicky-domenovy-model).

## 22.10 Kdy DDD naopak smysl má {#when-ddd-fits}

DDD se hodí na specifický kontext, ne na každý projekt. Podmínky níže jsou druhou stranou
bran rozhodovacího stromu. Plné taktické DDD na úrovni celého projektu se vyplatí tehdy,
když platí **všechny**:

| Podmínka | Proč záleží | Příklad z praxe |
|---|---|---|
| Komplexní doménová logika (ne jen CRUD) | DDD chrání invarianty a modeluje pravidla – bez pravidel nemá co chránit | Pojistné smlouvy s 50+ doménovými pravidly pro schválení, pojistné události s workflow |
| Projekt bude žít a růst roky | Investice do architektury se vrátí jen při dostatečném horizontu | Core banking systém, ERP, zdravotnický informační systém |
| Přístup k doménovým expertům | Ubiquitous Language a model se tvoří ve spolupráci – ne ze vzduchoprázdna | Pojistný matematik, zkušený účetní, vedoucí skladu – lidé, kteří žijí doménou denně |
| Tým rozumí DDD nebo má čas se učit | Špatně implementované DDD je horší než žádné DDD | Tým prošel školením, má za sebou alespoň jeden DDD projekt, nebo má 2–3 měsíce na rozjezd |
| Více bounded contextů nebo mikroservisy | DDD dává přirozené hranice pro dekompozici systému | E-commerce s oddělenými kontexty: katalog, objednávky, platby, logistika |

Když některá podmínka neplatí, není to automatické „ne“. Znamená to, že plné DDD nepatří
do celého systému a rozhodnutí se přesouvá na jednotlivé kontexty podle
[typu subdomény](#hybrid-subdomain). Tam, kde neplatí ani většina z pěti podmínek,
je jednodušší architektura levnější i poctivější volba.

Jedna věc na závěr, protože se z kapitoly dá snadno vyčíst opak. „Ne DDD“ neznamená „ne návrh“.
Vernon v *Domain-Driven Design Distilled* cituje Douglase Martina: alternativou dobrého
návrhu je špatný návrh, ne žádný návrh. Flat MVC, prostý controller i EasyAdmin potřebují
moduly, hranice, pojmenování a testy. Fowler totéž říká o architektuře, kterou plánujete
jednou zahodit: dobrá modularita zůstává součástí zdravé kódové základny i tam.

Detailní implementaci DDD v Symfony najdete v [implementační kapitole](/implementace-v-symfony).
Reálné problémy při zavádění DDD popisuje kapitola [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli).
Pokud jste se rozhodli DDD zavést postupně v existujícím projektu, začněte [migrací z CRUD](/migrace-z-crud).

:::faq{}
- question: Vyplatí se DDD pro jednoduchý CRUD admin?
  answer: 'Ne. CRUD administrace, která pouze mapuje formulář na databázovou tabulku, postrádá doménovou logiku, kterou by DDD mohlo chránit. Nasazení agregátů, value objectů a repozitářů nad prostým „create/update/delete“ přináší komplexitu bez odpovídající hodnoty. V této situaci je lepší volbou přímá CRUD implementace, například přes EasyAdmin nebo Sonata Admin. Podrobněji v <a href="#crud-admin">sekci CRUD admin a jednoduchý backoffice</a>.'
- question: Má smysl DDD ve startupu, kde se doména rychle mění?
  answer: 'Spíše ne, dokud startup hledá product-market fit. DDD investuje do přesného modelování domény. Když se doména s každým sprintem překopává, tato investice se odepisuje dřív, než přinese hodnotu. Pragmatičtější je začít s jednoduchou architekturou. DDD pak zaveďte selektivně, až se jádro produktu stabilizuje a doménová pravidla začnou být sdílena napříč use casy. Rozbor situace v <a href="#startup">sekci Startup – doména se mění každý sprint</a>.'
- question: Co když tým nemá s DDD zkušenosti?
  answer: 'Bez zkušenosti s DDD tým typicky produkuje anemický model: taktické vzory (agregáty, repozitáře, events) se používají jako prázdné obaly kolem CRUD logiky, zatímco strategický design schází. Výsledkem je komplikovaná architektura bez reálných přínosů. Pokud chybí čas na učení, lepší je začít čistou, dobře strukturovanou CRUD architekturou a DDD prvky přidávat postupně, až s rostoucí doménovou složitostí. Detailní rozbor v <a href="#no-knowledge">sekci Tým DDD nezná a čas na učení není</a>.'
- question: Kdy DDD naopak smysl má?
  answer: 'DDD se vyplatí tam, kde se sejde několik podmínek současně. Patří mezi ně komplexní doménová logika s mnoha invarianty, dlouhodobý horizont projektu (roky, ne měsíce), přístup k doménovým expertům a tým s dostatečnými zkušenostmi nebo časem na učení. Typické domény, kde DDD dlouhodobě vyhrává, jsou core banking, pojišťovnictví, zdravotnictví, logistika nebo regulovaná odvětví s bohatými pravidly. Rozhodnutí by nemělo stát na popularitě DDD, ale na konkrétní povaze projektu a týmu. Rozhodovací kritéria a domény v <a href="#when-ddd-fits">sekci Kdy DDD naopak smysl má</a>.'
:::

## 22.11 Zdroje a další čtení {#zdroje}

:::callout{type="note"}
**Knihy:**

- **Eric Evans: Domain-Driven Design – Tackling Complexity in the Heart of Software**
  (Addison-Wesley, 2003, ISBN 978-0-321-12521-7).
  Základní kniha DDD. Kapitoly 1–3 definují, kdy DDD aplikovat a kdy ne.
- **Vaughn Vernon: Implementing Domain-Driven Design**
  (Addison-Wesley, 2013, ISBN 978-0-321-83457-7).
  Praktická implementace DDD s důrazem na spolupráci s doménovými experty.
- **Vaughn Vernon: Domain-Driven Design Distilled**
  (Addison-Wesley, 2016, ISBN 978-0-134-43442-1).
  Stručný úvod do DDD – vhodný pro týmy, které teprve zvažují, zda DDD zavést.
- **Scott Millett, Nick Tune: Patterns, Principles, and Practices of Domain-Driven Design**
  (Wrox/Wiley, 2015, ISBN 978-1-118-71470-6).
  Podrobný průvodce s praktickými vzory, včetně kapitol o tom, kdy DDD nedává smysl.
- **Vlad Khononov: Learning Domain-Driven Design**
  (O'Reilly, 2021, ISBN 978-1-098-10013-1).
  Zdroj vazby mezi typem subdomény a architektonickým stylem, na které stojí sekce 22.09.

**Články:**

- <a href="https://martinfowler.com/bliki/BoundedContext.html" target="_blank" rel="noopener">Martin Fowler: BoundedContext</a>
  (2014) – srozumitelné vysvětlení jednoho z hlavních DDD konceptů.
- <a href="https://martinfowler.com/bliki/AnemicDomainModel.html" target="_blank" rel="noopener">Martin Fowler: AnemicDomainModel</a>
  (2003) – proč je doménový model bez chování, s logikou v servisní vrstvě, anti-vzor.
- <a href="https://martinfowler.com/bliki/DesignStaminaHypothesis.html" target="_blank" rel="noopener">Martin Fowler: Design Stamina Hypothesis</a>
  (2007) – kdy se investice do návrhu vrací a proč to zůstává hypotéza.
- <a href="https://martinfowler.com/bliki/SacrificialArchitecture.html" target="_blank" rel="noopener">Martin Fowler: Sacrificial Architecture</a>
  (2014) – kdy je legitimní architekturu obětovat a co musí zůstat i tak.
- <a href="https://verraes.net/2016/04/repair-replace-heuristic-for-legacy-software/" target="_blank" rel="noopener">Mathias Verraes: The Repair/Replace Heuristic for Legacy Software</a>
  (2016) – opravit, nebo nahradit; rozhoduje kvalita původního návrhu.
- <a href="https://www.joelonsoftware.com/2000/04/06/things-you-should-never-do-part-i/" target="_blank" rel="noopener">Joel Spolsky: Things You Should Never Do, Part I</a>
  (2000) – co se ztrácí při přepisu od nuly.
- <a href="https://domainlanguage.com/wp-content/uploads/2016/04/GettingStartedWithDDDWhenSurroundedByLegacySystemsV1.pdf" target="_blank" rel="noopener">Eric Evans: Getting Started with DDD When Surrounded by Legacy Systems</a>
  (2013) – čtyři strategie pro legacy systémy, včetně Bubble Contextu.
:::
