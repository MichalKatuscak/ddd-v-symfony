---
route: what_is_ddd
path: /co-je-ddd
title: Co je Domain-Driven Design?
page_title: "Co je Domain-Driven Design? Vysvětlení DDD | DDD Symfony"
meta_description: "Domain-Driven Design srozumitelně: filozofie Erica Evanse, Ubiquitous Language, Bounded Context a rozdíl mezi strategickým a taktickým designem."
meta_keywords: "Domain-Driven Design, DDD, Eric Evans, Ubiquitous Language, Bounded Context, doménový model, doménová logika, strategický design, taktický design"
og_type: article
published: "2025-04-24"
modified: "2026-07-08"
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

Senior vývojář si všiml, že kód odráží něco jiného než to, co produktový manažer popisuje. PM mluví o „závazné objednávce po kliknutí na platbu“ a o „rezervaci, která propadne za 24 hodin“. V kódu je `Order::status = 'awaiting_payment'` a TTL kontrola se schovává v týdenním cronu, do kterého nikdo nekouká. Když tester nahlásí bug v rezervační logice, je třeba přečíst `OrderService::checkExpiration`, `WeeklyCleanupCommand`, `OrderEventSubscriber` a `OrderRepository::findExpiredAwaitingPayment`, než je celé chování pohromadě. Doménová pravidla žijí roztroušená napříč pěti soubory bez společného slovníku.

Onboarding nového kolegy trvá dva měsíce, než začne dělat smysluplné PR. Ne proto, že by Symfony bylo komplikované – Symfony zná po týdnu. Ale doménová pravidla jsou v hlavách dvou seniorů a v kódu jsou jen jejich důsledky. Junior se ptá: „proč při refundaci nezapočítáváme dopravu, ale při dispute ano?“ Odpověď zní: „protože kdysi to chtěl účetní“. Není to nikde dokumentované.

Ředitel se ptá CTO: proč nedokážeme přidat novou platební metodu rychleji než za tři týdny? Konkurence to umí za týden. CTO ví, že problém není v nástrojích – problém je v tom, jak je modelovaná doména. Kód neodráží reálné rozhodování byznysu. Každá feature musí znovu dohledávat, co kde sedí, jaké pravidlo platí v jakém stavu, kdo má autoritu rozhodnout, že refund jde, a kdy ne.

Komplexita domény přerostla model – právě tento stav DDD řeší. Nabízí konkrétní odpověď: místo `OrderService::cancelOrder($order, $reason)` mít doménový model `Order` s explicitními metodami `confirm()`, `cancel()`, `dispute()`, `refund()`. Místo textového statusu mít stavový automat s explicitními přechody. Místo čtyř typů zákazníka v jednom modelu mít čtyři Bounded Contexts, kde každý má svého `Customer` s vlastními atributy a vlastními pravidly. Místo měsíců regresí mít hranice agregátů, které drží refaktoring v rozumných mezích.

Hlavní přínos DDD: kód odráží jazyk, kterým mluví doménoví experti. Když produktový manažer řekne „tohle není reklamace, je to dispute s odlišným procesem“ – kód to umí říct stejně. Když účetní rozhoduje, jestli refund započítává dopravu, doménová třída `Refund` má metodu `excludeShipping()` nebo `includeShipping()`, která to říká. Když tester píše scénář, používá stejný slovník jako PM. Slovník je jeden, žije v hlavě týmu i v kódu, a když se mění, mění se na obou místech najednou.

DDD má svou cenu. Vyžaduje vyšší počáteční složitost, učební křivku týmu a opakovanou spolupráci s doménovými experty. Pro CRUD aplikaci nad jednou tabulkou se nevyplatí – tam je `OrderService` se setterem správná volba a investice do agregátu by byla nepřiměřená. Pro komplexní doménu s rostoucí pravidlovou složitostí, kterou tým udržuje léta, se investice vrátí. Potřebuje na to ale čas a spolehlivý odhad, za jak dlouho, neexistuje.

V této knize se naučíte, jak rozhodnout, jestli DDD ve vašem projektu dává smysl (kapitola [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) je o tom, kdy odpověď zní „ne“). Jak modelovat doménu, identifikovat agregáty, oddělit zápis od čtení. Jak to konkrétně implementovat v Symfony 8 – bez teoretických odboček, s funkčním kódem, který lze převzít.

A teď k definicím.

## 01.01 Definice DDD {#definition}

Eric Evans staví DDD na předpokladu, že hlavním rizikem složitého softwaru není technologie, ale neporozumění problémové oblasti.
Odpovědí je posunout modelování domény do středu celého návrhu.
Systematicky to rozpracoval v knize *Domain-Driven Design: Tackling
Complexity in the Heart of Software* z roku 2003 [[1]](https://www.domainlanguage.com/ddd/).

Vlastní stručnou definici DDD ale publikoval až o dvanáct let později, v úvodu volně dostupné *DDD Reference* [[2]](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf). Shrnuje ji do tří bodů, v tomto pořadí:

:::callout{type="note"}
### Evansovo shrnutí DDD ve třech bodech {#key-aspects-heading}

- **Zaměření na Core Domain** – Modelovací úsilí patří tam, kde firma soutěží. Zbytek domény se kupuje, generuje nebo řeší nejkratší cestou. Rozlišení rozvádí [kapitola o subdoménách](/subdomeny#tri-kategorie).
- **Průzkum modelů v tvůrčí spolupráci** – Model nevzniká z analýzy předané zadavatelem. Vzniká společnou prací doménových praktiků a vývojářů, kteří projdou několik konkurenčních variant, než jednu zvolí.
- **Ubiquitous Language uvnitř explicitně ohraničeného kontextu** – Jeden jazyk, jeden model, jedna hranice platnosti. Hranice je součástí definice, ne dodatkem k ní.
:::

Pojmy z těchto tří bodů kniha dál používá v Evansově původním významu. **Doména** je oblast znalostí, vlivu a činností, ke které se software vztahuje. **Model** je systém abstrakcí popisující vybrané aspekty domény. **Ubiquitous Language** je jazyk vystavěný kolem doménového modelu a používaný všemi členy týmu – ovšem uvnitř jednoho bounded contextu, ne napříč celou firmou. **Bounded Context** je popis hranice, typicky subsystému nebo záběru jednoho týmu, uvnitř které je model definován a platí.

Vazba jazyka na hranici bývá první věcí, která se z definice ztratí. Martin Fowler ji formuluje z druhé strany: jakmile se mění jazyk, potřebujete jiný model [[3]](https://martinfowler.com/bliki/BoundedContext.html). Pokus o jeden firemní slovník napříč všemi odděleními proto skončí buď u kompromisních jmen, kterými nemluví nikdo, nebo u zamrzlého glosáře. Konkrétní podobu tohoto konfliktu ukazuje [podsekce o Bounded Contextu](#bounded-context).

## 01.02 Historie a vývoj DDD {#history}

Hlavní milníky ve vývoji DDD [[4]](https://dddcommunity.org/):

- **2003** – Eric Evans vydává knihu *Domain-Driven Design: Tackling Complexity in the Heart of Software*, která zavádí základní pojmy: Ubiquitous Language, Bounded Context, Aggregate a strategický/taktický design.
- **2009** – Na QCon London Evans shrnuje, co by po pěti letech napsal jinak [[5]](https://www.dddcommunity.org/library/evans_2009_1/). Stavební bloky – entity, hodnotové objekty, továrny a repozitáře – označuje za přeceněné. Do centra staví Ubiquitous Language, Context Mapping a Core Domain.
- **2013** – Vaughn Vernon vydává *Implementing Domain-Driven Design*, která přináší praktické příklady a propaguje vzory jako Aggregate design, Domain Events a CQRS v kontextu DDD.
- **2013** – Alberto Brandolini představuje *Event Storming* – workshopovou techniku pro kolaborativní modelování domény s doménovými experty.
- **2015** – Evans vydává *Domain-Driven Design Reference* pod licencí CC-BY: shrnutí všech vzorů zdarma. Bounded Context v něm stojí na prvním místě vzorového jazyka, zatímco v knize z roku 2003 přišel na řadu až ve čtrnácté kapitole.
- **2016** – Vernon vydává *Domain-Driven Design Distilled*. Pořadí výkladu je obrácené: nejdřív bounded contexts, subdomény a Context Mapping, teprve pak agregáty a doménové události. Ve stejném roce se v Bruselu koná první ročník konference DDD Europe.
- **Po roce 2015** – Mikroservisová vlna dělá z Bounded Contextu nejcitovanější vodítko pro určení hranic služeb [[6]](https://learn.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/). Rovnici „mikroslužba = bounded context“ ale sám Evans v roce 2019 označil za zjednodušení a rozlišil několik odlišných situací [[7]](https://www.infoq.com/news/2019/06/bounded-context-eric-evans).
- **2021** – Vlad Khononov vydává *Learning Domain-Driven Design* s podtitulem *Aligning Software Architecture and Business Strategy*. Těžiště se posouvá od kódu k obchodní strategii.

## 01.03 Ubiquitous Language v praxi {#ubiquitous-language-v-praxi}

Ubiquitous Language nevzniká sepsáním dokumentu. Vzniká konverzací – v plánovací schůzce, při Event Stormingu, v diskuzi nad bugem, kde doménový expert opraví vývojáře: „to není storno, to je propadnutí rezervace“. Dokument je až záznam této konverzace. Pokud tým začne dokumentem, vznikne slovník, kterým nikdo nemluví [[8]](https://martinfowler.com/bliki/UbiquitousLanguage.html).

Evans k tomu přidává pravidlo, které se v praxi přehlíží: změna jazyka je změnou modelu. Vazba platí oběma směry. Nový termín od experta si vynutí úpravu kódu, ale i opačně – když se při modelování ukáže, že dva stavy jsou ve skutečnosti tři, patří to nové rozlišení zpátky do konverzace s expertem, ne jen do enumu.

Praktická forma záznamu: glosář jako markdown soubor v repozitáři, vedle kódu. Ne wiki stránka, ne sdílený dokument v cloudu. Důvod je provozní – glosář v repozitáři prochází code review, má historii v gitu a změna termínu se dá svázat s commitem, který přejmenovává třídy. Glosář udržuje celý tým: kdo termín do kódu zavádí nebo mění, otevírá zároveň PR do glosáře. Doménový expert recenzuje význam; zápis a údržba zůstávají na vývojářích.

### Čeština v konverzaci, angličtina v kódu {#cestina-v-kodu}

Český tým řeší otázku, kterou Evans neřešil: doménoví experti mluví česky, identifikátory v kódu jsou zvykově anglické. Doporučený výchozí stav: **čeština v konverzaci a glosáři, angličtina v identifikátorech kódu**. Glosář pak slouží jako překladová tabulka – každý český termín má závazný anglický ekvivalent a o překladu rozhoduje tým, ne jednotlivý vývojář u klávesnice. Bez tabulky vznikne pro „propadnutí rezervace“ trojí překlad – ve třech třídách `expire`, `lapse` a `timeout`.

Čeština přímo v identifikátorech dává smysl u čistě české domény, pro kterou angličtina nemá ustálený termín. DPH není totéž co VAT v jiné jurisdikci, „datová schránka“ nemá anglický ekvivalent vůbec a překlad `DataBox` význam spíš zamlžuje. Třída `DatovaSchranka` nebo `DphSazba` je v takovém kódu přesnější než vymyšlený anglicismus. Hranici si tým stanoví v glosáři: termíny označené jako nepřeložitelné zůstávají česky.

Logika je stejná jako u překladu mezi kontexty. Uvnitř hranice platí jeden slovník, překládá se až na ní. Glosář je překladovou tabulkou pro dvojici konverzace/kód, [Published Language](/context-mapping#published-language) hraje tutéž roli mezi dvěma bounded contexty.

### Signály eroze jazyka {#eroze-jazyka}

Jazyk eroduje tiše. Tři signály, které erozi prozradí dřív než produkční incident:

- PM mluví o „rezervaci, která propadne za 24 hodin“, kód má `Order::status = 'awaiting_payment'` a cron job. Stejný koncept, dva slovníky – přesně situace z úvodu této kapitoly.
- Na schůzce se překládá. Jakmile vývojář větu experta v duchu převádí („tím myslí náš `PendingOrder`“), model a doména se už rozešly.
- Nový kolega se zeptá, co znamená termín z glosáře, a dostane odpověď „to už se nepoužívá“. Mrtvý glosář je horší než žádný – dokumentuje neexistující jazyk.

Odpověď na erozi je vždy stejná: srovnat kód s jazykem expertů. Tento směr platí pro erozi, ne obecně – při modelování se nový termín často rodí v modelu a do slovníku expertů vstupuje až potom. Přejmenování třídy je levné. Tým, který rok mluví jiným jazykem než jeho kód, platí překladem při každé konverzaci.

### Ukázka glosáře {#ukazka-glosare}

:::code{language="markdown" filename="docs/domain/glosar.md"}
# Glosář – kontext Objednávky (Ordering)

| Český termín | Identifikátor v kódu | Význam | Pozn. |
|---|---|---|---|
| objednávka | `Order` | Závazek zákazníka po kliknutí na „Zaplatit“. | |
| rezervace | `Reservation` | Blokace zboží před zaplacením, propadá za 24 h. | Není to objednávka! |
| propadnutí rezervace | `Reservation::expire()` | Automatické uvolnění blokace po TTL. | Ne „storno“. |
| storno | `Order::cancel()` | Aktivní zrušení zákazníkem nebo operátorem. | |
| dispute | `Dispute` | Sporná platba řešená s bránou. | Jiný proces než reklamace. |
| DPH | `Dph`, `DphSazba` | Česká sazba daně vč. přenesené povinnosti. | Nepřekládat na VAT. |

Změny: každá úprava termínu = PR s odkazem na commit,
který přejmenovává odpovídající třídy. Reviewer: doménový expert.
:::

Glosář nemá ambici být úplný. Zachycuje termíny, u kterých hrozí záměna – dvojice jako rezervace/objednávka nebo storno/propadnutí, kde chybný překlad znamená chybné chování systému.

## 01.04 Strategický design (Strategic Design) {#strategic-design}

:::diagram{fig="01.4-A" title="Strategický vs. taktický design – dvě úrovně rozhodování v DDD" src="images/diagrams/1_layers/strategic_vs_tactical.svg"}
:::

Strategický design rozhoduje, jak rozdělit systém na samostatné části a jak spolu komunikují.

Pořadí sekcí v této kapitole není náhodné. Evans v roce 2009 označil taktické stavební bloky za přeceněné a do centra postavil jazyk, mapování kontextů a Core Domain [[5]](https://www.dddcommunity.org/library/evans_2009_1/). Vernon o sedm let později obrátil pořadí výkladu ve své knize. Fowler považuje strategickou část za Evansův hlavní přínos: problém, jak rozdělit velkou doménu do propojených ohraničených kontextů, před ním nikdo přesvědčivě neřešil [[9]](https://martinfowler.com/bliki/DomainDrivenDesign.html). Kdo si z DDD odnese jen entity a agregáty, dostane objektový návrh s doménovým slovníkem. Strategickou částí se zabývají kapitoly 2 až 5.

Hlavní koncepty:

- **Core Domain** – Ta část domény, kvůli které firma vyhrává nad konkurencí. Evans ji uvádí jako první bod definice DDD a modelovací úsilí patří především sem. Jak ji rozeznat od podpůrných a generických částí, rozvádí [kapitola o subdoménách](/subdomeny#rozpoznat-core).
- **Bounded Context** – Ohraničený kontext je explicitně vymezená oblast, uvnitř které platí jeden doménový model. Plná definice s příkladem následuje v [podsekci níže](#bounded-context).
- **Context Map** – Mapa vztahů mezi kontexty. Zachycuje, kdo komu dodává model, kdo se komu přizpůsobuje a kde se překládá.

Typů vztahu na mapě je osm. **Partnership** znamená dva týmy, které stojí a padají společně a koordinují releasy. **Shared Kernel** je sdílený kus modelu, za který ručí obě strany, a stojí ze všech vztahů nejvíc koordinace. **Customer-Supplier** ukládá dodavateli povinnost brát požadavky odběratele do plánu, zatímco u **Conformist** odběratel převezme cizí model tak, jak je. **Anti-Corruption Layer** cizí model překládá a chrání tím vlastní jazyk. **Open Host Service** naopak nabízí jednotný protokol všem konzumentům a **Published Language** k němu dodává sdílený formát dat. Osmý vztah, **Separate Ways**, je rozhodnutí neintegrovat vůbec – a bývá to správná volba častěji, než se týmy odváží připustit. Devátým, nechtěným stavem je **Big Ball of Mud**: oblast bez rozeznatelných hranic, kterou Evans doplnil až v *DDD Reference*. Užitečné je ji na mapě přiznat a hlavně neexportovat její model ven. Všechny vztahy s rozhodovacími kritérii rozebírá [kapitola o Context Mappingu](/context-mapping#osm-typu-prehled).

### Bounded Context: hranice platnosti modelu {#bounded-context}

Žádný model neplatí všude. Každý je zjednodušením domény pro určitý účel, a mimo tento účel přestává dávat smysl. Bounded Context je explicitní hranice, uvnitř které jeden model a jeden Ubiquitous Language platí beze zbytku. Uvnitř hranice má každý termín právě jeden význam. Co je za ní, model záměrně ignoruje.

Tentýž pojem označuje v různých kontextech jiný model. V e-shopu existuje `Customer` v kontextu Ordering i v kontextu Support, ale jsou to dva různé objekty. Ordering zajímá doručovací adresa, platební metody, kreditní limit a historie objednávek; invarianty se točí kolem placení. Support vidí kontakt s komunikační historií, prioritou SLA a otevřenými tikety; platební data ho nezajímají a nemá k nim mít přístup. Společná je jen identita zákazníka – obvykle ID, přes které se oba modely propojují.

Pokus oba pohledy sloučit do jedné třídy `Customer` vede ke známému výsledku: objekt s třiceti atributy, z nichž každý use case používá pět, a s pravidly, která si vzájemně překáží. Jedna změna pro podporu rozbije fakturaci. Oddělené modely v oddělených kontextech tento konflikt odstraňují – každý model je malý, úplný a vnitřně konzistentní.

Hranice ale není jen sémantická. Evans ji předepisuje současně ve třech rozměrech: v týmové organizaci, v tom, kterých částí aplikace se model týká, a ve fyzických artefaktech – kódových bázích a databázových schématech. Kontext, který sdílí tabulky s jiným kontextem, hranici nemá, ať je v dokumentaci nakreslená jakkoli. Týmový rozměr rozvádí [kapitola o Conwayově zákonu](/team-topologies), fyzický [kapitola o mikroservisách](/ddd-a-microservices).

Bounded Context se také nerovná subdoméně. Subdoména je část problému, bounded context je rozhodnutí o řešení. V nově navrhovaném systému se obojí většinou kryje, ve zděděném skoro nikdy. Rozdíl rozebírá [kapitola o subdoménách](/subdomeny#subdomena-vs-bc-heading).

Explicitní hranice znamená explicitní překlad. Když Ordering potřebuje data ze Support (nebo naopak), komunikace jde přes definované rozhraní a pojmy se na hranici překládají – třeba přes Anti-Corruption Layer zmíněný výše. Překlad není režie navíc; je to zviditelnění práce, která jinak probíhá skrytě a chybově uvnitř sdíleného modelu.

Bounded Context je proto i hranicí jazyka. „Rezervace“ může v kontextu Ordering znamenat blokaci zboží, v kontextu Logistics časové okno doručení. Oba významy jsou správně – každý ve svém kontextu. Implementaci Bounded Contexts rozvádí [kapitola o základních konceptech](/zakladni-koncepty#bounded-contexts), vztahy mezi kontexty pak [kapitola o Context Mappingu](/context-mapping).

## 01.05 Taktický design (Tactical Design) {#tactical-design}

Taktický design řeší konkrétní implementaci doménového modelu uvnitř jednoho bounded contextu. Stavebních bloků je sedm a tato kapitola je jen pojmenuje. Definice, kód a hraniční případy patří do [kapitoly o základních konceptech](/zakladni-koncepty).

- **[Entity](/zakladni-koncepty#entities)** – objekt s identitou, která přetrvává změnu atributů. Zákazník zůstává týmž zákazníkem po změně jména, e-mailu i adresy.
- **[Value Object](/zakladni-koncepty#value-objects)** – objekt definovaný svými hodnotami, neměnný. Peněžní částka, adresa, e-mail.
- **[Aggregate](/zakladni-koncepty#aggregates)** – skupina objektů tvořící jednu jednotku konzistence při zápisu. Vstup zvenčí vede vždy přes kořen agregátu. Je to nejtěžší rozhodnutí taktického DDD a celou kapitolu mu věnuje [Návrh agregátu](/navrh-agregatu).
- **[Domain Event](/zakladni-koncepty#domain-events)** – záznam toho, co se v doméně stalo a co zajímá doménové experty. V knize z roku 2003 tento vzor ještě není; Evans jej doplnil až dodatečně.
- **[Repository](/zakladni-koncepty#repositories)** – kolekcové rozhraní nad persistencí agregátů.
- **[Domain Service](/zakladni-koncepty#domain-services)** – bezstavová operace, která nepatří přirozeně do žádné entity ani hodnotového objektu.
- **Factory** – zapouzdřuje složené vytvoření agregátu tak, aby vznikl už platný. Podrobněji mezi [doplňujícími vzory](/mene-zname-vzory).

:::callout{type="pattern"}
### Příklad: Agregát v e-commerce doméně {#aggregate-example-heading}

Objednávka v e-shopu je agregát: kořenem je `Order`, položky `OrderItem` jsou jeho vnitřní entity a `Money` s `Address` hodnotové objekty. Zvenčí je dosažitelný jen `Order` – volající nepřidá položku přímo, ale požádá o to kořen. Tím drží agregát své invarianty na jednom místě.
:::

:::diagram{fig="01.5-A" title="Stavební bloky taktického designu a vztahy mezi nimi" src="images/diagrams/2_basic_concepts/diagram.svg"}
:::

## 01.06 Implementace DDD v praxi {#implementation}

Typický postup zavedení DDD má osm kroků. První čtyři patří strategickému designu (kontexty, jazyk), zbytek taktickému designu a iteraci modelu.

1. **Pochopení domény** – Rozhovory s experty, workshopy, modelování na tabuli. Cílem není najít správný model, ale projít několik konkurenčních variant a vybrat tu, která na daný problém padne nejlépe. Bez této fáze model padá hned na začátku.
2. **Ubiquitous Language** – Společný slovník vývojářů a doménových expertů, zapsaný a průběžně aktualizovaný. Stejné pojmy v kódu, dokumentaci i mailu od PM.
3. **Identifikace Bounded Contexts** – Doména se rozděluje na menší kontexty s explicitními hranicemi. Každý kontext má vlastní model.
4. **Context Map** – Vztahy mezi kontexty (Customer-Supplier, Conformist, Anti-Corruption Layer) jsou popsané a mají odpovědné týmy.
5. **Doménový model** – Entity, Value Objects, agregáty, doménové služby a události jsou navrženy a implementovány v každém kontextu samostatně.
6. **Implementace** – Vrstvená nebo hexagonální architektura odděluje doménový model od infrastrukturní vrstvy.
7. **Testování** – Doménový model má pokrytí unit testy, hraniční scénáře integrační testy.
8. **Iterace** – Model se průběžně upravuje, jak roste pochopení domény. DDD není jednorázová investice.

Podobný postup udržuje i skupina ddd-crew. DDD Starter Modelling Process rozepisuje totéž do osmi kroků, od porozumění doméně po kód, a je dostupný pod licencí CC-BY [[10]](https://github.com/ddd-crew/ddd-starter-modelling-process).

## 01.07 Výhody používání DDD {#benefits}

Co konkrétně tým získá, když DDD nasadí správně:

První přínos je v komunikaci. Ubiquitous Language odstraňuje nedorozumění mezi vývojáři a doménovými experty, protože všichni používají stejné pojmy v kódu i v konverzaci. S tím souvisí odolnost vůči změnám: model orientovaný na doménu je stabilnější než model orientovaný na databázové schéma a změny v obchodních požadavcích se do něj promítají přirozeněji.

Bounded Contexty pak dovolí vyvíjet, nasazovat a škálovat části systému nezávisle na sobě. Doménové objekty bez infrastrukturních závislostí se testují v izolaci, bez mockování ([kapitola o testování](/testovani-ddd)). Explicitní model navíc nese pravidla přímo v kódu, takže dokumentace nestárne odděleně od implementace – technický dluh tím nezmizí, jen se hůř schová. A protože DDD odděluje Core Domain od podpůrných domén, je vidět, kam modelovací úsilí investovat a kam ne.

:::callout{type="pattern"}
### Konkrétní přínos: přidání nové platební metody {#priklad-platba-heading}

V úvodním příběhu jsme popsali e-shop, kde přidání BitPay trvalo tři týdny. V CRUD architektuře každá nová platební metoda znamená:

1. Přidat větev v `OrderService::processPayment` (a doufat, že netrhne refund logiku).
2. Upravit `OrderService::cancelOrder` (refund pro novou metodu).
3. Doplnit reporting v `MonthlyRevenueService` (statistiky podle metody).
4. Otestovat regrese v `WeeklyCleanupCommand` (TTL rezervací).
5. Smířit se s tím, že některý z těchto kroků pravděpodobně něco rozbije.

V DDD architektuře s explicitním agregátem `Payment` přidání nové metody znamená:

1. Implementovat adapter `BitPayGateway` v Anti-Corruption Layer (jednorázová práce).
2. Zaregistrovat novou metodu v `PaymentMethodRegistry`.
3. Existující agregáty `Order`, `Refund` a `Payment` zůstávají nedotčené – pravidla refundace, reportingu a TTL se nemění, změna se odehrává jen v adapteru a registru.

Rozdíl není v počtu odpracovaných hodin, ale v tom, kam změna sahá. V prvním případě do pravidel objednávky, refundace i reportingu, ve druhém do adaptéru a registru. Hranice agregátů drží refaktor v omezeném prostoru a doménová pravidla zůstávají na jednom místě, ne rozteklá napříč pěti soubory.
:::

Praktické příklady Ubiquitous Language a dalších konceptů naleznete v kapitole [Základní koncepty DDD](/zakladni-koncepty).

## 01.08 Výzvy a omezení DDD {#challenges}

DDD má reálné náklady, se kterými rozhodnutí o nasazení musí počítat:

- **Složitost** – hluboké pochopení domény i architektonických principů; pro vývojáře bez zkušenosti s objektovým modelováním velký skok.
- **Časová náročnost** – V počátku projektu se modelování domény a budování Ubiquitous Language nevrací rychle. Investice se vrátí až s rostoucí složitostí pravidel.
- **Nevhodnost pro jednoduché aplikace** – U CRUD aplikací s minimální doménovou logikou DDD přidává režii bez návratnosti.
- **Integrace s legacy systémy** – Napojení DDD modelu na starý systém typicky vyžaduje Anti-Corruption Layer, který má vlastní cenu.
- **Výkonnost** – Při špatné implementaci hrozí problém N+1 a načítání zbytečně velkých grafů.

K tomu se přidává lidská stránka. Bez přístupu k doménovému expertovi nemá kdo říct, jaká pravidla skutečně platí. Spolupráce vývojářů s experty navíc znamená pravidelné workshopy a sdílený jazyk – a některé organizace na to nejsou nastavené. Tým sám potřebuje měsíce, než získá rutinu; první projekt v DDD bývá pomalejší než stejný projekt v CRUD.

### DDD-Lite: taktika bez strategie {#ddd-lite}

Nejběžnější selhání nespočívá v tom, že tým DDD nezavede. Zavede jeho polovinu. Seznámí se s hodnotovými objekty, entitami, agregáty a repozitáři, prohlásí to za DDD a strategickou část přeskočí. Vernon pro tuto praxi používá název DDD-Lite, ale míní ho jako varování, ne jako odlehčenou variantu pro menší projekty: bez Ubiquitous Language, Bounded Contextu a Context Mappingu podle něj vzniká podřadný doménový model. Výsledkem bývá jeden model pro celou firmu, obalený vzory, které měly řešit něco jiného.

### Kritika a stav evidence {#kritika}

Námitky přicházejí i zvenčí komunity. Stefan Tilkov se ohrazuje proti reflexu volat DDD experty pokaždé, když padne otázka hranic služeb; DDD je podle něj užitečný prostředek, ne cíl [[11]](https://www.innoq.com/en/blog/2021/03/is-domain-driven-design-overrated/). Systematický přehled 36 recenzovaných studií z roku 2023 přidává střízlivější pohled na data. Zkoumané systémy se po nasazení DDD zlepšily, zejména v kombinaci s mikroservisami. Části studií ale chybí empirická validace a hlavní bariérou adopce zůstávají nároky na expertízu a onboarding [[12]](https://arxiv.org/abs/2310.01905). Přínosy z předchozí sekce jsou tedy zkušenostní, ne změřené – a tak s nimi zachází i tato kniha.

:::callout{type="warn"}
### Ilustrativní scénář: DDD bez doménového experta {#priklad-selhani-heading}

Scénář skládá dohromady typické rysy projektů, které DDD zavedly bez doménového experta. B2B startup nasadí DDD na projektu pro správu skladových rezervací. Tým má pět seniorních PHP vývojářů, zná Vernona i Khononova, modeluje agregáty s invariantami a používá CQRS přes Symfony Messenger. Doménový expert v týmu chybí – produktový manažer pracuje externě a má na projekt deset hodin měsíčně.

Po šesti měsících má tým 40 agregátů, 80 doménových událostí a 200 commandů. Kód vypadá jako z učebnice. Ale skutečná pravidla skladu v modelu nikdy nebyla: kdy smí být zboží rezervováno na dvou místech současně, jak se rozhoduje o přesunu mezi sklady, jaký je vztah mezi rezervací a fyzickým výdejem. Tým modeluje vlastní představu domény; realita skladu zůstává mimo model.

Když logistický ředitel po dvou měsících provozu zjistí, že systém umožňuje dvojí rezervaci (a tím způsobuje časté reklamace), vyžaduje okamžitou opravu. Refaktor 40 agregátů a 80 událostí trvá čtyři měsíce. Po roce vývoje a provozu pokrývá projekt 30 % funkcionality, kterou původní CRUD aplikace zvládala.

Lekce: **DDD bez doménového experta v týmu nefunguje.** Pravidla, která doménový expert nezná, nemůže nikdo modelovat. Žádný senior vývojář nedokáže odvodit, jak skutečně funguje sklad, jen z wireframů business analytika. Pokud nemáte přístup k expertovi, kapitola [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) doporučuje začít s jednodušší architekturou a investici do doménového modelování odložit.
:::

## 01.09 DDD vs. jiné přístupy {#ddd-vs-other}

DDD se v praxi nejčastěji srovnává se čtyřmi jinými přístupy. Žádný z nich není přímý konkurent. Některé řeší jinou vrstvu problému; jiné pro jednodušší domény stačí samy o sobě:

- **DDD vs. Transaction Script** – Transaction Script (Martin Fowler, *PoEAA*) organizuje logiku kolem případů užití: každý use case je jedna procedura, která čte data, aplikuje pravidla a ukládá výsledek. **Rozdíl:** Transaction Script nemá doménový model – logika je v procedurách, ne v objektech. Pro jednoduché domény je to přímočařejší; s rostoucí složitostí se pravidla duplikují a kód se hůř udržuje. DDD je vhodnější, jakmile stejná doménová pravidla sdílí více use cases.
- **DDD vs. CRUD** – CRUD (Create, Read, Update, Delete) je datově orientovaný přístup: aplikace je v podstatě editor databázových tabulek. **Rozdíl:** CRUD nerozlišuje mezi doménovým chováním a datovými operacemi – každá akce je variací na čtení/zápis řádku. DDD naproti tomu modeluje chování domény (objednávku nelze jen „updatovat“, ale „potvrdit“, „zrušit“ nebo „odeslat“). Pro jednoduchou správu dat CRUD postačí.
- **DDD vs. Hexagonální architektura** – Hexagonální architektura (Ports and Adapters, Alistair Cockburn) řeší *jak strukturovat závislosti*: doménové jádro komunikuje s vnějším světem přes porty (rozhraní) a adaptéry (implementace). **Rozdíl:** DDD řeší *jak modelovat doménu* (Entity, Value Objects, Aggregates), hexagonální architektura řeší *jak oddělit doménu od infrastruktury*. Doplňují se: DDD nabízí vzory pro doménové jádro, hexagonální architektura ho izoluje od infrastruktury. Volbu mezi hexagonální, onion a clean architekturou rozvádí [kapitola o architektonických stylech](/architektonicke-styly).
- **DDD vs. Mikroservisy** – Mikroservisy jsou architektonický styl zaměřený na *jak nasazovat a škálovat* části systému nezávisle. **Rozdíl:** DDD řeší logické hranice domény (Bounded Contexts), mikroservisy řeší fyzické hranice nasazení. Bounded Context z DDD je přirozeným kandidátem pro hranici mikroservisy, ale neplatí to automaticky – jeden Bounded Context lze implementovat jako více mikroservis a naopak. DDD lze nasadit i v monolitické architektuře.

:::callout{type="warn"}
### Kdy nepoužívat DDD {#when-not-to-use-heading}

DDD nemusí být vhodný pro všechny projekty. Nevyplatí se, pokud:

- Vyvíjíte jednoduchou aplikaci s minimální doménovou logikou.
- Nemáte přístup k doménovým expertům.
- Váš tým nemá zkušenosti s DDD a nemá čas se ho naučit.
- Máte velmi omezený čas a rozpočet.
:::

## 01.10 Shrnutí {#summary}

DDD strukturuje práci do tří vrstev. Každá má jiné odpovědnosti:

- **Strategický design** – Bounded Contexts, Context Map, Ubiquitous Language
- **Taktický design** – Entity, Value Objects, Aggregates, Repositories, Domain Events, Services, Factories
- **Implementační vzory** – Anti-Corruption Layer, [Specification](/glosar#term-specifikace), [Saga / Process Manager](/glosar#term-saga)

DDD se osvědčuje v aplikacích s bohatou doménou, kde přesné modelování obchodní logiky přináší měřitelnou hodnotu. Má reálné náklady – naučení se vzorů, vyšší počáteční složitost, nutnost spolupráce s doménovými experty – a proto vyžaduje vědomé rozhodnutí.

:::faq{}
- question: Co je Domain-Driven Design?
  answer: 'Domain-Driven Design (DDD) je přístup k vývoji softwaru, který staví modelování domény do středu celého návrhu. Systematicky jej popsal Eric Evans v knize z roku 2003. Cílem je, aby software co nejpřesněji odrážel způsob, jakým v dané oblasti uvažují doménoví experti, a aby tento soulad vydržel i při růstu aplikace. Podrobnosti v <a href="#definition">sekci Definice DDD</a>.'
- question: Co je Ubiquitous Language v DDD?
  answer: 'Ubiquitous Language (všudypřítomný jazyk) je společný slovník používaný vývojáři i doménovými experty při návrhu, diskuzi i implementaci systému. Platí vždy uvnitř jednoho bounded contextu, ne napříč celou firmou – hranice je součástí Evansovy definice. Stejné pojmy se objevují v doménové dokumentaci, v rozhovorech nad modelem i přímo v kódu, takže kód nemodeluje něco jiného, než doména potřebuje. Více v <a href="#ubiquitous-language-v-praxi">sekci Ubiquitous Language v praxi</a>.'
- question: Co je Bounded Context a k čemu slouží?
  answer: 'Bounded Context (ohraničený kontext) je explicitně definovaná hranice, uvnitř které platí jeden konzistentní doménový model a jeden Ubiquitous Language. Mimo tuto hranici mohou stejné pojmy znamenat něco jiného – například „Customer“ ve fakturaci a „Customer“ v podpoře jsou různé modely s různými atributy. Bounded Contexts pomáhají rozdělit složitou doménu na menší zvládnutelné části. Jsou přirozeným kandidátem na hranici mikroservisy, ale neplatí to automaticky: jeden bounded context lze nasadit jako několik služeb i jako modul v monolitu. Viz <a href="#bounded-context">podsekce o Bounded Contextu</a>.'
- question: Kdy se DDD nevyplatí použít?
  answer: 'Stručně: DDD nepřináší odpovídající hodnotu u projektů s triviální doménovou logikou, v týmech bez přístupu k doménovým expertům a při krátkém horizontu produktu. Detailní rozbor podmínek, příznaků a alternativ obsahuje samostatná kapitola <a href="/kdy-nepouzivat-ddd">Kdy DDD nepoužívat</a>.'
:::

## 01.11 Další četba {#further-reading}

Hlavní zdroje:

- [Domain Language – oficiální stránky Erica Evanse a DDD komunity](https://www.domainlanguage.com/ddd/)
- [Domain-Driven Design Reference (PDF, CC-BY) – Eric Evans, 2015](https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf) – definice a shrnutí všech vzorů na 50 stranách zdarma; nejlevnější vstup do tématu
- [Domain-Driven Design: Tackling Complexity in the Heart of Software – Eric Evans](https://www.amazon.com/Domain-Driven-Design-Tackling-Complexity-Software/dp/0321125215)
- [Implementing Domain-Driven Design – Vaughn Vernon](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)
- [Domain-Driven Design Distilled – Vaughn Vernon](https://www.amazon.com/Domain-Driven-Design-Distilled-Vaughn-Vernon/dp/0134434420)
- [DDD Community](https://dddcommunity.org/)
- [DDD Starter Modelling Process – ddd-crew](https://github.com/ddd-crew/ddd-starter-modelling-process) – udržovaný postup zavedení DDD v osmi krocích, CC-BY

## 01.12 Jak číst tuto knihu {#jak-cist}

Tato kapitola je první v sekvenci 24 kapitol. Pořadí kapitol je promyšlené – každá staví na předchozích – ale málokdo potřebuje lineární čtení od první do poslední. Většina čtenářů má konkrétní bolest a kniha je připravená na selektivní čtení.

Pro detailní cesty čtení podle role (junior/mid Symfony developer, senior PHP developer, architekt, tech lead, vývojář migrující z CRUD) projděte [Předmluvu, sekci 'Jak číst tuto knihu'](/predmluva#jak-cist). Stručný přehled částí knihy:

- **Strategický design** (kap. 2–5) odpovídá na otázku *kde* DDD aplikovat. Subdomény, Bounded Contexts, [Event Storming](/event-storming), Team Topologies. Pokud z této kapitoly odejdete s pocitem, že DDD ve vašem projektu nedává smysl, kapitoly 2–5 vám potvrdí proč. Pokud má smysl, dají vám nástroj, jak začít.
- **Taktický design** (kap. 6–9) pokrývá konkrétní stavební bloky: entity, hodnotové objekty, agregáty, [doplňující vzory](/mene-zname-vzory), [architektonické styly](/architektonicke-styly). Nejdůležitější je [návrh agregátu](/navrh-agregatu) – nejtěžší rozhodnutí v taktickém DDD.
- **Implementace v Symfony** (kap. 10–11) překládá teorii do konkrétního Symfony 8 kódu s Doctrine ORM, Messenger a aktuálními PHP rysy. Plus [autorizace ve čtyřech vrstvách](/autorizace-v-ddd).
- **Pokročilé vzory** (kap. 12–15) obsahují CQRS, Event Sourcing, Ságy a Outbox Pattern. Tyto vzory nejsou pro každý projekt – kapitoly začínají rozhodovacím rámcem, kdy ano a kdy ne.
- **Výkon a testování** (kap. 16–17), **migrace a microservices** (kap. 18–19), **provozní problémy, anti-vzory a kdy DDD nepoužívat** (kap. 20–22), **praktické příklady** (kap. 23–24) uzavírají knihu.

Pokud váháte, jestli má vůbec smysl pokračovat, nabízí se tento postup. Přečtěte si tuto kapitolu (1) a kapitolu [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd). Pokud po obou kapitolách máte pocit, že DDD ve vašem projektu dává smysl, pokračujte na kapitolu 2 [Subdomény](/subdomeny). Pokud váháte, projděte ještě [Cheat Sheet](/cheat-sheet) – jednostránkový přehled pro rychlou orientaci.

Pro definice termínů slouží [Glosář](/glosar). Pro citace knih a článků v každé kapitole je sekce „Další četba“ (jako tato). Souhrnný seznam knih, konferencí a nástrojů najdete na stránce [Zdroje](/zdroje). Co s doménovým modelováním dělá práce s jazykovými modely, shrnuje samostatná stránka [DDD a umělá inteligence](/ddd-a-umela-inteligence).
