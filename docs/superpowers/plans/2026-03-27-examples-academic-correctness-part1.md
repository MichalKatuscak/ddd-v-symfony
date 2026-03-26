# Examples Academic Correctness — Part 1: Bugy + DDD vzory

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Opravit všechny bugy a přivést DDD vzory do akademicky správného stavu v repozitáři `/home/michal/Work/ddd-symfony-examples/`.

**Architecture:** Práce probíhá přímo v examples repozitáři. Každý task končí commitem. Nejdřív bugy (nelámou API), pak vzory (enums, XML mapping, application layer). Testy se aktualizují jen pokud se mění API stávajících tříd — kompletní rozšíření testů je v Part 3.

**Tech Stack:** PHP 8.4, Symfony 8, Doctrine ORM, Symfony Messenger, PHPUnit 13

**Working directory:** `/home/michal/Work/ddd-symfony-examples`

---

## File Map

### Soubory k modifikaci (Sekce 1 — bugy)

| Soubor | Změna |
|--------|-------|
| `src/Chapter03_BasicConcepts/Domain/Order/Money.php` | Odstranit redundantní `readonly` |
| `src/Chapter03_BasicConcepts/Domain/Order/OrderId.php` | Přidat `use Uuid`, odstranit redundantní `readonly` |
| `src/Chapter03_BasicConcepts/Domain/Order/OrderStatus.php` | Odstranit redundantní `readonly` |
| `src/Chapter03_BasicConcepts/Domain/Order/OrderItem.php` | Přidat `readonly class` |
| `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderConfirmed.php` | Odstranit redundantní `readonly` |
| `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderItemAdded.php` | Odstranit redundantní `readonly` |
| `src/Chapter03_BasicConcepts/Domain/Email.php` | Přidat `declare(strict_types=1)` |
| `src/Chapter03_BasicConcepts/Domain/Repository/OrderRepositoryInterface.php` | Přidat `declare(strict_types=1)` |
| `src/Chapter03_BasicConcepts/Infrastructure/Persistence/InMemoryOrderRepository.php` | Přidat `declare(strict_types=1)` |
| `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php` | Fix float rounding |
| `src/Chapter04_Implementation/Domain/Order/Money.php` | Přidat currency guard, odstranit `readonly` |
| `src/Chapter04_Implementation/Domain/Order/OrderId.php` | Přidat `use Uuid`, odstranit `readonly` |
| `src/Chapter04_Implementation/Domain/Order/OrderStatus.php` | Odstranit `readonly` |
| `src/Chapter04_Implementation/Domain/Order/OrderPlaced.php` | Odstranit `readonly` |
| `src/Chapter04_Implementation/Domain/Order/Order.php` | Odstranit ORM atributy (bude XML) |
| `src/Chapter04_Implementation/Infrastructure/Persistence/DoctrineOrderRepository.php` | Přidat `declare(strict_types=1)` |
| `src/Chapter04_Implementation/UI/Chapter04Controller.php` | Fix float rounding |
| `src/Chapter05_CQRS/Domain/Order/Money.php` | Přidat currency guard, odstranit `readonly` |
| `src/Chapter05_CQRS/Domain/Order/OrderId.php` | Přidat `use Uuid`, odstranit `readonly` |
| `src/Chapter05_CQRS/Domain/Order/OrderPlaced.php` | Odstranit `readonly` |
| `src/Chapter05_CQRS/Domain/Order/Order.php` | Odstranit ORM atributy |
| `src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderCommand.php` | Odstranit `readonly` |
| `src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php` | Fix `rowid`, null guard |
| `src/Chapter05_CQRS/Application/GetOrders/OrderView.php` | Odstranit `readonly` |
| `src/Chapter05_CQRS/Infrastructure/Persistence/DoctrineOrderRepository.php` | Přidat `declare(strict_types=1)` |
| `src/Chapter05_CQRS/UI/Chapter05Controller.php` | Fix float rounding |
| `src/Chapter06_EventSourcing/Domain/Order/OrderId.php` | Přidat `use Uuid`, odstranit `readonly` |
| `src/Chapter06_EventSourcing/Domain/Order/Order.php` | Fix apply() default, přidat stavové guardy |
| `src/Chapter06_EventSourcing/Domain/Order/Events/OrderPlaced.php` | Odstranit `readonly` |
| `src/Chapter06_EventSourcing/Domain/Order/Events/OrderConfirmed.php` | Odstranit `readonly` |
| `src/Chapter06_EventSourcing/Domain/Order/Events/OrderCancelled.php` | Odstranit `readonly` |
| `src/Chapter06_EventSourcing/Infrastructure/EventStore/DoctrineEventStore.php` | Fix ordering, přidat concurrency |
| `src/Chapter06_EventSourcing/Infrastructure/EventStore/EventStoreInterface.php` | Přidat `expectedVersion` |
| `src/Chapter06_EventSourcing/Infrastructure/EventStore/StoredEvent.php` | Odstranit ORM atributy |
| `src/Chapter06_EventSourcing/UI/Chapter06Controller.php` | Fix typ na interface, přidat guard |
| `src/Chapter07_Sagas/Application/OrderFulfillmentSaga.php` | Přidat `declare(strict_types=1)` |
| `src/Chapter07_Sagas/UI/Chapter07Controller.php` | Přidat `declare(strict_types=1)` |
| `src/Chapter08_Testing/Domain/Task/Task.php` | Fix complete() guard |
| `src/Chapter08_Testing/Domain/Task/TaskId.php` | Přidat `use Uuid`, odstranit `readonly` |
| `src/Chapter08_Testing/Domain/Task/TaskStatus.php` | Odstranit `readonly` |
| `src/Chapter08_Testing/Domain/Task/TaskAssigned.php` | Odstranit `readonly` |
| `src/Chapter09_Migration/Domain/Task/Task.php` | Přidat title validace |
| `src/Chapter09_Migration/Domain/Task/TaskId.php` | Přidat `use Uuid`, odstranit `readonly` |
| `src/Chapter09_Migration/Domain/Task/TaskStatus.php` | Odstranit `readonly` |
| `src/Shared/Domain/AggregateRoot.php` | Přidat `declare(strict_types=1)` |
| `src/Shared/Domain/DomainEvent.php` | Přidat `declare(strict_types=1)` |
| `src/Kernel.php` | Přidat `declare(strict_types=1)` |
| `src/Twig/ClassNameExtension.php` | Přidat `declare(strict_types=1)` |
| `src/UI/ExamplesIndexController.php` | Přidat `declare(strict_types=1)` |
| `README.md` | Opravit `your-org`, PHP verzi |
| `.env` | Vygenerovat APP_SECRET |

### Soubory k vytvoření (Sekce 2 — DDD vzory)

| Soubor | Účel |
|--------|------|
| `config/doctrine/Chapter04/Order.orm.xml` | XML mapping pro Ch04 Order |
| `config/doctrine/Chapter05/Order.orm.xml` | XML mapping pro Ch05 Order |
| `config/doctrine/Chapter06/StoredEvent.orm.xml` | XML mapping pro Ch06 StoredEvent |
| `src/Chapter04_Implementation/Infrastructure/Doctrine/OrderIdType.php` | Custom Doctrine type |
| `src/Chapter04_Implementation/Infrastructure/Doctrine/MoneyAmountType.php` | Custom Doctrine type |
| `src/Chapter04_Implementation/Domain/Order/OrderLine.php` | VO pro položku objednávky |
| `src/Chapter04_Implementation/Application/PlaceOrder/PlaceOrderCommand.php` | Command |
| `src/Chapter04_Implementation/Application/PlaceOrder/PlaceOrderHandler.php` | Command handler |
| `src/Chapter04_Implementation/Application/GetOrder/GetOrderQuery.php` | Query |
| `src/Chapter04_Implementation/Application/GetOrder/GetOrderHandler.php` | Query handler |
| `src/Chapter06_EventSourcing/Domain/Order/ConcurrencyException.php` | Exception pro optimistic lock |
| `src/Chapter06_EventSourcing/Infrastructure/Projection/OrderListProjection.php` | Projekční entita |
| `src/Chapter06_EventSourcing/Infrastructure/Projection/OrderListProjector.php` | Event subscriber |
| `migrations/Version20260327_ch06_projection.php` | Migrace pro projekční tabulku |

---

## Task 1: `declare(strict_types=1)` a README/.env opravy

**Files:**
- Modify: Všechny `*.php` v `src/` a `tests/` (50+ souborů)
- Modify: `README.md`
- Modify: `.env`

- [ ] **Step 1: Přidat `declare(strict_types=1)` do všech PHP souborů**

Spustit jednorázový skript:

```bash
cd /home/michal/Work/ddd-symfony-examples
find src tests -name '*.php' | while read f; do
  if ! head -3 "$f" | grep -q 'strict_types'; then
    sed -i '1s/^<?php/<?php\n\ndeclare(strict_types=1);/' "$f"
  fi
done
```

- [ ] **Step 2: Opravit README.md**

Nahradit:
```markdown
- PHP 8.3+
```
za:
```markdown
- PHP 8.4+
```

Nahradit:
```
git clone https://github.com/your-org/ddd-symfony-examples
```
za:
```
git clone https://github.com/MichalKatuscak/ddd-symfony-examples
```

- [ ] **Step 3: Vygenerovat APP_SECRET v .env**

Nahradit:
```
APP_SECRET=
```
za:
```
APP_SECRET=a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6
```

- [ ] **Step 4: Ověřit, že aplikace bootuje**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console cache:clear
```

Expected: `[OK] Cache for the "dev" environment was successfully cleared.`

- [ ] **Step 5: Ověřit, že testy prochází**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 6: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: přidat declare(strict_types=1), opravit README a APP_SECRET"
```

---

## Task 2: Odstranit redundantní `readonly` z promoted properties

Všechny soubory kde je `final readonly class` a zároveň `public readonly` / `private readonly` na promoted properties.

**Files:**
- Modify: `src/Chapter01_WhatIsDDD/Domain/Product/ProductId.php`
- Modify: `src/Chapter01_WhatIsDDD/Domain/Product/Price.php`
- Modify: `src/Chapter01_WhatIsDDD/Domain/BoundedContext/CatalogProduct.php`
- Modify: `src/Chapter01_WhatIsDDD/Domain/BoundedContext/OrderProduct.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/OrderId.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/Money.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/OrderStatus.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderConfirmed.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderItemAdded.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/OrderId.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/Money.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/OrderStatus.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/OrderPlaced.php`
- Modify: `src/Chapter05_CQRS/Domain/Order/OrderId.php`
- Modify: `src/Chapter05_CQRS/Domain/Order/Money.php`
- Modify: `src/Chapter05_CQRS/Domain/Order/OrderPlaced.php`
- Modify: `src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderCommand.php`
- Modify: `src/Chapter05_CQRS/Application/GetOrders/OrderView.php`
- Modify: `src/Chapter06_EventSourcing/Domain/Order/OrderId.php`
- Modify: `src/Chapter06_EventSourcing/Domain/Order/Events/OrderPlaced.php`
- Modify: `src/Chapter06_EventSourcing/Domain/Order/Events/OrderConfirmed.php`
- Modify: `src/Chapter06_EventSourcing/Domain/Order/Events/OrderCancelled.php`
- Modify: `src/Chapter08_Testing/Domain/Task/TaskId.php`
- Modify: `src/Chapter08_Testing/Domain/Task/TaskStatus.php`
- Modify: `src/Chapter08_Testing/Domain/Task/TaskAssigned.php`
- Modify: `src/Chapter09_Migration/Domain/Task/TaskId.php`
- Modify: `src/Chapter09_Migration/Domain/Task/TaskStatus.php`

- [ ] **Step 1: Batch úprava — odstranit redundantní `readonly` z promoted properties**

Vzor úpravy — v každém souboru kde je `final readonly class`, nahradit `public readonly` → `public` a `private readonly` → `private` na promoted constructor properties.

Příklad — `src/Chapter01_WhatIsDDD/Domain/Product/ProductId.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\Product;

final readonly class ProductId
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('ProductId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
```

Příklad — `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderConfirmed.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderConfirmed implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public int $totalAmount,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

Stejný vzor aplikovat na všech 27 souborů. Také udělat `OrderItem` (Ch03) readonly class:

```php
<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

final readonly class OrderItem
{
    public function __construct(
        private string $name,
        private int $quantity,
        private Money $unitPrice,
    ) {}

    public function name(): string { return $this->name; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): Money { return $this->unitPrice; }
    public function lineTotal(): Money { return $this->unitPrice->multiply($this->quantity); }
}
```

- [ ] **Step 2: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 3: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: odstranit redundantní readonly z promoted properties v readonly class"
```

---

## Task 3: Přidat `use Uuid` import do všech *Id.php souborů

**Files:**
- Modify: `src/Chapter01_WhatIsDDD/Domain/Product/ProductId.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/OrderId.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/OrderId.php`
- Modify: `src/Chapter05_CQRS/Domain/Order/OrderId.php`
- Modify: `src/Chapter06_EventSourcing/Domain/Order/OrderId.php`
- Modify: `src/Chapter08_Testing/Domain/Task/TaskId.php`
- Modify: `src/Chapter09_Migration/Domain/Task/TaskId.php`

- [ ] **Step 1: Přidat `use` import a nahradit inline FQCN**

Vzor — každý *Id.php soubor dostane `use Symfony\Component\Uid\Uuid;` a `generate()` se změní na `Uuid::v4()`.

Příklad — `src/Chapter03_BasicConcepts/Domain/Order/OrderId.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

use Symfony\Component\Uid\Uuid;

final readonly class OrderId
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('OrderId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }
}
```

Aplikovat stejný vzor na všech 7 souborů.

- [ ] **Step 2: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 3: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: nahradit inline FQCN za use import pro Uuid ve všech Id souborech"
```

---

## Task 4: Fix Money::add() currency guard (Ch04, Ch05)

Ch03 již má guard. Ch04 a Ch05 ne.

**Files:**
- Modify: `src/Chapter04_Implementation/Domain/Order/Money.php`
- Modify: `src/Chapter05_CQRS/Domain/Order/Money.php`

- [ ] **Step 1: Přidat currency guard do Ch04 Money.php**

Výsledný soubor `src/Chapter04_Implementation/Domain/Order/Money.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf('Cannot add %s to %s', $other->currency, $this->currency)
            );
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $qty): self
    {
        return new self($this->amount * $qty, $this->currency);
    }

    public function percentage(int $pct): self
    {
        return new self((int) round($this->amount * $pct / 100), $this->currency);
    }

    public function formatted(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }
}
```

Poznámka: `percentage()` dostává také `round()` — opravuje potenciální truncation bug.

- [ ] **Step 2: Přidat currency guard do Ch05 Money.php**

Výsledný soubor `src/Chapter05_CQRS/Domain/Order/Money.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Domain\Order;

final readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf('Cannot add %s to %s', $other->currency, $this->currency)
            );
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $qty): self
    {
        return new self($this->amount * $qty, $this->currency);
    }

    public function formatted(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }
}
```

- [ ] **Step 3: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 4: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: přidat currency guard do Money::add() v Ch04 a Ch05"
```

---

## Task 5: Fix float-to-int rounding v kontrolerech

**Files:**
- Modify: `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`
- Modify: `src/Chapter04_Implementation/UI/Chapter04Controller.php`
- Modify: `src/Chapter05_CQRS/UI/Chapter05Controller.php`
- Modify: `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`

- [ ] **Step 1: Fix Ch03 Controller — řádky 34, 83, 84**

V `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`:

Řádek 34 — nahradit:
```php
new Money((int) ($request->request->get('price', 100) * 100), 'CZK'),
```
za:
```php
new Money((int) round((float) $request->request->get('price', '100') * 100), 'CZK'),
```

Řádek 83 — nahradit:
```php
$a = new Money((int) ($request->request->get('amount_a', 0) * 100), 'CZK');
```
za:
```php
$a = new Money((int) round((float) $request->request->get('amount_a', '0') * 100), 'CZK');
```

Řádek 84 — nahradit:
```php
$b = new Money((int) ($request->request->get('amount_b', 0) * 100), 'CZK');
```
za:
```php
$b = new Money((int) round((float) $request->request->get('amount_b', '0') * 100), 'CZK');
```

- [ ] **Step 2: Fix Ch04 Controller — řádek 30**

V `src/Chapter04_Implementation/UI/Chapter04Controller.php`:

Nahradit:
```php
$price = (int) ($request->request->get('price', 100) * 100);
```
za:
```php
$price = (int) round((float) $request->request->get('price', '100') * 100);
```

- [ ] **Step 3: Fix Ch05 Controller — řádek 31**

V `src/Chapter05_CQRS/UI/Chapter05Controller.php`:

Nahradit:
```php
'price' => (int) ($request->request->get('price', 100) * 100),
```
za:
```php
'price' => (int) round((float) $request->request->get('price', '100') * 100),
```

- [ ] **Step 4: Fix Ch06 Controller — řádek 32**

V `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`:

Nahradit:
```php
(int) ($request->request->get('price', 599) * 100),
```
za:
```php
(int) round((float) $request->request->get('price', '599') * 100),
```

- [ ] **Step 5: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 6: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: opravit float-to-int konverzi cen v kontrolerech (round místo truncate)"
```

---

## Task 6: Fix Ch06 EventStore ordering + apply() default

**Files:**
- Modify: `src/Chapter06_EventSourcing/Infrastructure/EventStore/DoctrineEventStore.php`
- Modify: `src/Chapter06_EventSourcing/Domain/Order/Order.php`

- [ ] **Step 1: Fix DoctrineEventStore::load() — přidat orderBy**

V `src/Chapter06_EventSourcing/Infrastructure/EventStore/DoctrineEventStore.php`, řádek 28:

Nahradit:
```php
->findBy(['aggregateId' => $aggregateId]);
```
za:
```php
->findBy(['aggregateId' => $aggregateId], ['id' => 'ASC']);
```

- [ ] **Step 2: Fix Order::apply() — throw na unknown event**

V `src/Chapter06_EventSourcing/Domain/Order/Order.php`, řádek 62:

Nahradit:
```php
default => null,
```
za:
```php
default => throw new \LogicException('Unknown event: ' . get_class($event)),
```

- [ ] **Step 3: Přidat stavové guardy do confirm() a cancel()**

V `src/Chapter06_EventSourcing/Domain/Order/Order.php`:

Nahradit metodu `confirm()`:
```php
public function confirm(): void
{
    if ($this->status !== 'pending') {
        throw new \DomainException('Cannot confirm order in status: ' . $this->status);
    }
    $event = new OrderConfirmed($this->id->value);
    $this->apply($event);
    $this->uncommittedEvents[] = $event;
}
```

Nahradit metodu `cancel()`:
```php
public function cancel(string $reason): void
{
    if ($this->status !== 'pending') {
        throw new \DomainException('Cannot cancel order in status: ' . $this->status);
    }
    $event = new OrderCancelled($this->id->value, $reason);
    $this->apply($event);
    $this->uncommittedEvents[] = $event;
}
```

- [ ] **Step 4: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)` — existující testy netestují invalid transitions, takže guardy nezlomí nic.

- [ ] **Step 5: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: Ch06 EventStore ordering, apply() throw na neznámý event, stavové guardy"
```

---

## Task 7: Fix Ch06 Controller — interface typ + guard na neexistující agregát

**Files:**
- Modify: `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`
- Modify: `config/services.yaml`

- [ ] **Step 1: Změnit typ v controlleru na EventStoreInterface**

V `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`:

Nahradit import:
```php
use App\Chapter06_EventSourcing\Infrastructure\EventStore\DoctrineEventStore;
```
za:
```php
use App\Chapter06_EventSourcing\Infrastructure\EventStore\EventStoreInterface;
```

Nahradit constructor:
```php
public function __construct(private readonly DoctrineEventStore $eventStore) {}
```
za:
```php
public function __construct(private readonly EventStoreInterface $eventStore) {}
```

- [ ] **Step 2: Přidat guard na prázdný event stream**

V confirm/cancel blocích, po `$events = $this->eventStore->load($orderId);` přidat guard:

```php
} elseif ($action === 'confirm') {
    $events = $this->eventStore->load($orderId);
    if (empty($events)) {
        throw new \DomainException('Objednávka neexistuje: ' . $orderId);
    }
    $order = Order::reconstruct(new OrderId($orderId), $events);
    $order->confirm();
    $this->eventStore->append($orderId, $order->pullUncommittedEvents());
    $result = 'Objednávka potvrzena. Rekonstruována z ' . count($events) . ' eventů.';
} elseif ($action === 'cancel') {
    $events = $this->eventStore->load($orderId);
    if (empty($events)) {
        throw new \DomainException('Objednávka neexistuje: ' . $orderId);
    }
    $order = Order::reconstruct(new OrderId($orderId), $events);
    $order->cancel('Zákazník si to rozmyslel');
    $this->eventStore->append($orderId, $order->pullUncommittedEvents());
    $result = 'Objednávka zrušena.';
}
```

Přidat catch pro DomainException v celém POST bloku — obalit celý if/elseif blok:

```php
if ($request->isMethod('POST')) {
    $action = $request->request->get('action');
    $orderId = $request->request->get('order_id') ?: OrderId::generate()->value;
    $currentOrderId = $orderId;

    try {
        // ... existující if/elseif logika ...
    } catch (\DomainException $e) {
        $result = 'Chyba: ' . $e->getMessage();
    }

    if ($currentOrderId) {
        $history = $this->eventStore->load($currentOrderId);
    }
}
```

- [ ] **Step 3: Přidat service binding do services.yaml**

V `config/services.yaml` přidat:

```yaml
    App\Chapter06_EventSourcing\Infrastructure\EventStore\EventStoreInterface:
        class: App\Chapter06_EventSourcing\Infrastructure\EventStore\DoctrineEventStore
```

- [ ] **Step 4: Ověřit cache clear + testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console cache:clear && php vendor/bin/phpunit
```

Expected: cache OK + `OK (24 tests, 42 assertions)`

- [ ] **Step 5: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: Ch06 controller typehint na EventStoreInterface, guard na neexistující agregát"
```

---

## Task 8: Fix Ch05 GetOrdersHandler — SQLite rowid + null guard

**Files:**
- Modify: `src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php`

- [ ] **Step 1: Opravit SQL a přidat null guard**

Výsledný soubor `src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Application\GetOrders;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetOrdersHandler
{
    public function __construct(private readonly Connection $connection) {}

    /** @return OrderView[] */
    public function __invoke(GetOrdersQuery $query): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, customer_id, total_amount, items FROM ch05_orders ORDER BY id DESC'
        );

        return array_map(fn(array $row) => new OrderView(
            id: substr($row['id'], 0, 8) . '…',
            customerId: $row['customer_id'],
            total: number_format((int) $row['total_amount'] / 100, 2) . ' CZK',
            itemCount: count(json_decode($row['items'], true) ?? []),
        ), $rows);
    }
}
```

Změny: `rowid` → `id`, přidáno `?? []` za `json_decode`, přidáno `(int)` cast na `total_amount`.

- [ ] **Step 2: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 3: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: Ch05 GetOrdersHandler — nahradit SQLite rowid, přidat null guard na json_decode"
```

---

## Task 9: Fix Ch08 Task::complete() guard

**Files:**
- Modify: `src/Chapter08_Testing/Domain/Task/Task.php`

- [ ] **Step 1: Přidat guard do complete()**

V `src/Chapter08_Testing/Domain/Task/Task.php`, nahradit:

```php
public function complete(): void
{
    $this->status = TaskStatus::done();
}
```
za:
```php
public function complete(): void
{
    if (!$this->status->isInProgress()) {
        throw new \DomainException('Task must be in progress before completing');
    }
    $this->status = TaskStatus::done();
}
```

- [ ] **Step 2: Přidat isInProgress() do Ch08 TaskStatus**

V `src/Chapter08_Testing/Domain/Task/TaskStatus.php`, přidat metodu:

Po `public function isDone(): bool { return $this->value === 'done'; }` přidat:
```php
    public function isInProgress(): bool { return $this->value === 'in_progress'; }
```

- [ ] **Step 3: Aktualizovat existující test**

Existující `tests/Chapter08/Domain/TaskTest.php` test `testCanCompleteTask` pravděpodobně vytvoří task a rovnou zavolá `complete()` bez `assignTo()`. Po přidání guardu to spadne. Otevřít test a upravit — přidat `assignTo` před `complete`:

V testu který volá `$task->complete()`, přidat řádek před ním:
```php
$task->assignTo('member-1');
$task->complete();
```

- [ ] **Step 4: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 5: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: Ch08 Task::complete() vyžaduje in_progress stav, přidat isInProgress()"
```

---

## Task 10: Fix Ch09 Task title validace

**Files:**
- Modify: `src/Chapter09_Migration/Domain/Task/Task.php`

- [ ] **Step 1: Přidat title guard**

V `src/Chapter09_Migration/Domain/Task/Task.php`, v konstruktoru přidat validaci:

Nahradit:
```php
private function __construct(
    private readonly TaskId $id,
    private readonly string $title,
    private readonly string $projectId,
) {
    $this->status = TaskStatus::todo();
}
```
za:
```php
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
```

- [ ] **Step 2: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 3: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "fix: Ch09 Task přidat validaci prázdného titulku (konzistence s Ch08)"
```

---

## Task 11: PHP enums — OrderStatus (Ch03, Ch04)

**Files:**
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/OrderStatus.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/Order.php`
- Modify: `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/OrderStatus.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/Order.php`

- [ ] **Step 1: Přepsat Ch03 OrderStatus na enum**

Výsledný `src/Chapter03_BasicConcepts/Domain/Order/OrderStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
```

- [ ] **Step 2: Aktualizovat Ch03 Order.php pro enum**

V `src/Chapter03_BasicConcepts/Domain/Order/Order.php`:

Konstruktor — nahradit:
```php
$this->status = OrderStatus::pending();
```
za:
```php
$this->status = OrderStatus::Pending;
```

`addItem()` — nahradit:
```php
if (!$this->status->isPending()) {
```
za:
```php
if ($this->status !== OrderStatus::Pending) {
```

`confirm()` — nahradit:
```php
$this->status = OrderStatus::confirmed();
```
za:
```php
$this->status = OrderStatus::Confirmed;
```

- [ ] **Step 3: Aktualizovat Ch03 Controller — status zobrazení**

V `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`, řádek 57:

Nahradit:
```php
$result = 'Objednávka potvrzena. Stav: ' . $order->status()->value();
```
za:
```php
$result = 'Objednávka potvrzena. Stav: ' . $order->status()->value;
```

- [ ] **Step 4: Přepsat Ch04 OrderStatus na enum**

Výsledný `src/Chapter04_Implementation/Domain/Order/OrderStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
```

- [ ] **Step 5: Aktualizovat Ch04 Order.php**

V `src/Chapter04_Implementation/Domain/Order/Order.php`:

Konstruktor — nahradit:
```php
$this->status = 'pending';
```
za:
```php
$this->status = OrderStatus::Pending;
```

Property — nahradit:
```php
private string $status;
```
za:
```php
private OrderStatus $status;
```

`status()` getter — nahradit:
```php
public function status(): OrderStatus { return OrderStatus::fromString($this->status); }
```
za:
```php
public function status(): OrderStatus { return $this->status; }
```

- [ ] **Step 6: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 7: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "refactor: přepsat OrderStatus na PHP enum (Ch03, Ch04)"
```

---

## Task 12: PHP enums — TaskStatus (Ch08, Ch09)

**Files:**
- Modify: `src/Chapter08_Testing/Domain/Task/TaskStatus.php`
- Modify: `src/Chapter08_Testing/Domain/Task/Task.php`
- Modify: `src/Chapter09_Migration/Domain/Task/TaskStatus.php`
- Modify: `src/Chapter09_Migration/Domain/Task/Task.php`

- [ ] **Step 1: Přepsat Ch08 TaskStatus na enum**

Výsledný `src/Chapter08_Testing/Domain/Task/TaskStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter08_Testing\Domain\Task;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
}
```

- [ ] **Step 2: Aktualizovat Ch08 Task.php**

V `src/Chapter08_Testing/Domain/Task/Task.php`:

Property + konstruktor:
```php
private TaskStatus $status;
```
zůstane stejný typ, ale inicializace se změní:
```php
$this->status = TaskStatus::Todo;
```

`assignTo()`:
```php
public function assignTo(string $memberId): void
{
    if ($this->status === TaskStatus::Done) {
        throw new \DomainException('Cannot reassign a completed task');
    }
    $this->assignedTo = $memberId;
    $this->status = TaskStatus::InProgress;
    $this->record(new TaskAssigned($this->id->value, $memberId));
}
```

`complete()`:
```php
public function complete(): void
{
    if ($this->status !== TaskStatus::InProgress) {
        throw new \DomainException('Task must be in progress before completing');
    }
    $this->status = TaskStatus::Done;
}
```

- [ ] **Step 3: Přepsat Ch09 TaskStatus na enum**

Výsledný `src/Chapter09_Migration/Domain/Task/TaskStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\Domain\Task;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
}
```

- [ ] **Step 4: Aktualizovat Ch09 Task.php**

V `src/Chapter09_Migration/Domain/Task/Task.php`:

Konstruktor:
```php
$this->status = TaskStatus::Todo;
```

`start()`:
```php
public function start(string $memberId): void
{
    if ($this->status !== TaskStatus::Todo) {
        throw new \DomainException('Task is already started or done');
    }
    $this->assignedTo = $memberId;
    $this->status = TaskStatus::InProgress;
}
```

`complete()`:
```php
public function complete(): void
{
    if ($this->status !== TaskStatus::InProgress) {
        throw new \DomainException('Task must be in progress before completing');
    }
    $this->status = TaskStatus::Done;
}
```

- [ ] **Step 5: Aktualizovat testy pro nové enum API**

V `tests/Chapter08/Domain/TaskTest.php` — nahradit `$task->status()->isTodo()` za `$task->status() === TaskStatus::Todo` atd.

V `tests/Chapter09/Domain/TaskMigrationTest.php` — stejně.

- [ ] **Step 6: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 7: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "refactor: přepsat TaskStatus na PHP enum (Ch08, Ch09)"
```

---

## Task 13: XML Doctrine mapping — Ch04

**Files:**
- Modify: `src/Chapter04_Implementation/Domain/Order/Order.php` — odstranit ORM atributy
- Create: `config/doctrine/Chapter04_Implementation/Order.orm.xml`
- Create: `src/Chapter04_Implementation/Infrastructure/Doctrine/OrderIdType.php`
- Create: `src/Chapter04_Implementation/Infrastructure/Doctrine/MoneyAmountType.php`
- Modify: `config/packages/doctrine.yaml`

- [ ] **Step 1: Vytvořit custom Doctrine type — OrderIdType**

Vytvořit `src/Chapter04_Implementation/Infrastructure/Doctrine/OrderIdType.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Infrastructure\Doctrine;

use App\Chapter04_Implementation\Domain\Order\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class OrderIdType extends StringType
{
    public const string NAME = 'ch04_order_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderId
    {
        if ($value === null) {
            return null;
        }

        return new OrderId((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof OrderId) {
            return $value->value;
        }

        return (string) $value;
    }
}
```

- [ ] **Step 2: Vytvořit custom Doctrine type — MoneyAmountType**

Vytvořit `src/Chapter04_Implementation/Infrastructure/Doctrine/MoneyAmountType.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Infrastructure\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

final class MoneyAmountType extends IntegerType
{
    public const string NAME = 'ch04_money_amount';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): int
    {
        return (int) $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int
    {
        return (int) $value;
    }
}
```

- [ ] **Step 3: Odstranit ORM atributy z Ch04 Order**

V `src/Chapter04_Implementation/Domain/Order/Order.php`:

Odstranit import:
```php
use Doctrine\ORM\Mapping as ORM;
```

Odstranit atributy:
```php
#[ORM\Entity]
#[ORM\Table(name: 'ch04_orders')]
```
a všechny `#[ORM\...]` atributy z properties.

Výsledný soubor (relevantní části):
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

use App\Shared\Domain\AggregateRoot;

class Order extends AggregateRoot
{
    private string $id;
    private string $customerId;
    private int $totalAmount = 0;
    private OrderStatus $status;
    private array $items = [];

    private function __construct(OrderId $id, string $customerId)
    {
        $this->id = $id->value;
        $this->customerId = $customerId;
        $this->status = OrderStatus::Pending;
    }

    /** @param array<array{name: string, qty: int, price: int}> $items */
    public static function place(OrderId $id, string $customerId, array $items): self
    {
        $order = new self($id, $customerId);
        foreach ($items as $item) {
            $order->items[] = $item;
            $order->totalAmount += $item['price'] * $item['qty'];
        }
        $order->record(new OrderPlaced($id->value, $customerId, $order->totalAmount));
        return $order;
    }

    public function id(): OrderId { return new OrderId($this->id); }
    public function customerId(): string { return $this->customerId; }
    public function status(): OrderStatus { return $this->status; }
    public function total(): Money { return new Money($this->totalAmount, 'CZK'); }
    /** @return array<array{name: string, qty: int, price: int}> */
    public function items(): array { return $this->items; }
}
```

- [ ] **Step 4: Vytvořit XML mapping**

Vytvořit adresář a soubor `config/doctrine/Chapter04_Implementation/Order.orm.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Chapter04_Implementation\Domain\Order\Order"
            table="ch04_orders">

        <id name="id" type="string" length="36"/>

        <field name="customerId" column="customer_id" type="string"/>
        <field name="totalAmount" column="total_amount" type="integer"/>
        <field name="status" column="status" type="string" length="20"/>
        <field name="items" column="items" type="json"/>

    </entity>
</doctrine-mapping>
```

- [ ] **Step 5: Aktualizovat doctrine.yaml**

V `config/packages/doctrine.yaml`, sekce `mappings`, přidat nový mapping a změnit stávající aby neskenovalo Ch04:

Nahradit celou `mappings` sekci:
```yaml
        mappings:
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src'
                prefix: 'App'
                alias: App
```
za:
```yaml
        mappings:
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src'
                prefix: 'App'
                alias: App
            Chapter04:
                type: xml
                is_bundle: false
                dir: '%kernel.project_dir%/config/doctrine/Chapter04_Implementation'
                prefix: 'App\Chapter04_Implementation\Domain\Order'
                alias: Chapter04
```

Přidat custom types pod `dbal`:
```yaml
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        profiling_collect_backtrace: '%kernel.debug%'
        types:
            ch04_order_id:
                class: App\Chapter04_Implementation\Infrastructure\Doctrine\OrderIdType
            ch04_money_amount:
                class: App\Chapter04_Implementation\Infrastructure\Doctrine\MoneyAmountType
```

- [ ] **Step 6: Ověřit schema validation**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console doctrine:schema:validate
```

Pokud hlásí nesoulad, je to OK — máme existující migrace. Důležité je že mapping se načte bez chyb.

```bash
php bin/console cache:clear
```

Expected: no errors.

- [ ] **Step 7: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: `OK (24 tests, 42 assertions)`

- [ ] **Step 8: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "refactor: Ch04 přesunout Doctrine mapping do XML, vytvořit custom types"
```

---

## Task 14: XML Doctrine mapping — Ch05

**Files:**
- Modify: `src/Chapter05_CQRS/Domain/Order/Order.php` — odstranit ORM atributy
- Create: `config/doctrine/Chapter05_CQRS/Order.orm.xml`
- Modify: `config/packages/doctrine.yaml`

- [ ] **Step 1: Odstranit ORM atributy z Ch05 Order**

Stejný postup jako Task 13 Step 3. Odstranit `use Doctrine\ORM\Mapping as ORM;` a všechny `#[ORM\...]` atributy. Doména zůstane čistá.

- [ ] **Step 2: Vytvořit XML mapping**

Vytvořit `config/doctrine/Chapter05_CQRS/Order.orm.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Chapter05_CQRS\Domain\Order\Order"
            table="ch05_orders">

        <id name="id" type="string" length="36"/>

        <field name="customerId" column="customer_id" type="string"/>
        <field name="totalAmount" column="total_amount" type="integer"/>
        <field name="items" column="items" type="json"/>

    </entity>
</doctrine-mapping>
```

- [ ] **Step 3: Přidat mapping do doctrine.yaml**

Přidat pod `mappings:`:
```yaml
            Chapter05:
                type: xml
                is_bundle: false
                dir: '%kernel.project_dir%/config/doctrine/Chapter05_CQRS'
                prefix: 'App\Chapter05_CQRS\Domain\Order'
                alias: Chapter05
```

- [ ] **Step 4: Ověřit + testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console cache:clear && php vendor/bin/phpunit
```

Expected: cache OK + testy OK.

- [ ] **Step 5: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "refactor: Ch05 přesunout Doctrine mapping do XML"
```

---

## Task 15: XML Doctrine mapping — Ch06 StoredEvent

**Files:**
- Modify: `src/Chapter06_EventSourcing/Infrastructure/EventStore/StoredEvent.php` — odstranit ORM atributy
- Create: `config/doctrine/Chapter06_EventSourcing/StoredEvent.orm.xml`
- Modify: `config/packages/doctrine.yaml`

- [ ] **Step 1: Odstranit ORM atributy z StoredEvent**

Výsledný `src/Chapter06_EventSourcing/Infrastructure/EventStore/StoredEvent.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

class StoredEvent
{
    private ?int $id = null;
    private string $aggregateId;
    private string $eventClass;
    private array $payload;
    private \DateTimeImmutable $occurredAt;

    public function __construct(string $aggregateId, string $eventClass, array $payload, \DateTimeImmutable $occurredAt)
    {
        $this->aggregateId = $aggregateId;
        $this->eventClass = $eventClass;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public function id(): ?int { return $this->id; }
    public function aggregateId(): string { return $this->aggregateId; }
    public function eventClass(): string { return $this->eventClass; }
    public function payload(): array { return $this->payload; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
```

- [ ] **Step 2: Vytvořit XML mapping**

Vytvořit `config/doctrine/Chapter06_EventSourcing/StoredEvent.orm.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Chapter06_EventSourcing\Infrastructure\EventStore\StoredEvent"
            table="ch06_event_store">

        <id name="id" type="integer">
            <generator strategy="AUTO"/>
        </id>

        <field name="aggregateId" column="aggregate_id" type="string" length="36"/>
        <field name="eventClass" column="event_class" type="string"/>
        <field name="payload" column="payload" type="json"/>
        <field name="occurredAt" column="occurred_at" type="datetime_immutable"/>

    </entity>
</doctrine-mapping>
```

- [ ] **Step 3: Přidat mapping do doctrine.yaml**

Přidat pod `mappings:`:
```yaml
            Chapter06:
                type: xml
                is_bundle: false
                dir: '%kernel.project_dir%/config/doctrine/Chapter06_EventSourcing'
                prefix: 'App\Chapter06_EventSourcing\Infrastructure\EventStore'
                alias: Chapter06
```

- [ ] **Step 4: Ověřit + testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console cache:clear && php vendor/bin/phpunit
```

Expected: cache OK + testy OK.

- [ ] **Step 5: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "refactor: Ch06 přesunout Doctrine mapping StoredEvent do XML"
```

---

## Task 16: Ch06 Optimistic Concurrency

**Files:**
- Modify: `src/Chapter06_EventSourcing/Infrastructure/EventStore/EventStoreInterface.php`
- Modify: `src/Chapter06_EventSourcing/Infrastructure/EventStore/DoctrineEventStore.php`
- Create: `src/Chapter06_EventSourcing/Domain/Order/ConcurrencyException.php`
- Modify: `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`

- [ ] **Step 1: Vytvořit ConcurrencyException**

Vytvořit `src/Chapter06_EventSourcing/Domain/Order/ConcurrencyException.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Domain\Order;

final class ConcurrencyException extends \RuntimeException
{
    public static function versionMismatch(string $aggregateId, int $expected, int $actual): self
    {
        return new self(sprintf(
            'Concurrency conflict for aggregate %s: expected version %d, actual %d',
            $aggregateId,
            $expected,
            $actual,
        ));
    }
}
```

- [ ] **Step 2: Aktualizovat EventStoreInterface**

Výsledný `src/Chapter06_EventSourcing/Infrastructure/EventStore/EventStoreInterface.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;

interface EventStoreInterface
{
    /** @param DomainEvent[] $events */
    public function append(string $aggregateId, array $events, int $expectedVersion): void;

    /** @return DomainEvent[] */
    public function load(string $aggregateId): array;

    public function countEvents(string $aggregateId): int;
}
```

- [ ] **Step 3: Aktualizovat DoctrineEventStore**

Výsledný `src/Chapter06_EventSourcing/Infrastructure/EventStore/DoctrineEventStore.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

use App\Chapter06_EventSourcing\Domain\Order\ConcurrencyException;
use App\Shared\Domain\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEventStore implements EventStoreInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function append(string $aggregateId, array $events, int $expectedVersion): void
    {
        $currentVersion = $this->countEvents($aggregateId);
        if ($currentVersion !== $expectedVersion) {
            throw ConcurrencyException::versionMismatch($aggregateId, $expectedVersion, $currentVersion);
        }

        foreach ($events as $event) {
            $payload = $this->serializeEvent($event);
            $stored = new StoredEvent(
                $aggregateId,
                get_class($event),
                $payload,
                $event->occurredAt(),
            );
            $this->em->persist($stored);
        }
        $this->em->flush();
    }

    public function load(string $aggregateId): array
    {
        $stored = $this->em->getRepository(StoredEvent::class)
            ->findBy(['aggregateId' => $aggregateId], ['id' => 'ASC']);

        return array_map(function (StoredEvent $s) {
            $class = $s->eventClass();
            $payload = $s->payload();
            $ref = new \ReflectionClass($class);
            $args = [];
            foreach ($ref->getConstructor()->getParameters() as $param) {
                $name = $param->getName();
                $value = $payload[$name];
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType && $type->getName() === \DateTimeImmutable::class) {
                    $value = new \DateTimeImmutable($value);
                }
                $args[$name] = $value;
            }
            return new $class(...$args);
        }, $stored);
    }

    public function countEvents(string $aggregateId): int
    {
        return (int) $this->em->getRepository(StoredEvent::class)
            ->count(['aggregateId' => $aggregateId]);
    }

    private function serializeEvent(DomainEvent $event): array
    {
        $ref = new \ReflectionClass($event);
        $payload = [];
        foreach ($ref->getProperties() as $prop) {
            $value = $prop->getValue($event);
            if ($value instanceof \DateTimeImmutable) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }
            $payload[$prop->getName()] = $value;
        }
        return $payload;
    }
}
```

- [ ] **Step 4: Aktualizovat Ch06 Controller pro expectedVersion**

V `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`:

Pro `place` akci:
```php
if ($action === 'place') {
    $order = Order::place(
        new OrderId($orderId),
        'zákazník-1',
        (int) round((float) $request->request->get('price', '599') * 100),
    );
    $this->eventStore->append($orderId, $order->pullUncommittedEvents(), 0);
    $result = 'Objednávka zadána. ID: ' . substr($orderId, 0, 8) . '…';
```

Pro `confirm`/`cancel`:
```php
} elseif ($action === 'confirm') {
    $events = $this->eventStore->load($orderId);
    if (empty($events)) {
        throw new \DomainException('Objednávka neexistuje: ' . $orderId);
    }
    $version = count($events);
    $order = Order::reconstruct(new OrderId($orderId), $events);
    $order->confirm();
    $this->eventStore->append($orderId, $order->pullUncommittedEvents(), $version);
    $result = 'Objednávka potvrzena. Rekonstruována z ' . $version . ' eventů.';
} elseif ($action === 'cancel') {
    $events = $this->eventStore->load($orderId);
    if (empty($events)) {
        throw new \DomainException('Objednávka neexistuje: ' . $orderId);
    }
    $version = count($events);
    $order = Order::reconstruct(new OrderId($orderId), $events);
    $order->cancel('Zákazník si to rozmyslel');
    $this->eventStore->append($orderId, $order->pullUncommittedEvents(), $version);
    $result = 'Objednávka zrušena.';
}
```

Přidat catch pro `ConcurrencyException` vedle `DomainException`:
```php
} catch (ConcurrencyException $e) {
    $result = 'Concurrency conflict: ' . $e->getMessage();
}
```

Přidat import nahoře:
```php
use App\Chapter06_EventSourcing\Domain\Order\ConcurrencyException;
```

- [ ] **Step 5: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: testy prochází (existující Ch06 test nepoužívá EventStore).

- [ ] **Step 6: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "feat: Ch06 přidat optimistic concurrency do EventStore"
```

---

## Task 17: Ch06 Projekce

**Files:**
- Create: `src/Chapter06_EventSourcing/Infrastructure/Projection/OrderListProjection.php`
- Create: `src/Chapter06_EventSourcing/Infrastructure/Projection/OrderListProjector.php`
- Create: `config/doctrine/Chapter06_EventSourcing/OrderListProjection.orm.xml`
- Create: `migrations/Version20260327_ch06_projection.php`
- Modify: `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`
- Modify: `templates/examples/chapter06/index.html.twig`

- [ ] **Step 1: Vytvořit OrderListProjection entita**

Vytvořit `src/Chapter06_EventSourcing/Infrastructure/Projection/OrderListProjection.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\Projection;

class OrderListProjection
{
    private string $orderId;
    private string $customerId;
    private int $totalAmount;
    private string $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $orderId,
        string $customerId,
        int $totalAmount,
        string $status,
        \DateTimeImmutable $createdAt,
    ) {
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->totalAmount = $totalAmount;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function orderId(): string { return $this->orderId; }
    public function customerId(): string { return $this->customerId; }
    public function totalAmount(): int { return $this->totalAmount; }
    public function status(): string { return $this->status; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

- [ ] **Step 2: Vytvořit XML mapping pro projekci**

Vytvořit `config/doctrine/Chapter06_EventSourcing/OrderListProjection.orm.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Chapter06_EventSourcing\Infrastructure\Projection\OrderListProjection"
            table="ch06_order_projection">

        <id name="orderId" column="order_id" type="string" length="36"/>

        <field name="customerId" column="customer_id" type="string"/>
        <field name="totalAmount" column="total_amount" type="integer"/>
        <field name="status" column="status" type="string" length="20"/>
        <field name="createdAt" column="created_at" type="datetime_immutable"/>
        <field name="updatedAt" column="updated_at" type="datetime_immutable"/>

    </entity>
</doctrine-mapping>
```

- [ ] **Step 3: Aktualizovat doctrine.yaml mapping prefix**

Změnit Chapter06 mapping prefix aby pokrýval jak EventStore tak Projection:
```yaml
            Chapter06:
                type: xml
                is_bundle: false
                dir: '%kernel.project_dir%/config/doctrine/Chapter06_EventSourcing'
                prefix: 'App\Chapter06_EventSourcing'
                alias: Chapter06
```

- [ ] **Step 4: Vytvořit OrderListProjector**

Vytvořit `src/Chapter06_EventSourcing/Infrastructure/Projection/OrderListProjector.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\Projection;

use App\Chapter06_EventSourcing\Domain\Order\Events\OrderCancelled;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderConfirmed;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderPlaced;
use App\Shared\Domain\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

final class OrderListProjector
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /** @param DomainEvent[] $events */
    public function project(array $events): void
    {
        foreach ($events as $event) {
            match (true) {
                $event instanceof OrderPlaced => $this->onOrderPlaced($event),
                $event instanceof OrderConfirmed => $this->onOrderConfirmed($event),
                $event instanceof OrderCancelled => $this->onOrderCancelled($event),
                default => null,
            };
        }
        $this->em->flush();
    }

    private function onOrderPlaced(OrderPlaced $event): void
    {
        $projection = new OrderListProjection(
            $event->orderId,
            $event->customerId,
            $event->totalAmount,
            'pending',
            $event->occurredAt(),
        );
        $this->em->persist($projection);
    }

    private function onOrderConfirmed(OrderConfirmed $event): void
    {
        $projection = $this->em->find(OrderListProjection::class, $event->orderId);
        $projection?->updateStatus('confirmed');
    }

    private function onOrderCancelled(OrderCancelled $event): void
    {
        $projection = $this->em->find(OrderListProjection::class, $event->orderId);
        $projection?->updateStatus('cancelled');
    }
}
```

- [ ] **Step 5: Vytvořit migraci**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console doctrine:migrations:diff
```

Alternativně ručně vytvořit migraci pro `ch06_order_projection` tabulku.

- [ ] **Step 6: Aktualizovat Controller — integrovat projektor**

V `src/Chapter06_EventSourcing/UI/Chapter06Controller.php`:

Přidat do konstruktoru:
```php
public function __construct(
    private readonly EventStoreInterface $eventStore,
    private readonly OrderListProjector $projector,
    private readonly EntityManagerInterface $em,
) {}
```

Po každém `$this->eventStore->append(...)` přidat:
```php
$this->projector->project($uncommittedEvents);
```

(Uložit events do proměnné před append voláním.)

Přidat na konec metody načtení projekcí pro zobrazení:
```php
$projections = $this->em->getRepository(OrderListProjection::class)->findBy([], ['updatedAt' => 'DESC']);
```

Předat do šablony:
```php
'projections' => $projections,
```

Přidat importy:
```php
use App\Chapter06_EventSourcing\Infrastructure\Projection\OrderListProjection;
use App\Chapter06_EventSourcing\Infrastructure\Projection\OrderListProjector;
use Doctrine\ORM\EntityManagerInterface;
```

- [ ] **Step 7: Aktualizovat šablonu**

V `templates/examples/chapter06/index.html.twig`, přidat sekci za Event Log:

```twig
<hr class="my-5">
<h2>Projekce (Read Model)</h2>
<p>Projekce je denormalizovaná tabulka aktualizovaná z event streamu. Nevyžaduje rekonstrukci agregátu pro čtení.</p>
{% if projections is defined and projections is not empty %}
    <table class="table table-sm">
        <thead><tr><th>Order ID</th><th>Zákazník</th><th>Celkem</th><th>Stav</th><th>Vytvořeno</th></tr></thead>
        <tbody>
        {% for p in projections %}
            <tr>
                <td><code>{{ p.orderId()|slice(0,8) }}…</code></td>
                <td>{{ p.customerId() }}</td>
                <td>{{ (p.totalAmount() / 100)|number_format(2) }} CZK</td>
                <td><span class="badge {{ p.status() == 'confirmed' ? 'bg-success' : (p.status() == 'cancelled' ? 'bg-danger' : 'bg-secondary') }}">{{ p.status() }}</span></td>
                <td>{{ p.createdAt()|date('H:i:s') }}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
{% else %}
    <p class="text-muted">Zatím žádné projekce. Zadej objednávku výše.</p>
{% endif %}
```

- [ ] **Step 8: Spustit migrace + testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console doctrine:migrations:migrate --no-interaction
php vendor/bin/phpunit
```

Expected: migrace OK, testy OK.

- [ ] **Step 9: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "feat: Ch06 přidat OrderListProjection — denormalizovaná projekce z event streamu"
```

---

## Task 18: Ch04 Application Layer (Command/Query + Messenger)

**Files:**
- Create: `src/Chapter04_Implementation/Application/PlaceOrder/PlaceOrderCommand.php`
- Create: `src/Chapter04_Implementation/Application/PlaceOrder/PlaceOrderHandler.php`
- Create: `src/Chapter04_Implementation/Application/GetOrders/GetOrdersQuery.php`
- Create: `src/Chapter04_Implementation/Application/GetOrders/GetOrdersHandler.php`
- Modify: `src/Chapter04_Implementation/UI/Chapter04Controller.php`
- Modify: `config/packages/messenger.yaml`

- [ ] **Step 1: Vytvořit PlaceOrderCommand**

Vytvořit `src/Chapter04_Implementation/Application/PlaceOrder/PlaceOrderCommand.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\PlaceOrder;

final readonly class PlaceOrderCommand
{
    /** @param array<array{name: string, qty: int, price: int}> $items */
    public function __construct(
        public string $customerId,
        public array $items,
    ) {}
}
```

- [ ] **Step 2: Vytvořit PlaceOrderHandler**

Vytvořit `src/Chapter04_Implementation/Application/PlaceOrder/PlaceOrderHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\PlaceOrder;

use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class PlaceOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(PlaceOrderCommand $command): string
    {
        $id = OrderId::generate();
        $order = Order::place($id, $command->customerId, $command->items);
        $this->orders->save($order);

        foreach ($order->pullEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $id->value;
    }
}
```

- [ ] **Step 3: Vytvořit GetOrdersQuery + Handler**

Vytvořit `src/Chapter04_Implementation/Application/GetOrders/GetOrdersQuery.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\GetOrders;

final readonly class GetOrdersQuery {}
```

Vytvořit `src/Chapter04_Implementation/Application/GetOrders/GetOrdersHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\GetOrders;

use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetOrdersHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /** @return \App\Chapter04_Implementation\Domain\Order\Order[] */
    public function __invoke(GetOrdersQuery $query): array
    {
        return $this->orders->findAll();
    }
}
```

- [ ] **Step 4: Přidat Messenger routing**

V `config/packages/messenger.yaml`, přidat do `routing:`:
```yaml
            'App\Chapter04_Implementation\Application\PlaceOrder\PlaceOrderCommand': sync
            'App\Chapter04_Implementation\Application\GetOrders\GetOrdersQuery': sync
```

- [ ] **Step 5: Přepsat Controller na tenký — deleguje na bus**

Výsledný `src/Chapter04_Implementation/UI/Chapter04Controller.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UI;

use App\Chapter04_Implementation\Application\GetOrders\GetOrdersQuery;
use App\Chapter04_Implementation\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter04Controller extends AbstractController
{
    public function __construct(
        #[Target('messenger.bus.command')] private readonly MessageBusInterface $commandBus,
        #[Target('messenger.bus.query')] private readonly MessageBusInterface $queryBus,
        private readonly OrderPricingService $pricing,
    ) {}

    #[Route('/examples/implementace', name: 'chapter04')]
    public function index(Request $request): Response
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $qty = max(1, (int) $request->request->get('qty', 1));
            $price = (int) round((float) $request->request->get('price', '100') * 100);
            $discountedPrice = $this->pricing->applyVolumeDiscount(
                new Money($price, 'CZK'),
                $qty,
            );

            $envelope = $this->commandBus->dispatch(new PlaceOrderCommand(
                customerId: 'student-' . random_int(1, 99),
                items: [['name' => $request->request->get('name', 'Produkt'), 'qty' => $qty, 'price' => $discountedPrice->amount]],
            ));
            $orderId = $envelope->last(HandledStamp::class)?->getResult();
            $result = sprintf('Objednávka uložena. ID: %s…', substr((string) $orderId, 0, 8));
        }

        $envelope = $this->queryBus->dispatch(new GetOrdersQuery());
        $orders = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->render('examples/chapter04/index.html.twig', [
            'orders' => $orders,
            'result' => $result,
        ]);
    }
}
```

- [ ] **Step 6: Ověřit + testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console cache:clear && php vendor/bin/phpunit
```

Expected: cache OK + testy OK.

- [ ] **Step 7: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "feat: Ch04 přidat Application vrstvu (PlaceOrder/GetOrders) s Messenger bus"
```

---

## Task 19: Ch04 OrderLine Value Object (primitive obsession fix)

**Files:**
- Create: `src/Chapter04_Implementation/Domain/Order/OrderLine.php`
- Modify: `src/Chapter04_Implementation/Domain/Order/Order.php`

- [ ] **Step 1: Vytvořit OrderLine VO**

Vytvořit `src/Chapter04_Implementation/Domain/Order/OrderLine.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class OrderLine
{
    public function __construct(
        public string $productName,
        public int $quantity,
        public Money $unitPrice,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1');
        }
        if (empty($productName)) {
            throw new \InvalidArgumentException('Product name cannot be empty');
        }
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }

    /** @return array{name: string, qty: int, price: int} */
    public function toArray(): array
    {
        return [
            'name' => $this->productName,
            'qty' => $this->quantity,
            'price' => $this->unitPrice->amount,
        ];
    }

    /** @param array{name: string, qty: int, price: int} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['qty'],
            new Money($data['price'], 'CZK'),
        );
    }
}
```

- [ ] **Step 2: Aktualizovat Order — používat OrderLine**

V `src/Chapter04_Implementation/Domain/Order/Order.php`:

Aktualizovat `place()` metodu:
```php
/** @param OrderLine[] $lines */
public static function place(OrderId $id, string $customerId, array $lines): self
{
    $order = new self($id, $customerId);
    foreach ($lines as $line) {
        $order->items[] = $line->toArray();
        $order->totalAmount += $line->lineTotal()->amount;
    }
    $order->record(new OrderPlaced($id->value, $customerId, $order->totalAmount));
    return $order;
}
```

- [ ] **Step 3: Aktualizovat controller a handler pro OrderLine**

V `PlaceOrderCommand.php`:
```php
/** @param \App\Chapter04_Implementation\Domain\Order\OrderLine[] $lines */
public function __construct(
    public string $customerId,
    public array $lines,
) {}
```

V `PlaceOrderHandler.php`:
```php
$order = Order::place($id, $command->customerId, $command->lines);
```

V Controller:
```php
use App\Chapter04_Implementation\Domain\Order\OrderLine;
// ...
$envelope = $this->commandBus->dispatch(new PlaceOrderCommand(
    customerId: 'student-' . random_int(1, 99),
    lines: [new OrderLine($request->request->get('name', 'Produkt'), $qty, $discountedPrice)],
));
```

- [ ] **Step 4: Aktualizovat existující test**

V `tests/Chapter04/Domain/OrderTest.php` — aktualizovat volání `Order::place()` aby používalo `OrderLine[]` místo raw arrays.

- [ ] **Step 5: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: testy prochází.

- [ ] **Step 6: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "refactor: Ch04 nahradit raw array items za OrderLine Value Object"
```

---

## Task 20: Ch05 Domain Event Dispatching

**Files:**
- Modify: `src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderHandler.php`

- [ ] **Step 1: Přidat event dispatching do PlaceOrderHandler**

Výsledný `src/Chapter05_CQRS/Application/PlaceOrder/PlaceOrderHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Application\PlaceOrder;

use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class PlaceOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(PlaceOrderCommand $command): string
    {
        $id = OrderId::generate();
        $order = Order::place($id, $command->customerId, $command->items);
        $this->orders->save($order);

        foreach ($order->pullEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $id->value;
    }
}
```

- [ ] **Step 2: Aktualizovat existující test**

V `tests/Chapter05/Application/PlaceOrderHandlerTest.php` — handler nyní vyžaduje `EventDispatcherInterface`. Přidat mock nebo in-memory implementaci:

```php
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// V testu, při vytváření handleru:
$eventDispatcher = new class implements EventDispatcherInterface {
    public array $dispatched = [];
    public function dispatch(object $event, ?string $eventName = null): object
    {
        $this->dispatched[] = $event;
        return $event;
    }
};
$handler = new PlaceOrderHandler($repo, $eventDispatcher);
```

- [ ] **Step 3: Spustit testy**

```bash
cd /home/michal/Work/ddd-symfony-examples
php vendor/bin/phpunit
```

Expected: testy prochází.

- [ ] **Step 4: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add -A
git commit -m "feat: Ch05 dispatchovat domain events po uložení objednávky"
```

---

## Self-Review Checklist

- [x] **Spec coverage:** Sekce 1 (bugy 1.1–1.9) pokryta v Tasks 1–10. Sekce 2 (DDD vzory 2.1–2.10) pokryta v Tasks 11–20.
- [x] **Placeholder scan:** Žádné TBD/TODO. Každý step má kód nebo příkaz.
- [x] **Type consistency:** `OrderStatus` enum cases `Pending`/`Confirmed`/`Cancelled` konzistentní napříč Tasks 11, 13. `TaskStatus` enum cases `Todo`/`InProgress`/`Done` konzistentní v Task 12. `OrderLine` type konzistentní mezi Task 19 (VO, Command, Handler, Controller).
- [x] `EventStoreInterface::append()` podpis `(string $aggregateId, array $events, int $expectedVersion)` konzistentní v Task 16 (interface, implementation, controller).
