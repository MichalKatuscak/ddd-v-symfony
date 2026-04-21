# FAQ sekce na pillar stránkách — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Přidat reusable FAQ Twig partial a doplnit FAQ sekci (4–6 otázek) na sedm pillar stránek průvodce, včetně `FAQPage` JSON-LD schema.

**Architecture:** Jeden Twig partial generuje ze stejného `items` pole viditelný HTML i JSON-LD schema (jeden zdroj pravdy). Každá pillar stránka partial includuje mezi „Shrnutí" a „Další četba". CSS přidává novou callout variantu `.faq` konzistentní s existujícími `.note/.tip/.warning/.caution`.

**Tech Stack:** Symfony 8, Twig 3.24 (arrow functions + `|map`), Vite 8 pro CSS pipeline.

**Reference:** Spec — `docs/superpowers/specs/2026-04-21-faq-sekce-clanku-design.md`.

---

## Předpoklady

Tento projekt **nemá testovací sadu**. Verifikace u každého úkolu probíhá:

- `php bin/console cache:clear` — kontrola Twig syntaxe (zkompiluje šablony).
- `npm run build` — kontrola CSS/JS buildu pro produkci.
- `symfony server:start` + vizuální kontrola v prohlížeči.
- Ruční validace JSON-LD přes [validator.schema.org](https://validator.schema.org/) a [Google Rich Results Test](https://search.google.com/test/rich-results) (v Tasku 10).

Commity **nepřidávají `Co-Authored-By` hlavičku** (projektové pravidlo).

---

## Task 1: Vytvořit Twig partial `faq.html.twig`

**Files:**
- Create: `templates/_partials/faq.html.twig`

- [ ] **Step 1: Vytvořit soubor s kompletním partial**

Obsah souboru `templates/_partials/faq.html.twig`:

```twig
{#
  FAQ sekce — viditelný HTML + FAQPage JSON-LD schema ze stejného datového zdroje.

  Parametry:
    items    – pole objektů { question: string, answer: string }
               answer může obsahovat inline HTML (<a>, <em>, <strong>, <code>)
    heading  – (volitelné) nadpis sekce, default "Časté otázky"
#}
{% set faq_heading = heading|default('Časté otázky') %}

<section class="faq" aria-labelledby="faq-heading">
    <h2 id="faq-heading">{{ faq_heading }}</h2>

    {% for item in items %}
        <article class="faq-item">
            <h3>{{ item.question }}</h3>
            <p>{{ item.answer|raw }}</p>
        </article>
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

Klíčové detaily:
- `|raw` u `item.answer` — povoluje inline HTML v odpovědích (odkazy do článku).
- `items|map(item => {...})` — Twig 3.2+ arrow function, generuje `mainEntity` pole pro JSON-LD.
- `|json_encode(...)|raw` — validní JSON s českými znaky (JSON_UNESCAPED_UNICODE) a čitelnými lomítky (JSON_UNESCAPED_SLASHES). Bez `|raw` by Twig řetězec znovu escapoval pro HTML.
- `aria-labelledby` + explicitní `id` u h2 — přístupnost.

- [ ] **Step 2: Ověřit Twig syntaxi**

Run:
```bash
php bin/console cache:clear
```

Expected: `[OK] Cache for the "dev" environment (debug=true) was successfully cleared.` Bez chyb.

Pokud Twig hlásí parse error (např. chybná arrow function syntax), zkontrolovat verzi Twig (`composer show twig/twig` — musí být >= 3.2) a případně přepsat na explicitní `{% for %}` smyčku pro `mainEntity`.

- [ ] **Step 3: Commit**

```bash
git add templates/_partials/faq.html.twig
git commit -m "feat: Twig partial pro FAQ sekci s JSON-LD schema"
```

---

## Task 2: Přidat CSS pro FAQ komponentu

**Files:**
- Modify: `assets/styles/modern-style.css`

- [ ] **Step 1: Přidat `--color-faq` do `:root`**

V `assets/styles/modern-style.css` najít `:root { ... }` (na začátku souboru, definice CSS proměnných) a přidat na konec seznamu proměnných:

```css
--color-faq: #8b5cf6;  /* fialová — FAQ callout akcent, odlišený od note/tip/warning/caution */
```

- [ ] **Step 2: Přidat `.faq` a `.faq-item` pravidla**

V `assets/styles/modern-style.css` najít sekci s pravidly pro `.note`, `.tip`, `.warning`, `.caution` (okolo řádku 164). **Bezprostředně pod bloky pro tyto callouty** (tj. za blok `.caution h2::before, .caution h3::before, .caution h4::before { ... }` na ř. ~187 a za `.caution { border-left: 4px solid #ef4444; }` na ř. ~192) přidat nový blok:

```css
/* ───────── FAQ sekce ───────── */

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
    content: "\2753" / "";  /* ❓ — prázdný alt, screen reader ignoruje */
    margin-right: 0.4rem;
    color: var(--color-faq);
    font-size: 0.95em;
}

.faq-item p:last-child { margin-bottom: 0; }
```

- [ ] **Step 3: Ověřit build**

Run:
```bash
npm run build
```

Expected: Vite skončí úspěšně (vypíše „built in Xs"), bez CSS chyb.

- [ ] **Step 4: Commit**

```bash
git add assets/styles/modern-style.css
git commit -m "feat: CSS pro FAQ callout variantu (fialový akcent, otazník)"
```

---

## Task 3: FAQ na stránce „Co je DDD" (4 otázky)

**Files:**
- Modify: `templates/ddd/what_is_ddd.html.twig`

Sekce k pokrytí (z ToC): Definice DDD, Ubiquitous Language, Bounded Context, Kdy DDD nepoužívat.

- [ ] **Step 1: Napsat 4 Q&A podle strategie spec § 6**

Otevřít `templates/ddd/what_is_ddd.html.twig`, přečíst sekce `#definition`, `#strategic-design`, `#ddd-vs-other`, `#challenges`. Pro každou odvodit jednu Q&A:

- Otázka v přirozeném jazyce hledajícího („Co je...", „Jaký je rozdíl...", „Kdy použít...").
- Odpověď 2–4 věty, akademický tón (vykání, bez osobních komentářů, bez AI signálů — viz memory `feedback-tonalita.md`, `feedback-ai-signaly.md`).
- Odpověď vychází z existujícího textu článku, nepřidává nová tvrzení.
- Volitelně odkaz do sekce článku přes `<a href="#anchor">...</a>`.

Vzorová Q&A (pro ilustraci, finální verze může znít jinak):

```
Otázka: Co je Ubiquitous Language v DDD?
Odpověď: Ubiquitous Language je společný jazyk používaný vývojáři a doménovými experty při návrhu i implementaci systému. Eliminuje nedorozumění tím, že všichni účastníci projektu pojmenovávají doménové koncepty stejně — od byznys dokumentace přes rozhovory až po kód. Viz <a href="#strategic-design">sekce o strategickém designu</a>.
```

- [ ] **Step 2: Najít vkládací pozici**

Run:
```bash
grep -n 'id="summary"\|id="further-reading"' templates/ddd/what_is_ddd.html.twig
```

Expected výstup (řádky ~437 a ~460):
```
437:        <section id="summary" aria-labelledby="summary-heading">
460:        <section id="further-reading" aria-labelledby="further-reading-heading">
```

Include se vloží **mezi uzavírající `</section>` sekce `summary` a otevírající `<section id="further-reading">`** (tj. okolo ř. 459).

- [ ] **Step 3: Vložit include s items**

Editovat soubor. Za uzavírající `</section>` sekce summary a před `<section id="further-reading">` vložit:

```twig
        {% include '_partials/faq.html.twig' with {
            items: [
                {
                    question: 'Co je Ubiquitous Language v DDD?',
                    answer: 'Ubiquitous Language je společný jazyk... <a href="#strategic-design">více ve strategickém designu</a>.'
                },
                {
                    question: '<druhá otázka — podle Bounded Context>',
                    answer: '<druhá odpověď>'
                },
                {
                    question: '<třetí otázka — porovnání DDD vs. CRUD/hexagonální/mikroservisy>',
                    answer: '<třetí odpověď>'
                },
                {
                    question: '<čtvrtá otázka — kdy DDD nepoužívat>',
                    answer: '<čtvrtá odpověď>'
                }
            ]
        } %}

```

Placeholdery `<...>` jsou pro Step 1 — implementátor vyplní konkrétní text z výstupu Step 1 přímo sem. Finální soubor **nesmí** placeholdery obsahovat.

- [ ] **Step 4: Ověřit Twig syntaxi a build**

Run:
```bash
php bin/console cache:clear && npm run build
```

Expected: Obojí úspěšně, bez chyb.

- [ ] **Step 5: Vizuální kontrola v prohlížeči**

Run:
```bash
symfony server:start
```

(V jiném terminálu) `npm run dev` pro hot reload CSS.

Otevřít `http://localhost:8000/co-je-ddd`, odscrollovat na konec článku. Ověřit:
- FAQ sekce se zobrazí mezi „Shrnutí" a „Další četba".
- Každá otázka má fialový levý border, ikonu ❓, nadpis jako v `.note` callout.
- Odpovědi čitelné, případné `<a>` odkazy fungují.
- V View Source (`Ctrl+U`) je přítomen `<script type="application/ld+json">` s FAQPage schema, obsahuje všechny 4 otázky, české znaky nejsou escapované.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/what_is_ddd.html.twig
git commit -m "feat: FAQ sekce na stránce Co je DDD (4 otázky)"
```

---

## Task 4: FAQ na stránce „Základní koncepty" (6 otázek)

**Files:**
- Modify: `templates/ddd/basic_concepts.html.twig`

Sekce k pokrytí (z ToC): Bounded Contexts, Ubiquitous Language, Entities, Value Objects, Aggregates, Repositories. Doménové služby a události lze případně sloučit do jedné Q&A pro 6. slot; preferovat však 6 samostatných konceptů.

- [ ] **Step 1: Napsat 6 Q&A podle strategie spec § 6**

Otevřít `templates/ddd/basic_concepts.html.twig`, pro 6 zvolených konceptů (Entita, Value Object, Agregát, Repozitář, Doménová služba, Doménová událost — nebo Bounded Context) odvodit jednu Q&A z příslušné sekce. Stejná pravidla jako Task 3 Step 1.

Vzorová Q&A:

```
Otázka: Jaký je rozdíl mezi Entitou a Value Objectem?
Odpověď: Entita má jednoznačnou identitu (ID), která ji odlišuje od ostatních i při stejných atributech — typicky `Order`, `User`. Value Object identitu nemá a porovnává se podle hodnot všech svých atributů — typicky `Money`, `Address`. Podrobnosti v <a href="#value-objects">sekci o Value Objects</a>.
```

- [ ] **Step 2: Najít vkládací pozici**

Run:
```bash
grep -n 'id="shrnuti"\|id="summary"\|id="further-reading"\|id="zdroje"' templates/ddd/basic_concepts.html.twig
```

Zaznamenat řádky uzavírací sekce Shrnutí a otevírací sekce Další četba / Zdroje.

- [ ] **Step 3: Vložit include s items**

Za uzavírající `</section>` Shrnutí a před `<section id="...">` Další četba / Zdroje vložit:

```twig
        {% include '_partials/faq.html.twig' with {
            items: [
                { question: '<Q1 — Bounded Context nebo Ubiquitous Language>', answer: '<A1>' },
                { question: 'Jaký je rozdíl mezi Entitou a Value Objectem?', answer: 'Entita má jednoznačnou identitu... <a href="#value-objects">více v sekci o Value Objects</a>.' },
                { question: '<Q3 — Aggregate>', answer: '<A3>' },
                { question: '<Q4 — Repository>', answer: '<A4>' },
                { question: '<Q5 — Domain Service>', answer: '<A5>' },
                { question: '<Q6 — Domain Event>', answer: '<A6>' }
            ]
        } %}

```

Implementátor nahradí `<...>` placeholdery konkrétním textem z výstupu Step 1.

- [ ] **Step 4: Ověřit Twig syntaxi a build**

Run: `php bin/console cache:clear && npm run build`. Expected: obojí bez chyb.

- [ ] **Step 5: Vizuální kontrola**

Otevřít `http://localhost:8000/zakladni-koncepty`, ověřit vzhled FAQ sekce a přítomnost JSON-LD v HTML zdroji.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/basic_concepts.html.twig
git commit -m "feat: FAQ sekce na stránce Základní koncepty (6 otázek)"
```

---

## Task 5: FAQ na stránce „CQRS" (5 otázek)

**Files:**
- Modify: `templates/ddd/cqrs.html.twig`

Sekce k pokrytí (z ToC): Co je CQRS, CQS vs. CQRS, Výhody, Challenges, Symfony Messenger / Commands / Queries. Vybrat 5 konceptů, které pokryjí klíčové rozhodovací body pro čtenáře.

- [ ] **Step 1: Napsat 5 Q&A podle strategie spec § 6**

Otevřít `templates/ddd/cqrs.html.twig`, vybrat 5 sekcí, pro každou jedna Q&A. Stejná pravidla jako Task 3 Step 1.

Vzorová Q&A:

```
Otázka: Jaký je rozdíl mezi CQS a CQRS?
Odpověď: CQS (Command Query Separation) je princip na úrovni metod — každá metoda buď mění stav, nebo vrací data, ne obojí. CQRS (Command Query Responsibility Segregation) je architektonický vzor, který toto oddělení povyšuje na úroveň celého modelu: existují oddělené write modely a read modely s vlastními datovými strukturami. Více v <a href="#cqs-vs-cqrs">sekci CQS vs. CQRS</a>.
```

- [ ] **Step 2: Najít vkládací pozici**

Run:
```bash
grep -n 'id="summary"\|id="shrnuti"\|id="further-reading"\|id="zdroje"' templates/ddd/cqrs.html.twig
```

- [ ] **Step 3: Vložit include s items**

Za uzavírající `</section>` Shrnutí a před otevírací `<section id="...">` Další četba / Zdroje vložit:

```twig
        {% include '_partials/faq.html.twig' with {
            items: [
                { question: 'Co je CQRS?', answer: '<A1>' },
                { question: 'Jaký je rozdíl mezi CQS a CQRS?', answer: 'CQS je princip na úrovni metod... <a href="#cqs-vs-cqrs">více v sekci CQS vs. CQRS</a>.' },
                { question: '<Q3 — Výhody CQRS>', answer: '<A3>' },
                { question: '<Q4 — Výzvy a eventual consistency>', answer: '<A4>' },
                { question: '<Q5 — Symfony Messenger jako základ>', answer: '<A5>' }
            ]
        } %}

```

Implementátor nahradí placeholdery finálními texty z výstupu Step 1.

- [ ] **Step 4: Ověřit Twig syntaxi a build**

Run: `php bin/console cache:clear && npm run build`. Expected: bez chyb.

- [ ] **Step 5: Vizuální kontrola**

Otevřít `http://localhost:8000/cqrs`, ověřit FAQ sekci a JSON-LD.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/cqrs.html.twig
git commit -m "feat: FAQ sekce na stránce CQRS (5 otázek)"
```

---

## Task 6: FAQ na stránce „Event Sourcing" (6 otázek)

**Files:**
- Modify: `templates/ddd/event_sourcing.html.twig`

Sekce k pokrytí (z ToC): Co je ES, Vztah k CQRS, Event Store, Projekce, Snapshotting, Kdy použít.

- [ ] **Step 1: Napsat 6 Q&A podle strategie spec § 6**

Otevřít `templates/ddd/event_sourcing.html.twig`, pro 6 vybraných sekcí odvodit Q&A. Stejná pravidla jako Task 3 Step 1.

Vzorová Q&A:

```
Otázka: Co je Event Sourcing?
Odpověď: Event Sourcing je způsob persistence stavu, při kterém se neukládá aktuální podoba entity, ale sekvence doménových událostí, které k aktuálnímu stavu vedly. Aktuální stav se rekonstruuje přehráním událostí od začátku. Přínosem je úplný audit trail a možnost zpětně analyzovat chování systému — za cenu vyšší komplexity persistence a projekcí. Viz <a href="#co-je-event-sourcing">úvodní sekce</a>.
```

- [ ] **Step 2: Najít vkládací pozici**

Run:
```bash
grep -n 'id="summary"\|id="shrnuti"\|id="further-reading"\|id="zdroje"' templates/ddd/event_sourcing.html.twig
```

- [ ] **Step 3: Vložit include s items**

Za Shrnutí, před Další četba / Zdroje, vložit:

```twig
        {% include '_partials/faq.html.twig' with {
            items: [
                { question: 'Co je Event Sourcing?', answer: 'Event Sourcing je způsob persistence... <a href="#co-je-event-sourcing">viz úvod</a>.' },
                { question: '<Q2 — ES vs. CQRS / vztah>', answer: '<A2>' },
                { question: '<Q3 — Event Store a jeho role>', answer: '<A3>' },
                { question: '<Q4 — Projekce a read modely>', answer: '<A4>' },
                { question: '<Q5 — Snapshotting>', answer: '<A5>' },
                { question: '<Q6 — Kdy použít Event Sourcing>', answer: '<A6>' }
            ]
        } %}

```

Implementátor nahradí placeholdery finálními texty.

- [ ] **Step 4: Ověřit Twig syntaxi a build**

Run: `php bin/console cache:clear && npm run build`.

- [ ] **Step 5: Vizuální kontrola**

Otevřít `http://localhost:8000/event-sourcing`, ověřit FAQ sekci a JSON-LD.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/event_sourcing.html.twig
git commit -m "feat: FAQ sekce na stránce Event Sourcing (6 otázek)"
```

---

## Task 7: FAQ na stránce „Kdy DDD nepoužívat" (4 otázky)

**Files:**
- Modify: `templates/ddd/when_not_to_use_ddd.html.twig`

Sekce k pokrytí (z ToC): Rozhodovací strom, CRUD admin, Startup, Malý tým. Vybrat 4 nejčastější rozhodovací situace.

- [ ] **Step 1: Napsat 4 Q&A podle strategie spec § 6**

Otevřít `templates/ddd/when_not_to_use_ddd.html.twig`, vybrat 4 sekce. Stejná pravidla jako Task 3 Step 1.

Vzorová Q&A:

```
Otázka: Vyplatí se DDD pro jednoduchý CRUD admin?
Odpověď: Ne. Pro administrační rozhraní, které pouze manipuluje s tabulkami databáze bez doménové logiky, je DDD nepřiměřeně komplexní. CRUD přístup (například EasyAdmin) přináší stejnou funkcionalitu za zlomek úsilí. Podrobný rozbor v <a href="#crud-admin">sekci o CRUD adminu</a>.
```

- [ ] **Step 2: Najít vkládací pozici**

Run:
```bash
grep -n 'id="summary"\|id="shrnuti"\|id="further-reading"\|id="zdroje"\|id="when-ddd-fits"' templates/ddd/when_not_to_use_ddd.html.twig
```

Na této stránce může poslední obsahová sekce být `when-ddd-fits` (Kdy DDD smysl má) místo „Shrnutí". Vložit FAQ **mezi poslední obsahovou sekci a sekci Zdroje**.

- [ ] **Step 3: Vložit include s items**

Za poslední obsahovou sekcí (`when-ddd-fits` nebo Shrnutí, podle struktury), před `<section id="zdroje">` (ř. ~549), vložit:

```twig
        {% include '_partials/faq.html.twig' with {
            items: [
                { question: 'Vyplatí se DDD pro jednoduchý CRUD admin?', answer: 'Ne. Pro administrační rozhraní... <a href="#crud-admin">viz sekce o CRUD adminu</a>.' },
                { question: '<Q2 — DDD ve startupu s měnící se doménou>', answer: '<A2>' },
                { question: '<Q3 — DDD v malém týmu bez doménových expertů>', answer: '<A3>' },
                { question: '<Q4 — Kdy naopak DDD smysl má>', answer: '<A4>' }
            ]
        } %}

```

Implementátor nahradí placeholdery finálními texty.

- [ ] **Step 4: Ověřit Twig syntaxi a build**

Run: `php bin/console cache:clear && npm run build`.

- [ ] **Step 5: Vizuální kontrola**

Otevřít `http://localhost:8000/kdy-nepouzivat-ddd`, ověřit FAQ sekci a JSON-LD.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/when_not_to_use_ddd.html.twig
git commit -m "feat: FAQ sekce na stránce Kdy DDD nepoužívat (4 otázky)"
```

---

## Task 8: FAQ na stránce „DDD a AI" (4 otázky)

**Files:**
- Modify: `templates/ddd/ddd_ai.html.twig`

Sekce k pokrytí (z ToC): Ubiquitous language pro LLM, Bounded contexts a kvalita AI kódu, Testování jako kontrola AI, Otevřené otázky.

- [ ] **Step 1: Napsat 4 Q&A podle strategie spec § 6**

Otevřít `templates/ddd/ddd_ai.html.twig`. Stejná pravidla jako Task 3 Step 1.

Vzorová Q&A:

```
Otázka: Proč AI nástroje lépe generují kód v projektech s Ubiquitous Language?
Odpověď: Ubiquitous Language poskytuje LLM jednoznačný slovník, který se používá napříč dokumentací i kódem. Model při generování kódu dostává konzistentní pojmy z kontextu a produkuje výstup, který zapadá do existujícího modelu bez překladu. Bez Ubiquitous Language AI často zavádí vlastní pojmenování, které se rozchází s doménou. Viz <a href="#ubiquitous-language">sekci o Ubiquitous Language a LLM</a>.
```

- [ ] **Step 2: Najít vkládací pozici**

Run:
```bash
grep -n 'id="zaver"\|id="summary"\|id="further-reading"\|id="zdroje"' templates/ddd/ddd_ai.html.twig
```

Tato stránka používá `id="zaver"` (Závěr) místo „Shrnutí". Vložit FAQ **mezi sekci Závěr a sekci Zdroje**.

- [ ] **Step 3: Vložit include s items**

Za `</section>` sekce Závěr, před `<section id="zdroje">`, vložit:

```twig
        {% include '_partials/faq.html.twig' with {
            items: [
                { question: 'Proč AI nástroje lépe generují kód v projektech s Ubiquitous Language?', answer: 'Ubiquitous Language poskytuje LLM... <a href="#ubiquitous-language">více v sekci o Ubiquitous Language a LLM</a>.' },
                { question: '<Q2 — Bounded contexts a kvalita generovaného kódu>', answer: '<A2>' },
                { question: '<Q3 — Role testování při práci s AI>', answer: '<A3>' },
                { question: '<Q4 — Limity AI v doménově komplexním kódu / otevřené otázky>', answer: '<A4>' }
            ]
        } %}

```

Implementátor nahradí placeholdery finálními texty.

- [ ] **Step 4: Ověřit Twig syntaxi a build**

Run: `php bin/console cache:clear && npm run build`.

- [ ] **Step 5: Vizuální kontrola**

Otevřít `http://localhost:8000/ddd-a-umela-inteligence`, ověřit FAQ sekci a JSON-LD.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/ddd_ai.html.twig
git commit -m "feat: FAQ sekce na stránce DDD a AI (4 otázky)"
```

---

## Task 9: FAQ na stránce „Migrace z CRUD" (5 otázek)

**Files:**
- Modify: `templates/ddd/migration_from_crud.html.twig`

Sekce k pokrytí (z ToC): Kdy a proč migrovat, Strangler Fig Pattern, Analýza domény (Krok 1), Extrakce doménové vrstvy (Krok 2), Rizika a doporučení.

- [ ] **Step 1: Napsat 5 Q&A podle strategie spec § 6**

Otevřít `templates/ddd/migration_from_crud.html.twig`. Stejná pravidla jako Task 3 Step 1.

Vzorová Q&A:

```
Otázka: Co je Strangler Fig Pattern?
Odpověď: Strangler Fig je migrační vzor, při kterém se nový systém postupně „obaluje" kolem starého a přebírá jeho funkcionalitu po kouscích. Starý systém běží dál, nová DDD vrstva začíná u jednoho bounded contextu a postupně nahrazuje CRUD logiku. Pattern pojmenoval Martin Fowler podle fíkovníku škrtícího, který postupně obroste hostitelský strom. Více v <a href="#strangler-fig">sekci Strangler Fig Pattern</a>.
```

- [ ] **Step 2: Najít vkládací pozici**

Run:
```bash
grep -n 'id="rizika-a-doporuceni"\|id="summary"\|id="shrnuti"\|id="further-reading"\|id="zdroje"' templates/ddd/migration_from_crud.html.twig
```

- [ ] **Step 3: Vložit include s items**

Za `</section>` poslední obsahové sekce (Rizika a doporučení nebo Shrnutí), před sekci Zdroje / Další četba, vložit:

```twig
        {% include '_partials/faq.html.twig' with {
            items: [
                { question: '<Q1 — Kdy má smysl migrovat z CRUD na DDD>', answer: '<A1>' },
                { question: 'Co je Strangler Fig Pattern?', answer: 'Strangler Fig je migrační vzor... <a href="#strangler-fig">více v sekci Strangler Fig Pattern</a>.' },
                { question: '<Q3 — Jak začít s analýzou existující domény>', answer: '<A3>' },
                { question: '<Q4 — Jak extrahovat doménovou vrstvu z legacy kódu>', answer: '<A4>' },
                { question: '<Q5 — Hlavní rizika migrace a jak je zmírnit>', answer: '<A5>' }
            ]
        } %}

```

Implementátor nahradí placeholdery finálními texty.

- [ ] **Step 4: Ověřit Twig syntaxi a build**

Run: `php bin/console cache:clear && npm run build`.

- [ ] **Step 5: Vizuální kontrola**

Otevřít `http://localhost:8000/migrace-z-crud`, ověřit FAQ sekci a JSON-LD.

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/migration_from_crud.html.twig
git commit -m "feat: FAQ sekce na stránce Migrace z CRUD (5 otázek)"
```

---

## Task 10: Finální ověření

Ověřit produkční build, JSON-LD validitu u dvou reprezentativních stránek a mobilní vzhled.

**Files:** (žádné změny, pokud ověření neodhalí chybu)

- [ ] **Step 1: Produkční build**

Run:
```bash
npm run build
```

Expected: Úspěšný build, žádné warning o duplicate CSS class / nepoužité proměnné.

- [ ] **Step 2: Vizuální kontrola všech 7 stránek**

Spustit `symfony server:start` a projít postupně:
- `/co-je-ddd`
- `/zakladni-koncepty`
- `/cqrs`
- `/event-sourcing`
- `/kdy-nepouzivat-ddd`
- `/ddd-a-umela-inteligence`
- `/migrace-z-crud`

Na každé ověřit:
- FAQ sekce je mezi Shrnutí/Závěr a Další četba/Zdroje.
- Vzhled karet s fialovým levým borderem a otazníkem odpovídá designu.
- Odkazy v odpovědích fungují (neklikají mimo stránku, anchor existuje).
- V konzoli prohlížeče žádné JS/CSS chyby.

- [ ] **Step 3: Mobilní vizuální kontrola**

V prohlížeči otevřít Device Mode (DevTools → responsive, např. iPhone 12 / 390px). Ověřit alespoň na 2 stránkách (jedna s 4 Q&A, jedna s 6 Q&A):
- Karty mají plnou šířku bez horizontálního scrollu.
- Padding a font-size jsou čitelné.
- Ikona ❓ nerozbíjí layout (zůstává vedle textu otázky, nevyskakuje na samostatný řádek).

- [ ] **Step 4: Validace JSON-LD — kratší stránka**

Otevřít `http://localhost:8000/co-je-ddd` → View Source → najít `<script type="application/ld+json">` s `"@type": "FAQPage"`. Zkopírovat celý JSON.

Vložit do [validator.schema.org](https://validator.schema.org/) a ověřit:
- Žádné chyby, žádné warning o missing properties.
- `mainEntity` pole obsahuje 4 objekty typu `Question`, každý s `name` a `acceptedAnswer.text`.

Dále vložit URL stránky do [Google Rich Results Test](https://search.google.com/test/rich-results):
- Detekuje `FAQPage` strukturovaná data.
- Bez chyb, případné warning si zapsat a posoudit, jestli se musí řešit (obvykle „doporučení", ne povinné).

- [ ] **Step 5: Validace JSON-LD — delší stránka**

Stejný postup jako Step 4 pro `http://localhost:8000/event-sourcing` (má 6 Q&A, delší text). Ověřit, že delší odpovědi nepůsobí problém s validací.

- [ ] **Step 6: Ověřit, že stávající `TechArticle` schema stále validuje**

Na stránce `/co-je-ddd` zkontrolovat v Google Rich Results Test, že jsou detekovány **dva** typy strukturovaných dat:
- `TechArticle` (existující, beze změny)
- `FAQPage` (nový)

Oba musí být bez chyb. Pokud se `TechArticle` náhle rozbije, znamená to, že nový JSON-LD skript konfliktuje — je třeba zkontrolovat, že partial generuje samostatný `<script>` tag a neprosákl mimo něj.

- [ ] **Step 7: Final commit (pokud byly drobné opravy)**

Pokud při ověření vznikly drobné opravy (překlep v Q&A, chybný anchor), commitnout je:

```bash
git add templates/
git commit -m "fix: drobné opravy FAQ po validaci"
```

Pokud nic není potřeba, Task 10 končí bez commitu.

---

## Self-Review (provedeno při tvorbě plánu)

**Spec coverage:**
- Spec § 1 (rozsah, 7 stránek) → Tasky 3–9, každá stránka jeden Task.
- Spec § 2 (architektura, reusable partial) → Task 1.
- Spec § 3 (HTML + přístupnost) → Task 1 (obsah partial) + Task 10 (ověření přístupnosti).
- Spec § 4 (CSS + accent barva) → Task 2.
- Spec § 5 (FAQPage JSON-LD, shoda textu) → Task 1 (generátor v partial) + Task 10 Steps 4–6 (validace).
- Spec § 6 (obsahová strategie, tabulka počtu Q&A) → každý per-page Task Step 1 referuje na spec § 6, počty odpovídají tabulce (4, 6, 5, 6, 4, 4, 5).
- Spec § 7 (testování) → Task 10.
- Spec § 8 (budoucí rozšíření) → partial je reusable, žádný speciální úkol není potřeba.

**Placeholder scan:** Placeholdery `<...>` v include blocích jsou záměrné — označují obsahový slot, který implementátor vyplní ve Step 1 daného tasku. **Step 3 každého per-page tasku explicitně říká, že finální soubor je nesmí obsahovat.** Žádný „TBD" / „implement later" / „fill in details" se v plánu nevyskytuje.

**Type consistency:** `items` struktura `{ question, answer }` je stejná ve všech taskech (Task 1 partial + Tasky 3–9 volání). CSS třídy `.faq`, `.faq-item` konzistentní mezi Task 1 (markup) a Task 2 (pravidla).
