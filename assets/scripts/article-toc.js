// ──────────────────────────────────────────────────────────────────────────
// Article TOC — generuje seznam z <h2> v .art-body, scroll-spy aktivního
// ──────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  const tocList = document.querySelector('[data-toc-list]');
  const body = document.querySelector('.art-body');
  if (!tocList || !body) return;
  if (tocList.children.length > 0) return;

  const headings = Array.from(body.querySelectorAll('h2'));
  if (headings.length === 0) return;

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

  const items = headings.map(function (h, i) {
    if (!h.id) h.id = uniqueSlug(h.textContent || ('section-' + (i + 1)));
    const num = chapterNum
      ? chapterNum + '.' + String(i + 1).padStart(2, '0')
      : String(i + 1).padStart(2, '0');

    // Pokud h2 obsahuje <span class="h-num">, použij text bez něj
    const numSpan = h.querySelector('.h-num');
    const titleText = numSpan
      ? h.textContent.replace(numSpan.textContent, '').trim()
      : h.textContent.trim();

    const li = document.createElement('li');
    li.dataset.targetId = h.id;
    li.innerHTML =
      '<span class="toc-num">' + num + '</span>' +
      '<a href="#' + h.id + '"><span class="toc-text">' + titleText + '</span></a>';
    return li;
  });

  items.forEach(function (li) { tocList.appendChild(li); });

  // Scroll-spy přes IntersectionObserver — vybírá topmost h2 podle bounding rect
  const linkByHash = new Map();
  items.forEach(function (li) { linkByHash.set(li.dataset.targetId, li); });

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
    // Pokud žádný heading ještě nepřekročil práh, použij první (po scrollu zhora)
    if (!current && headings.length > 0) {
      const firstTop = headings[0].getBoundingClientRect().top;
      if (firstTop < window.innerHeight * 0.4) current = headings[0];
    }
    items.forEach(function (x) { x.classList.remove('toc-current'); });
    if (current) {
      const li = linkByHash.get(current.id);
      if (li) li.classList.add('toc-current');
    }
  }

  const observer = new IntersectionObserver(function () {
    updateCurrent();
  }, {
    rootMargin: '-80px 0px -60% 0px',
    threshold: 0.1
  });

  headings.forEach(function (h) { observer.observe(h); });
});
