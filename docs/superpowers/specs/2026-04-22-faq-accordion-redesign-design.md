# FAQ accordion redesign — design

**Datum:** 2026-04-22
**Autor:** Michal Katuščák (spec zapsán při brainstormingu s AI asistencí)
**Kontext:** FAQ sekce zavedená dle spec [`2026-04-21-faq-sekce-clanku-design.md`](2026-04-21-faq-sekce-clanku-design.md) byla rozšířena z původních 7 pillar stránek na všechny relevantní stránky průvodce (~17 stránek včetně homepage). Při této expanzi se ukázalo, že současný vizuál — karty s fialovým left-borderem a otevřenými odpověďmi pod sebou — působí na delších stránkách roztaženě a vizuálně nezapadá. Na homepage zabírá FAQ ~944 px (téměř celý viewport) a tlačí ostatní sekce dolů.

---

## 1. Cíl a rozsah

Předělat FAQ partial z otevřených karet na nativní HTML accordion (`<details>/<summary>`), aby se výrazně zmenšila vertikální stopa a sjednotil vizuální tón se zbytkem stránek.

### Co se mění

- `templates/_partials/faq.html.twig` — HTML markup z `<article><h3>+<p>` na `<details><summary>+<div>`
- `assets/styles/modern-style.css`, sekce `/* ───────── FAQ sekce ───────── */` (řádky ~213–249) — kompletní přepis CSS pravidel

### Co zůstává beze změny

- Veřejné rozhraní partial: parametry `items` (pole `{question, answer}`) a `heading` (default `"Časté otázky"`)
- `FAQPage` JSON-LD `<script>` blok — generování je nezávislé na viditelném markupu
- Obsah otázek a odpovědí ve všech šablonách — žádný twig template se neupravuje
- Umístění FAQ sekce ve stránkách (mezi Shrnutí a Další četba, na HP před O autorovi)
- CSS proměnná `--color-faq` zůstává definovaná (může se uplatnit v jemnějším akcentu, viz §3)

### Mimo rozsah

- **Ne** přidávat JS animaci pro plynulé otevírání/zavírání — `<details>` defaultně skáče, animace vyžaduje hack přes `grid-template-rows` nebo Web Animations API a komplikace převažují přínos
- **Ne** dělat exclusive accordion (otevření jedné zavře ostatní) — uživatel může chtít porovnávat dvě sousední odpovědi
- **Ne** měnit obsah otázek/odpovědí
- **Ne** předdefinovat některé položky jako otevřené (`<details open>`) — jednotná logika "vše zavřené" napříč stránkami
- **Ne** odstraňovat `--color-faq` proměnnou — využije se jako jemný akcent (viz §3)

---

## 2. HTML struktura

Partial bude renderovat:

```html
<section class="faq" aria-labelledby="faq-heading">
    <h2 id="faq-heading">Časté otázky</h2>

    <details class="faq-item">
        <summary>Co je Ubiquitous Language?</summary>
        <div class="faq-answer">
            <p>Společný jazyk používaný vývojáři a doménovými experty...</p>
        </div>
    </details>

    <details class="faq-item">
        <summary>Co je Bounded Context?</summary>
        <div class="faq-answer">
            <p>Jasně definovaná hranice, ve které je model platný...</p>
        </div>
    </details>

    <script type="application/ld+json">
    { "@context": "https://schema.org", "@type": "FAQPage", "mainEntity": [...] }
    </script>
</section>
```

### Změny oproti současnému markupu

| Element | Bylo | Bude |
|---|---|---|
| Wrapper Q&A | `<article class="faq-item">` | `<details class="faq-item">` |
| Otázka | `<h3>{{ question }}</h3>` | `<summary>{{ question }}</summary>` |
| Odpověď | `<p>{{ answer }}</p>` (sourozenec h3) | `<div class="faq-answer"><p>{{ answer }}</p></div>` (uvnitř details) |

### Proč `<div class="faq-answer">` wrapper kolem `<p>`

Bez wrapperu by `<p>` byla přímý child `<details>` a bylo by těžší stylovat padding/margin nezávisle na summary. Wrapper navíc umožňuje, aby answer obsahoval víc než jen `<p>` (např. seznamy v budoucnu) bez zásahu do CSS.

### Heading hierarchie

Ztráta `<h3>` u otázky je záměr — `<summary>` má vlastní implicitní roli `button` a není nadpis. Stránky tím neztrácejí žádný h3 v sekci FAQ, který by jinak nesl strukturální význam (otázky nejsou samostatně linkovatelné, neobjevují se v "Na této stránce" navigaci).

### Přístupnost

- `<details>/<summary>` má nativní podporu screen readerů (oznamuje "expanded/collapsed")
- Klávesnicová podpora: `Tab` se zaměří na `<summary>`, `Space`/`Enter` toggle — bez JS
- Focus stav viditelný (viz §3)
- `aria-labelledby` na `<section>` zachován

---

## 3. CSS

Současné pravidla v `assets/styles/modern-style.css` (~ř. 213–249) se nahradí celá. Nový blok:

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

### Klíčové designové volby

- **Žádné karty, jen border-bottom mezi položkami** — výrazně lehčí vizuál, splývá s tokem stránky. `:first-of-type` přidá border-top, takže celá FAQ sekce má nahoře i dole linku.
- **Chevron `▾` jako affordance** — defaultně otočený `-90deg` (ukazuje doprava jako `▸`), při `[open]` se otočí do pozice dolů. Animace přes `var(--transition)`.
- **`--color-faq` zůstává v hover/focus/chevron** — fialová už nedominuje (žádný 4px border-left), ale zachovává se jako jemný akcent identity FAQ sekce.
- **`list-style: none` + `::-webkit-details-marker { display: none }`** — schová default browser šipku/triangle, nahrazujeme vlastním chevronem.
- **`cursor: pointer` na summary** — affordance že je klikatelný.
- **`focus-visible` outline** — dostupný pro klávesnicovou navigaci, neukazuje se při klikání myší.

### Responsivita

Žádná speciální pravidla. Border-bottom layout je 100% šířky a na mobilu funguje automaticky. Padding se nemění mezi desktop/mobil — `1rem` vertikální padding stačí pro touch target (~48 px s line-heightem).

### Kompatibilita

`<details>/<summary>` má 98%+ podporu (caniuse). `::-webkit-details-marker` je legacy WebKit/Blink, `list-style: none` pokrývá ostatní engine. `transform` a `transition` jsou bezpečné napříč prohlížeči.

---

## 4. JSON-LD a SEO

`FAQPage` JSON-LD blok zůstává **beze změny**. Generování ze stejného `items` pole nezávisí na tom, jestli je viditelný markup `<article>` nebo `<details>`.

### Vliv na SEO

- Text v DOM (`<summary>` a `<p>` uvnitř `<details>`) zůstává plně indexovatelný — Google čte i obsah zavřeného `<details>`
- FAQPage rich results v Google SERP nezávisí na otevřenosti — řídí se schématem
- AI crawlery (ChatGPT, Perplexity) text z `<details>` čtou stejně jako z `<article>`

### Validace

Po implementaci ověřit minimálně 2 stránky (jednu kratší, jednu delší) v:
- [Schema Markup Validator](https://validator.schema.org/)
- [Google Rich Results Test](https://search.google.com/test/rich-results)

Očekávání: žádný regres, FAQPage prochází identicky jako před změnou.

---

## 5. Velikostní dopad

| Stránka | FAQ height (před) | FAQ height (po, vše zavřené) | Redukce |
|---|---|---|---|
| `index` (HP) | ~944 px | ~280 px | ~70 % |
| `basic_concepts` (6 otázek) | ~1100 px | ~330 px | ~70 % |
| `what_is_ddd` (4 otázky) | ~750 px | ~210 px | ~72 % |

Hodnoty jsou orientační (závisí na délce otázek/odpovědí), ale řádově se ušetří 600–800 px vertikálního prostoru na každé stránce.

---

## 6. Testování a ověření

### Funkční

- Klik na `<summary>` otevře/zavře odpověď, animace chevronu plynulá
- `Tab` zaměří summary, `Space`/`Enter` toggluje
- Více otevřených najednou je možné
- Focus outline viditelný při klávesnicové navigaci
- Hover stav na otázce mění barvu textu

### Vizuální

- Projít všech ~17 stránek s FAQ na desktopu (1280 px) a mobilu (390 px)
- Ověřit, že FAQ sekce nemá vizuální skok proti okolnímu obsahu
- Na HP ověřit, že "O autorovi" je výrazně blíž "Časté otázky" než dnes
- Žádný horizontální scrollbar na mobilu

### SEO/přístupnost

- Schema Markup Validator — žádné chyby
- Google Rich Results Test — FAQPage rich results valid
- Screen reader (VoiceOver / NVDA) — ohlašuje "expanded/collapsed", čte otázku i odpověď
- Heading hierarchie kontrola — žádný `<h3>` se neztratil tak, aby vznikl skok

### Konzole

- Žádné JS chyby, žádné CSS warnings v devtools u všech testovaných stránek

---

## 7. Migrace existujícího obsahu

Žádná. Všechny stránky používající FAQ jsou v souladu díky partial — přepis partial automaticky propíše změnu na všechny stránky bez nutnosti dotknout se jediné `templates/ddd/*.html.twig`.

---

## 8. Rollback

V případě potřeby se vrátit k předchozí verzi stačí revert commit přepisující partial + CSS. Žádné databázové migrace, žádné asset-build dependencies — vite jen rebuilduje CSS bundle.
