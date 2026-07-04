#!/usr/bin/env node
// ──────────────────────────────────────────────────────────────────────────
// Generátor OG obrázků kapitol (1200×630 PNG do public/images/og/).
//
// Vizuál drží brand webu: tmavý blueprint, mřížka, nested-squares značka,
// jantarový akcent, Inter + JetBrains Mono (skutečné webfonty přes file://).
// Renderuje headless Chromium (Playwright), takže lámání českých titulků
// řeší prohlížeč, ne aproximace šířky znaků.
//
// Spuštění:  npm run og   (po přidání/přejmenování kapitoly; PNG se commitují)
// ──────────────────────────────────────────────────────────────────────────

import { chromium } from 'playwright';
import { readFileSync, readdirSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const outDir = join(root, 'public', 'images', 'og');
mkdirSync(outDir, { recursive: true });

// ── Frontmatter (jen jednoduché "klíč: hodnota" scalary, to nám stačí) ────
function parseFrontmatter(raw) {
  const m = raw.replace(/\r\n?/g, '\n').match(/^---\n([\s\S]*?)\n---/);
  if (!m) return null;
  const data = {};
  for (const line of m[1].split('\n')) {
    const kv = line.match(/^(\w+):\s*(.*)$/);
    if (!kv) continue;
    let v = kv[2].trim();
    if ((v.startsWith('"') && v.endsWith('"')) || (v.startsWith("'") && v.endsWith("'"))) {
      v = v.slice(1, -1);
    }
    data[kv[1]] = v;
  }
  return data;
}

const chapters = readdirSync(join(root, 'content', 'chapters'))
  .filter((f) => f.endsWith('.md'))
  .map((f) => parseFrontmatter(readFileSync(join(root, 'content', 'chapters', f), 'utf8')))
  .filter((fm) => fm && fm.path && fm.title);

const fontsUrl = pathToFileURL(join(root, 'assets', 'fonts')).href;

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

function cardHtml(fm) {
  const num = fm.chapter_number ?? '';
  const isNumeric = /^\d+$/.test(num);
  const kicker = isNumeric ? `KAPITOLA ${num}` : (fm.category ?? 'REFERENCE').toUpperCase();
  const watermark = isNumeric ? num : num.toUpperCase();
  // Pomlčka nesmí začínat řádek (česká typografie) – nezlomitelná mezera
  // ji sváže s předchozím slovem, zlom pak nastane až za ní.
  const title = fm.title.replace(/ – /g, ' – ');
  const titleSize = title.length > 45 ? 58 : 68;

  return `<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
  @font-face { font-family: 'Inter'; font-weight: 400 700; src: url('${fontsUrl}/inter-latin.woff2') format('woff2');
    unicode-range: U+0000-00FF, U+2000-206F; }
  @font-face { font-family: 'Inter'; font-weight: 400 700; src: url('${fontsUrl}/inter-latin-ext.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+1E00-1E9F; }
  @font-face { font-family: 'JetBrains Mono'; font-weight: 400 500; src: url('${fontsUrl}/jetbrains-mono-latin.woff2') format('woff2');
    unicode-range: U+0000-00FF; }
  @font-face { font-family: 'JetBrains Mono'; font-weight: 400 500; src: url('${fontsUrl}/jetbrains-mono-latin-ext.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+1E00-1E9F; }

  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { width: 1200px; height: 630px; overflow: hidden; }
  body {
    position: relative;
    background: #0B0D10;
    color: #E6E8EC;
    font-family: 'Inter', sans-serif;
    /* Mřížka jako .bg-grid na webu */
    background-image:
      linear-gradient(rgba(230,232,236,0.05) 1px, transparent 1px),
      linear-gradient(90deg, rgba(230,232,236,0.05) 1px, transparent 1px),
      linear-gradient(rgba(230,232,236,0.02) 1px, transparent 1px),
      linear-gradient(90deg, rgba(230,232,236,0.02) 1px, transparent 1px);
    background-size: 96px 96px, 96px 96px, 24px 24px, 24px 24px;
    padding: 64px 72px;
    display: flex;
    flex-direction: column;
  }
  .frame { position: absolute; inset: 24px; border: 1px solid rgba(230,232,236,0.10); pointer-events: none; }
  .top { display: flex; align-items: center; justify-content: space-between; }
  .brand { display: flex; align-items: center; gap: 18px; }
  .brand-name { font-family: 'JetBrains Mono', monospace; font-size: 24px; color: #A6ADBB; letter-spacing: 0.02em; }
  .brand-name b { color: #E6E8EC; font-weight: 500; }
  .kicker {
    font-family: 'JetBrains Mono', monospace; font-size: 21px; font-weight: 500;
    color: #F0A456; letter-spacing: 0.18em;
  }
  .title {
    margin-top: auto;
    margin-bottom: auto;
    max-width: 950px;
    font-size: ${titleSize}px;
    font-weight: 700;
    line-height: 1.14;
    letter-spacing: -0.022em;
    text-wrap: balance;
  }
  .bottom { display: flex; align-items: center; justify-content: space-between; }
  .tag {
    font-family: 'JetBrains Mono', monospace; font-size: 19px; font-weight: 500;
    color: #F0A456; border: 1px solid rgba(240,164,86,0.45);
    padding: 8px 18px; letter-spacing: 0.12em; text-transform: uppercase;
  }
  .domain { font-family: 'JetBrains Mono', monospace; font-size: 20px; color: #7C8492; }
  .watermark {
    position: absolute; right: 48px; bottom: -60px;
    font-family: 'JetBrains Mono', monospace; font-weight: 500;
    font-size: 380px; line-height: 1; color: rgba(240,164,86,0.07);
    pointer-events: none;
  }
</style></head>
<body>
  <div class="frame"></div>
  <div class="watermark">${esc(watermark)}</div>
  <div class="top">
    <div class="brand">
      <svg width="52" height="52" viewBox="0 0 28 28" aria-hidden="true">
        <rect x="1.5" y="1.5" width="25" height="25" fill="none" stroke="#F0A456" stroke-width="1"/>
        <rect x="6" y="6" width="16" height="16" fill="none" stroke="#7C8492" stroke-width="0.75"/>
        <rect x="10.5" y="10.5" width="7" height="7" fill="#F0A456"/>
      </svg>
      <div class="brand-name"><b>DDD</b> · Symfony 8</div>
    </div>
    <div class="kicker">${esc(kicker)}</div>
  </div>
  <h1 class="title">${esc(title)}</h1>
  <div class="bottom">
    <div class="tag">${esc(fm.category ?? 'Kapitola')}</div>
    <div class="domain">ddd-v-symfony.katuscak.cz</div>
  </div>
</body></html>`;
}

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1200, height: 630 } });

for (const fm of chapters) {
  const slug = fm.path.replace(/^\//, '');
  await page.setContent(cardHtml(fm), { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  const file = join(outDir, `${slug}.png`);
  await page.screenshot({ path: file, type: 'png' });
  console.log(`✓ ${slug}.png  (${fm.title})`);
}

await browser.close();
console.log(`\nHotovo: ${chapters.length} OG obrázků v public/images/og/`);
