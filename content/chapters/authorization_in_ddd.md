---
route: authorization_in_ddd
path: /autorizace-v-ddd
title: Autorizace v DDD na Symfony
page_title: "Autorizace v DDD: Voters a ACL na agregátu | DDD Symfony"
meta_description: "Kde má v DDD aplikaci sedět autorizace: edge, use case, agregát, field. Čtyři vrstvy s ukázkami Symfony Voterů a policy-based přístupu."
meta_keywords: "Autorizace, Authorization, Symfony Voter, RBAC, ABAC, Policy-based, ACL, Aggregate permissions, DDD Symfony 8, Security, Doctrine, Owner-based, Multi-tenancy, TenantFilter"
og_type: article
published: "2026-04-29"
modified: 2026-09-24
breadcrumb_name: Autorizace v DDD
schema_type: TechArticle
schema_headline: "Autorizace v DDD na Symfony – 4 vrstvy, Voters a policy-based přístup"
chapter_number: "11"
category: Architektura
deck: 'V DDD aplikacích se opakovaně objevuje stejná otázka: <em>„smí to ten uživatel udělat?“</em> – patří do controlleru, do voteru, do aggregate, nebo někam jinam? Kapitola dává konkrétní čtyřvrstvý rámec: Edge, Use Case, Aggregate, Field. Každá vrstva odpovídá na jinou otázku a používá jiný Symfony nástroj.'
reading_time: 54
difficulty: 3
github_examples: Chapter10_Authorization
---

Předchozí kapitola postavila v Symfony 8 agregáty, repozitáře a Application Services. Otevřená zůstala otázka, kterou projekty obvykle řeší případ od případu: **kdo smí který use case zavolat a za jakých podmínek**. Odpovědí je čtyřvrstvý rámec, který každé autorizační rozhodnutí umístí na jednu vrstvu: od HTTP firewallu přes Symfony Voter, který aplikační vrstva volá z handleru, až po doménové invarianty v agregátu. Volání z Command Handleru ukazuje [sekce 11.04](#use-case-voter); kapitola o CQRS na to navazuje [middleware vrstvou](/cqrs#middleware), kterou lze autorizaci vytáhnout před handler.

Autentizaci (Symfony firewall, JWT, OAuth) tým většinou postaví bez větších potíží. Otázka *„kdo smí udělat co s konkrétní entitou v konkrétním stavu“* je ale jiná disciplína. Bez rámce se odpověď rozptýlí mezi controllery, listenery, Twig šablony a Doctrine query buildery. Podle rámce poznáte, kam které pravidlo patří a jak ho v Symfony 8 zapsat tak, aby Security komponenta nepronikla do doménového jádra.

Kapitola navazuje na [Implementaci v Symfony](/implementace-v-symfony), která autorizaci záměrně ponechala stranou a odkazuje sem. Doplňuje praktický pohled k tématům [CQRS](/cqrs) (kde sedí ověření Command Handleru), [Testování](/testovani-ddd) (jak otestovat každou ze 4 vrstev samostatně) a [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli) (která autorizaci zmiňuje jen letmo).

## 11.01 Tři chyby s autorizací, které se v review opakovaně objevují {#tri-chyby}

Tři vzory níže spojuje jedna příčina: chybí rozhodovací rámec, kam které pravidlo patří. Pořadí odpovídá odhadované četnosti v code review; měřená data k tomu nejsou, jde o autorský odhad. Že téma unese vlastní kapitolu, ale doložit lze: OWASP posunul [Broken Access Control](https://owasp.org/Top10/A01_2021-Broken_Access_Control/) v Top 10 pro rok 2021 z pátého místa na první.

### Chyba 1: Vše v controlleru {#tri-chyby-controller-heading}

Nejčastější vzor. Controller přijme HTTP požadavek, načte entitu z repository a inline porovná atributy uživatele s atributy entity:

:::code{language="php" filename="src/Controller/OrderController.php (anti-vzor)" highlights="13,14,15,16,17,18"}
<?php

// src/Controller/OrderController.php (anti-vzor)
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Ordering\Domain\Repository\OrderRepository;

final class OrderController extends AbstractController
{
    #[Route('/order/{id}/cancel', methods: ['POST'])]
    public function cancel(string $id, OrderRepository $orders): Response
    {
        $order = $orders->find($id);
        $user  = $this->getUser();

        // Anti-vzor: autorizační logika rozsypaná v controlleru
        if ($user->getId() !== $order->getCustomerId()) {
            throw $this->createAccessDeniedException('Not your order');
        }
        if ($order->getStatus() !== 'placed') {
            throw new \LogicException('Cannot cancel a non-placed order');
        }

        $order->setStatus('cancelled');
        $orders->save($order);

        return $this->redirectToRoute('order_detail', ['id' => $id]);
    }
}
:::

Co je špatně: stejný use case se volá i z konzolového commandu (cron, batch), ze Symfony Messenger handleru (asynchronní fronta) a z administračního panelu. Každý vstupní bod musí tutéž podmínku zopakovat a stačí, aby na ni jeden zapomněl. Pravidlo „zrušit smí jen vlastník“ patří na jedno místo v use-case vrstvě, ne do infrastruktury.

### Chyba 2: Vše ve Voteru, doména nezná autorizaci {#tri-chyby-vse-voter-heading}

Druhý extrém. Tým objeví Symfony Voter a přesune do něj *všechna* pravidla, včetně doménových invariantů. Aggregate má veřejné API `setStatus()`, `setTotal()`, `setCustomerId()` a Voter „natáhne“ autorizaci přes ně:

:::code{language="php" filename="src/Security/OrderVoter.php (anti-vzor)" highlights="13,14,15,16,17"}
<?php

// src/Security/OrderVoter.php (anti-vzor)
namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoter extends Voter
{
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool
    {
        $user = $token->getUser();

        // Anti-vzor: doménové pravidlo (cancellation window) ve Voteru
        if ($attribute === 'CANCEL') {
            if ($user->getId() !== $subject->getCustomerId()) { return false; }
            if ($subject->getStatus() !== 'placed')           { return false; }
            $age = (new \DateTimeImmutable())->getTimestamp() - $subject->getPlacedAt()->getTimestamp();
            if ($age > 86400) { return false; }
            return true;
        }
        return false;
    }
}
:::

Co je špatně: setter `Order::setStatus(OrderStatus::Cancelled)` dál existuje a je veřejný. Stačí, aby ho kdokoli (test, fixture, migrační skript, jiný vývojář) zavolal mimo Voter, a invariant „storno do 24 h“ přestane platit. Voter je jen *volitelný* filtr před vstupem, doména žádnou pojistku nemá. Storno lhůta je doménové pravidlo, ne pravidlo use case.

### Chyba 3: Autorizace na úrovni databázových řádků {#tri-chyby-doctrine-heading}

Tým objeví Doctrine SQLFilter a rozhodne, že autorizaci vyřeší v perzistentní vrstvě: entity, ke kterým uživatel nemá přístup, se z databáze nevrátí. Pro *read* dotazy to funguje, v doménové logice ne:

- Když handler dostane `$orderId` a entitu nenajde, neví, jestli neexistuje, nebo jen není dostupná pro daného uživatele. Hláška „Order not found“ pak mate.
- Doctrine filtry se nevztahují na entity už načtené v identity map, na nativní SQL ani na Redis cache.
- Filtr se neuplatní ani při načtení **neowning strany asociace one-to-one**. Ověřeno na ORM 3.6:
  `find()` i DQL cizí záznam skryjí, ale průchod z entity na druhý konec vztahu ho vrátí. Kdo staví
  oddělení tenantů jen na filtru, má tudy díru.
- Doménová pravidla typu „order patří customerovi“ ztrácejí jedno závazné místo: zapsaná jsou v SQL filtru, ve Voteru se na ně zapomíná a v aggregate chybí. Při volání mimo HTTP vrstvu se nevynutí.

:::callout{type="warn"}
### Diagnóza: chybí rámec, kam co umístit {#diagnoza-heading}

Vývojář má v každém okamžiku **jednu konkrétní otázku**: „smí to vidět?“, „smí to udělat?“, „je to vůbec možné?“, „má vidět tento sloupec?“. Každá z nich má svůj nástroj a svou vrstvu. Bez mapy sáhne po prvním nástroji, který má po ruce, a pravidla skončí rozházená po celé aplikaci.
:::

## 11.02 Čtyři vrstvy autorizace {#ctyri-vrstvy}

Nejdřív strategický kontext. Identita a oprávnění tvoří vlastní **Bounded Context**, kterému Vernon v *Implementing Domain-Driven Design* říká Identity and Access Context. V referenční implementaci `IDDD_Samples` stojí jako samostatný modul vedle Collaboration a Agile PM a ostatní kontexty ho konzumují jako službu. Typologicky jde o [generickou subdoménu](/subdomeny#tri-kategorie): kupuje se (Keycloak, Auth0, OIDC provider), nemodeluje se vlastními silami. Autorizační *rozhodnutí* přitom zůstává v konzumujícím kontextu, protože závisí na jeho entitách a stavech. Zdroj identit a rolí leží mimo něj a vazbu mezi obojím popisuje [Context Mapping](/context-mapping) jako Open Host Service.

Uvnitř konzumujícího kontextu padá rozhodnutí ve čtyřech postupných vrstvách. Každá má vlastní otázku, Symfony nástroj i granularitu. Vrstvy fungují jako *filtry*: další odpovídá na jemnější otázku a předpokládá, že předchozí už řekla „ano“.

:::diagram{fig="11.2-A" title="4 vrstvy autorizace v DDD aplikaci" src="images/diagrams/19_authorization/policy_layers.svg"}
:::

| Vrstva | Otázka | Symfony nástroj | Příklad |
|---|---|---|---|
| **Edge** | Je přihlášený? Smí na tuhle URL? | `access_control`, JWT firewall | `/admin/*` jen pro `ROLE_ADMIN` |
| **Use Case** | Smí vykonat use case na tomto objektu? | `Voter` | „Smí Petr cancelnout order #42?“ |
| **Aggregate** | Dá se to vůbec teď udělat? | doménový check + výjimka | „Order lze cancelnout jen 24 h od vytvoření“ |
| **Field** | Smí vidět konkrétní pole? | Twig + Voter, query filter | „Sloupec `audit_log` vidí jen admin“ |

Každé autorizační pravidlo má právě jedno místo *definice*. Vynutit ho lze na více vrstvách, pokud všechny čtou tutéž definici. OWASP to formuluje jako požadavek implementovat kontrolu jednou a znovu ji používat. Duplicitou je až *přepis* téhož pravidla druhými slovy na druhé vrstvě; typické případy ukazuje [sekce o anti-vzorech](#antivzory).

Metafora filtrů má jednu podmínku. Uvnitř use-case vrstvy platí jen při rozhodovací strategii `unanimous`. Výchozí `affirmative` ji obrací: stačí jeden souhlasící Voter a nesouhlas ostatních se ignoruje. Podrobnosti v [sekci o rozhodovací strategii](#access-decision).

*Citace: Symfony Security komponenta dokumentuje vícevrstvý přístup v sekci „Authorization“ [[1]](https://symfony.com/doc/current/security.html#access-control-authorization); obecné principy ABAC vs. RBAC najdete v NIST SP 800-162 [[2]](https://csrc.nist.gov/publications/detail/sp/800-162/final). Vernon v *Implementing Domain-Driven Design* (kap. 14, „Application“) umisťuje autorizační kontrolu do Application Services: aplikační služba se podle něj stará o bezpečnost a překlad objektů. Čtyřvrstvý rámec této kapitoly u něj nenajdete – jde o autorské rozšíření, ne o Vernonův model.*

## 11.03 Edge – Symfony firewall a access_control {#edge}

Edge je nejhrubší vrstva a leží mimo doménový kód. Odpovídá jen na otázku **„kdo je vůbec na druhém konci socketu?“**: anonymní, přihlášený, případně s rolí pro hrubě dělené sekce (`/admin/*`, `/api/v1/*`). Pravidla typu „zákazník X smí na tuto objednávku“ patří o vrstvu výš (use case).

Uživatelský provider ukazuje na třídu `SecurityUser` z infrastruktury, ne na doménovou entitu. Na tom stojí celý zbytek kapitoly: provider vyžaduje implementaci `Symfony\Component\Security\Core\User\UserInterface`, a kdyby ji nesla doménová třída, doména by se svázala se Security komponentou. Přesně to zakazuje [anti-vzor 4](#anti-symfony-user-domain-heading). `SecurityUser` je navíc *read model* pro autentizaci: nese identifikátor, hash hesla, role a doménové ID (`CustomerId`, `TenantId`). Mění se z jiných důvodů než doménový model uživatele [[3]](https://matthiasnoback.nl/2022/07/decoupling-your-security-user-from-your-user-model/).

:::code{language="yaml" filename="config/packages/security.yaml (výřez: firewall)"}
# config/packages/security.yaml
security:
    # Recept security-bundle tenhle blok vygeneruje sám. Kdo výpis níž
    # zkopíruje jako celý soubor, přijde o něj – a první přihlášení pak
    # skončí na „No password hasher has been configured“.
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                # Infrastrukturní třída implementující UserInterface,
                # mapovaná na tabulku app_user. Doména o ní neví.
                class: App\Identity\Infrastructure\Security\SecurityUser
                property: email

    firewalls:
        # Stateless API – JWT. Klíč `jwt` registruje
        # lexik/jwt-authentication-bundle; bez něj konfigurace neprojde
        # („Unrecognized option jwt“), takže celý blok nechte zakomentovaný,
        # dokud balíček nemáte. Nativní ekvivalent je `access_token`.
        # api:
        #     pattern: ^/api/
        #     stateless: true
        #     jwt: ~
        #     provider: app_user_provider

        # Web – session
        main:
            pattern: ^/
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: login
                check_path: login
                # Šablona posílá `_csrf_token` s id `authenticate`, bez této
                # volby ho ale form_login nečte ani neověřuje.
                enable_csrf: true
                # Bez tohohle řádku míří Symfony po přihlášení na `/`,
                # kterou kniha nikde nedefinuje – první proklik po loginu
                # by skončil na 404.
                default_target_path: app_profile
            # Bez `target` platí totéž co u přihlášení: odhlášení
            # přesměruje na `/` a skončí na 404.
            logout: { target: login }

    access_control:
        # Veřejné endpointy
        - { path: ^/login,        roles: PUBLIC_ACCESS }
        # Pozor na tvar cesty: ^/register nepokrývá /api/register.
        # JSON endpoint by jinak skončil přesměrováním na login dřív,
        # než se kontroler vůbec spustí.
        - { path: ^/register,     roles: PUBLIC_ACCESS }
        - { path: ^/api/register, roles: PUBLIC_ACCESS }
        - { path: ^/health,       roles: PUBLIC_ACCESS }
        # Hrubá role-based separace
        - { path: ^/admin,        roles: ROLE_ADMIN }
        - { path: ^/api/internal, roles: ROLE_SERVICE_ACCOUNT }
        # Vše ostatní za autentizací
        - { path: ^/,             roles: IS_AUTHENTICATED_FULLY }
:::

`form_login` odkazuje na routu `login`. Firewall ji sám nezaloží a první chráněný request
spadne na chybějící routě:

:::code{language="php" filename="src/Identity/Infrastructure/Http/LoginController.php"}
<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    // Přihlášení samo zpracovává firewall. Kontroler jen vykreslí
    // formulář a poslední chybu – proto tu není žádná logika.
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function __invoke(AuthenticationUtils $utils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $utils->getLastUsername(),
            'error'         => $utils->getLastAuthenticationError(),
        ]);
    }
}
:::

Šablona je krátká, ale dvě jména v ní nejsou volná: `_username` a `_password` očekává
`form_login` autentikátor a přejmenovat je znamená přenastavit firewall.

:::code{language="twig" filename="templates/security/login.html.twig"}
{% extends 'base.html.twig' %}

{% block body %}
    {% if error %}
        <p class="error">{{ error.messageKey|trans(error.messageData, 'security') }}</p>
    {% endif %}

    <form method="post">
        <label for="username">E-mail</label>
        <input type="email" id="username" name="_username" value="{{ last_username }}" required>

        <label for="password">Heslo</label>
        <input type="password" id="password" name="_password" required>

        <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
        <button type="submit">Přihlásit</button>
    </form>
{% endblock %}
:::

Třída `SecurityUser` tvoří most mezi Symfony a doménou. Kromě `UserInterface` vystavuje doménové identifikátory pro aplikační vrstvu:

:::code{language="php" filename="src/Identity/Infrastructure/Security/SecurityUser.php"}
<?php

// src/Identity/Infrastructure/Security/SecurityUser.php
declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\Identity\Domain\TenantId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $email,
        #[ORM\Column(type: 'string')]
        private string $passwordHash,
        /** @var list<string> */
        #[ORM\Column(type: 'json')]
        private array $roles,
        #[ORM\Column(type: 'string')]
        private string $customerId,
        #[ORM\Column(type: 'string')]
        private string $tenantId,
    ) {}

    public function getUserIdentifier(): string { return $this->email; }
    public function getPassword(): string { return $this->passwordHash; }

    /** @return list<string> */
    public function getRoles(): array { return $this->roles; }

    // Most do domény – Voter i handler pracují s doménovým typem
    public function customerId(): CustomerId { return CustomerId::fromString($this->customerId); }
    public function tenantId(): TenantId { return TenantId::fromString($this->tenantId); }
}
:::

`TenantId` je obyčejný hodnotový objekt stejného tvaru jako `OrderId`; bez něj `SecurityUser`
neprojde ani autoloadem a firewall zůstane bez uživatelů:

:::code{language="php" filename="src/Identity/Domain/TenantId.php"}
<?php

declare(strict_types=1);

namespace App\Identity\Domain;

final readonly class TenantId
{
    public function __construct(
        public string $value,
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('TenantId nesmí být prázdné.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
:::

:::callout{type="warn"}
### Registrace musí založit obojí {#registration-two-writes-heading}

`SecurityUser` je samostatná tabulka, ne pohled na agregát `User`. Registrace proto zapisuje
dvakrát: doménový agregát do `users` a přihlašovací záznam do `app_user`. Bez druhého zápisu
vznikne uživatel, který se **nemůže přihlásit**, a nic přitom nespadne. Vazbu drží sloupec
`customer_id`, na který se ptá read model profilu.

:::code{language="php" filename="src/Identity/Application/CreateSecurityUserOnUserRegistered.php"}
<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class CreateSecurityUserOnUserRegistered
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UserRegistered $event): void
    {
        $user = $this->users->findById(new UserId($event->userId));

        if ($user === null) {
            return;
        }

        // Hash hesla vzniká v agregátu, security vrstva ho jen přebírá.
        // Druhé hashování by přihlášení rozbilo.
        $this->em->persist(new SecurityUser(
            email: $user->email()->value,
            passwordHash: $user->hashedPassword()->value,
            roles: ['ROLE_USER'],
            customerId: $user->id->value,
            tenantId: 'default',
        ));

        $this->em->flush();
    }
}
:::

Posluchač si hash dotáhne z agregátu, protože do doménové události nepatří. Událost se může
serializovat a otisk hesla v payloadu je zbytečné riziko. Cenou je závislost `Identity`
na repozitáři `UserManagementu`; je to jednosměrná vazba na rozhraní, ne na model,
a `Identity` je tu podpůrný kontext.
:::

Principy edge vrstvy:

- **Žádná doménová znalost.** Edge nezná pojmy „objednávka“, „zákazník“ ani „storno lhůta“. Pracuje jen se vzorem URL, rolemi a stavem autentizace.
- **Default deny.** Poslední pravidlo v `access_control` je „všechno ostatní vyžaduje přihlášení“. Bez něj stačí přidat nový endpoint, zapomenout ho zařadit, a je veřejný.
- **Role-based, ne attribute-based.** ROLE_ADMIN je hrubá kategorie; jemnější rozhodnutí jako „admin tenantu T1, ne T2“ patří do Voteru.
- **JWT firewall vs. session.** API bývá stateless (`jwt` autentikátor), web pracuje se session. Pro JWT v Symfony existuje balíček `lexik/jwt-authentication-bundle` nebo nativní `access_token` autentikátor s `OidcTokenHandler` pro OpenID Connect provider [[4]](https://openid.net/specs/openid-connect-core-1_0.html).

Matcherů má `access_control` víc než jen `path` a `roles`. K dispozici jsou `host`, `port`, `ips`, `methods`, `attributes`, `route`, `request_matcher`, a k tomu `allow_if` a `requires_channel` [[5]](https://symfony.com/doc/current/security/access_control.html). Uplatní se **první shodné pravidlo** a nespecifikovaný matcher odpovídá čemukoli. Pravidlo `{ path: ^/api, methods: [POST] }` tedy nechrání GET na téže cestě, pokud dřív v seznamu není obecnější záznam.

:::callout{type="warn"}
### Past: `roles` a `allow_if` v jednom pravidle se chovají jako OR {#edge-allow-if-heading}

Zápis `{ path: ^/report, roles: ROLE_ANALYST, allow_if: "is_granted('ROLE_ADMIN')" }` vypadá jako konjunkce dvou podmínek. Při výchozí strategii `affirmative` ale stačí splnit jednu, protože každá přispěje samostatným hlasem. Kdo takto skládá restrikce, otevře endpoint širší skupině, než zamýšlel. Má-li platit AND, patří obě podmínky do jednoho výrazu `allow_if`, nebo se strategie přepne na `unanimous`.
:::

:::callout{type="pattern"}
### Vzorová analogie: Stripe API key model {#edge-stripe-heading}

Stripe rozlišuje API klíče na úrovni edge: `sk_test_*`, `sk_live_*`, `pk_*`, restricted keys s explicitním scope [[6]](https://stripe.com/docs/keys). Klíč rozhoduje, zda volání vůbec dorazí do API; to je edge vrstva. *Kdo konkrétně* za klíčem stojí (jaký účet, jaká oprávnění na konkrétní entitu Customer nebo Charge), řeší až další vrstva. Stejně se dělí práce v Symfony API: JWT ověří, kdo volá, Voter rozhodne, co s konkrétním objektem smí.
:::

## 11.04 Use Case – Symfony Voter {#use-case-voter}

Use case vrstva odpovídá na otázku **„smí *tento* uživatel vykonat *tento* use case na *tomto* objektu?“**. Přesně na to je Symfony Voter navržený. Pravidlo: **1 use case = 1 atribut Voteru**; jeden Voter může pokrývat N atributů, pokud se týkají stejné entity (typicky operace nad jedním agregátem).

Voter zná dvě věci: **identitu uživatele** (přes `TokenInterface`) a **cílový subjekt** (typicky aggregate root). Nesmí načítat subjekt, o kterém rozhoduje, a nesmí znát doménové invarianty – ty vynucuje agregát. Storno lhůtu tedy Voter nepřebírá zvenku; patří ke stavu agregátu.

:::code{language="php" filename="src/Ordering/Infrastructure/Security/OrderVoter.php" highlights="18,19,20,28,29,30,31,33,34,35,36,50,58,59"}
<?php

// src/Ordering/Infrastructure/Security/OrderVoter.php
declare(strict_types=1);

namespace App\Ordering\Infrastructure\Security;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Ordering\Domain\Model\Order;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoter extends Voter
{
    public const VIEW   = 'order.view';
    public const CANCEL = 'order.cancel';
    public const REFUND = 'order.refund';

    public function __construct(
        private readonly AccessDecisionManagerInterface $decisions,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL, self::REFUND], true)
            && $subject instanceof Order;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL, self::REFUND], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === Order::class;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool
    {
        $user = $token->getUser();
        if (!$user instanceof SecurityUser) {
            return false;
        }

        \assert($subject instanceof Order);

        return match ($attribute) {
            self::VIEW   => $this->canView($subject, $user, $token),
            self::CANCEL => $this->canCancel($subject, $user),
            self::REFUND => $this->decisions->decide($token, ['ROLE_REFUND_AGENT']),
            default      => false,
        };
    }

    private function canView(Order $order, SecurityUser $user, TokenInterface $token): bool
    {
        // Vlastnictví definuje agregát, Voter se jen ptá
        return $order->isOwnedBy($user->customerId())
            || $this->decisions->decide($token, ['ROLE_ADMIN']);
    }

    private function canCancel(Order $order, SecurityUser $user): bool
    {
        return $order->isOwnedBy($user->customerId());
    }
}
:::

Pět implementačních detailů:

- **Konstanty atributů s prefixem entity** (`order.cancel`, ne jen `CANCEL`). Nekolidují s atributy jiných Voterů (`invoice.cancel`, `shipment.cancel`) a z audit logu je hned vidět, kterého subjektu se rozhodnutí týkalo.
- **Match expression** místo stromu if-else. Bez default větve PHPStan ohlásí nepokrytý případ; `default => false` volí tiché zamítnutí (fail-closed) a tuto kontrolu obětuje.
- **Privátní metody `canView`, `canCancel`**. Každý use case má vlastní metodu, test namockuje token i subjekt a asserce na výsledek je explicitní. Bez extrakce by Voter přerostl v nečitelný switch-case.
- **Role se uvnitř Voteru kontrolují přes `AccessDecisionManagerInterface::decide()`**, ne dotazem na uživatelskou třídu. Volání `$user->hasRole('ROLE_ADMIN')` obejde hierarchii rolí ze `security.yaml`: uživatel s `ROLE_SUPER_ADMIN` by `ROLE_ADMIN` nedostal, přestože ho hierarchie zahrnuje. Doporučuje to i dokumentace k Voterům [[7]](https://symfony.com/doc/current/security/voters.html).
- **`supportsAttribute()` a `supportsType()`** pocházejí z `CacheableVoterInterface`, které abstraktní `Voter` implementuje. Obě ve výchozím stavu vracejí `true`, takže bez override nic neušetří. Seznam s 200 řádky a pěti Votery znamená tisíc zbytečných volání `supports()`; s override jich většina odpadne už v rozhodovacím manažeru.

### Rozhodovací strategie a `AccessDecisionManager` {#access-decision}

Voterů bývá v aplikaci víc a jejich hlasy skládá dohromady `AccessDecisionManager`. Strategie, kterou použije, mění výsledek víc než cokoli uvnitř samotných Voterů:

| Strategie | Chování | Kdy se hodí |
|---|---|---|
| `affirmative` | výchozí; stačí jeden souhlas | jednoduché aplikace s jedním Voterem na subjekt |
| `unanimous` | zamítne, jakmile nesouhlasí kdokoli | vrstvená autorizace, multi-tenancy |
| `consensus` | rozhoduje většina | zřídka; výsledek se hůř zdůvodňuje |
| `priority` | rozhodne první nezdržující se volič | explicitní pořadí přes `#[AsTaggedItem(priority: …)]` |

Pro rámec této kapitoly je výchozí `affirmative` špatná volba. Kdo si vedle `OrderVoter` postaví `TenantVoter`, dostane opak toho, co čekal: `TenantVoter` cizí tenant zamítne, `OrderVoter` řekne ano podle vlastnictví a přístup projde. Vrstvená autorizace proto potřebuje `unanimous`.

Blok patří do stejného `security.yaml` jako firewall výše, ne místo něj. YAML má jeden kořenový klíč `security:` a druhý dokument by ten první přepsal.

:::code{language="yaml" filename="config/packages/security.yaml (kanonická konfigurace knihy)"}
# config/packages/security.yaml
security:
    access_decision_manager:
        strategy: unanimous
        allow_if_all_abstain: false   # nikdo nehlasoval = zamítnuto
:::

Hodnota `allow_if_all_abstain: false` je výchozí, ale patří do konfigurace explicitně. Je to poslední fail-closed pojistka: atribut, ke kterému se žádný Voter nepřihlásí, skončí zamítnutím, ne tichým povolením.

### `#[IsGranted]` na controlleru {#is-granted-attribute}

Autoritativní je kontrola v handleru. Na hranici HTTP se ale vyplatí odmítnout požadavek dřív, než vznikne command. K tomu slouží atribut `Symfony\Component\Security\Http\Attribute\IsGranted`. Funguje na metodě i na celé třídě controlleru a parametrem `subject` určuje argument akce, o kterém se rozhoduje:

:::code{language="php" filename="src/Ordering/Infrastructure/Http/OrderController.php"}
<?php

// src/Ordering/Infrastructure/Http/OrderController.php
declare(strict_types=1);

namespace App\Ordering\Infrastructure\Http;

use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Domain\Model\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class OrderController extends AbstractController
{
    #[Route('/order/{id}/cancel', name: 'order_cancel', methods: ['POST'])]
    #[IsGranted('order.cancel', subject: 'order', statusCode: 404)]
    public function cancel(Order $order, Request $request, MessageBusInterface $bus): Response
    {
        // Voter už rozhodl; controller jen přeloží vstup na command
        $bus->dispatch(new CancelOrderCommand(
            orderId: $order->id,
            reason:  (string) $request->request->get('reason', ''),
            actorId: $this->getUser()->customerId(),
        ));

        return $this->redirectToRoute('order_detail', ['id' => $order->id->value]);
    }

    #[Route('/order/{id}', name: 'order_detail', methods: ['GET'])]
    #[IsGranted('order.view', subject: 'order', statusCode: 404)]
    public function detail(Order $order): Response
    {
        // isCancellable() si aktuální čas nebere sama, takže ho šablona
        // musí dostat odsud – jinak Twig hlásí, že proměnná neexistuje.
        return $this->render('order/detail.html.twig', [
            'order' => $order,
            'now'   => new \DateTimeImmutable(),
        ]);
    }
}
:::

Parametr `statusCode: 404` mění odpověď z 403 na 404. Rozdíl není kosmetický: 403 útočníkovi potvrdí, že objednávka s daným ID existuje, a otevře cestu k enumeraci cizích identifikátorů. Téma rozvádí [callout o 403 vs. 409](#aggregate-403-vs-409-heading).

Atribut má dvě omezení. První: potřebuje subjekt už jako objekt, takže se neobejde bez převodu z parametru routy a dotaz do databáze se přesouvá do controlleru. `#[MapEntity]` na to sám nestačí. `EntityValueResolver` předá `find()` řetězec z URL, jenže identita je namapovaná vlastním typem, který řetězec odmítne. Resolver výjimku spolkne a vrátí 404, takže se chyba hledá na špatném místě:

```
Could not convert PHP value '01a074c3-…' to type OrderIdType.
Expected one of the following types: null, OrderId.
```

Řešením je vlastní resolver, který řetězec převede na hodnotový objekt dřív, než sáhne do repozitáře:

:::code{language="php" filename="src/Ordering/Infrastructure/Http/OrderValueResolver.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Http;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

// Vyšší priorita než EntityValueResolver, jinak se k slovu nedostane.
#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
final readonly class OrderValueResolver implements ValueResolverInterface
{
    public function __construct(private OrderRepository $orders) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Order::class) {
            return [];
        }

        $id = $request->attributes->get('id');

        if (!is_string($id) || !Uuid::isValid($id)) {
            throw new NotFoundHttpException('Neplatné ID objednávky.');
        }

        yield $this->orders->get(OrderId::fromString($id));
    }
}
:::

Druhé omezení: jakmile controller command jen odešle na asynchronní bus, `#[IsGranted]` chrání pouze vstup do fronty. Zpracování ve workeru běží bez tokenu a řeší ho [následující sekce](#async-authorization). Atribut proto kontrolu v handleru nenahrazuje, jen ji doplňuje na hranici.

### Proč ne Symfony ACL {#no-symfony-acl}

Starší materiály nabízejí pro oprávnění na jednotlivé objekty komponentu ACL: tabulky `acl_entries`, `acl_object_identities` a `MaskBuilder`. Pro Symfony 8 to volba není. Podpora ACL zmizela ze SecurityBundle ve verzi 4.0 a samostatný `symfony/acl-bundle` deklaruje ve svém posledním vydání (2.4.0 z dubna 2024) podporu Symfony 4.4 až 7.x. Symfony 8 mezi nimi není. Ani technicky by nešlo o dobrou náhradu. ACL ukládá rozhodnutí jako *data* v databázi, takže pravidlo „vlastník smí zrušit do 24 hodin“ se promítne do řádků, které musí někdo držet v synchronizaci se stavem agregátu. Voter tutéž věc spočítá z aktuálního stavu a nic synchronizovat nemusí. Kde jsou potřeba explicitně přidělovaná oprávnění na jednotlivé objekty (sdílení dokumentu, delegace), sahá se po vlastní tabulce vazeb nebo po ReBAC modelu z [pozdější sekce](#rebac).

### Použití ve Command Handleru {#voter-handler-heading}

Voter někdo musí zavolat. Místo pro to je **Application Service / Command Handler**, kde se autorizace ověří *před* doménovou operací. Handler injektuje `AuthorizationCheckerInterface`, tedy rozhraní Security komponenty. V aplikační vrstvě je taková závislost v pořádku, doménová by ji mít nesměla.

:::code{language="php" filename="src/Ordering/Application/Handler/CancelOrderHandler.php" highlights="18,19,25,26,27,28,29"}
<?php

// src/Ordering/Application/Handler/CancelOrderHandler.php
declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Infrastructure\Security\OrderVoter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private AuthorizationCheckerInterface $auth,
    ) {}

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->orders->get($command->orderId);

        if (!$this->auth->isGranted(OrderVoter::CANCEL, $order)) {
            throw new AccessDeniedDomainException(
                sprintf('Cancel not allowed for order %s', $command->orderId->value)
            );
        }

        $order->cancel(reason: $command->reason, when: new \DateTimeImmutable());
        $this->orders->save($order);

        // Publikaci událostí ukázka vynechává, aby zůstala čitelná otázka
        // autorizace. V úplném tvaru ji doplňuje async varianta níže –
        // a bez ní se agregát a read model rozejdou.
    }
}
:::

Obě výjimky, které v kapitole padají, jsou obyčejné doménové třídy. Dělí je vrstva:
autorizační patří aplikaci, časové okno doméně, a aplikační vrstva každou překládá
na jiný HTTP status.

:::code{language="php" filename="src/Ordering/Application/Exception/AccessDeniedDomainException.php + src/Ordering/Domain/Exception/CancellationWindowExpiredException.php"}
<?php

// --- src/Ordering/Application/Exception/AccessDeniedDomainException.php ---

declare(strict_types=1);

namespace App\Ordering\Application\Exception;

/** Aktér na operaci nemá právo. Aplikační vrstva to překládá na 403. */
final class AccessDeniedDomainException extends \DomainException
{
}

// --- src/Ordering/Domain/Exception/CancellationWindowExpiredException.php ---

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\OrderId;

/** Právo je v pořádku, jen uplynula lhůta. Odtud 409, ne 403. */
final class CancellationWindowExpiredException extends \DomainException
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly \DateTimeImmutable $placedAt,
        public readonly \DateTimeImmutable $attemptedAt,
    ) {
        parent::__construct(sprintf(
            'Objednávku „%s“ potvrzenou %s už nelze stornovat (pokus %s).',
            $orderId->value,
            $placedAt->format('Y-m-d H:i'),
            $attemptedAt->format('Y-m-d H:i'),
        ));
    }
}
:::

Po kontrole zavolá handler doménovou operaci `$order->cancel(...)` a ta uvnitř agregátu ověří invarianty (stav, storno lhůtu). Vznikají tak **dvě nezávislé bariéry**: Voter řekne „smí Petr“, agregát řekne „dá se to teď vůbec udělat“. Agregátovou vrstvu rozebírá [sekce 11.06](#aggregate-level). Háček: handler nese atribut `#[AsMessageHandler]` a v asynchronním workeru žádný token neexistuje. Tomu se věnuje [následující sekce](#async-authorization).

### Voter v Twig template {#voter-twig-heading}

Tentýž Voter pokrývá i rozhodnutí ve view, třeba skrýt tlačítko „Zrušit objednávku“ tomu, kdo objednávku nevlastní. Funkce `is_granted()` v Twigu volá stejný `AuthorizationCheckerInterface`. Proměnnou `now` (`\DateTimeImmutable`) předává do šablony controller, protože `isCancellable()` si aktuální čas nebere sama. Šablona čte agregát přímo a u detailu jedné objednávky to stačí. Jakmile obrazovka potřebuje jméno zákazníka nebo data z jiného kontextu, patří jí read model, ne další getter na agregátu:

:::code{language="twig" filename="templates/order/detail.html.twig" highlights="4,12,18"}
{# templates/order/detail.html.twig #}
{# Šablona sahá jen na to, co agregát opravdu má: identitu zákazníka,
   ne objekt Customer, a hodnotu enumu, ne vymyšlený label. #}
<h1>Objednávka {{ order.id.value }}</h1>

{% if is_granted('order.view', order) %}
    <dl>
        <dt>Zákazník</dt><dd>{{ order.customerId.value }}</dd>
        <dt>Celkem</dt>  <dd>{{ (order.totalAmount.amountInCents / 100)|number_format(2, ',', ' ') }} Kč</dd>
        <dt>Stav</dt>    <dd>{{ order.status.value }}</dd>
    </dl>
{% endif %}

{% if is_granted('order.cancel', order) and order.isCancellable(now) %}
    <form method="post" action="{{ path('order_cancel', {id: order.id.value}) }}">
        <button type="submit">Zrušit objednávku</button>
    </form>
{% endif %}

{# Refund je operace pro obsluhu, ne pro zákazníka – atribut ji pustí
   jen roli z Voteru. Odkaz vede na routu, kterou kniha nedodává, proto
   je zakomentovaný: path() na neexistující routu shodí celou šablonu,
   a to až ve chvíli, kdy se přihlásí první uživatel s tou rolí. #}
{# {% if is_granted('order.refund', order) %}
    <a href="{{ path('order_refund', {id: order.id.value}) }}">Vrátit platbu</a>
{% endif %} #}
:::

`{% if is_granted(...) %}` v Twigu jen schová tlačítko. Request poslaný ručně (curl, Postman, dev tools prohlížeče) nezastaví. Kontrola ve view je *UX*, ne bezpečnostní bariéra; ta stojí v handleru.

:::callout{type="warn"}
### Voter nenačítá subjekt, o kterém rozhoduje {#voter-anti-fetching-heading}

Voter, který volá `$this->repository->find($id)` nad subjektem, jenž mu měl přijít jako parametr, je anti-vzor. `$subject` dostává v paměti, handler ho už načetl. Druhé načtení znamená *duplicate query*, v horším případě *race condition*: mezi dotazem ve Voteru a operací v handleru se entita změní a rozhodnutí padne nad neaktuálním stavem.

Zákaz se netýká *doplňkových* dat, která na subjektu nejsou: členství v týmu, delegace, hierarchie tenantů. Ta si Voter načíst musí a dokumentace Symfony s injektovanými službami ve Voteru počítá [[7]](https://symfony.com/doc/current/security/voters.html). Takové dotazy patří za cache platnou po dobu requestu, jinak seznam s dvěma sty řádky vygeneruje dvě stě dotazů.
:::

## 11.05 Autorizace v asynchronním kontextu {#async-authorization}

Jakmile command putuje přes asynchronní transport, kontrola přes `AuthorizationCheckerInterface` přestane fungovat. Messenger worker běží mimo HTTP požadavek: `TokenStorage` je prázdná, `$this->security->getUser()` vrací `null` a Voter postavený na tokenu každé volání zamítne. Kód, který synchronně fungoval, začne po přepnutí transportu tiše odmítat legitimní operace.

Řešení: **command nese identitu aktéra**. V místě vzniku, typicky v controlleru, token ještě existuje. Tam se do commandu zapíše `actorId` jako doménový identifikátor uživatele, ne Symfony `UserInterface`. Handler pak autorizuje proti této identitě bez ohledu na to, kde a kdy běží.

:::code{language="php" filename="src/Ordering/Application/Command/CancelOrderCommand.php" highlights="14"}
<?php

// src/Ordering/Application/Command/CancelOrderCommand.php
declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderId;

final readonly class CancelOrderCommand
{
    public function __construct(
        public OrderId $orderId,
        public string $reason,
        public CustomerId $actorId, // identita aktéra z místa vzniku
    ) {}
}
:::

:::code{language="php" filename="src/Ordering/Application/Handler/CancelOrderHandler.php (async varianta)" highlights="19,20,21,22,23,24"}
<?php

// src/Ordering/Application/Handler/CancelOrderHandler.php (async varianta)
declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Ordering\Domain\Repository\OrderRepository;
use App\SharedKernel\Domain\SystemActor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private EntityManagerInterface $em,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->orders->get($command->orderId);

        // Autorizace proti identitě v commandu – token ve workeru neexistuje.
        // Systémová identita vlastníkem není a nikdy nebude, proto stojí ve
        // vlastní větvi. Bez ní handler odmítne vlastní kompenzaci ságy.
        $isSystem = $command->actorId->value === SystemActor::ID;

        if (!$isSystem && !$order->isOwnedBy($command->actorId)) {
            throw new AccessDeniedDomainException(
                sprintf('Cancel not allowed for order %s', $command->orderId->value)
            );
        }

        // Zámek patří procesu, takže si ho proces sám uvolní. Kdyby to
        // dělal až samostatný příkaz, záleželo by na pořadí ve frontě.
        if ($isSystem) {
            $order->releaseSagaLock();
        }

        $order->cancel(reason: $command->reason, when: new \DateTimeImmutable());
        $this->orders->save($order);
        $this->em->flush();

        // Bez tohohle kroku agregát skončí v cancelled, ale dashboard
        // zůstane na placed. Nic nespadne – stavy se jen rozejdou.
        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
:::

Tahle varianta je úplná. Obě ukázky nesou stejné FQCN, takže do projektu jde jen jedna, a to tahle. Synchronní verze výše zůstává proto, aby na ní byla vidět samotná otázka autorizace; ve workeru by neobstála, protože `AuthorizationCheckerInterface` tam nemá token.

Pravidlu vlastnictví stačí porovnat `actorId` s vlastníkem agregátu. Voter z HTTP vrstvy přitom nemizí: controller před odesláním commandu volá `is_granted` jako rychlou zpětnou vazbu pro UI. Rozhodující kontrola ale sedí v handleru a v agregátu a běží při každém zpracování, synchronním i asynchronním.

### Když je potřeba ve workeru celý Voter {#async-is-granted-for-user}

Ruční porovnání identit stačí na vlastnictví. Pravidla závislá na rolích (refund smí jen `ROLE_REFUND_AGENT`) by se tak musela ve workeru napsat podruhé a jinak než ve Voteru, což je přesně duplicita, kterou zakazuje [anti-vzor 3](#anti-duplication-heading). Symfony na to má `UserAuthorizationCheckerInterface` a metodu `isGrantedForUser()`, která spustí tytéž Votery proti předanému uživateli, aniž by potřebovala session nebo token v `TokenStorage`:

:::code{language="php" filename="src/Ordering/Application/Handler/RefundOrderHandler.php" highlights="20,27,29"}
<?php

// src/Ordering/Application/Handler/RefundOrderHandler.php
declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Identity\Infrastructure\Security\SecurityUserProvider;
use App\Ordering\Application\Command\RefundOrderCommand;
use App\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Infrastructure\Security\OrderVoter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authorization\UserAuthorizationCheckerInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RefundOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private SecurityUserProvider $users,
        private UserAuthorizationCheckerInterface $auth,
    ) {}

    public function __invoke(RefundOrderCommand $command): void
    {
        $order = $this->orders->get($command->orderId);
        // Aktér se načte podle identity v commandu, ne ze snapshotu rolí
        $actor = $this->users->byCustomerId($command->actorId);

        if (!$this->auth->isGrantedForUser($actor, OrderVoter::REFUND, $order)) {
            throw new AccessDeniedDomainException(
                sprintf('Refund not allowed for order %s', $command->orderId->value)
            );
        }

        $order->refund($command->amount);
        $this->orders->save($order);
    }
}
:::

Ukázka stojí na třech věcech, které kniha dál nerozvádí: `RefundOrderCommand`
se stejnou stavbou jako `CancelOrderCommand`, `Order::refund()` a `SecurityUserProvider`,
tenký repozitář nad `SecurityUser` s jedinou metodou `byCustomerId()`. Refundace tu slouží
jako druhý use case pro srovnání dvou přístupů k autorizaci, ne jako součást
objednávkového procesu; ten vrací platbu kompenzací v ságe.

Volbu mezi oběma variantami určuje povaha pravidla. Vlastnictví je vztah, který agregát zná sám, a porovnání `actorId` s `customerId` nepotřebuje ani Security komponentu, ani dotaz navíc. Jakmile pravidlo závisí na rolích, hierarchii rolí nebo atributech mimo agregát, vyplatí se `isGrantedForUser()` a pravidlo zapsané jen jednou – ve Voteru. Cenou je dotaz na aktéra a závislost aplikační vrstvy na Security komponentě, kterou už ale nese i synchronní handler.

Vzor má jeden trade-off. Mezi zařazením do fronty a zpracováním uplyne čas a oprávnění se mezitím mohla změnit: aktér přišel o roli, účet někdo zablokoval. Snapshot rolí přibalený do commandu proto slouží nanejvýš auditu; autoritativní je stav v okamžiku zpracování. Handler oprávnění ze zprávy nečte, ale ověřuje proti aktuálním datům: načte aktéra, nebo porovná vlastnictví, které se na rozdíl od rolí nemění.

Systémové procesy (cron, sága, batch) lidského aktéra nemají. Dostávají explicitní systémovou identitu s vlastním `actorId` a vyhrazenými právy, ne výjimku z kontroly typu „když aktér chybí, povol vše“. Taková podmínka je přesně ten fail-open default, před kterým varuje [sekce o multi-tenancy](#multi-tenancy).

Identita je definovaná na jednom místě. Kdyby ji sága a handler držely každý zvlášť, rozejdou se při první změně a chyba se projeví až v produkci:

:::code{language="php" filename="src/SharedKernel/Domain/SystemActor.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

/** Aktér pro procesy bez člověka: ságy, cron, batch. */
final class SystemActor
{
    public const ID = '01920000-0000-7000-8000-000000000001';
}
:::

Právo se té identitě musí explicitně udělit, jinak vlastní kompenzace neprojde. Sága pošle storno, handler porovná `actorId` s vlastníkem, neshodne se a příkaz po vyčerpání pokusů skončí v DLQ. Objednávka zůstane zaplacená a nezrušená, aniž by cokoli spadlo, a sága o odmítnutí příkazu neví. `CancelOrderHandler` výše proto testuje systémovou identitu ve vlastní větvi před kontrolou vlastnictví.

## 11.06 Aggregate-level – doména sama rozhoduje {#aggregate-level}

Některá pravidla do Voteru nepatří. Vyžadují znalost *doménového stavu*, kterou Voter nemá přebírat zvenku: časové okno, předchozí stav objednávky, invarianty napříč entitami uvnitř agregátu. Patří do **aggregate root**, který je vynutí *doménovou výjimkou*.

Praktická heuristika:

- Pravidlo, které vyžaduje *stav agregátu* („objednávka nesmí být odeslaná a nesmí být starší než 24 h“), patří do **agregátu**.
- Pravidlo o *vztahu* mezi aktérem a agregátem (vlastnictví, členství, hierarchie) definuje také **agregát**. Zná ho a nepřestává ho znát, ani když se command zpracuje asynchronně. Voter se na něj ptá, neopisuje ho.
- Pravidlo o *atributech aktéra* („refund smí jen `ROLE_REFUND_AGENT`“, „mimo pracovní dobu ne“) patří do **Voteru**. Agregát o rolích nic neví a vědět nemá.

Na prostředním bodě se týmy rozcházejí nejčastěji. „Zrušit smí jen vlastník“ zní jako typické use-case pravidlo, ve skutečnosti jde o invariant vztahu mezi `Order` a `CustomerId`. Agregát na něj proto odpovídá metodou `isOwnedBy()` a Voter i asynchronní handler ji volají místo vlastního porovnání. Definice zůstane jedna, vynucení může být na obou místech.

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (výřez)"}
<?php

// Výřez kanonického agregátu z kapitoly Návrh agregátu. Doplňuje jen to,
// co přidává autorizace; konstruktor, továrny ani mapování se nemění.
declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\Exception\CancellationWindowExpiredException;
use App\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Ordering\Domain\Exception\OrderLockedBySagaException;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\OrderStatus;
use App\SharedKernel\Domain\AggregateRoot;

class Order extends AggregateRoot
{
    private const CANCELLATION_WINDOW_SECONDS = 86_400; // 24 h

    public function cancel(string $reason, \DateTimeImmutable $when): void
    {
        // Zámek drží proces – viz kapitola o ságách, sekce Izolace ság.
        // Tahle podmínka je v kanonické verzi z Návrhu agregátu taky;
        // tenhle výpis metodu nahrazuje celou, ne po částech.
        if ($this->sagaInProgress) {
            throw new OrderLockedBySagaException($this->id);
        }

        // Odeslanou ani doručenou zásilku storno nevrátí – tam nastupuje
        // kompenzace v ságe.
        if (in_array($this->status, [OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            throw InvalidOrderStateTransitionException::cannotTransition(
                $this->status->value,
                OrderStatus::Cancelled->value,
            );
        }

        // Opakované storno není chyba volajícího, jen už není co dělat.
        if ($this->status === OrderStatus::Cancelled) {
            return;
        }

        // Lhůta běží od potvrzení. Draft ji ještě nemá a rozpracovaný
        // košík taky nikdo neruší na čas.
        if ($this->placedAt !== null) {
            $age = $when->getTimestamp() - $this->placedAt->getTimestamp();

            if ($age > self::CANCELLATION_WINDOW_SECONDS) {
                throw new CancellationWindowExpiredException(
                    $this->id,
                    $this->placedAt,
                    $when,
                );
            }
        }

        $this->status = OrderStatus::Cancelled;
        $this->record(new OrderCancelled(
            orderId:    $this->id,
            customerId: $this->customerId,
            reason:     $reason,
            occurredAt: $when,
        ));
    }

    public function isCancellable(\DateTimeImmutable $now): bool
    {
        // Šablona se ptá právě téhle metody, takže musí znát i zámek.
        // Jinak nabídne tlačítko, jehož příkaz skončí v dead-letter frontě.
        if ($this->sagaInProgress) {
            return false;
        }

        if (in_array($this->status, [
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ], true)) {
            return false;
        }

        return $this->placedAt === null
            || $now->getTimestamp() - $this->placedAt->getTimestamp()
               <= self::CANCELLATION_WINDOW_SECONDS;
    }

    // Vztahový invariant: vlastnictví zná agregát, ne Voter
    public function isOwnedBy(CustomerId $customerId): bool
    {
        return $this->customerId->equals($customerId);
    }
}
:::

Agregát nemá žádnou závislost na Symfony. Používá jen standardní typy PHP a vlastní doménové třídy: žádný `TokenInterface`, `AuthorizationChecker` ani `UserInterface`. Proto ho lze testovat unit testem bez Symfony Kernelu. Selhání hlásí výjimkami `InvalidOrderStateTransitionException` a `CancellationWindowExpiredException` z `App\Ordering\Domain\Exception`. Nesou doménový kontext (kdy byla objednávka potvrzena, kdy přišel pokus o storno) a aplikační vrstva je překládá na 409 Conflict, ne na 403 Forbidden. *Nejde o autorizační selhání, ale o doménový stav.*

Pomocná metoda `isCancellable()` je dotaz bez vedlejších efektů. UI podle ní skrývá tlačítko: Twig šablona ji volá s proměnnou `now` z controlleru a kombinuje ji s `is_granted`. Lhůtu sdílí s `cancel()` přes konstantu `CANCELLATION_WINDOW_SECONDS`, takže nevzniká duplicita. Zbývají doménové události: po úspěšné operaci agregát zaznamená `OrderCancelled` voláním `record()`, handler je po `repository->save()` vyzvedne přes `releaseEvents()` a publikuje (typicky přes [Outbox](/outbox-pattern)). Agregát sám `EventDispatcher` nikdy nevolá.

Otázku „smí Petr“ zde agregát **neřeší**; tu zodpověděl Voter v [sekci 11.04](#use-case-voter). Agregát odpovídá na *„dá se to teď vůbec udělat?“*, a jeho „ne“ platí i tehdy, když Voter řekl „ano“: Petr je vlastník, ale objednávka už odešla. Obě bariéry jsou nezávislé a obě nutné.

`cancel()` výše **nahrazuje** verzi z Návrhu agregátu celou, ne po částech: nese tytéž
stavové podmínky i zámek a přidává k nim lhůtu. `isCancellable()` je nová metoda, kterou
Návrh agregátu nemá. Storno lhůta je
jediné, co tahle kapitola k agregátu přidává; konstruktor, továrny i `markPaid()` zůstávají tak, jak je zavádí [Návrh agregátu](/navrh-agregatu#references-by-id). Stavová podmínka je proto stejná jako tam: blokuje odeslanou a doručenou objednávku, ne všechno kromě `Confirmed`. Zúžení na `Confirmed` by vypadalo přísněji, ale rozbilo by kompenzaci: sága ruší objednávku **zaplacenou**, handler by jí storno odmítl a objednávka by zůstala viset.

### End-to-end trace: cancellation request {#aggregate-trace-heading}

Co se stane, když zákazník Petr v rozhraní klikne na „Zrušit objednávku #42“:

1. **Edge (firewall).** Symfony ověří JWT nebo session. Bez ověření → 401. Petr je přihlášený, pokračuje se.
2. **Edge (access_control).** URL `/order/42/cancel` spadá pod `IS_AUTHENTICATED_FULLY`. Petr je přihlášený, pokračuje se.
3. **Controller** validuje vstup (CSRF token, tělo požadavku), vytvoří `CancelOrderCommand(orderId: 42, reason: 'changed mind', actorId: <Petrovo CustomerId>)` a předá ho na message bus.
4. **Application Handler** (`CancelOrderHandler`) načte agregát z repozitáře: `$order = $repo->get($orderId)`.
5. **Use Case.** Synchronní handler volá `$auth->isGranted('order.cancel', $order)` a `OrderVoter` se zeptá agregátu přes `$order->isOwnedBy($user->customerId())`; asynchronní varianta volá `isOwnedBy()` přímo s `actorId` z commandu. Petr je vlastník → pokračuje se. *Kdyby nebyl → `AccessDeniedDomainException` → HTTP 403.*
6. **Aggregate.** Handler volá `$order->cancel('changed mind', $now)`. Agregát ověří, že objednávka není odeslaná ani doručená a že od potvrzení neuplynulo víc než 24 h. Petr ji potvrdil před 30 minutami → stav se změní na `Cancelled` a vznikne událost `OrderCancelled`. *Kdyby už byla odeslaná → `InvalidOrderStateTransitionException` → HTTP 409.*
7. **Persistence + outbox.** Handler zavolá `$repo->save($order)`; v jedné transakci se uloží stav agregátu a událost `OrderCancelled` do outbox tabulky.
8. **Field-level (odpověď).** Controller přesměruje na detail objednávky. Kdyby detail obsahoval `audit_log` a Petr nebyl admin, read model by ho vynechal: u vlastní objednávky vidí stav, ale ne kdo a kdy ji upravoval.

Každá vrstva selže po svém: jiný HTTP status, jiná chybová hláška, jiné logy. Generické „Access denied“ nestačí.

:::callout{type="note"}
### 403 vs. 409: která chyba kdy? {#aggregate-403-vs-409-heading}

Drobnost s velkým dopadem na UX. Když řekne „ne“ Voter (Petr není vlastník), aplikace vrací **HTTP 403 Forbidden**: přihlášený uživatel nemá oprávnění. Když řekne „ne“ agregát (objednávka už odešla), jde o **HTTP 409 Conflict**: uživatel právo má, ale stav prostředku operaci nedovolí. Aplikační vrstva proto mapuje výjimky zvlášť: `AccessDeniedDomainException → 403`, `InvalidOrderStateTransitionException → 409`. UI pak může ukázat srozumitelnou hlášku („Objednávku už nelze stornovat, byla odeslána“) místo generického „Access denied“.

Překlad se ale neudělá sám. Bez posluchače vybublá doménová výjimka jako 500,
i když uživatel udělal všechno správně a jen se netrefil do lhůty.

Platí to jen pro **synchronní** zpracování. Jakmile se `CancelOrderCommand` v `messenger.yaml`
nasměruje na `async_commands` (kapitola o ságách to dělá), běží handler v jiném procesu.
Uživatel dostane přesměrování, protože v okamžiku odpovědi ještě nikdo neví, jak zpracování dopadne,
a výjimka skončí ve failed transportu. Je to legitimní kompromis, ne chyba. Odmítnutí pak ale
musí zjistit dřív ten, kdo formulář vykresluje: šablona volá `isCancellable()` a tlačítko
vůbec nenabídne.

:::code{language="php" filename="src/SharedKernel/Infrastructure/Http/DomainExceptionListener.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Http;

use App\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Ordering\Domain\Exception\CancellationWindowExpiredException;
use App\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Ordering\Domain\Exception\OrderNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

#[AsEventListener]
final readonly class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Synchronní sběrnice výjimku z handleru balí, takže se sem
        // dostane obálka, ne doménová výjimka.
        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious() ?? $exception;
        }

        $status = match (true) {
            $exception instanceof AccessDeniedDomainException => 403,
            $exception instanceof InvalidOrderStateTransitionException,
            $exception instanceof CancellationWindowExpiredException => 409,
            // Bez téhle větve vrátí detail neexistující objednávky 500,
            // zatímco sousední cesta s nevalidním tvarem ID vrací 404.
            // Rozdíl se pozná až v produkčních logech.
            $exception instanceof OrderNotFoundException => 404,
            default => null,
        };

        if ($status === null) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => $exception->getMessage()],
            $status,
        ));
    }
}
:::

Třetí volbou je **404 Not Found** místo 403. Odpověď 403 nad cizím identifikátorem potvrdí, že záznam existuje, a útočníkovi stačí projít rozsah ID, aby zmapoval cizí data. Symfony na to má `#[IsGranted(..., statusCode: 404)]`. Cenou je čitelnost chyby: uživatel, který o oprávnění přišel legitimně, uvidí „stránka neexistuje“ a nepozná proč. Rozumné dělení: 404 pro veřejné endpointy s uhodnutelnými identifikátory, 403 uvnitř administrace, kde jsou všichni aktéři důvěryhodní.
:::

## 11.07 Field-level – read model filtrace {#field-level}

Předchozí tři vrstvy řešily *akce* a *přípustnost* operace. Field-level řeší **viditelnost konkrétního pole** při jinak povoleném čtení. Klasický příklad: detail objednávky vidí zákazník i admin, ale sloupec `audit_log` (kdo a kdy objednávku upravoval) jen admin.

Nabízejí se dva přístupy s odlišnými kompromisy.

### Přístup 1: Twig if (view-level) {#field-twig-heading}

Nejjednodušší, ale s *únikem dat*: z databáze se načte všechno a ve view se část jen zahodí. Pro většinu UI to stačí, pro citlivá data ne – unikají přes HTML komentáře, JSON serializaci v JS aplikaci nebo ETag hashing.

:::code{language="twig" filename="templates/order/detail.html.twig (varianta nad read modelem)" highlights="7,8,9,10,11,12,13,14,15,16"}
{# Jiná varianta téže šablony než v 11.04. Tam čte agregát, zde
   OrderDetailDto z read modelu níže – obrazovka potřebuje audit log,
   který agregát nenese. Do projektu jde jedna z nich, ne obě. #}
<dl>
    <dt>Zákazník</dt> <dd>{{ order.customerId }}</dd>
    <dt>Celkem</dt>   <dd>{{ (order.totalAmount / 100)|number_format(2, ',', ' ') }} Kč</dd>
    <dt>Stav</dt>     <dd>{{ order.status }}</dd>

    {% if is_granted('order.audit_log', order) %}
        <dt>Audit log</dt>
        <dd>
            <ul class="audit">
                {% for entry in order.auditLog %}
                    <li>{{ entry.at|date }}: {{ entry.action }} ({{ entry.actor }})</li>
                {% endfor %}
            </ul>
        </dd>
    {% endif %}
</dl>
:::

### Přístup 2: Query filter (read model) {#field-query-heading}

Citlivá pole se z databáze *vůbec nenačtou* a read model vrací různá DTO podle role. Data neunikají, cenou je duplicita (dva dotazy, dvě struktury DTO). Hodí se pro PII, finanční data a audit logy.

:::code{language="php" filename="src/Ordering/Application/ReadModel/OrderDetailReadModel.php" highlights="16,17,18,19,20,21,22"}
<?php

// src/Ordering/Application/ReadModel/OrderDetailReadModel.php
declare(strict_types=1);

namespace App\Ordering\Application\ReadModel;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Ordering\Domain\Exception\OrderNotFoundException;
use App\Ordering\Domain\ValueObject\OrderId;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authorization\UserAuthorizationCheckerInterface;

final readonly class OrderDetailReadModel
{
    public function __construct(
        private Connection $db,
        private UserAuthorizationCheckerInterface $auth,
    ) {}

    public function forUser(string $orderId, SecurityUser $user): OrderDetailDto
    {
        // Read model čte projekci order_dashboard z kapitoly o CQRS, ne
        // tabulku agregátu. Celková částka je tam předpočítaná; nad `orders`
        // by se musela dopočítat joinem přes položky.
        $columns   = 'order_id, customer_id, total_amount, status, placed_at';
        $seesAudit = $this->auth->isGrantedForUser($user, 'ROLE_ADMIN');

        $sql = "SELECT {$columns} FROM order_dashboard WHERE order_id = :id";

        $row = $this->db->fetchAssociative($sql, ['id' => $orderId]);
        if ($row === false) {
            throw OrderNotFoundException::withId(OrderId::fromString($orderId));
        }

        // Audit log je vlastní tabulka, ne sloupec projekce – dotaz se
        // pro něj vůbec nepoloží, když ho aktér nesmí vidět.
        $row['audit_log'] = $seesAudit
            ? $this->db->fetchAllAssociative(
                'SELECT at, action, actor FROM order_audit_log
                  WHERE order_id = :id ORDER BY at',
                ['id' => $orderId],
            )
            : null;

        return OrderDetailDto::fromRow($row, includeAudit: $seesAudit);
    }
}
:::

Tabulku zakládá migrace. Do `schema_filter` z [kapitoly o CQRS](/cqrs#read-model-optimalizace)
patří ze stejného důvodu jako ostatní projekce, jinak ji `migrations:diff` navrhne zahodit:

:::code{language="sql" filename="migrations/Version20260906130000.php (výřez)"}
CREATE TABLE order_audit_log (
    id       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id UUID         NOT NULL,
    at       TIMESTAMP(0) NOT NULL,
    action   VARCHAR(64)  NOT NULL,
    actor    VARCHAR(255) NOT NULL
);

CREATE INDEX idx_audit_order ON order_audit_log (order_id, at);
:::

```yaml
# config/packages/doctrine.yaml – výčet je nutné rozšířit
schema_filter: '~^(?!order_dashboard|reporting_orders|order_audit_log)~'
```

DTO drží jen to, co obrazovka opravdu dostala. `auditLog` je `null`, pokud ho dotaz
nevybral. Oproti prázdnému poli to nese jiný význam: log se nenačetl, ne že by
neobsahoval žádné záznamy.

:::code{language="php" filename="src/Ordering/Application/ReadModel/OrderDetailDto.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\ReadModel;

final readonly class OrderDetailDto
{
    /** @param list<array<string, mixed>>|null $auditLog */
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $totalAmount,
        public string $status,
        public \DateTimeImmutable $placedAt,
        public ?array $auditLog = null,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row, bool $includeAudit): self
    {
        return new self(
            orderId:     (string) $row['order_id'],
            customerId:  (string) $row['customer_id'],
            totalAmount: (int) $row['total_amount'],
            status:      (string) $row['status'],
            placedAt:    new \DateTimeImmutable((string) $row['placed_at']),
            auditLog:    $includeAudit ? (array) ($row['audit_log'] ?? []) : null,
        );
    }
}
:::

Volba mezi přístupy:

| Kritérium | Twig if | Query filter |
|---|---|---|
| Data leak | Riziko (data v paměti; u API/SPA unikají do response) | Ne |
| Implementační složitost | Triviální | Vyžaduje různé DTO / read modely |
| Vhodné pro | UI hidden, neostrá ochrana | PII, finance, audit log, GDPR |
| Testování | Twig integrační test | Unit + integrační test read modelu |
| OWASP A01:2021 compliance | Insufficient – viz [[8]](https://owasp.org/Top10/A01_2021-Broken_Access_Control/) | Splňuje (server-side enforcement) |

Pro necitlivá data Twig if stačí a šetří čas, pro citlivá data vždy query filter. OWASP Top 10 v kategorii „A01 Broken Access Control“ výslovně varuje před kontrolou jen v UI jako jedinou bariérou.

### Seznamy: Voter na otázku „které objekty smí?“ neodpoví {#field-list-filtering}

Voter odpovídá na uzavřenou otázku: *smí tento uživatel tento konkrétní objekt?* Endpoint se seznamem ale potřebuje otázku opačnou: *které objekty z deseti tisíc smí vidět?* Rozdíl vypadá formálně, v praxi jde o strop celého rámce postaveného na Voterech.

Naivní řešení načte stránku výsledků a přefiltruje ji v PHP:

:::code{language="twig" filename="templates/order/list.html.twig (anti-vzor)"}
{# templates/order/list.html.twig (anti-vzor) #}
{% for order in orders %}
    {% if is_granted('order.view', order) %}
        <tr><td>{{ order.id.value }}</td><td>{{ order.status.value }}</td></tr>
    {% endif %}
{% endfor %}
:::

Rozbijí se dvě věci naráz. Stránkování přestane sedět: dotaz vrátí 20 řádků, filtr jich zahodí 7 a uživatel uvidí stránku o třinácti položkách. Celkový počet nikdo nespočítá, dokud nenačte všechno. Výkon navíc klesá lineárně: každý řádek projde rozhodováním přes všechny registrované Votery, takže dvacet řádků a pět Voterů znamená sto rozhodnutí na jedno vykreslení. Bez override `supportsAttribute()` a `supportsType()` (viz [implementační detaily Voteru](#use-case-voter)) se z toho počtu neubere nic.

Autorizace proto patří do dotazu. Read model dostane identitu aktéra a promítne ji do `WHERE`:

:::code{language="php" filename="src/Ordering/Application/ReadModel/OrderListReadModel.php" highlights="19,20,21,22,23"}
<?php

// src/Ordering/Application/ReadModel/OrderListReadModel.php
declare(strict_types=1);

namespace App\Ordering\Application\ReadModel;

use App\Ordering\Domain\ValueObject\CustomerId;
use Doctrine\DBAL\Connection;

final readonly class OrderListReadModel
{
    public function __construct(private Connection $db) {}

    /** @return list<array<string, mixed>> */
    public function visibleTo(CustomerId $actor, bool $isAdmin, int $limit, int $offset): array
    {
        $sql = 'SELECT order_id, status, total_amount, placed_at FROM order_dashboard';
        $params = ['limit' => $limit, 'offset' => $offset];

        // Autorizace je součástí dotazu, ne postprocessingu
        if (!$isAdmin) {
            $sql .= ' WHERE customer_id = :actor';
            $params['actor'] = $actor->value;
        }

        $sql .= ' ORDER BY placed_at DESC LIMIT :limit OFFSET :offset';

        return $this->db->fetchAllAssociative($sql, $params);
    }
}
:::

Podmínka ve `WHERE` je ale *druhý zápis* pravidla, které už zná `OrderVoter`. Jednu definici tu udržet nelze, protože SQL a PHP jsou různé jazyky; vazbu ale lze pojmenovat explicitně. Osvědčuje se držet obojí v jedné třídě nebo aspoň v jednom adresáři a doplnit komentář s odkazem na Voter. Hlavní pojistkou je test: projde objednávky vrácené read modelem a u každé ověří, že Voter řekne ano. Když se rozejdou, test spadne.

Modely vzniklé kolem Zanzibaru toto rozdělení pojmenovávají přímo: `Check` je otázka na jeden objekt, `ListObjects` vrací množinu. Pro druhou z nich Symfony 8 nativní podporu nemá. Voter je dobrý *Policy Enforcement Point* a víc si nenárokuje. Detail v [sekci o ReBAC](#rebac).

## 11.08 Policy-based přístup (ABAC) {#policy-based}

**RBAC** (Role-Based Access Control) se ptá na roli. **ABAC** (Attribute-Based Access Control) vyhodnocuje kombinaci atributů subjektu, akce, prostředku a kontextu proti policy a vrátí povoleno / zakázáno. O přechodu od prvního ke druhému nerozhoduje počet pravidel, ale tři kvalitativní signály: policy musí být čitelná pro někoho mimo vývojový tým, mění se v jiném rytmu než kód, nebo ji sdílí víc aplikací. Dokud neplatí ani jeden, Votery stačí a další abstrakce je jen práce navíc.

NIST SP 800-162 dává pro tuto vrstvu slovník, který používají i externí enginy [[2]](https://csrc.nist.gov/publications/detail/sp/800-162/final). **PEP** (Policy Enforcement Point) je místo, kde se rozhodnutí vynutí: v Symfony `access_control`, `#[IsGranted]` a volání `isGranted()` v handleru. Rozhodnutí samo padne v **PDP** (Policy Decision Point), u nás v rozhodovacím manažeru s Votery, případně ve vzdáleném enginu. **PIP** (Policy Information Point) dodává atributy, **PAP** (Policy Administration Point) policy spravuje. Rámec čtyř vrstev z [11.02](#ctyri-vrstvy) je tedy rozmístění PEP; PDP zůstává jeden.

Následující ukázka staví ABAC model explicitně: `Policy` jako kolekce objektů `Rule`, které se vyhodnotí proti trojici subject/user/context. Slouží k tomu, aby byl model vidět. Zda se takový kód vyplatí psát, řeší [závěr sekce](#abac-vlastni-vs-voter); ve většině Symfony projektů ne.

:::code{language="php" filename="src/SharedKernel/Authorization/Policy.php + Rule.php + PolicyContext.php"}
<?php

// src/SharedKernel/Authorization/Policy.php
declare(strict_types=1);

namespace App\SharedKernel\Authorization;

// Tři třídy, tři soubory – PSR-4 jinak najde jen tu první.
interface Policy
{
    public function name(): string;

    /** @return list<Rule> */
    public function rules(): array;
}

final readonly class Rule
{
    public function __construct(
        public string $expression,
        public string $description,
    ) {}
}

final readonly class PolicyContext
{
    public function __construct(
        public object $subject,
        public object $user,
        public \DateTimeImmutable $now,
    ) {}
}
:::

:::code{language="php" filename="src/Ordering/Authorization/CancelOrderPolicy.php" highlights="19,20,21,22,23,24,25,26,27,28,29,30,31,32,33"}
<?php

// src/Ordering/Authorization/CancelOrderPolicy.php
declare(strict_types=1);

namespace App\Ordering\Authorization;

use App\SharedKernel\Authorization\Policy;
use App\SharedKernel\Authorization\Rule;

final class CancelOrderPolicy implements Policy
{
    public function name(): string
    {
        return 'order.cancel';
    }

    /** @return list<Rule> */
    public function rules(): array
    {
        return [
            new Rule(
                // user.customerId je privátní – ExpressionLanguage k němu
                // getter nedohledá, volá se metoda.
                expression:  'subject.customerId == user.customerId()',
                description: 'Pouze vlastník objednávky',
            ),
            new Rule(
                expression:  'subject.status.value == "confirmed"',
                description: 'Objednávka musí být potvrzená',
            ),
            new Rule(
                expression:  'subject.placedAt.getTimestamp() >= now - 86400',
                description: 'Storno lhůta 24 h ještě neuplynula',
            ),
        ];
    }
}
:::

Zápis výrazů má svá úskalí a chyby se projeví až za běhu. ExpressionLanguage čte veřejné properties a volá veřejné metody. Gettery k privátním polím nedohledá, takže subjekt politiky musí mít stav čitelný zvenku: buď snapshot s veřejnými poli, nebo agregát s `public private(set)` jako kanonický `Order`. Odečíst `DateTimeImmutable` od čísla komponenta neumí: datum se převádí na unixový timestamp metodou objektu (`subject.placedAt.getTimestamp()`) a `now` přichází z proměnných evaluatoru jako číslo, ne jako objekt. Backed enum se neporovnává přímo: `subject.status == "confirmed"` selže, porovnává se až hodnota `subject.status.value`. A protože výrazy jsou řetězce, statická analýza je nevidí; každé pravidlo musí krýt test, viz [tabulkové testy policy](#testing-policy-heading).

Pravidla `subject.status.value == "confirmed"` a lhůta 24 h stojí v politice jen pro ilustraci ABAC zápisu. Jak popisuje sekce 11.06, doménové invarianty patří do agregátu a politika je ověřuje nanejvýš jako předběžnou kontrolu (obrana do hloubky). Zdrojem pravdy zůstává agregát, který neplatný příkaz odmítne i bez autorizační vrstvy.

Jednoduchý `PolicyEvaluator` vyhodnocuje pravidla v daném kontextu pomocí Symfony ExpressionLanguage. Balíček v základní instalaci není, prvním krokem je tedy `composer require symfony/expression-language`:

:::code{language="php" filename="src/SharedKernel/Authorization/PolicyEvaluator.php"}
<?php

// src/SharedKernel/Authorization/PolicyEvaluator.php
declare(strict_types=1);

namespace App\SharedKernel\Authorization;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class PolicyEvaluator
{
    public function __construct(private readonly ExpressionLanguage $expr = new ExpressionLanguage()) {}

    /**
     * Vrací první porušené pravidlo, nebo null pokud všechna prošla.
     */
    public function evaluate(Policy $policy, PolicyContext $ctx): ?Rule
    {
        $vars = [
            'subject' => $ctx->subject,
            'user'    => $ctx->user,
            'now'     => $ctx->now->getTimestamp(),
        ];
        foreach ($policy->rules() as $rule) {
            if (!$this->expr->evaluate($rule->expression, $vars)) {
                return $rule;
            }
        }
        return null;
    }
}
:::

Co tento přístup přináší a co stojí:

| | Policy nad ExpressionLanguage | Voter |
|---|---|---|
| Důvod zamítnutí | vrací porušené pravidlo | `Vote::addReason()` od Symfony 7.3 |
| Verzování | třída v repu, git historie | totéž |
| Statická analýza | výrazy jsou stringy, PHPStan je nevidí | plná |
| Subjekt | musí mít veřejně čitelný stav | libovolný objekt |
| Kompozice hlasů | vlastní kód | `AccessDecisionManager` a strategie |

Poslední dva řádky jsou skrytá cena, kterou tabulky výhod obvykle zamlčují. ExpressionLanguage čte jen veřejné properties. Agregát s privátním stavem proto subjektem politiky být nemůže a vzniká další model, který se musí držet v synchronizaci s doménou. Hlasy navíc skládá vlastní evaluátor místo rozhodovacího manažeru, takže strategie z [11.04](#access-decision) se neuplatní.

### Vlastní evaluátor, nebo Voter s `Vote`? {#abac-vlastni-vs-voter}

Pro vlastní `PolicyEvaluator` býval jediný silný argument: vědět, *které* pravidlo selhalo, ne jen že přístup nebyl povolen. Od Symfony 7.3 to umí Security komponenta sama. Voter přijímá volitelný parametr `?Vote $vote` a může do něj zapsat důvod. Aplikační vrstva pak čte celé rozhodnutí přes `Security::getAccessDecision()`, které přibylo ve verzi 7.4:

:::code{language="php" filename="src/Ordering/Infrastructure/Security/OrderVoter.php (výřez: voteOnAttribute s důvody)" highlights="10,15,20"}
// src/Ordering/Infrastructure/Security/OrderVoter.php (s důvody, jen větev CANCEL)
protected function voteOnAttribute(
    string $attribute,
    mixed $subject,
    TokenInterface $token,
    ?Vote $vote = null,
): bool {
    $user = $token->getUser();
    if (!$user instanceof SecurityUser) {
        $vote?->addReason('Aktér není přihlášený uživatel aplikace.');
        return false;
    }

    if (!$subject->isOwnedBy($user->customerId())) {
        $vote?->addReason('Objednávku smí zrušit pouze její vlastník.');
        return false;
    }

    if (!$subject->isCancellable(new \DateTimeImmutable())) {
        $vote?->addReason('Lhůta 24 h pro zrušení objednávky uplynula.');
        return false;
    }

    return true;
}
:::

Důvody se čtou z veřejné vlastnosti `$vote->reasons` (pole stringů); getter třída `Vote` nemá. Stejně je na tom `AccessDecision`, kde je výsledek vlastnost `$decision->isGranted`. Aplikační vrstva je vytáhne z `AccessDecision` a předá do chybové odpovědi:

:::code{language="php" filename="src/Ordering/Infrastructure/Http/ExplainedAccessDenied.php (výřez: tělo metody)"}
// src/Ordering/Infrastructure/Http/ExplainedAccessDenied.php
$decision = $this->security->getAccessDecision(OrderVoter::CANCEL, $order);

if (!$decision->isGranted) {
    $reasons = [];
    foreach ($decision->votes as $vote) {
        // Vote::$reasons je veřejná vlastnost, ne getter
        $reasons = array_merge($reasons, $vote->reasons);
    }

    throw new AccessDeniedDomainException(implode(' ', $reasons));
}
:::

Ukázka záměrně kontroluje i stav agregátu, aby bylo vidět, co se získá. Pravidlo ale zůstává definované v `Order::isCancellable()`; Voter ho volá, neopisuje.

Závěr pro Symfony 8: vlastní vrstva `Policy`/`Rule` se nevyplatí. Dá tytéž odpovědi jako Votery, ale bez statické analýzy, bez rozhodovacích strategií a s modelem navíc. ABAC zůstává užitečný jako *způsob uvažování* o pravidlech, implementace ale vzniká z Voterů. Externí engine přichází na řadu, až když policy musí žít mimo aplikaci: sdílí ji víc služeb, spravuje ji jiný tým nebo ji auditor kontroluje nezávisle na deploy cyklu. Tehdy se nabízí OPA s jazykem Rego nebo Cerbos a Voter se stane tenkým PEP, který se ptá vzdáleného PDP. Rozhraní mezi nimi standardizuje AuthZEN Authorization API 1.0, schválené v lednu 2026 [[9]](https://openid.net/wg/authzen/).

### ReBAC: když je oprávnění vztah, ne atribut {#rebac}

OPA není poslední stanice. Posun posledních let míří k **ReBAC** (Relationship-Based Access Control), kde se přístup odvozuje ze vztahů mezi uživateli a objekty *i mezi objekty navzájem*. Typické pravidlo: „uživatel vidí dokument, pokud má přístup k jeho nadřazené složce“. Na hierarchiích, sdílení a multi-tenancy RBAC selhává: buď vznikají role pro každou kombinaci, nebo se logika roztříští do Voterů.

Referenčním modelem je **Zanzibar**, autorizační systém Googlu popsaný na USENIX ATC '19. Ukládá vztahy jako trojice `objekt#relace@uživatel`, konfiguraci vztahů popisuje vlastním jazykem namespace a konzistenci řeší tokeny zvanými zookies. Provozní čísla z paperu dávají měřítko: biliony ACL záznamů, miliony autorizačních dotazů za sekundu, p95 latence pod 10 ms. Otevřené implementace téhož modelu jsou dnes dvě: **OpenFGA** (projekt CNCF) a **SpiceDB** od Authzed se schema jazykem, který rozlišuje zapsané vztahy a počítaná oprávnění.

:::callout{type="warn"}
### PHP klienta prakticky nemáte {#rebac-php-heading}

Ekosystém kolem ReBAC zatím PHP vynechává. Oficiální SDK OpenFGA existují pro Node.js, Go, .NET, Python a Javu; PHP mezi nimi není. Komunitní `evansims/openfga-php` je na Packagistu označený jako abandoned. Pro SpiceDB žádný PHP balíček není. Cerbos má oficiální `cerbos/cerbos-sdk-php`, ale s adopcí v řádu tisíců stažení. Kdo chce v Symfony projektu ReBAC engine, napíše si HTTP nebo gRPC klienta a bude ho sám udržovat. Předtím se vyplatí ověřit, jestli problém opravdu vyžaduje graf vztahů, nebo jestli stačí tabulka vazeb a Voter, který se jí ptá.
:::

:::callout{type="pattern"}
### RBAC vs. ABAC: kdy přejít? {#abac-vs-rbac-heading}

RBAC stačí, dokud platí *„role popisuje oprávnění sama o sobě“*: admin smí všechno, zákazník smí svoje, refund agent smí refundy. Jakmile oprávnění závisí na *vztazích mezi entitami* (tenant, vlastnictví, časové okno, stavový automat), počet rolí se vymkne kontrole. Buď vznikají úzce specifické role typu `ROLE_TENANT_42_ORDER_REFUND_AGENT`, nebo Votery s 200 řádky if-else. Tehdy je čas uvažovat v ABAC pojmech, tedy v atributech místo rolí. Implementace v Symfony 8 přitom zůstává ve Voterech; vlastní policy vrstva se nevyplatí, dokud policy nemusí žít mimo aplikaci.
:::

## 11.09 Multi-tenancy – tenant kontext {#multi-tenancy}

Multi-tenancy (vícenájemnost) je speciální případ ABAC: jedna aplikace obsluhuje více *oddělených zákazníků* (organizací, mandantů, tenantů) a žádný nesmí vidět data jiného. Architektonické strategie jsou tři:

- **Row-based** – sdílená databáze, sdílené tabulky, sloupec `tenant_id` všude. Nejlevnější, nejméně izolace, vyžaduje pečlivé filtry.
- **Schema-based** – sdílená databáze, samostatné schema per tenant (PostgreSQL `SET search_path`). Střední izolace. Výkon závisí na počtu tenantů: menší tabulky pomohou velkým tenantům, tisíce schémat ale zatíží katalog, migrace i connection pooling.
- **Database-based** – samostatná databáze per tenant. Nejvyšší izolace, nejnákladnější (DB connection per tenant, migrations × N).

Volba mezi nimi souvisí s velikostí instalace. Row-based se hodí pro SaaS s velkým počtem malých tenantů, schema-based tam, kde se mají oddělit zálohy a migrace per tenant, database-based pro regulované domény. Kritérium je pokaždé stejné: jak drahá je chyba, když se dva tenanty potkají v jedné odpovědi. Pro row-based je v Symfony zavedeným nástrojem **Doctrine SQLFilter**.

To vypadá jako rozpor s [chybou 3](#tri-chyby-doctrine-heading), která filtrování v perzistentní vrstvě označila za anti-vzor. Rozpor je jen zdánlivý a rozdíl spočívá v tom, na co filtr odpovídá. Tenant je **kontext dotazu**, ne autorizační rozhodnutí o akci: dimenze, kterou nese každý dotaz v požadavku, stejně jako jazyk nebo časová zóna. Rozhodnutí „Petr smí zrušit objednávku #42“ do SQL nepatří, protože handler pak nerozezná neexistující záznam od cizího. Otázka „ke kterému tenantovi tento request patří“ do SQL patří, protože odpověď je pro celý request jediná a neměnná.

:::code{language="php" filename="src/SharedKernel/Infrastructure/Doctrine/TenantFilter.php" highlights="13,14,15,16,17,18,19,20,21,22"}
<?php

// src/SharedKernel/Infrastructure/Doctrine/TenantFilter.php
declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Doctrine;

use App\SharedKernel\Domain\TenantAware;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class TenantFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$targetEntity->reflClass->implementsInterface(TenantAware::class)) {
            return '';
        }

        return sprintf(
            '%s.tenant_id = %s',
            $targetTableAlias,
            $this->getParameter('tenant_id'),
        );
    }
}
:::

Marker rozhraní je prázdné a říká jen „tahle entita patří tenantovi“. Bez něj filtr
spadne u prvního dotazu na `Interface … does not exist`, a protože je zapnutý globálně,
shodí každý dotaz v aplikaci:

:::code{language="php" filename="src/SharedKernel/Domain/TenantAware.php"}
<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

/**
 * Značka pro entity, které patří konkrétnímu tenantovi. Metody nemá –
 * filtr se ptá jen na to, jestli ji entita implementuje.
 */
interface TenantAware
{
}
:::

Filtr přidá klauzuli `tenant_id = ?` do každého dotazu nad entitou, která implementuje `TenantAware`. Dokud ho neimplementuje žádná, je filtr no-op: zapnutý, ale bez účinku. Značka nepatří na `SecurityUser`. Toho načítá provider **uvnitř** firewallu, tedy dřív, než listener stihne nastavit parametr, a přihlášení by spadlo na `Parameter 'tenant_id' does not exist`. Tenantní jsou doménové entity za firewallem, ne třída, kterou firewall používá k autentizaci. Aktivace filtru v `config/packages/doctrine.yaml`:

:::code{language="yaml" filename="config/packages/doctrine.yaml (výřez: filtr tenanta)"}
# config/packages/doctrine.yaml
doctrine:
    orm:
        filters:
            tenant:
                class:   App\SharedKernel\Infrastructure\Doctrine\TenantFilter
                enabled: true  # fail-closed: filter běží vždy, parametr dodá listener
:::

Pozor na výchozí stav. Vypnutý nebo nenakonfigurovaný filtr nepřidá do SQL žádné WHERE a dotaz vrátí data všech tenantů. SQLFilter je ze své podstaty *fail-open* a v tom je hlavní riziko celého přístupu. Konfigurace výše proto filtr zapíná globálně (`enabled: true`): běží pro každý dotaz a chybějící `tenant_id` skončí výjimkou, ne únikem dat.

Jedna mezera zůstane i pak. Filtr nepokrývá načtení **neowning strany asociace one-to-one**.
Měřeno na ORM 3.6 vrátí druhý konec vztahu i záznam cizího tenanta, přestože přes `find()`
nebo DQL týž záznam nedostanete. Kde na oddělení tenantů závisí bezpečnost, patří kontrola
tenanta i do doménové vrstvy, ne jen do filtru.

Hodnotu parametru dodává kernel event listener po autentizaci:

:::code{language="php" filename="src/SharedKernel/Infrastructure/Http/TenantContextListener.php" highlights="13,22,23,24,25,26,27,28,29,30,31,32,33,34"}
<?php

// src/SharedKernel/Infrastructure/Http/TenantContextListener.php
declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Http;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 7)]
final readonly class TenantContextListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokens,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokens->getToken();
        $user  = $token?->getUser();
        if ($user === null || !method_exists($user, 'tenantId')) {
            return; // public endpoint, anonymous request
        }

        $tenantId = $user->tenantId()->value;
        $filter   = $this->em->getFilters()->enable('tenant');
        $filter->setParameter('tenant_id', $tenantId);
    }
}
:::

Volání `enable('tenant')` v listeneru je u globálně zapnutého filtru neškodné: vrátí existující instanci, na kterou stačí nastavit parametr.

Tři detaily:

- **Priorita 7** v `AsEventListener`. V Symfony platí *vyšší priorita = dřívější vykonání*. Firewall registruje svůj `onKernelRequest` s prioritou 8, takže listener, který potřebuje už autentizovaného uživatele, musí mít prioritu *nižší než 8* (typicky 7 nebo 0). Detail v [dokumentaci EventDispatcheru](https://symfony.com/doc/current/event_dispatcher.html).
- **Main request guard.** Bez `$event->isMainRequest()` by se filtr nastavoval i pro dílčí požadavky (ESI, render fragments), kde token obvykle není a listener by spadl.
- **Anonymní požadavek parametr nedostane.** U veřejných endpointů (login, register, health) listener skončí na guardu a `tenant_id` zůstane nenastavené. První dotaz nad `TenantAware` entitou pak vyhodí výjimku, protože globálně zapnutý filtr bez parametru dotaz nepustí. Hlučné selhání je tu záměr: veřejný endpoint nemá tenantní data co číst. Pokud je přesto čte, patří požadavek odmítnout už na firewallu.

:::callout{type="warn"}
### Fail-closed se musí vyrobit, samo nevznikne {#multi-tenancy-fail-open-heading}

Častý omyl: „bez aktivního filtru se tenantní data prostě nevrátí“. Ve skutečnosti se vrátí *všechna*, napříč tenanty. Fail-closed chování stojí na třech opatřeních. Filtr běží globálně (`enabled: true`), ne až po aktivaci v listeneru, takže zapomenutá aktivace neznamená únik dat, ale výjimku. Parametr `tenant_id` je povinný: Doctrine ho při sestavování dotazu vyžaduje a bez něj selže. A požadavek bez známého tenanta (anonymní request, CLI command, Messenger worker) má skončit dřív, na firewallu nebo v listeneru. Kde odmítnutí nejde, poslouží nemožná hodnota `tenant_id`, které neodpovídá žádný řádek. CLI a worker procesy kernel listener neobslouží; tenant context tam nastavuje Messenger middleware nebo samotný command, jinak první dotaz spadne.
:::

:::callout{type="warn"}
### Pozor: filtr se neuplatní na nativní SQL ani Redis {#multi-tenancy-warn-heading}

Doctrine SQLFilter upravuje SQL generované ORM: DQL/QueryBuilder, `EntityManager::find()` i lazy loading kolekcí. Na `$conn->executeQuery('SELECT ...')`, Redis, Elasticsearch ani externí HTTP API *se žádný filtr neuplatní* a `tenant_id` tam musíte přidat ručně. V code review je proto potřeba hlídat surové SQL bez `tenant_id` ve `WHERE`. Takové dotazy umí odhalit i statická analýza (vlastní PHPStan pravidlo nebo PHPArkitect).

Filtr neúčinkuje ani na entity, které už leží v identity map – ty se vracejí tak, jak je EntityManager načetl, dokud ho někdo nevyčistí. Pro dočasné vypnutí (admin dotaz, migrace, cross-tenant report) použijte `suspend()` a `restore()`, ne `disable()`. `disable()` zahodí celou instanci filtru včetně nastavených parametrů a po `enable()` je musí někdo nastavit znovu; zapomenutý parametr pak shodí první dotaz, v horším případě běží kód dál bez izolace.
:::

### PostgreSQL Row-Level Security {#rls}

Díra popsaná výše má u PostgreSQL řešení o patro níž. **Row-Level Security** posouvá filtrování do databáze, takže platí i pro nativní SQL, pro konzolové skripty i pro připojení mimo aplikaci:

:::code{language="sql" filename="migrations/tenant_rls.sql"}
-- migrations/tenant_rls.sql
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
-- Bez FORCE se politika neuplatní na vlastníka tabulky
ALTER TABLE orders FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON orders
    USING      (tenant_id = current_setting('app.tenant_id', true))
    WITH CHECK (tenant_id = current_setting('app.tenant_id', true));
:::

Aplikace pak před dotazy nastaví proměnnou spojení příkazem `SET app.tenant_id = '…'`, a to ve stejném listeneru, který plní Doctrine filtr. Proti SQLFilteru se RLS liší výchozím chováním, a to rozhoduje. Po `ENABLE ROW LEVEL SECURITY` platí na tabulce default-deny: bez politiky se nevrátí nic. SQLFilter je naopak fail-open a fail-closed chování se musí vyrobit ručně, jak popisuje předchozí callout. Cenou za RLS je vázanost na PostgreSQL, obtížnější ladění (dotaz vrátí prázdno a nikde není proč) a role s atributem `BYPASSRLS`, kterou potřebují migrace a zálohy. Obě vrstvy se nevylučují: filtr drží čitelné chování v ORM, RLS je poslední záchytná síť.

## 11.10 Test pyramida pro autorizaci {#testing}

Každá ze čtyř vrstev se testuje jiným druhem testu. Kdo se snaží pokrýt všechno end-to-end, skončí u pomalé a křehké sady. Dělení odpovídá klasické *testovací pyramidě*: hodně rychlých unit testů, méně integračních, pár end-to-end.

### Aggregate-level: čistý unit test {#testing-aggregate-heading}

Doménová pravidla v agregátu jsou čisté PHP bez frameworku a databáze, takže test je rychlý a deterministický.

Testy sahají po `OrderFactory`, jednoduchém test-data builderu, který drží sestavení
agregátu na jednom místě. Vyplatí se, jakmile ho potřebuje víc testovacích souborů:

:::code{language="php" filename="tests/Ordering/Domain/OrderFactory.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Domain;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\ProductId;
use App\Shipping\Domain\ValueObject\ShipmentId;
use App\SharedKernel\Domain\Currency;
use App\SharedKernel\Domain\Money;

final class OrderFactory
{
    private const AT = '2026-04-29 10:00:00';

    public static function placed(
        string $at = self::AT,
        ?CustomerId $customerId = null,
    ): Order {
        return self::build($customerId ?? CustomerId::generate(), $at);
    }

    public static function placedFor(CustomerId $customerId): Order
    {
        return self::build($customerId, self::AT);
    }

    // Vlastník je parametr i zde. Bez něj by testy policy hlásily
    // porušení vlastnictví místo pravidla, které chtěly ověřit.
    public static function shipped(?CustomerId $customerId = null): Order
    {
        $order = self::build($customerId ?? CustomerId::generate(), self::AT);
        $order->markPaid();
        $order->ship(ShipmentId::generate());

        return $order;
    }

    /**
     * Builder jde přes veřejné API agregátu, ne přes reflexi. Konstruktor
     * je privátní a stav se mění jen přechody – kdyby si test sahal dovnitř,
     * přestal by hlídat právě ta pravidla, kvůli kterým existuje.
     */
    private static function build(CustomerId $customerId, string $at): Order
    {
        // Poslední parametr je čas potvrzení. Bez něj by se scénář
        // „potvrzeno v 10:00, stornováno ve 12:00“ nedal postavit jinak
        // než reflexí – a test by přestal hlídat pravidla agregátu.
        $order = Order::placeWithFirstItem(
            $customerId,
            ProductId::generate(),
            1,
            new Money(10_000, Currency::CZK),
            new \DateTimeImmutable($at),
        );

        $order->releaseEvents(); // fronta událostí patří testu, ne továrně

        return $order;
    }
}
:::

:::code{language="php" filename="tests/Ordering/Domain/OrderCancelTest.php"}
<?php

// tests/Ordering/Domain/OrderCancelTest.php
declare(strict_types=1);

namespace App\Tests\Ordering\Domain;

use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\Exception\CancellationWindowExpiredException;
use App\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Ordering\Domain\Model\Order;
use PHPUnit\Framework\TestCase;

final class OrderCancelTest extends TestCase
{
    public function testCancelWithinWindowSucceeds(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');
        $order->releaseEvents(); // vyprázdní eventy z fáze vytvoření

        $order->cancel('changed mind', new \DateTimeImmutable('2026-04-29 12:00:00'));

        // Stav se ověří přes chování: úspěšný cancel zaznamená OrderCancelled
        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderCancelled::class, $events[0]);
    }

    public function testCancelOfShippedOrderThrows(): void
    {
        $order = OrderFactory::shipped();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->cancel('changed mind', new \DateTimeImmutable());
    }

    public function testCancelAfter24hThrows(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');

        $this->expectException(CancellationWindowExpiredException::class);
        $order->cancel('too late', new \DateTimeImmutable('2026-04-30 11:00:00'));
    }
}
:::

### Voter: unit test s mock TokenInterface {#testing-voter-heading}

Voter dostává `TokenInterface`; v testu stačí jeho mock, reálný subject a mock rozhodovacího manažeru pro role. Žádný Symfony Kernel:

:::code{language="php" filename="tests/Ordering/Infrastructure/Security/OrderVoterTest.php"}
<?php

// tests/Ordering/Infrastructure/Security/OrderVoterTest.php
declare(strict_types=1);

namespace App\Tests\Ordering\Infrastructure\Security;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Infrastructure\Security\OrderVoter;
use App\Tests\Identity\SecurityUserFixture;
use App\Tests\Ordering\Domain\OrderFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoterTest extends TestCase
{
    // Identifikátory jsou UUID – CustomerId jinou hodnotu nepřijme.
    private const OWNER    = '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03';
    private const STRANGER = '02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746';

    public function testOwnerCanCancelOwnOrder(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString(self::OWNER));

        self::assertSame(
            Voter::ACCESS_GRANTED,
            $this->voteCancel($order, actor: self::OWNER)
        );
    }

    public function testStrangerCannotCancelOrder(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString(self::OWNER));

        self::assertSame(
            Voter::ACCESS_DENIED,
            $this->voteCancel($order, actor: self::STRANGER)
        );
    }

    private function voteCancel(object $order, string $actor): int
    {
        // Bez očekávání jde o stuby, ne mocky – createMock() by na PHPUnit 13
        // hlásil „No expectations were configured“ a ve 14 přestane fungovat.
        $decisions = $this->createStub(AccessDecisionManagerInterface::class);
        $decisions->method('decide')->willReturn(false); // aktér nemá žádnou roli navíc

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(SecurityUserFixture::for($actor));

        return (new OrderVoter($decisions))->vote($token, $order, [OrderVoter::CANCEL]);
    }
}
:::

:::code{language="php" filename="tests/Identity/SecurityUserFixture.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Identity;

use App\Identity\Infrastructure\Security\SecurityUser;

final class SecurityUserFixture
{
    /** Aktér pro test Voteru. Zajímá ho jen customerId, zbytek je výplň. */
    public static function for(string $customerId, string ...$roles): SecurityUser
    {
        // E-mail je primární klíč, takže musí být pro každého aktéra jiný –
        // dvě fixture se stejným by při ukládání kolidovaly.
        return new SecurityUser(
            email: $customerId . '@example.test',
            passwordHash: 'irrelevant',
            roles: $roles ?: ['ROLE_USER'],
            customerId: $customerId,
            tenantId: 'tenant-test',
        );
    }
}
:::

Stub `AccessDecisionManagerInterface` vrací záměrně `false`. Test tak ověřuje vlastnictví bez vlivu rolí; pro scénář s adminem stačí druhý test s návratovou hodnotou `true`.

### End-to-end: WebTestCase {#testing-e2e-heading}

Celou pipeline (firewall → controller → handler → Voter → agregát) pokrývá Symfony `WebTestCase`. To už je integrační test s kernelem a databází. Rozumná míra: *jeden e2e test na use case*, který pokryje hlavní scénář a jeden až dva nejdůležitější chybové stavy. Okrajové případy patří do unit testů na nižších vrstvách.

Přihlášení v takovém testu neprobíhá přes formulář. `KernelBrowser::loginUser()` vloží uživatele rovnou do session a ušetří jeden request i závislost na podobě login stránky. Jednu vazbu ale neodstraní: s `entity` providerem firewall uživatele při každém dalším requestu načítá znovu, takže fixture musí být v databázi. Jinak test skončí přesměrováním na `/login` a tváří se jako chyba autorizace.

:::code{language="php" filename="tests/Ordering/Http/CancelOrderE2eTest.php" highlights="12,13,14,17"}
<?php

// tests/Ordering/Http/CancelOrderE2eTest.php
declare(strict_types=1);

namespace App\Tests\Ordering\Http;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Tests\Identity\SecurityUserFixture;
use App\Tests\Ordering\Domain\OrderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CancelOrderE2eTest extends WebTestCase
{
    private const OWNER    = '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03';
    private const STRANGER = '02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746';

    protected function setUp(): void
    {
        // Fixture se zapisuje do databáze, takže se musí uklidit – jinak
        // druhý běh spadne na unique indexu, ne na testovaném chování.
        // Pohodlnější alternativa je dama/doctrine-test-bundle, který
        // každý test obalí transakcí a na konci ji vrátí zpět.
        $connection = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();

        foreach (['order_items', 'orders', 'app_user'] as $table) {
            $connection->executeStatement('DELETE FROM ' . $table);
        }

        // getContainer() kernel nabootuje, ale createClient() ho chce
        // nastartovat znovu. Bez tohohle řádku test spadne na
        // „Booting the kernel before calling createClient() is not supported“.
        self::ensureKernelShutdown();
    }

    public function testStrangerGetsNotFound(): void
    {
        $client = static::createClient();
        $order  = $this->givenOrderOf(self::OWNER);

        // loginUser() vloží uživatele do session, ale při dalším requestu
        // ho firewall obnovuje přes entity provider – uživatel proto musí
        // v databázi být, jinak se token zahodí a test skončí na /login.
        $stranger = SecurityUserFixture::for(self::STRANGER);
        $container = static::getContainer();
        $container->get(EntityManagerInterface::class)->persist($stranger);
        $container->get(EntityManagerInterface::class)->flush();

        $client->loginUser($stranger);
        $client->request('POST', '/order/' . $order->id->value . '/cancel');

        // #[IsGranted(..., statusCode: 404)] brání enumeraci cizích ID
        self::assertResponseStatusCodeSame(404);
    }

    private function givenOrderOf(string $customerId): Order
    {
        $container = static::getContainer();
        $order = OrderFactory::placedFor(CustomerId::fromString($customerId));

        $container->get(OrderRepository::class)->save($order);
        $container->get(EntityManagerInterface::class)->flush();

        return $order;
    }
}
:::

### Architektonický test: doména bez Security komponenty {#testing-architecture-heading}

Anti-vzor 4 zakazuje závislost domény na `Symfony\Component\Security`. Pravidlo, které hlídá jen code review, se dřív nebo později poruší, proto ho vynucuje test. S PHPArkitect stačí jedno pravidlo:

:::code{language="php" filename="tests/Architecture/DomainRules.php (výřez: jedno pravidlo)"}
// tests/Architecture/DomainRules.php
Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
    ->should(new NotDependsOnTheseNamespaces('Symfony\Component\Security'))
    ->because('doménový model nesmí znát autorizační infrastrukturu');
:::

Test běží v CI vedle unit testů a selže při prvním nepovoleném importu, ne až při refaktoringu za rok. Pyramidu, fixture buildery i další architektonická pravidla rozebírá [kapitola o testování](/testovani-ddd).

### Policy: tabulkový unit test {#testing-policy-heading}

U [policy-based přístupu](#policy-based) je každé pravidlo jeden test case. Vyhodnocení výrazů stojí na balíčku `symfony/expression-language`, který v základní instalaci není. Nejlépe poslouží tabulkový test s data providerem: jeden řádek = jeden scénář, čitelný i pro netechnického reviewera:

:::code{language="php" filename="tests/Ordering/Authorization/CancelOrderPolicyTest.php"}
<?php

// tests/Ordering/Authorization/CancelOrderPolicyTest.php
declare(strict_types=1);

namespace App\Tests\Ordering\Authorization;

use App\Ordering\Authorization\CancelOrderPolicy;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\SharedKernel\Authorization\PolicyContext;
use App\SharedKernel\Authorization\PolicyEvaluator;
use App\Tests\Identity\SecurityUserFixture;
use App\Tests\Ordering\Domain\OrderFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CancelOrderPolicyTest extends TestCase
{
    private const OWNER    = '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03';
    private const STRANGER = '02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746';

    public static function scenarios(): iterable
    {
        yield 'happy path' => [
            'subject'  => OrderFactory::placedFor(CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::OWNER),
            'expected' => null,
        ];
        yield 'wrong customer' => [
            'subject'  => OrderFactory::placedFor(CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::STRANGER),
            'expected' => 'Pouze vlastník objednávky',
        ];
        yield 'shipped order' => [
            'subject'  => OrderFactory::shipped(CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::OWNER),
            'expected' => 'Objednávka musí být potvrzená',
        ];
        yield 'window expired' => [
            'subject'  => OrderFactory::placed('2026-04-28 09:00:00', CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::OWNER),
            'expected' => 'Storno lhůta 24 h ještě neuplynula',
        ];
    }

    #[DataProvider('scenarios')]
    public function testEvaluate(object $subject, object $user, ?string $expected): void
    {
        $evaluator = new PolicyEvaluator();
        // Čas vyhodnocení je pevný, jinak by scénář se lhůtou po roce
        // začal padat sám od sebe.
        $context = new PolicyContext($subject, $user, new \DateTimeImmutable('2026-04-29 12:00:00'));

        $violation = $evaluator->evaluate(new CancelOrderPolicy(), $context);

        self::assertSame($expected, $violation?->description);
    }
}
:::

Oproti testu s metodou na každý případ má tabulka dvě výhody. Nové pravidlo znamená jeden řádek navíc v `scenarios()`. A celý test slouží jako *spustitelná dokumentace policy*: reviewer mimo vývojový tým vidí všechny případy v jedné tabulce a může doménová pravidla schválit.

## 11.11 Anti-vzory {#antivzory}

Čtyři anti-vzory mají strukturu „symptom – důsledek – náprava“. První dva shrnují, co kapitola už rozebrala, aby šel v code review projít celý seznam na jednom místě.

### Anti-vzor 1: Autorizace v controlleru {#anti-controller-heading}

Rozebírá ho sekce [11.01](#tri-chyby). Symptom: stejná autorizační podmínka opakovaná ve třech a více controllerech, která chybí ve verzích volaných z konzolového commandu nebo Messenger handleru. Náprava: přesun do Voteru a volání `AuthorizationCheckerInterface` v Application Service. Souvisí: [obecné anti-vzory v DDD](/anti-vzory).

### Anti-vzor 2: Voter, který si načte vlastní subjekt {#anti-fetching-voter-heading}

Rozebráno v [calloutu u Voteru](#voter-anti-fetching-heading). Symptom: konstruktor Voteru přijímá repository a `voteOnAttribute()` volá `find($subject)` nad ID, které dostal místo objektu. Důsledek: druhý dotaz na tutéž entitu a rozhodování nad stavem, který se mezitím mohl změnit. Náprava: handler načte entitu jednou a předá ji do `isGranted()`. Doplňková data mimo subjekt (členství, delegace) si Voter načíst smí; zákaz míří na subjekt, ne na všechny dotazy.

### Anti-vzor 3: Voter == Aggregate logic {#anti-duplication-heading}

Symptom: storno lhůta („objednávka ne starší než 24 h“) je zapsaná *jak* ve Voteru, *tak* v `Order::cancel()`. Když se pravidlo změní (lhůta se prodlouží na 48 h), musí se upravit obě místa a na jedno se typicky zapomene.

Náprava: pravidlo patří do agregátu, protože jde o doménový invariant. Voter doménový stav agregátu **neověřuje**; odpovídá jen na identitu a role uživatele a na vlastnictví subjektu. Pro skrytí tlačítka ve view se v Twigu kombinuje `{% if is_granted(...) and order.isCancellable(now) %}`: Voter pro oprávnění, doménová metoda pro stav.

### Anti-vzor 4: Symfony User natažený do doménového Aggregate {#anti-symfony-user-domain-heading}

Symptom:

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (anti-vzor)" highlights="5,10,11,12"}
<?php

// src/Ordering/Domain/Model/Order.php (anti-vzor)
namespace App\Ordering\Domain\Model;

use Symfony\Component\Security\Core\User\UserInterface;

final class Order
{
    // Anti-vzor: doména závisí na Symfony Security komponentě
    public function cancel(UserInterface $user, string $reason): void
    {
        if ($user->getUserIdentifier() !== $this->customerEmail) {
            throw new \DomainException('Not your order');
        }
        // ...
    }
}
:::

Technický důsledek: doména závisí na `Symfony\Component\Security`, takže stejný kód nespustíte z konzolového commandu, z Messenger workeru ani z unit testu bez Kernelu. Modelový důsledek váží víc. Role a oprávnění jsou slovník *jiné* subdomény, Identity & Access kontextu z [11.02](#ctyri-vrstvy). Jakmile se objeví v `Order::cancel()`, mluví Ordering kontext cizím ubiquitous language a hranice mezi kontexty se stírá.

Náprava: doména pracuje s vlastním typem (`CustomerId`, `TenantId`), aplikační handler překládá `SecurityUser` na doménový identifikátor a vynucuje to [architektonický test](#testing-architecture-heading). Opačný směr téhož porušení vrstev, tedy doménovou logiku v infrastruktuře, rozebírá [kapitola o anti-vzorech](/anti-vzory#logika-v-infrastrukture).

:::callout{type="warn"}
### Společný jmenovatel anti-vzorů {#anti-summary-heading}

Všechny čtyři anti-vzory mají stejnou příčinu: *autorizační rozhodnutí skončilo na nesprávné vrstvě*. S čtyřvrstvým rámcem z [11.02](#ctyri-vrstvy) na očích je code review odhalí na první pohled.
:::

## 11.12 Shrnutí {#summary}

Autorizace v DDD aplikaci na Symfony 8 sedí na čtyřech vrstvách, každá s vlastním Symfony nástrojem a vlastní granularitou:

- **Edge** – Symfony firewall + `access_control`. Anonymní vs. přihlášený, hrubé dělení podle rolí. Žádná doménová znalost.
- **Use Case** – Symfony Voter. „Smí Petr zrušit objednávku #42?“ Aplikační handler volá `AuthorizationCheckerInterface::isGranted()`; doména to nesmí.
- **Aggregate** – doménový invariant + doménová výjimka. „Objednávku nelze stornovat po odeslání ani po 24 h od potvrzení.“ Agregát vyhazuje `InvalidOrderStateTransitionException` nebo `CancellationWindowExpiredException`; aplikační vrstva je mapuje na HTTP 409.
- **Field** – Twig `is_granted` ve view (s rizikem úniku dat) nebo query filter / read model pro citlivá data (PII, audit log). Seznamy potřebují filtr v dotazu, ne Voter nad každým řádkem.

Hrubá oprávnění pokryje RBAC. Jakmile pravidla závisí na vztazích mezi entitami, nastupuje uvažování v ABAC pojmech, implementované ale z Voterů, ne z vlastní policy vrstvy. Vícenájemnost řeší Doctrine SQLFilter s kernel listenerem, nastavený fail-closed, a v PostgreSQL k tomu RLS jako záchytná síť pod aplikací. Doménové stavové pravidlo patří do agregátu; vztah aktéra k agregátu (vlastnictví) definuje rovněž agregát a Voter se ho ptá.

Kdy z Voterů odejít, neurčuje počet pravidel, ale tři otázky: musí být policy čitelná mimo vývojový tým, mění se v jiném rytmu než kód, sdílí ji víc aplikací? Dokud zní odpověď třikrát ne, jsou Votery s `Vote::addReason()` levnější varianta. Při prvním ano přichází externí engine (OPA, Cerbos) a Voter se stane tenkým vynucovacím bodem.

### Praktický checklist před deploy {#summary-checklist-heading}

Než commitnete autorizační změnu, projděte těchto devět bodů:

1. Existuje v `access_control` default-deny pravidlo na konci? *Pokud ne: nový endpoint bez explicitní role je veřejný.*
2. Volá Application Handler `$auth->isGranted()` **před** doménovou operací? *Pokud ne: autorizace se může obejít přes alternativní vstupní bod (CLI, Messenger).*
3. Je doménový invariant zapsaný v aggregate, ne ve Voteru? *Pokud ne: pravidlo se obejde přímým voláním aggregate metody mimo handler.*
4. Je rozhodovací strategie nastavená na `unanimous`? *Pokud ne: při výchozí `affirmative` přebije jeden souhlasící Voter všechny nesouhlasící.*
5. Vrací aplikace 403, 404 nebo 409 podle typu selhání? *Pokud ne: uživatel dostane matoucí hlášku, nebo lze enumerovat cizí identifikátory.*
6. Mají citlivá pole (PII, audit) query filter, ne jen Twig if? *Pokud ne: data unikají přes JSON API, dev tools, ETag.*
7. Filtruje endpoint se seznamem v dotazu, ne přes `is_granted()` nad každým řádkem? *Pokud ne: rozpadne se stránkování a výkon klesá lineárně.*
8. Pokud je aplikace multi-tenant: má Doctrine SQLFilter *fail-closed* default? *Pokud ne: chybějící tenant context vrátí všechna data.*
9. Existuje na každé vrstvě alespoň jeden test, včetně architektonického? *Aggregate test, Voter test, e2e test a zákaz importu Security v doméně.*

:::callout{type="pattern"}
### Audit log autorizačních rozhodnutí {#audit-log-heading}

Regulované domény (zdravotnictví, finance) často vyžadují audit log *každého* autorizačního rozhodnutí, ne jen úspěšných operací. Nabízí se dekorátor nad `AuthorizationCheckerInterface` přes `#[AsDecorator(decorates: 'security.authorization_checker')]`. Má to dvě omezení: služba je od Symfony 6.0 privátní, takže se do aplikace dostane jen typehintem přes autowiring, a dekorátor vidí pouze výsledek, ne hlasy jednotlivých voličů.

Přesnější log poskytne vlastní `AccessDecisionStrategyInterface`, případně čtení `AccessDecision` přes `Security::getAccessDecision()`. Tam jsou k dispozici jednotlivé `Vote` objekty s vlastnostmi `$voter`, `$result`, `$reasons` a od Symfony 7.4 i `$extraData`. Audit pak zaznamená i to, který volič zamítl a s jakým odůvodněním. Loguje se obvykle do vyhrazeného Monolog channelu `authorization` a odtud do ELK / Loki / centrálního SIEM.
:::

:::faq{}
- question: Mám psát jeden Voter na entitu, nebo víc?
  answer: 'Jeden Voter na entitu, který pokrývá N atributů (VIEW, CANCEL, REFUND, …). V <code>supports()</code> se filtruje podle <code>$subject instanceof Order</code> a podle whitelistu atributů; v <code>voteOnAttribute()</code> se atributy mapují přes <code>match</code> expression na privátní metody. Více Voterů na jednu entitu se vyplatí jen tehdy, když oprávnění potřebují úplně jiné závislosti (typicky vlastnictví vs. role) a chcete je testovat nezávisle. Detail v <a href="#use-case-voter">sekci o Voteru</a>.'
- question: Smí Voter načítat aggregate z databáze?
  answer: 'Subjekt, o kterém rozhoduje, ne. Voter ho dostává jako <code>$subject</code>; handler ho už načetl a předává v paměti. Druhé načtení je anti-vzor (<a href="#anti-fetching-voter-heading">11.11</a>): vede k duplicate query a k rozhodování nad stavem, který se mezitím mohl změnit. Doplňková data, která na subjektu nejsou (členství v týmu, delegace, hierarchie tenantů), si Voter načíst musí a Symfony s injektovanými službami ve Voteru počítá. Takové dotazy patří za cache platnou po dobu requestu.'
- question: Kdy stačí ROLE_USER a kdy je třeba attribute-based přístup?
  answer: 'RBAC (role) stačí, dokud platí „role popisuje oprávnění sama o sobě“: ROLE_ADMIN smí všechno, ROLE_REFUND_AGENT smí refundy bez ohledu na konkrétní entitu. Jakmile oprávnění závisí na vztazích (vlastnictví, tenant, časové okno, stav agregátu), RBAC se rozroste a vznikají úzce specifické role typu ROLE_TENANT_42_ORDER_AGENT. Tehdy nastupuje uvažování v ABAC pojmech (<a href="#policy-based">11.08</a>): rozhodnutí vyhodnocuje atributy subjektu, uživatele a kontextu. Neznamená to psát vlastní policy engine; v Symfony 8 se totéž postaví z Voterů, které umí i vrátit důvod zamítnutí.'
- question: Co když máme 100 různých rolí?
  answer: 'To je obvykle příznak, že role replikují data, která patří do entit. Místo ROLE_TENANT_42_ADMIN, ROLE_TENANT_43_ADMIN, … zaveďte atribut <code>user.tenantId</code> + jednu generickou roli ROLE_TENANT_ADMIN a ve Voteru ověřte, že <code>user.tenantId == subject.tenantId</code>. Zjednoduší to správu uživatelů, audit i delegaci. Detail v <a href="#multi-tenancy">sekci o multi-tenancy</a>.'
- question: Smí doménový Aggregate záviset na Symfony Security komponentě?
  answer: 'Ne. Doména musí být nezávislá na frameworku. Jinak ji nelze unit-testovat bez Kernelu, sdílet mezi webem a CLI ani převést na jiný framework. Modelový důvod je ale silnější než technický: role a oprávnění jsou slovník Identity &amp; Access kontextu, ne toho, ve kterém agregát žije. Pokud potřebuje aggregate „znát“ uživatele, dostane <em>vlastní</em> doménový typ (<code>CustomerId</code>). Překlad ze <code>SecurityUser</code> obstará aplikační handler. Detail v anti-vzoru 4 v <a href="#anti-symfony-user-domain-heading">11.11</a>.'
- question: Kam ukládat audit log autorizačních rozhodnutí?
  answer: 'Tři možnosti, podle compliance požadavků: (1) Symfony Monolog s vlastním channelem <code>authorization</code>, což stačí pro většinu aplikací, log do souboru / ELK / Loki; (2) doménová tabulka <code>authorization_decisions</code> s parametry (user_id, attribute, subject_id, decision, policy_version), vhodné pro regulované domény (PCI DSS, zdravotnictví); (3) externí audit služba (AWS CloudTrail, Datadog) pro multi-tenant SaaS. Implementačně se osvědčil decorator nad <code>AuthorizationCheckerInterface</code>, který každé volání zaloguje. Pro detail viz <a href="#audit-log-heading">Audit log autorizačních rozhodnutí</a>.'
:::

## 11.13 Další četba {#further-reading}

- [Symfony Security komponenta – oficiální dokumentace](https://symfony.com/doc/current/security.html)
- [Symfony Voters – Custom Authorization](https://symfony.com/doc/current/security/voters.html)
- [OWASP Top 10 (2021): A01 – Broken Access Control](https://owasp.org/Top10/A01_2021-Broken_Access_Control/)
- [NIST SP 800-162 – Guide to Attribute-Based Access Control](https://csrc.nist.gov/publications/detail/sp/800-162/final)
- [OpenID Connect Core 1.0 – autentizační vrstva nad OAuth 2.0](https://openid.net/specs/openid-connect-core-1_0.html)
- [Stripe API keys – restricted keys s explicitním scope](https://stripe.com/docs/keys)
- [Symfony – jak funguje access_control](https://symfony.com/doc/current/security/access_control.html)
- [Open Policy Agent (OPA) – externí policy engine](https://www.openpolicyagent.org/docs/latest/)
- [OpenFGA – Authorization Concepts (ReBAC, model Zanzibaru)](https://openfga.dev/docs/authorization-concepts)
- [PostgreSQL – Row Security Policies](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
- [Noback, M.: Decoupling your security user from your user model (2022)](https://matthiasnoback.nl/2022/07/decoupling-your-security-user-from-your-user-model/)
- Pang, R. a kol.: *Zanzibar: Google's Consistent, Global Authorization System*. USENIX ATC '19, Renton, WA, 2019.
- Vernon, V.: *Implementing Domain-Driven Design*. Addison-Wesley, 2013. Kapitola 14 „Application“ a Identity and Access Context.
