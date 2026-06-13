---
route: cqrs
path: /cqrs
title: CQRS v Symfony 8
page_title: "CQRS v Symfony 8 | DDD Symfony"
meta_description: "CQRS v Symfony 8: oddělení command a query strany přes Messenger, čtecí modely, eventual consistency a praktická konfigurace bus alias."
meta_keywords: "CQRS, Command Query Responsibility Segregation, Symfony Messenger, bounded contexts, doménové modely, příkazy, dotazy, command handlers, query handlers, asynchronní zpracování, Event Sourcing, DDD, Symfony 8, read model, eventual consistency, ViewModel, projekce, dead letter queue"
og_type: article
published: "2025-04-24"
modified: "2026-06-09"
breadcrumb_name: CQRS
schema_type: TechArticle
schema_headline: "CQRS v Symfony 8"
chapter_number: "12"
category: Vzory
deck: 'Implementace CQRS (Command Query Responsibility Segregation) v Symfony 8 s využitím DDD principů – oddělení operací čtení a zápisu, optimalizace read modelů, řešení eventual consistency a stavba škálovatelných aplikací.'
reading_time: 35
difficulty: 3
github_examples: Chapter05_CQRS
---

## 12.01 Co je CQRS? {#what-is-cqrs}

CQRS vychází z prostého pozorování: **model, který slouží k zápisu dat, nemusí být tentýž model,
který slouží k jejich čtení**. CQRS (Command Query Responsibility Segregation) tento princip
přenáší na úroveň architektury – popsal jej Greg Young
[[1]](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf)
jako rozšíření Command-Query Separation (CQS) od Bertranda Meyera
[[2]](https://martinfowler.com/bliki/CommandQuerySeparation.html).

V tradičních aplikacích používáme jednu entitu (např. Doctrine ORM entity)
pro obojí – vytváříme objednávku i zobrazujeme seznam objednávek přes tentýž objekt `Order`.
CQRS tuto zodpovědnost explicitně rozděluje do dvou oddělených modelů, z nichž každý
nese vlastní úkol a vlastní optimalizační profil.

:::callout{type="note"}
### Základní principy CQRS: {#zakladni-principy-heading}

- **Commands** – Příkazy, které mění stav systému. Ve striktním pojetí CQS nevracejí žádná data; v praxi CQRS mohou vracet identifikátor vytvořeného záznamu.
- **Queries** – Dotazy, které vracejí data, ale nemění stav systému.
- **Oddělené modely** – Write model (bohatý doménový model s doménovou logikou) a Read model (jednoduchá denormalizovaná datová struktura optimalizovaná pro dotazy).
- **Oddělené databáze** – V pokročilých implementacích lze čtení a zápis rozdělit do oddělených databází a nezávisle škálovat zátěž.
:::

CQRS se často kombinuje s [Event Sourcing](/event-sourcing),
což je vzor, který místo aktuálního stavu ukládá historii změn jako sekvenci událostí.
Tyto dva vzory jsou však **nezávislé**. CQRS lze plnohodnotně implementovat
s klasickou Doctrine ORM persistencí na write straně a denormalizovanými tabulkami na straně čtení,
aniž by se sahalo po Event Sourcingu.

## 12.02 CQS vs. CQRS – kde je hranice? {#cqs-vs-cqrs}

Bertrand Meyer formuloval princip **Command-Query Separation (CQS)** jako pravidlo
na úrovni metod: každá metoda by měla buď měnit stav (command), nebo vracet hodnotu (query),
ale nikdy obojí. CQS je návrhové pravidlo pro rozhraní tříd.

Greg Young posunul tuto myšlenku na **architektonickou úroveň**: CQRS není pravidlo
pro jednotlivé metody, ale rozhodnutí o struktuře celé aplikace. Místo jednoho doménového modelu
vznikají dva oddělené modely – každý s vlastní sadou tříd, vlastním úložištěm a vlastním
optimalizačním profilem.

:::callout{type="pattern"}
### CQS vs. CQRS – přehled {#cqs-vs-cqrs-tabulka-heading}

| Aspekt | CQS | CQRS |
|---|---|---|
| Úroveň | Metoda / třída | Architektura celé aplikace |
| Pravidlo | Metoda buď mění stav, nebo vrací data | Oddělený write model a read model |
| Počet modelů | Jeden sdílený model | Dva (nebo více) oddělených modelů |
| Databáze | Sdílená | Může být oddělená (write DB + read DB) |
| Složitost | Nízká – jde o konvenci | Střední až vysoká – jde o architekturu |
| Příklad | `getBalance()` nemodifikuje účet | `RegisterUserHandler` a `GetUserProfileHandler` pracují s různými datovými strukturami |
:::

V praxi se CQS přirozeně stává výchozím bodem pro CQRS. Pokud dodržujete CQS na úrovni metod,
zjistíte, že metody měnící stav (command methods) potřebují výrazně jiná data než ty,
které jej čtou (query methods). CQRS toto pozorování formalizuje rozdělením do dvou explicitních modelů.

:::callout{type="note"}
### Úrovně zavedení CQRS {#cqrs-urovne-heading}

CQRS lze zavést v několika úrovních hloubky, od nejjednodušší po nejpokročilejší:

1. **Oddělené handlery** – Command handlers a query handlers jako samostatné třídy, ale sdílejí tutéž databázi a ORM entity. Nejjednodušší forma CQRS, vhodná pro většinu aplikací.
2. **Oddělené modely** – Write model staví na doménových entitách (Doctrine ORM), read strana využívá vlastní DTO/ViewModely plněné přímým SQL nebo Doctrine DBAL. Sdílená databáze, ale oddělené PHP třídy.
3. **Oddělená úložiště** – Write databáze (PostgreSQL) a read databáze (Elasticsearch, Redis, denormalizované tabulky). Změny se propagují asynchronně přes události.
4. **CQRS + Event Sourcing** – Write side ukládá události do [Event Store](/event-sourcing), read side buduje projekce z event streamu. Nejvyšší složitost, ale i nejvyšší flexibilita.

Doporučený přístup: začněte na úrovni 1 nebo 2. Na úroveň 3 a 4 přejděte teprve tehdy,
když to vyžadují konkrétní škálovací nebo doménové požadavky. Pokud máte existující
CRUD aplikaci, postup migrace popisuje kapitola [Migrace z CRUD](/migrace-z-crud).

CQRS se v DDD obvykle nasazuje **per Bounded Context**,
nikoli globálně na celou aplikaci. Core doména s komplexní logikou může těžit z plného CQRS
(úroveň 3–4), zatímco podpůrné kontexty (notifikace, administrace) si vystačí s jednoduchým
CRUD – viz [Bounded Contexts](/zakladni-koncepty#bounded-contexts).
:::

## 12.03 Výhody CQRS {#benefits}

CQRS přináší architektonické výhody zejména u aplikací
s netriviální doménovou logikou a odlišnými požadavky na čtení a zápis:

- **Oddělení odpovědností** – Write model nese doménovou logiku, validaci invariantů
  a konzistenci dat. Read straně zbývá jediný úkol: dostat data v podobě, kterou potřebuje
  obrazovka. Každý model obsahuje jen to, co ke své práci potřebuje.
- **Nezávislá optimalizace** – Write model může používat normalizované relační schéma
  a Doctrine ORM entity s bohatou doménovou logikou. Na straně čtení poslouží denormalizovaná tabulka,
  Elasticsearch index nebo Redis cache – cokoli, co nejlépe vyhovuje konkrétním dotazům.
- **Škálovatelnost** – Ve většině aplikací výrazně převažuje čtení nad zápisem
  (poměr 10:1 až 100:1). CQRS umožňuje nezávisle škálovat read stranu (repliky, cache, CDN)
  bez dopadu na write stranu.
- **Testovatelnost** – Command handlers se testují jako čistě doménová logika
  (given state → when command → then events/state). U query handlerů se ověřuje jen správnost
  vrácených dat. Žádné propletení obou odpovědností v jedné testovací sadě.
  Viz kapitola [Testování DDD kódu](/testovani-ddd).
- **Flexibilita evoluce** – Read model lze kdykoli přebudovat (rebuild projekcí),
  přidat nový read model pro nový use case nebo změnit strukturu dotazu – bez jakéhokoli
  dopadu na write model a doménovou logiku.

## 12.04 Výzvy a omezení CQRS {#challenges}

CQRS má své limity. Kompromisy, které přináší, je lepší znát ještě před zavedením.

Místo jednoho modelu existují dva (nebo více) a každý command či query vyžaduje vlastní
třídu, handler a často i vlastní datovou strukturu – pro jednoduchou CRUD operaci 4–6 tříd
místo jedné. Při oddělených úložištích se přidává synchronizace: read model se musí aktualizovat
po každé změně write modelu, aby se nedostal mimo. Selhání propagace (výpadek fronty, chyba
projektoru) vede k divergenci modelů.

Sem patří i eventual consistency. Mezi zápisem a aktualizací read modelu vzniká okno,
kdy uživatel po odeslání formuláře vidí „starou“ verzi dat. Vzory pro UI popisuje
[sekce Eventual Consistency](#eventual-consistency).

Poslední cenou je učební křivka. CQRS vyžaduje změnu myšlení oproti tradičnímu přístupu,
kde jeden model pokrývá všechny operace. Vývojáři musejí porozumět konceptům jako message bus,
eventual consistency, idempotence handlerů a read model projekce.

:::callout{type="warn"}
### Kdy nepoužívat CQRS {#when-not-to-use-cqrs-heading}

CQRS nemusí být vhodný pro všechny projekty. Nepoužívejte CQRS, pokud:

- Vyvíjíte jednoduchou aplikaci s minimální doménovou logikou – klasický CRUD
  s Doctrine ORM má méně tříd a kratší cestu od formuláře k databázi.
- Požadavky na čtení a zápis jsou téměř identické – CQRS přináší hodnotu teprve tehdy,
  když se datové struktury pro zápis a čtení výrazně liší.
- Nemáte potřebu škálovat operace čtení a zápisu nezávisle – pokud celá aplikace
  běží na jednom serveru a zvládá zátěž, oddělená infrastruktura je zbytečná režie.
- Váš tým nemá zkušenosti s asynchronním zpracováním – eventual consistency problémy
  mohou být frustrující bez předchozí zkušenosti s distribuovanými systémy.

Dobrým kompromisem je začít s CQRS na úrovni 1 (oddělené handlery, sdílená databáze)
a rozšiřovat postupně. Viz také
[Anti-vzory – Over-engineering u jednoduchých aplikací](/anti-vzory#over-engineering).
:::

## 12.05 Symfony Messenger jako základ CQRS {#symfony-messenger}

Pro CQRS je na komponentě Symfony Messenger podstatná jedna schopnost: definovat
**více message busů** – jeden pro příkazy (command bus) a jeden pro dotazy (query bus).
Každý bus může mít vlastní sadu middleware, vlastní transport a vlastní strategii zpracování.

:::diagram{fig="12.5-A" title="Symfony Messenger jako CQRS bus" src="images/diagrams/6_cqrs/diagram.svg"}
:::

:::callout{type="pattern"}
### Konfigurace Symfony Messenger pro CQRS {#messenger-config-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml"}
# config/packages/messenger.yaml
framework:
    messenger:
        # Výchozí bus - použitý, když není specifikovaný jiný
        default_bus: command.bus

        # Konfigurace transportů
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
            sync: 'sync://'

        # Konfigurace busů
        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction

            query.bus:
                middleware:
                    - validation

        # Směrování zpráv - mapuje konkrétní třídy nebo rozhraní na transport
        routing:
            # Příkazy vhodné pro asynchronní zpracování:
            # operace, kde uživatel nemusí čekat na výsledek
            App\Notification\Application\Command\SendWelcomeEmail: async
            App\Reporting\Application\Command\GenerateMonthlyReport: async

            # Dotazy jsou zpracovány synchronně (výchozí, není třeba uvádět)
            # App\UserManagement\Profile\Query\GetUserProfile: sync
:::
:::

Konfigurace definuje dva transporty: `async` pro zpracování přes frontu a `sync` pro okamžité vykonání v témže procesu.
Dva message busy: `command.bus` pro příkazy s `doctrine_transaction`
middleware (automatická transakce kolem handleru) a `query.bus` pro dotazy pouze s validací.

:::callout{type="warn"}
### `doctrine_transaction` middleware vs. „jeden agregát = jedna transakce“ {#doctrine-transaction-konflikt-heading}

`doctrine_transaction` middleware obaluje **celý handler** do jedné transakce.
To znamená, že pokud handler dělá `save()` na dva různé agregáty, oba se zapíší
atomicky. To je v rozporu s pravidlem z [Návrh agregátu – Transakční konzistence](/navrh-agregatu#transactional-consistency):
*„jeden command modifikuje právě jeden agregát“*.

Dvě použitelné strategie:

- **Striktní DDD:** middleware necháte zapnutý, ale **pravidlo „1 command = 1 agregát“
  vynucuje code review**. Middleware pak slouží jen jako pojistka pro idiomatické
  uložení outboxu + agregátu uvnitř `save()` repozitáře. Pokud někdo poruší
  pravidlo, atomicita transakcí mu kompenzuje chybu, ale nikoli architektonický dluh.
- **Bez `doctrine_transaction`:** middleware vypnete a transakční hranice si řídí
  repozitář explicitně přes `EntityManager::wrapInTransaction()` v metodě
  `save()`. Komplikovanější nastavení, ale handler dostane garantovanou izolaci
  „1 save = 1 transakce“ a víc agregátů v jednom commandu prostě nelze atomicky uložit
  (což je správně).

V průvodci pokračujeme se zapnutým middleware – pro většinu projektů je to
pragmatický kompromis, sledování pravidla „1 agregát = 1 transakce“ pak patří
do code review.
:::

:::callout{type="note"}
### Proč dva oddělené busy? {#proc-dva-busy-heading}

Oddělení command busu a query busu má konkrétní praktické důsledky:

- **Různý middleware** – Command bus potřebuje `doctrine_transaction` jako pojistku,
  query bus ne (jen čte data).
- **Různé transporty** – Commands mohou být směrovány na async transport
  (frontu). Queries jsou vždy synchronní – uživatel čeká na odpověď.
- **Type safety** – Pokud controller injektuje `$commandBus`,
  je jasné, že dispatchuje příkaz. Pokud injektuje `$queryBus`,
  je jasné, že čte data. To zlepšuje čitelnost kódu a brání náhodným záměnám.
:::

:::callout{type="note"}
### Jak vybrat příkazy pro asynchronní zpracování {#async-poznamka-heading}

**Asynchronně** zpracovávejte operace, kde uživatel nemusí čekat na výsledek –
odesílání e-mailů, generování reportů, aktualizace read modelů, notifikace.

**Synchronně** zpracovávejte operace vyžadující okamžitou zpětnou vazbu –
registrace uživatele, vytvoření objednávky, přihlášení. Uživatel čeká na odpověď
(úspěch / chyba validace) a potřebuje ji okamžitě.
:::

## 12.06 Implementace Commands {#commands}

Commands v CQRS jsou příkazy, které mění stav systému. V Symfony 8 se implementují jako jednoduché
PHP třídy – immutabilní datové objekty (DTO), které nesou veškerá data potřebná pro vykonání operace.
Command sám o sobě neobsahuje žádnou doménovou logiku; je to pouhý přepravní kontejner dat.

Dobře navržený command má několik vlastností:

- Je **immutabilní** (`readonly` properties) – po vytvoření se nemění.
- Obsahuje **validační atributy** – díky middleware `validation` na command busu se command validuje ještě před předáním handleru.
- Pojmenování vyjadřuje **záměr** – `RegisterUser`, `PlaceOrder`, `CancelSubscription`. Ne `SaveUser` nebo `UpdateOrder`.
- Pracuje typicky s primitivními typy (string, int, float) nebo serializovatelnými hodnotovými objekty (např. `OrderId`, `Money`). Command musí jít bezpečně přenést přes asynchronní kanál.

:::callout{type="pattern"}
### PHP: Implementace příkazu v Symfony 8 {#command-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUser.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Příkaz pro registraci nového uživatele.
 * Immutabilní DTO - neslouží k doménové logice, pouze přenáší data.
 */
final class RegisterUser
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $name,

        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public readonly string $password
    ) {
    }
}
:::
:::

V tomto příkladu je `RegisterUser` příkaz, který obsahuje data potřebná pro registraci uživatele.
Příkaz používá PHP atributy pro validaci dat – ta proběhne automaticky díky `validation` middleware
na command busu, ještě než se command dostane k handleru.

:::callout{type="warn"}
### Mají commands vracet hodnotu? {#command-navratova-hodnota-heading}

Ve striktním CQS pojetí commands nevracejí žádná data. V praxi CQRS však existují legitimní
scénáře, kdy je užitečné vrátit alespoň identifikátor nově vytvořeného záznamu. Dva běžné přístupy:

- **ID generovat na klientovi** – Command obsahuje `$userId` jako UUID
  vygenerované před dispatchem. Handler toto ID použije. Klient zná ID okamžitě, command nemusí
  nic vracet. Toto je preferovaný přístup.
- **ID vracet z handleru** – Handler vrátí ID přes `HandledStamp`.
  Jednodušší na implementaci, ale porušuje striktní CQS. **Nefunguje pro asynchronní transport** – handler běží v jiném procesu a výsledek přes HandledStamp do původního requestu nedoputuje.
:::

## 12.07 Implementace Queries {#queries}

Queries v CQRS jsou dotazy, které vracejí data bez změny stavu systému. Podobně jako commands
se implementují jako immutabilní DTO třídy, ale na rozdíl od commands **vždy vracejí
hodnotu** – handler vrací data přes `HandledStamp`.

:::callout{type="pattern"}
### PHP: Implementace dotazu v Symfony 8 {#query-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/Query/GetUserProfile.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class GetUserProfile
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId
    ) {
    }
}
:::
:::

Dotaz nese jediné pole: ID uživatele, jehož profil chceme získat. Nevalidní UUID odmítne
`validation` middleware ještě před zpracováním.

:::callout{type="note"}
### Queries s filtrováním a stránkováním {#query-slozitejsi-heading}

Reálné aplikace potřebují dotazy složitější než pouhé „dej mi záznam podle ID“. Queries
mohou obsahovat filtrovací kritéria, řazení a stránkování:

:::code{language="php" filename="src/Ordering/Application/Query/ListOrders.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class ListOrders
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $customerId,

        public readonly ?string $status = null,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 20,

        #[Assert\PositiveOrZero]
        public readonly int $offset = 0,

        public readonly string $sortBy = 'createdAt',
        public readonly string $sortDirection = 'DESC',
    ) {
    }
}
:::
:::

## 12.08 Implementace Handlers {#handlers}

Handler je místo, kde se zpráva potká s logikou. V Symfony 8 jde o třídu s atributem
`AsMessageHandler` a metodou `__invoke()`.
Symfony Messenger automaticky spojí handler s jeho command/query podle type-hintu parametru.

Command handler a query handler mají odlišnou odpovědnost:

- **Command handler** – Načte agregát z repozitáře, zavolá na něm doménovou metodu
  (která validuje invarianty) a uloží změny. Může emitovat doménové události.
  Pracuje s **doménovým modelem** (entity, value objects, repozitáře).
- **Query handler** – Čte data z optimalizovaného zdroje (denormalizovaná tabulka,
  Elasticsearch, cache) a vrací je jako ViewModel. **Nepracuje s doménovým modelem**
  – obchází ho záměrně, protože doménový model není optimalizovaný pro čtení.

:::callout{type="pattern"}
### PHP: Command handler – RegisterUserHandler {#command-handler-heading}

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUserHandler.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\Service\PasswordHasher;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RegisterUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher
    ) {
    }

    public function __invoke(RegisterUser $command): void
    {
        $email = new Email($command->email);

        if ($this->userRepository->findByEmail($email)) {
            throw new \DomainException('User with this email already exists');
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $command->password
        );

        $user = User::register(
            new UserId(),
            $command->name,
            $email,
            $hashedPassword
        );

        $this->userRepository->save($user);
    }
}
:::
:::

:::callout{type="note"}
**Pozn.:** Tato varianta používá `PasswordHasher` jako závislost handleru.
Alternativní přístup s `HashedPassword` hodnotovým objektem ukazuje kapitola
[Implementace v Symfony](/implementace-v-symfony).
:::

:::callout{type="pattern"}
### PHP: Query handler – GetUserProfileHandler {#query-handler-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/Query/GetUserProfileHandler.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Query;

use App\UserManagement\Profile\ReadModel\UserProfileReadRepository;
use App\UserManagement\Profile\ViewModel\UserProfileViewModel;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetUserProfileHandler
{
    public function __construct(
        private UserProfileReadRepository $readRepository
    ) {
    }

    public function __invoke(GetUserProfile $query): ?UserProfileViewModel
    {
        return $this->readRepository->findById($query->userId);
    }
}
:::
:::

Rozdíl je vidět přímo v závislostech: command handler pracuje s doménovým modelem (`UserRepository`,
`User` entita, value objects), zatímco query handler sahá do **read repozitáře**
(`UserProfileReadRepository`), který vrací přímo ViewModel – jednoduchou datovou strukturu
optimalizovanou pro prezentaci. Query handler neprochází přes doménový model.

## 12.09 ViewModely a Read Modely {#view-models}

ViewModel (nebo Read Model) je datová struktura navržená výhradně pro potřeby konkrétního dotazu
nebo obrazovky. Na rozdíl od doménové entity neobsahuje žádnou doménovou logiku – je to čistě
prezentační objekt. Zatímco doménová entita `User` chrání invarianty a zapouzdřuje
chování, ViewModel `UserProfileViewModel` obsahuje přesně ta data, která potřebuje
šablona nebo API endpoint.

:::callout{type="pattern"}
### PHP: UserProfileViewModel {#viewmodel-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/ViewModel/UserProfileViewModel.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\ViewModel;

/**
 * Read model pro zobrazení uživatelského profilu.
 * Obsahuje pouze data potřebná pro prezentaci - žádná doménová logika.
 */
final readonly class UserProfileViewModel
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public \DateTimeImmutable $registeredAt,
        public int $totalOrders,
        public string $membershipTier,
    ) {
    }
}
:::
:::

ViewModel často obsahuje **data z více agregátů** – v příkladu výše kombinuje
údaje o uživateli s počtem objednávek a členskou úrovní. Sestavení téhož pohledu přes doménový model by vyžadovalo
načtení uživatele, jeho objednávek a propočet úrovně – pomalé a porušující hranice
[agregátů](/zakladni-koncepty#aggregates). Read model tato data drží
připravená v denormalizované podobě.

:::callout{type="pattern"}
### PHP: Read repozitář s přímým SQL (Doctrine DBAL) {#read-repository-example-heading}

:::code{language="php" filename="src/UserManagement/Infrastructure/ReadModel/DbalUserProfileReadRepository.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\ReadModel;

use App\UserManagement\Profile\ReadModel\UserProfileReadRepository;
use App\UserManagement\Profile\ViewModel\UserProfileViewModel;
use Doctrine\DBAL\Connection;

final class DbalUserProfileReadRepository implements UserProfileReadRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function findById(string $userId): ?UserProfileViewModel
    {
        $row = $this->connection->fetchAssociative(
            'SELECT u.id, u.name, u.email, u.registered_at,
                    COUNT(o.id) AS total_orders,
                    COALESCE(m.tier, :defaultTier) AS membership_tier
               FROM users u
          LEFT JOIN orders o ON o.customer_id = u.id
          LEFT JOIN memberships m ON m.user_id = u.id
              WHERE u.id = :userId
           GROUP BY u.id',
            ['userId' => $userId, 'defaultTier' => 'standard'],
        );

        if (!$row) {
            return null;
        }

        return new UserProfileViewModel(
            userId: $row['id'],
            name: $row['name'],
            email: $row['email'],
            registeredAt: new \DateTimeImmutable($row['registered_at']),
            totalOrders: (int) $row['total_orders'],
            membershipTier: $row['membership_tier'],
        );
    }
}
:::
:::

:::callout{type="note"}
### Proč ne Doctrine ORM pro read stranu? {#read-model-poznamka-heading}

Doctrine ORM je stavěný pro práci s doménovým modelem – mapuje entity, řeší vztahy,
lazy loading, identity map a unit of work. Pro read stranu CQRS je to zbytečná režie.
Read model potřebuje pouze **načíst data a namapovat je na ViewModel**.
Doctrine DBAL (přímý SQL přes Connection) nebo nativní PDO je pro tento účel rychlejší,
jednodušší a bez rizika N+1 problémů. O výkonnostních dopadech pojednává
kapitola [Výkonnostní aspekty](/vykonnostni-aspekty).
:::

## 12.10 Implementace Command a Query Buses {#buses}

Zbývá dopravit příkazy a dotazy ke správnému handleru. V Symfony 8 se pro injektování busu
používá named autowiring – názvy parametrů v konstruktoru musejí odpovídat konfiguraci
v `messenger.yaml`:

:::callout{type="pattern"}
### PHP: Použití command busu v controlleru {#buses-example-heading}

:::code{language="php" filename="src/UserManagement/Registration/Controller/RegistrationController.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Controller;

use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $command = new RegisterUser(
                $data['name'],
                $data['email'],
                $data['password']
            );

            try {
                $this->commandBus->dispatch($command);

                $this->addFlash('success', 'Váš účet byl vytvořen. Nyní se můžete přihlásit.');

                return $this->redirectToRoute('app_login');
            } catch (HandlerFailedException $e) {
                $domainError = null;

                foreach ($e->getWrappedExceptions() as $wrapped) {
                    if ($wrapped instanceof \DomainException) {
                        $domainError = $wrapped;
                        break;
                    }
                }

                if ($domainError === null) {
                    throw $e; // neznámou chybu nemaskovat
                }

                $this->addFlash('error', $domainError->getMessage());
            }
        }

        return $this->render('@UserManagement/Registration/View/registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
:::
:::

:::callout{type="warn"}
### Messenger balí výjimky {#messenger-bali-vyjimky-heading}

Synchronní Messenger nepropaguje výjimku z handleru přímo – balí ji do
`HandlerFailedException`. Blok `catch (\DomainException $e)` kolem `dispatch()`
by proto nikdy nic nechytil. Controller chytá obálku, zabalené výjimky projde
přes `getWrappedExceptions()` a neznámé typy posílá dál. Podrobnější rozbor
včetně dekorátoru busu, který rozbalování centralizuje, obsahuje kapitola
[Implementace v Symfony](/implementace-v-symfony#handler-failed-exception-heading).
:::

:::callout{type="pattern"}
### PHP: Použití query busu v controlleru {#query-bus-example-heading}

:::code{language="php" filename="src/UserManagement/Profile/Controller/ProfileController.php"}
<?php

declare(strict_types=1);

namespace App\UserManagement\Profile\Controller;

use App\UserManagement\Profile\Query\GetUserProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class ProfileController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus
    ) {
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(UserInterface $user): Response
    {
        $query = new GetUserProfile($user->getUserIdentifier());

        $envelope = $this->queryBus->dispatch($query);
        $profile = $envelope->last(HandledStamp::class)->getResult();

        if (!$profile) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('@UserManagement/Profile/View/profile.html.twig', [
            'profile' => $profile,
        ]);
    }
}
:::
:::

V těchto příkladech Symfony přiřadí bus podle názvu parametru v konstruktoru: klíč
`command.bus` z konfigurace `buses` se namapuje na `$commandBus`.

Tím končí popis základní infrastruktury CQRS – příkazů, dotazů, handlerů a busů.
Následující sekce se věnují pokročilejším aspektům: optimalizaci read strany
pro konkrétní dotazy, eventual consistency a provozním problémům
v asynchronním prostředí.

## 12.11 Optimalizace Read Modelů {#read-model-optimalizace}

Read strana má volnou ruku ve výběru struktury. Write model drží normalizaci kvůli konzistenci dat;
read model může jít opačným směrem – denormalizovat data přesně do tvaru, který obrazovka
nebo API endpoint očekává.

### Strategie optimalizace read modelů

:::callout{type="note"}
### Přehled strategií {#read-strategie-heading}

| Strategie | Popis | Vhodné pro | Složitost |
|---|---|---|---|
| Přímý SQL (DBAL) | Query handler čte z téže DB přes Doctrine DBAL, obchází ORM | Většinu aplikací na úrovni 1–2 | Nízká |
| Denormalizované tabulky | Separátní tabulky s předpočítanými daty, aktualizované přes eventy | Složité dashboard dotazy, reporting | Střední |
| Materialized views (DB) | Databázové materialized views refreshované periodicky nebo triggerem | Agregační dotazy nad velkými daty | Střední |
| Elasticsearch / Meilisearch | Fulltextový engine jako read store, plněný asynchronně z eventů | Fulltextové vyhledávání, faceted search | Vysoká |
| Redis cache | Hotová data serializovaná do Redis, invalidace přes eventy | Vysoká čtecí zátěž, nízká latence | Střední |
:::

### Denormalizované tabulky jako read model

Nejrozšířenější strategií v praxi je **denormalizovaná tabulka**,
která drží data předpočítaná pro jedinou obrazovku či endpoint.
Tabulka se aktualizuje asynchronně přes doménové události.

:::callout{type="pattern"}
### PHP: Projektor aktualizující denormalizovanou tabulku {#denorm-projekce-heading}

:::code{language="php" filename="src/Ordering/Infrastructure/Projection/OrderDashboardProjector.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Projection;

use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderShipped;
use App\Ordering\Domain\Event\OrderCancelled;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Asynchronní projektor: naslouchá doménovým událostem a aktualizuje
 * denormalizovanou tabulku order_dashboard, optimalizovanou pro
 * obrazovku "Přehled objednávek".
 */
#[AsMessageHandler]
final class OrderDashboardProjector
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(OrderPlaced|OrderShipped|OrderCancelled $event): void
    {
        match (true) {
            $event instanceof OrderPlaced => $this->onOrderPlaced($event),
            $event instanceof OrderShipped => $this->onOrderShipped($event),
            $event instanceof OrderCancelled => $this->onOrderCancelled($event),
        };
    }

    private function onOrderPlaced(OrderPlaced $event): void
    {
        $this->connection->executeStatement(
            'INSERT INTO order_dashboard
                (order_id, customer_name, total_amount, status, placed_at, updated_at)
             VALUES (:orderId, :customerName, :totalAmount, :status, :placedAt, :updatedAt)
             ON DUPLICATE KEY UPDATE
                status = VALUES(status), updated_at = VALUES(updated_at)',
            [
                'orderId'      => $event->orderId(),
                'customerName' => $event->customerName(),
                'totalAmount'  => $event->totalAmount(),
                'status'       => 'placed',
                'placedAt'     => $event->occurredOn()->format('Y-m-d H:i:s'),
                'updatedAt'    => $event->occurredOn()->format('Y-m-d H:i:s'),
            ],
        );
    }

    private function onOrderShipped(OrderShipped $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_dashboard
                SET status = :status,
                    tracking_number = :trackingNumber,
                    updated_at = :updatedAt
              WHERE order_id = :orderId',
            [
                'orderId'        => $event->orderId(),
                'status'         => 'shipped',
                'trackingNumber' => $event->trackingNumber(),
                'updatedAt'      => $event->occurredOn()->format('Y-m-d H:i:s'),
            ],
        );
    }

    private function onOrderCancelled(OrderCancelled $event): void
    {
        $this->connection->executeStatement(
            'UPDATE order_dashboard
                SET status = :status, updated_at = :updatedAt
              WHERE order_id = :orderId',
            [
                'orderId'   => $event->orderId(),
                'status'    => 'cancelled',
                'updatedAt' => $event->occurredOn()->format('Y-m-d H:i:s'),
            ],
        );
    }
}
:::
:::

:::callout{type="note"}
### Idempotence projektorů {#idempotence-heading}

Při asynchronním zpracování může být událost doručena **více než jednou**
(at-least-once delivery). Projektor proto musí být **idempotentní** – opakované
zpracování téže události nesmí vést k nesprávným datům. V příkladu výše je idempotence
zajištěna konstrukcí `ON DUPLICATE KEY UPDATE`, která při opakovaném insertu
provede pouze update. Alternativní přístupy:

- **Sledování pozice** – projektor si ukládá pozici posledního zpracovaného
  eventu (event ID nebo sequence number) a ignoruje události se stejnou nebo nižší pozicí.
- **Upsert/Merge** – `INSERT ... ON CONFLICT DO UPDATE` (PostgreSQL)
  nebo `REPLACE INTO` (MySQL). Jednoduchý, ale méně flexibilní.

Podrobněji o idempotenci projektorů a dalších praktických problémech pojednává kapitola
[Event Sourcing – Praktické problémy projekcí](/event-sourcing#prakticke-problemy-projekci).
:::

### Kdo doménové události odešle

Projektor výše předpokládá, že mu události `OrderPlaced` či `OrderShipped` někdo
doručí. V nejjednodušší podobě je po `flush()` vyzvedne aplikační vrstva z agregátu
metodou `releaseEvents()` a dispatchne je na event bus – celý mechanismus popisuje
sekce [Agregát a doménové události: lifecycle](/zakladni-koncepty#aggregate-root-lifecycle).
Pro vývoj a méně kritické projekce tato synchronní cesta stačí.

Má ale slabé místo: dispatch po flushi není atomický. Spadne-li proces mezi commitem
transakce a odesláním do fronty, událost se ztratí a projekce tiše diverguje od write
modelu. Produkční řešení ukládá události do outbox tabulky ve stejné transakci jako
agregát a do fronty je publikuje samostatný relay proces – podrobně v kapitole
[Outbox Pattern](/outbox-pattern).

### Rebuild projekcí

CQRS s asynchronními projekcemi umožňuje **kompletní rebuild read modelu**.
Pokud se změní struktura denormalizované tabulky (nový sloupec, jiný formát dat), stačí:

1. Vytvořit novou verzi projekční tabulky.
2. Přehrát všechny relevantní události přes projektor.
3. Přepnout read dotazy na novou tabulku.
4. Smazat starou tabulku.

Tento přístup je realizovatelný pouze tehdy, jsou-li zdrojové události stále dostupné
(v [Event Store](/event-sourcing) nebo v message logu).
Bez Event Sourcingu je rebuild projekcí možný, ale musíte mít alternativní zdroj dat
(např. change data capture z write databáze).

## 12.12 Eventual Consistency v praxi {#eventual-consistency}

Eventual consistency je nejčastějším zdrojem nejistoty při zavádění CQRS. Při asynchronní
propagaci změn z write strany na read stranu existuje **časové okno** (typicky
milisekundy až jednotky sekund), kdy read model ještě neodráží poslední zápis. Uživatel
odešle formulář, dostane potvrzení o úspěchu, ale seznam na další stránce ještě nezobrazuje
nový záznam.

Eventual consistency je **vlastnost distribuované architektury**, ne bug.
Následující diagram zachycuje celý datový tok – od zápisu přes asynchronní propagaci
až po čtení – a zvýrazňuje okno, ve kterém k eventual consistency dochází:

:::diagram{fig="12.12-A" title="Eventual consistency v CQRS toku" src="images/diagrams/6_cqrs/eventual_consistency.svg"}
:::

Konkrétnější časový pohled na to, kdy uživatel vidí 404 navzdory tomu, že command
proběhl úspěšně, je v následující sekvenci:

:::diagram{fig="12.12-B" title="Okno zastaralosti – kdy GET vrátí 404 po úspěšném POST" src="images/diagrams/6_cqrs/staleness_window.svg"}
:::

Existuje několik osvědčených vzorů, jak eventual consistency v UI řešit:

### Strategie řešení v UI

:::callout{type="pattern"}
### Přehled strategií pro práci s eventual consistency {#ec-strategie-heading}

| Strategie | Princip | Implementace |
|---|---|---|
| Optimistická aktualizace UI | UI okamžitě zobrazí nový stav, aniž čeká na read model | Frontend (JavaScript) přidá záznam do seznamu lokálně po úspěšném POST |
| Post-Redirect-Get s flash | Po command se provede redirect a zobrazí se potvrzující zpráva | Standardní Symfony flash messages – uživatel vidí potvrzení a read model má čas se aktualizovat |
| Polling / Long polling | Frontend periodicky dotazuje read model, dokud nezobrazí aktuální stav | AJAX request každých N milisekund s timeoutem |
| Write-through cache | Command handler po úspěšném zápisu synchronně aktualizuje i read model / cache | Porušuje čisté oddělení, ale eliminuje lag pro kritické operace |
| Synchronní projekce pro kritické cesty | Některé projekce se aktualizují synchronně (ve stejné transakci), ostatní asynchronně | Hybrid: synchronní projekce pro okamžitou konzistenci, asynchronní pro reporting |
:::

:::callout{type="pattern"}
### PHP: Optimistická aktualizace – controller vrací data z command handleru {#ec-priklad-heading}

:::code{language="php" filename="src/Ordering/Application/Controller/PlaceOrderController.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\Controller;

use App\Ordering\Application\Command\PlaceOrder;
use App\Ordering\Application\Form\PlaceOrderFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class PlaceOrderController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    #[Route('/orders', name: 'place_order', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(PlaceOrderFormType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('cart');
        }

        // ID generováno na straně klienta - command nemusí vracet hodnotu
        $orderId = (string) Uuid::v7();
        $data = $form->getData();

        $command = new PlaceOrder(
            orderId: $orderId,
            customerId: $this->getUser()->getUserIdentifier(),
            items: $data['items'],
        );

        $this->commandBus->dispatch($command);

        // Redirect na detail objednávky - read model se může
        // ještě aktualizovat, ale uživatel vidí potvrzení
        $this->addFlash('success', 'Objednávka byla úspěšně vytvořena.');

        return $this->redirectToRoute('order_detail', ['id' => $orderId]);
    }
}
:::
:::

### Read-your-writes na úrovni HTTP {#read-your-writes-http}

Strategie z tabulky výše řeší vnímání uživatele v prohlížeči. API klienti potřebují
tvrdší záruku: „přečti si, co jsi právě zapsal“ (read-your-writes). Docílit jí lze
předáním pozice zápisu – odpověď na command nese číslo verze agregátu nebo offset,
na který se projekce musí dostat. Klient hodnotu pošle s následujícím dotazem,
typicky v hlavičce.

Čtecí endpoint porovná aktuální pozici projekce s požadovanou. Pokud projekce
ještě zaostává, krátce počká (desítky až stovky milisekund) a porovnání zopakuje.
Po vypršení limitu vrátí klientovi signál k opakování – `202 Accepted`
s hlavičkou `Retry-After`, načež klient data po uvedené pauze načte znovu (refetch).
Stavový kód 304 se k tomu nehodí: znamená „vaše cache je platná“, ne „data ještě nejsou“.

:::callout{type="pattern"}
### Náznak: předání pozice zápisu přes HTTP hlavičky {#ryw-http-heading}

:::code{language="php" filename="snippet.php"}
<?php
// POST /orders - odpověď nese verzi zápisu
return new JsonResponse(['orderId' => $orderId], 201, ['X-Write-Version' => '17']);

// GET /orders/{id} s hlavičkou X-Expected-Version: 17
if ($projectionVersion < $expectedVersion) {
    // Projekce ještě nedoběhla - klient GET zopakuje po uvedené pauze.
    return new Response(status: 202, headers: ['Retry-After' => '1']);
}
:::
:::

Vzor se vyplatí jen na cestách, kde klient bezprostředně po zápisu čte tatáž data.
Plošné nasazení by čtecí stranu zatížilo čekáním, které většina dotazů nepotřebuje.

:::callout{type="warn"}
### Kdy eventual consistency NENÍ přijatelná {#ec-warning-heading}

Existují scénáře, kde i krátkodobá nekonzistence dat je nepřijatelná:

- **Finanční zůstatky** – Uživatel nesmí vidět neaktuální stav účtu
  a provést operaci na základě zastaralých dat.
- **Unikátní omezení** – Kontrola duplicitního e-mailu při registraci musí
  být konzistentní v okamžiku zápisu, ne „až se read model aktualizuje“.
- **Limity a kvóty** – Pokud uživatel nesmí překročit limit 10 objednávek denně,
  kontrola musí být přesná v okamžiku commandu.

Pro tyto scénáře zajistěte konzistenci na **write straně** (v command handleru
přes doménový model a databázová omezení), ne přes read model. Read model eventual consistency
má vliv pouze na *zobrazení* dat, nikoli na *doménová rozhodnutí*.
:::

## 12.13 Asynchronní zpracování {#async}

CQRS otevírá dveře asynchronnímu zpracování příkazů. V Symfony 8 se asynchronní
zpracování konfiguruje přes transporty v Messenger komponentě. Příkaz označený pro asynchronní
transport je při dispatchi serializován a zařazen do fronty; Messenger worker jej později
vyzvedne a předá handleru.

:::callout{type="pattern"}
### Konfigurace asynchronního zpracování v Symfony 8 {#async-example-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml"}
# config/packages/messenger.yaml
framework:
    messenger:
        # Konfigurace transportů
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: commands
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 60000

            async_priority_high:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: high_priority

        # Směrování zpráv - mapuje konkrétní třídy nebo rozhraní na transport
        routing:
            # Příkazy vhodné pro asynchronní zpracování:
            # odesílání notifikací, generování reportů, aktualizace read modelů
            App\Notification\Application\Command\SendWelcomeEmail: async
            App\Reporting\Application\Command\GenerateMonthlyReport: async

            # Vysoká priorita - aktualizace read modelů pro kritické obrazovky
            App\Ordering\Domain\Event\OrderPlaced: async_priority_high
:::
:::

Tato konfigurace směruje příkazy pro odesílání e-mailů a generování reportů na asynchronní
transport s retry strategií (3 pokusy s exponenciálním backoffem). Pro kritické události
definuje samostatný transport `async_priority_high` s vlastní frontou – Messenger worker
pro tuto frontu může běžet s vyšší prioritou nebo na dedikovaném serveru.
Spolehlivé předání doménových událostí do fronty, atomické se zápisem agregátu,
zajišťuje [Outbox Pattern](/outbox-pattern).

:::callout{type="pattern"}
### Spuštění Messenger workerů {#worker-heading}

:::code{language="bash" filename="snippet.sh"}
# Konzumace zpráv z obou front - high_priority má přednost
$ php bin/console messenger:consume async_priority_high async

# V produkci: Supervisor nebo systemd pro automatický restart
# /etc/supervisor/conf.d/messenger-worker.conf
[program:messenger-consume]
command=php /var/www/app/bin/console messenger:consume async_priority_high async --time-limit=3600 --memory-limit=128M
numprocs=2
autostart=true
autorestart=true
startsecs=0
redirect_stderr=true
stdout_logfile=/var/log/messenger-worker.log
:::
:::

:::callout{type="note"}
### Produkční provoz workerů {#worker-produkce-heading}

Messenger workery jsou dlouhodobě běžící procesy. V produkci je nutné zajistit:

- **Automatický restart** – Worker může spadnout (memory leak, neočekávaná výjimka).
  Supervisor nebo systemd zajistí automatický restart.
- **Time limit a memory limit** – `--time-limit=3600` ukončí worker
  po hodině, `--memory-limit=128M` po dosažení limitu paměti. Supervisor pak
  worker restartuje s čistým stavem.
- **Graceful shutdown** – Při deployi pošlete workerům signál
  `SIGTERM`. Worker dokončí aktuálně zpracovávanou zprávu a poté se ukončí.
  Příkaz `messenger:stop-workers` toho docílí přes cache signal.
:::

## 12.14 Zpracování chyb a Dead Letter Queue {#error-handling}

Zpracování chyb se v asynchronním prostředí podstatně liší od synchronního světa.
Při synchronním dispatchi výjimka probublá přímo do controlleru a uživatel vidí chybovou
hlášku. Při asynchronním dispatchi je zpráva ve frontě – pokud handler selže, uživatel
o tom neví a zpráva musí být zpracována znovu.

### Retry strategie

Symfony Messenger podporuje automatické opakování selhalých zpráv. Konfigurace
`retry_strategy` na transportu definuje, kolikrát a s jakým zpožděním
se handler znovu zavolá:

- `max_retries: 3` – Maximální počet opakování.
- `delay: 1000` – Zpoždění prvního opakování (v ms).
- `multiplier: 2` – Exponenciální backoff: 1s → 2s → 4s.
- `max_delay: 60000` – Maximální zpoždění (60 sekund).

### Failed transport (Dead Letter Queue)

Když selžou všechny pokusy o retry, Messenger zprávu přesune na **failed transport**
(dead letter queue). Zprávy na failed transportu čekají na manuální zpracování –
vývojář je může prozkoumat, opravit příčinu chyby a znovu odeslat.

:::callout{type="pattern"}
### Konfigurace failed transportu a diagnostické příkazy {#failed-transport-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml"}
# config/packages/messenger.yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            failed:
                dsn: 'doctrine://default?queue_name=failed'
:::

:::code{language="bash" filename="snippet.sh"}
# Zobrazení selhalých zpráv
$ php bin/console messenger:failed:show

# Detail konkrétní selhalé zprávy (včetně výjimky)
$ php bin/console messenger:failed:show 42

# Opakované zpracování selhalé zprávy
$ php bin/console messenger:failed:retry 42

# Opakování všech selhalých zpráv
$ php bin/console messenger:failed:retry

# Trvalé odstranění selhalé zprávy (po analýze)
$ php bin/console messenger:failed:remove 42
:::
:::

:::callout{type="warn"}
### Monitoring selhalých zpráv {#failed-monitoring-heading}

Dead letter queue není odkladiště. Patří do ní zprávy, které **vyžadují pozornost**.
V produkčním systému musíte monitorovat počet zpráv na failed transportu
a nastavit alerting (např. přes Prometheus metriky nebo jednoduchý cron job
kontrolující `messenger:failed:show --format=json`). Neošetřené selhávající
zprávy mohou znamenat, že read model diverguje od write modelu, události se ztrácejí,
nebo notifikace nejsou doručovány.
:::

## 12.15 Middleware v CQRS {#middleware}

Middleware v Symfony Messenger tvoří řetěz komponent kolem handleru – zachycuje zprávu
před zpracováním a po něm. Tudy do dispatch cyklu vstupuje validace, logování,
transakce nebo autorizace, aniž by se musel měnit handler.

Vestavěné middleware `validation` a `doctrine_transaction` se objevily
v dřívější konfiguraci. Pro pokročilejší scénáře si můžete vytvořit vlastní middleware:

:::callout{type="pattern"}
### PHP: Logovací middleware pro command bus {#middleware-priklad-heading}

:::code{language="php" filename="src/Infrastructure/Messenger/Middleware/CommandLoggingMiddleware.php"}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class CommandLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $commandName = (new \ReflectionClass($message))->getShortName();

        $this->logger->info('Dispatching command: {command}', [
            'command' => $commandName,
            // Pozor: v produkci filtrujte citlivá pole (hesla, tokeny)
            // pomocí vlastního serializéru nebo allowlistu properties
            'payload' => get_object_vars($message),
        ]);

        $startTime = microtime(true);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $this->logger->info('Command handled: {command} ({duration}ms)', [
                'command'  => $commandName,
                'duration' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $envelope;
        } catch (\Throwable $e) {
            $this->logger->error('Command failed: {command} - {error}', [
                'command'  => $commandName,
                'error'    => $e->getMessage(),
                'duration' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            throw $e;
        }
    }
}
:::
:::

:::callout{type="pattern"}
### Registrace vlastního middleware {#middleware-registrace-heading}

:::code{language="yaml" filename="config/packages/messenger.yaml"}
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - App\Infrastructure\Messenger\Middleware\CommandLoggingMiddleware
                    - validation
                    - doctrine_transaction
:::
:::

Na pořadí middleware záleží: v příkladu výše se logování provede jako první (zachytí
i validační chyby), následuje validace (odmítne nevalidní command ještě před zahájením
transakce) a nakonec `doctrine_transaction` (obalí handler do DB transakce).

## 12.16 Testování CQRS {#testovani-cqrs}

CQRS usnadňuje testování. Command handlers, query handlers a projektory jsou izolované
komponenty s jasně definovanými vstupy a výstupy. Testovací strategie se liší
podle testované komponenty:

### Testování command handlerů

Command handler se testuje jako unit test s mocknutým repozitářem. Ověřujete, že handler
správně validuje invarianty, volá doménový model a ukládá změny:

:::callout{type="pattern"}
### PHP: Test command handleru {#test-command-handler-heading}

:::code{language="php" filename="Tests/UserManagement/Registration/Command/RegisterUserHandlerTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Registration\Command;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\Service\PasswordHasher;
use App\UserManagement\Registration\Command\RegisterUser;
use App\UserManagement\Registration\Command\RegisterUserHandler;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    public function testRegistersNewUser(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null); // žádný existující uživatel

        $repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $hasher = $this->createMock(PasswordHasher::class);
        $hasher->method('hashPassword')->willReturn('hashed_password');

        $handler = new RegisterUserHandler($repository, $hasher);

        $handler(new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'securepassword123',
        ));
    }

    public function testRejectsDuplicateEmail(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByEmail')
            ->willReturn($this->createMock(User::class)); // uživatel existuje

        $hasher = $this->createMock(PasswordHasher::class);

        $handler = new RegisterUserHandler($repository, $hasher);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User with this email already exists');

        $handler(new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'securepassword123',
        ));
    }
}
:::
:::

### Testování query handlerů

Query handler se testuje na správnost mapování dat z read repozitáře na ViewModel.
Pro integrační testy s reálnou databází můžete ověřit i správnost SQL dotazů:

:::callout{type="pattern"}
### PHP: Test query handleru {#test-query-handler-heading}

:::code{language="php" filename="Tests/UserManagement/Profile/Query/GetUserProfileHandlerTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Profile\Query;

use App\UserManagement\Profile\Query\GetUserProfile;
use App\UserManagement\Profile\Query\GetUserProfileHandler;
use App\UserManagement\Profile\ReadModel\UserProfileReadRepository;
use App\UserManagement\Profile\ViewModel\UserProfileViewModel;
use PHPUnit\Framework\TestCase;

final class GetUserProfileHandlerTest extends TestCase
{
    public function testReturnsProfileForExistingUser(): void
    {
        $expectedProfile = new UserProfileViewModel(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            name: 'Jan Novák',
            email: 'jan@example.com',
            registeredAt: new \DateTimeImmutable('2025-01-15'),
            totalOrders: 5,
            membershipTier: 'gold',
        );

        $readRepository = $this->createMock(UserProfileReadRepository::class);
        $readRepository->method('findById')
            ->with('550e8400-e29b-41d4-a716-446655440000')
            ->willReturn($expectedProfile);

        $handler = new GetUserProfileHandler($readRepository);

        $result = $handler(new GetUserProfile('550e8400-e29b-41d4-a716-446655440000'));

        $this->assertSame($expectedProfile, $result);
    }

    public function testReturnsNullForNonExistingUser(): void
    {
        $readRepository = $this->createMock(UserProfileReadRepository::class);
        $readRepository->method('findById')->willReturn(null);

        $handler = new GetUserProfileHandler($readRepository);

        $result = $handler(new GetUserProfile('non-existing-id'));

        $this->assertNull($result);
    }
}
:::
:::

### Testování projektorů

Projektory se nejlépe testují jako integrační testy s reálnou databází. Ověřujete,
že po zpracování sekvence událostí read model obsahuje očekávaná data:

:::callout{type="pattern"}
### PHP: Integrační test projektoru {#test-projektor-heading}

:::code{language="php" filename="Tests/Ordering/Infrastructure/Projection/OrderDashboardProjectorTest.php"}
<?php

declare(strict_types=1);

namespace Tests\Ordering\Infrastructure\Projection;

use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderShipped;
use App\Ordering\Infrastructure\Projection\OrderDashboardProjector;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrderDashboardProjectorTest extends KernelTestCase
{
    private Connection $connection;
    private OrderDashboardProjector $projector;

    protected function setUp(): void
    {
        $this->connection = self::getContainer()->get(Connection::class);
        $this->projector = new OrderDashboardProjector($this->connection);

        // Vyčistit testovací tabulku
        $this->connection->executeStatement('DELETE FROM order_dashboard');
    }

    public function testProjectsOrderLifecycle(): void
    {
        // Given: objednávka byla vytvořena
        ($this->projector)(new OrderPlaced(
            orderId: 'order-1',
            customerName: 'Jan Novák',
            totalAmount: 1500,
        ));

        // When: objednávka byla odeslána
        ($this->projector)(new OrderShipped(
            orderId: 'order-1',
            trackingNumber: 'CZ123456789',
        ));

        // Then: read model obsahuje aktuální stav
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM order_dashboard WHERE order_id = :id',
            ['id' => 'order-1'],
        );

        $this->assertSame('shipped', $row['status']);
        $this->assertSame('CZ123456789', $row['tracking_number']);
        $this->assertSame(1500, (int) $row['total_amount']);
    }

    public function testIdempotentProjection(): void
    {
        $event = new OrderPlaced(
            orderId: 'order-2',
            customerName: 'Eva Černá',
            totalAmount: 800,
        );

        // Zpracovat stejnou událost dvakrát (at-least-once delivery)
        ($this->projector)($event);
        ($this->projector)($event);

        // Read model obsahuje záznam pouze jednou
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM order_dashboard WHERE order_id = :id',
            ['id' => 'order-2'],
        );

        $this->assertSame(1, (int) $count);
    }
}
:::
:::

Kompletnější přehled testovacích strategií pro DDD kód – včetně testování agregátů,
value objects a doménových služeb – najdete v kapitole
[Testování DDD kódu](/testovani-ddd).

## 12.17 Saga / Process Manager {#saga}

Při použití CQRS s více [Bounded Contexts](/zakladni-koncepty#bounded-contexts)
vzniká potřeba koordinovat dlouhotrvající procesy napříč kontexty.
Vzor **Saga** – v orchestrované podobě označovaný **Process Manager** – naslouchá
doménovým událostem a podle nich odesílá příkazy, čímž propojuje command a event stranu CQRS
do ucelených doménových procesů.

Podrobný výklad ság – včetně implementace v Symfony Messenger,
kompenzačních strategií a testování – najdete v kapitole
[Ságy a Process Managery](/sagy-a-process-managery).

:::faq{}
- question: Co je CQRS?
  answer: 'CQRS (Command Query Responsibility Segregation) je architektonický vzor, který rozděluje aplikaci na dva oddělené modely: write model pro změny stavu a read model pro dotazy. Write model se soustředí na doménovou logiku a validaci invariantů, read model na rychlou prezentaci dat uživateli. Každý model lze nezávisle optimalizovat i škálovat. Zformuloval jej Greg Young jako rozšíření staršího principu CQS od Bertranda Meyera. Viz <a href="#what-is-cqrs">úvodní sekce</a>.'
- question: Jaký je rozdíl mezi CQS a CQRS?
  answer: 'CQS (Command Query Separation) je návrhové pravidlo na úrovni metod – každá metoda by měla buď měnit stav, nebo vracet hodnotu, ne obojí. CQRS (Command Query Responsibility Segregation) povyšuje tuto myšlenku na architektonickou úroveň: místo jednoho doménového modelu vznikají dva oddělené modely, každý s vlastními třídami, úložištěm i optimalizačním profilem. CQS je tedy princip ve třídě, CQRS rozhodnutí o struktuře celé aplikace. Více v <a href="#cqs-vs-cqrs">sekci CQS vs. CQRS</a>.'
- question: Kdy se vyplatí CQRS nasadit?
  answer: 'CQRS přináší hodnotu v aplikacích, kde se požadavky na zápis a čtení výrazně liší – například doménově bohatý write model s mnoha invarianty proti výrazně převažujícím dotazům, které potřebují denormalizovaná data. Uplatní se také tam, kde má čtení nezávislý škálovací profil (repliky, cache, full-text vyhledávání) nebo kde je hodnota v odděleném auditu změn. U jednoduchých CRUD operací zvyšuje počet tříd bez odpovídajícího přínosu. Podrobný rozbor ve <a href="#benefits">Výhodách CQRS</a> a <a href="#challenges">Výzvách a omezeních</a>.'
- question: Musím použít Event Sourcing, když používám CQRS?
  answer: 'Ne. CQRS a Event Sourcing jsou nezávislé vzory, které se často kombinují, ale každý z nich lze zavést samostatně. CQRS lze plnohodnotně implementovat s klasickou Doctrine ORM persistencí na write straně a denormalizovanými SQL tabulkami na read straně. Event Sourcing lze naopak zavést i bez CQRS – byť kombinace obou je v praxi běžná, protože si vzájemně prospívají. Rozbor vztahu obou vzorů v <a href="#what-is-cqrs">sekci Co je CQRS</a>.'
- question: Jak se CQRS implementuje v Symfony?
  answer: 'Základním stavebním kamenem je komponenta Symfony Messenger, která funguje jako sběrnice pro příkazy a dotazy. Pro CQRS se obvykle definují dvě oddělené sběrnice (<code>command.bus</code> a <code>query.bus</code>), každá s vlastní sadou handler tříd a middleware. Příkazy mění stav a nevracejí data; dotazy vracejí ViewModely (read modely) a stav nemění. Asynchronní zpracování lze zapnout přes transport, což umožňuje dlouhé operace vytáhnout z request-response cyklu. Více v <a href="#symfony-messenger">sekci Symfony Messenger jako základ CQRS</a>.'
:::
