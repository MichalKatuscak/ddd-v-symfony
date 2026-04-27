// ──────────────────────────────────────────────────────────────────────────
// Article TOC — generuje seznam z <h2> v .art-body, scroll-spy aktivního
// ──────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  const tocList = document.querySelector('[data-toc-list]');
  const body = document.querySelector('.art-body');
  if (!tocList || !body) return;

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

  // Render TOC items
  const chapterEl = document.querySelector('[data-chapter-number]');
  const chapterNum = chapterEl ? chapterEl.dataset.chapterNumber : '';

  const items = headings.map(function (h, i) {
    if (!h.id) h.id = slugify(h.textContent || ('section-' + (i + 1)));
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

  // Scroll-spy přes IntersectionObserver
  const linkByHash = new Map();
  items.forEach(function (li) { linkByHash.set(li.dataset.targetId, li); });

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        const li = linkByHash.get(entry.target.id);
        if (!li) return;
        items.forEach(function (x) { x.classList.remove('toc-current'); });
        li.classList.add('toc-current');
      }
    });
  }, {
    rootMargin: '-80px 0px -60% 0px',
    threshold: 0.1
  });

  headings.forEach(function (h) { observer.observe(h); });
});
