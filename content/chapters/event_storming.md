---
route: event_storming
path: /event-storming
title: Event Storming a Domain Storytelling
page_title: "Event Storming a Domain Storytelling – workshop pro objevení domény | DDD Symfony"
meta_description: "Praktický návod na Event Storming (Brandolini) a Domain Storytelling: jak připravit, vést a vyhodnotit workshop, na jehož konci máte Bounded Contexty, eventy a první kandidáty na agregáty."
meta_keywords: "Event Storming, Domain Storytelling, Alberto Brandolini, Stefan Hofer, Henning Schwentner, Domain Discovery, DDD workshop, Big Picture, Process Level, Design Level, Pivotal Event, Hot Spot, Bounded Context"
og_type: article
published: "2026-04-29"
modified: "2026-04-29"
breadcrumb_name: Event Storming
schema_type: TechArticle
schema_headline: "Event Storming a Domain Storytelling – workshop pro objevení domény"
chapter_number: "04"
category: Praxe
deck: "Před první řádkou kódu byste měli odejít od počítače. Event Storming Alberta Brandoliniho a Domain Storytelling Hofera & Schwentnera jsou dvě nejprověřenější workshopové techniky, jak v jedné místnosti dostat do shody vývojáře s doménovými experty. Průvodce, který v Symfony projektu funguje."
reading_time: 25
difficulty: 2
---

DDD se nezačíná u kódu. Začíná v místnosti, ve které proti sobě sedí lidé, kteří kód píší, a lidé, kteří doménu reálně provozují. Tato kapitola popisuje dvě konkrétní techniky, jak takovou místnost zařídit, jak v ní strávit dvě až čtyři hodiny smysluplně a jak z ní odejít s něčím, co se dá zítra otevřít v IDE: **Event Storming** Alberta Brandoliniho (2013) a **Domain Storytelling** Stefana Hofera a Henninga Schwentnera (2021). Obě techniky řeší stejný problém – extrakci tacitních doménových znalostí – ale různými cestami. Po této kapitole budete vědět, kterou kdy zvolit a jak ji prakticky uřídit.

## 04.01 Proč workshop, proč ne čtení dokumentace {#proc-workshop}

Standardní reakce vývojářského týmu, který má zahájit nový projekt nebo přepsat existující, je *„dejte nám specifikaci a my to naprogramujeme"*. Specifikace ale typicky neexistuje ve formě, která by stačila. Existují wiki stránky staré tři roky, e-mailová vlákna, ticketovací systém s 1 800 issues, a čtyři lidé, kteří „to vědí". Žádný z těchto zdrojů není autoritativní – každý zachycuje doménu z jiného úhlu, v jiné době a často si protiřečí.

To je v pořádku. Doména není *knihovna*, kterou lze přečíst; je to *znalostní síť*, která žije v hlavách doménových expertů. Když se vás obchodní ředitel a šéf logistiky liší v tom, co znamená „odeslaná objednávka", není to bug, ale signál: existují dva pohledy, dva kontexty, a tudíž – pravděpodobně – i dva [Bounded Contexty](/zakladni-koncepty#bounded-contexts). Workshop je formát, ve kterém tyto kontradikce **vidíte v reálném čase** a řešíte je společně. Wiki vám je nikdy neukáže; vždy zachytí pohled toho, kdo ji psal.

Eric Evans v *Domain-Driven Design* (2003) píše, že [Ubiquitous Language](/co-je-ddd#strategic-design) nelze odvodit z dokumentů; vzniká pouze v dialogu. Brandolini, Hofer a Schwentner přidávají k tomuto pozorování praktickou metodologii: konkrétní notaci, konkrétní harmonogram, konkrétní role v místnosti.

:::callout{type="note"}
### Co dostanete z workshopu, co z dokumentace nikdy {#why-workshop-heading}

- **Kontradikce v reálném čase.** Když dva experti řeknou totéž jinak, vidíte to a řešíte hned.
- **Slovník, který si lidé sami vytvořili.** Kód pak může používat přesně ty výrazy.
- **Sdílená paměť události.** Tým si pamatuje „když jsme řešili Stripe, padlo, že refundy jsou async". Wiki se zapomene.
- **Hot Spots – místa, která doména nemá vyřešená.** Ta byste z dokumentace neidentifikovali, protože dokumentace je vždy psaná jako „hotová".
:::

## 04.02 Event Storming – co to je a co umí {#event-storming-co}

**Event Storming** je kolaborativní modelovací technika, kterou v roce 2013 představil italský konzultant Alberto Brandolini. Princip je jednoduchý: účastníci v reálném čase pokládají na dlouhou stěnu (nebo Miro/Mural board) **oranžové sticky notes s doménovými událostmi vyjádřenými v minulém čase**. Postupně z nich vzniká časová osa toho, co se v doméně děje. Jak osa roste, přidávají se další barvy – modrá pro Commands, žlutá pro Actors, růžová pro Hot Spots – a obraz domény se vyjasňuje.

Brandolini techniku původně vyvíjel jako rychlý způsob, jak v *jednom dni* dostat do shody konzultanty, vývojáře a doménové experty. V *Introducing EventStorming* (Leanpub, 2021) pak techniku formálně rozdělil do tří úrovní detailu – každá řeší jinou otázku a má jiný cíl:

1. **Big Picture Event Storming** – strategická úroveň. Otázka: *„Co se v naší doméně vůbec děje?"* Cílem je objevit Bounded Contexty a hlavní procesy. Trvání 2-4 h, 8-12 účastníků.
2. **Process Level Event Storming** – operační úroveň. Otázka: *„Jak konkrétně běží jeden konkrétní proces?"* Cílem je popsat jeden Bounded Context detailněji, včetně Commands, Actors, Policies a externích systémů. Trvání 4-8 h.
3. **Design Level Event Storming** – taktická úroveň. Otázka: *„Jak se tato část modelu přeloží do tříd?"* Cílem jsou kandidáti na [agregáty](/zakladni-koncepty#aggregates), invariantní pravidla a první draft API. Trvání 2-6 h, typicky per BC.

Vaughn Vernon v *Domain-Driven Design Distilled* (Addison-Wesley, 2016, kap. 7) označuje Event Storming za „nejrychlejší známou cestu k pracovnímu modelu domény". Doporučuje ho jako první techniku, kterou tým zavede, než se pustí do taktických DDD vzorů.

:::diagram{fig="04.2-A" title="Tři úrovně Event Stormingu – od strategického přehledu k taktickému návrhu" src="images/diagrams/17_event_storming/big_picture_levels.svg"}
:::

## 04.03 Notace – barvy a tvary {#notace}

Event Storming používá **standardizovanou paletu barev**, kterou Brandolini ustanovil v roce 2014 a která se od té doby téměř nezměnila. Každá barva má jeden konkrétní význam a tým by ji měl dodržovat – jakmile začnete improvizovat, ztrácíte schopnost rychle „číst" cizí mapu.

| Barva | Tvar | Notace | Příklad | Význam |
|---|---|---|---|---|
| **Oranžová** | obdélníková sticky | Domain Event | `OrderPlaced` | Něco, co se v doméně stalo. **Vždy v minulém čase.** |
| **Modrá** | obdélníková sticky | Command | `PlaceOrder` | Záměr / rozkaz, který vede k eventu. Imperativ. |
| **Žlutá** | menší sticky | Actor / Aktér | `Customer`, `Cashier` | Kdo command iniciuje. Lidská role. |
| **Šedá / hnědá** | obdélníková sticky | External System | `Stripe`, `SendGrid` | Systém mimo naši doménu, se kterým komunikujeme. |
| **Růžová** | otočená do kosočtverce | Hot Spot | „Co když platba selže?" | Otázka, kontroverze, nevyjasněné místo. **Nediskutuje se** hned, jen se zaznamenává. |
| **Lila / světle fialová** | větší sticky | Policy / Reactive logic | „When OrderPlaced ⇒ send confirmation" | Reaktivní pravidlo: „kdykoliv se stane X, udělej Y". |
| **Zelená** | menší sticky | Read Model / Query Model | „Order detail page" | Pohled / projekce, na základě které se actor rozhodne pro command. |
| **Fialová** | velká sticky / čára | Bounded Context | „Ordering BC" | Hranice mezi modely. Kreslí se až ke konci Big Picture. |
| **Žlutooranžová (gold)** | velká sticky | Aggregate | `Order` | Konzistenční hranice. Objevuje se až na Design Level. |

:::callout{type="pattern"}
### Pravidlo minulého času {#past-tense-rule-heading}

Hlavní jazykové pravidlo Event Stormingu: **doménové eventy se píšou v minulém čase**. Píšete `OrderPlaced`, ne `PlaceOrder`. `PaymentReceived`, ne `ReceivePayment`. `ShipmentDispatched`, ne `DispatchShipment`.

Důvod není kosmetický. Minulý čas vás *jazykově nutí* mluvit o tom, co už nastalo (a tedy o doménové realitě), místo toho, co bychom rádi (záměru či featuře). Tento posun perspektivy je rozhodující – zabraňuje workshopu sklouznout do diskuse o tom, co bude umět formulář, a drží ho u toho, jak doména opravdu funguje. Brandolini to označuje jako *„grammar discipline"*.

Když si nejste jistí, zda je sticky event, command, nebo policy: zkuste si ji přečíst nahlas. Zní v minulém čase? Event. V imperativu? Command. „Když se stane X, dělej Y"? Policy.
:::

Pro online workshopy v Miru existuje hotová [Event Storming šablona](https://miro.com/templates/event-storming/) s předvybranými barvami stickies. Pro offline workshop si stejné barvy nakupte v balení Post-It 3M (oranžová má kód *Energetic Orange*, růžová *Power Pink*) a mějte vždy zásobu – workshop konzumuje stovky sticky notes.

## 04.04 Big Picture workshop – návod krok za krokem {#big-picture}

Big Picture je první workshop, který tým s novou doménou (nebo s migrací z existujícího CRUD systému, viz [kapitola o migraci](/migrace-z-crud)) udělá. Cílem není dokonalý model – cílem je **společná mapa** toho, co se v doméně děje, a identifikace 3-7 Bounded Contextů.

### 04.04.1 Příprava (-1 týden) {#bp-priprava}

Před workshopem se nelze vyhnout přípravě:

- **Místnost a stěna.** 4-8 m dlouhá rovná stěna, ideálně bez oken (světlo odlepuje stickies). Pokud je workshop online, založte v Miro nebo Mural *frame* minimálně 6000×3000 px.
- **Účastníci.** 6-12 lidí. Musí tam být **alespoň 2 doménoví experti** (lidé, kteří doménu reálně provozují, ne PM-ové). Z developer side: 3-5 vývojářů včetně tech leada. Plus jeden facilitátor (viz níže).
- **Materiál.** 5-10 balíčků oranžových stickies (3M Post-It, 76×76 mm), 2 balíčky růžových, 2 modrých, 1 žlutý, 1 šedý, 1 zelený, 1 lila (světle fialový), 1 tmavě fialový. Černé fixy Sharpie pro každého (žádné kuličkové pera – text nebude čitelný z 2 m).
- **Catering.** Káva, voda, ovoce, oběd. Workshop unaví – bez catering padá energie po 90 minutách.
- **Pozvánka.** Pošlete účastníkům 1-stránkovou agendu předem. Doménové experty upozorněte, že *nebudou prezentovat slidy*, ale budou „vyprávět příběh".

### 04.04.2 Postup workshopu (2-4 hodiny) {#bp-postup}

1. **(10 min) Brief a nakopávací event.** Facilitátor v 5 minutách vysvětlí pravidla: oranžová = co se stalo, minulý čas, lepit kamkoliv. Pak nakopne workshop tím, že napíše první event, o kterém ví, že nastává v doméně, a nalepí ho doprostřed stěny – např. `OrderPlaced`.
2. **(20-30 min) Chaotic exploration.** Všichni dostanou stejně oranžových stickies (~15 každý) a píší události, které je napadnou. **Lepí kamkoliv** bez pořadí. Jde o záměrný chaos – chcete, aby si lidé vzpomněli na vše, ne aby okamžitě strukturovali. Facilitátor sbírá poznámky a tlačí lidi: „a co se stane potom? a předtím?".
3. **(30 min) Time enforcement.** Facilitátor začne přesouvat eventy doleva (raně) a doprava (později). Vznikne časová osa. Účastníci do toho mluví – „ne, refund je až po reklamaci, posuň to". Duplicitní eventy se slučují, ale jen se souhlasem účastníků.
4. **(30-45 min) Pivotal Events.** Facilitátor identifikuje *zlomové body* – eventy, kolem kterých se přirozeně sdružuje skupina ostatních. V e-shopu typicky: `CustomerRegistered`, `OrderPlaced`, `PaymentSettled`, `ShipmentDispatched`, `OrderClosed`. Označí je velkou červenou šipkou nebo vodorovnou čarou pod osu. Typicky 3-7 pivotal events.
5. **(30-45 min) Hot Spots.** Kdykoliv během workshopu zazní otázka, kterou nikdo neumí hned zodpovědět („Co když zákazník zaplatí dvakrát?"), **nediskutuje se** – napíše se na růžovou sticky a nalepí se přesně tam, kde otázka vznikla. Po 45 minutách máte typicky 8-15 hot spotů. To je *nejcennější výstup* Big Picture.
6. **(20-30 min) Bounded Context boundaries.** Facilitátor s týmem hledá místa, kde se mění slovník – kde *tentýž* pojem znamená něco jiného, kde končí jeden příběh a začíná jiný. Označí je fialovými stickies nebo silnými fialovými čarami. Typicky 3-7 BC.
7. **(15 min) Foto a transkripce.** Wide-angle foto stěny v originálu, pak detailní fotky po sekcích. Vše uložit do `docs/discovery/<datum>/` v repu. Online workshop: Miro export jako PNG i jako board (link).

### 04.04.3 Co máte na konci Big Picture {#bp-vystup}

- Časová osa s 30-100 doménovými eventy.
- 3-7 identifikovaných pivotal eventů.
- 3-7 vyznačených Bounded Contextů.
- 8-15 hot spotů jako budoucí tickety.
- Foto / Miro export.

Co **nemáte** a ani by nemělo být cílem: kompletní model, schéma databáze, finální seznam tříd. Big Picture je strategický nástroj – taktiku řeší až Design Level.

### 04.04.4 Online varianta – Miro / Mural setup {#bp-online}

Když workshop musí být online (distribuovaný tým, pandemie, zahraniční doménový expert), příprava je o něco delší než pro offline, ale výsledek je téměř srovnatelný – pokud dodržíte několik pravidel:

1. **Frame 12 000 × 4 000 px.** Týmy často podcení velikost plátna. Big Picture na 50+ eventů potřebuje hodně horizontálního prostoru, jinak se účastníci začnou navzájem překrývat. V Miro založte nový board a první frame udělejte explicitně s těmito rozměry – parametr *Frame size*.
2. **Předpřipravená paleta.** Vlevo na boardu položte 7-9 zdrojových stickies (jednu od každé barvy) a kolem nich rámeček s popiskem „*Drag from here – copy & paste pak Ctrl+D*". Účastníci si stickies kopírují místo aby pracně otevírali sticky picker.
3. **Voice-only, kamery vypnuté.** Kamery odvádějí pozornost od boardu; všichni se musí dívat na stejný kanvas. Výjimka: úvodních 5 minut představení a pak při hot-spot diskusích.
4. **Breakout místnosti pro dvě fáze.** Při Pivotal Events fázi rozdělte skupinu do 2-3 breakout místností po 4 lidech. Každá skupina si v Miru pracuje na jednom segmentu časové osy. Po 20 minutách se vše vrátí zpět do hlavní místnosti a synchronizuje. Bez breakoutů online workshop kolabuje na jednoho aktivního a pět pasivních pozorovatelů.
5. **Přestávky každých 60 minut.** Online unavuje rychleji než offline. Vložte 10minutové přestávky a nezkracujte je.
6. **Asynchronní pre-work.** Pošlete účastníkům 24 hodin předem otevřený Miro board s úvodním textem a požádejte je, aby *před* workshopem nalepili 5-10 eventů, které je napadnou. Workshop pak nezačíná u prázdné stěny.

### 04.04.5 Kdy Big Picture *nedělat* {#bp-when-again}

- **Zralý produkt s ustáleným modelem.** Když tým pracuje v jedné doméně tři roky a má aktuální Context Map, nový Big Picture typicky neodhalí nic nového – investujte raději do Process Level pro konkrétní bolavý BC.
- **Tým není ochotný diskutovat.** Big Picture stojí na otevřené debatě. Pokud je v týmu strach z konfrontace nebo silně hierarchická kultura, nejdřív tu bariéru zlomte – jinak workshop produkuje falešný konsenzus.
- **Doménoví experti jsou v různých časových pásmech bez přesahu.** Big Picture musí proběhnout najednou. Pokud nemůžete najít 3-4 hodinové okno, kdy všichni hlavní hráči jsou online, udělejte místo toho sérii Domain Storytelling sessionů 1:1 a výstupy slijte.

:::callout{type="warn"}
### Facilitátor nesmí být tech lead {#facilitator-rule-heading}

Tech lead má názor – často velmi silný. V okamžiku, kdy facilituje, ten názor – vědomě či nevědomě – protlačí. Doménoví experti to vycítí a začnou si přikyvovat namísto toho, aby přinášeli vlastní pohled.

Facilitátor by měl být buď externí konzultant, nebo někdo z týmu, kdo *není* senior developer ani tech lead – typicky senior PM, agile coach nebo product designer. Pokud takovou roli nemáte, alespoň si **explicitně domluvte**, že tech lead bude během workshopu mlčet a mluvit jen tehdy, když se ho někdo přímo zeptá.

Brandolini v *Introducing EventStorming* tomu říká „*facilitator's silence*" – facilitátor neformuluje obsah, jen drží proces.
:::

## 04.05 Process Level – jeden BC, hlubší detail {#process-level}

Po Big Picture máte 3-7 Bounded Contextů. Process Level Event Storming si vždy bere **jeden BC najednou** a zhušťuje ho. Cílem je dostat se ke struktuře, která v Symfony reálně přeloží do `Command` tříd, `Handler`ů a `Event`ů na [message busu](/cqrs).

### 04.05.1 Co Process Level přidává oproti Big Picture {#pl-co-pridava}

- **Commands** (modré) – záměry, které vedou k eventům.
- **Actors** (žluté) – kdo command spouští.
- **Policies** (lila / světle fialové) – reaktivní pravidla typu „kdykoliv X, udělej Y".
- **External Systems** (šedé) – třetí strany.
- **Read Models** (zelené) – projekce, na základě kterých se actor rozhoduje.

### 04.05.2 Postup (4-8 hodin per BC) {#pl-postup}

1. Otevřete **jen události a hot spoty** z Big Picture, které spadají do cílového BC. Zbytek skryjte (jiný frame v Miru, papírová stěna jen pro tento BC).
2. Pro každou událost zpětně doplňte: **jaký command k ní vedl?** a **kdo ten command vyvolal?** Vznikne sekvence `Actor → Command → Event`.
3. Pro každou událost dopředně doplňte: **co se v reakci stane?** Zelené policy stickies. „Kdykoliv `OrderPlaced`, pošli potvrzovací mail".
4. Identifikujte commands, které volají externí systémy nebo na něj reagují (šedé). „Po `PaymentRequested` volám Stripe, čekám na `StripePaymentSucceeded`".
5. Pro každý command identifikujte, jaký **read model** actor potřebuje vidět, aby command spustil. „Cashier potvrdí objednávku, když vidí, že platba prošla – read model `OrderDetail` musí obsahovat `paymentStatus`".
6. Aktualizujte hot spoty – některé z Big Picture se na této úrovni vyřeší, jiné se rozpadnou na podrobnější (např. „Co když Stripe vrátí 500?").

### 04.05.3 Příklad – Ordering BC e-shopu {#pl-priklad}

Sekvence pro happy path:

:::code{language="plaintext" filename="Sekvence Process Level – happy path"}
Customer (actor)
    → PlaceOrder (command)
        → OrderPlaced (event)
            → "Reserve stock" (policy)
                → ReserveStock (command, jiný BC: Warehouse)
            → "Send confirmation email" (policy)
                → SendGrid (external system)
            → "Initiate payment" (policy)
                → ChargeCard (command, jiný BC: Payment)
                    → Stripe (external system)
                    → PaymentReceived (event)
:::

Tato sekvence není kód – je to mapa. Ale je z ní **okamžitě vidět**, že budete potřebovat:

- Application Service `PlaceOrderHandler` v Ordering BC.
- [Process Manager](/sagy-a-process-managery), který koordinuje `OrderPlaced → ReserveStock → ChargeCard` přes BC hranice.
- Adaptér k Stripe (anti-corruption layer).
- Read model `OrderDetailView` pro UI.

### 04.05.4 Co máte na konci Process Level {#pl-vystup}

- Pro každý BC: detailní mapu commands, events, policies, externals, read models.
- Seznam **kandidátů na Application Services** (1 command typicky = 1 service / 1 handler).
- Seznam **kandidátů na ságy / process managery** (každý policy přes hranici BC).
- Seznam externích systémů, pro každý plánovaný ACL.
- Aktualizovaný seznam hot spotů, vyřešené i nové.

## 04.06 Design Level – pro každý BC zvlášť {#design-level}

Design Level je nejtaktičtější vrstva Event Stormingu a první, která se přibližuje kódu. Cílem je pro každý Bounded Context identifikovat **agregáty**, jejich **invariantní pravidla** a způsob, jakým commands modifikují stav agregátu.

### 04.06.1 Co Design Level přidává {#dl-co-pridava}

- **Aggregates** (žlutooranžové, velké) – konzistenční hranice. Pro každý command identifikujte agregát, který ho obsluhuje.
- **Invariants** – pravidla, která agregát musí dodržet. Píšou se jako bullet pointy na sticky agregátu.
- **Pre-conditions** – co musí být splněno, aby command směl projít.

### 04.06.2 Postup (2-6 hodin per BC) {#dl-postup}

1. Vezměte mapu Process Levelu a pro každý **command** položte velkou žlutooranžovou sticky agregátu, který ho obsluhuje. Stejný agregát pro více commandů je v pořádku – agregát má více metod.
2. Pod každý agregát vypište jeho **invarianty**. „Order: nemůže být confirmed bez aspoň jedné položky", „Order: po cancelled už nelze confirm", „Order: součet item.quantity * item.price = total".
3. Pro každý command vyznačte **pre-conditions**: „`ConfirmOrder` vyžaduje, aby `Order` byl ve stavu `Pending` a měl alespoň jeden item".
4. Označte hot spoty, které vám chybí pro úplnou specifikaci agregátu („Co když má položka nulovou cenu? Jde o legitimní freebie nebo chybu?").

### 04.06.3 Mapping z workshopu do Symfony {#dl-mapping}

Workshop: `Customer → PlaceOrder → Order Aggregate → OrderPlaced`

Symfony / PHP draft (toto je první draft, ne finální kód):

:::code{language="php" filename="Symfony / PHP draft Ordering BC"}
// Application/Command/PlaceOrderCommand.php
namespace App\Ordering\Application\Command;

final readonly class PlaceOrderCommand
{
    public function __construct(
        public CustomerId $customerId,
        public array $items, // OrderItemDto[]
    ) {}
}

// Domain/Order.php
namespace App\Ordering\Domain;

final class Order
{
    /** @var DomainEvent[] */
    private array $releasedEvents = [];

    private function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private array $items,
        private OrderStatus $status,
    ) {}

    public static function place(
        OrderId $id,
        CustomerId $customer,
        array $items,
    ): self {
        // Invariant z workshopu: musí mít aspoň jednu položku
        if ($items === []) {
            throw new EmptyOrderNotAllowed();
        }
        $order = new self($id, $customer, $items, OrderStatus::Pending);
        $order->releasedEvents[] = new OrderPlaced($id, $customer);
        return $order;
    }

    public function confirm(): void
    {
        // Invariant z workshopu: confirm jen z Pending
        if ($this->status !== OrderStatus::Pending) {
            throw new CannotConfirmFromStatus($this->status);
        }
        $this->status = OrderStatus::Confirmed;
        $this->releasedEvents[] = new OrderConfirmed($this->id);
    }

    /** @return DomainEvent[] */
    public function releaseEvents(): array
    {
        $events = $this->releasedEvents;
        $this->releasedEvents = [];
        return $events;
    }
}

// Application/Handler/PlaceOrderHandler.php
namespace App\Ordering\Application\Handler;

#[AsMessageHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private EventBusInterface $eventBus,
        private OrderIdGenerator $ids,
    ) {}

    public function __invoke(PlaceOrderCommand $cmd): OrderId
    {
        $order = Order::place($this->ids->next(), $cmd->customerId, $cmd->items);
        $this->orders->save($order);
        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
        return $order->id();
    }
}
:::

**Každý prvek z workshopu se mapuje 1:1 do kódu**: command sticky → `PlaceOrderCommand`; aggregate sticky → `Order` entita; invariant z bullet pointu → throw v doménové výjimce; event sticky → `OrderPlaced` dispatchovaný na bus.

:::callout{type="pattern"}
### Komentář v kódu = pojítko s workshopem {#design-level-comment-heading}

Když píšete invariantní check v doménové třídě, dejte k němu komentář s odkazem na workshop:

:::code{language="php" filename="src/Ordering/Domain/Order.php (fragment)"}
// Invariant Order-3 (workshop 2026-04-29):
// "Order nemůže být confirmed bez aspoň jedné položky."
// Hot spot Order-7 (otevřený): co když je položka backorder?
if ($items === []) {
    throw new EmptyOrderNotAllowed();
}
:::

Tato vazba má praktický dopad. Za půl roku nový developer ví, odkud pravidlo pochází, a může si ho ověřit u doménového experta. Nesmaže ho v dobré víře jako „divnou validaci".
:::

## 04.07 Domain Storytelling – alternativa pro malé týmy {#domain-storytelling}

**Domain Storytelling** je workshopová technika, kterou v knize stejného jména (Addison-Wesley, 2021) představili Stefan Hofer a Henning Schwentner. Stejně jako Event Storming řeší extrakci doménových znalostí, ale jinou cestou: místo časové osy událostí kreslíte **příběh** o práci doménového experta ve standardizované piktogramové notaci.

### 04.07.1 Notace {#ds-notace}

- **Actor** – postavička panáčka. Kdo v doméně něco dělá. „Customer", „Cashier", „Warehouse worker". Mohou to být i jiné systémy nebo organizace.
- **Work Object** – piktogram věci, se kterou actor pracuje. Dokument (objednávka), peníze, e-mail, balík, zboží. Hofer se Schwentnerem v knize doporučují konkrétní ikonky, ale stačí stylizace.
- **Activity** – šipka mezi actorem a work objectem, opatřená **číslem v pořadí** (1, 2, 3...) a slovesem. „Customer (1) sends order to Cashier".
- **Annotation** – text bublina s poznámkou (důvod, hodnota, podmínka).
- **Group** – rámeček kolem skupiny activit, který je ohraničuje (typicky proces nebo Bounded Context).

### 04.07.2 Konkrétní příklad – proces objednávky v e-shopu {#ds-priklad}

Story *„Customer places an order"* v Domain Storytelling notaci, čtená v pořadí čísel:

1. **Customer** →(1) *browses* → **Catalog**
2. **Customer** →(2) *adds product to* → **Cart**
3. **Customer** →(3) *submits* → **Order** →(4) *to* → **Order System**
4. **Order System** →(5) *requests payment from* → **Payment Gateway** (annotation: „Stripe; async webhook")
5. **Payment Gateway** →(6) *confirms payment to* → **Order System**
6. **Order System** →(7) *sends* → **Confirmation Email** →(8) *to* → **Customer**
7. **Order System** →(9) *creates* → **Shipment Order** →(10) *for* → **Warehouse**

Kresba je úmyslně jednoduchá – ručně nakreslené piktogramy nebo nástroj [egon.io](https://egon.io/) (open source, browser-based). Příběh je čitelný shora dolů ve sledu čísel a každá activity má slovesné jméno.

### 04.07.3 Domain Storytelling vs. Event Storming – kdy zvolit co {#ds-vs-es}

| Kritérium | Event Storming | Domain Storytelling |
|---|---|---|
| Velikost skupiny | 6-12 lidí | 2-5 lidí |
| Doba trvání | 2-8 h | 30-90 min na story |
| Šíře záběru | Celý systém / podstatná část | Jeden konkrétní proces |
| Hloubka záběru | Mělčí, ale široký | Hluboká, úzká |
| Hlavní výstup | Bounded Contexty + eventy | Sekvence kroků s actor a work object |
| Dovednost facilitátora | Vyšší (mnoho lidí, chaos) | Nižší (lineární proces) |
| Doporučený nástroj | Stěna + Post-It nebo Miro | egon.io, papír, Miro |
| Kdy zvolit | Nový BC, migrace, strategický přehled | Hluboká diskuse o jednom procesu, malý tým, omezený čas |

Hofer a Schwentner v knize zdůrazňují, že obě techniky se **nekonkurují**, ale doplňují: Event Storming ukáže, jaké procesy v doméně existují (širokoúhlý objektiv), Domain Storytelling pak v každém z nich odkryje detail (teleobjektiv). Doporučují kombinovat: Big Picture Event Storming pro strategický přehled, Domain Storytelling pro jednotlivé hlavní procesy a Process / Design Level Event Storming pro implementaci.

:::callout{type="note"}
### Tooling pro Domain Storytelling {#ds-tooling-heading}

- **[egon.io](https://egon.io/)** – open-source nástroj přímo v prohlížeči. Drag-and-drop actor / work object / activity, export do SVG. Vhodný pro online workshopy a archivaci.
- **Miro / Mural** – pomocí vlastních tvarů. Méně specializované, ale tým ho už typicky zná.
- **Papír A1 + tlustý fix** – pro offline workshop nejrychlejší. Po kresbě vyfotit a uložit do `docs/discovery/`.

Knihu *Domain Storytelling* doplňuje volně přístupný web [domainstorytelling.org](https://domainstorytelling.org/) s vzorovými stories i šablonami.
:::

### 04.07.4 Praktický egon.io walkthrough {#ds-egon-walkthrough}

[egon.io](https://egon.io/) je open-source webová aplikace (postavená na bpmn-js), která Domain Storytelling notaci plně implementuje. Pro tým, který nechce kupovat Miro licence nebo tahat papír, je to vhodný nástroj. Postup pro první session:

1. **Otevřete egon.io v prohlížeči** – nevyžaduje registraci. Vlevo nahoře je toolbar s ikonkami: actor (panáček), work object (obdélník), activity (šipka).
2. **Začněte s actorem.** Přetáhněte ikonu „person" na plátno a pojmenujte ji rolí, ne osobou – `Customer`, ne `Petr Novák`. Pojmenování je důležité; v exportu se objeví u každé aktivity.
3. **Přidejte work object.** Druhý nejčastější tvar – věc, se kterou actor pracuje. V e-shopu typicky `Cart`, `Order`, `Invoice`, `ShipmentLabel`.
4. **Spojte je activity.** Klik na actora, drag na work object – egon.io vytvoří očíslovanou šipku. Slovesné jméno (*browses*, *submits*, *confirms*) se píše do labelu šipky.
5. **Buďte struční.** Jeden Domain Storytelling diagram by měl mít **jeden lineární příběh** s 5-15 aktivitami. Když jich máte 30, rozdělte ho na dva diagramy.
6. **Export do SVG.** Menu vpravo nahoře → Download → SVG. Soubor pojmenujte `<datum>-<story-name>.svg` a uložte do `docs/discovery/<datum>/storytelling/`. SVG je textový formát, který se v gitu pěkně diffuje a v PR review vidíte změny.

Pro tým, který chce diagramy generovat z kódu (např. v dokumentaci aktualizované CI), egon.io umí číst i **vlastní DSL formát** ve YAML, ze kterého pak rendrouje SVG. Tím můžete například mít zdrojový text storyboardu uložený v repu a jeho rendrovaná verze se generuje při buildu dokumentace.

## 04.08 Anti-vzory workshopů {#anti-vzory}

Workshop, který je špatně připravený nebo špatně řízený, je horší než žádný workshop – vytvoří zdání shody, která neexistuje, a tým podle něj implementuje špatný model. Zde je seznam nejčastějších anti-vzorů a jejich řešení.

:::callout{type="warn"}
### „Doménoví experti nemají čas, uděláme to bez nich." {#anti-no-experts-heading}

**Bez doménových expertů to není workshop, ale brainstorming developerů**, kteří si vymýšlí, jak doména funguje. Výstup vypadá podobně, ale je nepoužitelný – chybí mu validní kontradikce a hot spoty.

**Řešení:** nepřesvědčujte experty na 4 hodiny. Domluvte si *90 minut Big Picture*. Téměř vždy se to dá v kalendáři vyargumentovat. A pokud opravdu nikdo z expertů nemůže, workshop odložte – neudělejte ho jen proto, že máte rezervovanou místnost.
:::

:::callout{type="warn"}
### „Začneme rovnou Design Level, na Big Picture nemáme čas." {#anti-skip-bp-heading}

Když přeskočíte Big Picture, modelujete agregáty bez znalosti, ve kterém Bounded Contextu leží. Výsledek: *God Aggregate* typu `Order`, který obsahuje payment status, shipping data, fakturační adresu a kupóny – protože nikdo neoznačil, že tyto pojmy patří do různých BC.

**Řešení:** i kdyby Big Picture mělo být jen 90 minut, udělejte ho. Bez něj Design Level skoro vždy vede k nesprávnému rozdělení agregátů.
:::

:::callout{type="warn"}
### „Workshop facilituje senior dev / tech lead." {#anti-tech-lead-heading}

Senior developer při facilitaci podsouvá technický pohled – automaticky strukturuje eventy podle toho, co se dá hezky implementovat, místo podle toho, jak doména reálně funguje. Doménoví experti to vycítí a začnou potlačovat svůj jazyk ve prospěch toho „technicky čistého".

**Řešení:** facilitátor by měl být PM, agile coach, designer, nebo externí konzultant. Pokud takovou roli nemáte, alespoň si hlídejte tech leada – nejlépe ať během workshopu mlčí.
:::

:::callout{type="warn"}
### „Zápis = Word dokument." {#anti-word-heading}

Když převedete vizuální workshop do lineárního textu, ztratíte 80 % informace – rozložení v prostoru, vztahy, blízkost hot spotů k eventům. Wordový dokument o osmi stranách nikdo nepřečte; foto a Miro export se otevřou na 5 sekund a všichni si vzpomenou, co kde stálo.

**Řešení:** wide-angle foto stěny v originálu (4K), detailní fotky po sekcích, Miro link s read-only přístupem pro celý tým. Vše do `docs/discovery/<datum>/` v repu, vedle čistého `events.md` s prostým seznamem objevených eventů (řádek na event).
:::

:::callout{type="warn"}
### „Po workshopu se to nezapíše do kódu." {#anti-no-followup-heading}

Workshop, který skončí slávou, fotkou stěny a sdílením v Slacku, ale jehož výstup se nepromítne do kódu, je za 3 měsíce zapomenutý. Slovník, který v místnosti vznikl, se v kódu nepoužije, a Ubiquitous Language opět degeneruje.

**Řešení:** v prvním PR po workshopu pojmenujte třídy přesně podle workshopu (`OrderPlaced`, ne `OrderSavedEvent`). Doplňte komentáře s odkazem na hot spoty. Jeden hot spot z workshopu = jeden ticket v issue trackeru.
:::

:::callout{type="warn"}
### „Big Picture musíme dotáhnout k dokonalosti." {#anti-perfectionism-heading}

Big Picture nemá být dokonalý. Je to první mapa neznámého území. Pokud na něm strávíte 8 hodin a budete debatovat o tom, zda `OrderShipped` je `ShipmentDispatched` nebo `OrderDispatched`, ztrácíte čas – rozhodnutí padne až na Process Levelu, kde uvidíte kontext.

**Řešení:** stanovte si 4hodinový timebox. Pak workshop skončete, i kdyby polovina hot spotů byla nevyřešená – to je v pořádku. Hot spoty *mají* zůstat otevřené.
:::

## 04.09 Po workshopu – co s výstupem {#po-workshopu}

Workshop bez follow-upu je promarněná investice. Zde je seznam **4 konkrétních artefaktů**, které musí jít do repa do 24 hodin po skončení workshopu.

### 04.09.1 Foto / Miro link {#post-1-foto}

Wide-angle foto stěny v originálu, detailní fotky po sekcích, Miro export PNG i link. Uložit do:

:::code{language="plaintext" filename="docs/discovery/<datum>/"}
docs/discovery/2026-04-29-big-picture/
├── 00-wide-angle.jpg
├── 01-customer-area.jpg
├── 02-payment-area.jpg
├── 03-shipment-area.jpg
├── 99-miro-export.png
└── README.md
:::

`README.md` obsahuje datum, účastníky, BC, a link na živý Miro board.

### 04.09.2 Aktualizovaný Context Map {#post-2-bc}

Z fialových BC stickies aktualizujte [Context Map](/context-mapping) v `docs/context-map.png`. Pokud ji ještě nemáte, vytvořte ji teď. Pro každý BC zkontrolujte, který tým ho vlastní ([core / supporting / generic](/subdomeny)).

### 04.09.3 Seznam doménových eventů {#post-3-events}

Plain-text soubor s jedním eventem na řádek. Slouží jako reference pro budoucí PR – když vývojář přidává nový event, kontroluje, zda už nějaký podobný neexistuje.

:::code{language="plaintext" filename="docs/discovery/2026-04-29-big-picture/events.md"}
# docs/discovery/2026-04-29-big-picture/events.md

## Ordering BC
- OrderPlaced
- OrderConfirmed
- OrderCancelled
- OrderItemAdded
- OrderItemRemoved

## Payment BC
- PaymentRequested
- PaymentReceived
- PaymentFailed
- PaymentRefunded

## Shipment BC
- ShipmentCreated
- ShipmentDispatched
- ShipmentDelivered
- ShipmentReturned
:::

### 04.09.4 Hot Spots → tickety {#post-4-hotspots}

Každý hot spot z workshopu = jeden ticket v issue trackeru, ve formátu „*Discovery question*" nebo „*Domain question*", s odkazem na fotku/Miro. Ticket je přiřazen doménovému expertovi, ne developerovi – protože odpověď leží v doméně, ne v kódu.

:::code{language="plaintext" filename="Šablona ticketu z hot spotu"}
Title: [Discovery] Co když platba selže po vytvoření zásilky?
Labels: discovery, ordering-bc
Assignee: @business-expert-name
Description:
Hot spot z Big Picture workshopu 2026-04-29 (foto: docs/discovery/2026-04-29-big-picture/02-payment-area.jpg).
Tým si není jist, zda se zásilka vrací zpět, nebo se účet zákazníka jen označí jako neuhrazený.
Potřebujeme jednoznačné rozhodnutí před implementací Process Manager v Ordering BC.
:::

### 04.09.5 Doporučená struktura repa po prvním workshopu {#post-5-repo}

Aby výstup workshopu nebyl pohřben v Slacku, doporučujeme v Symfony projektu rovnou založit tuto adresářovou strukturu. Každý soubor má jasný účel a nikdo nemusí hádat, kam co patří:

:::code{language="plaintext" filename="Doporučená struktura repa"}
my-symfony-app/
├── docs/
│   ├── discovery/
│   │   └── 2026-04-29-big-picture/
│   │       ├── 00-wide-angle.jpg
│   │       ├── 01-customer-area.jpg
│   │       ├── 02-payment-area.jpg
│   │       ├── 03-shipment-area.jpg
│   │       ├── 99-miro-export.png
│   │       ├── events.md           ← seznam doménových eventů (text)
│   │       ├── hot-spots.md        ← otázky k vyřešení
│   │       └── README.md           ← účastníci, datum, link na Miro
│   ├── context-map.png             ← aktualizovaná z workshopu
│   ├── context-map.md              ← textový popis vztahů mezi BC
│   └── ubiquitous-language.md      ← rostoucí slovník pojmů
├── src/
│   ├── Ordering/                   ← jeden BC z workshopu = jeden namespace
│   │   ├── Domain/
│   │   ├── Application/
│   │   └── Infrastructure/
│   ├── Payment/
│   └── Shipment/
└── ...
:::

Adresář `docs/discovery/` je **append-only** – staré workshopy se nemažou, jen se přidávají nové (s novým datem). Tým tak má historii, jak se mapa domény vyvíjela, a re-storming porovná `docs/discovery/2026-04-29-big-picture/events.md` s `docs/discovery/2026-10-15-re-storming/events.md`.

Dolní hranice `src/Ordering`, `src/Payment`, `src/Shipment` přímo zrcadlí 3 fialové stickies z workshopu – Bounded Contexty. Když nový developer otevře projekt, vidí strukturu, která mu odpovídá tomu, co viděl na fotce ze workshopu. Tato vazba mezi *artefaktem v repu* a *artefaktem ze stěny* je jediná ochrana proti tomu, aby se jazyk workshopu po půl roce vytratil z kódu.

### 04.09.6 První PR po workshopu {#post-6-prvni-pr}

První pull request po workshopu by měl být **malý a explicitně značený** jako follow-up – ne velký commit s implementací první feature. Doporučená velikost:

- Vytvoření `docs/discovery/<datum>/` se všemi výstupy workshopu.
- Aktualizace `docs/context-map.md` a `docs/ubiquitous-language.md`.
- Založení prázdných namespace adresářů (`src/<BC>/Domain/`) s krátkým `README.md` v každém – kdy vznikl, z jakého workshopu, co obsahuje.
- Tickety pro hot spoty (případně přes script, který je vytvoří hromadně).

Žádný kód doménové logiky. Tento PR má jediný úkol: **uložit společnou paměť workshopu do repa, než ji všichni zapomenou.** Implementace prvního agregátu přijde v dalším PR, který už je Design Level výstupem.

:::callout{type="pattern"}
### Workshop commit message konvence {#commit-disclaimer-heading}

Pro PR navazující na workshop používejte konzistentní commit message, aby je šlo v gitu vyhledat:

:::code{language="plaintext" filename="git commit message konvence"}
docs(discovery): big picture workshop 2026-04-29

Účastníci: 4 doménoví experti, 5 vývojářů, 1 PM, 1 facilitátor.
Identifikováno: 5 BC (Ordering, Payment, Shipment, Catalog, Identity),
               12 hot spotů, 47 doménových eventů.
Miro: https://miro.com/board/xyz123 (read-only)
Foto: docs/discovery/2026-04-29-big-picture/
:::

Za rok, když si potřebujete dohledat „kdy jsme rozhodli, že refunds patří do Payment BC, ne do Ordering", `git log --grep="discovery"` vás dovede k odpovědi za 5 vteřin.
:::

## 04.10 Pravidelné re-stormingy {#re-storming}

Doména se vyvíjí. Pivotní událost, která dnes platí (`OrderPlaced`), může za rok ztratit význam, protože podnikání přešlo na model *subscription* a ústředním eventem se stane `SubscriptionRenewed`. Když tým neudělá nový workshop, kód a doména se rozejdou – a nikdo si toho hned nevšimne, protože jednotlivé PR vypadají rozumně.

### 04.10.1 Doporučená frekvence {#re-cadence}

- **Pravidelně**: 1× za 6 měsíců nebo 1× za rok velký Big Picture re-storming pro celý systém. Podle stáří produktu – startup může re-stormovat čtvrtletně, zralý produkt jednou ročně.
- **Po velkém produktovém rozhodnutí**: nový tržní segment, nový obchodní model, akvizice. Re-storming proběhne *před* implementací, ne po ní.
- **Při akutních problémech**: tým má pocit, že kód „nedává smysl" nebo že feature requesty se opakovaně modelují špatně. Pak je čas znovu vytáhnout stickies.

### 04.10.2 Diff jako priorita refaktoringu {#re-diff}

Po re-stormingu porovnejte novou mapu se starou (uloženou v `docs/discovery/<starý-datum>/`). Místa, kde se mapa změnila **nejvíc**, jsou **kandidáti na refaktoring** – tam doména reálně dopředu „utekla" kódu. Naopak místa, kde se mapa změnila málo, jsou stabilní a kód v nich je pravděpodobně v pořádku.

Brandolini tomu říká „*Strategic re-storming*" – typicky ho dělá menší skupina (3-5 lidí z původního workshopu) a trvá kratší dobu, protože hodně mapy se zachová.

## 04.10b Most z workshopu do testů {#workshop-to-tdd}

Design Level Event Storming přirozeně ústí v test-driven development. Každý invariant napsaný na sticky agregátu je **jeden test case**. Každý hot spot, který se za běhu workshopu vyřešil, je **jeden další test case**. Tým, který z workshopu odejde a nezačne psát testy podle invariantů, ztrácí polovinu hodnoty workshopu.

### 04.10b.1 Mapping invariantů na PHPUnit testy {#tdd-mapping}

Sticky agregátu z workshopu:

:::code{language="plaintext" filename="Sticky agregátu Order"}
Order Aggregate
- Inv-1: nemůže být confirmed bez aspoň jedné položky
- Inv-2: po cancelled už nelze confirm
- Inv-3: součet item.quantity * item.price = total
- Inv-4: confirm vyžaduje, aby payment byl Settled (hot spot Order-7)
:::

Přímý překlad do testů:

:::code{language="php" filename="tests/Ordering/OrderTest.php"}
final class OrderTest extends TestCase
{
    /** @test Inv-1 (workshop 2026-04-29) */
    public function place_throws_when_no_items(): void
    {
        $this->expectException(EmptyOrderNotAllowed::class);
        Order::place(OrderId::new(), new CustomerId('c1'), []);
    }

    /** @test Inv-2 (workshop 2026-04-29) */
    public function cannot_confirm_after_cancellation(): void
    {
        $order = Order::place(OrderId::new(), new CustomerId('c1'), [$this->item()]);
        $order->cancel('customer request');
        $this->expectException(CannotConfirmFromStatus::class);
        $order->confirm();
    }

    /** @test Inv-3 (workshop 2026-04-29) */
    public function total_equals_sum_of_line_subtotals(): void
    {
        $order = Order::place(
            OrderId::new(),
            new CustomerId('c1'),
            [$this->item(qty: 2, price: 100), $this->item(qty: 1, price: 50)],
        );
        self::assertSame(250, $order->total()->amount());
    }
}
:::

Komentáře `Inv-1 (workshop 2026-04-29)` nejsou kosmetika – umožňují dohledání původu. Když test selže za půl roku a nový developer chce zjistit, proč pravidlo existuje, doloví ho přes git blame nebo podle data workshopu.

### 04.10b.2 Doménové eventy jako tests {#tdd-events}

Z Process Levelu máte sekvenci `Command → Event → Policy → Command`. Tato sekvence je acceptance test:

:::code{language="php" filename="tests/Ordering/PlaceOrderHandlerTest.php"}
final class PlaceOrderHandlerTest extends KernelTestCase
{
    /** @test Workshop scenario "Customer places an order" (2026-04-29) */
    public function place_order_emits_OrderPlaced_and_triggers_payment(): void
    {
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $events = $this->collectEvents();

        $bus->dispatch(new PlaceOrderCommand(
            new CustomerId('c1'),
            [new ItemDto('SKU-1', 1, 100)],
        ));

        self::assertCount(1, $events->ofType(OrderPlaced::class));
        // Policy z workshopu: OrderPlaced ⇒ ChargeCard
        self::assertCount(1, $events->commands(ChargeCardCommand::class));
    }
}
:::

Toto má dva přínosy. První: testy jsou *čitelné pro doménové experty* – pojmenování přesně odpovídá workshopu, takže ne-developer si test může přečíst a potvrdit, že vyjadřuje to, co měl na mysli. Druhý: testy jsou **ochrana před regresí**. Když někdo za rok refaktoruje a omylem porušuje invariant z workshopu, test ho chytí.

Podrobně viz kapitolu [Testování v DDD](/testovani-ddd) – testovací strategie, doménové testy, integrační testy s Symfony Messenger.

## 04.11 Shrnutí {#summary}

Event Storming a Domain Storytelling jsou dvě konkrétní, prověřené techniky, jak před první řádkou kódu dostat doménu na společný papír. Obě stojí na stejném předpokladu: doménové znalosti nelze přečíst – musí se v dialogu objevit.

- **Event Storming** ve třech úrovních (Big Picture / Process Level / Design Level) je nástrojem pro *širokoúhlé* mapování domény. Big Picture objevuje Bounded Contexty a pivotal events. Process Level zhušťuje jeden BC do Command-Event-Policy sekvencí. Design Level dodává agregáty s invarianty.
- **Domain Storytelling** je *úzkoúhlý teleobjektiv* pro hloubkovou diskusi nad jedním procesem v malé skupině. Notace actor-work object-activity je intuitivní a vhodná pro kontexty, kde Event Storming je „příliš velký".
- **Vychází se ven, ne dovnitř.** Workshop začíná u doménového experta, ne u datového modelu. Eventy se píšou v minulém čase, agregáty se objevují až nakonec.
- **Workshop bez follow-upu je promarněný.** Foto, eventy, hot spoty a Context Map musí jít do repa do 24 hodin a do kódu do 1-2 sprintů.
- **Re-stormujte pravidelně.** Doména se vyvíjí; mapa zastará. 1× za 6-12 měsíců nebo po každém velkém produktovém rozhodnutí.

Po prvním Event Stormingu typicky následuje implementace prvního Bounded Contextu – viz kapitoly o [základních konceptech DDD](/zakladni-koncepty), [CQRS](/cqrs), [Event Sourcingu](/event-sourcing) a [ságách](/sagy-a-process-managery). Pokud migrujete z legacy CRUD systému, pokračujte kapitolou [Migrace z CRUD na DDD](/migrace-z-crud).

:::faq{}
- question: Kolik lidí by mělo být na Event Storming workshopu?
  answer: 'Pro Big Picture 6-12 lidí: 2-4 doménoví experti, 3-5 vývojářů (včetně tech leada), 1 PM nebo product owner, 1 facilitátor. Méně než 4 lidé znamená příliš úzký pohled; více než 14 lidí znamená, že se část účastníků stane diváky a workshop ztrácí dynamiku. Pro Process Level a Design Level stačí 4-8 lidí; tam jde o detail jednoho BC. Pro Domain Storytelling 2-5 lidí. Detailní rozpis v <a href="#big-picture">sekci 04.04</a>.'
- question: Dá se Event Storming dělat online?
  answer: 'Ano, ale s kompromisy. Online (Miro, Mural, Lucidspark) odpadá fyzická únava a tým nemusí cestovat, ale ztrácíte něco z energie chaotic exploration fáze – v Miru lidé píšou pomaleji než lepí Post-It na zeď. Doporučení: Big Picture v úvodu projektu udělejte offline, pokud to lze; následné Process / Design Level a re-stormingy pak klidně online. Nezapomeňte na breakout místnosti pro paralelní diskuse a častější přestávky (online unaví víc).'
- question: Jak vést hot spoty během workshopu?
  answer: 'Pravidlo zní: <strong>nediskutuje se, jen se zaznamenává</strong>. Když během workshopu zazní otázka, kterou nikdo neumí hned odpovědět, facilitátor ji okamžitě napíše na růžovou sticky a nalepí přesně tam, kde otázka vznikla, a workshop pokračuje dál. Pokus o vyřešení hot spotu hned vždy konzumuje 15-30 minut a typicky se nedořeší – protože odpověď leží mimo místnost. Po workshopu se každý hot spot stane ticketem přiřazeným doménovému expertovi, ne developerovi.'
- question: Kdo platí workshop – produkt nebo engineering?
  answer: 'Engineering. Argument je jednoduchý: bez workshopu engineering vyrobí špatný model, který bude refaktorovat tři sprinty, což stojí mnohonásobně víc než 4 hodiny doménových expertů. V praxi by produkt a engineering měli platit společně – workshop je investice do společné Ubiquitous Language a slovníku, který používají obě strany. Pokud ho zaplatí jen jedna, druhá strana ho nevezme vážně.'
- question: Co když doménoví experti používají hovorovou češtinu a slang („chronický neplatič nás zase odbil")?
  answer: 'Workshop dělejte v jazyce, který experti používají v reálné práci – typicky češtinou s vlastním slangem. Slang se neopravuje; <em>je</em> Ubiquitous Language. Když expert říká „chronický neplatič", napište to na sticky tak, jak to řekl, a v kódu pak modelujte koncept s tímto jménem (např. <code>ChronicLatePayer</code>), případně doplňte synonymum používané v týmu jako PHPDoc komentář. Ztratit jazyk = ztratit slovník = za rok zase nikdo neví, o čem mluvíme.'
- question: Když máme jen sólo developer a PM, dá se Event Storming dělat ve dvou?
  answer: 'Ne, Event Storming ve dvou ztrácí smysl – je založen na konfrontaci více pohledů. Místo toho použijte <a href="#domain-storytelling">Domain Storytelling</a>, který je pro 2-5 lidí navržený. PM hraje doménového experta, developer kreslí story, debatujete krok za krokem. Za 60-90 minut dostanete srovnatelný výstup pro jeden konkrétní proces. Až přibude třetí člen týmu nebo se uvolní více doménových expertů, přejděte k Big Picture Event Stormingu.'
:::

## 04.12 Další četba {#further-reading}

- [Alberto Brandolini – *Introducing EventStorming* (Leanpub, 2021)](https://leanpub.com/introducing_eventstorming). Autoritativní kniha přímo od autora techniky; detailní popis všech tří úrovní, příklady, anti-patterny.
- [eventstorming.com](https://www.eventstorming.com/) – oficiální web techniky, kde Brandolini publikuje šablony, fotografie z workshopů a aktuální postupy.
- [Stefan Hofer & Henning Schwentner – *Domain Storytelling: A Collaborative, Visual, and Agile Way to Build Domain-Driven Software* (Addison-Wesley, 2021)](https://domainstorytelling.org/). Komplexní kniha o Domain Storytellingu s notací, příklady a integrací s DDD.
- [egon.io](https://egon.io/) – open-source webový nástroj pro Domain Storytelling. Drag-and-drop editor, export do SVG.
- [Vaughn Vernon – *Domain-Driven Design Distilled* (Addison-Wesley, 2016)](https://www.amazon.com/Domain-Driven-Design-Distilled-Vaughn-Vernon/dp/0134434420), kapitola 7 obsahuje stručný úvod do Event Stormingu jako součásti DDD strategie.
- [Eric Evans – *Domain-Driven Design: Tackling Complexity in the Heart of Software* (Addison-Wesley, 2003)](https://www.domainlanguage.com/ddd/). Kniha, ze které DDD vychází; Ubiquitous Language a Bounded Context jsou základem všech workshopových technik.
- [Miro Event Storming template](https://miro.com/templates/event-storming/) – hotová šablona pro online workshopy s předpřipravenými barvami stickies.
