# ddd-symfony-examples Implementation Plan (Part 1: Infrastructure + Chapters 01–04)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vytvořit nový GitHub repozitář `ddd-symfony-examples` — plně funkční Symfony 8 projekt s živými ukázkami DDD konceptů mapovanými na kapitoly příručky.

**Architecture:** Modulární monorepo — jeden Symfony 8 projekt, každá kapitola jako PHP namespace (`App\ChapterXX_Topic\`). Sdílené primitives v `App\Shared\`. Doctrine ORM + SQLite, Symfony Messenger pro CQRS.

**Tech Stack:** PHP 8.3+, Symfony 8, Doctrine ORM + SQLite, Symfony Messenger, PHPUnit 11

**Poznámka:** Tento plán pokrývá Tasks 1–5. Navazující Tasks 6–13 (CQRS, Event Sourcing, Ságy, Testování, Migrace, Index, Makefile) jsou v `2026-03-26-ddd-symfony-examples-part2.md`.

---

### Task 1: Inicializace projektu

**Files:**
- Create: `.env`
- Create: `config/packages/doctrine.yaml`
- Create: `config/packages/messenger.yaml`

- [ ] **Step 1: Vytvoř nový Symfony projekt (spustit MIMO adresář ddd-v-symfony)**

```bash
cd ~/Work
symfony new ddd-symfony-examples --version="8.*" --webapp
cd ddd-symfony-examples
```

- [ ] **Step 2: Nainstaluj závislosti**

```bash
composer require symfony/messenger symfony/uid
composer require --dev phpunit/phpunit symfony/test-pack
```

- [ ] **Step 3: Nastav SQLite v .env**

Uprav `.env` — nahraď řádek s `DATABASE_URL`:
```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

- [ ] **Step 4: Ověř, že projekt startuje**

```bash
php bin/console about
```
Expected: výpis Symfony verze bez chyb.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: inicializace ddd-symfony-examples projektu"
```

---

### Task 2: Shared domain primitives

**Files:**
- Create: `src/Shared/Domain/DomainEvent.php`
- Create: `src/Shared/Domain/AggregateRoot.php`
- Test: `tests/Shared/AggregateRootTest.php`

- [ ] **Step 1: Napiš failing test**

`tests/Shared/AggregateRootTest.php`:
```php
<?php

namespace App\Tests\Shared;

use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\DomainEvent;
use PHPUnit\Framework\TestCase;

final class AggregateRootTest extends TestCase
{
    public function test_records_and_pulls_domain_events(): void
    {
        $aggregate = new class extends AggregateRoot {
            public function doSomething(): void
            {
                $this->record(new class implements DomainEvent {
                    public function occurredAt(): \DateTimeImmutable
                    {
                        return new \DateTimeImmutable();
                    }
                });
            }
        };

        $aggregate->doSomething();
        $events = $aggregate->pullEvents();

        $this->assertCount(1, $events);
        $this->assertEmpty($aggregate->pullEvents());
    }
}
```

- [ ] **Step 2: Spusť test — ověř selhání**

```bash
./vendor/bin/phpunit tests/Shared/ --testdox
```
Expected: FAIL — "Class AggregateRoot not found"

- [ ] **Step 3: Implementuj**

`src/Shared/Domain/DomainEvent.php`:
```php
<?php

namespace App\Shared\Domain;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
```

`src/Shared/Domain/AggregateRoot.php`:
```php
<?php

namespace App\Shared\Domain;

abstract class AggregateRoot
{
    private array $domainEvents = [];

    protected function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return DomainEvent[] */
    public function pullEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

- [ ] **Step 4: Spusť test — ověř průchod**

```bash
./vendor/bin/phpunit tests/Shared/ --testdox
```
Expected: PASS ✓ Records and pulls domain events

- [ ] **Step 5: Commit**

```bash
git add src/Shared/ tests/Shared/
git commit -m "feat: přidat Shared domain primitives"
```

---

### Task 3: Chapter01 — Co je DDD (čistý doménový model, bez DB)

**Files:**
- Create: `src/Chapter01_WhatIsDDD/Domain/Product/ProductId.php`
- Create: `src/Chapter01_WhatIsDDD/Domain/Product/Price.php`
- Create: `src/Chapter01_WhatIsDDD/Domain/Product/Product.php`
- Create: `src/Chapter01_WhatIsDDD/Domain/Cart/Cart.php`
- Create: `src/Chapter01_WhatIsDDD/UI/Chapter01Controller.php`
- Create: `templates/examples/chapter01/index.html.twig`
- Create: `src/Chapter01_WhatIsDDD/README.md`
- Test: `tests/Chapter01/Domain/CartTest.php`

- [ ] **Step 1: Napiš failing testy**

`tests/Chapter01/Domain/CartTest.php`:
```php
<?php

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\Cart\Cart;
use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\Product\ProductId;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    public function test_can_add_product_to_cart(): void
    {
        $cart = Cart::empty();
        $product = new Product(ProductId::generate(), 'Symfony kniha', new Price(59900, 'CZK'));
        $cart->add($product, 2);

        $this->assertSame(2, $cart->itemCount());
        $this->assertEquals(new Price(119800, 'CZK'), $cart->total());
    }

    public function test_cannot_add_product_with_zero_quantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $cart = Cart::empty();
        $cart->add(new Product(ProductId::generate(), 'Kniha', new Price(59900, 'CZK')), 0);
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter01/ --testdox
```
Expected: FAIL — "Class Cart not found"

- [ ] **Step 3: Implementuj doménové třídy**

`src/Chapter01_WhatIsDDD/Domain/Product/ProductId.php`:
```php
<?php

namespace App\Chapter01_WhatIsDDD\Domain\Product;

final readonly class ProductId
{
    public function __construct(public readonly string $value)
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

`src/Chapter01_WhatIsDDD/Domain/Product/Price.php`:
```php
<?php

namespace App\Chapter01_WhatIsDDD\Domain\Product;

final readonly class Price
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    public function multiply(int $qty): self
    {
        return new self($this->amount * $qty, $this->currency);
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add prices in different currencies');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function formatted(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }
}
```

`src/Chapter01_WhatIsDDD/Domain/Product/Product.php`:
```php
<?php

namespace App\Chapter01_WhatIsDDD\Domain\Product;

final class Product
{
    public function __construct(
        private readonly ProductId $id,
        private readonly string $name,
        private readonly Price $price,
    ) {}

    public function id(): ProductId { return $this->id; }
    public function name(): string { return $this->name; }
    public function price(): Price { return $this->price; }
}
```

`src/Chapter01_WhatIsDDD/Domain/Cart/Cart.php`:
```php
<?php

namespace App\Chapter01_WhatIsDDD\Domain\Cart;

use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;

final class Cart
{
    /** @var array<string, array{product: Product, qty: int}> */
    private array $items = [];

    private function __construct() {}

    public static function empty(): self
    {
        return new self();
    }

    public function add(Product $product, int $qty): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }
        $id = $product->id()->value;
        if (isset($this->items[$id])) {
            $this->items[$id]['qty'] += $qty;
        } else {
            $this->items[$id] = ['product' => $product, 'qty' => $qty];
        }
    }

    public function itemCount(): int
    {
        return array_sum(array_column($this->items, 'qty'));
    }

    public function total(): Price
    {
        $total = null;
        foreach ($this->items as ['product' => $product, 'qty' => $qty]) {
            $lineTotal = $product->price()->multiply($qty);
            $total = $total === null ? $lineTotal : $total->add($lineTotal);
        }
        return $total ?? new Price(0, 'CZK');
    }

    /** @return array<array{name: string, qty: int, lineTotal: string}> */
    public function summary(): array
    {
        return array_values(array_map(
            fn($item) => [
                'name' => $item['product']->name(),
                'qty' => $item['qty'],
                'lineTotal' => $item['product']->price()->multiply($item['qty'])->formatted(),
            ],
            $this->items,
        ));
    }
}
```

- [ ] **Step 4: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter01/ --testdox
```
Expected: PASS ✓ 2 testy

- [ ] **Step 5: Implementuj controller**

`src/Chapter01_WhatIsDDD/UI/Chapter01Controller.php`:
```php
<?php

namespace App\Chapter01_WhatIsDDD\UI;

use App\Chapter01_WhatIsDDD\Domain\Cart\Cart;
use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\Product\ProductId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter01Controller extends AbstractController
{
    private static array $catalog = [
        ['name' => 'Symfony v praxi', 'price' => 59900],
        ['name' => 'Domain-Driven Design', 'price' => 89900],
        ['name' => 'Clean Architecture', 'price' => 74900],
    ];

    #[Route('/examples/co-je-ddd', name: 'chapter01')]
    public function index(Request $request): Response
    {
        $products = array_map(
            fn($p) => new Product(ProductId::generate(), $p['name'], new Price($p['price'], 'CZK')),
            self::$catalog,
        );

        $cart = Cart::empty();
        if ($request->isMethod('POST')) {
            foreach ($request->request->all('items') as $idx => $qty) {
                $qty = (int) $qty;
                if ($qty > 0 && isset($products[$idx])) {
                    $cart->add($products[$idx], $qty);
                }
            }
        }

        return $this->render('examples/chapter01/index.html.twig', [
            'products' => $products,
            'cart' => $cart,
        ]);
    }
}
```

- [ ] **Step 6: Vytvoř template**

`templates/examples/chapter01/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Co je DDD — čistý doménový model{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('what_is_ddd') }}"><strong>Co je Domain-Driven Design?</strong></a>
    </div>
    <h1>Ukázka: Čistý doménový model</h1>
    <p>Nákupní košík bez databáze — čistá doménová logika v paměti.</p>

    <form method="post">
        <table class="table">
            <thead><tr><th>Produkt</th><th>Cena</th><th>Množství</th></tr></thead>
            <tbody>
            {% for i, product in products %}
                <tr>
                    <td>{{ product.name() }}</td>
                    <td>{{ product.price().formatted() }}</td>
                    <td><input type="number" name="items[{{ i }}]" value="0" min="0" max="10" class="form-control" style="width:80px"></td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Přidat do košíku</button>
    </form>

    {% if cart.itemCount() > 0 %}
    <div class="card mt-4">
        <div class="card-header">Košík ({{ cart.itemCount() }} kusů)</div>
        <ul class="list-group list-group-flush">
            {% for item in cart.summary() %}
            <li class="list-group-item d-flex justify-content-between">
                <span>{{ item.name }} × {{ item.qty }}</span>
                <span>{{ item.lineTotal }}</span>
            </li>
            {% endfor %}
        </ul>
        <div class="card-footer text-end fw-bold">Celkem: {{ cart.total().formatted() }}</div>
    </div>
    {% endif %}
</div>
{% endblock %}
```

- [ ] **Step 7: Vytvoř README**

`src/Chapter01_WhatIsDDD/README.md`:
```markdown
# Kapitola 1: Co je DDD

**Článek v příručce:** https://ddd-symfony.cz/co-je-ddd

## Co tato ukázka ukazuje

- Doménový model bez závislosti na frameworku nebo DB
- Value Object (`Price`, `ProductId`) — neměnné objekty s vlastní logikou
- Entity (`Product`) — objekt s identitou
- Doménová logika ve třídě `Cart`

## Spuštění

```bash
symfony server:start
# http://localhost:8000/examples/co-je-ddd
```
```

- [ ] **Step 8: Commit**

```bash
git add src/Chapter01_WhatIsDDD/ tests/Chapter01/ templates/examples/chapter01/
git commit -m "feat: přidat Chapter01 — Co je DDD (košík, čistý doménový model)"
```

---

### Task 4: Chapter03 — Základní koncepty (Entity, Value Objects, Agregát)

**Files:**
- Create: `src/Chapter03_BasicConcepts/Domain/Order/OrderId.php`
- Create: `src/Chapter03_BasicConcepts/Domain/Order/Money.php`
- Create: `src/Chapter03_BasicConcepts/Domain/Order/OrderStatus.php`
- Create: `src/Chapter03_BasicConcepts/Domain/Order/OrderItem.php`
- Create: `src/Chapter03_BasicConcepts/Domain/Order/Order.php`
- Create: `src/Chapter03_BasicConcepts/Domain/Repository/OrderRepositoryInterface.php`
- Create: `src/Chapter03_BasicConcepts/Infrastructure/Persistence/InMemoryOrderRepository.php`
- Create: `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`
- Create: `templates/examples/chapter03/index.html.twig`
- Test: `tests/Chapter03/Domain/OrderTest.php`

- [ ] **Step 1: Napiš failing testy**

`tests/Chapter03/Domain/OrderTest.php`:
```php
<?php

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_new_order_is_pending(): void
    {
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $this->assertTrue($order->status()->isPending());
    }

    public function test_can_add_item_to_pending_order(): void
    {
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->addItem('Symfony kniha', 2, new Money(59900, 'CZK'));
        $this->assertEquals(new Money(119800, 'CZK'), $order->total());
    }

    public function test_cannot_add_item_to_confirmed_order(): void
    {
        $this->expectException(\DomainException::class);
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->addItem('Produkt', 1, new Money(10000, 'CZK'));
        $order->confirm();
        $order->addItem('Další', 1, new Money(5000, 'CZK'));
    }

    public function test_cannot_confirm_empty_order(): void
    {
        $this->expectException(\DomainException::class);
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->confirm();
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter03/ --testdox
```
Expected: FAIL — "Class Order not found"

- [ ] **Step 3: Implementuj doménové třídy**

`src/Chapter03_BasicConcepts/Domain/Order/OrderId.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\Domain\Order;

final readonly class OrderId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('OrderId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
```

`src/Chapter03_BasicConcepts/Domain/Order/Money.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\Domain\Order;

final readonly class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies');
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

`src/Chapter03_BasicConcepts/Domain/Order/OrderStatus.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\Domain\Order;

final readonly class OrderStatus
{
    private function __construct(private readonly string $value) {}

    public static function pending(): self { return new self('pending'); }
    public static function confirmed(): self { return new self('confirmed'); }
    public static function cancelled(): self { return new self('cancelled'); }

    public function isPending(): bool { return $this->value === 'pending'; }
    public function isConfirmed(): bool { return $this->value === 'confirmed'; }
    public function value(): string { return $this->value; }
}
```

`src/Chapter03_BasicConcepts/Domain/Order/OrderItem.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\Domain\Order;

final class OrderItem
{
    public function __construct(
        private readonly string $name,
        private readonly int $quantity,
        private readonly Money $unitPrice,
    ) {}

    public function name(): string { return $this->name; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): Money { return $this->unitPrice; }
    public function lineTotal(): Money { return $this->unitPrice->multiply($this->quantity); }
}
```

`src/Chapter03_BasicConcepts/Domain/Order/Order.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\Domain\Order;

use App\Shared\Domain\AggregateRoot;

final class Order extends AggregateRoot
{
    /** @var OrderItem[] */
    private array $items = [];
    private OrderStatus $status;

    private function __construct(
        private readonly OrderId $id,
        private readonly string $customerId,
    ) {
        $this->status = OrderStatus::pending();
    }

    public static function create(OrderId $id, string $customerId): self
    {
        return new self($id, $customerId);
    }

    public function addItem(string $name, int $qty, Money $unitPrice): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Cannot add items to a non-pending order');
        }
        $this->items[] = new OrderItem($name, $qty, $unitPrice);
    }

    public function confirm(): void
    {
        if (empty($this->items)) {
            throw new \DomainException('Cannot confirm an empty order');
        }
        $this->status = OrderStatus::confirmed();
    }

    public function id(): OrderId { return $this->id; }
    public function customerId(): string { return $this->customerId; }
    public function status(): OrderStatus { return $this->status; }

    public function total(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $carry, OrderItem $item) => $carry->add($item->lineTotal()),
            new Money(0, 'CZK'),
        );
    }

    /** @return OrderItem[] */
    public function items(): array { return $this->items; }
}
```

- [ ] **Step 4: Implementuj Repository**

`src/Chapter03_BasicConcepts/Domain/Repository/OrderRepositoryInterface.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\Domain\Repository;

use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(OrderId $id): ?Order;
    /** @return Order[] */
    public function findAll(): array;
}
```

`src/Chapter03_BasicConcepts/Infrastructure/Persistence/InMemoryOrderRepository.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\Infrastructure\Persistence;

use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Repository\OrderRepositoryInterface;

final class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var Order[] */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->id()->value] = $order;
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->orders[$id->value] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->orders);
    }
}
```

- [ ] **Step 5: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter03/ --testdox
```
Expected: PASS ✓ 4 testy

- [ ] **Step 6: Implementuj controller + template**

`src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`:
```php
<?php

namespace App\Chapter03_BasicConcepts\UI;

use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter03Controller extends AbstractController
{
    #[Route('/examples/zakladni-koncepty', name: 'chapter03')]
    public function index(Request $request): Response
    {
        $order = Order::create(OrderId::generate(), 'student-1');
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            try {
                match ($action) {
                    'add_item' => (function () use ($order, $request, &$result) {
                        $order->addItem(
                            $request->request->get('name', 'Produkt'),
                            max(1, (int) $request->request->get('qty', 1)),
                            new Money((int) ($request->request->get('price', 100) * 100), 'CZK'),
                        );
                        $result = 'Položka přidána. Celkem: ' . $order->total()->formatted();
                    })(),
                    'confirm_with_item' => (function () use ($order, &$result) {
                        $order->addItem('Demo produkt', 1, new Money(10000, 'CZK'));
                        $order->confirm();
                        $result = 'Objednávka potvrzena. Stav: ' . $order->status()->value();
                    })(),
                    'confirm_empty' => $order->confirm(),
                    default => null,
                };
            } catch (\DomainException $e) {
                $error = 'DomainException: ' . $e->getMessage();
            }
        }

        return $this->render('examples/chapter03/index.html.twig', [
            'order' => $order,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
```

`templates/examples/chapter03/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Základní koncepty DDD{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('basic_concepts') }}"><strong>Základní koncepty DDD</strong></a>
    </div>
    <h1>Ukázka: Entity, Value Objects, Agregát</h1>
    <p>Objednávka jako agregát — chrání svá doménová invarianty.</p>

    {% if result %}<div class="alert alert-success">{{ result }}</div>{% endif %}
    {% if error %}<div class="alert alert-danger">{{ error }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-6">
            <h3>Order.addItem()</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_item">
                <div class="mb-2"><input type="text" name="name" value="Symfony kniha" class="form-control" placeholder="Název produktu"></div>
                <div class="mb-2"><input type="number" name="qty" value="2" min="1" class="form-control" placeholder="Množství"></div>
                <div class="mb-2"><input type="number" name="price" value="599" class="form-control" placeholder="Cena (CZK)"></div>
                <button class="btn btn-primary">Přidat položku</button>
            </form>
        </div>
        <div class="col-md-6">
            <h3>Doménová pravidla</h3>
            <form method="post" class="mb-2">
                <input type="hidden" name="action" value="confirm_with_item">
                <button class="btn btn-success w-100">Order.confirm() — úspěch</button>
            </form>
            <form method="post">
                <input type="hidden" name="action" value="confirm_empty">
                <button class="btn btn-warning w-100">Order.confirm() na prázdné → DomainException</button>
            </form>
        </div>
    </div>
</div>
{% endblock %}
```

- [ ] **Step 7: Commit**

```bash
git add src/Chapter03_BasicConcepts/ tests/Chapter03/ templates/examples/chapter03/
git commit -m "feat: přidat Chapter03 — Základní koncepty (Entity, VO, Agregát)"
```

---

### Task 5: Chapter04 — Implementace (Doctrine/SQLite, Domain Events, Domain Services)

**Files:**
- Create: `src/Chapter04_Implementation/Domain/Order/OrderId.php`
- Create: `src/Chapter04_Implementation/Domain/Order/Money.php`
- Create: `src/Chapter04_Implementation/Domain/Order/OrderStatus.php`
- Create: `src/Chapter04_Implementation/Domain/Order/OrderItem.php`
- Create: `src/Chapter04_Implementation/Domain/Order/Order.php`
- Create: `src/Chapter04_Implementation/Domain/Order/OrderPlaced.php`
- Create: `src/Chapter04_Implementation/Domain/Service/OrderPricingService.php`
- Create: `src/Chapter04_Implementation/Domain/Repository/OrderRepositoryInterface.php`
- Create: `src/Chapter04_Implementation/Infrastructure/Persistence/DoctrineOrderRepository.php`
- Create: `src/Chapter04_Implementation/UI/Chapter04Controller.php`
- Create: `templates/examples/chapter04/index.html.twig`
- Create: `migrations/` (vygenerovaná Doctrine migrace)
- Test: `tests/Chapter04/Domain/OrderTest.php`

Tato kapitola jako první používá Doctrine + SQLite. Domain objects mají Doctrine atributy přímo na sebe (pragmatický přístup pro demo; README zmiňuje trade-off).

- [ ] **Step 1: Napiš failing testy**

`tests/Chapter04/Domain/OrderTest.php`:
```php
<?php

namespace App\Tests\Chapter04\Domain;

use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_order_raises_domain_event_when_placed(): void
    {
        $order = Order::place(OrderId::generate(), 'zákazník-1', [
            ['name' => 'Symfony kniha', 'qty' => 1, 'price' => 59900],
        ]);

        $events = $order->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(
            \App\Chapter04_Implementation\Domain\Order\OrderPlaced::class,
            $events[0],
        );
    }

    public function test_domain_service_applies_discount(): void
    {
        $service = new \App\Chapter04_Implementation\Domain\Service\OrderPricingService();

        $price = $service->applyVolumeDiscount(new Money(100000, 'CZK'), 3);
        $this->assertEquals(new Money(90000, 'CZK'), $price); // 10% sleva
    }
}
```

- [ ] **Step 2: Spusť — ověř selhání**

```bash
./vendor/bin/phpunit tests/Chapter04/ --testdox
```
Expected: FAIL — "Class Order not found"

- [ ] **Step 3: Implementuj doménové třídy**

`src/Chapter04_Implementation/Domain/Order/OrderId.php`:
```php
<?php

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class OrderId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('OrderId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
```

`src/Chapter04_Implementation/Domain/Order/Money.php`:
```php
<?php

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {}

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $qty): self
    {
        return new self($this->amount * $qty, $this->currency);
    }

    public function percentage(int $pct): self
    {
        return new self((int) ($this->amount * $pct / 100), $this->currency);
    }

    public function formatted(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }
}
```

`src/Chapter04_Implementation/Domain/Order/OrderPlaced.php`:
```php
<?php

namespace App\Chapter04_Implementation\Domain\Order;

use App\Shared\Domain\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly int $totalAmount,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

`src/Chapter04_Implementation/Domain/Order/OrderStatus.php`:
```php
<?php

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class OrderStatus
{
    private function __construct(private readonly string $value) {}

    public static function pending(): self { return new self('pending'); }
    public static function confirmed(): self { return new self('confirmed'); }
    public function value(): string { return $this->value; }
    public function isPending(): bool { return $this->value === 'pending'; }
}
```

`src/Chapter04_Implementation/Domain/Order/Order.php`:
```php
<?php

namespace App\Chapter04_Implementation\Domain\Order;

use App\Shared\Domain\AggregateRoot;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ch04_orders')]
class Order extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string')]
    private string $customerId;

    #[ORM\Column(type: 'integer')]
    private int $totalAmount = 0;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'json')]
    private array $items = [];

    private function __construct(OrderId $id, string $customerId)
    {
        $this->id = $id->value;
        $this->customerId = $customerId;
        $this->status = 'pending';
    }

    /**
     * @param array<array{name: string, qty: int, price: int}> $items
     */
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
    public function status(): OrderStatus { return OrderStatus::pending(); }
    public function total(): Money { return new Money($this->totalAmount, 'CZK'); }
    /** @return array<array{name: string, qty: int, price: int}> */
    public function items(): array { return $this->items; }
}
```

`src/Chapter04_Implementation/Domain/Service/OrderPricingService.php`:
```php
<?php

namespace App\Chapter04_Implementation\Domain\Service;

use App\Chapter04_Implementation\Domain\Order\Money;

final class OrderPricingService
{
    public function applyVolumeDiscount(Money $price, int $itemCount): Money
    {
        if ($itemCount >= 3) {
            return $price->add($price->percentage(-10));
        }
        if ($itemCount >= 2) {
            return $price->add($price->percentage(-5));
        }
        return $price;
    }
}
```

`src/Chapter04_Implementation/Domain/Repository/OrderRepositoryInterface.php`:
```php
<?php

namespace App\Chapter04_Implementation\Domain\Repository;

use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(OrderId $id): ?Order;
    /** @return Order[] */
    public function findAll(): array;
}
```

`src/Chapter04_Implementation/Infrastructure/Persistence/DoctrineOrderRepository.php`:
```php
<?php

namespace App\Chapter04_Implementation\Infrastructure\Persistence;

use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
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

- [ ] **Step 4: Zaregistruj repository jako service**

Přidej do `config/services.yaml`:
```yaml
    App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface:
        class: App\Chapter04_Implementation\Infrastructure\Persistence\DoctrineOrderRepository
```

- [ ] **Step 5: Vygeneruj a spusť migraci**

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```
Expected: vytvoří tabulku `ch04_orders`.

- [ ] **Step 6: Spusť testy — ověř průchod**

```bash
./vendor/bin/phpunit tests/Chapter04/ --testdox
```
Expected: PASS ✓ 2 testy

- [ ] **Step 7: Implementuj controller**

`src/Chapter04_Implementation/UI/Chapter04Controller.php`:
```php
<?php

namespace App\Chapter04_Implementation\UI;

use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter04Controller extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderPricingService $pricing,
    ) {}

    #[Route('/examples/implementace', name: 'chapter04')]
    public function index(Request $request): Response
    {
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $qty = max(1, (int) $request->request->get('qty', 1));
            $price = (int) ($request->request->get('price', 100) * 100);
            $discountedPrice = $this->pricing->applyVolumeDiscount(
                new \App\Chapter04_Implementation\Domain\Order\Money($price, 'CZK'),
                $qty,
            );

            $order = Order::place(
                OrderId::generate(),
                'student-' . rand(1, 99),
                [['name' => $request->request->get('name', 'Produkt'), 'qty' => $qty, 'price' => $discountedPrice->amount]],
            );
            $this->orders->save($order);

            $events = $order->pullEvents();
            $result = sprintf(
                'Objednávka uložena. Celkem: %s. Domain event: %s',
                $order->total()->formatted(),
                get_class($events[0] ?? new \stdClass()),
            );
        }

        return $this->render('examples/chapter04/index.html.twig', [
            'orders' => $this->orders->findAll(),
            'result' => $result,
            'error' => $error,
        ]);
    }
}
```

- [ ] **Step 8: Vytvoř template**

`templates/examples/chapter04/index.html.twig`:
```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Implementace DDD v Symfony{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="{{ path('implementation_in_symfony') }}"><strong>Implementace DDD v Symfony</strong></a>
    </div>
    <h1>Ukázka: Repository, Domain Service, Domain Event</h1>

    {% if result %}<div class="alert alert-success">{{ result }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-5">
            <h3>Vytvořit objednávku</h3>
            <form method="post">
                <div class="mb-2"><input type="text" name="name" value="Symfony kurz" class="form-control" placeholder="Produkt"></div>
                <div class="mb-2"><input type="number" name="qty" value="3" min="1" class="form-control" placeholder="Množství (≥3 = 10% sleva)"></div>
                <div class="mb-2"><input type="number" name="price" value="1000" class="form-control" placeholder="Cena za kus (CZK)"></div>
                <button class="btn btn-primary">Zadat objednávku</button>
            </form>
            <p class="text-muted mt-2"><small>OrderPricingService: 2+ ks = −5 %, 3+ ks = −10 %</small></p>
        </div>
        <div class="col-md-7">
            <h3>Uložené objednávky (Doctrine + SQLite)</h3>
            {% if orders is empty %}
                <p class="text-muted">Zatím žádné objednávky.</p>
            {% else %}
                <table class="table table-sm">
                    <thead><tr><th>ID</th><th>Zákazník</th><th>Celkem</th></tr></thead>
                    <tbody>
                    {% for order in orders %}
                        <tr>
                            <td><code>{{ order.id().value|slice(0,8) }}…</code></td>
                            <td>{{ order.customerId() }}</td>
                            <td>{{ order.total().formatted() }}</td>
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

- [ ] **Step 9: Commit**

```bash
git add src/Chapter04_Implementation/ tests/Chapter04/ templates/examples/chapter04/ config/services.yaml migrations/
git commit -m "feat: přidat Chapter04 — Implementace (Doctrine, Domain Service, Domain Event)"
```
