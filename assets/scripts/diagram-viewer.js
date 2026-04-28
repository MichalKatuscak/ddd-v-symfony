// ──────────────────────────────────────────────────────────────────────────
// Diagram viewer — zoom, pan, fullscreen pro inline SVG/img diagramy
// ──────────────────────────────────────────────────────────────────────────

const INLINE_MIN_SCALE = 0.5;
const INLINE_MAX_SCALE = 4;
const MODAL_MIN_SCALE  = 0.05; // floor: even 3156-wide diagram on 320px phone fits at ~0.10
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
    const pointers = new Map(); // pointerId -> {x, y}
    let dragPointerId = null;
    let dragStartX = 0, dragStartY = 0, dragStartTx = 0, dragStartTy = 0;
    let pinchStartDist = 0;
    let pinchStartScale = 1;

    const distance = (p1, p2) => Math.hypot(p1.x - p2.x, p1.y - p2.y);

    this.stage.addEventListener('pointerdown', (e) => {
      pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

      if (pointers.size === 2) {
        // Start pinch — cancel any in-progress drag
        if (dragPointerId !== null) {
          this.stage.releasePointerCapture(dragPointerId);
          dragPointerId = null;
          this.stage.classList.remove('is-dragging');
        }
        const [p1, p2] = Array.from(pointers.values());
        pinchStartDist = distance(p1, p2);
        pinchStartScale = this.scale;
        return;
      }

      if (pointers.size === 1 && this._isPannable()) {
        dragPointerId = e.pointerId;
        this.stage.setPointerCapture(e.pointerId);
        this.stage.classList.add('is-dragging');
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        dragStartTx = this.tx;
        dragStartTy = this.ty;
      }
    });

    this.stage.addEventListener('pointermove', (e) => {
      if (pointers.has(e.pointerId)) {
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
      }

      // Pinch (two fingers)
      if (pointers.size >= 2) {
        const [p1, p2] = Array.from(pointers.values()).slice(0, 2);
        const newDist = distance(p1, p2);
        if (pinchStartDist === 0) return;
        const factor = newDist / pinchStartDist;
        const newScale = clamp(pinchStartScale * factor, this.minScale, this.maxScale);
        if (newScale === this.scale) return;
        const stageRect = this.stage.getBoundingClientRect();
        const cx = (p1.x + p2.x) / 2 - stageRect.left;
        const cy = (p1.y + p2.y) / 2 - stageRect.top;
        this._zoomAt(cx, cy, newScale);
        return;
      }

      // Drag (one finger / mouse)
      if (e.pointerId !== dragPointerId) return;
      this.tx = dragStartTx + (e.clientX - dragStartX);
      this.ty = dragStartTy + (e.clientY - dragStartY);
      this._clamp();
      this._applyTransform();
    });

    const onEnd = (e) => {
      pointers.delete(e.pointerId);

      if (e.pointerId === dragPointerId) {
        this.stage.releasePointerCapture(e.pointerId);
        dragPointerId = null;
        this.stage.classList.remove('is-dragging');
      }
      // After lifting one of two pinch fingers, drop pinch state.
      // We do NOT auto-transition to drag — wait for fresh pointerdown.
      if (pointers.size < 2) {
        pinchStartDist = 0;
      }
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
    // Defer fit to next animation frame — mobile Chrome/Safari sometimes
    // doesn't synchronously layout fixed+flex children, so getBoundingClientRect
    // returns near-zero before the next paint. rAF ensures the stage has its
    // final size when we compute the fit.
    requestAnimationFrame(() => {
      if (this.modalCtrl) this.modalCtrl.fitToViewport();
    });

    // Wheel zoom kolem pozice kurzoru (jen v modalu, ne v inline)
    this._wheelHandler = (e) => {
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
    };
    stage.addEventListener('wheel', this._wheelHandler, { passive: false });

    // Resize — přizpůsobit modal při změně rozměrů okna (rotace, resize)
    this._resizeHandler = () => this.modalCtrl && this.modalCtrl.fitToViewport();
    window.addEventListener('resize', this._resizeHandler);

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
    window.removeEventListener('resize', this._resizeHandler);
    if (this.modalStage) this.modalStage.removeEventListener('wheel', this._wheelHandler);
    document.body.style.overflow = this._prevBodyOverflow || '';
    this.modal.removeEventListener('keydown', this._trapHandler);
    this.modal.remove();
    this.modal = null;
    this.modalStage = null;
    this.modalCloseBtn = null;
    this.modalToolbar = null;
    this.modalCtrl = null;
    this._wheelHandler = null;
    this._resizeHandler = null;
    if (this._prevActive && this._prevActive.focus) {
      this._prevActive.focus();
    } else if (this.btnFs) {
      this.btnFs.focus();
    }
    this._prevActive = null;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-diagram]').forEach((el) => new DiagramViewer(el));
});
