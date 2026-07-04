// ──────────────────────────────────────────────────────────────────────────
// Code block — copy button; zvýraznění i řádky (.ln) renderuje server
// ──────────────────────────────────────────────────────────────────────────

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

document.addEventListener('DOMContentLoaded', function () {
  // Copy button delegation
  document.body.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;

    const figure = btn.closest('figure.code');
    if (!figure) return;
    const codeEl = figure.querySelector('pre code');
    if (!codeEl) return;

    // Sebrat čistý text (bez line numbers spans) — vezmeme textContent z .ln-text
    const lns = codeEl.querySelectorAll('.ln-text');
    const text = lns.length > 0
      ? Array.from(lns).map(function (el) { return el.textContent; }).join('\n')
      : codeEl.textContent;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { showCopied(btn); }).catch(function () {});
    } else {
      // Fallback
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); showCopied(btn); } catch (_) {}
      document.body.removeChild(ta);
    }
  });

  function showCopied(btn) {
    const label = btn.querySelector('.code-copy-label');
    if (!label) return;
    const original = label.textContent;
    label.textContent = 'zkopírováno ✓';
    btn.classList.add('code-copied');
    setTimeout(function () {
      label.textContent = original;
      btn.classList.remove('code-copied');
    }, 1400);
  }
});
