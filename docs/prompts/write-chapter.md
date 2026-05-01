# Prompt: Psaní nové kapitoly

Tento prompt řídí vytvoření nové kapitoly DDD průvodce od začátku.

**Spuštění:** „Použij docs/prompts/write-chapter.md, téma: <název tématu>"

---

## Fáze 1 — Příprava

### 1a. Přečti podklady

Přečti tyto soubory v tomto pořadí:
1. `CLAUDE.md` — zásady projektu a voice/tone pravidla (sekce „Voice, tón a jazyk")
2. `docs/MICRODATA_ARIA_GUIDE.md` — SEO a ARIA struktura šablon
3. `src/Controller/DddController.php` — seznam existujících tras a čísel kapitol
4. Tři tematicky nejbližší kapitoly z `templates/ddd/` (dle navigační struktury nebo tematické příbuznosti)

### 1b. Sestav terminologický slovník

Ze tří přečtených kapitol vypiš interně:
- Všechny DDD termíny a jejich definice tak, jak jsou použity v průvodci
- Čísla kapitol a jejich témata (pro správné cross-reference)
- Symfony verzi, kterou průvodce používá v kódových ukázkách

Tento seznam použiješ při psaní — nepoužívej alternativní definice.

---

## Fáze 2 — Faktická příprava

Před psaním prohledej web a ověř vše, o čem budeš psát.

Zdroje v pořadí důvěryhodnosti:
1. martinfowler.com
2. Oficiální dokumentace Symfony (symfony.com/doc)
3. Weby autorů: vlad.gg, eventstorming.com, teamtopologies.com
4. Google Books preview (pro citace z knih)
5. ACM Digital Library

Co ověřit:
- Kanonické definice všech klíčových pojmů tématu
- Primární zdroj každého pojmu (Evans DDD 2003 Addison-Wesley, Vernon IDDD 2013 Addison-Wesley, Khononov LDDD 2021 O'Reilly, Brandolini, Newman, Skelton & Pais)
- Přesný název a číslo kapitoly v každé citované knize
- Rok vydání a nakladatel každé citované publikace
- Aktuální Symfony API pro verzi průvodce
- PHP 8.x syntaxi používanou v průvodci

Sestav interní seznam ověřených faktů. Při psaní z něj čerpáš. Pokud fakt nemáš ověřený, nepiš ho.

---

## Fáze 3 — Psaní

### Struktura souboru

Nová kapitola musí mít přesně tuto strukturu (viz libovolná existující šablona v `templates/ddd/`):

```twig
{% extends 'base.html.twig' %}

{% block title %}<Název kapitoly> | DDD Symfony{% endblock %}
{% block meta_description %}<150 znaků, konkrétní, bez buzzwords>{% endblock %}
{% block meta_keywords %}<5–8 termínů oddělených čárkou>{% endblock %}
{% block og_type %}article{% endblock %}
{% block article_published_time %}RRRR-MM-DD{% endblock %}
{% block article_modified_time %}RRRR-MM-DD{% endblock %}
{% block breadcrumb_name %}<Krátký název pro breadcrumb>{% endblock %}

{% block structured_data %}
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "TechArticle",
      "headline": "<Název kapitoly>",
      "description": "{{ block('meta_description')|escape('js') }}",
      "keywords": "{{ block('meta_keywords')|escape('js') }}",
      "author": {
        "@type": "Person",
        "name": "Michal Katuščák",
        "url": "https://www.katuscak.cz/",
        "sameAs": [
          "https://blog.katuscak.cz/",
          "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"
        ]
      },
      "publisher": { "@type": "Person", "name": "Michal Katuščák" },
      "datePublished": "{{ block('article_published_time')|trim }}",
      "dateModified": "{{ block('article_modified_time')|trim }}",
      "image": "{{ app.request.schemeAndHttpHost }}{{ asset('images/social.png') }}",
      "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ app.request.schemeAndHttpHost }}{{ app.request.pathInfo }}"
      }
    }
    </script>
{% endblock %}

{% block body %}
<article class="article">
    {% include '_partials/article_head.html.twig' with {
        chapter_number: 'NN',
        category: '<kategorie>',
        title: '<Název kapitoly>',
        deck: '<2–3 věty úvodního shrnutí>',
        reading_time: N,
        difficulty: N,
        published: block('article_published_time'),
        last_updated: block('article_modified_time'),
        author: 'M. Katuščák'
    } %}

    {% include '_partials/article_toc.html.twig' %}

    <div class="art-body" data-chapter-number="NN">
        {# sekce #}
    </div>
</article>
{% endblock %}
```

### Pravidla při psaní textu

- Každá věta říká jednu věc. Přes 25 slov = rozdělit.
- Žádný odstavec nezačíná výplní ani fillerem (viz CLAUDE.md sekce „Zakázáno").
- Technický termín se definuje při prvním výskytu, pak se používá konzistentně bez variací.
- Kód je vždy kompletní a funkční pro Symfony verzi průvodce.
- Žádné tvrzení bez ověřeného zdroje z fáze 2. Pokud si nejsi jistý, nepiš to.
- Diagramy: vlož `{# TODO: diagram — <popis co diagram zobrazuje> #}` jako placeholder.
- Citace: `<a href="<URL>" target="_blank" rel="noopener">[N]</a>` — čísla průběžně od 1.

### Co nepsat

- Žádná vágní přídavná jména (mocný, robustní, elegantní, moderní...)
- Žádný marketing ani hype
- Žádné osobní komentáře autora
- Žádné em dashe (—) — použít en pomlčku (–) s mezerami nebo přeformulovat

---

## Fáze 4 — Vlastní kontrola před odevzdáním

Projdi napsaný text a zkontroluj každý bod:

1. **Zakázaná slova**: mentální grep na všechny vzory ze seznamu „Zakázáno" v CLAUDE.md
2. **Délka vět**: každá věta přes 25 slov — zkrátit nebo rozdělit
3. **Faktická tvrzení**: každé tvrzení cross-check s ověřenými fakty z fáze 2; co není na seznamu — buď ověřit, nebo smazat
4. **Konzistentnost termínů**: každý DDD termín — sedí s definicí ze slovníku z fáze 1?
5. **Struktura šablony**: jsou všechny povinné Twig bloky přítomny a vyplněny?

Teprve po čisté kontrole proveď:

1. Zapiš soubor do `templates/ddd/<slug>.html.twig`
2. Přidej routu do `src/Controller/DddController.php` dle existujícího vzoru:
   ```php
   #[Route('/czech-slug', name: 'route_name')]
   public function methodName(): Response
   {
       return $this->render('ddd/<soubor>.html.twig');
   }
   ```
3. Počkej na potvrzení uživatele — teprve pak commitni
