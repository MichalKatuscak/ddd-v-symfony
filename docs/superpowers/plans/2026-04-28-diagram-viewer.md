# Diagram viewer — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Přidat zoom/pan/fullscreen viewer do `_partials/diagram.html.twig` (toolbar `+ − ⤢ ⛶`), nahradit stávající mobilní horizontal-scroll. Žádná knihovna — vanilla JS modul.

**Architecture:** Per-instance controller udržuje stav `{ scale, tx, ty }` a aplikuje jediný CSS `transform` na vnitřní `<svg>`/`<img>`. Pointer Events pro pan, lazy-vytvořený modal s klonem SVG pro fullscreen.

**Tech Stack:** Twig, Vanilla JS (ES modules), CSS variables (existující design tokens), Vite (existující build pipeline).

**Spec:** `docs/superpowers/specs/2026-04-28-diagram-viewer-design.md` — referenční zdroj všech rozhodnutí, podívej se na něj v případě nejasnosti.

---

## Verifikační poznámka

Projekt nemá žádný test suite (ani frontend, ani backend) a není v plánu ho zavádět. **Verifikace je manuální v prohlížeči**: po každé úpravě spustit `symfony server:start`, otevřít stránku s diagramem (např. `/co-je-ddd` — má diagram FIG. 01.5-A), ověřit chování. U JS změn doporučuji DevTools console pro chyby.

Každý task končí commitem. Při problému s pre-commit hookem **nikdy** nepoužívat `--no-verify` — vyřešit root cause.

---

## File Structure

| Soubor | Akce | Odpovědnost |
|---|---|---|
| `templates/_partials/diagram.html.twig` | modify | DOM struktura — figure, head, toolbar, stage |
| `assets/styles/article.css` | modify | Vizuální stránka — toolbar, stage, modal CSS; smazat mobilní overflow blok |
| `assets/scripts/diagram-viewer.js` | **create** | Per-instance controller — zoom/pan/modal logika |
| `assets/app.js` | modify | Import nového modulu |
| `assets/scripts/code-block.js` | modify | Odstranit `.diagram` z `tabindex` přidávání (overflow scroll už tam nebude) |

---

## Task 1: Twig partial + CSS toolbar (hidden bez JS)

**Cíl:** DOM dostane toolbar a `.diagram-stage`. Toolbar je v této fázi ještě CSS-skrytý (`.diagram:not(.diagram-js) .diagram-toolbar { display: none; }`), takže stránka vypadá identicky jako dnes.

**Files:**
- Modify: `templates/_partials/diagram.html.twig`
- Modify: `assets/styles/article.css:755-825` (sekce `.diagram`)

- [ ] **Step 1: Upravit partial — přidat `data-diagram`, toolbar a stage wrapper**

Otevři `templates/_partials/diagram.html.twig` a přepiš ho takto (hlídka: `_safe_svg` logika musí zůstat — slouží proti deformaci PlantUML):

```twig
{#
  args:
    fig         – např. '04.4-A'
    title       – např. 'Tok jednoho příkazu'
    src         – relativní cesta v public/ k SVG/PNG (např. 'images/diagrams/4_implementation/command_flow.svg')
    inline      – alternativně inline SVG/HTML jako string (volitelné, vyšší priorita než src)
    caption     – HTML caption pod diagramem (volitelné). Konvence: caption se používá jen
                  tehdy, když diagram obsahuje vizuální sémantiku, kterou nelze pochopit bez
                  vysvětlení (barvy, šipky, vzor čar). Pro běžné architektonické diagramy stačí
                  hlavička FIG. + title bez captionu.
    alt         – alt text pro img (volitelné, default = title)
#}
<figure class="diagram" data-diagram>
    <header class="diagram-head">
        {% if fig is defined and fig %}<span class="diagram-num">FIG. {{ fig }}</span>{% endif %}
        {% if title is defined and title %}<span class="diagram-title">{{ title }}</span>{% endif %}
        <div class="diagram-toolbar" role="toolbar" aria-label="Ovládání diagramu">
            <button type="button" class="mv-btn" data-action="zoom-in"    aria-label="Přiblížit">+</button>
            <button type="button" class="mv-btn" data-action="zoom-out"   aria-label="Oddálit">−</button>
            <button type="button" class="mv-btn" data-action="fit"        aria-label="Přizpůsobit">⤢</button>
            <button type="button" class="mv-btn" data-action="fullscreen" aria-label="Celá obrazovka">⛶</button>
        </div>
    </header>
    <div class="diagram-stage" role="img" aria-label="{{ alt|default(title|default('Diagram')) }}">
        {% if inline is defined and inline %}
            {# PlantUML generuje SVG s preserveAspectRatio="none" + inline pixel
               dimenzemi (style="width:1698px;height:876px"), což způsobuje deformaci
               obsahu, když CSS sníží šířku na mobilu. Strip obojího — necháme browser
               škálovat dle viewBox a preserveAspectRatio default (xMidYMid meet). #}
            {% set _safe_svg = inline
                |replace({'preserveAspectRatio="none"': 'preserveAspectRatio="xMidYMid meet"'})
                |replace({' style="width:': ' data-orig-style="width:'}) %}
            {{ _safe_svg|raw }}
        {% elseif src is defined and src %}
            <img src="{{ asset(src) }}" alt="{{ alt|default(title|default('Diagram')) }}" loading="lazy">
        {% endif %}
    </div>
    {% if caption is defined and caption %}
        <figcaption class="diagram-caption">{{ caption|raw }}</figcaption>
    {% endif %}
</figure>
```

- [ ] **Step 2: Upravit CSS — flex pro head, hidden toolbar bez JS, stage**

V `assets/styles/article.css` najdi sekci `.diagram` (cca řádek 755) a přepiš ji takto. **Smaž celý existující `@media (max-width: 540px)` blok pro `.diagram` — viewer ho nahrazuje.**

```css
/* ── Diagram ───────────────────────────────────────────────────────────── */
.diagram {
  margin: var(--s-6) 0;
  border: 1px solid var(--stroke);
  background: var(--bg-1);
  border-radius: var(--r-2);
  overflow: hidden;
}
.diagram-head {
  display: flex;
  align-items: center;
  gap: var(--s-3);
  padding: var(--s-3) var(--s-4);
  border-bottom: 1px solid var(--stroke);
  font-family: var(--font-mono);
  font-size: var(--t-xs);
}
.diagram-num {
  color: var(--accent);
  letter-spacing: var(--tracking-wide);
  font-weight: 500;
}
.diagram-title {
  color: var(--fg-muted);
  letter-spacing: var(--tracking-mono);
}

/* Toolbar */
.diagram-toolbar {
  margin-left: auto;
  display: flex;
  gap: var(--s-1);
}
.mv-btn {
  font-family: var(--font-mono);
  font-size: var(--t-xs);
  line-height: 1;
  padding: var(--s-1) var(--s-2);
  background: transparent;
  color: var(--fg-muted);
  border: 1px solid var(--stroke);
  border-radius: var(--r-1);
  cursor: pointer;
  min-width: 1.75rem;
}
.mv-btn:hover:not(:disabled) {
  color: var(--accent);
  border-color: var(--accent);
}
.mv-btn:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 1px;
}
.mv-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Toolbar je skrytý dokud JS nepřidá .diagram-js (no-JS fallback) */
.diagram:not(.diagram-js) .diagram-toolbar {
  display: none;
}

/* Stage (viewport pro SVG/img) */
.diagram-stage {
  overflow: hidden;
  touch-action: none;
  position: relative;
}
.diagram-stage > svg,
.diagram-stage > img {
  display: block;
  width: 100%;
  height: auto;
  background: var(--bg-1);
  padding: var(--s-4);
  box-sizing: border-box;
  transform-origin: 0 0;
  will-change: transform;
  transition: transform 0.15s ease;
}
.diagram-js .diagram-stage {
  cursor: grab;
}
.diagram-js .diagram-stage.is-dragging {
  cursor: grabbing;
  user-select: none;
}
.diagram-js .diagram-stage.is-dragging > svg,
.diagram-js .diagram-stage.is-dragging > img {
  transition: none;
}

.diagram-caption {
  padding: var(--s-3) var(--s-4);
  border-top: 1px solid var(--stroke);
  font-family: var(--font-mono);
  font-size: var(--t-xs);
  color: var(--fg-muted);
  letter-spacing: var(--tracking-mono);
  display: flex;
  gap: var(--s-2);
  flex-wrap: wrap;
  align-items: center;
}
.d-cap-num {
  color: var(--accent);
  font-weight: 600;
}

@media (prefers-reduced-motion: reduce) {
  .diagram-stage > svg,
  .diagram-stage > img {
    transition: none;
  }
}
```

**Pozn.:** `@media (max-width: 540px)` blok pro `.diagram` musí být **smazaný** — pokud tam stále je, smaž ho. Nahrazuje ho responsive zoom/pan UX.

- [ ] **Step 3: Verifikace v prohlížeči**

```bash
symfony server:start
```

Otevři `http://localhost:8000/co-je-ddd` (nebo jakoukoli stránku s diagramem). Očekávané chování:
- Diagram se zobrazí **stejně jako dnes** (žádný toolbar viditelný — bez `.diagram-js` class CSS toolbar skrývá).
- Žádné chyby v console.
- Na mobile width (DevTools, viewport 375px): diagram se vejde do šířky (žádný horizontal scroll). Pokud je text uvnitř malý a nečitelný, je to OK — to vyřeší zoom v dalších taskech.

- [ ] **Step 4: Commit**

```bash
git add templates/_partials/diagram.html.twig assets/styles/article.css
git commit -m "feat(diagram): toolbar a stage wrapper v partialu (skrytý bez JS)

Připravuje DOM strukturu pro budoucí JS viewer.
Smazán mobilní overflow-x scroll fallback — bude nahrazen zoom/pan."
```

---

## Task 2: JS skeleton — `.diagram-js` class + import v app.js

**Cíl:** Vytvořit `diagram-viewer.js` modul, který po načtení DOM přidá `.diagram-js` class na všechny `[data-diagram]` elementy. Tím se odhalí toolbar (zatím nefunkční tlačítka).

**Files:**
- Create: `assets/scripts/diagram-viewer.js`
- Modify: `assets/app.js`

- [ ] **Step 1: Vytvořit modul s minimálním DiagramViewer skeletem**

Vytvoř `assets/scripts/diagram-viewer.js`:

```js
// ──────────────────────────────────────────────────────────────────────────
// Diagram viewer — zoom, pan, fullscreen pro inline SVG/img diagramy
// ──────────────────────────────────────────────────────────────────────────
//
// Per-instance controller drží stav { scale, tx, ty } a aplikuje jediný
// CSS transform na vnitřní <svg>/<img>. Bez závislostí.

const INLINE_MIN_SCALE = 0.5;
const INLINE_MAX_SCALE = 4;
const MODAL_MIN_SCALE  = 0.25;
const MODAL_MAX_SCALE  = 8;
const STEP             = 1.25;

class DiagramViewer {
  constructor(figureEl) {
    this.figure = figureEl;
    this.stage = figureEl.querySelector('.diagram-stage');
    this.content = this.stage && this.stage.firstElementChild;
    this.toolbar = figureEl.querySelector('.diagram-toolbar');
    if (!this.stage || !this.content || !this.toolbar) return;

    this.scale = 1;
    this.tx = 0;
    this.ty = 0;

    this.figure.classList.add('diagram-js');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-diagram]').forEach((el) => new DiagramViewer(el));
});
```

- [ ] **Step 2: Importovat modul v app.js**

V `assets/app.js` přidej import vedle ostatních scriptů (po `article-toc.js`):

```js
import './scripts/article-toc.js';
import './scripts/diagram-viewer.js';
```

- [ ] **Step 3: Verifikace**

Restart Vite (pokud běží) a refresh prohlížeče.

- Otevři DevTools → Elements, najdi `<figure class="diagram">` — měla by mít přidanou class `diagram-js`.
- Toolbar `+ − ⤢ ⛶` by měl být teď **viditelný** vpravo v hlavičce diagramu.
- Tlačítka po kliku nedělají nic (správné — funkce přijde v tasku 3).
- Žádné chyby v console.

- [ ] **Step 4: Commit**

```bash
git add assets/scripts/diagram-viewer.js assets/app.js
git commit -m "feat(diagram): JS skeleton, toolbar viditelný"
```

---

## Task 3: Zoom in / out / fit + disabled stavy

**Cíl:** `+`, `−`, `⤢` tlačítka mění scale a resetují view. Zoom probíhá kolem středu viewportu. Tlačítka jsou disabled na limitech.

**Files:**
- Modify: `assets/scripts/diagram-viewer.js`

- [ ] **Step 1: Rozšířit DiagramViewer — applyTransform, button delegation, zoom akce**

Přepiš obsah `assets/scripts/diagram-viewer.js`:

```js
// ──────────────────────────────────────────────────────────────────────────
// Diagram viewer — zoom, pan, fullscreen pro inline SVG/img diagramy
// ──────────────────────────────────────────────────────────────────────────

const INLINE_MIN_SCALE = 0.5;
const INLINE_MAX_SCALE = 4;
const MODAL_MIN_SCALE  = 0.25;
const MODAL_MAX_SCALE  = 8;
const STEP             = 1.25;

const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

class DiagramViewer {
  constructor(figureEl, opts = {}) {
    this.figure = figureEl;
    this.stage = figureEl.querySelector('.diagram-stage');
    this.content = this.stage && this.stage.firstElementChild;
    this.toolbar = figureEl.querySelector('.diagram-toolbar');
    if (!this.stage || !this.content || !this.toolbar) return;

    this.minScale = opts.minScale ?? INLINE_MIN_SCALE;
    this.maxScale = opts.maxScale ?? INLINE_MAX_SCALE;

    this.scale = 1;
    this.tx = 0;
    this.ty = 0;

    this.figure.classList.add('diagram-js');
    this._cacheButtons();
    this._bindToolbar();
    this._updateButtonStates();
  }

  _cacheButtons() {
    this.btnZoomIn  = this.toolbar.querySelector('[data-action="zoom-in"]');
    this.btnZoomOut = this.toolbar.querySelector('[data-action="zoom-out"]');
    this.btnFit     = this.toolbar.querySelector('[data-action="fit"]');
    this.btnFs      = this.toolbar.querySelector('[data-action="fullscreen"]');
  }

  _bindToolbar() {
    this.toolbar.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action]');
      if (!btn || btn.disabled) return;
      const action = btn.dataset.action;
      if (action === 'zoom-in')    this.zoomBy(STEP);
      else if (action === 'zoom-out') this.zoomBy(1 / STEP);
      else if (action === 'fit')      this.fit();
      // 'fullscreen' přijde v tasku 5
    });
  }

  _applyTransform() {
    this.content.style.transform =
      `translate(${this.tx}px, ${this.ty}px) scale(${this.scale})`;
    this._updateButtonStates();
  }

  _updateButtonStates() {
    if (this.btnZoomIn)  this.btnZoomIn.disabled  = this.scale >= this.maxScale - 1e-6;
    if (this.btnZoomOut) this.btnZoomOut.disabled = this.scale <= this.minScale + 1e-6;
    if (this.btnFit)     this.btnFit.disabled =
      this.scale === 1 && this.tx === 0 && this.ty === 0;
  }

  // Zoom o `factor`, kolem středu viewportu stage.
  zoomBy(factor) {
    const newScale = clamp(this.scale * factor, this.minScale, this.maxScale);
    if (newScale === this.scale) return;
    const rect = this.stage.getBoundingClientRect();
    this._zoomAt(rect.width / 2, rect.height / 2, newScale);
  }

  // Zoom kolem konkrétního bodu (cx, cy) v souřadnicích stage.
  _zoomAt(cx, cy, newScale) {
    // Bod v souřadnicích diagramu, který je teď pod (cx, cy):
    const dx = (cx - this.tx) / this.scale;
    const dy = (cy - this.ty) / this.scale;
    this.scale = newScale;
    this.tx = cx - dx * this.scale;
    this.ty = cy - dy * this.scale;
    this._applyTransform();
  }

  fit() {
    this.scale = 1;
    this.tx = 0;
    this.ty = 0;
    this._applyTransform();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-diagram]').forEach((el) => new DiagramViewer(el));
});
```

- [ ] **Step 2: Verifikace v prohlížeči**

Refresh stránky s diagramem. Ověř:

1. Klik na `+` — diagram se zvětší o 25% kolem středu.
2. Opakovaný klik na `+` — po několika klicích se zdisabluje (max 4×).
3. Klik na `−` — diagram se zmenší. Limit min 0.5×.
4. Klik na `⤢` (fit) — zoom se vrátí na 1×, pozice 0,0. Tlačítko se zdisabluje (žádný zoom navíc fit nepřinese).
5. Když je view ve výchozím stavu, **`⤢` je disabled, `−` má prostor (může jít na 0.5)**.
6. Žádné chyby v console.

- [ ] **Step 3: Commit**

```bash
git add assets/scripts/diagram-viewer.js
git commit -m "feat(diagram): zoom in/out/fit s limity a disabled stavy"
```

---

## Task 4: Pan (drag) přes Pointer Events

**Cíl:** Když je `scale > 1`, drag myší / dotykem posune diagram. Clamping zajistí, že obraz neuteče za hranice.

**Files:**
- Modify: `assets/scripts/diagram-viewer.js`

- [ ] **Step 1: Přidat pan handlery a clamp logiku**

V `assets/scripts/diagram-viewer.js` rozšiř konstruktor o volání `_bindPan()`, přidej metody `_bindPan()`, `_clamp()`, a uprav `_zoomAt` a `fit` aby volaly `_clamp()` před `_applyTransform()`.

V konstruktoru za `this._bindToolbar();` přidej:

```js
    this._bindPan();
```

V těle třídy (např. před `zoomBy`) přidej:

```js
  _bindPan() {
    let startX = 0, startY = 0, startTx = 0, startTy = 0;
    let activePointerId = null;

    this.stage.addEventListener('pointerdown', (e) => {
      if (this.scale <= 1) return;
      activePointerId = e.pointerId;
      this.stage.setPointerCapture(e.pointerId);
      this.stage.classList.add('is-dragging');
      startX = e.clientX;
      startY = e.clientY;
      startTx = this.tx;
      startTy = this.ty;
    });

    this.stage.addEventListener('pointermove', (e) => {
      if (e.pointerId !== activePointerId) return;
      this.tx = startTx + (e.clientX - startX);
      this.ty = startTy + (e.clientY - startY);
      this._clamp();
      this._applyTransform();
    });

    const onEnd = (e) => {
      if (e.pointerId !== activePointerId) return;
      this.stage.releasePointerCapture(e.pointerId);
      activePointerId = null;
      this.stage.classList.remove('is-dragging');
    };
    this.stage.addEventListener('pointerup', onEnd);
    this.stage.addEventListener('pointercancel', onEnd);
  }

  _clamp() {
    const stageRect = this.stage.getBoundingClientRect();
    // Reálné rozměry obsahu při scale=1 (před transformem):
    // <img> má naturalWidth, <svg> má viewBox.baseVal.width.
    let baseW, baseH;
    if (this.content.tagName.toLowerCase() === 'img') {
      baseW = this.content.naturalWidth || stageRect.width;
      baseH = this.content.naturalHeight || stageRect.height;
    } else {
      const vb = this.content.viewBox && this.content.viewBox.baseVal;
      baseW = (vb && vb.width)  || stageRect.width;
      baseH = (vb && vb.height) || stageRect.height;
    }
    // Skutečné rozměry po scale (transform-origin: 0 0):
    const contentW = baseW * this.scale;
    const contentH = baseH * this.scale;

    // Rozsah pro tx: pokud je obsah větší než stage, povoľ posun tak, aby
    // hrany obsahu nemohly opustit hrany stage. Pokud menší, drž na 0.
    if (contentW <= stageRect.width) {
      this.tx = 0;
    } else {
      const minTx = stageRect.width - contentW;
      const maxTx = 0;
      this.tx = clamp(this.tx, minTx, maxTx);
    }
    if (contentH <= stageRect.height) {
      this.ty = 0;
    } else {
      const minTy = stageRect.height - contentH;
      const maxTy = 0;
      this.ty = clamp(this.ty, minTy, maxTy);
    }
  }
```

Uprav `_zoomAt` aby volala `_clamp()`:

```js
  _zoomAt(cx, cy, newScale) {
    const dx = (cx - this.tx) / this.scale;
    const dy = (cy - this.ty) / this.scale;
    this.scale = newScale;
    this.tx = cx - dx * this.scale;
    this.ty = cy - dy * this.scale;
    this._clamp();
    this._applyTransform();
  }
```

`fit()` volat `_clamp()` nemusí — výchozí 0,0 jsou platné.

**Důležité — `transform-origin`:** stávající CSS má `transform-origin: 0 0`. Clamping je počítán pro tento origin (obsah se škáluje od levého horního rohu). Pokud někdo přepne na `center`, math by se musel upravit — proto **nemodifikuj** CSS `transform-origin` v dalších taskech.

- [ ] **Step 2: Verifikace**

Refresh. Ověř:

1. Default state — `cursor: grab` na diagramu, ale drag nedělá nic (scale = 1).
2. Klikni 2× na `+` (scale 1.56). Drag fungoval — diagram se posouvá. Cursor během dragu `grabbing`.
3. Drag až na hranice — obraz se zastaví, nepokračuje za hranice (clamping).
4. Pusť myš mimo diagram během dragu — drag se ukončí (`pointerup` mimo target díky pointer capture stále funguje).
5. Mobile (DevTools touch emulation): tap and drag funguje stejně.
6. Klik na tlačítko v toolbaru během dragu — tlačítko reaguje (toolbar je mimo stage).

- [ ] **Step 3: Commit**

```bash
git add assets/scripts/diagram-viewer.js
git commit -m "feat(diagram): pan drag s pointer events a clampingem"
```

---

## Task 5: Fullscreen modal — open/close/lazy DOM

**Cíl:** Klik na ⛶ otevře modal s overlayem. Zavře ESC, klik na ×, klik mimo. Body scroll je zablokovaný.

**Files:**
- Modify: `assets/scripts/diagram-viewer.js`
- Modify: `assets/styles/article.css` (přidat modal CSS)

- [ ] **Step 1: Přidat modal CSS na konec `.diagram` sekce v article.css**

Pod existující `@media (prefers-reduced-motion: reduce)` blok pro `.diagram-stage` přidej:

```css
/* ── Diagram modal (fullscreen) ────────────────────────────────────────── */
.diagram-modal {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.85);
  display: flex;
  opacity: 0;
  transition: opacity 0.15s ease;
}
.diagram-modal--open {
  opacity: 1;
}
.diagram-modal-stage {
  flex: 1;
  position: relative;
  overflow: hidden;
  touch-action: none;
  cursor: grab;
}
.diagram-modal-stage.is-dragging {
  cursor: grabbing;
  user-select: none;
}
.diagram-modal-stage > svg,
.diagram-modal-stage > img {
  display: block;
  transform-origin: 0 0;
  will-change: transform;
}
.diagram-modal-toolbar {
  position: absolute;
  top: var(--s-3);
  right: calc(var(--s-3) + 3rem); /* místo pro × tlačítko */
  display: flex;
  gap: var(--s-1);
  z-index: 1;
}
.diagram-modal-close {
  position: absolute;
  top: var(--s-3);
  right: var(--s-3);
  z-index: 1;
  font-family: var(--font-mono);
  font-size: var(--t-md);
  line-height: 1;
  width: 2.5rem;
  height: 2.5rem;
  background: transparent;
  color: var(--fg);
  border: 1px solid var(--stroke);
  border-radius: var(--r-1);
  cursor: pointer;
}
.diagram-modal-close:hover,
.diagram-modal-close:focus-visible {
  color: var(--accent);
  border-color: var(--accent);
}
.diagram-modal-close:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 1px;
}

@media (prefers-reduced-motion: reduce) {
  .diagram-modal {
    transition: none;
  }
}
```

- [ ] **Step 2: Implementovat openFullscreen / closeFullscreen v diagram-viewer.js**

V `assets/scripts/diagram-viewer.js`:

a) V `_bindToolbar` přidej případ pro `'fullscreen'`:

```js
      else if (action === 'fullscreen') this.openFullscreen();
```

b) Přidej do třídy nové metody (nad uzávěrku `}` třídy):

```js
  openFullscreen() {
    if (this.modal) return; // už otevřeno

    const modal = document.createElement('div');
    modal.className = 'diagram-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Diagram — celá obrazovka');

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'diagram-modal-close';
    closeBtn.setAttribute('aria-label', 'Zavřít');
    closeBtn.textContent = '×';

    const toolbar = document.createElement('div');
    toolbar.className = 'diagram-modal-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Ovládání diagramu');
    toolbar.innerHTML = `
      <button type="button" class="mv-btn" data-action="zoom-in"  aria-label="Přiblížit">+</button>
      <button type="button" class="mv-btn" data-action="zoom-out" aria-label="Oddálit">−</button>
      <button type="button" class="mv-btn" data-action="fit"      aria-label="Přizpůsobit">⤢</button>
    `;

    const stage = document.createElement('div');
    stage.className = 'diagram-modal-stage';
    stage.appendChild(this.content.cloneNode(true));

    modal.appendChild(closeBtn);
    modal.appendChild(toolbar);
    modal.appendChild(stage);

    // Lock body scroll
    this._prevBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    document.body.appendChild(modal);
    // Force reflow než aplikujeme --open class (transition fade-in):
    modal.offsetHeight;
    modal.classList.add('diagram-modal--open');

    // Close handlers
    closeBtn.addEventListener('click', () => this.closeFullscreen());
    modal.addEventListener('click', (e) => {
      if (e.target === modal) this.closeFullscreen();
    });
    this._escHandler = (e) => {
      if (e.key === 'Escape') this.closeFullscreen();
    };
    document.addEventListener('keydown', this._escHandler);

    this.modal = modal;
    this.modalStage = stage;
    this.modalCloseBtn = closeBtn;
    this.modalToolbar = toolbar;
    // Modal viewer přijde v tasku 6 — zatím statický klon.
  }

  closeFullscreen() {
    if (!this.modal) return;
    document.removeEventListener('keydown', this._escHandler);
    document.body.style.overflow = this._prevBodyOverflow || '';
    this.modal.remove();
    this.modal = null;
    this.modalStage = null;
    this.modalCloseBtn = null;
    this.modalToolbar = null;
    this.btnFs.focus(); // focus restoration
  }
```

- [ ] **Step 3: Verifikace**

Refresh. Ověř:

1. Klik na ⛶ — otevře se tmavý overlay přes celou obrazovku, uvnitř kopie diagramu (statická).
2. Klik na × v rohu — modal se zavře, focus skočí zpět na ⛶ tlačítko.
3. Otevři znovu, stiskni `ESC` — zavře.
4. Otevři znovu, klikni na tmavé pozadí (mimo diagram) — zavře.
5. Klik **na samotný diagram uvnitř modalu** modal **nezavře** (`e.target === modal` je false).
6. Body je při otevřeném modalu zamčený (zkus scrollovat — nejde).
7. Diagram v modalu zatím není ovládatelný (tlačítka v toolbaru nedělají nic, žádný zoom). To je OK — task 6.

- [ ] **Step 4: Commit**

```bash
git add assets/scripts/diagram-viewer.js assets/styles/article.css
git commit -m "feat(diagram): fullscreen modal — open/close/ESC/click-outside"
```

---

## Task 6: Modal viewer — fit-on-open + zoom/pan

**Cíl:** Modal dostane vlastní `DiagramViewer`-like instanci — toolbar funguje, drag pan funguje, po otevření je diagram fit-na-viewport.

**Files:**
- Modify: `assets/scripts/diagram-viewer.js`

- [ ] **Step 1: Refaktor — vyextrahovat společný controller pro stage + toolbar**

Strategie: stávající `DiagramViewer` má logiku zoom/pan/clamp svázanou s `this.stage` / `this.content` / `this.toolbar`. Modal má svoji trojici. Vyextrahujeme jádro do třídy `StageController`, která je parametrizovaná elementy a min/max scale. `DiagramViewer` ji pak instancuje pro inline pohled, `openFullscreen` pro modal.

Přepiš `assets/scripts/diagram-viewer.js`:

```js
// ──────────────────────────────────────────────────────────────────────────
// Diagram viewer — zoom, pan, fullscreen pro inline SVG/img diagramy
// ──────────────────────────────────────────────────────────────────────────

const INLINE_MIN_SCALE = 0.5;
const INLINE_MAX_SCALE = 4;
const MODAL_MIN_SCALE  = 0.25;
const MODAL_MAX_SCALE  = 8;
const STEP             = 1.25;

const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

// Per-stage zoom/pan controller. Operuje nad libovolnou trojicí:
//   stage    — kontejner s overflow:hidden, drží pointer events
//   content  — vnitřní <svg>/<img>, na který se aplikuje transform
//   toolbar  — element s tlačítky [data-action="zoom-in|zoom-out|fit"]
class StageController {
  constructor(stage, content, toolbar, opts = {}) {
    this.stage = stage;
    this.content = content;
    this.toolbar = toolbar;
    this.minScale = opts.minScale ?? INLINE_MIN_SCALE;
    this.maxScale = opts.maxScale ?? INLINE_MAX_SCALE;

    this.scale = 1;
    this.tx = 0;
    this.ty = 0;

    this.btnZoomIn  = toolbar ? toolbar.querySelector('[data-action="zoom-in"]')  : null;
    this.btnZoomOut = toolbar ? toolbar.querySelector('[data-action="zoom-out"]') : null;
    this.btnFit     = toolbar ? toolbar.querySelector('[data-action="fit"]')      : null;

    if (toolbar) this._bindToolbar();
    this._bindPan();
    this._updateButtonStates();
  }

  _bindToolbar() {
    this.toolbar.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action]');
      if (!btn || btn.disabled) return;
      const action = btn.dataset.action;
      if (action === 'zoom-in')       this.zoomBy(STEP);
      else if (action === 'zoom-out') this.zoomBy(1 / STEP);
      else if (action === 'fit')      this.fit();
      // 'fullscreen' řeší DiagramViewer extra
    });
  }

  _bindPan() {
    let startX = 0, startY = 0, startTx = 0, startTy = 0;
    let activePointerId = null;

    this.stage.addEventListener('pointerdown', (e) => {
      // Pan jen když je obsah větší než viewport (jinak není kam panovat)
      if (!this._isPannable()) return;
      activePointerId = e.pointerId;
      this.stage.setPointerCapture(e.pointerId);
      this.stage.classList.add('is-dragging');
      startX = e.clientX;
      startY = e.clientY;
      startTx = this.tx;
      startTy = this.ty;
    });

    this.stage.addEventListener('pointermove', (e) => {
      if (e.pointerId !== activePointerId) return;
      this.tx = startTx + (e.clientX - startX);
      this.ty = startTy + (e.clientY - startY);
      this._clamp();
      this._applyTransform();
    });

    const onEnd = (e) => {
      if (e.pointerId !== activePointerId) return;
      this.stage.releasePointerCapture(e.pointerId);
      activePointerId = null;
      this.stage.classList.remove('is-dragging');
    };
    this.stage.addEventListener('pointerup', onEnd);
    this.stage.addEventListener('pointercancel', onEnd);
  }

  _isPannable() {
    const stageRect = this.stage.getBoundingClientRect();
    const { baseW, baseH } = this._baseDims(stageRect);
    return baseW * this.scale > stageRect.width
        || baseH * this.scale > stageRect.height;
  }

  _baseDims(stageRect) {
    let baseW, baseH;
    if (this.content.tagName.toLowerCase() === 'img') {
      baseW = this.content.naturalWidth  || stageRect.width;
      baseH = this.content.naturalHeight || stageRect.height;
    } else {
      const vb = this.content.viewBox && this.content.viewBox.baseVal;
      baseW = (vb && vb.width)  || stageRect.width;
      baseH = (vb && vb.height) || stageRect.height;
    }
    return { baseW, baseH };
  }

  _applyTransform() {
    this.content.style.transform =
      `translate(${this.tx}px, ${this.ty}px) scale(${this.scale})`;
    this._updateButtonStates();
  }

  _updateButtonStates() {
    if (this.btnZoomIn)  this.btnZoomIn.disabled  = this.scale >= this.maxScale - 1e-6;
    if (this.btnZoomOut) this.btnZoomOut.disabled = this.scale <= this.minScale + 1e-6;
    if (this.btnFit)     this.btnFit.disabled =
      Math.abs(this.scale - 1) < 1e-6 && this.tx === 0 && this.ty === 0;
  }

  _clamp() {
    const stageRect = this.stage.getBoundingClientRect();
    const { baseW, baseH } = this._baseDims(stageRect);
    const contentW = baseW * this.scale;
    const contentH = baseH * this.scale;

    if (contentW <= stageRect.width) {
      this.tx = 0;
    } else {
      this.tx = clamp(this.tx, stageRect.width - contentW, 0);
    }
    if (contentH <= stageRect.height) {
      this.ty = 0;
    } else {
      this.ty = clamp(this.ty, stageRect.height - contentH, 0);
    }
  }

  zoomBy(factor) {
    const newScale = clamp(this.scale * factor, this.minScale, this.maxScale);
    if (newScale === this.scale) return;
    const rect = this.stage.getBoundingClientRect();
    this._zoomAt(rect.width / 2, rect.height / 2, newScale);
  }

  _zoomAt(cx, cy, newScale) {
    const dx = (cx - this.tx) / this.scale;
    const dy = (cy - this.ty) / this.scale;
    this.scale = newScale;
    this.tx = cx - dx * this.scale;
    this.ty = cy - dy * this.scale;
    this._clamp();
    this._applyTransform();
  }

  fit() {
    this.scale = 1;
    this.tx = 0;
    this.ty = 0;
    this._applyTransform();
  }

  // Fit-na-viewport — vypočte scale tak, aby se obsah vešel do stage celý.
  fitToViewport() {
    const stageRect = this.stage.getBoundingClientRect();
    const { baseW, baseH } = this._baseDims(stageRect);
    if (!baseW || !baseH) { this.fit(); return; }
    const fitScale = Math.min(stageRect.width / baseW, stageRect.height / baseH);
    this.scale = clamp(fitScale, this.minScale, this.maxScale);
    // Vycentrovat obsah ve stage:
    this.tx = (stageRect.width - baseW * this.scale) / 2;
    this.ty = (stageRect.height - baseH * this.scale) / 2;
    this._applyTransform();
  }
}

class DiagramViewer {
  constructor(figureEl) {
    this.figure = figureEl;
    const stage = figureEl.querySelector('.diagram-stage');
    const content = stage && stage.firstElementChild;
    const toolbar = figureEl.querySelector('.diagram-toolbar');
    if (!stage || !content || !toolbar) return;

    this.figure.classList.add('diagram-js');

    this.controller = new StageController(stage, content, toolbar, {
      minScale: INLINE_MIN_SCALE,
      maxScale: INLINE_MAX_SCALE,
    });
    this.btnFs = toolbar.querySelector('[data-action="fullscreen"]');
    if (this.btnFs) this.btnFs.addEventListener('click', () => this.openFullscreen());

    this.contentTpl = content; // pro klonování při fullscreen
  }

  openFullscreen() {
    if (this.modal) return;

    const modal = document.createElement('div');
    modal.className = 'diagram-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Diagram — celá obrazovka');

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'diagram-modal-close';
    closeBtn.setAttribute('aria-label', 'Zavřít');
    closeBtn.textContent = '×';

    const toolbar = document.createElement('div');
    toolbar.className = 'diagram-modal-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Ovládání diagramu');
    toolbar.innerHTML = `
      <button type="button" class="mv-btn" data-action="zoom-in"  aria-label="Přiblížit">+</button>
      <button type="button" class="mv-btn" data-action="zoom-out" aria-label="Oddálit">−</button>
      <button type="button" class="mv-btn" data-action="fit"      aria-label="Přizpůsobit">⤢</button>
    `;

    const stage = document.createElement('div');
    stage.className = 'diagram-modal-stage';
    const clone = this.contentTpl.cloneNode(true);
    // Vyresetuj inline transform z inline pohledu (klonujeme i ten):
    clone.style.transform = '';
    stage.appendChild(clone);

    modal.appendChild(closeBtn);
    modal.appendChild(toolbar);
    modal.appendChild(stage);

    this._prevBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    document.body.appendChild(modal);
    modal.offsetHeight;
    modal.classList.add('diagram-modal--open');

    // Modal-specific controller — vlastní stav, vlastní min/max
    this.modalCtrl = new StageController(stage, clone, toolbar, {
      minScale: MODAL_MIN_SCALE,
      maxScale: MODAL_MAX_SCALE,
    });
    // Po umístění do DOM má stage nenulové rozměry — fit:
    this.modalCtrl.fitToViewport();

    // Close handlers
    closeBtn.addEventListener('click', () => this.closeFullscreen());
    modal.addEventListener('click', (e) => {
      if (e.target === modal) this.closeFullscreen();
    });
    this._escHandler = (e) => {
      if (e.key === 'Escape') this.closeFullscreen();
    };
    document.addEventListener('keydown', this._escHandler);

    this.modal = modal;
    this.modalStage = stage;
    this.modalCloseBtn = closeBtn;
    this.modalToolbar = toolbar;
  }

  closeFullscreen() {
    if (!this.modal) return;
    document.removeEventListener('keydown', this._escHandler);
    document.body.style.overflow = this._prevBodyOverflow || '';
    this.modal.remove();
    this.modal = null;
    this.modalStage = null;
    this.modalCloseBtn = null;
    this.modalToolbar = null;
    this.modalCtrl = null;
    if (this.btnFs) this.btnFs.focus();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-diagram]').forEach((el) => new DiagramViewer(el));
});
```

**Pozn.:** `fit()` v `StageController` zůstává reset na 1×; `fitToViewport()` je nová metoda, kterou volá modal po otevření. Inline pohled má tlačítko `⤢` namapované na reset `fit()`, modal taky — protože ve fullscreenu reset = "vejít se do viewportu" by mohlo být matoucí, necháváme reset = scale 1 i tam (uživatel pak může zoom-out na fit pomocí `−`). Pokud chceš v modalu fit-to-viewport přímo přes `⤢`, byla by to malá změna v `_bindToolbar` — ale držme spec (oddělené konstanty, společná logika).

- [ ] **Step 2: Verifikace**

Refresh. Ověř:

1. Inline diagram: zoom/pan/fit funguje stejně jako v tasku 4.
2. Klik na ⛶ — modal se otevře, klon diagramu **vyplní téměř celý viewport** (fit-to-viewport).
3. V modalu klik na `+` — diagram se zvětší kolem středu modalu. Limit 8×.
4. Klik na `−` — zmenší. Limit 0.25×.
5. Drag v modalu — diagram se posune (kdykoli je `_isPannable()` true, tj. obsah > viewport).
6. Klik na `⤢` v modalu — reset na scale 1 (diagram bude menší než viewport pro většinu diagramů).
7. Zavři modal (`ESC` / × / overlay). Otevři znovu — fit-na-viewport funguje znovu.
8. Žádné chyby v console při opakovaném open/close.

- [ ] **Step 3: Commit**

```bash
git add assets/scripts/diagram-viewer.js
git commit -m "feat(diagram): modal viewer s fit-on-open a zoom/pan"
```

---

## Task 7: Modal — wheel zoom kolem kurzoru

**Cíl:** V modalu kolečko myši zoomuje. Zoom je kolem aktuální pozice kurzoru, ne středu.

**Files:**
- Modify: `assets/scripts/diagram-viewer.js`

- [ ] **Step 1: Přidat wheel handler v openFullscreen**

V `openFullscreen()` v `assets/scripts/diagram-viewer.js`, **po vytvoření `this.modalCtrl` a před close handlery**, přidej:

```js
    // Wheel zoom kolem pozice kurzoru (jen v modalu, ne v inline)
    stage.addEventListener('wheel', (e) => {
      e.preventDefault();
      const factor = e.deltaY < 0 ? STEP : 1 / STEP;
      const newScale = clamp(
        this.modalCtrl.scale * factor,
        this.modalCtrl.minScale,
        this.modalCtrl.maxScale
      );
      if (newScale === this.modalCtrl.scale) return;
      const stageRect = stage.getBoundingClientRect();
      const cx = e.clientX - stageRect.left;
      const cy = e.clientY - stageRect.top;
      this.modalCtrl._zoomAt(cx, cy, newScale);
    }, { passive: false });
```

**Pozn.:** voláme `_zoomAt` přímo (s underscoringem) — beru to jako interní API třídy, ale z fullscreenu to potřebujeme. Alternativa by byla udělat z `zoomAt(cx, cy, factor)` veřejné API; zatím to není potřeba mimo modal, takže nechávám private.

- [ ] **Step 2: Verifikace**

Refresh. Otevři modal. Ověř:

1. Posun kolečka **nahoru** (deltaY < 0) — zoom in, **kolem pozice kurzoru** (bod pod kurzorem se nehne).
2. Posun kolečka **dolů** — zoom out kolem kurzoru.
3. Body pod modalem **nescrolluje** (`preventDefault`).
4. Inline pohled (mimo modal) — kolečko **scrolluje stránku normálně** (žádný wheel listener tam není).
5. Po wheel-zoomu lze panovat dragem.

- [ ] **Step 3: Commit**

```bash
git add assets/scripts/diagram-viewer.js
git commit -m "feat(diagram): wheel zoom v modalu kolem kurzoru"
```

---

## Task 8: A11y — focus trap v modalu

**Cíl:** `Tab` v modalu cykluje jen mezi tlačítky uvnitř modalu (`× → + → − → ⤢ → ×`). Focus se po otevření přesune na ×, po zavření zpět na ⛶.

**Files:**
- Modify: `assets/scripts/diagram-viewer.js`

- [ ] **Step 1: Přidat focus management do openFullscreen**

V `openFullscreen()`, **po `document.body.appendChild(modal)`** (a po nastavení `this.modalCtrl`), přidej:

```js
    // Focus management — uložit původní fokus, dát na zavírací X
    this._prevActive = document.activeElement;
    closeBtn.focus();

    // Focus trap — Tab cyklí mezi focusable v modalu
    const focusables = () => Array.from(
      modal.querySelectorAll('button:not([disabled])')
    );
    this._trapHandler = (e) => {
      if (e.key !== 'Tab') return;
      const items = focusables();
      if (items.length === 0) return;
      const first = items[0];
      const last  = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault(); last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault(); first.focus();
      }
    };
    modal.addEventListener('keydown', this._trapHandler);
```

A v `closeFullscreen()` před `this.modal.remove()`:

```js
    this.modal.removeEventListener('keydown', this._trapHandler);
```

A místo `if (this.btnFs) this.btnFs.focus();` použij prioritně uložený předchozí fokus, fallback na btnFs:

```js
    if (this._prevActive && this._prevActive.focus) {
      this._prevActive.focus();
    } else if (this.btnFs) {
      this.btnFs.focus();
    }
    this._prevActive = null;
```

- [ ] **Step 2: Verifikace s klávesnicí**

Refresh. Ověř:

1. Tab-uj na ⛶ tlačítko inline, stiskni Enter — modal se otevře, fokus je na ×.
2. Tab — fokus jde na `+`. Tab → `−`. Tab → `⤢`. Tab → zase × (cyklus).
3. Shift+Tab — opačný směr (× → ⤢ → − → + → ×).
4. ESC — modal zavře, fokus zpět na ⛶.
5. Tab po zavření — pokračuje normálně z ⛶ na další element stránky.
6. **Disabled** tlačítka focus přeskočí (`button:not([disabled])` selektor).

- [ ] **Step 3: Commit**

```bash
git add assets/scripts/diagram-viewer.js
git commit -m "feat(diagram): focus trap v modalu, restore focus po zavření"
```

---

## Task 9: Cleanup — odstranit `.diagram` z code-block.js tabindex

**Cíl:** `code-block.js:9-15` přidává `tabindex="0"` na `.diagram` jako workaround pro scrollable region (axe-core pravidlo). Po odstranění mobilního overflow scrollu už diagram nemá `overflow-x: auto`, takže tabindex je nesprávný (chytá fokus zbytečně).

**Files:**
- Modify: `assets/scripts/code-block.js`

- [ ] **Step 1: Upravit selektor — odstranit `.diagram`**

V `assets/scripts/code-block.js` najdi blok (řádek 9-15):

```js
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.table-responsive, .diagram').forEach(function (el) {
    if (!el.hasAttribute('tabindex')) {
      el.setAttribute('tabindex', '0');
    }
  });
});
```

Změň selektor a aktualizuj komentář:

```js
// Scrollable region a11y: tabindex="0" na .table-responsive (overflow-x: auto),
// aby keyboard users mohli scrollovat. Code-body má tabindex="0" už v partialu
// kapitoly. Diagramy mají vlastní zoom/pan controls (.diagram-toolbar).
// axe-core: "scrollable-region-focusable".
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.table-responsive').forEach(function (el) {
    if (!el.hasAttribute('tabindex')) {
      el.setAttribute('tabindex', '0');
    }
  });
});
```

- [ ] **Step 2: Verifikace**

Refresh. Ověř:

1. Tab-uj stránkou s diagramem — fokus **nepřistává** přímo na `<figure class="diagram">` (jen na tlačítka uvnitř toolbaru).
2. Tabuláry (`<table>` v `.table-responsive`) jsou stále focusable (tabindex tam zůstal).
3. DevTools → Elements → najdi `<figure class="diagram">` — nemá `tabindex` atribut.

- [ ] **Step 3: Commit**

```bash
git add assets/scripts/code-block.js
git commit -m "refactor(diagram): odstranit tabindex hack — viewer má vlastní controls"
```

---

## Task 10: End-to-end smoke test napříč diagramy

**Cíl:** Otestovat několik různých diagramů v projektu, ne jen jeden, kvůli edge cases (různé viewBoxy, malé vs. velké diagramy, sequence diagram).

**Files:** žádné — pouze manuální verifikace.

- [ ] **Step 1: Najít všechny stránky s diagramy**

```bash
grep -l "include '_partials/diagram\|include '/diagrams" templates/ddd/
```

Otevři postupně v prohlížeči (lokální server běží):
- `/co-je-ddd` (FIG. 01.5-A)
- `/zakladni-koncepty` (vícero diagramů — různé viewBoxy)
- `/case-study` (sekvenční / komplexní diagram)
- `/saga-process-managers` (sagy)
- `/ddd-a-ai`

- [ ] **Step 2: Smoke check checklist (na každé stránce):**

Pro každý diagram na stránce ověř:

- [ ] Toolbar viditelný v `.diagram-head`, vpravo zarovnaný.
- [ ] `+` zoomuje 4 levely → disabled.
- [ ] `−` zoomuje −1 level → disabled (z 1× na 0.5×).
- [ ] `⤢` reset funguje, zdisabluje sebe.
- [ ] Drag pan funguje při scale > 1, clamping drží obsah uvnitř.
- [ ] ⛶ otevře fullscreen, fit-to-viewport vyplní obrazovku, klon má vlastní stav.
- [ ] V modalu `+`, `−`, `⤢`, drag, wheel zoom — vše funguje.
- [ ] Modal zavírá ESC, ×, klik mimo. Focus restoration funguje.
- [ ] Mobile (DevTools 375px viewport): toolbar má dost místa, drag funguje, žádný horizontal scroll, ⛶ rozumný.

- [ ] **Step 3: Console check**

DevTools → Console → žádné errory ani warnings spojené s diagram-viewer.

- [ ] **Step 4: Edge case — diagram bez `viewBox`**

Pokud nějaký inline SVG nemá `viewBox` (PlantUML by je měl mít vždy, ale ujistit se), `_baseDims` fallbackne na `stageRect` rozměry. Pro takový diagram by clamping degradoval na bez efektu — ne ideální, ale ne crashe.

```bash
grep -L "viewBox" templates/diagrams/**/*.svg | head
```

Pokud najdeš SVG bez viewBox, otestuj ručně v prohlížeči — nemělo by být crashe; pan se prostě nemusí omezit. (Není to v scope spec — fix by byl regenerace SVG, ne kód vieweru.)

- [ ] **Step 5: Závěrečný commit (pokud něco menšího v cestě opraveno)**

Pokud při testování narazíš na drobnost (např. překlep v aria-labelu, špatná barva), oprav inline a commitni:

```bash
git commit -m "fix(diagram): drobnosti z e2e testu"
```

Pokud nic — žádný commit, plán hotový.

---

## Acceptance criteria (ze specu)

Po dokončení všech tasků ověřit:

1. ✅ Toolbar `+ − ⤢ ⛶` viditelný v `.diagram-head`, vpravo zarovnaný.
2. ✅ `+`/`−` zoomují kolem středu, dodržují limity (inline 0.5×–4×, modal 0.25×–8×).
3. ✅ `⤢` resetuje (scale 1, tx 0, ty 0).
4. ✅ Drag pan funguje desktop + mobil, jen když obsah > viewport.
5. ✅ ⛶ otevře modal s klonem, fit-to-viewport, zoom/pan, wheel-zoom kolem kurzoru.
6. ✅ Modal: ESC, ×, klik na overlay zavírají; focus restoration na ⛶.
7. ✅ Mobile: žádný horizontal scroll, diagram se vejde na šířku.
8. ✅ `prefers-reduced-motion: reduce` → žádné transitions.
9. ✅ Bez JS: žádné errory, toolbar skrytý, statický diagram.
10. ✅ Žádný caller `{% include '_partials/diagram.html.twig' %}` se nemusí měnit.

Tasky 1–8 implementují bod 1–6, 8, 9. Task 9 zajišťuje, že tabindex hack nepřežil. Task 10 ověří 10 (žádný caller change) a 7 (mobile).
