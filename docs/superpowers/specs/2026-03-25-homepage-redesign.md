# Homepage Redesign — Design Spec

**Date:** 2026-03-25
**Scope:** `templates/ddd/index.html.twig` + `public/css/modern-style.css`

## Goal

Redesign the homepage into a technical landing page that signals credibility, while keeping the existing dark/gold visual language. Both visual improvement and content expansion are in scope.

## Page Structure

All sections render inside `{% block body %}` in `index.html.twig`, inside `content-area` (standard sidebar offset). Insertion order:

1. Trust bar ← new
2. Hero ← reworked
3. "Jak číst průvodce" ← new
4. "Témata průvodce" ← existing, no changes
5. "O tomto průvodci" ← existing, no changes

---

### 1. Trust Bar

```html
<div class="trust-bar">
  <span>PHP 8.4 &amp; Symfony 8</span>
  <span>15 kapitol s ukázkami kódu</span>
  <span>CQRS · Event Sourcing · Taktický design</span>
  <span>100% česky · zdarma</span>
</div>
```

CSS:
```css
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

---

### 2. Hero Section

Wrapper: `<section class="hero" aria-labelledby="hero-heading">` — **existing class, extended with flex layout**.

```html
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
```

CSS — extend existing `.hero` rule, add new helpers:
```css
/* Extend existing .hero (keep padding, border-bottom, margin-bottom) */
.hero {
    display: flex;
    gap: 2.5rem;
    align-items: center;
}
.hero-text { flex: 1; }

.hero-eyebrow {
    font-size: 0.72rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--color-primary);
    margin-bottom: 0.75rem;
}
/* .hero h1 already has margin-top:0, border-bottom:none, padding-bottom:0 */
/* .hero-lead already exists */
/* .btn-lg already exists: padding:0.75rem 1.75rem; font-size:1.1rem */

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

---

### 3. "Jak číst průvodce" Section

Route names verified against `DddController.php`: `what_is_ddd`, `basic_concepts`, `implementation_in_symfony`, `cqrs`, `event_sourcing`, `anti_patterns` — all exist.

```html
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
```

CSS:
```css
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

---

## Mobile (max-width: 768px)

```css
@media (max-width: 768px) {
    .hero { flex-direction: column; }
    /* .hero-stats renders below .hero-text — correct DOM order */
    .hero-stats { flex: none; width: 100%; }
    .reading-paths { grid-template-columns: 1fr; }
}
```

---

## Constraints

- Pure Twig — no JS, no build step
- All new markup in `{% block body %}` of `index.html.twig` only
- Existing SEO blocks, structured data, ARIA on homepage remain unchanged
- `.btn-lg` exists in `modern-style.css` (line 337)
- `--text-muted: #8888a8` exists in `:root`
