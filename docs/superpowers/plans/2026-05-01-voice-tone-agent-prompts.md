# Voice/Tone Agent Prompts — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vytvořit tři soubory (CLAUDE.md sekce + dva prompt soubory), které standardizují voice/tón a faktickou verifikaci pro psaní i revizi kapitol DDD průvodce.

**Architecture:** CLAUDE.md dostane sekci se stálými zásadami. Adresář `docs/prompts/` bude obsahovat dva dedikované prompt soubory pro dva odlišné módy práce (revize vs. psaní). Žádná logika, žádný kód — pouze textové instrukce pro agenty.

**Tech Stack:** Markdown, Twig (šablony průvodce), git

---

## Přehled souborů

| Soubor | Akce | Zodpovědnost |
|---|---|---|
| `CLAUDE.md` | Upravit | Přidat sekci `## Voice, tón a jazyk` na konec souboru |
| `docs/prompts/review-chapter.md` | Vytvořit | Kompletní instrukce pro revizi existující kapitoly (5 průchodů) |
| `docs/prompts/write-chapter.md` | Vytvořit | Kompletní instrukce pro psaní nové kapitoly (4 fáze) |

---

## Task 1: Přidat sekci Voice/tón do CLAUDE.md

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Krok 1: Přečti aktuální CLAUDE.md**

  Ověř, že soubor končí sekcí `## Routing` a neobsahuje žádnou sekci o voice nebo tónu.

  ```bash
  tail -10 CLAUDE.md
  ```

  Očekávaný výstup: poslední řádky obsahují text o Czech slugs, bez žádné sekce Voice.

- [ ] **Krok 2: Přidej sekci na konec CLAUDE.md**

  Přidej přesně tento obsah na konec souboru (za existující text):

  ```markdown

  ## Voice, tón a jazyk

  Platí vždy — při každé editaci i psaní nového obsahu.

  ### Hlas průvodce

  Průvodce mluví jako zkušený praktik, který věci dobře zná a nebojí se říct názor. Neprodává, nevybízí, nenadsazuje. Tvrzení jsou přímá a podložená. Čtenář se cítí respektován jako profesionál.

  ### Pravidla věty

  - Věty: krátké až střední (do ~25 slov). Dlouhé věty rozdělit.
  - Věta říká jednu věc. Pokud říká dvě, rozdělit.
  - Žádný odstavec nezačíná „Je důležité...", „V rámci...", „Je třeba poznamenat...".

  ### Zakázáno — marketing a hype

  | Vzor | Problém |
  |---|---|
  | mocný, výkonný, elegantní, robustní | vágní přídavná jména bez obsahu |
  | revoluční, průlomový, game-changer | nadsázka |
  | moderní, cutting-edge, state-of-the-art | časově nestabilní, prázdné |
  | perfektní, ideální, optimální | bez zdůvodnění jsou lži |
  | bezproblémový, hladký, seamless | marketingový jazyk |
  | jednoduše, snadno, rychle | podceňuje čtenářův kontext |
  | „posune váš projekt na další úroveň" | hype bez obsahu |
  | „plně využít potenciál" | PR fráze |
  | „nová éra", „nový přístup k..." | inflační superlativy |
  | best practice (bez dalšího) | overused — nahradit konkrétním popisem |

  ### Zakázáno — výplň a filler

  | Vzor | Náhrada |
  |---|---|
  | „je důležité si uvědomit, že" | smazat, věc říct přímo |
  | „hraje klíčovou roli" | konkrétní sloveso (zajišťuje / umožňuje / brání) |
  | „stojí za zmínku, že" | smazat |
  | „je třeba poznamenat, že" | smazat |
  | „jak jsme již zmínili" | odkaz na sekci, nebo smazat |
  | „v rámci" | nahradit „v", „při", „pro" |
  | „klíčový" bez konkrétního obsahu | smazat nebo specifikovat |
  | „s ohledem na" | „protože", „vzhledem k" |
  | „co se týče X" | začít rovnou větou o X |
  | „v neposlední řadě" | smazat |
  | „samozřejmě", „pochopitelně", „logicky" | smazat |
  | „zcela", „naprosto", „absolutně" | smazat nebo zdůvodnit |
  | „jinými slovy" (opakovaně) | smazat, přeformulovat přímo |
  | „celkově vzato", „shrnuto" | nahradit konkrétním shrnutím |
  | „není pochyb o tom, že" | smazat |
  | „jak víme" | smazat |

  ### Typografie a forma

  - Em dash (—) zakázán → en pomlčka (–) s mezerami, nebo přeformulovat
  - Anglické uvozovky "" zakázány → české „"
  - Vykat — nikdy tykat
  - „Zde" nikdy „Tady"
  - „průvodce" nikdy „kurz" ani „tutoriál"
  - Žádné osobní komentáře autora („z mé zkušenosti", „překvapilo mě")

  ### Prompt soubory

  Pro komplexní úlohy použij dedikované prompt soubory:

  - `docs/prompts/review-chapter.md` — revize existující kapitoly (voice, jazyk, fakta, konzistentnost)
  - `docs/prompts/write-chapter.md` — psaní nové kapitoly od začátku
  ```

- [ ] **Krok 3: Ověř přidání**

  ```bash
  grep -n "Voice, tón" CLAUDE.md
  ```

  Očekávaný výstup: řádek s `## Voice, tón a jazyk` na konci souboru.

- [ ] **Krok 4: Commitni**

  ```bash
  git add CLAUDE.md
  git commit -m "docs(claude): přidat sekci Voice, tón a jazyk"
  ```

---

## Task 2: Vytvořit docs/prompts/review-chapter.md

**Files:**
- Create: `docs/prompts/review-chapter.md`

- [ ] **Krok 1: Vytvoř adresář**

  ```bash
  mkdir -p docs/prompts
  ```

- [ ] **Krok 2: Vytvoř soubor s tímto přesným obsahem**

  Soubor `docs/prompts/review-chapter.md`:

  ````markdown
  # Prompt: Revize kapitoly

  Tento prompt řídí kompletní revizi jedné kapitoly DDD průvodce.

  **Spuštění:** „Použij docs/prompts/review-chapter.md na templates/ddd/<soubor>.html.twig"

  ---

  ## Kontext

  Průvodce je odborná příručka o DDD v Symfony. Čtenář je zkušený PHP vývojář.
  Hlas průvodce: zkušený praktik, přímý, věcný, bez marketingu. Podrobná pravidla viz CLAUDE.md sekce „Voice, tón a jazyk".

  ---

  ## Průchod 1 — Voice & tón

  Přečti celý soubor. Pro každé porušení pravidel z CLAUDE.md vytvoř záznam:

  ```
  [V-N] Řádek <číslo>
  Originál: <původní text>
  Návrh:    <opravený text>
  Důvod:    <konkrétní pravidlo>
  ```

  Co hledat:
  - Všechna slova a fráze ze seznamu „Zakázáno" v CLAUDE.md
  - Věty přes 25 slov (označit číslo řádku a délku)
  - Em dash (—), anglické uvozovky, „Tady" místo „Zde"

  ---

  ## Průchod 2 — Jazyková kvalita

  Zkontroluj přirozenost češtiny. Pro každý nález:

  ```
  [J-N] Řádek <číslo>
  Originál: <původní text>
  Návrh:    <opravený text>
  Důvod:    <pasivum | nominalizace | germanismus | anglicismus | registr>
  ```

  Co hledat:
  - Pasivní konstrukce tam, kde jde použít aktivum bez ztráty smyslu
  - Nominalizace: „provedení implementace" → „implementovat", „zajištění konzistentnosti" → „zajistit konzistentnost"
  - Germanismy: „z důvodu toho, že" → „protože", „na základě toho" → „proto"
  - Anglicismy mimo technické termíny (technické termíny jako „repository", „event sourcing" jsou v pořádku)

  ---

  ## Průchod 3 — Faktická verifikace

  Pro každé konkrétní tvrzení proveď webové vyhledávání a ověř je z důvěryhodného zdroje.

  Zdroje v pořadí důvěryhodnosti:
  1. martinfowler.com
  2. Oficiální dokumentace Symfony (symfony.com/doc)
  3. Weby autorů: vlad.gg, eventstorming.com, teamtopologies.com
  4. Google Books preview (pro citace z knih)
  5. ACM Digital Library

  Co ověřit:
  - Definice a atribuce DDD vzorů (kdo je zavedl, kde, kdy)
  - Přesné názvy kapitol v citovaných knihách (Evans DDD 2003, Vernon IDDD 2013, Khononov LDDD 2021)
  - Rok vydání a nakladatel každé citované publikace
  - Verze Symfony API v kódových ukázkách (ověřit na symfony.com/doc)
  - Pravopis jmen autorů

  Pro každé ověřené tvrzení:

  ```
  [F-N] Řádek <číslo>
  Tvrzení:  <text tvrzení>
  Stav:     OK | OPRAVIT | NEJISTÉ
  Zdroj:    <URL nebo bibliografický záznam>
  Návrh:    <oprava — vyplnit pouze pokud stav=OPRAVIT>
  ```

  ---

  ## Průchod 4 — Konzistentnost s ostatními kapitolami

  Přečti ostatní šablony v `templates/ddd/`. Pro každý klíčový termín z aktuální kapitoly ověř, zda je definován stejně:

  ```
  [K-N] Termín: <termín>
  Definice zde:   <jak je definován v aktuální kapitole>
  Definice jinde: <jak je definován v jiné kapitole> (soubor:řádek)
  Rozpor:         <popis rozporu>
  Návrh:          <jak sjednotit>
  ```

  Zkontroluj také:
  - Čísla kapitol v odkazech (`href="#..."`) sedí s obsahem cílové sekce
  - Kotvy (`id="..."`) odkazované z jiných šablon existují v tomto souboru

  ---

  ## Průchod 5 — Výstupní report

  Vypiš kompletní report v tomto formátu:

  ```markdown
  # Revize: <název souboru>
  Datum: <datum>

  ## Voice/tón — <N> nálezů
  <záznamy [V-N]>

  ## Jazyk — <N> nálezů
  <záznamy [J-N]>

  ## Fakta — <N> tvrzení (OK: X | OPRAVIT: Y | NEJISTÉ: Z)
  <záznamy [F-N]>

  ## Konzistentnost — <N> nálezů
  <záznamy [K-N]>

  ---
  Celkem nálezů vyžadujících akci: <součet OPRAVIT ze všech kategorií>
  ```

  ---

  ## Zápis oprav

  Po explicitním potvrzení reportu uživatelem:

  1. Zapiš opravy do souboru — pouze nálezy se stavem OPRAVIT (ne NEJISTÉ)
  2. Neměň HTML strukturu, ARIA atributy, SEO bloky — pouze textový obsah uvnitř `<p>`, `<li>`, `<h2>`, `<h3>`, `<td>` tagů
  3. Zachovej Twig syntaxi a odsazení beze změny
  4. Po zápisu spusť: `git diff templates/ddd/<soubor>.html.twig`
  5. Počkej na potvrzení uživatele — teprve pak commitni
  ````

- [ ] **Krok 3: Ověř délku souboru**

  ```bash
  wc -l docs/prompts/review-chapter.md
  ```

  Očekávaný výstup: přibližně 100+ řádků.

- [ ] **Krok 4: Commitni**

  ```bash
  git add docs/prompts/review-chapter.md
  git commit -m "docs(prompts): přidat prompt pro revizi kapitoly"
  ```

---

## Task 3: Vytvořit docs/prompts/write-chapter.md

**Files:**
- Create: `docs/prompts/write-chapter.md`

- [ ] **Krok 1: Vytvoř soubor s tímto přesným obsahem**

  Soubor `docs/prompts/write-chapter.md`:

  ````markdown
  # Prompt: Psaní nové kapitoly

  Tento prompt řídí vytvoření nové kapitoly DDD průvodce od začátku.

  **Spuštění:** „Použij docs/prompts/write-chapter.md, téma: <název tématu>"

  ---

  ## Fáze 1 — Příprava

  ### 1a. Přečti podklady

  Přečti tyto soubory v tomto pořadí:
  1. `CLAUDE.md` — zásady projektu a voice/tone pravidla (sekce „Voice, tón a jazyk")
  2. `docs/MICRODATA_ARIA_GUIDE.md` — SEO a ARIA struktura šablon
  3. `src/Controller/DddController.php` — seznam existujících tras a čísel kapitol
  4. Tři tematicky nejbližší kapitoly z `templates/ddd/` (dle navigační struktury nebo tematické příbuznosti)

  ### 1b. Sestav terminologický slovník

  Ze tří přečtených kapitol vypiš interně:
  - Všechny DDD termíny a jejich definice tak, jak jsou použity v průvodci
  - Čísla kapitol a jejich témata (pro správné cross-reference)
  - Symfony verzi, kterou průvodce používá v kódových ukázkách

  Tento seznam použiješ při psaní — nepoužívej alternativní definice.

  ---

  ## Fáze 2 — Faktická příprava

  Před psaním prohledej web a ověř vše, o čem budeš psát.

  Zdroje v pořadí důvěryhodnosti:
  1. martinfowler.com
  2. Oficiální dokumentace Symfony (symfony.com/doc)
  3. Weby autorů: vlad.gg, eventstorming.com, teamtopologies.com
  4. Google Books preview (pro citace z knih)
  5. ACM Digital Library

  Co ověřit:
  - Kanonické definice všech klíčových pojmů tématu
  - Primární zdroj každého pojmu (Evans DDD 2003 Addison-Wesley, Vernon IDDD 2013 Addison-Wesley, Khononov LDDD 2021 O'Reilly, Brandolini, Newman, Skelton & Pais)
  - Přesný název a číslo kapitoly v každé citované knize
  - Rok vydání a nakladatel každé citované publikace
  - Aktuální Symfony API pro verzi průvodce
  - PHP 8.x syntaxi používanou v průvodci

  Sestav interní seznam ověřených faktů. Při psaní z něj čerpáš. Pokud fakt nemáš ověřený, nepiš ho.

  ---

  ## Fáze 3 — Psaní

  ### Struktura souboru

  Nová kapitola musí mít přesně tuto strukturu (viz libovolná existující šablona v `templates/ddd/`):

  ```twig
  {% extends 'base.html.twig' %}

  {% block title %}<Název kapitoly> | DDD Symfony{% endblock %}
  {% block meta_description %}<150 znaků, konkrétní, bez buzzwords>{% endblock %}
  {% block meta_keywords %}<5–8 termínů oddělených čárkou>{% endblock %}
  {% block og_type %}article{% endblock %}
  {% block article_published_time %}RRRR-MM-DD{% endblock %}
  {% block article_modified_time %}RRRR-MM-DD{% endblock %}
  {% block breadcrumb_name %}<Krátký název pro breadcrumb>{% endblock %}

  {% block structured_data %}
      <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "TechArticle",
        "headline": "<Název kapitoly>",
        "description": "{{ block('meta_description')|escape('js') }}",
        "keywords": "{{ block('meta_keywords')|escape('js') }}",
        "author": {
          "@type": "Person",
          "name": "Michal Katuščák",
          "url": "https://www.katuscak.cz/",
          "sameAs": [
            "https://blog.katuscak.cz/",
            "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"
          ]
        },
        "publisher": { "@type": "Person", "name": "Michal Katuščák" },
        "datePublished": "{{ block('article_published_time')|trim }}",
        "dateModified": "{{ block('article_modified_time')|trim }}",
        "image": "{{ app.request.schemeAndHttpHost }}{{ asset('images/social.png') }}",
        "mainEntityOfPage": {
          "@type": "WebPage",
          "@id": "{{ app.request.schemeAndHttpHost }}{{ app.request.pathInfo }}"
        }
      }
      </script>
  {% endblock %}

  {% block body %}
  <article class="article">
      {% include '_partials/article_head.html.twig' with {
          chapter_number: 'NN',
          category: '<kategorie>',
          title: '<Název kapitoly>',
          deck: '<2–3 věty úvodního shrnutí>',
          reading_time: N,
          difficulty: N,
          published: block('article_published_time'),
          last_updated: block('article_modified_time'),
          author: 'M. Katuščák'
      } %}

      {% include '_partials/article_toc.html.twig' %}

      <div class="art-body" data-chapter-number="NN">
          {# sekce #}
      </div>
  </article>
  {% endblock %}
  ```

  ### Pravidla při psaní textu

  - Každá věta říká jednu věc. Přes 25 slov = rozdělit.
  - Žádný odstavec nezačíná výplní ani fillerem (viz CLAUDE.md).
  - Technický termín se definuje při prvním výskytu, pak se používá konzistentně bez variací.
  - Kód je vždy kompletní a funkční pro Symfony verzi průvodce.
  - Žádné tvrzení bez ověřeného zdroje z fáze 2. Pokud si nejsi jistý, nepiš to.
  - Diagramy: vlož `{# TODO: diagram — <popis co diagram zobrazuje> #}` jako placeholder.
  - Citace: `<a href="<URL>" target="_blank" rel="noopener">[N]</a>` — čísla průběžně od 1.

  ### Co nepsat

  - Žádná vágní přídavná jména (mocný, robustní, elegantní, moderní...)
  - Žádný marketing ani hype
  - Žádné osobní komentáře autora
  - Žádné em dashe (—) — použít en pomlčku (–) s mezerami nebo přeformulovat

  ---

  ## Fáze 4 — Vlastní kontrola před odevzdáním

  Projdi napsaný text a zkontroluj každý bod:

  1. **Zakázaná slova**: mentální grep na všechny vzory ze seznamu „Zakázáno" v CLAUDE.md
  2. **Délka vět**: každá věta přes 25 slov — zkrátit nebo rozdělit
  3. **Faktická tvrzení**: každé tvrzení cross-check s ověřenými fakty z fáze 2; co není na seznamu — buď ověřit, nebo smazat
  4. **Konzistentnost termínů**: každý DDD termín — sedí s definicí ze slovníku z fáze 1?
  5. **Struktura šablony**: jsou všechny povinné Twig bloky přítomny a vyplněny?

  Teprve po čisté kontrole proveď:

  1. Zapiš soubor do `templates/ddd/<slug>.html.twig`
  2. Přidej routu do `src/Controller/DddController.php` dle existujícího vzoru:
     ```php
     #[Route('/czech-slug', name: 'route_name')]
     public function methodName(): Response
     {
         return $this->render('ddd/<soubor>.html.twig');
     }
     ```
  3. Počkej na potvrzení uživatele — teprve pak commitni
  ````

- [ ] **Krok 2: Ověř délku souboru**

  ```bash
  wc -l docs/prompts/write-chapter.md
  ```

  Očekávaný výstup: přibližně 120+ řádků.

- [ ] **Krok 3: Commitni**

  ```bash
  git add docs/prompts/write-chapter.md
  git commit -m "docs(prompts): přidat prompt pro psaní nové kapitoly"
  ```

---

## Task 4: Ověřovací průchod

**Files:**
- Read: `CLAUDE.md`, `docs/prompts/review-chapter.md`, `docs/prompts/write-chapter.md`

- [ ] **Krok 1: Ověř CLAUDE.md**

  ```bash
  grep -c "Zakázáno" CLAUDE.md
  ```

  Očekávaný výstup: `2` (dvě sekce Zakázáno).

- [ ] **Krok 2: Ověř review-chapter.md má všech 5 průchodů**

  ```bash
  grep -c "^## Průchod" docs/prompts/review-chapter.md
  ```

  Očekávaný výstup: `5`

- [ ] **Krok 3: Ověř write-chapter.md má všechny 4 fáze**

  ```bash
  grep -c "^## Fáze" docs/prompts/write-chapter.md
  ```

  Očekávaný výstup: `4`

- [ ] **Krok 4: Ověř git log**

  ```bash
  git log --oneline -5
  ```

  Očekávaný výstup: 3 nové commity (CLAUDE.md, review-chapter.md, write-chapter.md) na vrcholu logu.
