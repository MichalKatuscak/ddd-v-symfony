# Prompt: Revize kapitoly

Tento prompt řídí kompletní revizi jedné kapitoly DDD průvodce.

**Spuštění:** „Použij docs/prompts/review-chapter.md na templates/ddd/<soubor>.html.twig"

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

Přečti ostatní šablony v `templates/ddd/`. Pro každý klíčový termín z aktuální kapitoly ověř, zda je definován stejně:

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
2. Neměň HTML strukturu, ARIA atributy, SEO bloky — pouze textový obsah uvnitř `<p>`, `<li>`, `<h2>`, `<h3>`, `<td>` tagů
3. Zachovej Twig syntaxi a odsazení beze změny
4. Po zápisu spusť: `git diff templates/ddd/<soubor>.html.twig`
5. Počkej na potvrzení uživatele — teprve pak commitni
