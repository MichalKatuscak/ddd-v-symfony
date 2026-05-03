# Revize DDD příručky – Fáze 3: implementační plán

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dokončit revizi příručky pokrytím textových oblastí, které byly mimo Fázi 1 a 2 – hub stránky, glosář, popisky diagramů a pravopis v MD obsahu.

**Architecture:** Tři sériové passy na vlastní větvi `revize-prirucky-faze-3`. Každý pass je 1 commit. Pass A používá paralelní subagenty (jako Fáze 1). Pass B používá `aspell --lang=cs` jako mechanický nástroj. Pass C upravuje `.puml` zdrojáky a rekompiluje SVG. Finální PR pro review.

**Tech Stack:** PHP/Symfony, Twig templaty, Markdown, PlantUML 1.2020.02, aspell-cs 0.51.0-1.3, git, gh CLI.

---

## Předpoklady

- Větev `revize-prirucky-faze-3` existuje a je aktivní (vytvořena při commitu specu).
- aspell + aspell-cs nainstalované (ověřeno: `echo "test" | aspell --lang=cs --mode=none list`).
- plantuml v `/usr/bin/plantuml` (PlantUML 1.2020.02).
- gh CLI funkční (Fáze 2 PR byla uzavřena přes gh).
- Origin remote `origin` ukazuje na `MichalKatuscak/ddd-v-symfony`.

---

## Task 1: Pass A – Hub stránky a glosář

Voice/jazyk pass na 9 Twig šablon (8 hubů + glosář). Subagenti paralelně, 2 várky (5 + 4).

**Files:**
- Modify: `templates/ddd/glossary.html.twig`
- Modify: `templates/ddd/hub_basics.html.twig`
- Modify: `templates/ddd/hub_tactics.html.twig`
- Modify: `templates/ddd/hub_architecture.html.twig`
- Modify: `templates/ddd/hub_patterns.html.twig`
- Modify: `templates/ddd/hub_practice.html.twig`
- Modify: `templates/ddd/hub_synthesis.html.twig`
- Modify: `templates/ddd/hub_reference.html.twig`

### Subagent prompt (šablona)

````
Provedeš voice/jazyk revizi jedné Twig šablony DDD příručky.

# Kontext
- Projekt: DDD v Symfony, obsahový web v češtině.
- CLAUDE.md sekce „Voice, tón a jazyk" definuje pravidla. PŘEČTI JI PŘED EDITACÍ: /home/michal/Work/ddd-v-symfony/CLAUDE.md
- Soubor: templates/ddd/<SOUBOR>.html.twig
- Příručka byla revidována ve Fázi 1 (kapitoly v MD) a Fázi 2 (konzistence). Tento pass dokončuje hub stránky a glosář.

# Co dělat
1. Přečti CLAUDE.md sekci „Voice, tón a jazyk".
2. Přečti přidělený soubor.
3. Aplikuj opravy podle pravidel:
   - Voice/tón: zakázané fráze (marketing, hype, výplň), em dash → en pomlčka, anglické uvozovky → české „", „Tady" → „Zde", vykání.
   - Délka věty ≤ 25 slov (delší rozdělit, kde nemění smysl).
   - Pasiva → aktiva (kde nemění smysl).
   - Nominalizace → slovesné vazby.
4. Zapiš opravy do souboru pomocí Edit toolu.
5. NESÁHNI:
   - HTML strukturu (tagy, atributy, třídy)
   - Twig syntaxi ({% %}, {{ }}, {# #})
   - ARIA atributy
   - SEO bloky: <title>, <meta>, <link rel="canonical">, og:*, twitter:*
   - JSON-LD schema.org bloky uvnitř <script type="application/ld+json">
   - Strukturu nadpisů, pořadí sekcí, identifikátory id="..."
   - Faktické nepřesnosti v definicích – jen flagni v reportu
6. NECOMMITUJ. Jen edituj soubor.

# Co je v rozsahu
- Textový obsah uvnitř <p>, <li>, <h2>, <h3>, <h4>, <td>, <strong>, <em>, <span>, atd.
- V Twig blocích vkládané texty: hub_eyebrow, hub_h_main, hub_h_em, hub_deck, hub_part_title, hub_part_sub, hub_meta hodnoty.

# Výstup – jednostránkový report
## Voice/tón – N úprav
- Stručný popis úprav per kategorie (zakázaná slova, em dash, uvozovky, „Tady"→„Zde")

## Jazyk – N úprav
- Pasiva, nominalizace, délka vět

## Upozornění (neopravené)
- Faktické věci, které vypadají sporně, ale nesahám na ně
- HTML/Twig artefakty, pokud nějaké jsou

## Místa nejistá
- Místa, kde jsem si nebyl jistý a edit jsem raději neudělal

## Souhrn
- Celkový počet úprav, hlavní vzory
````

- [ ] **Step 1: Várka 1 – paralelní subagenti pro 5 souborů**

Spusť 5 paralelních subagentů (general-purpose) v jedné zprávě, každý dostane prompt výše s vyplněným SOUBOR:
- glossary
- hub_basics
- hub_tactics
- hub_architecture
- hub_patterns

- [ ] **Step 2: Projít diff várky 1**

```bash
git diff templates/ddd/glossary.html.twig templates/ddd/hub_basics.html.twig templates/ddd/hub_tactics.html.twig templates/ddd/hub_architecture.html.twig templates/ddd/hub_patterns.html.twig | head -200
```

Hledat:
- Změny v HTML tagech, atributech, ARIA, SEO, JSON-LD → odmítnout cílenou opravou (`git restore -p <soubor>`)
- Změny v Twig syntaxi → odmítnout
- Sporné voice úpravy → ponechat / zrušit

- [ ] **Step 3: Várka 2 – paralelní subagenti pro 4 zbývající**

Spusť 4 paralelní subagenty:
- hub_practice
- hub_synthesis
- hub_reference

A jeden navíc (podle volné kapacity – pokud žádný, jen 3): nepoužitý slot.

- [ ] **Step 4: Projít diff várky 2**

```bash
git diff templates/ddd/hub_practice.html.twig templates/ddd/hub_synthesis.html.twig templates/ddd/hub_reference.html.twig
```

- [ ] **Step 5: Vizuální ověření**

Spustit dev server a otevřít hub stránky + glosář:

```bash
symfony server:start --no-tls -d
```

Otevřít v prohlížeči (nebo curl):
- `/zaklady`, `/takticke-vzory`, `/architektura`, `/vzory`, `/praxe`, `/synteza`, `/reference`
- `/glosar`

Ověřit, že stránky se renderují bez chyb (200 OK), žádné Twig errory.

```bash
for path in zaklady takticke-vzory architektura vzory praxe synteza reference glosar; do
  curl -s -o /dev/null -w "%{http_code} /$path\n" http://127.0.0.1:8000/$path
done
```

Expected: 8× `200`.

```bash
symfony server:stop
```

- [ ] **Step 6: Commit**

```bash
git add templates/ddd/glossary.html.twig templates/ddd/hub_*.html.twig
git commit -m "chore(content): revize hub stránek a glosáře"
```

---

## Task 2: Pass B – Pravopis v MD

Mechanický spell check napříč 26 MD soubory pomocí `aspell-cs`. Whitelist technických termínů, manuální review zbytku.

**Files:**
- Modify: `content/chapters/*.md` (26 souborů, podle nálezů)
- Create: `/tmp/aspell-whitelist.txt` (dočasný whitelist)
- Create: `/tmp/aspell-results/<chapter>.txt` (per-chapter výsledky)

- [ ] **Step 1: Vytvořit whitelist technických termínů**

```bash
mkdir -p /tmp/aspell-results
cat > /tmp/aspell-whitelist.txt <<'EOF'
DDD
CQRS
ORM
ACL
API
HTTP
HTTPS
JSON
JSONB
JSON-LD
SQL
NoSQL
PHP
PHPUnit
Symfony
Doctrine
Twig
Composer
Messenger
GitHub
GitLab
PostgreSQL
MySQL
Redis
RabbitMQ
Kafka
Stripe
SES
AWS
Bounded
Context
Aggregate
Root
ValueObject
Repository
Specification
Factory
Saga
Outbox
Inbox
EventSourcing
EventStore
ReadModel
WriteModel
Projection
Projector
CommandHandler
QueryHandler
EventBus
CommandBus
QueryBus
ACL
DTO
UUID
ULID
OpenAPI
GraphQL
gRPC
REST
TCP
WebSocket
React
Vue
TypeScript
JavaScript
Node
npm
yarn
pnpm
Linux
macOS
Windows
EOF
echo "Whitelist created: $(wc -l </tmp/aspell-whitelist.txt) entries"
```

- [ ] **Step 2: Funkce pro spell check kapitoly**

Vytvoř shell funkci, která:
1. Přečte `.md`
2. Odstraní kódové bloky (```...```), inline kód (`...`), frontmatter (---...---), URL
3. Spustí `aspell --lang=cs list`
4. Odfiltruje whitelist
5. Uloží do `/tmp/aspell-results/<chapter>.txt`

```bash
spell_check() {
  local file="$1"
  local name=$(basename "$file" .md)
  python3 -c "
import re, sys
text = open('$file').read()
# Strip frontmatter
text = re.sub(r'^---.*?---', '', text, count=1, flags=re.DOTALL)
# Strip fenced code blocks
text = re.sub(r'\`\`\`.*?\`\`\`', '', text, flags=re.DOTALL)
# Strip inline code
text = re.sub(r'\`[^\`]*\`', '', text)
# Strip URLs
text = re.sub(r'https?://\S+', '', text)
# Strip markdown links (keep text)
text = re.sub(r'\[([^\]]+)\]\([^)]+\)', r'\1', text)
sys.stdout.write(text)
" | aspell --lang=cs --mode=none --encoding=UTF-8 list \
    | sort -u \
    | grep -vxFf /tmp/aspell-whitelist.txt \
    > /tmp/aspell-results/${name}.txt
  local n=$(wc -l < /tmp/aspell-results/${name}.txt)
  echo "$name: $n nálezů"
}
```

- [ ] **Step 3: Spustit spell check na všech 26 kapitol**

```bash
spell_check() { ... } # definice z kroku 2

for f in content/chapters/*.md; do
  spell_check "$f"
done
```

Expected: výpis `<chapter>: <N> nálezů` pro každou kapitolu.

- [ ] **Step 4: Agregovat globální výsledky**

```bash
cat /tmp/aspell-results/*.txt | sort | uniq -c | sort -rn | head -100 > /tmp/aspell-global.txt
cat /tmp/aspell-global.txt
```

Pohled: nejčastější neznámá slova napříč všemi kapitolami.

- [ ] **Step 5: Rozšířit whitelist o evidentně technické termíny z výsledků**

Projít `/tmp/aspell-global.txt`, najít termíny, které jsou validní (jména, technické pojmy, anglické termíny v kontextu) a přidat do `/tmp/aspell-whitelist.txt`. Spustit znovu Step 3 + 4.

Tento krok opakovat 1-3× dokud `/tmp/aspell-global.txt` neobsahuje hlavně skutečné překlepy / podezřelá slova.

- [ ] **Step 6: Per-kapitola review a opravy**

Pro každou kapitolu s nenulovým počtem nálezů:

```bash
for f in /tmp/aspell-results/*.txt; do
  if [ -s "$f" ]; then
    chapter=$(basename "$f" .txt)
    echo "=== $chapter ==="
    cat "$f"
    echo ""
  fi
done
```

Pro každý nález:
1. Vyhledat v `content/chapters/<chapter>.md` (grep -n)
2. Posoudit kontext: skutečný překlep, jméno, citace, technický termín?
3. Pokud překlep: opravit přes Edit tool
4. Pokud ne: přidat do whitelistu (pro budoucnost) NEBO ignorovat

Sporné nechat na rozhodnutí uživatele – vypsat seznam k review.

- [ ] **Step 7: Verifikace – znovu spustit aspell**

```bash
for f in content/chapters/*.md; do
  spell_check "$f"
done
cat /tmp/aspell-results/*.txt | sort | uniq -c | sort -rn | head -20
```

Expected: nálezy zredukovány, zbývající jsou whitelistované termíny nebo schválené výjimky.

- [ ] **Step 8: Commit**

```bash
git diff content/chapters/*.md | head -200  # poslední kontrola
git add content/chapters/*.md
git commit -m "chore(content): oprava překlepů"
```

Pokud žádné opravy nebyly nutné (bez překlepů), task přeskočit (žádný prázdný commit).

---

## Task 3: Pass C – Diagramy

Voice/jazyk pass na 17 `.puml` souborů + rekompilace SVG.

**Files:**
- Modify: `templates/diagrams/**/*.puml` (17 souborů)
- Modify: `templates/diagrams/**/*.svg` (rekompilované)

### Subagent prompt (šablona)

````
Provedeš voice/jazyk revizi popisků v jednom PlantUML diagramu.

# Kontext
- Projekt: DDD v Symfony, obsahový web v češtině.
- CLAUDE.md sekce „Voice, tón a jazyk" definuje pravidla. PŘEČTI JI PŘED EDITACÍ: /home/michal/Work/ddd-v-symfony/CLAUDE.md
- Soubor: templates/diagrams/<SOUBOR>.puml
- Diagram je PlantUML zdrojový kód, který se kompiluje do SVG embeddingovaného ve stránce.

# Co dělat
1. Přečti CLAUDE.md sekci „Voice, tón a jazyk".
2. Přečti přidělený soubor.
3. Aplikuj opravy textů na CSS pravidla:
   - Voice/tón: zakázané fráze (marketing, hype, výplň), em dash → en pomlčka, anglické uvozovky → české.
   - Délka popisku: stručná, bez výplně.
   - České uvozovky pokud uvozovky vůbec.
4. Zapiš opravy do souboru pomocí Edit toolu.
5. NESÁHNI:
   - PlantUML syntaxi: `@startuml`, `@enduml`, `!include`, `rectangle`, `note`, `as`, `$ACCENT`, šipky `-->`, `-[hidden]-`, atd.
   - Identifikátory uzlů (např. `as core`, `as pricing`)
   - Strukturu uzlů a vztahů
   - Anglické technické termíny (`Bounded Context`, `Core Domain`, `Aggregate Root`) – necháváme stejně jako v textu příručky
6. Editovatelné je jen:
   - `title <text>`
   - Český obsah uvnitř `note ... end note`
   - Český obsah uvnitř závorek `"..."` u rectangle/uzlů – jen tam, kde je to popisek (ne identifikátor v as)
7. NECOMMITUJ. Jen edituj soubor.

# Výstup – jednostránkový report
## Úpravy – N
- Stručný popis úprav per kategorie

## Upozornění (neopravené)
- Co vypadá sporně

## Místa nejistá
- Místa, kde jsem nebyl jistý

## Souhrn
- Celkový počet úprav
````

- [ ] **Step 1: Várka 1 – 5 .puml souborů**

Spusť 5 paralelních subagentů:
- `templates/diagrams/10_ddd_ai/spectrum.puml`
- `templates/diagrams/11_subdomains/core_supporting_generic.puml`
- `templates/diagrams/12_context_mapping/acl_anatomy.puml`
- `templates/diagrams/12_context_mapping/context_map_patterns.puml`
- `templates/diagrams/13_architectural_styles/hexagonal_vs_onion.puml`

- [ ] **Step 2: Projít diff várky 1**

```bash
git diff templates/diagrams/10_ddd_ai templates/diagrams/11_subdomains templates/diagrams/12_context_mapping templates/diagrams/13_architectural_styles
```

Odmítnout změny v PlantUML syntaxi, identifikátorech uzlů.

- [ ] **Step 3: Várka 2 – 5 .puml souborů**

- `templates/diagrams/14_outbox/inbox_idempotency.puml`
- `templates/diagrams/14_outbox/outbox_flow.puml`
- `templates/diagrams/15_case_study/context_map.puml`
- `templates/diagrams/16_lesser_patterns/specification_compose.puml`
- `templates/diagrams/17_event_storming/big_picture_levels.puml`

- [ ] **Step 4: Projít diff várky 2**

```bash
git diff templates/diagrams/14_outbox templates/diagrams/15_case_study templates/diagrams/16_lesser_patterns templates/diagrams/17_event_storming
```

- [ ] **Step 5: Várka 3 – 5 .puml souborů**

- `templates/diagrams/18_team_topologies/conway_inverse.puml`
- `templates/diagrams/19_authorization/policy_layers.puml`
- `templates/diagrams/20_microservices/bc_to_service.puml`
- `templates/diagrams/21_aggregate_design/aggregate_boundary.puml`
- `templates/diagrams/21_aggregate_design/order_states.puml`

- [ ] **Step 6: Projít diff várky 3**

```bash
git diff templates/diagrams/18_team_topologies templates/diagrams/19_authorization templates/diagrams/20_microservices templates/diagrams/21_aggregate_design
```

- [ ] **Step 7: Várka 4 – 2 zbývající**

- `templates/diagrams/21_aggregate_design/transaction_flow.puml`
- `templates/diagrams/3_implementation_in_symfony/boundary.puml`

- [ ] **Step 8: Projít diff várky 4**

```bash
git diff templates/diagrams/21_aggregate_design/transaction_flow.puml templates/diagrams/3_implementation_in_symfony/boundary.puml
```

- [ ] **Step 9: Identifikovat změněné .puml**

```bash
git diff --name-only -- 'templates/diagrams/**/*.puml' > /tmp/changed-puml.txt
cat /tmp/changed-puml.txt
```

- [ ] **Step 10: Rekompilace SVG pro každou změněnou .puml**

```bash
while read puml; do
  if [ -n "$puml" ]; then
    dir=$(dirname "$puml")
    echo "Compiling: $puml"
    plantuml -tsvg "$puml" -o "$(realpath $dir)" 2>&1
  fi
done < /tmp/changed-puml.txt
```

Expected: pro každou .puml vznikne aktualizovaný .svg ve stejné složce.

```bash
git status templates/diagrams
```

Should show modified `.puml` and modified `.svg` (vždy v páru).

- [ ] **Step 11: Vizuální kontrola SVG**

Pro každou změněnou SVG ověřit, že se neporušil layout. Otevřít dev server:

```bash
symfony server:start --no-tls -d
```

Identifikovat, které stránky vykreslují změněné diagramy (mapování diagram → kapitola podle jména složky `<NN>_<topic>`):

```bash
for puml in $(cat /tmp/changed-puml.txt); do
  topic=$(basename $(dirname "$puml"))
  echo "diagram: $puml → kapitola odvozená z: $topic"
done
```

Stáhnout HTML stránky a ověřit přítomnost SVG bez chyb (200 + nepoškozený).

```bash
# Příklad ověření, upravit cesty podle skutečných URL
curl -s http://127.0.0.1:8000/subdomeny | grep -c '<svg'
```

Pokud se layout rozbije:
- Přidat zpět původní SVG (`git restore --source=HEAD -- <svg>`)
- Flagnout problém (rekompilace neproběhla 1:1, možná verze plantuml jiná než původní)
- Pokračovat bez SVG změny pro daný diagram (ponechat .puml změnu, .svg vrátit)

```bash
symfony server:stop
```

- [ ] **Step 12: Commit**

```bash
git add templates/diagrams
git commit -m "chore(diagrams): revize popisků a rekompilace SVG"
```

---

## Task 4: Push větve a otevření PR

- [ ] **Step 1: Ověřit stav větve**

```bash
git log --oneline main..HEAD
git status
```

Expected:
- 4 commity proti main: spec + 3 passy (případně méně, pokud některý pass neměl změny)
- Working tree clean

- [ ] **Step 2: Push větve**

```bash
git push -u origin revize-prirucky-faze-3
```

- [ ] **Step 3: Otevřít PR**

Tělo PR by mělo shrnout výsledky všech tří passů. Šablona:

```bash
gh pr create --title "Fáze 3: hub stránky, glosář, pravopis, diagramy" --body "$(cat <<'EOF'
## Souhrn

Pokračování revize příručky po Fázi 1 (voice/jazyk per kapitola) a Fázi 2 (konzistence). Fáze 3 pokrývá zbývající textové oblasti webu mimo faktickou verifikaci.

Spec: `docs/superpowers/specs/2026-05-03-revize-prirucky-faze-3-design.md`
Plán: `docs/superpowers/plans/2026-05-03-revize-prirucky-faze-3.md`

---

## Pass A – Hub stránky a glosář

[souhrn úprav: počty per kategorie, hlavní vzory, případná upozornění]

## Pass B – Pravopis

[souhrn: počet opravených překlepů, kapitoly s nálezy, false positives přidané do whitelistu]

## Pass C – Diagramy

[souhrn: počet upravených popisků, počet rekompilovaných SVG, vizuální verifikace]

---

## Mimo rozsah (flagnuto pro pozdější kolo)

[seznam upozornění z reportů subagentů – faktické nepřesnosti, HTML/Twig artefakty]
EOF
)"
```

Vyplnit placeholdery `[souhrn ...]` skutečnými údaji z reportů subagentů a Step výstupů.

- [ ] **Step 4: Vrátit URL PR uživateli**

```bash
gh pr view --web 2>&1 | head -1 || gh pr view --json url -q .url
```

Vypsat URL PR, aby uživatel věděl, kam jít na review.

---

## Definition of Done

- [ ] 3 passy commitnuté na větvi `revize-prirucky-faze-3` (spec + 3 contentové commity, případně méně pokud pass neměl změny)
- [ ] Větev pushnuta na origin
- [ ] PR otevřen s vyplněným souhrnem všech passů
- [ ] Žádné necommitnuté změny v `templates/ddd/`, `content/chapters/`, `templates/diagrams/`
- [ ] Všechny vizuální kontroly (curl 200, SVG bez chyb) prošly

---

## Self-Review checklist (pro autora plánu)

- [ ] Pokrývá plán všechny tři passy ze specu? Ano: Task 1 = A, Task 2 = B, Task 3 = C, Task 4 = PR.
- [ ] Žádné placeholdery, TBD, „atd."? Pouze placeholdery v PR body templatu, které se vyplní z reportů.
- [ ] Konzistentní názvy souborů, větví, commit zpráv napříč úkoly? Větev `revize-prirucky-faze-3`, commit prefixy `chore(content):` a `chore(diagrams):`.
- [ ] Bezpečnostní zásady CLAUDE.md (žádný Co-Authored-By Claude, vykání)? Commit zprávy bez Co-Authored-By.
