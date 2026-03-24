# Design Spec: Akademický UI Redesign (Symfony Docs styl)

**Datum:** 2026-03-24
**Projekt:** DDD v Symfony — vzdělávací web pro vysokou školu
**Cíl:** Přestavět UI do stylu Symfony docs — tmavý theme zachován, 3-sloupcový layout, sticky pravý TOC, profesionální akademická prezentace

---

## 1. Kontext a motivace

Web je vzdělávací průvodce DDD v Symfony, psaný česky. Aktuální design je funkční, ale trpí několika problémy:

- **Duplicitní navigace na homepage** — sidebar, inline TOC i pravý sloupec ukazují totéž
- **Chybí sticky pravý TOC** — uživatel při scrollování dlouhých kapitol ztrácí orientaci
- **Breadcrumby jsou vizuálně skryty** — `.breadcrumb-container` používá CSS accessible-hide (`position: absolute; width: 1px; height: 1px; clip: rect(0,0,0,0)`) — jsou zcela neviditelné
- **Callout boxy** nemají ikony — všechny vypadají podobně

Stávající stav (co JIŽ je implementováno a neměníme):
- Chapter nav jako karty (← Předchozí / Další →) — JS `modern-script.js` řádky 128–154, CSS řádky 642–693
- Kotvy `.anchor-link` s `opacity: 0` + hover reveal — JS řádky 67–75, CSS řádky 175–189
- 15 kapitol v `CHAPTERS` array včetně Výkonnostních aspektů — `modern-script.js` řádky 104–120

Inspirace: [Symfony Docs](https://symfony.com/doc/current/index.html) — světlá verze, ale implementujeme tmavý theme.

---

## 2. Architektura layoutu

### 2.1 Tři sloupce (inner pages)

```
┌─────────────────────────────────────────────────────────┐
│                    FIXED HEADER (56px)                   │
├──────────┬──────────────────────────────┬────────────────┤
│          │                              │                │
│  LEFT    │      MAIN CONTENT            │  RIGHT TOC     │
│ SIDEBAR  │      (max 820px)             │  (220px)       │
│ (260px)  │                              │  sticky        │
│ fixed    │  breadcrumb (viditelné)      │                │
│          │  <article>                   │  Na této       │
│  nav     │    h2, h3 sections           │  stránce:      │
│  links   │    callouts                  │  - Sekce 1     │
│          │    code blocks               │  - Sekce 2     │
│          │    diagrams                  │  - Sekce 3     │
│          │  </article>                  │  ...           │
│          │  chapter nav (existující)    │                │
└──────────┴──────────────────────────────┴────────────────┘
```

- Levý sidebar: zachovává stávající `position: fixed; width: 260px`
- Pravý TOC: `position: sticky; top: 72px; width: 220px; align-self: start`
- Obsah: zachovává stávající `margin-left: 260px`; `max-width` mění z 780px na **820px**
- Footer: zachovává stávající `margin-left: 260px`

### 2.2 HTML struktura v base.html.twig

Aktuální `base.html.twig` má jednoduchou `<main class="content-area">`. Nová struktura:

```html
<div class="page-wrapper">
  <aside class="sidebar">...</aside>          {# beze změny #}
  <div class="main-with-toc">
    <main class="content-area">
      {% block body %}{% endblock %}
    </main>
    <aside class="toc-sidebar" aria-label="Na této stránce">
      {% block toc %}{% endblock %}
    </aside>
  </div>
</div>
```

**Twig blok `toc`:**
- V `base.html.twig` je prázdný (fallback)
- Na vnitřních stránkách obsahuje pouze `<p class="toc-title">Na této stránce</p>` — JS `toc.js` do stejného `<aside>` doplní `<ul>` seznam
- Na **homepage** blok `toc` není přepsán → `<aside class="toc-sidebar">` obsahuje jen prázdný title; `toc.js` zjistí, že `<ul>` je prázdný, a přidá třídu `no-toc` na `<aside>` → CSS třídu `no-toc` skryje (`display: none`)

**Skrytí pravého TOC na homepage** — JS přístup (ne CSS `:empty`):
```js
// toc.js: po vygenerování listu
if (tocList.children.length === 0) {
  tocSidebar.classList.add('no-toc');
}
```
```css
.toc-sidebar.no-toc { display: none; }
```

### 2.3 Homepage layout

Homepage **nemá** pravý TOC (blok `toc` nepřepsán). Layout je 2-sloupcový (sidebar + obsah). Obsah:

1. **Hero sekce** — název průvodce, perex (2 věty), CTA tlačítko "Začít s DDD →"
2. **Feature grid** — 6 karet v CSS Grid `grid-template-columns: repeat(2, 1fr)` (ne Bootstrap) pro klíčová témata: Co je DDD, Základní koncepty, CQRS, Event Sourcing, Implementace v Symfony, Glosář
3. **O průvodci** — krátký odstavec kdo je cílová skupina

Odstraní se: duplicitní inline TOC seznam, pravý sloupec s "Rychlý přehled" a "Proč číst".

---

## 3. Komponenty

### 3.1 Callout boxy — přejmenování tříd (druhý rename)

Aktuální třídy (post-první-redesign, v JS `modern-script.js` řádek 89 a v 17 Twig šablonách) → nové třídy:

| Aktuální třída CSS | Nová třída CSS | Ikona | Barva borderu | Sémantika |
|--------------------|---------------|-------|---------------|-----------|
| `.info-card` | `.note` | `&#x2139;` (ℹ) | `--color-accent` (#7c6ff7) | Doplňující informace |
| `.example-card` | `.tip` | `&#x25BA;` (►) | `#22c55e` | Tipy, příklady, best practices |
| `.practice-card` | `.tip` | `&#x25BA;` (►) | `#22c55e` | Praktické tipy |
| `.warning-card` | `.warning` | `&#x26A0;` (⚠) | `#f59e0b` | Upozornění |
| `.success-card` | `.note` | `&#x2139;` (ℹ) | `--color-accent` (#7c6ff7) | Pozitivní/doplňující informace |
| `.caution` (nová třída) | `.caution` | `&#x26D4;` (⛔) | `#ef4444` | Kritická varování |

**Pořadí implementace callout migrace:**
1. Nejprve smazat orphaned templates (viz sekce 6)
2. Pak spustit rename přes zbývající šablony

**Aktualizace `modern-script.js` řádek 89** — selektor fade-in observeru změnit z:
```js
'.card, .info-card, .warning-card, .example-card, .practice-card, .success-card'
```
na:
```js
'.card, .note, .tip, .warning, .caution'
```

### 3.2 Kotvy — jen změna symbolu

Systém `.anchor-link` s hover-only reveal **je již implementován** (JS řádky 67–75, CSS řádky 175–189). Jedinou změnou je symbol v JS `modern-script.js` řádek 72:

```js
// Změna: '§' → '#'
anchorLink.textContent = '#';
```

### 3.3 Pravý sticky TOC (nový soubor toc.js)

Nový soubor `public/js/toc.js`:

```
1. Najde <aside class="toc-sidebar"> — pokud neexistuje, okamžitě skončí
2. Vybere všechny h2[id] a h3[id] uvnitř .content-area article
3. Vygeneruje <ul> s vnořenými h3 pod jejich nadřazeným h2
4. Pokud je vygenerovaný seznam prázdný → přidá class "no-toc" na aside a skončí
5. Vloží seznam za .toc-title element uvnitř aside
6. IntersectionObserver sleduje h2/h3 a přidává class "active" na odpovídající TOC odkaz
   - Aktivní odkaz: color: var(--color-primary); font-weight: 600
```

**Heading IDs jsou přítomny přímo v Twig šablonách** — `toc.js` je negeneruje, jen čte.

`toc.js` je načten v `base.html.twig` — ale díky kroku 1 (kontrola přítomnosti `.toc-sidebar`) bezpečně funguje i na stránkách bez TOC.

### 3.4 Breadcrumby — zpřístupnění

Aktuální stav: `.breadcrumb-container` je vizuálně skrytý v `modern-style.css` (accessible-hide pattern). Odebrat toto CSS a nahradit viditelným stylem:

```css
/* Odebrat ze .breadcrumb-container: position/width/height/overflow/clip/white-space */
.breadcrumb-container {
  font-size: 0.875rem;
  color: var(--text-muted);
  padding-bottom: 1rem;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--border);
}
.breadcrumb-item + .breadcrumb-item::before { content: " / "; color: var(--text-muted); }
.breadcrumb-item.active { color: var(--text-primary); }
```

### 3.5 Homepage Feature Grid

CSS Grid (ne Bootstrap grid třídy):

```css
.feature-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
  margin: 2rem 0;
}
@media (max-width: 576px) {
  .feature-grid { grid-template-columns: 1fr; }
}
```

---

## 4. Typografie a barvy

Paleta zůstává beze změny. Drobné úpravy:
- Body `line-height`: 1.75 → **1.85**
- `max-width` obsahu: 780px → **820px** *(deliberate change)*
- Pravý TOC font-size: **0.8rem**

---

## 5. Responsive chování

Zachováváme stávající breakpointy z `modern-style.css` — **nepřidáváme nové** (stávající jsou 1024px a 576px):

| Breakpoint | Chování |
|-----------|---------|
| ≥ 1024px | Plný 3-sloupcový layout (sidebar + content + right TOC) |
| < 1024px | Pravý TOC skryt (`display: none`); stávající hamburger logika pro levý sidebar |
| < 576px | Single column; feature grid 1 sloupec |

---

## 6. Rozsah implementace

### Soubory ke smazání (PRVNÍ krok)

| Soubor | Důvod |
|--------|-------|
| `templates/ddd/what_is_ddd_updated.html.twig` | Orphaned — bez routy v DddController |
| `templates/ddd/horizontal_vs_vertical_updated.html.twig` | Orphaned — bez routy v DddController |

### Soubory k úpravě

| Soubor | Změna |
|--------|-------|
| `public/css/modern-style.css` | `.main-with-toc` wrapper, `.toc-sidebar` styl, callout třídy (přejmenování), breadcrumb viditelnost, `max-width` 820px |
| `templates/base.html.twig` | Přidat `.main-with-toc` wrapper, `<aside class="toc-sidebar">`, `{% block toc %}` |
| `templates/ddd/index.html.twig` | Hero sekce + feature grid, odstranit duplicity |
| `public/js/modern-script.js` | Řádek 89: selektor callout tříd → `.note, .tip, .warning, .caution`; řádek 72: symbol `§` → `#` |
| Všechny `templates/ddd/*.html.twig` (13 vnitřních stránek) | Přejmenovat callout třídy; přidat `{% block toc %}<p class="toc-title">Na této stránce</p>{% endblock %}` |

### Nové soubory

| Soubor | Obsah |
|--------|-------|
| `public/js/toc.js` | Generování TOC + IntersectionObserver scroll-spy |

---

## 7. Akceptační kritéria

1. Pravý TOC se zobrazuje na všech vnitřních stránkách (ne na homepage) a zvýrazňuje aktivní sekci při scrollování
2. Homepage obsahuje hero sekci + feature grid 2×3 (CSS Grid); žádná duplicitní navigace
3. Kotvy zobrazí `#` pouze při hoveru nad nadpisem
4. Callout boxy mají Unicode ikony a jsou vizuálně odlišeny (4 typy: note, tip, warning, caution)
5. Breadcrumby jsou vizuálně viditelné a čitelné
6. Chapter nav karty (← Předchozí / Další →) fungují — beze změny stávajícího JS
7. Na viewportu < 1024px pravý TOC zmizí; sidebar je za hamburgerem
8. `php bin/console cache:clear` proběhne bez chyb
9. `modern-script.js`: selektor callout tříd a symbol kotvy jsou aktualizovány
