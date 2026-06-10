# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Educational web book about Domain-Driven Design (DDD) in Symfony 8, written in Czech. Content-focused site — no database entities; chapter content lives in Markdown files rendered through a custom parser.

## Common Commands

```bash
# Install dependencies
composer install

# Start local dev server (requires Symfony CLI)
symfony server:start

# Clear cache (needed after editing chapter .md files or Chapters.php)
php bin/console cache:clear

# Lint templates
php bin/console lint:twig templates/
```

Frontend assets go through Vite (`vite-plugin-symfony`, see `package.json`); `highlight.js` for code blocks. There is no test suite.

## Architecture

- `content/chapters/*.md` — **the book itself**: one Markdown file per chapter, YAML frontmatter (`route`, `path`, `title`, `chapter_number`, `category`, `modified`, SEO meta…)
- `src/Catalog/Chapters.php` — chapter catalog: numbering (00–24), titles, descriptions, reading time, hub grouping, prev/next neighbors. Source of truth for the TOC.
- `src/Content/` — `ChapterMarkdownParser`, `ChapterFrontmatter`, `ChapterRouteLoader` (routes generated from frontmatter `path:`), `ChapterHeadingRenderer`, and `Block/` renderers for custom blocks
- `src/Controller/ChapterController.php` — renders chapters via `templates/chapter.html.twig`
- `src/Controller/DddController.php` — homepage, hub pages (`/zaklady`, `/takticke-vzory`, `/architektura`, `/vzory`, `/praxe`, `/synteza`, `/reference`), glossary, cheat sheet, resources
- `templates/ddd/` — Twig templates for non-chapter pages; `templates/_partials/` — hub, callout, diagram, chapter nav partials
- `templates/diagrams/<N>_<topic>/` — PlantUML `.puml` sources + compiled SVGs (also copied under `public/images/diagrams/`)

**Adding a new chapter:** create `content/chapters/<name>.md` with full frontmatter, add an entry to `Chapters::all()` (correct hub `group`), clear cache.

## Chapter Markdown syntax

- Callout: `:::callout{type="note|pattern|warn|anti"}` … `:::` (note=info, pattern=recommended pattern, warn=risk, anti=anti-pattern)
- Code: `:::code{language="php" filename="src/...php"}` … `:::`
- Diagram: `:::diagram{fig="NN.x-A" title="..." src="images/diagrams/..."}` — `fig` is a display label and must match the chapter number; `src` points to the rendered SVG
- FAQ: `:::faq{}` … `:::`
- Headings carry explicit anchors: `## NN.MM Title {#anchor}`. Never change existing anchors — other chapters link to them.
- Internal links use paths (`[text](/co-je-ddd#anchor)`), never chapter numbers. Verify target path (frontmatter `path:`) and anchor exist before adding a link.

## Conventions established in the book

- Aggregate base class: `AggregateRoot` with `record(object $event)` / `releaseEvents(): array` (defined in `/zakladni-koncepty#aggregate-root-lifecycle`). All examples use this API.
- Value objects expose `public readonly` properties (e.g. `$email->value`), not `value()` methods.
- IDs are generated via `symfony/uid` `Uuid::v7()`.
- Code targets PHP 8.4, Symfony 8, Doctrine ORM 3.
- Citations: no page numbers; unverifiable direct quotes are paraphrased. Partnership and Big Ball of Mud are attributed to Evans's *DDD Reference* (2015); "Supporting Subdomain" to Vernon (2013). Fictional case studies are labeled "Ilustrativní scénář".

## Templates & SEO

`templates/base.html.twig` defines the master layout. Chapter SEO (JSON-LD, breadcrumbs, meta) is generated from frontmatter. Non-chapter templates must provide:
- JSON-LD structured data (schema.org `Article` or `WebPage`)
- Breadcrumb markup with schema.org
- ARIA attributes per `docs/MICRODATA_ARIA_GUIDE.md`
- Meta tags: `description`, `keywords`, `og:*`, `twitter:*`, canonical URL

## Diagrams

Diagrams live in `templates/diagrams/<N>_<topic>/`. PlantUML `.puml` files are the source; compiled SVGs are what chapters embed. Directory numbers are historical and may not match current chapter numbers — the `fig` attribute in `:::diagram` must match the chapter, the `src` path stays as is.

## Voice, tón a jazyk

Platí vždy — při každé editaci i psaní nového obsahu.

### Hlas průvodce

Průvodce mluví jako zkušený praktik, který věci dobře zná a nebojí se říct názor. Neprodává, nevybízí, nenadsazuje. Tvrzení jsou přímá a podložená. Čtenář se cítí respektován jako profesionál.

### Pravidla věty

- Věty: krátké až střední (do ~25 slov). Dlouhé věty rozdělit.
- Věta říká jednu věc. Pokud říká dvě, rozdělit.
- Žádný odstavec nezačíná „Je důležité...", „V rámci...", „Je třeba poznamenat...".
- Mezi dvěma sousedními větami nikdy neopakovat stejné podstatné jméno v podmětu, pokud to nemá explicitní důvod (kontrastní porovnání). Použít synonymum nebo zájmeno, nebo věty spojit.
- Tón průvodce je deklarativní (autor popisuje), ne imperativní (autor velí). Imperativní formy („Použijte", „Definujte", „Rozdělte") jsou vyhrazeny pro callout typu `pattern` nebo numerované postupy v sekci „Implementace v praxi", nikdy pro definiční nebo srovnávací pasáže.
- „Lze + infinitiv" je standardní český registr v odborné próze. Substituce „lze" → „jde" / „jdou" se provádí pouze tehdy, když výsledná věta zní přirozeněji.

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
| „není stříbrná kulka" | AI klišé, vyřezat nebo přeformulovat („má své limity", „nehodí se všude") |
| „není binární volba", „není černobílé" | AI klišé, vyřezat nebo přeformulovat |
| „svatý grál", „švýcarský nůž" | AI klišé, vyřezat nebo přeformulovat |
| „v každém případě platí, že" | obecná fráze, smazat nebo specifikovat |
| „v dnešní době", „v moderním vývoji" | časově nestabilní, smazat |
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

### Zakázáno — strukturní vzory

Pravidla na úrovni věty a odstavce:

- Dvojí výskyt stejného slovesa nebo plnovýznamového slova ve větě → přepsat. Příklad chyby: „přináší strukturu a vyjadřovací sílu, ale jeho složitost přináší řadu úskalí".
- Paralelismus dvou sousedních vět se stejnou syntaktickou kostrou → rozbít. Příklad: „Write model se soustředí výhradně na X. Read model se soustředí na Y." Druhou větu přeformulovat tak, aby měla jiný rytmus.
- Wikipedijní úvod sekce → vyřezat nebo přepsat. Definice: 1–2 věty obecného typu „X je přístup, který přináší Y a řeší Z" na začátku sekce, bez konkrétního zakotvení nebo vlastního názoru. Sekce má začínat rovnou věcí, kterou přináší novou.
- „Není to jen X, je to Y" / „Nejen X, ale i Y" → klišé, přepsat na věcné tvrzení.

### Strukturní rytmus seznamů

Bullet listy: vyhnout se uniformnímu rytmu napříč kapitolou.

- Pokud má kapitola 4+ bullet listů ve formátu `**Pojem** – věta vysvětlující X.`, alespoň jeden přepsat. Buď bez tučného leadu, nebo s různými délkami položek, nebo zlomit do dvou bloků (číslovaný + nečíslovaný), nebo nahradit prózou.
- Bullet list 4–7 položek se stejnou délkou věty = signál AI generování. Záměrně rozkolísat: jedna položka delší, jedna kratší, jedna jako věta bez bullet.
- Alespoň jednou v kapitole nahradit list souvislým odstavcem se třemi krátkými větami. Plynulost mění rytmus.

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
