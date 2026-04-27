// ──────────────────────────────────────────────────────────────────────────
// Topnav — mobile drawer toggle
// ──────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.querySelector('.nav-toggle');
  const drawer = document.querySelector('.nav-drawer');
  const backdrop = document.querySelector('.nav-drawer-backdrop');

  if (!toggle || !drawer) return;

  function setOpen(open) {
    toggle.setAttribute('aria-expanded', String(open));
    drawer.dataset.open = String(open);
    if (backdrop) backdrop.dataset.open = String(open);
    document.body.style.overflow = open ? 'hidden' : '';
  }

  toggle.addEventListener('click', function () {
    const isOpen = drawer.dataset.open === 'true';
    setOpen(!isOpen);
  });

  if (backdrop) {
    backdrop.addEventListener('click', function () { setOpen(false); });
  }

  // ESC zavře drawer
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.dataset.open === 'true') setOpen(false);
  });

  // Klik na link v draweru zavře drawer
  drawer.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () { setOpen(false); });
  });
});
