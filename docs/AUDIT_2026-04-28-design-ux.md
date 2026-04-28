# Audit hloubkového design+UX — 2026-04-28

Doplnění publikačního auditu z 27.4. Cílem je zachytit dimenze, které tam nebyly pokryty
do hloubky: vizuální konzistence napříč šablonami, typografie a rytmus, spacing systém,
interakce a stavy, mobilní detaily, IA-flow, komponentový chrome.

Severita:
- **P0 — publikační blokátor**: nelze pustit ven; jasně rozbité, viditelné na první pohled.
- **P1 — důležité**: sníží kvalitu vůči seniornímu publiku, ale nezpůsobí ostudu.
- **P2 — leštění**.

Dimenze:
- **D** Vizuální konzistence komponent
- **T** Typografie a vertikální rytmus
- **S** Spacing systém
- **I** Interakce a stavy
- **M** Mobilní detaily
- **N** Navigace a IA-flow
- **C** Komponenty a chrome
- **R** Recovery / data integrita

---

## P0 — Publikační blokátory

### Recovery / data integrita

- **R-P0-1** **(VYŘEŠENO commit `21256b5`)** `templates/ddd/{cqrs,event_sourcing,sagas,implementation_in_symfony,performance_aspects,testing_ddd,ddd_pain_points,anti_patterns,when_not_to_use_ddd,case_study}.html.twig` — 168 code bloků obsahovalo literály `PLACEHOLDER0`/`PLACEHOLDER1`/… místo skutečného PHP/YAML/SQL kódu. Příčina: publikační audit z 27.4. (commit `a94ff69`) protegoval `{% set _code %}…{% endset %}` regiony NUL-bracketovanými placeholdery při hromadné náhradě ASCII pomlček v próze a nikdy je nevrátil zpět. Pre-audit kód existoval v gitové historii na commitu `07e554d`. Recovery skript `scripts/recover-placeholders.py` vrátil 166 placeholderů z gitu (mapování indexem) a odstranil 2 phantom callouty v `event_sourcing.html.twig` (Outbox relay + outbox_position SQL — sekce přidané auditem bez původního zdroje kódu). Verifikováno: `lint:twig` a `lint:container` zelené, smoke render všech 10 dotčených stránek vrací HTTP 200, 0 PLACEHOLDER tokenů ve výstupu.

### Vizuální rendering

- **D-P0-1** `assets/styles/article.css:283–344` + `assets/styles/article.css:179–222` — tabulky uvnitř callouts se vizuálně ořezávají na desktopu i tabletu. Reprodukce: `/cqrs` sekce 05.02 „CQS vs. CQRS — přehled", tabulka uvnitř `.callout-pattern` má `<div class="table-responsive">` wrapper, ten má `overflow-x: auto`, ale šířka tabulky (sloupce „Aspekt | CQS | CQRS" se širokými PHP příklady jako `RegisterUserHandler a GetUserProfileHandler pracují s různými datovými strukturami`) přesahuje `1fr` callout body (700 px max-width art-body − 36 px callout rail = 664 px). Uživatel vizuálně vidí oříznutý text na pravé straně bez scroll-shadow indikátoru. Buď zúžit sloupce, nebo pro tabulky uvnitř callouts přidat scroll-shadow / hint, nebo přesunout tabulku ven z callout (tabulky jsou primární obsah, callout je rámeček pro doplňky).

---

## P1 — Důležité

### Typografie a vertikální rytmus

- **T-P1-1** `templates/ddd/*.html.twig` — **287 list itemů s ASCII pomlčkou** v separátoru po `</strong>`. Příklad z `cqrs.html.twig:1198`: `<li><strong>Upsert/Merge</strong> - <code>…</code>`. Audit z 27.4. (T-P1-3) opravil 389 výskytů ASCII pomlčky v próze, ale list-itemový pattern `</strong> - ` tudy neprošel (regex prose dashes míří na ` - ` mezi slovy, ne na ` - ` po `</strong>`). Distribuce: `what_is_ddd: 63`, `cqrs: 39`, `event_sourcing: 38`, `implementation_in_symfony: 37`, `testing_ddd: 24`, `case_study: 23`, `sagas: 15`, `anti_patterns: 15`, `horizontal_vs_vertical: 9`, `basic_concepts: 8`, `when_not_to_use_ddd: 7`, `glossary: 4`, `ddd_pain_points: 3`, `performance_aspects: 2`. V kombinaci s 20 list-itemy, které už en pomlčku používají, jasně dominuje stará varianta. Sjednotit přechodem na en pomlčku s mezerami.

- **T-P1-2** `templates/ddd/glossary.html.twig` — uvnitř `<dd>` (definic glosáře) zůstávají ASCII pomlčky v textu i tam, kde audit prošel. Příklad z `Doména` hesla: `Doména není totéž co aplikace samotná - doména existuje i bez softwaru`. Příklad ze `Subdoména`: `(Core Domain) - zdroj konkurenční výhody;`, `(Supporting Subdomain) - nutná pro fungování, ale ne jako zdroj výhody;`. Glosář byl auditem dotčen (T-P1-4 klíčové shluky), ale prózová náhrada ASCII → en pomlček uvnitř `<dd>` nebyla úplná.

- **T-P1-3** `assets/styles/hub.css` — **24 hardcodovaných `font-size`** mimo token systém: 56 px (h1), 36 px (h1 mobile, foot-h), 22 px (sect-title), 16 px (deck), 15 px (card-t), 14 px (mark), 13 px (foot-list), 12 px (sect-num, card-n, sect-sub, foot-list, foot-spec), 11.5 px (card-d), 11 px (meta, sub, foot-mark, foot-base), 10.5 px (eyebrow, card-meta, foot-h4, foot-imprint), 10 px (meta-k, card-time, foot-spec-dt, foot-rights). Tokeny `--t-xs/sm/base/md/lg/xl/2xl/3xl/4xl` (11/13/16/18/22/28/36/48/72) jsou definovány ale ignorovány. Hodnoty 10/10.5/11.5/12/14/15 nemají odpovídající token, hodnoty 11/13/16/22/36 mají token, ale nejsou na něj navázány. Drift mezi article (kde tokeny jsou používány) a hub/landing (kde nejsou) je systematický.

- **T-P1-4** `assets/styles/landing.css` — **15 hardcodovaných `font-size`** mimo token systém: 19 px (deck), 16 px (toc-title), 14 px (author-name), 13 px (toc-n), 12 px (path, skip), 11.5 px (toc-desc), 11 px (author-role, mark), 10.5 px (eyebrow, path-label, path-tag, toc-num, toc-meta, toc-side, toc-tag), 10 px (toc-tag, path-time). Stejný problém jako T-P1-3.

- **T-P1-5** `assets/styles/{hub,landing,article,chrome}.css` — **hardcodované line-heighty** mimo token systém: hub 1.05/1.1/1.4/1.5/1.65; landing 1.02/1.05/1.4/1.65; chrome 1.1; article hardcodovaný `font-size: 13px` v `.code-body` místo `var(--t-sm)`. Token `--lh-tight/snug/normal/loose/code` (1.15/1.3/1.55/1.7/1.65) je definován jen pro article. Sjednotit, případně rozšířit token sadu o `--lh-display: 1.05` pro display nadpisy.

- **T-P1-6** `assets/styles/landing.css:32` + `hub.css:31` — **`letter-spacing`** je hardcodován jako `-0.026em`, `-0.022em`, `-0.012em`, `-0.008em`, `-0.005em` napříč hub/landing/article. Tokeny `--tracking-tight/normal/wide/mono` (-0.022 / 0 / 0.04 / -0.005) pokrývají jen 3 z 5 hodnot. Article dodržuje tokeny, hub/landing ne.

- **T-P1-7** `templates/ddd/{about,security_policy,resources}.html.twig` — sekční číslování používá písmena `A/B/C` zatímco glossary používá `01/02/...` a kapitoly `NN.MM`. Tři různé schémata pro tři typy stránek. Buď sjednotit (např. všechny ne-kapitoly písmeny), nebo dokumentovat záměr.

### Vizuální konzistence komponent

- **D-P1-1** `assets/styles/article.css:339, :393, :496, :824` — **podtržení odkazů uvnitř komponent zůstává `var(--accent-dim)`** (nízká alfa, ≈ 1.4 : 1 kontrast podtržení proti pozadí), zatímco audit z 27.4. (A-P1-8) opravil podtržení v `.art-body a` na plný `var(--accent)`. Komponenty s nedotaženou opravou: `.callout-body a`, `.bio-links a`, `.glossary-entry dd a`, `.faq-answer a`. Příklad: na `/o-autorovi` je sekce s odkazy `katuscak.cz / blog.katuscak.cz / LinkedIn` v `.bio-links` — podtržení je téměř neviditelné. Uživatel s běžným zrakem na 1280 px monitoru nemusí poznat, že jsou to odkazy.

- **D-P1-2** `templates/_partials/article_meta.html.twig` + nepřítomný CSS pro `.article-meta`/`.article-meta__item`/`.article-meta__label`/`.article-meta__sep` — **dead code partial**. Žádná šablona ho nezahrnuje (`grep -rn _partials/article_meta` vrací nulu), žádné CSS pravidlo třídy nepoužívá. Buď použít (např. v `article_head.html.twig` k zobrazení i `published` data, ne jen `last_updated`), nebo smazat.

- **D-P1-3** `templates/_partials/article_head.html.twig` — partial nezobrazuje **datum publikace** (`published`), pouze `last_updated`. JSON-LD ovšem `datePublished` má. Pro čtenáře užitečné vědět, že kapitola vznikla v dubnu 2025 a byla aktualizována v dubnu 2026; aktuálně vidí jen druhé. Doplnit `published`/`first_published` parametr a v meta-bloku zobrazit jako 5. řádek (případně sloučit do jediného řádku „Publikováno X · Aktualizováno Y").

- **D-P1-4** `templates/ddd/about.html.twig:45` — `<aside class="bio-card">` je sémanticky nesprávný element. Bio-card je primární obsah stránky o autorovi, ne tangenciální. Mělo by být `<section class="bio-card">` nebo `<div class="bio-card">`. Pro screen reader uživatele se aside často oznamuje jako vedlejší obsah, což je matoucí.

- **D-P1-5** `templates/_partials/callout.html.twig` (`.callout`) vs `assets/styles/article.css:407` (`.note`) — **dvě podobné komponenty s rozdílným chrome**. `.callout` má rail s glyfem a vertikálním labelem; `.note` má jen levý border + interní `<h3>` s mono-uppercase nadpisem. Glossary používá `.note` na sekce typu „Co jsou strategické vzory?", chapters používají `.callout` typu `note` pro analogický účel. Buď sjednotit do jedné komponenty, nebo dokumentovat kdy `.note` (uvnitř glosáře) a kdy `.callout` (mimo glosář).

- **D-P1-6** `assets/styles/article.css:270, :828` + `assets/styles/landing.css:54` + (callout-body) — **inline-code styling deklarovaný 4×**. Při změně designu inline-code se musí ladit na čtyřech místech. Audit z 27.4. (C-P2-3) toto označil, neopravil. Konsolidovat do jediného `.ic, code` selektoru s globální platností.

### Spacing systém

- **S-P1-1** `assets/styles/{article,hub,landing}.css` — **tři různé page max-widths**: `.article` 1180 px, `.hub-a-shell` `var(--canvas-max)` (1280 px), `.land-hero`/`.land-featured` 1480 px. Žádný token pro page max-width. Buď tokenizovat jako `--page-narrow/standard/wide` (1080/1280/1480), nebo sjednotit.

- **S-P1-2** `assets/styles/{article,hub,landing,chrome}.css` — **breakpointy nadále nejednotné**. Po 27.4. auditu chrome (900) a article (540 + 900) sjednoceny. Hub stále má 720/880/1080, landing 540/900. Sjednotit hub na 540/720/900 (případně přidat 1080 jako page-wide breakpoint pro foot-cols 4-col → 2-col).

- **S-P1-3** `assets/styles/landing.css:11` vs `assets/styles/hub.css:10` vs `assets/styles/article.css:8` — **shell padding nejednotný**: landing `var(--s-9) var(--s-7) var(--s-8)` (96/48/64), hub `var(--s-7) var(--s-6) var(--s-8)` (48/32/64), article `var(--s-7) var(--s-5) var(--s-9)` (48/24/96). Tři různé vertikální rytmy mezi nadřazenou navigací a obsahem stránky. Sjednotit alespoň horní padding (48 px).

### Interakce a stavy

- **I-P1-1** `assets/styles/{article,landing,hub,chrome}.css` — **transition timings nejednotné**: 120 ms (většina), 160 ms (`.btn-arrow`), 180 ms (`.toc-m-chev`), 200 ms (`.nav-drawer`). Bez tokenu `--motion-fast/medium/slow`. Sjednotit na 2–3 hladiny v tokenech.

- **I-P1-2** `assets/styles/article.css:97–116` (TOC list) — **focus-visible existuje pro `.toc-list a`** ale `.toc-list a` má `display: contents`, takže outline obtéká text linku, ne grid cell s číslem. Při tabování přes TOC vidí uživatel jen rámeček kolem textu nadpisu, číslo zůstává mimo. Buď přepnout na `display: grid` na `<a>` a outline kolem celé položky, nebo upravit outline-offset.

- **I-P1-3** `assets/scripts/code-block.js` (kopírovací tlačítko) — chybí vizuální stav „copied" jiný než text. Po stisku se label změní na „zkopírováno ✓" s `.code-copied` třídou (`color: var(--state-ok)`), ale tlačítko nezůstává nijak vizuálně odlišné. Při rychlém klikání může uživatel pochybovat, jestli akce proběhla.

### Mobilní detaily

- **M-P1-1** `templates/_partials/article_head.html.twig:18` (`.art-meta` grid) — na 390 px se 4-sloupcový meta-grid skládá na 2×2 (audit fix A-P1-1 sjednotil breakpointy). Resources a glossary mají jen 2 položky (Autor, Aktualizace) — zobrazují se jako 2 sloupce vedle sebe ale s velkým prázdným místem. Pro malou položkovou hustotu by bylo lepší jednořádkové zobrazení nebo `auto-fit`.

- **M-P1-2** `assets/styles/article.css:740` — diagramy na `<= 540 px` mají `min-width: 720 px` + horizontální scroll. Bez vizuálního scroll-shadow indikátoru uživatel nevidí, že může scrollovat. Audit 27.4. D-P2-5 to označil, ponecháno k řešení. Přidat fade-out shadow z pravé strany, nebo `overflow-x: scroll` se zviditelněnou scrollbar.

- **M-P1-3** `templates/base.html.twig:88` — mobile drawer otevírá hamburger menu. Drawer obsahuje 5 položek (4 huby + O autorovi). Chybí kontextový aktivní state pro sub-routy: na `/cqrs` je aktivní „Vzory" (správně), ale uvnitř drawer položky „Vzory" nemá vizuální zvýraznění. CSS má `.nav-drawer-list a.active { color: var(--accent); }`, takže Twig {% if %} kontextu by mělo fungovat — ověřit a opravit, pokud aktuálně neaktivuje.

- **M-P1-4** Mobilní vykreslení listů uvnitř callouts (např. `/cqrs` 390 px scroll na cca 3000 px) — bullety obsahují ASCII pomlčku v separátoru („Oddělené handlery - Command handlers..."), což je čitelné, ale na úzké šíři při wrappingu vznikají vizuální mezery, kdy `-` zůstává na konci řádku osamoceně. Po opravě T-P1-1 (en pomlčka s mezerami) se to vyřeší.

### Navigace a IA-flow

- **N-P1-1** `templates/ddd/*.html.twig` (kapitoly) — **chybí navigace „další kapitola"**. Po dočtení kapitoly 05 (CQRS) musí uživatel scrollovat zpět nahoru, otevřít hamburger nebo skočit přes hub. Pro lineární čtení od začátku do konce je to třecí bod. Doplnit blok na konec art-body: `← 04 Implementace v Symfony 8 | 06 Event Sourcing →` před FAQ sekcí.

- **N-P1-2** `templates/ddd/*.html.twig` — **glosář není automaticky linkovaný** z těla kapitol. Termín „Bounded Context" se zmiňuje v desítkách kapitol; existuje glosářové heslo `term-ohraniceny-kontext`, ale chapters na něj odkazují manuálně, převážně neodkazují. Buď přidat manuální odkazy v pre-pass na první výskyt termínu na každé stránce, nebo zavést Twig `dictionary_link` filter.

- **N-P1-3** `templates/ddd/cqrs.html.twig` (a další) — kapitoly mezi sebou linkují *přírůstkově* (v textu „CQRS je často používán v kombinaci s Event Sourcingem" → odkaz na `/event-sourcing`), ale linkování není systematické. Některé referencované kapitoly nejsou linkované, jiné jsou. Cross-link audit by stál samostatnou vlnu.

### Komponenty a chrome

- **C-P1-1** `templates/_partials/diagram.html.twig` + `assets/styles/article.css:700` — diagram chrome má `.diagram-head` s `FIG. NN.M-X` číslem a popisem. Některé kapitoly (`event_sourcing.html.twig` 06.06 projekce) mají k diagramu i `.diagram-caption` (figcaption), jiné (`cqrs.html.twig` 05.05 messenger bus) ne. Bez jasného rozhodnutí o tom, kdy caption ano a kdy ne.

- **C-P1-2** `templates/ddd/cqrs.html.twig:1010-1013` (06.05 OrderSummaryProjector) — code blocky s velmi dlouhými řádky (`new \DateTimeZone('UTC')` přesahuje 80 znaků) se na 1280 px ořezávají, mají `overflow-x: auto`, ale chybí scroll-shadow. Stejný issue jako M-P1-2 ale pro desktop.

- **C-P1-3** `assets/styles/hub.css:329–335` (`.foot-a-cols`) — 4-sloupcový footer kolapsuje na 2-col na 1080 px a 1-col na 720 px. Při 4 sloupcích na 1080 px a 720+ px je hraniční oblast, kdy je kolaps zbytečně agresivní; můžeme přejít na 2-col až na 720 px.

---

## P2 — Leštění

### Typografie

- **T-P2-1** `assets/styles/tokens.css:50-58` — token `--t-4xl: 72px` deklarován ale nepoužit (audit 27.4. C-P2-7). Landing zapisuje raw `clamp(40px, 11vw, 84px)`. Buď využít token jako základ, nebo smazat.

- **T-P2-2** `assets/styles/article.css:271` (`.ic, .art-body code`) — `font-size: 0.88em` relativní k rodiči. Uvnitř callout-body, faq-answer a glossary dd jsou tělové texty `var(--t-sm)` (13 px), takže inline-code = 11.4 px. To je pod běžné minimum pro mono čitelnost. Zvážit absolutní hodnotu `var(--t-sm)` nebo zvýšit na 0.92em pro nečitelný kontext.

- **T-P2-3** `templates/ddd/glossary.html.twig` — page ranges v `term-source` blocích ASCII („str. 2-3"). Většinu už audit opravil na en pomlčku, ale 2 výskyty zůstávají. Mechanická oprava.

### Vizuální

- **D-P2-1** Téměř všechny popisky barev v citačních blocích `.term-source` a `.foot-a-rights` jsou `var(--fg-dim)` (≈ 5.1 : 1 kontrast po 27.4. A-P0-4). To prošlo WCAG AA, ale font 10–11 px je hraniční. Pro malý font lépe 7 : 1.

- **D-P2-2** `templates/_partials/hub.html.twig:43` — šipka v hub kartě `→` je v kódu jako prostý znak, nikoli SVG. To znamená, že může mít rozdílnou tloušťku napříč fonty/platformami. Není to vada, jen možné nepředvídatelné renderování na uživatelských OS bez Inter pro tyto šipky.

- **D-P2-3** `templates/_partials/article_head.html.twig:33` (`.diff-bar`) — vizuální indikátor obtížnosti pomocí čtyř obdélníčků. Pro daltonisty (deuteranopia) může splývat zapnutý/vypnutý stav (oranžová vs šedá). Doplnit `aria-label` nebo viditelný text vedle (už je „pokročilá" — možná stačí, ale ověřit).

### Spacing

- **S-P2-1** `assets/styles/article.css:269` (`.ic, .art-body code`) — `padding: 1px 5px` hardcodované. Nepoužívá `var(--s-1)` (4 px). Drobnost.

- **S-P2-2** `assets/styles/article.css:5` `.article` `padding: var(--s-7) var(--s-5) var(--s-9)` (48/24/96). Spodní 96 px je velký, vznikne tak vakuum mezi posledním obsahem a footerem. Posoudit, zda 64 px (`--s-8`) není dostatečné.

### Komponenty

- **C-P2-1** `templates/_partials/code_block.html.twig:31` — copy button label `Kopírovat`/`Zkopírovat kód` (aria-label) má rozdílný text. `aria-label` má sloveso v dokonavém vidu, viditelný label v nedokonavém. Sjednotit.

- **C-P2-2** `assets/scripts/article-toc.js` — TOC fillne se po načtení DOMu z `<h2 class="h-section">`. Pokud kapitola nemá h2 (např. about má jen 2 sekce), TOC zobrazí ty 2 položky korektně. Ale pokud je sekce přidaná dynamicky (žádný náš případ), TOC by se neaktualizoval. Není problém pro statický web.

### IA / drobnosti

- **N-P2-1** `templates/ddd/index.html.twig:155` (featured) — link na případovou studii má `aria-label="Otevřít případovou studii: e-shop end-to-end"`. Na mobilu (390 px) je `<span class="btn btn-ghost" aria-hidden="true">Otevřít →</span>` v rámci linku, což je pseudo-tlačítko schované screen-readeru. Nadbytečnost — jelikož celá karta je link, tlačítko vizuálně značí akci a aria-label nese texturu samotného linku. OK, ale lze zvážit zjednodušit.

- **N-P2-2** `templates/base.html.twig:50` — skip-link „Přejít na obsah" cílí na `#content`. To funguje, ale na long pages by se hodil i druhý skip-link „Přejít na FAQ" nebo „Přejít na obsah kapitoly". Nice-to-have.

### CSS

- **C-P2-3** `assets/styles/article.css:114` `.toc-current { color: var(--fg) !important; }` — `!important` lze vyřešit specificitou (audit 27.4. C-P2-1 přebíraná).

- **C-P2-4** `assets/styles/article.css:436, :440, :441` — tři další `!important` v `.term-source { margin-top: var(--s-3) !important; }`. Přepsat specificitou (audit 27.4. C-P2-1 přebíraná).

---

## Souhrnná čísla

| Severita | Položek |
|----------|---------|
| P0       | 2 (R-P0-1 hotovo, D-P0-1 otevřeno) |
| P1       | 21      |
| P2       | 11      |
| **Celkem** | **34** |

## Doporučený postup (návrh vlnového plánu)

| Vlna | Obsah | Odhad |
|------|-------|-------|
| **0** (HOTOVO) | R-P0-1 — recovery PLACEHOLDER → kód | 1 commit |
| **1** | Auto-fix triviálních: T-P1-1 (287 list-itemů), T-P1-2 (glossary `<dd>`), I-P1-1 (transition timings token), C-P2-1 (aria-label), T-P2-3 (str. ranges), C-P2-3,4 (!important) | 1 commit |
| **2** | Vizuální konzistence: D-P0-1 (table-overflow uvnitř callouts), D-P1-1 (link underline accent-dim → accent), D-P1-2 (article_meta dead code), D-P1-3 (published date), D-P1-4 (about aside → section), D-P1-6 (inline-code konsolidace), D-P1-5 (`.note` vs `.callout`) | 1 commit |
| **3** | Typografie & rytmus: T-P1-3, T-P1-4, T-P1-5, T-P1-6 — sjednotit hub/landing na tokeny | 1 commit |
| **4** | Spacing & breakpointy: S-P1-1, S-P1-2, S-P1-3, C-P1-3 | 1 commit |
| **5** | Mobilní polish: M-P1-1, M-P1-2, M-P1-3, M-P1-4, I-P1-2, I-P1-3, C-P1-2 | 1 commit |
| **6** | Navigace / IA: N-P1-1 (next chapter), N-P1-2 (glossary linking), N-P1-3 | 1 commit |
| **7** | Diagram & hub chrome: C-P1-1 (caption konzistence), N-P2-1, N-P2-2, T-P1-7 | 1 commit |
| **8** | P2 leštění: D-P2-*, S-P2-*, C-P2-2, T-P2-1,2 | 1 commit |

Každá vlna končí vyhodnocením a zápisem stavu (✓ / ⊘ / odloženo) přímo do tohoto dokumentu.

---

## Použité metody auditu

- Čtení CSS systému (`tokens.css`, `base.css`, `fonts.css`, `chrome.css`, `article.css`, `hub.css`, `landing.css`, `hljs-theme.css`).
- Čtení 9 partials (`article_head`, `article_meta`, `article_toc`, `callout`, `code_block`, `diagram`, `faq`, `github_examples`, `hub`).
- Čtení reprezentativních šablon (`base`, `index`, `cqrs`, `event_sourcing`, `sagas`, `glossary`, `about`, `resources`, `security_policy`, `hub_basics`).
- Playwright vizuální průchod 1280 / 768 / 390 px na 6 typech stránek (homepage, hub, kapitola s diagramem, kapitola s FAQ, glosář, about/resources). Screenshoty v `.playwright-mcp/audit/` (gitignored).
- Cross-component diff pro callout, code-block, FAQ, table, list napříč 3+ kapitolami.

## Verifikace stavu před auditem

- `php bin/console lint:twig templates/` — všech 39 šablon validní.
- `php bin/console lint:container --env=prod` — OK.
- Smoke render všech 25 routes na portu 8765 — všechny 200.
- Stav po commitu `21256b5` (PLACEHOLDER recovery): 0 PLACEHOLDER tokenů ve výstupu.
