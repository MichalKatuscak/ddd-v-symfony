// ──────────────────────────────────────────────────────────────────────────
// Kotvící odkazy u nadpisů — kliknutí na „#" zkopíruje absolutní URL sekce
// do schránky a krátce to potvrdí. Výchozí chování (skok na #kotvu) necháme
// proběhnout, takže se zároveň aktualizuje adresní řádek a stránka odscroluje.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  let timer = null;

  function flash(anchor) {
    if (timer) { clearTimeout(timer); }
    document.querySelectorAll('.h-anchor.is-copied').forEach(function (el) {
      el.classList.remove('is-copied');
    });
    anchor.classList.add('is-copied');
    timer = setTimeout(function () { anchor.classList.remove('is-copied'); }, 1200);
  }

  document.addEventListener('click', function (e) {
    const anchor = e.target.closest('.h-anchor');
    if (!anchor) return;
    const href = anchor.getAttribute('href') || '';
    const url = window.location.origin + window.location.pathname + href;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function () { flash(anchor); }).catch(function () {});
    }
    // Bez preventDefault: prohlížeč skočí na #kotvu a nastaví hash v URL.
  });
})();
