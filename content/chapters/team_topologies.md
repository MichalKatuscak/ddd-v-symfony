---
route: team_topologies
path: /team-topologies
title: Conway's Law a Team Topologies
page_title: "Conway's Law a Team Topologies v DDD | DDD Symfony"
meta_description: "Bounded Context jako týmová hranice. Conway's Law, Team Topologies (Skelton & Pais) a Inverse Conway Maneuver – jak rozdělit týmy kolem DDD."
meta_keywords: "Conway's Law, Team Topologies, Inverse Conway Maneuver, Skelton, Pais, stream-aligned team, platform team, enabling team, complicated subsystem team, Bounded Context, DDD, kognitivní zátěž, Westrum, Vernon, organizační struktura, microservices"
og_type: article
published: "2026-04-29"
modified: 2026-09-24
breadcrumb_name: Team Topologies
schema_type: TechArticle
schema_headline: "Conway's Law a Team Topologies – týmová struktura v DDD"
chapter_number: "05"
category: Základy
deck: 'Když Conway v roce 1968 publikoval tezi, že „systém kopíruje komunikační strukturu organizace, která ho stvořila“, popisoval gravitační zákon softwarového designu. DDD Bounded Contexts dávají smysl jen tehdy, když mapují na týmy – jinak vznikají falešné hranice. Kapitola o tom, jak vědomě navrhnout týmy kolem domény.'
reading_time: 37
difficulty: 2
github_examples: null
---

Většina knih o DDD končí Bounded Contextem a Context Mapou, jako by architektura žila ve vakuu.
Jakmile ale máte víc než jeden tým, organizační struktura začne tlačit architekturu
do svého obrazu. Kapitola popisuje toto *gravitační pole*: Conway's Law z roku 1968
a Team Topologies (Skelton & Pais 2019) jako rámec pro vědomý návrh týmů. A vysvětluje, proč je
**jeden Bounded Context = jeden tým** první DDD pravidlo, které vám management poruší.

## 05.01 Conway's Law – gravitační zákon softwarové architektury {#conway-law}

V dubnu 1968 vyšel v časopise *Datamation* krátký esej Melvina Conwaye s názvem
*How Do Committees Invent?* [[1]](http://www.melconway.com/Home/Committees_Paper.html).
Conway v něm formuloval pozorování, které se později stalo známé jako **Conway's Law**:

> „Organizations which design systems (in the broad sense used here) are constrained
> to produce designs which are copies of the communication structures of these
> organizations.“
>
> – Melvin E. Conway, 1968

V překladu: **organizace navrhující systémy jsou nuceny vytvářet designy,
které kopírují jejich komunikační struktury.** Volněji: design systému kopíruje
komunikační strukturu organizace.
Conway nic nepředepisuje, popisuje *empirické pozorování*. Oddělený frontend
a backend tým? Dostanete oddělený frontend a backend v kódu. Oddělený DBA tým?
V kódu se objeví vrstva, která jen obsluhuje databázi. A jeden tým bez vnitřních hranic
vyrobí Big Ball of Mud.

Tezi Conway nepodává jako slogan, ale dokládá ji strukturně. Každému uzlu návrhu
odpovídá jedna návrhová skupina a každé větvi mezi uzly dohodnuté rozhraní
mezi dvěma skupinami. V eseji to formuluje jako matematik: mezi grafem systému
a grafem navrhující organizace existuje homomorfismus. Rozhraní v kódu tedy zapisuje dohodu dvou skupin lidí.

### Komunikační struktura není organizační diagram {#komunikacni-struktura}

Conway mluví o komunikační struktuře, ne o reportovacích linkách. Obojí splývá jen
tam, kde formální hierarchie skutečně určuje, kdo s kým smí mluvit; sám to uvádí jako
důvod, proč vojensky řízené organizace produkují systémy podobné svému diagramu.
Jinde rozhoduje, kdo s kým denně řeší práci.

Rozdíl platí pro celou kapitolu. „Týmová hranice“ dál znamená tým,
který doručuje a drží pohotovost, ne políčko v organizačním diagramu. Team Topologies
staví na stejném rozlišení: první kapitola knihy se jmenuje *The Problem with Org Charts*.

### Tři reálné případy Conway's Law v praxi

1. **Tým rozdělený podle vrstev → Layered Architecture.**
   Společnost s 30 vývojáři rozdělená na „frontend tým“, „backend tým“ a „DBA tým“
   vyrobí třívrstvou architekturu. Každý tým má vlastní release cyklus,
   vlastní CI/CD pipeline, vlastní sprint review. Bounded Context je v lepším případě
   interní záležitostí backend týmu; frontend a DBA o něm nevědí. *Důsledek:*
   změna jednoho doménového požadavku projde všemi třemi týmy a třemi sprinty.

2. **Tým rozdělený podle produktu/streamu → mikroservis nebo modul per BC.**
   Stejná organizace přeorganizovaná na „Catalog tým“, „Ordering tým“, „Billing tým“
   a „Identity tým“ vyrobí 4 mikroservisy nebo 4 izolované moduly v monolitu,
   jeden na každý Bounded Context. Každý z týmů je plně end-to-end: frontend, backend, DB, devops.
   Conway's Law platí dál, jen dostala jiné vstupy.

3. **Tým bez vnitřních hranic → Big Ball of Mud.**
   8 vývojářů, kteří všichni sahají do všeho, žádné Bounded Contexts nevytvoří.
   Vznikne jeden monolit, ve kterém je *Customer* ve fakturaci tatáž třída jako
   *Customer* v marketingu, jen s víc atributy. Klasický důsledek: po 18 měsících
   si nikdo netroufne změnit nic, protože „to může mít vliv kdekoli“.

:::callout{type="note"}
### „Law“ je trochu silné slovo {#conway-not-law-heading}

Conway's Law popisuje silnou tendenci, ne fyzikální zákonitost. Stojí
za ní praktický fakt: lidé, kteří spolu denně mluví, koordinují sdílená
rozhodnutí přímo. Kdo spolu nemluví, nahrazuje koordinaci stabilním rozhraním
(API, schématem, kontraktem). Tato rozhraní časem ztuhnou v architektonické
švy. Conway's Law je tedy **statistický důsledek nákladů na komunikaci**,
ne metafyzika. Obejít ji jde, ale jen vědomým úsilím (Inverse Conway Maneuver,
sekce 05.05).
:::

:::diagram{fig="05.1-A" title="Conway vs. Inverse Conway Maneuver" src="images/diagrams/18_team_topologies/conway_inverse.svg"}
:::

## 05.02 Bounded Context = týmová hranice {#bc-team-boundary}

Vaughn Vernon v knize *Implementing Domain-Driven Design* (2013, kap. 2)
[[2]](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)
formuluje doporučení, které je možná nejužitečnějším praktickým výstupem celého DDD:
Bounded Context má vlastnit jediný tým. Obrácená situace je podle Vernona přijatelná:
jeden tým vlastní více Bounded Contexts. V *Domain-Driven Design Distilled* (2016, kap. 2)
to zopakoval: více týmů nemá sdílet jeden kontext.

Vernon to ovšem nepodává jako zákon. Píše, že jediný Bounded Context není pokus omezovat
flexibilitu týmové organizace, a hned dodává, že firma má lidi využívat tak, jak potřebuje.
Členové jednoho týmu mohou vypomáhat na jiných projektech. Jde tedy o preferenci
(„it is best for“), ne o zákaz sdílet lidi. Kontext vlastní tým jako celek,
ne každý jeho člen na plný úvazek.

Doporučení má dvě části, které se často chybně čtou jako jedna:

- **Jeden Bounded Context = jeden tým (výchozí stav).**
  Když dva týmy sdílejí jeden BC, Conway's Law se projeví okamžitě. Buď vznikne neoficiální
  vnitřní hranice, tedy fakticky dva BC, které nikdo nepřiznal. Nebo *sdílené
  vlastnictví*: BC nikdo nevlastní a ten degraduje na Big Ball of Mud.
  *Prakticky:* sdílený BC je dočasný stav s koncovým datem, ne cílová podoba.
  Kód, který dva týmy skutečně potřebují sdílet, patří do malého
  [Shared Kernelu](/context-mapping#shared-kernel) mezi dvěma oddělenými BC.
  I ten je ale drahý vztah, ne výchozí volba.

- **Jeden tým = jeden nebo více Bounded Contexts (povoleno).**
  Malý tým (5–9 lidí) může vlastnit 1–2 menší BC, výjimečně 3. Limit plyne
  z [kognitivní zátěže](#cognitive-load), kterou rozebírá sekce 05.06. Tým, který
  vlastní 5+ BC, je signál k rozdělení.

:::callout{type="pattern"}
### Vernon Rule: 1 BC = 1 tým {#vernon-rule-heading}

Pro management to jde zkrátit na jednu větu. **Každý Bounded Context má právě
jednoho vlastníka – tým, který se zavázal ho vyvíjet, nasazovat a opravovat
v noci.** Bez takového vlastníka BC neexistuje
architektonicky. Je to jen složka v repu.

Test: položte vedle sebe Team Map a Context Map. BC, pro který neumíte pojmenovat
tým, jenž ho nasazuje a drží u něj pohotovost, je fikce. V Symfony repu má test
strojovou podobu: každý adresář Bounded Contextu má mít pravidlo v `CODEOWNERS`. Co není
v `CODEOWNERS`, nemá vlastníka ani tehdy, když se o tom na retru mluví jinak.
:::

Důsledek je nepříjemný pro mnoho organizací: **Context Map a Team Map jsou ve zdravém
stavu téměř izomorfní**. Při 7 BC a 4 týmech máte buď nesoulad (3 BC nemají
vlastníka), nebo jeden tým vlastní 2+ BC (vědomé rozhodnutí, ne nedopatření).
Detail vztahu mezi Context Map a Team Map je v kapitole o
[Context Mappingu](/context-mapping).

A obráceně: při 4 BC a 7 týmech hranice nevznikly podle DDD, ale z historické
organizační struktury, kterou nikdo neaktualizoval. Na řadu přichází Inverse Conway Maneuver
(sekce 05.05).

### Co dělat, když Context Map a Team Map nesedí {#bc-team-mismatch}

Nesoulad mezi Context Mapou a Team Mapou má čtyři podoby a každá vyžaduje
jinou akci:

| Symptom | Příčina | Akce |
|---|---|---|
| BC bez týmu | BC vznikl architekturou na papíře, nikdy nikomu nepřiřazen | Sloučit s jiným BC nebo přiřadit existujícímu týmu jako 2. BC |
| Tým bez BC | Horizontální tým (frontend / DBA) bez doménové odpovědnosti | Inverse Conway: rozpustit a přerozdělit do stream-aligned týmů |
| BC sdílený 2 týmy | Organické zvětšování bez rozdělení BC nebo týmu | Buď rozdělit BC na 2 menší + Customer/Supplier, nebo sloučit týmy |
| 1 tým vlastní 5+ BC | Akumulace bez měření cognitive load | Split týmu (sekce [05.06](#cognitive-load)) nebo redukce počtu BC |

Žádný z těchto scénářů není akutní krize. Conway's Law dává systému dost setrvačnosti,
aby s nesouladem fungoval měsíce. Dlouhodobě se ale projeví: prodlužuje se lead time,
roste podíl nasazení s incidentem, klesá morálka. Přímé měření tohoto řetězce žádný
z citovaných zdrojů nenabízí, jde o pozorování z praxe.

## 05.03 Team Topologies – 4 typy týmů (Skelton & Pais 2019) {#team-topologies-typy}

V roce 2019 vydali Matthew Skelton a Manuel Pais knihu
*Team Topologies: Organizing Business and Technology Teams for Fast Flow*
[[3]](https://teamtopologies.com/book). Poprvé systematicky popsali, **jaké typy
týmů má organizace mít** a **jak mezi sebou mají komunikovat**. Team Topologies dodává *slovník* pro organizační návrh,
nepředepisuje proces jako SAFe nebo LeSS.
DDD tím získává organizační slovník, který u Vernona chybí.

Druhé vydání vyšlo 23. září 2025 [[4]](https://itrevolution.com/product/team-topologies-second-edition/).
Podtitul se změnil z „business and technology teams“ na „business and technology“ a rámec
tím míří i mimo IT. Kognitivní zátěž v něm autoři povýšili na hlavní designový princip
a spolu s Dr. Laurou Weis k ní publikovali model s více než dvaceti drivery ve čtyřech
skupinách. Následující text vychází z prvního vydání, na kterém stojí zavedená terminologie.

Skelton v roce 2024 doplnil, co v knize podle něj zapadlo: nejdůležitější nejsou
statické čtyři typy týmů, ale interakce mezi nimi a vývoj topologie v čase. Čtyři typy
si čtenáři pamatují, protože se dobře kreslí do slidu. Rozhodují ale interakční módy
ze sekce 05.04 a ochota topologii po půl roce přepsat.

Skelton a Pais rozlišují 4 typy týmů. Cokoliv jiného (klasický „enterprise architecture
team“, „QA tým“, „Center of Excellence“) je buď maskovaná varianta jednoho z nich,
nebo organizační anti-vzor.

### Stream-aligned team {#stream-aligned}

**Vlastník end-to-end value streamu, typicky jednoho Bounded Contextu.**
Stream-aligned tým má všechny role pro samostatné doručení hodnoty koncovému uživateli:
vývojáře (frontend i backend), QA, designéra, někdy product ownera.
Tým rozhoduje, doručuje a provozuje v produkci. Žádné „předání“ do jiného týmu.

- **Velikost:** 5–9 lidí. Hranici autoři neodvozují od objednávky pizzy, ale od Dunbarových hranic důvěry (5, 15, 50, 150).
- **Vlastnictví:** 1 BC (typicky), maximálně 2–3 související malé BC.
- **Cíl:** minimalizovat kognitivní zátěž a maximalizovat *flow* hodnoty.
- **Měření:** DORA metriky, aktuální sadu rozebírá sekce [05.09](#dora-metriky).

*Ve zdravé technologické organizaci je většina týmů stream-aligned.* Skelton a Pais
k tomu dávají tip: poměr stream-aligned týmů k ostatním má být zhruba 6:1 až 9:1. Číslo
neměřili, opírá se o to, co o sobě úspěšné organizace samy hlásí. Jako řádová kontrola
ale stačí. Organizace s deseti týmy, z nichž jsou stream-aligned tři, typicky trpí
některým z anti-vzorů ze sekce 05.08.

### Platform team {#platform-team}

**Poskytuje self-service platformu pro stream-aligned týmy.**
Platform team vlastní interní vývojářskou platformu (IDP – Internal Developer Platform).
Patří sem CI/CD šablony, observability stack (Prometheus, Grafana, Sentry), Kubernetes,
secrets management, šablony pro nové BC, vývojářský portál.

Platform team stojí na slově **self-service**. Stream-aligned tým
si na platformu nezadává ticket („potřebuju nový Postgres“) a nečeká týden. Databázi si naklikne
sám přes portál nebo nasadí přes IaC modul, který Platform team udržuje. Platform
team, který funguje jako ticketová fronta, se stane úzkým hrdlem
infrastruktury (anti-vzor v sekci 05.08).

Platformu autoři nedefinují jako jeden tým, ale jako seskupení dalších týmů, které
stream-aligned týmům dodává přesvědčivý interní produkt. Velká platforma tak může mít
uvnitř vlastní stream-aligned týmy pro jednotlivé služby. Poměr typu „jeden platform
tým na sto vývojářů“ kniha neuvádí.

Rozsah platformy určuje koncept **Thinnest Viable Platform**: platforma má být jen tak
tlustá, jak je nutné. Nejmenší funkční TVP je wiki stránka se seznamem schválených služeb
a návodem k jejich použití. Teprve když přestane stačit, přidává se automatizace
a s ní lidé.

- **Charakter:** platforma je produkt. Má roadmapu, interní zákazníky a měřenou adopci. Bez toho je to sdílená infrastruktura s novým jménem.
- **Měření:** NPS od stream-aligned týmů, adoption rate, time-to-first-deploy pro nový BC.
- **Anti-charakter:** Platform team *nesedí na změnách*. Má roli enabler, ne gatekeeper.

### Enabling team {#enabling-team}

**Tým specialistů, který pomáhá stream-aligned týmu osvojit si novou
techniku nebo technologii.** Klasické úkoly: „naučte je TDD“, „zaveďte CQRS“,
„pomozte s migrací na K8s“, „rozjeďte s nimi event sourcing“.

Časově omezená je *spolupráce*, ne tým. Kniha mluví o závislosti, která má po několika
týdnech či měsících skončit a nesmí zůstat trvalá. Enabling tým jako útvar pokračuje
dál a přesune se k dalšímu stream-aligned týmu, který právě něco přebírá.

Enabling team se často zaměňuje s Center of Excellence, ačkoli se podstatně liší:

| Aspekt | Enabling team | Center of Excellence (anti-vzor) |
|---|---|---|
| Doba spolupráce s jedním týmem | Time-boxed, konec dohodnutý předem | Trvalá, konec se neplánuje |
| Cíl | Předat dovednost a odejít | Držet kontrolní bod, schvalovat |
| Vztah k stream-aligned týmu | Mentor, peer | Recenzent, autorita |
| Měření úspěchu | Stream-aligned tým to umí sám | Kolik ticketů jsme schválili |

### Complicated-subsystem team {#complicated-subsystem-team}

**Vlastní algoritmicky náročnou doménu, kterou by stream-aligned tým nezvládl
bez vyhrazených specialistů.** Typické příklady: risk engine v reálném čase v bance,
ML scoring model, video transcoder, fyzikální simulátor, kompilátor, kryptografická
knihovna. Tým soustřeďuje specializované znalosti (PhD v matematice,
fyzice nebo CS, hluboké know-how v doméně), které nejde rozprostřít přes 6 stream-aligned
týmů.

- **Vznik:** jen tehdy, když stream-aligned tým objektivně narazí na strop.
- **Komunikace:** obvykle X-as-a-Service vůči stream-aligned týmům.
- **Past:** ze stream-aligned týmu se stane „complicated subsystem“ jen proto, že má seniornější obsazení. To není důvod. Rozhoduje *objektivní specializace*.

### Mapování DDD subdomén na typy týmů {#subdomain-mapping}

Klasifikace subdomén (Core / Supporting / Generic) se na typy týmů přirozeně mapuje.
Co jednotlivé kategorie znamenají a jak je rozpoznat, rozebírá kapitola o
[subdoménách](/subdomeny#tri-kategorie); zde zůstává jen týmový pohled:

| Subdoména | Typ týmu | Týmový důsledek |
|---|---|---|
| **Core** | Stream-aligned (1 tým per BC); Complicated-subsystem, jen pokud je doména algoritmicky náročná | Plná kontrola nad designem, deploymentem i provozem; nejsilnější obsazení. |
| **Supporting** | Stream-aligned | Často sdílí tým s dalším supporting BC. Standardní vzory, žádný over-engineering. |
| **Generic** | Žádný vlastní tým | Platform team integruje SaaS nebo hotové řešení. |

:::callout{type="pattern"}
### Core domain dostane nejlepší tým {#core-stream-heading}

Praktický důsledek mapování: **nejlepší stream-aligned tým musí vlastnit Core BC**.
Nejčastější chyba v enterprise: senior vývojáři dělají „platformu“ nebo „architekturu“
a Core BC drží junior tým. To je inverze priorit. Platform tým má autonomii umožnit,
ne ji soustředit u sebe. Core BC je jediné místo, kde firma vyhrává nad konkurencí.
:::

## 05.04 Tři interakční módy mezi týmy {#interakcni-mody}

Skelton a Pais vedle typů týmů definují i 3 (a jen 3) povolené módy interakce
mezi nimi. Cokoliv jiného („tak ti tam někdo pomůže“, „domluvte se nějak“, „pošli
ticket a uvidíme“) je neformální vztah, který Conway's Law okamžitě otiskne do kódu
jako ad hoc rozhraní.

### Collaboration {#collaboration}

**Dva týmy společně, intenzivně řeší problém.** Sdílejí backlog, plánují spolu,
dělají code review napříč. Mód je *vysoce produktivní, ale drahý*: zdvojuje porady,
rozmazává odpovědnost, zvyšuje kognitivní zátěž obou týmů. Proto je výslovně
**časově omezený**.

- **Kdy:** při objevu nového problému (discovery), při zásadním refaktoringu, při bootstrapu nového BC.
- **Kdy ukončit:** jakmile je interface jasný, přejděte na X-as-a-Service.
- **Mapování na DDD:** Partnership / Shared Kernel z Context Mapy.
- **Past:** trvalá Collaboration → oba týmy jsou fakticky *jeden tým* a sloučení to jen přizná.

### X-as-a-Service {#x-as-a-service}

**Jeden tým konzumuje druhý jako černou skříňku přes stabilní API/kontrakt.**
Konzument nezná interní strukturu ani sprint plán poskytovatele. Má jen SLA,
dokumentaci a release notes. Ve zralé organizaci je to výchozí stav většiny vztahů
mezi týmy.

- **Mapování na DDD:** Customer/Supplier nebo Open Host Service z Context Mapy.
- **Měření:** SLA, error rate, dostupnost API, breaking-change rate.
- **Cíl:** minimální komunikace nutná k používání služby. Žádný stand-up napříč týmy.
- **Past:** X-as-a-Service vyžaduje *vyspělé API a verzování*. Poskytovatel, který mění API každý sprint, provozuje faktickou Collaboration s falešnou nálepkou.

### Facilitating {#facilitating}

**Enabling team pomáhá stream-aligned týmu osvojit si nové know-how.**
Mód trvá týdny až měsíce, s koncem dohodnutým na začátku. Probíhá interaktivně:
pair programming, code review, workshopy.
Cíl: stream-aligned tým *to bude umět sám*. Pak Enabling team
odejde k jinému stream-aligned týmu.

Facilitating nemá přímý ekvivalent v Context Mapě, která řeší vztahy mezi BC, ne dovednosti uvnitř BC. Cílem je autonomie stream-aligned týmu po předání. Pozor na časový limit: Facilitating, který trvá rok a déle, se z definice mění v Center of Excellence.

### Mapování na Context Map má hranice {#mody-vs-context-map}

Překryv mezi interakčními módy a vzory z Context Mapy je užitečná zkratka, ne rovnítko.
Alberto Brandolini [[5]](https://blog.avanscoperta.it/2021/04/22/about-team-topologies-and-context-mapping/)
rozdíl formuluje takto: Team Topologies popisují žádoucí cílový stav, zatímco Context
Mapping nabízí jemnější vzory pro posouzení stavu současného. Context Map proto umí
pojmenovat i patologie jako Big Ball of Mud nebo Conformist, pro které v Team Topologies
žádný mód neexistuje.

Mód také není trvalý štítek. Collaboration při bootstrapu nového BC má přejít
v X-as-a-Service, jakmile je rozhraní stabilní. Pohyb opačným směrem, z X-as-a-Service zpět do Collaboration, signalizuje, že hranice
mezi kontexty nesedí.

:::callout{type="warn"}
### Žádné „volné vztahy“ {#modes-mandatory-heading}

Hlavní pravidlo Team Topologies: **každá interakce mezi dvěma týmy MUSÍ být
explicitně jedním ze 3 módů**. Žádné „neformální“ vztahy. Důvod plyne přímo
z Conway's Law: neformální vztah nemá kontrakt. V kódu pak vznikne ad hoc kontrakt
(sdílená třída, sdílené DB schéma, „toho se prostě nedotýkej“), který později nikdo
nedokáže refaktorovat.

Při onboardingu nového týmu vztah zapište explicitně: „*S týmem A jsme
v X-as-a-Service, s týmem B ve čtyřměsíční Collaboration na bootstrap nového BC,
s Enabling teamem máme kontrakt na tříměsíční facilitaci CQRS.*“ Co nejde
napsat, je neformální vztah – a ten je v ohrožení.
:::

## 05.05 Inverse Conway Maneuver {#inverse-conway}

Conway's Law říká „struktura kopíruje organizaci“. **Inverse Conway Maneuver**
obrací směr: *kdo chce jinou strukturu, MUSÍ nejdřív změnit organizaci.*
Conway's Law se tím z překážky stává nástrojem.

Termín Inverse Conway Maneuver zavedli konzultanti ThoughtWorks Jonny LeRoy
a Matt Simons v článku pro Cutter IT Journal (prosinec 2010);
Skelton a Pais (2019, kap. 2) ho rozpracovali s odkazem na výzkum Forsgren, Humble a Kim
v *Accelerate* (2018). Postup lze shrnout do 4 kroků:

1. **Definovat cílovou architekturu.** Typicky Context Map z DDD,
   tedy seznam Bounded Contexts a vztahů mezi nimi. Bez tohoto kroku není co kopírovat.
   Detail v kapitole o [Context Mappingu](/context-mapping).

2. **Spočítat počet stream-aligned týmů.** Hrubé pravidlo: 1 BC = 1 tým.
   Šest BC znamená šest stream-aligned týmů. Při současných třech týmech
   (frontend, backend, DBA) to znamená reorganizaci na 6 vertikálních týmů, z existujících
   lidí nebo náborem.

3. **Re-org: rozpustit horizontální týmy, poskládat vertikální stream-aligned týmy.**
   Na tomto kroku implementace typicky selže. Frontendoví lidé nechtějí být „v Catalog týmu“,
   chtějí sedět s ostatními frontendisty. Manažeři nechtějí vyměnit tým 12 lidí za tým
   7 lidí. Tato fáze potřebuje silnou podporu CTO/VP Engineering.

4. **Vyřešit platformu.** Vznikne typicky z bývalých „infrastructure“ lidí a 1–2 seniorů
   z každého stream-aligned týmu. Rozsah se odvozuje od potřeby (Thinnest Viable Platform),
   ne od počtu vývojářů. Cíl: do 6 měsíců self-service, ne dokonalý IDP.

Skelton a Pais výslovně varují: **Inverse Conway Maneuver bez podpory managementu
neuspěje**. Re-org je politický akt. Když CTO řekne „udělejte to, ale beze změny
org chartu“, čeká vás 6 měsíců práce, která nikam nevede. Conway's Law pak při každém
refaktoru vrátí architekturu k původní komunikační struktuře.

:::callout{type="note"}
### Reálný příběh: Amazon, 2002 {#inverse-real-world-heading}

Klasická případová studie Inverse Conway Maneuver: Jeff Bezos kolem roku 2002 vydal
interní nařízení, že *všechny týmy budou komunikovat výhradně přes API*. Žádné sdílené
databáze, žádné funkční volání napříč týmy, žádné neformální komunikační kanály.
Primární dokument nikdy nebyl zveřejněn. Mandát je znám z podání bývalého inženýra
Amazonu Steva Yeggeho („Google Platforms Rant“, 2011), včetně dovětku, že kdo se
nepodřídí, bude propuštěn.

Bezos nepředepsal architekturu. Předepsal **komunikační režim
týmů**. Architektura služeb za API z něj vyplynula, protože jinak se mandát
splnit nedal. Jde o Inverse Conway Maneuver v měřítku celé firmy.
:::

Praktická past: reorganizace bolí. Lidé ztrácejí senioritu, manažeři pravomoci,
domácí kultury týmů (frontend kávovar, backend stand-up) se rozbijí. Team lead, který
zvažuje Inverse Conway Maneuver bez výslovného zadání od CTO, si ho nejdřív vyžádá.
Komunikaci s managementem rozebírá sekce 05.09.

### Kdy Inverse Conway nefunguje {#inverse-conway-limity}

Manévr funguje jako změna směru, ne jako jednorázový zásah. Martin Fowler
[[6]](https://martinfowler.com/bliki/ConwaysLaw.html) k němu dodává výhradu: reorganizace
neopraví zabetonovanou architekturu, přesune jen lidi kolem ní. Nastane období, kdy nové
týmy vlastní kód, který nepsaly. Fowler proto doporučuje malé inkrementální kroky s vyhodnocením po každém z nich.
Podle něj musí vývoj architektury a reorganizace lidí jít ruku v ruce
po celou dobu života firmy.

U existujících systémů jde kritika dál. Komunikační struktura se změní dnem reorganizace,
kódová báze ne. Organizace tedy projde obdobím, kdy je měřitelně horší než před zásahem.
Týmy se prodírají cizím kódem, lead time se prodlouží a podíl nasazení
s incidentem stoupne.
Kdo s tímto propadem nepočítá, vyloží po třech měsících čísla jako důkaz neúspěchu
a reorganizaci vrátí zpět.

Ke checklistu níže tedy patří ještě jedna otázka, kterou nikdo nerad pokládá nahlas: jak
dlouho propad potrvá a kdo ho bude vysvětlovat vedení.

### Praktický checklist před spuštěním Inverse Conway Maneuver {#inverse-checklist}

Před zahájením reorganizace slouží jako kontrola následující seznam. Odpověď „ne“
na *kterýkoli* bod znamená, že Inverse Conway je předčasný a zpravidla selže:

1. **Existuje kanonická Context Map?** Bez ní není cílová architektura
   definovaná. Krok 1 selhal a kroky 2–4 nemají kam směřovat. Bez Context Mapy
   začněte tam (kapitola o [Context Mappingu](/context-mapping)).

2. **Má reorganizace výslovnou podporu CTO / VP Engineering?** Reorganizace je politický
   akt. Bez podpory shora odpor nepřekonáte. Lidé budou hledat výjimky a starou
   strukturu obnoví neoficiálně.

3. **Máte 6 měsíců času?** Reorganizace kratší než 6 měsíců typicky nefunguje. Lidé
   potřebují čas se přesunout, naučit se nové domény, vybudovat nové vztahy.

4. **Existuje plán pro Platform team?** Bez self-service platformy se
   stream-aligned týmy zaseknou na infrastruktuře. Platform team musí mít alespoň
   minimum-viable IDP připravený před reorganizací (1-click new-BC bootstrap, CI šablona,
   výchozí observability).

5. **Změřili jste DORA metriky před reorganizací?** Bez baseline neumíte
   obhájit úspěch ani odhalit regresi. Stačí čtyři čísla: lead time od commitu
   do produkce, deployment frequency, change failure rate (podíl nasazení, která
   způsobí incident) a čas zotavení po nasazení, které něco rozbilo.

6. **Má organizace generativní kulturu podle Westruma?** V patologické či byrokratické
   reorganizace formálně proběhne, ale provozní vztahy se vrátí (sekce [05.09](#westrum)).

7. **Je obsazená pozice „topology owner“?** Někdo musí reorganizaci vést každý den,
   typicky staff engineer + manažer. Bez vlastníka se rozplyne
   v běžných sprintových prioritách.

Se všemi sedmi body „ano“ máte vyšší šanci než průměr.

## 05.06 Cognitive Load – limit pro velikost týmu/BC {#cognitive-load}

Pojem **kognitivní zátěž** (cognitive load) převzali Skelton a Pais
z teorie učení Johna Swellera. Ten ji zavedl v roce 1988 studií o řešení problémů;
trojici typů, kterou dnes teorie používá, doplnili Sweller, van Merriënboer a Paas
až v roce 1998. Na softwarové týmy se vážou všechny tři:

- **Intrinsic load** (vnitřní) – komplexita samotné domény. „Bankovní risk
  engine“ má vyšší intrinsic load než „katalog produktů“. Odstranit ji nejde; lze ji snížit
  zaškolením a volbou technologií, nebo rozdělit mezi víc týmů.

- **Extraneous load** (zbytečná) – zátěž z prostředí, ne z domény: nestabilní
  CI, špatná dokumentace platformy, 5 různých deploy procesů, chaos ve Slack kanálech.
  Odstranit ji je úkol Platform teamu.

- **Germane load** (rozvojová) – energie, kterou tým vkládá do učení a zlepšování.
  O tu jde. Když je tým přetížený intrinsic a extraneous zátěží, germane mizí
  a tým přestane investovat do zlepšení.

Cíl podle Skeltona a Paise: **intrinsic zátěž minimalizovat, extraneous odstranit
a uvolněný prostor nechat germane.** Tým, který tráví 80 % energie zápasem s CI a deploy
procesem, nemá kapacitu zlepšovat doménový model.

### Jak zátěž měří sami autoři {#cognitive-load-mereni}

Skelton a Pais přiznávají, že přesná míra kognitivní zátěže neexistuje. Nabízejí místo
ní dvě věci. První je jediná otázka položená týmu: *„Do you feel like you are effective
and able to respond in a timely fashion to the work you are asked to do?“*

Druhá je relativní míra přes komplexitu domén. Domény se roztřídí na simple, complicated
a complex a pak platí několik heuristik. Každá doména patří jedinému týmu. Je-li doména
na tým velká, dělí se doména, ne odpovědnost za ni. Jeden tým unese dvě až tři simple
domény. Tým s complex doménou nedostane nic dalšího. Dvě complicated domény na jeden tým
jsou špatný nápad.

Pro DDD je převod přímočarý: doména se v této úvaze chová jako Bounded Context.
Klasifikaci komplexity nabízí kapitola o [subdoménách](/subdomeny#tri-kategorie),
kandidátní hranice pak workshop popsaný v kapitole o
[Event Stormingu](/event-storming#jak-poznat-hranici).

### Pravidlo cognitive load pro počet BC na tým {#cognitive-load-rule}

Následující tabulka je autorské zobecnění pro potřeby této kapitoly, v knize takto
uvedena není. Počítá kontexty místo domén a přidává druhý rozměr, velikost týmu:

| Velikost týmu | Doporučený počet BC | Komentář |
|---|---|---|
| 5 lidí | 1 BC (max 2 malé) | Hranice, kdy má každý přehled o všem; každý zná každou část kódu. |
| 6–9 lidí | 1–2 BC (výjimečně 3) | Běžná velikost stream-aligned týmu; každý ještě zná každého. |
| 10+ lidí | Tým je už příliš velký – rozdělit | Dunbar number (familiarity ≈ 15). Komunikační režie roste kvadraticky s počtem lidí. |
| Tým s 5+ BC | – | Signál pro rozdělení. BC nemají soudržného vlastníka. |

### Jak změřit cognitive load (jednoduchá rubrika) {#cognitive-load-rubric}

Rubrika níže je nástroj této knihy, ne nástroj z Team Topologies. Autoři publikují
šablonu *Team Cognitive Load Assessment* pod CC BY-SA, její veřejná verze ale znění
otázek neobsahuje. Rubrika stojí na téže myšlence: sbírá vnímání členů týmu, ne
technická čísla.

Použití je nenáročné: jednou za kvartál 30minutový workshop. Každý člen
ohodnotí na škále 1–5 pět oblastí – doménovou a technickou komplexitu
(intrinsic), stabilitu platformy a kvalitu dokumentace (extraneous, inverzně)
a prostor na učení (germane).

Vysoké body 1+2 znamenají, že tým má intrinsic zátěž pod kontrolou. Vysoké body 3+4, že Platform team funguje
a extraneous load je nízký. Vysoký bod 5 ukazuje kapacitu na germane.

*Je-li průměr bodu 5 pod 3, tým je v krizovém režimu: žádné nové BC, žádné nové
technologie. Nejdřív stabilizovat extraneous load.*

Rubrika níže má formát, který stačí vlepit do `docs/cognitive-load.md`
v repu týmu. Vejde se na stránku A4, vyplní se za 30 minut jednou za kvartál a slouží
jako vstup pro retro:

:::code{language="markdown" filename="docs/cognitive-load.md"}
# Cognitive Load Rubric – Q?/YYYY

Tým: <název týmu>
Bounded Contexts ve vlastnictví: <seznam BC>
Velikost týmu: <N> lidí
Datum měření: YYYY-MM-DD

## 1. Doménová komplexita (intrinsic)
Otázka: „Rozumím kompletně doméně, kterou náš tým vlastní?“
Skóre 1–5: __
Komentář: ____________________________________________

## 2. Technická komplexita (intrinsic)
Otázka: „Rozumím všem technologiím, které používáme (jazyk, framework, DB, broker)?“
Skóre 1–5: __
Komentář: ____________________________________________

## 3. Stabilita platformy (extraneous, inverze)
Otázka: „Můžu se spolehnout na CI/CD, observability, deploy bez ad hoc oprav?“
Skóre 1–5: __ (5 = stabilní, 1 = každý deploy je dobrodružství)
Komentář: ____________________________________________

## 4. Kvalita dokumentace (extraneous, inverze)
Otázka: „Najdu v interní dokumentaci potřebné info do 5 minut?“
Skóre 1–5: __
Komentář: ____________________________________________

## 5. Prostor na učení (germane)
Otázka: „Mám každý sprint alespoň 2 hodiny na zlepšení / learning / refaktoring?“
Skóre 1–5: __
Komentář: ____________________________________________

## Vyhodnocení (vyplní team-lead po sběru od všech členů týmu)

Průměr 1+2 (intrinsic kapacita): __
Průměr 3+4 (extraneous tlak):    __
Bod 5 (germane prostor):         __

## Akce na další kvartál

- [ ] Pokud bod 5 < 3 → zastavit přírůstek BC.
- [ ] Pokud body 3+4 < 3 → eskalovat na Platform team (extraneous load).
- [ ] Pokud body 1+2 < 3 → zvážit rozdělení BC nebo přidání člena týmu.
- [ ] Pokud > 4 BC ve vlastnictví → naplánovat rozdělení do 2 kvartálů.
:::

Rubrika záměrně měří *vnímání* členů týmu. Kognitivní zátěž je psychologická kategorie a tvrdá metrika z Grafany ji nezachytí.
Skelton a Pais (2019, kap. 3 „Team-First Thinking“) jdou dál: snahu určit kognitivní zátěž
softwaru z jednoduchých měr, jako je počet řádků kódu, modulů, tříd nebo metod, označují
doslova za *misguided*. Jazyky se podle nich liší v upovídanosti, takže v polyglotním systému
řádky kódu neporovnávají srovnatelné. Rozhoduje limit kognitivní kapacity týmu efektivně
měnit systém, ne velikost toho systému.

:::callout{type="warn"}
### Varování: sklon k rozšiřování BC {#cognitive-warning-heading}

Velmi častá past: tým s úspěšným Core BC dostane od managementu „ještě jeden malý BC,
zvládnete to“. Pak další a další. Po roce má tým 4 BC, je vyhořelý a žádný
BC není dotažený. **Zdravý mechanismus: kdykoli se přidává BC, musí někdo
výslovně odpovědět na otázku, co se odebírá.** Pokud nic, tým se buď rozšíří,
nebo rozdělí. Žádná akumulace.
:::

## 05.07 Praktické scénáře (5 / 20 / 200+ lidí) {#scenare}

Team Topologies není doktrína „zaveďte hned všechny 4 typy týmů a 3 módy“. Je to
*jazyk* pro popis aktuálního stavu a cíle. Konkrétní podoba závisí na velikosti
organizace.

### Scénář A – Startup, 5 lidí, 1 produkt {#scenar-startup}

**Doporučení:** 1 stream-aligned tým, 2–3 malé BC v jednom monolitu (modulární
monolit). Žádný Platform team, žádný Enabling team.

- **Architektura:** jeden Symfony monolit; BC jsou složky/moduly s explicitními rozhraními (kapitola o [mikroservisech a DDD](/ddd-a-microservices#modular-monolith)).
- **Generic subdomény:** nakoupit jako SaaS, žádná vlastní implementace. Argumenty a sourcing strategii build/buy rozebírá kapitola o [subdoménách](/subdomeny#sourcing).
- **Hosting:** Upsun (dříve Platform.sh), Heroku, Railway, Fly.io. Managed services nahrazují Platform team.
- **Čeho se vyvarovat:** Kubernetes, vlastní observability stack, mikroservisy. Na to je brzy.

*Chyba startupů:* kopírovat enterprise architekturu, „aby to bylo připravené na budoucnost“.
Pětičlenný tým nemá kognitivní kapacitu na 6 mikroservisů. Správnou volbou je modulární
monolit.

### Scénář B – Scale-up, 20 lidí, 1 produkt s rostoucí komplexitou {#scenar-scaleup}

**Doporučení:** 2–3 stream-aligned týmy podle BC + 1 mini-Platform team
(3–5 lidí) na CI/CD a observability. Žádný permanentní Enabling team.

- **Stream-aligned týmy:** rozdělené podle hlavních value streamů. Např. Catalog tým (5 lidí), Ordering tým (6 lidí), Identity+Billing tým (4 lidi, sdílí 2 supporting BC).
- **Platform team:** 4 lidi, vlastní CI pipeline šablonu, K8s cluster, Grafana/Sentry, šablonu pro nový BC. Self-service.
- **Enabling team:** ne na trvalo. Zavedení CQRS pokryje externí konzultant na 3 měsíce.
- **Interakční módy:** Stream-aligned týmy mezi sebou X-as-a-Service. Platform team se všemi v X-as-a-Service. Příležitostná Collaboration při bootstrapu nového BC.

Tato fáze je nejrizikovější. Organizace už není malá, ale na plný rozsah Team Topologies
ještě nemá kapacitu. Klasická chyba: vznikne Center of Excellence („architektonický
výbor“) a stane se úzkým hrdlem.

### Scénář C – Enterprise, 200+ lidí, 10+ BC {#scenar-enterprise}

**Doporučení:** plná Team Topologies struktura.

- **15–25 stream-aligned týmů** po 6–9 lidech, každý vlastní 1 BC (případně 2 související supporting BC).
- **1–2 Platform teamy** – typicky 1 hlavní (IDP, K8s, observability) + někdy specializovaný (data platform, ML platform).
- **1–3 Enabling teamy** – rotující, time-boxed, podle aktuálních potřeb (např. „security enabling team“ na 6 měsíců, „event sourcing enabling team“ na 3 měsíce).
- **1–2 Complicated-subsystem teamy** – jen pro objektivně specializované domény (např. risk engine v bance, video transcoder v médiích, ML scoring v ad-techu).
- **Topology design:** v této velikosti se osvědčuje malý *topology team* (1–2 lidi, není to Center of Excellence). Sleduje kognitivní zátěž týmů a navrhuje reorganizace. Často je to staff engineer + manažer.

I ve dvousetčlenné firmě mají stream-aligned týmy **výrazně převažovat**, orientačně
tři čtvrtiny lidí. Připadá-li ze 200 lidí 100 na Platform/Enabling/CoE týmy
a architekty, máte problém. Doménovou hodnotu nesou stream-aligned týmy, ostatní ji jen
násobí. Násobitelů nemá být víc než těch, kdo hodnotu vytvářejí.

:::callout{type="pattern"}
### Orientační proporce (75/15/10) {#scenare-summary-heading}

Orientační poměr pro zralou organizaci, zároveň autorské zobecnění. Rozdělení lidí
v procentech Skelton a Pais nikde neuvádějí. Uvádějí poměr stream-aligned týmů k ostatním
6:1 až 9:1, a to jako tip opřený o vlastní hlášení úspěšných organizací, ne o měření:

- **≈ 75 %** lidí ve stream-aligned týmech (delivery hodnoty)
- **≈ 15 %** v Platform teamu(ech)
- **≈ 10 %** v Enabling + Complicated-subsystem (rotující, podle potřeby)

Pokud vám čísla ukazují 50/30/20 nebo dokonce 30/40/30, máte „enterprise architecture
inflation“: moc lidí v multiplikátorech, málo lidí, co reálně doručují.
:::

## 05.08 Anti-vzory {#antivzory}

Následujících pět anti-vzorů patří v praxi k nejčastějším a nejdražším.
Detailní katalog DDD anti-vzorů je v samostatné kapitole o
[anti-vzorech](/anti-vzory).

### 1. „Sdílíme jeden monorepo bez hranic modulů“ {#antivzor-shared-repo}

Více týmů commituje do jednoho repozitáře bez jasných hranic mezi moduly. Každá
netriviální změna jednoho týmu pak vyžaduje code review od ostatních („jen abychom
se ujistili, že to nic nerozbije“). Druhý tým má fakticky veto na změny prvního.

**Řešení:** hranice modulů vynucené v CI, ne dohodou na retru. V PHP na to slouží Deptrac
nebo PHPArkitect: pull request, který sáhne z modulu jednoho týmu do modulu druhého,
spadne. Konkrétní pravidla ukazuje kapitola o
[mikroservisech a DDD](/ddd-a-microservices#phparkitect-heading). K tomu explicitní
vlastnictví v `CODEOWNERS`. Alternativou jsou separátní repa pro každý BC. Nikdy ne princip
„všichni do jednoho repa, nějak se domluvíme“.

### 2. „Frontend / Backend / Mobile týmy“ {#antivzor-frontend-backend}

Klasický anti-vzor přímo z Conway's Law: týmy rozdělené po vrstvách. Každá nová funkce
vyžaduje koordinaci 3 týmů, 3 sprintů, 3 retrospektiv. Úprava na zhruba 3 dny práce
má lead time přes 6 týdnů.

**Řešení:** Inverse Conway Maneuver. Rozpustit horizontální týmy a poskládat
vertikální stream-aligned týmy. Každý tým má *všechny* potřebné role
(frontend dev + backend dev + mobile dev + QA + designer). Je-li mobilní aplikace
zásadní částí produktu, ne vedlejším kanálem, patří mobilní vývojáři do stream-aligned týmů,
ne do separátního „mobile týmu“.

Výjimka: při jednom či dvou mobilních vývojářích na celou organizaci se hodí dočasná „mobile guild“ –
ne jako tým s vlastním backlogem, ale jako komunita pro sdílení znalostí.

### 3. „Center of Excellence“ místo Enabling teamu {#antivzor-coe}

Permanentní útvar „architektů“ / „expertů“ / „vedoucího týmu“, který drží schvalovací
pravomoc nad ostatními. Klasická corporate inkarnace: ARB (Architecture Review Board),
který musí každou novou službu schválit.

**Co je špatně:** CoE typicky drží *kontrolní bod*, ne expertní podporu.
Schvalování ze své podstaty zpomaluje, vytváří frontu a zbavuje stream-aligned týmy
odpovědnosti („to nám neschválili, nemůžeme za to“).

**Řešení:** CoE → Enabling team. Časově omezená spolupráce, mentoring místo schvalování,
rozpuštění po předání. Je-li „schvalování“ nutné, dělá ho stream-aligned tým sám
podle dokumentovaných standardů, ne externí výbor.

### 4. „Platform team jako gatekeeper / ticketová fronta“ {#antivzor-platform-gatekeeper}

Platform team funguje jako infrastrukturní ticket support. Stream-aligned tým
potřebuje nový Postgres, vytvoří JIRA ticket a čeká 5 dnů. Potřebuje upravit CI pipeline,
vytvoří ticket a čeká týden. Z Platform teamu se stalo úzké hrdlo celé
organizace.

**Řešení:** Platform team musí dodávat *self-service* rozhraní
(CLI, portál, IaC moduly). Když stream-aligned tým musí zadávat tickety, jde o chybu návrhu
platformy, ne zadávajícího týmu.

Hlavní metrika: **time-to-first-deploy pro nový BC**. Ve zdravé organizaci
pod 1 den. V nezdravé „ozkoušíme to za měsíc, jakmile bude mít Platform team kapacitu“.

### 5. „Sdílený Bounded Context mezi 2 týmy“ {#antivzor-shared-bc}

Dva stream-aligned týmy commitují do stejného Bounded Contextu, protože „to dává smysl“.
Conway's Law zareaguje okamžitě. Vznikne neformální vnitřní hranice, čára „naše/vaše“ v kódu,
ale bez formální Context Mapy. Čára ztvrdne a po půl roce je z ní Big Ball of Mud se dvěma vlastníky.

**Řešení:** rozdělit BC na 2 menší BC se Shared Kernel (drahý, viz Context
Mapping) nebo Customer/Supplier vztahem. Případně sloučit 2 týmy do 1 většího, pokud
doména nejde rozdělit.

:::callout{type="anti"}
### Test: máte tyto anti-vzory? {#antivzory-test-heading}

1. Umíte pro každý BC pojmenovat *jediný* vlastnící tým a najít ho v `CODEOWNERS`?
2. Mají všechny stream-aligned týmy *všechny* role potřebné k samostatné delivery?
3. Existuje útvar (CoE, ARB, „architektonický výbor“), který schvaluje technická rozhodnutí stream-aligned týmů?
4. Když stream-aligned tým chce nový Postgres, klikne na něj, nebo ticketuje?
5. Je každá interakce mezi 2 týmy explicitně Collaboration / X-as-a-Service / Facilitating?

Pokud na 2+ otázky odpovídáte „ne“ / „ano (CoE)“ / „zadává ticket“, máte před sebou práci.
:::

## 05.09 Komunikace s managementem – jak prodat reorganizaci {#management}

Inverse Conway Maneuver je hluboká organizační změna. Týmy bude třeba rozdělit, manažery
přeřadit, lidé možná ztratí senioritu nebo „svůj koutek“. Bez pochopení a podpory
managementu (CTO / VP Engineering / People Ops) Inverse Conway selže.

Podstatné je **mluvit jazykem, kterému management rozumí** – ne jazykem DDD.
„Přesnější doménový model“ nebo „jasněji ohraničené Bounded Contexts“ manažeři ocenit neumí.
Metriky ano.

### Argumenty, které fungují (DORA metriky) {#dora-metriky}

Nicole Forsgren, Jez Humble a Gene Kim v knize *Accelerate* (2018)
[[7]](https://itrevolution.com/product/accelerate/)
zveřejnili 4 metriky (DORA). Měří efektivitu doručování softwaru a *silně
korelují* s obchodními výsledky (zisk, růst, spokojenost zákazníků).

Sada se od roku 2018 posunula [[8]](https://dora.dev/insights/dora-metrics-history/).
V roce 2023 se MTTR přejmenovala na *failed deployment recovery time* a změnila se
i definice: metrika měří zotavení po selhání, které způsobila změna v produkci, ne po libovolném výpadku. V roce
2024 přibyla pátá metrika. Aktuální podoba:

- **Change lead time** – čas od commitu do produkce. Stream-aligned týmy: hodiny. Horizontální týmy: dny až týdny.
- **Deployment frequency** – jak často se nasazuje. Stream-aligned: víckrát denně. Horizontální: 1× za sprint.
- **Failed deployment recovery time** – čas zotavení po nasazení, které něco rozbilo. V *Accelerate* ještě pod historickým názvem MTTR.
- **Change failure rate** – podíl nasazení, která způsobí incident.
- **Deployment rework rate** – podíl neplánovaných nasazení vyvolaných incidentem.

První tři metriky popisují průtok, poslední dvě nestabilitu. Na ročníku sady tolik
nezáleží. Podstatné je měřit před reorganizací i po ní stejně definovaná čísla.

**Sada se měří dvakrát: před reorganizací a šest měsíců po ní.**
O kolik se čísla posunou, dopředu neví nikdo. Slíbit CTO konkrétní procento zlepšení
znamená vyrobit si za půl roku problém. Slíbit lze baseline, termín druhého měření
a rozhodnutí podle výsledku.

### Argumenty, které nefungují {#argumenty-nefunguji}

- „Eric Evans by to chtěl.“ Manažer v DDD komunitě není.
- „Je to elegantnější“ – eleganci nikdo neměří.
- „Bounded Contexts jsou kanonické.“ Kanoničnost taky nikdo neměří.
- „Zlepší se to“, jenže bez metriky je „zlepší“ prázdné slovo.
- „Skelton a Pais to říkají.“ Autorita sama o sobě nestačí.

### Westrumova kultura organizace {#westrum}

Sociolog Ron Westrum v roce 2004 publikoval typologii organizačních kultur
[[9]](https://qualitysafety.bmj.com/content/13/suppl_2/ii22),
kterou později Forsgren v *Accelerate* použila jako prediktor výkonu doručování
softwaru. Westrum rozlišuje 3 typy:

| Aspekt | Pathological (power-oriented) | Bureaucratic (rule-oriented) | Generative (performance-oriented) |
|---|---|---|---|
| Spolupráce | Nízká | Mírná | Vysoká |
| Selhání | Hledá se obětní beránek | Vyvozuje se odpovědnost | Hledají se příčiny |
| Nové nápady | Drceny | Považovány za problém | Vítány |
| Sdílení informací | Skryto | Ignorováno | Aktivně podporováno |

**Team Topologies funguje jen v generativní kultuře.** V patologické kultuře
(manažer trestá za chyby, hierarchie je vše) stream-aligned týmy nedostanou autonomii.
Manažer chce mít kontrolní bod, takže se z Platform teamu stane gatekeeper. V byrokratické
kultuře (přesné role, formální procesy) reorganizace projde, ale provozní vztahy
zůstanou. Conway's Law se vrátí přes formální schvalování.

V patologické nebo byrokratické organizaci *Inverse Conway Maneuver není první krok*.
Prvním krokem je změna kultury, případně změna pracoviště.

:::callout{type="pattern"}
### Vzorový pitch pro CTO (3 odstavce) {#management-pitch-heading}

1. „*Naše současné DORA metriky: lead time 18 dní, deployment frequency 1×/sprint,
change failure rate 35 %. Organizace v horním pásmu podle reportu DORA doručují
v řádu hodin, nasazují víckrát denně a incidenty jim způsobuje zlomek nasazení.*“

2. „*Hlavní příčina: rozdělení týmů podle vrstev (frontend/backend/DBA), které způsobuje
předávky a koordinační režii. Conway's Law nám brání rychlejšímu doručování.*“

3. „*Návrh: reorganizace na stream-aligned týmy podle Bounded Contexts během 6 měsíců.
Cíl: lead time pod 3 dny, deploy denně, change failure rate na třetinu současné hodnoty.
Měření a re-evaluace po 6 měsících.*“

Benchmark uvádějte vždy s ročníkem reportu, ze kterého pochází, protože DORA metodiku mění.
V roce 2025 opustila čtyřstupňové dělení Elite / High / Medium / Low a nahradila
ho týmovými profily. Odkaz na „elite performers“ bez uvedení roku tedy dnes
nic neznamená.

Pitch stojí na třech věcech: číslech, srovnání a časovém plánu s re-evaluací. Tím jazykem
mluví CTO. Filozofie DDD a Team Topologies do pitche nepatří, patří do technické
přílohy.
:::

## 05.10 Shrnutí {#summary}

Conway's Law z roku 1968 říká: architektura kopíruje komunikační strukturu organizace. Pro DDD
z toho plyne: **Bounded Context bez vlastnícího týmu je fikce**.
Kontexty v Context Mapě, ke kterým neumíte jmenovat tým, jenž je doručuje, neexistují.
Jsou jen napsané v dokumentaci.

Team Topologies (Skelton & Pais, 2019) je rámec pro vědomý návrh týmů, který doplňuje
DDD tam, kde Vernon a Evans mlčí. Hlavní poznatky:

- **4 typy týmů:** Stream-aligned (vlastní BC end-to-end, výchozí),
  Platform (self-service produkt, ne jeden tým), Enabling (mentoring s dohodnutým koncem
  spolupráce), Complicated-subsystem (objektivně specializovaná doména).
- **3 interakční módy:** Collaboration (drahá, časově omezená),
  X-as-a-Service (výchozí vyspělý vztah), Facilitating (mentoring time-boxed).
- **Vernonova preference:** 1 BC = 1 tým; BC sdílený mezi týmy je dočasný stav,
  ne cílový. Kolik kontextů jeden tým unese, Vernon nečísluje – rozmezí 1–2,
  výjimečně 3, je autorské zobecnění této knihy.
- **Subdomény → typy týmů:** Core → stream-aligned (nejlepší tým) /
  complicated-subsystem; Supporting → stream-aligned (sdílí tým s jiným supporting BC);
  Generic → SaaS, Platform team integruje.
- **Inverse Conway Maneuver:** nejdřív definovat cílovou architekturu,
  pak postavit týmy tak, aby ji přirozeně vyprodukovaly. Bez podpory CTO neuspěje.
- **Cognitive load:** 1–2 BC (výjimečně 3) na 5–9 lidí. 5+ BC na tým = signál pro rozdělení.
  Měří se kvartálně.
- **Proporce:** orientačně 75 % stream-aligned, 15 % platform, 10 % enabling
  + complicated-subsystem. Procenta jsou autorské zobecnění; kniha uvádí poměr
  stream-aligned týmů k ostatním 6:1 až 9:1 jako tip, ne jako naměřenou hodnotu.
- **Komunikace s managementem:** DORA metriky, ne DDD filozofie.
  Westrumova generativní kultura je předpoklad, ne výstup.

**Bounded Context je závazek konkrétního týmu vyvíjet, nasazovat a v noci opravovat
svou část domény.** Bez toho závazku zůstává složkou v repu.

K hlubšímu studiu slouží *Team Topologies* od Skeltona a Paise
[[3]](https://teamtopologies.com/book)
a kapitoly 2 a 3 z Vernonova *Implementing Domain-Driven Design*
[[2]](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577).
DORA metriky a Westrumovu typologii rozebírá *Accelerate* od Nicole Forsgren a kolektivu
[[7]](https://itrevolution.com/product/accelerate/). Conwayův původní esej z roku 1968 má
jen 4 strany a stojí za přečtení
[[1]](http://www.melconway.com/Home/Committees_Paper.html).

:::faq{}
- question: Co když máme jediný tým? Platí Team Topologies i pro nás?
  answer: 'Ano, ale ve zjednodušené podobě. Jediný stream-aligned tým (5–9 lidí) je legitimní organizační struktura, typická pro startup. Nemáte Platform team (využijete managed services jako Heroku/Vercel/Stripe/Auth0), nemáte Enabling team (najmete externího konzultanta na 3 měsíce, pokud potřebujete). Jediné, co řeší Team Topologies pro vás, je interní rozdělení týmu: nepoužívejte „mini-frontend / mini-backend“ rozdělení uvnitř 6 lidí. Detail v <a href="#scenar-startup">scénáři A</a>.'
- question: Mohu mít 1 tým, který vlastní 5 Bounded Contexts?
  answer: 'Krátkodobě možná, dlouhodobě ne. Vernon (2013) připouští, že jeden tým může vlastnit více BC; kolik, neuvádí. Tato kniha doporučuje 1–2, výjimečně 3. Při 5 BC narážíte na cognitive load (sekce <a href="#cognitive-load">05.06</a>): tým ztratí přehled o detailech každého BC, kvalita kódu klesá, lead time roste. Praktická heuristika: pokud máte 5 BC na jeden tým, plánujte rozdělení na 2 týmy do 6 měsíců. Pokud nemáte na 2 týmy lidi, redukujte počet BC (sloučení do supersetu, nebo přesun na SaaS u Generic subdomén).'
- question: Jak Team Topologies souvisí se Spotify Modelem?
  answer: 'Spotify Model (squads, tribes, chapters, guilds) popsali Henrik Kniberg a Anders Ivarsson v roce 2012 s výslovnou poznámkou, že jde o snapshot tehdejšího způsobu práce, ne o předpis. Přesto se z něj předpis stal. Jeremiah Lee, bývalý produktový manažer Spotify, v roce 2020 v eseji <em>Spotify''s Failed #SquadGoals</em> tvrdí, že model byl z velké části aspirativní a firma uspěla spíš navzdory němu. Paralely existují: stream-aligned tým ≈ squad, chapters a guilds odpovídají komunitám sdílení znalostí nad rámec topologie. Tribe (kolekce squadů kolem doménové oblasti) sedí velikostí na Dunbarovy hranice 50 a 150, se kterými Team Topologies pracují. Hlavní rozdíl je v povaze obojího: Spotify Model popisuje jednu firmu v jednom období, Team Topologies dávají rámec s explicitními typy týmů a interakcemi.'
- question: Vyplatí se Team Topologies v padesátičlenné firmě?
  answer: 'Ano, ale ne v plné formě. Padesátičlenná firma leží mezi scénáři B a C, blíž scale-upu: typicky 4–6 stream-aligned týmů + 1 mini-Platform team (3–5 lidí). Žádný permanentní Enabling team, žádný Complicated-subsystem team (pokud nejste banka nebo ML startup). Hlavní hodnota Team Topologies v této velikosti je <em>jazyk</em>. Pokud začnete mluvit o „Platform team“ a „Stream-aligned team“, okamžitě se ukáže, kdo dělá co a co je ticket-fronta vs. self-service. Výchozí bod popisuje <a href="#scenar-scaleup">scénář B</a>.'
- question: Co dělat, když management nesouhlasí s reorganizací?
  answer: 'Tři možnosti, podle závažnosti. (1) <em>Postupný posun:</em> nedělejte reorganizaci najednou, ale ovlivňujte hranice „pod kapotou“: hranice modulů v monorepu, code owners, samostatná nasazení. Část předávání tím zmizí i bez formální reorganizace. (2) <em>Pilot stream-aligned týmu:</em> přesvědčte management o jednom pilotním týmu (5–7 lidí) na 6 měsíců. Změřte DORA metriky před a po. Pokud pilot uspěje, máte case pro plnou reorganizaci. (3) <em>Diagnóza kultury podle Westruma:</em> je-li organizace patologická nebo byrokratická (sekce <a href="#westrum">05.09</a>), Team Topologies neuspěje ani s formální reorganizací. Zvážte změnu místa. Detail komunikace s CTO v <a href="#management">sekci 05.09</a>.'
- question: Jaký je vztah mezi Team Topologies a mikroservisy?
  answer: 'Team Topologies mikroservisy neřeší, ale mikroservisy bez promyšlené týmové topologie obvykle vedou k distribuovanému monolitu. Mikroservis je <em>fyzická</em> hranice nasazení; stream-aligned tým je <em>organizační</em> hranice odpovědnosti. Ve zdravém stavu jsou izomorfní: 1 stream-aligned tým = 1 BC = 1 mikroservis (nebo modul v modulárním monolitu). Pokud máte 30 mikroservisů a 5 týmů, nejste v mikroservisové architektuře. Jste v distribuovaném monolitu, kde každý tým „vlastní“ 6 služeb a žádná hranice nemá soudržného vlastníka. Detail rozebírá kapitola o <a href="/ddd-a-microservices#distributed-monolith">mikroservisech a DDD</a>.'
:::

## 05.11 Další četba a citované zdroje {#dalsi-cetba}

Číslování odpovídá odkazům v textu; položky 10–14 jsou doplňková četba.

1. **Conway, M. E.** (1968). *How Do Committees Invent?* Datamation, 14(4), 28–31.
   [melconway.com](http://www.melconway.com/Home/Committees_Paper.html)

2. **Vernon, V.** (2013). *Implementing Domain-Driven Design.* Addison-Wesley. Kap. 2 (Domains, Subdomains, Bounded Contexts) a kap. 3 (Context Maps).
   [amazon.com](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)

3. **Skelton, M. & Pais, M.** (2019). *Team Topologies: Organizing Business and Technology Teams for Fast Flow.* IT Revolution Press.
   [teamtopologies.com](https://teamtopologies.com/book)

4. **Skelton, M. & Pais, M.** (2025). *Team Topologies: Organizing business and technology for fast flow of value*, 2. vydání. IT Revolution Press.
   [itrevolution.com](https://itrevolution.com/product/team-topologies-second-edition/)

5. **Brandolini, A.** (2021). *About Team Topologies and Context Mapping.* Avanscoperta Blog.
   [blog.avanscoperta.it](https://blog.avanscoperta.it/2021/04/22/about-team-topologies-and-context-mapping/)

6. **Fowler, M.** *Conway's Law.* Bliki – atribuce Inverse Conway Maneuveru a výhrady k jeho účinnosti.
   [martinfowler.com](https://martinfowler.com/bliki/ConwaysLaw.html)

7. **Forsgren, N., Humble, J. & Kim, G.** (2018). *Accelerate: The Science of Lean Software and DevOps.* IT Revolution Press.
   [itrevolution.com](https://itrevolution.com/product/accelerate/)

8. **DORA.** *A history of DORA's software delivery metrics.* Vývoj sady metrik od roku 2018.
   [dora.dev](https://dora.dev/insights/dora-metrics-history/)

9. **Westrum, R.** (2004). *A typology of organisational cultures.* Quality and Safety in Health Care, 13(suppl_2), ii22–ii27.
   [qualitysafety.bmj.com](https://qualitysafety.bmj.com/content/13/suppl_2/ii22)

10. **Yegge, S.** (2011). *Stevey's Google Platforms Rant.* Zdroj podání Bezosova API mandátu z roku 2002.
    [gist.github.com](https://gist.github.com/chitchcock/1281611)

11. **Vernon, V.** (2016). *Domain-Driven Design Distilled.* Addison-Wesley. Kap. 2 (Strategic Design with Bounded Contexts and the Ubiquitous Language).

12. **Evans, E.** (2003). *Domain-Driven Design: Tackling Complexity in the Heart of Software.* Addison-Wesley.

13. **Tune, N. & Perrin, J.-G.** (2024). *Architecture Modernization: Socio-technical alignment of software, strategy, and structure.* Manning. Kombinuje strategický DDD, EventStorming a Team Topologies do jednoho postupu.

14. Související kapitoly: [subdomény](/subdomeny), [context mapping](/context-mapping), [architektonické styly](/architektonicke-styly), [anti-vzory](/anti-vzory).
