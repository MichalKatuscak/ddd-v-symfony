# Studie: Subdomény: Core, Supporting, Generic

- **Kapitola:** `content/chapters/subdomains.md` (č. 02, kategorie Základy, 632 řádků)
- **Cesta:** /subdomeny
- **Typ kapitoly:** definiční
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| 02.01 Proč subdomény předcházejí všemu | 21–40 | Evans zavedl Core Domain + Generic Subdomains, Vernon dokončil trojici pojmem Supporting. Subdoména = obchodní hranice, BC = implementační | [1] domainlanguage.com, [2] kalele.io, [3] O'Reilly | Odkazy vedou na prodejní stránky, ne ke konkrétnímu tvrzení |
| 02.02 Tři kategorie | 42–90 | Definice tří kategorií přes konkurenční výhodu, investiční matice, tři destilační techniky | [2], [3] | Klasifikace stojí jen na jedné ose; chybí komplexita a volatilita |
| 02.03 Pětibodový test | 92–128 | Pět ANO/NE otázek, 3+ ANO = kandidát na Core; callout „když máte 5 Core domén" | [3], [5] | Test je autorská konstrukce, ne heuristika Khononova ani ddd-crew |
| 02.04 Anti-vzor „všechno je Core" | 130–154 | Psychologie, manažerská a technická rovina; ilustrativní scénář custom auth; distribuce kapacity 20–30/50–60/10–20 % | [4] | Procenta bez zdroje; scénář je poctivě označen jako ilustrativní |
| 02.05 Mapování na Bounded Contexts | 156–182 | Vztahy 1:1, 1:N, N:1; tabulka e-shopu s 9 subdoménami | [3] | Nejsilnější sekce kapitoly, tabulka je konkrétní |
| 02.06 Subdomény v Symfony | 184–298 | `src/Core|Supporting|Generic/`, PSR-4, `services.yaml` per subdoména | – | Klasifikace zapsaná do namespace, bez zdroje, kontroverzní |
| 02.06 – tři ukázky kódu | 299–455 | Pricelist (plný Aggregate), Order (Doctrine entita), Auth0UserProvider (adaptér) | – | Tři technické chyby a rozpory s konvencemi knihy |
| 02.07 Sourcing build/buy/partner | 457–483 | Mapování klasifikace na sourcing; varianta partnership; vendor lock-in | – | Reprodukuje Purpose Alignment Model (2009) bez atribuce |
| 02.08 Evoluce v čase | 485–516 | Tři posuny (Generic→Core, Core→Supporting, Supporting→Generic), audit po 12–18 měsících | [3] | Příklady Stripe / Dropbox / Zendesk bez zdroje |
| 02.09 Praktický postup | 518–592 | Pětikrokový workshop, šablona Domain Vision Statementu | – | DVS je Evansův vzor, kapitola ho necituje |
| 02.10 Shrnutí + FAQ | 594–621 | 5 pravidel, 6 FAQ | – | Opakuje sporné „jedna Core na produkt" |
| 02.11 Další četba | 623–632 | 6 odkazů | – | Chybí Millett & Tune, Kaiser, Nickolaisen, Tune, DDD Reference |

Kapitola je dobře strukturovaná a čte se dobře. Nejvíc prostoru dává autorským
konstrukcím – pětibodovému testu, investiční matici, adresářové struktuře
a workshopu. Odbývá naopak to, čím se výklad subdomén posunul za posledních
deset let: druhou a třetí osu klasifikace, Core Domain Charts jako techniku,
vazbu na volbu architektury a existující jména pro totéž (Purpose Alignment
Model, Wardleyho evoluce, Moorovo core/context). Působí jako uzavřený autorský
systém, který komunitní slovník nepoužívá, ačkoliv jej v „Další četbě" odkazuje.

## 2. Kanonické zdroje k tématu

**Evans, *Domain-Driven Design* (2003), kapitola 15 „Distillation".** Ověřeno
z manuskriptu [1]. Sedm vzorů: `CORE DOMAIN`, `GENERIC SUBDOMAINS`, `DOMAIN
VISION STATEMENT`, `HIGHLIGHTED CORE`, `COHESIVE MECHANISMS`, `SEGREGATED CORE`,
`ABSTRACT CORE`. Formulace Core Domain: „Boil the model down… Make the core
small. Apply top talent to the core domain… Justify investment in any other part
by how it supports the distilled core."

Kategorie „Supporting Subdomain" v knize **jako pojmenovaný vzor neexistuje**.
Slovo „supporting" se objevuje popisně – „the mass of supporting model and code",
„purifying supporting subdomains to be GENERIC". Evans pracuje s dvojicí
Core / Generic a zbytek nepojmenovává.

**Evans, *DDD Reference* (2015)** [2]. Ověřeno z PDF: obsah části V je totožný
se sedmi vzory z roku 2003. Dokument má legendu „* New term introduced since the
2004 book" a hvězdičkou označuje pouze `Domain Events`, `Partnership` a `Big Ball
of Mud`. **Supporting Subdomain mezi nimi není.** Potvrzuje to konvenci knihy
z `CLAUDE.md` (Partnership a BBoM patří DDD Reference) i to, že Evans trojici
nikdy nedoplnil.

**Evansovy čtyři možnosti sourcingu Generic Subdomain** (2003), které kapitola
nezmiňuje: off-the-shelf řešení, publikovaný design/model (odkaz na Fowlerovy
*Analysis Patterns*), outsourcovaná implementace, in-house implementace. Evans
uvádí u každé plusy i minusy a uzavírá: „Off-the-shelf subdomain solutions
usually are not worth the trouble, but are worth investigating."

**Vernon, *IDDD* (2013), kapitola 2 „Domains, Subdomains, and Bounded Contexts".**
Ověřeno z oficiálních sample pages vydavatele [3]: rejstřík má samostatné heslo
`Supporting Subdomains` („defined, 52"), dále `Generic Subdomains, 52`, dvojici
`Problem space` / `Solution space` (56–57) a položku „identifying multiple
Subdomains in one Bounded Context, 49–52, 57–58". **Atribuce v kapitole je
správná** – kategorie Supporting jako pojmenovaný člen trojice pochází od
Vernona. Doplnit lze jen upřesnění, že Evans slovo používá popisně už v 2003.

**Vernon, *DDD Distilled* (2016), kapitola 3.** Prosazuje ideál 1:1 mezi
subdoménou a Bounded Contextem s výhradou, že v praxi to nejde vždy [4].

**Khononov, *Learning DDD* (2021), kapitola 1.** Hlavní posun: klasifikace stojí
na **třech** osách, ne na jedné.

| Typ | Konkurenční výhoda | Komplexita | Volatilita | Sourcing |
|---|---|---|---|---|
| Core | ano | vysoká | vysoká | build in-house |
| Generic | ne | **vysoká** | nízká | buy / adopt |
| Supporting | ne | **nízká** | nízká | build in-house nebo outsource |

Zdroje [5], [6]. Core i Generic jsou komplexní; rozdíl je v tom, že Generic je
**vyřešený** problém, který se nemění. Supporting je z podstaty jednoduchý –
„glorified CRUD". Z toho plyne diagnostická heuristika: **složitost ve Supporting
subdoméně je signál skryté Core subdomény** [7]. Khononov na svém blogu (2018)
[7] výslovně píše, že organizace **může mít víc Core subdomén**, pokud soutěží na
několika osách, a že celý podnik může být složen jen ze Supporting a Generic.

**Khononov – vazba typu subdomény na architekturu.** Druhá polovina knihy staví
rozhodovací strom, kde typ subdomény kaskádovitě určuje vzor obchodní logiky
(Transaction Script → Active Record → Domain Model → Event-Sourced Domain Model)
a architektonický styl (vrstvená → ports & adapters → CQRS) [5], [6]. Transaction
Script ani Active Record nejsou vhodné pro Core.

**Millett & Tune (2015)** [8] a Millettova esej ve sborníku *15 Years of DDD* [9]
přinášejí dvě věci, které kapitola nemá. Za prvé heuristiku **„code for
replacement rather than reuse"**: u Supporting se vyplácí stavět model s vědomím,
že bude nahrazen – „Strive for good boundaries rather than perfect models.
Boundaries are often harder to change than a model." Za druhé **příklad
Pottermore**: Core doména e-shopu s Harry Potterem nebyla ta část, kterou
zákazník vidí, ale neviditelné vodoznakování knih místo DRM.

**Nick Tune / ddd-crew – Core Domain Charts** [10]. Dvě osy: komplexita (y)
a business differentiation (x). Tři kategorie nejsou škatulky, ale oblasti
v souvislé rovině. README uvádí smysl techniky: „The true power of this technique
is the conversations that it triggers, especially cross-discipline. Complexity is
something that engineers can gauge whereas business differentiation is provided by
product managers or business stakeholders." Součástí je katalog devíti otázek pro
obě osy (esenciální doménová, akcidentální technická a operační komplexita;
obtížnost pro nováčka i pro stávajícího konkurenta). Licence CC BY 4.0.

**Nick Tune – Core Domain Patterns** [11]. Jemnější slovník: *Decisive Core*,
*Big Bet / Disruptive Core*, *Short-term Core*, *Hidden Core*, *Suspect
Supporting* (vysoká komplexita, nízká diferenciace – buď akcidentální složitost,
nebo chybná klasifikace), *Table Stakes Former Core*, *Commoditised Core*
(Elasticsearch), *Black Swan Core* (Slack). Jde přesně o přechody, které popisuje
sekce 02.08 – jen pojmenované.

**Nickolaisen – Purpose Alignment Model**, publikovaný v Pixton, Nickolaisen,
Little, McDonald: *Stand Back and Deliver* (Addison-Wesley, 2009) [12]. Osy
mission critical × market differentiating, kvadranty **Differentiating**
(excelovat), **Parity** (zjednodušit a standardizovat), **Partner** (hledat
externího partnera), **Who cares** (dělat minimálně). ddd-crew ho vede jako
nástroj kroku „Strategize" [13]. Sekce 02.07 tento model reprodukuje bez atribuce
včetně varianty „partnerství".

**Kaiser, *Architecture for Flow* (2022)** [14]. Napojuje klasifikaci na Wardleyho
evoluční osu (genesis → custom-built → product/rental → commodity/utility): Core
začíná v genesis nebo custom-built, Supporting bývá custom-built či product,
Generic sedí v product/rental až commodity. Metoda per stupeň: build in-house
v genesis a custom-built, off-the-shelf v product, outsourcing na utility
dodavatele v commodity. To je mechanismus, který kapitola v 02.08 popisuje
anekdotami, ale nepojmenovává.

**Moore, *Dealing with Darwin* (2005)** [15]. Business-strategická předloha celé
myšlenky: core vytváří diferenciaci, context je všechno ostatní, co musíte dělat,
abyste zůstali v byznysu. Pravidlo: context minimalizovat, automatizovat nebo
outsourcovat.

## 3. Stav praxe a posuny

**Z tří škatulek do souvislé roviny.** Core Domain Charts a Tunovy Core Domain
Patterns [10], [11] nahradily otázku „do které ze tří kategorií to patří"
otázkou „kde na dvou osách to leží a kam se to posouvá". Tři kategorie zůstávají
jako hrubý jazyk pro management, samotné rozhodování se dělá na grafu.

**Klasifikace se stala krokem procesu, ne samostatným cvičením.** DDD Starter
Modelling Process [13] má osm kroků; klasifikace je krok „Strategize" a stojí mezi
„Decompose" (rozdělení EventStormu na subdomény) a „Connect" (Context Mapping).
Předpokládá tedy, že subdomény vznikají z Big Picture EventStormingu, ne
z brainstormu capability.

**Identifikace hranic má vlastní nástroje.** Vedle EventStormingu doporučuje
ddd-crew Business Capability Modelling, Design Heuristics a **Independent Service
Heuristics** [16] – sadu otázek typu „dala by se tato část provozovat jako
samostatný SaaS produkt?", kterou vymysleli autoři Team Topologies a rozvinula DDD
komunita. ISH je formulovaná tak, aby jí rozuměl i netechnický účastník.

**Vazba klasifikace na architekturu se stala mainstreamem.** Khononovův
rozhodovací strom (typ subdomény → vzor obchodní logiky → architektonický styl →
testovací strategie) je dnes nejcitovanější praktický výstup klasifikace [5], [6],
a zároveň nejkritizovanější – recenzenti mu vytýkají přílišnou preskriptivnost.

**Vazba na organizaci.** Kaiser [14] a ddd-crew propojili subdomény s Team
Topologies: Core si zaslouží stream-aligned tým s end-to-end vlastnictvím, Generic
se řeší přes platform team nebo dodavatele. Core Domain Charts mají variantu, která
do grafu kreslí i interaction modes.

**AI a LLM posunuly hranici Generic.** Co bylo v roce 2020 Supporting (klasifikace
textu, extrakce dat z dokumentů, sumarizace), je dnes běžně dostupné jako API.
Tvrdá data k tomuto posunu jsem v žádném primárním DDD zdroji nedohledal – viz
„Neověřené". Kapitola by k tomu měla mít alespoň jednu větu, protože jde
o největší jednorázový posun hranice Generic za dobu existence DDD.

## 4. Symfony / PHP specifika

**Symfony 8.0** byla vydána 27. 11. 2025, vyžaduje PHP 8.4+, jde o major bez LTS
s běžnou podporou do července 2026 [17]. Kapitola cílí správně.

**Adresářová struktura.** Symfony na strukturu pod `src/` nemá názor. Matthias
Noback doporučuje `src/<BoundedContext>/{Domain,Application,Infrastructure}`, tedy
**bounded context na první úrovni** [18]. Kapitola dává na první úroveň
klasifikaci (`src/Core/`, `src/Supporting/`, `src/Generic/`) – rozbor v sekci 5.

**Vynucení hranic.** Kapitola tvrdí (`subdomains.md:294`), že strukturu hlídá code
review. V PHP to jde vynutit strojově: **Deptrac** (`deptrac/deptrac`) definuje
vrstvy nad třídami a pravidla mezi nimi, běží v CI a dokumentace explicitně zmiňuje
použití pro bounded contexty uvnitř jednoho projektu [19]; **PHPArkitect**
(`phparkitect/phparkitect`) píše architektonická pravidla jako PHP kód spouštěný
jako testy [20]. Pro kapitolu stojící na tezi „strategie se zhmotnila v adresáři"
je absence obou podstatná mezera.

**Auth0 v Symfony.** Balíček `auth0/symfony` obsahuje hotový
`Auth0\Symfony\Security\UserProvider` a `auth0.authenticator`, konfigurované
v `security.yaml` [21]. Kapitola na `subdomains.md:416–452` píše
`Auth0UserProvider` ručně – v kapitole, jejíž teze zní „Generic se kupuje,
nepíše". Poznámka k verzím: podpora Symfony 7 i 8 je v balíčku
community-contributed a označená jako experimentální.

**Doctrine ORM 3 + `symfony/uid`.** Ukázka `Order` (`subdomains.md:372–411`)
používá `#[ORM\Column(type: "uuid")]`. Doctrine typ `uuid` není součástí ORM, musí
se registrovat (`Symfony\Bridge\Doctrine\Types\UuidType` v `doctrine.dbal.types`).
V kapitole to není a čtenář, který ukázku opíše, narazí.

**`services.yaml` per subdoména.** Syntaxe v ukázce (`resource` + `exclude` +
`alias`) je platná. Za zvážení stojí zmínka o atributu `#[Autoconfigure]`.

## 5. Sporné a chybně podávané body

**S1 – „Jedna Core Doména na produkt."** (`:121`, `:127`, `:600`, FAQ `:616`.)
Khononov výslovně píše, že organizace může mít víc Core subdomén [7]; Core Domain
Charts jsou navrženy jako **portfolio** [10]. Evansovo „make the core small" je
omezení velikosti, ne počtu. Doporučení: změkčit na „Core je vzácné, ne většinové",
zrušit číselné pravidlo, kontrolní otázku „máte pět Core domén?" ponechat jako
varovný signál.

**S2 – Klasifikace stojí jen na jedné ose.** (`:46–62`.) Khononov [5], [6], [7]
i Core Domain Charts [10] používají minimálně dvě osy. Bez osy komplexity kapitola
neodliší Supporting od Generic ničím jiným než „existuje SaaS" a vůbec nepojmenuje
*Suspect Supporting* (vysoká komplexita, nulová diferenciace), což je v legacy
projektech nejčastější nález.

**S3 – „V Generic subdoméně je vlastní kód anti-vzor."** (`:60`.) Evans uvádí
in-house implementaci jako jednu ze čtyř legitimních možností, s výhodami „you get
just what you want and no extra" a „easy integration", a o hotových řešeních píše,
že „usually are not worth the trouble, but are worth investigating" [1]. Kniha by
měla tvrzení otočit: výchozí volba je koupit, vlastní implementace je legitimní,
pokud integrační náklad převyšuje implementační. Scénář s custom auth zůstává
platný – jde o extrém, ne o pravidlo.

**S4 – Klasifikace zapsaná do PSR-4 namespace.** (`:191–260`.) Kapitola sama tvrdí,
že klasifikace stárne a mění se každých 12–18 měsíců. Struktura `src/Core/Pricing/`
ale znamená, že překlasifikace Pricing na Supporting je přejmenování namespace
napříč projektem – tedy přesně ta změna, kterou tým odkládá. Noback [18] a praxe
modulárních monolitů dávají na první úroveň doménové jméno (`src/Pricing/`)
a klasifikaci drží v dokumentaci a v Core Domain Chartu. Kniha má obě varianty
postavit vedle sebe a přiznat trade-off: struktura podle klasifikace je pedagogicky
silná a provozně křehká.

**S5 – Problem space / solution space.** (`:25`, `:27`.) Rozlišení pochází od
Vernona (2013) [3], ne od Evanse – v manuskriptu 2003 se „problem space" objevuje
jednou mimochodem, „solution space" vůbec. Nick Tune upozorňuje, že dichotomie
„subdomény určuje byznys, bounded contexty inženýři" má „velmi zastaralý nádech"
a v komunitě není konsensuální [22]. Doporučení: rozlišení zachovat, doplnit
půlvětou, že hranice mezi objevením a návrhem není ostrá.

**S6 – Přisouzení pětibodového testu.** (`:94`.) Ani Khononov, ani Core Domain
Charts žádnou z pěti otázek neobsahují. Khononovovy heuristiky jsou komplexita /
volatilita / diferenciace plus otázka „dal by se ten kus prodat jako samostatný
byznys" [7]; ddd-crew nabízí devět otázek k oběma osám [10]. Test je autorský a měl
by být takto označen.

**S7 – Mapování 1:N u Core.** (`:158`.) Že Vernon cílí na 1:1, je doložitelné [4].
Zdůvodnění připsané Khononovovi („čtenářský vs. zápisový kontext") se dohledat
nepodařilo; rejstřík IDDD naopak dokládá opačný směr – víc subdomén v jednom BC
[3]. Doložit konkrétní pasáží, nebo přeformulovat bez atribuce.

**S8 – Numerická tvrzení bez zdroje.** „5–10 % kapacity na Generic" (`:62`),
„20–30 / 50–60 / 10–20 %" (`:154`), „Supporting ≈ 60 % objemu kódu" (`:601`),
„8–15 subdomén u středního produktu, 20–40 u enterprise" (`:532`, `:616`). Žádné
z těchto čísel se v primárních zdrojích nenachází. Označit jako autorská rules of
thumb, nebo doplnit zdroj.

**S9 – Khononovovy příklady Uber a Google.** (`:50`.) Že Khononov používá právě
tyto příklady, se z dostupných zdrojů potvrdit nepodařilo. Doložitelná alternativa:
Pottermore a vodoznakování knih [9] – navíc lépe ilustruje, že Core je
neintuitivní.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | `subdomains.md:46–62` | Klasifikace jen podle diferenciace; chybí osy komplexity a volatility | Tabulka tří os; explicitně: Generic je komplexní ale vyřešené, Supporting jednoduché |
| G2 | sporné | `:121–127`, `:600`, `:616` | „Jedna Core Doména na produkt" odporuje Khononovovi i ddd-crew | Změkčit na „Core je vzácné"; zrušit numerické pravidlo |
| G3 | sporné | `:60` | „Vlastní kód v Generic je anti-vzor" – Evans uvádí in-house jako jednu ze čtyř variant | Přeformulovat na „výchozí volba je koupit"; doplnit Evansovy čtyři varianty |
| G4 | chybí | sekce 02.03 | Core Domain Charts jsou jen odkaz [5], technika není vysvětlena | Nová podsekce: dvě osy, devět otázek, cross-disciplinární konverzace jako přínos |
| G5 | chybí | sekce 02.08 | Přechody popsané anekdotami; existují pro ně vzory (Tune) a mechanismus (Wardley / Kaiser) | Doplnit Commoditised Core, Table Stakes Former Core, Hidden Core, Suspect Supporting; zmínit evoluční osu |
| G6 | nepodložené | `:94` | Pětibodový test připsán Khononovovi a ddd-crew, u nich se nevyskytuje | Označit jako autorský; odkázat na skutečné heuristiky obou zdrojů |
| G7 | nepodložené | `:62`, `:154`, `:532`, `:601`, `:616` | Pět numerických pravidel bez zdroje | Označit jako autorská rules of thumb, nebo doložit |
| G8 | chybí | sekce 02.07 | Sourcing reprodukuje Purpose Alignment Model (2009) včetně kvadrantu Partner, bez atribuce | Doplnit atribuci; tabulka mapující kvadranty na tři kategorie |
| G9 | sporné | `:184–298` | Klasifikace v namespace je v přímém rozporu se sekcí 02.08 o re-klasifikaci | Postavit vedle sebe `src/<Subdomena>/` (Noback) a `src/Core|Supporting|Generic/`; přiznat trade-off |
| G10 | chybí | sekce 02.06 | Strukturu prý hlídá code review; neuvádí Deptrac ani PHPArkitect | Doplnit ukázku pravidla v Deptracu |
| G11 | nepodložené | `:416–452` | Vlastní `Auth0UserProvider`, ačkoliv `auth0/symfony` dodává `Auth0\Symfony\Security\UserProvider` | Nahradit ukázku konfigurací `security.yaml` – silněji podpoří tezi kapitoly |
| G12 | chybí | `:372–411` | `#[ORM\Column(type: "uuid")]` bez zmínky o registraci Doctrine typu ze `symfony/uid` | Doplnit poznámku nebo ukázku `doctrine.yaml` |
| G13 | nepodložené | `:408` | `getId(): string` vrací `Uuid` – ukázka není spustitelná | Opravit návratový typ, nebo `->toRfc4122()` |
| G14 | sporné | `:262` vs. `:309`, `:311` | Text říká, že `Money` patří do `App\Shared\`, ukázka ho importuje z `App\Core\Pricing\Domain\ValueObject`; `composer.json` deklaruje `App\Shared\` i `App\SharedKernel\` | Sjednotit na jeden shared namespace napříč knihou |
| G15 | sporné | `:314–357` | `Pricelist` má veřejný `__construct` místo pojmenovaného konstruktoru a hází bare `\DomainException` – proti `CLAUDE.md` | Přidat `Pricelist::create()` a pojmenovanou výjimku |
| G16 | sporné | `:374–411` | Třída `Order` koliduje s kanonickým `Order` z `basic_concepts.md:570` a `aggregate_design.md:366` (`Order::place()`, `CustomerId`, `addItem()`) | Přejmenovat na jinou doménu (`SupportTicket`, `StockReceipt`), nebo sladit |
| G17 | mělké | `:84–90` | Ze sedmi Evansových destilačních vzorů kapitola popisuje tři; chybí Abstract Core a Distillation Document | Tabulka všech sedmi vzorů s poznámkou, kdy který |
| G18 | chybí | celá kapitola | Chybí Geoffrey Moore (core vs. context, 2005) jako business-strategický předchůdce | Dvě věty v 02.01 |
| G19 | chybí | sekce 02.09 | Krok 1 „vypsat capability" bez metody; komunitní praxe začíná Big Picture EventStormingem a ISH | Provázat s kapitolou o EventStormingu; zmínit Independent Service Heuristics |
| G20 | mělké | `:151`, `:174`, `:179`, `:296` | Řídké prolinkování; chybí `/event-storming`, `/team-topologies`, `/architektonicke-styly`, `/migrace-z-crud`; ACL míří na `/co-je-ddd#strategic-design` místo `/context-mapping#acl` | Opravit cíl ACL, doplnit čtyři kontextové odkazy |
| G21 | chybí | 02.02 / 02.06 | Chybí Khononovova vazba typ subdomény → vzor obchodní logiky → architektonický styl | Odkázat na `/kdy-nepouzivat-ddd#hybrid-subdomain`, kde tabulka už je |
| G22 | chybí | sekce 02.09 | Řeší jen greenfield; brownfield klasifikace uvnitř Big Ball of Mud chybí | Odstavec s odkazem na `/migrace-z-crud` a variantu chartu Architecture Migration |

## 7. Doporučení k přepisu

**P1-1 — Doplnit druhou a třetí osu klasifikace (komplexita, volatilita).**
Bez nich kapitola neodliší Supporting od Generic jinak než tržní dostupností
a neumí popsat nejčastější reálný nález – komplexní nediferencující subdoménu.
Jde o hlavní posun výkladu za deset let. *Rozšíření sekce 02.02, ~35 řádků, plus
úprava investiční matice.*

**P1-2 — Opravit „jedna Core Doména na produkt".** Primární zdroje počítají s víc
Core subdoménami. Tvrzení je v kapitole na čtyřech místech včetně shrnutí a FAQ,
takže se z něj stává hlavní memorovatelné pravidlo – a je nesprávné. *Oprava
calloutu `:118–128`, bodu 1 shrnutí a jedné FAQ položky, ~15 řádků.*

**P1-3 — Změkčit „vlastní kód v Generic je anti-vzor" a doplnit Evansovy čtyři
možnosti sourcingu.** Evansova formulace je opatrnější než ta, kterou mu kapitola
implicitně přisuzuje. Čtyři varianty zároveň zaplní díru mezi 02.02 a 02.07.
*Přepis odstavce `:60` a nová tabulka v 02.07, ~25 řádků.*

**P1-4 — Opravit technické chyby v ukázkách.** `getId(): string` vracející `Uuid`,
chybějící registrace Doctrine typu `uuid`, ruční `Auth0UserProvider` navzdory
hotovému providerovi, rozpor `App\Shared\` vs. `App\SharedKernel\` a kolize třídy
`Order` s kanonickým příkladem knihy. Kapitola v Základech nesmí učit vzory, které
se o dvě kapitoly dál rozcházejí. *Oprava tří ukázek, ~30 řádků změn.*

**P1-5 — Označit pětibodový test jako autorský a doplnit chybějící atribuce.**
Test stojí za zachování, ale připsání Khononovovi a ddd-crew je nepodložené. Sem
patří i atribuce Purpose Alignment Modelu Nickolaisenovi v 02.07. *Úprava vět
`:94` a `:459`, ~8 řádků.*

**P2-1 — Nová podsekce o Core Domain Charts.** Kapitola techniku odkazuje, ale
nevysvětluje, ačkoliv jde o standardní nástroj kroku „Strategize" a jediný, který
dává klasifikaci vizuální a diskutovatelnou formu. *Nová sekce ~35 řádků, včetně
nového diagramu (dvě osy plus rozmístění subdomén z tabulky 02.05).*

**P2-2 — Přepsat 02.08 se slovníkem Core Domain Patterns a Wardleyho evoluce.**
Tři anekdoty fungují, ale zůstávají anekdotami. Pojmenované vzory a evoluční osa
(přes Kaiser 2022) dávají přechodům mechanismus a čtenáři jazyk, kterým je
pojmenuje ve vlastním produktu. *Přepis sekce, ~40 řádků.*

**P2-3 — Přiznat trade-off adresářové struktury a doplnit strojové vynucení.**
Struktura `src/Core|Supporting|Generic/` je v rozporu s vlastní tezí kapitoly
o re-klasifikaci. Postavit vedle sebe variantu podle subdomény a doplnit Deptrac /
PHPArkitect místo spoléhání na code review. *Úprava 02.06, ~25 řádků.*

**P2-4 — Provázat kapitolu s okolím knihy.** Chybí odkazy na `/event-storming`,
`/team-topologies`, `/migrace-z-crud` a `/architektonicke-styly`; ACL míří na
špatný cíl. Kapitola 02 je vstupní uzel do Základů a měla by rozvádět dál.
*Šest úprav odkazů, ~6 řádků.*

**P3-1 — Nahradit nedoložený příklad Uber/Google Pottermorem.** Millettův příklad
je doložitelný [9] a lépe učí, že Core je neintuitivní. *Přepis odstavce `:50`,
~8 řádků.*

**P3-2 — Doplnit úplný výčet sedmi Evansových destilačních vzorů.** Kapitola uvádí
tři; Abstract Core a Distillation Document chybí, DVS je odtržený do 02.09.
*Tabulka ~12 řádků.*

**P3-3 — Odstavec o posunu hranice Generic vlivem LLM.** Klasifikace textu,
extrakce z dokumentů a sumarizace se za tři roky posunuly ze Supporting do Generic.
Kapitola má sekci o evoluci a tento posun v ní nesmí chybět – bez tvrdých čísel,
protože doložitelný DDD zdroj zatím není. *~8 řádků, případně křížový odkaz na
kapitolu o DDD a AI.*

## 8. Otevřené otázky pro autora

1. **Zůstane `src/Core|Supporting|Generic/` jako doporučení knihy?** Je to
   pedagogicky nejsilnější ukázka v kapitole a zároveň jediná, kterou žádný
   primární zdroj nepodporuje. Varianty: ponechat s přiznaným trade-offem;
   přesunout do calloutu jako didaktickou variantu a jako hlavní doporučit
   `src/<Subdomena>/`; ukázat obě rovnocenně. Ovlivní i kapitolu o implementaci.
2. **Kolik prostoru dostane Khononovova vazba na architekturu?** Tabulka je už
   v `when_not_to_use_ddd.md:353–360`. Duplikovat v kapitole 02, kde je
   logičtější, nebo odkázat?
3. **Zavést slovník Core Domain Patterns?** Přidá osm pojmů do druhé kapitoly.
   Alternativa: zmínit jen Hidden Core a Suspect Supporting, zbytek odkazem.
4. **Zůstává pětibodový test hlavním nástrojem kapitoly?** Po doplnění Core Domain
   Charts bude mít kapitola dva nástroje na totéž. Návrh: test jako rychlý filtr
   pro jednotlivce, chart jako výstup workshopu.
5. **Šablona DVS zabírá 37 řádků.** Vyplatí se takový rozsah v definiční kapitole,
   nebo ji zkrátit na polovinu a plnou verzi dát do referenční části?
6. **Jak daleko jít s AI/LLM posunem hranice Generic?** Riziko časové nestability
   textu je vysoké. Jedna věta, nebo vlastní odstavec?

## 9. Bibliografie

### Ověřené zdroje

`[1]` Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*, Final Manuscript, 2003. Kapitola 15 „Distillation": CORE DOMAIN, GENERIC SUBDOMAINS, čtyři varianty sourcingu. https://fabiofumarola.github.io/nosql/readingMaterial/Evans03.pdf (staženo 2026-09-03)

`[2]` Eric Evans — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, 2015. Část V; legenda „* New term introduced since the 2004 book". https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf (staženo 2026-09-03)

`[3]` Vaughn Vernon — *Implementing Domain-Driven Design*, Addison-Wesley 2013. Sample pages vydavatele (obsah + rejstřík): kapitola 2, hesla Supporting Subdomains, Generic Subdomains, Problem/Solution space. https://ptgmedia.pearsoncmg.com/images/9780321834577/samplepages/0321834577.pdf (staženo 2026-09-03)

`[4]` Vaughn Vernon — *Domain-Driven Design Distilled*, Addison-Wesley 2016, kapitola 3 „Strategic Design with Subdomains". https://www.oreilly.com/library/view/domain-driven-design-distilled/9780134434964/ch03.html (přístup 2026-09-03)

`[5]` Vlad Khononov — *Learning Domain-Driven Design*, O'Reilly 2021. https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/ (přístup 2026-09-03)

`[6]` Candost Dagdeviren — poznámky k *Learning DDD*, část I Strategic Design (tři osy; „core and generic are complex, supporting are simple"). https://candost.blog/books/learning-domain-driven-design-part-1-strategic-design/ (přístup 2026-09-03)

`[7]` Vlad Khononov — *Revisiting the Basics of Domain-Driven Design*, 2018. Heuristiky komplexity, výslovné připuštění více Core subdomén. https://vladikk.com/2018/01/26/revisiting-the-basics-of-ddd/ (přístup 2026-09-03)

`[8]` Scott Millett, Nick Tune — *Patterns, Principles and Practices of Domain-Driven Design*, Wiley/Wrox 2015, kapitola 3 „Focusing on the Core Domain". https://www.wiley.com/Patterns,+Principles,+and+Practices+of+Domain+Driven+Design-p-9781118714706 (přístup 2026-09-03)

`[9]` Scott Millett — *Distilling DDD Into First Principles*, in: Mathias Verraes (ed.), *15 Years of DDD*. „Code for replacement rather than reuse", příklad Pottermore. https://github.com/mathiasverraes/15yearsddd/blob/master/manuscript/scott-millett/essay.md (staženo 2026-09-03)

`[10]` DDD Crew — *Core Domain Charts*, CC BY 4.0. Osy komplexita × diferenciace, katalog otázek, varianta Architecture Migration. https://github.com/ddd-crew/core-domain-charts (staženo 2026-09-03)

`[11]` Nick Tune — *Core Domain Patterns*. https://nicktune.substack.com/p/core-domain-patterns-941f89446af5 (přístup 2026-09-03)

`[12]` Pollyanna Pixton, Niel Nickolaisen, Todd Little, Kent McDonald — *Stand Back and Deliver: Accelerating Business Agility*, Addison-Wesley 2009. Purpose Alignment Model. https://insideproduct.co/purpose-based-alignment-model/ (přístup 2026-09-03)

`[13]` DDD Crew — *DDD Starter Modelling Process*. Kroky „Decompose" a „Strategize". https://github.com/ddd-crew/ddd-starter-modelling-process (staženo 2026-09-03)

`[14]` Susanne Kaiser — *Architecture for Flow: Adaptive Systems with Domain-Driven Design, Wardley Mapping, and Team Topologies*, Addison-Wesley 2022, ISBN 9780137393039. https://www.informit.com/articles/article.aspx?p=3222355&seqNum=3 (přístup 2026-09-03)

`[15]` Geoffrey Moore — *Dealing with Darwin*, 2005. Core vs. context. https://www.inc.com/tech-blog/interviewing-geoffrey-moore-core-versus-context.html (přístup 2026-09-03)

`[16]` Matthew Skelton, Manuel Pais a další — *Independent Service Heuristics*. https://github.com/TeamTopologies/Independent-Service-Heuristics (přístup 2026-09-03)

`[17]` Symfony — *Symfony 8.0 Release*. Vydáno 27. 11. 2025, PHP 8.4+, major bez LTS. https://symfony.com/releases/8.0 (přístup 2026-09-03)

`[18]` Matthias Noback — *Layers, ports & adapters – Part 3, Ports & Adapters*. Struktura `src/<BoundedContext>/…`. https://matthiasnoback.nl/2017/08/layers-ports-and-adapters-part-3-ports-and-adapters/ (přístup 2026-09-03)

`[19]` Deptrac — statická analýza architektonických vrstev pro PHP. https://github.com/deptrac/deptrac, https://deptrac.github.io/deptrac/ (přístup 2026-09-03)

`[20]` PHPArkitect — architektonická pravidla jako PHP kód. https://packagist.org/packages/phparkitect/phparkitect (přístup 2026-09-03)

`[21]` Auth0 — *Symfony SDK for Auth0*. `Auth0\Symfony\Security\UserProvider`; podpora Symfony 7 a 8 community-contributed a experimentální. https://github.com/auth0/symfony (přístup 2026-09-03)

`[22]` Nick Tune — *Domain, Subdomain, Bounded Context, Problem/Solution Space in DDD: Clearly Defined*. https://medium.com/nick-tune-tech-strategy-blog/domains-subdomain-problem-solution-space-in-ddd-clearly-defined-e0b49c7b586c (přístup 2026-09-03, plný text nedostupný – HTTP 403)

### Neověřené / nedohledané

- **Khononovovy příklady Core Domain (`subdomains.md:50`) – POTVRZENO 2026-09-04 z knihy
  (vlastní výtisk). Oba sedí doslovně.** Uber: *„A core subdomain is what a company does
  differently from its competitors. […] Let’s take Uber as an example. Initially, the company
  provided a novel form of transportation: ridesharing. As its competitors caught up, Uber found
  ways to optimize and evolve its core business: for example, reducing costs by matching riders
  heading in the same direction.“* Google: *„Consider another example: Google Search’s ranking
  algorithm. […] So, for Google, the ranking algorithm is a core subdomain.“*

  **Detail, který kapitole chybí a stojí za doplnění:** Khononov k Uberu dodává kritérium
  složitosti – *„A core subdomain that is simple to implement can only provide a short-lived
  competitive advantage. Therefore, core subdomains are naturally complex.“* To je test, který
  kapitola v této sekci nemá, a doplňuje její vlastní kritérium konkurenční výhody.
- **Khononovovo tvrzení o 1:N mapování u Core subdomén (`:158`) – OVĚŘENO 2026-09-04 z knihy.
  Khononov říká opak: před rozdělením koherentní funkcionality varuje.** Doslova:

  > *„One thing to beware of is splitting a coherent functionality into multiple bounded contexts.
  > Such division will hinder the ability to evolve each context independently. Instead, the same
  > business requirements and changes will simultaneously affect the bounded contexts and require
  > simultaneous deployment of the changes. To avoid such ineffective decomposition, use the rule
  > of thumb we discussed in Chapter 1 to find subdomains: identify sets of coherent use cases
  > that operate on the same data.“*

  Rozdělení jedné subdomény do více kontextů u něj tedy není „u Core často nevyhnutelné“, nýbrž
  **neefektivní dekompozice, které se má tým vyhnout**. Důvody, kdy je extrakce přesto namístě,
  uvádí jiné než kapitola: oddělení vývojových cyklů komponent a možnost škálovat funkcionalitu
  nezávisle – tedy provozní důvody, ne „čtenářský vs. zápisový kontext“.

  **Doporučení: pasáž přepsat.** Vztah 1:N v přehledu ponechat jako popis možnosti, ale
  Khononovovu pozici uvést správně – jako varování s výčtem legitimních výjimek.
- **Vernonovo doporučení k Supporting subdoméně (`:56`) – OVĚŘENO 2026-09-04 v *IDDD*.
  Kapitola mu připisuje opak toho, co píše.** Vernonovo znění:

  > *„If you are developing a Supporting Subdomain that, for various reasons, cannot be acquired
  > as a third-party Generic Subdomain, it is possible that the tactical patterns would benefit
  > your efforts. In this case consider the skill level of the team and whether or not the model
  > is new and innovative. […] If the team is capable of properly applying tactical design, and
  > the Supporting Subdomain is innovative and must endure for years in the future, this is
  > a good opportunity to invest in your software using tactical design. However, this does not
  > make this model the Core Domain.“*

  Vernon tedy u Supporting subdomény, kterou nelze pořídit jako hotovou Generic, taktické vzory
  **nezakazuje ani neředí** – naopak je označuje za dobrou příležitost k investici, pokud jsou
  splněné tři podmínky: tým to umí, model je inovativní (přidává konkrétní obchodní hodnotu
  a zachycuje zvláštní znalost, ne jen technicky zajímavou) a má vydržet roky. Zároveň dodává,
  že to z modelu nedělá Core Domain.

  **Doporučení: tvrzení na `:56` přepsat.** Místo „pro Supporting stačí lehčí varianta DDD“
  formulovat Vernonovo podmíněné kritérium. Je to použitelnější – dává čtenáři rozhodovací test
  místo paušálu.xplicitní doporučení lehčího taktického designu pro Supporting jsem z veřejných zdrojů nepotvrdil. Kontrola v IDDD, kapitola 2.
- **Přesné znění Khononovovy rozhodovací tabulky typ subdomény → vzor obchodní logiky.** Existence doložena recenzemi [6], [7]; přesné znění ne.
- **Plný text Nicka Tuna [11] – DOHLEDÁNO 2026-09-04, Substack je čitelný.** *Core Domain Patterns*,
  19. 1. 2020, definuje osm vzorů, ne jen dělení Core/Supporting/Generic: **Decisive Core**
  („extremely complex and offers maximum business differentiation potential“), **Short-term Core**
  (vysoký potenciál odlišení, nízká složitost), **Hidden Core** (nízká složitost, vysoké odlišení),
  **Table Stakes / Former Core** (kdysi inovace, dnes „no longer differentiate but still needed“),
  **Commoditised Core** (bývalé jádro se mění v „generic capability which any company can easily
  utilise“), **Black Swan Core** („completely unexpected happens and an apparent commodity becomes
  a core domain“), **Big Bet / Disruptive Core** (vysoký potenciál, neznámá návratnost)
  a **Suspect Supporting** (vysoká složitost v Supporting jako signál nahodilé složitosti).

  **Dopad na kapitolu.** Tuneův přínos není jen ilustrace tří kategorií, ale to, že kategorie jsou
  **pohyblivé v čase** – Core se komoditizuje, komodita se může stát Core. To je argument, který
  kapitola nemá a který přímo podpírá její pasáž o posunu hranice Generic. **Doporučení: nahradit
  zprostředkované převyprávění přímou citací [11] a doplnit alespoň Commoditised Core a Black Swan
  Core.** Text [22] na Mediu zůstává za HTTP 403; jeho téma (vymezení pojmů) pokrývají jiné zdroje.
- **Kvantitativní data k posunu hranice Generic vlivem LLM (2023–2026).** Žádný primární DDD zdroj s čísly; případný odstavec musí zůstat kvalitativní.
- **Auth0 PHP SDK v8 – OVĚŘENO 2026-09-04, podezření potvrzeno. Ukázka na `subdomains.md:436–439`
  nefunguje.** Oficiální API dokumentace SDK uvádí signaturu
  `public get(string $id[, RequestOptions|null $options = null ]) : ResponseInterface`. Metoda
  vrací PSR-7 `ResponseInterface` a **null vrátit nemůže** – v8 přešla na PSR-18/PSR-17 factories
  a PSR-7 odpovědi. Podmínka `if ($profile === null) { throw new UserNotFoundException(); }`
  proto nikdy neplatí; neexistujícího uživatele ukázka zamlčí a předá do `new Auth0User($profile)`
  celou HTTP odpověď.

  **Oprava:** kontrolovat stavový kód odpovědi (404) a tělo dekódovat, ne porovnávat s `null`.
  Nález patří mezi faktické chyby kódu, ne mezi stylistické – je to ukázka, kterou čtenář zkopíruje.
  Zdroj: https://auth0.github.io/auth0-PHP/classes/Auth0-SDK-Contract-API-Management-UsersInterface.html

### Doověřeno devátým kolem (2026-09-05)

**OVĚŘENO — Evans a slovo „supporting".** Kapitola tvrdí: „Evans slovo *supporting* v roce 2003
používá, ale jen popisně; jako pojmenovaný vzor v knize ani v *DDD Reference* (2015) nefiguruje."
**Potvrzeno.** Evans 2003 má jediný výskyt v souvětí *„…on purifying supporting subdomains to be
GENERIC"* — malými písmeny, zatímco své vzory píše kapitálkami (CORE DOMAIN, GENERIC SUBDOMAINS).
V *DDD Reference* se „supporting" objevuje jen ve spojení „supporting model and code" a
„supporting roles". Naproti tomu **IDDD (Vernon 2013) používá „Supporting Subdomain (2)"
s odkazovou notací vzoru** — atribuce Vernonovi je tedy správná.

**Neověřitelné lokálně:** Millett & Tune, *Patterns, Principles and Practices of DDD* — název
kapitoly 3 („Focusing on the Core Domain") a heuristika „code for replacement rather than reuse".
Ani jedno není v Evansovi, Vernonovi ani v *DDD Reference*; knihu nemám k dispozici.

### Doověřeno z vlastní knihovny (2026-09-05, druhý průchod)

Předchozí zápis tvrdil, že Millett & Tune a Khononov „nemám k dispozici". **To byla chyba —
obě knihy jsou na sdílení `//katuscakovi/Work/Knihy/DDD/` spolu s Brandolinim, Newmanem,
Richardsonem, Skeltonem & Paisem a Hohpe & Woolfem.** Ověřeno tedy proti primárním textům:

| Tvrzení kapitoly | Výsledek |
|---|---|
| Millett & Tune, kapitola 3 „Focusing on the Core Domain" | **sedí** — kap. 3, str. 31 |
| heuristika „code for replacement rather than reuse" pro Supporting | **sedí** — sekce „Build Subdomains for Replacement Rather Than Reuse", v textu *„By coding for replacement rather than reuse you can create good enough supporting subdomains…"* |
| Khononov, kapitola 1 „Analyzing Business Domains" | **sedí** |
| Khononov rozlišuje subdomény podle tří os | **sedí** — Table 1-1: Competitive advantage / Complexity / Volatility |
| Khononov „ptá se, zda by se daný kus dal prodat jako samostatný byznys" | **NESEDÍ — opraveno** |

**OPRAVENO — neopodstatněná atribuce.** Otázka „dal by se ten kus prodat jako samostatný
byznys?" **není ani v *Learning DDD*, ani v Khononovově článku *Revisiting the Basics of DDD***
(prohledáno na „standalone", „sell", „separate business" — nula výskytů v obou). Nejblíž je
příklad Google Ads, který je ale o něčem jiném (*„not a subdomain, but rather a separate
business domain"*). Věta přeformulována na to, co Khononov skutečně má: tři osy z Table 1-1.

Je to přesně ten vzor, na který upozorňuje `_STAV.md`: tvrzení připsané autoritě, které
v uvedeném zdroji není.

**Poznámka k hledání:** exact-match na „code for replacement" selže, kniha píše „coding for
replacement". Při ověřování hledat kmen, ne celou frázi.
