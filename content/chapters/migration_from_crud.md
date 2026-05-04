---
route: migration_from_crud
path: /migrace-z-crud
title: Migrace z CRUD architektury na DDD
page_title: "Migrace z CRUD architektury na DDD v Symfony | DDD Symfony"
meta_description: "Postupná migrace z CRUD na DDD v Symfony 8: Strangler Fig Pattern, extrakce doménové vrstvy, zavedení repozitářů a CQRS bez velkého big-bangu."
meta_keywords: "migrace CRUD DDD, Strangler Fig Pattern, refaktorizace na DDD, extrakce doménové vrstvy, value objects, repozitáře DDD, CQRS migrace, charakterizační testy, Symfony DDD migrace"
og_type: article
published: "2025-04-24"
modified: "2026-05-03"
breadcrumb_name: Migrace z CRUD
schema_type: TechArticle
schema_headline: "Migrace z CRUD architektury na DDD v Symfony"
chapter_number: "18"
category: Praxe
deck: "Podrobný průvodce migrací z CRUD architektury na Domain-Driven Design v Symfony. Strangler Fig Pattern, extrakce doménové vrstvy, zavedení repozitářů a postupné zavedení CQRS s praktickými PHP příklady."
reading_time: 25
difficulty: 3
github_examples: Chapter09_Migration
---

## 18.01 Kdy a proč migrovat z CRUD na DDD {#kdy-migrovat}

CRUD architektura (Create, Read, Update, Delete) je přirozený výchozí bod pro mnoho aplikací.
Pro správu dat bez komplexní logiky – záznamy kontaktů, katalogy produktů, administrační rozhraní –
CRUD dostačuje a přidávání vrstev DDD přináší zbytečnou složitost.
Problém nastává v okamžiku, kdy aplikace roste do větší komplexity a doménová logika proniká
na nevhodná místa.

:::callout{type="note"}
### Příznaky, že CRUD architektura nestačí {#priznaky-heading}

- **God Services (Boží služby)** – Třídy jako `UserService` nebo `OrderService` obsahují stovky řádků kódu a stávají se centrálním místem pro veškerou doménovou logiku. Přidání jakékoli nové funkce vyžaduje zásah do stejné třídy a riziko regresí roste.
- **Fat Controllers (Tlusté kontrolery)** – Symfony kontrolery přestaly být tenkou vrstvou pro HTTP adaptaci. Místo toho přímo implementují doménová pravidla: validaci, výpočty, přechody stavů. Kontroler má delegovat na doménový model, nikoli ho suplovat.
- **Doménová logika v repozitářích** – Doctrine repozitáře obsahují komplexní podmínky, které vyjadřují doménová pravidla (např. „objednávky, které je možné zrušit“). Tato logika patří do doménového modelu, nikoli do databázové vrstvy.
- **Překrývání zodpovědností** – Není jasné, zda konkrétní pravidlo patří do kontroleru, service nebo repozitáře. Tým nemá sdílené chápání, kde co hledat.
- **Nízká testovatelnost** – Doménová logika je neoddělitelně svázána s HTTP vrstvou nebo databází. Napsání unit testu pro doménové pravidlo vyžaduje rozsáhlý mocking.
- **Komunikační propast** – Vývojáři a doménoví experti používají jiný slovník. Kód neodráží doménový jazyk; pojmy jako „aktivace účtu“ nebo „storno objednávky“ nejsou viditelné v názvech tříd a metod.
:::

### Kdy DDD přináší hodnotu a kdy je CRUD dostačující

Rozhodnutí o migraci podložte analýzou komplexity domény, nikoli módními trendy.
Martin Fowler ve své práci o architektonických vzorech upozorňuje, že aplikace Transaction Script
a CRUD přístupy jsou legitimní pro aplikace s jednoduchými doménovými pravidly
[[1]](https://martinfowler.com/eaaCatalog/transactionScript.html).

:::callout{type="note"}
### Kdy DDD přináší hodnotu {#kdy-ddd-heading}

- Doména obsahuje komplexní doménová pravidla, která se často mění.
- Existují přechody stavů entit (objednávka: vytvořena → potvrzena → odeslána → doručena).
- Tým komunikuje s doménovými experty a potřebuje sdílený jazyk.
- Aplikace je dlouhodobě rozvíjena a musí být udržovatelná v horizontu let.
- Existuje více Bounded Contexts s odlišnými pohledy na stejné entity.

### Kdy zůstat u CRUD

- Aplikace zůstává CRUD nad databázovými tabulkami bez doménové logiky.
- Doménová pravidla jsou triviální a stabilní.
- Tým je malý a čas na migraci není dostupný.
- Aplikace je krátkodobá nebo se bude v blízké budoucnosti kompletně přepisovat.
:::

### Realistické zhodnocení nákladů migrace

Migrace z CRUD na DDD není jednorázová akce. Je to kontinuální proces, který trvá měsíce
až roky podle velikosti kódové základny. Migrace sama o sobě
nepřináší okamžitou hodnotu pro zákazníka. Hodnotu přinese zlepšená schopnost přidávat nové funkcionality
s nižším rizikem regresí. Tým management přesvědčí tím, že migraci provádí inkrementálně
souběžně s vývojem nových funkcionalit, nikoli jako izolovaný refaktoringový projekt.

## 18.02 Strangler Fig Pattern – vzor postupné náhrady {#strangler-fig}

Strangler Fig Pattern (vzor fíkovníku škrtiče) je architektonická strategie popsaná Martinem Fowlerem
[[2]](https://martinfowler.com/bliki/StranglerFigApplication.html),
která umožňuje postupnou náhradu starého systému novým bez nutnosti „big bang“ přepisu. Název pochází
od tropického fíkovníku, který roste kolem hostitelského stromu a postupně ho nahrazuje.

:::diagram{fig="19.2-A" title="Strangler Fig: čtyři fáze migrace CRUD → DDD" src="images/diagrams/19_migration_from_crud/strangler_fig.svg"}
:::

:::callout{type="note"}
### Princip fungování {#strangler-princip-heading}

1. **Nová funkcionalita** je vždy implementována v DDD stylu – nové Bounded Contexts, doménové objekty, repozitáře.
2. **Stará funkcionalita** zůstává v CRUD podobě a je postupně nahrazována při refaktoringu nebo při úpravách stávajících funkcí.
3. **Koexistence** – obě části systému fungují paralelně a jsou propojeny přes Anti-Corruption Layer nebo sdílenou databázi.
4. **Postupná eliminace** – s každou iterací se CRUD část zmenšuje a DDD část roste, dokud starý kód nevymizí.
:::

:::callout{type="pattern"}
### Příklad: Koexistence CRUD a DDD ve struktuře projektu {#strangler-struktura-heading}

:::code{language="bash" filename="snippet.sh"}
src/
├── Controller/                    # Stará CRUD vrstva (postupně se zmenšuje)
│   ├── UserController.php         # Původní CRUD kontroler
│   └── OrderController.php        # Původní CRUD kontroler
│
├── Service/                       # Stará service vrstva (God Services)
│   ├── UserService.php            # Bude nahrazena DDD vrstvou
│   └── OrderService.php           # Bude nahrazena DDD vrstvou
│
├── Entity/                        # Doctrine entity (sdílené nebo duplikované)
│   ├── User.php
│   └── Order.php
│
└── UserManagement/                # Nová DDD vrstva (postupně roste)
    ├── Domain/
    │   ├── Model/
    │   │   ├── User.php           # Doménová entita (ne Doctrine entita)
    │   │   └── Email.php          # Value Object
    │   ├── Repository/
    │   │   └── UserRepository.php # Doménové rozhraní
    │   └── Event/
    │       └── UserRegistered.php # Domain Event
    ├── Application/
    │   ├── Command/
    │   │   ├── RegisterUser.php
    │   │   └── RegisterUserHandler.php
    │   └── Query/
    │       ├── GetUserProfile.php
    │       └── GetUserProfileHandler.php
    └── Infrastructure/
        └── Repository/
            └── DoctrineUserRepository.php  # Implementace repozitáře
:::
:::

### Výhody oproti přímé refaktorizaci (Big Bang Rewrite)

Přímý přepis celého systému najednou (tzv. „big bang rewrite“) patří mezi největší rizika
v softwarovém vývoji. Joel Spolsky ve svém článku „Things You Should Never Do“
[[3]](https://www.joelonsoftware.com/2000/04/06/things-you-should-never-do-part-i/)
popisuje, proč firmy ztratily konkurenční výhodu tím, že kompletně přepsaly fungující systémy.
Strangler Fig Pattern oproti tomu:

- Umožňuje kontinuální dodávku nové hodnoty zákazníkovi i během migrace.
- Snižuje riziko – systém nikdy není kompletně „rozbitý“.
- Poskytuje možnost rollbacku: pokud nová implementace selhává, stará stále funguje.
- Umožňuje týmu učit se DDD postupně, na reálném produkčním kódu.
- Refaktoring lze zastavit kdykoli – systém zůstává v konzistentním, funkčním stavu.

## 18.03 Krok 1: Analýza existující domény {#analyza-domeny}

Než začneme přesouvat kód, musíme pochopit doménu. Nejčastější chybou je přímý skok do refaktoringu
bez předchozí analýzy – výsledkem je pak DDD architektura, která přesně kopíruje strukturu starých
databázových tabulek, aniž by odrážela skutečný doménový model.

### Identifikace Bounded Contexts z existujícího CRUD kódu

Bounded Contexts lze v existující CRUD aplikaci identifikovat sledováním přirozených hranic:

- **Skupiny entit a tabulek**, které jsou silně provázané navzájem, ale slabě propojené s ostatními skupinami – to jsou kandidáti na jeden Bounded Context.
- **God Services** – velké service třídy jsou paradoxně dobrým vodítkem. Pokud `OrderService` obsahuje logiku objednávky, platby i doručení, jsou to tři různé Bounded Contexts skryté v jedné třídě.
- **Opakující se slovo s různým významem** – pokud „zákazník“ v kontextu prodeje znamená něco jiného než „zákazník“ v kontextu zákaznické podpory, jde o přirozené rozhraní dvou Bounded Contexts.

### Event Storming jako nástroj pro analýzu

Event Storming je workshopová technika navržená Albertem Brandolinim
[[4]](https://www.eventstorming.com/), která umožňuje kolaborativně
modelovat doménu prostřednictvím doménových událostí. Při migraci z CRUD slouží
pro:

- Odkrytí implicitní doménové logiky skryté v kontrolerech a service třídách.
- Identifikaci přechodů stavů entit (z pohledu domény, nikoli databáze).
- Nalezení přirozených hranic Bounded Contexts.
- Zapojení doménových expertů do návrhu nové architektury.

:::callout{type="pattern"}
### Příklad: Identifikace doménové logiky v CRUD kontroleru {#crud-before-heading}

Následující příklad ilustruje typický CRUD kontroler, ve kterém se skrývá netriviální doménová
logika. Tuto logiku v dalších krocích extrahujeme do doménového modelu.

:::code{language="php" filename="src/Controller/UserController.php"}
<?php

// PŘED migrací: Typický CRUD kontroler s ukrytou doménovou logikou
namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    #[Route('/users/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Doménová logika č. 1: validace emailu (patří do Value Object)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Neplatný e-mail'], 422);
        }

        // Doménová logika č. 2: kontrola unikátnosti (patří do doménové služby)
        $existing = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'E-mail již existuje'], 409);
        }

        // Doménová logika č. 3: hashování hesla a bezpečnostní pravidla
        if (strlen($password) < 8) {
            return $this->json(['error' => 'Heslo musí mít alespoň 8 znaků'], 422);
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTimeImmutable());
        // Doménová logika č. 4: výchozí stav uživatele
        $user->setStatus('pending_verification');

        $this->em->persist($user);
        $this->em->flush();

        // Doménová logika č. 5: odeslání uvítacího e-mailu
        // ... (inline kód pro odeslání e-mailu)

        return $this->json(['id' => $user->getId()], 201);
    }
}
:::

V tomto kontroleru lze identifikovat nejméně pět oblastí doménové logiky, které patří do
doménového modelu: validace formátu e-mailu, unikátnost e-mailu, bezpečnostní pravidla hesla,
výchozí stav uživatele a vedlejší efekt registrace (uvítací e-mail jako Domain Event).
:::

## 18.04 Krok 2: Extrakce doménové vrstvy {#extrakce-domainove-vrstvy}

Extrakce doménové vrstvy znamená přesunutí doménových pravidel z kontrolerů a service tříd do doménových
objektů. Cílem je, aby doménové objekty samy vynucovaly svá invarianty – pravidla, která musí být
vždy splněna, bez ohledu na to, kdo s objektem pracuje.

### Přesunutí doménových pravidel do doménových objektů

Refaktoring probíhá ve dvou hlavních krocích: nejprve vytvoříme doménové Value Objects pro primitivní
typy nesoucí doménová pravidla, poté extrahujeme logiku do doménových entit a služeb.

:::callout{type="pattern"}
### Příklad: Refaktorizace UserService – before/after {#before-after-heading}

:::code{language="php" filename="src/Service/UserService.php"}
<?php

// PŘED: God Service s přímou závislostí na Doctrine
namespace App\Service;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {}

    public function register(string $email, string $password): User
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Neplatný e-mail');
        }
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Heslo příliš krátké');
        }
        $existing = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        if ($existing) {
            throw new \RuntimeException('E-mail již existuje');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $user->setStatus('pending_verification');
        $this->em->persist($user);
        $this->em->flush();

        $this->mailer->send(/* uvítací e-mail */);

        return $user;
    }
}
:::

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php"}
<?php

declare(strict_types=1);

// PO: Doménová entita s vlastními invarianty
namespace App\UserManagement\Domain\Model;

use App\Shared\Domain\AggregateRoot;
use App\UserManagement\Domain\Event\UserActivated;
use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'user_id')]
    public readonly UserId $id;

    #[ORM\Column(type: 'email_vo', unique: true)]
    private Email $email;

    #[ORM\Embedded(class: HashedPassword::class)]
    private HashedPassword $password;

    #[ORM\Column(enumType: UserStatus::class)]
    private UserStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    public readonly \DateTimeImmutable $registeredAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    private function __construct(UserId $id, Email $email, HashedPassword $password)
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->status = UserStatus::PENDING_VERIFICATION;
        $this->registeredAt = new \DateTimeImmutable();

        // Doménová událost – vedlejší efekt registrace je nyní explicitní
        $this->record(new UserRegistered($id, $email));
    }

    // Named constructor vyjadřuje záměr lépe než new User()
    public static function register(UserId $id, Email $email, HashedPassword $password): self
    {
        return new self($id, $email, $password);
    }

    public function activate(VerificationToken $token): void
    {
        if ($this->status !== UserStatus::PENDING_VERIFICATION) {
            throw new \DomainException('Uživatel již byl aktivován nebo je zablokován.');
        }
        $token->validate(); // Token si validuje sám sebe
        $this->status = UserStatus::ACTIVE;
        $this->record(new UserActivated($this->id));
    }

    public function email(): Email { return $this->email; }
    public function status(): UserStatus { return $this->status; }
}
:::

Doménová entita `User` nyní sama vynucuje svá pravidla: výchozí stav, přechod stavu
při aktivaci, emituje Domain Event při registraci. Kontroler ani service nemůže tyto invarianty
obejít.
:::

### Zavedení Value Objects místo primitive types

Primitive Obsession je code smell, při kterém koncepty s doménovou sémantikou reprezentují
primitivní typy jako `string` nebo `int`. Value Object nahrazuje primitiv
objektem, který zapouzdřuje validaci a chování.

:::callout{type="pattern"}
### Příklad: Refaktorizace string emailu na Email Value Object {#email-vo-heading}

:::code{language="php" filename="src/UserManagement/Domain/ValueObject/Email.php"}
<?php

// PŘED: Email jako string – validace je rozptýlena v celé aplikaci
class UserController {
    public function register(Request $request): Response {
        $email = $request->request->get('email'); // string, nic negarantuje
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { /* ... */ }
        // ... validace se opakuje na každém místě použití
    }
}

// --- Soubor: Email.php ---
declare(strict_types=1);

// PO: Email jako Value Object – validace je na jednom místě
namespace App\UserManagement\Domain\ValueObject;

final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" není platná e-mailová adresa.', $value)
            );
        }

        // Zakázané domény (doménové pravidlo)
        $domain = substr($normalized, strpos($normalized, '@') + 1);
        if (in_array($domain, ['example.com', 'test.com'], true)) {
            throw new \DomainException(
                'Registrace z testovacích domén není povolena.'
            );
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
:::

Value Object `Email` zapouzdřuje validaci na jednom místě. Kdykoli vznikne
instance `Email`, máme garantovanou platnost hodnoty – bez ohledu na to,
kde v aplikaci k vytvoření dochází. Toto je základní princip „Make Illegal States Unrepresentable“.
:::

## 18.05 Krok 3: Zavedení repozitářů {#zavedeni-repozitaru}

V CRUD architektuře se pro přístup k datům typicky používá `EntityManagerInterface` nebo
Doctrine repozitáře přímo v kontrolerech a service třídách. DDD přináší doménové rozhraní repozitáře,
které abstrahuje persistenci od domény a umožňuje výměnu implementace (např. přechod z SQL na jiné
úložiště) bez změny doménového kódu.

### Vytvoření doménového rozhraní repozitáře

Doménové rozhraní repozitáře je součástí doménové vrstvy – definuje, jaké operace jsou potřeba
z pohledu domény. Neobsahuje žádné zmínky o Doctrine, SQL nebo jiné infrastrukturní technologii.

:::callout{type="pattern"}
### Příklad: Doménové rozhraní vs. Doctrine implementace {#repository-interface-heading}

:::code{language="php" filename="src/UserManagement/Domain/Repository/UserRepository.php"}
<?php

declare(strict_types=1);

// Doménové rozhraní – součást domény, žádná infrastrukturní závislost
namespace App\UserManagement\Domain\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /** @return User[] */
    public function findActiveUsers(): array;

    public function nextIdentity(): UserId;
}
:::

:::code{language="php" filename="src/UserManagement/Infrastructure/Repository/DoctrineUserRepository.php"}
<?php

declare(strict_types=1);

// Infrastrukturní implementace – obaluje Doctrine EntityManager
namespace App\UserManagement\Infrastructure\Repository;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function save(User $user): void
    {
        $this->em->persist($user);
        // Flush je záměrně ponechán na aplikační vrstvě (Command Handler)
        // aby byla možná transakční konzistence přes více agregátů
    }

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['email.value' => $email->value()]);
    }

    public function findActiveUsers(): array
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}
:::

Doménová vrstva závisí pouze na rozhraní `UserRepository`. Symfony DI container
injektuje do doménových služeb `DoctrineUserRepository`. Díky
tomu lze implementaci repozitáře vyměnit v konfiguračním souboru bez změny doménového kódu.
:::

:::callout{type="note"}
### Konfigurace Dependency Injection v Symfony {#di-config-heading}

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
# config/services.yaml
services:
    App\UserManagement\Domain\Repository\UserRepository:
        alias: App\UserManagement\Infrastructure\Repository\DoctrineUserRepository
:::

Tato konfigurace zajistí, že Symfony automaticky injektuje Doctrine implementaci všude tam,
kde je typovaná závislost na doménovém rozhraní `UserRepository`.
:::

## 18.06 Krok 4: Postupné zavedení CQRS {#cqrs-postupne}

Command Query Responsibility Segregation (CQRS) je přirozené rozšíření DDD, jeho zavedení
ale přichází až poté, co se doménový model usadí. Předčasné zavedení CQRS bez zralého
doménového modelu přesouvá komplexitu z doménové vrstvy do handleru, kde je neviditelná a
hůře testovatelná.

### Začít s Command stranou (write side)

Nejpřirozenějším místem pro zavedení CQRS je write side – operace, které mění stav systému.
Query side (čtení) lze zpočátku ponechat s přímými Doctrine dotazy a refaktorovat ji samostatně,
nebo ji ponechat jako optimalizované SQL dotazy i v DDD systému (read modely).

:::callout{type="pattern"}
### Příklad: Extrakce RegisterUserCommand z UserController {#command-extraction-heading}

:::code{language="php" filename="src/Controller/UserController.php"}
<?php

// PŘED: Logika přímo v kontroleru nebo service
namespace App\Controller;

class UserController extends AbstractController
{
    public function __construct(
        private UserService $userService
    ) {}

    #[Route('/users/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        // Kontroler musí vědět, jaké parametry service očekává
        $this->userService->register(
            $request->request->get('email'),
            $request->request->get('password'),
            $request->request->get('name')
        );
        return $this->json(['status' => 'ok'], 201);
    }
}
:::

:::code{language="php" filename="src/UserManagement/Application/Command/RegisterUser.php"}
<?php

// --- Soubor: RegisterUser.php ---
// PO: Command objekt jako explicitní kontrakt
namespace App\UserManagement\Application\Command;

final readonly class RegisterUser
{
    public function __construct(
        public string $email,
        public string $password,
        public string $name,
    ) {}
}

// --- Soubor: RegisterUserHandler.php ---
// Handler zapouzdřuje aplikační logiku jednoho use case
namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private UserRegistrationPolicy $policy,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = new Email($command->email);
        $password = HashedPassword::fromPlaintext($command->password);

        // Doménová politika ověřuje pravidla přes repozitář
        $this->policy->assertEmailIsUnique($email);

        $user = User::register(
            $this->users->nextIdentity(),
            $email,
            $password
        );

        $this->users->save($user);

        // Domain Events jsou zpracovány Symfony Messengerem
        foreach ($user->releaseDomainEvents() as $event) {
            // event dispatch je řešen infrastrukturní vrstvou
        }
    }
}

// --- Soubor: UserController.php ---
// Kontroler je nyní tenký – pouze HTTP adaptér
namespace App\Controller;

use App\UserManagement\Application\Command\RegisterUser;
use Symfony\Component\Messenger\MessageBusInterface;

class UserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {}

    #[Route('/users/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $this->commandBus->dispatch(new RegisterUser(
            email: $request->request->getString('email'),
            password: $request->request->getString('password'),
            name: $request->request->getString('name'),
        ));
        return $this->json(['status' => 'ok'], 201);
    }
}
:::

Command `RegisterUser` je prosté DTO (Data Transfer Object) bez závislostí. Handler
`RegisterUserHandler` orchestruje doménový model. Kontroler se zužuje na HTTP
vrstvu, která pouze přeloží HTTP požadavek na Command. Tato separace odpovědností
umožňuje testovat každou vrstvu zvlášť.
:::

## 18.07 Testování při migraci {#testovani-pri-migraci}

Testování rozhoduje o úspěchu migrace. Bez dostatečného pokrytí testy hrozí, že refaktoring
zavede regrese, které se projeví až v produkci. Strategie testování při migraci z CRUD na DDD
kombinuje dvě techniky: charakterizační testy pro zachycení stávajícího chování a postupné
doplňování unit testů pro novou doménovou vrstvu.

### Charakterizační testy (Characterization Tests)

Pojem „charakterizační testy“ pochází z knihy Michaela Featherse „Working Effectively with Legacy
Code“
[[5]](https://www.oreilly.com/library/view/working-effectively-with/0131177052/).
Charakterizační test nepopisuje, jaké *by mělo být* správné chování systému, ale zachycuje
jaké chování systém *aktuálně má*. Slouží jako síť, která zachytí nechtěné změny chování
při refaktoringu.

:::callout{type="pattern"}
### Příklad: Charakterizační test pro CRUD kontroler {#char-test-heading}

:::code{language="php" filename="Tests/Characterization/UserRegistrationCharacterizationTest.php"}
<?php

declare(strict_types=1);

namespace Tests\Characterization;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Charakterizační testy zachycují AKTUÁLNÍ chování systému.
 * Jsou záměrně popsány jako "chová se tak, jak se chová",
 * ne "mělo by se chovat tak a tak".
 * Pokud refaktoring změní toto chování, test selže a upozorní tým.
 */
class UserRegistrationCharacterizationTest extends WebTestCase
{
    public function test_registration_returns_201_with_valid_data(): void
    {
        $client = static::createClient();
        $client->request('POST', '/users/register', [
            'email' => 'test@example-valid.com',
            'password' => 'SecurePassword123',
            'name' => 'Jan Novák',
        ]);

        // Zachycujeme aktuální HTTP status kód
        self::assertResponseStatusCodeSame(201);
    }

    public function test_registration_returns_422_for_invalid_email(): void
    {
        $client = static::createClient();
        $client->request('POST', '/users/register', [
            'email' => 'not-an-email',
            'password' => 'SecurePassword123',
            'name' => 'Jan Novák',
        ]);

        // Zachycujeme aktuální chybový kód a strukturu odpovědi
        self::assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function test_duplicate_email_returns_409(): void
    {
        $client = static::createClient();
        // První registrace
        $client->request('POST', '/users/register', [
            'email' => 'duplicate@example-valid.com',
            'password' => 'SecurePassword123',
            'name' => 'Jan Novák',
        ]);
        // Druhá registrace se stejným e-mailem
        $client->request('POST', '/users/register', [
            'email' => 'duplicate@example-valid.com',
            'password' => 'AnotherPassword456',
            'name' => 'Jiný Uživatel',
        ]);

        self::assertResponseStatusCodeSame(409);
    }
}
:::

Charakterizační testy vznikají *před* refaktoringem. Cílem je, aby všechny procházely
po celou dobu migrace – selhání testu signalizuje, že refaktoring změnil pozorovatelné chování
systému, ať záměrně nebo omylem.
:::

### Unit testy doménové vrstvy

Jednou z výhod DDD je testovatelnost doménových objektů v izolaci bez databáze,
HTTP klienta nebo jiné infrastruktury. Unit testy doménové vrstvy jsou rychlé, deterministické
a přesně dokumentují doménová pravidla.

:::callout{type="pattern"}
### Příklad: Unit test doménové entity {#domain-unit-test-heading}

:::code{language="php" filename="Tests/UserManagement/Domain/Model/UserTest.php"}
<?php

declare(strict_types=1);

namespace Tests\UserManagement\Domain\Model;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\Event\UserRegistered;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_newly_registered_user_is_pending_verification(): void
    {
        $user = User::register(
            UserId::generate(),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlaintext('SecurePass123')
        );

        self::assertTrue($user->status()->isPendingVerification());
    }

    public function test_registration_emits_user_registered_event(): void
    {
        $user = User::register(
            UserId::generate(),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlaintext('SecurePass123')
        );

        $events = $user->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function test_cannot_activate_already_active_user(): void
    {
        $user = User::register(/* ... */);
        $token = VerificationToken::valid('abc123');
        $user->activate($token);

        $this->expectException(\DomainException::class);
        $user->activate($token); // druhá aktivace musí selhat
    }
}
:::
:::

## 18.08 Rizika a doporučení {#rizika-a-doporuceni}

### Nejčastější chyby při migraci

- **Anémický doménový model** – Nejčastější past. Vývojáři vytvoří třídy s názvem jako v DDD (`User`, `Order`), ale tyto třídy obsahují pouze gettery a settery bez doménové logiky. Logika zůstane v service třídách. Výsledek je DDD terminologie s CRUD implementací.
- **Přílišná granularita Bounded Contexts** – Rozdělení domény na příliš mnoho malých kontextů vede k distribuované komplexitě. Každá integrace mezi kontexty přidává overhead. Začněte s většími kontexty a rozdělujte je až tehdy, když je důvod k tomu jasný.
- **Doctrine entity jako doménové entity** – Přímé přidávání DDD logiky do Doctrine entit je antipattern. Doctrine mapování (anotace, atributy) svazuje doménový objekt s infrastrukturní technologií. Oddělte doménové entity od persistence mapování.
- **CQRS bez doménového modelu** – Zavedení CommandBusu a QueryBusu bez refaktorovaného doménového modelu přidá vrstvy komplexity bez přínosu. CQRS je amplifikátor – zesílí jak výhody, tak problémy stávající architektury.
- **Ignorování Anti-Corruption Layer** – Při integraci nové DDD vrstvy se starým CRUD kódem je nutné vytvořit překladovou vrstvu. Bez ní pronikají koncepty starého modelu do nového a kontaminují ho.

### Tipy pro týmovou komunikaci

- Vytvořte **glosář pojmů** (Ubiquitous Language) a udržujte ho aktuální. Vyvěste ho na wiki nebo přímo v repozitáři jako součást dokumentace.
- Pravidelně pořádejte **krátká Event Storming sezení** (30–60 minut) pro nové funkcionality před jejich implementací.
- Nastavte **code review pravidla**: doménová logika nesmí být v kontrolerech, doménové objekty nesmějí záviset na infrastruktuře.
- Komunikujte s managementem v pojmech **obchodní hodnoty**, nikoli technické architektury. Migrace na DDD = schopnost rychleji a bezpečněji přidávat nové funkce.

### Realistické odhady náročnosti

Zkušenosti z praxe ukazují, že migrace středně velké CRUD aplikace (50–100 tabulek, 3–5 let vývoje)
na DDD architekturu trvá při inkrementálním přístupu 12 až 24 měsíců. Tato čísla předpokládají,
že migrace probíhá souběžně s vývojem nových funkcionalit a nevěnuje se jí dedikovaný tým na
plný úvazek. Faktory, které dobu prodlužují: špatná testovatelnost stávajícího kódu (nutnost
psát charakterizační testy), nedostatečná znalost domény v týmu, absence doménových expertů.

:::callout{type="warn"}
### Varování před Big Bang Rewrites {#big-bang-warning-heading}

**Nikdy nezačínejte migraci na DDD kompletním přepisem systému.** Big Bang Rewrite
je architektonicky nejrizikovější rozhodnutí, které tým může učinit. Typický scénář:
tým začne „přepis na zelenou louku“. Po 6 měsících zjistí, že nový systém nesplňuje všechny
okrajové případy původního systému (které nikdo nezdokumentoval). Původní systém mezitím dostává
nové funkcionality a nový systém za ním nestíhá. Výsledkem je buď zrušení projektu přepisu,
nebo spuštění nedokončeného systému s fatálními chybami.

Vždy preferujte **inkrementální migraci pomocí Strangler Fig Patternu**:
zachovejte funkční systém v produkci, přidávejte DDD vrstvami a nahrazujte CRUD kód
postupně při každém sprintu.
:::

DDD koncepty a jejich implementaci v Symfony rozebírají navazující kapitoly
[Implementace DDD v Symfony](/implementace-v-symfony)
a [CQRS v Symfony](/cqrs).

## 18.09 Refactoring kuchařka – krátké recepty {#refactoring-kucharka}

Strangler Fig je strategický pohled na celou migraci. V denní praxi narazíte na opakující se mikrosituace.
Tato kuchařka obsahuje 8 nejčastějších, každá ve formátu *„symptomy → krok 1, 2, 3“*.
Recepty jsou záměrně krátké – když potřebujete kontext nebo důkladnější rozbor, projděte odkazované kapitoly.

### Recept 1: Anémická Doctrine entita {#recept-anemic-entita-heading}

**Symptomy:** entita má jen gettery/settery, veškerá logika je v Service třídě.

1. Identifikujte invarianty entity (co nesmí být porušeno).
2. Pro každý invariant najděte metodu v `*Service`, která ho dnes drží.
3. Přesuňte metodu do entity, getter/setter zúžte na `private` nebo zrušte.
4. Service se stane tenkým koordinátorem (Application Service) – jen volá entitu, transakce, eventy.
5. Souvisí: [Anti-vzor: Anemic Domain Model](/anti-vzory) · [Domain Services vs. Application Services](/mene-zname-vzory#domain-services).

### Recept 2: Doctrine atributy v doménové třídě – kdy je to problém {#recept-doctrine-anotace-heading}

**Symptomy:** `App\Domain\Order` má `#[ORM\Entity]`, doména závisí na Doctrine.

Pragmatická výchozí volba v tomto průvodci atributy přijímá – jsou to metadata, ne chování,
a Symfony ekosystém s nimi pracuje idiomaticky (viz [rozhodnutí o mappingu](/implementace-v-symfony#mapping-volba-heading)).
Pokud váš projekt skutečně potřebuje striktní oddělení (Hexagonal, dlouhodobá výměna ORM,
core doména s vysokou hodnotou), postup je:

1. Zaveďte [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern) –
   doménová třída zůstane POPO, persistence model + mapper jdou do
   `App\<BC>\Infrastructure\Persistence\Doctrine\`.
2. Mapper hydratujte z perzistence přes `User::reconstitute(...)` factory metodu, která
   neemituje doménové události.
3. Hlídejte hranici staticky: `composer require --dev phpat/phpat` + rule
   `App\<BC>\Domain\* nesmí závisět na Doctrine\*`.

### Recept 3: Primitivní ID jako `string` / `int` {#recept-id-string-heading}

**Symptomy:** `Order::$id: string`, kdekoli se předává jen `string`.

1. Zaveďte VO `OrderId` (`final readonly class OrderId { public function __construct(public Ulid $value) {} }`).
2. Doctrine custom type pro `OrderId` (mapping z DB string ↔ VO).
3. Postupně refaktorujte signature napříč handlery. PHPStan na úrovni 8 odhalí každý zapomenutý `string`.

### Recept 4: Doctrine tabulka sdílená napříč BC {#recept-shared-tabulka-heading}

**Symptomy:** tabulka `users` se používá v Ordering BC i Billing BC; oba do ní zapisují.

1. Identifikujte vlastnícího BC (typicky Identity).
2. Ostatní BC do ní nesmí zapisovat – jen číst. Reads přesuňte do read-modelů (každý BC má vlastní projekci).
3. Zápisy nahraďte voláním Identity API (sync HTTP nebo async event publishing s outboxem).
4. Souvisí: [Outbox Pattern](/outbox-pattern).

### Recept 5: Doménová logika v controlleru {#recept-business-logika-controlleru-heading}

**Symptomy:** 200řádkový controller s if-else stromem doménových rozhodnutí.

1. Vytvořte `Command` DTO + `CommandHandler` v Application vrstvě.
2. Controller se zúží na: validate input → dispatch command → vrátit response.
3. Autorizaci přesuňte do Voteru (souvisí [Autorizace](/autorizace-v-ddd)).

### Recept 6: Aggregate bobtná (1000+ řádků) {#recept-aggregate-bobtna-heading}

**Symptomy:** `Order` má 30 metod a 15 polí.

1. Najděte pole, která se mění nezávisle (různé invarianty, různé use cases).
2. Zvažte split na 2 agregáty (např. `Order` + `OrderShipment`). Spojí je sdílené `OrderId`, žádná silná reference.
3. Specifikační logiku vyextrahujte do `Specification` tříd (souvisí [Specifications](/mene-zname-vzory#specification)).

### Recept 7: `eventDispatcher->dispatch()` uvnitř doménové metody {#recept-event-publish-uvnitr-heading}

**Symptomy:** Aggregate volá Symfony `EventDispatcher` přímo.

1. Aggregate uchová eventy v `private array $releasedEvents`.
2. Aplikační handler po `repository->save()` volá `$order->releaseEvents()` a publikuje (přes outbox).
3. Doména ztratí závislost na Symfony EventDispatcheru. Test je čistý.
4. Souvisí: [Outbox – Aggregate publikuje](/outbox-pattern#aggregate-publishes).

### Recept 8: Stav je sloupec `string $status` {#recept-fields-jako-stav-heading}

**Symptomy:** `Order::$status: string`, podmínky všude `if ($order->status === 'PLACED')`.

1. Zaveďte enum (PHP 8.1+): `enum OrderStatus: string { case PLACED = 'placed'; case CANCELLED = 'cancelled'; }`.
2. Aggregate metody dělají transitions: `$this->status = OrderStatus::CANCELLED`.
3. Pro komplexní transition rules zvažte State Machine (Symfony Workflow component nebo doménová reprezentace).

:::faq{}
- question: Jaké příznaky ukazují, že CRUD aplikace je zralá na migraci?
  answer: 'Typickými signály jsou God Services o stovkách řádků a kontrolery obsahující doménová pravidla. Dále doménová logika zamíchaná v Doctrine repozitářích, opakované regresní chyby při drobných změnách a rostoucí čas potřebný pro onboarding nových vývojářů. Pokud aplikace tyto příznaky nevykazuje a zůstává prostým mapováním formulářů na tabulky, migrace odpovídající hodnotu nepřinese. Obecnější otázku, pro jaké projekty je DDD vhodné, řeší samostatná kapitola <a href="/kdy-nepouzivat-ddd">Kdy DDD nepoužívat</a>. Viz také <a href="#kdy-migrovat">sekci Kdy a proč migrovat</a>.'
- question: Co je Strangler Fig Pattern?
  answer: 'Strangler Fig (fíkovník škrtič) je migrační vzor popsaný Martinem Fowlerem, při kterém nová architektura postupně „obroste“ starý systém a nahradí ho po částech. Nová funkcionalita vzniká od začátku v DDD stylu, zatímco stará CRUD část zůstává v provozu a s každou iterací ubývá. Obě části existují paralelně a propojují se přes Anti-Corruption Layer. Podrobný rozbor v <a href="#strangler-fig">sekci Strangler Fig Pattern</a>.'
- question: Jak začít s analýzou existující domény?
  answer: 'Začíná se Event Stormingem nebo obdobnou kolaborativní technikou s doménovými experty – zmapují se hlavní události, commands a aktéři. Z této mapy vyplývá návrh Bounded Contexts a Ubiquitous Language. Paralelně se v existujícím kódu hledají implicitní hranice modelu: moduly, tabulky nebo funkční celky, které jsou málo propojené. Cílem první iterace je hrubá mapa, ne úplný model. Praktický postup v <a href="#analyza-domeny">sekci Analýza existující domény</a>.'
- question: Jak extrahovat doménovou vrstvu z existujícího CRUD kódu?
  answer: 'Migrace začíná u jednoho vybraného Bounded Contextu, pro který vzniká nová doménová vrstva oddělená od Doctrine entit. Doménová logika ze service tříd a kontrolerů se přesouvá do metod agregátu, zatímco původní CRUD kód zůstává jako adaptér pro API a persistenci. Nejprve se zavede Anti-Corruption Layer, pak se refaktorují jednotlivé use casy. Charakterizační testy proti původnímu chování minimalizují regrese. Detailní rozbor v <a href="#extrakce-domainove-vrstvy">sekci Extrakce doménové vrstvy</a>.'
- question: Jaká jsou hlavní rizika migrace z CRUD na DDD a jak je zmírnit?
  answer: 'Nejčastější pastí je anémický model: nové třídy mají DDD názvy, ale logika zůstává v servisech. Dále hrozí nadměrná granularita Bounded Contexts, přímé ukládání doménové logiky do Doctrine entit a zavádění CQRS bez přepracovaného modelu. Největším rizikem je Big Bang Rewrite, který se zřídka dotáhne do konce. Migrace má probíhat inkrementálně přes Strangler Fig, u středně velké aplikace s realistickým odhadem 12–24 měsíců. Rozbor rizik a zmírňujících opatření v <a href="#rizika-a-doporuceni">sekci Rizika a doporučení</a>.'
:::
