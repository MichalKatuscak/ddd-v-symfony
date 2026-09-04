# Studie: Co je Domain-Driven Design?

- **Kapitola:** `content/chapters/what_is_ddd.md` (č. 01, kategorie Základy, 303 řádků)
- **Cesta:** /co-je-ddd
- **Typ kapitoly:** hybridní (narativní úvod + definiční jádro + rozcestník)
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| Úvodní příběh (bez nadpisu) | 22–43 (22 ř.) | E-shop po třech letech: 12 stavů, 4 typy zákazníka, 5 platebních metod, přidání BitPay trvá 3 týdny. Komplexita přerostla model. | žádné | Nejsilnější část kapitoly. Není označen jako ilustrativní scénář, ačkoli fiktivní je. |
| 01.01 Definice DDD | 44–58 (15 ř.) | DDD staví modelování domény do středu návrhu; Evans 2003. Čtyři „základní aspekty": Doména, Ubiquitous Language, Bounded Context, Model-Driven Design. | [1] domainlanguage.com, [2][3][4] Fowler bliki, [5] InfoQ 2008 | Definice je poskládaná z bliki hesel, ne z Evansova vlastního shrnutí. |
| 01.02 Historie a vývoj | 60–68 (9 ř.) | Pět milníků: 2003 Evans, 2013 Vernon IDDD, 2013 Brandolini Event Storming, 2016 Vernon Distilled, po 2015 mikroservisy. | [6] dddcommunity.org, [7] docs.microsoft.com | Chybí posun důrazu 2009 a DDD Reference 2015. |
| 01.03 Ubiquitous Language v praxi | 70–110 (41 ř.) | UL vzniká konverzací; glosář jako markdown v repozitáři; čeština v konverzaci / angličtina v kódu; tři signály eroze; ukázka glosáře. | žádné | Nejoriginálnější a nejlépe napsaná sekce. Zcela bez zdrojů. |
| 01.04 Strategický design | 112–138 (27 ř.) | Diagram strategický vs. taktický. Sedm vzorů v seznamu + podsekce Bounded Context (`Customer` v Ordering vs. Support). | žádné | Chybí Distillation (Core Domain), Separate Ways, Big Ball of Mud. |
| 01.05 Taktický design | 140–166 (27 ř.) | Sedm stavebních bloků + callout s agregátem `Order`/`OrderLine`/`Money`/`Address` + diagram. | žádné | Duplikuje kapitolu 06. Používá `OrderLine` proti konvenci knihy. |
| 01.06 Implementace v praxi | 168–179 (12 ř.) | Osm kroků zavedení DDD, první čtyři strategické. | žádné | Postup je blízký `ddd-crew/ddd-starter-modelling-process`, necituje jej. |
| 01.07 Výhody | 181–212 (32 ř.) | Komunikace, odolnost vůči změnám, modularita, testovatelnost, snížení tech. dluhu, zaměření na hodnotu + callout „tři týdny vs. tři dny". | žádné | Kvantifikace bez opory. |
| 01.08 Výzvy a omezení | 214–236 (23 ř.) | Pět nákladů + lidská stránka + ilustrativní scénář „DDD bez doménového experta". | žádné | Scénář je správně označen jako ilustrativní. |
| 01.09 DDD vs. jiné přístupy | 238–256 (19 ř.) | Transaction Script, CRUD, hexagonální architektura, mikroservisy + callout „kdy nepoužívat". | Fowler *PoEAA*, Cockburn (v textu, bez URL) | Nejvěcnější srovnávací pasáž v knize. |
| 01.10 Shrnutí | 258–266 (9 ř.) | Tři vrstvy: strategický, taktický, implementační vzory. | žádné | Rozdělení „implementační vzory" jako třetí vrstva je autorské, ne Evansovo. |
| FAQ | 268–277 (10 ř.) | Čtyři otázky: co je DDD, UL, BC, kdy se nevyplatí. | žádné | FAQ zopakuje mikroservisové zjednodušení. |
| 01.11 Další četba | 279–287 (9 ř.) | Pět odkazů: domainlanguage, tři Amazony, dddcommunity. | – | Chybí volně dostupný DDD Reference PDF. |
| 01.12 Jak číst tuto knihu | 289–303 (15 ř.) | Mapa 24 kapitol podle částí + doporučená vstupní trasa. | – | Duplikuje předmluvu (`preface.md:104`), ale účelně. |

Kapitola dává nejvíc prostoru vyprávění a Ubiquitous Language – dohromady 63 řádků ze 303. Definiční jádro (01.01) má 15 řádků a je nejslabším místem: čtyři odrážky z Fowlerova bliki, žádné Evansovo vlastní shrnutí. Strategický a taktický design dostávají skoro identický prostor (27 a 27 řádků), což čtenáři signalizuje rovnocennost, kterou primární zdroje po roce 2009 popírají. Core Domain, podle Evanse první bod definice DDD, se v celé kapitole objeví jednou, v odrážce o výhodách (`what_is_ddd.md:190`).

Stručnost kapitoly (303 řádků proti mediánu ~1000) není sama o sobě vada – vstupní kapitola má být rozcestník. Vadné je rozložení té stručnosti: kapitola šetří tam, kde je jediná (definice, důraz, kritika), a utrácí tam, kde ji později zastoupí kapitoly 06 a 07 (výčet taktických vzorů, agregát objednávky).

## 2. Kanonické zdroje k tématu

**Evansovo vlastní shrnutí DDD.** Nejdůležitější zjištění rešerše. V *DDD Reference* (2015) Evans na začátku části I definuje DDD třemi body [1]:

> Domain-Driven Design is an approach to the development of complex software in which we:
> 1. Focus on the core domain.
> 2. Explore models in a creative collaboration of domain practitioners and software practitioners.
> 3. Speak a ubiquitous language within an explicitly bounded context.

Toto je jediná stručná definice DDD, kterou Evans sám formuloval a publikoval. Kapitola ji nemá. Její struktura je pro vstupní kapitolu ideální: tři body, každý ukazuje na jednu část knihy.

**Definice pojmů podle Evanse** (*DDD Reference*, sekce Definitions) [1]:

- *domain* – „A sphere of knowledge, influence, or activity. The subject area to which the user applies a program is the domain of the software."
- *model* – „A system of abstractions that describes selected aspects of a domain and can be used to solve problems related to that domain."
- *ubiquitous language* – „A language structured around the domain model and used by all team members **within a bounded context** to connect all the activities of the team with the software."
- *bounded context* – „A description of a boundary (typically a subsystem, or the work of a particular team) within which a particular model is defined and applicable."

Zvýrazněná část definice UL je klíčová a v kapitole chybí (`what_is_ddd.md:55`).

**Bounded Context jako pattern** (*DDD Reference*, část I) [1]. Evans předepisuje hranici ve třech rozměrech současně: „Explicitly set boundaries in terms of team organization, usage within specific parts of the application, and physical manifestations such as code bases and database schemas." Hranice tedy není jen sémantická; je i organizační a fyzická. Pozoruhodné je i umístění: v knize z roku 2003 byl Bounded Context až kapitola 14, v Reference z roku 2015 jej Evans přesunul na první místo celého vzorového jazyka.

**Ubiquitous Language jako pattern** (*DDD Reference*, část I) [1]. Dvě věty, které kapitola nemá a které mění vyznění celé sekce 01.03: „Use the model as the backbone of a language." a „**Recognize that a change in the language is a change to the model.**" Vztah je obousměrný. Kapitola na řádku 90 tvrdí jednosměrně: „srovnat kód s jazykem expertů, ne naopak."

**Tři vzory přidané po knize.** *DDD Reference* označuje hvězdičkou tři termíny zavedené po roce 2004: **Domain Events**, **Partnership** a **Big Ball of Mud** [1]. To potvrzuje atribuci zapsanou v `CLAUDE.md`. Zároveň to znamená, že Domain Events, které kapitola uvádí v seznamu taktických vzorů (`what_is_ddd.md:147`), v původní knize nejsou.

**Posun důrazu, 2009.** Na QCon London 2009 měl Evans přednášku *What I've learned about DDD since the book* [2][3]. Podstata: „Ubiquitous Language, Context Mapping a Core Domain jsou nyní v centru, agregáty na blízké oběžné dráze." Stavební bloky (value objects, entities, factories, repositories, services) označil za **přeceněné** („over emphasised"). Ke Context Mappingu (kapitola 14) a Core Domain (kapitola 15) řekl, že „zaslouží mnohem větší důraz v celém procesu", než naznačuje jejich pozice v knize. K agregátům: jsou to hranice konzistence pro transakce, distribuci a souběžnost, ne primárně pravidla přístupu.

**Definice podle Fowlera.** Bliki heslo *DomainDrivenDesign* (22. 4. 2020) [4]: DDD je „an approach to software development that centers the development on programming a domain model that has a rich understanding of the processes and rules of a domain." Fowler v témže textu označuje strategický design za hlavní Evansův přínos: problém organizace velkých domén do propojených bounded contexts podle něj předtím nikdo přesvědčivě neřešil. Heslo *BoundedContext* (15. 1. 2014) [6] přidává: „you need a different model when the language changes." Heslo *UbiquitousLanguage* (31. 10. 2006) [5] definuje UL jako praxi budování společného, rigorózního jazyka mezi vývojáři a uživateli.

**Vernon, *Implementing Domain-Driven Design*** (Addison-Wesley, 16. 2. 2013) [7]. Kniha je „top-down": strategické vzory nejdřív, taktické nástroje z nich odvozené. Zavádí termín **DDD-Lite** pro praxi, která bere jen taktické vzory kvůli technickému užitku a strategickou část vynechá.

**Vernon, *Domain-Driven Design Distilled*** (Addison-Wesley, 23. 5. 2016) [8]. Struktura potvrzuje posun: kap. 2 Bounded Contexts a Ubiquitous Language, kap. 3 Subdomains, kap. 4 Context Mapping – teprve pak kap. 5 Aggregates a kap. 6 Domain Events. Tři strategické kapitoly před dvěma taktickými.

**Khononov, *Learning Domain-Driven Design*** (O'Reilly, říjen 2021) [9]. Podtitul *Aligning Software Architecture and Business Strategy* posouvá rámec ještě dál od kódu: DDD jako nástroj sladění architektury s obchodní strategií. Používá trojici Core / Supporting / Generic subdomain. Evansův *DDD Reference* má v části V pouze **Core Domain** a **Generic Subdomains**; „Supporting Subdomain" u Evanse jako vzor není – potvrzuje atribuci Vernonovi z `CLAUDE.md`.

**Evans, DDD Europe 2019, *Domain-Driven Design: The Good Parts*** [10]. Formulace, které se hodí vstupní kapitole: cílem je model dobře padnoucí na konkrétní problém v konkrétním kontextu, ne dokonalý model; před volbou modelu je namístě prozkoumat několik konkurenčních; DDD je cenné u doménové složitosti, ne u čistě technických problémů typu škálování.

**Evans, DDD Europe 2019, o bounded contexts a mikroservisách** [11]. Explicitně: tvrzení „mikroslužba je bounded context" je zjednodušení. Rozlišuje čtyři situace – vnitřek služby, API služby, klastr společně navržených služeb jako jeden kontext, a interchange context (samotná interakce mezi službami se musí modelovat). Dodává, že bounded context a subdoména „v ideálním světě splývají, v realitě jsou často nesouhlasné", a že „ne všechny softwarové projekty se mají dělat DDD".

## 3. Stav praxe a posuny

**Strategie před taktikou je dnes většinový výklad.** Pořadí kapitol u Vernona 2016, rámování u Khononova 2021 i Evansova vlastní revize z roku 2009 ukazují stejným směrem. Kniha, která v roce 2026 představuje strategický a taktický design jako dvě stejně velké krabice, opakuje výklad z let 2004–2010.

**Dominantní způsob selhání je „DDD-lite".** Doložený napříč zdroji různého typu: Vernon (2013) [7], Tilkov (INNOQ, 2021) [12], praktické blogy [13]. Tým se s DDD seznámí přes value objects, entity, agregáty a repozitáře, prohlásí to za DDD a strategickou část přeskočí. Tilkov k tomu přidává druhou stranu – kritiku přehnaného evangelizování: „DDD je užitečný přístup… ale je to prostředek, ne cíl," a namítá proti reflexu volat DDD experty pokaždé, když přijde řeč na hranice služeb.

**Empirická opora je slabší, než se běžně tvrdí.** Systematický přehled Özkan, Babur, van den Brand (2023, rev. 2025) prošel 36 recenzovaných studií [14]. Závěr: DDD prokazatelně zlepšilo zkoumané systémy, zejména v kombinaci s mikroslužbami, ale části studií chybí empirická validace; bariérami adopce jsou onboarding a nároky na expertízu. Pro knihu to znamená: tvrzení o přínosech je poctivější formulovat jako zkušenostní, ne jako měřený fakt.

**Jak DDD vypadá v reálném open-source kódu.** Rozsáhlá empirická charakterizace ekosystému (2026) identifikovala 2 502 ověřených DDD repozitářů na GitHubu [15]. Zjištění relevantní pro tuto kapitolu: adopce se zrychlila po roce 2017; dominují vrstvená a Clean Architecture, CQRS a Event Sourcing se objevují v distribuovaných a datově náročných systémech; nejvíc DDD projektů je v C# a TypeScriptu, ne v Javě; přibližně 25,3 % projektů nemá explicitně dokumentovaný obchodní kontext. Poslední číslo je přímý argument pro sekci o Ubiquitous Language a glosáři.

**Institucionalizace komunity.** Konference DDD Europe od roku 2016 (Brusel) [16], sborník *Domain-Driven Design: The First 15 Years* (Leanpub, 15. 2. 2020) s příspěvky Fowlera, Coplien, Wirfs-Brock a Conwaye [17], a otevřené nástroje ddd-crew: **DDD Starter Modelling Process** (CC-BY-4.0) s osmi kroky Understand – Discover – Decompose – Strategize – Connect – Organise – Define – Code [18], a **Core Domain Charts** Nicka Tunea (osy model complexity × business differentiation) [19].

**Rozšíření za hranice softwaru.** Khononov *Balancing Coupling in Software Design* (Addison-Wesley, 26. 9. 2024) [20] přesouvá těžiště od DDD vzorů k obecnějším principům coupling a modularity. Trend: DDD přestává být samostatnou doktrínou a stává se jedním slovníkem mezi několika.

**AI jako nová proměnná.** Evans v otevírací keynote DDD Europe 2026 [21] formuluje pracovní hypotézu: „domain models still matter, bounded contexts still matter, language still matters – but the models will look different." Popisuje experiment, kdy komponenty LLM extrahují doménový slovník z kódu jako objektivnější způsob zkoumání hranic kontextů. Kniha na to má samostatnou referenční stránku (`/ddd-ai`), na kterou vstupní kapitola neodkazuje.

## 4. Symfony / PHP specifika

Kapitola neobsahuje ani řádek PHP a to je pro vstupní kapitolu obhajitelné. Pro přepis je relevantní spíš rámování než API.

**Verze, na které kniha cílí.** Symfony 8.0 vyšlo v listopadu 2025 a vyžaduje PHP 8.4+ [22]. Symfony vydává minor verze každých šest měsíců (květen a listopad), major po dvou letech. K datu studie je udržovanou větví řady 8 verze 8.1 (květen 2026, konec oprav listopad 2026); 8.0 už podporu nemá. Doctrine ORM 3.0 vyšlo 3. 2. 2024 a vyžaduje PHP 8.1+ [23]. PHP 8.4 přineslo property hooks, asymetrickou viditelnost (`public private(set)`) a lazy objects [24] – vše tři věci, které se přímo dotýkají konvence knihy o `public readonly` vlastnostech value objektů.

**Co do vstupní kapitoly patří.** Jedna věta o tom, že Symfony samo o sobě nic z DDD nevynucuje ani nebrání: výchozí struktura projektu (`src/Entity`, `src/Repository`, MakerBundle) je datově orientovaná a implicitně vede k CRUD. To je konkrétní zakotvení pro čtenáře, který přišel z Symfony a ne z DDD literatury. Detailní strukturu řeší kapitola 10 (`/implementace-v-symfony`), rozhodnutí o vrstvách kapitola 09.

**Ekosystém, který stojí za jednu zmínku.** Symfony Messenger jako výchozí infrastruktura pro command/event bus (kapitoly 12–15). Z nadstaveb je aktivně vyvíjený **Ecotone** (DDD, CQRS, Event Sourcing nad Symfony, Laravel i Tempest) [25]; **EventSauce** a **Prooph** jsou v ekosystému stále přítomné. Do kapitoly 01 patří nanejvýš věta, že tyto knihovny existují a kniha je nepoužívá jako výchozí volbu.

**Doporučení k odkazům.** Kapitola 01 v současnosti neodkazuje na `symfony.com/doc` vůbec. To je v pořádku; přidávat odkaz na dokumentaci do definiční kapitoly by rozostřilo její roli.

## 5. Sporné a chybně podávané body

**1. Ubiquitous Language jako firemní slovník.** Kapitola definuje UL jako „společný jazyk používaný vývojáři a doménovými experty" (`what_is_ddd.md:55`) a nikde v 01.01 ani v FAQ neváže jazyk na kontext. Evansova definice obsahuje „within a bounded context" [1]; Fowler v hesle *BoundedContext* říká totéž z druhé strany [6]. Důsledek chybného výkladu je konkrétní a častý: firma se pokusí sjednotit terminologii napříč všemi odděleními, narazí na polysémii (`Customer` v Ordering vs. Support) a skončí buď u kompromisních jmen, nebo u zamrzlého slovníku. Kapitola paradoxně sama tuto situaci správně popisuje o 75 řádků níž (`what_is_ddd.md:130–138`), ale definice na začátku ji nepředjímá. **Doporučení:** definici UL doplnit o vazbu na kontext hned v 01.01 a v FAQ.

**2. Rovnocennost strategického a taktického designu.** Kapitola dává oběma stejný prostor a nikde neříká, že u samotného Evanse posun proběhl. Proti tomu stojí Evans 2009 [2][3], struktura Vernona 2016 [8] i Khononov 2021 [9]. **Doporučení:** neposouvat rovnováhu prostoru násilím, ale doplnit dva až tři řádky, které posun pojmenují a odkážou na kapitoly 2–5.

**3. Bounded Context = mikroslužba.** Řádek 68 tvrdí, že po roce 2015 se Bounded Context „stává standardním vodítkem pro určení hranic jednotlivých služeb", FAQ na řádku 274 mluví o „přirozených hranicích pro mikroservisy". Evans sám v roce 2019 označil ztotožnění za zjednodušení a rozlišil čtyři různé případy [11]. Kapitola má správnou formulaci na řádku 245 (v sekci 01.09), takže si sama se sebou protiřečí. **Doporučení:** srovnat všechna tři místa na formulaci z řádku 245.

**4. Rok vydání knihy: 2003, nebo 2004?** Kapitola uvádí 2003. Vydavatel (Addison-Wesley) uvádí datum vydání 20. 8. 2003 a copyright 2004 [26]. Evans sám na svém webu i v *DDD Reference* mluví o „my 2004 book" a citaci uvádí jako „Addison-Wesley, 2004" [1]. Obě čísla jsou obhajitelná. **Doporučení:** ponechat 2003 (datum vydání), nikde v knize nesměšovat obojí, a nekomentovat rozpor v textu – čtenáře by to zdrželo bez užitku.

**5. „Projekty selhávají spíš kvůli neporozumění doméně než kvůli technickým chybám."** (`what_is_ddd.md:46`) Tvrzení bez zdroje. Evidence existuje, ale je slabší a spornější, než formulace naznačuje: Standish CHAOS (1995) vede „incomplete requirements" jako hlavní příčinu, CHAOS 2014 už rozpouští příčiny do menších procent (12,3 % neúplné požadavky, 12,8 % nedostatečné zapojení uživatelů); metodika CHAOS je dlouhodobě kritizovaná. **Doporučení:** přeformulovat jako Evansovu výchozí tezi („Evans staví DDD na předpokladu, že…"), ne jako doložený fakt o oboru. Alternativa: opřít se o novější a lépe dohledatelný Özkan et al. (2023) [14], který mluví o expertíze a onboardingu jako hlavních bariérách.

**6. Návratnost „šest až dvanáct měsíců".** (`what_is_ddd.md:38`) Číslo bez opory a bez možnosti opory. Žádný z prošlých zdrojů takovou metriku neuvádí; SLR z roku 2023 naopak konstatuje nedostatek empirické validace [14]. **Doporučení:** buď číslo vypustit, nebo explicitně označit za autorský odhad.

**7. „Tři týdny vs. tři dny."** (`what_is_ddd.md:209`) Kvantifikovaný závěr uvnitř calloutu typu `pattern`, tedy v roli doporučeného vzoru. Kapitola přitom o 20 řádků níž správně označuje fiktivní případ jako „Ilustrativní scénář" (`what_is_ddd.md:227`). Nekonzistence v rámci jedné kapitoly. **Doporučení:** callout přeznačit nebo číslo nahradit kvalitativním tvrzením („změna se odehraje v adapteru, ne napříč pěti soubory").

**8. Model-Driven Design jako „jeden ze čtyř základních aspektů".** (`what_is_ddd.md:57`) Model-Driven Design je u Evanse pattern v části I *Putting the Model to Work* [1], vedle Continuous Integration, Hands-on Modelers a Refactoring Toward Deeper Insight – ne jeden ze čtyř pilířů. Kapitola jej navíc dokládá článkem InfoQ z roku 2008 (Penchikala), tedy sekundárním zdrojem, když primární je volně dostupný. **Doporučení:** buď nahradit čtveřici Evansovým tříbodovým shrnutím, nebo alespoň převést citaci [5] na *DDD Reference*.

**9. Jednosměrnost „srovnat kód s jazykem expertů, ne naopak".** (`what_is_ddd.md:90`) Jako reakce na erozi je to správná rada. Jako obecný princip odporuje Evansovu „Recognize that a change in the language is a change to the model" [1] – jazyk a model se mění společně a někdy podnět přichází z modelu. **Doporučení:** zúžit tvrzení na situaci eroze, nebo doplnit obousměrnost.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | `what_is_ddd.md:44–58` | Evansovo vlastní tříbodové shrnutí DDD (*DDD Reference*, 2015) v kapitole není; definice je poskládaná z Fowlerova bliki. | Otevřít 01.01 tříbodovým shrnutím a teprve pak rozvinout pojmy. |
| G2 | chybí | celá kapitola, jediná zmínka `:190` | Core Domain / distillation – první bod Evansovy definice a podle jeho revize z 2009 nejdůležitější strategický koncept. | Zařadit jako plnohodnotný pojem do 01.01 a 01.04 s odkazem na `/subdomeny`. |
| G3 | sporné | `what_is_ddd.md:55`, `:272` | Definice Ubiquitous Language bez vazby „within a bounded context". Nejčastější dezinterpretace DDD. | Doplnit vazbu do definice i do FAQ odpovědi. |
| G4 | chybí | `what_is_ddd.md:90` | Chybí Evansova obousměrnost jazyka a modelu („a change in the language is a change to the model"). | Doplnit jednou větou v 01.03. |
| G5 | chybí | `what_is_ddd.md:60–68` | Posun důrazu ze stavebních bloků na UL / Context Mapping / Core Domain (Evans 2009) není nikde zmíněn. | Nový milník 2009 v 01.02 + dvě věty v 01.04. |
| G6 | chybí | `what_is_ddd.md:214–236` | Anti-vzor „DDD-lite" (taktické vzory bez strategie) – nejčastější způsob, jak adopce DDD selže. | Nová odrážka nebo krátký odstavec v 01.08. |
| G7 | chybí | celá kapitola | Kritika DDD (Tilkov 2021) a slabá empirická opora (Özkan et al. 2023) nejsou zastoupeny. | Krátká pasáž v 01.08 nebo 01.09, dva odkazy. |
| G8 | chybí | `what_is_ddd.md:128–138` | Rozdíl subdoména (problémový prostor) vs. bounded context (prostor řešení) není naznačen; Evans 2019 jej označuje za častý zdroj zmatku. | Jedna věta s dopřednou vazbou na `/subdomeny`. |
| G9 | mělké | `what_is_ddd.md:130` | Definice BC je čistě sémantická. Evans předepisuje hranici i v týmové organizaci a fyzických artefaktech (kódové báze, DB schémata). | Doplnit organizační a fyzický rozměr, vazba na `/conways-law-a-team-topologies`. |
| G10 | mělké | `what_is_ddd.md:60–68` | Historie má 5 milníků. Chybí 2006 (CC uvolnění pattern summaries), 2009 (QCon), 2015 (*DDD Reference*), 2016 (DDD Europe), 2020 (*The First 15 Years*), 2021 (Khononov). | Rozšířit na 8–10 milníků, ne víc. |
| G11 | nepodložené | `what_is_ddd.md:46` | „Projekty selhávají častěji kvůli neporozumění doméně" – bez zdroje, evidence je sporná. | Přeformulovat jako Evansovu tezi, nebo doložit. |
| G12 | nepodložené | `what_is_ddd.md:38` | Návratnost „šest až dvanáct měsíců" – žádný zdroj neexistuje. | Vypustit číslo, nebo označit za autorský odhad. |
| G13 | nepodložené | `what_is_ddd.md:209` | „Tři týdny vs. tři dny" v calloutu `pattern`, tedy podáno jako doporučený vzor, ne jako scénář. | Přeznačit callout nebo zrušit kvantifikaci. |
| G14 | sporné | `what_is_ddd.md:68`, `:274` | Bounded Context ztotožněn s hranicí mikroslužby; kapitola si na `:245` sama protiřečí. | Srovnat na formulaci z `:245`. |
| G15 | zastaralé | `what_is_ddd.md:68` | Odkaz `docs.microsoft.com` přesměrovává na `learn.microsoft.com`. | Aktualizovat URL. |
| G16 | nepodložené | `what_is_ddd.md:57` | Model-Driven Design doložen článkem InfoQ 2008, primární zdroj (*DDD Reference*) je volně dostupný. | Nahradit citaci. |
| G17 | chybí | `what_is_ddd.md:279–287` | „Další četba" nezná volný CC-BY PDF *DDD Reference* – nejlepší bezplatný vstup do tématu. | Přidat odkaz na PDF. |
| G18 | chybí | `what_is_ddd.md:168–179` | Osm kroků zavedení DDD se překrývá s `ddd-crew/ddd-starter-modelling-process` (CC-BY-4.0), který citován není. | Doplnit odkaz jako externí, udržovaný postup. |
| G19 | nadbytečné | `what_is_ddd.md:140–166` | Výčet sedmi taktických vzorů + callout s agregátem duplikují kapitolu 06 (`basic_concepts.md:22–531`) a diagram z jejího adresáře. | Zkrátit na jmenný přehled s odkazem; uvolněné řádky použít na G1/G2/G5. |
| G20 | sporné | `what_is_ddd.md:158` | Agregát používá `OrderLine`; kanonický příklad knihy používá `OrderItem` (12 výskytů v `aggregate_design.md`, 9 v `basic_concepts.md`). | Sjednotit na `OrderItem`. |
| G21 | mělké | `what_is_ddd.md:117–126` | Seznam strategických vzorů vynechává Separate Ways a Big Ball of Mud; Partnership je jen v závorce u Context Map, ač jde o jeden ze tří Evansem doplněných vzorů. | Doplnit dva vzory, Partnership povýšit na odrážku. |
| G22 | chybí | `what_is_ddd.md:168–172` | Knowledge crunching / průzkum více konkurenčních modelů (Evans 2019: „fit over perfection") – druhý bod Evansovy definice – v kapitole není. | Rozšířit krok 1 v 01.06 o jednu až dvě věty. |
| G23 | nepodložené | `what_is_ddd.md:181–191` | Výhody („snížení technického dluhu", „živá dokumentace") jsou tvrzení bez opory; SLR 2023 upozorňuje na chybějící empirickou validaci. | Zmírnit formulace, jednou větou přiznat stav evidence. |
| G24 | mělké | `what_is_ddd.md:76–80` | Sekce o češtině v kódu je autorský obsah bez jakéhokoli ukotvení, ač je to nejcitovanější potenciál kapitoly pro české publikum. | Zakotvit logikou překladu na hranici kontextu (Published Language / ACL), doplnit odkaz na `/context-mapping#published-language`. |
| G25 | chybí | `what_is_ddd.md:289–303` | Mapa knihy nezmiňuje referenční stránky `/glosar` je zmíněn, ale `/ddd-a-umela-inteligence` a `/zdroje` ne. | Doplnit dvě položky do závěrečného odstavce. |
| G26 | chybí | `what_is_ddd.md:22–43` | Úvodní příběh je fiktivní, ale není označen jako ilustrativní scénář, na rozdíl od scénáře na `:227`. | Jedna věta nebo štítek pro konzistenci s konvencí knihy. |

Celkem 26 nálezů: chybí 12, mělké 5, nepodložené 5, sporné 3, zastaralé 1, nadbytečné 1.

## 7. Doporučení k přepisu

**P1-1 — Nahradit definiční jádro 01.01 Evansovým tříbodovým shrnutím.**
Kapitola dnes definuje DDD čtyřmi odrážkami odvozenými z Fowlerova bliki. Evans má vlastní publikované shrnutí ve třech bodech (*DDD Reference*, 2015, CC-BY), které kniha nikde nepoužívá. Je stručnější, autoritativnější a jeho tři body přímo mapují na části knihy: Core Domain → kapitoly 2–5, průzkum modelů se spolupracujícími experty → kapitola 4, ubiquitous language v ohraničeném kontextu → kapitoly 3 a 6. Řeší G1, G2, G16.
*Odhad: přepis sekce 01.01, ~20 řádků.*

**P1-2 — Opravit definici Ubiquitous Language a doplnit vazbu na kontext.**
Definice na `:55` i FAQ odpověď na `:272` podávají UL jako jazyk týmu bez hranice. Evansova definice tuto hranici obsahuje a je to rozdíl, který určuje, jestli tým skončí u firemního slovníku, nebo u funkčního modelu. Kapitola sama si na `:130–138` protiřečí. Řeší G3.
*Odhad: oprava dvou vět + jedné FAQ odpovědi.*

**P1-3 — Pojmenovat posun důrazu ze stavebních bloků na strategický design.**
Nejdůležitější věc, kterou vstupní kapitola v roce 2026 může říct a dnes neříká: Evans sám v roce 2009 označil taktické stavební bloky za přeceněné a Vernon v roce 2016 překlopil pořadí výkladu. Bez toho čtenář z kapitoly odejde s modelem „DDD = entity, VO, agregáty", tedy s DDD-lite. Zahrnuje nový milník 2009 v historii. Řeší G5, částečně G6.
*Odhad: nový milník v 01.02 + odstavec ~8 řádků na začátek 01.04.*

**P1-4 — Opravit ztotožnění Bounded Contextu s mikroslužbou na třech místech.**
Řádky 68 a 274 tvrdí něco, co řádek 245 správně vyvrací, a co Evans sám v roce 2019 označil za zjednodušení. Vnitřní rozpor ve vstupní kapitole je vážnější než jinde, protože právě tuto kapitolu čte nejvíc lidí. Řeší G14, zároveň opravit URL z G15.
*Odhad: oprava tří vět + jedné URL.*

**P1-5 — Odstranit nebo označit nepodložené kvantifikace.**
Tři místa: příčiny selhání projektů (`:46`), návratnost 6–12 měsíců (`:38`), tři týdny vs. tři dny (`:209`). Kniha jinde konvenci „Ilustrativní scénář" drží, tady ne. U vstupní kapitoly s nejvyšší návštěvností je nepodložené číslo největší reputační riziko celé knihy. Řeší G11, G12, G13, G26.
*Odhad: přepis čtyř vět + přeznačení jednoho calloutu.*

**P2-1 — Doplnit sekci o kritice DDD a stavu evidence.**
Kapitola má 23 řádků o „výzvách a omezeních", ale všechny jsou interní (náročnost, čas, legacy). Chybí vnější pohled: Tilkovova námitka proti evangelizování a zjištění systematického přehledu, že empirická validace přínosů je nerovnoměrná. Průvodce, který zná kritiku svého tématu, působí důvěryhodněji než průvodce, který ji zamlčí. Řeší G7, G23.
*Odhad: nová podsekce v 01.08, ~12 řádků.*

**P2-2 — Zkrátit taktický výčet (01.05) a uvolněný prostor dát strategii.**
Sedm odrážek taktických vzorů plus callout s agregátem duplikují kapitolu 06 skoro doslova, včetně diagramu z jejího adresáře. Vstupní kapitola má vzory pojmenovat a poslat dál, ne je vysvětlovat. Ušetřených ~15 řádků pokryje P1-1 až P1-3 beze změny celkového rozsahu. Řeší G19, zároveň sjednotit `OrderLine` → `OrderItem` (G20).
*Odhad: přepis sekce 01.05, −15 řádků.*

**P2-3 — Rozšířit definici Bounded Contextu o organizační a fyzický rozměr.**
Evans hranici definuje ve třech rozměrech: sémantickém, týmovém a fyzickém (kódová báze, DB schéma). Kapitola má jen první. Druhý je most ke kapitole 05 (Team Topologies), třetí ke kapitole 19 (mikroslužby a modular monolith). Řeší G9, otevírá i G8 (subdoména vs. bounded context).
*Odhad: rozšíření podsekce `#bounded-context` o ~6 řádků.*

**P2-4 — Doplnit historii o chybějící milníky a odkazy na volné zdroje.**
Pět milníků na dvacet let vývoje je málo. Doplnit 2009 (QCon), 2015 (*DDD Reference*), 2016 (DDD Europe), 2021 (Khononov). V „Další četbě" přidat volně dostupné CC-BY PDF *DDD Reference* a `ddd-crew/ddd-starter-modelling-process`. Řeší G10, G17, G18.
*Odhad: rozšíření 01.02 o 4 odrážky + 2 odkazy v 01.11.*

**P3-1 — Zakotvit sekci o češtině v kódu do logiky překladu na hranici.**
Sekce je autorská a věcně dobrá, ale visí ve vzduchu. Stačí ji spojit s myšlenkou, kterou kapitola už má o 50 řádků níž: překlad patří na hranici kontextu, uvnitř kontextu je jeden slovník. Glosář jako překladová tabulka je pak přirozený důsledek, ne izolované doporučení. Řeší G24.
*Odhad: dvě věty + jeden interní odkaz.*

**P3-2 — Doplnit knowledge crunching do kroku 1 v 01.06.**
Druhý bod Evansovy definice je „průzkum modelů v tvůrčí spolupráci". Krok 1 dnes zní „rozhovory, workshopy, modelování na tabuli", což je popis aktivit, ne principu. Evans 2019: model se volí podle toho, jak padne na problém, a před volbou se prozkoumá víc variant. Řeší G22.
*Odhad: přepis jedné odrážky.*

**P3-3 — Doplnit odkazy na referenční stránky do mapy knihy.**
Sekce 01.12 zmiňuje glosář a cheat sheet, ale ne `/ddd-a-umela-inteligence` a `/zdroje`. U nejsilnějšího SEO uzlu webu je každý odchozí interní odkaz relevantní. Řeší G25.
*Odhad: jedna věta.*

## 8. Otevřené otázky pro autora

1. **Má kapitola zůstat krátká?** Rešerše ukazuje, že 303 řádků nestačí na obsah, který kapitola musí unést (definice, důraz, kritika, mapa knihy). Doporučení P1 a P2 přidají zhruba 45 řádků a P2-2 jich 15 ubere. Cílový rozsah kolem 330–350 řádků drží kapitolu stále nejkratší v sekci Základy. Je to přijatelné, nebo má vstupní kapitola zůstat pod 310 řádky?

2. **Kde má být Core Domain poprvé vysvětlen?** Kapitola 02 (`/subdomeny`) mu věnuje celou sekci. Studie doporučuje pojmenovat jej už v kapitole 01 jako první bod Evansovy definice. Riziko duplicity je reálné. Rozhodnout, jestli v kapitole 01 stačí jedna věta s odkazem, nebo krátký odstavec.

3. **Úvodní příběh e-shopu – ponechat, nebo označit?** Konvence knihy fiktivní případy označuje („Ilustrativní scénář"). Úvod kapitoly označený není, a přitom obsahuje nejvíc konkrétních čísel. Označení může ubrat na síle vyprávění. Rozhodnout, jestli konzistence převáží nad účinkem.

4. **Kolik prostoru dát kritice DDD?** Vstupní kapitola, která hned relativizuje vlastní téma, může čtenáře odradit. Kniha ale kritiku potřebuje pro důvěryhodnost a kapitola 22 (`/kdy-nepouzivat-ddd`) už jednu její část nese. Rozhodnout, jestli kritika patří do kapitoly 01 (P2-1), nebo se má celá odsunout do kapitoly 22 s dopředným odkazem.

5. **AI a DDD ve vstupní kapitole.** Evans této otázce věnoval keynote DDD Europe 2026 a kniha má samostatnou referenční stránku `/ddd-a-umela-inteligence`. Má se to v kapitole 01 objevit jako milník v historii, jen jako odkaz v 01.12, nebo vůbec?

6. **Sekce o češtině v kódu – rozšířit, nebo držet?** Je to jediné místo v celé knize, kde se řeší jazyková realita českého týmu, a v rešerši se pro ni nenašel žádný uznávaný externí zdroj. Buď jde o autorský přínos, který by si zasloužil víc prostoru, nebo o odbočku, která ve vstupní kapitole bere místo definicím.

## 9. Bibliografie

### Ověřené zdroje

[1] Eric Evans — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, Domain Language, 2015. CC-BY-4.0. https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf (přístup 2026-09-03) — PDF ověřeno stažením; obsahuje tříbodové shrnutí DDD, definice pojmů, hvězdičkou označené nové vzory (Domain Events, Partnership, Big Ball of Mud).

[2] Eric Evans — *What I've learned about DDD since the book*, QCon London 2009 / DDD-NYC SIG, záznam. https://www.dddcommunity.org/library/evans_2009_1/ (přístup 2026-09-03)

[3] Gojko Adzic — poznámky z QCon London 2009: *Eric Evans – What I've learned about DDD since the book*, 12. 3. 2009. https://gojko.net/2009/03/12/qcon-london-2009-eric-evans-what-ive-learned-about-ddd-since-the-book/ (přístup 2026-09-03)

[4] Martin Fowler — *Domain Driven Design*, bliki, 22. 4. 2020. https://martinfowler.com/bliki/DomainDrivenDesign.html (přístup 2026-09-03)

[5] Martin Fowler — *Ubiquitous Language*, bliki, 31. 10. 2006. https://martinfowler.com/bliki/UbiquitousLanguage.html (přístup 2026-09-03)

[6] Martin Fowler — *Bounded Context*, bliki, 15. 1. 2014. https://martinfowler.com/bliki/BoundedContext.html (přístup 2026-09-03)

[7] Vaughn Vernon — *Implementing Domain-Driven Design*, Addison-Wesley Professional, 16. 2. 2013. ISBN 978-0-321-83457-7.

[8] Vaughn Vernon — *Domain-Driven Design Distilled*, Addison-Wesley Professional, 23. 5. 2016. ISBN 978-0-134-43442-1. Struktura kapitol ověřena na https://www.pearson.com/en-us/subject-catalog/p/domain-driven-design-distilled/P200000009615/9780134434995

[9] Vlad Khononov — *Learning Domain-Driven Design: Aligning Software Architecture and Business Strategy*, O'Reilly Media, říjen 2021. ISBN 978-1-098-10013-1.

[10] Eric Evans — *Domain-Driven Design: The Good Parts*, DDD Europe 2019; shrnutí na https://ddd.academy/blog/what-is-ddd-by-eric-evans (přístup 2026-09-03)

[11] Jan Stenberg — *Eric Evans at DDD Europe 2019: Bounded Contexts*, InfoQ, červen 2019. https://www.infoq.com/news/2019/06/bounded-context-eric-evans (přístup 2026-09-03)

[12] Stefan Tilkov — *Is Domain-driven Design Overrated?*, INNOQ, 2. 3. 2021. https://www.innoq.com/en/blog/2021/03/is-domain-driven-design-overrated/ (přístup 2026-09-03)

[13] madewithlove — *The Domain-Driven Design fallacy*. https://madewithlove.com/blog/the-domain-driven-design-fallacy/ (přístup 2026-09-03) — sekundární, použito jen jako doklad rozšířenosti výtky „DDD-lite".

[14] Ozan Özkan, Önder Babur, Mark van den Brand — *Domain-Driven Design in Software Development: A Systematic Literature Review on Implementation, Challenges, and Effectiveness*, arXiv:2310.01905, 2023 (rev. 2025). https://arxiv.org/abs/2310.01905 (přístup 2026-09-03) — 36 recenzovaných studií.

[15] *Domain-Driven Design in Practice: A Large-Scale Empirical Characterisation of the Open-Source Ecosystem*, arXiv:2607.06471, 2026. https://arxiv.org/abs/2607.06471 (přístup 2026-09-03) — 2 502 ověřených DDD repozitářů; 25,3 % bez explicitní dokumentace obchodního kontextu.

[16] Domain-Driven Design Europe — první ročník, Brusel 2016. https://2016.dddeurope.com/ (přístup 2026-09-03)

[17] *Domain-Driven Design: The First 15 Years — Essays from the DDD Community*, Leanpub, 15. 2. 2020. https://leanpub.com/ddd_first_15_years (přístup 2026-09-03)

[18] ddd-crew — *DDD Starter Modelling Process*, CC-BY-4.0. https://github.com/ddd-crew/ddd-starter-modelling-process (přístup 2026-09-03)

[19] Nick Tune / ddd-crew — *Core Domain Charts*. https://github.com/ddd-crew/core-domain-charts (přístup 2026-09-03)

[20] Vlad Khononov — *Balancing Coupling in Software Design*, Addison-Wesley (Vernon Signature Series), 26. 9. 2024. ISBN 978-0-137-35348-4.

[21] Eric Evans — *Opening Keynote*, DDD Europe 2026. https://2026.dddeurope.com/program/opening-keynote-eric-evans/ (přístup 2026-09-03)

[22] Symfony — *Releases*. https://symfony.com/releases (přístup 2026-09-03) — Symfony 8.0 listopad 2025, PHP 8.4+; udržovaná větev 8.1 (květen 2026).

[23] Doctrine — *Doctrine ORM 3 and DBAL 4 Released*, 3. 2. 2024. https://www.doctrine-project.org/2024/02/03/doctrine-orm-3-and-dbal-4-released.html (přístup 2026-09-03)

[24] PHP — *PHP 8.4 Release Announcement*. https://www.php.net/releases/8.4/en.php (přístup 2026-09-03) — property hooks, asymetrická viditelnost, lazy objects.

[25] Ecotone Framework. https://docs.ecotone.tech/modules/symfony (přístup 2026-09-03)

[26] Pearson / InformIT — *Domain-Driven Design: Tackling Complexity in the Heart of Software*, datum vydání 20. 8. 2003, copyright 2004, ISBN 978-0-321-12521-7. https://www.informit.com/store/domain-driven-design-tackling-complexity-in-the-heart-9780321125217 (přístup 2026-09-03)

[27] Martin Fowler — *Anemic Domain Model*, bliki, 25. 11. 2003. https://martinfowler.com/bliki/AnemicDomainModel.html (přístup 2026-09-03)

[28] Alistair Cockburn — *Hexagonal Architecture (Ports & Adapters)*, HaT Technical Report 2005.02, 2005. https://alistair.cockburn.us/hexagonal-architecture (přístup 2026-09-03)

[29] Srini Penchikala — *Domain Driven Design and Development In Practice*, InfoQ, 12. 6. 2008. https://www.infoq.com/articles/ddd-in-practice/ (přístup 2026-09-03) — současný zdroj [5] v kapitole; sekundární.

[30] Avanscoperta blog — *Introducing EventStorming*, 12. 2. 2014 (repost původního článku Alberta Brandoliniho z 18. 11. 2013). https://blog.avanscoperta.it/2014/02/12/introducing-event-storming/ (přístup 2026-09-03)

### Neověřené / nedohledané

- **Přesné znění „DDD-Lite" u Vernona.** Termín je v komunitě konzistentně připisován Vernonovi (*IDDD*, 2013), ale doslovné znění se nepodařilo ověřit z primárního textu – jen z druhotných shrnutí. Před citací v knize ověřit v knize samotné.
- **Původní datum Brandoliniho blogpostu.** Sekundární zdroje uvádějí 18. 11. 2013 na `ziobrando.blogspot.de`; původní URL už není dostupné. Ověřený je až repost z 12. 2. 2014 [30]. Údaj „2013" v kapitole (`:66`) je tedy pravděpodobně správný, ale doložený jen nepřímo.
- **Vernonovo užití termínu „Supporting Subdomain".** Ověřeno negativně u Evanse (*DDD Reference* zná jen Core Domain a Generic Subdomains) a pozitivně u Khononova (2021). Přímé doložení z *IDDD* (2013) se nepodařilo; atribuce Vernonovi podle `CLAUDE.md` zůstává nepotvrzená z primárního zdroje.
- **Statistiky o příčinách selhání softwarových projektů.** Standish CHAOS je dostupný jen v druhotných citacích a jeho metodika je dlouhodobě kritizovaná. Pro tvrzení na `what_is_ddd.md:46` se nenašel zdroj, který by ho podpořil v uvedené síle.
- **Kvantifikace návratnosti investice do DDD.** Žádný prošlý zdroj neuvádí časový horizont návratnosti. Tvrzení na `what_is_ddd.md:38` je nedoložitelné.
- **Datum a rozsah Symfony 8.1.** Údaje pocházejí ze stránky `symfony.com/releases` v době přístupu; před vydáním knihy ověřit znovu, protože se mění každých šest měsíců.
