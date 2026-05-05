# Pilot retrospektiva — anti-AI revize obsahu

**Datum:** 2026-05-05
**Rozsah:** Dávka 1 (5 kapitol)
**Spec:** `docs/superpowers/specs/2026-05-05-anti-ai-revize-design.md`
**Plán:** `docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot.md`

## Data

### Commity z pilotu

| Task | Kapitola | Typ | První revize | Quality fix |
|---|---|---|---|---|
| 4 | `what_is_ddd.md` | hybridní | `f0427f2` | `dbb8446` |
| 5 | `basic_concepts.md` | definiční | `1a0133e` | `892a51a` |
| 6 | `when_not_to_use_ddd.md` | narativní | `ddb440a` | – |
| 7 | `ddd_pain_points.md` | narativní | `6d6b2b7` | `4b4e919` |
| 8 | `anti_patterns.md` | definiční | `30b1e17` | – |

Tři kapitoly z pěti potřebovaly post-fix po quality review. Dvě prošly bez follow-up.

### Počty nálezů × kategorie

| Kapitola | Voice | Strukturní | Jazyk | Fakta | Konzistentnost | Celkem aplikováno |
|---|---|---|---|---|---|---|
| `what_is_ddd.md` | 4 | 8 | 9 | 0 | 0 | 21 |
| `basic_concepts.md` | 4 | 9 | 3 | 0 | 0 | 16 |
| `when_not_to_use_ddd.md` | 1 | 4 | 3 | 0 | 0 | 7 |
| `ddd_pain_points.md` | 4 | 6 | 1 | 0 | 0 | 13 |
| `anti_patterns.md` | rozdělené | rozdělené | rozdělené | 0 | 0 | 12 editů (17 nálezů) |
| **Součet** | **~17** | **~33** | **~19** | **0** | **0** | **~70** |

Žádné faktické nebo konzistenční opravy v pilotu. Strukturní vzory dominantní kategorie (≈47 % všech nálezů).

## Co fungovalo

- **Klasifikace narativní/definiční/hybridní** dávala různé výsledky. Hybridní `what_is_ddd.md` měl 21 nálezů, narativní `when_not_to_use_ddd.md` jen 7 — to odpovídá tomu, že narativní kapitoly mají méně bullet listů a méně wikipedijních úvodů sekcí.
- **Ochrana referenčních pasáží** (ř. 22–34 v `what_is_ddd.md`, ř. 21–29 v `when_not_to_use_ddd.md`) fungovala přesně. Spec reviewer u obou kapitol potvrdil bit-perfect zachování.
- **Spec compliance review** spolehlivě chytal porušení struktury (frontmatter, callouts, code, citace, cross-linky).
- **Quality review** chytal věci, které spec compliance pustil:
  - Disonanci tónu mezi sekcemi (Task 4 — imperativy v 01.05 vs. deklarativ jinde)
  - Gramatické chyby zavedené přepisem (Task 5 „tyto pravidla", Task 7 „s ním sráží")
  - Sémantické posuny (Task 5 „entity" místo „entity ani agregátu")
- **Dávkový přístup** ušetřil pozornost — implementer subagent fresh per kapitola měl čistý kontext a nepřenášel chyby z předchozí kapitoly.

## Co je třeba kalibrovat

### CLAUDE.md — návrhy na rozšíření

**1. Zákaz imperativy v doménově deklarativních sekcích**

Implementer v Tasku 4 přepsal 8 očíslovaných kroků sekce 01.05 z deklarativního stylu na imperativní („Mluvte", „Definujte", „Rozdělte"). Disonance se zbytkem knihy. Po fix subagentu vráceno do deklarativu.

Návrh do `CLAUDE.md`, sekce „Pravidla věty":

> Tón průvodce je deklarativní (autor popisuje), ne imperativní (autor velí). Imperativní formy („Použijte", „Definujte", „Rozdělte") se vyhraďte pro callout typu `pattern` nebo numerované postupy v sekci „Implementace v praxi", nikdy pro definiční nebo srovnávací pasáže.

**2. „lze + infinitiv" je povolené**

Implementer v Tasku 4 přepsal „lze testovat" → „jdou testovat" se snahou vyhnout se opisné konstrukci. Quality reviewer označil náhradu jako toporné. „Lze" je v moderní české odborné próze přijatelné a CLAUDE.md ho nezakazuje.

Návrh do `CLAUDE.md`, sekce „Pravidla věty":

> „Lze + infinitiv" je standardní český registr v odborné próze. Substituce „lze" → „jde" / „jdou" provádět **pouze** v případě, že výsledná věta zní přirozeněji, ne reflexivně.

**3. Pre-write gramatika check**

Dva ze čtyř post-fix commitů byly o gramatice (`tyto pravidla`, `s ním sráží`). Implementer při přepisu odstavce občas zavedl chyby v rodové shodě nebo zvratných slovesech. Návrh není pravidlo do `CLAUDE.md` — je to procesní krok do `review-chapter.md`.

### `docs/prompts/review-chapter.md` — návrhy na úpravu

**4. Vyřadit kategorii 5 (Klišé) z Průchod 1.5**

Quality reviewer u Tasku 2 (infrastruktura) upozornil na překryv: Průchod 1 už hledá všechny zakázané fráze ze seznamu CLAUDE.md jako `[V-N]`. Průchod 1.5 kategorie 5 dělá totéž jako `[S-N]`. V pilotu jsme vyřešili poznámkou „Pokud zaznamenáno v Průchodu 1, zde neopakuj", ale lepší je kategorii 5 z Průchod 1.5 úplně odebrat — Průchod 1 to pokrývá.

Návrh: V `review-chapter.md`, Průchod 1.5, smazat bod 5 (Klišé). Číslování bodů přerovnat (6 → 5: Kontextové vsuvky).

**5. Pre-write gramatika check krok**

Návrh do `review-chapter.md`, Průchod 1.5 nebo do sekce „Zápis oprav":

> Před každou aplikací návrhu OPRAVIT, který přepisuje větu nebo odstavec, znovu přečti přepsaný text. Zkontroluj:
> - shodu rodu mezi přídavným jménem/zájmenem a podstatným jménem
> - správnost zvratných sloves (sloveso s reciprocitou vyžaduje „se" / „si")
> - logické vazby konektorů (po „protože" následuje příčina, po „proto" následek)
> Pokud cokoli zní nepřirozeně, klasifikuj nález jako NEJISTÉ a neaplikuj.

**6. Anti-imperativ instrukce v Průchod 1.5**

Návrh do `review-chapter.md`, Průchod 1.5, kategorie 1 (Wikipedijní úvody):

> Když přepisuješ wikipedijní úvod sekce, drž deklarativní tón knihy. Příklad správně: „X je situace, kdy…" → „Anémický model nastane tehdy, když…". Špatně: přepsat na imperativ „Vyhněte se anémickému modelu — jeho příčiny jsou tři…".

### Mimo aktivní kalibraci

**7. „Hladkost napříč kapitolami"** — bod 5 z původní analýzy. Spec H to záměrně označil jen za částečně řešitelný klasifikací. Pilot ukázal, že klasifikace funguje (různé počty nálezů u různých typů), ale extrémní stylová homogenita napříč 26 kapitolami zůstane. Pro dávky 2–5 to není důvod k akci, jen pro vědomí.

**8. Pre-existing problémy** mimo scope této revize:
- Anglické uvozovky `"…"` v `docs/prompts/review-chapter.md` (tj. ASCII zavírací místo `"`) — quality reviewer u Tasku 2 to nahlásil jako pre-existing. Není v scope, ale stojí za fix při příští údržbě.
- Em dash v nadpisech sekcí v `review-chapter.md` — zavedený vzor souboru, není to chyba revize.

## Doporučení pro dávky 2–5

**Pokračovat po malé úpravě.** Pilot prošel — všech 5 kapitol commitnuto, fakta zachována, struktura intaktní. Tři post-fix commity ukazují, že proces detekuje zbytkové chyby přes quality review, ne že proces selhává.

**Před dávkou 2:**

1. Aplikovat kalibrace 1–4 (úpravy `CLAUDE.md` a `review-chapter.md`).
2. Aplikovat kalibrace 5–6 (procesní kroky do `review-chapter.md`).
3. Commit kalibrací jako samostatný `chore(content): kalibrace anti-AI revize po pilotu`.
4. Napsat samostatný plán `docs/superpowers/plans/<datum>-anti-ai-revize-davky-2-5.md` (mimo scope tohoto plánu).

## Rozdělení dávek 2–5 zůstává beze změny

Per spec sekce F:

- **Dávka 2 — Strategie (5):** preface, subdomains, context_mapping, event_storming, team_topologies
- **Dávka 3 — Taktika + architektura (5):** aggregate_design, lesser_known_patterns, architectural_styles, implementation_in_symfony, authorization_in_ddd
- **Dávka 4 — Vzory (5):** cqrs, event_sourcing, sagas, outbox_pattern, microservices_and_ddd
- **Dávka 5 — Praxe (6):** testing_ddd, performance_aspects, migration_from_crud, case_study, practical_examples, ddd_ai

Celkem 21 zbývajících kapitol.
