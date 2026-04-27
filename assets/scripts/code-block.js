// ──────────────────────────────────────────────────────────────────────────
// Code block — po hljs highlight wrapne každý řádek, řeší copy button
// ──────────────────────────────────────────────────────────────────────────

window.__enhanceCodeBlock = function (codeEl) {
  if (codeEl.dataset.enhanced === 'true') return;
  codeEl.dataset.enhanced = 'true';

  const pre = codeEl.parentElement;
  const hlAttr = pre ? pre.dataset.highlight : '';
  const highlights = (hlAttr || '').split(',').map(function (s) { return parseInt(s.trim(), 10); }).filter(Boolean);

  // Highlight už proběhl (hljs.highlightElement v app.js). Vezmeme innerHTML a rozsekáme po \n.
  const html = codeEl.innerHTML;
  const lines = html.split('\n');
  // Pokud poslední řádek je prázdný (kvůli trailing newline), zahodíme ho
  if (lines.length > 1 && lines[lines.length - 1].replace(/\s/g, '') === '') lines.pop();

  const wrapped = lines.map(function (line, i) {
    const num = i + 1;
    const isHl = highlights.includes(num) ? ' ln-hl' : '';
    const safeLine = line === '' ? '&nbsp;' : line;
    return (
      '<span class="ln' + isHl + '">' +
      '<span class="ln-num">' + num + '</span>' +
      '<span class="ln-text">' + safeLine + '</span>' +
      '</span>'
    );
  }).join('');

  codeEl.innerHTML = wrapped;
};

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
