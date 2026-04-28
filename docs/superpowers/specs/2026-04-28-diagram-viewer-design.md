# Diagram viewer — interaktivní zobrazení SVG diagramů

**Datum:** 2026-04-28
**Status:** Schváleno (po sekcích 1–5)

## Cíl

Inline SVG diagramy v `templates/_partials/diagram.html.twig` jsou dnes statické a na mobilu se řeší horizontálním scrollem (`min-width: 720px`). Cílem je přidat lehký zoom/pan/fullscreen viewer, inspirovaný řešením na blog.katuscak.cz, ale **bez rotace a SVG/PNG exportu** — jednodušší toolbar `+ − ⤢ ⛶`. UX musí být jednotná na desktopu i mobilu.

## Rozsah

- Aplikuje se na **všechny** diagramy v `_partials/diagram.html.twig` automaticky (varianta `inline` SVG i `src` `<img>`).
- Žádný opt-in flag, žádná breaking změna v API partialu pro callery.
- Nahrazuje stávající mobilní horizontal-scroll fallback.

## Architektura

Vanilla JS modul, žádná knihovna. Tři dotčená místa:

| Soubor | Změna |
|---|---|
| `templates/_partials/diagram.html.twig` | Přidat toolbar do `.diagram-head`, obalit obsah do `<div class="diagram-stage">` |
| `assets/styles/article.css` | Sekce `.diagram` rozšířit o toolbar, stage, modal; **smazat** `@media (max-width: 540px)` blok |
| `assets/scripts/diagram-viewer.js` | Nový modul, importovaný z `assets/app.js` |

**Datový tok:** každá `.diagram` instance má vlastní controller s lokálním stavem `{ scale, translateX, translateY }`. Stav se aplikuje jediným `transform: translate(tx,ty) scale(s)` na vnitřní `<svg>`/`<img>`. Tlačítka volají metody controlleru.

**Fullscreen modal je oddělený DOM uzel**, vytvářený **lazy** při prvním kliku na ⛶, klonuje obsah `.diagram-stage` a má vlastní (nezávislý) zoom/pan stav. Po zavření se z DOM odstraňuje.

## DOM struktura po úpravě

```html
<figure class="diagram" data-diagram>
  <header class="diagram-head">
    <span class="diagram-num">FIG. 01.5-A</span>
    <span class="diagram-title">Agregát v e-commerce doméně</span>
    <div class="diagram-toolbar" role="toolbar" aria-label="Ovládání diagramu">
      <button type="button" class="mv-btn" data-action="zoom-in"    aria-label="Přiblížit">+</button>
      <button type="button" class="mv-btn" data-action="zoom-out"   aria-label="Oddálit">−</button>
      <button type="button" class="mv-btn" data-action="fit"        aria-label="Přizpůsobit">⤢</button>
      <button type="button" class="mv-btn" data-action="fullscreen" aria-label="Celá obrazovka">⛶</button>
    </div>
  </header>
  <div class="diagram-stage" role="img" aria-label="…">
    <svg …>…</svg>
  </div>
  <figcaption class="diagram-caption">…</figcaption>
</figure>
```

`role="img"` jde z dnešního obalu přímo na `.diagram-stage` (není nutné mít dva wrappery). `<figure>` má atribut `data-diagram` jako stabilní hook pro JS.

## CSS

**Změny v `article.css`:**

- `.diagram-head` přejde na `display: flex; align-items: center; gap: var(--s-3)`. Toolbar dostane `margin-left: auto` (vpravo).
- `.diagram-toolbar { display: flex; gap: var(--s-1); }`.
- `.mv-btn` — sjednoceno s `--font-mono`, `--t-xs`. Hover/focus state přes `--accent`. `:disabled` má sníženou opacitu a `cursor: not-allowed`.
- `.diagram-stage { overflow: hidden; touch-action: none; cursor: grab; }`. Class `.is-dragging` přepne `cursor: grabbing` a `user-select: none`. Class `.is-zoomed` se přidává když `scale > 1 || tx !== 0 || ty !== 0` (vzhled / event hooks).
- Vnitřní `<svg>`/`<img>` dostane `transform-origin: 0 0; will-change: transform; transition: transform .15s ease`. Během dragu se transition vypne (přes class na stage).
- **Smazat** stávající `@media (max-width: 540px) { .diagram { overflow-x: auto … } .diagram svg { min-width: 720px … } }` blok.
- `@media (prefers-reduced-motion: reduce)` → `transition: none` pro stage transform i opacity modalu.
- `.diagram:not(.diagram-js) .diagram-toolbar { display: none; }` — toolbar skryt v no-JS režimu.

**Modal CSS** (přidává se do article.css):

- `.diagram-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 1000; display: flex; }` (overlay).
- `.diagram-modal-stage` — flex-grow, overflow hidden, vlastní cursor: grab/grabbing, totéž `touch-action: none`.
- `.diagram-modal-toolbar` — fixní v rohu (top-right), styl shodný s inline toolbarem.
- `.diagram-modal-close` — top-right větší zavírací X.
- `.diagram-modal--open` — opacity transition při otevření / zavření.

## JS modul (`assets/scripts/diagram-viewer.js`)

**Konstanty (top-of-module, ne magic numbery v handlerech):**

```js
const INLINE_MIN_SCALE  = 0.5;
const INLINE_MAX_SCALE  = 4;
const MODAL_MIN_SCALE   = 0.25;   // víc ploše modalu, povolíme menší
const MODAL_MAX_SCALE   = 8;      // víc ploše modalu, povolíme větší
const STEP              = 1.25;
```

Inline a modal sdílejí logiku, ale mají oddělené min/max konstanty — modal má víc viewportu, takže snese širší rozsah.

**Init:**

```js
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-diagram]').forEach((el) => new DiagramViewer(el));
});
```

**Třída `DiagramViewer` (per `.diagram` instance):**

- Konstruktor najde `.diagram-stage` a vnitřní `<svg>`/`<img>` (`stage.firstElementChild`).
- Přidá class `.diagram-js` na `<figure>` (povolí toolbar přes CSS).
- Připojí listenery: tlačítka toolbaru (delegated na `.diagram-toolbar`), pointer events na stage.
- Drží stav `{ scale: 1, tx: 0, ty: 0 }`.

**Akce:**

| Akce | Implementace |
|---|---|
| `zoom-in` | `scale = min(scale * STEP, maxScale)` + přepočítání tx/ty pro **střed viewportu jako střed zoomu**. (`maxScale` je `INLINE_MAX_SCALE` v inline / `MODAL_MAX_SCALE` v modalu) |
| `zoom-out` | `scale = max(scale / STEP, minScale)` + střed viewportu. |
| `fit` | Reset: `scale = 1; tx = 0; ty = 0`. |
| `fullscreen` | Otevři modal (popsáno níže). |

**Zoom kolem středu viewportu** (matematický klíč — bez tohoto zoom-in posune obraz):

```
// Před změnou scale:
const stageRect = stage.getBoundingClientRect();
const cx = stageRect.width / 2;
const cy = stageRect.height / 2;
// Bod v souřadnicích diagramu, který je teď uprostřed:
const dx = (cx - tx) / scale;
const dy = (cy - ty) / scale;
// Po změně scale:
scale = newScale;
tx = cx - dx * scale;
ty = cy - dy * scale;
applyClamp();
applyTransform();
```

**Clamp (omezení panu na hranice obrazu):**

```
const svgRect = svgEl.getBoundingClientRect();  // už po současném transformu
// Spočítáme reálné rozměry obsahu při aktuálním scale:
const contentW = svgEl.naturalWidth ? svgEl.naturalWidth * scale
                                    : svgEl.viewBox.baseVal.width * scale;
const contentH = …;  // analogicky
const maxTx = Math.max(0, (contentW - stageRect.width) / 2);
const maxTy = Math.max(0, (contentH - stageRect.height) / 2);
tx = clamp(tx, -maxTx, +maxTx);
ty = clamp(ty, -maxTy, +maxTy);
```

Pokud `contentW <= stageW`, tx se clampne na 0 (obraz je menší než kontejner — žádný pan není potřeba).

**Pan (drag) přes Pointer Events:**

```
stage.addEventListener('pointerdown', (e) => {
  if (scale <= 1) return;          // nepanovat, když není kam
  stage.setPointerCapture(e.pointerId);
  stage.classList.add('is-dragging');
  startX = e.clientX;
  startY = e.clientY;
  startTx = tx;
  startTy = ty;
});
stage.addEventListener('pointermove', (e) => {
  if (!stage.hasPointerCapture(e.pointerId)) return;
  tx = startTx + (e.clientX - startX);
  ty = startTy + (e.clientY - startY);
  applyClamp();
  applyTransform();
});
stage.addEventListener('pointerup',     onEnd);
stage.addEventListener('pointercancel', onEnd);
function onEnd() {
  stage.classList.remove('is-dragging');
}
```

**Disabled stav tlačítek** (volá se po každém `applyTransform()`):

- `zoom-in` disabled když `scale === maxScale`.
- `zoom-out` disabled když `scale === minScale`.
- `fit` disabled když `scale === 1 && tx === 0 && ty === 0`.

## Fullscreen modal

**DOM struktura (vytvořená lazy v `openFullscreen()`):**

```html
<div class="diagram-modal" role="dialog" aria-modal="true" aria-label="Diagram — celá obrazovka">
  <button class="diagram-modal-close" aria-label="Zavřít">×</button>
  <div class="diagram-modal-toolbar" role="toolbar" aria-label="Ovládání diagramu">
    <button data-action="zoom-in"  aria-label="Přiblížit">+</button>
    <button data-action="zoom-out" aria-label="Oddálit">−</button>
    <button data-action="fit"      aria-label="Přizpůsobit">⤢</button>
  </div>
  <div class="diagram-modal-stage">
    <!-- klon vnitřního SVG/img -->
  </div>
</div>
```

**Behavior:**

- Klik na ⛶ → `document.body.appendChild(modal)`. Klon SVG/img přes `cloneNode(true)` do modal-stage. Vlastní instance "modal vieweru" se stejným zoom/pan API.
- **Po otevření se aplikuje fit-na-viewport** (ne identity scale 1): spočítá `min(stageW / contentW, stageH / contentH)` a nastaví `scale` tak, aby byl celý diagram viditelný a co největší. Vypočtený scale se clampne na `[MODAL_MIN_SCALE, MODAL_MAX_SCALE]` (širší rozsah než inline).
- Modal se otevírá s `.diagram-modal--open` class (opacity transition 150 ms, vypnuté při `prefers-reduced-motion`).
- Zavření: ESC, klik na `×`, klik na overlay pozadí (mimo `.diagram-modal-stage` a toolbar). Modal se odstraní (`modal.remove()`).
- `document.body.style.overflow = 'hidden'` při open, vrátit zpátky při close (uložit původní hodnotu).

**Wheel-zoom v modalu (jen tam, ne v inline):**

```
modalStage.addEventListener('wheel', (e) => {
  e.preventDefault();
  const factor = e.deltaY < 0 ? STEP : 1 / STEP;
  zoomAt(e.clientX, e.clientY, factor);   // zoom kolem pozice kurzoru
}, { passive: false });
```

`zoomAt(x, y, factor)` je verze zoom akce, kde střed zoomu není střed viewportu, ale konkrétní bod (pozice kurzoru). Stejná matematika, jen `cx, cy` je `event.clientX - stageRect.left`.

**A11y:**

- Při otevření focus jde na `×` tlačítko. Trap focus uvnitř modalu — `Tab` cyklí mezi `× → +`, `−`, `⤢ → ×`. Implementace: keydown listener na modal, kontrola `Tab` na první/poslední focusable element.
- Při zavření focus zpět na původní ⛶ tlačítko.
- ESC handler na document-level, attached jen po dobu otevřeného modalu, detached při zavření.
- Všechna tlačítka mají `aria-label` v češtině.

## Inicializace v `assets/app.js`

Přidat řádek:

```js
import './scripts/diagram-viewer.js';
```

Modul si sám obstará `DOMContentLoaded` listener (stejný pattern jako `topnav.js`/`code-block.js`).

## Úprava `templates/_partials/diagram.html.twig`

Logika `_safe_svg` (replace `preserveAspectRatio` a `style`) zůstává — slouží proti deformaci, kterou pořád potřebujeme. Změny:

1. Přidat `data-diagram` atribut na `<figure>`.
2. Do `.diagram-head` přidat `.diagram-toolbar` (4 tlačítka).
3. Inline SVG / `<img>` obalit do `<div class="diagram-stage" role="img" aria-label="...">`.
4. Smazat existující `<div role="img">` wrap kolem `_safe_svg` (přesouvá se na `.diagram-stage`).

API partialu (parametry `fig`, `title`, `src`, `inline`, `caption`, `alt`) zůstává nezměněné. Žádný caller se nemusí přepisovat.

## A11y a no-JS chování

- **Bez JS** (modul selže nebo je vypnutý): toolbar je díky `.diagram:not(.diagram-js) .diagram-toolbar { display: none; }` skrytý. SVG/img se zobrazí staticky. Žádný horizontal scroll na mobilu — diagram se vejde dle CSS `width: 100%`. To je kompromis (na velmi malém viewportu může být detail nečitelný), ale s JS to vyřeší pinch-via-zoom-tlačítka.
- **Reduce motion**: `prefers-reduced-motion: reduce` vypne všechny transitions (transform, opacity).
- **Keyboard**: tlačítka jsou nativní `<button>`, fokus management defaultně funguje. Modal má focus trap.
- **Screen reader**: `role="img"` na stage zachová původní popis (`aria-label`). Toolbar má `role="toolbar"` + `aria-label`. Tlačítka `aria-label` v češtině.

## Out of scope

- Pinch-zoom (vícedotykové). Uživatel má `+`/`−` tlačítka.
- Wheel-zoom v inline pohledu (konflikt s page scroll). Jen ve fullscreenu.
- SVG/PNG export.
- Reset (oddělené od fit) — fit toto supluje.
- Animace zoomu typu spring/ease přes víc kroků. Jednoduchý 150 ms ease.
- Backwards-compat shim pro stávající `_safe_svg` logiku — necháváme ji beze změny.

## Akceptační kritéria

1. Toolbar `+ − ⤢ ⛶` viditelný v `.diagram-head` všech diagramů, vpravo zarovnaný.
2. `+`/`−` zoomují kolem středu, dodržují limity (inline 0.5×–4×, modal 0.25×–8×).
3. `⤢` resetuje pohled (scale 1, tx 0, ty 0).
4. Drag panem funguje na desktopu i mobilu, jen když `scale > 1`.
5. `⛶` otevře modal, klon diagramu je fit-na-viewport, zoom/pan funguje, wheel-zoom kolem kurzoru funguje.
6. Modal zavírá ESC, klik na × i overlay; po zavření focus zpět na ⛶.
7. Stávající mobilní horizontal-scroll (`@media (max-width: 540px)` blok s `min-width: 720px`) je odstraněn. `.diagram-stage` zabírá plnou šířku `<figure>`, vnitřní SVG/img má `width: 100%; height: auto` (zachováno z dnešního CSS).
8. `prefers-reduced-motion: reduce` → žádné transitions.
9. Bez JS: žádné chyby, toolbar skrytý, diagram se zobrazí staticky.
10. Žádný caller `{% include '_partials/diagram.html.twig' …}` se nemusí měnit.
