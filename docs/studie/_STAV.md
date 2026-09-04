# Stav prací na studiích + jak navázat

Poslední aktualizace: 2026-09-04 — **všech 26 studií hotovo**

## Co to je

Ke každé z 26 kapitol vzniká jedna deep-research studie v `docs/studie/<kapitola>-studie.md`.
Studie je podklad pro budoucí přepis kapitoly, ne návrh textu. Formát řídí `docs/studie/_SABLONA.md`.

Studie **nesahají na `content/`**. Soubory nesmí ležet v `content/chapters/` — `ChapterRouteLoader`
tam dělá `glob('*.md')` a čte z každého souboru frontmatter `path`/`route`, takže by aplikace spadla.

## Stav: 26 z 26 hotovo

Ke každé kapitole existuje studie. Celkem 10 199 řádků, zhruba 660 nálezů v gap analýzách
a 136 doporučení priority P1. Kontrola úplnosti:

```bash
for f in content/chapters/*.md; do n=$(basename $f .md); \
  [ -f "docs/studie/$n-studie.md" ] || echo "CHYBI: $n"; done
```

| Kapitola | Řádků | Nálezů | P1 |
|---|---:|---:|---:|
| preface | 421 | 22 | 6 |
| what_is_ddd | 287 | 26 | 5 |
| subdomains | 433 | 22 | 5 |
| context_mapping | 272 | 22 | 7 |
| event_storming | 450 | 30 | 5 |
| team_topologies | 309 | 28 | 5 |
| basic_concepts | 429 | 25 | 6 |
| aggregate_design | 258 | 24 | 6 |
| lesser_known_patterns | 405 | 24 | 5 |
| architectural_styles | 422 | 21 | 5 |
| implementation_in_symfony | 403 | 21 | 4 |
| authorization_in_ddd | 259 | 25 | 6 |
| cqrs | 450 | 22 | 6 |
| event_sourcing | 439 | 21 | 4 |
| sagas | 441 | 24 | 4 |
| outbox_pattern | 427 | 20 | 4 |
| performance_aspects | 250 | 44 | 5 |
| testing_ddd | 448 | 20 | 5 |
| migration_from_crud | 449 | 25 | 7 |
| microservices_and_ddd | 507 | 26 | 7 |
| ddd_pain_points | 444 | 34 | 4 |
| anti_patterns | 250 | 38 | 5 |
| when_not_to_use_ddd | 447 | 21 | 4 |
| practical_examples | 397 | 26 | 4 |
| case_study | 452 | 27 | 8 |
| ddd_ai | 450 | 23 | 4 |

## Omezení, které tuto session limitovalo

Rozpočet `WebSearch` (200 dotazů / session) se vyčerpal zhruba u sedmé studie. Zbytek běžel
na cíleném `WebFetch` a `curl`, což u verifikace API a dokumentace fungovalo dobře, ale
neumožňuje dohledat výrok, u kterého neznáš URL.

Do `~/.claude/settings.json` proto přibylo:

```json
"env": { "CLAUDE_CODE_MAX_WEB_SEARCHES_PER_SESSION": "1000" }
```

Platí od příštího startu Claude Code. V nové session tedy hledání zase funguje.

## Doověřovací backlog pro session s funkčním hledáním

Tohle agenti nedohledali bez fulltextu. Seřazeno podle důležitosti.

1. **`ddd_ai`** — studie hotová, tabulka 2.6 obsahuje 40 výroků připsaných osobám:
   23 ověřených, **6 vyvrácených citovaným zdrojem**, **11 nedohledaných**. Těch 11 je
   vypsaných v sekci 8 studie a jsou priorita číslo jedna.
2. **Udi Dahan, *Sagas: not just for workflows*** — URL vrací 404, není v archivu.
   Kapitola 14 se na text odvolává. Buď dohledat, nebo citaci odstranit.
3. **Greg Young, „CQRS is not an architecture"** — všechny zkoušené URL 404, codebetter.com
   nerezolvuje. Kapitola 12 tvrzení používá na třech místech.
4. **Nat Pryce, Test Data Builder** — natpryce.com odmítá spojení (kapitola 17).
5. **Newman, *Building Microservices* 2. vydání** — čísla kapitol, na která odkazuje
   kapitola 19 (nálezy G23 a G5 v `microservices_and_ddd-studie.md`).
6. **Vernon, *IDDD*, kapitola 14** — tvrzení, na které se odvolává `authorization_in_ddd.md:124`.
7. **Shared Kernel, `context_mapping.md:121`** — formulace „menší než přirozený průnik"
   se v primárním zdroji nenašla.

Každá studie má vlastní sekci 9 „Neověřené / nedohledané" — před přepisem kapitoly ji projdi.

## Jak zadat studii (šablona promptu pro agenta)

Model: opus. Jeden agent = jedna kapitola. Paralelně max 4–5, jinak hrozí session limit.

```
Pracuješ v repozitáři /home/michal/Work/ddd-v-symfony — česká odborná kniha „DDD v Symfony 8".

ÚKOL: Vytvoř deep-research studii ke kapitole `content/chapters/<X>.md`
a ulož ji do `docs/studie/<X>-studie.md`.

POSTUP:
1. Přečti `docs/studie/_SABLONA.md` — závazná šablona a pravidla studie.
2. Přečti `CLAUDE.md` — kanonické API knihy.
3. Přečti celou kapitolu.
4. Prozkoumej kontext: `src/Catalog/Chapters.php`, nadpisy sousedních kapitol
   (`grep -n '^## ' content/chapters/*.md`), a hotové studie v `docs/studie/`
   k tématům, která se překrývají.
5. Proveď skutečnou webovou rešerši. Primární zdroje podle pořadí v šabloně.
6. Napiš studii podle sekcí 1–9 v šabloně. 250–450 řádků.

DŮLEŽITÉ:
- NEEDITUJ nic v `content/`. Zapisuješ jediný soubor.
- Nevymýšlej si zdroje ani URL. Co neověříš, patří do „Neověřené / nedohledané".
- Studie je česky, věcně, bez marketingového jazyka.
- Vrať shrnutí do 10 řádků: počet nálezů, počet P1, 2–3 nejdůležitější zjištění.
```

Do zadání přidej metadata kapitoly (číslo, kategorie, cesta, počet řádků, typ podle
`docs/prompts/review-chapter.md`) a odstavec „specifika této kapitoly" — co konkrétně ověřit.

## Co přijde potom

Studie jsou vstup, ne výstup. Doporučené pořadí prací:

**1. Průřezové nálezy dřív než jednotlivé kapitoly.** Opakují se napříč studiemi, takže
oprava po kapitolách by je řešila dvacetkrát a pokaždé jinak.

- **Rozcházející se kanonické API.** `Order` má nejméně tři varianty (veřejný konstruktor
  v ch06 a ch21, `place()` jako stavový přechod v ch21, `place()` jako factory v ch05/07/08/10,
  instanční `place()` v ch20). `Money` je definován dvakrát odlišně (ch03 `final readonly class`
  vs. ch21 `final class`), `Money::zero()` se používá v ch06 a definuje jen v ch21.
  `AggregateRoot` má dvě různé vnitřní implementace (ch06 vs. ch10).
- **Události nahrávané v konstruktoru** — `practical_examples.md:162` a `case_study.md`,
  proti výslovnému pravidlu v `CLAUDE.md`.
- **Sufix `Event`** u názvů událostí — `anti_patterns.md`, zmínka v `testing_ddd.md`.
- **Doctrine zastarale**: `enable_lazy_ghost_objects`, `auto_generate_proxy_classes`,
  tvrzení „entita nesmí být `final`" (ch07 i ch10 — na Symfony 8 / ORM 3.7 už neplatí),
  `fetch: 'EAGER'` popsaný jako JOIN (ch16 i ch20), odstraněné partial objects a `iterate()`.
- **Idempotency klíč z `TransportMessageIdStamp`** — ch14 i ch20, dokumentace Messengeru
  přesně tuto konstrukci odmítá.
- **Duplicity mezi kapitolami**: 12.17 vs. 14 (sagy), 12.11 vs. 16 (read modely, dokonce
  stejná kotva `#read-model-optimalizace`), 12.16 vs. 17 (testování, a navzájem si protiřečí),
  ch20 duplikuje ~250 řádků z ch10, 11, 15 a 18.
- **Nepodložená čísla** napříč knihou (nákladové modely, výkonnostní údaje, časové odhady)
  a nedůsledné značení „Ilustrativní scénář".

**2. Z P1 doporučení sestavit jeden prioritizovaný seznam.** 136 položek je moc na jeden
průchod — rozdělit na faktické chyby (kód, který nefunguje, a chybné atribuce) a na
strukturální práci (chybějící témata, proporce kapitol).

**3. Teprve pak přepisovat kapitolu po kapitole** podle `docs/prompts/review-chapter.md`,
s příslušnou studií jako podkladem.
