---
route: sagas
path: /sagy-a-process-managery
title: Ságy a Process Managery
page_title: "Ságy a Process Managery | DDD Symfony"
meta_description: "Ságy a Process Managery v DDD a Symfony Messenger: kompenzace, choreografie vs. orchestrace, timeouty, paralelní kroky a idempotence dlouhotrvajících procesů."
meta_keywords: "saga, process manager, kompenzační transakce, choreografie, orchestrace, CQRS, DDD, Symfony 8, Messenger, distribuované transakce"
og_type: article
published: "2026-03-26"
modified: "2026-05-03"
breadcrumb_name: Ságy a Process Managery
schema_type: TechArticle
schema_headline: "Ságy a Process Managery"
chapter_number: "14"
category: Vzory
deck: 'Ságy a Process Managery v DDD a Symfony 8 – implementace kompenzačních transakcí, choreografie i orchestrace dlouhotrvajících procesů pomocí Symfony Messenger. Včetně timeoutů, paralelních kroků a monitorování distribuovaných procesů.'
reading_time: 40
difficulty: 4
github_examples: Chapter07_Sagas
---

V předchozí kapitole jsme se zabývali
[Event Sourcingem](/event-sourcing) – vzorem persistence,
který ukládá stav jako sekvenci neměnných událostí. Ságy na tento koncept přirozeně
navazují. Event Sourcing řeší persistenci uvnitř jednoho agregátu; ságy koordinují
procesy **napříč více agregáty a Bounded Contexts**, které spolu komunikují
prostřednictvím doménových událostí.

## 14.01 Proč potřebujeme ságy? {#proc-sagy}

Jako ilustrativní příklad slouží typický e-shop: zákazník odešle objednávku a systém musí provést čtyři kroky
napříč odlišnými [Bounded Contexts](/zakladni-koncepty#bounded-contexts):

1. **Ordering** – vytvoření objednávky (agregát `Order`),
2. **Payment** – stržení platby zákazníkovi (agregát `Payment`),
3. **Warehouse** – rezervace zboží na skladě (agregát `StockReservation`),
4. **Shipping** – vytvoření zásilky (agregát `Shipment`).

Každý z těchto kontextů má vlastní agregát, vlastní databázi (nebo alespoň vlastní tabulky
se striktně oddělenou odpovědností) a vlastní invarianty, které musí chránit. Agregáty
v různých Bounded Contexts nelze měnit v jedné databázové transakci – to by porušilo
autonomii kontextů, jež je základním pilířem DDD. Jeden kontext nesmí sahat do databáze
jiného kontextu; komunikace probíhá výhradně prostřednictvím zpráv (událostí a příkazů).

Proč nelze zabalit všechny čtyři kroky do jediné databázové transakce?
Jednotlivé kontexty mohou běžet na různých serverech a používat různé databázové systémy
(PostgreSQL pro objednávky, Redis pro skladové rezervace, externí platební bránu pro platby).
Komunikují asynchronně přes frontu zpráv. Koncept atomické transakce se zde rozpadá.

:::callout{type="note"}
### Proč ne Two-Phase Commit (2PC)? {#2pc-heading}

Distribuované databáze nabízejí protokol **Two-Phase Commit** (2PC), který
koordinuje commit napříč více databázemi. V první fázi (*prepare*) se všichni
účastníci ptají, zda mohou commitnout; ve druhé fázi (*commit*) koordinátor
rozhodne o globálním commitu nebo rollbacku. Tento přístup je však pro DDD systémy
nevhodný z několika důvodů:

- **Výkonnostní overhead** – všichni účastníci drží zámky po celou dobu
  obou fází, což dramaticky snižuje propustnost systému.
- **Tight coupling** – všechny kontexty musí být dostupné současně;
  výpadek jediného účastníka zablokuje celou transakci.
- **Single point of failure** – koordinátor 2PC je kritické místo;
  jeho selhání mezi fázemi zanechá účastníky v nejistém stavu.
- **Nekompatibilita s autonomií Bounded Contexts** – 2PC vyžaduje, aby
  všechny kontexty sdílely transakční protokol, čímž porušuje princip nezávislého
  nasazení a vývoje jednotlivých kontextů.
:::

Příklad selhání: systém úspěšně strhne platbu zákazníkovi
(krok 2), ale při rezervaci skladu zjistí, že zboží není dostupné (krok 3 selže).
Zákazník přišel o peníze, zboží nemá a systém je v **nekonzistentním stavu**.
Bez mechanismu, který by tento stav detekoval a napravil, zůstane zákazník bez peněz
i bez zboží – což je v produkčním systému nepřijatelné.

Řešení tohoto problému navrhli již v roce 1987 Hector Garcia-Molina a Kenneth Salem
v článku *Sagas*. Místo jedné velké distribuované transakce rozdělili proces na sérii
**lokálních transakcí**, z nichž každá má definovanou **kompenzační
akci**. Pokud některý krok selže, systém provede kompenzační akce pro všechny
předchozí úspěšné kroky – v opačném pořadí. Tento vzor se v DDD komunitě ustálil
pod názvy **Saga** a **Process Manager**.

*Citace: Garcia-Molina, H. & Salem, K., **Sagas**, ACM SIGMOD (1987);
Vernon, V., **Implementing Domain-Driven Design** (2013), kap. 8.*

V následujících sekcích si ukážeme dva základní přístupy ke koordinaci ság –
[choreografii](#choreografie) a [orchestraci](#orchestrace) –
a jejich praktickou implementaci v Symfony 8 s využitím
[Symfony Messenger](/cqrs).

## 14.02 Kompenzační transakce {#kompenzacni-transakce}

Kompenzační transakce je **sémantické vrácení efektu předchozího kroku**.
Na rozdíl od technického rollbacku databázové transakce (který „vymaže“ změny, jako by
se nikdy nestaly) je kompenzace plnohodnotná doménová operace. Má vlastní vedlejší
efekty – notifikace, auditní záznamy, aktualizace stavů. Systém se nevrací do
původního stavu bit po bitu, ale do takového, který je z doménového pohledu
ekvivalentní situaci před provedením kompenzovaného kroku.

Pro náš e-shop scénář vypadá mapa akcí a jejich kompenzací následovně:

| Akce | Kompenzace | Poznámka |
|---|---|---|
| `ChargeCustomer` | `RefundCustomer` | Zahrnuje notifikaci zákazníka |
| `ReserveStock` | `ReleaseStock` | Uvolnění rezervace, nikoliv smazání |
| `CreateShipment` | `CancelShipment` | Pouze do okamžiku odeslání |

Kompenzace **není přesný inverzní příkaz**. Zatímco
`ChargeCustomer` strhne peníze, kompenzační `RefundCustomer` nejenže
vrátí peníze, ale navíc odešle zákazníkovi notifikaci o vrácení platby, zapíše záznam
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
        return new RefundCustomer(
            orderId: $this->orderId,
            customerId: $this->customerId,
            amountCents: $this->amountCents,
            reason: 'Saga compensation',
        );
    }
}
:::
:::

:::callout{type="warn"}
### Kompenzace musí být idempotentní {#idempotence-warning-heading}

V distribuovaném systému se může stát, že kompenzační příkaz bude doručen více než
jednou – například kvůli retry mechanismu Symfony Messenger, výpadku workeru nebo
duplikaci zprávy ve frontě. Proto musí být každá kompenzace **idempotentní**:
opakované provedení téhož kompenzačního příkazu nesmí mít žádný další efekt.
Typicky se toho dosahuje kontrolou aktuálního stavu před provedením akce
(např. `RefundCustomer` nejprve ověří, zda platba již nebyla vrácena).
:::

## 14.03 Choreografie {#choreografie}

Choreografie je přístup ke koordinaci ságy, při němž **neexistuje centrální
koordinátor**. Každý Bounded Context reaguje na události publikované jinými
kontexty a na jejich základě provádí svůj krok procesu. Žádná služba neví o celém
toku – každá zná pouze svou část a ví, na které události má reagovat.

:::diagram{fig="15.3-A" title="Choreografie vs. orchestrace - kdo koordinuje ságu" src="images/diagrams/8_sagas/choreography_vs_orchestration.svg"}
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

use App\Ordering\Domain\Event\OrderPlaced;
use App\Payment\Application\Command\ChargeCustomer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class InitiatePaymentOnOrderPlaced
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(OrderPlaced $event): void
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

#[AsMessageHandler]
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

#[AsMessageHandler]
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

:::code{language="yaml" filename="config/packages/messenger.yaml"}
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            'App\Ordering\Domain\Event\OrderPlaced': async
            'App\Payment\Domain\Event\PaymentSucceeded': async
            'App\Warehouse\Domain\Event\StockReserved': async
:::
:::

Hlavní výhodou choreografie je **volné provázání** (loose coupling) mezi
kontexty. Každý reaguje pouze na události, které mu přicházejí, a o ostatních nic
neví. Přidání nového kontextu (například Loyalty, který přidělí body za objednávku)
je triviální: stačí vytvořit nový handler naslouchající `OrderPlaced`. Pro
procesy o dvou až třech krocích vystačí s choreografií.

## 14.04 Limity choreografie {#limity-choreografie}

U procesů s pěti a více kontexty nebo s podmíněným větvením narazí choreografie na
čtyři problémy, které se v menším měřítku skrývají. V produkci se tyto problémy
projeví ve chvíli, kdy do toku přibude šestý kontext nebo větvení podle stavu.

### 1. Neviditelný tok procesu {#neviditelny-tok-heading}

Při choreografii neexistuje žádné jedno místo, kde by byl celý doménový proces popsán.
Tok procesu je rozdrobený do desítek handlerů v různých kontextech. S pěti a více
kontexty není možné vizualizovat kompletní tok. Nikdo nemá přehled o tom, které
kroky po sobě následují, kde se proces větví a jaké jsou alternativní cesty při
selhání. Vzniká fenomén, který se někdy označuje jako
**„distribuované špagety“** (*distributed spaghetti*) – analogie
ke špagetovému kódu, ale rozloženému do celého systému.

### 2. Porušení Open-Closed Principle {#ocp-heading}

Přidání nového kroku do procesu často vyžaduje úpravu existujícího kontextu. Například
pokud chceme mezi platbu a sklad vložit krok „ověření proti podvodům“ (Fraud Detection),
musíme změnit handler ve Warehouse. Místo události `PaymentSucceeded`
musí naslouchat na `FraudCheckPassed`. Tím porušujeme
**Open-Closed Principle** – stávající kód kontextu Warehouse je nutné
upravit, aby fungoval s novým krokem. Při orchestraci by stačilo přidat krok do
centrálního Process Manageru bez zásahu do existujících kontextů.

### 3. Obtížná diagnostika selhání {#diagnostika-heading}

Když se proces „zasekne“, kde hledat příčinu? Každý kontext zná pouze svůj krok –
neví, jaký je celkový stav procesu. Operátor musí ručně procházet logy všech
kontextů, korelovat události podle `orderId` a rekonstruovat, kde přesně
proces selhal. Neexistuje centrální dashboard, který by zobrazil:
„Objednávka #42 – platba OK, sklad SELHÁNÍ, zásilka NESPUŠTĚNA.“
V produkčním prostředí s tisíci objednávkami denně je tento přístup neúnosný.

### 4. Chybějící timeout management {#timeout-heading}

Kdo detekuje, že proces „visí“? Pokud kontext Payment strhne platbu, ale Warehouse
nikdy nezareaguje (handler spadl, zpráva se ztratila ve frontě), kdo zjistí, že
proces stojí? Každý kontext zná pouze svůj krok a nemá přehled o časových limitech
celého procesu. V choreografii neexistuje přirozené místo pro definici globálního
timeoutu – nikdo nehlídá, že celý proces od `OrderPlaced` po
`ShipmentCreated` musí trvat maximálně 30 minut.

Všechny tyto problémy poukazují na jednu věc: u komplexních procesů potřebujeme
**centrální místo**, které zná celý tok, řídí kroky, detekuje selhání
a spouští kompenzace. Tímto centrálním místem je [orchestrátor
– Process Manager](#orchestrace).

:::callout{type="note"}
### Choreografie má své místo {#choreografie-stale-validni-heading}

Choreografie je stále legitimním řešením pro jednoduché procesy se dvěma až třemi
kroky, kde je tok lineární a selhání řeší jediná kompenzace. Pokud proces zahrnuje
pouze „vytvoření objednávky → stržení platby → potvrzení“, choreografie ušetří
kód oproti plnohodnotnému Process Manageru. Orchestrace má smysl až ve chvíli,
kdy se objeví výše popsané problémy.
:::

## 14.05 Orchestrace – Process Manager {#orchestrace}

V orchestraci celý doménový proces řídí jediná třída – tzv. **Process Manager**.
Funguje jako stavový automat s definovanými stavy a přechody. V našem e-shop scénáři
tuto roli plní `OrderProcessManager`. Přijímá události ze všech kontextů (Payment,
Warehouse, Shipping) a na jejich základě rozhoduje, jaký příkaz vydat jako další krok.
Tok není rozdrobený do desítek handlerů – celá logika procesu se soustředí do jedné
třídy. Na jednom místě je viditelný kompletní tok od `OrderPlaced` po `ConfirmOrder`.

Následující diagram zobrazuje stavový automat procesu objednávky. Zelené šipky značí úspěšné
přechody, červené selhání a oranžová cesta vede přes kompenzaci:

:::diagram{fig="15.1-A" title="Stavový automat OrderProcessManager" src="images/diagrams/8_sagas/saga_state_machine.svg"}
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
}
:::
:::

:::callout{type="pattern"}
### PHP: OrderProcessManager – jádro orchestrace {#process-manager-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/OrderProcessManager.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

use App\Ordering\Domain\Event\OrderPlaced;
use App\Payment\Domain\Event\PaymentSucceeded;
use App\Payment\Domain\Event\PaymentFailed;
use App\Warehouse\Domain\Event\StockReserved;
use App\Warehouse\Domain\Event\StockReservationFailed;
use App\Shipping\Domain\Event\ShipmentCreated;
use App\Payment\Application\Command\ChargeCustomer;
use App\Payment\Application\Command\RefundCustomer;
use App\Warehouse\Application\Command\ReserveStock;
use App\Shipping\Application\Command\CreateShipment;
use App\Ordering\Application\Command\ConfirmOrder;
use App\Ordering\Application\Command\CancelOrder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Process Manager koordinující objednávkový proces napříč kontexty:
 * Ordering → Payment → Warehouse → Shipping → Ordering (potvrzení)
 */
#[AsMessageHandler]
final class OrderProcessManager
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly SagaStateRepositoryInterface $sagaStateRepository,
    ) {}

    public function __invoke(
        OrderPlaced|PaymentSucceeded|PaymentFailed|StockReserved|StockReservationFailed|ShipmentCreated $event,
    ): void {
        match (true) {
            $event instanceof OrderPlaced => $this->onOrderPlaced($event),
            $event instanceof PaymentSucceeded => $this->onPaymentSucceeded($event),
            $event instanceof PaymentFailed => $this->onPaymentFailed($event),
            $event instanceof StockReserved => $this->onStockReserved($event),
            $event instanceof StockReservationFailed => $this->onStockReservationFailed($event),
            $event instanceof ShipmentCreated => $this->onShipmentCreated($event),
        };
    }

    private function onOrderPlaced(OrderPlaced $event): void
    {
        $state = SagaState::start(
            sagaType: 'order_process',
            correlationId: $event->orderId,
            status: OrderSagaStatus::AwaitingPayment,
            context: [
                'customerId' => $event->customerId,
                'amountCents' => $event->totalAmountCents,
                'completedSteps' => [],
            ],
        );
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new ChargeCustomer(
            orderId: $event->orderId,
            customerId: $event->customerId,
            amountCents: $event->totalAmountCents,
        ));
    }

    private function onPaymentSucceeded(PaymentSucceeded $event): void
    {
        $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
        $state->transitionTo(OrderSagaStatus::AwaitingStockReservation);
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'payment_charged',
        ]);
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new ReserveStock(orderId: $event->orderId));
    }

    private function onPaymentFailed(PaymentFailed $event): void
    {
        $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
        $state->transitionTo(OrderSagaStatus::Failed);
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new CancelOrder(
            orderId: $event->orderId,
            reason: 'Platba selhala: ' . $event->failureReason,
        ));
    }

    private function onStockReserved(StockReserved $event): void
    {
        $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
        $state->transitionTo(OrderSagaStatus::AwaitingShipment);
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'stock_reserved',
        ]);
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new CreateShipment(orderId: $event->orderId));
    }

    private function onStockReservationFailed(StockReservationFailed $event): void
    {
        $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaStateRepository->save($state);

        // Kompenzace: vrátit platbu
        $this->commandBus->dispatch(new RefundCustomer(
            orderId: $event->orderId,
            customerId: $state->context()['customerId'],
            amountCents: $state->context()['amountCents'],
            reason: 'Zboží není skladem',
        ));

        $this->commandBus->dispatch(new CancelOrder(
            orderId: $event->orderId,
            reason: 'Zboží není skladem',
        ));

        $state->transitionTo(OrderSagaStatus::Failed);
        $this->sagaStateRepository->save($state);
    }

    private function onShipmentCreated(ShipmentCreated $event): void
    {
        $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
        $state->transitionTo(OrderSagaStatus::Completed);
        $state->updateContext('completedSteps', [
            ...$state->context()['completedSteps'],
            'shipment_created',
        ]);
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new ConfirmOrder(orderId: $event->orderId));
    }
}
:::
:::

Orchestrace přináší oproti choreografii několik výhod: celý doménový proces
je popsán na **jediném místě**, takže vývojář okamžitě vidí kompletní tok
od objednávky po potvrzení. Při debugování stačí zkontrolovat stav
ságy v databázi a hned je jasné, ve kterém kroku proces stojí. Rozšíření procesu
o nový krok (například Fraud Detection mezi platbu a sklad) znamená doplnit jednu
metodu do Process Manageru a jeden nový stav do enumu. Existující kontexty se nemění.

:::callout{type="note"}
### Každá metoda = jeden krok stavového automatu {#step-method-heading}

Každá privátní metoda v `OrderProcessManager` reprezentuje jeden krok
stavového automatu. Přidání nového kroku do procesu znamená přidání jedné metody
a jedné události – stávající metody ani stávající kontexty se nemění. Tím je splněn
**Open-Closed Principle**: Process Manager je otevřený pro rozšíření
(nové kroky), ale uzavřený pro modifikaci (existující kroky zůstávají beze změny).
:::

## 14.06 Perzistence stavu ságy {#perzistence-stavu}

Process Manager potřebuje **perzistentní úložiště stavu**, aby přežil
restart workeru, nové nasazení aplikace i horizontální škálování na více instancí.
Bez perzistence by pád workeru mezi kroky `OrderPlaced` a
`PaymentSucceeded` znamenal ztrátu informace o tom, kde se proces nachází.
Sága by zůstala navždy „viset“ bez možnosti dokončení nebo kompenzace. Stav ságy proto
ukládáme do databáze jako Doctrine entitu.

:::callout{type="pattern"}
### PHP: SagaState – Doctrine entita {#saga-state-entity-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/SagaState.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'saga_state')]
#[ORM\Index(fields: ['correlationId'], name: 'idx_saga_correlation')]
#[ORM\Index(fields: ['status'], name: 'idx_saga_status')]
class SagaState
{
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
### PHP: Rozhraní SagaStateRepositoryInterface {#saga-state-repo-interface-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/SagaStateRepositoryInterface.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

/**
 * Rozhraní repozitáře stavu ságy - umožňuje snadnou záměnu
 * implementace (Doctrine v produkci, in-memory v testech).
 */
interface SagaStateRepositoryInterface
{
    public function save(SagaState $state): void;

    public function findByCorrelationId(string $correlationId): SagaState;

    /** @return list<SagaState> */
    public function findStale(\DateTimeImmutable $olderThan): array;
}
:::
:::

:::callout{type="pattern"}
### PHP: Doctrine implementace SagaStateRepository {#saga-state-repository-heading}

:::code{language="php" filename="src/Ordering/Application/Saga/SagaStateRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

use Doctrine\ORM\EntityManagerInterface;

final readonly class SagaStateRepository implements SagaStateRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function save(SagaState $state): void
    {
        $this->em->persist($state);
        $this->em->flush();
    }

    public function findByCorrelationId(string $correlationId): SagaState
    {
        $state = $this->em->getRepository(SagaState::class)
            ->findOneBy(['correlationId' => $correlationId]);

        if ($state === null) {
            throw new \RuntimeException(
                sprintf('Saga state not found for correlation ID "%s"', $correlationId),
            );
        }

        return $state;
    }

    /** @return list<SagaState> */
    public function findStale(\DateTimeImmutable $olderThan): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(SagaState::class, 's')
            ->where('s.completedAt IS NULL')
            ->andWhere('s.updatedAt < :threshold')
            ->setParameter('threshold', $olderThan)
            ->getQuery()
            ->getResult();
    }
}
:::
:::

Díky perzistenci stavu je obnova po selhání přímočará. Worker spadne mezi zpracováním
`OrderPlaced` a příchodem `PaymentSucceeded`. Po restartu Messenger znovu doručí
nedokončenou událost a Process Manager načte stav ságy z databáze. Okamžitě ví, že
sága čeká na platbu (`AwaitingPayment`), a pokračuje od správného kroku. Metoda
`findStale()` v repository navíc umožňuje periodicky detekovat zaseklé ságy, které
se déle než stanovený práh neposunuly kupředu, a spustit pro ně kompenzaci nebo
eskalaci.

:::callout{type="note"}
### Optimistické zamykání v produkci {#optimistic-locking-heading}

V produkčním prostředí s více workery má smysl přidat sloupec pro optimistické
zamykání (`#[ORM\Version]`). Bez něj by dva workery zpracovávající události pro
stejnou objednávku mohly současně načíst stejný stav ságy a přepsat si navzájem
změny. Optimistický zámek zajistí, že druhý worker dostane výjimku
`OptimisticLockException` a Messenger zprávu automaticky zopakuje.
:::

### Multi-worker Process Manager – co se rozpadne {#multi-worker-heading}

Optimistic lock řeší konflikt na *jedné* instanci ságy. V produkci se stane
něco složitějšího: stejná zpráva (např. `PaymentCompleted` z téže objednávky)
dorazí do více worker instancí současně (Messenger `numprocs > 1`),
nebo *různé* eventy z téže ságy dorazí ve špatném pořadí (Kafka partition
balancing, RabbitMQ multiple consumers). Důsledky:

- **Race na vznik ságy.** První `OrderPlaced` pro stejné `orderId`
  dorazí do dvou workerů současně. Oba volají `findOrCreateSaga(orderId)`,
  oba vidí prázdný stav, oba vytvoří `OrderSaga`. UNIQUE constraint na
  `order_id` jednoho z nich zabije, druhý zůstane. Bez constraint → dvě paralelní
  ságy téhož orderu, koliduje to o stav.
- **Out-of-order events.** `PaymentCompleted` dorazí dřív než
  `OrderConfirmed`, sága zatím není ve stavu „čeká na platbu“. Process Manager
  netuší, co s ní – buď event zahodí (bug v doméně), nebo ho zařadí do
  *pending* fronty pro pozdější zpracování (komplexní stavový automat).
- **Kompenzační závody.** Sága rozhodne `Compensate`, vyšle `RefundPayment`,
  a *zároveň* dorazí pomalá `PaymentCompleted` z jiného workeru. Druhá
  zpráva může resetovat stav ságy zpět na `Confirmed`, ale `Refund` už
  běží – peníze odešly i přijdou.

Standardní obrana proti všem třem:

:::callout{type="pattern"}
### Vzor: idempotentní state transitions + UNIQUE constraint {#idempotent-saga-transitions-heading}

:::code{language="php" filename="src/OrderSaga/Domain/OrderSaga.php"}
<?php

declare(strict_types=1);

namespace App\OrderSaga\Domain;

use App\Shared\Domain\AggregateRoot;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_sagas', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_order_id', columns: ['order_id']),
])]
final class OrderSaga extends AggregateRoot
{
    #[ORM\Column(enumType: OrderSagaStatus::class)]
    private OrderSagaStatus $status;

    #[ORM\Column(type: 'json')]
    private array $processedEventIds = [];

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function applyPaymentCompleted(string $eventId): void
    {
        // 1) Idempotence: stejný event už zpracován? Skip.
        if (in_array($eventId, $this->processedEventIds, true)) {
            return;
        }

        // 2) Stav-stroje guard: smí ten přechod nastat?
        if (!$this->status->canTransitionTo(OrderSagaStatus::Paid)) {
            // Out-of-order: událost dorazila ve stavu, kde ji nečekáme.
            // Buď: zaloguj a zahoď (idempotentní), nebo zařaď do pending fronty.
            return;
        }

        $this->status = OrderSagaStatus::Paid;
        $this->processedEventIds[] = $eventId;
    }
}
:::
:::

Tři stavební prvky, které zde fungují společně:

- **UNIQUE constraint na `order_id`** zabrání duplicitnímu vzniku ságy.
  Druhý INSERT vyhodí `UniqueConstraintViolationException`, handler ji zachytí
  a načte existující ságu místo vytvoření nové.
- **`processedEventIds` v entitě** drží seznam již zpracovaných event ID.
  Stejný event přijde dvakrát → druhé volání skončí na guardu. To je „inbox
  per saga“ – paralela [Idempotent Inbox z Outbox kapitoly](/outbox-pattern#inbox).
- **State machine guard** odmítne out-of-order event. Buď ho zahodí
  (idempotentně), nebo ho zařadí do *pending events* sloupce pro pozdější aplikaci.

### Distributed deadlock mezi ságami {#distributed-deadlock-heading}

Klasický dvouagregátový deadlock přes Doctrine pessimistic lock: sága A drží
lock na `Order#1` a žádá o `Inventory#42`; sága B drží lock na `Inventory#42`
a žádá o `Order#1`. Postgres deadlock detector po cca 1 s jednu z transakcí
zabije, ale do té doby čeká celý connection pool a stojí workers.

S **eventual consistency** (Vernonovo „eventual consistency mimo hranici agregátu“,
viz [Návrh agregátu](/navrh-agregatu#transactional-consistency)) deadlock
**nemůže nastat na úrovni databáze** – každý krok ságy je samostatná transakce
na jeden agregát. Jiný typ deadlocku ale možný je: **logický cycle deadlock**
v sáze samotné.

Příklad: sága `OrderProcess` čeká na `PaymentSettled`. Sága `RefundProcess` (pro
storno) čeká na `OrderCancelled`. Pokud kompenzace způsobí storno objednávky
a zároveň zrušení refundu, obě ságy čekají na sebe a žádná nedokončí.

:::callout{type="warn"}
### Detekce logických deadlocků {#deadlock-detekce-heading}

Optimistic lock to nezachytí – obě ságy mají rozdílná ID a vlastní version
columny. Detekce vyžaduje:

- **Timeout management.** Každá sága má `maxDurationMinutes`. Sága,
  která neúspěšně čeká déle než threshold, se eskaluje na manuální zásah
  nebo automaticky kompenzuje. Implementace v sekci
  [Timeouty a deadliny](#timeouty).
- **Topologický audit.** Při návrhu kompenzací nakreslete graf závislostí
  ság: pokud existuje cyklus, máte potenciální deadlock. V produkci ho
  spustí konkrétní sekvence eventů.
- **Distributed tracing** (OpenTelemetry, Jaeger). Saga ID se propaguje jako
  `correlation_id` ve všech eventech a HTTP voláních. Zaseklé ságy
  najdete jako trace bez `END` spanu po N minutách.
:::

### Recovery z nekonzistentního stavu ságy {#saga-recovery-heading}

Sága může skončit v nekonzistentním stavu z legitimních příčin: deployment
během transakce, OOM kill v polovině compensation kroku, schema migration
změnila tvar `state` JSONu. Operátor potřebuje nástroje:

- **Read-only inspekce.** CLI command `app:saga:show <id>`, který
  vypíše current state, pending events, processed event IDs, count of
  attempts. Plus link do Grafany na sagu.
- **Manual transition.** CLI command `app:saga:force-transition <id> <to>`
  s povinným `--reason="..."`. Aktualizuje status, zapíše audit log,
  invaliduje pending events. Jen pro operátory, ne automatický recovery –
  manual transition je signál, že sága má bug nebo doména má neošetřený scénář.
- **Replay od checkpointu.** Pokud je sága idempotentní (a měla by být – viz
  výše), smazání saga state + replay všech jejích eventů z outbox/event store
  obnoví správný stav. Vyžaduje tracking správného starting eventu (typicky
  `OrderPlaced` event ID).

## 14.07 Implementace v Symfony Messenger {#messenger-implementace}

Předchozí sekce ukázaly Process Manager (orchestrátor) a perzistenci stavu ságy. Nyní
propojíme obě části s **Symfony Messenger** – asynchronním message busem,
který zajistí spolehlivé doručování událostí a příkazů mezi kontexty.
Základní konfigurace Messenger busů je popsána v kapitole
[CQRS – Symfony Messenger](/cqrs#symfony-messenger). Zde se
zaměříme na specifika pro ságy: **oddělené transporty** pro události
a příkazy a **retry strategie**, bez kterých dlouhotrvající procesy
ztrácejí zprávy při běžných výpadcích.

:::callout{type="pattern"}
### YAML: Kompletní konfigurace Messenger {#messenger-yaml-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml"}
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
            'App\Ordering\Domain\Event\OrderPlaced': async_events
            'App\Payment\Domain\Event\PaymentSucceeded': async_events
            'App\Payment\Domain\Event\PaymentFailed': async_events
            'App\Warehouse\Domain\Event\StockReserved': async_events
            'App\Warehouse\Domain\Event\StockReservationFailed': async_events
            'App\Shipping\Domain\Event\ShipmentCreated': async_events
            'App\Payment\Application\Command\ChargeCustomer': async_commands
            'App\Payment\Application\Command\RefundCustomer': async_commands
            'App\Warehouse\Application\Command\ReserveStock': async_commands
            'App\Warehouse\Application\Command\ReleaseStock': async_commands
            'App\Shipping\Application\Command\CreateShipment': async_commands
:::
:::

:::callout{type="pattern"}
### PHP: Doménová událost OrderPlaced {#order-placed-event-heading}

:::code{language="php" filename="src/Ordering/Domain/Event/OrderPlaced.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

final readonly class OrderPlaced
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $totalAmountCents,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
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
(a uvolní paměť). Pro vysokou dostupnost běží více instancí workeru – Messenger
zajistí, že každou zprávu zpracuje právě jeden worker.
:::

:::callout{type="warn"}
### Pozor na ztrátu zpráv: Outbox pattern {#outbox-pattern-heading}

Výše uvedená konfigurace předpokládá, že doménová událost se spolehlivě dostane do
message brokeru. V praxi to však není samozřejmé. Agregát uloží změny do databáze
(Doctrine flush), ale dispatch události do fronty může selhat – síťový výpadek,
pád workeru mezi flush a dispatch, restart aplikace. Výsledkem je „ztracená“ událost
a sága, která se nikdy nespustí.

Řešením je **Outbox pattern**: událost se zapíše do speciální tabulky
`outbox` v téže databázové transakci jako doménová změna. Samostatný
proces (relay/poller) pak události z outbox tabulky přenáší do message brokeru a po
úspěšném odeslání je označí jako zpracované. Tím je zaručeno, že žádná událost se
neztratí – a to i při selhání mezi kroky. Podrobněji viz sekci
[Outbox a transakční doručování událostí](/event-sourcing#outbox)
v kapitole Event Sourcing, kde je vzor popsán v kontextu event store, včetně relay workeru
a checkpoint tabulky.
:::

Podrobnější informace o asynchronním zpracování zpráv, konfiguraci transportů a retry
strategiích najdete v kapitole [CQRS – asynchronní
zpracování](/cqrs#async).

## 14.08 Timeouty a deadliny {#timeouty}

Co se stane, když událost `PaymentSucceeded` nikdy nedorazí? Síťový výpadek,
nedostupnost platební brány, ztráta zprávy ve frontě – v distribuovaném systému musíte
vždy počítat s tím, že odpověď nepřijde. Bez explicitního timeout mechanismu sága zůstane
navždy ve stavu `AwaitingPayment` a objednávka se nikdy nedokončí ani nezruší.
Proto potřebujeme **timeout check** – odložený příkaz, který po uplynutí
stanovené doby zkontroluje, zda se sága posunula dál, a pokud ne, spustí kompenzaci.

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
use App\Ordering\Application\Command\CancelOrder;
use App\Ordering\Application\Saga\OrderSagaStatus;
use App\Ordering\Application\Saga\SagaStateRepository;
use App\Payment\Application\Command\RefundCustomer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class CheckSagaTimeoutHandler
{
    public function __construct(
        private SagaStateRepositoryInterface $sagaStateRepository,
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(CheckSagaTimeout $command): void
    {
        $state = $this->sagaStateRepository->findByCorrelationId($command->orderId);

        // Saga se od posledního kroku posunula - timeout neplatí
        if ($state->status()->value !== $command->expectedStatus) {
            return;
        }

        // Zapamatovat původní stav před přechodem
        $originalStatus = OrderSagaStatus::from($command->expectedStatus);

        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaStateRepository->save($state);

        match ($originalStatus) {
            OrderSagaStatus::AwaitingPayment => $this->commandBus->dispatch(
                new CancelOrder(orderId: $command->orderId, reason: 'Payment timeout'),
            ),
            OrderSagaStatus::AwaitingStockReservation => $this->compensatePayment($state),
            default => null,
        };
    }

    private function compensatePayment(/* SagaState */ $state): void
    {
        $this->commandBus->dispatch(new RefundCustomer(
            orderId: $state->correlationId(),
            customerId: $state->context()['customerId'],
            amountCents: $state->context()['amountCents'],
            reason: 'Timeout: stock reservation not received',
        ));

        $this->commandBus->dispatch(new CancelOrder(
            orderId: $state->correlationId(),
            reason: 'Timeout waiting for stock reservation',
        ));
    }
}
:::
:::

Timeout check naplánujeme přímo v Process Manageru při zpracování události
`OrderPlaced`. Messenger nabízí `DelayStamp`, který zprávu
podrží v transportu po zadanou dobu a teprve poté ji doručí workeru:

:::callout{type="pattern"}
### PHP: Naplánování timeout checku v OrderProcessManager {#delay-stamp-heading}

:::code{language="php" filename="snippet.php"}
use Symfony\Component\Messenger\Stamp\DelayStamp;

private function onOrderPlaced(OrderPlaced $event): void
{
    // ... (vytvoření SagaState a dispatch ChargeCustomer - viz sekci 5)

    // Naplánovat timeout check za 5 minut
    $this->commandBus->dispatch(
        new CheckSagaTimeout(
            orderId: $event->orderId,
            expectedStatus: OrderSagaStatus::AwaitingPayment->value,
        ),
        [new DelayStamp(5 * 60 * 1000)], // 5 minut v milisekundách
    );
}
:::
:::

:::callout{type="note"}
### Konfigurovatelné timeouty {#configurable-timeouts-heading}

Každý krok ságy může vyžadovat jiný timeout. Platební brána typicky potřebuje
**5 minut** (zákazník zadává údaje karty). Rezervace skladu by měla
proběhnout do **30 sekund** (interní synchronní operace). Potvrzení
zásilky může trvat i **24 hodin** (závisí na externím dopravci).
Timeouty proto patří do konfigurace – ideálně jako parametry v
`services.yaml`, aby je bylo možné upravit bez změny kódu.
:::

:::callout{type="warn"}
### Požadavky na transport {#delay-stamp-warning-heading}

`DelayStamp` vyžaduje asynchronní transport, který podporuje odložené
doručování zpráv. **Doctrine transport** tuto funkcionalitu podporuje
nativně (používá sloupec `available_at`). Pokud používáte
**RabbitMQ**, potřebujete plugin
`rabbitmq-delayed-message-exchange`. Synchronní transport
(`sync://`) `DelayStamp` ignoruje a zprávu doručí okamžitě.
:::

## 14.09 Kompenzační strategie v praxi {#kompenzacni-strategie}

Když krok ságy selže, máme dvě základní strategie, jak situaci řešit. Volba závisí
na povaze chyby – je přechodná (síťový výpadek, dočasná nedostupnost služby), nebo
trvalá (nedostatek prostředků na účtu, zboží vyprodáno)?

### Forward recovery (retry) {#forward-recovery}

Při **přechodných chybách** je nejjednodušší strategií opakování – pokus
o provedení stejného kroku znovu. Symfony Messenger nabízí vestavěnou retry strategii
s exponenciálním backoffem, kterou jsme konfigurovali v
[sekci 7](#messenger-implementace). Worker automaticky opakuje selhané
zprávy podle nastavení `max_retries`, `delay` a
`multiplier`. Tento přístup je vhodný, když věříme, že problém je dočasný
a opakování může uspět.

### Backward recovery (kompenzace) {#backward-recovery}

Při **trvalých chybách** (selhání s doménovou příčinou) musíme spustit kompenzaci –
vrátit systém do konzistentního stavu provedením kompenzačních akcí v
**opačném pořadí** dokončených kroků. Kompenzace je
**sémantická**, nikoli technická. Neděláme
`DELETE FROM payments` – místo toho dispatchujeme nový doménový příkaz
`RefundCustomer`, který vytvoří novou transakci (refund). Každá kompenzační
akce je plnohodnotná doménová operace s vlastními pravidly a událostmi.

:::diagram{fig="15.9-A" title="Kompenzační flow - rollback ságy v opačném pořadí" src="images/diagrams/8_sagas/compensation_flow.svg"}
:::

:::callout{type="pattern"}
### PHP: Kompenzační logika v opačném pořadí kroků {#compensate-method-heading}

:::code{language="php" filename="snippet.php"}
/**
 * Kompenzace: spouštěna při selhání libovolného kroku.
 * Provádí kompenzační akce v opačném pořadí dokončených kroků.
 */
private function compensate(SagaState $state): void
{
    $completedSteps = $state->context()['completedSteps'] ?? [];

    foreach (array_reverse($completedSteps) as $step) {
        match ($step) {
            'shipment_created' => $this->commandBus->dispatch(
                new \App\Shipping\Application\Command\CancelShipment(
                    orderId: $state->correlationId(),
                ),
            ),
            'stock_reserved' => $this->commandBus->dispatch(
                new ReleaseStock(orderId: $state->correlationId()),
            ),
            'payment_charged' => $this->commandBus->dispatch(
                new RefundCustomer(
                    orderId: $state->correlationId(),
                    customerId: $state->context()['customerId'],
                    amountCents: $state->context()['amountCents'],
                    reason: 'Order saga compensation',
                ),
            ),
            default => null,
        };
    }

    $state->transitionTo(OrderSagaStatus::Failed);
    $this->sagaStateRepository->save($state);
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

Podrobnější informace o Dead Letter Queue, retry strategiích a zpracování chyb v Messenger
najdete v kapitole [CQRS – zpracování chyb](/cqrs#error-handling).

## 14.10 Paralelní kroky {#paralelni-kroky}

Dosud jsme uvažovali sériové provádění kroků – jeden po druhém. V praxi však některé
kroky na sobě nezávisí a mohou běžet **současně**. Například po úspěšné
platbě chceme zároveň **rezervovat zboží na skladě** a
**vygenerovat fakturu**. Obě operace jsou nezávislé – výsledek jedné
neovlivňuje druhou. Paralelním zpracováním zkrátíme celkovou dobu trvání ságy.

Princip: sága dispatchuje oba příkazy současně a přejde do stavu
`AwaitingStockAndInvoice`. V kontextu si uchovává dva příznaky
(`stockReserved` a `invoiceCreated`). Teprve když oba dorazí
jako splněné, sága pokračuje dalším krokem – vytvořením zásilky. Tomuto vzoru se říká
**synchronizační bariéra** (synchronization barrier).

:::callout{type="pattern"}
### PHP: Paralelní zpracování kroků se synchronizační bariérou {#parallel-steps-heading}

:::code{language="php" filename="snippet.php"}
private function onPaymentSucceeded(PaymentSucceeded $event): void
{
    $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
    $state->transitionTo(OrderSagaStatus::AwaitingStockAndInvoice);
    $state->updateContext('stockReserved', false);
    $state->updateContext('invoiceCreated', false);
    $this->sagaStateRepository->save($state);

    $this->commandBus->dispatch(new ReserveStock(orderId: $event->orderId));
    $this->commandBus->dispatch(new CreateInvoice(orderId: $event->orderId));
}

private function onStockReserved(StockReserved $event): void
{
    $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
    $state->updateContext('stockReserved', true);
    $state->updateContext('completedSteps', [
        ...$state->context()['completedSteps'] ?? [],
        'stock_reserved',
    ]);
    $this->sagaStateRepository->save($state);

    $this->proceedIfParallelStepsCompleted($state);
}

private function onInvoiceCreated(InvoiceCreated $event): void
{
    $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
    $state->updateContext('invoiceCreated', true);
    $state->updateContext('completedSteps', [
        ...$state->context()['completedSteps'] ?? [],
        'invoice_created',
    ]);
    $this->sagaStateRepository->save($state);

    $this->proceedIfParallelStepsCompleted($state);
}

private function proceedIfParallelStepsCompleted(SagaState $state): void
{
    if ($state->context()['stockReserved'] && $state->context()['invoiceCreated']) {
        $state->transitionTo(OrderSagaStatus::AwaitingShipment);
        $this->sagaStateRepository->save($state);

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
generování faktury selže, musíte sklad uvolnit – přestože samotná rezervace proběhla
správně. Kompenzační logika z [předchozí sekce](#kompenzacni-strategie)
toto řeší automaticky díky poli `completedSteps`: kompenzuje se pouze to,
co skutečně proběhlo.
:::

:::callout{type="note"}
### Optimistické zamykání {#optimistic-locking-parallel-heading}

Při paralelních krocích mohou dvě události (`StockReserved` a
`InvoiceCreated`) dorazit téměř současně a oba handlery se pokusí
aktualizovat stejný `SagaState` záznam. Bez ochrany hrozí ztráta dat
(lost update). Řešením je **optimistické zamykání** – entita
`SagaState` obsahuje sloupec `version` (viz
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

Každá zpráva v jedné sáze nese stejné **korelační ID** – typicky
`orderId`. Díky němu můžete v logu vyfiltrovat všechny zprávy patřící
ke konkrétní objednávce a sledovat celý průběh procesu od začátku do konce.
Více o korelačních identifikátorech najdete v
[glosáři](/glosar#term-korelacni-id).

:::callout{type="pattern"}
### SagaLoggingMiddleware {#saga-logging-middleware-heading}

Middleware pro Symfony Messenger, který loguje každou zprávu procházející ságou.
Zaregistrujte ho v `messenger.yaml` a všechny zprávy se automaticky
zaznamenají s korelačním ID:

:::code{language="php" filename="src/SharedKernel/Infrastructure/Middleware/SagaLoggingMiddleware.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class SagaLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $messageName = (new \ReflectionClass($message))->getShortName();

        $this->logger->info('Saga: zpracovávám zprávu', [
            'message' => $messageName,
            'correlationId' => $message->orderId ?? 'unknown',
        ]);

        $envelope = $stack->next()->handle($envelope, $stack);

        $handledStamp = $envelope->last(HandledStamp::class);
        $this->logger->info('Saga: zpráva zpracována', [
            'message' => $messageName,
            'handler' => $handledStamp?->getHandlerName(),
        ]);

        return $envelope;
    }
}
:::
:::

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

use App\Ordering\Application\Saga\SagaStateRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:saga:check-stale', description: 'Najde ságy zaseklé déle než 30 minut')]
final class CheckStaleSagasCommand extends Command
{
    public function __construct(
        private readonly SagaStateRepositoryInterface $sagaStateRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $threshold = new \DateTimeImmutable('-30 minutes');
        $staleSagas = $this->sagaStateRepository->findStale($threshold);

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

V produkčním prostředí se detekce zaseklých ság napojuje na alertingový systém –
**Prometheus** pro metriky (počet aktivních ság, průměrná doba dokončení),
**Grafana** pro dashboardy a **PagerDuty** nebo obdobný nástroj
pro eskalaci kritických situací. Příkaz `app:saga:check-stale` může běžet jako
Kubernetes CronJob nebo Symfony Scheduler task.
:::

Podrobnosti o implementaci middleware v Symfony Messenger najdete v kapitole
[CQRS – sekce middleware](/cqrs#middleware).

## 14.12 Testování ság {#testovani}

Ságy koordinují složité vícekrokové procesy napříč Bounded Contexts. Chyba
v přechodové logice nebo v kompenzacích se projeví až v produkci – stržená platba
bez doručeného zboží, duplikované zásilky a podobně. Testujeme na třech úrovních.

### Unit testy stavového automatu {#unit-testy-heading}

Nejdůležitější úroveň: testujeme samotný Process Manager izolovaně od infrastruktury.
Místo skutečného message busu použijeme spy implementaci, která zaznamenává dispatchované
příkazy, a místo databáze in-memory repozitář:

:::callout{type="pattern"}
### PHPUnit test ságy {#saga-unit-test-heading}

:::code{language="php" filename="src/Tests/Ordering/Application/Saga/OrderProcessManagerTest.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Application\Saga;

use App\Ordering\Application\Saga\OrderProcessManager;
use App\Ordering\Application\Saga\OrderSagaStatus;
use App\Ordering\Application\Saga\SagaState;
use App\Ordering\Application\Saga\SagaStateRepository;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Payment\Application\Command\ChargeCustomer;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\PaymentSucceeded;
use App\Warehouse\Application\Command\ReserveStock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderProcessManagerTest extends TestCase
{
    private MessageBusInterface $commandBus;
    private SagaStateRepository $repository;
    private OrderProcessManager $saga;
    /** @var list<object> */
    private array $dispatchedCommands = [];

    protected function setUp(): void
    {
        $this->dispatchedCommands = [];

        $this->commandBus = new class($this->dispatchedCommands) implements MessageBusInterface {
            /** @param list<object> $commands */
            public function __construct(private array &$commands) {}

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->commands[] = $message;
                return new Envelope($message);
            }
        };

        $this->repository = new InMemorySagaStateRepository();
        $this->saga = new OrderProcessManager($this->commandBus, $this->repository);
    }

    public function testOrderPlacedInitiatesPayment(): void
    {
        ($this->saga)(new OrderPlaced(
            orderId: 'order-1',
            customerId: 'cust-1',
            totalAmountCents: 10000,
        ));

        self::assertCount(1, $this->dispatchedCommands);
        self::assertInstanceOf(ChargeCustomer::class, $this->dispatchedCommands[0]);
        self::assertSame('order-1', $this->dispatchedCommands[0]->orderId);

        $state = $this->repository->findByCorrelationId('order-1');
        self::assertSame(OrderSagaStatus::AwaitingPayment, $state->status());
    }

    public function testPaymentSucceededReservesStock(): void
    {
        ($this->saga)(new OrderPlaced('order-1', 'cust-1', 10000));
        $this->dispatchedCommands = [];

        ($this->saga)(new PaymentSucceeded(orderId: 'order-1'));

        self::assertCount(1, $this->dispatchedCommands);
        self::assertInstanceOf(ReserveStock::class, $this->dispatchedCommands[0]);

        $state = $this->repository->findByCorrelationId('order-1');
        self::assertSame(OrderSagaStatus::AwaitingStockReservation, $state->status());
    }

    public function testPaymentFailedCancelsOrder(): void
    {
        ($this->saga)(new OrderPlaced('order-1', 'cust-1', 10000));
        $this->dispatchedCommands = [];

        ($this->saga)(new PaymentFailed(
            orderId: 'order-1',
            failureReason: 'Insufficient funds',
        ));

        $state = $this->repository->findByCorrelationId('order-1');
        self::assertSame(OrderSagaStatus::Failed, $state->status());
    }
}
:::
:::

:::callout{type="note"}
### InMemorySagaStateRepository {#in-memory-repo-heading}

Testovací in-memory implementace repozitáře, kterou používáme místo Doctrine:

:::code{language="php" filename="src/Tests/Ordering/Application/Saga/InMemorySagaStateRepository.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Application\Saga;

use App\Ordering\Application\Saga\SagaState;
use App\Ordering\Application\Saga\SagaStateRepositoryInterface;

final class InMemorySagaStateRepository implements SagaStateRepositoryInterface
{
    /** @var array<string, SagaState> */
    private array $states = [];

    public function save(SagaState $state): void
    {
        $this->states[$state->correlationId()] = $state;
    }

    public function findByCorrelationId(string $correlationId): SagaState
    {
        return $this->states[$correlationId]
            ?? throw new \RuntimeException(
                sprintf('Saga state not found for "%s"', $correlationId),
            );
    }
}
:::
:::

Další vzory pro testování doménové logiky, agregátů a event handlerů najdete v kapitole
[Testování DDD aplikací](/testovani-ddd).

:::faq{}
- question: Jaký je rozdíl mezi Ságou a Process Managerem?
  answer: 'Sága je obecný pojem pro dlouhotrvající transakci napříč více službami, rozdělenou na sérii lokálních transakcí propojených kompenzacemi. Process Manager je konkrétní implementační styl ságy – centralizovaná komponenta s vlastním stavem, která orchestruje kroky zasíláním příkazů a reaguje na přicházející události. Tyto dvě osy (Sága/Process Manager vs. choreografie/orchestrace) jsou ortogonální: sága může být choreografická i orchestrovaná, Process Manager je vždy orchestrátor. V některé literatuře (Hohpe &amp; Woolf, Richardson) se ovšem pojmem sága myslí spíš choreografická varianta, Process Managerem orchestrátor – terminologii je proto vhodné v každém zdroji ověřit. Rozbor rozdílů v <a href="#orchestrace">sekci Orchestrace – Process Manager</a>.'
- question: Choreografie, nebo orchestrace – kdy zvolit co?
  answer: 'Choreografie, kde služby reagují na události publikované ostatními, se hodí pro krátké procesy o dvou až třech krocích, kde je spojení mezi službami volné a globální stav není kritický. Orchestrace přes Process Manager je vhodnější pro složitější procesy s rozhodovací logikou, časovými limity nebo nutností centralizovaně znát stav běhu. Pro procesy přes více než tři kroky nebo s podmínkami je orchestrace obvykle udržovatelnější. Rozhodovací kritéria v <a href="#limity-choreografie">sekci Limity choreografie</a>.'
- question: Jak implementovat kompenzační transakce v Symfony?
  answer: 'Kompenzace je samostatná operace nebo command handler, který vrací systém do stavu před selhaným krokem – například <code>CancelPayment</code> jako protějšek <code>AuthorizePayment</code>. V Messenger sáze se kompenzace spouští, když příchozí událost signalizuje selhání některého z pozdějších kroků. Kompenzační příkazy musí být idempotentní a tolerantní k situaci, že kompenzovaný krok nikdy neproběhl. Ne každou operaci lze technicky vrátit, proto se někdy kompenzuje jiným způsobem. Praktický příklad v <a href="#kompenzacni-strategie">sekci Kompenzační strategie v praxi</a>.'
- question: Jak zajistit idempotenci ságy při opakovaném doručení událostí?
  answer: 'Messenger může stejnou zprávu doručit vícekrát – při selhání workera nebo přebalení na retry queue – takže handler musí opakované zpracování bezpečně ignorovat. Standardní řešení jsou dvě: jedinečný identifikátor zprávy uložený do tabulky zpracovaných ID, nebo stavový automat ságy, který u každého kroku kontroluje, zda už není ve stavu „dokončeno“. Obě techniky brání duplicitnímu publikování příkazů i duplicitním kompenzacím. Podrobný rozbor v <a href="#messenger-implementace">sekci Implementace v Symfony Messenger</a>.'
- question: Má se sága obsluhovat přes Command Bus, nebo Event Bus?
  answer: 'Obojí, s jasně rozdělenou rolí. Události na Event Busu spouštějí reakce ságy – informují, že se něco stalo, a sága na ně navazuje. Příkazy na Command Busu sága sama vydává, aby řídila další kroky. Typická smyčka má tvar: příchozí event → Process Manager → odchozí command → handler → nový event. Nikdy se nezaměňuje: event nic nepřikazuje, command nic neoznamuje. Viz <a href="#messenger-implementace">sekci Implementace v Symfony Messenger</a>.'
:::
