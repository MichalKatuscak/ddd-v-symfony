---
route: architectural_styles
path: /architektonicke-styly
title: Architektonické styly – Hexagonal, Onion, Clean
page_title: "Hexagonal vs. Onion vs. Clean Architecture | DDD Symfony"
meta_description: "Layered, Hexagonal, Onion nebo Clean Architecture? Kdy který styl volit v DDD se Symfony a jaký to má dopad na strukturu projektu."
meta_keywords: "Hexagonal Architecture, Ports and Adapters, Onion Architecture, Clean Architecture, Layered Architecture, Vertical Slice, DDD, Symfony, Cockburn, Palermo, Martin, Dependency Rule"
og_type: article
published: "2026-04-29"
modified: 2026-09-24
breadcrumb_name: Architektonické styly
schema_type: TechArticle
schema_headline: "Architektonické styly: Hexagonal, Onion, Clean – co si vybrat"
chapter_number: "09"
category: Architektura
deck: "DDD vám říká <em>co</em> modelovat. Architektonický styl říká <em>kam</em> to modelované strčit. Čtyři školy – klasická vrstvená, Hexagonální (Cockburn), Onion (Palermo), Clean (Martin) – a Vertical Slice jako pátá. Kapitola srovnává jejich odlišnosti, podobnosti a co vybrat v Symfony 8 projektu."
reading_time: 44
difficulty: 3
github_examples: null
---

Když tým řekne „přejdeme na DDD“, obvykle tím myslí dvě věci: *budeme líp modelovat doménu* a *přerovnáme adresářovou strukturu*. Obě rozhodnutí jsou ve skutečnosti **ortogonální**, tedy na sobě nezávislá. Domain-Driven Design je modelovací technika; architektonický styl určuje uspořádání kódu a směr závislostí. DDD funguje ve vrstvené architektuře, v Hexagonální, Onion, Clean i ve Vertical Slice. Platí to i obráceně: hexagonální architektura postavená nad anémickým CRUD modelem nemá s DDD nic společného.

Kapitola srovnává čtyři vrstvové styly (Layered, Hexagonal, Onion, Clean) s pátým, feature-orientovaným Vertical Slice a ukazuje, jak každý vypadá v Symfony 8 projektu. Vítěze nevyhlašuje, každý styl má kontext, ve kterém se vyplatí. Cílem je dát vám rozhodovací kritéria a ukázat anti-vzory, které z dobré teorie dělají špatný kód.

## 09.01 Proč architektonický styl není totéž co DDD {#proc-styl}

Nejčastější zdroj zmatku v DDD literatuře je směšování dvou nezávislých rozhodnutí. První je **modelovací technika**: budeme používat agregáty, hodnotové objekty, doménové události, ubiquitous language a Bounded Contexts? Nebo zůstaneme u procedurálního CRUDu, kde controller čte z databáze, aplikuje validaci a zapíše zpět? Druhé je **uspořádání kódu**: členit projekt podle technických vrstev, přes porty a adaptéry, do koncentrických prstenců, nebo podle feature?

Obě rozhodnutí lze kombinovat libovolně. Najdete projekty s čistým CRUD modelem v Hexagonální architektuře (porty oddělují HTTP od databáze, ale uvnitř je anémický řádek tabulky). Najdete bohaté DDD agregáty v klasické vrstvené struktuře (Doctrine entity v adresáři `src/Entity`, ale s metodami jako `$order->confirm()`, `$order->cancel()` a invarianty kontrolovanými v konstruktoru). Architektonický styl ovlivňuje *testovatelnost a kompozici*; na modelovací metodu nesahá.

:::callout{type="note"}
### Dvě ortogonální osy rozhodnutí

Při návrhu projektu pomáhá držet obě otázky odděleně:

- **Modelovací osa** – od CRUD/Transaction Script přes Anemic Domain Model k bohatému DDD modelu (agregáty, value objekty, doménové události, invarianty).
- **Strukturální osa** – od jednoduché Layered struktury přes Hexagonal/Onion/Clean s explicitní inverzí závislostí až po Vertical Slice s feature-first organizací.

Posun po jedné ose neznamená posun po druhé. *Špatně modelovanou doménu* samotný přechod na Hexagonal nespraví. *Špatnou izolaci od infrastruktury* zase nevyřeší DDD, pokud entity zůstanou pevně provázané s Doctrine.
:::

Eric Evans v původní knize *Domain-Driven Design* (2003) [[1]](https://www.domainlanguage.com/ddd/) věnuje vrstvené architektuře („layered architecture“) jen jednu krátkou kapitolu. Těžiště knihy leží v modelování. Vrstvy v ní model chrání před technickými detaily, cílem samy o sobě nejsou. Pozdější autoři (Vernon, Khononov, Millett & Tune) ukazují DDD ve stylech vrstvových, hexagonálních i feature-first. Všechny fungují, pokud má doménový model uvnitř skutečný obsah.

Triviální doméně (CRUD nad několika tabulkami, žádné invarianty, žádné stavové přechody) žádný architektonický styl nepomůže, protože není co chránit. Bohatá doména, kterou neoddělíte od frameworku (přímá volání `EntityManageru`, Symfony Request/Response objekty, externí HTTP klienti), dá kód, který na první pohled vypadá „čistě“. Bez celé infrastruktury se ale testovat nedá.

Styly následují od nejjednoduššího ke složitějším. U každého: co říká, jak vypadá v Symfony, kdy se hodí, kdy ne a jaký je nejčastější anti-vzor.

## 09.02 Layered (klasická vrstvená) {#layered}

Martin Fowler v *Patterns of Enterprise Application Architecture* (2002) [[2]](https://martinfowler.com/eaaCatalog/) pracuje se třemi hlavními vrstvami: Presentation, Domain (doménová logika) a Data Source. Eric Evans v *Domain-Driven Design* (2003) schéma upravil na čtyři vrstvy: User Interface (Presentation), Application, Domain a Infrastructure. Přidal pravidlo, že **vrstva smí záviset jen na vrstvách pod sebou**, nikdy nahoru. Pozdější DDD literatura toto rozdělení převzala.

### Čtyři standardní vrstvy {#layered-vrstvy-heading}

- **Presentation Layer** – interakce se světem (HTTP controllery, CLI commandy, GraphQL resolvery). V Symfony to jsou třídy v `src/Controller/`.
- **Application Layer** – orchestrace use casů, transakce, mapování DTO. Tenké třídy, žádná doménová logika; ta žije v doméně. V Symfony bývají v `src/Service/` nebo `src/Application/`.
- **Domain Layer** – agregáty, entity, hodnotové objekty, doménové služby, repository *rozhraní*. Žádné framework závislosti. V Symfony obvykle `src/Entity/` + `src/Domain/`.
- **Infrastructure Layer** – Doctrine repository implementace, e-mail brány, HTTP klienti, Messenger transporty. V Symfony `src/Repository/` + `src/Infrastructure/`.

Struktury dál v kapitole pracují jen se třemi adresáři (`Domain/`, `Application/`, `Infrastructure/`) a controllery řadí do infrastruktury. Tak vrstvy pro PHP popsal Matthias Noback a konvence se ujala. Z Evansovy čtveřice tím splývá UI s infrastrukturou; pravidlo o směru závislostí zůstává beze změny.

### Typická Symfony struktura {#layered-symfony-heading}

:::code{language="bash" filename="src/ (Symfony Layered konvence)"}
src/
├── Controller/                      # Presentation
│   ├── OrderController.php
│   └── CustomerController.php
├── Service/                          # Application
│   ├── OrderService.php
│   └── CustomerService.php
├── Entity/                           # Domain (s Doctrine atributy → leak)
│   ├── Order.php
│   ├── OrderLine.php
│   └── Customer.php
├── Repository/                       # Infrastructure
│   ├── OrderRepository.php
│   └── CustomerRepository.php
└── Form/                             # Presentation (vstupy)
    └── OrderType.php
:::

Adresáře `Controller/`, `Entity/` a `Repository/` odpovídají výchozímu *Symfony skeletonu*. Plní je MakerBundle: `make:controller` vytvoří controller, `make:entity` entitu i její repository, `make:form` formulář. Junior tým se ve struktuře vyzná bez vysvětlování. Každý soubor má své místo a nový use case znamená controller, service, entitu a repository.

### Příklad doménové entity ve vrstveném DDD {#layered-priklad-heading}

:::code{language="php" filename="src/Entity/Order.php" highlights="9,10,11,18,19,20,21,22,23,24,25,26"}
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
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

    public function __construct(string $id)
    {
        $this->id = $id;
        // Bez inicializace skončí první dotaz na kolekci hláškou
        // "Typed property must not be accessed before initialization".
        $this->lines = new ArrayCollection();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function status(): string
    {
        return $this->status;
    }

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

Třída `Order` má bohaté chování (`confirm()`, `cancel()`) a kontroluje invarianty. To je kvalitní DDD modelování. Zároveň ale **závisí na Doctrine ORM** přes atributy `#[ORM\Entity]` a `#[ORM\Column]`. Pravidlo „nelze potvrdit prázdnou objednávku“ žije v doménovém kódu, který přitom *ví*, že se ukládá přes Doctrine. Z pohledu **Hexagonal/Onion architektury** je to *domain leak*: doménová třída importuje knihovnu z Infrastructure a nese její metadata. Pragmatický pohled (Layered, který tu rozebíráme) tento kompromis přijímá. Je to i výchozí volba knihy, jak rozebírá [Implementace v Symfony](/implementace-v-symfony). Hexagonal trvá na separaci přes [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern). Ve stejném duchu tu stojí i holá `\DomainException`. Zbytek knihy používá pojmenované výjimky jako `InvalidOrderStateTransitionException`; Layered ukázka zůstává u zkratky, aby bylo vidět, co styl skutečně vyžaduje a co ne.

### Kdy se Layered hodí {#layered-kdy-heading}

Layered se vyplatí tam, kde je předvídatelnost cennější než izolace. Juniornímu týmu dá Symfony skeleton a `make:*` commandy strukturu, kterou nemusí vymýšlet. U aplikace s deseti až padesáti endpointy se investice do portů a adaptérů nevrátí. Má-li produkt krátký horizont (MVP, prototyp, interní nástroj), je vendor lock-in na Doctrine teoretické riziko, protože migrace nikdy nepřijde. Týmu, který Symfony ovládá plynně, by dodatečná vrstva jen zdržovala práci a žádný jeho skutečný problém by neřešila.

### Kdy Layered přestává stačit {#layered-kdy-ne-heading}

- **Doménový model vyžaduje testy bez databáze.** Testy přes Doctrine fixtures jsou pomalé a křehké.
- **Plánujete vyměnit perzistentní vrstvu** (např. PostgreSQL → DynamoDB, nebo Doctrine → manuální SQL). Vyjmout Doctrine atributy z entit pak znamená rozsáhlou migraci.
- **Doménová pravidla potřebují žít na jednom místě.** Ve vrstveném modelu mají sklon rozptýlit se mezi controllery, service vrstvu a entity.
- **Aplikace má více vstupních kanálů** (HTTP API, CLI, message queue, GraphQL). Application Service psaný kolem HTTP Request objektu se na CLI vstup hodí špatně.

### Typický Layered controller v Symfony {#layered-controller-heading}

Orchestrační kód v Layered architektuře: controller načte Doctrine entitu z repository a předá ji Application Service, která zavolá doménovou metodu a flushne změny. Žádné porty, DTO mappery ani explicitní rozhraní mezi vrstvami.

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

        return new JsonResponse(['status' => $order->status()]);
    }
}
:::

Kód je čitelný, krátký a pro Symfony typický. Cenu zaplatíte v testech. `OrderController::confirm()` otestujete buď přes `WebTestCase` s celým bootem aplikace, nebo s mocky `OrderRepository` i `OrderService`. V Hexagonal struktuře stačí zavolat use case bez controlleru.

:::callout{type="warn"}
### Anti-vzor: Anemic Domain Model {#layered-anti-heading}

Typické riziko Layered architektury: *Entity* zdegeneruje na strukturu pro Doctrine s gettery a settery bez logiky. Logika se přestěhuje do *Service* vrstvy a vznikají obří třídy `OrderService` s desítkami metod. Martin Fowler tento anti-vzor popularizoval pod jménem [Anemic Domain Model](https://martinfowler.com/bliki/AnemicDomainModel.html) už v roce 2003 a DDD literatura se v jeho odmítnutí shoduje. Detail v kapitole [Anti-vzory](/anti-vzory).

Příznak: třída `Order` má jen `$status`, `setStatus()`, `getStatus()`, ale nikde není kontrola, zda přechod ze stavu „draft“ do „confirmed“ je validní. Místo toho v `OrderService::confirmOrder()` stojí: `if ($order->getStatus() !== 'draft') { throw …; } $order->setStatus('confirmed');`. Z modelu se stala databázová tabulka v PHP.
:::

## 09.03 Hexagonal Architecture (Ports & Adapters, Cockburn 2005) {#hexagonal}

V klasické třívrstvé struktuře (UI / Logic / Database) procházely testy aplikační logiky nutně buď přes UI, nebo přes databázi. To Alistairu Cockburnovi vadilo a v roce 2005 navrhl v článku *Hexagonal Architecture (Ports and Adapters)* [[3]](https://alistair.cockburn.us/hexagonal-architecture/) jiné uspořádání. Aplikační jádro (doména) v něm komunikuje s vnějším světem výhradně přes dobře definované **porty** (rozhraní). Konkrétní technologie (HTTP, SQL, e-mail, fronta zpráv) tyto porty implementují jako **adaptéry**.

Číslo šest v hexagonu nic neznamená. Cockburn ho zvolil proto, aby měl kreslíř kolem jádra dost místa na porty a adaptéry a nebyl svázaný jednorozměrným vrstvovým schématem [[3]](https://alistair.cockburn.us/hexagonal-architecture/). Stejně dobře by posloužil osmiúhelník nebo trojúhelník.

Od roku 2024 má vzor i knižní zpracování: *Hexagonal Architecture Explained*, které Cockburn napsal s Juanem Manuelem Garridem de Paz. Kniha vznikla mimo jiné jako reakce na výklady, které se od originálu odchýlily.

### Dva typy portů {#hexagonal-typy-portu-heading}

- **Driving (Inbound, Primary) port** – to, co aplikace *umí*. Definuje, jak vnější svět volá doménu. V DDD termínech to odpovídá *Application Service* nebo *Use Case* rozhraní. Příklad: `PlaceOrder`, `CancelOrder`, `GetOrderHistory`.
- **Driven (Outbound, Secondary) port** – to, co aplikace *potřebuje*. Definuje rozhraní pro externí závislosti. V DDD jsou to repository rozhraní, brány na externí systémy, publishery doménových událostí. Příklad: `OrderRepository`, `EmailSender`, `EventPublisher`.

Adaptéry implementují porty: **Driving adaptér** (Symfony Controller, CLI Command, Messenger Handler) volá inbound port; **Driven adaptér** (Doctrine Repository, SMTP Mailer, RabbitMQ publisher) implementuje outbound port. Doména samotná nezná žádný adaptér ani konkrétní technologii.

### Kolik portů dává smysl {#hexagonal-granularita-heading}

Port není v originále synonymum pro rozhraní jedné závislosti. Cockburn ho definuje jako „účelovou konverzaci“, tedy tematický kanál, do kterého se typicky zapojuje víc adaptérů pro různé technologie [[3]](https://alistair.cockburn.us/hexagonal-architecture/). K počtu dodává, že krajní varianta „port pro každý use case“ vede u větší aplikace ke stovkám portů. Sám se přiklání ke dvěma až čtyřem. V ukázkovém systému jmenuje čtyři: příjem dat o počasí, správce, odběratele notifikací a databázi odběratelů.

PHP praxe jde jinudy. Repozitáře, mailery a publishery událostí dostávají vlastní rozhraní jedna ku jedné, protože to odpovídá tomu, jak se v Symfony píše autowiring i testovací double. Tuto jemnější granularitu používá i zbytek průvodce. Cockburnova definice to ale není: u něj pokrývá jeden port celou konverzaci, kterou PHP praxe rozdělí do několika rozhraní.

Jedna hranice platí v obou výkladech. V rozhovoru z roku 2020 označuje Cockburn za hlavní chybu praxe „jednu technologii na port, nebo port na technologii“ [[4]](https://jmgarridopaz.github.io/content/interviewalistair.html). Tím se ztrácí smysl portu, tedy záměna technologie beze změny jádra. Rozhraní `RedisOrderCache` je porušením vzoru; `OrderCache` s Redis adaptérem a in-memory adaptérem pro testy není.

Mechanismus pod porty pojmenoval Gerard Meszaros v roce 2011 jako **Configurable Dependency**. Konkrétní implementaci takové závislosti určuje až sestavení aplikace zvenčí [[4]](https://jmgarridopaz.github.io/content/interviewalistair.html). V Symfony tu roli plní Service Container.

### Symfony struktura podle Hexagonal {#hexagonal-symfony-heading}

:::code{language="bash" filename="src/ (Symfony Hexagonal struktura)"}
src/
├── Ordering/                           # Bounded Context
│   ├── Domain/                         # Doménové jádro (žádné framework deps)
│   │   ├── Model/
│   │   │   ├── Order.php               # Aggregate Root – ČISTÉ PHP
│   │   │   └── OrderLine.php
│   │   ├── ValueObject/
│   │   │   └── OrderId.php
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
└── SharedKernel/
    └── Domain/
        └── DomainException.php
:::

Ze struktury plyne:

- Adresář `Domain/` neobsahuje *žádný* import z Doctrine, Symfony, Twig ani jiné knihovny. Pouze čisté PHP a vlastní typy.
- Repository rozhraní (`OrderRepository`) žije v `Domain/Port/`; jeho implementace (`DoctrineOrderRepository`) žije v `Infrastructure/Persistence/`. Doména závisí na rozhraní, infrastruktura ho implementuje.
- Doménová entita (`Order`) **není Doctrine entita**. K mapování slouží samostatná `OrderOrmEntity` + mapper (vzor [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern)), takže doména zůstává čistá. *Pozn.: Hexagonal Architecture trvá na této separaci. Pragmatičtější přístup, který zbytek průvodce používá jako výchozí, atributy přímo na agregátu připouští (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)).*
- Vstup do aplikace prochází přes *inbound port* (`PlaceOrder`). HTTP Controller a CLI Command nezávisí na doméně přímo, ale na tomto portu.

Dělení jádra na `Domain/` a `Application/` v originále nenajdete. Cockburn popisuje jen vnitřek a vnějšek hexagonu; rozdělení na aplikační a doménovou vrstvu je podle Garrida de Paz téma DDD, ne hexagonální architektury [[4]](https://jmgarridopaz.github.io/content/interviewalistair.html). Struktura výše je tedy skladba dvou vzorů, ne jednoho.

### Příklad: Outbound port a jeho adaptér {#hexagonal-priklad-heading}

:::code{language="php" filename="src/Ordering/Domain/Port/OrderRepository.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Port;

use App\Ordering\Domain\Exception\OrderNotFoundException;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;

// Port/ je hexagonální jméno pro adresář, kterému zbytek knihy říká Repository/.
interface OrderRepository
{
    /** @throws OrderNotFoundException */
    public function get(OrderId $id): Order;

    public function save(Order $order): void;

    /**
     * @return list<Order>
     */
    public function findByCustomer(CustomerId $customerId): array;
}
:::

:::code{language="php" filename="src/Ordering/Infrastructure/Persistence/DoctrineOrderRepository.php" highlights="13,14,15,16,17,18,19,24,25"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Persistence;

use App\Ordering\Domain\Exception\OrderNotFoundException;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Port\OrderRepository;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderMapper $mapper,
    ) {
    }

    public function get(OrderId $id): Order
    {
        $orm = $this->em->find(OrderOrmEntity::class, $id->value)
            ?? throw OrderNotFoundException::withId($id);

        return $this->mapper->toDomain($orm);
    }

    public function save(Order $order): void
    {
        // Ukázka pokrývá insert. Reálná implementace při update nejprve
        // najde existující OrderOrmEntity přes find() a přepíše její pole –
        // persist() nové instance by skončil kolizí primárního klíče.
        //
        // Flush je tu proto, že kontroler volá port přímo. Jakmile příkazy
        // půjdou přes command bus s doctrine_transaction middleware,
        // transakci vlastní ten a flush odsud zmizí. Bez jednoho nebo
        // druhého API vrátí 201 a v databázi nezůstane nic.
        $orm = $this->mapper->toOrm($order);
        $this->em->persist($orm);
        $this->em->flush();
    }

    /**
     * @return list<Order>
     */
    public function findByCustomer(CustomerId $customerId): array
    {
        $rows = $this->em->getRepository(OrderOrmEntity::class)
            ->findBy(['customerId' => $customerId->value]);

        return array_map(fn (OrderOrmEntity $r) => $this->mapper->toDomain($r), $rows);
    }
}
:::

Doménová třída `Order` je čisté PHP bez jediné Doctrine anotace. `OrderOrmEntity` je samostatná persistenční třída s Doctrine mapováním a `OrderMapper` překlápí mezi nimi. Cena: dvojí třída a explicitní mapování. Zisk: doménový model je testovatelný v paměti bez databáze, lze ho serializovat do JSON Event Storu beze změny tvaru a změna persistence vrstvy doménu nezasáhne.

### Příklad: Inbound port a jeho HTTP adapter {#hexagonal-inbound-heading}

Driving (inbound) port definuje, co aplikace umí. V DDD termínech je to kontrakt Application Service. V Symfony 8 se zpravidla mapuje na CQRS Command/Query handler (podrobně v kapitole [CQRS](/cqrs)) dispatchovaný přes Messenger Bus. Port jde také zapsat explicitně jako rozhraní s jediným handlerem jako implementací.

:::code{language="php" filename="src/Ordering/Application/UseCase/PlaceOrder.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\UseCase;

use App\Ordering\Application\Dto\PlaceOrderInput;
use App\Ordering\Application\Dto\PlaceOrderOutput;

/**
 * Inbound port (driving) – kontrakt aplikační schopnosti
 * „umístit objednávku“. HTTP adaptér, CLI command i testy
 * volají přes tento port; konkrétní implementace je v handleru.
 */
interface PlaceOrder
{
    public function handle(PlaceOrderInput $input): PlaceOrderOutput;
}
:::

Oba DTO jsou obyčejné neměnné struktury bez chování. Port jimi vymezuje, co vstupuje dovnitř a co vystupuje ven:

:::code{language="php" filename="src/Ordering/Application/Dto/PlaceOrderInput.php + PlaceOrderOutput.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Dto;

final readonly class PlaceOrderInput
{
    /** @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items */
    public function __construct(
        public string $customerId,
        public array $items,
    ) {}
}

final readonly class PlaceOrderOutput
{
    public function __construct(
        public string $orderId,
        public string $status,
    ) {}
}
:::

HTTP adaptér nezná konkrétní třídu handleru, jen rozhraní portu. Na implementaci ho naváže kontejner (viz [sekci o Service Containeru](#hexagonal-symfony-di-heading) níže). V testech tak jde handler vyměnit za fake bez celé aplikační vrstvy.

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

Symfony autowiring doplňuje závislosti podle typu. U rozhraní si kontejner poradí sám, pokud mezi načtenými službami najde právě jednu implementaci. Alias na ni pak [vytvoří automaticky](https://symfony.com/doc/current/service_container/autowiring.html). V hexagonální struktuře výše leží `OrderRepository` i `DoctrineOrderRepository` pod `src/`, takže type-hint na port funguje bez jediného řádku konfigurace.

Explicitní **alias** potřebujete ve dvou situacích. Buď je implementací víc než jedna, nebo adresář s rozhraním či s implementací nespadá do `resource`, typicky když ho vyloučíte (viz [Konfigurace per-context](#symfony-config-heading)). Psát alias i tam, kde by vznikl sám, není chyba. Dokumentuje volbu výchozího adaptéru. První možnost je zápis v `config/services.yaml`:

:::code{language="yaml" filename="config/services.yaml (výřez: alias portu)" highlights="10,11"}
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

Druhá možnost je atribut `#[AsAlias]` přímo na implementaci. Alias pak žije ve vrstvě Infrastructure, kam patří:

:::code{language="php" filename="src/Ordering/Infrastructure/Persistence/DoctrineOrderRepository.php (s AsAlias)"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Persistence;

use App\Ordering\Domain\Port\OrderRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: OrderRepository::class)]
final class DoctrineOrderRepository implements OrderRepository
{
    // ... viz implementace výše
}
:::

Use case v Application vrstvě pak deklaruje závislost prostým type-hintem na doménové rozhraní a o existenci Doctrine adaptéru neví:

:::code{language="php" filename="src/Ordering/Application/UseCase/PlaceOrderHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\UseCase;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Port\OrderRepository;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\ProductId;
use App\Ordering\Application\Dto\PlaceOrderInput;
use App\Ordering\Application\Dto\PlaceOrderOutput;
use App\SharedKernel\Domain\Currency;
use App\SharedKernel\Domain\Money;

final class PlaceOrderHandler implements PlaceOrder
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {
    }

    public function handle(PlaceOrderInput $input): PlaceOrderOutput
    {
        // DTO nese primitivy z HTTP vrstvy; převod na hodnotové objekty
        // patří sem, do aplikační vrstvy. Doména primitivy nepřijímá.
        $order = Order::place(
            OrderId::generate(),
            CustomerId::fromString($input->customerId),
        );

        foreach ($input->items as $item) {
            $order->addItem(
                ProductId::fromString($item['productId']),
                $item['quantity'],
                new Money($item['unitPriceInCents'], Currency::CZK),
            );
        }

        $this->orders->save($order);

        return new PlaceOrderOutput($order->id->value, $order->status->value);
    }
}
:::

Nabízel by se i atribut `#[Autowire(service: DoctrineOrderRepository::class)]` přímo v konstruktoru handleru. To je v Application vrstvě anti-vzor. Vyžaduje import Infrastructure třídy, čímž porušuje Dependency Rule, kterou celá struktura chrání. Use case by znal konkrétní adaptér a záměna implementace (testovací `InMemoryOrderRepository`) by znamenala zásah do aplikačního kódu místo do konfigurace. Alias patří do `services.yaml` nebo na implementaci, nikdy do vnitřních vrstev.

Jakmile portu odpovídá víc implementací, automatický alias zaniká a kontejner ohlásí nejednoznačnost. Výchozí adaptér pak určuje alias a druhá implementace se zpřístupní pojmenovaným autowiring aliasem:

:::code{language="yaml" filename="config/services.yaml (výřez: pojmenovaný alias)"}
services:
    # Výchozí adaptér portu
    App\Ordering\Domain\Port\OrderRepository:
        alias: App\Ordering\Infrastructure\Persistence\DoctrineOrderRepository

    # Druhá implementace pod pojmenovaným aliasem
    App\Ordering\Domain\Port\OrderRepository $readOnlyOrders:
        alias: App\Ordering\Infrastructure\Persistence\ReadOnlyOrderRepository
:::

Na pojmenovaný alias se v konstruktoru odkazuje atribut `#[Target('readOnlyOrders')]`. Vazba podle jména parametru bez atributu funguje také, ale rozbije ji přejmenování parametru. Testovací prostředí přepisuje výchozí alias v `config/services_test.yaml`, takže `InMemoryOrderRepository` nahradí Doctrine adaptér bez zásahu do aplikačního kódu.

### Druhý port: publisher doménových událostí {#hexagonal-event-port-heading}

Repository je nejviditelnější, ale ne jediný outbound port. Druhým častým kandidátem je publikace doménových událostí. Aplikační vrstva volá `EventPublisher::publish($event)` a neřeší, kdo události konzumuje. Implementací může být Symfony Messenger, RabbitMQ, in-memory dispatcher pro testy, v jednoduchých scénářích i no-op.

:::code{language="php" filename="src/Ordering/Domain/Port/EventPublisher.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Port;

interface EventPublisher
{
    // Události se typují jako `object`. Kanonický AggregateRoot::record()
    // v této knize žádnou bázovou třídu událostí nevyžaduje, takže vázat
    // port na společného předka by sem nepustil ani OrderPlaced.
    public function publish(object $event): void;

    /**
     * @param iterable<object> $events
     */
    public function publishAll(iterable $events): void;
}
:::

:::code{language="php" filename="src/Ordering/Infrastructure/Messaging/MessengerEventPublisher.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Messaging;

use App\Ordering\Domain\Port\EventPublisher;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventPublisher implements EventPublisher
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function publish(object $event): void
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

Pro testy stačí `InMemoryEventPublisher`, který události jen sbírá do pole. Test pak ověří, co use case publikoval, bez Messengeru, RabbitMQ a jiné infrastruktury. Běží v jednotkách milisekund, ne ve stovkách.

:::code{language="php" filename="tests/Ordering/Doubles/InMemoryEventPublisher.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Doubles;

use App\Ordering\Domain\Port\EventPublisher;

final class InMemoryEventPublisher implements EventPublisher
{
    /** @var list<object> */
    private array $published = [];

    public function publish(object $event): void
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
     * @return list<object>
     */
    public function published(): array
    {
        return $this->published;
    }
}
:::

### Kdy se Hexagonal hodí {#hexagonal-kdy-heading}

- **Doména s bohatým chováním** – kde se vyplatí investovat do testů domény bez databáze.
- **Více vstupních kanálů.** HTTP API, CLI, Messenger consumer i GraphQL jsou jen jiné driving adaptéry nad stejným inbound portem.
- **Plánovaná výměna technologie** – migrace z Doctrine ORM na DBAL nebo na cloudovou databázi se omezí na nový adaptér.
- **Aplikace s 50–500 endpointy** – kde se overhead portů amortizuje počtem use casů.

### Kdy Hexagonal nedává smysl {#hexagonal-kdy-ne-heading}

U CRUDu nad několika tabulkami je port, adaptér a mapper pro každou entitu režie bez návratnosti. Nepomůže ani týmu, který neovládá Dependency Injection; bez inverze závislostí je struktura jen kosmetická. A nevyplatí se ani u produktu s krátkým horizontem.

:::callout{type="warn"}
### Anti-vzor: Anemic Hexagonal {#hexagonal-anti-heading}

Tým přečte Cockburnův článek a založí vzornou strukturu s `Domain/Port/`, `Application/UseCase/` a `Infrastructure/Adapter/`. Doménová třída ale dál nese jen `$status: string` a settery a všechna logika sedí v handleru. Hexagonal bez DDD modelování je rituál kolem prázdné domény.

Druhý častý anti-vzor: **port = repository, ostatní jsou jen služby**. Tým definuje jen `OrderRepository` jako port, ale e-mailový mailer, externí HTTP klient a publisher událostí žijí v `App\Service\` bez rozhraní. Doména pak závisí na *konkrétním* maileru. Princip Hexagonal to porušuje stejně jako Doctrine atributy na entitě.
:::

## 09.04 Onion Architecture (Palermo 2008) {#onion}

Onion Architecture představil Jeffrey Palermo v blogové sérii [[5]](https://jeffreypalermo.com/2008/07/the-onion-architecture-part-1/). První tři díly vyšly v roce 2008, čtvrtý (*Part 4 – After Four Years*) v roce 2013. Vzor upravuje vrstvenou architekturu: doménový model staví do středu a závislosti smějí směřovat jen *dovnitř*, nikdy ven. Jméno **Dependency Rule** dal tomuto pravidlu až Robert C. Martin (viz [Clean Architecture](#clean)). Metaforou je cibule (onion) s koncentrickými prstenci.

### Čtyři koncentrické vrstvy Onion {#onion-vrstvy-heading}

1. **Domain Model (jádro)** – entity, hodnotové objekty, agregáty, doménové události. Žádné závislosti. Žádný framework. Žádná persistence.
2. **Domain Services** – bezstavové třídy s doménovou logikou, která nepatří do žádné konkrétní entity. Závisí jen na Domain Model.
3. **Application Services** – orchestrace use casů, transakce, mapování DTO. Závisí na Domain Services a Domain Model.
4. **UI / Infrastructure** – controllery, repository implementace, externí brány. Vnější vrstva závisí na Application Services.

Podstatné je slovo **koncentrické**. Vrstvy nejsou naskládané nad sebou (nahoře UI, dole DB), ale soustředné: jádro uprostřed, vnější svět kolem. Tím mizí jeden problém klasické vrstvené architektury, kde Domain smí záviset na Infrastructure (třeba číst z databáze). V Onion to dovolené není. Repozitáře jádro deklaruje jako rozhraní a implementuje je vrstva UI/Infrastructure.

V dílu *After Four Years* shrnul Palermo vzor do čtyř tezí [[6]](https://jeffreypalermo.com/2013/08/onion-architecture-part-4-after-four-years/). Aplikace stojí kolem nezávislého objektového modelu. Vnitřní vrstvy definují rozhraní, vnější je implementují. Každá vazba míří do středu. A jádro se dá zkompilovat a spustit bez infrastruktury. Tamtéž odmítá běžné čtení, že jde o „DDD architekturu“. Onion podle něj nezávisí na DDD, na CQRS ani na IoC kontejneru. Výklad přes agregáty a doménové služby, který používá tato kapitola, je tedy jedno z možných čtení, ne definice vzoru.

### Rozdíl proti Hexagonal {#onion-vs-hexagonal-heading}

Onion a Hexagonal stojí na téže myšlence: izolovat doménu a obrátit závislosti dovnitř. V běžné implementaci jsou v Symfony nerozlišitelné. Tři jemné odlišnosti:

- **Vrstvení uvnitř.** Onion explicitně rozlišuje Domain Services a Application Services jako dvě samostatné vrstvy. Hexagonal je topologicky střídmější – port + adaptér, žádné vnitřní vrstvení.
- **Statický vs. dynamický pohled.** Onion popisuje vrstvy a kdo na koho závisí. Hexagonal se dívá dynamicky: porty, adaptéry a cesta dat skrz ně.
- **Driving vs. driven porty.** V Onion je v UI vrstvě i HTTP controller (driving) i Doctrine repository (driven). Z pohledu Hexagonal je to nepřesné – driving adaptér *volá* aplikaci, driven adaptér *je volán* doménou.

Pokud váš projekt používá Hexagonal slovník (port, adapter, driving, driven), ale uvnitř má dvě vrstvy služeb (Domain Service, Application Service), děláte hybrid Hexagonal+Onion. To je v pořádku. Málokdo implementuje jeden styl „čistě“.

### Příklad: Domain Service vs. Application Service {#onion-priklad-heading}

Domain Service obsahuje *doménovou logiku*, která nepatří do agregátu (typicky proto, že pracuje s více agregáty najednou nebo vyžaduje data, která agregát nemá k dispozici). Application Service je *orchestrátor*. Řídí transakci, načítá agregáty z repository, volá doménovou logiku a publikuje výstupy.

:::code{language="php" filename="src/Pricing/Domain/Service/PriceCalculator.php"}
<?php

declare(strict_types=1);

namespace App\Pricing\Domain\Service;

use App\Pricing\Domain\Model\Cart;
use App\Pricing\Domain\Model\Customer;
use App\Pricing\Domain\Model\DiscountPolicy;
use App\SharedKernel\Domain\Money;

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
        $net = $subtotal->subtract($discount);
        $vat = $net->percentage(21);

        return $net->add($vat);
    }
}
:::

:::code{language="php" filename="src/Pricing/Application/Service/CalculateCartPrice.php" highlights="16,17,18,19,20,21,22,23,24"}
<?php

declare(strict_types=1);

namespace App\Pricing\Application\Service;

use App\Pricing\Domain\Exception\CartNotFoundException;
use App\Pricing\Domain\Exception\CustomerNotFoundException;
use App\Pricing\Domain\Port\CartRepository;
use App\Pricing\Domain\Port\CustomerRepository;
use App\Pricing\Domain\Port\DiscountPolicyRepository;
use App\Pricing\Domain\Service\PriceCalculator;
use App\SharedKernel\Domain\Money;

/**
 * Application Service – orchestrace use casu „Spočítej cenu košíku“.
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
            ?? throw new CartNotFoundException($cartId);

        $customer = $this->customers->get($cart->customerId())
            ?? throw new CustomerNotFoundException($cart->customerId());

        $policy = $this->policies->forCustomer($customer);

        return $this->calculator->calculate($cart, $customer, $policy);
    }
}
:::

Rozdíl je v přístupu k datům. `PriceCalculator` repository nezná a bere si *již načtené* objekty. `CalculateCartPrice` je zná přes porty a orchestruje načtení i předání dat. Když obě odpovědnosti slijete do jedné třídy, přijdete o možnost testovat výpočet ceny izolovaně, bez databáze.

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
└── SharedKernel/
    └── Domain/
        └── Money.php
:::

Symfony auto-wiring funguje pro Onion stejně jako pro Hexagonal. Application Service závisí na Domain Service a portech, vnější HTTP adapter na Application Service. Žádná třída v `Domain/` nepoužívá `use Symfony\…` ani `use Doctrine\…`; `use` v jádře míří jen na třídy z `Domain/` a ze sdíleného jádra (`SharedKernel/Domain/`).

### Kdy se Onion hodí {#onion-kdy-heading}

- **Domény s rozsáhlými Domain Services** – pricing engine, risk scoring, tax calculation, kde hodně logiky pracuje s víc agregáty najednou.
- **Týmy, které mají rády explicitní vrstvení** – Onion má jasné jméno pro každou vrstvu a směr závislostí hlídá statická analýza v CI. V PHP se k tomu používá [Deptrac](https://github.com/deptrac/deptrac), typicky právě s vrstvami Domain / Application / Infrastructure; postup je v kapitole [Architektonické testy](/testovani-ddd#architektonicke-testy).
- **Enterprise aplikace s 100+ use casy** – kde rozdělení Domain Services a Application Services brání monolitickým „God service“ třídám.

### Kdy Onion nedává smysl {#onion-kdy-ne-heading}

Doména s hrstkou doménových služeb dvě vrstvy služeb neuživí; Hexagonal pak stačí. Druhá překážka je zkušenost týmu. Hranice mezi Domain Service a Application Service není intuitivní a špatné zařazení jedné třídy protáhne infrastrukturní závislost až do jádra.

## 09.05 Clean Architecture (Robert C. Martin 2012) {#clean}

Robert C. Martin („Uncle Bob“) shrnul společné rysy Hexagonal, Onion, DCI a BCE (Boundary-Control-Entity od Ivara Jacobsona) do jednoho modelu. Výsledkem byl blogový post *Clean Architecture* z roku 2012 [[7]](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html). O pět let později ho rozvedl do knihy *Clean Architecture: A Craftsman's Guide to Software Structure and Design* (Prentice Hall, 2017). Ta vzor doplňuje o kapitoly k hranicím komponent a k organizaci balíčků.

### Čtyři prsteny Clean Architecture {#clean-prsteny-heading}

1. **Entities** – doménové objekty s nejhlubšími invarianty. Odpovídá DDD agregátům a hodnotovým objektům. Nezávisí na ničem.
2. **Use Cases** – obchodní pravidla specifická pro aplikaci. V běžné implementaci je každý use case třída s jednou veřejnou metodou (`execute()` nebo `handle()`). Závisí jen na Entities.
3. **Interface Adapters** – Controllers (pro vstup), Presenters (pro výstup), Gateways (pro outbound). Překlápějí mezi formátem use casu a formátem vnějšího světa.
4. **Frameworks & Drivers** – Symfony, Doctrine, HTTP klienty, databázové ovladače. Vnější prsten, kde žije všechno framework-specifické.

**Dependency Rule**: závislosti ve zdrojovém kódu směřují jen dovnitř. Vnější vrstva smí odkazovat na třídy vnitřní vrstvy, nikdy naopak. Potřebuje-li vnitřní vrstva něco z vnější (např. uložit objednávku), použije *Dependency Inversion*: rozhraní definuje u sebe a vnější vrstva ho implementuje.

Počet čtyři přitom není závazný. Martin sám píše, že prstence jsou schéma a aplikace jich může potřebovat víc [[7]](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html). Druhé pravidlo se týká toho, co hranici překračuje: jednoduché datové struktury, nikdy ORM entity ani databázové řádky. Request a Response DTO v ukázkách níže jsou přesně tím.

### Co Clean přidává proti Onion a Hexagonal {#clean-co-pridava-heading}

Hexagonal a Onion nepojmenovávají jednotlivé use casy explicitně. Hexagonal mluví o „inbound portech“, Onion o „Application Services“. Clean Architecture povyšuje use case na **prvotřídní koncept**. Každý use case je jedna třída s jednou metodou a vlastním Request/Response DTO. Z aplikace se tak stává explicitní seznam schopností.

V DDD termínech: Use Case z Clean Architecture ≈ DDD Application Service ≈ CQRS Command Handler. Pokud používáte Symfony Messenger pro Command Bus (viz kapitolu [CQRS](/cqrs)), váš `PlaceOrderHandler` plní roli Clean Use Case.

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
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
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

use App\Ordering\Domain\Exception\CustomerNotFoundException;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Port\CustomerRepository;
use App\Ordering\Domain\Port\EventPublisher;
use App\Ordering\Domain\Port\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\ProductId;
use App\SharedKernel\Domain\Currency;
use App\SharedKernel\Domain\Money;

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
            ?? throw new CustomerNotFoundException($request->customerId);

        // Kanonický Order::place() bere jen identitu a vlastníka; položky
        // se přidávají metodou, která u každé kontroluje invariant.
        $order = Order::place(
            OrderId::generate(),
            $customer->id, // reference na jiný agregát vede přes ID
        );

        foreach ($request->items as $item) {
            $order->addItem(
                ProductId::fromString($item['productId']),
                $item['quantity'],
                new Money($item['unitPriceInCents'], Currency::CZK),
            );
        }

        $this->orders->save($order);

        // Synchronní publikace stačí pro vývoj. Produkčně sem patří
        // Outbox, jinak se událost ztratí při pádu mezi zápisem a publikací.
        foreach ($order->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return new PlaceOrderResponse(
            orderId: $order->id->value,
            status: $order->status->value,
            totalAmount: $order->totalAmount()->amountInCents,
        );
    }
}
:::

`PlaceOrderUseCase` je *jediný vstupní bod* této aplikační schopnosti. HTTP Controller, CLI Command, Messenger Handler, GraphQL Resolver i test používají stejný kontrakt: `PlaceOrderRequest` dovnitř, `PlaceOrderResponse` ven.

### Adaptér: Symfony HTTP Controller jako Interface Adapter {#clean-controller-heading}

Kontroler níže je **varianta** toho z hexagonální sekce, ne druhý soubor. Má stejný namespace, stejné jméno třídy i stejnou routu `/api/orders`, takže v projektu může existovat jen jeden z nich.

:::code{language="php" filename="src/Ordering/Infrastructure/Http/PlaceOrderController.php (varianta Clean Architecture)"}
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

Controller dělá přesně tři věci: dekóduje HTTP vstup do `PlaceOrderRequest`, zavolá use case a zformátuje výstup zpět do JSON. Žádná doménová logika, žádné rozhodování. Stejný use case obslouží CLI command na pár řádcích, Messenger handler, nebo ho přímo zavolá PHPUnit test bez frameworku.

:::callout{type="note"}
### Mapování mezi DTO a modelem v Symfony 8 {#clean-objectmapper-heading}

Nejčastější námitka proti Clean i proti [Persisted Object Patternu](/implementace-v-symfony#persisted-object-pattern) je ruční mapování, kdy se každé pole opisuje dvakrát. Symfony 8 na to má komponentu `symfony/object-mapper`. Vznikla v 7.3 jako experimentální a od 7.4, vydané současně s 8.0, je stabilní. Převod řídí atribut `#[Map(target: ...)]` na zdrojové třídě a volání `ObjectMapperInterface::map()`; [dokumentace](https://symfony.com/doc/current/object_mapper.html) jmenuje mezi případy užití přímo hexagonální architekturu.

Cena mapování tím neklesá na nulu. Komponenta ušetří opisování polí, ale rozhodnutí, co přes hranici projde a v jakém tvaru, zůstává na vás. A právě to je na hranici podstatné.
:::

### Kdy se Clean hodí {#clean-kdy-heading}

- **Aplikace s explicitním seznamem use casů** – kde má každá schopnost svoje jméno a kontrakt (např. ERP systémy, finanční aplikace).
- **Více vstupních kanálů.** HTTP API, CLI, Messenger i GraphQL sdílejí stejné use casy.
- **Tým s vyšší zkušeností** – kde dodatečné vrstvení a DTO ping-pong nezpomalí vývoj.
- **Aplikace, kde je důležitý audit „co aplikace umí“** – Use Case třídy jsou tím seznamem.

### Kdy Clean nedává smysl {#clean-kdy-ne-heading}

U aplikace s třiceti endpointy se DTO ping-pong (Request → Domain → Response) nezaplatí. Clean navíc stojí na inverzi závislostí ještě silněji než Hexagonal, takže tým bez praxe s Dependency Injection se v něm ztratí. A nad tenkou doménou zůstane z use casů jen rituál.

:::callout{type="pattern"}
### Vztah Clean Use Case ↔ CQRS Command Handler {#clean-pattern-heading}

Pokud znáte CQRS pattern (kapitola [CQRS](/cqrs)), všimnete si, že Use Case v Clean v zásadě odpovídá CQRS Command Handleru:

- `PlaceOrderRequest` ≈ Command DTO
- `PlaceOrderResponse` ≈ Command Result (často void nebo ID)
- `PlaceOrderUseCase::execute()` ≈ `PlaceOrderHandler::__invoke()`; Martin pro takovou třídu používá název *interactor*
- Symfony Messenger Bus v Clean Architecture přímý protějšek nemá, jen doručí command správnému handleru

Symfony 8 projekt s Messengerem jako Command Busem má tedy use casy v duchu Clean Architecture bez další vrstvy. Stačí Use Case přejmenovat na `*Handler` a Request na `*Command`. Řada DDD projektů funguje jako kombinace *Hexagonal + CQRS + Clean Use Cases* v jednom hybridním stylu.
:::

## 09.06 Vertical Slice Architecture (a horizontální vs. vertikální dělení) {#vertical-slice}

Vrstvové architektury mají skrytou cenu: běžný use case se rozprostře do 5–7 souborů (Controller, Service, Domain Service, Repository interface, Repository impl, DTO, Mapper) a změna jediné funkce sahá do každého z nich. Na to reaguje *Vertical Slice Architecture*, kterou Jimmy Bogard popsal v roce 2018 [[8]](https://www.jimmybogard.com/vertical-slice-architecture/).

Vertical Slice Architecture organizuje kód **podle feature, ne podle vrstvy**. Každá feature dostane svůj adresář, ve kterém žije všechno potřebné: Command/Query, Handler, Validátor, Read Model, Controller. Slice je vertikální „sloupec“ přes všechny technické vrstvy aplikace.

### Horizontální dělení – tradiční vrstvený přístup {#horizontalni-deleni}

Tradiční vrstvené DDD člení projekt **podle technických vrstev**. Každá vrstva má svůj adresář a soubory podobného typu leží pohromadě. Typický `src/`:

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

Vrstvy leží horizontálně nad sebou; každá poskytuje služby té nad sebou. Doménové stavební kameny (entity, hodnotové objekty, agregáty, doménové služby) jsou stejné jako u jakéhokoli jiného architektonického stylu.

### Vertikální dělení – Vertical Slice {#vertikalni-deleni}

Vertikální slice obrací členění. Jednotkou není vrstva, ale **feature**. Každá funkce (registrace uživatele, vytvoření objednávky, generování faktury) má svůj adresář se vším, co její implementace potřebuje. Sdílený doménový model zůstává v `{BC}/Domain/`, ale aplikační, prezentační a infrastrukturní logika se dělí per feature.

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
└── SharedKernel/Domain/Exception/DomainException.php
:::

Vazby mezi funkcemi klesají a soudržnost uvnitř každé z nich roste [[8]](https://www.jimmybogard.com/vertical-slice-architecture/). Bounded Contexts i sdílený doménový model přitom zůstávají.

Bogard jde ve svém článku dál, než tato struktura ukazuje. Vadí mu povinný řetěz „controller musí volat službu, která musí použít repozitář“, a tvrdí, že uvnitř slice většina abstrakcí odpadá. Vzor doménové logiky se podle něj volí per slice: triviální slice může být Transaction Script, složitý bohatý model [[8]](https://www.jimmybogard.com/vertical-slice-architecture/).

Konvence této knihy je vědomě měkčí. Doménový model zůstává sdílený uvnitř Bounded Contextu a slice krájí jen aplikační, prezentační a část infrastrukturní vrstvy. Důvod jsou invarianty. Agregát, který si každý slice modeluje po svém, přestane být jediným místem, kde doménová pravidla platí.

:::callout{type="note"}
### Konvence struktury v této knize {#konvence-heading}

Většina příkladů v knize používá vertikální slice s těmito konvencemi:

- `{BC}/Domain/` – doménová vrstva sdílená uvnitř Bounded Contextu (Model, ValueObject, Event, Repository rozhraní, Service).
- `{BC}/Infrastructure/` – infrastrukturní implementace (Doctrine repozitáře, event bus adaptéry).
- `{BC}/{Feature}/` – feature slice s `Command/`, `Query/`, `Controller/` přímo uvnitř.
- `SharedKernel/` – pouze skutečně sdílené komponenty (abstraktní typy, výjimky, bus rozhraní).
:::

### Co Vertical Slice mění {#vs-rozdil-heading}

- **Adresářová struktura** – místo `Controller/, Service/, Domain/, Infrastructure/` máte `Ordering/PlaceOrder/, Ordering/CancelOrder/, Ordering/GetOrderHistory/`.
- Závislosti mezi feature klesají na minimum. Každá feature je téměř samostatná; sdílí se jen agregáty, hodnotové objekty a sběrnice (event bus, command bus).
- Diff jedné feature sedí v jednom adresáři, takže recenzent vidí při code review celý use case na jednom místě.
- Akceptační test pokryje celý slice najednou (HTTP request → response) bez mockování sedmi vrstev.

### Srovnání horizontálního a vertikálního dělení {#srovnani-deleni}

| Aspekt | Horizontální (vrstvený) | Vertikální slice |
|---|---|---|
| **Organizace kódu** | Podle technických vrstev | Podle funkcí (features) |
| **Vazby** | Silné mezi vrstvami | Silné uvnitř funkce, slabé mezi funkcemi |
| **Změna jednoho use casu** | Úpravy v 5–7 souborech napříč vrstvami | Úpravy v jednom adresáři |
| **Testovatelnost** | Vyžaduje více mocků (vrstvy mezi sebou) | Méně mocků, závislosti jsou lokální |
| **Škálovatelnost na microservices** | Vyžaduje přeorganizování všech vrstev | Feature lze přesunout jako celek |
| **Pochopení na začátku** | Jednodušší (tradičnější) | Vyžaduje pochopení slice jako jednotky |
| **Vhodnost pro CQRS** | CQRS vyžaduje dodatečnou práci | Přirozeně podporuje CQRS [[9]](https://learn.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/apply-simplified-microservice-cqrs-ddd-patterns) |

### Kdy zvolit který přístup {#kdy-vs}

**Horizontální (vrstvený) přístup** se vyplatí týmu, který má dlouhou zkušenost s vrstvenou architekturou a CQRS neplánuje. Sedí aplikaci s 10–30 endpointy a malou doménovou složitostí. Hodí se i tam, kde doménový model nese silně sdílené invarianty napříč více funkcemi a musí je vymáhat jednotně. Vyhovuje i týmu, který dá přednost explicitnímu oddělení technických vrstev před organizací podle funkcí.

**Vertikální slice** se vyplatí, když:

- Aplikace má 50+ funkcí s nezávislými use casy.
- Tým plánuje CQRS nebo je už zavedlo (Symfony Messenger jako Command/Query Bus).
- Aplikace se v budoucnu rozdělí do mikroslužeb a feature jde vyjmout jako celek.
- Preferujete rychlou iteraci s minimální koordinací mezi vrstvami.

### Třetí osa dělení: modul {#modul-osa}

Vrstva a slice nejsou jediné jednotky členění. Martin Fowler píše, že jakmile některá vrstva naroste, patří nejvyšší úroveň rozdělit na doménově orientované moduly a vrstvit až uvnitř nich [[10]](https://martinfowler.com/bliki/PresentationDomainDataLayering.html). Vrstvení tedy podle něj není správná dekompozice nejvyšší úrovně. Ve stejném textu varuje před organizací týmů podle vrstev.

Modulární monolit tu myšlenku dotahuje na úroveň nasazení. Kamil Grzybek popisuje modul třemi vlastnostmi [[11]](https://www.kamilgrzybek.com/blog/posts/modular-monolith-primer). Je nezávislý a zaměnitelný. Pokrývá kompletní business funkčnost, tedy moduly jako vertikální slice, ne technické vrstvy. A má definované rozhraní: všechno, co ven sdílí, se stává jeho veřejným API. Simon Brown jde na totéž ze strany balíčkování a v [package by component](https://simonbrown.je/modular-monolith/) doporučuje spoléhat na překladač, ne na disciplínu týmu.

PHP takovou oporu nedá, hranice modulu proto hlídá statická analýza a code review. Prakticky to znamená jeden adresář na modul, sadu command a query zpráv jako veřejné API a zákaz importů do vnitřku cizího modulu. Detail v kapitole [Kdy zvolit modular monolith](/ddd-a-microservices#modular-monolith).

### Vertical Slice a Hexagonal jsou ortogonální {#vs-vs-hexagonal-heading}

Hexagonal/Onion/Clean popisují, *jak strukturovat závislosti uvnitř jedné feature*. Vertical Slice určuje, *jak organizovat feature mezi sebou*. Oba přístupy jdou kombinovat. Každý vertikální slice může uvnitř používat Hexagonal port-adapter strukturu, tedy mít vlastní Port, vlastní Adapter i vlastní Domain Service. Nebo nemusí. Některé slice jsou tak triviální, že stačí jediná třída.

Kombinace **Hexagonal + Vertical Slice** je v Symfony projektech rozšířenou výchozí volbou. Bounded Context má sdílený doménový model (agregáty, value objekty, repository interfaces), aplikační vrstva se dělí do feature slice. Každý slice má svůj Command/Handler (nebo Query/Handler) a svůj HTTP Controller. Kombinace vyvažuje testovatelnost a přehlednost struktury.

## 09.07 Praktické srovnání – co si vybrat v Symfony 8 {#srovnani}

Volba stylu závisí na velikosti aplikace, zkušenosti týmu, plánovaném horizontu produktu a na tom, kolik se vyplatí investovat do izolace. Rozhodovací matice níže shrnuje typická kritéria. Čísla v ní jsou autorské pravidlo palce, ne měřený údaj. Slouží k porovnání stylů mezi sebou, ne jako hranice, o kterou by šlo opřít rozhodnutí samo o sobě.

:::diagram{fig="09.7-A" title="Layered vs. Hexagonal vs. Onion vs. Clean – vrstvy a směr závislostí" src="images/diagrams/13_architectural_styles/styles_comparison.svg"}
:::

:::diagram{fig="09.7-B" title="Čtyři architektonické styly aplikované na DDD" src="images/diagrams/13_architectural_styles/hexagonal_vs_onion.svg"}
:::

| Faktor | Layered | Hexagonal | Onion | Clean | Vertical Slice |
|---|---|---|---|---|---|
| **Křivka učení** | nízká | střední | střední | vysoká | nízká |
| **Vhodnost pro juniory** | ✓✓✓ | ✓ | ✓ | ✗ | ✓✓ |
| **Izolace domény v testech** | nízká | vysoká | vysoká | vysoká | střední |
| **Vazba na Doctrine** | těsná (atributy na entitě) | volná (přes adaptér) | volná | volná | podle slice |
| **Více vstupních kanálů** | náročné (duplicita) | přirozené | přirozené | přirozené | přirozené |
| **Boilerplate (DTO, mappery)** | nízký | střední | střední | vysoký | nízký |
| **Doporučená velikost projektu** | < 50 endpointů | 50–500 | 100+ | enterprise (200+) | 50–500 |
| **Soulad s CQRS** | vyžaduje úpravy | vysoká (port = command bus) | střední | vysoká (use case = handler) | velmi vysoká |
| **Změna jedné feature** | 5–7 souborů | 4–6 souborů | 5–7 souborů | 6–8 souborů | 1 adresář |

### Doporučená výchozí volba pro Symfony 8 {#srovnani-vyber-heading}

Pro středně velký projekt vychází jako výchozí volba:

**Hexagonal + Vertical Slice s CQRS přes Symfony Messenger.**

Konkrétně: Bounded Context má vlastní adresář (`src/Ordering/`). Uvnitř `Domain/` leží agregáty, hodnotové objekty a repository *interfaces* (porty); `Infrastructure/` obsahuje Doctrine adaptéry. Každá feature má svůj slice (`PlaceOrder/`, `CancelOrder/`) s Command/Query, Handler (= Clean Use Case) a HTTP Controller. Tato kombinace nabízí:

- **Doménové testy bez databáze** – agregáty jsou čisté PHP, mockují se jen porty.
- **Jednoduché code review** – diff jedné feature je v jednom adresáři.
- **CLI/HTTP/Messenger paritu** – Symfony Messenger Bus dispatchuje stejný Command z libovolného adaptéru.
- **Symfony idiomatičnost** – Messenger je prvotřídní komponenta, vlastní bus psát nemusíte.

Univerzální pravda to není. Interní administrativní aplikaci s 20 endpointy stačí obyčejná Layered struktura ze Symfony skeletonu, i když má desetiletý horizont. Tým s ní nejspíš iteruje rychleji. U enterprise CRM s 500+ use casy a 15 vývojáři se naopak vyplatí Clean Architecture s explicitním katalogem use casů.

:::callout{type="pattern"}
### Tři otázky před výběrem stylu {#srovnani-rozhodnuti-heading}

1. **Kolik bude use casů za rok?** Řádově: do 50 Layered, 50–500 Hexagonal nebo Vertical Slice, nad 200 Clean nebo hybrid.
2. **Vyplatí se izolovat doménu od Doctrine?** Pokud chcete testy bez databáze nebo plánujete migraci persistence vrstvy, ANO → Hexagonal+. Pokud Doctrine zůstane navždy a testy přes fixtures jsou OK, NE → Layered stačí.
3. **Kolik vstupních kanálů má aplikace?** Pokud jen HTTP, Layered je v pořádku. Pokud HTTP + CLI + Messenger + GraphQL, Hexagonal/Clean se výrazně vyplatí.
:::

## 09.08 Hybridní přístup – Hexagonal core, Layered okraje {#hybrid}

Reálný projekt málokdy potřebuje jediný styl pro celou kódovou bázi. Častěji se vyplatí **diferencovat investici podle typu subdomény**. Core Domain dostane plný Hexagonal s čistými agregáty a porty. Supporting subdoména si vystačí s Layered DDD se zjednodušeným modelováním. Generic subdoména je tenký adaptér na externí SaaS. Odpovídá to Evansovu doporučení z knihy *DDD*: modelovací úsilí patří *tam, kde přináší konkurenční výhodu*, ne všude stejně.

Detail klasifikace subdomén (Core / Supporting / Generic) je v kapitole [Subdomény: Core, Supporting, Generic](/subdomeny). Následuje ukázka, jak hybridní přístup vypadá ve struktuře Symfony projektu.

### Příklad: e-shop s diferencovanou architekturou {#hybrid-priklad-heading}

:::code{language="bash" filename="src/ (hybridní rozložení e-shopu)"}
src/
├── Ordering/                           # CORE DOMAIN – plný Hexagonal
│   ├── Domain/
│   │   ├── Model/                      # Bohatý agregát Order
│   │   │   ├── Order.php
│   │   │   └── OrderLine.php
│   │   ├── ValueObject/
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
└── SharedKernel/                       # Sdílené koncepty mezi BC
    ├── Domain/
    │   ├── Money.php
    │   └── DomainException.php
    └── Bus/
        ├── CommandBus.php              # Interface
        └── EventBus.php
:::

### Pravidla hybridního přístupu {#hybrid-pravidla-heading}

- **Core Domain** dostává plný Hexagonal, Vertical Slice a CQRS. Sem patří modelovací úsilí, čas na refaktoring i investice do testů.
- **Supporting subdomény** mají Layered strukturu – controller, service, entity, repository. Dostatečně dobré, rychlé k napsání, čitelné.
- **Generic subdomény** jsou tenké adaptéry. Žádné agregáty, žádné domain services – jen wrap kolem externí knihovny nebo SaaS API.
- **Uvnitř jednoho Bounded Contextu se styly nemíchají.** Jeden BC = jeden styl. Hybrid znamená „různé BC mají různé styly“, ne „jeden BC má polovinu Hexagonal a polovinu Layered“.

### Cena vs. zisk hybridního přístupu {#hybrid-cena-zisk-heading}

Cena: tým musí umět víc stylů a vědět, kdy který použít. Hybrid proto potřebuje aspoň jednoho seniora, který architekturu hlídá. Mezi BC jsou *nutně* rozdílné konvence a čtenáře kódu to může mást.

Zisk: nejvyšší ROI z modelovacího úsilí. V Core Domain (kde se rozhoduje o konkurenční výhodě) máte čistý model a rychlé testy. V Generic části (kde vendor lock-in není problém, protože SaaS si stejně neměníte každý měsíc) ušetříte stovky hodin nepotřebné izolace.

:::callout{type="pattern"}
### Vzor: Diferencovaná investice {#hybrid-pattern-heading}

Vaughn Vernon v *Implementing Domain-Driven Design* (2013) [[12]](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577) doporučuje investici diferencovat. Největší modelovací úsilí patří Core Domain, Supporting a Generic subdomény si zaslouží méně. U Supporting subdomény se plný taktický návrh vyplatí jen za tří podmínek: tým ho zvládá, model je inovativní a má vydržet roky. Kde neplatí, vystačí pragmatická struktura. Tento test je autorská konstrukce této knihy, ne pravidlo převzaté od Vernona.

Hybridní přístup je pragmatický a DDD literatura ho doporučuje. Tlak na „jednotnou architekturu všude“ jde proti němu.
:::

## 09.09 Anti-vzory napříč styly {#antivzory}

Většina problémů nepramení ze špatné volby stylu, ale ze špatné implementace. Následujících šest anti-vzorů se v Symfony projektech opakuje nejčastěji.

### Anti-vzor 1: Hexagonal kult {#anti-1-heading}

Tým přečte Cockburnův článek a každý CRUD endpoint dostane port + adapter. `GET /api/products/{id}` má port `FindProductById`, adapter `FindProductByIdHttpAdapter`, repository port `ProductRepository`, adapter `DoctrineProductRepository`, mapper `ProductMapper` a use case `FindProductByIdUseCase`. Pro nejtriviálnější operaci máte šest souborů místo dvou.

**Náprava:** Hexagonal aplikujte jen tam, kde je doménová logika. Pro čisté CRUD endpointy (žádné invarianty, žádné stavové přechody, žádné doménové pravidlo) stačí přímý Doctrine dotaz v controlleru. Architektonický styl se používá, když přináší hodnotu; povinnost to není.

### Anti-vzor 2: Domain leakage přes Doctrine atributy {#anti-2-heading}

Klasický Layered problém přenesený do Hexagonal. Tým má `Domain/Port/OrderRepository`, ale třída `Domain/Model/Order.php` má `#[ORM\Entity]`, `#[ORM\Column]`, `#[ORM\OneToMany]`. Doména dál závisí na knihovně Doctrine a cíl izolace padá.

**Náprava (pro Hexagonal/Onion):** zaveďte separátní persistenční třídu (`OrderOrmEntity`) a Mapper, tedy vzor [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern). Cena je dvojí třída a explicitní mapping, zisk čistá doména. Pokud projekt Hexagonal hranici reálně nepotřebuje, atributy přímo na agregátu jsou pragmatický kompromis (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)).

### Anti-vzor 3: Anemic Hexagonal / Anemic Clean {#anti-3-heading}

Strukturálně dokonalý Hexagonal nad anémickou doménou plnou getterů, setterů a logiky odsunuté do handlerů. Podrobný popis je v calloutu [Anti-vzor: Anemic Hexagonal](#hexagonal-anti-heading) v sekci 09.03; totéž platí pro Clean.

**Náprava:** Před zavedením architektonického stylu zkontrolujte, zda váš doménový model má skutečné chování. Pokud ne, vyřešte nejprve modelování. Hexagonal nad anémickým modelem izolaci nepřinese, jen zkomplikuje code review.

### Anti-vzor 4: Port na technologii {#anti-4-heading}

Port se jmenuje `RedisOrderCache`, `SendGridMailer` nebo `RabbitMqPublisher`. Rozhraní kopíruje jméno knihovny, kterou obaluje, a často i tvar jejího API. Cockburn to označuje za hlavní chybu, kterou u svého vzoru v praxi vidí [[4]](https://jmgarridopaz.github.io/content/interviewalistair.html). Jedna technologie na port ruší celý smysl portu, tedy záměnu technologie beze změny jádra. Opačný extrém popisuje [callout o Anemic Hexagonal](#hexagonal-anti-heading). Portem je tam jen repozitář a ostatní výstupní závislosti domény žádné rozhraní nemají.

**Náprava:** Port pojmenujte podle konverzace, kterou doména vede, ne podle technologie na druhém konci. `OrderCache`, `Mailer`, `EventPublisher`. Pod každým z nich může viset víc adaptérů včetně in-memory varianty pro testy. Rozhraní dostanou i zbylé výstupní závislosti (`PaymentGateway`, `EmailSender`), ne jen repozitář.

### Anti-vzor 5: Předčasná inverze závislostí {#anti-5-heading}

Tým si přečte „Dependency Inversion Principle“ a začne otáčet závislosti i tam, kde to nemá smysl. Vznikají rozhraní s jedinou implementací, která se nikdy nemockují. Čtení kódu se zhoršuje („musím skočit do interface a pak najít implementaci“), aniž by to přineslo testovatelnost.

**Náprava:** Inverze závislostí má cenu jen tam, kde existuje aspoň jeden ze dvou důvodů: (1) chcete v testech mockovat tu závislost, (2) plánujete víc implementací (Doctrine + InMemory, SendGrid + Twilio). Pokud ani jeden, interface je zbytečný.

### Anti-vzor 6: Architecture astronaut (astronaut architektury) {#anti-6-heading}

Tým investuje měsíce do „dokonalé architektury“, do osmivrstvové Clean s explicitními BCE rolemi, formálními use case katalogy, presenter třídami a gateway hierarchiemi. Koncový uživatel pořád čeká na první funkci. Architektura se stala cílem sama o sobě.

**Náprava:** *Architektura má vracet investici.* Každá vrstva, vzor i abstrakce musí mít pro projekt konkrétní zisk. Pokud nedokážete za pět minut vysvětlit, jaký reálný problém daná abstrakce řeší, pravděpodobně neřeší žádný a měla by se odstranit.

Detail dalších anti-vzorů (Anemic Domain Model, God Service, Smart UI, Leaky Abstractions) je v samostatné kapitole [Anti-vzory a typické chyby](/anti-vzory).

## 09.10 Symfony 8 specifika všech stylů {#symfony-specifika}

Ať zvolíte kterýkoli styl, v Symfony 8 pracujete se stejnými nástroji: Service Container, Messenger, Doctrine, Form, Security. Liší se jen konvence jejich použití. Následující tři body platí pro všechny styly.

### Bundle vs. namespace organizace {#symfony-bundles-heading}

Symfony dřív stavělo na bundlech jako jednotce modularity. Oficiální [Best Practices](https://symfony.com/doc/current/best_practices.html) dnes radí opak: **bundly na vlastní aplikační logiku nezakládat** a strukturovat `src/` přímo přes namespacy pod `App\`. Bundle se hodí jen pro znovupoužitelné knihovny publikované jako Composer packages, ne pro aplikační moduly. Pravidlo platí pro všechny architektonické styly: aplikačnímu kódu bundly nedají nic, co by nezvládly namespacy a autowiring.

### Konfigurace per-context v Symfony 8 {#symfony-config-heading}

Pokud máte víc Bounded Contexts (Ordering, Billing, Customer, …), můžete pro každý mít vlastní YAML konfiguraci v `config/packages/contexts/`. To je užitečné zejména v hybridním přístupu, kde různé BC mají různé úrovně izolace. Příklad: jen Core Domain BC má explicitní binding portů, ostatní BC spoléhají na auto-wiring.

:::code{language="yaml" filename="config/services.yaml (výřez: importy a vyloučení)" highlights="13,14,15,16,17"}
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

Doménové modely **vylučte z auto-registrace v Service Containeru**. Agregáty, hodnotové objekty a doménové události *nejsou služby*, ale data. Kontejner sám o sobě problém nedělá. Nepoužité privátní služby [Symfony při sestavení odstraní](https://symfony.com/doc/current/service_container.html), takže do entity nikdo nic neinjektuje. Vyloučení je hygiena. Kontejner je menší, konfigurace čitelnější a agregát si nikdo omylem nevyautowiruje jako závislost.

Vyloučení má jeden vedlejší efekt. Vyloučený adresář vypadne i z automatického aliasování rozhraní popsaného [výše](#hexagonal-symfony-di-heading). Port, který v takovém adresáři leží, pak potřebuje alias zapsaný ručně.

### Symfony Messenger jako Command Bus {#symfony-messenger-heading}

Pro všechny styly kromě Layered je Symfony Messenger vhodný nástroj na Command Bus a Event Bus. V Layered se aplikační služba typicky volá přímo z controlleru, takže sběrnici nepotřebuje. V Hexagonal a Clean Architecture se use case typicky dispatchuje jako Command a handler je buď driving adaptér, nebo přímo use case. Konfigurace jednotlivých sběrnic:

:::code{language="yaml" filename="config/packages/messenger.yaml (výřez – plná konfigurace v kapitole o CQRS)"}
# config/packages/messenger.yaml
framework:
    messenger:
        # Při více sběrnicích je default_bus povinný, jinak kontejner
        # odmítne konfiguraci celou.
        default_bus: command.bus

        buses:
            command.bus:
                middleware:
                    # Vyžaduje symfony/validator, jinak kontejner spadne
                    # na "The Validation middleware is only available…".
                    - validation
                    - doctrine_transaction
            query.bus: ~                          # výchozí middleware stačí
            event.bus:
                default_middleware:
                    allow_no_handlers: true   # Eventy mohou mít 0+ konzumentů

        transports:
            async_events: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            # Doménové události zůstávají synchronní; ven jde integrační tvar
            # (viz kapitola Outbox Pattern).
            App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent: async_events
:::

`query.bus` žádné nastavení nepotřebuje. Výchozí `allow_no_handlers: false` odhalí překlep v názvu dotazu už při dispatchi. Druhý přepínač `allow_no_senders` musí zůstat na výchozí hodnotě `true`. Při `false` vyhodí `NoSenderForMessageException` každá zpráva bez transportu, tedy každý synchronní dotaz.

Tři sběrnice (command, query, event) jsou v DDD aplikaci s CQRS obvyklé. Detail konfigurace Messengeru pro DDD je v kapitole [CQRS](/cqrs) a v kapitole [Implementace v Symfony 8](/implementace-v-symfony).

## 09.11 Shrnutí {#summary}

- **Architektonický styl ≠ DDD.** DDD je modelovací technika; architektonický styl je rozhodnutí o uspořádání kódu. Lze je kombinovat libovolně – DDD funguje v Layered, Hexagonal, Onion, Clean i Vertical Slice.
- **Čtyři vrstvové styly stojí na téže myšlence, izolaci domény, ale liší se slovníkem i granularitou.** Hexagonal mluví o portech a adaptérech, Onion o koncentrických vrstvách, Clean o use casech jako prvotřídním konceptu. V praxi se často kombinují do jednoho hybridního stylu.
- **Vertical Slice je ortogonální k vrstvovým stylům.** Popisuje, jak organizovat feature mezi sebou, ne jak strukturovat závislosti uvnitř feature. Hexagonal + Vertical Slice + CQRS je rozšířená výchozí volba v Symfony 8 projektech.
- **Hybridní přístup (různé styly pro různé subdomény) je pragmatický a DDD literatura ho doporučuje.** Modelovací úsilí patří do Core Domain; Supporting a Generic si vystačí s jednodušší strukturou. Každá vrstva architektury musí projektu vrátit, co stojí.

:::faq{}
- question: Hexagonal vs. Onion – jaký je praktický rozdíl?
  answer: 'V běžné Symfony implementaci jsou téměř nerozlišitelné: oba mají interfaces v doméně, implementace v infrastruktuře, závislosti směřují dovnitř. Tři jemné odlišnosti: Hexagonal explicitně dělí driving (inbound) a driven (outbound) porty; Onion staví Domain Services a Application Services jako dvě samostatné vrstvy; Onion je „statický“ model závislostí, Hexagonal „dynamický“ model toku dat. Pokud váš projekt používá Hexagonal slovník (port, adapter), ale uvnitř má Domain Service i Application Service, děláte hybrid. To je v pořádku. Detail v <a href="#onion">sekci o Onion Architecture</a>.'
- question: Můžu použít Hexagonal bez DDD?
  answer: 'Ano, technicky to funguje. Hexagonal řeší <em>jak strukturovat závislosti</em>, zatímco DDD popisuje <em>jak modelovat doménu</em>. Jde o ortogonální dimenze. Můžete mít Hexagonal nad anémickým CRUD modelem a žádné DDD principy nepoužívat. Praktický zisk je ale omezený. Bez bohatého doménového modelu uvnitř je Hexagonal jen vrstvení rituálu, které zhoršuje code review a zpomaluje vývoj. Anti-vzor „Anemic Hexagonal“ je v reálných projektech běžný. Detail v <a href="#anti-3-heading">anti-vzorech</a>.'
- question: Jak migrovat z Layered na Hexagonal v existujícím Symfony projektu?
  answer: 'Strangler Fig pattern: nezačínejte velký rewrite, ale postupně. Vyberte jeden Bounded Context (ideálně Core Domain) a v něm jednu feature. Pro tu feature zaveďte port (interface v Domain/Port/) a adapter (implementace v Infrastructure/), původní Doctrine entitu rozdělte na čistou doménovou třídu + persistenční OrmEntity + Mapper. Otestujte. Iterujte na další feature. Když je hotová celá Core Domain, druhý BC může zůstat v Layered (hybridní přístup). Migrace všeho najednou nese vysoké riziko regresí. Detail strangler fig v kapitole <a href="/migrace-z-crud">Migrace z CRUD na DDD</a>.'
- question: Co je „Port“ přesně a jak se liší od běžného PHP interface?
  answer: 'Port je interface s explicitní architektonickou rolí: definuje hranici mezi doménou a vnějším světem. Technicky je to běžný PHP <code>interface</code>, ale konvenčně žije v adresáři <code>Domain/Port/</code>, nemá framework závislosti a má smysluplné jméno z doménového jazyka (<code>OrderRepository</code>, ne <code>OrderRepositoryInterface</code>). Cockburn rozlišuje driving porty (vnější svět volá doménu) a driven porty (doména volá vnější svět). V Symfony se port na implementaci napojuje aliasem, ten ale u rozhraní s jedinou objevenou implementací vzniká automaticky. Ručně ho zapíšete (v <code>services.yaml</code> nebo atributem <code>#[AsAlias]</code>) až tehdy, když implementací je víc nebo když je adresář vyloučený z <code>resource</code>. Detail v <a href="#hexagonal">sekci o Hexagonal</a>.'
- question: Vyplatí se Clean Architecture v malé Symfony aplikaci?
  answer: 'Spíše ne. Clean Architecture vyžaduje DTO ping-pong: Request DTO → Use Case → Response DTO → Adapter překládá zpět. Je to znatelná režie, pro každou funkci tři až čtyři další třídy. V malé aplikaci s 20–30 endpointy je to čistá ztráta. Vyplatí se až v aplikacích s explicitním seznamem use casů (200+ schopností). Tam je podstatné, aby šlo doložit, „co aplikace umí“, a vstupních kanálů bývá víc (HTTP, CLI, Messenger, GraphQL). Pro malou Symfony aplikaci stačí Layered nebo Hexagonal s méně rituálem. Detail v <a href="#srovnani">rozhodovací matici</a>.'
- question: Jak Vertical Slice zapadá mezi Hexagonal/Onion/Clean?
  answer: 'Vertical Slice je ortogonální k vrstvovým stylům. Hexagonal/Onion/Clean popisují <em>jak strukturovat závislosti uvnitř jedné feature</em>; Vertical Slice popisuje <em>jak organizovat feature mezi sebou</em>. Tyto dvě dimenze lze kombinovat. Každý vertikální slice může uvnitř používat Hexagonal port-adapter strukturu, nebo nemusí. V Symfony projektech je rozšířená kombinace <strong>Hexagonal + Vertical Slice + CQRS přes Symfony Messenger</strong>. Bounded Context má sdílený doménový model, ale aplikační vrstva je rozdělená do feature slice. Detail v <a href="#vertical-slice">sekci 09.06 výše</a>.'
:::

## 09.12 Další četba a citace {#further-reading}

1. Eric Evans – [*Domain-Driven Design: Tackling Complexity in the Heart of Software*](https://www.domainlanguage.com/ddd/) (2003). Originální definice DDD a doporučení vrstvené architektury.
2. Martin Fowler – [*Patterns of Enterprise Application Architecture*](https://martinfowler.com/eaaCatalog/) (2002). Service Layer, Domain Model, Data Mapper a další základní vzory.
3. Alistair Cockburn – [*Hexagonal Architecture (Ports and Adapters)*](https://alistair.cockburn.us/hexagonal-architecture/) (2005). Originální článek o Hexagonal architektuře.
4. Juan Manuel Garrido de Paz – [*Interview with Alistair Cockburn*](https://jmgarridopaz.github.io/content/interviewalistair.html) (2020). Cockburn upřesňuje granularitu portů a nejčastější chyby ve výkladu vzoru.
5. Jeffrey Palermo – [*The Onion Architecture: Part 1*](https://jeffreypalermo.com/2008/07/the-onion-architecture-part-1/) (2008). První ze čtyř blogových postů zavádějících Onion model.
6. Jeffrey Palermo – [*Onion Architecture: Part 4 – After Four Years*](https://jeffreypalermo.com/2013/08/onion-architecture-part-4-after-four-years/) (2013). Čtyři teze vzoru a odstup od DDD, CQRS i IoC kontejneru.
7. Robert C. Martin – [*The Clean Architecture*](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html) (2012). Původní článek o Clean Architecture, který zobecňuje Hexagonal a Onion.
8. Jimmy Bogard – [*Vertical Slice Architecture*](https://www.jimmybogard.com/vertical-slice-architecture/) (2018). Feature-first přístup k organizaci kódu.
9. Microsoft – [*Apply simplified CQRS and DDD patterns in a microservice*](https://learn.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/apply-simplified-microservice-cqrs-ddd-patterns) (.NET microservices architecture guide). Jak feature-orientovaná struktura přirozeně podporuje CQRS.
10. Martin Fowler – [*PresentationDomainDataLayering*](https://martinfowler.com/bliki/PresentationDomainDataLayering.html) (2015). Argument pro moduly na nejvyšší úrovni a vrstvy až uvnitř nich.
11. Kamil Grzybek – [*Modular Monolith: A Primer*](https://www.kamilgrzybek.com/blog/posts/modular-monolith-primer) (2019). Definice modulu a jeho veřejného API v modulárním monolitu.
12. Vaughn Vernon – [*Implementing Domain-Driven Design*](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577) (2013). Praktický průvodce DDD s ukázkami architektonických stylů.
13. Herberto Graça – [*DDD, Hexagonal, Onion, Clean, CQRS, … How I put it all together*](https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together/) (2017). Hybridní pohled na kombinaci stylů.
14. Martin Fowler – [*Anemic Domain Model*](https://martinfowler.com/bliki/AnemicDomainModel.html) (2003). Klasický článek popisující anti-vzor anémického modelu.
15. **Cockburn, A. & Garrido de Paz, J. M.** (2024). *Hexagonal Architecture Explained: How the Ports & Adapters Architecture Simplifies Your Life, and How to Implement It.* Humans & Technology Press. ISBN 978-1-7375197-8-2. Knižní zpracování vzoru od jeho autora.
16. **Martin, R. C.** (2017). *Clean Architecture: A Craftsman's Guide to Software Structure and Design.* Prentice Hall. Knižní rozvedení článku z roku 2012.
