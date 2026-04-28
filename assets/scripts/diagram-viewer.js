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
