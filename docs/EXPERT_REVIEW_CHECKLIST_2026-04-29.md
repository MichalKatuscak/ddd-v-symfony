# Expert Review Checklist — 9 nových kapitol DDD průvodce

**Vytvořeno:** 29. dubna 2026
**Účel:** Strukturovaný checklist tvrzení, citací a definic, které
po automatizovaném auditu (lint, anchor audit, číslování kapitol) zůstávají
**bez ověření odbornou autoritou v doméně DDD/architektury/Symfony**.

Tento dokument není seznam chyb. Je to seznam **bodů, kde model
mohl udělat chybu, kterou nezachytí grep ani PHP lint** — typicky:

- nesprávné přisouzení výroku autoritě,
- zastaralé tvrzení o framework API,
- definice vzoru, která zní pravděpodobně, ale nesedí s kanonickým zdrojem,
- tvrzení o roku publikace nebo verzi knihy.

Reviewer by měl být **senior DDD praktik** se znalostí Symfony 6+/7+ a primárních
DDD zdrojů (Evans 2003, Vernon 2013, Khononov 2021, Newman 2021, Brandolini,
Skelton & Pais).

---

## Jak používat tento dokument

1. Reviewer prochází jednotlivé položky.
2. Pro každou položku zaznamená jeden ze tří stavů:
   - **OK** — tvrzení sedí, nepotřebuje korekci.
   - **CORRECT** — tvrzení je nepřesné, navržená korekce v poli „Pokud chyba".
   - **INVESTIGATE** — nejistota, vyžaduje další zdroj.
3. Pokud reviewer korekce doplní, autor je provede a označí položku „RESOLVED".

Pole **file:line** odkazují na stav kódu k 29. dubnu 2026 (commit po phase-6
consistency sweep + fix priority/Conway/EventStorming-paleta).

---

## Kapitola 03 — Subdomény (`subdomains.html.twig`)

### A1. Klasifikace Core/Supporting/Generic — atribuce
- **file:line** `subdomains.html.twig:70`
- **Tvrzení:** Eric Evans v *Domain-Driven Design* (2003), kapitola 14
  „Maintaining Model Integrity", zavádí rozdělení na Core/Supporting/Generic.
- **Co ověřit:** Evansova kniha kapitolu 14 nazývá *Maintaining Model Integrity*
  (to sedí), ale klasifikace Core/Supporting/Generic primárně **patří do
  kapitoly 15** *Distillation*. Otázka: má-li reviewer Evansovu knihu
  fyzicky, je atribuce na kapitolu 14 správná, nebo by mělo být 15?

### A2. Khononov a klasifikace subdomén
- **file:line** `subdomains.html.twig:78`
- **Tvrzení:** Vlad Khononov v *Learning Domain-Driven Design* (O'Reilly 2021),
  kapitola 1 „Analyzing Business Domains".
- **Co ověřit:** Sedí název kapitoly přesně tak, jak je v knize? Khononov
  je relativně mladá kniha s detaily v anglických názvech kapitol —
  ověřit doslova oproti tištěnému/PDF výtisku.

### A3. Pětibodový Core Domain test
- **file:line** `subdomains.html.twig:216`
- **Tvrzení:** Test je „kombinace heuristik z Khononova a Brandoliniho
  diskuse o Core Domain Charts".
- **Co ověřit:** Brandolini má skutečně publikovaný materiál o „Core Domain
  Charts" (ne Core Domain Charters)? Tento konkrétní výraz je správný?
  Reference [4] směřuje na eventstorming.com, ale tam tento materiál
  není snadno dohledatelný.

### A4. Vernon o Shared Kernel jako „nejnebezpečnějším vzoru"
- **file:line** `subdomains.html.twig:512`
- **Tvrzení:** „Vernon i Khononov se shodují, že shared kernel je nejnebezpečnější
  vzor v DDD a má se používat výjimečně."
- **Co ověřit:** Vernon má v IDDD pro Shared Kernel jednoznačně silné varování?
  Slovo „nejnebezpečnější" je silné — je to tvrzení podložené, nebo
  zveličení?

---

## Kapitola 04 — Context Mapping (`context_mapping.html.twig`)

### B1. „Osm vztahů" vs Evansův původní katalog
- **file:line** `context_mapping.html.twig:159, 170`
- **Tvrzení:** Kapitola katalogizuje 8 vztahů; nově doplněná poznámka
  uvádí, že Evans má devátý (Big Ball of Mud) probíraný samostatně.
- **Co ověřit:** Sedí, že Evansův katalog v kap. 14 obsahuje přesně
  tyto vzory: Partnership, Shared Kernel, Customer-Supplier, Conformist,
  Anticorruption Layer, Separate Ways, Open Host Service, Published
  Language, Big Ball of Mud (= 9)? Některé seznamy (Khononov) Customer
  a Supplier rozdělují — kolik vzorů přesně Evans uvádí?

### B2. Conformist přesná definice
- **file:line** `context_mapping.html.twig:662+ (sekce 04.06)`
- **Co ověřit:** Definice Conformistu — downstream přijímá upstream model
  „bez překladu" — sedí přesně? Některé prameny říkají „bez vlastního
  překladu, ale s možností odmítnutí". Ověřit oproti Evansovu textu.

### B3. OHS + PL kombinace
- **file:line** `context_mapping.html.twig:1014+, 1164+`
- **Co ověřit:** Tvrzení, že OHS a PL jsou často spárované (OHS = protokol,
  PL = formát zpráv), je dobře známé, ale zda přesný popis sedí s Evansovou
  knihou — a kde leží hranice mezi nimi.

### B4. Big Ball of Mud — Foote & Yoder
- **file:line** `context_mapping.html.twig:1521+`
- **Co ověřit:** Big Ball of Mud bývá atribuován Brian Foote & Joseph Yoder
  (1997, paper na PLoP). Je-li v textu zmíněna autorství, sedí?

---

## Kapitola 06 — Architektonické styly (`architectural_styles.html.twig`)

### C1. Hexagonal — Cockburn 2005
- **file:line** `architectural_styles.html.twig:312`
- **Tvrzení:** Alistair Cockburn publikoval *Hexagonal Architecture (Ports and
  Adapters)* v roce 2005.
- **Co ověřit:** Cockburn článek poprvé publikoval na své wiki kolem
  **2005 - 2006** (různé zdroje uvádějí rozdílná data). Některé zdroje
  uvádějí původní článek z **2005**, ale revize a popularizaci až **2008**.
  Jaké je správné datum? Existuje původní URL na alistair.cockburn.us?

### C2. Onion — Palermo 2008
- **file:line** `architectural_styles.html.twig:7 (meta_keywords)`, dále v textu
- **Tvrzení:** Onion Architecture autor Jeffrey Palermo, rok 2008.
- **Co ověřit:** Palermo má sérii blogpostů z **července 2008**
  (jeffreypalermo.com). Sedí rok i atribuce?

### C3. Clean Architecture — R. C. Martin 2012 / 2017
- **file:line** `architectural_styles.html.twig:7 (meta_keywords)`, kontext
  v sekci 06.05
- **Co ověřit:** Robert C. Martin publikoval „The Clean Architecture" jako
  blogpost v **srpnu 2012**, kniha *Clean Architecture* vyšla **2017**.
  Pokud kapitola udává jeden rok, je to ten správný?

### C4. Anemic Domain Model — Fowler 2003
- **file:line** `architectural_styles.html.twig:293`
- **Tvrzení:** Martin Fowler nazval anti-vzor *Anemic Domain Model* už
  v roce 2003.
- **Co ověřit:** Fowlerův bliki článek o AnemicDomainModel je z **20. listopadu
  2003**. Sedí přesně.

### C5. Vrstvená architektura — Fowler 2002 PoEAA
- **file:line** `architectural_styles.html.twig:126`
- **Tvrzení:** Fowler v PoEAA (2002) popisuje „Service Layer + Domain Model
  + Data Source Layer".
- **Co ověřit:** Tato formulace přesně vystihuje, jak Fowler třívrstvý
  pattern v PoEAA pojmenovává? Reviewer s knihou potvrzuje.

---

## Kapitola 11 — Outbox Pattern (`outbox_pattern.html.twig`)

### D1. Pat Helland — atribuce konceptu
- **file:line** `outbox_pattern.html.twig:5 (meta_description)`, dále v textu
- **Tvrzení:** Pat Helland je zmiňován v meta_description.
- **Co ověřit:** Konkrétní atribuce — Pat Helland psal o outboxu kde?
  („Idempotence Is Not a Medical Condition" — 2012) ALE samotný
  „Transactional Outbox" pojmenoval typicky **Chris Richardson** v
  *Microservices Patterns* (2018). Sedí kapitola s realitou?

### D2. Chris Richardson — Microservices Patterns kap. 3
- **file:line** `outbox_pattern.html.twig:182, 1924`
- **Tvrzení:** Outbox v knize Richardson, kapitola 3 a 4.
- **Co ověřit:** Microservices Patterns má outbox v **kapitole 3**
  „Interprocess Communication" — sedí. Kapitola 4 je „Managing
  transactions with sagas". Je outbox detailněji v 3 nebo 4?

### D3. Two Generals' Problem & FLP impossibility
- **file:line** `outbox_pattern.html.twig:312`
- **Tvrzení:** „Exactly-once delivery v distribuovaných systémech obecně
  neexistuje (viz Two Generals' Problem, FLP impossibility)."
- **Co ověřit:** Two Generals' a FLP impossibility říkají něco mírně jiného
  než „exactly-once delivery neexistuje". Two Generals = nelze garantovaně
  potvrdit doručení. FLP = consensus v asynchronní síti s i jediným pádem
  není deterministicky řešitelný. Naivní spojení obou pro „exactly-once"
  je legitimní, ale stojí za precizní formulaci.

### D4. Outbox lag „pod 1 sekundu při 100 ms polling"
- **file:line** `outbox_pattern.html.twig:272 (caption)`
- **Co ověřit:** Toto je zkušenostní tvrzení. Realistické? Reviewer
  potvrzuje z provozní praxe, nebo jde o příliš optimistický odhad?

### E5. Symfony Messenger transport `doctrine://default`
- **file:line** `outbox_pattern.html.twig:891-892, microservices_and_ddd:1022`
- **Co ověřit:** Doctrine transport DSN je `doctrine://default?queue_name=...`
  v Symfony Messenger 6/7. Sedí přesně? Některé verze používají odlišné
  parametry.

---

## Kapitola 12 — Méně známé taktické vzory (`lesser_known_patterns.html.twig`)

### E1. Specification Pattern — Evans + Fowler
- **file:line** `lesser_known_patterns.html.twig:169-170`
- **Tvrzení:** Evans (DDD 2003, kap. 9 „Making Implicit Concepts Explicit")
  + Fowler (ve stejném roce).
- **Co ověřit:**
  - Sedí kapitola 9 v Evansově knize jako „Making Implicit Concepts Explicit"?
    (Některé kapitoly Evansovy knihy nejsou číslované intuitivně.)
  - Fowlerův text o Specification je z roku **2003**, nebo později? Bývá
    cílen na *Specifications* (2003) co-authored s Eric Evans.

### E2. Domain Service — Evans kap. 5
- **file:line** `lesser_known_patterns.html.twig:760`
- **Tvrzení:** Evans kapitola 5 (DDD 2003).
- **Co ověřit:** Domain Service je v Evansově knize v **kapitole 5**
  „A Model Expressed in Software" jako součást stavebních prvků.
  Sedí?

### E3. Modules — Evans organization
- **file:line** `lesser_known_patterns.html.twig:5 (meta_description)`
- **Co ověřit:** Modules patří do kapitoly 5 (taktické vzory) Evansovy knihy.
  Atribuce sedí?

---

## Kapitola 14 — Event Storming (`event_storming.html.twig`)

### F1. Brandoliniho paleta barev (PO opravě)
- **file:line** `event_storming.html.twig:200-262 (tabulka)`
- **Stav po opravě:** Policy = lila / světle fialová; Read Model = zelená.
- **Co ověřit:** I po opravě může být reviewer schopen detailněji potvrdit:
  - **Aggregate**: kapitola používá „Žlutooranžová (gold)" — Brandolini
    podle DDD Crew cheatsheetu používá „big yellow" + termín se mění
    na *Constraint*. Co je doporučená praxe?
  - **External System**: kapitola má „Šedá / hnědá" — sedí?
  - **Hot Spot**: kapitola má „Růžová" otočená do kosočtverce — sedí
    s Brandoliniho konvencí (Pink, rotated)?

### F2. Vernon Domain-Driven Design Distilled (2016, kap. 7)
- **file:line** `event_storming.html.twig:161, 1500`
- **Co ověřit:** Vernon DDD Distilled (Addison-Wesley, 2016) má v **kapitole 7**
  Event Storming. Sedí číslo kapitoly?

### F3. Brandolini — italský konzultant + rok zavedení
- **file:line** `event_storming.html.twig:129`
- **Tvrzení:** Brandolini je italský konzultant.
- **Co ověřit:** Sedí (Alberto Brandolini je italský DDD konzultant). Není
  uveden konkrétní rok zavedení Event Stormingu — jeho původ se datuje
  kolem **2013** (na Domain-Driven Design Europe). Stálo by za zmínku?

### F4. „Past tense rule" — Brandolini terminologie
- **file:line** `event_storming.html.twig:278`
- **Tvrzení:** Brandolini to označuje jako „grammar discipline".
- **Co ověřit:** Toto je přesný citát od Brandoliniho? Nebo parafráze?

### F5. Hofer & Schwentner — Domain Storytelling
- **file:line** `event_storming.html.twig:7 (meta_keywords)`, kontext v textu
- **Co ověřit:** Stefan Hofer & Henning Schwentner jsou skutečně autoři
  *Domain Storytelling* (Addison-Wesley 2021). Atribuce sedí?

---

## Kapitola 15 — Conway's Law a Team Topologies (`team_topologies.html.twig`)

### G1. Conway 1968 (po opravě 1967 → 1968)
- **file:line** `team_topologies.html.twig:54, 70, 79, 89, 1187, 1248, 1287`
- **Stav po opravě:** Vše sjednoceno na 1968 (Datamation, duben 1968).
- **Co ověřit:** Tvrzení „Conway v roce 1968 publikoval tezi" je nyní
  konzistentní. Reviewer potvrdí, že esej *How Do Committees Invent?*
  je správně atribuován Datamation **14(4)**, **str. 28-31**, duben 1968.

### G2. Skelton & Pais — Team Topologies (2019, IT Revolution)
- **file:line** Multiple
- **Co ověřit:** *Team Topologies* (Matthew Skelton & Manuel Pais,
  IT Revolution, 2019). Sedí vydavatel, rok? Sedí konkrétní citace
  ze 4 typů týmů (Stream-aligned, Enabling, Complicated-Subsystem, Platform)?

### G3. Cognitive Load — typologie Sweller
- **file:line** `team_topologies.html.twig:737 a okolí`
- **Co ověřit:** Skelton & Pais přebírají kategorie cognitive load
  (intrinsic / extraneous / germane) z **John Sweller** (1988+).
  Atribuce a definice sedí?

### G4. Westrum's typology
- **file:line** `team_topologies.html.twig:1248`
- **Co ověřit:** Ron Westrum publikoval typologii organizačních kultur
  (pathological / bureaucratic / generative) v **2004**.
  Atribuce sedí v kontextu DORA + Team Topologies?

### G5. DORA metriky
- **file:line** `team_topologies.html.twig:1248`
- **Co ověřit:** DORA (DevOps Research and Assessment, Forsgren / Humble /
  Kim, *Accelerate*, 2018). Pokud kapitola tyto metriky uvádí
  konkrétně (deployment frequency, lead time, MTTR, change failure rate),
  jsou to čtyři kanonické?

---

## Kapitola 16 — Autorizace v DDD (`authorization_in_ddd.html.twig`)

### H1. Symfony Voter API (po opravě priority)
- **file:line** `authorization_in_ddd.html.twig:1118, 1155`
- **Stav po opravě:** Priority změněna na 7, vysvětlení opraveno.
- **Co ověřit:** Reviewer se Symfony znalostí potvrdí, že:
  - Firewall listener priority je v **Symfony 6.x/7.x stále 8**.
  - Priority 7 (= o 1 nižší) skutečně garantuje běh PO Firewall.
  - Není v nové verzi (8) změněno na něco jiného (např. priority 0)?

### H2. AccessDecisionManager strategie
- **file:line** Pokud je kapitola zmiňuje (sekce o Voterech)
- **Co ověřit:** Symfony 5.4+ má strategie: affirmative, consensus,
  unanimous, priority. Pokud kapitola některou popisuje detailně,
  sedí popis?

### H3. ACL Bundle status
- **file:line** N/A (nezmiňováno v textu, ale za zvážení)
- **Co ověřit:** Symfony ACL Bundle byl deprecated v 4.0, oddělen do
  samostatného balíčku `symfony/acl`. Pokud kapitola zmiňuje „ACL
  na agregátu", explicitně odlišuje od Symfony ACL Bundle?

### H4. Domain Exception jako autorizační mechanismus
- **file:line** Sekce 16.05 (Aggregate)
- **Co ověřit:** DDD knihy doporučují vyhazovat doménové exceptions, když
  invariant porušen. Kapitola používá tento pattern pro autorizaci na
  úrovni aggregate (= „cancellation window v aggregate, ne ve voteru").
  Je to Vernon-aligned přístup nebo alternativa?

### H5. Multi-tenancy s Doctrine SQLFilter
- **file:line** `authorization_in_ddd.html.twig:1163`
- **Co ověřit:** Tvrzení, že SQLFilter modifikuje pouze QueryBuilder
  a EntityManager::find — sedí přesně? Reviewer s aktuální verzí
  Doctrine ORM (3.x) potvrdí.

---

## Kapitola 17 — DDD a microservices (`microservices_and_ddd.html.twig`)

### I1. Newman Building Microservices 2nd ed. (2021)
- **file:line** `microservices_and_ddd.html.twig:107, 205, 311, 1508, 1591`
- **Co ověřit:** *Building Microservices, 2nd ed.* (Sam Newman,
  O'Reilly **2021**). Sedí rok? První vydání bylo 2015.

### I2. Richardson Microservices Patterns kap. 3 a 4
- **file:line** `microservices_and_ddd.html.twig:111, 695, 1597`
- **Co ověřit:** Konkrétní obsah kapitol Richardson knihy:
  - Kap. 2 — Decomposition strategies?
  - Kap. 3 — Interprocess communication?
  - Kap. 4 — Sagas?
  - Kap. 13 — Refactoring monolithic?
  Sedí čísla?

### I3. Modular monolith jako legitimní cíl
- **file:line** Sekce 17.02
- **Co ověřit:** Tvrzení, že modular monolith je legitimní DDD cíl
  (ne jen mezistupeň k microservices), je v souladu s konsensem komunity
  (Newman 2021 explicitně říká „monolith first")?

### I4. Strangler Fig — Fowler 2004
- **file:line** Pokud kapitola zmiňuje
- **Co ověřit:** Strangler Application pattern Martin Fowler popsal
  v **červnu 2004** na svém bliki. Atribuce a rok sedí?

### I5. Symfony Messenger AMQP transport
- **file:line** `microservices_and_ddd.html.twig:990, 1014, 1102`
- **Co ověřit:** Aktuální Messenger 7.x AMQP transport DSN syntaxe
  (`amqp://...`) sedí? Není už něco preferovanějšího (php-amqp / RabbitMQ)?

### I6. Distributed Monolith definice
- **file:line** Sekce 17.03
- **Co ověřit:** Definice „distributed monolith = microservices se
  sync coupling + shared DB + sync everywhere" — sedí s Newmanovým
  vymezením? Newman varuje před tímto anti-vzorem konkrétně v 2nd ed.

---

## Společné (cross-chapter)

### X1. Jednotnost terminologie
- **Co ověřit:** Některé pojmy mají více překladů. Ověřit, že napříč
  9 kapitolami se používá konzistentní volba:
  - Bounded Context vs ohraničený kontext
  - Aggregate root vs agregátní kořen
  - Domain Event vs doménová událost
  - Saga vs sága

### X2. Kódové ukázky — runtime
- **Co ověřit:** Automatický `php -l` říká „70/70 bloků prošlo syntakticky".
  Ale lint neověří:
  - Existence importovaných tříd v Symfony 8 (např.
    `Symfony\Component\Security\Core\...` — ne `Http\...`)
  - Správnost Doctrine atributů
  - Že volání metod má korektní signature
- **Doporučení:** Spustit reálnou kompilaci v sandboxovém Symfony 8 projektu
  s alespoň jedním reprezentativním snippetem z každé kapitoly.

### X3. Vnitřní cross-reference textem (mimo path())
- **file:line** Across files, especially v sekcích „Detail v kapitole X"
- **Co ověřit:** Po opravě 5 odkazů v `microservices_and_ddd.html.twig`
  by audit měl být čistý. Ověřit, že žádný další text neodkazuje na číslo
  kapitoly slovně bez `path()` linku.

### X4. JSON-LD `Article` headline shoda s `<h1>`
- **Co ověřit:** Pro každou novou kapitolu — zda hodnota
  `"headline":` v JSON-LD odpovídá vizuálnímu `<h1>` ve `_partials/article_head.html.twig`.
  Google Search Console rozdíly hlásí jako warning.

### X5. FAQ sekce — věcná správnost odpovědí
- **file:line** `_partials/faq.html.twig` includes ve všech 9 kapitolách
- **Co ověřit:** FAQ sekce nebyly v automatickém auditu hloubkově
  zkontrolovány. Reviewer projde otázky/odpovědi ve všech 9 kapitolách.

---

## Status sledování

| ID | Kapitola | Stav | Poznámka reviewera |
|----|----------|------|--------------------|
| A1 | 03 | TODO | |
| A2 | 03 | TODO | |
| A3 | 03 | TODO | |
| A4 | 03 | TODO | |
| B1 | 04 | TODO | |
| B2 | 04 | TODO | |
| B3 | 04 | TODO | |
| B4 | 04 | TODO | |
| C1 | 06 | TODO | |
| C2 | 06 | TODO | |
| C3 | 06 | TODO | |
| C4 | 06 | TODO | |
| C5 | 06 | TODO | |
| D1 | 11 | TODO | |
| D2 | 11 | TODO | |
| D3 | 11 | TODO | |
| D4 | 11 | TODO | |
| E1 | 12 | TODO | |
| E2 | 12 | TODO | |
| E3 | 12 | TODO | |
| F1 | 14 | TODO | |
| F2 | 14 | TODO | |
| F3 | 14 | TODO | |
| F4 | 14 | TODO | |
| F5 | 14 | TODO | |
| G1 | 15 | RESOLVED | Po opravě sjednoceno na 1968. |
| G2 | 15 | TODO | |
| G3 | 15 | TODO | |
| G4 | 15 | TODO | |
| G5 | 15 | TODO | |
| H1 | 16 | TODO | Po opravě priority na 7. |
| H2 | 16 | TODO | |
| H3 | 16 | TODO | |
| H4 | 16 | TODO | |
| H5 | 16 | TODO | |
| I1 | 17 | TODO | |
| I2 | 17 | TODO | |
| I3 | 17 | TODO | |
| I4 | 17 | TODO | |
| I5 | 17 | TODO | |
| I6 | 17 | TODO | |
| X1 | cross | TODO | |
| X2 | cross | TODO | |
| X3 | cross | TODO | |
| X4 | cross | TODO | |
| X5 | cross | TODO | |

---

## Kontext a omezení

Tento checklist generoval Claude Code (Anthropic, model Opus 4.7) jako
samokritický výstup po automatizovaném auditu. Položky jsou tedy
**hypotetické problémy, které model sám u sebe nevidí**, ale které
historicky odpovídají typickým chybám LLM-generovaného obsahu:

- **Plausibility hallucination** — model napíše smysluplně znějící
  atribuci výroku autoritě, ale výrok pochází od jiné autority.
- **Year drift** — rok publikace se posune o 1-2 roky vůči realitě.
- **Chapter number drift** — atribuce výroku do kapitoly knihy se
  posune o 1-2 čísla.
- **API drift** — popis frameworku odpovídá staré verzi, nikoliv aktuální
  (Symfony 6 → 7 → 8).

Pro kritická místa (atribuce, roky, kapitoly knih) by reviewer měl
mít fyzický nebo PDF přístup k primárním zdrojům, ne jen Wikipedia
nebo blogposty.
