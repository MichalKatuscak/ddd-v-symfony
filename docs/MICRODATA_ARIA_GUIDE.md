# Průvodce implementací mikrodat a ARIA atributů

Tento dokument poskytuje pokyny pro implementaci mikrodat (strukturovaných dat) a ARIA atributů do vzdělávacích materiálů o Domain-Driven Design bez narušení stávajícího designu.

## Mikrodata (Strukturovaná data)

Mikrodata pomáhají vyhledávačům lépe porozumět obsahu stránek a mohou zlepšit jejich zobrazení ve výsledcích vyhledávání.

### 1. JSON-LD Mikrodata

JSON-LD je doporučený formát pro implementaci strukturovaných dat. Přidejte následující skript do hlavičky stránky:

```html
{% block structured_data %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "{{ block('title') }}",
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
  "datePublished": "2023-06-01",
  "dateModified": "{{ "now"|date("Y-m-d") }}",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ app.request.schemeAndHttpHost }}{{ app.request.pathInfo }}"
  }
}
</script>
{% endblock %}
```

### 2. Inline Mikrodata

Inline mikrodata můžete přidat pomocí atributů `itemscope`, `itemtype` a `itemprop` bez narušení designu:

```html
<article itemscope itemtype="https://schema.org/TechArticle">
  <h2 itemprop="headline">Nadpis článku</h2>
  <div itemprop="articleBody">
    <!-- Obsah článku -->
  </div>
</article>
```

## ARIA Atributy

ARIA atributy zlepšují přístupnost webu pro uživatele s asistivními technologiemi.

### 1. ARIA Role

Přidejte role k hlavním prvkům stránky:

```html
<header role="banner">
<nav role="navigation">
<main role="main">
<footer role="contentinfo">
<aside role="complementary">
```

### 2. ARIA Labelledby

Propojte nadpisy s jejich sekcemi:

```html
<section id="introduction" aria-labelledby="introduction-heading">
  <h3 id="introduction-heading">Úvod do Domain-Driven Design</h3>
  <!-- Obsah sekce -->
</section>
```

### 3. ARIA Label

Přidejte popisky k prvkům, které nemají viditelný text:

```html
<nav aria-label="Hlavní navigace">
<nav aria-label="Drobečková navigace">
<button aria-label="Zavřít">X</button>
```

### 4. ARIA Current

Označte aktuální položku v navigaci:

```html
<a href="{{ path('what_is_ddd') }}" {% if app.request.get('_route') == 'what_is_ddd' %}aria-current="page"{% endif %}>Co je DDD?</a>
```

## Příklad implementace

Zde je příklad, jak implementovat mikrodata a ARIA atributy do existující šablony bez narušení designu:

```html
{% extends 'base.html.twig' %}

{% block title %}Co je Domain-Driven Design? | DDD Symfony{% endblock %}

{% block structured_data %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "Co je Domain-Driven Design?",
  "description": "Objevte, co je Domain-Driven Design (DDD) a jaké jsou jeho základní principy.",
  "keywords": "Domain-Driven Design, DDD, Eric Evans, Ubiquitous Language, Bounded Context",
  "author": {
            "@type": "Person",
            "name": "Michal Katuščák"
  },
  "publisher": {
            "@type": "Person",
            "name": "Michal Katuščák"
  },
  "datePublished": "2023-06-01",
  "dateModified": "2023-11-15",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ app.request.schemeAndHttpHost }}{{ path('what_is_ddd') }}"
  }
}
</script>
{% endblock %}

{% block body %}
<article itemscope itemtype="https://schema.org/TechArticle">
  <h2 itemprop="headline">Co je Domain-Driven Design?</h2>
  
  <nav aria-label="Obsah kapitoly">
    <h3>Obsah kapitoly:</h3>
    <ul>
      <li><a href="#introduction">Úvod do Domain-Driven Design</a></li>
      <!-- další položky -->
    </ul>
  </nav>
  
  <div itemprop="articleBody">
    <section id="introduction" aria-labelledby="introduction-heading">
      <h3 id="introduction-heading">Úvod do Domain-Driven Design</h3>
      <p>
        Domain-Driven Design (DDD) je přístup k vývoji softwaru, který se zaměřuje na doménu, doménovou logiku a doménový model.
      </p>
    </section>
    
    <!-- další sekce -->
  </div>
</article>
{% endblock %}
```

## Testování

Pro testování mikrodat a přístupnosti doporučujeme použít následující nástroje:

1. **Google Rich Results Test** - https://search.google.com/test/rich-results
2. **Schema.org Validator** - https://validator.schema.org/
3. **WAVE Web Accessibility Evaluation Tool** - https://wave.webaim.org/
4. **Axe DevTools** - https://www.deque.com/axe/

## Další zdroje

- **Schema.org** - https://schema.org/
- **WAI-ARIA Authoring Practices** - https://www.w3.org/TR/wai-aria-practices/
- **Google Search Central: Structured Data** - https://developers.google.com/search/docs/advanced/structured-data
