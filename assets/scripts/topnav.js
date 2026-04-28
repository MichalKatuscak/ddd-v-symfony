// ──────────────────────────────────────────────────────────────────────────
// Topnav — mobile drawer toggle s focus trapem a návratem fokusu
// ──────────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.querySelector('.nav-toggle');
  const drawer = document.querySelector('.nav-drawer');
  const backdrop = document.querySelector('.nav-drawer-backdrop');

  if (!toggle || !drawer) return;

  // Mark drawer as a modal dialog for assistive tech
  drawer.setAttribute('role', 'dialog');
  drawer.setAttribute('aria-modal', 'true');
  // Initially closed — inert removes drawer ze focus order a aria tree
  drawer.inert = true;

  const FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function focusableInDrawer() {
    return Array.from(drawer.querySelectorAll(FOCUSABLE));
  }

  function setOpen(open) {
    toggle.setAttribute('aria-expanded', String(open));
    drawer.dataset.open = String(open);
    drawer.inert = !open;
    if (backdrop) backdrop.dataset.open = String(open);
    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
      const items = focusableInDrawer();
      if (items.length > 0) items[0].focus();
    } else {
      // Návrat fokusu na toggle button
      toggle.focus();
    }
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
    if (drawer.dataset.open !== 'true') return;
    if (e.key === 'Escape') {
      setOpen(false);
      return;
    }
    // Focus trap — Tab cycluje uvnitř draweru
    if (e.key === 'Tab') {
      const items = focusableInDrawer();
      if (items.length === 0) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });

  // Klik na link v draweru zavře drawer (a navigace stránku stejně přepíše)
  drawer.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      drawer.dataset.open = 'false';
      drawer.inert = true;
      if (backdrop) backdrop.dataset.open = 'false';
      toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    });
  });
});
