# FAQ sekce na pillar stránkách — design

**Datum:** 2026-04-21
**Autor:** Michal Katuščák (spec zapsán při brainstormingu s AI asistencí)
**Kontext:** AI crawlery (ChatGPT, Perplexity, Gemini) ve článcích vyhledávají FAQ jako strukturovanou jednotku. Cílem je přidat FAQ sekci na hlavní („pillar") stránky průvodce tak, aby vznikl silný AI signál a zároveň hodnota pro čtenáře, ale beze změny tónu průvodce.

---

## 1. Cíl a rozsah

Přidat FAQ sekci (4–6 otázek) na sedm hlavních stránek průvodce. FAQ bude rekapitulační (shrnuje klíčové body článku, nepřidává nová tvrzení), vizuálně konzistentní s existujícím callout systémem a doprovodí ji `FAQPage` JSON-LD schema pro strukturované rozpoznání AI crawlery i Googlem.

### Vybrané stránky (pillar pages)

1. `what_is_ddd` — 4 otázky
2. `basic_concepts` — 6 otázek
3. `cqrs` — 5 otázek
4. `event_sourcing` — 6 otázek
5. `when_not_to_use_ddd` — 4 otázky
6. `ddd_ai` — 4 otázky
7. `migration_from_crud` — 5 otázek

Celkem ~34 Q&A. Konkrétní znění jednotlivých otázek není součástí tohoto spec — zpracuje se během implementace kapitoly po kapitole. Spec definuje strukturu, vzhled, umístění a obsahovou strategii.

### Mimo rozsah

- **Ne** přidávat FAQ na zbývajících 15 stránek (glosář, zdroje, security-policy, about, rozcestník atd.)
- **Ne** měnit existující `TechArticle` JSON-LD schema
- **Ne** přidávat accordion / rozbalovací UI — plain heading + odstavec v kartách
- **Ne** generovat FAQ programově z obsahu článku — ručně psané, rekapitulační
- **Ne** upravovat `llms.txt` — FAQ jsou detail implementace stránky, ne samostatný dokument

---

## 2. Architektura

### Reusable Twig partial

Nový soubor: `templates/_partials/faq.html.twig`

Partial přijímá parametry:

- `items` (povinné) — pole objektů `{ question: string, answer: string }`
- `heading` (volitelné) — nadpis sekce, default `"Časté otázky"`

Partial ze stejného `items` pole vygeneruje **zároveň**:

1. Viditelný HTML (sekce s kartami Q&A)
2. `FAQPage` JSON-LD `<script>` uvnitř sekce

Jeden zdroj pravdy → viditelný text a schema se nemůžou rozejít (Googlův striktní požadavek).

### Použití v šabloně

```twig
{% include '_partials/faq.html.twig' with {
    items: [
        { question: 'Co je Ubiquitous Language?', answer: 'Společný jazyk...' },
        { question: 'Co je Bounded Context?',    answer: 'Jasně definovaná hranice...' }
    ]
} %}
```

Umístění: **mezi `<section id="summary">` a `<section id="further-reading">`** v každé ze sedmi šablon. Přirozený závěrečný flow: shrnutí → FAQ → kam dál.

---

## 3. HTML struktura

Partial renderuje:

```html
<section class="faq" aria-labelledby="faq-heading">
    <h2 id="faq-heading">Časté otázky</h2>

    <article class="faq-item">
        <h3>Co je Ubiquitous Language?</h3>
        <p>Společný jazyk používaný vývojáři a doménovými experty...</p>
    </article>

    <article class="faq-item">
        <h3>Co je Bounded Context?</h3>
        <p>Jasně definovaná hranice, ve které je model platný...</p>
    </article>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "Co je Ubiquitous Language?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Společný jazyk používaný vývojáři a doménovými experty..."
          }
        }
      ]
    }
    </script>
</section>
```

### Přístupnost

- `<section aria-labelledby="faq-heading">` s explicitním `<h2 id="faq-heading">`
- Každá Q&A jako `<article>` (samostatně čitelná jednotka)
- Ikona otazníku přes CSS `::before` s prázdným alternativním textem (`content: "❓" / ""`) — screen readery ikonu ignorují, slyší jen text otázky
- Heading hierarchie: `<h2>` FAQ zapadá za `<h2>` Shrnutí, `<h3>` otázky korektně vnořené

### Escapování

Obsah `question` a `answer` se v JSON-LD bloku escapuje filtrem `|escape('js')`, aby uvozovky/zalomení neporušily JSON. Ve viditelném HTML se escapuje standardním `|escape('html')` (Twig default).

---

## 4. Vizuální design a CSS

### Accent barva

Přidat do `:root` v `assets/styles/modern-style.css`:

```css
--color-faq: #8b5cf6;  /* fialová */
```

Záměrně se nekryje s existujícími akcenty:
- `.note` — modrá (var(--color-accent))
- `.tip` — zelená `#22c55e`
- `.warning` — oranžová `#f59e0b`
- `.caution` — červená `#ef4444`
- `.faq` — fialová `#8b5cf6` (nové)

### CSS pravidla

Přidat do `assets/styles/modern-style.css` (blízko callout sekce, ~ř. 164):

```css
.faq { margin: 2.5rem 0; }

.faq-item {
    background: var(--bg-elevated);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    margin: 1rem 0;
    border: 1px solid var(--border);
    border-left: 4px solid var(--color-faq);
}

.faq-item h3 {
    margin-top: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-heading);
}

.faq-item h3::before {
    content: "\2753" / "";  /* ❓ */
    margin-right: 0.4rem;
    color: var(--color-faq);
    font-size: 0.95em;
}

.faq-item p:last-child { margin-bottom: 0; }
```

### Proč to zapadne

Identické stavební kameny s existujícími callouty (`.note`, `.tip`, `.warning`, `.caution`):
- stejné pozadí (`var(--bg-elevated)`)
- stejný border radius (`var(--radius)`)
- stejný subtilní border (`1px solid var(--border)`)
- stejný barevný levý border (`4px solid <accent>`)
- stejný vzor `::before` ikony u `h3`

Jediné odlišnosti: fialový akcent + otazník + uspořádání do série karet v rámci jedné sekce. Čtenář, který zná průvodce, okamžitě rozpozná „další typ upozornění", a otazník + kontext („Časté otázky") jednoznačně identifikují FAQ.

### Responsivita

Žádná speciální pravidla. Karty jsou 100% šířky, chovají se jako ostatní callouty, na mobilu fungují automaticky.

---

## 5. JSON-LD FAQPage schema

### Umístění

JSON-LD `<script>` je součástí partial a vykreslí se **uvnitř `<section class="faq">`** přímo na stránce. Není obsažen v Twig bloku `{% block structured_data %}`, protože ten už obsahuje `TechArticle` schema.

Multiple JSON-LD `<script>` bloků na jedné stránce je validní a Googlem podporovaný vzor.

### Struktura

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "<text otázky>",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "<text odpovědi>"
      }
    }
  ]
}
```

### Požadavek shody

Text v `acceptedAnswer.text` musí 1:1 odpovídat viditelnému textu v `<p>` pod otázkou. Tento požadavek je Googlovým guideline pro FAQPage rich results a partial ho splňuje konstrukcí (render ze stejné proměnné `items[].answer`).

---

## 6. Obsahová strategie

### Charakter FAQ

**Rekapitulační** — otázky shrnují klíčové body článku, odpovědi čerpají z existujícího textu. Nepřinášejí nová tvrzení, nepřidávají nové koncepty mimo rozsah článku.

### Formulace otázek

- Přirozený jazyk, jak by dotaz zadal hledající: „Co je...", „Jaký je rozdíl mezi X a Y?", „Kdy použít...", „Proč...", „Jak..."
- Ne akademické/formální: vyhnout se frázím typu „V kontextu X se ptáme..."
- Konkrétní, ne vágní: místo „Co je DDD?" použít například „Co je Ubiquitous Language?" (pokud to sedí k tématu článku)

### Formulace odpovědí

- 2–4 věty
- Akademický tón průvodce (vykání, bez osobních komentářů, bez AI signálů)
- Konzistentní se stylem zbytku průvodce — pravidla z memory (`feedback-tonalita.md`, `feedback-ai-signaly.md`) se uplatňují i na FAQ
- Volitelně odkaz na detailnější sekci článku (`<a href="#bounded-context">více v sekci o Bounded Contextech</a>`), pokud to přirozeně navazuje
- Odpověď musí dávat smysl samostatně (bez toho, že by si čtenář přečetl článek) — AI crawler cituje odpověď izolovaně

### Počet Q&A per stránka

Variabilní 4–6 podle obsahu, ne mechanicky:

| Stránka | Počet otázek |
|---|---|
| `what_is_ddd` | 4 |
| `basic_concepts` | 6 |
| `cqrs` | 5 |
| `event_sourcing` | 6 |
| `when_not_to_use_ddd` | 4 |
| `ddd_ai` | 4 |
| `migration_from_crud` | 5 |

---

## 7. Testování a ověření

- **Vizuální kontrola** — `symfony server:start`, projít všech 7 stránek v prohlížeči, ověřit vzhled FAQ sekce na desktop i mobilu. Kontrola, že FAQ zapadá mezi Shrnutí a Další četba bez vizuálního skoku.
- **JSON-LD validace** — otestovat minimálně 2 stránky (jednu kratší, jednu delší) v:
  - [Schema Markup Validator](https://validator.schema.org/)
  - [Google Rich Results Test](https://search.google.com/test/rich-results)
- **Shoda viditelný vs. JSON-LD** — Google Rich Results Test tuto kontrolu provádí automaticky (pokud FAQPage neprojde validací rich results, obsah se nezapisuje 1:1)
- **Přístupnost** — manuální kontrola tab orderu, screen reader průchodu (VoiceOver / NVDA), heading hierarchie
- **Žádné JS chyby v konzoli** — síťová/JS konzole čistá u všech 7 stránek

---

## 8. Rozšíření v budoucnu

Spec řeší 7 pillar stránek. Pokud se později rozhodne přidat FAQ na další stránky, stačí do dané šablony vložit `{% include '_partials/faq.html.twig' with { items: [...] } %}` mezi Shrnutí a Další četba. Žádná další změna architektury nebo CSS už není potřeba.
