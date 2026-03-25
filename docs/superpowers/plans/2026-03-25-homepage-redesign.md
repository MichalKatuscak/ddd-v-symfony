# Homepage Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign `index.html.twig` with a trust bar, two-column hero with stats panel, and a "Jak číst průvodce" reading-paths section.

**Architecture:** Pure Twig template edit + CSS additions. No JS, no build step, no database. New CSS rules go into `modern-style.css` near the existing `.hero` / `.feature-grid` / `.homepage-about` block. New HTML replaces the current `{% block body %}` content in `index.html.twig`, keeping the existing "Témata průvodce" and "O tomto průvodci" sections verbatim.

**Tech Stack:** Twig 3, CSS custom properties (existing design tokens in `:root`), Symfony 8 routing (`path()`)

**Spec:** `docs/superpowers/specs/2026-03-25-homepage-redesign.md`

---

## File Map

| File | Action | Responsibility |
|------|--------|---------------|
| `templates/ddd/index.html.twig` | Modify | Replace hero markup, prepend trust bar, insert reading-paths section |
| `public/css/modern-style.css` | Modify | Add `.trust-bar`, extend `.hero`, add `.hero-stats*`, `.reading-paths*` rules |

---

### Task 1: CSS — Trust Bar

**Files:**
- Modify: `public/css/modern-style.css` (near `.hero` block, around line 756)

- [ ] **Step 1: Add `.trust-bar` CSS rules after the existing `.hero-lead` rule**

Find the block ending at `.hero-lead { ... }` and insert after it:

```css
/* ── Trust Bar ── */
.trust-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1.5rem;
    background: var(--bg-surface);
    border-radius: var(--radius);
    padding: 0.75rem 1.5rem;
    margin-bottom: 1.5rem;
}
.trust-bar span {
    font-size: 0.78rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.trust-bar span::before {
    content: '';
    display: inline-block;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--color-primary);
    flex-shrink: 0;
}
```

- [ ] **Step 2: Commit**

```bash
git add public/css/modern-style.css
git commit -m "feat: přidat CSS pro trust bar na homepage"
```

---

### Task 2: CSS — Hero Two-Column Layout + Stats Panel

**Files:**
- Modify: `public/css/modern-style.css`

- [ ] **Step 1: Replace the entire `.hero { }` block**

Find the existing `.hero` block and **replace the whole block** (not just append) with:

```css
.hero {
    padding: 0 0 2rem;
    border-bottom: 1px solid var(--border);
    margin-bottom: 2.5rem;
    display: flex;
    gap: 2.5rem;
    align-items: center;
}
```

- [ ] **Step 2: Add hero sub-element rules after the `.hero` block**

After the `.hero h1` and `.hero-lead` rules, insert:

```css
.hero-text { flex: 1; }

.hero-eyebrow {
    font-size: 0.72rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--color-primary);
    margin-bottom: 0.75rem;
}

.hero h1 span { color: var(--color-primary); }

.hero-stats {
    flex: 0 0 210px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.hero-stats-item {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}
.hero-stats-num {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--color-primary);
    line-height: 1;
}
.hero-stats-desc {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted);
}
```

- [ ] **Step 3: Commit**

```bash
git add public/css/modern-style.css
git commit -m "feat: přidat CSS pro dvousloupcový hero se stats panelem"
```

---

### Task 3: CSS — Reading Paths Section

**Files:**
- Modify: `public/css/modern-style.css`

- [ ] **Step 1: Add reading-paths rules after the hero stats block**

```css
/* ── Reading Paths ── */
.reading-paths-section { margin-bottom: 2.5rem; }

.reading-paths {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-top: 1rem;
}
.reading-path-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
    border-left-width: 4px;
    border-left-style: solid;
}
.reading-path-card--beginner { border-left-color: var(--color-primary); }
.reading-path-card--advanced { border-left-color: #6655aa; }

.reading-path-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--text-muted);
    margin-bottom: 0.4rem;
}
.reading-path-card h3 {
    font-size: 1.1rem;
    border-bottom: none;
    margin-top: 0;
    padding-bottom: 0;
    margin-bottom: 1rem;
}
.reading-path-card ol {
    padding-left: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 0;
}
.reading-path-card ol li a {
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.9rem;
}
.reading-path-card ol li a:hover { color: var(--color-primary); }
.reading-path-card--beginner ol { color: var(--color-primary); }
.reading-path-card--advanced ol { color: #6655aa; }
```

- [ ] **Step 2: Add mobile breakpoints**

The stylesheet has no `768px` breakpoint — only `576px`. Create a **new** `@media (max-width: 768px)` block at the end of the homepage section (after `.homepage-about h2`):

```css
@media (max-width: 768px) {
    .hero { flex-direction: column; }
    .hero-stats { flex: none; width: 100%; }
    .reading-paths { grid-template-columns: 1fr; }
}
```

Do **not** merge into the existing `576px` block — that would break the tablet layout.

- [ ] **Step 3: Commit**

```bash
git add public/css/modern-style.css
git commit -m "feat: přidat CSS pro reading paths sekci a mobilní breakpointy"
```

---

### Task 4: HTML — Rewrite `index.html.twig` body

**Files:**
- Modify: `templates/ddd/index.html.twig`

- [ ] **Step 1: Replace the entire `{% block body %}...{% endblock %}` content**

Keep all blocks above (`{% block title %}`, `{% block meta_description %}`, `{% block meta_keywords %}`, `{% block structured_data %}`) unchanged.

Replace only `{% block body %}...{% endblock %}` with:

```twig
{% block body %}
<div class="trust-bar">
    <span>PHP 8.4 &amp; Symfony 8</span>
    <span>15 kapitol s ukázkami kódu</span>
    <span>CQRS · Event Sourcing · Taktický design</span>
    <span>100% česky · zdarma</span>
</div>

<section class="hero" aria-labelledby="hero-heading">
    <div class="hero-text">
        <p class="hero-eyebrow">Komplexní průvodce · česky</p>
        <h1 id="hero-heading">Domain-Driven Design v <span>Symfony 8</span></h1>
        <p class="hero-lead">Od základních konceptů přes CQRS a Event Sourcing až po reálné případové studie. Vše s ukázkami kódu v PHP 8.4 a Symfony 8.</p>
        <a href="{{ path('what_is_ddd') }}" class="btn btn-primary btn-lg">Začít s DDD →</a>
    </div>
    <div class="hero-stats">
        <div class="hero-stats-item">
            <span class="hero-stats-num">15</span>
            <span class="hero-stats-desc">kapitol</span>
        </div>
        <div class="hero-stats-item">
            <span class="hero-stats-num">PHP 8.4</span>
            <span class="hero-stats-desc">ukázky kódu</span>
        </div>
        <div class="hero-stats-item">
            <span class="hero-stats-num">100%</span>
            <span class="hero-stats-desc">česky</span>
        </div>
        <div class="hero-stats-item">
            <span class="hero-stats-num">CQRS+ES</span>
            <span class="hero-stats-desc">pokryto</span>
        </div>
    </div>
</section>

<section class="reading-paths-section" aria-labelledby="reading-paths-heading">
    <h2 id="reading-paths-heading">Jak číst průvodce</h2>
    <div class="reading-paths">
        <div class="reading-path-card reading-path-card--beginner">
            <p class="reading-path-label">Pro začátečníky</p>
            <h3>Začínám s DDD</h3>
            <ol>
                <li><a href="{{ path('what_is_ddd') }}">Co je Domain-Driven Design?</a></li>
                <li><a href="{{ path('basic_concepts') }}">Základní koncepty DDD</a></li>
                <li><a href="{{ path('implementation_in_symfony') }}">Implementace v Symfony 8</a></li>
            </ol>
        </div>
        <div class="reading-path-card reading-path-card--advanced">
            <p class="reading-path-label">Pro pokročilé</p>
            <h3>Znám základy</h3>
            <ol>
                <li><a href="{{ path('cqrs') }}">CQRS v Symfony 8</a></li>
                <li><a href="{{ path('event_sourcing') }}">Event Sourcing</a></li>
                <li><a href="{{ path('anti_patterns') }}">Anti-vzory a typické chyby</a></li>
            </ol>
        </div>
    </div>
</section>

<section aria-labelledby="features-heading">
    <h2 id="features-heading">Témata průvodce</h2>
    <div class="feature-grid">
        <a href="{{ path('what_is_ddd') }}" class="feature-card">
            <h3 class="feature-card-title">Co je Domain-Driven Design?</h3>
            <p class="feature-card-desc">Základní filozofie DDD, Ubiquitous Language a Bounded Context.</p>
        </a>
        <a href="{{ path('basic_concepts') }}" class="feature-card">
            <h3 class="feature-card-title">Základní koncepty DDD</h3>
            <p class="feature-card-desc">Entity, Value Objects, Agregáty, Repozitáře a Doménové události.</p>
        </a>
        <a href="{{ path('cqrs') }}" class="feature-card">
            <h3 class="feature-card-title">CQRS v Symfony 8</h3>
            <p class="feature-card-desc">Oddělení operací čtení a zápisu pomocí Messenger komponenty.</p>
        </a>
        <a href="{{ path('event_sourcing') }}" class="feature-card">
            <h3 class="feature-card-title">Event Sourcing</h3>
            <p class="feature-card-desc">Uchování stavu aplikace jako sekvence doménových událostí.</p>
        </a>
        <a href="{{ path('implementation_in_symfony') }}" class="feature-card">
            <h3 class="feature-card-title">Implementace v Symfony 8</h3>
            <p class="feature-card-desc">Praktická implementace DDD architektury ve Symfony projektu.</p>
        </a>
        <a href="{{ path('glossary') }}" class="feature-card">
            <h3 class="feature-card-title">Glosář DDD terminologie</h3>
            <p class="feature-card-desc">Přehled všech klíčových pojmů DDD s vysvětlením v češtině.</p>
        </a>
    </div>
</section>

<section class="homepage-about" aria-labelledby="about-heading">
    <h2 id="about-heading">O tomto průvodci</h2>
    <p>Průvodce je určen PHP vývojářům a Symfony vývojářům, kteří chtějí implementovat Domain-Driven Design v reálných projektech. Pokrývá strategický i taktický design, CQRS, Event Sourcing a testování DDD kódu — vše s příklady v PHP 8.4 a Symfony 8.</p>
</section>
{% endblock %}
```

- [ ] **Step 2: Verify the page renders without errors**

Open `https://localhost:8000/` in the browser. Check:
- No Twig exceptions (all `path()` calls resolve)
- Trust bar visible at top
- Hero shows two columns (text left, stats panel right)
- "Jak číst průvodce" section with two cards below hero
- "Témata průvodce" grid still present
- "O tomto průvodci" block still present

- [ ] **Step 3: Commit**

```bash
git add templates/ddd/index.html.twig
git commit -m "feat: redesign homepage — trust bar, dvousloupcový hero, reading paths"
```

---

### Task 5: Visual QA

**Files:** none (browser verification only)

- [ ] **Step 1: Check desktop layout**

Visit `https://localhost:8000/`. Verify:
- Trust bar: single horizontal row with gold dots, readable
- Hero: two columns — text on left, stats panel on right (surface background, gold numbers)
- Stats panel not taller than hero text
- Hero h1 has gold "Symfony 8" span
- Reading paths: two cards side by side — gold left border (beginner), purple left border (advanced)
- Numbered list items are links, hover turns gold
- Feature grid and "O tomto průvodci" unchanged

- [ ] **Step 2: Check mobile layout (DevTools)**

Open Chrome DevTools → toggle device toolbar → set width to 375px. Verify:
- Trust bar wraps to multiple lines
- Hero collapses to single column (stats below text)
- Reading path cards stack vertically

- [ ] **Step 3: Final commit if any tweaks made**

```bash
git add public/css/modern-style.css templates/ddd/index.html.twig
git commit -m "fix: vizuální doladění homepage po QA"
```
