// Fonts (self-hosted)
import './styles/fonts.css';

// Styles
import './styles/modern-style.css';
import './styles/code-style.css';

// highlight.js — only needed languages (tree-shaking saves ~500KB)
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import yaml from 'highlight.js/lib/languages/yaml';
import xml from 'highlight.js/lib/languages/xml';
import bash from 'highlight.js/lib/languages/bash';
import json from 'highlight.js/lib/languages/json';
import javascript from 'highlight.js/lib/languages/javascript';
import sql from 'highlight.js/lib/languages/sql';
import plaintext from 'highlight.js/lib/languages/plaintext';
import 'highlight.js/styles/atom-one-dark.css';

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

// svg-pan-zoom
import svgPanZoom from 'svg-pan-zoom';

// App scripts
import './scripts/modern-script.js';
import './scripts/code-script.js';
import './scripts/toc-sidebar.js';

// Init on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
    // Syntax highlighting
    document.querySelectorAll('pre code').forEach(function (el) {
        hljs.highlightElement(el);
    });

    // SVG Pan/Zoom for diagrams
    document.querySelectorAll('.diagram-container svg').forEach(function (svgElement) {
        svgPanZoom(svgElement, {
            zoomEnabled: true,
            controlIconsEnabled: true,
            fit: true,
            center: true,
            minZoom: 0.5,
            maxZoom: 20,
        });
    });
});
