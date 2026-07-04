// ──────────────────────────────────────────────────────────────────────────
// Diagramy podle tématu — přepíná src mezi tmavou a světlou variantou SVG
// (data-light-src doplňuje _partials/diagram.html.twig). Díky loading="lazy"
// se stahuje jen varianta, která je skutečně vidět.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
  }

  function sync(theme) {
    document.querySelectorAll('img[data-light-src]').forEach(function (img) {
      if (!img.dataset.darkSrc) img.dataset.darkSrc = img.getAttribute('src');
      var want = theme === 'light' ? img.dataset.lightSrc : img.dataset.darkSrc;
      if (img.getAttribute('src') !== want) img.setAttribute('src', want);
    });
  }

  document.addEventListener('DOMContentLoaded', function () { sync(currentTheme()); });
  document.addEventListener('ddd:themechange', function (e) { sync(e.detail.theme); });
})();
