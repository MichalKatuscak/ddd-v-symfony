# Design Spec: Dark Mode Redesign

**Date:** 2026-03-23
**Author:** Michal Katuščák
**Status:** Approved

## Overview

Redesign of the DDD Symfony educational website from a pastel light theme to a professional dark mode inspired by symfony.com. The target audience is university students learning DDD and Symfony. The site is content-only (no database), built with Symfony + Twig templates.

## Goals

- Modern, dark academic look that resonates with young developers
- Better readability for long-form text, code examples, and diagrams
- Sidebar navigation for documentation-style UX
- Symfony brand identity (navy + gold)

## 1. Color Palette

```css
--bg-base: #0f0f1a;           /* main dark navy background */
--bg-surface: #161625;        /* sidebar, cards, code block bg */
--bg-elevated: #1e1e30;       /* hover states, info cards, code headers */

--color-primary: #f5c518;     /* Symfony gold — primary accent */
--color-primary-dim: #c9a010; /* hover state for primary (buttons, sidebar active hover) */
--color-accent: #7c6ff7;      /* secondary accent — purple for badges/tags */

--text-primary: #e8e8f0;      /* main body text */
--text-muted: #8888a8;        /* captions, metadata, line numbers */
--text-heading: #ffffff;      /* headings */

--border: #2a2a40;            /* subtle section separators */
```

## 2. Layout

### Global Structure

```
┌──────────────────────────────────────────────┐
│  Top bar (logo + hamburger)    [fixed, 56px] │
├─────────────┬────────────────────────────────┤
│             │                                │
│   Sidebar   │   Main content area            │
│   (260px)   │   (max 780px, centered)        │
│   fixed     │                                │
│             │   [breadcrumb]                 │
│   Chapter   │   <article>                    │
│   list with │     headings, text,            │
│   active    │     code, diagrams             │
│   state     │   </article>                   │
│             │                                │
│             │   [prev/next navigation]       │
└─────────────┴────────────────────────────────┘
```

### Top Bar

- Height: `56px`, fixed position
- Background: `--bg-surface`
- Bottom border: `2px solid --color-primary`
- Logo left: "DDD **Symfony**" — only "Symfony" in bold, gold color
- Right: hamburger icon for mobile sidebar toggle
- Also update: `<meta name="theme-color" content="#0f0f1a">` in `base.html.twig`

### Sidebar

- Width: `260px`, fixed left
- Background: `--bg-surface`
- Lists all **14 chapters** in order (intentionally reordered from current nav — security_policy moved to position 10, migration_from_crud to 11, before testing/event sourcing/anti-patterns):
  1. Úvod (`homepage`)
  2. Co je DDD (`what_is_ddd`)
  3. Vertikální slice (`horizontal_vs_vertical`)
  4. Základní koncepty (`basic_concepts`)
  5. Implementace v Symfony (`implementation_in_symfony`)
  6. CQRS (`cqrs`)
  7. Příklady (`practical_examples`)
  8. Případová studie (`case_study`)
  9. Zdroje (`resources`)
  10. Bezpečnostní politika (`security_policy`)
  11. Migrace z CRUD (`migration_from_crud`)
  12. Testování DDD (`testing_ddd`)
  13. Event Sourcing (`event_sourcing`)
  14. Anti-patterny (`anti_patterns`)
  *(Note: `performance_aspects` route exists in controller but has no current nav link — keep excluded from sidebar)*
- Active item: `3px solid --color-primary` left border + gold text (`--color-primary`)
- Active hover: `--color-primary-dim` text
- Mobile: hidden by default, opens as overlay. Backdrop: `background: rgba(0,0,0,0.6); z-index: 99`. Sidebar: `z-index: 100`.

### Prev/Next Navigation

- Hardcoded ordered chapter array in `base.html.twig` as Twig variable or in `modern-script.js` as a JS array of `{label, url}` objects matching the 14-chapter sidebar order above
- Rendered below every page's `<article>` as two cards: ← previous | next →
- Styled as dark cards (`--bg-surface`, `--border` border) with hover highlight (`--bg-elevated`)

### Main Content

- Max width: `780px`, horizontally centered in remaining space
- Padding: `2rem`
- Diagrams may expand to full content column width (`100%` of `.content-area`)

## 3. Typography

**Fonts:** Keep existing Google Fonts (Merriweather, Nunito, JetBrains Mono)

| Element        | Font          | Size      | Weight | Color             |
|----------------|---------------|-----------|--------|-------------------|
| h1             | Merriweather  | 2.2rem    | 700    | `--text-heading`  |
| h2             | Merriweather  | 1.75rem   | 700    | `--text-heading`  |
| h3             | Merriweather  | 1.35rem   | 600    | `--text-heading`  |
| Body           | Nunito        | 16px      | 400    | `--text-primary`  |
| Sidebar links  | Nunito        | 14px      | 500    | `--text-primary`  |
| Inline code    | JetBrains Mono | 0.875em  | 400    | `--color-primary` |

**Line height:** `1.75` for body text (academic readability)

**h2** gets a `2px solid --color-primary` bottom border (Symfony signature style)

**Heading anchors:** JS in `modern-script.js` injects `<a class="anchor-link" href="#id">§</a>` after each heading that has an `id` attribute. The anchor is hidden by default, shown on heading `:hover`. CSS handles visibility; JS handles injection.

### Callout Box Class Renames

The 14 routed page templates (`templates/ddd/*.html.twig`, excluding the two unrouted orphan files `what_is_ddd_updated.html.twig` and `horizontal_vs_vertical_updated.html.twig`) must have class names updated.

Classes present in the codebase (rename required):

| Old class      | New class       | Border color              | Use case                |
|----------------|-----------------|---------------------------|-------------------------|
| `.concept-box` | `.info-card`    | `--color-accent` (purple) | Definitions, concepts   |
| `.example-box` | `.example-card` | `#f5c518` (gold)          | Code/practical examples |
| `.warning-box` | `.warning-card` | `#f59f00` (orange)        | Cautions, gotchas       |

New classes to introduce in CSS (no rename needed — use these for future content):

| New class        | Border color       | Use case        |
|------------------|--------------------|-----------------|
| `.practice-card` | `#51cf66` (green)  | Best practices  |
| `.success-card`  | `#51cf66` (green)  | Good outcomes   |

All boxes: `background: --bg-elevated`, `border-top: 3px solid <color>`, `border-radius: 8px`, `padding: 1.25rem`

**Blockquote:** `4px solid --color-primary` left border, `--text-muted` color, indented

## 4. Code Blocks

### Structure

```
┌─────────────────────────────────────┬──────┐
│  PHP · src/Domain/Order.php         │  □   │  ← header (--bg-elevated)
├─────────────────────────────────────┴──────┤
│  1  │  class Order                        │
│  2  │  {                                  │
│  3  │      private OrderId $id;           │
└─────────────────────────────────────────────┘
```

- **Header injection:** `code-script.js` detects the language class added by highlight.js (e.g. `language-php`) and injects the header `<div>` above each `<pre>`. Optional filename comes from `data-filename` attribute on `<pre>` if present (templates do not need to be updated — filename display is a progressive enhancement; if absent, only the language badge shows).
- **Language badge:** `--color-accent` (purple) background, white text, `4px` border-radius
- **Copy button:** top-right corner of header; on click shows "Zkopírováno!" for 2 seconds (keep existing Czech text, consistent with current UX), then reverts to copy icon
- **Line numbers:** `--text-muted` color, separated by a subtle `--border` right border on the line-number column
- **Syntax highlighting:** Switch highlight.js CDN stylesheet in `base.html.twig` `<head>` from `atom-one-light.min.css` to `atom-one-dark.min.css`
- **Border:** `border-radius: 8px`, `1px solid --border`, no box-shadow

## 5. Diagrams

- Container: `background: --bg-surface`, `border-radius: 8px`, `border: 1px solid --border`
- Optional `<figcaption>`: `--text-muted`, italic, centered, below diagram
- Pan/zoom (svg-pan-zoom) kept as-is

### Dark Mode SVG Fix

Existing PlantUML SVGs are generated for light backgrounds. Apply CSS filter scoped to diagram content only — **not** to the entire SVG, to avoid inverting svg-pan-zoom control icons:

```css
/* Target the main diagram group, not the svg-pan-zoom controls */
.diagram-container svg > g:first-of-type {
    filter: invert(1) hue-rotate(180deg);
}
```

If this selector does not reliably target only the diagram (svg-pan-zoom injects its controls as a separate SVG layer), fallback: apply the filter to the whole `.diagram-container svg` and separately override svg-pan-zoom control icon colors:

```css
.diagram-container svg {
    filter: invert(1) hue-rotate(180deg);
}
/* Re-invert svg-pan-zoom controls back to visible */
.diagram-container .svg-pan-zoom-control {
    filter: invert(1) hue-rotate(180deg);
}
```

Test on at least two diagrams before committing to one approach. Revisit if specific diagrams still look incorrect — fallback is adding dark theme directly to `.puml` source files.

## 6. Implementation Scope

### Files to modify

| File | Change |
|------|--------|
| `public/css/modern-style.css` | Full rewrite — dark palette, layout, typography, callout boxes, sidebar, top bar |
| `public/css/code-style.css` | Rewrite — dark code blocks, line numbers, copy button header |
| `templates/base.html.twig` | Add sidebar HTML, restructure layout wrapper, add top bar, update `theme-color` meta, swap highlight.js CDN to `atom-one-dark`, add prev/next nav data structure |
| `public/js/modern-script.js` | Add: sidebar toggle, heading anchor injection, mobile backdrop; **remove** existing copy button injection (duplicate — now handled by `code-script.js`); **update** fade-in observer selectors from `.concept-box, .warning-box, .example-box` to `.info-card, .warning-card, .example-card` |
| `public/js/code-script.js` | Add: code block header injection (language badge, copy button with "Zkopírováno!" text) |
| `templates/ddd/*.html.twig` (all 14) | Rename callout classes: `.concept-box` → `.info-card`, `.example-box` → `.example-card`, `.practice-box` → `.practice-card`, `.warning-box` → `.warning-card`, `.success-box` → `.success-card` |

### Files NOT to change

- `public/css/bootstrap-grid.css` — keep as grid utility
- All diagram `.puml` and `.svg` files (CSS filter workaround used)
- All controllers

### Responsive breakpoints

- `< 768px`: sidebar hidden, top bar shows hamburger, mobile backdrop active
- `768px – 1024px`: sidebar collapsible (starts closed)
- `> 1024px`: sidebar always visible

## 7. Out of Scope

- Dark/light mode toggle (dark only)
- Changing fonts (keep current Google Fonts)
- Modifying content or page structure
- Regenerating PlantUML diagrams (CSS filter workaround used instead)
- Adding `data-filename` attributes to existing `<pre>` tags (progressive enhancement only)
