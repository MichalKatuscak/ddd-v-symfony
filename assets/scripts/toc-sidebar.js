// toc-sidebar.js — Generates table of contents from article headings
document.addEventListener('DOMContentLoaded', function () {
    var tocSidebar = document.querySelector('.toc-sidebar');
    if (!tocSidebar) return;
    var article = document.querySelector('.content-area article');
    if (!article) { tocSidebar.classList.add('no-toc'); return; }
    var headings = Array.prototype.slice.call(article.querySelectorAll('h2[id], h3[id]'));
    if (headings.length === 0) { tocSidebar.classList.add('no-toc'); return; }
    var ul = document.createElement('ul');
    ul.className = 'toc-list';
    headings.forEach(function (heading) {
        var li = document.createElement('li');
        if (heading.tagName === 'H3') li.classList.add('toc-h3');
        var a = document.createElement('a');
        a.href = '#' + heading.id;
        var text = '';
        heading.childNodes.forEach(function (node) {
            if (node.nodeType === 3) text += node.textContent;
        });
        a.textContent = text.trim();
        li.appendChild(a);
        ul.appendChild(li);
    });
    var titleEl = tocSidebar.querySelector('.toc-title');
    if (titleEl) { titleEl.after(ul); } else { tocSidebar.appendChild(ul); }
    var links = Array.prototype.slice.call(ul.querySelectorAll('a'));
    var activeLink = null;
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var id = entry.target.id;
            var link = null;
            for (var i = 0; i < links.length; i++) {
                if (links[i].getAttribute('href') === '#' + id) { link = links[i]; break; }
            }
            if (!link) return;
            if (activeLink) activeLink.classList.remove('active');
            activeLink = link;
            activeLink.classList.add('active');
        });
    }, { rootMargin: '-56px 0px -70% 0px', threshold: 0 });
    headings.forEach(function (h) { observer.observe(h); });
});
