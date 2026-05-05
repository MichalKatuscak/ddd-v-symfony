# Spec: Anti-AI revize obsahu

**Datum:** 2026-05-05
**Stav:** Schválený design, čeká na implementační plán
**Rozsah:** Všech 26 kapitol v `content/chapters/*.md`

## Problém

Současná pravidla v `CLAUDE.md` a `docs/prompts/review-chapter.md` chytají **slovní úroveň** AI signálů (zakázané fráze, em dash, „Tady"). Strukturní vzory propouštějí. Po vyhodnocení obsahu jsou identifikované zbývající AI signály:

1. **Pravidelnost struktury bulletů** — napříč 26 kapitolami se opakuje formát `**Pojem** — věta vysvětlující X.` v seznamech 4–7 položek se stejnou délkou.
2. **Paralelismy sousedních vět** — dvě věty se stejnou syntaktickou kostrou („Write model se soustředí na X. Read model se soustředí na Y.").
3. **Klišé která prošla** — „není stříbrná kulka", „není binární volba", „svatý grál" nejsou v současném seznamu zakázaných.
4. **Wikipedijní úvody sekcí** — 1–2 obecné věty na začátku sekce typu „X je přístup, který přináší Y a řeší Z".
5. **Hladkost napříč kapitolami** — extrémní stylová konzistence všech 26 kapitol jako fingerprint.
6. **Kontextové vsuvky** — fráze typu „kterou většina příruček mlčky přeskočí", „jak víme".

## Cíl

Snížit AI signály v existujícím obsahu a zabránit jejich znovuobjevení v budoucích editacích. Cílový stav: text projde čtením recenzenta zaměřeného na AI-detection bez okamžité identifikace AI autorství.

## Princip

Práce na třech úrovních:

1. **Slovní úroveň** — rozšířit existující pravidla v `CLAUDE.md` a Průchod 1 v `review-chapter.md`.
2. **Strukturní úroveň** — zavést nový Průchod 1.5 v `review-chapter.md` pro vzory, které slovní substituce nezachytí.
3. **Konzistence napříč** — referenční vzor 3 pasáží + klasifikace kapitol na narativní / definiční / hybridní, aby revize každé kapitoly respektovala kontext knihy.

## Rozsah zásahu

**Agresivní revize.** Povolen přepis sekce při:
- zachování faktů, citací a odkazů
- zachování struktury šablony (frontmatter, callouts, code blocks, diagramy)
- zachování stylu a tónu celé knihy (kotveno referenčními vzory)

Změna pořadí bulletů, zkrácení seznamů, nahrazení listu prózou, přepis odstavce — všechno povoleno, pokud výsledek lépe odpovídá pravidlům a vzorům.

## Struktura řešení

### A) Rozšíření `CLAUDE.md`, sekce „Voice, tón a jazyk"

#### A1) Doplnit do tabulky „Zakázáno — marketing a hype"

| Vzor | Problém |
|---|---|
| „není stříbrná kulka" | AI klišé, vyřezat nebo přeformulovat („má své limity", „nehodí se všude") |
| „není binární volba", „není černobílé" | totéž |
| „svatý grál", „švýcarský nůž" | totéž |
| „v každém případě platí, že" | obecná fráze |
| „v dnešní době", „v moderním vývoji" | časově nestabilní |

#### A2) Nová podsekce „Zakázáno — strukturní vzory"

Pravidla na úrovni věty a odstavce:

- Dvojí výskyt stejného slovesa nebo plnovýznamového slova ve větě → přepsat.
  Příklad chyby: „přináší strukturu a vyjadřovací sílu, ale jeho složitost přináší řadu úskalí".
- Paralelismus dvou sousedních vět se stejnou syntaktickou kostrou → rozbít.
  Příklad: „Write model se soustředí výhradně na X. Read model se soustředí na Y."
  Druhou větu přeformulovat tak, aby měla jiný rytmus.
- Wikipedijní úvod sekce → vyřezat nebo přepsat.
  Definice: 1–2 věty obecného typu „X je přístup, který přináší Y a řeší Z" na začátku sekce, bez konkrétního zakotvení nebo vlastního názoru.
  Sekce má začínat rovnou věcí, kterou přináší novou.
- „Není to jen X, je to Y" / „Nejen X, ale i Y" → klišé, přepsat na věcné tvrzení.

#### A3) Nová podsekce „Strukturní rytmus seznamů"

Bullet listy: vyhnout se uniformnímu rytmu napříč kapitolou.

- Pokud má kapitola 4+ bullet listů ve formátu `**Pojem** — věta vysvětlující X.`, alespoň jeden přepsat. Buď bez tučného leadu, nebo s různými délkami položek, nebo zlomit do dvou bloků (číslovaný + nečíslovaný), nebo nahradit prózou.
- Bullet list 4–7 položek se stejnou délkou věty = signál. Záměrně rozkolísat: jedna položka delší, jedna kratší, jedna jako věta bez bullet.
- Alespoň jednou v kapitole nahradit list souvislým odstavcem se třemi krátkými větami.

#### A4) Doplnit do „Pravidla věty"

- Mezi dvěma sousedními větami nikdy neopakovat stejné podstatné jméno v podmětu, pokud to nemá explicitní důvod (kontrastní porovnání). Použít synonymum nebo zájmeno, nebo věty spojit.

### B) Nový Průchod 1.5 v `docs/prompts/review-chapter.md`

Vložen mezi Průchod 1 (Voice & tón) a Průchod 2 (Jazyková kvalita).

Co Claude hledá:

1. **Wikipedijní úvody sekcí** — 1–2 obecné věty na začátku sekce, vyřezat nebo přepsat.
2. **Paralelismy** — sousední věty se stejnou kostrou, rozbít druhou.
3. **Pravidelnost bulletů** — sekce s 4+ listy stejného rytmu, alespoň jeden přepsat.
4. **Dvojí slovo ve větě** — stejné plnovýznamové slovo dvakrát, přepsat.
5. **Klišé** — z rozšířeného seznamu A1.
6. **Kontextové vsuvky** — „kterou většina příruček mlčky přeskočí", „jak víme", „jak jsme již zmínili".

Výstupní formát stejný jako u ostatních průchodů:

```
[S-N] Řádek <číslo>
Originál: <text>
Návrh:    <přepis>
Důvod:    <kategorie 1–6>
```

### C) Oprava cesty v `review-chapter.md`

Aktuální hlavička odkazuje na `templates/ddd/<soubor>.html.twig`. Obsah je v `content/chapters/*.md`. Aktualizovat prompt, aby pracoval nad markdownem.

### D) Referenční vzor

Tři pasáže slouží Claude jako kotva tónu. Před každou dávkou si je přečte:

- **Vzor A — narativní:** `content/chapters/what_is_ddd.md`, řádky 22–34. Otevírací story o e-shopu (3 stavy → 12 stavů, BitPay). Konkrétní čísla, narativní oblouk, přímá řeč. Funkce: kotva pro otevření kapitoly typu „proč by tě tohle mělo zajímat".
- **Vzor B — definiční:** `content/chapters/when_not_to_use_ddd.md`, řádky 21–29. Krátký ostrý úvod kapitoly. Věty pod 15 slov, žádný marketingový obal, přímý úsudek. Funkce: kotva pro úvod kapitoly typu „upřímný názor".
- **Vzor C — pro úvody/předmluvy:** `content/chapters/preface.md`, řádky 21–23. Otvírací odstavec celé knihy. Osobní motivace, konkrétní reference (Evans 2003, Vernon 2013), bez floskulí. Funkce: kotva pro „proč tato kniha existuje".

### E) Klasifikace 26 kapitol

**Narativní (7)** — povolit delší vyprávění, story pasáže, méně bulletů, vyšší podíl prózy. Hlavní vzor: A.
- preface, when_not_to_use_ddd, migration_from_crud, case_study, practical_examples, ddd_pain_points, ddd_ai

**Definiční (6)** — referenční, hutné, ale rozbít rytmus bulletů. Hlavní vzor: B, doplnit C pro úvody.
- basic_concepts, subdomains, lesser_known_patterns, architectural_styles, anti_patterns, aggregate_design

**Hybridní (13)** — v koncepční části tón blíž definičnímu, v implementační/příkladové části povolit narativní pasáže. Hlavní vzor: A + B.
- what_is_ddd, context_mapping, event_storming, team_topologies, cqrs, event_sourcing, sagas, outbox_pattern, microservices_and_ddd, implementation_in_symfony, authorization_in_ddd, testing_ddd, performance_aspects

### F) Dávkový plán

**Dávka 1 — pilot s mixem typů (5):** what_is_ddd, basic_concepts, when_not_to_use_ddd, ddd_pain_points, anti_patterns.
Pokrývá narativní + definiční + hybridní. Klíčové: what_is_ddd je zdroj vzoru A — ověřit, že agresivní revize neporuší vlastní vzor.

**Dávka 2 — Strategie (5):** preface, subdomains, context_mapping, event_storming, team_topologies.

**Dávka 3 — Taktika + architektura (5):** aggregate_design, lesser_known_patterns, architectural_styles, implementation_in_symfony, authorization_in_ddd.

**Dávka 4 — Vzory (5):** cqrs, event_sourcing, sagas, outbox_pattern, microservices_and_ddd.

**Dávka 5 — Praxe a finále (6):** testing_ddd, performance_aspects, migration_from_crud, case_study, practical_examples, ddd_ai.

### G) Workflow dávky

Každá dávka = jedna nová Claude session (čerstvý kontext). Postup:

1. Přečti `CLAUDE.md` (rozšířená pravidla podle této spec).
2. Přečti referenční pasáže A, B, C.
3. Pro každou kapitolu v dávce, sekvenčně:
   1. Identifikuj typ kapitoly (narativní / definiční / hybridní).
   2. Spusť `review-chapter.md` přes všechny průchody (1, 1.5, 2, 3, 4).
   3. Vypiš report ve formátu z `review-chapter.md`.
   4. Počkej na schválení reportu uživatelem.
   5. Zapiš opravy do souboru.
   6. Spusť `git diff content/chapters/<soubor>.md`.
   7. Počkej na schválení diffu.
   8. Commitni samostatně (jeden commit = jedna kapitola).
4. Na konci dávky krátké shrnutí: co se opakovalo, co bylo systematicky nutné měnit, návrhy úprav promptu/CLAUDE.md.

### H) Kalibrační bod po pilotu

Po dávce 1 explicitní pauza. Uživatel + Claude projdou:

- Které kategorie nálezů se opakovaly napříč všemi 5 kapitolami → kandidát na rozšíření `CLAUDE.md`.
- Které kategorie LLM přehlédl → upravit Průchod 1.5.
- Kde byl agresivní zásah příliš/málo → kalibrovat instrukci.

Až po této kalibraci pokračují dávky 2–5.

## Co tato spec řeší vědomě jen částečně

- **Bod 5 (hladkost napříč kapitolami)** se neřeší přímo. Spoléháme na klasifikaci a referenční vzory: když narativní kapitoly dostanou jiný tón než definiční, a každá dávka je v čerstvém kontextu, výsledná uniformita klesne. Plné vyřešení by vyžadovalo schválně psát některé kapitoly úplně jinak — to je nad rámec této spec a riskuje rozbití kvality.

## Mimo rozsah

- Žádný regex/skript automatizace (zvažováno, zamítnuto). Detekce běží čistě skrz LLM Průchod 1.5.
- Žádné CI gate, git hook, ani jiná automatizace nad rámec ručního volání promptu.
- Žádný refactor existujících diagramů, callout struktury nebo SEO bloků — pouze textový obsah.

## Závazky vůči obsahu

Při revizi se nesmí změnit:

- Frontmatter (název, datum, kategorie, reading_time, atd.).
- Citace a odkazy `[N]` — text může být přeformulován, ale referenční číslo a URL se zachovává.
- HTML/Twig syntaxe v šabloně, callouts, code blocks, diagramy.
- Doménová fakta (atribuce vzoru autorovi, rok publikace, název knihy).
- Cross-linky mezi kapitolami (`/cqrs`, `/zakladni-koncepty`, atd.).

## Kritérium úspěchu

Pilotní dávka (5 kapitol) projde čtením s těmito výsledky:

- Žádná z kapitol neobsahuje paralelismus na úrovni dvou sousedních vět.
- Žádný bullet list v kapitole nemá 4+ položek identického rytmu, pokud to není dataset.
- Žádný úvod sekce není wikipedijní (1–2 obecné věty „X je přístup, který…").
- Klišé z rozšířeného seznamu CLAUDE.md jsou pryč.
- Diff každé kapitoly schválen uživatelem před commitem.
- Fakta, citace, odkazy, struktura šablony beze změny.
