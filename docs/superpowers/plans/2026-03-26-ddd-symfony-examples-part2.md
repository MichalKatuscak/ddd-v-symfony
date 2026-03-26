# ddd-symfony-examples Implementation Plan (Part 2: CQRS, Event Sourcing, Ságy, Testování, Migrace, Finalizace)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Prerequisite:** Part 1 dokončen (`2026-03-26-ddd-symfony-examples-part1.md`).

**Goal:** Dokončit repozitář ddd-symfony-examples — kapitoly CQRS, Event Sourcing, Ságy, Testování, Migrace z CRUD, plus index stránka, Makefile, README a propojení s příručkou.

**Tech Stack:** PHP 8.3+, Symfony 8, Doctrine ORM + SQLite, Symfony Messenger, PHPUnit 11

---

### Task 6: Chapter05 — CQRS (Symfony Messenger, Commands, Queries, oddělené read/write modely)

**Files:**
- Create: `src/Chapter05_CQRS/Domain/Order/` (OrderId, Money, OrderStatus, OrderItem, Order, OrderPlaced)
- Create: `src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderCommand.php`
- Create: `src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderHandler.php`
- Create: `src/Chapter05_CQRS/Application/GetOrders/GetOrdersQuery.php`
- Create: `src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php`
- Create: `src/Chapter05_CQRS/Application/GetOrders/OrderView.php`
- Create: `src/Chapter05_CQRS/Domain/Repository/OrderRepositoryInterface.php`
- Create: `src/Chapter05_CQRS/Infrastructure/Persistence/DoctrineOrderRepository.php`
- Create: `src/Chapter05_CQRS/UI/Chapter05Controller.php`
- Create: `templates/examples/chapter05/index.html.twig`
- Modify: `config/packages/messenger.yaml`
- Test: `tests/Chapter05/Application/PlaceOrderHandlerTest.php`

- [ ] **Step 1: Napiš failing testy**

`tests/Chapter05/Application/PlaceOrderHandlerTest.php`:
```php
<?php

namespace App\Tests\Chapter05\Application;

use App\Chapter05_CQRS\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter05_CQRS\Application\PlaceOrder\PlaceOrderHandler;
use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PlaceOrderHandlerTest extends TestCase
{
    public function test_places_order_and_returns_id(): void
    {
        $repo = new class implements OrderRepositoryInterface {
            public ?Order $saved = null;
            public function save(Order $order): void { $this->saved = $order; }
            public function findById(OrderId $id): ?Order { return null; }
            public function findAll(): array { return []; }
        };

        $handler = new PlaceOrderHandler($repo);
        $orderId = ($handler)(new PlaceOrderCommand(
            customerId: 'zákazník-1',
            items: [['name' => 'Symfony kniha', 'qty' => 1, 'price' => 59900]],
        ));

        $this->assertNotEmpty($orderId);
        $this->assertNotNull($repo->saved);
        $this->assertSame($orderId, $repo->saved->id()->value);
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter05/ --testdox
```
Expected: FAIL — "Class PlaceOrderHandler not found"

- [ ] **Step 3: Implementuj Domain (zkopíruj vzor z Chapter04, prefix namespace Chapter05_CQRS)**

`src/Chapter05_CQRS/Domain/Order/OrderId.php`:
```php
<?php

namespace App\Chapter05_CQRS\Domain\Order;

final readonly class OrderId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) throw new \InvalidArgumentException('OrderId cannot be empty');
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
```

`src/Chapter05_CQRS/Domain/Order/Money.php`:
```php
<?php

namespace App\Chapter05_CQRS\Domain\Order;

final readonly class Money
{
    public function __construct(public readonly int $amount, public readonly string $currency) {}

    public function add(self $other): self { return new self($this->amount + $other->amount, $this->currency); }
    public function multiply(int $qty): self { return new self($this->amount * $qty, $this->currency); }
    public function formatted(): string { return number_format($this->amount / 100, 2) . ' ' . $this->currency; }
}
```

`src/Chapter05_CQRS/Domain/Order/OrderPlaced.php`:
```php
<?php

namespace App\Chapter05_CQRS\Domain\Order;

use App\Shared\Domain\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

`src/Chapter05_CQRS/Domain/Order/Order.php`:
```php
<?php

namespace App\Chapter05_CQRS\Domain\Order;

use App\Shared\Domain\AggregateRoot;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ch05_orders')]
class Order extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string')]
    private string $customerId;

    #[ORM\Column(type: 'integer')]
    private int $totalAmount = 0;

    #[ORM\Column(type: 'json')]
    private array $items = [];

    private function __construct(OrderId $id, string $customerId)
    {
        $this->id = $id->value;
        $this->customerId = $customerId;
    }

    /** @param array<array{name: string, qty: int, price: int}> $items */
    public static function place(OrderId $id, string $customerId, array $items): self
    {
        $order = new self($id, $customerId);
        foreach ($items as $item) {
            $order->items[] = $item;
            $order->totalAmount += $item['price'] * $item['qty'];
        }
        $order->record(new OrderPlaced($id->value, $customerId));
        return $order;
    }

    public function id(): OrderId { return new OrderId($this->id); }
    public function customerId(): string { return $this->customerId; }
    public function total(): Money { return new Money($this->totalAmount, 'CZK'); }
    public function items(): array { return $this->items; }
}
```

`src/Chapter05_CQRS/Domain/Repository/OrderRepositoryInterface.php`:
```php
<?php

namespace App\Chapter05_CQRS\Domain\Repository;

use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(OrderId $id): ?Order;
    /** @return Order[] */
    public function findAll(): array;
}
```

- [ ] **Step 4: Implementuj Application layer**

`src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderCommand.php`:
```php
<?php

namespace App\Chapter05_CQRS\Application\PlaceOrder;

final readonly class PlaceOrderCommand
{
    /** @param array<array{name: string, qty: int, price: int}> $items */
    public function __construct(
        public readonly string $customerId,
        public readonly array $items,
    ) {}
}
```

`src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderHandler.php`:
```php
<?php

namespace App\Chapter05_CQRS\Application\PlaceOrder;

use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PlaceOrderHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function __invoke(PlaceOrderCommand $command): string
    {
        $id = OrderId::generate();
        $order = Order::place($id, $command->customerId, $command->items);
        $this->orders->save($order);
        return $id->value;
    }
}
```

`src/Chapter05_CQRS/Application/GetOrders/OrderView.php`:
```php
<?php

namespace App\Chapter05_CQRS\Application\GetOrders;

final readonly class OrderView
{
    public function __construct(
        public readonly string $id,
        public readonly string $customerId,
        public readonly string $total,
        public readonly int $itemCount,
    ) {}
}
```

`src/Chapter05_CQRS/Application/GetOrders/GetOrdersQuery.php`:
```php
<?php

namespace App\Chapter05_CQRS\Application\GetOrders;

final readonly class GetOrdersQuery {}
```

`src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php`:
```php
<?php

namespace App\Chapter05_CQRS\Application\GetOrders;

use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetOrdersHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /** @return OrderView[] */
    public function __invoke(GetOrdersQuery $query): array
    {
        return array_map(
            fn($order) => new OrderView(
                id: substr($order->id()->value, 0, 8) . '…',
                customerId: $order->customerId(),
                total: $order->total()->formatted(),
                itemCount: count($order->items()),
            ),
            $this->orders->findAll(),
        );
    }
}
```

- [ ] **Step 5: Implementuj Infrastructure**

`src/Chapter05_CQRS/Infrastructure/Persistence/DoctrineOrderRepository.php`:
```php
<?php

namespace App\Chapter05_CQRS\Infrastructure\Persistence;

use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(Order $order): void
    {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->em->find(Order::class, $id->value);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(Order::class)->findAll();
    }
}
```

- [ ] **Step 6: Zaregistruj service + nastav Messenger routing**

Přidej do `config/services.yaml`:
```yaml
    App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface:
        class: App\Chapter05_CQRS\Infrastructure\Persistence\DoctrineOrderRepository
```

Přidej do `config/packages/messenger.yaml` pod `routing:`:
```yaml
            'App\Chapter05_CQRS\Application\PlaceOrder\PlaceOrderCommand': sync
            'App\Chapter05_CQRS\Application\GetOrders\GetOrdersQuery': sync
```

- [ ] **Step 7: Vygeneruj migraci**

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```
Expected: vytvoří tabulku `ch05_orders`.

- [ ] **Step 8: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter05/ --testdox
```
Expected: PASS ✓ Places order and returns id

- [ ] **Step 9: Implementuj controller**

`src/Chapter05_CQRS/UI/Chapter05Controller.php`:
```php
<?php

namespace App\Chapter05_CQRS\UI;

use App\Chapter05_CQRS\Application\GetOrders\GetOrdersQuery;
use App\Chapter05_CQRS\Application\PlaceOrder\PlaceOrderCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter05Controller extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {}

    #[Route('/examples/cqrs', name: 'chapter05')]
    public function index(Request $request): Response
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $envelope = $this->commandBus->dispatch(new PlaceOrderCommand(
                customerId: $request->request->get('customer', 'student-1'),
                items: [[
                    'name' => $request->request->get('product', 'Produkt'),
                    'qty' => max(1, (int) $request->request->get('qty', 1)),
                    'price' => (int) ($request->request->get('price', 100) * 100),
                ]],
            ));
            $orderId = $envelope->last(HandledStamp::class)?->getResult();
            $result = 'Objednávka zadána přes Command bus. ID: ' . substr($orderId, 0, 8) . '…';
        }

        $orders = $this->queryBus->dispatch(new GetOrdersQuery())
            ->last(HandledStamp::class)
            ?->getResult() ?? [];

        return $this->render('examples/chapter05/index.html.twig', [
            'orders' => $orders,
            'result' => $result,
        ]);
    }
}
```

- [ ] **Step 10: Vytvoř template**

`templates/examples/chapter05/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: CQRS v Symfony{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('cqrs') }}"><strong>CQRS v Symfony 8</strong></a>
    </div>
    <h1>Ukázka: CQRS — Commands a Queries</h1>
    <p>Zápis jde přes <code>PlaceOrderCommand</code>, čtení přes <code>GetOrdersQuery</code> — dva oddělené toky dat.</p>

    {% if result %}<div class="alert alert-success">{{ result }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-5">
            <h3>Command (Write side)</h3>
            <form method="post">
                <div class="mb-2"><input type="text" name="customer" value="zákazník-1" class="form-control" placeholder="ID zákazníka"></div>
                <div class="mb-2"><input type="text" name="product" value="Symfony kurz" class="form-control" placeholder="Produkt"></div>
                <div class="mb-2"><input type="number" name="qty" value="1" min="1" class="form-control" placeholder="Množství"></div>
                <div class="mb-2"><input type="number" name="price" value="999" class="form-control" placeholder="Cena (CZK)"></div>
                <button class="btn btn-primary">Dispatch PlaceOrderCommand</button>
            </form>
        </div>
        <div class="col-md-7">
            <h3>Query (Read side) — OrderView</h3>
            {% if orders is empty %}
                <p class="text-muted">Zatím žádné objednávky. Zadej první příkaz.</p>
            {% else %}
                <table class="table table-sm">
                    <thead><tr><th>ID</th><th>Zákazník</th><th>Položky</th><th>Celkem</th></tr></thead>
                    <tbody>
                    {% for order in orders %}
                        <tr>
                            <td><code>{{ order.id }}</code></td>
                            <td>{{ order.customerId }}</td>
                            <td>{{ order.itemCount }}</td>
                            <td>{{ order.total }}</td>
                        </tr>
                    {% endfor %}
                    </tbody>
                </table>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
```

- [ ] **Step 11: Commit**

```bash
git add src/Chapter05_CQRS/ tests/Chapter05/ templates/examples/chapter05/ config/
git commit -m "feat: přidat Chapter05 — CQRS (Messenger, Commands, Queries, ReadModel)"
```

---

### Task 7: Chapter06 — Event Sourcing

**Files:**
- Create: `src/Chapter06_EventSourcing/Domain/Order/Events/` (OrderPlaced, OrderConfirmed, OrderCancelled)
- Create: `src/Chapter06_EventSourcing/Domain/Order/Order.php` (event-sourced)
- Create: `src/Chapter06_EventSourcing/Infrastructure/EventStore/EventStoreInterface.php`
- Create: `src/Chapter06_EventSourcing/Infrastructure/EventStore/DoctrineEventStore.php`
- Create: `src/Chapter06_EventSourcing/Infrastructure/EventStore/StoredEvent.php` (Doctrine entity)
- Create: `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`
- Create: `templates/examples/chapter06/index.html.twig`
- Test: `tests/Chapter06/Domain/EventSourcedOrderTest.php`

- [ ] **Step 1: Napiš failing testy**

`tests/Chapter06/Domain/EventSourcedOrderTest.php`:
```php
<?php

namespace App\Tests\Chapter06\Domain;

use App\Chapter06_EventSourcing\Domain\Order\Events\OrderCancelled;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderConfirmed;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderPlaced;
use App\Chapter06_EventSourcing\Domain\Order\Order;
use App\Chapter06_EventSourcing\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

final class EventSourcedOrderTest extends TestCase
{
    public function test_order_state_reconstructed_from_events(): void
    {
        $id = OrderId::generate();
        $events = [
            new OrderPlaced($id->value, 'zákazník-1', 59900),
            new OrderConfirmed($id->value),
        ];

        $order = Order::reconstruct($id, $events);
        $this->assertSame('confirmed', $order->status());
        $this->assertSame(59900, $order->totalAmount());
    }

    public function test_cancelled_order_has_cancelled_status(): void
    {
        $id = OrderId::generate();
        $order = Order::reconstruct($id, [
            new OrderPlaced($id->value, 'zákazník-1', 10000),
            new OrderCancelled($id->value, 'Zákazník si to rozmyslel'),
        ]);

        $this->assertSame('cancelled', $order->status());
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter06/ --testdox
```
Expected: FAIL — "Class Order not found"

- [ ] **Step 3: Implementuj Domain Events**

`src/Chapter06_EventSourcing/Domain/Order/OrderId.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Domain\Order;

final readonly class OrderId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) throw new \InvalidArgumentException('OrderId cannot be empty');
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
```

`src/Chapter06_EventSourcing/Domain/Order/Events/OrderPlaced.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly int $totalAmount,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

`src/Chapter06_EventSourcing/Domain/Order/Events/OrderConfirmed.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderConfirmed implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

`src/Chapter06_EventSourcing/Domain/Order/Events/OrderCancelled.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderCancelled implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $reason,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

- [ ] **Step 4: Implementuj event-sourced Order**

`src/Chapter06_EventSourcing/Domain/Order/Order.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Domain\Order;

use App\Chapter06_EventSourcing\Domain\Order\Events\OrderCancelled;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderConfirmed;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderPlaced;
use App\Shared\Domain\DomainEvent;

final class Order
{
    private string $status = 'pending';
    private int $totalAmount = 0;
    private string $customerId = '';
    /** @var DomainEvent[] */
    private array $uncommittedEvents = [];

    private function __construct(private readonly OrderId $id) {}

    public static function place(OrderId $id, string $customerId, int $totalAmount): self
    {
        $order = new self($id);
        $event = new OrderPlaced($id->value, $customerId, $totalAmount);
        $order->apply($event);
        $order->uncommittedEvents[] = $event;
        return $order;
    }

    /** @param DomainEvent[] $events */
    public static function reconstruct(OrderId $id, array $events): self
    {
        $order = new self($id);
        foreach ($events as $event) {
            $order->apply($event);
        }
        return $order;
    }

    public function confirm(): void
    {
        $event = new OrderConfirmed($this->id->value);
        $this->apply($event);
        $this->uncommittedEvents[] = $event;
    }

    public function cancel(string $reason): void
    {
        $event = new OrderCancelled($this->id->value, $reason);
        $this->apply($event);
        $this->uncommittedEvents[] = $event;
    }

    private function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof OrderPlaced => (function () use ($event) {
                $this->status = 'pending';
                $this->totalAmount = $event->totalAmount;
                $this->customerId = $event->customerId;
            })(),
            $event instanceof OrderConfirmed => (function () { $this->status = 'confirmed'; })(),
            $event instanceof OrderCancelled => (function () { $this->status = 'cancelled'; })(),
            default => null,
        };
    }

    public function id(): OrderId { return $this->id; }
    public function status(): string { return $this->status; }
    public function totalAmount(): int { return $this->totalAmount; }
    public function customerId(): string { return $this->customerId; }

    /** @return DomainEvent[] */
    public function pullUncommittedEvents(): array
    {
        $events = $this->uncommittedEvents;
        $this->uncommittedEvents = [];
        return $events;
    }
}
```

- [ ] **Step 5: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter06/ --testdox
```
Expected: PASS ✓ 2 testy

- [ ] **Step 6: Implementuj EventStore**

`src/Chapter06_EventSourcing/Infrastructure/EventStore/EventStoreInterface.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;

interface EventStoreInterface
{
    /** @param DomainEvent[] $events */
    public function append(string $aggregateId, array $events): void;

    /** @return DomainEvent[] */
    public function load(string $aggregateId): array;
}
```

`src/Chapter06_EventSourcing/Infrastructure/EventStore/StoredEvent.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ch06_event_store')]
class StoredEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(type: 'string')]
    private string $eventClass;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    public function __construct(string $aggregateId, string $eventClass, array $payload, \DateTimeImmutable $occurredAt)
    {
        $this->aggregateId = $aggregateId;
        $this->eventClass = $eventClass;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public function aggregateId(): string { return $this->aggregateId; }
    public function eventClass(): string { return $this->eventClass; }
    public function payload(): array { return $this->payload; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

`src/Chapter06_EventSourcing/Infrastructure/EventStore/DoctrineEventStore.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEventStore implements EventStoreInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function append(string $aggregateId, array $events): void
    {
        foreach ($events as $event) {
            $stored = new StoredEvent(
                $aggregateId,
                get_class($event),
                (array) $event,
                $event->occurredAt(),
            );
            $this->em->persist($stored);
        }
        $this->em->flush();
    }

    public function load(string $aggregateId): array
    {
        $stored = $this->em->getRepository(StoredEvent::class)
            ->findBy(['aggregateId' => $aggregateId]);

        return array_map(function (StoredEvent $s) {
            $class = $s->eventClass();
            $payload = $s->payload();
            return new $class(...array_values($payload));
        }, $stored);
    }
}
```

- [ ] **Step 7: Vygeneruj migraci**

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```
Expected: vytvoří tabulku `ch06_event_store`.

- [ ] **Step 8: Implementuj controller**

`src/Chapter06_EventSourcing/UI/Chapter06Controller.php`:
```php
<?php

namespace App\Chapter06_EventSourcing\UI;

use App\Chapter06_EventSourcing\Domain\Order\Order;
use App\Chapter06_EventSourcing\Domain\Order\OrderId;
use App\Chapter06_EventSourcing\Infrastructure\EventStore\DoctrineEventStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter06Controller extends AbstractController
{
    public function __construct(
        private readonly DoctrineEventStore $eventStore,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/examples/event-sourcing', name: 'chapter06')]
    public function index(Request $request): Response
    {
        $result = null;
        $history = [];

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $orderId = $request->request->get('order_id') ?: OrderId::generate()->value;

            if ($action === 'place') {
                $order = Order::place(
                    new OrderId($orderId),
                    'zákazník-1',
                    (int) ($request->request->get('price', 599) * 100),
                );
                $this->eventStore->append($orderId, $order->pullUncommittedEvents());
                $result = 'Objednávka zadána. ID: ' . substr($orderId, 0, 8) . '…';
            } elseif ($action === 'confirm' && $orderId) {
                $events = $this->eventStore->load($orderId);
                $order = Order::reconstruct(new OrderId($orderId), $events);
                $order->confirm();
                $this->eventStore->append($orderId, $order->pullUncommittedEvents());
                $result = 'Objednávka potvrzena. Rekonstruována z ' . count($events) . ' eventů.';
            } elseif ($action === 'cancel' && $orderId) {
                $events = $this->eventStore->load($orderId);
                $order = Order::reconstruct(new OrderId($orderId), $events);
                $order->cancel('Zákazník si to rozmyslel');
                $this->eventStore->append($orderId, $order->pullUncommittedEvents());
                $result = 'Objednávka zrušena.';
            }

            if ($orderId) {
                $history = $this->eventStore->load($orderId);
            }
        }

        return $this->render('examples/chapter06/index.html.twig', [
            'result' => $result,
            'history' => $history,
        ]);
    }
}
```

- [ ] **Step 9: Vytvoř template**

`templates/examples/chapter06/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Event Sourcing{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('event_sourcing') }}"><strong>Event Sourcing v DDD a Symfony</strong></a>
    </div>
    <h1>Ukázka: Event Sourcing</h1>
    <p>Stav objednávky není uložen jako řádek v DB — je <strong>rekonstruován z event logu</strong>.</p>

    {% if result %}<div class="alert alert-success">{{ result }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-5">
            <h3>Akce</h3>
            <form method="post" class="mb-3">
                <input type="hidden" name="action" value="place">
                <div class="mb-2"><input type="number" name="price" value="599" class="form-control" placeholder="Cena (CZK)"></div>
                <button class="btn btn-primary w-100">1. Zadat objednávku (OrderPlaced)</button>
            </form>
            <p class="text-muted small">Po zadání zkopíruj ID z výsledku a vlož níže:</p>
            <form method="post" class="mb-2">
                <input type="hidden" name="action" value="confirm">
                <div class="mb-2"><input type="text" name="order_id" class="form-control" placeholder="Order ID"></div>
                <button class="btn btn-success w-100">2. Potvrdit (OrderConfirmed)</button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="cancel">
                <div class="mb-2"><input type="text" name="order_id" class="form-control" placeholder="Order ID"></div>
                <button class="btn btn-warning w-100">3. Zrušit (OrderCancelled)</button>
            </form>
        </div>
        <div class="col-md-7">
            <h3>Event Log (EventStore)</h3>
            {% if history is empty %}
                <p class="text-muted">Zadej objednávku a vlož Order ID pro zobrazení event logu.</p>
            {% else %}
                <ol>
                {% for event in history %}
                    <li><code>{{ event|class_name }}</code> @ {{ event.occurredAt()|date('H:i:s') }}</li>
                {% endfor %}
                </ol>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
```

Poznámka: filtr `class_name` je custom Twig extension — přidej do `src/Twig/ClassNameExtension.php`:
```php
<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ClassNameExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('class_name', fn($obj) => (new \ReflectionClass($obj))->getShortName())];
    }
}
```

- [ ] **Step 10: Commit**

```bash
git add src/Chapter06_EventSourcing/ tests/Chapter06/ templates/examples/chapter06/ src/Twig/
git commit -m "feat: přidat Chapter06 — Event Sourcing (EventStore, rekonstrukce ze eventů)"
```

---

### Task 8: Chapter07 — Ságy a Process Managery

**Files:**
- Create: `src/Chapter07_Sagas/Application/OrderFulfillmentSaga.php`
- Create: `src/Chapter07_Sagas/Application/Steps/ReserveStock.php`
- Create: `src/Chapter07_Sagas/Application/Steps/ProcessPayment.php`
- Create: `src/Chapter07_Sagas/Application/Steps/ShipOrder.php`
- Create: `src/Chapter07_Sagas/UI/Chapter07Controller.php`
- Create: `templates/examples/chapter07/index.html.twig`
- Test: `tests/Chapter07/Application/OrderFulfillmentSagaTest.php`

Tato ukázka simuluje ságu bez persistence — ukazuje orchestraci a kompenzační transakce in-memory.

- [ ] **Step 1: Napiš failing testy**

`tests/Chapter07/Application/OrderFulfillmentSagaTest.php`:
```php
<?php

namespace App\Tests\Chapter07\Application;

use App\Chapter07_Sagas\Application\OrderFulfillmentSaga;
use PHPUnit\Framework\TestCase;

final class OrderFulfillmentSagaTest extends TestCase
{
    public function test_successful_fulfillment(): void
    {
        $saga = new OrderFulfillmentSaga();
        $log = $saga->execute('order-1', stockAvailable: true, paymentSuccess: true);

        $this->assertContains('ReserveStock: OK', $log);
        $this->assertContains('ProcessPayment: OK', $log);
        $this->assertContains('ShipOrder: OK', $log);
    }

    public function test_payment_failure_triggers_compensation(): void
    {
        $saga = new OrderFulfillmentSaga();
        $log = $saga->execute('order-1', stockAvailable: true, paymentSuccess: false);

        $this->assertContains('ProcessPayment: FAILED', $log);
        $this->assertContains('ReserveStock: COMPENSATED (stock released)', $log);
        $this->assertNotContains('ShipOrder: OK', $log);
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter07/ --testdox
```
Expected: FAIL — "Class OrderFulfillmentSaga not found"

- [ ] **Step 3: Implementuj Ságu**

`src/Chapter07_Sagas/Application/OrderFulfillmentSaga.php`:
```php
<?php

namespace App\Chapter07_Sagas\Application;

final class OrderFulfillmentSaga
{
    /** @return string[] */
    public function execute(string $orderId, bool $stockAvailable, bool $paymentSuccess): array
    {
        $log = [];
        $compensations = [];

        // Krok 1: Rezervace skladu
        if (!$stockAvailable) {
            $log[] = 'ReserveStock: FAILED (out of stock)';
            return $log;
        }
        $log[] = 'ReserveStock: OK';
        $compensations[] = function () use (&$log) {
            $log[] = 'ReserveStock: COMPENSATED (stock released)';
        };

        // Krok 2: Platba
        if (!$paymentSuccess) {
            $log[] = 'ProcessPayment: FAILED';
            foreach (array_reverse($compensations) as $compensate) {
                $compensate();
            }
            return $log;
        }
        $log[] = 'ProcessPayment: OK';
        $compensations[] = function () use (&$log) {
            $log[] = 'ProcessPayment: COMPENSATED (payment refunded)';
        };

        // Krok 3: Odeslání
        $log[] = 'ShipOrder: OK';

        return $log;
    }
}
```

- [ ] **Step 4: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter07/ --testdox
```
Expected: PASS ✓ 2 testy

- [ ] **Step 5: Implementuj controller + template**

`src/Chapter07_Sagas/UI/Chapter07Controller.php`:
```php
<?php

namespace App\Chapter07_Sagas\UI;

use App\Chapter07_Sagas\Application\OrderFulfillmentSaga;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter07Controller extends AbstractController
{
    #[Route('/examples/sagy', name: 'chapter07')]
    public function index(Request $request): Response
    {
        $log = [];
        if ($request->isMethod('POST')) {
            $saga = new OrderFulfillmentSaga();
            $log = $saga->execute(
                'order-' . rand(1, 999),
                stockAvailable: $request->request->getBoolean('stock', true),
                paymentSuccess: $request->request->getBoolean('payment', true),
            );
        }
        return $this->render('examples/chapter07/index.html.twig', ['log' => $log]);
    }
}
```

`templates/examples/chapter07/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Ságy a Process Managery{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('sagas') }}"><strong>Ságy a Process Managery</strong></a>
    </div>
    <h1>Ukázka: Orchestrační sága s kompenzacemi</h1>
    <p>Sága řídí dlouhotrvající transakci přes více kroků. Při chybě spustí kompenzační akce.</p>

    <form method="post" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Sklad dostupný?</label>
            <select name="stock" class="form-select" style="width:auto">
                <option value="1">Ano</option>
                <option value="0">Ne (sága selže v kroku 1)</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Platba úspěšná?</label>
            <select name="payment" class="form-select" style="width:auto">
                <option value="1">Ano</option>
                <option value="0">Ne (sága selže v kroku 2, kompenzuje krok 1)</option>
            </select>
        </div>
        <button class="btn btn-primary">Spustit ságu</button>
    </form>

    {% if log is not empty %}
    <h3>Průběh ságy:</h3>
    <ol>
        {% for entry in log %}
        <li class="{{ 'FAILED' in entry ? 'text-danger' : ('COMPENSATED' in entry ? 'text-warning' : 'text-success') }}">
            {{ entry }}
        </li>
        {% endfor %}
    </ol>
    {% endif %}
</div>
{% endblock %}
```

- [ ] **Step 6: Commit**

```bash
git add src/Chapter07_Sagas/ tests/Chapter07/ templates/examples/chapter07/
git commit -m "feat: přidat Chapter07 — Ságy (orchestrace, kompenzační transakce)"
```

---

### Task 9: Chapter08 — Testování DDD kódu

**Files:**
- Create: `src/Chapter08_Testing/Domain/Task/TaskId.php`
- Create: `src/Chapter08_Testing/Domain/Task/TaskStatus.php`
- Create: `src/Chapter08_Testing/Domain/Task/Task.php`
- Create: `src/Chapter08_Testing/Domain/Task/TaskAssigned.php`
- Create: `src/Chapter08_Testing/UI/Chapter08Controller.php`
- Create: `templates/examples/chapter08/index.html.twig`
- Test: `tests/Chapter08/Domain/TaskTest.php`

Tato kapitola ukazuje různé druhy testů: unit testy doménových tříd, testování domain events, testování invariantů.

- [ ] **Step 1: Napiš failing testy (jsou zároveň ukázkou pro studenty)**

`tests/Chapter08/Domain/TaskTest.php`:
```php
<?php

namespace App\Tests\Chapter08\Domain;

use App\Chapter08_Testing\Domain\Task\Task;
use App\Chapter08_Testing\Domain\Task\TaskId;
use App\Chapter08_Testing\Domain\Task\TaskAssigned;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function test_new_task_is_todo(): void
    {
        $task = Task::create(TaskId::generate(), 'Implementovat CQRS', 'projekt-1');
        $this->assertTrue($task->status()->isTodo());
    }

    public function test_assigning_task_raises_domain_event(): void
    {
        $task = Task::create(TaskId::generate(), 'Implementovat CQRS', 'projekt-1');
        $task->assignTo('member-42');

        $events = $task->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskAssigned::class, $events[0]);
        $this->assertSame('member-42', $events[0]->assignedTo);
    }

    public function test_completed_task_cannot_be_reassigned(): void
    {
        $this->expectException(\DomainException::class);
        $task = Task::create(TaskId::generate(), 'Hotový úkol', 'projekt-1');
        $task->assignTo('member-1');
        $task->complete();
        $task->assignTo('member-2');
    }

    public function test_task_title_cannot_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Task::create(TaskId::generate(), '', 'projekt-1');
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter08/ --testdox
```
Expected: FAIL — "Class Task not found"

- [ ] **Step 3: Implementuj doménový model**

`src/Chapter08_Testing/Domain/Task/TaskId.php`:
```php
<?php

namespace App\Chapter08_Testing\Domain\Task;

final readonly class TaskId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) throw new \InvalidArgumentException('TaskId cannot be empty');
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
```

`src/Chapter08_Testing/Domain/Task/TaskStatus.php`:
```php
<?php

namespace App\Chapter08_Testing\Domain\Task;

final readonly class TaskStatus
{
    private function __construct(private readonly string $value) {}

    public static function todo(): self { return new self('todo'); }
    public static function inProgress(): self { return new self('in_progress'); }
    public static function done(): self { return new self('done'); }

    public function isTodo(): bool { return $this->value === 'todo'; }
    public function isDone(): bool { return $this->value === 'done'; }
    public function value(): string { return $this->value; }
}
```

`src/Chapter08_Testing/Domain/Task/TaskAssigned.php`:
```php
<?php

namespace App\Chapter08_Testing\Domain\Task;

use App\Shared\Domain\DomainEvent;

final readonly class TaskAssigned implements DomainEvent
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $assignedTo,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

`src/Chapter08_Testing/Domain/Task/Task.php`:
```php
<?php

namespace App\Chapter08_Testing\Domain\Task;

use App\Shared\Domain\AggregateRoot;

final class Task extends AggregateRoot
{
    private TaskStatus $status;
    private ?string $assignedTo = null;

    private function __construct(
        private readonly TaskId $id,
        private readonly string $title,
        private readonly string $projectId,
    ) {
        if (empty($title)) {
            throw new \InvalidArgumentException('Task title cannot be empty');
        }
        $this->status = TaskStatus::todo();
    }

    public static function create(TaskId $id, string $title, string $projectId): self
    {
        return new self($id, $title, $projectId);
    }

    public function assignTo(string $memberId): void
    {
        if ($this->status->isDone()) {
            throw new \DomainException('Cannot reassign a completed task');
        }
        $this->assignedTo = $memberId;
        $this->status = TaskStatus::inProgress();
        $this->record(new TaskAssigned($this->id->value, $memberId));
    }

    public function complete(): void
    {
        $this->status = TaskStatus::done();
    }

    public function id(): TaskId { return $this->id; }
    public function title(): string { return $this->title; }
    public function status(): TaskStatus { return $this->status; }
    public function assignedTo(): ?string { return $this->assignedTo; }
}
```

- [ ] **Step 4: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter08/ --testdox
```
Expected: PASS ✓ 4 testy

- [ ] **Step 5: Implementuj controller + template**

`src/Chapter08_Testing/UI/Chapter08Controller.php`:
```php
<?php

namespace App\Chapter08_Testing\UI;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter08Controller extends AbstractController
{
    #[Route('/examples/testovani', name: 'chapter08')]
    public function index(): Response
    {
        return $this->render('examples/chapter08/index.html.twig');
    }
}
```

`templates/examples/chapter08/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Testování DDD kódu{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('testing_ddd') }}"><strong>Testování DDD kódu v Symfony</strong></a>
    </div>
    <h1>Ukázka: Testování doménového modelu</h1>
    <p>Doménové třídy jsou testovatelné bez frameworku — žádné mocking, žádná DB.</p>

    <h3>Spusť testy pro tuto kapitolu:</h3>
    <pre class="bg-dark text-light p-3 rounded">./vendor/bin/phpunit tests/Chapter08/ --testdox</pre>

    <h3>Co testy ověřují:</h3>
    <ul>
        <li>Nový úkol má stav <code>todo</code></li>
        <li>Přiřazení úkolu vyvolá domain event <code>TaskAssigned</code></li>
        <li>Dokončený úkol nelze přeřadit → <code>DomainException</code></li>
        <li>Název úkolu nesmí být prázdný → <code>InvalidArgumentException</code></li>
    </ul>

    <h3>Zdrojový kód testů:</h3>
    <p><a href="https://github.com/your-org/ddd-symfony-examples/blob/main/tests/Chapter08/Domain/TaskTest.php" class="btn btn-outline-secondary btn-sm">Zobrazit na GitHubu</a></p>
</div>
{% endblock %}
```

- [ ] **Step 6: Commit**

```bash
git add src/Chapter08_Testing/ tests/Chapter08/ templates/examples/chapter08/
git commit -m "feat: přidat Chapter08 — Testování DDD (unit testy doménového modelu)"
```

---

### Task 10: Chapter09 — Migrace z CRUD

**Files:**
- Create: `src/Chapter09_Migration/Crud/TaskCrudController.php` (before — CRUD styl)
- Create: `src/Chapter09_Migration/Domain/Task/` (TaskId, TaskStatus, Task — DDD verze)
- Create: `src/Chapter09_Migration/UI/Chapter09Controller.php`
- Create: `templates/examples/chapter09/index.html.twig`
- Test: `tests/Chapter09/Domain/TaskMigrationTest.php`

- [ ] **Step 1: Napiš failing testy**

`tests/Chapter09/Domain/TaskMigrationTest.php`:
```php
<?php

namespace App\Tests\Chapter09\Domain;

use App\Chapter09_Migration\Domain\Task\Task;
use App\Chapter09_Migration\Domain\Task\TaskId;
use PHPUnit\Framework\TestCase;

final class TaskMigrationTest extends TestCase
{
    public function test_ddd_task_encapsulates_status_transition(): void
    {
        $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
        $task->start('member-1');

        $this->assertTrue($task->status()->isInProgress());
        $this->assertSame('member-1', $task->assignedTo());
    }

    public function test_ddd_task_prevents_invalid_transition(): void
    {
        $this->expectException(\DomainException::class);
        $task = Task::create(TaskId::generate(), 'Hotový úkol', 'projekt-1');
        $task->complete();       // nelze dokončit úkol, který nebyl zahájen
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter09/ --testdox
```
Expected: FAIL

- [ ] **Step 3: Implementuj DDD verzi Tasku**

`src/Chapter09_Migration/Domain/Task/TaskId.php`:
```php
<?php

namespace App\Chapter09_Migration\Domain\Task;

final readonly class TaskId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) throw new \InvalidArgumentException('TaskId cannot be empty');
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
```

`src/Chapter09_Migration/Domain/Task/TaskStatus.php`:
```php
<?php

namespace App\Chapter09_Migration\Domain\Task;

final readonly class TaskStatus
{
    private function __construct(private readonly string $value) {}

    public static function todo(): self { return new self('todo'); }
    public static function inProgress(): self { return new self('in_progress'); }
    public static function done(): self { return new self('done'); }

    public function isTodo(): bool { return $this->value === 'todo'; }
    public function isInProgress(): bool { return $this->value === 'in_progress'; }
    public function isDone(): bool { return $this->value === 'done'; }
    public function value(): string { return $this->value; }
}
```

`src/Chapter09_Migration/Domain/Task/Task.php`:
```php
<?php

namespace App\Chapter09_Migration\Domain\Task;

final class Task
{
    private TaskStatus $status;
    private ?string $assignedTo = null;

    private function __construct(
        private readonly TaskId $id,
        private readonly string $title,
        private readonly string $projectId,
    ) {
        $this->status = TaskStatus::todo();
    }

    public static function create(TaskId $id, string $title, string $projectId): self
    {
        return new self($id, $title, $projectId);
    }

    public function start(string $memberId): void
    {
        if (!$this->status->isTodo()) {
            throw new \DomainException('Task is already started or done');
        }
        $this->assignedTo = $memberId;
        $this->status = TaskStatus::inProgress();
    }

    public function complete(): void
    {
        if (!$this->status->isInProgress()) {
            throw new \DomainException('Task must be in progress before completing');
        }
        $this->status = TaskStatus::done();
    }

    public function id(): TaskId { return $this->id; }
    public function title(): string { return $this->title; }
    public function status(): TaskStatus { return $this->status; }
    public function assignedTo(): ?string { return $this->assignedTo; }
}
```

- [ ] **Step 4: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter09/ --testdox
```
Expected: PASS ✓ 2 testy

- [ ] **Step 5: Implementuj controller + template**

`src/Chapter09_Migration/UI/Chapter09Controller.php`:
```php
<?php

namespace App\Chapter09_Migration\UI;

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
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            try {
                $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
                if ($action === 'valid') {
                    $task->start('member-1');
                    $task->complete();
                    $result = 'DDD Task: todo → in_progress → done ✓';
                } elseif ($action === 'invalid') {
                    $task->complete(); // přeskočí start()
                }
            } catch (\DomainException $e) {
                $error = 'DomainException zachycena: ' . $e->getMessage();
            }
        }

        return $this->render('examples/chapter09/index.html.twig', [
            'result' => $result,
            'error' => $error,
        ]);
    }
}
```

`templates/examples/chapter09/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Migrace z CRUD na DDD{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('migration_from_crud') }}"><strong>Migrace z CRUD architektury na DDD</strong></a>
    </div>
    <h1>Ukázka: CRUD → DDD</h1>

    {% if result %}<div class="alert alert-success">{{ result }}</div>{% endif %}
    {% if error %}<div class="alert alert-danger">{{ error }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-6">
            <h3>CRUD přístup (problém)</h3>
            <pre class="bg-light p-3 rounded"><code>// Kdokoliv může nastavit libovolný stav:
$task->setStatus('done');
// Žádná validace přechodů!
// Logika je v controlleru/service, ne v objektu.</code></pre>
        </div>
        <div class="col-md-6">
            <h3>DDD přístup (řešení)</h3>
            <pre class="bg-light p-3 rounded"><code>// Doménový model chrání své invarianty:
$task->start('member-1');   // todo → in_progress
$task->complete();          // in_progress → done
// Přeskočení start() vyvolá DomainException.</code></pre>
        </div>
    </div>

    <div class="mt-4">
        <form method="post" class="d-inline me-2">
            <input type="hidden" name="action" value="valid">
            <button class="btn btn-success">Správný přechod (start → complete)</button>
        </form>
        <form method="post" class="d-inline">
            <input type="hidden" name="action" value="invalid">
            <button class="btn btn-warning">Neplatný přechod (complete bez start) → DomainException</button>
        </form>
    </div>
</div>
{% endblock %}
```

- [ ] **Step 6: Commit**

```bash
git add src/Chapter09_Migration/ tests/Chapter09/ templates/examples/chapter09/
git commit -m "feat: přidat Chapter09 — Migrace z CRUD (ochrana invariantů vs. settery)"
```

---

### Task 11: Index stránka (rozcestník)

**Files:**
- Create: `src/UI/ExamplesIndexController.php`
- Create: `templates/examples/index.html.twig`

- [ ] **Step 1: Vytvoř controller**

`src/UI/ExamplesIndexController.php`:
```php
<?php

namespace App\UI;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExamplesIndexController extends AbstractController
{
    #[Route('/examples', name: 'examples_index')]
    public function index(): Response
    {
        return $this->render('examples/index.html.twig', [
            'chapters' => [
                ['route' => 'chapter01', 'num' => 1,  'title' => 'Co je DDD',               'desc' => 'Čistý doménový model — košík bez DB'],
                ['route' => 'chapter03', 'num' => 3,  'title' => 'Základní koncepty',        'desc' => 'Entity, Value Objects, Agregát'],
                ['route' => 'chapter04', 'num' => 4,  'title' => 'Implementace v Symfony',   'desc' => 'Doctrine, Domain Events, Domain Service'],
                ['route' => 'chapter05', 'num' => 5,  'title' => 'CQRS',                     'desc' => 'Commands, Queries, Symfony Messenger'],
                ['route' => 'chapter06', 'num' => 6,  'title' => 'Event Sourcing',            'desc' => 'EventStore, rekonstrukce ze eventů'],
                ['route' => 'chapter07', 'num' => 7,  'title' => 'Ságy',                     'desc' => 'Orchestrace, kompenzační transakce'],
                ['route' => 'chapter08', 'num' => 8,  'title' => 'Testování',                'desc' => 'Unit testy doménových tříd'],
                ['route' => 'chapter09', 'num' => 9,  'title' => 'Migrace z CRUD',           'desc' => 'Ochrana invariantů vs. settery'],
            ],
        ]);
    }
}
```

- [ ] **Step 2: Vytvoř template**

`templates/examples/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Živé ukázky DDD v Symfony{% endblock %}
{% block body %}
<div class="container mt-4">
    <h1>Živé ukázky DDD v Symfony</h1>
    <p class="lead">Ke každé kapitole příručky <a href="/">ddd-symfony.cz</a> patří spustitelná ukázka kódu.</p>

    <div class="row g-4 mt-2">
        {% for chapter in chapters %}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="badge bg-primary me-2">{{ chapter.num }}</span>
                        {{ chapter.title }}
                    </h5>
                    <p class="card-text text-muted">{{ chapter.desc }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ path(chapter.route) }}" class="btn btn-primary btn-sm">Spustit ukázku</a>
                </div>
            </div>
        </div>
        {% endfor %}
    </div>
</div>
{% endblock %}
```

- [ ] **Step 3: Commit**

```bash
git add src/UI/ templates/examples/index.html.twig
git commit -m "feat: přidat index stránku s rozcestníkem ukázek"
```

---

### Task 12: Makefile + README

**Files:**
- Create: `Makefile`
- Create: `README.md`

- [ ] **Step 1: Vytvoř Makefile**

`Makefile`:
```makefile
.PHONY: install test reset

install:
	composer install
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction

test:
	./vendor/bin/phpunit --testdox

reset:
	rm -f var/data.db
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate --no-interaction
```

- [ ] **Step 2: Vytvoř README.md**

`README.md`:
```markdown
# ddd-symfony-examples

Živé, spustitelné ukázky Domain-Driven Design v Symfony 8.

Součást příručky **[DDD v Symfony](https://ddd-symfony.cz)**.

## Požadavky

- PHP 8.3+
- Composer
- [Symfony CLI](https://symfony.com/download)

## Spuštění

```bash
git clone https://github.com/your-org/ddd-symfony-examples
cd ddd-symfony-examples
make install
symfony server:start
```

Otevři **http://localhost:8000/examples**

## Obsah

| Kapitola | Ukázka | URL |
|---|---|---|
| 1 | Co je DDD — čistý doménový model | `/examples/co-je-ddd` |
| 3 | Základní koncepty — Entity, VO, Agregát | `/examples/zakladni-koncepty` |
| 4 | Implementace — Doctrine, Domain Events | `/examples/implementace` |
| 5 | CQRS — Commands, Queries, Messenger | `/examples/cqrs` |
| 6 | Event Sourcing — EventStore | `/examples/event-sourcing` |
| 7 | Ságy — orchestrace, kompenzace | `/examples/sagy` |
| 8 | Testování — unit testy domény | `/examples/testovani` |
| 9 | Migrace z CRUD | `/examples/migrace-z-crud` |

## Testy

```bash
make test
```
```

- [ ] **Step 3: Commit**

```bash
git add Makefile README.md
git commit -m "docs: přidat Makefile a README"
```

---

### Task 13: Propojení s příručkou ddd-v-symfony

Tento task se provádí v repozitáři **ddd-v-symfony** (ne v ddd-symfony-examples).

**Files v ddd-v-symfony:**
- Modify: `templates/ddd/basic_concepts.html.twig`
- Modify: `templates/ddd/implementation_in_symfony.html.twig`
- Modify: `templates/ddd/cqrs.html.twig`
- Modify: `templates/ddd/event_sourcing.html.twig`
- Modify: `templates/ddd/sagas.html.twig`
- Modify: `templates/ddd/testing_ddd.html.twig`
- Modify: `templates/ddd/migration_from_crud.html.twig`
- Modify: `templates/ddd/what_is_ddd.html.twig`

Do každé z výše uvedených šablon přidej banner **těsně za `<h1>` tag**:

```twig
<div class="alert alert-secondary d-flex align-items-center gap-2 mt-3" role="note">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-code-slash" viewBox="0 0 16 16">
        <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0m6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0"/>
    </svg>
    <span>
        Ke kapitole patří <strong>živá ukázka kódu</strong>:
        <a href="https://github.com/your-org/ddd-symfony-examples/tree/main/src/{ChapterXX_TopicName}" class="alert-link" target="_blank" rel="noopener">
            Zobrazit na GitHubu →
        </a>
    </span>
</div>
```

Nahraď `{ChapterXX_TopicName}` správnou složkou pro každou šablonu:

| Šablona | Složka |
|---|---|
| `what_is_ddd.html.twig` | `Chapter01_WhatIsDDD` |
| `basic_concepts.html.twig` | `Chapter03_BasicConcepts` |
| `implementation_in_symfony.html.twig` | `Chapter04_Implementation` |
| `cqrs.html.twig` | `Chapter05_CQRS` |
| `event_sourcing.html.twig` | `Chapter06_EventSourcing` |
| `sagas.html.twig` | `Chapter07_Sagas` |
| `testing_ddd.html.twig` | `Chapter08_Testing` |
| `migration_from_crud.html.twig` | `Chapter09_Migration` |

- [ ] **Step 1: Přidej banner do what_is_ddd.html.twig (za h1)**

V souboru `templates/ddd/what_is_ddd.html.twig` najdi první `<h1` a za uzavírací tag `</h1>` vlož banner s odkazem na `Chapter01_WhatIsDDD`.

- [ ] **Step 2: Opakuj pro zbývající šablony**

Přidej banner do každé šablony ze seznamu výše.

- [ ] **Step 3: Commit**

```bash
git add templates/ddd/
git commit -m "feat: přidat odkazy na živé ukázky do kapitol příručky"
```
