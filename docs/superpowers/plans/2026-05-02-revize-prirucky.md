# Kompletní revize DDD příručky — implementační plán

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Provést revizi voice/tónu, jazykové kvality a konzistence napříč 26 kapitolami DDD příručky podle pravidel z `CLAUDE.md`.

**Architecture:** Fáze 1 = paralelní subagenti per kapitola ve várkách po 5 (voice + jazyk), s diff review a commitem v hlavní konverzaci. Fáze 2 = sériová kontrola konzistence napříč kapitolami v hlavní konverzaci.

**Tech Stack:** Markdown soubory v `content/chapters/`, git, pravidla z `CLAUDE.md` a `docs/prompts/review-chapter.md`.

**Spec:** `docs/superpowers/specs/2026-05-02-revize-prirucky-design.md`

---

## Společný prompt pro subagenta (Fáze 1)

Tento prompt se používá ve všech taskách Fáze 1. Při dispatchování subagenta dosaď za `<NAME>` jméno kapitoly bez přípony (např. `what_is_ddd`).

````
Provádíš revizi jedné kapitoly DDD příručky podle pevně daných pravidel.

VSTUP:
- Soubor: /home/michal/Work/ddd-v-symfony/content/chapters/<NAME>.md
- Pravidla: /home/michal/Work/ddd-v-symfony/CLAUDE.md sekce "Voice, tón a jazyk"

POSTUP:
1. Načti CLAUDE.md a vyhledej sekci "Voice, tón a jazyk". Drž se přesně toho seznamu.
2. Načti přidělený soubor.
3. Projdi soubor řádek po řádku a identifikuj porušení v tomto pořadí:
   a) Voice/tón:
      - Marketing/hype slova z tabulky v CLAUDE.md (mocný, výkonný, elegantní, robustní, revoluční, moderní, perfektní, ideální, bezproblémový, jednoduše, snadno, "posune na další úroveň", "plně využít potenciál", "best practice" bez kontextu, ...)
      - Výplňové fráze z tabulky v CLAUDE.md ("je důležité si uvědomit", "hraje klíčovou roli", "stojí za zmínku", "je třeba poznamenat", "v rámci", "klíčový" bez obsahu, "s ohledem na", "v neposlední řadě", "samozřejmě", "zcela/naprosto/absolutně", "není pochyb o tom", ...)
      - Em dash (—) → en pomlčka s mezerami: " – "
      - Anglické uvozovky "..." → české „..."
      - "Tady" → "Zde"
      - Tykání → vykání
      - Osobní komentáře autora ("z mé zkušenosti", "překvapilo mě")
   b) Délka věty:
      - Věty přes 25 slov rozděl na kratší celky (každá věta = jedna myšlenka)
   c) Pasivní vazby:
      - Tam, kde aktivum nezmění smysl ani neprotáhne větu, použij aktivum
      - Pasivní vazby ponech jen tam, kde aktivum vyžaduje uměle dosadit subjekt
   d) Nominalizace:
      - "provedení implementace" → "implementovat"
      - "zajištění konzistentnosti" → "zajistit konzistentnost"
      - "v procesu modelování" → "při modelování"
   e) Germanismy a anglicismy:
      - "z důvodu toho, že" → "protože"
      - "na základě toho" → "proto"
      - Anglicismy mimo technické termíny (technické termíny ZACHOVAT)
4. Zapiš opravy POMOCÍ Edit tool. NEPOUŽÍVEJ Write.
5. NECOMMITUJ. NESPOUŠTĚJ git příkazy.

CO NESMÍŠ ZMĚNIT:
- Frontmatter mezi --- ... ---
- Kódové bloky ``` ... ```
- Inline kód `...`
- Identifikátory v kódu, názvy tříd, metod, atributů
- Citace a bibliografické údaje (jména autorů, roky, ISBN)
- Strukturu nadpisů (# ## ### ####) ani jejich pořadí
- URL adresy uvnitř markdown linků (text linku upravit můžeš, URL ne)
- Anchor odkazy a kotvy (#section)
- HTML tagy, pokud někde zbyly

CO MŮŽEŠ ZMĚNIT:
- Pouze text v odstavcích, položkách seznamů, popiscích obrázků a tabulek, callout/admonition obsahu
- Text uvnitř nadpisu jen pokud obsahuje výplňovou frázi, jinak ne

TECHNICKÉ TERMÍNY ZACHOVAT (i když jsou anglické):
repository, bounded context, aggregate, aggregate root, entity, value object,
domain event, event sourcing, CQRS, saga, process manager, outbox, projection,
read model, read-model, write model, ubiquitous language, anti-corruption layer,
ACL, context map, context mapping, microservice, modular monolith, distributed
monolith, domain service, application service, factory, specification, anemic
model, strangler fig, eventual consistency, conformist, customer/supplier,
partnership, open host service, published language, shared kernel, separate
ways, big ball of mud, hexagonal architecture, onion architecture, clean
architecture, ports and adapters, voter, ABAC, RBAC, snapshot, event store,
command, query, message bus, Doctrine, Symfony, Messenger, DI, ORM, DTO

TYPOGRAFIE:
- Em dash (—) → en pomlčka s mezerami: " – "
- Anglické uvozovky "..." → české „..."
- Tři tečky bez mezer (např. "atd...") → " ..." (s mezerou před)

REPORT (vrať na konci jako poslední zprávu, čistý text):

# Report: <NAME>.md

## Voice/tón — N úprav
- L<řádek>: <stručně co (např. "smazána 'je důležité si uvědomit, že'")>
- L<řádek>: ...

## Jazyk — N úprav
- L<řádek>: <např. "pasivum → aktivum">
- L<řádek>: <např. "rozdělena věta o 38 slovech na dvě">

## Upozornění (žádné úpravy, jen flag)
- L<řádek>: <např. "raw HTML <div>, zbytek po Twig migraci — neopraveno">

## Místa, kde jsem si nebyl jistý
- L<řádek>: <proč jsi neupravil>

## Souhrn
- Voice/tón: N úprav
- Jazyk: N úprav
- Upozornění: N
- Nejistá místa: N
````

Pokud subagent v reportu vrátí cokoli v sekci „Místa, kde jsem si nebyl jistý" nebo „Upozornění" → hlavní konverzace o tom uživateli povinně řekne před commitem.

---

## Task 1: Příprava — kontrola výchozího stavu

**Files:** žádné soubory se nemění, pouze ověřuje stav

- [ ] **Step 1: Ověř, že pracovní strom je čistý nebo má jen image artefakty**

Run: `cd /home/michal/Work/ddd-v-symfony && git status`

Expected: Pouze untracked image soubory (.jpg/.png), žádné modifikace v `content/chapters/`. Pokud jsou modifikace v `content/chapters/` nebo jiných verzovaných souborech, ZASTAV a zeptej se uživatele.

- [ ] **Step 2: Ověř, že všech 26 kapitol existuje**

Run:
```bash
ls /home/michal/Work/ddd-v-symfony/content/chapters/*.md | wc -l
```

Expected: `26`

- [ ] **Step 3: Ulož baseline řádek countů (referenčně)**

Run:
```bash
wc -l /home/michal/Work/ddd-v-symfony/content/chapters/*.md | sort -n
```

Expected: 26 řádků + součet `25638 celkem` (přibližně, drobné rozdíly OK).

---

## Task 2: Várka A — kapitoly 01–05 (basics)

**Files:**
- Modify: `content/chapters/what_is_ddd.md`
- Modify: `content/chapters/subdomains.md`
- Modify: `content/chapters/context_mapping.md`
- Modify: `content/chapters/event_storming.md`
- Modify: `content/chapters/team_topologies.md`

- [ ] **Step 1: Dispatch 5 subagentů paralelně**

Pošli JEDNU zprávu s pěti `Agent` tool calls (subagent_type=`general-purpose`) — každý s promptem ze sekce „Společný prompt pro subagenta" výše, kde za `<NAME>` dosadíš:

1. `what_is_ddd`
2. `subdomains`
3. `context_mapping`
4. `event_storming`
5. `team_topologies`

Description pro každý: `Revize <NAME>.md per CLAUDE.md`.

- [ ] **Step 2: Po doběhnutí všech 5 — projdi reporty**

Pro každý subagent:
- Pokud sekce „Upozornění" nebo „Místa, kde jsem si nebyl jistý" obsahují něco netriviálního → zmín to uživateli stručně.

- [ ] **Step 3: Pro každou z 5 kapitol projdi diff a commitni**

Pro každou kapitolu (5×) sekvenčně:

```bash
cd /home/michal/Work/ddd-v-symfony
git --no-pager diff content/chapters/<NAME>.md
```

Pokud diff vypadá rozumně:
```bash
git add content/chapters/<NAME>.md
git commit -m "chore(content): revize <NAME>"
```

Pokud konkrétní hunk je sporný (např. změnil termín, kterému by neměl, nebo zkrátil větu nešťastně):
1. Před uživatelem ukaž konkrétní hunk a zeptej se
2. Pokud uživatel řekne „ne tento hunk" → použij `git restore -p content/chapters/<NAME>.md` a interaktivně odmítni jen ten hunk (nebo ručně edituj zpět)
3. Po opravě commitni zbytek

Commit message vždy: `chore(content): revize <route>` (bez Co-Authored-By).

- [ ] **Step 4: Ověř git log po várce**

Run: `git log --oneline -10`

Expected: Posledních 5 commitů jsou `chore(content): revize <route>` pro 5 kapitol z této várky.

---

## Task 3: Várka B — kapitoly 06–10 (tactics + první 2 architecture)

**Files:**
- Modify: `content/chapters/basic_concepts.md`
- Modify: `content/chapters/aggregate_design.md`
- Modify: `content/chapters/lesser_known_patterns.md`
- Modify: `content/chapters/architectural_styles.md`
- Modify: `content/chapters/horizontal_vs_vertical.md`

- [ ] **Step 1: Dispatch 5 subagentů paralelně**

Stejný postup jako Task 2 Step 1. Substituce:
1. `basic_concepts`
2. `aggregate_design`
3. `lesser_known_patterns`
4. `architectural_styles`
5. `horizontal_vs_vertical`

- [ ] **Step 2: Projdi reporty stejně jako Task 2 Step 2**

- [ ] **Step 3: Diff + commit per kapitola, postup jako Task 2 Step 3**

- [ ] **Step 4: Ověř git log**

Run: `git log --oneline -10`
Expected: 5 nových commitů `chore(content): revize <route>` z této várky.

---

## Task 4: Várka C — kapitoly 11–15 (zbytek architecture + první 2 patterns)

**Files:**
- Modify: `content/chapters/implementation_in_symfony.md`
- Modify: `content/chapters/authorization_in_ddd.md`
- Modify: `content/chapters/cqrs.md`
- Modify: `content/chapters/event_sourcing.md`
- Modify: `content/chapters/sagas.md`

- [ ] **Step 1: Dispatch 5 subagentů paralelně**

Substituce:
1. `implementation_in_symfony`
2. `authorization_in_ddd`
3. `cqrs`
4. `event_sourcing`
5. `sagas`

POZN.: Tyto kapitoly jsou velké (1300–1630 řádků). Subagent může běžet déle. Trpělivost.

- [ ] **Step 2: Projdi reporty (Task 2 Step 2)**

- [ ] **Step 3: Diff + commit per kapitola (Task 2 Step 3)**

POZN.: Diff může být dlouhý → použij `git --no-pager diff content/chapters/<NAME>.md | head -200` pro přehled, pak po sekcích.

- [ ] **Step 4: Ověř git log (Task 2 Step 4)**

---

## Task 5: Várka D — kapitoly 16–20 (zbytek patterns + první 3 practice)

**Files:**
- Modify: `content/chapters/outbox_pattern.md`
- Modify: `content/chapters/performance_aspects.md`
- Modify: `content/chapters/testing_ddd.md`
- Modify: `content/chapters/migration_from_crud.md`
- Modify: `content/chapters/microservices_and_ddd.md`

- [ ] **Step 1: Dispatch 5 subagentů paralelně**

Substituce:
1. `outbox_pattern`
2. `performance_aspects`
3. `testing_ddd`
4. `migration_from_crud`
5. `microservices_and_ddd`

- [ ] **Step 2: Projdi reporty (Task 2 Step 2)**

- [ ] **Step 3: Diff + commit per kapitola (Task 2 Step 3)**

- [ ] **Step 4: Ověř git log (Task 2 Step 4)**

---

## Task 6: Várka E — kapitoly 21–25 (zbytek practice + synthesis)

**Files:**
- Modify: `content/chapters/ddd_pain_points.md`
- Modify: `content/chapters/anti_patterns.md`
- Modify: `content/chapters/when_not_to_use_ddd.md`
- Modify: `content/chapters/practical_examples.md`
- Modify: `content/chapters/case_study.md`

- [ ] **Step 1: Dispatch 5 subagentů paralelně**

Substituce:
1. `ddd_pain_points`
2. `anti_patterns`
3. `when_not_to_use_ddd`
4. `practical_examples`
5. `case_study`

- [ ] **Step 2: Projdi reporty (Task 2 Step 2)**

- [ ] **Step 3: Diff + commit per kapitola (Task 2 Step 3)**

- [ ] **Step 4: Ověř git log (Task 2 Step 4)**

---

## Task 7: Várka F — extras (ddd_ai)

**Files:**
- Modify: `content/chapters/ddd_ai.md`

- [ ] **Step 1: Dispatch 1 subagenta**

Pošli jednu `Agent` tool call s promptem ze sekce „Společný prompt", `<NAME>` = `ddd_ai`.

- [ ] **Step 2: Projdi report**

- [ ] **Step 3: Diff + commit**

```bash
cd /home/michal/Work/ddd-v-symfony
git --no-pager diff content/chapters/ddd_ai.md
```

Pokud OK:
```bash
git add content/chapters/ddd_ai.md
git commit -m "chore(content): revize ddd_ai"
```

- [ ] **Step 4: Ověř, že fáze 1 je kompletní**

Run:
```bash
git log --oneline | grep "chore(content): revize" | wc -l
```

Expected: `26` (jeden commit per kapitola).

---

## Task 8: Fáze 2 — Konzistence napříč kapitolami

**Files:** všech 26 kapitol potenciálně, dle nálezů

Tato fáze běží sériově v hlavní konverzaci. Žádné subagenty.

- [ ] **Step 1: Vytáhni definice klíčových termínů napříč kapitolami**

Pro každý termín spusť grep a porovnej, jak je definován:

```bash
cd /home/michal/Work/ddd-v-symfony
grep -nE "(Bounded Context|Bounded context|bounded context)" content/chapters/*.md | head -50
grep -nE "(Agregát|agregát|Aggregate)" content/chapters/*.md | head -50
grep -nE "(Doménová událost|doménová událost|Domain event|Domain Event)" content/chapters/*.md | head -50
grep -nE "(Repozitář|repozitář|Repository)" content/chapters/*.md | head -50
grep -nE "(Value Object|Hodnotový objekt|hodnotový objekt)" content/chapters/*.md | head -50
grep -nE "(Subdoména|subdoména|Subdomain)" content/chapters/*.md | head -50
grep -nE "(Ubiquitous Language|Všudypřítomný jazyk|jednotný jazyk)" content/chapters/*.md | head -50
grep -nE "(Anti-Corruption Layer|Anti-corruption layer|ACL)" content/chapters/*.md | head -50
```

Pro každý termín, kde je definice rozporná:
- Vyber primární definici (preferuj `basic_concepts.md` nebo glosář, pokud existuje)
- Sjednoť napříč kapitolami pomocí `Edit` tool

- [ ] **Step 2: Commit za sjednocení definic**

Pokud se aspoň jedna kapitola změnila:
```bash
git add content/chapters/
git commit -m "chore(content): sjednocení definic klíčových termínů napříč kapitolami"
```

Pokud nic nepotřebovalo úpravu, přeskoč commit.

- [ ] **Step 3: Najdi anchor odkazy mezi kapitolami a ověř, že kotvy existují**

Run:
```bash
cd /home/michal/Work/ddd-v-symfony
grep -nE "\]\([a-z_-]+#[a-z0-9-]+\)" content/chapters/*.md
```

Pro každý nalezený odkaz `[text](other-chapter#anchor)`:
1. Cílový soubor musí existovat
2. V cílovém souboru musí být nadpis, který odpovídá `#anchor` (Markdown automaticky generuje slug z nadpisu — anchor odpovídá lowercased nadpisu se spojovníky místo mezer; diakritika se obvykle zachovává v slugu závisle na pipeline)

Pokud najdeš rozbitý odkaz, oprav ho:
- Buď uprav text odkazu na existující kotvu
- Nebo přidej kotvu do cílového souboru, pokud má smysl (ale nesahej na strukturu, jen pokud chybí jen explicitní `{#anchor}` u existujícího nadpisu)
- Pokud cíl neexistuje a oprava není zřejmá → zeptej se uživatele

- [ ] **Step 4: Commit za anchor odkazy**

Pokud se něco změnilo:
```bash
git add content/chapters/
git commit -m "chore(content): oprava anchor odkazů mezi kapitolami"
```

- [ ] **Step 5: Ověř číslování v textu**

Run:
```bash
cd /home/michal/Work/ddd-v-symfony
grep -nE "(kapitol[ae] [0-9]+|Hub [0-9])" content/chapters/*.md
```

Pro každý výskyt ověř, že číslo sedí s `App\Catalog\Chapters::all()` (zdroj pravdy je `src/Catalog/Chapters.php`):
- "kapitola 7" má odkazovat na `aggregate_design`
- "Hub 4" je `patterns`
- atd. (viz tabulka ve specu)

Pokud něco nesedí, oprav.

- [ ] **Step 6: Commit za číslování**

Pokud se něco změnilo:
```bash
git add content/chapters/
git commit -m "chore(content): oprava číslování kapitol a hubů v textu"
```

- [ ] **Step 7: Sjednocení s glosářem (pokud existuje)**

Run:
```bash
cd /home/michal/Work/ddd-v-symfony
ls templates/ddd/glossary*.html.twig 2>/dev/null
ls content/chapters/ | grep -i glossary 2>/dev/null
```

Pokud glosář existuje (jako Twig šablona, protože není v `content/chapters/`):
1. Načti ho
2. Pro každý termín v glosáři grep první výskyt v každé kapitole
3. Pokud první výskyt termínu v kapitole používá jiný překlad/formulaci než glosář → sjednoť na glosář

Pokud glosář neexistuje, přeskoč tento krok.

- [ ] **Step 8: Commit za glosář**

```bash
git add content/chapters/
git commit -m "chore(content): sjednocení terminologie s glosářem"
```

(Pokud nic nezměněno, přeskoč.)

---

## Task 9: Závěrečná validace

**Files:** žádné

- [ ] **Step 1: Ověř, že fáze 1 zanechala 26 commitů**

Run:
```bash
git log --oneline | grep "chore(content): revize" | wc -l
```

Expected: `26`.

- [ ] **Step 2: Ověř, že není v `content/chapters/` nic necommitnutého**

Run: `git status content/chapters/`

Expected: `nothing to commit, working tree clean` (nebo neuvádí žádný `.md` soubor).

- [ ] **Step 3: Ověř, že struktura každé kapitoly je nadále validní**

Run:
```bash
cd /home/michal/Work/ddd-v-symfony
for f in content/chapters/*.md; do
  head -1 "$f" | grep -q "^---" || echo "ŠPATNĚ: $f nemá frontmatter"
done
```

Expected: žádný výstup (všechny kapitoly mají frontmatter).

- [ ] **Step 4: Ověř, že počet kódových bloků se nezměnil**

Pro spot check vybraných kapitol porovnej count ` ``` ` s baseline (mělo by zůstat sudé číslo):

```bash
for f in content/chapters/*.md; do
  count=$(grep -c '^```' "$f")
  if [ $((count % 2)) -ne 0 ]; then
    echo "ŠPATNĚ: $f má lichý počet code fence ($count)"
  fi
done
```

Expected: žádný výstup.

- [ ] **Step 5: Stručný report uživateli**

Vytvoř shrnutí pro uživatele:
- Počet kapitol upraveno: X / 26
- Celkový počet úprav voice/tón: součet z reportů subagentů
- Celkový počet úprav jazyk: součet z reportů subagentů
- Konzistence: počet úprav v Task 8
- Upozornění od subagentů (Twig artefakty, atd.)
- `git log --oneline` posledních ~30 commitů
