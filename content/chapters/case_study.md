---
route: case_study
path: /pripadova-studie
title: Případová studie
page_title: "Případová studie: Implementace DDD v Symfony | DDD Symfony"
meta_description: "Systém pro správu projektů v DDD krok za krokem: bounded contexts, agregáty, CQRS, projekce s reconciliation a event-driven workflow v Symfony 8."
meta_keywords: "případová studie DDD, Symfony projekt, bounded contexts, strategický design, taktický design, agregáty, doménové události, CQRS, kompletní implementace, analýza domény, návrh, vývoj, testování, reálný projekt, DDD v praxi"
og_type: article
published: "2025-04-24"
modified: "2026-09-05"
breadcrumb_name: Případová studie
schema_type: TechArticle
schema_headline: "Případová studie: Implementace DDD v Symfony"
chapter_number: "24"
category: Syntéza
deck: 'Detailní případová studie implementace Domain-Driven Design v Symfony 8 na kompletním projektu – celý proces od analýzy domény, identifikace bounded contexts a strategického i taktického designu až po implementaci s využitím DDD principů a CQRS.'
reading_time: 55
difficulty: 4
github_examples: null
---

## 24.01 Úvod {#introduction}

Ilustrativní scénář: tým, čísla i rozhodnutí v této kapitole jsou smyšlené. Slouží jako souvislá ukázka, jak DDD a CQRS drží pohromadě napříč jedním projektem.

Tým dostal zadání postavit systém pro správu projektů. Uživatelé zakládají projekty, přidávají úkoly, přiřazují
je členům týmu, mění jejich stav a komentují je. Triviální zadání. První instinkt vývojáře je tabulka `projects`,
tabulka `tasks` s cizím klíčem, tabulka `comments` a `TaskService`, který vše obslouží. Za tři měsíce má `TaskService`
osm set řádků a každá změna v přiřazování úkolů rozbije reportování. Tato studie ukazuje druhou cestu –
strategický a taktický DDD s CQRS v Symfony 8 od prvního workshopu po projekce s reconciliation.

## 24.02 Požadavky {#requirements}

Systém pro správu projektů má následující požadavky:

- Uživatelé se mohou registrovat a přihlašovat.
- Uživatelé mohou vytvářet projekty.
- Uživatelé mohou přidávat úkoly do projektů.
- Uživatelé mohou přiřazovat úkoly členům týmu.
- Uživatelé mohou měnit stav úkolů (To Do, In Progress, Done).
- Uživatelé mohou přidávat komentáře k úkolům.
- Uživatelé mohou sledovat aktivitu na projektech a úkolech.
- Systém musí být škálovatelný a udržitelný.

## 24.03 Doménová analýza {#discovery}

Architektura začíná u rozhovoru s doménovými experty, ne u kódu. Než přijde rozhodnutí o tabulkách
a třídách, musí tým vědět, co se v doméně děje a kde leží hranice. Pět bounded contexts z následující
[sekce Architektura](#architecture) nevypadlo z hlavy architekta – vyplynulo ze tří kroků
*event stormingu*: sběru doménových událostí, jejich seskupení do subdomén a vykreslení
kontextových hranic. Formát pochází od Alberta Brandoliniho; notaci, průběh workshopu i jeho
anti-vzory rozebírá kapitola [Event Storming](/event-storming).

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

Tým druhý den shlukoval události podle významu. Otázka pro každou skupinu zněla: kdo z byznysu za toto odpovídá?
Skupina, které rozumí jediný expert, je kandidát na subdoménu. Výsledkem byla mapa událostí na subdomény:

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

### Klasifikace subdomén {#discovery-subdomain-types-heading}

Pět subdomén neznamená pět stejně důležitých subdomén. Před převodem na kontexty zařadil tým každou
z nich do jedné ze tří kategorií podle kapitoly [Subdomény](/subdomeny#tri-kategorie). Zařazení
rozhoduje o tom, kolik modelování si která část zaslouží.

| Subdoména | Kategorie | Důsledek pro návrh |
|---|---|---|
| **ProjectManagement** | Core | vlastní model, bohaté invarianty, nejvíc času na workshopu |
| **TaskManagement** | Core | stavový automat úkolu je to, čím se produkt liší |
| **CommentManagement** | Supporting | malý model, žádná investice do taktických vzorů navíc |
| **ActivityTracking** | Supporting | append-only log bez invariantů |
| **UserManagement** | Generic | registrace, přihlášení, reset hesla – vyřešený problém |

Zařazení **UserManagement** mezi Generic jde proti prvnímu instinktu postavit vlastní autentizaci.
Kolik taková volba stojí, ukazuje [Subdomény](/subdomeny#custom-auth-warning-heading). Kontext ve
studii zůstává, protože nese hranici a vztah v kontextové mapě, jeho model je ale tenký: identita,
e-mail a delegace na Symfony Security, respektive na externího poskytovatele identity. Doménová
práce se soustředí do dvou Core kontextů.

Bez tohoto kroku dostane každý kontext stejnou investici do modelu. Přesně tomu se říká anti-vzor
[„všechno je Core“](/subdomeny#vsechno-core-antipattern).

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

Převod dopadl 1:1 – z každé subdomény vznikl právě jeden kontext. Pravidlo to není: Core subdoména
se běžně rozpadá do několika kontextů a několik Supporting subdomén se naopak vejde do jednoho
([Subdomény](/subdomeny#subdomeny-na-bc)).

V tomto projektu zafungovala všechna tři kritéria společně. Kompletní mapa vztahů mezi kontexty
(Partnership, Customer-Supplier, Open Host Service) je v [sekci Architektura](#architecture).
Hlubší teoretický základ pro identifikaci kontextů poskytují kapitoly
[Co je Domain-Driven Design](/co-je-ddd) a
[Základní koncepty DDD](/zakladni-koncepty).

:::callout{type="note"}
Event storming není jednorázový workshop. Po prvním nasazení se ukazují události, se kterými tým nepočítal
(`InvitationExpired`, `TaskBlocked`) i události, které se v praxi nepoužívají.
Doménový model je *živý dokument* – při každém větším incrementu se vyplatí ověřit, že slovník
v kódu odpovídá slovníku v týmu.
:::

## 24.04 Architektura {#architecture}

Strategická úroveň drží pět bounded contexts a kontextovou mapu jejich vztahů; typy vztahů, které
mapa používá, zavádí kapitola [Bounded Context a Context Mapping](/context-mapping). Na taktické úrovni žijí
agregáty, hodnotové objekty, doménové události a doménové služby. Kód je organizovaný do vertikálních sliců:
každá feature obsahuje vše od příkazu po view model. Změna v přiřazování úkolů se neprojeví v reportování,
protože obě věci žijí v různých slicích a komunikují přes explicitní kontrakty.

### Strategický design: Bounded Contexts a Context Map

Identifikace bounded contexts vychází z doménové analýzy v [sekci 24.03](#discovery).
Systém je rozdělen do následujících kontextů:

- **UserManagement** – identita, registrace, autentizace; vlastník přístupových práv uživatelů.
- **ProjectManagement** – životní cyklus projektů a členství uživatelů v projektu.
- **TaskManagement** – úkoly, jejich přiřazování a stavové přechody.
- **CommentManagement** – komentáře a zpětná vazba k úkolům.
- **ActivityTracking** – auditní stopa nad událostmi z ostatních kontextů.

:::diagram{fig="24.4-A" title="Kontextová mapa: vztahy mezi pěti bounded contexts" src="images/diagrams/15_case_study/context_map.svg"}
:::

Vztahy zachycené v kontextové mapě:

- **UserManagement ⟷ ProjectManagement** – *Partnership*. Oba kontexty
  ovlivňují společný model členství v projektu. Změna kontraktu vyžaduje koordinaci obou týmů.
- **ProjectManagement → TaskManagement** – *Customer / Supplier*.
  ProjectManagement určuje, jaký kontrakt o existenci a členství projektu TaskManagement potřebuje;
  TaskManagement se přizpůsobuje upstreamu.
- **TaskManagement → CommentManagement** – *Customer / Supplier*. Komentář drží `TaskId`
  a bez úkolu ztrácí smysl, takže kontrakt určuje TaskManagement. CommentManagement je downstream
  a přizpůsobuje se.
- **Všechny kontexty → ActivityTracking** – doménové události na sdílené sběrnici.
  Diagram tento vztah popisuje jako *Open Host Service / Published Language*, což sedí jen zčásti:
  publikované události nesou `ProjectId`, `UserId` a `TaskStatus`, tedy interní typy vydávajícího
  kontextu. Published Language je proti tomu samostatný výměnný formát, díky kterému konzument
  závisí na *schématu* události, ne na třídách publishera. Dokud tenká integrační událost
  s primitivy nevznikne, jde o publikaci interního modelu ven
  ([Open Host Service](/context-mapping#ohs),
  [Published Language](/context-mapping#published-language)).
- **Sdílené identifikátory** – `UserId`, `ProjectId` a `TaskId` tvoří minimální
  [Shared Kernel](/context-mapping#shared-kernel). V diagramu stojí ve vlastním balíčku, v kódu žijí
  ve vlastnickém kontextu a ostatní je importují. Evansova podmínka vzoru je závazek koordinovat
  každou změnu; zde ho drží jediná okolnost – tým je jeden. Cena a alternativa jsou
  v [sekci 24.07.2](#trade-off-shared-kernel-heading).

**Hranici mezi TaskManagement a ProjectManagement drží port, ne Anti-Corruption Layer.** Oba
kontexty pracují s týmiž třídami `ProjectId` a `UserId` importovanými z vlastnických kontextů,
takže se nic nepřekládá. Zbývá obrácení závislosti: port `ProjectChecker` je definovaný v doméně
TaskManagement a jeho infrastrukturní implementace je adaptér do ProjectManagement.
[Anti-Corruption Layer](/context-mapping#acl) v Evansově smyslu z něj bude ve chvíli, kdy do
adaptéru přibude překlad mezi dvěma modely – například až ProjectManagement odejde do vlastní
služby s vlastním tvarem odpovědi. Popisek „ACL“ v diagramu tedy pojmenovává cílový stav, ne
dnešní. Synchronní vs. asynchronní volba je popsaná
v [sekci 24.07.3](#trade-off-sync-acl-heading).

Pro asynchronní integraci mezi kontexty slouží doménové události publikované přes Symfony Messenger.
Konkrétní ukázka projekce, která naslouchá událostem ze tří kontextů, je v
[sekci 24.06](#read-model).

### Taktický design a struktura projektu

Implementace na taktické úrovni stojí na těchto vzorech. Základ tvoří entity – objekty s identitou, které se v čase mění (User, Project, Task) – a hodnotové objekty, neměnné nositele konceptů domény bez vlastní identity (UserId, ProjectId, TaskStatus). Nad nimi stojí čtyři další stavební kameny. Agregát drží skupinu objektů, kterou doména mění jako jednu jednotku; zde jím je `Project` a samostatně `Task`. Doménová událost zaznamenává, co se stalo a co má význam pro doménové experty (`ProjectCreated`, `TaskAssigned`). Repozitář zapouzdřuje persistenci agregátu, takže doménový kód o databázi neví. A doménová služba nese pravidlo, které nepatří žádné entitě ani hodnotovému objektu – v této studii `TaskAssignmentService`, jehož existenci rozebírá [sekce 24.07.4](#trade-off-domain-service-heading).

Struktura adresářů odráží oba designy zároveň. Každý bounded context má vlastní doménovou vrstvu, infrastrukturu i feature slice; sdílené komponenty žijí v `SharedKernel/`:

:::code{language="bash" filename="snippet.sh"}
src/
├── UserManagement/            # Bounded Context: Správa uživatelů
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   └── User.php        # Entita uživatele (Aggregate Root)
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── UserId.php
│   │   │   └── Email.php
│   │   ├── Event/             # Doménové události
│   │   │   └── UserRegistered.php
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── UserRepository.php
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineUserRepository.php
│   ├── Registration/          # Feature: Registrace uživatele
│   │   ├── Command/           # Příkazy
│   │   │   ├── RegisterUser.php
│   │   │   └── RegisterUserHandler.php
│   │   └── Controller/        # Kontrolery
│   │       └── RegistrationController.php
│   ├── Authentication/        # Feature: Autentizace
│   │   └── Controller/        # Kontrolery
│   │       └── SecurityController.php
│   └── GetUser/               # Feature: Získání uživatele
│       ├── Query/             # Dotazy
│       │   ├── GetUser.php
│       │   └── GetUserHandler.php
│       └── ViewModel/         # View modely
│           └── UserViewModel.php
├── ProjectManagement/         # Bounded Context: Správa projektů
│   ├── Domain/
│   │   ├── Model/
│   │   │   ├── Project.php     # Entita projektu (Aggregate Root)
│   │   │   └── ProjectMember.php
│   │   ├── ValueObject/
│   │   │   ├── ProjectId.php
│   │   │   └── ProjectStatus.php
│   │   ├── Event/
│   │   │   ├── ProjectCreated.php
│   │   │   └── MemberAdded.php
│   │   ├── Exception/         # Pojmenované doménové výjimky
│   │   │   └── ProjectOwnerCannotBeRemovedException.php
│   │   └── Repository/
│   │       └── ProjectRepository.php
│   ├── Infrastructure/
│   │   └── Repository/
│   │       └── DoctrineProjectRepository.php
│   ├── CreateProject/         # Feature: Vytvoření projektu
│   │   ├── Command/
│   │   │   ├── CreateProject.php
│   │   │   └── CreateProjectHandler.php
│   │   └── Controller/
│   │       └── ProjectController.php
│   └── GetProjects/           # Feature: Seznam projektů
│       ├── Query/
│       │   ├── GetProjects.php
│       │   └── GetProjectsHandler.php
│       ├── Controller/
│       │   └── ProjectsController.php
│       └── ViewModel/
│           └── ProjectViewModel.php
├── TaskManagement/            # Bounded Context: Správa úkolů
│   ├── Domain/
│   │   ├── Model/
│   │   │   └── Task.php        # Entita úkolu (Aggregate Root)
│   │   ├── ValueObject/
│   │   │   ├── TaskId.php
│   │   │   └── TaskStatus.php
│   │   ├── Event/
│   │   │   ├── TaskCreated.php
│   │   │   ├── TaskAssigned.php
│   │   │   └── TaskStatusChanged.php
│   │   ├── Service/           # Doménové služby
│   │   │   └── TaskAssignmentService.php
│   │   ├── Port/              # Porty do jiných kontextů
│   │   │   └── ProjectChecker.php
│   │   ├── Exception/
│   │   │   ├── InvalidTaskStateTransitionException.php
│   │   │   └── TaskNotFoundException.php
│   │   └── Repository/
│   │       └── TaskRepository.php
│   ├── Infrastructure/
│   │   └── Repository/
│   │       └── DoctrineTaskRepository.php
│   ├── CreateTask/            # Feature: Vytvoření úkolu
│   │   ├── Command/
│   │   │   ├── CreateTask.php
│   │   │   └── CreateTaskHandler.php
│   │   └── Controller/
│   │       └── TaskController.php
│   ├── AssignTask/            # Feature: Přiřazení úkolu
│   │   ├── Command/
│   │   │   ├── AssignTask.php
│   │   │   └── AssignTaskHandler.php
│   │   └── Controller/
│   │       └── AssignController.php
│   ├── ChangeStatus/          # Feature: Změna stavu úkolu
│   │   ├── Command/
│   │   │   ├── ChangeTaskStatus.php
│   │   │   └── ChangeTaskStatusHandler.php
│   │   └── Controller/
│   │       └── StatusController.php
│   └── GetTask/               # Feature: Získání úkolu
│       ├── Query/
│       │   ├── GetTask.php
│       │   └── GetTaskHandler.php
│       └── ViewModel/
│           └── TaskViewModel.php
├── CommentManagement/         # Bounded Context: Správa komentářů
│   ├── Domain/
│   │   ├── Model/
│   │   │   └── Comment.php
│   │   ├── ValueObject/
│   │   │   └── CommentId.php
│   │   ├── Event/
│   │   │   └── CommentAdded.php
│   │   └── Repository/
│   │       └── CommentRepository.php
│   ├── Infrastructure/
│   │   └── Repository/
│   │       └── DoctrineCommentRepository.php
│   └── AddComment/            # Feature: Přidání komentáře
│       ├── Command/
│       │   ├── AddComment.php
│       │   └── AddCommentHandler.php
│       └── Controller/
│           └── CommentController.php
├── ActivityTracking/          # Bounded Context: Sledování aktivity
│   ├── Domain/
│   │   ├── Model/
│   │   │   └── Activity.php
│   │   ├── ValueObject/
│   │   │   └── ActivityId.php
│   │   └── Repository/
│   │       └── ActivityRepository.php
│   ├── Infrastructure/
│   │   └── Repository/
│   │       └── DoctrineActivityRepository.php
│   └── RecordActivity/        # Feature: Zaznamenání aktivity
│       ├── Command/
│       │   ├── RecordActivity.php
│       │   └── RecordActivityHandler.php
│       └── Controller/
│           └── ActivityController.php
└── SharedKernel/              # Sdílené komponenty
    ├── Domain/                # Sdílená doménová logika
    │   ├── AggregateRoot.php  # Bázová třída agregátu (record/releaseEvents)
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

## 24.05 Implementace {#implementation}

Sekce prochází jádro systému – od slovníku přes agregáty a doménové události až po command a query stranu CQRS.

### Ubiquitous Language {#ubiquitous-language-heading}

Slovník vznikl s doménovými experty ještě před prvním řádkem kódu. Tytéž pojmy najdete ve třídách, v rozhovoru
s produktovým manažerem i v ticketech. Hlavní pojmy:

- **Project** – Organizační jednotka, která sdružuje související úkoly a členy týmu.
- **Task** – Jednotka práce, která má být dokončena v projektu.
- **Assignee** – Člen týmu, kterému je přiřazen úkol.
- **Status** – Stav úkolu (To Do, In Progress, Done).
- **Comment** – Textová zpětná vazba k úkolu.
- **Activity** – Záznam o akci provedené v systému.

### Doménový model: Projekt (kořen agregátu) {#project-model-heading}

Agregát používá Doctrine atributy přímo na doménové třídě – jako pragmatickou výchozí volbu,
v souladu s [kapitolou 10](/implementace-v-symfony#mapping-volba-heading). Třída dědí
z `AggregateRoot` (sdílené chování pro `record` a `releaseEvents`, viz
[lifecycle agregátu](/zakladni-koncepty#aggregate-root-lifecycle)) a je `final`,
protože dědit z agregátu nechceme. Konstruktor je `private`
a vznik agregátu probíhá přes statickou factory metodu `create()`.

`final` třída s `public readonly` vlastnostmi má na Doctrine entitě jednu podmínku: nativní lazy
objekty. Zapíná je `$config->enableNativeLazyObjects(true)` na PHP 8.4; metoda přibyla v ORM 3.4.0
a od 3.5 je starý režim generovaných proxy vedený jako zastaralý. Bez nich potřebuje Doctrine proxy
odvozenou z entity a `final` ani `readonly` neprojdou.

:::code{language="php" filename="src/ProjectManagement/Domain/Model/Project.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Model;

use App\ProjectManagement\Domain\Event\MemberAdded;
use App\ProjectManagement\Domain\Event\MemberRemoved;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\ProjectManagement\Domain\Exception\ProjectOwnerCannotBeRemovedException;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\SharedKernel\Domain\AggregateRoot;
// UserId žije v UserManagement; ostatní kontexty ho importují (viz sekci 24.07.2)
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
    }

    public static function create(ProjectId $id, string $name, ?string $description, UserId $ownerId): self
    {
        $project = new self($id, $name, $description, $ownerId);
        $project->record(new ProjectCreated($id, $name, $ownerId));

        return $project;
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
            throw ProjectOwnerCannotBeRemovedException::forProject($this->id);
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

Výjimky nesou jméno pravidla, které se porušilo. `ProjectOwnerCannotBeRemovedException` dědí
z `\DomainException` a nabízí statickou factory metodu; tvar ukazuje
[kapitola 10](/implementace-v-symfony#custom-exception-heading).

Sloupec `version` s `#[ORM\Version]` zapíná optimistické zamykání. Ukázkové handlery ale verzi
nikam nepředávají, takže konflikt odhalí až Doctrine při `flush()`. Kontrola očekávané verze
v handleru (`find($id, LockMode::OPTIMISTIC, $expectedVersion)`) je krok, který ve studii chybí.

`rename()` a `changeDescription()` žádnou událost neemitují. Projekce ze
[sekce 24.06](#read-model) se o změně nedozví a read model zůstává zastaralý až do běhu
reconcileru – drift jména patří k rozdílům, které reconciler dorovnává právě proto.

:::callout{type="note"}
`UserId` žije ve vlastnickém kontextu UserManagement; ostatní kontexty
třídu importují. Cena této volby a alternativa (samostatný primitiv v každém kontextu)
jsou rozebrány v [sekci 24.07.2](#trade-off-shared-kernel-heading). V kontextech, kde
by se model musel rozejít (jiná validace, jiná sériová reprezentace), by sdílená
třída nestačila a kontext by si držel vlastní kopii.
:::

### Doménový model: Úkol (kořen agregátu) {#task-model-heading}

:::code{language="php" filename="src/TaskManagement/Domain/Model/Task.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Event\TaskCreated;
use App\TaskManagement\Domain\Event\TaskAssigned;
use App\TaskManagement\Domain\Event\TaskStatusChanged;
use App\TaskManagement\Domain\Exception\InvalidTaskStateTransitionException;
use App\TaskManagement\Domain\ValueObject\TaskId;
use App\TaskManagement\Domain\ValueObject\TaskStatus;
use App\SharedKernel\Domain\AggregateRoot;
// ProjectId a UserId se importují z vlastnických kontextů (viz sekci 24.07.2)
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\UserManagement\Domain\ValueObject\UserId;

final class Task extends AggregateRoot
{
    private readonly TaskId $id;
    private string $title;
    private ?string $description;
    private readonly ProjectId $projectId;
    private ?UserId $assigneeId = null;
    private TaskStatus $status;
    private readonly \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    private function __construct(TaskId $id, string $title, ?string $description, ProjectId $projectId)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->projectId = $projectId;
        $this->status = TaskStatus::Todo;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(TaskId $id, string $title, ?string $description, ProjectId $projectId): self
    {
        $task = new self($id, $title, $description, $projectId);
        $task->record(new TaskCreated($id, $title, $projectId));

        return $task;
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

        $this->record(new TaskAssigned($this->id, $assigneeId));
    }

    public function unassign(): void
    {
        $this->assigneeId = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeStatus(TaskStatus $status): void
    {
        if (!$this->status->canTransitionTo($status)) {
            throw InvalidTaskStateTransitionException::cannotTransition(
                $this->status->value,
                $status->value,
            );
        }

        $oldStatus = $this->status;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        $this->record(new TaskStatusChanged($this->id, $oldStatus, $status));
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
}
:::

### Doménové události {#domain-events-heading}

Agregáty publikují skutečnosti, které pro doménu mají význam. Událost je neměnný záznam minulého
děje – proto jsou všechny třídy `final readonly` s veřejnými promovanými parametry. Vlastnost
`occurredAt` nese okamžik vzniku – výchozí `new \DateTimeImmutable()` ovšem přebírá časovou zónu
serveru, garance UTC vyžaduje explicitní předání hodnoty. Payload obsahuje minimální množinu identifikátorů
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

Identifikátory `ProjectId`, `TaskId` a `UserId` sdílí společné rozhraní: konstruktor předaný
string jen ověří, nové UUID vydává statická metoda `generate()`. Property `$value` nese surový
string pro persistenci, `equals()` srovnává podle hodnoty. `TaskStatus` je výčtový typ
s explicitním doménovým jazykem.
Plný rozbor Value Objektů je v kapitole
[Základní koncepty DDD](/zakladni-koncepty#value-objects).

:::code{language="php" filename="src/ProjectManagement/Domain/ValueObject/ProjectId.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class ProjectId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('Neplatné ProjectId: "%s".', $value),
            );
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    // Doctrine skládá klíč identity mapy přes implode() nad identifikátorem.
    // Bez __toString() padne už persist().
    public function __toString(): string
    {
        return $this->value;
    }
}
:::

:::code{language="php" filename="src/TaskManagement/Domain/ValueObject/TaskStatus.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\ValueObject;

enum TaskStatus: string
{
    case Todo       = 'todo';
    case InProgress = 'in_progress';
    case Done       = 'done';

    public function canTransitionTo(self $next): bool
    {
        return match ([$this, $next]) {
            [self::Todo,       self::InProgress] => true,
            [self::InProgress, self::Done]       => true,
            [self::InProgress, self::Todo]       => true,
            default                              => false,
        };
    }
}
:::

:::callout{type="note"}
`ProjectId::generate()` vydá nové UUID v7 – časově řazené, a proto vhodné jako primární klíč.
`new ProjectId($uuid)` hydratuje existující identifikátor z databáze nebo z příchozího příkazu.
Validace tak zůstává v konstruktoru, generování v pojmenované metodě. `TaskId` a `UserId`
následují stejnou konvenci. Diskuse o sdílení těchto VO mezi kontexty (sdílená třída vs. duplikace)
je v [sekci 24.07.2](#trade-off-shared-kernel-heading).
:::

Přechodová tabulka používá `match` nad polem dvou případů. Porovnání je striktní a u polí probíhá
prvek po prvku; případy výčtového typu jsou singletony, takže dvojice `[$this, $next]` sedne právě
na jeden řádek tabulky. Zápis je hutný, za cenu toho, že ho čtenář musí přečíst dvakrát.

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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class CreateProjectHandler
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly EntityManagerInterface $em,
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateProject $command): string
    {
        $project = Project::create(
            ProjectId::generate(),
            $command->name,
            $command->description,
            new UserId($command->ownerId),
        );

        $this->projectRepository->save($project); // persist agregátu
        $this->em->flush();                       // commit zápisu

        foreach ($project->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return $project->id->value;
    }
}
:::

Pořadí `save()` → `flush()` → `releaseEvents()` → `dispatch()` je záměrné. Publikovat před commitem
znamená oznámit změnu, kterou databáze ještě může odmítnout; publikovat po commitu zase znamená
o událost přijít, když proces spadne mezi oběma kroky. Obě varianty i cestu přes transakční tabulku
rozebírají [Základní koncepty DDD](/zakladni-koncepty#aggregate-root-lifecycle) a kapitola
[Outbox Pattern](/outbox-pattern).

Handler generuje `ProjectId` sám a vrací ho volajícímu. Návratová hodnota z command handleru drží
příkaz na synchronní sběrnici – jakmile by šel na asynchronní transport, muselo by ID vzniknout
u volajícího a putovat uvnitř příkazu ([CQRS](/cqrs)).

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

Port `ProjectChecker` je rozhraní v doméně TaskManagement. Implementace žije v infrastruktuře
a překládá dotaz na volání upstream kontextu:

:::code{language="php" filename="src/TaskManagement/Domain/Port/ProjectChecker.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Port;

use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\UserManagement\Domain\ValueObject\UserId;

interface ProjectChecker
{
    public function exists(ProjectId $projectId): bool;

    public function isMember(ProjectId $projectId, UserId $userId): bool;
}
:::

:::code{language="php" filename="src/TaskManagement/AssignTask/Command/AssignTaskHandler.php"}
<?php

declare(strict_types=1);

namespace App\TaskManagement\AssignTask\Command;

use App\TaskManagement\Domain\Exception\AssigneeNotProjectMemberException;
use App\TaskManagement\Domain\Exception\ProjectNotFoundException;
use App\TaskManagement\Domain\Exception\TaskNotFoundException;
use App\TaskManagement\Domain\Port\ProjectChecker;
use App\TaskManagement\Domain\Repository\TaskRepository;
use App\TaskManagement\Domain\Service\TaskAssignmentService;
use App\TaskManagement\Domain\ValueObject\TaskId;
use App\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class AssignTaskHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly ProjectChecker $projectChecker,
        private readonly TaskAssignmentService $taskAssignmentService,
        private readonly EntityManagerInterface $em,
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(AssignTask $command): void
    {
        $taskId = new TaskId($command->taskId);
        $task = $this->taskRepository->findById($taskId);

        if ($task === null) {
            throw TaskNotFoundException::withId($taskId->value);
        }

        $assigneeId = new UserId($command->assigneeId);

        // Ověření přes port - bez přímé závislosti na ProjectManagement
        if (!$this->projectChecker->exists($task->projectId())) {
            throw ProjectNotFoundException::withId($task->projectId()->value);
        }

        if (!$this->projectChecker->isMember($task->projectId(), $assigneeId)) {
            throw AssigneeNotProjectMemberException::forTask($taskId->value, $assigneeId->value);
        }

        // Doménová služba drží pravidlo přiřazení
        $this->taskAssignmentService->assignTask($task, $assigneeId);

        $this->taskRepository->save($task);
        $this->em->flush();

        foreach ($task->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
:::

Handler ověřuje doménové pravidlo: řešitel musí být členem projektu. Otázku, kdo smí úkol přiřadit,
neřeší – oprávnění patří do autorizační vrstvy nad handlerem, kterou rozebírá kapitola
[Autorizace v DDD](/autorizace-v-ddd).

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
                $project->id->value,
                $project->name(),
                $project->description(),
                $project->ownerId->value,
                count($project->memberIds()),
                0, // počet úkolů naivní verze nezná – Task je samostatný agregát (viz sekci 24.06)
                $project->createdAt
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

## 24.06 Read modely a projekce {#read-model}

`GetProjectsHandler` z [předchozí sekce](#implementation) načítá projekty přes doménový repozitář.
Hydratuje agregáty, i když potřebuje jen tabulkový výpis. Pro malý dataset to funguje. Jakmile dataset
naroste na tisíce projektů a desetitisíce úkolů a výpis se obohatí o jména členů a počty úkolů,
každý dotaz znamená opakované `JOIN`y a hydrataci agregátů kvůli zobrazení.

V projektu proto postupně vznikl samostatný read model. Princip: doménové události aktualizují
denormalizovanou tabulku, ze které čte *query handler*. Žádný `JOIN` mezi agregáty, žádná
hydratace doménových objektů. Hlubší teoretický základ je v kapitolách
[CQRS](/cqrs) a [Výkonnostní aspekty](/vykonnostni-aspekty).

### Schéma read modelu {#read-model-schema-heading}

Tabulka `project_list_view` drží tvar potřebný pro výpis projektů uživatele. Není normalizovaná –
obsahuje vypočítané hodnoty (`member_count`, `task_count`) a denormalizované pole
`member_ids` jako JSON. Tato tabulka není zdrojem pravdy; lze ji kdykoli znovu sestavit z primárních tabulek.
Dotaz operátorem `@>` i GIN index předpokládají PostgreSQL sloupec typu `jsonb`. `Types::JSON`
vytvoří sloupec `json`, nad kterým `@>` ani GIN index nefungují; od DBAL 4.3 na to existuje typ
`Types::JSONB`. Na DBAL 3.x zbývá option `['jsonb' => true]`, kterou tatáž verze 4.3 označila
za zastaralou.

Entita read modelu nenese `readOnly: true`. Příznak vypíná sledování změn, takže by z projekce
prošel jen `persist()` a každý `UPDATE` by tiše zmizel – přesně to, co projekce dělá nejčastěji.

:::code{language="php" filename="src/ProjectManagement/Infrastructure/ReadModel/ProjectListView.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ReadModel;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project_list_view')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_owner')]
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

    // PostgreSQL: jsonb, ne json - nad json operátor @> ani GIN index nefungují.
    // Types::JSONB vyžaduje DBAL 4.3+; na DBAL 3.x: Types::JSON s options ['jsonb' => true].
    #[ORM\Column(type: Types::JSONB)]
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

GIN index nad `member_ids` v mapování nenajdete. Atribut `#[ORM\Index]` sice zná parametr `flags`,
ale `PostgreSQLPlatform` ho nepřepisuje a do DDL se nedostane; vznikl by běžný B-tree index, který
dotaz s `@>` stejně nepoužije. Index proto zakládá ruční migrace:

:::code{language="sql" filename="migrations/Version20250424120000.php (výřez)"}
CREATE INDEX idx_members ON project_list_view USING gin (member_ids);
:::

### Projection: aktualizace read modelu z událostí {#read-model-projection-heading}

Projekce naslouchá doménovým událostem ze všech kontextů, které mají vliv na podobu výpisu projektů.
Běží jako asynchronní message handler – mimo originální transakci, takže ji nemůže shodit.
Každou událost obsluhuje samostatná metoda s atributem `#[AsMessageHandler]`; Messenger
routuje podle type-hintu parametru, obecný type-hint `object` proto použít nelze.

:::code{language="php" filename="src/ProjectManagement/Infrastructure/ReadModel/ProjectListProjection.php"}
<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ReadModel;

use App\ProjectManagement\Domain\Event\MemberAdded;
use App\ProjectManagement\Domain\Event\MemberRemoved;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\TaskManagement\Domain\Event\TaskCreated;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

class ProjectListProjection
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    #[AsMessageHandler]
    public function onProjectCreated(ProjectCreated $event): void
    {
        if ($this->em->find(ProjectListView::class, $event->projectId->value) !== null) {
            return; // událost už byla zpracovaná
        }

        $now = new \DateTimeImmutable();
        $view = new ProjectListView();
        $view->projectId = $event->projectId->value;
        $view->name = $event->name;
        $view->ownerId = $event->ownerId->value;
        $view->memberIds = [$event->ownerId->value];
        $view->memberCount = 1;
        $view->taskCount = 0;
        $view->createdAt = $now;
        $view->updatedAt = $now;
        $this->em->persist($view);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Souběh: mezi find() a flush() stihl řádek vložit jiný worker.
            // Výsledek je stejný, jaký jsme chtěli, takže zprávu potvrdíme.
            $this->em->clear();
        }
    }

    #[AsMessageHandler]
    public function onMemberAdded(MemberAdded $event): void
    {
        $view = $this->em->find(ProjectListView::class, $event->projectId->value);
        if ($view === null) {
            // Out-of-order delivery: MemberAdded přišlo dřív než ProjectCreated.
            // Reconciler (sekce 24.06.4) dohledá zaostalou view a obnoví ji
            // ze zdrojových agregátů.
            return;
        }
        $userId = $event->userId->value;
        if (!in_array($userId, $view->memberIds, strict: true)) {
            $view->memberIds[] = $userId;
            $view->memberCount++;
            $view->updatedAt = new \DateTimeImmutable();
            $this->em->flush();
        }
    }

    #[AsMessageHandler]
    public function onMemberRemoved(MemberRemoved $event): void
    {
        $view = $this->em->find(ProjectListView::class, $event->projectId->value);
        if ($view === null) {
            return;
        }
        $userId = $event->userId->value;
        $view->memberIds = array_values(array_filter(
            $view->memberIds,
            static fn(string $id): bool => $id !== $userId
        ));
        $view->memberCount = count($view->memberIds);
        $view->updatedAt = new \DateTimeImmutable();
        $this->em->flush();
    }

    #[AsMessageHandler]
    public function onTaskCreated(TaskCreated $event): void
    {
        $view = $this->em->find(ProjectListView::class, $event->projectId->value);
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

Naivní verze ze [sekce 24.05](#get-projects-handler-heading) hydratovala doménové agregáty
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

**Idempotence.** Opakované zpracování téže události nesmí změnit výsledek. V ukázce výše to
zajišťují tři detaily: `onProjectCreated` nejdřív hledá existující view a při druhém doručení
skončí bez zápisu – samotné `find()` ale nestačí, protože mezi ním a `flush()` může řádek
vložit jiný worker, takže se zároveň odchytává porušení primárního klíče; `onMemberAdded` nepřidá uživatele dvakrát díky kontrole
`in_array(..., strict: true)`; `onMemberRemoved` přepočítává `memberCount` z aktuální délky
pole, ne inkrementem.

Slabé místo zbývá u `onTaskCreated`. Inkrement `taskCount` znamená při opakovaném doručení
o jedničku navíc. Odsunout problém na retry strategii Messengeru (výchozí tři pokusy) a odtud
na failure transport není idempotence, jen odklizené selhání. Řešení má dvě podoby. Buď každá událost ponese vlastní `eventId` a projekce si zpracovaná ID zapamatuje –
dnešní třídy nesou jen `occurredAt`, takže by šlo o změnu payloadu. Nebo deduplikaci převezme
`DeduplicateMiddleware` se stampem `DeduplicateStamp`, které Messenger nabízí od verze 7.3. Plný
vzor idempotentního příjmu popisuje kapitola [Outbox Pattern](/outbox-pattern#inbox).

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
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'project-list:reconcile',
    description: 'Dorovná zaostalý read model project_list_view ze zdrojových agregátů.',
)]
final class ReconcileProjectListView
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $now = new \DateTimeImmutable();
        $repaired = 0;

        foreach ($this->projects->all() as $project) {
            $view = $this->em->find(ProjectListView::class, $project->id->value);
            $expectedMembers = array_map(
                static fn($id) => $id->value,
                $project->memberIds(),
            );

            if ($view === null) {
                // Chybějící view: založit a rovnou naplnit všechna pole.
                $view = new ProjectListView();
                $view->projectId   = $project->id->value;
                $view->ownerId     = $project->ownerId->value;
                $view->createdAt   = $project->createdAt;
                $view->name        = $project->name();
                $view->description = $project->description();
                $view->memberIds   = $expectedMembers;
                $view->memberCount = count($expectedMembers);
                $view->updatedAt   = $now;
                $this->em->persist($view);
                $repaired++;
                continue;
            }

            $needsRepair = $view->name !== $project->name()
                || $view->description !== $project->description()
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
        $io->writeln(sprintf('Dorovnáno %d projektů.', $repaired));

        return Command::SUCCESS;
    }
}
:::

Command používá invokable syntaxi, kterou Symfony doporučuje od verze 7.3. Dědění z třídy
`Command` zůstává podporované, jen je vedené jako starší tvar zápisu.

Reconciler nepřebírá roli projekce; jen dorovnává to, co projekce z technických důvodů
nedoručila. V provozu se vyplatí alert nad počtem dorovnaných záznamů: vysoké číslo signalizuje
systémový problém s transportem, ne drobné přeházení pořadí zpráv.

Co tato podoba nedorovnává: `task_count` (dopočítal by se z `TaskRepository`), `ownerId`
a `createdAt` u již existující view a sirotčí řádky po smazaných projektech. Neřeší ani objem –
`$this->projects->all()` hydratuje všechny agregáty najednou. Na tisících projektů patří do smyčky
dávkování po několika stovkách kusů, `EntityManager::clear()` po každé dávce a přepínače `--limit`
a `--dry-run`.

:::callout{type="note"}
Pokud projekt přejde na Event Sourcing, reconciler se zjednoduší: znovuvytvoří view čistě
replay-em událostí z event store. V tomto projektu reconciler čte přímo write model,
protože doménové události nejsou perzistentní (zveřejňují se transientně přes Messenger).
Detaily v kapitole [Event Sourcing](/event-sourcing).
:::

### Důsledky pro konzistenci {#read-model-consistency-heading}

Read model je *eventually consistent*. Mezi commitem zápisu a aktualizací projekce zůstává okno
(typicky milisekundy, při zatížení Messengeru sekundy), ve kterém vrácený seznam neobsahuje nově
vytvořený projekt. Toto okno se v projektu pokrylo dvěma cestami:

- **Optimistická aktualizace UI** – po úspěšné odpovědi na command klient přidá záznam
  do lokálního stavu a teprve po další navigaci načítá aktualizovaný read model. Uživatel okamžitě vidí
  výsledek své akce.
- **Read-your-writes přes write model** – pro kritické dotazy okamžitě po commandu (např.
  stránka *Detail nově vytvořeného projektu*) handler čte přímo z write modelu nebo z cache namapované
  na ID právě dokončené operace. Cena: ztráta výhod read modelu pro tento jeden tok.

:::callout{type="warn"}
Outbox pattern je předpokladem spolehlivé projekce. Bez něj může transakce zápisu agregátu projít, ale
publikace události na transport selhat – read model zůstane navždy nesynchronizovaný. Ukázky v této
kapitole outbox nemají: události jdou na Messenger přímo po commitu, takže popsané okno zůstává
otevřené. Vzor i jeho implementaci v Symfony rozebírá kapitola [Outbox Pattern](/outbox-pattern).
:::

## 24.07 Výzvy a rozhodnutí {#trade-offs}

Žádný projekt v DDD nezačíná hotový. Pět níže uvedených rozhodnutí ukazuje místa, kde tým váhal mezi
dvěma legitimními možnostmi. Místo „správné“ odpovědi existuje kontext, který volbu určil, a cena, kterou
za ni tým platí. Stejná otázka v jiném projektu by mohla dopadnout jinak. Závěrečná podsekce pak
shrnuje, co by dnes proběhlo jinak.

### 1. Eventual consistency napříč kontexty {#trade-off-consistency-heading}

**Otázka:** má být zápis aktivity v **ActivityTracking** součástí téže transakce
jako vydávající operace (např. zápis projektu), nebo asynchronní reakce na publikovanou událost?

**Volba:** asynchronní zpracování přes Messenger transport. Audit se nesmí stát kritickým bodem
selhání pro hlavní use case. Pokud je transport pro audit nedostupný, zápis projektu se přesto úspěšně
dokončí a aktivita se zaznamená, jakmile je transport zase dostupný. Záruka, že se událost neztratí
ani při výpadku mezi commitem a publikací, ovšem vyžaduje outbox tabulku – tu ukázky v této
kapitole nemají ([Outbox Pattern](/outbox-pattern)).

**Cena:** uživatel s rolí auditor vidí novou aktivitu se zpožděním. Pro audit log, kde čtenář
není stejný uživatel jako autor akce, je toto zpoždění přijatelné. Pro notifikace v reálném čase by tento
kompromis nestačil – tam pomůže synchronní integrace nebo websocket push z projekce.

### 2. Sdílené identifikátory jako Shared Kernel {#trade-off-shared-kernel-heading}

**Otázka:** `UserId` se objevuje ve všech kontextech (vlastník projektu, přiřazený
řešitel, autor komentáře). Bude jedna sdílená třída, kterou ostatní kontexty importují, nebo si každý kontext drží
vlastní reprezentaci jako primitivní string?

**Volba:** jedna třída ve vlastnickém kontextu, importovaná ostatními. `UserId` žije
v UserManagement, `ProjectId` v ProjectManagement, `TaskId` v TaskManagement; downstream
kontexty tyto value objecty používají přímo. Vzor má jméno:
[Shared Kernel](/context-mapping#shared-kernel) – malá společně vlastněná část modelu, kterou žádný
z kontextů nemůže změnit sám. Tým je jeden, deploy je jeden, riziko, že se UUID formát mezi kontexty
rozejde, je zanedbatelné. Sdílená třída navíc drží validaci na jednom místě.

**Cena:** závislost na doménové vrstvě cizího kontextu. Když vlastnický kontext rozšíří `UserId` o novou
validaci, dotkne se to všech ostatních. Refaktor takto sdílené třídy je v praxi koordinovaný release.

**Alternativa:** Pokud by se tým štěpil nebo se kontexty oddělovaly do samostatných služeb,
primitivní string by byl bezpečnější (každý kontext si validuje sám) za cenu duplikace. Pro monolit
s jedním deploy pipeline je sdílená třída pragmatičtější.

### 3. Synchronní ACL přes port vs. asynchronní reakce na event {#trade-off-sync-acl-heading}

**Otázka:** při přiřazení úkolu (`AssignTask`) musí **TaskManagement**
ověřit, že přiřazovaný uživatel je členem projektu. Synchronní volání portu `ProjectChecker`, nebo
čistě asynchronní reakce na `TaskAssignmentRequested` a kompenzace, pokud členství neplatí?

**Volba:** synchronní port. Operace musí selhat okamžitě, pokud uživatel není členem projektu.
Uživatel čeká na odpověď příkazu a chce hned vědět, zda přiřazení prošlo, nebo proč ne.

**Cena:** **TaskManagement** má časovou závislost na **ProjectManagement**.
Pokud druhý kontext není dostupný, přiřazení selže. V monolitu je tato závislost neviditelná, ve světě služeb
přidá síťový skok a riziko kaskádových selhání.

**Alternativa pro distribuovaný systém:** **TaskManagement** by si držel lokální
projekci „project members“ aktualizovanou přes eventy z **ProjectManagement**. Validace by běžela
nad lokální tabulkou, bez síťového volání. Pro monolit jde o předčasnou optimalizaci, ale jakmile by se kontexty
oddělily, je to první refaktor, který by měl proběhnout. Kdy takové oddělení dává smysl a co stojí,
rozebírá kapitola [DDD a microservices](/ddd-a-microservices). Pokud by validace selhala až po dokončení přiřazení,
stav vrací kompenzační scénář – vzor, který popisuje kapitola
[Sagas a Process Manager](/sagy-a-process-managery).

### 4. Doménová služba vs. logika v handleru {#trade-off-domain-service-heading}

**Otázka:** `TaskAssignmentService::assignTask()` aktuálně volá pouze
`Task::assign()`. Má smysl mít doménovou službu, která jen deleguje?

**Volba:** zachovat ji jako *místo pro rozšíření*. Přiřazení úkolu je doménový koncept, který
v budoucnu zřejmě poroste – notifikace přiřazenému, kontrola pracovní zátěže, validace deadline, integrace
s kalendářem. Vystavená abstrakce dovolí přidat tato pravidla, aniž by se musel měnit handler, controller
nebo samotný agregát.

**Cena:** aktuálně prázdná abstrakce, která může čtenáři kódu připadat nadbytečná. Kapitola 10
označuje doménovou službu, která jen obalí volání jediného agregátu, za
[anti-vzor](/implementace-v-symfony#anti-payment-service-heading): oslabuje agregát a vede
k anemickému modelu. Zdejší výjimka stojí a padá s tím, jestli pravidla kolem přiřazení opravdu
přibudou. Pokud nepřibudou, platí anti-vzor a služba má zmizet.

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
na invariant). Pravidla pro velikost agregátu a jeho transakční hranici rozebírá
[Návrh agregátů](/navrh-agregatu#aggregate-size), anti-vzory typu *God Aggregate* pak
[Anti-vzory a typické chyby](/anti-vzory).

### Co by dnes proběhlo jinak {#trade-off-retro-heading}

Předchozích pět voleb vyšlo. Tři další stojí za pojmenování právě proto, že nevyšly.

`rename()` a `changeDescription()` neemitují událost. Read model se o změně jména nedozví
a zůstane zastaralý až do běhu reconcileru. Oprava je událost `ProjectRenamed`, ne hodinový cron.

Události publikované do ActivityTrackingu nesou interní hodnotové objekty vydávajícího kontextu.
Dokud běží monolit, nikdo to nepocítí. První konzument mimo repozitář ale zmrazí doménový model
v podobě, ve které se zrovna nachází.

`TaskAssignmentService` je pořád prázdná. Rozšíření, kvůli kterému vznikla, za celou dobu nepřišlo.

Katalog podobných třecích ploch – od Doctrine přes ordering zpráv po jazykový drift – vede kapitola
[DDD v praxi: kde to bolí](/ddd-v-praxi-kde-to-boli).

## 24.08 Ponaučení {#lessons}

Z návrhu popsaného výše plyne deset bodů, které drží i mimo tuto studii. Většina vychází ze
strategického a taktického designu, zbytek z práce s read modely a z vědomého řízení kompromisů.

1. **Strategický design rozhoduje o výsledku** – Identifikace pěti bounded contexts a jejich vztahů na začátku projektu odhalila, že slovo „uživatel“ znamená v každém kontextu něco jiného. Bez kontextové mapy by se tato sémantická rozdílnost objevila až ve sporech nad pull requesty.
2. **Ubiquitous Language zpřesní model** – Společný jazyk s doménovými experty odstranil nejednoznačnosti v požadavcích a zrcadlil se přímo v názvech tříd a metod. Tester, vývojář i produktový manažer mluví o `TaskAssigned`, ne každý o něčem jiném.
3. **Agregáty a hranice transakcí** – Vymezené agregáty udržely data konzistentní. Každý agregát si hlídal vnitřní konzistenci a měnil se v jedné transakci.
4. **Doménové události pro integraci** – Doménové události odvázaly bounded contexts od vzájemných synchronních volání. Po vytvoření úkolu publikoval agregát událost `TaskCreated`; ActivityTracking i ProjectListProjection na ni reagovaly samostatně, aniž by o sobě věděly.
5. **CQRS pro oddělení zodpovědností** – Příkazy mění stav, dotazy čtou bez vedlejších efektů. Každá strana má vlastní handler, vlastní model a vlastní testy. Roli message busu obstaral Symfony Messenger.
6. **Vertikální slice architektura pro modularitu** – Organizace kódu podle feature místo technických vrstev znamenala, že změna v jedné feature se zpravidla nedotýká ostatních. Každá feature nese vlastní command, handler, kontroler i view model. Nová feature obvykle vznikne přidáním adresáře, ne úpravou existujících tříd.
7. **Testování doménového modelu** – Doménové objekty bez závislostí na frameworku lze testovat čistým PHPUnit bez bootstrappingu kernelu.
   Unit testy ověřovaly chování agregátů a doménových služeb, integrační testy spolupráci mezi částmi systému.
   Podrobná strategie pro DDD projekty je v kapitole
   [Testování DDD aplikací](/testovani-ddd).
8. **Read modely jako samostatný artefakt** – Oddělení write a read strany přes projekce ukázalo svou hodnotu, jakmile dataset překročil několik tisíc projektů. Hydratace agregátů pro účely výpisu je drahá; denormalizovaný read model ji z výpisu odstranil úplně a místo několika `JOIN`ů a stovek objektů zbyl jeden dotaz nad jednou tabulkou. Cenou byla eventual consistency, kterou tým ošetřil optimistickou aktualizací UI v kombinaci s read-your-writes pro kritické scénáře.
9. **Doménová analýza předchází kódu** – Tři kroky event stormingu (sběr událostí, seskupení do subdomén, definice hranic) zafungovaly jako filtr proti předčasné technické dekompozici. Bez tohoto kroku by hranice kontextů kopírovaly databázové tabulky nebo obrazovkový tok, ne sémantické bloky domény. Dva dny u tabule stojí zlomek toho, co později stojí posun špatně vedené hranice.
10. **Trade-offy dokumentovat, ne řešit** – Ne každé rozhodnutí má jednu správnou odpověď. Sdílené třídy identifikátorů napříč kontexty, eventual consistency u auditu, synchronní ACL přes port – každá z těchto voleb má cenu, kterou tým přijal s vědomím alternativy. Záznam těchto rozhodnutí v dokumentaci (ADR) zachoval kontext pro pozdější refaktor; bez něj by se za půl roku diskuse opakovala znovu.

## 24.09 Další četba {#further-reading}

- Eric Evans, *Domain-Driven Design* (Addison-Wesley, 2003) – jediná průběžná doména lodní přepravy napříč celou knihou; open-source implementace [citerus/dddsample-core](https://github.com/citerus/dddsample-core).
- Vaughn Vernon, *Implementing Domain-Driven Design* (Addison-Wesley, 2013) a ukázky [VaughnVernon/IDDD_Samples](https://github.com/VaughnVernon/IDDD_Samples). Kontext `iddd_agilepm` řeší prakticky totožnou doménu jako tato studie, identitu ale drží jako samostatný kontext, se kterým ostatní pracují přes překlad.
- Vlad Khononov, *Learning Domain-Driven Design* (O'Reilly, 2021) – help-desk SaaS jako průběžný příklad, včetně klasifikace subdomén.
- DDD Crew, [*DDD Starter Modelling Process*](https://github.com/ddd-crew/ddd-starter-modelling-process) – osm kroků od pochopení byznysu ke kódu. Tato kapitola prochází kroky Discover, Decompose, Strategize, Connect, Define a Code; Understand a Organise nechává stranou.
- [CodelyTV/php-ddd-example](https://github.com/CodelyTV/php-ddd-example) – spustitelná PHP reference se strukturou `src/<BoundedContext>/<Modul>/{Application,Domain,Infrastructure}` a vlastní bázovou třídou agregátu.
- Mathias Verraes, [*Patterns for Decoupling in Distributed Systems: Explicit Public Events*](https://verraes.net/2019/05/patterns-for-decoupling-distsys-explicit-public-events/) – proč je veřejná jen malá, vědomě označená podmnožina událostí.

:::faq{}
- question: Jakou doménu případová studie popisuje?
  answer: 'Systém pro správu projektů a úkolů – uživatelé vytvářejí projekty, přidávají úkoly, přiřazují je členům týmu, mění jejich stav a komentují je. Scénář je ilustrativní: tým, čísla i rozhodnutí jsou smyšlené a slouží jako souvislá ukázka návrhu. Doména je dostatečně bohatá, aby obsáhla strategické (context map) i taktické (agregát, doménová služba) vzory DDD, a přitom uchopitelná v rozsahu jedné kapitoly. Konkrétní požadavky v <a href="#requirements">sekci Požadavky</a>.'
- question: Proč je systém rozdělen do pěti bounded contexts místo jednoho modelu?
  answer: 'Každý kontext má jinou sémantiku: UserManagement řeší identitu, ProjectManagement životní cyklus projektu, TaskManagement stavové přechody úkolů, CommentManagement komunikaci a ActivityTracking audit. Rozdělení odráží reálné doménové hranice a umožňuje vyvíjet každý kontext samostatně, s vlastním jazykem a vlastními invarianty. Sdílení jediného modelu by vedlo ke god aggregate a ke kompromisům napříč sémanticky odlišnými oblastmi. Rozbor v <a href="#architecture">sekci Architektura</a>.'
- question: Jak spolu bounded contexty komunikují?
  answer: 'Primárním prostředkem integrace jsou doménové události: po dokončení operace agregát publikuje událost (např. <code>TaskCreated</code>), na kterou reagují jiné kontexty asynchronně přes Messenger. Synchronní dotazy mezi kontexty se řeší přes porty (rozhraní) s implementací v infrastruktuře cílového kontextu – volající kontext nezávisí na detailech implementace. Konkrétní ukázka v <a href="#implementation">sekci Implementace</a>.'
- question: Jaký přínos měla vertikální slice architektura?
  answer: 'Každá feature (CreateProject, AssignTask, AddComment) vznikla jako samostatný balíček s vlastním commandem, handlerem, kontrolerem a view modelem. Změna ve feature nezasahuje do ostatních slicí, což zkracuje cyklus vývoj–test–nasazení a usnadňuje onboarding. Šíření změn napříč vrstvami, typické pro horizontální členění, se v takovém uspořádání téměř nevyskytuje. Detailní srovnání v kapitole <a href="/architektonicke-styly#vertical-slice">Architektonické styly</a>.'
- question: Proč má smysl oddělit read model od doménového modelu?
  answer: 'Doménový model existuje pro vynucování invariantů a reprezentaci doménových pravidel; výpis projektů žádné invarianty nepotřebuje. Hydratace agregátu jen kvůli zobrazení názvu a počtu členů je drahá – při růstu datasetu rozhoduje, jestli výpis znamená jeden dotaz nad jednou tabulkou, nebo několik <code>JOIN</code>ů a stovky sestavených objektů. Denormalizovaný read model aktualizovaný přes projekce umožní oddělit tempo zápisu a čtení a optimalizovat každou stranu zvlášť. Cenou je eventual consistency. Konkrétní implementace v <a href="#read-model">sekci Read modely a projekce</a>.'
- question: Jaká jsou tři nejdůležitější ponaučení z projektu?
  answer: 'Zaprvé, kontextová mapa nakreslená před kódem oddělí významy, které jedno slovo nese v různých částech systému; bez ní se rozdíl objeví až ve sporech nad pull requesty. Zadruhé, ubiquitous language budovaný s doménovými experty drží stejné pojmy v kódu, v ticketu i v rozhovoru. Zatřetí, malé agregáty s jasnou transakční hranicí udrží model konzistentní bez distribuovaných transakcí. Úplný seznam včetně ponaučení o read modelech a vědomých trade-offech v <a href="#lessons">sekci Ponaučení</a>.'
- question: Co bylo nejtěžším rozhodnutím projektu?
  answer: 'Volba mezi synchronním ověřením členství v projektu (přes port <code>ProjectChecker</code>) a asynchronní reakcí přes lokální projekci. Synchronní cesta v monolitu znamená méně pohyblivých částí, ale vytváří časovou závislost mezi kontexty. Studie volí synchronní variantu jako pragmatický kompromis pro fázi monolitu, s vědomím, že při štěpení do služeb přijde refaktor na lokální projekci. Plný kontext rozhodnutí včetně dalších čtyř kompromisů v <a href="#trade-offs">sekci Výzvy a rozhodnutí</a>.'
:::
