---
route: cqrs
path: /cqrs
title: CQRS v Symfony 8
page_title: "CQRS v Symfony 8 | DDD Symfony"
meta_description: "CQRS v Symfony 8: oddělení command a query strany přes Messenger, čtecí modely, eventual consistency a praktická konfigurace bus alias."
meta_keywords: "CQRS, Command Query Responsibility Segregation, Symfony Messenger, bounded contexts, doménové modely, příkazy, dotazy, command handlers, query handlers, asynchronní zpracování, Event Sourcing, DDD, Symfony 8, read model, eventual consistency, ViewModel, projekce, dead letter queue"
og_type: article
published: "2025-04-24"
modified: 2026-09-24
breadcrumb_name: CQRS
schema_type: TechArticle
schema_headline: "CQRS v Symfony 8"
chapter_number: "12"
category: Vzory
deck: 'Implementace CQRS (Command Query Responsibility Segregation) v Symfony 8 s využitím DDD principů – oddělení operací čtení a zápisu, optimalizace read modelů, řešení eventual consistency a stavba škálovatelných aplikací.'
reading_time: 28
difficulty: 3
github_examples: Chapter05_CQRS
---

## 12.01 Co je CQRS? {#what-is-cqrs}

CQRS vychází z prostého pozorování: **model, který slouží k zápisu dat, nemusí být tentýž model,
který slouží k jejich čtení**. CQRS (Command Query Responsibility Segregation) přenáší oddělení
čtení a zápisu z jednotlivé metody na celý model. Popsal ho Greg Young
[[1]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf).
Kořeny sahají ke Command-Query Separation (CQS) Bertranda Meyera
[[2]](https://martinfowler.com/bliki/CommandQuerySeparation.html). Young v *CQRS Documents*
dodává, že první roky se o vzoru mluvilo jako o rozšíření CQS na vyšší úrovni,
a sám tuto formulaci označuje za nepřesnou. Po letech záměn obou pojmů se CQRS ustálil
jako samostatný vzor, ne jako varianta staršího pravidla.

Tradiční aplikace používá pro obojí jednu entitu (typicky Doctrine ORM entitu):
objednávku vytváří i zobrazuje v seznamu přes tentýž objekt `Order`.
CQRS tuto odpovědnost rozděluje do dvou modelů, z nichž každý má vlastní úkol
a vlastní optimalizační profil.

:::callout{type="note"}
### Základní principy CQRS: {#zakladni-principy-heading}

- **Commands** – Příkazy, které mění stav systému. Ve striktním pojetí CQS nevracejí žádná data; v praxi CQRS mohou vracet identifikátor vytvořeného záznamu.
- **Queries** – Dotazy, které vracejí data, ale nemění stav systému.
- **Oddělené modely** – Write model (bohatý doménový model s doménovou logikou) a Read model (jednoduchá denormalizovaná datová struktura optimalizovaná pro dotazy).
- **Oddělené databáze** – Pokročilé implementace mohou čtení a zápis rozdělit do oddělených databází a škálovat je nezávisle.
:::

CQRS se často kombinuje s [Event Sourcingem](/event-sourcing), který místo aktuálního
stavu ukládá historii změn jako sekvenci událostí. Oba vzory jsou ale **nezávislé**.
CQRS lze plnohodnotně postavit na klasické Doctrine ORM persistenci na write straně
a denormalizovaných tabulkách na straně čtení, bez Event Sourcingu.

:::callout{type="note"}
### Tři mýty o CQRS {#cqrs-myty-heading}

- **„CQRS vyžaduje Event Sourcing.“** Nevyžaduje, viz odstavec výše.
- **„CQRS vyžaduje dvě databáze.“** Young popisuje read stranu jako tenkou vrstvu, která
  čte z **téže** databáze jako write strana a promítá řádky rovnou do DTO. Oddělené úložiště
  je jedna z možností, ne součást definice vzoru.
- **„CQRS vyžaduje frontu.“** Azure Architecture Center to shrnuje přímo: posílání zpráv
  není pro CQRS podmínkou
  [[3]](https://learn.microsoft.com/en-us/azure/architecture/patterns/cqrs).
  Udi Dahan k tomu dodává, že způsob zpracování příkazů je implementační detail
  [[4]](https://udidahan.com/2009/12/09/clarified-cqrs/). Message bus je v této kapitole
  zvolený nástroj, ne předpoklad vzoru.
:::

## 12.02 CQS vs. CQRS – kde je hranice? {#cqs-vs-cqrs}

Bertrand Meyer formuloval **Command-Query Separation (CQS)** jako pravidlo
na úrovni metod: každá metoda buď mění stav (command), nebo vrací hodnotu (query),
nikdy obojí. CQS je návrhové pravidlo pro rozhraní tříd.

Greg Young tutéž myšlenku posunul z metody na **model**: CQRS rozděluje jeden doménový
model na dva, každý s vlastní sadou tříd a vlastním optimalizačním profilem. Oddělené
úložiště je volitelné (viz [tři mýty o CQRS](#cqrs-myty-heading)).
Young přitom zdůrazňuje, že CQRS **není architektura**, nýbrž architektonický
vzor. Popisuje něco uvnitř jediného systému nebo komponenty, ne uspořádání celé aplikace
[[5]](https://gregfyoung.wordpress.com/2012/09/09/cqrs-is-not-an-architecture/).

:::callout{type="pattern"}
### CQS vs. CQRS – přehled {#cqs-vs-cqrs-tabulka-heading}

| Aspekt | CQS | CQRS |
|---|---|---|
| Úroveň | Metoda / třída | Doménový model uvnitř systému |
| Pravidlo | Metoda buď mění stav, nebo vrací data | Oddělený write model a read model |
| Počet modelů | Jeden sdílený model | Dva (nebo více) oddělených modelů |
| Databáze | Sdílená | Může být oddělená (write DB + read DB) |
| Složitost | Nízká – jde o konvenci | Střední až vysoká – zasahuje do tříd i do infrastruktury |
| Příklad | `getBalance()` nemodifikuje účet | `RegisterUserHandler` a `GetUserProfileHandler` pracují s různými datovými strukturami |
:::

CQS je přirozený výchozí bod pro CQRS. Kdo dodržuje CQS na úrovni metod, brzy zjistí,
že metody měnící stav potřebují výrazně jiná data než metody, které stav čtou.
CQRS z tohoto pozorování vyvozuje dva explicitní modely.

:::callout{type="note"}
### Úrovně zavedení CQRS {#cqrs-urovne-heading}

CQRS lze zavést v několika úrovních hloubky:

1. **Oddělené handlery** – Command a query handlery jako samostatné třídy nad sdílenou databází a ORM entitami. Nejjednodušší forma CQRS, vhodná pro většinu aplikací.
2. **Oddělené modely** – Write model staví na doménových entitách (Doctrine ORM), read strana na vlastních DTO/ViewModelech plněných přímým SQL přes Doctrine DBAL. Databáze zůstává sdílená, PHP třídy jsou oddělené.
3. **Oddělená úložiště** – Write databáze (PostgreSQL) a read úložiště (Elasticsearch, Redis, denormalizované tabulky). Změny se propagují asynchronně přes události.
4. **CQRS + Event Sourcing** – Write strana ukládá události do [Event Store](/event-sourcing), read strana staví projekce z event streamu. Nejvyšší složitost, ale také největší volnost.

Rozumná cesta vede přes úroveň 1 nebo 2. Na úroveň 3 a 4 se přechází, až když to vyžadují
konkrétní škálovací nebo doménové požadavky. Postup pro existující CRUD aplikaci popisuje
kapitola [Migrace z CRUD na DDD](/migrace-z-crud).

V DDD se CQRS obvykle nasazuje **per Bounded Context**, ne globálně na celou aplikaci.
Core doména s komplexní logikou může těžit z plného CQRS (úroveň 3–4), podpůrným kontextům
(notifikace, administrace) stačí jednoduchý CRUD – viz [Bounded Contexts](/zakladni-koncepty#bounded-contexts).
:::

## 12.03 Výhody CQRS {#benefits}

Výhody se projeví hlavně u aplikací s netriviální doménovou logikou a odlišnými požadavky
na čtení a zápis.

První je oddělení odpovědností. Write model nese doménovou logiku, invarianty
a konzistenci dat; read straně zbývá jediný úkol – dodat data v podobě, jakou vyžaduje obrazovka.
Každý model obsahuje jen to, co ke své práci potřebuje, a optimalizuje se nezávisle.
Na straně zápisu stojí normalizované relační schéma a Doctrine ORM entity s doménovou
logikou, na straně čtení denormalizovaná tabulka, Elasticsearch index nebo Redis cache –
cokoli, co nejlépe vyhovuje konkrétním dotazům. Z téhož oddělení plyne volnost při evoluci.
Read model jde kdykoli přebudovat (rebuild projekcí), přidat další pro nový use case nebo
změnit strukturu dotazu, aniž by se to dotklo write modelu.

Dvě další výhody:

- **Škálovatelnost** – Young uvádí, že v systémech, a zvlášť ve webových, odbaví dotazovací
  strana běžně o dva a více řádů víc operací než strana zápisu
  [[1]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf). CQRS umožňuje škálovat
  read stranu nezávisle (repliky, cache, CDN), bez dopadu na write stranu.
- **Testovatelnost** – Command handlery se testují jako doménová logika
  (given state → when command → then events/state), u query handlerů se ověřuje jen správnost
  vrácených dat. Obě odpovědnosti se v jedné testovací sadě nepletou.
  Viz kapitola [Testování DDD kódu](/testovani-ddd).

## 12.04 Výzvy a omezení CQRS {#challenges}

Kompromisy CQRS je lepší znát dřív, než se do něj pustíte.

Místo jednoho modelu existují dva nebo víc. Každý command i query si žádá vlastní
třídu, handler a často i vlastní datovou strukturu: kde by v CRUD stačila jedna
třída, vznikne jich několik. Oddělená úložiště přidávají synchronizaci: read model se musí
aktualizovat po každé změně write modelu. Selhání propagace (výpadek fronty, chyba
projektoru) vede k tomu, že se modely rozejdou.

Patří sem i eventual consistency. Mezi zápisem a aktualizací read modelu vzniká okno,
kdy uživatel po odeslání formuláře vidí „starou“ verzi dat. Vzory pro UI popisuje
[sekce Eventual Consistency](#eventual-consistency).

Poslední cenou je zaučení týmu. Kdo je zvyklý na jeden model pro všechny operace, musí
přemýšlet jinak a porozumět pojmům jako message bus, eventual consistency, idempotence
handlerů a projekce read modelu.

Technická kritéria přitom nejsou jedinou osou rozhodování. Udi Dahan, který vzor pomáhal
popularizovat, staví na **kolaborativnosti domény**: CQRS se vyplatí tam, kde více aktérů
mění tatáž data podle pravidel závislých na kontextu
[[6]](https://udidahan.com/2011/04/22/when-to-avoid-cqrs/). Nákupní košík mezi takové domény
nepatří, protože nikdo neupravuje košík někoho jiného. Podle Dahana proto pro CQRS nekandiduje
ani při extrémním poměru čtení k zápisu. Ke stejné opatrnosti vede Martin Fowler:
o vzoru se rozhoduje per Bounded Context, ne pro celý systém
[[7]](https://martinfowler.com/bliki/CQRS.html).

:::callout{type="warn"}
### Kdy nepoužívat CQRS {#when-not-to-use-cqrs-heading}

CQRS se nevyplatí, pokud:

- Vyvíjíte jednoduchou aplikaci s minimální doménovou logikou. Klasický CRUD
  s Doctrine ORM má méně tříd a kratší cestu od formuláře k databázi.
- Požadavky na čtení a zápis jsou téměř identické. CQRS se vyplácí, až když se datové
  struktury pro zápis a čtení výrazně liší.
- Doména není kolaborativní – nad týmiž daty pracuje vždy jeden aktér a souběžné změny
  spolu nekolidují. Dahan k tomu poznamenává, že většina týmů, které CQRS nasadily,
  to dělat neměla.
- Nemáte potřebu škálovat operace čtení a zápisu nezávisle – pokud celá aplikace
  běží na jednom serveru a zvládá zátěž, oddělená infrastruktura je zbytečná režie.
- Tým nemá zkušenosti s asynchronním zpracováním – problémy s eventual consistency
  bez předchozí praxe s distribuovanými systémy dokážou potrápit.

Dobrým kompromisem je začít s CQRS na úrovni 1 (oddělené handlery, sdílená databáze)
a rozšiřovat postupně. Viz také
[Anti-vzory – Over-engineering u jednoduchých aplikací](/anti-vzory#over-engineering).
:::

## 12.05 Symfony Messenger jako základ CQRS {#symfony-messenger}

Message bus není pro CQRS podmínkou. Oddělené command a query třídy volané přímo z controlleru
jsou plnohodnotná úroveň 1 a malé aplikaci stačí. Sběrnice se vyplatí, až když kolem
zpracování přibývá společná infrastruktura: transakce, validace, logování,
odložené vykonání. V Symfony tuto roli plní Messenger. Dedikované PHP knihovny z let 2014–2018
mezitím skončily (`broadway/broadway` je archivovaný) nebo roky nedostaly commit
(`prooph/service-bus`, `SimpleBus`). Volba se tím zúžila na Messenger, nebo vlastní tenkou
vrstvu nad kontejnerem.

Pro CQRS je na Messengeru podstatná možnost definovat **více message busů**: pro
příkazy, pro dotazy a pro doménové události. Každý bus má vlastní sadu middleware
a vlastní strategii zpracování. Dokumentace Symfony k tomu dodává podmínku,
kterou se vyplatí brát vážně: jeden bus je dobrý výchozí stav a další se přidává tehdy,
když potřebuje jiný middleware stack, ne proto, že to nějaký vzor doporučuje
[[8]](https://symfony.com/doc/current/messenger/multiple_buses.html). Konfigurace níže
podmínku splňuje – command bus obaluje handler do transakce, query bus ne.

:::diagram{fig="12.5-A" title="Symfony Messenger jako CQRS bus" src="images/diagrams/6_cqrs/diagram.svg"}
:::

:::callout{type="pattern"}
### Konfigurace Symfony Messenger pro CQRS {#messenger-config-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml (kanonická konfigurace knihy)"}
# config/packages/messenger.yaml
framework:
    messenger:
        # Tohle je kanonická konfigurace celé knihy. Kapitoly o Outboxu
        # a ságách z ní ukazují jen výřezy – jména transportů a busů
        # jsou všude stejná, aby šly poskládat do jednoho projektu.
        default_bus: command.bus

        # Konfigurace transportů. Příkazy a události mají vlastní frontu:
        # zahlcený report tak nebrzdí projekce a naopak. Nad doctrine://
        # frontu rozlišuje queue_name, bez něj by šlo o jednu a tutéž.
        transports:
            async_commands:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options: { queue_name: commands }
            async_events:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options: { queue_name: events }
            sync: 'sync://'

        # Konfigurace busů
        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction

            query.bus:
                middleware:
                    - validation

            # Doménové události: publish/subscribe, posluchačů může být 0..N
            event.bus:
                default_middleware:
                    enabled: true
                    allow_no_handlers: true

        # Směrování zpráv - mapuje konkrétní třídy nebo rozhraní na transport
        # POZOR: Messenger třídy ověřuje při kompilaci kontejneru. Neexistující
        # jméno shodí každý příkaz hláškou „Invalid Messenger routing
        # configuration: class or interface … not found“. Nechte tu jen zprávy,
        # které ve svém projektu opravdu máte.
        routing:
            # Asynchronně patří operace, na jejichž výsledek volající nečeká.
            # PlaceOrder mezi ně NEPATŘÍ: kontroler z 12.12 si z odpovědi
            # bere OrderId přes HandledStamp, a ten z jiného procesu
            # nedoputuje – požadavek by skončil na
            # „Call to a member function getResult() on null“.
            # Třída vzniká až v kapitole 15 (Outbox). Odkomentujte řádek
            # s kapitolou 15 – dřív by Messenger při kompilaci kontejneru
            # spadl na „class or interface … not found“.
            # App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent: async_events

            # Dotazy jsou zpracovány synchronně (výchozí, není třeba uvádět)
            # App\UserManagement\Profile\Query\GetUserProfile: sync
:::
:::

Konfigurace definuje tři transporty: `async_commands` a `async_events` pro zpracování přes frontu a `sync` pro okamžité
vykonání v témže procesu. Busy jsou také tři. `command.bus` pro příkazy má middleware
`doctrine_transaction`, tedy automatickou transakci kolem handleru. `query.bus` má jen validaci.
Transport `doctrine://default` dodává balíček `symfony/doctrine-messenger`; bez něj
Messenger hlásí „No transport supports Messenger DSN“. Pro AMQP je to obdobně
`symfony/amqp-messenger`.

`event.bus` slouží doménovým událostem a liší se v podstatném bodě: příkaz bez handleru
je chyba, událost bez posluchače legitimní stav. Proto `allow_no_handlers: true`; bez něj
Messenger vyhodí `NoHandlerForMessageException` u každé události, kterou zatím nikdo neodebírá.

Jakmile máte víc než jednu sběrnici, přestane stačit prostý type-hint na `MessageBusInterface`
a holý `#[AsMessageHandler]`. Type-hint dostane `default_bus` a handler se zaregistruje na
**všechny** sběrnice. Obojí se řeší explicitně:

:::code{language="php" filename="snippet.php"}
final readonly class OrderPlacedProjectorRegistration
{
    // Injektáž konkrétní sběrnice
    public function __construct(
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}
}

// Handler patřící na jednu sběrnici
#[AsMessageHandler(bus: 'event.bus')]
final readonly class OrderPlacedProjector { /* … */ }
:::

Na Outboxu je vidět, proč na tom záleží. Relay, který odešle doménovou událost
na `command.bus`, narazí na chybějící handler, vyčerpá retry a událost zahodí –
přesně to, čemu má Outbox bránit. Dostupné aliasy vypíše `debug:autowiring MessageBus`.

:::callout{type="warn"}
### `doctrine_transaction` middleware vs. „jeden agregát = jedna transakce“ {#doctrine-transaction-konflikt-heading}

Middleware `doctrine_transaction` obaluje **celý handler** do jedné transakce.
Když handler zavolá `save()` na dva různé agregáty, oba se zapíší atomicky. To odporuje
pravidlu z [Návrh agregátu – Transakční konzistence](/navrh-agregatu#transactional-consistency):
*„jeden command modifikuje právě jeden agregát“*.

Použitelné strategie jsou dvě:

- **Striktní DDD:** middleware zůstane zapnutý a **pravidlo „1 command = 1 agregát“
  hlídá code review**. Middleware pak slouží jen jako pojistka pro uložení outboxu
  a agregátu v jedné transakci uvnitř `save()` repozitáře. Kdo pravidlo poruší, toho
  transakce ochrání před nekonzistentními daty, ne před architektonickým dluhem.
- **Bez `doctrine_transaction`:** middleware se vypne a transakční hranici řídí
  repozitář explicitně přes `EntityManager::wrapInTransaction()` v metodě
  `save()`. Nastavení je pracnější, ale platí „1 save = 1 transakce“ a víc agregátů
  v jednom commandu atomicky uložit nejde – což je správně.

Průvodce dál pracuje se zapnutým middleware. Pro většinu projektů je to
pragmatický kompromis a pravidlo „1 agregát = 1 transakce“ pak hlídá code review.
:::

:::callout{type="note"}
### Proč dva oddělené busy? {#proc-dva-busy-heading}

Oddělení command a query busu stojí na rozdílném chování obou stran:

- **Různý middleware** – Command bus potřebuje `doctrine_transaction` jako pojistku,
  query bus data jen čte.
- **Různé transporty** – Příkazy lze směrovat na async transport (frontu).
  Dotazy zůstávají synchronní, protože uživatel čeká na odpověď.
- **Čitelnost volání** – Controller s `$commandBus` v konstruktoru mění stav, controller
  s `$queryBus` čte. Rozdíl je vidět na první pohled.

Samotné oddělení busů ale záměnu zprávy nezastaví. Handler je ve výchozím stavu registrovaný
na **všech** busech, takže dotaz odeslaný na command bus doputuje ke svému handleru i
s transakcí kolem. Vazbu na jediný bus vynutí až parametr atributu `#[AsMessageHandler(bus: 'command.bus')]`,
případně tag `messenger.message_handler` s klíčem `bus`.
:::

:::callout{type="note"}
### Jak vybrat příkazy pro asynchronní zpracování {#async-poznamka-heading}

**Asynchronně** patří operace, na jejichž výsledek uživatel nečeká –
odesílání e-mailů, generování reportů, aktualizace read modelů, notifikace.

**Synchronně** zůstávají operace, které potřebují okamžitou zpětnou vazbu: registrace uživatele,
vytvoření objednávky, přihlášení. Uživatel čeká na odpověď (úspěch, nebo chybu validace)
a potřebuje ji hned.
:::

## 12.06 Implementace Commands {#commands}

V Symfony 8 je command jednoduchá PHP třída: immutabilní datový objekt (DTO), který nese
všechna data potřebná pro vykonání operace. Doménovou logiku neobsahuje, jen data přepravuje.

Dobře navržený command:

- je **immutabilní** (`readonly` properties) a po vytvoření se nemění;
- nese **validační atributy**, takže ho middleware `validation` na command busu zkontroluje dřív, než dorazí k handleru;
- pojmenováním vyjadřuje **záměr** – `RegisterUser`, `PlaceOrder`, `CancelSubscription`, ne `SaveUser` nebo `UpdateOrder`;
- pracuje s primitivními typy (string, int, float) nebo serializovatelnými hodnotovými objekty (`OrderId`, `Money`), protože musí bezpečně projít asynchronním kanálem.

Záměr se přitom nebere odnikud. U Younga stojí před CQRS **task-based UI**, rozhraní složené
z úloh („Změnit doručovací adresu“, „Stornovat objednávku“), ne formulář nad entitou s tlačítkem
Uložit. Formulář mapovaný na entitu vyprodukuje jediný command `UpdateOrder` a informace o tom,
co uživatel vlastně chtěl, se ztratí ještě před vstupem do domény. Úlohy v UI přitom obvykle
odpovídají doménovým událostem, které tým našel při [Event Stormingu](/event-storming).

:::callout{type="pattern"}
### PHP: Implementace příkazu v Symfony 8 {#command-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUser.php (týž soubor jako v kap. 10)"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Příkaz pro registraci nového uživatele.
 * Immutabilní DTO – neslouží k doménové logice, pouze přenáší data.
 */
final readonly class RegisterUser
{
    public function __construct(
        // Trim je tu schválně: UserName si vstup ořízne, takže bez něj
        // by „  a  “ prošlo délkovou kontrolou a spadlo až v hodnotovém
        // objektu jako 500. HTML formulář to maskuje, protože TextType
        // trimuje sám – JSON endpoint ne.
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(min: 2, max: 100, normalizer: 'trim')]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT)]
        public string $email,

        // Hranice musí sedět s HashedPassword::fromPlainText(). Volnější
        // pravidlo tady by pustilo heslo, které pak agregát odmítne –
        // a uživatel by místo hlášky u pole dostal chybu z domény.
        #[Assert\NotBlank]
        #[Assert\Length(min: 12)]
        public string $password,
    ) {}
}
:::
:::

Validační atributy na commandu a doménová pravidla v handleru řeší dvě různé věci. Dahan
odděluje **validaci**, která na kontextu nezávisí (je e-mail e-mailem, má heslo dost znaků),
od **business rules**, které na něm závisí (tento e-mail už někdo použil, zákazník vyčerpal
denní limit). Formálně validní command proto může doménově selhat, protože se mezitím změnily
podmínky. Middleware `validation` doménovou kontrolu nenahrazuje, jen odfiltruje zprávy,
které nedávají smysl ani formálně.

:::callout{type="warn"}
### Mají commands vracet hodnotu? {#command-navratova-hodnota-heading}

Ve striktním CQS commands nevracejí žádná data. Vědomé porušení pravidla ale připouští
i Fowler: `pop()` na zásobníku mění stav a zároveň vrací hodnotu. Principu se podle svých slov
drží, dokud může, ale kvůli použitelnému `pop()` ho poruší
[[2]](https://martinfowler.com/bliki/CommandQuerySeparation.html). I v CQRS existují
legitimní scénáře, kdy je užitečné vrátit aspoň identifikátor nově vytvořeného záznamu.
Běžné přístupy jsou dva:

- **ID generovat na klientovi** – Command nese `$userId` jako UUID
  vygenerované před dispatchem a handler ho použije. Klient zná ID okamžitě a command nemusí
  nic vracet. Z hlediska CQS je to čistší varianta a funguje i s asynchronním transportem.
- **ID vracet z handleru** – Handler vrátí ID přes `HandledStamp`.
  Porušuje striktní CQS, ale identitu přiděluje továrna agregátu, ne volající; tak to dělá
  `PlaceOrderController` v [sekci 12.12](#ec-priklad-heading). **Nefunguje pro asynchronní transport** – handler běží v jiném procesu a výsledek přes HandledStamp do původního requestu nedoputuje.
:::

## 12.07 Implementace Queries {#queries}

Query se od commandu liší směrem toku dat: nemění stav systému, jen čte. Implementace je podobná,
immutabilní DTO. Rozdíl je v tom, že query **vždy vrací hodnotu**, kterou handler předá přes `HandledStamp`.

:::callout{type="pattern"}
### PHP: Implementace dotazu v Symfony 8 {#query-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/Query/GetUserProfile.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class GetUserProfile
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId
    ) {
    }
}
:::
:::

Dotaz nese jediné pole: ID uživatele, jehož profil se má načíst. Nevalidní UUID odmítne
middleware `validation` ještě před zpracováním.

:::callout{type="note"}
### Queries s filtrováním a stránkováním {#query-slozitejsi-heading}

Reálné aplikace potřebují víc než „dej mi záznam podle ID“. Query může nést filtrovací
kritéria, řazení a stránkování:

:::code{language="php" filename="src/Ordering/Application/Query/ListOrders.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class ListOrders
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $customerId,

        public readonly ?string $status = null,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 20,

        #[Assert\PositiveOrZero]
        public readonly int $offset = 0,

        public readonly string $sortBy = 'createdAt',
        public readonly string $sortDirection = 'DESC',
    ) {
    }
}
:::
:::

## 12.08 Implementace Handlers {#handlers}

V handleru se zpráva potká s logikou. V Symfony 8 jde o třídu s atributem
`AsMessageHandler` a metodou `__invoke()`; Messenger ji se zprávou spojí podle type-hintu parametru.

Command handler a query handler mají odlišnou odpovědnost:

- **Command handler** – Načte agregát z repozitáře, zavolá na něm doménovou metodu
  (ta hlídá invarianty) a uloží změny. Může emitovat doménové události.
  Pracuje s **doménovým modelem** (entity, value objects, repozitáře).
- **Query handler** – Čte data z optimalizovaného zdroje (denormalizovaná tabulka,
  Elasticsearch, cache) a vrací je jako ViewModel. **Doménový model záměrně obchází**,
  protože ten pro čtení optimalizovaný není.

:::callout{type="pattern"}
### PHP: Command handler – RegisterUserHandler {#command-handler-heading}

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUserHandler.php (týž soubor jako v kap. 10)"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use App\UserManagement\Domain\Exception\DuplicateEmailException;
use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\UserName;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = Email::fromUserInput($command->email);

        $user = User::register(
            UserId::generate(),
            new UserName($command->name),
            $email,
            HashedPassword::fromPlainText($command->password),
        );

        try {
            $this->userRepository->save($user);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw DuplicateEmailException::with($email, $e);
        }

        // Bez tohohle kroku zůstane UserRegistered ležet v agregátu a nikdo
        // se o registraci nedozví – ani posluchač, který zakládá přihlašovací
        // záznam. Uživatel se pak nemůže přihlásit a nic přitom nespadne.
        foreach ($user->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
:::
:::

:::callout{type="note"}
**Pozn.:** Handler je tentýž, jaký zavádí kapitola
[Implementace v Symfony](/implementace-v-symfony); CQRS ho jen zařadí na `command.bus`.
Za pozornost stojí, co v něm **není**: kontrola duplicity přes `findByEmail()`. Ta by
proti souběžným registracím nechránila, protože mezi dotazem a zápisem se vejde druhý
požadavek. Unikátnost proto vynucuje unique constraint, viz
[Race condition v naivní variantě](/implementace-v-symfony#register-race-heading).
:::

:::callout{type="pattern"}
### PHP: Query handler – GetUserProfileHandler {#query-handler-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/Query/GetUserProfileHandler.php (verze nad read modelem)"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Query;

use App\UserManagement\Profile\ReadModel\UserProfileReadRepository;
use App\UserManagement\Profile\ViewModel\UserProfileViewModel;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserProfileHandler
{
    public function __construct(
        private UserProfileReadRepository $readRepository
    ) {
    }

    public function __invoke(GetUserProfile $query): ?UserProfileViewModel
    {
        return $this->readRepository->findById($query->userId);
    }
}
:::
:::

Rozdíl je vidět přímo v závislostech. Command handler pracuje s doménovým modelem
(`UserRepository`, entita `User`, value objects). Query handler sahá do **read repozitáře**
(`UserProfileReadRepository`), který vrací rovnou ViewModel – jednoduchou datovou strukturu
pro prezentaci – a doménovým modelem vůbec neprochází.

Rozhraní read repozitáře patří do read strany, ne do domény, a je záměrně úzké:

:::code{language="php" filename="src/UserManagement/Profile/ReadModel/UserProfileReadRepository.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\ReadModel;

use App\UserManagement\Profile\ViewModel\UserProfileViewModel;

interface UserProfileReadRepository
{
    public function findById(string $userId): ?UserProfileViewModel;
}
:::

## 12.09 ViewModely a Read Modely {#view-models}

ViewModel (nebo Read Model) je datová struktura navržená pro konkrétní dotaz
nebo obrazovku. Doménovou logiku neobsahuje, je čistě prezentační. Doménová entita `User`
chrání invarianty a zapouzdřuje chování; `UserProfileViewModel` nese přesně ta data,
která potřebuje šablona nebo API endpoint.

:::callout{type="pattern"}
### PHP: UserProfileViewModel {#viewmodel-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/ViewModel/UserProfileViewModel.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\ViewModel;

/**
 * Read model pro zobrazení uživatelského profilu.
 * Obsahuje pouze data potřebná pro prezentaci - žádná doménová logika.
 */
final readonly class UserProfileViewModel
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public \DateTimeImmutable $registeredAt,
        public int $totalOrders,
        public string $membershipTier,
    ) {
    }
}
:::
:::

ViewModel často obsahuje **data z více agregátů** – v příkladu výše kombinuje
údaje o uživateli s počtem objednávek a členskou úrovní. Přes doménový model by stejný pohled
znamenal načíst uživatele, jeho objednávky a spočítat úroveň, což je pomalé a porušuje hranice
[agregátů](/zakladni-koncepty#aggregates). Read model má tato data připravená
v denormalizované podobě.

:::callout{type="pattern"}
### PHP: Read repozitář s přímým SQL (Doctrine DBAL) {#read-repository-example-heading}

:::code{language="php" filename="src/UserManagement/Infrastructure/ReadModel/DbalUserProfileReadRepository.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\ReadModel;

use App\UserManagement\Profile\ReadModel\UserProfileReadRepository;
use App\UserManagement\Profile\ViewModel\UserProfileViewModel;
use Doctrine\DBAL\Connection;

final class DbalUserProfileReadRepository implements UserProfileReadRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function findById(string $userId): ?UserProfileViewModel
    {
        $row = $this->connection->fetchAssociative(
            'SELECT u.id, u.name_value AS name, u.email, u.created_at,
                    (SELECT COUNT(*) FROM orders o
                      WHERE o.customer_id = u.id) AS total_orders,
                    -- Tabulka memberships patří jinému kontextu. Kdo ji nemá,
                    -- nahradí celý COALESCE za `:defaultTier AS membership_tier`
                    -- – sloupec musí zůstat, ViewModel ho v konstruktoru
                    -- vyžaduje. Ukázka je tu kvůli tvaru dotazu, ne kvůli
                    -- konkrétnímu schématu.
                    COALESCE(
                        (SELECT MAX(m.tier) FROM memberships m
                          WHERE m.user_id = u.id),
                        :defaultTier
                    ) AS membership_tier
               FROM users u
              WHERE u.id = :userId',
            ['userId' => $userId, 'defaultTier' => 'standard'],
        );

        if (!$row) {
            return null;
        }

        return new UserProfileViewModel(
            userId: $row['id'],
            name: $row['name'],
            email: $row['email'],
            registeredAt: new \DateTimeImmutable($row['created_at']),
            totalOrders: (int) $row['total_orders'],
            membershipTier: $row['membership_tier'],
        );
    }
}
:::
:::

:::callout{type="note"}
### Kolik Doctrine ORM na read straně? {#read-model-poznamka-heading}

Doctrine ORM je stavěný pro práci s doménovým modelem: mapuje entity, řeší vztahy,
lazy loading, identity map a unit of work. Read model z toho nepotřebuje nic. Má
**načíst data a namapovat je na ViewModel**, takže hydratace spravovaných entit je
režie bez užitku.

Mezi ručním SQL a plnou hydratací entit ale leží střední cesta. Varianty jsou tři:

- **Doctrine DBAL** – přímý SQL přes `Connection`, plná kontrola nad dotazem a žádná
  hydratace navíc. Sedí na denormalizované tabulky a agregační dotazy, jako v příkladu výše.
- **DQL s `NEW` expression** – dotaz zůstává v mapovaných entitách a názvech polí,
  ale výsledek se hydratuje rovnou do konstruktoru ViewModelu. Žádná entita se nedostane
  do identity map. Tuto variantu ukazuje kapitola
  [Výkonnostní aspekty](/vykonnostni-aspekty#read-model-optimalizace).
- **Doménový repozitář vracející entity** – pro read stranu nevhodný: N+1 dotazy, spravované
  objekty v paměti a pokušení volat doménové metody ze šablony.
:::

## 12.10 Implementace Command a Query Buses {#buses}

Zbývá dopravit příkazy a dotazy ke správnému handleru. Vedle atributu `#[Target]` z 12.05
funguje v Symfony 8 i named autowiring: parametr konstruktoru pojmenovaný podle busu
z `messenger.yaml` (`$commandBus` pro `command.bus`) dostane právě tento bus:

:::callout{type="pattern"}
### PHP: Použití command busu v controlleru {#buses-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Controller/RegistrationController.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Controller;

use App\UserManagement\Domain\Exception\DuplicateEmailException;
use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {
    }

    private const TEMPLATE = '@UserManagement/Registration/View/registration.html.twig';

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $template = self::TEMPLATE;
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $command = new RegisterUser(
                $data['name'],
                $data['email'],
                $data['password']
            );

            try {
                $this->commandBus->dispatch($command);

                $this->addFlash('success', 'Váš účet byl vytvořen. Nyní se můžete přihlásit.');

                return $this->redirectToRoute('login');
            } catch (HandlerFailedException $e) {
                // getWrappedExceptions() umí filtrovat podle typu výjimky
                $duplicates = $e->getWrappedExceptions(DuplicateEmailException::class);

                if ($duplicates !== []) {
                    $this->addFlash('error', reset($duplicates)->getMessage());

                    return $this->render($template, ['form' => $form->createView()]);
                }

                throw $e; // neznámou chybu nemaskovat
            } catch (ValidationFailedException $e) {
                // Validaci commandu dělá middleware na sběrnici, ne formulář,
                // a běží PŘED handlerem – výjimka proto nepřijde zabalená
                // v HandlerFailedException a potřebuje vlastní větev.
                // Pozor na jmenný prostor: middleware hází variantu
                // z Messengeru, ne z Validatoru.
                foreach ($e->getViolations() as $violation) {
                    $form->get($violation->getPropertyPath())->addError(
                        new FormError($violation->getMessage()),
                    );
                }
            }
        }

        return $this->render($template, ['form' => $form->createView()]);
    }
}
:::
:::

Profil vykresluje ViewModel, ne agregát, takže šablona nic nedopočítává:

:::code{language="twig" filename="src/UserManagement/Profile/View/profile.html.twig"}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>{{ profile.name }}</h1>
    <dl>
        <dt>E-mail</dt>       <dd>{{ profile.email }}</dd>
        <dt>Registrace</dt>   <dd>{{ profile.registeredAt|date('j. n. Y') }}</dd>
        <dt>Objednávek</dt>   <dd>{{ profile.totalOrders }}</dd>
        <dt>Úroveň</dt>       <dd>{{ profile.membershipTier }}</dd>
    </dl>
{% endblock %}
:::

Registrační šablona je běžný Twig formulář; `form_row` vykreslí i chyby, které do polí
vložil kontroler z validace commandu:

:::code{language="twig" filename="src/UserManagement/Registration/View/registration.html.twig"}
{% extends 'base.html.twig' %}

{% block body %}
    {# Bez tohohle bloku se uživatel o kolizi e-mailu nedozví: kontroler
       ji hlásí flashem, ne chybou u pole. #}
    {% for message in app.flashes('error') %}
        <p class="error">{{ message }}</p>
    {% endfor %}

    {{ form_start(form) }}
        {# form_errors vypíše chyby na kořeni formuláře, typicky CSRF. #}
        {{ form_errors(form) }}
        {{ form_row(form.name) }}
        {{ form_row(form.email) }}
        {{ form_row(form.password) }}
        <button type="submit">Registrovat</button>
    {{ form_end(form) }}
{% endblock %}
:::

Vertikální řez potřebuje ke kontroleru dvě věci navíc. Šablony leží u feature, ne
v centrálním `templates/`, takže Twig musí jejich adresář znát pod jménem. A formulář
je obyčejný `FormType` vedle nich:

:::code{language="yaml" filename="config/packages/twig.yaml (doplněk k receptu)"}
# Do souboru z receptu symfony/twig-bundle se doplňuje jen klíč `paths`.
# Zbytek (default_path, file_name_pattern…) zůstává, jak ho recept založil.
twig:
    paths:
        # Bez tohohle řádku Twig hlásí „There are no registered paths
        # for namespace UserManagement“ a šablona u feature se nenajde.
        '%kernel.project_dir%/src/UserManagement': UserManagement
:::

:::code{language="php" filename="src/UserManagement/Registration/Form/RegistrationFormType.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // NotBlank tu není duplicita pravidla z commandu. Bez data_class
        // formulář constrainty commandu nepřebírá a prázdné pole přijde
        // do konstruktoru jako null – tedy 500 dřív, než se validace spustí.
        $builder
            ->add('name', TextType::class, [
                'label' => 'Jméno',
                'constraints' => [new NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [new NotBlank()],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Heslo',
                'constraints' => [new NotBlank()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Bez data_class vrací formulář pole, a to je zde záměr: command
        // má promované readonly vlastnosti, do kterých PropertyAccess
        // zapsat neumí („is a promoted readonly property“). Kontroler
        // z pole postaví command sám.
        $resolver->setDefaults(['data_class' => null]);
    }
}
:::

:::callout{type="warn"}
### Messenger balí výjimky {#messenger-bali-vyjimky-heading}

Synchronní Messenger nepropaguje výjimku z handleru přímo, ale balí ji do
`HandlerFailedException`. Blok `catch (DuplicateEmailException $e)` kolem `dispatch()`
by proto nikdy nic nechytil. Controller chytá obálku a vytáhne z ní jen výjimky,
na které umí reagovat. Metoda `getWrappedExceptions()` bere jako první argument název
třídy a druhým rozbalí i vnořené obálky. Ostatní výjimky putují dál nezměněné.
Podrobnější rozbor včetně dekorátoru busu, který rozbalování centralizuje, obsahuje kapitola
[Implementace v Symfony](/implementace-v-symfony#handler-failed-exception-heading).
:::

:::callout{type="pattern"}
### PHP: Použití query busu v controlleru {#query-bus-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/Controller/ProfileController.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Controller;

use App\UserManagement\Profile\Query\GetUserProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use App\Identity\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProfileController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus
    ) {
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(#[CurrentUser] SecurityUser $user): Response
    {
        // getUserIdentifier() vrací e-mail, kterým se uživatel přihlašuje –
        // ne identitu agregátu. Read model se ptá po customerId, takže
        // type-hint míří na konkrétní třídu. Atribut #[CurrentUser] je
        // povinný: SecurityUser je Doctrine entita, takže bez něj se
        // resolver nespustí a argument se nedá sestavit.
        $query = new GetUserProfile($user->customerId()->value);

        $envelope = $this->queryBus->dispatch($query);
        $profile = $envelope->last(HandledStamp::class)->getResult();

        if (!$profile) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('@UserManagement/Profile/View/profile.html.twig', [
            'profile' => $profile,
        ]);
    }
}
:::
:::

Oba controllery tedy dostanou svůj bus podle názvu parametru: `$commandBus` míří na
`command.bus`, `$queryBus` na `query.bus`.

:::callout{type="pattern"}
### Bezpečnější odběr výsledku: vlastní `QueryBus` {#query-bus-handle-trait-heading}

Zápis `$envelope->last(HandledStamp::class)->getResult()` mlčky předpokládá, že zprávu
obsloužil právě jeden handler. Když handler chybí, vrátí `last()` hodnotu `null`
a controller spadne na volání metody nad `null`. Messenger na to má `HandleTrait`, jehož
metoda `handle()` výsledek vrátí a zároveň ověří počet obsloužení: nula i víc než jeden handler
skončí `LogicException` se srozumitelnou hláškou.

:::code{language="php" filename="src/SharedKernel/Application/Query/QueryBus.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Application\Query;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class QueryBus
{
    use HandleTrait;

    public function __construct(
        #[Target('query.bus')]
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function ask(object $query): mixed
    {
        return $this->handle($query);
    }
}
:::

Controller pak injektuje `QueryBus` a píše `$this->queryBus->ask($query)` bez práce
se stampy. Atribut `#[Target]` adresuje bus nezávisle na názvu parametru, což se hodí tam,
kde se název řídí doménou, ne konfigurací.
:::

Tím je základní infrastruktura CQRS – příkazy, dotazy, handlery a busy – kompletní.
Další sekce řeší optimalizaci read strany, eventual consistency a provoz
v asynchronním prostředí.

## 12.11 Optimalizace Read Modelů {#read-model-optimalizace}

Read strana má volnou ruku ve výběru struktury. Write model drží normalizaci kvůli konzistenci dat;
read model může jít opačným směrem a denormalizovat data přesně do tvaru, který obrazovka
nebo API endpoint očekává.

Ještě před volbou strategie rozhoduje jeden provozní detail: **denormalizované tabulky nejsou
ORM entity.** Doctrine o nich neví, takže je `doctrine:schema:update` navrhne zahodit
a `doctrine:schema:validate` hlásí nesoulad schématu. Vytvářejí se migrací a z porovnávání
schématu je vyřadí filtr:

:::code{language="yaml" filename="config/packages/doctrine.yaml (výřez: read model)"}
doctrine:
    dbal:
        # Tabulky read modelů spravují migrace, ne ORM. Každou novou
        # projekci je nutné do výčtu doplnit, jinak ji Doctrine při
        # dalším diffu navrhne zahodit.
        schema_filter: '~^(?!order_dashboard|reporting_orders)~'
:::

Blok patří do stejného `doctrine.yaml`, kde už leží `mappings`, `naming_strategy`
a vlastní typy z [kapitoly o agregátech](/navrh-agregatu#symfony-doctrine); `url`
z receptu zůstává. Kniha konfiguraci Doctriny skládá postupně, v projektu je to ale
jeden soubor.

### Strategie optimalizace read modelů

:::callout{type="note"}
### Přehled strategií {#read-strategie-heading}

| Strategie | Popis | Vhodné pro | Složitost |
|---|---|---|---|
| Přímý SQL (DBAL) | Query handler čte z téže DB přes Doctrine DBAL, obchází ORM | Většinu aplikací na úrovni 1–2 | Nízká |
| DQL s `NEW` expression | Dotaz nad mapovanými entitami, hydratace přímo do ViewModelu | Read modely blízké struktuře write modelu | Nízká |
| Denormalizované tabulky | Separátní tabulky s předpočítanými daty, aktualizované přes eventy | Složité dashboard dotazy, reporting | Střední |
| Materialized views (DB) | Databázové materialized views refreshované periodicky nebo triggerem | Agregační dotazy nad velkými daty | Střední |
| Elasticsearch / Meilisearch | Fulltextový engine jako read store, plněný asynchronně z eventů | Fulltextové vyhledávání, faceted search | Vysoká |
| Redis cache | Hotová data serializovaná do Redis, invalidace přes eventy | Vysoká čtecí zátěž, nízká latence | Střední |
:::

### Denormalizované tabulky jako read model

V praxi nejrozšířenější strategií je **denormalizovaná tabulka** s daty předpočítanými
pro jedinou obrazovku či endpoint. Aktualizuje se asynchronně přes doménové události
a vzniká migrací, ne přes `schema:update` – Doctrine o ní neví a bez `schema_filter`
výše by ji navrhla zahodit:

:::code{language="php" filename="migrations/Version20260906090000.php"}
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Read model: order_dashboard';
    }

    public function up(Schema $schema): void
    {
        // DDL pro PostgreSQL. Read model není entita, takže ho
        // `migrations:diff` nevygeneruje – tahle migrace se píše ručně
        // a pro jinou platformu se ručně i přepisuje: MySQL chce
        // CHAR(36) místo UUID, DATETIME(6) místo TIMESTAMP(6) a upsert
        // v projektoru níže zapisuje přes ON DUPLICATE KEY UPDATE.
        $this->addSql(<<<'SQL'
            CREATE TABLE order_dashboard (
                order_id        UUID          NOT NULL,
                customer_id     UUID          NOT NULL,
                total_amount    INT           NOT NULL,
                status          VARCHAR(32)   NOT NULL,
                shipment_id     UUID          DEFAULT NULL,
                placed_at       TIMESTAMP(0)  NOT NULL,
                -- Mikrosekundy nejsou kosmetika: na porovnání updated_at
                -- stojí ochrana proti opožděné události a dvě události
                -- jednoho agregátu běžně spadnou do téže vteřiny.
                updated_at      TIMESTAMP(6)  NOT NULL,
                -- ON CONFLICT (order_id) v projektoru se opírá právě o tento klíč.
                PRIMARY KEY (order_id)
            )
        SQL);

        // Dashboard se řadí podle data a filtruje podle stavu.
        $this->addSql('CREATE INDEX idx_dashboard_status_placed
            ON order_dashboard (status, placed_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_dashboard');
    }
}
:::

Sloupec `status` nese slovník obrazovky, ne hodnoty enumu `OrderStatus`. Objednávku,
kterou agregát drží ve stavu `Confirmed`, dashboard zobrazuje jako `placed`. Read model
smí mít vlastní popisky, protože ho nikdo nepoužívá k rozhodování o přechodech.

:::callout{type="pattern"}
### PHP: Projektor aktualizující denormalizovanou tabulku {#denorm-projekce-heading}

:::code{language="php" filename="src/Ordering/Infrastructure/Projection/OrderDashboardProjector.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Projection;

use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Ordering\Domain\Event\OrderShipped;
use App\Ordering\Domain\Event\OrderCancelled;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Asynchronní projektor: naslouchá doménovým událostem a aktualizuje
 * denormalizovanou tabulku order_dashboard, optimalizovanou pro
 * obrazovku "Přehled objednávek".
 */
// Priorita není kosmetika. Na synchronní sběrnici běží posluchači v pořadí
// registrace a Process Manager z kapitoly o ságách odebírá tutéž událost.
// Bez přednosti by sága proběhla celá dřív, než projekce založí řádek,
// a její UPDATE by pak netrefil nic. Nic nespadne – dashboard jen zamrzne
// na „placed“.
#[AsMessageHandler(bus: 'event.bus', priority: 10)]
final class OrderDashboardProjector
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(
        OrderPlacedIntegrationEvent|OrderShipped|OrderCancelled $event,
    ): void
    {
        match (true) {
            $event instanceof OrderPlacedIntegrationEvent => $this->onOrderPlaced($event),
            $event instanceof OrderShipped => $this->onOrderShipped($event),
            $event instanceof OrderCancelled => $this->onOrderCancelled($event),
        };
    }

    private function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        $this->connection->executeStatement(
            'INSERT INTO order_dashboard
                (order_id, customer_id, total_amount, status, placed_at, updated_at)
             VALUES (:orderId, :customerId, :totalAmount, :status, :placedAt, :updatedAt)
             -- ON CONFLICT je PostgreSQL i SQLite; MySQL má
             -- ON DUPLICATE KEY UPDATE … = VALUES(…). Upsert není přenositelný.
             -- Bez podmínky by opakované doručení vrátilo odeslanou
             -- objednávku zpět na „placed“. Řádek se přepíše jen tehdy,
             -- když je nová událost novější než ta zapsaná.
             ON CONFLICT (order_id) DO UPDATE SET
                status = excluded.status, updated_at = excluded.updated_at
             WHERE order_dashboard.updated_at < excluded.updated_at',
            [
                'orderId'      => $event->orderId,
                'customerId'   => $event->customerId,
                'totalAmount'  => $event->totalAmountCents,
                'status'       => 'placed',
                'placedAt'     => $event->occurredAt->format('Y-m-d H:i:s.u'),
                'updatedAt'    => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );
    }

    private function onOrderShipped(OrderShipped $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_dashboard
                SET status = :status,
                    shipment_id = :shipmentId,
                    updated_at = :updatedAt
              WHERE order_id = :orderId
                AND updated_at < :updatedAt',
            [
                // Událost nese OrderId, DBAL do dotazu potřebuje skalár.
                'orderId'    => $event->orderId->value,
                'status'     => 'shipped',
                'shipmentId' => $event->shipmentId->value,
                'updatedAt'  => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );
    }

    private function onOrderCancelled(OrderCancelled $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_dashboard
                SET status = :status, updated_at = :updatedAt
              WHERE order_id = :orderId
                AND updated_at < :updatedAt',
            [
                'orderId'   => $event->orderId->value,
                'status'    => 'cancelled',
                'updatedAt' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );
    }
}
:::
:::

:::callout{type="note"}
### Idempotence projektorů {#idempotence-heading}

Při asynchronním zpracování může táž událost dorazit **více než jednou**
(at-least-once delivery). Projektor proto musí být **idempotentní**: opakované
zpracování téže události nesmí vést k nesprávným datům. Samotný upsert nestačí.
`ON CONFLICT … DO UPDATE` je last-write-wins, takže opakované doručení `OrderPlaced`
po `OrderShipped` vrátí řádek zpět na `placed` a dashboard začne lhát. Obě věty
v ukázce proto nesou podmínku `updated_at < :updatedAt` a zápis projde jen tehdy, když je
událost novější než to, co v řádku už je. Čas se do dotazu předává jako řetězec, takže na
formátu záleží: `Y-m-d H:i:s` se sekundovou přesností podmínku obrátí proti vám. Dvě události
téhož agregátu běžně spadnou do jedné vteřiny a `<` je pak nepravdivé i pro legitimní přechod:
objednávka se odešle, ale dashboard mlčky zůstane na `placed`. Proto `.u` ve formátu
a `TIMESTAMP(6)` ve sloupci.

Jedna výhrada: událost, která projde outboxem, se serializuje přes `DateTimeNormalizer`
a ten ve výchozím nastavení píše RFC 3339 **bez** zlomků sekundy. Mikrosekundy se cestou
ztratí, řádek dostane `.000000` a ochrana rozliší nejvýš vteřiny. Kdo ji potřebuje
i za outboxem, nastaví normalizeru `DateTimeNormalizer::FORMAT_KEY` na `'Y-m-d\TH:i:s.uP'`.
Alternativní přístupy:

- **Sledování pozice** – projektor si ukládá pozici posledního zpracovaného
  eventu (event ID nebo sequence number) a ignoruje události se stejnou nebo nižší pozicí.
- **Upsert/Merge** – `INSERT ... ON CONFLICT DO UPDATE` (PostgreSQL)
  nebo `REPLACE INTO` (MySQL). Jednoduchý, ale méně flexibilní.

Od Symfony 7.3 má komponenta i hotový nástroj. `DeduplicateMiddleware` staví na
`symfony/lock`: zpráva nese `DeduplicateStamp` s klíčem, TTL (výchozích 300 sekund)
a volbou `onlyDeduplicateInQueue`. Zámek vzniká při odeslání, takže druhé odeslání téže
zprávy, dokud první čeká ve frontě nebo se zpracovává, middleware zahodí. Databázovou
idempotenci to ale nenahradí. Opakované doručení už přijaté zprávy (retry, pád workeru
před ACK) zámek nezastaví, stejně jako přehrání celého streamu při rebuildu projekce.

Idempotenci projektorů a další praktické problémy rozebírá kapitola
[Event Sourcing – Praktické problémy projekcí](/event-sourcing#prakticke-problemy-projekci).
:::

### Kdo doménové události odešle

Projektor výše předpokládá, že mu události `OrderPlaced` či `OrderShipped` někdo
doručí. V nejjednodušší podobě je po `flush()` vyzvedne aplikační vrstva z agregátu
metodou `releaseEvents()` a odešle na event bus; mechanismus popisuje
sekce [Agregát a doménové události: lifecycle](/zakladni-koncepty#aggregate-root-lifecycle).
Pro vývoj a méně kritické projekce tato synchronní cesta stačí.

Slabé místo má jedno: dispatch po flushi není atomický. Spadne-li proces mezi commitem
transakce a odesláním do fronty, událost se ztratí a projekce se tiše rozejde s write
modelem. Produkční řešení ukládá události do outbox tabulky ve stejné transakci jako
agregát a do fronty je publikuje samostatný relay proces – podrobně v kapitole
[Outbox Pattern](/outbox-pattern).

### Rebuild projekcí

Asynchronní projekce dovolují **kompletní rebuild read modelu**.
Když se změní struktura denormalizované tabulky (nový sloupec, jiný formát dat), stačí:

1. Vytvořit novou verzi projekční tabulky.
2. Přehrát všechny relevantní události přes projektor.
3. Přepnout read dotazy na novou tabulku.
4. Smazat starou tabulku.

Rebuild funguje, jen když jsou zdrojové události stále dostupné
(v [Event Store](/event-sourcing) nebo v message logu).
Bez Event Sourcingu je rebuild možný, ale potřebuje jiný zdroj dat
(např. change data capture z write databáze).

## 12.12 Eventual Consistency v praxi {#eventual-consistency}

Eventual consistency je při zavádění CQRS nejčastějším zdrojem nejistoty. Při asynchronní
propagaci změn z write strany na read stranu existuje **časové okno** (typicky
milisekundy až jednotky sekund), kdy read model ještě neodráží poslední zápis. Uživatel
odešle formulář, dostane potvrzení o úspěchu, ale seznam na další stránce nový záznam
ještě neukazuje.

Chyba to není: jde o **vlastnost distribuované architektury**.
Diagram zachycuje datový tok od zápisu přes asynchronní propagaci po čtení
a zvýrazňuje okno, ve kterém se eventual consistency projeví:

:::diagram{fig="12.12-A" title="Eventual consistency v CQRS toku" src="images/diagrams/6_cqrs/eventual_consistency.svg"}
:::

Sekvence níže ukazuje v čase, kdy uživatel dostane 404, přestože command proběhl úspěšně:

:::diagram{fig="12.12-B" title="Okno zastaralosti – kdy GET vrátí 404 po úspěšném POST" src="images/diagrams/6_cqrs/staleness_window.svg"}
:::

### Strategie řešení v UI

:::callout{type="pattern"}
### Přehled strategií pro práci s eventual consistency {#ec-strategie-heading}

| Strategie | Princip | Implementace |
|---|---|---|
| Optimistická aktualizace UI | UI okamžitě zobrazí nový stav, aniž čeká na read model | Frontend (JavaScript) přidá záznam do seznamu lokálně po úspěšném POST |
| Post-Redirect-Get s flash | Po command se provede redirect a zobrazí se potvrzující zpráva | Standardní Symfony flash messages – uživatel vidí potvrzení a read model má čas se aktualizovat |
| Polling / Long polling | Frontend periodicky dotazuje read model, dokud nezobrazí aktuální stav | AJAX request každých N milisekund s timeoutem |
| Write-through cache | Command handler po úspěšném zápisu synchronně aktualizuje i read model / cache | Porušuje čisté oddělení, ale eliminuje lag pro kritické operace |
| Synchronní projekce pro kritické cesty | Některé projekce se aktualizují synchronně (ve stejné transakci), ostatní asynchronně | Hybrid: synchronní projekce pro okamžitou konzistenci, asynchronní pro reporting |
:::

:::callout{type="pattern"}
### PHP: Post-Redirect-Get po vytvoření objednávky {#ec-priklad-heading}

:::code{language="php" filename="src/Ordering/Infrastructure/Http/PlaceOrderController.php (varianta s Post-Redirect-Get)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Http;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Ordering\Application\Command\PlaceOrder;
use App\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class PlaceOrderController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    #[Route('/orders', name: 'place_order', methods: ['POST'])]
    public function __invoke(Request $request, #[CurrentUser] SecurityUser $user): Response
    {
        // Form-encoded POST nese všechno jako řetězec, agregát chce int.
        // Bez přetypování spadne Money::__construct() na typu. Klíče se
        // doplňují prázdnou hodnotou schválně: chybějící pole má odmítnout
        // validátor commandu se srozumitelnou hláškou, ne PHP warningem.
        $items = array_map(
            // Položka nemusí být pole: items[0]=foo projde přes ParameterBag
            // jako řetězec a bez tohohle guardu by shodilo uzávěru na typu.
            static fn (mixed $row): array => is_array($row) ? [
                'productId'        => (string) ($row['productId'] ?? ''),
                'quantity'         => (int) ($row['quantity'] ?? 0),
                'unitPriceInCents' => (int) ($row['unitPriceInCents'] ?? -1),
            ] : ['productId' => '', 'quantity' => 0, 'unitPriceInCents' => -1],
            $request->request->all('items'),
        );

        // Prázdná objednávka je chyba vstupu, ne doménový stav – agregát
        // ji stejně nepustí, ale 422 řekne klientovi víc než výjimka.
        if ($items === []) {
            return new Response('Objednávka musí mít alespoň jednu položku.', 422);
        }

        try {
            // PlaceOrderHandler vrací OrderId – funguje to jen na synchronní
            // sběrnici. Pro async transport musí ID vzniknout u volajícího
            // a přijít v commandu (viz kapitola Praktické příklady).
            $envelope = $this->commandBus->dispatch(new PlaceOrder(
                customerId: $user->customerId()->value,
                items: $items,
            ));
        } catch (ValidationFailedException $e) {
            // Middleware validuje PŘED handlerem, takže výjimka nepřijde
            // zabalená v HandlerFailedException a potřebuje vlastní větev.
            // Bez ní vybublá jako 500, přestože jde o chybu vstupu.
            return new Response((string) $e->getViolations(), 422);
        }

        /** @var OrderId $orderId */
        $orderId = $envelope->last(HandledStamp::class)->getResult();

        // Redirect na detail objednávky – read model se může
        // ještě aktualizovat, ale uživatel vidí potvrzení.
        $this->addFlash('success', 'Objednávka byla úspěšně vytvořena.');

        return $this->redirectToRoute('order_detail', ['id' => $orderId->value]);
    }
}
:::
:::

### Read-your-writes na úrovni HTTP {#read-your-writes-http}

Strategie z tabulky řeší, co vidí uživatel v prohlížeči. API klienti potřebují
tvrdší záruku: „přečti si, co jsi právě zapsal“ (read-your-writes). Dosáhne se jí
předáním pozice zápisu: odpověď na command nese číslo verze agregátu nebo offset,
na který se projekce musí dostat, a klient hodnotu pošle s dalším dotazem,
typicky v hlavičce.

Čtecí endpoint porovná aktuální pozici projekce s požadovanou. Když projekce
zaostává, krátce počká (desítky až stovky milisekund) a porovnání zopakuje.
Po vypršení limitu vrátí klientovi signál k opakování – `202 Accepted`
s hlavičkou `Retry-After` – a klient data po uvedené pauze načte znovu.
Stavový kód 304 se k tomu nehodí: znamená „vaše cache je platná“, ne „data ještě nejsou“.

:::callout{type="pattern"}
### Náznak: předání pozice zápisu přes HTTP hlavičky {#ryw-http-heading}

:::code{language="php" filename="snippet.php"}
<?php
// POST /orders - odpověď nese verzi zápisu
return new JsonResponse(['orderId' => $orderId], 201, ['X-Write-Version' => '17']);

// GET /orders/{id} s hlavičkou X-Expected-Version: 17
if ($projectionVersion < $expectedVersion) {
    // Projekce ještě nedoběhla - klient GET zopakuje po uvedené pauze.
    return new Response(status: 202, headers: ['Retry-After' => '1']);
}
:::
:::

Vzor se vyplatí jen na cestách, kde klient bezprostředně po zápisu čte tatáž data.
Plošné nasazení by čtecí stranu zatížilo čekáním, které většina dotazů nepotřebuje.

:::callout{type="warn"}
### Kdy eventual consistency NENÍ přijatelná {#ec-warning-heading}

V některých scénářích je nepřijatelná i krátkodobá nekonzistence:

- **Finanční zůstatky** – Uživatel nesmí vidět neaktuální stav účtu
  a provést operaci na základě zastaralých dat.
- **Unikátní omezení** – Kontrola duplicitního e-mailu při registraci musí
  platit v okamžiku zápisu, ne „až se read model aktualizuje“.
- **Limity a kvóty** – Když uživatel nesmí překročit 10 objednávek denně,
  kontrola musí být přesná v okamžiku commandu.

V těchto scénářích konzistenci zajišťuje **write strana** (command handler přes doménový model
a databázová omezení), ne read model. Eventual consistency read modelu se týká jen
*zobrazení* dat, ne *doménových rozhodnutí*.
:::

## 12.13 Asynchronní zpracování {#async}

Asynchronní zpracování navazuje přirozeně na oddělenou command stranu.
V Symfony 8 se konfiguruje přes transporty Messengeru. Zpráva směrovaná na asynchronní
transport se při dispatchi serializuje a zařadí do fronty, odkud ji worker později
vyzvedne a předá handleru.

:::callout{type="pattern"}
### Konfigurace asynchronního zpracování v Symfony 8 {#async-example-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez – doplňuje konfiguraci z 12.05)"}
# Doplněk ke konfiguraci z 12.05, ne náhrada: jména transportů
# i queue_name zůstávají stejná, přibývá jen retry a druhá fronta.
# Vložit místo původního bloku znamená přijít o default_bus,
# event.bus s allow_no_handlers i o celý routing.
framework:
    messenger:
        transports:
            async_events:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: events
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 60000
                    # Náhodný rozptyl zpoždění, aby se opakování nesešla naráz
                    jitter: 0.2

            async_priority_high:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: high_priority

        # Směrování zpráv - mapuje konkrétní třídy nebo rozhraní na transport
        # POZOR: Messenger třídy ověřuje při kompilaci kontejneru. Neexistující
        # jméno shodí každý příkaz hláškou „Invalid Messenger routing
        # configuration: class or interface … not found“. Nechte tu jen zprávy,
        # které ve svém projektu opravdu máte.
        routing:
            # Vysoká priorita - aktualizace read modelů pro kritické obrazovky.
            # Pozor: relay z kapitoly o Outboxu si transport vynucuje
            # přes TransportNamesStamp, a ten routing přebije. Prioritní
            # frontu má proto smysl nastavit až na straně relaye.
            #
            #
            # Řádek je schválně zakomentovaný. Prioritní frontu má smysl dát
            # události, která se o ni pere s ostatními – v projektu podle
            # knihy taková není. OrderPlacedIntegrationEvent to být nemůže:
            # na async_events ji směruje konfigurace ságy a jedna třída na
            # dvou transportech znamená duplicitní klíč v jednom mapování,
            # kde druhý tiše přebije první.
            #
            # A pozor na jméno: Messenger třídy ověřuje při kompilaci
            # kontejneru, takže vymyšlená třída zde shodí i cache:clear.
            # App\Notification\Application\Event\InvoiceIssued: async_priority_high
:::
:::

Konfigurace dává transportu `async_events` retry strategii: tři opakování s exponenciálním
backoffem. Klíč `jitter` přidá k vypočtenému zpoždění náhodný rozptyl a rozprostře opakování
v čase; bez něj se po výpadku vrátí všechny zprávy naráz. Celou strategii lze nahradit vlastní
implementací `RetryStrategyInterface` přes klíč `service`. Pro kritické události přibývá
samostatný transport `async_priority_high` s vlastní frontou – worker pro ni může běžet
s vyšší prioritou nebo na dedikovaném serveru.

Spolehlivé předání doménových událostí do fronty, atomické se zápisem agregátu,
zajišťuje [Outbox Pattern](/outbox-pattern).

Transport si zpráva může nést i sama: atribut `#[AsMessage(transport: 'async_events')]` nad třídou
příkazu nebo události nahradí odpovídající řádek v sekci `routing:`. Je to věc zvyku:
YAML drží směrování na jednom místě, atribut u zprávy.

:::callout{type="pattern"}
### Spuštění Messenger workerů {#worker-heading}

:::code{language="bash" filename="snippet.sh"}
# Konzumace ze všech tří front – pořadí určuje přednost, high_priority první
$ php bin/console messenger:consume async_priority_high async_events async_commands

# V produkci: Supervisor nebo systemd pro automatický restart
# /etc/supervisor/conf.d/messenger-worker.conf
[program:messenger-consume]
command=php /var/www/app/bin/console messenger:consume async_priority_high async_events async_commands --time-limit=3600 --memory-limit=128M
numprocs=2
autostart=true
autorestart=true
startsecs=0
redirect_stderr=true
stdout_logfile=/var/log/messenger-worker.log
:::
:::

:::callout{type="note"}
### Produkční provoz workerů {#worker-produkce-heading}

Messenger workery jsou dlouho běžící procesy. V produkci proto zajistěte:

- **Automatický restart** – Worker může spadnout (memory leak, neočekávaná výjimka)
  a Supervisor nebo systemd ho spustí znovu.
- **Time limit a memory limit** – `--time-limit=3600` ukončí worker
  po hodině, `--memory-limit=128M` po dosažení limitu paměti. Supervisor pak
  worker restartuje s čistým stavem.
- **Limit selhání** – `--failure-limit=5` zastaví worker po pátém neúspěšně
  zpracovaném příkazu. U projekcí to zabrání tomu, aby vadné nasazení protlačilo
  celou frontu do dead letter queue dřív, než si toho někdo všimne. Z dalších přepínačů
  se v provozu hodí `--queues` (odběr jedné fronty z transportu), `--all`
  (odběr ze všech nakonfigurovaných transportů) a `--keepalive` pro transporty
  s vlastním timeoutem.
- **Graceful shutdown** – Při deployi pošlete workerům signál
  `SIGTERM`. Worker dokončí rozpracovanou zprávu a teprve pak skončí.
  Příkaz `messenger:stop-workers` toho docílí přes signál uložený v cache.
:::

## 12.14 Zpracování chyb a Dead Letter Queue {#error-handling}

V asynchronním prostředí se chyby zpracovávají jinak než v synchronním.
Při synchronním dispatchi výjimka probublá přímo do controlleru a uživatel vidí chybovou
hlášku. Při asynchronním zpráva čeká ve frontě; když handler selže, uživatel
o tom neví a zprávu je potřeba zpracovat znovu.

### Retry strategie

Messenger umí zprávy, které selhaly, automaticky opakovat. Konfigurace
`retry_strategy` na transportu určuje, kolikrát a s jakým zpožděním
se handler zavolá znovu (hodnoty z konfigurace ve 12.13):

- `max_retries: 3` – Maximální počet opakování.
- `delay: 1000` – Zpoždění prvního opakování (v ms).
- `multiplier: 2` – Exponenciální backoff: 1s → 2s → 4s.
- `max_delay: 60000` – Maximální zpoždění (60 sekund).
- `jitter: 0.2` – Náhodný rozptyl zpoždění, hodnota 0 až 1 (výchozí 0.1).

### Failed transport (Dead Letter Queue)

Když selžou všechny pokusy, Messenger zprávu přesune na **failed transport**
(dead letter queue). Tam čeká na ruční zásah: vývojář ji prozkoumá, opraví příčinu
chyby a odešle znovu.

Klíč `failure_transport` funguje globálně i u jednotlivého transportu. Projekce tak mohou
mít vlastní dead letter frontu, oddělenou od e-mailů a reportů, což usnadní monitoring
i hromadné přehrání po opravě projektoru.

:::callout{type="pattern"}
### Konfigurace failed transportu a diagnostické příkazy {#failed-transport-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez)"}
# Výřez, ne celý soubor: klíče se přilévají ke konfiguraci z 12.05.
framework:
    messenger:
        failure_transport: failed

        transports:
            failed:
                dsn: 'doctrine://default?queue_name=failed'
:::

:::code{language="bash" filename="snippet.sh"}
# Zobrazení neúspěšných zpráv
$ php bin/console messenger:failed:show

# Detail konkrétní selhalé zprávy (včetně výjimky)
$ php bin/console messenger:failed:show 42

# Opakované zpracování selhalé zprávy
$ php bin/console messenger:failed:retry 42

# Opakování všech neúspěšných zpráv
$ php bin/console messenger:failed:retry

# Trvalé odstranění selhalé zprávy (po analýze)
$ php bin/console messenger:failed:remove 42
:::
:::

:::callout{type="warn"}
### Monitoring neúspěšných zpráv {#failed-monitoring-heading}

Dead letter queue není odkladiště, ale seznam zpráv, které **vyžadují pozornost**.
V produkci se proto počet zpráv na failed transportu monitoruje a hlídá alertingem (např. přes Prometheus metriky nebo jednoduchý cron job
kontrolující `messenger:stats failed --format=json`; volbu `--format` má z příkazů
messengeru jen `messenger:stats`, `messenger:failed:show` ji nezná). Neošetřené selhávající
zprávy mohou znamenat, že se read model rozchází s write modelem, že se ztrácejí události
nebo že se nedoručují notifikace.
:::

## 12.15 Middleware v CQRS {#middleware}

Middleware v Symfony Messenger tvoří řetěz kolem handleru a zachytí zprávu
před zpracováním i po něm. Tudy do dispatch cyklu vstupuje validace, logování,
transakce nebo autorizace, aniž by se měnil handler.

Vestavěné middleware `validation` a `doctrine_transaction` už znáte z konfigurace ve 12.05.
Pro další potřeby lze napsat vlastní:

:::callout{type="pattern"}
### PHP: Logovací middleware pro command bus {#middleware-priklad-heading}

:::code{language="php" filename="src/Infrastructure/Messenger/Middleware/CommandLoggingMiddleware.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class CommandLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $commandName = (new \ReflectionClass($message))->getShortName();

        $this->logger->info('Dispatching command: {command}', [
            'command' => $commandName,
            // Pozor: v produkci filtrujte citlivá pole (hesla, tokeny)
            // pomocí vlastního serializéru nebo allowlistu properties
            'payload' => get_object_vars($message),
        ]);

        $startTime = microtime(true);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $this->logger->info('Command handled: {command} ({duration}ms)', [
                'command'  => $commandName,
                'duration' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $envelope;
        } catch (\Throwable $e) {
            $this->logger->error('Command failed: {command} - {error}', [
                'command'  => $commandName,
                'error'    => $e->getMessage(),
                'duration' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            throw $e;
        }
    }
}
:::
:::

:::callout{type="pattern"}
### Registrace vlastního middleware {#middleware-registrace-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez)"}
# Nahrazuje jen seznam middleware u command.bus v konfiguraci z 12.05.
# Vložit celý blok místo ní znamená přijít o transporty i routing.
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - App\Infrastructure\Messenger\Middleware\CommandLoggingMiddleware
                    - validation
                    - doctrine_transaction
:::
:::

Na pořadí middleware záleží. Logování jde první, takže zachytí i validační chyby.
Následuje validace, která odmítne nevalidní command ještě před zahájením transakce,
a nakonec `doctrine_transaction`, které obalí handler do databázové transakce.

## 12.16 Testování CQRS {#testovani-cqrs}

Command handlery, query handlery a projektory jsou izolované komponenty s jasně
definovanými vstupy a výstupy, takže se testují dobře. Strategie se liší podle komponenty.

### Testování command handlerů

Command handler se často testuje jako unit test s mockem repozitáře. `RegisterUserHandler`
je výjimka: unikátnost e-mailu vynucuje databázový index, ne podmínka v kódu. Mock
repozitáře žádný index nemá, takže by test prošel i nad handlerem, který duplicity pouští
dál. Proto běží proti skutečné databázi:

:::callout{type="pattern"}
### PHP: Test command handleru {#test-command-handler-heading}

:::code{language="php" filename="tests/UserManagement/Registration/Command/RegisterUserHandlerTest.php"}
<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Registration\Command;

use App\UserManagement\Domain\Exception\DuplicateEmailException;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Command\RegisterUserHandler;
use App\UserManagement\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RegisterUserHandlerTest extends KernelTestCase
{
    private RegisterUserHandler $handler;
    private UserRepository $users;

    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->users = $container->get(UserRepository::class);

        // Handler se bere z kontejneru, ne staví ručně. Ruční konstrukce
        // se rozejde s každou novou závislostí – naposledy s event busem,
        // bez kterého by se přihlašovací záznam nezaložil.
        $this->handler = $container->get(RegisterUserHandler::class);

        // Registrace zapisuje do dvou tabulek, takže se uklízejí obě.
        // Jinak druhý běh testu spadne na unique indexu v app_user.
        $connection = $container->get(EntityManagerInterface::class)->getConnection();
        $connection->executeStatement('DELETE FROM app_user');
        $connection->executeStatement('DELETE FROM users');
    }

    public function testRegistersNewUser(): void
    {
        ($this->handler)(new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'securepassword123',
        ));

        $user = $this->users->findByEmail(Email::fromUserInput('jan@example.com'));

        self::assertNotNull($user);
        self::assertSame('Jan Novák', $user->name()->value);
    }

    public function testRejectsDuplicateEmail(): void
    {
        ($this->handler)(new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'securepassword123',
        ));

        $this->expectException(DuplicateEmailException::class);

        // Velikost písmen srovná Email::fromUserInput(), takže kolizi
        // zachytí unique index, ne porovnání řetězců. Mezery kolem adresy
        // sem nepatří: přes sběrnici by je dřív odmítl #[Assert\Email]
        // a test by dokládal chování, které aplikace nemá.
        ($this->handler)(new RegisterUser(
            name: 'Jan Jiný',
            email: 'JAN@example.com',
            password: 'jineheslo456',
        ));
    }
}
:::
:::

### Testování query handlerů

U query handleru se ověřuje mapování dat z read repozitáře na ViewModel.
Integrační test s reálnou databází prověří i samotné SQL dotazy:

:::callout{type="pattern"}
### PHP: Test query handleru {#test-query-handler-heading}

:::code{language="php" filename="tests/UserManagement/Profile/Query/GetUserProfileHandlerTest.php"}
<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Profile\Query;

use App\UserManagement\Profile\Query\GetUserProfile;
use App\UserManagement\Profile\Query\GetUserProfileHandler;
use App\UserManagement\Profile\ReadModel\UserProfileReadRepository;
use App\UserManagement\Profile\ViewModel\UserProfileViewModel;
use PHPUnit\Framework\TestCase;

final class GetUserProfileHandlerTest extends TestCase
{
    public function testReturnsProfileForExistingUser(): void
    {
        $expectedProfile = new UserProfileViewModel(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            name: 'Jan Novák',
            email: 'jan@example.com',
            registeredAt: new \DateTimeImmutable('2025-01-15'),
            totalOrders: 5,
            membershipTier: 'gold',
        );

        $readRepository = $this->createMock(UserProfileReadRepository::class);
        // with() bez expects() je od PHPUnit 12 deprecated a ve 14 zmizí.
        $readRepository->expects($this->once())
            ->method('findById')
            ->with('550e8400-e29b-41d4-a716-446655440000')
            ->willReturn($expectedProfile);

        $handler = new GetUserProfileHandler($readRepository);

        $result = $handler(new GetUserProfile('550e8400-e29b-41d4-a716-446655440000'));

        $this->assertSame($expectedProfile, $result);
    }

    public function testReturnsNullForNonExistingUser(): void
    {
        // Bez očekávání jde o stub, ne mock. createMock() by na PHPUnit 13
        // hlásil „No expectations were configured for the mock object“.
        $readRepository = $this->createStub(UserProfileReadRepository::class);
        $readRepository->method('findById')->willReturn(null);

        $handler = new GetUserProfileHandler($readRepository);

        $result = $handler(new GetUserProfile('non-existing-id'));

        $this->assertNull($result);
    }
}
:::
:::

### Testování projektorů

Projektory se nejlépe testují integračně s reálnou databází. Test ověří,
že po zpracování sekvence událostí obsahuje read model očekávaná data:

:::callout{type="pattern"}
### PHP: Integrační test projektoru {#test-projektor-heading}

:::code{language="php" filename="tests/Ordering/Infrastructure/Projection/OrderDashboardProjectorTest.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Infrastructure\Projection;

use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Ordering\Domain\Event\OrderShipped;
use App\Ordering\Infrastructure\Projection\OrderDashboardProjector;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Shipping\Domain\ValueObject\ShipmentId;
use App\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\Uid\Uuid;

final class OrderDashboardProjectorTest extends KernelTestCase
{
    // OrderId hodnotu nevalidního tvaru nepřijme, proto skutečná UUID.
    private const ORDER_ID       = '01a07424-28ff-7c31-9d40-6f2a1c8e5b05';
    private const OTHER_ORDER_ID = '01a07424-28ff-7c31-9d40-6f2a1c8e5b06';
    private const CUSTOMER_ID    = '01a07424-28ff-7c31-9d40-6f2a1c8e5b07';

    private Connection $connection;
    private OrderDashboardProjector $projector;

    protected function setUp(): void
    {
        $this->connection = self::getContainer()->get(Connection::class);
        $this->projector = new OrderDashboardProjector($this->connection);

        // Vyčistit testovací tabulku
        $this->connection->executeStatement('DELETE FROM order_dashboard');
    }

    public function testProjectsOrderLifecycle(): void
    {
        $shipmentId = ShipmentId::generate();

        // Given: objednávka byla vytvořena
        ($this->projector)(new OrderPlacedIntegrationEvent(
            eventId: Uuid::v7(),
            orderId: self::ORDER_ID,
            customerId: self::CUSTOMER_ID,
            items: [],
            totalAmountCents: 1500,
            occurredAt: new \DateTimeImmutable('2026-03-01 10:00:00'),
        ));

        // When: objednávka byla odeslána
        ($this->projector)(new OrderShipped(
            orderId: OrderId::fromString(self::ORDER_ID),
            shipmentId: $shipmentId,
            occurredAt: new \DateTimeImmutable('2026-03-02 08:30:00'),
        ));

        // Then: read model obsahuje aktuální stav
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM order_dashboard WHERE order_id = :id',
            ['id' => self::ORDER_ID],
        );

        $this->assertSame('shipped', $row['status']);
        $this->assertSame($shipmentId->value, $row['shipment_id']);
        $this->assertSame(1500, (int) $row['total_amount']);
    }

    public function testIdempotentProjection(): void
    {
        $event = new OrderPlacedIntegrationEvent(
            eventId: Uuid::v7(),
            orderId: self::OTHER_ORDER_ID,
            customerId: self::CUSTOMER_ID,
            items: [],
            totalAmountCents: 800,
            occurredAt: new \DateTimeImmutable('2026-03-01 12:00:00'),
        );

        // Zpracovat stejnou událost dvakrát (at-least-once delivery)
        ($this->projector)($event);
        ($this->projector)($event);

        // Read model obsahuje záznam pouze jednou
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM order_dashboard WHERE order_id = :id',
            ['id' => self::OTHER_ORDER_ID],
        );

        $this->assertSame(1, (int) $count);
    }

    public function testLateRedeliveryDoesNotRollBackStatus(): void
    {
        $placed = new OrderPlacedIntegrationEvent(
            eventId: Uuid::v7(),
            orderId: self::ORDER_ID,
            customerId: self::CUSTOMER_ID,
            items: [],
            totalAmountCents: 1500,
            occurredAt: new \DateTimeImmutable('2026-03-01 10:00:00'),
        );

        ($this->projector)($placed);
        ($this->projector)(new OrderShipped(
            orderId: OrderId::fromString(self::ORDER_ID),
            shipmentId: ShipmentId::generate(),
            occurredAt: new \DateTimeImmutable('2026-03-02 08:30:00'),
        ));

        // Tohle je ten případ, kvůli kterému upsert nese podmínku na updated_at:
        // stará událost dorazí znovu až po novější. Bez ní by dashboard tvrdil,
        // že odeslaná objednávka je zase jen přijatá.
        ($this->projector)($placed);

        $status = $this->connection->fetchOne(
            'SELECT status FROM order_dashboard WHERE order_id = :id',
            ['id' => self::ORDER_ID],
        );

        $this->assertSame('shipped', $status);
    }
}
:::
:::

Testování agregátů, value objects i doménových služeb rozebírá kapitola
[Testování DDD kódu](/testovani-ddd).

## 12.17 Saga / Process Manager {#saga}

CQRS nad více [Bounded Contexts](/zakladni-koncepty#bounded-contexts) dřív či později
potřebuje koordinovat dlouhotrvající procesy napříč kontexty.
Vzor **Saga**, v orchestrované podobě označovaný **Process Manager**, naslouchá doménovým
událostem a podle nich odesílá příkazy. Propojuje tak command a event stranu CQRS do
ucelených doménových procesů.

Ságy včetně implementace v Symfony Messengeru, kompenzačních strategií a testování
rozebírá kapitola [Ságy a Process Managery](/sagy-a-process-managery).

:::faq{}
- question: Co je CQRS?
  answer: 'CQRS (Command Query Responsibility Segregation) je architektonický vzor, který rozděluje aplikaci na dva oddělené modely: write model pro změny stavu a read model pro dotazy. Write model se soustředí na doménovou logiku a validaci invariantů, read model na rychlou prezentaci dat uživateli. Každý model lze nezávisle optimalizovat i škálovat. Popsal jej Greg Young; kořeny sahají ke staršímu pravidlu CQS Bertranda Meyera, sám Young ale označuje formulaci „CQRS je rozšíření CQS“ za nepřesnou a mluví o samostatném vzoru. Viz <a href="#what-is-cqrs">úvodní sekce</a>.'
- question: Jaký je rozdíl mezi CQS a CQRS?
  answer: 'CQS (Command Query Separation) je návrhové pravidlo na úrovni metod: každá metoda by měla buď měnit stav, nebo vracet hodnotu, ne obojí. CQRS (Command Query Responsibility Segregation) posouvá tutéž myšlenku z metody na model: místo jednoho doménového modelu vznikají dva oddělené, každý s vlastními třídami, úložištěm i optimalizačním profilem. CQS je tedy princip ve třídě, CQRS rozhodnutí o podobě modelu uvnitř systému. Více v <a href="#cqs-vs-cqrs">sekci CQS vs. CQRS</a>.'
- question: Kdy se vyplatí CQRS nasadit?
  answer: 'CQRS přináší hodnotu v aplikacích, kde se požadavky na zápis a čtení výrazně liší – například doménově bohatý write model s mnoha invarianty proti výrazně převažujícím dotazům, které potřebují denormalizovaná data. Uplatní se také tam, kde má čtení nezávislý škálovací profil (repliky, cache, full-text vyhledávání) nebo kde je hodnota v odděleném auditu změn. U jednoduchých CRUD operací zvyšuje počet tříd bez odpovídajícího přínosu. Podrobný rozbor ve <a href="#benefits">Výhodách CQRS</a> a <a href="#challenges">Výzvách a omezeních</a>.'
- question: Musím použít Event Sourcing, když používám CQRS?
  answer: 'Ne. CQRS a Event Sourcing jsou nezávislé vzory, které se často kombinují, ale každý z nich lze zavést samostatně. CQRS lze plnohodnotně implementovat s klasickou Doctrine ORM persistencí na write straně a denormalizovanými SQL tabulkami na read straně. Event Sourcing lze naopak zavést i bez CQRS – byť kombinace obou je v praxi běžná, protože si vzájemně prospívají. Rozbor vztahu obou vzorů v <a href="#what-is-cqrs">sekci Co je CQRS</a>.'
- question: Potřebuje CQRS frontu nebo druhou databázi?
  answer: 'Ne. Message bus, asynchronní transport i oddělené úložiště jsou volby, ne součást vzoru. Greg Young popisuje read stranu jako tenkou vrstvu nad toutéž databází, která promítá řádky rovnou do DTO; Azure Architecture Center uvádí, že posílání zpráv není pro CQRS podmínkou. Většině aplikací proto stačí nejjednodušší podoba: oddělené command a query handlery nad jednou databází. Viz <a href="#cqrs-myty-heading">Tři mýty o CQRS</a>.'
- question: Jak se CQRS implementuje v Symfony?
  answer: 'Základním stavebním kamenem je komponenta Symfony Messenger, která funguje jako sběrnice pro příkazy a dotazy. Pro CQRS se obvykle definují dvě až tři oddělené sběrnice (<code>command.bus</code>, <code>query.bus</code> a pro doménové události <code>event.bus</code>), každá s vlastní sadou handler tříd a middleware. Dokumentace Symfony přitom doporučuje přidávat další sběrnici jen tehdy, když potřebuje jiný middleware stack. Příkazy mění stav a nevracejí data; dotazy vracejí ViewModely (read modely) a stav nemění. Asynchronní zpracování se zapíná přes transport a vyjme dlouhé operace z cyklu request-response. Více v <a href="#symfony-messenger">sekci Symfony Messenger jako základ CQRS</a>.'
:::
