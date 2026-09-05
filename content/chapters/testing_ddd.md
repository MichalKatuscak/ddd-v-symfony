---
route: testing_ddd
path: /testovani-ddd
title: Testování DDD kódu v Symfony
page_title: "Testování DDD kódu v Symfony | DDD Symfony"
meta_description: "Testování DDD kódu v Symfony: unit testy agregátů, integrace přes Doctrine, in-memory repozitáře, testy doménových událostí a architektonické testy (Deptrac)."
meta_keywords: "testování DDD, PHPUnit, unit testy, integrační testy, funkční testy, InMemory repozitář, test doubles, doménové události, Deptrac, phparkitect, KernelTestCase, WebTestCase, Symfony testy, testovací pyramida, coverage, messenger-test, async testování"
og_type: article
published: "2025-04-24"
modified: "2026-07-08"
breadcrumb_name: Testování DDD
schema_type: TechArticle
schema_headline: "Testování DDD kódu v Symfony"
chapter_number: "17"
category: Praxe
deck: "Testování Domain-Driven Design kódu v Symfony v praxi. Unit testy doménové vrstvy, integrační testy s Doctrine, funkční testy API, InMemory repozitáře, testování doménových událostí a architektonické testy s Deptrac."
reading_time: 40
difficulty: 3
github_examples: Chapter08_Testing
---

## 17.01 Filozofie testování v DDD {#filozofie-testovani}

Doménová vrstva v DDD nezávisí na frameworku ani na databázi, takže ji lze testovat přímo z PHPUnitu bez
bootstrappingu Symfony kernelu. To je hlavní praktický rozdíl proti tradičním vrstveným architekturám,
kde unit testy potřebují kontejner a každý z nich zaplatí bootstrap. Bez kernelu je jeden test řádově
rychlejší a celou doménovou sadu má smysl spouštět po každé změně, ne jen v CI. Stavební kameny doménové
vrstvy – entity, hodnotové objekty, agregáty, doménové události – popisuje kapitola
[Základní koncepty DDD](/zakladni-koncepty).

:::diagram{fig="17.1-A" title="Testovací pyramida pro DDD aplikaci – poměr a obsah jednotlivých vrstev" src="images/diagrams/18_testing_ddd/test_pyramid.svg"}
:::

:::callout{type="note"}
### Proč je DDD dobře testovatelný

Doménové třídy – entity, value objects, agregáty – jsou čisté PHP objekty bez závislosti na frameworku. Nepotřebují Symfony kontejner, Doctrine ani HTTP stack. Závislosti dostávají výhradně přes konstruktor (constructor injection), nikoli ze statických globálních objektů, takže je test může zaměnit za test doubles. Logika je přitom soustředěna v doménových objektech, ne roztroušena po kontrolerech a šablonách – testy proto pokrývají chování, na kterém záleží. A protože value objekty a agregáty ověřují svá invariantní pravidla už v konstruktoru nebo v továrních metodách, dá se testovat správný i nesprávný stav.
:::

### Testovací pyramida pro DDD

Testovací pyramida (koncept popularizovaný Mikem Cohnem v knize *Succeeding with Agile*, 2009
[[1]](https://www.mountaingoatsoftware.com/blog/the-forgotten-layer-of-the-test-automation-pyramid))
rozděluje testovací sadu do tří vrstev. Liší se rychlostí, mírou izolace a tím, kolik kódu jeden test pokryje:

:::callout{type="note"}
### Vrstvy testovací pyramidy:

- **Unit testy – doménová vrstva (základ pyramidy, nejvíce testů)**
  Testují izolované doménové objekty: value objects, entity, agregáty a doménové služby.
  Nepotřebují databázi ani framework. Jsou rychlé (stovky testů za sekundu).
  Cíl: ověřit doménová pravidla a invarianty.
- **Integrační testy – infrastrukturní vrstva (střed pyramidy)**
  Testují spolupráci doménového kódu s infrastrukturou: Doctrine repozitáře, e-mailové odesílatele, messagingové systémy.
  Vyžadují databázi nebo jiné externí zdroje. Jsou pomalejší, ale ověřují mapování a persistenci.
  Cíl: ověřit, že infrastruktura správně implementuje doménová rozhraní.
- **Funkční testy – aplikační vrstva / API (špička pyramidy, nejméně testů)**
  Testují celé use cases přes HTTP vrstvu nebo přímo přes aplikační služby.
  Simulují uživatele aplikace. Jsou nejpomalejší a nejkřehčí.
  Cíl: ověřit integraci všech vrstev v hlavních scénářích.
:::

### Poměr vrstev není konstanta

Pyramida říká, že pomalých testů má být méně než rychlých. V jakém poměru, neříká. Fowler k ní proto
staví protipól **ice-cream cone**: sadu, ve které převažují pomalé testy přes UI. Rozpadá se ze tří
důvodů – build trvá dlouho, drobná změna systému rozbije mnoho testů naráz a headless běh v pipeline
je problematický [[2]](https://martinfowler.com/bliki/TestPyramid.html). Ham Vocke jde ještě dál
a Cohnovo pojmenování vrstev označuje za zjednodušující; místo poměru se ptá, kolik integračních
bodů jeden test skutečně ověřuje [[3]](https://martinfowler.com/articles/practical-test-pyramid.html).

Pro DDD projekt z toho plyne vodítko: těžiště sady kopíruje tloušťku doménové logiky. Bounded context
s bohatým modelem unese širokou základnu unit testů. Kontext, který jen překládá HTTP požadavky
na dotazy do databáze, takovou základnu nemá a jeho záruku nesou integrační testy. Kent C. Dodds na téže
úvaze staví alternativu **Testing Trophy** s největší investicí do integrační vrstvy
[[4]](https://kentcdodds.com/blog/the-testing-trophy-and-testing-classifications). Formulovaná je
pro JavaScript, ale otázku klade dobře: kolik logiky vlastně testujete v izolaci?

:::callout{type="note"}
### Testovací strategie – co testovat na každé vrstvě:

- **Doménová vrstva:** Validační logika value objects, invarianty entit, transakční konzistence agregátů, vydávání doménových událostí, doménové výjimky.
- **Aplikační vrstva:** Command handlery a query handlery – s použitím fake (InMemory) repozitářů, ověření, že handler volá správné metody repozitáře s očekávanými argumenty.
- **Infrastrukturní vrstva:** Správné Doctrine mapování, dotazy repozitářů, transakce, volání externích API.
- **Prezentační vrstva:** Správné HTTP status kódy, formát odpovědi, autentizace a autorizace.
:::

## 17.02 Unit testy doménové vrstvy {#unit-testy-domeny}

Unit testy doménové vrstvy tvoří základ testovací sady DDD aplikace. Pokrývají největší podíl kódu, běží
v řádu milisekund a nepotřebují nic jiného než PHPUnit a samotné doménové třídy. Žádný bootstrap Symfony
kernelu, žádná databáze, žádné fixtures.

Ukázky v této kapitole cílí na PHPUnit 13 a PHP 8.4. Na verzi tentokrát záleží víc než obvykle. PHPUnit 12
odstranil podporu metadat v doc-komentářích, takže `@dataProvider`, `@covers`, `@test` ani `@group` už
nejsou pokyny pro framework – zůstávají obyčejným komentářem. Metadata se zapisují atributy z namespace
`PHPUnit\Framework\Attributes`: `#[DataProvider]`, `#[TestWith]`, `#[CoversClass]`, `#[Group]`, `#[Test]`
[[5]](https://docs.phpunit.de/en/12.4/attributes.html). Test přenesený z návodu psaného pro PHPUnit 9
skončí chybou o chybějícím argumentu metody, ne tichou změnou chování.

### Testování Value Objects

Test value objektu ověřuje tři věci: že neplatný vstup vyhodí odpovídající výjimku, že dvě instance
se stejnou hodnotou jsou si rovny přes `equals()`, a že objekt zůstává neměnný – jiná hodnota
znamená novou instanci. Tím je hodnotový objekt pokrytý.

:::callout{type="pattern"}
### Příklad: Test pro Email value object (PHPUnit)

:::code{language="php" filename="Tests/UserManagement/Domain/ValueObject/EmailTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use App\UserManagement\Domain\ValueObject\Email;

final class EmailTest extends TestCase
{
    public function testCreatesValidEmail(): void
    {
        $email = new Email('jan.novak@example.com');

        $this->assertSame('jan.novak@example.com', $email->value);
    }

    public function testNormalizesToLowercase(): void
    {
        // Normalizaci dělá pojmenovaná factory, ne konstruktor
        $email = Email::fromUserInput('Jan.Novak@EXAMPLE.COM');

        $this->assertSame('jan.novak@example.com', $email->value);
    }

    #[DataProvider('invalidInputs')]
    public function testThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email($input);
    }

    /**
     * Data provider musí být od PHPUnit 11 statický a veřejný.
     *
     * @return iterable<string, array{string}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'bez zavináče'    => ['not-an-email'];
        yield 'prázdný řetězec' => [''];
        yield 'chybí doména'    => ['jan@'];
        yield 'mezera uvnitř'   => ['jan novak@example.com'];
    }

    public function testEqualityBySameValue(): void
    {
        $email1 = new Email('jan@example.com');
        $email2 = new Email('jan@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testInequalityForDifferentValues(): void
    {
        $email1 = new Email('jan@example.com');
        $email2 = new Email('petr@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    public function testImmutabilityViaNewInstance(): void
    {
        $original = new Email('jan@example.com');
        // Hodnotové objekty jsou immutabilní - změna vyžaduje vytvoření nové instance
        $different = new Email('petr@example.com');

        $this->assertSame('jan@example.com', $original->value);
        $this->assertSame('petr@example.com', $different->value);
        $this->assertFalse($original->equals($different));
    }
}
:::
:::

Pojmenované klíče v data provideru se objeví ve výstupu PHPUnitu, takže spadlý případ je vidět
bez čtení testu: `EmailTest::testThrowsExceptionForInvalidInput with data set „chybí doména“`.
Pro jeden nebo dva vstupy se vyplatí atribut `#[TestWith([''])]` přímo nad metodou; samostatný
provider dává smysl od tří případů výš.

### Testování entit

Test entity ověřuje, co entita dělá, ne jak vypadají její fieldy. Volá se veřejná metoda, ověřuje se
výsledný stav přes další veřejné metody a u zakázaných operací se očekává konkrétní doménová výjimka.
Přístup k privátním vlastnostem přes reflexi je signál, že test sleduje implementaci místo chování.

:::callout{type="pattern"}
### Příklad: Test pro User entitu

:::code{language="php" filename="Tests/UserManagement/Domain/Model/UserTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Domain\Model;

use PHPUnit\Framework\TestCase;
use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\Exception\UserAlreadyActiveException;

final class UserTest extends TestCase
{
    private UserId $userId;
    private Email $email;

    protected function setUp(): void
    {
        $this->userId = UserId::generate();
        $this->email  = new Email('jan@example.com');
    }

    public function testCreatesInactiveUserByDefault(): void
    {
        $user = User::register($this->userId, 'Jan Novák', $this->email, HashedPassword::fromPlainText('secret123'));

        $this->assertFalse($user->isActive());
    }

    public function testActivatesUser(): void
    {
        $user = User::register($this->userId, 'Jan Novák', $this->email, HashedPassword::fromPlainText('secret123'));
        $user->activate();

        $this->assertTrue($user->isActive());
    }

    public function testThrowsExceptionWhenActivatingAlreadyActiveUser(): void
    {
        $user = User::register($this->userId, 'Jan Novák', $this->email, HashedPassword::fromPlainText('secret123'));
        $user->activate();

        $this->expectException(UserAlreadyActiveException::class);

        $user->activate();
    }

    public function testChangesEmailAddress(): void
    {
        $user     = User::register($this->userId, 'Jan Novák', $this->email, HashedPassword::fromPlainText('secret123'));
        $newEmail = new Email('novy@example.com');

        $user->changeEmail($newEmail);

        $this->assertTrue($newEmail->equals($user->email()));
    }

    public function testEmailRemainsUnchangedWhenSameValueProvided(): void
    {
        $user = User::register($this->userId, 'Jan Novák', $this->email, HashedPassword::fromPlainText('secret123'));
        $user->releaseEvents(); // vyprázdní buffer - registrace vydala UserRegistered

        $user->changeEmail(new Email('jan@example.com'));

        // Žádná událost by neměla být vydána, email je stále stejný
        $this->assertCount(0, $user->releaseEvents());
    }
}
:::
:::

:::callout{type="note"}
**Pozn.:** V tomto zjednodušeném příkladu metoda `activate()` nepřijímá token.
Plnou implementaci s `VerificationToken` naleznete v kapitole
[Anti-vzory](/anti-vzory).
:::

### Testování agregátů

Agregát chrání konzistenci skupiny entit a vydává doménové události. Test agregátu má proto dvě role:
ověřit transakční invarianty (pravidla platná pro celý agregát po každé operaci) a zkontrolovat, že
operace vydala očekávané události ve správném pořadí.

:::callout{type="pattern"}
### Příklad: Test pro Order agregát

:::code{language="php" filename="Tests/Ordering/Domain/Model/OrderTest.php"}
<?php

declare(strict_types=1);

namespace Tests\Ordering\Domain\Model;

use PHPUnit\Framework\TestCase;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\ProductId;
use App\SharedKernel\Domain\Money;
use App\SharedKernel\Domain\Currency;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Event\OrderItemAdded;
use App\Ordering\Domain\Exception\EmptyOrderException;
use App\Ordering\Domain\Exception\InvalidOrderStateTransitionException;

final class OrderTest extends TestCase
{
    public function testAddsItemToOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $order->addItem(ProductId::generate(), 2, new Money(49900, Currency::CZK));

        $this->assertSame(1, $order->itemCount());          // 1 řádek objednávky
        $this->assertEquals(new Money(99800, Currency::CZK), $order->totalAmount()); // 49 900 × 2
    }

    public function testThrowsExceptionWhenConfirmingEmptyOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(EmptyOrderException::class);

        $order->confirm();
    }

    public function testConfirmsOrderSuccessfully(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));

        $order->confirm();

        $this->assertTrue($order->isConfirmed());
    }

    public function testThrowsExceptionWhenConfirmingAlreadyConfirmedOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));
        $order->confirm();

        $this->expectException(InvalidOrderStateTransitionException::class);

        $order->confirm();
    }

    public function testReleasesRecordedEventsInOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));
        $order->confirm();

        $events = $order->releaseEvents();

        $this->assertCount(3, $events); // OrderPlaced + OrderItemAdded + OrderConfirmed
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
        $this->assertInstanceOf(OrderItemAdded::class, $events[1]);
        $this->assertInstanceOf(OrderConfirmed::class, $events[2]);
    }
}
:::
:::

## 17.03 Testování doménových událostí {#testovani-domain-events}

Doménové události jsou způsob, jak agregát mluví se zbytkem systému. Test proto ověřuje přímo to, co
agregát po operaci vydá – typ události, její data a pořadí během jedné transakce. Spoléhat se na
vedlejší efekt event dispatcheru je křehké a do unit testu přibírá zbytečnou závislost. Pokud váš systém
používá události jako zdroj pravdy, doplňující strategie testování auditovatelnosti a rebuildu projekcí
najdete v kapitole [Event Sourcing](/event-sourcing).

:::callout{type="note"}
### Pattern „Record and Verify Events“:

Agregáty sbírají vydané události interně v privátním poli (viz bázová třída `AggregateRoot` nebo trait).
Metoda `releaseEvents()` vrátí všechny nashromážděné události a pole vymaže. Tento přístup nevyžaduje
v unit testech žádný event dispatcher ani bus. Testovací kód zavolá doménovou operaci a ověří
obsah vrácených událostí.
:::

:::callout{type="pattern"}
### Příklad: Trait pro testování doménových událostí

:::code{language="php" filename="Tests/Shared/Domain/DomainEventAssertions.php"}
<?php

declare(strict_types=1);

namespace Tests\Shared\Domain;

use App\SharedKernel\Domain\Event\DomainEvent;

/**
 * Reusable trait pro ověřování doménových událostí v unit testech.
 * Použití: `use DomainEventAssertions;` ve třídě TestCase.
 */
trait DomainEventAssertions
{
    /**
     * Ověří, že kolekce událostí obsahuje právě jednu událost daného typu.
     *
     * @param array<DomainEvent> $events
     */
    protected function assertSingleEventOfType(string $expectedType, array $events): DomainEvent
    {
        $matching = array_filter($events, fn(DomainEvent $e) => $e instanceof $expectedType);

        $this->assertCount(
            1,
            $matching,
            sprintf('Očekávána právě jedna událost typu %s, nalezeno %d.', $expectedType, count($matching))
        );

        return array_values($matching)[0];
    }

    /**
     * Ověří, že kolekce událostí neobsahuje žádnou událost daného typu.
     *
     * @param array<DomainEvent> $events
     */
    protected function assertNoEventOfType(string $unexpectedType, array $events): void
    {
        $matching = array_filter($events, fn(DomainEvent $e) => $e instanceof $unexpectedType);

        $this->assertCount(
            0,
            $matching,
            sprintf('Neočekávána žádná událost typu %s, ale nalezena.', $unexpectedType)
        );
    }

    /**
     * Ověří přesné pořadí vydaných událostí.
     *
     * @param array<class-string>  $expectedTypes
     * @param array<DomainEvent> $events
     */
    protected function assertEventSequence(array $expectedTypes, array $events): void
    {
        $actualTypes = array_map(fn(DomainEvent $e) => $e::class, $events);

        $this->assertSame(
            $expectedTypes,
            $actualTypes,
            'Pořadí doménových událostí neodpovídá očekávání.'
        );
    }
}

// --- Příklad použití traitu v testu ---

namespace Tests\Ordering\Domain\Model;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\ProductId;
use App\SharedKernel\Domain\Money;
use App\SharedKernel\Domain\Currency;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderConfirmed;
use Tests\Shared\Domain\DomainEventAssertions;

final class OrderEventsTest extends \PHPUnit\Framework\TestCase
{
    use DomainEventAssertions;

    public function testOrderPlacedEventContainsCorrectData(): void
    {
        $orderId    = OrderId::generate();
        $customerId = CustomerId::generate();
        $order      = Order::place($orderId, $customerId);
        $order->addItem(ProductId::generate(), 3, new Money(25000, Currency::CZK));

        $events       = $order->releaseEvents();
        $createdEvent = $this->assertSingleEventOfType(OrderPlaced::class, $events);

        // Ověření dat události
        $this->assertTrue($orderId->equals($createdEvent->orderId));
        $this->assertTrue($customerId->equals($createdEvent->customerId));
        $this->assertNotNull($createdEvent->occurredAt);
    }

    public function testNoOrderConfirmedEventWhenOrderNotConfirmed(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));

        $events = $order->releaseEvents();

        $this->assertNoEventOfType(OrderConfirmed::class, $events);
    }
}
:::
:::

### Given-when-then nad event streamem

U event-sourced agregátu je stav odvozený z historie, takže se test píše jinak. Vstupem není
konstruktor, ale stream událostí: **given** je historie, **when** volání doménové metody a **then**
události nahrané právě touto operací. Bázovou třídu `EventSourcedAggregate` s metodami `recordEvent()`
a `reconstituteFromEvents()` definuje kapitola
[Event Sourcing](/event-sourcing#es-aggregate-base-heading).

Rekonstrukce z historie stav aplikuje, ale nic nenahrává – `releaseEvents()` po ní vrátí prázdné pole.
Assertion se tedy vztahuje výhradně k tomu, co přidala testovaná operace.

:::callout{type="pattern"}
### Příklad: Given-when-then test event-sourced agregátu

:::code{language="php" filename="Tests/Ordering/Domain/OrderEventSourcingTest.php"}
<?php

declare(strict_types=1);

namespace Tests\Ordering\Domain;

use PHPUnit\Framework\TestCase;
use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderItem;
use App\Ordering\Domain\Event\OrderConfirmed;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderItemAdded;
use App\Ordering\Domain\Exception\EmptyOrderException;
use App\SharedKernel\Domain\Event\DomainEvent;

final class OrderEventSourcingTest extends TestCase
{
    public function testConfirmingOrderWithItemRecordsOrderConfirmed(): void
    {
        // given
        $order = $this->given(
            OrderPlaced::create('order-1', 'customer-1'),
            OrderItemAdded::create('order-1', new OrderItem(
                productId: 'product-1',
                quantity: 2,
                unitPriceInCents: 49900,
            )),
        );

        // when
        $order->confirm();

        // then
        $this->assertRecorded([OrderConfirmed::class], $order);
        $this->assertSame(3, $order->version()); // dvě historické události + jedna nová
    }

    public function testConfirmingEmptyOrderRecordsNothing(): void
    {
        $order = $this->given(OrderPlaced::create('order-1', 'customer-1'));

        try {
            $order->confirm();
            $this->fail('Očekávána EmptyOrderException.');
        } catch (EmptyOrderException) {
            // očekáváno
        }

        $this->assertRecorded([], $order);
    }

    /** given: historie streamu, ze které se agregát rekonstruuje. */
    private function given(DomainEvent ...$history): Order
    {
        return Order::reconstituteFromEvents($history);
    }

    /**
     * then: typy událostí nahraných až testovanou operací.
     *
     * @param array<class-string> $expected
     */
    private function assertRecorded(array $expected, Order $order): void
    {
        $actual = array_map(fn (DomainEvent $event) => $event::class, $order->releaseEvents());

        $this->assertSame($expected, $actual);
    }
}
:::
:::

Druhý test hlídá pravidlo, které stavově ukládaný agregát nemá jak porušit: neúspěšná operace nesmí
nechat v bufferu událost. Kdyby `confirm()` nahrál `OrderConfirmed` ještě před kontrolou invariantu,
repozitář by událost při nejbližším uložení zapsal do streamu a historie by tvrdila něco, co se nestalo.

## 17.04 Test doubles a InMemory repozitáře {#test-doubles}

Test double je obecný název pro náhradu reálné závislosti v testu. Taxonomii pěti typů – dummy, stub,
spy, mock a fake – zavedl Gerard Meszaros v knize *xUnit Test Patterns: Refactoring Test Code*
(Addison-Wesley, 2007) [[6]](http://xunitpatterns.com/); do širšího povědomí ji dostal Fowler článkem
*Mocks Aren't Stubs* [[7]](https://martinfowler.com/articles/mocksArentStubs.html). V praxi s PHPUnit se pracuje
hlavně se čtyřmi z nich (stub, mock, fake, spy) a v DDD má každý jiný dopad: vede k jinému stylu testu
a k jiné odolnosti vůči refaktoringu.

:::callout{type="note"}
### Typy test doubles a jejich použití v DDD:

- **Stub** – Vrací předpřipravené odpovědi bez logiky. Vhodný, když potřebujeme, aby závislost vrátila konkrétní hodnotu, ale nezajímá nás, zda a kolikrát byla volána. Příklad: `$stub->method('findById')->willReturn($user)`.
- **Mock** – Stub s ověřením volání. Ověřuje, že byla zavolána konkrétní metoda s konkrétními argumenty přesně n-krát. Vhodný pro ověření vedlejších efektů (volání repozitáře, odeslání e-mailu). Příklad: `$mock->expects($this->once())->method('save')`.
- **Fake** – Plnohodnotná, ale zjednodušená implementace rozhraní (typicky in-memory). Nemá databázovou závislost, ale chová se jako skutečná implementace. **Doporučený přístup pro DDD repozitáře** – umožňuje psát čitelné testy bez konfigurování mocků.
- Méně častý je **spy** – podobný mocku, ale ověření probíhá až po akci (post-assertion style).
:::

:::callout{type="note"}
### Proč preferovat Fake (InMemory) před Mockem pro repozitáře:

- Testy jsou čitelnější – nepotřebují konfigurace `expects()->method()->with()->willReturn()`.
- InMemory repozitář lze sdílet mezi command handlerem a query handlerem v jednom testu – ověříme reálný průchod dat.
- Při změně signatury rozhraní IDE a statická analýza okamžitě upozorní, na rozdíl od string-based konfigurace mocků.
- Mocky testují implementační detail (které metody jsou volány), Fake testuje chování (co se stane s daty).
:::

Za tímto doporučením stojí Fowlerovo rozlišení dvou způsobů ověření. *State verification* kontroluje
stav systému po akci, *behavior verification* kontroluje, že proběhla očekávaná volání spolupracovníků.
Z pěti typů doubles trvá na behavior verification jedině mock – ostatní se s kontrolou stavu spokojí.
Volba fake repozitáře je přihlášením ke stylu, kterému Fowler říká
classical TDD, tedy reálné objekty všude, kde to jde, a doubles jen na hranicích systému.

:::callout{type="pattern"}
### Příklad: InMemoryUserRepository implementace

:::code{language="php" filename="Tests/UserManagement/Infrastructure/Repository/InMemoryUserRepository.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Infrastructure\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\Repository\UserRepository;

/**
 * InMemory implementace UserRepository pro unit a integrační testy.
 * Simuluje chování Doctrine repozitáře bez potřeby databáze.
 */
final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $storage = [];

    public function save(User $user): void
    {
        // Aproximace unikátního indexu na sloupci `email`. V produkci ho
        // vymáhá databáze a handler překládá UniqueConstraintViolationException;
        // fake ho vymáhá sám, aby test nepotřeboval běžící DB.
        $existing = $this->findByEmail($user->email());
        if ($existing !== null && !$existing->id()->equals($user->id())) {
            throw DuplicateEmailException::forEmail($user->email());
        }

        $this->storage[(string) $user->id()] = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->storage[(string) $id] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->storage as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function remove(User $user): void
    {
        unset($this->storage[(string) $user->id()]);
    }

    /** Pomocná metoda pro assertiony v testech. */
    public function count(): int
    {
        return count($this->storage);
    }

    /** @return array<User> */
    public function all(): array
    {
        return array_values($this->storage);
    }
}
:::
:::

:::callout{type="pattern"}
### Příklad: Test command handleru s InMemoryRepository

:::code{language="php" filename="Tests/UserManagement/Application/Command/RegisterUserHandlerTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Application\Command;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Command\RegisterUserHandler;
use App\UserManagement\Domain\Exception\DuplicateEmailException;
use App\UserManagement\Domain\ValueObject\Email;
use Tests\UserManagement\Infrastructure\Repository\InMemoryUserRepository;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        // Handler volá flush() kvůli překladu unique violation. Fake repozitář
        // ho neřeší, takže EntityManager stačí jako stub, který nedělá nic.
        $this->handler        = new RegisterUserHandler(
            $this->userRepository,
            $this->createStub(EntityManagerInterface::class),
        );
    }

    public function testRegistersNewUser(): void
    {
        $command = new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'SilneHeslo123!'
        );

        ($this->handler)($command);

        $this->assertSame(1, $this->userRepository->count());

        $user = $this->userRepository->findByEmail(new Email('jan@example.com'));
        $this->assertNotNull($user);
        $this->assertFalse($user->isActive()); // nový uživatel je neaktivní
    }

    public function testThrowsExceptionWhenEmailAlreadyTaken(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'jan@example.com', password: 'Heslo123!');
        ($this->handler)($command); // první registrace

        $this->expectException(DuplicateEmailException::class);

        ($this->handler)($command); // duplicitní registrace
    }

    public function testDoesNotPersistUserWhenEmailAlreadyTaken(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'jan@example.com', password: 'Heslo123!');
        ($this->handler)($command);

        try {
            ($this->handler)($command);
        } catch (DuplicateEmailException) {
            // očekáváno
        }

        $this->assertSame(1, $this->userRepository->count());
    }
}
:::
:::

InMemory repozitář zde unikátnost e-mailu jen aproximuje: kontroluje ji v paměti a rovnou
hází `DuplicateEmailException`. Finální záruku dává unikátní constraint v databázi –
kanonický handler překládá jeho porušení na tutéž výjimku
(viz [race condition v naivní variantě](/implementace-v-symfony#register-race-heading)).
Test ověřuje chování handleru, ne řešení souběhu, proto `EntityManager` stačí jako stub.

:::callout{type="warn"}
### Varování: Přílišné používání mocků

Nadměrné použití mocků (mockování každé závislosti) vede k tzv. *nadměrné specifikaci* testů.
Takové testy ověřují implementační detaily, nikoli chování. Při každém refaktoringu přestanou procházet,
i když se chování systému nezměnilo. Pro repozitáře se osvědčily InMemory Fake implementace; mocky mají
místo jen tam, kde se ověřují vedlejší efekty (odeslání e-mailu, volání externího API).
:::

### Testovací data: builder místo opakovaného konstruktoru

Testy v předchozích ukázkách opakují v každé metodě totéž volání
`User::register($id, 'Jan Novák', $email, HashedPassword::fromPlainText('secret123'))`. Pro test
je z něj podstatný jeden argument, zbytek je tam proto, že ho vyžaduje konstruktor. Až přibude pátý
parametr, mění se všechny testy naráz.

Odpovědí je **Test Data Builder** od Steva Freemana a Nata Pryce z knihy *Growing Object-Oriented
Software, Guided by Tests* (Addison-Wesley, 2009)
[[8]](http://www.growing-object-oriented-software.com/). Builder má pole pro každý parametr
konstruktoru, inicializované bezpečnou hodnotou, řetězitelné metody pro přepsání těchto polí
a metodu `build()`, která z nich složí cílový objekt. Volitelně přidá statickou tovární metodu,
aby bylo v testu na první pohled zřejmé, co se staví – autoři ji ukazují právě na `OrderBuilder`
s metodou `anOrder()`. Přínos shrnují do tří bodů: builder obalí syntaktický šum kolem vytváření
objektů, udrží výchozí případ jednoduchý a zvláštní případ jen o málo složitější, a odstíní testy
od změny struktury objektu – po přidání parametru se mění jediné místo.

:::callout{type="pattern"}
### Příklad: Test Data Builder pro Order agregát

:::code{language="php" filename="Tests/Ordering/Domain/Builder/OrderBuilder.php"}
<?php

declare(strict_types=1);

namespace Tests\Ordering\Domain\Builder;

use App\Ordering\Domain\Model\Order;
use App\SharedKernel\Domain\Currency;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\SharedKernel\Domain\Money;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\ProductId;

final class OrderBuilder
{
    private OrderId $orderId;
    private CustomerId $customerId;

    /** @var list<array{ProductId, int, Money}> */
    private array $items = [];

    private bool $confirmed = false;

    private function __construct()
    {
        // Bezpečné výchozí hodnoty - test nastavuje jen to, na čem mu skutečně záleží
        $this->orderId    = OrderId::generate();
        $this->customerId = CustomerId::generate();
    }

    public static function anOrder(): self
    {
        return new self();
    }

    public function forCustomer(CustomerId $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function withItem(int $quantity = 1, ?Money $unitPrice = null): self
    {
        $this->items[] = [
            ProductId::generate(),
            $quantity,
            $unitPrice ?? new Money(49900, Currency::CZK),
        ];

        return $this;
    }

    public function confirmed(): self
    {
        $this->confirmed = true;

        return $this;
    }

    public function build(): Order
    {
        $order = Order::place($this->orderId, $this->customerId);

        // Objednávka bez položek nejde potvrdit, výchozí položka je proto bezpečná hodnota
        $items = $this->items !== []
            ? $this->items
            : [[ProductId::generate(), 1, new Money(49900, Currency::CZK)]];

        foreach ($items as [$productId, $quantity, $unitPrice]) {
            $order->addItem($productId, $quantity, $unitPrice);
        }

        if ($this->confirmed) {
            $order->confirm();
        }

        return $order;
    }
}

// --- Použití v testu ---

$order = OrderBuilder::anOrder()
    ->forCustomer($customerId)
    ->withItem(quantity: 3, unitPrice: new Money(25000, Currency::CZK))
    ->confirmed()
    ->build();

$order->releaseEvents(); // stavba agregátu vydala události, test si buffer vyprázdní
:::
:::

Builder je mutabilní, takže každý test si volá `anOrder()` znovu; sdílená instance mezi testy je
zdrojem těžko dohledatelných závislostí na pořadí.

Starší příbuzný vzor je **Object Mother** (Peter Schuh, Stephanie Punke, *ObjectMother: Easing Test
Object Creation In XP*, XP Universe, 2001): pojmenovaná tovární metoda vrátí hotovou fixturu,
například `OrderMother::confirmedOrder()`. Čte se dobře, dokud variant nepřibývá. Fowler mu vyčítá
dvě věci [[9]](https://martinfowler.com/bliki/ObjectMother.html): mnoho testů se váže na přesná data
jedné fixtury a po změně tříd je potřeba migrovat všechny mothers najednou. Builder tento tlak
rozpouští tím, že variantu skládá volající.

Fixtury, které mají skončit v databázi, řeší v Symfony `zenstruck/foundry`
[[10]](https://github.com/zenstruck/foundry). Zápis `OrderFactory::new()->confirmed()->create()`
je týž vzor obalený integrací na Doctrine. Doménové unit testy persistenci nepotřebují a vystačí
si s builderem; factory patří do integračních a funkčních testů z následujících dvou sekcí.

## 17.05 Integrační testy s Doctrine {#integracni-testy}

Integrační testy odpovídají na otázku, kterou unit testy pokrýt nemohou: zda Doctrine mapování, dotazy
repozitářů a transakce skutečně dělají to, co jejich rozhraní slibuje. Výchozí volbou je stejný
databázový engine jako v produkci, spuštěný v kontejneru. SQLite in-memory je rychlejší, ale liší se
typovým systémem, chováním unikátních constraintů i transakční sémantikou – tedy přesně v těch věcech,
které má integrační test ověřit. Zůstává vědomou zkratkou pro rychlou zpětnou vazbu při lokálním vývoji,
ne konfigurací, na které stojí CI.

Obě implementace přitom plní tutéž smlouvu rozhraní: `findById(UserId $id): ?User` vrací při
nenalezení `null`, stejně jako InMemory varianta ze [sekce o test doubles](#test-doubles).
Druhou běžnou konvencí je metoda `getById()`, která místo `null` vyhazuje `UserNotFoundException` –
projekt si vybere jednu variantu a drží ji ve všech implementacích i testech.

:::callout{type="note"}
### KernelTestCase vs WebTestCase:

- **KernelTestCase** – Bootstrapuje Symfony kernel bez HTTP vrstvy. Vhodný pro testování
  Doctrine repozitářů, služeb z DI kontejneru a dalších komponent infrastruktury. Rychlejší než WebTestCase.
- **WebTestCase** – Bootstrapuje kernel i simulovaného HTTP klienta. Vhodný pro funkční testy
  kontrolerů a API endpointů. Pomalejší, ale testuje celý zásobník.
:::

:::callout{type="note"}
### Transakce a rollback po každém testu:

Nejpřímočařejší způsob, jak zajistit izolaci integračních testů, je zabalit každý test do databázové transakce
a po jeho dokončení provést rollback. Toto chování dodá bundle
`dama/doctrine-test-bundle` [[11]](https://github.com/dmaicher/doctrine-test-bundle): zaregistruje
PHPUnit extension a obalí každý test transakcí pomocí dekorátoru nad `Connection`, bez zásahu
do testovacího kódu. Bez transakční izolace by každý test zanechával data v databázi a testy by se
navzájem ovlivňovaly.
:::

Bundle se nezapíná v konfiguraci Symfony, ale v konfiguraci PHPUnitu – jako bootstrap extension:

:::callout{type="pattern"}
### Příklad: Registrace DAMA extension v phpunit.dist.xml

:::code{language="xml" filename="phpunit.dist.xml"}
<extensions>
    <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
</extensions>
:::
:::

:::callout{type="warn"}
### Kde transakční izolace přestává fungovat

Bundle drží jedno statické DBAL spojení po celý běh procesu a po každém testu provede rollback.
Z toho plyne jeho jediné vážné omezení: **DDL dotazy** (`ALTER TABLE`, `DROP TABLE`) transakci
implicitně commitnou, takže následný rollback selže hláškou o neexistujícím savepointu. Testy,
které mění schéma, patří mimo tuto sadu – schéma se připraví před během testů, ne v nich.

Volbu `use_savepoints` bundle vyžaduje jen na DBAL nižším než 4. Kniha cílí na Doctrine ORM 3
s DBAL 4, kde se nenastavuje.
:::

:::callout{type="pattern"}
### Příklad: Integrační test DoctrineUserRepository

:::code{language="php" filename="Tests/UserManagement/Infrastructure/Repository/DoctrineUserRepositoryTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Infrastructure\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Infrastructure\Repository\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Integrační test pro DoctrineUserRepository.
 * Vyžaduje běžící databázi (konfigurovanou přes DATABASE_URL v .env.test).
 * Transakční rollback zajišťuje dama/doctrine-test-bundle.
 */
final class DoctrineUserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository    = self::getContainer()->get(DoctrineUserRepository::class);
    }

    public function testPersistsAndRetrievesUser(): void
    {
        $userId = UserId::generate();
        $email  = new Email('integrace@example.com');
        $user   = User::register($userId, 'Test Uživatel', $email, HashedPassword::fromPlainText('Heslo123!'));

        $this->repository->save($user);
        $this->entityManager->clear(); // vyčistíme identity map - nutné pro skutečné čtení z DB

        $retrieved = $this->repository->findById($userId);

        $this->assertNotNull($retrieved);
        $this->assertTrue($userId->equals($retrieved->id()));
        $this->assertTrue($email->equals($retrieved->email()));
    }

    public function testReturnsNullForNonExistentUser(): void
    {
        $this->assertNull($this->repository->findById(UserId::generate()));
    }

    public function testFindsByEmailAddress(): void
    {
        $email = new Email('hledat@example.com');
        $user  = User::register(UserId::generate(), 'Test Uživatel', $email, HashedPassword::fromPlainText('Heslo123!'));

        $this->repository->save($user);
        $this->entityManager->clear();

        $found = $this->repository->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertTrue($email->equals($found->email()));
    }

    public function testExistsByEmail(): void
    {
        $email = new Email('exists@example.com');
        $user  = User::register(UserId::generate(), 'Test Uživatel', $email, HashedPassword::fromPlainText('Heslo123!'));

        $this->assertFalse($this->repository->existsByEmail($email));

        $this->repository->save($user);

        $this->assertTrue($this->repository->existsByEmail($email));
    }
}
:::
:::

:::callout{type="warn"}
### Proč volat `$entityManager->clear()`?

Doctrine udržuje tzv. *Identity Map* – interní cache, která vrátí stejnou instanci objektu
pro stejné ID bez dalšího dotazu do databáze. Bez volání `clear()` by integrační test
mohl projít, i kdyby data v databázi vůbec nebyla uložena – Doctrine by je vrátil
z paměti. Voláme tedy `clear()` mezi zápisem a čtením, aby byl test skutečně integrační.
:::

## 17.06 Funkční testy API a kontrolerů {#funkcni-testy}

Funkční test prochází celý zásobník: request přijde do kontroleru, projde aplikační vrstvou, dotkne se
databáze a vrátí odpověď. Ověřuje se HTTP status kód, tělo (typicky JSON), hlavičky a chování při
chybových vstupech. V DDD je to jediná vrstva testů, která ověří, že prezentace s aplikační vrstvou
spolu skutečně mluví správně.

:::callout{type="note"}
### WebTestCase v Symfony:

`Symfony\Bundle\FrameworkBundle\Test\WebTestCase` poskytuje metodu `createClient()`,
která vrátí HTTP klienta simulujícího prohlížeč. Klient odesílá requesty GET, POST, PUT, PATCH a DELETE.
Response obsahuje status kód, tělo a hlavičky – vše přímo assertovatelné.

Vedle obecného `assertResponseStatusCodeSame()` nabízí framework desítky pojmenovaných assertů
(`assertResponseIsSuccessful()`, `assertResponseIsUnprocessable()`, `assertResponseRedirects()`,
asserty nad odeslanými e-maily a zprávami Messengeru). Jejich úplný seznam je v dokumentaci
Symfony [[12]](https://symfony.com/doc/current/testing.html). Pojmenovaný assert navíc vypíše
při selhání celou odpověď, takže se ladí bez dodatečného `dump()`.
:::

:::callout{type="pattern"}
### Příklad: Funkční test registračního endpointu

:::code{language="php" filename="Tests/UserManagement/Registration/Controller/RegistrationControllerTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Registration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Funkční testy registračního REST API endpointu.
 * Testují HTTP vrstvu + celý zásobník až po databázi.
 */
final class RegistrationControllerTest extends WebTestCase
{
    public function testRegistersUserSuccessfully(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/users/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name'     => 'Jan Novák',
                'email'    => 'novy@example.com',
                'password' => 'SilneHeslo123!',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('userId', $responseData);
        $this->assertSame('novy@example.com', $responseData['email']);
    }

    public function testReturns422ForInvalidEmail(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/users/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name'     => 'Jan Novák',
                'email'    => 'not-valid-email',
                'password' => 'Heslo123!',
            ])
        );

        $this->assertResponseIsUnprocessable();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $responseData);
        $this->assertStringContainsString('email', strtolower($responseData['errors'][0]['field']));
    }

    public function testReturns409WhenEmailAlreadyRegistered(): void
    {
        $client = static::createClient();

        $payload = json_encode(['name' => 'Jan Novák', 'email' => 'existujici@example.com', 'password' => 'Heslo123!']);

        $client->request('POST', '/api/users/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $payload
        );
        $this->assertResponseStatusCodeSame(201);

        // druhý pokus se stejným emailem
        $client->request('POST', '/api/users/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $payload
        );
        $this->assertResponseStatusCodeSame(409);
    }

    public function testReturns400ForMissingRequiredFields(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/users/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);
    }
}
:::
:::

:::callout{type="warn"}
### Rozsah funkčních testů

Funkční testy jsou nejpomalejší a nejkřehčí. Pokrývají jen hlavní scénář a nejdůležitější chybové cesty.
Vše ostatní (okrajové případy, validace, doménová pravidla) patří do unit testů doménové vrstvy.
Příliš mnoho funkčních testů prodlužuje dobu CI/CD pipeline a snižuje motivaci vývojářů spouštět testy lokálně.
:::

## 17.07 Testování asynchronních toků {#testovani-asynchronnich-toku}

Asynchronní zpracování přes Messenger rozděluje tok na dvě poloviny: odeslání zprávy a její zpracování
workerem. Test musí pokrýt obě, každou zvlášť. Architekturu busů popisuje kapitola
[CQRS](/cqrs#symfony-messenger), transakční předávání zpráv kapitola [Outbox Pattern](/outbox-pattern).

### In-memory transport

V testovacím prostředí nahradí reálný broker transport `in-memory://`. Zprávy nikam neodcházejí,
zůstávají v paměti procesu a test si je vyzvedne přímo z kontejneru. Stačí přepsat DSN
pro prostředí `test`:

:::callout{type="pattern"}
### Příklad: In-memory transport pro testy

:::code{language="yaml" filename="config/packages/test/messenger.yaml"}
framework:
    messenger:
        transports:
            async: 'in-memory://'
:::
:::

Transport vystavuje `getSent()`, `getAcknowledged()` a `reset()`. O úklid se přitom starat nemusíte:
v testech dědících z `KernelTestCase` nebo `WebTestCase` se všechny in-memory transporty po každém
testu resetují samy [[13]](https://symfony.com/doc/current/messenger.html). Volba `serialize: true`
navíc zprávy protáhne serializační vrstvou, takže se otestuje i to, co se v produkci posílá po drátě.

Funkční test pak ověří, že endpoint zprávu skutečně odeslal – aniž by čekal na workera:

:::callout{type="pattern"}
### Příklad: Assertions nad odeslanými zprávami

:::code{language="php" filename="Tests/UserManagement/Functional/RegisterUserDispatchTest.php"}
public function testRegistrationDispatchesWelcomeEmail(): void
{
    $client = static::createClient();

    $client->request(
        method: 'POST',
        uri: '/api/users/register',
        server: ['CONTENT_TYPE' => 'application/json'],
        content: json_encode(['name' => 'Jan Novák', 'email' => 'jan@example.com', 'password' => 'SilneHeslo123!'])
    );

    $transport = self::getContainer()->get('messenger.transport.async');
    $sent      = $transport->getSent();

    $this->assertCount(1, $sent);
    $this->assertInstanceOf(SendWelcomeEmail::class, $sent[0]->getMessage());
}
:::
:::

### zenstruck/messenger-test

Holé assertions nad transportem fungují, ale v každém testu se opakují. Balíček
**zenstruck/messenger-test** je zabalí do čitelného API: trait `InteractsWithMessenger` zpřístupní
frontu transportu a metoda `process()` zpracuje zařazené zprávy přímo v testu, bez spouštění workera.

Balíček přináší vlastní transport a trvá na svém DSN. Nad `in-memory://` trait nefunguje, konfigurace
z předchozí ukázky se proto pro tento styl testů přepíše na `test://`
[[14]](https://github.com/zenstruck/messenger-test):

:::callout{type="pattern"}
### Příklad: Transport pro zenstruck/messenger-test

:::code{language="yaml" filename="config/packages/test/messenger.yaml"}
framework:
    messenger:
        transports:
            # DSN test:// vyžaduje trait InteractsWithMessenger; volitelné parametry
            # se předávají v query stringu, například test://?catch_exceptions=false
            async: 'test://'
:::
:::

Obě konfigurace jsou alternativy, ne vrstvy: transport má v daném prostředí jedno DSN. Sada, která
zůstane u `in-memory://`, si vystačí s `getSent()`; sada postavená na zenstruck jde na `test://`
a assertions píše přes trait.

:::callout{type="pattern"}
### Příklad: Test celého asynchronního toku se zenstruck/messenger-test

:::code{language="php" filename="Tests/UserManagement/Functional/RegisterUserFlowTest.php"}
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class RegisterUserFlowTest extends WebTestCase
{
    use InteractsWithMessenger;

    public function testRegistrationQueuesAndProcessesWelcomeEmail(): void
    {
        $client = static::createClient();
        $client->request(/* ... registrace jako výše ... */);

        $this->transport('async')->queue()->assertCount(1);
        $this->transport('async')->queue()->assertContains(SendWelcomeEmail::class);

        $this->transport('async')->process();      // zpracuje frontu v testu
        $this->transport('async')->queue()->assertEmpty();
    }
}
:::
:::

### Test idempotence handleru

Asynchronní transport doručuje zprávy v režimu at-least-once: po pádu workera dorazí tatáž
zpráva podruhé. Handler proto musí být idempotentní – dvojí zpracování smí vyvolat jen jeden efekt.
Deduplikační mechanismus popisuje [Idempotent Inbox](/outbox-pattern#inbox) – záznam se ukládá
pod dvojicí `eventId` (ULID) a `consumer`. Test je krátký: zavolat handler dvakrát se stejnou
zprávou a spočítat efekty.

:::callout{type="pattern"}
### Příklad: Test idempotence handleru

:::code{language="php" filename="Tests/UserManagement/Application/SendWelcomeEmailHandlerTest.php"}
public function testHandlesDuplicateDeliveryOnce(): void
{
    $mailer  = new SpyMailer();
    $handler = new SendWelcomeEmailHandler($mailer, new InMemoryInboxRepository());

    $message = new SendWelcomeEmail(eventId: '01J0E2Q4Z3V9K5T7N8M2R6W1X0', email: 'jan@example.com');

    ($handler)($message);
    ($handler)($message); // opakované doručení téže zprávy

    $this->assertSame(1, $mailer->sentCount());
}
:::
:::

### Test outboxu

Outbox dává dvě garance a každá potřebuje vlastní integrační test. První: po `flush()` leží
událost v outbox tabulce, zapsaná ve stejné transakci jako agregát. Druhá: relay ji publikuje
do transportu a označí jako zpracovanou. Obě varianty relay procesu rozebírá
[kapitola o Outbox Pattern](/outbox-pattern#relay).

:::callout{type="pattern"}
### Příklad: Integrační testy outboxu (KernelTestCase)

:::code{language="php" filename="Tests/Ordering/Infrastructure/OutboxFlowTest.php"}
public function testFlushWritesEventToOutbox(): void
{
    ($this->placeOrderHandler)(new PlaceOrderCommand(/* ... */));

    $pending = $this->outboxRepository->fetchPending(limit: 10);

    $this->assertCount(1, $pending);
    $this->assertSame('order.placed', $pending[0]->messageType());
}

public function testRelayPublishesPendingEvents(): void
{
    ($this->placeOrderHandler)(new PlaceOrderCommand(/* ... */));

    $tester = new CommandTester($this->outboxDispatchCommand);
    $tester->execute([]);

    $transport = self::getContainer()->get('messenger.transport.async');

    $this->assertCount(1, $transport->getSent());
    $this->assertCount(0, $this->outboxRepository->fetchPending(limit: 10));
}
:::
:::

Druhý test záměrně končí dvojicí assertions: zpráva odešla a fronta pending záznamů je prázdná.
Pokud by relay publikoval, ale neoznačil záznam jako zpracovaný, příští běh by událost poslal znovu.

## 17.08 Architektonické testy {#architektonicke-testy}

Pravidlo, že doménová vrstva nesmí záviset na infrastruktuře ani na aplikační vrstvě, drží jen do první
uspěchané code review, ve které někdo přidá `use Doctrine\ORM\Mapping` do entity. Architektonické testy
tomu zabraňují technicky: pravidla závislostí jsou popsána deklarativně a porušení padne v CI jako
spadlý test, ne až v review.

### Deptrac

**Deptrac** analyzuje závislosti staticky. Popíšete vrstvy (layers) a povolená pravidla mezi nimi
(ruleset), nástroj projde kód a vypíše porušení. V CI běží jako samostatný krok vedle statické analýzy.

Historie balíčku stojí za jednu větu, protože podle ní čtenář hledá dokumentaci. Projekt vznikl
v sensiolabs-de, pokračoval pod hlavičkou QOSSMIC a dnes má vlastní organizaci. Balíček
`qossmic/deptrac` je od listopadu 2024 na Packagistu označený jako abandoned a nahradil ho
`deptrac/deptrac` [[15]](https://github.com/deptrac/deptrac). Řada 4.x drží konfiguraci ve výchozím
souboru `deptrac.php` s typovaným API; YAML zůstává podporovaný, ale `vendor/bin/deptrac init`
generuje PHP.

:::callout{type="pattern"}
### Příklad: deptrac.php konfigurace pro DDD projekt

:::code{language="php" filename="deptrac.php"}
<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\DirectoryConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $config
        ->paths('./src')
        ->baseline('deptrac.baseline.yaml')
        ->layers(
            $domain = Layer::withName('Domain')->collectors(
                DirectoryConfig::create('src/.*/Domain/.*')
            ),
            $application = Layer::withName('Application')->collectors(
                DirectoryConfig::create('src/.*/Application/.*'),
                // use-case slice mimo Application/ - handlery typu
                // App\UserManagement\Registration\Command\RegisterUserHandler
                DirectoryConfig::create('src/.*/Registration/Command/.*')
            ),
            $infrastructure = Layer::withName('Infrastructure')->collectors(
                DirectoryConfig::create('src/.*/Infrastructure/.*')
            ),
            $presentation = Layer::withName('Presentation')->collectors(
                DirectoryConfig::create('src/.*/Controller/.*')
            ),
            $shared = Layer::withName('Shared')->collectors(
                DirectoryConfig::create('src/SharedKernel/.*')
            ),
        )
        ->rulesets(
            // Doménová vrstva sahá jen na Shared
            Ruleset::forLayer($domain)->accesses($shared),
            // Aplikační vrstva stojí na doméně a sdílených komponentách
            Ruleset::forLayer($application)->accesses($domain, $shared),
            // Infrastruktura implementuje doménová rozhraní
            Ruleset::forLayer($infrastructure)->accesses($domain, $application, $shared),
            // Kontrolery mluví s aplikační vrstvou (commands, queries)
            Ruleset::forLayer($presentation)->accesses($application, $shared),
            // Shared nezávisí na ničem projektovém - ruleset bez accesses()
            Ruleset::forLayer($shared),
        )
    ;
};
:::
:::

V novém projektu drží pravidlo od prvního commitu. Do existující kódové báze se dostane jen přes
**baseline**: `analyse --formatter=baseline` zapíše současná porušení do `deptrac.baseline.yaml`,
konfigurace ho načte a build zůstane zelený. Nová porušení pak selžou, stará se odbourávají postupně.
Bez baseline skončí první spuštění stovkou chyb a pravidlo se z CI zase vyhodí.

:::callout{type="pattern"}
### Příklad: Spuštění Deptrac v CI

:::code{language="bash" filename="snippet.sh"}
# Instalace (dev závislost)
composer require --dev deptrac/deptrac

# Vygenerování šablony konfigurace
vendor/bin/deptrac init

# Spuštění analýzy nad výchozím deptrac.php
vendor/bin/deptrac analyse

# Jednorázově při zavádění: zapsat současná porušení do baseline
vendor/bin/deptrac analyse --formatter=baseline

# Výstup v případě porušení:
# [ERROR] Found 1 Violation
# UserManagement\Domain\Model\User must not depend on
# Doctrine\ORM\Mapping\Column (Infrastructure layer)
:::
:::

### phparkitect jako alternativa

Pravidla závislostí umí vynutit i **phparkitect** (phparkitect/phparkitect)
[[16]](https://github.com/phparkitect/arkitect). Pracuje na jiné úrovni než Deptrac: místo vrstev
popisuje pravidla nad jednotlivými třídami, jejich namespace a názvy. Zapisují se do souboru
`phparkitect.php`. Nástroj má vlastní CLI a nespouští se přes PHPUnit – v CI běží jako samostatný
krok vedle testovací sady. Instalaci a přehled pravidel uvádí kapitola
[Méně známé vzory](/mene-zname-vzory#mod-phparkitect); plnou konfiguraci pro modular monolith
ukazuje kapitola [DDD a microservices](/ddd-a-microservices#phparkitect-rules-heading).
Zde stačí zapojení do pipeline:

:::callout{type="pattern"}
### Příklad: Spuštění phparkitect v CI

:::code{language="bash" filename="snippet.sh"}
composer require --dev phparkitect/phparkitect

# Samostatný krok CI pipeline vedle PHPUnit a Deptrac
vendor/bin/phparkitect check

# Zavádění do existujícího projektu: zmrazit současná porušení
vendor/bin/phparkitect generate-baseline   # zapíše phparkitect-baseline.json
vendor/bin/phparkitect prune-baseline      # odstraní z baseline už opravená porušení
:::
:::

Volba mezi oběma nástroji je věcí preferencí týmu. Deptrac popisuje vrstvy a hodí se pro plošná
pravidla mezi nimi; phparkitect dovolí jemnější pravidla nad jednotlivými třídami (suffixy názvů,
konkrétní namespace). Oba selžou v CI stejně – jako spadlý build. Projekty, které používají Pest,
mají třetí možnost: vestavěné `arch()` API zapisuje totéž pravidlo jako
`arch('domain')->expect('App\Domain')->not->toUse('App\Infrastructure')` a běží rovnou uvnitř
testovací sady [[17]](https://pestphp.com/docs/arch-testing).

## 17.09 Code coverage a doporučené postupy {#pokryti-a-best-practices}

Code coverage měří, jaké procento řádků kódu se při běhu testů provede. Sama metrika nic neříká
o kvalitě testů – 100% pokrytí lze dosáhnout testy, které jen volají metody bez assertů. Užitečná
je ale opačně: tam, kde je pokrytí nízké, leží kód, který nikdo netestuje. Tam stojí za to se podívat.

:::callout{type="note"}
### Pokrytí po vrstvách – co kde testovat:

- **Doménová vrstva (Domain)** – testuje se beze zbytku. Leží tu veškerá doménová logika a každý invariant, každá validace i každé pravidlo má mít vlastní test. Nepokrytý řádek v doméně je otázka, ne statistika.
- **Aplikační vrstva (Application)** – unit testy handlerů s InMemory repozitáři. Nepokryté zůstávají hlavně technické větve: logování, mapování výjimek na HTTP kódy.
- **Infrastrukturní vrstva (Infrastructure)** – integrační testy tam, kde je vlastní kód. Dotazy repozitářů, konverze typů a klienti externích API test potřebují; vygenerované Doctrine mapování ne.
- **Prezentační vrstva (Presentation)** – funkční testy hlavního scénáře a nejdůležitějších chybových cest.

Konkrétní cílové procento tato kniha nedoporučuje. Žádný z kanonických zdrojů takové číslo nestanovuje
a metrika sama o kvalitě testů nevypovídá. Užitečný je trend: propad pokrytí domény mezi dvěma sprinty
znamená, že do ní něco přibylo bez testu.
:::

:::callout{type="note"}
### Naming conventions pro testy v DDD:

- Testovací třída odpovídá testované třídě: `Email` → `EmailTest`, `RegisterUserHandler` → `RegisterUserHandlerTest`.
- Testovací metody popisují chování anglicky nebo česky: `testThrowsExceptionForInvalidEmail()`, `testRegistersNewUser()`.
- Struktura testovacích souborů zrcadlí strukturu produkčního kódu: `src/UserManagement/Domain/` → `tests/UserManagement/Domain/`.
- Testy hledá PHPUnit podle suffixu **souboru**, výchozí hodnota je `Test.php`. Suffix se dá změnit (volbou `--test-suffix` nebo atributem `suffix` u `<directory>` v XML konfiguraci), odchylka od konvence ale nikomu nepomůže.
:::

:::callout{type="note"}
### Arrange-Act-Assert (AAA) pattern:

Každý test má tři oddělené fáze:

1. **Arrange (připrav)** – Nastav počáteční stav: vytvoř objekty, nakonfiguruj závislosti, nastav data.
2. **Act (proveď)** – Proveď jednu testovanou akci: zavolej metodu, odešli command.
3. **Assert (ověř)** – Ověř výsledek: assertuj výstup, zkontroluj stav objektu, ověř vydané události.

Každý test by měl ověřovat právě jednu věc (jeden logický assertion). Více assertů v jednom testu je přijatelné,
pokud všechny společně ověřují jeden konzistentní scénář.
:::

:::callout{type="warn"}
### Nejčastější chyby při testování DDD

- **Testování getterů místo chování** – Špatně: `$this->assertSame('jan@example.com', $user->getEmail())` po přímém nastavení fieldu.
  Správně: zavolat doménovou operaci a ověřit výsledný stav.
- **Přímý přístup k privátním fieldům přes reflexi** – Porušuje zapouzdření. Pokud potřebujete přistupovat k privátnímu stavu v testu, je to příznak špatného návrhu API třídy.
- **Bootstrapování celého Symfony kernelu v unit testech** – Unit testy doménové vrstvy nesmí volat `self::bootKernel()`. Bootstrap kernelu patří do integračních testů. Zpomaluje sadu testů.
- **Sdílený stav mezi testy** – Každý test musí být nezávislý. Sdílené statické proměnné nebo globální stav způsobují nestabilní (flaky) testy, jejichž výsledek závisí na pořadí spouštění.
- **Mockování value objects** – Value objekty jsou datové třídy bez závislostí. Není důvod je mockovat – vždy vytvořte skutečnou instanci.
- **Ignorování doménových výjimek v testech** – Každá doménová výjimka (`InvalidEmailException`, `InvalidOrderStateTransitionException` apod.) musí mít test ověřující, že je vyhozena za správných podmínek.
- **Chybějící test pro releaseEvents() po operaci** – Pokud agregát vydává doménové události, každá veřejná operace, která má událost vydat, musí mít test ověřující typ, počet a obsah vydaných událostí.
:::

### Mutation testing

Coverage říká, které řádky test spustil. Neříká, jestli by si všiml, kdyby se změnily. Na tuto otázku
odpovídá mutation testing: nástroj zanese do kódu drobné změny (obrátí podmínku, posune mez, smaže
volání) a spustí testy. Mutace, kterou nikdo nezachytil, ukazuje na řádek, který má pokrytí, ale
nemá assert. Podíl zachycených mutací je mutation score.

V PHP mutace generuje Infection [[18]](https://infection.github.io/). Nejvíc se vyplatí nad doménovou
vrstvou: logika je tam hustá, testy rychlé a mutace mají co porušit. Nad infrastrukturou běh trvá
dlouho a nálezy bývají technické.

:::callout{type="pattern"}
### Příklad: Spuštění Infection nad doménovou vrstvou

:::code{language="bash" filename="snippet.sh"}
composer require --dev infection/infection

# Zdrojové adresáře se nastavují v infection.json5, práh se hlídá v CI
vendor/bin/infection --threads=max --min-msi=80
:::
:::

### Konfigurace a spouštění

Tři testovací sady z předchozích sekcí definuje `phpunit.dist.xml`. PHPUnit hledá konfiguraci
v pořadí `phpunit.xml`, `phpunit.dist.xml`, `phpunit.xml.dist` – starší název tedy dál funguje,
jen má nejnižší prioritu. Ve stejném souboru se registruje i extension pro transakční izolaci
z [sekce o integračních testech](#integracni-testy):

:::callout{type="pattern"}
### Příklad: phpunit.dist.xml se třemi testsuitami

:::code{language="xml" filename="phpunit.dist.xml"}
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         failOnWarning="true"
         failOnDeprecation="true"
>
    <php>
        <server name="APP_ENV" value="test" force="true"/>
        <server name="KERNEL_CLASS" value="App\Kernel"/>
    </php>

    <testsuites>
        <!-- Doména běží bez kernelu - tuto sadu lze spouštět po každé změně -->
        <testsuite name="Domain">
            <directory>tests/*/Domain</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/*/Infrastructure</directory>
        </testsuite>
        <testsuite name="Functional">
            <directory>tests/*/Functional</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>

    <!-- Transakční izolace integračních a funkčních testů -->
    <extensions>
        <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
    </extensions>
</phpunit>
:::
:::

:::callout{type="pattern"}
### Příklad: Spuštění testových sad pro DDD projekt

:::code{language="bash" filename="snippet.sh"}
# Spuštění unit testů doménové vrstvy (rychlé, bez kernelu)
vendor/bin/phpunit --testsuite=Domain

# Spuštění integračních testů (vyžaduje databázi)
vendor/bin/phpunit --testsuite=Integration

# Spuštění funkčních testů
vendor/bin/phpunit --testsuite=Functional

# Generování HTML coverage reportu (vyžaduje Xdebug nebo PCOV)
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=coverage/

# Spuštění architektonických testů s Deptrac
vendor/bin/deptrac analyse
:::
:::

Testovací pyramida v DDD funguje díky tomu, že doménová vrstva je čistý PHP bez závislostí na frameworku.
Doménová sada se proto dá spustit po každé změně, ne jen na konci dne. Integrační a funkční testy doplňují
pokrytí tam, kde vstupuje infrastruktura, a architektonické testy hlídají, aby izolace domény nezmizela
při dalším refaktoringu.

:::faq{}
- question: Jak testovat agregát – unit test s mock repozitářem, nebo integrační test?
  answer: 'Agregát se testuje primárně unit testem – je to čistý PHP bez závislostí na frameworku nebo databázi. Test instancuje agregát, volá jeho metody a ověřuje výsledný stav i vyvolané doménové události. Mock repozitáře přitom není potřeba, protože samotný agregát repozitář nevolá. Integrační test doplňuje pokrytí až na úrovni, kde vstupuje persistence – tedy při ukládání a načítání agregátu. Podrobný rozbor v <a href="#unit-testy-domeny">sekci Unit testy doménové vrstvy</a>.'
- question: K čemu slouží InMemory repozitář a kdy ho preferovat před mockem?
  answer: 'InMemory repozitář je plnohodnotná implementace rozhraní repozitáře, která drží agregáty v poli v paměti. Oproti mocku simuluje reálné chování (najít, uložit, počítat), takže testy aplikačních služeb procházejí celý use case věrohodněji. Mock se hodí tam, kde je potřeba ověřit konkrétní interakci – kolikrát byla metoda volána a s jakými argumenty. InMemory repozitář naopak slouží pro ověření výsledku, ne volání. Rozbor variant v <a href="#test-doubles">sekci Test doubles a InMemory repozitáře</a>.'
- question: Jak ověřit, že agregát publikuje správné doménové události?
  answer: 'Po vykonání metody se z agregátu vyčte seznam zaznamenaných událostí (typicky přes <code>releaseEvents()</code>) a testem se ověří jejich typ, pořadí i obsah. Kontroluje se, že agregát vyvolal přesně ty události, které má, a nevyvolal žádné navíc. Pro funkční test lze stejné události zachytávat přes Messenger event bus a ověřit reakce dalších částí systému. Praktický příklad v <a href="#testovani-domain-events">sekci Testování doménových událostí</a>.'
- question: Mají se testovat privátní invarianty agregátu, nebo jen veřejné rozhraní?
  answer: 'Testuje se pouze veřejné rozhraní – chování agregátu přes metody, které se reálně volají z aplikační vrstvy. Privátní invarianty jsou detailem implementace a jejich přímé testování sváže test s konkrétní strukturou kódu, což brání refaktoringu. Dobře navržený test ověřuje, že po sérii veřejných volání je agregát ve validním stavu, vyvolal očekávané události a při porušení pravidla vyhodil konkrétní doménovou výjimku. Detailní rozbor v <a href="#unit-testy-domeny">sekci Unit testy doménové vrstvy</a>.'
- question: Co jsou architektonické testy a co kontrolují?
  answer: 'Architektonické testy automaticky ověřují, že kód dodržuje zvolená pravidla struktury – například že doménová vrstva nezávisí na Doctrine, že agregáty nevolají repozitáře přímo, nebo že kontrolery nekomunikují s infrastrukturou. V Symfony se používá nástroj Deptrac (balíček <code>deptrac/deptrac</code>), který pravidla popisuje deklarativně v souboru <code>deptrac.php</code> a spouští se v CI jako samostatný krok vedle testovací sady. Porušení pravidla se projeví jako spadlý build, nikoli až při code review. Rozbor nástrojů a pravidel v <a href="#architektonicke-testy">sekci Architektonické testy</a>.'
:::
