# Studie: Ságy a Process Managery

- **Kapitola:** `content/chapters/sagas.md` (č. 14, kategorie Vzory, 1777 řádků)
- **Cesta:** /sagy-a-process-managery
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| úvod | 22–28 | Navazuje na Event Sourcing; ságy koordinují napříč agregáty a BC | – | krátké, funkční |
| 14.01 Proč potřebujeme ságy | 29–86 | E-shop se čtyřmi BC, 2PC se nehodí, sága jako řešení; terminologické vymezení Saga vs. Process Manager | Garcia-Molina & Salem 1987; Vernon 2013 kap. 4 | jediné místo, kde se objevuje původní paper – dvě věty |
| 14.02 Kompenzační transakce | 87–176 | Kompenzace je sémantická, ne technická; tabulka akce↔kompenzace; `CompensatableCommand` | – | 68 ř. kódu na rozhraní, které kapitola dál nikdy nepoužije |
| 14.03 Choreografie | 177–315 | Bez koordinátora, tři handlery, Messenger routing | – | 3 handlery = 3× tentýž kód |
| 14.04 Limity choreografie | 316–373 | Čtyři problémy: neviditelný tok, OCP, diagnostika, timeouty | – | nejhutnější prozaická sekce kapitoly, 0 řádků kódu |
| 14.05 Orchestrace – Process Manager | 374–572 | `OrderProcessManager` jako stavový automat, `match(true)` dispatch, 6 privátních metod | – | jeden code blok má 135 řádků |
| 14.06 Perzistence stavu ságy | 573–989 (417 ř., 23 %) | Doctrine entita, repozitář, optimistic lock, multi-worker race, deadlocky, recovery, ACD bez I, semantic lock | Richardson 2018 kap. 4 | nejsilnější a zároveň nejpřetíženější sekce; míchá 6 různých témat |
| 14.07 Implementace v Symfony Messenger | 990–1112 | Konfigurace busů, transportů, retry; odkaz na Outbox | – | z 122 řádků je 74 YAML/PHP |
| 14.08 Timeouty a deadliny | 1113–1266 | `CheckSagaTimeout` + `DelayStamp`, konfigurovatelné timeouty, požadavky na transport | – | timeout se plánuje jen pro první krok |
| 14.09 Kompenzační strategie | 1267–1411 | Forward vs. backward recovery, `compensate()` v opačném pořadí, compensation pending, pivot | Richardson 2018 kap. 4 | pojmy „forward/backward recovery“ použity v jiném významu než v [1] |
| 14.10 Paralelní kroky | 1412–1506 | Synchronizační bariéra přes dva booleany v kontextu | – | fork/join bez zmínky o závislosti save-pointů |
| 14.11 Monitoring a observabilita | 1507–1600 | Korelační ID, `CorrelationIdStamp`, cron pro zaseklé ságy, Prometheus/Grafana | – | 48 ř. Console commandu na triviální výpis |
| 14.12 Testování ság | 1601–1764 | „Testujeme na třech úrovních“ + unit testy Process Manageru, in-memory repozitář | – | slíbí tři úrovně, dodá jednu (viz G14) |
| FAQ | 1765–1777 | 5 otázek; první rozebírá Saga vs. Process Manager | EIP (Hohpe & Woolf), Richardson | nejpřesnější terminologická pasáž kapitoly je schovaná ve FAQ |

Kapitola je z **54,9 % kód** (976 z 1777 řádků, 24 bloků `:::code`). Dva bloky mají 135 a 127 řádků,
další čtyři přes 45. Poměr se zlomil ve prospěch kódu už v sekci 14.05 a od té chvíle se nesrovnal.
Nejvíc prostoru dostává perzistence stavu (417 řádků) – a je to prostor zasloužený, protože právě
tam kapitola říká věci, které jinde nenajdete česky: multi-worker race, logický deadlock, ACD bez I,
semantic lock. Naproti tomu **historie a terminologie vzoru dostane dvě věty**. Původní paper z roku 1987
je citován, ale nikdo nečtenáři neřekne, že sága v něm znamená něco jiného než dnes. Udi Dahan, jehož
pojetí ságy jako stavového message handleru stojí za vším, co kapitola v sekcích 14.05 a 14.06 ukazuje,
není zmíněn ani jednou. Chybí Routing Slip, chybí Symfony Workflow, chybí externí orchestrátory.
Kapitola tedy dobře učí *jak* napsat ságu v Symfony a špatně vysvětluje, *co to vlastně je*
a *kdy si ji nepsat sám*.

## 2. Kanonické zdroje k tématu

### 2.1 Co sága v roce 1987 skutečně znamenala

Paper Hectora Garcii-Moliny a Kennetha Salema *Sagas* vyšel na ACM SIGMOD 1987 [1]. Definice zní:
„Let us use the term saga to refer to a LLT that can be broken up into a collection of sub-transactions
that can be interleaved in any way with other transactions." LLT je *long lived transaction*.

Čtyři věci, ve kterých se originál liší od dnešního použití:

1. **Motivací nebyla distribuce, ale výkon.** Paper otevírá tím, že dlouhé transakce drží zámky
   a blokují krátké: „other transactions wishing to access the LLT's objects suffer a long locking delay".
   Sága zámky uvolňuje po každé sub-transakci. Autonomie služeb, oddělené databáze ani message brokery
   v paperu nefigurují.
2. **Prostředím je jedna centralizovaná databáze.** Explicitně: „Due to space limitations, we only
   discuss sagas in a centralized system, although clearly they can be implemented in a distributed
   database system." [1] Sub-transakce jsou obyčejné ACID transakce nad týmž DBMS.
3. **Model je orchestrovaný, ne choreografický.** Paper zavádí *SEC* (Saga Execution Component)
   a *TEC* (Transaction Execution Component). SEC čte log, po pádu zjistí poslední nezkompenzovanou
   transakci a spustí kompenzace. Choreografie – tok emergentně vznikající z reakcí účastníků –
   v originále neexistuje.
4. **Garance je slabší, než se tradičně cituje.** Systém garantuje buď `T1…Tn`, nebo `T1…Tj, Cj, …, C1`.
   K tomu paper dodává: „Note that other transactions might see the effects of a partial saga execution.
   When a compensating transaction Cj is run, no effort is made to notify or abort transactions that
   might have seen the results of Tj." [1] To je přímý předchůdce dnešního „ságy nemají izolaci".

Paper obsahuje i tři věci, které dnešní texty o ságách vypouštějí a kapitola je nemá:

- **Kritérium, kdy LLT ságou být nemůže.** Příklad s převodem peněz: `T1` peníze odečte, `T3` je připíše,
  mezitím je částka v lokální proměnné. „After T1 completes, the database is left in an inconsistent
  state because some money is 'missing' […] Therefore, L cannot be run as a saga." [1] Test je jasný:
  pokud je mezistav ságy pro doménu *nečitelný*, ne pouze dočasně neúplný, vzor se nehodí.
- **Forward recovery přes save-pointy.** Sekce 5 a 6 [1] rozlišují backward recovery (kompenzace),
  smíšenou a *pure forward recovery* – restart od save-pointu bez kompenzací. „Pure forward recovery
  does not require compensating transactions. So if compensating transactions are hard to write,
  then one has the choice of tailoring the application so that LLTs do not have user initiated aborts." [1]
  To je jiná věc než retry jednoho kroku.
- **Paralelní ságy a nekonzistentní save-pointy.** Sekce 8 [1] zavádí fork/join a upozorňuje na
  *cascading rollbacks*: save-point ve větvi může záviset na transakci, která je právě kompenzována
  v jiné větvi, a tím se stává nepoužitelným.

Nezanedbatelné je i to, co paper říká o obtížně vratných akcích: dopis se kompenzuje druhým dopisem,
šek stop-payment příkazem. „Of course, it would be desirable not to have to compensate for such actions.
However, the price of running LLTs as regular transactions may be so high that one is forced to write
sagas and their compensating transactions." [1] Kompenzace je tedy v originále poslední možnost, ne
výchozí návrhový styl.

### 2.2 Process Manager a Routing Slip (Hohpe & Woolf)

*Process Manager* z Enterprise Integration Patterns řeší otázku: „How do we route a message through
multiple processing steps when the required steps may not be known at design-time and may not be
sequential?" Řešení: „Use a central processing unit, a Process Manager, to maintain the state of the
sequence and determine the next processing step based on intermediate results." [3] Vzniká
*hub-and-spoke* topologie: příchozí trigger message vytvoří instanci, ta posílá zprávy, přijímá
odpovědi a rozhoduje o dalším kroku.

*Routing Slip* je jeho jednodušší sourozenec: „Attach a Routing Slip to each message, specifying the
sequence of processing steps. Wrap each component with a special message router that reads the Routing
Slip and routes the message to the next component in the list." [4] Rozdíl formulují autoři sami:
Routing Slip předpokládá, že „the sequence of processing steps has to be determined up-front and the
sequence is linear" [3]. Stav procesu cestuje **se zprávou**, ne v databázi – proto Routing Slip
nepotřebuje perzistentní úložiště ani korelaci, ale neumí větvení, paralelismus ani timeouty.

Hohpe & Woolf zároveň Process Manager nedoporučují paušálně: „may be overkill" a „can distract from
the core design issue and also cause significant performance overhead" [3].

### 2.3 Proč komunita termíny míchá

Nejlepší doložený rozbor záměny je *A Saga on Sagas* z průvodce Microsoft patterns & practices
*CQRS Journey* [5]. Tým tam explicitně vysvětluje, proč termín *saga* opustil:

„The term saga is commonly used in discussions of CQRS to refer to a piece of code that coordinates
and routes messages between bounded contexts and aggregates. However, for the purposes of this guidance
we prefer to use the term process manager […] There is a well-known, pre-existing definition of the
term saga that has a different meaning from the one generally understood in relation to CQRS." [5]

A dodává dělicí čáru, kterou dnes skoro nikdo nepoužívá: „Typically, you would expect to see a process
manager routing messages between aggregates **within** a bounded context, and you would expect to see
a saga managing a long-running business process that spans **multiple** bounded contexts." [5]

Ještě jedno omezení, které kapitola porušuje: „It's important to note that the process manager does not
perform any business logic. It only routes messages, and in some cases translates between message types."
a v seznamu kontraindikací: „You should not use a process manager to implement any business logic in
your domain. Business logic belongs in the aggregate types." [5]

### 2.4 Udi Dahan a sága jako stavový message handler

Článek *Sagas: not just for workflows*, na který se v komunitě běžně odkazuje, se **na udidahan.com
nepodařilo dohledat** – URL vrací 404, v archivu dubna 2007 ani ve výsledcích vyhledávání na webu se
nevyskytuje (viz sekce 9). Dohledatelné jsou tři jeho texty a dokumentace NServiceBus:

- *The Danger of Centralized Workflows* [12] je jeho hlavní argument proti orchestraci nástroji:
  „you are still writing code. The fact that it doesn't look like the rest of your code doesn't change
  that fact." Alternativa: „small pieces of code, each encapsulating a single business responsibility,
  working in concert with each other – reacting to each others events."
- *Sagas and Unit Testing* [13] ukazuje ságu jako komponentu držící stav (pending autorizace, data
  objednávky, příznak dokončení) a testovanou přes očekávání odeslaných zpráv: „By keeping them
  disconnected from any communications or persistence technology […] it should be fairly easy to use
  mock objects to test them."
- *NServiceBus Saga Tips* [14]: timeouty nepatří natvrdo do kódu ságy, ale do jejích properties –
  „don't have your saga code call out to some configuration infrastructure directly" – a je nutné
  vyřešit, co změna konfigurace udělá s ságami, které už běží.
- Dokumentace NServiceBus [11] definuje ságu jako message-driven stavový automat: „all public get/set
  properties are persisted by default", korelace hledá existující instanci podle dat příchozí zprávy,
  timeout je „an upper limit to the waiting period for messages", konzistence stavu a messagingu se
  drží pesimistickým nebo optimistickým zámkem podle persisteru. Doporučení: ságy jako lehké
  orchestrátory, které nesahají přímo na externí zdroje.

Dahanovo pojetí je tedy „stavový handler zpráv s korelací a timeouty", ne „distribuovaná transakce
s kompenzacemi". Kompenzace v jeho textech skoro nefigurují – to je Richardsonova linie.

### 2.5 Choreografie, orchestrace a Event Collaboration

Fowlerův *Event Collaboration* [15] popisuje styl, ze kterého choreografie vychází: „components raise
events when things change. Other components then listen to events and react appropriately", „the sender
is just broadcasting the event, the sender does not need to know who is interested and who will respond".
Fowler sám hodnotí kompromis přesně tak, jak to kapitola potřebuje: „The great strength of Event
Collaboration is that it affords a very loose coupling between its components; this, of course, is also
its great weakness." **Pozor:** Fowler v tomto textu slova *saga*, *process manager* ani *choreography*
nepoužívá – nelze ho citovat jako zdroj těch pojmů.

Richardson na microservices.io definuje ságu jako „a sequence of local transactions. Each local
transaction updates the database and publishes a message or event to trigger the next local transaction
in the saga" a jmenuje obě varianty koordinace [2]. Dvě nevýhody uvádí explicitně: nutnost ručně
navrhnout kompenzace a chybějící izolace – sága „lacks isolation (the 'I' in ACID)" [2].

## 3. Stav praxe a posuny

**Kyvadlo se vrátilo k orchestraci.** Kolem roku 2016 platila choreografie za defaultní volbu.
Dnes i zdroje z tábora workflow enginů argumentují, že spor je špatně položený. Camunda [19]:
„Orchestration is often perceived as being tighter coupled. But this is not true" – diskuse podle nich
zaměňuje způsob komunikace (sync vs. async) za architektonické coupling; orchestrátor může posílat
zprávy brokerem stejně dobře jako choreografie. Doporučení je hybridní: jádrový proces orchestrovat,
okrajové reakce (věrnostní body, newsletter) nechat choreografii. Kapitola tento posun v podstatě
odráží, ale prezentuje ho jako svůj vlastní závěr, ne jako doložený stav debaty.

**Durable execution jako konkurent ručně psané sáze.** Temporal definuje durable execution jako záruku,
že aplikace „will run to completion" navzdory pádům [17]. Stav se nedrží v ručně navržené tabulce,
ale v *Event History* – „a complete and durable log of everything that has happened in the lifecycle
of a Workflow Execution" – a po pádu worker „uses the Event History to replay the code and recreate the
state of the Workflow Execution to what it was immediately before the crash" [17]. Retry aktivit je
konfigurace, ne kód. Kompenzace zůstává na vývojáři: „the manner of compensation depends on the
particular scenario […] you, the developer, need to define it in your program" [18]. Temporal má
oficiální PHP SDK; Camunda 8 (Zeebe) se v PHP integruje přes gRPC/REST. Kapitola tuto alternativu
nezmiňuje vůbec – čtenář z ní odchází s dojmem, že ruční Process Manager je jediná cesta.

**Idempotence se přesunula z „dobré praxe" do dokumentace frameworku.** Symfony Messenger dnes má
vlastní pasáž o idempotenci [6] s explicitním varováním, které je pro kapitolu důležité (viz G7):
„A UUID generated at dispatch time is not suitable as an idempotency key […] The key must remain stable
across all dispatches of the same logical event."

**Exactly-once zůstává iluzí a je to formálně doložitelné.** Treat [16] to opírá o FLP a problém dvou
generálů: „Within the context of a distributed system, you cannot have exactly-once message delivery."
A praktický závěr: „The way we achieve exactly-once delivery in practice is by faking it. Either the
messages themselves should be idempotent […] or we remove the need for idempotency through deduplication."
Kapitola dvakrát zmiňuje at-least-once, ale nikdy neřekne, proč exactly-once nelze mít.

## 4. Symfony / PHP specifika

**Union typy v handleru fungují.** `MessengerPass::guessHandledClasses()` v Symfony 7.3 obsahuje větev
`if ($type instanceof \ReflectionUnionType)`, projde členy typu a všechny non-builtin registruje jako
obsluhované zprávy [9]. Konstrukce z kapitoly (`OrderPlaced|PaymentSucceeded|…` v `__invoke`) je tedy
platná. `ReflectionIntersectionType` podporován není. To stojí za explicitní poznámku v knize, protože
oficiální dokumentace o union typech mlčí [6].

**`DelayStamp` a transporty.** Doctrine transport skutečně řeší odklad sloupcem `available_at`;
v `Connection::send()` se počítá `$availableAt = $now->modify(sprintf('%+d seconds', $delay / 1000))` [10].
AMQP transport používá delay exchange konfigurovaný přes `delay[exchange_name]` (výchozí `delays`) [6] –
tvrzení kapitoly, že plugin `rabbitmq-delayed-message-exchange` není potřeba, sedí.

**`retry_strategy` má víc klíčů, než kapitola ukazuje.** Kromě `max_retries`, `delay` a `multiplier`
existují `max_delay`, `jitter` (0–1.0) a `service` pro vlastní `RetryStrategyInterface` [6]. `jitter`
je pro ságy relevantní: bez něj se po výpadku externí služby všechny ságy vrhnou na retry současně.

**`failure_transport` lze nastavit per transport** [6]. Pro ságy to má smysl – selhaná kompenzace patří
do jiné fronty než selhaná projekce.

**Worker má víc pojistek než `--time-limit`.** Dokumentace uvádí `--limit`, `--memory-limit`,
`--fetch-size`, `--queues`, `--all`, `--exclude-receivers` a `--keepalive` (značí zprávu jako
rozpracovanou a brání redelivery u dlouhých handlerů) [6]. `--keepalive` je pro ságy s pomalými
externími voláními přímo relevantní.

**`TransportMessageIdStamp` není idempotency key.** Podle dokumentace ho AMQP transport přidává při
odeslání a přijetí a slouží „to improve logging context when messages fail and are retried" [6].
Je to identifikátor *přenosu*, ne logické události.

**Symfony Scheduler nahrazuje cron.** `RecurringMessage::cron()` / `::every()`, `#[AsSchedule]`,
`#[AsCronTask]`, `#[AsPeriodicTask]`, `JitterTrigger`, `ExcludeTimeTrigger` [8]. Detekce zaseklých ság
z kapitoly 14.11 je učebnicový případ – místo Kubernetes CronJobu stačí `#[AsCronTask]` na příkazu.

**Symfony Workflow ságou není a je dobré to říct.** Komponenta rozlišuje `workflow` (více míst současně)
a `state_machine` (jedno místo), má marking store, guards, sedm událostí a od nedávna podporu backed
enumů jako places a vážené přechody [7]. O sagách, kompenzacích, timeoutech ani distribuované koordinaci
dokumentace nemluví – je to synchronní správa stavu jednoho objektu. Použitelná je jako *definice*
stavového automatu ságy (validace přechodů, guards), ne jako běhový koordinátor. Kapitola komponentu
nezmiňuje vůbec, což je u knihy o Symfony nápadné.

## 5. Sporné a chybně podávané body

**Sága vs. Process Manager: tři neslučitelné definice.** (a) *CQRS Journey* [5]: process manager routuje
uvnitř BC, sága překračuje hranice BC. (b) Kapitola a část komunity: sága je zastřešující pojem,
Process Manager je jeho orchestrovaná podoba. (c) Richardson [2]: sága je oboje, orchestrátor je
implementační detail. Doporučení pro knihu: nechat současnou konvenci (b), ale **doložit ji** a přidat
odstavec, že (a) i (c) existují a proč. Kapitola to dnes dělá jen ve FAQ na řádku 1766 – patří to
do sekce 14.01.

**„Ságu navrhli Garcia-Molina a Salem v roce 1987."** Formálně pravda, obsahově zavádějící. Paper řeší
jednu centralizovanou databázi a motivací je držení zámků [1]. Dnešní sága je jiný vzor se stejným
jménem. Kniha by měla rozdíl pojmenovat, ne ho zamlčet – je to jeden z mála bodů, kde se dá čtenáři
nabídnout něco, co v běžných článcích nenajde.

**Forward / backward recovery.** Kapitola v 14.09 nazývá forward recovery „retry" jednoho kroku [1] –
v paperu je to restart od save-pointu bez kompenzací, tedy jiná věc. Buď použít původní význam
a doplnit save-pointy, nebo termín nepoužívat a mluvit o retry a kompenzaci.

**„Process Manager nesmí obsahovat doménovou logiku."** [5] Kapitolový `OrderProcessManager` rozhoduje,
kdy kompenzovat a co, a rozhoduje o timeoutech – to už doménová rozhodnutí jsou. Spor je legitimní:
Richardson orchestrátoru rozhodovací logiku přiznává, patterns & practices ne. Kniha by měla zaujmout
stanovisko a zdůvodnit ho, ne to obejít.

**Choreografie „do tří kroků".** Číslo se v kapitole objevuje třikrát (ř. 314, 370, FAQ) a nemá zdroj.
Doložitelné je jen kvalitativní kritérium z [3] (větvení, neznámá sekvence, paralelismus → Process
Manager) a Camundovo hybridní doporučení [19]. Buď číslo opustit, nebo ho označit jako autorskou
heuristiku.

**Orchestrace = coupling.** Kapitola tento názor implicitně přebírá v 14.03 („hlavní výhodou choreografie
je volné provázání"). Camunda ho výslovně odmítá [19], Dahan ho naopak zastává [12]. Obě strany stojí
za jednu větu.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | mělké | `sagas.md:65–79` | Paper z 1987 citován, ale rozdíl mezi původní a dnešní ságou (centralizovaná DB, motivace zámky, SEC jako orchestrátor, žádná choreografie) chybí | Doplnit 15–25 řádků podle sekce 2.1 |
| G2 | chybí | sekce 14.01 | Kritérium z [1], kdy proces ságou být nemůže (nečitelný mezistav – „chybějící peníze") | Přidat jako callout `warn`; je to nejlepší praktický test v celé literatuře |
| G3 | chybí | celá kapitola | Udi Dahan a pojetí ságy jako stavového message handleru s korelací a timeouty | Odstavec v 14.01 nebo 14.05, zdroje [11][12][13][14] |
| G4 | chybí | sekce 14.05 | Routing Slip jako lehčí alternativa k Process Manageru (stav cestuje se zprávou) | Callout `note`, 10–12 řádků, zdroj [4] |
| G5 | chybí | celá kapitola | Externí orchestrátory (Temporal durable execution, Camunda/Zeebe) jako alternativa k ručnímu Process Manageru | Nová podsekce ~30 řádků, zdroje [17][18][19] |
| G6 | chybí | celá kapitola | Symfony Workflow – proč to ságou není a k čemu se přesto hodí | Callout v 14.05, zdroj [7] |
| G7 | sporné | `sagas.md:840–844`, `sagas.md:856–860` | `$eventId` se čte z obálky Messengeru (`TransportMessageIdStamp`). Ten identifikuje přenos, ne logickou událost, a Symfony explicitně varuje, že klíč musí být stabilní napříč všemi dispatchi téže události | Nahradit stabilním `eventId` neseným v samotné události; opravit i větu o `TransportMessageIdStamp` [6] |
| G8 | nepodložené | `sagas.md:314`, `sagas.md:370` | „Procesy o dvou až třech krocích si s choreografií vystačí" – bez zdroje, opakováno třikrát | Buď označit jako heuristiku, nebo nahradit kvalitativním kritériem z [3] |
| G9 | sporné | `sagas.md:1273–1281` | „Forward recovery (retry)" – v [1] znamená restart od save-pointu bez kompenzací, ne opakování kroku | Přejmenovat, nebo doplnit původní význam a save-pointy |
| G10 | chybí | sekce 14.06 nebo 14.07 | Dual-write mezi uložením stavu ságy a dispatchem příkazu. Callout na ř. 1092 řeší jen směr agregát→event | Rozšířit callout o směr sága→command |
| G11 | mělké | `sagas.md:1113–1244` | Timeout se plánuje jen jednou, pro `AwaitingPayment` u `OrderPlaced`. Pro ostatní stavy (`AwaitingStockReservation`, `AwaitingShipment`, `Compensating`) žádný timeout nevzniká, přestože 14.08 slibuje per-krok timeouty | Doplnit plánování timeoutu při každém přechodu, nebo přiznat, že příklad je zjednodušený |
| G12 | sporné | `sagas.md:800–808` | Prose tvrdí, že po pádu workeru sága „ví, že čeká na platbu, a pokračuje od správného kroku". Ukázaný `onPaymentSucceeded` žádný guard nemá – přechod a `ReserveStock` proběhnou znovu | Přeformulovat, nebo guard přesunout do hlavní ukázky (dnes je až o 40 řádků níž) |
| G13 | mělké | `sagas.md:1412–1506` | Paralelní kroky bez zmínky o závislosti save-pointů a cascading rollbacku, který řeší už [1] sekce 8 | 8–12 řádků: kompenzace jedné větve může zneplatnit postup druhé |
| G14 | chybí | `sagas.md:1603–1605` | „Testujeme na třech úrovních" – dodána je jedna (unit testy). Integrační a end-to-end úroveň chybí | Buď doplnit dvě úrovně, nebo větu opravit |
| G15 | chybí | celá kapitola | Proč exactly-once neexistuje (FLP, problém dvou generálů) a co znamená „effectively-once" | Callout 8–10 řádků, zdroj [16] |
| G16 | nadbytečné | `sagas.md:109–164` (56 ř.) | Rozhraní `CompensatableCommand` a `ChargeCustomer::compensation()`. `OrderProcessManager` ani `compensate()` je nikdy nepoužijí – kompenzace se konstruují ručně | Buď rozhraní v `compensate()` skutečně použít, nebo obojí vyškrtnout |
| G17 | nadbytečné | `sagas.md:197–291` (95 ř.) | Tři choreografické handlery jsou téměř identické | Nechat jeden, zbylé dva popsat prózou; úspora ~55 řádků |
| G18 | nadbytečné | `sagas.md:1534–1585` (52 ř.) | `CheckStaleSagasCommand` je běžný Console boilerplate bez ságové specifiky | Zkrátit na metodu `execute()`, nebo nahradit `#[AsCronTask]` variantou [8] |
| G19 | nadbytečné | `sagas.md:1616–1760` (145 ř.) | Unit test + in-memory repozitář; anonymní spy bus s referencí v konstruktoru zabere 25 řádků na věc, kterou udělá jednodušší třída | Zkrátit na dva testy a odkázat na kapitolu Testování DDD |
| G20 | sporné | `sagas.md:961–974` | `Order::place(): void` jako instanční metoda nastavující semantic lock. Kánon knihy má `Order::place()` jako pojmenovaný konstruktor (`CLAUDE.md`) | Přejmenovat na `startApprovalProcess()` nebo `lockForSaga()` |
| G21 | mělké | `sagas.md:1002–1044` | Routing v YAML neobsahuje `CheckSagaTimeout`, `CancelOrder`, `ConfirmOrder`, ani eventy `RefundSucceeded`/`RefundFailed`, které kapitola později používá | Doplnit, nebo přidat větu, že výpis je zkrácený |
| G22 | chybí | sekce 14.06 | `processedEventIds` v JSON sloupci roste bez omezení a `in_array` kontrola není atomická – dva workery projdou guardem současně, chrání až optimistický zámek | 5–8 řádků k limitům řešení |
| G23 | chybí | sekce 14.01 nebo 14.05 | Omezení z [5]: Process Manager nemá obsahovat doménovou logiku, jen routovat a překládat zprávy | Zaujmout stanovisko a zdůvodnit odchylku |
| G24 | mělké | `sagas.md:1766–1777` | Nejpřesnější terminologický výklad kapitoly je ve FAQ, kam se čtenář dostane až po 1700 řádcích | Přesunout jádro do 14.01, FAQ zkrátit na odkaz |

## 7. Doporučení k přepisu

**P1-1 — Přepsat historicko-terminologickou pasáž v 14.01.**
Dnes jsou to dva odstavce, které tvrdí, že vzor pochází z roku 1987, a tím to končí. Čtenář si odnese
chybnou představu, že dnešní distribuovaná sága je totéž, co paper popsal. Nová pasáž má říct: co sága
v [1] znamenala (jedna DB, zámky, SEC), čím se dnešní použití liší, kde do toho vstoupil EIP Process
Manager [3], proč *CQRS Journey* termín opustil [5] a jakou konvenci volí tato kniha.
Odhad: **přepis sekce 14.01, +40 řádků**.

**P1-2 — Opravit idempotenci ságy: `eventId` musí být stabilní.**
Kapitola staví idempotenci na ID z obálky Messengeru. Symfony dokumentace přesně tuto konstrukci
odmítá [6] a technicky má pravdu: transport ID se mění při přebalení na retry queue. Celá sekce
14.06 tak stojí na vadném základu. Oprava znamená přidat `eventId` do doménových událostí (nebo použít
outbox ID) a upravit tři ukázky plus dva odstavce.
Odhad: **oprava sekce 14.06, ~15 řádků + 3 code bloky**.

**P1-3 — Doplnit kritérium použitelnosti ságy.**
Kapitola nikde neříká, kdy ságu **nepoužít**. Paper [1] nabízí ostrý test: pokud je mezistav ságy pro
doménu nekonzistentní, ne jen neúplný, vzor selže. K tomu patří kategorie akcí, které kompenzovat nelze
(odeslaný e-mail, předaná data třetí straně, vytištěný doklad) a co s nimi – [1] doporučuje kompenzovat
druhou akcí, ne rušením první.
Odhad: **nová podsekce v 14.02, ~35 řádků**.

**P1-4 — Srovnat prózu s kódem v 14.06 a 14.08.**
Dvě konkrétní nesrovnalosti: text popisuje obnovu po pádu workeru, kterou ukázaný kód neumí (G12),
a slibuje per-krok timeouty, které se plánují jen pro první krok (G11). Obojí čtenáře svede k tomu,
že opíše kód a spolehne se na chování, které nenastane.
Odhad: **oprava dvou odstavců + doplnění jedné metody**.

**P2-1 — Přidat sekci o alternativách k ručnímu Process Manageru.**
Temporal a Camunda dnes stojí za rozhodnutím, které kapitola čtenáři vůbec nenabízí: durable execution
řeší persistenci stavu, retry i timeouty za vás [17], kompenzaci stále píšete sami [18]. Sekce má být
střízlivá – provozní cena externího orchestrátoru je vysoká a pro jednu ságu se nevyplatí.
Odhad: **nová sekce ~35 řádků**.

**P2-2 — Zkrátit kód o 200–250 řádků.**
Kapitola je z 54,9 % kód. Konkrétní kandidáti: `CompensatableCommand` a jeho implementace (G16, 56 ř.,
nikde se nepoužijí), dva ze tří choreografických handlerů (G17, ~55 ř.), `CheckStaleSagasCommand`
(G18, ~35 ř.), testovací blok (G19, ~70 ř.). Uvolněné místo pokryje P1-1, P1-3 a P2-1 bez růstu kapitoly.
Odhad: **úprava pěti code bloků**.

**P2-3 — Rozdělit sekci 14.06.**
417 řádků a šest témat: entita, repozitář, multi-worker, deadlocky, recovery, izolace. Přirozený řez je
mezi perzistencí (entita + repozitář + optimistic lock) a souběžností (multi-worker, deadlock, ACD,
semantic lock). Druhá polovina je nejlepší materiál kapitoly a v současné podobě ji čtenář najde
schovanou pod nadpisem o perzistenci.
Odhad: **rozdělení na 14.06 a 14.07, přečíslování následujících sekcí**.

**P2-4 — Doplnit Routing Slip a Symfony Workflow.**
Dvě krátké pasáže, které zasadí Process Manager do kontextu. Routing Slip [4] ukazuje, že ne každý
vícekrokový proces potřebuje perzistentní stav. Workflow [7] odpoví na otázku, kterou si čtenář knihy
o Symfony položí sám a kapitola ji ignoruje.
Odhad: **dva callouty, ~25 řádků**.

**P2-5 — Callout o exactly-once.**
Kapitola dvakrát říká „at-least-once" a nikdy nevysvětlí proč. Odkaz na FLP a problém dvou generálů [16]
tomu dá pevný základ a zároveň zdůvodní, proč se idempotenci v celé kapitole věnuje tolik místa.
Odhad: **callout ~10 řádků**.

**P3-1 — Doplnit `jitter`, `--keepalive` a per-transport `failure_transport`.**
Tři konkrétní věci z dokumentace Messengeru [6], které kapitola nezmiňuje a které mají v ságách přímé
použití. `jitter` brání retry bouři, `--keepalive` chrání dlouhé kroky před redelivery.
Odhad: **oprava YAML bloku + 6 řádků prózy**.

**P3-2 — Nahradit cron Symfony Schedulerem.**
Detekce zaseklých ság je učebnicový `#[AsCronTask]` [8]. Kniha o Symfony 8 by měla ukázat komponentu,
ne Kubernetes CronJob.
Odhad: **přepis jedné ukázky**.

**P3-3 — Přejmenovat `Order::place()` v semantic lock ukázce.**
Konflikt s kánonem knihy (G20). Dvě slova.
Odhad: **oprava jedné ukázky**.

## 8. Otevřené otázky pro autora

1. **Kterou terminologickou konvenci kniha přijme?** Současná (sága = zastřešující pojem) je obhajitelná,
   ale rozchází se s *CQRS Journey* [5]. Rozhodnutí patří i do glosáře a do kapitol 12 a 19.
2. **Jak daleko jít s externími orchestrátory?** Temporal v PHP je reálná volba, ale otevírá téma
   provozu, které kniha jinde neřeší. Odstavec, nebo plná sekce s ukázkou?
3. **Má kapitola držet jediný příklad e-shopu napříč všemi sekcemi?** Dnes ano a je to čitelné, ale
   vede k tomu, že se stejný `OrderProcessManager` ukazuje čtyřikrát v mírně odlišných variantách.
4. **Kolik prostoru dát souběžnosti?** Multi-worker, deadlocky a ACD bez I jsou nejcennější část
   kapitoly a zároveň nejnáročnější. Patří na úroveň obtížnosti 4, nebo do samostatné pokročilé sekce?
5. **Zůstane `CompensatableCommand`?** Buď ho `compensate()` má skutečně používat, nebo nemá být
   v knize vůbec – nepoužité rozhraní čtenáře mate.
6. **Kapitola má 1777 řádků při deklarovaných 40 minutách čtení** (`Chapters.php:38`). Po zkrácení kódu
   podle P2-2 a doplnění P1/P2 zůstane rozsah zhruba stejný. Je 40 minut cíl, nebo strop?

## 9. Bibliografie

### Ověřené zdroje

Není-li uvedeno jinak, všechny zdroje byly získány **přímým fetchem** uvedené URL dne 2026-09-03.
Rozpočet na fulltextové vyhledávání byl v této session vyčerpán; **žádný zdroj níže nepochází z hledání**.


[1] Garcia-Molina, H., Salem, K. — *Sagas*. ACM SIGMOD, 1987 (ACM 0-89791-236-5/87/0005/0249). https://www.cs.cornell.edu/andru/cs711/2002fa/reading/sagas.pdf — přímý fetch, PDF, text extrahován lokálně přes `pdftotext` (skenovaný originál, OCR obsahuje překlepy; citace kontrolovány proti kontextu)
[2] Richardson, C. — *Pattern: Saga*, microservices.io. https://microservices.io/patterns/data/saga.html — přímý fetch
[3] Hohpe, G., Woolf, B. — *Process Manager*, Enterprise Integration Patterns. https://www.enterpriseintegrationpatterns.com/patterns/messaging/ProcessManager.html — přímý fetch
[4] Hohpe, G., Woolf, B. — *Routing Slip*, Enterprise Integration Patterns. https://www.enterpriseintegrationpatterns.com/patterns/messaging/RoutingTable.html — přímý fetch
[5] Microsoft patterns & practices — *Reference 6: A Saga on Sagas*, CQRS Journey, 2012 (archiv 2014). https://learn.microsoft.com/en-us/previous-versions/msp-n-p/jj591569(v=pandp.10) — přímý fetch
[6] Symfony — *Messenger: Sync & Queued Message Handling*. https://symfony.com/doc/current/messenger.html — přímý fetch
[7] Symfony — *Workflow*. https://symfony.com/doc/current/workflow.html — přímý fetch
[8] Symfony — *Scheduler*. https://symfony.com/doc/current/scheduler.html — přímý fetch
[9] Symfony — `MessengerPass::guessHandledClasses()`, větev 7.3. https://raw.githubusercontent.com/symfony/symfony/7.3/src/Symfony/Component/Messenger/DependencyInjection/MessengerPass.php — přímý fetch
[10] Symfony — Doctrine transport `Connection`, větev 7.3. https://raw.githubusercontent.com/symfony/symfony/7.3/src/Symfony/Component/Messenger/Bridge/Doctrine/Transport/Connection.php — přímý fetch
[11] Particular Software — *Sagas*, dokumentace NServiceBus. https://docs.particular.net/nservicebus/sagas/ — přímý fetch
[12] Dahan, U. — *The Danger of Centralized Workflows*, 2011. https://udidahan.com/2011/07/13/the-danger-of-centralized-workflows/ — přímý fetch
[13] Dahan, U. — *Sagas and Unit Testing – Business Process Verification Made Easy*, 2008. https://udidahan.com/2008/02/04/sagas-and-unit-testing-business-process-verification-made-easy/ — přímý fetch
[14] Dahan, U. — *NServiceBus Saga Tips*, 2012. https://udidahan.com/2012/02/27/nservicebus-saga-tips/ — přímý fetch
[15] Fowler, M. — *Event Collaboration*. https://martinfowler.com/eaaDev/EventCollaboration.html — přímý fetch
[16] Treat, T. — *You Cannot Have Exactly-Once Delivery*, 2015. https://bravenewgeek.com/you-cannot-have-exactly-once-delivery/ — přímý fetch
[17] Temporal — *Understanding Temporal* (durable execution, event history, replay). https://docs.temporal.io/evaluate/understanding-temporal — přímý fetch
[18] Temporal — *Compensating Actions: Part of a Complete Breakfast with Sagas*. https://temporal.io/blog/compensating-actions-part-of-a-complete-breakfast-with-sagas — přímý fetch
[19] Camunda — *Orchestration vs. Choreography*, blog. https://camunda.com/blog/2023/02/orchestration-vs-choreography/ — přímý fetch
[20] Dahan, U. — seznam článků o ságách (výsledky vyhledávání na vlastním webu). https://udidahan.com/?s=sagas — přímý fetch
### Neověřené / nedohledané

- **Dahan, U. – *Sagas: not just for workflows* – VYŘEŠENO 2026-09-04. Článek toho jména
  neexistuje.** Druhý průchod s fulltextovým hledáním nenašel na `udidahan.com` ani jinde nic
  s tímto titulem. Titul koluje komunitou jako zkomolenina.

  **Skutečný text, který tuto myšlenku nese, je dohledaný a živý:** Udi Dahan – *No more workflow
  for nServiceBus – please welcome the Saga*, 17. 12. 2007,
  https://udidahan.com/2007/12/17/no-more-workflow-for-nservicebus-please-welcome-the-saga/.
  Dahan v něm termín „workflow“ pro nServiceBus výslovně opouští: „nServiceBus doesn’t really need
  workflow in the general sense of the term. An older term that’s been used in the DBMS community
  might make more sense – **‚long-lived transactions‘**.“ Sagu vymezuje proti workflow enginům
  („Bigger than a WCF/WF, smaller than a breadbox Biztalk“) a popisuje ji jako koordinaci
  distribuovaných služeb přes zprávy: „Each service runs its own ‚mini-workflow‘, and coordinates
  its actions with other services via messages.“

  **Poznámka k rozsahu nálezu:** kapitola 14 (`sagas.md`) ve své současné podobě žádný externí
  odkaz neobsahuje a Dahana nejmenuje, takže **není co opravovat**. Nález je relevantní pro přepis:
  je-li potřeba doložit, že saga není workflow engine, tohle je citovatelný primární zdroj.

- **Vernon, *IDDD* (2013), kap. 4 – POTVRZENO 2026-09-04 z plného textu knihy
  (vlastní výtisk).** Citace na `sagas.md:79` sedí. Kapitola 4 se jmenuje *Architecture* a mezi
  jejími podsekcemi je doslova **„Long-Running Processes, aka Sagas“**, zařazená pod
  Event-Driven Architecture vedle *Pipes and Filters* a *Event Sourcing*. Vernon sám shrnuje záběr
  kapitoly jako styly „Hexagonal (Ports and Adapters), Service-Oriented, REST, CQRS, Event-Driven
  (Pipes and Filters, Long-Running Processes or Sagas, Event Sourcing), and Data Fabric/Grid-Based“.

  **Navíc: téma je i v kapitole 13** *Integrating Bounded Contexts*, kde má podsekce
  „Long-Running Processes, and Avoiding Responsibility“, „Process State Machines and Time-out
  Trackers“ a „Designing a More Sophisticated Process“. Pro kapitolu o sagách je tahle druhá
  pasáž praktičtější než kap. 4, protože řeší stavové automaty a timeouty. **Doporučení: citovat
  obě, kap. 4 pro zařazení vzoru a kap. 13 pro implementační detaily.**
- **Richardson, C. – *Microservices Patterns*, Manning, 2018 – OVĚŘENO 2026-09-04 ze zakoupeného
  výtisku (1. vydání). Citace „kap. 4“ sedí, všechny pojmy v knize jsou. Jedna terminologická
  nepřesnost.**

  Kapitola 4 je skutečně o sagách – kniha ji sama anotuje jako *„Chapter 4 explains how to
  maintain data consistency across services by using the Saga pattern“*, dále *„introduces the
  saga concept“*, *„discusses how they can implement choreography-based sagas“* a *„discusses how
  to implement an RDBMS-based saga orchestrator“*. Odkazy na `sagas.md:989` a `:1400` jsou tedy
  správné.

  Všechna čtyři countermeasures jsou doložená: **semantic lock** (15 výskytů),
  **commutative updates** (2), **pessimistic view** (4), **reread value** (4); slovo
  „countermeasure“ se v knize objevuje 57×. Doložená je i trojice typů transakcí:
  **compensatable** (25) a **pivot transaction** (19).

  **Nepřesnost k opravě (`sagas.md:1396`):** kapitola píše „**Retryable** transactions“.
  Richardson používá tvar **„retriable“** – 18 výskytů, zatímco „retryable“ se v knize
  nevyskytuje ani jednou. Obojí je anglicky správně, ale při citaci autora se má držet jeho tvar.
- **Hohpe, G., Woolf, B. — *Enterprise Integration Patterns*, Addison-Wesley, 2003.** Ověřeny jsou
  webové verze vzorů [3][4]. Knižní znění se může lišit; pokud kniha cituje EIP doslovně, ověřit
  proti tisku.

- **Temporal PHP SDK – OVĚŘENO 2026-09-04 z Packagistu.** `temporal/sdk` je na **v2.18
  (17. 8. 2026)**, vyžaduje `php >=8.1`. SDK existuje, je aktivně vydávané a ve stabilní řadě 2.x,
  takže zmínka v kapitole je podložená. **Camunda 8 v PHP** se nedohledalo ani podruhé: oficiální
  PHP klient pro Zeebe/Camunda 8 neexistuje, dostupné jsou jen komunitní gRPC obálky bez vydané
  stabilní verze. **Doporučení: Temporal zmiňovat s verzí, Camundu 8 pro PHP neuvádět jako
  reálnou volbu, nebo výslovně napsat, že oficiální PHP klient chybí.**
  Pokud se P2-1 přijme, ověřit před psaním (`temporal.io/sdk` / `github.com/temporalio/sdk-php`).
