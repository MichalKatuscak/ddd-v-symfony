// ──────────────────────────────────────────────────────────────────────────
// Filtr glosáře — lokální našeptávač pojmů. Levná alternativa k full-textu:
// skryje hesla, jejichž termín (CZ i EN) neodpovídá dotazu, a schová prázdné
// sekce. Bez diakritiky a bez ohledu na velikost písmen.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  const root = document.querySelector('[data-glossary]');
  const input = document.querySelector('[data-glossary-input]');
  if (!root || !input) return;

  const countEl = document.querySelector('[data-glossary-count]');
  const emptyEl = document.querySelector('[data-glossary-empty]');
  const entries = Array.from(root.querySelectorAll('.glossary-entry'));
  const sections = Array.from(root.querySelectorAll('section'));

  function norm(s) {
    return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  }

  // Předpočítej hledaný text z <dt> (termín CZ + EN) každého hesla.
  const haystacks = entries.map(function (e) {
    const dt = e.querySelector('dt');
    return norm(dt ? dt.textContent : e.textContent);
  });

  const total = entries.length;

  function setCount(visible) {
    if (!countEl) return;
    const n = visible;
    const word = n === 1 ? 'pojem' : (n >= 2 && n <= 4 ? 'pojmy' : 'pojmů');
    countEl.textContent = n + ' ' + word + (n < total ? ' z ' + total : '');
  }

  function apply() {
    const q = norm(input.value).trim();
    let visible = 0;

    entries.forEach(function (e, i) {
      const match = q === '' || haystacks[i].indexOf(q) !== -1;
      e.hidden = !match;
      if (match) visible++;
    });

    // Schovej sekce (nadpis + intro), které nemají žádné viditelné heslo.
    sections.forEach(function (sec) {
      const any = sec.querySelector('.glossary-entry:not([hidden])');
      sec.hidden = q !== '' && !any;
    });

    if (emptyEl) emptyEl.hidden = !(q !== '' && visible === 0);
    setCount(visible);
  }

  input.addEventListener('input', apply);
  // Esc vyčistí filtr.
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && input.value) { input.value = ''; apply(); }
  });

  setCount(total);
})();
