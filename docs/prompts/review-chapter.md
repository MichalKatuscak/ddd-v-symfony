# Prompt: Revize kapitoly

Tento prompt řídí kompletní revizi jedné kapitoly DDD průvodce.

**Spuštění:** „Použij docs/prompts/review-chapter.md na content/chapters/<soubor>.md"

---

## Kontext

Průvodce je odborná příručka o DDD v Symfony. Čtenář je zkušený PHP vývojář.
Hlas průvodce: zkušený praktik, přímý, věcný, bez marketingu. Podrobná pravidla viz CLAUDE.md sekce „Voice, tón a jazyk".

---

## Průchod 1 — Voice & tón

Přečti celý soubor. Pro každé porušení pravidel z CLAUDE.md vytvoř záznam:

```
[V-N] Řádek <číslo>
Originál: <původní text>
Návrh:    <opravený text>
Důvod:    <konkrétní pravidlo>
```

Co hledat:
- Všechna slova a fráze ze seznamu „Zakázáno" v CLAUDE.md
- Věty přes 25 slov (označ číslo řádku a délku)
- Em dash (—), anglické uvozovky, „Tady" místo „Zde"

---

## Průchod 1.5 — AI strukturní vzory

Před tímto průchodem si přečti tři referenční pasáže — slouží jako kotva přirozeného tónu této knihy. Bez nich revize každé kapitoly dopadne jinak.

**Referenční vzor:**

- **Vzor A — narativní:** `content/chapters/what_is_ddd.md`, řádky 22–34. Otevírací story o e-shopu (3 stavy → 12 stavů, BitPay). Konkrétní čísla, narativní oblouk, přímá řeč. Kotva pro otevření kapitoly typu „proč by tě tohle mělo zajímat".
- **Vzor B — definiční:** `content/chapters/when_not_to_use_ddd.md`, řádky 21–29. Krátký ostrý úvod kapitoly. Věty pod 15 slov, žádný marketingový obal, přímý úsudek. Kotva pro úvod kapitoly typu „upřímný názor".
- **Vzor C — pro úvody/předmluvy:** `content/chapters/preface.md`, řádky 21–23. Otvírací odstavec celé knihy. Osobní motivace, konkrétní reference, bez floskulí.

**Klasifikace kapitoly před revizí:**

Před hledáním vzorů identifikuj typ kapitoly podle níže uvedeného seznamu. Typ určuje, jak agresivně přepisovat a jaký vzor brát jako primární kotvu.

- **Narativní (7):** preface, when_not_to_use_ddd, migration_from_crud, case_study, practical_examples, ddd_pain_points, ddd_ai. Povolit delší vyprávění, story pasáže, méně bulletů, vyšší podíl prózy. Primární vzor: A.
- **Definiční (6):** basic_concepts, subdomains, lesser_known_patterns, architectural_styles, anti_patterns, aggregate_design. Referenční, hutné, ale rozbít rytmus bulletů. Primární vzor: B, C pro úvody.
- **Hybridní (13):** what_is_ddd, context_mapping, event_storming, team_topologies, cqrs, event_sourcing, sagas, outbox_pattern, microservices_and_ddd, implementation_in_symfony, authorization_in_ddd, testing_ddd, performance_aspects. Koncepční část = blíž definičnímu, implementační/příkladová část = blíž narativnímu. Primární vzor: A + B.

**Co Claude hledá:**

1. **Wikipedijní úvody sekcí** — 1–2 obecné věty na začátku sekce typu „X je přístup, který přináší Y a řeší Z". Vyřezat nebo přepsat tak, aby sekce začínala konkrétním tvrzením.
2. **Paralelismy** — sousední věty se stejnou syntaktickou kostrou. Rozbít druhou větu (jiný rytmus, jiné sloveso, jiná délka).
3. **Pravidelnost bulletů** — pokud má sekce 4+ bullet listů s identickým rytmem `**Pojem** – věta`, alespoň jeden přepsat (próza / různé délky / bez tučného leadu).
4. **Dvojí slovo ve větě** — stejné plnovýznamové slovo dvakrát ve větě → přepsat.
5. **Klišé** — z rozšířeného seznamu v `CLAUDE.md` sekce „Zakázáno — marketing a hype": „není stříbrná kulka", „není binární volba", „svatý grál", „v dnešní době" atd. Pokud už nález zaznamenán v Průchodu 1 jako `[V-N]`, neopakuj zde — tato kategorie slouží pro klišé typu strukturní idiom (frázové vzory přes 2+ slova).
6. **Kontextové vsuvky** — fráze typu „kterou většina příruček mlčky přeskočí", „jak víme", „jak jsme již zmínili" — vyřezat.

**Rozsah zásahu:**

Povolen přepis odstavce nebo sekce při:

- zachování faktů, citací (`[N]`) a jejich URL
- zachování struktury šablony (frontmatter, callouts, code blocks, diagram bloky)
- zachování stylu a tónu celé knihy (kotveno vzory A, B, C)
- zachování cross-linků mezi kapitolami

Pro každý nález:

```
[S-N] Řádek <číslo>
Originál: <text>
Návrh:    <přepis>
Důvod:    <kategorie 1–6>
```

---

## Průchod 2 — Jazyková kvalita

Zkontroluj přirozenost češtiny. Pro každý nález:

```
[J-N] Řádek <číslo>
Originál: <původní text>
Návrh:    <opravený text>
Důvod:    <pasivum | nominalizace | germanismus | anglicismus | registr>
```

Co hledat:
- Pasivní konstrukce tam, kde jde použít aktivum bez ztráty smyslu
- Nominalizace: „provedení implementace" → „implementovat", „zajištění konzistentnosti" → „zajistit konzistentnost"
- Germanismy: „z důvodu toho, že" → „protože", „na základě toho" → „proto"
- Anglicismy mimo technické termíny (technické termíny jako „repository", „event sourcing" jsou v pořádku)

---

## Průchod 3 — Faktická verifikace

Pro každé konkrétní tvrzení proveď webové vyhledávání a ověř je z důvěryhodného zdroje.

Zdroje v pořadí důvěryhodnosti:
1. martinfowler.com
2. Oficiální dokumentace Symfony (symfony.com/doc)
3. Weby autorů: vlad.gg, eventstorming.com, teamtopologies.com
4. Google Books preview (pro citace z knih)
5. ACM Digital Library

Co ověřit:
- Definice a atribuce DDD vzorů (kdo je zavedl, kde, kdy)
- Přesné názvy kapitol v citovaných knihách (Evans DDD 2003, Vernon IDDD 2013, Khononov LDDD 2021)
- Rok vydání a nakladatel každé citované publikace
- Verze Symfony API v kódových ukázkách (ověřit na symfony.com/doc)
- Pravopis jmen autorů

Pro každé ověřené tvrzení:

```
[F-N] Řádek <číslo>
Tvrzení:  <text tvrzení>
Stav:     OK | OPRAVIT | NEJISTÉ
Zdroj:    <URL nebo bibliografický záznam>
Návrh:    <oprava — vyplnit pouze pokud stav=OPRAVIT>
```

---

## Průchod 4 — Konzistentnost s ostatními kapitolami

Přečti ostatní kapitoly v `content/chapters/`. Pro každý klíčový termín z aktuální kapitoly ověř, zda je definován stejně:

```
[K-N] Termín: <termín>
Definice zde:   <jak je definován v aktuální kapitole>
Definice jinde: <jak je definován v jiné kapitole> (soubor:řádek)
Rozpor:         <popis rozporu>
Návrh:          <jak sjednotit>
```

Zkontroluj také:
- Čísla kapitol v odkazech (`href="#..."`) sedí s obsahem cílové sekce
- Kotvy (`id="..."`) odkazované z jiných šablon existují v tomto souboru

---

## Průchod 5 — Výstupní report

Vypiš kompletní report v tomto formátu:

```markdown
# Revize: <název souboru>
Datum: <datum>

## Voice/tón — <N> nálezů
<záznamy [V-N]>

## AI strukturní vzory — <N> nálezů
<záznamy [S-N]>

## Jazyk — <N> nálezů
<záznamy [J-N]>

## Fakta — <N> tvrzení (OK: X | OPRAVIT: Y | NEJISTÉ: Z)
<záznamy [F-N]>

## Konzistentnost — <N> nálezů
<záznamy [K-N]>

---
Celkem nálezů vyžadujících akci: <součet OPRAVIT ze všech kategorií>
```

---

## Zápis oprav

Po explicitním potvrzení reportu uživatelem:

1. Zapiš opravy do souboru — pouze nálezy se stavem OPRAVIT (ne NEJISTÉ)
2. Neměň frontmatter, callout markup (`:::callout{...}`), code bloky (`:::code{...}`), diagram bloky (`:::diagram{...}`), citace `[N]` a jejich URL, cross-linky (`/cqrs`, `/zakladni-koncepty`, atd.). Pouze textový obsah odstavců, bullet listů, nadpisů a tabulek.
3. Zachovej markdown syntaxi a odsazení beze změny
4. Po zápisu spusť: `git diff content/chapters/<soubor>.md`
5. Počkej na potvrzení uživatele — teprve pak commitni
