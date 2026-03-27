// modern-script.js — Dark Mode Redesign

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar toggle (mobile) ──────────────────────────────────────────────
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');

    function openSidebar() {
        sidebar.classList.add('open');
        backdrop.classList.add('active');
        sidebarToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('active');
        sidebarToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    // Close sidebar when a nav link is clicked (mobile)
    document.querySelectorAll('.sidebar-nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) closeSidebar();
        });
    });

    // ── Scroll to top ────────────────────────────────────────────────────────
    const scrollBtn = document.querySelector('.scroll-to-top');
    if (scrollBtn) {
        window.addEventListener('scroll', function () {
            scrollBtn.classList.toggle('show', window.pageYOffset > 300);
        });
        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ── Smooth anchor scrolling ──────────────────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.startsWith('#')) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                    history.pushState(null, null, href);
                }
            }
        });
    });

    // ── Heading anchor links ─────────────────────────────────────────────────
    document.querySelectorAll('h2[id], h3[id], h4[id]').forEach(function (heading) {
        const anchor = document.createElement('a');
        anchor.className = 'anchor-link';
        anchor.href = '#' + heading.id;
        anchor.textContent = '#';
        anchor.setAttribute('aria-label', 'Link na tuto sekci');
        heading.appendChild(anchor);
    });

    // ── Table responsiveness ─────────────────────────────────────────────────
    document.querySelectorAll('table').forEach(function (table) {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // ── Fade-in observer (updated class names) ───────────────────────────────
    const fadeTargets = document.querySelectorAll(
        '.card, .note, .tip, .warning, .caution'
    );
    if (fadeTargets.length > 0) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        fadeTargets.forEach(function (el) { observer.observe(el); });
    }

    // ── Prev/Next navigation ─────────────────────────────────────────────────
    const CHAPTERS = [
        { label: 'Úvod', url: '/' },
        { label: 'Co je DDD', url: '/co-je-ddd' },
        { label: 'Základní koncepty', url: '/zakladni-koncepty' },
        { label: 'Vertikální slice', url: '/horizontalni-vs-vertikalni' },
        { label: 'Implementace v Symfony', url: '/implementace-v-symfony' },
        { label: 'CQRS', url: '/cqrs' },
        { label: 'Event Sourcing', url: '/event-sourcing' },
        { label: 'Ságy a Process Managery', url: '/sagy-a-process-managery' },
        { label: 'DDD v praxi — kde to bolí', url: '/ddd-v-praxi-kde-to-boli' },
        { label: 'Kdy DDD nepoužívat', url: '/kdy-nepouzivat-ddd' },
        { label: 'Příklady', url: '/prakticke-priklady' },
        { label: 'Případová studie', url: '/pripadova-studie' },
        { label: 'Testování DDD', url: '/testovani-ddd' },
        { label: 'Migrace z CRUD', url: '/migrace-z-crud' },
        { label: 'Anti-vzory', url: '/anti-vzory' },
        { label: 'Výkonnostní aspekty', url: '/vykonnostni-aspekty' },
        { label: 'DDD a AI', url: '/ddd-a-umela-inteligence' },
        { label: 'Zdroje', url: '/zdroje' },
        { label: 'Glosář', url: '/glosar' },
    ];

    const currentPath = window.location.pathname;
    const currentIndex = CHAPTERS.findIndex(function (c) { return c.url === currentPath; });

    if (currentIndex !== -1) {
        const contentArea = document.querySelector('.content-area');
        if (contentArea) {
            const nav = document.createElement('nav');
            nav.className = 'chapter-nav';
            nav.setAttribute('aria-label', 'Navigace mezi kapitolami');

            const prev = CHAPTERS[currentIndex - 1];
            const next = CHAPTERS[currentIndex + 1];

            if (prev) {
                const prevLink = document.createElement('a');
                prevLink.href = prev.url;
                prevLink.className = 'chapter-nav-card chapter-nav-prev';
                prevLink.innerHTML = '<span class="chapter-nav-dir">← Předchozí</span><span class="chapter-nav-label">' + prev.label + '</span>';
                nav.appendChild(prevLink);
            } else {
                nav.appendChild(document.createElement('div')); // placeholder to keep next on the right
            }

            if (next) {
                const nextLink = document.createElement('a');
                nextLink.href = next.url;
                nextLink.className = 'chapter-nav-card chapter-nav-next';
                nextLink.innerHTML = '<span class="chapter-nav-dir">Další →</span><span class="chapter-nav-label">' + next.label + '</span>';
                nav.appendChild(nextLink);
            }

            contentArea.appendChild(nav);
        }
    }

});
