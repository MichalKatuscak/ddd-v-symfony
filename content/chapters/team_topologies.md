---
route: team_topologies
path: /team-topologies
title: Conway's Law a Team Topologies
page_title: "Conway's Law a Team Topologies – týmová struktura v DDD | DDD Symfony"
meta_description: "Bounded Context není jen architektonický artefakt – je to týmová hranice. Conway's Law, Team Topologies (Skelton & Pais), Inverse Conway Maneuver a praktické tipy pro rozdělení týmů kolem DDD."
meta_keywords: "Conway's Law, Team Topologies, Inverse Conway Maneuver, Skelton, Pais, stream-aligned team, platform team, enabling team, complicated subsystem team, Bounded Context, DDD, kognitivní zátěž, Westrum, Vernon, organizační struktura, microservices"
og_type: article
published: "2026-04-29"
modified: "2026-06-09"
breadcrumb_name: Team Topologies
schema_type: TechArticle
schema_headline: "Conway's Law a Team Topologies – týmová struktura v DDD"
chapter_number: "05"
category: Základy
deck: 'Když Conway v roce 1968 publikoval tezi, že „systém kopíruje komunikační strukturu organizace, která ho stvořila“, popisoval gravitační zákon softwarového designu. DDD Bounded Contexts dávají smysl jen tehdy, když mapují na týmy – jinak vznikají falešné hranice. Kapitola o tom, jak vědomě navrhnout týmy kolem domény.'
reading_time: 22
difficulty: 2
---

Většina knih o DDD končí Bounded Contextem a Context Mapem – jako kdyby architektura žila ve vakuu.
Realita je jiná: jakmile máte víc než jeden tým, organizační struktura začne tlačit architekturu
do svého obrazu. Tato kapitola je o tomto *gravitačním poli*. Probereme Conway's Law z roku 1968,
Team Topologies (Skelton & Pais 2019) jako rámec pro vědomý návrh týmů a důvod, proč je
**jeden Bounded Context = jeden tým** to první DDD pravidlo, které vám management zlomí.

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
které kopírují komunikační struktury těchto organizací.** Conway sám tezi
později shrnul volněji – design systému kopíruje komunikační strukturu organizace.
Není to architektonická preskripce, ale *empirické pozorování*. A je vysoce
spolehlivé. Oddělený frontend a backend tým? Dostanete oddělený frontend a backend
v kódu. Oddělený DBA tým? V kódu se objeví vrstva, která jen obsluhuje databázi.
A jeden tým bez sub-hranic vyrobí Big Ball of Mud.

### Tři reálné případy Conway's Law v praxi

1. **Tým rozdělený podle vrstev → Layered Architecture.**
   Společnost s 30 vývojáři rozdělená na „frontend tým“, „backend tým“ a „DBA tým“
   nevyhnutelně vyprodukuje třívrstvou architekturu. Každý tým má vlastní release cyklus,
   vlastní CI/CD pipeline, vlastní sprint review. Bounded Context se stane v lepším případě
   interní záležitostí backend týmu – frontend a DBA o něm nevědí. *Důsledek:*
   změna jednoho doménového požadavku se obtočí přes všechny tři týmy a tři sprinty.

2. **Tým rozdělený podle produktu/streamu → mikroservis nebo modul per BC.**
   Stejná organizace přeorganizovaná na „Catalog tým“, „Ordering tým“, „Billing tým“
   a „Identity tým“ – každý tým plně end-to-end (frontend, backend, DB, devops) – vyprodukuje
   4 mikroservisy nebo 4 izolované moduly v monolitu, jeden per Bounded Context.
   Conway's Law funguje, jen dostala jiné vstupy.

3. **Tým bez interních hranic → Big Ball of Mud.**
   8 vývojářů, kteří všichni sahají do všeho, nevyprodukují žádné Bounded Contexts.
   Vyprodukují jeden monolit, ve kterém *Customer* ve fakturaci je tatáž třída jako
   *Customer* v marketingu, jen s víc atributy. Klasický důsledek: po 18 měsících
   si nikdo netroufne změnit nic, protože „to může mít vliv kdekoli“.

:::callout{type="note"}
### „Law“ je trochu silné slovo {#conway-not-law-heading}

Conway's Law popisuje silnou tendenci, ne fyzikální zákonitost. Stojí
za ní praktický fakt: tým, který spolu denně mluví, koordinuje sdílená
rozhodnutí přímo. Tým, který spolu nemluví, koordinaci nahrazuje stabilním rozhraním
(API, schématem, kontraktem). Tato rozhraní se časem petrifikují a stanou se architektonickými
švy. Conway's Law je tedy **statistický důsledek nákladů na komunikaci**,
ne metafyzika. Proto ji lze obejít, ale stojí to vědomé úsilí (Inverse Conway Maneuver,
sekce 05.05).
:::

:::diagram{fig="05.1-A" title="Conway vs. Inverse Conway Maneuver" src="images/diagrams/18_team_topologies/conway_inverse.svg"}
:::

## 05.02 Bounded Context = týmová hranice {#bc-team-boundary}

Vaughn Vernon v knize *Implementing Domain-Driven Design* (2013, kap. 2)
[[2]](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)
formuluje pravidlo, které je možná nejdůležitějším praktickým výstupem celého DDD:
Bounded Context má vlastnit jediný tým. Obrácená situace – jeden tým vlastnící více
Bounded Contexts – podle Vernona může být přijatelná. Pravidlo zopakoval
v *Domain-Driven Design Distilled* (2016, kap. 2): více týmů nesmí sdílet jeden kontext.

Pravidlo má dvě části, které se často chybně čtou jako jedno:

- **Jeden Bounded Context = jeden tým (vždy).**
  Pokud dva týmy sdílejí jeden BC, Conway's Law okamžitě vstoupí – buď vznikne neoficiální
  sub-hranice (a tedy fakticky dva BC, ale nikdo to nepřiznal), nebo vznikne *sdílené
  vlastnictví*, které znamená, že BC nikdo nevlastní a degraduje na Big Ball of Mud
  se [Shared Kernel](/context-mapping#shared-kernel)
  režií. *Pravidlo:* nikdy nesdílejte BC mezi týmy bez explicitního Shared Kernel
  vztahu – a Shared Kernel sám je drahý vztah, ne výchozí volba.

- **Jeden tým = jeden nebo více Bounded Contexts (povoleno).**
  Malý tým (5–9 lidí) může vlastnit 2–3 menší BC. Důvodem k limitu je
  [kognitivní zátěž](#cognitive-load) – viz sekci 05.06. Velký tým, který by
  vlastnil 5+ BC, je signál, že tým má být rozdělen.

:::callout{type="pattern"}
### Vernon Rule: 1 BC = 1 tým {#vernon-rule-heading}

Když to zkrátíme na jednu větu pro management: **každý Bounded Context má právě
jednoho vlastníka – jeden tým s explicitním závazkem ho vyvíjet, nasazovat
a opravovat v noci.** Bez takového vlastníka BC neexistuje
architektonicky. Je to jen složka v repu.

Test: ukažte si org chart a ukažte si Context Map. Pokud na org chart neumíte ukázat
jméno týmu pro každý BC, váš BC je fikce.
:::

Důsledek je nepříjemný pro mnoho organizací: **Context Map a Team Map jsou ve zdravém
stavu téměř izomorfní**. Při 7 BC a 4 týmech máte buď nesoulad (3 BC nemají
vlastníka), nebo jeden tým vlastní 2+ BC (vědomé rozhodnutí, ne nedopatření).
Detail vztahu mezi Context Map a Team Map je v kapitole o
[Context Mappingu](/context-mapping).

A obráceně: při 4 BC a 7 týmech BC nestavěl někdo, kdo používal DDD. Spíš historická
organizační struktura, kterou nikdo neaktualizoval. Zde přichází Inverse Conway Maneuver
(sekce 05.05).

### Co dělat, když Context Map a Team Map nesedí {#bc-team-mismatch}

V praxi narazíte na 4 typy nesouladu mezi Context Mapem a Team Mapem. Každý vyžaduje
jiný typ akce:

| Symptom | Příčina | Akce |
|---|---|---|
| BC bez týmu | BC vznikl architekturou na papíře, nikdy nikomu nepřiřazen | Sloučit s jiným BC nebo přiřadit existujícímu týmu jako 2. BC |
| Tým bez BC | Horizontální tým (frontend / DBA) bez doménové odpovědnosti | Inverse Conway: rozpustit a přerozdělit do stream-aligned týmů |
| BC sdílený 2 týmy | Organické zvětšování bez rozdělení BC nebo týmu | Buď rozdělit BC na 2 menší + Customer/Supplier, nebo sloučit týmy |
| 1 tým vlastní 5+ BC | Akumulace bez měření cognitive load | Split týmu (sekce [05.06](#cognitive-load)) nebo redukce počtu BC |

Žádný z těchto scénářů není akutní krize – Conway's Law dává systému dostatek setrvačnosti,
aby s nesouladem fungoval měsíce. Ale dlouhodobě se nesoulad projeví v rostoucích lead time,
rostoucí change failure rate a klesající morálce týmů. Nesoulad je pomalý jed, ne explozivní
porucha.

## 05.03 Team Topologies – 4 typy týmů (Skelton & Pais 2019) {#team-topologies-typy}

V roce 2019 vydali Matthew Skelton a Manuel Pais knihu
*Team Topologies: Organizing Business and Technology Teams for Fast Flow*
[[3]](https://teamtopologies.com/book). Poprvé systematicky popsali, **jaké typy
týmů má organizace mít** a **jak mezi sebou mají komunikovat**. Team Topologies dodává *slovník* pro organizační návrh,
nepředepisuje proces jako SAFe nebo LeSS.
A pro DDD je to chybějící doplněk Vernona.

Skelton a Pais identifikovali 4 typy týmů. Cokoliv jiného (klasický „enterprise architecture
team“, „QA tým“, „Center of Excellence“) je buď maskovaná varianta jednoho ze 4 typů,
nebo organizační anti-vzor.

### Stream-aligned team {#stream-aligned}

**Vlastník end-to-end value streamu – typicky jeden Bounded Context.**
Stream-aligned tým má všechny role pro samostatné doručení hodnoty koncovému uživateli:
vývojáře (frontend i backend), QA, designéra, někdy product ownera.
Tým rozhoduje, doručuje a provozuje v produkci – žádné „předání“ do jiného týmu.

- **Velikost:** 5–9 lidí (Two-Pizza Rule).
- **Vlastnictví:** 1 BC (typicky), maximálně 2–3 související malé BC.
- **Cíl:** minimalizovat kognitivní zátěž a maximalizovat *flow* hodnoty.
- **Měření:** DORA metriky (lead time, deployment frequency, change failure rate, MTTR).

*Většina týmů v každé zdravé technologické organizaci jsou stream-aligned týmy.*
Při 10 týmech a méně než 7 stream-aligned se podíváme na anti-vzory v sekci 05.08.

### Platform team {#platform-team}

**Poskytuje self-service platformu pro stream-aligned týmy.**
Platform team vlastní interní vývojářskou platformu (IDP – Internal Developer Platform).
Patří sem CI/CD šablony, observability stack (Prometheus, Grafana, Sentry), Kubernetes,
secrets management, šablony pro nové BC, vývojářský portál.

Hlavní atribut Platform teamu je slovo **self-service**. Stream-aligned tým
si na platformu nezadává ticket („potřebuju nový Postgres“) a nečeká týden. Naklikne ho
sám přes portál nebo nasadí přes IaC modul, který Platform team udržuje. Platform
team, který funguje jako ticketová fronta, se mění v úzké hrdlo
infrastruktury – anti-vzor v sekci 05.08.

- **Velikost:** 1 Platform team na 50–150 vývojářů; obvykle 5–9 lidí.
- **Měření:** NPS od stream-aligned týmů, adoption rate, time-to-first-deploy pro nový BC.
- **Anti-charakter:** Platform team *nesedí na změnách* – má roli enabler, ne gatekeeper.

### Enabling team {#enabling-team}

**Time-boxed tým specialistů, který pomáhá stream-aligned týmu osvojit si novou
techniku nebo technologii.** Klasické úkoly: „naučte je TDD“, „zaveďte CQRS“,
„pomozte s migrací na K8s“, „rozjeďte s nimi event sourcing“. Enabling team se po předání
rozpustí, typicky po 3–6 měsících. Žádný permanentní útvar.

Enabling team se často zamění s Center of Excellence. Rozdíl je podstatný:

| Aspekt | Enabling team | Center of Excellence (anti-vzor) |
|---|---|---|
| Doba existence | Time-boxed (3–6 měsíců) | Permanentní útvar |
| Cíl | Předat dovednost, rozpustit se | Držet kontrolní bod, schvalovat |
| Vztah k stream-aligned týmu | Mentor, peer | Recenzent, autorita |
| Měření úspěchu | Stream-aligned tým to umí sám | Kolik tiketů jsme schválili |

### Complicated-subsystem team {#complicated-subsystem-team}

**Vlastní algoritmicky náročnou doménu, kterou by stream-aligned tým nezvládl
bez vyhrazených specialistů.** Typické příklady: risk engine v reálném čase v bance,
ML scoring model, video transcoder, fyzikální simulátor, kompilátor, kryptografická
knihovna. Tým drží vysokou koncentraci specializovaných znalostí (PhD v matematice,
fyzice nebo CS, hluboké know-how v doméně), které nelze rozprostřít přes 6 stream-aligned
týmů.

- **Vznik:** jen tehdy, kdy stream-aligned tým objektivně narazí na strop.
- **Komunikace:** obvykle X-as-a-Service vůči stream-aligned týmům.
- **Past:** ze stream-aligned týmu se stane „complicated subsystem“ jen proto, že má seniornější obsazení. To není důvod – pravým důvodem je *objektivní specializace*.

### Mapování DDD subdomén na typy týmů {#subdomain-mapping}

Klasifikace subdomén (Core / Supporting / Generic) přirozeně mapuje na typy týmů.
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
Nejčastější chyba v enterprise: senior vývojáři dělají „platformu“ nebo „architecture“
a Core BC drží junior tým. To je inverze priorit – Platform tým má povolit autonomii,
ne ji koncentrovat. Core BC je jediné místo, kde firma vyhrává nad konkurencí.
:::

## 05.04 Tři interakční módy mezi týmy {#interakcni-mody}

Skelton a Pais nedefinovali jen typy týmů, ale i 3 (a jen 3) povolené módy interakce
mezi nimi. Cokoliv jiného („tak ti tam někdo pomůže“, „domluvte se nějak“, „pošli
ticket a uvidíme“) = neformální vztah. Conway's Law ho okamžitě začne tvarovat ad hoc
interfacem v kódu.

### Collaboration {#collaboration}

**Dva týmy společně, intenzivně řeší problém.** Sdílí backlog, plánují spolu,
code-review napříč. Mód je *vysoce produktivní, ale drahý* – duplikuje meetingy,
rozmazává odpovědnost, zatěžuje cognitive load obou týmů. Proto je explicitně
**časově omezený**.

- **Kdy:** při objevu nového problému (discovery), při zásadním refaktoringu, při bootstrapu nového BC.
- **Kdy ukončit:** jakmile je interface jasný – přejít na X-as-a-Service.
- **Mapování na DDD:** Partnership / Shared Kernel z Context Mapu.
- **Past:** permanentní Collaboration → tyto dva týmy mají být *jeden tým*. Spojte je.

### X-as-a-Service {#x-as-a-service}

**Jeden tým konzumuje druhý jako černou skříňku přes stabilní API/kontrakt.**
Konzument nezná ani interní strukturu, ani sprint plan poskytovatele. Má pouze SLA,
dokumentaci a release notes. Toto je výchozí stav většiny mezi-týmových vztahů
ve zralé organizaci.

- **Mapování na DDD:** Customer / Supplier nebo Open Host Service z Context Mapu.
- **Měření:** SLA, error rate, dostupnost API, breaking-change rate.
- **Cíl:** minimální komunikace nutná k používání služby. Žádný stand-up napříč týmy.
- **Past:** X-as-a-Service vyžaduje *vyspělé API a versionování* – pokud poskytovatel mění API každý sprint, je to faktická Collaboration s falešnou nálepkou.

### Facilitating {#facilitating}

**Enabling team pomáhá stream-aligned týmu osvojit si nové know-how.**
Mód je dočasný (3–6 měsíců) a interaktivní (pair programming, code review, workshopy).
Cíl: stream-aligned tým *to bude umět sám*. Po dosažení cíle Enabling team
odejde k jinému stream-aligned týmu.

- **Mapování na DDD:** nemá přímý ekvivalent v Context Mapu (Context Map řeší vztahy mezi BC, ne dovednosti uvnitř BC).
- **Cíl:** autonomie stream-aligned týmu po předání.
- **Past:** Facilitating, který trvá rok+, se z definice mění na Center of Excellence.

:::callout{type="warn"}
### Žádné „volné vztahy“ {#modes-mandatory-heading}

Hlavní pravidlo Team Topologies: **každá interakce mezi dvěma týmy MUSÍ být
explicitně jeden ze 3 módů**. Žádné „neformální“ vztahy. Důvod je čistě
Conwayovský: neformální vztahy nemají kontrakt. Vznikne ad hoc kontrakt v kódu
(sdílená třída, sdílené DB schéma, „prostě se tomu nedotýkej“), který později nikdo
nedokáže refaktorovat.

Při onboardingu nového týmu napište explicitně: „*S týmem A jsme v X-as-a-Service,
s týmem B jsme ve 4měsíční Collaboration na bootstrap nového BC, s Enabling teamem
máme kontrakt na 3-měsíční facilitaci CQRS.*“ Pokud to nedokážete napsat, vztah
je neformální = v ohrožení.
:::

## 05.05 Inverse Conway Maneuver {#inverse-conway}

Conway's Law říká „struktura kopíruje organizaci“. **Inverse Conway Maneuver**
obrací směr: *pokud chceme jinou strukturu, MUSÍME nejdřív změnit organizaci.*
Místo bojování s Conway's Law ji použijeme jako nástroj.

Inverse Conway Maneuver popsali Skelton a Pais (2019, kap. 2) jako 4-krokový postup,
navazující na výzkum Forsgren, Humble a Kim v *Accelerate* (2018):

1. **Definovat cílovou architekturu.** Typicky Context Map z DDD –
   seznam Bounded Contexts a vztahů mezi nimi. Bez tohoto kroku není co kopírovat.
   Detail v kapitole o [Context Mappingu](/context-mapping).

2. **Spočítat počet stream-aligned týmů.** Hrubé pravidlo: 1 BC = 1 tým.
   Pokud máte 6 BC, potřebujete 6 stream-aligned týmů. Pokud máte aktuálně 3 týmy
   (frontend, backend, DBA), znamená to reorganizaci na 6 vertikálních týmů – buď z existujících
   lidí, nebo nábor.

3. **Re-org: rozpustit horizontální týmy, poskládat vertikální stream-aligned týmy.**
   Klasický bod, kde implementace selže. Frontendoví lidé nechtějí být „v Catalog týmu“ –
   chtějí být s ostatními frontend kolegy. Manažeři nechtějí ztratit tým 12 lidí pro tým
   7 lidí. Tato fáze potřebuje silnou podporu CTO/VP Engineering.

4. **Vyřešit Platform team.** Z definice 1 Platform team na celou organizaci
   (50–150 vývojářů). Vznikne typicky z bývalých „infrastructure“ lidí + 1–2 senior
   z každého stream-aligned týmu. Cíl: do 6 měsíců self-service IDP.

Skelton a Pais výslovně varují: **Inverse Conway Maneuver bez podpory managementu
neuspěje**. Re-org je politický akt. Pokud CTO řekne „udělejte to, ale beze změny
org chartu“, máte před sebou 6 měsíců práce, která nikam nevede. Conway's Law původní
org chart vrátí každý refaktor.

:::callout{type="note"}
### Reálný příběh: Amazon, 2002 {#inverse-real-world-heading}

Klasická případová studie Inverse Conway Maneuver: Jeff Bezos v roce 2002 vydal interní
nařízení, že *všechny týmy budou komunikovat výhradně přes API*. Žádné sdílené databáze,
žádné funkční volání napříč týmy, žádné neformální komunikační kanály.
Týmy, které to nedodrží, budou propuštěny.

Bezos nepředepsal architekturu mikroservis. Předepsal **komunikační režim
týmů**. Mikroservisy přišly automaticky, protože to byl jediný způsob, jak
Bezosův mandát splnit. Tahle klasika je ukázka Inverse Conway Maneuver
v měřítku 7 000 vývojářů.
:::

Praktická past: reorganizace je bolestivá. Lidé ztrácejí seniority, manažeři ztrácejí pravomoci,
domácí kultury týmů (frontend kávovar, backend stand-up) se rozbijí. Pokud jste team-lead
a zvažujete Inverse Conway Maneuver bez výslovného zadání od CTO, raději se nejdřív
zeptejte. Detail komunikace s managementem je v sekci 05.09.

### Praktický checklist před spuštěním Inverse Conway Maneuver {#inverse-checklist}

Než zahájíte reorganizaci, projděte následující seznam. Pokud na *kterýkoli* bod
odpovíte „ne“, Inverse Conway je předčasný a v 90 % případů selže:

1. **Existuje kanonická Context Map?** Bez ní není definovaná cílová
   architektura. Krok 1 selhal a kroky 2–4 nemají kam směřovat. Pokud nemáte Context Map,
   začněte tam (kapitola o [Context Mappingu](/context-mapping)).

2. **Má reorganizace výslovnou podporu CTO / VP Engineering?** Reorganizace je politický
   akt. Bez podpory shora není odpor odolatelný – lidé budou hledat výjimky a starou
   strukturu obnoví neoficiálně.

3. **Máte 6 měsíců času?** Reorganizace pod 6 měsíců typicky nefunguje. Lidé
   potřebují čas se přesunout, naučit se nové domény, vybudovat nové vztahy.

4. **Existuje plán pro Platform team?** Bez self-service platformy se
   stream-aligned týmy zaseknou na infrastruktuře. Platform team musí mít alespoň
   minimum-viable IDP připravený před reorganizací (1-click new-BC bootstrap, CI šablona,
   výchozí observability).

5. **Změřili jste DORA metriky před reorganizací?** Bez baseline neumíte
   obhájit úspěch ani identifikovat regresi. Jednoduché měření: lead time z PR-merge
   do produkce, deployment frequency, change failure rate (% deploy s rollbackem),
   MTTR (medián času na vyřešení P1 incidentu).

6. **Je organizace v Westrum generative kultuře?** V pathological / bureaucratic
   reorganizace formálně proběhne, ale operativní vztahy se vrátí (sekce [05.09](#westrum)).

7. **Je obsazená pozice „topology owner“?** Někdo musí reorganizaci vést na
   denní bázi – typicky staff engineer + manažer. Bez vlastníka se reorganizace rozplyne
   do běžných sprint priorit.

Pokud máte všech 7 bodů „ano“, máte vyšší šanci než průměr. Zbývá jen práce.

## 05.06 Cognitive Load – limit pro velikost týmu/BC {#cognitive-load}

Pojem **kognitivní zátěž** (cognitive load) převzali Skelton a Pais
z teorie učení Johna Swellera (1988). Sweller rozlišuje 3 typy zátěže, které se vážou
i na softwarové týmy:

- **Intrinsic load** (přirozená) – komplexita samotné domény. „Bankovní risk
  engine“ má vyšší intrinsic load než „katalog produktů“. Toto se nedá snížit, jen rozdělit
  mezi víc týmů.

- **Extraneous load** (zbytečná) – zátěž z prostředí, ne z domény: nestabilní
  CI, špatná dokumentace platformy, 5 různých deploy procesů, chaos v Slack kanálech.
  Toto je úkol Platform teamu odstranit.

- **Germane load** (rozvojová) – energie, kterou tým vkládá do učení a zlepšování.
  Toto má být pozitivní – pokud má tým přetížený intrinsic + extraneous, germane mizí
  (tým přestane investovat do zlepšení).

Cíl: **maximalizovat intrinsic + germane, minimalizovat extraneous.**
Tým, který tráví 80 % energie zápasem s CI a deploy procesem, nemá kapacitu zlepšovat
doménový model.

### Pravidlo cognitive load pro počet BC na tým {#cognitive-load-rule}

Skelton a Pais doporučují praktickou heuristiku:

| Velikost týmu | Doporučený počet BC | Komentář |
|---|---|---|
| 5 lidí | 1 BC (max 2 malé) | Limit bližšího vědomí; každý zná každý kus kódu. |
| 7–9 lidí | 1–2 BC | Zdravé optimum stream-aligned týmu. |
| 10+ lidí | Tým je už příliš velký – rozdělit | Dunbar number (familiarity ≈ 15). Komunikační režie exponenciálně roste. |
| Tým s 4+ BC | – | Signál pro rozdělení. BC nemají soudržného vlastníka. |

### Jak změřit cognitive load (jednoduchá rubrika) {#cognitive-load-rubric}

Skelton a Pais doporučují jednou za kvartál spustit s týmem 30-minutový workshop,
kde každý člen ohodnotí na škále 1–5 následující:

1. **Doménová komplexita** (intrinsic) – „Rozumím kompletně doméně, kterou náš tým vlastní?“
2. **Technická komplexita** (intrinsic) – „Rozumím všem technologiím, které používáme?“
3. **Stabilita platformy** (extraneous, inverze) – „Můžu se spolehnout na CI/CD, observability, deploy?“
4. **Kvalita dokumentace** (extraneous, inverze) – „Najdu potřebné info do 5 minut?“
5. **Prostor na učení** (germane) – „Mám každý sprint 1–2 hodiny na zlepšení/learning?“

Body 1+2 vysoké = tým má pod kontrolou intrinsic. Body 3+4 vysoké = Platform team funguje
a extraneous load je nízký. Bod 5 vysoký = tým má kapacitu na germane.

*Pokud je průměr bodu 5 pod 3, tým je v krizovém režimu – žádné nové BC, žádné nové
technologie. Nejdřív stabilizovat extraneous load.*

Níže je rubrika ve formátu, který stačí vlepit do `docs/cognitive-load.md`
v repu týmu. Vejde se na 1 stránku A4, vyplní se za 30 minut na konci sprintu a je dobrým
vstupem pro retro:

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

Rubrika záměrně měří *vnímání* členů týmu; tvrdá metrika z Grafany cognitive load nezachytí.
Důvod: cognitive load je psychologická kategorie, žádná metrika z Grafany ji nezachytí.
Skelton a Pais (2019, kap. 3 „Team-First Thinking“) výslovně varují před snahou cognitive load „objektivizovat“
přes počet řádků kódu, count of services, nebo ticket throughput. Tyto proxy metriky
nemají korelaci s tím, jak se tým reálně cítí.

:::callout{type="warn"}
### Varování: sklon k rozšiřování BC {#cognitive-warning-heading}

Velmi častá past: tým s úspěšným Core BC dostane od managementu „ještě jeden malý BC,
zvládnete to“. Pak ještě jeden. Pak ještě jeden. Po roce má tým 4 BC, je vyhořelý a žádný
BC není dotažen. **Zdravý mechanismus: kdykoli se přidává BC, někdo musí
explicitně zodpovědět – co odebíráme?** Pokud nic, tým buď rozšíříme,
nebo rozdělíme. Žádná akumulace.
:::

## 05.07 Praktické scénáře (5 / 20 / 200+ lidí) {#scenare}

Team Topologies není doktrína „udělejte všech 4 typy týmů a 3 módy hned“. Je to
*jazyk*, kterým se popisuje aktuální stav a cíl. Konkrétní podoba závisí na velikosti
organizace.

### Scénář A – Startup, 5 lidí, 1 produkt {#scenar-startup}

**Doporučení:** 1 stream-aligned tým, 2–3 malé BC v jednom monolitu (modulární
monolit). Žádný Platform team, žádný Enabling team.

- **Architektura:** jeden Symfony monolit; BC jsou složky/moduly s explicitními rozhraními (kapitola o [architektonických stylech](/architektonicke-styly)).
- **Generic subdomény:** nakoupit jako SaaS, žádná vlastní implementace – argumenty a sourcing strategii build/buy rozebírá kapitola o [subdoménách](/subdomeny#sourcing).
- **Hosting:** Heroku, Vercel, Railway, Fly.io – managed services nahrazují Platform team.
- **Čeho se vyvarovat:** nepouštět se do Kubernetes, vlastní observability stack, mikroservisy. Předčasné.

*Chyba startupů:* kopírovat enterprise architekturu „aby to bylo připraveno na budoucnost“.
Cognitive load 5-členného týmu nemá kapacitu na 6 mikroservisů. Modulární monolit je správná
volba.

### Scénář B – Scale-up, 20 lidí, 1 produkt s rostoucí komplexitou {#scenar-scaleup}

**Doporučení:** 2–3 stream-aligned týmy podle BC + 1 mini-Platform team
(3–5 lidí) na CI/CD a observability. Žádný permanentní Enabling team.

- **Stream-aligned týmy:** rozdělené podle hlavních value streamů. Např. Catalog tým (5 lidí), Ordering tým (6 lidí), Identity+Billing tým (4 lidi, sdílí 2 supporting BC).
- **Platform team:** 4 lidi, vlastní CI pipeline šablonu, K8s cluster, Grafana/Sentry, šablonu pro nový BC. Self-service.
- **Enabling team:** ne na trvalo. Pokud potřebujete zavést CQRS, najměte si externího konzultanta na 3 měsíce.
- **Interakční módy:** Stream-aligned týmy mezi sebou X-as-a-Service. Platform team se všemi v X-as-a-Service. Příležitostná Collaboration při bootstrapu nového BC.

Tato fáze je nejrizikovější – organizace už není malá, ale ještě nemá kapacitu na plný
rozsah Team Topologies. Klasická chyba: vznikne Center of Excellence („architektonický
výbor“), který se stane bottleneckem.

### Scénář C – Enterprise, 200+ lidí, 10+ BC {#scenar-enterprise}

**Doporučení:** plná Team Topologies struktura.

- **10–15 stream-aligned týmů**, každý vlastní 1 BC (případně 2 související supporting BC).
- **1–2 Platform teamy** – typicky 1 hlavní (IDP, K8s, observability) + někdy specializovaný (data platform, ML platform).
- **1–3 Enabling teamy** – rotující, time-boxed, podle aktuálních potřeb (např. „security enabling team“ na 6 měsíců, „event sourcing enabling team“ na 3 měsíce).
- **1–2 Complicated-subsystem teamy** – jen pro objektivně specializované domény (např. risk engine v bance, video transcoder v médiích, ML scoring v ad-techu).
- **Topology design:** Skelton a Pais doporučují, aby v této velikosti existoval malý *topology team* (1–2 lidi, není to Center of Excellence). Sleduje cognitive load týmů a navrhuje reorganizace. Často je to staff engineer + manažer.

I v 200-členné firmě mají stream-aligned týmy **výrazně převažovat** – orientačně
tři čtvrtiny lidí. Pokud máte 200 lidí a 100 z nich je v Platform/Enabling/CoE/architekti, máte problém –
stream-aligned týmy nesou doménovou hodnotu, ostatní jsou multiplikátory. Multiplikátorů
nemá být víc než multiplicandů.

:::callout{type="pattern"}
### Orientační proporce (75/15/10) {#scenare-summary-heading}

Orientační poměr pro zralou organizaci. Skelton a Pais konkrétní procenta neuvádějí; trvají jen na tom, aby stream-aligned týmy v organizaci jasně dominovaly:

- **≈ 75 %** lidí ve stream-aligned týmech (delivery hodnoty)
- **≈ 15 %** v Platform teamu(ech)
- **≈ 10 %** v Enabling + Complicated-subsystem (rotující, podle potřeby)

Pokud vám čísla ukazují 50/30/20 nebo dokonce 30/40/30, máte „enterprise architecture
inflation“ – moc lidí v multiplikátorech, málo lidí, co reálně doručují.
:::

## 05.08 Anti-vzory {#antivzory}

Než se dostaneme k tomu, jak to udělat, je užitečné vědět, jak to *nedělat*.
Následujících pět anti-vzorů je ze zkušenosti autorů Team Topologies nejčastějších a nejdražších.
Detailní katalog DDD anti-vzorů je v samostatné kapitole o
[anti-vzorech](/anti-vzory).

### 1. „Sdílíme jeden monorepo bez hranic modulů“ {#antivzor-shared-repo}

Více týmů commituje do jednoho repozitáře bez jasných hranic mezi moduly. Důsledek:
každá netriviální změna jednoho týmu vyžaduje code review od ostatních („jen abychom
se ujistili, že to nic nerozbije“). Druhý tým má fakticky veto na změny prvního.

**Řešení:** buď jasné hranice modulů v monorepu (Nx, Bazel, Turborepo
pro JS, Symfony Bundles pro Symfony) s explicitním ownership v `CODEOWNERS`,
nebo separátní repa per BC. Nikdy ne princip „všichni do jednoho repa, nějak se domluvíme“.

### 2. „Frontend / Backend / Mobile týmy“ {#antivzor-frontend-backend}

Klasický anti-vzor přímo z Conway's Law – týmy rozdělené po vrstvách. Každá nová funkce
vyžaduje koordinaci 3 týmů, 3 sprintů, 3 retrospektiv. Lead time přes 6 týdnů na úpravu,
která si vyžádá zhruba 3 dny práce.

**Řešení:** Inverse Conway Maneuver. Rozpustit horizontální týmy a poskládat
vertikální stream-aligned týmy. Každý tým má *všechny* potřebné role
(frontend dev + backend dev + mobile dev + QA + designer). Pokud je mobile aplikace
zásadní část produktu, ne vedlejší kanál, mobile vývojáři patří do stream-aligned týmů,
ne do separátního „mobile týmu“.

Výjimka: při jediném mobilním vývojáři na celou organizaci má dočasná „mobile guild“
smysl. Ne jako tým s vlastním backlog, ale jako komunita pro sdílení znalostí.

### 3. „Center of Excellence“ místo Enabling teamu {#antivzor-coe}

Permanentní útvar „architektů“ / „expertů“ / „vedoucího týmu“, který drží schvalovací
pravomoc nad ostatními. Klasická corporate inkarnace: ARB (Architecture Review Board),
který musí každou novou službu schválit.

**Co je špatně:** CoE typicky drží *kontrolní bod*, ne expertní podporu.
Schvalování ze své podstaty zpomaluje, vytváří frontu a zbavuje stream-aligned týmy
odpovědnosti („to nám neschválili, nemůžeme za to“).

**Řešení:** CoE → Enabling team. Time-boxed, mentoring místo schvalování,
rozpuštění po předání. Pokud je „schvalování“ nutné, dělá ho stream-aligned tým sám
podle dokumentovaných standardů, ne externí výbor.

### 4. „Platform team jako gatekeeper / ticketová fronta“ {#antivzor-platform-gatekeeper}

Platform team, který funguje jako infrastructure ticket support: stream-aligned tým
potřebuje nový Postgres, vytvoří JIRA ticket, čeká 5 dnů. Potřebuje upravit CI pipeline,
vytvoří ticket, čeká týden. Reálně se z Platform teamu stalo úzké hrdlo pro celou
organizaci.

**Řešení:** Platform team má povinnost dodávat *self-service* rozhraní
(CLI, portál, IaC moduly). Pokud stream-aligned tým musí zadávat tikety, je to chyba designu
platformy, ne chyba zadávajícího týmu.

Hlavní metrika: **time-to-first-deploy pro nový BC**. Ve zdravé organizaci
pod 1 den. V nezdravé organizaci „ozkoušíme to za měsíc, jakmile platform team má kapacitu“.

### 5. „Sdílený Bounded Context mezi 2 týmy“ {#antivzor-shared-bc}

Dva stream-aligned týmy oba commitují do stejného Bounded Contextu, protože „to dává smysl“.
Conway's Law okamžitě reaguje – vznikne neformální sub-hranice (čára „naše/vaše“ v kódu),
ale bez formální Context Map. Tato čára se petrifikuje a po 6 měsících je z toho de facto
Big Ball of Mud s dvěma vlastníky.

**Řešení:** rozdělit BC na 2 menší BC se Shared Kernel (drahý, viz Context
Mapping) nebo Customer/Supplier vztahem. Případně sloučit 2 týmy do 1 většího – pokud
doména nejde rozdělit.

:::callout{type="anti"}
### Test: máte tyto anti-vzory? {#antivzory-test-heading}

1. Můžete v org chartu ukázat *jediného* vlastníka pro každý BC?
2. Mají všechna stream-aligned týmy *všechny* role potřebné k samostatné delivery?
3. Existuje útvar (CoE, ARB, „architektonický výbor“), který schvaluje technická rozhodnutí stream-aligned týmů?
4. Když stream-aligned tým chce nový Postgres, klikne na něj, nebo ticketuje?
5. Je každá interakce mezi 2 týmy explicitně Collaboration / X-as-a-Service / Facilitating?

Pokud na 2+ otázky odpovídáte „ne“ / „ano (CoE)“ / „zadává tiket“, máte před sebou práci.
:::

## 05.09 Komunikace s managementem – jak prodat reorganizaci {#management}

Inverse Conway Maneuver je hluboká organizační změna. Týmy bude třeba rozdělit, manažery
přealokovat, lidé možná ztratí senioritu nebo „svůj koutek“. Bez pochopení a podpory
managementu (CTO / VP Engineering / People Ops) Inverse Conway selže. Reorganizace
bez podpory shora se v praxi neudělá vůbec.

Podstatné je **mluvit jazykem, kterému management rozumí** – ne jazykem DDD.
Manažeři neumí ocenit „přesnější doménový model“ nebo „jasněji ohraničené Bounded Contexts“.
Umí ocenit metriky.

### Argumenty, které fungují (DORA metriky) {#dora-metriky}

Nicole Forsgren, Jez Humble a Gene Kim v knize *Accelerate* (2018)
[[4]](https://itrevolution.com/product/accelerate/)
zveřejnili 4 metriky (DORA). Ty měří efektivitu doručování softwaru a *silně
korelují* s obchodními výsledky (zisk, růst, customer satisfaction):

- **Lead time for changes** – čas od commitu do produkce. Stream-aligned týmy: hodiny. Horizontální týmy: dny–týdny.
- **Deployment frequency** – kolikrát za den/týden deploy. Stream-aligned: víckrát denně. Horizontální: 1× za sprint.
- **Change failure rate** – % deploymentů, které způsobí incident. Stream-aligned: pod 15 %. Horizontální: 30–60 %.
- **Mean time to restore (MTTR)** – čas zotavení z incidentu. Stream-aligned: hodina. Horizontální: dny.

**Před reorganizací změřte 4 DORA metriky. Po reorganizaci změřte znovu po 6 měsících.**
Pokud Inverse Conway funguje, lead time se zkrátí o 30–80 %, change failure rate se sníží
o 30–50 %. Tato čísla CTO chápe.

### Argumenty, které nefungují {#argumenty-nefunguji}

- „Eric Evans by to chtěl“ – manažer není v DDD komunitě.
- „Je to elegantnější“ – manažer neměří eleganci.
- „Bounded Contexts jsou kanonické“ – manažer neměří kanoničnost.
- „Zlepší se to“ – bez metriky je „zlepší“ prázdné slovo.
- „Skelton a Pais to říkají“ – autorita argumentem nestačí.

### Westrumova kultura organizace {#westrum}

Sociolog Ron Westrum v roce 2004 publikoval typologii organizačních kultur
[[5]](https://qualitysafety.bmj.com/content/13/suppl_2/ii22),
kterou později použila Forsgren v *Accelerate* jako hlavní prediktor úspěchu DevOps
transformací. Westrum rozlišuje 3 typy:

| Aspekt | Pathological (power-oriented) | Bureaucratic (rule-oriented) | Generative (performance-oriented) |
|---|---|---|---|
| Spolupráce | Nízká | Mírná | Vysoká |
| Chyby | Trestány | Vedou k hledání viníků | Vedou k učení |
| Nové nápady | Drceny | Považovány za problém | Vítány |
| Sdílení informací | Skryto | Ignorováno | Aktivně podporováno |

**Team Topologies funguje jen v generative kultuře.** V pathological kultuře
(manažer trestá za chyby, hierarchie je vše) stream-aligned týmy nedostanou autonomii.
Manažer chce mít kontrolní bod, takže Platform team se stane gatekeeper. V bureaucratic
kultuře (přesné role, formální procesy) reorganizace projde, ale provozní vztahy
zůstanou. Conway's Law přijde zpět přes formální schvalování.

Pokud poznáte, že vaše organizace je pathological nebo bureaucratic, *Inverse Conway
Maneuver není první krok*. První krok je změna kultury (nebo i změna pracoviště).
To je smutná, ale realistická diagnóza.

:::callout{type="pattern"}
### Vzorový pitch pro CTO (3 odstavce) {#management-pitch-heading}

1. „*Naše současné DORA metriky: lead time 18 dní, deployment frequency 1×/sprint,
change failure rate 35 %. Benchmark high-performers (Google, Amazon, Netflix):
lead time hodiny, deploy víckrát denně, change failure rate pod 15 %.*“

2. „*Hlavní příčina: rozdělení týmů podle vrstev (frontend/backend/DBA), které způsobuje
předávky a koordinační režii. Conway's Law nám brání rychlejšímu doručování.*“

3. „*Návrh: reorganizace na stream-aligned týmy podle Bounded Contexts během 6 měsíců.
Cíl: lead time pod 3 dny, deploy denně, change failure rate pod 20 %. Měření
a re-evaluace po 6 měsících.*“

Tento pitch má 3 atributy: čísla, srovnání, časový plán s re-evaluací. To je jazyk
CTO. Vlastní filozofie DDD a Team Topologies do pitche nepatří – leží v technické
příloze.
:::

## 05.10 Shrnutí {#summary}

Conway's Law z roku 1968 říká: architektura kopíruje organizační strukturu. Pro DDD
to znamená jednu věc – **Bounded Context bez vlastnícího týmu je fikce**.
Pokud máte 7 BC v Context Mapu a 3 týmy v org chartu, vaše BC neexistují, jen jsou
napsané v dokumentaci.

Team Topologies (Skelton & Pais, 2019) je rámec pro vědomý návrh týmů, který doplňuje
DDD tam, kde Vernon a Evans mlčí. Hlavní poznatky:

- **4 typy týmů:** Stream-aligned (vlastní BC end-to-end, výchozí),
  Platform (self-service IDP), Enabling (time-boxed mentoring), Complicated-subsystem
  (objektivně specializovaná doména).
- **3 interakční módy:** Collaboration (drahá, časově omezená),
  X-as-a-Service (výchozí vyspělý vztah), Facilitating (mentoring time-boxed).
- **Vernonovo pravidlo:** 1 BC = 1 tým. 1 tým může vlastnit 1–3 BC,
  ale BC sdílený mezi týmy = porucha.
- **Subdomény → typy týmů:** Core → stream-aligned (nejlepší tým) /
  complicated-subsystem; Supporting → stream-aligned (sdílí tým s jiným supporting BC);
  Generic → SaaS, Platform team integruje.
- **Inverse Conway Maneuver:** nejdřív definovat cílovou architekturu,
  pak postavit týmy tak, aby ji přirozeně vyprodukovaly. Bez podpory CTO neuspěje.
- **Cognitive load:** ≤ 2 BC na 5–9 lidí. 4+ BC na tým = signál pro rozdělení.
  Měřte kvartálně.
- **Proporce:** orientačně 75 % stream-aligned, 15 % platform, 10 % enabling
  + complicated-subsystem. Poměr je zobecněním z praxe; podstatná je převaha
  stream-aligned týmů.
- **Komunikace s managementem:** DORA metriky, ne DDD filozofie.
  Westrumova generative kultura je předpoklad, ne výstup.

Pokud z této kapitoly odejdete s jednou větou, ať je to tato: **Bounded Context
je závazek konkrétního týmu vyvíjet, nasazovat a v noci opravovat svou část
domény.** Bez tohoto závazku zůstává jen složkou v repu.

Pro hlubší studium doporučujeme Skelton & Pais – *Team Topologies*
[[3]](https://teamtopologies.com/book),
Vernon – *Implementing Domain-Driven Design*, kap. 2 a 3
[[2]](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577),
a Forsgren et al. – *Accelerate*
[[4]](https://itrevolution.com/product/accelerate/)
pro DORA metriky a Westrumovu typologii. Originální Conway 1968 esej je krátká
(4 strany) a stojí za přečtení
[[1]](http://www.melconway.com/Home/Committees_Paper.html).

:::faq{}
- question: Co když máme jediný tým? Platí Team Topologies i pro nás?
  answer: 'Ano, ale v zjednodušené podobě. Jednočlenný stream-aligned tým (5–9 lidí) je legitimní organizační struktura – typický startup. Nemáte Platform team (využijete managed services jako Heroku/Vercel/Stripe/Auth0), nemáte Enabling team (najmete externího konzultanta na 3 měsíce, pokud potřebujete). Jediné, co řeší Team Topologies pro vás, je interní rozdělení týmu – nepoužívejte „mini-frontend / mini-backend“ rozdělení uvnitř 6 lidí. Detail v <a href="#scenar-startup">scénáři A</a>.'
- question: Můžu mít 1 tým, který vlastní 5 Bounded Contexts?
  answer: 'Krátkodobě možná, dlouhodobě ne. Vernon (2013) sám připouští, že 1 tým může vlastnit více BC – typicky 2, ojediněle 3. Při 5 BC narážíte na cognitive load (sekce <a href="#cognitive-load">05.06</a>): tým ztratí přehled o detailech každého BC, kvalita kódu klesá, lead time roste. Praktická heuristika: pokud máte 5 BC na jeden tým, plánujte rozdělení na 2 týmy do 6 měsíců. Pokud nemáte na 2 týmy lidi, redukujte počet BC (sloučení do supersetu, nebo přesun na SaaS u Generic subdomén).'
- question: Jak Team Topologies souvisí se Spotify Modelem?
  answer: 'Spotify Model (squads, tribes, chapters, guilds, popsaný 2012) byl jeden z prvních pokusů popsat organizační strukturu pro software v poměrech velké internetové firmy. Stream-aligned tým ≈ Spotify squad. Tribe (kolekce squadů kolem doménové oblasti) v Team Topologies žádný přímý ekvivalent nemá. Skelton a Pais se jí vyhnuli, protože zkušenosti ukazují, že tribes se stávají Conway-stylové „divize“, které brzdí toky napříč. Chapters a guilds (komunity sdílení znalostí, např. „všichni iOS devs“) fungují i v Team Topologies – typicky jako neformální komunity nad rámec hlavní topologie. Hlavní rozdíl: Spotify Model byl popisem jednoho úspěšného podniku v určitém období; Team Topologies je obecný rámec s explicitními typy a interakcemi.'
- question: Vyplatí se Team Topologies v 50-člověké firmě?
  answer: 'Ano, ale ne v plné formě. 50-člověká firma odpovídá scénáři B (scale-up): typicky 4–6 stream-aligned týmů + 1 mini-Platform team (3–5 lidí). Žádný permanentní Enabling team, žádný Complicated-subsystem team (pokud nejste banka nebo ML startup). Hlavní hodnota Team Topologies v této velikosti je <em>jazyk</em>. Pokud začnete mluvit o „Platform team“ a „Stream-aligned team“, okamžitě se ukáže, kdo dělá co a co je ticket-fronta vs. self-service. Detail v <a href="#scenar-scaleup">scénáři B</a>.'
- question: Co dělat, když management nesouhlasí s reorganizací?
  answer: 'Tři možnosti, podle závažnosti. (1) <em>Postupný posun:</em> nedělejte reorganizaci najednou, ale ovlivňujte hranice „pod kapotou“ – hranice modulů v monorepu, code owners, samostatná nasazení. To eliminuje 30–50 % předávání i bez formální reorganizace. (2) <em>Pilot stream-aligned týmu:</em> přesvědčte management o jednom pilotním týmu (5–7 lidí) na 6 měsíců. Změřte DORA metriky před a po. Pokud pilot uspěje, máte case pro plnou reorganizaci. (3) <em>Diagnóza Westrum kultury:</em> pokud je organizace pathological/bureaucratic (sekce <a href="#westrum">05.09</a>), Team Topologies neuspěje ani s formální reorganizací. Zvážte změnu místa. Detail komunikace s CTO v <a href="#management">sekci 05.09</a>.'
- question: Jaký je vztah mezi Team Topologies a mikroservisy?
  answer: 'Team Topologies není o mikroservisech, ale mikroservisy bez Team Topologies obvykle vedou k distribuovanému monolitu. Mikroservis je <em>fyzická</em> hranice nasazení; stream-aligned tým je <em>organizační</em> hranice odpovědnosti. Ve zdravém stavu jsou izomorfní – 1 stream-aligned tým = 1 BC = 1 mikroservis (nebo modul v modulárním monolitu). Pokud máte 30 mikroservis a 5 týmů, nejste v mikroservisové architektuře. Jste v distribuovaném monolitu, kde každý tým „vlastní“ 6 služeb a žádná hranice nemá soudržného vlastníka. Kapitola o <a href="/architektonicke-styly">architektonických stylech</a> rozebírá detail.'
:::

## 05.11 Další četba a citované zdroje {#dalsi-cetba}

1. **Conway, M. E.** (1968). *How Do Committees Invent?* Datamation, 14(4), 28–31.
   [melconway.com](http://www.melconway.com/Home/Committees_Paper.html)

2. **Vernon, V.** (2013). *Implementing Domain-Driven Design.* Addison-Wesley. Kap. 2 (Domains, Subdomains, Bounded Contexts) a kap. 3 (Context Maps).
   [amazon.com](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)

3. **Skelton, M. & Pais, M.** (2019). *Team Topologies: Organizing Business and Technology Teams for Fast Flow.* IT Revolution Press.
   [teamtopologies.com](https://teamtopologies.com/book)

4. **Forsgren, N., Humble, J. & Kim, G.** (2018). *Accelerate: The Science of Lean Software and DevOps.* IT Revolution Press.
   [itrevolution.com](https://itrevolution.com/product/accelerate/)

5. **Westrum, R.** (2004). *A typology of organisational cultures.* Quality and Safety in Health Care, 13(suppl_2), ii22–ii27.
   [qualitysafety.bmj.com](https://qualitysafety.bmj.com/content/13/suppl_2/ii22)

6. **Evans, E.** (2003). *Domain-Driven Design: Tackling Complexity in the Heart of Software.* Addison-Wesley.

7. Související kapitoly: [subdomény](/subdomeny), [context mapping](/context-mapping), [architektonické styly](/architektonicke-styly), [anti-vzory](/anti-vzory).
