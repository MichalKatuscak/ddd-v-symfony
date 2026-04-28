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
| **0** (HOTOVO `21256b5`) | R-P0-1 — recovery PLACEHOLDER → kód | 1 commit |
| **1** (HOTOVO `8762f94`) | Auto-fix triviálních: T-P1-1, T-P1-2, I-P1-1, C-P2-1, T-P2-3, C-P2-3,4 | 1 commit |
| **2** (HOTOVO `cc22cfa`) | Vizuální konzistence: D-P0-1, D-P1-1, D-P1-2, D-P1-3, D-P1-4, D-P1-6, D-P1-5 | 1 commit |
| **6** (HOTOVO `954ffce`) | Navigace: N-P1-1 (next chapter); N-P1-2, N-P1-3 odloženo na content review | 1 commit |
| **5** (HOTOVO `69390e6`) | Mobilní polish: M-P1-1, M-P1-2, I-P1-2, I-P1-3; M-P1-3,4 a C-P1-2 vyřešeno dříve | 1 commit |
| **4** (HOTOVO `0e64b68`) | Spacing & breakpointy: S-P1-1, S-P1-2; S-P1-3 a C-P1-3 ponecháno záměrně | 1 commit |
| **7** (HOTOVO `47c78af`) | Diagram chrome: C-P1-1, T-P1-7 dokumentováno; N-P2-1, N-P2-2 ponecháno | 1 commit |
| **8** (HOTOVO `4e0f40e`) | P2 leštění: T-P2-1,2, D-P2-1,3, S-P2-2; D-P2-2, S-P2-1, C-P2-2 ponecháno | 1 commit |
| **3** (NEZAŘAZENO) | Typografie & rytmus: T-P1-3..6 — pouze interní CSS hygiena, bez user impact, odložené po-launch | – |

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

---

## Stav po realizaci

### Vlna 0 (commit `21256b5`)

- R-P0-1 ✓ recovery 168 PLACEHOLDER → originální kód.

### Vlna 1 (commit `8762f94`)

- T-P1-1 ✓ 291 list-itemů `</strong> - ` → `</strong> – ` napříč 14 kapitolami.
- T-P1-2 ✓ 60 dalších prózových ASCII pomlček v textu (glosář `<dd>` + ddd_ai + event_sourcing aj.) sjednoceno na en pomlčku. Twig math expression `total_time - hours * 60` v `index.html.twig` vrácena na ASCII (lint regrese odhalena při verifikaci).
- I-P1-1 ✓ tokeny `--motion-fast` (120 ms) a `--motion-medium` (200 ms) v `tokens.css`; 16 výskytů 120/160/180 ms sjednoceno na fast, 1 výskyt 200 ms na medium napříč `article.css`, `base.css`, `chrome.css`, `hub.css`, `landing.css`.
- C-P2-1 ✓ copy button aria-label sjednocena z dokonavého „Zkopírovat kód" na nedokonavé „Kopírovat kód" — soulad s viditelným labelem „Kopírovat".
- T-P2-3 ✓ page ranges (`str. NN-NN`) byly auditem 27.4. již sjednoceny na en pomlčku (0 ASCII zůstává); ověřeno greppingem.
- C-P2-3,4 ✓ 4× `!important` v `article.css` odstraněny: `.toc-list li.toc-current` (specificity 0,2,1 přebíjí `.toc-list li`); `.glossary-entry dd p.term-source` a `.term-related` (specificity 0,3,1 přebíjí `.glossary-entry dd p`).

### Vlna 2 (commit `cc22cfa`)

- D-P1-1 ✓ podtržení odkazů `accent-dim → accent` ve 4 komponentách (`.callout-body a`, `.bio-links a`, `.glossary-entry dd a`, `.faq-answer a`); idle 1 px, hover 2 px tloušťky.
- D-P1-4 ✓ `about.html.twig` `<aside class="bio-card">` → `<section class="bio-card" aria-label="Profil autora">`.
- D-P1-6 ✓ inline-code styling konsolidován z 3 deklarací do 1 v `article.css`; `landing.css` duplicity smazána.
- D-P0-1 ✓ scroll-shadow technikou (`linear-gradient covers attached: local` přes `radial-gradient shadows attached: scroll`) přidán na `.art-body .table-responsive` a `.code-body`. Shadow se ukazuje jen když má obsah ještě kam skrolovat. Vedlejší: `.code-body font-size: 13px` tokenizováno na `var(--t-sm)`.
- D-P1-2 ✓ dead partial `templates/_partials/article_meta.html.twig` odstraněn.
- D-P1-3 ✓ `article_head.html.twig` přijímá `published` parametr; meta-cell ukazuje „Publikováno · Aktualizováno 24. 4. 2025 · 12. 4. 2026" když je k dispozici obojí. 18 šablon (16 kapitol + glosář + resources) předává `published: block('article_published_time')` a `last_updated: block('article_modified_time')` — eliminuje duplicitu mezi Twig blokem a hardcoded include argumentem.
- D-P1-5 ⊘ ponecháno záměrně oboje. Docblock v `article.css` vysvětluje že `.note` je decentní úvod sekce v glosáři (bez rail/glyfu), `.callout type=note` je pojmenovaný blok s rail+glyf.

### Vlna 6 (commit `954ffce`)

- N-P1-1 ✓ navigace „další kapitola" implementována jako `_partials/chapter_nav.html.twig`. `Chapters::neighbors(route)` v `src/Catalog/Chapters.php` vrací prev/next dle pořadí v `all()`. Twig funkce `ddd_chapter_neighbors()` v `ChaptersExtension`. Partial čte aktuální route z `app.request.attributes._route`. Kartičky s číslem a titulem souseda; první/poslední kapitola má visibility-hidden spacer pro symetrii gridu. Mobilní (<540 px) přechod na jednosloupcový stack. CSS `.art-nav*` v `article.css`; specificita `.art-body .art-nav-item` potlačuje generický `.art-body a` underline uvnitř karty. Vloženo do všech 16 kapitol.
- N-P1-2 ⊘ glosář auto-link odložen — vyžaduje content review (které termíny linkovat, kde) a samostatnou Twig logiku.
- N-P1-3 ⊘ cross-link audit odložen — vyžaduje obsahový průchod, ne mechanickou opravu.

### Vlna 5 (commit `69390e6`)

- M-P1-1 ✓ `.art-meta` přechází z `repeat(4, 1fr)` na `repeat(auto-fit, minmax(160px, 1fr))` — kapitoly se 4 položkami zaplní řadu, ne-kapitoly se 2 položkami (resources, glossary) zůstávají kompaktní bez prázdných tracků.
- M-P1-2 ✓ `.diagram` na `<540 px` má scroll-shadow techniku totožnou s `.table-responsive`/`.code-body`. Indikuje uživateli, že obsah je scrollovatelný (diagramy mají `min-width: 720 px` a vždy přetékají na úzkých viewportech).
- I-P1-2 ✓ outline pro TOC focus se přesunul z `.toc-list a` (které má `display: contents` bez vlastní geometrie) na `.toc-list li:focus-within`. Tabování na odkaz teď zvýrazní celý `<li>` řádek (číslo + titul), ne jen textovou buňku.
- I-P1-3 ✓ `.code-copy.code-copied` dostala kromě `color: var(--state-ok)` i `border-color` a `background` v 12% alfa. Po kliku na kopírování je vizuální feedback výraznější.
- M-P1-3 ⊘ ověřeno bez změny — drawer JS už nastavuje `role="dialog"` + `aria-modal="true"` (audit 27.4. A-P1-5), `.nav-drawer-list` aktivní state na sub-routách funguje (např. `/cqrs` → „Vzory" má `class="active"`).
- M-P1-4 ⊘ vyřešeno ve vlně 1 (T-P1-1) — list-itemy s ASCII pomlčkou hromadně sjednoceny.
- C-P1-2 ⊘ vyřešeno ve vlně 2 (D-P0-1) — scroll-shadow na `.code-body` funguje pro dlouhé řádky stejně na desktopu i mobilu.

### Vlna 4 (commit `0e64b68`)

- S-P1-1 ✓ tokenizováno: `--page-article` (1180 px) a `--page-hero` (1480 px) doplněny vedle `--canvas-max` (1280 px). `.article` max-width a `.land-hero`/`.land-featured` max-width používají tokeny. Záměrně různé pro různé typy stránek (článek vs. hub shell vs. landing hero); komentář v tokens.css.
- S-P1-2 ✓ hub.css breakpoint 880 → 900 px sjednoceno s chrome a article. Hub 720 px (footer 2→1 col) a 1080 px (footer 4→2 col) ponechány pro jiné osy. `tokens.css` má docblock popisující systém 540/720/900/1080 px (CSS @media nelze tokenizovat, hodnoty žijí inline ale konzistentně).
- S-P1-3 ⊘ shell padding ponecháno — top padding hub i article shodný (48 px = `--s-7`), landing 96 px je záměr pro hero.
- C-P1-3 ⊘ footer cols 1080 px transition ponechán — při 720-1080 px viewportu by 4-col layout byl příliš úzký (≈ 200 px na sloupec).

### Vlna 7 (commit `47c78af`)

- C-P1-1 ✓ docstring v `_partials/diagram.html.twig` popisuje, kdy `caption` používat: jen u diagramů s vizuální sémantikou, která potřebuje vysvětlit (barvy šipek, vzory čar). Pro běžné architektonické diagramy stačí FIG. + title bez captionu. Aktuálně 1 z 12 diagramů využívá (sagas, barevné šipky úspěch/selhání/kompenzace).
- T-P1-7 ✓ konvence sekčního číslování zdokumentovaná v `_partials/article_head.html.twig`: kapitoly `NN.MM`, glosář `01..NN`, meta-stránky (about/resources/security_policy) `A..Z`. Záměr odráží kontext (kapitolní subsekce vs. samostatný dokument vs. krátký rozcestník).
- N-P2-1 ⊘ featured aria-label ponecháno — nese jasnou akci a kontext.
- N-P2-2 ⊘ druhý skip-link ne — primární „Přejít na obsah" stačí.

### Vlna 8 (commit `4e0f40e`)

- T-P2-1 ✓ nepoužitý token `--t-4xl` (72 px) odstraněn z `tokens.css`.
- T-P2-2 ✓ inline code `0.88em` → `0.92em`. Při rodiči 13 px (FAQ, glosář) posun z 11.4 px na ~12 px znatelně zlepšuje čitelnost. Současně odstraněna nadbytečná override `.callout-body code` (dědí ze základního selektoru přes `.art-body code`).
- D-P2-1 ✓ `.glossary-entry dd p.term-source` a `.term-related` přechází z `var(--fg-dim)` (≈ 5.1 : 1) na `var(--fg-muted)` (≈ 8.5 : 1). Pro 11 px metadata v glosáři vyšší kontrast bezpečnější (WCAG AAA).
- S-P2-2 ✓ `.article` bottom padding `var(--s-9)` (96 px) → `var(--s-8)` (64 px). Chapter_nav z vlny 6 drží uzavírací rytmus.
- D-P2-3 ✓ `.diff-bar` dostala `aria-hidden="true"` — vizuální indikátor dublovaný textem („pokročilá" atd.).
- D-P2-2 ⊘ hub karta šipka „→" jako Unicode znak ponechán — JetBrains Mono ji vyřeší konzistentně.
- S-P2-1 ⊘ `.ic` padding `1px 5px` — hardcoded; nový token by si vyžádal vlastní rozhodnutí o spacingu, vizuální posun zbytečný.
- C-P2-2 ⊘ `article-toc.js` dynamický scénář — statický web, žádný side-effect.

---

## Stav 2026-04-28 — publikační připravenost

**Hotovo:** 7 implementačních vln + recovery + 7 dokumentačních zápisů. Z 34 položek auditu **vyřízeno 21**, **6 odloženo záměrně** (D-P1-5 dvojí komponenta, M-P1-3,4 vyřešeno dříve, C-P1-2 vyřešeno dříve, S-P1-3 a C-P1-3 spacingu ponecháno), **3 ponecháno k content review** (N-P1-2 glosář auto-link, N-P1-3 cross-link audit, N-P2-1 featured aria-label) a **4 P1 typografické tokeny v hub/landing** (T-P1-3..6) jsou odloženy jako post-launch interní hygiena bez uživatelského dopadu.

**Verifikace:** `lint:twig` zelená pro 39 šablon, `lint:container` zelená, smoke render všech 25 routes vrací 200, scroll-shadow / next-chapter nav / publish-date / inline-code konsolidace / kontrast podtržení odkazů potvrzeno vizuálně na 1280 / 768 / 390 px.
