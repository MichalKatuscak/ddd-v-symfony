// ──────────────────────────────────────────────────────────────────────────
// Code block — po hljs highlight wrapne každý řádek, řeší copy button
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

// ──────────────────────────────────────────────────────────────────────────

window.__enhanceCodeBlock = function (codeEl) {
  if (codeEl.dataset.enhanced === 'true') return;
  codeEl.dataset.enhanced = 'true';

  const pre = codeEl.parentElement;
  const hlAttr = pre ? pre.dataset.highlight : '';
  const highlights = (hlAttr || '').split(',').map(function (s) { return parseInt(s.trim(), 10); }).filter(Boolean);

  // Rozdělení HTML na řádky tak, aby se zachovala vyváženost tagů.
  // hljs občas obaluje konstrukce (např. class) přes víc řádků – při naivním split('\n')
  // by se otevřené spany táhly přes řádky a poškodily DOM strukturu .ln wrapperů.
  // Tady tagy uzavřeme na konci každého řádku a znovu otevřeme na začátku dalšího.
  const html = codeEl.innerHTML;
  const tokenRegex = /(<[^>]+>)|(\n)|([^<\n]+)/g;
  const lines = [['']]; // pole řádků; každý řádek = pole HTML kusů
  const openStack = []; // pole otevřených tagů (jejich plná opening string)
  const lineOpenStacks = [[]]; // openStack na začátku každého řádku
  let m;
  while ((m = tokenRegex.exec(html)) !== null) {
    if (m[1]) {
      // tag
      const tag = m[1];
      lines[lines.length - 1].push(tag);
      if (tag.startsWith('</')) {
        openStack.pop();
      } else if (!tag.endsWith('/>') && !/^<(br|hr|img|input|wbr)\b/i.test(tag)) {
        openStack.push(tag);
      }
    } else if (m[2]) {
      // newline — uzavři všechny otevřené tagy na konci aktuálního řádku
      for (let i = openStack.length - 1; i >= 0; i--) {
        lines[lines.length - 1].push('</span>');
      }
      lines.push([]);
      lineOpenStacks.push(openStack.slice());
      // znovuotevři tagy na začátku nového řádku
      for (const open of openStack) {
        lines[lines.length - 1].push(open);
      }
    } else if (m[3]) {
      lines[lines.length - 1].push(m[3]);
    }
  }

  // Pokud poslední řádek je prázdný (trailing newline), zahodíme ho
  while (lines.length > 1) {
    const last = lines[lines.length - 1].join('').replace(/<[^>]+>/g, '').replace(/\s/g, '');
    if (last === '') {
      lines.pop();
    } else {
      break;
    }
  }

  const wrapped = lines.map(function (parts, i) {
    const num = i + 1;
    const isHl = highlights.includes(num) ? ' ln-hl' : '';
    const lineHTML = parts.join('');
    const safeLine = lineHTML.replace(/<[^>]+>/g, '').trim() === '' ? '&nbsp;' : lineHTML;
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
