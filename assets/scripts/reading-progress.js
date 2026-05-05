// ──────────────────────────────────────────────────────────────────────────
// Reading progress — tichá stopa „kde jsem v knize“.
// Dvě role:
//   1) Na rozcestnících (hub, homepage TOC) tlumí karty kapitol, které
//      už uživatel přečetl. Žádné procenta, žádné ✓ – jen ztlumené číslo.
//   2) Na stránce kapitoly označí route za přečtenou, jakmile uživatel
//      doscroluje k 90 % výšky <article>.
// Stav je per-prohlížeč (localStorage), bez backendu, bez analytics.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  const STORAGE_KEY = 'ddd:read';

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

  function tagReadCards(set) {
    document.querySelectorAll('[data-route]').forEach(function (el) {
      if (set.has(el.dataset.route)) {
        el.classList.add('is-read');
      }
    });
  }

  function watchChapterScroll(set) {
    const article = document.querySelector('article[data-chapter-route]');
    if (!article) return;
    const route = article.dataset.chapterRoute;
    if (!route || set.has(route)) return;

    let marked = false;
    let ticking = false;

    function check() {
      ticking = false;
      if (marked) return;
      const rect = article.getBoundingClientRect();
      const articleTop = rect.top + window.scrollY;
      const articleHeight = article.offsetHeight;
      if (articleHeight <= 0) return;
      const visited = window.scrollY + window.innerHeight - articleTop;
      if (visited / articleHeight >= 0.9) {
        marked = true;
        set.add(route);
        saveSet(set);
        window.removeEventListener('scroll', onScroll);
      }
    }

    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(check);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    check();
  }

  document.addEventListener('DOMContentLoaded', function () {
    const set = loadSet();
    tagReadCards(set);
    watchChapterScroll(set);
  });
})();
