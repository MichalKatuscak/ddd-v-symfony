# DDD v praxi — kde to bolí: Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vytvořit novou kapitolu příručky `/ddd-v-praxi-kde-to-boli` s 20 reálnými pain pointy DDD v PHP/Symfony, umístěnou za Ságami a před Praktickými příklady.

**Architecture:** Čistě content-driven Twig template, žádná databáze ani business logika. Nová stránka = nová akce v DddController + nový template + aktualizace navigace na 4 místech.

**Tech Stack:** PHP 8.4, Symfony 8, Twig, Doctrine ORM (pro ukázky kódu), Symfony Messenger (pro ukázky kódu)

---

### Task 1: Route + controller action

**Files:**
- Modify: `src/Controller/DddController.php`

- [ ] **Krok 1: Přidat akci do DddController**

Otevři `src/Controller/DddController.php` a za akci `sagas()` (řádek ~121) vlož:

```php
#[Route('/ddd-v-praxi-kde-to-boli', name: 'ddd_pain_points')]
public function dddPainPoints(): Response
{
    return $this->render('ddd/ddd_pain_points.html.twig', [
        'title' => 'DDD v praxi — kde to bolí',
    ]);
}
```

- [ ] **Krok 2: Ověřit routing**

```bash
php bin/console debug:router | grep pain
```

Očekávaný výstup obsahuje: `ddd_pain_points   ANY   /ddd-v-praxi-kde-to-boli`

- [ ] **Krok 3: Commit**

```bash
git add src/Controller/DddController.php
git commit -m "feat: přidat route ddd_pain_points pro kapitolu DDD v praxi"
```

---

### Task 2: Template — kostra, meta, TOC, úvod

**Files:**
- Create: `templates/ddd/ddd_pain_points.html.twig`

- [ ] **Krok 1: Vytvořit soubor s kompletní kostrou**

Vytvoř `templates/ddd/ddd_pain_points.html.twig` s následujícím obsahem (kostra + meta + JSON-LD + úvod + TOC):

```twig
{% extends 'base.html.twig' %}

{% block title %}DDD v praxi — kde to bolí | DDD Symfony{% endblock %}

{% block meta_description %}Katalog 20 reálných bolestivých míst při implementaci DDD v PHP a Symfony: transakce přes agregáty, Doctrine mapping, Outbox pattern, debugging Messengeru, validace, Anti-Corruption Layer, přesvědčení managementu a další.{% endblock %}

{% block meta_keywords %}DDD problémy, Doctrine transakce agregáty, Outbox pattern Symfony, Messenger debugging, idempotence handler, validace DDD, Anti-Corruption Layer PHP, strangler fig pattern, Symfony Form Command, API Platform agregát{% endblock %}

{% block structured_data %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "DDD v praxi — kde to bolí",
  "description": "{{ block('meta_description') }}",
  "keywords": "{{ block('meta_keywords') }}",
  "author": {
            "@type": "Person",
            "name": "Michal Katuščák"
  },
  "publisher": {
            "@type": "Person",
            "name": "Michal Katuščák"
  },
  "datePublished": "2026-03-26",
  "dateModified": "2026-03-26",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ app.request.schemeAndHttpHost }}{{ app.request.pathInfo }}"
  }
}
</script>
{% endblock %}

{% block body %}
    <article itemscope itemtype="https://schema.org/TechArticle">
    <h1 itemprop="headline">DDD v praxi — kde to bolí</h1>

    <p>
        Předchozí kapitoly pokryly teorii i pokročilé vzory: od
        <a href="{{ path('basic_concepts') }}">základních stavebních bloků</a> přes
        <a href="{{ path('cqrs') }}">CQRS</a> a
        <a href="{{ path('event_sourcing') }}">Event Sourcing</a> až po
        <a href="{{ path('sagas') }}">Ságy a Process Managery</a>.
        Teorie je přehledná. Implementace ale přináší třecí plochy, na které standardní DDD literatura
        většinou neupozorňuje — místa, kde se architektonické ideály střetávají s realitou frameworku,
        databáze, asynchronní infrastruktury nebo týmové dynamiky.
    </p>

    <p>
        Tato kapitola je <strong>katalog 20 reálných problémů</strong>, se kterými se setkávají týmy
        implementující DDD v PHP a Symfony. Pro každý problém najdete: popis situace, analýzu příčiny
        a doporučené řešení — tam kde je to výmluvné, s ukázkou kódu.
    </p>

    <div class="table-of-contents mb-4" role="navigation" aria-labelledby="toc-heading">
        <p id="toc-heading">Obsah kapitoly:</p>
        <ul>
            <li><a href="#doctrine">A — Doctrine vs. doménový model</a>
                <ul>
                    <li><a href="#a1-transakce">A1. Transakce přes agregáty a Unit of Work</a></li>
                    <li><a href="#a2-spinavy-em">A2. „Špinavý" EntityManager a nechtěné změny</a></li>
                    <li><a href="#a3-value-objects">A3. Mapping složitých Value Objects</a></li>
                    <li><a href="#a4-lazy-loading">A4. Lazy loading vs. bohaté agregáty</a></li>
                    <li><a href="#a5-identity">A5. Identity generation — kdy a kde</a></li>
                    <li><a href="#a6-polymorfismus">A6. Polymorfismus a discriminator map</a></li>
                </ul>
            </li>
            <li><a href="#async">B — Asynchronní infrastruktura</a>
                <ul>
                    <li><a href="#b1-outbox">B1. Outbox pattern — zaručené doručení událostí</a></li>
                    <li><a href="#b2-debugging">B2. Debugging ztracené zprávy v Messengeru</a></li>
                    <li><a href="#b3-idempotence">B3. Idempotence handlerů</a></li>
                    <li><a href="#b4-ordering">B4. Ordering zpráv</a></li>
                </ul>
            </li>
            <li><a href="#modelovani">C — Modelování</a>
                <ul>
                    <li><a href="#c1-validace">C1. Kde žije validace</a></li>
                    <li><a href="#c2-stavy">C2. Stavový automat bez anémického modelu</a></li>
                    <li><a href="#c3-acl">C3. Anti-Corruption Layer k externím API</a></li>
                    <li><a href="#c4-language">C4. Ubiquitous Language drift</a></li>
                </ul>
            </li>
            <li><a href="#symfony">D — Symfony-specifické třenice</a>
                <ul>
                    <li><a href="#d1-form">D1. Symfony Form vs. Command</a></li>
                    <li><a href="#d2-api-platform">D2. API Platform vs. doménové agregáty</a></li>
                    <li><a href="#d3-voter">D3. Security Voter vs. doménová oprávnění</a></li>
                </ul>
            </li>
            <li><a href="#tym">E — Organizace a tým</a>
                <ul>
                    <li><a href="#e1-management">E1. Business case pro refaktoring</a></li>
                    <li><a href="#e2-strangler">E2. Postupné zavedení — strangler fig</a></li>
                    <li><a href="#e3-silos">E3. Knowledge silos a bus factor</a></li>
                </ul>
            </li>
            <li><a href="#shrnuti">Shrnutí</a></li>
            <li><a href="#cviceni">Cvičení</a></li>
        </ul>
    </div>

    {# ═══════════════════════════════════════════════════════════
       BLOK A: Doctrine vs. doménový model
       ═══════════════════════════════════════════════════════════ #}
    <section id="doctrine" aria-labelledby="doctrine-heading">
    <h2 id="doctrine-heading">A — Doctrine vs. doménový model</h2>

    <p>
        Doctrine ORM je mocný nástroj, ale jeho interní model (Unit of Work, Identity Map, lazy loading)
        byl navržen pro jednoduchý CRUD. Bohaté doménové modely s ním přicházejí do konfliktu na šesti
        místech.
    </p>

    </article>
{% endblock %}

{% block toc %}<p class="toc-title">Na této stránce</p>{% endblock %}
```

> **Poznámka:** Template uzavíráme hned — jinak by nešel renderovat při průběžném ověřování.
> Tasky 3–7 vkládají obsah **před** řádek `    </article>` pomocí editoru (hledej tento řetězec).

- [ ] **Krok 2: Ověřit, že stránka renderuje bez chyby**

```bash
symfony server:start -d
curl -s http://127.0.0.1:8000/ddd-v-praxi-kde-to-boli | head -20
```

Očekáváno: HTTP 200, HTML s nadpisem "DDD v praxi — kde to bolí"

- [ ] **Krok 3: Commit**

```bash
git add templates/ddd/ddd_pain_points.html.twig
git commit -m "feat: přidat kostru template ddd_pain_points s meta, TOC a úvodem bloku A"
```

---

### Task 3: Template — Blok A (Doctrine)

**Files:**
- Modify: `templates/ddd/ddd_pain_points.html.twig` (append)

- [ ] **Krok 1: Přidat sekce A1–A3 do template**

Za poslední řádek (uzavírací `</p>` úvodu bloku A) přidej:

```twig
    {# ─── A1: Transakce přes agregáty ─────────────────────────── #}
    <section id="a1-transakce" aria-labelledby="a1-heading">
    <h3 id="a1-heading">A1. Transakce přes agregáty a Doctrine Unit of Work</h3>

    <p>
        <strong>Problém:</strong> DDD říká, že jedna transakce smí měnit nejvýše jeden agregát.
        Praxe ale přináší situace, kde potřebujete atomicky uložit změny ve dvou agregátech
        zároveň — například přesunout objednávku do stavu <em>Transferred</em> a zároveň
        potvrdit skladovou rezervaci. Doctrine sdílí jeden <code>EntityManager</code>
        (a tím jeden Unit of Work) přes celou aplikaci; jeden <code>flush()</code> commituje
        vše, co EM sleduje.
    </p>

    <p>
        <strong>Příčina:</strong> Doctrine Unit of Work je <em>session-scoped</em> — drží
        identity map všech načtených entit a při <code>flush()</code> uloží všechny změny
        najednou v jediné databázové transakci. To je výkonné pro CRUD, ale pro DDD to znamená,
        že neúmyslně načtená entita z jiného agregátu může být commitnuta společně s vaší
        záměrnou změnou.
    </p>

    <p>
        <strong>Řešení:</strong> Application Service funguje jako explicitní transakční hranice.
        Pokud váš use case skutečně vyžaduje změnu dvou agregátů atomicky (a nemůžete použít
        <a href="{{ path('sagas') }}">Outbox + Saga</a>), zavolejte explicitně
        <code>beginTransaction()</code> / <code>commit()</code> v Application Service
        a oba repozitáře volejte v rámci téže transakce. Toto je <strong>přijatelná výjimka
        z pravidla jeden agregát = jedna transakce</strong> za předpokladu, že oba agregáty
        leží ve stejném Bounded Context a stejné databázi.
    </p>

    <div class="tip" role="note" aria-labelledby="a1-code-heading">
        <h4 id="a1-code-heading">PHP: Application Service jako transakční hranice</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\Warehouse\Application\Service;

use App\Ordering\Domain\Repository\OrderRepository;
use App\Warehouse\Domain\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ConfirmTransferService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ReservationRepository $reservations,
        private readonly EntityManagerInterface $em,
    ) {}

    public function execute(ConfirmTransferCommand $command): void
    {
        $this->em->beginTransaction();
        try {
            $order       = $this->orders->get($command->orderId);
            $reservation = $this->reservations->get($command->reservationId);

            $order->markAsTransferred();
            $reservation->confirmFor($order->id());

            $this->orders->save($order);
            $this->reservations->save($reservation);

            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}</code></pre>
    </div>

    <div class="note" role="note">
        <p>
            Pokud oba agregáty nesdílejí databázi (nebo jsou v různých Bounded Contexts),
            použijte místo transakce
            <a href="{{ path('sagas') }}#b1-outbox">Outbox pattern</a> nebo Sagu.
            Atomická cross-context transakce je architektonický zápach.
        </p>
    </div>

    </section>

    {# ─── A2: Špinavý EntityManager ─────────────────────────────── #}
    <section id="a2-spinavy-em" aria-labelledby="a2-heading">
    <h3 id="a2-heading">A2. „Špinavý" EntityManager a nechtěné změny</h3>

    <p>
        <strong>Problém:</strong> V read-heavy akcích (příprava dat pro API response, sestavení
        read modelu) načtete entitu z databáze, provedete výpočet, ale <em>neuložíte nic</em>.
        Přesto se při prvním <code>flush()</code> kdekoli v requestu (třeba v jiné části aplikace)
        commitují změny do databáze — protože jste nenápadně modifikovali entitu, kterou
        Doctrine stále sleduje.
    </p>

    <p>
        <strong>Příčina:</strong> Doctrine Identity Map zapamatuje každý načtený objekt
        a při <code>flush()</code> porovnává aktuální stav se snapshoty uloženými při
        načtení (<em>change tracking</em>). Volání getterů, které interně modifikují stav
        (lazy-init kolekce, computed fields), může způsobit detekci „změny".
    </p>

    <p>
        <strong>Řešení — tři přístupy podle situace:</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>Situace</th>
                <th>Řešení</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Read model v rámci téhož requestu</td>
                <td><code>$em-&gt;detach($entity)</code> po načtení — EM přestane entitu sledovat</td>
            </tr>
            <tr>
                <td>Komplexní read queries</td>
                <td>Použijte <code>HYDRATE_ARRAY</code> nebo raw SQL přes <code>$em-&gt;getConnection()</code> — EM nehydratuje objekty</td>
            </tr>
            <tr>
                <td>Celý controller je read-only</td>
                <td>Injektujte separátní <code>EntityManager</code> nakonfigurovaný jako read-only (second EM v Symfony)</td>
            </tr>
        </tbody>
    </table>

    </section>

    {# ─── A3: Mapping Value Objects ──────────────────────────────── #}
    <section id="a3-value-objects" aria-labelledby="a3-heading">
    <h3 id="a3-heading">A3. Mapping složitých Value Objects</h3>

    <p>
        <strong>Problém:</strong> Doctrine <code>#[Embedded]</code> funguje dobře pro jednoduché
        VO (jméno + příjmení → dva sloupce). Narazíte ale na jeho limity, jakmile potřebujete:
        polymorfní VO (různé typy cen), nullable VO v kolekcích, VO s vlastní serializační logikou
        (Money = integer + string), nebo VO, které se mapují na jiný datový typ než default
        (enum, JSONB, custom SQL type).
    </p>

    <p>
        <strong>Řešení — Custom Doctrine Type:</strong> Implementujte <code>Type</code>
        z <code>Doctrine\DBAL\Types</code>. Typ definuje, jak se PHP objekt serializuje
        do SQL hodnoty a zpět. Zaregistrujte typ v <code>config/packages/doctrine.yaml</code>.
    </p>

    <div class="tip" role="note" aria-labelledby="a3-code-heading">
        <h4 id="a3-code-heading">PHP: Custom Type pro Money Value Object</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Doctrine\Type;

use App\SharedKernel\Domain\ValueObject\Money;
use App\SharedKernel\Domain\ValueObject\Currency;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class MoneyType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(50)'; // formát: "12345_CZK"
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }
        [$amount, $currencyCode] = explode('_', (string) $value, 2);

        return new Money((int) $amount, new Currency($currencyCode));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        /** @var Money $value */
        return $value->amountInCents() . '_' . $value->currency()->code();
    }

    public function getName(): string
    {
        return 'money';
    }
}
</code></pre>
    </div>

    <p>
        Typ zaregistrujte v <code>config/packages/doctrine.yaml</code>:
    </p>

    <pre><code class="language-yaml">doctrine:
    dbal:
        types:
            money: App\SharedKernel\Infrastructure\Doctrine\Type\MoneyType</code></pre>

    <p>
        Poté ho použijte v entitě:
    </p>

    <pre><code class="language-php">#[ORM\Column(type: 'money', nullable: true)]
private ?Money $price = null;</code></pre>

    <div class="note" role="note">
        <p>
            Pro <strong>polymorfní VO</strong> (různé typy platby: karta, hotovost, voucher)
            zvažte místo dědičnosti <strong>Value Object s diskriminátorem</strong>:
            uložte typ jako enum do jednoho sloupce a detaily jako JSON do druhého.
            Tím se vyhnete discriminator map, která je těžkopádná pro VO.
        </p>
    </div>

    </section>
```

- [ ] **Krok 2: Přidat sekce A4–A6**

Pokračuj v souboru — připoj za sekci A3:

```twig
    {# ─── A4: Lazy loading ──────────────────────────────────────── #}
    <section id="a4-lazy-loading" aria-labelledby="a4-heading">
    <h3 id="a4-heading">A4. Lazy loading vs. bohaté agregáty</h3>

    <p>
        <strong>Problém:</strong> Doctrine defaultně načítá asociace lazy — místo skutečného
        objektu vloží do property proxy třídu, která se inicializuje až při prvním přístupu.
        Bohaté agregáty (metody jako <code>totalPrice()</code>, <code>items()</code>)
        mohou neúmyslně triggerovat lazy load <em>mimo otevřenou transakci</em> nebo
        <em>po detach()</em>, což vyústí ve výjimku
        <code>LazyInitializationException</code>.
    </p>

    <p>
        <strong>Příčina:</strong> Lazy proxy je infrastrukturní koncept — doménový model
        o ní neví a nesmí vědět. Bohužel, pokud Doctrine vloží proxy na místo
        <code>OrderItems</code>, doménová metoda <code>$order-&gt;items()</code>
        v sobě implicitně spoléhá na aktivní databázové připojení.
    </p>

    <p>
        <strong>Řešení — podle složitosti situace:</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>Situace</th>
                <th>Řešení</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Kolekce vždy potřebná s agregátem</td>
                <td><code>fetch: 'EAGER'</code> na asociaci — načte v jednom JOIN</td>
            </tr>
            <tr>
                <td>Kolekce potřebná jen někdy</td>
                <td>Repozitář nabídne dvě metody: <code>get()</code> (lazy) a <code>getWithItems()</code> (EAGER JOIN)</td>
            </tr>
            <tr>
                <td>Serializace / JSON response</td>
                <td>Nikdy neserializujte agregát přímo — sestavte DTO z načtených dat uvnitř transakce</td>
            </tr>
        </tbody>
    </table>

    </section>

    {# ─── A5: Identity generation ────────────────────────────────── #}
    <section id="a5-identity" aria-labelledby="a5-heading">
    <h3 id="a5-heading">A5. Identity generation — kdy a kde</h3>

    <p>
        <strong>Problém:</strong> Doctrine standardně generuje ID v databázi
        (<code>SEQUENCE</code>, <code>AUTO_INCREMENT</code>). To znamená, že nově vytvořený
        agregát nemá ID, dokud není persistován a flushed — porušuje to doménový invariant,
        že každý agregát musí mít identitu od okamžiku vzniku.
    </p>

    <p>
        <strong>Příčina:</strong> Databázové generování ID je výkonné, ale váže vznik identity
        na infrastrukturu. Doménový model by neměl vědět o databázi; identita patří do domény.
    </p>

    <p>
        <strong>Řešení:</strong> Generujte UUID v doméně, v konstruktoru agregátu.
        Doctrine nakonfigurujte s <code>strategy: 'NONE'</code> — ID předáváte sami,
        Doctrine ho jen uloží.
    </p>

    <div class="tip" role="note" aria-labelledby="a5-code-heading">
        <h4 id="a5-code-heading">PHP: UUID v konstruktoru agregátu (PHP 8.4 + Symfony Uid)</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final class OrderId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self((string) Uuid::v7()); // UUIDv7 — time-sortable
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid OrderId: {$value}");
        }
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

// V agregátu:
final class Order
{
    private OrderId $id;

    public function __construct(CustomerId $customerId)
    {
        $this->id = OrderId::generate(); // identita vzniká v doméně
        // ...
    }
}
</code></pre>
    </div>

    <p>
        Doctrine mapping pro UUID ID:
    </p>

    <pre><code class="language-php">#[ORM\Id]
#[ORM\Column(type: 'string', length: 36)]
#[ORM\GeneratedValue(strategy: 'NONE')] // Doctrine ID nepřiřazuje
private string $id;</code></pre>

    </section>

    {# ─── A6: Polymorfismus a discriminator map ───────────────────── #}
    <section id="a6-polymorfismus" aria-labelledby="a6-heading">
    <h3 id="a6-heading">A6. Polymorfismus a discriminator map</h3>

    <p>
        <strong>Problém:</strong> Potřebujete modelovat hierarchii — například různé typy
        doručení (<code>HomeDelivery</code>, <code>PickupPoint</code>, <code>LockerDelivery</code>).
        Doctrine nabízí <code>InheritanceType::SINGLE_TABLE</code> nebo
        <code>JOINED</code> s discriminator map. Jenže: přidání nového subtypu vyžaduje
        úpravu anotace na <em>rodičovské</em> třídě, a discriminator map je zapsána v kódu
        jako statický seznam — narušuje Open/Closed Principle.
    </p>

    <p>
        <strong>Řešení — dvě alternativy:</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>Přístup</th>
                <th>Kdy použít</th>
                <th>Nevýhoda</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Value Object místo dědičnosti</strong></td>
                <td>Varianty se liší jen daty, ne chováním</td>
                <td>Složitý switch pro chování</td>
            </tr>
            <tr>
                <td><strong>Flat table + Custom Type</strong></td>
                <td>Varianty mají odlišné chování</td>
                <td>JSON sloupec pro detaily ztrácí typovou bezpečnost</td>
            </tr>
            <tr>
                <td><strong>Discriminator map (Doctrine default)</strong></td>
                <td>Málo variant, stabilní hierarchie</td>
                <td>Rigidní, narušuje OCP</td>
            </tr>
        </tbody>
    </table>

    <p>
        Pro většinu DDD scénářů doporučujeme <strong>Value Object s type fieldem</strong>:
        jeden enum sloupec pro typ, jeden JSON sloupec pro specifická data varianty.
        Logika se přesouvá do doménových metod, které přijímají VO jako parametr —
        ne do dědičnosti.
    </p>

    </section>

    </section>{# konec bloku A #}
```

- [ ] **Krok 3: Ověřit, že sekce jsou v souboru**

```bash
grep -c '<section id="a' templates/ddd/ddd_pain_points.html.twig
```

Očekáváno: `6`

- [ ] **Krok 4: Commit**

```bash
git add templates/ddd/ddd_pain_points.html.twig
git commit -m "feat: přidat blok A (Doctrine) do kapitoly DDD v praxi"
```

---

### Task 4: Template — Blok B (Asynchronní infrastruktura)

**Files:**
- Modify: `templates/ddd/ddd_pain_points.html.twig` (append)

- [ ] **Krok 1: Přidat sekce B1–B2**

Za uzavírací `</section>{# konec bloku A #}` přidej:

```twig
    {# ═══════════════════════════════════════════════════════════
       BLOK B: Asynchronní infrastruktura
       ═══════════════════════════════════════════════════════════ #}
    <section id="async" aria-labelledby="async-heading">
    <h2 id="async-heading">B — Asynchronní infrastruktura</h2>

    <p>
        Symfony Messenger a asynchronní fronty přinášejí distribuovanou komunikaci —
        a s ní distribuované problémy: zprávy se ztrácejí, doručují dvakrát, přicházejí
        v nesprávném pořadí. Tato sekce pokrývá čtyři nejčastější bolesti.
    </p>

    {# ─── B1: Outbox pattern ─────────────────────────────────────── #}
    <section id="b1-outbox" aria-labelledby="b1-heading">
    <h3 id="b1-heading">B1. Outbox pattern — zaručené doručení doménových událostí</h3>

    <p>
        <strong>Problém:</strong> Uložíte agregát (<code>flush()</code> proběhne úspěšně),
        ale před tím, než stihnete odeslat doménovou událost do Messengeru, server spadne.
        Událost se ztratí — databáze je konzistentní, ale žádný subscriber ji nikdy
        nezpracuje. Platba proběhla, ale sklad nebyl upozorněn.
    </p>

    <p>
        <strong>Příčina:</strong> <code>flush()</code> a <code>$bus-&gt;dispatch()</code>
        jsou dvě separátní operace bez atomické záruky. Neexistuje způsob, jak je
        zabalit do jedné transakce — databáze a message broker jsou různé systémy.
    </p>

    <p>
        <strong>Řešení — Outbox pattern:</strong> Místo přímého odeslání do brokeru
        uložte událost do <code>outbox</code> tabulky <em>ve stejné databázové transakci</em>
        jako agregát. Separátní worker pak z tabulky čte a odešle zprávy do Messengeru.
        Atomicita je garantována databázovou transakcí; at-least-once doručení zajišťuje worker.
    </p>

    <div class="tip" role="note" aria-labelledby="b1-code-heading">
        <h4 id="b1-code-heading">PHP: OutboxEvent entita a OutboxPublisher service</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Outbox;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_events')]
final class OutboxEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $eventType;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    public function __construct(string $eventType, array $payload)
    {
        $this->eventType = $eventType;
        $this->payload   = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function markAsPublished(): void
    {
        $this->publishedAt = new \DateTimeImmutable();
    }

    public function isPublished(): bool { return $this->publishedAt !== null; }
    public function eventType(): string  { return $this->eventType; }
    public function payload(): array     { return $this->payload; }
}
</code></pre>
    </div>

    <p>
        Doctrine Event Subscriber uloží událost do outboxu ve stejné transakci jako agregát:
    </p>

    <pre><code class="language-php">final class OutboxEventSubscriber implements EventSubscriberInterface
{
    public function postFlush(PostFlushEventArgs $args): void
    {
        // Po každém flush projdeme agregáty a uložíme jejich events do outboxu
        foreach ($this->pendingEvents as $event) {
            $outbox = new OutboxEvent(
                get_class($event),
                $this->serializer->normalize($event),
            );
            $args->getObjectManager()->persist($outbox);
        }
        $args->getObjectManager()->flush(); // druhý flush jen pro outbox záznamy
        $this->pendingEvents = [];
    }
}</code></pre>

    <div class="note" role="note">
        <p>
            Symfony Messenger v Symfony 7+ nabízí vlastní <strong>Doctrine Transport</strong>,
            který ukládá zprávy do databáze a garantuje at-least-once doručení bez nutnosti
            vlastního Outbox kódu. Zvažte jeho použití jako alternativu před implementací
            vlastního Outbox patternu.
        </p>
    </div>

    </section>

    {# ─── B2: Debugging Messengeru ───────────────────────────────── #}
    <section id="b2-debugging" aria-labelledby="b2-heading">
    <h3 id="b2-heading">B2. Debugging ztracené zprávy v Messengeru</h3>

    <p>
        <strong>Problém:</strong> Zpráva odešla do async fronty. Worker běží.
        Handler ale nikdy nezavolal. Jak zjistit, kde zpráva skončila?
    </p>

    <p>
        <strong>Postup debuggingu:</strong>
    </p>

    <ol>
        <li>
            <strong>Zkontrolujte failed transport:</strong>
            <pre><code class="language-bash">php bin/console messenger:failed:show</code></pre>
            Pokud je zpráva zde, zobrazí se s chybou. Znovu ji zpracujte:
            <pre><code class="language-bash">php bin/console messenger:failed:retry</code></pre>
        </li>
        <li>
            <strong>Zapněte verbose logging:</strong> V <code>config/packages/monolog.yaml</code>
            přidejte handler pro <code>messenger</code> channel na úroveň <code>debug</code>.
            Každý dispatch, receive a zpracování se zaloguje.
        </li>
        <li>
            <strong>Correlation ID middleware:</strong> Přidejte vlastní middleware, který
            přiřadí každé zprávě UUID a loguje ho při dispatch i při receive. Pak hledáte
            v logu podle ID.
        </li>
    </ol>

    <div class="tip" role="note" aria-labelledby="b2-code-heading">
        <h4 id="b2-code-heading">PHP: Middleware pro Correlation ID logging</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Uid\Uuid;

final class CorrelationIdMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(CorrelationIdStamp::class)
            ?? new CorrelationIdStamp((string) Uuid::v7());

        $this->logger->info('Messenger: processing message', [
            'correlation_id'  => $stamp->correlationId,
            'message_class'   => $envelope->getMessage()::class,
        ]);

        return $stack->next()->handle(
            $envelope->with($stamp),
            $stack,
        );
    }
}
</code></pre>
    </div>

    <p>
        Zaregistrujte middleware v <code>config/packages/messenger.yaml</code>:
    </p>

    <pre><code class="language-yaml">framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - App\SharedKernel\Infrastructure\Messenger\CorrelationIdMiddleware</code></pre>

    </section>
```

- [ ] **Krok 2: Přidat sekce B3–B4**

Za uzavírací `</section>` sekce B2 přidej:

```twig
    {# ─── B3: Idempotence handlerů ──────────────────────────────── #}
    <section id="b3-idempotence" aria-labelledby="b3-heading">
    <h3 id="b3-heading">B3. Idempotence handlerů</h3>

    <p>
        <strong>Problém:</strong> Messenger garantuje <em>at-least-once</em> doručení —
        nikoli exactly-once. Pokud worker zprávu zpracuje, ale před potvrzením (ack)
        spadne, broker zprávu znovu doručí. Handler ji zpracuje podruhé. Výsledkem může
        být dvojitá platba, duplicitní objednávka nebo zdvojený email.
    </p>

    <p>
        <strong>Řešení — Idempotency Middleware s deduplikační tabulkou:</strong>
        Každá zpráva nese <code>IdempotencyStamp</code> s unikátním klíčem
        (vygenerovaným při prvním odeslání). Middleware před zpracováním zkontroluje
        databázovou tabulku — pokud klíč existuje, zprávu přeskočí.
    </p>

    <div class="tip" role="note" aria-labelledby="b3-code-heading">
        <h4 id="b3-code-heading">PHP: IdempotencyMiddleware</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Messenger;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class IdempotencyMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Connection $connection) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(IdempotencyStamp::class);

        if ($stamp === null) {
            return $stack->next()->handle($envelope, $stack); // zpráva bez klíče: vždy zpracuj
        }

        $alreadyProcessed = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM processed_messages WHERE idempotency_key = ?',
            [$stamp->key],
        );

        if ($alreadyProcessed) {
            return $envelope; // duplikát — přeskočit bez zpracování
        }

        $result = $stack->next()->handle($envelope, $stack);

        $this->connection->insert('processed_messages', [
            'idempotency_key' => $stamp->key,
            'processed_at'    => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return $result;
    }
}
</code></pre>
    </div>

    <div class="note" role="note">
        <p>
            Tabulka <code>processed_messages</code> poroste bez omezení. Přidejte
            pravidelný cleanup (cron) nebo <code>TTL</code> index pro automatické mazání
            starých záznamů. Obvyklá retence je 7–30 dní — doba, po které broker
            přestane doručovat retries.
        </p>
    </div>

    </section>

    {# ─── B4: Ordering zpráv ─────────────────────────────────────── #}
    <section id="b4-ordering" aria-labelledby="b4-heading">
    <h3 id="b4-heading">B4. Ordering zpráv — zpráva B dorazí před A</h3>

    <p>
        <strong>Problém:</strong> Máte dva workery zpracovávající stejnou frontu paralelně.
        Obě události <code>OrderPlaced</code> a <code>OrderShipped</code> jsou odeslány
        za sebou, ale <code>OrderShipped</code> zpracuje jiný worker rychleji —
        handler se pokusí označit objednávku jako odeslanou, ale objednávka ještě
        neexistuje (nebo je ve špatném stavu).
    </p>

    <p>
        <strong>Řešení — tři přístupy podle kontextu:</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>Přístup</th>
                <th>Kdy použít</th>
                <th>Trade-off</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Optimistický retry</strong></td>
                <td>Závislost je krátkodobá (ms)</td>
                <td>Handler hodí výjimku → Messenger retry s <code>DelayStamp</code></td>
            </tr>
            <tr>
                <td><strong>Jeden worker na agregát</strong></td>
                <td>Ordering je kritický</td>
                <td>Nižší throughput, ale garantované pořadí per-aggregate</td>
            </tr>
            <tr>
                <td><strong>Inbox buffer</strong></td>
                <td>Komplexní závislosti</td>
                <td>Handler uloží zprávu do "inbox" tabulky a zpracuje ji až po splnění podmínek</td>
            </tr>
        </tbody>
    </table>

    <p>
        Nejjednodušší řešení: handler při nesprávném stavu agregátu hodí
        <code>UnrecoverableMessageHandlingException</code> — Messenger to zaloguje
        jako chybu a zprávu <em>neretryuje</em>. Nebo hodí standardní výjimku
        a Messenger zprávu odloží (retry s exponential backoff).
    </p>

    </section>

    </section>{# konec bloku B #}
```

- [ ] **Krok 3: Ověřit sekce B v souboru**

```bash
grep -c '<section id="b' templates/ddd/ddd_pain_points.html.twig
```

Očekáváno: `4`

- [ ] **Krok 4: Commit**

```bash
git add templates/ddd/ddd_pain_points.html.twig
git commit -m "feat: přidat blok B (Async infrastruktura) do kapitoly DDD v praxi"
```

---

### Task 5: Template — Blok C (Modelování)

**Files:**
- Modify: `templates/ddd/ddd_pain_points.html.twig` (append)

- [ ] **Krok 1: Přidat sekce C1–C4**

Za `</section>{# konec bloku B #}` přidej:

```twig
    {# ═══════════════════════════════════════════════════════════
       BLOK C: Modelování
       ═══════════════════════════════════════════════════════════ #}
    <section id="modelovani" aria-labelledby="modelovani-heading">
    <h2 id="modelovani-heading">C — Modelování</h2>

    <p>
        Správné doménové modelování je obtížnější než implementace — vyžaduje disciplínu
        v rozhodnutích, která se zdají triviální, dokud nezpůsobí problém.
    </p>

    {# ─── C1: Kde žije validace ──────────────────────────────────── #}
    <section id="c1-validace" aria-labelledby="c1-heading">
    <h3 id="c1-heading">C1. Kde žije validace</h3>

    <p>
        <strong>Problém:</strong> Validace je rozeseta na třech místech: Symfony Validator
        (anotace na DTO), Application Service (business podmínky) a doménový konstruktor
        (invarianty). Výsledkem je buď duplicita (stejná pravidla na dvou místech),
        nebo díry (pravidlo chybí na jednom místě).
    </p>

    <table>
        <thead>
            <tr>
                <th>Typ validace</th>
                <th>Kde patří</th>
                <th>Příklad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Formátová validace</strong></td>
                <td>API / formulářová vrstva (Symfony Validator)</td>
                <td>Email musí být validní formát, číslo musí být kladné</td>
            </tr>
            <tr>
                <td><strong>Doménový invariant</strong></td>
                <td>Konstruktor / metoda agregátu nebo VO</td>
                <td>Množství nesmí být nulové, cena nesmí být záporná</td>
            </tr>
            <tr>
                <td><strong>Business pravidlo</strong></td>
                <td>Domain Service nebo Application Service</td>
                <td>Zákazník nesmí mít více než 5 otevřených objednávek</td>
            </tr>
            <tr>
                <td><strong>Databázová unikátnost</strong></td>
                <td>Databázový unique constraint + Application Service check</td>
                <td>Email zákazníka musí být unikátní v systému</td>
            </tr>
        </tbody>
    </table>

    <p>
        <strong>Klíčové pravidlo:</strong> Doménový invariant vždy vynucujte v doméně —
        nikdy nespoléhejte na validaci ve vyšší vrstvě, protože doménový objekt může být
        sestaven i z jiného místa (CLI command, test, import). Symfony Validator je
        <em>první linie obrany</em> pro uživatelský vstup, nikoli náhrada doménové validace.
    </p>

    </section>

    {# ─── C2: Stavový automat ────────────────────────────────────── #}
    <section id="c2-stavy" aria-labelledby="c2-heading">
    <h3 id="c2-heading">C2. Stavový automat bez anémického modelu</h3>

    <p>
        <strong>Problém:</strong> Objednávka prochází stavy: <em>Draft → Placed → Paid →
        Shipped → Delivered → Cancelled</em>. Anémický přístup: <code>$order-&gt;setStatus('shipped')</code>
        — stav se změní bez guard conditions, bez side effectů, bez kontroly, zda přechod
        dává smysl.
    </p>

    <p>
        <strong>Řešení:</strong> Explicitní metody pro každý přechod. Metoda ověřuje,
        zda je přechod validní (guard condition), provede změnu stavu a zaregistruje
        doménovou událost.
    </p>

    <pre><code class="language-php">final class Order
{
    private OrderStatus $status = OrderStatus::Draft;

    public function place(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new \DomainException("Objednávku lze odeslat pouze ve stavu Draft.");
        }
        $this->status = OrderStatus::Placed;
        $this->record(new OrderPlaced($this->id));
    }

    public function ship(TrackingNumber $trackingNumber): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new \DomainException("Objednávku lze expedovat pouze po zaplacení.");
        }
        $this->status         = OrderStatus::Shipped;
        $this->trackingNumber = $trackingNumber;
        $this->record(new OrderShipped($this->id, $trackingNumber));
    }
}</code></pre>

    <div class="note" role="note">
        <p>
            <strong>Symfony Workflow</strong> může spravovat přechody stavů — ale jako
            <em>infrastrukturní helper</em>, nikoli jako součást doménového modelu.
            Doménový objekt nesmí záviset na <code>WorkflowInterface</code>. Voter / Controller
            může použít Workflow pro UI logiku; doménová metoda ověřuje invariant sama.
        </p>
    </div>

    </section>

    {# ─── C3: Anti-Corruption Layer ──────────────────────────────── #}
    <section id="c3-acl" aria-labelledby="c3-heading">
    <h3 id="c3-heading">C3. Anti-Corruption Layer k externím API</h3>

    <p>
        <strong>Problém:</strong> Stripe vrací <code>\Stripe\Charge</code>, Ares vrací
        XML nebo pole, Fakturoid vrací vlastní DTO. Pokud tato data z externích systémů
        prosakují přímo do doménového kódu, změna externího API = změna doménového modelu.
    </p>

    <p>
        <strong>Řešení — Port &amp; Adapter (Hexagonální architektura):</strong>
        Doménový model definuje <strong>Port</strong> (interface) popisující, co potřebuje
        od externího systému — v doménových pojmech. Infrastrukturní vrstva implementuje
        <strong>Adapter</strong>, který přeloží externí API do doménového rozhraní.
    </p>

    <div class="tip" role="note" aria-labelledby="c3-code-heading">
        <h4 id="c3-code-heading">PHP: Port v doméně + Adapter v infrastruktuře</h4>
        <pre><code class="language-php">&lt;?php

// Port — v doméně (App\Payment\Domain\Port)
interface PaymentGateway
{
    /** @throws PaymentFailedException */
    public function charge(Money $amount, PaymentToken $token): PaymentId;

    /** @throws RefundFailedException */
    public function refund(PaymentId $id, Money $amount): void;
}

// Adapter — v infrastruktuře (App\Payment\Infrastructure\Stripe)
final class StripePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly \Stripe\StripeClient $stripe) {}

    public function charge(Money $amount, PaymentToken $token): PaymentId
    {
        try {
            $charge = $this->stripe->charges->create([
                'amount'   => $amount->amountInCents(),
                'currency' => strtolower($amount->currency()->code()),
                'source'   => $token->value(),
            ]);
            return PaymentId::fromString($charge->id);
        } catch (\Stripe\Exception\CardException $e) {
            throw new PaymentFailedException($e->getMessage(), previous: $e);
        }
    }

    public function refund(PaymentId $id, Money $amount): void
    {
        $this->stripe->refunds->create([
            'charge' => $id->toString(),
            'amount' => $amount->amountInCents(),
        ]);
    }
}
</code></pre>
    </div>

    <p>
        Doménový kód pracuje pouze s <code>PaymentGateway</code> rozhraním — nic neví
        o Stripe. Výměna platební brány (Stripe → Adyen) vyžaduje pouze nový Adapter,
        doménový kód se nemění.
    </p>

    </section>

    {# ─── C4: Ubiquitous Language drift ──────────────────────────── #}
    <section id="c4-language" aria-labelledby="c4-heading">
    <h3 id="c4-heading">C4. Ubiquitous Language drift</h3>

    <p>
        <strong>Problém:</strong> Po šesti měsících vývoje kód mluví jiným jazykem než
        doménový expert. V kódu je <code>Invoice</code>, zákazník říká „faktura",
        účetní systém zná „Bill". Třída <code>Order</code> pokrývá pojmy, které
        business rozděluje na „nabídku", „objednávku" a „smlouvu". Vývojáři si
        přestávají být jisti, co třída modeluje.
    </p>

    <p>
        <strong>Příčina:</strong> Ubiquitous Language není statický artefakt — vyvíjí se
        s pochopením domény. Bez aktivní správy kód zaostává za aktuálním chápáním.
    </p>

    <p>
        <strong>Opatření — čtyři praktiky:</strong>
    </p>

    <ol>
        <li>
            <strong>Doménový glosář v repozitáři</strong> (<code>docs/glossary.md</code>) —
            živý dokument, kde každý pojem má definici, synonyma a odkaz na třídu v kódu.
            Aktualizuje se při každém přejmenování.
        </li>
        <li>
            <strong>Architecture Decision Records (ADR)</strong> — při každém záměrném
            přejmenování konceptu zapište ADR s důvodem. Budoucí vývojář pochopí, proč
            <code>Contract</code> nahradil <code>Order</code>.
        </li>
        <li>
            <strong>Event Storming jako pravidelná aktivita</strong> — ne jednorázový workshop
            na začátku projektu, ale čtvrtletní revize s doménovými experty.
        </li>
        <li>
            <strong>Living documentation přes testy</strong> — BDD-style popis v testech
            (<code>it_places_an_order_when_items_are_in_stock()</code>) tvoří čitelnou dokumentaci
            aktuálního chování.
        </li>
    </ol>

    </section>

    </section>{# konec bloku C #}
```

- [ ] **Krok 2: Commit**

```bash
git add templates/ddd/ddd_pain_points.html.twig
git commit -m "feat: přidat blok C (modelování) do kapitoly DDD v praxi"
```

---

### Task 6: Template — Blok D (Symfony-specifické třenice)

**Files:**
- Modify: `templates/ddd/ddd_pain_points.html.twig` (append)

- [ ] **Krok 1: Přidat sekce D1–D3**

Za `</section>{# konec bloku C #}` přidej:

```twig
    {# ═══════════════════════════════════════════════════════════
       BLOK D: Symfony-specifické třenice
       ═══════════════════════════════════════════════════════════ #}
    <section id="symfony" aria-labelledby="symfony-heading">
    <h2 id="symfony-heading">D — Symfony-specifické třenice</h2>

    <p>
        Symfony je mocný framework, ale některé jeho konvence jsou navrženy pro CRUD aplikace.
        Tato sekce popisuje tři místa, kde framework-first přístup koliduje s DDD.
    </p>

    {# ─── D1: Form vs Command ────────────────────────────────────── #}
    <section id="d1-form" aria-labelledby="d1-heading">
    <h3 id="d1-heading">D1. Symfony Form vs. Command</h3>

    <p>
        <strong>Problém:</strong> <code>FormType</code> ve Symfony chce mutable objekt,
        který hydratuje daty z requestu. Application Command by naopak měl být immutable
        DTO sestaven z validovaných dat. Tyto dva světy se obtížně kombinují bez toho,
        aby <code>FormInterface</code> pronikl do aplikační vrstvy.
    </p>

    <p>
        <strong>Řešení:</strong> Form mapuje na <strong>plain mutable DTO</strong>
        (formulářový objekt), Application Service pak sestaví immutable Command.
        Žádná ze dvou vrstev neví o existenci té druhé.
    </p>

    <pre><code class="language-php">// 1. Formulářový objekt — mutable, framework-friendly
final class PlaceOrderFormData
{
    public string $customerId = '';
    public array  $items      = [];
}

// 2. FormType pracuje s formulářovým objektem
$form = $this->createForm(PlaceOrderType::class, new PlaceOrderFormData());
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    /** @var PlaceOrderFormData $data */
    $data = $form->getData();

    // 3. Controller sestaví Command — immutable, doménově typovaný
    $command = new PlaceOrderCommand(
        customerId: CustomerId::fromString($data->customerId),
        items: array_map(
            fn($i) => new OrderItemDto($i['productId'], (int) $i['quantity']),
            $data->items,
        ),
    );

    $this->commandBus->dispatch($command);
}</code></pre>

    <p>
        <code>PlaceOrderCommand</code> je readonly PHP class — doménový kód s ní pracuje
        bez jakékoli závislosti na Symfony Form komponentě.
    </p>

    </section>

    {# ─── D2: API Platform vs. agregáty ─────────────────────────── #}
    <section id="d2-api-platform" aria-labelledby="d2-heading">
    <h3 id="d2-heading">D2. API Platform vs. doménové agregáty</h3>

    <p>
        <strong>Problém:</strong> API Platform ve výchozím nastavení očekává přímý přístup
        k Doctrine entitám — čte a zapisuje je pomocí vestavěných Provider a Processor.
        Agregáty ale nechceme serializovat přímo (interní stav by pronikl do API)
        ani nechat API Platform je modifikovat bez Application Service.
    </p>

    <p>
        <strong>Řešení:</strong> Vystavte API Platform <strong>API Resource DTO</strong>
        (ne agregát) a implementujte vlastní <code>StateProvider</code>
        a <code>StateProcessor</code>, které fungují jako adaptéry k Application Services.
    </p>

    <div class="tip" role="note" aria-labelledby="d2-code-heading">
        <h4 id="d2-code-heading">PHP: StateProcessor jako adapter k Application Service</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Ordering\Application\Command\PlaceOrderCommand;
use App\Ordering\Application\Command\PlaceOrderCommandBus;

// API resource DTO — nikdy agregát
#[ApiResource(operations: [new Post(processor: PlaceOrderProcessor::class)])]
final class OrderResource
{
    public string $customerId;
    public array  $items;
    // Pouze to, co API má vidět
}

// StateProcessor — tenká vrstva
final class PlaceOrderProcessor implements ProcessorInterface
{
    public function __construct(private readonly PlaceOrderCommandBus $bus) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderResponse
    {
        /** @var OrderResource $data */
        $command = new PlaceOrderCommand(
            customerId: CustomerId::fromString($data->customerId),
            items: $data->items,
        );

        $orderId = $this->bus->dispatch($command);

        return new OrderResponse($orderId->toString());
    }
}
</code></pre>
    </div>

    </section>

    {# ─── D3: Security Voter vs. doménová oprávnění ──────────────── #}
    <section id="d3-voter" aria-labelledby="d3-heading">
    <h3 id="d3-heading">D3. Security Voter vs. doménová oprávnění</h3>

    <p>
        <strong>Problém:</strong> Business pravidla přístupu jsou součástí domény —
        například „objednávku může zrušit zákazník nebo admin, ale pouze do 24 hodin
        od vytvoření a pouze pokud ještě nebyla expedována." Symfony Security Voter
        žije v infrastrukturní vrstvě a závisí na frameworku. Pokud logiku napíšete
        přímo ve Voteru, stane se netestovatelnou bez Symfony kontejneru.
    </p>

    <p>
        <strong>Řešení:</strong> Voter funguje jako <strong>tenký adaptér</strong>,
        který deleguje rozhodnutí na doménovou metodu agregátu. Doménová metoda je
        čistá funkce — testovatelná bez frameworku.
    </p>

    <div class="tip" role="note" aria-labelledby="d3-code-heading">
        <h4 id="d3-code-heading">PHP: Voter jako tenký adaptér + doménová metoda</h4>
        <pre><code class="language-php">&lt;?php

declare(strict_types=1);

// Doménová metoda v agregátu — testovatelná bez frameworku
final class Order
{
    public function canBeCancelledBy(UserId $userId): bool
    {
        if ($this->status === OrderStatus::Shipped || $this->status === OrderStatus::Delivered) {
            return false;
        }
        $withinWindow = $this->placedAt > new \DateTimeImmutable('-24 hours');

        return $withinWindow && $this->customerId->equals($userId);
    }
}

// Voter — pouze adaptér, žádná business logika
final class OrderVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'ORDER_CANCEL' && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof SecurityUser) {
            return false;
        }

        /** @var Order $subject */
        return $subject->canBeCancelledBy(UserId::fromString($user->getId()));
    }
}
</code></pre>
    </div>

    </section>

    </section>{# konec bloku D #}
```

- [ ] **Krok 2: Commit**

```bash
git add templates/ddd/ddd_pain_points.html.twig
git commit -m "feat: přidat blok D (Symfony-specifické třenice) do kapitoly DDD v praxi"
```

---

### Task 7: Template — Blok E, závěr, cvičení

**Files:**
- Modify: `templates/ddd/ddd_pain_points.html.twig` (append)

- [ ] **Krok 1: Přidat sekce E1–E3**

Za `</section>{# konec bloku D #}` přidej:

```twig
    {# ═══════════════════════════════════════════════════════════
       BLOK E: Organizace a tým
       ═══════════════════════════════════════════════════════════ #}
    <section id="tym" aria-labelledby="tym-heading">
    <h2 id="tym-heading">E — Organizace a tým</h2>

    <p>
        DDD selže ne proto, že by byl technicky špatný — ale proto, že tým ho nepochopil,
        management ho nepodpořil nebo znalosti zůstaly u jednoho člověka.
    </p>

    {# ─── E1: Business case ──────────────────────────────────────── #}
    <section id="e1-management" aria-labelledby="e1-heading">
    <h3 id="e1-heading">E1. Business case pro DDD refaktoring</h3>

    <p>
        <strong>Problém:</strong> Management vidí náklady refaktoringu (čas, riziko),
        ale ne benefity. „Přepsat to do DDD" zní jako technická čistota bez business hodnoty.
        Vývojáři neumí výhody přeložit do jazyka, který rozhodující osoby slyší.
    </p>

    <p>
        <strong>Jak argumentovat — měřitelné metriky:</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>Metrika</th>
                <th>Jak měřit</th>
                <th>Co říká managementu</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Time-to-feature</strong></td>
                <td>Průměrná doba od zadání po produkci (JIRA, Linear)</td>
                <td>Refaktoring → kratší cyklus = rychlejší obchodní reakce</td>
            </tr>
            <tr>
                <td><strong>Bug rate per modul</strong></td>
                <td>Počet bugů na 1000 řádků kódu (SonarQube)</td>
                <td>Moduly po DDD refaktoringu mají nižší bug rate</td>
            </tr>
            <tr>
                <td><strong>Onboarding time</strong></td>
                <td>Čas, než nový vývojář dělá první commit do modulu</td>
                <td>Explicitní doménový model = kratší onboarding</td>
            </tr>
            <tr>
                <td><strong>Regression rate</strong></td>
                <td>% ticketů označených jako regression</td>
                <td>Dobře ohraničené agregáty = méně neúmyslných side effectů</td>
            </tr>
        </tbody>
    </table>

    <p>
        <strong>Taktika:</strong> Nezačínejte argumentem „naše kód je špatný."
        Začněte konkrétní business bolestí: „Přidání nového způsobu platby trvá 3 týdny
        a vždy způsobí regression v objednávkovém modulu. Tady je proč a jak to opravit."
    </p>

    </section>

    {# ─── E2: Strangler fig ──────────────────────────────────────── #}
    <section id="e2-strangler" aria-labelledby="e2-heading">
    <h3 id="e2-heading">E2. Postupné zavedení — strangler fig pattern</h3>

    <p>
        <strong>Problém:</strong> Big-bang rewrite — přepsání celé aplikace do DDD najednou —
        téměř vždy selže: trvá déle než odhadnuto, tým ztrácí motivaci, business se nedočká
        nových funkcí. A přitom původní aplikace musí dál žít.
    </p>

    <p>
        <strong>Řešení — strangler fig pattern:</strong> Identifikujte jeden modul
        s nejvyšší změnovou frekvencí (highest-churn), nejčastějšími bugy nebo největší
        business hodnotou. Implementujte právě ten modul v DDD. Zbytek aplikace zůstane
        beze změny.
    </p>

    <p>
        <strong>Postup v Symfony projektu:</strong>
    </p>

    <ol>
        <li>
            <strong>Identifikujte modul:</strong> <code>git log --stat | grep "files changed" | sort -rn | head -20</code>
            — soubory s nejvíce změnami za posledních 6 měsíců jsou nejlepší kandidáti.
        </li>
        <li>
            <strong>Vytvořte fasádu</strong> přes legacy kód: nový DDD kód volá legacy
            přes interface (ACL vzor). Legacy kód o novém DDD ví co nejméně.
        </li>
        <li>
            <strong>Feature flag:</strong> Pro každý nový modul zapněte DDD implementaci
            pomocí feature flagu. Při problémech okamžitě rollback na legacy.
        </li>
        <li>
            <strong>Opakujte</strong> pro další modul, dokud legacy nevyschne.
        </li>
    </ol>

    <div class="note" role="note">
        <p>
            Strangler fig neznamená, že legacy kód a DDD kód sdílejí databázové tabulky.
            Nový modul má vlastní tabulky; data z legacy se migrují postupně,
            případně se synchronizují přes events nebo cron job.
        </p>
    </div>

    </section>

    {# ─── E3: Knowledge silos ────────────────────────────────────── #}
    <section id="e3-silos" aria-labelledby="e3-heading">
    <h3 id="e3-heading">E3. Knowledge silos a bus factor</h3>

    <p>
        <strong>Problém:</strong> Doménový model je komplexní — a po roce vývoje
        mu rozumí dobře jen jeden člověk. Pokud tento člověk onemocní, odejde nebo
        je přetížen, tým stojí. Onboarding nového vývojáře trvá měsíce.
        Bus factor = 1 je pro business kritické riziko.
    </p>

    <p>
        <strong>Opatření — čtyři praktiky:</strong>
    </p>

    <ol>
        <li>
            <strong>Living documentation přes testy:</strong> Pojmenování testů ve stylu
            <code>it_cannot_ship_order_that_is_not_paid()</code> tvoří čitelný katalog
            doménových pravidel. Kdo čte testy, pochopí doménový model bez vývojáře.
        </li>
        <li>
            <strong>Architecture Decision Records (ADR):</strong> Každé netriviální
            rozhodnutí (proč Saga místo 2PC, proč Value Object místo entity, proč
            tento Bounded Context takto ohraničený) zapište do <code>docs/adr/</code>.
            Budoucí vývojář pochopí kontext bez „senior kolegy".
        </li>
        <li>
            <strong>Event Storming jako týmová aktivita:</strong> Modelování domény
            musí probíhat v celém týmu, ne v hlavě jednoho architekta. Pravidelné
            (čtvrtletní) Event Storming sessions sdílejí znalosti a odhalují nekonzistence.
        </li>
        <li>
            <strong>Doménový glosář v repozitáři:</strong> Živý dokument,
            kde každý vývojář může hledat, co <code>FulfillmentContext</code> znamená,
            jaké jsou jeho agregáty a na jaké Bounded Contexts navazuje.
        </li>
    </ol>

    </section>

    </section>{# konec bloku E #}
```

- [ ] **Krok 2: Přidat závěr a cvičení**

Za `</section>{# konec bloku E #}` přidej:

```twig
    {# ═══════════════════════════════════════════════════════════
       Shrnutí
       ═══════════════════════════════════════════════════════════ #}
    <section id="shrnuti" aria-labelledby="shrnuti-heading">
    <h2 id="shrnuti-heading">Co jsme se naučili</h2>

    <ul>
        <li>
            <strong>Doctrine</strong> — Unit of Work je session-scoped; Application Service
            je správná transakční hranice. Složité Value Objects mapujte přes Custom Type.
            UUID generujte v doméně, nikoli v databázi.
        </li>
        <li>
            <strong>Asynchronní infrastruktura</strong> — Outbox pattern garantuje
            at-least-once doručení událostí. Každý handler musí být idempotentní.
            Correlation ID je nezbytné pro debugging distribuovaných systémů.
        </li>
        <li>
            <strong>Modelování</strong> — Doménový invariant patří do konstruktoru
            nebo metody agregátu, nikdy jen do Symfony Validatoru. Anti-Corruption Layer
            chrání doménu před změnami externích API.
        </li>
        <li>
            <strong>Symfony</strong> — Form mapuje na DTO, Application Service sestaví Command.
            API Platform pracuje s API Resource DTO, nikoli s agregáty. Security Voter
            je tenký adaptér nad doménovou metodou.
        </li>
        <li>
            <strong>Tým a organizace</strong> — Strangler fig je bezpečnější než big-bang
            rewrite. Business case argumentuje měřitelnými metrikami, ne technickou čistotou.
            Bus factor snižují živá dokumentace, ADR a Event Storming.
        </li>
    </ul>

    </section>

    {# ═══════════════════════════════════════════════════════════
       Cvičení
       ═══════════════════════════════════════════════════════════ #}
    <section id="cviceni" aria-labelledby="cviceni-heading">
    <h2 id="cviceni-heading">Zkuste sami</h2>

    <div class="tip" role="note">
        <ol>
            <li>
                Implementujte <code>MoneyType</code> pro Doctrine a použijte ho na agregátu
                <code>Order</code> s polem <code>totalPrice</code>. Ověřte, že po
                <code>flush()</code> a novém načtení vrací <code>Money</code> objekt, ne string.
            </li>
            <li>
                Přidejte <code>IdempotencyMiddleware</code> do command busu.
                Napište integrační test, který odešle stejný command dvakrát se stejným
                <code>IdempotencyStamp::key</code> a ověří, že handler byl zavolán pouze jednou.
            </li>
            <li>
                Navrhněte <code>PaymentGateway</code> interface pro váš aktuální projekt.
                Implementujte <code>FakePaymentGateway</code> pro testy a
                <code>StripePaymentGateway</code> pro produkci. Zaregistrujte je přes
                Symfony DI a přepínejte pomocí environment variable.
            </li>
            <li>
                Identifikujte v existujícím projektu modul s nejvyšší změnovou frekvencí
                (<code>git log --stat</code>). Navrhněte, jak by vypadal strangler fig
                přechod tohoto modulu na DDD: jaká fasáda by byla potřeba,
                jak by se synchronizovala data?
            </li>
            <li>
                Přepište existující Security Voter tak, aby business logika žila
                v doménové metodě agregátu. Napište unit test doménové metody
                bez Symfony Security kontextu.
            </li>
        </ol>
    </div>

    <p>
        V další kapitole se podíváme na
        <a href="{{ path('practical_examples') }}">praktické příklady implementace DDD v Symfony</a>,
        kde uvidíte vzory z celé příručky zasazené do reálnějšího kontextu.
    </p>

    </section>

    </article>
{% endblock %}

{% block toc %}<p class="toc-title">Na této stránce</p>{% endblock %}
```

- [ ] **Krok 3: Ověřit kompletní render**

```bash
curl -s http://127.0.0.1:8000/ddd-v-praxi-kde-to-boli | grep -c "<h3"
```

Očekáváno: ≥ 20 (20 sekcí)

- [ ] **Krok 4: Commit**

```bash
git add templates/ddd/ddd_pain_points.html.twig
git commit -m "feat: dokončit template — blok E, závěr, cvičení"
```

---

### Task 8: Aktualizace JS navigace

**Files:**
- Modify: `public/js/modern-script.js`

- [ ] **Krok 1: Přidat kapitolu do CHAPTERS array**

V souboru `public/js/modern-script.js` najdi CHAPTERS array (řádek ~104).
Za řádek `{ label: 'Ságy a Process Managery', url: '/sagy-a-process-managery' },` vlož:

```javascript
        { label: 'DDD v praxi — kde to bolí', url: '/ddd-v-praxi-kde-to-boli' },
```

Výsledný blok vypadá takto (zkráceno pro kontext):

```javascript
    const CHAPTERS = [
        { label: 'Úvod', url: '/' },
        { label: 'Co je DDD', url: '/co-je-ddd' },
        { label: 'Základní koncepty', url: '/zakladni-koncepty' },
        { label: 'Vertikální slice', url: '/horizontalni-vs-vertikalni' },
        { label: 'Implementace v Symfony', url: '/implementace-v-symfony' },
        { label: 'CQRS', url: '/cqrs' },
        { label: 'Event Sourcing', url: '/event-sourcing' },
        { label: 'Ságy a Process Managery', url: '/sagy-a-process-managery' },
        { label: 'DDD v praxi — kde to bolí', url: '/ddd-v-praxi-kde-to-boli' },
        { label: 'Příklady', url: '/prakticke-priklady' },
        // ... zbytek beze změny
    ];
```

- [ ] **Krok 2: Ověřit navigaci v prohlížeči**

Otevřete `http://127.0.0.1:8000/sagy-a-process-managery` — v dolní části stránky
musí být tlačítko „Další →" s nápisem „DDD v praxi — kde to bolí".

Otevřete `http://127.0.0.1:8000/ddd-v-praxi-kde-to-boli` — musí být
„← Předchozí: Ságy a Process Managery" a „Další →: Příklady".

- [ ] **Krok 3: Commit**

```bash
git add public/js/modern-script.js
git commit -m "feat: přidat ddd_pain_points do JS navigace CHAPTERS"
```

---

### Task 9: Aktualizace Ság — odkaz na další kapitolu

**Files:**
- Modify: `templates/ddd/sagas.html.twig`

- [ ] **Krok 1: Najít a přepsat odkaz na následující kapitolu**

V souboru `templates/ddd/sagas.html.twig` najdi v sekci `#cviceni` odstavec:

```twig
    <p>
        V další kapitole se podíváme na
        <a href="{{ path('practical_examples') }}">praktické příklady implementace DDD v Symfony</a>,
        kde uvidíte vzory z tohoto článku zasazené do reálnějšího kontextu.
    </p>
```

Nahraď ho:

```twig
    <p>
        V další kapitole se podíváme na
        <a href="{{ path('ddd_pain_points') }}">reálná bolestivá místa DDD v PHP a Symfony</a>
        — catalog 20 problémů, na které narazíte při implementaci, s doporučenými řešeními.
    </p>
```

- [ ] **Krok 2: Ověřit**

```bash
curl -s http://127.0.0.1:8000/sagy-a-process-managery | grep "ddd-v-praxi"
```

Očekáváno: řádek obsahující `/ddd-v-praxi-kde-to-boli`

- [ ] **Krok 3: Commit**

```bash
git add templates/ddd/sagas.html.twig
git commit -m "fix: aktualizovat odkaz na další kapitolu v Ságách"
```

---

### Task 10: Aktualizace index stránky

**Files:**
- Modify: `templates/ddd/index.html.twig`

- [ ] **Krok 1: Přidat feature card pro novou kapitolu**

V souboru `templates/ddd/index.html.twig` najdi div s třídou `feature-grid`.
Za feature card pro Ságy (nebo za Event Sourcing card — vlož na logické místo) přidej:

```twig
        <a href="{{ path('ddd_pain_points') }}" class="feature-card">
            <h3 class="feature-card-title">DDD v praxi — kde to bolí</h3>
            <p class="feature-card-desc">20 reálných problémů: Doctrine, Messenger, validace, ACL, strangler fig a přesvědčení managementu.</p>
        </a>
```

- [ ] **Krok 2: Aktualizovat počet kapitol**

V `templates/ddd/index.html.twig` najdi trust-bar a hero-stats:

```twig
<span>15 kapitol s ukázkami kódu</span>
```

Změň na:

```twig
<span>16 kapitol s ukázkami kódu</span>
```

A v hero-stats:

```twig
<span class="hero-stats-num">15</span>
```

Změň na:

```twig
<span class="hero-stats-num">16</span>
```

- [ ] **Krok 3: Ověřit**

```bash
curl -s http://127.0.0.1:8000/ | grep "16 kapitol"
curl -s http://127.0.0.1:8000/ | grep "ddd-v-praxi"
```

Obě musí vrátit neprázdný výstup.

- [ ] **Krok 4: Commit**

```bash
git add templates/ddd/index.html.twig
git commit -m "feat: přidat feature card pro ddd_pain_points, aktualizovat počet kapitol na 16"
```

---

## Kontrolní seznam po implementaci

- [ ] `GET /ddd-v-praxi-kde-to-boli` vrací HTTP 200
- [ ] Stránka obsahuje všech 20 sekcí (6+4+4+3+3)
- [ ] Navigace Předchozí/Další funguje na nové stránce
- [ ] Navigace Předchozí/Další funguje na stránce Ság (ukazuje na novou kapitolu)
- [ ] Index stránka zobrazuje feature card a číslo 16
- [ ] JSON-LD structured data je přítomna (zkontroluj zdrojový kód)
- [ ] TOC odkazuje na všechny sekce (`href="#a1-transakce"` atd.)
