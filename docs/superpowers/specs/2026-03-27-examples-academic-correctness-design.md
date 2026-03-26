# Design: Akademická správnost ddd-symfony-examples

**Datum:** 2026-03-27
**Autor:** Michal Katuščák
**Status:** Schváleno

---

## Cíl

Zajistit, že repozitář `ddd-symfony-examples` je plně akademicky správný — kód odpovídá tomu, co učí články v příručce `ddd-v-symfony`, neobsahuje bugy, správně demonstruje DDD vzory, a má dostatečné pokrytí testy.

---

## Sekce 1: Opravy bugů

### 1.1 EventStore ordering (Ch06)
`DoctrineEventStore::load()` — přidat `['id' => 'ASC']` do `findBy()`. Bez toho se eventy mohou replayovat v nesprávném pořadí.

### 1.2 Money currency guard (Ch03, Ch04, Ch05)
`Money::add()` bude validovat `$this->currency === $other->currency`, jinak `\InvalidArgumentException`.

### 1.3 Float-to-int rounding (Ch03, Ch04, Ch05 Controllers)
`(int) round($value * 100)` místo `(int)($value * 100)`. Oprava floating-point chyby (19.99 → 1998 místo 1999).

### 1.4 Ch06 Controller
- Typ na `EventStoreInterface` místo `DoctrineEventStore`
- Přidat guard na prázdný event stream (neexistující agregát → výjimka)

### 1.5 Redundantní `readonly`
Odstranit z promoted properties ve všech `readonly class` (15+ souborů).

### 1.6 Inline FQCN
Přidat `use Symfony\Component\Uid\Uuid;` do všech `*Id.php` souborů, nahradit inline FQCN.

### 1.7 Ch06 `Order::apply()`
`default => throw new \LogicException('Unknown event')` místo `default => null`.

### 1.8 `declare(strict_types=1)`
Přidat do všech PHP souborů v `src/` a `tests/`.

### 1.9 README.md a .env
- Opravit `your-org` → `MichalKatuscak`
- Vygenerovat APP_SECRET

---

## Sekce 2: DDD vzory a akademická správnost

### 2.1 PHP enums
`OrderStatus` (Ch03, Ch04) a `TaskStatus` (Ch08, Ch09) přepsat na backed enums:
```php
enum OrderStatus: string {
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
```
Upravit všechny agregáty a controllery.

### 2.2 XML Doctrine mapping (Ch04, Ch05, Ch06)
- Odstranit `#[ORM\...]` atributy z doménových tříd
- Vytvořit `config/doctrine/` s XML mapping soubory
- Registrovat v `doctrine.yaml` jako separátní mapping s `type: xml`
- Doménové třídy budou čistě POPO

### 2.3 Custom Doctrine typy pro Value Objects (Ch04, Ch05)
- Vytvořit `OrderIdType` a `MoneyAmountType`
- Registrovat v `doctrine.yaml`
- Value Objects se mapují nativně

### 2.4 Application vrstva (Ch04)
- Přidat `PlaceOrderCommand`, `PlaceOrderHandler`, `GetOrderQuery`, `GetOrderHandler`
- Controller deleguje na Messenger bus

### 2.5 Ch05 domain event dispatching
- Po `save()` v `PlaceOrderHandler` → `$order->pullEvents()` → dispatch přes `EventDispatcherInterface`
- Ukázat kompletní event flow

### 2.6 Ch06 optimistic concurrency
- `expectedVersion` parametr v `EventStoreInterface::append()`
- `DoctrineEventStore` ověří aktuální počet eventů
- Při konfliktu `ConcurrencyException`

### 2.7 Ch06 projekce
- `OrderListProjection` — denormalizovaná tabulka
- `OrderListProjector` jako event subscriber
- Nová migrace pro projekční tabulku

### 2.8 Stavové přechody
- Ch06 `Order`: guardy (nelze confirm cancelled, nelze cancel confirmed)
- Ch08 `Task::complete()`: guard (musí být `in_progress`)

### 2.9 Ch09 title validace
Přidat `empty title` guard, srovnat s Ch08.

### 2.10 Primitive obsession (Ch04)
`Order::$items` z raw array na `OrderLine` value object collection.

---

## Sekce 3: Chybějící kód a přepisy kapitol

### 3.1 Ch07 Ságy — kompletní přepis

Nahradit synchronní funkci plným Process Manager vzorem:

**Domain:**
- `OrderFulfillmentSaga.php` — entita s persistentním stavem
- `SagaState` enum: `Started`, `StockReserved`, `PaymentProcessed`, `Shipped`, `Compensating`, `Failed`, `Completed`
- `SagaStep.php` — value object

**Domain Events:**
- `OrderPlaced`, `StockReserved`, `StockReservationFailed`
- `PaymentProcessed`, `PaymentFailed`
- `OrderShipped`, `ShipmentFailed`

**Application Handlers (Messenger):**
- `ReserveStockHandler`, `ProcessPaymentHandler`, `ShipOrderHandler`

**Compensation:**
- `ReleaseStockCommand` + handler
- `RefundPaymentCommand` + handler

**Infrastructure:**
- `DoctrineSagaRepository.php`
- Doctrine XML mapping + migrace pro `ch07_sagas`

**UI:**
- Formulář s volbou scénáře (success, stock fail, payment fail, shipping fail)
- Zobrazení průběhu ságy krok po kroku s kompenzacemi

**Config:**
- Messenger routování commands na sync transport
- Logging middleware

### 3.2 Ch09 Migrace — přidat CRUD "before" kód

**CrudVersion/:**
- `TaskController.php` — anemic CRUD controller s přímým EntityManager
- `Task.php` — anemic entita s public settery, žádná business logika

**DddVersion/:**
- Stávající DDD kód (přesunutý)

**UI:**
- Controller ukáže oba přístupy side-by-side
- Student vidí kde CRUD selže (nevalidní přechod projde tiše) a kde DDD chrání
- Dvousloupcové porovnání v šabloně

### 3.3 Ch01 rozšíření
- `Domain/ContextMap/` — Anti-Corruption Layer interface mezi Catalog a Order
- `Domain/SharedKernel/ProductId.php` — shared value object
- Aktualizace controlleru a šablony

### 3.4 Ch03 Domain Service
`OrderConfirmationService` — doménová služba validující potvrzení objednávky přes repozitář.

### 3.5 Per-chapter README.md
Vytvořit pro Ch03–Ch09: CZ popis, odkaz na článek, popis co ukázka demonstruje.

### 3.6 Navigace v šablonách
Breadcrumbs a prev/next linky v chapter šablonách.

---

## Sekce 4: Testy

### 4.1 Ch01
- `ProductTest`, `PriceTest` (immutabilita, validace)
- `ProductIdTest`
- `BoundedContextTest` (CatalogProduct vs OrderProduct)

### 4.2 Ch03
- `MoneyTest` (add, currency mismatch, percentage, formatted)
- `OrderStatusTest` (enum cases)
- `InMemoryOrderRepositoryTest`
- `OrderConfirmationServiceTest`

### 4.3 Ch04
- `MoneyTest`, `OrderLineTest`, `OrderStatusTest`
- `OrderPricingServiceTest`
- `PlaceOrderHandlerTest`, `GetOrderHandlerTest`
- Custom Doctrine type testy (`OrderIdType`, `MoneyAmountType`)

### 4.4 Ch05
- `GetOrdersHandlerTest` (query strana)
- Test domain event dispatching

### 4.5 Ch06
- `EventStoreTest` (append, load ordering, concurrency)
- `OrderProjectionTest`
- `OrderTest` (place/confirm/cancel + stavové guardy)

### 4.6 Ch07
- Happy path test
- Test každého failure scénáře s kompenzacemi
- Test persistentního stavu
- Test reverse-order kompenzací

### 4.7 Ch08
- `TaskIdTest`, `TaskStatusTest` (enum)
- `InMemoryTaskRepository` + test
- Integrační test s Doctrine (KernelTestCase)
- Showcase best practices — tato kapitola je o testování

### 4.8 Ch09
- Test CRUD verze (propustí nevalidní přechod)
- Test DDD verze (chrání invarianty)
- Porovnávací test side-by-side

### 4.9 Shared
- `AggregateRootTest` edge cases (pull twice, multiple events)

**Cíl:** Každý soubor v `src/` má odpovídající test. Ch08 slouží jako showcase testovací pyramidy.

---

## Co není v scope

- Autentizace a autorizace
- API endpointy (REST/GraphQL)
- Frontend framework
- CI/CD pipeline
- Deployment
- Async transport (vše běží na sync pro demo)
