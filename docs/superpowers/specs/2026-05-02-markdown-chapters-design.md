# Design: Markdown-based chapters

**Datum:** 2026-05-02  
**Status:** schváleno

## Cíl

Přesunout obsah 35 obsahových kapitol z individuálních Twig šablon do Markdown souborů s YAML frontmatter. Výstup pro návštěvníka zůstává bit-for-bit identický. Přidání nové kapitoly = přidání jednoho `.md` souboru, žádná změna v PHP.

## Co se nemění

- Vizuální výstup pro návštěvníky (HTML, CSS, JS)
- `DddController.php` — hub stránky, index, about, redirecty, speciální stránky
- Hub šablony: `templates/ddd/hub_*.html.twig`, `index.html.twig` a další strukturální stránky
- `Chapters::all()` — zůstává jako zdroj dat pro navigaci a hub stránky
- Všechny partials v `templates/_partials/`
- Public assets, CSS, JS

## Nové soubory a třídy

```
content/chapters/           ← 35 MD souborů (jeden per kapitola)
src/Content/
  ChapterRouteLoader.php    ← Symfony RouteLoaderInterface
  ChapterController.php     ← jeden action: show()
  ChapterMarkdownParser.php ← service: CommonMark + custom extensions
  ChapterFrontmatter.php    ← DTO pro data z frontmatter
templates/
  chapter.html.twig         ← generická šablona (nahrazuje 35 šablon)
```

## Struktura MD souboru

```yaml
---
route: what_is_ddd
path: /co-je-ddd
title: Co je Domain-Driven Design?
page_title: "Co je Domain-Driven Design? Vysvětlení DDD | DDD Symfony"
meta_description: "Domain-Driven Design srozumitelně: filozofie Erica Evanse..."
meta_keywords: "Domain-Driven Design, DDD, Eric Evans, Ubiquitous Language..."
og_type: article
published: "2025-04-24"
modified: "2026-04-28"
breadcrumb_name: Co je DDD
schema_type: TechArticle
schema_headline: "Co je Domain-Driven Design? Podrobné vysvětlení DDD"

chapter_number: "01"
category: Základy
deck: "Domain-Driven Design (DDD), jeho základní principy..."
reading_time: 12
difficulty: 1
github_examples: Chapter01_WhatIsDDD
---

## 01.01 Definice DDD {#definition}

Text kapitoly...
```

Žádné hodnoty se nededukují automaticky — vše, co je dnes explicitně v Twig bloku, bude explicitně ve frontmatter.

## Speciální bloky (custom CommonMark extensions)

### Callout

```markdown
:::callout{type="note"}
**Základní aspekty DDD:**
- **Doména** – Oblast znalostí...
:::
```

Generuje identický HTML jako `_partials/callout.html.twig`. Podporované typy: `note`, `warning`, `tip` (dle stávajícího partialu).

### Diagram

```markdown
:::diagram{fig="02.1" title="Základní koncepty DDD" src="images/diagrams/2_basic_concepts/diagram.svg"}
:::
```

Generuje identický HTML jako `_partials/diagram.html.twig` včetně zoom/fullscreen toolbaru. SVG jsou referovány jako `<img src="...">` — inline embedding není potřeba (barvy jsou hardcoded, ne CSS proměnné).

### Code block

Standardní CommonMark fenced code:

````markdown
```php
final class Order { ... }
```
````

Generuje identický HTML jako `_partials/code_block.html.twig`.

### FAQ

```markdown
:::faq{heading="Časté otázky"}
- question: Kdy použít DDD?
  answer: Odpověď...
- question: Co je Aggregate?
  answer: Odpověď...
:::
```

Vnitřní obsah se parsuje jako YAML list. Extension generuje identický HTML jako `_partials/faq.html.twig` včetně `FAQPage` JSON-LD schema.

### Nadpisy se sekcemi

`## 01.01 Definice DDD {#definition}` generuje:

```html
<section id="definition" aria-labelledby="definition-heading">
  <h2 id="definition-heading" class="h-section">
    <span class="h-num">01.01</span> Definice DDD
  </h2>
```

Prefix `NN.MM` se automaticky extrahuje do `<span class="h-num">`. ID pochází z `{#id}` atributu (CommonMark attributes extension).

## Tok požadavku

```
GET /co-je-ddd
  → ChapterRouteLoader přiřadí: content/chapters/what_is_ddd.md
  → ChapterController::show() načte soubor
  → ChapterMarkdownParser vrátí: ChapterFrontmatter + HTML string
  → chapter.html.twig (extends base.html.twig) vyrenderuje stránku
```

`chapter.html.twig` z `ChapterFrontmatter` generuje:
- `{% block title %}`, meta tagy, og:*, twitter:*
- `{% block structured_data %}` — JSON-LD (identický s dnešním)
- `{% block breadcrumb_name %}`
- parametry pro `_partials/article_head.html.twig`
- `_partials/article_toc.html.twig` (bez parametrů — JavaScript ho plní z `<h2>` v `.art-body`)

## RouteLoader

`ChapterRouteLoader` implementuje `RouteLoaderInterface`. Při buildu projde `content/chapters/*.md`, přečte frontmatter a zaregistruje routy:

- Route name = `route:` z frontmatter (identický s dnešním názvem v `DddController`)
- Path = `path:` z frontmatter
- Controller = `ChapterController::show`
- Default parameter `_file` = název MD souboru

Díky zachování route names fungují bez změny:
- `path('what_is_ddd')` v navigaci, breadcrumbech, `Chapters::all()`
- `redirectToRoute()` v `DddController`

## Strategie migrace (postupná)

1. Vznikne `ChapterController`, `chapter.html.twig`, `ChapterMarkdownParser` s extensions
2. Migrace probíhá kapitola po kapitole — nejdřív jedna jako pilot
3. Dokud MD soubor neexistuje, obslouží stránku starý `DddController` action
4. Jakmile MD soubor vznikne a RouteLoader ho zaregistruje, odpovídající action v `DddController` se smaže
5. Po migraci všech 35 kapitol zbyde v `DddController` jen index, hubs, about, redirecty a speciální stránky

## Balíčky

- `league/commonmark` — Markdown parser s extension API
- `symfony/yaml` — parsování YAML frontmatter (již přítomen v Symfony)
- Žádné další závislosti

## Rozsah — co je mimo scope

- Hub stránky (`hub_basics`, `hub_tactics` atd.) — zůstávají jako Twig šablony
- `index.html.twig`, `about.html.twig`, `security_policy.html.twig` — zůstávají
- `glossary.html.twig`, `cheat_sheet.html.twig`, `resources.html.twig` — zůstávají (odlišná struktura)
- Žádná změna CSS, JS, public assets
- Žádná změna `Chapters::all()`
