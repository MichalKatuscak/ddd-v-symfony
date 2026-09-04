# Studie: Event Storming a Domain Storytelling

- **Kapitola:** `content/chapters/event_storming.md` (č. 04, kategorie Základy, 700 řádků)
- **Cesta:** /event-storming
- **Typ kapitoly:** hybridní (definiční notace + narativní návod na workshop)
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| deck + úvod | 21 | ES (Brandolini 2013) a DST (Hofer & Schwentner 2021) řeší extrakci tacitních znalostí | žádné | roky sedí, atribuce sedí |
| 04.01 Proč workshop | 23–38 | dokumentace nezachytí kontradikce; workshop ano | Evans 2003 (bez lokace) | nejlepší psaná část kapitoly, věcná |
| 04.02 Co ES je | 40–53 | ES zavedl Brandolini 2013; kniha z r. 2018 jej rozdělila do tří úrovní | *Introducing EventStorming* (Leanpub, 2018), Vernon *DDD Distilled* kap. 7 | rok knihy chybný, názvy úrovní nekanonické |
| 04.03 Notace | 55–81 | tabulka 9 barev; pravidlo minulého času; Post-It kódy | žádný primární zdroj legendy | jádro sekce má tři faktické chyby (viz G5) |
| 04.04 Big Picture | 83–149 | příprava, 7 kroků, heuristiky hranic, online varianta, kdy nedělat | žádné | největší blok (67 ř.); počty účastníků i remote doporučení kolidují s primárním zdrojem |
| 04.05 Process Level | 151–199 | commands/actors/policies/externals/read models, sekvence pro Ordering BC | žádné | obsahově v pořádku, jen jinak pojmenovaná úroveň |
| 04.06 Design Level | 201–321 | agregáty, invarianty, pre-conditions, PHP draft, komentáře v kódu | žádné | 121 řádků, z toho 80 kód; nejdelší sekce kapitoly |
| 04.07 Domain Storytelling | 323–385 | notace, příklad, srovnávací tabulka s ES, egon.io walkthrough | kniha 2021, domainstorytelling.org, egon.io | chybí koncept *scope*, číslování v příkladu je sporné |
| 04.08 Anti-vzory | 387–437 | 6 warn calloutů o vedení workshopu | jedna zmínka Brandoliniho | vlastní taxonomie, nepropojená s kanonickými patterny |
| 04.09 Po workshopu | 439–562 | 4 artefakty, struktura repa, commit konvence, první PR | žádné | 124 řádků autorské praxe bez opory ve zdroji |
| 04.10 Re-storming | 564–578 | frekvence 6–12 měsíců, diff jako priorita refaktoringu | žádné | 15 řádků, nejkratší sekce; myšlenka diffu je hodnotná |
| 04.11 Most do testů | 580–663 | invariant = test case, PHPUnit + KernelTestCase | žádné | dobrá vazba na kap. 17; jen pro ES, ne pro DST |
| 04.12 Shrnutí + FAQ | 665–690 | 6 FAQ položek | žádné | FAQ o počtu lidí a o online formátu odporují primárnímu zdroji |
| 04.13 Další četba | 692–700 | 7 odkazů | Leanpub, eventstorming.com, DST, egon.io, Vernon, Evans, Miro | chybí Brandoliniho esej o pivotal events, Khononov, Rayner, Baas-Schwegler |

Kapitola je návodová, ne referenční. Devět z třinácti sekcí popisuje *jak workshop uřídit*, jen jedna (04.03)
definuje notaci — a právě ta je nejméně podložená. Nejvíc prostoru dostává překlad workshopu do Symfony kódu
(04.06 a 04.11 dohromady 205 řádků, tedy 29 % kapitoly), což je pro tuto knihu správná volba a odlišuje ji
od anglických textů o Event Stormingu. Odbytá je naopak celá vrstva facilitace lidí: kapitola řeší catering
a fixy, ale ne psychologické bezpečí, ranking ani kognitivní zkreslení — přitom právě tomu se komunita
od roku 2020 věnuje nejvíc. Druhá díra je kritika: šest anti-vzorů *vedení* workshopu, ale ani věta o tom,
co Event Storming principiálně neumí.

## 2. Kanonické zdroje k tématu

### Vznik a autorství Event Stormingu

Alberto Brandolini techniku nevymyslel jako metodu, ale jako zkratku. Sám její vznik popisuje větou
*„Ouch I have no time to draw a precise UML diagram, let's do this instead"* [6]. Chronologie je tato:

**2012** — Italian Agile Day, prezentace pod názvem *event-based modelling workshop*.
**2013 (léto)** — Brandolini nachází jméno *EventStorming* po experimentech v Belgii a Polsku
během Vernonova IDDD tour; v listopadu vychází první blogový post, v únoru 2014 publikovaná
verze *Introducing Event Storming* na blogu Avanscoperta [6].

Původní účel byl **taktický**: rychlé odhalení hranic agregátů a kontextů. Brandolini v původním postu píše,
že model *„allows for a quick determination of Context and Aggregate boundaries"* [6]. Strategické použití
(Big Picture) přišlo až později. V přednášce *50.000 Orange Stickies Later* (ExploreDDD Denver 2017,
KanDDDinsky 2017) Brandolini tuto trajektorii shrnuje sám: ES začal jako nástroj pro objevování agregátů,
stal se učební pomůckou pro „DDD-illiterates" a nakonec platformou pro kolaborativní modelování od byznysu
po implementaci [24]. Kapitola tuto trajektorii nezmiňuje a prezentuje ES rovnou jako trojúrovňovou metodu.

### Tři formáty — kanonické názvy

Oficiální web i Brandoliniho firma Avanscoperta uvádějí shodně tři formáty [1][8]:

1. **Big Picture EventStorming** — celá obchodní linie, 15–30 účastníků (typicky 25–30) [8],
   podle ddd-crew 10–30+ na jednom papírovém rolu [9].
2. **Process Modelling EventStorming** — jeden end-to-end proces včetně variant; zavádí přísnou gramatiku,
   ale nevstupuje do software designu [1][8].
3. **Software Design EventStorming** — napojení na implementaci, přidává agregáty a bounded contexty [1][8].

Označení **Design Level** a **Process Level**, které kapitola používá, jsou komunitní zkratky. Brandolini
sám pojmenoval oficiální Miro šablony *EventStorming Process Modelling* a *EventStorming Software Design* [11][12].
Web eventstorming.com navíc uvádí ještě marketingovější členění podle záměru — *Improve, Envision, Explore, Design* [1] —
což je jiná osa než tři formáty a v komunitě se neujalo.

### Barevná legenda — autoritativní podoba

Nejblíž k autoritativnímu zdroji je **EventStorming Glossary & Cheat Sheet** organizace ddd-crew,
kterou sepsal Kenny Baas-Schwegler s odkazy přímo na *Introducing EventStorming* a na Brandoliniho esej
z *DDD: The First 15 Years* [9][10]. Používá formulaci *„the official EventStorming colour is …"*:

| Prvek | Oficiální barva | Formát | Poznámka |
|---|---|---|---|
| Domain Event | oranžová | všechny | sloveso v minulém čase |
| Hot Spot | **neonově růžová**, natočená o ~45° | všechny | natočení je součást notace |
| Opportunity | zelená | Big Picture | pozitivní protějšek hot spotu |
| Actor / Agent | malá **žlutá** | Big Picture | osoba, tým, oddělení |
| System | **široká růžová** | Big Picture | nasaditelný IT systém, i Excel |
| Value | malá červená / zelená | Big Picture | kladná / záporná hodnota |
| Policy | **velká lila** | Process Modelling | „whenever X, we do Y" |
| Command / Action | modrá | Process Modelling | se stakeholdery se lépe drží slovo *Action* |
| Query Model / Information | zelená | Process Modelling | informace pro rozhodnutí actora |
| Constraint | **velká žlutá** | Software Design | *„It was called an aggregate before, which is now officially a legacy word in EventStorming"* [9] |

Dvě věci z toho stojí za zdůraznění, protože je kapitola má jinak. **Aggregate už není oficiální termín** —
prvek se jmenuje **Constraint** (nebo business rule) a je na **velké žluté** lepce; důvod je jazykový,
slovo *aggregate* nedává smysl doménovému expertovi [9]. **Bounded Context nemá vlastní barvu** —
v Big Picture se objevuje jako *emerging bounded context*, tedy hranice tažená páskou nebo čarou, ne lepka [9].
Zelená pak nese v různých formátech dva významy (Opportunity vs. Query Model); není to nekonzistence,
v jednom workshopu se oba prvky nepoužijí současně.

### Pivotal events a hranice kontextů

Kanonickým zdrojem pro pivotal events a jejich vztah k Bounded Contextům je Brandoliniho esej
*Discovering Bounded Contexts with EventStorming* ve sborníku *Domain-Driven Design: The First 15 Years*
(Leanpub, 2017) — na ten se odvolává i ddd-crew glossary při ilustraci pivotal events, swimlanes
a emergent bounded contexts [9]. Pivotal events se značí **svislými čarami napříč časovou osou**,
ne šipkou pod osou. Kapitola ten zdroj vůbec necituje, přestože sekce 04.04.3 stojí přesně na jeho obsahu.

### Stav knihy *Introducing EventStorming*

Kniha **není dokončená a nikdy nevyšla v roce 2018**. Leanpub k datu studie uvádí: 70 % hotovo,
poslední aktualizace **26. srpna 2021**, 416 stran, glosář ~40 % [5]. Autor sám píše, že je kniha
*„still in progress, after all these years"*. Citace „(Leanpub, 2018)" na třech místech kapitoly
(řádky 44, 412, 694) je tedy chybná a čtenáře uvádí v omyl o dostupnosti materiálu.

### Domain Storytelling

Kniha: Stefan Hofer & Henning Schwentner, *Domain Storytelling: A Collaborative, Visual, and Agile Way
to Build Domain-Driven Software*, Addison-Wesley Professional, řada Vernon Signature Series,
7. září 2021, 288 stran, ISBN 978-0-13-745891-2 [15]. Kapitola má atribuci správně.

Notace (piktografický jazyk) [14]:

- **Actor** — osoba, skupina osob, nebo softwarový systém. Každý actor se v jednom příběhu kreslí **jen jednou**.
- **Work Object** — dokument, fyzická věc, digitální objekt. Kreslí se **znovu u každé aktivity**,
  i když jde o tutéž věc — protože se během příběhu mění její stav či médium.
- **Activity** — číslovaná šipka se slovesem. Číslo patří aktivitě, ne jednotlivé šipce.
- **Annotation** — varianty, volitelné kroky, možné chyby, doménové pojmy.
- **Group** — rámeček: opakované kroky, lokality, organizační hranice, subdomény.

Gramatika věty: *kdo (actor) dělá co (activity) s čím (work object) s kým (jiný actor)* [14].

Klíčový koncept, který kapitola vynechává, je **scope** — trojice os, podle kterých se každý domain story
klasifikuje [14]:

1. **Granularity** — coarse-grained (přehled) ↔ fine-grained (detail).
2. **Point in time** — **AS-IS** (jak to funguje dnes) ↔ **TO-BE** (jak to má fungovat).
3. **Domain purity** — **pure** (bez softwaru) ↔ **digitalized** (se softwarem). Příběh musí být jedno,
   nebo druhé; míchat je nelze.

Typická cesta projektu vede přes *fine-grained / as-is / pure* → *coarse-grained / as-is / pure* →
*fine-grained / to-be / digitalized*. Bez těchto tří os je Domain Storytelling jen „kreslení šipek".
Kniha věnuje samostatnou kapitolu (č. 7) vztahu k ostatním metodám — DDD, EventStorming, User Story Mapping,
Example Mapping, Storystorming, Use Cases, UML, BPMN — a kapitoly 11 a 12 cestě od příběhu k požadavkům
a ke kódu [15].

## 3. Stav praxe a posuny

**Remote se z nouzového režimu stal samostatným formátem — a Brandolini k němu má tvrdý postoj.**
V březnu 2020 publikoval *Remote EventStorming* s explicitním doporučením podle formátu [7]:

- **Big Picture remote: „Don't even try."** Formát ztrácí příliš mnoho — paralelní konverzace, lokální
  diskuse u části stěny, řeč těla, celodenní ponoření. Jeho vlastní pokus označil za *„disturbingly
  dysfunctional"* i s expertními účastníky.
- **Process Modelling remote: podmínečně.** Půlden, 5–15 lidí, a doporučení „každá třetí session naživo".
  Podmínkou je, že tým už formát zná z prezenční verze.
- **Software Design remote: nejlépe snesitelné.** Menší rozsah, 90 minut, technické publikum.

Brandolini navíc doporučuje **remote session ani nenazývat EventStormingem**, aby si tým s tím jménem
nespojil špatnou zkušenost [7]. Do patternů přibyl *Frame Sorting* — v online workshopu se místo přesouvání
digitálních lepek pracuje s rámečky [2].

**Facilitace se stala samostatnou disciplínou.** Kniha *Collaborative Software Design: How to facilitate
domain modeling decisions* (van Kelle, Verschatse, Baas-Schwegler, Manning 2024) [21] pokrývá to, co
Brandoliniho materiály jen naznačují: vliv rankingu a hierarchie v místnosti, kognitivní zkreslení,
práci s konfliktem a odporem, a jak z workshopu udělat *udržitelné* rozhodnutí, ne jen fotku. Ke stejné
vrstvě patří check-in / check-out a explicitní pracovní dohody, které ddd-crew cheat sheet přebírá
z Deep Democracy [9]. Za posledních pět let je to největší posun v praxi a kapitola ho nereflektuje.

**Event Storming se posunul ze „workshopu před projektem" na nástroj modernizace.** Nick Tune používá
Big Picture EventStorming jako jeden ze čtyř hlavních nástrojů v knize *Architecture Modernization*
(Manning, červen 2024) — vedle Wardley Mappingu, product taxonomy a Team Topologies [22]. Kapitola 05
(Team Topologies) i kapitola 18 (Migrace z CRUD) tímto propojením získávají.

**Vznikla sesterská technika.** *Event Modeling* Adama Dymitruka vzniklo jako evoluce Event Stormingu;
termín byl zaveden po EventStorming Summitu v Bologni v červenci 2018 [23]. Časová osa je **pouze dopředná**
(ES připouští větvení), přidává se UI/UX vrstva a výsledek vypadá jako storyboard. Pro kapitolu 13
(Event Sourcing) je to bližší nástroj než Software Design ES.

**Nástrojově přibyly specializované editory.** Vedle Miro a Mural existuje egon.io pro Domain Storytelling
[16], Qlerify pro Event Storming s automatickým seskupováním agregátů a open-source DDD Toolbox [9].
Kapitola z toho zná jen Miro, Mural a egon.io.

**Miro šablona, na kterou kapitola odkazuje, není oficiální.** Odkaz na řádcích 81 a 700 vede na komunitní
šablonu autorky Judith Birmoser [13]. Brandoliniho vlastní šablony jsou dvě a jsou na Miroverse [11][12];
odkazuje na ně i eventstorming.com/resources [4].

**Existuje pojmenovaný katalog patternů, který kapitola nezná.** Web eventstorming.com má sekci Patterns
se 17 položkami [2] — mimo jiné *Chaotic Exploration*, *Unlimited Modelling Space*, *One person / One Marker*,
*Speaking out loud*, *Leave stuff around*, *Raise the bar*, *Rush to the goal*, *Fuzzy Definitions*,
*Time-boxed Leadership* a anti-pattern *Deliverable Obsession*. Kapitola si pro anti-vzory vytvořila
vlastní taxonomii, přestože polovina jejích doporučení má kanonické jméno.

## 4. Symfony / PHP specifika

**Symfony 8.1 je aktuální verze** dokumentace k datu studie [18]. Registrace handlerů přes
`#[AsMessageHandler]` na třídě s `__invoke()` je stále doporučený způsob a autoconfiguration handler
napojí podle typehintu argumentu [18]. Ukázka v kapitole (řádky 283–301) je tedy z hlediska API správná.

**Návratová hodnota handleru má podmínku, kterou kapitola neuvádí.** `PlaceOrderHandler::__invoke()`
na řádku 292 vrací `OrderId`. Získat ji jde jen přes `HandledStamp` nebo `HandleTrait`, a **pouze
u synchronně zpracovaných zpráv** — u async transportu handler běží ve worker procesu a hodnota
se ke commandu nikdy nedostane [19]. Pro kapitolu, která tvrdí „každý prvek z workshopu se mapuje 1:1
do kódu", je to podstatná výhrada: command sticky se přeloží buď na sync command s výsledkem, nebo
na async bez něj, a to rozhodnutí padá mimo workshop.

**`EventBusInterface` není Symfony třída.** Ukázka na řádku 288 ji injektuje. V Symfony existuje
`MessageBusInterface`; pojmenovaná event bus je až konfigurace více sběrnic (`framework.messenger.buses`)
s aliasem. Bez vysvětlující věty čtenář hledá neexistující třídu; chybí i vazba na kapitolu 12 (CQRS).

**Ruční dispatch eventů za `save()` je v knize již vyřešený problém.** Řádky 296–299 dispatchují eventy
ve stejné metodě jako uložení agregátu; kapitola 15 (Outbox Pattern) přesně tento dual-write řeší.
Bez odkazu si čtenář odnese vzor, který kniha o jedenáct kapitol dál označí za chybný.

**Ukázka porušuje kanonický `Order` z CLAUDE.md.** `Order::place()` zde přebírá `array $items` (pole DTO),
zatímco kanonická podoba je `place()` + `addItem(ProductId, int $quantity, Money $unitPrice)`. Test na řádku
628 čte `$order->total()->amountInCents`, což s `Money` konvenci sedí; konstruktor s polem DTO už ne.

**Context Mapper jako most z workshopu do modelu.** Nástroj má dokumentovaný postup „Model Event Storming
Results in CML" [17]: domain events → `DomainEvent` uvnitř agregátu, commands → `CommandEvent` nebo metody
service, aggregates → `Aggregate`, actors → doc komentáře nad commandem; bounded contexty a subdomény jsou
first-class konstrukty, časová osa se zapisuje komentáři `// triggers ...`. Read modely a policies vlastní
konstrukt nemají. Je to jediný veřejně dokumentovaný formální most tohoto typu.

**Post-It kódy v kapitole sedí.** *Vital Orange* a *Power Pink* jsou skutečná označení 3M z kolekce Poptimistic [26].

## 5. Sporné a chybně podávané body

**Velikost skupiny pro Big Picture.** Kapitola uvádí 8–12 (ř. 46), 6–12 (ř. 92) a v FAQ tvrdí,
že „více než 14 lidí znamená, že se část účastníků stane diváky" (ř. 679). Primární zdroje uvádějí
pro Big Picture 15–30, typicky 25–30 [8], respektive 10–30+ [9]. Rozpor je zásadní: Big Picture je
*ze své podstaty* největší formát a čísla, která kapitola uvádí, odpovídají Process Modellingu.
**Doporučení:** převzít rozsah z primárního zdroje, vysvětlit, proč se velká skupina řeší rozdělením
u stěny a ne redukcí lidí, a menší variantu nabídnout jako vědomý kompromis.

**Online Big Picture.** Kapitola má pro online Big Picture šestibodový návod (ř. 128–137) a FAQ
odpovídá „Ano, ale s kompromisy" (ř. 681). Autor techniky doporučuje opak: *„Don't even try"* pro
Big Picture, a varuje před tím takovou session vůbec nazývat EventStormingem [7]. Kapitola tedy proti
primárnímu zdroji nestojí jen v důrazu, ale v doporučení. **Doporučení:** online návod ponechat, ale
uvést Brandoliniho stanovisko i zdůvodnění, rámovat online Big Picture jako kompromis a převzít jeho
odstupňování (Software Design online funguje nejlépe, Big Picture nejhůř).

**Facilitátor a tech lead.** Kapitola má absolutní zákaz (ř. 146, 408). Zdroje formulují požadavek jinak:
facilitátor má **neutrální roli**, aby mohl utnout dlouhé diskuse a převést je na hot spoty [9];
Brandoliniho pattern *Time-boxed Leadership* naopak počítá s tím, že facilitátor styl vedení mění [2].
Riziko, které kapitola popisuje, je reálné, ale zákaz podle role je slabší tvrzení než požadavek
na neutralitu — externí konzultant se silným názorem je stejně škodlivý jako tech lead. **Doporučení:**
přeformulovat na princip neutrality a role uvést jako typický, ne povinný výběr.

**Aggregate vs. Constraint.** Kapitola prezentuje „Aggregate (žlutooranžová)" jako součást notace.
Ddd-crew glossary uvádí, že *aggregate* je v EventStormingu **legacy termín** a prvek se dnes jmenuje
Constraint / business rule na velké žluté lepce [9]. Obě strany mají důvod: Brandoliniho posun je jazykový
(nechce zatěžovat stakeholdery DDD žargonem), zatímco pro čtenáře knihy o DDD je *agregát* přesně to slovo,
které chce slyšet. **Doporučení:** uvést oba názvy, primárně Constraint, a barvu opravit na velkou žlutou.
Konvence navíc podpírá další tvrzení kapitoly — že invariant ze žluté lepky se překládá na `throw` v doméně.

**Externí systém: šedá vs. růžová.** Kapitola volí šedou a v poznámce přiznává, že originál používá růžovou
(ř. 64). Oficiálně jde o **širokou růžovou** [9], odlišenou od neonově růžového hot spotu, který se navíc
natáčí do kosočtverce. Šedá je běžná improvizace, ale znemožňuje číst cizí mapu — což je argument,
který kapitola sama používá na řádku 57. **Doporučení:** sjednotit na oficiální barvě a odchylku zmínit
jen jako známou variantu.

**Kolísání barvy hot spotů.** Kapitola tvrdí, že „barva hot spotů v praxi kolísá" (ř. 65). Zdroj je
jednoznačný: *„The official EventStorming colour is neon pink and we also slightly pivot a hotspot"* [9].
**Doporučení:** tvrzení otočit — barva je daná, kolísá dostupnost neonově růžových lepek.

**Místnost bez oken.** Řádek 91 požaduje „stěnu bez oken"; doporučení z praxe je opačné — místnost,
kde jde otevřít okno, protože workshop spotřebuje kyslík [9]. Modelovací plocha se uvádí 6–8 m, ne 4–8 m.

**Rok knihy.** Viz sekce 2. Tři výskyty „(Leanpub, 2018)" jsou fakticky chybné [5].

**Číslování v příkladu Domain Storytellingu.** Řádky 341–345 číslují jednu větu dvěma čísly
(„Customer →(3) submits → Order →(4) to → Order System"). V notaci nese číslo **aktivita**, ne šipka;
egon.io čísluje aktivitu vycházející z actora [16][14]. Příklad tedy pravděpodobně zobrazuje sedm vět
jako deset kroků. **Doporučení:** překreslit v egon.io, nebo přeformulovat tak, aby bylo jasné,
co je jedna aktivita.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | zastaralé | `event_storming.md:44`, `47`, `48`, `151`, `201` | Úrovně nazvané Process Level / Design Level; kanonicky Process Modelling / Software Design | Přejmenovat, komunitní zkratky uvést v závorce |
| G2 | zastaralé | `event_storming.md:44`, `412`, `694` | „*Introducing EventStorming* (Leanpub, 2018)" — kniha není dokončená (70 %, poslední update 2021-08-26) | Opravit citaci a v Další četbě uvést, že jde o průběžně vydávaný, nedokončený text |
| G3 | nepodložené | `event_storming.md:44` | Tvrzení, že Brandolini úrovně „formálně rozdělil" v knize z 2018 | Atribuovat taxonomii webu eventstorming.com a přednášce *50.000 Orange Stickies Later* (2017) |
| G4 | sporné | `event_storming.md:46`, `92`, `679` | Big Picture 6–12 účastníků; zdroje uvádějí 15–30, typicky 25–30 | Převzít rozsah z primáru, malou skupinu rámovat jako kompromis |
| G5 | sporné | `event_storming.md:59–69` | Aggregate = žlutooranžová (kanonicky Constraint, velká žlutá); External System = šedá (kanonicky široká růžová); Bounded Context = fialová (nemá oficiální barvu) | Přepsat tabulku podle ddd-crew glossary, sloupec „formát" doplnit |
| G6 | chybí | `event_storming.md:59–69` | V legendě chybí Opportunity (zelená), Value (červená/zelená), Swimlanes, Constraint, notace pivotal events | Doplnit řádky, rozdělit tabulku podle tří formátů |
| G7 | sporné | `event_storming.md:65` | „Barva hot spotů v praxi kolísá" | Oficiální barva je neonově růžová + natočení; opravit |
| G8 | sporné | `event_storming.md:128–137`, `681` | Návod na online Big Picture bez zmínky, že autor techniky ho nedoporučuje | Doplnit Brandoliniho stanovisko a odstupňování formátů podle vhodnosti pro remote |
| G9 | sporné | `event_storming.md:146`, `408–413` | Absolutní zákaz „facilitátor nesmí být tech lead" | Přeformulovat na princip neutrality facilitátora |
| G10 | zastaralé | `event_storming.md:81`, `700` | Odkaz na komunitní Miro šablonu (Judith Birmoser) prezentovaný jako standard | Nahradit oficiálními šablonami Brandoliniho na Miroverse |
| G11 | nepodložené | `event_storming.md:91` | „4–8 m stěna bez oken" | Zdroje uvádějí 6–8 m plochu a místnost, kde jde větrat |
| G12 | chybí | sekce 04.08 | Kapitola nezná katalog 17 pojmenovaných patternů z eventstorming.com | Anti-vzory namapovat na kanonická jména (*Deliverable Obsession*, *Rush to the goal*, *Fuzzy Definitions*) |
| G13 | chybí | celá kapitola | Žádná zmínka o facilitační vrstvě: check-in/check-out, pracovní dohody, ranking, kognitivní zkreslení | Nová podsekce v 04.04, opřená o *Collaborative Software Design* (2024) |
| G14 | chybí | celá kapitola | Chybí kritika a limity Event Stormingu (bias k happy path, chybějící edge cases a NFR, degradace modelu po workshopu, závislost na facilitátorovi) | Nová sekce „Co Event Storming neumí" |
| G15 | chybí | sekce 04.07 | Domain Storytelling bez konceptu *scope* (granularity / point in time / domain purity) | Doplnit — je to jádro techniky, bez něj nelze oddělit AS-IS od TO-BE |
| G16 | mělké | `event_storming.md:327–333` | Notace DST bez pravidel „actor jednou, work object u každé aktivity" a bez větné gramatiky | Rozšířit o pravidla a o pravidlo 10–20 kroků na příběh |
| G17 | sporné | `event_storming.md:339–345` | Číslování aktivit v příkladu neodpovídá notaci (dvě čísla na jednu větu) | Překreslit v egon.io, nebo přepsat |
| G18 | nepodložené | `event_storming.md:353–354` | „DST: 2–5 lidí, 30–90 min" bez zdroje | Nahradit rolemi z knihy (moderátor/modelář, storytellers, listeners) nebo označit jako autorskou zkušenost |
| G19 | chybí | sekce 04.07 / 04.11 | Chybí cesta z Domain Storytellingu do kódu a do testů (kniha má kapitoly 11 a 12) | Doplnit — fine-grained story dává Given-When-Then strukturu přímo |
| G20 | chybí | celá kapitola | Chybí Example Mapping (Wynne: žlutá story, modrá rule, zelená example, červená question) a User Story Mapping | Nová podsekce v 04.06: Example Mapping jako navazující krok po Software Design ES |
| G21 | chybí | celá kapitola | Chybí Event Modeling (Dymitruk, 2018) jako sesterská technika s dopřednou časovou osou | Odstavec v 04.12 nebo v Další četbě, s odkazem na kapitolu 13 |
| G22 | chybí | `event_storming.md:102`, `107–116` | Sekce o pivotal events a hranicích BC necituje kanonický zdroj (Brandolini, *Discovering Bounded Contexts with EventStorming*) | Doplnit citaci; opravit značení pivotal events na svislé čáry |
| G23 | nepodložené | `event_storming.md:292` | Handler vrací `OrderId` bez zmínky, že to funguje jen synchronně | Doplnit poznámku o `HandledStamp` / `HandleTrait` a async omezení |
| G24 | nepodložené | `event_storming.md:288` | `EventBusInterface` — v Symfony neexistuje | Použít `MessageBusInterface` nebo vysvětlit jako alias vícesběrnicové konfigurace |
| G25 | sporné | `event_storming.md:296–299` | Ruční dispatch eventů hned po `save()` — dual-write, který kap. 15 označuje za chybu | Doplnit odkaz na Outbox Pattern |
| G26 | sporné | `event_storming.md:250–262`, `606` | `Order::place(array $items)` nesedí s kanonickým `place()` + `addItem(ProductId, int, Money)` | Sjednotit s konvencí z CLAUDE.md |
| G27 | nadbytečné | `event_storming.md:504–562` | 59 řádků o struktuře repa, commit message konvenci a prvním PR bez opory ve zdroji | Zkrátit na polovinu, ponechat `docs/discovery/` a hot-spot tickety; zbytek je autorská praxe |
| G28 | chybí | `event_storming.md:692–700` | V Další četbě chybí Khononov kap. 12, Rayner *EventStorming Handbook*, Baas-Schwegler *Collaborative Software Design*, Tune *Architecture Modernization*, ddd-crew glossary | Doplnit pět položek |
| G29 | mělké | `event_storming.md:40–53` | Původ techniky zúžen na „zavedl v roce 2013" | Doplnit dvě věty o trajektorii: zkratka za UML → nástroj na agregáty → učební pomůcka → kolaborativní platforma |
| G30 | chybí | sekce 04.05 / 04.06 | Chybí Context Mapper jako formální most z workshopu do modelu | Odstavec s mapováním sticky → CML konstrukt |

## 7. Doporučení k přepisu

**P1-1 — Opravit barevnou legendu a názvy tří formátů podle primárních zdrojů.**
Sekce 04.03 je definiční jádro kapitoly a má v ní tři faktické chyby (Aggregate, External System,
Bounded Context) plus tvrzení, že barva hot spotů kolísá. Zároveň přejmenovat Process Level →
Process Modelling a Design Level → Software Design, s komunitními zkratkami v závorce. Bez toho kniha
učí notaci, kterou nikdo jiný nepoužívá. *Přepis sekce 04.03 (~35 řádků) + záměna termínů na pěti místech.*

**P1-2 — Opravit citaci knihy *Introducing EventStorming* na třech místech.** Kniha není z roku 2018
a není dokončená; čtenář narazí na 70% verzi ze srpna 2021 s neúplným glosářem.
*Oprava tří vět + jedna věta v Další četbě.*

**P1-3 — Doplnit sekci o limitech Event Stormingu.**
Kapitola má šest anti-vzorů vedení workshopu, ale žádnou pasáž o tom, co technika principiálně neumí:
sklon k happy path, chybějící edge cases a provozní scénáře, žádné pokrytí nefunkčních požadavků,
degradace modelu po workshopu, závislost na facilitátorovi. Kniha jinde (kap. 22) umí být k DDD kritická;
tady ten tón chybí. *Nová sekce ~35 řádků, zařadit před 04.09.*

**P1-4 — Přepsat pasáž o online formátu podle Brandoliniho stanoviska.**
Kapitola dnes doporučuje něco, co autor techniky výslovně nedoporučuje, a nikde to neuvádí.
Návod na online workshop má hodnotu a má zůstat, ale musí být rámovaný jako kompromis a doplněný
o odstupňování podle formátu. *Přepis sekce 04.04.5 (~15 řádků) + oprava FAQ odpovědi.*

**P1-5 — Doplnit koncept *scope* do Domain Storytellingu.** Granularity / point in time / domain purity
je to, co z DST dělá metodu, ne kreslicí konvenci. Bez rozlišení AS-IS a TO-BE tým nakreslí příběh,
o kterém si půlka místnosti myslí, že popisuje současnost, a druhá půlka, že návrh. *Podsekce ~25 řádků.*

**P2-1 — Doplnit Example Mapping jako navazující krok po Software Design ES.**
Čtyři barvy (žlutá story, modrá rule, zelená example, červená question) a přímý přechod
z examples do PHPUnit testů zapadají přesně do sekce 04.11. Hofer & Schwentner tuto vazbu
popisují v kapitole 7 své knihy, Baas-Schwegler ji má v celém repertoáru. *Nová podsekce ~30 řádků.*

**P2-2 — Doplnit facilitační vrstvu.**
Check-in / check-out, explicitní pracovní dohody, ranking a kognitivní zkreslení. Zdrojem je
*Collaborative Software Design* (Manning 2024) a ddd-crew cheat sheet. Dnes kapitola v přípravě
řeší catering, ale ne to, proč lidé v místnosti mlčí. *Nová podsekce v 04.04 ~25 řádků.*

**P2-3 — Doplnit cestu z Domain Storytellingu do kódu.**
Kapitola 04.11 mapuje na testy jen výstupy Event Stormingu. Fine-grained domain story dává
Given-When-Then strukturu přímo (actor / activity / work object), což je pro Symfony projekt
s Behatem nebo s doménovými testy okamžitě použitelné. *Rozšíření sekce 04.11 ~20 řádků.*

**P2-4 — Opravit Symfony ukázku v 04.06.3.**
Čtyři nálezy: `EventBusInterface`, návratová hodnota handleru bez zmínky o `HandledStamp`, dual-write
bez odkazu na Outbox, a `Order::place(array $items)` proti kanonickému `addItem()`. Ukázka je exponovaná —
je to první kód, který čtenář uvidí jako „výstup workshopu". *Přepis ukázky + tři věty, ~20 řádků.*

**P2-5 — Namapovat vlastní anti-vzory na kanonické patterny.** Šest calloutů v 04.08 popisuje z velké
části to, co má na eventstorming.com jméno. Kanonický název v nadpisu stojí řádek a zvedne dohledatelnost.
*Úprava šesti nadpisů + odstavec s odkazem na katalog.*

**P2-6 — Doplnit citaci Brandoliniho eseje o pivotal events.** Sekce 04.04.3 a krok 4 v 04.04.2 stojí
obsahově na eseji *Discovering Bounded Contexts with EventStorming* z *DDD: The First 15 Years* (2017),
ale necitují ji. Zároveň opravit značení pivotal events na svislé čáry napříč osou. *Dvě věty + oprava jedné.*

**P3-1 — Zkrátit sekci 04.09.** 124 řádků autorské praxe o struktuře repa, commit konvenci a prvním PR.
Jádro (foto, `docs/discovery/`, hot spot → ticket) obstojí; zbytek je návod na vedení repa, ne na workshop.
*Zkrácení o ~50 řádků.*

**P3-2 — Odstavec o Event Modelingu.** Sesterská technika s dopřednou časovou osou a UI vrstvou,
relevantní pro kapitolu 13. Jeden odstavec ve shrnutí plus položka v Další četbě. *~8 řádků.*

**P3-3 — Odstavec o Context Mapperu.** Jediný veřejně dokumentovaný formální most mezi lepkami
a modelem (sticky → CML → diagramy). Pro Symfony tým volitelný, ale funkční mezikrok. *~10 řádků v 04.06.*

**P3-4 — Rozšířit Další četbu o pět položek.** Khononov kap. 12, Rayner *EventStorming Handbook*,
Baas-Schwegler *Collaborative Software Design*, Tune *Architecture Modernization*, ddd-crew glossary. *~7 řádků.*

## 8. Otevřené otázky pro autora

1. **Držet, nebo opustit „Design Level"?** Přejmenování na Software Design je faktograficky správné,
   ale „Design Level Event Storming" je termín, který česká i anglická komunita reálně používá
   a který lidé googlují. Varianta: primární kanonický název, komunitní v závorce a v `meta_keywords`.
   Poznámka: `meta_keywords` na řádku 7 dnes obsahuje „Design Level" — pokud se název změní, je to SEO rozhodnutí.

2. **Jak daleko jít proti Brandolinimu v otázce online Big Picture?** Kniha cílí na české týmy,
   ze kterých má většina distribuované doménové experty. Doporučit „nedělejte to" je věrné zdroji,
   ale prakticky to znamená „nedělejte workshop vůbec". Alternativa: přiznat rozpor a nabídnout
   redukovanou variantu jako vědomý kompromis s vyjmenovanými ztrátami.

3. **Kolik prostoru dát facilitační vrstvě?** Realistický rozsah je 25–40 řádků, ale je otázka,
   zda materiál nepatří spíš do kapitoly 05 (Team Topologies), kde už se řeší lidská stránka.

4. **Zůstává Domain Storytelling ve stejné kapitole?** Dnes zabírá 63 řádků z 700 (9 %), v názvu
   kapitoly má ale rovnocenné postavení. Po doplnění *scope*, pravidel notace a cesty do kódu vyroste
   na ~120 řádků. Buď se kapitola rozdělí, nebo DST zůstane vedlejší technikou a z titulku zmizí.

5. **Fiktivní workshop s daty 2026-04-29.** Kapitola opakovaně používá konkrétní datum, počty
   účastníků a čísla hot spotů, jako by šlo o reálný workshop. Podle konvence knihy by to mělo být
   označené jako „Ilustrativní scénář". Ponechat s labelem, nebo data odosobnit?

6. **Vztah k sekci 24.03 v případové studii.** Kapitola 24 popisuje třídenní event storming
   s vlastní terminologií („Krok 1: Sběr doménových událostí"), která se s notací kapitoly 04
   nekryje a nepoužívá ani jednu ze tří úrovní. Sjednotit, nebo v kap. 24 přiznat zjednodušení?

## 9. Bibliografie

### Ověřené zdroje

`[1]` EventStorming — oficiální web Alberta Brandoliniho. https://www.eventstorming.com/ (přístup 2026-09-03)
`[2]` EventStorming — Patterns (katalog 17 pojmenovaných patternů). https://www.eventstorming.com/patterns/ (2026-09-03)
`[3]` EventStorming — Unlimited Modelling Space. https://www.eventstorming.com/patterns/unlimited-modelling-space/ (2026-09-03)
`[4]` EventStorming — Resources (Starter Kit, oficiální šablony, seznam knih). https://www.eventstorming.com/resources/ (2026-09-03)
`[5]` Alberto Brandolini — *Introducing EventStorming*, Leanpub, průběžně vydávané; k 2026-09-03 uvedeno 70 % hotovo, poslední aktualizace 2021-08-26, 416 stran. https://leanpub.com/introducing_eventstorming (2026-09-03)
`[6]` Alberto Brandolini — *Introducing Event Storming*, Avanscoperta Blog, 2014-02-12 (původ techniky, Italian Agile Day 2012, pojmenování léto 2013). https://blog.avanscoperta.it/2014/02/12/introducing-event-storming/ (2026-09-03)
`[7]` Alberto Brandolini — *Remote EventStorming*, Avanscoperta Blog, 2020-03-26. https://blog.avanscoperta.it/2020/03/26/remote-eventstorming/ (2026-09-03)
`[8]` Avanscoperta — *EventStorming: model domain complexity…* (tři formáty, 15–30 účastníků Big Picture). https://www.avanscoperta.it/en/eventstorming/ (2026-09-03)
`[9]` ddd-crew — *EventStorming Glossary & Cheat Sheet* (autor Kenny Baas-Schwegler, CC BY 4.0). https://github.com/ddd-crew/eventstorming-glossary-cheat-sheet (2026-09-03)
`[10]` Kenny Baas-Schwegler — *EventStorming; Core concepts, glossary and legend*, 2020-07-16. https://weave-it.org/2020/07/16/eventstorming-core-concepts-glossary-and-legend/ (2026-09-03)
`[11]` Alberto Brandolini — *EventStorming Process Modelling Template*, Miroverse. https://miro.com/templates/eventstorming-process-modelling/ (2026-09-03)
`[12]` Alberto Brandolini — *EventStorming Software Design Template*, Miroverse. https://miro.com/miroverse/eventstorming-software-design-template/ (2026-09-03)
`[13]` *Event Storming Template*, Miro (komunitní šablona, autorka Judith Birmoser) — šablona, na kterou dnes odkazuje kapitola. https://miro.com/templates/event-storming/ (2026-09-03)
`[14]` Domain Storytelling — oficiální web a Quick-Start Guide (notace, scope: granularity / point in time / domain purity). https://domainstorytelling.org/ a https://domainstorytelling.org/quick-start-guide (2026-09-03)
`[15]` Stefan Hofer, Henning Schwentner — *Domain Storytelling: A Collaborative, Visual, and Agile Way to Build Domain-Driven Software*, Addison-Wesley Professional (Vernon Signature Series), 2021-09-07, 288 s., ISBN 978-0-13-745891-2. https://www.informit.com/store/domain-storytelling-a-collaborative-visual-and-agile-9780137458912 (2026-09-03)
`[16]` Egon.io — *How to Use* (formáty .egn, .egn.svg, .png, .html; data pouze v prohlížeči). https://egon.io/howto (2026-09-03)
`[17]` Context Mapper — *Model Event Storming Results in Context Mapper*. https://contextmapper.org/docs/event-storming/ (2026-09-03)
`[18]` Symfony Documentation — *Messenger: Sync & Queued Message Handling* (aktuální verze 8.1; `#[AsMessageHandler]`, více sběrnic). https://symfony.com/doc/current/messenger.html (2026-09-03)
`[19]` Symfony Documentation — *Getting Results from your Handler* (`HandledStamp`, `HandleTrait`, omezení u async transportů). https://symfony.com/doc/current/messenger/handler_results.html (2026-09-03)
`[20]` Cucumber — *Example Mapping* (žlutá story, modrá rule, zelená example, červená question). https://cucumber.io/docs/bdd/example-mapping/ (2026-09-03)
`[21]` Evelyn van Kelle, Gien Verschatse, Kenny Baas-Schwegler — *Collaborative Software Design: How to facilitate domain modeling decisions*, Manning, 2024, ISBN 978-1-63343-925-2. https://www.manning.com/books/collaborative-software-design (2026-09-03)
`[22]` Nick Tune, Jean-Georges Perrin — *Architecture Modernization: Socio-technical alignment of software, strategy, and structure*, Manning, 2024. https://www.manning.com/books/architecture-modernization (2026-09-03)
`[23]` Event Modeling — oficiální web Adama Dymitruka; k původu (termín zaveden po EventStorming Summitu v Bologni, červenec 2018) rozhovor na Semaphore. https://eventmodeling.org/ a https://semaphore.io/blog/adam-dymitruk-event-modeling (2026-09-03)
`[24]` Alberto Brandolini — *50.000 Orange Stickies Later*, KanDDDinsky 2017 / ExploreDDD Denver 2017. https://www.youtube.com/watch?v=cG-G6tNCGqY (2026-09-03)
`[25]` Vlad Khononov — *Learning Domain-Driven Design*, O'Reilly, 2021, kapitola 12 „EventStorming". https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/ (2026-09-03)
`[26]` Post-it / 3M — kolekce Poptimistic (barvy *Vital Orange*, *Power Pink*). https://www.post-it.com/ (2026-09-03)
`[27]` Vaughn Vernon — *Domain-Driven Design Distilled*, Addison-Wesley, 2016, kapitola 7 „Acceleration and Management Tools" (Event Storming). Ověřeno proti obsahu knihy u vydavatele.

### Neověřené / nedohledané

- **Barevná legenda z *Introducing EventStorming* – OVĚŘENO 2026-09-04 ze zakoupeného výtisku.
  Čtyři barvy sedí, jedna je v rozporu.**

  Potvrzeno doslovně: **oranžová** = doménová událost (*„orange sticky note, in a verb at past
  tense, and place them along a timeline“*), **modrá** = command (*„a user decision which will
  involve both logical and emotional thinking“*), **lila** = policy (*„a lilac sticky note,
  sitting in between an orange event and a blue command“*), **žlutá** = aktér (*„yellow sticky
  note to every event in the flow. Adding significant people adds more clarity“*).

  **Rozpor – Hot Spot.** Brandolini pro hot spoty používá **fialovou**; v knize se to opakuje ve
  čtyřech formulacích: „purple for Hot Spot“, „hot spots in purple“, „hot spot purple“,
  „hot spots with purple“. Příklad z jeho workshopu: fialová sticky s textem *„Training Class
  Description Sucks!“* umístěná k odpovídající události.

  Kapitola `event_storming.md:65-66` má ale **růžovou pro Hot Spot a fialovou pro Bounded
  Context**. Pro Bounded Context přitom Brandolini žádnou barvu nezavádí – hranice kreslí, ne
  lepí.

  **Doporučení: legendu sjednotit s primárním zdrojem, nebo v kapitole výslovně napsat, že jde
  o variantu.** Barevné konvence se v komunitě liší a to je legitimní; problém je jen v tom, že
  kapitola legendu podává jako danou, aniž by odchylku od Brandoliniho zmínila. Nejmenší zásah:
  poznámka pod tabulkou.
- **Brandoliniho esej *Discovering Bounded Contexts with EventStorming*** ve sborníku
  *Domain-Driven Design: The First 15 Years* (Leanpub, 2017). Existenci i autorství potvrzuje [9],
  plný text ověřen nebyl; značení pivotal events svislými čarami pochází z obrázků citovaných v [9].
- **Velikost skupiny a délka sezení pro Domain Storytelling.** Kniha [15] má kapitolu 6
  „The Workshop Format", je ale za paywallem (O'Reilly vrací 403). Údaje „2–5 lidí, 30–90 minut"
  se nepodařilo potvrdit ani vyvrátit; role jsou ověřené jen ze sekundárních zdrojů.
- **Číslování aktivit v DST příkladu.** Pravidlo „číslo patří aktivitě, ne šipce" vychází z [16] a [14].
  Konkrétní chybu na řádcích 341–345 je třeba ověřit překreslením v egon.io, ne z dokumentace.
- **Khononovův postup Event Stormingu** (kap. 12 *Learning DDD*): existence kapitoly ověřena,
  struktura kroků ne — obsah je za paywallem O'Reilly.
- **Paul Rayner — *The EventStorming Handbook*.** Uvedena mezi zdroji na eventstorming.com [4];
  vydavatel, rok ani stav vydání nebyly ověřeny.
