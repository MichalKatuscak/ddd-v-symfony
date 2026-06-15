# Vizuální audit – nálezy

Prostředí: PHP 8.4 (Docker, php:8.4-cli), Vite build, http://localhost:8000, router.php pro správné MIME.
Viewporty: desktop 1440×900, mobil 390×844 (Playwright). Screenshoty v `audit/`.

## Závažné nálezy (opraveno)

### 1. CRLF rozbíjí VŠECHNY `:::` bloky kapitol (kód, callout, diagram, FAQ) — OPRAVENO
- **Příčina:** `git core.autocrlf=true` převede na Windows checkoutu LF→CRLF. `ChapterMarkdownParser::extractTopLevelBlocks` používá regex `^:::(\w+)...$`, který na `\r` selže. Bloky se nevyrenderovaly — v HTML zůstaly literály `:::code`/`:::callout` a kód se vysypal jako surové, neescapované HTML (`<?php`, `<DomainEvent>` parsováno jako tag → požírání textu).
- **Rozsah:** všech 26 kapitol (cqrs mělo 22× `:::code`, 38× `:::callout` jako literál).
- **Produkce (Linux/LF) nebyla zasažena**, ale jakýkoli Windows checkout (default autocrlf) renderuje rozbitě.
- **Oprava:** `src/Content/ChapterMarkdownParser.php` — normalizace `\r\n`/`\r` → `\n` hned po `file_get_contents`. (Robustní, bezpečné.)
- **Doporučení navíc:** přidat `.gitattributes` s `*.md text eol=lf` (a `*.css`, `*.twig`), aby konce řádků byly stabilní napříč OS.

### 2. Mobilní horizontální přetékání u holých fenced bloků (``` ```) — OPRAVENO
- **Příčina:** commonmark renderuje ```` ``` ```` jako prosté `<pre><code>` bez `.code-body` wrapperu, takže bez `overflow-x`. Dlouhý řádek (např. event-stream příklad na `/event-sourcing`) roztáhl layout.
- **Oprava:** `assets/styles/article.css` — `.art-body pre:not(.code-body){ overflow-x:auto; max-width:100% }`.
- Po opravě: 0/35 stránek přetéká na 390px.

## Prostředí (mimo kód projektu)
- Chyběl `league/commonmark` stack ve `vendor/` → kapitoly vracely 404 (controller nešel instancovat). Doinstalováno `composer install` (5 balíčků). Pokud je vendor commitovaný, doplnit; jinak OK.

## Ověřeno bez vad
- Homepage desktop i mobil (hero, TOC, „Jak číst", case-study CTA, patička).
- Mobilní hamburger menu (drawer zprava, ztmavené pozadí).
- Vyhledávací modal: živé výsledky, zvýraznění, kategorie.
- Kapitola: code blok (syntax highlight, čísla řádků, badge, Kopírovat, zvýrazněné řádky), callout (note/pattern/warn/anti), diagram (SVG + zoom ovládání).
- Nav-drawer (position:fixed off-canvas) — NEzpůsobuje přetečení (dřívější podezření vyvráceno; šlo o důsledek CRLF bugu).

## Ověřeno bez vad (dokončeno)
- Hub stránky (desktop i mobil): titulek, metadata, 2sloupcová mřížka → 1 sloupec na mobilu.
- Glosář: filtr (živé filtrování, „1 pojem z 63"), kategorie, citace.
- Cheat-sheet: rozhodovací tabulka s odkazy.
- O autorovi: bio karta (foto, odkazy, sekce).
- Zdroje: seznam literatury s metadaty a externími odkazy.
- Security-policy: KONTAKT callout, sekce, seznamy.
- 404: velký „404", CTA, plná hlavička/patička.
- FAQ blok: akordeon (rozbalí odpověď, „+/−" ikona, odkazy).
- Sticky hlavička + reading progress bar: po odscrollování sedí pod hlavičkou (neplave nad textem).
- Mobilní horizontální přetékání: 0/35 stránek na 390px.
- Konzole: bez chyb (mimo očekávané 404 resource u neexistující URL).

## Prevence (přidáno)
- `.gitattributes` — `* text=auto eol=lf` + explicitní LF pro `.md/.twig/.css/.js/.php/.yaml/.json`.
  Drží konce řádků na LF napříč OS. Vedlejší efekt: vyřešil i „phantom" modifikaci
  `templates/base.html.twig` (šlo jen o CRLF rozdíl).

## Pomocné soubory auditu
- `router.php` — dev helper pro `php -S` (správné MIME statických assetů). Pro Symfony CLI / nginx není potřeba; lze smazat.
- `audit/*.jpeg` — screenshoty z auditu.
