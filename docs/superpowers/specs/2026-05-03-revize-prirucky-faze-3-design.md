# Revize DDD příručky – Fáze 3 (textové oblasti webu)

Datum: 2026-05-03
Autor: Michal + Claude
Status: schváleno

## Cíl

Pokračování revize příručky započaté Fází 1 (voice/jazyk per kapitola) a Fází 2 (konzistence definic, anchor odkazy, číslování). Fáze 3 pokrývá textové oblasti, které dosud nebyly revidovány: hub stránky, glosář, popisky diagramů, a překlepy v MD obsahu.

Faktická verifikace je z rozsahu vyloučena (uživatel uvedl, že ji nedávno provedl).

## Rozsah

**Vstupy:**
- 8 hub stránek + glosář v `templates/ddd/` (Twig)
- 17 `.puml` souborů v `templates/diagrams/`
- 26 souborů v `content/chapters/*.md` (jen pravopis)

**Mimo rozsah:**
- Faktická verifikace (atribuce vzorů, citace knih, verze API)
- HTML/Twig struktura, ARIA atributy, SEO meta tagy a JSON-LD (dotýkáme se jen textového obsahu)
- Kódové bloky v MD (lint/syntax)
- Struktura, pořadí sekcí, nadpisy

## Pracovní postup

Vlastní větev `revize-prirucky-faze-3` od `main`. Žádný direct push do main, finální PR pro review.

### Pass A – Hub stránky + glosář

Voice/jazyk pass na Twig templaty:
- `templates/ddd/glossary.html.twig`
- `templates/ddd/hub_basics.html.twig`
- `templates/ddd/hub_tactics.html.twig`
- `templates/ddd/hub_architecture.html.twig`
- `templates/ddd/hub_patterns.html.twig`
- `templates/ddd/hub_practice.html.twig`
- `templates/ddd/hub_synthesis.html.twig`
- `templates/ddd/hub_reference.html.twig`

Pravidla podle CLAUDE.md sekce „Voice, tón a jazyk" (zakázané fráze, marketing, hype, výplň, em dash, anglické uvozovky, „Tady"→„Zde", vykání).

Editují se jen textové uzly uvnitř HTML tagů. HTML strukturu, Twig syntaxi, ARIA, SEO bloky, schema.org JSON-LD nesahat. Faktické nepřesnosti v definicích se jen flagují, neopravují.

Subagenti paralelně, jeden subagent = jeden Twig soubor (9 souborů → 2 várky po 5 a 4).

**Commit:** `chore(content): revize hub stránek a glosáře`

### Pass B – Pravopis v MD

Mechanický spell check pomocí `aspell --lang=cs` napříč `content/chapters/*.md`.

1. Pro každou kapitolu vyextrahovat plain text (vyjmout kódové bloky, frontmatter, URL, citace v anglických knihách).
2. Spustit `aspell list` → seznam neznámých slov.
3. Odfiltrovat technické termíny whitelistem (Bounded Context, Aggregate Root, CQRS, Symfony, repository, projection, ...).
4. Zbylé výsledky projít manuálně, opravit jen evidentní překlepy.
5. Zachovat technické citace, jména, anglicismy v technických termínech.

**Commit:** `chore(content): oprava překlepů`

### Pass C – Diagramy

Voice/jazyk pass na 17 `.puml` souborů + rekompilace SVG.

1. Subagenti přečtou .puml, opraví popisky podle CLAUDE.md pravidel
2. Hlavní konverzace projde diff a schválí
3. Rekompilace: `plantuml -tsvg <soubor>.puml` pro každý změněný .puml
4. Vizuální kontrola SVG (Read tool) – ověřit, že se nerozbil layout

Subagenti paralelně po 5.

**Commit:** `chore(diagrams): revize popisků a rekompilace SVG`

### PR

Po dokončení všech tří passů:
- Push větve `revize-prirucky-faze-3`
- PR „Fáze 3: hub stránky, glosář, pravopis, diagramy"
- Tělo PR: souhrn nálezů per pass

## Pořadí

A → B → C. A je nejmenší riziko (jasně ohraničený voice/jazyk pass). B je mechanická kontrola s malými diffy. C má nejvíc kroků (úprava .puml + rekompilace + ověření SVG).

## Subagent prompty

Šablony viz plán (writing-plans), ne zde. Klíčové body:
- Striktně podle CLAUDE.md voice pravidel
- Jen textový obsah – HTML strukturu / Twig / ARIA / SEO / kód NESÁHNOUT
- Faktické nepřesnosti jen flagovat, neopravovat
- Po dokončení vrátit jednostránkový report s počty úprav per kategorie

## Commitování

- Per pass = 1 commit (3 commity celkem)
- Bez `Co-Authored-By: Claude`
- Vše na větvi `revize-prirucky-faze-3`, pak PR pro review

## Rizika

| Riziko | Mitigace |
|---|---|
| Subagent zasáhne HTML / Twig strukturu | Explicitní zákaz v promptu, manuální kontrola diffu |
| Rekompilace SVG změní layout | Vizuální kontrola SVG před commitem; rollback na původní SVG, pokud se layout rozbije |
| aspell hlásí technické termíny jako překlepy | Whitelist domain/Symfony termínů, manuální review zbytku |

## Definition of Done

**Per pass:**
- Subagent zapsal opravy
- Diff schválen v hlavní konverzaci
- Commit s jasným popisem na větvi `revize-prirucky-faze-3`

**Celkově:**
- 3 commity na větvi
- Push + otevřený PR „Fáze 3: …"
- Žádné necommitnuté změny

## Otevřené otázky

Žádné. Návrh schválen uživatelem 2026-05-03.
