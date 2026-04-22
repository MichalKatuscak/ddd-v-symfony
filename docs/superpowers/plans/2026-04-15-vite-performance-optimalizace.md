# Vite + Performance Optimalizace — Implementační plán

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Zavést Vite build pipeline pro minifikaci/hashing CSS+JS, self-hostovat fonty pro eliminaci render-blocking requestů, opravit og:image kompatibilitu, přidat CSP a prefers-reduced-motion. Cíl: Lighthouse Performance 95+.

**Architecture:** Vite s `pentatrion/vite-bundle` jako build nástroj. Zdrojové assety se přesunou z `public/css|js/` do `assets/`. Vite kompiluje do `public/build/`. Google Fonts se nahradí self-hostovanými WOFF2 soubory. External libraries (highlight.js, svg-pan-zoom) se nainstalují přes npm místo CDN.

**Tech Stack:** Vite 6, vite-plugin-symfony, pentatrion/vite-bundle, npm

---

### Task 1: Opravit og:image (WebP → PNG pro sociální sítě)

**Files:**
- Create: `public/images/social.png` (optimized, ~200KB)
- Modify: `templates/base.html.twig:24,31`

Facebook, LinkedIn a další crawlery nepodporují WebP v og:image. Ponecháme WebP pro jiné účely, ale og:image musí být PNG.

- [ ] **Step 1: Vytvořit optimalizovaný PNG ze stávajícího WebP**

```bash
dwebp public/images/social.webp -o /tmp/social-full.png
# Zmenšit na 1200x630 (optimální OG velikost) a komprimovat
convert /tmp/social-full.png -resize 1200x630^ -gravity center -extent 1200x630 -quality 85 public/images/social.png
```

Pokud `convert` (ImageMagick) není k dispozici:
```bash
sudo apt-get install -y imagemagick
```

- [ ] **Step 2: Aktualizovat og:image a twitter:image v base.html.twig**

V `templates/base.html.twig` změnit oba řádky s `social.webp` zpět na `social.png`:

```twig
{# řádek 24 #}
<meta property="og:image" content="{{ app.request.schemeAndHttpHost }}{{ asset('images/social.png') }}">

{# řádek 31 #}
<meta property="twitter:image" content="{{ app.request.schemeAndHttpHost }}{{ asset('images/social.png') }}">
```

- [ ] **Step 3: Ověřit velikost**

```bash
ls -la public/images/social.png
# Očekáváno: < 300 KB
```

- [ ] **Step 4: Commit**

```bash
git add public/images/social.png templates/base.html.twig
git commit -m "fix: og:image zpět na optimalizovaný PNG pro kompatibilitu se sociálními sítěmi"
```

---

### Task 2: Nainstalovat Vite a pentatrion/vite-bundle

**Files:**
- Create: `package.json`
- Create: `vite.config.js`
- Modify: `composer.json`
- Modify: `config/packages/pentatrion_vite.yaml` (auto-vytvořeno receptem)

- [ ] **Step 1: Nainstalovat pentatrion/vite-bundle přes Composer**

```bash
composer require pentatrion/vite-bundle
```

Potvrdit recept (odpověď `y`). Vytvoří konfigurační soubory automaticky.

- [ ] **Step 2: Nainstalovat npm závislosti**

```bash
npm init -y
npm install --save-dev vite vite-plugin-symfony
```

- [ ] **Step 3: Nainstalovat highlight.js a svg-pan-zoom jako npm balíčky**

```bash
npm install highlight.js svg-pan-zoom
```

- [ ] **Step 4: Vytvořit vite.config.js**

```js
import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig({
    plugins: [
        symfonyPlugin(),
    ],
    build: {
        rollupOptions: {
            input: {
                app: './assets/app.js',
            },
        },
    },
});
```

- [ ] **Step 5: Přidat node_modules a public/build do .gitignore**

Zkontrolovat `.gitignore` a přidat:
```
/node_modules/
/public/build/
```

- [ ] **Step 6: Commit**

```bash
git add package.json package-lock.json vite.config.js .gitignore composer.json composer.lock config/
git commit -m "feat: nainstalovat Vite s pentatrion/vite-bundle"
```

---

### Task 3: Přesunout assety do assets/ a vytvořit entry point

**Files:**
- Create: `assets/app.js` (entry point)
- Create: `assets/styles/modern-style.css` (přesunout z public/)
- Create: `assets/styles/code-style.css` (přesunout z public/)
- Create: `assets/scripts/modern-script.js` (přesunout z public/)
- Create: `assets/scripts/code-script.js` (přesunout z public/)

- [ ] **Step 1: Vytvořit adresářovou strukturu**

```bash
mkdir -p assets/styles assets/scripts assets/fonts
```

- [ ] **Step 2: Přesunout CSS a JS soubory**

```bash
mv public/css/modern-style.css assets/styles/modern-style.css
mv public/css/code-style.css assets/styles/code-style.css
mv public/js/modern-script.js assets/scripts/modern-script.js
mv public/js/code-script.js assets/scripts/code-script.js
```

- [ ] **Step 3: Vytvořit assets/app.js (hlavní entry point)**

```js
// Styles
import './styles/modern-style.css';
import './styles/code-style.css';

// highlight.js — importovat pouze potřebné jazyky
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import yaml from 'highlight.js/lib/languages/yaml';
import xml from 'highlight.js/lib/languages/xml';
import bash from 'highlight.js/lib/languages/bash';
import json from 'highlight.js/lib/languages/json';
import javascript from 'highlight.js/lib/languages/javascript';
import sql from 'highlight.js/lib/languages/sql';
import plaintext from 'highlight.js/lib/languages/plaintext';
import 'highlight.js/styles/atom-one-dark.css';

hljs.registerLanguage('php', php);
hljs.registerLanguage('yaml', yaml);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('html', xml);
hljs.registerLanguage('twig', xml);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('shell', bash);
hljs.registerLanguage('json', json);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('plaintext', plaintext);

// svg-pan-zoom
import svgPanZoom from 'svg-pan-zoom';

// App scripts
import './scripts/modern-script.js';
import './scripts/code-script.js';

// Init on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
    // Syntax highlighting
    document.querySelectorAll('pre code').forEach(function (el) {
        hljs.highlightElement(el);
    });

    // SVG Pan/Zoom for diagrams
    document.querySelectorAll('.diagram-container svg').forEach(function (svgElement) {
        svgPanZoom(svgElement, {
            zoomEnabled: true,
            controlIconsEnabled: true,
            fit: true,
            center: true,
            minZoom: 0.5,
            maxZoom: 20,
        });
    });
});
```

- [ ] **Step 4: Upravit modern-script.js a code-script.js**

Oba soubory zabalit jako DOMContentLoaded listener se exportem (neměnit logiku).
V `assets/scripts/modern-script.js` ponechat stávající kód beze změn — již je obalený `DOMContentLoaded`.
V `assets/scripts/code-script.js` ponechat beze změn — také DOMContentLoaded.

Ale: oba soubory mají vlastní `document.addEventListener('DOMContentLoaded', ...)` wrapper. To je v pořádku — Vite je importuje jako moduly, DOMContentLoaded se volá správně.

- [ ] **Step 5: Commit**

```bash
git add assets/ && git rm public/css/modern-style.css public/css/code-style.css public/js/modern-script.js public/js/code-script.js
git commit -m "refactor: přesunout assety do assets/ pro Vite build pipeline"
```

---

### Task 4: Aktualizovat base.html.twig pro Vite

**Files:**
- Modify: `templates/base.html.twig`

- [ ] **Step 1: Nahradit staré CSS/JS odkazy za Vite tagy**

Celý `<head>` a skripty na konci `<body>` se změní. Nahradit:

Staré CSS (řádky 39-43):
```twig
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css" integrity="..." crossorigin="anonymous">
<link rel="stylesheet" href="{{ asset('css/modern-style.css') }}">
<link rel="stylesheet" href="{{ asset('css/code-style.css') }}">
```

Nové CSS:
```twig
{% block stylesheets %}
    {{ vite_entry_link_tags('app') }}
{% endblock %}
```

Staré JS (řádky 160-163):
```twig
<script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js" integrity="..." crossorigin="anonymous" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js" integrity="..." crossorigin="anonymous" defer></script>
<script src="{{ asset('js/modern-script.js') }}" defer></script>
<script src="{{ asset('js/code-script.js') }}" defer></script>
```

Plus inline skripty pro hljs init a svgPanZoom init (řádky 164-181) — **smazat** (přesunuto do app.js).

Nové JS:
```twig
{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

Inline skript pro TOC sidebar (řádky 182-228) **ponechat** — je specifický pro runtime DOM a nemá smysl ho bundlovat.

- [ ] **Step 2: Ověřit Vite dev server**

```bash
npx vite
```

V jiném terminálu spustit PHP server a otevřít stránku. Ověřit, že CSS i JS fungují s HMR.

- [ ] **Step 3: Vyzkoušet produkční build**

```bash
npx vite build
```

Ověřit, že `public/build/` obsahuje hashované soubory.

- [ ] **Step 4: Commit**

```bash
git add templates/base.html.twig public/build/
git commit -m "feat: napojit base.html.twig na Vite build"
```

---

### Task 5: Self-hostovat Google Fonts

**Files:**
- Create: `assets/fonts/nunito-400.woff2`
- Create: `assets/fonts/nunito-600.woff2`
- Create: `assets/fonts/nunito-700.woff2`
- Create: `assets/fonts/merriweather-400.woff2`
- Create: `assets/fonts/merriweather-700.woff2`
- Create: `assets/fonts/merriweather-900.woff2`
- Create: `assets/fonts/jetbrains-mono-400.woff2`
- Create: `assets/fonts/jetbrains-mono-500.woff2`
- Create: `assets/styles/fonts.css`
- Modify: `assets/app.js`
- Modify: `templates/base.html.twig`

Toto je **největší performance win** — eliminuje render-blocking Google Fonts request (~1.5s).

- [ ] **Step 1: Stáhnout fonty z Google Fonts**

Použít google-webfonts-helper nebo přímé URL. Stáhnout WOFF2 (latin-ext subset):

```bash
cd assets/fonts

# Nunito
curl -o nunito-400.woff2 "https://fonts.gstatic.com/s/nunito/v26/XRXI3I6Li01BKofiOc5wtlZ2di8HDIkhdTQ3j6zbXWjgevT5.woff2"
curl -o nunito-600.woff2 "https://fonts.gstatic.com/s/nunito/v26/XRXI3I6Li01BKofiOc5wtlZ2di8HDIkhdTo3j6zbXWjgevT5.woff2"
curl -o nunito-700.woff2 "https://fonts.gstatic.com/s/nunito/v26/XRXI3I6Li01BKofiOc5wtlZ2di8HDIkhdRM3j6zbXWjgevT5.woff2"

# Merriweather
curl -o merriweather-400.woff2 "https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-fCZMdeX3rsHo.woff2"
curl -o merriweather-700.woff2 "https://fonts.gstatic.com/s/merriweather/v30/u-4n0qyriQwlOrhSvowK_l52xwNZVcf6hPvhPUWH.woff2"
curl -o merriweather-900.woff2 "https://fonts.gstatic.com/s/merriweather/v30/u-4n0qyriQwlOrhSvowK_l52_w1ZVcf6hPvhPUWH.woff2"

# JetBrains Mono
curl -o jetbrains-mono-400.woff2 "https://fonts.gstatic.com/s/jetbrainsmono/v18/tDbY2o-flEEny0FZhsfKu5WU4zr3E_BX0PnT8RD8yKxjPVmUsaaDhw.woff2"
curl -o jetbrains-mono-500.woff2 "https://fonts.gstatic.com/s/jetbrainsmono/v18/tDbY2o-flEEny0FZhsfKu5WU4zr3E_BX0PnT8RD8-axjPVmUsaaDhw.woff2"

cd ../..
```

Poznámka: Přesné URL se mohou lišit. Alternativně stáhnout z https://gwfh.mranftl.com/fonts — vyhledat fonty, vybrat latin-ext charset, stáhnout WOFF2.

- [ ] **Step 2: Vytvořit assets/styles/fonts.css**

```css
/* Self-hosted Google Fonts — latin-ext subset */

/* Nunito */
@font-face {
    font-family: 'Nunito';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('../fonts/nunito-400.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}
@font-face {
    font-family: 'Nunito';
    font-style: normal;
    font-weight: 600;
    font-display: swap;
    src: url('../fonts/nunito-600.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}
@font-face {
    font-family: 'Nunito';
    font-style: normal;
    font-weight: 700;
    font-display: swap;
    src: url('../fonts/nunito-700.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}

/* Merriweather */
@font-face {
    font-family: 'Merriweather';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('../fonts/merriweather-400.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}
@font-face {
    font-family: 'Merriweather';
    font-style: normal;
    font-weight: 700;
    font-display: swap;
    src: url('../fonts/merriweather-700.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}
@font-face {
    font-family: 'Merriweather';
    font-style: normal;
    font-weight: 900;
    font-display: swap;
    src: url('../fonts/merriweather-900.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}

/* JetBrains Mono */
@font-face {
    font-family: 'JetBrains Mono';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('../fonts/jetbrains-mono-400.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}
@font-face {
    font-family: 'JetBrains Mono';
    font-style: normal;
    font-weight: 500;
    font-display: swap;
    src: url('../fonts/jetbrains-mono-500.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF, U+0000-00FF;
}
```

- [ ] **Step 3: Importovat fonts.css v app.js (jako první import)**

Na začátek `assets/app.js` přidat:
```js
import './styles/fonts.css';
```

- [ ] **Step 4: Odstranit Google Fonts z base.html.twig**

Smazat tyto řádky:
```twig
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=..." rel="stylesheet">
```

- [ ] **Step 5: Ověřit, že fonty se načítají**

```bash
npx vite build
# Spustit PHP server a ověřit v prohlížeči
```

- [ ] **Step 6: Commit**

```bash
git add assets/fonts/ assets/styles/fonts.css assets/app.js templates/base.html.twig
git commit -m "perf: self-hostovat Google Fonts pro eliminaci render-blocking requestu"
```

---

### Task 6: Přidat prefers-reduced-motion a CSP

**Files:**
- Modify: `assets/styles/modern-style.css`
- Modify: `public/.htaccess`

- [ ] **Step 1: Přidat prefers-reduced-motion do modern-style.css**

Na konec souboru přidat:

```css
/* Respektovat preference uživatele ohledně animací */
@media (prefers-reduced-motion: reduce) {
    html {
        scroll-behavior: auto;
    }
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

- [ ] **Step 2: Aktivovat CSP v .htaccess**

Nahradit zakomentovaný CSP řádek (řádek 127) za:

```apache
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self' ws://localhost:* http://localhost:*; object-src 'none'; frame-ancestors 'self';"
```

Poznámka: `'unsafe-inline'` je potřeba pro inline skripty v base.html.twig (TOC generátor, structured data). `ws://localhost:*` a `http://localhost:*` jsou pro Vite HMR v dev režimu — na produkci je můžete odebrat.

- [ ] **Step 3: Commit**

```bash
git add assets/styles/modern-style.css public/.htaccess
git commit -m "feat: přidat prefers-reduced-motion a aktivovat CSP hlavičku"
```

---

### Task 7: Aktualizovat sitemap.xml

**Files:**
- Modify: `public/sitemap.xml`

- [ ] **Step 1: Aktualizovat lastmod na dnešní datum**

Nahradit všechna `<lastmod>2025-04-24</lastmod>` za `<lastmod>2026-04-15</lastmod>` v celém souboru.

- [ ] **Step 2: Ověřit, že všechny route URL v sitemapu odpovídají aktuálním routes**

```bash
php bin/console debug:router --format=txt | grep -E '^\s' | awk '{print $1}'
```

Porovnat s URL v sitemap.xml.

- [ ] **Step 3: Commit**

```bash
git add public/sitemap.xml
git commit -m "chore: aktualizovat sitemap lastmod na 2026-04-15"
```

---

### Task 8: Přesunout inline TOC skript do Vite bundlu

**Files:**
- Create: `assets/scripts/toc-sidebar.js`
- Modify: `assets/app.js`
- Modify: `templates/base.html.twig`

Přesunutí inline skriptu umožní odstranit `'unsafe-inline'` z CSP pro script-src (bezpečnější).

- [ ] **Step 1: Extrahovat TOC skript do samostatného souboru**

Vytvořit `assets/scripts/toc-sidebar.js` s obsahem inline skriptu z base.html.twig (řádky 182-228), bez IIFE wrapperu:

```js
// toc-sidebar.js — Generates table of contents from article headings
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
```

- [ ] **Step 2: Importovat v app.js**

Přidat na konec importů v `assets/app.js`:
```js
import './scripts/toc-sidebar.js';
```

- [ ] **Step 3: Smazat inline TOC skript z base.html.twig**

Smazat celý blok `<script>` s TOC generátorem (řádky 182-228).

- [ ] **Step 4: Zpřísnit CSP — odebrat unsafe-inline ze script-src**

V `.htaccess` změnit CSP:
```apache
Header set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; object-src 'none'; frame-ancestors 'self';"
```

Poznámka: `'unsafe-inline'` zůstává ve `style-src` kvůli inline stylům, které generuje highlight.js. Z `script-src` ho ale můžeme odebrat, protože structured data (`type="application/ld+json"`) nepotřebují `script-src`.

- [ ] **Step 5: Commit**

```bash
git add assets/scripts/toc-sidebar.js assets/app.js templates/base.html.twig public/.htaccess
git commit -m "refactor: přesunout inline TOC skript do Vite bundlu a zpřísnit CSP"
```

---

### Task 9: Produkční build, Lighthouse audit a finální push

**Files:**
- Modify: `public/build/` (regenerovat)

- [ ] **Step 1: Vytvořit produkční build**

```bash
npx vite build
```

Ověřit výstup:
```bash
ls -la public/build/assets/
# Očekáváno: hashované .js a .css soubory, fonty
```

- [ ] **Step 2: Spustit Lighthouse audit**

```bash
npx lighthouse http://localhost:8000/ --output=json --output-path=/tmp/lighthouse-after.json --chrome-flags="--headless --no-sandbox" --only-categories=performance,accessibility,best-practices,seo
```

Parsovat výsledky a porovnat s baseline (Performance: 76, Accessibility: 100, Best Practices: 100, SEO: 69).

Cíl: Performance 90+.

- [ ] **Step 3: Ověřit přes Playwright MCP**

Otevřít hlavní stránky v prohlížeči a ověřit:
- Homepage se načítá správně
- Fonty se zobrazují
- Syntax highlighting funguje
- SVG diagramy mají pan/zoom
- Skip-link funguje
- Prev/Next navigace funguje
- Mobilní zobrazení je v pořádku

- [ ] **Step 4: Finální commit a push**

```bash
git add -A
git commit -m "perf: Vite build pipeline — minifikace, self-hosted fonty, optimalizované assety"
git push
```
