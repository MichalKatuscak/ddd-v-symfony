---
route: migration_from_crud
path: /migrace-z-crud
title: Migrace z CRUD architektury na DDD
page_title: "Migrace z CRUD architektury na DDD v Symfony | DDD Symfony"
meta_description: "Postupná migrace z CRUD na DDD v Symfony 8: Strangler Fig Pattern, extrakce doménové vrstvy, zavedení repozitářů a CQRS bez velké revoluce."
meta_keywords: "migrace CRUD DDD, Strangler Fig Pattern, refaktorizace na DDD, extrakce doménové vrstvy, value objects, repozitáře DDD, CQRS migrace, charakterizační testy, Symfony DDD migrace"
og_type: article
published: "2025-04-24"
modified: "2026-07-08"
breadcrumb_name: Migrace z CRUD
schema_type: TechArticle
schema_headline: "Migrace z CRUD architektury na DDD v Symfony"
chapter_number: "18"
category: Praxe
deck: "Podrobný průvodce migrací z CRUD architektury na Domain-Driven Design v Symfony. Strangler Fig Pattern, extrakce doménové vrstvy, zavedení repozitářů a postupné zavedení CQRS s praktickými PHP příklady."
reading_time: 33
difficulty: 3
github_examples: Chapter09_Migration
---

## 18.01 Kdy a proč migrovat z CRUD na DDD {#kdy-migrovat}

CRUD architektura (Create, Read, Update, Delete) je výchozí volba pro většinu aplikací a dlouho stačí.
Pro správu dat bez komplexní logiky – záznamy kontaktů, katalogy produktů, administrační rozhraní –
CRUD odvede práci a vrstvy DDD by byly zbytečnou zátěží.
Problém přijde, když aplikace přeroste do větší komplexity a doménová logika proniká
na nevhodná místa.

:::callout{type="note"}
### Příznaky, že CRUD architektura nestačí {#priznaky-heading}

- **God Services (Boží služby)** – Třídy jako `UserService` nebo `OrderService` mají stovky řádků a soustřeďují celou doménovou logiku. Každá nová funkce vede do stejné třídy a riziko regresí roste.
- **Fat Controllers (Tlusté kontrolery)** – Symfony kontrolery přestaly být tenkou vrstvou pro HTTP adaptaci. Místo toho přímo implementují doménová pravidla: validaci, výpočty, přechody stavů. Kontroler má delegovat na doménový model, nikoli ho suplovat.
- **Doménová logika v repozitářích** – Doctrine repozitáře obsahují komplexní podmínky, které vyjadřují doménová pravidla (např. „objednávky, které je možné zrušit“). Tato logika patří do doménového modelu, nikoli do databázové vrstvy.
- **Překrývání zodpovědností** – Není jasné, zda konkrétní pravidlo patří do kontroleru, service nebo repozitáře. Tým nemá sdílené chápání, kde co hledat.
- **Nízká testovatelnost** – Doménová logika je neoddělitelně svázána s HTTP vrstvou nebo databází. Napsání unit testu pro doménové pravidlo vyžaduje rozsáhlé mockování.
- Vývojáři a doménoví experti používají jiný slovník – **komunikační propast**. Kód neodráží doménový jazyk; pojmy jako „aktivace účtu“ nebo „storno objednávky“ nejsou viditelné v názvech tříd a metod.
:::

### Kdy DDD přináší hodnotu a kdy je CRUD dostačující

Rozhodnutí o migraci stojí na analýze komplexity domény, ne na trendech.
Martin Fowler ve své práci o architektonických vzorech ukazuje, že Transaction Script
a CRUD jsou legitimní volbou pro aplikace s jednoduchými doménovými pravidly
[[1]](https://martinfowler.com/eaaCatalog/transactionScript.html).

:::callout{type="note"}
### Kdy DDD přináší hodnotu {#kdy-ddd-heading}

- Doména obsahuje komplexní pravidla, která se často mění.
- Existují přechody stavů entit (objednávka: vytvořena → potvrzena → odeslána → doručena).
- Tým komunikuje s doménovými experty a potřebuje sdílený jazyk.
- Aplikace je dlouhodobě rozvíjena a musí být udržovatelná v horizontu let.
- Existuje více Bounded Contexts s odlišnými pohledy na stejné entity.

### Kdy zůstat u CRUD

- Aplikace zůstává CRUD nad databázovými tabulkami bez doménové logiky.
- Doménová pravidla jsou triviální a stabilní.
- Tým je malý a čas na migraci není dostupný.
- Aplikace je krátkodobá nebo se bude v blízké budoucnosti kompletně přepisovat.
:::

### Ekonomika migrace: co stojí a kdy se vrátí

Migrace z CRUD na DDD trvá měsíce až roky podle velikosti kódové základny. Je to dlouhý
proces, ne jednorázová akce, a zákazníkovi sama o sobě nic nepřinese. Užitek přijde až
s tím, jak tým začne přidávat funkce s menším rizikem regresí.

Inkrementální migrace středně velké CRUD aplikace (50–100 tabulek, 3–5 let vývoje) zabere
zpravidla 12 až 24 měsíců. To číslo je zkušenostní řádový odhad, ne měření. Počítá s tím,
že migrace běží souběžně s vývojem nových funkcí a nemá dedikovaný tým na plný úvazek.
Dobu prodlužuje špatná testovatelnost stávajícího kódu, slabá znalost domény v týmu
a nedostupnost doménových expertů.

Rozhodovací kritérium přitom není technické. Investice se vrátí jen tehdy, když přínos za
zbývající životnost systému převýší cenu migrace s rezervou. Nákladový model i situace, kdy
se investice nikdy nedoběhne, rozebírá sekce
[Migration cost paradox](/kdy-nepouzivat-ddd#migration-paradox-heading).
Management přijme migraci snáz, když probíhá inkrementálně souběžně s vývojem nových funkcí,
ne jako izolovaný refaktoringový projekt.

### Kdy migraci nezačínat a kdy je přepis levnější {#kdy-nezacinat}

Tři situace mluví proti tomu pouštět se do migrace vůbec:

- Systému zbývá kratší životnost než samotná migrace. Aplikace, která má za dva roky skončit,
  investici nesplatí.
- Doména se ruší nebo přechází na koupené řešení. Modelovat pravidla, která za rok zaniknou,
  je práce do koše.
- V dosahu týmu není nikdo, kdo doméně rozumí. Bez doménového experta vznikne přejmenovaný
  CRUD, ne doménový model. Tuto past popisuje kapitola
  [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd#pseudo-ddd-cargo-cult-heading).

Opačný pól je stejně reálný. Přepis od nuly vyjde levněji tam, kde je systém menší než
náklad na zavedení švů a charakterizačních testů, nebo kde ještě nejsou produkční uživatelé
a data. Martin Fowler pro to má jméno Sacrificial Architecture
[[6]](https://martinfowler.com/bliki/SacrificialArchitecture.html): architektura navržená
s tím, že se za pár let zahodí, je legitimní volba. Podstatná je podmínka, která k tomu patří.
Rozhodnout o zahození má tým, který systém napsal – ne někdo, kdo přichází zvenčí se slibem,
že to udělá lépe.

## 18.02 Strangler Fig Pattern – vzor postupné náhrady {#strangler-fig}

Strangler Fig Pattern (vzor fíkovníku škrtiče) pojmenoval Martin Fowler
[[2]](https://martinfowler.com/bliki/StranglerFigApplication.html).
Vzor nahrazuje starý systém po částech, bez „big bang“ přepisu. Název pochází
od tropického fíkovníku, který roste kolem hostitelského stromu a postupně ho zardousí.

Původní zápis vyšel 29. června 2004 pod názvem *Strangler Application*. K 29. dubnu 2019
Fowler vzor přejmenoval na *Strangler Fig Application*: zkrácené „strangler“ se odtrhlo od
botanické metafory a začalo vyznívat násilně. Starší název proto potkáte v článcích
i v názvech knihoven dodnes.

:::diagram{fig="18.2-A" title="Strangler Fig: čtyři fáze migrace CRUD → DDD" src="images/diagrams/19_migration_from_crud/strangler_fig.svg"}
:::

:::callout{type="note"}
### Princip fungování {#strangler-princip-heading}

1. **Nová funkcionalita** je vždy implementována v DDD stylu – nové Bounded Contexts, doménové objekty, repozitáře.
2. **Stará funkcionalita** zůstává v CRUD podobě a je postupně nahrazována při refaktoringu nebo při úpravách stávajících funkcí.
3. **Koexistence** – obě části systému fungují paralelně a jsou propojeny přes Anti-Corruption Layer nebo sdílenou databázi.
4. **Postupná eliminace** – s každou iterací se CRUD část zmenšuje a DDD část roste, dokud starý kód nevymizí.
:::

:::callout{type="pattern"}
### Příklad: Koexistence CRUD a DDD ve struktuře projektu {#strangler-struktura-heading}

:::code{language="bash" filename="snippet.sh"}
src/
├── Controller/                    # Stará CRUD vrstva (postupně se zmenšuje)
│   ├── UserController.php         # Původní CRUD kontroler
│   └── OrderController.php        # Původní CRUD kontroler
│
├── Service/                       # Stará service vrstva (God Services)
│   ├── UserService.php            # Bude nahrazena DDD vrstvou
│   └── OrderService.php           # Bude nahrazena DDD vrstvou
│
├── Entity/                        # Doctrine entity (sdílené nebo duplikované)
│   ├── User.php
│   └── Order.php
│
└── UserManagement/                # Nová DDD vrstva (postupně roste)
    ├── Domain/
    │   ├── Model/
    │   │   ├── User.php           # Doménová entita (ne Doctrine entita)
    │   │   └── Email.php          # Value Object
    │   ├── Repository/
    │   │   └── UserRepository.php # Doménové rozhraní
    │   └── Event/
    │       └── UserRegistered.php # Domain Event
    ├── Application/
    │   ├── Command/
    │   │   ├── RegisterUser.php
    │   │   └── RegisterUserHandler.php
    │   └── Query/
    │       ├── GetUserProfile.php
    │       └── GetUserProfileHandler.php
    └── Infrastructure/
        └── Repository/
            └── DoctrineUserRepository.php  # Implementace repozitáře
:::
:::

### Výhody oproti přímé refaktorizaci (Big Bang Rewrite)

Přepsat celý systém najednou (tzv. „big bang rewrite“) je jedno z největších rizik
v softwarovém vývoji. Joel Spolsky ve svém článku „Things You Should Never Do“
[[3]](https://www.joelonsoftware.com/2000/04/06/things-you-should-never-do-part-i/)
popisuje, proč firmy ztratily konkurenční výhodu tím, že kompletně přepsaly fungující systémy.
Strangler Fig Pattern oproti tomu:

- Umožňuje kontinuální dodávku nové hodnoty zákazníkovi i během migrace.
- Snižuje riziko – systém nikdy není kompletně „rozbitý“.
- Poskytuje možnost rollbacku: pokud nová implementace selhává, stará stále funguje.
- Tým se učí DDD postupně, na reálném produkčním kódu.
- Refaktoring lze zastavit kdykoli – systém zůstává v konzistentním, funkčním stavu.

### Co vzor neřeší

Ve verzi textu z roku 2024 Fowler popis rozšířil ze čtyř kroků v kódu na čtyři aktivity:
ujasnit cílové výsledky, najít v systému švy a rozdělit problém na části, dodávat náhrady
inkrementálně a měnit organizační praktiky. Poslední bod týmy vynechávají nejčastěji.
Bez něj vznikne systém stejně křehký jako ten nahrazený, jen postavený na novějším
frameworku [[2]](https://martinfowler.com/bliki/StranglerFigApplication.html).

Vzor komplexitu neodstraňuje, rozprostírá ji v čase. Platí se za to přechodovou
architekturou: routovací vrstvou, dvojím zápisem, překladovými adaptéry. Ian Cartwright,
Rob Horn a James Lewis pro ni mají vlastní jméno – Transitional Architecture
[[7]](https://martinfowler.com/articles/patterns-legacy-displacement/transitional-architecture.html)
– a připojují k němu varování: počítejte s prací, kterou nakonec zahodíte. Kdo přechodovou
vrstvu nezahodí, zdědí ji jako trvalou součást systému.

### Datová migrace při Strangler Fig {#datova-migrace-strangler-heading}

Kód se dá nahrazovat po částech, data ne – tabulka má v každém okamžiku jeden tvar.
Strangler Fig proto potřebuje plán, jak data převést do nového modelu bez výpadku
a s možností návratu. Osvědčený postup má čtyři fáze.

**1. Dual-write s porovnáním.** Aplikace začne zapisovat do starého i nového modelu současně.
Primární zůstává starý zápis; ten nový se provádí navíc a jeho chyba nesmí shodit požadavek.
Asynchronní job oba zdroje porovnává a rozdíly loguje. Každý nalezený rozdíl znamená chybu
v mapování, kterou je nutné opravit ještě před přepnutím.

Pozor na termín. „Dual-write“ zde znamená zápis do dvou datových modelů v jedné databázové
transakci, tedy operaci, která buď proběhne celá, nebo vůbec. Kapitola
[Outbox Pattern](/outbox-pattern) používá tentýž pojem pro zápis do databáze a do brokeru
bez společné transakce, což je problém, který outbox řeší. Stejné slovo, dvě různé situace.

Dual-write z aplikace předpokládá, že do zápisové cesty starého systému lze zasáhnout.
U kódu, kterému nikdo nerozumí, nebo u zápisů obcházejících ORM to neplatí. Náhradou je
Event Interception [[8]](https://martinfowler.com/bliki/EventInterception.html): změny se
zachytávají pod aplikací – databázovým triggerem nebo CDC nástrojem typu Debezium – a nový
model je konzumuje. Volba se řídí jedním kritériem. Existuje-li v kódu jedno místo, kudy
teče každý zápis, stačí dual-write. Pokud takové místo není, zbývá vrstva pod aplikací.

**2. Backfill.** Teprve po zapnutí dual-write naplní jednorázový skript nové tabulky historickými
daty. Obrácené pořadí je vadné: UPDATE legacy řádku, který backfill už zpracoval, by se před
zapnutím dual-write ztratil – checkpoint `WHERE id > checkpoint` ho podruhé nenačte. Skript musí
být idempotentní: opakované spuštění nesmí vytvořit duplicity ani přepsat novější záznam, který
mezitím zapsal dual-write. Běží po dávkách podle `id` nebo `updated_at`, při konfliktu vyhrává
novější záznam, a ukládá si checkpoint posledního zpracovaného řádku, takže po pádu naváže tam,
kde skončil.

:::callout{type="pattern"}
### Příklad: Idempotentní backfill po dávkách (pseudokód)

:::code{language="php" filename="src/UserManagement/Infrastructure/Migration/BackfillUsersCommand.php"}
// Dual-write už běží – skript jen doplňuje historická data.
$lastId = $checkpoint->load() ?? 0;

do {
    $rows = $legacyDb->fetchAllAssociative(
        'SELECT * FROM users WHERE id > ? ORDER BY id LIMIT 500',
        [$lastId]
    );

    foreach ($rows as $row) {
        // UPSERT: existující záznam se aktualizuje, nikdy neduplikuje;
        // novější zápis z dual-write má přednost (porovnání updated_at)
        $newWriteModel->upsertFromLegacyIfOlder($row);
        $lastId = $row['id'];
    }

    $checkpoint->save($lastId); // po pádu skript naváže zde
} while (count($rows) === 500);
:::
:::

**3. Shadow reads.** Čtení probíhá z obou zdrojů: odpověď uživateli sestavuje starý model,
výsledek toho nového se pouze porovná a neshoda zvedne alert. Teprve nulová míra rozdílů
po dnech až týdnech provozu dává jistotu, že nový model je úplný a správný.

**4. Cutover.** Přepnutí na nový model řídí feature flag, ne deploy. Provoz se převádí
postupně – 1 %, 10 %, 50 %, vše – a metriky z fáze shadow reads zůstávají zapnuté.
Rollback znamená přepnout flag zpět; starý model je díky dual-write stále aktuální.
To platí jen tehdy, když po přepnutí primáru dual-write pokračuje v obráceném směru –
nový model zapisuje zpět do starého. Starý zápis se vypíná jako úplně poslední krok,
po několika týdnech klidného provozu. Symfony pro feature flagy vlastní komponentu nemá;
v PHP se používají knihovny třetích stran, typicky bundle `flagception/flagception-bundle`
nebo klient `unleash/client`.

**Schéma se mění stejným způsobem.** Sloupec ani tabulka se nepřejmenovává jedním
`ALTER TABLE`. Pramod Sadalage a Martin Fowler pro to popisují přechodnou fázi
[[9]](https://martinfowler.com/articles/evodb.html): nová struktura vznikne vedle staré,
obě jsou po dobu migrace naplněné, a původní název zůstane dostupný jako pohled (view)
nebo přes trigger. Závislé systémy tak migrují vlastním tempem a `DROP` přijde až na konci.
V Symfony tuto posloupnost nese `DoctrineMigrationsBundle` – každá fáze je vlastní
verzovaná migrace, ne ruční zásah do produkční databáze.

## 18.03 Anti-Corruption Layer mezi legacy a novým modelem {#acl-legacy}

Koexistence dvou architektur má jedno slabé místo, a tím jsou data. Nový model si sáhne pro
uživatele do legacy tabulky, převezme její sloupce jeden ku jedné a za měsíc vypadá stejně
jako to, co měl nahradit. Anti-Corruption Layer je vrstva, která tomu brání: překládá cizí
model na doménový a zpět, a nic jiného nedělá. Plný výklad vzoru včetně jeho místa v Context
Mapu je v kapitole [Context Mapping](/context-mapping#acl), zde jde o jeho migrační podobu.

Cartwright, Horn a Lewis popisují tutéž věc pod jménem Legacy Mimic
[[10]](https://martinfowler.com/articles/patterns-legacy-displacement/legacy-mimic.html):
nový systém mluví se starým tak, aby starý o změně nevěděl. Rozdíl je v úhlu pohledu.
Legacy Mimic chrání starý systém před novým, ACL chrání nový model před tvarem toho starého.
Při migraci z CRUD potřebujete obojí, obvykle ve stejné třídě.

Tři pravidla drží ACL na uzdě:

1. Vrstva patří do infrastruktury nového Bounded Contextu, nikdy do jeho domény. Doménový
   kód o existenci legacy tabulky neví.
2. Překlad je explicitní kontrakt. Co legacy model neumí vyjádřit, se doplní výchozí
   hodnotou nebo hlasitě selže. Doménový model se kvůli tomu neohýbá.
3. ACL má datum expirace. Až legacy zápisy skončí, vrstva se maže. Přechodová architektura,
   kterou nikdo nezahodil, je jen další legacy.

:::callout{type="pattern"}
### Příklad: Translator z legacy řádku na doménový agregát {#acl-translator-heading}

:::code{language="php" filename="src/UserManagement/Infrastructure/Legacy/LegacyUserTranslator.php"}
<?php

declare(strict_types=1);

// ACL žije v infrastruktuře nového Bounded Contextu.
// Doménová vrstva o legacy tabulce ani o této třídě neví.
namespace App\UserManagement\Infrastructure\Legacy;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserStatus;
use Symfony\Component\Uid\Uuid;

final class LegacyUserTranslator
{
    /**
     * @param array<string, mixed> $row řádek z legacy tabulky `users`
     */
    public function toDomain(array $row): User
    {
        // Legacy sloupec zná hodnoty, které doména nemá.
        // Mapování je vyjmenované: neznámý stav je chyba, ne tichý default.
        $status = match ($row['status']) {
            'pending_verification' => UserStatus::PendingVerification,
            'active'               => UserStatus::Active,
            'banned', 'deleted'    => UserStatus::Blocked,
            default => throw new UnmappableLegacyStatusException((string) $row['status']),
        };

        // Rekonstituce, ne registrace: žádná doménová událost nevzniká.
        return User::reconstitute(
            new UserId(Uuid::fromString((string) $row['uuid'])),
            new Email((string) $row['email']),
            HashedPassword::fromHash((string) $row['password']),
            $status,
        );
    }
}
:::

Translator je jediné místo v novém kontextu, které zná názvy legacy sloupců. Repozitář
nového modelu ho volá při čtení, dual-write přes něj v opačném směru zapisuje. Až se starý
zápis vypne, zmizí obojí a doménový kód se nezmění ani o řádek.
:::

Hranici je rozumné hlídat i staticky: pravidlo, které zakáže jmennému prostoru
`App\<BC>\Domain\*` odkazovat na `App\<BC>\Infrastructure\Legacy\*`, odhalí prosáknutí
dřív než code review. Postup je v kapitole
[Architektonické testy](/testovani-ddd#architektonicke-testy).

## 18.04 Techniky bezpečné postupné změny {#bezpecne-zmeny}

Strangler Fig říká, co dělat v měřítku systému. Nad jedním konkrétním refaktoringem ale
mlčí: jak vyměnit implementaci třídy, kterou volá čtyřicet míst, a přitom nasazovat každý
den. Na to jsou tři pojmenované techniky.

### Branch by Abstraction

Fowler ji popsal v roce 2014 [[11]](https://martinfowler.com/bliki/BranchByAbstraction.html);
termín zavedl Paul Hammant a autorství připisuje Stacy Curlovi. Postup má pět kroků:

1. Nad rozhraním mezi volajícím a starou implementací vytvořte abstrakci – v PHP zpravidla
   interface.
2. Převeďte na ni všechny volající. Chování se nemění, systém zůstává nasaditelný.
3. Za stejnou abstrakcí postavte novou implementaci (doménový repozitář místo přímého
   `EntityManager`).
4. Přepínejte volající po částech, přes feature flag nebo alias v `services.yaml`.
5. Starou implementaci smažte.

Ve verzovacím systému přitom žádná větev nevznikne. Obě implementace žijí v hlavní větvi
vedle sebe a rozdíl mezi nimi drží abstrakce, ne merge.

### Parallel Change: expand – migrate – contract

Zápis na Fowlerově webu je od Danila Sata
[[12]](https://martinfowler.com/bliki/ParallelChange.html); samotnou techniku popsal Joshua
Kerievsky už v roce 2006. Týká se změny jednoho rozhraní, ne celého systému:

- **Expand** – rozhraní se rozšíří tak, aby uneslo starou i novou variantu. Metoda
  `setEmail(string $email)` dostane sourozence `changeEmail(Email $email)`.
- **Migrate** – volající se převádějí. Uvnitř aplikace je to práce na hodiny, u externích
  klientů API na měsíce; tato fáze bývá nejdelší.
- **Contract** – stará varianta se odstraní. Odchod se ohlásí atributem `#[\Deprecated]`
  (PHP 8.4) nebo `trigger_error(..., E_USER_DEPRECATED)`.

Hodnota vzoru je v tom, že kód je nasaditelný v každé ze tří fází. Stejná trojice platí i na
databázové schéma – přesně to popisuje čtyřfázová migrace dat v sekci
[Strangler Fig Pattern](#strangler-fig).

### Mikado Method

Metodu vytvořili Ola Ellnestam a Daniel Brolund, v PHP ji zpopularizoval Matthias Noback
[[13]](https://matthiasnoback.nl/2021/02/refactoring-the-mikado-method/). Jde shora dolů:
cílová změna se provede rovnou a nechá se selhat. Z chybových hlášek a padlých testů se
odvodí prerekvizity, změna se zahodí (`git checkout .`) a řeší se nejdřív listy vzniklého
grafu. Graf tak vzniká z reality kompilátoru a testů, ne z odhadu u tabule.

K metodě patří pravidlo, které migraci drží při životě: refaktoring musí jít kdykoli
zastavit a projekt musí být v tu chvíli v lepším stavu než na začátku
[[14]](https://matthiasnoback.nl/2021/02/refactoring-prepare-to-stop/). Migrace, která
je použitelná až po dokončení, je big bang rozložený do sprintů.

## 18.05 Krok 1: Analýza existující domény {#analyza-domeny}

Než začneme přesouvat kód, musíme pochopit doménu. Nejčastější chybou je přímý skok do refaktoringu
bez předchozí analýzy – výsledkem je pak DDD architektura, která přesně kopíruje strukturu starých
databázových tabulek, aniž by odrážela skutečný doménový model.

### Identifikace Bounded Contexts z existujícího CRUD kódu

Bounded Contexts lze v existující CRUD aplikaci identifikovat sledováním přirozených hranic:

- **Skupiny entit a tabulek**, které jsou silně provázané navzájem, ale slabě propojené s ostatními skupinami – to jsou kandidáti na jeden Bounded Context.
- **God Services** – velké service třídy jsou paradoxně dobrým vodítkem. Pokud `OrderService` obsahuje logiku objednávky, platby i doručení, jsou to tři různé Bounded Contexts skryté v jedné třídě.
- **Opakující se slovo s různým významem** – pokud „zákazník“ v kontextu prodeje znamená něco jiného než „zákazník“ v kontextu zákaznické podpory, jde o přirozené rozhraní dvou Bounded Contexts.

### Event Storming jako nástroj pro analýzu

Event Storming vymyslel Alberto Brandolini
[[4]](https://www.eventstorming.com/). Workshopová technika modeluje doménu
přes doménové události a zapojuje do návrhu i lidi mimo tým vývoje. Při migraci z CRUD odkrývá
implicitní doménovou logiku skrytou v kontrolerech a service třídách. Vedle toho pojmenuje
přechody stavů entit z pohledu domény, nikoli databáze, ukáže přirozené hranice
Bounded Contexts a přivede doménové experty k návrhu nové architektury. Notaci, průběh
workshopu a jeho tři úrovně rozebírá kapitola [Event Storming](/event-storming#big-picture).

### Co nemigrovat

Analýza má přinést dva seznamy: co existuje a co skončí. Feature parity, tedy požadavek, aby
nový systém uměl přesně totéž co starý, Cartwright, Horn a Lewis nedoporučují
[[15]](https://martinfowler.com/articles/patterns-legacy-displacement/feature-parity.html).
Náklad na replikaci existující funkcionality se soustavně podceňuje a část replikovaného
chování nikdo nepoužívá. V CRUD aplikaci s deseti lety historie bývá typickým nálezem
exportní obrazovka, kterou obsluhovali dva lidé a jeden z nich je tři roky pryč. Takovou
funkci nemá smysl modelovat. Ruší se.

Verdikt ale nevydává tým sám. Seznam funkcí ke zrušení patří produktu a doménovému expertovi;
vývojáři k němu dodají data o skutečném užití z logů a metrik.

:::callout{type="pattern"}
### Příklad: Identifikace doménové logiky v CRUD kontroleru {#crud-before-heading}

Následující příklad ilustruje typický CRUD kontroler, ve kterém se skrývá netriviální doménová
logika. Tuto logiku v dalších krocích extrahujeme do doménového modelu.

:::code{language="php" filename="src/Controller/UserController.php"}
<?php

// PŘED migrací: Typický CRUD kontroler s ukrytou doménovou logikou
namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    #[Route('/users/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Doménová logika č. 1: validace emailu (patří do Value Object)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Neplatný e-mail'], 422);
        }

        // Doménová logika č. 2: kontrola unikátnosti (patří do doménové služby)
        $existing = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'E-mail již existuje'], 409);
        }

        // Doménová logika č. 3: hashování hesla a bezpečnostní pravidla
        if (strlen($password) < 8) {
            return $this->json(['error' => 'Heslo musí mít alespoň 8 znaků'], 422);
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTimeImmutable());
        // Doménová logika č. 4: výchozí stav uživatele
        $user->setStatus('pending_verification');

        $this->em->persist($user);
        $this->em->flush();

        // Doménová logika č. 5: odeslání uvítacího e-mailu
        // ... (inline kód pro odeslání e-mailu)

        return $this->json(['id' => $user->getId()], 201);
    }
}
:::

V tomto kontroleru lze identifikovat nejméně pět oblastí doménové logiky, které patří do
doménového modelu: validace formátu e-mailu, unikátnost e-mailu, bezpečnostní pravidla hesla,
výchozí stav uživatele a vedlejší efekt registrace (uvítací e-mail jako Domain Event).
:::

## 18.06 Krok 2: Extrakce doménové vrstvy {#extrakce-domainove-vrstvy}

Extrakce doménové vrstvy přesouvá doménová pravidla z kontrolerů a service tříd do objektů,
které je vlastní. Cíl: tyto objekty si své invarianty hlídají samy. Nikdo zvenčí je nemůže obejít.

### Přesunutí doménových pravidel do doménových objektů

Refaktoring má dva kroky. Nejdřív vzniknou Value Objects pro primitivy s doménovými pravidly. Pak se
logika přesune do entit a doménových služeb.

:::callout{type="pattern"}
### Příklad: Refaktorizace UserService – before/after {#before-after-heading}

:::code{language="php" filename="src/Service/UserService.php"}
<?php

// PŘED: God Service s přímou závislostí na Doctrine
namespace App\Service;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {}

    public function register(string $email, string $password): User
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Neplatný e-mail');
        }
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Heslo příliš krátké');
        }
        $existing = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        if ($existing) {
            throw new \RuntimeException('E-mail již existuje');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $user->setStatus('pending_verification');
        $this->em->persist($user);
        $this->em->flush();

        $this->mailer->send(/* uvítací e-mail */);

        return $user;
    }
}
:::

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php"}
<?php

declare(strict_types=1);

// PO: Doménová entita s vlastními invarianty
namespace App\UserManagement\Domain\Model;

use App\SharedKernel\Domain\AggregateRoot;
use App\UserManagement\Domain\Event\UserActivated;
use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\Exception\UserAlreadyActivatedException;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserStatus;
use App\UserManagement\Domain\ValueObject\VerificationToken;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'user_id')]
    public readonly UserId $id;

    #[ORM\Column(type: 'email_vo', unique: true)]
    private Email $email;

    #[ORM\Embedded(class: HashedPassword::class)]
    private HashedPassword $password;

    #[ORM\Column(enumType: UserStatus::class)]
    private UserStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    public readonly \DateTimeImmutable $registeredAt;

    #[ORM\Column(type: 'verification_token', nullable: true)]
    private ?VerificationToken $verificationToken;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    private function __construct(UserId $id, Email $email, HashedPassword $password)
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->status = UserStatus::PendingVerification;
        $this->registeredAt = new \DateTimeImmutable();
        $this->verificationToken = VerificationToken::generate();
    }

    // Named constructor vyjadřuje záměr lépe než new User()
    public static function register(UserId $id, Email $email, HashedPassword $password): self
    {
        $user = new self($id, $email, $password);
        // Doménová událost – vedlejší efekt registrace je nyní explicitní.
        // Nahrává ji named constructor, ne __construct: rekonstituce událost nevyvolá.
        $user->record(new UserRegistered($id, $email, $user->registeredAt));

        return $user;
    }

    // Rekonstituce z persistence nebo z ACL nad legacy tabulkou.
    // Nastavuje stav tak, jak byl uložen, a nezaznamenává žádnou událost.
    public static function reconstitute(
        UserId $id,
        Email $email,
        HashedPassword $password,
        UserStatus $status,
    ): self {
        $user = new self($id, $email, $password);
        $user->status = $status;

        return $user;
    }

    public function activate(VerificationToken $token): void
    {
        if ($this->status !== UserStatus::PendingVerification) {
            throw UserAlreadyActivatedException::forUser($this->id);
        }
        // Token musí odpovídat tomu, který entita vydala. Bez tohoto
        // porovnání aktivuje libovolný platný token libovolný účet.
        if (!$this->verificationToken->equals($token)) {
            throw InvalidVerificationTokenException::forUser($this->id);
        }
        $this->status = UserStatus::Active;
        $this->verificationToken = null;
        $this->record(new UserActivated($this->id));
    }

    public function email(): Email { return $this->email; }
    public function status(): UserStatus { return $this->status; }
}
:::

Doménová entita `User` nyní sama vynucuje svá pravidla: výchozí stav, přechod stavu
při aktivaci, emituje Domain Event při registraci. Kontroler ani service nemůže tyto invarianty
obejít.
:::

### Zavedení Value Objects místo primitive types

Doménový koncept skrytý v `string` nebo `int` se nazývá Primitive Obsession. Value Object nahradí
primitiv objektem, který drží validaci i chování pohromadě.

:::callout{type="pattern"}
### Příklad: Refaktorizace string emailu na Email Value Object {#email-vo-heading}

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/Email.php"}
<?php

// PŘED: Email jako string – validace je rozptýlena v celé aplikaci
namespace App\Controller;

class UserController {
    public function register(Request $request): Response {
        $email = $request->request->get('email'); // string, nic negarantuje
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { /* ... */ }
        // ... validace se opakuje na každém místě použití
    }
}

// --- Soubor: Email.php ---

// PO: Email jako Value Object – validace je na jednom místě
namespace App\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\Exception\ForbiddenEmailDomainException;

final readonly class Email
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" není platná e-mailová adresa.', $value)
            );
        }

        // Zakázané domény (doménové pravidlo)
        $domain = substr($value, strpos($value, '@') + 1);
        if (in_array($domain, ['example.com', 'test.com'], true)) {
            throw ForbiddenEmailDomainException::forDomain($domain);
        }
    }

    // Normalizace vstupu (lowercase, trim) patří sem, ne do konstruktoru
    public static function fromUserInput(string $input): self
    {
        return new self(mb_strtolower(trim($input)));
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
:::

Value Object `Email` zapouzdřuje validaci na jednom místě. Kdykoli vznikne
instance `Email`, máme garantovanou platnost hodnoty – bez ohledu na to,
kde v aplikaci k vytvoření dochází. Toto je základní princip „Make Illegal States Unrepresentable“.
:::

## 18.07 Krok 3: Zavedení repozitářů {#zavedeni-repozitaru}

CRUD aplikace typicky volá `EntityManagerInterface` nebo Doctrine repozitáře přímo z kontrolerů
a service tříd. DDD postaví mezi doménu a persistenci doménové rozhraní repozitáře. Doménový kód
o Doctrine ani SQL nic neví a implementace se dá vyměnit bez jeho úprav.

### Vytvoření doménového rozhraní repozitáře

Doménové rozhraní repozitáře žije v doménové vrstvě. Popisuje operace tak, jak je potřebuje doména.
O Doctrine, SQL ani jiné infrastruktuře nepadne ani zmínka.

:::callout{type="pattern"}
### Příklad: Doménové rozhraní vs. Doctrine implementace {#repository-interface-heading}

:::code{language="php" filename="src/UserManagement/Domain/Repository/UserRepository.php"}
<?php

declare(strict_types=1);

// Doménové rozhraní – součást domény, žádná infrastrukturní závislost
namespace App\UserManagement\Domain\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /** @return User[] */
    public function findActiveUsers(): array;

    public function nextIdentity(): UserId;
}
:::

:::code{language="php" filename="src/UserManagement/Infrastructure/Repository/DoctrineUserRepository.php"}
<?php

declare(strict_types=1);

// Infrastrukturní implementace – obaluje Doctrine EntityManager
namespace App\UserManagement\Infrastructure\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserStatus;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function save(User $user): void
    {
        $this->em->persist($user);
        // Flush je záměrně ponechán na aplikační vrstvě (Command Handler)
        // aby byla možná transakční konzistence přes více agregátů
    }

    public function findById(UserId $id): ?User
    {
        // Identifikátor se předává jako Value Object, stejně jako u findByEmail().
        // Převod na sloupec obstará custom typ 'user_id'.
        return $this->em->find(User::class, $id);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }

    public function findActiveUsers(): array
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.status = :status')
            ->setParameter('status', UserStatus::Active)
            ->getQuery()
            ->getResult();
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}
:::

Doménová vrstva závisí pouze na rozhraní `UserRepository`. Symfony DI container
injektuje do doménových služeb `DoctrineUserRepository`. Díky
tomu lze implementaci repozitáře vyměnit v konfiguračním souboru bez změny doménového kódu.
Pole `email` je mapované custom Doctrine typem `email_vo`, proto `findOneBy` dostává přímo
Value Object – převod na databázovou hodnotu zajistí typ, žádná cesta `email.value` neexistuje.
Totéž platí pro identifikátor: `find()` dostane `UserId`, ne primitiv. Doctrine ale u ID
mapovaných na objekt vyžaduje, aby třída implementovala `__toString()`; bez toho
`UnitOfWork` identitu nesestaví.
:::

:::callout{type="note"}
### Konfigurace Dependency Injection v Symfony {#di-config-heading}

:::code{language="yaml" filename="config/services.yaml"}
services:
    App\UserManagement\Domain\Repository\UserRepository:
        alias: App\UserManagement\Infrastructure\Repository\DoctrineUserRepository
:::

Tato konfigurace zajistí, že Symfony automaticky injektuje Doctrine implementaci všude tam,
kde je typovaná závislost na doménovém rozhraní `UserRepository`.
:::

## 18.08 Krok 4: Postupné zavedení CQRS {#cqrs-postupne}

Command Query Responsibility Segregation (CQRS) na DDD navazuje, ale má se zavést
až poté, co se doménový model usadí. Když přijde dřív, přesune komplexitu z domény do handleru,
kde je neviditelná a hůř se testuje.

### Začít s Command stranou (write side)

CQRS se zavádí od write side, tedy od operací, které mění stav systému. Tam už doménový model
existuje a Command jen pojmenuje záměr. Čtení může zpočátku zůstat na přímých Doctrine dotazech;
optimalizované SQL jako read model je v DDD systému legitimní trvalý stav, ne provizorium.

:::callout{type="pattern"}
### Příklad: Extrakce RegisterUserCommand z UserController {#command-extraction-heading}

:::code{language="php" filename="src/Controller/UserController.php"}
<?php

// PŘED: Logika přímo v kontroleru nebo service
namespace App\Controller;

class UserController extends AbstractController
{
    public function __construct(
        private UserService $userService
    ) {}

    #[Route('/users/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        // Kontroler musí vědět, jaké parametry service očekává
        $this->userService->register(
            $request->request->get('email'),
            $request->request->get('password'),
            $request->request->get('name')
        );
        return $this->json(['status' => 'ok'], 201);
    }
}
:::

:::code{language="php" filename="src/UserManagement/Application/Command/RegisterUser.php"}
<?php

// --- Soubor: RegisterUser.php ---
// PO: Command objekt jako explicitní kontrakt
namespace App\UserManagement\Application\Command;

final readonly class RegisterUser
{
    public function __construct(
        public string $email,
        public string $password,
        public string $name,
    ) {}
}

// --- Soubor: RegisterUserHandler.php ---
// Handler zapouzdřuje aplikační logiku jednoho use case
namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private UserRegistrationPolicy $policy,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = Email::fromUserInput($command->email);
        $password = HashedPassword::fromPlainText($command->password);

        // Doménová politika ověřuje pravidla přes repozitář
        $this->policy->assertEmailIsUnique($email);

        $user = User::register(
            $this->users->nextIdentity(),
            $email,
            $password
        );

        $this->users->save($user);

        // Doménové události handler nepublikuje: po flushi je z agregátu
        // sebere infrastruktura (outbox listener) přes releaseEvents()
        // a předá Messengeru – viz Recept 7 a kapitolu DDD v praxi.
    }
}

// --- Soubor: UserController.php ---
// Kontroler je nyní tenký – pouze HTTP adaptér
namespace App\Controller;

use App\UserManagement\Application\Command\RegisterUser;
use Symfony\Component\Messenger\MessageBusInterface;

class UserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {}

    #[Route('/users/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $this->commandBus->dispatch(new RegisterUser(
            email: $request->request->getString('email'),
            password: $request->request->getString('password'),
            name: $request->request->getString('name'),
        ));
        return $this->json(['status' => 'ok'], 201);
    }
}
:::

Command `RegisterUser` je prosté DTO (Data Transfer Object) bez závislostí. Handler
`RegisterUserHandler` orchestruje doménový model. `UserRegistrationPolicy` je doménová
služba: nese pravidlo unikátní e-mailové adresy, které nelze ověřit uvnitř jediného
agregátu, a proto smí použít repozitář. Kontroler se zužuje na HTTP
vrstvu, která pouze přeloží HTTP požadavek na Command. Takto oddělené vrstvy
se dají testovat každá zvlášť.
:::

### Read model nad legacy schématem jako první krok

Věta o tom, že query side lze zpočátku ponechat, svádí k odložení čtení na konec. Opačné
pořadí bývá bezpečnější. Read model postavený nad starým schématem nezmění ani jeden zápis,
takže nemůže poškodit data. Ověří se porovnáním výstupu se starou obrazovkou. A projekce,
která pro něj vznikne, je později připravená konzumovat doménové události z nového modelu.

Konkrétně: dotaz z `OrderRepository::findAllForListing()` se přesune do `OrderListQuery`
s vlastním handlerem a read DTO, samotné SQL zůstane beze změny. Až se write model překlopí,
mění se jen tělo handleru; kontroler ani šablona o tom nevědí. Read modely, jejich projekce
a eventual consistency rozebírá kapitola [CQRS v Symfony](/cqrs).

## 18.09 Testování při migraci {#testovani-pri-migraci}

O úspěchu migrace rozhodují testy. Bez nich refaktoring zavede regrese, které se projeví
v produkci. Migrace z CRUD na DDD potřebuje dvě techniky: charakterizační testy pro zachycení
stávajícího chování a unit testy pro nově vznikající doménovou vrstvu.

### Charakterizační testy (Characterization Tests)

Pojem „charakterizační testy“ pochází z knihy Michaela Featherse „Working Effectively with Legacy
Code“
[[5]](https://www.oreilly.com/library/view/working-effectively-with/0131177052/).
Charakterizační test nepopisuje, jaké *by mělo být* správné chování systému, ale zachycuje,
jaké chování systém *aktuálně má*. Slouží jako síť, která zachytí nechtěné změny chování
při refaktoringu. Feathers zároveň definuje legacy code prostě jako kód bez testů – ne jako
kód starý nebo ošklivý.

### Švy: kudy se test do legacy kódu dostane

Charakterizační test je výsledek, šev (seam) je jeho předpoklad. Podle Featherse je šev
místo, kde lze změnit chování programu, aniž se edituje kód na tomtéž místě. Ke každému
patří enabling point: místo, kde se rozhoduje, která varianta chování se použije
[[16]](https://martinfowler.com/bliki/LegacySeam.html). Zavést švy do zaběhnutého systému
stojí značné úsilí. Je to investice, ne vedlejší produkt refaktoringu.

V Symfony CRUD kódu se opakují tři:

- **Konstruktorová injekce místo `new`.** Řádek `$mailer = new SmtpMailer();` uvnitř metody
  šev nemá. Přesun závislosti do konstruktoru z něj šev udělá a enabling point se přesune
  do konfigurace služeb.
- **Interface místo konkrétní třídy.** Type hint na `UserRepository` místo na
  `DoctrineUserRepository` posune enabling point do `services.yaml`, kde ho test přepíše.
- **Event listener místo inline kódu.** Vedlejší efekt vytažený z metody do listeneru jde
  v testovacím prostředí odpojit; enabling point je registrace listeneru.

### Kdy charakterizační test být nemusí

Plné pokrytí legacy systému charakterizačními testy nikdo nenapíše a čekat na ně znamená
nezačít. Matthias Noback argumentuje, že strukturální transformace zachovávající chování
jsou bezpečné i bez testů, pokud je jistí statická analýza a párové programování
[[17]](https://matthiasnoback.nl/2022/10/refactoring-without-tests-should-be-fine/).
Použitelná gradace vypadá takto:

1. Automatizovaný refaktoring z IDE (rename, extract method, move class) – bez testu.
2. Ruční strukturální změna pod PHPStan na úrovni 8 a s jedním vysokoúrovňovým smoke testem.
3. Zásah do rozhodovací logiky – teprve zde charakterizační test, a jen na dotčené cestě.

Hranice mezi druhým a třetím bodem je odhad, ne pravidlo. Když si tým není jistý, patří změna
do třetí kategorie.

Při extrakci logiky z legacy kódu pomáhají i jazykové modely: vygenerují první sadu
charakterizačních testů nebo popíší, co nepřehledná metoda dělá. Souvislosti tohoto
přístupu přibližuje kapitola [DDD a umělá inteligence](/ddd-a-umela-inteligence).

:::callout{type="pattern"}
### Příklad: Charakterizační test pro CRUD kontroler {#char-test-heading}

:::code{language="php" filename="Tests/Characterization/UserRegistrationCharacterizationTest.php"}
<?php

declare(strict_types=1);

namespace Tests\Characterization;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Charakterizační testy zachycují AKTUÁLNÍ chování systému.
 * Jsou záměrně popsány jako "chová se tak, jak se chová",
 * ne "mělo by se chovat tak a tak".
 * Pokud refaktoring změní toto chování, test selže a upozorní tým.
 */
class UserRegistrationCharacterizationTest extends WebTestCase
{
    public function test_registration_returns_201_with_valid_data(): void
    {
        $client = static::createClient();
        $client->request('POST', '/users/register', [
            'email' => 'test@example-valid.com',
            'password' => 'SecurePassword123',
            'name' => 'Jan Novák',
        ]);

        // Zachycujeme aktuální HTTP status kód
        self::assertResponseStatusCodeSame(201);
    }

    public function test_registration_returns_422_for_invalid_email(): void
    {
        $client = static::createClient();
        $client->request('POST', '/users/register', [
            'email' => 'not-an-email',
            'password' => 'SecurePassword123',
            'name' => 'Jan Novák',
        ]);

        // Zachycujeme aktuální chybový kód a strukturu odpovědi
        self::assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function test_duplicate_email_returns_409(): void
    {
        $client = static::createClient();
        // První registrace
        $client->request('POST', '/users/register', [
            'email' => 'duplicate@example-valid.com',
            'password' => 'SecurePassword123',
            'name' => 'Jan Novák',
        ]);
        // Druhá registrace se stejným e-mailem
        $client->request('POST', '/users/register', [
            'email' => 'duplicate@example-valid.com',
            'password' => 'AnotherPassword456',
            'name' => 'Jiný Uživatel',
        ]);

        self::assertResponseStatusCodeSame(409);
    }
}
:::

Charakterizační test vzniká *před* refaktoringem té části, které se týká, a prochází po celou
dobu migrace. Když selže, refaktoring změnil pozorovatelné chování systému – buď záměrně,
nebo omylem.
:::

### Unit testy doménové vrstvy

Doménový objekt, který nezná databázi ani HTTP, se testuje bez nich. Test běží
v milisekundách, nemá závislost na okolí a čte se jako zápis doménového pravidla.
Techniky, které tuto vrstvu pokrývají do hloubky – InMemory repozitáře, testování
doménových událostí, architektonické testy – rozebírá kapitola
[Testování DDD aplikací](/testovani-ddd#unit-testy-domeny).

:::callout{type="pattern"}
### Příklad: Unit test doménové entity {#domain-unit-test-heading}

:::code{language="php" filename="Tests/UserManagement/Domain/Model/UserTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Domain\Model;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserStatus;
use App\UserManagement\Domain\ValueObject\VerificationToken;
use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\Exception\UserAlreadyActivatedException;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_newly_registered_user_is_pending_verification(): void
    {
        $user = User::register(
            UserId::generate(),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123')
        );

        self::assertSame(UserStatus::PendingVerification, $user->status());
    }

    public function test_registration_emits_user_registered_event(): void
    {
        $user = User::register(
            UserId::generate(),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123')
        );

        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function test_cannot_activate_already_active_user(): void
    {
        $user = User::register(
            UserId::generate(),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123')
        );
        $token = VerificationToken::valid('abc123');
        $user->activate($token);

        $this->expectException(UserAlreadyActivatedException::class);
        $user->activate($token); // druhá aktivace musí selhat
    }
}
:::
:::

## 18.10 Rizika a doporučení {#rizika-a-doporuceni}

### Nejčastější chyby při migraci

- **Anémický doménový model** – Nejčastější past. Vývojáři vytvoří třídy s názvem jako v DDD (`User`, `Order`), ale tyto třídy obsahují pouze gettery a settery bez doménové logiky. Logika zůstane v service třídách. Výsledek je DDD terminologie s CRUD implementací.
- **Přílišná granularita Bounded Contexts** – Rozdělení domény na příliš mnoho malých kontextů vede k distribuované komplexitě. Každá integrace mezi kontexty přidává overhead. Začněte s většími kontexty a rozdělujte je až tehdy, když je důvod k tomu jasný.
- **ORM diktující tvar modelu** – Anti-vzorem není atributové mapování samo o sobě; [sekce 18.06](#extrakce-domainove-vrstvy) i Recept 2 ho přijímají jako pragmatickou volbu. Problém začíná, když ORM určuje tvar modelu: public settery kvůli hydrataci, anemická entita, `flush()` volaný z kontroleru. Projekty, které potřebují striktní oddělení domény od persistence, řeší tutéž potřebu přes [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern).
- **CQRS bez doménového modelu** – Zavedení CommandBusu a QueryBusu bez refaktorovaného doménového modelu přidá vrstvy komplexity bez přínosu. CQRS je amplifikátor – zesílí jak výhody, tak problémy stávající architektury.
- **Ignorování Anti-Corruption Layer** – Při integraci nové DDD vrstvy se starým CRUD kódem je nutné vytvořit překladovou vrstvu. Bez ní pronikají koncepty starého modelu do nového a kontaminují ho.

### Tipy pro týmovou komunikaci

- Vytvořte **glosář pojmů** (Ubiquitous Language) a udržujte ho aktuální. Vyvěste ho na wiki nebo přímo v repozitáři jako součást dokumentace.
- Pravidelně pořádejte **krátká Event Storming sezení** (30–60 minut) pro nové funkcionality před jejich implementací.
- Nastavte **code review pravidla**: doménová logika nesmí být v kontrolerech, doménové objekty nesmějí záviset na infrastruktuře.
- Komunikujte s managementem v pojmech **obchodní hodnoty**, nikoli technické architektury. Migrace na DDD = schopnost rychleji a bezpečněji přidávat nové funkce.

### Proč migrace selhávají

Seznam výše je technický, důvody selhání bývají organizační. Čtyři se opakují:

- **Migrace bez mandátu.** Tým ji dělá „při tom“, nikdo z vedení o ní neví, a první tlak na
  termín ji zastaví v půli. Argumentaci v jazyce obchodní hodnoty rozebírá sekce
  [Business case pro DDD refaktoring](/ddd-v-praxi-kde-to-boli#e1-management).
- **Tým bez doménového experta.** Model pak kopíruje databázi, protože jiný zdroj pravdy
  není k dispozici.
- **Chybějící rozhodnutí, co nemigrovat.** Bez něj se rozsah rovná celému legacy systému
  a konec se vzdaluje rychleji, než se pracuje.
- **Migrace nesená jedním člověkem.** Odejde-li, zůstane polovina rozdělané práce, které
  nikdo nerozumí. Bus factor a knowledge silos rozebírá sekce
  [Knowledge silos](/ddd-v-praxi-kde-to-boli#e3-silos).

Pátý důvod je tišší než ostatní: přechodová architektura, kterou nikdo nezahodil. Routovací
vrstva a dvojí zápis měly žít měsíce. Po dvou letech jsou z nich součásti systému a migrace
skončila tím, že přibyla třetí architektura vedle dvou původních.

:::callout{type="warn"}
### Varování před Big Bang Rewrites {#big-bang-warning-heading}

**Migraci na DDD nezačínejte kompletním přepisem produkčního systému, který je v aktivním
vývoji.** Big Bang Rewrite patří k nejrizikovějším architektonickým rozhodnutím, jaké tým
může udělat. Výjimky existují a popisuje je sekce
[Kdy migraci nezačínat](#kdy-nezacinat) – systém bez produkčních dat, kód menší než náklad
na zavedení švů. Živý produkt s uživateli mezi ně nepatří.

*Ilustrativní scénář.* Tým začne „přepis na zelenou louku“. Po půl roce zjistí, že nový systém
nesplňuje okrajové případy toho původního, které nikdo nezdokumentoval. Starý systém mezitím
dostává nové funkce a nový za ním nestíhá. Výsledkem je buď zrušení přepisu, nebo spuštění
nedokončeného systému s fatálními chybami.

Vždy preferujte **inkrementální migraci pomocí Strangler Fig Patternu**:
zachovejte funkční systém v produkci, přidávejte DDD vrstvu po vrstvě a nahrazujte CRUD kód
postupně při každém sprintu.
:::

DDD koncepty a jejich implementaci v Symfony rozebírají navazující kapitoly
[Implementace DDD v Symfony](/implementace-v-symfony)
a [CQRS v Symfony](/cqrs). Konkrétní třecí plochy, na které migrace naráží v produkci –
Doctrine, asynchronní infrastruktura, tým – shrnuje kapitola
[DDD v praxi: kde to bolí](/ddd-v-praxi-kde-to-boli).

## 18.11 Refaktoring kuchařka – krátké recepty {#refactoring-kucharka}

Strangler Fig je strategický pohled na celou migraci. V denní praxi narazíte na opakující se mikrosituace.
Tato kuchařka obsahuje 9 nejčastějších, každá ve formátu *„symptomy → krok 1, 2, 3“*.
Recepty jsou záměrně krátké – když potřebujete kontext nebo důkladnější rozbor, projděte odkazované kapitoly.

### Recept 1: Anémická Doctrine entita {#recept-anemic-entita-heading}

**Symptomy:** entita má jen gettery/settery, veškerá logika je v Service třídě.

1. Identifikujte invarianty entity (co nesmí být porušeno).
2. Pro každý invariant najděte metodu v `*Service`, která ho dnes drží.
3. Přesuňte metodu do entity, getter/setter zúžte na `private` nebo zrušte.
4. Service se stane tenkým koordinátorem (Application Service) – jen volá entitu, transakce, eventy.
5. Souvisí: [Anti-vzor: Anemic Domain Model](/anti-vzory) · [Domain Services vs. Application Services](/mene-zname-vzory#domain-services).

### Recept 2: Doctrine atributy v doménové třídě – kdy je to problém {#recept-doctrine-anotace-heading}

**Symptomy:** `App\Domain\Order` má `#[ORM\Entity]`, doména závisí na Doctrine.

Pragmatická výchozí volba v tomto průvodci atributy přijímá – jsou to metadata, ne chování,
a Symfony ekosystém s nimi pracuje idiomaticky (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)).
Pokud váš projekt skutečně potřebuje striktní oddělení (Hexagonal, dlouhodobá výměna ORM,
core doména s vysokou hodnotou), postup je:

1. Zaveďte [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern) –
   doménová třída zůstane POPO, persistence model + mapper jdou do
   `App\<BC>\Infrastructure\Persistence\Doctrine\`.
2. Mapper hydratujte z perzistence přes `User::reconstitute(...)` factory metodu, která
   neemituje doménové události.
3. Hlídejte hranici staticky: `composer require --dev phparkitect/phparkitect` + pravidlo
   `App\<BC>\Domain\* nesmí závisět na Doctrine\*` (kniha používá phparkitect napříč
   kapitolami, alternativou je `deptrac`).

### Recept 3: Primitivní ID jako `string` / `int` {#recept-id-string-heading}

**Symptomy:** `Order::$id: string`, kdekoli se předává jen `string`.

1. Zaveďte VO `OrderId` (`final readonly class OrderId { public function __construct(public Uuid $value) {} }`, generování přes `Uuid::v7()`).
2. Doctrine custom type pro `OrderId` (mapping z DB string ↔ VO). VO musí implementovat `__toString()` – `UnitOfWork` předpokládá, že identifikátor je převeditelný na řetězec, a bez toho mapování nefunguje.
3. Postupně refaktorujte signature napříč handlery. PHPStan na úrovni 8 odhalí každý zapomenutý `string`.

### Recept 4: Doctrine tabulka sdílená napříč BC {#recept-shared-tabulka-heading}

**Symptomy:** tabulka `users` se používá v Ordering BC i Billing BC; oba do ní zapisují.

1. Identifikujte vlastnícího BC (typicky Identity).
2. Ostatní BC do ní nesmí zapisovat – jen číst. Čtení přesuňte do read-modelů (každý BC má vlastní projekci).
3. Zápisy nahraďte voláním Identity API (sync HTTP nebo async event publishing s outboxem).
4. Souvisí: [Outbox Pattern](/outbox-pattern).

### Recept 5: Doménová logika v controlleru {#recept-business-logika-controlleru-heading}

**Symptomy:** 200řádkový controller s if-else stromem doménových rozhodnutí.

1. Vytvořte `Command` DTO + `CommandHandler` v Application vrstvě.
2. Controller se zúží na: validate input → dispatch command → vrátit response.
3. Autorizaci přesuňte do Voteru (souvisí [Autorizace](/autorizace-v-ddd)).

### Recept 6: Aggregate bobtná (1000+ řádků) {#recept-aggregate-bobtna-heading}

**Symptomy:** `Order` má 30 metod a 15 polí.

1. Najděte pole, která se mění nezávisle (různé invarianty, různé use cases).
2. Zvažte rozdělení na 2 agregáty (např. `Order` + `OrderShipment`). Spojí je sdílené `OrderId`, žádná silná reference.
3. Specifikační logiku vyextrahujte do `Specification` tříd (souvisí [Specifications](/mene-zname-vzory#specification)).

### Recept 7: `eventDispatcher->dispatch()` uvnitř doménové metody {#recept-event-publish-uvnitr-heading}

**Symptomy:** Aggregate volá Symfony `EventDispatcher` přímo.

1. Aggregate dědí z `AggregateRoot` a eventy zaznamenává voláním `record($event)`.
2. Aplikační handler po `repository->save()` volá `$order->releaseEvents()` a publikuje (přes outbox).
3. Doména ztratí závislost na Symfony EventDispatcheru. Test je čistý.
4. Souvisí: [Outbox – Aggregate publikuje](/outbox-pattern#aggregate-publishes).

### Recept 8: Stav je sloupec `string $status` {#recept-fields-jako-stav-heading}

**Symptomy:** `Order::$status: string`, podmínky všude `if ($order->status === 'PLACED')`.

1. Zaveďte enum: `enum OrderStatus: string { case Placed = 'placed'; case Cancelled = 'cancelled'; }`.
2. Aggregate metody dělají transitions: `$this->status = OrderStatus::Cancelled`.
3. Pro komplexní transition rules zvažte State Machine (Symfony Workflow component nebo doménová reprezentace).

### Recept 9: Legacy tabulka, kterou nelze změnit {#recept-nemenitelna-tabulka-heading}

**Symptomy:** do tabulky `users` zapisuje i skript mimo aplikaci nebo cizí systém; ALTER TABLE je vyloučený.

1. Nový model si tabulku nepřivlastňuje. Čte přes [ACL translator](#acl-legacy), který legacy sloupce překládá na doménové pojmy.
2. Chybějící atributy uložte do vlastní tabulky nového kontextu, spojené cizím klíčem. Legacy schéma zůstává nedotčené.
3. Pokud jsou názvy sloupců neúnosné, vytvořte nad tabulkou pohled (view) s doménovým pojmenováním a mapujte entitu na něj.
4. Souvisí: [Mapping složitých Value Objects](/ddd-v-praxi-kde-to-boli#a3-value-objects).

:::faq{}
- question: Jaké příznaky ukazují, že CRUD aplikace je zralá na migraci?
  answer: 'Typickými signály jsou God Services o stovkách řádků a kontrolery obsahující doménová pravidla. Dále doménová logika zamíchaná v Doctrine repozitářích, opakované regresní chyby při drobných změnách a rostoucí čas potřebný pro onboarding nových vývojářů. Pokud aplikace tyto příznaky nevykazuje a zůstává prostým mapováním formulářů na tabulky, migrace odpovídající hodnotu nepřinese. Obecnější otázku, pro jaké projekty je DDD vhodné, řeší samostatná kapitola <a href="/kdy-nepouzivat-ddd">Kdy DDD nepoužívat</a>. Viz také <a href="#kdy-migrovat">sekci Kdy a proč migrovat</a>.'
- question: Co je Strangler Fig Pattern?
  answer: 'Strangler Fig (fíkovník škrtič) je migrační vzor popsaný Martinem Fowlerem, při kterém nová architektura postupně „obroste“ starý systém a nahradí ho po částech. Nová funkcionalita vzniká od začátku v DDD stylu, zatímco stará CRUD část zůstává v provozu a s každou iterací ubývá. Obě části existují paralelně a propojují se přes Anti-Corruption Layer. Podrobný rozbor v <a href="#strangler-fig">sekci Strangler Fig Pattern</a>.'
- question: Jak začít s analýzou existující domény?
  answer: 'Začíná se Event Stormingem nebo obdobnou kolaborativní technikou s doménovými experty – zmapují se hlavní události, commands a aktéři. Z této mapy vyplývá návrh Bounded Contexts a Ubiquitous Language. Paralelně se v existujícím kódu hledají implicitní hranice modelu: moduly, tabulky nebo funkční celky, které jsou málo propojené. Cílem první iterace je hrubá mapa, ne úplný model. Praktický postup v <a href="#analyza-domeny">sekci Analýza existující domény</a>.'
- question: Jak extrahovat doménovou vrstvu z existujícího CRUD kódu?
  answer: 'Migrace začíná u jednoho vybraného Bounded Contextu, pro který vzniká nová doménová vrstva oddělená od Doctrine entit. Doménová logika ze service tříd a kontrolerů se přesouvá do metod agregátu, zatímco původní CRUD kód zůstává jako adaptér pro API a persistenci. Nejprve se zavede Anti-Corruption Layer, pak se refaktorují jednotlivé use casy. Charakterizační testy proti původnímu chování minimalizují regrese. Detailní rozbor v <a href="#extrakce-domainove-vrstvy">sekci Extrakce doménové vrstvy</a>.'
- question: Jaká jsou hlavní rizika migrace z CRUD na DDD a jak je zmírnit?
  answer: 'Nejčastější pastí je anémický model: nové třídy mají DDD názvy, ale logika zůstává v servisech. Dále hrozí nadměrná granularita Bounded Contexts, přímé ukládání doménové logiky do Doctrine entit a zavádění CQRS bez přepracovaného modelu. Největším rizikem je Big Bang Rewrite, který se zřídka dotáhne do konce. Migrace má probíhat inkrementálně přes Strangler Fig, u středně velké aplikace s realistickým odhadem 12–24 měsíců. Rozbor rizik a zmírňujících opatření v <a href="#rizika-a-doporuceni">sekci Rizika a doporučení</a>.'
:::
