# Audit AI optimalizace — 21. 4. 2026

Dokument popisuje, co bylo na webu upraveno v rámci auditu AI optimalizace a proč. Audit vycházel z obecného checklistu, nikoli z požadavků šitých na míru tomuto webu — některé body proto nebyly aplikovány, viz níže.

## Co bylo změněno

### 1. robots.txt — explicitní povolení AI crawlerů

**Soubor:** `public/robots.txt`

Původní obsah povoloval pouze obecného `User-agent: *`. Přidány byly explicitní sekce pro AI boty:

- `GPTBot` — OpenAI (trénink modelu)
- `ChatGPT-User` — ChatGPT (browse with Bing / vyhledávání)
- `OAI-SearchBot` — OpenAI SearchGPT
- `ClaudeBot` — Anthropic (trénink i vyhledávání)
- `PerplexityBot` — Perplexity AI
- `Google-Extended` — Google Gemini / Vertex AI
- `CCBot` — Common Crawl (používá více LLM pro trénink)

**Proč:** Ačkoli `User-agent: *` s `Allow: /` AI boty technicky povoluje, některé firmy (zejména OpenAI a Google) doporučují explicitní identifikaci. Explicitní záznamy eliminují nejednoznačnost a signalizují, že web je vědomě otevřený AI.

### 2. public/llms.txt — mapa obsahu pro LLM

**Soubor:** `public/llms.txt` (nový)

Vytvořen podle specifikace [llms.txt](https://llmstxt.org/) jako strukturovaný markdown s odkazy na všech 20 kapitol seskupených do logických sekcí (Začátečníci, Pokročilí, Praxe, AI, Reference, O webu) s krátkými popisy.

**Proč:** `llms.txt` je vznikající konvence pro LLM: poskytuje kompaktní, strojem snadno zpracovatelnou mapu obsahu webu. Slouží k tomu, aby modely lépe rozuměly struktuře webu a mohly přesněji odkazovat.

### 3. article:published_time a article:modified_time (OG meta tagy)

**Soubor:** `templates/base.html.twig`

V `<head>` přibyly podmíněné Open Graph meta tagy pro datum publikace a aktualizace článku. Implementace využívá Twigové bloky `article_published_time` a `article_modified_time`, které jsou zachycené pomocí `{% set %}...{% endset %}` — díky tomu:

- blok je deklarovaný v parent šabloně, ale nerenderuje se jako viditelný text,
- podstránky (articles) hodnotu nastavují, homepage/about bloky vynechávají a meta tagy se na nich nevygenerují,
- pokud jsou hodnoty vyplněny, přidává se i `article:author`.

Současně byl `og:type` převeden na přepínatelný blok (`website` default, `article` na článkových šablonách).

**Proč:** Data v JSON-LD (`datePublished`, `dateModified`) už existovala, ale meta tagy `article:*` jsou samostatný signál používaný Facebookem, Slackem, některými AI crawlery i tradičními scraper engine. Duplikace informace v OG tagu a JSON-LD je záměrná — různí konzumenti čtou různé zdroje.

### 4. Viditelné datum aktualizace pro uživatele

**Soubory:**
- `templates/_partials/article_meta.html.twig` (nový)
- `assets/styles/modern-style.css` (přidán blok `.article-meta`)
- 18 článkových šablon (`templates/ddd/*.html.twig`) — include hned za `<h1>`

Vznikl opakovaně použitelný Twig partial, který renderuje řádek `Publikováno: DD. M. YYYY · Aktualizováno: DD. M. YYYY` s `<time>` tagy a `itemprop="datePublished"` / `itemprop="dateModified"` pro microdata. Partial přijímá ISO datum jako parametry.

**Proč:** Datum aktualizace v JSON-LD je strojový signál. Viditelný text je lidský i další strojový signál (E-E-A-T): pro uživatele ukazuje aktuálnost obsahu, pro Google je to jeden z faktorů „Freshness", pro AI asistenty je to kontext pro citaci. Zároveň microdata `itemprop="datePublished/dateModified"` umocňují validaci strukturovaných dat.

### 5. Rozšíření WebSite schema — autor a publisher

**Soubor:** `templates/base.html.twig`

Globální `WebSite` JSON-LD měl dosud `author` a `publisher` jen se jménem. Doplněno:

- `"url": "https://www.katuscak.cz/"`
- `"sameAs": ["https://blog.katuscak.cz/", "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"]`

**Proč:** Identita autora je jedním z pilířů E-E-A-T. Strukturované odkazy na externí profily (`sameAs`) umožňují Googlu, Bingu i LLM propojit tento web s jinými projekty autora a potvrdit autorství. Stejné rozšíření už bylo na úrovni článků, teď je konzistentní i globálně.

### 6. og:locale

**Soubor:** `templates/base.html.twig`

Přidán tag `<meta property="og:locale" content="cs_CZ">`.

**Proč:** Explicitní signál jazykové lokalizace pro Open Graph konzumenty. Atribut `lang="cs"` na `<html>` už existoval, ale OG má vlastní prostor a Facebook/LinkedIn/Slack preview tuto hodnotu používá.

## Co nebylo aplikováno a proč

### FAQPage strukturovaná data

**Rozhodnutí:** Nepřidávat.

Checklist FAQPage doporučuje pro stránky s otázkami. Zvažované kandidáty:

- `/kdy-nepouzivat-ddd` — má 7 číslovaných sekcí „1. CRUD admin", „2. Startup" apod. Nejsou to explicitní otázky a odpovědi, ale klasifikace situací.
- `/ddd-v-praxi-kde-to-boli`, `/ddd-a-umela-inteligence` — dlouhé článkové texty se strukturovanými nadpisy, ne FAQ.

[Google Search Central — FAQ Page](https://developers.google.com/search/docs/appearance/structured-data/faqpage) vyžaduje, aby stránka obsahovala **explicitní seznam otázek a odpovědí**, ne článkový obsah. Natažení FAQPage na článkovou strukturu je proti pokynům a může vést k označení jako structured-data spam.

### NAP (jméno, adresa, telefon) v Organization

**Rozhodnutí:** Nepřidávat.

Checklist doporučuje pro firmy. Web je osobní projekt jednoho autora bez veřejně publikované adresy nebo telefonu. Místo firemního `Organization` schema je použit `Person` s `url` a `sameAs`, což je správná volba pro osobní web.

### Cloudflare whitelist pro AI boty

**Rozhodnutí:** Mimo rozsah.

Web neběží za Cloudflare, tato konfigurace se ho netýká.

### Product / Review / AggregateRating

**Rozhodnutí:** Mimo rozsah.

Vzdělávací web bez produktového katalogu. Toto schema nemá relevantní obsah.

### Server logy AI botů

**Rozhodnutí:** Nelze ověřit z kódu.

Ověření, že AI boti web skutečně navštěvují, vyžaduje přístup k produkčním logům mimo repozitář. Tento bod zůstává jako provozní kontrola mimo rozsah commitu.

## Stav checklistu po auditu

| Bod | Stav před | Stav po |
|---|---|---|
| robots.txt povoluje AI boty | implicitně přes `*` | explicitně pro 7 AI botů |
| llms.txt | chyběl | hotový |
| sitemap.xml s lastmod | existoval | beze změny |
| Organization/WebSite se sameAs | WebSite bez sameAs | WebSite s url + sameAs pro Person |
| Article/BlogPosting s autorem a daty | hotový | beze změny (dateModified nově i v OG tagu) |
| BreadcrumbList | hotový | beze změny |
| Autor jako Person se sameAs | hotový (na článcích) | rozšířen i do globálního WebSite schema |
| FAQPage | chyběl | úmyslně nepřidán (viz výše) |
| Jedna H1, hierarchie H2/H3 | hotová | beze změny |
| Sémantické tagy | hotové | beze změny |
| Obsah v HTML, ne JS | hotové | beze změny |
| Alt texty | hotové | beze změny |
| article:published_time / modified_time | chyběly | přidány |
| Viditelné datum aktualizace | chybělo | přidáno (pro lidi i microdata) |
| Autorská stránka s bio a externími odkazy | hotová | beze změny |

## Další doporučené kroky (mimo tento commit)

1. **Monitorovat server logy** na přítomnost AI botů (GPTBot, ClaudeBot, PerplexityBot) — běžně do 2–4 týdnů od publikace `llms.txt` a úpravy `robots.txt`.
2. **Validovat strukturovaná data** přes [Rich Results Test](https://search.google.com/test/rich-results) a [Schema Markup Validator](https://validator.schema.org/) — pro ověření, že úpravy JSON-LD jsou validní.
3. **Sledovat Search Console** na „Enhancements → Articles" — tam by se měly objevit upravené datumy.
