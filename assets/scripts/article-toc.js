// ──────────────────────────────────────────────────────────────────────────
// Article TOC — generuje seznam z <h2> v .art-body, scroll-spy aktivního.
// Plní paralelně všechny [data-toc-list] (desktop sidebar + mobile <details>).
// ──────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  const tocLists = Array.from(document.querySelectorAll('[data-toc-list]'));
  const body = document.querySelector('.art-body');
  if (tocLists.length === 0 || !body) return;
  if (tocLists[0].children.length > 0) return;

  const headings = Array.from(body.querySelectorAll('h2'));
  if (headings.length < 2) {
    document.querySelectorAll('[data-toc-target], [data-toc-mobile], [data-toc-fab]').forEach(function (el) {
      el.style.display = 'none';
    });
    return;
  }

  // Slugify Czech: lowercase, ASCII fold approx, remove non-alphanum
  function slugify(text) {
    return text
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  const usedSlugs = new Set();
  function uniqueSlug(text) {
    let base = slugify(text || 'section');
    if (!base) base = 'section';
    let slug = base;
    let n = 2;
    while (usedSlugs.has(slug)) { slug = base + '-' + n; n++; }
    usedSlugs.add(slug);
    return slug;
  }

  // Render TOC items
  const chapterEl = document.querySelector('[data-chapter-number]');
  const chapterNum = chapterEl ? chapterEl.dataset.chapterNumber : '';

  const itemsData = headings.map(function (h, i) {
    if (!h.id) h.id = uniqueSlug(h.textContent || ('section-' + (i + 1)));
    const num = chapterNum
      ? chapterNum + '.' + String(i + 1).padStart(2, '0')
      : String(i + 1).padStart(2, '0');

    // Titul bez čísla sekce (.h-num) a bez kotvícího „#" (.h-anchor).
    const clone = h.cloneNode(true);
    clone.querySelectorAll('.h-num, .h-anchor').forEach(function (n) { n.remove(); });
    const titleText = clone.textContent.trim();

    return { id: h.id, num: num, title: titleText };
  });

  function buildLi(data) {
    const li = document.createElement('li');
    li.dataset.targetId = data.id;
    li.innerHTML =
      '<span class="toc-num">' + data.num + '</span>' +
      '<a href="#' + data.id + '"><span class="toc-text">' + data.title + '</span></a>';
    return li;
  }

  tocLists.forEach(function (list) {
    itemsData.forEach(function (data) { list.appendChild(buildLi(data)); });
  });

  // Section count v summary mobilního TOC (s českou deklinací)
  const countEls = document.querySelectorAll('[data-toc-count]');
  if (countEls.length > 0) {
    const n = itemsData.length;
    const word = n === 1 ? 'sekce' : (n >= 2 && n <= 4 ? 'sekce' : 'sekcí');
    countEls.forEach(function (el) { el.textContent = n + ' ' + word; });
  }

  // Auto-close mobile <details> po kliknutí na položku
  const mobileDetails = document.querySelector('[data-toc-mobile]');
  if (mobileDetails) {
    mobileDetails.addEventListener('click', function (e) {
      const a = e.target.closest('a[href^="#"]');
      if (a && mobileDetails.contains(a)) mobileDetails.open = false;
    });
  }

  // Scroll-spy přes IntersectionObserver — vybírá topmost h2 podle bounding rect
  function updateCurrent() {
    const threshold = 80;
    let current = null;
    for (let i = 0; i < headings.length; i++) {
      const top = headings[i].getBoundingClientRect().top;
      if (top - threshold <= 0) {
        current = headings[i];
      } else {
        break;
      }
    }
    if (!current && headings.length > 0) {
      const firstTop = headings[0].getBoundingClientRect().top;
      if (firstTop < window.innerHeight * 0.4) current = headings[0];
    }
    const currentId = current ? current.id : null;
    document.querySelectorAll('[data-toc-list] > li').forEach(function (li) {
      li.classList.toggle('toc-current', li.dataset.targetId === currentId);
    });
  }

  const observer = new IntersectionObserver(function () {
    updateCurrent();
  }, {
    rootMargin: '-80px 0px -60% 0px',
    threshold: 0.1
  });

  headings.forEach(function (h) { observer.observe(h); });

  // Pojistka pro skokové přesuny (tažení scrollbarem, skok na #kotvu): velký
  // okamžitý posun přeskočí pásmo observeru a žádný callback nepřijde.
  // Throttlovaný scroll listener drží zvýraznění aktuální i v těchto případech.
  let spyTicking = false;
  window.addEventListener('scroll', function () {
    if (spyTicking) return;
    spyTicking = true;
    window.requestAnimationFrame(function () {
      spyTicking = false;
      updateCurrent();
    });
  }, { passive: true });

  // Plovoucí TOC (mobil): otevření/zavření panelu se seznamem sekcí.
  const fab = document.querySelector('[data-toc-fab]');
  if (fab) {
    const toggle = fab.querySelector('[data-toc-fab-toggle]');
    const panel = fab.querySelector('[data-toc-fab-panel]');
    if (toggle && panel) {
      const setFab = function (open) {
        toggle.setAttribute('aria-expanded', String(open));
        panel.hidden = !open;
        fab.classList.toggle('is-open', open);
      };
      toggle.addEventListener('click', function () {
        setFab(panel.hidden);
      });
      panel.addEventListener('click', function (e) {
        if (e.target.closest('a[href^="#"]')) setFab(false);
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) { setFab(false); toggle.focus(); }
      });
      document.addEventListener('click', function (e) {
        if (!panel.hidden && !fab.contains(e.target)) setFab(false);
      });
    }
  }
});
