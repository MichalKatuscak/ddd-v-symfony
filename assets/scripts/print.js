// ──────────────────────────────────────────────────────────────────────────
// Print — před tiskem otevři všechny <details> (FAQ), aby se v PDF objevily
// i odpovědi, ne jen otázky. Po tisku původní stav obnov.
// (Zavřené <details> jinak v tisku skryjí svůj obsah.)
// Zároveň dočti lazy obrázky (diagramy) — neodscrollovaný img[loading=lazy]
// se jinak vytiskne jako prázdný rámeček.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  let restore = [];
  let opened = false;

  function openAll() {
    // beforeprint a matchMedia('print') můžou přijít obě (Firefox); druhé
    // volání by vyprázdnilo restore a původní stav by se po tisku neobnovil.
    if (opened) return;
    opened = true;
    restore = [];
    document.querySelectorAll('details:not([open])').forEach(function (d) {
      restore.push(d);
      d.open = true;
    });
    document.querySelectorAll('img[loading="lazy"]').forEach(function (img) {
      img.loading = 'eager';
    });
  }

  function restoreAll() {
    opened = false;
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
