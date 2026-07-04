// ──────────────────────────────────────────────────────────────────────────
// Přepínač tématu — data-theme atribut nastavuje inline skript v <head>
// (před prvním paintem), tady se řeší klik, persistence a meta theme-color
// ──────────────────────────────────────────────────────────────────────────

(function () {
  var THEME_COLOR = { dark: '#0B0D10', light: '#F5F4F0' };
  var root = document.documentElement;

  function current() {
    return root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
  }

  function apply(theme) {
    root.setAttribute('data-theme', theme);
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', THEME_COLOR[theme]);
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute(
        'aria-label',
        theme === 'dark' ? 'Přepnout na světlý motiv' : 'Přepnout na tmavý motiv'
      );
    });
    // Ostatní moduly (diagram-theme.js) reagují na změnu tématu přes event
    document.dispatchEvent(new CustomEvent('ddd:themechange', { detail: { theme: theme } }));
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Meta i aria-label sladit se stavem, který nastavil inline skript
    apply(current());

    document.body.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-theme-toggle]');
      if (!btn) return;
      var next = current() === 'dark' ? 'light' : 'dark';
      apply(next);
      try { localStorage.setItem('theme', next); } catch (err) {}
    });

    // Sledovat systémovou preferenci, dokud si uživatel sám nevybral
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function (e) {
        var stored = null;
        try { stored = localStorage.getItem('theme'); } catch (err) {}
        if (stored !== 'light' && stored !== 'dark') {
          apply(e.matches ? 'light' : 'dark');
        }
      });
    }
  });
})();
