# Kompletní revize DDD příručky — design

Datum: 2026-05-02
Autor: Michal + Claude
Status: schváleno

## Cíl

Provést úplnou revizi všech 26 kapitol DDD příručky (`content/chapters/*.md`) podle pravidel z `CLAUDE.md` a `docs/prompts/review-chapter.md`. Revize se soustředí na **voice/tón**, **jazykovou kvalitu** a **konzistenci napříč kapitolami**. Faktická verifikace (webové vyhledávání zdrojů) je z rozsahu vyloučena.

## Rozsah

Vstupy:
- 26 souborů v `content/chapters/`:
  - 25 hlavních kapitol očíslovaných 01–25 podle `App\Catalog\Chapters::all()`
  - 1 extras kapitola: `ddd_ai.md`
- Pravidla v `CLAUDE.md` sekce „Voice, tón a jazyk"
- Metodika `docs/prompts/review-chapter.md`, průchody 1, 2, 4 (voice, jazyk, konzistence)

Mimo rozsah:
- Faktická verifikace (`review-chapter.md` průchod 3)
- Změny ve frontmatter, kódových blocích, citacích, struktuře nadpisů, pořadí sekcí
- Oprava případných artefaktů z migrace Twig → Markdown (na ty subagent jen upozorní v reportu)

## Pořadí kapitol

Podle `App\Catalog\Chapters::all()`:

| # | Soubor | Hub |
|---|---|---|
| 01 | what_is_ddd.md | basics |
| 02 | subdomains.md | basics |
| 03 | context_mapping.md | basics |
| 04 | event_storming.md | basics |
| 05 | team_topologies.md | basics |
| 06 | basic_concepts.md | tactics |
| 07 | aggregate_design.md | tactics |
| 08 | lesser_known_patterns.md | tactics |
| 09 | architectural_styles.md | architecture |
| 10 | horizontal_vs_vertical.md | architecture |
| 11 | implementation_in_symfony.md | architecture |
| 12 | authorization_in_ddd.md | architecture |
| 13 | cqrs.md | patterns |
| 14 | event_sourcing.md | patterns |
| 15 | sagas.md | patterns |
| 16 | outbox_pattern.md | patterns |
| 17 | performance_aspects.md | patterns |
| 18 | testing_ddd.md | practice |
| 19 | migration_from_crud.md | practice |
| 20 | microservices_and_ddd.md | practice |
| 21 | ddd_pain_points.md | practice |
| 22 | anti_patterns.md | practice |
| 23 | when_not_to_use_ddd.md | practice |
| 24 | practical_examples.md | synthesis |
| 25 | case_study.md | synthesis |
| extras | ddd_ai.md | reference |

## Pracovní postup

### Fáze 1 — Voice/tón + jazyková kvalita

Paralelní zpracování kapitol pomocí subagentů ve várkách po 5.

Každý subagent:
1. Načte `CLAUDE.md` sekci „Voice, tón a jazyk"
2. Načte přidělenou kapitolu
3. Aplikuje opravy podle průchodů 1+2 z `review-chapter.md`:
   - Voice/tón: zakázané fráze (marketing, hype, výplň), em dash → en pomlčka, anglické uvozovky → české, „Tady" → „Zde", vykání
   - Délka věty ≤ 25 slov (delší rozdělit)
   - Pasiva → aktiva (kde nemění smysl)
   - Nominalizace → slovesné vazby
   - Germanismy a anglicismy mimo technické termíny
4. Zapíše opravy přímo do `.md` souboru (`Edit` tool)
5. Vrátí stručný report:
   - Počet úprav per kategorie (voice, jazyk)
   - Případné artefakty z migrace Twig → MD (raw HTML, dvojité escapování) — pouze upozornit, neopravovat
   - Jakékoli místo, kde si subagent nebyl jistý a edit raději neudělal

Subagent **necommituje**.

Po doběhnutí každé várky 5 subagentů hlavní konverzace:
1. Spustí `git diff content/chapters/<X>.md` pro každou kapitolu z várky
2. Diff projde, případně odmítne sporné změny (`git checkout content/chapters/<X>.md` nebo cílený `git restore -p`)
3. Commitne kapitolu po kapitole: `chore(content): revize <kapitola>`

Várkování (5+5+5+5+5+1):
- Várka A: kapitoly 01–05
- Várka B: kapitoly 06–10
- Várka C: kapitoly 11–15
- Várka D: kapitoly 16–20
- Várka E: kapitoly 21–25
- Várka F: ddd_ai

### Fáze 2 — Konzistence napříč kapitolami

Sériová práce v hlavní konverzaci po dokončení Fáze 1.

Kontroluje:
1. **Definice klíčových termínů** napříč kapitolami — pokud je stejný termín definován různě (Bounded Context, Agregát, Doménová událost, Repozitář, Value Object, Subdoména, Doménová služba, Aplikační služba, Specification, Factory, Module), sjednotit s primární definicí v glosáři, případně v `basic_concepts.md`
2. **Anchor odkazy** mezi kapitolami — pokud kapitola odkazuje `[text](other-chapter#kotva)`, kotva v cíli musí existovat
3. **Číslování v textu** — věty typu „v kapitole 7", „v Hub 4" sedí s `Chapters::all()`
4. **Sjednocení s glosářem** — první výskyt termínu v kapitole odpovídá glosáři (pokud glosář existuje)

Commit za fázi 2: 1–3 commity podle rozsahu, např.:
- `chore(content): sjednocení definice agregátu napříč kapitolami`
- `chore(content): oprava anchor odkazů mezi kapitolami`

## Promptová šablona pro subagenta

Uloženo v plánu (writing-plans), ne zde. Klíčové body:
- Striktně podle CLAUDE.md voice pravidel
- Jen text v odstavcích, položkách seznamů, nadpisech, popiscích, callout boxech
- Frontmatter, kódové bloky, identifikátory, citace, strukturu nadpisů NESÁHNOUT
- Po dokončení vrátit jednostránkový report

## Commitování

- **Per kapitola** ve fázi 1: `chore(content): revize <route>` (např. `chore(content): revize what_is_ddd`)
- **Per oprava** ve fázi 2: zaměřený na konkrétní problém (sjednocení termínu, anchor odkazy, …)
- **Bez** `Co-Authored-By: Claude` (per uživatelské preferenci)

## Rizika a omezení

| Riziko | Mitigace |
|---|---|
| Subagenti vykládají hraniční pravidla různě | Finální fáze konzistence v hlavní konverzaci |
| Velká kapitola → velký diff k revizi | Per-kapitola checkpoint, možnost zastavit se na sporné větě |
| Twig artefakty po migraci | Subagent jen upozorní v reportu, neopravuje |
| Souběžné editace | Bezpečné, každý subagent edituje jiný soubor |
| Subagent přepíše kód v code blocích | Explicitní zákaz v promptu, manuální kontrola v diffu |

## Definition of Done

**Per kapitola (fáze 1):**
- Subagent zapsal opravy
- `git diff` schválen v hlavní konverzaci
- Commit `chore(content): revize <kapitola>` v `git log`

**Per oprava (fáze 2):**
- Konkrétní problém odstraněn napříč všemi dotčenými kapitolami
- Commit s jasným popisem rozsahu

**Celkově:**
- Všech 26 kapitol prošlo fází 1
- Fáze 2 dokončena
- `git log --oneline` ukazuje čistou řadu commitů
- Žádné necommitnuté změny v `content/chapters/`

## Otevřené otázky

Žádné. Návrh schválen uživatelem 2026-05-02.
