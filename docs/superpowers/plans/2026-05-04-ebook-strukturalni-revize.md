# Ebook strukturální revize — implementační plán

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Závěrečný polishing struktury knihy „Domain-Driven Design v Symfony 8" — vyřešit 8 bodů strukturální revize identifikovaných v specu.

**Architecture:** Inkrementální úpravy markdown obsahu v `content/chapters/`, doplněné synchronizací s `ebook/build.sh`, `src/Catalog/Chapters.php` a `src/Controller/DddController.php`. Pořadí od nejmenšího rizika k největšímu — strukturální změna (sloučení kapitol 9+10 + přečíslování) až nakonec.

**Tech Stack:** Markdown + YAML frontmatter, PHP (Symfony), Pandoc + Typst (ebook build), Doctrine, Twig.

**Spec:** [`docs/superpowers/specs/2026-05-04-ebook-strukturalni-revize-design.md`](../specs/2026-05-04-ebook-strukturalni-revize-design.md)

---

## Příprava

### Task 0: Ověřit, že build funguje před zahájením

**Files:**
- Read: `ebook/build.sh`

- [ ] **Step 1: Spustit build a ověřit, že funguje.**

```bash
cd /home/michal/Work/ddd-v-symfony && bash ebook/build.sh 2>&1 | tail -20
```

Expected: `✓ EPUB:` a `✓ PDF:` ve výstupu. Soubory `ebook/output/ddd-v-symfony.epub` a `ebook/output/ddd-v-symfony.pdf` existují.

- [ ] **Step 2: Zaznamenat baseline výstupy.**

```bash
ls -la /home/michal/Work/ddd-v-symfony/ebook/output/
```

Expected: oba soubory s rozumnou velikostí (typicky 5–15 MB EPUB, 10–25 MB PDF).

---

## Task 1: Bod 8 — `ddd_ai.md` poznámka o web-only stavu

**Files:**
- Modify: `content/chapters/ddd_ai.md` (frontmatter)

**Cíl:** Soubor je už mimo `CHAPTER_ORDER` v `build.sh` a v `Chapters::extras()` v PHP. Doplnit v něm explicitní frontmatter flag pro budoucí čtenáře, aby bylo jasné, že to není opomenutí.

- [ ] **Step 1: Přidat do frontmatteru `ddd_ai.md` flag `ebook: false`.**

Read file first:
```bash
head -20 content/chapters/ddd_ai.md
```

Add after `category: Reference`:
```yaml
ebook: false
```

- [ ] **Step 2: Ověřit, že build pořád funguje (žádný side effect).**

```bash
bash ebook/build.sh 2>&1 | tail -5
```

Expected: build successful, žádné varování.

- [ ] **Step 3: Commit.**

```bash
git add content/chapters/ddd_ai.md
git commit -m "content(ddd_ai): označit jako web-only ve frontmatteru"
```

---

## Task 2: Bod 2 — Přejmenovat kap. 8 „Méně známé" → „Doplňující"

**Files:**
- Modify: `content/chapters/lesser_known_patterns.md` (frontmatter + úvodní odstavce)
- Modify: `src/Catalog/Chapters.php:25` (label)
- Possibly modify: cross-links v ostatních kapitolách

**Cíl:** Změnit titul z „Méně známé taktické vzory" na „Doplňující taktické vzory" napříč ebookem i webem. Cesta `/mene-zname-vzory` zůstává (URL nikdy neměnit).

- [ ] **Step 1: Najít všechny výskyty „Méně známé" v projektu.**

```bash
grep -rn "Méně známé\|méně známé\|Méně znám" content/ src/ templates/ 2>/dev/null
```

Expected: výskyty v `content/chapters/lesser_known_patterns.md` (frontmatter, úvod, FAQ, závěr) + případně v cross-linkách v ostatních kapitolách.

- [ ] **Step 2: Aktualizovat frontmatter `lesser_known_patterns.md`.**

Změnit:
- `title: 'Méně známé taktické vzory: Specifications, Domain Services, Factories, Modules'` → `title: 'Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules'`
- `page_title: "..."` (analogicky, pokud obsahuje slovo „Méně")
- `breadcrumb_name: Méně známé taktické vzory` → `breadcrumb_name: Doplňující taktické vzory`
- `schema_headline: "Méně známé taktické vzory: Specifications, Domain Services, Factories, Modules"` → `schema_headline: "Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules"`
- `meta_description` zkontrolovat a aktualizovat pokud obsahuje „méně známé"
- `deck:` zkontrolovat — pokud obsahuje formulaci „Vedle entit, value objektů a agregátů obsahuje Evansova kniha čtyři další taktické vzory, které programátoři často přeskočí" — to je v pořádku, nemění se. Ale slovo „přeskočí" → ponechat (popisuje praktickou realitu, ne kuriozitu).

- [ ] **Step 3: Aktualizovat úvodní odstavce a FAQ kapitoly.**

V `content/chapters/lesser_known_patterns.md` projít text a kde se říká „méně známé" v hlavním kontextu (např. „08.01 Proč tyto vzory přehlížíme" — sekce 08.01 „Proč tyto vzory přehlížíme" může zůstat, ale úvodní výrok „čtyři další taktické vzory, které programátoři často přeskočí" lze ponechat). Zkontrolovat, že nikde není „méně známé vzory" jako fráze. Pokud ano, nahradit za „doplňující vzory" nebo obecně popisné formulace.

- [ ] **Step 4: Aktualizovat `src/Catalog/Chapters.php:25`.**

Změnit:
```php
['n' => '08', 'route' => 'lesser_known_patterns',     't' => 'Doplňkové taktické vzory',
```
na:
```php
['n' => '08', 'route' => 'lesser_known_patterns',     't' => 'Doplňující taktické vzory',
```

(Sjednotit s frontmatterem — „Doplňující" místo „Doplňkové".)

- [ ] **Step 5: Aktualizovat cross-linky v ostatních kapitolách.**

```bash
grep -rn "Méně známé vzory\|Méně známé taktické vzory" content/chapters/ --include="*.md" 2>/dev/null
```

Pro každý nalezený link nahradit text odkazu na „Doplňující taktické vzory" (cesta `/mene-zname-vzory` zůstává).

- [ ] **Step 6: Ověřit build.**

```bash
bash ebook/build.sh 2>&1 | tail -10
```

Expected: build successful, kap. 8 v TOC se jmenuje „Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules".

- [ ] **Step 7: Ověřit web (smoke test).**

```bash
php bin/console cache:clear && php bin/console debug:router | grep mene-zname
```

Expected: route `lesser_known_patterns` má cestu `/mene-zname-vzory`.

- [ ] **Step 8: Commit.**

```bash
git add content/chapters/lesser_known_patterns.md src/Catalog/Chapters.php
# Plus jakékoli další modifikované soubory s cross-linky
git commit -m "content(kap-8): přejmenovat 'Méně známé' → 'Doplňující taktické vzory'"
```

---

## Task 3: Bod 4 — Vyladit úvod kap. 12 (Autorizace)

**Files:**
- Modify: `content/chapters/authorization_in_ddd.md` (úvodní odstavce, sekce 12.01)

**Cíl:** Přidat 1–2 odstavce v úvodu kap. 12, které explicitně navazují na kap. 11 (Implementace v Symfony) a předznamenávají kap. 13 (CQRS). Důvod: na první pohled působí, jako by autorizace „skočila" do uprostřed implementační části, ačkoli pozice je obhajitelná.

- [ ] **Step 1: Načíst aktuální úvod kap. 12.**

```bash
sed -n '20,35p' content/chapters/authorization_in_ddd.md
```

Expected: současný úvodní odstavec a začátek sekce 12.01.

- [ ] **Step 2: Přepsat úvodní odstavec (před sekcí 12.01).**

Stávající úvod:
> „Autorizace je v DDD aplikacích dlouhodobě podceněné téma. Většina týmů zvládne autentizaci..."

Nahradit za:
> „V předchozí kapitole jsme implementovali agregáty, repozitáře a Application Services v Symfony 8. Otevřená zůstala otázka, kterou většina projektů řeší ad-hoc: **kdo smí který use case zavolat a za jakých podmínek**. V této kapitole zavedeme čtyřvrstvý rámec, který autorizační rozhodnutí umístí na správnou vrstvu — od HTTP firewallu přes Symfony Voter v aplikační vrstvě až po doménové invarianty v agregátu. V navazující kapitole o CQRS pak ukážeme, jak se autorizace integruje do Command Handleru.
>
> Autorizace je v DDD aplikacích dlouhodobě podceněné téma. Většina týmů zvládne autentizaci (Symfony firewall, JWT, OAuth) bez větších potíží. Jakmile ale přijde otázka *„kdo smí udělat co s konkrétní entitou v konkrétním stavu"*, kód se rozsype napříč controllery, listenery, twig templaty a Doctrine query buildery. Kapitola dává **čtyřvrstvý rámec**, podle kterého poznáte, kam které pravidlo patří a jak ho v Symfony 8 implementovat idiomaticky — bez toho, aby Symfony Security komponenta pronikla do doménového jádra."

- [ ] **Step 3: Aktualizovat křížové odkazy v navazujícím odstavci.**

Stávající:
> „Kapitola navazuje na [DDD Pain Points](/ddd-v-praxi-kde-to-boli), kde jsme autorizaci jen letmo zmínili..."

Nahradit za:
> „Kapitola navazuje na [Implementaci v Symfony](/implementace-v-symfony), která pokrývá Voter API jako jeden z několika Symfony idiomů. Doplňuje praktický pohled k tématům [CQRS](/cqrs) (kde sedí ověření Command Handleru), [Testování](/testovani-ddd) (jak otestovat každou ze 4 vrstev samostatně) a [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli) (kde jsme autorizaci jen letmo zmínili)."

- [ ] **Step 4: Ověřit build.**

```bash
bash ebook/build.sh 2>&1 | tail -5
```

Expected: build successful.

- [ ] **Step 5: Ověřit, že úvod kapitoly dává smysl jako navazující čtení po kap. 11.**

Read first 50 lines:
```bash
sed -n '1,55p' content/chapters/authorization_in_ddd.md
```

Self-check: úvod explicitně zmiňuje předchozí kapitolu (Implementace v Symfony), uvádí, co tato kapitola přidává, a předznamenává návaznost na CQRS. Hlas a tón odpovídá CLAUDE.md (vykání, žádný marketing, krátké věty).

- [ ] **Step 6: Commit.**

```bash
git add content/chapters/authorization_in_ddd.md
git commit -m "content(kap-12): explicitní úvod navazující na kap. 11 a 13"
```

---

## Task 4: Bod 7 — Napsat předmluvu

**Files:**
- Create: `content/chapters/preface.md`
- Modify: `ebook/build.sh` (přidat `preface` na začátek `CHAPTER_ORDER`)
- Modify: `src/Catalog/Chapters.php` (přidat předmluvu jako extras nebo specialní entry — viz step 4)

**Cíl:** Vytvořit kompletní předmluvu (250–400 řádků) podle struktury v specu. Cesta v ebooku jako první kapitola, na webu jako specialní stránka (podobně jako glosář).

- [ ] **Step 1: Vytvořit kostru `preface.md` s frontmatterem.**

Create file with frontmatter:
```yaml
---
route: preface
path: /predmluva
title: Předmluva
page_title: "Předmluva | DDD Symfony"
meta_description: "Jak číst tuto knihu o Domain-Driven Design v Symfony 8 — pro koho je, co pokrývá, doporučené čtecí cesty podle role."
meta_keywords: "předmluva, DDD, Symfony, jak číst, doporučená cesta čtení"
og_type: article
published: "2026-05-04"
modified: "2026-05-04"
breadcrumb_name: Předmluva
schema_type: TechArticle
schema_headline: "Předmluva: Domain-Driven Design v Symfony 8"
chapter_number: "00"
category: Úvod
deck: "Co je tato kniha, pro koho je, jak je strukturovaná a jak ji číst podle role čtenáře."
reading_time: 8
difficulty: 1
---
```

- [ ] **Step 2: Napsat sekci „Pro koho je tato kniha".**

Add after frontmatter (cca 50–80 řádků):

Struktura:
- Úvodní odstavec: kniha předpokládá zkušenost s PHP a Symfony, OOP a základními designovými vzory (DI, Repository, MVC). Nepředpokládá zkušenost s DDD.
- Konkrétní cílové role: senior PHP developer / Symfony developer / architekt / tech lead / vývojář migrující z CRUD aplikace na DDD.
- Co kniha NENÍ: ne úvod do PHP, ne úvod do Symfony, ne kuchařka „kopíruj-vlož".
- Předpokládané výchozí znalosti: jak vypadá Symfony controller, co je Doctrine entity, jak funguje DI container, co je interface a kompozice.

Voice: vykání, krátké věty, žádný marketing. Žádné „Tato kniha vás posune…".

- [ ] **Step 3: Napsat sekci „Co kniha pokrývá".**

Add after previous section (cca 60–100 řádků):

Struktura:
- Krátké rozdělení do 8 částí (po sloučení 9+10):
  - **Strategický design** (kap. 1–5): co je DDD, subdomény, Bounded Context, Event Storming, Team Topologies.
  - **Taktický design** (kap. 6–9): základní koncepty, agregát, doplňující vzory, architektonické styly.
  - **Implementace v Symfony** (kap. 10–11): konkrétní mapování DDD do Symfony, autorizace.
  - **Pokročilé vzory** (kap. 12–15): CQRS, Event Sourcing, ságy, Outbox.
  - **Výkon a testování** (kap. 16–17): průřezové otázky.
  - **Migrace a microservices** (kap. 18–19): postupný přechod, fyzické hranice.
  - **Provozní problémy a anti-vzory** (kap. 20–22): kde to bolí, kódové anti-vzory, kdy DDD nepoužívat.
  - **Praktické příklady** (kap. 23–24): mini-projekty a velká case study.
- Pro každou část jeden krátký odstavec s motivací.

> **Pozn.:** V této chvíli ještě platí staré číslování (kap 10 ještě existuje, kap 11–25). Přečíslování proběhne v Tasku 8. V předmluvě tedy zatím dočasně použít stará čísla a po Tasku 8 je opravit.

- [ ] **Step 4: Napsat sekci „Jak číst tuto knihu" — doporučené cesty podle role.**

Add (cca 80–120 řádků):

Pro každou roli vytvořit „cestu":

**Junior PHP dev** — sekvence kapitol s krátkým komentářem proč:
- Kap. 1: pochopit, co DDD vůbec je.
- Kap. 6: základní koncepty (entity, VO, agregáty) — nejdůležitější mentální model.
- Kap. 7: návrh agregátu — jak ho udělat dobře.
- Kap. 11 (po přečíslování 10): implementace v Symfony — konkrétní kód.
- Kap. 18 (po přečíslování 17): testování — jak ověřit, že to funguje.
- Volitelné: kap. 13 (CQRS) až po měsíci praxe.

**Senior PHP dev** — lineárně 1–25 (resp. 1–24 po přečíslování). Ale pokud chce přeskočit, doporučit pořadí strategických (1–5) → taktických (6–9) → pokročilých (12–15) podle tématu projektu.

**Architekt / tech lead** — strategické kapitoly + provozní:
- Kap. 1–5: strategie a Team Topologies.
- Kap. 19 (18): Migrace z CRUD.
- Kap. 20 (19): Microservices a DDD.
- Kap. 22 (21): Anti-vzory.
- Kap. 23 (22): Kdy DDD nepoužívat.
- Kap. 25 (24): Case study pro inspiraci.

**Vývojář migrující z CRUD** — selektivní cesta:
- Kap. 1: rozhodnutí, jestli vůbec DDD.
- Kap. 23 (22): kdy NE.
- Kap. 6, 7: klíčové koncepty.
- Kap. 19 (18): Strangler Fig Pattern, postup migrace.
- Pak selektivně podle bolesti, kterou v současné aplikaci pociťuje.

**Konzultant / školitel** — kompletní knihu, ale s důrazem na:
- Kap. 4: Event Storming jako workshopová technika.
- Kap. 5: Team Topologies.
- Kap. 23 (22): kdy DDD nedoporučit klientovi.

Format: pro každou roli krátký odstavec úvodu (1–2 věty) + bullet list s odkazy na kapitoly (cesty, ne čísla — `/co-je-ddd`, `/zakladni-koncepty` atd. — aby přečíslování v Tasku 8 nezneplatnilo plán).

- [ ] **Step 5: Napsat sekci „Konvence v knize".**

Add (cca 30–50 řádků):

Krátký popis konvencí:
- **Voice a tón:** vykání, krátké věty, žádný marketing.
- **Code style:** PHP 8.4, Symfony 8, Doctrine ORM 3, modern attribute-based DI.
- **Callouty:** `note` (informace navíc), `warn` (riziko), `pattern` (doporučený vzor), `anti` (anti-vzor).
- **Diagramy:** PlantUML zdroj v `templates/diagrams/`, vyrenderované SVG embedded.
- **Cross-linky:** vždy na cesty (`/co-je-ddd`), ne na čísla kapitol.
- **Pozn. o čerpání:** odkazy na kanonické zdroje (Evans, Vernon, Khononov, Newman, Fowler, …).

- [ ] **Step 6: Volitelná sekce „Poděkování" — vynechat, pokud autor nechce.**

V této verzi vynechat (autor doplní později podle potřeby).

- [ ] **Step 7: Self-review nového obsahu proti CLAUDE.md voice rules.**

Read full preface:
```bash
cat content/chapters/preface.md
```

Checklist (z CLAUDE.md):
- [ ] Žádný em-dash (—) — místo toho en-dash (–) s mezerami.
- [ ] Žádné anglické uvozovky "" — vždy české „".
- [ ] Vykání, ne tykání.
- [ ] „Zde", ne „Tady".
- [ ] „Průvodce" / „kniha", ne „kurz", „tutoriál".
- [ ] Žádné osobní komentáře autora („z mé zkušenosti…").
- [ ] Žádné marketingové fráze (mocný, výkonný, revoluční, jednoduše, snadno, rychle…).
- [ ] Žádné výplňové fráze („je důležité si uvědomit…", „hraje klíčovou roli…", „v rámci…").

Pokud něco najdete, upravit.

- [ ] **Step 8: Přidat předmluvu na začátek `CHAPTER_ORDER` v `ebook/build.sh`.**

Modify `ebook/build.sh:23-49`. Přidat `preface` jako první položku:

```bash
CHAPTER_ORDER=(
  preface
  what_is_ddd
  subdomains
  ...
)
```

- [ ] **Step 9: Přidat předmluvu do `src/Catalog/Chapters.php`.**

Otázka: kam přesně? Možnosti:
- (a) Jako specialní entry v `extras()` (vedle glosáře, cheat sheetu, atd.). To je správné, pokud je předmluva spíše referenční.
- (b) Jako součást `all()` s `n: '00'` před kap. 01. To je správné, pokud chcete, aby se zobrazovala v hlavním TOC ebooku/webu.

Doporučeno (b) pro ebook konzistenci. Modify `src/Catalog/Chapters.php:14-16` — přidat na začátek pole:

```php
['n' => '00', 'route' => 'preface', 't' => 'Předmluva', 'd' => 'Pro koho je kniha, co pokrývá, jak číst', 'time' => 8, 'lvl' => 1, 'tag' => 'Úvod', 'group' => 'preface'],
```

Pozn.: Bude potřeba nový group `preface` nebo zařadit do `basics`. Doporučuji `preface` jako samostatnou skupinu (jen 1 položka, ale jasně oddělená od kap. 1).

Alternativně: ponechat předmluvu jako web-only a v ebooku ji zařadit jen přes `CHAPTER_ORDER`. To by znamenalo nedat ji do `Chapters::all()` a místo toho jen jako entry v `extras()` s tag „Úvod". Méně konzistentní, ale jednodušší.

Volba: doporučuji **(b)** s vlastní skupinou `preface`, ale pokud autor chce minimální dopad na web, použít `extras()`. Otázku rozhodnout interaktivně.

- [ ] **Step 10: Pokud (b), přidat hub route v `DddController.php` a template.**

Pokud byla zvolena (b) s vlastní skupinou — předmluva je samostatná stránka, takže pravděpodobně není potřeba hub. Stačí, aby `Chapters::all()` ji obsahovala — `ChapterRouteLoader` ji automaticky nasměruje na route podle frontmatter `path: /predmluva`.

Verify route:
```bash
php bin/console cache:clear && php bin/console debug:router | grep predmluva
```

Expected: route existuje a směruje na markdown soubor.

- [ ] **Step 11: Ověřit build.**

```bash
bash ebook/build.sh 2>&1 | tail -10
```

Expected:
- Build successful.
- V output PDF/EPUB je předmluva první kapitolou (před „01. Co je DDD").
- Žádné varování typu „Chybí: preface.md".

- [ ] **Step 12: Commit.**

```bash
git add content/chapters/preface.md ebook/build.sh src/Catalog/Chapters.php
git commit -m "content(preface): přidat předmluvu — pro koho, struktura, cesty čtení"
```

---

## Task 5: Bod 5 — Rozšířit kap. 1 z 181 na 400–500 řádků

**Files:**
- Modify: `content/chapters/what_is_ddd.md`

**Cíl:** Současná kapitola je suchá teoretická. Přidat motivační příběh na začátek (~80–120 řádků) a sekci „Jak číst tuto knihu" na konec (~100 řádků). Současné sekce 01.01–01.10 zachovat, jen drobně rozšířit o konkrétní příklady kde dává smysl.

> **Pozn.:** Sekce „Jak číst tuto knihu" v předmluvě (Task 4) a v kap. 1 budou částečně překrývat. Kap. 1 by měla mít kratší verzi „Jak číst NÁSLEDUJÍCÍ kapitoly" (orientovaná spíš na obsah jednotlivých částí knihy), zatímco předmluva má detailní cesty podle role. Pokud autor preferuje jeden zdroj pravdy, je možné v kap. 1 jen odkázat na předmluvu. Doporučení: v kap. 1 stručná navigace v rámci knihy (1 odstavec), v předmluvě plný detail.

- [ ] **Step 1: Přidat motivační příběh na začátek kap. 1, před sekci 01.01.**

Stávající začátek je:
```markdown
## 01.01 Definice DDD {#definition}
```

Vložit před to (mezi frontmatter a sekci 01.01):

Strukturalní příběh (cca 80–120 řádků). Doporučená struktura:

**Úvod do příběhu** (cca 20 řádků):
- Konkrétní e-shop, který začínal jednoduchý: 3 stavy objednávky (`new`, `paid`, `shipped`), 1 typ zákazníka, 1 platební metoda.
- Po roce: 12 stavů (přidaly se `cancelled`, `refunded`, `partially_paid`, `disputed`, `held_for_review`…), 4 typy zákazníka (B2C, B2B, dealer, partner), 5 platebních metod.
- Tým má 5 lidí, kód má 80 000 řádků, přidání nové platební metody trvá 3 týdny a vždy něco rozbije.

**Symptomy růstu komplexity** (cca 30 řádků):
- `OrderService` má 47 metod a 1200 řádků. Nikdo neví, jakou kombinaci přechodů stavů systém podporuje.
- Změna v jednom místě způsobí regresi v jiném (cancellation logika rozbije refund).
- Onboarding nového vývojáře trvá 2 měsíce, než začne dělat smysluplné PR.
- Ředitel se ptá: „proč nedokážeme přidat novou platební metodu rychleji?"

**Co DDD slibuje** (cca 30 řádků):
- Místo `OrderService` doménový model `Order` s explicitními stavovými přechody (metoda `confirm()` ne `setStatus('confirmed')`).
- Místo „4 typy zákazníka" v jednom modelu — 4 různé Bounded Contexts, kde každý má svého `Customer` s vlastními atributy.
- Místo dlouhého trvání feature work — explicitní hranice, které drží refaktoring v rozumných mezích.
- Hlavní přínos: kód odráží jazyk doménových expertů. Když produkťák řekne „tohle není vrácený nárok, je to reklamace s odlišným procesem" — kód to umí říct stejně.

**Cena, kterou DDD má** (cca 20 řádků):
- DDD není zadarmo: vyšší počáteční složitost, učební křivka, nutnost mluvit s doménovými experty.
- Pro CRUD aplikaci nad jednou tabulkou se DDD nevyplatí.
- Pro komplexní doménu s rostoucí pravidlovou složitostí se vrátí v horizontu 6–12 měsíců.

**Co se naučíte v této knize** (cca 10–20 řádků):
- Jak rozhodnout, jestli DDD ve vašem projektu dává smysl (kap. 23 — pokud po přečíslování, použijte cestu `/kdy-nepouzivat-ddd`).
- Jak modelovat doménu, identifikovat agregáty, oddělit zápis od čtení.
- Jak to konkrétně implementovat v Symfony 8 — bez teoretických květů, s funkčním kódem.

Voice: vykání, krátké věty, konkrétní čísla. Žádný marketing.

- [ ] **Step 2: Drobně rozšířit existující sekce 01.01–01.08 o konkrétní příklady.**

V každé sekci ověřit, že obsahuje alespoň jeden konkrétní příklad (e-shop, banka, pojišťovnictví, …). Pokud chybí, přidat 2–4 řádkový příklad.

Hlavně:
- Sekce 01.03 (Strategický design): příklad Bounded Contexts v reálné e-shop doméně (Catalog, Ordering, Billing).
- Sekce 01.04 (Taktický design): konkrétní třída `Order` s metodami `place()`, `confirm()`, `cancel()`.
- Sekce 01.06 (Výhody): místo abstraktních „flexibilita" — konkrétní příklad ze startu kapitoly (přidání platební metody za týden, ne za měsíc).
- Sekce 01.07 (Výzvy): konkrétní zkušenost, kdy DDD selhalo (např. tým bez doménového experta).

Cíl: zvednout délku kapitoly o cca 100 řádků celkem z těchto rozšíření.

- [ ] **Step 3: Přidat na konec kapitoly novou sekci „01.11 Jak číst tuto knihu".**

Přidat za sekci 01.10 (Další četba):

Krátká navigace (cca 50–80 řádků). Struktura:
- Odkaz na předmluvu pro detailní cesty podle role.
- Stručný přehled částí knihy (3–4 věty každá): co najdete v částech 1 (strategie), 2 (taktika), 3 (implementace), 4 (vzory), 5 (provoz).
- Doporučení pro lineární čtení vs. selektivní čtení.
- Odkaz na cheat sheet (`/cheat-sheet`) pro rychlou orientaci.

- [ ] **Step 4: Self-review proti CLAUDE.md voice rules.**

Stejný checklist jako v Tasku 4 step 7. Projít celou kapitolu po úpravě:
```bash
wc -l content/chapters/what_is_ddd.md
```

Expected: cca 400–500 řádků (oproti původním 181).

- [ ] **Step 5: Ověřit build.**

```bash
bash ebook/build.sh 2>&1 | tail -5
```

Expected: build successful, kap. 1 v PDF má cca 8–12 stránek (oproti původním 4–5).

- [ ] **Step 6: Commit.**

```bash
git add content/chapters/what_is_ddd.md
git commit -m "content(kap-1): rozšířit o motivační příběh a navigační sekci"
```

---

## Task 6: Bod 3 — Vyčistit duplicitu mezi kap. 21, 22, 23

**Files:**
- Modify: `content/chapters/ddd_pain_points.md` (zostřit zaměření na provozní problémy)
- Modify: `content/chapters/anti_patterns.md` (zostřit zaměření na kódové anti-vzory)
- Modify: `content/chapters/when_not_to_use_ddd.md` (ověřit, že je to čistý rozhodovací rámec)
- Optionally modify: navigační poznámky v úvodu každé kapitoly

**Cíl:** Tři kapitoly mají každá jiný úhel, ale obsahují duplicitu (anémický model v 22.02 i v 21.D, pseudo-DDD v obou).
Vyčistit, ne sloučit.

- [ ] **Step 1: Identifikovat duplicity mezi kap. 21 a 22.**

```bash
grep -n "anemický\|anemic\|anémick" content/chapters/ddd_pain_points.md content/chapters/anti_patterns.md
```

Expected: výskyty v obou kapitolách. Stejně pro:
```bash
grep -n "primitive obsession\|Primitive Obsession" content/chapters/ddd_pain_points.md content/chapters/anti_patterns.md
grep -n "god aggregate\|God Aggregate\|příliš velký agregát" content/chapters/ddd_pain_points.md content/chapters/anti_patterns.md
grep -n "pseudo-DDD\|cargo cult" content/chapters/ddd_pain_points.md content/chapters/anti_patterns.md content/chapters/when_not_to_use_ddd.md
```

- [ ] **Step 2: Vyhodnotit každou nalezenou duplicitu.**

Pro každý duplicitní pojem rozhodnout:
- **Vlastník:** kterou kapitole pojem patří jako primární (typicky 22 pro kódové anti-vzory).
- **V druhé kapitole:** zkrátit na max. 2–3 řádky a odkázat na primární kapitolu.

Konkrétně:
- **Anémický model:** primárně v 22.02. V 21.D (Form vs. Command) případně zmínit jen jako důsledek špatně navrženého modelu, s odkazem na 22.02.
- **Primitive Obsession:** primárně v 22.03. V 21 vůbec není (zkontrolovat).
- **God Aggregate:** primárně v 22.04. V kap. 7 (Aggregate Design) je „God Aggregate" jako anti-vzor — to je ok, je to varování v kontextu návrhu agregátu. V 21 by neměl být.
- **Pseudo-DDD / cargo cult:** primárně v 23 (Kdy DDD nepoužívat → Pseudo-DDD warning) a v 22 (jako anti-vzor). Rozhodnout: nechat v 23 jako warning, v 22 jako kódový vzor s odlišným úhlem (rozšíření o konkrétní symptomy v kódu).

- [ ] **Step 3: Zostřit zaměření kap. 21 (DDD pain points).**

Projít všech 5 sekcí (A Doctrine, B Async, C Modelování, D Symfony, E Tým) a:
- V sekci C (Modelování) — odebrat zmínky anémického modelu jako anti-vzoru. Místo toho zaměřit na *praktická třenice s modelováním v Symfony* (např. „kde žije validace mezi Symfony Validator a doménovou validací" — to už tam je, jen ověřit, že je to o praktickém problému, ne o teorii anti-vzoru).
- V sekci D (Symfony-specifická třenice) — zaměřit na praktické problémy: Form vs. Command, API Platform, Voter coupling. Žádné teoretické anti-vzory.
- Aktualizovat úvod kapitoly:
  > „Tato kapitola je **katalog 20 reálných praktických problémů**, se kterými se setkávají týmy při implementaci DDD v PHP a Symfony. Pro úhel kódových anti-vzorů (anémický model, primitive obsession, god aggregate) viz následující kapitolu [Anti-vzory](/anti-vzory). Pro rozhodovací rámec, jestli DDD vůbec použít, viz [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd)."

- [ ] **Step 4: Zostřit zaměření kap. 22 (Anti-vzory).**

Projít existující sekce:
- 22.01 Úvodem
- 22.02 Anémický doménový model
- 22.03 Primitive Obsession
- 22.04 Příliš velký agregát (God Aggregate)
- 22.05 Sdílená databáze napříč BC
- 22.06 Mutovatelné doménové události
- 22.07 Doménová logika v infrastrukturní vrstvě
- 22.08 Over-engineering u jednoduchých aplikací
- 22.09 Ignorování Ubiquitous Language

Akce:
- Z 22.08 (Over-engineering) zvážit přesun do kap. 23 (Kdy DDD nepoužívat) — přesah mezi „nezavádět DDD plošně" je tam silný. Ale pokud zůstane v 22, doplnit cross-link na 23.
- Aktualizovat úvod kapitoly:
  > „Tato kapitola je **katalog kódových a modelovacích anti-vzorů** v DDD. Pro praktické infrastrukturní problémy (Doctrine, Messenger, ACL k externím API) viz [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli). Pro rozhodovací rámec, jestli DDD vůbec použít, viz [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd)."

- [ ] **Step 5: Ověřit kap. 23 — zachovat jako rozhodovací rámec.**

Read kap. 23:
```bash
head -100 content/chapters/when_not_to_use_ddd.md
```

Self-check:
- Kapitola začíná rozhodovacím stromem? Ano.
- Pokrývá 7 situací, kdy DDD nepoužít. Ano.
- Obsahuje sekci „Hybrid podle typu subdomény". Ano.
- Obsahuje sekci „Pseudo-DDD warning"? Ano (v 23.09).
- Aktualizovat úvod kapitoly:
  > „Tato kapitola je **rozhodovací rámec**: kdy DDD nasadit a kdy ne. Pro detailní katalog anti-vzorů, kdy už DDD nasadíte, ale uděláte chyby, viz [Anti-vzory](/anti-vzory). Pro praktická provozní třenice, viz [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli)."

- [ ] **Step 6: Self-review proti CLAUDE.md voice rules.**

Pro každou ze tří kapitol:
```bash
grep -n "Méně známé\|méně známé\|tady\|cizí slovo" content/chapters/{ddd_pain_points,anti_patterns,when_not_to_use_ddd}.md
```

Drobné jazykové opravy.

- [ ] **Step 7: Ověřit build.**

```bash
bash ebook/build.sh 2>&1 | tail -5
```

Expected: build successful.

- [ ] **Step 8: Commit.**

```bash
git add content/chapters/ddd_pain_points.md content/chapters/anti_patterns.md content/chapters/when_not_to_use_ddd.md
git commit -m "content(21-22-23): vyčistit duplicitu, zostřit zaměření, navigační poznámky"
```

---

## Task 7: Bod 6 — Zkrátit kap. 24 (Praktické příklady) z 917 na ~300 řádků

**Files:**
- Modify: `content/chapters/practical_examples.md`

**Cíl:** Zachovat strukturu kap. 24 jako shrnující průžez, odstranit duplikovaný kód, který je v dřívějších kapitolách. Cílit na ~300 řádků (-67 % oproti současnému stavu).

> **Pozn.:** Po Tasku 8 (přečíslování) se kap. 24 stane kap. 23. V tomto Tasku ještě editujeme `practical_examples.md` v kontextu starého číslování (24).

- [ ] **Step 1: Identifikovat sekce v kap. 24.**

```bash
grep "^## " content/chapters/practical_examples.md
```

Expected:
- 24.01 Příklad: E-commerce aplikace
- 24.02 Příklad: Blog
- 24.03 Příklad: Správa uživatelů

- [ ] **Step 2: Pro každý ze 3 příkladů ponechat redukovanou strukturu.**

Pro každý příklad (e-shop, blog, users) zachovat:
- **Krátký úvod** (5–10 řádků): kontext, doménu, hlavní agregáty.
- **Strukturu projektu** (`src/` tree, 20–40 řádků): jak je projekt rozčleněn.
- **1–2 klíčové třídy s odkazem na detail.** Místo plného kódu agregátu (např. `Order` se 100 řádky) jen 5–10 řádkový skeleton:

```php
final class Order extends AggregateRoot
{
    private function __construct(...) { /* ... */ }

    public static function place(...): self { /* invariant: items not empty */ }

    public function confirm(): void { /* invariant: status == draft */ }

    // Plná implementace v kap. 7 (Aggregate Design): /navrh-agregatu
}
```

- **Závěrečný odstavec** (3–5 řádků): „Kompletní implementaci včetně Doctrine mapování a testů popisuje [Případová studie](/pripadova-studie) — minulou kapitolu této knihy." + odkaz na konkrétní předchozí kapitoly (Aggregate, CQRS, Implementace).

> **Pozn.:** Pokud existuje veřejný GitHub repozitář s ukázkovým kódem (frontmatter většiny kapitol obsahuje `github_examples: ChapterXX_Name` — naznačuje plánovanou nebo existující strukturu), lze ho v závěrečném odstavci připojit jako externí odkaz. Pokud neexistuje, nezmiňovat — žádný falešný odkaz.

Cíl pro každý příklad: cca 80–100 řádků (ne 300).

- [ ] **Step 3: Smazat duplikovaný kód.**

V současné kap. 24 jsou plné výpisy `Cart`, `Order`, `User` agregátů — tyto výpisy jsou v kap. 7 (Aggregate), kap. 11 (Implementace) a kap. 13 (CQRS). Smazat plné výpisy a nahradit zkráceným skeletonem (viz step 2).

Konkrétně:
- 24.01 (E-commerce): Cart agregát na řádcích cca 100–230 — zkrátit na 20 řádků skeleton + odkaz na kap. 7.
- 24.01: Cart command/handler na řádcích cca 230–360 — zkrátit na 10 řádků struct + odkaz na kap. 13 (CQRS).
- 24.02 (Blog): podobně.
- 24.03 (Users): podobně.

- [ ] **Step 4: Aktualizovat závěrečný odstavec kap. 24.**

Přidat větu typu:
> „Pro hluboký ponor do reálného projektu pokračujte v navazující [Případové studii](/pripadova-studie), která pokrývá systém pro správu projektů včetně doménové analýzy, architektury, implementace a kompletních read modelů s reconciliation."

- [ ] **Step 5: Self-review.**

```bash
wc -l content/chapters/practical_examples.md
```

Expected: cca 280–350 řádků.

```bash
grep -c "^## " content/chapters/practical_examples.md
```

Expected: 3 hlavní sekce (24.01, 24.02, 24.03) zůstaly.

- [ ] **Step 6: Ověřit, že žádná jiná kapitola neodkazuje na konkrétní pasáže v kap. 24, které byly smazány.**

```bash
grep -rn "prakticke-priklady" content/chapters/ --include="*.md"
```

Pro každý nalezený odkaz: ověřit, že odkazuje obecně na kap. 24 (nebo na sekci, která pořád existuje), ne na pasáž, která byla smazána. Pokud problematický odkaz, opravit.

- [ ] **Step 7: Ověřit build.**

```bash
bash ebook/build.sh 2>&1 | tail -5
```

Expected: build successful, kap. 24 v PDF výrazně kratší než dříve.

- [ ] **Step 8: Commit.**

```bash
git add content/chapters/practical_examples.md
git commit -m "content(kap-24): zkrátit z 917 na ~300 řádků, odkazovat detail na předchozí kapitoly"
```

---

## Task 8: Bod 1 — Sloučit kap. 9 a 10 + přečíslovat 11–25 → 10–24

**Files:**
- Modify: `content/chapters/architectural_styles.md` (rozšířit sekci 09.06 + následně přečíslovat na 09)
- Delete: `content/chapters/horizontal_vs_vertical.md`
- Modify: `ebook/build.sh` (odebrat `horizontal_vs_vertical` z `CHAPTER_ORDER`)
- Modify: `src/Catalog/Chapters.php` (odebrat řádek pro `horizontal_vs_vertical`, přečíslovat 11–25 → 10–24)
- Modify: `src/Controller/DddController.php` (přesměrovat redirect `/horizontalni-vs-vertikalni`)
- Modify: 14 souborů `content/chapters/*.md` (přečíslovat `chapter_number` v frontmatteru a všechny `## NN.MM` sekce)
- Update cross-links v textu

**Cíl:** Sloučit 9. a 10. kapitolu, smazat duplikátní obsah, přečíslovat zbylých 14 kapitol z 11–25 na 10–24.

> **Tato úloha je největší změna v plánu.** Provádějí se mechanické úpravy
> stovek řádků metadat. Důležité je:
> 1. Pečlivě testovat build po každém kroku.
> 2. Použít automatizované rename, ne ruční.
> 3. Slugy souborů zachovat — měníme jen `chapter_number` a sekce.

- [ ] **Step 1: Sloučit obsah `horizontal_vs_vertical.md` do `architectural_styles.md` sekce 09.06.**

Read `horizontal_vs_vertical.md`:
```bash
cat content/chapters/horizontal_vs_vertical.md
```

V `architectural_styles.md` najít sekci 09.06 Vertical Slice Architecture (existující, krátká). Rozšířit ji o:
- Definici „horizontal vs. vertical" jako rozhodovací osy (z kap. 10.01 a 10.02).
- Kdy zvolit který přístup (z kap. 10.04).
- Praktickou Symfony strukturu pro Vertical Slice (z kap. 10.05).
- Tabulkové srovnání (z kap. 10.03).

Cíl: rozšířit sekci 09.06 z cca 50 řádků na 200–250 řádků. Po přečíslování v kroku 6 se sekce stane 09.06 (číslo se nemění, kapitola má pořadí 09 i po přečíslování).

- [ ] **Step 2: Smazat soubor `horizontal_vs_vertical.md`.**

```bash
git rm content/chapters/horizontal_vs_vertical.md
```

- [ ] **Step 3: Odebrat `horizontal_vs_vertical` z `CHAPTER_ORDER` v `ebook/build.sh:33`.**

Modify `ebook/build.sh:33`. Smazat řádek `  horizontal_vs_vertical`.

- [ ] **Step 4: Odebrat řádek z `src/Catalog/Chapters.php:29`.**

Smazat řádek:
```php
['n' => '10', 'route' => 'horizontal_vs_vertical', ...]
```

- [ ] **Step 5: Aktualizovat redirect v `src/Controller/DddController.php`.**

Najít:
```php
#[Route('/horizontalni-vs-vertikalni', name: 'horizontal_vs_vertical_redirect')]
public function horizontalVsVerticalRedirect(): Response
{
    return $this->redirectToRoute('horizontal_vs_vertical', [], 301);
}
```

A přidat nový redirect pro starou cestu `/vertikalni-slice` (ta zaniká s mazáním souboru):
```php
#[Route('/vertikalni-slice', name: 'vertical_slice_redirect')]
public function verticalSliceRedirect(): Response
{
    return $this->redirectToRoute('architectural_styles', [], 301);
}

#[Route('/horizontalni-vs-vertikalni', name: 'horizontal_vs_vertical_redirect')]
public function horizontalVsVerticalRedirect(): Response
{
    return $this->redirectToRoute('architectural_styles', [], 301);
}
```

(Druhá redirect cesta původně mířila na `horizontal_vs_vertical` route — teď tam neexistuje, takže místo toho přesměrovat na `architectural_styles`.)

- [ ] **Step 6: Přečíslovat `chapter_number` v frontmatteru 14 následujících souborů.**

Mapování:
- `implementation_in_symfony.md`: 11 → 10
- `authorization_in_ddd.md`: 12 → 11
- `cqrs.md`: 13 → 12
- `event_sourcing.md`: 14 → 13
- `sagas.md`: 15 → 14
- `outbox_pattern.md`: 16 → 15
- `performance_aspects.md`: 17 → 16
- `testing_ddd.md`: 18 → 17
- `migration_from_crud.md`: 19 → 18
- `microservices_and_ddd.md`: 20 → 19
- `ddd_pain_points.md`: 21 → 20
- `anti_patterns.md`: 22 → 21
- `when_not_to_use_ddd.md`: 23 → 22
- `practical_examples.md`: 24 → 23
- `case_study.md`: 25 → 24

Pro každý soubor:
```bash
sed -i 's/^chapter_number: "11"/chapter_number: "10"/' content/chapters/implementation_in_symfony.md
sed -i 's/^chapter_number: "12"/chapter_number: "11"/' content/chapters/authorization_in_ddd.md
# ... atd. pro všech 14 souborů
```

Verify:
```bash
grep -E "^chapter_number:" content/chapters/*.md | sort
```

Expected: chapter_number jdou postupně od 00 (preface) přes 01–24, plus „ai" (ddd_ai).

- [ ] **Step 7: Přečíslovat `## NN.MM` sekce ve 14 souborech.**

Pro každou sekci nahradit prefix sekce. Protože regex je netriviální (musí matchovat začátky řádků), použít sed:

```bash
# implementation_in_symfony.md: ## 11.01 → ## 10.01, ## 11.02 → ## 10.02, atd.
sed -i -E 's/^(## )11\.([0-9]+)/\110.\2/' content/chapters/implementation_in_symfony.md

# authorization_in_ddd.md: ## 12.01 → ## 11.01
sed -i -E 's/^(## )12\.([0-9]+)/\111.\2/' content/chapters/authorization_in_ddd.md

# cqrs.md: ## 13.01 → ## 12.01
sed -i -E 's/^(## )13\.([0-9]+)/\112.\2/' content/chapters/cqrs.md

# event_sourcing.md: ## 14.01 → ## 13.01
sed -i -E 's/^(## )14\.([0-9]+)/\113.\2/' content/chapters/event_sourcing.md

# sagas.md: ## 15.01 → ## 14.01
sed -i -E 's/^(## )15\.([0-9]+)/\114.\2/' content/chapters/sagas.md

# outbox_pattern.md: ## 16.01 → ## 15.01
sed -i -E 's/^(## )16\.([0-9]+)/\115.\2/' content/chapters/outbox_pattern.md

# performance_aspects.md: ## 17.01 → ## 16.01
sed -i -E 's/^(## )17\.([0-9]+)/\116.\2/' content/chapters/performance_aspects.md

# testing_ddd.md: ## 18.01 → ## 17.01
sed -i -E 's/^(## )18\.([0-9]+)/\117.\2/' content/chapters/testing_ddd.md

# migration_from_crud.md: ## 19.01 → ## 18.01
sed -i -E 's/^(## )19\.([0-9]+)/\118.\2/' content/chapters/migration_from_crud.md

# microservices_and_ddd.md: ## 20.01 → ## 19.01
sed -i -E 's/^(## )20\.([0-9]+)/\119.\2/' content/chapters/microservices_and_ddd.md

# ddd_pain_points.md: ## 21.01 → ## 20.01
sed -i -E 's/^(## )21\.([0-9]+)/\120.\2/' content/chapters/ddd_pain_points.md

# anti_patterns.md: ## 22.01 → ## 21.01
sed -i -E 's/^(## )22\.([0-9]+)/\121.\2/' content/chapters/anti_patterns.md

# when_not_to_use_ddd.md: ## 23.01 → ## 22.01
sed -i -E 's/^(## )23\.([0-9]+)/\122.\2/' content/chapters/when_not_to_use_ddd.md

# practical_examples.md: ## 24.01 → ## 23.01
sed -i -E 's/^(## )24\.([0-9]+)/\123.\2/' content/chapters/practical_examples.md

# case_study.md: ## 25.01 → ## 24.01
sed -i -E 's/^(## )25\.([0-9]+)/\124.\2/' content/chapters/case_study.md
```

> **Pozn.:** Některé sekce mají suffix typu `## 24.10b` (kap. 4 má `## 04.10b`). Regex `[0-9]+` to zachytí, ale následný text (např. `b Most z workshopu`) zůstane. To je správně.

Verify:
```bash
grep -E "^## (1[1-9]|2[0-5])\." content/chapters/*.md | head -20
```

Expected: žádné výskyty starých čísel `## 11.`–`## 25.` (kromě případně podčíslovaných sekcí, které jsme nezachytili).

- [ ] **Step 8: Přečíslovat `n` v `src/Catalog/Chapters.php` pro 14 řádků.**

Otevřít `src/Catalog/Chapters.php`. Pro každou položku po `architectural_styles` (původně `n` = 09) zachovat `n` = 09. Pro položky po smazané `horizontal_vs_vertical` (původně 10), tj. od `implementation_in_symfony` (původně 11) dál, snížit `n` o 1.

Konkrétně mapování:
- `implementation_in_symfony`: '11' → '10'
- `authorization_in_ddd`: '12' → '11'
- `cqrs`: '13' → '12'
- `event_sourcing`: '14' → '13'
- `sagas`: '15' → '14'
- `outbox_pattern`: '16' → '15'
- `performance_aspects`: '17' → '16'
- `testing_ddd`: '18' → '17'
- `migration_from_crud`: '19' → '18'
- `microservices_and_ddd`: '20' → '19'
- `ddd_pain_points`: '21' → '20'
- `anti_patterns`: '22' → '21'
- `when_not_to_use_ddd`: '23' → '22'
- `practical_examples`: '24' → '23'
- `case_study`: '25' → '24'

```bash
sed -i \
  -e "s/'n' => '11', 'route' => 'implementation_in_symfony'/'n' => '10', 'route' => 'implementation_in_symfony'/" \
  -e "s/'n' => '12', 'route' => 'authorization_in_ddd'/'n' => '11', 'route' => 'authorization_in_ddd'/" \
  -e "s/'n' => '13', 'route' => 'cqrs'/'n' => '12', 'route' => 'cqrs'/" \
  -e "s/'n' => '14', 'route' => 'event_sourcing'/'n' => '13', 'route' => 'event_sourcing'/" \
  -e "s/'n' => '15', 'route' => 'sagas'/'n' => '14', 'route' => 'sagas'/" \
  -e "s/'n' => '16', 'route' => 'outbox_pattern'/'n' => '15', 'route' => 'outbox_pattern'/" \
  -e "s/'n' => '17', 'route' => 'performance_aspects'/'n' => '16', 'route' => 'performance_aspects'/" \
  -e "s/'n' => '18', 'route' => 'testing_ddd'/'n' => '17', 'route' => 'testing_ddd'/" \
  -e "s/'n' => '19', 'route' => 'migration_from_crud'/'n' => '18', 'route' => 'migration_from_crud'/" \
  -e "s/'n' => '20', 'route' => 'microservices_and_ddd'/'n' => '19', 'route' => 'microservices_and_ddd'/" \
  -e "s/'n' => '21', 'route' => 'ddd_pain_points'/'n' => '20', 'route' => 'ddd_pain_points'/" \
  -e "s/'n' => '22', 'route' => 'anti_patterns'/'n' => '21', 'route' => 'anti_patterns'/" \
  -e "s/'n' => '23', 'route' => 'when_not_to_use_ddd'/'n' => '22', 'route' => 'when_not_to_use_ddd'/" \
  -e "s/'n' => '24', 'route' => 'practical_examples'/'n' => '23', 'route' => 'practical_examples'/" \
  -e "s/'n' => '25', 'route' => 'case_study'/'n' => '24', 'route' => 'case_study'/" \
  src/Catalog/Chapters.php
```

Verify:
```bash
grep "'n' =>" src/Catalog/Chapters.php | head -30
```

Expected: čísla jdou postupně 00 (preface), 01–09, 10–24, bez mezery.

- [ ] **Step 9: Aktualizovat inline reference na čísla kapitol v textu.**

Najít v textu reference jako „kapitole 22", „kapitola 19" atd.:
```bash
grep -nE "kapitol[ae]\? \?[12][0-9]" content/chapters/*.md | grep -v "^content/chapters/[a-z_]*\.md:.*kapitola [0-9]+$"
```

Pro každou nalezenou referenci ověřit kontext:
- Pokud jde o referenci na **vlastní knihu** (např. „v kapitole 22 — Anti-vzory"), aktualizovat číslo podle nového mapování. Příklad: „v kapitole 22 – Anti-vzory" → „v kapitole 21 – Anti-vzory".
- Pokud jde o referenci na **externí knihu** (Vernon, Evans, Khononov, Newman), nechat jak je.

Konkrétně známé místa k opravě (z dříve zjištěného grepu):
- `microservices_and_ddd.md:289`: „kapitole 22 – Anti-vzory DDD" → „kapitole 21 – Anti-vzory DDD"
- `microservices_and_ddd.md:453`: „kapitola 15 – Ságy" → „kapitola 14 – Ságy"
- `microservices_and_ddd.md:822`: „kapitole 19 – Migrace z CRUD" → „kapitole 18 – Migrace z CRUD"
- `microservices_and_ddd.md:868`: „kapitole 22 – Anti-vzory" → „kapitole 21 – Anti-vzory"
- `microservices_and_ddd.md:880`: „kapitole 15" → „kapitole 14"
- `aggregate_design.md:609`: „CQRS, kapitola 13" → „CQRS, kapitola 12"
- Jiné pravděpodobně existují — projít všechny soubory.

> **Pozn.:** Doporučuji udělat `git diff` po této úpravě a ručně projít každou změnu, abychom omylem neaktualizovali čísla v citacích externích knih.

- [ ] **Step 10: Ověřit, že žádný odkaz nezískal špatné číslo.**

```bash
git diff content/chapters/ | grep "^[-+].*kapitol" | head -30
```

Pro každou diff řádku self-check: jde o vnitřní referenci nebo externí citace? Pokud externí, vrátit (např. `git checkout content/chapters/file.md`).

- [ ] **Step 11: Aktualizovat preface.md po přečíslování.**

Preface (Task 4) byla psána s odkazy podle starého číslování. Nyní aktualizovat čísla v sekci „Co kniha pokrývá" a „Jak číst tuto knihu":
- Strategie: 1–5 (zůstává).
- Taktika: 6–9 (zůstává — nyní zahrnuje kap. 9 obohacenou o vertical slice).
- Implementace: 10–11 (z 11–12).
- Pokročilé vzory: 12–15 (z 13–16).
- Výkon a testování: 16–17 (z 17–18).
- Migrace + microservices: 18–19 (z 19–20).
- Provoz a anti-vzory: 20–22 (z 21–23).
- Příklady: 23–24 (z 24–25).

V Reading Path sekci preface aktualizovat čísla podle nového mapování.

- [ ] **Step 12: Aktualizovat what_is_ddd.md po přečíslování.**

Pokud Task 5 obsahoval explicitní číselné odkazy (např. „kap. 23"), aktualizovat na nová čísla. Doporučuji v Tasku 5 použít cesty (`/kdy-nepouzivat-ddd`) místo čísel — pak Task 8 v této kapitole nic neaktualizuje.

- [ ] **Step 13: Ověřit build.**

```bash
bash ebook/build.sh 2>&1 | tail -15
```

Expected:
- Build successful.
- V output PDF/EPUB:
  - Kap. 9 (Architektonické styly) je rozšířená o vertical slice obsah.
  - Kap. 10 = původní kap. 11 (Implementace v Symfony).
  - Kap. 24 = původní kap. 25 (Case study).
  - Kapitola horizontal_vs_vertical neexistuje.
  - Žádné mezery v číslování.

- [ ] **Step 14: Smoke test webu.**

```bash
php bin/console cache:clear && php bin/console debug:router | grep -E "horizont|vertikal|architekt"
```

Expected:
- `vertical_slice_redirect` → 301 na `architectural_styles`.
- `horizontal_vs_vertical_redirect` → 301 na `architectural_styles`.
- `architectural_styles` route má cestu `/architektonicke-styly`.
- Žádná `horizontal_vs_vertical` route v routeru.

- [ ] **Step 15: Spustit Symfony server a ručně otevřít několik kapitol.**

```bash
symfony server:start -d
```

Otevřít v prohlížeči:
- `http://localhost:8000/architektonicke-styly` — měla by se zobrazit kap. 9 s rozšířenou sekcí Vertical Slice.
- `http://localhost:8000/vertikalni-slice` — měl by být 301 redirect na `/architektonicke-styly`.
- `http://localhost:8000/implementace-v-symfony` — měla by se zobrazit kap. 10 (původně 11) v hlavičce.
- `http://localhost:8000/pripadova-studie` — měla by se zobrazit kap. 24 (původně 25) v hlavičce.

```bash
symfony server:stop
```

- [ ] **Step 16: Commit.**

```bash
git add -A
git commit -m "content(struktura): sloučit kap. 9+10, přečíslovat kap. 11–25 → 10–24"
```

---

## Závěrečná verifikace

### Task 9: Konečné ověření celé revize

- [ ] **Step 1: Spustit build a ověřit kompletnost.**

```bash
bash ebook/build.sh 2>&1 | tee /tmp/final_build.log | tail -20
```

Expected:
- `✓ EPUB:` a `✓ PDF:` ve výstupu.
- Žádné chyby ani varování typu „Chybí: X.md" v `/tmp/final_build.log`.
- Soubory `ebook/output/ddd-v-symfony.epub` a `.pdf` mají rozumnou velikost.

- [ ] **Step 2: Otevřít PDF a zkontrolovat TOC.**

```bash
xdg-open ebook/output/ddd-v-symfony.pdf  # nebo open na macOS
```

Manual checklist v PDF TOC:
- [ ] Předmluva je první kapitolou (přečíslování `00`).
- [ ] Po předmluvě jdou kapitoly 1–24 lineárně bez mezery.
- [ ] Kap. 1 „Co je DDD" je výrazně delší než dříve (motivační příběh + jak číst).
- [ ] Kap. 8 se jmenuje „Doplňující taktické vzory: …".
- [ ] Kap. 9 „Architektonické styly" obsahuje sekci 09.06 Vertical Slice s detailem.
- [ ] Žádná kap. „Horizontal vs. Vertical" jako samostatná položka.
- [ ] Kap. 11 „Autorizace v DDD" má úvod navazující na kap. 10.
- [ ] Kap. 23 „Praktické příklady" je krátká (~6–10 stránek).
- [ ] Kap. 24 „Případová studie" je velká case study.
- [ ] V TOC se nezobrazuje „DDD a umělá inteligence" (ddd_ai).

- [ ] **Step 3: Otevřít EPUB v čtečce.**

```bash
xdg-open ebook/output/ddd-v-symfony.epub
```

Manual checklist: stejný jako step 2.

- [ ] **Step 4: Ověřit web (smoke test).**

```bash
symfony server:start -d
```

Otevřít v prohlížeči:
- `http://localhost:8000/` (homepage) — TOC ukazuje kapitoly 1–24 + extras.
- `http://localhost:8000/predmluva` — předmluva existuje a renderuje.
- `http://localhost:8000/co-je-ddd` — kap. 1 je rozšířená.
- `http://localhost:8000/mene-zname-vzory` — funguje, titul „Doplňující taktické vzory".
- `http://localhost:8000/architektonicke-styly` — funguje, obsahuje Vertical Slice sekci.
- `http://localhost:8000/vertikalni-slice` — 301 redirect na `/architektonicke-styly`.
- `http://localhost:8000/autorizace-v-ddd` — funguje, úvod navazuje na implementaci.
- `http://localhost:8000/prakticke-priklady` — kratší.
- `http://localhost:8000/pripadova-studie` — funguje.
- `http://localhost:8000/ddd-a-umela-inteligence` — funguje (web-only kapitola).

```bash
symfony server:stop
```

- [ ] **Step 5: Spustit existující testy.**

```bash
# Pokud projekt má phpunit
composer test 2>/dev/null || vendor/bin/phpunit 2>/dev/null || echo "no test suite"
```

Expected: pokud existuje test suite, projde.

- [ ] **Step 6: Final git status.**

```bash
git log --oneline -10
```

Expected: 8–9 commitů od začátku revize, každý s jasným popisem.

- [ ] **Step 7: Vytvořit pull request nebo merge to main (volitelné).**

Pokud byla revize na samostatné větvi:
```bash
git push origin <branch>
gh pr create --title "Strukturální revize ebooku — 8 bodů" --body "$(cat docs/superpowers/specs/2026-05-04-ebook-strukturalni-revize-design.md | head -30)"
```

Pokud byla rovnou na `main`, není potřeba nic dělat — commity jsou už lokálně.

---

## Dependency graph (vizuální)

```
Task 1 (ddd_ai poznámka)         — nezávislé
Task 2 (rename kap 8)            — nezávislé
Task 3 (úvod kap 12)             — nezávislé
Task 4 (preface)                 — závisí na Task 5 a 8 pro finální čísla
Task 5 (rozšíření kap 1)         — nezávislé na ostatních
Task 6 (vyčistit 21/22/23)       — nezávislé
Task 7 (zkrátit kap 24)          — nezávislé
Task 8 (sloučit 9+10 + přečíslovat) — POSLEDNÍ, aktualizuje čísla v Task 4 a 5

Doporučené pořadí: 1 → 2 → 3 → 7 → 5 → 4 → 6 → 8
                   (s drobnou aktualizací 4 a 5 v rámci 8)
```

> **Pozn.:** Plán uvádí výchozí pořadí 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8. Pokud preferujete iteraci s rychlými výhrami napřed, alternativní pořadí je 1 → 2 → 3 → 7 → 6 → 5 → 4 → 8 (tj. mechanické úpravy → krácení → rozšiřování → strukturální).

## Risk register

| Risk | Likelihood | Impact | Mitigace |
|---|---|---|---|
| Sed přečíslování zničí náhodné výskyty čísel | Nízká | Vysoký | Regex je kotvený `^## ` na začátek řádku — nezachytí inline čísla |
| Cross-link na neexistující kapitolu | Střední | Střední | Step 9 a 10 v Task 8 — projít a ověřit |
| Build PDF selže kvůli nezbalanced markdown po editaci | Nízká | Střední | Build po každém Tasku |
| Web 404 na `/vertikalni-slice` | Vysoká bez Tasku 8 step 5 | Střední | Redirect na `/architektonicke-styly` |
| Předmluva se na webu nezobrazí | Střední | Nízký | Step 9–10 v Tasku 4 — verify routing |
| Prodleva dat na webu vs. ebook | Nízká | Nízký | Web čte stejné `content/chapters/*.md` jako ebook |

## Akceptační kritéria (z specu)

Po dokončení všech 9 tasků:

- [ ] `ebook/build.sh` projde bez chyb.
- [ ] Každá kapitola má frontmatter s aktuálním `chapter_number`.
- [ ] Žádné dva soubory v `CHAPTER_ORDER` nemají stejné číslo.
- [ ] Cross-linky uvnitř knihy nemají broken targets.
- [ ] Kniha má lineární tok od předmluvy přes kap. 1–24 až po case study.
- [ ] `ddd_ai.md` se ve výsledném PDF/EPUB neobjevuje.
- [ ] Voice/tón v nových sekcích odpovídá CLAUDE.md pravidlům.
