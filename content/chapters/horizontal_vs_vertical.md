---
route: horizontal_vs_vertical
path: /vertikalni-slice
title: Vertikální slice architektura vs. Tradiční DDD
page_title: "Vertikální slice vs. tradiční DDD architektura | DDD Symfony"
meta_description: "Vertikální slice architektura vs. horizontální vrstvení v DDD: kdy slicovat podle feature, kdy podle vrstvy, a jak to vypadá v Symfony 8 projektu."
meta_keywords: "vertikální slice architektura, tradiční DDD, feature slices, vrstvená architektura, modularity, Symfony architektura, organizace kódu, Domain-Driven Design"
og_type: article
published: "2025-04-24"
modified: "2026-04-28"
breadcrumb_name: Vertikální slice
schema_type: TechArticle
schema_headline: "Vertikální slice architektura vs. Tradiční DDD: Srovnání přístupů"
chapter_number: "10"
category: Základy
deck: "Srovnání vertikální slice architektury a tradičního vrstveného přístupu k Domain-Driven Design – jejich výhody, nevýhody a vodítko pro volbu přístupu v Symfony projektu."
reading_time: 12
difficulty: 2
---

## 10.01 Tradiční přístup k DDD {#traditional}

S rostoucí složitostí projektu se struktura adresářů a modulů stává jedním z rozhodujících faktorů
udržitelnosti kódu. Špatně zvolená organizace vede k tomu, že související logika je rozptýlena
napříč celou aplikací a každá změna vyžaduje úpravy na mnoha místech. Tradiční přístup k DDD,
často označovaný jako „vrstvený" (layered), tento problém řeší rozdělením kódu do vrstev
podle technické odpovědnosti [[1]](https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together/).
Typické vrstvy v tradičním DDD jsou:

- **Prezentační vrstva (Presentation Layer)** – Zodpovědná za interakci s uživatelem.
- **Aplikační vrstva (Application Layer)** – Koordinuje aplikační aktivity a deleguje práci doménové vrstvě.
- **Doménová vrstva (Domain Layer)** – Obsahuje doménový model a doménovou logiku.
- **Infrastrukturní vrstva (Infrastructure Layer)** – Poskytuje technické služby pro ostatní vrstvy.

:::code{language="bash" filename="src/ (tradiční DDD struktura)"}
src/
├── Presentation/                # Prezentační vrstva
│   └── Controller/
│       └── UserController.php
├── Application/                 # Aplikační vrstva
│   ├── Service/
│   │   └── UserService.php
│   └── DTO/
│       └── UserDTO.php
├── Domain/                      # Doménová vrstva
│   ├── Model/
│   │   └── User.php
│   ├── Repository/
│   │   └── UserRepository.php
│   └── Service/
│       └── DomainUserService.php
└── Infrastructure/              # Infrastrukturní vrstva
    ├── Repository/
    │   └── DoctrineUserRepository.php
    └── Persistence/
        └── Doctrine/
            └── Mapping/
                └── User.orm.xml
:::

V tradičním přístupu jsou vrstvy organizovány horizontálně, což znamená, že každá vrstva poskytuje služby vrstvě nad ní.
Vrstvená architektura se liší od hexagonální (Ports & Adapters) nebo cibulové (Onion) – ty kladou důraz na inverzi závislostí a izolaci domény od infrastruktury, zatímco prostá vrstvená architektura závislosti pouze směruje shora dolů [[2]](https://alistair.cockburn.us/hexagonal-architecture/). Doménové stavební kameny – entity, hodnotové objekty, agregáty, doménové služby – jsou společné pro oba přístupy a popisuje je kapitola [Základní koncepty DDD](/zakladni-koncepty).

## 10.02 Vertikální slice architektura (Vertical Slice Architecture) {#vertical-slice}

Vertikální slice architektura organizuje kód podle funkcí (feature slices) namísto technických vrstev [[3]](https://www.jimmybogard.com/vertical-slice-architecture/).
Každá funkce (feature) obsahuje všechny vrstvy potřebné pro svou implementaci, čímž se snižují vazby mezi funkcemi a každá část aplikace může být vyvíjena nezávisle.

:::code{language="bash" filename="src/ (vertical slice struktura)"}
src/
├── UserManagement/             # Bounded Context: Správa uživatelů
│   ├── Domain/                # Doménová vrstva - sdílená pro celý kontext
│   │   ├── Model/
│   │   │   └── User.php       # Entita (Aggregate Root)
│   │   ├── ValueObject/
│   │   │   ├── UserId.php
│   │   │   └── Email.php
│   │   ├── Event/
│   │   │   └── UserRegistered.php
│   │   └── Repository/
│   │       └── UserRepository.php  # Rozhraní
│   ├── Infrastructure/        # Infrastruktura pro celý kontext
│   │   └── Repository/
│   │       └── DoctrineUserRepository.php
│   ├── Registration/          # Feature: Registrace uživatelů
│   │   ├── Command/
│   │   │   ├── RegisterUser.php
│   │   │   └── RegisterUserHandler.php
│   │   └── Controller/
│   │       └── RegistrationController.php
│   └── Profile/               # Feature: Profil uživatele
│       ├── Query/
│       │   ├── GetUserProfile.php
│       │   └── GetUserProfileHandler.php
│       ├── Controller/
│       │   └── ProfileController.php
│       └── ViewModel/
│           └── UserProfileViewModel.php
├── OrderManagement/           # Bounded Context: Správa objednávek
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/
│   │   │   ├── Order.php      # Aggregate Root
│   │   │   └── OrderItem.php
│   │   ├── ValueObject/
│   │   │   ├── OrderId.php
│   │   │   └── Money.php
│   │   └── Repository/
│   │       └── OrderRepository.php
│   ├── Infrastructure/
│   │   └── Repository/
│   │       └── DoctrineOrderRepository.php
│   └── Checkout/              # Feature: Pokladna
│       ├── Command/
│       │   ├── CreateOrder.php
│       │   └── CreateOrderHandler.php
│       └── Controller/
│           └── CheckoutController.php
└── Shared/                    # Skutečně sdílené komponenty
    └── Domain/
        └── Exception/
            └── DomainException.php
:::

Ve vertikální slice architektuře kód dělíme podle funkcí (feature slices) – každá funkce prochází celým stackem a obsahuje vše, co potřebuje pro svou implementaci.
Tento přístup minimalizuje vazby mezi jednotlivými funkcemi a maximalizuje vazby uvnitř funkce [[4]](https://www.youtube.com/watch?v=SUiWfhAhgQw). Zároveň zachovává principy DDD tím, že respektuje bounded contexts a doménový model.

:::callout{type="note"}
### Konvence struktury v tomto průvodci {#konvence-heading}

V celém průvodci používáme konzistentní strukturu pro vertikální slice architekturu:

- `{BC}/Domain/` – doménová vrstva sdílená uvnitř bounded contextu (Model, ValueObject, Event, Repository rozhraní, Service)
- `{BC}/Infrastructure/` – infrastrukturní implementace (Doctrine repozitáře, event bus adaptéry)
- `{BC}/{Feature}/` – feature slice s Command/, Query/, Controller/ přímo uvnitř
- `Shared/` – pouze skutečně sdílené komponenty (abstraktní typy, výjimky, bus rozhraní)
:::

## 10.03 Porovnání přístupů {#comparison}

| Aspekt | Tradiční (vrstvený) DDD | Vertikální slice architektura |
|---|---|---|
| **Organizace kódu** | Podle technických vrstev | Podle funkcí (features) |
| **Vazby** | Silné vazby mezi vrstvami | Silné vazby uvnitř funkce, slabé vazby mezi funkcemi |
| **Změny** | Změna často vyžaduje úpravy ve více vrstvách | Změna je obvykle omezena na jednu funkci |
| **Testovatelnost** | Často vyžaduje mnoho mocků pro testování | Méně mocků, protože závislosti jsou lokální |
| **Škálovatelnost** | Může být obtížné škálovat při růstu aplikace | Funkce lze přesunout do mikroslužeb bez přeorganizování všech vrstev |
| **Složitost** | Jednodušší pro pochopení na začátku | Může být složitější pro pochopení na začátku |
| **Vhodnost pro CQRS** | Vyžaduje dodatečnou práci pro implementaci CQRS | Přirozeně podporuje CQRS [[5]](https://docs.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/apply-simplified-microservice-cqrs-ddd-patterns) |

## 10.04 Kdy použít který přístup {#when-to-use}

:::callout{type="note"}
### Kdy použít tradiční DDD: {#traditional-when-to-use-heading}

- Když je tým zvyklý na tradiční architekturu.
- Pro menší aplikace s jasně definovanými vrstvami.
- Když je důležitá jasná separace technických vrstev.
- Když tým preferuje explicitní oddělení technických vrstev před organizací podle funkcí.
:::

:::callout{type="note"}
### Kdy použít vertikální slice architekturu: {#vertical-slice-when-to-use-heading}

- Pro větší a složitější aplikace.
- Když je důležitá modularita a nezávislost funkcí.
- Pro aplikace, které budou v budoucnu rozděleny do mikroslužeb.
- Když chcete implementovat CQRS.
- Pro týmy, které jsou zvyklé na agilní vývoj a časté změny.
:::

## 10.05 Implementace v Symfony 8 {#symfony-implementation}

Symfony 8 poskytuje nástroje a komponenty pro implementaci obou přístupů k DDD [[7]](https://symfony.com/doc/current/index.html).

### Implementace tradičního DDD v Symfony 8: {#traditional-symfony-implementation-heading}

Pro implementaci tradičního DDD v Symfony 8 můžete použít standardní adresářovou strukturu Symfony a rozdělit kód do vrstev. Detaily konfigurace Messengeru, DI a Doctrine pro DDD model popisuje kapitola [Implementace v Symfony 8](/implementace-v-symfony); kompletní příklad reálného projektu členěného do bounded contexts je v [Případové studii](/pripadova-studie).

:::code{language="bash" filename="src/ (Symfony 8 – tradiční)"}
src/
├── UserManagement/             # Bounded Context: Správa uživatelů
│   ├── Presentation/            # Prezentační vrstva
│   │   ├── Controller/
│   │   │   ├── UserController.php
│   │   │   └── RegistrationController.php
│   │   └── ViewModel/
│   │       └── UserViewModel.php
│   ├── Application/            # Aplikační vrstva
│   │   ├── Command/
│   │   │   ├── RegisterUser.php
│   │   │   └── RegisterUserHandler.php
│   │   ├── Query/
│   │   │   ├── GetUser.php
│   │   │   └── GetUserHandler.php
│   │   └── Service/
│   │       └── UserApplicationService.php
│   ├── Domain/                 # Doménová vrstva
│   │   ├── Model/
│   │   │   └── User.php         # Entita (Aggregate Root)
│   │   ├── ValueObject/
│   │   │   ├── UserId.php
│   │   │   └── Email.php
│   │   ├── Event/
│   │   │   └── UserRegistered.php
│   │   ├── Repository/
│   │   │   └── UserRepository.php  # Rozhraní
│   │   └── Service/
│   │       └── UserDomainService.php
│   └── Infrastructure/         # Infrastrukturní vrstva
│       ├── Repository/
│       │   └── DoctrineUserRepository.php
│       └── Persistence/
│           └── Doctrine/
│               └── Mapping/
│                   └── User.orm.xml
├── OrderManagement/           # Bounded Context: Správa objednávek
│   ├── Presentation/            # Prezentační vrstva
│   ├── Application/            # Aplikační vrstva
│   ├── Domain/                 # Doménová vrstva
│   └── Infrastructure/         # Infrastrukturní vrstva
└── Shared/                    # Sdílené komponenty
    └── Domain/                 # Sdílená doménová logika
        └── Exception/
            └── DomainException.php
:::

### Implementace vertikální slice architektury v Symfony 8: {#vertical-slice-symfony-implementation-heading}

Pro implementaci vertikální slice architektury v Symfony 8 můžete organizovat kód podle funkcí (features) [[8]](https://dev.to/etienneleba/another-way-to-structure-your-symfony-project-llo).
Oproti základnímu modelu z [sekce 10.02](#vertical-slice) (kde celý `Domain/` leží na úrovni kontextu) jde tato varianta hlouběji: každá feature dostane vlastní `Domain/` pro doménové služby a události specifické pro ni, zatímco sdílený agregátový model zůstává v kontextovém `Domain/`.

:::code{language="bash" filename="src/ (Symfony 8 – vertical slice)"}
src/
├── UserManagement/             # Bounded Context: Správa uživatelů
│   ├── Registration/           # Feature: Registrace uživatelů
│   │   ├── Domain/             # Doménová logika pro registraci
│   │   │   ├── Event/
│   │   │   │   └── UserRegistered.php
│   │   │   └── Service/
│   │   │       └── RegistrationDomainService.php
│   │   ├── Application/       # Aplikační logika pro registraci
│   │   │   ├── Command/
│   │   │   │   ├── RegisterUser.php
│   │   │   │   └── RegisterUserHandler.php
│   │   │   └── Service/
│   │   │       └── RegistrationService.php
│   │   └── Presentation/      # Prezentační vrstva pro registraci
│   │       ├── Controller/
│   │       │   └── UserRegistrationController.php
│   │       └── Form/
│   │           └── UserRegistrationForm.php
│   ├── Profile/                # Feature: Profil uživatele
│   │   ├── Application/       # Aplikační logika pro profil
│   │   │   ├── Query/
│   │   │   │   ├── GetUserProfile.php
│   │   │   │   └── GetUserProfileHandler.php
│   │   │   └── Service/
│   │   │       └── ProfileService.php
│   │   └── Presentation/      # Prezentační vrstva pro profil
│   │       ├── Controller/
│   │       │   └── UserProfileController.php
│   │       └── ViewModel/
│   │           └── UserProfileViewModel.php
│   ├── Domain/                # Sdílená doménová logika pro celý kontext
│   │   ├── Model/
│   │   │   └── User.php       # Entita (Aggregate Root)
│   │   ├── ValueObject/
│   │   │   ├── UserId.php
│   │   │   └── Email.php
│   │   └── Repository/
│   │       └── UserRepository.php  # Rozhraní
│   └── Infrastructure/       # Infrastruktura pro celý kontext
│       └── Repository/
│           └── DoctrineUserRepository.php
├── OrderManagement/           # Bounded Context: Správa objednávek
│   ├── Checkout/              # Feature: Pokladna
│   │   ├── Domain/
│   │   ├── Application/
│   │   └── Presentation/
│   └── ... (podobná struktura jako u UserManagement)
└── Shared/                    # Sdílené komponenty pro celý systém
    └── Domain/
        └── Exception/
            └── DomainException.php
:::

Pro vertikální slice architekturu jsou v Symfony 8 relevantní zejména tyto komponenty [[9]](https://symfony.com/doc/current/components/index.html):

- **Messenger komponenta** – Pro implementaci CQRS a asynchronní zpracování [[10]](https://symfony.com/doc/current/messenger.html).
- **Validator komponenta** – Pro validaci doménových objektů.
- **Form komponenta** – Pro zpracování vstupů od uživatele.
- **Security komponenta** – Pro autentizaci a autorizaci.
- **Doctrine ORM** – Pro persistenci doménových objektů.

:::faq{}
- question: Co je Vertical Slice Architecture?
  answer: 'Vertical Slice je přístup, který kód organizuje kolem feature neboli use casu, nikoli kolem technické vrstvy. Každá funkčnost má vlastní „sloupec" obsahující vše potřebné – command, handler, doménový model, read model, validaci – v jednom balíčku. Opakem je tradiční horizontální členění (Controller, Service, Repository vrstvy), kde jeden use case zasahuje do několika balíčků. Viz <a href="#vertical-slice">sekci Vertikální slice architektura</a>.'
- question: Jaký je rozdíl mezi tradiční vrstvovou architekturou a Vertical Slice?
  answer: 'Vrstvová architektura (Controller, Service, Repository, Entity) je horizontální: kód podobného typu žije pohromadě, ale jeden use case je roztroušený napříč vrstvami. Vertical Slice obrací orientaci – každý use case má vlastní izolovaný sloupec kódu, takže změna feature typicky nezasahuje jiné sloupce. Vrstvová architektura je tradičnější, Vertical Slice jednodušeji škáluje počet feature bez narůstajících vazeb. Srovnání obou přístupů v <a href="#comparison">sekci Porovnání přístupů</a>.'
- question: Kdy zvolit Vertical Slice a kdy tradiční DDD vrstvy?
  answer: 'Vertical Slice je vhodný pro aplikace s mnoha nezávislými use casy a rychlým vývojovým tempem, kde je přínosem izolace každé feature. Tradiční vrstvové DDD se osvědčuje tam, kde má doménový model silné sdílené invarianty, které je třeba jednotně vymáhat napříč celou aplikací. V praxi se přístupy kombinují: Vertical Slice pro aplikační vrstvu, sdílený doménový model uvnitř Bounded Contextu. Rozhodovací kritéria v <a href="#when-to-use">sekci Kdy použít který přístup</a>.'
- question: Lze Vertical Slice kombinovat s DDD?
  answer: 'Ano, kombinace je běžná a produktivní. DDD definuje, jak modelovat doménu (agregáty, hodnotové objekty, Bounded Contexts), Vertical Slice říká, jak organizovat kód use casu okolo tohoto modelu. Každý slice typicky obsahuje command nebo query DTO, handler, volání doménového modelu a read model pro odpověď. Doménový model zůstává sdílený uvnitř Bounded Contextu. Podrobný rozbor kombinace v <a href="#vertical-slice">sekci Vertikální slice architektura</a>.'
- question: Jak se Vertical Slice implementuje v Symfony?
  answer: 'Základem je struktura <code>src/[BoundedContext]/[Feature]/</code>, kde každá feature obsahuje vlastní Command, Handler, případně Query, Controller a View. Symfony Messenger funguje jako sběrnice, přes kterou kontroler dispatchuje command handleru uvnitř slicu. Sdílená doménová vrstva (agregáty, hodnotové objekty) zůstává na úrovni Bounded Contextu. Každý slice je tak samostatný modul s minimem závislostí na ostatních. Praktický příklad v <a href="#symfony-implementation">sekci Implementace v Symfony 8</a>.'
:::
