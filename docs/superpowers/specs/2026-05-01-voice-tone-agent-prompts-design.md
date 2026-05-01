# Design: Sjednocení voice/tone — agent prompty pro DDD průvodce

**Datum:** 2026-05-01  
**Stav:** schváleno  
**Rozsah:** CLAUDE.md (stálé zásady) + dva prompt soubory pro dva oddělené módy práce

---

## Kontext

Průvodce má 20+ kapitol psaných v různých sezeních. Voice a tón nejsou konzistentní — místy se objevuje marketing, filler, AI signály, nerovnoměrná délka vět. Faktická verifikace dosud probíhala manuálně nebo nebyla prováděna vůbec.

Cíl: zavést systém instrukcí pro Claude Code, který zajistí konzistentní hlas napříč celou příručkou a umožní spolehlivou faktickou verifikaci s webovým vyhledáváním.

---

## Architektura

**Tři soubory, dva módy:**

```
CLAUDE.md                          ← stálé zásady (platí vždy)
docs/prompts/review-chapter.md    ← mód revize existující kapitoly
docs/prompts/write-chapter.md     ← mód psaní nové kapitoly
```

---

## Sekce 1: CLAUDE.md — stálé zásady

Přibyde sekce `## Voice, tón a jazyk`. Platí při každé editaci i psaní.

### Hlas průvodce

Průvodce mluví jako zkušený praktik, který věci dobře zná a nebojí se říct názor. Neprodává, nevybízí, nenadsazuje. Tvrzení jsou přímá a podložená. Čtenář se cítí respektován jako profesionál.

### Pravidla věty

- Věty: krátké až střední (do ~25 slov). Dlouhé věty rozdělit.
- Věta říká jednu věc. Pokud říká dvě, rozdělit.
- Žádný odstavec nezačíná „Je důležité...", „V rámci...", „Je třeba poznamenat...".

### Zakázaná slova — marketing a hype

| Vzor | Problém |
|---|---|
| mocný, výkonný, elegantní, robustní | vágní přídavná jména bez obsahu |
| revoluční, průlomový, game-changer | nadsázka |
| moderní, cutting-edge, state-of-the-art | časově nestabilní, prázdné |
| perfektní, ideální, optimální | bez zdůvodnění jsou lži |
| bezproblémový, hladký, seamless | marketingový jazyk |
| jednoduše, snadno, rychle | podceňuje čtenářův kontext |
| „posune váš projekt na další úroveň" | hype bez obsahu |
| „plně využít potenciál" | PR fráze |
| „nová éra", „nový přístup k..." | inflační superlativy |
| best practice (bez dalšího) | overused — nahradit konkrétním popisem |

### Zakázaná slova — výplň a filler

| Vzor | Náhrada |
|---|---|
| „je důležité si uvědomit, že" | smazat, věc říct přímo |
| „hraje klíčovou roli" | konkrétní sloveso (zajišťuje / umožňuje / brání) |
| „stojí za zmínku, že" | smazat |
| „je třeba poznamenat, že" | smazat |
| „jak jsme již zmínili" | odkaz na sekci, nebo smazat |
| „v rámci" | nahradit „v", „při", „pro" |
| „klíčový" bez konkrétního obsahu | smazat nebo specifikovat |
| „s ohledem na" | „protože", „vzhledem k" |
| „co se týče X" | začít rovnou větou o X |
| „v neposlední řadě" | smazat |
| „samozřejmě", „pochopitelně", „logicky" | smazat |
| „zcela", „naprosto", „absolutně" | smazat nebo zdůvodnit |
| „jinými slovy" (opakovaně) | smazat, přeformulovat přímo |
| „celkově vzato", „shrnuto" | nahradit konkrétním shrnutím |
| „není pochyb o tom, že" | smazat |
| „jak víme" | smazat |

### Typografie a forma

- Em dash (—) zakázán → en pomlčka (–) s mezerami, nebo přeformulovat
- Anglické uvozovky "" zakázány → české „"
- Vykat — nikdy tykat
- „Zde" nikdy „Tady"
- „průvodce" nikdy „kurz" ani „tutoriál"
- Žádné osobní komentáře autora („z mé zkušenosti", „překvapilo mě")

### Reference

```
docs/prompts/review-chapter.md   — revize existující kapitoly
docs/prompts/write-chapter.md    — psaní nové kapitoly
```

---

## Sekce 2: docs/prompts/review-chapter.md

**Spuštění:** „Použij docs/prompts/review-chapter.md na templates/ddd/event_sourcing.html.twig"

**Pět průchodů v pevném pořadí:**

### Průchod 1 — Voice & tón

Projde celý text a označí každé porušení zásad z CLAUDE.md: marketing, filler, délka věty, typografie, zakázané vzory. Výstup: číslovaný seznam s číslem řádku, původním textem a návrhem opravy.

### Průchod 2 — Jazyková kvalita

Kontrola přirozenosti češtiny:
- pasivní konstrukce tam, kde jde použít aktivum
- nominalizace („provedení implementace" → „implementovat")
- germanismy a anglicismy mimo technické termíny
- neodpovídající slovní zásoba pro odborný registr

### Průchod 3 — Faktická verifikace

Pro každé konkrétní tvrzení (definice vzoru, atribuce citátu, číslo verze frameworku, název kapitoly v knize, rok vydání) agent prohledá web z důvěryhodných zdrojů:
- martinfowler.com
- Oficiální dokumentace Symfony (symfony.com/doc)
- Google Books preview
- ACM Digital Library
- weby autorů (vlad.gg, eventstorming.com, teamtopologies.com)

Výstup: tabulka tvrzení se sloupci: tvrzení | stav (OK / OPRAVIT / NEJISTÉ) | zdroj | návrh opravy.

### Průchod 4 — Konzistentnost s ostatními kapitolami

Agent přečte klíčové termíny z kapitoly a vyhledá jejich definice v ostatních šablonách. Označí:
- stejný termín definovaný jinak ve dvou kapitolách
- číslo nebo název kapitoly nesedící s obsahem
- odkaz vedoucí jinam než říká kotva

### Průchod 5 — Výstupní report

Strukturovaný Markdown report se čtyřmi bloky:

```
## Voice/tón — N nálezů
## Jazyk — N nálezů
## Fakta — N nálezů (OK: X, OPRAVIT: Y, NEJISTÉ: Z)
## Konzistentnost — N nálezů
```

Každý nález: číslo řádku | původní text | návrh opravy | důvod.

**Po vašem schválení** agent zapíše opravy do `.html.twig` souboru.

---

## Sekce 3: docs/prompts/write-chapter.md

**Spuštění:** „Použij docs/prompts/write-chapter.md, téma: Context Mapping"

**Čtyři fáze:**

### Fáze 1 — Příprava

Agent přečte:
- `CLAUDE.md` (zásady)
- tři tematicky nejbližší kapitoly (dle navigační struktury nebo tematické příbuznosti — kontext a terminologie)
- `docs/MICRODATA_ARIA_GUIDE.md` (SEO/ARIA struktura šablony)

Sestaví interní seznam termínů, které průvodce již definoval — aby je neopakoval ani s nimi nerozporoval.

### Fáze 2 — Faktická příprava

Před prvním slovem textu agent prohledá web a ověří:
- kanonické definice pojmů (Evans, Vernon, Khononov)
- aktuální verze Symfony API zmíněná v kapitole
- přesné názvy a čísla kapitol v citovaných knihách
- rok vydání a editora každé citované publikace

Výstup: interní seznam „ověřených faktů". Agent z něj čerpá při psaní — nevymýšlí nic navíc.

### Fáze 3 — Psaní

Agent píše kapitolu podle struktury existujících šablon (frontmatter, SEO bloky, sekce s `h2`/`h3`, kódové ukázky, diagramy jako placeholder). Zásady při psaní:

- Každá věta říká jednu věc.
- Žádný odstavec nezačíná výplní.
- Technický termín se definuje při prvním výskytu, pak se používá konzistentně.
- Kód je vždy funkční a odpovídá Symfony verzi průvodce.
- Žádné tvrzení bez ověřeného zdroje z fáze 2.

### Fáze 4 — Vlastní kontrola před odevzdáním

Agent projde napsaný text sám proti sobě:
1. Zakázaná slova a fráze (dle CLAUDE.md)
2. Délky vět — věty přes 25 slov označí a zkrátí
3. Faktická tvrzení — cross-check s fází 2
4. Konzistentnost termínů s ostatními kapitolami

Teprve po čisté kontrole zapíše soubor.

---

## Co se neřeší

- Automatické spouštění přes hooks — pouze explicitní volání promptem
- Překlad obsahu
- Refaktoring HTML/CSS struktury šablon

---

## Soubory ke vzniku

1. `CLAUDE.md` — přidat sekci Voice, tón a jazyk
2. `docs/prompts/review-chapter.md` — nový soubor
3. `docs/prompts/write-chapter.md` — nový soubor
