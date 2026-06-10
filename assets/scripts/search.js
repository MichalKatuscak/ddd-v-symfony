// ──────────────────────────────────────────────────────────────────────────
// Vyhledávání — klientský fulltext nad statickým JSON indexem (/search-index.json).
// Spouští se přes Ctrl/⌘+K, klávesu „/" nebo tlačítko v hlavičce. Index se načte
// líně při prvním otevření a drží v paměti. Bez backendu, bez analytiky.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  const overlay = document.querySelector('[data-search-overlay]');
  const input = document.querySelector('[data-search-input]');
  const resultsEl = document.querySelector('[data-search-results]');
  const emptyEl = document.querySelector('[data-search-empty]');
  const triggers = Array.from(document.querySelectorAll('[data-search-open]'));
  if (!overlay || !input || !resultsEl) return;

  let index = null;
  let loading = null;
  let items = []; // aktuálně vykreslené výsledky
  let active = -1;
  let lastFocused = null;

  function norm(s) {
    return (s || '')
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .toLowerCase();
  }

  function loadIndex() {
    if (index) return Promise.resolve(index);
    if (loading) return loading;
    loading = fetch('/search-index.json', { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (data) {
        index = (Array.isArray(data) ? data : []).map(function (e) {
          return { t: e.t, x: e.x, u: e.u, g: e.g, _t: norm(e.t), _x: norm(e.x) };
        });
        return index;
      })
      .catch(function () { index = []; return index; });
    return loading;
  }

  function search(q) {
    const query = norm(q).trim();
    if (!query) return [];
    const tokens = query.split(/\s+/).filter(Boolean);
    const scored = [];
    for (let i = 0; i < index.length; i++) {
      const e = index[i];
      let ok = true;
      let score = 0;
      for (let j = 0; j < tokens.length; j++) {
        const tk = tokens[j];
        const inTitle = e._t.indexOf(tk);
        const inCtx = e._x.indexOf(tk);
        if (inTitle === -1 && inCtx === -1) { ok = false; break; }
        if (inTitle === 0) score += 100;
        else if (inTitle > 0) score += 40;
        if (inCtx !== -1) score += 8;
      }
      if (!ok) continue;
      // Kratší titulky (přesnější shody) o malinko výš.
      score += Math.max(0, 30 - e.t.length / 4);
      scored.push({ e: e, s: score });
    }
    scored.sort(function (a, b) { return b.s - a.s; });
    return scored.slice(0, 30).map(function (x) { return x.e; });
  }

  function highlight(text, query) {
    const frag = document.createDocumentFragment();
    const n = norm(text);
    const tokens = norm(query).split(/\s+/).filter(Boolean);
    // Najdi první výskyt libovolného tokenu a zvýrazni ho (lehká vizuální nápověda).
    let best = -1, bestLen = 0;
    for (let i = 0; i < tokens.length; i++) {
      const idx = n.indexOf(tokens[i]);
      if (idx !== -1 && (best === -1 || idx < best)) { best = idx; bestLen = tokens[i].length; }
    }
    if (best === -1) { frag.appendChild(document.createTextNode(text)); return frag; }
    frag.appendChild(document.createTextNode(text.slice(0, best)));
    const mark = document.createElement('mark');
    mark.textContent = text.slice(best, best + bestLen);
    frag.appendChild(mark);
    frag.appendChild(document.createTextNode(text.slice(best + bestLen)));
    return frag;
  }

  function render(q) {
    items = search(q);
    resultsEl.innerHTML = '';
    active = items.length ? 0 : -1;

    if (!q.trim()) {
      emptyEl.textContent = 'Začněte psát – prohledá kapitoly, sekce i glosář.';
      emptyEl.hidden = false;
      return;
    }
    if (items.length === 0) {
      emptyEl.textContent = 'Nic nenalezeno. Zkuste jiné slovo.';
      emptyEl.hidden = false;
      return;
    }
    emptyEl.hidden = true;

    const frag = document.createDocumentFragment();
    items.forEach(function (e, i) {
      const a = document.createElement('a');
      a.className = 'search-hit';
      a.href = e.u;
      a.setAttribute('role', 'option');
      a.id = 'search-hit-' + i;
      a.setAttribute('aria-selected', i === active ? 'true' : 'false');

      const main = document.createElement('span');
      main.className = 'search-hit-main';
      const title = document.createElement('span');
      title.className = 'search-hit-title';
      title.appendChild(highlight(e.t, q));
      main.appendChild(title);
      if (e.x && norm(e.x) !== norm(e.t)) {
        const ctx = document.createElement('span');
        ctx.className = 'search-hit-ctx';
        ctx.textContent = e.x.length > 90 ? e.x.slice(0, 90) + '…' : e.x;
        main.appendChild(ctx);
      }
      a.appendChild(main);

      const tag = document.createElement('span');
      tag.className = 'search-hit-tag';
      tag.textContent = e.g;
      a.appendChild(tag);

      frag.appendChild(a);
    });
    resultsEl.appendChild(frag);
    updateActive();
  }

  function updateActive() {
    const hits = resultsEl.querySelectorAll('.search-hit');
    hits.forEach(function (h, i) {
      const on = i === active;
      h.classList.toggle('is-active', on);
      h.setAttribute('aria-selected', on ? 'true' : 'false');
      if (on) h.scrollIntoView({ block: 'nearest' });
    });
    input.setAttribute('aria-activedescendant', active >= 0 ? 'search-hit-' + active : '');
  }

  function move(delta) {
    if (!items.length) return;
    active = (active + delta + items.length) % items.length;
    updateActive();
  }

  function go() {
    if (active >= 0 && items[active]) {
      window.location.href = items[active].u;
    }
  }

  function open() {
    lastFocused = document.activeElement;
    overlay.dataset.open = 'true';
    document.body.style.overflow = 'hidden';
    loadIndex().then(function () { render(input.value); });
    render(input.value);
    setTimeout(function () { input.focus(); input.select(); }, 0);
  }

  function close() {
    overlay.dataset.open = 'false';
    document.body.style.overflow = '';
    input.value = '';
    if (lastFocused && lastFocused.focus) lastFocused.focus();
  }

  function isOpen() { return overlay.dataset.open === 'true'; }

  triggers.forEach(function (btn) {
    btn.addEventListener('click', function (e) { e.preventDefault(); open(); });
  });

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay || e.target.closest('[data-search-backdrop]')) close();
  });

  input.addEventListener('input', function () { render(input.value); });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
    else if (e.key === 'Enter') { e.preventDefault(); go(); }
    else if (e.key === 'Escape') { e.preventDefault(); close(); }
  });

  // Klik na výsledek myší = nech proběhnout navigaci (href).
  resultsEl.addEventListener('mousemove', function (e) {
    const hit = e.target.closest('.search-hit');
    if (!hit) return;
    const i = Array.prototype.indexOf.call(resultsEl.querySelectorAll('.search-hit'), hit);
    if (i !== -1 && i !== active) { active = i; updateActive(); }
  });

  document.addEventListener('keydown', function (e) {
    const k = e.key.toLowerCase();
    if ((e.metaKey || e.ctrlKey) && k === 'k') {
      e.preventDefault();
      isOpen() ? close() : open();
      return;
    }
    // „/" otevře hledání, pokud uživatel zrovna nepíše do pole.
    if (k === '/' && !isOpen()) {
      const tag = (document.activeElement && document.activeElement.tagName) || '';
      const editable = document.activeElement && document.activeElement.isContentEditable;
      if (tag !== 'INPUT' && tag !== 'TEXTAREA' && !editable) {
        e.preventDefault();
        open();
      }
    }
  });
})();
