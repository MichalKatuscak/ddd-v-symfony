// ──────────────────────────────────────────────────────────────────────────
// Reading progress — tichá stopa „kde jsem v knize“.
// Role:
//   1) Na rozcestnících (hub, homepage TOC) tlumí karty kapitol, které
//      už uživatel přečetl. Žádné procenta, žádné ✓ – jen ztlumené číslo.
//   2) Na stránce kapitoly: plní tenký progress bar pod hlavičkou, označí
//      route za přečtenou při doscrolování k 90 %, a ukládá poslední pozici.
//   3) Na homepage z poslední pozice vykreslí kartu „Pokračovat ve čtení“.
// Stav je per-prohlížeč (localStorage), bez backendu, bez analytics.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  const STORAGE_KEY = 'ddd:read';
  const LAST_KEY = 'ddd:last';

  function loadSet() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return new Set();
      const arr = JSON.parse(raw);
      return new Set(Array.isArray(arr) ? arr : []);
    } catch (_) {
      return new Set();
    }
  }

  function saveSet(set) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(set)));
    } catch (_) { /* localStorage zaplněný / disabled — tichá ignorace */ }
  }

  function saveLast(obj) {
    try { localStorage.setItem(LAST_KEY, JSON.stringify(obj)); } catch (_) {}
  }

  function loadLast() {
    try {
      const raw = localStorage.getItem(LAST_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (_) { return null; }
  }

  function tagReadCards(set) {
    document.querySelectorAll('[data-route]').forEach(function (el) {
      if (set.has(el.dataset.route)) {
        el.classList.add('is-read');
      }
    });
  }

  // Postup čtení článku v rozsahu 0–1: kolik výšky <article> už proteklo dolní
  // hranou viewportu (od horní hrany článku).
  function articleProgress(article) {
    const rect = article.getBoundingClientRect();
    const articleTop = rect.top + window.scrollY;
    const height = article.offsetHeight;
    if (height <= 0) return 0;
    const scrolled = window.scrollY + window.innerHeight - articleTop;
    return Math.max(0, Math.min(1, scrolled / height));
  }

  function watchChapter(set) {
    const article = document.querySelector('article[data-chapter-route]');
    if (!article) return;
    const route = article.dataset.chapterRoute;
    if (!route) return;

    const bar = document.querySelector('[data-reading-bar]');
    const titleEl = article.querySelector('.art-title');
    const bodyEl = article.querySelector('[data-chapter-number]');
    const meta = {
      route: route,
      url: window.location.pathname,
      title: titleEl ? titleEl.textContent.trim() : document.title,
      num: bodyEl ? (bodyEl.dataset.chapterNumber || '') : '',
    };

    let marked = set.has(route);
    let ticking = false;
    let lastPct = -1;

    function update() {
      ticking = false;
      const p = articleProgress(article);
      if (bar) bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';

      // Poslední pozice – zápis do localStorage jen při změně celého procenta,
      // ne v každém scroll snímku (synchronní I/O by zbytečně zatěžoval scroll).
      const pct = Math.round(p * 100);
      if (pct !== lastPct) {
        lastPct = pct;
        saveLast({ route: meta.route, url: meta.url, title: meta.title, num: meta.num, pct: pct });
      }

      if (!marked && p >= 0.9) {
        marked = true;
        set.add(route);
        saveSet(set);
      }
    }

    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(update);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    update();
  }

  // Karta „Pokračovat ve čtení“ na homepage — naplní se z poslední pozice.
  function renderResume(set) {
    const card = document.querySelector('[data-resume]');
    if (!card) return;
    const last = loadLast();
    if (!last || !last.url || !last.title) return;
    // Přečtené (≥90 %) kapitoly už nenabízíme jako „pokračovat“.
    if (set.has(last.route) || (last.pct || 0) >= 95) return;

    // [data-resume-link] bývá samotná karta (<a>), querySelector hledá jen potomky.
    const link = card.matches('[data-resume-link]') ? card : card.querySelector('[data-resume-link]');
    const titleEl = card.querySelector('[data-resume-title]');
    const numEl = card.querySelector('[data-resume-num]');
    const pctEl = card.querySelector('[data-resume-pct]');
    if (link) link.href = last.url;
    if (titleEl) titleEl.textContent = last.title;
    if (numEl) numEl.textContent = last.num ? ('Kapitola ' + last.num) : 'Rozečteno';
    if (pctEl) pctEl.textContent = (last.pct || 0) + ' %';
    const fill = card.querySelector('[data-resume-fill]');
    if (fill) fill.style.width = (last.pct || 0) + '%';

    card.hidden = false;
  }

  document.addEventListener('DOMContentLoaded', function () {
    const set = loadSet();
    tagReadCards(set);
    watchChapter(set);
    renderResume(set);
  });
})();
