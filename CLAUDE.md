# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Educational website about Domain-Driven Design (DDD) in Symfony, written in Czech. It is a content-focused site — no database entities are used; pages render Twig templates only.

## Common Commands

```bash
# Install dependencies
composer install

# Start local dev server (requires Symfony CLI)
symfony server:start

# Clear cache
php bin/console cache:clear

# Install assets
php bin/console assets:install
```

There is no build step, no npm, and no test suite.

## Architecture

**Pure content site** — `src/Entity/` and `src/Repository/` are empty. The entire application is:

- `src/Controller/DddController.php` — 10 route handlers, each rendering a Twig template
- `src/Controller/ErrorController.php` — custom error pages
- `templates/ddd/` — one `.html.twig` per page/topic
- `templates/diagrams/` — PlantUML source files and rendered SVGs, organized by topic number (1–7)
- `public/css/`, `public/js/` — served directly, no build pipeline

**Adding a new page:** create a template in `templates/ddd/`, add a route+action in `DddController.php`.

## Templates & SEO

`templates/base.html.twig` defines the master layout. Each page template must provide:
- JSON-LD structured data (schema.org `Article` or `WebPage`)
- Breadcrumb markup with schema.org
- ARIA attributes per `docs/MICRODATA_ARIA_GUIDE.md`
- Meta tags: `description`, `keywords`, `og:*`, `twitter:*`, canonical URL

## Diagrams

Diagrams live in `templates/diagrams/<N>_<topic>/`. PlantUML `.puml` files are the source; compiled SVGs are what the templates embed. When adding diagrams, follow the existing numbered directory convention.

## Routing

All routes use PHP attributes in `DddController`. Route paths are Czech slugs (e.g., `/co-je-ddd`, `/zakladni-koncepty`).

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
