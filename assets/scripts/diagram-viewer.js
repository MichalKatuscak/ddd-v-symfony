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
