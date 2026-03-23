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
