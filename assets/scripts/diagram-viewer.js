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
    this._bindPan();
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
      else if (action === 'fullscreen') this.openFullscreen();
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
    this._clamp();
    this._applyTransform();
  }

  fit() {
    this.scale = 1;
    this.tx = 0;
    this.ty = 0;
    this._applyTransform();
  }

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
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-diagram]').forEach((el) => new DiagramViewer(el));
});
