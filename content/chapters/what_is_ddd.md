---
route: what_is_ddd
path: /co-je-ddd
title: Co je Domain-Driven Design?
page_title: "Co je Domain-Driven Design? Vysvětlení DDD | DDD Symfony"
meta_description: "Domain-Driven Design srozumitelně: filozofie Erica Evanse, Ubiquitous Language, Bounded Context a rozdíl mezi strategickým a taktickým designem."
meta_keywords: "Domain-Driven Design, DDD, Eric Evans, Ubiquitous Language, Bounded Context, doménový model, doménová logika, strategický design, taktický design"
og_type: article
published: "2025-04-24"
modified: "2026-05-03"
breadcrumb_name: Co je DDD
schema_type: TechArticle
schema_headline: "Co je Domain-Driven Design? Podrobné vysvětlení DDD"
chapter_number: "01"
category: Základy
deck: "Domain-Driven Design (DDD), jeho základní principy a způsob, jakým pomáhá řešit složité domény a zlepšuje komunikaci mezi vývojáři a doménovými experty."
reading_time: 12
difficulty: 1
github_examples: Chapter01_WhatIsDDD
---

## 01.01 Definice DDD {#definition}

Softwarové projekty selhávají překvapivě často nikoli kvůli technickým nedostatkům, ale proto, že vývojáři
nedostatečně rozumí problémové oblasti, kterou jejich aplikace řeší. Na tento problém reaguje
Domain-Driven Design (DDD) – přístup k vývoji softwaru, který staví modelování domény do středu
celého návrhu. Poprvé jej systematicky popsal Eric Evans v knize *Domain-Driven Design: Tackling
Complexity in the Heart of Software* v roce 2003 [[1]](https://www.domainlanguage.com/ddd/).

:::callout{type="note"}
### Základní aspekty DDD: {#key-aspects-heading}

- **Doména (Domain)** – Oblast znalostí, problémů a aktivit, na kterou se aplikace zaměřuje [[2]](https://martinfowler.com/bliki/DomainDrivenDesign.html).
- **Ubiquitous Language** – Společný jazyk používaný vývojáři a doménovými experty [[3]](https://martinfowler.com/bliki/UbiquitousLanguage.html). Eliminuje nedorozumění tím, že stejné pojmy se používají v kódu, dokumentaci i v komunikaci s doménovými experty.
- **Bounded Context** – Jasně definovaná hranice, ve které je model platný [[4]](https://martinfowler.com/bliki/BoundedContext.html). Bounded Context pomáhá rozdělit složité domény do menších, lépe zvládnutelných částí.
- **Model-Driven Design** – Návrh softwaru založený na modelu domény [[5]](https://www.infoq.com/articles/ddd-in-practice/). Model je zjednodušenou reprezentací domény, která zachycuje její podstatné aspekty.
:::

## 01.02 Historie a vývoj DDD {#history}

Hlavní milníky ve vývoji DDD [[6]](https://dddcommunity.org/):

- **2003** – Eric Evans vydává knihu *Domain-Driven Design: Tackling Complexity in the Heart of Software*, která zavádí základní pojmy: Ubiquitous Language, Bounded Context, Aggregate a strategický/taktický design.
- **2013** – Vaughn Vernon vydává *Implementing Domain-Driven Design*, která přináší praktické příklady a propaguje vzory jako Aggregate design, Domain Events a CQRS v kontextu DDD.
- **2013** – Alberto Brandolini představuje *Event Storming* – workshopovou techniku pro kolaborativní modelování domény s doménovými experty.
- **2016** – Vaughn Vernon vydává *Domain-Driven Design Distilled*, zkrácenou a přístupnější verzi pro rychlé pochopení hlavních konceptů.
- **Po roce 2015** – DDD si nachází přirozené uplatnění v architektuře mikroslužeb: Bounded Context se stává standardním vodítkem pro určení hranic jednotlivých služeb [[7]](https://docs.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/).

## 01.03 Strategický design (Strategic Design) {#strategic-design}

:::diagram{fig="01.3-A" title="Strategický vs. taktický design - dvě úrovně rozhodování v DDD" src="images/diagrams/1_layers/strategic_vs_tactical.svg"}
:::

Strategický design se zabývá širším kontextem systému a definuje, jak různé části systému spolu interagují. Hlavní koncepty strategického designu zahrnují:

- **Bounded Context** – Ohraničený kontext je explicitně vymezená oblast, uvnitř které platí jeden doménový model. Každý bounded context má svůj vlastní Ubiquitous Language a model.
- **Context Map** – Mapa kontextů zobrazuje vztahy mezi různými bounded contexts. Tyto vztahy mohou být různého typu, například Partnership, Customer-Supplier, Conformist nebo Anti-Corruption Layer.
- **Shared Kernel** – Sdílené jádro je část modelu, která je sdílena mezi dvěma nebo více bounded contexts. Toto sdílení vyžaduje úzkou spolupráci mezi týmy.
- **Customer-Supplier** – Vztah zákazník-dodavatel mezi dvěma bounded contexts, kde jeden kontext (dodavatel) poskytuje služby druhému kontextu (zákazník).
- **Conformist** – Vztah, kde jeden kontext přijímá model jiného kontextu bez možnosti jej ovlivnit.
- **Anti-Corruption Layer** – Vrstva, která překládá mezi dvěma bounded contexts s různými modely, aby chránila integritu cílového modelu.
- **Open Host Service** – Služba, která definuje protokol pro přístup k bounded contextu, aby usnadnila integraci s mnoha jinými kontexty.
- **Published Language** – Dobře dokumentovaný jazyk, který usnadňuje komunikaci mezi různými bounded contexts.

## 01.04 Taktický design (Tactical Design) {#tactical-design}

Taktický design se zabývá implementací doménového modelu v jednom bounded contextu. Hlavní vzory taktického designu:

- **Entity** – Objekty, které mají identitu a kontinuitu v čase. Entity jsou definovány svou identitou, nikoli svými atributy. Například zákazník v e-shopu je entita, protože má unikátní identifikátor (CustomerId), i když se jeho ostatní atributy (jméno, e-mail, adresa) v průběhu času mění.
- **Value Object** – Hodnotové objekty jsou definovány svými atributy, nikoli identitou. Jsou neměnné (immutable) a používají se k popisu aspektů domény. Typickým příkladem hodnotového objektu je adresa nebo peněžní částka.
- **Aggregate** – Agregát je skupina objektů, která tvoří jednu jednotku konzistence při zápisu dat. Každý agregát má kořen agregátu (Aggregate Root), který je jediným vstupním bodem pro veškeré vnější interakce s agregátem.
- **Domain Event** – Doménová událost reprezentuje něco, co se stalo v doméně a má význam pro doménové experty. Doménové události slouží mimo jiné ke komunikaci mezi různými bounded contexts.
- **Service** – Doménová služba implementuje doménovou logiku, která nepatří přirozeně do žádné entity nebo hodnotového objektu. Služby jsou bezstavové a jejich názvy by měly být odvozeny z Ubiquitous Language.
- **Repository** – Repozitář zapouzdřuje logiku pro přístup k persistenci agregátů. Poskytuje abstrakci nad datovým úložištěm a umožňuje pracovat s agregáty jako s objekty v paměti.
- **Factory** – Továrna zapouzdřuje logiku pro vytváření složitých objektů a agregátů. Používá se, když je vytvoření objektu složité nebo když je potřeba zajistit konzistenci nově vytvořených objektů.

:::callout{type="pattern"}
### Příklad: Agregát v e-commerce doméně {#aggregate-example-heading}

V e-commerce doméně by objednávka (Order) mohla být agregátem s následujícími částmi:

- **Order** – Kořen agregátu (Aggregate Root)
- **OrderLine** – Entity reprezentující položky objednávky
- **Money** – Hodnotový objekt reprezentující peněžní částku
- **Address** – Hodnotový objekt reprezentující dodací adresu

Přístup k OrderLine entitám je možný pouze přes Order entitu, což zajistí konzistenci celého agregátu.
:::

:::diagram{fig="01.5-A" title="Agregát v e-commerce doméně" src="images/diagrams/2_basic_concepts/diagram.svg"}
:::

## 01.05 Implementace DDD v praxi {#implementation}

Implementace Domain-Driven Design v praxi zahrnuje tyto kroky:

1. **Pochopení domény** – Prvním krokem je důkladné pochopení domény ve spolupráci s doménovými experty. Tato fáze zahrnuje rozhovory, workshopy a modelování.
2. **Vytvoření Ubiquitous Language** – Definování společného jazyka vývojářů a doménových expertů. Dokumentujte ho a průběžně aktualizujte.
3. **Identifikace Bounded Contexts** – Rozdělení složité domény do menších, jasně definovaných kontextů s explicitními hranicemi.
4. **Vytvoření Context Map** – Definování vztahů mezi různými bounded contexts a způsobu jejich integrace.
5. **Modelování domény** – Vytvoření doménového modelu pro každý bounded context, který zachycuje podstatné koncepty a vztahy v doméně.
6. **Implementace taktických vzorů** – Použití taktických vzorů DDD (Entity, Value Object, Aggregate, Repository a další) pro implementaci doménového modelu v kódu.
7. **Testování** – Ověření, zda model věrně zachycuje doménové chování.
8. **Iterace a vylepšování** – Neustálé vylepšování modelu na základě zpětné vazby od doménových expertů a zkušeností z implementace.

## 01.06 Výhody používání DDD {#benefits}

Domain-Driven Design přináší mnoho výhod pro vývoj softwaru:

- **Lepší komunikace** – Ubiquitous Language odstraňuje nedorozumění mezi vývojáři a doménovými experty, protože všichni používají stejné pojmy v kódu i v konverzaci.
- **Flexibilita a odolnost vůči změnám** – Model orientovaný na doménu je stabilnější než model orientovaný na technická řešení; změny v obchodních požadavcích se přirozeněji promítají do kódu.
- **Modularita** – Bounded Contexts umožňují nezávislý vývoj, nasazení a škálování jednotlivých částí systému.
- **Testovatelnost** – Doménové objekty bez infrastrukturních závislostí lze testovat v izolaci bez mockování (viz [kapitola o testování](/testovani-ddd)).
- **Snížení technického dluhu** – Explicitní doménový model slouží jako živá dokumentace systému, která zůstává aktuální s kódem.
- **Zaměření na hodnotu** – DDD rozlišuje Core Domain (zdroj konkurenční výhody) od podpůrných domén, což pomáhá soustředit investice tam, kde přinášejí největší obchodní hodnotu.

Praktické příklady Ubiquitous Language a dalších konceptů naleznete v kapitole [Základní koncepty DDD](/zakladni-koncepty).

## 01.07 Výzvy a omezení DDD {#challenges}

I když DDD přináší mnoho výhod, má také svá omezení, která je třeba znát před rozhodnutím zavést DDD:

- **Složitost** – DDD může být složité pochopit a implementovat, zejména pro začátečníky. Vyžaduje hluboké pochopení domény a architektonických principů.
- **Časová náročnost** – Implementace DDD může být časově náročná, zejména v počátečních fázích projektu. Modelování domény a vytváření Ubiquitous Language vyžaduje čas a úsilí.
- **Nevhodnost pro jednoduché aplikace** – DDD je navržen pro složité aplikace s bohatou doménou. Pro jednoduché aplikace s minimální doménovou logikou může být zbytečně složitý a nákladný.
- **Potřeba doménových expertů** – DDD vyžaduje přístup k doménovým expertům, což nemusí být vždy možné. Bez doménových expertů je obtížné vytvořit přesný model domény.
- **Organizace týmu** – DDD může vyžadovat změny v organizaci týmu, aby podporovala spolupráci mezi vývojáři a doménovými experty. To může být v některých organizacích obtížné.
- **Integrace s legacy systémy** – Napojení DDD na existující legacy systémy bývá náročné a často vyžaduje vytvoření Anti-Corruption Layer.
- **Výkonnost** – Některé vzory DDD, jako jsou agregáty a repozitáře, mohou mít dopad na výkonnost, pokud nejsou správně implementovány.
- **Učební křivka** – DDD má strmou učební křivku a může trvat nějakou dobu, než tým získá potřebné znalosti a zkušenosti.

## 01.08 DDD vs. jiné přístupy {#ddd-vs-other}

Domain-Driven Design lze porovnat s jinými přístupy k vývoji softwaru:

- **DDD vs. Transaction Script** – Transaction Script (Martin Fowler, *PoEAA*) organizuje logiku kolem případů užití: každý use case je jedna procedura, která čte data, aplikuje pravidla a ukládá výsledek. **Rozdíl:** Transaction Script nemá doménový model – logika je v procedurách, ne v objektech. Pro jednoduché domény je to přímočařejší; s rostoucí složitostí však dochází k duplicitě pravidel a těžko udržovatelnému kódu. DDD je vhodnější, jakmile doménová pravidla začnou být sdílena napříč více use cases.
- **DDD vs. CRUD** – CRUD (Create, Read, Update, Delete) je datově orientovaný přístup: aplikace je v podstatě editor databázových tabulek. **Rozdíl:** CRUD nerozlišuje mezi doménovým chováním a datovými operacemi – každá akce je variací na čtení/zápis řádku. DDD naproti tomu modeluje chování domény (objednávku nelze jen „updatovat“, ale „potvrdit“, „zrušit“ nebo „odeslat“). Pro jednoduchou správu dat CRUD postačí.
- **DDD vs. Hexagonální architektura** – Hexagonální architektura (Ports and Adapters, Alistair Cockburn) řeší *jak strukturovat závislosti*: doménové jádro komunikuje s vnějším světem přes porty (rozhraní) a adaptéry (implementace). **Rozdíl:** DDD řeší *jak modelovat doménu* (Entity, Value Objects, Aggregates), hexagonální architektura řeší *jak oddělit doménu od infrastruktury*. Tyto přístupy jsou komplementární: DDD poskytuje vzory pro doménové jádro, hexagonální architektura poskytuje strukturu pro jeho izolaci.
- **DDD vs. Mikroservisy** – Mikroservisy jsou architektonický styl zaměřený na *jak nasazovat a škálovat* části systému nezávisle. **Rozdíl:** DDD řeší logické hranice domény (Bounded Contexts), mikroservisy řeší fyzické hranice nasazení. Bounded Context z DDD je přirozeným kandidátem pro hranici mikroservisy, ale neplatí to automaticky – jeden Bounded Context může být implementován jako více mikroservis a naopak. DDD lze nasadit i v monolitické architektuře.

:::callout{type="warn"}
### Kdy nepoužívat DDD {#when-not-to-use-heading}

DDD nemusí být vhodný pro všechny projekty. Nepoužívejte DDD, pokud:

- Vyvíjíte jednoduchou aplikaci s minimální doménovou logikou.
- Nemáte přístup k doménovým expertům.
- Váš tým nemá zkušenosti s DDD a nemá čas se ho naučit.
- Máte velmi omezený čas a rozpočet.
:::

## 01.09 Shrnutí {#summary}

Domain-Driven Design je ucelený přístup k vývoji softwaru, který se zaměřuje na modelování domény a její implementaci v kódu. Stěžejní koncepty DDD zahrnují:

- **Strategický design** – Bounded Contexts, Context Map, Ubiquitous Language
- **Taktický design** – Entity, Value Objects, Aggregates, Repositories, Domain Events, Services, Factories
- **Implementační vzory** – Anti-Corruption Layer, [Specification](/glosar#term-specifikace), [Saga / Process Manager](/glosar#term-saga)

DDD se osvědčuje v aplikacích s bohatou doménou, kde přesné modelování obchodní logiky přináší měřitelnou hodnotu. Má reálné náklady – naučení se vzorů, vyšší počáteční složitost, nutnost spolupráce s doménovými experty – a proto vyžaduje vědomé rozhodnutí.

:::faq{}
- question: Co je Domain-Driven Design?
  answer: 'Domain-Driven Design (DDD) je přístup k vývoji softwaru, který staví modelování domény do středu celého návrhu. Systematicky jej popsal Eric Evans v knize z roku 2003. Cílem je, aby software co nejpřesněji odrážel způsob, jakým v dané oblasti uvažují doménoví experti, a aby tento soulad vydržel i při růstu aplikace. Podrobnosti v <a href="#definition">sekci Definice DDD</a>.'
- question: Co je Ubiquitous Language v DDD?
  answer: 'Ubiquitous Language (všudypřítomný jazyk) je společný slovník používaný vývojáři i doménovými experty při návrhu, diskuzi i implementaci systému. Stejné pojmy se objevují v doménové dokumentaci, v rozhovorech nad modelem i přímo v kódu. Tím se eliminují nedorozumění a snižuje se riziko, že kód bude modelovat něco jiného, než doména skutečně potřebuje. Více v <a href="#strategic-design">sekci o strategickém designu</a>.'
- question: Co je Bounded Context a k čemu slouží?
  answer: 'Bounded Context (ohraničený kontext) je explicitně definovaná hranice, uvnitř které platí jeden konzistentní doménový model a jeden Ubiquitous Language. Mimo tuto hranici mohou stejné pojmy znamenat něco jiného – například „Customer“ ve fakturaci a „Customer“ v podpoře jsou různé modely s různými atributy. Bounded Contexts pomáhají rozdělit složitou doménu na menší zvládnutelné části a bývají přirozenými hranicemi pro mikroservisy. Viz <a href="#strategic-design">strategický design</a>.'
- question: Kdy se DDD nevyplatí použít?
  answer: 'Stručně: DDD nepřináší odpovídající hodnotu u projektů s triviální doménovou logikou, v týmech bez přístupu k doménovým expertům a při krátkém horizontu produktu. Detailní rozbor podmínek, příznaků a alternativ obsahuje samostatná kapitola <a href="/kdy-nepouzivat-ddd">Kdy DDD nepoužívat</a>.'
:::

## 01.10 Další četba {#further-reading}

Pro další studium Domain-Driven Design jsou vhodné následující zdroje:

- [Domain Language – oficiální stránky Erica Evanse a DDD komunity](https://www.domainlanguage.com/ddd/)
- [Domain-Driven Design: Tackling Complexity in the Heart of Software – Eric Evans](https://www.amazon.com/Domain-Driven-Design-Tackling-Complexity-Software/dp/0321125215)
- [Implementing Domain-Driven Design – Vaughn Vernon](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)
- [Domain-Driven Design Distilled – Vaughn Vernon](https://www.amazon.com/Domain-Driven-Design-Distilled-Vaughn-Vernon/dp/0134434420)
- [DDD Community](https://dddcommunity.org/)
