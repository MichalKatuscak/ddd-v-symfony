# Academic UI Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Přestavět UI vzdělávacího webu DDD/Symfony na 3-sloupcový layout inspirovaný Symfony docs — sticky pravý TOC, viditelné breadcrumby, callout boxy s ikonami, redesign homepage. Při tom zachovat nebo zlepšit Google PageSpeed skóre.

**Architecture:** Čistý front-end redesign bez změny backendu. Nový `.main-with-toc` wrapper v `base.html.twig` přidá pravý `<aside class="toc-sidebar">` s Twig blokem `toc`. JavaScript `toc.js` je inlinován přímo do `base.html.twig` (úspora 1 HTTP requestu) a čte existující `h3[id]` atributy ze stránky.

**Tech Stack:** Twig, CSS custom properties (bez preprocessoru), vanilla JS, Symfony 8, PHP 8.4

**Spec:** `docs/superpowers/specs/2026-03-24-academic-ui-redesign-design.md`

**Verify after each task:** `php bin/console cache:clear` nesmí vrátit chybu.

---

## File Map

| Soubor | Akce | Účel |
|--------|------|------|
| `templates/ddd/what_is_ddd_updated.html.twig` | DELETE | Orphaned — bez routy |
| `templates/ddd/horizontal_vs_vertical_updated.html.twig` | DELETE | Orphaned — bez routy |
| `templates/ddd/*.html.twig` (14 vnitřních, viz Task 9) | MODIFY | Přejmenovat callout třídy + přidat `{% block toc %}` |
| `templates/ddd/index.html.twig` | REWRITE | Hero sekce + feature grid |
| `templates/base.html.twig` | MODIFY | `.main-with-toc` wrapper + `<aside class="toc-sidebar">` + `{% block toc %}` + inline toc.js |
| `public/css/modern-style.css` | MODIFY | Layout, callout CSS, breadcrumbs, TOC sidebar, .btn-lg |
| `public/js/modern-script.js` | MODIFY | Callout selektor řádek 89, kotva symbol řádek 72 |

**Poznámka k PageSpeed:** `toc.js` NEBUDE samostatný soubor — bude inlinován v `{% block javascripts %}` v `base.html.twig` jako `<script>` tag, aby se předešlo extra HTTP requestu. Všechny nové CSS jsou čistě additivní, žádné nové externí zdroje.

---

## Task 1: Smazat orphaned templates

**Files:**
- Delete: `templates/ddd/what_is_ddd_updated.html.twig`
- Delete: `templates/ddd/horizontal_vs_vertical_updated.html.twig`

- [ ] **Step 1: Ověřit že soubory nemají routu**

```bash
grep -r "what_is_ddd_updated\|horizontal_vs_vertical_updated" src/
```
Expected: žádný výsledek (prázdný výstup)

- [ ] **Step 2: Smazat oba soubory**

```bash
rm templates/ddd/what_is_ddd_updated.html.twig
rm templates/ddd/horizontal_vs_vertical_updated.html.twig
```

- [ ] **Step 3: Ověřit cache clear**

```bash
php bin/console cache:clear
```
Expected: "Cache was successfully cleared."

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "chore: smazat orphaned šablony bez routy"
```

---

## Task 2: Přejmenovat callout třídy v šablonách

Mapování aktuálních tříd na nové:
- `info-card` → `note`
- `example-card` → `tip` (včetně `example-card mt-5` → `tip mt-5`)
- `practice-card` → `tip`
- `warning-card` → `warning`
- `success-card` → `note`

**Zpracovávané soubory:** Všechny `templates/ddd/*.html.twig` **kromě `index.html.twig`** (ten se kompletně přepíše v Task 10).

**Files:**
- Modify: `templates/ddd/*.html.twig` (14 vnitřních šablon, index.html.twig výslovně vynecháme)
- Modify: `public/js/modern-script.js:89`

- [ ] **Step 1: Spustit rename přes šablony (PowerShell, bez index.html.twig)**

```powershell
Get-ChildItem -Path "templates/ddd" -Filter "*.html.twig" -Exclude "index.html.twig" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $content = $content -replace 'class="info-card"', 'class="note"'
    $content = $content -replace 'class="example-card mt-5"', 'class="tip mt-5"'
    $content = $content -replace 'class="example-card"', 'class="tip"'
    $content = $content -replace 'class="practice-card"', 'class="tip"'
    $content = $content -replace 'class="warning-card"', 'class="warning"'
    $content = $content -replace 'class="success-card"', 'class="note"'
    Set-Content $_.FullName $content -NoNewline
}
```

**Důležité:** `example-card mt-5` musí být PŘED `example-card` (specifičtější vzor první).

- [ ] **Step 2: Ověřit že staré třídy zmizely (mimo index.html.twig)**

```bash
grep -r "info-card\|example-card\|practice-card\|warning-card\|success-card" templates/ddd/ --exclude="index.html.twig"
```
Expected: žádný výsledek

- [ ] **Step 3: Aktualizovat modern-script.js řádek 89**

Otevřít `public/js/modern-script.js`. Najít řádek 89:
```js
'.card, .info-card, .warning-card, .example-card, .practice-card, .success-card'
```
Změnit na:
```js
'.card, .note, .tip, .warning, .caution'
```

- [ ] **Step 4: Cache clear**

```bash
php bin/console cache:clear
```

- [ ] **Step 5: Commit**

```bash
git add templates/ddd/ public/js/modern-script.js
git commit -m "refactor: přejmenovat callout CSS třídy na note/tip/warning/caution"
```

---

## Task 3: Aktualizovat CSS callout bloků + přidat ikony

**Files:**
- Modify: `public/css/modern-style.css:141-172`

- [ ] **Step 1: Nahradit callout CSS blok**

V `public/css/modern-style.css` nahradit celý blok od řádku 141 do 172 (`.info-card, .example-card...` až po `.success-card h3 { ... }`):

```css
/* Callout cards */
.note,
.tip,
.warning,
.caution {
    background: var(--bg-elevated);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem 1.25rem 3.5rem;
    margin: 1.75rem 0;
    border: 1px solid var(--border);
    position: relative;
}

.note::before,
.tip::before,
.warning::before,
.caution::before {
    position: absolute;
    left: 1.1rem;
    top: 1.2rem;
    font-size: 1.1rem;
    line-height: 1;
}

.note    { border-left: 4px solid var(--color-accent); }
.note::before { content: "\2139"; color: var(--color-accent); }

.tip     { border-left: 4px solid #22c55e; }
.tip::before  { content: "\25BA"; color: #22c55e; }

.warning { border-left: 4px solid #f59e0b; }
.warning::before { content: "\26A0"; color: #f59e0b; }

.caution { border-left: 4px solid #ef4444; }
.caution::before { content: "\26D4"; color: #ef4444; }

.note h3, .note h4,
.tip h3, .tip h4,
.warning h3, .warning h4,
.caution h3, .caution h4 {
    margin-top: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-heading);
}
```

- [ ] **Step 2: Ověřit vizuálně v prohlížeči**

Otevřít `http://localhost:8080/zakladni-koncepty` — callout boxy musí mít ikony a barevné bordery.

- [ ] **Step 3: Commit**

```bash
git add public/css/modern-style.css
git commit -m "feat: callout boxy s Unicode ikonami (note/tip/warning/caution)"
```

---

## Task 4: Změnit symbol kotvy § → #

**Files:**
- Modify: `public/js/modern-script.js:72`

- [ ] **Step 1: Změnit symbol**

V `public/js/modern-script.js` na řádku 72 změnit:
```js
anchorLink.textContent = '§';
```
na:
```js
anchorLink.textContent = '#';
```

- [ ] **Step 2: Ověřit v prohlížeči**

Otevřít `/co-je-ddd`, najet myší nad nadpis — symbol musí být `#`.

- [ ] **Step 3: Commit**

```bash
git add public/js/modern-script.js
git commit -m "fix: symbol kotvy § → # (Symfony docs styl)"
```

---

## Task 5: Zpřístupnit breadcrumby vizuálně

**Files:**
- Modify: `public/css/modern-style.css:266-277`

- [ ] **Step 1: Nahradit CSS accessible-hide breadcrumbů**

V `public/css/modern-style.css` najít blok (řádky 266–277):
```css
/* Breadcrumbs (visually hidden, for SEO) */
.breadcrumb-container {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
}
```
Nahradit:
```css
/* Breadcrumbs */
.breadcrumb-container {
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}
.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 0.25rem;
}
.breadcrumb-item a { color: var(--text-muted); }
.breadcrumb-item a:hover { color: var(--color-primary); }
.breadcrumb-item + .breadcrumb-item::before {
    content: "/";
    color: var(--text-muted);
    padding-right: 0.25rem;
}
.breadcrumb-item.active { color: var(--text-primary); }
```

- [ ] **Step 2: Ověřit v prohlížeči**

Otevřít `/co-je-ddd` — breadcrumb "Hlavní stránka / Co je Domain-Driven Design?" musí být viditelný.

- [ ] **Step 3: Commit**

```bash
git add public/css/modern-style.css
git commit -m "feat: breadcrumby viditelné (odebrat accessible-hide)"
```

---

## Task 6: Restrukturalizovat base.html.twig (3-sloupcový layout)

**Files:**
- Modify: `templates/base.html.twig`

Přesný popis změny (bez přepisování existujícího obsahu):

1. Na řádku **117** (za `</aside>` která zavírá `.sidebar`) vložit `<div class="main-with-toc">` na nový řádek
2. Na řádku **158** (za `</main><!-- /.content-area -->`) vložit:
   ```html
           <aside class="toc-sidebar" aria-label="Na této stránce">
               {% block toc %}{% endblock %}
           </aside>
       </div><!-- /.main-with-toc -->
   ```
   A **smazat** původní `</div><!-- /.page-wrapper -->` řádek 158 — ten zůstane **za** novým `</div><!-- /.main-with-toc -->`.

Výsledná struktura (schematicky, bez změny existujícího obsahu):
```html
<div class="page-wrapper">

    <aside class="sidebar" ...> {# beze změny, řádky 67–117 #}
    </aside>

    <div class="main-with-toc">              {# NOVÉ — vložit za řádek 117 #}

        <main class="content-area">          {# existující, beze změny #}
            ... breadcrumb, JSON-LD, {% block body %} ...
        </main><!-- /.content-area -->

        <aside class="toc-sidebar" ...>      {# NOVÉ — vložit za </main> #}
            {% block toc %}{% endblock %}
        </aside>

    </div><!-- /.main-with-toc -->            {# NOVÉ #}

</div><!-- /.page-wrapper -->                {# existující closing div #}
```

- [ ] **Step 1: Provést změny v base.html.twig**

Použít Edit tool (ne ruční přepis) — dvě přesné úpravy:

**Úprava A:** Za řádek 117 (`        </aside>`) vložit:
```html
        <div class="main-with-toc">
```

**Úprava B:** Nahradit řádek 157-158:
```html
        </main><!-- /.content-area -->
    </div><!-- /.page-wrapper -->
```
za:
```html
        </main><!-- /.content-area -->

            <aside class="toc-sidebar" aria-label="Na této stránce">
                {% block toc %}{% endblock %}
            </aside>

        </div><!-- /.main-with-toc -->
    </div><!-- /.page-wrapper -->
```

- [ ] **Step 2: Přidat inline toc.js skript**

**PageSpeed důvod:** Inlinujeme TOC script přímo do base.html.twig místo samostatného souboru — šetříme 1 HTTP request. Script je malý (~1.5 KB) a používá `defer`-ekvivalent přes `DOMContentLoaded`.

Za řádek s `modern-script.js` scriptem (řádek 172 v originále), uvnitř `{% block javascripts %}{% endblock %}` bloku, přidat **před** `{% block javascripts %}`:

```html
    <script>
    (function () {
        'use strict';
        document.addEventListener('DOMContentLoaded', function () {
            var tocSidebar = document.querySelector('.toc-sidebar');
            if (!tocSidebar) return;
            var article = document.querySelector('.content-area article');
            if (!article) { tocSidebar.classList.add('no-toc'); return; }
            var headings = Array.prototype.slice.call(article.querySelectorAll('h2[id], h3[id]'));
            if (headings.length === 0) { tocSidebar.classList.add('no-toc'); return; }
            var ul = document.createElement('ul');
            ul.className = 'toc-list';
            headings.forEach(function (heading) {
                var li = document.createElement('li');
                if (heading.tagName === 'H3') li.classList.add('toc-h3');
                var a = document.createElement('a');
                a.href = '#' + heading.id;
                // Extract text only from text nodes (skip injected anchor-link elements)
                var text = '';
                heading.childNodes.forEach(function (node) {
                    if (node.nodeType === 3) text += node.textContent;
                });
                a.textContent = text.trim();
                li.appendChild(a);
                ul.appendChild(li);
            });
            var titleEl = tocSidebar.querySelector('.toc-title');
            if (titleEl) { titleEl.after(ul); } else { tocSidebar.appendChild(ul); }
            var links = Array.prototype.slice.call(ul.querySelectorAll('a'));
            var activeLink = null;
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var id = entry.target.id;
                    var link = null;
                    for (var i = 0; i < links.length; i++) {
                        if (links[i].getAttribute('href') === '#' + id) { link = links[i]; break; }
                    }
                    if (!link) return;
                    if (activeLink) activeLink.classList.remove('active');
                    activeLink = link;
                    activeLink.classList.add('active');
                });
            }, { rootMargin: '-56px 0px -70% 0px', threshold: 0 });
            headings.forEach(function (h) { observer.observe(h); });
        });
    }());
    </script>
```

- [ ] **Step 3: Cache clear + ověřit žádné Twig chyby**

```bash
php bin/console cache:clear
```

- [ ] **Step 4: Commit**

```bash
git add templates/base.html.twig
git commit -m "feat: 3-sloupcový layout — .main-with-toc wrapper + toc-sidebar + inline toc.js"
```

---

## Task 7: CSS pro 3-sloupcový layout + TOC sidebar

**Files:**
- Modify: `public/css/modern-style.css`

- [ ] **Step 1: Nahradit `.content-area` a přidat `.main-with-toc` + `.toc-sidebar`**

Najít sekci `.content-area` (řádky 517–524). Nahradit:
```css
/* Content Area */
.content-area {
    flex: 1;
    margin-left: 260px;
    padding: 2.5rem 2rem;
    max-width: calc(780px + 4rem); /* 780px content + padding */
    min-width: 0;
}
```
Za:
```css
/* Main + TOC wrapper */
.main-with-toc {
    display: flex;
    flex: 1;
    margin-left: 260px;
    min-width: 0;
}

/* Content Area */
.content-area {
    flex: 1;
    padding: 2.5rem 2rem;
    max-width: calc(820px + 4rem); /* 820px content + padding */
    min-width: 0;
}

/* Right TOC Sidebar */
.toc-sidebar {
    width: 220px;
    flex-shrink: 0;
    padding: 2.5rem 0.5rem 2.5rem 1rem;
    position: sticky;
    top: 72px; /* topbar 56px + 16px breathing room */
    align-self: flex-start;
    max-height: calc(100vh - 72px);
    overflow-y: auto;
}
.toc-sidebar.no-toc { display: none; }

.toc-title {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    margin: 0 0 0.75rem;
}

.toc-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.toc-list li { margin-bottom: 0.3rem; }
.toc-list a {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-decoration: none;
    display: block;
    padding: 0.2rem 0;
    line-height: 1.4;
    transition: color var(--transition);
}
.toc-list a:hover { color: var(--text-primary); text-decoration: none; }
.toc-list a.active { color: var(--color-primary); font-weight: 600; }
.toc-list .toc-h3 { padding-left: 0.75rem; }
```

- [ ] **Step 2: Aktualizovat responsive blok `@media (max-width: 1024px)`**

Najít blok (řádek 527) a nahradit:
```css
@media (max-width: 1024px) {
    .sidebar { transform: translateX(-100%); transition: transform var(--transition); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-toggle { display: block; }
    .content-area { margin-left: 0; max-width: 100%; padding: 1.5rem 1rem; }
}
```
Za:
```css
@media (max-width: 1024px) {
    .sidebar { transform: translateX(-100%); transition: transform var(--transition); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-toggle { display: block; }
    .main-with-toc { margin-left: 0; }
    .content-area { max-width: 100%; padding: 1.5rem 1rem; }
    .toc-sidebar { display: none; }
}
```

- [ ] **Step 3: Aktualizovat `.footer-inner` max-width**

Najít `.footer-inner` (řádek 557):
```css
.footer-inner {
    max-width: 780px;
```
Změnit na `max-width: 820px`.

- [ ] **Step 4: Aktualizovat body `line-height`**

Najít `line-height: 1.75` (řádek 45), změnit na `line-height: 1.85`.

- [ ] **Step 5: Přidat `.btn-lg`**

Najít `.btn-lg` v CSS. Pokud neexistuje, přidat za `.btn { ... }` blok (okolo řádku 320):
```css
.btn-lg { padding: 0.75rem 1.75rem; font-size: 1.05rem; }
```

- [ ] **Step 6: Ověřit v prohlížeči**

Otevřít `/co-je-ddd` — 3-sloupcový layout musí být viditelný. Na šířce < 1024px pravý sloupec zmizí.

- [ ] **Step 7: Commit**

```bash
git add public/css/modern-style.css
git commit -m "feat: CSS pro 3-sloupcový layout, TOC sidebar, typography tweaks"
```

---

## Task 8: Přidat {% block toc %} do vnitřních stránek

**14 vnitřních stránek** (vše kromě `index.html.twig` a `security_policy.html.twig`):
`what_is_ddd`, `basic_concepts`, `horizontal_vs_vertical`, `implementation_in_symfony`, `cqrs`, `event_sourcing`, `practical_examples`, `case_study`, `testing_ddd`, `migration_from_crud`, `anti_patterns`, `performance_aspects`, `resources`, `glossary`

`security_policy.html.twig` záměrně vynecháváme — je to právní stránka bez `<article>` tagu, toc.js ji automaticky označí `.no-toc`.

**Files:**
- Modify: 14 výše uvedených šablon

- [ ] **Step 1: Přidat block toc do každé šablony**

Do každé šablony přidat kdekoliv mimo `{% block body %}` (např. na konec souboru před `{% endif %}`):

```twig
{% block toc %}<p class="toc-title">Na této stránce</p>{% endblock %}
```

Twig bloky mohou být definovány kdekoliv v child šabloně — placement nezáleží na pozici v souboru.

- [ ] **Step 2: Ověřit na stránce s mnoha sekcemi**

Otevřít `/zakladni-koncepty` — pravý TOC musí zobrazovat seznam sekcí a při scrollování zvýrazňovat aktivní (žlutá).

Otevřít `/` (homepage) — pravý TOC nesmí být viditelný.

Otevřít `/security-policy` — pravý TOC nesmí být viditelný (no-toc class).

- [ ] **Step 3: Commit**

```bash
git add templates/ddd/
git commit -m "feat: přidat {% block toc %} do 14 vnitřních stránek"
```

---

## Task 9: Redesign homepage (hero + feature grid)

**Files:**
- Modify: `templates/ddd/index.html.twig` — kompletní přepis `{% block body %}`
- Modify: `public/css/modern-style.css` — přidat `.feature-grid` CSS

- [ ] **Step 1: Přidat CSS pro homepage na konec modern-style.css**

```css
/* =====================
   HOMEPAGE
   ===================== */

.hero {
    padding: 3rem 0 2rem;
    border-bottom: 1px solid var(--border);
    margin-bottom: 2.5rem;
}
.hero h1 {
    font-size: 2.2rem;
    margin-top: 0;
    border-bottom: none;
    padding-bottom: 0;
}
.hero-lead {
    font-size: 1.1rem;
    color: var(--text-muted);
    max-width: 600px;
    margin-bottom: 1.5rem;
    line-height: 1.7;
}
.feature-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin: 0 0 2.5rem;
}
.feature-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    text-decoration: none;
    display: block;
    transition: border-color var(--transition), background var(--transition);
}
.feature-card:hover {
    border-color: var(--color-primary);
    background: var(--bg-elevated);
    text-decoration: none;
}
.feature-card-title {
    font-family: var(--font-heading);
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-heading);
    margin: 0 0 0.4rem;
}
.feature-card-desc {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.5;
}
.homepage-about {
    background: var(--bg-surface);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    border-left: 4px solid var(--color-primary);
    margin-bottom: 2rem;
}
@media (max-width: 576px) {
    .feature-grid { grid-template-columns: 1fr; }
    .hero h1 { font-size: 1.75rem; }
}
```

- [ ] **Step 2: Přepsat {% block body %} v index.html.twig**

Zachovat všechny ostatní bloky (title, meta_description, structured_data atd.) beze změny. Nahradit pouze `{% block body %}...{% endblock %}`:

```twig
{% block body %}
<section class="hero">
    <h1>Průvodce Domain-Driven Design v Symfony 8</h1>
    <p class="hero-lead">Komplexní akademický průvodce implementací DDD architektury v Symfony 8 — od základních konceptů po pokročilé vzory jako CQRS a Event Sourcing.</p>
    <a href="{{ path('what_is_ddd') }}" class="btn btn-primary btn-lg">Začít s DDD →</a>
</section>

<section>
    <h2>Témata průvodce</h2>
    <div class="feature-grid">
        <a href="{{ path('what_is_ddd') }}" class="feature-card">
            <p class="feature-card-title">Co je Domain-Driven Design?</p>
            <p class="feature-card-desc">Základní filozofie DDD, Ubiquitous Language a Bounded Context.</p>
        </a>
        <a href="{{ path('basic_concepts') }}" class="feature-card">
            <p class="feature-card-title">Základní koncepty DDD</p>
            <p class="feature-card-desc">Entity, Value Objects, Agregáty, Repozitáře a Doménové události.</p>
        </a>
        <a href="{{ path('cqrs') }}" class="feature-card">
            <p class="feature-card-title">CQRS v Symfony 8</p>
            <p class="feature-card-desc">Oddělení operací čtení a zápisu pomocí Messenger komponenty.</p>
        </a>
        <a href="{{ path('event_sourcing') }}" class="feature-card">
            <p class="feature-card-title">Event Sourcing</p>
            <p class="feature-card-desc">Uchování stavu aplikace jako sekvence doménových událostí.</p>
        </a>
        <a href="{{ path('implementation_in_symfony') }}" class="feature-card">
            <p class="feature-card-title">Implementace v Symfony 8</p>
            <p class="feature-card-desc">Praktická implementace DDD architektury ve Symfony projektu.</p>
        </a>
        <a href="{{ path('glossary') }}" class="feature-card">
            <p class="feature-card-title">Glosář DDD terminologie</p>
            <p class="feature-card-desc">Přehled všech klíčových pojmů DDD s vysvětlením v češtině.</p>
        </a>
    </div>
</section>

<div class="homepage-about">
    <h2>O tomto průvodci</h2>
    <p>Průvodce je určen PHP vývojářům a Symfony vývojářům, kteří chtějí implementovat Domain-Driven Design v reálných projektech. Pokrývá strategický i taktický design, CQRS, Event Sourcing a testování DDD kódu — vše s příklady v PHP 8.4 a Symfony 8.</p>
</div>
{% endblock %}
```

- [ ] **Step 3: Ověřit homepage v prohlížeči**

Otevřít `http://localhost:8080/` — musí být viditelné:
- Hero sekce s nadpisem, perexem a CTA
- Grid 6 karet (2×3)
- Sekce "O tomto průvodci"
- Žádný duplicitní TOC seznam, žádný pravý TOC sloupec

- [ ] **Step 4: Cache clear**

```bash
php bin/console cache:clear
```

- [ ] **Step 5: Commit**

```bash
git add templates/ddd/index.html.twig public/css/modern-style.css
git commit -m "feat: redesign homepage — hero sekce + feature grid 2x3"
```

---

## Task 10: Finální ověření (PageSpeed + akceptační kritéria)

- [ ] **Step 1: Projít klíčové stránky v prohlížeči**

| Stránka | Co ověřit |
|---------|-----------|
| `/` | Hero + grid, žádný pravý TOC, btn-lg funguje |
| `/co-je-ddd` | Breadcrumb viditelný, pravý TOC, kotvy `#` při hoveru, callout ikony |
| `/zakladni-koncepty` | Scroll-spy TOC zvýrazňuje sekce |
| `/glosar` | Breadcrumb viditelný |
| `/security-policy` | Pravý TOC NENÍ viditelný |
| Mobilní (< 1024px) | Pravý TOC zmizí, sidebar za hamburgerem |
| Mobilní (< 576px) | Feature grid 1 sloupec |

- [ ] **Step 2: Ověřit PageSpeed předpoklady**

Zkontrolovat v DevTools (Network tab):
- Žádný nový externí zdroj (font, CDN) nebyl přidán
- Inline toc.js script se nenačítá jako samostatný soubor
- CSS `modern-style.css` je jediný modifikovaný stylesheet
- Všechny `<script>` tagy mají `defer` atribut

Zkontrolovat v DevTools (Performance tab):
- IntersectionObserver callback je pasivní (neblokuje scroll)
- `.toc-sidebar { position: sticky }` je GPU-akcelerovaný

- [ ] **Step 3: Cache clear (finální)**

```bash
php bin/console cache:clear
```

- [ ] **Step 4: Finální commit**

```bash
git add -A
git commit -m "feat: dokončení akademického UI redesignu (Symfony docs styl)"
```

---

## Akceptační kritéria (z design spec)

1. ✅ Pravý TOC na vnitřních stránkách (ne na homepage/security-policy), scroll-spy funguje
2. ✅ Homepage: hero + feature grid 2×3, žádná duplicitní navigace
3. ✅ Kotvy `#` pouze při hoveru
4. ✅ Callout boxy s Unicode ikonami (4 typy: note, tip, warning, caution)
5. ✅ Breadcrumby viditelné a čitelné
6. ✅ Chapter nav karty fungují (beze změny)
7. ✅ Na <1024px pravý TOC zmizí
8. ✅ `php bin/console cache:clear` bez chyb
9. ✅ `modern-script.js` aktualizován (callout selektor + symbol kotvy)
10. ✅ Žádný nový HTTP request pro toc.js (inlinován), žádný nový externí zdroj

## PageSpeed poznámky

Tato implementace nesnižuje PageSpeed skóre díky:
- **toc.js inlinován** — žádný extra HTTP request
- **CSS additivní** — pouze nové selektory, žádné nové soubory ani blokující zdroje
- **IntersectionObserver** — pasivní, bez layout thrashingu
- **`position: sticky`** — GPU-akcelerovaný, bez Reflow
- **Žádné obrázky na homepage** — hero sekce je čistý text → LCP je textový prvek, rychlý
- **Fonty zachovány** — `preconnect` na Google Fonts již existuje v `base.html.twig`
