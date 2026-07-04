#!/usr/bin/env node
// ──────────────────────────────────────────────────────────────────────────
// Axe-core přístupnostní kontrola klíčových stránek (obě barevná témata).
// Používá se v CI (viz .github/workflows/deploy.yml); vyžaduje běžící web,
// URL základu se předává přes BASE_URL (default http://127.0.0.1:8765).
//
// Spuštění:  node scripts/axe-check.mjs
// ──────────────────────────────────────────────────────────────────────────

import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8765';
const PATHS = ['/', '/cqrs', '/glosar', '/zaklady'];
const THEMES = ['dark', 'light'];

const browser = await chromium.launch();
let failed = false;

for (const theme of THEMES) {
  const context = await browser.newContext({
    colorScheme: theme === 'light' ? 'light' : 'dark',
  });
  const page = await context.newPage();

  for (const path of PATHS) {
    await page.goto(BASE + path, { waitUntil: 'load' });
    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa'])
      .analyze();

    if (results.violations.length === 0) {
      console.log(`✓ ${theme.padEnd(5)} ${path}`);
      continue;
    }

    failed = true;
    console.error(`✗ ${theme.padEnd(5)} ${path} — ${results.violations.length} nálezů:`);
    for (const v of results.violations) {
      console.error(`    [${v.impact}] ${v.id}: ${v.help} (${v.nodes.length}×)`);
      for (const node of v.nodes.slice(0, 3)) {
        console.error(`        ${node.target.join(' ')}`);
      }
    }
  }

  await context.close();
}

await browser.close();
process.exit(failed ? 1 : 0);
