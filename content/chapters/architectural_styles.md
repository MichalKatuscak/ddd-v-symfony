---
route: architectural_styles
path: /architektonicke-styly
title: Architektonické styly – Hexagonal, Onion, Clean
page_title: "Hexagonal, Onion, Clean Architecture – co si vybrat | DDD Symfony"
meta_description: "Srovnání architektonických stylů (Layered, Hexagonal, Onion, Clean) v kontextu DDD a Symfony. Praktický návod, kdy který volit a jaký dopad to má na strukturu Symfony projektu."
meta_keywords: "Hexagonal Architecture, Ports and Adapters, Onion Architecture, Clean Architecture, Layered Architecture, Vertical Slice, DDD, Symfony, Cockburn, Palermo, Martin, Dependency Rule"
og_type: article
published: "2026-04-29"
modified: "2026-05-03"
breadcrumb_name: Architektonické styly
schema_type: TechArticle
schema_headline: "Architektonické styly: Hexagonal, Onion, Clean – co si vybrat"
chapter_number: "09"
category: Základy
deck: "DDD vám říká <em>co</em> modelovat. Architektonický styl říká <em>kam</em> to modelované strčit. Čtyři školy – klasická vrstvená, Hexagonální (Cockburn), Onion (Palermo), Clean (Martin) – a Vertical Slice jako pátá. Kapitola srovnává jejich odlišnosti, podobnosti a co vybrat v Symfony 8 projektu."
reading_time: 22
difficulty: 3
github_examples: null
---

Když tým poprvé pronese „přejdeme na DDD“, obvykle si pod tím představuje dvě věci najednou: *budeme líp modelovat doménu* a zároveň *přerovnáme adresářovou strukturu*. Tato dvě rozhodnutí jsou ve skutečnosti **ortogonální**. Domain-Driven Design je modelovací technika; architektonický styl je rozhodnutí o uspořádání kódu a směru závislostí. Můžete dělat DDD ve vrstvené architektuře, v Hexagonální, v Onion, v Clean i ve Vertical Slice. Můžete naopak vést Hexagonální architekturu nad anémickým CRUD modelem a tím nemít s DDD nic společného.

Následující sekce srovnávají čtyři vrstvové styly (Layered, Hexagonal, Onion, Clean) s pátým – feature-orientovaným Vertical Slice – a ukazují, jak konkrétně každý vypadá v Symfony 8 projektu. Cílem není prohlásit jeden styl za vítěze: každý má svůj kontext, kde dává smysl. Cílem je dát vám rozhodovací kritéria a varovat před nejčastějšími anti-vzory, které z dobré teorie udělají špatný kód.

## 09.01 Proč architektonický styl není totéž co DDD {#proc-styl}

Asi nejčastější zdroj zmatku v DDD literatuře je směšování dvou nezávislých rozhodnutí. První rozhodnutí se týká **modelovací techniky**: budeme používat agregáty, hodnotové objekty, doménové události, ubiquitous language a bounded contexts? Nebo zůstaneme u procedurálního CRUDu, kde controller čte z databáze, aplikuje validaci a zapíše zpět? Druhé rozhodnutí se týká **uspořádání kódu**: budeme členit projekt podle technických vrstev, přes porty a adaptéry, do koncentrických prstenců, nebo podle feature?

Tato dvě rozhodnutí lze kombinovat libovolně. Najdete projekty s čistým CRUD modelem v Hexagonální architektuře (porty oddělují HTTP od databáze, ale uvnitř je anémický řádek tabulky). Najdete bohaté DDD agregáty v klasické vrstvené struktuře (Doctrine entity v adresáři `src/Entity`, ale s metodami jako `$order->confirm()`, `$order->cancel()` a invarianty kontrolovanými v konstruktoru). Architektonický styl ovlivňuje *testovatelnost a kompozici*, ne *modelovací metodu*.

:::callout{type="note"}
### Dva ortogonální axisy rozhodnutí

Při návrhu projektu si vždy oddělte tyto dvě otázky:

- **Modelovací osa** – od CRUD/Transaction Script přes Anemic Domain Model k bohatému DDD modelu (agregáty, value objekty, doménové události, invarianty).
- **Strukturální osa** – od jednoduché Layered struktury přes Hexagonal/Onion/Clean s explicitní inverzí závislostí až po Vertical Slice s feature-first organizací.

Posun po jedné ose neimplikuje posun po druhé. Pokud váš projekt trpí *špatným modelováním domény*, přechod na Hexagonal samotný to nevyřeší. A pokud trpí *špatnou izolací od infrastruktury*, přechod na DDD s dál pevně provázanými Doctrine entitami to také neopraví.
:::

Eric Evans v původní knize *Domain-Driven Design* (2003) [[1]](https://www.domainlanguage.com/ddd/) popisuje doporučenou „layered architecture“ jen v jedné krátké kapitole. Explicitně říká, že DDD je primárně o modelování – strukturální vrstvy jsou způsob, jak ten model chránit před technickými detaily, ne cíl sám o sobě. Pozdější autoři (Vernon, Khononov, Millett & Tune) ukazují DDD ve více strukturálních stylech – vrstvové i hexagonální i feature-first. Všechny fungují, pokud doménový model uvnitř má skutečný obsah.

Pokud je vaše doména triviální (CRUD nad několika tabulkami, žádné invarianty, žádné stavové přechody), žádný architektonický styl vám nepomůže – protože není co chránit. Pokud je vaše doména bohatá, ale neoddělíte ji od framework-specifických věcí (Doctrine anotace, Symfony Request/Response objekty, externí HTTP klienti), získáte na první pohled „čistý“ kód. Ten se ale nedá testovat bez celé infrastruktury.

V dalších sekcích projdeme jednotlivé styly v pořadí od nejjednoduššího k nejkomplexnějšímu. Pro každý definujeme: co styl říká, jak vypadá v Symfony, kdy se hodí, kdy ne, a jaký je nejčastější anti-vzor.

## 09.02 Layered (klasická vrstvená) {#layered}

Vrstvená architektura je nejstarší a nejrozšířenější způsob, jak organizovat podnikovou aplikaci. Martin Fowler ji popsal v knize *Patterns of Enterprise Application Architecture* (2002) [[2]](https://martinfowler.com/eaaCatalog/) jako „Service Layer + Domain Model + Data Source Layer“, což pozdější DDD literatura zjednodušila na čtyři vrstvy: Presentation, Application, Domain, Infrastructure. Eric Evans v *Domain-Driven Design* (2003) převzal totéž schéma a přidal pravidlo, že **vrstva smí záviset jen na vrstvách pod sebou**, nikdy nahoru.

### Čtyři standardní vrstvy {#layered-vrstvy-heading}

- **Presentation Layer** – interakce se světem (HTTP controllery, CLI commandy, GraphQL resolvery). V Symfony to jsou třídy v `src/Controller/`.
- **Application Layer** – orchestrace use casů, transakce, mapování DTO. Tenké třídy, žádná doménová logika; ta žije v doméně. V Symfony bývají v `src/Service/` nebo `src/Application/`.
- **Domain Layer** – agregáty, entity, hodnotové objekty, doménové služby, repository *rozhraní*. Žádné framework závislosti. V Symfony obvykle `src/Entity/` + `src/Domain/`.
- **Infrastructure Layer** – Doctrine repository implementace, e-mail brány, HTTP klienti, Messenger transporty. V Symfony `src/Repository/` + `src/Infrastructure/`.

### Typická Symfony struktura {#layered-symfony-heading}

:::code{language="bash" filename="src/ (Symfony Layered konvence)"}
src/
├── Controller/                      # Presentation
│   ├── OrderController.php
│   └── CustomerController.php
├── Service/                          # Application
│   ├── OrderService.php
│   └── CustomerService.php
├── Entity/                           # Domain (s Doctrine anotacemi → leak)
│   ├── Order.php
│   ├── OrderLine.php
│   └── Customer.php
├── Repository/                       # Infrastructure
│   ├── OrderRepository.php
│   └── CustomerRepository.php
└── Form/                             # Presentation (vstupy)
    └── OrderType.php
:::

Tato struktura je výchozí *Symfony skeleton*: `make:entity`, `make:controller` a `make:repository` ji generují automaticky. Pro junior tým je dobře čitelná – každý soubor má své místo, a přidání nového use casu je triviální (controller + service + entity + repository).

### Příklad doménové entity ve vrstveném DDD {#layered-priklad-heading}

:::code{language="php" filename="src/Entity/Order.php" highlights="9,10,11,18,19,20,21,22,23,24,25,26"}
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status = 'draft';

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderLine::class, cascade: ['persist'])]
    private Collection $lines;

    public function confirm(): void
    {
        if ($this->status !== 'draft') {
            throw new \DomainException('Only draft orders can be confirmed.');
        }
        if ($this->lines->isEmpty()) {
            throw new \DomainException('Cannot confirm an empty order.');
        }
        $this->status = 'confirmed';
    }

    public function cancel(): void
    {
        if ($this->status === 'shipped') {
            throw new \DomainException('Cannot cancel a shipped order.');
        }
        $this->status = 'cancelled';
    }
}
:::

Třída `Order` má bohaté chování (`confirm()`, `cancel()`) a kontroluje invarianty – to je kvalitní DDD modelování. Ale třída zároveň **závisí na Doctrine ORM** přes atributy `#[ORM\Entity]`, `#[ORM\Column]`. Doménové pravidlo „nelze potvrdit prázdnou objednávku“ je definováno v doménovém kódu, ale zároveň ten kód *ví*, že se ukládá přes Doctrine. Z pohledu **Hexagonal/Onion architektury** je to *domain leak* – doménová vrstva potřebuje knihovnu z Infrastructure, aby se vůbec dala zkompilovat. Pragmatický pohled (Layered, který tu rozebíráme) tento kompromis přijímá; Hexagonal trvá na separaci přes [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern).

### Kdy se Layered hodí {#layered-kdy-heading}

- **Junior tým a rychlý start** – Symfony skeleton, `make:*` commandy, předvídatelná struktura.
- **Aplikace s 10–50 endpointy** – kde investice do izolace nepřinese měřitelný přínos.
- **Krátký horizont produktu** (MVP, prototyp, interní nástroj) – kde Doctrine vendor lock-in není riziko, protože migrace nikdy nepřijde.
- **Tým, který Symfony ovládá plynně** – kde dodatečná složitost by jen brzdila, aniž by řešila reálný problém.

### Kdy Layered přestává stačit {#layered-kdy-ne-heading}

- **Doménový model vyžaduje testy bez databáze** – testy přes Doctrine fixtures jsou pomalé a křehké.
- **Plánujete vyměnit perzistentní vrstvu** (např. PostgreSQL → DynamoDB, nebo Doctrine → manuální SQL) – Doctrine anotace na entitách jsou pak masivní migrace.
- **Doménová pravidla potřebují žít v jednom místě** – ve vrstveném modelu se rozptýlí mezi controllery, service vrstvou a entity třídami.
- **Aplikace má více vstupních kanálů** (HTTP API, CLI, message queue, GraphQL) – Application Service psaný kolem HTTP Request objektu se na CLI vstup hodí špatně.

### Typický Layered controller v Symfony {#layered-controller-heading}

Pro úplnost ukázka, jak vypadá orchestrační kód v Layered architektuře. Controller volá Application Service, ten načte Doctrine entitu z repository, zavolá doménovou metodu a flushne změny. Žádné porty, žádné DTO mappery, žádné explicitní rozhraní mezi vrstvami.

:::code{language="php" filename="src/Controller/OrderController.php"}
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderService $service,
    ) {
    }

    #[Route('/orders/{id}/confirm', methods: ['POST'])]
    public function confirm(string $id): JsonResponse
    {
        $order = $this->repository->find($id);
        if ($order === null) {
            throw $this->createNotFoundException("Order {$id} not found.");
        }

        $this->service->confirm($order);

        return new JsonResponse(['status' => $order->getStatus()]);
    }
}
:::

Tento kód je čitelný, krátký a v Symfony idiomu standardní. Cena je v testech: pro test `OrderController::confirm()` potřebujete buď `WebTestCase` s celým bootem aplikace, nebo komplikovaný setup s mockováním `OrderRepository` i `OrderService`. V Hexagonal struktuře byste místo toho jen zavolali use case bez controlleru.

:::callout{type="warn"}
### Anti-vzor: Anemic Domain Model {#layered-anti-heading}

Klasické riziko Layered architektury je, že *Entity* degeneruje do pouhé struktury pro Doctrine – getry, setry, žádná logika. Logika se přesune do *Service* vrstvy, kde se tvoří gigantické `OrderService` třídy s desítkami metod. Martin Fowler nazval tento anti-vzor [Anemic Domain Model](https://martinfowler.com/bliki/AnemicDomainModel.html) už v roce 2003 a v DDD literatuře je to univerzálně považováno za něco, čemu se vyhýbat. Detail v kapitole [Anti-vzory](/anti-vzory).

Příznak: třída `Order` má jen `$status`, `setStatus()`, `getStatus()`, ale nikde není kontrola, zda přechod ze stavu „draft“ do „confirmed“ je validní. Místo toho v `OrderService::confirmOrder()` stojí: `if ($order->getStatus() !== 'draft') { throw …; } $order->setStatus('confirmed');`. Z modelu se stala databázová tabulka v PHP.
:::

## 09.03 Hexagonal Architecture (Ports & Adapters, Cockburn 2005) {#hexagonal}

Alistair Cockburn publikoval *Hexagonal Architecture (Ports and Adapters)* v roce 2005 [[3]](https://alistair.cockburn.us/hexagonal-architecture/) jako reakci na klasickou tří-vrstvou strukturu (UI / Logic / Database), kde testy aplikační logiky nutně procházely buď přes UI nebo přes databázi. Cockburnova teze: **aplikační jádro (doména) komunikuje s vnějším světem výhradně přes dobře definované porty (rozhraní)**; konkrétní technologie (HTTP, SQL, e-mail, fronta zpráv) tyto porty implementují jako adaptéry.

Geometrická metafora hexagonu (šestiúhelníku) je pouze grafická pomůcka – Cockburn původně chtěl ukázat, že kolem jádra je víc než dvě strany (UI nahoře, DB dole), že portů může být libovolný počet. Číslo „šest“ nemá žádný význam; stejně dobře by mohl být osmiúhelník, desetiúhelník nebo trojúhelník.

### Dva typy portů {#hexagonal-typy-portu-heading}

- **Driving (Inbound, Primary) port** – to, co aplikace *umí*. Definuje, jak vnější svět volá doménu. V DDD termínech to odpovídá *Application Service* nebo *Use Case* rozhraní. Příklad: `PlaceOrder`, `CancelOrder`, `GetOrderHistory`.
- **Driven (Outbound, Secondary) port** – to, co aplikace *potřebuje*. Definuje rozhraní pro externí závislosti. V DDD jsou to repository rozhraní, brány na externí systémy, publishery doménových událostí. Příklad: `OrderRepository`, `EmailSender`, `EventPublisher`.

Adaptéry implementují porty: **Driving adaptér** (Symfony Controller, CLI Command, Messenger Handler) volá inbound port; **Driven adaptér** (Doctrine Repository, SMTP Mailer, RabbitMQ publisher) implementuje outbound port. Doména samotná nezná žádný adaptér ani konkrétní technologii.

### Symfony struktura podle Hexagonal {#hexagonal-symfony-heading}

:::code{language="bash" filename="src/ (Symfony Hexagonal struktura)"}
src/
├── Ordering/                           # Bounded Context
│   ├── Domain/                         # Doménové jádro (žádné framework deps)
│   │   ├── Model/
│   │   │   ├── Order.php               # Aggregate Root – ČISTÉ PHP
│   │   │   ├── OrderLine.php
│   │   │   └── OrderId.php             # Value Object
│   │   ├── Event/
│   │   │   └── OrderConfirmed.php
│   │   └── Port/                       # Outbound porty (interfaces)
│   │       ├── OrderRepository.php
│   │       └── EventPublisher.php
│   ├── Application/                    # Inbound porty + use casy
│   │   ├── UseCase/
│   │   │   ├── PlaceOrder.php          # Inbound port (interface)
│   │   │   └── PlaceOrderHandler.php   # Implementace use casu
│   │   └── Dto/
│   │       └── PlaceOrderInput.php
│   └── Infrastructure/                 # Adaptéry (driving + driven)
│       ├── Http/                       # Driving adapter
│       │   └── PlaceOrderController.php
│       ├── Cli/                        # Driving adapter
│       │   └── PlaceOrderCommand.php
│       └── Persistence/                # Driven adapter
│           ├── DoctrineOrderRepository.php
│           └── OrderOrmEntity.php      # Mapper na databázi
└── Shared/
    └── Domain/
        └── DomainException.php
:::

Z této struktury plyne několik věcí:

- Adresář `Domain/` neobsahuje *žádný* import z Doctrine, Symfony, Twig ani jiné knihovny. Pouze čisté PHP a vlastní typy.
- Repository rozhraní (`OrderRepository`) žije v `Domain/Port/`; jeho implementace (`DoctrineOrderRepository`) žije v `Infrastructure/Persistence/`. Doména závisí na rozhraní, infrastruktura ho implementuje.
- Doménová entita (`Order`) **není Doctrine entita**. K mapování slouží samostatná `OrderOrmEntity` + mapper (vzor [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern)) – doména zůstává čistá. *Pozn.: Hexagonal Architecture trvá na této separaci. Pragmatičtější přístup, který zbytek průvodce používá jako výchozí, atributy přímo na agregátu připouští – viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading).*
- Vstup do aplikace prochází přes *inbound port* (`PlaceOrder`). HTTP Controller a CLI Command nezávisí na doméně přímo, ale na tomto portu.

### Příklad: Outbound port a jeho adaptér {#hexagonal-priklad-heading}

:::code{language="php" filename="src/Ordering/Domain/Port/OrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Port;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Model\OrderId;

interface OrderRepository
{
    public function get(OrderId $id): ?Order;

    public function save(Order $order): void;

    /**
     * @return list<Order>
     */
    public function findByCustomer(string $customerId): array;
}
:::

:::code{language="php" filename="src/Ordering/Infrastructure/Persistence/DoctrineOrderRepository.php" highlights="13,14,15,16,17,18,19,24,25"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Persistence;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Model\OrderId;
use App\Ordering\Domain\Port\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderMapper $mapper,
    ) {
    }

    public function get(OrderId $id): ?Order
    {
        $orm = $this->em->find(OrderOrmEntity::class, $id->toString());

        return $orm === null ? null : $this->mapper->toDomain($orm);
    }

    public function save(Order $order): void
    {
        $orm = $this->mapper->toOrm($order);
        $this->em->persist($orm);
        $this->em->flush();
    }

    /**
     * @return list<Order>
     */
    public function findByCustomer(string $customerId): array
    {
        $rows = $this->em->getRepository(OrderOrmEntity::class)
            ->findBy(['customerId' => $customerId]);

        return array_map(fn (OrderOrmEntity $r) => $this->mapper->toDomain($r), $rows);
    }
}
:::

Doménová třída `Order` nemá žádné Doctrine anotace – je to čisté PHP. `OrderOrmEntity` je samostatná persistenční třída s Doctrine mapováním a `OrderMapper` překlápí mezi nimi. Cena: dvojí třída a explicitní mapování. Zisk: doménový model je testovatelný v paměti bez databáze, lze ho serializovat do JSON Event Storu beze změny tvaru, a změna persistence vrstvy nezasáhne doménu.

### Příklad: Inbound port a jeho HTTP adapter {#hexagonal-inbound-heading}

Driving (inbound) port definuje, co aplikace umí. V DDD termínech je to kontrakt Application Service. V Symfony 8 se zpravidla mapuje na CQRS Command/Query handler dispatchovaný přes Messenger Bus. Port lze také definovat explicitně jako interface, který má jediný handler jako implementaci.

:::code{language="php" filename="src/Ordering/Application/UseCase/PlaceOrder.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\UseCase;

use App\Ordering\Application\Dto\PlaceOrderInput;
use App\Ordering\Application\Dto\PlaceOrderOutput;

/**
 * Inbound port (driving) – kontrakt aplikační schopnosti
 * „umístit objednávku". HTTP adaptér, CLI command i testy
 * volají přes tento port; konkrétní implementace je v handleru.
 */
interface PlaceOrder
{
    public function handle(PlaceOrderInput $input): PlaceOrderOutput;
}
:::

HTTP adapter pak nezná konkrétní třídu handleru – zná jen rozhraní portu, a Symfony DI ho automaticky napojí na implementaci. Tím získáte schopnost handler v testech vyměnit za fake bez celé aplikační vrstvy.

:::code{language="php" filename="src/Ordering/Infrastructure/Http/PlaceOrderController.php" highlights="13,14,15,16"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Http;

use App\Ordering\Application\Dto\PlaceOrderInput;
use App\Ordering\Application\UseCase\PlaceOrder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlaceOrderController
{
    public function __construct(
        private readonly PlaceOrder $useCase,
    ) {
    }

    #[Route('/api/orders', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $input = new PlaceOrderInput(
            customerId: $payload['customerId'],
            items: $payload['items'],
        );

        $output = $this->useCase->handle($input);

        return new JsonResponse([
            'orderId' => $output->orderId,
            'status' => $output->status,
        ], 201);
    }
}
:::

### Symfony Service Container a auto-wiring {#hexagonal-symfony-di-heading}

Symfony Dependency Injection automaticky binduje rozhraní na implementaci, pokud je *jen jedna* implementace daného rozhraní. Pokud je víc (např. `InMemoryOrderRepository` pro testy a `DoctrineOrderRepository` pro produkci), explicitně určíte mapování v `config/services.yaml`:

:::code{language="yaml" filename="config/services.yaml" highlights="10,11"}
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'

    # Explicitní binding portu na výchozí adaptér
    App\Ordering\Domain\Port\OrderRepository:
        alias: App\Ordering\Infrastructure\Persistence\DoctrineOrderRepository

    # Pro testy lze přepsat v config/services_test.yaml
:::

Alternativa s atributem `#[Autowire]` přímo v konstruktoru use casu:

:::code{language="php" filename="src/Ordering/Application/UseCase/PlaceOrderHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\UseCase;

use App\Ordering\Domain\Port\OrderRepository;
use App\Ordering\Infrastructure\Persistence\DoctrineOrderRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class PlaceOrderHandler implements PlaceOrder
{
    public function __construct(
        #[Autowire(service: DoctrineOrderRepository::class)]
        private readonly OrderRepository $orders,
    ) {
    }
}
:::

### Druhý port: publisher doménových událostí {#hexagonal-event-port-heading}

Repository je nejviditelnější, ale ne jediný outbound port. Druhým častým kandidátem je publikace doménových událostí. Doména volá `EventPublisher::publish($event)` a nestará se, kdo eventy konzumuje. Možnosti: Symfony Messenger, RabbitMQ, in-memory dispatcher pro testy, nebo nikdo (event bus může být no-op v jednoduchých scénářích).

:::code{language="php" filename="src/Ordering/Domain/Port/EventPublisher.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Port;

use App\Ordering\Domain\Event\DomainEvent;

interface EventPublisher
{
    public function publish(DomainEvent $event): void;

    /**
     * @param iterable<DomainEvent> $events
     */
    public function publishAll(iterable $events): void;
}
:::

:::code{language="php" filename="src/Ordering/Infrastructure/Messaging/MessengerEventPublisher.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Messaging;

use App\Ordering\Domain\Event\DomainEvent;
use App\Ordering\Domain\Port\EventPublisher;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventPublisher implements EventPublisher
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function publish(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }

    public function publishAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
:::

Pro testy si napíšete `InMemoryEventPublisher`, který eventy pouze sbírá do pole a umožní v testu zkontrolovat, jaké eventy doména publikovala. Žádné Symfony Messenger, žádný RabbitMQ, žádná infrastruktura. Test běží v 5 milisekundách místo 500.

:::code{language="php" filename="tests/Ordering/Doubles/InMemoryEventPublisher.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Doubles;

use App\Ordering\Domain\Event\DomainEvent;
use App\Ordering\Domain\Port\EventPublisher;

final class InMemoryEventPublisher implements EventPublisher
{
    /** @var list<DomainEvent> */
    private array $published = [];

    public function publish(DomainEvent $event): void
    {
        $this->published[] = $event;
    }

    public function publishAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }

    /**
     * @return list<DomainEvent>
     */
    public function published(): array
    {
        return $this->published;
    }
}
:::

:::diagram{fig="09.3-A" title="Čtyři architektonické styly aplikované na DDD" src="images/diagrams/13_architectural_styles/hexagonal_vs_onion.svg"}
:::

### Kdy se Hexagonal hodí {#hexagonal-kdy-heading}

- **Doména s bohatým chováním** – kde se vyplatí investovat do testů domény bez databáze.
- **Více vstupních kanálů** – HTTP API, CLI, Messenger consumer, GraphQL – všechny jsou jen jiné driving adaptéry nad stejným inbound portem.
- **Plánovaná výměna technologie** – migrace z Doctrine ORM na DBAL nebo na cloudovou databázi se omezí na nový adaptér.
- **Aplikace s 50–500 endpointy** – kde overhead zavedení portů je amortizovaný počtem use casů.

### Kdy Hexagonal nedává smysl {#hexagonal-kdy-ne-heading}

- **CRUD nad několika tabulkami** – port + adaptér + mapper pro každou entitu je over-engineering bez návratnosti.
- **Tým neumí Dependency Injection** – Hexagonal stojí na principu inverze závislostí; bez něj je struktura jen kosmetická.
- **Krátký horizont produktu** – investice do izolace se nezaplatí, pokud projekt zanikne za rok.

:::callout{type="warn"}
### Anti-vzor: Anemic Hexagonal {#hexagonal-anti-heading}

Tým přečte Cockburnův článek, vytvoří krásnou strukturu s `Domain/Port/`, `Application/UseCase/`, `Infrastructure/Adapter/` … ale doménová třída je pořád `$status: string` + setry, a všechna logika sedí v handleru. Hexagonal bez DDD modelování je jen hodně rituálu kolem prázdné domény.

Druhý častý anti-vzor: **port = repository, ostatní jsou jen služby**. Tým definuje jen `OrderRepository` jako port, ale e-mailový mailer, externí HTTP klient a publisher událostí žijí v `App\Service\` bez rozhraní. Doména pak má závislost na *konkrétní* implementaci e-mailového maileru, což porušuje princip Hexagonal stejně jako Doctrine anotace na entitě.
:::

## 09.04 Onion Architecture (Palermo 2008) {#onion}

Jeffrey Palermo publikoval *Onion Architecture* ve čtyřech blogových postech v roce 2008 [[4]](https://jeffreypalermo.com/2008/07/the-onion-architecture-part-1/) jako vylepšení vrstvené architektury, které explicitně staví doménový model do středu a uvádí **Dependency Rule**: závislosti smí směřovat pouze *dovnitř*, nikdy ven. Geometrickou metaforou je cibule (onion) s koncentrickými prstenci.

### Čtyři koncentrické vrstvy Onion {#onion-vrstvy-heading}

1. **Domain Model (jádro)** – entity, hodnotové objekty, agregáty, doménové události. Žádné závislosti. Žádný framework. Žádná persistence.
2. **Domain Services** – bezstavové třídy s doménovou logikou, která nepatří do žádné konkrétní entity. Závisí jen na Domain Model.
3. **Application Services** – orchestrace use casů, transakce, mapování DTO. Závisí na Domain Services a Domain Model.
4. **UI / Infrastructure** – controllery, repository implementace, externí brány. Vnější vrstva závisí na Application Services.

Podstatné je slovo **koncentrické**. Vrstvy nejsou vertikálně poskládané (nahoře UI, dole DB), ale soustředné – jádro uprostřed, vnější svět kolem. To řeší jeden problém klasické vrstvené architektury: ve vrstvené struktuře může Domain záviset na Infrastructure (čte z databáze), v Onion to není dovoleno. Repozitáře jsou definovány jako rozhraní v jádře a implementovány v UI/Infrastructure vrstvě.

### Rozdíl proti Hexagonal {#onion-vs-hexagonal-heading}

Onion a Hexagonal mají stejnou základní myšlenku – izolovat doménu, závislosti dovnitř – a v běžné implementaci jsou v Symfony nerozlišitelné. Tři jemné odlišnosti:

- **Onion explicitně rozlišuje Domain Services a Application Services** jako dvě samostatné vrstvy. Hexagonal je topologicky střídmější – port + adaptér, žádné vnitřní vrstvení.
- **Onion popisuje vrstvy**, Hexagonal popisuje porty a adaptéry. Onion je „statický“ model (kdo na koho závisí), Hexagonal je „dynamický“ (kudy data tečou).
- **Onion neodděluje driving a driven porty.** V Onion je v UI vrstvě i HTTP controller (driving) i Doctrine repository (driven), což je z pohledu Hexagonal nepřesné – driving adaptér *volá* aplikaci, driven adaptér *je volán* doménou.

Pokud váš projekt používá Hexagonal slovník (port, adapter, driving, driven), ale uvnitř má dvě vrstvy služeb (Domain Service, Application Service), děláte de facto hybrid Hexagonal+Onion. To je v pořádku – málokdo dnes implementuje jeden styl „čistě“.

### Příklad: Domain Service vs. Application Service {#onion-priklad-heading}

Domain Service obsahuje *doménovou logiku*, která nepatří do agregátu (typicky kvůli tomu, že pracuje s víc agregáty najednou nebo vyžaduje data, která agregát nemá k dispozici). Application Service je *orchestrátor* – řídí transakci, načítá agregáty z repository, volá doménovou logiku a publikuje výstupy.

:::code{language="php" filename="src/Pricing/Domain/Service/PriceCalculator.php"}
<?php

declare(strict_types=1);

namespace App\Pricing\Domain\Service;

use App\Pricing\Domain\Model\Cart;
use App\Pricing\Domain\Model\Customer;
use App\Pricing\Domain\Model\DiscountPolicy;
use App\Pricing\Domain\Model\Money;

/**
 * Domain Service – výpočet ceny vyžaduje data z více agregátů
 * (Cart, Customer, DiscountPolicy). Logika je čistě doménová,
 * žádný framework, žádná persistence.
 */
final class PriceCalculator
{
    public function calculate(
        Cart $cart,
        Customer $customer,
        DiscountPolicy $policy,
    ): Money {
        $subtotal = $cart->subtotal();
        $discount = $policy->applyTo($subtotal, $customer->loyaltyTier());
        $vat = $subtotal->subtract($discount)->multiply(0.21);

        return $subtotal->subtract($discount)->add($vat);
    }
}
:::

:::code{language="php" filename="src/Pricing/Application/Service/CalculateCartPrice.php" highlights="16,17,18,19,20,21,22,23,24"}
<?php

declare(strict_types=1);

namespace App\Pricing\Application\Service;

use App\Pricing\Domain\Port\CartRepository;
use App\Pricing\Domain\Port\CustomerRepository;
use App\Pricing\Domain\Port\DiscountPolicyRepository;
use App\Pricing\Domain\Service\PriceCalculator;
use App\Shared\Domain\Money;

/**
 * Application Service – orchestrace use casu „Spočítej cenu košíku".
 * Vlastní logika je v Domain Service; aplikační vrstva jen řídí transakci
 * a načítá agregáty z repository.
 */
final class CalculateCartPrice
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly CustomerRepository $customers,
        private readonly DiscountPolicyRepository $policies,
        private readonly PriceCalculator $calculator,
    ) {
    }

    public function execute(string $cartId): Money
    {
        $cart = $this->carts->get($cartId)
            ?? throw new \DomainException("Cart {$cartId} not found.");

        $customer = $this->customers->get($cart->customerId())
            ?? throw new \DomainException("Customer not found.");

        $policy = $this->policies->forCustomer($customer);

        return $this->calculator->calculate($cart, $customer, $policy);
    }
}
:::

Rozdíl: `PriceCalculator` nezná repository – bere si *již načtené* objekty. `CalculateCartPrice` zná repository (přes porty) – orchestruje načtení a předání dat. Pokud byste obě zodpovědnosti slili do jedné třídy, ztratíte schopnost testovat výpočet ceny izolovaně, bez databáze.

### Onion struktura v Symfony {#onion-symfony-heading}

:::code{language="bash" filename="src/ (Symfony Onion struktura)"}
src/
├── Pricing/                            # Bounded Context
│   ├── Domain/                         # Vnitřní prsten (jádro)
│   │   ├── Model/
│   │   │   ├── Cart.php
│   │   │   ├── Customer.php
│   │   │   └── DiscountPolicy.php
│   │   ├── Port/                       # Repository interfaces
│   │   │   ├── CartRepository.php
│   │   │   ├── CustomerRepository.php
│   │   │   └── DiscountPolicyRepository.php
│   │   └── Service/                    # 2. prsten – Domain Services
│   │       └── PriceCalculator.php
│   ├── Application/                    # 3. prsten – Application Services
│   │   └── Service/
│   │       ├── CalculateCartPrice.php
│   │       └── ApplyCouponToCart.php
│   └── Infrastructure/                 # Vnější prsten – UI a infra
│       ├── Persistence/
│       │   └── DoctrineCartRepository.php
│       └── Http/
│           └── CartPriceController.php
└── Shared/
    └── Domain/
        └── Money.php
:::

Symfony auto-wiring funguje pro Onion stejně jako pro Hexagonal – Application Service závisí na Domain Service a portech, vnější HTTP adapter závisí na Application Service. Žádná třída v `Domain/` nepoužívá `use Symfony\…` ani `use Doctrine\…`; jediné `use` v jádře jsou na vlastní třídy z `Domain/`.

### Kdy se Onion hodí {#onion-kdy-heading}

- **Domény s mnoha doménovými službami** – pricing engine, risk scoring, tax calculation, kde hodně logiky pracuje s víc agregáty najednou.
- **Týmy, které mají rády explicitní vrstvení** – Onion má jasné jméno pro každou vrstvu a každá závislost se dá zkontrolovat statickou analýzou.
- **Enterprise aplikace s 100+ use casy** – kde rozdělení Domain Services a Application Services brání monolitickým „God service“ třídám.

### Kdy Onion nedává smysl {#onion-kdy-ne-heading}

- **Doména má málo doménových služeb** – pak je Onion zbytečně složitý a Hexagonal stačí.
- **Mladší tým** – rozdíl mezi Domain Service a Application Service není intuitivní a chybné rozhodnutí způsobí silně provázané kostky.

## 09.05 Clean Architecture (Robert C. Martin 2012) {#clean}

Robert C. Martin (známý pod přezdívkou „Uncle Bob“) publikoval *Clean Architecture* v roce 2012 jako blogový post [[5]](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html) a v roce 2017 ji rozvinul do stejnojmenné knihy. Jeho cílem bylo zobecnit společné rysy Hexagonal, Onion, DCI a BCE (Boundary-Control-Entity od Ivara Jacobsona) do jednoho srozumitelného modelu.

### Čtyři prsteny Clean Architecture {#clean-prsteny-heading}

1. **Entities** – doménové objekty s nejhlubšími invarianty. Odpovídá DDD agregátům a hodnotovým objektům. Nezávisí na ničem.
2. **Use Cases** – obchodní pravidla specifická pro aplikaci. Každý use case je třída s jednou public metodou (`execute()` nebo `handle()`). Závisí jen na Entities.
3. **Interface Adapters** – Controllers (pro vstup), Presenters (pro výstup), Gateways (pro outbound). Překlápějí mezi formátem use casu a formátem vnějšího světa.
4. **Frameworks & Drivers** – Symfony, Doctrine, HTTP klienty, databázové ovladače. Vnější prsten, kde žije všechno framework-specifické.

**Dependency Rule**: zdrojový kód směřuje jen směrem dovnitř. Vnější vrstva může citovat třídy z vnitřní vrstvy, ale nikdy naopak. Pokud vnitřní vrstva potřebuje něco z vnější (např. uložit objednávku), použije *Dependency Inversion* – definuje rozhraní v sobě, které vnější vrstva implementuje.

### Co Clean přidává proti Onion a Hexagonal {#clean-co-pridava-heading}

Hexagonal a Onion nepojmenovávají jednotlivé use casy explicitně – Hexagonal mluví o „inbound portech“, Onion o „Application Services“. Clean Architecture povyšuje use case na **prvotřídní koncept**: každý use case je jedna třída s jednou metodou a vlastním Request/Response DTO. Tím se aplikace stává explicitním seznamem schopností, které poskytuje.

V DDD termínech: Use Case z Clean Architecture ≈ DDD Application Service ≈ CQRS Command Handler. Pokud používáte Symfony Messenger pro Command Bus (viz kapitolu [CQRS](/cqrs)), váš `PlaceOrderHandler` de facto plní roli Clean Use Case.

### Příklad: Use Case s Request/Response DTO {#clean-priklad-heading}

:::code{language="php" filename="src/Ordering/UseCase/PlaceOrder/PlaceOrderRequest.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\UseCase\PlaceOrder;

/**
 * Request DTO – vstup do use casu, framework-agnostický.
 * Žádné Symfony Request, žádné Doctrine entity, žádné HTTP detaily.
 */
final readonly class PlaceOrderRequest
{
    /**
     * @param list<array{productId: string, quantity: int}> $items
     */
    public function __construct(
        public string $customerId,
        public array $items,
        public string $shippingAddress,
    ) {
    }
}
:::

:::code{language="php" filename="src/Ordering/UseCase/PlaceOrder/PlaceOrderResponse.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\UseCase\PlaceOrder;

/**
 * Response DTO – výstup z use casu. Žádné view, žádný JSON.
 * Adaptér (Controller, CLI Command) si zformátuje výstup sám.
 */
final readonly class PlaceOrderResponse
{
    public function __construct(
        public string $orderId,
        public string $status,
        public int $totalAmount,
    ) {
    }
}
:::

:::code{language="php" filename="src/Ordering/UseCase/PlaceOrder/PlaceOrderUseCase.php" highlights="13,22,23,41"}
<?php

declare(strict_types=1);

namespace App\Ordering\UseCase\PlaceOrder;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Model\OrderId;
use App\Ordering\Domain\Port\CustomerRepository;
use App\Ordering\Domain\Port\OrderRepository;
use App\Shared\Domain\EventPublisher;

final class PlaceOrderUseCase
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CustomerRepository $customers,
        private readonly EventPublisher $events,
    ) {
    }

    public function execute(PlaceOrderRequest $request): PlaceOrderResponse
    {
        $customer = $this->customers->get($request->customerId)
            ?? throw new \DomainException('Customer not found.');

        $order = Order::place(
            OrderId::generate(),
            $customer,
            $request->items,
            $request->shippingAddress,
        );

        $this->orders->save($order);

        foreach ($order->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return new PlaceOrderResponse(
            orderId: $order->id()->toString(),
            status: $order->status(),
            totalAmount: $order->totalAmount()->toMinorUnits(),
        );
    }
}
:::

Use Case `PlaceOrderUseCase` je *jediný vstupní bod* pro tuto aplikační schopnost. Ať už ho zavolá HTTP Controller, CLI Command, Messenger Handler, GraphQL Resolver nebo testovací suite – všichni používají stejný kontrakt: `PlaceOrderRequest` dovnitř, `PlaceOrderResponse` ven.

### Adaptér: Symfony HTTP Controller jako Interface Adapter {#clean-controller-heading}

:::code{language="php" filename="src/Ordering/Infrastructure/Http/PlaceOrderController.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Http;

use App\Ordering\UseCase\PlaceOrder\PlaceOrderRequest;
use App\Ordering\UseCase\PlaceOrder\PlaceOrderUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlaceOrderController
{
    public function __construct(
        private readonly PlaceOrderUseCase $useCase,
    ) {
    }

    #[Route('/api/orders', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $useCaseRequest = new PlaceOrderRequest(
            customerId: $payload['customerId'],
            items: $payload['items'],
            shippingAddress: $payload['shippingAddress'],
        );

        $response = $this->useCase->execute($useCaseRequest);

        return new JsonResponse([
            'orderId' => $response->orderId,
            'status' => $response->status,
            'totalAmount' => $response->totalAmount,
        ], 201);
    }
}
:::

Controller dělá přesně tři věci: dekóduje HTTP vstup do `PlaceOrderRequest`, zavolá use case, zformátuje výstup zpět do JSON. Žádná doménová logika, žádné rozhodování. Stejný use case lze obsloužit z CLI commandu pár řádky kódu, ze Symfony Messengeru jako CommandHandler, nebo zavolat přímo z PHPUnit testu bez celého frameworku.

### Kdy se Clean hodí {#clean-kdy-heading}

- **Aplikace s explicitním seznamem use casů** – kde má každá schopnost svoje jméno a kontrakt (např. ERP systémy, finanční aplikace).
- **Více vstupních kanálů** – HTTP API, CLI, Messenger, GraphQL – všechny sdílejí stejné use casy.
- **Tým s vyšší zkušeností** – kde dodatečné vrstvení a DTO ping-pong nezpomalí vývoj.
- **Aplikace, kde je důležitý audit „co aplikace umí“** – Use Case třídy jsou tím seznamem.

### Kdy Clean nedává smysl {#clean-kdy-ne-heading}

- **Malá Symfony aplikace s ~30 endpointy** – DTO ping-pong v Clean (Request → Domain → Response) představuje významnou režii.
- **Tým bez zkušenosti s Dependency Injection** – Clean stojí na Dependency Inversion ještě silněji než Hexagonal.
- **Doména je velmi tenká** – Use Case v Clean kolem prázdné domény je jen vrstvení rituálu.

:::callout{type="pattern"}
### Vztah Clean Use Case ↔ CQRS Command Handler {#clean-pattern-heading}

Pokud znáte CQRS pattern (kapitola [CQRS](/cqrs)), všimnete si, že Use Case v Clean v zásadě odpovídá CQRS Command Handleru:

- `PlaceOrderRequest` ≈ Command DTO
- `PlaceOrderResponse` ≈ Command Result (často void nebo ID)
- `PlaceOrderUseCase::execute()` ≈ `PlaceOrderHandler::__invoke()`
- Symfony Messenger Bus ≈ „interactor“ routing v Clean

V Symfony 8 projektu, kde používáte Symfony Messenger jako Command Bus, máte tedy Clean Architecture „zadarmo“ – stačí Use Case přejmenovat na `*Handler` a Request na `*Command`. Řada DDD projektů funguje jako kombinace *Hexagonal + CQRS + Clean Use Cases* v jednom hybridním stylu.
:::

## 09.06 Vertical Slice Architecture (a horizontální vs. vertikální dělení) {#vertical-slice}

Jimmy Bogard popsal *Vertical Slice Architecture* v roce 2018 [[6]](https://www.jimmybogard.com/vertical-slice-architecture/) jako reakci na náklady vrstvových architektur. Jeden jednoduchý use case se rozprostírá přes 5–7 souborů (Controller, Service, Domain Service, Repository interface, Repository impl, DTO, Mapper). Změna jediné funkce vyžaduje úpravy ve všech sedmi.

Vertical Slice Architecture organizuje kód **podle feature, ne podle vrstvy**. Každá feature dostane svůj adresář, ve kterém žije všechno potřebné: Command/Query, Handler, Validátor, Read Model, Controller. Slice je kompletní vertikální „sloupec“ přes všechny technické vrstvy aplikace. To je v ostrém kontrastu s tradičním vrstveným (horizontálním) přístupem, kde se kód člení podle technické odpovědnosti (Controller / Service / Repository / Entity), a jeden use case se rozprostírá napříč všemi vrstvami.

### Horizontální dělení – tradiční vrstvený přístup {#horizontalni-deleni}

V tradičním vrstveném DDD je projekt organizovaný **podle technických vrstev**. Každá vrstva má svůj adresář, soubory podobného typu žijí spolu. Typický `src/`:

:::code{language="bash" filename="src/ (tradiční DDD struktura)"}
src/
├── Presentation/                # Prezentační vrstva
│   └── Controller/UserController.php
├── Application/                 # Aplikační vrstva
│   ├── Service/UserService.php
│   └── DTO/UserDTO.php
├── Domain/                      # Doménová vrstva
│   ├── Model/User.php
│   ├── Repository/UserRepository.php
│   └── Service/DomainUserService.php
└── Infrastructure/              # Infrastrukturní vrstva
    ├── Repository/DoctrineUserRepository.php
    └── Persistence/Doctrine/Mapping/User.orm.xml
:::

Vrstvy jsou organizovány horizontálně. Každá vrstva poskytuje služby vrstvě nad ní. Doménové stavební kameny (entity, hodnotové objekty, agregáty, doménové služby) jsou stejné jako u jakéhokoli jiného architektonického stylu.

### Vertikální dělení – Vertical Slice {#vertikalni-deleni}

Vertikální slice organizuje kód **podle feature**. Každá funkce (registrace uživatele, vytvoření objednávky, generování faktury) má svůj adresář, který obsahuje všechny vrstvy potřebné pro svou implementaci. Sdílený doménový model zůstává v `{BC}/Domain/`, ale aplikační, prezentační a infrastrukturní logika je rozdělená per feature.

:::code{language="bash" filename="src/ (Vertical Slice struktura)"}
src/
├── UserManagement/             # Bounded Context
│   ├── Domain/                 # Sdílený doménový model BC
│   │   ├── Model/User.php
│   │   ├── ValueObject/{UserId, Email}.php
│   │   ├── Event/UserRegistered.php
│   │   └── Repository/UserRepository.php
│   ├── Infrastructure/         # Sdílená infrastruktura BC
│   │   └── Repository/DoctrineUserRepository.php
│   ├── Registration/           # Feature: Registrace
│   │   ├── Command/{RegisterUser, RegisterUserHandler}.php
│   │   └── Controller/RegistrationController.php
│   └── Profile/                # Feature: Profil
│       ├── Query/{GetUserProfile, GetUserProfileHandler}.php
│       ├── Controller/ProfileController.php
│       └── ViewModel/UserProfileViewModel.php
└── Shared/Domain/Exception/DomainException.php
:::

Tento přístup minimalizuje vazby mezi jednotlivými funkcemi a maximalizuje vazby uvnitř funkce [[7]](https://www.youtube.com/watch?v=SUiWfhAhgQw). Zároveň zachovává principy DDD – respektuje Bounded Contexts a sdílený doménový model.

:::callout{type="note"}
### Konvence struktury v této knize {#konvence-heading}

Většina příkladů v knize používá vertikální slice s těmito konvencemi:

- `{BC}/Domain/` – doménová vrstva sdílená uvnitř Bounded Contextu (Model, ValueObject, Event, Repository rozhraní, Service).
- `{BC}/Infrastructure/` – infrastrukturní implementace (Doctrine repozitáře, event bus adaptéry).
- `{BC}/{Feature}/` – feature slice s `Command/`, `Query/`, `Controller/` přímo uvnitř.
- `Shared/` – pouze skutečně sdílené komponenty (abstraktní typy, výjimky, bus rozhraní).
:::

### Co Vertical Slice mění {#vs-rozdil-heading}

- **Adresářová struktura** – místo `Controller/, Service/, Domain/, Infrastructure/` máte `Ordering/PlaceOrder/, Ordering/CancelOrder/, Ordering/GetOrderHistory/`.
- **Závislosti mezi feature** jsou minimální – každá feature je téměř samostatná. Sdílí se jen agregáty, hodnotové objekty a sběrnice (event bus, command bus).
- **Diff jedné feature** sedí v jednom adresáři. Code review se zjednoduší – recenzent vidí celý use case na jednom místě.
- **Akceptační test** může pokrýt celý slice najednou (HTTP request → response), aniž by bylo nutné mockovat sedm vrstev.

### Srovnání horizontálního a vertikálního dělení {#srovnani-deleni}

| Aspekt | Horizontální (vrstvený) | Vertikální slice |
|---|---|---|
| **Organizace kódu** | Podle technických vrstev | Podle funkcí (features) |
| **Vazby** | Silné mezi vrstvami | Silné uvnitř funkce, slabé mezi funkcemi |
| **Změna jednoho use casu** | Úpravy v 5–7 souborech napříč vrstvami | Úpravy v jednom adresáři |
| **Testovatelnost** | Vyžaduje více mocků (vrstvy mezi sebou) | Méně mocků, závislosti jsou lokální |
| **Škálovatelnost na microservices** | Vyžaduje přeorganizování všech vrstev | Feature lze přesunout jako celek |
| **Pochopení na začátku** | Jednodušší (tradičnější) | Vyžaduje pochopení slice jako jednotky |
| **Vhodnost pro CQRS** | CQRS vyžaduje dodatečnou práci | Přirozeně podporuje CQRS [[8]](https://docs.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/apply-simplified-microservice-cqrs-ddd-patterns) |

### Kdy zvolit který přístup {#kdy-vs}

**Horizontální (vrstvený) přístup** se vyplatí, když:

- Tým má dlouhou zkušenost s vrstvenou architekturou a CQRS není v plánu.
- Aplikace má 10–30 endpointů a malou doménovou složitost.
- Doménový model má silně sdílené invarianty napříč více funkcemi, které je třeba jednotně vymáhat.
- Preference týmu je explicitní oddělení technických vrstev před organizací podle funkcí.

**Vertikální slice** se vyplatí, když:

- Aplikace má 50+ funkcí s nezávislými use casy.
- Tým plánuje CQRS nebo je už zavedlo (Symfony Messenger jako Command/Query Bus).
- Aplikace bude v budoucnu rozdělena do mikroslužeb – feature jako celek se snadněji extrahuje.
- Preferujete rychlou iteraci s minimální koordinací mezi vrstvami.

### Vertical Slice a Hexagonal jsou ortogonální {#vs-vs-hexagonal-heading}

Hexagonal/Onion/Clean popisují *jak strukturovat závislosti uvnitř jedné feature*. Vertical Slice popisuje *jak organizovat feature mezi sebou*. Tyto dva přístupy lze kombinovat: každý vertikální slice může uvnitř používat Hexagonal port-adapter strukturu (slice má vlastní Port, vlastní Adapter, vlastní Domain Service). Nebo nemusí – některé slice jsou tak triviální, že stačí jediná třída.

Kombinace **Hexagonal + Vertical Slice** je v současných Symfony projektech rozšířenou výchozí volbou. Bounded Context má sdílený doménový model (agregáty, value objekty, repository interfaces), ale aplikační vrstva je rozdělená do feature slice. Každý slice má svůj Command/Handler (nebo Query/Handler) a svůj HTTP Controller. Tato kombinace dává vyvážený poměr testovatelnosti, organizace a srozumitelnosti pro tým.

## 09.07 Praktické srovnání – co si vybrat v Symfony 8 {#srovnani}

Žádný styl není univerzálně lepší. Volba závisí na velikosti aplikace, zkušenosti týmu, plánovaném horizontu produktu a tom, kolik se vyplatí investovat do izolace. Následující rozhodovací matice shrnuje typická kritéria a směruje na vhodný styl.

:::diagram{fig="09.7-A" title="Layered vs. Hexagonal vs. Onion vs. Clean - vrstvy a směr závislostí" src="images/diagrams/13_architectural_styles/styles_comparison.svg"}
:::

| Faktor | Layered | Hexagonal | Onion | Clean | Vertical Slice |
|---|---|---|---|---|---|
| **Křivka učení** | nízká | střední | střední | vysoká | nízká |
| **Junior friendly** | ✓✓✓ | ✓ | ✓ | ✗ | ✓✓ |
| **Test isolation domény** | nízká | vysoká | vysoká | vysoká | střední |
| **Doctrine integrace** | tight (anotace na entity) | loose (přes adapter) | loose | loose | flexibilní (per slice) |
| **Více vstupních kanálů** | náročné (duplicita) | přirozené | přirozené | přirozené | přirozené |
| **Boilerplate (DTO, mappery)** | nízký | střední | střední | vysoký | nízký |
| **Doporučená velikost projektu** | < 50 endpointů | 50–500 | 100+ | enterprise (200+) | 50–500 |
| **CQRS přirozenost** | vyžaduje úpravy | vysoká (port = command bus) | střední | vysoká (use case = handler) | velmi vysoká |
| **Refactoring jedné feature** | 5–7 souborů | 4–6 souborů | 5–7 souborů | 6–8 souborů | 1 adresář |

### Doporučená výchozí volba pro Symfony 8 {#srovnani-vyber-heading}

Pro středně velký projekt vychází jako výchozí volba:

**Hexagonal + Vertical Slice s CQRS přes Symfony Messenger.**

Konkrétně: Bounded Context má vlastní adresář (`src/Ordering/`). Uvnitř `Domain/` obsahuje agregáty, hodnotové objekty a repository *interfaces* (porty); `Infrastructure/` obsahuje Doctrine adaptéry. Každá feature má svůj slice (`PlaceOrder/`, `CancelOrder/`) s Command/Query, Handler (= Clean Use Case) a HTTP Controller. Tato kombinace nabízí:

- **Doménové testy bez databáze** – agregáty jsou čisté PHP, mockují se jen porty.
- **Jednoduché code review** – diff jedné feature je v jednom adresáři.
- **CLI/HTTP/Messenger paritu** – Symfony Messenger Bus dispatchuje stejný Command z libovolného adaptéru.
- **Symfony idiomatičnost** – Messenger je prvotřídní komponenta, není nutné psát vlastní bus.

Tato volba není univerzální pravda. Pokud váš projekt má 20 endpointů a jde o interní administrativní aplikaci s desetiletým horizontem, obyčejná Layered struktura ze Symfony skeletu stačí a pravděpodobně iteruje rychleji. Pokud je váš projekt enterprise CRM s 500+ use casy a 15 vývojáři, Clean Architecture s explicitním Use Case katalogem se vyplatí.

:::callout{type="pattern"}
### Tři otázky před výběrem stylu {#srovnani-rozhodnuti-heading}

1. **Kolik bude use casů za rok?** Pokud < 30, Layered. Pokud 50–500, Hexagonal/Vertical Slice. Pokud 200+, Clean nebo hybrid.
2. **Vyplatí se izolovat doménu od Doctrine?** Pokud chcete testy bez databáze nebo plánujete migraci persistence vrstvy, ANO → Hexagonal+. Pokud Doctrine zůstane navždy a testy přes fixtures jsou OK, NE → Layered stačí.
3. **Kolik vstupních kanálů má aplikace?** Pokud jen HTTP, Layered je v pořádku. Pokud HTTP + CLI + Messenger + GraphQL, Hexagonal/Clean se výrazně vyplatí.
:::

## 09.08 Hybridní přístup – Hexagonal core, Layered okraje {#hybrid}

Realistické projekty zřídka používají jediný styl pro celou kódovou bázi. Mnohem častěji se vyplatí **diferencovat investici podle typu subdomény**. Core Domain dostane plný Hexagonal s čistými agregáty a porty. Supporting subdoména si vystačí s Layered DDD se zjednodušeným modelováním. Generic subdoména je tenký adaptér na externí SaaS. Tento přístup je v souladu s tím, co Eric Evans doporučuje v knize *DDD*: investujte modelovací úsilí *tam, kde přináší konkurenční výhodu*, ne všude stejně.

Detail klasifikace subdomén (Core / Supporting / Generic) je v kapitole [Subdomény: Core, Supporting, Generic](/subdomeny). Následuje ukázka, jak hybridní přístup vypadá ve struktuře Symfony projektu.

### Příklad: e-shop s diferencovanou architekturou {#hybrid-priklad-heading}

:::code{language="bash" filename="src/ (hybridní rozložení e-shopu)"}
src/
├── Ordering/                           # CORE DOMAIN – plný Hexagonal
│   ├── Domain/
│   │   ├── Model/                      # Bohatý agregát Order
│   │   │   ├── Order.php
│   │   │   ├── OrderLine.php
│   │   │   └── OrderId.php
│   │   ├── Event/
│   │   │   ├── OrderPlaced.php
│   │   │   └── OrderConfirmed.php
│   │   └── Port/                       # Porty (interfaces)
│   │       ├── OrderRepository.php
│   │       └── EventPublisher.php
│   ├── Application/
│   │   └── UseCase/
│   │       ├── PlaceOrder/
│   │       │   ├── PlaceOrderCommand.php
│   │       │   └── PlaceOrderHandler.php
│   │       └── CancelOrder/
│   │           ├── CancelOrderCommand.php
│   │           └── CancelOrderHandler.php
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── DoctrineOrderRepository.php
│       │   └── OrderOrmEntity.php      # Persistence-friendly mapping
│       └── Http/
│           └── PlaceOrderController.php
│
├── Customer/                           # SUPPORTING – Layered DDD
│   ├── Controller/                     # Symfony skeleton struktura
│   │   └── CustomerController.php
│   ├── Service/
│   │   └── CustomerService.php
│   ├── Entity/                         # Doctrine entity přímo
│   │   └── Customer.php
│   └── Repository/
│       └── CustomerRepository.php
│
├── Notifications/                      # GENERIC – tenký adapter na SaaS
│   ├── Service/
│   │   └── NotificationService.php
│   └── Provider/
│       ├── SendGridAdapter.php         # Wrap kolem externí HTTP API
│       └── TwilioAdapter.php
│
└── Shared/                             # Sdílené koncepty mezi BC
    ├── Domain/
    │   ├── Money.php
    │   └── DomainException.php
    └── Bus/
        ├── CommandBus.php              # Interface
        └── EventBus.php
:::

### Pravidla hybridního přístupu {#hybrid-pravidla-heading}

- **Core Domain** dostává plný Hexagonal, Vertical Slice a CQRS. Sem teče modelovací úsilí, sem teče čas na refaktoring, sem teče investice do testů.
- **Supporting subdomény** mají Layered strukturu – controller, service, entity, repository. Dostatečně dobré, rychlé k napsání, čitelné.
- **Generic subdomény** jsou tenké adaptéry. Žádné agregáty, žádné domain services – jen wrap kolem externí knihovny nebo SaaS API.
- **Nemíchejte styly uvnitř jednoho Bounded Contextu.** Jeden BC = jeden styl. Hybrid znamená „různé BC mají různé styly“, ne „jeden BC má polovinu Hexagonal a polovinu Layered“.

### Cena vs. zisk hybridního přístupu {#hybrid-cena-zisk-heading}

Cena: tým musí umět víc stylů a vědět, kdy který použít. Junior to nezvládne – musíte mít aspoň jednoho seniora, který architekturu hlídá. Mezi BC jsou *nutně* rozdílné konvence, což může čtenáře kódu mást.

Zisk: nejvyšší ROI z modelovacího úsilí. V Core Domain (kde projekt vyhrává konkurenční bitvu) máte čistý model a rychlé testy. V Generic části (kde vendor lock-in není problém, protože SaaS si stejně neměníte každý měsíc) ušetříte stovky hodin nepotřebné izolace.

:::callout{type="pattern"}
### Vzor: Differentiated Investment {#hybrid-pattern-heading}

Vaughn Vernon v *Implementing Domain-Driven Design* (2013) [[7]](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577) nazývá tento vzor *differentiated investment*: 80 % modelovací investice teče do 20 % kódové báze (Core Domain). Zbylých 80 % kódu si vystačí s pragmatickou strukturou. To přímo odpovídá Pareto principu a v praxi vede k nejlepšímu poměru kvalita / čas.

Hybridní přístup je tedy nejen pragmatický, ale i **doporučený** autoritami DDD literatury. Tlak na „jednotnou architekturu všude“ jde proti tomuto principu – ne každá část projektu si zaslouží stejnou investici.
:::

## 09.09 Anti-vzory napříč styly {#antivzory}

Většina problémů s architektonickými styly nepramení ze špatné volby stylu, ale ze špatné implementace. Následuje šest nejčastějších anti-vzorů, které se v reálných Symfony projektech opakují.

### Anti-vzor 1: Hexagonal kult {#anti-1-heading}

Tým přečte Cockburnův článek a každý CRUD endpoint dostane port + adapter. `GET /api/products/{id}` má port `FindProductById`, adapter `FindProductByIdHttpAdapter`, repository port `ProductRepository`, adapter `DoctrineProductRepository`, mapper `ProductMapper` a use case `FindProductByIdUseCase`. Pro nejtriviálnější operaci máte sedm souborů místo dvou.

**Náprava:** Hexagonal aplikujte jen tam, kde je doménová logika. Pro čisté CRUD endpointy (žádné invarianty, žádné stavové přechody, žádné doménové pravidlo) stačí přímý Doctrine query v controlleru. Architektonický styl není povinnost – je to nástroj, který se používá, když přináší hodnotu.

### Anti-vzor 2: Domain leakage přes Doctrine anotace {#anti-2-heading}

Klasický Layered problém přenesený do Hexagonal: tým má `Domain/Port/OrderRepository`, ale třída `Domain/Model/Order.php` má `#[ORM\Entity]`, `#[ORM\Column]`, `#[ORM\OneToMany]`. Doména stále závisí na Doctrine knihovně. Cíl izolace padá.

**Náprava (pro Hexagonal/Onion):** zaveďte separátní persistenční třídu (`OrderOrmEntity`) a Mapper – vzor [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern). Cena je dvojí třída a explicitní mapping – zisk je čistá doména. Pokud projekt Hexagonal hranici reálně nepotřebuje, atributy přímo na agregátu jsou pragmatický kompromis (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)).

### Anti-vzor 3: Anemic Hexagonal / Anemic Clean {#anti-3-heading}

Strukturálně dokonalý Hexagonal, ale doménové třídy jsou anémické – getry, setry, žádná logika. Veškerá logika sedí v handlerech a service vrstvě. Hexagonal/Clean bez DDD modelování jsou jen vrstvení rituálu kolem prázdné domény.

**Náprava:** Před zavedením architektonického stylu zkontrolujte, zda váš doménový model má skutečné chování. Pokud ne, vyřešte nejprve modelování – zavedení Hexagonal nad anémickým modelem nepřinese izolaci, jen zkomplikuje code review.

### Anti-vzor 4: Port jen pro Repository {#anti-4-heading}

Tým definuje jen `OrderRepository` jako port (interface v doméně, implementace v infrastructure). Ostatní výstupní závislosti – e-mail mailer, externí HTTP klient, publisher událostí – žijí v `App\Service\` bez rozhraní. Doména pak má závislost na *konkrétní* implementaci e-mailového maileru, což porušuje princip Hexagonal stejně jako Doctrine anotace.

**Náprava:** Každá výstupní závislost domény dostane port. `EmailSender`, `EventPublisher`, `PaymentGateway` – všechno jsou interfaces v `Domain/Port/`, a infrastructure je implementuje.

### Anti-vzor 5: Premature inverze závislostí {#anti-5-heading}

Tým si přečte „Dependency Inversion Principle“ a začne otáčet závislosti i tam, kde to nemá smysl. Vznikají abstraktní interfaces, které mají jednu jedinou implementaci a nejsou nikdy mock-ované. Čtení kódu se zhoršuje („musím skočit do interface a pak najít implementaci“), aniž by to přineslo testovatelnost.

**Náprava:** Inverze závislostí má cenu jen tam, kde existuje aspoň jeden ze dvou důvodů: (1) chcete v testech mockovat tu závislost, (2) plánujete víc implementací (Doctrine + InMemory, SendGrid + Twilio). Pokud ani jeden, interface je zbytečný.

### Anti-vzor 6: Architecture astronaut (astronaut architektury) {#anti-6-heading}

Tým investuje měsíce do „dokonalé architektury“ – osmivrstvová Clean s explicitními BCE rolemi, formálními use case katalogy, presenter třídami, gateway hierarchiemi. Koncový uživatel pořád čeká na první funkci. Architektura se stala cílem, ne nástrojem.

**Náprava:** *Architektura je investice, ne dekorace.* Každá vrstva, každý pattern, každá abstrakce musí mít konkrétní zisk pro projekt. Pokud nedokážete za pět minut vysvětlit, jaký reálný problém daná abstrakce řeší, pravděpodobně neřeší žádný a měla by se odstranit.

Detail dalších anti-vzorů (Anemic Domain Model, God Service, Smart UI, Leaky Abstractions) je v samostatné kapitole [Anti-vzory a typické chyby](/anti-vzory).

## 09.10 Symfony 8 specifika všech stylů {#symfony-specifika}

Bez ohledu na to, který styl zvolíte, v Symfony 8 budete pracovat se stejnou sadou nástrojů: Service Container, Messenger, Doctrine, Form, Security. Liší se pouze konvence, jak je v projektu používat. Následují tři praktické tipy, které platí pro všechny architektonické styly.

### Bundle vs. namespace organizace {#symfony-bundles-heading}

Symfony historicky stavěl na bundlech jako jednotce modularity. V Symfony 8 se v aplikačním kódu doporučuje **bundly nepoužívat** a místo toho strukturovat `src/` přímo přes namespacy. Bundle se hodí jen pro znovupoužitelné knihovny publikované jako Composer packages, ne pro aplikační moduly. Pravidlo platí pro všechny architektonické styly – bundly nepřinášejí žádnou výhodu, kterou by neposkytovaly namespacy + auto-wiring.

### Konfigurace per-context v Symfony 8 {#symfony-config-heading}

Pokud máte víc Bounded Contexts (Ordering, Billing, Customer, …), můžete pro každý mít vlastní YAML konfiguraci v `config/packages/contexts/`. To je užitečné zejména v hybridním přístupu, kde různé BC mají různé úrovně izolace. Příklad: jen Core Domain BC má explicitní binding portů, ostatní BC spoléhají na auto-wiring.

:::code{language="yaml" filename="config/services.yaml" highlights="13,14,15,16,17"}
# config/services.yaml
imports:
    - { resource: 'packages/contexts/ordering.yaml' }
    - { resource: 'packages/contexts/billing.yaml' }
    - { resource: 'packages/contexts/customer.yaml' }

services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/Kernel.php'
            - '../src/**/Domain/Model/'        # Doménové modely nejsou služby
            - '../src/**/Domain/Event/'        # Události také ne
            - '../src/**/Application/Dto/'     # DTO také ne
:::

Doménové modely **vylučte z auto-registrace v Service Containeru**. Doménové entity, hodnotové objekty a doménové eventy *nejsou služby* – jsou to data. Pokud je necháte registrovat jako služby, riskujete, že Symfony do nich zkusí injektovat závislosti, což porušuje DDD pravidla.

### Symfony Messenger jako Command Bus {#symfony-messenger-heading}

Pro všechny styly kromě Layered je Symfony Messenger vhodný nástroj pro implementaci Command Bus a Event Bus pattern. V Hexagonal a Clean Architecture každý use case dispatchujete jako Command, handler je váš inbound adaptér nebo přímo use case. Konfigurace per-bus:

:::code{language="yaml" filename="config/packages/messenger.yaml"}
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction
            query.bus:
                default_middleware:
                    allow_no_handlers: false
                    allow_no_senders: false
            event.bus:
                default_middleware:
                    allow_no_handlers: true   # Eventy mohou mít 0+ konzumentů

        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            App\Ordering\Domain\Event\OrderConfirmed: async
            App\Ordering\Domain\Event\OrderCancelled: async
:::

Tři sběrnice (command, query, event) jsou doporučená praxe v CQRS-friendly DDD aplikaci. Detail konfigurace Messengeru pro DDD je v kapitole [CQRS](/cqrs) a v kapitole [Implementace v Symfony 8](/implementace-v-symfony).

## 09.11 Shrnutí {#summary}

- **Architektonický styl ≠ DDD.** DDD je modelovací technika; architektonický styl je rozhodnutí o uspořádání kódu. Lze je kombinovat libovolně – DDD funguje v Layered, Hexagonal, Onion, Clean i Vertical Slice.
- **Čtyři vrstvové styly mají stejnou základní myšlenku – izolaci domény – ale jiný slovník a jinou granularitu.** Hexagonal mluví o portech a adaptérech, Onion o koncentrických vrstvách, Clean o use casech jako prvotřídním konceptu. V praxi se často kombinují do jednoho hybridního stylu.
- **Vertical Slice je ortogonální k vrstvovým stylům.** Popisuje, jak organizovat feature mezi sebou, ne jak strukturovat závislosti uvnitř feature. Hexagonal + Vertical Slice + CQRS je rozšířená výchozí volba v Symfony 8 projektech.
- **Hybridní přístup (různé styly pro různé subdomény) je nejen pragmatický, ale i doporučený autoritami DDD literatury.** Investujte modelovací úsilí do Core Domain; Supporting a Generic si vystačí s jednodušší strukturou. Architektura je investice, ne dekorace.

:::faq{}
- question: Hexagonal vs. Onion – jaký je praktický rozdíl?
  answer: 'V běžné Symfony implementaci jsou téměř nerozlišitelné: oba mají interfaces v doméně, implementace v infrastruktuře, závislosti směřují dovnitř. Tři jemné odlišnosti: Hexagonal explicitně rozlišuje driving (inbound) a driven (outbound) porty; Onion rozlišuje Domain Services a Application Services jako dvě samostatné vrstvy; Onion je „statický“ model závislostí, Hexagonal „dynamický“ model toku dat. Pokud váš projekt používá Hexagonal slovník (port, adapter), ale uvnitř má Domain Service i Application Service, děláte v podstatě hybrid – což je v pořádku. Detail v <a href="#onion">sekci o Onion Architecture</a>.'
- question: Můžu použít Hexagonal bez DDD?
  answer: 'Ano, technicky to funguje. Hexagonal řeší <em>jak strukturovat závislosti</em>, DDD řeší <em>jak modelovat doménu</em> – jsou ortogonální. Můžete mít Hexagonal nad anémickým CRUD modelem a žádné DDD principy nepoužívat. Praktický zisk je ale omezený: bez bohatého doménového modelu uvnitř je Hexagonal jen vrstvení rituálu, které zhoršuje code review a zpomaluje vývoj. Anti-vzor „Anemic Hexagonal“ je v reálných projektech běžný. Detail v <a href="#anti-3-heading">anti-vzorech</a>.'
- question: Jak migrovat z Layered na Hexagonal v existujícím Symfony projektu?
  answer: 'Strangler Fig pattern: nezačínejte velký rewrite, ale postupně. Vyberte jeden Bounded Context (ideálně Core Domain) a v něm jednu feature. Pro tu feature zaveďte port (interface v Domain/Port/) a adapter (implementace v Infrastructure/), původní Doctrine entitu rozdělte na čistou doménovou třídu + persistenční OrmEntity + Mapper. Otestujte. Iterujte na další feature. Pokud Core Domain doženete celý, druhý BC možná stačí ponechat v Layered (hybridní přístup). Nikdy nemigrujte všechno najednou – riziko regresí je vysoké. Detail strangler fig v kapitole <a href="/migrace-z-crud">Migrace z CRUD</a>.'
- question: Co je „Port“ přesně a jak se liší od běžného PHP interface?
  answer: 'Port je interface s explicitní architektonickou rolí: definuje hranici mezi doménou a vnějším světem. Technicky je to běžný PHP <code>interface</code>, ale konvenčně žije v adresáři <code>Domain/Port/</code>, nemá framework závislosti a má smysluplné jméno z domain language (<code>OrderRepository</code>, ne <code>OrderRepositoryInterface</code>). Cockburn rozlišuje driving porty (vnější svět volá doménu) a driven porty (doména volá vnější svět). V Symfony auto-wiringu je port automaticky napojen na svou jedinou implementaci, nebo můžete explicitně mapovat v services.yaml. Detail v <a href="#hexagonal">sekci o Hexagonal</a>.'
- question: Vyplatí se Clean Architecture v malé Symfony aplikaci?
  answer: 'Spíše ne. Clean Architecture vyžaduje DTO ping-pong (Request DTO → Use Case → Response DTO → Adapter překládá zpět), je významný overhead – pro každou funkci tři až čtyři další třídy. V malé aplikaci s 20–30 endpointy je to čistá ztráta. Vyplatí se až v aplikacích s explicitním seznamem use casů (200+ schopností), kde je důležitá auditability „co aplikace umí“ a kde je víc vstupních kanálů (HTTP + CLI + Messenger + GraphQL). Pro malou Symfony aplikaci stačí Layered nebo Hexagonal s méně rituálem. Detail v <a href="#srovnani">rozhodovací matici</a>.'
- question: Jak Vertical Slice zapadá mezi Hexagonal/Onion/Clean?
  answer: 'Vertical Slice je ortogonální k vrstvovým stylům. Hexagonal/Onion/Clean popisují <em>jak strukturovat závislosti uvnitř jedné feature</em>; Vertical Slice popisuje <em>jak organizovat feature mezi sebou</em>. Tyto dvě dimenze lze kombinovat: každý vertikální slice může uvnitř používat Hexagonal port-adapter strukturu, nebo nemusí. V moderních Symfony projektech je rozšířená kombinace <strong>Hexagonal + Vertical Slice + CQRS přes Symfony Messenger</strong> – Bounded Context má sdílený doménový model, ale aplikační vrstva je rozdělená do feature slice. Detail Vertical Slice v <a href="/vertikalni-slice">samostatné kapitole</a>.'
:::

## 09.12 Další četba a citace {#further-reading}

1. Eric Evans – [*Domain-Driven Design: Tackling Complexity in the Heart of Software*](https://www.domainlanguage.com/ddd/) (2003). Originální definice DDD a doporučení layered architecture.
2. Martin Fowler – [*Patterns of Enterprise Application Architecture*](https://martinfowler.com/eaaCatalog/) (2002). Service Layer, Domain Model, Data Mapper a další foundational patterns.
3. Alistair Cockburn – [*Hexagonal Architecture (Ports and Adapters)*](https://alistair.cockburn.us/hexagonal-architecture/) (2005). Originální článek o Hexagonal architektuře.
4. Jeffrey Palermo – [*The Onion Architecture: Part 1*](https://jeffreypalermo.com/2008/07/the-onion-architecture-part-1/) (2008). První ze čtyř blogových postů zavádějících Onion model.
5. Robert C. Martin – [*The Clean Architecture*](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html) (2012). Original Clean Architecture article zobecňující Hexagonal a Onion.
6. Jimmy Bogard – [*Vertical Slice Architecture*](https://www.jimmybogard.com/vertical-slice-architecture/) (2018). Feature-first přístup k organizaci kódu.
7. Vaughn Vernon – [*Implementing Domain-Driven Design*](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577) (2013). Praktický průvodce DDD s ukázkami architektonických stylů.
8. Herberto Graça – [*DDD, Hexagonal, Onion, Clean, CQRS, … How I put it all together*](https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together/) (2017). Hybridní pohled na kombinaci stylů.
9. Martin Fowler – [*Anemic Domain Model*](https://martinfowler.com/bliki/AnemicDomainModel.html) (2003). Klasický článek popisující anti-vzor anémického modelu.
