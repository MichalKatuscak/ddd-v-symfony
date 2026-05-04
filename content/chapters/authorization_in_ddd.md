---
route: authorization_in_ddd
path: /autorizace-v-ddd
title: Autorizace v DDD na Symfony
page_title: "Autorizace v DDD na Symfony – Voters, ACL na agregátu, policy-based | DDD Symfony"
meta_description: "Kde má sedět autorizační logika v DDD aplikaci v Symfony 8? Edge, use case, aggregate, field – 4 vrstvy s konkrétními ukázkami Voterů, doménových exceptions a policy-based přístupu."
meta_keywords: "Autorizace, Authorization, Symfony Voter, RBAC, ABAC, Policy-based, ACL, Aggregate permissions, DDD Symfony 8, Security, Doctrine, Owner-based, Multi-tenancy, TenantFilter"
og_type: article
published: "2026-04-29"
modified: "2026-05-04"
breadcrumb_name: Autorizace v DDD
schema_type: TechArticle
schema_headline: "Autorizace v DDD na Symfony – 4 vrstvy, Voters a policy-based přístup"
chapter_number: "11"
category: Praxe
deck: 'V DDD aplikacích se opakovaně objevuje stejná otázka: <em>„smí to ten uživatel udělat?“</em> – patří do controlleru, do voteru, do aggregate, nebo někam jinam? Kapitola dává konkrétní čtyřvrstvý rámec: Edge, Use Case, Aggregate, Field. Každá vrstva odpovídá jinou otázku a používá jiný Symfony nástroj.'
reading_time: 25
difficulty: 3
---

V předchozí kapitole jsme implementovali agregáty, repozitáře a Application Services v Symfony 8. Otevřená zůstala otázka, kterou většina projektů řeší ad-hoc: **kdo smí který use case zavolat a za jakých podmínek**. V této kapitole zavedeme čtyřvrstvý rámec, který autorizační rozhodnutí umístí na správnou vrstvu – od HTTP firewallu přes Symfony Voter v aplikační vrstvě až po doménové invarianty v agregátu. V navazující kapitole o CQRS pak ukážeme, jak se autorizace integruje do Command Handleru.

Autorizace je v DDD aplikacích dlouhodobě podceněné téma. Většina týmů zvládne autentizaci (Symfony firewall, JWT, OAuth) bez větších potíží. Jakmile ale přijde otázka *„kdo smí udělat co s konkrétní entitou v konkrétním stavu“*, kód se rozsype napříč controllery, listenery, twig templaty a Doctrine query buildery. Kapitola dává **čtyřvrstvý rámec**, podle kterého poznáte, kam které pravidlo patří a jak ho v Symfony 8 implementovat idiomaticky – bez toho, aby Symfony Security komponenta pronikla do doménového jádra.

Kapitola navazuje na [Implementaci v Symfony](/implementace-v-symfony), která pokrývá Voter API jako jeden z několika Symfony idiomů. Doplňuje praktický pohled k tématům [CQRS](/cqrs) (kde sedí ověření Command Handleru), [Testování](/testovani-ddd) (jak otestovat každou ze 4 vrstev samostatně) a [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli) (kde jsme autorizaci jen letmo zmínili).

## 11.01 Tři chyby s autorizací, které se v review opakovaně objevují {#tri-chyby}

Než přejdeme ke správnému přístupu, projděme si tři opakující se chyby. V projektech s neformální DDD strukturou nad Symfony se vyskytují pravidelně. Diagnóza je vždy stejná: chybí rozhodovací rámec, kam které pravidlo patří.

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
        if ($order->getStatus() !== 'PLACED') {
            throw new \LogicException('Cannot cancel a non-placed order');
        }

        $order->setStatus('CANCELLED');
        $orders->save($order);

        return $this->redirectToRoute('order_detail', ['id' => $id]);
    }
}
:::

Co je špatně: stejný use case se volá i z konzolového commandu (cron, batch), z Symfony Messenger handleru (asynchronní queue) a z administračního panelu. Při každém volání musí někdo tutéž podmínku zopakovat – a stačí, aby jeden vstupní bod selhal, a celá ochrana padá. Doménové pravidlo „zrušit smí jen vlastník“ je rozeseté po infrastruktuře, ne na jednom místě.

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
            if ($subject->getStatus() !== 'PLACED')           { return false; }
            $age = (new \DateTimeImmutable())->getTimestamp() - $subject->getPlacedAt()->getTimestamp();
            if ($age > 86400) { return false; }
            return true;
        }
        return false;
    }
}
:::

Co je špatně: Aggregate `Order::setStatus(OrderStatus::CANCELLED)` stále existuje a je veřejné. Stačí, aby kdokoli (test, fixture, migration script, jiný vývojář) zavolal setter mimo Voter – a invariant „24h cancellation window“ je porušen. Voter je jen *volitelný* filtr před vstupem; doména nemá žádnou pojistku. Pravidlo „cancellation window“ je doménové, ne use-case-level.

### Chyba 3: Autorizace na úrovni databázových řádků {#tri-chyby-doctrine-heading}

Tým objeví Doctrine SQLFilter a rozhodne, že autorizaci vyřeší v perzistentní vrstvě – entity se z databáze nevrátí, pokud k nim uživatel nemá přístup. Funguje to pro *read* dotazy, ale rozpadá se v doménové logice:

- Když handler dostane `$orderId` a entita se nenajde, neví, jestli neexistuje, nebo jen není dostupná pro daného uživatele. Chybová hláška „Order not found“ je matoucí.
- Doctrine filtry se nevztahují na `EntityManager::find()` z jiného Bounded Contextu, na nativní SQL, na Redis cache.
- Doménová pravidla typu „order patří customerovi“ jsou *duplikovaná*: jednou v SQL filtru, jednou (zapomenutě) ve Voteru, jednou (chybějícím způsobem) v aggregate.

:::callout{type="warn"}
### Diagnóza: chybí framework, kde co umístit {#diagnoza-heading}

Společným jmenovatelem všech tří chyb je absence rozhodovacího rámce. Vývojář má v každém okamžiku **jednu konkrétní otázku** („smí to vidět?“, „smí to udělat?“, „je to vůbec možné?“, „má vidět tento sloupec?“). Každou z nich řeší správný nástroj na správné vrstvě. Když chybí mapa, použije se první nástroj, který má po ruce – a kód se rozpadne. V další sekci ten rámec dáme dohromady.
:::

## 11.02 Čtyři vrstvy autorizace {#ctyri-vrstvy}

Autorizační rozhodnutí v DDD aplikaci nikdy nepadá na jednom místě – padá ve čtyřech postupných vrstvách, každá s vlastní otázkou, vlastním Symfony nástrojem a vlastní granularitou. Vrstvy fungují jako *filtry*: každá další odpovídá jemnější otázku a předpokládá, že předchozí vrstva už řekla „ano“.

:::diagram{fig="12.2-A" title="4 vrstvy autorizace v DDD aplikaci" src="images/diagrams/19_authorization/policy_layers.svg"}
:::

| Vrstva | Otázka | Symfony nástroj | Příklad |
|---|---|---|---|
| **Edge** | Je přihlášený? Smí na tuhle URL? | `access_control`, JWT firewall | `/admin/*` jen pro `ROLE_ADMIN` |
| **Use Case** | Smí vykonat use case na tomto objektu? | `Voter` | „Smí Petr cancelnout order #42?“ |
| **Aggregate** | Dá se to vůbec teď udělat? | doménový check + exception | „Order lze cancelnout jen 24 h od vytvoření“ |
| **Field** | Smí vidět konkrétní pole? | Twig + Voter, query filter | „Sloupec `audit_log` vidí jen admin“ |

Pravidlo: každé autorizační rozhodnutí patří do *právě jedné* vrstvy. Pokud zjistíte, že stejné pravidlo musíte zapsat na dvou vrstvách, jedna z nich je špatně zvolená. V [sekci o anti-vzorech](#antivzory) ukážeme typické duplicity, kterým se vyhnout.

*Citace: Symfony Security komponenta dokumentuje vícevrstvý přístup v sekci „Authorization“ [[1]](https://symfony.com/doc/current/security.html#access-control-authorization); obecné principy ABAC vs. RBAC najdete v NIST SP 800-162 [[2]](https://csrc.nist.gov/publications/detail/sp/800-162/final); praktický pohled na vrstvení autorizace v doménové aplikaci dává Vernon ve *Implementing Domain-Driven Design* (kap. 14, „Application“).*

## 11.03 Edge – Symfony firewall a access_control {#edge}

Edge je nejhrubší vrstva a leží mimo doménový kód. Odpovídá pouze na otázku **„kdo je vůbec na druhém konci socketu?“** – anonymous, authenticated, případně role-based pro hrubě dělené sekce (`/admin/*`, `/api/v1/*`). Doménová pravidla typu „zákazník X smí na tuto objednávku“ sem *nepatří* – to je use-case-level.

:::code{language="yaml" filename="config/packages/security.yaml"}
# config/packages/security.yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Identity\Domain\AppUser
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

Principy edge vrstvy:

- **Žádná doménová znalost.** Edge nezná pojem „order“, „customer“, „cancellation window“. Pracuje jen s URL pattern + roles + autentizační stav.
- **Default deny.** Poslední pravidlo v `access_control` je „všechno ostatní vyžaduje přihlášení“. Bez tohoto fallbacku stačí přidat nový endpoint a zapomenout ho zařadit – automaticky bude veřejný.
- **Role-based, ne attribute-based.** ROLE_ADMIN je hrubá kategorizace; jemnější rozhodnutí jako „admin tenantu T1, ne T2“ patří do Voteru, ne do `access_control`.
- **JWT firewall vs. session.** API typicky stateless (`jwt` autentikátor), web typicky session-based. Pro JWT v Symfony existuje balíček `lexik/jwt-authentication-bundle` nebo nativní `OidcAuthenticator` pro OpenID Connect provider [[3]](https://openid.net/specs/openid-connect-core-1_0.html).

:::callout{type="pattern"}
### Vzorová analogie: Stripe API key model {#edge-stripe-heading}

Stripe rozlišuje API klíče na úrovni edge: `sk_test_*`, `sk_live_*`, `pk_*`, restricted keys s explicitním scope [[4]](https://stripe.com/docs/keys). Klíč rozhoduje, zda volání vůbec dorazí do API – to je edge vrstva. Ale *kdo konkrétně* je za klíčem (jaký účet, jaké permissions na konkrétní Customer/Charge entitu) řeší až další vrstva. Tatáž logika by měla platit ve vašem Symfony API: JWT validuje, kdo to je; Voter rozhoduje, co s konkrétním objektem smí.
:::

## 11.04 Use Case – Symfony Voter {#use-case-voter}

Use case vrstva odpovídá na otázku **„smí *tento* uživatel vykonat *tento* use case na *tomto* objektu?“**. Symfony Voter je přesně k tomu navržený nástroj. Pravidlo: **1 use case = 1 atribut Voteru**; jeden Voter může pokrývat N atributů, pokud se týkají stejné entity (typicky CRUD operace nad agregátem).

Voter zná dvě věci: **identitu uživatele** (přes `TokenInterface`) a **cílový subjekt** (typicky aggregate root). Co Voter *nesmí* dělat: fetchovat entity z databáze (to je práce handleru) a znát doménové invarianty (to je práce aggregate). Doménová pravidla typu „cancellation window“ Voter nesmí natáhnout zvenku – jsou to doménové stavy.

:::code{language="php" filename="src/Ordering/Infrastructure/Security/OrderVoter.php" highlights="12,14,15,16,31,32,33,34,35,36"}
// src/Ordering/Infrastructure/Security/OrderVoter.php
declare(strict_types=1);

namespace App\Ordering\Infrastructure\Security;

use App\Identity\Domain\AppUser;
use App\Ordering\Domain\Order;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoter extends Voter
{
    public const VIEW   = 'order.view';
    public const CANCEL = 'order.cancel';
    public const REFUND = 'order.refund';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL, self::REFUND], true)
            && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof AppUser) {
            return false;
        }

        \assert($subject instanceof Order);

        return match ($attribute) {
            self::VIEW   => $this->canView($subject, $user),
            self::CANCEL => $this->canCancel($subject, $user),
            self::REFUND => $user->hasRole('ROLE_REFUND_AGENT'),
            default      => false,
        };
    }

    private function canView(Order $order, AppUser $user): bool
    {
        return $user->customerId()->equals($order->customerId())
            || $user->hasRole('ROLE_ADMIN');
    }

    private function canCancel(Order $order, AppUser $user): bool
    {
        return $user->customerId()->equals($order->customerId());
    }
}
:::

Tři implementační detaily:

- **Konstanty atributů s prefixem entity** (`order.cancel`, ne jen `CANCEL`). Vyhne se kolizi s atributy jiných Voterů (`invoice.cancel`, `shipment.cancel`) a v audit logu je hned jasné, kterého subjektu se rozhodnutí týkalo.
- **Match expression** (PHP 8.0+) místo if-else stromu. Při přidání nového atributu PHPStan na úrovni 8 odhalí chybějící case (díky `default => false` + případnému `throw` ve striktnější verzi).
- **Privátní metody `canView`, `canCancel`**. Každý use case má vlastní privátní metodu – testy umí mockovat token a subjekt, asserce na výsledek metody je explicitní. Bez extrakce by se voter rozrostl do nečitelného switch-case.

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

Po této kontrole zavolá handler doménovou operaci `$order->cancel(...)`, která uvnitř agregátu ověří doménové invarianty (status, cancellation window). Tím vznikají **dvě nezávislé bariéry**: Voter řekne „smí Petr“, aggregate řekne „dá se to vůbec teď“. Detail aggregate vrstvy v [další sekci](#aggregate-level).

### Voter v Twig template {#voter-twig-heading}

Stejný Voter pokrývá i view-level rozhodnutí (skrýt tlačítko „Cancel order“ pro ne-vlastníka). V Twigu funkce `is_granted()` volá tentýž `AuthorizationCheckerInterface`:

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

{% if is_granted('order.cancel', order) and order.isCancellable %}
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
### Voter nesmí fetchovat z databáze {#voter-anti-fetching-heading}

Pokud váš Voter dělá `$this->repository->find($id)` nebo `$this->em->getRepository(Order::class)->findBy(...)`, je to anti-vzor. Voter dostává subjekt jako parametr (`$subject`); handler ho už načetl a předává v paměti. Voterové načítání vede k *duplicate query* (handler načetl, voter načetl znovu) a v horším případě k *race condition* (mezi načtením ve voteru a operací v handleru se entita změní). Vždy předávejte načtenou entitu.
:::

## 11.05 Aggregate-level – doména sama rozhoduje {#aggregate-level}

Některá pravidla nelze rozumně dát do Voteru. Vyžadují znalost *doménového stavu*, který Voter nemá natáhnout zvenku – typicky časové okno, předchozí stav agregátu, doménové invarianty napříč vlastními entitami uvnitř agregátu. Tato pravidla patří do **aggregate root** a vynucují se vyhozením *doménové exception*.

Praktická heuristika:

- Pokud lze pravidlo zformulovat v jazyce *uživatel + use case + entita* („smí Petr zrušit objednávku #42“), patří do **Voteru**.
- Pokud pravidlo vyžaduje *stav agregátu + doménové pravidlo* („order musí být ve stavu PLACED a ne starší než 24 h“), patří do **Aggregate**.
- Pokud pravidlo kombinuje obojí, rozdělte ho: část do Voteru, část do Aggregate, a každá vrstva ověří svou polovinu.

:::code{language="php" filename="src/Ordering/Domain/Order.php" highlights="25,26,27,28,29,30,31,32,33,35,36,37,38,39,40,41,42"}
// src/Ordering/Domain/Order.php
declare(strict_types=1);

namespace App\Ordering\Domain;

use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\Exception\CancellationWindowExpiredException;
use App\Ordering\Domain\Exception\InvalidOrderStateException;

final class Order
{
    private const CANCELLATION_WINDOW_SECONDS = 86_400; // 24 h

    /** @var list<object> */
    private array $releasedEvents = [];

    public function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private OrderStatus $status,
        private readonly \DateTimeImmutable $placedAt,
    ) {}

    public function cancel(string $reason, \DateTimeImmutable $when): void
    {
        if ($this->status !== OrderStatus::PLACED) {
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

        $this->status = OrderStatus::CANCELLED;
        $this->releasedEvents[] = new OrderCancelled(
            orderId:    $this->id,
            customerId: $this->customerId,
            reason:     $reason,
            cancelledAt: $when,
        );
    }

    public function isCancellable(\DateTimeImmutable $now): bool
    {
        if ($this->status !== OrderStatus::PLACED) {
            return false;
        }
        return $now->getTimestamp() - $this->placedAt->getTimestamp()
            <= self::CANCELLATION_WINDOW_SECONDS;
    }
}
:::

Vlastnosti tohoto kódu:

- **Žádná závislost na Symfony.** Aggregate používá pouze PHP standardní typy a vlastní doménové třídy. Žádný `TokenInterface`, žádný `AuthorizationChecker`, žádný `UserInterface`. Třídu lze testovat unit testem bez Symfony Kernel.
- **Doménové exceptions.** `InvalidOrderStateException` a `CancellationWindowExpiredException` jsou doménové třídy v `App\Ordering\Domain\Exception`. Nesou doménový kontext (kdy byl order placed, kdy se zkouší cancel) a aplikační vrstva je překládá na HTTP status (typicky 409 Conflict, ne 403 Forbidden – *není to autorizační selhání, je to doménový stav*).
- **Idempotentní pomocná metoda `isCancellable()`.** Voter ani Twig ji nevolají; používá ji UI pro skrytí tlačítka (kombinováno s `is_granted`). Tatáž logika je sdílená s `cancel()` přes konstantu `CANCELLATION_WINDOW_SECONDS` – žádná duplicita.
- **Domain Events.** Po úspěšné operaci se do `$releasedEvents` přidá `OrderCancelled`. Aplikační handler je po `repository->save()` publikuje (typicky přes [Outbox](/outbox-pattern)). Aggregate sám nikdy nevolá `EventDispatcher`.

Zde tedy **není** otázka „smí Petr“ – tu vyřešil Voter v [předchozí sekci](#use-case-voter). Zde je otázka *„dá se to vůbec teď udělat?“*. A odpověď „ne“ se sem dostane i v případě, že Voter řekl „ano“ (Petr je vlastník, ale order je už zaplacen a odeslán). Obě bariéry jsou nezávislé a obě jsou potřeba.

### End-to-end trace: cancellation request {#aggregate-trace-heading}

Pro úplnost si projděme, co se konkrétně stane, když zákazník Petr klikne na tlačítko „Cancel order #42“ v rozhraní:

1. **Edge (firewall).** Symfony ověří JWT/session token. Bez ověření → 401. Petr je přihlášený, pokračuje.
2. **Edge (access_control).** URL `/order/42/cancel` spadá pod `IS_AUTHENTICATED_FULLY`. Petr je přihlášený, pokračuje.
3. **Controller** validuje vstup (CSRF token, request body), vytvoří `CancelOrderCommand(orderId: 42, reason: 'changed mind')` a předá ho na message bus.
4. **Application Handler** (CancelOrderHandler) načte agregát z repository: `$order = $repo->getOrFail(42)`.
5. **Use Case Voter.** Handler volá `$auth->isGranted('order.cancel', $order)`. OrderVoter porovná `$order->customerId()` s `$user->customerId()`. Petr je vlastník → ACCESS_GRANTED, pokračuje. *Kdyby nebyl vlastník → AccessDeniedDomainException → HTTP 403.*
6. **Aggregate.** Handler volá `$order->cancel('changed mind', $now)`. Aggregate ověří `status === PLACED` a `age <= 24h`. Order je placed před 30 min → ok, status se změní na CANCELLED, vznikne OrderCancelled event. *Kdyby byl už shipped → InvalidOrderStateException → HTTP 409.*
7. **Persistence + outbox.** Handler zavolá `$repo->save($order)`; v jedné transakci se uloží stav agregátu i OrderCancelled event do outbox tabulky.
8. **Field-level (response).** Controller vrátí 200 OK. Pokud by Petr nebyl admin a v response figuroval `audit_log`, read model by ho vyfiltroval – na svém vlastním orderu vidí status, ale ne kdo a kdy ho editoval.

Několik vrstev kontroly v jediné cestě požadavku – a každá vrstva selže *jiným* způsobem, s *jiným* HTTP statusem, s *jinou* chybovou hláškou. To je rozdíl mezi doménově navrženou autorizací a generickým „Access denied“.

:::callout{type="note"}
### 403 vs. 409: která chyba kdy? {#aggregate-403-vs-409-heading}

Drobnost s velkým UX dopadem. Když Voter řekne „ne“ (Petr není vlastník), aplikace má vrátit **HTTP 403 Forbidden** – autentizovaný uživatel, ale nedostatečné oprávnění. Když aggregate řekne „ne“ (order už není v PLACED), je to **HTTP 409 Conflict** – uživatel má právo, ale stav prostředku to neumožňuje. Aplikační vrstva má dvě různé exception handlery: `AccessDeniedDomainException → 403`, `InvalidOrderStateException → 409`. UI tak může zobrazit smysluplnou hlášku („Tento order už nelze stornovat – byl odeslán“) místo generického „Access denied“.
:::

## 11.06 Field-level – read model filtrace {#field-level}

Nejjemnější vrstva. Až dosud jsme řešili *akce* (smí udělat) a *existenci* operace (dá se vůbec); field-level řeší **viditelnost konkrétního pole** během jinak povoleného čtení. Klasický příklad: detail orderu vidí customer i admin, ale sloupec `audit_log` (kdo a kdy editoval) má vidět jen admin.

Existují dva přístupy s odlišnými kompromisy:

### Přístup 1: Twig if (view-level) {#field-twig-heading}

Nejjednodušší, ale s *únikem dat*: data se z databáze načtou všechna, jen se ve view zahodí. Pro většinu UI to stačí; **nikdy** to nepoužívejte pro citlivá data, která mohou unikat přes HTML komentáře, JSON serializaci v JS aplikaci nebo Etag hashing.

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

use App\Identity\Domain\AppUser;
use Doctrine\DBAL\Connection;

final readonly class OrderDetailReadModel
{
    public function __construct(private Connection $db) {}

    public function forUser(string $orderId, AppUser $user): OrderDetailDto
    {
        $base = 'SELECT id, customer_id, total_cents, status, placed_at FROM orders WHERE id = :id';

        if ($user->hasRole('ROLE_ADMIN')) {
            $sql = $base . ', audit_log';
        } else {
            $sql = $base;
        }

        $row = $this->db->fetchAssociative($sql, ['id' => $orderId]);
        if ($row === false) {
            throw new OrderNotFoundException($orderId);
        }

        return OrderDetailDto::fromRow($row, includeAudit: $user->hasRole('ROLE_ADMIN'));
    }
}
:::

Volba mezi přístupy:

| Kritérium | Twig if | Query filter |
|---|---|---|
| Data leak | Ano (data v paměti, response, dev tools) | Ne |
| Implementační složitost | Triviální | Vyžaduje různé DTO / read modely |
| Vhodné pro | UI hidden, neostrá ochrana | PII, finance, audit log, GDPR |
| Testování | Twig integration test | Unit + integration test read modelu |
| OWASP A01:2021 compliance | Insufficient – viz [[5]](https://owasp.org/Top10/A01_2021-Broken_Access_Control/) | Splňuje (server-side enforcement) |

Pro necitlivá data Twig if stačí a šetří čas. Pro citlivá data vždy query filter – OWASP Top 10 v kategorii „A01 Broken Access Control“ výslovně varuje před UI-only kontrolou jako jedinou bariérou.

## 11.07 Policy-based přístup (ABAC) {#policy-based}

Když počet pravidel naroste a vrstvení do Voterů přestane být udržitelné (typicky 5+ rolí × 10+ entit × 3+ atributy = 150+ pravidel), je čas přejít z **RBAC** (Role-Based Access Control) na **ABAC** (Attribute-Based Access Control). RBAC říká „role X smí Y“. ABAC vyhodnocuje kombinaci atributů subjektu, akce, prostředku a kontextu proti policy a vrátí povoleno / zakázáno.

V čisté Symfony aplikaci si stačí napsat tenkou vrstvu nad Voter API: `Policy` jako kolekce `Rule` objektů, které se vyhodnotí proti subject/user/context trojici. Pro velké organizace se vyplatí externí policy engine (OPA – Open Policy Agent), který umí policy verzovat, distribuovat a auditovat nezávisle na aplikaci.

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
                expression:  'subject.status == "PLACED"',
                description: 'Order musí být ve stavu PLACED',
            ),
            new Rule(
                expression:  '(now - subject.placedAt) <= 86400',
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

Poznámka: pravidla `subject.status == "PLACED"` a časové okno 24 h jsou v politice pro ilustraci ABAC zápisu. Jak popisuje sekce 12.05, tyto doménové invarianty patří primárně do agregátu. Politika je ověřuje jako pre-check před dosažením domény (obrana do hloubky). Agregát ale musí být zdrojem pravdy a nepřijmout neplatný příkaz ani bez autorizační vrstvy.

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

Výhody policy-based přístupu:

- **Auditovatelnost.** Pravidla jsou data, ne kód. `PolicyEvaluator` vrací, *které* pravidlo selhalo – uživatel dostane přesnou chybovou hlášku („Cancellation window 24 h ještě neuplynulo“) místo generického „Access denied“.
- **Versioning.** Policy je třída v repu – změny přes git, code review, deploy. ABAC standardně vyžaduje policy versioning [[2]](https://csrc.nist.gov/publications/detail/sp/800-162/final).
- **Testovatelnost.** Test policy je čistý unit test bez frameworku – pro každé pravidlo jeden case.
- **Externí policy engine.** Když policy přerostou aplikaci, lze je portovat do **Open Policy Agent (OPA)** – engine v Go s vlastním policy language (Rego). Symfony aplikace potom dělá HTTP volání místo lokálního `evaluate()`.

:::callout{type="pattern"}
### RBAC vs. ABAC: kdy přejít? {#abac-vs-rbac-heading}

RBAC stačí, dokud platí *„role popisuje oprávnění sama o sobě“* – admin smí všechno, zákazník smí svoje, refund agent smí refundy. Jakmile oprávnění závisí na *vztazích mezi entitami* (tenant, vlastnictví, časové okno, stavový automat), RBAC začne nekontrolovaně narůstat. Buď vznikají hyper-specific role typu `ROLE_TENANT_42_ORDER_REFUND_AGENT`, nebo se logika rozpadne do Voterů s 200 řádky if-else. Tehdy je čas na ABAC.
:::

## 11.08 Multi-tenancy – owner kontext {#multi-tenancy}

Multi-tenancy (vícenájemnost) je speciální případ ABAC, kdy stejná aplikace obsluhuje více *oddělených zákazníků* (organizací, mandantů, tenantů) a žádný tenant nesmí vidět data jiného. Existují tři architektonické strategie:

- **Row-based** – sdílená databáze, sdílené tabulky, sloupec `tenant_id` všude. Nejlevnější, nejméně izolace, vyžaduje pečlivé filtry.
- **Schema-based** – sdílená databáze, samostatné schema per tenant (PostgreSQL `SET search_path`). Střední izolace, lepší performance než row-based.
- **Database-based** – samostatná databáze per tenant. Nejvyšší izolace, nejnákladnější (DB connection per tenant, migrations × N).

V praxi se nejčastěji volí row-based pro startupy a SaaS s malým počtem tenantů, schema-based pro mid-size B2B, database-based pro enterprise / compliance-heavy domény (zdravotnictví, finance). Pro row-based v Symfony je idiomatický nástroj **Doctrine SQLFilter**.

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
                enabled: false  # zapne kernel listener až po identifikaci tenanta
:::

Filter se musí **aktivovat v každém požadavku** a předat mu správné `tenant_id`. Bez toho je výchozí stav „filter vypnutý“ – tedy žádná izolace. Aktivaci řeší kernel event listener:

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

Tři detaily, které se vyplatí zachytit:

- **Priority 7** v `AsEventListener` – v Symfony platí *vyšší priority = dřívější vykonání*. Symfony Firewall registruje svůj `onKernelRequest` s prioritou 8, takže aby měl listener k dispozici už autentizovaného uživatele, musí běžet s prioritou *nižší než 8* (typicky 7 nebo 0). Detail v [Symfony EventDispatcher dokumentaci](https://symfony.com/doc/current/event_dispatcher.html).
- **Main request guard.** Bez `$event->isMainRequest()` by se filter nastavoval i pro dílčí požadavky (např. ESI, render fragments) – tam typicky není token a listener by spadl.
- **Anonymous fallback.** Pokud je požadavek anonymní (login, register, health), listener prostě filter neaktivuje – Doctrine queries nevrátí žádné `TenantAware` entity bez explicitního filteru. Tím vzniká *fail-closed* default.

:::callout{type="warn"}
### Pozor: filter neaplikuje na native SQL ani Redis {#multi-tenancy-warn-heading}

Doctrine SQLFilter modifikuje pouze `QueryBuilder` a `EntityManager::find`. Pokud aplikace volá `$conn->executeQuery('SELECT ...')`, používá Redis, Elasticsearch nebo externí HTTP API, *žádný filter se neaplikuje*. V těchto místech musíte tenant_id přidat ručně. V code review hledejte anti-vzor: surové SQL bez tenant_id v `WHERE`. Statická analýza (Phpstan-rule nebo PHPArkitect) umí takové query odhalit.
:::

## 11.09 Test pyramida pro autorizaci {#testing}

Každá ze 4 vrstev se testuje jiným druhem testu – a snaha pokrýt vše end-to-end vede k pomalé, křehké test suitě. Dělení odpovídá klasické *test pyramidě*: hodně rychlých unit testů, méně integration, pár end-to-end.

### Aggregate-level: čistý unit test {#testing-aggregate-heading}

Doménová pravidla v aggregate jsou plain PHP – žádný framework, žádná databáze. Test je rychlý a deterministický:

:::code{language="php" filename="tests/Ordering/Domain/OrderCancelTest.php"}
// tests/Ordering/Domain/OrderCancelTest.php
declare(strict_types=1);

namespace Tests\Ordering\Domain;

use App\Ordering\Domain\Exception\CancellationWindowExpiredException;
use App\Ordering\Domain\Exception\InvalidOrderStateException;
use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderCancelTest extends TestCase
{
    public function testCancelWithinWindowSucceeds(): void
    {
        $order = OrderFactory::placed(at: '2026-04-29 10:00:00');

        $order->cancel('changed mind', new \DateTimeImmutable('2026-04-29 12:00:00'));

        self::assertSame(OrderStatus::CANCELLED, $order->status());
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

Voter dostává `TokenInterface`; v testu stačí jeho mock + reálný subject. Žádný Symfony Kernel:

:::code{language="php" filename="tests/Ordering/Infrastructure/Security/OrderVoterTest.php"}
// tests/Ordering/Infrastructure/Security/OrderVoterTest.php
declare(strict_types=1);

namespace Tests\Ordering\Infrastructure\Security;

use App\Identity\Domain\AppUser;
use App\Identity\Domain\CustomerId;
use App\Ordering\Domain\Order;
use App\Ordering\Infrastructure\Security\OrderVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class OrderVoterTest extends TestCase
{
    public function testOwnerCanCancelOwnOrder(): void
    {
        $voter   = new OrderVoter();
        $owner   = new AppUser(CustomerId::fromString('cus_1'), ['ROLE_USER']);
        $order   = OrderFactory::placedFor(CustomerId::fromString('cus_1'));
        $token   = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($owner);

        self::assertTrue(
            $voter->vote($token, $order, [OrderVoter::CANCEL]) === Voter::ACCESS_GRANTED
        );
    }

    public function testStrangerCannotCancelOrder(): void
    {
        $voter    = new OrderVoter();
        $stranger = new AppUser(CustomerId::fromString('cus_2'), ['ROLE_USER']);
        $order    = OrderFactory::placedFor(CustomerId::fromString('cus_1'));
        $token    = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($stranger);

        self::assertSame(
            Voter::ACCESS_DENIED,
            $voter->vote($token, $order, [OrderVoter::CANCEL])
        );
    }
}
:::

### End-to-end: WebTestCase {#testing-e2e-heading}

Pro pokrytí celé pipeline (firewall → controller → handler → voter → aggregate) slouží Symfony `WebTestCase`. Zde už je to integration test, který používá kernel a databázi. Doporučená míra: *1 e2e test na use case*, pokrývající happy path + 1-2 nejdůležitější chybové stavy. Detailní edge-case pokrytí patří do unit testů na nižších vrstvách.

Detail pyramidy + příklady fixture builderů v [samostatné kapitole o testování](/testovani-ddd).

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

Tabulkový test má dvě hodnoty navíc oproti klasickému test-per-method přístupu. Přidání pravidla = přidání jednoho řádku v `scenarios()`. A celý test slouží jako *spustitelná dokumentace policy* – netechnický reviewer vidí všechny případy v jedné tabulce a může schválit doménová pravidla.

## 11.10 Anti-vzory {#antivzory}

Čtyři opakující se anti-vzory, které se v projektech objevují nejčastěji. Každý je pojmenován, doložen konkrétním příkladem a doplněn náhradou.

### Anti-vzor 1: Autorizace v controlleru {#anti-controller-heading}

Probrali jsme v sekci [12.01](#tri-chyby). Vyplatí se to zdůraznit znovu, protože jde o nejčastější chybu. Symptom: stejná autorizační podmínka opakovaná v 3+ controllerech, neexistující ve verzích volaných z konzolového commandu nebo Messenger handleru. Náprava: přesun do Voteru + volání `AuthorizationCheckerInterface` v Application Service. Souvisí: [obecné anti-vzory v DDD](/anti-vzory).

### Anti-vzor 2: Voter, který načte aggregate z databáze {#anti-fetching-voter-heading}

Symptom:

:::code{language="php" filename="src/Security/OrderVoter.php (anti-vzor)" highlights="9,10"}
// src/Security/OrderVoter.php (anti-vzor)
final class OrderVoter extends Voter
{
    public function __construct(private OrderRepository $orders) {}

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // Anti-vzor: voter dostane jen ID a sám načte entitu
        $order = $this->orders->find($subject);
        // ... rozhodování ...
    }
}
:::

Důsledek: handler načte order, pak voter načte order podruhé, mezi tím se může stát race condition (jiný proces order změní). Náprava: handler načte entitu jednou, předá ji do `$auth->isGranted($attr, $order)`, voter pracuje s touto instancí.

### Anti-vzor 3: Voter == Aggregate logic {#anti-duplication-heading}

Symptom: cancellation window pravidlo („order ne starší než 24 h“) je zapsané *jak* ve Voteru, *tak* v `Order::cancel()`. Když se doménové pravidlo změní (např. window se prodlouží na 48 h), obě místa se musí upravit – a typicky se zapomene jedno.

Náprava: pravidlo patří do aggregate (je to doménový invariant). Voter **nesmí** ověřovat doménový stav agregátu – odpovídá jen na identitu/role uživatele a vlastnictví subjektu. Pro view-level skrytí tlačítka se v Twigu kombinuje `{% if is_granted(...) and order.isCancellable %}` – voter pro permission, doménová metoda pro stavovou kontrolu.

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

Doména teď závisí na `Symfony\Component\Security`. Pokud byste chtěli stejný kód spustit z konzolového commandu, asynchronně přes Messenger nebo v unit testu bez Kernel, narazíte na chybějící `UserInterface`. Náprava: doména pracuje s *vlastním* typem (`CustomerId`, doménový `AppUser` bez Symfony rozhraní). Aplikační handler překládá ze Symfony `UserInterface` na doménový typ. Detail v [kapitole o anti-vzorech](/anti-vzory).

:::callout{type="warn"}
### Společný jmenovatel anti-vzorů {#anti-summary-heading}

Všechny čtyři anti-vzory vznikají z jediné chyby: *autorizační rozhodnutí se umístilo do nesprávné vrstvy*. Když máte čtyřvrstvý rámec z [12.02](#ctyri-vrstvy) na zřeteli, code review takové chyby odhalí na první pohled.
:::

## 11.11 Shrnutí {#summary}

Autorizace v DDD aplikaci na Symfony 8 sedí na čtyřech vrstvách, každá s vlastním Symfony nástrojem a vlastní granularitou:

- **Edge** – Symfony firewall + `access_control`. Anonymous vs. authenticated, role-based hrubá separace. Žádná doménová znalost.
- **Use Case** – Symfony Voter. „Smí Petr cancelnout order #42?“ Aplikační handler volá `AuthorizationCheckerInterface::isGranted()`; doména to nesmí.
- **Aggregate** – doménový invariant + doménová exception. „Order musí být PLACED a ne starší než 24 h.“ Aggregate vyhazuje `InvalidOrderStateException`; aplikační vrstva to mapuje na HTTP 409.
- **Field** – Twig `is_granted` pro view-level (s rizikem data leaku) nebo query filter / read model pro citlivá data (PII, audit log).

Kde co řešit:

- Hrubé permissions → **RBAC** (role).
- Jemné, vztahy mezi entitami → **ABAC** / policy-based.
- Vícenájemnost → Doctrine SQLFilter + kernel listener (fail-closed default).
- Doménové stavové pravidlo → Aggregate, ne Voter.

Kdy zvážit externí policy engine: 100+ pravidel, multi-tenant SaaS s individuálními policy per tenant, regulovaná doména s nutností auditovat policy nezávisle na aplikačním kódu. Pro většinu Symfony aplikací stačí Voter + (volitelně) tenké policy-evaluator vrstvení nad Voter API.

### Praktický checklist před deploy {#summary-checklist-heading}

Než commitnete autorizační změnu, projděte si těchto sedm bodů:

1. Existuje v `access_control` default-deny pravidlo na konci? *Pokud ne – nový endpoint bez explicitní role je veřejný.*
2. Volá Application Handler `$auth->isGranted()` **před** doménovou operací? *Pokud ne – autorizace se může obejít přes alternativní vstupní bod (CLI, Messenger).*
3. Je doménový invariant zapsaný v aggregate, ne ve Voteru? *Pokud ne – pravidlo se obejde přímým voláním aggregate metody mimo handler.*
4. Vrací aplikace 403 vs. 409 podle typu selhání? *Pokud ne – uživatel dostane matoucí hlášku.*
5. Mají citlivá pole (PII, audit) query filter, ne jen Twig if? *Pokud ne – data leakují přes JSON API, dev tools, ETag.*
6. Pokud aplikace je multi-tenant: má Doctrine SQLFilter *fail-closed* default? *Pokud ne – chybějící tenant context vrátí všechna data.*
7. Existuje na každé vrstvě alespoň jeden test? *Aggregate test, Voter test, e2e test minimum.*

:::callout{type="pattern"}
### Audit log autorizačních rozhodnutí {#audit-log-heading}

Regulované domény (zdravotnictví, finance, GDPR čl. 30) vyžadují audit log *každého* autorizačního rozhodnutí, ne jen úspěšných operací. Idiomaticky se to v Symfony řeší **decorator pattern**em nad `AuthorizationCheckerInterface`: vlastní třída obalí původní checker, zaloguje volání (kdo, na čem, jaký atribut, jaký výsledek) a deleguje. Symfony DI to umožňuje přes `#[AsDecorator(decorates: 'security.authorization_checker')]`. Loguje se obvykle do vyhrazeného Monolog channelu `authorization` a odtud do ELK / Loki / centrálního SIEM.
:::

:::faq{}
- question: Mám psát jeden Voter na entitu, nebo víc?
  answer: 'Jeden Voter na entitu, který pokrývá N atributů (VIEW, CANCEL, REFUND, …). V <code>supports()</code> se filtruje podle <code>$subject instanceof Order</code> a podle whitelistu atributů; v <code>voteOnAttribute()</code> se atributy mapují přes <code>match</code> expression na privátní metody. Více Voterů na jednu entitu se vyplatí jen tehdy, když permissions využívají úplně jiný subset závislostí (typicky owner-based vs. role-based) a chcete je nezávisle testovat. Detail v <a href="#use-case-voter">sekci o Voteru</a>.'
- question: Smí Voter načítat aggregate z databáze?
  answer: 'Ne. Voter dostává <code>$subject</code> jako parametr; handler ho už načetl a předává v paměti. Voterové fetchování je anti-vzor (<a href="#anti-fetching-voter-heading">12.10</a>) – vede k duplicate query, race condition a pomalé test suitě. Pokud Voter potřebuje další data, předajte je přes konstruktor (např. config) nebo přes obohacený DTO subject, ne přes repository.'
- question: Kdy stačí ROLE_USER a kdy je třeba attribute-based přístup?
  answer: 'RBAC (role) stačí, dokud platí „role popisuje permissions sama o sobě“ – ROLE_ADMIN smí všechno, ROLE_REFUND_AGENT smí refundy bez ohledu na konkrétní entitu. Jakmile permissions závisí na vztazích (vlastnictví, tenant, časové okno, stav agregátu), RBAC explodne – vznikají hyper-specific role typu ROLE_TENANT_42_ORDER_AGENT. Tehdy přejít na ABAC (<a href="#policy-based">12.07</a>): permissions vyhodnocují atributy subjektu, uživatele a kontextu proti policy.'
- question: Co když máme 100 různých rolí?
  answer: 'To je obvykle příznak, že role replikují data, která patří do entit. Místo ROLE_TENANT_42_ADMIN, ROLE_TENANT_43_ADMIN, … zaveďte atribut <code>user.tenantId</code> + jednu generickou roli ROLE_TENANT_ADMIN a v Voteru ověřte, že <code>user.tenantId == subject.tenantId</code>. Drasticky to zjednoduší správu uživatelů, audit a delegaci. Detail v <a href="#multi-tenancy">sekci o multi-tenancy</a>.'
- question: Smí doménový Aggregate záviset na Symfony Security komponentě?
  answer: 'Ne. Doména musí být framework-agnostic – bez ní nelze unit-testovat bez Kernel, nelze sdílet kód mezi web a CLI, nelze migrovat na jiný framework. Pokud potřebuje aggregate „znát“ uživatele, dostane <em>vlastní</em> doménový typ (<code>CustomerId</code>, doménový <code>AppUser</code>). Aplikační handler překládá Symfony <code>UserInterface</code> na doménový typ. Detail v anti-vzoru 4 v <a href="#anti-symfony-user-domain-heading">12.10</a>.'
- question: Kam ukládat audit log autorizačních rozhodnutí?
  answer: 'Tři možnosti, podle compliance požadavků: (1) Symfony Monolog s vlastním channelem <code>authorization</code> – stačí pro většinu aplikací, log do souboru / ELK / Loki; (2) doménová tabulka <code>authorization_decisions</code> s parametry (user_id, attribute, subject_id, decision, policy_version) – vhodné pro regulaci (PCI-DSS, GDPR Article 30); (3) externí audit služba (AWS CloudTrail, Datadog) pro multi-tenant SaaS. Implementačně doporučuji decorator nad <code>AuthorizationCheckerInterface</code>, který každé volání zaloguje. Pro detail viz sekci o testování v <a href="#testing">12.09</a>.'
:::

## 11.12 Další četba {#further-reading}

- [Symfony Security komponenta – oficiální dokumentace](https://symfony.com/doc/current/security.html)
- [Symfony Voters – Custom Authorization](https://symfony.com/doc/current/security/voters.html)
- [OWASP Top 10 (2021): A01 – Broken Access Control](https://owasp.org/Top10/A01_2021-Broken_Access_Control/)
- [NIST SP 800-162 – Guide to Attribute-Based Access Control](https://csrc.nist.gov/publications/detail/sp/800-162/final)
- [OpenID Connect Core 1.0 – autentizační vrstva nad OAuth 2.0](https://openid.net/specs/openid-connect-core-1_0.html)
- [Stripe API keys – restricted keys s explicitním scope](https://stripe.com/docs/keys)
- [Open Policy Agent (OPA) – externí policy engine](https://www.openpolicyagent.org/docs/latest/)
- [Vernon, V.: Implementing Domain-Driven Design (kap. 14, „Application“)](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577)
