# Studie: Event Sourcing

- **Kapitola:** `content/chapters/event_sourcing.md` (č. 13, kategorie Vzory, 1830 řádků)
- **Cesta:** /event-sourcing
- **Typ kapitoly:** hybridní (definiční úvod 13.01–13.03, implementační zbytek)
- **Datum studie:** 2026-09-03

> Poznámka k rešerši: rozpočet `WebSearch` byl v této session vyčerpaný (200/200), takže
> veškerá rešerše proběhla cíleným `WebFetch` na primární URL a přímými HTTP dotazy na
> Packagist a GitHub API. Sekce 9 u každého zdroje uvádí způsob získání.

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| 13.01 Co je ES | ř. 22–63 (42) | Definice přes Fowlera, srovnávací tabulka CRUD vs. ES, glosář pěti pojmů | [1] | Jediná citace v celé sekci |
| 13.02 Vztah k CQRS | ř. 64–96 (33) | ES a CQRS jsou samostatné vzory, v praxi se kombinují; datový tok v 7 krocích | [4] | Správně, ale mělce – neuvádí, co Young o symbióze skutečně píše |
| 13.03 Kdy použít | ř. 97–151 (55) | 5 vhodných a 4 nevhodné případy, varování před plošným nasazením, přehled PHP knihoven | žádné | Přehled knihoven (ř. 141–144) je fakticky zastaralý – viz G1 |
| 13.04 Doménové události | ř. 152–332 (181) | Požadavky na tvar eventu, bázová třída `DomainEvent`, `create()`/`fromPayload()`, UTC, `eventType()` konvence | žádné | Nejlepší pasáž kapitoly: rozbor identity eventu a idempotence (ř. 311–319) |
| 13.05 Event Store | ř. 333–547 (215) | DDL pro MySQL/MariaDB, optimistic locking přes `UNIQUE (aggregate_id, version)`, interface + DBAL implementace | žádné | Jen MySQL varianta; PostgreSQL se objeví až v ř. 1786 |
| 13.06 Agregát s ES | ř. 548–846 (299) | `recordEvent()` + `apply*()`, `reconstituteFromEvents()`, ES repozitář | žádné | Odchylku od `AggregateRoot::record()` z `/zakladni-koncepty` explicitně vysvětluje (ř. 559) |
| 13.07 Projekce | ř. 847–976 (130) | Sync vs. async projekce, `OrderSummaryProjection`, Messenger routing YAML | žádné | Chybí pojem checkpoint/position a catch-up subscription jako model |
| 13.08 ES jako outbox | ř. 977–1020 (44) | `event_store` sám plní roli outbox tabulky; gap problém auto-incrementu; at-least-once | žádné | Věcně silné, deleguje detaily na `/outbox-pattern` |
| 13.09 Problémy projekcí | ř. 1021–1333 (313) | Idempotence, tracking tabulka, out-of-order UPDATE, retry + DLQ, rebuild command, eventual consistency | [19] implicitně | Nejdelší sekce; rebuild command sám má ~110 řádků kódu |
| 13.10 Snapshotting | ř. 1334–1493 (160) | Kdy snapshotovat, `Snapshot` třída, repozitář, invalidace při změně schématu | žádné | Neuvádí Youngův termín „Rolling Snapshot" ani jeho zdroj |
| 13.11 Verzování | ř. 1494–1830 (337) | Upcaster + chain, weak/strong schema, změny které upcasting neřeší, 3 strategie, tiering, GDPR | žádné | Nejambicióznější sekce, ale bez jediného odkazu na Younga [5] |

Kapitola je z hlediska implementace nadprůměrně poctivá. Rozbor identity eventu a jeho vlivu
na idempotenci (ř. 311–319), varování před tichým selháním out-of-order UPDATE (ř. 1132–1139)
a gap problém auto-incrementu (ř. 1002–1011) jsou detaily, které většina česky ani anglicky
psaných úvodů do ES vůbec neotevírá. Cenu za to platí rozsahem: 1830 řádků, z toho odhadem
přes 900 řádků kódu, a jen **dvě citace na celou kapitolu** (ř. 27 a ř. 67).

Odbývá naopak tři věci. Za prvé teorii – definiční část je 130 řádků z 1830 a stojí na jediné
Fowlerově větě. Za druhé rozlišení interních a veřejných eventů, které v ES rozhoduje o tom,
jestli půjde systém vůbec refaktorovat. Za třetí aktuálnost PHP ekosystému: čtyři odstavce
na ř. 141–144 popisují stav, který už neplatí.

## 2. Kanonické zdroje k tématu

**Fowler, *Event Sourcing*, 12. 12. 2005** [1]. Definice zní: „Capture all changes to an
application state as a sequence of events." Fowler formuluje test, který kapitola nepoužívá:
„We can discard the application state completely and rebuild it by re-running the events from
the event log on an empty application." Věnuje se dvěma tématům, která kapitola neotevírá
vůbec: **gateways k externím systémům** („You get problems when you are sending modifier
messages to external systems"; gateway musí znát replay mód a při replay nesmí volat ven)
a odkaz na Retroactive Event.

**Fowler, *Retroactive Event*, 12. 12. 2005** [2]. Rozlišuje tři druhy oprav: out-of-order
události, zamítnuté (rejected) události a nesprávné události. Zdůrazňuje, že „building an
application to support Retroactive Event is a significant decision with an impact across the
whole system", a že propagace oprav do externích systémů bývá „very involved".

**Fowler, *Temporal Patterns*, 16. 2. 2005** [3]. Zavádí dvojici **actual time** (kdy se to
skutečně stalo) a **record time** (kdy jsme se to dozvěděli): „Whenever something happens,
there are always these two times that come with it." Bitemporalita je předpokladem korektních
retroaktivních oprav.

**Young, *CQRS Documents*, 2010** [4]. Kapitola cituje jen na podporu tvrzení „ES a CQRS jsou
dva samostatné vzory". Young je ovšem přesnější: „CQRS and Event Sourcing have a symbiotic
relationship. CQRS allows Event Sourcing to be used as the data storage mechanism for the
domain. One of the largest issues when using Event Sourcing is that you cannot ask the system
a query such as 'Give me all users whose first names are Greg'. […] With CQRS the only query
that exists within the domain is GetById which is supported with Event Sourcing." Tvrzení tedy
není „jsou nezávislé", ale „ES bez CQRS naráží na neschopnost dotazovat write model".

Ze stejného dokumentu pochází kanonická terminologie, kterou kapitola nepoužívá:

- **Rolling Snapshot** – „a denormalization of the current state of an aggregate at a given
  point in time […] used as a heuristic to prevent the need to load all events".
- **Reversal Transaction** – Youngova odpověď na mazání („There is no Delete"): smazání se
  modeluje jako nová transakce, která stav vrátí, ale stopu ponechá. To, čemu kapitola říká
  „compensating event" (ř. 1759), je Youngův Reversal Transaction.
- **Struktura event storage** – Young navrhuje **dvě** tabulky: `Events` (AggregateId, Data,
  Version) a `Aggregates` (AggregateId, Type, Version) s denormalizovanou aktuální verzí,
  která slouží k optimistic concurrency check. Jediná dotazovací operace v produkci je
  `SELECT * FROM EVENTS WHERE AGGREGATEID='' ORDER BY VERSION`.

**Young, *Versioning in an Event Sourced System*, Leanpub, 2017** [5]. Kniha je dodnes vedena
jako rozpracovaná (90 % k 9. 4. 2017), přesto je to nejcitovanější zdroj k tématu. Obsahuje
kapitoly **Weak Schema**, **Copy and Replace**, **Upcasting**, **Double Write**, **Versioning
of Behaviour**, a řeší **internal vs. external models**. Dvě z nich kapitola nezná:

- **Double Write** – při změně schématu se po přechodné období zapisují obě verze eventu,
  konzumenti se stěhují postupně. Jde o nejméně invazivní strategii breaking changes a chybí
  mezi třemi, které kapitola nabízí (ř. 1728–1776).
- **Versioning of Behaviour** – změna se netýká payloadu, ale významu; řeší se verzováním
  logiky, ne dat.

**Verraes, *Eventsourcing: State from Events or Events as State?*, 24. 8. 2019** [9] nabízí
ostřejší definici než Fowler: „A system is eventsourced when the single source of truth is
a persisted history of the system's events; and that history is taken into account for
enforcing constraints on new events." Druhá polovina věty je podstatná – odlišuje ES od
pouhého logování a od stream processingu.

**Verraes, *Explicit Public Events*, 11. 5. 2019** [8]. „Mark a small subset of events as
public, keep the rest private by default." Bez toho „the outside API is tightly coupled to
the internal structure of the Bounded Context. Changing the internals would force an API
change."

**Verraes, *Multi-temporal Events*, 22. 3. 2022** [10]. Event má nést oddělené časy: `recorded_at`
jako infrastrukturní metadata a doménově pojmenovaný čas výskytu (`deposited_at`, `crashed_at`).
Příklady: bankovní výpis dorazí po půlnoci s transakcemi z předchozího dne; havárie vozu
nastane, je nahlášena později a do systému vstoupí ještě později.

**Verraes, *Crypto-Shredding*, 13. 5. 2019** [6] a **Forgettable Payloads**, 13. 5. 2019 [7].
Klíčové pro sekci 13.11 – viz bod 5.3.

## 3. Stav praxe a posuny

**Definice se zúžila.** Fowlerova formulace z roku 2005 („capture all changes as a sequence
of events") dnes zahrnuje i change data capture a stream processing. Verraesova definice [9]
z roku 2019 přidává podmínku, že historie se používá k vynucení invariantů nových eventů.
To je posun, který stojí za zaznamenání – kapitola definuje po Fowlerovi a k rozlišení se
nedostane.

**Event Sourcing ≠ Event Streaming.** Nejčastější omyl posledního desetiletí je nasazení
Kafky jako event store. Dudycz [13] uvádí tři technické důvody, proč to nefunguje: chybí
optimistic concurrency („tools such as Kafka don't support it. They have not been built for
it"), nelze spolehlivě přečíst jeden stream za účelem rekonstrukce agregátu, a retenční model
je navržený pro průtok, ne pro trvalé uložení. Kapitola tuto hranici nikde nekreslí, přestože
§13.07 a §13.08 pracují s Messengerem a RabbitMQ.

**Adopce.** Podle InfoQ Architecture Trends 2022, jak ji cituje Dudycz [14], se Event Sourcing
posunul do fáze „Late Majority". Vzor tedy není exotický, ale ani výchozí volba.

**KurrentDB.** Event Store Ltd i produkt EventStoreDB se přejmenovaly – README repozitáře
kurrent-io/KurrentDB [22] to potvrzuje doslovně: „Event Store – the company and the product –
are rebranding as Kurrent. […] EventStoreDB will be referred to as KurrentDB." Kapitola to
v FAQ (ř. 1823) uvádí správně. Datum přejmenování se nepodařilo ověřit.

**PHP ekosystém se za poslední dva roky přeskupil.** Údaje z Packagistu a GitHub API
k 3. 9. 2026 [17][18]:

| Balíček | Poslední stabilní verze | PHP | Stav |
|---|---|---|---|
| `eventsauce/eventsauce` | 3.9.1 (2026-05-03) | ^8.0 | aktivní, 870 hvězd, 2,3 M stažení |
| `patchlevel/event-sourcing` | 3.21.0 (2026-08-26) | 8.2–8.5 | aktivní, poslední push 2026-09-02 |
| `patchlevel/event-sourcing-bundle` | 3.17.1 (2026-07-15) | – | **jediný ES bundle s deklarovaným `symfony/* ^8.0`** |
| `ecotone/ecotone` | 1.326.1 (2026-08-22), 2.0.0-beta.1 (2026-08-28) | ^8.2 | aktivní, před vydáním v2 |
| `prooph/event-store` | v7.12.3 (2025-04-21) | ^8.1 | v7 udržovaný, v8 stále „Development" |
| `prooph/event-store-symfony-bundle` | v0.11.2 (2024-05-28) | – | max `symfony/* ^7.0`, bez Symfony 8 |
| `broadway/broadway` | 3.0.1 (2026-08-09) | ^8.2 | **archivováno na GitHubu, `abandoned: true` na Packagistu** |

Broadway vydala 9. 8. 2026 verzi 3.0.0, o dvě hodiny později 3.0.1 s příznakem `abandoned`
a repozitář byl téhož dne archivován. Celá organizace `broadway/*` je označena jako opuštěná.

prooph není archivovaný ani opuštěný, ale README [21] uvádí v tabulce „Version Guidance"
verzi 8.x jako „Development" – ten stav trvá roky. Symfony bundle se od května 2024 nehnul,
takže na Symfony 8 prooph fakticky nedosáhne bez vlastní integrace.

## 4. Symfony / PHP specifika

**Symfony Messenger** [19]. Kapitola uvádí výchozí retry hodnoty jako „3 pokusy s násobičem 2"
(ř. 1147) – to sedí. Pozor: výchozí `max_delay` **není** 10000 ms, ale **0** (bez stropu), `jitter` je 0.1 – ověřeno 2026-09-04 v `Configuration.php` FrameworkBundle. Dřívější znění této věty bylo chybné (kapitola v ukázce
nastavuje 60000, což je legitimní, ale nejde o default) a že Messenger má výchozí `jitter: 0.1`,
o kterém se kapitola nezmiňuje.

Novinky relevantní pro ES, které kapitola nezná:

- **`#[AsMessage('async')]`** – routing přímo na třídě eventu místo YAML seznamu (ř. 963–972).
  Pro ES, kde typů eventů rychle přibývá, je to podstatná úspora údržby konfigurace.
  **Atribut je v Symfony od 7.2** (ověřeno 2026-09-04 [23]), takže jde o zavedenou věc, ne novinku.
- **Doctrine transport s PostgreSQL `LISTEN/NOTIFY`** (`options: use_notify: true`, výchozí
  zapnuto). Kapitola na ř. 1019 doporučuje `LISTEN/NOTIFY` jako cestu ke snížení latence
  relay, ale netuší, že Messenger to už umí sám. **Funkce je v Symfony od 7.1**, ne od 8.1
  (ověřeno 2026-09-04 [23]); v 8.1 k ní přibyl jen `PostgreSqlNotifyOnIdleListener` pro
  správnou funkci s multi-queue workery.
- **`messenger:consume --keepalive`** (Symfony **7.3**, ne 8.1 – ověřeno 2026-09-04 v CHANGELOZÍCH
  `symfony/doctrine-messenger` a `symfony/redis-messenger` [23]) – zabraňuje předčasnému redelivery
  dlouhých zpráv; relevantní pro rebuild a pro projektory nad velkými dávkami.
- **`--fetch-size=N`** (Symfony **8.1**, ověřeno [23]) – snižuje režii při vysokém průtoku
  eventů, přesně scénář projekcí. Spolu s ním přibyl v 8.1 `AmqpPriorityStamp` pro prioritu
  jednotlivé zprávy – pozor, je specifický pro AMQP transport, ne obecný stamp.
- **`messenger:failed:show --stats`, `--class-filter`, `messenger:failed:remove --all`** –
  kapitola na ř. 1183–1187 uvádí jen tři holé příkazy bez užitečných přepínačů.

**Doctrine DBAL.** EventSauce má `eventsauce/message-repository-for-doctrine` 1.4.0
(2025-10-30) s `doctrine/dbal: ^3.1|^4.0` a `eventsauce/message-outbox-for-doctrine` 1.2.1
s toutéž podporou [17]. Pro stack knihy (Doctrine ORM 3 / DBAL 4) to znamená, že EventSauce
je použitelný out of the box včetně outboxu – kapitola tvrdí, že „integraci do Symfony si
napíšete sami" (ř. 141), což platí pro DI a Messenger, ne pro persistenci. Pozor na starší
`eventsauce/doctrine-message-repository` (0.8.3, 2021, DBAL ^2.12) – ten je mrtvý a název
je matoucí.

**patchlevel/event-sourcing** [20] dokumentuje sadu funkcí, které kapitola staví ručně nebo
vůbec: subscriptions s verzovaným životním cyklem (projekce i procesory), automatický snapshot
systém, upcasting, **split stream** pro rozdělení velkých agregátů, **crypto-shredding pro
osobní údaje** a CLI příkazy pro Symfony. Doprovodné balíčky `event-sourcing-phpunit`
(given/when/then) a `event-sourcing-phpstan-extension` jsou pro knihu psanou pro Symfony 8
relevantní.

**Ecotone** míří na verzi 2.0 (beta z 28. 8. 2026). Popis v kapitole („přijímáte ji vcelku")
je věcně v pořádku.

## 5. Sporné a chybně podávané body

**5.1 „ES a CQRS jsou nezávislé" (ř. 66–71).** Tvrzení je běžné a formálně správné, ale
zamlčuje Youngovu pointu [4]: ES bez CQRS neumí dotazovat write model ničím než `GetById`.
Symetrie tedy neplatí – CQRS bez ES je běžná a bezbolestná konfigurace, ES bez CQRS je
možná, ale nutí postavit read stranu tak jako tak. Doporučení: tvrzení ponechat, ale doplnit
asymetrii a citovat Younga přesněji než jen odkazem v závorce.

**5.2 „Event Store je zdrojem pravdy pro integraci" (ř. 109).** V rozporu s Explicit Public
Events [8]. Když externí konzumenti čtou interní eventy agregátu, každé přejmenování pole
se stává breaking change API. Sekce 13.11 pak řeší důsledky (upcasting, multi-version store)
bez toho, aby zmínila příčinu. Doporučení: zavést rozlišení interních a publikovaných eventů
a v 13.03 tvrzení přeformulovat.

**5.3 Crypto-shredding jako řešení práva na výmaz (ř. 1799–1818).** Kapitola prezentuje
crypto-shredding jako primární řešení a referenční přístup jako alternativu. Verraes [6]
doporučuje pořadí opačné a uvádí právní námitku od Harrisona J. Browna: „encrypted personal
data is still personal data, regardless of whether anyone has the key." Podle GDPR nestačí
zničit klíč, pokud lze data teoreticky rozšifrovat. Verraes proto pro osobní údaje doporučuje
**Forgettable Payloads** [7] – PII v samostatné databázi, v eventu jen reference; crypto-shredding
nechává pro obchodně citlivá data. Přidává i technickou výhradu: „Today's unbreakable
encryption could be tomorrow's infosec disaster." Doporučení: obě strany uvést, pořadí
doporučení otočit, a explicitně dodat, že studie ani kniha nenahrazují právní posouzení.

**5.4 Optimistic locking přes `UNIQUE (aggregate_id, version)` (ř. 370–380).** Kapitola
podává jako jediné řešení. Young [4] používá denormalizovanou verzi v tabulce `Aggregates`.
Rozdíl je praktický: unikátní index detekuje konflikt až při insertu a vyžaduje překlad
chyby integrity na `ConcurrencyException`; druhá tabulka umožňuje kontrolu předem, ale přidá
zápis. Doporučení: uvést obě varianty a důvod volby, ne jen výsledek.

**5.5 Jediný časový údaj `occurredAt` (ř. 190).** Kapitola má jeden čas a v projekcích ho
používá jako `placed_at` (ř. 899) i `shippedAt` (ř. 937), tedy jako doménový čas. To je přesně
záměna, před kterou varuje Verraes [10] i Fowler [3]: v `create()` (ř. 260) vzniká
`new DateTimeImmutable('now')`, což je čas zápisu, ne čas doménového výskytu. U importů,
zpětných zadání a integrace s pomalými externími systémy dá projekce špatná data.

**5.6 „Rebuild = zastavte workery" (ř. 1309–1315).** Funkční, ale v produkci u kritických
projekcí nepoužitelné. Standardní alternativa je blue/green rebuild: nová projekce se staví
do stínové tabulky, po dojetí k aktuální pozici se přepne pod tím samým jménem. Kapitola
o tom neví.

**5.7 Definice Event Sourcingu.** Kapitola staví na Fowlerovi [1]. Verraesova definice [9]
by ostřeji odlišila ES od auditního logu a od Kafky. Doporučení: uvést obě, s poznámkou,
že se za 14 let zúžila.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | zastaralé | `event_sourcing.md:142` | Broadway popsán jako „funguje, tempo vývoje zvolnilo". Repozitář je od 2026-08-09 archivovaný a balíček označen `abandoned` [17][18] | Přepsat na „archivovaná, pro nové projekty nepoužívat"; zmínit jen historicky |
| G2 | zastaralé | `event_sourcing.md:143` | prooph „udržuje komunita" – v8 je roky „Development" a Symfony bundle končí na `symfony ^7.0` (2024-05) [17][21] | Doplnit, že prooph nemá integraci pro Symfony 8 |
| G3 | chybí | `event_sourcing.md:136-151` | `patchlevel/event-sourcing` v seznamu vůbec není, přitom je to jediná ES knihovna s bundlem deklarujícím Symfony 8 a s vestavěným crypto-shreddingem, snapshoty, upcastingem a split streamem [17][20] | Přidat do seznamu, u Symfony 8 zmínit jako první volbu při preferenci hotového řešení |
| G4 | nepodložené | celá kapitola | 1830 řádků, dvě citace (ř. 27, ř. 67). Sekce 13.10 a 13.11 nemají zdroj vůbec | Doplnit Younga [4][5], Verraese [6][7][8][10] a Fowlera [2][3] |
| G5 | chybí | `event_sourcing.md:97-151` | Chybí hranice ES vs. event streaming; kapitola pracuje s RabbitMQ/Messengerem, ale nikde neřekne, proč broker není event store [13] | Nová podsekce ~25 řádků v 13.03 nebo 13.05 |
| G6 | chybí | `event_sourcing.md:109` | Není rozlišení interních a veřejných (integračních) eventů [8] | Nová podsekce ~35 řádků; ovlivňuje i 13.11 |
| G7 | sporné | `event_sourcing.md:1799-1818` | Crypto-shredding podán jako primární řešení GDPR; Verraes [6][7] doporučuje pro PII opačné pořadí a cituje právní námitku | Přepsat callout, otočit doporučení, doplnit Forgettable Payloads |
| G8 | mělké | `event_sourcing.md:190,260` | Jediný `occurredAt` slouží zároveň jako čas zápisu i doménový čas [3][10] | Doplnit `recordedAt` do metadat a doménově pojmenovaný čas do payloadu; ~20 řádků + úprava ukázek |
| G9 | chybí | `event_sourcing.md:1728-1776` | Ze čtyř Youngových strategií [5] chybí Double Write a Versioning of Behaviour | Doplnit Double Write jako čtvrtou strategii (~25 řádků) |
| G10 | chybí | `event_sourcing.md:1494-1830` | Chybí migrace z legacy: import bez historie. Verraesovy Migration Events / Ghost Context [11] | Podsekce ~25 řádků, provázat na `/migrace-z-crud` |
| G11 | chybí | `event_sourcing.md:847-976` | Nezaveden pojem *catch-up subscription* a *position/checkpoint* jako model projekce. Checkpoint tabulka se objeví až v 13.09 jako nástroj idempotence, ne jako pozice ve streamu | Doplnit do 13.07 (~20 řádků) a provázat s 13.09 |
| G12 | mělké | `event_sourcing.md:1309-1315` | Rebuild řeší zastavením workerů; chybí blue/green rebuild se stínovou tabulkou | Doplnit variantu do calloutu (~15 řádků) |
| G13 | chybí | `event_sourcing.md:333-547` | Chybí problém interakce s externími systémy při replay – Fowlerovy gateways [1] | Doplnit do 13.06 varování ~15 řádků |
| G14 | sporné | `event_sourcing.md:370-380` | Optimistic locking podán jako jediné možné řešení; Young [4] používá tabulku agregátů s denormalizovanou verzí | Doplnit srovnání dvou variant (~12 řádků) |
| G15 | chybí | celá kapitola | Neodkazuje na `/event-storming` ani `/sagy-a-process-managery`, přestože obě existují a s ES přímo souvisejí | Doplnit odkazy; process manager nad event streamem zmínit v 13.07 |
| G16 | zastaralé | `event_sourcing.md:963-972`, `1019` | Routing eventů výhradně přes YAML; `LISTEN/NOTIFY` doporučeno bez vědomí, že Doctrine transport to má vestavěné [19] | Zmínit `#[AsMessage]` a `use_notify` |
| G17 | nepodložené | `event_sourcing.md:1334-1352` | Snapshotting bez zdroje; Youngův termín „Rolling Snapshot" a jeho definice chybí [4] | Doplnit citaci a termín |
| G18 | mělké | `event_sourcing.md:1759-1776` | Compensating event je Youngova „Reversal Transaction" [4]; kapitola termín ani zdroj neuvádí | Doplnit atribuci |
| G19 | chybí | `event_sourcing.md:22-63` | Definice jen podle Fowlera; Verraesovo zúžení [9] chybí | Doplnit druhou definici (~10 řádků) |
| G20 | nadbytečné | `event_sourcing.md:1197-1307` | Rebuild command zabírá ~110 řádků kódu; poměr k informační hodnotě je nepříznivý v kapitole, která už tak má přes 900 řádků kódu | Zkrátit na jádro smyčky, zbytek do GitHub příkladu |
| G21 | chybí | `event_sourcing.md:1778-1797` | Storage tiering uvádí „Hot tier (PostgreSQL master)", zatímco DDL v 13.05 je pro MySQL/MariaDB | Sjednotit, nebo doplnit PostgreSQL variantu DDL |

## 7. Doporučení k přepisu

**P1-1 — Přepsat přehled PHP knihoven na ř. 136–151.**
Broadway je archivovaná a označená jako opuštěná [17][18], prooph nedosáhne na Symfony 8
a patchlevel v seznamu chybí. Tři ze čtyř položek jsou dnes zavádějící. Přehled je navíc
místo, kde kniha zestárne nejrychleji – proto doporučuji doplnit větu s datem ověření
(„stav k <měsíc rok>"), aby čtenář věděl, jak starou informaci čte. Odhad: přepis podsekce,
~30 řádků.

**P1-2 — Doplnit citace do sekcí 13.04–13.11.**
Dvě citace na 1830 řádků nesplňují standard zbytku knihy. Kritická místa: snapshotting bez
Younga, verzování bez *Versioning in an Event Sourced System*, GDPR bez Verraese, temporalita
bez Fowlera. Odhad: 15–20 nových odkazů, bez zásahu do struktury.

**P1-3 — Otočit doporučení v GDPR calloutu (ř. 1799–1818).**
Crypto-shredding je dnes v kapitole primární řešení práva na výmaz. Verraes [6] pro osobní
údaje doporučuje Forgettable Payloads a cituje právní stanovisko, že zašifrovaný osobní údaj
zůstává osobním údajem. Kapitola v současné podobě dává čtenáři návod, který mu při auditu
neobstojí. Odhad: přepis calloutu + nový callout na Forgettable Payloads, ~35 řádků.

**P1-4 — Zavést rozlišení interních a publikovaných eventů.**
Tvrzení „Event Store je zdrojem pravdy pro integraci" (ř. 109) je v rozporu s Explicit Public
Events [8] a je zároveň příčinou většiny bolesti, kterou pak řeší sekce 13.11. Bez tohoto
rozlišení kapitola učí architekturu, kterou nelze refaktorovat. Odhad: nová podsekce
~35 řádků v 13.04 nebo 13.07 plus úprava dvou vět v 13.03.

**P2-1 — Doplnit hranici ES vs. event streaming.**
Kapitola pracuje s RabbitMQ a Messengerem a nikde neřekne, že broker není event store [13].
Čtenář, který přijde s Kafkou v zádech, si z kapitoly odnese špatný závěr. Odhad: nová
podsekce ~25 řádků v 13.03.

**P2-2 — Rozšířit časový model eventu.**
Rozdělit `occurredAt` na `recordedAt` (metadata) a doménově pojmenovaný čas výskytu [3][10].
Vedle korektnosti to zlepší i projekce, které dnes berou infrastrukturní čas jako doménový
(ř. 899, ř. 937). Zásah do bázové třídy `DomainEvent` a několika ukázek. Odhad: ~25 řádků
prózy + úpravy v pěti ukázkách.

**P2-3 — Doplnit Double Write jako čtvrtou strategii breaking changes.**
Young [5] ji vede jako základní typovou strategii verzování a je z celé čtveřice nejméně
invazivní. Kapitola nabízí tři strategie, z nichž dvě jsou drahé (copy-and-replace, multi-version).
Odhad: nový callout ~25 řádků do 13.11.

**P2-4 — Zavést pojmy checkpoint/position a catch-up subscription v 13.07.**
Kapitola zavádí checkpoint tabulku až v 13.09 jako nástroj idempotence, což je odvozená role.
Primární je pozice projekce ve streamu; z ní plyne rebuild i lag monitoring. Odhad: ~20 řádků
v 13.07 a jedna vysvětlující věta v 13.09.

**P2-5 — Doplnit blue/green rebuild.**
Zastavení workerů (ř. 1309) je pro produkci s SLA nepoužitelné. Stínová tabulka a přepnutí
po dojetí je standardní alternativa. Odhad: rozšíření calloutu ~15 řádků.

**P2-6 — Doplnit varování o externích systémech při replay.**
Fowlerovy gateways [1]: při replay se nesmí volat ven. Kapitola replay používá na třech
místech (rekonstituce agregátu, rebuild projekce, snapshot) a nikde na to neupozorní.
Odhad: callout ~15 řádků.

**P3-1 – Zmínit funkce Messengeru, které kapitola obchází.**
`#[AsMessage]` místo YAML routingu (7.2), `use_notify` v Doctrine transportu (7.1),
`--keepalive` (7.3), `--fetch-size` (8.1) [19][23]. Odhad: dvě až tři věty a jedna úprava
YAML ukázky.

> **Pozor na verze.** První průchod všechny čtyři funkce označil za novinky Symfony 8.1 podle
> dokumentace `current`. Ověření proti CHANGELOGům 2026-09-04 ukázalo, že tři z nich jsou starší
> (7.1, 7.2, 7.3) a jen `--fetch-size` je skutečně z 8.1. Kapitola cílí na Symfony 8, takže
> věcně jsou použitelné všechny – ale **neoznačovat je za novinky 8.1**, to by byla faktická chyba.

**P3-2 — Doplnit Migration Events / Ghost Context.**
Import legacy dat bez historie je situace, do které se dostane každý, kdo ES nasazuje na
existující systém. Verraesův pattern [11] dává jméno a pravidlo („eventy pojmenované
terminologií starého systému, `LegacyCustomerWasImported`"). Odhad: ~25 řádků, provázat
na `/migrace-z-crud`.

**P3-3 — Zkrátit rebuild command a doplnit chybějící interní odkazy.**
Ukázka na ř. 1197–1307 nese ~110 řádků kódu, z toho většina je Console boilerplate. Zároveň
chybí odkazy na `/event-storming` a `/sagy-a-process-managery`. Odhad: úspora ~60 řádků,
přidání dvou odkazů.

**P3-4 — Sjednotit databázi napříč kapitolou.**
DDL je MySQL/MariaDB (ř. 342), tiering mluví o PostgreSQL masteru (ř. 1786), doporučení
`LISTEN/NOTIFY` (ř. 1019) je PostgreSQL-only. Odhad: oprava tří míst, nebo přidání
PostgreSQL varianty DDL.

## 8. Otevřené otázky pro autora

1. **Kapitola má 1830 řádků a ~900 z toho je kód.** Doporučení výše přidávají odhadem
   250–300 řádků. Má kapitola růst, nebo se má část kódu přesunout do doprovodného
   repozitáře `Chapter06_EventSourcing` (frontmatter `github_examples`)? Kandidáti na přesun:
   rebuild command, `UpcasterChain`, snapshot repozitář.
2. **Vlastní store, nebo knihovna?** Kapitola staví vlastní store „pro výuku principů"
   (ř. 149–151) a to je obhajitelné. Otázka je, jestli má na konci přibýt sekce „jak by to
   vypadalo s patchlevel/EventSauce", nebo jestli stačí zmínka v úvodu.
3. **Jak hluboko do GDPR?** Crypto-shredding vs. Forgettable Payloads je rozhodnutí
   s právním rozměrem. Má kniha dávat doporučení, nebo jen popsat obě cesty a odkázat
   na právní posouzení?
4. **Bitemporalita** (`recordedAt` vs. doménový čas) zasahuje do bázové třídy `DomainEvent`,
   která se používá i v jiných kapitolách. Stojí korektnost za konzistenční zásah napříč
   knihou, nebo to zůstane jako poznámka?
5. **Kam patří hranice ES vs. Kafka?** Do této kapitoly, do `/architektonicke-styly`, nebo
   do `/anti-vzory`?
6. **Jak často se bude přehled knihoven aktualizovat?** Bez datované poznámky zestárne
   do roka. Broadway je toho čerstvý příklad.

## 9. Bibliografie

### Ověřené zdroje
`[1]` Martin Fowler — *Event Sourcing*, 12. 12. 2005. https://martinfowler.com/eaaDev/EventSourcing.html
(přímý WebFetch, přístup 2026-09-03)
`[2]` Martin Fowler — *Retroactive Event*, 12. 12. 2005. https://martinfowler.com/eaaDev/RetroactiveEvent.html
(přímý WebFetch, přístup 2026-09-03)
`[3]` Martin Fowler — *Temporal Patterns*, 16. 2. 2005. https://martinfowler.com/eaaDev/timeNarrative.html
(přímý WebFetch, přístup 2026-09-03)
`[4]` Greg Young — *CQRS Documents*, 2010. https://cqrs.wordpress.com/wp-content/uploads/2010/11/cqrs_documents.pdf
(přímý WebFetch na redirect cíl původní URL z kapitoly; PDF staženo a text extrahován
lokálně přes `pdftotext`, citace v sekci 2 jsou z extrahovaného textu, přístup 2026-09-03)
`[5]` Gregory Young — *Versioning in an Event Sourced System*, Leanpub, 2017 (stav „90 % complete",
duben 2017). https://leanpub.com/esversioning (přímý WebFetch stránky knihy, přístup 2026-09-03)
`[6]` Mathias Verraes — *Eventsourcing Patterns: Crypto-Shredding*, 13. 5. 2019.
https://verraes.net/2019/05/eventsourcing-patterns-throw-away-the-key/ (přímý WebFetch,
přístup 2026-09-03)
`[7]` Mathias Verraes — *Eventsourcing Patterns: Forgettable Payloads*, 13. 5. 2019.
https://verraes.net/2019/05/eventsourcing-patterns-forgettable-payloads/ (přímý WebFetch,
přístup 2026-09-03)
`[8]` Mathias Verraes — *Patterns for Decoupling in Distributed Systems: Explicit Public Events*,
11. 5. 2019. https://verraes.net/2019/05/patterns-for-decoupling-distsys-explicit-public-events/
(přímý WebFetch, přístup 2026-09-03)
`[9]` Mathias Verraes — *Eventsourcing: State from Events or Events as State?*, 24. 8. 2019.
https://verraes.net/2019/08/eventsourcing-state-from-events-vs-events-as-state/ (přímý WebFetch,
přístup 2026-09-03)
`[10]` Mathias Verraes — *Eventsourcing Patterns: Multi-temporal Events*, 22. 3. 2022.
https://verraes.net/2022/03/multi-temporal-events/ (přímý WebFetch, přístup 2026-09-03)
`[11]` Mathias Verraes — *Eventsourcing Patterns: Migration Events in a Ghost Context*, 1. 6. 2019.
https://verraes.net/2019/06/eventsourcing-patterns-migration-events-ghost-context/ (přímý WebFetch,
přístup 2026-09-03)
`[12]` Mathias Verraes — index článků, https://verraes.net/ (přímý WebFetch, použito pro
dohledání URL zdrojů [6]–[11], přístup 2026-09-03)
`[13]` Oskar Dudycz — *Event Streaming is not Event Sourcing!*, 1. 12. 2021.
https://event-driven.io/en/event_streaming_is_not_event_sourcing/ (přímý WebFetch, přístup 2026-09-03)
`[14]` Oskar Dudycz — *Never Lose Data Again – Event Sourcing to the Rescue!*, 6. 11. 2022.
https://event-driven.io/en/never_lose_data_with_event_sourcing/ (přímý WebFetch, přístup 2026-09-03)
`[15]` EventSauce — dokumentace, autor Frank de Jonge. https://eventsauce.io/docs/
(přímý WebFetch, přístup 2026-09-03)
`[16]` EventSauce — *Upcasting*. https://eventsauce.io/docs/advanced/upcasting/
(přímý WebFetch, přístup 2026-09-03)
`[17]` Packagist API — metadata balíčků `eventsauce/*`, `prooph/*`, `broadway/*`,
`patchlevel/*`, `ecotone/*`. https://repo.packagist.org/p2/<balíček>.json a
https://packagist.org/search.json (přímé HTTP dotazy přes curl, 2026-09-03)
`[18]` GitHub REST API — stav repozitářů EventSaucePHP/EventSauce, prooph/event-store,
broadway/broadway, patchlevel/event-sourcing, ecotoneframework/ecotone.
https://api.github.com/repos/<owner>/<repo> (přímé HTTP dotazy, 2026-09-03)
`[19]` Symfony — *Messenger: Sync & Queued Message Handling*.
https://symfony.com/doc/current/messenger.html (přímý WebFetch, přístup 2026-09-03)
`[20]` Patchlevel — *Event Sourcing* dokumentace.
https://patchlevel.dev/docs/event-sourcing/latest/ (přímý WebFetch, přístup 2026-09-03)
`[21]` prooph/event-store — README, tabulka „Version Guidance".
https://raw.githubusercontent.com/prooph/event-store/master/README.md (přímé HTTP, 2026-09-03)
`[22]` KurrentDB — README, sekce „What is Kurrent".
https://raw.githubusercontent.com/kurrent-io/KurrentDB/master/README.md (přímé HTTP, 2026-09-03)

### Neověřené / nedohledané

> **Doplňkové hledání proběhlo 2026-09-04.** První průchod běžel bez `WebSearch` (vyčerpaný
> rozpočet 200/200) a pokryl jen URL, které šlo odhadnout nebo dohledat z indexů. Druhý průchod
> body níže prošel; vyřešené jsou označené a přesunuté na konec sekce.

- **Datum přejmenování Event Store → Kurrent – DOHLEDÁNO, viz [24].** Tisková zpráva je
  datovaná **24. 12. 2024** (aktualizace 12. 2. 2025): „Event Store, a startup that aims to unify
  streaming data systems and databases, today announced today that it has changed its name to
  Kurrent to better reflect its goals.“ Produkt **EventStoreDB se přejmenoval na KurrentDB**.
- **Přesné znění citací z *CQRS Documents* [4] pro optimistic concurrency a rebuild projekcí.**
  Ověřeny a doslovně přepsány jsou pasáže „CQRS and Event Sourcing", „There is no Delete",
  „Rolling Snapshots" a „Building an Event Storage / Structure / Operations". Pasáže
  k rebuildu projekcí nebyly v extrahovaném textu dohledány.
- **Greg Young, *Versioning in an Event Sourced System*** [5] — obsah ověřen jen z popisu
  a obsahu na Leanpubu. Formulace k Double Write a Versioning of Behaviour v sekci 2
  vycházejí z názvů kapitol, ne z jejich textu. Před citováním v knize je třeba text ověřit.
- **Youngova série přednášek k verzování** (dohledatelné záznamy z DDD Europe) — nedohledáno.
- **Vernon, *Implementing DDD* (2013), kapitola o Event Sourcingu** — nebylo možné ověřit
  online, kniha není v digitálně dostupné podobě přes WebFetch. Doporučuji ověřit z tištěného
  vydání; je to zdroj, který v kapitole zcela chybí.
- **Khononov, *Learning DDD* (2021)** — totéž; obsahuje kapitolu o event-sourced doménovém
  modelu s argumentací „kdy ES nepoužívat", která by G5 a 13.03 posílila.
- **Statistika neúspěšných nasazení ES.** Zadání očekávalo „nejčastější důvody selhání".
  Kvantifikovaný, citovatelný zdroj se nepodařilo najít; jediný dohledaný údaj o adopci
  je odkaz na InfoQ Architecture Trends 2022 zprostředkovaný přes [14], samotná zpráva
  ověřena nebyla.
- **Symfony verze funkcí Messengeru – OVĚŘENO proti CHANGELOGům [23]; tři ze čtyř byly špatně.**
  `use_notify` (LISTEN/NOTIFY) je od **7.1**, `#[AsMessage]` od **7.2**, `keepalive` od **7.3**;
  jen `--fetch-size` je skutečně z **8.1**, spolu s `AmqpPriorityStamp` (AMQP-specifický, ne
  obecný „PriorityStamp“). Promítnuto do sekce 4 a do doporučení P3-1.

### Doověřeno druhým průchodem (2026-09-04)

`[23]` CHANGELOGy Symfony komponent, přímé HTTP na `raw.githubusercontent.com`, větev 8.1:
`symfony/messenger`, `symfony/doctrine-messenger`, `symfony/redis-messenger`, `symfony/amqp-messenger`.
Verze funkcí odečtené z hlaviček verzí v CHANGELOGu, ne z dokumentace `current`.

`[24]` Kurrent – tisková zpráva *Event Store Changes Name to Kurrent, Raises $12M*, 24. 12. 2024.
https://kurrentdb.kurrent.io/press/event-store-changes-name-to-kurrent-raises-12m-to-unify-streams-and-databases

`[25]` Packagist a GitHub API, stav PHP knihoven pro Event Sourcing k 2026-09-04:

| Balíček | Poslední vydání | Stav |
|---|---|---|
| `eventsauce/eventsauce` | 3.9.1 (3. 5. 2026) | aktivní, 870 hvězd |
| `broadway/broadway` | 3.0.1 (9. 8. 2026) | **abandoned** na Packagistu, repozitář **archivovaný** |
| `prooph/event-store` | v7.12.3 (21. 4. 2025) | aktivní, poslední push 3. 5. 2026, 548 hvězd |
| `ecotone/ecotone` | 2.0.0-beta.1 (28. 8. 2026) | aktivní, řada 2.0 zatím v beta |
| `patchlevel/event-sourcing` | 3.21.0 (26. 8. 2026) | aktivní, 215 hvězd; kapitola ji nezmiňuje |

**Dopad na kapitolu (ř. 138–150).** Nález G1 potvrzen a zpřesněn: Broadway není „funguje, ale
tempo vývoje zvolnilo“ – je archivovaná a označená jako opuštěná. Tvrzení o proophu je naopak
v pořádku: řada 7.x žije (v p2 feedu ji zakrývá starší `v8.0.0-RC-1` z roku 2019, což svádí
k opačnému závěru). U Ecotone stojí za zmínku, že řada 2.0 je zatím beta. Jako podnět, ne
povinnost: `patchlevel/event-sourcing` je aktivní knihovna s nativní integrací do Symfony,
kterou přehled vynechává.

### Doověřeno devátým kolem (2026-09-05)

**OPRAVENO — nedoložená citace.** Kapitola uváděla kurzívou *„current state is derived from
the history of events"* jako formulaci principu. Věta **není z Fowlerova článku ani odjinud** —
ověřeno proti plnému textu martinfowler.com/eaaDev/EventSourcing.html. Nahrazena Fowlerovou
skutečnou definicí: *„Event Sourcing ensures that all changes to application state are stored
as a sequence of events."*

**Kód:** ES agregát dostal chybějící definici `OrderItem` (testy v `testing_ddd` ji používají),
factory `create()` → `place()`, holá `\DomainException` → `EmptyOrderException` /
`InvalidOrderStateTransitionException`, namespace `App\Shared` → `App\SharedKernel`,
přiznána odchylka s primitivními identifikátory.
