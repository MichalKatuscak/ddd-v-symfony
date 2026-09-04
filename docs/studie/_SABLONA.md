# Šablona studie ke kapitole

Tento soubor řídí formát všech souborů `docs/studie/<kapitola>-studie.md`.
Studie je **podklad pro budoucí přepis kapitoly**, ne návrh výsledného textu.

## Účel

Ke každé kapitole knihy vzniká jedna studie. Slouží k tomu, aby přepis kapitoly
stál na ověřené rešerši, ne na dojmu. Studie shrnuje, co k tématu říkají primární
zdroje, jak se stav praxe posunul, kde má současná kapitola díry, a co s tím dělat.

## Pravidla

1. **Studie nesahá na kapitolu.** Žádné editace `content/chapters/*.md`.
2. **Studie není návrh textu.** Nepiš hotové odstavce k vložení do knihy.
   Piš zjištění, tvrzení, zdroje a doporučení.
3. **Každé faktické tvrzení má zdroj.** Buď URL, nebo bibliografický záznam
   (autor, titul, rok, nakladatel). Co se nepodařilo ověřit, patří do sekce
   „neověřené" a musí být takto označeno.
4. **Citace bez čísel stránek.** Konvence knihy — viz `CLAUDE.md`.
5. **Jazyk studie: čeština.** Technické termíny zůstávají v originále.
   Studie je interní dokument, takže pravidla hlasu knihy (zákaz „lze", rytmus
   bulletů apod.) zde neplatí. Platí ale věcnost: žádný marketing, žádná vata.
6. **Odkazy na kapitolu uváděj s číslem řádku** (`content/chapters/cqrs.md:412`),
   ať se dá nález najít.
7. **Rozsah:** 250–450 řádků. Kratší znamená mělkou rešerši, delší znamená vatu.

## Pořadí důvěryhodnosti zdrojů

1. Primární zdroj vzoru (autor, jeho web, jeho kniha, jeho přednáška)
2. martinfowler.com
3. Oficiální dokumentace Symfony / Doctrine / PHP RFC
4. Knihy: Evans *DDD* (2003), Evans *DDD Reference* (2015), Vernon *IDDD* (2013),
   Vernon *DDD Distilled* (2016), Khononov *Learning DDD* (2021),
   Millett & Tune *Patterns, Principles and Practices of DDD* (2015)
5. Konferenční přednášky s dohledatelným záznamem (DDD Europe, Symfony Con)
6. Blogy uznávaných praktiků (Vaughn Vernon, Mathias Verraes, Matthias Noback,
   Greg Young, Udi Dahan, Alberto Brandolini, Nick Tune, Kacper Gunia)

Stack Overflow, obsah generovaný AI a nepodepsané blogy nejsou zdroj.

## Struktura souboru

````markdown
# Studie: <Název kapitoly>

- **Kapitola:** `content/chapters/<soubor>.md` (č. NN, kategorie X, N řádků)
- **Cesta:** /url-kapitoly
- **Typ kapitoly:** narativní | definiční | hybridní (viz `docs/prompts/review-chapter.md`)
- **Datum studie:** YYYY-MM-DD

## 1. Mapa současné kapitoly

Sekce po sekci: co pokrývá, jak hluboko, čím to podkládá.
Tabulka `sekce | rozsah | co tvrdí | zdroje | poznámka`.
Na konci 3–5 vět o celkovém charakteru kapitoly: čemu dává prostor, co odbývá.

## 2. Kanonické zdroje k tématu

Co k tématu skutečně říkají primární zdroje. Kdo vzor zavedl, kde, kdy, jak jej
definoval a jak se jeho definice případně vyvíjela. Pozor na atribuce, které se
v komunitě tradují chybně.

## 3. Stav praxe a posuny

Co se za posledních zhruba pět až deset let změnilo. Co komunita opustila, co
přibylo, jaká doporučení zestárla. Kde má smysl uvést konkrétní příklad z praxe.

## 4. Symfony / PHP specifika

Konkrétní API, balíčky, verze. Co Symfony 8 / Doctrine ORM 3 / PHP 8.4 nabízí
k tématu, co se změnilo oproti starším verzím, jaké knihovny z ekosystému stojí
za zmínku. Odkazy na symfony.com/doc.

## 5. Sporné a chybně podávané body

Kde se seriózní zdroje rozcházejí. Časté mýty. Místa, kde i uznávané knihy
zjednodušují nebo si protiřečí. U každého bodu obě strany sporu a doporučení,
jak k tomu má kniha přistoupit.

## 6. Gap analýza vůči kapitole

Tabulka nálezů:

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí / mělké / zastaralé / sporné / nadbytečné / nepodložené | `soubor.md:123` nebo „sekce 12.04" | co je špatně | co s tím |

Typy:
- **chybí** — téma, které kapitola vůbec neotevírá a mělo by
- **mělké** — otevře a hned opustí
- **zastaralé** — bylo pravda, dnes už ne
- **sporné** — tvrzení, se kterým část seriózních zdrojů nesouhlasí
- **nadbytečné** — patří jinam, nebo nikam
- **nepodložené** — tvrzení bez zdroje, které zdroj potřebuje

## 7. Doporučení k přepisu

Prioritizovaně. U každého: co udělat, proč, jak velký zásah to je.

- **P1** — bez toho je kapitola faktograficky nebo strukturně vadná
- **P2** — znatelně zvedne hodnotu kapitoly
- **P3** — dobrovolné vylepšení

Formát: `P1-1 — <věta co udělat>` + 2–4 věty zdůvodnění + odhad (`nová sekce ~X řádků`,
`přepis sekce 12.04`, `oprava dvou vět`).

## 8. Otevřené otázky pro autora

Rozhodnutí, která nemůže udělat rešerše. Rozsah, cílová skupina, kolik prostoru
dát okrajovému tématu, zda držet nebo opustit určitý příklad.

## 9. Bibliografie

Očíslovaný seznam. Oddělit:

### Ověřené zdroje
`[1] Autor — Titul, rok. URL` (u webu i datum přístupu)

### Neověřené / nedohledané
Co se nepodařilo potvrdit a co by chtělo dohledat ručně.
````

## Kontrola před uložením

- Každé číslo, datum a atribuce v sekcích 2–5 má zdroj v bibliografii.
- Sekce 6 odkazuje na konkrétní řádky kapitoly.
- Sekce 7 je akční — dá se podle ní zadat práce.
- Soubor je 250–450 řádků.
