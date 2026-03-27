# Examples Academic Correctness — Part 2: Chybějící kód a přepisy kapitol

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Přepsat Ch07 (Ságy) na plný Process Manager, přidat CRUD "before" kód do Ch09, rozšířit Ch01 a Ch03, přidat per-chapter README a navigaci.

**Architecture:** Práce probíhá v `/home/michal/Work/ddd-symfony-examples/` na větvi `feature/academic-correctness-part1`. Ch07 je kompletní přepis. Ch09 přidává CRUD verzi vedle existující DDD verze. Ch01/Ch03 jsou rozšíření.

**Tech Stack:** PHP 8.4, Symfony 8, Doctrine ORM (XML mapping), Symfony Messenger, PHPUnit 13

**Working directory:** `/home/michal/Work/ddd-symfony-examples`

---

## Task 1: Ch07 Ságy — Domain vrstva (entity, eventy, enum)

**Files:**
- Delete: `src/Chapter07_Sagas/Application/OrderFulfillmentSaga.php` (stará synchronní verze)
- Create: `src/Chapter07_Sagas/Domain/SagaState.php`
- Create: `src/Chapter07_Sagas/Domain/SagaStep.php`
- Create: `src/Chapter07_Sagas/Domain/OrderFulfillmentSaga.php`
- Create: `src/Chapter07_Sagas/Domain/Events/SagaStarted.php`
- Create: `src/Chapter07_Sagas/Domain/Events/StockReserved.php`
- Create: `src/Chapter07_Sagas/Domain/Events/StockReservationFailed.php`
- Create: `src/Chapter07_Sagas/Domain/Events/PaymentProcessed.php`
- Create: `src/Chapter07_Sagas/Domain/Events/PaymentFailed.php`
- Create: `src/Chapter07_Sagas/Domain/Events/OrderShipped.php`
- Create: `src/Chapter07_Sagas/Domain/Events/ShipmentFailed.php`
- Create: `src/Chapter07_Sagas/Domain/Events/CompensationStarted.php`
- Create: `src/Chapter07_Sagas/Domain/Events/SagaCompleted.php`
- Create: `src/Chapter07_Sagas/Domain/Events/SagaFailed.php`

- [ ] **Step 1: Vytvořit SagaState enum**

```php
<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain;

enum SagaState: string
{
    case Started = 'started';
    case StockReserved = 'stock_reserved';
    case PaymentProcessed = 'payment_processed';
    case Shipped = 'shipped';
    case Compensating = 'compensating';
    case Failed = 'failed';
    case Completed = 'completed';
}
```

- [ ] **Step 2: Vytvořit SagaStep value object**

```php
<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain;

final readonly class SagaStep
{
    public function __construct(
        public string $name,
        public string $status,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
```

- [ ] **Step 3: Vytvořit domain eventy**

Každý event implementuje `App\Shared\Domain\DomainEvent`. Všechny mají `string $sagaId`, relevantní data a `private \DateTimeImmutable $occurredAt = new \DateTimeImmutable()`.

Vytvořit tyto eventy v `src/Chapter07_Sagas/Domain/Events/`:
- `SagaStarted` — `string $sagaId, string $orderId`
- `StockReserved` — `string $sagaId, string $orderId`
- `StockReservationFailed` — `string $sagaId, string $orderId, string $reason`
- `PaymentProcessed` — `string $sagaId, string $orderId, int $amount`
- `PaymentFailed` — `string $sagaId, string $orderId, string $reason`
- `OrderShipped` — `string $sagaId, string $orderId`
- `ShipmentFailed` — `string $sagaId, string $orderId, string $reason`
- `CompensationStarted` — `string $sagaId, string $failedStep`
- `SagaCompleted` — `string $sagaId`
- `SagaFailed` — `string $sagaId, string $reason`

- [ ] **Step 4: Vytvořit OrderFulfillmentSaga entitu**

```php
<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain;

use App\Chapter07_Sagas\Domain\Events\*;

class OrderFulfillmentSaga
{
    private string $id;
    private string $orderId;
    private SagaState $state;
    private int $amount;
    /** @var SagaStep[] */
    private array $steps = [];
    private \DateTimeImmutable $createdAt;
    /** @var \App\Shared\Domain\DomainEvent[] */
    private array $uncommittedEvents = [];

    private function __construct(string $id, string $orderId, int $amount)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->state = SagaState::Started;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function start(string $id, string $orderId, int $amount): self
    {
        $saga = new self($id, $orderId, $amount);
        $saga->addStep('start', 'ok');
        $saga->recordEvent(new SagaStarted($id, $orderId));
        return $saga;
    }

    public function handleStockReserved(): void
    {
        $this->state = SagaState::StockReserved;
        $this->addStep('reserve_stock', 'ok');
        $this->recordEvent(new StockReserved($this->id, $this->orderId));
    }

    public function handleStockFailed(string $reason): void
    {
        $this->state = SagaState::Failed;
        $this->addStep('reserve_stock', 'failed');
        $this->recordEvent(new StockReservationFailed($this->id, $this->orderId, $reason));
        $this->recordEvent(new SagaFailed($this->id, 'Stock reservation failed: ' . $reason));
    }

    public function handlePaymentProcessed(): void
    {
        $this->state = SagaState::PaymentProcessed;
        $this->addStep('process_payment', 'ok');
        $this->recordEvent(new PaymentProcessed($this->id, $this->orderId, $this->amount));
    }

    public function handlePaymentFailed(string $reason): void
    {
        $this->state = SagaState::Compensating;
        $this->addStep('process_payment', 'failed');
        $this->recordEvent(new PaymentFailed($this->id, $this->orderId, $reason));
        $this->recordEvent(new CompensationStarted($this->id, 'process_payment'));
    }

    public function handleShipmentCompleted(): void
    {
        $this->state = SagaState::Completed;
        $this->addStep('ship_order', 'ok');
        $this->recordEvent(new OrderShipped($this->id, $this->orderId));
        $this->recordEvent(new SagaCompleted($this->id));
    }

    public function handleShipmentFailed(string $reason): void
    {
        $this->state = SagaState::Compensating;
        $this->addStep('ship_order', 'failed');
        $this->recordEvent(new ShipmentFailed($this->id, $this->orderId, $reason));
        $this->recordEvent(new CompensationStarted($this->id, 'ship_order'));
    }

    public function compensateStock(): void
    {
        $this->addStep('compensate_stock', 'ok');
    }

    public function compensatePayment(): void
    {
        $this->addStep('compensate_payment', 'ok');
    }

    public function markFailed(string $reason): void
    {
        $this->state = SagaState::Failed;
        $this->recordEvent(new SagaFailed($this->id, $reason));
    }

    // Getters
    public function id(): string { return $this->id; }
    public function orderId(): string { return $this->orderId; }
    public function state(): SagaState { return $this->state; }
    public function amount(): int { return $this->amount; }
    /** @return SagaStep[] */
    public function steps(): array { return $this->steps; }

    /** @return \App\Shared\Domain\DomainEvent[] */
    public function pullUncommittedEvents(): array
    {
        $events = $this->uncommittedEvents;
        $this->uncommittedEvents = [];
        return $events;
    }

    private function addStep(string $name, string $status): void
    {
        $this->steps[] = new SagaStep($name, $status);
    }

    private function recordEvent(\App\Shared\Domain\DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
    }
}
```

- [ ] **Step 5: Smazat starou OrderFulfillmentSaga**

Smazat `src/Chapter07_Sagas/Application/OrderFulfillmentSaga.php`.

- [ ] **Step 6: Spustit testy**

Existující Ch07 test bude selhat (závisí na staré třídě). To je OK — opravíme v Task 3.

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit --exclude-group ch07 2>&1 || true
```

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: Ch07 nová doménová vrstva pro Process Manager (sága entita, eventy, stavy)"
```

---

## Task 2: Ch07 Ságy — Application handlers, persistence, config

**Files:**
- Create: `src/Chapter07_Sagas/Application/Command/StartSagaCommand.php`
- Create: `src/Chapter07_Sagas/Application/Command/StartSagaHandler.php`
- Create: `src/Chapter07_Sagas/Application/Command/ReserveStockCommand.php`
- Create: `src/Chapter07_Sagas/Application/Command/ReserveStockHandler.php`
- Create: `src/Chapter07_Sagas/Application/Command/ProcessPaymentCommand.php`
- Create: `src/Chapter07_Sagas/Application/Command/ProcessPaymentHandler.php`
- Create: `src/Chapter07_Sagas/Application/Command/ShipOrderCommand.php`
- Create: `src/Chapter07_Sagas/Application/Command/ShipOrderHandler.php`
- Create: `src/Chapter07_Sagas/Application/Command/CompensateCommand.php`
- Create: `src/Chapter07_Sagas/Application/Command/CompensateHandler.php`
- Create: `src/Chapter07_Sagas/Infrastructure/InMemorySagaRepository.php`
- Create: `src/Chapter07_Sagas/Application/SagaOrchestrator.php`
- Modify: `config/packages/messenger.yaml`

- [ ] **Step 1: Vytvořit command DTOs**

Všechny v `src/Chapter07_Sagas/Application/Command/`:

`StartSagaCommand.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

final readonly class StartSagaCommand
{
    public function __construct(
        public string $orderId,
        public int $amount,
        public bool $stockAvailable = true,
        public bool $paymentSuccess = true,
        public bool $shipmentSuccess = true,
    ) {}
}
```

`ReserveStockCommand.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

final readonly class ReserveStockCommand
{
    public function __construct(
        public string $sagaId,
        public string $orderId,
        public bool $shouldSucceed,
    ) {}
}
```

`ProcessPaymentCommand.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

final readonly class ProcessPaymentCommand
{
    public function __construct(
        public string $sagaId,
        public string $orderId,
        public int $amount,
        public bool $shouldSucceed,
    ) {}
}
```

`ShipOrderCommand.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

final readonly class ShipOrderCommand
{
    public function __construct(
        public string $sagaId,
        public string $orderId,
        public bool $shouldSucceed,
    ) {}
}
```

`CompensateCommand.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

final readonly class CompensateCommand
{
    public function __construct(
        public string $sagaId,
        public string $failedStep,
    ) {}
}
```

- [ ] **Step 2: Vytvořit InMemorySagaRepository**

```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Infrastructure;

use App\Chapter07_Sagas\Domain\OrderFulfillmentSaga;

final class InMemorySagaRepository
{
    /** @var array<string, OrderFulfillmentSaga> */
    private array $sagas = [];

    public function save(OrderFulfillmentSaga $saga): void
    {
        $this->sagas[$saga->id()] = $saga;
    }

    public function findById(string $id): ?OrderFulfillmentSaga
    {
        return $this->sagas[$id] ?? null;
    }
}
```

- [ ] **Step 3: Vytvořit SagaOrchestrator**

Tento orchestrátor řídí flow ságy — dispatche commands přes Messenger bus a reaguje na výsledky. Pro demo účely běží synchronně.

```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application;

use App\Chapter07_Sagas\Application\Command\*;
use App\Chapter07_Sagas\Domain\OrderFulfillmentSaga;
use App\Chapter07_Sagas\Domain\SagaState;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class SagaOrchestrator
{
    public function __construct(
        private readonly InMemorySagaRepository $repository,
        private readonly MessageBusInterface $bus,
    ) {}

    public function start(StartSagaCommand $command): OrderFulfillmentSaga
    {
        $sagaId = Uuid::v4()->toRfc4122();
        $saga = OrderFulfillmentSaga::start($sagaId, $command->orderId, $command->amount);
        $this->repository->save($saga);

        // Step 1: Reserve stock
        $this->bus->dispatch(new ReserveStockCommand($sagaId, $command->orderId, $command->stockAvailable));
        $saga = $this->repository->findById($sagaId);

        if ($saga->state() === SagaState::Failed) {
            return $saga;
        }

        // Step 2: Process payment
        $this->bus->dispatch(new ProcessPaymentCommand($sagaId, $command->orderId, $command->amount, $command->paymentSuccess));
        $saga = $this->repository->findById($sagaId);

        if ($saga->state() === SagaState::Compensating) {
            $this->bus->dispatch(new CompensateCommand($sagaId, 'payment'));
            return $this->repository->findById($sagaId);
        }

        // Step 3: Ship order
        $this->bus->dispatch(new ShipOrderCommand($sagaId, $command->orderId, $command->shipmentSuccess));
        $saga = $this->repository->findById($sagaId);

        if ($saga->state() === SagaState::Compensating) {
            $this->bus->dispatch(new CompensateCommand($sagaId, 'shipment'));
            return $this->repository->findById($sagaId);
        }

        return $saga;
    }
}
```

- [ ] **Step 4: Vytvořit Messenger handlery**

`ReserveStockHandler.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ReserveStockHandler
{
    public function __construct(private readonly InMemorySagaRepository $repository) {}

    public function __invoke(ReserveStockCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);
        if ($command->shouldSucceed) {
            $saga->handleStockReserved();
        } else {
            $saga->handleStockFailed('Nedostatek skladových zásob');
        }
        $this->repository->save($saga);
    }
}
```

`ProcessPaymentHandler.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessPaymentHandler
{
    public function __construct(private readonly InMemorySagaRepository $repository) {}

    public function __invoke(ProcessPaymentCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);
        if ($command->shouldSucceed) {
            $saga->handlePaymentProcessed();
        } else {
            $saga->handlePaymentFailed('Platba zamítnuta bankou');
        }
        $this->repository->save($saga);
    }
}
```

`ShipOrderHandler.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ShipOrderHandler
{
    public function __construct(private readonly InMemorySagaRepository $repository) {}

    public function __invoke(ShipOrderCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);
        if ($command->shouldSucceed) {
            $saga->handleShipmentCompleted();
        } else {
            $saga->handleShipmentFailed('Dopravce nedostupný');
        }
        $this->repository->save($saga);
    }
}
```

`CompensateHandler.php`:
```php
<?php
declare(strict_types=1);
namespace App\Chapter07_Sagas\Application\Command;

use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CompensateHandler
{
    public function __construct(private readonly InMemorySagaRepository $repository) {}

    public function __invoke(CompensateCommand $command): void
    {
        $saga = $this->repository->findById($command->sagaId);

        if ($command->failedStep === 'payment') {
            // Compensate stock reservation
            $saga->compensateStock();
            $saga->markFailed('Payment failed, stock released');
        } elseif ($command->failedStep === 'shipment') {
            // Compensate payment and stock
            $saga->compensatePayment();
            $saga->compensateStock();
            $saga->markFailed('Shipment failed, payment refunded, stock released');
        }

        $this->repository->save($saga);
    }
}
```

- [ ] **Step 5: Messenger routing**

Přidat do `config/packages/messenger.yaml` routing:
```yaml
            'App\Chapter07_Sagas\Application\Command\ReserveStockCommand': sync
            'App\Chapter07_Sagas\Application\Command\ProcessPaymentCommand': sync
            'App\Chapter07_Sagas\Application\Command\ShipOrderCommand': sync
            'App\Chapter07_Sagas\Application\Command\CompensateCommand': sync
```

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: Ch07 Application vrstva — orchestrátor, Messenger handlery, kompenzace"
```

---

## Task 3: Ch07 Ságy — Controller, šablona, testy

**Files:**
- Modify: `src/Chapter07_Sagas/UI/Chapter07Controller.php`
- Modify: `templates/examples/chapter07/index.html.twig`
- Modify: `tests/Chapter07/Application/OrderFulfillmentSagaTest.php`

- [ ] **Step 1: Přepsat Controller**

```php
<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\UI;

use App\Chapter07_Sagas\Application\Command\StartSagaCommand;
use App\Chapter07_Sagas\Application\SagaOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter07Controller extends AbstractController
{
    public function __construct(
        private readonly SagaOrchestrator $orchestrator,
    ) {}

    #[Route('/examples/sagy', name: 'chapter07')]
    public function index(Request $request): Response
    {
        $saga = null;

        if ($request->isMethod('POST')) {
            $saga = $this->orchestrator->start(new StartSagaCommand(
                orderId: 'order-' . random_int(1, 999),
                amount: 59900,
                stockAvailable: $request->request->getBoolean('stock', true),
                paymentSuccess: $request->request->getBoolean('payment', true),
                shipmentSuccess: $request->request->getBoolean('shipment', true),
            ));
        }

        return $this->render('examples/chapter07/index.html.twig', [
            'saga' => $saga,
        ]);
    }
}
```

- [ ] **Step 2: Přepsat šablonu**

Nová šablona zobrazí kroky ságy, stav, a kompenzace se zvýrazněním:

```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Ságy a Process Managery{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="https://ddd-symfony.cz/sagy-a-process-managery"><strong>Ságy a Process Managery</strong></a>
    </div>
    <h1>Ukázka: Process Manager s kompenzacemi</h1>
    <p>Sága orchestruje více kroků přes Symfony Messenger. Každý krok je samostatný command handler. Při selhání se spustí kompenzace v opačném pořadí.</p>

    <form method="post" data-turbo="false" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Sklad dostupný?</label>
                <select name="stock" class="form-select">
                    <option value="1">Ano</option>
                    <option value="0">Ne (selhání v kroku 1)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Platba úspěšná?</label>
                <select name="payment" class="form-select">
                    <option value="1">Ano</option>
                    <option value="0">Ne (selhání v kroku 2 → kompenzace 1)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Expedice úspěšná?</label>
                <select name="shipment" class="form-select">
                    <option value="1">Ano</option>
                    <option value="0">Ne (selhání v kroku 3 → kompenzace 2, 1)</option>
                </select>
            </div>
        </div>
        <button class="btn btn-primary mt-3">Spustit ságu</button>
    </form>

    {% if saga %}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Sága {{ saga.id()|slice(0,8) }}…</strong>
            <span class="badge {{ saga.state().value == 'completed' ? 'bg-success' : (saga.state().value == 'failed' ? 'bg-danger' : 'bg-warning') }} ms-2">
                {{ saga.state().value }}
            </span>
        </div>
        <div class="card-body">
            <h5>Průběh kroků:</h5>
            <ol>
            {% for step in saga.steps() %}
                <li class="{{ step.status == 'ok' ? 'text-success' : 'text-danger' }}">
                    <strong>{{ step.name }}</strong>: {{ step.status }}
                    <small class="text-muted">@ {{ step.occurredAt|date('H:i:s') }}</small>
                </li>
            {% endfor %}
            </ol>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <pre class="bg-light p-3 rounded small">// Orchestrátor dispatchuje commands:
$bus->dispatch(new ReserveStockCommand(...));
$bus->dispatch(new ProcessPaymentCommand(...));
$bus->dispatch(new ShipOrderCommand(...));

// Při selhání:
$bus->dispatch(new CompensateCommand($sagaId, 'payment'));
// → CompensateHandler uvolní sklad + refunduje platbu</pre>
        </div>
        <div class="col-md-6">
            <pre class="bg-light p-3 rounded small">// Messenger routing (sync pro demo):
'ReserveStockCommand': sync
'ProcessPaymentCommand': sync
'ShipOrderCommand': sync
'CompensateCommand': sync

// V produkci by commands šly přes async
// transport (RabbitMQ, Redis, Doctrine)</pre>
        </div>
    </div>
    {% endif %}
</div>
{% endblock %}
```

- [ ] **Step 3: Přepsat test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Application;

use App\Chapter07_Sagas\Application\Command\StartSagaCommand;
use App\Chapter07_Sagas\Application\SagaOrchestrator;
use App\Chapter07_Sagas\Domain\SagaState;
use App\Chapter07_Sagas\Infrastructure\InMemorySagaRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use App\Chapter07_Sagas\Application\Command\ReserveStockHandler;
use App\Chapter07_Sagas\Application\Command\ProcessPaymentHandler;
use App\Chapter07_Sagas\Application\Command\ShipOrderHandler;
use App\Chapter07_Sagas\Application\Command\CompensateHandler;
use App\Chapter07_Sagas\Application\Command\ReserveStockCommand;
use App\Chapter07_Sagas\Application\Command\ProcessPaymentCommand;
use App\Chapter07_Sagas\Application\Command\ShipOrderCommand;
use App\Chapter07_Sagas\Application\Command\CompensateCommand;

final class OrderFulfillmentSagaTest extends TestCase
{
    private InMemorySagaRepository $repository;
    private SagaOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->repository = new InMemorySagaRepository();

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                ReserveStockCommand::class => [new ReserveStockHandler($this->repository)],
                ProcessPaymentCommand::class => [new ProcessPaymentHandler($this->repository)],
                ShipOrderCommand::class => [new ShipOrderHandler($this->repository)],
                CompensateCommand::class => [new CompensateHandler($this->repository)],
            ])),
        ]);

        $this->orchestrator = new SagaOrchestrator($this->repository, $bus);
    }

    public function testHappyPath(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand('order-1', 50000));

        $this->assertSame(SagaState::Completed, $saga->state());
        $steps = array_map(fn($s) => $s->name . ':' . $s->status, $saga->steps());
        $this->assertContains('start:ok', $steps);
        $this->assertContains('reserve_stock:ok', $steps);
        $this->assertContains('process_payment:ok', $steps);
        $this->assertContains('ship_order:ok', $steps);
    }

    public function testStockFailure(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand('order-2', 50000, stockAvailable: false));

        $this->assertSame(SagaState::Failed, $saga->state());
        $steps = array_map(fn($s) => $s->name . ':' . $s->status, $saga->steps());
        $this->assertContains('reserve_stock:failed', $steps);
        $this->assertNotContains('process_payment:ok', $steps);
    }

    public function testPaymentFailureCompensatesStock(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand('order-3', 50000, paymentSuccess: false));

        $this->assertSame(SagaState::Failed, $saga->state());
        $steps = array_map(fn($s) => $s->name . ':' . $s->status, $saga->steps());
        $this->assertContains('reserve_stock:ok', $steps);
        $this->assertContains('process_payment:failed', $steps);
        $this->assertContains('compensate_stock:ok', $steps);
    }

    public function testShipmentFailureCompensatesBoth(): void
    {
        $saga = $this->orchestrator->start(new StartSagaCommand('order-4', 50000, shipmentSuccess: false));

        $this->assertSame(SagaState::Failed, $saga->state());
        $steps = array_map(fn($s) => $s->name . ':' . $s->status, $saga->steps());
        $this->assertContains('reserve_stock:ok', $steps);
        $this->assertContains('process_payment:ok', $steps);
        $this->assertContains('ship_order:failed', $steps);
        $this->assertContains('compensate_payment:ok', $steps);
        $this->assertContains('compensate_stock:ok', $steps);
    }
}
```

- [ ] **Step 4: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Ch07 controller, šablona a testy pro Process Manager"
```

---

## Task 4: Ch09 Migrace — přidat CRUD "before" kód

**Files:**
- Create: `src/Chapter09_Migration/CrudVersion/Task.php`
- Create: `src/Chapter09_Migration/CrudVersion/TaskController.php`
- Modify: `src/Chapter09_Migration/UI/Chapter09Controller.php`
- Modify: `templates/examples/chapter09/index.html.twig`

- [ ] **Step 1: Vytvořit CRUD Task (anémický model)**

```php
<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\CrudVersion;

final class Task
{
    private string $id;
    private string $title;
    private string $status = 'todo';
    private ?string $assignedTo = null;

    public function getId(): string { return $this->id; }
    public function setId(string $id): void { $this->id = $id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getAssignedTo(): ?string { return $this->assignedTo; }
    public function setAssignedTo(?string $assignedTo): void { $this->assignedTo = $assignedTo; }
}
```

- [ ] **Step 2: Vytvořit CRUD TaskController (ukazuje co je špatně)**

```php
<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\CrudVersion;

final class TaskController
{
    /**
     * CRUD přístup: logika v controlleru, žádné guardy.
     * @return array{task: Task, error: ?string}
     */
    public function completeWithoutStart(): array
    {
        $task = new Task();
        $task->setId('task-1');
        $task->setTitle('Refaktorovat controller');
        // CRUD: nastavíme done přímo bez průchodu in_progress
        $task->setStatus('done');
        // Žádná výjimka — CRUD to povolí!
        return ['task' => $task, 'error' => null];
    }

    /**
     * CRUD: libovolný status string bez validace
     * @return array{task: Task, error: ?string}
     */
    public function setInvalidStatus(): array
    {
        $task = new Task();
        $task->setId('task-2');
        $task->setTitle('Test task');
        $task->setStatus('banana');
        // CRUD to povolí — žádná enum, žádná validace
        return ['task' => $task, 'error' => null];
    }
}
```

- [ ] **Step 3: Aktualizovat Ch09 Controller**

Přidat CRUD demonstraci vedle DDD:

```php
<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UI;

use App\Chapter09_Migration\CrudVersion\Task as CrudTask;
use App\Chapter09_Migration\CrudVersion\TaskController as CrudController;
use App\Chapter09_Migration\Domain\Task\Task;
use App\Chapter09_Migration\Domain\Task\TaskId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter09Controller extends AbstractController
{
    #[Route('/examples/migrace-z-crud', name: 'chapter09')]
    public function index(Request $request): Response
    {
        $dddResult = null;
        $dddError = null;
        $crudResult = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'ddd_valid') {
                $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
                $task->start('member-1');
                $task->complete();
                $dddResult = 'DDD: todo → in_progress → done ✓ (stav: ' . $task->status()->value . ')';
            } elseif ($action === 'ddd_invalid') {
                try {
                    $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
                    $task->complete();
                } catch (\DomainException $e) {
                    $dddError = 'DDD DomainException: ' . $e->getMessage();
                }
            } elseif ($action === 'crud_skip') {
                $crud = new CrudController();
                $result = $crud->completeWithoutStart();
                $crudResult = 'CRUD: stav nastaven na "' . $result['task']->getStatus() . '" — žádná výjimka, žádný guard!';
            } elseif ($action === 'crud_invalid') {
                $crud = new CrudController();
                $result = $crud->setInvalidStatus();
                $crudResult = 'CRUD: stav nastaven na "' . $result['task']->getStatus() . '" — nevalidní status prošel!';
            }
        }

        return $this->render('examples/chapter09/index.html.twig', [
            'dddResult' => $dddResult,
            'dddError' => $dddError,
            'crudResult' => $crudResult,
        ]);
    }
}
```

- [ ] **Step 4: Aktualizovat šablonu**

Dvousloupcové side-by-side porovnání s interaktivními tlačítky:

```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Migrace z CRUD na DDD{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="https://ddd-symfony.cz/migrace-z-crud"><strong>Migrace z CRUD architektury na DDD</strong></a>
    </div>
    <h1>Ukázka: CRUD → DDD — živé porovnání</h1>

    {% if crudResult %}<div class="alert alert-warning">{{ crudResult }}</div>{% endif %}
    {% if dddResult %}<div class="alert alert-success">{{ dddResult }}</div>{% endif %}
    {% if dddError %}<div class="alert alert-danger">{{ dddError }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white"><strong>CRUD přístup (problém)</strong></div>
                <div class="card-body">
                    <pre class="bg-light p-2 rounded small">class Task {
    public function setStatus(string $s): void {
        $this->status = $s; // žádná validace
    }
}</pre>
                    <form method="post" data-turbo="false" class="mb-2">
                        <input type="hidden" name="action" value="crud_skip">
                        <button class="btn btn-outline-danger w-100">setStatus('done') bez start</button>
                    </form>
                    <form method="post" data-turbo="false">
                        <input type="hidden" name="action" value="crud_invalid">
                        <button class="btn btn-outline-danger w-100">setStatus('banana') — nevalidní</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white"><strong>DDD přístup (řešení)</strong></div>
                <div class="card-body">
                    <pre class="bg-light p-2 rounded small">class Task {
    public function complete(): void {
        if ($this->status !== TaskStatus::InProgress)
            throw new DomainException(...);
        $this->status = TaskStatus::Done;
    }
}</pre>
                    <form method="post" data-turbo="false" class="mb-2">
                        <input type="hidden" name="action" value="ddd_valid">
                        <button class="btn btn-outline-success w-100">start() → complete() ✓</button>
                    </form>
                    <form method="post" data-turbo="false">
                        <input type="hidden" name="action" value="ddd_invalid">
                        <button class="btn btn-outline-success w-100">complete() bez start → DomainException</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

- [ ] **Step 5: Spustit testy + commit**

```bash
php vendor/bin/phpunit
git add -A && git commit -m "feat: Ch09 přidat CRUD 'before' kód pro side-by-side porovnání"
```

---

## Task 5: Ch01 rozšíření — strategické vzory

**Files:**
- Create: `src/Chapter01_WhatIsDDD/Domain/ContextMap/CatalogProductTranslator.php`
- Create: `src/Chapter01_WhatIsDDD/Domain/SharedKernel/ProductId.php`
- Modify: `src/Chapter01_WhatIsDDD/UI/Chapter01Controller.php`
- Modify: `templates/examples/chapter01/index.html.twig`

- [ ] **Step 1: Vytvořit SharedKernel ProductId**

Value object sdílený mezi kontexty:

```php
<?php
declare(strict_types=1);
namespace App\Chapter01_WhatIsDDD\Domain\SharedKernel;

final readonly class ProductId
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('ProductId cannot be empty');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

- [ ] **Step 2: Vytvořit Anti-Corruption Layer**

```php
<?php
declare(strict_types=1);
namespace App\Chapter01_WhatIsDDD\Domain\ContextMap;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\BoundedContext\OrderProduct;

/**
 * Anti-Corruption Layer: překládá CatalogProduct (z Catalog kontextu)
 * na OrderProduct (pro Order kontext).
 * Chrání Order kontext před změnami v Catalog kontextu.
 */
final class CatalogProductTranslator
{
    public function toOrderProduct(CatalogProduct $catalog, int $priceCents, string $currency, float $taxRate): OrderProduct
    {
        return new OrderProduct(
            productId: $catalog->id,
            unitPriceCents: $priceCents,
            currency: $currency,
            taxRate: $taxRate,
        );
    }
}
```

- [ ] **Step 3: Aktualizovat Controller — přidat ACL ukázku**

V controlleru přidat ACL demonstraci a předat translator + translated product do šablony.

- [ ] **Step 4: Aktualizovat šablonu — přidat sekci strategických vzorů**

Přidat po Bounded Context sekci:
- Shared Kernel ukázka (ProductId sdílený mezi kontexty)
- Anti-Corruption Layer diagram (CatalogProduct → translator → OrderProduct)
- Context Map vysvětlení

- [ ] **Step 5: Spustit testy + commit**

```bash
php vendor/bin/phpunit
git add -A && git commit -m "feat: Ch01 rozšířit o strategické vzory (ACL, Shared Kernel, Context Map)"
```

---

## Task 6: Ch03 Domain Service

**Files:**
- Create: `src/Chapter03_BasicConcepts/Domain/Service/OrderConfirmationService.php`
- Modify: `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`
- Modify: `templates/examples/chapter03/index.html.twig`

- [ ] **Step 1: Vytvořit OrderConfirmationService**

```php
<?php
declare(strict_types=1);
namespace App\Chapter03_BasicConcepts\Domain\Service;

use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Repository\OrderRepositoryInterface;

final class OrderConfirmationService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * Potvrdí objednávku a uloží ji.
     * Doménová služba — operuje nad agregátem + repozitářem.
     */
    public function confirm(Order $order): void
    {
        $order->confirm();
        $this->orders->save($order);
    }
}
```

- [ ] **Step 2: Aktualizovat Controller — přidat domain service ukázku**

Přidat akci `confirm_via_service` která ukáže použití domain service.

- [ ] **Step 3: Aktualizovat šablonu — přidat Domain Service sekci**

Přidat sekci vysvětlující kdy použít domain service vs. metodu na agregátu.

- [ ] **Step 4: Spustit testy + commit**

```bash
php vendor/bin/phpunit
git add -A && git commit -m "feat: Ch03 přidat OrderConfirmationService (domain service ukázka)"
```

---

## Task 7: Per-chapter README.md soubory

**Files:**
- Create: `src/Chapter03_BasicConcepts/README.md`
- Create: `src/Chapter04_Implementation/README.md`
- Create: `src/Chapter05_CQRS/README.md`
- Create: `src/Chapter06_EventSourcing/README.md`
- Create: `src/Chapter07_Sagas/README.md`
- Create: `src/Chapter08_Testing/README.md`
- Create: `src/Chapter09_Migration/README.md`

Každý README.md má formát:
```markdown
# Kapitola X: [Název]

Tato ukázka demonstruje [co ukázka ukazuje].

## Spuštění

Otevři [http://localhost:8000/examples/[slug]](http://localhost:8000/examples/[slug])

## Co se naučíš

- [Bod 1]
- [Bod 2]

## Odkaz na příručku

[Název kapitoly](https://ddd-symfony.cz/[slug])
```

- [ ] **Step 1: Vytvořit všech 7 README souborů**
- [ ] **Step 2: Commit**

```bash
git add -A && git commit -m "docs: přidat per-chapter README.md soubory"
```

---

## Task 8: Navigace v šablonách

**Files:**
- Modify: `templates/base.html.twig` nebo každá chapter šablona

- [ ] **Step 1: Přidat breadcrumbs a navigaci**

Do každé chapter šablony přidat na začátek (po alert):
```twig
<nav class="mb-3">
    <a href="{{ path('examples_index') }}">← Zpět na přehled</a>
</nav>
```

A na konec (před uzavření containeru):
```twig
<nav class="d-flex justify-content-between mt-5 pt-3 border-top">
    {% if prev_route is defined and prev_route %}
        <a href="{{ path(prev_route) }}">← {{ prev_title }}</a>
    {% else %}
        <span></span>
    {% endif %}
    {% if next_route is defined and next_route %}
        <a href="{{ path(next_route) }}">{{ next_title }} →</a>
    {% else %}
        <span></span>
    {% endif %}
</nav>
```

Každý controller předá prev/next data do šablony. Pořadí kapitol: 01 → 03 → 04 → 05 → 06 → 07 → 08 → 09.

- [ ] **Step 2: Spustit cache clear + commit**

```bash
php bin/console cache:clear
git add -A && git commit -m "feat: přidat breadcrumbs a prev/next navigaci do chapter šablon"
```
