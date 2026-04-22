# Obsahový audit průvodce — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Odstranit formulaické koncové sekce ("Co jsme se naučili", "Zkuste sami", "V další kapitole se podíváme na..."), vyčistit zbývající AI signály a zkrátit duplicitní obsah — aby průvodce vypadal jako autorský odborný text, ne jako šablonový AI výstup.

**Architecture:** Čistě editační práce na Twig šablonách. Žádné nové soubory, žádné změny routování ani PHP kódu. Plán je rozdělen na 4 fáze: (1) odstranění formulaických konců, (2) AI signály, (3) duplicity, (4) drobné konzistenční opravy. Každý commit je jeden soubor nebo logická skupina.

**Tech Stack:** Twig HTML šablony, plain text editing

---

## Fáze 1: Odstranění formulaických konců stránek

Ze všech 15 obsahových kapitol (ne index, about, resources, glossary, security_policy) odstranit:
- Sekci `<section id="shrnuti-kapitoly">` / `<section id="co-jsme-se-naucili">` ("Co jsme se naučili")
- Sekci `<section id="zkuste-sami">` ("Zkuste sami")
- Větu "V další kapitole se podíváme na..." (pokud je samostatný odstavec na konci)

TOC odkazy na tyto sekce (`#shrnuti-kapitoly`, `#zkuste-sami`, `#co-jsme-se-naucili`, `#cviceni`) též smazat.

### Task 1: Odstranit formulaické konce — what_is_ddd.html.twig

**Files:**
- Modify: `templates/ddd/what_is_ddd.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"**

Smazat celý blok `<section id="co-jsme-se-naucili" ...>` včetně obsahu a uzavíracího `</section>`. (Přibližně řádky 458–474.)

- [ ] **Step 2: Smazat sekci "Zkuste sami"**

Smazat celý blok `<section id="zkuste-sami" ...>` včetně obsahu a uzavíracího `</section>`. (Přibližně řádky 476–486.)

- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."**

Smazat odstavec obsahující "V další kapitole se podíváme na základní koncepty DDD" (řádek ~460).

- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**

V TOC na začátku stránky smazat `<li>` položky odkazující na `#co-jsme-se-naucili`, `#shrnuti-kapitoly`, `#zkuste-sami`.

- [ ] **Step 5: Ověřit výsledek**

Otevřít stránku v prohlížeči, ověřit že kapitola končí přirozeně posledním obsahovým odstavcem.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/what_is_ddd.html.twig
git commit -m "refactor: odstranit formulaické konce z what_is_ddd"
```

---

### Task 2: Odstranit formulaické konce — basic_concepts.html.twig

**Files:**
- Modify: `templates/ddd/basic_concepts.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 671–679)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 682–691)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 664)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/basic_concepts.html.twig
git commit -m "refactor: odstranit formulaické konce z basic_concepts"
```

---

### Task 3: Odstranit formulaické konce — horizontal_vs_vertical.html.twig

**Files:**
- Modify: `templates/ddd/horizontal_vs_vertical.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"**
- [ ] **Step 2: Smazat sekci "Zkuste sami"**
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."**
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/horizontal_vs_vertical.html.twig
git commit -m "refactor: odstranit formulaické konce z horizontal_vs_vertical"
```

---

### Task 4: Odstranit formulaické konce — implementation_in_symfony.html.twig

**Files:**
- Modify: `templates/ddd/implementation_in_symfony.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 1446–1456)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 1458–1467)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 1441)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/implementation_in_symfony.html.twig
git commit -m "refactor: odstranit formulaické konce z implementation_in_symfony"
```

---

### Task 5: Odstranit formulaické konce — cqrs.html.twig

**Files:**
- Modify: `templates/ddd/cqrs.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 1715–1726)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 1729–1739)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 1709)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/cqrs.html.twig
git commit -m "refactor: odstranit formulaické konce z cqrs"
```

---

### Task 6: Odstranit formulaické konce — practical_examples.html.twig

**Files:**
- Modify: `templates/ddd/practical_examples.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 925–931)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 934–941)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 919)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/practical_examples.html.twig
git commit -m "refactor: odstranit formulaické konce z practical_examples"
```

---

### Task 7: Odstranit formulaické konce — case_study.html.twig

**Files:**
- Modify: `templates/ddd/case_study.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 851–857)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 860–867)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 846)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/case_study.html.twig
git commit -m "refactor: odstranit formulaické konce z case_study"
```

---

### Task 8: Odstranit formulaické konce — migration_from_crud.html.twig

**Files:**
- Modify: `templates/ddd/migration_from_crud.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 1043–1049)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 1052–1059)
- [ ] **Step 3: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 4: Ověřit v prohlížeči**
- [ ] **Step 5: Commit**

```bash
git add templates/ddd/migration_from_crud.html.twig
git commit -m "refactor: odstranit formulaické konce z migration_from_crud"
```

---

### Task 9: Odstranit formulaické konce — testing_ddd.html.twig

**Files:**
- Modify: `templates/ddd/testing_ddd.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 1181–1188)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 1191–1199)
- [ ] **Step 3: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 4: Ověřit v prohlížeči**
- [ ] **Step 5: Commit**

```bash
git add templates/ddd/testing_ddd.html.twig
git commit -m "refactor: odstranit formulaické konce z testing_ddd"
```

---

### Task 10: Odstranit formulaické konce — event_sourcing.html.twig

**Files:**
- Modify: `templates/ddd/event_sourcing.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 1810–1821)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 1824–1833)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 1802)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/event_sourcing.html.twig
git commit -m "refactor: odstranit formulaické konce z event_sourcing"
```

---

### Task 11: Odstranit formulaické konce — sagas.html.twig

**Files:**
- Modify: `templates/ddd/sagas.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (~řádky 1724–1765)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (~řádky 1772–1802)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 1806)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/sagas.html.twig
git commit -m "refactor: odstranit formulaické konce z sagas"
```

---

### Task 12: Odstranit formulaické konce — ddd_pain_points.html.twig

**Files:**
- Modify: `templates/ddd/ddd_pain_points.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (hledat `id="shrnuti-kapitoly"` nebo `id="co-jsme-se-naucili"`)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (hledat `id="cviceni"` nebo `id="zkuste-sami"`)
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 1597)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/ddd_pain_points.html.twig
git commit -m "refactor: odstranit formulaické konce z ddd_pain_points"
```

---

### Task 13: Odstranit formulaické konce — anti_patterns.html.twig

**Files:**
- Modify: `templates/ddd/anti_patterns.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (hledat `id="shrnuti-kapitoly"`)
- [ ] **Step 2: Smazat sekci "Zkuste sami"** (hledat `id="zkuste-sami"`)
- [ ] **Step 3: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 4: Ověřit v prohlížeči**
- [ ] **Step 5: Commit**

```bash
git add templates/ddd/anti_patterns.html.twig
git commit -m "refactor: odstranit formulaické konce z anti_patterns"
```

---

### Task 14: Odstranit formulaické konce — performance_aspects.html.twig

**Files:**
- Modify: `templates/ddd/performance_aspects.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"**
- [ ] **Step 2: Smazat sekci "Zkuste sami"**
- [ ] **Step 3: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 4: Ověřit v prohlížeči**
- [ ] **Step 5: Commit**

```bash
git add templates/ddd/performance_aspects.html.twig
git commit -m "refactor: odstranit formulaické konce z performance_aspects"
```

---

### Task 15: Odstranit formulaické konce — when_not_to_use_ddd.html.twig

**Files:**
- Modify: `templates/ddd/when_not_to_use_ddd.html.twig`

- [ ] **Step 1: Smazat sekci "Co jsme se naučili"** (hledat `id="shrnuti-kapitoly"` nebo `id="co-jsme-se-naucili"`)
- [ ] **Step 2: Smazat sekci "Zkuste sami"**
- [ ] **Step 3: Smazat odkaz "V další kapitole se podíváme na..."** (~řádek 572)
- [ ] **Step 4: Smazat TOC odkazy na smazané sekce**
- [ ] **Step 5: Ověřit v prohlížeči**
- [ ] **Step 6: Commit**

```bash
git add templates/ddd/when_not_to_use_ddd.html.twig
git commit -m "refactor: odstranit formulaické konce z when_not_to_use_ddd"
```

---

## Fáze 2: AI signály — zbývající výskyty

### Task 16: Vyčistit "zásadní" (7 výskytů)

**Files:**
- Modify: `templates/ddd/implementation_in_symfony.html.twig` — řádek 87: "Směr závislostí je zásadní" → "Směr závislostí je určující"
- Modify: `templates/ddd/when_not_to_use_ddd.html.twig` — řádek 538: "pět zásadních otázek" → "pět otázek"
- Modify: `templates/ddd/event_sourcing.html.twig` — řádek 426: "zásadní roli" → "hlavní roli"; řádek 1497: "zásadní problém" → "problém"; řádek 1785: "zásadní zvýšení" → "výrazné zvýšení"
- Modify: `templates/ddd/sagas.html.twig` — řádek 437: "zásadní limity" → "praktické limity"; řádek 695: "zásadních výhod" → "praktických výhod"

- [ ] **Step 1: Nahradit všech 7 výskytů** (přečíst kontext každého a zvolit přirozený český ekvivalent)
- [ ] **Step 2: Ověřit grepu `zásadní` vrací 0 výskytů**
- [ ] **Step 3: Commit**

```bash
git add templates/ddd/implementation_in_symfony.html.twig templates/ddd/when_not_to_use_ddd.html.twig templates/ddd/event_sourcing.html.twig templates/ddd/sagas.html.twig
git commit -m "fix: nahradit AI signál 'zásadní' přirozenějšími výrazy"
```

---

### Task 17: Zredukovat "klíčový" (28 výskytů → max 8)

**Files:**
- Modify: Soubory dle grepu — `testing_ddd` (4×), `glossary` (5×), `event_sourcing` (4×), `ddd_ai` (3×), `what_is_ddd` (2×), `cqrs` (2×), `when_not_to_use_ddd` (2×), `case_study` (2×), `performance_aspects` (1×), `migration_from_crud` (1×), `index` (1×), `sagas` (1×)

- [ ] **Step 1: Pro každý soubor přečíst kontext výskytu**
- [ ] **Step 2: Ponechat "klíčový" jen tam, kde je skutečně přesný (max 1× na soubor, celkem max 8)**
- [ ] **Step 3: U ostatních nahradit kontextově vhodným výrazem** (např. "hlavní", "podstatný", "určující", "stěžejní", nebo přeformulovat větu)
- [ ] **Step 4: Ověřit grep**
- [ ] **Step 5: Commit**

```bash
git commit -am "fix: zredukovat nadužívání 'klíčový' napříč průvodcem"
```

---

### Task 18: Zredukovat "v rámci" (20 výskytů → max 5)

**Files:**
- Modify: `performance_aspects` (3×), `implementation_in_symfony` (3×), `sagas` (3×), `event_sourcing` (2×), `ddd_pain_points` (2×), `glossary` (2×), `horizontal_vs_vertical` (1×), `what_is_ddd` (1×), `cqrs` (1×), `case_study` (1×), `anti_patterns` (1×)

- [ ] **Step 1: Pro každý výskyt přečíst kontext**
- [ ] **Step 2: Nahradit kde lze** — "v rámci projektu" → "v projektu", "v rámci kontextu" → "v kontextu", "v rámci týmu" → "v týmu"
- [ ] **Step 3: Ponechat jen tam, kde "v rámci" nese skutečný význam ohraničení**
- [ ] **Step 4: Ověřit grep**
- [ ] **Step 5: Commit**

```bash
git commit -am "fix: nahradit 'v rámci' jednodušší předložkou kde to jde"
```

---

### Task 19: Zredukovat "efektivní/efektivně" (16 výskytů → max 5)

**Files:**
- Modify: `event_sourcing` (5×), `performance_aspects` (3×), `cqrs` (3×), `sagas` (1×), `migration_from_crud` (1×), `when_not_to_use_ddd` (1×), `glossary` (1×), `ddd_ai` (1×)

- [ ] **Step 1: Pro každý výskyt přečíst kontext**
- [ ] **Step 2: Nahradit kde "efektivní" je jen AI výplň bez obsahu** — buď smazat, nebo nahradit konkrétním tvrzením (např. "efektivní dotazování" → "rychlé dotazování" nebo prostě "dotazování")
- [ ] **Step 3: Ponechat kde výraz nese skutečnou informaci**
- [ ] **Step 4: Ověřit grep**
- [ ] **Step 5: Commit**

```bash
git commit -am "fix: zredukovat nadužívání 'efektivní' napříč průvodcem"
```

---

### Task 20: Opravit zbývající em dash (2 výskyty)

**Files:**
- Modify: `templates/ddd/implementation_in_symfony.html.twig` (1×)
- Modify: `templates/ddd/what_is_ddd.html.twig` (1×)

- [ ] **Step 1: Najít výskyty `—` (em dash)**
- [ ] **Step 2: Nahradit za ` – ` (en dash s mezerami) nebo přeformulovat**
- [ ] **Step 3: Commit**

```bash
git commit -am "fix: nahradit zbývající em dash za en pomlčku"
```

---

### Task 21: Opravit frázi "robustnějšímu a spolehlivějšímu systému" v case_study

**Files:**
- Modify: `templates/ddd/case_study.html.twig` — řádek 841

- [ ] **Step 1: Přečíst kontext**
- [ ] **Step 2: Přeformulovat** — nahradit obecnou chválu konkrétním tvrzením (např. "vedl k systému s vyšším pokrytím testy a snadnější údržbou")
- [ ] **Step 3: Commit**

```bash
git add templates/ddd/case_study.html.twig
git commit -m "fix: nahradit AI frázi 'robustnější a spolehlivější' v case_study"
```

---

## Fáze 3: Duplicity a konzistence

### Task 22: Zkrátit duplicitní sekci "Saga / Process Manager" v CQRS

**Files:**
- Modify: `templates/ddd/cqrs.html.twig` — sekce "Saga / Process Manager" (~řádky 1679–1712)

- [ ] **Step 1: Přečíst celou sekci**
- [ ] **Step 2: Zkrátit na 2–3 věty + odkaz na samostatnou kapitolu** — smazat detailní popis, ponechat jen krátký úvod proč ságy souvisí s CQRS a odkaz `{{ path('sagas') }}`
- [ ] **Step 3: Ověřit v prohlížeči**
- [ ] **Step 4: Commit**

```bash
git add templates/ddd/cqrs.html.twig
git commit -m "refactor: zkrátit duplicitní sekci o ságách v CQRS kapitole"
```

---

### Task 23: Ověřit banner "živá ukázka kódu"

**Files:**
- Check: Které soubory mají banner odkaz na GitHub repo a zda repo existuje

- [ ] **Step 1: Grep `živá ukázka` ve všech šablonách**
- [ ] **Step 2: Ověřit zda cílové URL existuje**
- [ ] **Step 3: Pokud repo neexistuje, smazat bannery ze všech souborů a commitnout**
- [ ] **Step 4: Pokud repo existuje, přidat banner do chybějících kódových kapitol**
- [ ] **Step 5: Commit**

```bash
git commit -am "fix: sjednotit banner živé ukázky kódu"
```

---

## Fáze 4: Finální ověření

### Task 24: Celkový grep audit

- [ ] **Step 1: Ověřit že grep na tyto vzory vrací přijatelné počty:**
  - `zásadní` → 0
  - `klíčov` → max 8
  - `v rámci` → max 5
  - `efektivn` → max 5
  - `se podíváme na` → max 3 (uprostřed textu, ne formulaický konec)
  - `Co jsme se naučili` → 0
  - `Zkuste sami` → 0

- [ ] **Step 2: Projít stránky v prohlížeči a ověřit že konce kapitol vypadají přirozeně**

- [ ] **Step 3: Commit pokud jsou potřeba další drobné opravy**
