# Strukturální revize ebooku: 8 bodů

**Datum:** 2026-05-04
**Status:** Draft, čeká na schválení
**Cíl:** Závěrečný polishing struktury knihy „Domain-Driven Design v Symfony 8" před vydáním.

## Kontext

Kniha aktuálně obsahuje 25 kapitol v `CHAPTER_ORDER` (v `ebook/build.sh`) plus
soubor `ddd_ai.md` mimo sequence. Po načtení všech kapitol jsem identifikoval
8 bodů ke zlepšení. Tento spec dokumentuje rozhodnutí pro každý z nich a stanoví
pořadí implementace.

Žádný z těchto bodů není kritický. Cílem revize je lineární tok knihy
a odstranění duplicit, ne přepis obsahu.

## Rozhodnutí pro 8 bodů

### Bod 1: Sloučit kap. 10 do kap. 9

**Problém:** Kap. 9 (Architektonické styly) má sekci 09.06 Vertical Slice.
Kap. 10 (Horizontal vs. Vertical) téma rozvíjí samostatně. Ze 306 řádků
kap. 10 je ~80 % rozšíření toho, co kap. 9 už pokryla.

**Řešení:**
- Obsah kap. 10 přesunout do rozšířené sekce 09.06 v kap. 9.
- Smazat soubor `content/chapters/horizontal_vs_vertical.md`.
- Odebrat `horizontal_vs_vertical` z `CHAPTER_ORDER` v `ebook/build.sh`.
- Přejmenovat `chapter_number` v frontmatteru následujících kapitol z 11–25 na 10–24.
- Přečíslovat všechny `## NN.MM` sekce v následujících kapitolách.
- Přejmenovat odkazy `[kapitola X](/path)` v textu, kde je číslo zmíněno.

**Dopad:** Strukturální změna napříč 14 následujícími kapitolami. Cca 140
sekčních hlaviček k přečíslování. Soubory se nepřejmenovávají (cesty jsou
podle slugů, ne čísel).

**Cross-links:** Po sloučení projít všechny kapitoly a opravit odkazy na
`/vertikalni-slice` (cesta zaniká) — přesměrovat na `/architektonicke-styly#vertical-slice`.

### Bod 2: Přejmenovat kap. 8

**Problém:** Současný název „Méně známé taktické vzory" navozuje představu
kuriozit. Obsah pokrývá Specification Pattern, Domain Services, Factories,
Modules — což jsou doplňující taktické vzory, ne kuriozity.

**Řešení:**
- Přejmenovat na **„Doplňující taktické vzory: Specifications, Domain Services, Factories, Modules"**.
- Aktualizovat `title`, `breadcrumb_name`, `schema_headline`, `page_title`,
  `meta_description` ve frontmatteru.
- Cesta `/mene-zname-vzory` zůstává (URL nikdy neměnit kvůli SEO a externím odkazům).
- Aktualizovat všechny odkazy v ostatních kapitolách: `[Méně známé vzory](/mene-zname-vzory)` → `[Doplňující taktické vzory](/mene-zname-vzory)`.

**Dopad:** Metadata + ~10 cross-linků v ostatních kapitolách.

### Bod 3: Vyčistit duplicitu mezi kap. 21, 22, 23

**Problém:** Tři kapitoly s „negativním" zaměřením v řadě. Po čtení mají každá
jiný úhel: 21 = provozní třenice s technologií, 22 = kódové anti-vzory,
23 = rozhodovací rámec. Existuje ale duplicita: anémický model v 22.02 i v 21.D
(Form vs. Command).

**Řešení:**
- Zachovat všechny tři kapitoly.
- V kap. 21 zostřit zaměření na **provozní/infrastrukturní problémy**:
  Doctrine Unit of Work, Messenger debugging, race conditions, idempotence,
  ACL k externím API, Symfony Form vs. Command. Co se v 21 týká kódových
  anti-vzorů (anemic doménový model, primitive obsession), buď odstranit,
  nebo zkrátit na 1–2 odstavce s odkazem na 22.
- V kap. 22 zostřit zaměření na **kódové/modelovací anti-vzory**:
  anemic, primitive obsession, god aggregate, mutable events, sdílená DB,
  ignored ubiquitous language. Co se v 22 týká provozních problémů, odkázat na 21.
- V kap. 23 ověřit, že je to čistě **rozhodovací rámec** („mám použít DDD?")
  bez detailních anti-vzorů — ty patří do 21 a 22.
- Do úvodu každé z tří kapitol přidat krátkou navigační poznámku
  („tato kapitola pokrývá X, sousední kapitoly Y a Z").

**Dopad:** Cílené krácení a redirect cross-linků. Bez sloučení.

### Bod 4: Vyladit přechod do kap. 12 Autorizace

**Problém:** Kap. 12 sedí mezi 11 (Implementace) a 13 (CQRS). Pozice je
obhajitelná — most mezi implementací (Voter z 11) a CQRS (autorizace
v Command Handleru). Ale úvod kap. 12 to neříká explicitně.

**Řešení:**
- Přepsat úvod kap. 12 (12.00 / 12.01) tak, aby explicitně navazoval na kap. 11.
- Přidat 1–2 odstavce v úvodu typu „v předchozí kapitole jsme implementovali
  agregáty a aplikační vrstvu. Teď přidáme autorizaci, která rozhoduje, kdo
  smí zavolat které use case. V další kapitole pak ukážeme, jak autorizace
  zapadá do CQRS Command Handlerů."
- Žádný přesun pozice kapitoly.

**Dopad:** ~30 řádků v úvodu kap. 12.

### Bod 5: Rozšířit kap. 1

**Problém:** 181 řádků, nejkratší v knize. Pro otevírací kapitolu málo.
Současný obsah: definice + historie + strategický/taktický + výhody/výzvy +
DDD vs. jiné přístupy. Vše suchá teorie.

**Řešení:**
- Cílit na 400–500 řádků (cca 2× současná délka).
- Přidat na začátek **motivační příběh** (~80–120 řádků): konkrétní příklad
  bolavého problému, který DDD řeší. Inspirace: e-shop s rostoucí komplexitou,
  kde tým pochopí, že CRUD model už nestačí (3 stavy objednávky → 12 stavů,
  4 typy zákazníka, 5 platebních metod). Příběh musí být specifický, ne abstraktní.
- Zachovat současné sekce 01.01–01.08 (definice, historie, strategický/taktický
  design, implementace, výhody, výzvy, DDD vs. jiné přístupy), jen rozšířit
  o konkrétní příklady a důkladnější vysvětlení.
- Přidat na konec novou sekci **„Jak číst tuto knihu"** (~100 řádků):
  - Doporučená cesta podle role (junior dev / senior dev / architect / tech lead).
  - Co je nutné, co lze přeskočit.
  - Cross-link na předmluvu (bod 7).

**Dopad:** Cca 250–300 nových řádků v kap. 1.

### Bod 6: Zkrátit kap. 24

**Problém:** Kap. 24 (Praktické příklady) má 917 řádků s 3 mini-příklady
(e-shop, blog, user mgmt). Hodně kódu, který už byl v dřívějších kapitolách.
Stojí za kap. 25 (Case study, 1566 řádků) — působí jako dva konkurenční závěry.

**Řešení:**
- Zachovat strukturu kap. 24 jako „shrnující průžez" před case study.
- Zkrátit z 917 na cca 300 řádků.
- Pro každý ze tří příkladů (e-shop, blog, users) ponechat:
  - Krátký úvod (kontext, doménu, hlavní agregáty).
  - Strukturu projektu (`src/` tree).
  - 1–2 klíčové třídy (agregát + handler) s odkazem na podrobnější verzi
    v dřívějších kapitolách (Aggregate, CQRS, Implementace).
- Smazat duplikovaný kód, který je v jiných kapitolách identicky.
- Závěr kap. 24: „pro detailní procházení reálného projektu pokračujte
  v kap. 24 (Case study)" — protože po přečíslování (bod 1) bude case study
  kap. 24 → 24 a 25 → 24. Wait, čísla po sloučení 9-10:

> **Pozn. k číslování po bodu 1:** Po sloučení kap. 9 a 10 se kapitola
> 11 stává 10, …, kapitola 24 se stává 23 (Praktické příklady)
> a kapitola 25 se stává 24 (Case study).

**Dopad:** Cca 600 řádků k odstranění z kap. 24 (po přečíslování 23).

### Bod 7: Napsat předmluvu

**Problém:** Kniha jde rovnou do kap. 1. Pro 27 000 řádků není „pro koho",
„jak číst", ani struktura.

**Řešení:**
- Vytvořit nový soubor `content/chapters/preface.md` (cca 250–400 řádků).
- Zařadit do `CHAPTER_ORDER` v `build.sh` jako první (před `what_is_ddd`).
- Frontmatter: `chapter_number: "00"` nebo bez číslování (pandoc to zvládne přes `unnumbered`).
- Sekce předmluvy:
  - **Pro koho je tato kniha** — předpoklady (zkušenost s PHP/Symfony,
    OOP, základní designové vzory), úroveň zkušenosti.
  - **Co kniha pokrývá** — krátký přehled částí (číslování po sloučení 9-10):
    strategický design (kap. 1–5), taktický design (kap. 6–9), implementace
    v Symfony a autorizace (kap. 10–11), pokročilé vzory (kap. 12–15),
    výkon a testování (kap. 16–17), migrace a microservices (kap. 18–19),
    provozní problémy a anti-vzory (kap. 20–22), praktické příklady (kap. 23–24).
  - **Jak číst tuto knihu** — doporučené cesty podle role
    (čísla po sloučení 9-10):
    - Junior PHP dev: kap. 1, 6, 7, 10, 17 (základy + implementace + testy).
    - Senior PHP dev: lineárně 1–24.
    - Architekt: kap. 1–5, 18, 19, 21, 22, 23–24 (strategie + migrace + microservices + anti-vzory + příklady).
    - Tech lead: kap. 5, 18, 20, 21 (Team Topologies + migrace + provozní problémy + anti-vzory).
    - Migrující z CRUD: kap. 1, 18 (migrace z CRUD na DDD), pak selektivně podle bolesti.
  - **Konvence v knize** — voice (vykání), code style, callouty (note/warn/pattern/anti),
    diagramy.
  - **Poděkování** — pokud chce autor zmínit, jinak vynechat.

**Dopad:** Nový soubor + úprava `build.sh`.

### Bod 8: Vyřadit `ddd_ai.md` z knihy

**Problém:** Soubor existuje v `content/chapters/ddd_ai.md` (515 řádků),
ale není v `CHAPTER_ORDER`. Po dotazu autor potvrdil, že nemá být v knize.

**Řešení:**
- Soubor `content/chapters/ddd_ai.md` zachovat (zůstává pro web).
- Do frontmatteru přidat poznámku/flag, že kapitola je `ebook: false`
  nebo `web_only: true` (podle existujícího konvence v projektu;
  pokud žádná taková konvence není, jen ponechat soubor mimo `CHAPTER_ORDER`).
- Žádné změny v `build.sh` (soubor tam už není uveden).
- Ověřit v Twig templatech / webových sekcích, že kapitola se v ebooku
  nezobrazuje (pravděpodobně neproblém, protože soubor není v `CHAPTER_ORDER`).

**Dopad:** Minimální. Možná drobná úprava metadat.

## Pořadí implementace

Doporučené pořadí od nejmenšího rizika k největšímu, abychom při každém
kroku mohli ověřit, že nic nerozbilo build:

1. **Bod 8** — `ddd_ai.md` (potvrzení, že je mimo `CHAPTER_ORDER`, případně doplnění poznámky).
   Risk: nulový. Soubor už je mimo build.

2. **Bod 2** — přejmenovat kap. 8.
   Risk: nízký. Jen metadata + cross-linky. Žádná změna struktury.

3. **Bod 4** — vyladit úvod kap. 12.
   Risk: nízký. Jen rozšíření úvodu o ~30 řádků.

4. **Bod 7** — napsat předmluvu.
   Risk: nízký. Nový soubor, přidání do `CHAPTER_ORDER` na první místo.

5. **Bod 5** — rozšířit kap. 1.
   Risk: střední. Hodně nového textu (motivační příběh + jak číst), ale neovlivňuje strukturu.

6. **Bod 3** — vyčistit duplicitu mezi 21, 22, 23.
   Risk: střední. Krácení a redirect cross-linků uvnitř tří kapitol.

7. **Bod 6** — zkrátit kap. 24.
   Risk: střední. Velké krácení, nutno ověřit, že ostatní kapitoly neodkazují
   na konkrétní pasáže v kap. 24, které mizí.

8. **Bod 1** — sloučit kap. 9 a 10 + přečíslovat.
   Risk: vyšší. Strukturální změna napříč 14 kapitolami: `chapter_number`
   ve frontmatteru, sekční hlavičky `## NN.MM`, případné textové reference
   na čísla kapitol. Soubory se nepřejmenovávají (slugy zůstávají).

> **Pozn.:** Bod 1 je až na konci, aby všechny ostatní úpravy proběhly
> na stabilní struktuře. Pokud bychom přečíslování dělali první, každá
> následující úprava by musela vědět, že už je v novém číslování — což zvyšuje
> riziko chyby.

## Co spec NEpokrývá

- **Generování PDF/EPUB** — předpokládáme, že `ebook/build.sh` produkci zvládne
  bez úprav (kromě úpravy `CHAPTER_ORDER` v bodech 1 a 7).
- **Korektury jazyka** — voice/tón už proběhl v dřívějších revizích
  (commit 5e392b2 a 2eedaf8). Nový text v bodech 5 a 7 musí dodržovat
  CLAUDE.md voice rules, ale full lingvistická revize knihy není součástí
  tohoto specu.
- **Diagramy** — žádné nové diagramy se neplánují. Existující se nepřesouvají.
- **Web verze** — předpokládáme, že web a ebook sdílejí stejný `content/chapters/`
  zdroj a přepíše-li se obsah pro ebook, projeví se i na webu (což je žádoucí).
- **Code samples ověření** — předpokládáme, že existující kódové ukázky jsou
  technicky správné (rovněž z dřívějších revizí).

## Akceptační kritéria

Po dokončení revize:
- `ebook/build.sh` projde bez chyb.
- Každá kapitola má frontmatter s aktuálním `chapter_number`.
- Žádné dva soubory v `CHAPTER_ORDER` nemají stejné číslo.
- Cross-linky uvnitř knihy nemají broken targets (žádný odkaz na neexistující
  kapitolu nebo sekci).
- Kniha má lineární tok od předmluvy přes kap. 1–24 (po sloučení 9-10) až
  po case study.
- `ddd_ai.md` se ve výsledném PDF/EPUB neobjevuje.
- Voice/tón v nových sekcích (rozšíření kap. 1, předmluva) odpovídá CLAUDE.md
  pravidlům.

## Otevřené otázky

Žádné. Všechna rozhodnutí jsou potvrzena uživatelem.
