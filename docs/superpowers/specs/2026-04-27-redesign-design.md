# Kompletní redesign webu — design spec

**Datum:** 2026-04-27
**Rozsah:** Celý web (21 stránek)
**Princip:** Texty článků zůstávají beze změny; mění se vizuální layer (fonty, barvy, layouty, komponenty).

---

## 1. Vizuální směr

**„Architektonický blueprint v tmavém editoru"** — dark-first JetBrains/Zed estetika, technické výkresy, žádné gradienty, žádné stíny, ostré linky 0.5–1 px, mřížkové pozadí jako milimetrový papír.

Inspirace: technická publikace + vývojářský nástroj. Cílí na akademický a strohý dojem, sebedůvěru autora a klid pro dlouhé čtení.

Zdrojem pravdy designu jsou předané soubory:
- `tokens.css` — design tokens (barvy, fonty, spacing, typography scale, radii, reading widths)
- `styles.css` — komponenty (topnav, hero, article, TOC, callouts, code, diagrams, specimens)
- `landing-variants.css` — landing varianta A (TOC-as-Hero)

## 2. Architektura souborů

### Smazat
- `assets/styles/modern-style.css`
- `assets/styles/code-style.css`
- `assets/styles/fonts.css`
- `assets/scripts/modern-script.js`
- `assets/scripts/code-script.js`
- `assets/scripts/toc-sidebar.js`
- `assets/fonts/merriweather-*.woff2` (5 souborů)
- `assets/fonts/nunito-*.woff2` (5 souborů)

### Přidat — styles
- `assets/styles/tokens.css` — 1:1 z předaného `tokens.css`
- `assets/styles/base.css` — reset, base typography, html/body, ::selection, bg-grid utility
- `assets/styles/chrome.css` — topnav, footer, skip link, mobile menu
- `assets/styles/article.css` — art-head, art-meta, toc, art-body, headings, callouts, code-block chrome, diagram chrome
- `assets/styles/landing.css` — landA hero grid, eyebrow, reading paths, featured case study, full TOC list, marquee
- `assets/styles/prism-theme.css` — Prism token classes mapované na `var(--syn-*)`

### Přidat — scripts
- `assets/scripts/copy-button.js` — clipboard API pro `.code-copy` (~12 řádků)
- `assets/scripts/topnav.js` — mobilní toggle, sticky scroll-shadow stav
- `assets/scripts/article-toc.js` — generuje TOC z `<h2>` v `.art-body`, scroll-spy aktivního odkazu

### Přidat — fonts
- `assets/fonts/inter-latin.woff2`
- `assets/fonts/inter-latin-ext.woff2`
- `assets/fonts/inter-cyrillic.woff2`
- `assets/fonts/inter-cyrillic-ext.woff2`
- `assets/fonts/inter-vietnamese.woff2`

### Přidat — Twig partials
- `_partials/code_block.html.twig` — `<figure class="code">` s headerem (filename + jazyk pill + copy button) a Prism-zvýrazněným tělem (line numbers, line highlights)
- `_partials/callout.html.twig` — `<aside class="callout callout-{type}">` s rail (glyph + vertikální label) + body
- `_partials/diagram.html.twig` — `<figure class="diagram">` s headerem (FIG. xx · titulek), inline `<svg>` nebo `<img>`, caption
- `_partials/article_toc.html.twig` — `<aside class="toc">` (renderuje se prázdný kontejner; obsah generuje JS z `<h2>` v article body)
- `_partials/article_meta.html.twig` — přepsán na `<div class="art-meta">` 4-column grid (Autor / Doba čtení / Náročnost / Aktualizováno)

### Přepsat — Twig templates
- `templates/base.html.twig` — sticky topnav, page wrapper bez sidebaru, footer, error grid layout
- `templates/ddd/index.html.twig` — Landing varianta A
- 19 článkových šablon — Article layout (texty 1:1, jen wrapping a komponenty)
- `templates/ddd/glossary.html.twig` — list-bez-prózy (mřížka termů ve stylu spec-grid)
- `templates/ddd/resources.html.twig` — list-bez-prózy (sekce knihy / blogy / online kurzy)
- `templates/ddd/about.html.twig`, `security_policy.html.twig` — krátká stránka bez TOC a meta
- `templates/error.html.twig` — nový, parametrizovaný 404/500

### Beze změny
- `src/Controller/DddController.php` (21 routes)
- `src/Controller/ErrorController.php` (jen template path se může změnit)
- `templates/diagrams/` — PlantUML zdroje zachovány, SVG re-renderovat s upraveným skinem
- Composer, Vite konfig (jen `vite_entry_link_tags('app')` zůstává)
- JSON-LD a SEO meta v base.html.twig

---

## 3. Hierarchie obsahu

**4 kategorie v topnavu** (pořadí zleva: **Základy · Vzory · Praxe · Reference**).

| # | Route | Kategorie |
|---|-------|-----------|
| 01 | `what_is_ddd` | Základy |
| 02 | `basic_concepts` | Základy |
| 03 | `horizontal_vs_vertical` | Základy |
| 04 | `implementation_in_symfony` | Základy |
| 05 | `cqrs` | Vzory |
| 06 | `event_sourcing` | Vzory |
| 07 | `sagas` | Vzory |
| 08 | `performance_aspects` | Vzory |
| 09 | `practical_examples` | Praxe |
| 10 | `testing_ddd` | Praxe |
| 11 | `migration_from_crud` | Praxe |
| 12 | `ddd_pain_points` | Praxe |
| 13 | `anti_patterns` | Praxe |
| 14 | `when_not_to_use_ddd` | Praxe |
| 15 | `case_study` | Praxe (FEATURED na HP) |
| 16 | `ddd_ai` | Reference |
| – | `glossary` | Reference (nečíslováno) |
| – | `resources` | Reference (nečíslováno) |
| – | `about` | patička |
| – | `security_policy` | patička |
| – | `index` | landing |

**Logika číslování:** vertikální slice 03 mezi koncepty a Symfony (organizační rozhodnutí předchází implementaci). Výkon 08 na konci Vzorů (snapshoty stojí na ES a CQRS read-modelech). Testování 10 hned po praktických příkladech. Migrace 11 před pain points. Anti-vzory 13 jako *zhmotnění* pain points. *Kdy nepoužívat* 14 jako meta-otázka před case study. Case study 15 jako finále, ne začátek. AI 16 jako reflexe.

**Tag mapping** (v TOC i v topnavu):
- Základy → `Foundations`
- Vzory → `Tactical`
- Praxe → `Practice`
- Reference → `Reference`

**Reading paths** (4 kapitoly per cesta, žádný overlap):

| Cesta | Pořadí | Cíl |
|-------|--------|-----|
| Začátečník | 01 → 02 → 04 → 09 | Co je DDD → koncepty → Symfony konkrétně → praktické příklady |
| Pokročilý | 05 → 06 → 07 → 10 | CQRS → ES → Ságy → Testování |
| Architekt | 14 → 11 → 12 → 08 | Kdy nepoužívat → Migrace → Pain points → Performance |

---

## 4. Vizuální foundation

### Barvy (z `tokens.css`)
- **Surface (4 vrstvy):** `--bg-0: #0B0D10` (canvas) → `--bg-1: #11141A` → `--bg-2: #161A22` → `--bg-3: #1B202A`. `--bg-inset: #08090C`.
- **Foreground:** `--fg: #E6E8EC` / `--fg-muted: #A6ADBB` / `--fg-dim: #6B7383` / `--fg-faint: #3D424E`.
- **Stroke:** `rgba(230, 232, 236, 0.08)` (default), `0.16` (strong), `0.04` (faint).
- **Akcent (default amber):** `oklch(0.78 0.13 65)`. `--accent-dim` (18% alpha), `--accent-faint` (8% alpha), `--accent-ink: #0B0D10` (text na akcentu).
- **Sémantika (jen v kódu/stavech):** keyword fialová `oklch(0.72 0.16 290)`, string zelená `oklch(0.78 0.14 145)`, number žlutá `oklch(0.82 0.13 80)`, type modrošedá `oklch(0.78 0.10 220)`, fn tyrkys `oklch(0.82 0.12 195)`, comment `oklch(0.55 0.02 250)`, tag terakota `oklch(0.75 0.13 25)`, property světle teplá `oklch(0.80 0.09 30)`.
- **DDD koncept barvy** (jen pro diagramy): aggregate = accent, event = string-green, command = type-blue, query = keyword-violet, vo = comment-grey.

### Typografie
- `--font-sans: "Inter", ui-sans-serif, system-ui`
- `--font-mono: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, Consolas`
- Scale: 11/13/16/18/22/28/36/48/72 px (vars `--t-xs` … `--t-4xl`)
- Line-height: 1.15 (tight) / 1.3 (snug) / 1.55 (normal) / 1.7 (loose pro long-form) / 1.65 (kód)
- Tracking: -0.022em pro velké display, -0.005em pro mono, +0.04em pro UPPERCASE labely

### Spacing a struktura
- 4px raster: `--s-1: 4px` … `--s-10: 128px`
- Radii: 0/2/4/6/pill — žádný 12px+ rounded
- Reading widths: `--measure-prose: 68ch`, `--measure-wide: 88ch`, `--canvas-max: 1280px`

### Mřížkové pozadí (utility)
- `.bg-grid` — 32px grid, `--stroke-faint` linky
- `.bg-grid-fine` — 8px grid

---

## 5. Layout — Chrome

### Topnav (sticky, height 56 px, `backdrop-filter: blur(12px)`)

```
┌──────────────────────────────────────────────────────────────────┐
│ [▣] DDD·Symfony 8        Základy  Vzory  Praxe  Reference   [⌕ Hledat v knize…  ⌘K] │
│     Domain-Driven Design pro pokročilé                            │
└──────────────────────────────────────────────────────────────────┘
```

- Brand: SVG mark (3 vnořené čtverce — accent vnější, dim střední, accent fill vnitřní), title `DDD·Symfony 8` + sub mono.
- Aktivní link má `::after` 1px accent underline. Detekce přes `app.request.attributes.get('_route')` mapped na kategorii.
- Searchbar: vizuální placeholder (nefunkční v tomto redesignu — funkcionalita budoucí). Min-width 280 px, mizí na mobile.
- Mobile: hamburger toggle přepíná `aria-expanded` na `<body>`, side drawer slide z pravé strany.

### Footer
- 3 kolony: brand + popis | navigace (vč. *O autorovi*, *Bezpečnostní zásady*) | meta (`Aktualizováno {{ now|date }}`, autor, GitHub, copyright)
- Mono font, `--fg-dim` foreground, top border `--stroke`, padding `--s-7 0`

### Skip link
- Zachovat (`#content`), restylizovat: pozice `top: -40px` → `top: 8px` při focus, accent background, mono font

---

## 6. Homepage (Landing varianta A)

### Layout grid

```
┌────────────────────────────────┬────────────────────────────────┐
│ EYEBROW · VOL. I · 2026 ED.    │ CONTENTS ── 16 kap. · ~6h 41′ │
│                                ├────────────────────────────────┤
│ <h1>                           │ 01  Co je DDD                  │
│ Doménově řízený návrh,         │     Filozofie, Bounded Context │
│ přeložený do Symfony.          │     ZÁKLADY · 12′ · ▮▮▯▯       │
│                                │                                │
│ <lede ~3 řádky>                │ 02  Základní koncepty          │
│                                │     Entity, VO, Aggregate, …   │
│ ┌────────────────────────────┐ │     ZÁKLADY · 22′ · ▮▮▮▯       │
│ │ [MK] Michal Katuščák       │ │                                │
│ │      PHP/Symfony, 13+ let  │ │ ... (16 kapitol)               │
│ └────────────────────────────┘ │                                │
│                                │ Glosář                         │
│ [Otevřít první kap. →] [skip] │ Zdroje                         │
│                                │                                │
│ DOPORUČENÉ ČTENÍ                │                                │
│   Začátečník  01→02→04→09 ~70′ │                                │
│   Pokročilý   05→06→07→10 ~80′ │                                │
│   Architekt   14→11→12→08 ~70′ │                                │
└────────────────────────────────┴────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│ FEATURED · KAPITOLA 15                                            │
│                                                                   │
│ Případová studie: e-shop end-to-end                               │
│ <2-řádkový lede z meta_description case_study>                   │
│ [Otevřít případovou studii →]                                     │
└──────────────────────────────────────────────────────────────────┘

  Marquee: PHP 8.4+ · Symfony 8 · Doctrine ORM · Messenger · 16 kapitol · česky · zdarma · open source
```

### Komponenty HP

- **Hero grid:** `grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr)`, gap 96 px (`--s-9`), max-width 1480 px. Levá kolona pad-top 12 px.
- **Eyebrow:** `VOL. I` (accent) — `<rule>` — `2026 EDITION · ČESKY · ZDARMA` (dim mono).
- **Title:** 84 px, line-height 1.02, `letter-spacing: -0.026em`, max 14ch. `.A-em` (italic, fg-muted, weight 400) pro druhou řádku.
- **Deck:** 19 px, line-height 1.65, max 48ch. `<em>` mapováno na accent.
- **Author block:** 36×36 px box s `MK` v mono accent monogram, jméno + role mono dim.
- **CTA:** primary button + skip-link (mono, dim, dashed-underlined accent target).
- **Reading paths:** 3 řádky grid `110px 1fr auto` (tag · route · time), tečkované border-bottom.
- **TOC pravá kolona:** border-left, padding-left 48 px. Header má `CONTENTS` accent + flex rule + meta info.
- **TOC item:** grid `44px 1fr 200px` (číslo · text · side), padding 14 px Y, border-bottom solid stroke. Hover: `background: bg-1`, accent `→` v `::before`. Číslo accent mono 13 px tabular-nums. Title 16 px sans. Description 11.5 px mono dim. Side: tag UPPERCASE 10 px · čas mono `12′` · level bar 4 buněk. Glossary a Resources jsou bez čísla v `<span class="A-toc-n">` (prázdná buňka, zachovaný grid).
- **Featured case study:** plná šířka pod hero grid, accent border-left 2 px, padding `--s-7`. Eyebrow `FEATURED · KAPITOLA 15` (accent mono 10.5 px UPPERCASE), nadpis 28 px, lede 16 px loose, CTA primary.
- **Marquee:** flex centered, mono 13 px, dot separator accent. Žádná animace v MVP (vizuální dekorace).

### Hero spec hodnoty (computed)

V Twig template HP:
- **Rozsah:** `16 kapitol · {{ section_count }} sekcí` — `section_count` se spočítá build-time scriptem (PHP CLI `bin/console app:count-sections`) nebo hardkódovaný odhad (~250 sekcí).
- **Stack:** `PHP 8.4 · Symfony 8 · Doctrine ORM · Messenger`
- **Formát:** `Long-form, runnable examples, doplňková Git repa`
- **Pro koho:** `Senior PHP/Symfony, architekti, tech leadi`
- **Aktualizováno:** `{{ "now"|date("j. n. Y") }}` — automaticky aktualizováno (alternativně z sitemap lastmod)

> Section count: pro v1 můžeme dát hardkódovanou hodnotu „~250 sekcí" nebo to zatím vynechat z spec listu.

---

## 7. Article template

### Layout

```
┌─────────────────────────────────────────────────────────────────┐
│ KAPITOLA 04 · ZÁKLADY · Implementace v Symfony 8                │  art-head
│                                                                 │
│ <h1>Implementace DDD v Symfony 8</h1>                           │
│ <deck ≈ 2 věty z meta_description>                             │
│                                                                 │
│ AUTOR     │ DOBA ČTENÍ │ NÁROČNOST  │ AKTUALIZOVÁNO              │  art-meta
│ M. Kat.   │ ≈ 26 min   │ ▮▮▮▯ 3/4   │ 12. dub 2026               │
├─────────────────────────────┬───────────────────────────────────┤
│ OBSAH KAPITOLY              │                                   │  body grid
│ 04.01 ...                   │   <h2 04.01>                      │
│ 04.02 ...                   │                                   │
│ 04.03 ... (current)         │   <p>Texty 1:1 z původní šablony</p>│
│ 04.04 ...                   │                                   │
│  (sticky)                   │   {% include _partials/callout %} │
│                             │   {% include _partials/code_block %}│
│                             │   {% include _partials/diagram %} │
└─────────────────────────────┴───────────────────────────────────┘
```

- Article wrap: `max-width: 1180px; padding: 48px 24px 96px; grid-template-columns: 220px 1fr; gap: 48px`.
- Art-head spans full width (`grid-column: 1 / -1`), top border, bottom margin 48 px.
- Crumb: mono dim, separator `·`, current accent. Pattern: `Kapitola 04 · ZÁKLADY · Implementace v Symfony 8`.
- Title: 48 px (`--t-3xl`), line-height 1.15, max 22ch, balanced.
- Deck: 18 px loose, max 68ch, fg-muted.
- Meta: 4-col grid, mono labely UPPERCASE, hodnoty 13 px sans. Difficulty bar: 4 buňky, accent fill = úroveň 1–4.
- TOC: sticky `top: 80px`, border-left, padding-left 16 px. Items grid `56px 1fr` (číslo · text). Current ma fg + accent číslo + weight 500.
- Body: `max-width: 700px`, line-height 1.7, fg primary. Paragraphs margin-bottom 24 px. `<em>` accent + weight 500. `<strong>` fg primary + weight 600.
- H-section: 28 px (`--t-xl`), weight 600, snug, `<span class="h-num">04.03</span>` mono accent prefix.

### Twig blocks v article šablonách
- `{% block category %}Základy{% endblock %}`
- `{% block chapter_number %}04{% endblock %}`
- `{% block reading_time %}26{% endblock %}` (čísla v minutách)
- `{% block difficulty %}3{% endblock %}` (1–4)
- `{% block last_updated %}2026-04-22{% endblock %}` (default = sitemap lastmod nebo dnes)
- `{% block deck %}{% endblock %}` (lede pod h1)

### Partials API

**callout.html.twig:**
```twig
{# args: type ('pattern'|'anti'|'note'|'warn'), label, body (HTML allowed) #}
<aside class="callout callout-{{ type }}">
  <div class="callout-rail">
    <span class="callout-glyph">{{ glyphs[type] }}</span>
    <span class="callout-label">{{ label }}</span>
  </div>
  <div class="callout-body">{{ body|raw }}</div>
</aside>
```

**code_block.html.twig:**
```twig
{# args: filename, language, code (raw), highlights (array of line numbers) #}
<figure class="code">
  <header class="code-head">
    <span class="code-dot" data-lang="{{ language }}">{{ language }}</span>
    <span class="code-file">{{ filename }}</span>
    <span class="code-spacer"></span>
    <button class="code-copy" data-copy>...</button>
  </header>
  <pre class="code-body line-numbers"
       data-line="{{ highlights|join(',') }}"><code class="language-{{ language }}">{{ code }}</code></pre>
</figure>
```
Prism běží přes Vite import; theme přemapuje Prism class names na `var(--syn-*)`.

**diagram.html.twig:**
```twig
{# args: fig (např. '04.4-A'), title, src (cesta k SVG nebo inline), caption #}
<figure class="diagram">
  <header class="diagram-head">
    <span class="diagram-num">FIG. {{ fig }}</span>
    <span class="diagram-title">{{ title }}</span>
  </header>
  {% if inline %}
    {{ inline|raw }}
  {% else %}
    <img class="diagram-svg" src="{{ asset(src) }}" alt="{{ title }}">
  {% endif %}
  {% if caption %}
    <figcaption class="diagram-caption">{{ caption|raw }}</figcaption>
  {% endif %}
</figure>
```

### TOC generování
- `article-toc.js` na `DOMContentLoaded`:
  1. Najde `.art-body h2`
  2. Přidá `id` (slugifikovaný text), pokud chybí
  3. Vyrenderuje `<ol class="toc-list">` do `.toc[data-target]`
  4. IntersectionObserver na h2 → aktivní `.toc-current`

---

## 8. Code blocks — Prism integrace

### Strategie A (zvolená)
- Necháme Prism (auto-detekce jazyka z `class="language-*"`)
- Nový `prism-theme.css` přemapuje `.token.keyword` → `var(--syn-keyword)`, `.token.string` → `var(--syn-string)` atd.
- Prism plugin `line-numbers` — line numbers jako v designu (tabular-nums, fg-faint, padding-right 12 px)
- Prism plugin `line-highlight` — `data-line="7,8,9"` vykreslí accent border-left + accent-faint background přes `::before`
- Copy button — vlastní 12-řádkový JS, použije `navigator.clipboard.writeText()`, dočasně přepne tlačítko na "zkopírováno ✓" (`--state-ok`)

### Co se NEMĚNÍ
- `<pre><code class="language-php">...</code></pre>` v existujících šablonách funguje dál
- Když kód obalíme do `_partials/code_block.html.twig`, jen přidáme filename header a copy button. Obsah `<pre>` zůstane.

### Theme variants v `tokens.css`
Existují (`[data-codestyle="paper"]`, `[data-codestyle="inverted"]`), ale v MVP nepoužíváme — drží se default (terminal style).

---

## 9. Diagramy — PlantUML re-render

### Strategie B (zvolená)
- Existující `.puml` zdroje v `templates/diagrams/<N>_<topic>/` zůstávají
- Upravit `skin` direktivu globálně přes `!include` shared skin file:
  - `skinparam backgroundColor #11141A` (= bg-1)
  - `skinparam defaultFontName "JetBrains Mono"`
  - `skinparam defaultFontSize 11`
  - `skinparam defaultFontColor #E6E8EC`
  - `skinparam ArrowColor #A6ADBB`
  - `skinparam ArrowFontColor #6B7383`
  - `skinparam ClassBackgroundColor #161A22`
  - `skinparam ClassBorderColor #3D424E`
  - `skinparam ClassFontColor #E6E8EC`
  - `skinparam ClassBorderThickness 0.75`
  - aggregate / event / command / query barvy přes stereotype skin (DDD koncept barvy)
- Přerenderovat všechny `.puml` → `.svg` build skriptem
- Pokud PlantUML output nebude vypadat dostatečně blízko designu po 1 hodině experimentování, akceptujeme současnou estetiku — důležité je, aby diagram byl čitelný a v `<figure class="diagram">` chrome
- Wrap zůstává designový: `diagram-head` (FIG. xx · titulek) + caption

### Diagram chrome (vždy aplikovaný)
- Border `--stroke`, background `--bg-1`, radius 4 px, overflow hidden
- Header padding 12 px 16 px, mono 11 px, accent prefix (FIG. xx)
- Caption padding 12 px 16 px, top border, mono dim, accent indexy `①②③`

---

## 10. Migrace 21 stránek

### Princip
Každá článková šablona dnes má strukturu:
```twig
{% extends 'base.html.twig' %}
{% block body %}
  <h1>...</h1>
  <p>...</p>
  <h2>...</h2>
  <pre><code class="language-php">...</code></pre>
  ...
{% endblock %}
```

Po migraci:
```twig
{% extends 'base.html.twig' %}

{% block category %}Základy{% endblock %}
{% block chapter_number %}04{% endblock %}
{% block reading_time %}26{% endblock %}
{% block difficulty %}3{% endblock %}
{% block deck %}<deck text 1:1 z meta_description nebo z prvního <p>>{% endblock %}

{% block body %}
  <article class="article">
    {% include '_partials/article_head.html.twig' %}
    {% include '_partials/article_toc.html.twig' %}
    <div class="art-body">
      <h2 id="s1" class="h-section"><span class="h-num">04.01</span> ...</h2>
      <p>... (text 1:1) ...</p>
      {% include '_partials/code_block.html.twig' with {...} %}
      ...
    </div>
  </article>
{% endblock %}
```

### Co se ručně přepíše per-stránku
1. Přidat 5 Twig blocks (category, chapter_number, reading_time, difficulty, deck)
2. Wrap body do `<article class="article">…<div class="art-body">…</div>`
3. Zaměnit `<h1>` (zůstává v `art-head` přes partial)
4. Předtransformovat `<h2>` na `<h2 class="h-section"><span class="h-num">XX.NN</span>…`
5. Existující `<pre><code>` obalit do `code_block` partial (vyžaduje vyčlenit filename a highlights — provedu best-effort z přilehlých `<p>` textů a komentářů v kódu)
6. Existující "varování / tip" blocky → callout partial (typ vybrán dle obsahu: ✕ anti, ◢ pattern, § note, ! warn)
7. Existující `<img src="{{ asset('diagrams/...') }}">` nebo SVG embed → diagram partial

### Reading time + difficulty
Hodnoty zatím vlastním judgment callem:

| # | Route | Reading min | Difficulty (1–4) |
|---|-------|-------------|------------------|
| 01 | what_is_ddd | 12 | 1 |
| 02 | basic_concepts | 22 | 2 |
| 03 | horizontal_vs_vertical | 14 | 2 |
| 04 | implementation_in_symfony | 26 | 3 |
| 05 | cqrs | 20 | 3 |
| 06 | event_sourcing | 28 | 4 |
| 07 | sagas | 24 | 4 |
| 08 | performance_aspects | 20 | 4 |
| 09 | practical_examples | 32 | 3 |
| 10 | testing_ddd | 18 | 3 |
| 11 | migration_from_crud | 22 | 3 |
| 12 | ddd_pain_points | 30 | 4 |
| 13 | anti_patterns | 16 | 2 |
| 14 | when_not_to_use_ddd | 12 | 2 |
| 15 | case_study | 45 | 4 |
| 16 | ddd_ai | 10 | 1 |

Tyto hodnoty se v rámci migrace doladí podle reálné délky textu (`wc -w`) a komplexity.

### Edge-case stránky
- **Glossary** — bez TOC, bez meta. Layout: art-head + grid termů ve stylu `.spec` listu (term mono accent + definice prose). Více sekcí (např. *Strategická DDD*, *Taktická DDD*, *CQRS+ES*).
- **Resources** — bez TOC, bez meta. Sekce: Knihy, Blogy, Videa, Kurzy, Repos. Každý záznam: titulek + autor + 1-řádek popis + link (mono accent).
- **About** — krátká stránka, art-head + body bez TOC. Žádná meta.
- **Security policy** — krátká stránka, art-head + body bez TOC. Bod-by-bodu mono spec list.

---

## 11. Error stránky

`templates/error.html.twig` parametrizovaný:
```twig
{% extends 'base.html.twig' %}
{% block body %}
<section class="error">
  <div class="error-code">{{ status_code }}</div>
  <h1 class="error-title">{{ status_text }}</h1>
  <p class="error-msg">{{ message ?? default_message }}</p>
  <a href="{{ path('homepage') }}" class="btn btn-ghost">← Zpět na přehled</a>
</section>
{% endblock %}
```

`ErrorController` předá `status_code`, `status_text`, `message`. Default messages:
- 404 → „Stránka nenalezena"
- 500 → „Něco se pokazilo"
- ostatní → „Chyba {{ status_code }}"

CSS: error-code mono 96 px accent, error-title 36 px sans, msg fg-muted, layout flex center, padding 96 px.

---

## 12. FAQ partial

Přepsán bez akordeonu (žádné JS rozbalování). Layout = mřížka mono Q labelů + body.

```twig
<section class="faq">
  <header class="faq-head">
    <span class="faq-eyebrow">Q&A</span>
    <span class="faq-rule"></span>
  </header>
  <dl class="faq-list">
    {% for item in items %}
    <div class="faq-row">
      <dt class="faq-q">
        <span class="faq-q-num">{{ "%02d"|format(loop.index) }}</span>
        <span class="faq-q-text">{{ item.question }}</span>
      </dt>
      <dd class="faq-a">{{ item.answer|raw }}</dd>
    </div>
    {% endfor %}
  </dl>
</section>
```

CSS: faq-row grid `64px 1fr`, dotted border-bottom, faq-q-num mono accent, dt + dd vertical stack uvnitř row.

---

## 13. Plán fází

| Fáze | Obsah | Odhad |
|------|-------|-------|
| 1. Foundation | tokens.css, base.css, prism-theme.css, fonty Inter (5 woff2), smazat staré, Vite registrace | ~2 h |
| 2. Chrome | topnav (sticky, brand SVG, search placeholder, mobile drawer), footer, base.html.twig wrap | ~2 h |
| 3. Article partials | callout, code_block (Prism + copy), diagram, article_toc, article_head, article-toc.js | ~3 h |
| 4. Homepage | LandingA: hero grid, eyebrow, author, CTA, reading paths, full TOC, featured case study, marquee | ~3 h |
| 5. Migrace 19 článků | wrap do article + partials, texty 1:1, manuální per-stránka kontrola | ~6–10 h |
| 6. Edge cases | error, glossary, resources, about, security_policy, FAQ partial, PlantUML skin re-render | ~3 h |

**Celkem:** ~19–23 hodin čistého času.

---

## 14. Co je *out of scope*

- Funkční vyhledávání (placeholder only)
- Tweaks panel (přepínač akcent / code style — design-canvas only)
- Změny obsahu textů
- Změny URL slugs nebo route
- Změny PlantUML zdrojů kromě skin parametrů
- Nové diagramy (nebo přepis existujících do hand-coded SVG)
- Dark/light theme switcher (jediné téma = dark)
- Animace marquee, scroll, animations beyond hover transitions

## 15. Akceptační kritéria

- Všech 21 routes vrací HTTP 200 a renderuje bez chyb
- Žádné konzolové chyby v devtools (CSS, JS, fonts loading)
- Lighthouse mobile/desktop performance ≥ 90 (cíl)
- Lighthouse accessibility ≥ 95 (cíl)
- WAVE auditor 0 errors, 0 contrast errors
- HP a všechny článkové šablony renderují s novým layoutem
- JSON-LD pro `Article` a `BreadcrumbList` zachováno
- OG/Twitter meta zachováno
- Texty článků 1:1 stejné jako před migrací (rozumný diff: jen wrappery a chrome, žádné slovní změny)
- 5 woff2 souborů Inter, žádný Merriweather/Nunito
- Code bloky mají filename header, copy button, line numbers, syntax highlighting v designových barvách
