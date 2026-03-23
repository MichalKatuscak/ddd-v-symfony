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
--bg-base: #0f0f1a;        /* main dark navy background */
--bg-surface: #161625;     /* sidebar, cards, code block bg */
--bg-elevated: #1e1e30;    /* hover states, info cards, code headers */

--color-primary: #f5c518;  /* Symfony gold — primary accent */
--color-primary-dim: #c9a010; /* hover state for primary */
--color-accent: #7c6ff7;   /* secondary accent — purple for badges/tags */

--text-primary: #e8e8f0;   /* main body text */
--text-muted: #8888a8;     /* captions, metadata, line numbers */
--text-heading: #ffffff;   /* headings */

--border: #2a2a40;         /* subtle section separators */
```

## 2. Layout

### Global Structure

```
┌──────────────────────────────────────────────┐
│  Top bar (logo + GitHub link)  [fixed, 56px] │
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

### Sidebar

- Width: `260px`, fixed left
- Background: `--bg-surface`
- Lists all 9 chapter links (Twig nav)
- Active item: `3px solid --color-primary` left border + gold text
- Mobile: hidden by default, opens as overlay with backdrop

### Main Content

- Max width: `780px`, horizontally centered in remaining space
- Padding: `2rem`
- Diagrams may expand to full content column width

### Prev/Next Navigation

- Below every page's article
- Left arrow → previous chapter, right arrow → next chapter
- Styled as dark cards with hover highlight

## 3. Typography

**Fonts:** Keep existing Google Fonts (Merriweather, Nunito, JetBrains Mono)

| Element        | Font        | Size      | Weight | Color           |
|----------------|-------------|-----------|--------|-----------------|
| h1             | Merriweather | 2.2rem   | 700    | `--text-heading` |
| h2             | Merriweather | 1.75rem  | 700    | `--text-heading` |
| h3             | Merriweather | 1.35rem  | 600    | `--text-heading` |
| Body           | Nunito       | 16px     | 400    | `--text-primary` |
| Sidebar links  | Nunito       | 14px     | 500    | `--text-primary` |
| Inline code    | JetBrains Mono | 0.875em | 400  | `--color-primary` |

**Line height:** `1.75` for body text (academic readability)

**h2** gets a `2px solid --color-primary` bottom border (Symfony signature style)

**Heading anchors:** `#` icon appears on hover (`:hover::after` pseudo-element), links to the section

### Semantic Callout Boxes

Replace current `.concept-box`, `.warning-box`, `.success-box` with:

| Class         | Top border color | Use case                  |
|---------------|-----------------|---------------------------|
| `.info-card`  | `--color-accent` (purple) | Definitions, concepts |
| `.warning-box`| `#f59f00` (orange) | Cautions, gotchas      |
| `.success-box`| `#51cf66` (green)  | Good practices         |

All boxes: `background: --bg-elevated`, `border-radius: 8px`, `padding: 1.25rem`

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

- **Header:** `--bg-elevated` background; language badge (PHP/YAML/Twig) in `--color-accent`; optional filename in `--text-muted`
- **Copy button:** top-right corner; on click shows ✓ for 2 seconds
- **Line numbers:** `--text-muted` color, separated by a subtle border
- **Syntax highlighting:** Switch highlight.js theme to `atom-one-dark` or `github-dark`
- **Border:** `border-radius: 8px`, `1px solid --border`

## 5. Diagrams

- Container: `background: --bg-surface`, `border-radius: 8px`, `border: 1px solid --border`
- Optional `<figcaption>`: `--text-muted`, italic, centered, below diagram
- Pan/zoom (svg-pan-zoom) kept as-is

### Dark Mode SVG Fix

Existing PlantUML SVGs are generated for light backgrounds. Apply CSS filter:

```css
.diagram-container svg {
    filter: invert(1) hue-rotate(180deg);
}
```

This inverts light SVGs to dark without regenerating `.puml` files. Revisit if specific diagrams look incorrect — fallback is adding dark theme directly to `.puml` source files.

## 6. Implementation Scope

### Files to modify

| File | Change |
|------|--------|
| `public/css/modern-style.css` | Full rewrite — dark palette, layout, typography, callout boxes |
| `public/css/code-style.css` | Rewrite — dark code blocks, line numbers, copy button |
| `templates/base.html.twig` | Add sidebar HTML, restructure layout, add top bar, add prev/next nav |
| `public/js/modern-script.js` | Add sidebar toggle, copy button logic, heading anchor injection |

### Files NOT to change

- Individual page templates (`templates/ddd/*.html.twig`) — class names updated to match new CSS classes where needed (`.concept-box` → `.info-card`)
- `public/css/bootstrap-grid.css` — keep as grid utility
- All diagram `.puml` and `.svg` files
- All controllers

### Responsive breakpoints

- `< 768px`: sidebar hidden, top bar shows hamburger
- `768px – 1024px`: sidebar collapsible
- `> 1024px`: sidebar always visible

## 7. Out of Scope

- Dark/light mode toggle (dark only)
- Changing fonts (keep current Google Fonts)
- Modifying content or page structure
- Regenerating PlantUML diagrams (CSS filter workaround used instead)
