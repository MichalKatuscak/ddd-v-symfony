---
route: context_mapping
path: /context-mapping
title: Bounded Context a Context Mapping
page_title: "Context Mapping a 8 vztahů Bounded Contextů | DDD Symfony"
meta_description: "Context Map a 8 vztahů mezi Bounded Contexts: Partnership, Customer/Supplier, Conformist, ACL, Shared Kernel, Separate Ways a další, s ukázkami v Symfony."
meta_keywords: "Context Map, Context Mapping, Bounded Context, Anti-Corruption Layer, ACL, Open Host Service, Published Language, Shared Kernel, Customer Supplier, Conformist, Partnership, Separate Ways, Symfony Messenger"
og_type: article
published: "2026-04-29"
modified: "2026-06-13"
breadcrumb_name: Context Mapping
schema_type: TechArticle
schema_headline: "Bounded Context a Context Mapping – 8 vztahů mezi Bounded Contexts"
chapter_number: "03"
category: Základy
deck: "Bounded Context vám definuje hranici. Context Mapping vám definuje, co se na té hranici děje. Osm pojmenovaných vztahů, které popisují všechny způsoby, jak spolu BC komunikují – od těsné spolupráce po úmyslnou separaci."
reading_time: 28
difficulty: 3
github_examples: null
---

Strategický design v DDD má dvě stránky. **Bounded Context** definuje *hranici* jednoho modelu – co je uvnitř, co je venku, kde končí jeden Ubiquitous Language a začíná druhý. Definici rozvádí [kapitola Co je DDD](/co-je-ddd#bounded-context), taktické dopady [Základní koncepty](/zakladni-koncepty#bounded-contexts). Sám o sobě však neřeší jednu podstatnou otázku: **co se děje na té hranici**, když dva kontexty potřebují spolupracovat.

**Context Mapping** je odpověď. Evans ji popsal v roce 2003 v knize *Domain-Driven Design: Tackling Complexity in the Heart of Software*, kapitola 14 (*Maintaining Model Integrity*). Šlo o vizuální i textovou dokumentaci všech Bounded Contexts v systému a všech vztahů mezi nimi [[1]](https://www.domainlanguage.com/ddd/). Vaughn Vernon v *Implementing Domain-Driven Design* (2013) tuto disciplínu rozvedl o praktické implementační vzory [[2]](https://kalele.io/books/). V praxi Context Map typicky vzniká jako výstup [Event Stormingu](/event-storming) – zde napřed projdeme teorii vztahů, samotnou workshopovou techniku popisuje následující kapitola. Sekce pokrývají všech osm pojmenovaných vztahů mezi BC a jejich kompromisy. Ukázky realizace v Symfony 8 využívají [CQRS s Symfony Messenger](/cqrs), REST API nebo HTTP klienty.

## 03.01 Co je Context Map a proč ji nakreslit {#co-je-context-map}

Eric Evans Context Map vymezuje jako přehled všech modelů ve hře. Každý model na projektu dostane jméno a ohraničený kontext. U každého bodu dotyku mezi modely se popíše explicitní překlad i všechno, co se sdílí [[1]](https://www.domainlanguage.com/ddd/). Context Map není UML diagram tříd. Je to **organizační a politická mapa**, která zachycuje, kdo s kým mluví, jakým jazykem a kdo rozhoduje, když se jazyk musí změnit.

Context Map má dvě složky:

- **Vizuální složka** – diagram s krabičkami (Bounded Contexts) a šipkami (vztahy) opatřenými stereotypy (`<<ACL>>`, `<<OHS>>`, `U/D` pro upstream/downstream).
- **Textová složka** – krátký dokument popisující každý vztah: odpovědné týmy, kontrakt, frekvenci změn, eskalační kontakt. Tato část je důležitější než obrázek; obrázek zastarává rychleji, než se stačí aktualizovat.

Proč tu mapu nakreslit? Protože alternativou je **implicitní vztahový graf**. Tým A ví, že volá tým B – ale nikdo neřekl, jakým způsobem, kdo kontrakt vlastní a co se stane, když ho někdo jednostranně změní. Implicitní vztahy vedou k integračním bugům, plíživému sdílení modelů a nakonec k *Big Ball of Mud* (viz [03.12](#big-ball-of-mud)).

:::diagram{fig="03.1-A" title="Context Map: 5 Bounded Contexts a všech 8 typů vztahů" src="images/diagrams/12_context_mapping/context_map_patterns.svg"}
:::

:::callout{type="note"}
### Kdy Context Map nakreslit

- **Při zahájení projektu** jako součást discovery fáze – typicky výstup [Event Stormingu](/event-storming).
- **Před přidáním nového Bounded Contextu** – abychom věděli, s jakým existujícím vztahem se nový BC zapojí.
- **Před migrací nebo náhradou legacy systému** – Context Map ukáže, kolik downstream BC bude potřebovat ACL upgrade.
- **Při onboardingu nových inženýrů** – mapa řekne víc o architektuře za 10 minut než README za hodinu.
:::

## 03.02 Osm typů vztahů – přehled {#osm-typu-prehled}

Kapitola pracuje s **osmi pojmenovanými vztahy**, kterými mohou Bounded Contexts spolu vyjít. Sedm z nich popsal Eric Evans v *Domain-Driven Design* (2003); Partnership doplnil později v *Domain-Driven Design Reference* (volně dostupná edice 2015). Vaughn Vernon v IDDD (2013) katalog rozšířil o nuance a kombinace, jádro pojmenování ale zůstalo. Tato osmičlenná taxonomie je užitečná ze dvou důvodů. **(1)** Dává nám sdílený slovník („zde je to Customer/Supplier, ne Conformist“). **(2)** Zviditelňuje cenu vztahu – některé jsou dražší než jiné a volba mezi nimi je strategická.

*Pozn.:* Evans v *Domain-Driven Design Reference* (2015) uvádí vedle osmi vztahů ještě devátý vzor – **Big Ball of Mud**, převzatý od Foota a Yodera. Probíráme jej samostatně v sekci [03.12 Anti-vzor: Big Ball of Mud](#big-ball-of-mud). Nejde o cílový vztah, který by si někdo vědomě volil, ale o stav rozpadu, kterému se aktivně bráníme. Osm vztahů níže tedy představuje volby, které lze vědomě navrhnout; Big Ball of Mud je to, co se stane, když žádnou volbu neuděláte.

| Vztah | Symetrický? | Coupling | Použití | Kdo o něm rozhoduje |
|---|---|---|---|---|
| [**Partnership**](#partnership) | Symetrický | Vysoký | Společné doménové cíle, společný release | Oba týmy |
| [**Shared Kernel**](#shared-kernel) | Symetrický | Vysoký | Sdílený codebase modul (VO, eventy) | Oba týmy souhlasem |
| [**Customer/Supplier**](#customer-supplier) | Asymetrický | Střední | Upstream poskytuje, downstream konzumuje | Upstream rozhoduje, downstream prioritizuje |
| [**Conformist**](#conformist) | Asymetrický | Střední | Downstream přijímá upstream model 1:1 | Vynucené (downstream nemá vliv) |
| [**Anti-Corruption Layer**](#acl) | Asymetrický | Nízký | Downstream chrání svůj model před upstreamem | Downstream rozhoduje |
| [**Open Host Service**](#ohs) | Asymetrický | Nízký | Upstream stabilizuje protokol pro mnoho konzumentů | Upstream rozhoduje |
| [**Published Language**](#published-language) | Asymetrický | Nízký | Stabilizovaný formát zpráv (schema) | Upstream + standardy |
| [**Separate Ways**](#separate-ways) | – | Žádný | Žádná integrace, vědomá duplicita | Strategické rozhodnutí |

Tabulka ukazuje, že vztahy nejsou nezávislé varianty – některé se kombinují. Customer/Supplier typicky *používá* Open Host Service jako kanál a Published Language jako formát zpráv. Anti-Corruption Layer je technika, kterou downstream *aplikuje*, když je v Customer/Supplier nebo Conformist pozici vůči nevstřícnému (legacy) modelu.

:::callout{type="pattern"}
### Rychlé rozhodovací pravidlo

- **Když downstream *nemá kontrolu*, ale chce *chránit* svůj model** → [ACL](#acl).
- **Když downstream *má hlas*** (může požádat o změnu kontraktu) → [Customer/Supplier](#customer-supplier).
- **Když downstreamu *vůbec nezáleží*** a integrace musí být co nejlevnější → [Conformist](#conformist).
- **Když je upstream zveřejněn pro *mnoho* konzumentů** → [OHS](#ohs) + [PL](#published-language).
- **Když dva týmy *nemůžou žít odděleně*** a sdílí doménový cíl → [Partnership](#partnership).
- **Když by integrace stála *víc než vědomá duplicita*** → [Separate Ways](#separate-ways).
:::

## 03.03 Partnership {#partnership}

**Partnership** je symetrický vztah mezi dvěma Bounded Contexts, jejichž týmy *společně uspějí, nebo společně padnou*. Sdílí doménový cíl, koordinují plánování a typicky se nasazují společným release procesem. Není to „náhodná spolupráce“ – Partnership je **vědomé strategické rozhodnutí**. Integrační náklady (synchronní porady, společný roadmap, časté merge konflikty) jsou nižší než cena, kterou by oba týmy zaplatily, kdyby pracovaly nezávisle.

Eric Evans vzor zachytil v *Domain-Driven Design Reference* (2015): pokud by selhání vývoje v kterémkoli ze dvou kontextů znamenalo selhání dodávky pro oba, mají odpovědné týmy navázat partnerství. Součástí je koordinované plánování vývoje a společné řízení integrace.

### Příklad: Catalog BC + Pricing BC v early-stage startupu

Představme si fiktivní e-shop, kde tým *Catalog* vlastní produktové informace (název, popis, obrázky, kategorie) a tým *Pricing* vlastní cenotvorbu (základní cena, slevy, A/B test ceny pro různé segmenty). V early-stage fázi platí dvě věci najednou:

- Catalog bez Pricingu je k ničemu – produktovou stránku nelze zobrazit bez ceny.
- Pricing bez Catalogu je k ničemu – cena bez produktu nedává smysl.

V této fázi se týmy **záměrně** rozhodnou pro Partnership: jeden produktový manažer pokrývá oba BC, retrospektivy se konají společně, release proces je jednotný (deploy obou BC současně). Toto uspořádání funguje, dokud se domény neusadí natolik, aby mohly žít vlastním tempem.

### Symfony detail: monorepo a společný release

Adresářové uspořádání podle subdomén jsme rozebrali dříve – viz [struktura podle subdomén](/subdomeny#symfony-implications). Pro Partnership je podstatné, co oba BC v monorepu sdílí navíc: jeden `composer.json`, společnou DI registraci v `config/services.yaml` a jednu message bus konfiguraci v `config/messenger.yaml`.

Oba BC si přitom drží **vlastní namespacy** (`App\Catalog`, `App\Pricing`) i **vlastní invarianty**; infrastruktura (DI kontejner, RabbitMQ, databázový server) je společná. Komunikace mezi nimi je in-process přes Symfony Messenger sync transport – žádné serializované JSON přes drát.

### Anti-vzor: „Partnership jako výchozí volba“

Když se týmy rozhodnou pro Partnership **aniž by si tu otázku položily**, vede to přímo k *Big Ball of Mud*. Typický důvod: „nemáme čas se domluvit, takže to budeme dělat dohromady“. Agregáty jednoho BC začnou číst tabulky druhého „protože je to rychlejší“. Doménové eventy ustoupí sdíleným service třídám. Po roce nikdo nedokáže říct, kde přesně končí Catalog a začíná Pricing.

:::callout{type="warn"}
**Partnership znamená nákladnou spolupráci.** Použít jen tehdy, když oba týmy explicitně podepíší „padáme nebo letíme spolu“. Pokud váháte – pravděpodobně chcete [Customer/Supplier](#customer-supplier) nebo [Shared Kernel](#shared-kernel). Partnership má tendenci se rozpadnout, jakmile týmy začnou mít odlišné priority, a v tu chvíli vás čeká bolestivá reorganizace.
:::

### Indikátory, že Partnership přestává fungovat

- Týmy začínají odkládat vlastní featury, protože čekají na druhý tým – celková rychlost klesá.
- Retrospektivy se opakovaně točí kolem stejných „mezi-týmových“ napětí.
- Release proces se prodlužuje, protože koordinace dvou roadmap je příliš nákladná.
- Jeden tým získá silně odlišnou prioritu (např. Catalog SEO sprint, zatímco Pricing dělá compliance) – společné nasazení přestává mít smysl.

V tu chvíli je čas *Partnership rozpustit* a přejít na [Customer/Supplier](#customer-supplier) nebo [Open Host Service](#ohs) mezi oběma BC.

## 03.04 Shared Kernel {#shared-kernel}

**Shared Kernel** je *malý* modul kódu fyzicky sdílený mezi dvěma a více Bounded Contexts. Sdílení je oboustranně závazné: žádný vlastník nemůže Shared Kernel jednostranně změnit, protože to by porušilo invarianty v ostatních BC. Změna SK vyžaduje **souhlas všech vlastníků**, což je nákladný proces a důvod, proč musí SK zůstat malý.

Evans v *Domain-Driven Design* (2003) doporučuje explicitní hranicí vyznačit podmnožinu doménového modelu, na jejímž sdílení se týmy dohodly. Kernel má být menší než přirozený průnik obou modelů a zahrnuje i příslušný kód či návrh databáze. Takto sdílený materiál má zvláštní status: nemění se bez konzultace s druhým týmem.

### Kdy Shared Kernel zvolit

- Existuje koncept, který má v obou BC **identický význam** (typicky elementární VO: `Money`, `Currency`, `EmailAddress`, `UserId`).
- Konceptů je málo (řekněme < 10 tříd) a jsou stabilní (mění se jednou ročně, ne týdně).
- Týmy mají dobrou komunikaci a souhlasí, že koordinaci budou dělat.

### Ukázka kódu: SharedKernel\Money modul

:::code{language="php" filename="shared-kernel/src/Money/Money.php"}
<?php

declare(strict_types=1);

// shared-kernel/src/Money/Money.php
namespace App\SharedKernel\Money;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        public int $amountCents,
        public Currency $currency,
    ) {
        if ($amountCents < 0) {
            throw new InvalidArgumentException(
                'Money cannot be negative; use SignedMoney for credits/debits.'
            );
        }
    }

    public function add(self $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException(
                "Cannot add {$this->currency->code} and {$other->currency->code}."
            );
        }
        return new self($this->amountCents + $other->amountCents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amountCents * $factor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents
            && $this->currency->equals($other->currency);
    }
}
:::

`Money` je VO bez identity – vhodný kandidát na SK. Nemá závislosti na žádné infrastruktuře, je `readonly`, neměnný, plně testovatelný. Catalog ho používá pro vyjádření základní ceny, Pricing pro vyjádření slevy, Ordering pro celkovou částku objednávky – a **ve všech BC znamená přesně totéž**.

### Symfony detail: composer path repository

:::code{language="json" filename="composer.json"}
{
    "name": "ddd/eshop",
    "type": "project",
    "repositories": [
        {
            "type": "path",
            "url": "shared-kernel/",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "ddd/shared-kernel": "*"
    }
}
:::

Shared Kernel se v Symfony monorepu typicky drží jako lokální composer balíček v adresáři `shared-kernel/`. Verzování probíhá přes git tagy (`v1.0.0`, `v1.1.0`) a změny SK procházejí **společným code review** obou (či více) týmů. Pull request do SK by měl mít CODEOWNERS pravidlo, které automaticky přiřadí review obou týmů.

### Anti-vzor: „rozjetý“ Shared Kernel

Nejčastější selhání Shared Kernelu je **jeho růst**. Tým si řekne: „máme tu `Money`, přidáme `Address` – vždyť adresa je taky všude stejná“. Pak `PhoneNumber`, pak `Customer`, pak `Order`… a najednou má SK 200 tříd a každá změna trvá týdny, protože vyžaduje souhlas tří týmů. V tu chvíli SK přestal být kernel a stal se *Big Ball of Shared Mud*.

Pravidlo: **SK musí být malý, stabilní a recenzovaný oběma týmy**. Pokud roste, znamená to, že koncepty, které do něj přidáváme, jsou v jednotlivých BC ve skutečnosti *odlišné*. Jen vypadají podobně a měly by se modelovat samostatně. Pokud koncept do SK skutečně patří, ale je velký, patří do samostatného BC s [Open Host Service](#ohs).

:::callout{type="note"}
**Shared Kernel vs. sdílená utility knihovna.** Sdílená logger knihovna nebo HTTP klient *nejsou* Shared Kernel – jsou to běžné technické závislosti. Shared Kernel obsahuje výhradně **doménový model**: VO, doménové eventy, doménové výjimky. Pokud váš „SK“ obsahuje `HttpClient`, `Cache` nebo `EventDispatcher`, není to Shared Kernel.
:::

## 03.05 Customer / Supplier {#customer-supplier}

**Customer/Supplier** je asymetrický vztah, ve kterém upstream (*supplier*) poskytuje data nebo službu a downstream (*customer*) je konzumuje. Hlavní rozdíl proti Conformist (viz dále): downstream **má hlas**. Může od supplieru explicitně požadovat featury, supplier je do svého backlogu přijme a dohodne se na termínu. *Supplier ale rozhoduje, kdy a jak feature dodá.*

Evans (2003) varuje na obě strany. Právo veta downstream týmu nebo těžkopádné procedury žádostí o změny ochromí volný vývoj upstreamu; downstream bez vlivu je zase vydán na milost prioritám upstreamu. Řešením je jasný vztah zákazník–dodavatel: downstream hraje v plánovacích schůzkách roli zákazníka upstreamu, požadavky se vyjednávají a rozpočtují, takže obě strany rozumí závazkům i termínům.

### Příklad: Catalog (supplier) → Ordering (customer)

Ordering BC potřebuje znát produktové ID a aktuální cenu, aby mohl sestavit objednávku. Catalog je vlastníkem produktových dat. Vztah je čistě jednosměrný: Ordering čerpá z Catalogu, Catalog bere Ordering jako „prvotřídního zákazníka“, ale neztrácí svobodu rozhodovat o vlastním modelu.

Když Ordering tým řekne „potřebujeme v product DTO i `availableStock`“, Catalog tým to **neudělá okamžitě**. Posoudí, zda to dává smysl pro Catalog model (ano – *Stock* patří do Catalogu), naplánuje to do následujícího sprintu a dodá. Pokud by to nedávalo smysl pro Catalog model, navrhl by alternativu (např. samostatný *Inventory BC* s vlastním API).

### Ukázka kódu: Symfony Messenger external transport

Customer/Supplier vztah se v Symfony 8 typicky implementuje přes **asynchronní eventy**. Catalog publikuje `ProductPriceChanged` do AMQP exchange, Ordering ho konzumuje přes external Messenger transport.

:::code{language="yaml" filename="config/packages/messenger.yaml (Ordering BC)"}
# config/packages/messenger.yaml – downstream Ordering BC
framework:
    messenger:
        transports:
            from_catalog:
                dsn: '%env(CATALOG_AMQP_DSN)%'
                options:
                    exchange:
                        name: 'catalog.events'
                        type: 'topic'
                    queues:
                        ordering.from_catalog:
                            binding_keys: ['product.price_changed', 'product.discontinued']
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
        routing:
            'App\Ordering\Application\ExternalEvent\ProductPriceChanged': from_catalog
            'App\Ordering\Application\ExternalEvent\ProductDiscontinued': from_catalog
:::

:::callout{type="note"}
**Sekce `routing` konfiguruje odesílání, ne příjem.** Říká, na který transport Messenger zprávu pošle při dispatchi – konzumaci cizích zpráv neřídí. Aby worker dokázal event z fronty `ordering.from_catalog` přečíst, potřebuje transport vlastní serializer (volba `serializer` v konfiguraci transportu), který JSON payload od Catalogu namapuje na lokální třídu `ProductPriceChanged`. Výchozí serializer Messengeru totiž očekává zprávy, které odeslal sám. Implementaci konzumní strany včetně serializeru a deduplikace rozebírá kapitola [Outbox Pattern](/outbox-pattern).
:::

A handler v Orderingu:

:::code{language="php" filename="src/Ordering/Application/EventHandler/ProductPriceChangedHandler.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Application\EventHandler;

use App\Ordering\Application\ExternalEvent\ProductPriceChanged;
use App\Ordering\Domain\PriceCache;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProductPriceChangedHandler
{
    public function __construct(
        private readonly PriceCache $priceCache,
    ) {}

    public function __invoke(ProductPriceChanged $event): void
    {
        // Ordering si drží lokální projekci ceny – eventually consistent.
        $this->priceCache->update(
            productId: $event->productId,
            newPrice: $event->newPriceCents,
            currency: $event->currency,
            effectiveAt: $event->occurredAt,
        );
    }
}
:::

### Stabilní kontrakt jako základ

Customer/Supplier vyžaduje **stabilní kontrakt**. Bez něj je každá změna upstream modelu breaking change pro všechny downstream konzumenty. V praxi se Customer/Supplier *kombinuje* s [Open Host Service](#ohs) jako kanálem a [Published Language](#published-language) jako formátem zpráv. Detail viz sekci 03.08 a 03.09.

### Plánovací rituály mezi týmy

Customer/Supplier funguje jen při existenci minimálních koordinačních rituálů:

- **Pravidelný cross-team grooming** (typicky 1× za sprint), kde downstream prezentuje své požadavky.
- **Dokumentovaný roadmap upstreamu** – downstream musí vidět, co se chystá a kdy očekávat breaking changes.
- **Eskalační kanál** – kdo rozhoduje, když se týmy neshodnou? Typicky produktový manažer nebo architekt.

Bez těchto rituálů sklouzne Customer/Supplier do [Conformistu](#conformist): downstream přestane mít hlas, jen se přizpůsobuje.

## 03.06 Conformist {#conformist}

**Conformist** je asymetrický vztah, ve kterém downstream *vědomě rezignuje* na vlastní model a přijímá upstream model 1:1. Žádný překlad, žádná validace, žádné mapování. Conformist znamená vědomou úsporu na hranici, kde *boj o vlastní model nestojí za to*.

Evans (2003) situaci popisuje bez příkras: když upstream nemá motivaci vycházet potřebám downstream týmu vstříc, je downstream bezmocný. Otrocké převzetí modelu upstream týmu odstraní složitost překladu mezi kontexty. A pokud je upstream design dost dobrý nebo kompatibilní, nemusí to způsobit větší potíže.

### Kdy Conformist zvolit

- **Externí dodavatel** – používáte SaaS (Stripe, Shopify, Auth0) a nemá smysl bojovat proti jejich datovým modelům.
- **Regulátor** – banka přijímá ISO 20022 zprávy. Boj proti formátu by byl boj proti standardu.
- **Reporting nebo dashboard BC**, který data jen přebírá a zobrazuje.
- **Krátkodobé řešení**, dokud nemá smysl investovat do ACL.

### Příklad: Reporting BC přijímá Stripe payment objekty 1:1

:::code{language="php" filename="src/Reporting/Application/StripePaymentReportRepository.php"}
<?php

declare(strict_types=1);

namespace App\Reporting\Application;

// Conformist: žádné vlastní VO, používáme přímo Stripe SDK objekty
use Stripe\PaymentIntent;
use Stripe\Charge;

final class StripePaymentReportRepository
{
    public function __construct(
        private readonly \Stripe\StripeClient $stripe,
    ) {}

    /**
     * @return PaymentIntent[]
     */
    public function getRecentPayments(\DateTimeImmutable $since): array
    {
        return $this->stripe->paymentIntents->all([
            'created' => ['gte' => $since->getTimestamp()],
            'limit'   => 100,
        ])->data;
    }

    public function generateMonthlyRevenue(int $year, int $month): array
    {
        $payments = $this->getRecentPayments(/* ... */);

        return array_map(
            // Reporting prostě používá Stripe pole jak jsou – currency, amount,
            // status. Žádný překlad na Money VO, žádná Czech terminologie.
            fn(PaymentIntent $p) => [
                'id'       => $p->id,
                'amount'   => $p->amount,        // Stripe používá centy
                'currency' => $p->currency,      // 'usd', 'eur' lower-case
                'status'   => $p->status,
            ],
            $payments,
        );
    }
}
:::

Reporting je vědomě Conformist vůči Stripe. Žádný převod na `Money` VO, žádný překlad `'usd'` → `Currency::USD`. Když Stripe přejmenuje pole nebo přidá nový status, Reporting musí změnu přijmout. Cena za to je **nulová investice do ACL**; cena, kterou platíme, je **křehkost vůči neslučitelným změnám upstreamu**.

### Kompromis Conformistu

Conformist *ušetří*:

- Kód překladu (DTO → VO mapování).
- Pochopení dvou modelů namísto jednoho.
- Údržbu testů ACL vrstvy.

Conformist *zaplatí*:

- Při každé neslučitelné změně upstreamu se downstream musí přepsat.
- Doménová logika downstreamu používá pojmy upstreamu, což zhoršuje srozumitelnost.
- Pokud se upstream rozhodne odejít (Stripe zavře službu), je downstream odkázán na vendora.
- Není možné sdílet model napříč více upstreamy (např. přidat alternativu PayPal vedle Stripe – celá doménová logika kopíruje Stripe).

:::callout{type="warn"}
**Conformist je krátkodobá úleva s dlouhodobou cenou.** Když upstream provede neslučitelnou změnu, rozbije se i downstream. Pokud má downstream *jakoukoliv* doménovou logiku, která je závislá na konzumovaných datech (a vy plánujete s tou logikou žít déle než upstream), **postavte ACL**. Conformist použijte jen tam, kde downstream je opravdu jen průchozí transformací (reporting, log forwarder, jednoduchý webhook handler).
:::

### Conformist jako přechodný stav

Často je Conformist přijatelný *dočasně* – projekt potřebuje rychle dodat MVP a integrace s upstreamem je třeba okamžitě. V tu chvíli je rozumné napsat Conformist, ale **vypsat technický dluh do backlogu**: „za 6 měsíců, až budeme vědět, jak Reporting používáme, postavíme ACL“. Bez explicitního zápisu do backlogu Conformist „uzraje“ na permanentní řešení a refaktor je pak dvojnásob bolestivý.

## 03.07 Anti-Corruption Layer (ACL) {#acl}

**Anti-Corruption Layer** (ACL) je izolační vrstva mezi downstream doménovým modelem a cizím (legacy, externím, neupřímným) protějškem. Překládá oběma směry, validuje vstupní data a *filtruje neplatné stavy* ještě předtím, než dorazí do domény. ACL je nejčastěji používaný vztah – a nejčastěji špatně implementovaný.

Evans (2003) rozlišuje dvě situace. Mezi dobře navrženými kontexty se spolupracujícími týmy může být překladová vrstva prostá. Když ale chybí kontrola nebo komunikace potřebná pro Shared Kernel, Partnership či Customer/Supplier, překlad nabývá obranného tónu. Doporučení zní: downstream klient si vytvoří izolační vrstvu, která mu funkčnost upstream systému zpřístupní v pojmech jeho vlastního doménového modelu.

:::diagram{fig="03.7-A" title="Anatomie Anti-Corruption Layeru: tři odpovědnosti translátoru" src="images/diagrams/12_context_mapping/acl_anatomy.svg"}
:::

### Tři odpovědnosti ACL

1. **Schema mapping** – překlad datových struktur. SOAP response, REST DTO, CSV řádek na doménová VO a entity. Zde se řeší „jak vypadá payload“.
2. **Concept translation** – překlad *významu* dat. Upstream používá `customerNumber` jako int, my používáme `CustomerId` jako UUID. Upstream má status `"PENDING"`, my máme enum `OrderState::AwaitingPayment`. Zde se řeší „co to znamená“.
3. **Anti-corruption** – validace a filtrace. Negativní částky, chybějící required pole, status mimo známý enum, datum v budoucnosti – všechno musí ACL odmítnout, *než* se to dostane do domény. Zde se řeší „je to důvěryhodné“.

### Ukázka kódu: kompletní LegacyBillingTranslator

:::code{language="php" filename="src/Ordering/Infrastructure/Acl/LegacyBillingTranslator.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Acl;

use App\Ordering\Domain\Event\InvoicePaidEvent;
use App\Ordering\Domain\ValueObject\InvoiceId;
use App\SharedKernel\Money\Currency;
use App\SharedKernel\Money\Money;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * ACL mezi legacy Billing systémem (SOAP) a Ordering BC.
 *
 * Tři odpovědnosti:
 *   1. Schema mapping  – InvoicePaidSoapResponse => InvoicePaidEvent
 *   2. Concept translation – invoiceNumber (int) => InvoiceId (UUID)
 *   3. Anti-corruption – odmítá neplatné stavy z legacy
 */
final class LegacyBillingTranslator
{
    public function __construct(
        private readonly LegacyBillingClient $soap,
    ) {}

    public function translateInvoicePaid(InvoicePaidSoapResponse $r): InvoicePaidEvent
    {
        // (3) Anti-corruption: legacy umí poslat negativní amount jako "credit"
        if ($r->amountCents < 0) {
            throw new UnrecoverableMessageHandlingException(
                'Negative amount from legacy; credits are not supported in Ordering BC.'
            );
        }

        // (3) Anti-corruption: chybějící identifikátor
        if ($r->invoiceNumber === '' || $r->invoiceNumber === null) {
            throw new UnrecoverableMessageHandlingException('Missing invoiceNumber in legacy payload.');
        }

        // (3) Anti-corruption: legacy umí poslat status, kterému nerozumíme
        if ($r->status !== 'PAID') {
            throw new UnrecoverableMessageHandlingException(
                "Unsupported legacy status '{$r->status}'; expected PAID."
            );
        }

        // (1) Schema mapping + (2) Concept translation
        return new InvoicePaidEvent(
            invoiceId: InvoiceId::fromLegacy($r->invoiceNumber),
            paidAt:    new \DateTimeImmutable($r->paidAtIso),
            amount:    new Money($r->amountCents, Currency::EUR),
        );
    }
}
:::

Translator je `final` třída s jedinou veřejnou metodou. Žádný stav, žádná cache, žádný vedlejší efekt. Vstup je upstream DTO, výstup je doménová událost. Toto je důvod, proč je ACL tak silný – a tak křehký, když ho implementujete jinak.

### Test ACL: snadný a důležitý

:::code{language="php" filename="tests/Ordering/Infrastructure/Acl/LegacyBillingTranslatorTest.php"}
<?php

declare(strict_types=1);

namespace App\Tests\Ordering\Infrastructure\Acl;

use App\Ordering\Infrastructure\Acl\InvoicePaidSoapResponse;
use App\Ordering\Infrastructure\Acl\LegacyBillingClient;
use App\Ordering\Infrastructure\Acl\LegacyBillingTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class LegacyBillingTranslatorTest extends TestCase
{
    public function testTranslatesPaidInvoice(): void
    {
        $soap = $this->createMock(LegacyBillingClient::class);
        $translator = new LegacyBillingTranslator($soap);

        $event = $translator->translateInvoicePaid(new InvoicePaidSoapResponse(
            invoiceNumber: 'INV-2025-00042',
            amountCents:   12_345,
            status:        'PAID',
            paidAtIso:     '2025-04-29T10:00:00Z',
        ));

        $this->assertSame(12_345, $event->amount->amountCents);
        $this->assertSame('EUR', $event->amount->currency->code);
    }

    public function testRejectsNegativeAmount(): void
    {
        $translator = new LegacyBillingTranslator($this->createMock(LegacyBillingClient::class));

        $this->expectException(UnrecoverableMessageHandlingException::class);
        $translator->translateInvoicePaid(new InvoicePaidSoapResponse(
            invoiceNumber: 'INV-1',
            amountCents:   -100,
            status:        'PAID',
            paidAtIso:     '2025-04-29T10:00:00Z',
        ));
    }

    public function testRejectsUnknownStatus(): void
    {
        $translator = new LegacyBillingTranslator($this->createMock(LegacyBillingClient::class));

        $this->expectException(UnrecoverableMessageHandlingException::class);
        $translator->translateInvoicePaid(new InvoicePaidSoapResponse(
            invoiceNumber: 'INV-2',
            amountCents:   100,
            status:        'CANCELLED', // legacy občas takhle pošle
            paidAtIso:     '2025-04-29T10:00:00Z',
        ));
    }
}
:::

Protože ACL nemá stav a má jediný entrypoint, testy mají tvar tabulky vstup → výstup a tabulky vstup → výjimka. Každý nový okrajový případ z produkce se promítá jako nový test.

### Anti-vzor: prosakující ACL

Nejčastější selhání ACL: **cizí pojmy začnou prosakovat do domény**. Symptomy:

- Doménová třída `Order` má pole `legacyInvoiceNumber: string`.
- Doménový event `OrderShipped` má v payloadu `stripeChargeId`.
- Application Service kontroluje `$soapResponse->status === 'PAID'`.
- ACL třída se rozrůstá do 1000 řádků s mnoha veřejnými metodami a sdíleným stavem.

Pravidlo: **ACL drží jednu odpovědnost.** Vrstva s desítkami metod a sdíleným stavem už ACL není. Jeden upstream koncept = jeden translator. Výstup translátoru je *vždy* doménový VO/entity/event, nikdy raw DTO. Pokud translátor začíná obsahovat doménovou logiku, je to signál, že máte *Application Service* schovanou v ACL – vyčleňte ji.

### ACL a Strangler Fig pattern

Anti-Corruption Layer je nosný prvek *Strangler Fig* patternu pro postupnou migraci z legacy. Detail viz kapitolu [Migrace z CRUD do DDD](/migrace-z-crud). V Strangler Fig přístupu **každý nový BC obklopuje ACL**, dokud legacy nezmizí. V tu chvíli ACL většinou také zmizí (nebo se zjednoduší na čistý translator bez anti-corruption logiky).

## 03.08 Open Host Service (OHS) {#ohs}

**Open Host Service** je vzor, kdy upstream *otevřeně publikuje* stabilní dokumentovaný protokol pro **mnoho** downstream konzumentů. Místo aby upstream udržoval N různých integračních smluv (jeden customer = jeden kontrakt), publikuje *jeden veřejný protokol* a downstreamy se k němu přizpůsobí.

Evans (2003) vychází z pozorování, že úprava translátoru pro každého z mnoha integrujících se sousedů tým utopí. Místo toho se definuje protokol, který subsystém zpřístupní jako sadu služeb, a otevře se všem, kdo integraci potřebují. Protokol se rozšiřuje podle nových integračních požadavků; výjimkou jsou idiosynkratické potřeby jediného týmu, které řeší jednorázový translator, aby sdílený protokol zůstal malý a soudržný.

### Kdy zvolit OHS

- **3+ downstream konzumentů**. S 1 konzumentem je OHS overkill – stačí Customer/Supplier ad hoc.
- **Stabilní doména**. Upstream model se mění zřídka, takže investice do veřejného protokolu má návratnost.
- **Otevřená integrace**, kde konzument může být i třetí strana (partneři, white-label klienti, mobilní aplikace).

### Implementace v Symfony 8

V Symfony 8 OHS typicky znamená jedno z:

- **REST API** přes `api-platform/core` nebo vlastní controllery, popsané OpenAPI spec.
- **gRPC** přes `spiral/roadrunner-grpc`, popsané `.proto` souborem.
- **Event stream** publikovaný přes RabbitMQ / Kafka, popsaný JSON Schema (přechod k [PL](#published-language)).

### Ukázka kódu: minimální OHS endpoint s versioningem

:::code{language="php" filename="src/Catalog/Infrastructure/Http/OpenHostService/ProductController.php"}
<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http\OpenHostService;

use App\Catalog\Application\Query\GetProductQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    /**
     * OHS v1 – stabilní kontrakt zveřejněný v OpenAPI.
     * Verze v URL ('/api/v1/...') je explicitní záruka, že se kontrakt nemění.
     */
    #[Route('/api/v1/products/{id}', name: 'api_v1_product_get', methods: ['GET'])]
    public function getProductV1(string $id): JsonResponse
    {
        $product = $this->handle(new GetProductQuery($id));

        return $this->json($product, context: ['groups' => ['ohs.v1']]);
    }

    /**
     * OHS v2 – přidává pole 'availableStock'. v1 zůstává funkční pro staré klienty.
     */
    #[Route('/api/v2/products/{id}', name: 'api_v2_product_get', methods: ['GET'])]
    public function getProductV2(string $id): JsonResponse
    {
        $product = $this->handle(new GetProductQuery($id));

        return $this->json($product, context: ['groups' => ['ohs.v1', 'ohs.v2']]);
    }
}
:::

Důležité: **v1 a v2 koexistují**. Zveřejnění OHS v1 je *závazek* – jakmile nějaký downstream začne v1 používat, nesmíte ji rozbít. Bez explicitního verzování to není OHS, jen „REST endpoint s nedostatečnou disciplínou“.

### Strategie verzování

Tři běžné přístupy k verzování OHS:

- **Verzování v URI** (`/api/v1/...`) – nejčitelnější, cacheovatelné na úrovni HTTP, doporučené pro veřejné API.
- **Verzování v hlavičce** (`Accept: application/vnd.catalog.v2+json`) – čistší URL, ale komplikovaná diagnostika a debugging.
- **Query parameter** (`?api-version=2`) – flexibilní, ale bývá zneužívaný k „polo-versioningu“ (nikdy nezmizí v1).

### Politika zastarávání

OHS musí mít explicitní politiku zastarávání. Příklad pro veřejné API:

- Při zveřejnění nové majoritní verze (v3) se starší verze (v1) označí jako *zastaralá* v dokumentaci.
- Hlavička `Deprecation: true` a `Sunset: Wed, 31 Dec 2025 23:59:59 GMT` se posílá v každé odpovědi v1.
- Minimálně 6 měsíců před odstraněním v1 dostanou všichni známí klienti oznámení.
- Po odstranění v1 vrací `410 Gone` s odkazem na migrační průvodce.

:::callout{type="pattern"}
**OHS bez verzování není OHS.** Je to neoznačený REST endpoint. Pokud nedokážete vyjmenovat neslučitelné změny za poslední rok, zveřejnit kalendář zastarávání a ukázat onboarding průvodce pro downstream konzumenty, vaše API není Open Host Service. Ani když tak vypadá.
:::

## 03.09 Published Language (PL) {#published-language}

**Published Language** je dobře dokumentovaný, formálně specifikovaný *formát* zpráv mezi Bounded Contexts, který je nezávislý na konkrétním programovacím jazyce, frameworku ani databázi. PL si může každý konzument přečíst, validovat proti němu a generovat z něj kód.

Evans (2003) doporučuje používat dobře dokumentovaný sdílený jazyk, který vyjádří potřebné doménové informace, jako společné komunikační médium – s překladem do něj a z něj podle potřeby. Vernon (2013) zdůrazňuje, že Published Language není jen schema – je to *ubiquitous language pro integraci*: pojmenovává koncepty, jejich invarianty a sémantiku.

### OHS vs. PL – kanál vs. formát

Vztah OHS a PL bývá matoucí. Hlavní rozdíl:

- **OHS je kanál** – REST endpoint, gRPC service, AMQP exchange. „Jak se data dostanou ven.“
- **PL je formát** – JSON Schema, OpenAPI, Avro, Protocol Buffers. „Jak data vypadají a co znamenají.“

Můžete mít OHS bez PL (REST endpoint vracející ad-hoc JSON) – a je to špatně, protože downstream nemá jak validovat. Můžete mít PL bez OHS (Avro schema na disku) – a je to také špatně, protože nikdo neví, jak data získat. **Plnohodnotná veřejná integrace = OHS + PL**.

### Příklady standardů PL

- **JSON Schema** ([json-schema.org](https://json-schema.org/)) – nejběžnější pro REST a event payloady.
- **OpenAPI** ([openapis.org](https://www.openapis.org/)) – popis kompletního REST API včetně paths, parameters, schemas.
- **AsyncAPI** ([asyncapi.com](https://www.asyncapi.com/)) – analogie OpenAPI pro asynchronní (eventní) integrace.
- **CloudEvents** ([cloudevents.io](https://cloudevents.io/)) – CNCF specifikace obálky pro eventy (typ, source, id, time).
- **Avro / Protobuf** – binární formáty s povinným schema, oblíbené pro Kafka/gRPC.

### Ukázka kódu: JSON Schema pro OrderPlaced event

:::code{language="json" filename="order-placed-v1.json"}
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://example.com/events/order-placed-v1.json",
  "title": "OrderPlaced",
  "description": "Doménová událost vyvolaná po úspěšném vytvoření objednávky v Ordering BC.",
  "type": "object",
  "required": ["eventId", "orderId", "customerId", "totalAmount", "currency", "occurredAt"],
  "properties": {
    "eventId": {
      "type": "string",
      "format": "uuid",
      "description": "Unikátní ID této instance eventu (pro deduplikaci na konzumentech)."
    },
    "orderId": {
      "type": "string",
      "format": "uuid",
      "description": "ID objednávky v Ordering BC."
    },
    "customerId": {
      "type": "string",
      "format": "uuid",
      "description": "ID zákazníka v Identity BC. Stabilní napříč BC."
    },
    "totalAmount": {
      "type": "integer",
      "minimum": 0,
      "description": "Celková částka v nejmenší jednotce měny (centech)."
    },
    "currency": {
      "type": "string",
      "pattern": "^[A-Z]{3}$",
      "description": "ISO 4217 kód měny (EUR, CZK, USD)."
    },
    "occurredAt": {
      "type": "string",
      "format": "date-time",
      "description": "ISO 8601 timestamp v UTC, kdy bylo event vyvoláno upstreamem."
    }
  },
  "additionalProperties": false
}
:::

Toto schema je publikováno na URL `https://example.com/events/order-placed-v1.json` a slouží jako **kanonický kontrakt**. Každý producer i konzument může proti němu validovat. Když Ordering BC chce přidat nové pole (například `shippingAddressId`), publikuje `order-placed-v2.json` a oba schémata koexistují minimálně po dobu okna zastarávání.

### Validace proti schema v Symfony

:::code{language="php" filename="src/Ordering/Infrastructure/PublishedLanguage/OrderPlacedValidator.php"}
<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\PublishedLanguage;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class OrderPlacedValidator
{
    public function __construct(
        private readonly Validator $validator,
    ) {}

    public function validate(string $json): void
    {
        $data = json_decode($json, false, flags: JSON_THROW_ON_ERROR);

        $result = $this->validator->validate(
            $data,
            'https://example.com/events/order-placed-v1.json',
        );

        if (!$result->isValid()) {
            $errors = (new ErrorFormatter())->format($result->error());
            throw new UnrecoverableMessageHandlingException(
                'Payload does not match OrderPlaced v1 schema: ' . json_encode($errors),
            );
        }
    }
}
:::

Schema validation je první krok ACL na konzumující straně. Pokud payload neprojde schema validací, vrací se `UnrecoverableMessageHandlingException` a zpráva se přesouvá do dead letter queue. Bez schema validace se downstream BC vystavuje všem chybám upstreamu.

:::callout{type="note"}
### Nástroje: schema-first vs. code-first

- **Schema-first** – JSON Schema / OpenAPI je zdrojem pravdy, kód je generován (např. `jane-php/open-api`). Upstream nemůže omylem rozbít kontrakt – kontrakt je oddělený artefakt.
- **Code-first** – entity v kódu jsou zdrojem pravdy, schema se generuje (Symfony Serializer s `nelmio/api-doc-bundle`). Snadnější vývoj, ale riziko, že interní změna kódu projde do veřejného kontraktu nepozorovaně.

Pro skutečně veřejné OHS je schema-first bezpečnější. Pro interní integraci mezi týmy stejné firmy je code-first často dostatečný (rychlost iterace).
:::

## 03.10 Separate Ways {#separate-ways}

**Separate Ways** je strategické rozhodnutí, že dva Bounded Contexts *nebudou integrovány vůbec*. Přijímáme duplicitu dat nebo paralelní procesy, protože náklady na propojení by převýšily jeho hodnotu. Separate Ways je jediný „vztah“, který znamená „žádný vztah“.

Evans (2003) připomíná, že integrace je vždy drahá a přínos bývá někdy malý. Bounded Context lze proto prohlásit za nepropojený s ostatními – vývojáři pak v takto malém rozsahu najdou specializovaná řešení bez ohledu na zbytek systému.

### Příklad: Marketing BC posílá maily přes vlastní SendGrid

Představme si situaci: Identity BC má hlavní seznam zákazníků s preferencemi. Marketing BC posílá hromadné mailové kampaně. Mohli bychom je integrovat:

- Identity by publikovala `CustomerEmailChanged` event a Marketing by si držel projekci.
- Marketing by před každým odesláním ověřoval u Identity, zda má zákazník opt-in.

Marketingový tým si spočítá: 50 kampaní ročně, integrace stojí 200 hodin vývoje a 8 hodin údržby měsíčně. Riziko špatně synchronizovaného opt-in stavu zůstane nenulové i s integrací. Místo toho přijme **Separate Ways**:

- Marketing si drží *vlastní* mailing list v SendGridu.
- Při odhlášení v hlavičce mailu (CAN-SPAM compliance) SendGrid zákazníka odhlásí lokálně.
- Když se zákazník odhlásí v Identity, Marketing pořád může poslat kampaň, ale bude tam legal opt-out v patičce a SendGrid honoruje globální unsubscribe.

Řešení má své ústupky – mohou nastat krátká okna, kdy odhlášený zákazník dostane jednu kampaň navíc – ale je to **levné** a **v souladu s legislativou**. A hlavně: rozhodnutí padlo vědomě.

### Kdy Separate Ways zvážit

- **Low-value integrace** – synchronizovaná data nepřinesou výrazné UX zlepšení.
- **High-effort sync** – integrace by vyžadovala distribuovaný konsensus, eventually consistent projekce, složitou retry logiku.
- **Krátká životnost jednoho z BC** – Marketing kampaňový engine se mění každé 2 roky; investice do hluboké integrace se nevyplatí.
- **Externí SaaS** bez kvalitního API – integrace by stejně byla nestabilní.

### Anti-vzor: „Separate Ways z lenosti“

Separate Ways je strategické rozhodnutí, ne výmluva pro vyhnutí se práci. Pokud tým prohlásí „my to nebudeme integrovat, je to příliš složité“ *bez* vyčíslení nákladů a hodnoty, není to Separate Ways – je to skrytý technický dluh. Skutečný Separate Ways má dokumentovanou alternativu (jak jsme to vyřešili bez integrace) a dokumentovaný okrajový případ (co se stane, když se to rozpadne).

:::callout{type="note"}
**Separate Ways nevylučuje pozdější integraci.** Pokud se ukáže, že duplicita je dražší než integrace, je to validní moment Separate Ways zrušit a postavit [Customer/Supplier](#customer-supplier). Typicky to nastane, když objem dat naroste, nebo když compliance audit přijde s otázkou „proč máte dva seznamy zákazníků?“. Strategická rozhodnutí jsou platná v daném kontextu – ne navždy.
:::

## 03.11 Praktický postup – jak nakreslit Context Map za 90 minut {#postup}

Context Map se nepíše v izolaci jedním architektem. Je to **týmové cvičení**, které vyžaduje účast lidí znalých všech BC v systému. Doporučená velikost skupiny: 3–8 lidí. Nástroj: tabule, sticky notes, fixy. Digitální nástroje (Miro, FigJam) jsou v pořádku, fyzická interakce u tabule však odhalí mezi týmy víc napětí než cokoli online.

### Pět kroků workshopu

1. **(0–15 min) Vyjmenovat všechny Bounded Contexts.** Sticky note pro každý BC, jméno + 1 věta popisu („Catalog: produktové info“, „Pricing: cena včetně slev“). Pokud někdo přidá víc než 12 BC, je to varovný signál – možná je modelujete příliš jemně.

2. **(15–45 min) Pro každou dvojici BC, která spolu interaguje, nakreslit šipku.** Šipka = směr toku dat / kauzality. Pojmenovat vztah jedním z 8 typů. Pokud se tým neshodne („je to Customer/Supplier nebo Conformist?“), je to indikátor, že vztah je *nedefinovaný* a stojí za eskalaci. Označit žlutým fixem.

3. **(45–60 min) Označit upstream (U) a downstream (D).** Na každé šipce napsat U na straně, která rozhoduje, a D na straně, která se přizpůsobuje. Pokud nikdo neví, kdo je U a kdo D, vztah *není pojmenovaný* – eskalace.

4. **(60–80 min) Identifikovat „nebezpečné“ vztahy.** Conformist k upstreamu, který se rychle mění; Big Ball of Mud (vícenásobné nepojmenované vztahy mezi stejnými BC); Shared Kernel, který přerůstá; Partnership, která už nemá doménový důvod. Pro každý nebezpečný vztah vytvořit konkrétní úkol (Jira ticket / koncept ADR).

5. **(80–90 min) Zachytit výsledek.** Vyfotit tabuli, vložit do `docs/context-map.png` v repu, doprovázet Markdown souborem `docs/context-map.md` s textovým popisem každého vztahu (kdo vlastní, kontrakt, frekvence změn, eskalační kontakt). Owner = architekt nebo tech lead.

### Co dát do textového popisu vztahu

:::code{language="plaintext" filename="docs/context-map.md (fragment)"}
## Catalog -> Ordering (Customer/Supplier + OHS + PL)

- **Upstream tým**: @catalog-team
- **Downstream tým**: @ordering-team
- **Kanál**: AMQP topic `catalog.events`
- **Schema**: https://schemas.example.com/catalog/product-v2.json
- **Frekvence změn**: ~1× kvartál (minor), 1× rok (major)
- **Eskalační kontakt**: @lead-architect
- **SLA**: 99.9% delivery within 5s, dead letter queue po 3 retry
- **Onboarding**: docs/onboarding/consume-catalog-events.md
:::

### Verzování Context Mapy

Context Map je **živý dokument**. Doporučení:

- Datum poslední aktualizace v patičce povinné.
- Verzování přes git – Markdown a SVG/PNG v repu.
- Revize po každé větší architektonické změně (nový BC, zánik BC, změna typu vztahu).
- Plánovaná revize 1× za 6 měsíců, i když se nic „nestalo“ – často se ukáže, že něco se stalo a nikdo to nezdokumentoval.

:::callout{type="warn"}
**Context Map zastarává.** Přepište ji při každé větší architektonické změně. Datum + verze v patičce povinné. Mapa, která je rok stará, je horší než žádná mapa – uvádí v omyl. Pokud nemáte čas Context Map pravidelně udržovat, ponechte si jen textovou složku (`docs/context-map.md`) – ta zastará pomaleji.
:::

## 03.12 Anti-vzor: Big Ball of Mud {#big-ball-of-mud}

**Big Ball of Mud** popsali Brian Foote a Joseph Yoder v roce 1997 v eseji *Big Ball of Mud* [[3]](http://www.laputan.org/mud/): „*A Big Ball of Mud is haphazardly structured, sprawling, sloppy, duct-tape and bailing wire, spaghetti code jungle.*“ V jazyce Context Mappingu: systém, kde každý BC „nějak“ mluví s každým, sdílí databázové tabulky, sdílí entity, sdílí ad-hoc service vrstvy – a nikdo nedokáže nakreslit Context Map, protože vztahy nejsou pojmenované.

### Symptomy

- **Sdílená databáze** mezi více BC, kde každý BC čte (a často píše) tabulky druhých.
- **Cirkulární závislosti** mezi BC – A volá B volá C volá A.
- **Doctrine entity sdílené** napříč BC – jediná třída `Order` je používaná v Catalog, Pricing i Billing s odlišnými očekáváními.
- **Service tříd s 50+ veřejnými metodami**, které „obstarají všechno“.
- **Žádný explicitní integrační kontrakt** – komunikace přes přímé volání DB, sdílené Redis klíče, file system.
- **Nemožnost říci „kde končí jeden BC a začíná druhý“.**

### Proč k tomu dochází

Foote & Yoder upozorňují, že Big Ball of Mud je v praxi de facto standardní architektura – ne proto, že by ji někdo volil, ale protože vzniká sama, když:

- Tým je pod tlakem dodat funkčnost rychle a nemá čas přemýšlet o hranicích.
- Noví inženýři kopírují existující vzory – a ty jsou samy o sobě špatné.
- Architekt(i) chybí nebo jsou ignorováni.
- Refactoring je politicky obtížný (přidává riziko ke krátkodobému dodání).

### Cesta ven

Big Ball of Mud se nedá „opravit“ rewriteem. Jediný funkční postup je **Strangler Fig**: postupně vyčleňovat čisté BC, každý obklopit ACL a přesouvat funkčnost ze staré spaghetti vrstvy do nového čistého modelu. Detail viz [Migrace z CRUD do DDD](/migrace-z-crud).

Detail anti-vzorů a jejich projevů v Symfony 8 najdete v kapitole [Anti-vzory v DDD](/anti-vzory). Ta pokrývá konkrétní symptomy v PHP/Symfony technologii a strategie jejich nápravy.

:::callout{type="warn"}
**Big Ball of Mud je výsledek absence Context Mappingu.** Pokud máte BBoM, prvním krokem refaktoringu *není* psaní kódu – je to nakreslení (čistě deskriptivní) Context Mapy popisující současný stav. Teprve s mapou v ruce se dá plánovat cesta ven.
:::

## 03.13 Shrnutí {#summary}

Context Mapping je strategická disciplína, která dává smysl Bounded Contextům tím, že popisuje, co se na jejich hranicích děje. Hlavní body:

- **Context Map = mapa vztahů.** Vizuální + textová dokumentace všech BC v systému a všech vazeb mezi nimi. Místo struktury tříd zachycuje organizační a politickou realitu.
- **Osm pojmenovaných vztahů.** Partnership, Shared Kernel, Customer/Supplier, Conformist, ACL, OHS, Published Language, Separate Ways. Každý má svůj kompromis (coupling vs. flexibilita) a každý odpovídá jiné organizační situaci.
- **ACL je nejčastěji potřebný vztah.** Skoro každá netriviální integrace s legacy nebo externím systémem chce ACL. Tři odpovědnosti: schema mapping, concept translation, anti-corruption.
- **OHS + PL = stabilní veřejná integrace.** Open Host Service je kanál, Published Language je formát. Bez versioningu nejde o OHS.
- **Big Ball of Mud = „ještě jsme nedělali Context Map“.** Pokud nedokážete nakreslit Context Map, máte BBoM. Cesta ven začíná deskriptivní mapou současného stavu, ne kódem.

Pro praktické nakreslení Context Mapy doporučujeme techniku [Event Stormingu](/event-storming) jako discovery workshop – odhalí jak hranice BC, tak vztahy mezi nimi v jediném sezení. Pro propojení s organizačním designem viz [Team Topologies](/team-topologies). Podle Conway's Law architektura kopíruje komunikační strukturu organizace – Context Map a org chart proto musí korespondovat. Jinak jeden z nich vyhraje a ten druhý se rozsype.

:::faq{}
- question: Jak často aktualizovat Context Map?
  answer: 'Při každé větší architektonické změně (nový BC, zánik BC, změna typu vztahu) okamžitě, plus plánovaná revize minimálně 1× za 6 měsíců. Pokud nemáte čas vizuální složku udržovat aktualně, ponechte si alespoň textový popis (<code>docs/context-map.md</code>) – ten zastará pomaleji než obrázek. Datum poslední aktualizace v patičce je povinné. Detail v <a href="#postup">sekci 03.11 Praktický postup</a>.'
- question: Můžu mít více než 1 typ vztahu mezi 2 BC?
  answer: 'Ano, je to běžné a často nutné. Customer/Supplier popisuje organizační vztah (kdo rozhoduje, kdo prosí), Open Host Service popisuje technický kanál a Published Language popisuje formát. Tyto tři se typicky kombinují do jednoho komplexního vztahu. Anti-Corruption Layer je technika, kterou downstream aplikuje uvnitř Customer/Supplier nebo Conformist vztahu. Při kreslení mapy stačí na šipku napsat všechny relevantní stereotypy: <code>&lt;&lt;Customer/Supplier&gt;&gt; &lt;&lt;OHS&gt;&gt; &lt;&lt;PL&gt;&gt;</code>.'
- question: ACL vs. Adapter – jaký je rozdíl?
  answer: 'Adapter (z Hexagonal Architecture / GoF) je technický vzor: třída, která implementuje port a překládá volání na konkrétní knihovnu (Doctrine, HTTP klient, Redis). ACL je strategický vzor: vrstva, která chrání váš doménový model před modelem cizího Bounded Contextu. ACL je <em>typicky implementován pomocí Adapterů</em>, ale ne každý Adapter je ACL. ACL má navíc tři specifické odpovědnosti – schema mapping, concept translation a anti-corruption (filtraci) – které „obyčejný“ Adapter nemá. Detail v <a href="#acl">sekci 03.07</a>.'
- question: Co dělat, když si všimnu Conformist vztahu, který tam neměl být?
  answer: 'Tři kroky. (1) Ověřte, že je to opravdu nechtěný Conformist – někdy je to vědomé strategické rozhodnutí, které tým zapomněl zdokumentovat. (2) Pokud je nechtěný, vyčíslete cenu jeho opravy: kolik doménových pojmů upstreamu prosáklo do downstream modelu, kolik testů by bylo třeba přepsat, jak často upstream dělá breaking changes. (3) Otevřete ADR (Architecture Decision Record) s návrhem migrace na ACL – typicky inkrementálně, jeden translator za sprint. Dokud není ADR schválené, držte si Conformist jako známý technický dluh v backlogu, ne jako překvapení v produkci.'
- question: Je Context Map součást Architecture Decision Record (ADR)?
  answer: 'Context Map sama o sobě není ADR – je to <em>průběžně udržovaný stav</em>, zatímco ADR popisuje konkrétní rozhodnutí v čase. Ale <strong>každá změna Context Mapy by měla mít ADR</strong>: „Změnili jsme vztah Catalog ↔ Pricing z Partnership na Customer/Supplier, protože…“. ADR pak slouží jako historie změn Context Mapy a dává budoucím inženýrům kontext, proč je mapa taková, jaká je. V repu typicky drží mapa <code>docs/context-map.md</code>, ADR <code>docs/adr/0023-rozdeleni-catalog-pricing.md</code>.'
- question: Jak Context Map kreslit v textu, ne nástrojem?
  answer: 'Pro malé systémy (do 5 BC) je textová Context Map v Markdownu dostatečná. Formát: pro každý vztah jeden odstavec s polotučnou hlavičkou ve tvaru <code>**Catalog -&gt; Ordering**</code>, šipka určuje směr (upstream → downstream), v textu typ vztahu (Customer/Supplier + OHS + PL), kontrakt, kontakt. Výhody: žádný nástroj, git review beze změny pipeline, full-text search. Nevýhody: chybí vizuální „aha“ efekt. Doporučení: textová verze <em>vždy</em>, vizuální verze (PlantUML, Mermaid, Excalidraw) navíc pro systémy s 5+ BC. PlantUML zdrojový kód lze držet vedle Markdownu a renderovat při CI.'
:::

## 03.14 Další četba {#further-reading}

- Eric Evans, *Domain-Driven Design: Tackling Complexity in the Heart of Software*, kap. 14 „Maintaining Model Integrity“ (Addison-Wesley, 2003) [[1]](https://www.domainlanguage.com/ddd/).
- Eric Evans, *Domain-Driven Design Reference: Definitions and Pattern Summaries* (2015) – stručné definice všech vzorů včetně Partnership a Big Ball of Mud; volně dostupná na [domainlanguage.com](https://www.domainlanguage.com/ddd/reference/).
- Vaughn Vernon, *Implementing Domain-Driven Design*, kap. 3 „Context Maps“ (Addison-Wesley, 2013) [[2]](https://kalele.io/books/).
- Brian Foote & Joseph Yoder, *Big Ball of Mud*, PLoP 1997 [[3]](http://www.laputan.org/mud/).
- Martin Fowler, *Bounded Context* (bliki) [[4]](https://martinfowler.com/bliki/BoundedContext.html).
- Vaughn Vernon, *Domain-Driven Design Distilled* (Addison-Wesley, 2016) – zkrácená přístupnější verze pro úvod do strategického designu.
- DDD Crew, *Context Mapping* shareable resources [[6]](https://github.com/ddd-crew/context-mapping) – komunitní vizuální notace pro Context Mapping.
