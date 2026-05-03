---
route: case_study
path: /pripadova-studie
title: Případová studie
page_title: "Případová studie: Implementace DDD v Symfony | DDD Symfony"
meta_description: "Případová studie systému pro správu projektů v DDD architektuře krok za krokem: bounded contexts, agregáty, CQRS, projekce s reconciliation a event-driven workflow v Symfony 8."
meta_keywords: "případová studie DDD, Symfony projekt, bounded contexts, strategický design, taktický design, agregáty, doménové události, CQRS, kompletní implementace, analýza domény, návrh, vývoj, testování, reálný projekt, DDD v praxi"
og_type: article
published: "2025-04-24"
modified: "2026-04-28"
breadcrumb_name: Případová studie
schema_type: TechArticle
schema_headline: "Případová studie: Implementace DDD v Symfony"
chapter_number: "25"
category: Praxe
deck: 'Detailní případová studie implementace Domain-Driven Design v Symfony 8 na kompletním projektu – celý proces od analýzy domény, identifikace bounded contexts a strategického i taktického designu až po implementaci s využitím DDD principů a CQRS.'
reading_time: 50
difficulty: 4
---

## 25.01 Úvod {#introduction}

Případová studie popisuje implementaci systému pro správu projektů pomocí Domain-Driven Design a CQRS v Symfony 8.
Systém umožňuje uživatelům vytvářet projekty, přidávat úkoly, přiřazovat úkoly členům týmu a sledovat jejich stav.
Studie klade důraz na správnou aplikaci strategických i taktických vzorů DDD v reálném projektu.

## 25.02 Požadavky {#requirements}

Systém pro správu projektů má následující požadavky:

- Uživatelé se mohou registrovat a přihlašovat.
- Uživatelé mohou vytvářet projekty.
- Uživatelé mohou přidávat úkoly do projektů.
- Uživatelé mohou přiřazovat úkoly členům týmu.
- Uživatelé mohou měnit stav úkolů (To Do, In Progress, Done).
- Uživatelé mohou přidávat komentáře k úkolům.
- Uživatelé mohou sledovat aktivitu na projektech a úkolech.
- Systém musí být škálovatelný a udržitelný.

## 25.03 Doménová analýza {#discovery}

Architektura nezačíná u kódu, ale u rozhovoru s doménovými experty. Tato sekce ukazuje, jak vznikla pětice
bounded contexts ze sekce [Architektura](#architecture) – jaká data byla na vstupu a jakými kroky
se ze seznamu událostí stala mapa kontextů. Postup čerpá z techniky *event storming* (Alberto Brandolini)
a probíhal ve třech krocích: sběr doménových událostí, jejich seskupení do subdomén a definice kontextových hranic.

### Krok 1: Sběr doménových událostí {#discovery-events-heading}

První workshop směřoval k otázce „co se v systému děje“. Doménoví experti formulovali v chronologickém
pořadí události, které pro ně mají význam. Seznam vznikl bez ohledu na strukturu kódu, frameworku nebo databáze –
cílem je zachytit slovník ([Ubiquitous Language](/zakladni-koncepty#ubiquitous-language)),
ne implementaci.

- Uživatel se zaregistroval.
- Uživatel se přihlásil.
- Uživatel vytvořil projekt.
- Vlastník projektu pozval dalšího uživatele jako člena.
- Pozvaný uživatel přijal pozvánku do projektu.
- Vlastník projektu odebral člena.
- Člen projektu přidal úkol.
- Vlastník přiřadil úkol členovi.
- Přiřazený člen převzal úkol (stav `To Do` → `In Progress`).
- Přiřazený člen dokončil úkol (`In Progress` → `Done`).
- Člen projektu přidal komentář k úkolu.
- Autor komentáře komentář upravil.
- Systém zaznamenal aktivitu pro audit.

Slovník událostí odhalil několik rozhodnutí ještě před prvním řádkem kódu. Slovo „uživatel“ má
v každém kontextu jiný význam: v **UserManagement** je to identita s e-mailem a heslem,
v **ProjectManagement** je to vlastník nebo člen, v **TaskManagement** přiřazený
řešitel a v **CommentManagement** autor textu. Stejné slovo, jiná odpovědnost. Právě toto
zjištění je zárodkem rozdělení do bounded contexts.

### Krok 2: Seskupení událostí do subdomén {#discovery-grouping-heading}

Druhý krok shlukuje události podle významu. Tým hledal odpověď na otázku, které události patří k sobě
z pohledu doménového experta. Výsledkem byla tabulka mapující událost na subdoménu:

| Subdoména | Událost | Doménový expert |
|---|---|---|
| **UserManagement** | UserRegistered | Bezpečnostní administrátor |
| **UserManagement** | UserSignedIn | Bezpečnostní administrátor |
| **ProjectManagement** | ProjectCreated | Projektový manažer |
| **ProjectManagement** | MemberAdded | Projektový manažer |
| **ProjectManagement** | MemberRemoved | Projektový manažer |
| **TaskManagement** | TaskCreated | Týmový vedoucí |
| **TaskManagement** | TaskAssigned | Týmový vedoucí |
| **TaskManagement** | TaskStatusChanged | Týmový vedoucí |
| **CommentManagement** | CommentAdded | Týmový vedoucí |
| **CommentManagement** | CommentEdited | Týmový vedoucí |
| **ActivityTracking** | ActivityRecorded | Compliance / interní audit |

Sloupec *Doménový expert* není dekorativní. Pomáhá ověřit, že se hranice kontextů skutečně kryjí
s organizační realitou. Pokud by jeden kontext potřeboval čtyři různé experty, je to signál, že jde
o agregaci nesouvisejících odpovědností. Pokud naopak dva kontexty řídí stejný expert, mohou být kandidáty
na sloučení – nebo signálem, že expert pokrývá víc rolí, než je zdravé.

### Krok 3: Definice kontextových hranic {#discovery-boundaries-heading}

Třetí krok převedl subdomény na bounded contexts – jednotky, ve kterých má slovník jeden význam, model jedny
invarianty a kód jednu modulovou hranici. Kritéria pro hranici byla tři:

1. **Sémantická koherence** – slova uvnitř kontextu mají jeden význam. Pokud uvnitř téhož
   kontextu znamená „status“ jednou stav úkolu a podruhé stav projektu, je to signál pro rozdělení.
2. **Vlastnictví domény** – každý kontext má jednoho doménového experta odpovědného za pravidla
   a slovník. Bez identifikovatelného vlastníka jsou rozhodnutí o modelu náhodná.
3. **Tempo změn** – části systému, které se mění společně, patří do téhož kontextu. Pokud změna
   v **TaskManagement** opakovaně vynucuje úpravu v **CommentManagement**, je hranice
   mezi nimi špatně vedená.

V tomto projektu zafungovala všechna tři kritéria společně. Kompletní mapa vztahů mezi kontexty
(Partnership, Customer-Supplier, Open Host Service) je v [sekci Architektura](#architecture).
Hlubší teoretický základ pro identifikaci kontextů poskytují kapitoly
[Co je Domain-Driven Design](/co-je-ddd) a
[Základní koncepty DDD](/zakladni-koncepty).

:::callout{type="note"}
Event storming není jednorázový workshop. Po prvním nasazení se ukazují události, které tým neuvažoval
(`InvitationExpired`, `TaskBlocked`) i události, které se v praxi nepoužívají.
Doménový model je *živý dokument* – při každém větším incrementu se vyplatí ověřit, že slovník
v kódu odpovídá slovníku v týmu.
:::

## 25.04 Architektura {#architecture}

Architektura kombinuje strategický a taktický DDD s CQRS v Symfony 8. Na strategické úrovni vznikla
pětice bounded contexts a kontextová mapa popisující jejich vztahy. Taktická úroveň pokrývá agregáty,
hodnotové objekty, doménové události a doménové služby. Kód je organizovaný do vertikálních sliců –
každá feature obsahuje vše od příkazu po view model, čímž se omezí šíření změn napříč vrstvami.

### Strategický design: Bounded Contexts a Context Map

Identifikace bounded contexts vychází z doménové analýzy v [sekci 25.03](#discovery).
Systém je rozdělen do následujících kontextů:

- **UserManagement** – identita, registrace, autentizace; vlastník přístupových práv uživatelů.
- **ProjectManagement** – životní cyklus projektů a členství uživatelů v projektu.
- **TaskManagement** – úkoly, jejich přiřazování a stavové přechody.
- **CommentManagement** – komentáře a zpětná vazba k úkolům.
- **ActivityTracking** – auditní stopa nad událostmi z ostatních kontextů.

:::diagram{fig="25.4-A" title="Kontextová mapa: vztahy mezi pěti bounded contexts" src="images/diagrams/15_case_study/context_map.svg"}
:::

Vztahy zachycené v kontextové mapě:

- **UserManagement ⟷ ProjectManagement** – *Partnership*. Oba kontexty
  ovlivňují společný model členství v projektu. Změna kontraktu vyžaduje koordinaci obou týmů.
- **ProjectManagement → TaskManagement** – *Customer / Supplier*.
  ProjectManagement určuje, jaký kontrakt o existenci a členství projektu TaskManagement potřebuje;
  TaskManagement se přizpůsobuje upstreamu.
- **TaskManagement → CommentManagement** – *Customer / Supplier*.
  CommentManagement vystavuje API pro komentáře nad úkoly, TaskManagement je downstream zákazník.
- **Všechny kontexty → ActivityTracking** – *Open Host Service / Published Language*.
  Vydávající kontexty publikují doménové události veřejným kontraktem (Published Language);
  ActivityTracking je čistě downstream konzument, který nemá vliv zpět.
- **Shared Kernel** – `UserId`, `ProjectId`, `TaskId`
  jsou sdílené hodnotové objekty napříč kontexty. Volba a její cena jsou rozebrány
  v [sekci 25.07.2](#trade-off-shared-kernel-heading).

**Anti-Corruption Layer (ACL)** v této studii nabývá zjednodušené podoby. Mezi
TaskManagement a ProjectManagement není potřeba sémantická translace – Shared Kernel
sdílí `ProjectId` i `UserId` –, přesto TaskManagement nesmí ze své doménové
vrstvy přímo volat infrastrukturu jiného kontextu. Hranici proto tvoří port
`ProjectChecker` definovaný v doméně TaskManagement; jeho infrastrukturní implementace
je adaptér do ProjectManagement. Port plní funkci ACL i tam, kde se nepřekládají typy: chrání
TaskManagement před přímým provázáním s interním modelem upstream kontextu. Synchronní vs.
asynchronní volba je popsaná v [sekci 25.07.3](#trade-off-sync-acl-heading).

Pro asynchronní integraci mezi kontexty slouží doménové události publikované přes Symfony Messenger.
Konkrétní ukázka projekce, která naslouchá událostem ze tří kontextů, je v
[sekci 25.06](#read-model).

### Taktický design a struktura projektu

Na taktické úrovni implementace pokrývá tyto DDD vzory:

- **Entity** – Objekty s identitou, které se v čase mění (např. User, Project, Task).
- **Value Objects** – Neměnné objekty bez identity, které reprezentují koncepty v doméně (např. UserId, ProjectId, TaskStatus).
- **Aggregates** – Shluky objektů, které doména považuje za jednu jednotku z hlediska změn dat (např. Project s TaskCollection).
- **Domain Events** – Události, které nastávají v doméně a mají význam pro doménové experty (např. ProjectCreated, TaskAssigned).
- **Repositories** – Objekty, které zapouzdřují přístup k persistenci agregátů (např. ProjectRepository, TaskRepository).
- **Domain Services** – Služby, které implementují doménovou logiku, která nepatří do žádné entity nebo hodnotového objektu (např. TaskAssignmentService).

Struktura projektu odráží jak strategický, tak taktický design DDD. Níže je ukázka správné struktury projektu, kde každý bounded context má svou vlastní doménovou vrstvu, infrastrukturu a aplikační služby:

:::code{language="bash" filename="snippet.sh"}
src/
├── UserManagement/            # Bounded Context: Správa uživatelů
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   └── User.php        # Entita uživatele (Aggregate Root)
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── UserId.php      # Identifikátor uživatele
│   │   │   └── Email.php       # Email uživatele
│   │   ├── Event/             # Doménové události
│   │   │   └── UserRegistered.php  # Událost registrace uživatele
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── UserRepository.php  # Rozhraní pro práci s uživateli
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineUserRepository.php  # Doctrine implementace
│   ├── Registration/          # Feature: Registrace uživatele
│   │   ├── Command/           # Příkazy
│   │   │   ├── RegisterUser.php  # Příkaz pro registraci uživatele
│   │   │   └── RegisterUserHandler.php  # Handler příkazu
│   │   └── Controller/        # Kontrolery
│   │       └── RegistrationController.php  # Kontroler pro registraci
│   ├── Authentication/        # Feature: Autentizace
│   │   └── Controller/        # Kontrolery
│   │       └── SecurityController.php  # Kontroler pro autentizaci
│   └── GetUser/               # Feature: Získání uživatele
│       ├── Query/             # Dotazy
│       │   ├── GetUser.php      # Dotaz pro získání uživatele
│       │   └── GetUserHandler.php  # Handler dotazu
│       └── ViewModel/         # View modely
│           └── UserViewModel.php  # View model uživatele
├── ProjectManagement/         # Bounded Context: Správa projektů
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   ├── Project.php     # Entita projektu (Aggregate Root)
│   │   │   └── ProjectMember.php  # Entita člena projektu
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── ProjectId.php    # Identifikátor projektu
│   │   │   └── ProjectStatus.php  # Status projektu
│   │   ├── Event/             # Doménové události
│   │   │   ├── ProjectCreated.php  # Událost vytvoření projektu
│   │   │   └── MemberAdded.php  # Událost přidání člena
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── ProjectRepository.php  # Rozhraní pro práci s projekty
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineProjectRepository.php  # Doctrine implementace
│   ├── CreateProject/         # Feature: Vytvoření projektu
│   │   ├── Command/           # Příkazy
│   │   │   ├── CreateProject.php  # Příkaz pro vytvoření projektu
│   │   │   └── CreateProjectHandler.php  # Handler příkazu
│   │   └── Controller/        # Kontrolery
│   │       └── ProjectController.php  # Kontroler pro vytvoření projektu
│   └── GetProjects/           # Feature: Seznam projektů
│       ├── Query/             # Dotazy
│       │   ├── GetProjects.php  # Dotaz pro získání projektů
│       │   └── GetProjectsHandler.php  # Handler dotazu
│       ├── Controller/        # Kontrolery
│       │   └── ProjectsController.php  # Kontroler pro seznam projektů
│       └── ViewModel/         # View modely
│           └── ProjectViewModel.php  # View model projektu
├── TaskManagement/            # Bounded Context: Správa úkolů
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   └── Task.php        # Entita úkolu (Aggregate Root)
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── TaskId.php      # Identifikátor úkolu
│   │   │   └── TaskStatus.php  # Status úkolu
│   │   ├── Event/             # Doménové události
│   │   │   ├── TaskCreated.php  # Událost vytvoření úkolu
│   │   │   ├── TaskAssigned.php  # Událost přiřazení úkolu
│   │   │   └── TaskStatusChanged.php  # Událost změny stavu
│   │   ├── Service/           # Doménové služby
│   │   │   └── TaskAssignmentService.php  # Služba pro přiřazení úkolu
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── TaskRepository.php  # Rozhraní pro práci s úkoly
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineTaskRepository.php  # Doctrine implementace
│   ├── CreateTask/            # Feature: Vytvoření úkolu
│   │   ├── Command/           # Příkazy
│   │   │   ├── CreateTask.php   # Příkaz pro vytvoření úkolu
│   │   │   └── CreateTaskHandler.php  # Handler příkazu
│   │   └── Controller/        # Kontrolery
│   │       └── TaskController.php  # Kontroler pro úkoly
│   ├── AssignTask/            # Feature: Přiřazení úkolu
│   │   ├── Command/           # Příkazy
│   │   │   ├── AssignTask.php   # Příkaz pro přiřazení úkolu
│   │   │   └── AssignTaskHandler.php  # Handler příkazu
│   │   └── Controller/        # Kontrolery
│   │       └── AssignController.php  # Kontroler pro přiřazení
│   ├── ChangeStatus/          # Feature: Změna stavu úkolu
│   │   ├── Command/           # Příkazy
│   │   │   ├── ChangeTaskStatus.php  # Příkaz pro změnu stavu
│   │   │   └── ChangeTaskStatusHandler.php  # Handler příkazu
│   │   └── Controller/        # Kontrolery
│   │       └── StatusController.php  # Kontroler pro změnu stavu
│   └── GetTask/               # Feature: Získání úkolu
│       ├── Query/             # Dotazy
│       │   ├── GetTask.php      # Dotaz pro získání úkolu
│       │   └── GetTaskHandler.php  # Handler dotazu
│       └── ViewModel/         # View modely
│           └── TaskViewModel.php  # View model úkolu
├── CommentManagement/         # Bounded Context: Správa komentářů
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   └── Comment.php     # Entita komentáře
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   └── CommentId.php   # Identifikátor komentáře
│   │   ├── Event/             # Doménové události
│   │   │   └── CommentAdded.php  # Událost přidání komentáře
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── CommentRepository.php  # Rozhraní pro práci s komentáři
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineCommentRepository.php  # Doctrine implementace
│   └── AddComment/            # Feature: Přidání komentáře
│       ├── Command/           # Příkazy
│       │   ├── AddComment.php   # Příkaz pro přidání komentáře
│       │   └── AddCommentHandler.php  # Handler příkazu
│       └── Controller/        # Kontrolery
│           └── CommentController.php  # Kontroler pro komentáře
├── ActivityTracking/          # Bounded Context: Sledování aktivity
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   └── Activity.php    # Entita aktivity
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   └── ActivityId.php  # Identifikátor aktivity
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── ActivityRepository.php  # Rozhraní pro práci s aktivitami
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineActivityRepository.php  # Doctrine implementace
│   └── RecordActivity/        # Feature: Zaznamenání aktivity
│       ├── Command/           # Příkazy
│       │   ├── RecordActivity.php  # Příkaz pro zaznamenání aktivity
│       │   └── RecordActivityHandler.php  # Handler příkazu
│       └── Controller/        # Kontrolery
│           └── ActivityController.php  # Kontroler pro aktivity
└── Shared/                    # Sdílené komponenty
    ├── Domain/                # Sdílená doménová logika
    │   ├── Exception/         # Výjimky
    │   │   └── DomainException.php  # Základní doménová výjimka
    │   └── Bus/               # Rozhraní pro message bus
    │       ├── CommandBus.php   # Rozhraní pro command bus
    │       └── QueryBus.php     # Rozhraní pro query bus
    └── Infrastructure/        # Sdílená infrastruktura
        ├── Bus/               # Implementace message bus
        │   ├── MessengerCommandBus.php  # Implementace command bus
        │   └── MessengerQueryBus.php  # Implementace query bus
        └── Persistence/        # Sdílená persistence
            └── DoctrineTypes/    # Vlastní Doctrine typy
                └── UuidType.php    # Typ pro UUID
:::

## 25.05 Implementace {#implementation}

Následující sekce ukazuje implementaci hlavních částí systému s aplikací DDD principů.

### Ubiquitous Language {#ubiquitous-language-heading}

Ubiquitous Language vznikl ve spolupráci s doménovými experty ještě před zahájením implementace. Stejné pojmy se používají v kódu, dokumentaci i komunikaci. Hlavní pojmy:

- **Project** – Organizační jednotka, která sdružuje související úkoly a členy týmu.
- **Task** – Jednotka práce, která má být dokončena v projektu.
- **Assignee** – Člen týmu, kterému je přiřazen úkol.
- **Status** – Stav úkolu (To Do, In Progress, Done).
- **Comment** – Textová zpětná vazba k úkolu.
- **Activity** – Záznam o akci provedené v systému.

### Doménový model: Projekt (kořen agregátu) {#project-model-heading}

Agregát používá Doctrine atributy přímo na doménové třídě – jako pragmatický default,
v souladu s [kapitolou 11](/implementace-v-symfony#mapping-volba-heading). Třída je `final`,
dědí z `AggregateRoot` (sdílené chování pro `record` a `releaseDomainEvents`),
konstruktor je `private` a vznik agregátu probíhá přes statickou factory metodu `create()`.

:::code{language="php" filename="src/ProjectManagement/Domain/Model/Project.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Model;

use App\ProjectManagement\Domain\Event\MemberAdded;
use App\ProjectManagement\Domain\Event\MemberRemoved;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\Shared\Domain\AggregateRoot;
// UserId je sdílený v Shared Kernelu (viz sekci 25.07.2)
use App\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'projects')]
final class Project extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'project_id')]
    public readonly ProjectId $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'user_id')]
    public readonly UserId $ownerId;

    /** @var list<UserId> */
    #[ORM\Column(type: 'user_id_list')]
    private array $memberIds = [];

    #[ORM\Column(type: 'datetime_immutable')]
    public readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    private function __construct(ProjectId $id, string $name, ?string $description, UserId $ownerId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->ownerId = $ownerId;
        $this->memberIds = [$ownerId];
        $this->createdAt = new \DateTimeImmutable();

        $this->record(new ProjectCreated($id, $name, $ownerId));
    }

    public static function create(ProjectId $id, string $name, ?string $description, UserId $ownerId): self
    {
        return new self($id, $name, $description, $ownerId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /** @return list<UserId> */
    public function memberIds(): array
    {
        return $this->memberIds;
    }

    public function addMember(UserId $userId): void
    {
        foreach ($this->memberIds as $existingId) {
            if ($existingId->equals($userId)) {
                return; // již je členem – idempotentní operace
            }
        }
        $this->memberIds[] = $userId;
        $this->updatedAt = new \DateTimeImmutable();

        $this->record(new MemberAdded($this->id, $userId));
    }

    public function removeMember(UserId $userId): void
    {
        if ($this->ownerId->equals($userId)) {
            throw new \DomainException('Vlastníka projektu nelze odebrat z členů.');
        }

        $before = count($this->memberIds);
        $this->memberIds = array_values(array_filter(
            $this->memberIds,
            fn(UserId $id) => !$id->equals($userId),
        ));

        if (count($this->memberIds) === $before) {
            return; // nebyl členem – idempotentní operace
        }

        $this->updatedAt = new \DateTimeImmutable();
        $this->record(new MemberRemoved($this->id, $userId));
    }

    public function rename(string $newName): void
    {
        if ($this->name === $newName) {
            return;
        }
        $this->name = $newName;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeDescription(?string $newDescription): void
    {
        if ($this->description === $newDescription) {
            return;
        }
        $this->description = $newDescription;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
:::

:::callout{type="note"}
`UserId` je v této studii sdílen mezi kontexty přes Shared Kernel –
jeho cena a alternativa (samostatný primitiv v každém kontextu) jsou rozebrány
v [sekci 25.07.2](#trade-off-shared-kernel-heading). V kontextech, kde
by se model musel rozejít (jiná validace, jiná sériová reprezentace), by sdílení
přes Shared Kernel nestačilo a kontext by si držel vlastní kopii.
:::

### Doménový model: Úkol (kořen agregátu) {#task-model-heading}

:::code{language="php" filename="src/TaskManagement/Domain/Model/Task.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Event\TaskCreated;
use App\TaskManagement\Domain\Event\TaskAssigned;
use App\TaskManagement\Domain\Event\TaskStatusChanged;
use App\TaskManagement\Domain\ValueObject\TaskId;
use App\TaskManagement\Domain\ValueObject\TaskStatus;
// ProjectId a UserId jsou sdílené v Shared Kernelu (viz sekci 25.07.2)
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\UserManagement\Domain\ValueObject\UserId;

class Task
{
    private readonly TaskId $id;
    private string $title;
    private ?string $description;
    private readonly ProjectId $projectId;
    private ?UserId $assigneeId = null;
    private TaskStatus $status;
    private readonly \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    private array $domainEvents = [];

    private function __construct(TaskId $id, string $title, ?string $description, ProjectId $projectId)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->projectId = $projectId;
        $this->status = TaskStatus::TODO;
        $this->createdAt = new \DateTimeImmutable();

        $this->recordEvent(new TaskCreated($id, $title, $projectId));
    }

    public static function create(TaskId $id, string $title, ?string $description, ProjectId $projectId): self
    {
        return new self($id, $title, $description, $projectId);
    }

    public function id(): TaskId
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function assigneeId(): ?UserId
    {
        return $this->assigneeId;
    }

    public function status(): TaskStatus
    {
        return $this->status;
    }

    public function assign(UserId $assigneeId): void
    {
        $this->assigneeId = $assigneeId;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new TaskAssigned($this->id, $assigneeId));
    }

    public function unassign(): void
    {
        $this->assigneeId = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeStatus(TaskStatus $status): void
    {
        $oldStatus = $this->status;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new TaskStatusChanged($this->id, $oldStatus, $status));
    }

    public function updateTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
:::

### Doménové události {#domain-events-heading}

Agregáty publikují skutečnosti, které pro doménu mají význam. Každá událost je neměnný záznam minulé skutečnosti –
proto jsou všechny třídy `final readonly` s veřejnými promovanými parametry. Atribut
`occurredAt` nese okamžik vzniku v UTC; payload obsahuje minimální množinu identifikátorů
a hodnot potřebnou k rekonstrukci kontextu. Teoretický základ doménových událostí je v kapitole
[Základní koncepty DDD](/zakladni-koncepty#domain-events); návaznost na Event
Sourcing v kapitole [Event Sourcing](/event-sourcing).

:::code{language="php" filename="src/ProjectManagement/Domain/Event/ProjectCreated.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\UserManagement\Domain\ValueObject\UserId;

final readonly class ProjectCreated
{
    public function __construct(
        public ProjectId $projectId,
        public string $name,
        public UserId $ownerId,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
:::

:::code{language="php" filename="src/ProjectManagement/Domain/Event/MemberAdded.php a MemberRemoved.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\UserManagement\Domain\ValueObject\UserId;

final readonly class MemberAdded
{
    public function __construct(
        public ProjectId $projectId,
        public UserId $userId,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}

final readonly class MemberRemoved
{
    public function __construct(
        public ProjectId $projectId,
        public UserId $userId,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
:::

:::code{language="php" filename="src/TaskManagement/Domain/Event/TaskCreated.php, TaskAssigned.php, TaskStatusChanged.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\TaskManagement\Domain\ValueObject\TaskId;
use App\TaskManagement\Domain\ValueObject\TaskStatus;
use App\UserManagement\Domain\ValueObject\UserId;

final readonly class TaskCreated
{
    public function __construct(
        public TaskId $taskId,
        public string $title,
        public ProjectId $projectId,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}

final readonly class TaskAssigned
{
    public function __construct(
        public TaskId $taskId,
        public UserId $assigneeId,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}

final readonly class TaskStatusChanged
{
    public function __construct(
        public TaskId $taskId,
        public TaskStatus $oldStatus,
        public TaskStatus $newStatus,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
:::

:::callout{type="note"}
Doménová událost zde nese pouze identifikátory a hodnoty, ne celý agregát. Konzument události
si v případě potřeby dohledá zbytek dat přes repozitář nebo lokální projekci. Tlustý payload
(sériově předávané reference na celý agregát) je anti-vzor – při opakovaném zpracování může vést
k nekonzistentnímu stavu, pokud se mezitím agregát změnil.
:::

### Hodnotové objekty: identifikátory a stav úkolu {#value-objects-heading}

Identifikátory `ProjectId`, `TaskId` a `UserId` sdílí společné
rozhraní: konstruktor bez argumentů vygeneruje nové UUID, konstruktor se stringem ho ověří
a uloží. Metoda `value()` vrací surový string pro persistenci, `equals()`
srovnává podle hodnoty. `TaskStatus` je výčtový typ s explicitním doménovým jazykem.
Plný rozbor Value Objektů je v kapitole
[Základní koncepty DDD](/zakladni-koncepty#value-objects).

:::code{language="php" filename="src/ProjectManagement/Domain/ValueObject/ProjectId.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final class ProjectId
{
    private readonly string $value;

    public function __construct(string $value = '')
    {
        $resolved = $value === '' ? Uuid::v7()->toRfc4122() : $value;
        if (!Uuid::isValid($resolved)) {
            throw new \InvalidArgumentException('ProjectId must be a valid UUID');
        }
        $this->value = $resolved;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
:::

:::code{language="php" filename="src/TaskManagement/Domain/ValueObject/TaskStatus.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\ValueObject;

enum TaskStatus: string
{
    case TODO        = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE        = 'done';

    public function canTransitionTo(self $next): bool
    {
        return match ([$this, $next]) {
            [self::TODO,        self::IN_PROGRESS] => true,
            [self::IN_PROGRESS, self::DONE]        => true,
            [self::IN_PROGRESS, self::TODO]        => true,
            default                                 => false,
        };
    }
}
:::

:::callout{type="note"}
Konstruktor `new ProjectId()` bez argumentů generuje UUID v7 (časově řazené,
vhodné jako primární klíč). `new ProjectId($uuid)` hydratuje existující identifikátor
z databáze nebo z příchozího příkazu. `TaskId` a `UserId` následují
stejnou konvenci. Diskuse o sdílení těchto VO mezi kontexty (Shared Kernel vs. duplikace)
je v [sekci 25.07.2](#trade-off-shared-kernel-heading).
:::

### Command: Vytvoření projektu (Command Pattern) {#create-project-command-heading}

:::code{language="php" filename="src/ProjectManagement/CreateProject/Command/CreateProject.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\CreateProject\Command;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProject
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public readonly string $name,

        public readonly ?string $description,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $ownerId
    ) {
    }
}
:::

### Command Handler: Zpracování vytvoření projektu (Application Service) {#create-project-handler-heading}

:::code{language="php" filename="src/ProjectManagement/CreateProject/Command/CreateProjectHandler.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\CreateProject\Command;

use App\ProjectManagement\Domain\Model\Project;
use App\ProjectManagement\Domain\Repository\ProjectRepository;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateProjectHandler
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {
    }

    public function __invoke(CreateProject $command): string
    {
        $projectId = new ProjectId();

        $project = Project::create(
            $projectId,
            $command->name,
            $command->description,
            new UserId($command->ownerId)
        );

        $this->projectRepository->save($project);

        return $projectId->value();
    }
}
:::

### Command: Přiřazení úkolu (Command Pattern) {#assign-task-command-heading}

:::code{language="php" filename="src/TaskManagement/AssignTask/Command/AssignTask.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\AssignTask\Command;

use Symfony\Component\Validator\Constraints as Assert;

class AssignTask
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $taskId,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $assigneeId
    ) {
    }
}
:::

### Command Handler: Zpracování přiřazení úkolu (Application Service) {#assign-task-handler-heading}

:::code{language="php" filename="src/TaskManagement/AssignTask/Command/AssignTaskHandler.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\AssignTask\Command;

use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\TaskManagement\Domain\Port\ProjectChecker;
use App\TaskManagement\Domain\Repository\TaskRepository;
use App\TaskManagement\Domain\Service\TaskAssignmentService;
use App\TaskManagement\Domain\ValueObject\TaskId;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Port ProjectChecker tvoří hranici (Anti-Corruption Layer) mezi TaskManagement
 * a ProjectManagement. TaskManagement nezná interní model ProjectManagement;
 * adaptér v infrastruktuře přeloží dotaz na konkrétní volání upstream kontextu.
 *
 * Skutečné rozhraní:
 *   interface ProjectChecker {
 *       public function exists(ProjectId $projectId): bool;
 *       public function isMember(ProjectId $projectId, UserId $userId): bool;
 *   }
 */
#[AsMessageHandler]
class AssignTaskHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly ProjectChecker $projectChecker,
        private readonly TaskAssignmentService $taskAssignmentService
    ) {
    }

    public function __invoke(AssignTask $command): void
    {
        $task = $this->taskRepository->findById(new TaskId($command->taskId));

        if (!$task) {
            throw new \DomainException('Task not found');
        }

        $assigneeId = new UserId($command->assigneeId);

        // Ověření přes port - bez přímé závislosti na ProjectManagement
        if (!$this->projectChecker->exists($task->projectId())) {
            throw new \DomainException('Project not found');
        }

        if (!$this->projectChecker->isMember($task->projectId(), $assigneeId)) {
            throw new \DomainException('Assignee is not a member of the project');
        }

        // Použití doménové služby pro přiřazení úkolu
        $this->taskAssignmentService->assignTask($task, $assigneeId);

        // Uložení úkolu
        $this->taskRepository->save($task);
    }
}
:::

### Query: Získání projektů uživatele (Query Pattern) {#get-projects-query-heading}

:::code{language="php" filename="src/ProjectManagement/GetProjects/Query/GetProjects.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\GetProjects\Query;

use Symfony\Component\Validator\Constraints as Assert;

class GetProjects
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId
    ) {
    }
}
:::

### Query Handler: Zpracování získání projektů uživatele (Read Model) {#get-projects-handler-heading}

:::code{language="php" filename="src/ProjectManagement/GetProjects/Query/GetProjectsHandler.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\GetProjects\Query;

use App\ProjectManagement\Domain\Repository\ProjectRepository;
use App\ProjectManagement\GetProjects\ViewModel\ProjectViewModel;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetProjectsHandler
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {
    }

    public function __invoke(GetProjects $query): array
    {
        $projects = $this->projectRepository->findByMemberId(new UserId($query->userId));

        $result = [];

        foreach ($projects as $project) {
            $result[] = new ProjectViewModel(
                $project->id()->value(),
                $project->name(),
                $project->description(),
                $project->ownerId()->value(),
                count($project->memberIds()),
                $project->createdAt()
            );
        }

        return $result;
    }
}
:::

### Doménová služba: Přiřazení úkolu {#task-assignment-service-heading}

:::code{language="php" filename="src/TaskManagement/Domain/Service/TaskAssignmentService.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Service;

use App\TaskManagement\Domain\Model\Task;
use App\UserManagement\Domain\ValueObject\UserId;

class TaskAssignmentService
{
    // Doménová služba pracuje výhradně s objekty vlastního bounded contextu.
    // Ověření příslušnosti k projektu zajišťuje handler přes ProjectChecker port.
    public function assignTask(Task $task, UserId $assigneeId): void
    {
        $task->assign($assigneeId);
    }
}
:::

## 25.06 Read modely a projekce {#read-model}

Implementace v [sekci Implementace](#implementation) ukazuje stranu zápisu (commands).
`GetProjectsHandler` ale stále načítá projekty přes doménový repozitář – tedy hydratuje agregáty,
i když potřebuje jen tabulkový výpis. Pro malý dataset to funguje. Při růstu (tisíce projektů, desetitisíce
úkolů, obohacený výpis se jmény členů a počtem úkolů) by každý dotaz znamenal opakované `JOIN`y
a hydratace doménových objektů jen pro účely zobrazení.

Tato sekce ukazuje, jak v systému postupně vznikl samostatný read model. Princip: doménové události
aktualizují denormalizovanou tabulku, ze které čte *query handler*. Žádný `JOIN` mezi
agregáty, žádná hydratace doménových objektů. Hlubší teoretický základ je v kapitolách
[CQRS](/cqrs) a [Výkonnostní aspekty](/vykonnostni-aspekty).

### Schéma read modelu {#read-model-schema-heading}

Tabulka `project_list_view` drží tvar potřebný pro výpis projektů uživatele. Není normalizovaná –
obsahuje vypočítané hodnoty (`member_count`, `task_count`) a denormalizované pole
`member_ids` jako JSON. Tato tabulka není zdrojem pravdy; lze ji kdykoli rebuildovat z událostí
nebo z primárních tabulek.

:::code{language="php" filename="src/ProjectManagement/Infrastructure/ReadModel/ProjectListView.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ReadModel;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'project_list_view')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_owner')]
#[ORM\Index(columns: ['member_ids'], name: 'idx_members', flags: ['gin'])]
class ProjectListView
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public string $projectId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: Types::GUID)]
    public string $ownerId;

    #[ORM\Column(type: Types::JSON)]
    public array $memberIds = [];

    #[ORM\Column(type: Types::INTEGER)]
    public int $memberCount = 0;

    #[ORM\Column(type: Types::INTEGER)]
    public int $taskCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;
}
:::

### Projection: aktualizace read modelu z událostí {#read-model-projection-heading}

Projekce naslouchá doménovým událostem ze všech kontextů, které mají vliv na podobu výpisu projektů.
Běží jako asynchronní message handler – mimo originální transakci, takže ji nemůže shodit.

:::code{language="php" filename="src/ProjectManagement/Infrastructure/ReadModel/ProjectListProjection.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ReadModel;

use App\ProjectManagement\Domain\Event\MemberAdded;
use App\ProjectManagement\Domain\Event\MemberRemoved;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\TaskManagement\Domain\Event\TaskCreated;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProjectListProjection
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function __invoke(object $event): void
    {
        match (true) {
            $event instanceof ProjectCreated => $this->onProjectCreated($event),
            $event instanceof MemberAdded    => $this->onMemberAdded($event),
            $event instanceof MemberRemoved  => $this->onMemberRemoved($event),
            $event instanceof TaskCreated    => $this->onTaskCreated($event),
            default                          => null,
        };
    }

    private function onProjectCreated(ProjectCreated $event): void
    {
        $now = new \DateTimeImmutable();
        $view = new ProjectListView();
        $view->projectId = $event->projectId->value();
        $view->name = $event->name;
        $view->ownerId = $event->ownerId->value();
        $view->memberIds = [$event->ownerId->value()];
        $view->memberCount = 1;
        $view->taskCount = 0;
        $view->createdAt = $now;
        $view->updatedAt = $now;
        $this->em->persist($view);
        $this->em->flush();
    }

    private function onMemberAdded(MemberAdded $event): void
    {
        $view = $this->em->find(ProjectListView::class, $event->projectId->value());
        if ($view === null) {
            // Out-of-order delivery: MemberAdded přišlo dřív než ProjectCreated.
            // Reconciler (sekce 25.06.5) dohledá zaostalou view a obnoví ji
            // ze zdrojových agregátů.
            return;
        }
        $userId = $event->userId->value();
        if (!in_array($userId, $view->memberIds, strict: true)) {
            $view->memberIds[] = $userId;
            $view->memberCount++;
            $view->updatedAt = new \DateTimeImmutable();
            $this->em->flush();
        }
    }

    private function onMemberRemoved(MemberRemoved $event): void
    {
        $view = $this->em->find(ProjectListView::class, $event->projectId->value());
        if ($view === null) {
            return;
        }
        $userId = $event->userId->value();
        $view->memberIds = array_values(array_filter(
            $view->memberIds,
            static fn(string $id): bool => $id !== $userId
        ));
        $view->memberCount = count($view->memberIds);
        $view->updatedAt = new \DateTimeImmutable();
        $this->em->flush();
    }

    private function onTaskCreated(TaskCreated $event): void
    {
        $view = $this->em->find(ProjectListView::class, $event->projectId->value());
        if ($view === null) {
            return;
        }
        $view->taskCount++;
        $view->updatedAt = new \DateTimeImmutable();
        $this->em->flush();
    }
}
:::

### Query handler nad read modelem (revize `GetProjectsHandler`) {#read-model-query-heading}

Naivní verze ze [sekce 25.05](#get-projects-handler-heading) hydratovala doménové agregáty
jen kvůli zobrazení. Po zavedení projekce se třída `GetProjectsHandler` přepsala na čistý
DBAL dotaz nad read tabulkou. Žádné agregáty, žádná doménová logika – jen výběr sloupců a mapování
na `ProjectViewModel`. Stejný název třídy, stejný command, jiná implementace; volající
ani Symfony Messenger o změně nevědí.

:::code{language="php" filename="src/ProjectManagement/GetProjects/Query/GetProjectsHandler.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\GetProjects\Query;

use App\ProjectManagement\GetProjects\ViewModel\ProjectViewModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetProjectsHandler
{
    public function __construct(
        private readonly Connection $db
    ) {
    }

    /** @return ProjectViewModel[] */
    public function __invoke(GetProjects $query): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT project_id, name, description, owner_id, member_count, task_count, created_at
             FROM project_list_view
             WHERE member_ids @> :userId
             ORDER BY updated_at DESC',
            ['userId' => json_encode([$query->userId])]
        );

        return array_map(
            static fn(array $row): ProjectViewModel => new ProjectViewModel(
                projectId:   $row['project_id'],
                name:        $row['name'],
                description: $row['description'],
                ownerId:     $row['owner_id'],
                memberCount: (int) $row['member_count'],
                taskCount:   (int) $row['task_count'],
                createdAt:   new \DateTimeImmutable($row['created_at']),
            ),
            $rows
        );
    }
}
:::

### Idempotence projekce a reconciliation {#read-model-reconciliation-heading}

Asynchronní doručování přes Messenger nezaručuje pořadí zpráv: pokud transport přerozdělí
zprávy mezi více workerů, může `MemberAdded` dorazit dřív než `ProjectCreated`
téhož projektu. Projekce na to musí být připravená dvěma vlastnostmi.

**Idempotence.** Opakované zpracování téže události nesmí změnit výsledek. V ukázce
výše to zajišťují tři detaily: `onMemberAdded` nepřidá uživatele dvakrát díky kontrole
`in_array(..., strict: true)`; `onMemberRemoved` přepočítává
`memberCount` z aktuální délky pole, ne inkrementem; `onProjectCreated`
při kolizi PK skončí výjimkou, kterou Messenger zaloguje a dál se nepokouší (po prvním zpracování
už view existuje). Pro silnější záruku lze do `project_list_view` přidat sloupec
`last_event_id` a každou událost zpracovat jen tehdy, pokud její ID je novější.

**Reconciler.** Pokud událost přijde mimo pořadí (handler vrátí `return` bez zápisu, protože `$view === null`) nebo se ztratí, projekce zůstává zastaralá. Reconciler je samostatný proces, který
v pravidelném intervalu detekuje rozdíl mezi write modelem a read modelem a doplní chybějící data.
V této studii je řešen jako Symfony console command spouštěný z cronu jednou za hodinu (frekvence je
kompromis mezi čerstvostí a zatížením DB):

:::code{language="php" filename="src/ProjectManagement/Infrastructure/ReadModel/ReconcileProjectListView.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ReadModel;

use App\ProjectManagement\Domain\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'project-list:reconcile',
    description: 'Dorovná zaostalý read model project_list_view ze zdrojových agregátů.',
)]
final class ReconcileProjectListView extends Command
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();
        $repaired = 0;

        foreach ($this->projects->all() as $project) {
            $view = $this->em->find(ProjectListView::class, $project->id()->value());
            $expectedMembers = array_map(
                static fn($id) => $id->value(),
                $project->memberIds(),
            );

            if ($view === null) {
                $view = new ProjectListView();
                $view->projectId  = $project->id()->value();
                $view->ownerId    = $project->ownerId()->value();
                $view->createdAt  = $project->createdAt();
                $this->em->persist($view);
            }

            $needsRepair = $view->name !== $project->name()
                || $view->memberIds !== $expectedMembers
                || $view->memberCount !== count($expectedMembers);

            if (!$needsRepair) {
                continue;
            }

            $view->name        = $project->name();
            $view->description = $project->description();
            $view->memberIds   = $expectedMembers;
            $view->memberCount = count($expectedMembers);
            $view->updatedAt   = $now;
            $repaired++;
        }

        $this->em->flush();
        $output->writeln(sprintf('Dorovnáno %d projektů.', $repaired));

        return Command::SUCCESS;
    }
}
:::

Reconciler nepřebírá roli projekce; jen dorovnává to, co projekce z technických důvodů
nedoručila. Pro `task_count` by se obdobně načetly počty úkolů z
`TaskRepository`. V provozu je užitečné mít alert, který detekuje rozdíl
a varuje, pokud je počet dorovnaných záznamů vysoký – signalizuje to systémový problém
s transportem, ne jen drobné pořadí zpráv.

:::callout{type="note"}
Pokud projekt přejde na Event Sourcing, reconciler se zjednoduší: znovuvytvoří view čistě
replay-em událostí z event store. V tomto projektu reconciler čte přímo write model,
protože doménové události nejsou perzistentní (zveřejňují se transientně přes Messenger).
Detaily v kapitole [Event Sourcing](/event-sourcing).
:::

### Důsledky pro konzistenci {#read-model-consistency-heading}

Read model je *eventually consistent*. Mezi commitem zápisu a aktualizací projekce existuje krátké okno
(typicky milisekundy, při zatížení Messengeru sekundy), ve kterém vrácený seznam ještě neobsahuje nově
vytvořený projekt. Praxe ukazuje dvě cesty, jak tento gap pokrýt:

- **Optimistická aktualizace UI** – po úspěšné odpovědi na command klient přidá záznam
  do lokálního stavu a teprve po další navigaci načítá aktualizovaný read model. Uživatel okamžitě vidí
  výsledek své akce.
- **Read-your-writes přes write model** – pro kritické dotazy okamžitě po commandu (např.
  stránka *Detail nově vytvořeného projektu*) handler čte přímo z write modelu nebo z cache namapované
  na ID právě dokončené operace. Cena: ztráta výhod read modelu pro tento jeden flow.

:::callout{type="warn"}
Outbox pattern je předpokladem spolehlivé projekce. Bez něj může transakce zápisu agregátu projít, ale
publikace události na transport selhat – read model zůstane navždy nesynchronizovaný. Detaily v kapitole
[Event Sourcing](/event-sourcing) v sekci o transactional outbox.
:::

## 25.07 Výzvy a rozhodnutí {#trade-offs}

Žádný projekt v DDD nezačíná hotový. Pět níže uvedených rozhodnutí představuje místa, kde tým váhal mezi
dvěma legitimními možnostmi. Cílem této sekce není ukázat „správnou“ odpověď, ale popsat kontext, který určil
volbu, a její cenu. Stejné rozhodnutí v jiném projektu by mohlo dopadnout jinak.

### 1. Eventual consistency napříč kontexty {#trade-off-consistency-heading}

**Otázka:** má být zápis aktivity v **ActivityTracking** součástí téže transakce
jako vydávající operace (např. zápis projektu), nebo asynchronní reakce na publikovanou událost?

**Volba:** asynchronní zpracování přes Messenger transport. Audit se nesmí stát single point of
failure pro hlavní use case. Pokud je transport pro audit nedostupný, zápis projektu se přesto úspěšně
dokončí; aktivita se zaznamená později při replay z outbox tabulky.

**Cena:** uživatel s rolí auditor vidí novou aktivitu se zpožděním. Pro audit log, kde čtenář
není stejný uživatel jako autor akce, je toto zpoždění přijatelné. Pro real-time notifikace by tento
kompromis nestačil – tam pomůže synchronní integrace nebo websocket push z projekce.

### 2. Shared Kernel vs. duplikace identifikátorů {#trade-off-shared-kernel-heading}

**Otázka:** `UserId` se objevuje ve všech kontextech (vlastník projektu, přiřazený
řešitel, autor komentáře). Bude jedna sdílená třída ve *Shared Kernel*, nebo si každý kontext drží
vlastní reprezentaci jako primitivní string?

**Volba:** Shared Kernel pro `UserId`, `ProjectId`, `TaskId`.
Tým je jeden, deploy je jeden, riziko, že se UUID formát mezi kontexty rozejde, je zanedbatelné. Sdílená třída
navíc zajistí konzistentní validaci.

**Cena:** sdílený package mezi kontexty. Když jeden kontext rozšíří `UserId` o novou
validaci, dotkne se to všech ostatních. Refaktor napříč Shared Kernelem je v praxi koordinovaný release.

**Alternativa:** Pokud by se tým štěpil nebo se kontexty oddělovaly do samostatných služeb,
primitivní string by byl bezpečnější (každý kontext si validuje sám) za cenu duplikace. Pro monolit
s jedním deploy pipeline je Shared Kernel pragmatičtější.

### 3. Synchronní ACL přes port vs. asynchronní reakce na event {#trade-off-sync-acl-heading}

**Otázka:** při přiřazení úkolu (`AssignTask`) musí **TaskManagement**
ověřit, že přiřazovaný uživatel je členem projektu. Synchronní volání portu `ProjectChecker`, nebo
čistě asynchronní reakce na `TaskAssignmentRequested` a kompenzace, pokud členství neplatí?

**Volba:** synchronní port. Operace musí selhat okamžitě, pokud uživatel není členem projektu.
Uživatel čeká na odpověď příkazu a chce hned vědět, zda přiřazení prošlo, nebo proč ne.

**Cena:** **TaskManagement** má časovou závislost na **ProjectManagement**.
Pokud druhý kontext není dostupný, přiřazení selže. V monolitu je tato závislost neviditelná, ve světě služeb
přidá síťový hop a riziko cascading failures.

**Alternativa pro distribuovaný systém:** **TaskManagement** by si držel lokální
projekci „project members“ aktualizovanou přes eventy z **ProjectManagement**. Validace by běžela
nad lokální tabulkou, bez síťového volání. Pro monolit jde o předčasnou optimalizaci, ale jakmile by se kontexty
oddělily, je to první refaktor, který by měl proběhnout. Pokud by validace selhala až po dokončení přiřazení,
stav vrací kompenzační scénář – vzor, který popisuje kapitola
[Sagas a Process Manager](/sagy-a-process-managery).

### 4. Doménová služba vs. logika v handleru {#trade-off-domain-service-heading}

**Otázka:** `TaskAssignmentService::assignTask()` aktuálně volá pouze
`Task::assign()`. Má smysl mít doménovou službu, která jen deleguje?

**Volba:** zachovat ji jako *extension point*. Přiřazení úkolu je doménový koncept, který
v budoucnu zřejmě poroste – notifikace přiřazenému, kontrola pracovní zátěže, validace deadline, integrace
s kalendářem. Vystavená abstrakce dovolí přidat tato pravidla, aniž by se dotkly handleru, controlleru ani
agregátu.

**Cena:** aktuálně prázdná abstrakce, která může čtenáři kódu připadat nadbytečná.

**Alternativa:** inline volání v handleru a refaktor ve chvíli, kdy vznikne první důvod pro
doménovou službu. YAGNI v praxi. Volba mezi těmito dvěma cestami je věcí týmové dohody – obě jsou v DDD
legitimní.

### 5. Velikost agregátu Project {#trade-off-aggregate-size-heading}

**Otázka:** má `Project` obsahovat seznam úkolů (`Task[]`) a být velkým
agregátem, nebo jsou `Project` a `Task` dva samostatné agregáty propojené přes
`ProjectId`?

**Volba:** dva samostatné agregáty. `Task` drží `ProjectId` jako referenci,
ale není uvnitř `Project`.

**Důvody:**

- Přidání úkolu nemusí způsobovat update verze projektu (žádné optimistické locking konflikty).
- Načítání projektu nemusí načítat všechny úkoly – výpis projektu zůstává levný.
- Souběžné přidávání úkolů různými uživateli nezpůsobuje konflikt na agregátu projektu.
- Transakční hranice úkolu je omezená; menší agregát = menší zámek = vyšší propustnost.

**Cena:** invariant „úkol patří do existujícího projektu“ se vynucuje na úrovni handleru
(přes `ProjectChecker`), ne v doménovém modelu. Při přímém zápisu do databáze (např. data import)
může vzniknout úkol bez projektu. Foreign key constraint na `project_id` tomu zabrání na úrovni
infrastruktury.

**Alternativa:** Pokud by aplikace vyžadovala invariant „projekt nesmí mít víc než 50 úkolů“,
nabízejí se dvě cesty: přesunout pravidlo do doménové služby s explicitním kontraktem, nebo z `Task`
udělat komponentu uvnitř `Project` agregátu (hůř škálovatelné, ale konzistentní s ohledem
na invariant). Rozbor transakčních hranic je v kapitole
[Základní koncepty DDD](/zakladni-koncepty); anti-vzory typu *God Aggregate*
v kapitole [Anti-vzory a typické chyby](/anti-vzory).

## 25.08 Ponaučení {#lessons}

Implementace systému pro správu projektů pomocí Domain-Driven Design a CQRS v Symfony 8 přinesla deset
ponaučení, která lze přenést do dalších projektů. Prvních sedm vychází ze strategického a taktického designu,
zbylá tři z provozu read modelů a vědomého řízení trade-offů.

1. **Strategický design rozhoduje o výsledku** – Identifikace bounded contexts a jejich vztahů na začátku projektu poskytla jasný rámec pro vývoj. Definice context map pomohla předejít nedorozuměním a zajistila konzistentní integraci mezi kontexty.
2. **Ubiquitous Language zpřesní model** – Společný jazyk s doménovými experty odstranil nejednoznačnosti v požadavcích a zrcadlil se přímo v názvech tříd a metod. Díky tomu kód dává smysl i bez komentářů.
3. **Agregáty a hranice transakcí** – Správně vymezené agregáty a jejich hranice udržely data konzistentní. Každý agregát si hlídal vnitřní konzistenci a měnil se v jedné transakci.
4. **Doménové události pro integraci** – Doménové události zajistily komunikaci mezi různými bounded contexts. Po vytvoření úkolu se publikovala událost `TaskCreated`, na kterou reagovaly jiné kontexty bez přímých závislostí.
5. **CQRS pro oddělení zodpovědností** – Příkazy mění stav, dotazy čtou bez vedlejších efektů. Každá strana má vlastní handler, vlastní model a vlastní testy. Symfony Messenger poskytl infrastrukturu pro implementaci CQRS.
6. **Vertikální slice architektura pro modularitu** – Organizace kódu podle feature místo technických vrstev znamenala, že změna v jedné feature se nedotýká ostatních. Každá feature nese vlastní command, handler, kontroler i view model – přidání nové feature nevyžaduje zásahy do existujícího kódu.
7. **Testování doménového modelu** – Doménové objekty bez závislostí na frameworku lze testovat čistým PHPUnit bez bootstrappingu kernelu.
   Unit testy ověřovaly chování agregátů a doménových služeb, integrační testy spolupráci mezi částmi systému.
   Podrobná strategie pro DDD projekty je v kapitole
   [Testování DDD aplikací](/testovani-ddd).
8. **Read modely jako samostatný artefakt** – Oddělení write a read strany přes projekce ukázalo svou hodnotu, jakmile dataset překročil několik tisíc projektů. Hydratace agregátů pro účely výpisu je drahá; denormalizovaný read model umožnil držet odezvu výpisu pod 50 ms i při tisícovkách projektů na uživatele. Cenou byla eventual consistency, kterou tým ošetřil optimistickou aktualizací UI v kombinaci s read-your-writes pro kritické flowy.
9. **Doménová analýza předchází kódu** – Tři kroky event stormingu (sběr událostí, seskupení do subdomén, definice hranic) zafungovaly jako filtr proti předčasné technické dekompozici. Bez tohoto kroku by hranice kontextů kopírovaly databázové tabulky nebo screen flow, ne sémantické bloky domény. Workshop trval dva dny; následný refaktor by trval řády déle.
10. **Trade-offy dokumentovat, ne řešit** – Ne každé rozhodnutí má jednu správnou odpověď. Sdílený kernel pro identifikátory, eventual consistency u auditu, synchronní ACL přes port – každá z těchto voleb má cenu, kterou tým přijal s vědomím alternativy. Záznam těchto rozhodnutí v dokumentaci (ADR) zachoval kontext pro pozdější refaktor; bez něj by se za půl roku diskuse opakovala znovu.

:::faq{}
- question: Jakou doménu případová studie popisuje?
  answer: 'Systém pro správu projektů a úkolů – uživatelé vytvářejí projekty, přidávají úkoly, přiřazují je členům týmu, mění jejich stav a komentují je. Doména je dostatečně bohatá, aby obsáhla strategické (context map) i taktické (agregát, doménová služba) vzory DDD, a přitom uchopitelná v rozsahu jedné kapitoly. Konkrétní požadavky v <a href="#requirements">sekci Požadavky</a>.'
- question: Proč je systém rozdělen do pěti bounded contexts místo jednoho modelu?
  answer: 'Každý kontext má jinou sémantiku: UserManagement řeší identitu, ProjectManagement životní cyklus projektu, TaskManagement stavové přechody úkolů, CommentManagement komunikaci a ActivityTracking audit. Rozdělení odráží reálné doménové hranice a umožňuje vyvíjet každý kontext samostatně, s vlastním jazykem a vlastními invarianty. Sdílení jediného modelu by vedlo ke god aggregate a ke kompromisům napříč sémanticky odlišnými oblastmi. Rozbor v <a href="#architecture">sekci Architektura</a>.'
- question: Jak spolu bounded contexty komunikují?
  answer: 'Primárním prostředkem integrace jsou doménové události: po dokončení operace agregát publikuje událost (např. <code>TaskCreated</code>), na kterou reagují jiné kontexty asynchronně přes Messenger. Synchronní dotazy mezi kontexty se řeší přes porty (rozhraní) s implementací v infrastruktuře cílového kontextu – volající kontext nezávisí na detailech implementace. Konkrétní ukázka v <a href="#implementation">sekci Implementace</a>.'
- question: Jaký přínos měla vertikální slice architektura?
  answer: 'Každá feature (CreateProject, AssignTask, AddComment) vznikla jako samostatný balíček s vlastním commandem, handlerem, kontrolerem a view modelem. Změny ve feature nezasahovaly do ostatních slicí, což zkrátilo cyklus vývoj–test–nasazení a usnadnilo onboarding. Problém tradičního horizontálního členění – šíření změn napříč vrstvami – se v projektu prakticky nevyskytoval. Detailní srovnání v kapitole <a href="/vertikalni-slice">Vertikální slice architektura</a>.'
- question: Proč má smysl oddělit read model od doménového modelu?
  answer: 'Doménový model existuje pro vynucování invariantů a reprezentaci doménových pravidel; výpis projektů žádné invarianty nepotřebuje. Hydratace agregátu jen kvůli zobrazení názvu a počtu členů je drahá – při růstu datasetu rozhoduje rozdíl mezi 5 ms a 200 ms odezvy. Denormalizovaný read model aktualizovaný přes projekce umožní oddělit tempo zápisu a čtení a optimalizovat každou stranu zvlášť. Cenou je eventual consistency. Konkrétní implementace v <a href="#read-model">sekci Read modely a projekce</a>.'
- question: Jaká jsou tři nejdůležitější ponaučení z projektu?
  answer: 'Zaprvé, investice do strategického designu a kontextové mapy na začátku projektu se mnohonásobně vyplatila – pozdější změny architektury by byly dražší. Zadruhé, důsledné budování ubiquitous language s doménovými experty zabránilo většině nedorozumění v komunikaci. Zatřetí, malé agregáty s jasnou transakční hranicí udržely model konzistentní bez potřeby distribuovaných transakcí. Úplný seznam včetně ponaučení o read modelech a vědomých trade-offech v <a href="#lessons">sekci Ponaučení</a>.'
- question: Co bylo nejtěžším rozhodnutím projektu?
  answer: 'Volba mezi synchronním ověřením členství v projektu (přes port <code>ProjectChecker</code>) a asynchronní reakcí přes lokální projekci. Synchronní cesta v monolitu znamená méně pohyblivých částí, ale vytváří časovou závislost mezi kontexty. Tým zvolil synchronní variantu jako pragmatický kompromis pro fázi monolitu, s vědomím, že při budoucím štěpení do služeb přijde refaktor na lokální projekci. Plný kontext rozhodnutí včetně dalších čtyř trade-offů v <a href="#trade-offs">sekci Výzvy a rozhodnutí</a>.'
:::
