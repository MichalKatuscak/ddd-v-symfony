---
route: subdomains
path: /subdomeny
title: "Subdomény: Core, Supporting, Generic"
page_title: "Subdomény: Core, Supporting, Generic | DDD Symfony"
meta_description: "Kam soustředit modelovací úsilí: rozlišení Core, Supporting a Generic subdomén. Kde nasadit plný taktický design a kde stačí Doctrine CRUD nebo SaaS."
meta_keywords: "Core Domain, Supporting Subdomain, Generic Subdomain, strategický DDD, subdoména, Eric Evans, business strategy, build vs buy, Symfony"
og_type: article
published: "2026-04-29"
modified: 2026-09-07
breadcrumb_name: Subdomény
schema_type: TechArticle
schema_headline: "Subdomény: Core, Supporting, Generic – kde investovat modelovací úsilí"
chapter_number: "02"
category: Základy
deck: "Než vytvoříte první Aggregate, rozhodněte, kde to vůbec dává smysl. Subdomény jsou strategický filtr DDD: tři kategorie, které určují, kolik úsilí, jakou seniority a jaký technologický stack si konkrétní část aplikace zaslouží."
reading_time: 21
difficulty: 2
github_examples: null
---

## 02.01 Proč subdomény předcházejí všemu ostatnímu {#proc-subdomeny}

Vývojářský reflex „naimplementuju to celé pořádně“ je drahý a u většiny produktů marný. Ne každá část aplikace si zaslouží stejnou hloubku modelování. Pokus modelovat *všechno* stejně pečlivě patří mezi nejspolehlivější cesty, jak vyčerpat rozpočet dřív, než tým dojde k tomu, co zákazníka skutečně zajímá. Evans v *Domain-Driven Design* (2003), kapitola „Distillation“, zavádí pro celou doménu strategický filtr a dva jeho pojmy: **Core Domain** a **Generic Subdomains** [[1]](https://www.domainlanguage.com/ddd/). Vaughn Vernon v *Implementing Domain-Driven Design* (2013) trojici dokončil pojmem **Supporting Subdomain** [[2]](https://kalele.io/books/). Evans slovo „supporting“ v roce 2003 používá, ale jen popisně; jako pojmenovaný vzor v knize ani v *DDD Reference* (2015) nefiguruje. Než napíšete první Aggregate nebo Value Object, potřebujete odpověď na otázku: **která část domény je vaše konkurenční výhoda, která nutné zlo a kterou nedává smysl vůbec psát**. Tomuto filtru se dnes říká rozdělení domény na **Core**, **Supporting** a **Generic** subdomény.

Obchodní myšlenka je přitom starší než DDD. Geoffrey Moore v *Dealing with Darwin* (2005) dělí činnosti firmy na *core*, které vytváří odlišení, a *context*, tedy všechno ostatní, co musíte dělat, abyste zůstali v byznysu. Jeho pravidlo zní: context minimalizovat, automatizovat nebo outsourcovat. Evansova destilace říká totéž jazykem modelu.

Subdoména není totéž co [Bounded Context](/zakladni-koncepty#bounded-contexts), ačkoliv se oba pojmy v rozhovorech běžně zaměňují. Bounded Context je *implementační* hranice: místo, kde platí jeden Ubiquitous Language, jeden konzistentní model a typicky jeden tým s jednou nasazovací jednotkou. Subdoména ohraničuje obchod. Je to kus problému, který organizace řeší jako jednu ucelenou kapitolu. Vztah mezi nimi není 1:1. Jedna subdoména („Pricing“) se může rozdělit do více BC: Catalog počítá indikativní cenu, Checkout závaznou cenu se slevami. Naopak jeden BC může pokrývat více malých subdomén, například Backoffice zpravidla sdruží kousky reportingu, fakturace i správy uživatelů.

Dělicí čára mezi obojím není ostrá. Zkratka „subdomény objevuje byznys, kontexty navrhují inženýři“ se dobře pamatuje, ale realita ji nectí. Hranice subdomény se v praxi vyjasní často až ve chvíli, kdy tým začne modelovat.

Vernon to v kapitole 2 *Implementing Domain-Driven Design* (2013) formuluje pragmaticky [[2]](https://kalele.io/books/): *doména* je celý problémový prostor organizace; *subdoména* je jeho logická část; *Bounded Context* je řešení, které pro ni navrhujete. Vlad Khononov v *Learning Domain-Driven Design* (O'Reilly 2021), kapitola 1 „Analyzing Business Domains“, k tomu doplňuje: **klasifikace subdomén je první nástroj DDD a zároveň nejlevnější**. Stojí jediný workshop a změní distribuci milionů korun rozpočtu [[3]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/).

Jak potvrzuje [úvodní kapitola](/co-je-ddd#strategic-design): chyba ve volbě subdomény vás bude stát násobně víc než chyba v jednotlivém Aggregate. Špatně navržený Aggregate refaktorujete za dva sprinty. Špatně klasifikovaná Core Domain znamená rok vývoje v nesprávné oblasti – a promarněný čas na skutečném diferenciátoru. Cílem této kapitoly je naučit vás **filtrovat dřív, než modelujete**.

:::callout{type="note"}
### Subdoména vs. Bounded Context – krátký test {#subdomena-vs-bc-heading}

Pokud si nejste jisti, zda mluvíte o subdoméně, nebo o Bounded Contextu, položte si tyto dvě otázky:

- **„Existoval tento koncept v organizaci dřív, než vznikl IT systém?“** Pokud ano, mluvíte o subdoméně (např. „prodej“, „logistika“, „personalistika“).
- **„Vznikl tento koncept, protože jsme potřebovali oddělit modely v softwaru?“** Pokud ano, mluvíte o Bounded Contextu (např. „Catalog Service“, „Checkout Service“, „Identity Provider“).

Heuristika: o subdoménách mluví CFO a produkt, o Bounded Contexts architekt a tech-lead.
:::

## 02.02 Tři kategorie subdomén {#tri-kategorie}

Rozdělení je záměrně hrubé: tři škatulky, žádný odstín. Důvod je praktický: jakmile se v každé z nich rozhodnete pro investici, podrobnost už řeší taktické úrovně (Aggregate, Repository, Domain Service). Strategická úroveň potřebuje jen tolik granularity, aby šlo říct: *do této kategorie investujeme, do této ne*.

**Core Domain** *(jádrová doména)*

Část domény, která tvoří **konkurenční výhodu organizace**, tedy to, kvůli čemu zákazníci platí právě vám a ne někomu jinému. Test: *„pokud z toho zítra ustoupíme, ztratíme zákazníky.“* Nebo formulováno opačně: pokud byste si stejnou funkcionalitu mohli stejně levně koupit od dodavatele, není to Core, je to Generic.

Důsledky pro tým a stack: plný taktický DDD design (Aggregate, Value Object, Domain Event), seniorní tým, vlastní IP, nízká tolerance k externím závislostem v jádře. Sem patří i nejvíc automatizovaných testů, nejpřísnější code review a nejčastější diskuse s doménovými experty. Khononov uvádí jako příklady [[3]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/) ridesharing a matching jezdců u Uberu nebo ranking algoritmus vyhledávání u Googlu. Každý z nich je pro svou firmu Core. Je to přesně to, čím se liší od trhu. Tentýž autor přidává druhé kritérium, které samotná konkurenční výhoda neobsáhne: Core subdoména s nízkou složitostí poskytne jen krátkodobou výhodu, protože ji konkurence dorovná. Core subdomény jsou proto z podstaty složité.

**Supporting Subdomain** *(podpůrná subdoména)*

Část domény, která je **nezbytná pro provoz, ale nediferencuje vás**. Test: *„potřebujeme to, ale nikdo nás kvůli tomu nenajme.“* Klasické příklady: správa objednávek v e-shopu, evidence skladu, fakturace, reporting pro management. Kdyby Supporting fungoval „stejně jako u konkurence“, nikdo by si toho nevšiml. Kdyby ale vůbec nefungoval, provoz by stál.

Důsledky pro tým a stack: lehký DDD (často stačí *anemic* model s těžkým [Doctrine ORM](/implementace-v-symfony)), juniorní až mediorní tým, ochota použít hotová řešení, kde dávají smysl. Cílem je **fungovat spolehlivě s minimálními náklady na údržbu**, ne mít nejhezčí model. Místo paušálu se hodí test. U Supporting subdomény, kterou nelze pořídit hotovou jako Generic, se taktický návrh vyplatí za tří podmínek: tým ho zvládá, model je inovativní a má vydržet roky. Kde tyto podmínky neplatí, vynaloží organizace seniorní čas na něco, co nikoho nezajímá. Test je autorská konstrukce této knihy; Vernon [[2]](https://kalele.io/books/) dává obecnější vodítko, že Supporting si zaslouží méně modelovacího úsilí než Core.

**Generic Subdomain** *(generická subdoména)*

Část domény, která je **komoditizovaná**. Test: *„řešení existuje 30 let, prodává se v krabici nebo v cloudu, koupíme.“* Klasické příklady: autentizace uživatelů, posílání transakčních e-mailů, integrace platební brány, generování PDF faktur, fulltext, antispam. **Výchozí volbou je koupit, ne psát.** Evans je v tomto opatrnější, než se mu obvykle přisuzuje: in-house implementaci uvádí mezi čtyřmi legitimními variantami sourcingu a o hotových řešeních píše, že se obvykle nevyplatí, ale stojí za prozkoumání [[1]](https://www.domainlanguage.com/ddd/). Vlastní kód je tedy obhajitelný tam, kde integrační náklad převýší ten implementační. Kde takový důvod chybí, znamená znovuobjevování kola na účet Core Domény.

Důsledky pro tým a stack: SaaS, open-source knihovna, externí API, případně tenký bridge / Anti-Corruption Layer mezi naším modelem a komoditním řešením. Sem patří integrace na Auth0 / Keycloak, Stripe, Mailgun, AWS SES, Algolia. **Velikostní pravidlo palce**: pokud na konkrétní Generic subdoméně sedíte víc než 5–10 % vývojové kapacity, něco je špatně. Číslo je autorské, žádný primární zdroj ho neuvádí – stejně jako ostatní procenta v této kapitole. Buď jste zvolili nevhodný produkt, nebo jste subdoménu klasifikovali nesprávně.

:::diagram{fig="02.2-A" title="E-shop: subdoménové členění a investice" src="images/diagrams/11_subdomains/core_supporting_generic.svg"}
:::

### Tři osy, ne jedna {#tri-osy}

Konkurenční výhoda je jen první osa. Khononov přidává další dvě, komplexitu a volatilitu, a teprve celá trojice odliší Generic od Supporting jinak než větou „existuje na to SaaS“ [[3]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/).

| Typ | Konkurenční výhoda | Komplexita | Volatilita | Sourcing |
|---|---|---|---|---|
| Core | ano | vysoká | vysoká | build in-house |
| Supporting | ne | nízká | nízká | build in-house nebo outsource |
| Generic | ne | vysoká | nízká | koupit / převzít hotové |

Core i Generic jsou složité. Rozdíl je v tom, že Generic je složitý *vyřešený* problém, který se nemění: kryptografie, OAuth, doručitelnost e-mailů. Supporting je naproti tomu jednoduchý z podstaty. Formuláře, seznamy, dva tři stavy. Označení „glorified CRUD“ na ni sedí.

Z tabulky plyne diagnostická otázka, kterou klasifikace podle jediné osy položit neumí: **složitost ve Supporting subdoméně je signál**. Buď se v ní skrývá nerozpoznaná Core subdoména, nebo jde o nahodilou složitost, kterou tam nikdo nechtěl. Nick Tune pro kombinaci vysoké složitosti a nulového odlišení používá název *Suspect Supporting* [[6]](https://nicktune.substack.com/p/core-domain-patterns-941f89446af5). V legacy projektech bývá nejčastějším nálezem celého cvičení.

Volatilita je třetí osa a rozhoduje o architektuře. Pravidla, která se mění každý sprint, potřebují model, jenž změnu unese; pravidla, která se nezměnila deset let, si vystačí s tabulkou a jedním servisním objektem. Khononovův rozhodovací řetězec (typ subdomény → vzor obchodní logiky → architektonický styl) rozvádí [kapitola o tom, kdy DDD nepoužívat](/kdy-nepouzivat-ddd#hybrid-subdomain).

:::callout{type="pattern"}
### Investiční matice Core / Supporting / Generic {#invest-matrix-heading}

Tabulka, kterou by měl mít na zdi každý tech-lead i CTO podepisující rozpočty:

| Aspekt | Core | Supporting | Generic |
|---|---|---|---|
| Modelovací úsilí | Maximální – plný taktický DDD | Střední – lehký DDD nebo CRUD+ | Minimální – žádný vlastní model |
| Seniorita týmu | Senior + doménový expert | Medior, junior pod dohledem | Junior, integrátor |
| Technologie | Vlastní IP, žádné SaaS v jádru | Doctrine ORM, standardní bundles | SaaS, open-source, externí API |
| Vlastnictví IP | 100 % in-house | In-house, ale bez ambicí | Žádné – komodita |
| Tolerance k bugům | Nulová – okamžitý fix | Nízká – fix ve sprintu | Střední – eskalace na vendora |
| Hloubka testů | Unit + property + acceptance | Unit + integration | Smoke / kontraktní |
:::

### Distillation nad rámec klasifikace {#distillation-beyond}

Klasifikace Core / Supporting / Generic je první krok Evansovy destilace, ne celý postup. Kapitola 15 „Distillation“ v *Domain-Driven Design* obsahuje sedm vzorů: `CORE DOMAIN`, `GENERIC SUBDOMAINS`, `DOMAIN VISION STATEMENT`, `HIGHLIGHTED CORE`, `COHESIVE MECHANISMS`, `SEGREGATED CORE` a `ABSTRACT CORE`. Domain Vision Statement rozebírá [sekce 02.09](#dvs-template), Abstract Core je nejdražší z celé sady: vytáhne nejobecnější doménové koncepty do samostatného modulu. Dává smysl až u modelu, který tým čtvrtým rokem nestíhá číst. Zbývající tři techniky jsou ty, po kterých sáhnete nejdřív:

**Highlighted Core.** Jádrové prvky se označí přímo v existujícím modelu: krátkým destilačním dokumentem (pár stran) nebo zvýrazněním v dokumentaci a kódu, bez jakéhokoliv refaktoringu. Nejlevnější technika destilace. Hodí se, když tým potřebuje sdílené vědomí o tom, co je jádro, ale na strukturální změny nemá čas ani mandát.

**Segregated Core.** Refaktoring, který jádrové koncepty přesune do samostatného modulu a odřízne je od podpůrného kódu. Dává smysl, když je Core tak propletené se Supporting třídami, že ho v modelu nikdo nevidí a každá změna jádra táhne za sebou periferii. Platí se přepisem a investice se vrací jen u skutečné Core Domény.

**Cohesive Mechanisms.** Výpočetně složitý, ale koncepčně oddělitelný mechanismus (grafový algoritmus, přepočtový engine) se vyčlení do samostatného pomocného frameworku. Doménový model pak deklaruje, *co* se počítá; mechanismus řeší *jak*. Nasazuje se ve chvíli, kdy technické „jak“ začíná v modelu zastiňovat doménové „co“.

## 02.03 Jak rozpoznat Core Domain – pětibodový test {#rozpoznat-core}

Nejtěžším krokem je rozpoznat Core Domain. Týmy mají sklon o všem prohlašovat, že je to „strategicky důležité“. Pojem Core Domain tím klesne na bezvýznamný štítek.

Následující pětibodový test je autorská konstrukce této knihy, ne heuristika převzatá z primárního zdroje. Khononov odlišuje typy subdomén třemi osami: konkurenční výhoda, složitost byznys logiky a volatilita [[3]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/). Ke svým dvěma osám nabízí ddd-crew katalog devíti otázek [[5]](https://github.com/ddd-crew/core-domain-charts). Test níže obojí zjednodušuje do rychlého filtru, který zvládne jednotlivec u kávy. Každou položku ohodnoťte ANO/NE. Tři a více ANO znamená kandidáta na Core Domain; v opačném případě jde o Supporting nebo Generic.

1. **„Pokud bychom to outsourcovali, přijdeme o hlavní produkt?“**

   Pokud ANO (= bez té funkcionality nemáme co prodávat) → jde o kandidáta na Core. Pokud NE → není to Core. Příklad: e-shop může outsourcovat platby (není Core), ale nemůže outsourcovat svůj sortiment a způsob jeho doporučování (kandidát na Core).

2. **„Chybí pro tuto oblast tržní benchmark / standard?“**

   Pokud NE (standard existuje) → s vysokou pravděpodobností Generic. Standard znamená, že problém už někdo vyřešil a trh se shodl, jak má řešení vypadat. Příklad: OAuth 2.1 / OpenID Connect pro autentizaci, ISO 8583 pro karetní platby, RFC 5321 pro SMTP. Pokud ANO, výsledek je neutrální: může jít o Core i Supporting.

3. **„Píšeme to už podruhé jinak než konkurence?“**

   Pokud ANO → silný indikátor Core. Vývoj „jinak než ostatní“ je nákladný a smysl má jen tehdy, pokud z té odlišnosti plyne tržní výhoda. Pokud děláme něco jinak *bez* hmatatelné výhody, je to často špatně klasifikovaná subdoména. Měli jsme koupit standardní řešení.

4. **„Mluví o tom CEO / VP product každý týden?“**

   Pokud ANO → silný indikátor Core. Vrcholný management se nezabývá Supporting subdoménami; o těch slyší jen tehdy, když přestanou fungovat. Pokud o určité funkcionalitě průběžně rozhoduje CEO, je to konkurenční diferenciátor, tedy Core. Pokud ne, je to provoz.

5. **„Plánujeme v této oblasti experimentovat / měnit pravidla často?“**

   Pokud ANO → Core. Frekvence změn je proxy pro to, jak silně se v té oblasti hraje o trh. V Generic subdoménách se pravidla nemění. Autentizace funguje letos stejně jako loni. V Core Doméně tým testuje, A/B měří a iteruje na doménových pravidlech, protože právě v iteraci je výhoda.

Test má jeden užitečný vedlejší efekt: **nutí formulovat obchodní důvody před technickými**. Pokud na otázku 4 („mluví o tom CEO?“) tým odpoví „nevím, neptali jsme se“, je to znamení, že strategický rozhovor musí proběhnout ještě před začátkem implementace.

### Core Domain Charts – tři škatulky jako souvislá rovina {#core-domain-charts}

Pětibodový test dá odpověď ANO/NE. Core Domain Charts komunity ddd-crew místo toho kreslí graf o dvou osách: svislá je komplexita, vodorovná obchodní diferenciace [[5]](https://github.com/ddd-crew/core-domain-charts). Subdomény se do něj umisťují jako body, ne jako položky ve třech přihrádkách. Core, Supporting a Generic pak nejsou škatulky, ale oblasti v jedné rovině. Subdoména, která leží mezi nimi, přestane být problémem klasifikace a stane se tématem k rozhovoru.

Právě v tom rozhovoru leží podle autorů hlavní přínos techniky. Komplexitu umí odhadnout inženýři, diferenciaci dodává produkt nebo byznys; graf je jediné místo, kde obě skupiny musí své odhady postavit vedle sebe. Šablona k tomu nabízí katalog otázek pro obě osy. U komplexity odděluje esenciální doménovou složitost od nahodilé technické a operační. U diferenciace se ptá zvlášť na obtížnost pro nováčka na trhu a pro stávajícího konkurenta.

Praktické rozdělení rolí: test v předchozí sekci jako rychlý filtr pro jednotlivce, chart jako výstup workshopu, který si tým pověsí na zeď a za rok do něj překreslí posuny.

:::callout{type="note"}
### Když test říká, že máte 5 Core domén {#too-many-cores-heading}

Pokud z testu vyjde pět nebo víc Core domén, je to varovný signál, ne verdikt. **Core je vzácné, ne většinové.** Evansovo „make the core small“ omezuje velikost jádra, ne jeho počet. Khononov výslovně připouští, že organizace může mít víc Core subdomén, pokud soutěží na několika osách zároveň, a že celý podnik může být poskládaný jen ze Supporting a Generic [[7]](https://vladikk.com/2018/01/26/revisiting-the-basics-of-ddd/). Číselné pravidlo tedy neexistuje. Existuje jen podezření, že si pletete pojmy „důležité pro nás“ a „diferencujeme se tím“:

- Logistika je v Amazonu Core. V e-shopu, který používá DPD, je to Generic (DPD má vyřešeno).
- Reporting je v BI startupu Core. V e-shopu je to Supporting (potřebujeme to, ale neutrhneme se tím).
- Transakční e-maily jsou pro Mailgun Core byznys. U vás jsou Generic – transport se konfiguruje, nepíše.

Kontrolní otázka místo limitu: dokážete u každé z těch pěti pojmenovat, co konkrétně děláte na trhu jinak než konkurence a jak to zákazník pozná? Co touto otázkou neprojde, je Supporting subdoména s heroickou nálepkou.
:::

## 02.04 Anti-vzor: „všechno je Core“ {#vsechno-core-antipattern}

Nejčastější chyba ve strategickém DDD se nejmenuje „špatně navržený Aggregate“, ale **„všechno je Core“**. Týmy mají k této chybě silný psychologický sklon. Každý vývojář, kterého se zeptáte, zda je jeho oblast strategická, řekne ANO. Důvody: ego, kariérní obavy z „nedůležité“ oblasti a obecná tendence přeceňovat vlastní práci. Každá funkcionalita má svého hrdinu, který ji obhajuje jako nezbytnou pro firmu. Na EventStorming workshopech je ten jev dobře vidět [[4]](https://www.eventstorming.com/).

Manažerská rovina situaci zhoršuje. Ředitel bez technického zázemí slyší od každého vedoucího týmu, že jeho oblast je strategická. Nemá nástroj, jak vyhodnotit, kde je investice opodstatněná a kde jde o obhajobu pozic. Výsledek: rozpočet se rozteče rovnoměrně, Core dostane stejně jako fakturace. Do dvou let firmu předběhne menší konkurent, který soustředil pětinásobek do svého skutečného Core.

Třetí rozměr je technický. Pokud je „všechno Core“, vznikne **monolitický doménový model bez priorit**: každá entita je prvotřídní, každý use case má vlastní Aggregate, každá akce má Domain Event. Refactor jednoho zákoutí se dotýká dvaceti dalších, výkon trpí, testy běží hodinu. Zdravá DDD aplikace má naopak ostře vyhraněnou hierarchii: pár Aggregatů v Core, lehké modely v Supporting a tenké adaptéry v Generic.

:::callout{type="warn"}
### Ilustrativní scénář: custom auth jako rozpočtová past {#custom-auth-warning-heading}

Celý scénář se vejde do jediné schůzky. B2B FinTech, 12 vývojářů, plánování kvartálu. „Autentizaci si napíšeme sami, je bezpečnostně zásadní,“ navrhne tech-lead a odhadne šest sprintů. CTO přikývne. Dvě otázky z pětibodového testu na schůzce nepadnou: *Existuje tržní standard?* (Ano: OAuth 2.1, OpenID Connect, hotové implementace.) *Diferencuje nás to?* (Ne, zákazník platí za produkt, přihlašovací obrazovku má každý.)

Účet za 18 měsíců vypadá takto:

- Odhad 6 sprintů, skutečnost přibližně 6 člověko-let: login, registrace, reset hesla, TOTP, auditní log doplněný po GDPR auditu, SAML 2.0 pro enterprise zákazníka, rozpracovaný WebAuthn po SOC 2 auditu.
- Migrace na Auth0 nakonec stejně proběhne – jen o rok a půl později a po zaplacení vlastního vývoje.
- Největší položka na účtu není auth samotné, ale ušlá práce na Core Doméně, kterou tým mezitím odkládal.

Lekce: autentizace je **Generic subdoména** u 99 % organizací. Pokud nestavíte Auth0, Okta nebo Keycloak, patří váš čas jinam. Custom auth je v tom 1 % případů Core (např. peer-to-peer kryptoměnové burzy s vlastním podpisovým schématem); ve zbytku je to drahý anti-vzor.

Související diskuse: [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) – pokud po klasifikaci subdomén vyjde, že nemáte Core Domain, plné DDD pravděpodobně nestojí za náklady.
:::

Obrana proti anti-vzoru „všechno je Core“ je přímočará: **vynuťte si rozpočet**. Před začátkem každého kvartálu (nebo OKR cyklu) si nakreslete tři škatulky: Core / Supporting / Generic. Do každé napište procentní podíl celkové vývojové kapacity. Pokud vám do Core spadne 80 %, není to 80 % Core, ale 80 % iluze. Distribuce, se kterou u průměrné B2B SaaS firmy počítá tato kniha: **20–30 % Core, 50–60 % Supporting, 10–20 % Generic**. Jde o pravidlo palce zkalibrované na středně velkých produktech, ne o měřená data. Poslední číslo bývá nejnižší – Generic se z definice *nepíše*, jen integruje.

## 02.05 Mapování subdomén na Bounded Contexts {#subdomeny-na-bc}

Subdoména a Bounded Context se mapují přes tři standardní vztahy: **1:1** (jedna subdoména = jeden BC, žádoucí stav), **1:N** (jedna subdoména se dělí do více BC) a **N:1** (více malých subdomén žije v jednom BC, obvyklé pro Supporting / Generic). Vernon doporučuje cílit na 1:1 všude, kde to jde. Khononov jde dál a před rozdělováním souvislé funkcionality varuje: kontexty pak nelze rozvíjet nezávisle, protože tatáž změna požadavků zasáhne oba a vynutí si současné nasazení. Legitimním důvodem k rozdělení zůstává potřeba oddělit vývojové cykly nebo škálovat část nezávisle na zbytku; to už je ale doporučení této knihy, ne jeho formulace [[3]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/).

Pro názornost mapujme imaginární e-shop střední velikosti (3–4 týmy, 25 vývojářů) na subdomény a Bounded Contexts:

| Subdoména | Klasifikace | Bounded Context(s) | Vztah | Poznámka |
|---|---|---|---|---|
| Pricing & Promotions | Core | Catalog BC, Checkout BC | 1:N | Rozdělené kvůli provozu: listing a checkout se škálují i nasazují jiným tempem. |
| Personalized Recommendations | Core | Recommendation BC | 1:1 | Vlastní ML model + read-only projekce nákupů z ostatních BC. |
| Order Management | Supporting | Ordering BC | 1:1 | Jednoznačná hranice, lehký DDD. |
| Inventory | Supporting | Warehouse BC | 1:1 | Stav skladu, rezervace, příjemky. |
| Customer Support | Supporting | Support BC (Zendesk + ACL) | 1:1 přes ACL | Hotový SaaS, Anti-Corruption Layer kvůli mapování ID a zákazníků. |
| Identity / Auth | Generic | External IdP (Auth0) | 1:1 přes ACL | Žádný interní BC, jen tenký bridge a `UserProvider`. |
| Payments | Generic | External (Stripe / Adyen) | 1:1 přes ACL | Webhook subscriber, mapování na náš `PaymentIntent`. |
| Email Delivery | Generic | External (AWS SES / Mailgun) | 1:1 přes ACL | Symfony Mailer + transport bundle. |
| Reporting / Analytics | Supporting | Analytics BC | N:1 | Více malých subdomén (Sales, Stock, Marketing) sdílí jeden BC s read modely. |

Tabulka ilustruje typický rozklad: Core má vlastní silně modelované BC, Supporting má 1:1 BC s lehčím designem, Generic přebírá cizí BC (externího providera) přes [Anti-Corruption Layer](/context-mapping#acl). Pokud by ve vašem produktu vyšlo radikálně jiné rozložení (např. 5 Core BC + žádný Generic), je to signál pro re-validaci klasifikace. Kdo hranice teprve hledá, začne u [Big Picture EventStormingu](/event-storming#big-picture); komu už hranice sedí a řeší, který tým co dostane, pokračuje [kapitolou o Team Topologies](/team-topologies).

:::callout{type="note"}
### Vztahy mezi BC v context mappingu {#forward-context-mapping-heading}

Detailní rozbor vztahů mezi Bounded Contexts (Customer-Supplier, Conformist, Anti-Corruption Layer, Open Host Service, Published Language) najdete v navazující [kapitole o Context Mappingu](/context-mapping). V této kapitole stačí vědět, že:

ACL (Anti-Corruption Layer) je standardní vzor pro integraci s Generic subdoménou. Chrání naše modely před vnucením cizího slovníku. Mezi Core a Supporting BC ve stejné organizaci se obvykle objevuje Customer-Supplier. A když se s Generic nedá vyjednávat (typicky daňový státní systém), zbývá Conformist: přejmete jejich slovník takový, jaký je.
:::

## 02.06 Subdomény v Symfony – co to znamená pro strukturu projektu {#symfony-implications}

Symfony 8 dává plnou volnost v adresářové struktuře pod `src/`. Výchozí dělení `src/Controller/`, `src/Entity/`, `src/Repository/` je *technické*: řadí kód podle vrstev. Pro DDD aplikaci je to chyba: ztratíte schopnost na první pohled poznat, do které subdomény funkcionalita patří. Junior, který hledá „jak se počítá cena“, musí projít všechny tři adresáře. Lepší cesta: **strukturovat `src/` primárně podle subdomén, sekundárně podle vrstev uvnitř subdomény**.

Konkrétní rozložení v Symfony 8 e-shopu:

:::code{language="text" filename="src/ (struktura projektu)"}
src/
├── Core/
│   ├── Pricing/                       ← plný DDD: Aggregate, VO, Domain Event
│   │   ├── Domain/
│   │   │   ├── Aggregate/Pricelist.php
│   │   │   ├── ValueObject/Money.php
│   │   │   ├── ValueObject/PriceRule.php
│   │   │   ├── Event/PricelistChanged.php
│   │   │   └── Repository/PricelistRepository.php          (interface)
│   │   ├── Application/
│   │   │   ├── Command/UpdatePriceCommand.php
│   │   │   └── Handler/UpdatePriceHandler.php
│   │   └── Infrastructure/
│   │       ├── Doctrine/DoctrinePricelistRepository.php
│   │       └── Symfony/PricingMessageHandler.php
│   └── Recommendations/               ← analogická struktura
│
├── Supporting/
│   ├── Ordering/                      ← lehký DDD: minimal Aggregate, Doctrine ORM
│   │   ├── Domain/
│   │   │   ├── Order.php                                   (Doctrine entity + chování)
│   │   │   └── OrderRepository.php                         (interface)
│   │   ├── Application/
│   │   │   └── PlaceOrderHandler.php
│   │   └── Infrastructure/
│   │       └── Repository/DoctrineOrderRepository.php
│   ├── Inventory/
│   └── Reporting/
│
└── Generic/
    ├── Auth/                          ← bridge na Auth0, žádný custom Aggregate
    │   ├── Adapter/Auth0Client.php
    │   ├── Adapter/Auth0UserProvider.php
    │   └── Adapter/Auth0AuthenticationListener.php
    ├── Mail/
    │   └── Adapter/SesMailerAdapter.php
    └── Payment/
        └── Adapter/StripeWebhookHandler.php
:::

Strukturální rozdíl odráží rozdíl strategický: **Core má tři vrstvy (Domain / Application / Infrastructure), Supporting také tři, ale tenčí, a Generic jen jednu – Adapter**. Junior, který se rozhodne přidat `SomeBusinessRule.php` do `src/Generic/Auth/`, narazí na chybějící `Domain/` adresář a dostane signál, že kód tam nepatří. Naopak Aggregate v `src/Core/Pricing/Domain/` má kolem sebe celou doménovou infrastrukturu a tým u něj pracuje s invarianty do hloubky.

Tato struktura má ovšem cenu, kterou je poctivé přiznat. Sekce 02.08 tvrdí, že klasifikace stárne a re-evaluuje se každý rok až dva. Jenže překlasifikace Pricing ze Core na Supporting v této struktuře znamená přejmenovat namespace napříč celým projektem – tedy přesně tu změnu, kterou tým odloží. Klasifikace zapsaná do cesty je pedagogicky nejsilnější a provozně nejkřehčí varianta.

Druhá varianta dává na první úroveň doménové jméno a klasifikaci nechává v dokumentaci:

:::code{language="text" filename="src/ (varianta podle subdomény)"}
src/
├── Pricing/          ← Core (viz docs/domain/pricing.md)
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
├── Ordering/         ← Supporting
├── Inventory/        ← Supporting
└── Auth/             ← Generic, jen Adapter/
:::

Matthias Noback doporučuje právě ji: na první úrovni Bounded Context nebo subdoména, uvnitř vrstvy. Přeřazení Pricing mezi Supporting pak znamená smazat pár tříd, ne přepsat `use` v celém projektu. Cenu za to zaplatíte jinde. Ze stromu adresářů už nikdo klasifikaci nevyčte, takže musí žít v Core Domain Chartu a v Domain Vision Statementech. **Kniha dál používá právě tuhle druhou variantu**: od kapitoly o základních konceptech je všude `App\Ordering\`, `App\UserManagement\` a podobně, tedy kontext na první úrovni. Rozdělení podle klasifikace ukazuje tahle kapitola proto, že zviditelňuje své téma – jako kostru projektu ho ale nepřebírejte, rozešlo by se se zbytkem knihy.

Aby autoload fungoval, musí `composer.json` deklarovat odpovídající PSR-4 mapování:

:::code{language="json" filename="composer.json (varianta podle klasifikace, kniha ji dál nepoužívá)"}
{
    "name": "acme/eshop",
    "type": "project",
    "require": {
        "php": ">=8.4",
        "symfony/framework-bundle": "^8.0",
        "doctrine/orm": "^3.0",
        "symfony/messenger": "^8.0"
    },
    "autoload": {
        "psr-4": {
            "App\\Core\\": "src/Core/",
            "App\\Supporting\\": "src/Supporting/",
            "App\\Generic\\": "src/Generic/",
            "App\\SharedKernel\\": "src/SharedKernel/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Tests\\": "tests/"
        }
    }
}
:::

Namespace `App\SharedKernel\` slouží na opravdu sdílené primitivy: základní třídu `AggregateRoot`, `DomainEvent` a obecné identifikátory. Používají se napříč subdoménami a nepatří do žádné z nich. Kniha ho takto používá i v dalších kapitolách. Shared kernel má ovšem sklon rozrůstat se do anti-vzoru „všechno je sdílené“; rizika a pravidla rozebírá [sekce o Shared Kernelu](/context-mapping#shared-kernel) v kapitole o Context Mappingu.

V `config/services.yaml` pak obvykle stojí každá subdoména jako vlastní `resource` blok. DI definice tím zůstanou izolované na úrovni subdomény:

:::code{language="yaml" filename="config/services.yaml (výřez: struktura podle klasifikace)"}
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Core subdomény: explicitní bind interfaces
    App\Core\Pricing\:
        resource: '../src/Core/Pricing/'
        exclude:
            - '../src/Core/Pricing/Domain/Event/'
            - '../src/Core/Pricing/Domain/ValueObject/'

    App\Core\Pricing\Domain\Repository\PricelistRepository:
        alias: App\Core\Pricing\Infrastructure\Doctrine\DoctrinePricelistRepository

    # Supporting: standardní autowire
    App\Supporting\:
        resource: '../src/Supporting/'

    # Generic: jen adaptery, žádné doménové třídy
    App\Generic\:
        resource: '../src/Generic/'
:::

:::callout{type="pattern"}
### Adresářová struktura jako strategický nástroj {#forced-strategy-heading}

Tato struktura není kosmetická – **vynucuje strategické rozhodnutí**. Jakmile máte `src/Core/` a `src/Supporting/` jako oddělené namespacy, každý pull request odpovídá na otázku, do které kategorie nová funkcionalita patří. Strategie se zhmotnila v adresáři.

Spoléhat přitom na samotné code review je slabé. Hranice mezi subdoménami se v PHP vynutí strojově a v CI:

- **Deptrac** definuje vrstvy nad třídami a povolené závislosti mezi nimi. Pravidlo „`Core` nesmí záviset na `Supporting` ani na `Generic`“ je pár řádků v `deptrac.yaml` a poruší-li ho někdo, build spadne. Bounded contexty uvnitř jednoho projektu jsou obvyklé komunitní použití; oficiální dokumentace je jmenovitě neuvádí.
- **PHPArkitect** píše totéž jako PHP kód, který se spouští stejně jako testy. Vhodné tam, kde tým nechce další konfigurační formát.

Použijte jeden z nich, jakmile struktura přežije první čtvrtletí. Do té doby se hranice ještě posouvají a strojové pravidlo by jen překáželo.

Související: implementační detail uvnitř jedné subdomény rozebírá [kapitola o implementaci v Symfony](/implementace-v-symfony); volbu architektonického stylu podle typu subdomény rozvádí [kapitola o architektonických stylech](/architektonicke-styly).
:::

Příklad konkrétního Aggregate v Core subdoméně, který demonstruje očekávanou hloubku modelování:

:::code{language="php" filename="src/Core/Pricing/Domain/Aggregate/Pricelist.php"}
<?php

declare(strict_types=1);

namespace App\Core\Pricing\Domain\Aggregate;

use App\Core\Pricing\Domain\Event\PricelistChanged;
use App\Core\Pricing\Domain\Event\PricelistCreated;
use App\Core\Pricing\Domain\Exception\ConflictingPriceRuleException;
use App\Core\Pricing\Domain\ValueObject\Money;
use App\Core\Pricing\Domain\ValueObject\PriceRule;
use App\SharedKernel\Domain\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Pricelist extends AggregateRoot
{
    /** @var list<PriceRule> */
    private array $rules = [];

    private function __construct(
        private readonly Uuid $id,
        private readonly string $name,
    ) {
    }

    public static function create(string $name): self
    {
        $pricelist = new self(Uuid::v7(), $name);
        $pricelist->record(new PricelistCreated($pricelist->id, $name));

        return $pricelist;
    }

    public function applyRule(PriceRule $rule): void
    {
        if ($this->hasConflictingRule($rule)) {
            throw ConflictingPriceRuleException::forCode($rule->code);
        }

        $this->rules[] = $rule;
        $this->record(new PricelistChanged($this->id, $rule));
    }

    public function priceFor(Money $listPrice, array $context): Money
    {
        $price = $listPrice;
        foreach ($this->rules as $rule) {
            if ($rule->matches($context)) {
                $price = $rule->apply($price);
            }
        }
        return $price;
    }

    private function hasConflictingRule(PriceRule $candidate): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->conflictsWith($candidate)) {
                return true;
            }
        }
        return false;
    }
}
:::

Pro srovnání: ekvivalent v **Supporting subdoméně** (Order Management) je podstatně lehčí: Doctrine entita s pár metodami a bez separátních Value Objektů, protože invarianty jsou triviální. Jde vědomě o tentýž `Order`, který [kapitola o návrhu agregátů](/navrh-agregatu) modeluje jako plný Aggregate s `Order::place()`, `CustomerId` a `Money`. Rozdíl mezi oběma ukázkami není v tom, že by jedna byla správná; rozdíl je v klasifikaci. Objednávky jsou v e-shopu s vlastním pricing enginem Supporting, v logistické firmě jsou Core – a model se tomu přizpůsobí. Ve stejném duchu je i holá `\DomainException` níže přiznanou zkratkou: pojmenované výjimky patří tam, kde na nich někdo staví reakci, ne do třístavového workflow.

:::code{language="php" filename="src/Supporting/Ordering/Domain/Order.php"}
<?php

declare(strict_types=1);

namespace App\Supporting\Ordering\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: "orders")]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid")]
    private Uuid $id;

    #[ORM\Column(length: 32)]
    private string $status = "pending";

    #[ORM\Column(type: "decimal", precision: 12, scale: 2)]
    private string $total;

    public function __construct(Uuid $id, string $total)
    {
        $this->id = $id;
        $this->total = $total;
    }

    public function confirm(): void
    {
        if ($this->status !== "pending") {
            throw new \DomainException("Order is not pending.");
        }
        $this->status = "confirmed";
    }

    public function cancel(): void
    {
        if ($this->status === "shipped") {
            throw new \DomainException("Cannot cancel shipped order.");
        }
        $this->status = "cancelled";
    }

    public function getId(): Uuid { return $this->id; }
    public function getStatus(): string { return $this->status; }
    public function getTotal(): string { return $this->total; }
}
:::

Jeden detail, na kterém ukázka bez konfigurace spadne: Doctrine typ `uuid` není součástí ORM 3. Dodává ho most na `symfony/uid` a musíte ho zaregistrovat, jinak mapování `#[ORM\Column(type: "uuid")]` skončí výjimkou o neznámém typu.

:::code{language="yaml" filename="config/packages/doctrine.yaml (výřez: mapping podle kontextů)"}
doctrine:
    dbal:
        types:
            uuid: Symfony\Bridge\Doctrine\Types\UuidType
:::

A v **Generic subdoméně** (Auth0 integrace) není entita ani Aggregate. Při troše štěstí není ani vlastní adaptér: balíček `auth0/symfony` dodává hotový `Auth0\Symfony\Security\UserProvider` a authenticator, takže celá subdoména se smrskne na konfiguraci. Přesně tak má Generic vypadat.

:::code{language="yaml" filename="config/packages/security.yaml (výřez: oddělený firewall)"}
security:
    providers:
        auth0_provider:
            id: Auth0\Symfony\Security\UserProvider

    firewalls:
        main:
            provider: auth0_provider
            custom_authenticators:
                - auth0.authenticator
:::

Vlastní `UserProvider` píšete až tehdy, když hotový nestačí. Typicky proto, že profil z Auth0 potřebujete obohatit o interní data. I pak zůstane u tenkého adaptéru, který implementuje rozhraní ze Symfony Security a nic nemodeluje:

:::code{language="php" filename="src/Generic/Auth/Adapter/Auth0UserProvider.php"}
<?php

declare(strict_types=1);

namespace App\Generic\Auth\Adapter;

use Auth0\SDK\Auth0;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class Auth0UserProvider implements UserProviderInterface
{
    public function __construct(private readonly Auth0 $auth0)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $response = $this->auth0->management()->users()->get($identifier);
        if ($response->getStatusCode() === 404) {
            throw new UserNotFoundException();
        }
        $profile = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return new Auth0User($profile);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === Auth0User::class;
    }
}
:::

Třikrát jde o tentýž typ úlohy, o práci s doménovým objektem. Objem kódu je pokaždé radikálně jiný. To je strategická investice.

## 02.07 Subdomény a sourcing strategie (build / buy / partner) {#sourcing}

Klasifikace subdomén nemá smysl, pokud z ní neplynou rozhodnutí. Přímé mapování klasifikace na sourcing strategii (kdo a jak ten kód napíše) je následující:

| Klasifikace | Doporučená strategie | Tým | Příklady |
|---|---|---|---|
| Core | **BUILD in-house** (vlastní IP, plná kontrola) | Senior + doménový expert | Pricing engine, Recommendation, Risk scoring |
| Supporting | **BUILD lehce** nebo **BUY hotové řešení**, pokud existuje s ≥80% pokrytím | Medior, junior pod dohledem | Order mgmt (build), Helpdesk (buy – Zendesk) |
| Generic | **BUY / RENT / OPEN-SOURCE** | Junior, integrátor | Auth (Auth0/Keycloak), Email (SES), Payments (Stripe) |

Klasifikace je relativní k obchodnímu modelu, ne absolutní. Co je pro vás Generic, je pro vendora jeho Core, jak ukázaly příklady Mailgunu a autentizace výše.

U Generic subdomény má „koupit“ čtyři podoby, které Evans rozlišil už v roce 2003 a které se v praxi pletou do jedné [[1]](https://www.domainlanguage.com/ddd/):

1. **Hotové řešení.** Nejrychlejší cesta, ale platí se integrací a tím, že cizí model vnutí slovník. Evansův verdikt zní opatrně: obvykle se to nevyplatí, prozkoumat to ale stojí za to.
2. **Publikovaný design nebo model.** Cizí je návrh, kód je váš. Evans odkazuje na Fowlerovy *Analysis Patterns*; dnešní ekvivalent jsou standardy typu OpenID Connect nebo ISO 20022.
3. **Outsourcovaná implementace.** Zadání ven, integrace a údržba doma. Šetří kapacitu seniorního týmu, přidává komunikační režii a náklad na code review.
4. **Vlastní implementace.** Dostanete přesně to, co chcete, nic navíc, a integrace odpadá. Cenou je údržba navždy.

Sourcing podle strategické hodnoty ostatně není objev DDD. Niel Nickolaisen popsal v *Stand Back and Deliver* (2009) **Purpose Alignment Model** se dvěma osami, mission critical a market differentiating [[8]](https://insideproduct.co/purpose-based-alignment-model/). Jeho čtyři kvadranty mapují na tabulku výše skoro doslova. *Differentiating* (excelovat) odpovídá Core, *Parity* (zjednodušit a standardizovat) pokrývá Supporting i Generic. *Partner* je varianta popsaná níže a *Who cares* je práce, kterou má tým dělat co nejlevněji, nebo vůbec.

Praktický důsledek pro rozhodování o nákupu je jedna otázka před podpisem SaaS smlouvy: *„kupujeme Generic, nebo si snižujeme Core?“* Pokud SaaS pokryje Generic, je to čistý zisk: ušetříme čas, koupíme zkušenosti vendora, soustředíme se na Core. Pokud by SaaS pokryl Core, je to strategický ústup: odevzdáváme konkurenční výhodu třetí straně. Stejné rozhodnutí, ale opačné znaménko.

Třetí variantou sourcingu je **partnerství**. Hodí se pro Supporting subdomény, kde hotové řešení existuje, ale potřebujete větší míru přizpůsobení, než dovolí standardní SaaS. Příklad: e-shop integruje fakturaci přes API jiné fintech firmy, která za měsíční poplatek počítá daně pro 30 jurisdikcí. Není to BUY (žádná krabice), není to BUILD (cizí tým), je to partnerství s rizikem dlouhodobé závislosti. Vyžaduje smluvní jistoty (vlastnictví dat, exit clause, SLA) a Anti-Corruption Layer na hranici.

:::callout{type="note"}
### Vendor lock-in je daň za Generic, ne za Core {#vendor-lockin-heading}

Tým, který se rozhoduje pro Generic SaaS, často namítá: *„ale co když nás vendor zdraží nebo skončí?“* Odpověď: vendor lock-in v Generic subdoméně je daň, kterou za to platíte. Nezpochybňujte ji, kupte si ji rozumně. Konkrétně:

- U Generic můžete vyměnit jeden SaaS za druhý za 1–2 sprinty (ACL je tenký).
- U Core nelze vyměnit „SaaS za SaaS“, protože tam žádný SaaS nepatří.
- Nesmyslné je dělat plný DDD model jen proto, abychom „mohli vyměnit databázi“. Databáze se nemění, vendor SaaS se mění.

Pravidlo: chraňte se před lock-inem v Core (vlastní IP, vlastní data), nikoliv v Generic (kde lock-in je naopak smysluplným kompromisem).
:::

## 02.08 Evoluce subdomén v čase {#evoluce}

Klasifikace subdomén není jednorázové cvičení. Trh i technologie se za pár let posunou natolik, že to, co bylo Core před třemi lety, může být dnes Supporting. Khononov tomu věnuje samostatnou sekci v *Learning DDD*: popisuje, jak k posunu typu subdomény dochází, jak ho poznat a jak na něj reagovat [[3]](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/). Formulace, že **opomenutá re-evaluace stojí stejně jako špatná první klasifikace**, je už závěr této knihy.

Posuny mají svá jména. Nick Tune je sepsal jako *Core Domain Patterns* a jeho slovník poslouží lépe než tři statické kategorie [[6]](https://nicktune.substack.com/p/core-domain-patterns-941f89446af5). Bývalou inovaci, která dnes neodlišuje, ale pořád ji potřebujete, označuje jako *Table Stakes / Former Core*. Jádro, ze kterého se stala schopnost dostupná komukoliv, nazývá *Commoditised Core*. Opačný pohyb dostal jméno *Black Swan Core*: stane se něco nečekaného a zdánlivá komodita se přes noc promění v jádro. A *Hidden Core* je ta, kterou tým přehlédl právě proto, že vypadá nenápadně: vysoké odlišení při nízké složitosti.

Mechanismus za těmito posuny popsala Susanne Kaiser napojením klasifikace na Wardleyho evoluční osu genesis → custom-built → product → commodity [[9]](https://www.informit.com/articles/article.aspx?p=3222355&seqNum=3). Core začíná v genesis nebo custom-built, Supporting bývá custom-built či product, Generic sedí v product až commodity. Pohyb po ose jde jedním směrem a rovnou předepisuje metodu: co je v genesis, se staví doma; co dorazilo do product, se kupuje; co je commodity, se pronajímá. Tři posuny níže jsou tři různá místa na téže ose.

### Z Generic do Core – komodita se stane diferenciátorem {#shift-generic-to-core}

Příklad: **online platby v roce 2010 byly pro většinu firem Generic**. Koupíte si bránu, integrujete, hotovo. Pro Stripe, který tehdy začínal, to byl ale Core: investovali do API, do podpory pro vývojáře, do globálního pokrytí. Dnes je Stripe víceméně oborový standard a jádro jeho byznysu zůstává u plateb, jen se posunula laťka (fraud detection, tax compliance, finanční produkty pro startupy). Pokud vaše firma identifikuje, že se v určité dosud-Generic oblasti dá hrát o trh, je namístě ji posunout do Core a zvýšit investici. Riziko: pokud se mýlíte, utratíte peníze v subdoméně, kterou trh vůbec neoceňuje.

### Z Core do Supporting – komoditizace {#shift-core-to-supporting}

Příklad: **cloud storage**. Dropbox v roce 2008 měl Core v synchronizaci souborů. Byl to nepříjemný problém s race conditions, latencemi a binární diff propagací, který tehdy nikdo neřešil dobře. Dnes je „cloud storage“ komoditizován cloud providerem (AWS S3, Azure Blob, GCS) a Dropbox musel posunout Core jinam, do produktivních nástrojů (Paper, integrace), aby zůstal odlišený. Jakmile je Core Doména dostupná jako služba u tří velkých vendorů, je čas snížit investici, refaktorovat model na lehčí a hledat nový diferenciátor.

### Ze Supporting do Generic – když dorazí kvalitní SaaS {#shift-supporting-to-generic}

Příklad: **helpdesk / ticketing**. V roce 2005 většina středních firem implementovala vlastní helpdesk modul, tedy Supporting subdoménu. Dnes je Zendesk / Freshdesk / Intercom dost dobrý, aby pokryl 90 % požadavků, a vlastní implementace je nesmyslná. Subdoména se posunula z Supporting do Generic, a tým, který ji nadále udržuje sám, plýtvá rozpočtem.

### Kam hranici Generic posunuly jazykové modely {#shift-llm}

Poslední posun je čerstvý a stojí za samostatnou zmínku, protože zasáhl celou třídu úloh naráz. Klasifikace textu, extrakce dat z nestrukturovaných dokumentů, sumarizace, jazyková normalizace vstupů: ještě v roce 2020 to byly Supporting subdomény, na kterých seděl vlastní tým s vlastním modelem. Dnes jde o volání API. Ekonomika rozhodnutí se otočila. Kde dřív vedla jediná cesta přes vlastní implementaci, existuje teď hotové řešení. Jeho kvalita se rok od roku mění rychleji, než stihnete napsat vlastní.

Tvrdá čísla k tomuto posunu žádný primární DDD zdroj zatím nenabízí, takže s ním pracujte jako s pozorováním, ne jako s doloženým trendem. Praktický dopad je ale jednoznačný: subdomény, kolem kterých se točí zpracování textu, patří do nejbližšího auditu jako první. Souvislosti rozvádí [kapitola o DDD a umělé inteligenci](/ddd-a-umela-inteligence).

Praktická obrana proti zastarávání klasifikace:

1. Naplánujte **strategický audit subdomén každých 12–18 měsíců**. Workshop na půl dne s product managementem a architekty.
2. Po každém auditním cyklu projděte pětibodový test (sekce 02.03) na všech subdoménách znovu – i na těch, kterými „jste si jistí“.
3. Srovnávejte s konkurencí: pokud váš největší konkurent neutralizuje vaši Core subdoménu (např. tím, že koupil hotové řešení, které je 80 % vašeho rozsahu), je to varovný signál.
4. Sledujte SaaS landscape v Generic subdoménách – co bylo loni „kupte si“, může být letos „má to každý a stojí to dvacetinu“.

:::callout{type="note"}
### Strategický audit není událost, je to proces {#audit-not-event-heading}

Strategický audit subdomén nesmí skončit jako jednorázový workshop s PowerPointem na SharePointu. Cílem auditu je **zaktualizovat investiční prioritu na další 12–18 měsíců**: kde porostou týmy, kde se bude škrtat, co se bude outsourcovat. Bez tohoto výstupu je workshop nákladnou ztrátou času.

Tip: výstup auditu zveřejněte celému inženýrskému týmu (alespoň formou jednostránkového shrnutí „co je nově Core a proč“). Tým, který nezná aktuální klasifikaci, neumí prioritizovat.
:::

## 02.09 Praktický postup – krok za krokem {#postup}

Pětikrokový postup pro první klasifikaci subdomén vlastního produktu. Doporučená délka workshopu: půl dne, 5–8 účastníků (architekt, tech-lead, product manager, doménový expert, případně CTO / VP product).

1. **Vypsat všechny capability / use-case.**

   Použijte obchodní slovník, ne IT žargon. Příklady: „objednat zboží“, „sledovat zásilku“, „získat doporučení produktu“, „přihlásit se“, „obdržet účtenku e-mailem“, „reklamovat“. Cíl: 20–40 položek u středně velkého produktu. Pokud máte víc, agregujte. Pokud méně, buďte ostražití, pravděpodobně vám něco uniklo.

   Brainstorm nad prázdnou tabulí je nejhorší způsob, jak k seznamu dojít. Komunitní praxe (DDD Starter Modelling Process) klasifikaci zařazuje až za [Big Picture EventStorming](/event-storming#big-picture): capability se odečtou z časové osy událostí, kterou tým právě nakreslil. Kde hranice zůstávají sporné, pomůže sada *Independent Service Heuristics* od autorů Team Topologies. Jsou to otázky typu „dala by se tato část provozovat jako samostatný SaaS produkt s vlastní cenovkou?“, kterým rozumí i netechnický účastník workshopu.

2. **U každé položky odpovědět na pětibodový test (sekce 02.03).**

   Tabulka 6 sloupců: capability + 5 otázek. Každý účastník odpovídá nezávisle (anonymně, Post-it nebo Miro), pak shrnete medián. Pozor na syndrom hrdiny. Pokud se týmový advokát konkrétní oblasti účastní hlasování, přiřaďte jeho hlasu nižší váhu. Jinak hlasování ratifikuje stávající advokacii místo toho, aby ji testovalo.

3. **Seskupit do subdomén.**

   Capability, které sdílejí obchodní slovník a klasifikaci, patří do jedné subdomény. Příklad: „nabídnout slevu“, „aplikovat kupon“, „spočítat finální cenu“ → subdoména Pricing. Orientační počet subdomén, opět pravidlo palce bez opory v primárním zdroji: 8–15 u středního produktu, 20–40 u velkého enterprise systému. Pokud jich máte 5, je to podezřele málo (Supporting subdomény se typicky schovávají uvnitř Core); pokud 60, je to moc (typicky neagregujete capability).

4. **Pro každou subdoménu rozhodnout sourcing.**

   Použijte tabulku ze sekce 02.07. U Core potvrďte BUILD; u Generic porovnejte 2–3 SaaS varianty (cena, vendor stability, ACL složitost); u Supporting rozhodněte BUILD vs. BUY. Výstupem kroku je seznam vybraných vendorů a interních týmů, které dostávají rozpočet.

5. **Zapsat do Domain Vision Statement (1 stránka A4) – kdo, co, proč, kdy.**

   Pro každou Core subdoménu vytvořte jednostránkový dokument (markdown nebo Notion / Confluence). Pro Supporting stačí jedna věta v interním wiki. Pro Generic stačí poznámka „kupujeme X od vendora Y, smlouva platí do Z“. Tento dokument je jediný legitimní výstup workshopu. Bez něj se klasifikace ztratí.

Postup výše počítá s tím, že hranice teprve vznikají. V existujícím monolitu, kde je pricing rozprostřený přes čtyřicet tříd a tři kontrolery, krok 3 selže: capability se nedají seskupit podle kódu, protože kód žádné skupiny nemá. Klasifikujte proto podle obchodních schopností a teprve pak hledejte, kudy jimi vede řez v existujícím kódu. Core Domain Charts na to mají variantu *Architecture Migration*, která do grafu kreslí současný a cílový stav. Postup extrakce popisuje [kapitola o migraci z CRUD](/migrace-z-crud).

### Šablona Domain Vision Statementu {#dvs-template}

Domain Vision Statement (DVS) je krátký dokument, který pro Core subdoménu definuje *co, proč, kdo, kdy*. Inspirovaný Evansovou kapitolou „Distillation“, ale zkrácený do agilního formátu o 15–20 řádcích markdownu:

:::code{language="markdown" filename="docs/domain/pricing.md"}
# Pricing – Core Domain

## What
Dynamický pricing s personalizovanými promo kódy.
Vstupy: zákaznický segment, historie nákupů, sklad, čas dne.
Výstup: cena pro konkrétního zákazníka v konkrétním kontextu.

## Why core
Konkurenti používají statický pricing nebo banální slevové kódy.
Náš dynamic pricing engine generuje +18 % marže oproti statickému (A/B test 2026 Q1).
Bez něj jsme jen další e-shop.

## Investment
Tým: 3 senior PHP devs + 1 data scientist + product owner na full-time.
Stack: vlastní engine v PHP 8.4 / Symfony 8, žádné SaaS v jádru.
Persistence: PostgreSQL (rule store) + Redis (cache).
ML model: Python sidecar service, gRPC API.

## Bounded contexts
- Catalog BC (read model – indikativní cena)
- Checkout BC (write model – finální cena, validace, invariants)
- Recommendation BC (cross-context, čte z Pricing eventy)

## Off-limits
Žádný outsourcing. Žádné low-code platformy.
Žádný junior bez code review od tech-leadu.
Žádné rozhodnutí o pricing logice bez VP product.

## KPI
- Marže (cíl +20 % oproti baseline)
- Latence ceny < 50 ms p99
- Konzistence cena katalog vs. checkout < 0.1 %

## Re-evaluace
Každých 12 měsíců – pokud konkurence dorovná, posuneme do Supporting.
:::

DVS má být **živý dokument**: aktualizujte ho, kdykoliv se mění strategie, vendor, tým nebo KPI. Pokud DVS půl roku nikdo neaktualizoval a Core Doména pořád existuje, něco je špatně. Buď se nic neděje (a pak možná není Core), nebo se nikdo neobtěžoval dokument udržovat (a pak ho nikdo nečte).

:::callout{type="pattern"}
### DVS není funkční specifikace {#dvs-not-spec-heading}

Domain Vision Statement **nepopisuje, jak co implementovat**. Není to user story, není to API kontrakt, není to schéma databáze. Je to *strategický kompas*. Odpovídá na otázku „proč na tom tým pracuje a kolik to stojí“. Pokud váš DVS narostl na 5 stránek, je to už něco jiného (možná RFC nebo design doc) a není to DVS.

Kontrolní otázka: porozumí DVS za 3 minuty čtení i člověk, který není ve vývoji? Pokud ne, je moc dlouhý.
:::

## 02.10 Shrnutí {#summary}

Bez subdoménové klasifikace nemá smysl řešit Bounded Contexts, Aggregaty ani Doctrine mapping. Modelujete naslepo a rozpočet se rozplývá rovnoměrně po nedůležitých částech aplikace.

Hlavní pravidla na zapamatování:

1. **Core Domain je vzácné, ne jediné** – žádné číselné pravidlo neexistuje, firma soutěžící na několika osách může mít Core subdomén víc. Platí ale, že Core je z podstaty složité a mění se; jednoduchá subdoména dá jen krátkodobou výhodu. Sem směřuje většina modelovacího úsilí, senior tým a vlastní IP.
2. **Supporting subdomén je většina** – řádově 50–60 % vývojové kapacity, stejná jednotka jako v [sekci 02.04](#vsechno-core-antipattern) (pravidlo palce, ne měřená data). Lehký DDD nebo Doctrine ORM CRUD, mediorní tým, ochota použít hotová řešení, kde dávají smysl. Cíl: fungovat spolehlivě s minimální údržbou. Vysoká složitost ve Supporting je signál k re-klasifikaci.
3. **Generic se výchozím rozhodnutím kupuje** – autentizace, e-maily, platby, fulltext. Vlastní implementaci obhájíte jen tehdy, když integrační náklad převýší ten implementační. Tenký Anti-Corruption Layer chrání naše modely před cizím slovníkem.
4. **Mapování subdomén na Bounded Contexty není automaticky 1:1** – ideál je 1:1, časté je N:1 u drobných Supporting a Generic. Rozdělit souvislou funkcionalitu do víc kontextů se vyplatí jen z provozních důvodů (oddělené vývojové cykly, nezávislé škálování). Subdoména je obchodní hranice, BC je implementační hranice; nezaměňujte je.
5. **Klasifikace stárne** – re-evaluujte každých 12–18 měsíců. Generic se může stát Core (Stripe), Core se může stát Supporting (cloud storage), Supporting se může stát Generic (helpdesk). Tým, který nemá aktuální klasifikaci, neumí prioritizovat.

Subdoménová klasifikace slouží k rozhodování o investici, ne k estetickému dělení kódu. Kapitola splní účel, jakmile z ní vznikne konkrétní seznam subdomén vlastního produktu a u každé z nich rozhodnutí o sourcing strategii. Pouhý dojem „takto by se to dalo kategorizovat“ znamená, že kapitola zůstala teorií – projděte ji znovu s konkrétním projektem v ruce.

:::faq{}
- question: Jaký je rozdíl mezi subdoménou a Bounded Contextem?
  answer: 'Subdoména je <strong>obchodní</strong> hranice, tedy kus problému, který se v organizaci řeší jako jedna kapitola. Existovala obvykle dříve, než vznikl IT systém („prodej“, „logistika“, „personalistika“). Bounded Context je <strong>implementační</strong> hranice: místo, kde platí jeden Ubiquitous Language a jeden konzistentní model, typicky jeden tým a jeden deployment. Vztah nemusí být 1:1: jedna subdoména může být rozdělena do více BC, nebo více subdomén může žít v jednom BC (typické pro drobné Supporting a Generic). Detail v <a href="#subdomeny-na-bc">sekci 02.05 Mapování subdomén na BC</a>.'
- question: Můžu změnit klasifikaci subdomény v průběhu života produktu?
  answer: 'Ano. Klasifikace stárne a re-evaluace každých 12–18 měsíců je nutnou součástí strategického DDD. Typické posuny: Generic se stává Core (online platby pro Stripe v roce 2010), Core se stává Supporting (cloud storage pro Dropbox po nástupu S3), Supporting se stává Generic (helpdesk po nástupu Zendesk). Re-klasifikace má praktický důsledek: jiná investice, jiný tým, jiná sourcing strategie. Detail v <a href="#evoluce">sekci 02.08 Evoluce subdomén v čase</a>.'
- question: Jak poznám, že je subdoména Generic?
  answer: 'Generic subdoména je komoditizovaná: řešení existuje roky, prodává se jako SaaS, knihovna nebo open-source a tržní standard určuje, jak má vypadat. Typické příklady vedle autentizace: generování PDF faktur (hotové knihovny a fakturační služby) a rozesílání transakčních e-mailů (SMTP je standardizovaný protokol, doručitelnost řeší vendor). Od Supporting ji odlišuje složitost: Generic je složitý, ale už vyřešený problém, který se nemění, zatímco Supporting bývá jednoduchý. Výchozím rozhodnutím je nákup plus tenký Anti-Corruption Layer na hranici; vlastní implementaci obhájíte jen tehdy, když integrace vyjde dráž než napsání. Detail v <a href="#tri-osy">sekci 02.02 Tři osy, ne jedna</a>.'
- question: Kolik subdomén je „normální“ počet?
  answer: 'Orientačně u středního produktu <strong>8–15 subdomén</strong>, u velkého enterprise systému 20–40. Jde o pravidla palce této knihy, ne o měřená data. Pokud máte méně než 8, typicky se Supporting subdomény schovávají uvnitř Core; pokud máte víc než 40, neagregujete capability dostatečně a pracujete v příliš jemné granularitě. Rozdělení vývojové kapacity vychází přibližně na 20–30 % Core, 50–60 % Supporting a 10–20 % Generic. Pět a víc Core subdomén není samo o sobě chyba, protože firma soutěžící na několika osách jich může mít víc. Zaslouží si ale kontrolní otázku: čím konkrétně se každá z nich odlišuje na trhu?'
- question: Co když vyjde, že nemáme žádnou Core Domain?
  answer: 'Je to legitimní výsledek a často signál, že <strong>plné DDD nestojí za náklady</strong>. Pokud po pětibodovém testu (sekce 02.03) nezůstane ani jedna subdoména s 3+ ANO, váš produkt je zřejmě „lepší CRUD“: kombinace komoditizovaných řešení (Generic) a interní administrativy (Supporting) bez skutečného diferenciátoru. V takovém případě zvažte CRUD architekturu se servisní vrstvou, anemic model a Doctrine ORM; investice do plného taktického DDD by se nevrátila. Detailní rozbor v kapitole <a href="/kdy-nepouzivat-ddd">Kdy DDD nepoužívat</a>.'
- question: Musí každá Core subdoména mít vlastní Bounded Context?
  answer: 'Cílem je 1:1. Rozdělení souvislé funkcionality do víc kontextů je spíš varovný signál: tatáž změna požadavků pak zasáhne oba kontexty a vynutí si současné nasazení. Legitimní důvody k rozdělení jsou provozní: potřeba oddělit vývojové cykly komponent nebo škálovat část nezávisle na zbytku. Příklad z e-shopu: Pricing (Core) žije v Catalog BC i v Checkout BC, protože listing a checkout mají odlišné výkonnostní nároky a nasazují se různým tempem. Bez takového důvodu subdoménu nedělte.'
:::

## 02.11 Další četba {#further-reading}

Pro další studium strategického DDD a klasifikace subdomén poslouží následující zdroje:

- [Domain Language](https://www.domainlanguage.com/ddd/) – oficiální stránky Erica Evanse, kde najdete *DDD Reference* (zdarma) shrnující strategické vzory včetně Core Domain a Generic Subdomains.
- [Implementing Domain-Driven Design](https://kalele.io/books/) – Vaughn Vernon, kapitola 2 „Domains, Subdomains, and Bounded Contexts“ je referenční čtení pro tuto kapitolu.
- [Learning Domain-Driven Design](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/) – Vlad Khononov (O'Reilly 2021), kapitola 1 „Analyzing Business Domains“ je nejaktuálnější výklad subdoménové klasifikace.
- [EventStorming](https://www.eventstorming.com/) – Alberto Brandolini, workshopová technika pro identifikaci subdomén ve velkém měřítku (Big Picture EventStorming).
- [Martin Fowler – Bounded Context](https://martinfowler.com/bliki/BoundedContext.html) – krátká, ale výstižná definice BC, která pomáhá odlišit ho od subdomény.
- [Core Domain Charts (DDD Crew)](https://github.com/ddd-crew/core-domain-charts) – open-source šablony pro vizualizaci subdoménové klasifikace, včetně katalogu otázek k oběma osám a varianty Architecture Migration.
- [Core Domain Patterns](https://nicktune.substack.com/p/core-domain-patterns-941f89446af5) – Nick Tune, slovník osmi vzorů (Decisive Core, Commoditised Core, Black Swan Core, Suspect Supporting a další) pro popis posunů v čase.
- [Revisiting the Basics of DDD](https://vladikk.com/2018/01/26/revisiting-the-basics-of-ddd/) – Vlad Khononov, kratší předloha první kapitoly *Learning DDD*: tři osy klasifikace a otázka, zda by se subdoména dala prodat jako samostatný byznys.
- [Patterns, Principles and Practices of DDD](https://www.wiley.com/Patterns,+Principles,+and+Practices+of+Domain+Driven+Design-p-9781118714706) – Scott Millett a Nick Tune, kapitola 3 „Focusing on the Core Domain“ a heuristika „code for replacement rather than reuse“ pro Supporting subdomény.
- [Architecture for Flow](https://www.informit.com/articles/article.aspx?p=3222355&seqNum=3) – Susanne Kaiser propojuje klasifikaci subdomén s Wardleyho mapami a Team Topologies.
- [Independent Service Heuristics](https://github.com/TeamTopologies/Independent-Service-Heuristics) – sada otázek pro hledání hranic subdomén, srozumitelná i netechnickým účastníkům workshopu.
