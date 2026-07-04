#!/usr/bin/env node
// ──────────────────────────────────────────────────────────────────────────
// Světlé varianty PlantUML diagramů (<název>-light.svg vedle tmavých).
//
// Tmavé SVG jsou zdroj pravdy (renderované z .puml přes theme.iuml);
// světlá varianta vzniká deterministickým přemapováním palety – bez
// nutnosti mít lokálně Javu, PlantUML a Graphviz. Sémantické barvy
// (event-storming stickies, stavové zelené/červené) zůstávají: jsou
// navržené pro světlý podklad, na tmavém fungovaly také.
//
// Spuštění:  npm run diagrams:light   (po přerenderování .puml diagramů)
// ──────────────────────────────────────────────────────────────────────────

import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const dir = join(root, 'public', 'images', 'diagrams');

// Mapa tmavá → světlá. Klíče odpovídají paletě v templates/diagrams/theme.iuml
// (BG, BG_ALT, BG_HI, FG, FG_DIM, STROKE, ACCENT, ACCENT_DIM) plus
// per-diagram tmavým tintům. Vše lowercase, 6místný hex.
const MAP = {
  '#11141a': '#fcfbf9', // BG — plátno diagramu
  '#161a22': '#f0eee8', // BG_ALT — výplň uzlů
  '#1b202a': '#e6e3dc', // BG_HI — zvýrazněné výplně (db, queue, note)
  '#e6e8ec': '#1c1f26', // FG — text
  '#a6adbb': '#4a5160', // FG_DIM — sekundární text
  '#3d424e': '#b6bac2', // STROKE — rámečky, lifelines
  '#f0a456': '#a2621c', // ACCENT — šipky, zvýraznění (tmavší jantar kvůli kontrastu)
  '#7a5530': '#e9d3b3', // ACCENT_DIM — jantarový tint
  '#260000': '#fbe4e4', // tmavě červený podklad → světle červený tint
  '#102b00': '#e4f2dd', // tmavě zelený podklad → světle zelený tint
  '#555555': '#d7dade', // tmavě šedý box (externí systém) – text flipnul na tmavý, výplň musí zesvětlat
  '#1976d2': '#90caf9', // event-storming Command – světlejší modrá, ať unese tmavý text
  '#e91e63': '#f48fb1', // event-storming Hot Spot – světlejší růžová, ať unese tmavý text
};

const HEX_RE = /#[0-9a-fA-F]{6}/g;

function* svgFiles(d) {
  for (const entry of readdirSync(d)) {
    const p = join(d, entry);
    if (statSync(p).isDirectory()) {
      yield* svgFiles(p);
    } else if (entry.endsWith('.svg') && !entry.endsWith('-light.svg')) {
      yield p;
    }
  }
}

let count = 0;
for (const file of svgFiles(dir)) {
  const svg = readFileSync(file, 'utf8');
  const light = svg.replace(HEX_RE, (hex) => MAP[hex.toLowerCase()] ?? hex);
  writeFileSync(file.replace(/\.svg$/, '-light.svg'), light);
  count++;
}

console.log(`Hotovo: ${count} světlých variant diagramů.`);
