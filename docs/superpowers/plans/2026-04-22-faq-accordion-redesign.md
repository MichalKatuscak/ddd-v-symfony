# FAQ accordion redesign — implementační plán

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Přepsat FAQ partial z otevřených karet na nativní HTML accordion (`<details>/<summary>`), zredukovat vertikální stopu FAQ o ~70 % a sjednotit vizuál se zbytkem stránek.

**Architecture:** Dva soubory — Twig partial (markup) a jeden CSS blok v globálním stylesheetu. Žádný JS, žádná změna šablon jednotlivých stránek, žádná změna JSON-LD. Po CSS změně je nutný vite rebuild, aby se produkční bundle v `public/build/` aktualizoval.

**Tech Stack:** Symfony 8 (Twig), vanilla HTML5 `<details>/<summary>`, Vite 8 (CSS bundle).

**Spec:** [`docs/superpowers/specs/2026-04-22-faq-accordion-redesign-design.md`](../specs/2026-04-22-faq-accordion-redesign-design.md)

---

## Poznámky k ověřování

Projekt nemá automatizované testy (CLAUDE.md: „no test suite"). Verifikace probíhá přes:
- **Vizuální kontrola v prohlížeči** (PHP built-in server + Playwright screenshoty / manuální kontrola)
- **Schema validace** — Schema Markup Validator + Google Rich Results Test (po lokální kontrole lze řešit i po merge na produkci, pokud to není dostupné v dev módu)
- **Accessibility smoke test** — Tab/Space/Enter v prohlížeči, DevTools inspektor focus ringu

Pro každou změnu: dev server běží na `http://127.0.0.1:8000/` (`php -S 127.0.0.1:8000 -t public`). Vite build produkuje `public/build/assets/app-*.css` který konzumuje Symfony přes `vite-plugin-symfony`.

---

## Task 1: Přepis FAQ partial markupu

**Files:**
- Modify: `templates/_partials/faq.html.twig` (celý soubor)

- [ ] **Step 1: Přepsat partial na `<details>/<summary>` strukturu**

Nahradit celý obsah souboru `templates/_partials/faq.html.twig` tímto:

```twig
{#
  FAQ sekce — viditelný HTML accordion + FAQPage JSON-LD schema ze stejného datového zdroje.

  Parametry:
    items    – pole objektů { question: string, answer: string }
               answer může obsahovat inline HTML (<a>, <em>, <strong>, <code>)
    heading  – (volitelné) nadpis sekce, default "Časté otázky"
#}
{% set faq_heading = heading|default('Časté otázky') %}

<section class="faq" aria-labelledby="faq-heading">
    <h2 id="faq-heading">{{ faq_heading }}</h2>

    {% for item in items %}
        <details class="faq-item">
            <summary>{{ item.question }}</summary>
            <div class="faq-answer">
                <p>{{ item.answer|raw }}</p>
            </div>
        </details>
    {% endfor %}

    <script type="application/ld+json">
{{ {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    'mainEntity': items|map(item => {
        '@type': 'Question',
        'name': item.question,
        'acceptedAnswer': {
            '@type': 'Answer',
            'text': item.answer
        }
    })
}|json_encode(constant('JSON_UNESCAPED_UNICODE') b-or constant('JSON_UNESCAPED_SLASHES'))|raw }}
    </script>
</section>
```

Klíčové změny oproti původnímu:
- `<article class="faq-item">` → `<details class="faq-item">`
- `<h3>{{ item.question }}</h3>` → `<summary>{{ item.question }}</summary>`
- `<p>{{ item.answer|raw }}</p>` → wrapnuté do `<div class="faq-answer">`
- JSON-LD `<script>` zůstává beze změny

- [ ] **Step 2: Ověřit Twig syntax build**

Run:
```bash
php bin/console cache:clear
php bin/console lint:twig templates/_partials/faq.html.twig
```

Expected: `OK in templates/_partials/faq.html.twig`

---

## Task 2: Přepis CSS FAQ bloku

**Files:**
- Modify: `assets/styles/modern-style.css` (blok „FAQ sekce" ~ř. 213–249)

- [ ] **Step 1: Nahradit FAQ CSS blok**

V souboru `assets/styles/modern-style.css` najít blok začínající `/* ───────── FAQ sekce ───────── */` a končící `.faq-item p:last-child { margin-bottom: 0; }` (~ř. 213–249). Nahradit celý blok tímto:

```css
/* ───────── FAQ sekce ───────── */

.faq { margin: 2.5rem 0; }

.faq-item {
    border-bottom: 1px solid var(--border);
}

.faq-item:first-of-type {
    border-top: 1px solid var(--border);
}

.faq-item summary {
    cursor: pointer;
    padding: 1rem 2rem 1rem 0;
    position: relative;
    font-weight: 600;
    color: var(--text-heading);
    list-style: none;
    transition: color var(--transition);
}

.faq-item summary::-webkit-details-marker { display: none; }

.faq-item summary::after {
    content: "▾";
    position: absolute;
    right: 0.25rem;
    top: 50%;
    transform: translateY(-50%) rotate(-90deg);
    color: var(--color-faq);
    font-size: 0.85em;
    transition: transform var(--transition);
}

.faq-item[open] summary::after {
    transform: translateY(-50%) rotate(0);
}

.faq-item summary:hover {
    color: var(--color-faq);
}

.faq-item summary:focus-visible {
    outline: 2px solid var(--color-faq);
    outline-offset: 2px;
    border-radius: 2px;
}

.faq-answer {
    padding: 0 0 1rem 0;
    color: var(--text-body);
}

.faq-answer p:last-child { margin-bottom: 0; }
```

Klíčové změny:
- Odstraněno: `background: var(--bg-elevated)`, `border-radius`, `padding: 1.25rem 1.5rem`, `border: 1px solid`, `border-left: 4px solid`, pravidlo `.faq-item h3`, pravidlo `.faq-item h3::before` (kruhový "?")
- Přidáno: `border-bottom` / `border-top:first-of-type` layout, summary styling, chevron `::after` s rotací, hover/focus stavy, `.faq-answer` padding

- [ ] **Step 2: Rebuild vite bundle**

Run:
```bash
npx vite build
```

Expected: output bez erroru, v `public/build/manifest.json` aktualizovaný hash pro `app.css` / `app.js`. Čas buildu typicky 2–5 s.

- [ ] **Step 3: Ověřit, že CSS blok obsahuje nová pravidla**

Run:
```bash
grep -c "faq-item summary" public/build/assets/app-*.css
```

Expected: `1` nebo vyšší (obsahuje `faq-item summary`, `faq-item summary::after`, `faq-item summary:hover`, `faq-item summary:focus-visible`).

Run:
```bash
grep -c "border-left: 4px solid" public/build/assets/app-*.css | head -1
```

Expected: pokud vrací `0`, starý fialový left-border byl odstraněn. Pokud vrací vyšší, může jít o jiné pravidlo (OK, dokud to není FAQ).

---

## Task 3: Vizuální verifikace desktop

**Files:** žádné změny, pouze běh serveru a kontrola

- [ ] **Step 1: Spustit PHP dev server (pokud neběží)**

Run:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/
```

Expected: `200` (server běží). Pokud `000` nebo error:
```bash
php -S 127.0.0.1:8000 -t public &
sleep 2
```

- [ ] **Step 2: Screenshot HP desktop (full page)**

V Playwright (MCP) nebo manuálně:
- Resize na 1280×900
- Navigate `http://127.0.0.1:8000/`
- Full-page screenshot do `verify-hp-desktop.png`

Expected: FAQ sekce měří ~250–350 px vertikálně (vše zavřené). Chevron vpravo od každé otázky, border-bottom mezi položkami, žádný fialový left-border, žádný fialový kruh "?".

- [ ] **Step 3: Otevřít 2 FAQ a ověřit interakci**

V prohlížeči kliknout na první FAQ otázku → měla by se rozbalit odpověď a chevron se otočit do `▾`. Kliknout na druhou otázku → také se otevře, první zůstane otevřená (non-exclusive accordion).

Kliknout znovu na první → zavře se.

Expected: všechny tři klikací operace fungují plynule, animace chevronu smooth.

- [ ] **Step 4: Screenshot HP s rozbalenou 1. otázkou**

Otevřít první FAQ, screenshot `verify-hp-desktop-open.png`.

Expected: odpověď vykreslená pod otázkou, žádný horizontální scroll, padding vertikální konzistentní.

- [ ] **Step 5: Screenshot podstránky (`/co-je-ddd`)**

Navigate `http://127.0.0.1:8000/co-je-ddd`, scroll na `.faq`, screenshot `verify-subpage-desktop.png`.

Expected: FAQ sekce konzistentní s HP, všechny 4 otázky zavřené, border-bottom layout, chevron vpravo.

---

## Task 4: Vizuální verifikace mobil

- [ ] **Step 1: Resize na 390×800 (iPhone 14)**

V Playwright MCP nebo DevTools Device Toolbar.

- [ ] **Step 2: Screenshot HP mobil**

Navigate `http://127.0.0.1:8000/`, scroll na `.faq`, screenshot `verify-hp-mobile.png`.

Expected: otázky lámou text na více řádků tak, aby neschovaly chevron. Chevron stále vpravo, nepřekrývá text. Žádný horizontální scroll.

- [ ] **Step 3: Touch target test**

Tab do první otázky, ověřit focus outline (2px fialový, 2px offset). Enter → rozbalí odpověď.

Expected: focus ring viditelný, Enter/Space fungují, touch targety dostatečně velké (~48 px výška summary).

---

## Task 5: SEO a accessibility kontrola

- [ ] **Step 1: Ověřit, že JSON-LD zůstal v DOM**

Run:
```bash
curl -s http://127.0.0.1:8000/ | grep -o 'FAQPage' | head -1
```

Expected: `FAQPage`

Run:
```bash
curl -s http://127.0.0.1:8000/ | python3 -c "
import sys, re, json
html = sys.stdin.read()
match = re.search(r'<script type=\"application/ld\+json\">\s*(\{[^<]*?FAQPage[^<]*?\})\s*</script>', html, re.DOTALL)
if match:
    data = json.loads(match.group(1))
    print(f'Questions: {len(data[\"mainEntity\"])}')
    print(f'First Q: {data[\"mainEntity\"][0][\"name\"]}')
else:
    print('FAQPage JSON-LD not found')
"
```

Expected: `Questions: 5`, `First Q: Kolik času a lidí zavedení DDD v projektu vyžaduje?` (na HP).

- [ ] **Step 2: Ověřit, že text otázek i odpovědí je v DOM i při zavřeném stavu**

Run:
```bash
curl -s http://127.0.0.1:8000/ | grep -c "Pilotní Bounded Context"
```

Expected: `2` nebo vyšší (text odpovědi je v `<p>` uvnitř `<details>` a zároveň v JSON-LD).

- [ ] **Step 3: Schema Markup Validator**

Otevřít v prohlížeči: `https://validator.schema.org/` → vložit URL `http://127.0.0.1:8000/` (pokud není veřejně dostupný, zkopírovat HTML ze `view-source:` a vložit jako code snippet) → spustit validaci.

Expected: `FAQPage` valid, žádné errors. Warnings OK pokud se týkají nepovinných polí (`publisher`, `datePublished` atd.).

Pokud dev server není veřejně dostupný, zaznamenat plán: **„Po merge na produkci spustit validaci na produkční URL."**

- [ ] **Step 4: Klávesnicová navigace a screen reader smoke test**

V prohlížeči (Chrome DevTools > Lighthouse nebo manuálně):
- `Tab` na první summary → focus outline viditelný
- `Space` → rozbalí
- `Shift+Tab` → navigace zpět
- `Tab` → další summary

Expected: celý accordion ovladatelný klávesnicí bez myši.

(Volitelné, pokud je k dispozici) Spustit macOS VoiceOver (`Cmd+F5`) nebo Windows NVDA a navigovat přes FAQ. Expected hlásky: „seskupení uzavřeno/rozbaleno", čte text otázky i odpovědi po rozbalení.

---

## Task 6: Commit a cleanup

- [ ] **Step 1: Review diff**

Run:
```bash
git diff templates/_partials/faq.html.twig assets/styles/modern-style.css
git status
```

Expected:
- `templates/_partials/faq.html.twig` — markup přepsaný na details/summary
- `assets/styles/modern-style.css` — FAQ CSS blok přepsaný
- `public/build/assets/app-*.css` a `public/build/assets/app-*.js` — nové hashované soubory po vite buildu, starý bundle smazán, `manifest.json` aktualizován

- [ ] **Step 2: Staging a commit**

Run:
```bash
git add templates/_partials/faq.html.twig \
         assets/styles/modern-style.css \
         public/build/
git commit -m "refactor: FAQ accordion (details/summary) místo otevřených karet"
```

Expected: commit vytvořen bez hook erroru. (Feedback memory: NE přidávat Co-Authored-By s Claude.)

- [ ] **Step 3: Úklid verify screenshotů**

Verify screenshoty (`verify-*.png`) byly pomocné pro ověření a nemají smysl v repu. Pokud byly vytvořené do repo rootu:

```bash
rm -f verify-hp-desktop.png verify-hp-desktop-open.png verify-subpage-desktop.png verify-hp-mobile.png
```

Expected: soubory odstraněny, `git status` nezobrazuje nové untracked screenshoty v rootu.

- [ ] **Step 4: Finální kontrola**

Run:
```bash
git log --oneline -3
git status
```

Expected: nejnovější commit je `refactor: FAQ accordion...`, working tree clean (kromě existujících untracked souborů mimo scope tohoto plánu).

---

## Self-Review Checklist

- [x] Každý krok má konkrétní kód/příkaz, žádné TODO/TBD
- [x] Cesty k souborům jsou přesné (`templates/_partials/faq.html.twig`, `assets/styles/modern-style.css`)
- [x] Spec coverage:
  - §2 HTML struktura → Task 1 Step 1 ✓
  - §3 CSS → Task 2 Step 1 ✓
  - §4 JSON-LD → Task 5 Steps 1–3 ✓
  - §5 Velikostní dopad → Task 3 Step 2 (expected ~250–350 px) ✓
  - §6 Testování (funkční/vizuální/SEO/a11y/konzole) → Tasks 3, 4, 5 ✓
- [x] Type consistency: třídy `.faq`, `.faq-item`, `.faq-answer` konzistentní mezi Twigem a CSS
- [x] Build step: Task 2 Step 2 (vite build) je explicitní
- [x] Commit strategie: jeden commit bundlující partial + CSS + build output (tightly coupled změny)
