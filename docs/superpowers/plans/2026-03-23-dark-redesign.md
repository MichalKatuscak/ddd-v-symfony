# Dark Mode Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the DDD Symfony educational site from a pastel light theme to a professional dark mode inspired by symfony.com, with a fixed sidebar, gold accents, improved code blocks, and better readability.

**Architecture:** Full rewrite of `modern-style.css` and `code-style.css` for the new dark palette. `base.html.twig` restructured to add a top bar and fixed sidebar. JS files updated for sidebar toggle, heading anchors, and code block header injection. All 14 page templates updated with renamed callout classes.

**Tech Stack:** Symfony 8 Twig templates, plain CSS (no build pipeline), vanilla JS, highlight.js (CDN), svg-pan-zoom (CDN), Google Fonts (Merriweather, Nunito, JetBrains Mono), Bootstrap grid (kept as-is).

**Spec:** `docs/superpowers/specs/2026-03-23-dark-redesign-design.md`

**Note:** There is no test suite. Verify each task visually by running `symfony server:start` and opening the browser.

---

## File Map

| File | Role after redesign |
|------|---------------------|
| `public/css/modern-style.css` | Dark palette vars, body, layout (top bar, sidebar, content), typography, callout cards, footer, utilities |
| `public/css/code-style.css` | Dark code blocks, line numbers, code block header (language badge + copy button) |
| `templates/base.html.twig` | Top bar HTML, sidebar HTML, `.page-wrapper` + `.content-area` layout wrappers, updated theme-color meta, atom-one-dark CDN |
| `public/js/modern-script.js` | Sidebar toggle + mobile backdrop, heading anchor injection, scroll-to-top, smooth links, fade-in observer (updated selectors); duplicate copy button removed |
| `public/js/code-script.js` | Code block header injection (language badge + copy button); line numbers kept; processAugmentCodeSnippets kept |
| `templates/ddd/*.html.twig` (14 files) | Callout class renames: `.concept-box`→`.info-card`, `.example-box`→`.example-card`, `.warning-box`→`.warning-card` |

---

## Task 1: CSS Variables & Dark Base Styles

**Files:**
- Modify: `public/css/modern-style.css` (lines 1–48: `:root` vars + body)

- [ ] **Step 1: Replace `:root` and body in `modern-style.css`**

Replace the entire `:root` block and `body` / base styles with:

```css
/* Dark Mode Redesign — Symfony-inspired */

* {
    box-sizing: border-box;
}

:root {
    /* Dark palette */
    --bg-base: #0f0f1a;
    --bg-surface: #161625;
    --bg-elevated: #1e1e30;

    /* Accents */
    --color-primary: #f5c518;
    --color-primary-dim: #c9a010;
    --color-accent: #7c6ff7;

    /* Text */
    --text-primary: #e8e8f0;
    --text-muted: #8888a8;
    --text-heading: #ffffff;

    /* Border */
    --border: #2a2a40;

    /* Typography */
    --font-sans: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --font-heading: 'Merriweather', Georgia, 'Times New Roman', serif;
    --font-mono: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace;

    /* Misc */
    --transition: 0.2s ease;
    --radius: 8px;
}

html {
    font-size: 16px;
    scroll-behavior: smooth;
}

body {
    font-family: var(--font-sans);
    color: var(--text-primary);
    background-color: var(--bg-base);
    line-height: 1.75;
    margin: 0;
    padding: 0;
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    color: var(--text-heading);
    line-height: 1.25;
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}

h1 { font-size: 2.2rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem; }
h2 { font-size: 1.75rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.4rem; }
h3 { font-size: 1.35rem; font-weight: 600; }
h4 { font-size: 1.15rem; font-weight: 600; }
h5 { font-size: 1rem; font-weight: 600; }
h6 { font-size: 0.875rem; font-weight: 600; }

*:first-child > h1:first-child,
*:first-child > h2:first-child,
*:first-child > h3:first-child { margin-top: 0; }

p { margin-top: 0; margin-bottom: 1rem; }

a {
    color: var(--color-primary);
    text-decoration: none;
    transition: color var(--transition);
}
a:hover { color: var(--color-primary-dim); text-decoration: underline; }

code {
    font-family: var(--font-mono);
    font-size: 0.875em;
    color: var(--color-primary);
    background: var(--bg-elevated);
    padding: 0.15em 0.4em;
    border-radius: 4px;
}

blockquote {
    border-left: 4px solid var(--color-primary);
    margin: 1.5rem 0;
    padding: 0.75rem 1.25rem;
    color: var(--text-muted);
    font-style: italic;
}

ul, ol { padding-left: 1.5rem; margin-bottom: 1rem; }
li { margin-bottom: 0.35rem; }
```

- [ ] **Step 2: Verify base styles load**

Open a page in the browser. Body should be dark navy (`#0f0f1a`), text light (`#e8e8f0`), headings white. Links gold.

- [ ] **Step 3: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/css/modern-style.css
git commit -m "css: dark palette vars and base typography"
```

---

## Task 2: Restructure `base.html.twig` — Top Bar + Sidebar HTML

**Files:**
- Modify: `templates/base.html.twig`

This task replaces the old `<header>` + horizontal nav with a fixed top bar and sidebar layout.

- [ ] **Step 1: Update `<head>` in `base.html.twig`**

Find and replace the two lines:
```html
    <meta name="theme-color" content="#a5d8ff">
```
with:
```html
    <meta name="theme-color" content="#0f0f1a">
```

Find and replace the highlight.js CDN stylesheet line:
```html
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-light.min.css">
```
with:
```html
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
```

- [ ] **Step 2: Replace `<body>` content — top bar + sidebar + wrapper**

Replace the entire content of `<body>` up to (but not including) the `{% block javascripts %}` and scripts section. The new structure:

```html
<body>
    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-inner">
            <div class="topbar-logo">
                <a href="{{ path('homepage') }}">DDD <strong>Symfony</strong></a>
            </div>
            <button class="sidebar-toggle" aria-label="Otevřít navigaci" aria-expanded="false">
                <span class="hamburger-icon">&#9776;</span>
            </button>
        </div>
    </header>

    <!-- Sidebar backdrop (mobile) -->
    <div class="sidebar-backdrop" aria-hidden="true"></div>

    <!-- Page Wrapper -->
    <div class="page-wrapper">

        <!-- Sidebar -->
        <aside class="sidebar" aria-label="Navigace kapitol">
            <nav>
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'homepage' %}active{% endif %}" href="{{ path('homepage') }}">Úvod</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'what_is_ddd' %}active{% endif %}" href="{{ path('what_is_ddd') }}">Co je DDD</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'horizontal_vs_vertical' %}active{% endif %}" href="{{ path('horizontal_vs_vertical') }}">Vertikální slice</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'basic_concepts' %}active{% endif %}" href="{{ path('basic_concepts') }}">Základní koncepty</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'implementation_in_symfony' %}active{% endif %}" href="{{ path('implementation_in_symfony') }}">Implementace v Symfony</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'cqrs' %}active{% endif %}" href="{{ path('cqrs') }}">CQRS</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'practical_examples' %}active{% endif %}" href="{{ path('practical_examples') }}">Příklady</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'case_study' %}active{% endif %}" href="{{ path('case_study') }}">Případová studie</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'resources' %}active{% endif %}" href="{{ path('resources') }}">Zdroje</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'security_policy' %}active{% endif %}" href="{{ path('security_policy') }}">Bezpečnostní politika</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'migration_from_crud' %}active{% endif %}" href="{{ path('migration_from_crud') }}">Migrace z CRUD</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'testing_ddd' %}active{% endif %}" href="{{ path('testing_ddd') }}">Testování DDD</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'event_sourcing' %}active{% endif %}" href="{{ path('event_sourcing') }}">Event Sourcing</a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'anti_patterns' %}active{% endif %}" href="{{ path('anti_patterns') }}">Anti-vzory</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="content-area">
            {% if app.request.attributes.get('_route') != 'homepage' %}
            <div class="breadcrumb-container">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ path('homepage') }}">Hlavní stránka</a></li>
                        {% block breadcrumbs %}
                        <li class="breadcrumb-item active" aria-current="page">{{ title }}</li>
                        {% endblock %}
                    </ol>
                </nav>
            </div>

            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "BreadcrumbList",
                "itemListElement": [
                    {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "Hlavní stránka",
                        "item": "{{ app.request.schemeAndHttpHost }}{{ path('homepage') }}"
                    },
                    {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "{{ title }}",
                        "item": "{{ app.request.schemeAndHttpHost }}{{ app.request.requestUri }}"
                    }
                ]
            }
            </script>
            {% endif %}

            {% block body %}{% endblock %}

        </main><!-- /.content-area -->
    </div><!-- /.page-wrapper -->

    <footer class="footer">
        <div class="footer-inner">
            <strong>DDD Symfony</strong>
            <p>Komplexní průvodce Domain-Driven Design v Symfony 8</p>
            <p>&copy; {{ "now"|date("Y") }} Michal Katuščák – Všechna práva vyhrazena</p>
        </div>
    </footer>

    <button class="scroll-to-top" aria-label="Zpět nahoru">↑</button>
```

Keep all `<script>` tags and `{% block javascripts %}` after the footer exactly as they were.

- [ ] **Step 3: Verify page loads without errors**

Run `php bin/console cache:clear` then open the site. Layout will look broken until CSS is added — that's fine. Confirm no Twig or PHP errors in the browser.

- [ ] **Step 4: Commit**

```bash
cd "W:/ddd-v-symfony"
git add templates/base.html.twig
git commit -m "twig: restructure layout — top bar, sidebar, content wrapper"
```

---

## Task 3: Layout CSS — Top Bar, Sidebar, Content Area

**Files:**
- Modify: `public/css/modern-style.css` (append layout section)

- [ ] **Step 1: Append layout CSS to `modern-style.css`**

Add after the base styles from Task 1:

```css
/* =====================
   LAYOUT
   ===================== */

/* Top Bar */
.topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    background: var(--bg-surface);
    border-bottom: 2px solid var(--color-primary);
    z-index: 200;
}

.topbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    padding: 0 1.5rem;
}

.topbar-logo a {
    font-family: var(--font-heading);
    font-size: 1.4rem;
    font-weight: 400;
    color: var(--text-primary);
    text-decoration: none;
    letter-spacing: 0.03em;
}
.topbar-logo strong {
    color: var(--color-primary);
    font-weight: 700;
}
.topbar-logo a:hover { text-decoration: none; color: var(--text-primary); }

/* Hamburger (mobile only) */
.sidebar-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--text-primary);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    line-height: 1;
}

/* Sidebar Backdrop */
.sidebar-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 99;
}
.sidebar-backdrop.active { display: block; }

/* Page Wrapper */
.page-wrapper {
    display: flex;
    margin-top: 56px; /* topbar height */
    min-height: calc(100vh - 56px);
}

/* Sidebar */
.sidebar {
    width: 260px;
    flex-shrink: 0;
    background: var(--bg-surface);
    border-right: 1px solid var(--border);
    position: fixed;
    top: 56px;
    left: 0;
    bottom: 0;
    overflow-y: auto;
    z-index: 100;
    padding: 1.5rem 0;
}

.sidebar-nav {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar-nav-item { margin: 0; }

.sidebar-nav-link {
    display: block;
    padding: 0.6rem 1.5rem;
    font-family: var(--font-sans);
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-primary);
    text-decoration: none;
    transition: color var(--transition), background var(--transition);
    border-left: 3px solid transparent;
}

.sidebar-nav-link:hover {
    color: var(--color-primary-dim);
    background: var(--bg-elevated);
    text-decoration: none;
}

.sidebar-nav-link.active {
    color: var(--color-primary);
    border-left-color: var(--color-primary);
    background: rgba(245, 197, 24, 0.07);
}

/* Content Area */
.content-area {
    flex: 1;
    margin-left: 260px;
    padding: 2.5rem 2rem;
    max-width: calc(780px + 4rem); /* 780px content + padding */
    min-width: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform var(--transition);
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .sidebar-toggle { display: block; }
    .content-area {
        margin-left: 0;
        max-width: 100%;
        padding: 1.5rem 1rem;
    }
}

@media (max-width: 576px) {
    html { font-size: 15px; }
    .content-area { padding: 1rem 0.75rem; }
}

/* Footer */
.footer {
    background: var(--bg-surface);
    border-top: 1px solid var(--border);
    padding: 2rem 0;
    text-align: center;
    margin-left: 260px;
}

.footer-inner {
    max-width: 780px;
    margin: 0 auto;
    padding: 0 2rem;
}

.footer strong {
    color: var(--color-primary);
    font-size: 1.1rem;
    display: block;
    margin-bottom: 0.5rem;
}

.footer p {
    color: var(--text-muted);
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

@media (max-width: 1024px) {
    .footer { margin-left: 0; }
}

/* Scroll to top */
.scroll-to-top {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    width: 40px;
    height: 40px;
    background: var(--color-primary);
    color: #000;
    border: none;
    border-radius: 50%;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition);
    z-index: 300;
}
.scroll-to-top.show { opacity: 1; visibility: visible; }
.scroll-to-top:hover { background: var(--color-primary-dim); }
```

- [ ] **Step 2: Verify layout in browser**

The page should now have: fixed gold-lined top bar, sidebar on the left with chapter links, content area on the right. Active link highlighted in gold. Test mobile by narrowing the browser window below 1024px — sidebar should hide.

- [ ] **Step 3: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/css/modern-style.css
git commit -m "css: layout — top bar, fixed sidebar, content area, responsive"
```

---

## Task 4: Sidebar JS — Toggle, Backdrop, Cleanup

**Files:**
- Modify: `public/js/modern-script.js` (full rewrite)

- [ ] **Step 1: Rewrite `modern-script.js`**

Replace the entire file with:

```javascript
// modern-script.js — Dark Mode Redesign

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar toggle (mobile) ──────────────────────────────────────────────
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');

    function openSidebar() {
        sidebar.classList.add('open');
        backdrop.classList.add('active');
        sidebarToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('active');
        sidebarToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    // Close sidebar when a nav link is clicked (mobile)
    document.querySelectorAll('.sidebar-nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) closeSidebar();
        });
    });

    // ── Scroll to top ────────────────────────────────────────────────────────
    const scrollBtn = document.querySelector('.scroll-to-top');
    if (scrollBtn) {
        window.addEventListener('scroll', function () {
            scrollBtn.classList.toggle('show', window.pageYOffset > 300);
        });
        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ── Smooth anchor scrolling ──────────────────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.startsWith('#')) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                    history.pushState(null, null, href);
                }
            }
        });
    });

    // ── Heading anchor links ─────────────────────────────────────────────────
    document.querySelectorAll('h2[id], h3[id], h4[id]').forEach(function (heading) {
        const anchor = document.createElement('a');
        anchor.className = 'anchor-link';
        anchor.href = '#' + heading.id;
        anchor.textContent = '§';
        anchor.setAttribute('aria-label', 'Link na tuto sekci');
        heading.appendChild(anchor);
    });

    // ── Table responsiveness ─────────────────────────────────────────────────
    document.querySelectorAll('table').forEach(function (table) {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // ── Fade-in observer (updated class names) ───────────────────────────────
    const fadeTargets = document.querySelectorAll(
        '.card, .info-card, .warning-card, .example-card, .practice-card, .success-card'
    );
    if (fadeTargets.length > 0) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        fadeTargets.forEach(function (el) { observer.observe(el); });
    }

});
```

- [ ] **Step 2: Verify sidebar toggle works on mobile**

Narrow browser below 1024px. Click the hamburger — sidebar should slide in, backdrop appears. Click backdrop or a link — sidebar closes.

- [ ] **Step 3: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/js/modern-script.js
git commit -m "js: sidebar toggle, heading anchors, updated fade-in selectors"
```

---

## Task 5: Code Blocks CSS

**Files:**
- Modify: `public/css/code-style.css` (full rewrite)

- [ ] **Step 1: Rewrite `code-style.css`**

Replace the entire file with:

```css
/* code-style.css — Dark Mode Code Blocks */

/* ── Wrapper ──────────────────────────────────────────────────────────────── */
.code-block-wrapper {
    position: relative;
    margin: 1.5rem 0;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
}

/* ── Header (language badge + copy button) ───────────────────────────────── */
.code-block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-elevated);
    padding: 0.4rem 0.75rem;
    border-bottom: 1px solid var(--border);
    font-family: var(--font-sans);
    font-size: 0.8rem;
}

.code-lang-badge {
    background: var(--color-accent);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.2em 0.5em;
    border-radius: 4px;
}

.code-filename {
    color: var(--text-muted);
    font-family: var(--font-mono);
    font-size: 0.78rem;
    margin-left: 0.75rem;
    flex: 1;
}

.copy-button {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-muted);
    font-size: 0.75rem;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all var(--transition);
    white-space: nowrap;
}
.copy-button:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}
.copy-button.copied {
    border-color: #51cf66;
    color: #51cf66;
}

/* ── pre + code ───────────────────────────────────────────────────────────── */
pre {
    position: relative;
    background: var(--bg-surface);
    border-radius: 0; /* border on wrapper */
    margin: 0;
    overflow-x: auto;
    border: none;
    font-family: var(--font-mono);
    line-height: 1.6;
    display: flex;
}

.code-block-wrapper pre {
    border-radius: 0;
}

/* Standalone pre (not in wrapper yet — before JS runs) */
pre:not(.code-block-wrapper pre) {
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin: 1.5rem 0;
}

/* Line numbers */
.line-numbers-rows {
    display: flex;
    flex-direction: column;
    padding: 1rem 0.5rem;
    margin: 0;
    border-right: 1px solid var(--border);
    background: transparent;
    user-select: none;
    text-align: right;
    color: var(--text-muted);
    font-size: 0.8rem;
    min-width: 2.5rem;
}

/* Code content */
pre code {
    display: block;
    color: var(--text-primary);
    font-size: 0.875rem;
    padding: 1rem;
    background: transparent;
    border-radius: 0;
    tab-size: 4;
    overflow: visible;
    white-space: pre;
    flex: 1;
}

/* Inline code (not in pre) */
code:not(pre code) {
    font-family: var(--font-mono);
    font-size: 0.875em;
    color: var(--color-primary);
    background: var(--bg-elevated);
    padding: 0.15em 0.4em;
    border-radius: 4px;
}

/* ── Augment code snippet (kept) ─────────────────────────────────────────── */
.augment-code-snippet { position: relative; margin: 1.5rem 0; }
.augment-code-snippet pre { margin: 0; }

.augment-code-snippet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-elevated);
    color: var(--text-primary);
    padding: 0.5rem 1rem;
    border-radius: var(--radius) var(--radius) 0 0;
    font-size: 0.875rem;
    border: 1px solid var(--border);
    border-bottom: none;
}

.augment-code-snippet pre {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.file-path {
    font-family: var(--font-mono);
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 80%;
    font-weight: 600;
    color: var(--text-muted);
}

.view-full-file {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-muted);
    border-radius: 4px;
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all var(--transition);
}
.view-full-file:hover { border-color: var(--color-primary); color: var(--color-primary); }

/* ── Mobile ───────────────────────────────────────────────────────────────── */
@media (max-width: 576px) {
    pre { font-size: 0.8rem; }
    .line-numbers-rows { min-width: 1.75rem; padding: 0.75rem 0.25rem; font-size: 0.75rem; }
    pre code { padding: 0.75rem 0.5rem; }
    .copy-button { opacity: 1; }
    .file-path { max-width: 60%; }
}
```

- [ ] **Step 2: Verify code blocks look correct in browser**

Open a page with code examples (e.g., `/zakladni-koncepty`). Code blocks should have dark background, monospace font, visible line numbers. Copy button in header area (added in Task 6).

- [ ] **Step 3: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/css/code-style.css
git commit -m "css: dark code blocks with header, line numbers, copy button"
```

---

## Task 6: Code Block Header Injection

**Files:**
- Modify: `public/js/code-script.js`

The existing `addLineNumbers()` and `addCopyButton()` functions work correctly. We need to add a new `addCodeBlockHeader()` function that wraps each `<pre>` in a `.code-block-wrapper` and injects the header div before the `<pre>`.

- [ ] **Step 1: Update `code-script.js`**

Replace the existing `DOMContentLoaded` handler and `addCopyButton` function. Keep `addLineNumbers`, `processAugmentCodeSnippets`, `processXmlTags` unchanged. Full new file:

```javascript
// code-script.js — Dark Mode Code Blocks

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('pre code').forEach(function (codeBlock) {
        addLineNumbers(codeBlock);
        addCodeBlockHeader(codeBlock);
    });

    processAugmentCodeSnippets();
});

// Detect language from highlight.js class (e.g. "language-php" → "PHP")
function detectLanguage(codeBlock) {
    const classes = Array.from(codeBlock.classList);
    for (const cls of classes) {
        if (cls.startsWith('language-')) {
            return cls.replace('language-', '').toUpperCase();
        }
    }
    // Fallback: check hljs class names
    if (codeBlock.classList.contains('hljs')) {
        const hljsLang = codeBlock.getAttribute('data-highlighted-language');
        if (hljsLang) return hljsLang.toUpperCase();
    }
    return null;
}

// Wrap <pre> in .code-block-wrapper and inject header with language badge + copy button
function addCodeBlockHeader(codeBlock) {
    const pre = codeBlock.parentNode;
    if (pre.tagName !== 'PRE') return;
    if (pre.parentNode.classList.contains('code-block-wrapper')) return; // already wrapped

    // Build wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'code-block-wrapper';

    // Build header
    const header = document.createElement('div');
    header.className = 'code-block-header';

    const lang = detectLanguage(codeBlock);

    // Language badge
    const leftSide = document.createElement('div');
    leftSide.style.display = 'flex';
    leftSide.style.alignItems = 'center';
    leftSide.style.gap = '0.5rem';
    leftSide.style.minWidth = '0';

    if (lang) {
        const badge = document.createElement('span');
        badge.className = 'code-lang-badge';
        badge.textContent = lang;
        leftSide.appendChild(badge);
    }

    // Optional filename from data-filename attribute on <pre>
    const filename = pre.getAttribute('data-filename');
    if (filename) {
        const filenameSpan = document.createElement('span');
        filenameSpan.className = 'code-filename';
        filenameSpan.textContent = filename;
        leftSide.appendChild(filenameSpan);
    }

    header.appendChild(leftSide);

    // Copy button
    const copyBtn = document.createElement('button');
    copyBtn.className = 'copy-button';
    copyBtn.textContent = 'Kopírovat'; // matches existing code-script.js default label (line 54)
    copyBtn.addEventListener('click', function () {
        const code = codeBlock.getAttribute('data-original') || codeBlock.textContent;
        navigator.clipboard.writeText(code).then(function () {
            copyBtn.textContent = 'Zkopírováno!';
            copyBtn.classList.add('copied');
            setTimeout(function () {
                copyBtn.textContent = 'Kopírovat';
                copyBtn.classList.remove('copied');
            }, 2000);
        }).catch(function (err) {
            console.error('Copy failed:', err);
            copyBtn.textContent = 'Chyba!';
            setTimeout(function () { copyBtn.textContent = 'Kopírovat'; }, 2000);
        });
    });
    header.appendChild(copyBtn);

    // Insert wrapper: replace <pre> with wrapper containing header + pre
    pre.parentNode.insertBefore(wrapper, pre);
    wrapper.appendChild(header);
    wrapper.appendChild(pre);
}

// Add line numbers — unchanged from original
function addLineNumbers(codeBlock) {
    const originalContent = codeBlock.textContent;
    codeBlock.setAttribute('data-original', originalContent);

    const lineNumbersContainer = document.createElement('div');
    lineNumbersContainer.className = 'line-numbers-rows';

    const lines = originalContent.split('\n');
    const lineCount = lines[lines.length - 1].trim() === '' ? lines.length - 1 : lines.length;

    for (let i = 1; i <= lineCount; i++) {
        const lineNumber = document.createElement('span');
        lineNumber.textContent = i;
        lineNumbersContainer.appendChild(lineNumber);
    }

    const pre = codeBlock.parentNode;
    pre.insertBefore(lineNumbersContainer, codeBlock);
}

// processAugmentCodeSnippets — unchanged from original
function processAugmentCodeSnippets() {
    document.querySelectorAll('augment_code_snippet').forEach(function (snippet) {
        const filePath = snippet.getAttribute('path') || 'example.php';

        const wrapper = document.createElement('div');
        wrapper.className = 'augment-code-snippet';

        const header = document.createElement('div');
        header.className = 'augment-code-snippet-header';

        const filePathDisplay = document.createElement('span');
        filePathDisplay.className = 'file-path';
        filePathDisplay.textContent = filePath;
        header.appendChild(filePathDisplay);

        const viewButton = document.createElement('button');
        viewButton.className = 'view-full-file';
        viewButton.textContent = 'Zobrazit celý soubor';
        viewButton.addEventListener('click', function () {
            alert('Zobrazení celého souboru: ' + filePath);
        });
        header.appendChild(viewButton);

        wrapper.appendChild(header);

        const pre = snippet.querySelector('pre');
        if (pre) {
            const clonedPre = pre.cloneNode(true);
            wrapper.appendChild(clonedPre);
            const codeBlock = clonedPre.querySelector('code');
            if (codeBlock) {
                addLineNumbers(codeBlock);
                addCodeBlockHeader(codeBlock);
            }
        }

        snippet.parentNode.replaceChild(wrapper, snippet);
    });
}

function processXmlTags() {
    document.querySelectorAll('pre code').forEach(function (codeBlock) {
        let content = codeBlock.innerHTML;
        content = content.replace(/&lt;(\/?[a-zA-Z0-9_:-]+)&gt;/g, '<span class="xml-tag">&lt;$1&gt;</span>');
        codeBlock.innerHTML = content;
    });
}
```

- [ ] **Step 2: Verify code block headers appear**

Open a page with code. Each `<pre>` should now have a header bar with the language badge (PHP, YAML, etc.) and a "Kopírovat" button. Clicking it should show "Zkopírováno!" briefly.

- [ ] **Step 3: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/js/code-script.js
git commit -m "js: code block header injection — language badge and copy button"
```

---

## Task 7: Typography, Callout Boxes & Table Styles

**Files:**
- Modify: `public/css/modern-style.css` (append)

- [ ] **Step 1: Append component styles to `modern-style.css`**

```css
/* =====================
   COMPONENTS
   ===================== */

/* Table of contents */
.table-of-contents {
    background: var(--bg-surface);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border);
    border-left: 4px solid var(--color-primary);
}
.table-of-contents h5 { margin-top: 0; margin-bottom: 0.75rem; color: var(--text-heading); }
.table-of-contents ul { margin-bottom: 0; padding-left: 1.25rem; }
.table-of-contents li { margin-bottom: 0.4rem; }
.table-of-contents a { color: var(--text-primary); font-weight: 500; }
.table-of-contents a:hover { color: var(--color-primary); text-decoration: none; }

/* Callout cards */
.info-card,
.example-card,
.warning-card,
.practice-card,
.success-card {
    background: var(--bg-elevated);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    margin: 1.75rem 0;
    border: 1px solid var(--border);
}
.info-card    { border-top: 3px solid var(--color-accent); }
.example-card { border-top: 3px solid var(--color-primary); }
.warning-card { border-top: 3px solid #f59f00; }
.practice-card{ border-top: 3px solid #51cf66; }
.success-card { border-top: 3px solid #51cf66; }

.info-card h4,
.example-card h4,
.warning-card h4,
.practice-card h4,
.success-card h4 {
    margin-top: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-heading);
}

/* Heading anchor links */
.anchor-link {
    display: inline-block;
    margin-left: 0.5rem;
    color: var(--text-muted);
    opacity: 0;
    font-size: 0.8em;
    font-family: var(--font-sans);
    font-weight: 400;
    transition: opacity var(--transition);
    text-decoration: none;
}
h2:hover .anchor-link,
h3:hover .anchor-link,
h4:hover .anchor-link { opacity: 1; }
.anchor-link:hover { color: var(--color-primary); text-decoration: none; }

/* Tables */
.table {
    width: 100%;
    margin-bottom: 1rem;
    color: var(--text-primary);
    border-collapse: collapse;
}
.table th,
.table td {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid var(--border);
}
.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-heading);
    font-weight: 600;
}
.table-bordered { border: 1px solid var(--border); }
.table-bordered th,
.table-bordered td { border: 1px solid var(--border); }
.table-hover tbody tr:hover { background: var(--bg-elevated); }
.table-responsive { display: block; width: 100%; overflow-x: auto; }

/* Cards (homepage etc.) */
.card {
    background: var(--bg-surface);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 2rem;
    overflow: hidden;
    transition: border-color var(--transition), transform var(--transition);
}
.card:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
}
.card-header {
    padding: 0.75rem 1rem;
    background: var(--bg-elevated);
    border-bottom: 1px solid var(--border);
    color: var(--text-heading);
    font-weight: 600;
}
.card-body { padding: 1.25rem; }
.card-body > h1:first-child,
.card-body > h2:first-child,
.card-body > h3:first-child,
.card-body > h4:first-child { margin-top: 0; }
.card-title { margin-top: 0; margin-bottom: 0.5rem; color: var(--text-heading); }

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

/* Error pages */
.error-page { padding: 3rem 0; text-align: center; }
.error-code {
    font-size: 8rem;
    font-weight: 900;
    color: var(--color-primary);
    line-height: 1;
    opacity: 0.4;
    margin-bottom: 1rem;
}
.error-title { font-size: 2.5rem; color: var(--text-heading); margin-bottom: 1rem; }
.error-description {
    font-size: 1.1rem;
    color: var(--text-muted);
    max-width: 600px;
    margin: 0 auto 2rem;
}
@media (max-width: 576px) {
    .error-code { font-size: 5rem; }
    .error-title { font-size: 1.75rem; }
}

/* Utilities */
.mb-0 { margin-bottom: 0 !important; }
.mb-1 { margin-bottom: 0.25rem !important; }
.mb-2 { margin-bottom: 0.5rem !important; }
.mb-3 { margin-bottom: 1rem !important; }
.mb-4 { margin-bottom: 1.5rem !important; }
.mb-5 { margin-bottom: 3rem !important; }
.mt-0 { margin-top: 0 !important; }
.mt-1 { margin-top: 0.25rem !important; }
.mt-2 { margin-top: 0.5rem !important; }
.mt-3 { margin-top: 1rem !important; }
.mt-4 { margin-top: 1.5rem !important; }
.mt-5 { margin-top: 3rem !important; }
.text-center { text-align: center !important; }
.text-muted { color: var(--text-muted) !important; }
.text-white { color: #fff !important; }
.py-5 { padding-top: 3rem !important; padding-bottom: 3rem !important; }

/* Animations */
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
.fade-in { animation: fadeIn 0.3s ease-out; }

/* Print */
@media print {
    body { background: white; color: black; }
    .topbar, .sidebar, .sidebar-backdrop, .scroll-to-top, .footer { display: none; }
    .page-wrapper { margin-top: 0; }
    .content-area { margin-left: 0; max-width: 100%; }
}
```

- [ ] **Step 2: Verify callout boxes and components**

Open a page with callout boxes (e.g., `/zakladni-koncepty`). The `.concept-box` class still exists in templates at this point — but old styles are gone so they'll be unstyled. That's fine, they'll be renamed in Task 8.

- [ ] **Step 3: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/css/modern-style.css
git commit -m "css: callout cards, tables, cards, utilities, error pages"
```

---

## Task 8: Rename Callout Classes in Templates

**Files:**
- Modify: all 14 routed templates in `templates/ddd/` (NOT `what_is_ddd_updated.html.twig` or `horizontal_vs_vertical_updated.html.twig`)

Three renames:
- `concept-box` → `info-card`
- `example-box` → `example-card`
- `warning-box` → `warning-card`

**Note on class coverage:** Per the spec, only three classes exist in templates: `concept-box` (51×), `example-box` (101×), `warning-box` (28×). The classes `practice-box` and `success-box` have **zero** occurrences — they are new CSS-only classes being introduced, no rename needed.

All 14 template files have been verified to exist in `templates/ddd/`.

- [ ] **Step 1: Run sed replacements**

```bash
cd "W:/ddd-v-symfony/templates/ddd"

# List of 14 routed templates (not the _updated orphans)
FILES="index.html.twig what_is_ddd.html.twig horizontal_vs_vertical.html.twig basic_concepts.html.twig implementation_in_symfony.html.twig cqrs.html.twig practical_examples.html.twig case_study.html.twig resources.html.twig security_policy.html.twig migration_from_crud.html.twig testing_ddd.html.twig event_sourcing.html.twig anti_patterns.html.twig"

for f in $FILES; do
    sed -i 's/concept-box/info-card/g; s/example-box/example-card/g; s/warning-box/warning-card/g' "$f"
done
```

- [ ] **Step 2: Verify replacements**

```bash
cd "W:/ddd-v-symfony"
# Should find zero occurrences in routed templates
grep -r "concept-box\|example-box\|warning-box" templates/ddd/index.html.twig templates/ddd/basic_concepts.html.twig templates/ddd/cqrs.html.twig
```

Expected output: no matches.

- [ ] **Step 3: Spot-check a template**

Open a page with callout boxes in the browser. Boxes should now have the new dark style from Task 7 (dark elevated background, colored top border).

- [ ] **Step 4: Commit**

```bash
cd "W:/ddd-v-symfony"
git add templates/ddd/
git commit -m "twig: rename callout classes — concept-box→info-card, example-box→example-card, warning-box→warning-card"
```

---

## Task 9: Diagrams Dark Mode

**Files:**
- Modify: `public/css/modern-style.css` (append)

- [ ] **Step 1: Append diagram styles to `modern-style.css`**

```css
/* =====================
   DIAGRAMS
   ===================== */

.diagram-container {
    background: var(--bg-surface);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    padding: 1rem;
    margin: 1.5rem 0;
    text-align: center;
    overflow: hidden;
}

.diagram-container svg {
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    display: block;
}

/* Invert the diagram content (first group) to show on dark background.
   svg-pan-zoom injects controls separately — they are NOT in the first <g>. */
.diagram-container svg > g:first-of-type {
    filter: invert(1) hue-rotate(180deg);
}

/* Fallback: if the above doesn't isolate correctly, use the rule below instead
   and uncomment the .svg-pan-zoom-control override:

.diagram-container svg {
    filter: invert(1) hue-rotate(180deg);
}
.diagram-container .svg-pan-zoom-control {
    filter: invert(1) hue-rotate(180deg);
}
*/

figcaption {
    color: var(--text-muted);
    font-style: italic;
    font-size: 0.875rem;
    text-align: center;
    margin-top: 0.5rem;
}
```

- [ ] **Step 2: Test on two diagrams**

Open a page with a diagram (e.g., `/zakladni-koncepty` or `/cqrs`). The diagram should appear with a dark background and inverted colours. If the svg-pan-zoom control icons are invisible (dark-on-dark), switch to the fallback CSS in the comment block.

- [ ] **Step 3: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/css/modern-style.css
git commit -m "css: dark mode diagram containers with SVG invert filter"
```

---

## Task 10: Prev/Next Navigation

**Files:**
- Modify: `public/js/modern-script.js` (append to DOMContentLoaded)
- Modify: `public/css/modern-style.css` (append)

- [ ] **Step 1: Add chapter order array and prev/next injection to `modern-script.js`**

All URL paths below have been verified against `DddController.php` route definitions.

Add this block inside `DOMContentLoaded`, after the fade-in observer section:

```javascript
    // ── Prev/Next navigation ─────────────────────────────────────────────────
    const CHAPTERS = [
        { label: 'Úvod', url: '/' },
        { label: 'Co je DDD', url: '/co-je-ddd' },
        { label: 'Vertikální slice', url: '/horizontalni-vs-vertikalni' },
        { label: 'Základní koncepty', url: '/zakladni-koncepty' },
        { label: 'Implementace v Symfony', url: '/implementace-v-symfony' },
        { label: 'CQRS', url: '/cqrs' },
        { label: 'Příklady', url: '/prakticke-priklady' },
        { label: 'Případová studie', url: '/pripadova-studie' },
        { label: 'Zdroje', url: '/zdroje' },
        { label: 'Bezpečnostní politika', url: '/security-policy' },
        { label: 'Migrace z CRUD', url: '/migrace-z-crud' },
        { label: 'Testování DDD', url: '/testovani-ddd' },
        { label: 'Event Sourcing', url: '/event-sourcing' },
        { label: 'Anti-vzory', url: '/anti-vzory' },
    ];

    const currentPath = window.location.pathname;
    const currentIndex = CHAPTERS.findIndex(function (c) { return c.url === currentPath; });

    if (currentIndex !== -1) {
        const contentArea = document.querySelector('.content-area');
        if (contentArea) {
            const nav = document.createElement('nav');
            nav.className = 'chapter-nav';
            nav.setAttribute('aria-label', 'Navigace mezi kapitolami');

            const prev = CHAPTERS[currentIndex - 1];
            const next = CHAPTERS[currentIndex + 1];

            if (prev) {
                const prevLink = document.createElement('a');
                prevLink.href = prev.url;
                prevLink.className = 'chapter-nav-card chapter-nav-prev';
                prevLink.innerHTML = '<span class="chapter-nav-dir">← Předchozí</span><span class="chapter-nav-label">' + prev.label + '</span>';
                nav.appendChild(prevLink);
            } else {
                nav.appendChild(document.createElement('div')); // placeholder
            }

            if (next) {
                const nextLink = document.createElement('a');
                nextLink.href = next.url;
                nextLink.className = 'chapter-nav-card chapter-nav-next';
                nextLink.innerHTML = '<span class="chapter-nav-dir">Další →</span><span class="chapter-nav-label">' + next.label + '</span>';
                nav.appendChild(nextLink);
            }

            contentArea.appendChild(nav);
        }
    }
```

- [ ] **Step 2: Add prev/next CSS to `modern-style.css`**

```css
/* =====================
   PREV/NEXT NAVIGATION
   ===================== */

.chapter-nav {
    display: flex;
    gap: 1rem;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.chapter-nav-card {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 1rem 1.25rem;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    text-decoration: none;
    transition: border-color var(--transition), background var(--transition);
    min-width: 0;
}
.chapter-nav-card:hover {
    border-color: var(--color-primary);
    background: var(--bg-elevated);
    text-decoration: none;
}

.chapter-nav-next { text-align: right; margin-left: auto; }

.chapter-nav-dir {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: block;
    margin-bottom: 0.25rem;
}

.chapter-nav-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-primary);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chapter-nav-card:hover .chapter-nav-label { color: var(--color-primary); }

@media (max-width: 576px) {
    .chapter-nav { flex-direction: column; }
    .chapter-nav-next { text-align: left; margin-left: 0; }
}
```

- [ ] **Step 3: Verify prev/next nav in browser**

Navigate to any middle chapter (e.g., `/zakladni-koncepty`). At the bottom of the page there should be two cards: ← previous chapter, next chapter →. First and last pages show only one card.

- [ ] **Step 4: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/js/modern-script.js public/css/modern-style.css
git commit -m "feat: prev/next chapter navigation"
```

---

## Task 11: Final Cleanup & Polish

**Files:**
- Modify: `public/css/modern-style.css` (remove old dead rules, append remaining polish)
- Verify: `templates/base.html.twig` (remove old `.container.py-5` main wrapper if still present)

- [ ] **Step 1: Remove leftover old CSS from `modern-style.css`**

Search for and remove any remaining references to old variable names (`--primary-color`, `--secondary-color`, `--text-color`, `--border-color`, etc.) that may have leaked through. Also remove the old `.site-header`, `.main-nav`, `.nav-link`, `.menu-toggle` blocks if present.

Run in browser devtools: check for any console errors or obviously broken styles.

- [ ] **Step 2: Verify all pages render correctly**

Visit each of these pages and confirm no obvious layout breaks:
- `/` (homepage)
- `/zakladni-koncepty` (has diagrams and callout boxes)
- `/cqrs` (has diagrams and code blocks)
- `/prakticke-priklady` (heavy code examples)
- `/zdroje` (cards and links)

- [ ] **Step 3: Check mobile layout**

Narrow browser to 375px width. Verify:
- Sidebar hidden, hamburger visible
- Content readable, no horizontal overflow
- Code blocks scroll horizontally without breaking layout
- Prev/next nav stacks vertically

- [ ] **Step 4: Commit**

```bash
cd "W:/ddd-v-symfony"
git add public/css/modern-style.css templates/base.html.twig
git commit -m "chore: cleanup dead CSS and layout polish"
```

---

## Done

After Task 11, the redesign is complete. All 11 tasks produce a visually consistent dark mode site with:
- Fixed sidebar (260px) with active chapter highlighting
- Gold-accented top bar
- Dark typography with serif headings
- Dark code blocks with language badge and copy button
- Inverted PlantUML diagrams
- Prev/next chapter navigation
- Mobile-responsive layout
