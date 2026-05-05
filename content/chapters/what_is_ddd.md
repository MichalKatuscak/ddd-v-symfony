---
route: what_is_ddd
path: /co-je-ddd
title: Co je Domain-Driven Design?
page_title: "Co je Domain-Driven Design? Vysvětlení DDD | DDD Symfony"
meta_description: "Domain-Driven Design srozumitelně: filozofie Erica Evanse, Ubiquitous Language, Bounded Context a rozdíl mezi strategickým a taktickým designem."
meta_keywords: "Domain-Driven Design, DDD, Eric Evans, Ubiquitous Language, Bounded Context, doménový model, doménová logika, strategický design, taktický design"
og_type: article
published: "2025-04-24"
modified: "2026-05-04"
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

Než se ponoříme do definic, podíváme se na konkrétní situaci, ve které DDD pomáhá. Modelový e-shop, který tým rozjel před třemi lety, měl tehdy tři stavy objednávky (`new`, `paid`, `shipped`), jeden typ zákazníka a jednu platební metodu. Doménový model byl triviální. Doctrine entita `Order` měla šest sloupců, `OrderService` dvě stě řádků, kontroler tři metody. Tým měl tři lidi a každou novou funkci dodal za týden.

Po třech letech provozu vypadá doména jinak. Stavů objednávky je dvanáct: `new`, `awaiting_payment`, `paid`, `partially_paid`, `held_for_review`, `confirmed`, `shipped`, `delivered`, `cancelled`, `refunded`, `disputed`, `returned`. Typů zákazníka jsou čtyři: B2C, B2B s fakturací, dealer s rabatem, partner s vlastním ceníkem. Platebních metod pět: karta přes Stripe, Apple Pay, bankovní převod, dobírka, faktura splatná do 30 dnů. Každý typ zákazníka má jiná pravidla pro slevy, jiné zacházení s DPH a jiný proces refundace.

Tým má teď pět lidí, kód má 80 000 řádků a přidání nové platební metody (Bitcoin přes BitPay) trvá tři týdny. Ne proto, že integrace s BitPay je složitá – ta je hotová za den. Ale protože každá změna v `OrderService` rozbije něco jiného. Když přidáte větev pro Bitcoin v metodě `processPayment`, rozbije se refund logika v `cancelOrder`. Když opravíte refund, rozbije se reporting v `MonthlyRevenueService`. Po třech týdnech ladění a regresních testů je BitPay v produkci, ale tým má dvouměsíční technický dluh v backlogu.

Senior vývojář si všiml, že kód odráží něco jiného než to, co produktový manažer popisuje. PM mluví o „závazné objednávce po kliknutí na platbu“ a o „rezervaci, která propadne za 24 hodin“. V kódu je `Order::status = 'awaiting_payment'` a TTL kontrola se schovává v týdenním cronu, do kterého nikdo nekouká. Když tester nahlásí bug v rezervační logice, je třeba přečíst `OrderService::checkExpiration`, `WeeklyCleanupCommand`, `OrderEventSubscriber` a `OrderRepository::findExpiredAwaitingPayment`, abychom našli celé chování. Doménová pravidla žijí roztroušená napříč pěti soubory bez společného slovníku.

Onboarding nového kolegy trvá dva měsíce, než začne dělat smysluplné PR. Ne proto, že by Symfony bylo komplikované – Symfony zná po týdnu. Ale doménová pravidla jsou v hlavách dvou seniorů a v kódu jsou jen jejich důsledky. Junior se ptá: „proč při refundaci nezapočítáváme dopravu, ale při dispute ano?“ Odpověď zní: „protože kdysi to chtěl účetní“. Není to nikde dokumentované.

Ředitel se ptá CTO: proč nedokážeme přidat novou platební metodu rychleji než za tři týdny? Konkurence to umí za týden. CTO ví, že problém není v nástrojích – problém je v tom, jak je modelovaná doména. Kód neodráží reálné rozhodování byznysu. Každá feature musí znovu dohledávat, co kde sedí, jaké pravidlo platí v jakém stavu, kdo má autoritu rozhodnout, že refund jde, a kdy ne.

Tomuto stavu DDD říká „komplexita domény přerostla model“. A nabízí konkrétní odpověď: místo `OrderService::cancelOrder($order, $reason)` mít doménový model `Order` s explicitními metodami `confirm()`, `cancel()`, `dispute()`, `refund()`. Místo textového statusu mít stavový automat s explicitními přechody. Místo čtyř typů zákazníka v jednom modelu mít čtyři Bounded Contexts, kde každý má svého `Customer` s vlastními atributy a vlastními pravidly. Místo měsíců regresí mít hranice agregátů, které drží refaktoring v rozumných mezích.

Hlavní přínos DDD: kód odráží jazyk, kterým mluví doménoví experti. Když produktový manažer řekne „tohle není reklamace, je to dispute s odlišným procesem“ – kód to umí říct stejně. Když účetní rozhoduje, jestli refund započítává dopravu, doménová třída `Refund` má metodu `excludeShipping()` nebo `includeShipping()`, která to říká. Když tester píše scénář, používá stejný slovník jako PM. Slovník je jeden, žije v hlavě týmu i v kódu, a když se mění, mění se na obou místech najednou.

DDD má svou cenu. Vyžaduje vyšší počáteční složitost, učební křivku týmu a opakovanou spolupráci s doménovými experty. Pro CRUD aplikaci nad jednou tabulkou se nevyplatí – tam je `OrderService` se setterem správná volba a investice do agregátu by byla zbytečně přebujelá. Pro komplexní doménu s rostoucí pravidlovou složitostí, kterou tým udržuje delší dobu než rok, se DDD vrací v horizontu šesti až dvanácti měsíců.

V této knize se naučíte, jak rozhodnout, jestli DDD ve vašem projektu dává smysl (kapitola [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) je o tom, kdy odpověď zní „ne“). Jak modelovat doménu, identifikovat agregáty, oddělit zápis od čtení. Jak to konkrétně implementovat v Symfony 8 – bez teoretických odboček, s funkčním kódem, který lze převzít.

A teď k definicím.

## 01.01 Definice DDD {#definition}

Softwarové projekty selhávají častěji kvůli neporozumění problémové oblasti než kvůli technickým chybám.
Domain-Driven Design (DDD) na to odpovídá tím, že modelování domény staví do středu celého návrhu.
Systematicky jej popsal Eric Evans v knize *Domain-Driven Design: Tackling
Complexity in the Heart of Software* z roku 2003 [[1]](https://www.domainlanguage.com/ddd/).

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

Strategický design rozhoduje, jak rozdělit systém na samostatné části a jak tyto části spolu komunikují. Hlavní koncepty:

- **Bounded Context** – Ohraničený kontext je explicitně vymezená oblast, uvnitř které platí jeden doménový model. Každý bounded context má svůj vlastní Ubiquitous Language a model.
- **Context Map** – Mapa kontextů zobrazuje vztahy mezi různými bounded contexts. Tyto vztahy mohou být různého typu, například Partnership, Customer-Supplier, Conformist nebo Anti-Corruption Layer.
- **Shared Kernel** – Sdílené jádro je část modelu, která je sdílena mezi dvěma nebo více bounded contexts. Toto sdílení vyžaduje úzkou spolupráci mezi týmy.
- **Customer-Supplier** – Vztah zákazník-dodavatel mezi dvěma bounded contexts, kde jeden kontext (dodavatel) poskytuje služby druhému kontextu (zákazník).
- **Conformist** – Vztah, kde jeden kontext přijímá model jiného kontextu bez možnosti jej ovlivnit.
- **Anti-Corruption Layer** – Vrstva, která překládá mezi dvěma bounded contexts s různými modely, aby chránila integritu cílového modelu.
- **Open Host Service** – Služba, která definuje protokol pro přístup k bounded contextu, aby usnadnila integraci s mnoha jinými kontexty.
- **Published Language** – Dobře dokumentovaný jazyk, který usnadňuje komunikaci mezi různými bounded contexts.

## 01.04 Taktický design (Tactical Design) {#tactical-design}

Taktický design řeší konkrétní implementaci doménového modelu uvnitř jednoho bounded contextu. Hlavní vzory:

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

Typický postup zavedení DDD má osm kroků. První čtyři patří strategickému designu (kontexty, jazyk), zbytek taktickému designu a iteraci modelu.

1. **Pochopení domény** – Začíná rozhovory s experty, workshopy, modelováním na tabuli. Bez této fáze model padá hned na začátku.
2. **Ubiquitous Language** – Společný slovník vývojářů a doménových expertů, zapsaný a průběžně aktualizovaný. Stejné pojmy v kódu, dokumentaci i mailu od PM.
3. **Identifikace Bounded Contexts** – Doména se rozděluje na menší kontexty s explicitními hranicemi. Každý kontext má vlastní model.
4. **Context Map** – Vztahy mezi kontexty (Customer-Supplier, Conformist, Anti-Corruption Layer) jsou popsané a mají odpovědné týmy.
5. **Doménový model** – Entity, Value Objects, agregáty, doménové služby a události jsou navrženy a implementovány v každém kontextu samostatně.
6. **Implementace** – Vrstvená nebo hexagonální architektura odděluje doménový model od infrastrukturní vrstvy.
7. **Testování** – Doménový model má pokrytí unit testy, hraniční scénáře integrační testy.
8. **Iterace** – Model se průběžně upravuje, jak roste pochopení domény. DDD není jednorázová investice.

## 01.06 Výhody používání DDD {#benefits}

Co konkrétně tým získá, když DDD nasadí správně:

- **Lepší komunikace** – Ubiquitous Language odstraňuje nedorozumění mezi vývojáři a doménovými experty. Všichni používají stejné pojmy v kódu i v konverzaci.
- **Odolnost vůči změnám** – Model orientovaný na doménu je stabilnější než model orientovaný na databázové schéma. Změny v obchodních požadavcích se přirozeněji promítají do kódu.
- **Modularita** – Bounded Contexts umožňují nezávislý vývoj, nasazení a škálování jednotlivých částí systému.
- **Testovatelnost** – Doménové objekty bez infrastrukturních závislostí lze testovat v izolaci bez mockování (viz [kapitola o testování](/testovani-ddd)).
- **Snížení technického dluhu** – Explicitní doménový model slouží jako živá dokumentace systému, která zůstává aktuální s kódem.
- **Zaměření na hodnotu** – DDD rozlišuje Core Domain (zdroj konkurenční výhody) od podpůrných domén. Investice se pak soustředí tam, kde přinášejí největší obchodní hodnotu.

:::callout{type="pattern"}
### Konkrétní přínos: přidání nové platební metody {#priklad-platba-heading}

V úvodním příběhu jsme popsali e-shop, kde přidání BitPay trvalo tři týdny. V CRUD architektuře každá nová platební metoda znamená:

1. Přidat větev v `OrderService::processPayment` (a doufat, že netrhne refund logiku).
2. Upravit `OrderService::cancelOrder` (refund pro novou metodu).
3. Doplnit reporting v `MonthlyRevenueService` (statistiky podle metody).
4. Otestovat regrese v `WeeklyCleanupCommand` (TTL rezervací).
5. Smířit se s tím, že některý z těchto kroků pravděpodobně něco rozbije.

V DDD architektuře s explicitním agregátem `Payment` a doménovým eventem `PaymentMethodAdded` přidání nové metody znamená:

1. Implementovat adapter `BitPayGateway` v Anti-Corruption Layer (jednorázová práce).
2. Zaregistrovat novou metodu v `PaymentMethodRegistry`.
3. Existující agregáty `Order`, `Refund` a `Payment` zachovají chování beze změny – pravidla refundace, reportingu a TTL nesahají do CRUD service vrstvy.

Rozdíl: tři týdny vs. tři dny. Důvod: hranice agregátů drží refaktor v omezeném prostoru a doménová pravidla jsou na jednom místě, ne rozteklá napříč pěti soubory.
:::

Praktické příklady Ubiquitous Language a dalších konceptů naleznete v kapitole [Základní koncepty DDD](/zakladni-koncepty).

## 01.07 Výzvy a omezení DDD {#challenges}

DDD má reálné náklady. Před rozhodnutím o nasazení s nimi počítejte:

- **Složitost** – DDD vyžaduje hluboké pochopení domény i architektonických principů. Pro vývojáře bez zkušenosti s objektovým modelováním je to skok.
- **Časová náročnost** – V počátku projektu se modelování domény a budování Ubiquitous Language nevrací rychle. Investice se vrátí až s rostoucí složitostí pravidel.
- **Nevhodnost pro jednoduché aplikace** – U CRUD aplikací s minimální doménovou logikou DDD přidává režii bez návratnosti.
- **Potřeba doménových expertů** – Bez přístupu k expertovi nemá kdo říct, jaká pravidla skutečně platí.
- **Organizace týmu** – Spolupráce vývojářů a doménových expertů znamená pravidelné workshopy a sdílený jazyk. Některé organizace na to nejsou nastavené.
- **Integrace s legacy systémy** – Napojení DDD modelu na starý systém typicky vyžaduje Anti-Corruption Layer, který má vlastní cenu.
- **Výkonnost** – Agregáty a repozitáře mají při špatné implementaci dopad na výkon (problém N+1, načítání zbytečně velkých grafů).
- **Učební křivka** – Tým potřebuje měsíce, než získá rutinu. První projekt v DDD bývá pomalejší než stejný projekt v CRUD.

:::callout{type="warn"}
### Skutečný případ selhání: DDD bez doménového experta {#priklad-selhani-heading}

Český B2B startup zavedl DDD na projektu pro správu skladových rezervací. Tým měl pět seniorních PHP vývojářů, znal Vernona i Khononova, modeloval agregáty s invariantami a používal CQRS přes Symfony Messenger. Doménový expert v týmu chyběl – produktový manažer pracoval externě a měl na projekt deset hodin měsíčně.

Po šesti měsících měl tým 40 agregátů, 80 doménových událostí a 200 commandů. Kód vypadal jako z učebnice. Ale skutečná pravidla skladu (kdy zboží může být rezervováno na dvou místech současně, jak se rozhoduje o přesunu mezi sklady, jaký je vztah mezi rezervací a skutečným fyzickým výdejem) v modelu nikdy nebyla. Tým modeloval to, co si představoval, ne to, co skutečně platilo.

Když logistický ředitel po dvou měsících provozu zjistil, že systém umožňuje dvojí rezervaci (a tím způsobuje časté reklamace), vyžadoval okamžitou opravu. Refaktor 40 agregátů a 80 událostí trval čtyři měsíce. Po čtrnácti měsících vývoje byl projekt na technologii za 30 % funkcionality, kterou původní CRUD aplikace zvládala.

Lekce: **DDD bez doménového experta v týmu nefunguje.** Pravidla, která doménový expert nezná, nemůže nikdo modelovat. Žádný senior vývojář nedokáže odvodit, jak skutečně funguje sklad, jen z business analystových wireframů. Pokud nemáte přístup k expertovi, kapitola [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) doporučuje začít s jednodušší architekturou a investici do doménového modelování odložit.
:::

## 01.08 DDD vs. jiné přístupy {#ddd-vs-other}

DDD se v praxi nejčastěji srovnává se čtyřmi jinými přístupy. Žádný z nich není přímý konkurent. Některé řeší jinou vrstvu problému, pro jednodušší domény stačí jejich vlastní nástroje:

- **DDD vs. Transaction Script** – Transaction Script (Martin Fowler, *PoEAA*) organizuje logiku kolem případů užití: každý use case je jedna procedura, která čte data, aplikuje pravidla a ukládá výsledek. **Rozdíl:** Transaction Script nemá doménový model – logika je v procedurách, ne v objektech. Pro jednoduché domény je to přímočařejší; s rostoucí složitostí však dochází k duplicitě pravidel a těžko udržovatelnému kódu. DDD je vhodnější, jakmile doménová pravidla začnou být sdílena napříč více use cases.
- **DDD vs. CRUD** – CRUD (Create, Read, Update, Delete) je datově orientovaný přístup: aplikace je v podstatě editor databázových tabulek. **Rozdíl:** CRUD nerozlišuje mezi doménovým chováním a datovými operacemi – každá akce je variací na čtení/zápis řádku. DDD naproti tomu modeluje chování domény (objednávku nelze jen „updatovat“, ale „potvrdit“, „zrušit“ nebo „odeslat“). Pro jednoduchou správu dat CRUD postačí.
- **DDD vs. Hexagonální architektura** – Hexagonální architektura (Ports and Adapters, Alistair Cockburn) řeší *jak strukturovat závislosti*: doménové jádro komunikuje s vnějším světem přes porty (rozhraní) a adaptéry (implementace). **Rozdíl:** DDD řeší *jak modelovat doménu* (Entity, Value Objects, Aggregates), hexagonální architektura řeší *jak oddělit doménu od infrastruktury*. Doplňují se: DDD nabízí vzory pro doménové jádro, hexagonální architektura ho izoluje od infrastruktury.
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

DDD strukturuje práci do tří vrstev. Každá má jiné odpovědnosti:

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

Hlavní zdroje:

- [Domain Language – oficiální stránky Erica Evanse a DDD komunity](https://www.domainlanguage.com/ddd/)
- [Domain-Driven Design: Tackling Complexity in the Heart of Software – Eric Evans](https://www.amazon.com/Domain-Driven-Design-Tackling-Complexity-Software/dp/0321125215)
- [Implementing Domain-Driven Design – Vaughn Vernon](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)
- [Domain-Driven Design Distilled – Vaughn Vernon](https://www.amazon.com/Domain-Driven-Design-Distilled-Vaughn-Vernon/dp/0134434420)
- [DDD Community](https://dddcommunity.org/)

## 01.11 Jak číst tuto knihu {#jak-cist}

Tato kapitola je první v sekvenci 24 kapitol. Pořadí kapitol je promyšlené – každá staví na předchozích – ale málokdo potřebuje lineární čtení od první do poslední. Většina čtenářů má konkrétní bolest a kniha je připravená na selektivní čtení.

Pro detailní cesty čtení podle role (junior PHP developer, senior, architekt, tech lead, vývojář migrující z CRUD) projděte [Předmluvu, sekci 'Jak číst tuto knihu'](/predmluva#jak-cist). Stručný přehled částí knihy:

- **Strategický design** (kap. 2–5) odpovídá na otázku *kde* DDD aplikovat. Subdomény, Bounded Contexts, Event Storming, Team Topologies. Pokud z této kapitoly odejdete s pocitem, že DDD ve vašem projektu nedává smysl, sekce 2–5 vám potvrdí proč. Pokud má smysl, dají vám nástroj, jak začít.
- **Taktický design** (kap. 6–9) pokrývá konkrétní stavební bloky: entity, hodnotové objekty, agregáty, doplňující vzory, architektonické styly. Nejdůležitější je [návrh agregátu](/navrh-agregatu) – nejtěžší rozhodnutí v taktickém DDD.
- **Implementace v Symfony** (kap. 10–11) překládá teorii do konkrétního Symfony 8 kódu s Doctrine ORM, Messenger a aktuálními PHP rysy. Plus autorizace ve čtyřech vrstvách.
- **Pokročilé vzory** (kap. 12–15) obsahují CQRS, Event Sourcing, Ságy a Outbox Pattern. Tyto vzory nejsou pro každý projekt – kapitoly začínají rozhodovacím rámcem, kdy ano a kdy ne.
- **Výkon a testování** (kap. 16–17), **migrace a microservices** (kap. 18–19), **provozní problémy a anti-vzory** (kap. 20–22), **praktické příklady** (kap. 23–24) uzavírají knihu.

Pokud váhate, jestli má vůbec smysl pokračovat, doporučuji následující postup. Přečtěte si tuto kapitolu (1) a kapitolu [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd). Pokud po obou kapitolách máte pocit, že DDD ve vašem projektu dává smysl, pokračujte na kapitolu 2 [Subdomény](/subdomeny). Pokud váháte, projděte ještě [Cheat Sheet](/cheat-sheet) – jednostránkový přehled pro rychlou orientaci.

Pro definice termínů slouží [Glosář](/glosar). Pro citace knih a článků v každé kapitole je sekce „Další četba“ (jako tato).
