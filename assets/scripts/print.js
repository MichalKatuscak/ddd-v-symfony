// ──────────────────────────────────────────────────────────────────────────
// Print — před tiskem otevři všechny <details> (FAQ), aby se v PDF objevily
// i odpovědi, ne jen otázky. Po tisku původní stav obnov.
// (Zavřené <details> jinak v tisku skryjí svůj obsah.)
// ──────────────────────────────────────────────────────────────────────────

(function () {
  let restore = [];

  function openAll() {
    restore = [];
    document.querySelectorAll('details:not([open])').forEach(function (d) {
      restore.push(d);
      d.open = true;
    });
  }

  function restoreAll() {
    restore.forEach(function (d) { d.open = false; });
    restore = [];
  }

  window.addEventListener('beforeprint', openAll);
  window.addEventListener('afterprint', restoreAll);

  // Safari/Chrome PDF přes media query change (některé cesty nevolají beforeprint).
  if (window.matchMedia) {
    const mq = window.matchMedia('print');
    mq.addEventListener('change', function (e) { e.matches ? openAll() : restoreAll(); });
  }
})();
