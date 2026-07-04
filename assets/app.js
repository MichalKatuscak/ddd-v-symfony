// ──────────────────────────────────────────────────────────────────────────
// DDD v Symfony 8 — Frontend entry point
// ──────────────────────────────────────────────────────────────────────────

// Design tokens MUSÍ být první (ostatní CSS používá var(--*))
import './styles/tokens.css';
import './styles/fonts.css';
import './styles/base.css';
import './styles/hljs-theme.css';
import './styles/chrome.css';
import './styles/article.css';
import './styles/landing.css';
import './styles/hub.css';
import './styles/print.css'; // @media print — poslední, ať přebíjí v tiskovém kontextu

// Zvýraznění syntaxe probíhá na serveru (App\Content\CodeHighlighter) —
// hljs-theme.css stylizuje hotové hljs-* spany, klient nic nedopočítává.

// App scripts
import './scripts/theme-toggle.js';
import './scripts/diagram-theme.js';
import './scripts/topnav.js';
import './scripts/search.js';
import './scripts/code-block.js';
import './scripts/article-toc.js';
import './scripts/heading-anchor.js';
import './scripts/glossary-filter.js';
import './scripts/print.js';
import './scripts/diagram-viewer.js';
import './scripts/reading-progress.js';
