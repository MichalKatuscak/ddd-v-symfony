# Design+UX audit a vlnové opravy – návrh řešení

**Datum:** 2026-04-28
**Stav:** schváleno uživatelem 2026-04-28
**Návaznost:** doplňuje publikační audit `docs/AUDIT_2026-04-27.md` o dimenze, které tam nebyly pokryty do hloubky.

## Cíl

Před prvním veřejným spuštěním průvodce projít web optikou hloubkového design+UX auditu, najít nekonzistence napříč 25 šablonami a 8 CSS soubory, a v sériích malých commitů je opravit. Výsledkem má být web, který v očích první návštěvy nepůsobí jako nedotažený rozhonění obsahu, ale jako jeden konzistentní produkt.

## Rozsah

**Pokryté dimenze:**

- **D – Vizuální konzistence** komponent (callout, code-block, FAQ, tabulka, blockquote, list, diagram, figure) napříč všemi 25 šablonami.
- **T – Typografie a rytmus** – stupnice nadpisů, line-height, weight, vertikální mezery, line-length, citace, popisky.
- **S – Spacing systém** – token vs. hardcode, `gap` / `padding` / `margin` konzistence, breakpointy.
- **I – Interakce a stavy** – hover / focus-visible / active / disabled napříč clickable prvky.
- **M – Mobilní detaily** – 320–540 px, touch-target ≥ 44 px, scroll a sticky chování.
- **N – Navigace a IA-flow** – cross-linking mezi kapitolami, „další kapitola", glosář linking, breadcrumb konzistence.
- **C – Komponenty a chrome** – figure / caption / label v diagramech, code-block toolbar, FAQ accordion, featured karty.

**Záměrně NEpokryté (uzamčeno):**

- Dark mode jako jediná varianta.
- Barevná paleta a accent v `tokens.css`.
- Volba písma a typografická stupnice v `fonts.css`.
- IA: 4 huby + 16 kapitol + glosář + about + resources + security_policy.
- Landing layout (Variant A: TOC-as-Hero + reading paths + featured).
- Slogan a hlavní headline kopie.
- Struktura breadcrumbs (dvouúrovňová, záměr z 27.4. auditu).
- Bullet listy, FAQ accordion princip, callout typy, code-block chrome princip – formátové konvence.

## Architektura procesu

```
Audit (čtení kódu + Playwright)
        |
        v
docs/AUDIT_2026-04-28-design-ux.md  (P0 / P1 / P2)
        |
        v
Triáž a plán vln (sekce dokumentu)
        |
        v
Vlny 1..N: oprava -> verifikace -> commit -> zápis stavu
        |
        v
Závěrečná verifikace (lint, smoke render, JSON-LD, screenshot diff)
```

## Komponenty (Twig partials)

Audit cílí primárně na konzistenci výstupu těchto partials přes všechny stránky, kde se používají:

- `_partials/article_head.html.twig`
- `_partials/article_meta.html.twig`
- `_partials/article_toc.html.twig`
- `_partials/callout.html.twig`
- `_partials/code_block.html.twig`
- `_partials/diagram.html.twig`
- `_partials/faq.html.twig`
- `_partials/github_examples.html.twig`
- `_partials/hub.html.twig`

Pro každý partial v auditu uvedu (a) zda jeho výstup vypadá identicky napříč konzumenty, (b) jaké jsou odchylky.

## Datový tok auditu

1. **Sběr** – pro každou ze 7 dimenzí:
   - čtení relevantního CSS a šablon,
   - Playwright snapshot na 390 / 768 / 1280 px klíčových stránek (homepage, hub, kapitola, glosář, about, resources, security-policy),
   - porovnání skutečného renderu s očekávanou konzistencí.
2. **Klasifikace** – každá nalezená položka dostane kód `<dimenze>-P<severita>-<index>` (např. `D-P1-3`).
3. **Severita:**
   - **P0** publikační blokátor (nelze pustit ven – jasně rozbité, viditelné na první pohled, nekonzistence narušující čitelnost nebo přístupnost).
   - **P1** důležité (sníží kvalitu vůči seniornímu publiku, ale není ostuda).
   - **P2** leštění.
4. **Triáž a vlny** – seskupení do logických commit-velikostí.

## Vlnová strategie

### Předběžné rozdělení

Konkrétní seznam vln se finalizuje až po auditu. Pravděpodobné rozdělení:

- **Vlna 1** – auto-fix triviálních (D-P0 + jednoduchá P1 mechanika).
- **Vlna 2** – vizuální konzistence komponent (callout, code-block, FAQ, tabulka, list, blockquote).
- **Vlna 3** – typografie a vertikální rytmus.
- **Vlna 4** – mobilní polish + interakce (hover/focus/active, touch-target, sticky).
- **Vlna 5** – cross-link / IA flow (chapter-to-chapter navigace, glosář linking).
- **Vlna 6** – diagram chrome (figure, caption, label, scroll hint).
- **Vlna 7** – závěrečná verifikace a publikační check.

### Auto-fix scope (bez schvalování per item)

Bez schválení opravím:

- CSS typo, mrtvá pravidla, duplicity v selektorech.
- Chybějící `aria-label`, `aria-hidden`, `role` u jasně dekorativních prvků.
- Single-occurrence inkonzistence (1 šablona se odchýlila od konvence ostatních).
- ASCII vs. typografické znaky v `<title>`, `alt`, `aria-label`, `meta`.
- Drobnosti `text-decoration`, `letter-spacing`, `font-feature-settings`.

Schválení vyžadují:

- Změny layoutu, hierarchie, vizuálního vzhledu komponent.
- Změny barev, fontů, breakpointů.
- Změny IA – přidání nebo odebrání odkazů, sekcí, partialů.
- Refaktor partialu (sjednocení API mezi konzumenty).
- Cokoliv, co viditelně přepíše renderovaný výstup více než jedné stránky.

## Verifikace

Po každé vlně:

- `php bin/console lint:twig templates/` – syntaktická kontrola Twigu.
- `php bin/console lint:container --env=prod` – kontejner.
- Smoke render přes vestavěný PHP server – `/`, `/zaklady`, jedna kapitola, glosář, about.
- U vln 2–5 dodatečně Playwright screenshot diff klíčové komponenty na 390 / 768 / 1280 px.

Před vlnami 2–5 (invazivní) udělám smoke render a screenshoty `before`. Pokud se `after` vizuálně liší od záměru, vlna se zastaví a problém se vyřeší před commitem.

## Riziko a rollback

Každá vlna = samostatný commit. Při problému `git revert <hash>` vrátí jen příslušnou vlnu. Audit dokument vede stav (✓ / ⊘ / odloženo) per položka, takže je vždy zřetelné, co je v aktuálním HEAD a co ne.

## Tonalita a typografie obsahu

Při dotyku obsahu (texty v šablonách) platí konvence projektu:

- Vykání, akademický tón, „zde" nikoli „tady".
- Slovo „průvodce" pro celý web.
- Em dash (`—`) → en pomlčka s mezerami (` – `) nebo přeformulování.
- ASCII uvozovky `"` → české `„"` (U+201E + U+201C).
- ASCII pomlčka v próze → en pomlčka s mezerami; ASCII pomlčka v kódu zůstává.
- Bez AI fillerů: „klíčový" v shlucích, „v rámci" tam kde stačí „v" / „při" / „pro", „je důležité si uvědomit".

## Definition of Done

- Audit dokument existuje, má všechny dimenze pokryté, položky mají severitu, stav po realizaci je zapsán per položka.
- Všechny P0 vyřízeny.
- Všechny P1 vyřízeny nebo explicitně odloženy s odůvodněním.
- P2 dle časových možností a se zdůvodněným odložením zbytku.
- Lint, container a smoke render zelené.
- Žádný neuložený diff v pracovním stromu kromě auditovacích screenshotů.
