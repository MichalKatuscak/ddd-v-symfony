# Čištění AI signálů průvodce — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Odstranit AI signály z průvodce tak, aby text vypadal jako autorský odborný průvodce, ne jako generovaný obsah.

**Architecture:** Čistě editační práce na Twig šablonách. Žádné nové soubory, žádné změny routování ani PHP kódu. Každý commit je logická jednotka (jeden soubor nebo jeden typ problému).

**Tech Stack:** Twig HTML šablony, plain text editing

---

## Přehled souborů ke změně

| Soubor | Problémy |
|--------|---------|
| `templates/ddd/basic_concepts.html.twig` | Box "Důležité poznámky" s imperativními "Používat" body |
| `templates/ddd/horizontal_vs_vertical.html.twig` | Box "Důležité poznámky" s imperativními "Používat" body |
| `templates/ddd/practical_examples.html.twig` | Box "Důležité poznámky" s imperativními "Používat" body |
| `templates/ddd/implementation_in_symfony.html.twig` | Box "Důležité poznámky" + "je důležité" intro fráze |
| `templates/ddd/case_study.html.twig` | Box "Důležité poznámky" s imperativními "Používat" body + "v rámci" |
| `templates/ddd/what_is_ddd.html.twig` | "je důležité" v uzavíracím odstavci + "klíčový" |
| `templates/ddd/testing_ddd.html.twig` | "klíčovým mechanismem" |
| `templates/ddd/sagas.html.twig` | "je klíčový" + "v rámci" |
| `templates/ddd/event_sourcing.html.twig` | "je klíčový" |

---

## Task 1: Odstranit box "Důležité poznámky" z basic_concepts.html.twig

**Soubor:** `templates/ddd/basic_concepts.html.twig`

Problém: Řádky 181–193 obsahují warning box s názvem "Důležité poznámky" a 5 generickými imperativními body ("Používat X", "Definovat Y"). Tento obsah je AI filler — opakuje informace, které jsou ve zbytku kapitoly detailně vysvětleny.

- [ ] **Krok 1: Odstranit warning box**

Nahradit blok (řádky 181–193):
```html
            <div class="warning" role="note" aria-labelledby="important-notes-heading">
                <h3 id="important-notes-heading">Důležité poznámky</h3>
                <p>
                    Při implementaci DDD platí:
                </p>
                <ul>
                    <li>Používat všudypřítomný jazyk konzistentně v celém projektu.</li>
                    <li>Definovat jasné hranice mezi ohraničenými kontexty.</li>
                    <li>Používat agregáty pro zajištění konzistence dat.</li>
                    <li>Používat repozitáře pro přístup k agregátům.</li>
                    <li>Používat doménové události pro komunikaci mezi ohraničenými kontexty.</li>
                </ul>
            </div>
```

prázdným řádkem (celý blok smazat).

- [ ] **Krok 2: Commit**

```bash
git add templates/ddd/basic_concepts.html.twig
git commit -m "fix: odstranit AI filler box z basic_concepts"
```

---

## Task 2: Odstranit box "Důležité poznámky" z horizontal_vs_vertical.html.twig

**Soubor:** `templates/ddd/horizontal_vs_vertical.html.twig`

Problém: Řádky 389–400 obsahují warning box se 4 generickými imperativními body.

- [ ] **Krok 1: Odstranit warning box**

Nahradit blok (řádky 389–400):
```html
    <div class="warning" role="note" aria-labelledby="important-notes-heading">
        <h3 id="important-notes-heading">Důležité poznámky</h3>
        <p>
            Při implementaci vertikální slice architektury v Symfony 8 je důležité:
        </p>
        <ul>
            <li>Minimalizovat vazby mezi jednotlivými funkcemi (features).</li>
            <li>Používat CQRS pro oddělení čtení a zápisu <a href="https://symfony.com/doc/current/messenger/multiple_buses.html" target="_blank">[11]</a>.</li>
            <li>Používat doménové události pro komunikaci mezi funkcemi.</li>
            <li>Definovat jasné hranice mezi funkcemi.</li>
        </ul>
    </div>
```

prázdným řádkem.

- [ ] **Krok 2: Commit**

```bash
git add templates/ddd/horizontal_vs_vertical.html.twig
git commit -m "fix: odstranit AI filler box z horizontal_vs_vertical"
```

---

## Task 3: Odstranit box "Důležité poznámky" z practical_examples.html.twig

**Soubor:** `templates/ddd/practical_examples.html.twig`

Problém: Řádky 918–931 obsahují warning box se 6 generickými imperativními body.

- [ ] **Krok 1: Odstranit warning box**

Nahradit blok (řádky 918–931):
```html
            <div class="warning" role="alert" aria-labelledby="important-notes-heading">
                <h3 id="important-notes-heading">Důležité poznámky</h3>
                <p>
                    Při implementaci praktických příkladů je důležité:
                </p>
                <ul>
                    <li>Používat hodnotové objekty pro validaci a enkapsulaci doménových konceptů.</li>
                    <li>Používat doménové události pro komunikaci mezi různými částmi aplikace.</li>
                    <li>Oddělovat příkazy a dotazy podle CQRS principů.</li>
                    <li>Používat Symfony Messenger pro implementaci command a query busů.</li>
                    <li>Používat Doctrine ORM pro persistenci doménových objektů.</li>
                    <li>Používat validaci pro validaci příkazů a dotazů.</li>
                </ul>
            </div>
```

prázdným řádkem.

- [ ] **Krok 2: Commit**

```bash
git add templates/ddd/practical_examples.html.twig
git commit -m "fix: odstranit AI filler box z practical_examples"
```

---

## Task 4: Opravit implementation_in_symfony.html.twig

**Soubor:** `templates/ddd/implementation_in_symfony.html.twig`

Tři problémy:
1. Řádek 985: "Je důležité vybrat správný:" — AI intro fráze
2. Řádek 1011: "V DDD je důležité rozlišovat" — AI intro fráze
3. Řádky 1400–1415: Warning box "Důležité poznámky" s 8 generickými body

- [ ] **Krok 1: Opravit řádek 985**

Stávající text:
```html
        Symfony nabízí dva mechanismy pro práci s událostmi. Je důležité vybrat správný:
```

Nový text:
```html
        Symfony nabízí dva mechanismy pro práci s událostmi — každý pro jiný účel:
```

- [ ] **Krok 2: Opravit řádek 1011**

Stávající text:
```html
        V DDD je důležité rozlišovat typy výjimek podle vrstvy, ve které vznikají. Každá vrstva
        má jiné odpovědnosti a jiný typ chyb:
```

Nový text:
```html
        V DDD se výjimky liší podle vrstvy, ve které vznikají. Každá vrstva
        má jiné odpovědnosti a jiný typ chyb:
```

- [ ] **Krok 3: Odstranit warning box (řádky 1400–1415)**

Nahradit blok:
```html
    <div class="warning">
        <h3>Důležité poznámky</h3>
        <p>
            Při implementaci DDD v Symfony 8 je důležité:
        </p>
        <ul>
            <li>Používat Dependency Injection pro oddělení závislostí.</li>
            <li>Používat Messenger komponentu pro implementaci CQRS.</li>
            <li>Používat Doctrine ORM pro persistenci doménových objektů.</li>
            <li>Používat atributy pro konfiguraci služeb a routování.</li>
            <li>Používat formuláře pro zpracování vstupů od uživatele.</li>
            <li>Používat validaci pro validaci doménových objektů.</li>
            <li>Respektovat hranice mezi doménami a neumisťovat doménové modely do sdílené složky.</li>
            <li>Používat Anti-Corruption Layer pro komunikaci mezi doménami, pokud je to nutné.</li>
        </ul>
    </div>
```

prázdným řádkem.

- [ ] **Krok 4: Commit**

```bash
git add templates/ddd/implementation_in_symfony.html.twig
git commit -m "fix: opravit AI fráze a odstranit filler box z implementation_in_symfony"
```

---

## Task 5: Opravit case_study.html.twig

**Soubor:** `templates/ddd/case_study.html.twig`

Tři problémy:
1. Řádky 845–863: Warning box "Důležité poznámky" s 11 generickými imperativními body
2. Řádek 99: "v rámci projektů" → "pro projekty"
3. Řádek 304: "v rámci projektu" → "pro projekt"
4. Řádek 823: vágní "Strategický design je klíčový"

- [ ] **Krok 1: Odstranit warning box (řádky 845–863)**

Nahradit blok:
```html
    <div class="warning" role="alert" aria-labelledby="important-notes-heading">
        <h3 id="important-notes-heading">Důležité poznámky pro implementaci DDD</h3>
        <p>
            Při implementaci Domain-Driven Design a CQRS v Symfony 8 je důležité:
        </p>
        <ul>
            <li>Začít strategickým designem - identifikovat bounded contexts a jejich vztahy před zahájením implementace.</li>
            <li>Vytvořit a používat Ubiquitous Language ve spolupráci s doménovými experty.</li>
            <li>Definovat jasné hranice mezi bounded contexts a implementovat vhodné integrační vzory (Shared Kernel, Customer-Supplier, Conformist, Anti-Corruption Layer).</li>
            <li>Správně identifikovat agregáty a jejich hranice, aby byla zajištěna konzistence dat.</li>
            <li>Používat hodnotové objekty pro validaci a enkapsulaci doménových konceptů.</li>
            <li>Implementovat doménové události pro komunikaci mezi bounded contexts.</li>
            <li>Oddělovat příkazy a dotazy podle CQRS principů.</li>
            <li>Používat Symfony Messenger pro implementaci command a query busů.</li>
            <li>Testovat doménový model nezávisle na infrastruktuře.</li>
            <li>Používat hexagonální architekturu pro oddělení domény od infrastruktury.</li>
            <li>Implementovat repozitáře jako rozhraní v doménové vrstvě a jejich konkrétní implementace v infrastrukturní vrstvě.</li>
        </ul>
    </div>
```

prázdným řádkem.

- [ ] **Krok 2: Opravit řádek 99**

Stávající:
```html
        <li><strong>TaskManagement</strong> - Správa úkolů, vytváření, aktualizace, přiřazování. Tento kontext řeší vše, co souvisí s úkoly v rámci projektů.</li>
```

Nový:
```html
        <li><strong>TaskManagement</strong> - Správa úkolů, vytváření, aktualizace, přiřazování. Tento kontext řeší vše, co souvisí s úkoly v projektech.</li>
```

- [ ] **Krok 3: Opravit řádek 304**

Stávající:
```html
        <li><strong>Task</strong> - Jednotka práce, která má být dokončena v rámci projektu.</li>
```

Nový:
```html
        <li><strong>Task</strong> - Jednotka práce, která má být dokončena v projektu.</li>
```

- [ ] **Krok 4: Opravit řádek 823**

Stávající:
```html
            <strong>Strategický design je klíčový</strong> - Identifikace bounded contexts a jejich vztahů na začátku projektu poskytla jasný rámec pro vývoj. Definice context map pomohla předejít nedorozuměním a zajistila konzistentní integraci mezi kontexty.
```

Nový:
```html
            <strong>Strategický design rozhoduje o výsledku</strong> - Identifikace bounded contexts a jejich vztahů na začátku projektu poskytla jasný rámec pro vývoj. Definice context map pomohla předejít nedorozuměním a zajistila konzistentní integraci mezi kontexty.
```

- [ ] **Krok 5: Commit**

```bash
git add templates/ddd/case_study.html.twig
git commit -m "fix: odstranit AI filler box, opravit klíčový a v rámci v case_study"
```

---

## Task 6: Opravit what_is_ddd.html.twig

**Soubor:** `templates/ddd/what_is_ddd.html.twig`

Problém: Řádky 435–439 obsahují uzavírací odstavec plný vágních frází.

- [ ] **Krok 1: Přepsat uzavírací odstavec (řádek 436–438)**

Stávající text:
```html
                DDD je vhodný pro složité aplikace s bohatou doménou, kde je důležité přesně modelovat doménu a její
                chování. Přináší mnoho výhod, jako je lepší komunikace, flexibilita a modularita, ale má také své výzvy
                a omezení, které je třeba zvážit před jeho adopcí.
```

Nový text:
```html
                DDD se osvědčuje v aplikacích s bohatou doménou, kde přesné modelování obchodní logiky přináší měřitelnou hodnotu. Má reálné náklady — naučení se vzorů, vyšší počáteční složitost, nutnost spolupráce s doménovými experty — a proto vyžaduje vědomé rozhodnutí.
```

- [ ] **Krok 2: Commit**

```bash
git add templates/ddd/what_is_ddd.html.twig
git commit -m "fix: přepsat vágní uzavírací odstavec v what_is_ddd"
```

---

## Task 7: Opravit vágní "klíčový" v testing_ddd.html.twig, sagas.html.twig, event_sourcing.html.twig

### testing_ddd.html.twig (řádek 391)

Stávající:
```html
        Doménové události jsou klíčovým mechanismem DDD pro komunikaci mezi agregáty a bounded contexty. Je nezbytné
```

Nový:
```html
        Doménové události jsou hlavním mechanismem DDD pro komunikaci mezi agregáty a bounded contexty. Je nezbytné
```

### sagas.html.twig (řádek 1750)

Stávající:
```html
            <strong>Timeout handling</strong> je klíčový - každý krok potřebuje časový limit
```

Nový:
```html
            <strong>Timeout handling</strong> — každý krok potřebuje časový limit
```

Pozn.: zde je správné použít en pomlčku (–), nikoliv em dash.

Nový (správně):
```html
            <strong>Timeout handling</strong> – každý krok potřebuje časový limit
```

### event_sourcing.html.twig (řádek 1818)

Stávající:
```html
            <li>ES a CQRS jsou <strong>nezávislé vzory</strong>, ale jejich kombinace je v praxi velmi efektivní.</li>
```

Toto je v pořádku — "efektivní" zde má konkrétní obsah. Neměnit.

Stávající (řádek 1818 — jiný):
Ověřit přesný text před editací (pozor, čísla řádků se mohla posunout).

- [ ] **Krok 1: Opravit testing_ddd.html.twig**

```bash
# Ověřit kontext
grep -n "klíčovým mechanismem" templates/ddd/testing_ddd.html.twig
```

Pak provést edit: "klíčovým mechanismem DDD" → "hlavním mechanismem DDD"

- [ ] **Krok 2: Opravit sagas.html.twig**

```bash
grep -n "Timeout handling.*klíčový" templates/ddd/sagas.html.twig
```

Pak provést edit: "je klíčový -" → "–"

- [ ] **Krok 3: Commit**

```bash
git add templates/ddd/testing_ddd.html.twig templates/ddd/sagas.html.twig
git commit -m "fix: nahradit vágní klíčový konkrétními formulacemi"
```

---

## Task 8: Opravit "v rámci" v event_sourcing.html.twig

**Soubor:** `templates/ddd/event_sourcing.html.twig`

- [ ] **Krok 1: Zkontrolovat "v rámci celého projektu"**

```bash
grep -n "v rámci celého projektu" templates/ddd/event_sourcing.html.twig
```

Stávající (řádek 378):
```
Konvence pojmenování událostí by měla být konzistentní v rámci celého projektu.
```

Nový:
```
Konvence pojmenování událostí by měla být konzistentní napříč celým projektem.
```

- [ ] **Krok 2: Commit**

```bash
git add templates/ddd/event_sourcing.html.twig
git commit -m "fix: nahradit v rámci celého projektu napříč projektem"
```

---

## Ověření po dokončení

- [ ] Spustit lokální server: `symfony server:start`
- [ ] Projít všechny upravené stránky, ověřit, že obsah dává smysl bez odstraněných boxů
- [ ] Zkontrolovat, že odstraněné boxy neobsahovaly nic, co není pokryto jinde v kapitole

---

## Poznámky k analýze (co se ukázalo jako NE-problém)

- **Em dash (—)** — již opraveno v předchozích commitech, v šablonách se nevyskytuje
- **HTML entity v kódu** (`&lt;`, `&gt;`) — správné HTML kódování uvnitř `<pre><code>`, NENÍ chyba
- **Sagas: sekce 2PC** — kompletní a přesná, neměnit
- **Performance: tvrzení o výkonu** — přesné a nuancované, neměnit
- **Specification Pattern** — existuje v `implementation_in_symfony.html.twig:837`
- **when_not_to_use_ddd: code examples** — existují od řádku 121
