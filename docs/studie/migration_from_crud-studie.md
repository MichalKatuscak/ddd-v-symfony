# Studie: Migrace z CRUD architektury na DDD

- **Kapitola:** `content/chapters/migration_from_crud.md` (č. 18, kategorie Praxe, 1049 řádků)
- **Cesta:** /migrace-z-crud
- **Typ kapitoly:** narativní
- **Datum studie:** 2026-09-03

> Poznámka k metodě: rozpočet `WebSearch` byl vyčerpán (200/200), rešerše proto stojí
> výhradně na cílených `WebFetch` primárních URL. U každého zdroje v sekci 9 je uvedeno,
> jak byl získán.

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| 18.01 Kdy a proč migrovat | 22–71 | Šest příznaků, že CRUD nestačí; kdy DDD přináší hodnotu vs. kdy zůstat u CRUD; migrace trvá měsíce až roky | Fowler, Transaction Script `[1]` | Jediný odkaz na EAA katalog; ekonomika migrace shrnutá do 5 vět bez čísel |
| 18.02 Strangler Fig | 73–198 | Atribuce Fowlerovi, 4 fáze principu, struktura projektu při koexistenci, výhody proti big-bang, čtyřfázová datová migrace (dual-write → backfill → shadow reads → cutover) | Fowler `[2]`, Spolsky `[3]` | Nejsilnější část kapitoly. Datová migrace (145–198) je věcně správná a v české literatuře neobvykle konkrétní |
| 18.03 Krok 1: Analýza domény | 200–293 | Tři vodítka pro nalezení BC v CRUD kódu; Event Storming; ukázka „před“ s pěti kusy doménové logiky v kontroleru | eventstorming.com `[4]` | Identifikace BC odbytá třemi bullety; nic o databázovém schématu jako zdroji hranic |
| 18.04 Krok 2: Extrakce doménové vrstvy | 295–507 | Nejdřív Value Objects, pak přesun logiky do entit; before/after `UserService` → `User`; `Email` VO | žádné | Kód ilustruje cílový stav, ne cestu k němu. Chybí mezikroky, kdy aplikace ještě běží |
| 18.05 Krok 3: Repozitáře | 509–624 | Doménové rozhraní vs. Doctrine implementace; DI alias v `services.yaml` | žádné | Technicky v pořádku, dvě drobné nekonzistence v kódu (viz G17, G18) |
| 18.06 Krok 4: CQRS | 626–756 | CQRS až po usazení modelu; začít write side; Command DTO + handler + tenký kontroler | žádné | Správné pořadí, ale bez odkazu na to, jak CQRS zavést inkrementálně vedle starých service tříd |
| 18.07 Testování při migraci | 758–910 | Charakterizační testy (Feathers) + unit testy domény; poznámka o LLM | Feathers `[5]` | Z Featherse převzat jediný pojem. Chybí seams, chybí jeho definice legacy code |
| 18.08 Rizika a doporučení | 912–953 | Pět nejčastějších chyb, tipy pro tým, odhad 12–24 měsíců, varování před big-bang | žádné | Odhad i „typický scénář“ selhání jsou bez zdroje |
| 18.09 Refaktoring kuchařka | 955–1037 | Osm receptů symptom → kroky, každý s odkazem do jiné kapitoly | žádné | Nejhustší část na užitečnost/řádek. Recepty 1, 3, 6, 8 jsou taktické, 2, 4, 7 architektonické |
| FAQ | 1038–1049 | Pět otázek shrnujících sekce | – | Konzistentní s tělem kapitoly |

Kapitola je narativní průvodce „krok 1 až 4“ a drží se ho poctivě. Nejvíc prostoru dává
mechanice Strangler Fig a ukázkám cílového kódu; datová migrace je zjevně psaná někým,
kdo ji dělal. Odbývá naopak dvě věci, na kterých migrace v praxi stojí: bezpečné techniky
postupné změny (Branch by Abstraction, Parallel Change, Mikado) a Anti-Corruption Layer,
který kapitola třikrát zmíní jako nosný mechanismus, ale nikdy nevysvětlí ani neukáže.
Ekonomická stránka je rozdělená mezi dvě sekce (18.01 a 18.08) a čísla v ní nemají oporu.
Faktografická hustota je nízká: pět externích odkazů na 1049 řádků.

## 2. Kanonické zdroje k tématu

**Strangler Fig Application.** Metaforu Fowler odvodil z fíkovníků, které viděl při dovolené
v deštném pralese v Queenslandu v roce 2001; blogový zápis napsal „a couple of years later“.
Současná verze textu na martinfowler.com nese datum 22. srpna 2024 a je podstatně
rozšířená proti původnímu zápisu `[2]`. Původní název zněl **Strangler Application**;
Fowler jej přejmenoval a důvod uvádí v poznámce pod čarou: *„The original post was just
entitled 'Strangler Application'. This led to people often using 'strangler' and forgetting
the botanical origin of the name. As the term gained popularity I became concerned about
this due to its connotations of violence."* Přejmenování bylo vědomě „subtle change“,
protože zavedený termín se mění těžko. Přesné datum původního zápisu ani datum přejmenování
se z primárního zdroje vyčíst nedá (viz sekce 9, neověřené).

Verze z roku 2024 formuluje vzor jako čtyři aktivity, ne jako čtyři fáze kódu:
(1) ujasnit cílové výsledky, (2) najít v systému seams a rozbít problém na části,
(3) dodávat náhrady inkrementálně, (4) měnit organizační praktiky a kulturu.
Fowler zároveň přiznává limity: nahrazení systému zůstává obtížné, vzor komplexitu
neodstraňuje, jen rozprostírá investici a návratnost v čase, a vyžaduje investici do
přechodové architektury, která se nakonec zahodí. Bez organizační změny hrozí, že nový
systém bude stejně křehký jako starý `[2]`.

**Patterns of Legacy Displacement.** Ian Cartwright, Rob Horn a James Lewis rozvedli
Strangler Fig do katalogu vzorů na martinfowler.com (index 5. 3. 2024) `[6]`. Pro migraci
z CRUD jsou relevantní zejména:

- **Event Interception** (5. 3. 2024) `[7]` – *„Intercept any updates to system state and route
  some of them to a new component."* Realizuje se přes messaging (Wire Tap, Content-Based
  Router), API gateway, progressive enhancement v UI nebo databázové triggery. Případová
  studie v článku popisuje čtyřstupňovou extrakci: nejdřív se přesměruje čtení, pak zápisy,
  pak doménová logika.
- **Legacy Mimic** (12. 1. 2022) `[8]` – nový systém komunikuje se starým tak, *„that the old
  system is not aware of any changes"*. Autoři explicitně říkají, že Legacy Mimic často
  realizuje Anti-Corruption Layer přes services, adaptéry, translatory a fasády. Rozlišují
  service-providing a service-consuming mimic.
- **Transitional Architecture** (28. 3. 2022) `[9]` – *„Software elements installed to ease the
  displacement of a legacy system that we intend to remove when the displacement is complete."*
  Klíčová věta: *„you will have to invest in work that will be thrown away."*
- **Divert the Flow** (20. 1. 2022) `[10]` – migraci nezačínat u okrajů, ale u Critical
  Aggregatoru, který drží architekturu zmrazenou. Ověřování přes parallel running.
- **Feature Parity** (27. 7. 2021) `[11]` – *„In general this is a pattern that we don't
  recommend."* Autoři varují, že lidé podceňují náklad replikace stávající funkcionality,
  a odkazují na Standish Group (2014), podle níž 50 % funkcí legacy systémů nikdo nepoužívá.

**Branch by Abstraction.** Fowlerův bliki zápis ze 7. 1. 2014 `[12]`. Termín zavedl
Paul Hammant, který jeho autorství připisuje Stacy Curlovi. Definice: technika *„making a
large-scale change to a software system in gradual way that allows you to release the system
regularly while the change is still in-progress."* Pět kroků: vytvořit abstrakci nad
interakcí klient–dodavatel, převést na ni klienty, postavit novou implementaci za stejnou
abstrakcí, přepínat klienty po částech, odstranit starou implementaci.

**Parallel Change (expand–contract).** Článek Danila Sata na martinfowler.com,
13. 5. 2014 `[13]`. Techniku dokumentoval Joshua Kerievsky v roce 2006 a prezentoval ji na
Lean Software and Systems Conference 2010 v přednášce „The Limited Red Society“.
Tři fáze: **expand** (rozhraní podporuje starou i novou verzi), **migrate** (klienti se
inkrementálně převádějí; u externích klientů je to nejdelší fáze), **contract** (stará verze
se odstraní). Hodnota vzoru je v tom, že kód je releasovatelný v kterékoli ze tří fází.

**Seams a legacy code.** Fowlerův bliki zápis „Legacy Seam“ (4. 1. 2024) `[14]` cituje
Featherse: *„a seam is a place where you can alter behavior in your program without editing
in that place."* Každý seam má **enabling point** – místo, kde se rozhoduje, které chování
se použije. Fowler uvádí tři použití seams: testování, observabilita a legacy displacement.
A varuje, že zavést seams do zaběhnutého legacy systému stojí značné úsilí. Kniha Michaela
Featherse *Working Effectively with Legacy Code* (Prentice Hall, ISBN 9780131177055) `[5]`
je primárním zdrojem pro charakterizační testy i pro seams.

**Evoluce databázového schématu.** Pramod Sadalage a Martin Fowler, „Evolutionary Database
Design“ (původně leden 2003, kompletně přepsáno v květnu 2016) `[15]`. Definují database
refactoring jako změnu vnitřní struktury bez změny pozorovatelného chování, popisují
**transition phase** (aplikace Parallel Change na schéma) a zpětnou kompatibilitu přes
**views a triggery** – přejmenovaná tabulka dostane view s původním názvem, takže závislé
systémy migrují vlastním tempem.

**Kdy je přepis legitimní.** Fowler, „Sacrificial Architecture“ (20. 10. 2014) `[16]`:
*„often the best code you can write now is code you'll discard in a couple of years time."*
Uvádí eBay (1997 Perl → C++, 2002 C++ → Java) a Googlem používané pravidlo „design for
~10X growth, but plan to rewrite before ~100X“. Podstatná je podmínka: *„The team that
writes the sacrificial architecture is the team that decides it's time to sacrifice it."*

## 3. Stav praxe a posuny

**Od jednoho vzoru ke katalogu.** V roce 2018 se o migraci mluvilo jako o „Stranglerovi“ a tím
to skončilo. Od roku 2021 existuje pojmenovaný katalog `[6]`, který rozkládá vzor na
rozhodnutelné kusy. Kapitola stojí na stavu poznání kolem roku 2018.

**Důraz se přesunul z techniky na organizaci.** Fowlerova verze z roku 2024 dává čtvrtinu
prostoru organizační změně a explicitně říká, že bez ní vznikne stejně křehký systém `[2]`.
Autoři katalogu mají celou skupinu vzorů „Organizational Change“ (Build as You Mean to
Continue, Incremental Displacement, Protected Pilot, New Co) `[6]`. Kapitola 18 má
organizační vrstvu zredukovanou na čtyři bullety „Tipy pro týmovou komunikaci“ (922–927).

**Feature parity přestala být cílem.** Do zhruba roku 2020 se migrace plánovala jako
„uděláme totéž, jen líp“. Dnes je to explicitně nedoporučovaný vzor `[11]`. Pro DDD migraci
to má přímý důsledek: část starého chování se nemá modelovat, má se zrušit. Kapitola tuto
možnost nezmiňuje.

**Refaktoring bez testů přestal být tabu.** Matthias Noback v roce 2022 argumentuje, že
strukturální transformace zachovávající chování jsou bezpečné i bez testů, pokud je jistí
statická analýza (PHPStan), vysokoúrovňové testy a párové programování `[17]`. To je posun
proti Feathersově pravidlu „bez testů nerefaktoruj“ a stojí za zmínku právě v migrační
kapitole, protože charakterizační testy pro celý legacy systém nikdo nenapíše.

**Mikado Method jako protipól bottom-up refaktoringu.** Metodu vytvořili Ola Ellnestam a
Daniel Brolund (Manning); Noback ji popsal v sérii z února 2021 `[18]`. Postup je shora dolů:
zkusí se rovnou cílová změna, nechá se selhat, a z pádů se odvodí graf prerekvizit.
Doplňkový článek téže série `[19]` formuluje pravidlo, které kapitole chybí:
*„You should be able to stop the refactoring project at any time, while still leaving the
project in a better state."*

**Práce s Doctrine se ustálila na pragmatismu.** Noback, který dřív prosazoval oddělení
doménových a ORM entit, v roce 2022 píše: *„full decoupling is usually not the best choice.
Rather, 80% decoupling is fine."* `[20]` Podmínkou je, že entita funguje v testu bez databáze
a její metody nespouštějí lazy loading. To je přesně pozice, kterou kniha zaujímá v Receptu 2
(971–986) – stojí za to ji tímto zdrojem podepřít.

## 4. Symfony / PHP specifika

**Symfony 8.** Aktuální dokumentace na symfony.com nese verzi 8.1 `[21]`. Kapitola cílí na
Symfony 8, což sedí. `doctrine.html` popisuje entity přes atributy, `ServiceEntityRepository`,
a migrace přes `DoctrineMigrationsBundle` (`make:migration`, `doctrine:migrations:migrate`,
tabulka `migration_versions`). Pro migrační kapitolu je podstatné, že migrace jsou verzované
SQL soubory vedle kódu – to je přesně mechanismus, který Sadalage a Fowler `[15]` popisují
jako předpoklad evolučního schématu. Kapitola 18 migrační nástroj nezmiňuje ani jednou.

**Doctrine custom mapping types.** Dokumentace ORM `[22]` vyžaduje `getSQLDeclaration()`,
`convertToPHPValue()`, `convertToDatabaseValue()` a `getName()`, registraci přes
`Type::addType()` plus `registerDoctrineTypeMapping()`. Zásadní omezení pro Recept 3
(988–994): *„The UnitOfWork internally assumes that entity identifiers are castable to string.
Hence, when using custom types that map to PHP objects as IDs, such objects must implement
the `__toString()` magic method."* Recept 3 navrhuje `final readonly class OrderId` bez
`__toString()`. Bez něj to s Doctrine nebude fungovat.

**Doctrine embeddables.** Dokumentace `[23]`: *„Embeddables can only contain properties with
basic `@Column` mapping."* Výchozí prefix sloupců je odvozen od názvu property, mění se přes
`columnPrefix`, vypíná `columnPrefix: false`. Vnořené embeddables dokumentace neuvádí jako
podporované. Kapitola používá `#[ORM\Embedded(class: HashedPassword::class)]` (378) bez
zmínky o těchto mezích.

**Doctrine – limitace relevantní pro migraci.** Stránka „Limitations and Known Issues“ `[24]`
obsahuje jedno omezení, které přímo trefuje migrační scénář: *„It is not possible to map
several equally looking tables onto one entity."* Kdo chce během migrace držet legacy tabulku
a novou tabulku pod jednou entitou, narazí. Dále: dvě třídy v hierarchii se stejně pojmenovaným
`private` polem vedou na `MappingException` – past při zavádění mezivrstvy nad legacy entitou.

**PHP 8.4 a readonly.** Kapitola mapuje `public readonly UserId $id` (371–373) jako Doctrine
sloupec. To je legitimní: RFC readonly properties `[25]` říká, že
*„ReflectionProperty::setValue() can bypass the requirement that initialization occurs from
the scope where the property has been declared"* a že *„as long as the object is created using
ReflectionClass::newInstanceWithoutConstructor() or some other constructor-bypass, it is always
safe to initialize readonly properties."* Doctrine hydratuje přesně takto. Kapitola to nikde
nevysvětluje, přitom je to častá obava čtenáře.

Od PHP 8.4 jsou readonly properties implicitně `protected(set)` místo private-set `[26]`, což
mění, co může dělat potomek – relevantní pro `class User extends AggregateRoot`.

**PHP 8.4 native lazy objects.** `ReflectionClass::newLazyGhost()` a `newLazyProxy()` `[27]`
umožňují lazy inicializaci bez podtřídy. Komentář v kódu kapitoly *„ne final – Doctrine proxy
z entity dědí"* (369) odpovídá stavu, který kniha popisuje i v `implementation_in_symfony.md:315`.
Zda a od které verze Doctrine ORM 3.x přechází na native lazy objects (a tedy zda entity mohou
být `final`), se z primárních zdrojů ověřit nepodařilo – patří to do neověřených.

## 5. Sporné a chybně podávané body

**„Nikdy nezačínejte migraci kompletním přepisem" (939).** Absolutní formulace. Fowler
v „Sacrificial Architecture“ `[16]` popisuje situace, kdy je přepis správná volba, a eBay
uvádí jako příklad dvou úspěšných přepisů. Rozdíl není v tom, zda přepsat, ale kdo rozhoduje:
*„The team that writes the sacrificial architecture is the team that decides it's time to
sacrifice it."* Kniha má tuto nuanci v `when_not_to_use_ddd.md:373–381` (migration cost
paradox, 5–10X vs 3–4X), kapitola 18 ji ale nezná. Doporučení: zákaz změkčit na
„big-bang rewrite existujícího produkčního systému s aktivním vývojem“ a doplnit dvě až tři
situace, kdy je přepis levnější (systém bez uživatelů, systém, jehož doména se ruší,
systém menší než náklad na zavedení seams).

**Dual-write jako doporučení vs. dual-write jako antipattern.** Sekce 18.02 (151–198) staví
migraci dat na dual-write. Kapitola 15 (`outbox_pattern.md`) používá tentýž termín pro problém,
který outbox odstraňuje. Jde o dvě různé věci – zápis do dvou datových modelů v jedné DB
transakci versus zápis do DB a brokeru bez společné transakce – ale čtenář, který čte obě
kapitoly, si to bez poznámky nespojí. Alternativa, kterou kapitola neuvádí: Event Interception
přes databázové triggery nebo CDC `[7]` zápis do dvou modelů z aplikace vůbec nepotřebuje.

**„Charakterizační testy vznikají před refaktoringem" (846).** Featherse to podpírá, praxe se
posunula. Noback `[17]` popisuje třídu bezpečných strukturálních transformací, kde stačí
PHPStan a vysokoúrovňové testy. Kapitola by měla připustit, že plné pokrytí legacy systému
charakterizačními testy je nedosažitelné, a nabídnout gradaci: automatizovaný refaktoring →
statická analýza → charakterizační test u rizikových míst.

**Atribuce Event Stormingu.** Kapitola (216–217) uvádí jen „vymyslel Alberto Brandolini“
s odkazem na eventstorming.com. To je v pořádku, ale Fowlerův katalog `[6]` řadí Event Storming
mezi vzory pro pochopení legacy problému vedle Value Stream Map, Create Town Plan a Identify
Business Capabilities – tedy jako jednu ze čtyř technik, ne jako jedinou.

**Odhad 12–24 měsíců (931–934).** Bez zdroje. Kniha týž odhad opakuje v
`ddd_pain_points.md:1073`. Číslo je věrohodné, ale v knize nemá oporu ani v citaci, ani
v označeném ilustrativním scénáři. Buď zdroj, nebo explicitní označení jako zkušenostní odhad.

**„Typický scénář" selhání big-bang přepisu (940–944).** Vyprávěný příběh s konkrétním
časovým údajem („po 6 měsících“), bez zdroje a bez označení. Konvence knihy (CLAUDE.md)
vyžaduje u smyšlených případů popisek „Ilustrativní scénář“; kniha jej používá v
`subdomains.md:139`, `what_is_ddd.md:227` a `case_study.md:23`. Zde chybí.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | `migration_from_crud.md:88`, `:920`, `:1042`, `:1046` | Anti-Corruption Layer je zmíněn čtyřikrát jako nosný mechanismus koexistence, ale kapitola ho nedefinuje ani neukáže na kódu | Nová sekce s ACL mezi legacy a novým BC + odkaz na `/context-mapping` |
| G2 | chybí | celá kapitola | Branch by Abstraction a Parallel Change nejsou v kapitole ani nikde jinde v knize (grep = 0 výskytů) | Nová sekce „Techniky bezpečné postupné změny“ `[12]` `[13]` |
| G3 | chybí | celá kapitola | Mikado Method – top-down plánování velkého refaktoringu | Doplnit do téže nové sekce `[18]` |
| G4 | mělké | `:764–776` | Z Featherse převzat jediný pojem. Chybí seams, enabling point a jeho definice legacy code | Rozšířit 18.07 o seams jako mechanismus zavedení švu do CRUD kódu `[5]` `[14]` |
| G5 | mělké | `:206–212` | Identifikace BC z existujícího kódu = tři bullety. Nic o databázi, ačkoli tam hranice reálně leží | Doplnit postup: graf cizích klíčů, schema ownership, churn/coupling metriky |
| G6 | nepodložené | `:931–934` | Odhad 12–24 měsíců pro 50–100 tabulek bez zdroje | Doložit, nebo označit jako zkušenostní odhad a zarámovat proměnnými |
| G7 | chybí | `:65–71`, `:929–934` | Ekonomika migrace je rozdělená mezi dvě sekce a nezná nákladový model z `when_not_to_use_ddd.md:373–381` (X / 5–10X / 3–4X) | Sloučit do jedné sekce a odkázat na `/kdy-nepouzivat-ddd#migration-paradox-heading` |
| G8 | sporné | `:939` | „Nikdy nezačínejte migraci kompletním přepisem“ – absolutní zákaz | Změkčit, doplnit legitimní výjimky podle Sacrificial Architecture `[16]` |
| G9 | chybí | `:912–927` | Chyby při migraci jsou jen technické; organizační příčiny selhání chybí | Doplnit sekci „Proč migrace selhávají“ s organizační vrstvou `[2]` `[6]` |
| G10 | mělké | `:75–78` | Strangler Fig bez data, bez zmínky o přejmenování z „Strangler Application“ a bez Fowlerem přiznaných limitů vzoru | Doplnit historii názvu a odstavec o tom, co vzor neřeší `[2]` |
| G11 | nadbytečné | `:95–128` | Ukázka struktury projektu duplikuje `implementation_in_symfony.md:65` | Zkrátit na deset řádků a odkázat |
| G12 | sporné | `:151–198` vs `outbox_pattern.md:39` | Termín „dual-write“ znamená v každé kapitole něco jiného, bez disambiguace | Jedna věta v 18.02, která rozdíl pojmenuje |
| G13 | chybí | `:151–162` | Aplikační dual-write je jediná nabídnutá cesta. Chybí Event Interception přes triggery / CDC | Doplnit alternativu a kritérium volby `[7]` |
| G14 | chybí | 18.02 | Views a triggery pro zpětnou kompatibilitu schématu; migrace schématu jako expand–contract | Doplnit odstavec `[15]` `[13]` |
| G15 | chybí | 18.03–18.06 | Feature parity jako past: část legacy chování se nemá migrovat, má se zrušit | Doplnit do 18.03 jako krok „co nemigrovat“ `[11]` |
| G16 | nepodložené | `:940–944` | Vyprávěný scénář selhání big-bang přepisu bez zdroje a bez označení | Označit „Ilustrativní scénář“ podle konvence knihy |
| G17 | sporné | `:414`, `:473` | `\DomainException` bez pojmenované výjimky; CLAUDE.md ji připouští jen jako přiznanou zkratku | Zavést `UserAlreadyActivatedException`, `ForbiddenEmailDomainException` |
| G18 | sporné | `:579` vs `:584–585` | `find(User::class, $id->value)` předává primitiv, `findOneBy(['email' => $email])` VO – ve stejné třídě dvě konvence | Sjednotit na VO a vysvětlit, že custom type převod zajistí |
| G19 | sporné | `:595` vs `:381` | `setParameter('status', 'active')` na poli mapovaném `enumType: UserStatus::class` | Předat `UserStatus::ACTIVE` |
| G20 | sporné | `:899–907`, `:883` | Test volá `User::register(/* ... */)` bez argumentů, používá `VerificationToken` bez `use`, a `status()->isPendingVerification()` na enumu, kde metoda není ukázána | Doplnit importy a argumenty, nebo enum s metodou |
| G21 | zastaralé | frontmatter `:19` | `github_examples: Chapter09_Migration` u kapitoly číslo 18 | Sjednotit s číslováním |
| G22 | sporné | `:985` | Recept 2 doporučuje `phpat/phpat`, `microservices_and_ddd.md:800` doporučuje phparkitect | Zvolit jeden nástroj napříč knihou, nebo rozdíl vysvětlit |
| G23 | chybí | `:22–71` | Kapitola nemá kritérium „kdy migraci nezačínat vůbec“ (krátká zbývající životnost, doména před zrušením, chybějící experti) | Doplnit do 18.01, provázat s `/kdy-nepouzivat-ddd` |
| G24 | chybí | celá kapitola | Žádný odkaz na `/testovani-ddd`, `/ddd-v-praxi-kde-to-boli`, `/context-mapping`, `/event-storming` – přitom všechny čtyři téma rozvíjejí | Doplnit cross-linky (SEO páka podle poznámek k prolinkování) |
| G25 | mělké | `:635–636` | „Query side lze zpočátku ponechat“ – jedna věta o tom, co je v praxi polovina migrace | Rozvést: read modely nad legacy schématem jako první krok, ne poslední |

## 7. Doporučení k přepisu

**P1-1 — Doplnit sekci o Anti-Corruption Layer mezi legacy a novým kódem.**
Kapitola čtyřikrát tvrdí, že koexistence stojí na ACL, a nikdy neukáže jak. Bez toho je
Strangler Fig v kapitole nedokončený: čtenář ví, že má stavět nové vedle starého, ale ne jak
je propojit, aniž nový model převezme tvar starých tabulek. Sekce má obsahovat translator
z legacy `User` row na doménový `User`, pravidlo, že ACL patří do infrastruktury nového BC,
a poznámku, že ACL po dokončení migrace zmizí. Odkaz na Legacy Mimic `[8]` a `/context-mapping`.
*Odhad: nová sekce ~70 řádků včetně jedné ukázky.*

**P1-2 — Nová sekce „Techniky bezpečné postupné změny“ (Branch by Abstraction, Parallel Change, Mikado).**
Tři pojmenované techniky, které migraci z CRUD reálně nesou, v knize nejsou vůbec. Strangler Fig
je strategie; tyto tři jsou taktika, jak jednotlivý krok provést tak, aby byl systém
releasovatelný po celou dobu. Parallel Change navíc platí i na databázové schéma, což kapitole
uzavře mezeru G14. Mikado dodá čtenáři odpověď na otázku „čím začít“, kterou kapitola dnes
neřeší. *Odhad: nová sekce ~90 řádků, tři podsekce, jeden diagram nepovinně.*

**P1-3 — Sloučit ekonomiku migrace do jedné sekce a doložit čísla.**
Dnes je rozdělená mezi 18.01 (65–71, bez čísel) a 18.08 (929–934, čísla bez zdroje), zatímco
nejsilnější data v knize leží v `when_not_to_use_ddd.md:373–381` a kapitola 18 o nich neví.
Sjednocení odstraní rozpor mezi „3–4X“ tam a „12–24 měsíců“ tady a dá čtenáři jeden model,
podle kterého rozhodne. Odhad 12–24 měsíců označit jako zkušenostní, ne jako měření.
*Odhad: přepis dvou pasáží, čistý přírůstek ~15 řádků.*

**P1-4 — Doplnit „kdy migraci nezačínat a kdy je přepis levnější“.**
Kapitola má dnes jen absolutní zákaz přepisu (939). Fowler `[16]` i katalog `[11]` ukazují, že
rozhodnutí je jemnější, a kniha sama v kapitole 22 popisuje případ telco, kde tři roky migrace
přišly vniveč. Sekce má dát tři konkrétní podmínky, za kterých se nezačíná, a dvě, za kterých
je přepis levnější. *Odhad: nová podsekce v 18.01, ~35 řádků.*

**P1-5 — Rozšířit 18.07 o seams a Feathersovu definici legacy code.**
Charakterizační test je nástroj, seam je předpoklad. Bez švu se do legacy kódu test nedostane
a kapitola tak dnes doporučuje výsledek bez mechanismu. Doplnit definici seamu, enabling point
a tři nejčastější švy v Symfony CRUD kódu (konstruktorová injekce místo `new`, interface místo
konkrétní service, event listener místo inline kódu). Zároveň připustit Nobackovu gradaci `[17]`,
že ne každý refaktoring test potřebuje. *Odhad: přepis sekce 18.07, +50 řádků.*

**P1-6 — Opravit kódové nekonzistence v ukázkách (G17–G20).**
Čtyři místa: bare `\DomainException` proti konvenci knihy, dvojí konvence předávání ID/VO do
repozitáře v jedné třídě, string parametr na enum sloupci, nekompilovatelný unit test.
Kapitola je z hlediska konvencí jinak čistá, tyto čtyři kazy podrývají důvěru v ostatní ukázky.
*Odhad: oprava zhruba deseti řádků kódu.*

**P1-7 — Doplnit atribuci a limity Strangler Fig.**
Sekce 18.02 dnes vzor představí jednou větou. Chybí: historie názvu a Fowlerovo zdůvodnění
přejmenování, jeho čtyři aktivity ve verzi z roku 2024 (zejména organizační), a jeho vlastní
přiznání, že vzor komplexitu nemaže a vyžaduje zahoditelnou přechodovou architekturu.
Bez toho kapitola vzor prodává, místo aby ho popsala. *Odhad: přepis úvodu 18.02, +30 řádků.*

**P2-1 — Konkretizovat identifikaci Bounded Contextů z existující databáze.**
Dnes tři bullety (206–212). Doplnit postup, který se dá provést: export grafu cizích klíčů,
hledání komponent slabě propojených s ostatními, mapa vlastnictví tabulek, churn analýza
z gitu jako indikátor, kde model tlačí. Fowlerův katalog nabízí Create Town Plan a Identify
Business Capabilities jako protějšek z byznysové strany `[6]`. *Odhad: přepis podsekce, +40 řádků.*

**P2-2 — Doplnit Event Interception a CDC jako alternativu k aplikačnímu dual-write.**
Sekce 18.02 předpokládá, že aplikace umí zapisovat do obou modelů. U skutečně starého kódu
to není dané. Triggery nebo CDC (Debezium – kniha ho zmiňuje v `outbox_pattern.md`) tuto
podmínku obcházejí. Přidat kritérium volby. *Odhad: +25 řádků v 18.02.*

**P2-3 — Doplnit expand–contract na úrovni schématu a views pro zpětnou kompatibilitu.**
Kapitola popisuje migraci dat, ne migraci schématu. Sadalage a Fowler `[15]` nabízejí přesný
mechanismus (view s původním názvem, přechodné období, teprve pak drop). Napojit na
`DoctrineMigrationsBundle`, který kapitola nezmiňuje. *Odhad: +30 řádků.*

**P2-4 — Nová podsekce „Proč migrace na DDD selhává“.**
Dnešní seznam chyb (916–920) je čistě technický. Doplnit organizační příčiny: migrace bez
mandátu, tým bez doménového experta, chybějící rozhodnutí co nemigrovat, migrace vedená
jedním člověkem (bus factor – kniha to má v `ddd_pain_points.md:1043`), a přechodová
architektura, kterou nikdo nezahodil. *Odhad: nová podsekce ~45 řádků.*

**P2-5 — Rozvést migraci read strany.**
Věta „Query side lze zpočátku ponechat“ (635–636) odbývá polovinu práce. Read model postavený
nad legacy schématem je často první bezpečný krok migrace: nemění zápisy, dá se ověřit
porovnáním, a připraví projekce pro nový model. *Odhad: +30 řádků v 18.06.*

**P2-6 — Doplnit cross-linky na `/testovani-ddd`, `/ddd-v-praxi-kde-to-boli`, `/context-mapping`, `/event-storming`.**
Kapitola 18 dnes odkazuje na šest cílů, ale ne na čtyři nejbližší sousedy. Sekce 20.05 E2
a 19.09 na kapitolu 18 odkazují, zpětné odkazy chybí. *Odhad: šest vložených odkazů.*

**P2-7 — Vyřešit terminologickou kolizi „dual-write“ a sjednotit nástroj architektonických testů.**
Jedna disambiguační věta v 18.02 a rozhodnutí phpat vs. phparkitect napříč knihou.
*Odhad: oprava tří vět.*

**P3-1 — Zkrátit ukázku struktury projektu (95–128) na odkaz do kapitoly 10.**
*Odhad: −25 řádků.*

**P3-2 — Doplnit do Receptu 3 poznámku o `__toString()` na ID value objectu.**
Doctrine `UnitOfWork` to vyžaduje `[22]`; bez toho recept nefunguje. *Odhad: jedna věta.*

**P3-3 — Doplnit devátý recept: „Legacy tabulka, kterou nelze změnit“.** Nejčastější situace
v praxi a jediná, kterou kuchařka neřeší. Řešení: view, custom mapping type, nebo persistence
model + mapper. *Odhad: ~15 řádků.* **P3-4 — Opravit `github_examples` ve frontmatteru.**

## 8. Otevřené otázky pro autora

1. **Rozsah.** Kapitola má 1049 řádků, doporučení P1 přidávají zhruba 300. Má kapitola narůst
   na ~1300 řádků, nebo se má část (kuchařka 18.09, struktura projektu) odsunout jinam?
2. **Hranice vůči kapitolám 19 a 20.** Strangler Fig se dnes vykládá na třech místech
   (18.02, 19.09, 20.05 E2). Má kapitola 18 zůstat jediným plným výkladem a ostatní jen
   odkazovat, nebo přijmout kontrolovanou redundanci?
3. **Kolik prostoru organizační vrstvě.** Fowlerova verze 2024 jí dává čtvrtinu. Kniha je
   technická; kolik z toho unese, aniž se změní žánr kapitoly?
4. **Držet nebo opustit příklad `User`/registrace.** Kanonický příklad knihy je `Order`
   (CLAUDE.md). Kapitola 18 jede celá na `User`. Sjednotit s knihou, nebo přiznat, že
   registrace uživatele je pro migraci názornější?
5. **Jak daleko jít s databázovou stránkou.** Views, triggery, CDC a expand–contract schématu
   jsou pro migraci zásadní, ale posouvají kapitolu k datovému inženýrství. Vlastní sekce,
   nebo odstavec s odkazem ven?
6. **Označení ilustrativních pasáží.** Má se scénář selhání big-bang přepisu (940–944)
   označit jako „Ilustrativní scénář“, nebo nahradit doloženým případem (eBay, Netscape,
   Khononovův telco příklad z kapitoly 22)?
7. **Číselné odhady.** Držet „12–24 měsíců“ jako zkušenostní tvrzení, nebo čísla z kapitoly
   vypustit a nechat jen kvalitativní model nákladů?

## 9. Bibliografie

### Ověřené zdroje

Všechny níže uvedené byly získány přímým `WebFetch` (rozpočet `WebSearch` byl vyčerpán);
datum přístupu u všech 2026-09-03.

`[1]` Martin Fowler — *Transaction Script* (P of EAA katalog). https://martinfowler.com/eaaCatalog/transactionScript.html — odkaz použitý kapitolou, nefetchováno v této studii.

`[2]` Martin Fowler — *Strangler Fig Application*, verze datovaná 22. 8. 2024. https://martinfowler.com/bliki/StranglerFigApplication.html — přímý fetch. Zdroj citátu o přejmenování, metafory z Queenslandu 2001, čtyř aktivit a přiznaných limitů vzoru. URL `bliki/StranglerApplication.html` vrací tentýž obsah.

`[3]` Joel Spolsky — *Things You Should Never Do, Part I*, 6. 4. 2000. https://www.joelonsoftware.com/2000/04/06/things-you-should-never-do-part-i/ — odkaz použitý kapitolou, nefetchováno.

`[4]` Alberto Brandolini — EventStorming. https://www.eventstorming.com/ — odkaz použitý kapitolou, nefetchováno.

`[5]` Michael C. Feathers — *Working Effectively with Legacy Code*, Prentice Hall / Pearson, ISBN 9780131177055. Výňatek na InformIT (21. 1. 2005): https://www.informit.com/articles/article.aspx?p=359417 — přímý fetch; potvrzeny autorství, vydavatel a ISBN. Definice legacy code a seamu ve fetchnuté části nejsou.

`[6]` Ian Cartwright, Rob Horn, James Lewis — *Patterns of Legacy Displacement*, 5. 3. 2024. https://martinfowler.com/articles/patterns-legacy-displacement/ — přímý fetch. Zdroj úplného seznamu vzorů ve čtyřech skupinách.

`[7]` Cartwright, Horn, Lewis — *Event Interception*, 5. 3. 2024. https://martinfowler.com/bliki/EventInterception.html — přímý fetch.

`[8]` Cartwright, Horn, Lewis — *Legacy Mimic*, 12. 1. 2022. https://martinfowler.com/articles/patterns-legacy-displacement/legacy-mimic.html — přímý fetch.

`[9]` Cartwright, Horn, Lewis — *Transitional Architecture*, 28. 3. 2022. https://martinfowler.com/articles/patterns-legacy-displacement/transitional-architecture.html — přímý fetch.

`[10]` Cartwright, Horn, Lewis — *Divert the Flow*, 20. 1. 2022. https://martinfowler.com/articles/patterns-legacy-displacement/divert-the-flow.html — přímý fetch.

`[11]` Cartwright, Horn, Lewis — *Feature Parity*, 27. 7. 2021. https://martinfowler.com/articles/patterns-legacy-displacement/feature-parity.html — přímý fetch. Údaj o 50 % nepoužívaných funkcí je v článku připsán Standish Group (2014); primární report neověřen.

`[12]` Martin Fowler — *Branch By Abstraction*, 7. 1. 2014. https://martinfowler.com/bliki/BranchByAbstraction.html — přímý fetch. Atribuce Paulu Hammantovi, ten připisuje Stacy Curlovi.

`[13]` Danilo Sato — *Parallel Change*, 13. 5. 2014. https://martinfowler.com/bliki/ParallelChange.html — přímý fetch. Techniku dokumentoval Joshua Kerievsky (2006), prezentoval na LSSC 2010.

`[14]` Martin Fowler — *Legacy Seam*, 4. 1. 2024. https://martinfowler.com/bliki/LegacySeam.html — přímý fetch. Zdroj Feathersovy definice seamu.

`[15]` Pramod Sadalage, Martin Fowler — *Evolutionary Database Design*, leden 2003, přepsáno květen 2016. https://martinfowler.com/articles/evodb.html — přímý fetch.

`[16]` Martin Fowler — *Sacrificial Architecture*, 20. 10. 2014. https://martinfowler.com/bliki/SacrificialArchitecture.html — přímý fetch.

`[17]` Matthias Noback — *Refactoring without tests should be fine*, říjen 2022. https://matthiasnoback.nl/2022/10/refactoring-without-tests-should-be-fine/ — přímý fetch.

`[18]` Matthias Noback — *Successful refactoring projects – The Mikado Method*, únor 2021. https://matthiasnoback.nl/2021/02/refactoring-the-mikado-method/ — přímý fetch. Metoda: Ola Ellnestam, Daniel Brolund, *The Mikado Method*, Manning.

`[19]` Matthias Noback — *Successful refactoring projects – Prepare to stop at any time*, únor 2021. https://matthiasnoback.nl/2021/02/refactoring-prepare-to-stop/ — přímý fetch.

`[20]` Matthias Noback — *DDD entities and ORM entities*, duben 2022. https://matthiasnoback.nl/2022/04/ddd-entities-and-orm-entities/ — přímý fetch.

`[21]` Symfony — *Databases and the Doctrine ORM*, dokumentace verze 8.1. https://symfony.com/doc/current/doctrine.html — přímý fetch.

`[22]` Doctrine ORM — *Custom Mapping Types*. https://www.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html — přímý fetch; stránka se hlásí k verzi 4.0 (upcoming).

`[23]` Doctrine ORM — *Separating Concerns using Embeddables*. https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/embeddables.html — přímý fetch; verze 4.0 (upcoming).

`[24]` Doctrine ORM — *Limitations and Known Issues*. https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/limitations-and-known-issues.html — přímý fetch; verze 4.0 (upcoming).

`[25]` PHP RFC — *Readonly properties 2.0*. https://wiki.php.net/rfc/readonly_properties_v2 — přímý fetch. Zdroj tvrzení o Reflection a hydrataci bez konstruktoru.

`[26]` PHP Manual — *Properties* (sekce readonly). https://www.php.net/manual/en/language.oop5.properties.php — přímý fetch. Změny v 8.3 (reinicializace v `__clone`) a 8.4 (implicitní `protected(set)`).

`[27]` PHP Manual — *Lazy Objects*. https://www.php.net/manual/en/language.oop5.lazy-objects.php — přímý fetch. `newLazyGhost()` / `newLazyProxy()` od PHP 8.4.

### Neověřené / nedohledané

- **Přesné datum původního zápisu „Strangler Application“.** Kniha jinde uvádí „(Martin Fowler, 2004)“ (`microservices_and_ddd.md:786`). Současná stránka na martinfowler.com nese datum 22. 8. 2024 a původní datum neuvádí; `web.archive.org` je pro použitý nástroj nedostupné. Dohledat ručně přes archiv martinfowler.com nebo přes tištěné citace.
- **Datum přejmenování na „Strangler Fig Application“.** Fowler uvádí důvod, ne datum.
- **Standish Group report 2014, 50 % nepoužívaných funkcí.** Znám pouze zprostředkovaně z `[11]`. Primární zdroj nedohledán; často se cituje i starší číslo (45 %, 2002), takže před převzetím do knihy ověřit.
- **Doctrine ORM a PHP 8.4 native lazy objects.** Od které verze ORM 3.x se používají místo proxy podtříd a zda pak entity mohou být `final`. Release blog doctrine-project.org nebyl na zkoušených URL dostupný. Ověřit v CHANGELOG repozitáře `doctrine/orm`.
- **Doctrine a readonly properties v kombinaci s lazy loadingem.** RFC `[25]` potvrzuje bezpečnost hydratace bez konstruktoru, ale nic neříká o proxy, který identifikátor nastaví dopředu a poté je hydratován znovu. Ověřit v issue trackeru `doctrine/orm`.
- **Feature-flag řešení v Symfony ekosystému pro fázi cutover.** Kapitola cutover přes feature flag doporučuje (192–195), Symfony core komponentu nemá. Dohledat, který bundle kniha může doporučit.
- **Michael Feathers – aktuální texty.** Na `michaelfeathers.silvrback.com` byl přímým fetchem ověřen seznam příspěvků (nejnovější 2023); k seams ani k definici legacy code tam nic není. Primárním zdrojem zůstává kniha.
- **Kacper Gunia / Symfony Con přednášky o migraci legacy Symfony aplikací na DDD.** Bez `WebSearch` se nepodařilo dohledat konkrétní záznam.
