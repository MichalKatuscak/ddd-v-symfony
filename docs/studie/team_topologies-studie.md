# Studie: Conway's Law a Team Topologies

- **Kapitola:** `content/chapters/team_topologies.md` (č. 05, kategorie Základy, 803 řádků)
- **Cesta:** /team-topologies
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| Deck + úvod | 16–25 | Conway 1968 jako „gravitační pole“ architektury; 1 BC = 1 tým je první pravidlo, které management poruší | – | Silný rámec, drží se ho celá kapitola |
| 05.01 Conway's Law | 27–81 | Citace původní teze; tři případy z praxe (vrstvy → layered, stream → BC per tým, žádné hranice → BBoM); callout „Law je silné slovo“ | [1] | Citace verbatim správně; z eseje se přebírá jen závěrečná věta |
| 05.02 BC = týmová hranice | 83–144 | Vernonovo pravidlo: 1 BC = 1 tým vždy, 1 tým = 1–3 BC; Context Map a Team Map jsou izomorfní; tabulka 4 typů nesouladu | [2], [6] | Nejsilnější sekce kapitoly; „vždy“ je ale tvrdší než zdroje |
| 05.03 Čtyři typy týmů | 146–239 | Stream-aligned / Platform / Enabling / Complicated-subsystem; velikosti, měření, past; mapování Core/Supporting/Generic na typy týmů | [3] | Definice odpovídají knize, ale číselné parametry (5–9, 1 platform na 50–150) zdroj nemají |
| 05.04 Tři interakční módy | 241–294 | Collaboration / X-as-a-Service / Facilitating; mapování na Context Map vzory; callout „žádné volné vztahy“ | [3] | Mapování na DDD vzory je převzato z komunitní diskuse, ne z knihy |
| 05.05 Inverse Conway Maneuver | 296–384 | Atribuce LeRoy & Simons, Cutter IT Journal 12/2010; 4 kroky; Amazon 2002; sedmibodový checklist | [3], [9], [12] | Atribuce správná; chybí Fowlerovy výhrady k účinnosti manévru |
| 05.06 Cognitive Load | 386–496 | Sweller a tři typy zátěže; tabulka „velikost týmu → počet BC“; vlastní pětipoložková rubrika v Markdownu | [3], [13] | Rubrika je autorský výtvor, ne nástroj Team Topologies |
| 05.07 Scénáře 5 / 20 / 200+ | 498–558 | Startup → 1 tým + modulární monolit; scale-up → 2–3 týmy + mini-platform; enterprise → plná struktura; poměr 75/15/10 | – | Nejpraktičtější část; poměry bez zdroje, callout to sám přiznává |
| 05.08 Anti-vzory | 560–640 | Pět anti-vzorů (monorepo bez hranic, vrstvené týmy, CoE, platform jako ticket fronta, sdílený BC) + test | [3] | Dobře napsané; tooling doporučení míří mimo PHP ekosystém |
| 05.09 Komunikace s managementem | 642–718 | DORA metriky z *Accelerate*; argumenty, které nefungují; Westrumova typologie; vzorový pitch | [4], [5] | DORA sada je ve stavu z roku 2018, čísla o zlepšení bez zdroje |
| 05.10 Shrnutí | 720–762 | Osm odrážek shrnujících kapitolu + doporučená četba | [1]–[4] | Konzistentní s tělem kapitoly |
| FAQ | 764–777 | Šest otázek: jediný tým, 5 BC na tým, Spotify Model, padesátičlenná firma, odpor managementu, mikroservisy | – | Nejslabší podložená část; Spotify a mikroservisy odkazují na špatnou kapitolu |
| 05.11 Další četba | 779–803 | Devět položek | – | Bibliografie je krátká a od roku 2019 se neposunula |

Kapitola je nadprůměrně čitelná a má jasnou tezi: Bounded Context bez vlastnícího týmu je fikce. Prostor dostává organizační rovina (reorganizace, management, kultura), která v české DDD literatuře chybí, a to je její hlavní hodnota. Odbývá naopak dvě věci. Zaprvé původní zdroje: Conwayův esej se cituje jednou větou, Team Topologies se redukuje na 4+3 typologii a vynechává techniky, kterými kniha most mezi DDD a týmy skutečně staví (fracture planes, Team API, Independent Service Heuristics, platform as a product). Zadruhé vlastní ekosystém knihy: kapitola v knize o Symfony 8 neobsahuje jediný Symfony nebo PHP artefakt, přestože nástroje pro vynucení vlastnictví hranic (CODEOWNERS, Deptrac) jsou triviálně dostupné a kniha je používá jinde. Číselné parametry (5–9 lidí, 1 platform na 50–150 vývojářů, 75/15/10, zlepšení lead time o 30–80 %) jsou z velké části autorské odhady prezentované vedle citovaných tvrzení, což čtenář nerozliší.

## 2. Kanonické zdroje k tématu

### Conway 1968

Esej *How Do Committees Invent?* vyšel v *Datamation* 14(4), duben 1968, s. 28–31. Plný text i sken jsou na Conwayově webu [1a][1b]. Před Datamation rukopis odmítl *Harvard Business Review* (1967) s odůvodněním, že teze není dokázaná [1c].

Závěrečná formulace v sekci *conclusion* zní verbatim: „The basic thesis of this article is that organizations which design systems (in the broad sense used here) are constrained to produce designs which are copies of the communication structures of these organizations." Kapitola cituje přesně (řádek 33–35), jen s velkým počátečním písmenem. To je v pořádku.

Kapitola ale vynechává tři věci, které dělají z eseje argument, a ne slogan:

- **Homomorfismus.** Conway dokazuje strukturní odpovídající vztah graficky: každému uzlu návrhu odpovídá návrhová skupina, každé větvi mezi uzly odpovídá dohodnuté rozhraní mezi skupinami. Doslova: „Speaking as a mathematician might, we would say that there is a homomorphism from the linear graph of a system to the linear graph of its design organization." [1b] Tohle je zdroj oné „vysoké spolehlivosti", kterou kapitola na řádku 42 tvrdí bez opory.
- **Rozdíl mezi komunikační a administrativní strukturou.** Conway je explicitní: „To the extent that organizational protocol restricts communication along lines of command, the communication structure of an organization will resemble its administrative structure. This is one reason why military-style organizations design systems which look like their organization charts." [1b] Org chart tedy Conwayův zákon **nepopisuje**; popisuje ho jen v organizacích, kde formální hierarchie skutečně určuje, kdo s kým mluví.
- **Nevyhnutelnost zaujetí a doporučení.** „The very act of organizing a design team means that certain design decisions have already been made […] there is no such thing as a design group which is both organized and unbiased." Závěr: „a design effort should be organized according to the need for communication" a „flexibility of organization is important to effective design." [1b] Druhá věta je přímý předchůdce Inverse Conway Maneuveru i Fowlerovy výhrady k němu.

Název „Conway's Law" Conway sám nezavedl. Podle jeho poznámky jej zpopularizoval Fred Brooks citací v *The Mythical Man-Month* [1c].

### Inverse Conway Maneuver

Termín zavedli Jonny LeRoy a Matt Simons (ThoughtWorks) v článku v *Cutter IT Journal*, prosinec 2010. Atribuce pochází od Martina Fowlera [9]; přesný název článku se nepodařilo ověřit (viz sekce 9). Kapitola atribuci uvádí správně, včetně data.

Fowler k manévru dodává výhrady, které kapitola nemá:

- Změna organizace neopraví zabetonovanou architekturu okamžitě. Riskuje naopak nesoulad mezi vývojáři a kódovou bází, který další vývoj zpomalí.
- Doporučuje malé inkrementální změny s vyhodnocováním zpětné vazby, ne jednorázovou reorganizaci.
- „Evolution of the architecture and reorganizing the human organization must go hand-in-hand throughout the life of an enterprise." [9] Tedy: souběžně a průběžně, ne „nejdřív org chart, pak architektura".

Skelton a Pais v knize používají převážně variantu **reverse Conway maneuver**; obě označení se v literatuře používají zaměnitelně.

### Team Topologies (Skelton & Pais)

- 1. vydání: IT Revolution, září 2019, podtitul *Organizing Business and Technology Teams for Fast Flow* [3a].
- **2. vydání: 23. září 2025**, podtitul změněn na *Organizing business and technology for fast flow of value* [3b][3c]. Kapitola cituje pouze vydání z roku 2019.

Struktura knihy: Part I (kap. 1–3: *The Problem with Org Charts*, *Conway's Law and Why It Matters*, *Team-First Thinking*), Part II (kap. 4–6: *Static Team Constructs*, *The Four Fundamental Team Topologies*, *Choose Team-First Boundaries*), Part III (kap. 7–9: *Team Interaction Modes*, *Evolve Team Structures with Organizational Sensing*, závěr) [15]. Odkazy kapitoly na „kap. 2" (Inverse Conway) a „kap. 3 Team-First Thinking" (cognitive load) tedy sedí.

Oficiální definice z webu autorů [3d]:

- Stream-aligned team – „aligned to a flow of work from (usually) a segment of the business domain"
- Enabling team – „helps a Stream-aligned team to overcome obstacles. Also detects missing capabilities."
- Complicated Subsystem team – „where significant mathematics/calculation/technical expertise is needed"
- Platform team – „**a grouping of other team types** that provide a compelling internal product to accelerate delivery by Stream-aligned teams"
- Collaboration – „working together for a defined period of time to discover new things"
- X-as-a-Service – „one team provides and one team consumes something 'as a Service'"
- Facilitation – „one team helps and mentors another team"

Poslední definice platformy je pro kapitolu podstatná: platforma **není** definována jako jeden tým. Autoři ji popisují jako fraktální („Russian doll") útvar, uvnitř kterého mohou být vlastní stream-aligned týmy [11].

Klíčové koncepty knihy, které kapitola vůbec nezmiňuje:

- **Thinnest Viable Platform (TVP)** – platforma má být jen tak tlustá, jak je nutné; příkladem minimální TVP je wiki stránka se seznamem schválených cloud služeb a návodem [11]. Platform-as-a-product s roadmapou, interními zákazníky a měřením (NPS) je jádrem doporučení autorů.
- **Fracture planes** – přirozené štěpné roviny systému, podle kterých se dělí software i týmy. Lakmusový test: vede výsledná architektura k autonomnějším týmům s nižší kognitivní zátěží? [16]
- **Team API** – explicitně publikované rozhraní týmu (kód, dokumentace, způsob komunikace, pracovní doba).
- **Dunbarovy hranice důvěry** – 5 (blízké vztahy), 15 (hluboká důvěra), 50, 150, 500 [17]. Tým je „stable grouping of five to nine people". Kapitola místo toho na řádku 166 uvádí „Two-Pizza Rule" (Amazon), což není zdroj, ze kterého Team Topologies vychází.
- **Organizational sensing / evoluce topologií v čase** (kap. 8).

### Cognitive load: jak ho Team Topologies měří

Sweller (1988) a tři typy zátěže kapitola podává správně [13]. Metoda měření ale ne. Kniha explicitně říká, že přesná míra neexistuje, a nabízí dva nástroje:

1. **Jedna otázka na tým:** „Do you feel like you are effective and able to respond in a timely fashion to the work you are asked to do?" [15]
2. **Relativní míra přes komplexitu domén.** Domény se klasifikují jako simple / complicated / complex a platí heuristiky: přiřaď každou doménu jedinému týmu; když je doména na tým velká, rozděl **doménu**, ne odpovědnost; jeden tým unese 2–3 simple domény; tým s complex doménou nemá dostat žádnou další; vyhni se jednomu týmu se dvěma complicated doménami [14].

Toto je přímý ekvivalent tabulky „velikost týmu → počet BC" na řádku 412–417, jen postavený na komplexitě domény, ne na počtu lidí. Autoři knihy nikde neuvádějí rubriku s pěti otázkami na škále 1–5. Existuje ale oficiální šablona *Team Cognitive Load Assessment* pod CC BY-SA 4.0 [10], jejíž veřejná verze obsahuje jen odkaz na ukázkový Google Form – vlastní znění otázek v repozitáři není.

Ve 2. vydání (2025) autoři ve spolupráci s Dr. Laurou Weis publikovali „scientific model identifying over twenty cognitive load drivers across four clusters" [3b]. Kognitivní zátěž je v druhém vydání povýšena na hlavní designový princip.

### Vazba Team Topologies ↔ DDD

- **Independent Service Heuristics (ISH)** – deset otázek typu „mohlo by to fungovat jako samostatná SaaS služba?" (brand, tržby, náklady, data, persony, tým, závislosti, dopad, produktová rozhodnutí). Vzniklo v Team Topologies a bylo dopracováno s Nickem Tunem a komunitou DDD London; licence CC BY-SA 4.0 [7]. Autoři ho popisují jako rychlou, komplementární alternativu k DDD postupu hledání hranic.
- **Mini-book *Finding software boundaries for fast flow – Team Topologies and Domain-Driven Design*** [8] s příspěvky Susanne Kaiser, Alberta Brandoliniho, Michaela Plöda, Nicka Tuneho, Riche Allena a Matthewa Skeltona. Jeden z článků je Plödův *Exploring Team and Service Relationships with Team Topologies and Context Maps*.
- **Brandolini (2021)** k mapování interakčních módů na Context Map vzory: překryv existuje (Collaboration ↔ Partnership / Customer-Supplier, Platform ↔ Open Host Service, X-as-a-Service ↔ Customer/Supplier), ale nejde o ekvivalenci. Podstatný rozdíl formuluje takto: „Team Topologies provides a reference towards a desirable to-be state, while DDD context mapping provides more fine-grained patterns for assessing the current state." [6] Context Mapping umí popsat i patologie (Big Ball of Mud, Conformist), pro které v Team Topologies neexistuje mód.
- **Nick Tune, *Architecture Modernization*** (Manning, 2024) – kombinuje strategický DDD, EventStorming, Wardley Mapping a Team Topologies do jednoho postupu modernizace [18].

## 3. Stav praxe a posuny

**Team Topologies se od roku 2019 posunuly.** Druhé vydání (9/2025) mění podtitul z „business and technology teams" na „business and technology" – rámec se explicitně rozšiřuje mimo IT (farmacie, právo, zdravotnictví, státní správa) [3b]. Metafora se posouvá od organizace jako „efficient machine" k „flourishing ecosystem". Kapitola psaná v roce 2026 cituje výhradně první vydání.

**Sami autoři korigovali, co v knize podle nich chybělo.** Skelton (2024): měli více zdůraznit *interakce mezi týmy a evoluci topologií v čase* jako centrální témata, ne statické čtyři typy [19]. To je přímo kontra tomu, jak kapitola téma podává – 05.03 (typy) je dvakrát delší než 05.04 (módy) a evoluce topologií v čase v kapitole není vůbec.

**Team Topologies není org chart.** První kapitola knihy se jmenuje *The Problem with Org Charts*. João Rosa na webu Team Topologies (2024): rámec „nemapuje celý organizační diagram", zaměřuje se na tok hodnoty místo na to, kdo komu reportuje; výsledné diagramy jsou startovní bod, který se musí vyvíjet [19]. Jde o jednu z nejčastějších výtek vůči rámci („neumí popsat celou organizaci") a zároveň o vědomé rozhodnutí autorů.

**DORA metriky se od *Accelerate* (2018) změnily.** Podle oficiální historie DORA [5b]:

- 2021: k dostupnosti přibyla širší **reliability**.
- 2023: „mean time to recover / MTTR" přejmenováno a předefinováno na **failed deployment recovery time** – nová definice měří jen zotavení po selhání způsobeném změnou v produkci, ne po výpadku datacentra.
- 2024: přidána pátá metrika **deployment rework rate** (podíl neplánovaných nasazení vyvolaných incidentem).
- Aktuální sada: change lead time, deployment frequency, failed deployment recovery time (throughput) + change fail rate, deployment rework rate (instability).

Kapitola používá sadu z roku 2018 včetně názvu MTTR (řádky 660–663, 742, 747).

**Platform engineering se stalo samostatnou disciplínou.** Od roku 2022 vznikl celý obor kolem Internal Developer Platform; Team Topologies do něj přispěly konceptem platform-as-a-product a TVP [11]. Kapitola IDP zmiňuje (řádek 177), ale jen jako seznam technologií (K8s, Prometheus, Grafana), ne jako produkt s roadmapou a měřenou adopcí.

**Spotify Model se posunul z reference na varovný příběh.** Kniberg & Ivarsson popsali squads/tribes/chapters/guilds v roce 2012 s výslovnou poznámkou, že jde o „snapshot of our current way of working". Jeremiah Lee, bývalý PM Spotify, publikoval v roce 2020 esej *Spotify's Failed #SquadGoals* s tezí, že model byl z velké části aspirativní a firma uspěla navzdory němu [20]. FAQ kapitoly (řádek 770) tento posun nezmiňuje.

**Kritika Inverse Conway Maneuveru zesílila.** Vedle Fowlerových výhrad [9] existuje série textů, které manévr odmítají u existujících systémů: Tobias Mende, *The Inverse Conway Manoeuvre in Existing Systems – It does not work!* [21], a vícedílná série „ICM: Say no to the Inverse Conway Maneuver" [22]. Argument je konzistentní: reorganizace mění komunikační strukturu okamžitě, ale kódová báze se nezmění, takže nové týmy zdědí cizí kód a organizace projde obdobím, kdy je horší než před reorganizací. Kapitola s tímto obdobím nepočítá – checklist na řádku 351–384 řeší politiku a přípravu, ne propad výkonu během přechodu.

**Kritika Team Topologies obecně.** Nejčastější námitky: rámec svádí ke kopírování vzorů bez pochopení principů; nepokrývá celou organizaci; podceňuje setrvačnost zavedených hierarchií; „end-to-end nezávislé týmy" vyžadují technickou a kulturní zralost, kterou většina organizací nemá [23]. Kapitola má v 05.09 obdobu poslední námitky (Westrum), ostatní ne.

## 4. Symfony / PHP specifika

Kapitola neobsahuje žádný PHP ani Symfony artefakt. Jediné dvě zmínky ekosystému jsou „jeden Symfony monolit" (řádek 509) a „Symfony Bundles pro Symfony" jako nástroj pro hranice modulů v monorepu (řádek 573). Doporučení na řádku 572 přitom vypočítává Nx, Bazel a Turborepo – nástroje pro JS.

Co ekosystém nabízí a co v kapitole chybí:

- **Deptrac** – statická analýza vynucující architektonické hranice. Kanonický balíček je dnes `deptrac/deptrac` (dřívější `qossmic/deptrac`), verze 4.7.x, vyžaduje PHP ≥ 8.2, podporuje více konfiguračních souborů pro různé pohledy na architekturu, což je přímo určené pro monorepo [24]. Použití v CI: pull request, který sáhne z modulu jednoho týmu do modulu druhého, spadne. To je technická implementace pravidla „1 BC = 1 tým" – hranice, kterou nikdo nemůže tiše obejít.
- **PHPArkitect** – alternativa, kterou kniha už používá v kapitole 19 (`content/chapters/microservices_and_ddd.md:163`). Kapitola 05 by na ni měla odkázat, ne zavádět JS nástroje.
- **`CODEOWNERS`** – strojově čitelná Team Map. Kapitola soubor zmiňuje jednou (řádek 573) bez vysvětlení. Přitom je to nejlevnější odpověď na test z callout na řádku 115: „Pokud v org chartu neumíte najít jméno týmu pro každý BC, váš BC je fikce." Správnější test v Symfony repu: `CODEOWNERS` musí mít pravidlo pro každý adresář Bounded Contextu.
- **Composer path repositories** pro Shared Kernel – kapitola 03 to už řeší (`content/chapters/context_mapping.md:179–198`) včetně poznámky o CODEOWNERS pravidle pro review obou týmů. Kapitola 05 na to odkázat může, duplikovat nemá.
- **Symfony Messenger a transport per tým** – hranice mezi X-as-a-Service vztahy dvou týmů se v Symfony realizuje jako samostatný Messenger transport a verzovaný kontrakt integračního eventu (viz `content/chapters/microservices_and_ddd.md:520–780`).
- Cílový stack knihy: PHP ≥ 8.4, `symfony/framework-bundle` 8.0.* (`composer.json`).

Doporučení pro rozsah: kapitola je organizační, takže Symfony sekce má být krátká (30–50 řádků), ale existovat musí. Návrh obsahu: `CODEOWNERS` jako Team Map, Deptrac jako vynucení hranice BC v CI, samostatné CI joby per modul jako předpoklad nezávislého nasazení stream-aligned týmu.

## 5. Sporné a chybně podávané body

**S1 – Směr odvození: BC → týmy, nebo týmy → BC?**
Kapitola odvozuje týmy z Context Mapy (05.05, krok 1–2: „Definovat cílovou architekturu […] Spočítat počet stream-aligned týmů"). Team Topologies kap. 6 se jmenuje *Choose Team-First Boundaries* a argumentuje opačně: hranice se má dimenzovat podle kognitivní kapacity týmu, protože tým je fixní jednotka a doména se dá dělit [16]. Obě čtení jsou v literatuře doložená a nejsou nesmiřitelná, ale kapitola prezentuje jen jedno a bez upozornění. **Doporučení:** podat to jako obousměrné vyjednávání – Context Map dává kandidátní hranice, cognitive load je odmítá nebo potvrzuje.

**S2 – „Nikdy nesdílejte BC mezi týmy" (řádek 98).**
Absolutní formulace naráží na dva zdroje. Evansovy vzory Partnership a Shared Kernel předpokládají dva týmy koordinující se nad společným kódem, a Team Topologies mají pro přesně tuto situaci mód Collaboration, který explicitně povolují jako dočasný. Kapitola sama Collaboration na řádku 255 doporučuje „při bootstrapu nového BC". **Doporučení:** zeslabit na „sdílený BC je vždy dočasný stav s koncovým datem", což je konzistentní i s Team Topologies i se zbytkem kapitoly.

**S3 – Org chart jako test vlastnictví BC (řádky 115–116, 119–121).**
Conway explicitně odlišuje komunikační a administrativní strukturu a org chart uvádí jen jako *jeden z důvodů*, proč vojensky řízené organizace produkují systémy podobné svému diagramu [1b]. První kapitola Team Topologies se jmenuje *The Problem with Org Charts* a João Rosa na webu autorů shrnuje, že rámec záměrně nemapuje reportovací linie [19]. Kapitola tak testuje architekturu proti struktuře, o které oba primární zdroje tvrdí, že není tou relevantní. **Doporučení:** test přeformulovat na Team Map / `CODEOWNERS` (kdo doručuje a drží pohotovost), ne na org chart, a rozdíl mezi topologií týmů a organizační strukturou pojmenovat explicitně.

**S4 – Rubrika cognitive load (řádky 419–486) není z Team Topologies.**
Pětipoložková rubrika se škálou 1–5 je autorský nástroj. Kniha nabízí jedinou otázku na tým a relativní klasifikaci domén simple/complicated/complex s konkrétními heuristikami [14][15]. Navíc od 2. vydání existuje model Weis s 20+ drivery ve 4 clusterech [3b]. Rubrika sama o sobě je legitimní přínos knihy, problém je kontext: stojí bez oddělení mezi odstavci, které se odvolávají na Skeltona a Paise, a čtenář ji přečte jako jejich nástroj. Věta na řádku 484–486 („Skelton a Pais […] výslovně varují před snahou cognitive load objektivizovat přes počet řádků kódu, počet služeb nebo průtok ticketů") se nepodařilo v žádném dohledatelném zdroji potvrdit. **Doporučení:** rubriku ponechat, ale označit jako autorskou a doplnit oficiální heuristiku podle komplexity domén.

**S5 – Poměr 75/15/10 (řádky 547–558).**
Callout tvrdí: „Skelton a Pais konkrétní procenta neuvádějí." Sekundární poznámky ke knize uvádějí poměr stream-aligned ku ostatním týmům 6:1 až 9:1 [15], což je ~86–90 % a s 75 % se nekryje. Zdroj je sekundární, takže tvrzení kapitoly nelze prohlásit za vyvrácené, ale ani ponechat bez ověření. **Doporučení:** ověřit proti knize; do té doby formulovat opatrněji („autoři konkrétní rozdělení organizace v procentech nedávají, uvádějí ale poměr stream-aligned ku ostatním v řádu jednotek ku jedné").

**S6 – Platform team jako jeden tým s velikostí 5–9 a poměrem 1 na 50–150 vývojářů (řádek 187).**
Definice autorů zní „a grouping of other team types" [3d] a platformu popisují jako fraktální útvar [11]. Číslo 50–150 nemá zdroj a koliduje s doporučením TVP, které velikost platformy odvozuje od skutečné potřeby, ne od počtu vývojářů. **Doporučení:** nahradit číselné pravidlo konceptem TVP a poznámkou, že platforma může být uvnitř sama členěná na týmy.

**S7 – „Enabling team se po předání rozpustí" (řádky 195–196, 202).**
Kniha říká, že enabling tým vytváří dočasnou závislost („after a few weeks or months; there should not be a permanent dependency") [16] – dočasná je **interakce**, ne existence týmu. Enabling tým jako útvar je v knize dlouhodobý a rotuje mezi stream-aligned týmy; kapitola to sama o dva odstavce dál na řádku 277 tvrdí správně („Po dosažení cíle Enabling team odejde k jinému stream-aligned týmu"), takže si v rámci jedné sekce protiřečí. Časový údaj „3–6 měsíců" v knize doložen není. **Doporučení:** sjednotit – time-boxed je interakce Facilitating, ne tým.

**S8 – Zastaralá sada DORA metrik (řádky 653–667, 742, 747, 704–713).**
Kapitola uvádí čtyři metriky ve verzi z roku 2018 včetně MTTR. Od 2023 je metrika přejmenovaná a předefinovaná, od 2024 existuje pátá [5b]. **Doporučení:** doplnit současnou sadu a poznámku, že MTTR je historický název.

**S9 – Číselné sliby o zlepšení (řádky 665–667).**
„Lead time se orientačně zkrátí o 30–80 %, change failure rate se sníží o 30–50 %" nemá zdroj. Podobně vzorový pitch (řádek 705–706) přisuzuje benchmark „high-performers" jmenovitě Googlu, Amazonu a Netflixu – DORA benchmarky jsou agregované napříč tisíci respondenty a ke konkrétním firmám se nevztahují. **Doporučení:** čísla vypustit nebo nahradit odkazem na aktuální DORA report bez jmen firem; pitch přeformulovat na „percentil vysoce výkonných organizací podle DORA".

**S10 – Práh „méně než 7 z 10 týmů stream-aligned = anti-vzor" (řádek 172).**
Autorský odhad prezentovaný jako pozorování. **Doporučení:** označit jako heuristiku, nebo nahradit poměrem z knihy (viz S5).

**S11 – Spotify Model ve FAQ (řádek 770).**
Tři sporná místa: (a) tvrzení, že tribes nemají v Team Topologies ekvivalent – kniha používá Dunbarovy hranice 50 a 150, což jsou přesně velikosti tribe [17]; (b) tvrzení, že se autoři tribes „vyhnuli, protože zkušenosti ukazují, že se stávají Conway-stylové divize" – bez zdroje; (c) chybí, že model byl podle Kniberga a Ivarssona snapshot, ne předpis, a že jej Jeremiah Lee (2020) zpětně označil za neúspěšný [20]. **Doporučení:** přepsat odpověď kolem rozdílu deskriptivní snapshot vs. rámec a doplnit Leeho kritiku.

**S12 – Amazon 2002 (řádky 330–344).**
Kapitola už správně uvádí, že primární dokument nebyl zveřejněn a zdrojem je Yeggeho text z roku 2011. Doplnit stojí, že Yegge text zveřejnil na Google+ omylem (mířil interně) a že formulace o propuštění je jeho parafráze [12]. Jinak je pasáž v pořádku.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | zastaralé | `team_topologies.md:148–150`, `787–788` | Cituje jen 1. vydání Team Topologies (2019); 2. vydání vyšlo 23. 9. 2025 se změněným podtitulem, novou předmluvou a povýšenou rolí cognitive load | Doplnit 2. vydání do bibliografie i do textu; uvést, co se změnilo |
| G2 | chybí | 05.01 (27–81) | Z Conwayova eseje se přebírá jen závěrečná věta; chybí homomorfismus, který je vlastním argumentem | Doplnit 8–12 řádků o homomorfismu a rozhraní jako důsledku dohody dvou skupin |
| G3 | sporné | `:115–121` | Test „org chart vs. Context Map" – Conway i Team Topologies org chart jako referenci odmítají | Přeformulovat na Team Map / `CODEOWNERS`; pojmenovat rozdíl mezi topologií a organizační strukturou |
| G4 | chybí | 05.01, 05.02 | Chybí explicitní věta, že komunikační struktura ≠ organizační struktura (zadání studie na to cílí) | Nová podsekce ~15 řádků |
| G5 | sporné | `:98` | „Nikdy nesdílejte BC mezi týmy" je tvrdší než Evans i Skelton & Pais | Zeslabit na „sdílený BC je dočasný stav s koncovým datem" |
| G6 | nepodložené | `:166`, `:187`, `:196` | Velikosti a doby (5–9 přes Two-Pizza Rule, 1 platform na 50–150 vývojářů, enabling 3–6 měsíců) bez zdroje | Nahradit Dunbarovými hranicemi z knihy; platformu popsat přes TVP; time-box vázat na interakci |
| G7 | sporné | `:195–196` vs. `:277` | Kapitola tvrdí, že enabling team se rozpustí, o kus dál že přechází k jinému týmu | Sjednotit: dočasná je interakce, ne tým |
| G8 | chybí | 05.03 (174–190) | Platform team podán jako jeden tým s technologickým stackem; chybí TVP a platform-as-a-product | Přepsat sekci kolem TVP a měření adopce |
| G9 | chybí | 05.03/05.04 | Chybí Team API – explicitní rozhraní týmu, které je v knize předpokladem X-as-a-Service | Doplnit ~15 řádků |
| G10 | chybí | 05.02 (83–144) | Chybí fracture planes (TT kap. 6) – hlavní technika, kterou kniha spojuje hranice softwaru s hranicemi týmů | Nová podsekce ~25 řádků |
| G11 | chybí | 05.02 nebo 05.05 | Chybí Independent Service Heuristics – deset otázek, oficiální nástroj Team Topologies vytvořený s DDD komunitou, CC BY-SA | Nová podsekce ~30 řádků s odkazem na kapitolu o Event Stormingu |
| G12 | chybí | celá kapitola | Chybí evoluce topologií v čase (TT kap. 8); Skelton sám v roce 2024 uvedl, že tohle měli zdůraznit nejvíc | Nová sekce ~30 řádků |
| G13 | mělké | 05.04 (241–294) | Tři módy jsou popsané na 54 řádcích proti 94 řádkům u typů týmů, přestože autoři je označují za důležitější | Rozšířit; doplnit, jak se mód mění v čase |
| G14 | nepodložené | `:257`, `:267` | Mapování módů na Context Map vzory bez zdroje, prezentované jako jednoznačné | Doplnit zdroj (Brandolini 2021) a jeho výhradu: překryv, ne ekvivalence; Context Mapping popisuje as-is včetně patologií |
| G15 | mělké | 05.06 (386–407) | Sweller správně, ale metoda měření knihy chybí | Doplnit oficiální otázku a heuristiky simple/complicated/complex |
| G16 | nepodložené | `:484–486` | Tvrzení, že autoři „výslovně varují" před objektivizací cognitive load přes LOC/služby/tickety, se nepodařilo doložit | Doložit, nebo přeformulovat na autorský názor |
| G17 | nepodložené | `:412–417` | Tabulka „velikost týmu → počet BC" je autorská, ale stojí bez odlišení vedle citovaných tvrzení | Označit jako heuristiku knihy a postavit vedle ní oficiální heuristiku podle komplexity domén |
| G18 | sporné | `:550` | „Skelton a Pais konkrétní procenta neuvádějí" – sekundární zdroje uvádějí poměr 6:1–9:1 | Ověřit proti knize a formulaci opravit |
| G19 | zastaralé | `:653–667`, `:742`, `:747` | DORA sada z roku 2018 včetně MTTR; od 2023 přejmenováno, od 2024 pátá metrika | Aktualizovat na současnou sadu, MTTR uvést jako historický název |
| G20 | nepodložené | `:665–667`, `:705–706` | Čísla o zlepšení (30–80 %, 30–50 %) a přiřazení DORA benchmarku jmenovitě Googlu/Amazonu/Netflixu | Vypustit nebo nahradit odkazem na aktuální DORA report |
| G21 | chybí | 05.05 (296–349) | Chybí Fowlerovy výhrady a novější kritika Inverse Conway Maneuveru u existujících systémů | Doplnit podsekci „Kdy Inverse Conway nefunguje" ~25 řádků |
| G22 | sporné | `:770` (FAQ Spotify) | Tvrzení o tribes bez zdroje; chybí, že model byl snapshot a že jej Lee (2020) označil za neúspěšný | Přepsat odpověď |
| G23 | chybí | celá kapitola | Nula Symfony/PHP obsahu v knize o DDD v Symfony 8 | Nová sekce ~40 řádků: `CODEOWNERS` jako Team Map, Deptrac v CI, CI job per modul |
| G24 | zastaralé | `:572–573` | Pro vynucení hranic modulů doporučuje Nx, Bazel, Turborepo (JS) a „Symfony Bundles“ | Nahradit Deptrac / PHPArkitect s odkazem na `microservices_and_ddd.md:163` |
| G25 | nadbytečné | `:509`, `:776` | Odkazy na `/architektonicke-styly` u modulárního monolitu a distribuovaného monolitu; obojí rozebírá kapitola 19 (`/ddd-a-microservices`) | Přesměrovat na `/ddd-a-microservices#modular-monolith` a `#distributed-monolith` |
| G26 | chybí | celá kapitola | Kapitola nikde neodkazuje na `/event-storming` (kap. 04), přestože hranice se hledají workshopem | Doplnit odkaz v sekci o hledání hranic |
| G27 | mělké | `:80–81` | Jediný diagram na 803 řádků | Doplnit 2 diagramy: mapa 4 typů + 3 módů, a mapování Context Map ↔ Team Map |
| G28 | mělké | `:141–144` | Tvrzení o rostoucím lead time a change failure rate při nesouladu bez opory | Buď doložit z DORA/Accelerate, nebo formulovat jako pozorování |

## 7. Doporučení k přepisu

**P1-1 — Opravit test vlastnictví BC: Team Map místo org chartu.**
Conway explicitně odlišuje komunikační a administrativní strukturu a org chart uvádí jen jako důsledek striktní hierarchie. První kapitola Team Topologies se jmenuje *The Problem with Org Charts*. Kapitola tedy staví ústřední test na referenci, kterou oba primární zdroje odmítají. Zásah: přepis callout na řádku 107–117 a odstavce 119–121 plus nová podsekce o rozdílu topologie vs. organizační struktura. `přepis dvou pasáží + nová podsekce ~15 řádků`

**P1-2 — Aktualizovat DORA a odstranit nepodložená čísla.**
Sada z roku 2018 je po přejmenování MTTR (2023) a přidání deployment rework rate (2024) neaktuální; sliby o 30–80% zlepšení a jmenovité benchmarky firem nemají zdroj a v kapitole, která management přesvědčuje čísly, jsou nejzranitelnější místo. Zásah: přepis 05.09 sekce DORA a vzorového pitche. `přepis sekce 05.09 (~40 řádků)`

**P1-3 — Doplnit 2. vydání Team Topologies (2025) a to, co autoři sami korigovali.**
Kniha vyšla znovu v září 2025 s novým podtitulem, rozšířením mimo IT a novým modelem kognitivní zátěže (Weis, 20+ driverů, 4 clustery). Skelton navíc v roce 2024 řekl, že měli více zdůraznit interakce a evoluci topologií. Kapitola datovaná 2026 citující jen vydání 2019 je faktograficky pozadu. Zásah: bibliografie, úvod 05.03, nová podsekce v 05.06. `oprava bibliografie + ~20 řádků textu`

**P1-4 — Opravit rozpor u enabling teamu a odstranit nedoložené číselné parametry.**
Kapitola na jedné straně tvrdí, že se enabling tým rozpustí, na druhé že přechází k jinému týmu; navíc uvádí velikosti a poměry (1 platform na 50–150 vývojářů, 3–6 měsíců), které v knize nejsou. Zásah: přepis podsekcí Platform team a Enabling team. `přepis dvou podsekcí v 05.03 (~35 řádků)`

**P1-5 — Odlišit autorské heuristiky od citovaných tvrzení.**
Rubrika cognitive load, tabulka „velikost týmu → počet BC", práh 7 z 10 týmů i poměr 75/15/10 jsou autorské. Stojí bez vizuálního oddělení vedle vět odkazujících na Skeltona a Paise. Čtenář si je připíše knize. Zásah: doplnit u každé z nich větu „autorská heuristika, v knize takto uvedena není" a vedle rubriky uvést oficiální nástroj. `~10 vět napříč 05.03, 05.06, 05.07`

**P2-1 — Nová sekce: fracture planes a Independent Service Heuristics.**
Toto je chybějící most mezi DDD a Team Topologies. Fracture planes (TT kap. 6) říkají, kudy systém řezat; ISH je deset otázek, které vznikly přímo ve spolupráci autorů s DDD komunitou a jsou pod CC BY-SA. Bez nich kapitola tvrdí „1 BC = 1 tým", ale nedává nástroj, jak k tomu BC dojít. Zásah: nová sekce mezi 05.02 a 05.03, odkaz na kapitolu o Event Stormingu. `nová sekce ~55 řádků`

**P2-2 — Nová sekce: Symfony a PHP realizace vlastnictví hranic.**
Kniha je o DDD v Symfony 8 a jediná kapitola bez PHP obsahu vyčnívá. Obsah: `CODEOWNERS` jako strojově čitelná Team Map, Deptrac (`deptrac/deptrac` 4.7.x, PHP ≥ 8.2) jako vynucení hranice BC v CI, samostatný CI job per modul jako předpoklad nezávislého nasazení. Náhrada za doporučení Nx/Bazel/Turborepo na řádku 572. `nová sekce ~40 řádků + oprava jednoho odstavce v 05.08`

**P2-3 — Doplnit kritiku Inverse Conway Maneuveru.**
Fowler manévr označuje za užitečný nástroj, ale varuje, že reorganizace neopraví zabetonovanou architekturu a může vytvořit nesoulad mezi lidmi a kódem; novější texty jdou dál a u existujících systémů jej odmítají. Kapitola manévr podává jako přímočarý čtyřkrokový postup. Doplnění zvyšuje důvěryhodnost, protože čtenář na tento problém v praxi narazí. Zásah: nová podsekce v 05.05. `nová podsekce ~25 řádků`

**P2-4 — Nová podsekce: evoluce topologií v čase.**
Team Topologies kap. 8 (*Evolve Team Structures with Organizational Sensing*) a Skeltonova vlastní korekce z roku 2024. Kapitola je dnes statická: popíše cílový stav a končí. Zásah: nová sekce před 05.08 nebo rozšíření 05.07. `nová sekce ~30 řádků`

**P2-5 — Přepsat FAQ o Spotify Modelu a opravit chybné odkazy na kapitoly.**
FAQ obsahuje nepodložené tvrzení o tribes a neobsahuje Leeho kritiku z roku 2020. Zároveň dvě místa (řádky 509 a 776) posílají čtenáře na `/architektonicke-styly` u témat, která rozebírá kapitola 19 `/ddd-a-microservices`. `přepis jedné FAQ odpovědi + dva odkazy`

**P2-6 — Rozšířit 05.04 o interakční módy a jejich mapování na Context Map.**
Doplnit Brandoliniho zdroj a jeho výhradu (překryv, ne ekvivalence; Context Mapping umí popsat i patologie, pro které mód neexistuje) plus mini-book Team Topologies × DDD jako doporučenou četbu. `rozšíření sekce 05.04 o ~25 řádků`

**P3-1 — Doplnit dva diagramy.**
Jeden diagram na 803 řádků je málo. Návrh: (a) matice 4 typů týmů × 3 módů, (b) překryv Context Map a Team Map na konkrétním příkladu ze scénáře B. `2 nové .puml + SVG`

**P3-2 — Doplnit Team API.**
Krátký koncept, který dává X-as-a-Service konkrétní obsah a v Symfony repu má přímý ekvivalent (README modulu + kontrakt integračního eventu + `CODEOWNERS`). `~15 řádků`

**P3-3 — Doplnit odkaz na Nickovu Tuneho *Architecture Modernization* (2024) do další četby.**
Je to dnes nejúplnější zpracování kombinace strategického DDD a Team Topologies a přirozený „co číst dál". `1 položka bibliografie`

## 8. Otevřené otázky pro autora

1. **Držet, nebo zrušit rubriku cognitive load (řádky 436–481)?** Je to 45 řádků kódového bloku pro nástroj, který kniha nikde jinde nepoužívá a který není z Team Topologies. Varianty: ponechat a označit jako autorský, zkrátit na tři otázky, nebo nahradit oficiální heuristikou podle komplexity domén.
2. **Kolik prostoru dát managementu?** Sekce 05.09 (77 řádků) plus vzorový pitch je největší nesoftwarová část celé knihy. Je cílová skupina knihy team-lead, který pitch skutečně použije, nebo vývojář, kterému stačí vědět, že reorganizace je politický akt?
3. **Kapitola 05 vs. kapitola 19.** Témata mikroservis, distribuovaného monolitu a modulárního monolitu se objevují v obou. Kde je hranice? Návrh: kapitola 05 řeší jen týmovou stranu a všechny architektonické důsledky odkazuje na 19.
4. **Přebírat sekundární poměr 6:1–9:1?** Bez ověření proti tištěné knize jde o poznámky třetí strany. Má se do knihy dostat s výhradou, nebo se má poměr vypustit úplně a nechat jen kvalitativní „stream-aligned musí jasně dominovat"?
5. **Rozsah 803 řádků.** Doporučení P1+P2 přidávají zhruba 200 řádků a odebírají zhruba 60. Je 950 řádků pro kapitolu kategorie Základy s reading time 22 minut přijatelné, nebo se má něco vypustit (kandidát: scénáře 05.07, které se částečně opakují ve FAQ)?
6. **Ilustrativní scénáře.** Tři případy Conway's Law (řádky 49–66) i scénáře A/B/C jsou fiktivní. Konvence knihy je označovat je „Ilustrativní scénář". V této kapitole označené nejsou.

## 9. Bibliografie

### Ověřené zdroje

[1a] Conway, M. E. — *How Do Committees Invent?* Datamation 14(4), duben 1968, s. 28–31. Text: https://www.melconway.com/research/committees.html (přístup 2026-09-03)
[1b] Conway, M. E. — tentýž esej, sken PDF. https://melconway.com/Home/pdf/committees.pdf (přístup 2026-09-03) — z něj ověřeny verbatim citace o homomorfismu, administrativní struktuře, zaujetí návrhové skupiny a závěru
[1c] Conway, M. E. — *Conway's Law* (autorova poznámka k eseji, odmítnutí HBR 1967, atribuce názvu Fredu Brooksovi). https://www.melconway.com/Home/Conways_Law.html (přístup 2026-09-03)
[2] Vernon, V. — *Implementing Domain-Driven Design*. Addison-Wesley, 2013
[3a] Skelton, M. & Pais, M. — *Team Topologies: Organizing Business and Technology Teams for Fast Flow*. IT Revolution, 2019. https://teamtopologies.com/book (přístup 2026-09-03)
[3b] Skelton, M. & Pais, M. — *Team Topologies, 2nd Edition*, oznámení autorů (podtitul, změny, model kognitivní zátěže s Dr. Laurou Weis). https://teamtopologies.com/news-blogs-newsletters/the-second-edition-of-team-topologies-is-now-available (přístup 2026-09-03)
[3c] IT Revolution — *Team Topologies, 2nd Edition* (datum vydání 23. 9. 2025, 19 příkladů z praxe, 38 obrázků). https://itrevolution.com/product/team-topologies-second-edition/ (přístup 2026-09-03)
[3d] Team Topologies — *Key concepts* (oficiální definice 4 typů týmů a 3 módů). https://teamtopologies.com/key-concepts (přístup 2026-09-03)
[4] Forsgren, N., Humble, J. & Kim, G. — *Accelerate: The Science of Lean Software and DevOps*. IT Revolution, 2018
[5a] Westrum, R. — *A typology of organisational cultures*. Quality and Safety in Health Care 13(suppl 2), 2004, ii22–ii27. https://pubmed.ncbi.nlm.nih.gov/15576687/ (přístup 2026-09-03)
[5b] DORA — *A history of DORA's software delivery metrics* (přejmenování MTTR 2023, reliability 2021, deployment rework rate 2024). https://dora.dev/insights/dora-metrics-history/ (přístup 2026-09-03)
[6] Brandolini, A. — *About Team Topologies and Context Mapping*. Avanscoperta Blog, 22. 4. 2021. https://blog.avanscoperta.it/2021/04/22/about-team-topologies-and-context-mapping/ (přístup 2026-09-03)
[7] Team Topologies — *Independent Service Heuristics* (10 otázek, CC BY-SA 4.0, spoluautorství Nick Tune a DDD London). https://github.com/TeamTopologies/Independent-Service-Heuristics (přístup 2026-09-03)
[8] Team Topologies — mini-book *Finding software boundaries for fast flow – Team Topologies and Domain-Driven Design* (Kaiser, Brandolini, Plöd, Tune, Allen, Skelton). https://teamtopologies.com/all-mini-books/finding-software-boundaries-for-fast-flow-team-topologies-and-domain-driven-design-mini-book-mb81-v1 (přístup 2026-09-03)
[9] Fowler, M. — *Conway's Law* (bliki; atribuce Inverse Conway Maneuveru LeRoyovi a Simonsovi, Cutter IT Journal 12/2010; výhrady k účinnosti). https://martinfowler.com/bliki/ConwaysLaw.html (přístup 2026-09-03)
[10] Team Topologies — *Team Cognitive Load Assessment* (šablona, CC BY-SA 4.0). https://github.com/TeamTopologies/Team-Cognitive-Load-Assessment (přístup 2026-09-03)
[11] Team Topologies — *What is a Thinnest Viable Platform (TVP)?* (platforma jako produkt, fraktální struktura). https://teamtopologies.com/key-concepts-content/what-is-a-thinnest-viable-platform-tvp (přístup 2026-09-03)
[12] Yegge, S. — *Stevey's Google Platforms Rant*, 2011. https://gist.github.com/chitchcock/1281611 (přístup 2026-09-03)
[13] IT Revolution — *Team Cognitive Load* (výtah z knihy: Swellerovy tři typy zátěže). https://itrevolution.com/articles/cognitive-load/ (přístup 2026-09-03)
[14] Heuristiky komplexity domén (simple / complicated / complex, 2–3 simple na tým, complex tým nedostane nic dalšího, vyhnout se dvěma complicated) — sekundární shrnutí knihy, viz [15] a marcabraham.com/2023/07/13/team-topologies-book-review/ (přístup 2026-09-03)
[15] Lebrero, D. — *Book notes: Team Topologies*, 2021 (struktura Part I–III, otázka na měření cognitive load, poměr stream-aligned ku ostatním). https://danlebrero.com/2021/01/20/team-topologies-summary/ (přístup 2026-09-03)
[16] Poznámky ke kapitolám knihy včetně názvů kapitol 1–8, fracture planes, lakmusového testu a dočasnosti enabling závislosti. https://hackmd.io/@ez4gDju3Sb2ZfBU6cjeuow/S1LO13ykU (přístup 2026-09-03)
[17] Team Topologies — *Trust Boundaries template* (Dunbarovy hranice 5 / 15 / 50 / 150). https://github.com/TeamTopologies/Trust-Boundaries-template (přístup 2026-09-03)
[18] Tune, N. — *Architecture Modernization: Socio-technical alignment of software, strategy, and structure*. Manning, 2024. https://leanpub.com/arch-modernization-ddd (přístup 2026-09-03)
[19] Rosa, J. — *The most important part of Team Topologies is also the one most people overlook*, teamtopologies.com, srpen 2024 (Skeltonova korekce; Team Topologies nemapuje org chart). https://teamtopologies.com/news-blogs-newsletters/2024/8/30/the-important-part-of-team-topologies-that-people-overlook (přístup 2026-09-03)
[20] Kniberg, H. & Ivarsson, A. — *Scaling Agile @ Spotify*, 2012; Lee, J. — *Spotify's Failed #SquadGoals*, 2020
[21] Mende, T. — *The Inverse Conway Manoeuvre in Existing Systems – It does not work!* https://mende.io/blog/the-inverse-conway-manoeuvre-in-existing-systems/ (přístup 2026-09-03)
[22] Dupdob — série *ICM: Say no to the 'Inverse Conway Maneuver'*. https://medium.com/@Cyrdup/icm-1-say-no-to-the-inverse-conway-maneuver-6672ba2373cb (přístup 2026-09-03)
[23] Oost, M. — *Stop Team Topologies* / *Stop Simplisticism* (kritika kopírování vzorů bez principů). https://martyoo.medium.com/stop-team-topologies-fd954ea26eca (přístup 2026-09-03)
[24] Deptrac — balíček `deptrac/deptrac` (dříve `qossmic/deptrac`), verze 4.7.1, PHP ≥ 8.2, podpora více konfigurací pro monorepo. https://packagist.org/packages/deptrac/deptrac (přístup 2026-09-03)

### Neověřené / nedohledané

- **Přesný název článku LeRoye a Simonse** v Cutter IT Journal 12/2010. Fowler [9] uvádí jen autory, časopis a datum. Číslo Cutter IT Journal není volně dostupné. Kapitola název neuvádí, což je zatím správné řešení.
- **Pravidlo „1 BC = 1 tým“ – OVĚŘENO 2026-09-04 v *IDDD*. Vernon ho formuluje jako preferenci
  a výslovně varuje před rigidním čtením.** Doslova: *„a single Bounded Context is not an attempt
  to limit flexibility to team organization. It’s not as if teams can’t be arranged as needed,
  or that individual members of one team cannot be used on one or more other projects. A company
  should use people in the way that best fits its needs. This is simply stating that it is best
  for one well-defined, cohesive team […]“*

  **Dopad na řádky 85–90:** parafráze „1 BC = 1 tým“ jako pravidlo jde dál než Vernon. On říká
  „it is best for“, a hned k tomu přidává výhradu, že organizaci týmů to omezovat nemá.
  **Doporučení: doplnit tu výhradu.** Kapitola tím získá přesnější tvrzení a zároveň odpověď na
  námitku, kterou si čtenář položí sám.je; doslovné znění se z veřejných zdrojů ověřit nepodařilo. Před přepisem ověřit proti knize, zejména zda Vernon skutečně říká „nikdy“, nebo mírnější formulaci.
- **Varování před měřením cognitive load přes LOC (`:484–486`) – POTVRZENO 2026-09-04 ze
  zakoupeného výtisku. Skelton a Pais to říkají výslovně a ostřeji, než kapitola naznačuje:**

  > *„Trying to determine the cognitive load of software using simple measures such as lines of
  > code, number of modules, classes, or methods is **misguided**.“*

  Argument doplňují odkazem na výzkum: Graylin Jay a kolegové v roce 2009 ukázali, že jazyky se
  liší v upovídanosti, a s nástupem microservices se polyglotní systémy staly běžnými – takže
  LOC nesrovnává srovnatelné.

  Na jiném místě to zpřesňují: jistá korelace mezi velikostí systému v řádcích kódu a zátěží sice
  existuje, ale rozhodující je *„the limit on cognitive capacity to handle changes to the system
  in an effective way“*. **Doporučení: citovat slovo „misguided“ i odkaz na Jaye;** kapitola tím
  získá doložené varování místo obecné výhrady.
- **Poměr stream-aligned týmů 6:1 až 9:1 – OVĚŘENO 2026-09-04 ze zakoupeného výtisku
  (1. vydání, 2019). Čísla sedí, ale mají u sebe výhradu, kterou kapitola vynechává.** Znění:

  > *„TIP: For organizations that are successful at delivering software rapidly and safely, most
  > teams are stream aligned, with only around one in seven to one in ten teams not stream
  > aligned. That is, **based on what successful organizations report**, the ratio of
  > stream-aligned teams to other kinds of teams should be between about 6:1 and 9:1.“*

  Dvě věci k zachování při citaci. Za prvé, je to označeno jako **TIP**, ne jako výsledek měření.
  Za druhé, opírá se o to, co organizace **samy hlásí** – tedy sebehodnocení, ne nezávislá data.
  Kapitola má poměr uvádět s touto výhradou, jinak z doporučení dělá naměřenou hodnotu.

  Autoři hned dodávají příklad převodu: DBA týmy se často dají změnit na enabling teams, pokud
  přestanou pracovat na úrovni aplikace a začnou šířit povědomí o výkonu a monitoringu databází
  ke stream-aligned týmům. To je konkrétní, do kapitoly použitelné.
- **Přesné znění otázek v šabloně Team Cognitive Load Assessment.** Veřejný repozitář [10] obsahuje jen odkaz na Google Form; sekce „Overview", „How to use" a „Template" jsou prázdné.
- **Obsahové změny 2. vydání Team Topologies mimo předmluvu a případové studie.** Zda byly upraveny definice čtyř typů nebo tří módů, se z veřejných materiálů zjistit nepodařilo. Nutné porovnat výtisky.
- **Helfand, H. — *Dynamic Reteaming* (O'Reilly)** jako protiváha k jednorázové reorganizaci. Kniha existuje, ale rok vydání 2. edice ani konkrétní tvrzení jsem neověřoval; pokud se má do bibliografie dostat, dohledat ručně.
- **DORA 2025/2026 – OVĚŘENO 2026-09-04. Nález je větší než čísla: klasifikace zanikla.**
  Report *State of DevOps 2025* opustil čtyřstupňové dělení Elite / High / Medium / Low, které kniha
  používá, a nahradil je **sedmi týmovými profily**, které vedle výkonu doručování hodnotí i kulturní
  a lidské signály. Zároveň se zpřísnila hranice change failure rate: ideál pro špičkový výkon je
  podle reportu 2025 **0–2 %** (dosahuje ho 16,7 % respondentů), zatímco starší benchmark „elite“
  pracoval s pásmem 0–15 %.

  **Dopad na kapitolu:** hodnota „pod 15 %“ na řádcích, kde kapitola benchmark uvádí, je
  z předchozí metodiky a dnes odpovídá spíš průměru než špičce. Horší je ale odkaz na kategorii
  „elite performers“ jako takovou – ta v aktuálním reportu neexistuje. **Doporučení: buď citovat
  konkrétní číslo s uvedením roku reportu („podle DORA 2025 …“), nebo benchmark vypustit
  a nechat jen kvalitativní tvrzení. Odkazovat na „elite“ bez ročníku je od 2025 zavádějící.**

