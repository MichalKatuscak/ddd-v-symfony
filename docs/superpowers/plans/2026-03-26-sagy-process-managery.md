# Ságy a Process Managery — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a comprehensive new chapter on Sagas and Process Managers to the DDD in Symfony educational website, positioned between Event Sourcing and Příklady.

**Architecture:** Pure content addition — one new Twig template (~1500–1800 lines), one new controller action, sidebar/navigation updates, cross-link updates in 4 existing templates. No database, no build step.

**Tech Stack:** Twig 3.8+, Symfony 8 routing (PHP attributes), HTML5 with schema.org microdata, ARIA attributes, CSS callout classes (.tip/.note/.warning)

---

## File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `templates/ddd/sagas.html.twig` | New chapter content (~1500–1800 lines) |
| Modify | `src/Controller/DddController.php:107-113` | Add `sagas()` route+action between event_sourcing and practical_examples |
| Modify | `templates/base.html.twig:88-92` | Add "Ságy" sidebar nav link after Event Sourcing |
| Modify | `templates/ddd/cqrs.html.twig:1657-1758` | Shorten Saga section to 2–3 paragraphs + link to new chapter |
| Modify | `templates/ddd/event_sourcing.html.twig:1782-1785` | Update forward link to mention Sagas chapter |
| Modify | `templates/ddd/index.html.twig:38,53` | Update chapter count 14→15 |
| Modify | `templates/ddd/glossary.html.twig:899` | Add link to new chapter in term-saga related links |

---

### Task 1: Add controller route

**Files:**
- Modify: `src/Controller/DddController.php:107-113`

- [ ] **Step 1: Add the sagas route+action between event_sourcing and practical_examples**

In `src/Controller/DddController.php`, insert a new method after `eventSourcing()` (line 113) and before `practicalExamples()` (currently `antiPatterns` at line 115, but logically before the `practical_examples` route at line 59). Since routes are attribute-based and order in file doesn't determine matching, add it after `eventSourcing()`:

```php
    #[Route('/sagy-a-process-managery', name: 'sagas')]
    public function sagas(): Response
    {
        return $this->render('ddd/sagas.html.twig', [
            'title' => 'Ságy a Process Managery',
        ]);
    }
```

Insert this after line 113 (closing `}` of `eventSourcing()`).

- [ ] **Step 2: Commit**

```bash
git add src/Controller/DddController.php
git commit -m "feat: přidat route pro novou kapitolu Ságy a Process Managery"
```

---

### Task 2: Add sidebar navigation link

**Files:**
- Modify: `templates/base.html.twig:88-92`

- [ ] **Step 1: Insert sidebar nav item for Ságy after Event Sourcing**

After line 90 (closing `</li>` of the Event Sourcing nav item), insert:

```html
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {% if app.request.attributes.get('_route') == 'sagas' %}active{% endif %}" href="{{ path('sagas') }}">Ságy</a>
                    </li>
```

- [ ] **Step 2: Commit**

```bash
git add templates/base.html.twig
git commit -m "feat: přidat odkaz Ságy do sidebar navigace"
```

---

### Task 3: Create the new chapter template — skeleton + sections 1–4

**Files:**
- Create: `templates/ddd/sagas.html.twig`

- [ ] **Step 1: Create the template file with the full Twig skeleton (extends, blocks, meta, JSON-LD), inline TOC for all 14 sections, and sections 1–4**

The file must include:
- `{% extends 'base.html.twig' %}`
- `{% block title %}Ságy a Process Managery | DDD Symfony{% endblock %}`
- `{% block meta_description %}` — SEO description mentioning ságy, process managery, kompenzační transakce, choreografie, orchestrace, Symfony Messenger
- `{% block meta_keywords %}` — saga, process manager, kompenzační transakce, choreografie, orchestrace, CQRS, DDD, Symfony 8, Messenger, distribuované transakce
- `{% block structured_data %}` — schema.org TechArticle JSON-LD (same pattern as cqrs.html.twig)
- `{% block body %}` with `<article itemscope itemtype="https://schema.org/TechArticle">`
- `<h1 itemprop="headline">Ságy a Process Managery</h1>`
- `<div class="table-of-contents">` listing all 14 sections with anchor links
- `{% block toc %}<p class="toc-title">Na této stránce</p>{% endblock %}`

**Section 1: Proč potřebujeme ságy?** (`id="proc-sagy"`, heading id `proc-sagy-heading`)
- Opening paragraph: e-shop objednávka = 4 kroky napříč Bounded Contexts (Ordering, Payment, Warehouse, Shipping), each with own aggregate/database
- Paragraph explaining why single DB transaction is impossible across contexts
- Note box explaining 2PC (Two-Phase Commit) and why it's unsuitable: výkonnostní overhead, tight coupling, single point of failure, incompatible with DDD bounded context autonomy
- Concrete failure scenario: customer charged but stock unavailable — system in inconsistent state
- Introductory paragraph about Garcia-Molina & Salem (1987) proposing Sagas as alternative
- Citation: Garcia-Molina & Salem, *Sagas* (1987); Vernon, *Implementing Domain-Driven Design* (2013), kap. 8
- Cross-links to `{{ path('basic_concepts') }}#bounded-contexts` and `{{ path('cqrs') }}`

**Section 2: Kompenzační transakce** (`id="kompenzacni-transakce"`, heading id `kompenzacni-transakce-heading`)
- Definition paragraph: semantic undo of previous step's effect, not a technical rollback
- Comparison table in HTML:

| Akce | Kompenzace | Poznámka |
|------|-----------|----------|
| `ChargeCustomer` | `RefundCustomer` | Zahrnuje notifikaci zákazníka |
| `ReserveStock` | `ReleaseStock` | Uvolnění rezervace, nikoliv smazání |
| `CreateShipment` | `CancelShipment` | Pouze do okamžiku odeslání |

- Key principle paragraph: compensation ≠ exact inverse (RefundCustomer includes notification, audit log)
- Tip box with PHP code example — `CompensatableCommand` interface:

```php
<?php

declare(strict_types=1);

namespace App\SharedKernel\Application\Command;

/**
 * Command, který lze kompenzovat — definuje svůj "undo" příkaz.
 */
interface CompensatableCommand
{
    /**
     * Vrátí příkaz, který sémanticky vrátí efekt tohoto příkazu.
     */
    public function compensation(): object;
}
```

- Tip box with implementation example — `ChargeCustomer` implementing the interface:

```php
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
```

- Warning box: compensation must be idempotent — the same compensation may be delivered more than once

**Section 3: Choreografie** (`id="choreografie"`, heading id `choreografie-heading`)
- Definition paragraph: no central coordinator, contexts react to each other's events
- Diagram description paragraph: OrderPlaced → Payment → PaymentSucceeded → Warehouse → StockReserved → Shipping → ShipmentCreated
- Tip box with PHP code — choreography example, 3 independent handlers:

```php
<?php

declare(strict_types=1);

namespace App\Payment\Application\Handler;

use App\Ordering\Domain\Event\OrderPlaced;
use App\Payment\Application\Command\ChargeCustomer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Choreografie: Payment kontext naslouchá na OrderPlaced
 * a autonomně iniciuje platbu.
 */
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
```

```php
<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Handler;

use App\Payment\Domain\Event\PaymentSucceeded;
use App\Warehouse\Application\Command\ReserveStock;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Choreografie: Warehouse kontext naslouchá na PaymentSucceeded
 * a autonomně rezervuje sklad.
 */
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
```

```php
<?php

declare(strict_types=1);

namespace App\Shipping\Application\Handler;

use App\Warehouse\Domain\Event\StockReserved;
use App\Shipping\Application\Command\CreateShipment;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Choreografie: Shipping kontext naslouchá na StockReserved
 * a autonomně vytváří zásilku.
 */
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
```

- Tip box with Messenger routing config:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            # Události se routují na async transport
            'App\Ordering\Domain\Event\OrderPlaced': async
            'App\Payment\Domain\Event\PaymentSucceeded': async
            'App\Warehouse\Domain\Event\StockReserved': async
```

- Closing paragraph: advantage is loose coupling, simplicity for 2–3 steps

**Section 4: Limity choreografie** (`id="limity-choreografie"`, heading id `limity-choreografie-heading`)
- 4 problems, each as a subsection with `<h3>`:
  1. **Neviditelný tok procesu** — with 5+ contexts, no one sees the full flow — "distributed spaghetti"
  2. **Porušení Open-Closed Principle** — adding a new step requires changing an existing context's handler
  3. **Obtížná diagnostika selhání** — where did the process get stuck? Which step failed? No central view
  4. **Chybějící timeout management** — who detects that a process is "hanging"? Each context only knows about its own step
- Transition paragraph: need a central place that knows the entire process → orchestration
- Note box: choreography is still valid for simple 2–3 step processes; don't over-engineer

- [ ] **Step 2: Commit**

```bash
git add templates/ddd/sagas.html.twig
git commit -m "content: přidat kapitolu Ságy — skeleton + sekce 1–4 (motivace, kompenzace, choreografie, limity)"
```

---

### Task 4: Sections 5–7 — Orchestrace, Perzistence, Implementace v Messenger

**Files:**
- Modify: `templates/ddd/sagas.html.twig`

- [ ] **Step 1: Add sections 5–7**

**Section 5: Orchestrace (Process Manager)** (`id="orchestrace"`, heading id `orchestrace-heading`)
- Definition paragraph: central `OrderProcessManager` as state machine with defined states and transitions
- Tip box with PHP enum:

```php
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
```

- Tip box with the `OrderProcessManager` class — event handler using `#[AsMessageHandler]` with union type `OrderPlaced|PaymentSucceeded|PaymentFailed|StockReserved|StockReservationFailed|ShipmentCreated`:

```php
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
use App\Warehouse\Application\Command\ReleaseStock;
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
        private readonly SagaStateRepository $sagaStateRepository,
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
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new ReserveStock(
            orderId: $event->orderId,
        ));
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
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new CreateShipment(
            orderId: $event->orderId,
        ));
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
        $this->sagaStateRepository->save($state);

        $this->commandBus->dispatch(new ConfirmOrder(
            orderId: $event->orderId,
        ));
    }
}
```

- Comparison paragraph: orchestration advantages — single place for entire flow, easy to debug, easy to extend
- Note box: each method is a "step" in the state machine — adding a new step means adding one method + one event, without modifying existing contexts

**Section 6: Perzistence stavu ságy** (`id="perzistence-stavu"`, heading id `perzistence-stavu-heading`)
- Opening paragraph: why persistence is necessary — worker crash, deployment, horizontal scaling
- Tip box with `SagaState` entity:

```php
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

    public function isCompleted(): bool
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
```

- Tip box with `SagaStateRepository`:

```php
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Saga;

use Doctrine\ORM\EntityManagerInterface;

final readonly class SagaStateRepository
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
```

- Recovery scenario paragraph: worker crashes between OrderPlaced and PaymentSucceeded — after restart, saga resumes from the correct step because state is in DB
- Note box: in production, consider optimistic locking (`@Version` column) to prevent concurrent updates to same saga

**Section 7: Implementace v Symfony Messenger** (`id="implementace-messenger"`, heading id `implementace-messenger-heading`)
- Opening paragraph: tying together sections 5 & 6 with full Messenger configuration
- Tip box with complete `messenger.yaml`:

```yaml
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
            # Doménové události → async event bus
            'App\Ordering\Domain\Event\OrderPlaced': async_events
            'App\Payment\Domain\Event\PaymentSucceeded': async_events
            'App\Payment\Domain\Event\PaymentFailed': async_events
            'App\Warehouse\Domain\Event\StockReserved': async_events
            'App\Warehouse\Domain\Event\StockReservationFailed': async_events
            'App\Shipping\Domain\Event\ShipmentCreated': async_events

            # Příkazy → async command bus
            'App\Payment\Application\Command\ChargeCustomer': async_commands
            'App\Payment\Application\Command\RefundCustomer': async_commands
            'App\Warehouse\Application\Command\ReserveStock': async_commands
            'App\Warehouse\Application\Command\ReleaseStock': async_commands
            'App\Shipping\Application\Command\CreateShipment': async_commands
```

- Tip box with event class example:

```php
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

/**
 * Doménová událost: objednávka byla vytvořena.
 * Obsahuje pouze identifikátory a data potřebná pro další kroky procesu.
 */
final readonly class OrderPlaced
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $totalAmountCents,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
```

- Paragraph explaining the flow: event dispatched → Messenger routes to async transport → worker picks it up → OrderProcessManager handles it → dispatches command → next handler picks it up
- Note box: run separate workers for events and commands: `php bin/console messenger:consume async_events async_commands --time-limit=3600`
- Cross-link to `{{ path('cqrs') }}#async` for async processing details

- [ ] **Step 2: Commit**

```bash
git add templates/ddd/sagas.html.twig
git commit -m "content: přidat sekce 5–7 (orchestrace, perzistence stavu, implementace v Messenger)"
```

---

### Task 5: Sections 8–10 — Timeouty, Kompenzační strategie, Paralelní kroky

**Files:**
- Modify: `templates/ddd/sagas.html.twig`

- [ ] **Step 1: Add sections 8–10**

**Section 8: Timeout handling** (`id="timeout-handling"`, heading id `timeout-handling-heading`)
- Opening paragraph: what if PaymentSucceeded never arrives? Network failure, external service down, message lost
- Tip box with `CheckSagaTimeout` command and handler:

```php
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
```

```php
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
        private SagaStateRepository $sagaStateRepository,
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(CheckSagaTimeout $command): void
    {
        $state = $this->sagaStateRepository->findByCorrelationId($command->orderId);

        // Saga se od posledního kroku posunula — timeout neplatí
        if ($state->status()->value !== $command->expectedStatus) {
            return;
        }

        // Saga stále čeká → iniciovat kompenzaci
        $state->transitionTo(OrderSagaStatus::Compensating);
        $this->sagaStateRepository->save($state);

        match ($state->status()) {
            OrderSagaStatus::AwaitingPayment => $this->commandBus->dispatch(
                new CancelOrder(orderId: $command->orderId, reason: 'Payment timeout'),
            ),
            OrderSagaStatus::AwaitingStockReservation => $this->compensatePayment($state),
            default => null, // Další kroky dle potřeby
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
```

- Tip box showing how to dispatch the timeout check with `DelayStamp` in the `OrderProcessManager.onOrderPlaced()`:

```php
use Symfony\Component\Messenger\Stamp\DelayStamp;

private function onOrderPlaced(OrderPlaced $event): void
{
    // ... (vytvoření SagaState a dispatch ChargeCustomer — viz sekce 5)

    // Naplánovat timeout check za 5 minut
    $this->commandBus->dispatch(
        new CheckSagaTimeout(
            orderId: $event->orderId,
            expectedStatus: OrderSagaStatus::AwaitingPayment->value,
        ),
        [new DelayStamp(5 * 60 * 1000)], // 5 minut v milisekundách
    );
}
```

- Note box: configurable timeouts per step — payment may need 5 min, stock reservation 30 s, shipment 24 h
- Warning box: DelayStamp requires an async transport that supports delayed delivery (e.g. RabbitMQ with delayed message plugin, or Doctrine transport)

**Section 9: Kompenzační strategie v praxi** (`id="kompenzacni-strategie"`, heading id `kompenzacni-strategie-heading`)
- Two strategies as `<h3>` subsections:
  - **Forward recovery (retry)** — suitable for transient failures (network timeout, temporary service unavailability); use Messenger retry strategy
  - **Backward recovery (kompenzace)** — suitable for business failures (insufficient funds, out of stock); execute compensation actions in reverse order
- Key principle paragraph: semantic compensation — `RefundCustomer` is a new domain action with its own business logic (notification, audit trail), not a DELETE from table
- Tip box with full compensation logic in `OrderProcessManager`:

```php
/**
 * Kompenzace: spouštěna při selhání libovolného kroku.
 * Provádí kompenzační akce v opačném pořadí — od posledního úspěšného kroku zpět.
 */
private function compensate(SagaState $state): void
{
    $completedSteps = $state->context()['completedSteps'] ?? [];

    // Kompenzace v opačném pořadí
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
```

- Note box: each compensation handler must be idempotent — check if compensation was already performed before acting
- Cross-link to `{{ path('cqrs') }}#error-handling` for DLQ and retry strategies

**Section 10: Paralelní kroky** (`id="paralelni-kroky"`, heading id `paralelni-kroky-heading`)
- Scenario: after payment, reserve stock AND generate invoice simultaneously
- Synchronization barrier: saga waits for both to complete before proceeding
- Tip box with PHP code — parallel step handling:

```php
private function onPaymentSucceeded(PaymentSucceeded $event): void
{
    $state = $this->sagaStateRepository->findByCorrelationId($event->orderId);
    $state->transitionTo(OrderSagaStatus::AwaitingStockAndInvoice);

    // Inicializovat bariéru: oba kroky musí dokončit
    $state->updateContext('stockReserved', false);
    $state->updateContext('invoiceCreated', false);
    $this->sagaStateRepository->save($state);

    // Spustit oba kroky paralelně
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
```

- Warning box: parallel steps increase compensation complexity — if stock reservation succeeds but invoice fails, you need to release the stock. Always design compensation for each parallel branch independently.
- Note box: consider optimistic locking on `SagaState` — two parallel events may arrive simultaneously and cause a race condition on the context update

- [ ] **Step 2: Commit**

```bash
git add templates/ddd/sagas.html.twig
git commit -m "content: přidat sekce 8–10 (timeouty, kompenzační strategie, paralelní kroky)"
```

---

### Task 6: Sections 11–14 — Monitoring, Testování, Shrnutí, Cvičení

**Files:**
- Modify: `templates/ddd/sagas.html.twig`

- [ ] **Step 1: Add sections 11–14**

**Section 11: Monitoring a observabilita** (`id="monitoring"`, heading id `monitoring-heading`)
- Opening paragraph: production sagas need observability — you need to know which sagas are running, which are stuck, which failed
- Subsection `<h3>`: **Korelační ID** — every message in the saga carries the same correlation ID (orderId), allowing full traceability across contexts. Link to `{{ path('glossary') }}#term-korelacni-id`
- Tip box with Messenger middleware for structured logging:

```php
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
```

- Subsection `<h3>`: **Detekce zaseklých ság** — cron/scheduled command checking for stale sagas
- Tip box with Symfony console command:

```php
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
        private readonly SagaStateRepository $sagaStateRepository,
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
                '  [%s] %s — stav: %s, poslední aktivita: %s',
                $saga->correlationId(),
                'order_process',
                $saga->status()->value,
                $saga->updatedAt()->format('Y-m-d H:i:s'),
            ));
        }

        return Command::FAILURE;
    }
}
```

- Note box: in production, integrate with your alerting system (Prometheus metrics, Grafana dashboard, PagerDuty) — this command is a starting point
- Cross-link to `{{ path('cqrs') }}#middleware` for middleware details

**Section 12: Testování ság** (`id="testovani-sag"`, heading id `testovani-sag-heading`)
- Opening paragraph: sagas coordinate complex flows — testing them is critical. Three levels: unit, integration, end-to-end.
- Subsection `<h3>`: **Unit testy stavového automatu** — test each transition independently
- Tip box with PHPUnit test:

```php
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
        // Given: saga čeká na platbu
        ($this->saga)(new OrderPlaced('order-1', 'cust-1', 10000));
        $this->dispatchedCommands = [];

        // When: platba uspěla
        ($this->saga)(new PaymentSucceeded(orderId: 'order-1'));

        // Then: reservace skladu
        self::assertCount(1, $this->dispatchedCommands);
        self::assertInstanceOf(ReserveStock::class, $this->dispatchedCommands[0]);

        $state = $this->repository->findByCorrelationId('order-1');
        self::assertSame(
            OrderSagaStatus::AwaitingStockReservation,
            $state->status(),
        );
    }

    public function testPaymentFailedCancelsOrder(): void
    {
        // Given: saga čeká na platbu
        ($this->saga)(new OrderPlaced('order-1', 'cust-1', 10000));
        $this->dispatchedCommands = [];

        // When: platba selhala
        ($this->saga)(new PaymentFailed(
            orderId: 'order-1',
            failureReason: 'Insufficient funds',
        ));

        // Then: objednávka zrušena, žádný refund (nic nebylo zaplaceno)
        $state = $this->repository->findByCorrelationId('order-1');
        self::assertSame(OrderSagaStatus::Failed, $state->status());
    }
}
```

- Note box with `InMemorySagaStateRepository` for tests:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Application\Saga;

use App\Ordering\Application\Saga\SagaState;
use App\Ordering\Application\Saga\SagaStateRepository;

final class InMemorySagaStateRepository extends SagaStateRepository
{
    /** @var array<string, SagaState> */
    private array $states = [];

    public function __construct() {}  // Nepotřebuje EntityManager

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
```

- Cross-link to `{{ path('testing_ddd') }}` for broader testing patterns in DDD

**Section 13: Co jsme se naučili** (`id="shrnuti-kapitoly"`, heading id `shrnuti-kapitoly-heading`)
- 10 bullet points:
  1. Distribuované transakce (2PC) nejsou v DDD vhodné — ságy nabízejí alternativu pomocí kompenzačních transakcí
  2. Kompenzační transakce sémanticky vrací efekt předchozího kroku — nejsou technickým rollbackem, ale novými doménovými akcemi
  3. Choreografie je jednoduchá pro 2–3 kroky, ale při rostoucím počtu kontextů vede k "distributed spaghetti"
  4. Orchestrace (Process Manager) centralizuje řízení procesu do stavového automatu — snadnější ladění, rozšiřování i monitoring
  5. Stav ságy musí být persistentní — worker crash nesmí ztratit informaci o tom, v jakém kroku se proces nachází
  6. Symfony Messenger s async transportem a DelayStamp poskytuje infrastrukturu pro implementaci ság
  7. Timeout handling je klíčový — každý krok potřebuje časový limit a definovanou reakci na jeho vypršení
  8. Kompenzace se provádí v opačném pořadí dokončených kroků a každá kompenzační akce musí být idempotentní
  9. Paralelní kroky zvyšují propustnost, ale vyžadují synchronizační bariéru a nezávislou kompenzaci pro každou větev
  10. Monitoring ság (korelační ID, detekce zaseklých procesů) je pro produkční provoz nezbytný

**Section 14: Zkuste sami** (`id="zkuste-sami"`, heading id `zkuste-sami-heading`)
- 5 exercises in `<div class="tip"><ol>`:
  1. Rozšiřte `OrderProcessManager` o krok notifikace: po `ShipmentCreated` odešlete zákazníkovi e-mail s trackovacím číslem. Co se stane, když odeslání e-mailu selže? Měla by tato chyba spustit kompenzaci celého procesu?
  2. Implementujte timeout pro krok `AwaitingStockReservation` s limitem 30 sekund. Použijte `DelayStamp` a `CheckSagaTimeout`. Jakou kompenzaci provedete?
  3. Přidejte do procesu paralelní krok: po úspěšné platbě se souběžně rezervuje sklad a generuje faktura. Implementujte synchronizační bariéru pomocí `SagaState.context`.
  4. Napište test kompenzační cesty: objednávka → platba úspěšná → rezervace skladu selhala. Ověřte, že saga provede refund a zruší objednávku.
  5. Navrhněte choreografickou alternativu pro zjednodušený dvou-krokový proces (objednávka → platba). Porovnejte složitost s orchestrací a rozhodněte, kdy je choreografie dostačující.

- Closing paragraph with forward link: „V další kapitole se podíváme na <a href="{{ path('practical_examples') }}">praktické příklady implementace DDD v Symfony</a>, kde uvidíte vzory z tohoto článku zasazené do reálnějšího kontextu."

- [ ] **Step 2: Commit**

```bash
git add templates/ddd/sagas.html.twig
git commit -m "content: přidat sekce 11–14 (monitoring, testování, shrnutí, cvičení)"
```

---

### Task 7: Update existing cross-links

**Files:**
- Modify: `templates/ddd/cqrs.html.twig:1657-1758`
- Modify: `templates/ddd/event_sourcing.html.twig:1782-1785`
- Modify: `templates/ddd/index.html.twig:38,53`
- Modify: `templates/ddd/glossary.html.twig:899`

- [ ] **Step 1: Shorten the CQRS Saga section**

Replace lines 1657–1758 in `cqrs.html.twig` with a shortened version (2–3 paragraphs + link). Keep the `<section id="saga">` and heading for existing anchor compatibility. Remove the code example and the persistence note box. Replace with:

```html
    <section id="saga" aria-labelledby="saga-heading">
    <h2 id="saga-heading">Saga / Process Manager</h2>

    <p>
        V komplexních systémech s více <a href="{{ path('basic_concepts') }}#bounded-contexts">Bounded Contexts</a>
        je často potřeba koordinovat dlouhotrvající business procesy, které zahrnují více kroků a více agregátů.
        K tomu slouží vzor <strong>Saga</strong> (neboli <strong>Process Manager</strong>).
    </p>
    <p>
        Saga naslouchá doménovým událostem a na základě nich odesílá příkazy do dalších kontextů.
        Pokud některý krok selže, Saga provede <strong>kompenzační akce</strong> místo rollbacku
        distribuované transakce. Dva hlavní přístupy:
    </p>

    <ul>
        <li><strong>Choreografie</strong> — kontexty komunikují pouze přes události, bez centrálního koordinátora.</li>
        <li><strong>Orchestrace</strong> — centrální Process Manager řídí tok procesu a odesílá příkazy.</li>
    </ul>

    <div class="note" role="note" aria-labelledby="saga-kapitola-heading">
        <h3 id="saga-kapitola-heading">Samostatná kapitola</h3>
        <p>
            Podrobný výklad ság — včetně implementace Process Manageru v Symfony Messenger,
            perzistence stavu, timeout handlingu, kompenzačních strategií, paralelních kroků,
            monitoringu a testování — najdete v kapitole
            <a href="{{ path('sagas') }}">Ságy a Process Managery</a>.
        </p>
    </div>

    <p>
        V další kapitole se podíváme na <a href="{{ path('event_sourcing') }}">Event Sourcing</a>,
        vzor persistence, který se s CQRS přirozeně doplňuje.
    </p>
    </section>
```

Also update the CQRS "Co jsme se naučili" section — change the Saga bullet point (line 1777) to:

```html
        <li><strong>Saga / Process Manager</strong> koordinuje dlouhotrvající procesy napříč Bounded Contexts — podrobně viz <a href="{{ path('sagas') }}">samostatná kapitola</a>.</li>
```

And update the "Zkuste sami" section — change exercise 5 (line 1789) to:

```html
            <li>Prozkoumejte kapitolu <a href="{{ path('sagas') }}">Ságy a Process Managery</a> a navrhněte Sagu pro proces „registrace uživatele", která po úspěšné registraci odešle uvítací e-mail a vytvoří výchozí nastavení profilu.</li>
```

- [ ] **Step 2: Update event_sourcing forward link**

Replace lines 1782–1785 in `event_sourcing.html.twig`:

```html
        <p>
            V další kapitole se podíváme na <a href="{{ path('sagas') }}">ságy a process managery</a> —
            vzor koordinace dlouhotrvajících procesů napříč Bounded Contexts, který se s Event Sourcing
            přirozeně doplňuje. Poté pokračujte na
            <a href="{{ path('practical_examples') }}">praktické příklady implementace DDD v Symfony</a>.
        </p>
```

- [ ] **Step 3: Update index.html.twig chapter count and reading path**

In `index.html.twig`, change line 38:

```html
    <span>15 kapitol s ukázkami kódu</span>
```

Change line 53:

```html
            <span class="hero-stats-num">15</span>
```

In the advanced reading path (around line 82–85), add the sagas chapter after Event Sourcing:

```html
                <li><a href="{{ path('sagas') }}">Ságy a Process Managery</a></li>
```

- [ ] **Step 4: Update glossary term-saga related links**

In `glossary.html.twig`, after line 898 (the term-source line), modify the `term-related` paragraph (line 899) to include a link to the new chapter. Replace:

```html
                        <p class="term-related">Viz také: <a href="#term-domenova-udalost">Doménová událost</a>, <a href="#term-eventual-consistency">Eventual Consistency</a>, <a href="#term-cqrs">CQRS</a>, <a href="#term-ohraniceny-kontext">Ohraničený kontext</a></p>
```

With:

```html
                        <p class="term-related">Viz také: <a href="{{ path('sagas') }}">kapitola Ságy a Process Managery</a>, <a href="#term-domenova-udalost">Doménová událost</a>, <a href="#term-eventual-consistency">Eventual Consistency</a>, <a href="#term-cqrs">CQRS</a>, <a href="#term-ohraniceny-kontext">Ohraničený kontext</a></p>
```

- [ ] **Step 5: Commit**

```bash
git add templates/ddd/cqrs.html.twig templates/ddd/event_sourcing.html.twig templates/ddd/index.html.twig templates/ddd/glossary.html.twig
git commit -m "content: aktualizovat křížové odkazy pro novou kapitolu Ságy"
```

---

### Task 8: Final review and verification

- [ ] **Step 1: Verify the template renders without Twig errors**

```bash
php bin/console cache:clear
php bin/console debug:router | grep saga
```

Expected: route `sagas` pointing to `/sagy-a-process-managery`

- [ ] **Step 2: Verify all cross-links**

Check that these route names resolve:
- `sagas` route exists
- CQRS chapter links to `sagas`
- Event Sourcing links to `sagas`
- Glossary links to `sagas`
- New chapter links back to `cqrs`, `event_sourcing`, `basic_concepts`, `testing_ddd`, `practical_examples`, `glossary`
- Sidebar shows "Ságy" between "Event Sourcing" and "Příklady"

- [ ] **Step 3: Visual check of navigation order**

Open the site and verify sidebar order:
1. Úvod → Co je DDD → Základní koncepty → Vertikální slice → Implementace v Symfony → CQRS → Event Sourcing → **Ságy** → Příklady → Případová studie → Testování DDD → Migrace z CRUD → Anti-vzory → Výkonnostní aspekty → Zdroje → Glosář

- [ ] **Step 4: Commit final state**

```bash
git add -A
git commit -m "chore: finální ověření nové kapitoly Ságy a Process Managery"
```
