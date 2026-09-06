---
route: sagas
path: /sagy-a-process-managery
title: Ságy a Process Managery
page_title: "Ságy a Process Managery | DDD Symfony"
meta_description: "Ságy a Process Managery v DDD a Symfony Messenger: kompenzace, choreografie vs. orchestrace, timeouty, paralelní kroky a idempotence dlouhotrvajících procesů."
meta_keywords: "saga, process manager, kompenzační transakce, choreografie, orchestrace, CQRS, DDD, Symfony 8, Messenger, distribuované transakce"
og_type: article
published: "2026-03-26"
modified: 2026-09-06
breadcrumb_name: Ságy a Process Managery
schema_type: TechArticle
schema_headline: "Ságy a Process Managery"
chapter_number: "14"
category: Vzory
deck: 'Ságy a Process Managery v DDD a Symfony 8 – implementace kompenzačních transakcí, choreografie i orchestrace dlouhotrvajících procesů pomocí Symfony Messenger. Včetně timeoutů, paralelních kroků a monitorování distribuovaných procesů.'
reading_time: 43
difficulty: 4
github_examples: Chapter07_Sagas
---

V předchozí kapitole jsme se zabývali
[Event Sourcingem](/event-sourcing), vzorem persistence,
který ukládá stav jako sekvenci neměnných událostí. Ságy na tento koncept přirozeně
navazují. Event Sourcing řeší persistenci uvnitř jednoho agregátu; ságy koordinují
procesy **napříč více agregáty a Bounded Contexts**, které spolu komunikují
doménovými událostmi.

## 14.01 Proč potřebujeme ságy? {#proc-sagy}

Jako ilustrativní příklad slouží typický e-shop: zákazník odešle objednávku a systém musí provést čtyři kroky
napříč odlišnými [Bounded Contexts](/zakladni-koncepty#bounded-contexts):

1. **Ordering** – vytvoření objednávky (agregát `Order`),
2. **Payment** – stržení platby zákazníkovi (agregát `Payment`),
3. **Warehouse** – rezervace zboží na skladě (agregát `StockReservation`),
4. **Shipping** – vytvoření zásilky (agregát `Shipment`).

Každý z těchto kontextů má vlastní agregát, vlastní databázi (nebo alespoň vlastní tabulky
se striktně oddělenou odpovědností) a vlastní invarianty, které musí chránit. Agregáty
v různých Bounded Contexts nelze měnit v jedné databázové transakci. Porušilo by to
autonomii kontextů, jež je základním pilířem DDD. Jeden kontext nesmí sahat do databáze
jiného kontextu; komunikace probíhá výhradně zprávami (událostmi a příkazy).

Proč nelze zabalit všechny čtyři kroky do jediné databázové transakce?
Jednotlivé kontexty mohou běžet na různých serverech a používat různé databázové systémy
(PostgreSQL pro objednávky, Redis pro skladové rezervace, externí platební bránu pro platby).
Komunikují asynchronně přes frontu zpráv. Koncept atomické transakce se zde rozpadá.

:::callout{type="note"}
### Proč ne Two-Phase Commit (2PC)? {#2pc-heading}

Protokol **Two-Phase Commit** (2PC) koordinuje commit napříč více databázemi,
ale za cenu zámků držených po obě fáze a koordinátora jako single point of failure.
Pro autonomní Bounded Contexts se nehodí. Všichni účastníci musí být dostupní
současně a sdílet transakční protokol. Podrobný rozbor obsahuje kapitola
[Outbox Pattern](/outbox-pattern#2pc-heading).
:::

Příklad selhání: systém úspěšně strhne platbu zákazníkovi
(krok 2), ale při rezervaci skladu zjistí, že zboží není dostupné (krok 3 selže).
Zákazník přišel o peníze, zboží nemá a systém je v **nekonzistentním stavu**.
Bez mechanismu, který by tento stav detekoval a napravil, se situace sama
nevyřeší. V produkčním systému je to nepřijatelné.

Základní tvar řešení popsali už v roce 1987 Hector Garcia-Molina a Kenneth Salem
v článku *Sagas*. Ságou tam nazývají dlouho běžící transakci rozloženou na sérii
dílčích transakcí, z nichž každá má definovanou **kompenzační akci**. Selže-li některý krok,
systém provede kompenzace všech předchozích úspěšných kroků – v opačném pořadí.

### Co sága znamenala v roce 1987 {#saga-1987}

Původní motivace byla jiná než ta dnešní. Autoři neřešili distribuci, ale zámky:
dlouhá transakce drží zdroje a krátké transakce za ní stojí frontu. Prostředím je
jediná centralizovaná databáze a dílčí transakce jsou obyčejné ACID transakce nad
týmž systémem. Koordinaci obstarává komponenta Saga Execution Component, která čte
transakční log a po pádu dohledá poslední nezkompenzovanou transakci. Jde tedy
o orchestrátor. Choreografie, kde tok vzniká emergentně z reakcí účastníků, v článku
vůbec není.

Jedno pozorování z článku přežilo beze změny: ostatní transakce mohou vidět efekty
částečně provedené ságy. Když běží kompenzace, nikdo neinformuje ani neruší transakce,
které mezitím stihly přečíst výsledek kompenzovaného kroku. Odtud pochází dnešní
formulace, že sága nemá izolaci (viz [Izolace ság](#izolace-sag)).

Dnešní distribuovaná sága tedy nese jméno staršího vzoru, ale řeší jiný problém:
autonomii služeb s oddělenými databázemi. Druhý kořen leží v Enterprise Integration
Patterns, kde *Process Manager* drží stav posloupnosti a podle mezivýsledků rozhoduje
o dalším kroku. Třetí přidal Udi Dahan, který v roce 2007 pro NServiceBus opustil
pojem *workflow* ve prospěch ságy jako stavového obsluhovače zpráv s korelací
a timeouty. Kompenzace v jeho pojetí skoro nefigurují; ty jsou Richardsonova linie.

### Kterou konvenci kniha používá {#terminologicka-konvence}

Výsledkem jsou tři neslučitelné definice, které dnes koexistují. Tým Microsoft
patterns & practices termín „sága“ v průvodci *CQRS Journey* záměrně opustil a mluví
jen o Process Manageru. Odkazuje přitom na starší a odlišný význam toho slova. Navrhuje přitom dělicí
čáru, kterou praxe nepřevzala: process manager routuje zprávy uvnitř jednoho Bounded
Contextu, sága řídí proces přes hranice kontextů. Richardson naopak ságou nazývá
obojí a orchestraci bere jako implementační detail. Třetí čára vede podle způsobu
koordinace: sága jede na událostech a kompenzacích, process manager překládá události
na příkazy.

Kniha vychází z třetího dělení, protože je v praxi nejrozšířenější, a bere ho volněji.
„Sága“ je zastřešující termín pro koordinátor dlouhotrvajícího procesu s perzistentním
stavem. „Process Manager“ označuje jeho orchestrovanou podobu ze
[sekce 14.05](#orchestrace). U cizího zdroje se vyplatí ověřit, kterou z konvencí používá.

*Citace: Garcia-Molina, H. & Salem, K., **Sagas**, ACM SIGMOD (1987);
Hohpe, G. & Woolf, B., **Enterprise Integration Patterns** (2003);
Dahan, U., **No more workflow for nServiceBus – please welcome the Saga** (2007);
Microsoft patterns & practices, **CQRS Journey** (2012), Reference 6: A Saga on Sagas;
Vernon, V., **Implementing Domain-Driven Design** (2013), kap. 4 a 13.*

V následujících sekcích si projdeme dva základní přístupy ke koordinaci ság:
[choreografii](#choreografie) a [orchestraci](#orchestrace).
Praktickou implementaci ukážeme v Symfony 8 nad
[Symfony Messenger](/cqrs).

## 14.02 Kompenzační transakce {#kompenzacni-transakce}

Kompenzační transakce je **sémantické vrácení efektu předchozího kroku**.
Na rozdíl od technického rollbacku databázové transakce (který „vymaže“ změny, jako by
se nikdy nestaly) je kompenzace plnohodnotná doménová operace. Má vlastní vedlejší
efekty: notifikace, auditní záznamy, aktualizace stavů. Systém se nevrací do
původního stavu bit po bitu, ale do takového, který z doménového pohledu
odpovídá stavu před kompenzovaným krokem.

Pro náš e-shop scénář vypadá mapa akcí a jejich kompenzací následovně:

| Akce | Kompenzace | Poznámka |
|---|---|---|
| `ChargeCustomer` | `RefundCustomer` | Zahrnuje notifikaci zákazníka |
| `ReserveStock` | `ReleaseStock` | Uvolnění rezervace, nikoliv smazání |
| `CreateShipment` | `CancelShipment` | Pouze do okamžiku odeslání |

Kompenzace **není přesný inverzní příkaz**. Zatímco
`ChargeCustomer` strhne peníze, kompenzační `RefundCustomer` peníze
vrátí a k tomu odešle zákazníkovi notifikaci, zapíše záznam
do auditního logu a může aktualizovat interní metriky. Každá kompenzace je samostatný
příkaz s vlastní logikou, validací a vedlejšími efekty.

:::callout{type="pattern"}
### PHP: Rozhraní CompensatableCommand {#compensatable-command-heading}

:::code{language="php" filename="src/SharedKernel/Application/Command/CompensatableCommand.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Application\Command;

/**
 * Command, který lze kompenzovat - definuje svůj "undo" příkaz.
 */
interface CompensatableCommand
{
    /**
     * Vrátí příkaz, který sémanticky vrátí efekt tohoto příkazu.
     */
    public function compensation(): object;
}
:::
:::

:::callout{type="pattern"}
### PHP: ChargeCustomer s kompenzací {#charge-customer-heading}

:::code{language="php" filename="src/Payment/Application/Command/ChargeCustomer.php"}
<?php

declare(strict_types=1);

namespace App\Payment\Application\Command;

use App\SharedKernel\Application\Command\CompensatableCommand;

final readonly class ChargeCustomer implements CompensatableCommand
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $amountCents,
    ) {}

    public function compensation(): RefundCustomer
    {
        // Tady je vidět mez vzoru: příkaz identifikátor transakce nezná,
        // protože ho teprve vytvoří. Sebekompenzující příkaz proto vystačí
        // jen tam, kde kompenzace nepotřebuje výsledek původního kroku.
        // Objednávkový proces v téhle knize ji z toho důvodu řídí
        // z Process Manageru, který si transactionId uloží do kontextu.
        return new RefundCustomer(
            orderId: $this->orderId,
            customerId: $this->customerId,
            transactionId: '',
            amountCents: $this->amountCents,
            reason: 'Saga compensation',
        );
    }
}
:::

Kompenzační příkaz je obyčejné DTO. Bez něj se `ChargeCustomer` ani nenačte. Návratový
typ `compensation()` je kovariantní zúžení `object` z rozhraní, takže PHP potřebuje třídu znát:

:::code{language="php" filename="src/Payment/Application/Command/RefundCustomer.php"}
<?php

declare(strict_types=1);

namespace App\Payment\Application\Command;

final readonly class RefundCustomer
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        // Bez identifikátoru transakce nemá brána co vrátit. Sága si ho
        // proto z PaymentSucceeded ukládá do kontextu – je to jediné
        // místo, kde ho v tu chvíli má kdo znát.
        public string $transactionId,
        public int $amountCents,
        public string $reason,
    ) {}
}
:::
:::

:::callout{type="warn"}
### Kompenzace musí být idempotentní {#idempotence-warning-heading}

V distribuovaném systému se může stát, že kompenzační příkaz bude doručen více než
jednou, například kvůli retry mechanismu Symfony Messenger, výpadku workeru nebo
duplikaci zprávy ve frontě. Proto musí být každá kompenzace **idempotentní**:
opakované provedení téhož kompenzačního příkazu nesmí mít žádný další efekt.
Kompenzace toho dosáhne tak, že si nejdřív ověří aktuální stav
(např. `RefundCustomer` zkontroluje, zda platba již nebyla vrácena).
:::

### Kdy proces ságou být nemůže {#kdy-saga-nestaci}

Ne každý vícekrokový proces snese rozdělení na kompenzovatelné kroky. Garcia-Molina
se Salemem uvádějí protipříklad, který funguje dodnes jako test: převod peněz mezi
dvěma účty. První krok částku odepíše, druhý ji připíše. V mezidobí není nikde.
Takový mezistav není neúplný, ale pro doménu nečitelný: účetnictví v něm
nesedí a nikdo si ho nesmí přečíst, ani na vteřinu.

Rozdíl mezi „neúplným“ a „nečitelným“ mezistavem je použitelné návrhové kritérium.
Objednávka se strženou platbou a nerezervovaným zbožím je neúplná: doména pro ten
stav má jméno a operátor s ním umí pracovat. Peníze, které nejsou na žádném účtu,
jméno nemají. Nedá-li se pro mezistav ságy napsat srozumitelný stav ve
[všudypřítomném jazyce](/zakladni-koncepty#ubiquitous-language), vzor nesedí
a kroky patří do jedné transakce nad jedním agregátem.

:::callout{type="warn"}
### Akce, které vrátit nelze {#nevratne-akce-heading}

Odeslaný e-mail, data předaná třetí straně, vytištěný a odeslaný doklad, převod na
cizí účet. Kompenzace zde neruší původní akci, jen k ní přidává druhou: omluvný
e-mail, opravný doklad, žádost o storno u protistrany. Článek z roku 1987 to ukazuje
na dopisu, který se kompenzuje druhým dopisem, a na šeku, který se kompenzuje
příkazem k zastavení platby. Kompenzaci přitom bere jako poslední možnost, ne jako
výchozí návrhový styl. Sahá se po ní tehdy, když je cena jedné dlouhé transakce
příliš vysoká.

Praktický důsledek: nevratné kroky patří v sáze co nejpozději, ideálně až za pivot
transakci (viz [Když selže kompenzace](#selhani-kompenzace)). Krok, který nelze vzít
zpět, se pak nikdy nekompenzuje, protože za pivotem už sága jen běží dopředu.
:::

## 14.03 Choreografie {#choreografie}

Při choreografii **neexistuje centrální koordinátor**. Každý Bounded Context
reaguje na události publikované jinými kontexty a podle nich provádí
svůj krok procesu. Žádná služba neví o celém
toku. Každá zná pouze svou část a ví, na které události má reagovat.

:::diagram{fig="14.3-A" title="Choreografie vs. orchestrace – kdo koordinuje ságu" src="images/diagrams/8_sagas/choreography_vs_orchestration.svg"}
:::

V našem e-shop scénáři probíhá choreografická sága následovně: kontext Ordering
publikuje událost `OrderPlaced`. Kontext Payment na ni reaguje, strhne
platbu a publikuje `PaymentSucceeded`. Kontext Warehouse naslouchá
události `PaymentSucceeded`, rezervuje zboží a publikuje
`StockReserved`. Kontext Shipping reaguje na `StockReserved`
a vytvoří zásilku, čímž publikuje `ShipmentCreated`. Celý tok vzniká
emergentně z reakcí jednotlivých kontextů na události ostatních – bez centrálního
řízení.

:::callout{type="pattern"}
### PHP: Choreografie – tři nezávislé handlery {#choreografie-handlers-heading}

**Handler 1 – InitiatePaymentOnOrderPlaced:**

:::code{language="php" filename="src/Payment/Application/Handler/InitiatePaymentOnOrderPlaced.php"}
<?php

declare(strict_types=1);

namespace App\Payment\Application\Handler;

use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Payment\Application\Command\ChargeCustomer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class InitiatePaymentOnOrderPlaced
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(OrderPlacedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new ChargeCustomer(
            orderId: $event->orderId,
            customerId: $event->customerId,
            amountCents: $event->totalAmountCents,
        ));
    }
}
:::

**Handler 2 – ReserveStockOnPaymentSucceeded:**

:::code{language="php" filename="src/Warehouse/Application/Handler/ReserveStockOnPaymentSucceeded.php"}
<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Handler;

use App\Payment\Domain\Event\PaymentSucceeded;
use App\Warehouse\Application\Command\ReserveStock;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class ReserveStockOnPaymentSucceeded
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(PaymentSucceeded $event): void
    {
        $this->commandBus->dispatch(new ReserveStock(
            orderId: $event->orderId,
        ));
    }
}
:::

**Handler 3 – CreateShipmentOnStockReserved:**

:::code{language="php" filename="src/Shipping/Application/Handler/CreateShipmentOnStockReserved.php"}
<?php

declare(strict_types=1);

namespace App\Shipping\Application\Handler;

use App\Warehouse\Domain\Event\StockReserved;
use App\Shipping\Application\Command\CreateShipment;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class CreateShipmentOnStockReserved
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(StockReserved $event): void
    {
        $this->commandBus->dispatch(new CreateShipment(
            orderId: $event->orderId,
        ));
    }
}
:::
:::

:::callout{type="pattern"}
### YAML: Konfigurace Messenger pro choreografii {#choreografie-messenger-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez – plná konfigurace v kapitole o CQRS)"}
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async_events:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            'App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent': async_events
            'App\Payment\Domain\Event\PaymentSucceeded': async_events
            'App\Warehouse\Domain\Event\StockReserved': async_events
:::
:::

Hlavní výhodou choreografie je **volné provázání** (loose coupling) mezi
kontexty. Každý reaguje pouze na události, které mu přicházejí, a o ostatních nic
neví. Přidání nového kontextu (například Loyalty, který přidělí body za objednávku)
nevyžaduje zásah do existujícího kódu: stačí nový handler naslouchající
`OrderPlaced`. Dokud je posloupnost kroků známá dopředu a běží lineárně, choreografie
stačí.

## 14.04 Limity choreografie {#limity-choreografie}

U procesů s pěti a více kontexty nebo s podmíněným větvením narazí choreografie na
čtyři problémy, které se v menším měřítku skrývají. V produkci se projeví
ve chvíli, kdy do toku přibude pátý kontext nebo větvení podle stavu.

### 1. Neviditelný tok procesu {#neviditelny-tok-heading}

Při choreografii neexistuje žádné jedno místo, kde by byl celý doménový proces popsán.
Tok procesu je rozdrobený do desítek handlerů v různých kontextech. Při takovém
rozsahu už kompletní tok nejde vizualizovat. Nikdo nemá přehled o tom, které
kroky po sobě následují, kde se proces větví a jaké jsou alternativní cesty při
selhání. Vzniká fenomén, který se někdy označuje jako
**„distribuované špagety“** (*distributed spaghetti*), tedy analogie
ke špagetovému kódu rozloženému do celého systému.

### 2. Porušení Open-Closed Principle {#ocp-heading}

Přidání nového kroku do procesu často vyžaduje úpravu existujícího kontextu. Například
pokud chceme mezi platbu a sklad vložit krok „ověření proti podvodům“ (Fraud Detection),
musíme změnit handler ve Warehouse. Místo události `PaymentSucceeded`
musí naslouchat na `FraudCheckPassed`. Tím porušujeme
**Open-Closed Principle**. Stávající kód kontextu Warehouse se musí
upravit, aby fungoval s novým krokem. Při orchestraci by stačilo přidat krok do
centrálního Process Manageru bez zásahu do existujících kontextů.

### 3. Obtížná diagnostika selhání {#diagnostika-heading}

Když se proces „zasekne“, kde hledat příčinu? Každý kontext zná pouze svůj krok.
Celkový stav procesu nezná. Operátor musí ručně procházet logy všech
kontextů, korelovat události podle `orderId` a rekonstruovat, kde přesně
proces selhal. Neexistuje centrální dashboard, který by zobrazil:
„Objednávka #42 – platba OK, sklad SELHÁNÍ, zásilka NESPUŠTĚNA.“
V produkčním prostředí s tisíci objednávkami denně je tento přístup neúnosný.

### 4. Chybějící timeout management {#timeout-heading}

Kdo detekuje, že proces „visí“? Pokud kontext Payment strhne platbu, ale Warehouse
nikdy nezareaguje (handler spadl, zpráva se ztratila ve frontě), kdo zjistí, že
proces stojí? Žádný z kontextů nemá přehled o časových limitech celého procesu. V choreografii neexistuje přirozené místo pro globální
timeout. Nikdo nehlídá, že celý proces od `OrderPlaced` po
`ShipmentCreated` musí trvat maximálně 30 minut.

Všechny tyto problémy poukazují na jednu věc: u komplexních procesů potřebujeme
**centrální místo**, které zná celý tok, řídí kroky, detekuje selhání
a spouští kompenzace. Tuto roli plní [orchestrátor
– Process Manager](#orchestrace).

:::callout{type="note"}
### Choreografie má své místo {#choreografie-stale-validni-heading}

Nerozhoduje počet kroků, ale tvar procesu. Hohpe s Woolfem sahají po Process
Manageru tehdy, když posloupnost kroků není známá v době návrhu nebo když kroky
neběží za sebou. Proces „vytvoření objednávky → stržení platby → potvrzení“ ani
jednu z těch podmínek nesplňuje, takže choreografie ušetří kód oproti plnohodnotnému
orchestrátoru. Hranice „dvou až tří kroků“, se kterou pracuje řada článků, je
autorská heuristika – použitelná zkratka, ne pravidlo.
:::

## 14.05 Orchestrace – Process Manager {#orchestrace}

V orchestraci celý doménový proces řídí jediná třída, tzv. **Process Manager**.
Funguje jako stavový automat s definovanými stavy a přechody. V našem e-shop scénáři
tuto roli plní `OrderProcessManager`. Přijímá události ze všech kontextů (Payment,
Warehouse, Shipping) a podle nich rozhoduje, jaký příkaz vydat jako další krok.
Tok není rozdrobený do desítek handlerů. Celá logika procesu se soustředí do jedné
třídy. Na jednom místě je viditelný kompletní tok od `OrderPlaced` po `ShipOrder`.

Následující diagram zobrazuje stavový automat procesu objednávky. Zelené šipky značí úspěšné
přechody, červené selhání a oranžová cesta vede přes kompenzaci:

:::diagram{fig="14.5-A" title="Stavový automat OrderProcessManager" src="images/diagrams/8_sagas/saga_state_machine.svg"}
:::

:::callout{type="pattern"}
### PHP: Enum OrderSagaStatus {#saga-status-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/OrderSagaStatus.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

enum OrderSagaStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case AwaitingStockReservation = 'awaiting_stock_reservation';
    case AwaitingShipment = 'awaiting_shipment';
    case Completed = 'completed';
    case Compensating = 'compensating';
    case Failed = 'failed';

    /**
     * Z terminálního stavu už sága nikam nepokračuje. Opožděná událost
     * ji nesmí vzkřísit – proto se na tuhle otázku ptá každý handler
     * hned na začátku.
     */
    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }
}
:::
:::

:::callout{type="pattern"}
### PHP: OrderProcessManager – jádro orchestrace {#process-manager-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/OrderProcessManager.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Payment\Domain\Event\PaymentSucceeded;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\RefundFailed;
use App\Payment\Domain\Event\RefundSucceeded;
use App\Warehouse\Domain\Event\StockReserved;
use App\Warehouse\Domain\Event\StockReservationFailed;
use App\Shipping\Domain\Event\ShipmentCreated;
use App\Payment\Application\Command\ChargeCustomer;
use App\Payment\Application\Command\RefundCustomer;
use App\Warehouse\Application\Command\ReserveStock;
use App\Warehouse\Application\Command\ReleaseStock;
use App\Shipping\Application\Command\CreateShipment;
use App\Shipping\Application\Command\CancelShipment;
use App\Ordering\Application\Command\MarkOrderPaid;
use App\Ordering\Application\Command\ShipOrder;
use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Application\Command\ReleaseOrderLock;
use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\SharedKernel\Domain\SystemActor;
use App\Ordering\Domain\ValueObject\OrderId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Process Manager koordinující objednávkový proces napříč kontexty:
 * Ordering → Payment → Warehouse → Shipping → Ordering (potvrzení)
 */
#[AsMessageHandler(bus: 'event.bus')]
final class OrderProcessManager
{

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly OrderSagaRepository $sagaRepository,
        private readonly ManagerRegistry $managerRegistry,
    ) {}

    public function __invoke(
        OrderPlacedIntegrationEvent|PaymentSucceeded|PaymentFailed|StockReserved
        |StockReservationFailed|ShipmentCreated|RefundSucceeded|RefundFailed
        |OrderCancelled $event,
    ): void {
        match (true) {
            $event instanceof OrderPlacedIntegrationEvent => $this->onOrderPlaced($event),
            $event instanceof PaymentSucceeded => $this->onPaymentSucceeded($event),
            $event instanceof PaymentFailed => $this->onPaymentFailed($event),
            $event instanceof StockReserved => $this->onStockReserved($event),
            $event instanceof StockReservationFailed => $this->onStockReservationFailed($event),
            $event instanceof ShipmentCreated => $this->onShipmentCreated($event),
            // Bez těchhle dvou větví uvázne sága navždy ve stavu Compensating:
            // event.bus má allow_no_handlers, takže se událost tiše ackne
            // a v logu po ní nezůstane ani řádek.
            $event instanceof RefundSucceeded => $this->onRefundSucceeded($event),
            $event instanceof RefundFailed => $this->onRefundFailed($event),
            // Objednávku může zrušit i člověk, ne jen kompenzace. Bez téhle
            // větve sága poběží dál, strhne platbu a vytvoří zásilku
            // k objednávce, která už neexistuje – a příkazy pak jeden po
            // druhém umřou v DLQ, aniž by kdokoli spadl.
            $event instanceof OrderCancelled => $this->onOrderCancelled($event),
        };
    }

    private function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        $state = OrderSaga::start(
            sagaType: 'order_process',
            correlationId: $event->orderId,
            status: OrderSagaStatus::AwaitingPayment,
            context: [
                'customerId' => $event->customerId,
                'amountCents' => $event->totalAmountCents,
                'completedSteps' => [],
            ],
        );

        try {
            $this->sagaRepository->save($state);
        } catch (UniqueConstraintViolationException) {
            // Souběžné doručení téže události. Unikátní index
            // (saga_type, correlation_id) druhý zápis odmítl – sága už
            // běží a druhý command by strhl peníze podruhé.
            //
            // Doctrine po neúspěšném flushi EntityManager zavře, takže
            // samotné spolknutí výjimky nestačí: bez resetu spadne další
            // handler téže zprávy na „The EntityManager is closed“.
            // Detail v kapitole o Outboxu.
            $this->managerRegistry->resetManager();

            return;
        }

        $this->commandBus->dispatch(new ChargeCustomer(
            orderId: $event->orderId,
            customerId: $event->customerId,
            amountCents: $event->totalAmountCents,
        ));
    }

    private function onPaymentSucceeded(PaymentSucceeded $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        // Opožděná událost nesmí vzkřísit ukončenou ságu. Bez téhle
        // podmínky by PaymentSucceeded doručený po timeoutu poslal
        // MarkOrderPaid na už zrušenou objednávku. Chybějící sága
        // znamená, že událost patří objednávce mimo tenhle proces.
        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        $state->transitionTo(OrderSagaStatus::AwaitingStockReservation);
        $state->updateContext('transactionId', $event->transactionId);
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'payment_charged',
        ]);
        $this->sagaRepository->save($state);

        // Stav agregátu mění příkaz, ne sága. Bez MarkOrderPaid by objednávka
        // zůstala v Draft a Order::ship() by nešlo nikdy zavolat.
        $this->commandBus->dispatch(new MarkOrderPaid(orderId: $event->orderId));
        $this->commandBus->dispatch(new ReserveStock(orderId: $event->orderId));
    }

    private function onPaymentFailed(PaymentFailed $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        // Bez ságy není co řídit – událost patří objednávce, kterou tenhle
        // proces nezaložil.
        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        $this->finish($state, OrderSagaStatus::Failed);

        $this->commandBus->dispatch(new CancelOrderCommand(
            orderId: OrderId::fromString($event->orderId),
            reason: 'Platba selhala: ' . $event->failureReason,
            // Sága není člověk. Podle pravidla z kapitoly o autorizaci
            // dostává explicitní systémovou identitu, ne chybějícího aktéra.
            actorId: CustomerId::fromString(SystemActor::ID),
        ));
    }

    private function onStockReserved(StockReserved $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        // Bez ságy není co řídit – událost patří objednávce, kterou tenhle
        // proces nezaložil.
        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        // Totéž co u zásilky: rezervace dorazila až po zahájení kompenzace,
        // takže se rovnou uvolní místo toho, aby sága pokračovala dál.
        if ($state->status() === OrderSagaStatus::Compensating) {
            $this->commandBus->dispatch(new ReleaseStock(orderId: $event->orderId));

            return;
        }

        $state->transitionTo(OrderSagaStatus::AwaitingShipment);
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'stock_reserved',
        ]);
        $this->sagaRepository->save($state);

        $this->commandBus->dispatch(new CreateShipment(orderId: $event->orderId));
    }

    private function onStockReservationFailed(StockReservationFailed $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        // Bez ságy není co řídit – událost patří objednávce, kterou tenhle
        // proces nezaložil.
        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaRepository->save($state);

        // Kompenzace: vrátit platbu. RefundCustomer je asynchronní příkaz -
        // sága zůstává ve stavu Compensating a do Failed přejde až po
        // potvrzení RefundSucceeded (viz sekci 9, Když selže kompenzace).
        $this->commandBus->dispatch(new RefundCustomer(
            orderId: $event->orderId,
            customerId: $state->context()['customerId'],
            transactionId: $state->context()['transactionId'],
            amountCents: $state->context()['amountCents'],
            reason: 'Zboží není skladem',
        ));
    }

    private function onOrderCancelled(OrderCancelled $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId->value);

        // Vlastní kompenzace ságu takhle nevzkřísí: ta už je v Compensating
        // nebo terminálním stavu.
        if ($state === null || $state->status()->isTerminal()
            || $state->status() === OrderSagaStatus::Compensating) {
            return;
        }

        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaRepository->save($state);

        // Vrací se jen to, co už proběhlo. Seznam hotových kroků je přesně
        // ten důvod, proč si sága vede stav.
        foreach (array_reverse($state->context()['completedSteps']) as $step) {
            match ($step) {
                'shipment_created' => $this->commandBus->dispatch(new CancelShipment(
                    orderId: $event->orderId->value,
                    shipmentId: $state->context()['shipmentId'],
                )),
                'stock_reserved' => $this->commandBus->dispatch(
                    new ReleaseStock(orderId: $event->orderId->value),
                ),
                'payment_charged' => $this->commandBus->dispatch(new RefundCustomer(
                    orderId: $event->orderId->value,
                    customerId: $state->context()['customerId'],
                    transactionId: $state->context()['transactionId'],
                    amountCents: $state->context()['amountCents'],
                    reason: 'Objednávku zrušil zákazník',
                )),
                default => null,
            };
        }
    }

    /** Zámek na objednávce uvolňuje sága, ať skončí jakkoli. */
    private function finish(OrderSaga $state, OrderSagaStatus $status): void
    {
        $state->transitionTo($status);
        $this->sagaRepository->save($state);

        // Bez tohohle kroku zůstane objednávka zamčená navždy a zákazník
        // ji nezruší ani po doručení. Ve větvích, které končí stornem,
        // zámek uvolní rovnou CancelOrderHandler – přichází pod systémovou
        // identitou, takže na pořadí zpráv ve frontě nezáleží.
        $this->commandBus->dispatch(
            new ReleaseOrderLock(orderId: $state->correlationId()),
        );
    }

    private function onShipmentCreated(ShipmentCreated $event): void
    {
        $state = $this->sagaRepository->findByCorrelationId($event->orderId);

        // Bez ságy není co řídit – událost patří objednávce, kterou tenhle
        // proces nezaložil.
        if ($state === null || $state->status()->isTerminal()) {
            return;
        }

        // Zásilka mohla vzniknout dřív, než dorazilo storno. Guard na
        // terminální stav tenhle případ nechytí – Compensating terminální
        // není – a bez téhle větve by sága kompenzaci přeskočila a přešla
        // do Completed s objednávkou, kterou už někdo zrušil.
        if ($state->status() === OrderSagaStatus::Compensating) {
            $this->commandBus->dispatch(new CancelShipment(
                orderId: $event->orderId,
                shipmentId: $event->shipmentId,
            ));

            return;
        }

        $state->updateContext('shipmentId', $event->shipmentId);
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'shipment_created',
        ]);
        $this->finish($state, OrderSagaStatus::Completed);

        // Zásilka existuje, takže objednávka přechází do Shipped.
        // Potvrzení proběhlo už v továrně při vzniku objednávky.
        $this->commandBus->dispatch(new ShipOrder(
            orderId: $event->orderId,
            shipmentId: $event->shipmentId,
        ));
    }
}
:::
:::

Objednávka projde třemi stavy: do `Confirmed` ji dostane už továrna
(`placeWithItems()` dostává kompletní objednávku, takže `Draft` opouští hned),
odtud `MarkOrderPaid` do `Paid` a `ShipOrder` do `Shipped`. Sága sama stav agregátu
nemění. Jen posílá příkazy a čeká na události.

Právě tady se pozná, jestli je proces domyšlený: chybí-li jediný příkaz, sága doběhne
do `Completed` a objednávka zůstane rozpracovaná. Nikde nespadne, jen se stavy rozejdou.
Test procesu proto nesmí končit u stavu ságy. Kontrolovat musí i stav agregátu.

Orchestrace přináší oproti choreografii několik výhod. Celý doménový proces
popisuje **jediné místo**, takže vývojář okamžitě vidí kompletní tok
od objednávky po potvrzení. Při debugování stačí zkontrolovat stav
ságy v databázi a hned je jasné, ve kterém kroku proces stojí. Rozšíření o nový
krok (například Fraud Detection mezi platbu a sklad) znamená nový stav v enumu,
novou metodu pro `FraudCheckPassed` a úpravu `onPaymentSucceeded`, která místo
rezervace skladu nově vydá příkaz pro kontrolu podvodů. Celá změna zůstává
v jediné třídě. Kontexty Warehouse ani Payment se neupravují.

:::callout{type="note"}
### Každá metoda = jeden krok stavového automatu {#step-method-heading}

Každá privátní metoda v `OrderProcessManager` reprezentuje jeden krok
stavového automatu. Vložení kroku doprostřed procesu znamená přidat metodu pro
novou událost a upravit metodu předchozího kroku, která nyní vydává jiný příkaz.
Úprava ale zůstává lokální, uvnitř jediné třídy. Bounded Contexts kolem ságy
se nemění; v tom spočívá rozdíl oproti choreografii, kde stejné rozšíření
vyžaduje zásah do cizího kontextu.
:::

### Události kroků {#step-events-heading}

Každý krok ohlásí výsledek událostí. Jsou to neměnné záznamy s primitivy. Cestují
mezi kontexty, takže hodnotové objekty by se přes serializaci nepřenesly:

:::code{language="php" filename="src/Payment/Domain/Event/ + Warehouse/ + Shipping/ (obdobně)"}
<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use Symfony\Component\Uid\Uuid;

// Každá kroková událost nese vlastní identitu. Bez ní nejde zapnout
// idempotenci ságy ze sekce 14.06 – guard se nemá čeho chytit
// a opakované doručení provede přechod podruhé.
final readonly class PaymentSucceeded
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $transactionId = '',
    ) {}
}

final readonly class PaymentFailed
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $failureReason,
    ) {}
}

final readonly class RefundSucceeded
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $refundId = '',
    ) {}
}

final readonly class RefundFailed
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $failureReason,
    ) {}
}

// --- src/Warehouse/Domain/Event/ ---
namespace App\Warehouse\Domain\Event;

use Symfony\Component\Uid\Uuid;

final readonly class StockReserved
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $reservationId = '',
    ) {}
}

final readonly class StockReservationFailed
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $failureReason,
    ) {}
}

// --- src/Shipping/Domain/Event/ ---
namespace App\Shipping\Domain\Event;

use Symfony\Component\Uid\Uuid;

final readonly class ShipmentCreated
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $shipmentId = '',
    ) {}
}
:::

Všechny nesou `orderId`, korelační klíč, podle kterého si Process Manager
najde svou ságu. Bez něj by událost nešlo přiřadit k běžícímu procesu.

### Handlery kroků žijí v cizích kontextech {#step-handlers-heading}

Process Manager příkazy jen rozesílá. Vykonává je vždy handler v tom kontextu, kterému
krok patří – a ten o existenci ságy nic neví. Ukazujeme jeden; `ReserveStockHandler`
i `CreateShipmentHandler` mají stejný tvar, jen jiný agregát a jinou výslednou událost:

:::code{language="php" filename="src/Payment/Application/Handler/ChargeCustomerHandler.php"}
<?php

declare(strict_types=1);

namespace App\Payment\Application\Handler;

use App\Payment\Application\Command\ChargeCustomer;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\PaymentSucceeded;
use App\Payment\Domain\PaymentGateway;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChargeCustomerHandler
{
    public function __construct(
        private PaymentGateway $gateway,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(ChargeCustomer $command): void
    {
        // Handler o sáze neví. Jen vykoná krok a oznámí výsledek;
        // co bude dál, rozhodne Process Manager.
        try {
            $transactionId = $this->gateway->charge(
                $command->customerId,
                $command->amountCents,
            );
        } catch (\RuntimeException $e) {
            $this->eventBus->dispatch(new PaymentFailed(
                eventId: Uuid::v7(),
                orderId: $command->orderId,
                failureReason: $e->getMessage(),
            ));

            return;
        }

        $this->eventBus->dispatch(new PaymentSucceeded(
            eventId: Uuid::v7(),
            orderId: $command->orderId,
            transactionId: $transactionId,
        ));
    }
}
:::

Selhání se hlásí událostí, ne výjimkou. Výjimka by skončila v retry smyčce Messengeru
a sága by se o neúspěchu nedozvěděla. Zůstala by viset ve stavu `AwaitingPayment`,
dokud ji nevypne timeout.

`PaymentGateway` je port do platební brány; pro ostatní kroky platí totéž se `StockService`
a `ShippingService`. Doména zná jen rozhraní, konkrétní adaptér přijde z infrastruktury:

:::code{language="php" filename="src/Payment/Domain/PaymentGateway.php"}
<?php

declare(strict_types=1);

namespace App\Payment\Domain;

interface PaymentGateway
{
    /**
     * @return string identifikátor transakce
     * @throws \RuntimeException když platba neprojde
     */
    public function charge(string $customerId, int $amountCents): string;

    public function refund(string $transactionId, int $amountCents): string;
}
:::

Adaptér je jediné místo, kde na knize nezáleží: za rozhraním může být HTTP klient
platební brány, nebo v testech a při rozbíhání ukázek pár řádků v paměti. Právě proto
rozhraní existuje.

:::code{language="php" filename="src/Payment/Infrastructure/InMemoryPaymentGateway.php"}
<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure;

use App\Payment\Domain\PaymentGateway;
use Symfony\Component\Uid\Uuid;

final readonly class InMemoryPaymentGateway implements PaymentGateway
{
    // Přepínač pro ruční zkoušku kompenzační větve; hodnotu dodá kontejner
    // (wiring o kus níž). V ostrém adaptéru ho nahradí odpověď brány.
    public function __construct(
        private bool $alwaysFails = false,
    ) {}

    public function charge(string $customerId, int $amountCents): string
    {
        if ($this->alwaysFails) {
            throw new \RuntimeException('Platba zamítnuta.');
        }

        return (string) Uuid::v7();
    }

    public function refund(string $transactionId, int $amountCents): string
    {
        return (string) Uuid::v7();
    }
}

:::

`StockService` a `ShippingService` mají stejnou stavbu. Uvádím je, protože bez nich se
sága zastaví po první platbě a kompenzační větev `STOCK_FAILS=1` nejde vůbec spustit:

:::code{language="php" filename="src/Warehouse/Domain/StockService.php + src/Shipping/Domain/ShippingService.php (+ adaptéry)"}
<?php

declare(strict_types=1);

namespace App\Warehouse\Domain;

interface StockService
{
    /** @throws \RuntimeException když zboží není skladem */
    public function reserve(string $orderId): void;

    public function release(string $orderId): void;
}

namespace App\Shipping\Domain;

interface ShippingService
{
    /** @return string identifikátor zásilky */
    public function create(string $orderId): string;

    public function cancel(string $shipmentId): void;
}

namespace App\Warehouse\Infrastructure;

use App\Warehouse\Domain\StockService;
use Symfony\Component\Uid\Uuid;

final readonly class InMemoryStockService implements StockService
{
    public function __construct(private bool $alwaysFails = false) {}

    public function reserve(string $orderId): void
    {
        if ($this->alwaysFails) {
            throw new \RuntimeException('Zboží není skladem.');
        }
    }

    public function release(string $orderId): void
    {
    }
}

// InMemoryShippingService vypadá stejně: create() vrací Uuid::v7(),
// cancel() nedělá nic.
:::

Zapojení do kontejneru patří do `services.yaml`; bez něj mají přepínače výchozí `false`
a kompenzační větev se nespustí, ať do prostředí napíšete cokoli:

:::code{language="yaml" filename="config/services.yaml (výřez)"}
    App\Payment\Infrastructure\InMemoryPaymentGateway:
        arguments: { $alwaysFails: '%env(bool:PAYMENT_FAILS)%' }

    App\Warehouse\Infrastructure\InMemoryStockService:
        arguments: { $alwaysFails: '%env(bool:STOCK_FAILS)%' }

    App\Warehouse\Domain\StockService: '@App\Warehouse\Infrastructure\InMemoryStockService'
    App\Shipping\Domain\ShippingService: '@App\Shipping\Infrastructure\InMemoryShippingService'
:::

Zbylé handlery kroků mají stejnou stavbu jako `ChargeCustomerHandler`: vykonají operaci
a vydají událost. Ty, které jen mění stav agregátu, jsou ještě kratší:

:::code{language="php" filename="src/Ordering/Application/Handler/MarkOrderPaidHandler.php (+ ShipOrderHandler)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\MarkOrderPaid;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class MarkOrderPaidHandler
{
    public function __construct(
        private OrderRepository $orders,
        private EntityManagerInterface $em,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(MarkOrderPaid $command): void
    {
        $order = $this->orders->get(OrderId::fromString($command->orderId));
        $order->markPaid();
        $this->em->flush();

        // Bez tohohle kroku by se doménová událost nikam nedostala
        // a projekce by o změně stavu nevěděla. Dispatch uvnitř transakce
        // je tu v pořádku: event.bus je synchronní a nic neopouští proces.
        // Kdyby událost mířila do brokera, patřila by do outboxu.
        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}

// ShipOrderHandler je totožný, jen volá
// $order->ship(ShipmentId::fromString($command->shipmentId));
:::

Příkazy samotné jsou prosté DTO. Primitivy nesou proto, že putují přes asynchronní
transport: co se serializuje do fronty, musí jít bez ztráty sestavit zpátky. Hodnotový
objekt to zvládne, pokud má veřejný konstruktor a veřejné vlastnosti. `CancelOrderCommand`
z kapitoly o autorizaci nese `OrderId` právě z tohoto důvodu. Řetězec je ale odolnější
vůči změnám: přejmenované pole ve VO shodí každou zprávu, která ve frontě čekala z minulé
verze aplikace.

:::code{language="php" filename="src/Ordering/Application/Command/MarkOrderPaid.php + ShipOrder.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

final readonly class MarkOrderPaid
{
    public function __construct(public string $orderId) {}
}

final readonly class ShipOrder
{
    public function __construct(
        public string $orderId,
        public string $shipmentId,
    ) {}
}
:::

Příkazy pro cizí kontexty mají tentýž tvar a doplňují je kompenzace ze sekce 14.03:

:::code{language="php" filename="src/Warehouse/Application/Command/ReserveStock.php + ReleaseStock.php, src/Shipping/Application/Command/CreateShipment.php + CancelShipment.php"}
<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Command;

final readonly class ReserveStock
{
    public function __construct(public string $orderId) {}
}

final readonly class ReleaseStock
{
    public function __construct(public string $orderId) {}
}

namespace App\Shipping\Application\Command;

final readonly class CreateShipment
{
    public function __construct(public string $orderId) {}
}

final readonly class CancelShipment
{
    // Zásilka se ruší podle vlastní identity, ne podle objednávky. Sága
    // si shipmentId ukládá z ShipmentCreated – jinde ho v tu chvíli
    // nemá kdo znát.
    public function __construct(
        public string $orderId,
        public string $shipmentId,
    ) {}
}
:::

Zbylých pět handlerů sedí ve svých kontextech a od `ChargeCustomerHandler` se liší jen
tím, co volají. Vypsané jsou dva, na kterých je vidět obojí: krok, který hlásí výsledek
událostí, i kompenzace, která nehlásí nic. Zbylé se od nich liší jen volanou službou
a jménem události, ale **vynechat je nejde** – chybějící handler se projeví až jako
`No handler for message` v dead-letter frontě, zatímco proces poběží dál. To je druh
chyby, kterou nikdo nehledá, dokud se stavy nerozejdou.

:::code{language="php" filename="src/Warehouse/Application/Handler/ReserveStockHandler.php + ReleaseStockHandler.php"}
<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Handler;

use App\Warehouse\Application\Command\ReleaseStock;
use App\Warehouse\Application\Command\ReserveStock;
use App\Warehouse\Domain\Event\StockReservationFailed;
use App\Warehouse\Domain\Event\StockReserved;
use App\Warehouse\Domain\StockService;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ReserveStockHandler
{
    public function __construct(
        private StockService $stock,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(ReserveStock $command): void
    {
        try {
            $this->stock->reserve($command->orderId);
        } catch (\RuntimeException $e) {
            $this->eventBus->dispatch(new StockReservationFailed(
                eventId: Uuid::v7(),
                orderId: $command->orderId,
                failureReason: $e->getMessage(),
            ));

            return;
        }

        $this->eventBus->dispatch(new StockReserved(
            eventId: Uuid::v7(),
            orderId: $command->orderId,
        ));
    }
}

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ReleaseStockHandler
{
    public function __construct(private StockService $stock) {}

    public function __invoke(ReleaseStock $command): void
    {
        // Kompenzace nevydává událost: na uvolnění rezervace nikdo nečeká.
        $this->stock->release($command->orderId);
    }
}
:::

`CreateShipmentHandler` v kontextu `Shipping` volá `ShippingService::create()` a úspěch
hlásí událostí `ShipmentCreated` se `ShipmentId` z brány. `CancelShipmentHandler` je proti
němu kratší: zavolá `cancel($command->shipmentId)` a **nevydá nic**. Kdyby vydal
`ShipmentCreated`, Process Manager by na ni ve stavu `Compensating` odpověděl dalším
`CancelShipment` a vznikla by nekonečná smyčka příkazu a události. `RefundCustomerHandler` je protějšek `ChargeCustomerHandler`: zavolá
`PaymentGateway::refund()` a podle výsledku vydá `RefundSucceeded`, nebo `RefundFailed`.

`CancelOrderHandler` má vlastní výpis v [kapitole o autorizaci](/autorizace-v-ddd#async-
authorization) a je potřeba právě ten. Musí totiž rozpoznat systémovou identitu a uvolnit
zámek – jinak kompenzace narazí na `OrderLockedBySagaException`, skončí v dead-letter frontě
a objednávka zůstane zaplacená, nezrušená a zamčená navždy. Verze „jako
`MarkOrderPaidHandler`, jen volá `cancel()`“ pro ságu nestačí.

Porty `StockService` a `ShippingService` mají stejnou stavbu jako `PaymentGateway`:
rezervovat, uvolnit, vytvořit zásilku, zrušit ji.

Adaptéry jsou jediné místo, kde na knize záleží nejmíň: za rozhraním může být HTTP klient
cizí služby, tabulka v téže databázi nebo v testech pole v paměti. Právě proto rozhraní
existuje. Pro rozběhnutí ukázek stačí adaptér, který vždy uspěje, a druhý, který vždy
selže. Kompenzační větve jinak nemá co spustit.

Právě tady se pozná, jestli je proces domyšlený: chybí-li jediný handler, sága doběhne
do `Completed` a objednávka zůstane rozpracovaná. Nikde nespadne, jen se stavy rozejdou.

### Kolik logiky smí Process Manager mít {#logika-v-process-manageru}

*CQRS Journey* odpovídá striktně: process manager zprávy jen routuje a překládá mezi
typy, doménová logika patří do agregátů. `OrderProcessManager` z ukázky tuto hranici
překračuje. Rozhoduje, kdy se kompenzuje, jakým příkazem a jak dlouho se čeká na
odpověď.

Kniha se drží volnějšího Richardsonova výkladu a tu odchylku pojmenovává. Orchestrátor
smí znát pořadí kroků, podmínky přechodů a časové limity, protože to je logika
*procesu*. Nepatří do žádného z agregátů, které proces koordinuje. Pravidla jednoho
agregátu do něj naopak nepatří: výpočet ceny, kontrola dostupnosti zboží, ověření
kreditního limitu. Ta zůstávají v doméně a Process Manager si pro ně posílá příkaz.
Praktický test: obsahuje-li třída ságy podmínku nad doménovými daty, na kterou by
uměl odpovědět agregát, sedí ta logika ve špatné vrstvě.

## 14.06 Perzistence stavu ságy {#perzistence-stavu}

Process Manager potřebuje **perzistentní úložiště stavu**, aby přežil
restart workeru, nové nasazení aplikace i horizontální škálování na více instancí.
Bez perzistence by pád workeru mezi kroky `OrderPlaced` a
`PaymentSucceeded` smazal informaci o tom, kde se proces nachází.
Sága by navždy „visela“ a nikdo by ji nedokončil ani nezkompenzoval. Stav ságy proto
ukládáme do databáze jako Doctrine entitu.

Entita leží v Application vrstvě, přestože Doctrine mapování jinak patří do
Infrastructure ([Hexagonal Architecture](/architektonicke-styly#hexagonal)). Stav
procesu je aplikační starost a mapování přímo na entitu šetří jednu vrstvu; kdo
trvá na přísném vrstvení, přesune Doctrine část do Infrastructure.

:::callout{type="pattern"}
### PHP: OrderSaga – Doctrine entita {#saga-state-entity-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/OrderSaga.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'order_saga')]
#[ORM\UniqueConstraint(name: 'uniq_saga_correlation', columns: ['saga_type', 'correlation_id'])]
#[ORM\Index(fields: ['status'], name: 'idx_saga_status')]
class OrderSaga
{
    // Auto-increment místo Uuid::v7() je záměr: jde o interní klíč
    // infrastrukturního stavu. Business identitou ságy je correlation_id.
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $sagaType;

    #[ORM\Column(length: 128)]
    private string $correlationId;

    #[ORM\Column(length: 32)]
    private string $status;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $context = [];

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $processedEventIds = [];

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    private function __construct() {}

    public static function start(
        string $sagaType,
        string $correlationId,
        OrderSagaStatus $status,
        array $context = [],
    ): self {
        $state = new self();
        $state->sagaType = $sagaType;
        $state->correlationId = $correlationId;
        $state->status = $status->value;
        $state->context = $context;
        $state->startedAt = new \DateTimeImmutable();
        $state->updatedAt = new \DateTimeImmutable();

        return $state;
    }

    public function transitionTo(OrderSagaStatus $newStatus): void
    {
        $this->status = $newStatus->value;
        $this->updatedAt = new \DateTimeImmutable();

        if ($newStatus === OrderSagaStatus::Completed || $newStatus === OrderSagaStatus::Failed) {
            $this->completedAt = new \DateTimeImmutable();
        }
    }

    public function status(): OrderSagaStatus
    {
        return OrderSagaStatus::from($this->status);
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return $this->context;
    }

    public function updateContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function hasProcessed(string $eventId): bool
    {
        return in_array($eventId, $this->processedEventIds, true);
    }

    public function markProcessed(string $eventId): void
    {
        $this->processedEventIds[] = $eventId;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function isTerminated(): bool
    {
        return $this->completedAt !== null;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
:::
:::

:::callout{type="pattern"}
### PHP: Rozhraní OrderSagaRepository {#saga-state-repo-interface-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/OrderSagaRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

/**
 * Rozhraní repozitáře stavu ságy - umožňuje snadnou záměnu
 * implementace (Doctrine v produkci, in-memory v testech).
 */
interface OrderSagaRepository
{
    public function save(OrderSaga $state): void;

    /**
     * Vrací null, když sága pro danou korelaci neexistuje. Objednávka mohla
     * vzniknout jinou cestou nebo ještě před nasazením procesu – a to není
     * chyba, kterou by měl hlásit repozitář.
     */
    public function findByCorrelationId(string $correlationId): ?OrderSaga;

    /** @return list<OrderSaga> */
    public function findStale(\DateTimeImmutable $olderThan): array;
}
:::
:::

:::callout{type="pattern"}
### PHP: Doctrine implementace OrderSagaRepository {#saga-state-repository-heading}

:::code{language="php" filename="src/Ordering/Infrastructure/Saga/DoctrineOrderSagaRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Saga;

use App\Ordering\Application\Saga\OrderSaga;
use App\Ordering\Application\Saga\OrderSagaRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOrderSagaRepository implements OrderSagaRepository
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function save(OrderSaga $state): void
    {
        $this->em->persist($state);
        $this->em->flush();
    }

    public function findByCorrelationId(string $correlationId): ?OrderSaga
    {
        return $this->em->getRepository(OrderSaga::class)
            ->findOneBy(['correlationId' => $correlationId]);
    }

    /** @return list<OrderSaga> */
    public function findStale(\DateTimeImmutable $olderThan): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(OrderSaga::class, 's')
            ->where('s.completedAt IS NULL')
            ->andWhere('s.updatedAt < :threshold')
            ->setParameter('threshold', $olderThan)
            ->getQuery()
            ->getResult();
    }
}
:::
:::

Perzistence stavu je předpokladem obnovy po selhání. Worker spadne uprostřed
zpracování zprávy `PaymentSucceeded`, dřív než ji stihne potvrdit. Po restartu
Messenger tutéž zprávu doručí znovu a Process Manager si stav ságy načte z databáze.
Ví tedy, že proces čekal na platbu (`AwaitingPayment`), a neztratil kontext.

Samo o sobě to ale nestačí. Ukázaná metoda `onPaymentSucceeded()` žádný guard nemá,
takže po redelivery provede přechod i příkaz `ReserveStock` podruhé. Obnovu bez
duplicit dodá až idempotentní přechod z podsekce
[Multi-worker Process Manager](#multi-worker-heading), který zpracované události
eviduje a druhé doručení zahodí. Metoda `findStale()` v repozitáři pak umožňuje
periodicky detekovat zaseklé ságy, které se déle než stanovený práh neposunuly
kupředu, a spustit pro ně kompenzaci nebo eskalaci.

:::callout{type="note"}
### Optimistické zamykání v produkci {#optimistic-locking-heading}

Entita `OrderSaga` nese sloupec `version` s atributem `#[ORM\Version]`.
Bez něj by dva workery zpracovávající události pro stejnou objednávku mohly
současně načíst stejný stav ságy a přepsat si navzájem změny. Optimistický zámek
zajistí, že druhý worker dostane výjimku `OptimisticLockException` a Messenger
zprávu automaticky zopakuje.
:::

### Multi-worker Process Manager – co se rozpadne {#multi-worker-heading}

Optimistic lock řeší konflikt na *jedné* instanci ságy. V produkci se stane
něco složitějšího: stejná zpráva (např. `PaymentSucceeded` z téže objednávky)
dorazí do více worker instancí současně (Messenger `numprocs > 1`),
nebo *různé* eventy z téže ságy dorazí ve špatném pořadí (Kafka partition
balancing, RabbitMQ multiple consumers). Důsledky:

- **Race na vznik ságy.** První `OrderPlaced` pro stejné `orderId`
  dorazí do dvou workerů současně. Oba vidí, že sága ještě neexistuje, a oba
  zavolají `OrderSaga::start`. UNIQUE constraint na
  `(saga_type, correlation_id)` jednoho z nich zabije, druhý zůstane. Bez constraintu
  vzniknou dvě paralelní ságy téže objednávky a soupeří o stav.
- **Out-of-order events.** `PaymentSucceeded` dorazí dřív než
  `OrderPlaced`, sága ještě není ve stavu `AwaitingPayment`. Process Manager
  netuší, co s ní. Buď event zahodí (bug v doméně), nebo ho zařadí do
  *pending* fronty pro pozdější zpracování (komplexní stavový automat).
- **Kompenzační závody.** Sága rozhodne `Compensate`, vyšle `RefundCustomer`,
  a *zároveň* dorazí pomalá `PaymentSucceeded` z jiného workeru. Druhá
  zpráva může resetovat stav ságy z `Compensating` zpět na `AwaitingShipment`,
  ale `RefundCustomer` už běží – zákazník dostane refund, a přesto proces
  pokračuje k expedici.

Standardní obrana proti všem třem:

:::callout{type="pattern"}
### Vzor: idempotentní state transitions + UNIQUE constraint {#idempotent-saga-transitions-heading}

Metoda doplněná do entity `OrderSaga` z předchozí ukázky. Využívá sloupce
`processedEventIds` a guard stavového automatu. Parametr `$eventId` musí nést
**sama událost**, tedy identifikátor přidělený při jejím vzniku, typicky `Uuid::v7()`.

Transportní identifikátory se k tomu nehodí. `TransportMessageIdStamp` je podle
vlastní dokumentace *„id of this message in that transport“*, tedy hodnota vázaná
na konkrétní transport a přidělená až při odeslání či příjmu. Po redelivery nebo
při průchodu jiným transportem se změní, takže by táž událost prošla dvakrát –
přesně to, čemu má idempotence zabránit.

Krokové události z [14.05](#process-manager-heading) proto nesou `eventId`. Bez něj se
guard nemá čeho chytit. Metoda patří do entity `OrderSaga` a Process Manager ji volá
místo přímého `transitionTo()`:

:::code{language="php" filename="src/Ordering/Application/Saga/OrderSaga.php (výřez)"}
/** @return bool zda se přechod opravdu odehrál */
public function applyPaymentSucceeded(string $eventId): bool
{
    // 1) Idempotence: stejný event už zpracován? Skip.
    if ($this->hasProcessed($eventId)) {
        return false;
    }

    // 2) Guard stavového automatu: smí přechod nastat?
    if ($this->status() !== OrderSagaStatus::AwaitingPayment) {
        // Out-of-order: událost dorazila ve stavu, kde ji nečekáme.
        // Buď: zalogovat a zahodit (idempotentní), nebo zařadit do pending fronty.
        return false;
    }

    $this->transitionTo(OrderSagaStatus::AwaitingStockReservation);
    $this->markProcessed($eventId);

    return true;
}
:::

Volání z Process Manageru pak vypadá takhle. Návratová hodnota říká, jestli se přechod
opravdu odehrál, takže se příkazy neodešlou podruhé. Výpis **nahrazuje celou metodu**
z [14.05](#process-manager-heading), včetně zápisu do `completedSteps`:

:::code{language="php" filename="src/Ordering/Application/Saga/OrderProcessManager.php (výřez)"}
private function onPaymentSucceeded(PaymentSucceeded $event): void
{
    $state = $this->sagaRepository->findByCorrelationId($event->orderId);

    if ($state === null || $state->status()->isTerminal()) {
        return;
    }

    // Guard vrátí false u opakovaného doručení. Bez něj by se
    // MarkOrderPaid i ReserveStock odeslaly znovu a v completedSteps
    // by přibyl druhý „payment_charged“.
    if (!$state->applyPaymentSucceeded((string) $event->eventId)) {
        return;
    }

    $state->updateContext('transactionId', $event->transactionId);

    // Bez tohohle řádku nemá pozdější kompenzace podle čeho poznat, že
    // platba proběhla, a RefundCustomer se nikdy neodešle. Objednávka
    // skončí zrušená se strženými penězi a sága uvázne v Compensating.
    $state->updateContext('completedSteps', [
        ...$state->context()['completedSteps'],
        'payment_charged',
    ]);
    $this->sagaRepository->save($state);

    $this->commandBus->dispatch(new MarkOrderPaid(orderId: $event->orderId));
    $this->commandBus->dispatch(new ReserveStock(orderId: $event->orderId));
}
:::
:::

Guard patří **do každého kroku**, ne jen do platby. `OrderSaga` proto dostane obdobné
`applyStockReserved()` a `applyShipmentCreated()`; liší se jen očekávaným stavem a cílem
přechodu. Bez nich vyrobí opakovaně doručená `StockReserved` druhou i třetí zásilku,
v kontextu přežije jen ta poslední a kompenzace zruší jednu ze tří. Objednávka přitom
skončí `shipped` a dead-letter fronta zůstane prázdná – nikde se to nepozná.

Druhá polovina obrany patří do agregátu. `Order::cancel()` je idempotentní záměrně;
`markPaid()` a `ship()` ale ne, přestože jdou přes tentýž asynchronní transport.
Opakované doručení `MarkOrderPaid` tak skončí po třech pokusech v DLQ s hláškou
„Nelze přejít ze stavu paid do stavu paid". Data se nerozbijí, ale nikdo se o tom
nedozví. Obě metody proto mají na začátku tutéž větev jako `cancel()`:

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (výřez)"}
public function markPaid(): void
{
    // Opakované doručení příkazu není chyba volajícího, jen už není
    // co dělat. Transport garantuje at-least-once, ne exactly-once.
    if ($this->status === OrderStatus::Paid) {
        return;
    }

    if ($this->status !== OrderStatus::Confirmed) {
        throw InvalidOrderStateTransitionException::cannotTransition(
            $this->status->value,
            OrderStatus::Paid->value,
        );
    }

    $this->status = OrderStatus::Paid;
}

// ship() má tutéž větev pro OrderStatus::Shipped.
:::

Tři stavební prvky, které zde fungují společně:

- **UNIQUE constraint na `(saga_type, correlation_id)`** zabrání duplicitnímu
  vzniku ságy. Druhý INSERT vyhodí `UniqueConstraintViolationException`, handler
  ji zachytí a načte existující ságu místo vytvoření nové.
- **`processedEventIds` v entitě** drží seznam již zpracovaných event ID.
  Stejný event přijde dvakrát → druhé volání skončí na guardu. To je „inbox
  per saga“, paralela [Idempotent Inbox z Outbox kapitoly](/outbox-pattern#inbox).
- **State machine guard** odmítne out-of-order event. Buď ho zahodí
  (idempotentně), nebo ho zařadí do *pending events* sloupce pro pozdější aplikaci.

Dvě omezení tohoto řešení stojí za vyslovení. Kontrola `in_array` nad JSON sloupcem
není atomická: dva workery mohou projít guardem současně, protože každý pracuje nad
svou kopií načtenou před zápisem. Duplicitní zápis zachytí až optimistický zámek
a jeden z workerů dostane `OptimisticLockException`. Guard tedy odfiltruje běžné
redelivery, souběh řeší až verze řádku. Druhé omezení je růst: `processedEventIds`
se nikdy nezmenšuje. U ságy s desítkami kroků to nevadí. U dlouhoběžících procesů
s tisíci událostí ale sloupec bobtná: po dokončení ságy ho vyprázdněte, nebo
si držte jen posledních N identifikátorů.

### Distributed deadlock mezi ságami {#distributed-deadlock-heading}

Klasický dvouagregátový deadlock přes Doctrine pessimistic lock: sága A drží
lock na `Order#1` a žádá o `Inventory#42`; sága B drží lock na `Inventory#42`
a žádá o `Order#1`. Postgres deadlock detector po cca 1 s jednu z transakcí
zabije, ale do té doby čeká celý connection pool a workery stojí.

S **eventual consistency** (Vernonovo „eventual consistency mimo hranici agregátu“,
viz [Návrh agregátu](/navrh-agregatu#transactional-consistency)) deadlock
**nemůže nastat na úrovni databáze**. Každý krok ságy je samostatná transakce
na jeden agregát. Jiný typ deadlocku ale možný je: **logický cycle deadlock**
v sáze samotné.

Příklad: sága `OrderProcess` čeká na `PaymentSettled`. Sága `RefundProcess` (pro
storno) čeká na `OrderCancelled`. Pokud kompenzace způsobí storno objednávky
a zároveň zrušení refundu, obě ságy čekají na sebe a žádná nedokončí.

:::callout{type="warn"}
### Detekce logických deadlocků {#deadlock-detekce-heading}

Optimistic lock to nezachytí. Obě ságy mají rozdílná ID a vlastní sloupce `version`. Detekce vyžaduje:

- **Timeout management.** Každá sága má `maxDurationMinutes`. Sága,
  která neúspěšně čeká déle než threshold, se eskaluje na manuální zásah
  nebo automaticky kompenzuje. Implementace v sekci
  [Timeouty a deadliny](#timeouty).
- **Topologický audit.** Při návrhu kompenzací pomáhá graf závislostí
  ság: pokud obsahuje cyklus, existuje potenciální deadlock. V produkci ho
  spustí konkrétní sekvence eventů.
- **Distributed tracing** (OpenTelemetry, Jaeger). Saga ID se propaguje jako
  `correlation_id` ve všech eventech a HTTP voláních. Zaseklé ságy
  najdete jako trace bez `END` spanu po N minutách.
:::

### Recovery z nekonzistentního stavu ságy {#saga-recovery-heading}

Sága může skončit v nekonzistentním stavu z legitimních příčin: nasazení uprostřed
transakce, OOM kill v polovině kompenzačního kroku, migrace schématu, která změnila
tvar uloženého JSONu. Operátor potřebuje tři nástroje.

Prvním je read-only inspekce. Příkaz `app:saga:show <id>` vypíše aktuální stav,
čekající události, zpracovaná ID událostí a počet pokusů, plus odkaz na ságu
v Grafaně. Druhým je manuální přechod: `app:saga:force-transition <id> <to>`
s povinným `--reason="..."` aktualizuje status, zapíše audit log a zneplatní čekající
události. Patří výhradně operátorům a každé jeho použití signalizuje bug v sáze nebo
neošetřený doménový scénář. Třetí nástroj je replay od checkpointu: u idempotentní
ságy stačí smazat stav a přehrát všechny její události z outboxu nebo event store.
Podmínkou je znát správnou počáteční událost (typicky ID události `OrderPlaced`).

### Izolace ság: ACD bez I {#izolace-sag}

Databázová transakce dává ACID. Sága jen ACD: atomicitu přes kompenzace,
konzistenci a trvanlivost. Izolace chybí: každý commitnutý krok je okamžitě
viditelný všem souběžným procesům, dlouho předtím, než celá sága skončí.
Jiný proces nad stejnou objednávkou nebo skladem může rozpracovaný stav přečíst
i přepsat. Vznikají anomálie známé z databází: *lost update* (storno
ságy přepíše změnu, kterou objednávková sága právě provádí) a *dirty read*
(proces si přečte platbu, kterou kompenzace vzápětí vrátí).

Richardson pro tyto anomálie popisuje sadu protiopatření (*countermeasures*).
První dvě pracují s daty. *Semantic lock* je aplikační zámek: záznam, na kterém
sága pracuje, nese stav s příznakem `*_PENDING` a ostatní procesy ho musí
respektovat. *Commutative updates* jsou operace navržené tak, aby na pořadí
nezáleželo – připsání a odepsání částky komutuje, nastavení absolutního
zůstatku ne.

Zbylá dvě pracují s průběhem ságy. *Pessimistic view* přeuspořádává kroky:
změna, jejíž dirty read napáchá největší škodu (třeba připsání kreditu), se
přesune za pivot transakci (viz [Když selže kompenzace](#selhani-kompenzace)).
Při *reread value* si krok před zápisem hodnotu znovu načte a ověří, že se od
prvního čtení nezměnila; jinak ságu zastaví nebo opakuje.

Semantic lock je z nich nejčastější:

:::callout{type="pattern"}
### PHP: Semantic lock přes stav *_PENDING {#semantic-lock-heading}

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (výřez)"}
// Příznak vedle stavu, ne nový stav. Stavový graf objednávky by se jinak
// zdvojil o „pending" variantu ke každé hraně.
private bool $sagaInProgress = false;

public function lockForSaga(): void
{
    $this->sagaInProgress = true;
}

public function releaseSagaLock(): void
{
    $this->sagaInProgress = false;
}

public function cancel(string $reason, \DateTimeImmutable $when): void
{
    // Zámek drží proces, ne uživatel. Odmítnutí je tady lepší než tiché
    // storno: sága by dál strhávala platbu a vytvářela zásilku
    // k objednávce, která už neexistuje.
    if ($this->sagaInProgress) {
        throw new OrderLockedBySagaException($this->id);
    }

    // ... zbytek podle kapitoly o autorizaci
}
:::
:::

Chybějící díly jsou dva prosté typy:

:::code{language="php" filename="src/Ordering/Application/Command/ReleaseOrderLock.php + src/Ordering/Domain/Exception/OrderLockedBySagaException.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

final readonly class ReleaseOrderLock
{
    public function __construct(public string $orderId) {}
}

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\OrderId;

final class OrderLockedBySagaException extends \DomainException
{
    public function __construct(public readonly OrderId $orderId)
    {
        parent::__construct(sprintf(
            'Objednávku „%s“ právě zpracovává jiný proces.',
            $orderId->value,
        ));
    }
}
:::

Handler je krátký, ale nosný: bez něj zůstane objednávka zamčená i po úspěšném
doběhnutí procesu a zákazník ji nezruší nikdy.

:::code{language="php" filename="src/Ordering/Application/Handler/ReleaseOrderLockHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\ReleaseOrderLock;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ReleaseOrderLockHandler
{
    public function __construct(
        private OrderRepository $orders,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(ReleaseOrderLock $command): void
    {
        $order = $this->orders->get(OrderId::fromString($command->orderId));
        $order->releaseSagaLock();

        // Žádná událost. Uvolnění zámku není doménová změna, na kterou
        // by někdo čekal – jen konec výhradního přístupu procesu.
        $this->em->flush();
    }
}
:::

Zámek má cenu jen tehdy, když ho někdo uvolní i při selhání. Jinak zůstane objednávka
zablokovaná navždy. Uvolnění proto patří do každé terminální větve ságy, ne jen
do té úspěšné. V ukázkách knihy to dělá metoda `finish()` v Process Manageru a u větví
končících stornem sám `CancelOrderHandler`, protože příkaz přichází pod systémovou
identitou.

Samotný zámek ale nestačí. `OrderProcessManager` proto navíc odebírá `OrderCancelled`
a při stornu spustí kompenzaci hotových kroků. Obojí řeší jinou část téhož problému:

- **Zámek** brání storna v okně, kdy proces běží. Bez něj by uživatel zrušil objednávku
  uprostřed ságy, ta by dál strhla platbu a vytvořila zásilku – a příkazy `MarkOrderPaid`
  a `ShipOrder` by pak jeden po druhém umřely v DLQ, aniž by kdokoli spadl.
- **Reakce na `OrderCancelled`** pokrývá storna mimo to okno a případ, kdy objednávku
  zruší jiný proces.
- **Guard na `Compensating`** v `onStockReserved()` a `onShipmentCreated()` ošetřuje
  opožděný úspěch: rezervace nebo zásilka dorazí až po zahájení kompenzace, takže se
  rovnou zase uvolní. Kontrola na terminální stav sama nestačí. `Compensating`
  terminální není.

Že jsou potřeba všechny tři, je vidět až za běhu. Sága bez nich doběhne do `Completed`
nad zrušenou objednávkou a rozdíl se pozná jedině čtením dead-letter fronty.

Volba mezi zámkem a plnou reakcí je doménová: **smí zákazník zrušit objednávku, u které
už běží platba?** Odpověď „ne, ať to zkusí za chvíli“ je legitimní a levnější.

Má to ale důsledek, který stojí za vyslovení. Složíte-li kapitoly téhle knihy dohromady,
vzniká každá objednávka rovnou uzamčená (`placeWithItems()` volá `lockForSaga()`) a zámek
uvolní až sága ve chvíli, kdy je objednávka `shipped` nebo `cancelled`. Zákazník se tak
k vlastnímu stornu **nedostane nikdy**: dokud proces běží, tlačítko se nenabídne, a až
doběhne, je pozdě. Celý tok z kapitoly o autorizaci pak v integrované aplikaci obsluhuje
jen systémový aktér.

Pro ukázkový e-shop je to obhajitelné, pro skutečný zpravidla ne. Kdo chce zákazníkovi
storno umožnit, buď zámek nasadí až od kroku, který se špatně vrací (typicky vytvoření
zásilky, ne strhnutí platby), nebo ho vynechá a spolehne se na reakci ságy
na `OrderCancelled`.

Kolizní požadavky lze místo výjimky také frontovat a provést po uvolnění zámku;
pro většinu domén ale stačí odmítnutí výjimkou a opakování na straně klienta.

*Citace: Richardson, C., **Microservices Patterns** (2018), kap. 4.*

## 14.07 Implementace v Symfony Messenger {#messenger-implementace}

Předchozí sekce ukázaly Process Manager (orchestrátor) a perzistenci stavu ságy. Nyní
propojíme obě části s **Symfony Messenger**, asynchronním message busem,
který spolehlivě doručuje události a příkazy mezi kontexty.
Základní konfiguraci Messenger busů popisuje kapitola
[CQRS – Symfony Messenger](/cqrs#symfony-messenger). Zde se
zaměříme na specifika pro ságy: **oddělené transporty** pro události
a příkazy a **retry strategie**, bez kterých dlouhotrvající procesy
ztrácejí zprávy při běžných výpadcích.

:::callout{type="pattern"}
### YAML: Kompletní konfigurace Messenger {#messenger-yaml-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez – plná konfigurace v kapitole o CQRS)"}
# config/packages/messenger.yaml
framework:
    messenger:
        default_bus: command.bus

        buses:
            command.bus:
                middleware:
                    - doctrine_transaction
            event.bus:
                default_middleware:
                    enabled: true
                    allow_no_handlers: true

        transports:
            async_events:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
            async_commands:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2

        routing:
            'App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent': async_events
            'App\Payment\Domain\Event\PaymentSucceeded': async_events
            'App\Payment\Domain\Event\PaymentFailed': async_events
            'App\Warehouse\Domain\Event\StockReserved': async_events
            'App\Warehouse\Domain\Event\StockReservationFailed': async_events
            'App\Shipping\Domain\Event\ShipmentCreated': async_events
            'App\Payment\Domain\Event\RefundSucceeded': async_events
            'App\Payment\Domain\Event\RefundFailed': async_events
            'App\Payment\Application\Command\ChargeCustomer': async_commands
            'App\Payment\Application\Command\RefundCustomer': async_commands
            'App\Warehouse\Application\Command\ReserveStock': async_commands
            'App\Warehouse\Application\Command\ReleaseStock': async_commands
            'App\Shipping\Application\Command\CreateShipment': async_commands
            'App\Shipping\Application\Command\CancelShipment': async_commands
            'App\Ordering\Application\Command\MarkOrderPaid': async_commands
            'App\Ordering\Application\Command\ShipOrder': async_commands
            'App\Ordering\Application\Command\CancelOrderCommand': async_commands
            'App\Ordering\Application\Command\ReleaseOrderLock': async_commands
            # Bez tohoto routingu by se CheckSagaTimeout zpracoval synchronně
            # a DelayStamp by neměl žádný efekt.
            'App\Ordering\Application\Command\CheckSagaTimeout': async_commands
:::
:::

:::callout{type="pattern"}
### Kterou událost sága konzumuje {#order-placed-event-heading}

Sága překračuje hranici kontextu. Z Orderingu volá Payment, Warehouse i Shipping.
Konzumuje proto **integrační** událost z [Outboxu](/outbox-pattern#domain-event-heading),
ne doménovou `OrderPlaced` ze [Základních konceptů](/zakladni-koncepty#domain-events).
Ta nese hodnotové objekty a zůstává uvnitř kontextu; přes hranici jdou primitivy:

:::code{language="php" filename="src/Ordering/Application/IntegrationEvent/OrderPlacedIntegrationEvent.php (výřez)"}
final readonly class OrderPlacedIntegrationEvent
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $customerId,
        /** @var list<array{productId: string, quantity: int, unitPriceInCents: int}> */
        public array $items,
        public int $totalAmountCents,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
:::
:::

Celý tok funguje následovně: agregát `Order` v kontextu Ordering publikuje
událost `OrderPlaced` na event bus. Messenger ji podle konfigurace routingu
odešle do transportu `async_events`. Worker naslouchající na tomto transportu
zprávu vyzvedne a předá ji `OrderProcessManager`, který ji zpracuje metodou
`onOrderPlaced()`. Ta uloží stav ságy a dispatchne příkaz
`ChargeCustomer` na command bus. Messenger tento příkaz routuje do transportu
`async_commands`, kde ho vyzvedne handler v kontextu Payment. Po úspěšném
zpracování Payment publikuje `PaymentSucceeded` – a cyklus se opakuje
pro další krok procesu.

:::callout{type="note"}
### Spouštění workerů {#worker-command-heading}

V produkci běží pro každý transport oddělené workery:
`php bin/console messenger:consume async_events async_commands --time-limit=3600`.
Parametr `--time-limit` zajistí, že se worker po hodině automaticky restartuje
(a uvolní paměť). Pro vysokou dostupnost běží více instancí workeru. Každou
zprávu vyzvedne v danou chvíli jediný z nich. Doručení ale zůstává at-least-once:
při pádu workeru uprostřed zpracování se zpráva doručí znovu.
:::

:::callout{type="warn"}
### Pozor na ztrátu zpráv: Outbox pattern {#outbox-pattern-heading}

Výše uvedená konfigurace předpokládá, že doménová událost se spolehlivě dostane do
message brokeru. Samozřejmé to ale není. Agregát uloží změny do databáze
(Doctrine flush), ale dispatch události do fronty může selhat: síťový výpadek,
pád workeru mezi flush a dispatch, restart aplikace. Výsledkem je „ztracená“ událost
a sága, která se nikdy nespustí.

Řešením je **Outbox pattern**: událost se zapíše do speciální tabulky
`outbox` v téže databázové transakci jako doménová změna. Samostatný
proces (relay/poller) pak události z outbox tabulky přenáší do message brokeru a po
úspěšném odeslání je označí jako zpracované. Žádná událost se tak
neztratí, ani při selhání mezi kroky. Podrobně vzor rozebírá kapitola
[Outbox Pattern](/outbox-pattern), včetně relay workeru, idempotentního
inboxu a napojení na Symfony Messenger.
:::

Podrobnější informace o asynchronním zpracování zpráv, konfiguraci transportů a retry
strategiích najdete v kapitole [CQRS – asynchronní
zpracování](/cqrs#async).

## 14.08 Timeouty a deadliny {#timeouty}

Co se stane, když událost `PaymentSucceeded` nikdy nedorazí? Síťový výpadek,
nedostupnost platební brány, ztráta zprávy ve frontě. V distribuovaném systému musíte
vždy počítat s tím, že odpověď nepřijde. Bez explicitního timeout mechanismu sága zůstane
navždy ve stavu `AwaitingPayment` a objednávka se nikdy nedokončí ani nezruší.
Proto potřebujeme **timeout check**, odložený příkaz, který po uplynutí
stanovené doby zkontroluje, zda se sága posunula dál. Pokud ne, ságu ukončí,
nebo spustí kompenzaci – podle toho, zda už proběhl krok, který je co vracet.

:::callout{type="pattern"}
### PHP: CheckSagaTimeout command {#check-saga-timeout-heading}

:::code{language="php" filename="src/Ordering/Application/Command/CheckSagaTimeout.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

final readonly class CheckSagaTimeout
{
    public function __construct(
        public string $orderId,
        public string $expectedStatus,
    ) {}
}
:::
:::

:::callout{type="pattern"}
### PHP: CheckSagaTimeoutHandler {#check-saga-timeout-handler-heading}

:::code{language="php" filename="src/Ordering/Application/Handler/CheckSagaTimeoutHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\CheckSagaTimeout;
use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Application\Saga\OrderSaga;
use App\Ordering\Application\Saga\OrderSagaStatus;
use App\Ordering\Application\Saga\OrderSagaRepository;
use App\Payment\Application\Command\RefundCustomer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\SharedKernel\Domain\SystemActor;
use App\Ordering\Domain\ValueObject\OrderId;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CheckSagaTimeoutHandler
{
    public function __construct(
        private OrderSagaRepository $sagaRepository,
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(CheckSagaTimeout $command): void
    {
        $state = $this->sagaRepository->findByCorrelationId($command->orderId);

        // Sága se od posledního kroku posunula, nebo pro tuhle objednávku
        // vůbec neběží – timeout v obou případech neplatí.
        if ($state === null || $state->status()->value !== $command->expectedStatus) {
            return;
        }

        match (OrderSagaStatus::from($command->expectedStatus)) {
            OrderSagaStatus::AwaitingPayment => $this->failWithoutCompensation($state),
            OrderSagaStatus::AwaitingStockReservation => $this->compensatePayment($state),
            default => null,
        };
    }

    private function failWithoutCompensation(OrderSaga $state): void
    {
        // Platba nikdy neproběhla - není co kompenzovat.
        // Sága přechází rovnou do terminálního Failed. Zámek na objednávce
        // uvolní CancelOrderCommand níž, protože přichází pod systémovou
        // identitou.
        $state->transitionTo(OrderSagaStatus::Failed);
        $this->sagaRepository->save($state);

        $this->commandBus->dispatch(new CancelOrderCommand(
            orderId: OrderId::fromString($state->correlationId()),
            reason: 'Payment timeout',
            actorId: CustomerId::fromString(SystemActor::ID),
        ));
    }

    private function compensatePayment(OrderSaga $state): void
    {
        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaRepository->save($state);

        $this->commandBus->dispatch(new RefundCustomer(
            orderId: $state->correlationId(),
            customerId: $state->context()['customerId'],
            transactionId: $state->context()['transactionId'],
            amountCents: $state->context()['amountCents'],
            reason: 'Timeout: stock reservation not received',
        ));

        // CancelOrder zde nedispatchujeme. Objednávku zruší
        // onRefundSucceeded až po potvrzení refundu (viz sekci 9).
    }
}
:::
:::

Timeout se plánuje v Process Manageru při každém přechodu do stavu, ve kterém sága
na něco čeká. Ne jen jednou na začátku procesu: kdyby se hlídal pouze první krok,
sága uvízlá ve stavu `AwaitingStockReservation` by nikdy nikoho neupozornila.
Messenger k tomu nabízí `DelayStamp`, který zprávu podrží v transportu po zadanou
dobu a teprve poté ji doručí workeru:

:::callout{type="pattern"}
### PHP: Naplánování timeout checku v OrderProcessManager {#delay-stamp-heading}

:::code{language="php" filename="snippet.php"}
use Symfony\Component\Messenger\Stamp\DelayStamp;

/** Doba čekání pro každý stav, ve kterém sága očekává odpověď (v sekundách). */
private const TIMEOUTS = [
    'awaiting_payment' => 300,
    'awaiting_stock_reservation' => 30,
];

private function scheduleTimeout(string $orderId, OrderSagaStatus $status): void
{
    $seconds = self::TIMEOUTS[$status->value] ?? null;

    if ($seconds === null) {
        return;
    }

    // DelayStamp funguje jen na asynchronním transportu. Chybí-li
    // CheckSagaTimeout v routingu messenger.yaml, zpracuje se hlídač
    // okamžitě a odklad se tiše zahodí – kontrola pak proběhne dřív,
    // než na co čeká.
    $this->commandBus->dispatch(
        new CheckSagaTimeout(
            orderId: $orderId,
            expectedStatus: $status->value,
        ),
        [new DelayStamp($seconds * 1000)],
    );
}

private function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
{
    // ... (vytvoření OrderSaga a dispatch ChargeCustomer - viz sekci 14.05)

    $this->scheduleTimeout($event->orderId, OrderSagaStatus::AwaitingPayment);
}
:::
:::

Metoda v úplném výpisu `OrderProcessManager` v [14.05](#process-manager-heading) není.
Je to doplněk, ne jeho součást. Kdo ho vynechá, dostane ságu bez hlídačů; kdo ho doplní,
musí počítat s tím, že unit test v 14.12 proto filtruje `CheckSagaTimeout` metodou `steps()`.

Volání `scheduleTimeout()` patří do každé metody, která ságu převede do čekajícího
stavu. Metoda `onPaymentSucceeded()` tak naplánuje kontrolu pro
`AwaitingStockReservation`. Kontroly naplánované pro stav, který sága mezitím
opustila, zahodí handler hned na první podmínce. Plánování se proto nikde neruší.

Stav `AwaitingShipment` v konstantě chybí záměrně. Rezervace skladu je v tomto
procesu pivot transakce (viz [Když selže kompenzace](#selhani-kompenzace)), takže za
ní se už nekompenzuje a prošlý čas nemá vyvolat kompenzaci, ale eskalaci
operátorovi. Tu obstará detekce zaseklých ság ze [sekce 11](#monitoring).

:::callout{type="note"}
### Konfigurovatelné timeouty {#configurable-timeouts-heading}

Každý krok ságy může vyžadovat jiný timeout. Platební brána typicky potřebuje
**5 minut** (zákazník zadává údaje karty). Rezervace skladu by měla
proběhnout do **30 sekund** (interní synchronní operace). Potvrzení
zásilky může trvat i **24 hodin** (závisí na externím dopravci).
Timeouty proto patří do konfigurace, typicky jako parametry v
`services.yaml`, aby šly upravit bez změny kódu. Konstanta v ukázce výše je
zkratka pro čitelnost. Změna hodnoty se přitom dotkne i ság, které už běží:
jejich naplánované kontroly nesou původní čas a nová konfigurace je zpětně nepřepíše.
:::

:::callout{type="warn"}
### Požadavky na transport {#delay-stamp-warning-heading}

`DelayStamp` vyžaduje asynchronní transport, který podporuje odložené
doručování zpráv. **Doctrine transport** odklad řeší sloupcem
`available_at`. **AMQP transport** (RabbitMQ) ho podporuje nativně:
Symfony založí pomocnou frontu s TTL zprávy a dead-letter exchange, přes
který se zpráva po vypršení vrátí do cílové fronty. Plugin
`rabbitmq-delayed-message-exchange` není potřeba. Synchronní transport
(`sync://`) `DelayStamp` ignoruje a zprávu doručí okamžitě.
:::

## 14.09 Kompenzační strategie v praxi {#kompenzacni-strategie}

Když krok ságy selže, máme dvě základní strategie, jak situaci řešit. Volba závisí
na povaze chyby. Je přechodná (síťový výpadek, dočasná nedostupnost služby), nebo
trvalá (nedostatek prostředků na účtu, zboží vyprodáno)?

### Forward recovery (retry) {#forward-recovery}

Při **přechodných chybách** stačí opakování, tedy nový pokus o tentýž krok.
Pojmenování má háček: u Garcii-Moliny a Salema znamená forward recovery
restart od save-pointu bez kompenzací, tedy něco jiného. Kniha termín používá
v dnešním, užším významu. Symfony Messenger nabízí vestavěnou retry strategii
s exponenciálním backoffem, kterou jsme konfigurovali v
[sekci 7](#messenger-implementace). Worker automaticky opakuje selhané
zprávy podle nastavení `max_retries`, `delay` a
`multiplier`. Tento přístup je vhodný, když věříme, že problém je dočasný
a opakování může uspět.

### Backward recovery (kompenzace) {#backward-recovery}

Při **trvalých chybách** (selhání s doménovou příčinou) musíme spustit kompenzaci:
vrátit systém do konzistentního stavu kompenzačními akcemi v
**opačném pořadí** dokončených kroků. Kompenzace je
**sémantická**, nikoli technická. Neděláme
`DELETE FROM payments`. Místo toho dispatchujeme nový doménový příkaz
`RefundCustomer`, který vytvoří novou transakci (refund). Každá kompenzační
akce je plnohodnotná doménová operace s vlastními pravidly a událostmi.

:::diagram{fig="14.9-A" title="Kompenzační flow – rollback ságy v opačném pořadí" src="images/diagrams/8_sagas/compensation_flow.svg"}
:::

:::callout{type="pattern"}
### PHP: Kompenzační logika v opačném pořadí kroků {#compensate-method-heading}

:::code{language="php" filename="snippet.php"}
/**
 * Kompenzace: spouštěna při selhání libovolného kroku.
 * Provádí kompenzační akce v opačném pořadí dokončených kroků.
 */
private function compensate(OrderSaga $state): void
{
    $completedSteps = $state->context()['completedSteps'] ?? [];

    foreach (array_reverse($completedSteps) as $step) {
        match ($step) {
            'shipment_created' => $this->commandBus->dispatch(
                new \App\Shipping\Application\Command\CancelShipment(
                    orderId: $state->correlationId(),
                    shipmentId: $state->context()['shipmentId'],
                ),
            ),
            'stock_reserved' => $this->commandBus->dispatch(
                new ReleaseStock(orderId: $state->correlationId()),
            ),
            'payment_charged' => $this->commandBus->dispatch(
                new RefundCustomer(
                    orderId: $state->correlationId(),
                    customerId: $state->context()['customerId'],
                    transactionId: $state->context()['transactionId'],
                    amountCents: $state->context()['amountCents'],
                    reason: 'Order saga compensation',
                ),
            ),
            default => null,
        };
    }

    // Sága zůstává ve stavu Compensating. Do terminálního Failed
    // přejde až po potvrzení kompenzace (viz následující podsekci).
    // Více souběžných kompenzací vyžaduje evidenci, které ještě čekají.
    $state->transitionTo(OrderSagaStatus::Compensating);
    $this->sagaRepository->save($state);
}
:::
:::

:::callout{type="note"}
### Idempotence kompenzačních handlerů {#idempotent-compensation-heading}

Každý kompenzační handler **musí být idempotentní**. Zpráva může být
doručena vícekrát (at-least-once delivery), a proto handler musí bezpečně zvládnout
opakované volání. Například `RefundCustomerHandler` by měl před vytvořením
refundu ověřit, zda refund pro danou objednávku již neexistuje.
:::

### Když selže kompenzace {#selhani-kompenzace}

Metoda `onStockReservationFailed` ze [sekce 14.05](#orchestrace) dispatchuje
`RefundCustomer` a převede ságu do stavu `Compensating`. Tam sága zůstává.
Refund je asynchronní příkaz. Do terminálního `Failed` sága smí přejít až poté,
co dorazí potvrzení `RefundSucceeded`. Přechod do `Failed` hned po dispatchi
by ságu uzavřel dřív, než refund proběhl; při jeho selhání by se po penězích
zákazníka nikdo nesháněl. Stav „kompenzace odeslána, čeká se na potvrzení“
se označuje jako *compensation pending*.

:::callout{type="pattern"}
### PHP: Potvrzení kompenzace v OrderProcessManager {#refund-confirmation-heading}

:::code{language="php" filename="snippet.php"}
// Doplnění do OrderProcessManager. Union typ v __invoke i routing
// událostí už obě třídy znají – viz sekce 14.05.
private function onRefundSucceeded(RefundSucceeded $event): void
{
    $state = $this->sagaRepository->findByCorrelationId($event->orderId);

    if ($state === null) {
        return;
    }

    $state->transitionTo(OrderSagaStatus::Failed); // teprve teď je sága uzavřená
    $this->sagaRepository->save($state);

    $this->commandBus->dispatch(new CancelOrderCommand(
        orderId: OrderId::fromString($event->orderId),
        reason: 'Zboží není skladem, platba vrácena',
        actorId: CustomerId::fromString(SystemActor::ID),
    ));
}

private function onRefundFailed(RefundFailed $event): void
{
    // Sem se řízení dostane až poté, co Messenger vyčerpal
    // retry strategii s backoffem (viz sekci 7).
    $state = $this->sagaRepository->findByCorrelationId($event->orderId);

    if ($state === null) {
        return;
    }

    $state->updateContext('manualInterventionReason', $event->failureReason);
    $this->sagaRepository->save($state);

    // Alert (PagerDuty, Slack) + zařazení do fronty ručních zásahů.
    // Sága zůstává v Compensating, dokud ji operátor neuzavře.
}
:::
:::

Richardson (Microservices Patterns, 2018, kap. 4) dělí kroky ságy do tří skupin:

- **Compensatable transactions** – kroky před pivotem; každý má definovanou
  kompenzaci (`ChargeCustomer` ↔ `RefundCustomer`).
- **Pivot transaction** – bod rozhodnutí. Jakmile commitne, sága už necouvá
  a poběží dopředu až do konce. V našem procesu je pivotem rezervace skladu.
- **Retriable transactions** – kroky po pivotu (`CreateShipment`,
  `ShipOrder`). Kompenzaci nemají, nesmí selhat z doménových důvodů
  a opakují se až do úspěchu.

Kompenzace samy patří do třetí kategorie. `RefundCustomer` nemá legitimní
doménový důvod selhat – peníze, které systém strhl, musí umět vrátit. Selhání
je vždy technické: nedostupná platební brána, timeout, chyba sítě. Odpovídá
tomu strategie: retry s exponenciálním backoffem (`retry_strategy` ze
[sekce 7](#messenger-implementace)), po vyčerpání pokusů zpráva končí ve
failure transportu, systém odešle alert a objednávka putuje do fronty ručních
zásahů. Ságu visící v `Compensating` zachytí i detekce zaseklých ság ze
[sekce 11](#monitoring).

Podrobnější informace o Dead Letter Queue, retry strategiích a zpracování chyb v Messenger
najdete v kapitole [CQRS – zpracování chyb](/cqrs#error-handling).

## 14.10 Paralelní kroky {#paralelni-kroky}

Dosud jsme kroky řadili sériově, jeden po druhém. Některé
kroky na sobě nezávisí a mohou běžet **současně**. Například po úspěšné
platbě chceme zároveň **rezervovat zboží na skladě** a
**vygenerovat fakturu**. Obě operace jsou nezávislé. Výsledek jedné
neovlivňuje druhou. Paralelním zpracováním zkrátíme celkovou dobu trvání ságy.

Princip: sága dispatchuje oba příkazy současně a přejde do stavu
`AwaitingStockAndInvoice`. V kontextu si uchovává dva příznaky
(`stockReserved` a `invoiceCreated`). Teprve když oba dorazí
jako splněné, sága pokračuje dalším krokem, vytvořením zásilky. Tomuto vzoru se říká
**synchronizační bariéra** (synchronization barrier). Stav `AwaitingStockAndInvoice`
v enumu `OrderSagaStatus` ze [sekce 14.05](#orchestrace) zatím chybí. Paralelní varianta
vyžaduje doplnit nový case.

:::callout{type="pattern"}
### PHP: Paralelní zpracování kroků se synchronizační bariérou {#parallel-steps-heading}

:::code{language="php" filename="snippet.php"}
private function onPaymentSucceeded(PaymentSucceeded $event): void
{
    $state = $this->sagaRepository->findByCorrelationId($event->orderId);

    if ($state === null) {
        return;
    }

    $state->transitionTo(OrderSagaStatus::AwaitingStockAndInvoice);
    $state->updateContext('stockReserved', false);
    $state->updateContext('invoiceCreated', false);
    $this->sagaRepository->save($state);

    $this->commandBus->dispatch(new ReserveStock(orderId: $event->orderId));
    $this->commandBus->dispatch(new CreateInvoice(orderId: $event->orderId));
}

private function onStockReserved(StockReserved $event): void
{
    $state = $this->sagaRepository->findByCorrelationId($event->orderId);

    if ($state === null) {
        return;
    }

    $state->updateContext('stockReserved', true);
    $state->updateContext('completedSteps', [
        ...$state->context()['completedSteps'] ?? [],
        'stock_reserved',
    ]);
    $this->sagaRepository->save($state);

    $this->proceedIfParallelStepsCompleted($state);
}

private function onInvoiceCreated(InvoiceCreated $event): void
{
    $state = $this->sagaRepository->findByCorrelationId($event->orderId);

    if ($state === null) {
        return;
    }

    $state->updateContext('invoiceCreated', true);
    $state->updateContext('completedSteps', [
        ...$state->context()['completedSteps'] ?? [],
        'invoice_created',
    ]);
    $this->sagaRepository->save($state);

    $this->proceedIfParallelStepsCompleted($state);
}

private function proceedIfParallelStepsCompleted(OrderSaga $state): void
{
    if ($state->context()['stockReserved'] && $state->context()['invoiceCreated']) {
        $state->transitionTo(OrderSagaStatus::AwaitingShipment);
        $this->sagaRepository->save($state);

        $this->commandBus->dispatch(new CreateShipment(
            orderId: $state->correlationId(),
        ));
    }
}
:::
:::

:::callout{type="warn"}
### Kompenzace paralelních kroků {#parallel-compensation-heading}

Paralelní kroky zvyšují složitost kompenzace. Pokud rezervace skladu uspěje, ale
generování faktury selže, musíte sklad uvolnit, přestože samotná rezervace proběhla
správně. Pole `completedSteps` z [předchozí sekce](#kompenzacni-strategie) zajistí,
že se kompenzuje pouze to, co skutečně proběhlo.

Časování je ale zrádnější. Druhá větev může v okamžiku kompenzace stále běžet
a kompenzace první větve jí zpod rukou vezme předpoklad, se kterým pracuje. Původní
článek o ságách tomu říká cascading rollback a rozebírá ho právě u fork/join.
Bezpečnější postup: počkat, až obě větve dorazí do bariéry, a teprve pak rozhodnout
o kompenzaci. Sága proto musí odlišit „krok selhal“ od „krok zatím neodpověděl“.
Dva booleany v kontextu na to nestačí, potřebujete tři stavy na větev.
:::

:::callout{type="note"}
### Optimistické zamykání {#optimistic-locking-parallel-heading}

Při paralelních krocích mohou dvě události (`StockReserved` a
`InvoiceCreated`) dorazit téměř současně a oba handlery se pokusí
aktualizovat stejný `OrderSaga` záznam. Bez ochrany hrozí ztráta dat
(lost update). Řešením je **optimistické zamykání**. Entita
`OrderSaga` obsahuje sloupec `version` (viz
[sekce 6](#perzistence-stavu)) a při uložení Doctrine ověří, že verze
nebyla mezitím změněna. Pokud ano, vyhodí
`OptimisticLockException` a Messenger zprávu automaticky zopakuje.
:::

## 14.11 Monitoring a observabilita {#monitoring}

Bez monitoringu se zpráva ztratí ve frontě, stav ságy zamrzne a nikdo si ničeho
nevšimne, dokud si zákazník nestěžuje. Produkční sága proto potřebuje vědět, které
instance právě běží, které se zasekly a které selhaly. Dva nástroje, které tuto
viditelnost zajišťují: korelační ID pro trasování a detekce zaseklých ság.

### Korelační ID {#korelacni-id-heading}

Každá zpráva v jedné sáze nese stejné **korelační ID**, typicky
`orderId`. Díky němu můžete v logu vyfiltrovat všechny zprávy patřící
ke konkrétní objednávce a sledovat celý průběh procesu od začátku do konce.
Více o korelačních identifikátorech najdete v
[glosáři](/glosar#term-korelacni-id).

Technicky se korelace řeší vlastním stampem (např. `CorrelationIdStamp`),
který sága připojí na envelope při dispatchi a logovací middleware ho čte
ze stampu, místo aby spoléhal na konkrétní pole zprávy. Envelope tak nese
korelaci i pro zprávy, které žádné `orderId` nemají.

### Detekce zaseklých ság {#detekce-zaseklych-heading}

I při nejlepším návrhu se stane, že zpráva se ztratí, worker spadne nebo externí služba
přestane odpovídat. Proto potřebujete **cron/scheduled command**, který
pravidelně kontroluje, zda některá sága nezůstala příliš dlouho v mezistavech:

:::callout{type="pattern"}
### Symfony Console příkaz pro detekci zaseklých ság {#check-stale-sagas-heading}

:::code{language="php" filename="src/Ordering/Infrastructure/Command/CheckStaleSagasCommand.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Command;

use App\Ordering\Application\Saga\OrderSagaRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:saga:check-stale', description: 'Najde ságy zaseklé déle než 30 minut')]
final class CheckStaleSagasCommand extends Command
{
    public function __construct(
        private readonly OrderSagaRepository $sagaRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $threshold = new \DateTimeImmutable('-30 minutes');
        $staleSagas = $this->sagaRepository->findStale($threshold);

        if (count($staleSagas) === 0) {
            $io->success('Žádné zaseklé ságy.');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('Nalezeno %d zaseklých ság:', count($staleSagas)));

        foreach ($staleSagas as $saga) {
            $io->writeln(sprintf(
                '  [%s] %s - stav: %s, poslední aktivita: %s',
                $saga->correlationId(),
                'order_process',
                $saga->status()->value,
                $saga->updatedAt()->format('Y-m-d H:i:s'),
            ));
        }

        return Command::FAILURE;
    }
}
:::
:::

:::callout{type="note"}
### Integrace s alertingem {#alerting-heading}

V produkci se detekce zaseklých ság napojuje na alerting. **Prometheus** sbírá
metriky (počet aktivních ság, průměrná doba dokončení), **Grafana** kreslí
dashboardy a **PagerDuty** nebo obdobný nástroj eskaluje kritické situace. Příkaz `app:saga:check-stale` může běžet jako
Kubernetes CronJob nebo Symfony Scheduler task.
:::

Podrobnosti o implementaci middleware v Symfony Messenger najdete v kapitole
[CQRS – sekce middleware](/cqrs#middleware).

## 14.12 Testování ság {#testovani}

Chyba v přechodové logice nebo v kompenzacích se projeví až v produkci:
stržená platba bez doručeného zboží, duplikované zásilky a podobně. Těžiště testů
ságy leží v unit testech stavového automatu; integrační testy s Doctrine a testování
asynchronních toků přes Messenger rozebírá kapitola
[Testování DDD aplikací](/testovani-ddd).

### Unit testy stavového automatu {#unit-testy-heading}

Nejdůležitější úroveň: testujeme samotný Process Manager izolovaně od infrastruktury.
Místo skutečného message busu použijeme spy implementaci, která zaznamenává dispatchované
příkazy, a místo databáze in-memory repozitář:

:::callout{type="pattern"}
### PHPUnit test ságy {#saga-unit-test-heading}

:::code{language="php" filename="tests/Ordering/Application/Saga/OrderProcessManagerTest.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Application\Saga;

use App\Ordering\Application\Saga\OrderProcessManager;
use App\Ordering\Application\Saga\OrderSagaStatus;
use App\Ordering\Application\Saga\OrderSagaRepository;
use App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Payment\Application\Command\ChargeCustomer;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\PaymentSucceeded;
use App\Warehouse\Application\Command\ReserveStock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use App\Ordering\Application\Command\MarkOrderPaid;
use App\Ordering\Application\Command\CheckSagaTimeout;

final class OrderProcessManagerTest extends TestCase
{
    // Identifikátory jsou UUID – OrderId a CustomerId jinou hodnotu nepřijmou.
    private const ORDER_ID    = '01a07424-28ff-7c31-9d40-6f2a1c8e5b03';
    private const CUSTOMER_ID = '01a07424-28ff-7c31-9d40-6f2a1c8e5b04';

    private MessageBusInterface $commandBus;
    private OrderSagaRepository $repository;
    private OrderProcessManager $saga;
    /** @var list<object> */
    private array $dispatchedCommands = [];

    protected function setUp(): void
    {
        $this->dispatchedCommands = [];

        // Pozor: promoted property nelze deklarovat by-reference,
        // referenci je nutné navázat v těle konstruktoru.
        $this->commandBus = new class($this->dispatchedCommands) implements MessageBusInterface {
            /** @var list<object> */
            private array $commands;

            /** @param list<object> $commands */
            public function __construct(array &$commands)
            {
                $this->commands = &$commands;
            }

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->commands[] = $message;
                return new Envelope($message);
            }
        };

        $this->repository = new InMemoryOrderSagaRepository();
        $this->saga = new OrderProcessManager(
            $this->commandBus,
            $this->repository,
            $this->createStub(ManagerRegistry::class),
        );
    }

    /**
     * Sága vedle příkazů rozesílá i vlastní odložené hlídače. Testy zajímají
     * jen kroky procesu, jinak by se počty rozešly při každém přidaném timeoutu.
     *
     * @return list<object>
     */
    private function steps(): array
    {
        return array_values(array_filter(
            $this->dispatchedCommands,
            static fn (object $c): bool => !$c instanceof CheckSagaTimeout,
        ));
    }

    /** Zkratka – integrační událost má pět polí, testy z nich mění dvě. */
    private function orderPlaced(
        string $orderId = self::ORDER_ID,
        string $customerId = self::CUSTOMER_ID,
        int $totalAmountCents = 10000,
    ): OrderPlacedIntegrationEvent {
        return new OrderPlacedIntegrationEvent(
            eventId: Uuid::v7(),
            orderId: $orderId,
            customerId: $customerId,
            items: [],
            totalAmountCents: $totalAmountCents,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public function testOrderPlacedInitiatesPayment(): void
    {
        ($this->saga)($this->orderPlaced(
            orderId: self::ORDER_ID,
            customerId: self::CUSTOMER_ID,
            totalAmountCents: 10000,
        ));

        self::assertCount(1, $this->steps());
        self::assertInstanceOf(ChargeCustomer::class, $this->steps()[0]);
        self::assertSame(self::ORDER_ID, $this->steps()[0]->orderId);

        $state = $this->repository->findByCorrelationId(self::ORDER_ID);
        self::assertNotNull($state);
        self::assertSame(OrderSagaStatus::AwaitingPayment, $state->status());
    }

    public function testPaymentSucceededReservesStock(): void
    {
        ($this->saga)($this->orderPlaced());
        $this->dispatchedCommands = [];

        ($this->saga)(new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID));

        // onPaymentSucceeded dispatchne dva kroky: MarkOrderPaid a ReserveStock.
        // Timeout, který k nim sága přidá, testy počítat nechtějí.
        self::assertCount(2, $this->steps());
        self::assertInstanceOf(MarkOrderPaid::class, $this->steps()[0]);
        self::assertInstanceOf(ReserveStock::class, $this->steps()[1]);

        $state = $this->repository->findByCorrelationId(self::ORDER_ID);
        self::assertNotNull($state);
        self::assertSame(OrderSagaStatus::AwaitingStockReservation, $state->status());
    }

    public function testPaymentFailedCancelsOrder(): void
    {
        ($this->saga)($this->orderPlaced());
        $this->dispatchedCommands = [];

        ($this->saga)(new PaymentFailed(
            eventId: Uuid::v7(),
            orderId: self::ORDER_ID,
            failureReason: 'Insufficient funds',
        ));

        $state = $this->repository->findByCorrelationId(self::ORDER_ID);
        self::assertNotNull($state);
        self::assertSame(OrderSagaStatus::Failed, $state->status());
    }

    public function testLateEventDoesNotReviveFinishedSaga(): void
    {
        ($this->saga)($this->orderPlaced());
        ($this->saga)(new PaymentFailed(
            eventId: Uuid::v7(),
            orderId: self::ORDER_ID,
            failureReason: 'Insufficient funds',
        ));
        $this->dispatchedCommands = [];

        // Platba dorazí až po timeoutu, kdy je sága ve Failed. Bez guardu
        // by ságu přepnula zpět a poslala MarkOrderPaid na zrušenou objednávku.
        ($this->saga)(new PaymentSucceeded(eventId: Uuid::v7(), orderId: self::ORDER_ID));

        self::assertSame([], $this->steps());
        $state = $this->repository->findByCorrelationId(self::ORDER_ID);
        self::assertNotNull($state);
        self::assertSame(OrderSagaStatus::Failed, $state->status());
    }
}
:::
:::

:::callout{type="note"}
### InMemoryOrderSagaRepository {#in-memory-repo-heading}

Testovací in-memory implementace repozitáře, kterou používáme místo Doctrine:

:::code{language="php" filename="tests/Ordering/Application/Saga/InMemoryOrderSagaRepository.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Application\Saga;

use App\Ordering\Application\Saga\OrderSaga;
use App\Ordering\Application\Saga\OrderSagaRepository;

final class InMemoryOrderSagaRepository implements OrderSagaRepository
{
    /** @var array<string, OrderSaga> */
    private array $states = [];

    public function save(OrderSaga $state): void
    {
        $this->states[$state->correlationId()] = $state;
    }

    public function findByCorrelationId(string $correlationId): ?OrderSaga
    {
        return $this->states[$correlationId] ?? null;
    }

    /** @return list<OrderSaga> */
    public function findStale(\DateTimeImmutable $olderThan): array
    {
        return array_values(array_filter(
            $this->states,
            fn (OrderSaga $s): bool => !$s->isTerminated() && $s->updatedAt() < $olderThan,
        ));
    }
}
:::
:::

Další vzory pro testování doménové logiky, agregátů a event handlerů najdete v kapitole
[Testování DDD aplikací](/testovani-ddd).

:::faq{}
- question: Jaký je rozdíl mezi Ságou a Process Managerem?
  answer: 'Sága je v této knize obecný pojem pro dlouhotrvající transakci napříč více službami, rozdělenou na sérii lokálních transakcí propojených kompenzacemi. Process Manager je její orchestrovaná podoba: centralizovaná komponenta s vlastním stavem, která zasílá příkazy a reaguje na přicházející události. Obě osy (sága/Process Manager vs. choreografie/orchestrace) jsou ortogonální – sága může být choreografická i orchestrovaná, Process Manager je vždy orchestrátor. Jde ale o jednu ze tří konvencí, které v literatuře koexistují, takže u cizího zdroje je vhodné ověřit, kterou používá. Rozbor v <a href="#terminologicka-konvence">sekci Kterou konvenci kniha používá</a>.'
- question: Choreografie, nebo orchestrace – kdy zvolit co?
  answer: 'Choreografie, kde služby reagují na události publikované ostatními, se hodí pro krátké procesy se známou lineární posloupností kroků, kde je spojení mezi službami volné a globální stav není kritický. Orchestrace přes Process Manager je vhodnější tam, kde posloupnost není známá dopředu, kde se proces větví nebo kde jsou potřeba časové limity a centrální přehled o stavu běhu. Běžně citovaná hranice „dvou až tří kroků“ je heuristika, ne pravidlo. Rozhodovací kritéria v <a href="#limity-choreografie">sekci Limity choreografie</a>.'
- question: Jak implementovat kompenzační transakce v Symfony?
  answer: 'Kompenzace je samostatná operace nebo command handler, který vrací systém do stavu před selhaným krokem – například <code>CancelPayment</code> jako protějšek <code>AuthorizePayment</code>. V Messenger sáze se kompenzace spouští, když příchozí událost signalizuje selhání některého z pozdějších kroků. Kompenzační příkazy musí být idempotentní a tolerantní k situaci, že kompenzovaný krok nikdy neproběhl. Ne každou operaci lze technicky vrátit, proto se někdy kompenzuje jiným způsobem. Praktický příklad v <a href="#kompenzacni-strategie">sekci Kompenzační strategie v praxi</a>.'
- question: Jak zajistit idempotenci ságy při opakovaném doručení událostí?
  answer: 'Messenger může stejnou zprávu doručit vícekrát, ať už při selhání workera, nebo při
    přebalení na retry queue. Handler proto musí opakované zpracování bezpečně ignorovat. Standardní řešení jsou dvě: jedinečný identifikátor zprávy uložený do tabulky zpracovaných ID, nebo stavový automat ságy, který u každého kroku kontroluje, zda už není ve stavu „dokončeno“. Obě techniky brání duplicitnímu publikování příkazů i duplicitním kompenzacím. Podrobný rozbor v <a href="#messenger-implementace">sekci Implementace v Symfony Messenger</a>.'
- question: Má se sága obsluhovat přes Command Bus, nebo Event Bus?
  answer: 'Obojí, s jasně rozdělenou rolí. Události na Event Busu spouštějí reakce ságy – informují, že se něco stalo, a sága na ně navazuje. Příkazy na Command Busu sága sama vydává, aby řídila další kroky. Typická smyčka má tvar: příchozí event → Process Manager → odchozí command → handler → nový event. Nikdy se nezaměňuje: event nic nepřikazuje, command nic neoznamuje. Viz <a href="#messenger-implementace">sekci Implementace v Symfony Messenger</a>.'
:::
