# Důvěryhodnost autora — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Zvýšit důvěryhodnost webu přidáním author bio na homepage, stránky `/o-autorovi` a rozšířením E-E-A-T structured data ve všech šablonách.

**Architecture:** Tři nezávislé změny: (1) nová route + šablona pro `/o-autorovi`, (2) rozšíření existující `.homepage-about` sekce o foto a bio autora, (3) systémové doplnění `url` a `sameAs` do `author` JSON-LD v 19 šablonách. Žádné DB závislosti.

**Tech Stack:** Symfony 8, Twig, PHP 8.4, vanilla CSS (dark theme s CSS variables), schema.org JSON-LD

---

## Soubory

| Soubor | Akce |
|--------|------|
| `src/Controller/DddController.php` | Přidat route `/o-autorovi` |
| `templates/ddd/about.html.twig` | Vytvořit nový soubor |
| `templates/ddd/index.html.twig` | Rozšířit `.homepage-about` sekci |
| `public/css/modern-style.css` | Přidat styly `.author-bio` |
| `templates/base.html.twig` | Přidat sidebar odkaz na `/o-autorovi` |
| `templates/ddd/*.html.twig` (19 souborů) | Rozšířit `author` JSON-LD |

---

## Task 1: Route a šablona `/o-autorovi`

**Files:**
- Modify: `src/Controller/DddController.php`
- Create: `templates/ddd/about.html.twig`

- [ ] **Krok 1: Přidat route do controlleru**

V `src/Controller/DddController.php` přidat novou metodu za poslední existující route (před uzavírací `}`):

```php
#[Route('/o-autorovi', name: 'about')]
public function about(): Response
{
    return $this->render('ddd/about.html.twig', [
        'title' => 'O autorovi',
    ]);
}
```

- [ ] **Krok 2: Vytvořit šablonu `templates/ddd/about.html.twig`**

```twig
{% extends 'base.html.twig' %}

{% block title %}O autorovi — Michal Katuščák | DDD Symfony{% endblock %}

{% block meta_description %}Michal Katuščák — PHP/React vývojář s 13+ lety komerčního vývoje. Autor průvodce DDD v Symfony 8.{% endblock %}

{% block meta_keywords %}Michal Katuščák, PHP vývojář, Symfony, DDD průvodce, autor{% endblock %}

{% block structured_data %}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ProfilePage",
    "mainEntity": {
        "@type": "Person",
        "name": "Michal Katuščák",
        "url": "https://www.katuscak.cz/",
        "jobTitle": "PHP/React vývojář",
        "description": "PHP/React vývojář s 13+ lety komerčního vývoje. Autor průvodce DDD v Symfony 8.",
        "image": "{{ app.request.schemeAndHttpHost }}/img/author.webp",
        "sameAs": [
            "https://blog.katuscak.cz/",
            "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"
        ]
    },
    "url": "{{ app.request.schemeAndHttpHost }}/o-autorovi"
}
</script>
{% endblock %}

{% block body %}
<article class="about-page" itemscope itemtype="https://schema.org/ProfilePage">
    <h1>O autorovi</h1>

    <div class="author-bio author-bio--large">
        <img
            src="{{ asset('img/author.webp') }}"
            alt="Michal Katuščák — PHP/React vývojář"
            class="author-bio__photo author-bio__photo--large"
            width="160"
            height="160"
            loading="lazy"
        >
        <div class="author-bio__content">
            <p class="author-bio__name" itemprop="name">Michal Katuščák</p>
            <p class="author-bio__role">PHP/React vývojář · České Budějovice</p>
            <div class="author-bio__links">
                <a href="https://www.katuscak.cz/" target="_blank" rel="noopener">katuscak.cz</a>
                <a href="https://blog.katuscak.cz/" target="_blank" rel="noopener">blog.katuscak.cz</a>
                <a href="https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/" target="_blank" rel="noopener">LinkedIn</a>
            </div>
        </div>
    </div>

    <h2>Komerční zkušenosti</h2>
    <p>13+ let vývoje webových aplikací. 6 let jako zaměstnanec (interní systémy, CRM, e-shopy), od 2019 na volné noze. Klienti: Footshop, BRZ, NeosVR, Alpha Supplies a další.</p>

    <h2>Proč tento průvodce</h2>
    <p>DDD literaturu (Evans, Vernon) jsem studoval souběžně s reálnými projekty v Symfony. Průvodce vznikl jako strukturovaný výstup tohoto procesu — pro vývojáře, kteří chtějí DDD pochopit do hloubky, ne jen zkopírovat vzory.</p>
</article>
{% endblock %}
```

- [ ] **Krok 3: Ověřit v prohlížeči**

Otevřít `http://localhost:8000/o-autorovi` (nebo port dle `symfony server:start`). Stránka musí zobrazit foto, jméno, dva odstavce, tři externé odkazy.

- [ ] **Krok 4: Commit**

```bash
git add src/Controller/DddController.php templates/ddd/about.html.twig
git commit -m "feat: přidat stránku /o-autorovi s bio autora"
```

---

## Task 2: Homepage author bio

**Files:**
- Modify: `templates/ddd/index.html.twig:137-140`

Aktuální `.homepage-about` sekce (řádky 137–140) zobrazuje generický popis průvodce. Nahradíme ji author bio blokem.

- [ ] **Krok 1: Nahradit sekci `.homepage-about` v `index.html.twig`**

Najít a nahradit celý blok:

```twig
{# STARÝ KÓD — nahradit: #}
<section class="homepage-about" aria-labelledby="about-heading">
    <h2 id="about-heading">O tomto průvodci</h2>
    <p>Průvodce je určen PHP vývojářům a Symfony vývojářům, kteří chtějí implementovat Domain-Driven Design v reálných projektech. Pokrývá strategický i taktický design, CQRS, Event Sourcing a testování DDD kódu - vše s příklady v PHP 8.4+ a Symfony 8.</p>
</section>
```

Nahradit za:

```twig
<section class="homepage-about" aria-labelledby="about-heading">
    <h2 id="about-heading">O autorovi</h2>
    <div class="author-bio">
        <img
            src="{{ asset('img/author.webp') }}"
            alt="Michal Katuščák — PHP/React vývojář"
            class="author-bio__photo"
            width="80"
            height="80"
            loading="lazy"
        >
        <div class="author-bio__content">
            <p class="author-bio__name">Michal Katuščák</p>
            <p class="author-bio__role">PHP/React vývojář · 13+ let komerčního vývoje</p>
            <p class="author-bio__text">Průvodce jsem napsal jako výsledek hloubkového studia DDD a Symfony ekosystému. V praxi stavím aplikace pro klienty jako Footshop, BRZ nebo NeosVR — z toho vychází konkrétní příklady v kurzu.</p>
            <div class="author-bio__links">
                <a href="{{ path('about') }}">O autorovi &rarr;</a>
                <a href="https://www.katuscak.cz/" target="_blank" rel="noopener">katuscak.cz &#8599;</a>
            </div>
        </div>
    </div>
</section>
```

- [ ] **Krok 2: Ověřit v prohlížeči**

Otevřít homepage. Sekce musí zobrazit foto vedle textu, dva tlačítka/odkazy.

- [ ] **Krok 3: Commit**

```bash
git add templates/ddd/index.html.twig
git commit -m "feat: nahradit popis průvodce author bio blokem na homepage"
```

---

## Task 3: CSS pro author bio

**Files:**
- Modify: `public/css/modern-style.css`

- [ ] **Krok 1: Přidat styly za `.homepage-about h2` blok (po řádku 953)**

```css
/* Author bio — homepage i stránka /o-autorovi */
.author-bio {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
}

.author-bio__photo {
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    width: 80px;
    height: 80px;
    border: 2px solid var(--border);
}

.author-bio__photo--large {
    width: 160px;
    height: 160px;
}

.author-bio__name {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-heading);
    margin: 0 0 0.15rem;
}

.author-bio__role {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0 0 0.5rem;
}

.author-bio__text {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
}

.author-bio__links {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.author-bio__links a {
    color: var(--color-primary);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
}

.author-bio__links a:hover {
    color: var(--color-primary-dim);
    text-decoration: underline;
}

.author-bio__content {
    flex: 1;
    min-width: 0;
}

.about-page .author-bio {
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 480px) {
    .author-bio {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .author-bio__links {
        justify-content: center;
    }
}
```

- [ ] **Krok 2: Ověřit v prohlížeči**

Homepage: foto vlevo, text vpravo, na mobilu pod sebou. Stránka `/o-autorovi`: větší foto v rámečku.

- [ ] **Krok 3: Commit**

```bash
git add public/css/modern-style.css
git commit -m "style: přidat CSS pro .author-bio komponentu"
```

---

## Task 4: Sidebar odkaz na `/o-autorovi`

**Files:**
- Modify: `templates/base.html.twig`

- [ ] **Krok 1: Přidat odkaz za položku Glosář v sidebar nav**

Najít v `base.html.twig`:

```twig
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'glossary' %}active{% endif %}" href="{{ path('glossary') }}">Glosář</a>
```

Za tuto `</li>` přidat:

```twig
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'about' %}active{% endif %}" href="{{ path('about') }}">O autorovi</a>
                    </li>
```

- [ ] **Krok 2: Ověřit v prohlížeči**

Sidebar musí zobrazit "O autorovi" jako poslední položku. Na stránce `/o-autorovi` musí být aktivní (zvýrazněná).

- [ ] **Krok 3: Commit**

```bash
git add templates/base.html.twig
git commit -m "feat: přidat odkaz na /o-autorovi do sidebar navigace"
```

---

## Task 5: E-E-A-T JSON-LD — rozšíření author ve všech šablonách

**Files:**
- Modify: `templates/ddd/*.html.twig` (19 souborů)

Všechny šablony mají `"author": { "@type": "Person", "name": "Michal Katuščák" }` bez `url` a `sameAs`. Cílem je přidat tyto pole.

Protože formátování se mezi soubory mírně liší, nejbezpečnější přístup je použít Perl pro multiline nahrazení.

- [ ] **Krok 1: Spustit hromadné nahrazení**

```bash
perl -i -0777 -pe '
s|"author":\s*\{\s*\n(\s*)"@type":\s*"Person",\s*\n\s*"name":\s*"Michal Katuščák"\s*\n\s*\}|"author": {\n$1"@type": "Person",\n$1"name": "Michal Katuščák",\n$1"url": "https://www.katuscak.cz/",\n$1"sameAs": [\n$1    "https://blog.katuscak.cz/",\n$1    "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"\n$1]\n$1}|g
' templates/ddd/*.html.twig
```

- [ ] **Krok 2: Ověřit počet změněných souborů**

```bash
grep -l '"url": "https://www.katuscak.cz/"' templates/ddd/*.html.twig | wc -l
```

Očekávaný výstup: `19`

- [ ] **Krok 3: Pokud perl nepokryl všechny soubory, doplnit ručně**

Najít soubory bez `url`:
```bash
grep -L '"url": "https://www.katuscak.cz/"' templates/ddd/*.html.twig
```

Pro každý výsledný soubor přidat ručně `"url"` a `"sameAs"` za `"name"` v bloku `author`. Vzor po úpravě:

```json
"author": {
    "@type": "Person",
    "name": "Michal Katuščák",
    "url": "https://www.katuscak.cz/",
    "sameAs": [
        "https://blog.katuscak.cz/",
        "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"
    ]
},
```

- [ ] **Krok 4: Ověřit validitu JSON-LD na jedné šabloně**

Otevřít v prohlížeči např. `/co-je-ddd`, zobrazit zdrojový kód, zkopírovat JSON-LD blok a ověřit ve [schema.org validátoru](https://validator.schema.org/).

- [ ] **Krok 5: Commit**

```bash
git add templates/ddd/
git commit -m "feat: rozšířit author JSON-LD o url a sameAs pro E-E-A-T signály"
```
