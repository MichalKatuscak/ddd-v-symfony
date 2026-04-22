# Kdy DDD nepoužívat — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Přidat novou standalone stránku „Kdy DDD nepoužívat — upřímně" na URL `/kdy-nepouzivat-ddd`, včetně propojení do navigace a indexu.

**Architecture:** Čistě contentový web — žádná databáze, žádné testy. Nová stránka = nový Twig template + route v controlleru + odkaz v sidebar + karta v index feature gridu.

**Tech Stack:** Symfony 8, Twig, PHP 8.4, Symfony CLI

> **Poznámka:** Projekt nemá test suite. Místo testovacích kroků jsou kroky manuální verifikace (`symfony server:start` + browser).

---

## Soubory

| Akce | Soubor | Co se mění |
|------|--------|------------|
| Create | `templates/ddd/when_not_to_use_ddd.html.twig` | Celý obsah nové stránky |
| Modify | `src/Controller/DddController.php` | Přidat akci `whenNotToUseDdd()` |
| Modify | `templates/base.html.twig` | Přidat odkaz do sidebar nav |
| Modify | `templates/ddd/index.html.twig` | Přidat kartu do feature gridu |

---

## Task 1: Přidat route a controller akci

**Files:**
- Modify: `src/Controller/DddController.php`

- [ ] **Step 1: Otevřít soubor** `src/Controller/DddController.php` a najít konec třídy (před uzavírací `}`)

- [ ] **Step 2: Přidat akci** těsně před uzavírací `}` třídy:

```php
    #[Route('/kdy-nepouzivat-ddd', name: 'when_not_to_use_ddd')]
    public function whenNotToUseDdd(): Response
    {
        return $this->render('ddd/when_not_to_use_ddd.html.twig', [
            'title' => 'Kdy DDD nepoužívat — upřímně',
        ]);
    }
```

- [ ] **Step 3: Ověřit PHP syntax**

```bash
php -l src/Controller/DddController.php
```

Expected: `No syntax errors detected in src/Controller/DddController.php`

- [ ] **Step 4: Commit**

```bash
git add src/Controller/DddController.php
git commit -m "feat: přidat route /kdy-nepouzivat-ddd"
```

---

## Task 2: Vytvořit Twig template

**Files:**
- Create: `templates/ddd/when_not_to_use_ddd.html.twig`

- [ ] **Step 1: Vytvořit soubor** `templates/ddd/when_not_to_use_ddd.html.twig` s tímto obsahem:

```twig
{% extends 'base.html.twig' %}

{% block title %}Kdy DDD nepoužívat — upřímně | DDD Symfony{% endblock %}

{% block meta_description %}7 konkrétních situací, kdy DDD nepoužívat — s alternativami. Upřímný průvodce pro PHP vývojáře, kteří nechtějí zavádět zbytečnou komplexitu.{% endblock %}

{% block meta_keywords %}kdy nepoužívat DDD, DDD nevhodné projekty, DDD alternativy, DDD limity, DDD CRUD, DDD startup, DDD malý tým{% endblock %}

{% block structured_data %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "Kdy DDD nepoužívat — upřímně",
  "description": "{{ block('meta_description') }}",
  "keywords": "{{ block('meta_keywords') }}",
  "author": {
    "@type": "Person",
    "name": "Michal Katuščák"
  },
  "publisher": {
    "@type": "Person",
    "name": "Michal Katuščák"
  },
  "datePublished": "2026-03-26",
  "dateModified": "2026-03-26",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ app.request.schemeAndHttpHost }}{{ app.request.pathInfo }}"
  }
}
</script>
{% endblock %}

{% block body %}
<article itemscope itemtype="https://schema.org/TechArticle">
    <h1 itemprop="headline">Kdy DDD nepoužívat — upřímně</h1>

    <p>
        DDD není architektura pro každý projekt. Špatně zvolená aplikace DDD přidává vrstvy abstrakce,
        zpomaluje vývoj a frustruje tým — aniž by přinesla cokoliv hodnotného.
        Tato kapitola říká přímo, kdy DDD vynechat, a co místo toho použít.
    </p>

    <p>
        Každý průvodce, který DDD jen propaguje, ti lže. Tohle je jiný průvodce.
    </p>

    <nav class="table-of-contents mb-4" role="navigation" aria-labelledby="toc-heading">
        <p id="toc-heading">Obsah kapitoly:</p>
        <ul>
            <li><a href="#crud-admin">1. CRUD admin a jednoduchý backoffice</a></li>
            <li><a href="#startup">2. Startup — doména se mění každý sprint</a></li>
            <li><a href="#small-team">3. Malý tým bez doménových expertů</a></li>
            <li><a href="#data-pipeline">4. Data pipeline, ETL a reportovací systémy</a></li>
            <li><a href="#short-lived">5. Projekt s životností kratší než rok</a></li>
            <li><a href="#no-knowledge">6. Tým DDD nezná a čas na učení není</a></li>
            <li><a href="#unclear-domain">7. Doména je nejasná, experti nejsou k dispozici</a></li>
            <li><a href="#when-ddd-fits">Kdy DDD naopak smysl má</a></li>
        </ul>
    </nav>

    <section id="crud-admin" aria-labelledby="crud-admin-heading">
        <h2 id="crud-admin-heading">1. CRUD admin a jednoduchý backoffice</h2>

        <p>
            Máš aplikaci, kde uživatel vytvoří záznam, upraví ho a smaže. Formulář mapuje 1:1 na tabulku.
            Žádná doménová logika — jen persistence.
        </p>

        <p>
            DDD tady přidá agregáty, repozitáře, doménové události a value objekty pro věci,
            které jsou přirozeně jenom řádky v databázi. Výsledek: 5× více kódu, žádná přidaná hodnota.
        </p>

        <div class="note" role="note">
            <strong>Použij místo toho:</strong>
            <ul>
                <li><strong>EasyAdmin</strong> — pro backoffice a CMS adminy. Konfigurací, ne kódem.</li>
                <li><strong>Symfony Forms + Doctrine Entity přímo v controlleru</strong> — pro jednoduchý CRUD bez business logiky.</li>
            </ul>
            Doménový model zavádíš tehdy, když máš doménu. CRUD admin doménu nemá.
        </div>
    </section>

    <section id="startup" aria-labelledby="startup-heading">
        <h2 id="startup-heading">2. Startup — doména se mění každý sprint</h2>

        <p>
            Hledáte product-market fit. Co dnes je objednávka, zítra je subscription. Co dnes je
            zákazník, zítra je partner. Ubiquitous Language nemůžeš vybudovat, když doménový model
            ještě neexistuje.
        </p>

        <p>
            DDD předpokládá, že doménu rozumíš dost dobře na to, abys ji modeloval. Ve fázi hledání
            to neplatí. Každý refaktoring agregátů a bounded contextů tě zpomaluje — a jedeš sprint
            kvůli kódu, ne kvůli zákazníkům.
        </p>

        <div class="note" role="note">
            <strong>Použij místo toho:</strong>
            <ul>
                <li><strong>Flat MVC s Doctrine Entities</strong> — rychlé iterace, změny jsou levné.</li>
                <li>Až doména stabilizuje (3–6 měsíců provozu), teprve pak zvaž zavedení DDD vzorů selektivně.</li>
            </ul>
        </div>
    </section>

    <section id="small-team" aria-labelledby="small-team-heading">
        <h2 id="small-team-heading">3. Malý tým bez doménových expertů</h2>

        <p>
            DDD stojí na spolupráci vývojářů s lidmi, kteří doméně rozumí — zákazníci, produktoví manažeři,
            analytici. Bez nich modeluješ doménu sám, z hlavy, bez zpětné vazby.
        </p>

        <p>
            Výsledkem je model, který odráží to, jak vývojář chápe doménu — ne jak funguje doména ve skutečnosti.
            To je přesně opak toho, k čemu DDD slouží.
        </p>

        <div class="note" role="note">
            <strong>Použij místo toho:</strong>
            <ul>
                <li><strong>Vrstvená architektura</strong> (Controller → Service → Repository) — dobře strukturovaný kód bez přílišné abstrakte.</li>
                <li>DDD zaveď až ve chvíli, kdy máš přístup k doménovým expertům a čas na Event Storming.</li>
            </ul>
        </div>
    </section>

    <section id="data-pipeline" aria-labelledby="data-pipeline-heading">
        <h2 id="data-pipeline-heading">4. Data pipeline, ETL a reportovací systémy</h2>

        <p>
            Načítáš data z externích zdrojů, transformuješ je a ukládáš nebo reportuješ.
            Žádné business rules, žádné invarianty, žádná doménová logika.
            Je to přesun a transformace dat — ne modelování domény.
        </p>

        <p>
            Agregáty chrání invarianty. Pokud žádné invarianty nemáš, agregáty nemáš k čemu potřebovat.
            Přidáváš komplexitu bez důvodu.
        </p>

        <div class="note" role="note">
            <strong>Použij místo toho:</strong>
            <ul>
                <li><strong>Service layer s plain PHP objekty</strong> — jednoduché třídy pro transformaci, bez agregátů.</li>
                <li><strong>Symfony Messenger</strong> pro asynchronní zpracování pipeline kroků — bez DDD overhead.</li>
            </ul>
        </div>
    </section>

    <section id="short-lived" aria-labelledby="short-lived-heading">
        <h2 id="short-lived-heading">5. Projekt s životností kratší než rok</h2>

        <p>
            Interní nástroj, landing page, jednorázová migrace, prototyp pro demo zákazníkovi.
            Kód napíšeš, použiješ a zahodíš.
        </p>

        <p>
            DDD investice se vrátí na projektech, které žijí roky a rostou. Na krátkodobých projektech
            zaplatíš cenu DDD (čas, komplexita, learning curve) bez toho, aniž bys někdy sklidil
            výhody (udržovatelnost, evolvability).
        </p>

        <div class="note" role="note">
            <strong>Použij místo toho:</strong>
            <ul>
                <li><strong>Prostý Symfony controller + Doctrine</strong> — nejkratší cesta od požadavku k funkčnímu kódu.</li>
                <li>Pokud projekt nečekaně vyroste, refaktorovat z jednoduchého kódu na DDD je snazší než vysvětlovat, proč jednoduchý projekt má 40 tříd.</li>
            </ul>
        </div>
    </section>

    <section id="no-knowledge" aria-labelledby="no-knowledge-heading">
        <h2 id="no-knowledge-heading">6. Tým DDD nezná a čas na učení není</h2>

        <p>
            DDD vyžaduje, aby tým rozuměl konceptům — aggregates, bounded contexts, domain events,
            repositories. Špatně pochopené DDD je horší než žádné DDD: produkuje pseudo-DDD kód,
            který má přidanou komplexitu bez architektonických výhod.
        </p>

        <p>
            „Naučíme se za pochodu" na produkčním projektu s deadlinem je recept na technický dluh,
            který bude bolet roky.
        </p>

        <div class="note" role="note">
            <strong>Použij místo toho:</strong>
            <ul>
                <li>Klasická architektura, kterou tým zná dobře — srozumitelný kód je vždy lepší než „správná" architektura, které nikdo nerozumí.</li>
                <li>DDD zaveď na vedlejším projektu nebo v části systému jako experiment. Pak přenášej zkušenosti postupně.</li>
            </ul>
        </div>
    </section>

    <section id="unclear-domain" aria-labelledby="unclear-domain-heading">
        <h2 id="unclear-domain-heading">7. Doména je nejasná, experti nejsou k dispozici</h2>

        <p>
            Zákazník neví, co chce. Požadavky jsou vágní. Doménový expert buď neexistuje, nebo
            nemá čas spolupracovat. Modeluješ ve tmě.
        </p>

        <p>
            DDD bez znalosti domény je jen přejmenování tříd. „Order", „Customer", „Product" —
            vypadá to jako DDD, ale model neodráží skutečnou doménu. Za rok, až doménu pochopíš,
            přepíšeš stejně všechno.
        </p>

        <div class="note" role="note">
            <strong>Použij místo toho:</strong>
            <ul>
                <li><strong>Event Storming napřed</strong> — než napíšeš první řádek kódu, zmapuj doménu se stakeholdery. Bez toho neví DDD co modelovat.</li>
                <li>Pokud Event Storming není možný, začni s jednoduchým kódem a DDD zaveď retrospektivně, až doménu pochopíš.</li>
            </ul>
        </div>
    </section>

    <section id="when-ddd-fits" aria-labelledby="when-ddd-fits-heading">
        <h2 id="when-ddd-fits-heading">Kdy DDD naopak smysl má</h2>

        <p>
            DDD není špatná architektura. Je to architektura pro specifický kontext. Smysl má, když platí
            <strong>většina</strong> z těchto podmínek:
        </p>

        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Podmínka</th>
                    <th>Proč záleží</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Komplexní doménová logika (ne jen CRUD)</td>
                    <td>DDD chrání invarianty a modeluje pravidla — bez pravidel nemá co chránit</td>
                </tr>
                <tr>
                    <td>Projekt bude žít a růst roky</td>
                    <td>Investice do architektury se vrátí jen při dostatečném horizontu</td>
                </tr>
                <tr>
                    <td>Přístup k doménovým expertům</td>
                    <td>Ubiquitous Language a model se tvoří ve spolupráci — ne ze vzduchuprázdna</td>
                </tr>
                <tr>
                    <td>Tým rozumí DDD nebo má čas se učit</td>
                    <td>Špatně implementované DDD je horší než žádné DDD</td>
                </tr>
                <tr>
                    <td>Více bounded contexts nebo mikroslužby</td>
                    <td>DDD dává přirozené hranice pro dekompozici systému</td>
                </tr>
            </tbody>
        </table>
        </div>

        <p>
            Pokud tvůj projekt splňuje tyto podmínky, DDD se vyplatí. Pokud ne — použij jednodušší
            přístup a ušetři si bolest.
        </p>

        <p>
            Detailní implementaci DDD v Symfony najdeš v <a href="{{ path('implementation_in_symfony') }}">implementační kapitole</a>.
            Reálné problémy při zavádění DDD popisuje kapitola <a href="{{ path('ddd_pain_points') }}">DDD v praxi — kde to bolí</a>.
        </p>
    </section>

</article>
{% endblock %}
```

- [ ] **Step 2: Commit**

```bash
git add templates/ddd/when_not_to_use_ddd.html.twig
git commit -m "feat: přidat template kdy-nepouzivat-ddd"
```

---

## Task 3: Přidat odkaz do sidebar navigace

**Files:**
- Modify: `templates/base.html.twig`

- [ ] **Step 1: Najít v `templates/base.html.twig`** řádek s odkazem na `ddd_pain_points`:

```twig
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'ddd_pain_points' %}active{% endif %}" href="{{ path('ddd_pain_points') }}">DDD v praxi — kde to bolí</a>
                    </li>
```

- [ ] **Step 2: Přidat novou položku** hned za tento `</li>` (před `case_study`):

```twig
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'when_not_to_use_ddd' %}active{% endif %}" href="{{ path('when_not_to_use_ddd') }}">Kdy DDD nepoužívat</a>
                    </li>
```

- [ ] **Step 3: Commit**

```bash
git add templates/base.html.twig
git commit -m "feat: přidat kdy-nepouzivat-ddd do sidebar navigace"
```

---

## Task 4: Přidat kartu do feature gridu na homepage

**Files:**
- Modify: `templates/ddd/index.html.twig`

- [ ] **Step 1: Najít v `templates/ddd/index.html.twig`** kartu s odkazem na `ddd_pain_points`:

```twig
        <a href="{{ path('ddd_pain_points') }}" class="feature-card">
            <h3 class="feature-card-title">DDD v praxi — kde to bolí</h3>
            <p class="feature-card-desc">20 reálných problémů: Doctrine, Messenger, validace, ACL, strangler fig a přesvědčení managementu.</p>
        </a>
```

- [ ] **Step 2: Přidat novou kartu** hned za tento closing `</a>` (před `implementation_in_symfony` kartu):

```twig
        <a href="{{ path('when_not_to_use_ddd') }}" class="feature-card">
            <h3 class="feature-card-title">Kdy DDD nepoužívat</h3>
            <p class="feature-card-desc">7 konkrétních situací kdy DDD přinese víc škody než užitku — s alternativami pro každý případ.</p>
        </a>
```

- [ ] **Step 3: Commit**

```bash
git add templates/ddd/index.html.twig
git commit -m "feat: přidat kartu kdy-nepouzivat-ddd do feature gridu"
```

---

## Task 5: Manuální verifikace

- [ ] **Step 1: Spustit dev server** (pokud neběží)

```bash
symfony server:start -d
```

- [ ] **Step 2: Ověřit stránku** — otevřít `http://127.0.0.1:8000/kdy-nepouzivat-ddd` v prohlížeči

Očekávaný výsledek:
- Stránka se načte bez chyby
- H1 je „Kdy DDD nepoužívat — upřímně"
- Sidebar obsahuje odkaz „Kdy DDD nepoužívat" (zvýrazněný jako aktivní)
- TOC sidebar vpravo zobrazuje nadpisy sekcí
- 7 sekcí + závěrečná tabulka jsou viditelné

- [ ] **Step 3: Ověřit homepage** — otevřít `http://127.0.0.1:8000/`

Očekávaný výsledek:
- Feature grid obsahuje kartu „Kdy DDD nepoužívat"
- Karta vede správně na `/kdy-nepouzivat-ddd`

- [ ] **Step 4: Ověřit cache** (pokud je potřeba)

```bash
php bin/console cache:clear
```
