# Studie: DDD a umělá inteligence – co říkají autority

- **Kapitola:** `content/chapters/ddd_ai.md` (č. „ai", kategorie Reference, 508 řádků)
- **Cesta:** /ddd-a-umela-inteligence
- **Typ kapitoly:** narativní (viz `docs/prompts/review-chapter.md`)
- **Datum studie:** 2026-09-04

> **Omezení této studie.** Rešerše proběhla bez fulltextového webového vyhledávání (`WebSearch`
> byl vyčerpán). Ověřovalo se přímým stažením URL, které kapitola uvádí, plus domén autorů
> (domainlanguage.com, avanscoperta.it, thoughtworks.com/radar, rubyonrails.org, exploreddd.com,
> dddeurope.com, github.com/ddd-crew, verraes.net). Výroky bez uvedeného zdroje, které se
> nepodařilo najít na doméně autora, jsou označeny **nedohledáno** – nikoli nepravdivé. Co je
> nutné doověřit v session s dostupným hledáním, je v sekci 8.

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| úvod | ř. 22–29 | rámuje otázku: pomáhá DDD v éře LLM, nebo přidává komplexitu | – | dvě otázky + deklarace „přehled pozic, ne obhajoba" |
| ai.01 Ubiquitous language jako rozhraní pro LLM | ř. 31–77 | Evans fine-tunoval LLM na UL jednoho BC; Fowler doporučuje DSL pro rigorózní prompty; DHH oponuje Markdownem a konvencemi | InfoQ 2024, TNS (Fowler), TNS + Rails World (DHH) | nejhustší sekce na atribuce; nejvíc nálezů |
| ai.02 Bounded contexts a kvalita generovaného kódu | ř. 79–150 | tabulka 55/88 %, 35/<5 %, 100/15–25 %; Tune a AI konfigurační soubory; GitClear code churn | understandingdata.com, O'Reilly Radar, VSM/GitClear | jediná „datová" sekce, stojí na jednom blogu |
| ai.03 Testování jako kontrolní mechanismus | ř. 152–212 | Beck augmented vs. vibe coding; Fowler „dodgy collaborator"; DHH ztráta kompetence | Substack, Pragmatic Engineer, TNS | tři citáty, tři různé úrovně doložení |
| ai.04 AI v doménové komplexitě vs. CRUD | ř. 214–265 | Evansova taxonomie hard-coded / human-handled / LLM-supported; Vernon fix suggester; eShopOnContainers; DHH „crud monkeys" | InfoQ, Lex Fridman | eShopOnContainers je mrtvý odkaz na živý projekt |
| ai.05 Architektonické nástroje a kontext pro AI | ř. 267–301 | formáty Cursor rules / copilot-instructions / CLAUDE.md; arXiv preprint; TW Radar 33; protiargument konvencí | arXiv, TW Radar | částečně duplikuje ai.02 |
| ai.06 Otevřené otázky a limity | ř. 303–345 | nedeterminismus bez metriky; Brandolini zdrženlivý; Newman se nevyjádřil; čtyři otevřené otázky | – (většinou bez zdroje) | nejpoctivější sekce v tónu, nejslabší v doložení |
| ai.07 Závěr | ř. 347–452 | tabulka spektra pozic sedmi autorů; syntéza; callout „hlavní závěry"; FAQ | – (souhrn výše) | tabulka spektra opakuje, co už bylo řečeno |
| ai.08 Zdroje a další čtení | ř. 454–508 | 12 odkazů ve třech skupinách | – | žádný odkaz na domainlanguage.com |

Kapitola je psaná dobře: střídá hlasy, staví DHH proti Evansovi, nesklouzává k nadšení
a u tabulky dat sama upozorňuje, že čísla nejsou z kontrolované studie. Problém leží jinde.
Titul slibuje „co říkají autority", takže kapitola žije a umírá na atribucích — a právě tam má
nejvíc slabin. Devět z dvanácti odkazů v ai.08 je sekundárních (novinový referát přednášky, blog
o podcastu, magazínový výtah z reportu), přičemž primární zdroje existují a jsou dostupné.
Kapitola dále dává velký prostor tématu, které do ní patří jen okrajově (kvalita AI generovaného
kódu obecně), a téměř žádný tomu, co je pro čtenáře knihy o DDD v Symfony nejužitečnější: jak
DDD vzory vypadají, když je jednou ze součástí systému LLM.

## 2. Kanonické zdroje k tématu

### 2.1 Eric Evans

Kapitola staví Evansovu pozici na jediném zdroji: novinovém referátu InfoQ z keynote na Explore
DDD, Denver, 12.–15. 3. 2024 [1]. Referát je autentický a jádro toho, co kapitola tvrdí, odpovídá
— detaily už ne (tabulka 2.6). Kapitola z něj ale vynechala Evansovu **explicitní výhradu
o platnosti**: „his thoughts must be considered in the context of when he was speaking, on March
14, 2024 […] a year from now, his comments may be irrelevant" [1]. Text z roku 2026 tak podává
dva roky staré výroky jako aktuální pozici.

Zásadnější je, že Evans mezitím publikoval **vlastní primární materiál**, který kapitola nezná:

- *AI Components for a Deterministic System* (24. 8. 2025) [12] — aplikace Domain Navigator
  klasifikující domény v cizí kódové bázi pomocí LLM. Evans zde formuluje rozlišení, které
  kapitole chybí a je použitelnější než cokoli z InfoQ referátu: **klasifikační úloha vs.
  modelovací úloha**. Klasifikace je opakovatelná a LLM v ní vyniká; modelování opakovatelné není
  a nemá „správnou" odpověď. Smíchané v jednom promptu dávají výstupy, které nelze mezi běhy
  porovnat. Řešení: nejdřív ustavit kanonickou taxonomii, teprve pak klasifikovat.
- *Context Mapping with an AI-based Component* (6. 1. 2026) [13] — context map systému, jehož
  komponentou je LLM. Závěry: **LLM je bounded context** (vlastní jazyk, vlastní model konzistence,
  vlastní kontrakty), **anticorruption layer je pro AI integraci nezbytný** (překlad mezi
  deterministickou aplikací a probabilistickým systémem není parsování JSONu), a kontext se musí
  jmenovat konkrétně — ne „LLM", ale „Claude Sonnet 3.5", protože modely nejsou zaměnitelné.
  Evans používá i Published Language (taxonomie NAICS) a Conformist a přiznává, že hranice mezi
  ACL a Conformistem je v reálném systému šedá.

Web jeho firmy uvádí jako **současné zaměření**: „helping organizations thoughtfully integrate AI
technologies like LLMs into domain-rich systems while preserving the design integrity that
delivers business value" [11]. Silnější a čerstvější doklad než dvouletý novinový referát.

### 2.2 Vaughn Vernon

Jediný Vernonův výrok v kapitole — LLM jako „fix suggester" ve vizi self-healing software —
odpovídá InfoQ referátu [1] přesně. Zdroj je ale sekundární a Vernon v něm vystupuje jako
diskutující, ne jako autor teze. Vlastní publikovaný text k DDD a AI se nedohledal.

### 2.3 Martin Fowler

Článek The New Stack [2] je referátem rozhovoru Gergelyho Orosze s Fowlerem pro podcast Pragmatic
Engineer. Citát „dodgy collaborator" tam je doslova, včetně redakční vsuvky „[pull request]" na
místě, kde kapitola píše „PR". Ověřená je i formulace „We're still learning how to do this."

Dvě nepřesnosti. Datace: kapitola uvádí rok 2024, článek má `datePublished 2025-12-28`. A medium:
kapitola mluví o „Fowlerových poznámkách", jde ale o referát cizího podcastu. Zdroj přitom jmenuje
**i DDD** — „Domain-driven design (DDD) and domain-specific languages may offer a way forward" [2].
Kapitola z té věty vypustila právě tu půlku, která je pro knihu relevantní.

### 2.4 Kent Beck

Rozlišení augmented vs. vibe coding je ověřené v Beckově vlastním textu [3] (25. 6. 2025) a teze
„TDD je superpower při práci s AI agenty" v shrnutí Pragmatic Engineer [4] (11. 6. 2025).

Týž Beckův text ale **vyvrací** klíčovou větu kapitoly. Kapitola píše, že testy jsou „jediným
mechanismem, který AI nemůže zfalšovat" (ř. 167). Beck v témž postu popisuje, na co si dával
pozor: „Any indication that the genie was cheating, for example by disabling or deleting
tests" [3]. Agent testy vypínal a mazal. Testy nejsou nefalšovatelné; jsou falšovatelné
způsobem, který jde detekovat. Slabší tvrzení, ale přesnější.

### 2.5 David Heinemeier Hansson

Oba citáty jsou ověřené [5][6]. Pozor ale: článek TNS je referátem **téhož** rozhovoru s Lexem
Fridmanem. Kapitola je uvádí jako dvě samostatná vystoupení („v sérii článků a přednášek DHH
popisuje…", ř. 192).

Kontext prvního citátu kapitola posouvá. DHH neříká „competence draining out of my fingers"
o intenzivním používání AI asistentů, ale o tom, že Cursor a Windsurf **zkusil a odmítl**:
„I don't let it drive my code. I've tried that — I've tried the Cursors and the Windsurfs, and
I don't enjoy that way of writing" [5]. V témž rozhovoru zároveň říká „I also use AI all day long".

Věcný podklad DHH pasáže v ai.01 (Ruby čitelné pro LLM, Markdown jako formát pro AI, ubiquitous
language jen pro enterprise) se v citovaných zdrojích nenachází vůbec. Ověřený je jediný dílčí
fakt, a to z jiného zdroje: **Rails 8.1 skutečně přidal Markdown rendering a release notes uvádějí
jako důvod AI** — „Markdown has become the lingua franca of AI" [7]. Fakt platí, atribuce k DHH
osobně a k Rails World 2025 je nedoložená.

### 2.6 Ověření výroků připsaných osobám

Legenda: **OK** = zdroj potvrzuje výrok · **OK\*** = zdroj potvrzuje jádro, kapitola posouvá
význam, rozsah nebo dataci · **NE** = zdroj tvrdí něco jiného, nebo výrok v jediném uvedeném
zdroji chybí · **?** = nedohledáno tímto průchodem.

| # | Osoba | Výrok (řádky kapitoly) | Zdroj v kapitole | Verdikt | Poznámka |
|---|---|---|---|---|---|
| A1 | Evans | citát „Because some parts of a complex system…" (40–44) | InfoQ [1] | **OK** | doslovná shoda |
| A2 | Evans | vytrénovaný LLM lze chápat jako bounded context (46–48) | InfoQ [1] | **OK** | „a trained language model is a bounded context" |
| A3 | Evans | několik fine-tuned modelů = silné oddělení zodpovědností (46–47) | InfoQ [1] | **OK** | „He sees this as a strong separation of concerns" |
| A4 | Evans | tým doladil LLM na UL jednoho BC, „výsledek byl překvapivě přesvědčivý" (33–38) | InfoQ [1] | **NE** | zdroj popisuje **návrh**, ne provedený fine-tuning; Evansovy skutečné experimenty byly prompt engineering s ChatGPT (NPC, s Reedem Berkowitzem) |
| A5 | Evans | specializovaný model levnější v provozu i přesnější (36–38) | InfoQ [1] | **OK\*** | zdroj: „fine-tuning makes an inexpensive model cheaper and faster"; „přesnější" je z jiné věty a jiného argumentu |
| A6 | Evans | NLP úlohy jako subdomény – klasifikace záměrů, extrakce entit, shrnutí (48–52) | InfoQ [1] | **OK\*** | zdroj mluví obecně o „tasks and subdomains that involve interpreting natural language input"; tři jmenované úlohy tam nejsou |
| A7 | Evans | taxonomie tří kategorií vč. „přesnost 80–90 % je přijatelná" (216–223) | InfoQ [1] | **OK\*** | tři kategorie ano; jejich definice a čísla ne – doplnila je kniha |
| A8 | Evans | *(chybí)* jeho vlastní výhrada o platnosti k 14. 3. 2024 | InfoQ [1] | **OK** | ve zdroji je, kapitola ji vynechává |
| A9 | Vernon | LLM jako „fix suggester", self-healing software, PR s opravou (232–237) | InfoQ [1] | **OK** | přesná parafráze |
| A10 | Fowler | citát „dodgy collaborator" (179–183) | TNS [2] | **OK\*** | citát doslova; kapitola datuje 2024, článek je z 28. 12. 2025 |
| A11 | Fowler | „stále se učíme" (309) | TNS [2] | **OK** | „We're still learning how to do this." |
| A12 | Fowler | „ve svých poznámkách zmiňuje DSL … pro rigorózní promptování" (56–58) | TNS [2] | **OK\*** | nejde o Fowlerovy poznámky, ale o referát podcastu; zdroj jmenuje **i DDD**, což kapitola vypustila |
| A13 | Fowler | „Pevný jazyk na vstupu znamená méně entropie na výstupu“ (58–60) | – | **OK\*** | jádro doloženo [20]: „A general-purpose language like Java offers lots of valid ways to express the same intent. A DSL strips the variation away.“ Autorem textu je ale **Unmesh Joshi**, host na martinfowler.com, ne Fowler |
| A14 | Beck | citát „In vibe coding…" (161–165) | Substack [3] | **OK** | doslova, 25. 6. 2025 |
| A15 | Beck | TDD obzvlášť cenné s AI agenty (155–156) | Pragmatic Engineer [4] | **OK** | „TDD is a 'superpower' when working with AI agents" |
| A16 | Beck | „testy jsou jediným mechanismem, který AI nemůže zfalšovat" (167) | – | **NE** | týž Substack: agent testy vypínal a mazal [3] |
| A17 | Beck | sám testuje méně věcí, testy jsou úmyslnější (171–173) | – | **NE** | Beckova vlastní esej [3] tvrdí opak: „In augmented coding you care about the code, its complexity, the tests, & their coverage“; jeho systémový prompt zní „Always follow the TDD cycle: Red → Green → Refactor“ a „Write the simplest failing test first“ |
| A18 | Beck | „od začátku roku 2024 se intenzivně věnuje“ (154–155) | – | **NE** | datace bez opory. Všechny dohledané Beckovy texty k AI jsou z r. 2025 a novější; [3] popisuje projekt jako čtyřtýdenní práci publikovanou 6/2025 |
| A19 | DHH | citát „competence draining out of my fingers" (195–197) | TNS [5] | **OK\*** | citát ano; kontext ne – DHH mluví o odmítnutí Cursoru/Windsurfu, ne o intenzivním používání |
| A20 | DHH | citát „crud monkeys" (249–254) | Lex Fridman [6] | **OK** | doslova |
| A21 | DHH | Rails World 2025 + TNS: Ruby čitelné pro LLM bez terminologie (62–64) | TNS [5] | **OK\*** | v TNS [5] ani v abstraktu keynote [21] není nic; doloženo ale v [6]: „Ruby has a much higher bandwidth of communication […] conveys so much more concept per character than most other programming languages do“, v kontextu práce s AI. **Opravit zdroj na [6] a formulaci na „hustota významu na znak“** |
| A22 | DHH | Markdown jako preferovaný formát pro AI (64–65) | TNS [5] | **NE** | není v [5], v [6] ani v abstraktu keynote [21]. Doložit lze jen Rails 8.1 (viz A24), ne DHH osobně |
| A23 | DHH | ubiquitous language užitečná pro enterprise, jinde stačí konvence (67–69) | – | **NE** | prohledány [5], [6] a [21]: „ubiquitous language“ ani „domain-driven design“ se u DHH nevyskytuje. Výrok je autorská konstrukce |
| A24 | – | Rails 8.1 přidal nativní Markdown rendering kvůli AI (65–66) | – | **OK** | ověřeno, ale jiným zdrojem: Rails 8.1 Release Notes [7] |
| A25 | Tune | Claude Code k reverznímu inženýrství architektury (124–126) | O'Reilly [8] | **OK** | článek z 6. 2. 2026 |
| A26 | Tune | ts-morph pro deterministickou extrakci vzorů (126–128) | O'Reilly [8] | **OK** | odkaz na navazující post |
| A27 | Tune | vidí bounded contexts jako rámec pro AI agenty, každý kontext má CLAUDE.md/Cursor rules (128–131) | O'Reilly [8] | **NE** | v článku 0× „bounded context", 0× CLAUDE.md, 0× Cursor; jediné výskyty „Domain-Driven Design" jsou v navigaci webu O'Reilly |
| A28 | Tune | AI nástroje si vybudovaly vlastní verzi bounded contextu (133–139) | – | **NE** | Tuneův vlastní blog tvrdí opak. Článek *Enforced application architecture for agents and humans* [22] má podtitul „Enforcing application architecture instead of relying on markdown files“ a otevírá větou: „One of the most frustrating parts of AI-generated code is that it does not follow architectural guidelines that are written in skill files, ADRs, and various other places in the repo.“ |
| A29 | Tune | praktici píší Cursor rules a CLAUDE.md jako BC dokumenty (278–280) | – | **NE** | zdrojový text dohledán na autorově webu [23] (Medium [9] je jeho reprint). Neobsahuje „bounded context“, „CLAUDE.md“ ani „Cursor“; jeho téma je extrakce DDD informací z kódu jako living documentation. Spolu s [22] jde proti tvrzení kapitoly |
| A30 | Tune | *(chybí)* jeho vlastní varování o nepřesnostech AI výstupu | O'Reilly [8] | **OK** | „there have been significant inaccuracies that I had to spot and correct" |
| A31 | GitClear | definice code churn + projekce zdvojnásobení 2024 vs. 2021 (141–146) | VSM [10] | **OK\*** | ve zdroji doslova, ale jde o **projekci** celorepozitářového churnu korelovaného s Copilotem, ne o měření „u AI generovaného kódu" |
| A32 | GitClear | citát „lokálně koherentní, ale architektonicky nekonzistentní" (146–147) | VSM [10] | **NE** | ve zdroji se nevyskytuje; zdroj mluví o porušování DRY, copy/paste a „itinerant contributor" |
| A33 | Phoenix | tabulka 55/88 %, 35/<5 %, 100/15–25 % (99–116) | understandingdata [14] | **OK\*** | čísla ve zdroji jsou; „~55 %/~88 %" tam ale znamená **accuracy**, ne „produkčně použitelný kód bez úprav" |
| A34 | Wiegand et al. | „preprint na arXiv z roku 2026" (282–284) | arXiv [15] | **OK\*** | na arXiv 28. 1. 2026, ale text je součástí sborníku Upper-Rhine AI Symposium **2024** |
| A35 | Wiegand et al. | „strukturovaný kontext vede k přesnějším výstupům než nestrukturovaný“ (284–286) | arXiv [15] | **NE** | práce takové srovnání vůbec nedělá. Hodnotí jediný postup – fine-tune Code Llama (4-bit, LoRA) na datech z reálných DDD projektů – a měří syntaktickou správnost generovaných JSON objektů. Kontrolní skupina s nestrukturovaným kontextem chybí |
| A36 | ThoughtWorks | vol. 33: legacy=Adopt, Context engineering=Assess, Anchoring=Assess (288–293) | Radar [16] | **OK\*** | k vol. 33 sedí; Context engineering je od vol. 34 (15. 4. 2026) v **Adopt** |
| A37 | Brandolini | vyjadřuje se zdrženlivě; Avanscoperta nemá workshop na DDD+AI (318–322) | – | **NE** | Avanscoperta nabízí „AI-powered Domain-Driven Design **with Alberto Brandolini**", Berlín 1.–4. 12. 2026 [17] |
| A38 | Brandolini | „EventStorming zůstává fundamentálně lidskou aktivitou“ (322–323) | – | **?** | eventstorming.com k AI nic neobsahuje; jeho veřejné vyjádření se nepodařilo dohledat ani tímto průchodem. Nepřímo: anotace workshopu [17] slibuje „combine the power of modern AI tools where they have the most impact, while still maximizing learning through hands-on activities“ a „balancing lo-fi, hands-on, and AI-powered approaches“ – tedy kombinaci, ne obranu lidské exkluzivity |
| A39 | Newman | nevyjádřil se; konzervativní k mikroservisům (325–330) | – | **?** | kapitola sama označuje za autorský odhad – to je korektní řešení |
| A40 | Microsoft | eShopOnContainers, moduly Ordering a Catalog (239–245) | – | **NE** | repozitář archivován 17. 11. 2023 a přesunut na `dotnet/eShop` [18] |

**Souhrn po doověření 2026-09-04:** OK 13 · OK\* 12 · NE 13 · ? 2.

Původní průchod skončil na OK 13 · OK\* 10 · NE 6 · ? 11. Druhý průchod s funkčním fulltextovým
hledáním rozhodl devět z jedenácti nedohledaných výroků: dva se potvrdily (A13, A21, oba s posunem
zdroje), sedm se vyvrátilo (A17, A18, A22, A23, A28, A29, A35). Nedohledané zůstávají dva – A38
(Brandolini) a A39 (Newman), přičemž A39 kapitola sama poctivě označuje za autorský odhad.

Třináct vyvrácených výroků ze čtyřiceti je pro kapitolu, jejímž tématem je „co říkají autority“,
zásadní nález. Tři z nich (A28, A29, A17) nejsou jen nedoložené – dohledané primární zdroje tvrdí
opak toho, co jim kapitola připisuje.

## 3. Stav praxe a posuny

Kapitola je datovaná `published: 2026-03-27`, `modified: 2026-07-08`. Za pět měsíců zestárly
čtyři konkrétní věci — to samo je nejsilnější argument pro přepis.

**Explore DDD běží dál a téma AI je jeho osou.** Ročník 2026 (Denver, 21.–25. 9. 2026) uvádí mezi
čtyřmi cíli konference „Examine how AI reshapes software design, modeling, and architecture" [19].
Kapitola staví Evansovu pozici na keynote z března 2024, dva ročníky zpět, aniž by to přiznala.

**Technology Radar se posunul o dvě vydání.** Kapitola cituje vol. 33 (11/2025). Vol. 34 vyšel
15. 4. 2026 a „Context engineering" v něm postoupil z Assess do **Adopt** [16]. Blipy „Using GenAI
to understand legacy codebases" a „Anchoring coding agents to a reference application" nesou na
webu Radaru upozornění „NOT ON THE CURRENT EDITION".

**Brandoliniho pozice se změnila.** Kapitola tvrdí, že se k propojení DDD a AI vyjadřuje zdrženlivě
a že Avanscoperta AI workshopy nezaměřuje na DDD. Aktuální katalog nabízí workshop „AI-powered
Domain-Driven Design with Alberto Brandolini" s popisem „Learn to combine Domain-Driven Design
with AI coding assistants" [17]. Tvrzení kapitoly je dnes nepravdivé.

**Změnil se i Rails.** Rails 8.1 přidal Markdown rendering s explicitním AI odůvodněním [7],
rubyonrails.org má nyní sekci „AI" a projekt „Agents on Rails" — vlastní benchmark agentních
nástrojů proti Rails úlohám. DHH tedy není protihlas, který AI nechce, ale někdo, kdo ji
do frameworku integruje a měří. Kapitola ho drží na pozici z července 2025.

Co za posledních dvanáct měsíců přibylo a v kapitole chybí úplně: **context engineering** jako
pojmenovaná disciplína (Radar, Adopt) a Evansovy dva vlastní články [12][13], které téma posouvají
z „co si autority myslí" do „jak se to modeluje".

## 4. Symfony / PHP specifika

Kapitola nemá jediný řádek kódu ani jednu zmínku o Symfony. U narativního přehledu pozic je to
obhajitelné, ale leží tu nevyužitý prostor, a to přesně tam, kam kapitola míří v ai.04 a ai.06:

- **Anticorruption layer nad LLM** je Evansův vlastní závěr [13] a v Symfony má konkrétní podobu:
  doménové rozhraní v `Domain/`, adaptér přes `symfony/http-client` v `Infrastructure/`, validace
  odpovědi proti povolené taxonomii, mapování na hodnotový objekt. Přesně ten vzor, který kniha
  učí v [Architektonických stylech](/architektonicke-styly) a [Implementaci DDD v Symfony 8](/implementace-v-symfony).
- **Nedeterministická komponenta v aplikační vrstvě.** Volání LLM je I/O s latencí, selháním
  a nestabilním výstupem — tedy Messenger handler s retry strategií, ne synchronní volání
  v controlleru. Kapitola v ai.06 sama píše, že „nasazení LLM do produkčního systému je
  distribuovaná závislost", a tam to končí.
- **PHP ekosystém:** `symfony/ai`, `php-llm/llm-chain`. Obojí je nutné před uvedením ověřit
  v session s webovým hledáním; tento průchod je neověřoval.

Kód jako takový do této kapitoly nepatří. Jeden odkaz na to, kde v knize ten vzor je, ano.

## 5. Sporné a chybně podávané body

**5.1 Numerická data ze single-author blogu.** Sekce ai.02 začíná větou „Existují data."
a slibuje „přehled dostupných zdrojů — od příspěvků praktiků přes konferenční záznamy po preprint
na arXiv". Celá tabulka je přitom z jediného blogpostu [14]; poznámka pod tabulkou to poctivě
přiznává, úvodní věta sekce slibuje něco jiného. **Doporučení:** čísla vypustit, nebo zredukovat
na jednu větu v próze. Tabulka jim dává váhu, kterou nemají.

**5.2 „Testy jsou jediným mechanismem, který AI nemůže zfalšovat."** Nejostřejší formulace
kapitoly a zároveň jediná, kterou její vlastní zdroj přímo popírá [3]. **Doporučení:** přepsat
na to, co Beck skutečně dělá — testy jsou levný signál odchylky, ale je nutné hlídat, jestli je
agent nevypíná nebo nemaže. Použitelnější rada než absolutní tvrzení.

**5.3 Evansův „experiment" vs. Evansův návrh.** Kapitola popisuje fine-tuning na UL bounded
contextu jako proběhlý týmový experiment s výsledkem. InfoQ [1] popisuje návrh; skutečné
experimenty z keynote byl prompt engineering s ChatGPT pro NPC ve hře. **Doporučení:**
přeformulovat na návrh a doplnit tím, co Evans skutečně provedl a publikoval [12][13].

**5.4 Fowlerova věta zbavená DDD.** Zdroj [2] říká, že cestou vpřed mohou být „domain-driven
design (DDD) and domain-specific languages". Kapitola cituje jen DSL. Je to jediné místo
v celé rešerši, kde uznávaná autorita explicitně spojuje DDD s prací s LLM ve větě, kterou lze
doslova citovat — a kapitola ho zahodila. **Doporučení:** citovat celé.

**5.5 Sekundární zdroje tam, kde existují primární.** Evans → InfoQ místo domainlanguage.com;
Beck → Pragmatic Engineer místo vlastního Substacku; Fowler → The New Stack místo podcastu, na
který TNS odkazuje; GitClear → Visual Studio Magazine místo původního reportu. Pořadí
důvěryhodnosti podle `_SABLONA.md` je opačné.

**5.6 Dvojí zdroj, který je jeden.** TNS o DHH [5] a transkript Lex Fridman [6] pokrývají tentýž
rozhovor z července 2025 (viz 2.5).

**5.7 Frontmatter `chapter_number: "ai"`.** Šablona `templates/_partials/article_head.html.twig`
(ř. 15) vypisuje `Kapitola {{ chapter_number }}`, čtenáři se tedy zobrazí „Kapitola ai". Ostatní
stránky kategorie Reference jsou Twig šablony bez `chapter_number`. **Doporučení:** odstranit
a nadpisy převést na tvar bez prefixu — anchory zůstávají, odkazy z FAQ i zvenčí drží.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | nepodložené | `ddd_ai.md:33–38` | Evansův „experiment" s fine-tuningem na UL je ve zdroji návrh, ne provedený pokus s výsledkem (A4) | přeformulovat na návrh; výsledek nepřipisovat |
| G2 | chybí | `ddd_ai.md:31–77` | Evansova výhrada, že jeho výroky platí k 14. 3. 2024 a za rok mohou být irelevantní | uvést; je to zároveň nejlepší obhajoba existence této kapitoly |
| G3 | chybí | `ddd_ai.md:31–77`, `454–508` | Evansovy vlastní články z 8/2025 a 1/2026 na domainlanguage.com [12][13] | nová sekce (viz P1-3); doplnit do zdrojů |
| G4 | nepodložené | `ddd_ai.md:62–69` | tři výroky připsané DHH (Ruby čitelné pro LLM, Markdown jako formát pro AI, UL jen pro enterprise) nejsou v citovaném zdroji (A21–A23) | doložit, nebo škrtnout a nechat jen ověřený Rails 8.1 fakt se správným zdrojem [7] |
| G5 | sporné | `ddd_ai.md:56–60` | Fowler „ve svých poznámkách"; kapitola vypustila DDD z jeho věty (A12) | opravit medium; citovat větu včetně DDD |
| G6 | nepodložené | `ddd_ai.md:81–121` | „Existují data" + „přehled dostupných zdrojů" pro obsah jednoho blogu (5.1) | přepsat úvod sekce; tabulku zrušit nebo degradovat na prózu |
| G7 | sporné | `ddd_ai.md:99–104` | „~55 %/~88 % produkčně použitelný kód bez úprav" – zdroj tam má accuracy (A33) | opravit popisek metriky, nebo řádek vypustit |
| G8 | nepodložené | `ddd_ai.md:128–131` | Tuneovi připsán DDD rámec, který v citovaném článku není (A27) | doložit z jiného Tuneova textu, nebo označit jako vlastní pozorování knihy |
| G9 | chybí | `ddd_ai.md:124–131` | Tuneovo vlastní varování o „significant inaccuracies" v AI výstupu [8] | doplnit; vyvažuje sekci a je z téhož zdroje |
| G10 | nepodložené | `ddd_ai.md:146–147` | uvozovkovaný citát GitClear „lokálně koherentní, ale architektonicky nekonzistentní" ve zdroji není (A32) | uvozovky zrušit a formulovat jako parafrázi knihy, nebo nahradit doslovným citátem ze zdroje |
| G11 | zastaralé | `ddd_ai.md:141–146` | GitClear projekce pro rok 2024, publikovaná v lednu 2024, citovaná v roce 2026 jako aktuální | ověřit, zda GitClear vydal data za 2024/2025; jinak formulovat výslovně jako dvouletou projekci |
| G12 | nepodložené | `ddd_ai.md:167` | „testy jsou jediným mechanismem, který AI nemůže zfalšovat" – Beckův vlastní text tvrdí opak (A16) | přepsat podle 5.2 |
| G13 | zastaralé | `ddd_ai.md:239–245` | eShopOnContainers archivován 11/2023, přesunut na `dotnet/eShop` (A40) | aktualizovat název a odkaz, nebo příklad z této kapitoly vypustit jako nesouvisející s AI |
| G14 | sporné | `ddd_ai.md:282–286` | arXiv preprint prezentován jako výzkum r. 2026 zkoumající strukturovaný kontext; jde o práci ze sborníku 2024 o fine-tuningu Code Llama (A34, A35) | opravit dataci i popis závěru, nebo odstavec vypustit |
| G15 | zastaralé | `ddd_ai.md:288–293` | TW Radar vol. 33; vol. 34 (4/2026) posunul „Context engineering" do Adopt (A36) | aktualizovat na vol. 34 a pojem context engineering pojmenovat jako samostatné téma |
| G16 | nepodložené | `ddd_ai.md:318–323` | Brandolini „zdrženlivý", Avanscoperta bez DDD+AI workshopu – dnes nepravdivé (A37) | přepsat podle [17]; Brandolini patří na jinou pozici ve spektru |
| G17 | nadbytečné | `ddd_ai.md:133–139` vs. `267–280` | pointa o CLAUDE.md / Cursor rules jako de facto bounded contextech dvakrát; ai.05 to sama přiznává („Zde zbývají detaily formátů") | sloučit do ai.02; z ai.05 udělat sekci o kontextovém okně a hranicích |
| G18 | mělké | `ddd_ai.md:303–345` | kontextové okno vs. bounded context – ústřední technická souvislost tématu – zmíněno jen jako řádek v tabulce (ř. 110–113) | rozvést: proč menší kontext znamená lepší výstup a kde ta úvaha selhává |
| G19 | chybí | celá kapitola | riziko, které patří do každého textu o LLM a kódu: model generuje **pravděpodobný** kód, ne správný; invariant agregátu je přesně to, co statistika neuhádne | nová podsekce (P1-4) |
| G20 | chybí | celá kapitola | druhý směr vztahu: DDD **jako** metoda pro stavbu AI aplikací (ACL nad modelem, LLM jako bounded context, Published Language jako taxonomie) | nová sekce (P1-3) |
| G21 | nadbytečné | `ddd_ai.md:349–400` + `424–441` | tabulka spektra pozic a callout „hlavní závěry" říkají potřetí totéž, co sekce a závěr | ponechat jednu z obou |
| G22 | sporné | `ddd_ai.md:14`, šablona `article_head.html.twig:15` | `chapter_number: "ai"` se vykresluje jako „Kapitola ai" (5.7) | odstranit z frontmatteru, nadpisy bez číselného prefixu, anchory beze změny |
| G23 | mělké | `ddd_ai.md` (odkazy) | jen tři odchozí odkazy do knihy; příchozí odkazy pouze z předmluvy a `migration_from_crud.md:775` | doplnit odkazy na `/context-mapping` (ACL, Published Language), `/architektonicke-styly`, `/subdomeny` |

## 7. Doporučení k přepisu

**P1-1 — Projít kapitolu výrok po výroku podle tabulky 2.6 a opravit šest položek „NE".**
Šest tvrzení připsaných jmenovaným lidem citovaný zdroj nepotvrzuje nebo přímo popírá (A4, A16,
A27, A32, A37, A40). Faktografická vada, ne stylistická; bez opravy nemá kapitola právo na titul
„co říkají autority".
*Odhad: rozptýlené opravy v ai.01–ai.06, cca 25 řádků.*

**P1-2 — Přepsat sekci ai.02 tak, aby nestála na jednom blogu.**
Tabulka tří metrik je nejviditelnější a nejhůř doložený prvek kapitoly. Nahradit ji dvěma větami
prózy, nebo vypustit a sekci postavit na doložitelném: Radar vol. 34, Tuneův O'Reilly článek
včetně jeho varování, GitClear se správně popsanou metrikou.
*Odhad: přepis sekce ai.02, ~70 řádků.*

**P1-3 — Nová sekce: DDD při stavbě systému, jehož součástí je LLM.**
Kapitola dnes odpovídá jen na „pomáhá DDD při generování kódu?". Druhý směr má primární
citovatelný zdroj přímo od Evanse [12][13]: LLM je bounded context, ACL je nezbytný, kontext se
pojmenovává konkrétním modelem, klasifikační úloha se odděluje od modelovací, taxonomie se řeší
jako Published Language. Nejtrvanlivější obsah, který kapitola může mít — stojí na vzorech,
které kniha už učí, a nezestárne s modelem.
*Odhad: nová sekce ~60 řádků, plus tři odkazy do kapitol knihy.*

**P1-4 — Doplnit odstavec o povaze rizika: model generuje pravděpodobný kód, ne správný.**
Riziko je dnes obalené v citátech (Fowler, DHH), ale mechanismus nikde nezazní. LLM predikuje
pravděpodobné pokračování; doménový invariant je z definice tvrzení o tom, co je nepřípustné,
i když by to bylo pravděpodobné. Tady leží spojnice mezi DDD a limitem generování.
*Odhad: nová podsekce v ai.03 nebo ai.06, ~20 řádků.*

**P2-1 — Vyměnit sekundární zdroje za primární** (viz 5.5). InfoQ referát [1] zůstává tam, kde je
jediným záznamem přednášky.
*Odhad: přepis ai.08 (~30 řádků) plus úpravy atribucí v textu.*

**P2-2 — Datovat všechny výroky a doplnit poznámku o platnosti.**
Každý výrok patří k nějakému roku (3/2024 Evans keynote, 6/2025 Beck, 7/2025 DHH, 11/2025 Radar,
12/2025 Fowler, 1/2026 Evans, 2/2026 Tune); kapitola datuje nedůsledně a jednou chybně (A10).
Na začátek patří věta, k jakému datu je stav zmapován — Evansova výhrada [1] je pro to předloha.
*Odhad: data u ~12 výroků, jeden nový odstavec v úvodu.*

**P2-3 — Sloučit duplicitu ai.02 / ai.05 a přesměrovat ai.05 na kontextové okno.**
Po sloučení (G17) se uvolní místo na vztah mezi hranicí bounded contextu a kontextovým oknem:
proč je menší kontext přesnější a kde ta úvaha přestává platit.
*Odhad: přepis ai.05, ~35 řádků.*

**P2-4 — Nahradit eShopOnContainers příkladem, který k tématu patří.**
Odstavec o Ordering vs. Catalog je platná úvaha o rozvrstvení komplexity, ale není o AI a odkazuje
na archivovaný repozitář (G13). Aktualizovat na `dotnet/eShop`, nebo nahradit Evansovým Domain
Navigatorem [12] — stejné rozlišení, a k AI.
*Odhad: přepis odstavce, ~8 řádků.*

**P3-1 — Odstranit `chapter_number: "ai"`** (viz 5.7). *Odhad: frontmatter + 8 nadpisů.*

**P3-2 — Zredukovat trojí shrnutí na jedno.** Tabulka spektra, závěrečná próza a callout „hlavní
závěry" nesou týž obsah třikrát (G21). *Odhad: škrt ~30 řádků.*

**P3-3 — Doplnit vnitřní prolinkování.** Kapitola má tři odchozí a dva příchozí odkazy. Po P1-3
přibudou přirozené cíle: `/context-mapping`, `/architektonicke-styly`, `/subdomeny`. Zpětně stojí
za zvážení odkaz z `/context-mapping` sem. *Odhad: 4–6 odkazů.*

## 8. Otevřené otázky pro autora

**Doověřeno 2026-09-04 v session s funkčním fulltextovým hledáním.** Původní seznam osmi bodů je
vyřízen, jeden zůstává otevřený. Verdikty jsou promítnuté do tabulky 2.6, zdroje do sekce 9.

1. **A21–A23 (DHH) – rozhodnuto.** Ruby a LLM: doloženo, ale jinde a jinak. V Lex Fridmanově
   podcastu [6] DHH říká „Ruby has a much higher bandwidth of communication […] conveys so much
   more concept per character than most other programming languages do“ a spojuje to s prací s AI.
   Argument je o hustotě významu na znak, ne o „čitelnosti bez terminologie“. Markdown (A22)
   a ubiquitous language (A23) nejsou v [5], [6] ani v anotaci keynote [21]. **Opravit A21 na
   citaci z [6], A22 a A23 vyřezat.**
2. **A17–A18 (Beck) – rozhodnuto, oba vyvráceny.** Beckova esej [3] říká opak: „In augmented coding
   you care about the code, its complexity, the tests, & their coverage“; jeho systémový prompt
   trvá na plném TDD cyklu. Datace „od začátku roku 2024“ nemá oporu – všechny dohledané texty jsou
   z roku 2025 a novější. **Obě pasáže přepsat.**
3. **A28–A29 (Tune) – rozhodnuto, oba vyvráceny, a je to nejtvrdší nález kapitoly.** Tuneova doména
   je nick-tune.me, ne nicktune.uk. Jeho článek [22] se jmenuje *Enforced application architecture
   for agents and humans* s podtitulem „Enforcing application architecture instead of relying on
   markdown files“ a otevírá konstatováním, že AI generovaný kód architektonická pravidla ze skill
   souborů a ADR prostě nedodržuje. Původní text za [9] je dohledatelný jako [23] a je o extrakci
   DDD informací z kódu, ne o CLAUDE.md jako bounded-context dokumentu. **Kapitola staví Tuneho do
   role zastánce teze, proti které argumentuje. Pasáž přepsat, nebo Tuneho použít jako protihlas.**
4. **A35 (Wiegand et al.) – rozhodnuto, vyvráceno.** Práce žádné srovnání strukturovaného
   a nestrukturovaného kontextu nedělá. Hodnotí jediný postup (fine-tune Code Llama, 4-bit, LoRA)
   a měří syntaktickou správnost generovaných JSON objektů. **Tvrzení odstranit.**
5. **A38 (Brandolini) – zůstává otevřené.** Vlastní vyjádření k AI a EventStormingu se nedohledalo
   ani podruhé. Nepřímý doklad je anotace jeho workshopu *AI-Powered Domain-Driven Design* [17]
   (Berlín, 1.–4. 12. 2026): „combine the power of modern AI tools where they have the most impact,
   while still maximizing learning through hands-on activities“. To je pozice kombinace, ne obrany
   lidské exkluzivity. **Doporučení: citovat anotaci workshopu a nedoplňovat vlastní odhad.**
6. **A13 (Fowler) – rozhodnuto, potvrzeno s posunem atribuce.** Myšlenka je doložená článkem
   *DSLs Enable Reliable Use of LLMs* [20]: „A general-purpose language like Java offers lots of
   valid ways to express the same intent. A DSL strips the variation away.“ Text staví na DDD
   a ubiquitous language, což je pro kapitolu použitelnější než dnešní formulace. **Autorem je ale
   Unmesh Joshi, ne Fowler** – článek jen vychází na martinfowler.com. **Přepsat atribuci a citovat [20].**
7. **GitClear – rozhodnuto.** Novější data existují a trend potvrzují. Report za 2025 [25]: podíl
   řádků spojených s refaktoringem klesl z 25 % (2021) pod 10 % (2024), klonované řádky 8,3 % →
   12,3 %, „copy/paste“ poprvé překonalo „moved“ kód. Report za 2026 [24] na 623 mil. změn: duplicita
   bloků +81 % proti roku 2023, copy/paste 15,7 % proti 3,8 % přesunutých řádků. **Nahradit projekci
   z ledna 2024 měřenými daty; kapitola tím získá silnější a čerstvější oporu.**
8. **Symfony ekosystém – rozhodnuto.** `php-llm/llm-chain` je na Packagistu označen jako
   **abandoned** s náhradou `symfony/ai-agent`; poslední vydání 0.25.0 je z 16. 7. 2025 [26].
   Symfony AI dnes existuje jako sada balíčků (`symfony/ai-platform`, `-agent`, `-bundle`, `-store`
   plus bridge balíčky pro OpenAI, Gemini, Ollama, Meta); samostatný balíček `symfony/ai` neexistuje.
   Shodná verze v0.13.0 z 30. 8. 2026 [27] – aktivní vývoj, ale stále série 0.x. **Do knihy patří
   jako zmínka s uvedením verze a poznámkou o 0.x, ne jako doporučená závislost.**
**Rozhodnutí, která rešerše udělat nemůže:**

9. **Má kapitola zůstat přehledem pozic, nebo se stát kapitolou o modelování?** Přehled stárne
   rychle a vyžaduje revizi dvakrát ročně; kapitola postavená na Evansově ACL vzoru [13] stárne
   pomalu a lépe zapadá do knihy. Doporučení studie: těžiště k modelování (P1-3), přehled pozic
   zkrátit na jednu sekci. Autor ale možná chce právě ten přehled jako vstupní bod z AI dotazů.
10. **Kolik prostoru dát DHH?** Dnes tři pasáže a řádek v tabulce, přičemž jeho argument je
    čtyřikrát tentýž („většina softwaru je CRUD"). Jedna pasáž by uvolnila místo pro G18–G20.
11. **Jmenovat konkrétní nástroje a modely?** Kapitola jmenuje Cursor (7×), CLAUDE.md (8×),
    Claude Code (6×), Copilot (6×), ChatGPT, ts-morph. Každé jméno je datum expirace. Alternativa:
    mluvit o „souborech s instrukcemi pro agenta" a jména uvést jednou v závorce.
12. **Jak často kapitolu revidovat?** Návrh: do frontmatteru přidat `reviewed:` s datem posledního
    ověření atribucí, do textu větu „stav ke dni X", a revizi zařadit dvakrát ročně proti tabulce
    2.6 — ta je pro to sestavená jako kontrolní seznam.
13. **Zůstává `ebook: false`?** Předmluva (ř. 102) to zdůvodňuje rychlým vývojem tématu.
    Po přepisu podle P1-3 by významná část kapitoly zestárla pomalu a do knihy by se hodila.

## 9. Bibliografie

### Ověřené zdroje

Všechny níže uvedené byly staženy přímým fetchem 2026-09-04.

- `[1]` Thomas Betts — *Eric Evans Encourages DDD Practitioners to Experiment with LLMs*, InfoQ, 18. 3. 2024.
  https://www.infoq.com/news/2024/03/Evans-ddd-experiment-llm/ — citát „hard-coded / human-handled /
  LLM-supported"; „a trained language model is a bounded context"; Vernonův „fix suggester"; Evansova výhrada
  o platnosti k 14. 3. 2024; skepse Chrise Richardsona a Jessicy Kerr.
- `[2]` Joab Jackson — *Martin Fowler on Preparing for AI's Nondeterministic Computing*, The New Stack, 28. 12. 2025.
  https://thenewstack.io/martin-fowler-on-preparing-for-ais-nondeterministic-computing/ — „dodgy collaborator";
  „We're still learning how to do this"; „Domain-driven design (DDD) and domain-specific languages may offer a way forward".
- `[3]` Kent Beck — *Augmented Coding: Beyond the Vibes*, Tidy First? (Substack), 25. 6. 2025.
  https://tidyfirst.substack.com/p/augmented-coding-beyond-the-vibes — definice vibe vs. augmented coding;
  „Any indication that the genie was cheating, for example by disabling or deleting tests".
- `[4]` Gergely Orosz — *TDD, AI agents and coding with Kent Beck*, The Pragmatic Engineer, 11. 6. 2025.
  https://newsletter.pragmaticengineer.com/p/tdd-ai-agents-and-coding-with-kent — přepis za paywallem, ověřeny
  takeaways („TDD is a superpower when working with AI agents").
- `[5]` David Cassel — *DHH on AI, Vibe Coding and the Future of Programming*, The New Stack, 27. 7. 2025.
  https://thenewstack.io/dhh-on-ai-vibe-coding-and-the-future-of-programming/ — referát téhož rozhovoru jako [6].
- `[6]` Lex Fridman — *DHH: Programming, AI, Startups, and Open Source*, transkript ep. 474, 2025.
  https://lexfridman.com/dhh-david-heinemeier-hansson-transcript/ — citát „crud monkeys".
- `[7]` *Ruby on Rails 8.1 Release Notes*, sekce 2.4 Markdown Rendering.
  https://guides.rubyonrails.org/8_1_release_notes.html — „Markdown has become the lingua franca of AI".
- `[8]` Nick Tune — *Reverse Engineering Your Software Architecture with Claude Code to Help Claude Code*,
  O'Reilly Radar, 6. 2. 2026. https://www.oreilly.com/radar/reverse-engineering-your-software-architecture-with-claude-code-to-help-claude-code/
  — text neobsahuje „bounded context", „CLAUDE.md" ani „Cursor"; obsahuje varování „there have been significant
  inaccuracies that I had to spot and correct".
- `[10]` David Ramel — *Coding on Copilot: 2023 Data Suggests Downward Pressure on Code Quality*, Visual Studio
  Magazine, 25. 1. 2024. https://visualstudiomagazine.com/articles/2024/01/25/copilot-research.aspx — definice churn
  a projekce zdvojnásobení; formulace „lokálně koherentní, ale architektonicky nekonzistentní" se v textu nevyskytuje.
- `[11]` *About Domain Language* (web Erica Evanse). https://www.domainlanguage.com/ — „Our current focus is on
  helping organizations thoughtfully integrate AI technologies like LLMs into domain-rich systems".
- `[12]` Eric Evans — *AI Components for a Deterministic System*, Domain Language, 24. 8. 2025.
  https://www.domainlanguage.com/articles/ai-components-deterministic-system/ — Domain Navigator; klasifikační vs.
  modelovací úloha; kanonická taxonomie před klasifikací.
- `[13]` Eric Evans — *Context Mapping with an AI-based Component*, Domain Language, 6. 1. 2026.
  https://www.domainlanguage.com/articles/context-mapping-an-ai-based-component/ — „LLMs are bounded contexts too";
  ACL jako nutnost; NAICS jako Published Language; Conformist; pojmenování kontextu konkrétním modelem.
- `[14]` James Phoenix — *DDD Bounded Contexts: Clear Domain Boundaries for LLM Code Generation*, understandingdata.com.
  https://understandingdata.com/posts/ddd-bounded-contexts-for-llms/ — osobní blog bez metodologie, datum publikace
  nezjištěno. Čísla 35 %, ~55 %, ~88 %, 100 %, 15–25 % ve zdroji jsou; „~55 %/~88 %" je tam popsáno jako accuracy.
- `[15]` Wiegand, G.-H.; Stepniak, F.; Baier, P. — *Leveraging Generative AI for Enhancing Domain-Driven Software
  Design*, arXiv:2601.20909, podáno 28. 1. 2026. https://arxiv.org/abs/2601.20909 — ověřen abstrakt, ne plný text.
  Poznámka arXiv: „Part of the Proceedings of the Upper-Rhine Artificial Intelligence Symposium 2024". Fine-tune
  Code Llama (4-bit, LoRA) na generování doménových JSON objektů.
- `[16]` Thoughtworks — *Technology Radar*: „Context engineering" (Assess 11/2025 → Adopt 4/2026), „Using GenAI to
  understand legacy codebases" (Assess 4/2024 → Trial 10/2024 → Adopt 11/2025), „Anchoring coding agents to
  a reference application" (Assess 11/2025). https://www.thoughtworks.com/radar/techniques/context-engineering
- `[17]` Avanscoperta — katalog workshopů, mj. „AI-powered Domain-Driven Design with Alberto Brandolini" (Berlín,
  1.–4. 12. 2026), „The Agentic Developer Workshop", „AI + Continuous Modernisation". https://www.avanscoperta.it/en/training/
- `[18]` *eShopOnContainers* — archivováno 17. 11. 2023, přesunuto na github.com/dotnet/eShop.
  https://github.com/dotnet-architecture/eShopOnContainers
- `[19]` *Explore DDD 2026*, Denver, 21.–25. 9. 2026. https://exploreddd.com/ — mezi cíli konference „Examine how
  AI reshapes software design, modeling, and architecture".

Doplněno druhým průchodem 2026-09-04 (fulltextové hledání):

- `[20]` Unmesh Joshi – *DSLs Enable Reliable Use of LLMs*, martinfowler.com, 14. 7. 2026.
  https://martinfowler.com/articles/llm-and-dsls.html – „A general-purpose language like Java offers lots of valid
  ways to express the same intent. A DSL strips the variation away. Giving the model a few examples is enough to
  reliably generate the correct syntax.“ Text explicitně staví na DDD a ubiquitous language. **Autorem je Joshi,
  ne Fowler** – článek vychází na Fowlerově webu jako hostovaný.
- `[21]` *Rails World 2025 – Opening Keynote*, rubyonrails.org.
  https://rubyonrails.org/world/2025/day-1/david-hansson – anotace zní celá takto: „DHH will kick off the third
  edition of Rails World in Amsterdam with an Opening Keynote highlighting what is new in Rails today, and where
  the framework is headed tomorrow.“ Žádná zmínka o LLM, Markdownu ani ubiquitous language.
- `[22]` Nick Tune – *Enforced application architecture for agents and humans*, nick-tune.me, 13. 8. 2026.
  https://nick-tune.me/blog/2026-08-13-enforced-application-architecture-for-agents-and-humans/ – podtitul
  „Enforcing application architecture instead of relying on markdown files“; tagy „ai ddd architecture typescript“.
  Celý text 0× „bounded context“, 0× „CLAUDE.md“, 0× „Cursor“.
- `[23]` Nick Tune – *Enterprise-wide Software Architecture as DDD Living Documentation*, nick-tune.me, 26. 10. 2025.
  https://nick-tune.me/blog/2025-10-26-enterprise-wide-software-architecture-as-ddd-living-document/ – původní
  vydání textu, jehož reprint je [9]. Téma: agregace `architecture.json` napříč doménami do jednoho modelu systému
  (princip „one model, many views“ ze Structurizru). 0× „bounded context“, 0× „CLAUDE.md“.
- `[24]` GitClear – *The Maintainability Gap: AI Code Quality in 2026*, 1/2026.
  https://www.gitclear.com/the_ai_code_quality_maintainability_gap – 623 mil. analyzovaných změn 2023–2026;
  duplicita bloků 40,3 → 73,0 na milion změněných řádků (+81 %); copy/paste 15,7 % vs. přesunuté řádky 3,8 %
  za 1. pol. 2026.
- `[25]` GitClear – *AI Copilot Code Quality: 2025 Look Back at 12 Months of Data*, 2/2025.
  https://www.gitclear.com/ai_assistant_code_quality_2025_research – podíl řádků spojených s refaktoringem klesl
  z 25 % (2021) pod 10 % (2024); klonované řádky 8,3 % → 12,3 %; „copy/paste“ poprvé v historii měření překonalo
  „moved“ kód.
- `[26]` Packagist – `php-llm/llm-chain`. https://packagist.org/packages/php-llm/llm-chain – balíček je označen
  jako **abandoned** s náhradou `symfony/ai-agent`; poslední vydání 0.25.0 z 16. 7. 2025.
- `[27]` Packagist – komponenty Symfony AI (`symfony/ai-platform`, `-agent`, `-bundle`, `-store` a bridge balíčky).
  Shodná verze v0.13.0 z 30. 8. 2026; samostatný balíček `symfony/ai` neexistuje. Aktivní vývoj, ale stále
  série 0.x, tedy bez záruky zpětné kompatibility.

### Neověřené / nedohledané

Po druhém průchodu zbývají tři položky.

- **Alberto Brandolini k AI a EventStormingu** – vlastní vyjádření se nedohledalo ani podruhé. eventstorming.com
  k tématu mlčí, jeho workshop na DDD Europe 2026 je klasický *EventStorming Master Class* bez AI. Nepřímý doklad
  je anotace workshopu [17] (Berlín, 1.–4. 12. 2026), která mluví o kombinaci lo-fi a AI přístupů. Tvrzení A38
  zůstává nedoloženo a doporučení studie je nenahrazovat je jiným odhadem, ale citovat anotaci workshopu.
- **Vlastní publikovaný text Vaughna Vernona k DDD a AI** — nedohledán; kapitola cituje jen jeho poznámku z [1].
- **Sam Newman k AI** – nedohledáno. Kapitola to sama uvádí jako autorský odhad, což je korektní řešení.

Vyřešeno druhým průchodem, ponecháno pro dohledatelnost:

- `[9]` Medium reprint Tuneova textu vracel HTTP 403. Původní vydání je na autorově webu jako [23] a je čitelné.
  Doména **nicktune.uk** z prvního průchodu neexistuje; správná je **nick-tune.me**.
- **GitClear za 2024/2025** – dohledáno, viz [24] a [25].
- **`symfony/ai`, `php-llm/llm-chain`** – dohledáno, viz [26] a [27].
- **Vlad Khononov, Mathias Verraes, Matthias Noback, Kevlin Henney k DDD a AI** – nedohledáno ani podruhé.
  Absence těchto jmen v kapitole tedy není nález.

> **Metodická poznámka po druhém průchodu.** První průchod nechal jedenáct výroků nedohledaných, protože bez
> fulltextového hledání nelze najít výrok, jehož URL neznáme. Druhý průchod devět z nich rozhodl a poměr dopadl
> nepříznivě: sedm vyvráceno, dvě potvrzena. To potvrzuje, že „nedohledáno“ nebyl neutrální stav – u výroků bez
> uvedeného zdroje šlo většinou o autorskou konstrukci, ne o citaci.
>
> Tři případy jsou horší než nedoložené tvrzení, protože dohledaný zdroj říká opak: Beck o testování (A17), Tune
> o markdown souborech jako nositelích architektury (A28, A29). Kapitola tyto autory používá jako oporu pro tezi,
> proti níž oni sami argumentují.

### Doověřeno devátým kolem (2026-09-05)

**Pozor na tuto kapitolu při automatických kontrolách.** Jako jediná v knize používá
odkazy ve tvaru `<a href="…">` místo markdownu, takže **vypadává ze všech skriptů**, které
hledají `](http` nebo `[[N]](url)`. Devatenáct jejích odkazů se poprvé prověřilo až zvlášť
(všechny vracejí 200). Kdo bude psát další kontrolu, musí zahrnout i `href="…"`.

**Ověřeno proti primárním zdrojům — obě Evansovy články existují a tvrzení sedí:**

| Tvrzení kapitoly | Výsledek |
|---|---|
| *AI Components for a Deterministic System*, srpen 2025 | článek existuje, datum **24. 8. 2025** |
| aplikace klasifikuje domény v cizí kódové bázi pomocí LLM | ano, na příkladu OpenEMR |
| rozlišení klasifikační vs. modelovací úloha | ano (*„mixed a classification task … with a modeling task"*) |
| taxonomie NAICS | ano, v obou článcích |
| *Context Mapping with an AI-based Component*, leden 2026 | článek existuje, datum **6. 1. 2026** |
| LLM jako bounded context, ACL, pojmenování konkrétním modelem, Published Language | všechny čtyři body v článku jsou |
| hranice mezi ACL a Conformistem je šedá | ano — *„This is more like Conformist behavior. The Domain Navigator is adopting Claude's language and concepts, not translating away from them."* |

**OPRAVENO — jméno aplikace připsáno špatnému článku.** Kapitola psala, že Evans v srpnovém
článku „popisuje aplikaci Domain Navigator". Srpnový text aplikaci popisuje, ale **jméno
*Domain Navigator* v něm není ani jednou** — dává jí ho až navazující článek z ledna 2026
(*„The application, »Domain Navigator«, identifies the business domains being addressed in
a code-base."*). Přeformulováno.

**Poznámka k dohledávání:** správná URL druhého článku je
`/articles/context-mapping-an-ai-based-component/` — bez slova „with", ačkoli titulek ho má.
Odhad podle titulku vrací 404.
