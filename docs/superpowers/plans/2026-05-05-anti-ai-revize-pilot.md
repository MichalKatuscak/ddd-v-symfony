# Anti-AI revize obsahu — Infrastruktura + Pilotní dávka

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Připravit pravidla a prompt pro detekci strukturních AI signálů a provést pilotní revizi 5 kapitol. Po pilotu kalibrovat a teprve pak pokračovat dávkami 2–5 (samostatný plán).

**Architecture:** Dvoukrokový rollout. Nejprve rozšíření `CLAUDE.md` o nová pravidla a `docs/prompts/review-chapter.md` o nový průchod 1.5 (AI strukturní vzory). Poté pilotní revize 5 kapitol pokrývajících narativní + definiční + hybridní typy. Každá kapitola = jeden commit. Žádné automatizační skripty, žádný CI gate — vše ručně přes Claude session.

**Tech Stack:** Markdown obsah v `content/chapters/*.md`, Twig šablony v `templates/ddd/*.html.twig`, prompty v `docs/prompts/*.md`. Žádné build/test infrastruktury.

**Spec:** `docs/superpowers/specs/2026-05-05-anti-ai-revize-design.md`

---

## File Structure

**Modify:**
- `CLAUDE.md` — rozšíření sekce „Voice, tón a jazyk" (A1, A2, A3, A4 ze spec)
- `docs/prompts/review-chapter.md` — oprava cesty, nový Průchod 1.5, aktualizace finálního reportu
- `content/chapters/what_is_ddd.md` — pilotní revize
- `content/chapters/basic_concepts.md` — pilotní revize
- `content/chapters/when_not_to_use_ddd.md` — pilotní revize
- `content/chapters/ddd_pain_points.md` — pilotní revize
- `content/chapters/anti_patterns.md` — pilotní revize

**Create:**
- `docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot-retrospektiva.md` — výstup z Task 9 (kalibrace)

**Bez TDD test suite** — projekt nemá testovou infrastrukturu (`CLAUDE.md` ř. 25). Ekvivalent „testu" je schválení reportu uživatelem před zápisem oprav. Tato spec hovoří o tomto verifikačním kroku jako o povinné gate.

---

## Část I — Infrastruktura

### Task 1: Rozšířit `CLAUDE.md`

**Files:**
- Modify: `CLAUDE.md` (sekce „Voice, tón a jazyk", ř. 55–112)

- [ ] **Step 1: Rozšířit tabulku „Zakázáno — marketing a hype" (A1)**

V `CLAUDE.md` najít tabulku na ř. 71–82 (začíná `| Vzor | Problém |`) a před uzavírací řádek `| best practice (bez dalšího) | overused — nahradit konkrétním popisem |` přidat 5 nových řádků:

```markdown
| „není stříbrná kulka" | AI klišé, vyřezat nebo přeformulovat („má své limity", „nehodí se všude") |
| „není binární volba", „není černobílé" | AI klišé, vyřezat nebo přeformulovat |
| „svatý grál", „švýcarský nůž" | AI klišé, vyřezat nebo přeformulovat |
| „v každém případě platí, že" | obecná fráze, smazat nebo specifikovat |
| „v dnešní době", „v moderním vývoji" | časově nestabilní, smazat |
```

Použít Edit tool. `old_string` musí obsahovat dostatek kontextu, aby byl unikátní — zahrnout ř. 81–82.

- [ ] **Step 2: Doplnit pravidlo do „Pravidla věty" (A4)**

V `CLAUDE.md` najít sekci „### Pravidla věty" (ř. 63–67). Po posledním bullet (ř. 67 končí na „Je třeba poznamenat...") přidat nový bullet:

```markdown
- Mezi dvěma sousedními větami nikdy neopakovat stejné podstatné jméno v podmětu, pokud to nemá explicitní důvod (kontrastní porovnání). Použít synonymum nebo zájmeno, nebo věty spojit.
```

- [ ] **Step 3: Přidat novou sekci „Zakázáno — strukturní vzory" (A2)**

Vložit za sekci „### Zakázáno — výplň a filler" (končí ř. 103) a před sekci „### Typografie a forma" (začíná ř. 105):

```markdown
### Zakázáno — strukturní vzory

Pravidla na úrovni věty a odstavce:

- Dvojí výskyt stejného slovesa nebo plnovýznamového slova ve větě → přepsat. Příklad chyby: „přináší strukturu a vyjadřovací sílu, ale jeho složitost přináší řadu úskalí".
- Paralelismus dvou sousedních vět se stejnou syntaktickou kostrou → rozbít. Příklad: „Write model se soustředí výhradně na X. Read model se soustředí na Y." Druhou větu přeformulovat tak, aby měla jiný rytmus.
- Wikipedijní úvod sekce → vyřezat nebo přepsat. Definice: 1–2 věty obecného typu „X je přístup, který přináší Y a řeší Z" na začátku sekce, bez konkrétního zakotvení nebo vlastního názoru. Sekce má začínat rovnou věcí, kterou přináší novou.
- „Není to jen X, je to Y" / „Nejen X, ale i Y" → klišé, přepsat na věcné tvrzení.

```

- [ ] **Step 4: Přidat novou sekci „Strukturní rytmus seznamů" (A3)**

Vložit hned za sekci přidanou v Step 3, před „### Typografie a forma":

```markdown
### Strukturní rytmus seznamů

Bullet listy: vyhnout se uniformnímu rytmu napříč kapitolou.

- Pokud má kapitola 4+ bullet listů ve formátu `**Pojem** – věta vysvětlující X.`, alespoň jeden přepsat. Buď bez tučného leadu, nebo s různými délkami položek, nebo zlomit do dvou bloků (číslovaný + nečíslovaný), nebo nahradit prózou.
- Bullet list 4–7 položek se stejnou délkou věty = signál AI generování. Záměrně rozkolísat: jedna položka delší, jedna kratší, jedna jako věta bez bullet.
- Alespoň jednou v kapitole nahradit list souvislým odstavcem se třemi krátkými větami. Plynulost mění rytmus.

```

- [ ] **Step 5: Verifikovat změny**

Run: `grep -c "není stříbrná kulka" CLAUDE.md`
Expected: `1`

Run: `grep -c "Zakázáno — strukturní vzory" CLAUDE.md`
Expected: `1`

Run: `grep -c "Strukturní rytmus seznamů" CLAUDE.md`
Expected: `1`

Run: `grep -c "stejné podstatné jméno v podmětu" CLAUDE.md`
Expected: `1`

Pokud kterýkoli grep nevrátí 1, oprava chybí — vrátit se k danému kroku.

### Task 2: Aktualizovat `docs/prompts/review-chapter.md`

**Files:**
- Modify: `docs/prompts/review-chapter.md`

- [ ] **Step 1: Opravit cestu v hlavičce promptu**

Najít ř. 5 v `review-chapter.md`:
```
**Spuštění:** „Použij docs/prompts/review-chapter.md na templates/ddd/<soubor>.html.twig"
```

Nahradit za:
```
**Spuštění:** „Použij docs/prompts/review-chapter.md na content/chapters/<soubor>.md"
```

- [ ] **Step 2: Aktualizovat Průchod 4 odkaz na soubory**

Najít v Průchod 4 (ř. 83–98) řetězec `templates/ddd/`. Nahradit za `content/chapters/`. Týká se to ř. 85: „Přečti ostatní šablony v `templates/ddd/`."

Nový text: „Přečti ostatní kapitoly v `content/chapters/`."

- [ ] **Step 3: Aktualizovat sekci „Zápis oprav"**

Najít sekci „## Zápis oprav" (kolem ř. 127) a v bodě 4 nahradit:
```
4. Po zápisu spusť: `git diff templates/ddd/<soubor>.html.twig`
```
za:
```
4. Po zápisu spusť: `git diff content/chapters/<soubor>.md`
```

- [ ] **Step 4: Aktualizovat bod 2 v sekci „Zápis oprav" (Twig → Markdown)**

Najít:
```
2. Neměň HTML strukturu, ARIA atributy, SEO bloky — pouze textový obsah uvnitř `<p>`, `<li>`, `<h2>`, `<h3>`, `<td>` tagů
3. Zachovej Twig syntaxi a odsazení beze změny
```

Nahradit za:
```
2. Neměň frontmatter, callout markup (`:::callout{...}`), code bloky (`:::code{...}`), diagram bloky (`:::diagram{...}`), citace `[N]` a jejich URL, cross-linky (`/cqrs`, `/zakladni-koncepty`, atd.). Pouze textový obsah odstavců, bullet listů, nadpisů a tabulek.
3. Zachovej markdown syntaxi a odsazení beze změny
```

- [ ] **Step 5: Vložit Průchod 1.5 mezi Průchod 1 a Průchod 2**

Najít konec Průchodu 1 (končí oddělovačem `---` před `## Průchod 2 — Jazyková kvalita`). Mezi `---` a `## Průchod 2` vložit:

````markdown
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
5. **Klišé** — z rozšířeného seznamu v `CLAUDE.md` sekce „Zakázáno — marketing a hype": „není stříbrná kulka", „není binární volba", „svatý grál", „v dnešní době" atd.
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

````

Použít Edit tool. `old_string` musí obsahovat unikátní kontext: konec Průchodu 1 (`---`) a začátek Průchodu 2 (`## Průchod 2 — Jazyková kvalita`).

- [ ] **Step 6: Aktualizovat finální report v Průchodu 5**

Najít sekci „## Průchod 5 — Výstupní report" (kolem ř. 101). V markdownu reportu (kolem ř. 105–123) přidat sekci pro `[S-N]` nálezy.

Najít:
```
## Voice/tón — <N> nálezů
<záznamy [V-N]>

## Jazyk — <N> nálezů
```

Nahradit za:
```
## Voice/tón — <N> nálezů
<záznamy [V-N]>

## AI strukturní vzory — <N> nálezů
<záznamy [S-N]>

## Jazyk — <N> nálezů
```

- [ ] **Step 7: Verifikovat změny**

Run: `grep -c "Průchod 1.5 — AI strukturní vzory" docs/prompts/review-chapter.md`
Expected: `1`

Run: `grep -c "content/chapters/<soubor>.md" docs/prompts/review-chapter.md`
Expected: minimálně `2` (hlavička + zápis oprav)

Run: `grep -c "templates/ddd/" docs/prompts/review-chapter.md`
Expected: `0`

Run: `grep -c "Vzor A — narativní" docs/prompts/review-chapter.md`
Expected: `1`

Pokud kterýkoli check selže, oprava chybí.

### Task 3: Commit infrastruktury

**Files:**
- Stage: `CLAUDE.md`, `docs/prompts/review-chapter.md`, `docs/superpowers/specs/2026-05-05-anti-ai-revize-design.md`, `docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot.md`

- [ ] **Step 1: Zkontrolovat staging**

Run: `git status`

Očekávané změny:
- modified: `CLAUDE.md`
- modified: `docs/prompts/review-chapter.md`
- new: `docs/superpowers/specs/2026-05-05-anti-ai-revize-design.md`
- new: `docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot.md`

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md docs/prompts/review-chapter.md docs/superpowers/specs/2026-05-05-anti-ai-revize-design.md docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot.md
git commit -m "$(cat <<'EOF'
chore(content): zavést pravidla a průchod 1.5 pro detekci AI strukturních vzorů

Rozšiřuje CLAUDE.md o klišé („není stříbrná kulka" atd.), strukturní vzory
(paralelismy, wikipedijní úvody, dvojí slovo ve větě) a rytmus seznamů.
Přidává nový průchod 1.5 do review-chapter.md s referenčními vzory a
klasifikací 26 kapitol. Opravuje cestu šablon na content/chapters/*.md.
EOF
)"
```

**DŮLEŽITÉ:** commit zpráva BEZ Co-Authored-By Claude (per `feedback-no-claude-coauthor.md`).

- [ ] **Step 3: Verifikace**

Run: `git log -1 --pretty=format:'%s'`
Expected: `chore(content): zavést pravidla a průchod 1.5 pro detekci AI strukturních vzorů`

Run: `git log -1 --format=%B | grep -c 'Co-Authored-By'`
Expected: `0`

---

## Část II — Pilotní dávka

**Workflow per kapitola** (platí pro Tasks 4–8) — **autonomní mód** (volba C):

1. Před úkolem: load `CLAUDE.md` (nová pravidla), `docs/prompts/review-chapter.md`, vzory A/B/C dle Průchod 1.5.
2. Identifikuj typ kapitoly (narativní / definiční / hybridní).
3. Spusť všechny průchody (1, 1.5, 2, 3, 4) a vytvoř report dle formátu Průchod 5.
4. Zapiš opravy se stavem OPRAVIT do souboru. Návrhy se stavem NEJISTÉ neaplikuj.
5. Spusť `git diff content/chapters/<soubor>.md` a zaznamenej do logu pro pozdější human review.
6. Commit (jeden commit = jedna kapitola, žádné Co-Authored-By).
7. Pokračuj k další kapitole bez čekání.

**Ochrana** (instrukce pro implementer subagent):
- Řádky 22–34 v `what_is_ddd.md` (zdroj vzoru A) NEMĚNIT.
- Řádky 21–29 v `when_not_to_use_ddd.md` (zdroj vzoru B) NEMĚNIT.
- Frontmatter, callouts, code bloky, diagram bloky, citace `[N]` a jejich URL, cross-linky — NEMĚNIT.

### Task 4: Revize `what_is_ddd.md` (hybridní, zdroj vzoru A)

**Files:**
- Modify: `content/chapters/what_is_ddd.md`

**Klasifikace:** Hybridní. Otevírací část (ř. 22–42, BitPay story) je narativní = primárně vzor A. Sekce 01.01–01.05 jsou definiční = blíž vzoru B.

**Speciální upozornění:** Tato kapitola je zdroj vzoru A (ř. 22–34). Pasáž 22–34 NEMĚNIT — chrání kotvu tónu pro celý projekt. Revize se týká ř. 35+ a sekcí 01.01 dál.

- [ ] **Step 1: Spustit review-chapter.md**

Project ramcuje toto jako jednu interaktivní akci. Claude přečte CLAUDE.md a vzory A/B/C, pak provede všechny průchody nad `content/chapters/what_is_ddd.md`. Poznámka: neaplikovat změny na řádky 22–34 (zdroj vzoru A).

- [ ] **Step 2: Vypsat report ve formátu Průchod 5**

Zaznamenat report do logu (input do Task 9 retrospektivy):
```
# Revize: what_is_ddd.md
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
Celkem nálezů vyžadujících akci: <součet OPRAVIT>
```

- [ ] **Step 3: Zapsat opravy se stavem OPRAVIT**

Použít Edit tool na opravy se stavem OPRAVIT (ne NEJISTÉ). Zachovat:
- frontmatter
- callouts (`:::callout{...}`)
- code bloky (`:::code{...}`)
- diagram bloky (`:::diagram{...}`)
- citace `[N]` a jejich URL
- cross-linky
- markdown syntaxi a odsazení
- ŘÁDKY 22–34 (zdroj vzoru A)

- [ ] **Step 4: Git diff do logu**

Run: `git diff content/chapters/what_is_ddd.md`
Předat uživateli celý výstup.

- [ ] **Step 5: Commit**

```bash
git add content/chapters/what_is_ddd.md
git commit -m "refactor(content): anti-AI revize what_is_ddd.md (pilot)"
```

Bez Co-Authored-By.

### Task 5: Revize `basic_concepts.md` (definiční)

**Files:**
- Modify: `content/chapters/basic_concepts.md`

**Klasifikace:** Definiční. Primární vzor: B (when_not_to_use_ddd ř. 21–29) pro tón, C (preface ř. 21–23) pro úvody. Sekce s definicemi mají zůstat hutné, ale rozbít rytmus bulletů (CLAUDE.md sekce „Strukturní rytmus seznamů").

- [ ] **Step 1: Spustit review-chapter.md** se zaměřením na: pravidelnost rytmu bulletů (kapitola pravděpodobně silně postižena), wikipedijní úvody, paralelismy v definicích.

- [ ] **Step 2: Vypsat report**

Zaznamenat do logu ve formátu Průchod 5.

- [ ] **Step 3: Počkat na schválení reportu**

- [ ] **Step 4: Zapsat schválené opravy** se zachováním všech ochran (frontmatter, callouts, code, diagrams, `[N]` citace, cross-linky).

- [ ] **Step 4: Git diff do logu**

Run: `git diff content/chapters/basic_concepts.md`

- [ ] **Step 5: Commit**

```bash
git add content/chapters/basic_concepts.md
git commit -m "refactor(content): anti-AI revize basic_concepts.md (pilot)"
```

### Task 6: Revize `when_not_to_use_ddd.md` (narativní, zdroj vzoru B)

**Files:**
- Modify: `content/chapters/when_not_to_use_ddd.md`

**Klasifikace:** Narativní. Primární vzor: A. Sekundární: B.

**Speciální upozornění:** Tato kapitola je zdroj vzoru B (ř. 21–29). Pasáž 21–29 NEMĚNIT.

- [ ] **Step 1: Spustit review-chapter.md** s explicitní instrukcí neměnit ř. 21–29.

- [ ] **Step 2: Vypsat report**

- [ ] **Step 3: Počkat na schválení reportu**

- [ ] **Step 4: Zapsat schválené opravy** se zachováním všech ochran + ř. 21–29 (zdroj vzoru B).

- [ ] **Step 4: Git diff do logu**

Run: `git diff content/chapters/when_not_to_use_ddd.md`

- [ ] **Step 5: Commit**

```bash
git add content/chapters/when_not_to_use_ddd.md
git commit -m "refactor(content): anti-AI revize when_not_to_use_ddd.md (pilot)"
```

### Task 7: Revize `ddd_pain_points.md` (narativní)

**Files:**
- Modify: `content/chapters/ddd_pain_points.md`

**Klasifikace:** Narativní (katalog 20 problémů, ale s narativním tónem). Primární vzor: A. Hodně bullet listů typu „Problém / Příčina / Řešení" — průchod 1.5 ostře hledá pravidelnost rytmu napříč 20 sekcemi.

- [ ] **Step 1: Spustit review-chapter.md** se zaměřením na: pravidelnost struktury 20 podsekcí (alespoň 2–3 z nich přepsat tak, aby měly jiný rytmus, nebo část nahradit prózou).

- [ ] **Step 2: Vypsat report**

- [ ] **Step 3: Počkat na schválení reportu**

- [ ] **Step 4: Zapsat schválené opravy**

- [ ] **Step 4: Git diff do logu**

Run: `git diff content/chapters/ddd_pain_points.md`

- [ ] **Step 5: Commit**

```bash
git add content/chapters/ddd_pain_points.md
git commit -m "refactor(content): anti-AI revize ddd_pain_points.md (pilot)"
```

### Task 8: Revize `anti_patterns.md` (definiční)

**Files:**
- Modify: `content/chapters/anti_patterns.md`

**Klasifikace:** Definiční (katalog anti-vzorů). Primární vzor: B. Hodně paralelní struktury „Špatně / Správně" — průchod 1.5 hledá paralelismy v úvodech sekcí a wikipedijní definice typu „Anti-vzor X je situace, kdy…".

- [ ] **Step 1: Spustit review-chapter.md** se zaměřením na: úvod každé z anti-pattern sekcí (typický kandidát na wikipedijní úvod), pravidelnost callout struktur.

- [ ] **Step 2: Vypsat report**

- [ ] **Step 3: Počkat na schválení reportu**

- [ ] **Step 4: Zapsat schválené opravy**

- [ ] **Step 4: Git diff do logu**

Run: `git diff content/chapters/anti_patterns.md`

- [ ] **Step 5: Commit**

```bash
git add content/chapters/anti_patterns.md
git commit -m "refactor(content): anti-AI revize anti_patterns.md (pilot)"
```

---

## Část III — Pilot retrospektiva

### Task 9: Kalibrace po pilotu

**Files:**
- Create: `docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot-retrospektiva.md`
- Possibly modify: `CLAUDE.md`, `docs/prompts/review-chapter.md`

- [ ] **Step 1: Sebrat data z pilotní dávky**

Pro každou z 5 kapitol shrnout:
- Počet nálezů v každé kategorii (V, S, J, F, K)
- Které kategorie byly nejčastější
- Které nálezy byly hraniční / sporné
- Místa, kde si Claude nebyl jistý

Výstupem je tabulka kapitola × kategorie → počet nálezů.

- [ ] **Step 2: Identifikovat opakující se vzory**

Z dat ze Step 1:

- Pokud se kategorie [S-N] kategorie 1 (wikipedijní úvody) opakuje 3+ kapitolách → kandidát na rozšíření CLAUDE.md
- Pokud kategorie [S-N] kategorie X má méně než 1 nález napříč pilotem → možná moc úzká definice, zvážit rozšíření v Průchod 1.5
- Pokud uživatel u 3+ kapitol odmítl konkrétní typ návrhu → kalibrovat instrukci v Průchod 1.5

- [ ] **Step 3: Zhodnotit kvalitu zásahu**

Pro každou kapitolu z pilotu:
- Příliš agresivní = uživatel zamítl > 30 % návrhů → změkčit instrukci
- Příliš konzervativní = po revizi by bystré oko stále poznalo AI → zostřit instrukci
- Správně = uživatel schválil > 70 % návrhů beze změny

Vyvodit, který směr (agresivnější / konzervativnější) bude pro dávky 2–5.

- [ ] **Step 4: Zapsat retrospektivu**

Vytvořit `docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot-retrospektiva.md` se sekcemi:

```markdown
# Pilot retrospektiva — anti-AI revize

**Datum:** <datum>
**Rozsah:** Dávka 1 (5 kapitol)

## Data

<tabulka kapitola × kategorie nálezů>

## Co fungovalo

<bullet list pozorování>

## Co je třeba kalibrovat

### CLAUDE.md
<bullet list návrhů na rozšíření, pokud jsou>

### review-chapter.md, Průchod 1.5
<bullet list návrhů na úpravu instrukce, pokud jsou>

## Doporučení pro dávky 2–5

<jednoznačné rozhodnutí: pokračovat beze změn / pokračovat po malé úpravě / pozastavit a přepracovat prompt>

## Další plán

Po schválení této retrospektivy: napsat `docs/superpowers/plans/<datum>-anti-ai-revize-davky-2-5.md` s upravenou instrukcí.
```

- [ ] **Step 5: Aplikovat kalibrace (pokud je třeba)**

Pokud retrospektiva navrhuje úpravy:
1. Edit `CLAUDE.md` — přidat / upravit pravidla
2. Edit `docs/prompts/review-chapter.md` — upravit Průchod 1.5
3. Předat uživateli diff k schválení
4. Commit:
```bash
git add CLAUDE.md docs/prompts/review-chapter.md docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot-retrospektiva.md
git commit -m "chore(content): kalibrace anti-AI revize po pilotní dávce"
```

Pokud retrospektiva nedoporučuje žádné úpravy infrastruktury:
```bash
git add docs/superpowers/plans/2026-05-05-anti-ai-revize-pilot-retrospektiva.md
git commit -m "docs: retrospektiva pilotní dávky anti-AI revize"
```

- [ ] **Step 6: Předat uživateli k schválení**

Předat retrospektivu a doporučení. Po schválení napsat samostatný plán pro dávky 2–5 (mimo rozsah tohoto plánu).

---

## Konec plánu

Po dokončení Task 9 je tento plán uzavřen. Dávky 2–5 (zbylé 21 kapitol) budou v samostatném plánu, který vznikne po schválení retrospektivy. Spec sekce H to vyžaduje záměrně — kalibrace musí proběhnout před masovou aplikací.
