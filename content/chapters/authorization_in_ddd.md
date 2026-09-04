---
route: authorization_in_ddd
path: /autorizace-v-ddd
title: Autorizace v DDD na Symfony
page_title: "Autorizace v DDD: Voters a ACL na agregátu | DDD Symfony"
meta_description: "Kde má v DDD aplikaci sedět autorizace: edge, use case, agregát, field. Čtyři vrstvy s ukázkami Symfony Voterů a policy-based přístupu."
meta_keywords: "Autorizace, Authorization, Symfony Voter, RBAC, ABAC, Policy-based, ACL, Aggregate permissions, DDD Symfony 8, Security, Doctrine, Owner-based, Multi-tenancy, TenantFilter"
og_type: article
published: "2026-04-29"
modified: "2026-07-08"
breadcrumb_name: Autorizace v DDD
schema_type: TechArticle
schema_headline: "Autorizace v DDD na Symfony – 4 vrstvy, Voters a policy-based přístup"
chapter_number: "11"
category: Architektura
deck: 'V DDD aplikacích se opakovaně objevuje stejná otázka: <em>„smí to ten uživatel udělat?“</em> – patří do controlleru, do voteru, do aggregate, nebo někam jinam? Kapitola dává konkrétní čtyřvrstvý rámec: Edge, Use Case, Aggregate, Field. Každá vrstva odpovídá na jinou otázku a používá jiný Symfony nástroj.'
reading_time: 34
difficulty: 3
---

V předchozí kapitole jsme implementovali agregáty, repozitáře a Application Services v Symfony 8. Otevřená zůstala otázka, kterou projekty obvykle řeší případ od případu: **kdo smí který use case zavolat a za jakých podmínek**. V této kapitole zavedeme čtyřvrstvý rámec, který autorizační rozhodnutí umístí na správnou vrstvu – od HTTP firewallu přes Symfony Voter v aplikační vrstvě až po doménové invarianty v agregátu. Volání z Command Handleru ukazuje [sekce 11.04](#use-case-voter); kapitola o CQRS na to navazuje [middleware vrstvou](/cqrs#middleware), kterou lze autorizaci vytáhnout před handler.

Autentizaci (Symfony firewall, JWT, OAuth) tým většinou postaví bez větších potíží. Otázka *„kdo smí udělat co s konkrétní entitou v konkrétním stavu“* je ale jiná disciplína. Bez rámce se odpověď rozpadne mezi controllery, listenery, twig šablony a Doctrine query buildery. Kapitola dává **čtyřvrstvý rámec**: podle něj poznáte, kam které pravidlo patří a jak ho v Symfony 8 implementovat idiomaticky. Security komponenta přitom nepronikne do doménového jádra.

Kapitola navazuje na [Implementaci v Symfony](/implementace-v-symfony), která autorizaci záměrně ponechala stranou a odkazuje sem. Doplňuje praktický pohled k tématům [CQRS](/cqrs) (kde sedí ověření Command Handleru), [Testování](/testovani-ddd) (jak otestovat každou ze 4 vrstev samostatně) a [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli) (která autorizaci zmiňuje jen letmo).

## 11.01 Tři chyby s autorizací, které se v review opakovaně objevují {#tri-chyby}

Tři vzory níže spojuje jedna příčina: chybí rozhodovací rámec, kam které pravidlo patří. Pořadí odpovídá odhadované četnosti v code review; měřená data k tomu nejsou, jde o autorský odhad. Že téma unese vlastní kapitolu, ale doložit lze: OWASP posunul [Broken Access Control](https://owasp.org/Top10/A01_2021-Broken_Access_Control/) v Top 10 pro rok 2021 z pátého místa na první.

### Chyba 1: Vše v controlleru {#tri-chyby-controller-heading}

Nejčastější vzor. Controller přijme HTTP požadavek, načte entitu z repository a inline porovná atributy uživatele s atributy entity:

:::code{language="php" filename="src/Controller/OrderController.php (anti-vzor)" highlights="13,14,15,16,17,18"}
// src/Controller/OrderController.php (anti-vzor)
namespace App\Controller;

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

Co je špatně: stejný use case se volá i z konzolového commandu (cron, batch), ze Symfony Messenger handleru (asynchronní queue) a z administračního panelu. Při každém volání musí někdo tutéž podmínku zopakovat – a stačí, aby jeden vstupní bod selhal, a celá ochrana padá. Pravidlo „zrušit smí jen vlastník“ patří do use-case vrstvy – zde je ale rozeseté po infrastruktuře, ne na jednom místě.

### Chyba 2: Vše ve Voteru, doména nezná autorizaci {#tri-chyby-vse-voter-heading}

Druhý extrém. Tým objeví Symfony Voter a přesune do něj *všechna* pravidla – včetně doménových invariantů. Aggregate má veřejné API `setStatus()`, `setTotal()`, `setCustomerId()` a Voter „natáhne“ autorizaci přes ně:

:::code{language="php" filename="src/Security/OrderVoter.php (anti-vzor)" highlights="13,14,15,16,17"}
// src/Security/OrderVoter.php (anti-vzor)
namespace App\Security;

final class OrderVoter extends Voter
{
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
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

Co je špatně: Aggregate `Order::setStatus(OrderStatus::Cancelled)` stále existuje a je veřejné. Stačí, aby kdokoli (test, fixture, migration script, jiný vývojář) zavolal setter mimo Voter – a invariant „24h cancellation window“ je porušen. Voter je jen *volitelný* filtr před vstupem; doména nemá žádnou pojistku. Pravidlo „cancellation window“ je doménové, ne use-case-level.

### Chyba 3: Autorizace na úrovni databázových řádků {#tri-chyby-doctrine-heading}

Tým objeví Doctrine SQLFilter a rozhodne, že autorizaci vyřeší v perzistentní vrstvě – entity se z databáze nevrátí, pokud k nim uživatel nemá přístup. Funguje to pro *read* dotazy, ale rozpadá se v doménové logice:

- Když handler dostane `$orderId` a entita se nenajde, neví, jestli neexistuje, nebo jen není dostupná pro daného uživatele. Chybová hláška „Order not found“ je matoucí.
- Doctrine filtry se nevztahují na entity už načtené v identity map, na nativní SQL ani na Redis cache.
- Filtr se neuplatní ani při načtení **neowning strany asociace one-to-one**. Ověřeno na ORM 3.6:
  `find()` i DQL cizí záznam skryjí, ale průchod z entity na druhý konec vztahu ho vrátí. Kdo staví
  oddělení tenantů jen na filtru, má tudy díru.
- Doménová pravidla typu „order patří customerovi“ ztrácejí jedno závazné místo: zapsaná jsou v SQL filtru, ve Voteru se na ně zapomíná a v aggregate chybí – při volání mimo HTTP vrstvu se nevynutí.

:::callout{type="warn"}
### Diagnóza: chybí rámec, kam co umístit {#diagnoza-heading}

Společným jmenovatelem všech tří chyb je absence rozhodovacího rámce. Vývojář má v každém okamžiku **jednu konkrétní otázku** („smí to vidět?“, „smí to udělat?“, „je to vůbec možné?“, „má vidět tento sloupec?“). Každou z nich řeší správný nástroj na správné vrstvě. Když chybí mapa, použije se první nástroj, který má po ruce – a kód se rozpadne. V další sekci ten rámec dáme dohromady.
:::

## 11.02 Čtyři vrstvy autorizace {#ctyri-vrstvy}

Vrstvy dávají smysl až ve strategickém kontextu. Identita a oprávnění tvoří vlastní **Bounded Context**. Vernon mu v *Implementing Domain-Driven Design* říká Identity and Access Context a v referenční implementaci `IDDD_Samples` je to samostatný modul vedle Ordering a Collaboration, který ostatní kontexty konzumují jako službu. Typologicky spadá pod [generickou subdoménu](/subdomeny#tri-kategorie): kupuje se (Keycloak, Auth0, OIDC provider), nemodeluje se vlastními silami. Autorizační *rozhodnutí* přitom zůstává v konzumujícím kontextu, protože závisí na jeho entitách a stavech. Zdroj identit a rolí leží mimo něj a vazbu mezi obojím popisuje [Context Mapping](/context-mapping) jako Open Host Service.

Uvnitř konzumujícího kontextu padá autorizační rozhodnutí ve čtyřech postupných vrstvách. Každá vrstva má vlastní otázku, Symfony nástroj i granularitu. Vrstvy fungují jako *filtry*: každá další odpovídá na jemnější otázku a předpokládá, že předchozí vrstva už řekla „ano“.

:::diagram{fig="11.2-A" title="4 vrstvy autorizace v DDD aplikaci" src="images/diagrams/19_authorization/policy_layers.svg"}
:::

| Vrstva | Otázka | Symfony nástroj | Příklad |
|---|---|---|---|
| **Edge** | Je přihlášený? Smí na tuhle URL? | `access_control`, JWT firewall | `/admin/*` jen pro `ROLE_ADMIN` |
| **Use Case** | Smí vykonat use case na tomto objektu? | `Voter` | „Smí Petr cancelnout order #42?“ |
| **Aggregate** | Dá se to vůbec teď udělat? | doménový check + výjimka | „Order lze cancelnout jen 24 h od vytvoření“ |
| **Field** | Smí vidět konkrétní pole? | Twig + Voter, query filter | „Sloupec `audit_log` vidí jen admin“ |

Formulace toho pravidla je přesnější takto: každé autorizační pravidlo má právě jedno místo *definice*. Vynucení může proběhnout na více vrstvách, pokud všechny čtou tutéž definici – OWASP to formuluje jako požadavek implementovat kontrolu jednou a znovu ji používat. Duplicitou je až *přepis* téhož pravidla druhými slovy na druhé vrstvě; typické případy ukazuje [sekce o anti-vzorech](#antivzory).

Metafora filtrů přitom stojí na jedné podmínce. Uvnitř use-case vrstvy platí jen tehdy, když je rozhodovací strategie nastavená na `unanimous`. Výchozí `affirmative` ji obrací naruby: stačí jeden souhlasící Voter a nesouhlas ostatních se ignoruje. Podrobnosti v [sekci o rozhodovací strategii](#access-decision).

*Citace: Symfony Security komponenta dokumentuje vícevrstvý přístup v sekci „Authorization“ [[1]](https://symfony.com/doc/current/security.html#access-control-authorization); obecné principy ABAC vs. RBAC najdete v NIST SP 800-162 [[2]](https://csrc.nist.gov/publications/detail/sp/800-162/final). Vernon v *Implementing Domain-Driven Design* (kap. 14, „Application“) umisťuje autorizační kontrolu do Application Services: aplikační služba se podle něj stará o bezpečnost a překlad objektů. Čtyřvrstvý rámec této kapitoly u něj nenajdete – jde o autorské rozšíření, ne o Vernonův model.*

## 11.03 Edge – Symfony firewall a access_control {#edge}

Edge je nejhrubší vrstva a leží mimo doménový kód. Odpovídá pouze na otázku **„kdo je vůbec na druhém konci socketu?“** – anonymous, authenticated, případně role-based pro hrubě dělené sekce (`/admin/*`, `/api/v1/*`). Doménová pravidla typu „zákazník X smí na tuto objednávku“ patří o vrstvu výš (use case).

Uživatelský provider ukazuje na třídu `SecurityUser` z infrastruktury, ne na doménovou entitu. Důvod je zásadní pro celý zbytek kapitoly: provider vyžaduje implementaci `Symfony\Component\Security\Core\User\UserInterface`, a kdyby ji nesla doménová třída, doména by se svázala se Security komponentou. Přesně to zakazuje [anti-vzor 4](#anti-symfony-user-domain-heading). `SecurityUser` je navíc *read model* pro autentizaci: nese identifikátor, hash hesla, role a doménové ID (`CustomerId`, `TenantId`), a mění se z jiných důvodů než doménový model uživatele [[3]](https://matthiasnoback.nl/2022/07/decoupling-your-security-user-from-your-user-model/).

:::code{language="yaml" filename="config/packages/security.yaml"}
# config/packages/security.yaml
security:
    providers:
        app_user_provider:
            entity:
                # Infrastrukturní třída implementující UserInterface,
                # mapovaná na tabulku app_user. Doména o ní neví.
                class: App\Identity\Infrastructure\Security\SecurityUser
                property: email

    firewalls:
        # Stateless API – JWT
        api:
            pattern: ^/api/
            stateless: true
            jwt: ~
            provider: app_user_provider

        # Web – session
        main:
            pattern: ^/
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: login
                check_path: login
            logout: ~

    access_control:
        # Veřejné endpointy
        - { path: ^/login,        roles: PUBLIC_ACCESS }
        - { path: ^/register,     roles: PUBLIC_ACCESS }
        - { path: ^/health,       roles: PUBLIC_ACCESS }
        # Hrubá role-based separace
        - { path: ^/admin,        roles: ROLE_ADMIN }
        - { path: ^/api/internal, roles: ROLE_SERVICE_ACCOUNT }
        # Vše ostatní za autentizací
        - { path: ^/,             roles: IS_AUTHENTICATED_FULLY }
:::

Doprovodná třída `SecurityUser` drží most mezi Symfony a doménou. Kromě `UserInterface` vystavuje doménové identifikátory, které si z ní vezme aplikační vrstva:

:::code{language="php" filename="src/Identity/Infrastructure/Security/SecurityUser.php"}
// src/Identity/Infrastructure/Security/SecurityUser.php
declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\CustomerId;
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

    public function eraseCredentials(): void {}

    // Most do domény – Voter i handler pracují s doménovým typem
    public function customerId(): CustomerId { return CustomerId::fromString($this->customerId); }
    public function tenantId(): TenantId { return TenantId::fromString($this->tenantId); }
}
:::

Principy edge vrstvy:

- **Žádná doménová znalost.** Edge nezná pojem „order“, „customer“, „cancellation window“. Pracuje jen s URL pattern + roles + autentizační stav.
- **Default deny.** Poslední pravidlo v `access_control` je „všechno ostatní vyžaduje přihlášení“. Bez tohoto fallbacku stačí přidat nový endpoint a zapomenout ho zařadit – automaticky bude veřejný.
- **Role-based, ne attribute-based.** ROLE_ADMIN je hrubá kategorizace; jemnější rozhodnutí jako „admin tenantu T1, ne T2“ patří do Voteru, ne do `access_control`.
- **JWT firewall vs. session.** API typicky stateless (`jwt` autentikátor), web typicky session-based. Pro JWT v Symfony existuje balíček `lexik/jwt-authentication-bundle` nebo nativní `access_token` autentikátor s `OidcTokenHandler` pro OpenID Connect provider [[4]](https://openid.net/specs/openid-connect-core-1_0.html).

Matcherů má `access_control` víc než jen `path` a `roles`. K dispozici jsou `host`, `port`, `ips`, `methods`, `attributes`, `route`, `request_matcher`, a k tomu `allow_if` a `requires_channel` [[5]](https://symfony.com/doc/current/security/access_control.html). Uplatní se **první shodné pravidlo** a nespecifikovaný matcher odpovídá čemukoli. Pravidlo `{ path: ^/api, methods: [POST] }` tedy nechrání GET na téže cestě, pokud dřív v seznamu není obecnější záznam.

:::callout{type="warn"}
### Past: `roles` a `allow_if` v jednom pravidle se chovají jako OR {#edge-allow-if-heading}

Zápis `{ path: ^/report, roles: ROLE_ANALYST, allow_if: "is_granted('ROLE_ADMIN')" }` vypadá jako konjunkce dvou podmínek. Při výchozí strategii `affirmative` ale stačí splnit jednu z nich, protože obě přispějí samostatným hlasem. Kdo takto skládá restrikce, otevře endpoint širší skupině, než zamýšlel. Když má platit AND, patří obě podmínky do jednoho výrazu `allow_if`, nebo se rozhodovací strategie musí přepnout na `unanimous`.
:::

:::callout{type="pattern"}
### Vzorová analogie: Stripe API key model {#edge-stripe-heading}

Stripe rozlišuje API klíče na úrovni edge: `sk_test_*`, `sk_live_*`, `pk_*`, restricted keys s explicitním scope [[6]](https://stripe.com/docs/keys). Klíč rozhoduje, zda volání vůbec dorazí do API – to je edge vrstva. Ale *kdo konkrétně* je za klíčem (jaký účet, jaké permissions na konkrétní Customer/Charge entitu) řeší až další vrstva. Tatáž logika by měla platit ve vašem Symfony API: JWT validuje, kdo to je; Voter rozhoduje, co s konkrétním objektem smí.
:::

## 11.04 Use Case – Symfony Voter {#use-case-voter}

Use case vrstva odpovídá na otázku **„smí *tento* uživatel vykonat *tento* use case na *tomto* objektu?“**. Symfony Voter je přesně k tomu navržený nástroj. Pravidlo: **1 use case = 1 atribut Voteru**; jeden Voter může pokrývat N atributů, pokud se týkají stejné entity (typicky CRUD operace nad agregátem).

Voter zná dvě věci: **identitu uživatele** (přes `TokenInterface`) a **cílový subjekt** (typicky aggregate root). Co Voter *nesmí* dělat: načítat subjekt, o kterém rozhoduje, a znát doménové invarianty (to je práce aggregate). Pravidla typu „cancellation window“ Voter nesmí natáhnout zvenku – patří ke stavu agregátu.

:::code{language="php" filename="src/Ordering/Infrastructure/Security/OrderVoter.php" highlights="18,19,20,28,29,30,31,33,34,35,36,50,58,59"}
// src/Ordering/Infrastructure/Security/OrderVoter.php
declare(strict_types=1);

namespace App\Ordering\Infrastructure\Security;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Ordering\Domain\Order;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
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

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
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

- **Konstanty atributů s prefixem entity** (`order.cancel`, ne jen `CANCEL`). Vyhne se kolizi s atributy jiných Voterů (`invoice.cancel`, `shipment.cancel`) a v audit logu je hned jasné, kterého subjektu se rozhodnutí týkalo.
- **Match expression** místo if-else stromu. Bez default větve PHPStan ohlásí nepokrytý case; `default => false` naopak volí tiché zamítnutí (fail-closed) výměnou za ztrátu této kontroly.
- **Privátní metody `canView`, `canCancel`**. Každý use case má vlastní privátní metodu – testy umí mockovat token a subjekt, asserce na výsledek metody je explicitní. Bez extrakce by se voter rozrostl do nečitelného switch-case.
- **Role se uvnitř Voteru kontrolují přes `AccessDecisionManagerInterface::decide()`**, ne dotazem na uživatelskou třídu. Volání `$user->hasRole('ROLE_ADMIN')` obejde hierarchii rolí nakonfigurovanou v `security.yaml`: uživatel s `ROLE_SUPER_ADMIN` by `ROLE_ADMIN` nedostal, přestože ho hierarchie zahrnuje. Doporučuje to dokumentace k Voterům [[7]](https://symfony.com/doc/current/security/voters.html).
- **`supportsAttribute()` a `supportsType()`** pocházejí z `CacheableVoterInterface`, které abstraktní `Voter` implementuje. Výchozí návratová hodnota obou je `true`, takže bez override žádnou optimalizaci nepřinášejí. Seznam s 200 řádky a pěti Votery znamená tisíc zbytečných volání `supports()`; s override odpadne většina z nich už na úrovni rozhodovacího manažeru.

### Rozhodovací strategie a `AccessDecisionManager` {#access-decision}

Voterů bývá v aplikaci víc a jejich hlasy někdo skládá dohromady. Dělá to `AccessDecisionManager` a strategie, kterou použije, mění výsledek zásadněji než cokoli uvnitř samotných Voterů:

| Strategie | Chování | Kdy dává smysl |
|---|---|---|
| `affirmative` | výchozí; stačí jeden souhlas | jednoduché aplikace s jedním Voterem na subjekt |
| `unanimous` | zamítne, jakmile nesouhlasí kdokoli | vrstvená autorizace, multi-tenancy |
| `consensus` | rozhoduje většina | zřídka; výsledek se hůř zdůvodňuje |
| `priority` | rozhodne první nezdržující se volič | explicitní pořadí přes `#[AsTaggedItem(priority: …)]` |

Výchozí `affirmative` je pro rámec této kapitoly špatná volba. Kdo si vedle `OrderVoter` postaví `TenantVoter`, dostane opak toho, co čekal: `TenantVoter` cizí tenant zamítne, `OrderVoter` řekne ano na základě vlastnictví a přístup projde. Pro vrstvenou autorizaci proto:

:::code{language="yaml" filename="config/packages/security.yaml"}
# config/packages/security.yaml
security:
    access_decision_manager:
        strategy: unanimous
        allow_if_all_abstain: false   # nikdo nehlasoval = zamítnuto
:::

Volba `allow_if_all_abstain: false` je výchozí, ale patří do konfigurace explicitně. Je to poslední fail-closed pojistka: atribut, pro který se žádný Voter nepřihlásí, skončí zamítnutím místo tichého povolení.

### `#[IsGranted]` na controlleru {#is-granted-attribute}

Kontrola v handleru je autoritativní, na hranici HTTP se ale vyplatí odmítnout požadavek dřív, než se vůbec sestaví command. K tomu slouží atribut `Symfony\Component\Security\Http\Attribute\IsGranted`. Funguje na metodě i na celé třídě controlleru a druhým argumentem odkazuje na argument akce, který se má stát subjektem:

:::code{language="php" filename="src/Ordering/Infrastructure/Http/OrderController.php"}
// src/Ordering/Infrastructure/Http/OrderController.php
declare(strict_types=1);

namespace App\Ordering\Infrastructure\Http;

use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Domain\Order;
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
            orderId: $order->id(),
            reason:  (string) $request->request->get('reason', ''),
            actorId: $this->getUser()->customerId(),
        ));

        return $this->redirectToRoute('order_detail', ['id' => $request->get('id')]);
    }
}
:::

Parametr `statusCode: 404` mění odpověď z 403 na 404. Rozdíl není kosmetický: 403 potvrdí útočníkovi, že objednávka s daným ID existuje, a umožní enumerovat cizí identifikátory. Ke stejnému tématu se vrací [callout o 403 vs. 409](#aggregate-403-vs-409-heading).

Dvě omezení, která je dobré znát předem. Atribut potřebuje subjekt už jako objekt, takže se neobejde bez `#[MapEntity]` nebo obdobného převodu – a tím se dotaz do databáze přesouvá do controlleru. A jakmile controller command jen odešle na asynchronní bus, `#[IsGranted]` chrání pouze vstup do fronty; zpracování ve workeru běží bez tokenu a řeší ho [následující sekce](#async-authorization). Atribut proto kontrolu v handleru nenahrazuje, jen ji doplňuje na hranici.

### Proč ne Symfony ACL {#no-symfony-acl}

Starší materiály nabízejí jako řešení per-objektových oprávnění komponentu ACL – tabulky `acl_entries`, `acl_object_identities` a `MaskBuilder`. Pro Symfony 8 už to není volba. ACL byla z jádra odstraněna ve verzi 6.0 a samostatný `symfony/acl-bundle` deklaruje ve svém posledním vydání podporu Symfony 4.4 až 7.0. Ani technicky by ale nešlo o dobrou náhradu: ACL ukládá rozhodnutí jako *data* v databázi, takže pravidlo „vlastník smí zrušit do 24 hodin“ se rozpadne na řádky, které někdo musí udržovat v synchronizaci se stavem agregátu. Voter tutéž věc počítá z aktuálního stavu a nic synchronizovat nemusí. Kde jsou potřeba explicitně přidělovaná oprávnění na jednotlivé objekty (sdílení dokumentu, delegace), sáhne se dnes po vlastní tabulce vazeb nebo po ReBAC modelu z [pozdější sekce](#rebac).

### Použití ve Command Handleru {#voter-handler-heading}

Voter sám o sobě nestačí – někdo ho musí zavolat. Idiomatické místo je **Application Service / Command Handler**, kde se autorizace ověří *před* doménovou operací. Handler injektuje `AuthorizationCheckerInterface` (rozhraní Security komponenty), což je v aplikační vrstvě v pořádku – doménová vrstva by tu závislost mít nesměla.

:::code{language="php" filename="src/Ordering/Application/Handler/CancelOrderHandler.php" highlights="18,19,25,26,27,28,29"}
// src/Ordering/Application/Handler/CancelOrderHandler.php
declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Ordering\Domain\OrderRepository;
use App\Ordering\Infrastructure\Security\OrderVoter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsMessageHandler]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private AuthorizationCheckerInterface $auth,
    ) {}

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->orders->getOrFail($command->orderId);

        if (!$this->auth->isGranted(OrderVoter::CANCEL, $order)) {
            throw new AccessDeniedDomainException(
                sprintf('Cancel not allowed for order %s', $command->orderId->toString())
            );
        }

        $order->cancel(reason: $command->reason, when: new \DateTimeImmutable());
        $this->orders->save($order);
    }
}
:::

Po této kontrole zavolá handler doménovou operaci `$order->cancel(...)`, která uvnitř agregátu ověří doménové invarianty (status, cancellation window). Tím vznikají **dvě nezávislé bariéry**: Voter řekne „smí Petr“, aggregate řekne „dá se to vůbec teď“. Detail aggregate vrstvy v [sekci 11.06](#aggregate-level). Jeden háček tu ale je: handler nese atribut `#[AsMessageHandler]` a v asynchronním workeru žádný token neexistuje – tomu se věnuje [následující sekce](#async-authorization).

### Voter v Twig template {#voter-twig-heading}

Stejný Voter pokrývá i view-level rozhodnutí (skrýt tlačítko „Cancel order“ pro ne-vlastníka). V Twigu funkce `is_granted()` volá tentýž `AuthorizationCheckerInterface`. Proměnnou `now` (`\DateTimeImmutable`) předává do šablony controller – doménová metoda `isCancellable()` si aktuální čas nezískává sama:

:::code{language="twig" filename="templates/order/detail.html.twig" highlights="4,12,18"}
{# templates/order/detail.html.twig #}
<h1>Order #{{ order.id }}</h1>

{% if is_granted('order.view', order) %}
    <dl>
        <dt>Customer</dt><dd>{{ order.customer.name }}</dd>
        <dt>Total</dt>   <dd>{{ order.total|format_currency('CZK') }}</dd>
        <dt>Status</dt>  <dd>{{ order.status.label }}</dd>
    </dl>
{% endif %}

{% if is_granted('order.cancel', order) and order.isCancellable(now) %}
    <form method="post" action="{{ path('order_cancel', {id: order.id}) }}">
        <button type="submit">Cancel order</button>
    </form>
{% endif %}

{% if is_granted('order.refund', order) %}
    <a href="{{ path('order_refund', {id: order.id}) }}" class="btn-danger">Refund</a>
{% endif %}
:::

Pozor: `{% if is_granted(...) %}` v Twigu jen schová tlačítko – neověří, že request nebude poslán manuálně (curl, Postman, browser dev tools). View-level kontrola je *UX*, nikoli bezpečnostní bariéra. Bezpečnostní rozhodnutí padne v handleru.

:::callout{type="warn"}
### Voter nenačítá subjekt, o kterém rozhoduje {#voter-anti-fetching-heading}

Pokud váš Voter dělá `$this->repository->find($id)` nad subjektem, který mu měl přijít jako parametr, je to anti-vzor. Voter dostává `$subject` v paměti; handler ho už načetl. Druhé načtení vede k *duplicate query* a v horším případě k *race condition* – mezi dotazem ve Voteru a operací v handleru se entita změní a rozhodovalo se nad neaktuálním stavem.

Zákaz se ale netýká *doplňkových* dat, která na subjektu nejsou: členství v týmu, delegace, hierarchie tenantů. Ta si Voter načíst musí a dokumentace Symfony s injektovanými službami ve Voteru počítá [[7]](https://symfony.com/doc/current/security/voters.html). Dotazy tohoto typu patří za cache platnou po dobu requestu; jinak seznam s dvěma sty řádky vygeneruje dvě stě dotazů.
:::

## 11.05 Autorizace v asynchronním kontextu {#async-authorization}

Jakmile command putuje přes asynchronní transport, kontrola přes `AuthorizationCheckerInterface` se rozpadne. Messenger worker běží mimo HTTP požadavek: `TokenStorage` je prázdná, `$this->security->getUser()` vrací `null` a Voter postavený na tokenu vyhodnotí každé volání jako zamítnuté. Kód, který v synchronním režimu fungoval, začne po přepnutí transportu tiše odmítat legitimní operace.

Řešení: **command nese identitu aktéra**. V místě vzniku, typicky v controlleru, token ještě existuje – tam se do commandu zapíše `actorId` jako doménový identifikátor uživatele, ne Symfony `UserInterface`. Handler pak autorizuje proti této identitě bez ohledu na to, kde a kdy běží.

:::code{language="php" filename="src/Ordering/Application/Command/CancelOrderCommand.php" highlights="14"}
// src/Ordering/Application/Command/CancelOrderCommand.php
declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Identity\Domain\CustomerId;
use App\Ordering\Domain\OrderId;

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
// src/Ordering/Application/Handler/CancelOrderHandler.php (async varianta)
declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Ordering\Application\Command\CancelOrderCommand;
use App\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Ordering\Domain\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelOrderHandler
{
    public function __construct(private OrderRepository $orders) {}

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->orders->getOrFail($command->orderId);

        // Autorizace proti identitě v commandu – token ve workeru neexistuje
        if (!$order->isOwnedBy($command->actorId)) {
            throw new AccessDeniedDomainException(
                sprintf('Cancel not allowed for order %s', $command->orderId->toString())
            );
        }

        $order->cancel(reason: $command->reason, when: new \DateTimeImmutable());
        $this->orders->save($order);
    }
}
:::

Owner-based pravidlo vystačí s porovnáním `actorId` proti vlastníkovi agregátu, jak ukazuje handler výše. Voter z HTTP vrstvy přitom nezaniká: controller před odesláním commandu volá `is_granted` jako rychlou zpětnou vazbu pro UI. Rozhodující kontrola ale sedí v handleru a v agregátu – běží při každém zpracování, synchronním i asynchronním.

### Když je potřeba ve workeru celý Voter {#async-is-granted-for-user}

Ruční porovnání identit stačí na vlastnictví. Pravidla závislá na rolích (refund smí jen `ROLE_REFUND_AGENT`) by se tímto způsobem musela ve workeru napsat podruhé a jinak než ve Voteru – a tím vzniká přesně ta duplicita, kterou zakazuje [anti-vzor 3](#anti-duplication-heading). Symfony na to má `UserAuthorizationCheckerInterface` a metodu `isGrantedForUser()`, která spustí tytéž Votery proti předanému uživateli, aniž by potřebovala session nebo token v `TokenStorage`:

:::code{language="php" filename="src/Ordering/Application/Handler/RefundOrderHandler.php" highlights="20,27,29"}
// src/Ordering/Application/Handler/RefundOrderHandler.php
declare(strict_types=1);

namespace App\Ordering\Application\Handler;

use App\Identity\Infrastructure\Security\SecurityUserProvider;
use App\Ordering\Application\Command\RefundOrderCommand;
use App\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Ordering\Domain\OrderRepository;
use App\Ordering\Infrastructure\Security\OrderVoter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authorization\UserAuthorizationCheckerInterface;

#[AsMessageHandler]
final readonly class RefundOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private SecurityUserProvider $users,
        private UserAuthorizationCheckerInterface $auth,
    ) {}

    public function __invoke(RefundOrderCommand $command): void
    {
        $order = $this->orders->getOrFail($command->orderId);
        // Aktér se načte podle identity v commandu, ne ze snapshotu rolí
        $actor = $this->users->byCustomerId($command->actorId);

        if (!$this->auth->isGrantedForUser($actor, OrderVoter::REFUND, $order)) {
            throw new AccessDeniedDomainException(
                sprintf('Refund not allowed for order %s', $command->orderId->toString())
            );
        }

        $order->refund($command->amount);
        $this->orders->save($order);
    }
}
:::

Volba mezi oběma variantami se řídí povahou pravidla. Vlastnictví je vztah, který zná agregát sám, a porovnání `actorId` proti `customerId` nepotřebuje ani Security komponentu, ani dotaz navíc. Jakmile pravidlo závisí na rolích, hierarchii rolí nebo na atributech mimo agregát, vyplatí se sáhnout po `isGrantedForUser()` a mít pravidlo jen jednou – ve Voteru. Cenou je dotaz na aktéra a závislost aplikační vrstvy na Security komponentě, což je tatáž závislost, jakou už nese synchronní handler.

Vzor má jeden trade-off. Mezi zařazením do fronty a zpracováním uplyne čas a oprávnění se mezitím mohla změnit – aktér přišel o roli, účet byl zablokován. Snapshot rolí přibalený do commandu proto slouží nanejvýš auditu; autoritativní je stav v okamžiku zpracování. Handler tedy nečte oprávnění ze zprávy, ale ověřuje je proti aktuálním datům – načtením aktéra, nebo porovnáním vlastnictví, které se na rozdíl od rolí nemění.

Systémové procesy (cron, saga, batch) lidského aktéra nemají. Pro ně se zavádí explicitní systémová identita s vlastním `actorId` a vyhrazenými právy – nikoli obcházení kontroly podmínkou „když aktér chybí, povol vše“. Taková podmínka je přesně ten fail-open default, před kterým varuje [sekce o multi-tenancy](#multi-tenancy).

## 11.06 Aggregate-level – doména sama rozhoduje {#aggregate-level}

Některá pravidla do Voteru nepatří. Vyžadují znalost *doménového stavu*, který Voter nemá natáhnout zvenku – typicky časové okno, předchozí status objednávky nebo invarianty napříč entitami uvnitř agregátu. Tato pravidla patří do **aggregate root** a vynucují se vyhozením *doménové výjimky*.

Praktická heuristika:

- Pokud pravidlo vyžaduje *stav agregátu* („order musí být ve stavu PLACED a ne starší než 24 h“), patří do **Aggregate**.
- Pokud pravidlo popisuje *vztah* mezi aktérem a agregátem (vlastnictví, členství, hierarchie), definuje ho také **Aggregate** – zná ho a nepřestává ho znát, když se command zpracuje asynchronně. Voter se na něj ptá, neopisuje ho.
- Pokud pravidlo mluví o *atributech aktéra* („refund smí jen `ROLE_REFUND_AGENT`“, „mimo pracovní dobu ne“), patří do **Voteru**. Agregát o rolích nic neví a vědět nemá.

Prostřední bod je ten, na kterém se týmy nejčastěji rozejdou. „Zrušit smí jen vlastník“ zní jako typické use-case pravidlo, ve skutečnosti je to invariant vztahu mezi `Order` a `CustomerId`. Agregát na něj proto odpovídá metodou `isOwnedBy()` a Voter i asynchronní handler ji volají místo vlastního porovnání. Definice zůstane jedna, vynucení může být na obou místech.

:::code{language="php" filename="src/Ordering/Domain/Order.php" highlights="23,24,25,26,27,28,29,30,31,33,34,35,36,37,38,39,40"}
// src/Ordering/Domain/Order.php
declare(strict_types=1);

namespace App\Ordering\Domain;

use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\Exception\CancellationWindowExpiredException;
use App\Ordering\Domain\Exception\InvalidOrderStateException;
use App\SharedKernel\Domain\AggregateRoot;

final class Order extends AggregateRoot
{
    private const CANCELLATION_WINDOW_SECONDS = 86_400; // 24 h

    public function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private OrderStatus $status,
        private readonly \DateTimeImmutable $placedAt,
    ) {}

    public function cancel(string $reason, \DateTimeImmutable $when): void
    {
        if ($this->status !== OrderStatus::Placed) {
            throw new InvalidOrderStateException(
                sprintf(
                    'Cancel allowed only for PLACED orders, got %s',
                    $this->status->value,
                )
            );
        }

        $age = $when->getTimestamp() - $this->placedAt->getTimestamp();
        if ($age > self::CANCELLATION_WINDOW_SECONDS) {
            throw new CancellationWindowExpiredException(
                $this->id,
                $this->placedAt,
                $when,
            );
        }

        $this->status = OrderStatus::Cancelled;
        $this->record(new OrderCancelled(
            orderId:    $this->id,
            customerId: $this->customerId,
            reason:     $reason,
            cancelledAt: $when,
        ));
    }

    public function isCancellable(\DateTimeImmutable $now): bool
    {
        if ($this->status !== OrderStatus::Placed) {
            return false;
        }
        return $now->getTimestamp() - $this->placedAt->getTimestamp()
            <= self::CANCELLATION_WINDOW_SECONDS;
    }

    // Vztahový invariant: vlastnictví zná agregát, ne Voter
    public function isOwnedBy(CustomerId $customerId): bool
    {
        return $this->customerId->equals($customerId);
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }
}
:::

Aggregate nemá žádnou závislost na Symfony. Používá pouze PHP standardní typy a vlastní doménové třídy – žádný `TokenInterface`, žádný `AuthorizationChecker`, žádný `UserInterface`. Třídu lze proto testovat unit testem bez Symfony Kernel. Selhání hlásí doménovými výjimkami: `InvalidOrderStateException` a `CancellationWindowExpiredException` jsou doménové třídy v `App\Ordering\Domain\Exception`. Nesou doménový kontext (kdy byl order placed, kdy se zkouší cancel) a aplikační vrstva je překládá na HTTP status – typicky 409 Conflict, ne 403 Forbidden, protože *není to autorizační selhání, je to doménový stav*.

Pomocná metoda `isCancellable()` je dotaz bez vedlejších efektů. Používá ji UI vrstva pro skrytí tlačítka: Twig šablona ji volá s proměnnou `now` předanou z controlleru (kombinováno s `is_granted`). Tatáž logika je sdílená s `cancel()` přes konstantu `CANCELLATION_WINDOW_SECONDS` – žádná duplicita. Zbývají domain events: po úspěšné operaci agregát zaznamená `OrderCancelled` voláním `record()`, aplikační handler eventy po `repository->save()` vyzvedne přes `releaseEvents()` a publikuje (typicky přes [Outbox](/outbox-pattern)). Aggregate sám nikdy nevolá `EventDispatcher`.

Zde tedy **není** otázka „smí Petr“ – tu vyřešil Voter v [sekci 11.04](#use-case-voter). Zde je otázka *„dá se to vůbec teď udělat?“*. A odpověď „ne“ se sem dostane i v případě, že Voter řekl „ano“ (Petr je vlastník, ale order je už zaplacen a odeslán). Obě bariéry jsou nezávislé a nutné.

### End-to-end trace: cancellation request {#aggregate-trace-heading}

Pro úplnost si projděme, co se konkrétně stane, když zákazník Petr klikne na tlačítko „Cancel order #42“ v rozhraní:

1. **Edge (firewall).** Symfony ověří JWT/session token. Bez ověření → 401. Petr je přihlášený, pokračuje.
2. **Edge (access_control).** URL `/order/42/cancel` spadá pod `IS_AUTHENTICATED_FULLY`. Petr je přihlášený, pokračuje.
3. **Controller** validuje vstup (CSRF token, request body), vytvoří `CancelOrderCommand(orderId: 42, reason: 'changed mind', actorId: <Petrovo CustomerId>)` a předá ho na message bus.
4. **Application Handler** (CancelOrderHandler) načte agregát z repository: `$order = $repo->getOrFail(42)`.
5. **Use Case Voter.** Handler volá `$auth->isGranted('order.cancel', $order)`. OrderVoter se zeptá agregátu přes `$order->isOwnedBy($user->customerId())`. Petr je vlastník → ACCESS_GRANTED, pokračuje. *Kdyby nebyl vlastník → AccessDeniedDomainException → HTTP 403.*
6. **Aggregate.** Handler volá `$order->cancel('changed mind', $now)`. Aggregate ověří `status === PLACED` a `age <= 24h`. Order je placed před 30 min → ok, status se změní na CANCELLED, vznikne OrderCancelled event. *Kdyby byl už shipped → InvalidOrderStateException → HTTP 409.*
7. **Persistence + outbox.** Handler zavolá `$repo->save($order)`; v jedné transakci se uloží stav agregátu i OrderCancelled event do outbox tabulky.
8. **Field-level (response).** Controller vrátí 200 OK. Pokud by Petr nebyl admin a v response figuroval `audit_log`, read model by ho vyfiltroval – na svém vlastním orderu vidí status, ale ne kdo a kdy ho editoval.

Každá z těchto vrstev selže po svém: jiný HTTP status, jiná chybová hláška, jiné logy. Generické „Access denied“ tu nestačí.

:::callout{type="note"}
### 403 vs. 409: která chyba kdy? {#aggregate-403-vs-409-heading}

Drobnost s velkým UX dopadem. Když Voter řekne „ne“ (Petr není vlastník), aplikace má vrátit **HTTP 403 Forbidden** – autentizovaný uživatel, ale nedostatečné oprávnění. Když aggregate řekne „ne“ (order už není v PLACED), je to **HTTP 409 Conflict** – uživatel má právo, ale stav prostředku to neumožňuje. Aplikační vrstva má dva různé handlery výjimek: `AccessDeniedDomainException → 403`, `InvalidOrderStateException → 409`. UI tak může zobrazit smysluplnou hlášku („Tento order už nelze stornovat – byl odeslán“) místo generického „Access denied“.

Třetí volbou je **404 Not Found** místo 403. Odpověď 403 nad cizím identifikátorem totiž potvrdí, že takový záznam existuje, a útočníkovi stačí projít rozsah ID, aby zmapoval cizí data. Symfony na to má `#[IsGranted(..., statusCode: 404)]`. Trade-off je čitelnost chyby: uživatel, který přišel o oprávnění legitimně, uvidí „stránka neexistuje“ a nepozná proč. Rozumné dělení je 404 pro veřejně dostupné endpointy s uhodnutelnými identifikátory, 403 uvnitř administrace, kde jsou všichni aktéři důvěryhodní.
:::

## 11.07 Field-level – read model filtrace {#field-level}

Nejjemnější vrstva. Předchozí tři vrstvy řešily *akce* a *existenci* operace; field-level řeší **viditelnost konkrétního pole** během jinak povoleného čtení. Klasický příklad: detail orderu vidí customer i admin, ale sloupec `audit_log` (kdo a kdy editoval) má vidět jen admin.

Existují dva přístupy s odlišnými kompromisy:

### Přístup 1: Twig if (view-level) {#field-twig-heading}

Nejjednodušší, ale s *únikem dat*: data se z databáze načtou všechna, jen se ve view zahodí. Pro většinu UI to stačí; na citlivá data nepatří – unikají přes HTML komentáře, JSON serializaci v JS aplikaci nebo ETag hashing.

:::code{language="twig" filename="templates/order/detail.html.twig" highlights="7,8,9,10,11,12,13,14,15,16"}
{# templates/order/detail.html.twig #}
<dl>
    <dt>Customer</dt> <dd>{{ order.customer.name }}</dd>
    <dt>Total</dt>    <dd>{{ order.total|format_currency('CZK') }}</dd>
    <dt>Status</dt>   <dd>{{ order.status.label }}</dd>

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

Citlivá pole se z databáze *vůbec nenačtou*. Read model vrací různé DTO podle role. Bez data leaku, ale za cenu duplicity (dvě query, dvě DTO struktury). Vhodné pro PII, finanční data, audit logy.

:::code{language="php" filename="src/Ordering/Application/ReadModel/OrderDetailReadModel.php" highlights="16,17,18,19,20,21,22"}
// src/Ordering/Application/ReadModel/OrderDetailReadModel.php
declare(strict_types=1);

namespace App\Ordering\Application\ReadModel;

use App\Identity\Infrastructure\Security\SecurityUser;
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
        $columns   = 'id, customer_id, total_cents, status, placed_at';
        $seesAudit = $this->auth->isGrantedForUser($user, 'ROLE_ADMIN');

        if ($seesAudit) {
            $columns .= ', audit_log';
        }

        $sql = "SELECT {$columns} FROM orders WHERE id = :id";

        $row = $this->db->fetchAssociative($sql, ['id' => $orderId]);
        if ($row === false) {
            throw new OrderNotFoundException($orderId);
        }

        return OrderDetailDto::fromRow($row, includeAudit: $seesAudit);
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

Pro necitlivá data Twig if stačí a šetří čas. Pro citlivá data vždy query filter – OWASP Top 10 v kategorii „A01 Broken Access Control“ výslovně varuje před UI-only kontrolou jako jedinou bariérou.

### Seznamy: Voter na otázku „které objekty smí?“ neodpoví {#field-list-filtering}

Voter odpovídá na uzavřenou otázku: *smí tento uživatel tento konkrétní objekt?* Endpoint se seznamem ale potřebuje otázku opačnou: *které objekty z deseti tisíc smí vidět?* Rozdíl vypadá formálně. V praxi je to den, kdy tým narazí na strop celého rámce postaveného na Voterech.

Naivní řešení načte stránku výsledků a přefiltruje ji v PHP:

:::code{language="twig" filename="templates/order/list.html.twig (anti-vzor)"}
{# templates/order/list.html.twig (anti-vzor) #}
{% for order in orders %}
    {% if is_granted('order.view', order) %}
        <tr><td>{{ order.id }}</td><td>{{ order.status.label }}</td></tr>
    {% endif %}
{% endfor %}
:::

Dvě věci se rozbijí naráz. Stránkování přestane sedět: dotaz vrátí 20 řádků, filtr jich zahodí 7 a uživatel uvidí stránku o třinácti položkách. Celkový počet nikdo nespočítá, dokud nenačte všechno. Výkon jde přitom dolů lineárně: každý řádek spustí rozhodovací proces přes všechny registrované Votery, takže dvacet řádků a pět Voterů znamená sto rozhodnutí na jedno vykreslení. Bez override `supportsAttribute()` a `supportsType()` (viz [implementační detaily Voteru](#use-case-voter)) se z toho počtu neubere nic.

Odpověď je přesunout autorizaci do dotazu. Read model dostane identitu aktéra a promítne ji do `WHERE`:

:::code{language="php" filename="src/Ordering/Application/ReadModel/OrderListReadModel.php" highlights="19,20,21,22,23"}
// src/Ordering/Application/ReadModel/OrderListReadModel.php
declare(strict_types=1);

namespace App\Ordering\Application\ReadModel;

use App\Identity\Domain\CustomerId;
use Doctrine\DBAL\Connection;

final readonly class OrderListReadModel
{
    public function __construct(private Connection $db) {}

    /** @return list<array<string, mixed>> */
    public function visibleTo(CustomerId $actor, bool $isAdmin, int $limit, int $offset): array
    {
        $sql = 'SELECT id, status, total_cents, placed_at FROM orders';
        $params = ['limit' => $limit, 'offset' => $offset];

        // Autorizace je součástí dotazu, ne postprocessingu
        if (!$isAdmin) {
            $sql .= ' WHERE customer_id = :actor';
            $params['actor'] = $actor->toString();
        }

        $sql .= ' ORDER BY placed_at DESC LIMIT :limit OFFSET :offset';

        return $this->db->fetchAllAssociative($sql, $params);
    }
}
:::

Podmínka v `WHERE` je ale *druhý zápis* téhož pravidla, které už zná `OrderVoter`. Jednu definici tu udržet nelze, protože SQL a PHP jsou různé jazyky – co ale lze, je pojmenovat vazbu explicitně. Osvědčuje se držet obojí v jedné třídě nebo alespoň v jednom adresáři, doplnit komentář s odkazem na Voter a hlavně přidat test, který ověří shodu: vyjmenuje objednávky vrácené read modelem a na každé z nich zkontroluje, že Voter řekne ano. Rozejdou-li se, test spadne.

Modely vzniklé kolem Zanzibaru toto rozdělení pojmenovávají přímo: `Check` je otázka na jeden objekt, `ListObjects` vrací množinu. Symfony 8 nativní podporu pro druhou z nich nemá – Voter je dobrý *Policy Enforcement Point* a nic víc si nenárokuje. Detail v [sekci o ReBAC](#rebac).

## 11.08 Policy-based přístup (ABAC) {#policy-based}

**RBAC** (Role-Based Access Control) se ptá na roli. **ABAC** (Attribute-Based Access Control) vyhodnocuje kombinaci atributů subjektu, akce, prostředku a kontextu proti policy a vrátí povoleno / zakázáno. Přechod od prvního ke druhému nepohání počet pravidel, ale tři kvalitativní signály: policy musí být čitelná pro někoho mimo vývojový tým, mění se v jiném rytmu než kód, nebo ji sdílí víc než jedna aplikace. Dokud neplatí ani jeden z nich, Votery stačí a přidaná abstrakce je jen práce navíc.

NIST SP 800-162 dává pro tuto vrstvu slovník, který se vyplatí znát, protože ho používají externí enginy [[2]](https://csrc.nist.gov/publications/detail/sp/800-162/final). **PEP** (Policy Enforcement Point) je místo, kde se rozhodnutí vynutí – v Symfony `access_control`, `#[IsGranted]` a volání `isGranted()` v handleru. **PDP** (Policy Decision Point) je místo, kde rozhodnutí padne – u nás rozhodovací manažer s Votery, případně vzdálený engine. **PIP** (Policy Information Point) dodává atributy, **PAP** (Policy Administration Point) policy spravuje. Rámec čtyř vrstev z [11.02](#ctyri-vrstvy) je tedy rozmístění PEP; PDP zůstává jeden.

Následující ukázka staví ABAC model explicitně: `Policy` jako kolekce `Rule` objektů, které se vyhodnotí proti trojici subject/user/context. Slouží k tomu, aby byl model vidět. Zda se takto psát vyplatí, řeší [závěr sekce](#abac-vlastni-vs-voter) – odpověď zní ve většině Symfony projektů „ne“.

:::code{language="php" filename="src/SharedKernel/Authorization/Policy.php"}
// src/SharedKernel/Authorization/Policy.php
declare(strict_types=1);

namespace App\SharedKernel\Authorization;

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
                expression:  'subject.customerId == user.customerId',
                description: 'Pouze vlastník objednávky',
            ),
            new Rule(
                expression:  'subject.status.value == "placed"',
                description: 'Order musí být ve stavu PLACED',
            ),
            new Rule(
                expression:  'subject.placedAt.getTimestamp() >= now - 86400',
                description: 'Cancellation window 24 h ještě neuplynulo',
            ),
            new Rule(
                expression:  'user.tenantId == subject.tenantId',
                description: 'Stejný tenant',
            ),
        ];
    }
}
:::

Zápis výrazů má svá úskalí a chyba se projeví až za běhu. ExpressionLanguage čte veřejné properties a volá veřejné metody – gettery k privátním polím nedohledá, subjektem politiky proto bývá snapshot s veřejnými poli, ne agregát s privátním stavem. Odečíst `DateTimeImmutable` od čísla komponenta neumí: datum se převádí na unixový timestamp metodou objektu (`subject.placedAt.getTimestamp()`) a `now` přichází jako číslo z proměnných evaluatoru, ne jako objekt. Backed enum se neporovnává přímo – `subject.status == "placed"` selže, srovnává se hodnota přes `subject.status.value`. A protože výrazy jsou stringy, statická analýza je nevidí; každé pravidlo musí krýt test, viz [tabulkové testy policy](#testing-policy-heading).

Poznámka: pravidla `subject.status.value == "placed"` a časové okno 24 h jsou v politice pro ilustraci ABAC zápisu. Jak popisuje sekce 11.06, tyto doménové invarianty patří primárně do agregátu. Politika je ověřuje jako pre-check před dosažením domény (obrana do hloubky). Agregát ale musí být zdrojem pravdy a nepřijmout neplatný příkaz ani bez autorizační vrstvy.

Jednoduchý `PolicyEvaluator` používá Symfony ExpressionLanguage komponentu a vyhodnocuje pravidla v daném kontextu:

:::code{language="php" filename="src/SharedKernel/Authorization/PolicyEvaluator.php"}
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
| Subjekt | musí být snapshot s veřejnými poli | libovolný objekt |
| Kompozice hlasů | vlastní kód | `AccessDecisionManager` a strategie |

Poslední dva řádky jsou skrytá cena, kterou tabulky výhod obvykle zamlčují. ExpressionLanguage čte veřejné properties, takže agregát s privátním stavem subjektem politiky být nemůže – vzniká další model, který musí zůstat v synchronizaci s doménou. A hlasy voličů skládá vlastní evaluátor místo rozhodovacího manažeru, takže se strategiemi z [11.04](#access-decision) nepočítá.

### Vlastní evaluátor, nebo Voter s `Vote`? {#abac-vlastni-vs-voter}

Hlavní argument pro vlastní `PolicyEvaluator` býval jediný: chceme vědět, *které* pravidlo selhalo, ne jen že přístup nebyl povolen. Od Symfony 7.3 to umí Security komponenta sama. Voter přijímá volitelný parametr `?Vote $vote` a může do něj zapsat důvod, aplikační vrstva pak čte celé rozhodnutí přes `Security::getAccessDecision()`:

:::code{language="php" filename="src/Ordering/Infrastructure/Security/OrderVoter.php (s důvody)" highlights="10,15,20"}
// src/Ordering/Infrastructure/Security/OrderVoter.php (s důvody)
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

Důvody se čtou z veřejné vlastnosti `$vote->reasons` (pole stringů); getter třída `Vote` nemá. Aplikační vrstva je vytáhne z `AccessDecision` a předá do chybové odpovědi:

:::code{language="php" filename="src/Ordering/Infrastructure/Http/ExplainedAccessDenied.php"}
// src/Ordering/Infrastructure/Http/ExplainedAccessDenied.php
$decision = $this->security->getAccessDecision(OrderVoter::CANCEL, $order);

if (!$decision->isGranted()) {
    $reasons = [];
    foreach ($decision->votes as $vote) {
        // Vote::$reasons je veřejná vlastnost, ne getter
        $reasons = array_merge($reasons, $vote->reasons);
    }

    throw new AccessDeniedDomainException(implode(' ', $reasons));
}
:::

Ukázka záměrně kontroluje i stav agregátu, aby bylo vidět, co se získá. Pravidlo ale zůstává definované v `Order::isCancellable()`; Voter ho volá, neopisuje.

Závěr pro Symfony 8: vlastní vrstvu `Policy`/`Rule` stavět nemá smysl. Dá tytéž odpovědi jako Votery, ale bez statické analýzy, bez rozhodovacích strategií a s modelem navíc. ABAC model z této sekce zůstává užitečný jako *způsob uvažování* o pravidlech – implementuje se ale z Voterů. Externí engine přichází na řadu až tehdy, když policy musí žít mimo aplikaci: sdílí ji víc služeb, spravuje ji jiný tým, nebo ji auditor kontroluje nezávisle na deploy cyklu. Tehdy dává smysl OPA s jazykem Rego nebo Cerbos, a Voter se stane tenkým PEP, který se ptá vzdáleného PDP. Rozhraní mezi nimi standardizuje AuthZEN Authorization API 1.0, schválené v lednu 2026 [[9]](https://openid.net/wg/authzen/).

### ReBAC: když je oprávnění vztah, ne atribut {#rebac}

Za ABAC nekončí cesta u OPA. Průmyslový posun posledních let míří k **ReBAC** (Relationship-Based Access Control), kde se přístup odvozuje ze vztahů mezi uživateli a objekty *a mezi objekty navzájem*. Typická otázka zní: „uživatel vidí dokument, pokud má přístup k jeho nadřazené složce“. RBAC se na takové hierarchii, sdílení a multi-tenancy láme – vznikají role pro každou kombinaci, nebo se logika rozpadne do Voterů.

Referenčním modelem je **Zanzibar**, autorizační systém Googlu popsaný na USENIX ATC '19. Ukládá vztahy jako trojice `objekt#relace@uživatel`, konfiguraci vztahů popisuje vlastním jazykem namespace a konzistenci řeší tokeny zvanými zookies. Provozní čísla z paperu dávají měřítko: biliony ACL záznamů, miliony autorizačních dotazů za sekundu, p95 latence pod 10 ms. Otevřené implementace téhož modelu jsou dnes dvě – **OpenFGA** (projekt CNCF) a **SpiceDB** od Authzed se schema jazykem, který rozlišuje zapsané vztahy a počítaná oprávnění.

:::callout{type="warn"}
### PHP klienta prakticky nemáte {#rebac-php-heading}

Ekosystém kolem ReBAC zatím PHP vynechává. Oficiální SDK OpenFGA existují pro Node.js, Go, .NET, Python a Javu; PHP mezi nimi není. Komunitní `evansims/openfga-php` je na Packagistu označený jako abandoned. Pro SpiceDB žádný PHP balíček není. Cerbos má oficiální `cerbos/cerbos-sdk-php`, ale s adopcí v řádu tisíců stažení. Kdo chce v Symfony projektu ReBAC engine, napíše si HTTP nebo gRPC klienta sám a bude ho udržovat sám. Než se do toho pustíte, stojí za zvážení, jestli problém opravdu vyžaduje graf vztahů, nebo jestli stačí tabulka vazeb a Voter, který se jí ptá.
:::

:::callout{type="pattern"}
### RBAC vs. ABAC: kdy přejít? {#abac-vs-rbac-heading}

RBAC stačí, dokud platí *„role popisuje oprávnění sama o sobě“* – admin smí všechno, zákazník smí svoje, refund agent smí refundy. Jakmile oprávnění závisí na *vztazích mezi entitami* (tenant, vlastnictví, časové okno, stavový automat), RBAC začne nekontrolovaně narůstat. Buď vznikají hyper-specific role typu `ROLE_TENANT_42_ORDER_REFUND_AGENT`, nebo se logika rozpadne do Voterů s 200 řádky if-else. Tehdy je čas začít uvažovat v ABAC pojmech – atributy místo rolí. Implementace v Symfony 8 přitom zůstává ve Voterech; vlastní policy vrstva se nevyplatí, dokud policy nemusí žít mimo aplikaci.
:::

## 11.09 Multi-tenancy – tenant kontext {#multi-tenancy}

Multi-tenancy (vícenájemnost) je speciální případ ABAC, kdy stejná aplikace obsluhuje více *oddělených zákazníků* (organizací, mandantů, tenantů) a žádný tenant nesmí vidět data jiného. Existují tři architektonické strategie:

- **Row-based** – sdílená databáze, sdílené tabulky, sloupec `tenant_id` všude. Nejlevnější, nejméně izolace, vyžaduje pečlivé filtry.
- **Schema-based** – sdílená databáze, samostatné schema per tenant (PostgreSQL `SET search_path`). Střední izolace, lepší performance než row-based.
- **Database-based** – samostatná databáze per tenant. Nejvyšší izolace, nejnákladnější (DB connection per tenant, migrations × N).

Volba mezi nimi jde ruku v ruce s velikostí instalace: row-based u SaaS s velkým počtem malých tenantů, schema-based tam, kde je potřeba oddělit zálohy a migrace per tenant, database-based v regulovaných doménách. Rozhodovací kritérium je pokaždé stejné – jak drahá je chyba, když se dva tenanty potkají v jedné odpovědi. Pro row-based v Symfony je idiomatický nástroj **Doctrine SQLFilter**.

Namístě je vrátit se k [chybě 3](#tri-chyby-doctrine-heading), kde jsme filtrování v perzistentní vrstvě označili za anti-vzor. Rozpor je zdánlivý a rozdíl je v tom, na co filtr odpovídá. Tenant není autorizační rozhodnutí o akci, je to **kontext dotazu** – dimenze, kterou má nést každý dotaz v požadavku, stejně jako jazyk nebo časová zóna. Autorizační rozhodnutí „Petr smí zrušit objednávku #42“ do SQL nepatří, protože handler pak nerozezná neexistující záznam od cizího. Otázka „ke kterému tenantovi tento request patří“ do SQL patří, protože odpověď je pro celý request jediná a neměnná.

:::code{language="php" filename="src/SharedKernel/Infrastructure/Doctrine/TenantFilter.php" highlights="13,14,15,16,17,18,19,20,21,22"}
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

Filter aplikuje WHERE klauzuli `tenant_id = ?` na každý dotaz nad entitou, která implementuje marker rozhraní `TenantAware`. Aktivace filtru v `config/packages/doctrine.yaml`:

:::code{language="yaml" filename="config/packages/doctrine.yaml"}
# config/packages/doctrine.yaml
doctrine:
    orm:
        filters:
            tenant:
                class:   App\SharedKernel\Infrastructure\Doctrine\TenantFilter
                enabled: true  # fail-closed: filter běží vždy, parametr dodá listener
:::

Pozor na sémantiku výchozího stavu. Vypnutý nebo nenakonfigurovaný filter nepřidá do SQL žádné WHERE – dotaz vrátí data všech tenantů. SQLFilter je tedy ze své podstaty *fail-open* a to je hlavní riziko celého přístupu. Proto konfigurace výše filter zapíná globálně (`enabled: true`): běží pro každý dotaz a chybějící `tenant_id` skončí výjimkou, ne únikem dat. Hodnotu parametru dodává kernel event listener po autentizaci:

Jedna mezera zůstane i pak. Filtr nepokrývá načtení **neowning strany asociace one-to-one** –
měřeno na ORM 3.6 vrátí druhý konec vztahu i záznam cizího tenanta, přestože týž záznam přes
`find()` nebo DQL nedostanete. Tam, kde na oddělení tenantů závisí bezpečnost, patří kontrola
tenanta i do doménové vrstvy, ne jen do filtru.

:::code{language="php" filename="src/SharedKernel/Infrastructure/Http/TenantContextListener.php" highlights="13,22,23,24,25,26,27,28,29,30,31,32,33,34"}
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

        $tenantId = $user->tenantId()->toString();
        $filter   = $this->em->getFilters()->enable('tenant');
        $filter->setParameter('tenant_id', $tenantId);
    }
}
:::

Volání `enable('tenant')` v listeneru je u globálně zapnutého filtru neškodné – vrátí existující instanci, na kterou stačí nastavit parametr.

Tři detaily, které se vyplatí zachytit:

- **Priority 7** v `AsEventListener` – v Symfony platí *vyšší priority = dřívější vykonání*. Symfony Firewall registruje svůj `onKernelRequest` s prioritou 8, takže aby měl listener k dispozici už autentizovaného uživatele, musí běžet s prioritou *nižší než 8* (typicky 7 nebo 0). Detail v [Symfony EventDispatcher dokumentaci](https://symfony.com/doc/current/event_dispatcher.html).
- **Main request guard.** Bez `$event->isMainRequest()` by se filter nastavoval i pro dílčí požadavky (např. ESI, render fragments) – tam typicky není token a listener by spadl.
- **Anonymní požadavek parametr nedostane.** U veřejných endpointů (login, register, health) listener skončí na guardu a `tenant_id` zůstane nenastavené. První dotaz nad `TenantAware` entitou pak vyhodí výjimku – globálně zapnutý filter bez parametru dotaz nepustí. Hlučné selhání je tu záměr: veřejný endpoint nemá tenantní data co číst. Pokud je přesto čte, patří takový požadavek odmítnout už na firewallu.

:::callout{type="warn"}
### Fail-closed se musí vyrobit, samo nevznikne {#multi-tenancy-fail-open-heading}

Častý omyl: „bez aktivního filtru se tenantní data prostě nevrátí“. Opak je pravdou – bez filtru se vrátí *všechna*, napříč tenanty. Fail-closed chování stojí na třech opatřeních. Filter běží globálně (`enabled: true`), ne až po aktivaci v listeneru; zapomenutá aktivace pak neznamená únik dat, ale výjimku. Parametr `tenant_id` je povinný – Doctrine ho při sestavování dotazu vyžaduje a bez něj selže. A požadavek bez známého tenanta (anonymní request, CLI command, Messenger worker) má skončit dřív, na firewallu nebo v listeneru; kde odmítnutí nedává smysl, poslouží nemožná hodnota `tenant_id`, které neodpovídá žádný řádek. CLI a worker procesy kernel listener neobslouží – tenant context tam nastavuje Messenger middleware nebo samotný command, jinak první dotaz spadne.
:::

:::callout{type="warn"}
### Pozor: filter neaplikuje na native SQL ani Redis {#multi-tenancy-warn-heading}

Doctrine SQLFilter modifikuje SQL generované ORM – DQL/QueryBuilder, `EntityManager::find()` i lazy loading kolekcí. Pokud aplikace volá `$conn->executeQuery('SELECT ...')`, používá Redis, Elasticsearch nebo externí HTTP API, *žádný filter se neaplikuje*. V těchto místech musíte tenant_id přidat ručně. V code review stojí za pozornost anti-vzor: surové SQL bez tenant_id v `WHERE`. Statická analýza (vlastní PHPStan pravidlo nebo PHPArkitect) umí takové query odhalit.

Filtr neúčinkuje ani na entity, které už leží v identity map – ty se vracejí tak, jak byly načteny, a obnovení vyžaduje vyčištění EntityManageru. A pro dočasné vypnutí (admin dotaz, migrace, cross-tenant report) použijte `suspend()` a `restore()`, ne `disable()`. `disable()` zahodí celou instanci filtru včetně nastavených parametrů a po `enable()` je musí někdo nastavit znovu; zapomenutý parametr pak shodí první dotaz, v horším případě běží kód dál bez izolace.
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

Aplikace pak před dotazy nastaví proměnnou spojení – `SET app.tenant_id = '…'` ve stejném listeneru, který plní Doctrine filtr. Rozdíl proti SQLFilteru je v defaultu a ten rozhoduje. Po `ENABLE ROW LEVEL SECURITY` platí na tabulce default-deny: bez politiky se nevrátí nic. SQLFilter je naopak fail-open a fail-closed chování se musí vyrobit ručně, jak popisuje předchozí callout. Cenou za RLS je vázanost na PostgreSQL, obtížnější ladění (dotaz vrátí prázdno a nikde není proč) a role s atributem `BYPASSRLS`, kterou potřebují migrace a zálohy. Obě vrstvy se nevylučují: filtr drží čitelné chování v ORM, RLS je poslední záchytná síť.

## 11.10 Test pyramida pro autorizaci {#testing}

Každá ze 4 vrstev se testuje jiným druhem testu – a snaha pokrýt vše end-to-end vede k pomalé, křehké testovací sadě. Dělení odpovídá klasické *test pyramidě*: hodně rychlých unit testů, méně integration, pár end-to-end.

### Aggregate-level: čistý unit test {#testing-aggregate-heading}

Doménová pravidla v aggregate jsou plain PHP – žádný framework, žádná databáze. Test je rychlý a deterministický:

:::code{language="php" filename="tests/Ordering/Domain/OrderCancelTest.php"}
// tests/Ordering/Domain/OrderCancelTest.php
declare(strict_types=1);

namespace Tests\Ordering\Domain;

use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\Exception\CancellationWindowExpiredException;
use App\Ordering\Domain\Exception\InvalidOrderStateException;
use App\Ordering\Domain\Order;
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

        $this->expectException(InvalidOrderStateException::class);
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
// tests/Ordering/Infrastructure/Security/OrderVoterTest.php
declare(strict_types=1);

namespace Tests\Ordering\Infrastructure\Security;

use App\Identity\Domain\CustomerId;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Ordering\Infrastructure\Security\OrderVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoterTest extends TestCase
{
    public function testOwnerCanCancelOwnOrder(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString('cus_1'));

        self::assertSame(
            Voter::ACCESS_GRANTED,
            $this->voteCancel($order, ownedBy: 'cus_1', actor: 'cus_1')
        );
    }

    public function testStrangerCannotCancelOrder(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString('cus_1'));

        self::assertSame(
            Voter::ACCESS_DENIED,
            $this->voteCancel($order, ownedBy: 'cus_1', actor: 'cus_2')
        );
    }

    private function voteCancel(object $order, string $ownedBy, string $actor): int
    {
        $decisions = $this->createMock(AccessDecisionManagerInterface::class);
        $decisions->method('decide')->willReturn(false); // aktér nemá žádnou roli navíc

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(SecurityUserFixture::for($actor));

        return (new OrderVoter($decisions))->vote($token, $order, [OrderVoter::CANCEL]);
    }
}
:::

Mock `AccessDecisionManagerInterface` je tu záměrně nastavený na `false`. Test tak ověřuje vlastnictví bez zásahu rolí; pro admin scénář stačí druhý test s návratovou hodnotou `true`.

### End-to-end: WebTestCase {#testing-e2e-heading}

Pro pokrytí celé pipeline (firewall → controller → handler → voter → aggregate) slouží Symfony `WebTestCase`. Zde už je to integrační test, který používá kernel a databázi. Doporučená míra: *1 e2e test na use case*, pokrývající hlavní scénář + 1–2 nejdůležitější chybové stavy. Detailní pokrytí okrajových případů patří do unit testů na nižších vrstvách.

Přihlášení se v takovém testu neprochází formulářem. `KernelBrowser::loginUser()` vloží uživatele rovnou do session a ušetří jeden request i závislost na podobě login stránky:

:::code{language="php" filename="tests/Ordering/Http/CancelOrderE2eTest.php" highlights="12,13,14,17"}
// tests/Ordering/Http/CancelOrderE2eTest.php
declare(strict_types=1);

namespace Tests\Ordering\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CancelOrderE2eTest extends WebTestCase
{
    public function testStrangerGetsNotFound(): void
    {
        $client   = static::createClient();
        $stranger = static::getContainer()
            ->get(SecurityUserRepository::class)
            ->byEmail('petr@example.com');

        $client->loginUser($stranger);
        $client->request('POST', '/order/' . self::FOREIGN_ORDER_ID . '/cancel');

        // #[IsGranted(..., statusCode: 404)] brání enumeraci cizích ID
        self::assertResponseStatusCodeSame(404);
    }
}
:::

### Architektonický test: doména bez Security komponenty {#testing-architecture-heading}

Anti-vzor 4 zakazuje závislost domény na `Symfony\Component\Security`. Pravidlo, které hlídá jen code review, se dřív nebo později poruší – proto ho má vynucovat test. S PHPArkitect stačí jedno pravidlo:

:::code{language="php" filename="tests/Architecture/DomainRules.php"}
// tests/Architecture/DomainRules.php
Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
    ->should(new NotDependsOnTheseNamespaces('Symfony\Component\Security'))
    ->because('doménový model nesmí znát autorizační infrastrukturu');
:::

Test běží v CI vedle unit testů a selže při prvním importu, ne až při refaktoringu za rok. Detail pyramidy, příklady fixture builderů i další architektonická pravidla v [samostatné kapitole o testování](/testovani-ddd).

### Policy: tabulkový unit test {#testing-policy-heading}

Pokud používáte [policy-based přístup](#policy-based), každé pravidlo v policy je jeden test case. Tabulkový (data provider) test je nejlepší forma – jeden řádek = jeden scénář, čitelně i pro netechnického reviewera:

:::code{language="php" filename="tests/Ordering/Authorization/CancelOrderPolicyTest.php"}
// tests/Ordering/Authorization/CancelOrderPolicyTest.php
declare(strict_types=1);

namespace Tests\Ordering\Authorization;

use App\Ordering\Authorization\CancelOrderPolicy;
use App\SharedKernel\Authorization\PolicyContext;
use App\SharedKernel\Authorization\PolicyEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CancelOrderPolicyTest extends TestCase
{
    public static function scenarios(): iterable
    {
        yield 'happy path' => [
            'subject'  => OrderFixture::placedFor('cus_1', 'tenant_a', minutesAgo: 30),
            'user'     => UserFixture::for('cus_1', 'tenant_a'),
            'expected' => null,
        ];
        yield 'wrong customer' => [
            'subject'  => OrderFixture::placedFor('cus_1', 'tenant_a', minutesAgo: 30),
            'user'     => UserFixture::for('cus_2', 'tenant_a'),
            'expected' => 'Pouze vlastník objednávky',
        ];
        yield 'shipped order' => [
            'subject'  => OrderFixture::shippedFor('cus_1', 'tenant_a'),
            'user'     => UserFixture::for('cus_1', 'tenant_a'),
            'expected' => 'Order musí být ve stavu PLACED',
        ];
        yield 'window expired' => [
            'subject'  => OrderFixture::placedFor('cus_1', 'tenant_a', minutesAgo: 1500),
            'user'     => UserFixture::for('cus_1', 'tenant_a'),
            'expected' => 'Cancellation window 24 h ještě neuplynulo',
        ];
        yield 'cross-tenant' => [
            'subject'  => OrderFixture::placedFor('cus_1', 'tenant_a', minutesAgo: 30),
            'user'     => UserFixture::for('cus_1', 'tenant_b'),
            'expected' => 'Stejný tenant',
        ];
    }

    #[DataProvider('scenarios')]
    public function testEvaluate(object $subject, object $user, ?string $expected): void
    {
        $evaluator = new PolicyEvaluator();
        $context   = new PolicyContext($subject, $user, new \DateTimeImmutable());

        $violation = $evaluator->evaluate(new CancelOrderPolicy(), $context);

        self::assertSame($expected, $violation?->description);
    }
}
:::

Tabulkový test má dvě výhody navíc oproti klasickému test-per-method přístupu. Přidání pravidla = přidání jednoho řádku v `scenarios()`. A celý test slouží jako *spustitelná dokumentace policy* – reviewer mimo vývojový tým vidí všechny případy v jedné tabulce a může schválit doménová pravidla.

## 11.11 Anti-vzory {#antivzory}

Čtyři anti-vzory následují strukturu „symptom – důsledek – náprava“. První dva shrnují to, co kapitola už rozebrala, aby se v code review dalo projít celý seznam na jednom místě.

### Anti-vzor 1: Autorizace v controlleru {#anti-controller-heading}

Probrali jsme v sekci [11.01](#tri-chyby). Symptom: stejná autorizační podmínka opakovaná v 3+ controllerech, neexistující ve verzích volaných z konzolového commandu nebo Messenger handleru. Náprava: přesun do Voteru + volání `AuthorizationCheckerInterface` v Application Service. Souvisí: [obecné anti-vzory v DDD](/anti-vzory).

### Anti-vzor 2: Voter, který si načte vlastní subjekt {#anti-fetching-voter-heading}

Rozebráno v [calloutu u Voteru](#voter-anti-fetching-heading). Symptom: konstruktor Voteru přijímá repository a `voteOnAttribute()` volá `find($subject)` nad ID, které dostal místo objektu. Důsledek: druhý dotaz na tutéž entitu a rozhodování nad stavem, který se mezitím mohl změnit. Náprava: handler načte entitu jednou a předá ji do `isGranted()`. Doplňková data mimo subjekt (členství, delegace) si Voter načíst smí – zákaz míří na subjekt, ne na všechny dotazy.

### Anti-vzor 3: Voter == Aggregate logic {#anti-duplication-heading}

Symptom: cancellation window pravidlo („order ne starší než 24 h“) je zapsané *jak* ve Voteru, *tak* v `Order::cancel()`. Když se doménové pravidlo změní (např. window se prodlouží na 48 h), obě místa se musí upravit – a typicky se zapomene jedno.

Náprava: pravidlo patří do aggregate (je to doménový invariant). Voter **nesmí** ověřovat doménový stav agregátu – odpovídá jen na identitu/role uživatele a vlastnictví subjektu. Pro view-level skrytí tlačítka se v Twigu kombinuje `{% if is_granted(...) and order.isCancellable(now) %}` – voter pro permission, doménová metoda pro stavovou kontrolu.

### Anti-vzor 4: Symfony User natažený do doménového Aggregate {#anti-symfony-user-domain-heading}

Symptom:

:::code{language="php" filename="src/Ordering/Domain/Order.php (anti-vzor)" highlights="5,10,11,12"}
// src/Ordering/Domain/Order.php (anti-vzor)
namespace App\Ordering\Domain;

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

Technický důsledek je zřejmý: doména závisí na `Symfony\Component\Security`, takže stejný kód nespustíte z konzolového commandu, z Messenger workeru ani z unit testu bez Kernelu. Modelový důsledek váží víc. Role a oprávnění jsou slovník *jiné* subdomény – Identity & Access kontextu z [11.02](#ctyri-vrstvy). Jakmile se objeví v `Order::cancel()`, mluví Ordering kontext cizím ubiquitous language a hranice mezi kontexty se rozpouští.

Náprava: doména pracuje s vlastním typem (`CustomerId`, `TenantId`), aplikační handler překládá `SecurityUser` na doménový identifikátor a vynucuje to [architektonický test](#testing-architecture-heading). Detail v [kapitole o anti-vzorech](/anti-vzory).

:::callout{type="warn"}
### Společný jmenovatel anti-vzorů {#anti-summary-heading}

Všechny čtyři anti-vzory vznikají z jediné chyby: *autorizační rozhodnutí se umístilo do nesprávné vrstvy*. Když máte čtyřvrstvý rámec z [11.02](#ctyri-vrstvy) na zřeteli, code review takové chyby odhalí na první pohled.
:::

## 11.12 Shrnutí {#summary}

Autorizace v DDD aplikaci na Symfony 8 sedí na čtyřech vrstvách, každá s vlastním Symfony nástrojem a vlastní granularitou:

- **Edge** – Symfony firewall + `access_control`. Anonymous vs. authenticated, role-based hrubá separace. Žádná doménová znalost.
- **Use Case** – Symfony Voter. „Smí Petr cancelnout order #42?“ Aplikační handler volá `AuthorizationCheckerInterface::isGranted()`; doména to nesmí.
- **Aggregate** – doménový invariant + doménová výjimka. „Order musí být PLACED a ne starší než 24 h.“ Aggregate vyhazuje `InvalidOrderStateException`; aplikační vrstva to mapuje na HTTP 409.
- **Field** – Twig `is_granted` pro view-level (s rizikem data leaku) nebo query filter / read model pro citlivá data (PII, audit log). Seznamy potřebují filtr v dotazu, ne Voter nad každým řádkem.

Kde co řešit:

Hrubé permissions pokryje RBAC. Jakmile pravidla závisí na vztazích mezi entitami, nastupuje uvažování v ABAC pojmech – implementované ale z Voterů, ne z vlastní policy vrstvy. Vícenájemnost řeší Doctrine SQLFilter s kernel listenerem, nastavený fail-closed, a v PostgreSQL k tomu RLS jako záchytná síť pod aplikací. Doménové stavové pravidlo patří do agregátu; vztah aktéra k agregátu (vlastnictví) definuje rovněž agregát a Voter se ho ptá.

Rozhodnutí, kdy z Voterů odejít, nestojí na počtu pravidel. Stojí na třech otázkách: musí být policy čitelná mimo vývojový tým, mění se v jiném rytmu než kód, a sdílí ji víc aplikací? Dokud zní odpověď třikrát ne, zůstávají Votery s `Vote::addReason()` tou levnější variantou. Když aspoň jednou ano, přichází externí engine (OPA, Cerbos) a Voter se stane tenkým vynucovacím bodem.

### Praktický checklist před deploy {#summary-checklist-heading}

Než commitnete autorizační změnu, projděte si těchto devět bodů:

1. Existuje v `access_control` default-deny pravidlo na konci? *Pokud ne – nový endpoint bez explicitní role je veřejný.*
2. Volá Application Handler `$auth->isGranted()` **před** doménovou operací? *Pokud ne – autorizace se může obejít přes alternativní vstupní bod (CLI, Messenger).*
3. Je doménový invariant zapsaný v aggregate, ne ve Voteru? *Pokud ne – pravidlo se obejde přímým voláním aggregate metody mimo handler.*
4. Je rozhodovací strategie nastavená na `unanimous`? *Pokud ne – při výchozí `affirmative` přebije jeden souhlasící Voter všechny nesouhlasící.*
5. Vrací aplikace 403, 404 nebo 409 podle typu selhání? *Pokud ne – uživatel dostane matoucí hlášku, nebo lze enumerovat cizí identifikátory.*
6. Mají citlivá pole (PII, audit) query filter, ne jen Twig if? *Pokud ne – data leakují přes JSON API, dev tools, ETag.*
7. Filtruje endpoint se seznamem v dotazu, ne přes `is_granted()` nad každým řádkem? *Pokud ne – rozpadne se stránkování a výkon klesá lineárně.*
8. Pokud je aplikace multi-tenant: má Doctrine SQLFilter *fail-closed* default? *Pokud ne – chybějící tenant context vrátí všechna data.*
9. Existuje na každé vrstvě alespoň jeden test, včetně architektonického? *Aggregate test, Voter test, e2e test a zákaz importu Security v doméně.*

:::callout{type="pattern"}
### Audit log autorizačních rozhodnutí {#audit-log-heading}

Regulované domény (zdravotnictví, finance, GDPR čl. 30) vyžadují audit log *každého* autorizačního rozhodnutí, ne jen úspěšných operací. Nabízí se dekorátor nad `AuthorizationCheckerInterface` přes `#[AsDecorator(decorates: 'security.authorization_checker')]`. Má to dvě omezení: služba je od Symfony 6.0 privátní, takže se do aplikace dostane jen typehintem přes autowiring, a dekorátor vidí pouze výsledek, ne hlasy jednotlivých voličů.

Přesnější log poskytne vlastní `AccessDecisionStrategyInterface`, případně čtení `AccessDecision` přes `Security::getAccessDecision()`. Tam jsou k dispozici jednotlivé `Vote` objekty s vlastnostmi `$voter`, `$result`, `$reasons` a od Symfony 7.4 i `$extraData` – audit pak zaznamená i to, který volič zamítl a s jakým odůvodněním. Loguje se obvykle do vyhrazeného Monolog channelu `authorization` a odtud do ELK / Loki / centrálního SIEM.
:::

:::faq{}
- question: Mám psát jeden Voter na entitu, nebo víc?
  answer: 'Jeden Voter na entitu, který pokrývá N atributů (VIEW, CANCEL, REFUND, …). V <code>supports()</code> se filtruje podle <code>$subject instanceof Order</code> a podle whitelistu atributů; v <code>voteOnAttribute()</code> se atributy mapují přes <code>match</code> expression na privátní metody. Více Voterů na jednu entitu se vyplatí jen tehdy, když permissions využívají úplně jiný subset závislostí (typicky owner-based vs. role-based) a chcete je nezávisle testovat. Detail v <a href="#use-case-voter">sekci o Voteru</a>.'
- question: Smí Voter načítat aggregate z databáze?
  answer: 'Subjekt, o kterém rozhoduje, ne. Voter ho dostává jako <code>$subject</code>; handler ho už načetl a předává v paměti. Druhé načtení je anti-vzor (<a href="#anti-fetching-voter-heading">11.11</a>) – vede k duplicate query a k rozhodování nad stavem, který se mezitím mohl změnit. Doplňková data, která na subjektu nejsou (členství v týmu, delegace, hierarchie tenantů), si Voter načíst musí a Symfony s injektovanými službami ve Voteru počítá. Takové dotazy patří za cache platnou po dobu requestu.'
- question: Kdy stačí ROLE_USER a kdy je třeba attribute-based přístup?
  answer: 'RBAC (role) stačí, dokud platí „role popisuje permissions sama o sobě“ – ROLE_ADMIN smí všechno, ROLE_REFUND_AGENT smí refundy bez ohledu na konkrétní entitu. Jakmile permissions závisí na vztazích (vlastnictví, tenant, časové okno, stav agregátu), RBAC se rozroste – vznikají hyper-specific role typu ROLE_TENANT_42_ORDER_AGENT. Tehdy nastupuje uvažování v ABAC pojmech (<a href="#policy-based">11.08</a>): rozhodnutí vyhodnocuje atributy subjektu, uživatele a kontextu. Neznamená to psát vlastní policy engine – v Symfony 8 se totéž postaví z Voterů, které umí i vrátit důvod zamítnutí.'
- question: Co když máme 100 různých rolí?
  answer: 'To je obvykle příznak, že role replikují data, která patří do entit. Místo ROLE_TENANT_42_ADMIN, ROLE_TENANT_43_ADMIN, … zaveďte atribut <code>user.tenantId</code> + jednu generickou roli ROLE_TENANT_ADMIN a ve Voteru ověřte, že <code>user.tenantId == subject.tenantId</code>. Zjednoduší to správu uživatelů, audit i delegaci. Detail v <a href="#multi-tenancy">sekci o multi-tenancy</a>.'
- question: Smí doménový Aggregate záviset na Symfony Security komponentě?
  answer: 'Ne. Doména musí být framework-agnostic – bez toho ji nelze unit-testovat bez Kernelu, sdílet mezi webem a CLI ani migrovat na jiný framework. Modelový důvod je ale silnější než technický: role a oprávnění jsou slovník Identity &amp; Access kontextu, ne toho, ve kterém agregát žije. Pokud potřebuje aggregate „znát“ uživatele, dostane <em>vlastní</em> doménový typ (<code>CustomerId</code>). Překlad ze <code>SecurityUser</code> obstará aplikační handler. Detail v anti-vzoru 4 v <a href="#anti-symfony-user-domain-heading">11.11</a>.'
- question: Kam ukládat audit log autorizačních rozhodnutí?
  answer: 'Tři možnosti, podle compliance požadavků: (1) Symfony Monolog s vlastním channelem <code>authorization</code> – stačí pro většinu aplikací, log do souboru / ELK / Loki; (2) doménová tabulka <code>authorization_decisions</code> s parametry (user_id, attribute, subject_id, decision, policy_version) – vhodné pro regulaci (PCI-DSS, GDPR Article 30); (3) externí audit služba (AWS CloudTrail, Datadog) pro multi-tenant SaaS. Implementačně se osvědčil decorator nad <code>AuthorizationCheckerInterface</code>, který každé volání zaloguje. Pro detail viz <a href="#audit-log-heading">Audit log autorizačních rozhodnutí</a>.'
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
