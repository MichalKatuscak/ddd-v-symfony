# Design Spec: DDD a umela inteligence -- co rikaji autority

## Meta

- **Datum:** 2026-03-27
- **Typ:** Nova kapitola webu (route v DddController, prev/next navigace)
- **Rozsah:** ~5000+ slov, obsahly akademicky clanek
- **Cilova skupina:** Siroka vyvojarska komunita, objektivni prehled
- **Ton:** Akademicky, objektivni, jako ostatni clanky na webu
- **Jazyk:** Cestina

## Ucel

Clanek mapuje, co klicove autority softwaroveho inzenyrstvi rikaji o vztahu Domain-Driven Designu a umele inteligence. Neobhajuje ani nekritizuje -- prezentuje spektrum nazoru s citacemi, daty a kontextem, aby si ctenar mohl udelat vlastni nazor.

## Struktura clanku (tematicka)

Protiargumenty jsou integrovany prubezne do kazde sekce, nikoliv v samostatne sekci.

### 1. Uvod
- Proc je vztah DDD a AI aktualni (2024-2026)
- AI meni zpusob navrhovani a psani softwaru
- Otazka: jsou nektere architektonicke pristupy pro spolupraci s AI vhodnejsi?
- Co clanek mapuje, co ne (neni benchmark, neni tutorial)

### 2. Ubiquitous language jako rozhrani pro LLM
- **Evans (Explore DDD 2024):** Fine-tuned LLM trenovany na ubiquitous language konkretniho bounded contextu je levnejsi a presnejsi nez genericky model. Navrhl, ze vyvojari budou identifikovat NLP ulohy jako samostatne subdomeny.
- **Fowler:** DDD a DSL jako cesta k "rigoroznijsimu" promptovani LLM. Pripravit se na nedeterminismus.
- **Protivaha — DHH:** Prilisna formalizace jazyka je zbytecna rezie. Ruby je dostatecne citelne pro AI samo o sobe. "AI's preferred format" je Markdown, ne domenovy jazyk.
- **Kontext:** LLM ze sve podstaty pracuji s prirozenim jazykem — ubiquitous language je pokus o precizni podmnozinu prirozeneho jazyka.

### 3. Bounded contexts a kvalita generovaneho kodu
- **Vyzkumna data:** Skok z ~55 % na ~88 % production-ready kodu pri pouziti bounded contexts. Snizeni boundary violations z 35 % na <5 %. Redukce kontextu na 15-25 % codebase.
- **Nick Tune:** Nejaktivnejsi praktik na prunicku DDD-AI. Reverse engineering architektury pomoci Claude Code (ts-morph extraktor). Living docs exportovane jako kontext pro AI agenty. Kniha "Architecture Modernization" (Manning, 2024).
- **AI nastroje jako de facto bounded contexts:** Cursor .cursor/rules/, CLAUDE.md, Copilot Instructions — vsechny implementuji koncept "dej AI omezeny domenovy kontext" na urovni konfigurace.
- **Protivaha:** GitClear analyza (2024): AI-generovany kod ma 41% vyssi churn rate nez lidsky psany. Struktura pomaha, ale neresi vse. AI generuje "lokalne koherentni, ale architektonicky nekonzistentni" kod.

### 4. Testovani jako kontrolni mechanismus pro AI
- **Kent Beck:** TDD je "superpower" pro praci s AI agenty. Rozlisuje "augmented coding" (udrzuje kvalitu, testy, architekturu) vs "vibe coding" (AI jako magic solution generator). AI nema "taste" — pridava zbytecny kod.
- **Fowler:** AI je "dodgy collaborator" — produktivni v radcich kodu, ale nelze mu verit. Kazdy vystup AI = pull request vyzadujici review.
- **Protivaha:** DHH — "I can literally feel competence draining out of my fingers!" Nebezpeci, ze se vyvojar stane "project manager of a murder of AI crows" misto toho, aby sam programoval.
- **Kontext:** TDD a code review nejsou specificke pro DDD, ale DDD komunita je historicky silne propojena s temito praktikami.

### 5. AI v domenove komplexite vs. CRUD
- **Evans:** Tri kategorie zpracovani domeny: (1) hard-coded strukturovany model, (2) human-handled, (3) nova kategorie — LLM-supported. Nektere casti systemu "nikdy nezapadnou do strukturovanych modelu" a ty jsou kandidaty pro LLM.
- **Vernon:** LLM jako "fix suggester" — self-healing software, ktery automaticky vytvori pull request s opravou runtime vyjimek.
- **Microsoft:** Pragmaticky pristup v eShopOnContainers — ordering microservice pouziva DDD, catalog microservice je jednoduchy CRUD. Matching complexity to need.
- **Protivaha — DHH (Rails World 2025):** "My career is CRUD monkeying." Vetsina prace je cteni/zapis z databaze. Neresime cele problemy, ale krajime vse na kousky a jdeme zpet. CRUD monolith staci.
- **Kontext:** Otazka neni "DDD nebo CRUD" ale "kde je hranice komplexity, za kterou se DDD vyplati — a meni AI tuto hranici?"

### 6. Architektonicke nastroje a kontext pro AI
- **Cursor:** .cursor/rules/ s .mdc soubory pro architektonicky kontext. Cursor.directory — komunitni pravidla vcetne DDD patternu. Semanticke indexovani celeho repozitare.
- **GitHub Copilot:** Copilot Instructions repozitar obsahuje DDD a Clean Architecture sablony. Zpracovava kontext souboru, strukturu projektu, zavislosti.
- **Claude Code:** CLAUDE.md jako projektovy kontext — analogie k bounded context dokumentaci.
- **Akademicky vyzkum:** AWS KDD 2024 — "Domain-Driven LLM Development". arXiv 2025 — "Leveraging Generative AI for Enhancing Domain-Driven Software Design".
- **Protivaha:** Nastroje funguji i bez DDD. Jednoduchy, dobre napsany kod s jasnymi konvencemi muze byt pro AI stejne srozumitelny. Struktura neni totez co DDD.

### 7. Otevrene otazky a limity
- **Fowler:** LLM by mely prichazet s metrikami popisujicimi presnost. "Jake jsou tolerance nedeterminismu?" Stale se ucime.
- **Brandolini:** Opatrne zapojovani AI — kurzy Avanscoperta nyni kombinuji DDD s AI asistenty, ale bez velkolepych proklamaci.
- **Newman:** Distribuovane systemy jako "posledni moznost". Zda se AI meni tento kalkul, zatim jasne nerekl.
- **Dlouhodobe otazky:** Meni AI hranici, od ktere se DDD vyplati? Bude ubiquitous language standardni vstup pro AI nastroje? Jak se zmeni role architekta?

### 8. Zaver
- Synteza bez advocatniho zaveru
- Vetsina autorit (Evans, Vernon, Tune, Beck, Fowler) vidi synergii mezi DDD koncepty a AI nastroji
- Skepticke hlasy (DHH) pripominaji, ze jednoduchost a citelnost mohou byt dostatecne
- Stav diskuse v roce 2026: konvergence smerem k "struktura pomaha AI, ale neni jedina cesta"
- Prostor pro vlastni usudek ctenare

## Prurezove prvky

### Diagram
PlantUML diagram vizualizujici spektrum pozic autorit na ose "jednoduchost ← → struktura" a "skepticky ← → optimisticky" vuci AI+DDD synergii.

### Citace a zdroje
Kazda sekce obsahuje primarne citace s odkazy. Zdrojova sekce na konci clanku se vsemi referencemi:

- Evans, E. — Explore DDD 2024 Keynote (InfoQ report)
- Vernon, V. — Explore DDD 2024 (InfoQ report)
- Fowler, M. — "Preparing for AI's Nondeterministic Computing" (The New Stack)
- Fowler, M. — Pragmatic Engineer interview
- ThoughtWorks Technology Radar Vol. 33 (2025)
- Beck, K. — "Augmented Coding: Beyond the Vibes" (Substack)
- Beck, K. — Pragmatic Engineer interview on TDD and AI
- Heinemeier Hansson, D. — Rails World 2025 Keynote
- Heinemeier Hansson, D. — Lex Fridman Podcast
- Tune, N. — "Reverse Engineering Your Software Architecture with Claude Code" (O'Reilly Radar)
- Tune, N. — "Enterprise-wide Software Architecture as DDD Living Documentation" (Medium)
- GitClear — AI code churn analysis (2024)
- Cursor Documentation — Rules
- GitHub — Copilot Instructions for DDD
- AWS — KDD 2024 "Domain-Driven LLM Development"
- arXiv — "Leveraging Generative AI for Enhancing Domain-Driven Software Design" (2025)
- UnderstandingData — "DDD Bounded Contexts for LLMs"

### Kodove ukazky
Pouze tam, kde prirozene ilustruji bod — napr. kratky priklad ubiquitous language v kodu vs. genericky pojmenovani, ukazka .cursor/rules konfigurace. Zadne umele benchmarky.

## Technicka implementace

### Route
- Path: `/ddd-a-umela-inteligence`
- Route name: `ddd_ai`
- Controller action: `dddAi()` v `DddController.php`

### Template
- `templates/ddd/ddd-a-umela-inteligence.html.twig`
- Stejny styl jako ostatni kapitoly (base.html.twig, JSON-LD, breadcrumbs, ARIA, meta tagy)

### Diagram
- `templates/diagrams/` — novy adresar pro PlantUML spektrum diagram

### Navigace
- Zarazeni do CHAPTERS pole pro prev/next navigaci
- Umisteni: za existujici kapitoly (na konec nebo jako pokrocila kapitola)

### SEO
- JSON-LD: schema.org Article
- Meta description, keywords, og:*, twitter:*, canonical URL
- Breadcrumbs s schema.org markup
