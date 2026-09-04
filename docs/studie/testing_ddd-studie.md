# Studie: Testování DDD kódu v Symfony

- **Kapitola:** `content/chapters/testing_ddd.md` (č. 17, kategorie Praxe, 1256 řádků)
- **Cesta:** /testovani-ddd
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

> Poznámka k rešerši: rozpočet `WebSearch` byl v této session vyčerpán (200/200). Veškeré
> zdroje níže byly získány přímým fetchem URL (WebFetch / curl na GitHub API a Packagist API).
> V bibliografii je způsob získání u každé položky uveden.

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| 17.01 Filozofie testování | 22–70 | Doména je čistý PHP, testuje se bez kernelu; testovací pyramida (Cohn 2009) ve třech vrstvách; tabulka „co testovat na které vrstvě“ | 1 odkaz na blog Mikea Cohna | Jediná citace v celé kapitole. Pyramida podána bez jakékoli kritiky. |
| 17.02 Unit testy domény | 71–333 | Tři testovací příklady: `Email` VO, `User` entita, `Order` agregát | žádné | Nejsilnější část. Kód odpovídá kanonickému API knihy (`Order::place()`, `Money(int, Currency)`, `releaseEvents()`). |
| 17.03 Doménové události | 334–467 | Pattern „Record and Verify Events“; vlastní trait `DomainEventAssertions` se třemi asserty | žádné | Chybí given-when-then nad streamem událostí, přestože na to `event_sourcing.md` dvakrát odkazuje. |
| 17.04 Test doubles + InMemory | 468–647 | Meszarosova taxonomie (5 typů); doporučení Fake > Mock pro repozitáře; `InMemoryUserRepository`; test handleru | zmínka *xUnit Test Patterns* bez bibliografického záznamu | Argumentace je věcná a v souladu s Fowlerem, ale nemá jediný odkaz. |
| 17.05 Integrační testy | 648–772 | KernelTestCase vs WebTestCase; `dama/doctrine-test-bundle`; `DoctrineUserRepositoryTest`; proč `clear()` | žádné | Chybí konfigurace bundle v `phpunit.dist.xml` a caveat se savepointy. |
| 17.06 Funkční testy | 773–897 | `WebTestCase`, `createClient()`, čtyři testy registračního endpointu | žádné | Používá jen `assertResponseStatusCodeSame()`; Symfony nabízí ~60 pojmenovaných assertů. |
| 17.07 Asynchronní toky | 898–1043 | `in-memory://` transport, `zenstruck/messenger-test`, test idempotence, test outboxu | žádné | Obsahuje věcnou chybu (viz G1). Jinak nejaktuálnější sekce kapitoly. |
| 17.08 Architektonické testy | 1044–1168 | Deptrac s `deptrac.yaml`, phparkitect jako alternativa | žádné | Konfigurace i atribuce Deptracu zastaraly (viz G2, G3). |
| 17.09 Coverage a postupy | 1169–1244 | Doporučené procento pokrytí per vrstva, naming conventions, AAA, seznam sedmi chyb, spouštěcí příkazy | žádné | Procenta jsou vymyšlená čísla bez opory. Chybí phpunit.dist.xml, který by testsuity vůbec definoval. |
| FAQ | 1246–1256 | 5 otázek | žádné | Konzistentní s tělem kapitoly. |

Kapitola je prakticky orientovaná a kódu dává výrazně víc prostoru než argumentaci: z 1256 řádků
připadá zhruba 800 na PHP a YAML ukázky. To je legitimní volba pro kapitolu v hubu Praxe, ale má
dvě viditelné oběti. Za prvé, kapitola nikde neuvádí verzi PHPUnit, proti které je psaná, a přitom
se PHPUnit mezi verzemi 10 a 13 změnil natolik, že část ekosystémových doporučení zestárla.
Za druhé, teoretické zázemí je odbyté — Meszaros, Fowler ani Vernon nejsou citováni, přestože
kapitola jejich rozlišení používá. Naopak úplně chybí témata, která k testování DDD standardně
patří: Object Mother / Test Data Builder, mutation testing, kontraktové testy mezi bounded
contexty a given-when-then testy pro event-sourced agregáty.

## 2. Kanonické zdroje k tématu

**Testovací pyramida.** Kapitola ji (řádek 41–42) připisuje Mikeovi Cohnovi a jeho knize
*Succeeding with Agile* (2009). To je správně, ale neúplně. Fowler v bliki hesle *TestPyramid*
[1] uvádí, že Cohn myšlenku rozvinul v rozhovorech s Lisou Crispin kolem roku 2003–4 a prezentoval
ji na scrum gatheringu v roce 2004; Jason Huggins ke stejnému konceptu dospěl nezávisle kolem 2006.
Fowler v témže hesle popisuje anti-vzor **ice-cream cone** — sadu, kde převažují pomalé testy přes
UI — a jmenuje tři důvody, proč se rozpadá: pomalost buildu, křehkost (drobná změna systému rozbije
mnoho testů) a nemožnost běhu v headless režimu v deployment pipeline.

**Test doubles.** Fowler v *Mocks Aren't Stubs* [2] (poprvé 2004, revize 2007) přebírá
Meszarosovu taxonomii z *xUnit Test Patterns* (Gerard Meszaros, Addison-Wesley, 2007) a definuje
pět typů. Dummy „jsou objekty, které se předávají, ale nikdy se nepoužijí". Fake „mají funkční
implementaci, ale používají zkratku, která je činí nevhodnými pro produkci". Stub „poskytuje
připravené odpovědi na volání v testu". Spy je „stub, který navíc zaznamenává informace o tom, jak
byl volán". Mock je „objekt předprogramovaný očekáváními, která tvoří specifikaci volání, jež má
obdržet". Klíčová věta, kterou kapitola nemá: **„Z těchto typů doubles pouze mocky trvají na
behavior verification."** Fowler zároveň staví proti sobě *state verification* (kontrola stavu po
akci) a *behavior verification* (kontrola, že proběhla správná volání spolupracovníků), a rozlišuje
classical TDD (reálné objekty, kde to jde) od mockist TDD (mock všude, kde je zajímavé chování).
Doporučení kapitoly „Fake > Mock pro repozitáře" (řádky 485–492) je tedy v podstatě volbou
classical TDD a mělo by se k Fowlerovi explicitně přihlásit.

**Praktická revize pyramidy.** Ham Vocke, *The Practical Test Pyramid* (martinfowler.com,
26. 2. 2018) [3], tvrdí, že Cohnovo pojmenování vrstev je „zjednodušující a proto zavádějící",
zejména termín „service test". Zavádí pojem **narrow integration test** — test, který ověřuje
jeden integrační bod, ne celý systém. Rozlišuje **solitary** testy (všichni spolupracovníci
nahrazeni doubly) od **sociable** testů (reální spolupracovníci) a odmítá, aby existoval jeden
správný poměr. Také jako samostatnou vrstvu zavádí **consumer-driven contract tests** (CDC) a
jmenuje Pact jako nástroj, kterým se realizují.

**Object Mother.** Fowler [4] (24. 10. 2006) uvádí, že termín vznikl „na projektu ThoughtWorks
na přelomu století" a že Peter Schuh a Stephanie Punke vzor následně publikovali pro konferenci
XP Universe. Fowler jmenuje dvě slabiny: silná vazba mnoha testů na přesná data fixtury a nutnost
migrovat všechny fixtury při změně tříd. Pozor na časté chybné podání: Fowler v tomto hesle
Test Data Builder **nejmenuje** jako alternativu — ta pochází od Nata Pryce (viz sekce 9,
neověřené) a je systematicky rozpracovaná v knize Freeman & Pryce, *Growing Object-Oriented
Software, Guided by Tests* (Addison-Wesley, 2009).

**Testing Trophy.** Kent C. Dodds (3. 6. 2021) [5] navrhuje alternativu k pyramidě se čtyřmi
patry: static, unit, integration, end to end, s největší investicí do integračních testů. Jeho
vodicí princip zní: „The more your tests resemble the way your software is used, the more
confidence they can give you." Trophy je formulována pro JavaScript a její přenos na DDD backend
není samozřejmý — právě proto je zajímavá jako protiklad k pyramidě, ne jako náhrada.

## 3. Stav praxe a posuny

**Anotace v PHPUnit skončily.** Toto je nejdůležitější posun pro celou kapitolu. PHPUnit 11.0
(2. 2. 2024) [6] deprecoval „support for metadata in doc-comments" a zároveň **odstranil** podporu
pro nestatické, neveřejné a parametrizované data providery. PHPUnit 12.0 (7. 2. 2025) [7] podporu
metadat v doc-comments **odstranil úplně** (issue #5541). Od PHPUnit 12 tedy `@dataProvider`,
`@covers`, `@test` a `@group` nefungují — jedinou cestou jsou atributy z namespace
`PHPUnit\Framework\Attributes`: `#[DataProvider]`, `#[DataProviderExternal]`, `#[TestWith]`,
`#[TestWithJson]`, `#[CoversClass]`, `#[UsesClass]`, `#[Group]`, `#[Test]`, `#[TestDox]`,
`#[Depends]`, `#[RequiresPhp]` [8]. PHPUnit 12 dále odstranil `getMockForAbstractClass()`,
`getMockForTrait()`, `createTestProxy()`, `MockBuilder::addMethods()` a možnost konfigurovat
`expects()` na test stubu.

**PHPUnit 13 je aktuální řada.** Vydán 6. 2. 2026, vyžaduje PHP ≥ 8.4 (odstranil podporu PHP 8.3),
poslední vydání v době psaní studie je 13.3.2 z 27. 8. 2026 [9][10]. Přinesl **sealed test doubles**
a konfigurační volbu vynucující je, dále `withParameterSetsInOrder()` / `withParameterSetsInAnyOrder()`
a rodinu `assertArraysAreIdentical*()`. Odstranil `Assert::isType()`, `assertContainsOnly()` a
`#[RunClassInSeparateProcess]`. **Tvrdě deprecoval matcher `any()`** — s doporučením použít místo
něj test stub nebo skutečné očekávání počtu volání; ve 13.0.2 přibyla deprecace `with*()` bez
`expects()` [11]. To se přímo dotýká stylu, který kapitola v sekci 17.04 popisuje.

**Odklon od mocků směrem k fakes.** Doporučení kapitoly (Fake pro repozitáře, mock jen pro vedlejší
efekty) je dnes většinovým názorem a shoduje se i s oficiální dokumentací Symfony: stránka
*Testing Doctrine Repositories* říká doslova, že „unit testing Doctrine repositories is not
recommended. Repositories are meant to be tested against a real database connection" [12].
Kapitola je tedy na správné straně, jen bez opory.

**Architektonické testy se rozšířily na tři nástroje.** Vedle Deptracu a PHPArkitectu existuje
třetí varianta: Pest 5 (PHP ≥ 8.4) má vestavěné `arch()` API včetně presetů `php`, `security`,
`strict` [13][14]. Zápis `arch('domain')->expect('App\Domain')->not->toUse('App\Infrastructure')`
je pro DDD projekt čitelnější než YAML a na rozdíl od obou předchozích nástrojů běží uvnitř
testovací sady. Kapitola Pest nezmiňuje vůbec.

**Mutation testing zdomácněl.** Infection 0.35.4 (2. 9. 2026, PHP ^8.3) [15] má přes 31 milionů
instalací. Pro doménovou vrstvu je to přesně ten nástroj, který zachraňuje metriku pokrytí před
kritikou, kterou kapitola sama vznáší na řádku 1171 („100% pokrytí lze dosáhnout testy, které jen
volají metody bez assertů"). Mutation score odpovídá na otázku, kterou line coverage zodpovědět
neumí. V celé knize se Infection nevyskytuje.

**Fixtury přes factory.** `zenstruck/foundry` 2.12.1 (26. 8. 2026, PHP ≥ 8.1) [16] je dnes
de facto standard pro tvorbu testovacích dat v Symfony a je to prakticky Test Data Builder
zabalený do Doctrine integrace (`PostFactory::new()->published()->create([...])`). V knize se
nevyskytuje ani on, ani samotné vzory Object Mother a Test Data Builder.

## 4. Symfony / PHP specifika

**Instalace.** Symfony doporučuje `composer require --dev symfony/test-pack`, který přitáhne
`phpunit/phpunit` a související balíčky. Dokumentace zároveň upozorňuje, že konfigurační soubor
se od PHPUnit 10 jmenuje `phpunit.dist.xml` (dříve `phpunit.xml.dist`) [17]. Kapitola v sekci 17.09
spouští `--testsuite=Domain`, `Integration` a `Functional`, ale samotný XML soubor, který tyto
testsuity definuje, nikde neukazuje — přitom je to nejpřenositelnější artefakt celé kapitoly.

**Doctrine izolace.** `dama/doctrine-test-bundle` 8.6.0 (21. 1. 2026, PHP ≥ 8.2) [18] podporuje
PHPUnit 11, 12 i 13. Zapíná se v `phpunit.dist.xml`:

```xml
<extensions>
    <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
</extensions>
```

Bundle drží statické DBAL spojení po celý PHP proces a po každém testu dělá rollback. Dva caveaty,
které kapitola neuvádí: pro DBAL < 4 je nutné `use_savepoints: true`, a bundle si neporadí s DDL
dotazy (ALTER/DROP TABLE), protože ty implicitně commitují transakci a rollback pak selže hláškou
o neexistujícím savepointu.

**Messenger v testech.** Symfony doporučuje pro testovací prostředí DSN `in-memory://` a
`InMemoryTransport` vystavuje `getSent()`, `getAcknowledged()` a `reset()`. Dokumentace explicitně
uvádí, že **všechny in-memory transporty se po každém testu automaticky resetují** v třídách
dědících z `KernelTestCase` nebo `WebTestCase` [19]. Transport přijímá volbu `serialize` (výchozí
`false`), kterou se dá otestovat i serializační vrstva. Kapitola resetování ani volbu `serialize`
nezmiňuje.

**zenstruck/messenger-test** 1.15.0 (2. 8. 2026) podporuje `symfony/messenger` ^6.4|^7.0|^8.0 [20].
Zásadní detail: trait `InteractsWithMessenger` **vyžaduje DSN `test://`**, nikoli `in-memory://`.
API je `$this->transport('async')->queue()->assertCount(n)`, `assertContains(Msg::class)`,
`assertEmpty()`, dále `process()` / `processOrFail()` a kolekce `dispatched()`, `acknowledged()`,
`rejected()`. DSN přijímá parametry `catch_exceptions`, `intercept`, `test_serialization`,
`support_delay_stamp`, `disable_retries` [21].

**Deptrac.** Balíček `qossmic/deptrac` je od verze 2.0.4 (21. 11. 2024) na Packagistu označen jako
**abandoned** s náhradou `deptrac/deptrac` [22]. Aktuální je Deptrac 4.7.1 (23. 7. 2026), vyžaduje
PHP ≥ 8.2 [23]. README řady 4.x říká: „This configuration file is written in YAML or php and, by
default, is stored with the name `deptrac.php`" [24]. YAML tedy dál funguje, ale výchozím
a dokumentovaným formátem je PHP:

```php
use Deptrac\Deptrac\Contract\Config\Collector\ClassLikeConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $config->paths('./src')
        ->layers($domain = Layer::withName('Domain')->collectors(ClassLikeConfig::create('.*Domain.*')))
        ->rulesets(Ruleset::forLayer($domain));
};
```

Příkaz `analyse` má alias `analyze` (definice: `name: 'analyse|analyze'`), takže zápis v kapitole
je platný. `vendor/bin/deptrac init` vygeneruje šablonu.

**PHPArkitect** 1.3.0 (31. 7. 2026, PHP ^8.0) [25]. Konfigurace v `phparkitect.php`, entry point
`Arkitect\CLI\Config`, `Arkitect\ClassSet::fromDir()`, pravidla přes
`Rule::allClasses()->that(...)->should(...)->because(...)`. Kapitola správně uvádí, že nástroj
nemá PHPUnit integraci. Co neuvádí a co je pro zavádění do existujícího projektu podstatné: nástroj
má **baseline** — `phparkitect generate-baseline` vytvoří `phparkitect-baseline.json`, `check` jej
automaticky použije, `prune-baseline` z něj odstraní opravená porušení. Bez baseline se
architektonické testy do brownfield projektu prakticky nedají nasadit. Deptrac má baseline
formatter s výstupem `deptrac.baseline.yaml`.

**Behat.** `behat/behat` 3.32.0 (20. 6. 2026, PHP >=8.2 <8.6), 4.0.0-alpha1 z 22. 6. 2026;
`friends-of-behat/symfony-extension` 2.7.0 (16. 7. 2026, PHP ^8.3) [26][27]. Pro DDD je Behat
relevantní tím, že scénáře v Gherkinu jsou přirozeným místem, kde se ubiquitous language dostane
do spustitelné podoby. V knize se nevyskytuje.

**Pact.** `pact-foundation/pact-php` je aktuálně ve verzi 11.0.0-alpha2 (12. 5. 2026, PHP ^8.2),
podporuje Pact specifikace 1 až 4, vyžaduje FFI [28]. Stabilní řada je starší; alfa stav je důvod
k opatrnosti při doporučování.

## 5. Sporné a chybně podávané body

**SQLite pro integrační testy.** Kapitola na řádku 652 nabízí „typicky SQLite in-memory pro
rychlost, nebo testovací instanci PostgreSQL/MySQL pro shodu s produkcí" jako rovnocenné volby.
Rovnocenné nejsou. SQLite se liší v typovém systému, chování unikátních constraintů, transakční
sémantice a v podpoře JSON a datových typů. Integrační test repozitáře má ověřit právě to mapování,
které se mezi enginy liší — pokud běží nad jiným enginem než produkce, ověřuje něco jiného.
Kapitola sama na řádcích 631–634 správně říká, že finální záruku unikátnosti dává constraint
v databázi; nad SQLite se tento test chová jinak než nad PostgreSQL. Doporučení pro knihu: nechat
SQLite jen jako výslovně označenou zkratku pro rychlou zpětnou vazbu lokálně a jako výchozí
doporučit stejný engine jako v produkci, spuštěný v kontejneru.

**Procenta pokrytí.** Řádky 1178–1184 uvádějí konkrétní čísla (90–100 %, 80–90 %, 60–80 %,
50–70 %) bez jakéhokoli zdroje. Žádný z kanonických zdrojů taková čísla nestanovuje, a kapitola
sama o dva odstavce výš správně tvrdí, že metrika o kvalitě testů nevypovídá. Tvrzení si tak
protiřečí. Serióznější formulace je kvalitativní: doména se testuje beze zbytku, protože tam je
logika; infrastruktura se testuje tam, kde je vlastní kód, ne kde je generovaný; číslo je signál,
ne cíl. Pokud kniha chce numerický orientační bod, musí ho označit jako konvenci autorů, ne jako
doporučení.

**„Suffix `Test` je nutný".** Řádek 1191 tvrdí, že PHPUnit třídu bez suffixu nespustí. Přesně
vzato jde o suffix **souboru** (výchozí `Test.php`) a je konfigurovatelný — jak CLI volbou
`--test-suffix`, tak atributem `suffix` na elementech `<directory>` v XML konfiguraci. Tvrzení
je tedy formulované jako tvrdé pravidlo, ačkoli jde o výchozí konvenci.

**Meszaros bez záznamu.** Řádek 471 odkazuje na *xUnit Test Patterns* kurzívou, bez autora, roku
a nakladatele. Podle pravidel knihy (CLAUDE.md, sekce Citace) to je bibliografický záznam, ne
odkaz. Zdroj patří doplnit: Gerard Meszaros, *xUnit Test Patterns: Refactoring Test Code*,
Addison-Wesley, 2007.

**Pyramida bez protistrany.** Kapitola podává pyramidu jako fakt. Vocke [3] i Dodds [5] ji
problematizují a Fowler [1] sám doplňuje ice-cream cone jako protipól. Kapitola v hubu Praxe
nemusí rozvíjet spor do šířky, ale jednoodstavcová zmínka, že poměr vrstev není univerzální
konstanta a že v systémech s tenkou doménovou logikou se těžiště přesouvá k integračním testům,
kapitole prospěje víc než další ukázka kódu.

**Rozpor s kapitolou CQRS.** Sekce 12.16 (`content/chapters/cqrs.md:1298`) testuje command handler
přesně tím stylem, který sekce 17.04 označuje za nevhodný: `$this->createMock(UserRepository::class)`
s `expects($this->once())->method('save')`, a navíc `$this->createMock(User::class)` — tedy mock
agregátu. Testovací kapitola přitom na řádcích 1214–1215 mockování doménových objektů uvádí mezi
nejčastějšími chybami. Ukázka v CQRS také vyhazuje `\DomainException` s textovou zprávou, zatímco
kanonická konvence knihy (CLAUDE.md) vyžaduje `DuplicateEmailException`. Obě kapitoly testují
tentýž `RegisterUserHandler` a čtenář dostane dvě protichůdná doporučení.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | zastaralé | `testing_ddd.md:911–922` a `947–976` | Sekce nastaví transport na `in-memory://` a hned nad ním používá API `zenstruck/messenger-test`. Ten ale vyžaduje DSN `test://`; nad `in-memory://` trait `InteractsWithMessenger` nefunguje. | Rozdělit na dvě konfigurace: `in-memory://` pro `getSent()` a `test://` pro zenstruck. |
| G2 | zastaralé | `testing_ddd.md:1053` | „Deptrac je nástroj od QOSSMIC (dříve sensiolabs-de)". Balíček `qossmic/deptrac` je od 11/2024 abandoned, projekt má vlastní organizaci `deptrac/deptrac`. | Přepsat atribuci na aktuální stav, zmínku o QOSSMIC ponechat maximálně jako historickou poznámku. |
| G3 | zastaralé | `testing_ddd.md:1058–1126` | Konfigurace v `deptrac.yaml`. Deptrac 4.x má jako výchozí `deptrac.php` s typovaným API (`DeptracConfig`, `Layer::withName()`, `Ruleset::forLayer()`). YAML sice funguje, ale dokumentace i `init` generují PHP. | Přepsat ukázku do PHP konfigurace, YAML zmínit jednou větou jako stále podporovaný. |
| G4 | chybí | celá kapitola | Kapitola neuvádí verzi PHPUnit. PHPUnit 12 odstranil metadata v doc-comments, PHPUnit 13 vyžaduje PHP 8.4. Čtenář nemá jak poznat, které API platí. | Do úvodu sekce 17.02 doplnit větu o cílové verzi (PHPUnit 13, PHP 8.4) a krátkou sekci o atributech. |
| G5 | chybí | sekce 17.02 | Nikde není `#[DataProvider]`, `#[TestWith]` ani `#[CoversClass]`. Testy VO jsou přitom učebnicový případ pro data provider (šest testů `Email` by byly dva). | Přepsat jeden test na `#[DataProvider]` se statickou metodou a doplnit poznámku, že anotace v PHPUnit 12+ nefungují. |
| G6 | chybí | sekce 17.03 | Chybí given-when-then nad streamem událostí, přestože `event_sourcing.md:125` i `:559–561` na testovací kapitolu kvůli tomuto vzoru **výslovně odkazují**. Dangling cross-reference. | Nová podsekce s testem event-sourced agregátu: `given(array $events)` → `reconstituteFromHistory()` → `when` → assert nad `releaseEvents()`. |
| G7 | chybí | sekce 17.02 / 17.04 | Object Mother a Test Data Builder nejsou v knize nikde. Kapitola přitom v každém testu opakuje `User::register($id, 'Jan Novák', $email, HashedPassword::fromPlainText('secret123'))`. | Nová podsekce: builder pro `Order` a `User`, zmínka Object Mother s Fowlerovou kritikou, odkaz na `zenstruck/foundry` pro persistované fixtury. |
| G8 | chybí | sekce 17.09 | Mutation testing (Infection) v knize není. Kapitola sama kritizuje line coverage, ale nenabízí metriku, která tu slabinu řeší. | Krátká podsekce na konci 17.09: co je mutation score, `composer require --dev infection/infection`, proč jej pouštět jen nad Domain. |
| G9 | chybí | sekce 17.09 | Není ukázán `phpunit.dist.xml`. Kapitola přitom spouští `--testsuite=Domain` a používá `dama/doctrine-test-bundle`, což obojí vyžaduje XML konfiguraci. | Doplnit `phpunit.dist.xml` se třemi testsuitami a registrací DAMA extension. Nejpřenositelnější artefakt kapitoly. |
| G10 | chybí | mimo kapitolu | Kontraktové testy mezi bounded contexty. Jediná zmínka Pactu v knize je buňka tabulky v `microservices_and_ddd.md:435`. | Přidat odstavec v 17.08 nebo v 19: consumer-driven contracts jako testovací protějšek Customer/Supplier vztahu z context mappingu. Pact-php je v alfě, uvést opatrně. |
| G11 | sporné | `testing_ddd.md:652` | SQLite in-memory a produkční engine podány jako rovnocenné varianty. | Přeformulovat: stejný engine jako produkce je výchozí volba, SQLite jen jako vědomá zkratka s uvedenými limity. |
| G12 | nepodložené | `testing_ddd.md:1178–1184` | Čtyři intervaly pokrytí bez zdroje, v rozporu s tvrzením o bezcennosti metriky o dva odstavce výš. | Buď zdroj, nebo přeformulovat kvalitativně a čísla označit jako konvenci autorů. |
| G13 | nepodložené | `testing_ddd.md:24–27`, `1241–1243` | „Běh tisíce testů trvá minuty… V DDD běží stejný počet testů v sekundách." Dvakrát opakované číslo bez měření. | Ponechat kvalitativní tvrzení (bez bootstrapu kernelu je test o řád rychlejší), vypustit konkrétní jednotky, nebo doplnit reálné měření. |
| G14 | mělké | `testing_ddd.md:468–483` | Meszarosova taxonomie odbytá jedním bullet listem, bez bibliografického záznamu a bez Fowlerova rozlišení state vs. behavior verification, které je pro doporučení „Fake > Mock" jádrem argumentu. | Doplnit záznam Meszarose, odkaz na Fowlera a dvě věty o state vs. behavior verification. |
| G15 | sporné | `testing_ddd.md:39–70` vs. `cqrs.md:1298–1434` | Sekce 12.16 testuje `RegisterUserHandler` mocky včetně `createMock(User::class)` a s `\DomainException` — přímý rozpor s 17.04 a s kanonickými výjimkami knihy. | Sjednotit: 12.16 přepsat na `InMemoryUserRepository` a `DuplicateEmailException`, nebo 12.16 zkrátit na odkaz do kapitoly 17. |
| G16 | mělké | `testing_ddd.md:1144–1167` | PHPArkitect podán jen jako „alternativa s PHP API". Chybí baseline, bez které se nástroj do existujícího projektu nenasadí. Chybí i Pest `arch()` jako třetí varianta. | Doplnit `generate-baseline` / `prune-baseline` (platí i pro Deptrac) a jednu větu o Pest arch testech. |
| G17 | mělké | `testing_ddd.md:669–677` | `dama/doctrine-test-bundle` popsán slovně, bez konfigurace a bez limitů (savepointy, DDL dotazy, `use_savepoints` pro DBAL < 4). | Doplnit XML snippet a callout typu warn s oběma limity. |
| G18 | mělké | `testing_ddd.md:773–897` | Funkční testy používají jen `assertResponseStatusCodeSame()`. Symfony nabízí desítky pojmenovaných assertů (`assertResponseIsUnprocessable()`, `assertJsonContains()` v API Platform, mailer a messenger asserty). | Doplnit odkaz na referenci assertů a použít alespoň `assertResponseIsUnprocessable()` v testu na 422. |
| G19 | chybí | sekce 17.01 | Pyramida bez protistrany. Ice-cream cone, Vockeho narrow integration testy a Doddsova Testing Trophy nejsou zmíněny. | Jeden odstavec za popisem pyramidy: poměr vrstev není konstanta, závisí na tloušťce doménové logiky. |
| G20 | chybí | sekce 17.05 | Chybí zmínka o `zenstruck/foundry`, dnešním standardu pro fixtury v Symfony. Kapitola staví testovací data ručně v každém testu. | Odstavec v 17.05 nebo v nové podsekci o testovacích datech (souvisí s G7). |

## 7. Doporučení k přepisu

**P1-1 — Opravit konflikt `in-memory://` vs. `test://` v sekci 17.07.**
Aktuální text čtenáře navede na konfiguraci, se kterou následující ukázka nefunguje. Je to jediná
věcná chyba v kapitole, kterou čtenář odhalí až spuštěním. Oprava je malá, ale nutná.
`oprava konfiguračního snippetu + dvě věty` (~10 řádků).

**P1-2 — Deklarovat cílovou verzi PHPUnit a přejít na atributy.**
Kapitola je psaná v API, které je verzově neurčené. PHPUnit 12 odstranil doc-comment metadata,
PHPUnit 13 vyžaduje PHP 8.4 — což je verze, na kterou kniha cílí. Bez této informace čtenář neví,
zda `@dataProvider` z jiného tutoriálu bude fungovat (nebude). Zároveň se tím otevře prostor pro
data providery, které kapitole chybí. `nová podsekce v 17.02 ~35 řádků + přepis jednoho testu`.

**P1-3 — Aktualizovat sekci Deptrac (atribuce, balíček, formát konfigurace).**
Tři samostatné zastaralosti v jedné sekci: mrtvý balíček `qossmic/deptrac`, neaktuální atribuce
a YAML místo výchozího `deptrac.php`. Architektonické testy jsou přitom pro DDD kapitolu nosné
téma a čtenář podle nich bude konfigurovat CI. `přepis sekce 17.08 ~60 řádků`.

**P1-4 — Doplnit given-when-then testy pro event-sourced agregát.**
Kapitola Event Sourcing na tuto kapitolu odkazuje dvakrát (`event_sourcing.md:125`, `:559–561`)
kvůli obsahu, který zde není. Odkaz je slib, který kniha neplní, a čtenář, který na něj klikne,
skončí u testu `releaseEvents()` nad state-based agregátem. `nová podsekce 17.03 ~70 řádků`.

**P1-5 — Sjednotit testovací styl s kapitolou CQRS.**
Sekce 12.16 a sekce 17.04 dávají na tentýž handler protichůdná doporučení a 12.16 navíc porušuje
kanonické výjimky knihy. Rozpor mezi dvěma kapitolami je horší než chybějící téma, protože podrývá
autoritu obou. Nejlevnější řešení: 12.16 zkrátit na krátký odkaz do kapitoly 17.
`přepis sekce 12.16 v cqrs.md, ~80 řádků ubere`.

**P2-1 — Nová podsekce o testovacích datech: Object Mother, Test Data Builder, Foundry.**
Každý test v kapitole staví agregáty ručně a opakuje stejné argumenty. To je přesně problém,
kvůli kterému oba vzory vznikly. Podsekce také uzavře mezeru mezi doménovými unit testy a
persistovanými fixturami pro integrační testy. `nová podsekce ~80 řádků`.

**P2-2 — Doplnit `phpunit.dist.xml`.**
Kapitola pracuje se třemi testsuitami a s PHPUnit extension, ale konfiguraci neukazuje. Čtenář si
z kapitoly nedokáže sestavit funkční projekt. `nová ukázka ~40 řádků v 17.09 nebo 17.05`.

**P2-3 — Opravit tvrzení o SQLite a o procentech pokrytí.**
Dvě nepodložená místa, u kterých si kapitola protiřečí sama se sebou. Zásah je textový, ne
strukturální. `oprava dvou odstavců`.

**P2-4 — Doplnit limity `dama/doctrine-test-bundle` a jeho konfiguraci.**
Bundle je doporučen bez konfiguračního snippetu a bez zmínky o DDL dotazech a savepointech. To
jsou přesně věci, na kterých integrační sada spadne po pár týdnech. `callout + XML snippet ~25 řádků`.

**P2-5 — Doplnit baseline pro architektonické testy.**
Bez baseline se Deptrac ani PHPArkitect nedají zavést do existujícího projektu, což je zdaleka
nejčastější situace čtenáře této kapitoly. `odstavec + dva příkazy ~15 řádků`.

**P3-1 — Kritika pyramidy jedním odstavcem.**
Ice-cream cone, narrow integration tests, Testing Trophy. Nepředělávat sekci 17.01, jen doplnit,
že poměr vrstev závisí na tom, kolik logiky doména skutečně nese. `~12 řádků`.

**P3-2 — Mutation testing (Infection).**
Přirozený závěr sekce 17.09, která už kritiku coverage obsahuje. Držet krátce, jde o odkaz na
nástroj, ne o tutoriál. `~20 řádků`.

**P3-3 — Kontraktové testy mezi bounded contexty.**
Testovací protějšek Customer/Supplier a Published Language z kapitoly Context Mapping. Vzhledem
k alfa stavu `pact-foundation/pact-php` doporučuji koncept popsat a nástroj jen jmenovat.
`~20 řádků, případně spíš do kapitoly 19`.

**P3-4 — Zmínit Pest `arch()` a Behat.**
Jednovětné odkazy na existenci alternativ, bez ukázek. Kapitola tím získá úplnost přehledu bez
nafouknutí. `~8 řádků`.

## 8. Otevřené otázky pro autora

1. **Cílová verze PHPUnit.** Repozitář knihy sám vyžaduje `phpunit/phpunit: ^13.2` (composer.json:76).
   Má se kapitola explicitně přihlásit k PHPUnit 13 (a tedy PHP 8.4), nebo držet formulace, které
   platí pro 12 i 13? Rozhodnutí ovlivňuje, zda se v ukázkách objeví sealed test doubles a zda se
   dá používat matcher `any()`.
2. **Kolik prostoru event-sourced testům.** Kapitola 13 má vlastních 1700+ řádků. Má být
   given-when-then plnohodnotná podsekce v kapitole 17, nebo krátká ukázka s odkazem do kapitoly 13?
   Aktuální stav (slib bez plnění) není udržitelný v žádné variantě.
3. **Osud sekce 12.16.** Zkrátit ji na odkaz, nebo ponechat a jen přepsat na InMemory repozitář?
   Zkrácení zbaví kapitolu CQRS ~130 řádků, ale ta se tím stane závislejší na pořadí čtení.
4. **Kontraktové testy: kapitola 17, nebo 19?** Tematicky patří k integraci mezi bounded contexty
   (kapitola 19), technicky jde o testy (kapitola 17). Duplicita by byla horší než rozhodnutí.
5. **Object Mother vs. Test Data Builder — mít oba, nebo jen jeden?** Kniha nikde vzory nezmiňuje.
   Doporučení této studie je ukázat builder a Object Mother jen pojmenovat s Fowlerovou kritikou,
   ale je to volba rozsahu.
6. **Behat.** Otevřít BDD jako téma znamená otevřít otázku, kdo scénáře píše a jestli je doména
   dost stabilní, aby se vyplatily. Je to spíš téma na kapitolu o Ubiquitous Language než na
   testovací kapitolu — patří sem vůbec?
7. **Numerická doporučení pokrytí.** Vypustit úplně, nebo ponechat s explicitním označením
   „konvence této knihy"? Čtenáři čísla chtějí, ale kapitola je nemá čím podložit.

## 9. Bibliografie

### Ověřené zdroje

Všechny položky získány **přímým fetchem URL** (WebFetch nebo curl na GitHub API / Packagist API);
`WebSearch` nebyl v této session k dispozici. Datum přístupu u všech webových zdrojů: 2026-09-03.

[1] Martin Fowler — *TestPyramid*, bliki, publikováno 1. 5. 2012, revize do 11/2017. https://martinfowler.com/bliki/TestPyramid.html — přímý fetch

[2] Martin Fowler — *Mocks Aren't Stubs*, publikováno 8. 7. 2004, revize 2. 1. 2007. https://martinfowler.com/articles/mocksArentStubs.html — přímý fetch

[3] Ham Vocke — *The Practical Test Pyramid*, martinfowler.com, 26. 2. 2018. https://martinfowler.com/articles/practical-test-pyramid.html — přímý fetch

[4] Martin Fowler — *ObjectMother*, bliki, 24. 10. 2006. https://martinfowler.com/bliki/ObjectMother.html — přímý fetch

[5] Kent C. Dodds — *The Testing Trophy and Testing Classifications*, 3. 6. 2021. https://kentcdodds.com/blog/the-testing-trophy-and-testing-classifications — přímý fetch

[6] PHPUnit — *ChangeLog 11.0*, vydání 11.0.0 dne 2. 2. 2024 (odstranění nestatických data providerů, deprecace metadat v doc-comments). https://raw.githubusercontent.com/sebastianbergmann/phpunit/11.0.0/ChangeLog-11.0.md — přímý fetch (curl)

[7] PHPUnit — *ChangeLog 12.0*, vydání 12.0.0 dne 7. 2. 2025 (odstranění „Support for metadata in doc-comments", issue #5541). https://raw.githubusercontent.com/sebastianbergmann/phpunit/12.0.0/ChangeLog-12.0.md — přímý fetch (curl)

[8] PHPUnit dokumentace 12.4 — *Attributes*. https://docs.phpunit.de/en/12.4/attributes.html — přímý fetch

[9] PHPUnit — *Supported Versions* (PHPUnit 12: 7. 2. 2025, PHP ≥ 8.3; PHPUnit 13: 6. 2. 2026, PHP ≥ 8.4). https://phpunit.de/supported-versions.html — přímý fetch

[10] PHPUnit — seznam vydání, tag 13.3.2 publikován 27. 8. 2026. https://api.github.com/repos/sebastianbergmann/phpunit/releases — přímý fetch (curl)

[11] PHPUnit — *ChangeLog 13.0*, vydání 13.0.0 dne 6. 2. 2026, a *DEPRECATIONS.md* (`TestCase::any()` deprecováno v 12.5.5; `with*()` bez `expects()` v 13.0.2). https://raw.githubusercontent.com/sebastianbergmann/phpunit/13.0.0/ChangeLog-13.0.md, https://raw.githubusercontent.com/sebastianbergmann/phpunit/main/DEPRECATIONS.md — přímý fetch (curl)

[12] Symfony dokumentace — *Testing Doctrine Repositories*. https://symfony.com/doc/current/testing/database.html — přímý fetch

[13] Pest — *Installation* (Pest 5, PHP 8.4+). https://pestphp.com/docs/installation — přímý fetch

[14] Pest — *Architecture Testing* (`arch()`, `toOnlyUse()`, `toUseNothing()`, presety). https://pestphp.com/docs/arch-testing — přímý fetch

[15] Packagist — `infection/infection` 0.35.4, 2. 9. 2026, PHP ^8.3. https://packagist.org/packages/infection/infection — přímý fetch

[16] Packagist — `zenstruck/foundry` 2.12.1, 26. 8. 2026, PHP >=8.1. https://packagist.org/packages/zenstruck/foundry — přímý fetch

[17] Symfony dokumentace — *Testing* (Symfony 8.1; `symfony/test-pack`, `phpunit.dist.xml`, KernelTestCase, WebTestCase, reference assertů). https://symfony.com/doc/current/testing.html — přímý fetch

[18] `dmaicher/doctrine-test-bundle` README + Packagist (`dama/doctrine-test-bundle` 8.6.0, 21. 1. 2026, PHP ≥ 8.2; PHPUnit 11/12/13; `use_savepoints` pro DBAL < 4; limit u DDL dotazů). https://github.com/dmaicher/doctrine-test-bundle, https://repo.packagist.org/p2/dama/doctrine-test-bundle.json — přímý fetch

[19] Symfony dokumentace — *Messenger*, sekce Testing (`in-memory://`, `getSent()`, `getAcknowledged()`, `reset()`, volba `serialize`, automatický reset po testu). https://symfony.com/doc/current/messenger.html — přímý fetch

[20] Packagist API — `zenstruck/messenger-test` 1.15.0, 2. 8. 2026, `symfony/messenger` ^6.4|^7.0|^8.0. https://repo.packagist.org/p2/zenstruck/messenger-test.json — přímý fetch (curl)

[21] `zenstruck/messenger-test` README (`InteractsWithMessenger`, DSN `test://`, `queue()`, `process()`, `dispatched()`, `acknowledged()`, `rejected()`). https://github.com/zenstruck/messenger-test — přímý fetch

[22] Packagist — `qossmic/deptrac`, označeno jako abandoned, náhrada `deptrac/deptrac`, poslední verze 2.0.4 z 21. 11. 2024. https://packagist.org/packages/qossmic/deptrac — přímý fetch

[23] Packagist API — `deptrac/deptrac` 4.7.1, 23. 7. 2026, PHP ^8.2. https://repo.packagist.org/p2/deptrac/deptrac.json — přímý fetch (curl)

[24] Deptrac — README a `docs/index.md` větve 4.x (výchozí `deptrac.php`, YAML stále podporován, PHP ≥ 8.2, `init`, PHP config API `DeptracConfig` / `Layer` / `Ruleset` / `ClassLikeConfig`); `AnalyseCommand.php` (`name: 'analyse|analyze'`). https://raw.githubusercontent.com/deptrac/deptrac/4.x/README.md, https://raw.githubusercontent.com/deptrac/deptrac/4.x/docs/index.md — přímý fetch (curl)

[25] PHPArkitect — README a Packagist API (1.3.0, 31. 7. 2026, PHP ^8.0; `phparkitect.php`, `Arkitect\CLI\Config`, `ClassSet::fromDir()`, `Rule::allClasses()`, baseline příkazy). https://raw.githubusercontent.com/phparkitect/arkitect/main/README.md, https://repo.packagist.org/p2/phparkitect/phparkitect.json — přímý fetch (curl)

[26] Packagist API — `behat/behat` 3.32.0 (20. 6. 2026) a 4.0.0-alpha1 (22. 6. 2026), PHP >=8.2 <8.6. https://repo.packagist.org/p2/behat/behat.json — přímý fetch (curl)

[27] Packagist API — `friends-of-behat/symfony-extension` 2.7.0, 16. 7. 2026, PHP ^8.3. https://repo.packagist.org/p2/friends-of-behat/symfony-extension.json — přímý fetch (curl)

[28] Packagist — `pact-foundation/pact-php` 11.0.0-alpha2, 12. 5. 2026, PHP ^8.2, Pact spec 1–4. https://packagist.org/packages/pact-foundation/pact-php — přímý fetch

[29] Gerard Meszaros — *xUnit Test Patterns: Refactoring Test Code*, Addison-Wesley, 2007. Bibliografický záznam; taxonomie test doubles ověřena nepřímo přes Fowlera [2].

[30] Steve Freeman, Nat Pryce — *Growing Object-Oriented Software, Guided by Tests*, Addison-Wesley, 2009. Bibliografický záznam; zdroj vzoru Test Data Builder.

[31] Mike Cohn — *Succeeding with Agile: Software Development Using Scrum*, Addison-Wesley, 2009. Bibliografický záznam; atribuce testovací pyramidy ověřena přes Fowlera [1].

### Neověřené / nedohledané

- **Test Data Builder – OVĚŘENO 2026-09-04 z tištěného GOOS, s. 258 (vlastní výtisk). Vzor lze
  citovat doslovně a nemusí se opírat o nedostupný blogpost.** Freeman a Pryce ho popisují takto:

  > *„For a class that requires complex setup, we create a test data builder that has a field for
  > each constructor parameter, initialized to a safe value. The builder has ‚chainable‘ public
  > methods for overwriting the values in its fields and, by convention, a build() method that is
  > called last to create a new instance of the target object from the field values.“*

  Volitelné rozšíření: statická factory metoda samotného builderu, aby bylo v testu zřetelnější,
  co se staví. **Ukázka v knize je `OrderBuilder` s metodou `anOrder()`** – tedy přesně ten
  příklad, na kterém stojí kanonické ukázky této knihy, takže se dá převzít bez překladu do jiné
  domény.

  Autoři uvádějí tři důvody, proč builder použít: obalí syntaktický šum při vytváření objektů,
  udrží výchozí případ jednoduchý a zvláštní případy jen o málo složitější, a ochrání test před
  změnou struktury objektu – po přidání parametru konstruktoru se mění jen builder.

  **Doporučení: citovat GOOS, s. 258.** Pryceův blogpost z roku 2007 uvádět jen jako první
  publikaci vzoru; jeho doména je nedostupná a pro obsah ho není potřeba.
- **Schuh & Punke, Object Mother – DOHLEDÁNO 2026-09-04 v bibliografii GOOS (vlastní výtisk).**
  Přesný záznam zní: *„[Schuh01] Schuh, Peter and Stephanie Punke. ObjectMother: Easing Test
  Object Creation In XP. XP Universe, 2001.“* Rok 2001 se tedy potvrdil, stejně jako místo vydání
  (konference XP Universe); doplněn je přesný název včetně podtitulu. Citace je tím uzavřená.
- **Symfony 8 a testovací API.** Dokumentace na symfony.com/doc/current byla v době rešerše
  označena jako Symfony 8.1. Nepodařilo se ověřit, zda mezi Symfony 7 a 8 došlo k BC změnám
  v `KernelTestCase` / `WebTestCase` — před přepisem kapitoly by to chtělo projít UPGRADE-8.0.md
  frameworku.
- **Reálná čísla o rychlosti testovacích sad** (tvrzení G13). Žádný dohledatelný benchmark
  srovnávající běh unit testů domény proti testům s bootstrapem kernelu. Pokud kniha chce číslo
  uvést, musí ho změřit na vlastním referenčním projektu.
- **Doctrine ORM 3 a testování.** Nebylo ověřeno, zda ORM 3 mění chování `EntityManager::clear()`
  popsané v sekci 17.05 (řádky 765–771). Před přepisem projít dokumentaci ORM 3.
- **`dama/doctrine-test-bundle` a DBAL 4 – OVĚŘENO 2026-09-04: `use_savepoints` se na DBAL 4
  nastavovat nemá.** README bundlu to říká přímo: *„Starting from version 8 **and only when using
  DBAL < 4** you need to make sure you have `use_savepoints` enabled on your doctrine DBAL
  configuration for all relevant connections.“* Kniha cílí na Doctrine ORM 3 s DBAL 4, takže
  ta volba je pro její stack zbytečná a v ukázce konfigurace nemá co dělat.

  Kompatibilita ověřena z `composer.json` verze **v8.6.0 (21. 1. 2026)**: PHP >= 8.2,
  `doctrine/dbal ^3.3 || ^4.0`, `doctrine/doctrine-bundle ^2.11.0 || ^3.0`,
  `symfony/framework-bundle ^6.4 || ^7.3 || ^8.0`. Bundle je se Symfony 8 a DoctrineBundle 3
  plně kompatibilní.

  jako nutné pro DBAL < 4; co přesně platí pro DBAL 4 a Doctrine ORM 3, nebylo z README
  jednoznačné.
