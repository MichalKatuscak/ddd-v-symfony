---
route: testing_ddd
path: /testovani-ddd
title: Testování DDD kódu v Symfony
page_title: "Testování DDD kódu v Symfony | DDD Symfony"
meta_description: "Testování DDD kódu v Symfony: unit testy agregátů, integrace přes Doctrine, in-memory repozitáře, testy doménových událostí a architektonické testy (Deptrac)."
meta_keywords: "testování DDD, PHPUnit, unit testy, integrační testy, funkční testy, InMemory repozitář, test doubles, doménové události, Deptrac, PHP-Arkitect, KernelTestCase, WebTestCase, Symfony testy, testovací pyramida, coverage"
og_type: article
published: "2025-04-24"
modified: "2026-05-03"
breadcrumb_name: Testování DDD
schema_type: TechArticle
schema_headline: "Testování DDD kódu v Symfony"
chapter_number: "17"
category: Praxe
deck: "Testování Domain-Driven Design kódu v Symfony v praxi. Unit testy doménové vrstvy, integrační testy s Doctrine, funkční testy API, InMemory repozitáře, testování doménových událostí a architektonické testy s Deptrac."
reading_time: 30
difficulty: 3
github_examples: Chapter08_Testing
---

## 17.01 Filozofie testování v DDD {#filozofie-testovani}

Doménová vrstva v DDD nezávisí na frameworku ani na databázi, takže ji lze testovat přímo z PHPUnitu bez
bootstrappingu Symfony kernelu. To je hlavní praktický rozdíl proti tradičním vrstveným architekturám,
kde jsou unit testy svázané s kontejnerem a kde běh tisíce testů trvá minuty. V DDD běží stejný počet testů
v sekundách. Stavební kameny doménové vrstvy – entity, hodnotové objekty, agregáty, doménové události – popisuje
kapitola [Základní koncepty DDD](/zakladni-koncepty).

:::diagram{fig="18.1-A" title="Testovací pyramida pro DDD aplikaci - poměr a obsah jednotlivých vrstev" src="images/diagrams/18_testing_ddd/test_pyramid.svg"}
:::

:::callout{type="note"}
### Proč je DDD dobře testovatelný:

- **Žádné závislosti na frameworku** – Doménové třídy (entity, value objects, agregáty) jsou čisté PHP objekty. Nepotřebují Symfony kontejner, Doctrine ani HTTP stack.
- **Explicitní závislosti** – Závislosti se vždy předávají přes konstruktor (constructor injection), nikoli ze statických globálních objektů. To umožňuje jejich záměnu za test doubles.
- **Bohaté doménové modely** – Doménová logika je soustředěna v doménových objektech, nikoli roztroušena v kontrolerech nebo šablonách. Testy pokrývají chování, na kterém záleží.
- **Invarianty se vynucují při konstrukci** – Value objekty a agregáty ověřují svá invariantní pravidla v konstruktoru nebo v továrních metodách. To usnadňuje testování správného i nesprávného stavu.
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

:::callout{type="note"}
### Testovací strategie – co testovat na každé vrstvě:

- **Doménová vrstva:** Validační logika value objects, invarianty entit, transakční konzistence agregátů, vydávání doménových událostí, doménové výjimky.
- **Aplikační vrstva:** Command handlery a query handlery – s použitím fake (InMemory) repozitářů, ověření, že správné metody repozitáře jsou volány s očekávanými argumenty.
- **Infrastrukturní vrstva:** Správné Doctrine mapování, dotazy repozitářů, transakce, volání externích API.
- **Prezentační vrstva:** Správné HTTP status kódy, formát odpovědi, autentizace a autorizace.
:::

## 17.02 Unit testy doménové vrstvy {#unit-testy-domeny}

Unit testy doménové vrstvy tvoří základ testovací sady DDD aplikace. Pokrývají největší podíl kódu, běží
v řádu milisekund a nepotřebují nic jiného než PHPUnit a samotné doménové třídy. Žádný bootstrap Symfony
kernelu, žádná databáze, žádné fixtures.

### Testování Value Objects

Test value objektu ověřuje tři věci: že neplatný vstup vyhodí odpovídající výjimku, že dvě instance
se stejnou hodnotou jsou si rovny přes `equals()`, a že každá operace vrací novou instanci místo
modifikace stávající. Tím je hodnotový objekt pokrytý.

:::callout{type="pattern"}
### Příklad: Test pro Email value object (PHPUnit)

:::code{language="php" filename="Tests/UserManagement/Domain/ValueObject/EmailTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\Exception\InvalidEmailException;

final class EmailTest extends TestCase
{
    public function testCreatesValidEmail(): void
    {
        $email = new Email('jan.novak@example.com');

        $this->assertSame('jan.novak@example.com', $email->value());
    }

    public function testNormalizesToLowercase(): void
    {
        $email = new Email('Jan.Novak@EXAMPLE.COM');

        $this->assertSame('jan.novak@example.com', $email->value());
    }

    public function testThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Neplatná e-mailová adresa: "not-an-email"');

        new Email('not-an-email');
    }

    public function testThrowsExceptionForEmptyString(): void
    {
        $this->expectException(InvalidEmailException::class);

        new Email('');
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

        $this->assertSame('jan@example.com', $original->value());
        $this->assertSame('petr@example.com', $different->value());
        $this->assertFalse($original->equals($different));
    }
}
:::
:::

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

        $user->changeEmail(new Email('jan@example.com'));

        // Žádná událost by neměla být vydána, email je stále stejný
        $this->assertCount(0, $user->releaseDomainEvents());
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
správné události byly vydány ve správném pořadí jako vedlejší efekt doménových operací.

:::callout{type="pattern"}
### Příklad: Test pro Order agregát

:::code{language="php" filename="Tests/OrderManagement/Domain/Model/OrderTest.php"}
<?php

declare(strict_types=1);

namespace Tests\OrderManagement\Domain\Model;

use PHPUnit\Framework\TestCase;
use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\CustomerId;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\Currency;
use App\OrderManagement\Domain\Event\OrderPlaced;
use App\OrderManagement\Domain\Event\OrderItemAdded;
use App\OrderManagement\Domain\Exception\EmptyOrderException;
use App\OrderManagement\Domain\Exception\OrderAlreadyPlacedException;

final class OrderTest extends TestCase
{
    public function testAddsItemToOrder(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::generate());

        $order->addItem('Kniha o DDD', new Money(49900, Currency::CZK), 2);

        $this->assertSame(1, $order->itemCount());          // 1 řádek objednávky
        $this->assertEquals(new Money(99800, Currency::CZK), $order->total()); // 49 900 × 2
    }

    public function testThrowsExceptionWhenPlacingEmptyOrder(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::generate());

        $this->expectException(EmptyOrderException::class);

        $order->place();
    }

    public function testPlacesOrderSuccessfully(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::generate());
        $order->addItem('Produkt A', new Money(10000, Currency::CZK), 1);

        $order->place();

        $this->assertTrue($order->isPlaced());
    }

    public function testThrowsExceptionWhenPlacingAlreadyPlacedOrder(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::generate());
        $order->addItem('Produkt A', new Money(10000, Currency::CZK), 1);
        $order->place();

        $this->expectException(OrderAlreadyPlacedException::class);

        $order->place();
    }

    public function testReleasesOrderPlacedEvent(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::generate());
        $order->addItem('Produkt A', new Money(10000, Currency::CZK), 1);
        $order->place();

        $events = $order->releaseDomainEvents();

        $this->assertCount(2, $events); // OrderItemAdded + OrderPlaced
        $this->assertInstanceOf(OrderItemAdded::class, $events[0]);
        $this->assertInstanceOf(OrderPlaced::class, $events[1]);
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
Metoda `releaseDomainEvents()` vrátí všechny nashromážděné události a pole vymaže. Tento přístup nevyžaduje
v unit testech žádný event dispatcher ani bus. Testovací kód zavolá doménovou operaci a ověří
obsah vrácených událostí.
:::

:::callout{type="pattern"}
### Příklad: Trait pro testování doménových událostí

:::code{language="php" filename="Tests/Shared/Domain/DomainEventAssertions.php"}
<?php

declare(strict_types=1);

namespace Tests\Shared\Domain;

use App\Shared\Domain\Event\DomainEvent;

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

namespace Tests\OrderManagement\Domain\Model;

use App\OrderManagement\Domain\Model\Order;
use App\OrderManagement\Domain\ValueObject\OrderId;
use App\OrderManagement\Domain\ValueObject\CustomerId;
use App\OrderManagement\Domain\ValueObject\Money;
use App\OrderManagement\Domain\ValueObject\Currency;
use App\OrderManagement\Domain\Event\OrderPlaced;

final class OrderEventsTest extends \PHPUnit\Framework\TestCase
{
    use DomainEventAssertions;

    public function testOrderPlacedEventContainsCorrectData(): void
    {
        $customerId = CustomerId::generate();
        $order      = Order::create(OrderId::generate(), $customerId);
        $order->addItem('Produkt A', new Money(25000, Currency::CZK), 3);
        $order->place();

        $events      = $order->releaseDomainEvents();
        $placedEvent = $this->assertSingleEventOfType(OrderPlaced::class, $events);

        // Ověření dat události
        $this->assertTrue($customerId->equals($placedEvent->customerId()));
        $this->assertEquals(new Money(75000, Currency::CZK), $placedEvent->total());
        $this->assertNotNull($placedEvent->occurredOn());
    }

    public function testNoOrderPlacedEventWhenOrderNotPlaced(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::generate());
        $order->addItem('Produkt B', new Money(10000, Currency::CZK), 1);

        $events = $order->releaseDomainEvents();

        $this->assertNoEventOfType(OrderPlaced::class, $events);
    }
}
:::
:::

## 17.04 Test doubles a InMemory repozitáře {#test-doubles}

Test double je obecný název pro náhradu reálné závislosti v testu. PHPUnit a literatura rozlišují čtyři
varianty (stub, mock, fake, spy) a v DDD má každá z nich jiný dopad: vede k jinému stylu testu a k jiné
odolnosti vůči refaktoringu.

:::callout{type="note"}
### Typy test doubles a jejich použití v DDD:

- **Stub** – Vrací předpřipravené odpovědi bez logiky. Vhodný, když potřebujeme, aby závislost vrátila konkrétní hodnotu, ale nezajímá nás, zda a kolikrát byla volána. Příklad: `$stub->method('findById')->willReturn($user)`.
- **Mock** – Stub s ověřením volání. Ověřuje, že byla zavolána konkrétní metoda s konkrétními argumenty přesně n-krát. Vhodný pro ověření vedlejších efektů (volání repozitáře, odeslání e-mailu). Příklad: `$mock->expects($this->once())->method('save')`.
- **Fake** – Plnohodnotná, ale zjednodušená implementace rozhraní (typicky in-memory). Nemá databázovou závislost, ale chová se jako skutečná implementace. **Doporučený přístup pro DDD repozitáře** – umožňuje psát čitelné testy bez konfigurování mocků.
- **Spy** – Podobný mocku, ale ověření probíhá až po akci (post-assertion style). Méně časté v PHP.
:::

:::callout{type="note"}
### Proč preferovat Fake (InMemory) před Mockem pro repozitáře:

- Testy jsou čitelnější – nepotřebují konfigurace `expects()->method()->with()->willReturn()`.
- InMemory repozitář lze sdílet mezi command handlerem a query handlerem v jednom testu – ověříme reálný průchod dat.
- Při změně signatury rozhraní IDE a statická analýza okamžitě upozorní, na rozdíl od string-based konfigurace mocků.
- Mocky testují implementační detail (které metody jsou volány), Fake testuje chování (co se stane s daty).
:::

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

use PHPUnit\Framework\TestCase;
use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Command\RegisterUserHandler;
use App\UserManagement\Domain\Exception\EmailAlreadyTakenException;
use Tests\UserManagement\Infrastructure\Repository\InMemoryUserRepository;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        $this->handler        = new RegisterUserHandler($this->userRepository);
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

        $user = $this->userRepository->findByEmail(new \App\UserManagement\Domain\ValueObject\Email('jan@example.com'));
        $this->assertNotNull($user);
        $this->assertFalse($user->isActive()); // nový uživatel je neaktivní
    }

    public function testThrowsExceptionWhenEmailAlreadyTaken(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'jan@example.com', password: 'Heslo123!');
        ($this->handler)($command); // první registrace

        $this->expectException(EmailAlreadyTakenException::class);

        ($this->handler)($command); // duplicitní registrace
    }

    public function testDoesNotPersistUserWhenEmailAlreadyTaken(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'jan@example.com', password: 'Heslo123!');
        ($this->handler)($command);

        try {
            ($this->handler)($command);
        } catch (EmailAlreadyTakenException) {
            // očekáváno
        }

        $this->assertSame(1, $this->userRepository->count());
    }
}
:::
:::

:::callout{type="warn"}
### Varování: Přílišné používání mocků

Nadměrné použití mocků (mockování každé závislosti) vede k tzv. *nadměrné specifikaci* testů.
Takové testy ověřují implementační detaily, nikoli chování. Při každém refaktoringu přestanou procházet,
i když se chování systému nezměnilo. Preferujte InMemory Fake implementace pro repozitáře a mocky používejte
pouze tam, kde ověřujete vedlejší efekty (odeslání e-mailu, volání externího API).
:::

## 17.05 Integrační testy s Doctrine {#integracni-testy}

Integrační testy odpovídají na otázku, kterou unit testy pokrýt nemohou: zda Doctrine mapování, dotazy
repozitářů a transakce skutečně dělají to, co jejich rozhraní slibuje. Spouští se proti reálné databázi –
typicky SQLite in-memory pro rychlost, nebo testovací PostgreSQL/MySQL instance pro shodu s produkcí.

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
a po jeho dokončení provést rollback. Symfony poskytuje `DoctrineTestHelper` a bundle
`dama/doctrine-test-bundle`, který toto chování implementuje automaticky pomocí dekorátoru
nad `Connection`. Bez toho by každý test zanechával data v databázi a testy by se navzájem ovlivňovaly.
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
use App\UserManagement\Domain\Exception\UserNotFoundException;
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

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository    = static::getContainer()->get(DoctrineUserRepository::class);
    }

    public function testPersistsAndRetrievesUser(): void
    {
        $userId = UserId::generate();
        $email  = new Email('integrace@example.com');
        $user   = User::register($userId, 'Test Uživatel', $email, HashedPassword::fromPlainText('Heslo123!'));

        $this->repository->save($user);
        $this->entityManager->clear(); // vyčistíme identity map - nutné pro skutečné čtení z DB

        $retrieved = $this->repository->findById($userId);

        $this->assertTrue($userId->equals($retrieved->id()));
        $this->assertTrue($email->equals($retrieved->email()));
    }

    public function testThrowsExceptionForNonExistentUser(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->repository->findById(UserId::generate());
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
                'email'    => 'not-valid-email',
                'password' => 'Heslo123!',
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $responseData);
        $this->assertStringContainsString('email', strtolower($responseData['errors'][0]['field']));
    }

    public function testReturns409WhenEmailAlreadyRegistered(): void
    {
        $client = static::createClient();

        $payload = json_encode(['email' => 'existujici@example.com', 'password' => 'Heslo123!']);

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

Funkční testy jsou nejpomalejší a nejkřehčí. Testujte pouze hlavní scénář a hlavní chybové scénáře.
Vše ostatní (okrajové případy, validace, doménová pravidla) pokryjte unit testy doménové vrstvy.
Příliš mnoho funkčních testů prodlužuje dobu CI/CD pipeline a snižuje motivaci vývojářů spouštět testy lokálně.
:::

## 17.07 Architektonické testy {#architektonicke-testy}

Pravidlo, že doménová vrstva nesmí záviset na infrastruktuře ani na aplikační vrstvě, drží jen do první
spěchající code review, ve které někdo přidá `use Doctrine\ORM\Mapping` do entity. Architektonické testy
tomu zabraňují technicky: pravidla závislostí jsou popsána deklarativně a porušení padne v CI jako
spadlý test, ne až v review.

### Deptrac

**Deptrac** je nástroj od QOSSMIC (dříve sensiolabs-de) pro statickou analýzu závislostí v PHP projektech.
Definujete vrstvy (layers) a povolená pravidla závislostí (ruleset). Deptrac analyzuje závislosti
v kódu a nahlásí porušení. Spouští se v CI jako součást statické analýzy.

:::callout{type="pattern"}
### Příklad: deptrac.yaml konfigurace pro DDD projekt

:::code{language="yaml" filename="deptrac.yaml"}
deptrac:
  paths:
    - ./src

  layers:
    - name: Domain
      collectors:
        - type: directory
          value: src/.*/Domain/.*

    - name: Application
      collectors:
        - type: directory
          value: src/.*/Application/.*

    - name: Infrastructure
      collectors:
        - type: directory
          value: src/.*/Infrastructure/.*

    - name: Presentation
      collectors:
        - type: directory
          value: src/.*/Controller/.*

    - name: Shared
      collectors:
        - type: directory
          value: src/Shared/.*

  ruleset:
    Domain:
      # Doménová vrstva nesmí záviset na ničem jiném než na Shared
      - Shared

    Application:
      # Aplikační vrstva závisí na doméně a sdílených komponentách
      - Domain
      - Shared

    Infrastructure:
      # Infrastruktura implementuje doménová rozhraní - závisí na doméně
      - Domain
      - Application
      - Shared

    Presentation:
      # Kontrolery závisí na aplikační vrstvě (Commands, Queries)
      - Application
      - Shared

    Shared:
      # Sdílené komponenty nezávisí na ničem projektovém
      []

  skip_violations:
    # Dočasné výjimky - měly by být minimalizovány
    # UserManagement\Domain\Model\User:
    #   - Symfony\Component\Security\Core\User\UserInterface  # Symfony interface v doméně - anti-vzor
:::
:::

:::callout{type="pattern"}
### Příklad: Spuštění Deptrac v CI

:::code{language="bash" filename="snippet.sh"}
# Instalace (dev závislost)
composer require --dev qossmic/deptrac-shim

# Spuštění analýzy
./vendor/bin/deptrac analyze --config-file=deptrac.yaml

# Výstup v případě porušení:
# [ERROR] Found 1 Violation
# UserManagement\Domain\Model\User must not depend on
# Doctrine\ORM\Mapping\Column (Infrastructure layer)
:::
:::

### PHP-Arkitect jako alternativa

**PHP-Arkitect** (phparkitect/phparkitect) je alternativní nástroj pro architektonické testy napsaný v PHP.
Na rozdíl od Deptrac s YAML konfigurací používá PHP API pro definici pravidel. To umožňuje
typově bezpečnou konfiguraci s podporou IDE. Pravidla se definují jako PHPUnit test,
takže výsledky se integrují přímo do testovací sady.

:::callout{type="pattern"}
### Příklad: PHP-Arkitect pravidla

:::code{language="php" filename="phparkitect.php"}
<?php

// phparkitect.php
use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $srcSet = ClassSet::fromDir(__DIR__ . '/src');

    $config->add(
        $srcSet,

        // Doménová vrstva nesmí záviset na Symfony ani Doctrine
        Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\UserManagement\Domain'))
            ->should(new NotDependsOnTheseNamespaces(
                'Symfony',
                'Doctrine',
            ))
            ->because('Doménová vrstva musí být nezávislá na frameworku a infrastruktuře.'),

        // Všechny třídy v Command namespace musí mít suffix Command nebo Handler
        Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\UserManagement\Application\Command'))
            ->should(new HaveNameMatching('*Command|*Handler'))
            ->because('Command namespace smí obsahovat pouze Command a Handler třídy (konvence projektu).'),
    );
};
:::
:::

## 17.08 Code coverage a doporučené postupy {#pokryti-a-best-practices}

Code coverage měří, jaké procento řádků kódu se při běhu testů provede. Sama metrika nic neříká
o kvalitě testů – 100% pokrytí lze dosáhnout testy, které jen volají metody bez assertů. Užitečná
je ale opačně: tam, kde je pokrytí nízké, leží kód, který nikdo netestuje. Tam stojí za to se podívat.

:::callout{type="pattern"}
### Doporučené pokrytí pro DDD vrstvy:

- **Doménová vrstva (Domain)** – 90–100 %. Tato vrstva obsahuje veškerou doménovou logiku. Každý invariant, každá validace a každé doménové pravidlo musí mít test.
- **Aplikační vrstva (Application)** – 80–90 %. Command a query handlery pokryjte unit testy s InMemory repozitáři.
- **Infrastrukturní vrstva (Infrastructure)** – 60–80 %. Repozitáře pokryjte integračními testy. Generovaný kód (Doctrine mappings) testovat nemusíte.
- **Prezentační vrstva (Presentation)** – 50–70 %. Kontrolery pokryjte funkčními testy pro hlavní scénáře.
:::

:::callout{type="note"}
### Naming conventions pro testy v DDD:

- Testovací třída odpovídá testované třídě: `Email` → `EmailTest`, `RegisterUserHandler` → `RegisterUserHandlerTest`.
- Testovací metody popisují chování anglicky nebo česky: `testThrowsExceptionForInvalidEmail()`, `testRegistersNewUser()`.
- Struktura testovacích souborů zrcadlí strukturu produkčního kódu: `src/UserManagement/Domain/` → `tests/UserManagement/Domain/`.
- Suffix `Test` pro PHPUnit testovací třídy je nutný (PHPUnit třídu bez suffixu nespustí).
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
- **Ignorování doménových výjimek v testech** – Každá doménová výjimka (`InvalidEmailException`, `OrderAlreadyPlacedException` apod.) musí mít test ověřující, že je vyhozena za správných podmínek.
- **Chybějící test pro releaseDomainEvents() po operaci** – Pokud agregát vydává doménové události, každá veřejná operace, která má událost vydat, musí mít test ověřující typ, počet a obsah vydaných událostí.
:::

:::callout{type="pattern"}
### Příklad: Spuštění testových sad pro DDD projekt

:::code{language="bash" filename="snippet.sh"}
# Spuštění unit testů doménové vrstvy (rychlé, bez kernelu)
./vendor/bin/phpunit --testsuite=Domain

# Spuštění integračních testů (vyžaduje databázi)
./vendor/bin/phpunit --testsuite=Integration

# Spuštění funkčních testů
./vendor/bin/phpunit --testsuite=Functional

# Generování HTML coverage reportu (vyžaduje Xdebug nebo PCOV)
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html=coverage/

# Spuštění architektonických testů s Deptrac
./vendor/bin/deptrac analyze
:::
:::

Testovací pyramida v DDD funguje díky tomu, že doménová vrstva je čistý PHP bez závislostí na frameworku.
Tisíce unit testů proto běží v sekundách. Integrační a funkční testy doplňují pokrytí tam, kde vstupuje
infrastruktura, a architektonické testy hlídají, aby tato izolace nezmizela při dalším refaktoringu.

:::faq{}
- question: Jak testovat agregát – unit test s mock repozitářem, nebo integrační test?
  answer: 'Agregát se testuje primárně unit testem – je to čistý PHP bez závislostí na frameworku nebo databázi. Test instancuje agregát, volá jeho metody a ověřuje výsledný stav i vyvolané doménové události. Mock repozitáře se přitom nepotřebuje, protože samotný agregát repozitář nevolá. Integrační test doplňuje pokrytí až na úrovni, kde vstupuje persistence – tedy při ukládání a načítání agregátu. Podrobný rozbor v <a href="#unit-testy-domeny">sekci Unit testy doménové vrstvy</a>.'
- question: K čemu slouží InMemory repozitář a kdy ho preferovat před mockem?
  answer: 'InMemory repozitář je plnohodnotná implementace rozhraní repozitáře, která drží agregáty v poli v paměti. Oproti mocku simuluje reálné chování (najít, uložit, počítat), takže testy aplikačních služeb procházejí celý use case věrohodněji. Mock se hodí tam, kde je potřeba ověřit konkrétní interakci – kolikrát byla metoda volána a s jakými argumenty. InMemory repozitář naopak slouží pro ověření výsledku, ne volání. Rozbor variant v <a href="#test-doubles">sekci Test doubles a InMemory repozitáře</a>.'
- question: Jak ověřit, že agregát publikuje správné doménové události?
  answer: 'Po vykonání metody se z agregátu vyčte seznam zaznamenaných událostí (typicky přes <code>releaseDomainEvents()</code>) a testem se ověří jejich typ, pořadí i obsah. Kontroluje se, že agregát vyvolal přesně ty události, které má, a nevyvolal žádné navíc. Pro funkční test lze stejné události zachytávat přes Messenger event bus a ověřit reakce dalších částí systému. Praktický příklad v <a href="#testovani-domain-events">sekci Testování doménových událostí</a>.'
- question: Mají se testovat privátní invarianty agregátu, nebo jen veřejné rozhraní?
  answer: 'Testuje se pouze veřejné rozhraní – chování agregátu přes metody, které se reálně volají z aplikační vrstvy. Privátní invarianty jsou detailem implementace a jejich přímé testování sváže test s konkrétní strukturou kódu, což brání refaktoringu. Dobře navržený test ověřuje, že po sérii veřejných volání je agregát ve validním stavu, vyvolal očekávané události a při porušení pravidla vyhodil konkrétní doménovou výjimku. Detailní rozbor v <a href="#unit-testy-domeny">sekci Unit testy doménové vrstvy</a>.'
- question: Co jsou architektonické testy a co kontrolují?
  answer: 'Architektonické testy automaticky ověřují, že kód dodržuje zvolená pravidla struktury – například že doménová vrstva nezávisí na Doctrine, že agregáty nevolají repozitáře přímo, nebo že kontrolery nekomunikují s infrastrukturou. V Symfony se používá nástroj Deptrac, který pravidla popisuje deklarativně v YAML a spouští se jako další testovací sada. Porušení pravidla se projeví jako spadlý test, nikoli až při code review. Rozbor nástrojů a pravidel v <a href="#architektonicke-testy">sekci Architektonické testy</a>.'
:::
