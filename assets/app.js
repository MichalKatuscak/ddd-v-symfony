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

// highlight.js — registrace pouze potřebných jazyků
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import yaml from 'highlight.js/lib/languages/yaml';
import xml from 'highlight.js/lib/languages/xml';
import bash from 'highlight.js/lib/languages/bash';
import json from 'highlight.js/lib/languages/json';
import javascript from 'highlight.js/lib/languages/javascript';
import sql from 'highlight.js/lib/languages/sql';
import plaintext from 'highlight.js/lib/languages/plaintext';

hljs.registerLanguage('php', php);
hljs.registerLanguage('yaml', yaml);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('html', xml);
hljs.registerLanguage('twig', xml);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('shell', bash);
hljs.registerLanguage('json', json);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('plaintext', plaintext);

// App scripts
import './scripts/topnav.js';
import './scripts/code-block.js';
import './scripts/article-toc.js';
import './scripts/diagram-viewer.js';
import './scripts/reading-progress.js';

// Init na DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
  // Syntax highlighting + line wrapping pro každý code block
  document.querySelectorAll('figure.code pre code').forEach(function (codeEl) {
    hljs.highlightElement(codeEl);
    if (window.__enhanceCodeBlock) window.__enhanceCodeBlock(codeEl);
  });
});
