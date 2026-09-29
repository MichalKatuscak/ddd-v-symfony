# Revize knihy 24.–28. 9. 2026 (druhé kolo) – souhrnný report

Zadání redakce: /tmp/claude-1000/-home-michal-Work-ddd-v-symfony/dd4fb968-e373-4735-828e-7ca8fdcbc33b/scratchpad/brief.md, oprav rozporů: /tmp/claude-1000/-home-michal-Work-ddd-v-symfony/dd4fb968-e373-4735-828e-7ca8fdcbc33b/scratchpad/fix-brief.md (obsah níže).

# Zadání: oprava rozporů napříč knihou (kolo 2, 27. 9. 2026)

Repo /home/michal/Work/ddd-v-symfony. Nálezy: `SCR/reports/consistency.md` (C-N, T-N, L-N, D-N
a sekce „Předané z reportů redaktorů“). SCR = /tmp/claude-1000/-home-michal-Work-ddd-v-symfony/dd4fb968-e373-4735-828e-7ca8fdcbc33b/scratchpad
Styl a konvence: `CLAUDE.md` (přečti celé). Předchozí mantinely: `SCR/brief.md` (sekce Mantinely platí dál).

Kapitoly právě prošly jazykovou redakcí (necommitováno). Citace v reportu mohou být mírně posunuté – vždy
si ověř aktuální text. Edituj **jen své soubory**; ostatní paralelně edituje jiný agent. Pokud nález
vyžaduje změnu v cizím souboru, řeš jen svou stranu a v reportu to uveď.

## Rozhodnutí koordinátora (závazná)

- **C-1:** tolerant reader. Schéma v kap. 03 nemá `additionalProperties: false` (nebo věta, že konzument validuje
  jen povinná pole); v2 jen pro rozbíjející změny. Pole `totalAmountCents`. `description` = integrační událost.
- **C-2:** pravidla (phparkitect/Deptrac/text) povolují doméně `Symfony\Component\Uid` a identifikátory cizích
  kontextů (`App\*\Domain\ValueObject\*Id`) a (dle dřívějšího rozhodnutí) `Doctrine\ORM\Mapping`, `Doctrine\Common\Collections`.
- **C-3:** handlery neimportují Infrastructure: atribut voteru jako řetězec/konstanta v Application; listener 403/409 do
  `App\Ordering\Infrastructure\Http` (výjimky z Ordering); read model s DBAL do `Infrastructure/ReadModel`
  (případně rozhraní v Application). Deptrac v kap. 17 povolí Presentation → Domain.
- **C-4:** kanonický FQCN `App\Ordering\Application\Command\PlaceOrder`; Ordering ve vrstvách.
- **C-5 … C-26, C-28 … C-35:** aplikuj návrh z reportu.
- **C-27:** kanonický `Order` je `final`. Každé `class Order extends AggregateRoot` bez `final` → `final class`.
- **C-34 hlášky výjimek:** v kódu anglicky (převažuje).
- **C-35 kadence revize UL:** kanonická je event_storming (6 měsíců / rok); pain points se přizpůsobí. „Shipment BC“ → Shipping.
  Dispatcher-vs-Messenger callout v kap. 10: synchronní `event.bus` je kanonická cesta pro posluchače v kontextu;
  EventDispatcher pro technické/frameworkové háčky (kernel eventy, Doctrine lifecycle…). Ne „anti-vzor“.
- **C-16:** klasifikace podle kap. 02 (Ordering = Supporting v e-shopu); kap. 09 ve stromu nesmí tvrdit opak –
  označ jako Core jiný kontext (např. Pricing), nebo komentář neutralizuj.
- **Terminologie:** T-1 „microservice(s)“; T-2 „modulární monolit“ v próze (anglicky jen při prvním výskytu v kap. 19,
  nadpisy smí zůstat, kotvy neměnit); T-3 „doménová událost“; T-4 „sága“, „Process Manager“; T-5 „Event Storming“
  (mimo názvy zdrojů); T-6 „Ubiquitous Language“; T-7 „Strangler Fig Pattern“ (u Fowlerova názvu zdroje nech originál);
  T-8 „read model“; T-9 „kořen agregátu“; T-10 dle návrhu. Neměň citace a názvy zdrojů.
- **L-1:** texty odkazů = aktuální tituly z `src/Catalog/Chapters.php`. **L-2:** cíl řádku „Mutovatelná událost“ →
  `/zakladni-koncepty#domain-events` (ověř kotvu). **L-3, D-1, D-2:** dle návrhu.
- **Předané:** RecordPaymentHandler `filename` → `Application/Handler/`. Zastaralé `highlights` v kap. 07 a 09 oprav
  (zvýrazněné řádky musí ukazovat na řádky, které text zdůrazňuje – ověř `SCR/hl.php`). Kap. 18 kontrola duplicity
  přes `findByEmail()`: přiznat TOCTOU a odkázat na unique constraint (`/implementace-v-symfony#register-race-heading`,
  ověř kotvu). Kap. 11 `OrderFactory`: v textu to je Object Mother (třídu nepřejmenovávej, jen popis + zmínka, že
  nejde o Foundry factory). Kap. 16 `App\Product` → `App\Catalog`. Kap. 19 outbox přes Doctrine transport
  `events_out`: jedna věta vztahu k vlastní `OutboxMessage` tabulce kap. 15. Kap. 21.04 import `Email` z UserManagement:
  komentář, že jde o zkratku výřezu. Kap. 09 driving port `interface PlaceOrder` koliduje s command `PlaceOrder`
  → přejmenuj port podle Cockburnovy konvence (`ForPlacingOrders`) konzistentně v celé kap. 09.
- Nová i přepsaná próza: krátce, česky, bez kýče, podle CLAUDE.md. Kód minimálně; po změně
  `php scripts/lint-php-snippets.php <soubor>.md`. Kotvy `{#…}` nikdy neměnit (ověř diffem proti HEAD).
  Frontmatter kromě `deck`/`modified` neměnit (`modified: 2026-09-27` už je nastaven). Necommituj.

## Výstup
Report do `SCR/reports/fix-<skupina>.md`: které nálezy jsi opravil (ID + soubor#kotva + stručně jak),
které jsi vědomě neopravil a proč, a co zůstává na jiných souborech. V závěrečné odpovědi zkrácená verze.

---
<!-- reports/g1.md -->
# g1 – preface.md (00), what_is_ddd.md (01), subdomains.md (02), when_not_to_use_ddd.md (22)

## Opravené nepravdy / věcné chyby
- preface.md (úvod): Vernon v IDDD rozepsal DDD „na příkladech v Javě a C#“ → „v Javě“. Zdroj: popis knihy od Pearsonu/Goodreads („realistic Java examples – all applicable to C# developers“), repo IDDD_Samples (Java). Jde o jedinou změnu v referenčním úvodu.
- preface.md#styl-kodu: konvence „Zprávy pro command bus nesou sufix `Command` (`PlaceOrderCommand`)“ odporovala kanonickému rozhodnutí `PlaceOrder`/`PlaceOrderHandler` (i `RegisterUser` v kap. 10) → „Příkazy … bez sufixu (`PlaceOrder`, `RegisterUser`), obsluha sufix `Handler`“.
- preface.md#cast-4 + what_is_ddd.md#jak-cist: tvrzení „kapitoly [12–16] začínají rámcem ‚kdy ano a kdy ne‘“ neplatí (to má jen CQRS 12.x a ES 13.03, a ani jedna tím nezačíná) → „kapitoly proto rozebírají i to, kdy se nevyplatí“.
- what_is_ddd.md#implementation + #further-reading: licence DDD Starter Modelling Process „CC-BY“ → „CC BY-SA“ (README v repu ddd-crew: CC-BY-SA-4.0).
- what_is_ddd.md#definition: definice DDD je „v úvodu“ DDD Reference → „na začátku“ (ve skutečnosti otevírá část I „Putting the Model to Work“, ne úvod). Ověřeno v PDF 2015.
- what_is_ddd.md#history (2003): Evans zavedl „strategický/taktický design“ → „strategický design“ (Evans má Part IV „Strategic Design“ a „Building Blocks“; pojem „tactical design“ zpopularizoval až Vernon).
- what_is_ddd.md#tactical-design: „Aggregate … Je to nejtěžší rozhodnutí taktického DDD“ (agregát není rozhodnutí) → „Hranice agregátu je nejtěžší rozhodnutí“.
- what_is_ddd.md#ddd-vs-other: „čtyři přístupy, z nichž žádný není přímý konkurent“ proti obsahu (Transaction Script a CRUD jsou alternativy) → rozděleno: TS a CRUD = alternativy pro jednodušší domény, hexagonální architektura a mikroservisy = jiná vrstva, kombinují se s DDD.
- subdomains.md#symfony-implications: „Matthias Noback doporučuje právě ji: na první úrovni Bounded Context nebo subdoména“ → doloženo a zpřesněno: Noback v „Layers, ports & adapters – Part 2“ (2017) dává pod `src/` adresář pro každý Bounded Context, uvnitř vrstvy; o subdoménách nemluví (matthiasnoback.nl/2017/08/layers-ports-and-adapters-part-2-layers/). Tím vyřešena NEJISTÁ položka z minula.
- subdomains.md#evoluce / #shift-generic-to-core: rozpor „pohyb po Wardleyho ose jde jedním směrem“ × „Z Generic do Core“ (minulá NEJISTÁ) → ke Stripe doplněna věta, že nejde o pohyb proti ose (Stripe platby nekupoval jako komoditu, ale stavěl z nich nový produkt); „Tři posuny níže“ → „Posuny níže“ (sekcí jsou čtyři, včetně LLM).
- when_not_to_use_ddd.md#zdroje: Evans „Kapitoly 1–3 definují, kdy DDD aplikovat a kdy ne“ – nepravda (kap. 1–3 jsou Crunching Knowledge / Ubiquitous Language / Binding Model and Implementation) → „Kapitola 4 v pasáži o Smart UI přiznává, kdy je jednodušší přístup rozumnou volbou“ (ověřeno v obsahu knihy: kap. 4 Isolating the Domain, „SMART UI ANTI-PATTERN“, s. 57).
- Ověřeno bez změny: DDD Reference 2015 má číslované strany 1–52 (+ vii úvodních, 59 stran PDF) → „na 52 stranách“ platí, minulá NEJISTÁ vyřešena. *DDD in PHP* má na Leanpubu skutečně 2. vydání (aktualizace 6. 4. 2026) → minulá NEJISTÁ vyřešena, text sedí. ddd-crew Core Domain Charts: 9 otázek, osy (svislá komplexita, vodorovná diferenciace) i varianta Architecture Migration sedí. Nick Tune: 8 vzorů včetně všech jmenovaných sedí. EasyAdmin 5 existuje (v5.6.0, 17. 9. 2026, podporuje Symfony ^8.0). `auth0.authenticator` a `Auth0\Symfony\Security\UserProvider` odpovídají README auth0/symfony. Evansovo QCon 2009 (slajdy): stavební bloky „overemphasized“, Domain Events jako nový blok, Partners a Big Ball of Mud už tam – text sedí.

## Opravené rozpory uvnitř kapitoly
- what_is_ddd.md#bounded-context: příklad je Ordering × Support, ale věta „Jedna změna pro podporu rozbije fakturaci“ → „rozbije objednávky“.
- what_is_ddd.md callout #priklad-platba-heading: bod 3 a následný odstavec dvakrát říkaly „změna se odehraje jen v adaptéru a registru“ → v bodu 3 vypuštěno.
- subdomains.md: rod „lehký DDD“ / „plný taktický DDD“ × „plné DDD“, „lehké DDD“ v kap. 22 a jinde v knize → sjednoceno na střední rod (5 míst), pleonasmus „taktický DDD design“ → „taktický design“.
- subdomains.md strom `src/Core/Pricing/`: chyběly `Event/PricelistCreated.php` a `Exception/ConflictingPriceRuleException.php`, které ukázka `Pricelist` importuje → doplněny.
- subdomains.md#sourcing: „Třetí variantou sourcingu je partnerství“ hned za výčtem čtyř Evansových variant → „Vedle BUILD a BUY existuje třetí varianta“.
- when_not_to_use_ddd.md#short-lived: „Hranice ‚jeden rok‘ je orientační bod, ne absolutní mez“ a o odstavec níž „Rok je autorský odhad, ne měřená veličina“ → první věta vypuštěna.

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- Pojmenování příkazů: kanonické je `PlaceOrder`/`RegisterUser` bez sufixu (outbox_pattern.md:590, cqrs.md:1443, testing_ddd.md:1503, practical_examples.md:351, implementation_in_symfony `RegisterUser`), ale sufix `Command` používají authorization_in_ddd.md (15× `CancelOrderCommand` apod.), sagas.md (9×), event_storming.md (7× `PlaceOrderCommand`), ddd_pain_points.md (6×), architectural_styles.md (3×), event_sourcing.md (1×). Návrh: v kapitolách s `PlaceOrderCommand` přejmenovat na `PlaceOrder`. U `CancelOrderCommand`, `RefundOrderCommand` a dalších buď také bez sufixu, nebo jednou větou přiznat, že kapitola kvůli čitelnosti používá sufix. Předmluva teď popisuje variantu bez sufixu.
- Hub „Úvod a strategie DDD“ (templates/ddd/hub_basics.html.twig) × tag „Základy“ v Chapters.php: předmluva i #jak-cist používají štítky (Základy, Taktika, Architektura, Vzory, Praxe, Syntéza). Tituly hub stránek jsou jiné („Taktické modelování“, „Pokročilé vzory DDD“, „Praxe a provoz DDD“). Není to chyba, jen možná nejasnost. Neměnil jsem.
- Kap. 01 i předmluva přisuzují Partnership a Big Ball of Mud až *DDD Reference* (podle CLAUDE.md). Evansovy slajdy z QCon 2009 už oba vzory mají („Partners“, „Big Ball of Mud“). Formulace „Evans doplnil až v DDD Reference“ (what_is_ddd #strategic-design) je tedy mírně nepřesná, ale odpovídá kanonickému rozhodnutí z CLAUDE.md. Neměnil jsem, rozhodnutí je na autorovi.

## Jazyk a styl – souhrn
Zhruba 70 zásahů ve čtyřech souborech (diff +96/−98 řádků). Referenční úvody (what_is_ddd ř. 22–34, when_not ř. 21–29, preface ř. 21–23) jsou beze změny, výjimkou je jen věcná oprava Java/C#. Typické vzory:
- kalky: „napříč“ (7×) → „ve všech“, „pro všechna“, „v celém“, „celou knihou“; „legitimní“ (5×) → „přípustný“, „oprávněný“; „dává/dávají smysl“ → „kde to jde“, „se vyplatí“; „use cases/use casy“, „value objekty“, „events“ → „případy užití“, „hodnotové objekty“, „události“;
- meta a výplň: „A teď k definicím.“, „Při správném nasazení přináší DDD několik konkrétních věcí.“, „Tato kapitola proto učí filtrovat…“ – vypuštěno nebo sloučeno;
- fragmenty a staccato: „Strategie a velký obraz. Méně kódu, víc rozhodnutí.“, „Plus [autorizace…]“, „Detail v [kapitole…]“ → úplné věty;
- imperativ v soudech a shrnutí: „nezaměňujte je“, „re-evaluujte“, „zvažte“, „nedělte“, „kupte“ (v tabulce) → oznamovací tvar;
- gramatika: „dělí do osmi částí“ → „na osm“, „s Doctrine, Messenger a Symfony Form“ → „Messengerem a Symfony Formem“, „člověko-let“ → „člověkoroků“, „investovali“ (Stripe) → „investoval“, „po nástupu Zendesk“ → „Zendesku“, anglické uvozovky v komentáři kódu → „…“.

Ukázky před → po:
1. „Rychleji než čtení od první kapitoly se vyplatí vstoupit podle problému…“ → „Místo čtení od první kapitoly se vyplatí vstoupit do knihy u problému…“
2. „Záznam má nejpraktičtější podobu jako glosář v markdownu…“ → „Nejpraktičtější podobou záznamu je glosář v markdownu…“
3. „Tři kapitoly, každá z jiného úhlu, o tom, co se v DDD pokazí. **Kapitola 20** …“ → „Tři kapitoly se z různých úhlů dívají na to, co se v DDD pokazí. Kapitola 20 …“ (bez tučného)
4. „Modelování domény a budování Ubiquitous Language se na začátku projektu nevrací. Investice se vrátí až…“ → „… stojí čas hned na začátku projektu. Návratnost přichází až…“
5. „Doplňují se: DDD nabízí vzory pro doménové jádro, hexagonální architektura ho izoluje od infrastruktury.“ (opakovalo předchozí větu) → „Oba přístupy se proto doplňují.“
6. „Není to BUY (žádná krabice), není to BUILD (cizí tým), je to partnerství s rizikem…“ → „Od nákupu se partnerství liší tím, že nejde o krabicový produkt, od vlastního vývoje tím, že kód píše cizí tým. Nese riziko…“
7. „*„kupujeme Generic, nebo si snižujeme Core?“*“ → „*„kupujeme Generic, nebo outsourcujeme Core?“*“
8. „Systém … ukládá nebo reportuje. Nemá doménová pravidla ani invarianty. Přesouvá a transformuje data…“ → „… ukládá nebo z nich skládá reporty. Doménová pravidla ani invarianty nemá. Data přesouvá, doménu nemodeluje.“

## NEJISTÉ (neopraveno)
- preface.md (úvod): „*Domain-Driven Design in PHP* … vyšlo v roce 2017“. Leanpub verze existovala už 2016, Packt ji vydal 2017. Rok 2017 je obhajitelný, nechal jsem ho.
- preface.md (úvod): „Česky k tématu nevyšlo nic.“ Neověřitelné tvrzení.
- subdomains.md#tri-kategorie: Khononov jako příklady Core uvádí „ridesharing a matching jezdců u Uberu“ a „ranking u Googlu“. Příklady jsem proti textu knihy neověřoval.
- subdomains.md#custom-auth-warning-heading: „autentizace je Generic u 99 % organizací“ a „v tom 1 % případů“ jsou rétorická čísla bez zdroje. Obsah jsem zachoval.
- when_not_to_use_ddd.md#complexity-heuristics: Khononovovi se připisuje pět otázek, včetně otázky na cyklomatickou složitost. Proti knize neověřeno.
- when_not_to_use_ddd.md#migration-paradox-heading: „Fowler přidává podmínku vlastnictví. O obětování architektury rozhoduje tým, který ji napsal.“ Proti *Sacrificial Architecture* jsem to neověřoval.

Kontroly: lint-php-snippets (subdomains, when_not_to_use_ddd) 0 chyb; check_anchors OK; check_faq_yaml OK; check_tonality 0 nálezů v mých souborech; diff kotev prázdný u všech čtyř souborů. `modified: 2026-09-24` je u všech čtyř kapitol (u what_is_ddd nastaveno nově). Změny kódu: jen komentáře (when_not: „nemá chránit“ → „nechrání“, uvozovky) a doplnění dvou řádků do textového stromu v subdomains.

---
<!-- reports/g2.md -->
# g2 – context_mapping.md, team_topologies.md, event_storming.md

## Opravené nepravdy / věcné chyby
- team_topologies.md#inverse-real-world-heading – Bezosův mandát: „Žádné sdílené databáze, žádné funkční volání napříč týmy, žádné neformální komunikační kanály“ → „Žádné přímé linkování, žádné čtení z úložiště dat jiného týmu, žádná zadní vrátka“; „výhradně přes API“ → „výhradně přes rozhraní služeb“. Yegge (bod 3) mluví o meziprocesové komunikaci, ne o neformálních lidských kanálech. Zdroj: gist.github.com/chitchcock/1281611, body 1–3 mandátu.
- context_mapping.md#conformist – „U integrace s proprietárním dodavatelem je bilance jednoznačně záporná“ odporovalo seznamu „Kdy Conformist zvolit → Externí dodavatel (Stripe…)“ a příkladu Reporting → Stripe. Nově: bilance vychází záporně, „jakmile na převzatém modelu stojí vlastní doménová logika“. To odpovídá warn calloutu hned pod ním.
- event_storming.md#design-level + #tdd-events – `PlaceOrderCommand` → `PlaceOrder` (kanonické jméno podle CLAUDE.md a revize: `PlaceOrder`/`PlaceOrderHandler`, stejně jako v outbox_pattern.md:590). Pro jednotnost s konvencí bez přípony také `ChargeCardCommand` → `ChargeCard`. Lint prošel.
- event_storming.md#tdd-mapping – „Inv-4: confirm vyžaduje, aby payment byl Settled“ → „aby platba prošla“ (zbytek po dřívějším `PaymentSettled`; kniha teď používá `PaymentSucceeded`).
- team_topologies.md FAQ „jediný tým“ – „Heroku/Vercel/…“ → „Upsun/Heroku/…“. Scénář A už Vercel dříve vyřadil (PHP nativně nepodporuje) a FAQ s ním nesouhlasilo.
- event_storming.md#ds-priklad – „Krok 3 se kreslí dvěma šipkami“: stejný tvar actor → work object → actor mají i kroky 6 a 7 → „Kroky 3, 6 a 7“.
- team_topologies.md#inverse-checklist bod 6 – ve větě chybělo podstatné jméno („V patologické či byrokratické reorganizace formálně proběhne“) → „V patologické či byrokratické kultuře reorganizace…“.

## Opravené rozpory uvnitř kapitoly
- team_topologies.md#management-pitch-heading – bod 3 pitche sliboval „change failure rate na třetinu současné hodnoty“, ale #dora-metriky o dva odstavce výš říká, že slibovat CTO konkrétní procento zlepšení je chyba. Pitch teď slibuje baseline, druhé měření po 6 měsících, směr změny a rozhodnutí podle čísel.
- event_storming.md#notace – „Každá barva má jeden význam“ × o dva odstavce níž „Zelená má ve dvou formátech dva různé významy“ → „Uvnitř jednoho workshopu má každá barva jediný význam“.
- event_storming.md#bp-online bod 4 – nadpis „Breakout místnosti pro dvě fáze“, text ale jmenuje jen jednu fázi → „Breakout místnosti.“
- event_storming.md#anti-vzory – úvod sliboval kanonická jména Brandoliniho vzorů „v závorce“, callouty je ale uvádějí v textu → „je uvedeno kanonické jméno“.
- team_topologies.md – Conway's Law se skloňoval v obou rodech („dostala“, „Obejít ji“ × „zareaguje“) → všude mužský rod (zákon).
- team_topologies.md#scenare-summary-heading a #summary – poměr 6:1 až 9:1 s výhradou „tip, ne měření“ stál ve stejném znění potřetí → v calloutu a ve shrnutí zkráceno na odkaz do 05.03 / 05.07.

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- **Command `PlaceOrder`, tvar:** event_storming.md (#dl-mapping) má po přejmenování `PlaceOrder(CustomerId $customerId, list<OrderItemDto> $items)`, kdežto outbox_pattern.md:590 má `PlaceOrder(string $customerId, array{productId, quantity, unitPriceInCents} $items)` s validačními atributy. Návrh: ponechat. Kap. 04 výslovně označuje kód jako „náčrt, ne finální kód“. Případně do věty pod kódem doplnit „kanonický tvar viz Outbox“.
- **Přípona `…Command` jinde v knize:** ddd_pain_points.md:944–1040 (`PlaceOrderCommand`), architectural_styles.md:267 (strom `PlaceOrderCommand.php`), authorization_in_ddd.md:808 (`CancelOrderCommand`). Návrh: sjednotit na `PlaceOrder` / `CancelOrder`.
- **Inv-4 × kanonický životní cyklus:** event_storming.md#tdd-mapping „confirm vyžaduje, aby platba prošla (hot spot)“ × kanonické `OrderStatus` Draft → Confirmed → Paid (platba až po potvrzení). Jako otevřený hot spot z workshopu obstojí a testy ho neimplementují. Návrh: nechat, nebo formulovat jako otázku („má confirm čekat na platbu?“).
- **Deck ve frontmatteru** (podle zadání needitován):
  - team_topologies – „popisoval gravitační zákon softwarového designu“ a Conwayova teze v uvozovkách jako volná parafráze. Návrh: „Conway v roce 1968 popsal, že systémy kopírují komunikační strukturu organizace, která je navrhla. Bounded Contexty fungují jen tehdy, když odpovídají týmům…“
  - event_storming – „Před první řádkou kódu byste měli odejít od počítače.“ (imperativ/kýč) a „Průvodce, který v Symfony projektu funguje.“
  - context_mapping – „Bounded Context vám definuje hranici. Context Mapping vám definuje…“ (paralelismus, kalk). Návrh: „Bounded Context určuje hranici, Context Mapping to, co se na ní děje.“

## Jazyk a styl – souhrn
Zhruba 230 zásahů (team_topologies ~87, event_storming ~77, context_mapping ~62). Typické vzory:
- kýč a metafory: „gravitační pole“, „gravitační zákon“, „Násobitelů nemá být víc…“, „teleobjektiv“, „Ztratit jazyk = ztratit slovník“;
- staccato a řečnické otázky: „Oddělený DBA tým? V kódu se objeví…“, „Ne X. Y.“;
- zdvojené parafráze: „V překladu: … Volněji: …“, opakovaný poměr 6:1–9:1, dvojí „30 minut jednou za kvartál“, dvojí „Bez politiky verzování…“;
- meta-komentáře: „Kapitola popisuje…“, „Tato kapitola prochází…“, „Předchozí sekce popisuje… Tato se věnuje…“, „Následují nejčastější anti-vzory…“;
- anglicismy a kalky: per BC, re-org, delivery, featury, split, draft, scope, walkthrough, early-stage, versioning, reviewovat, raw DTO, cross-team, „modelů ve hře“, „reportovací linky“, „legitimní“, „explicitní/napříč“ v nadbytku;
- gramatika: rod (Partnership, Conway's Law, „Mělčí, ale široký | Hluboká, úzká“, „jeden monorepo“), „rozvedl o“, chybějící „se“ („ukáže, že stalo“), chybějící čárka („neřekl jakým způsobem“), „z vzorů“, „k vzoru“, „papírového rolu“, „<starý-datum>“, časová souslednost u re-stormingu.

Ukázky (před → po):
1. „Kapitola popisuje toto *gravitační pole*: Conway's Law z roku 1968 a Team Topologies … jako rámec…“ → „Popsal to Conway už v roce 1968; Team Topologies … k tomu dodávají rámec pro vědomý návrh týmů.“
2. „V překladu: **organizace navrhující systémy jsou nuceny vytvářet designy…** Volněji: design systému kopíruje komunikační strukturu organizace. … Oddělený DBA tým? V kódu se objeví vrstva…“ → „Česky: organizace, které navrhují systémy, jsou nuceny vytvářet návrhy kopírující jejich komunikační strukturu. Conway nic nepředepisuje, popisuje pozorování: …“ (anglická citace ponechána doslovně).
3. „Eric Evans Context Map vymezuje jako přehled všech modelů ve hře. Každý model na projektu dostane jméno…“ → „Eric Evans vymezuje Context Map jako přehled všech modelů na projektu. Každý dostane jméno…“
4. „ACL dostává v této kapitole nejvíc prostoru a to svádí brát ho jako výchozí volbu. Výchozí volbou není. Vrstva má svou cenu a ve třech situacích se nevrátí.“ → „ACL dostává v této kapitole nejvíc prostoru, což svádí brát ho jako výchozí volbu. Tou ale není: vrstva něco stojí a ve třech situacích se investice do ní nevrátí.“
5. „Bezos nepředepsal architekturu. Předepsal **komunikační režim týmů**.“ → „Bezos nepředepsal architekturu, ale **způsob, jakým spolu týmy smějí komunikovat**.“
6. „Všichni dostanou stejně oranžových stickies (~15 každý) a píší události, které je napadnou.“ → „Každý dostane zhruba 15 oranžových stickies a píše události, které ho napadnou.“
7. „Předchozí sekce popisuje, jak workshop pokazí lidé. Tato se věnuje tomu, co technika neumí…“ → „Anti-vzory výše způsobují lidé. Následující limity má technika sama, i když se vede správně.“
8. „Doménovou hodnotu nesou stream-aligned týmy, ostatní ji jen násobí. Násobitelů nemá být víc než těch, kdo hodnotu vytvářejí.“ → pointa škrtnuta, první věta zůstala.

## NEJISTÉ (neopraveno)
- Dořešeno z minulé revize: context_mapping.md#ohs „Časový bod v Sunset nesmí předcházet ten v Deprecation“ je **správně**. RFC 9745 píše „MUST NOT be earlier“ (ověřeno na rfc-editor.org). Ostatní NEJISTÉ z minulé revize už v textu nejsou (Separate Ways, `getPaymentsInMonth`, `jane-php/open-api-3`, Evans a UL, počet týmů ve scénáři C).
- Ověřeno bez změny: 2. vydání Team Topologies vyšlo 23. 9. 2025 (IT Revolution). Conwayova zmínka o vojensky řízených organizacích a homomorfismu/větvích odpovídá eseji (melconway.com).
- team_topologies.md#platform-team / #cognitive-load-rubric – tvrzení, že veřejná šablona *Team Cognitive Load Assessment* „znění otázek neobsahuje“, a model „s více než dvaceti faktory ve čtyřech skupinách“ (Weis, 2. vyd.) – neověřeno.
- team_topologies.md#inverse-conway-limity – parafráze Fowlera („reorganizace neopraví zabetonovanou architekturu, přesune jen lidi kolem ní“) – bliki jsem proti ní neověřoval.
- event_storming.md#ds-scope – pořadí „jemný AS-IS pure → hrubý AS-IS pure → jemný TO-BE digitalized“ působí obráceně (Hofer & Schwentner obvykle začínají hrubým přehledem). Neověřeno, nechávám.
- context_mapping.md#published-language – `OrderPlacedValidator` leží v `App\Ordering\Infrastructure` (producent), text ho ale popisuje jako „první krok ACL na konzumující straně“. Prózu jsem upravil na „Na straně konzumenta je validace… první krok ACL“. Umístění třídy v kódu se nezměnilo.

## Kontroly
- `php scripts/lint-php-snippets.php context_mapping.md event_storming.md team_topologies.md` → 12 bloků, 0 chyb
- `php scripts/check_anchors.php` → OK; `check_faq_yaml.php` → OK; `check_tonality.php` → 0 nálezů ve všech třech
- diff kotev proti HEAD prázdný u všech tří souborů
- `modified: 2026-09-24` už v HEAD stálo u všech tří kapitol, beze změny
- git diff --stat: context_mapping 130 řádků, event_storming 150, team_topologies 307 (+285/−302)

---
<!-- reports/g3.md -->
# g3 – basic_concepts.md (06), aggregate_design.md (07), lesser_known_patterns.md (08)

Kanonické API (AggregateRoot, record/releaseEvents, Email, Money, Currency, Order, OrderItem, OrderPlaced, OrderItemAdded, ID) NEBYLO změněno. Kotvy beze změny (diff prázdný u všech tří), lint PHP bloků 15/11/21, 0 chyb. `modified: 2026-09-24` už bylo nastavené.

ZMĚNY KÓDU (všechny mimo kanonické třídy):
- aggregate_design#symfony-doctrine – blok `Money.php + ShippingAddress.php + OrderItem.php`: do sekce `namespace App\Ordering\Domain\ValueObject;` doplněno `use Doctrine\ORM\Mapping as ORM;`. Bez něj se `#[ORM\Embeddable]`/`#[ORM\Column]` na `ShippingAddress` rozlišily na `App\Ordering\Domain\ValueObject\ORM\…` (lint to nechytí, Doctrine ano). Blok nemá highlights.
- aggregate_design#transactional-consistency – komentář v ANTI-VZOR handleru: „Doctrine flush() commitne obojí atomicky“ → „wrapInTransaction() provede flush a commitne obojí atomicky“ (flush ≠ commit). Počet řádků beze změny.
- lesser_known_patterns#mod-bc – strom adresářů: `Application/CommandHandler/` a `QueryHandler/` → `Application/Handler/PlaceOrderHandler.php` a `Query/ListOrders.php + ListOrdersHandler.php` (shoda se stromem kap. 10 a s `App\Ordering\Application\Handler` v kap. 06, 11, 04, 15). Totéž v #ds-tip-heading (`Application/CommandHandler/` → `Application/Handler/`).

## Opravené nepravdy / věcné chyby
- aggregate_design#vernon-rules – „Khononov dodává páté vodítko, které z Vernonových implicitně plyne: jedna transakce mění jeden agregát“ → Vernon to v Part I píše výslovně („a properly designed bounded context modifies only one aggregate instance per transaction in all cases“); Khononov z toho dělá samostatné pravidlo. Ověřeno v PDF Vernon_2011_1 (dddcommunity.org).
- aggregate_design#vernon-rules – „Vernon je nenazývá pravidly, ale rules of thumb … ke každému sám uvádí situace, kdy se poruší … doporučuje aplikovat v tomto pořadí“ → Vernon používá obojí („Reasons To Break the Rules“, „Adhering to the Rules“ i „rules of thumb“); důvody k porušení jsou jedna společná sekce v Part II, ne u každého vodítka; žádné „doporučené pořadí“ – v Part III je jen shrnuje do čtyř bodů. Ověřeno v PDF Part I–III.
- aggregate_design#vernon-rules (NEJISTÉ z minula) – „Dovětek o čí práci přidal ve třetím dílu“ → upřesněno: otázku rozebírá už Part II (sekce „Ask Whose Job It Is“, od Evanse), do formulace pravidla ji připisuje Part III („(after asking whose job it is)“). Ověřeno v PDF.
- aggregate_design#references-by-id (NEJISTÉ z minula) – „Ve třetím dílu jeho tým kvůli režii dotazů zvolí přímou lazy-loaded referenci“ → Part II uvádí výkon dotazů jako Reason Four; Part III tým jen *zvažuje* vyčlenit objemný text příběhu do samostatného agregátu s přímou lazy referencí („Perhaps that makes sense“), důvodem je paměť, ne dotazy. Ověřeno v PDF.
- aggregate_design#references-by-id – Evansova výjimka pro vnitřní člen: „bez uchování a bez zápisu“ → „jen přechodně, pro jedinou operaci; volající si ji nesmí ponechat“ („bez zápisu“ Evans neříká).
- aggregate_design#breaking-the-rule (+FAQ) – „čtyři situace, ve kterých tým commitne víc agregátů najednou“ → první tři se týkají commitu více agregátů, čtvrtá (výkon dotazů) reference přes identitu. Ověřeno v PDF Part II.
- aggregate_design#doctrine-limits – „Obejít to lze třemi způsoby … explicitní `$em->lock($order, LockMode::OPTIMISTIC, $expectedVersion)`“ → dvěma (dotknout se pole na kořeni, pesimistický zámek). `lock(OPTIMISTIC)` jen porovná verzi a nezvedá ji, takže dva souběžné požadavky se stejnou verzí projdou oba. Ověřeno ve zdrojáku `UnitOfWork::lock()` (doctrine/orm HEAD).
- aggregate_design#doctrine-limits – „`flush()` commitne všechny špinavé entity“ → „zapíše všechny změněné entity“ (pod doctrine_transaction flush ≠ commit, kanonické rozhodnutí).
- aggregate_design#large-collection – „každé přidání položky způsobí flush všech úkolů“ → „každý flush prochází změny u všech načtených úkolů“ (Doctrine zapisuje jen změněné řádky, change set ale počítá pro všechny).
- basic_concepts#entity-identity – „Sdílený předek by dovolil předat `ProductId` tam, kde se čeká `CustomerId`“ (signatura typovaná na `CustomerId` potomka `ProductId` odmítne) → skutečný důvod: zděděné `equals(self $other)` přijme kteréhokoli potomka, stejně tak každá signatura typovaná na předka.
- basic_concepts#readonly-cost-heading – „readonly přijme hodnotu jen z rozsahu deklarující třídy“ → „zevnitř třídy (od PHP 8.4 i z potomka)“. Zdroj: php.net manuál Properties („As of PHP 8.4.0, readonly properties are implicitly protected(set)“).
- basic_concepts#entity-identity – Symfony UID: „doporučuje kvůli lepší entropii a chronologickému řazení“ → „doporučuje místo verzí 1 a 6 kvůli lepší entropii a přísnějšímu chronologickému řazení“ (doslovně podle symfony.com/doc/current/components/uid.html).
- basic_concepts#aggregates – „Verze z Návrhu agregátu má stejné metody“ → nemá (přidává `markPaid()`, `ship()`, `lockForSaga()`; nemá `removeItem()`, `itemCount()` aj.) → „Přidává další stavové přechody…“.
- basic_concepts#domain-services – „Evans mluví o službě jako o samostatném rozhraní“ (čteno jako PHP interface) → Evans: operace nabízená modelem jako samostatné rozhraní; ukázka z ní nedělá PHP `interface`. Ověřeno v DDD Reference (Services).
- basic_concepts#aggregate-root-lifecycle – DispatchAfterCurrentBusStamp „po skončení handleru“ → „po úspěšném dokončení“ (při výjimce se zpráva nepošle; symfony.com/doc messenger/dispatch_after_current_bus).
- lesser_known_patterns – Evans & Fowler *Specifications* nazýváno „pracovní papír / papír“ (kalk „paper“) → „článek“ (6×, vč. nadpisu #spec-subsumption, kotva beze změny).
- Ověřeno bez změny: Hedhman 70/30 i „Za univerzální poměr to Vernon nepovažuje“ (Part I), výpočet 144/25 objektů a 30–60 minut (Part III), „nehledají se výmluvy“ na konci Part II, generous seconds…days (Part II), DDD Reference: Entities, Value Objects, Domain Events (timestamp + identity), Factories („may itself have no responsibility in the domain model but is still part of the domain design“), Modules, absence Specification v DDD Reference, `AbstractPlatform::columnsEqual()` v DBAL 4.4, `Criteria::expr()` metody a striktní `===` v ClosureExpressionVisitor, Gomez 7. 2. 2015.

## Opravené rozpory uvnitř kapitoly
- aggregate_design#aggregate-size / #checklist bod 4 × kanonický `Order::addItem()`: pravidlo „doménová metoda se při změně potomka dotkne pole na kořeni“, kód to nedělá. Kód nezměněn; pod výpisem v 07.07 doplněny 3 věty: ukázka z pravidla vědomě slevuje, obhajobou je user-aggregate affinity (položky se mění jen v Draftu, pracuje s nimi jeden zákazník); u víc souběžných editorů se metoda musí dotknout kořene. V 07.04 „se dotkne“ → „musí dotknout“.
- basic_concepts#domain-events × FAQ: FAQ tvrdilo, že doménové události jsou základ komunikace mezi Bounded Contexty; kapitola říká, že doménová událost zůstává uvnitř → FAQ: „mezi kontexty putují až po překladu na integrační události“.
- lesser_known_patterns#antivzory – náprava „Cross-BC import bez ACL: integrace přes domain events“ → „přes integrační události (Outbox)“ (soulad s kap. 06 a 15).
- basic_concepts#aggregate-root-lifecycle – `OrderItemAdded` se používal bez vysvětlení → „`OrderItemAdded` a `OrderConfirmed` jsou obdoby `OrderPlaced`; definice v [Návrhu agregátu](/navrh-agregatu#references-by-id)“ (kotva ověřena).
- basic_concepts#repositories – tvrzení „dotazy pro obrazovky sem nepatří (CQRS)“ stálo dvakrát → sloučeno do bodu 3 seznamu.

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- implementation_in_symfony.md (~ř. 1333, `filename="src/Ordering/Application/Command/RecordPaymentHandler.php"`) – cesta v `filename` říká `Command/`, ale `namespace` uvnitř bloku je `App\Ordering\Application\Handler` (a strom téže kapitoly i kap. 06/11/15 mají handlery v `Handler/`). Návrh: filename → `src/Ordering/Application/Handler/RecordPaymentHandler.php`.
- Repozitář ukázek ddd-symfony-examples (Chapter03_BasicConcepts, Chapter12_LesserPatterns): pokud kopíruje strom z kap. 08, obsahuje `Application/CommandHandler/` – sjednotit na `Application/Handler/`. Pokud Chapter02 obsahuje `ShippingAddress` bez `use Doctrine\ORM\Mapping as ORM;`, stejná oprava.
- Jinak jsem nenašel nový rozpor: volání `new OrderConfirmed/OrderShipped/OrderCancelled/OrderPaid(...)` v kap. 04, 11, 12, 14, 20, 21, 22 sedí se signaturami z kap. 07; výjimky a továrny z kap. 10 (`cannotConfirm`, `cannotBePlaced`, `notAllowedInState`, `cannotTransition`, `withId`) sedí; `placeWithItems(CustomerId, array)` v kap. 15 sedí s odkazem v kap. 08; všechny interní odkazy a kotvy ze tří kapitol existují.

## Jazyk a styl – souhrn
Asi 75 zásahů (06 ≈ 30, 07 ≈ 25, 08 ≈ 20). Diff: 06 130 řádků, 07 142, 08 82; délka próz se zkrátila jen mírně, protože kapitoly už po minulé revizi byly hutné.
Typické vzory: „napříč“ (6× → „více agregátů“, „opakovaně“), „legitimní“ (3×), „explicitní/explicitně“ (4×), meta-věty („Tato kapitola/sekce…“, callout „Co od kapitoly očekávat“ o větu kratší), opakování téže myšlenky (věta pod ukázkou `User`, dvojí CQRS v repozitářích, dvojí „in-memory kolekce“ ve FAQ), imperativ v soudech → oznamovací věta („používejte/mapujte“ v pravidlech Doctrine, „řešte v projekci“, „hranici prověřte“), „my“ autora („doporučujeme“ → „kniha doporučuje“), „Síla vzoru“, kalky („pracovní papír“, „vlastnit“, „dodání funkce“, „nit kontinuity napříč životním cyklem“), rod („žádný interní cache“ → „žádná“), slovosled („že událost se“ → „že se událost“).

Ukázky před → po:
- „Evans … mluví o objektech, jež drží nit kontinuity a identity napříč celým životním cyklem.“ → „Evans v *DDD Reference* píše, že takový objekt definuje nepřerušená kontinuita a identita, ne jeho atributy.“
- „Hodnotový objekt je doménový pojem, který identifikuje sám sebe celou svou hodnotou, ne odděleným ID.“ → „Hodnotový objekt nemá identitu, záleží jen na jeho atributech.“
- „Bez middlewaru … hrozí opak: dispatch po flushi o událost přijde, když proces spadne mezi uložením a publikací.“ → „…hrozí opak: když proces spadne mezi uložením a publikací, událost se ztratí.“
- „Pokud uživatel zadá objednávku a čeká stránku „Objednávka přijata“, nesmí ji vidět dříve, než ji vidí read model.“ → „Uživatel, který odešle objednávku, čeká stránku „Objednávka přijata“. Ta se nesmí vykreslit z read modelu, do kterého objednávka ještě nedorazila.“
- „Kde vede hranice agregátu, tam později vede i hranice shardu nebo služby.“ → „Hranice agregátu tak předurčuje i pozdější hranici shardu nebo služby.“
- „Doména je explicitně připravena na chvilkovou nekonzistenci.“ → „Doména s chvilkovou nekonzistencí počítá.“
- „Síla vzoru je ve skládání…“ → „Hodnotu vzoru přináší skládání…“
- „Většinu objektů jde přímočaře vytvořit konstruktorem. Factory má smysl teprve tehdy, když konstruktor začne být nepřehledný:“ → „Factory se nevyplatí tam, kde konstruktor zůstává přehledný:“

## NEJISTÉ (neopraveno)
- aggregate_design – atribut `highlights` v blocích míří o řádek vedle (markup jsem podle zadání neměnil). Počítáno od `<?php` = 1, blok `Order.php` v 07.07 sedí přesně, takže konvence je ověřená. Návrh:
  - `TransferMoneyHandler.php (ANTI-VZOR)`: `22,23,24,25,26` → `21,22,24,25` (načtení obou účtů + withdraw/deposit; teď svítí prázdné řádky 23 a 26),
  - `Order.php (mapování)`: `32…45` → `31,32,33,34,35,36,37,39,40,42,43,44` (OneToMany až version; teď svítí prázdné 38, 41, 45 a chybí `#[ORM\OneToMany(` a `#[ORM\Version]`),
  - `DoctrineOrderRepository.php`: `34…40` → `33,34,35,36,37,40` (persist + komentáře; teď svítí `}` a prázdný řádek),
  - `InitiateTransferHandler.php`: `21…25` zahrnuje prázdný řádek 25; nejspíš `21,22,23,24,26`, pokud má svítit i `save()` (ověřit záměr).
- basic_concepts – FAQ otázky mají velká písmena u pojmů („Entitou“, „Doménovou službu“, „Repozitář“) na rozdíl od prózy. Nechal jsem je, protože jsou součástí FAQ schema.org a mohou být zvolené kvůli SEO.
- lesser_known_patterns#ds-priklad – Khorikovovo rozlišení *pure/impure* doménové služby jsem proti zdroji neověřil (tvrzení je bez odkazu).
- lesser_known_patterns#mod-kontrakt – parafráze Grzybkových tří vlastností modulu je bez odkazu; obsahově sedí s jeho článkem *Modular Monolith: Primer*, doslova neověřeno.

---
<!-- reports/g4.md -->
# g4 – architectural_styles.md (09), implementation_in_symfony.md (10)

## Opravené nepravdy / věcné chyby
- architectural_styles.md#layered – „Evans schéma [Fowlerovo] upravil na čtyři vrstvy. Přidal pravidlo, že vrstva smí záviset jen na vrstvách pod sebou“: to naznačovalo, že Evans odvozoval od Fowlera a pravidlo vymyslel. Obě knihy vznikaly souběžně a pravidlo vrstev je starší. Nově zní: „Evans pracuje se čtyřmi vrstvami … a trvá na pravidle“.
- architectural_styles.md#hexagonal-priklad-heading – „[doménový model] lze serializovat do JSON Event Storu beze změny tvaru“ → vypuštěno. Event Store ukládá události, ne agregát, a tvrzení s Persisted Object Pattern nesouvisí. Opraveno i „bez jediné Doctrine anotace“ → „atributu“ (ORM 3 anotace nemá, předchozí kolo tento výskyt přehlédlo).
- architectural_styles.md#hexagonal-symfony-heading (strom) – komentář `OrderOrmEntity.php # Mapper na databázi` byl chybný, mapper je `OrderMapper`. Ten ve stromu chyběl, přestože ho kód adaptéru používá. Doplněn `OrderMapper.php` a komentář opraven na „Doctrine entita pro persistenci“.
- architectural_styles.md#hexagonal-symfony-di-heading – automatický alias vzniká, „pokud kontejner mezi načtenými službami najde právě jednu implementaci“. Správně: „pokud při načítání `services.yaml` objeví rozhraní i právě jednu jeho implementaci“. Ověřeno ve `vendor/symfony/dependency-injection/Loader/FileLoader.php` (`singlyImplemented` se sbírá a vyhodnocuje po jednotlivých konfiguračních souborech, viz YamlFileLoader::load → registerAliasesForSinglyImplementedInterfaces).
- architectural_styles.md#onion – „Onion podle něj nezávisí na DDD, na CQRS ani na IoC kontejneru“ → zpřesněno podle Palermova textu: funguje s DDD vzory i bez nich, s CQRS i s formuláři nad daty a obejde se bez IoC kontejneru. Zdroj: jeffreypalermo.com, Part 4.
- architectural_styles.md#symfony-messenger-heading – výřez `messenger.yaml` měl `query.bus: ~ # výchozí middleware stačí`, přestože se odvolává na „plnou konfiguraci v kapitole o CQRS“, kde má `query.bus` middleware `validation` a `event.bus` má `enabled: true`. Výřez je sjednocený s cqrs.md. Věta „odhalí překlep v názvu dotazu“ byla nepřesná (překlep v názvu třídy shodí PHP) → „dotaz bez handleru se prozradí hned při dispatchi“.
- implementation_in_symfony.md#repositories (komentář v `DoctrineUserRepository::findById`) – „custom typ … na string vrátí null a dotaz pak tiše nenajde nic“ bylo v rozporu s `UserIdType` v téže kapitole, který na řetězec vyhodí `InvalidType`. Komentář teď odpovídá kódu.
- implementation_in_symfony.md#doctrine-custom-types – „`UserIdType` … se liší jen typem a délkou“. To neplatí: `EmailType` řetězec toleruje, `UserIdType` na něj hlasitě selže, a právě to je pointa výpisu. Přeformulováno.
- implementation_in_symfony.md#persisted-object-pattern – Noback podle textu „dodává, že většina projektů, které se toho dovolávají, to nepotřebuje“. Článek říká jen tolik, že v projektech, které sám viděl, taková potřeba nebyla (a uznaný scénář je „evolve the design of your aggregates without evolving the database schema“, ne „hranice agregátu“). Opraveno. Citát „expensive and unnecessary form of decoupling“ je doslovný (ověřeno na matthiasnoback.nl) a Khorikovovy argumenty také (enterprisecraftsmanship.com, 2016).
- implementation_in_symfony.md#persisted-object-pattern – „dva jednosměrné mappery“ × kód s jednou třídou `UserMapper` o dvou metodách → „mapper … překládá oběma směry“. Obdobně „v mapperech“ → „v mapperu“.
- implementation_in_symfony.md#validace-kde-heading – „Symfony Validator na ní [doménové validaci] nesmí záviset“: směr závislosti byl obráceně → „[doménová validace] na Symfony Validatoru nesmí záviset“.
- implementation_in_symfony.md#workflow-note-heading – „Enum s `match` drží pravidla tam, kde je vynucuje typový systém“ → pravidla vynucuje agregát, ne typový systém (obdoba opravy z 1. kola v #payment-aggregate-heading).
- implementation_in_symfony.md#dispatcher-vs-messenger-heading – transport „Doctrine outbox“ → „databáze přes Doctrine“. Messenger má Doctrine transport; ten sám o sobě outbox není.
- implementation_in_symfony.md#autowiring-bounded-contexts – „Třída v adresáři, který ve výčtu chybí, … chybu ohlásí až běh, ne `lint:container`“ platí jen pro třídy, na kterých nic nezávisí (handler, kontroler). Chybějící závislost jiné služby shodí kompilaci. Zpřesněno.
- implementation_in_symfony.md#dependency-injection-example-heading (komentář v YAML) – „DoctrineUserRepository zaregistruje Symfony přes autowiring“ → registraci provádí blok `App\:` (resource), ne autowiring.
- implementation_in_symfony.md#shared-folder-heading – callout radil dát do SharedKernel „abstraktní třídy pro ID“ a tvrdil, že hodnotové objekty do SharedKernel nepatří. Obojí je v rozporu s touto kapitolou: #project-structure uvádí „Identifikátory nemají sdíleného předka“ a strom má `Money`/`Currency` v `SharedKernel/Domain/`. Výčet je přepsaný (AggregateRoot, sdílené VO Money/Currency, utility, obecné výjimky, infrastruktura) a identifikátory výslovně patří do kontextu.
- implementation_in_symfony.md#domain-services (`recordPayment`) – holá `\DomainException` bez přiznání (CLAUDE.md: jen jako přiznaná zkratka) → doplněn komentář „Přiznaná zkratka“. Kód jinak beze změny.
- implementation_in_symfony.md#payment-handler-heading – „`Order` publikuje `PaymentRecorded`“ → „zaznamená“ (agregát nepublikuje; sjednoceno s opravou z 1. kola v #domain-events).
- implementation_in_symfony.md#persisted-object-example-heading (komentář v `reconstitute()`) – „bez vyhazování doménových událostí“ → „žádnou doménovou událost nezaznamená“ (vyhazují se výjimky).

### Změny kódu (všechny prošly `lint-php-snippets`: 09 = 18 bloků, 10 = 31 bloků, 0 chyb)
- 09 `CalculateCartPrice`: `$this->carts->get(...) ?? throw`, `$this->customers->get(...) ?? throw` → `find(...)`. V celé knize `get()` vrací nenulový agregát a sám hází `*NotFoundException` (viz `OrderRepository::get()` v téže kapitole). `?? throw` za ním byl mrtvý kód.
- 09 `PlaceOrderUseCase`: `$this->customers->get($request->customerId) ?? throw` → `find(CustomerId::fromString($request->customerId)) ?? throw`, přidán `use …\ValueObject\CustomerId`. Port teď bere VO jako ostatní porty (sjednoceno s `findByCustomer(CustomerId)` z 1. kola). Atribut `highlights` tohoto bloku byl zastaralý (zvýrazňoval `use ProductId`, `) {` a `$item['quantity']`). Nově `29,30,51,52,53` = načtení zákazníka a publikace událostí. Jde o jedinou změnu markupu, dělanou kvůli posunu řádků.
- 09 strom hybridního e-shopu: `PlaceOrderCommand.php`/`CancelOrderCommand.php` → `PlaceOrder.php`/`CancelOrder.php` (kanonické pojmenování commandu bez přípony). Callout #clean-pattern-heading: „Request přejmenovat na `*Command`“ → „z Requestu udělat command (`PlaceOrderRequest` → `PlaceOrder`)“.
- 09 `messenger.yaml`: viz výše (query.bus + `enabled: true`), anglické uvozovky v komentáři → „…“.
- 10 `RecordPaymentHandler`: namespace a cesta `Application/Command/` → `Application/Handler/`, přidán `use App\Ordering\Application\Command\RecordPayment;`. Strom kapitoly i zbytek knihy (authorization, outbox, event_storming) dávají handlery do `Application/Handler/`.
- 10 komentáře: `HashedPassword` „stejně jako UserName o kus výš“ → „o kus níž“ (`UserName` je až za ním), `findById`, `reconstitute`, `services.yaml` (viz výše), „taky“ → „také“.

## Opravené rozpory uvnitř kapitoly
- 09: `get()` s `?? throw` (Onion, Clean) × `get()` hází výjimku (Hexagonal port) – sjednoceno na `find()`.
- 09: Vertical Slice – „aplikační, prezentační a infrastrukturní logika se dělí per feature“ × strom se sdíleným `{BC}/Infrastructure/` a text o pár odstavců níž („část infrastrukturní vrstvy“) → „aplikační a prezentační logika a část infrastruktury“.
- 09: strom Hexagonal bez `OrderMapper` × kód adaptéru s `OrderMapper` (doplněno).
- 10: `findById` komentář × `UserIdType`; „liší se jen typem a délkou“ × obsah výpisu; `HashedPassword` „o kus výš“; SharedKernel callout × strom a #project-structure; `RecordPaymentHandler` namespace × strom; „dva mappery“ × jedna třída.

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- **Pojmenování commandů s příponou `Command`.** ddd_pain_points.md:984 `final readonly class PlaceOrderCommand`, event_storming.md:~245 `PlaceOrderCommand`, authorization_in_ddd.md:803 `src/Ordering/Application/Command/CancelOrderCommand.php` × kanonický `PlaceOrder`/`PlaceOrderHandler` (outbox_pattern.md:586, cqrs, kap. 09 a 10). Návrh: `PlaceOrder`, `CancelOrder`. U event_storming jde o „draft“, tam může přípona zůstat s poznámkou.
- **EventDispatcher vs. Messenger pro doménové události.** implementation_in_symfony.md#dispatcher-vs-messenger-heading doporučuje pro „in-context, in-request listenery“ EventDispatcher a za anti-vzor označuje „Messenger jako náhradu za EventDispatcher uvnitř téhož kontextu“. Kanonické rozhodnutí knihy (a RegisterUserHandler v téže kapitole) přitom dispatchuje doménové události přes Messenger `event.bus` synchronně pod `doctrine_transaction`. Kapitola to drží pohromadě jen tím, že `UserRegistered` odebírá i kontext Identity. Jiné kapitoly (např. cqrs/projekce, performance_aspects) podle všeho posílají přes `event.bus` i čistě vnitrokontextové posluchače. Návrh: v calloutu zmírnit anti-vzor na „asynchronní Messenger jako náhrada…“ a výslovně uvést, že synchronní `event.bus` je kanonická cesta knihy. Kapitolu 10 jsem v tomto bodě neměnil, protože jde o obsahové rozhodnutí.
- **Zastaralé `highlights` v kap. 09** (markup jsem neměnil): `src/Entity/Order.php` `highlights="9,10,11,18–26"` zvýrazňuje `use Collection` a prázdné řádky místo atributů (správně zhruba 10, 12, 13, 16–17, 19–20, 22–23); `DoctrineOrderRepository.php` `13–19` je o řádek posunuté (konstruktor je 16–19); `CalculateCartPrice.php` `16–24` zvýrazňuje docblock místo `execute()` (ten je zhruba na 30–40). Návrh: přepočítat skriptem (scratchpad/hl.php vypíše zvýrazněné řádky).
- Informativně: kap. 09 používá pro porty `Domain/Port/`, zbytek knihy `Domain/Repository/`. Jde o záměr, v kódu je to okomentované.

## Jazyk a styl – souhrn
Zásahů zhruba 45 v kap. 09 a 40 v kap. 10 (bez oprav kódu). Typické vzory: meta-komentáře („Kapitola srovnává…“, „Následuje ukázka…“, „Tato sekce poprvé skládá…“, „Ukázka potřebuje jednu poznámku, aby ji nikdo neopsal špatně“, „Zde je jeho implementace“), staccato a triády („Žádné závislosti. Žádný framework. Žádná persistence.“, „Dostatečně dobré, rychlé k napsání, čitelné.“), pointy („A právě to je na hranici podstatné.“), kapitálky ANO/NE a šipky v próze, imperativ v soudech („vylučte“, „Nepropagujte“), 1. osoba množného čísla („validujeme“, „pokračujeme“, „vylučujeme“), kalky („legální cesta“, „vlastnit transakci“, „duplicitní kopie“, „Wrap kolem“, „Explicitní alias potřebujete“), opakování slov („ze špatné volby … ze špatné implementace“, „zůstane … zůstanou“), neurčitý odkaz („Tuto hydrataci“ bez antecedentu), nepodložené číslo („stovky hodin“).

Ukázky:
- „Doménové modely **vylučte z auto-registrace v Service Containeru**. … Kontejner sám o sobě problém nedělá.“ → „Doménové modely se **z auto-registrace v Service Containeru vylučují**. … Kdyby v kontejneru zůstaly, nic by se nerozbilo: …“
- „Žádné závislosti. Žádný framework. Žádná persistence.“ → „Nezávisí na ničem, tedy ani na frameworku a persistenci.“
- „Pokud chcete testy bez databáze …, ANO → Hexagonal+. Pokud Doctrine zůstane navždy a testy přes fixtures jsou OK, NE → Layered stačí.“ → „Chcete-li testy bez databáze …, sáhněte po Hexagonal nebo po některém z dalších stylů. Zůstane-li Doctrine natrvalo a testy přes fixtures vám stačí, vystačíte s Layered.“
- „Většina problémů nepramení ze špatné volby stylu, ale ze špatné implementace.“ → „Většina problémů nepramení z volby stylu, ale z jeho implementace.“
- „Nabízel by se i atribut `#[Autowire(…)]` přímo v konstruktoru handleru. To je v Application vrstvě anti-vzor.“ → „Atribut `#[Autowire(…)]` přímo v konstruktoru handleru je v Application vrstvě anti-vzor.“
- „Transakci má vlastnit jedna vrstva.“ → „Transakci má řídit jediná vrstva.“
- „Malý rozdíl v syntaxi `services.yaml`, velký rozdíl v chování:“ → „Dva podobné zápisy v `services.yaml` se chovají různě:“
- „V dalších příkladech v tomto průvodci pokračujeme s atributy přímo na agregátech. Persisted Object Pattern dále nerozvíjíme. Principy jsou identické, jen vyžadují explicitní mapper na každý agregát.“ → „Další příklady průvodce zůstávají u atributů přímo na agregátech a Persisted Object Pattern dál nerozvádějí. Principy jsou stejné, jen každý agregát potřebuje vlastní mapper.“

## NEJISTÉ (neopraveno)
- Předchozí NEJISTÉ jsou dořešené: Meszaros / Configurable Dependency 2011 je doložený rozhovorem [4] (Cockburn: „Gerard Meszaros … was visiting me in 2011 … came up with the name ‚Configurable Dependency‘“). Text teď uvádí „při rozhovoru s Cockburnem v roce 2011“. `symfony/object-mapper` přestal být experimentální v srpnu 2025 a jako stabilní vyšel v 7.4 i 8.0 (symfony.com blog/docs), takže kap. 09 i 10 platí. Aktivační model s `VerificationToken` v anti_patterns.md existuje, odkaz v #error-handling platí. Cockburnova tvrzení o šestiúhelníku, 2–4 portech a čtyřech portech meteorologického systému sedí s článkem [3].
- implementation_in_symfony.md#payment-handler-heading: „invariant sdílený dvěma agregáty je podle Khononova signál, že hranice … stojí jinde“. Atribuci jsem neověřil (bez zdroje v seznamu literatury).
- implementation_in_symfony.md#entities: „DoctrineBundle 3 je [nativní lazy objekty] už neumožňuje vypnout“. Neověřeno ve vendor/ (Doctrine tam není nainstalovaná).
- architectural_styles.md#hexagonal: „Kniha [Hexagonal Architecture Explained] vznikla mimo jiné jako reakce na výklady, které se od originálu odchýlily“. Neověřeno, ponecháno.
- architectural_styles.md#hexagonal-event-port-heading: „[test] běží v jednotkách milisekund, ne ve stovkách“. Číslo nemá oporu (podobné „5 ms místo 500“ se v 1. kole vyřezávalo). Ponecháno, zvažte škrt.

Kontroly: lint-php-snippets 09: 18/0, 10: 31/0; check_anchors OK; check_faq_yaml OK; check_use_statements OK; check_messenger_routing OK; check_tonality 0 nálezů v obou souborech. Kotvy beze změny (diff prázdný u obou). `modified: 2026-09-24` už bylo nastavené. git diff --stat: architectural_styles.md 109 řádků (+/−), implementation_in_symfony.md 130.

---
<!-- reports/g5.md -->
# g5 – cqrs.md (kap. 12), event_sourcing.md (kap. 13)

## Opravené nepravdy / věcné chyby
- cqrs.md#challenges – „Podle Dahana [košík] pro CQRS nekandiduje ani při extrémním poměru čtení k zápisu“ → poměr čtení/zápis Dahan v článku vůbec nezmiňuje. Zůstalo jen „a pro CQRS proto podle Dahana nekandiduje“. Ověřeno: udidahan.com/2011/04/22/when-to-avoid-cqrs/.
- cqrs.md (sekce „Kdo doménové události odešle“, 12.11) – „dispatch po flushi není atomický. Spadne-li proces mezi commitem a odesláním…“ popisovalo jen jeden směr. Pod `doctrine_transaction` (kanonické řešení) odchází zpráva PŘED commitem → doplněn i phantom event při selhání commitu. Obojí sladěno s outbox_pattern.md (dvě nesymetrické nekonzistence).
- cqrs.md tamtéž – „Projektor výše předpokládá, že mu události `OrderPlaced` či `OrderShipped` …“ → projektor `OrderPlaced` nekonzumuje, bere `OrderPlacedIntegrationEvent` (z outboxu) → `OrderShipped` či `OrderCancelled`.
- cqrs.md#worker-produkce-heading – `--failure-limit=5` „po pátém neúspěšně zpracovaném příkazu“ → „po páté neúspěšně zpracované zprávě“ (volba počítá zprávy, callout mluví o projekcích). `messenger:stop-workers` „přes signál uložený v cache“ → „příznak“ (jde o položku v cache, ne o signál). Ověřeno ve vendor/symfony/messenger 8.0.
- event_sourcing.md#hotove-knihovny-heading – patchlevel „jediná z uvedených knihoven s bundlem, který deklaruje podporu Symfony 8“ je nepravda: `ecotone/symfony-bundle` 1.326.x deklaruje `^6.4|^7.0|^8.0` → „stejně jako Symfony bundle Ecotone“. K Ecotone doplněno „Stabilní řada 1.x Symfony 8 podporuje“. Zdroj: Packagist (repo.packagist.org/p2/…json).
- event_sourcing.md#messenger-yaml-heading, #messenger-retry-heading – výřez nadepsaný „plná konfigurace v kapitole o CQRS“ routoval na transport `async`. Ten kanonická konfigurace (cqrs.md#messenger-config-heading: „jména transportů a busů jsou všude stejná“) nezná → `async_events` s `queue_name: events`, v obou blocích i v `#[AsMessage('async_events')]`. Nadpis „Kompletní konfigurace…“ nad blokem „(výřez…)“ → „Konfigurace Messengeru s retry a dead letter queue“ (kotva beze změny).

## Opravené rozpory uvnitř kapitoly
- cqrs.md FAQ „Jak se CQRS implementuje“ – „Příkazy mění stav a nevracejí data“ × 12.06/12.12 (`PlaceOrderHandler` vrací `OrderId`) → „data nevracejí, nanejvýš identifikátor nového záznamu“.
- cqrs.md:12.13 YAML komentář – varování „Messenger třídy ověřuje při kompilaci…“ bylo v jednom bloku dvakrát → jeden výskyt.
- event_sourcing.md#es-cqrs-tok-heading – `PlaceOrderCommand` → `PlaceOrder` (kanonický název).
- event_sourcing.md DomainEvent::eventType() docblock – konvence „user.registered“ × text 13.04 a kód (`identity.user_registered`) → „identity.user_registered“.
- event_sourcing.md (Eventual consistency a UI) – kapitola CQRS prý rozebírá „optimistickou aktualizaci, potvrzovací stránku, polling i SSE“. SSE tam není → „optimistickou aktualizaci, Post-Redirect-Get, polling i synchronní projekce pro kritické cesty“ (podle tabulky 12.12).
- event_sourcing.md#multi-version-heading – „staré [agregáty zapisují] dál ve v1“ × hned následující „přepne se při příští doménové operaci“ → stávající streamy zůstávají ve v1, dokud je další operace nepřevede.
- Text odkazu „Výkonnostní aspekty“ → skutečný název kapitoly „Read modely, projekce a výkon“ (cqrs.md 1×, event_sourcing.md 2×; cesta beze změny).

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- Transport `async` × kanonické `async_commands`/`async_events` (cqrs.md#messenger-config-heading): microservices_and_ddd.md:621 a :711 („async:“), testing_ddd.md:1327/1393 (`async: 'in-memory://'`, `'test://'`). V testing_ddd jde o testovací přepis transportu, jméno by ale mělo odpovídat. Návrh: přejmenovat na `async_events`, nebo výslovně označit jako samostatný projekt. Totéž je potřeba zkontrolovat v repozitáři ukázek (Chapter06_EventSourcing), protože ES kapitola teď používá `async_events`.
- Nález z minulého kola trvá: cqrs.md#command-handler-heading odmítá kontrolu duplicity přes `findByEmail()` (TOCTOU), kap. 18 ji zavádí jako cílový stav.

## Jazyk a styl – souhrn
Zhruba 75 zásahů (cqrs ~40, ES ~35). Typické vzory: kalky („legitimní“, „dedikovaný“, „nemáte potřebu“, „eliminuje lag“, „mechaniky“, „one-time“, „tiers“), hovorové „tohle/tuhle/tady“ v komentářích kódu, meta-věty („Tím je základní infrastruktura kompletní. Další sekce řeší…“, „Kompromisy je lepší znát dřív…“), kýčovité pointy („Chyba to není: jde o…“, „Priorita není kosmetika“), imperativ v soudu („zajistěte“, „musíte doplnit“, „potřebujete garantovat“), shoda rodu („Všechny potomky“ → „Všichni potomci“, „doctrine_transaction, které“ → „který“), anglické uvozovky v komentářích (5×) → „…“.
Ukázky:
- „I v CQRS existují legitimní scénáře, kdy je užitečné vrátit aspoň identifikátor…“ → „I v CQRS se často vyplatí vrátit aspoň identifikátor…“
- „Chyba to není: jde o **vlastnost distribuované architektury**.“ → „Toto okno je **vlastnost asynchronní propagace**, ne chyba.“
- „Messenger workery jsou dlouho běžící procesy. V produkci proto zajistěte:“ → „… Produkční provoz proto potřebuje:“
- „je legitimní začít se **synchronními projekcemi**“ → „vystačí zpočátku se **synchronními projekcemi**“
- „Symfony Messenger nabízí dvě hlavní mechaniky pro řešení:“ → „Symfony Messenger na ně nabízí dva mechanismy:“
- „Pokud projektor není idempotentní, opakované zpracování způsobí poškozená data“ → „Když projektor není idempotentní, opakované zpracování poškodí data“
- „veškeré properties jsou read-only, nastavené v konstruktoru“ → „všechny vlastnosti jsou jen pro čtení a nastavuje je konstruktor“
- „potřebujete agregátní transformaci napříč streamem“ → „je potřeba transformace nad celým streamem“

## Dořešené NEJISTÉ z minulého kola (ověřeno, text beze změny)
- cqrs.md: `broadway/broadway` archivovaný – ano, GitHub: „archived by the owner on Aug 9, 2026“.
- cqrs.md: `--format` má jen `messenger:stats` – ano, vendor/symfony/messenger 8.0 (`FailedMessagesShowCommand` volbu nemá).
- cqrs.md 12.05: tvrzení o dokumentaci Symfony („jeden bus je dobrý výchozí stav…“) – ano, tip na symfony.com/doc/current/messenger/multiple_buses.html.
- event_sourcing.md: Broadway 3.0.1 abandoned 9. 8. 2026, Ecotone 2.0.0-beta.1 (28. 8. 2026), prooph bundle v0.11.2 (5/2024) končí na `^7.0` – vše sedí (Packagist).
- event_sourcing.md: `--fetch-size` od Symfony 8.1 – ano (Symfony blog „New in Symfony 8.1: Messenger Improvements“, commit #63662).
- event_sourcing.md: Harrison J. Brown u Verraese – ano, update v článku „Throw away the key“ včetně výroku právníka a Verraesovy výhrady k šifrám.
- Ověřeno navíc: `#[AsMessage]` od 7.2, `DeduplicateMiddleware` od 7.3 s TTL 300 s, výchozí jitter 0.1, `HandleTrait` hází `LogicException` při 0 i více handlerech (vše vendor 8.0); Young, *Versioning in an Event Sourced System* má kapitolu „Double Write“ (leanpub).

## NEJISTÉ (neopraveno)
- cqrs.md#test-query-handler-heading – komentáře „with() bez expects() je od PHPUnit 12 deprecated a ve 14 zmizí“ a „createMock() by na PHPUnit 13 hlásil ‚No expectations were configured…‘“. Verze PHPUnit, kde se deprecace a notice objevily, jsem neověřil (tipuji 12.x).
- cqrs.md:OrderDashboardProjector – docblock „Asynchronní projektor“, přitom `OrderShipped`/`OrderCancelled` v kanonickém routingu nejsou, jdou tedy synchronně po `event.bus` (async je jen zakomentovaný `OrderPlacedIntegrationEvent`). Komentář o prioritě s tím počítá. Zvážit „Projektor“ bez přívlastku.

Kontroly: lint-php-snippets (oba soubory) 41 bloků / 0 chyb; kotvy beze změny (diff prázdný); check_anchors, check_faq_yaml, check_messenger_routing OK; check_tonality bez nálezů v obou souborech. `modified: 2026-09-24` už bylo nastavené v obou souborech. `git diff --stat`: cqrs.md 102, event_sourcing.md 102 změněných řádků.

---
<!-- reports/g6.md -->
# g6 – sagas.md (kap. 14), outbox_pattern.md (kap. 15)

## Opravené nepravdy / věcné chyby
- sagas.md:#process-manager-heading (komentář v `onPaymentSucceeded`) – „Bez MarkOrderPaid by objednávka zůstala v Draft“ → „v Confirmed“. `placeWithItems()` volá `confirm()` už v továrně (outbox_pattern.md#order-aggregate-heading, text pod ukázkou v 14.05 to sám říká).
- sagas.md:#multi-worker-heading, odrážka „Kompenzační závody“ – `PaymentSucceeded` prý přepne ságu z `Compensating` „zpět na `AwaitingShipment`“. `onPaymentSucceeded` ale vede do `AwaitingStockReservation`, do `AwaitingShipment` vede `StockReserved`. Opožděná událost změněna na `StockReserved`, takže scénář (refund běží, proces jde k expedici) sedí s kódem.
- sagas.md:#izolace-sag – „Richardson popisuje sadu protiopatření… První dvě… Zbylá dvě“ naznačovalo, že jsou čtyři. V *Microservices Patterns* kap. 4.3 je jich šest (semantic lock, commutative updates, pessimistic view, reread value, version file, by value). Text teď říká „šest, kniha rozebírá čtyři“ a zbylé dvě jmenuje.
- sagas.md:#deadlock-detekce-heading – atribut `maxDurationMinutes` v kódu kapitoly neexistuje (timeouty jsou po stavech v `TIMEOUTS`) → „Každý čekající stav ságy má časový limit“. „END span“ (OpenTelemetry nic takového nemá) → „koncový span“.
- sagas.md:#distributed-deadlock-heading – „čeká celý connection pool“ → „oba workery stojí“. Doplněno, že ~1 s je výchozí `deadlock_timeout` PostgreSQL.
- sagas.md:#outbox-pattern-heading (warn) – „Agregát uloží změny do databáze (Doctrine flush) … pád workeru mezi flush a dispatch“ zaměňovalo flush a commit (kanonické rozhodnutí R-1) a agregát sám nic neukládá → „Handler změny agregátu uloží a transakce se commitne … pád procesu mezi commitem a odesláním“.
- sagas.md:#step-events-heading – „hodnotové objekty by se přes serializaci nepřenesly“ neplatí obecně: výchozí PhpSerializer Messengeru přenese libovolný objekt, a text o dvě sekce níž sám říká, že VO serializaci zvládne. Přepsáno na skutečný důvod: příjemce nemá znát VO cizího kontextu. Zmíněna i výjimka `eventId` (Uuid).
- sagas.md:#step-events-heading, test `testLateEventDoesNotReviveFinishedSaga` – komentář „Platba dorazí až po timeoutu“, ale ságu v testu ukončí `PaymentFailed`, žádný timeout → „až poté, co sága skončila ve Failed“.
- sagas.md:#delay-stamp-heading (komentář) – věta bez smyslu „kontrola pak proběhne dřív, než na co čeká“ → „dřív, než mohla odpověď vůbec dorazit“.
- sagas.md FAQ „kompenzační transakce“ – kompenzace „vrací systém do stavu před selhaným krokem“ → „sémanticky vrací efekt dříve dokončeného kroku“. Kompenzuje se úspěšný krok, ne ten, který selhal.
- sagas.md:#choreografie-handlers-heading – nepoužitý `use Doctrine\DBAL\Exception\UniqueConstraintViolationException` v `InitiatePaymentOnOrderPlaced` odstraněn.
- outbox_pattern.md:#2pc-heading – „V první fázi se všichni účastníci ptají, zda mohou commitnout“ → ptá se koordinátor účastníků. In-doubt stav: „Pomůže jen manuální zásah“ → „Dokud se koordinátor neobnoví, pomůže jen ruční zásah“ (po obnově koordinátora se stav vyřeší sám).
- outbox_pattern.md:#dual-write, scénář 1 – „Zápis do DB prošel, dispatch ne“ pod `doctrine_transaction` nenastane: selhaný dispatch vyhodí výjimku a middleware provede rollback. Tento scénář je možný jen tam, kde commit proběhne před dispatchem. Doplněna podmínka „typicky bez middlewaru, kdy `flush()` commitne vlastní transakci“, jinak text odporoval komentáři v ukázce i scénáři 2.
- outbox_pattern.md:#inbox (DbalInboxRepository) – raw DBAL zapisoval a hledal `event_id`/`id` jako RFC řetězec, zatímco `InboxMessage` mapuje sloupce jako `type: 'uuid'`. Na MySQL/SQLite je to BINARY(16)/BLOB (ověřeno ve `vendor/symfony/doctrine-bridge/Types/AbstractUidType.php`: bez nativního GUID typu platforma zapisuje `toBinary()`). INSERT by na MySQL selhal a `isProcessed()` by nikdy nic nenašel. Parametry nově mají typ `UuidType::NAME`. Komentář to vysvětluje.
- outbox_pattern.md:#outbox-message-entity-heading – docblock `messageType` „FQCN doménové události“ → integrační (odpovídá tabulce sloupců a `OutboxMessageFactory`).
- outbox_pattern.md:#migrace-krok-4-heading – podmínka „legacy dispatch nese stejné `eventId`“ nešla splnit, protože krok 2 ponechává dispatch doménové události a ta `eventId` nemá. Doplněno, že legacy dispatch musí posílat integrační událost.
- outbox_pattern.md:#cleanup-command-heading – nadpis „– MySQL“, ale kód je přenositelný (MySQL/PG/SQLite) → nadpis bez „MySQL“. Kotva se nezměnila.
- outbox_pattern.md:#relay-polling-heading + popis volby `--time-limit` – „process manager ho nastartuje znovu“ (v knize jde o kolizi s pojmem Process Manager z kap. 14) → „správce procesů“.

## Dořešené NEJISTÉ z předchozí revize (ověřeno, text beze změny)
- sagas.md:#terminologicka-konvence – CQRS Journey, Reference 6 (learn.microsoft.com/…/jj591569): „you would expect to see a process manager routing messages between aggregates within a bounded context, and … a saga managing a long-running business process that spans multiple bounded contexts“. Tvrzení sedí.
- sagas.md:#kdy-saga-nestaci – Garcia-Molina & Salem (1987), sekce 9: převod peněz s částkou „missing“ mezi T1 a T3 a auditní transakce, která by ji nenašla. Sedí.
- sagas.md:#parallel-compensation-heading – článek, sekce 8 (Parallel sagas): „This problem is known as cascading roll backs“. Sedí.
- sagas.md:#nevratne-akce-heading – dopis/druhý dopis, šek/stop-payment: v článku to je. Věta „kompenzaci bere jako poslední možnost“ se v článku týká jen těžko vratných akcí („it would be desirable not to have to compensate for such actions“) → upřesněno na „Kompenzaci takových akcí“.
- sagas.md:#selhani-kompenzace × 14.02 (CancelShipment) – vysvětlující odstavec („Storno objednávky zvenčí je jiný scénář…“) už v textu je. Není co dělat.
- outbox_pattern.md – body „stovky až nízké tisíce zpráv/s“, pořadí Inbox → relay v migraci, `precision` v migraci a sloupce partitioned tabulky už opravil předchozí průchod. Sedí.
- TransportMessageIdStamp: citace „id of this message in that transport“ ověřena ve vendoru.

## Opravené rozpory uvnitř kapitoly
- sagas.md: „Kompenzační závody“ × kód `onPaymentSucceeded` (cílový stav) – viz výše.
- sagas.md: callout #idempotent-compensation-heading opakoval warn #idempotence-warning-heading ze 14.02 → zkrácen na odkaz a konkrétní důsledek pro `RefundCustomerHandler`.
- sagas.md: note #optimistic-locking-parallel-heading opakoval #optimistic-locking-heading ze 14.06 → zkrácen na odkaz a aplikaci na paralelní kroky.
- sagas.md: callout #step-method-heading opakoval odstavec nad sebou (nový krok = nová metoda + úprava předchozí) → zkrácen.
- outbox_pattern.md: druhé vysvětlení pracovního jména „Idempotent Inbox“ v 15.06 (doslova totéž jako v úvodu) zkráceno na jednu větu.

## Rozpory s jinými kapitolami
- sagas.md:#perzistence-stavu – **opraveno v mém souboru**: „přestože Doctrine mapování jinak patří do Infrastructure (Hexagonal Architecture)“ odporovalo kanonické volbě implementation_in_symfony.md#mapping-volba-heading („Doctrine atributy přímo na doménových třídách“). Nově: stav procesu je aplikační starost, atributy odpovídají volbě knihy (s odkazem), přísné vrstvení zůstává jako alternativa. Odkaz na #hexagonal zůstal.
- NEEDITOVÁNO – repo ddd-symfony-examples, `src/Chapter11_OutboxPattern/Inbox/Infrastructure/DbalInboxRepository.php` – stále zapisuje a hledá `(string) $eventId`, test `tests/Chapter11/Inbox/DbalInboxRepositoryTest.php` zakládá `event_id CHAR(36)`. Návrh: převzít typy `UuidType::NAME` z knihy a v testu založit sloupce jako BLOB, nebo DDL generovat z mapování `InboxMessage`.
- NEEDITOVÁNO – cqrs.md:#saga („Vzor Saga, v orchestrované podobě označovaný Process Manager“) × sagas.md:#terminologicka-konvence („Process Manager“ = orchestrační komponenta, která ságu řídí). Nejde o tvrdý rozpor. Návrh na sjednocení: „…řídí ho orchestrační komponenta Process Manager“.

## Jazyk a styl – souhrn
Zhruba 165 zásahů (sagas ~92, outbox ~74), próza v obou kapitolách je netto o 13 řádků kratší. Typické vzory:
- Kalky a hovorové tvary: „napříč“ v nadbytku, „legitimní“ (3×), „v paperu“ (3×) → „v článku“, „tenhle/téhle/tuhle/těchhle/tohohle/takhle/tady“ (24×) → spisovné tvary. Dále „Subscribery dostanou“ (chybný 1. pád) → „Subscribeři“, „selectuje“, „ackne“, „order“/„event“ v próze → „objednávka“/„událost“, „mikroservis/mikroservisy“ → „mikroslužba“, „manuální resolve“, „tunit“ → „ladit“, „v Messenger“ → „v Messengeru“.
- 1. osoba mn. č. a imperativ v soudech: „Ukazujeme“, „Neděláme… dispatchujeme“, „Dosud jsme řadili“, „testujeme/použijeme“, „v našem procesu“, FAQ „zvolte/Použijte“ → oznamovací tvar.
- Meta-komentáře a vata: vypuštěn úvodní odstavec kap. 15 „Kapitola ukazuje…“, „stojí za vyslovení“ (2×), glosa „špagetový kód rozložený do celého systému“ a věta opakující, kdy se hodí retry.
- Kýč: „jednoho z nich zabije“ → „jeden zápis odmítne“, „umřou v DLQ, aniž by kdokoli spadl“ (2×), „Skip.“, „NEzamknul“, staccato „Žádný leader, žádný SPOF“.

Ukázky:
1. „Žádná služba neví o celém toku. Každá zná pouze svou část a ví, na které události má reagovat.“ → „O celém toku neví žádná služba: každá zná jen svou část a události, na které má reagovat.“
2. „Dva nástroje, které tuto viditelnost zajišťují: …“ → „Tuto viditelnost zajišťují dva nástroje: …“
3. „Na selhání kroku ságy existují dvě základní strategie. Volba závisí na povaze chyby. Je přechodná (…), nebo trvalá (…)?“ → „… Volba závisí na tom, zda je chyba přechodná (…), nebo trvalá (…).“
4. „Neděláme `DELETE FROM payments`. Místo toho dispatchujeme nový doménový příkaz“ → „místo `DELETE FROM payments` se odešle nový doménový příkaz“
5. „Test procesu proto nesmí končit u stavu ságy. Kontrolovat musí i stav agregátu.“ → „Test procesu proto kontroluje i stav agregátu, nejen stav ságy.“
6. „Doctrine adapter je krátký, ale dvě místa v něm přehlédne skoro každý.“ → „Doctrine adaptér je krátký, ale má dvě místa, která se často přehlédnou.“
7. „Pro produkci s vyšším objemem nebo vyšším HA požadavkem se nabízejí…“ → „Při vyšším objemu nebo přísnějším požadavku na dostupnost se nabízejí…“
8. „Ano, vyplatí – protože dual-write problem nevzniká až mezi mikroservisami…“ → „Ano. Dual-write problem nevzniká až mezi mikroslužbami…“

Kontroly: lint-php-snippets 36/0 (sagas) a 21/0 (outbox). check_anchors, check_faq_yaml, check_use_statements, check_named_arguments a check_messenger_routing hlásí OK, check_tonality 0 nálezů. Diff kotev je u obou souborů prázdný a em dash se v nich nevyskytuje. `modified: 2026-09-24` už v obou souborech bylo.

## NEJISTÉ (neopraveno)
- outbox_pattern.md:#dual-write – odkaz na Richardson, *Microservices Patterns*, „kapitola 3“ (Transactional messaging, 3.3.7) uvádím podle paměti, nemám to ověřené v knize.
- outbox_pattern.md:#relay-cdc-heading – „Výchozí `plugin.name` konektoru je `decoderbufs`“ a „privilegium `CREATE` kvůli publikaci“. Obojí odpovídá dokumentaci Debezium, jak si ji pamatuji. Nyní ověřeno nebylo, je to časově citlivé.
- outbox_pattern.md:#migrace-krok-2-heading – po úpravě kroku 4 vyžaduje párování duplicit, aby legacy dispatch posílal integrační událost. Legacy subscribeři na sync transportu ale poslouchají doménovou. Krok 2 by mohl tuto záměnu výslovně zmínit. Strukturu postupu jsem neměnil.
- sagas.md:#multi-worker-heading, odrážka „Události mimo pořadí“ – „`PaymentSucceeded` dorazí dřív než `OrderPlaced`“ v orchestrované ságe z kapitoly nenastane, protože `ChargeCustomer` odchází až po založení ságy. Platí to jen pro choreografický nebo obecný případ. Ponecháno.

---
<!-- reports/g7.md -->
# g7 – content/chapters/authorization_in_ddd.md (kap. 11), content/chapters/testing_ddd.md (kap. 17)

Kontroly: lint-php-snippets 52 bloků / 0 chyb; kotvy beze změny v obou souborech (diff prázdný);
`modified: 2026-09-24` už bylo nastavené. git diff --stat: authorization 72 ř. (±36), testing 99 ř. (±49).

## Opravené nepravdy / věcné chyby
- testing_ddd.md#integracni-testy (text pod DoctrineUserRepositoryTest + docblock testu) – „Privátní služby kontejner po kompilaci zahodí, takže `get()` na nich selže“ → testovací kontejner (`static::getContainer()`) vydá i privátní služby, jen ne ty, které kompilace odstranila (nepoužívané). Zdroj: symfony-docs 8.0 testing.rst („access to both the public services and the non-removed private services“).
- testing_ddd.md#testovani-asynchronnich-toku – „Volba `serialize: true`“ → volba `serialize` v DSN `in-memory://?serialize=true` (symfony-docs 8.0 messenger.rst, sekce In Memory transport).
- authorization_in_ddd.md#ctyri-vrstvy (tabulka) – „Order lze cancelnout jen 24 h od vytvoření“ → „do 24 h od potvrzení“ (kód `cancel()` počítá lhůtu od `placedAt`, které nastavuje `confirm()`; Draft lhůtu nemá).

## Opravené rozpory uvnitř kapitoly
- authorization#policy-based – odstavec za srovnávací tabulkou tvrdil „ExpressionLanguage čte jen veřejné properties. Agregát s privátním stavem proto subjektem politiky být nemůže a vzniká další model“ – v rozporu s odstavcem výš i s tabulkou (čte i veřejné metody; kanonický `Order` s `public private(set)` subjektem být může, test `CancelOrderPolicyTest` ho tak používá). Sjednoceno.
- authorization#summary (Use Case) a checklist bod 2 – „handler volá `isGranted()`“ × 11.05, kde je kanonická asynchronní varianta s `actorId` a `isOwnedBy()`. Doplněny obě cesty.
- authorization#use-case-voter (implementační detaily) – „test namockuje token i subjekt“ × OrderVoterTest (reálný subjekt, stub tokenu). Upraveno; totéž v úvodu #testing-voter-heading („mock“ → „stub“, jak ho test skutečně vytváří přes `createStub`), nadpis „s mock TokenInterface“ → „se stubem TokenInterface“ (kotva beze změny).
- authorization#aggregate-level – „`isCancellable()` je nová metoda… Storno lhůta je jediné, co tahle kapitola k agregátu přidává“ (dvě „jediné“ novinky) + výřez obsahuje `isOwnedBy()`, které už je v Návrhu agregátu. Přeformulováno: nová je jen `isCancellable()`, `isOwnedBy()` je pro úplnost a shoduje se s kap. 7.
- authorization#tri-chyby – „pravidlo ‚zrušit smí jen vlastník‘ patří na jedno místo v use-case vrstvě“ × 11.06 (vlastnictví definuje agregát, use case ho jen vynucuje) → „má mít jedno místo definice a vynucovat se v use case, ne v každém vstupním bodu“.
- authorization FAQ (audit log) – „osvědčil se decorator nad AuthorizationCheckerInterface“ × callout #audit-log-heading (dekorátor nevidí hlasy, přesnější je strategie / `getAccessDecision()`). Sjednoceno.
- testing#integracni-testy – „Obě implementace plní tutéž smlouvu“ navazovalo na odstavec o SQLite vs. produkční DB, takže „obě“ odkazovalo špatně → „Doctrine i InMemory implementace“.

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- authorization_in_ddd.md #testing-aggregate-heading: test-data třída `tests/Ordering/Domain/OrderFactory.php` se statickými `placed()`, `placedFor()`, `shipped()` × testing_ddd.md (17.04): „`OrderFactory::new()->confirmed()->create()`“ (zenstruck/foundry) a tamní taxonomie, podle níž jsou pojmenované statické továrny **Object Mother** („`OrderMother::confirmedOrder()`“), ne builder. Kap. 11 přitom svou třídu nazývá „test-data builderem“. Návrh: v kap. 11 buď třídu přejmenovat na `OrderMother`, nebo jen slovo „builder“ nahradit „Object Mother (viz [Testování](/testovani-ddd#test-doubles))“; zároveň se vyhnout kolizi jména s Foundry `OrderFactory`. (Kap. 11 je moje, ale přejmenování třídy by se rozešlo s repozitářem ukázek Chapter10_Authorization – nechávám na rozhodnutí.)

## Jazyk a styl – souhrn
Cca 45 zásahů (29 + 5 v kap. 11, 37 v kap. 17). Kapitoly už prošly dvěma revizemi, takže zásahy jsou cílené.
Typické vzory: anglicismy v próze a tabulkách („cancelnout order“, „Data leak“, „UI hidden“, „compliance / Insufficient“, „server-side enforcement“, „cross-tenant“, „trade-off“, „Twig template“, „trace: cancellation request“, anglický komentář v kódu, „filter“ v YAML komentáři), „dává/má smysl“ → „vyplatí se“, „legitimní“ → „oprávněné / vědomý“, „explicitně“ navíc, dvojtečky na konci nadpisů calloutů v kap. 17 (8×), „Varování:“ v nadpisu warn calloutu, „tzv.“, zdvojené výplně („skutečně správně“, „proto zajistí, že test skutečně čte“), fragmentované staccato („Že… Že… A že… Tím je pokrytý.“), meta-úvod („Kapitola navazuje… a odkazuje sem“ – sloučeno do první věty, odkaz zachován), imperativ v soudech („musíte přidat“, „použijte suspend()“, FAQ „zaveďte… ověřte“) → oznamovací tvar, gramatika („S čtyřvrstvým“ → „Se čtyřvrstvým“, „vývojáři … nerad“ → „neradi“), „aggregate“ v české větě → „agregát“.

Ukázky:
- „„Smí Petr cancelnout order #42?““ → „„Smí Petr zrušit objednávku #42?““
- „Test value objektu ověřuje tři věci. Že neplatný vstup vyhodí… Že dvě instance… A že objekt zůstává neměnný… Tím je hodnotový objekt pokrytý.“ → „Test value objektu ověřuje tři věci: neplatný vstup vyhodí odpovídající výjimku, dvě instance se stejnou hodnotou jsou si rovny přes `equals()` a objekt zůstává neměnný – jiná hodnota znamená novou instanci.“
- „…nepotřebují nic jiného než PHPUnit a samotné doménové třídy. Žádný bootstrap Symfony kernelu, žádná databáze, žádné fixtures.“ → „Stačí jim PHPUnit a samotné doménové třídy; kernel, databázi ani fixtures nepotřebují.“
- „Formulovaná je pro JavaScript, ale otázku klade dobře: kolik logiky vlastně testujete v izolaci?“ → „Formulovaná je pro JavaScript, ale otázka, kolik logiky se vlastně testuje v izolaci, platí i v PHP.“
- „Je to legitimní kompromis, ne chyba.“ → „Jde o vědomý kompromis, ne o chybu.“
- „| OWASP A01:2021 compliance | Insufficient – viz … | Splňuje (server-side enforcement) |“ → „| Soulad s OWASP A01:2021 | Nedostatečný – viz … | Ano (vynucení na serveru) |“
- „Kdo volí fake repozitář, hlásí se ke stylu, kterému Fowler říká classical TDD“ → „Fake repozitář patří ke stylu, kterému Fowler říká classical TDD“
- „…`tenant_id` tam musíte přidat ručně“ / „použijte `suspend()` a `restore()`“ → „`tenant_id` se tam přidává ručně“ / „slouží `suspend()` a `restore()`“

## Dořešené položky NEJISTÉ z předchozí revize (ověřeno, text ponechán)
- `Security::getAccessDecision()` – přibyla v SecurityBundle 7.4 (CHANGELOG 7.4: „Add `Security::getAccessDecision()` and `getAccessDecisionForUser()` helpers“); `Vote::$reasons`/`addReason()` od 7.3 („Add ability for voters to explain their vote“), `Vote::$extraData` od 7.4 (security-core CHANGELOG), `AccessDecision::$isGranted` je veřejná vlastnost. Text kapitoly to už uvádí správně.
- `security.authorization_checker` privátní od 6.0 – UPGRADE-6.0.md: „The `security.authorization_checker` and `security.token_storage` services are now private“. ✓
- `symfony/acl-bundle` – poslední vydání 2.4.0 (24. 4. 2024), constraint `^4.4|^5.0|^6.0|^7.0` (Packagist). ✓
- `evansims/openfga-php` – Packagist `abandoned: true`. ✓; `cerbos/cerbos-sdk-php` ~7,6 tis. stažení celkem („v řádu tisíců“). ✓
- AuthZEN Authorization API 1.0 – schválená jako OpenID Final Specification v lednu 2026 (openid.net, „Authorization API 1.0 Final Specification Approved“). ✓
- Ham Vocke – parafráze sedí: „Don't become too attached to the names of the individual layers…“, „Write tests with different granularity; the more high-level you get the fewer tests you should have“, „test one integration point at a time by replacing separate services and databases with test doubles“. ✓
- Deptrac – `qossmic/deptrac` je na Packagistu abandoned s náhradou `deptrac/deptrac`; řada 4.x (4.7.2, 9/2026) má výchozí `deptrac.php`, `init` generuje `deptrac.php`, `deptrac.yaml` auto-detekuje. ✓ (datum „od listopadu 2024“ viz níže.)
- phparkitect `generate-baseline` / `prune-baseline` jako samostatné příkazy – README projektu. ✓
- Doctrine ORM `FilterCollection::suspend()` / `restore()` existují (branch 3.7.x). ✓
- `#[AsTaggedItem(priority)]` se u tagovaných služeb uplatní (PriorityTaggedServiceTrait čte atribut). ✓

## NEJISTÉ (neopraveno)
- testing_ddd.md#architektonicke-testy – „`qossmic/deptrac` je **od listopadu 2024** abandoned“: abandonment potvrzen, přesné datum Packagist API neuvádí.
- authorization_in_ddd.md#testing-voter-heading (komentář v OrderVoterTest) – „createMock() by na PHPUnit 13 hlásil ‚No expectations were configured‘ a ve 14 přestane fungovat“ – plán pro PHPUnit 14 neověřen.
- authorization_in_ddd.md#tri-chyby-doctrine-heading a #multi-tenancy – „Ověřeno/Měřeno na ORM 3.6: filtr se neuplatní na neowning stranu one-to-one“ – tvrzení autorova měření, neověřováno.
- testing_ddd.md#testovani-domain-events (za given-when-then) – „pravidlo, které stavově ukládaný agregát nemá jak porušit“: i stavově ukládaný agregát může nahrát událost před kontrolou invariantu; tvrzení je spíš o tom, že u ES by falešná událost trvale zůstala v historii. Formulaci jsem neměnil, stojí za zvážení („u event-sourced agregátu má porušení trvalé následky“).
- authorization_in_ddd.md#registration-two-writes-heading – „Cenou je závislost `Identity` na repozitáři `UserManagementu`; je to jednosměrná vazba na rozhraní, ne na model“ – posluchač ale volá metody modelu `User` (`email()`, `hashedPassword()`), takže na model vázaný je. Nechávám autorovi.

---
<!-- reports/g8.md -->
# g8 – content/chapters/microservices_and_ddd.md (19), content/chapters/performance_aspects.md (16)

## Opravené nepravdy / věcné chyby
- microservices_and_ddd.md#naklady-heading – „Newman shrnuje nákladové oblasti … Kategorie níže jsou jeho“ → nepravda: Newmanovy „Microservice Pain Points“ (2nd ed., kap. 1) jsou vývojářský komfort, technologické přetížení, náklady, reporting, monitoring a ladění, bezpečnost, testování, latence, konzistence dat. Text je teď vyjmenovává a tabulku označuje za vlastní rozpis nákladové stránky. Zdroj: obsah knihy (O'Reilly/samnewman.io, poznámky danlebrero.com).
- microservices_and_ddd.md#velikost-service-heading – „Sloučit dvě předimenzované služby je refaktoring; rozpletení sítě nano-services je projekt“ bylo logicky obráceně vůči „macro first, then micro“ → „Rozdělit předimenzovanou službu je refaktoring; rozplést síť dvaceti nano-services je projekt na čtvrtletí“.
- microservices_and_ddd.md#migrace – „heslo Sama Newmana **„don't do a big-bang rewrite“**“ jako doslovný citát (neověřitelný) → parafráze (pravidlo CLAUDE.md o citacích).
- microservices_and_ddd.md#symfony (úvod) – „přes hranici services je směrujete na `amqp` transport“ neodpovídalo konfiguraci o dva bloky níž (routing na outbox `events_out`, AMQP až přes relay) → sjednoceno.
- microservices_and_ddd.md#messenger-publisher-heading + OutboundEventSerializer – kód četl `$event->eventId` a `$event->total->…` z doménové `OrderPlaced`, která má podle /zakladni-koncepty jen `orderId`, `customerId`, `occurredAt` (a v okamžiku `place()` je objednávka prázdná). Routing i serializer přepnuty na `OrderPlacedIntegrationEvent` z kap. 15 (má `eventId`, `totalAmountCents`); měna jde na drát výslovně jako `CZK`. Tím zmizel i rozpor z minulé revize „doménová třída na outbox transport × kap. 15 (do outboxu jde integrační tvar)“. Highlights 49–53 sedí.
- microservices_and_ddd.md IntegrationEventSerializer – fallback `currency ?? 'EUR'` v obchodě, který v knize účtuje v CZK → `'CZK'`.
- microservices_and_ddd.md – věta „Serializer je jediné místo v subscriberu…“ stála za kódem *publisherova* serializeru, dřív než se subscriberův ukázal → přesunuta za `IntegrationEventSerializer`.
- performance_aspects.md#n-plus-1-problem (EXTRA_LAZY výřez `Order`) – `final class Order`, `#[ORM\Table(name: '`order`')]`, `string $id` a veřejný konstruktor odporovaly kanonickému mapování (07.08: `orders`, `class Order extends AggregateRoot`, `OrderId` přes typ `order_id`, privátní konstruktor) i vlastní větě kapitoly v 16.05 → výřez sladěn s 07.08, nové je jen `fetch: 'EXTRA_LAZY'` + `countItems()`.
- performance_aspects.md – tabulky `` `order` ``/`"order"`/`order_item` (komentáře, callout, SalesReport SQL) → `orders`/`order_items` (kanonické názvy z 07.08, cqrs, 16.07 už `orders` používala).
- performance_aspects.md#agregat-hranice (`DoctrineOrderRepository::findHeaderById`) – `$em->find(Order::class, $id->value)`: `OrderIdType::convertToDatabaseValue()` řetězec odmítá (InvalidType) → `find(Order::class, $id)`, jako v 07.08.
- performance_aspects.md#cachovani (GetUserProfileHandler „verze s cache“) – SQL četlo `u.name`, ale `User` má embedded `UserName` → sloupec `name_value` (viz kap. 12). Navíc soubor stejného jména vracel jinou třídu (`UserProfileView`) než handler v kap. 12 (`UserProfileViewModel`). Handler teď dává cache před `UserProfileReadRepository` z kap. 12 a vrací `?UserProfileViewModel`. Kód se zkrátil a odpovídá kanonickému read modelu.
- performance_aspects.md StartProductImportHandler – chyběl `#[AsMessageHandler]`, přestože jde o handler, a komentář tvrdil „Controller nebo CLI příkaz rozdělí…“ a „žádný memory leak“ (odporovalo odstavci pod ním o nasčítané paměti workerů) → atribut doplněn, komentáře opraveny.
- performance_aspects.md#uuid-vs-integer – neexistující třída `AggregateId` → „Identifikátor“.

## Opravené rozpory uvnitř kapitoly
- microservices_and_ddd.md#mytus-pravda-heading – „1:N bývá chyba“ a hned „Každá varianta má svůj kontext, ve kterém je správná“ → závěrečná věta vypuštěna, 1:N přesunuta na konec výčtu.
- microservices_and_ddd.md – strom `src/` měl `SharedKernel/Domain/ValueObject/Money.php`; kniha má 30× `App\SharedKernel\Domain\Money` a 15× `…\Domain\Currency` → strom opraven.
- microservices_and_ddd.md#symfony-monolith-heading + #de-microservicing-postup-heading – integrace v monolitu přes „Symfony Event Dispatcher“ / „interní EventDispatcher“ × kanon knihy (doménové události přes Messenger `event.bus`) a fáze 1 v 19.09 („Symfony Messenger, sync transport“) → oba výskyty sjednoceny na Messenger se `sync` transportem.
- microservices_and_ddd.md – „saga“ × „sága“ v próze → „sága“ (kap. 14 se jmenuje „Ságy…“); „Bounded Contexts“ × „Bounded Contexty“ → české tvary.

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- team_topologies.md FAQ (ř. ~886): „Ve zdravém stavu jsou izomorfní: 1 stream-aligned tým = 1 BC = 1 mikroservis“ × microservices_and_ddd.md#mytus („Slogan BC = microservice je polopravda“, 1:1 jen jako výchozí hypotéza, N:1 = modular monolith). Návrh: „Ve zdravém stavu se kryjí tým a BC; BC může běžet jako mikroservis i jako modul modulárního monolitu (viz DDD a microservices).“
- Terminologie: team_topologies.md píše „mikroservis/mikroservisy“, kap. 19 a zbytek knihy „microservice(s)“. Návrh: sjednotit na „microservice(s)“.
- performance_aspects.md frontmatter `breadcrumb_name: Výkonnostní aspekty` a cqrs.md (link texty „[Výkonnostní aspekty](/vykonnostni-aspekty…)“ v 12.08 poznámce a ~ř. 767) × titul kapitoly „Read modely, projekce a výkon“ (Chapters.php). Frontmatter jsem podle zadání neměnil. Návrh: breadcrumb i link texty na „Read modely, projekce a výkon“.
- performance_aspects.md ImportProductsHandler – `App\Product\Domain\Model\Product`, `Product::create()` a `App\Product\…\ProductId` jsou v knize jediné; kap. 19 a jinde se katalog jmenuje `App\Catalog`. Nechal jsem (není to rozpor s konvencemi z CLAUDE.md), návrh: `App\Catalog\Domain\Model\Product` + pojmenovaná továrna.
- microservices_and_ddd.md#symfony – outbox je zde Doctrine transport `events_out` + vlastní relay, kap. 15 má vlastní tabulku `OutboxMessage`; odkaz `/outbox-pattern#relay` tedy popisuje relay nad jinou tabulkou. Návrh: v kap. 19 jednou větou říct, že jde o zjednodušenou variantu, nebo přejít na `OutboxMessage` z kap. 15.

## Jazyk a styl – souhrn
Zhruba 150 zásahů (kap. 19 ~135, kap. 16 ~25; kap. 16 byla po minulé revizi už hutná). Počet slov: kap. 19 10 906 → ~10 880, kap. 16 8 924 → 8 872. V kap. 16 úbytek hlavně díky kratšímu cache handleru. Typické vzory: meta-úvody („Tato kapitola odpovídá…“, „Tato sekce se soustředí…“, „Proto následuje sekce…“), anglicismy v próze (coupling, cross-BC, state changes, lookup, overhead/benefit, availability, network traffic, VAT IDs, patterny), kalky („legitimní“, „dedikovaný“, „explicitní“, „napříč“), staccato a pointy („Zotavení žádné.“, „Ne Prime Video…“, „Žádné X, žádné Y, žádné Z.“, „to nejhorší z obou světů“), imperativ v soudech („Rozlišujte…“), gramatika („Korelace se ztvrdla“, „oblasti, které se přehlíží“, „Tuto díru“), opakování podmětu v sousedních větách (Fowler…Fowler, Tabulka…Tabulka).

Ukázky:
- „Tato kapitola odpovídá na otázku, kterou si dříve nebo později položí každý tým: **jak …?** Konkrétně: kdy…“ → „Zbývá otázka, kterou si dřív nebo později položí každý tým: **jak …**, tedy kdy…“
- „Sync volající čeká a buď dostane odpověď, nebo timeout. Zotavení žádné.“ → „Synchronní volající jen čeká na odpověď nebo na timeout a sám se zotavit neumí.“
- „Coupling monolitu s operační režií microservices spojuje to nejhorší z obou světů:“ → „Distributed monolith spojuje provázanost monolitu s operační režií microservices:“
- „**Důsledek:** kumulativní latence v sekundách, availability v součinu, retry storm při výpadcích.“ → „**Důsledek:** latence se sčítá do sekund, dostupnost se násobí a výpadky spouštějí retry storm.“
- „Jedna komponenta jednoho týmu. Ne Prime Video jako produkt a rozhodně ne obrat…“ → „Šlo o jednu komponentu jednoho týmu, ne o Prime Video jako produkt, natož o obrat…“
- „Rozlišujte přitom sdílenou *instanci* od sdíleného *schématu*. … je legitimní a levný mezikrok“ → „Sdílená *instance* přitom není totéž co sdílené *schéma*. … je přijatelný a levný mezikrok“
- „Šest dní po vydání textu publikoval Fowler na svém webu opačný názor…“ (po větě „Fowler sám…“) → „Šest dní nato vyšel na jeho webu opačný názor…“
- kap. 16: „`OFFSET` databáze neumí přeskočit; musí projít a zahodit…“ → „Řádky před `OFFSET` databáze nepřeskočí, musí je projít a zahodit.“

## NEJISTÉ – dořešeno z minulé revize
- performance_aspects.md#lazy-objects-heading „DoctrineBundle 3 má nativní lazy objekty zapnuté napevno“ – **ověřeno, ponecháno**: UPGRADE-3.0 odstranil `proxy_dir`/`auto_generate_proxy_classes` jako no-op při nativních lazy objektech; UPGRADE-3.1: `enable_native_lazy_objects` deprecated, „native lazy objects are now always enabled“; Configuration.php hodnotu `false` odmítá.
- microservices_and_ddd.md `--fetch-size` „od Symfony 8.1“ – **ověřeno** (symfony.com/blog/new-in-symfony-8-1-messenger-improvements, PR #63662). Vendor má messenger 8.0.7, tam volba ještě není.
- microservices_and_ddd.md IntegrationEventSerializer docblock (8.1: decode failure přes retry/failure pipeline, 8.0: reject → DLX) – **ověřeno** týmž blogem („routes decode failures through the normal failure-handling pipeline“; dřív „silently discarded“).
- Evans, *DDD & Microservices: At Last, Some Boundaries!*, QCon London 2016 – ověřeno.

## NEJISTÉ (neopraveno)
- microservices_and_ddd.md billing-svc config – Symfony 8.1 podle blogu posílá dekódovací selhání i přes *retry*, a `DecodeFailedMessageMiddleware` při retry dekóduje znovu. Komentář „retry by šel přes encode() a spadl“ a `max_retries: 0` platí pro 8.0; zda na 8.1 retry skutečně jde přes `encode()` decode-only serializeru, jsem neověřoval v kódu 8.1.
- microservices_and_ddd.md#modular-monolith – „Newman (kap. 3) … výslovně doporučuje monolith-first nebo modular monolith-first“ – kapitolu 3 jsem proti textu neověřil.
- microservices_and_ddd.md#velikost-service-heading / četba – Dehghani „nejčastější chyba migrace: postavit novou službu a nezrušit původní cestu v monolitu“ – parafráze, neověřena.
- performance_aspects.md#replicy-pooling-heading – PgBouncer `max_prepared_statements` výchozí 0 v 1.21, 200 od 1.24 – ponecháno z minulé revize, znovu neověřeno.

Kontroly: lint-php-snippets (oba soubory) 20 bloků / 0 chyb; check_anchors, check_faq_yaml, check_use_statements, check_messenger_routing, check_property_access, check_named_arguments, check_static_calls OK; check_tonality 0 nálezů. Kotvy beze změny (diff prázdný u obou souborů). `modified: 2026-09-24` v obou (u kap. 19 už bylo).
git diff --stat: microservices_and_ddd.md ~317 řádků (±), performance_aspects.md ~139 řádků (±).

---
<!-- reports/g9.md -->
# g9 – migration_from_crud.md (18), case_study.md (24), practical_examples.md (23)

## Opravené nepravdy / věcné chyby
- migration_from_crud.md#strangler-fig („Co vzor neřeší“) – „Ve verzi z roku 2024 Fowler popis rozšířil ze čtyř kroků v kódu na čtyři aktivity“: původní zápis z 2004 žádné čtyři kroky neměl (krátká úvaha nad metaforou + EventInterception/AssetCapture) a čtyři aktivity Fowler převzal od Cartwrighta, Horna a Lewise → „Původní zápis byl krátká úvaha nad metaforou. V roce 2024 ho Fowler přepsal a převzal od … čtyři aktivity“. Zdroj: martinfowler.com/bliki/StranglerFigApplication.html (aktuální verze) + Wayback 2021.
- migration_from_crud.md#strangler-fig – NEJISTÉ z minulé revize (data 29. 6. 2004 a 29. 4. 2019) ověřeno ve Wayback: „29 June 2004“, „Changed URL and name to Strangler Fig Application April 29 2019“. Beze změny.
- migration_from_crud.md#kdy-nezacinat – podmínka Sacrificial Architecture zpřesněna podle Fowlera („The team that writes the sacrificial architecture is the team that decides…“ × „a new team coming in, hating the existing code“).
- migration_from_crud.md#repository-interface-heading – komentář v `DoctrineUserRepository::save()` „flush na aplikační vrstvě, aby byla možná transakční konzistence přes více agregátů“ odporoval pravidlu jeden agregát = jedna transakce i kanonickému dispatchi → „Jen persist. Flush patří handleru, commit middlewaru doctrine_transaction“ (souhlasí s kap. 10 a s `RegisterUserHandler` v téže kapitole).
- migration_from_crud.md#before-after-heading – `UserService` (PŘED) používal `MailerInterface` bez `use` → doplněno `Symfony\Component\Mailer\MailerInterface`.
- migration_from_crud.md#Value Objects – „Doménový koncept skrytý v string se nazývá Primitive Obsession“ (Primitive Obsession je název příznaku, ne konceptu) → „je příznak zvaný Primitive Obsession“.
- case_study.md#read-model-query-heading – „Stejný název třídy, stejný command, jiná implementace“: `GetProjects` je query, ne command → „Název třídy i dotaz `GetProjects` zůstaly“.
- case_study.md#read-model-schema-heading + komentář v `ProjectListView` – „Na DBAL 3.x zbývá option jsonb“: option platí do DBAL 4.2 včetně, `Types::JSONB` přibyl a option byla deprecována ve 4.3 → „Starší verze (DBAL 3.x až 4.2)“. Zdroj: doctrine/dbal UPGRADE.md (sekce Upgrade to 4.3), `Types.php`.
- case_study.md FAQ „Jak spolu Bounded Contexty komunikují?“ – „porty s implementací v infrastruktuře cílového kontextu“ odporovalo #architecture a #assign-task-handler-heading (port v doméně TaskManagement, adaptér v jeho infrastruktuře) → rozhraní v doméně volajícího, adaptér v jeho infrastruktuře.
- case_study.md#read-model-reconciliation-heading – NEJISTÉ z minulé revize (DeduplicateMiddleware) ověřeno ve `vendor/symfony/messenger` 8.0.7: zámek se bere jen bez `ReceivedStamp` a uvolní se po zpracování, opakované doručení projde. Současný text odpovídá; beze změny.
- practical_examples.md#user-aggregate – „`PasswordAuthenticatedUserInterface` doporučuje `__serialize()`, které citlivé pole vynechá“ – dokumentace nabízí vynechání, nebo náhradu otiskem `crc32c` (jen ten zachová zneplatnění relací po změně hesla) → doplněno. Zdroj: docblock `PasswordAuthenticatedUserInterface` (symfony 8.0), symfony-docs security.rst.
- practical_examples.md#user-aggregate – „dva kompromisy … A `final` projde“ (druhá položka kompromis není) → „jeden kompromis“; tvrzení o `final` doplněno o podmínku „se zapnutými nativními lazy objekty (PHP 8.4, ORM 3.4+)“ (řeší NEJISTÉ minulé revize).

## Opravené rozpory uvnitř kapitoly
- practical_examples.md#blog – úvod jmenoval slices „vytvoření, výpis, detail“, strom má i `AddComment` → doplněno.
- case_study.md – „view“ jednou ženského rodu („zaostalou view“), jinde bez rodu → sjednoceno na „řádek“ v komentáři i próze.
- case_study.md, migration_from_crud.md – text odkazů sjednocen s tituly kapitol: „Výkonnostní aspekty“ → „Read modely, projekce a výkon“, „Testování DDD aplikací“ → „Testování DDD kódu v Symfony“; „kapitola 10“ / „kapitola 14“ v textu → název kapitoly.
- migration_from_crud.md – „anemická“ × „anémická“ → „anémická“ (i case_study); Recept 5 „controller“ × zbytek kapitoly „kontroler“ → „kontroler“ (nadpis bez změny kotvy).

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- Žádný nový tvrdý rozpor. Komentář `save()` v kap. 18 je nyní v souladu s implementation_in_symfony.md ř. ~664 („Jen persist. Flush a commit vlastní doctrine_transaction middleware…“).

## Jazyk a styl – souhrn
Cca 125 zásahů (kap. 18 ~50, kap. 24 ~52, kap. 23 ~24), kotvy beze změny, frontmatter jen `modified` (practical_examples 2026-09-23 → 2026-09-24; ostatní dvě už 2026-09-24 měly). Typické vzory: kalky („dedikovaný tým“, „čas není dostupný“, „investice se nedoběhne“, „legitimní volba“ 5×, „vlastnící BC“, „Property“, „value objecty“, „data import“, „bootstrapping kernelu“, „trade-offy“, „cross-context“), staccato a pointy („Triviální zadání.“, „Stejné slovo, jiná odpovědnost.“, „Opačný pól je stejně reálný.“, „A pak se nestane nic.“), duplicity (dvakrát „implementaci lze vyměnit“, trojí „přechodová architektura, kterou nikdo nezahodil“ → dvakrát, závěr kap. 23 opakující úvod), meta-úvod kap. 23, opakování slova ve větě, gramatika (chybějící sloveso „připojení do prázdna“, „jaká tým“, pomlčky „-“ v komentářích → „–“, „tohle/tyhle/taky“ → spisovně).
Ukázky:
- „Symfony kontrolery přestaly být tenkou vrstvou pro HTTP adaptaci. Místo toho přímo implementují…“ → „Kontrolery přestaly být tenkou HTTP vrstvou a samy implementují…“
- „Přesun závislosti do konstruktoru z něj šev udělá a enabling point se přesune…“ → „Závislost předaná konstruktorem ho vytvoří a enabling point tím skončí v konfiguraci služeb.“
- „Service se stane tenkým koordinátorem: jen volá entitu, transakce, eventy.“ → „Service se zúží na tenký koordinátor: načte entitu, zavolá její metodu a předá události.“
- „Pokud by jeden kontext potřeboval čtyři různé experty, je to signál, že jde o agregaci nesouvisejících odpovědností.“ → „Kontext, který potřebuje čtyři různé experty, nejspíš slepuje nesouvisející odpovědnosti.“
- „Dva dny u tabule stojí zlomek toho, co později stojí posun…“ → „Dva dny u tabule jsou zlomek ceny, kterou má pozdější posun…“
- „Tým je jeden, deploy je jeden, riziko … je zanedbatelné.“ → „Tým i deploy jsou jen jeden a riziko … je zanedbatelné.“
- „Na tomhle kroku se naletí nejčastěji, protože se neprojeví chybou.“ → „Tento krok se přehlédne nejsnáz, protože jeho absence nic neshodí.“
- „Kapitola je průřezem předchozími kapitolami. Tři krátké příklady ukazují, jak vzory … drží pohromadě jako funkční aplikace.“ → „Tři krátké příklady skládají vzory … do funkční aplikace.“

Kontroly: lint-php-snippets (13 + 19 + 8 bloků, 0 chyb), check_anchors OK, check_faq_yaml OK, check_use_statements OK, check_toplevel_code OK, check_tonality 0 nálezů ve všech třech souborech, diff kotev vůči HEAD prázdný.

## NEJISTÉ (neopraveno)
- migration_from_crud.md#datova-migrace-strangler-heading – „Dual-write“ je definován jako zápis do dvou modelů „v jedné databázové transakci … buď proběhne celá, nebo vůbec“, o dva odstavce výš ale „nový zápis běží navíc a jeho chyba nesmí shodit požadavek“. V jedné transakci chyba SQL (zvlášť v PostgreSQL) transakci shodí; nesmí-li shodit požadavek, musí jít o savepoint nebo zachycenou chybu mapování před zápisem. Návrh: upřesnit („chyba mapování se zaloguje a nový zápis se přeskočí; na úrovni SQL chrání savepoint“) nebo zmírnit atomicitu.
- migration_from_crud.md#strangler-fig – „Poslední bod (změnu organizace) týmy vynechávají nejčastěji“ je autorský soud, ne Fowlerův; Fowler říká jen, že bez změny organizace skončí nový systém ve stejném stavu. Ponecháno jako názor průvodce.
- case_study.md#discovery-grouping-heading – tabulka přiřazuje TaskManagement i CommentManagement stejného experta (Týmový vedoucí), odstavec pod ní to označuje za kandidáta na sloučení; kapitola to nikde nerozebírá. Případně doplnit jednu větu, proč zůstaly oddělené (tempo změn / Supporting × Core).
- case_study.md#project-model-heading – „metoda přibyla v ORM 3.4.0 a od 3.5 je starý režim proxy vedený jako zastaralý“ jsem neověřoval proti UPGRADE.md doctrine/orm (vendor neobsahuje doctrine).

---
<!-- reports/g10.md -->
# g10 – anti_patterns.md (21), ddd_pain_points.md (20), ddd_ai.md (AI)

## Opravené nepravdy / věcné chyby
- anti_patterns.md#anemicky-domenovy-model – „Fowler považoval argument ‚porušuje se zapouzdření‘ za příliš slabý“ → „Fowler sám uznal, že argument čistotou OOP nestačí“. Zdroj: martinfowler.com/bliki/AnemicDomainModel.html („object-oriented purism is all very well, but I realize that I need more fundamental arguments“). Ověřeno i „Domain Models aren't always the best tool“ (TS) a obhajoba Service Layer.
- anti_patterns.md#primitive-obsession – „dobropis je `refund()`“ četlo se jako metoda `Money`, kterou kanonický Money (06.04) nemá → „Směr pohybu nese doménová operace … dobropis je volání `refund()`“.
- anti_patterns.md#prilis-velky-agregat (kód) – `Order::confirm()` ve výřezu nekontroloval stav, šlo potvrdit i zrušenou objednávku; kanonický `confirm()` (aggregate_design) hází `cannotTransition` mimo Draft → doplněna stejná kontrola. Lint OK.
- anti_patterns.md#prilis-velky-agregat – „Z toho, že objednávka má položky, neplyne, že zákazník má vlastnit historii objednávek“ (nesouvislý příklad) → „Z toho, že zákazník má objednávky, neplyne, že je jeho agregát musí obsahovat.“
- anti_patterns.md#sdilena-databaze (kód) – SQL spojovalo `o.user_id = u.id`, ale filtrovalo `o.customer_id` → obě JOIN na `o.customer_id`.
- anti_patterns.md#sdilena-db-spravne-heading – kontexty mají mluvit přes „doménové události“ × 21.07 téže kapitoly (doménová událost = vnitřní věc kontextu, ven jde integrační) → „integrační události“.
- ddd_pain_points.md#a3-value-objects – „Implementujte `Type`“ – `Doctrine\DBAL\Types\Type` je abstraktní třída, ne rozhraní → „Odvoďte třídu od abstraktní `Type`“.
- ddd_pain_points.md#a1-transakce, #a2-spinavy-em – „`flush()` commituje“ (3×) × kanonické flush ≠ commit → „zapíše“.
- ddd_pain_points.md FAQ „Jak vysvětlit přínos DDD managementu“ – radila „vyčíslit dlouhodobý přínos: nižší počet regresních chyb…“ × E1 („zdržte se slibu, že klesnou kvůli DDD; žádná studie to nedoložila“) → přínos opřít o konkrétní obchodní bolest, metriky měřit před/po jako společný jazyk, ne slib; „change failure rate“ → „change fail rate“ (jako tabulka E1 a DORA).
- ddd_pain_points.md#e1-management – „sada DORA, která má dnes pět položek“ → „která se z původních čtyř metrik rozrostla na pět“ (dora.dev/guides/dora-metrics: 5 metrik, dříve four keys).
- ddd_ai.md#testovani – „Beck se otázce věnuje veřejně od roku 2025“ → od roku 2023 (tweet 18. 4. 2023 a esej „90% of My Skills Are Now Worth $0“, 19. 4. 2023, newsletter.kentbeck.com); esej doplněna do zdrojů.
- ddd_ai.md#komplexita-vs-crud – text přičítal DHH závěr, že „AI, která generuje CRUD kód, je přirozeným řešením“, přitom tatáž kapitola (ai.03) uvádí, že DHH psaní kódu asistentovi nepřenechává → závěr výslovně označen jako úvaha, kterou DHH sám nevyslovuje; „DDD přeceňované“ (DHH o DDD v citátu nemluví) → „složitá architektura je pro ně zbytečná komplikace“; „většina“ → „velká část“ (citát: „a lot of people“).
- ddd_ai.md#nastroje – „formát [AGENTS.md] dnes zastřešuje Linux Foundation“ → „od prosince 2025 formát spravuje Agentic AI Foundation pod Linux Foundation“ (linuxfoundation.org press 9. 12. 2025).
- ddd_ai.md – časově nestabilní formulace ukotveny: „Akademický výzkum teprve začíná“ → „k září 2026 málo prací“; Newman „zatím“ → „do září 2026“; Brandolini „zatím jediný doklad“ → „k září 2026“; Fowler „opakovaně připomíná“ → „(například v prosinci 2025)“; otevřená otázka „Cursor rules a CLAUDE.md jsou ad hoc řešení“ (po standardizaci AGENTS.md neplatí) → „sjednocují formát souboru, ne jeho obsah“.
- Ověřeno bez změny (24. 9. 2026): Packagist `symfony/ai-platform|agent|bundle|store` v0.13.0 (30. 8. 2026), `php-llm/llm-chain` abandoned → `symfony/ai-agent`, `symfony/ai` neexistuje; TW Radar context engineering Assess (11/2025) → Adopt (4/2026); Rails 8.1 „lingua franca of AI“; Joshi článek na martinfowler.com (14. 7. 2026) a Tune 13. 8. 2026 existují; Evans DDD Reference „Many projects do modeling work without getting much real benefit in the end“ (pain_points #tym); Doctrine ORM 3 UPGRADE: repozitář musí dědit `EntityRepository` (anti_patterns #infra-spatne); `qossmic/deptrac` abandoned → `deptrac/deptrac`.

## Opravené rozpory uvnitř kapitoly
- anti_patterns: 21.08 neměla „Rozpoznávací znak“ jako ostatní sekce → „Příznaky:“ převedeny na **Rozpoznávací znak.** (sedí se shrnující tabulkou).
- anti_patterns: warn callout Primitive Obsession opakoval větu o záměně `$orderId`/`$userId` z předchozího calloutu → vypuštěna.
- ddd_pain_points: FAQ „špinavý EntityManager při dlouhých transakcích“ × A2 (problém je v rámci requestu) → „během requestu“.
- ddd_pain_points D1: „Když Command plní formulář“ (obrácený směr) → „Když Command vzniká přímo ve formuláři“; „Doménový kód s ní [Command] pracuje“ → „Handler“.
- ddd_ai: „anemický“ → „anémický“ (pravopis knihy); „context mapa“ → „Context Mapa“ (rozhodnutí K-25).

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- ddd_pain_points.md#d2-api-platform a #d1-form používají `App\Ordering\Application\Command\PlaceOrderCommand` (varianta s `orderId`); event_storming.md:249 a architectural_styles.md:267 také `PlaceOrderCommand`, kanonicky je `PlaceOrder` (outbox_pattern.md:590). Docblock v kap. 20 vysvětluje jen rozdíl v `orderId`, ne v názvu. Návrh: v kap. 20 přejmenovat na `PlaceOrderWithId` (nebo doplnit do docblocku, proč jiný název), v kap. 04/09 sjednotit na `PlaceOrder`.
- anti_patterns.md 21.04 `App\Ordering\Domain\Model\Customer` importuje `App\UserManagement\Domain\ValueObject\Email` (závislost Ordering → UserManagement domény) – tentýž import je v knize na ~10 místech; drobnost, ale v kapitole o hranicích kontextů by si ho čtenář mohl vyložit jako vzor. Návrh: komentář, nebo Email do SharedKernel.

## Jazyk a styl – souhrn
Cca 120 zásahů (anti_patterns ~35, ddd_pain_points ~50, ddd_ai ~40). Typické vzory: anglicismy v próze (read-heavy, API response, throughput, per-aggregate, guard conditions, type field, BDD-style, benefity, regression, dispatch/receive, runtime výjimky), „napříč“ (7×), „legitimní“/„explicitní“ v nadbytku, řečnické otázky („Rozhodli jste se…? Pak…“, „V okrajových případech? V porušení…?“, „Jak měřit…? Jak ověřit…?“), staccato a pointy („Žádná výjimka, žádný záznam v logu.“, „A nakonec hranice.“, „Stejná otázka, jiný výsledek.“, „A ten přijde častěji.“), zdvojené zdůvodnění, gramatika („rozeseta“, „Výzvou je … databáze, autentizace“, „jejíž výstup“ u collaborator, „obojí sdílí“).
Ukázky:
- „Rozhodli jste se pro doménový model? Pak v něm mají být pravidla. Rozhodli jste se pro Transaction Script? Pak žádnou anémii neřešíte…“ → „Po volbě doménového modelu v něm mají být pravidla. U Transaction Scriptu žádná anémie nehrozí; rozhodnutí pro něj ale musí jít pojmenovat…“
- „Evans ji proto označuje za zpravidla neměnnou, protože zaznamenává něco minulého“ → „Evans ji proto označuje za zpravidla neměnnou [6]“
- „V read-heavy akcích (příprava dat pro API response…)“ → „V akcích, které převážně čtou (příprava dat pro odpověď API…)“
- „Zpráva odešla do async fronty. Worker běží. Handler se ale nikdy nezavolal. Jak zjistit, kde zpráva skončila?“ → „Zpráva odešla do asynchronní fronty a worker běží, handler se ale nezavolal. Kde zpráva skončila, ukáže následující postup.“
- „Explicitní metoda pro každý přechod ověří… Tři kroky v jedné metodě, žádný setter navenek.“ → „Každý přechod má vlastní metodu, která ověří jeho platnost, změní stav a zaznamená doménovou událost; setter navenek nezbude.“
- „Fowler zdůrazňuje, že nedeterminismus LLM od základu mění způsob, jakým přemýšlíme o testování. Stejná otázka, jiný výsledek. … stejný vstup, stejný výstup, vždy.“ → „Podle Fowlera nedeterminismus LLM mění samotné uvažování o testování. Tradiční testování předpokládá, že stejný vstup dá vždy stejný výstup, a u AI komponent to neplatí.“
- „Vývojář, který nechápe doménu, nepíše správné testy, a AI pak ty testy plní falešně pozitivním kódem.“ → „…napíše špatné testy a AI pak dodá kód, který jimi projde, a přesto je chybný.“
- „Management vidí … ale ne benefity. Vývojáři neumí výhody přeložit do jazyka, který rozhodující osoby slyší.“ → „… ale ne přínosy. Vývojáři přínosy často neumějí přeložit do jazyka, kterému rozhodující lidé rozumějí.“

Kontroly: lint-php-snippets (anti_patterns 14 bloků, ddd_pain_points 14, 0 chyb); kotvy beze změny (diff prázdný u všech tří); check_anchors OK; check_faq_yaml OK; check_tonality 0 nálezů. `modified: 2026-09-24` u všech tří. Necommitováno.

## NEJISTÉ (neopraveno)
- anti_patterns.md#prilis-velky-agregat – „Vernon pojmenovává obě selhání: agregát složený pro pohodlí kompozice je moc velký, rozebraný na entity přestane chránit invarianty“ – druhou polovinu jsem v Effective Aggregate Design výslovně nedohledal (Vernon klade důraz na „true invariants“, ne na pojmenované selhání „příliš malý“).
- ddd_pain_points.md#e1-management – „Žádná studie souvislost mezi architektonickým stylem a chybovostí nedoložila“ – absolutní tvrzení, neověřitelné (existují studie modularity vs. defekty); návrh: „přesvědčivě doložená není“.
- ddd_pain_points.md#c3-acl – „Fakturoid [vrací] vlastní DTO“ – oficiální klient fakturoid-php vrací podle mých znalostí Response s tělem (pole/stdClass), ne DTO; neověřeno.
- ddd_ai.md#ubiquitous-language – „Předpověď odpovídá tomu, jak velké firmy AI platformy staví“ – bez zdroje.

---
<!-- reports/g11.md -->
# g11 – templates/ddd/: glossary, cheat_sheet, index, hub_* (7×), about, resources

Ověření: `php bin/console lint:twig templates/ddd/` → OK (13 souborů). `check_tonality.php` na změněných šablonách: 0 nálezů.
Atributy `id` v glossary a cheat_sheet se nezměnily (diff id před/po je prázdný). URL, ARIA, třídy ani Twig syntaxe změněné nejsou.
Jedinou výjimkou je hodnota pole `_path` v cheat sheetu (viz níže). `article_modified_time` jsem podle zadání neměnil.

## Opravené nepravdy / věcné chyby
- glossary#term-architektonicky-test: zdroj „Richards & Ford, *Fundamentals of Software Architecture* (2020), kap. „Architecture Decision Records and Fitness Functions““. Taková kapitola v knize není. → „kap. 6 „Measuring and Governing Architecture Characteristics“ (fitness functions)“. Ověřeno podle obsahu knihy (thoughtworks.com, výpisky na danlebrero.com). ADR jsou v kap. 19.
- resources#official-docs (Messenger): „Bez Messengeru se CQRS v Symfony neimplementuje.“ To není pravda. → „Kniha na Messengeru staví command a query bus.“
- resources#books (Khononov): „Aktualizovaný druhý pohled po 18 letech praxe“. Khononov nemá 18 let praxe, 18 let je odstup od Evanse. → „Pohled s odstupem 18 let od Evansovy knihy.“
- resources#articles: „Herberto Graca“ → „Herberto Graça“ (stejně jako kap. 09).
- cheat_sheet#must-read-talks-heading: délky přednášek ověřené na YouTube (lengthSeconds):
  - Young: 3828 s, dřív „~75 min“ → „~65 min“, doplněno „Code on the Beach 2014“;
  - Bogard: 3720 s, dřív „~45 min“ → „~60 min“, doplněno „NDC“;
  - Evans: 3367 s → „~55 min“, doplněno „keynote DDD Europe“.
- hub_synthesis: deck uváděl příklady „e-shopu, fakturace a inventáře“. Chapters.php i kap. 23 mají e-shop, blog a správu uživatelů → opraveno.
- hub_architecture: deck uváděl „**Vertikální slice**“ jako samostatnou položku, přestože hub má jen 3 kapitoly (09–11). Vertical Slice je součást kap. 09. Přepsáno na „Architektonické styly … ukazují i vertikální řezy“.
- hub_practice: „**Microservices a Bounded Contexts**“ a „**Pain points**“ neodpovídaly názvům kapitol. → „DDD a microservices“, „Kde to bolí“. „Testování ukazuje, jak vypadá zdravý běh“ nic neříkalo, nahrazeno popisem z katalogu (unit, integrační, asynchronní, architektonické testy).
- hub_reference: „shrnuje rozhodovací stromy“. Cheat sheet má jen jeden strom → „rozhodovací strom vzorů“.

## Opravené rozpory uvnitř kapitoly
- glossary#term-port, #term-adapter: `UserRepositoryInterface`, `EmailSenderInterface` → `UserRepository`, `EmailSender`. Kniha píše rozhraní bez přípony Interface (`interface UserRepository` v kap. 10 a 18). `PlaceOrderUseCase` není v kap. 09 rozhraní, ale `final class`. Proto „rozhraní use casu jako PlaceOrderUseCase“ → „vlastní rozhraní use casu“.
- glossary#term-kompenzace: příklady `CancelPayment`/`AuthorizePayment` a `RestoreInventory`/`ReserveInventory` se v knize nevyskytují. → `RefundCustomer`/`ChargeCustomer` a `ReleaseStock`/`ReserveStock` (tabulka v sagas.md:128–129).
- cheat_sheet#path-junior-heading: průchod pro juniora vedl přes 01 → 06 → 09 → 16 (Read modely, projekce a výkon) s cílem „rozumět slovníku“. To se nehodilo k cíli a rozcházelo se s předmluvou (#cesta-junior: 01, 06, 07, 10, 17). Pole `_path` jsem sladil s předmluvou: what_is_ddd, basic_concepts, aggregate_design, implementation_in_symfony, testing_ddd. Doba čtení se dopočítá sama. **Jde o jedinou změnu mimo viditelný text: hodnoty v Twig poli.**

## Rozpory s jinými kapitolami (NEEDITOVÁNO)
- architectural_styles.md:393 `interface PlaceOrder` (driving port s `handle(PlaceOrderInput)`) × kanonický command `PlaceOrder` + `PlaceOrderHandler` (CLAUDE.md / předchozí revize). V kap. 09 tak `PlaceOrder` znamená dvě různé věci. Návrh: port přejmenovat například na `PlacesOrders` nebo `PlaceOrderPort`.
- implementation_in_symfony.md (#dispatcher-vs-messenger-heading) doporučuje pro in-context listenery EventDispatcher. Kanonické rozhodnutí (dispatch přes in-process `event.bus` pod `doctrine_transaction`) a kód na ř. ~1735 (`#[Target('event.bus')]`) používají Messenger. Resources (Event Dispatcher) jsem nechal ve shodě s textem kap. 10. Kapitolu je třeba sjednotit s kanonickým rozhodnutím.
- cheat_sheet: průchody pro architekta, migraci a tech leada se liší od cest v předmluvě (#cesta-architekt, #cesta-migrace, #cesta-techlead). Persony jsou ale definované jinak („na novém projektu“, „před releasem“), proto je nechávám. Pokud má platit jedna sada cest, je třeba rozhodnout, která je zdroj pravdy.

## Jazyk a styl – souhrn
Asi 45 zásahů. Typické vzory: kalky („papír“ = paper, „destilace“, „dává smysl“, „nota“, „čítanka“, „party“, „servisa“, „verifikuje“), shoda rodu („Sbírka … Stručné a hutné“, „implementace … dobré“, „platforma … Užitečné“), dvojznačný slovosled („To snižuje jednotné pořadí zamykání“), opakování slova ve větě, meta-věty („Tato sekce řeší“, „Zde je krátký medailon“), duplicitní věty mezi úvodem a calloutem („Tabulka záměrně nepokrývá…“, „Tento seznam není vyčerpávající“), zakázané „moderní“, anglicismy (eventy, slicing, event-sourced) a překlep „uložišti“.
Ukázky před → po:
1. „Cenou je nižší propustnost a riziko uváznutí (deadlocku). To snižuje jednotné pořadí zamykání a krátké držení zámků.“ → „Cenou je nižší propustnost a riziko uváznutí (deadlocku), které se snižuje jednotným pořadím zamykání a krátkým držením zámků.“
2. „Vzor persistence, který ukládá stav jako append-only sekvenci doménových událostí místo aktuálního stavu. Aktuální stav agregátu vznikne…“ → „Vzor persistence, který místo aktuálního stavu ukládá append-only sekvenci doménových událostí. Stav agregátu vznikne…“
3. „Sbírka původních textů od autora pojmu CQRS. Stručné a hutné – definuje…“ → „Sbírka původních textů autora, který pojem CQRS zavedl. Hutně vymezuje…“
4. „Sem patří vše, co se v hlavním textu nevejde, ale bez čeho je hlavní text nepochopitelný.“ → „Sem patří vše, co se do kapitol nevešlo, ale čtení kapitol usnadňuje.“
5. „…doplňují slovník o vzory, bez kterých model brzy anemizuje.“ → „…přidávají vzory, bez kterých se doménová logika brzy přestěhuje do servisních tříd.“
6. „Vstupní brána celé příručky. Začíná se filozofií… Pokud nečtete od začátku, sem se vracejte vždy, když…“ → „Úvodní část příručky. Začíná filozofií… Sem se vyplatí vracet pokaždé, když…“
7. „Příklady: převod měn, …, převod peněz mezi účty.“ → „Příklady: přepočet měn, …, převod peněz mezi účty.“
8. „…driving adaptér, který patří do …Http\. Ne do Application a už vůbec ne do Domain.“ → „…patří do …Http\, nikoli do Application, natož do Domain.“

## NEJISTÉ (neopraveno)
- **resources#communities, „Domain-Driven Design Czechia“ (meetup.com/ddd-czechia/): skupina neexistuje.** Meetup vrací „the group you're looking for doesn't exist“ a vyhledávání českou DDD skupinu nenašlo. Položka tak popisuje neexistující komunitu. URL jsem podle mantinelů neměnil. Návrh: položku odstranit, nebo ji nahradit Virtual DDD (https://virtualddd.com/, Meetup: virtual-domain-driven-design-meetup).
- resources#courses, CodelyTV „DDD in PHP“: URL codely.com/blog/ddd vede na blog nebo navigaci, ne na kurz. Odkaz je třeba ověřit, případně změnit na stránku kurzu.
- resources#repositories, Sylius: „postavená na Symfony s DDD principy“. Sylius se prezentuje hlavně jako BDD a DDD nepoužívá důsledně. Tvrzení bych zmírnil.
- resources#articles, Fowler Bounded Context: „autora, který ho zpopularizoval mimo DDD komunitu“ nejde ověřit.
- resources: SymfonyCasts Messenger je v seznamu dvakrát (Videa 05 a Kurzy 04, stejná URL). Jednu položku doporučuji odstranit (strukturální změna, neprovedeno).
- resources.html.twig má `article_modified_time` 2026-05-03. Podle zadání neřešeno, ale obsah se změnil.

---
<!-- reports/consistency.md -->
# Konzistence napříč knihou – kolo 2 (stav po redakci g1–g11, necommitováno)

Rozsah: content/chapters/*.md, src/Catalog/Chapters.php, templates/ddd/*.twig.
Nic needitováno. Citace ověřené proti aktuálnímu stavu souborů. Místo čísla řádku je uvedena nejbližší kotva.

Vynecháno:
- vše vyřešené v docs/revize-knihy-2026-09-24.md (R-1…R-20, K-1…K-26),
- co redaktoři g1–g11 už opravili,
- přiznané odchylky a záměrné anti-vzory,
- sufix `…Command` u příkazů (PlaceOrderCommand, CancelOrderCommand, RefundOrderCommand, ActivateUserCommand, ConfirmTransferCommand, CapturePaymentCommand, RenameArticleCommand; k tomu analogicky `GetProductQuery` v context_mapping#ohs). Ten řeší koordinátor.

## Strojové kontroly (vše OK)

- **Interní odkazy.** Vlastní skript prošel všechna `](/cesta#kotva)`, `href="/…#…"`, `](#kotva)` v kapitolách a `path('route') }}#kotva` / `href="#…"` v šablonách. Cesty i kotvy existují (včetně hub stránek z DddController a kotev `id="…"` v twig). `scripts/check_anchors.php`: OK.
- **Číslované odkazy.** Každé „NN.MM“ v próze míří na existující nadpis. U odkazů s číslem v textu (`[sekce 11.04](#use-case-voter)`) číslo odpovídá cílovému nadpisu, u podsekcí jeho rodiči.
- **Frontmatter proti Chapters.php.** `chapter_number` i `reading_time` sedí s katalogem u všech 25 kapitol. `fig=` u diagramů i čísla nadpisů odpovídají číslu kapitoly.
- **Ukázky kódu.** `lint-php-snippets`: 355 bloků, 0 chyb. `check_use_statements`, `check_messenger_routing`, `check_named_arguments`, `check_static_calls`, `check_duplicate_listings`: OK.

---

# A. Věcné rozpory v kódu a tvrzeních

## [C-1] Kontrakt publikované `OrderPlaced`: `additionalProperties: false` vs. „nové pole subscriber ignoruje“ – vysoká
- context_mapping.md#published-language: JSON Schema obsahuje `"additionalProperties": false` a pole `"totalAmount"`. Konzument proti schématu validuje a při neshodě hodí `UnrecoverableMessageHandlingException`. Text k tomu: „Když Ordering BC potřebuje nové pole (například `shippingAddressId`), publikuje `order-placed-v2.json`“. `description` schématu zní „Doménová událost vyvolaná po úspěšném vytvoření objednávky“.
- microservices_and_ddd.md#symfony (odrážka „Verzování payloadu“): „publisher přidá pole a subscriber ho, dokud ho nepotřebuje, ignoruje. Koordinovaný release odpadá.“ Wire formát v `OutboundEventSerializer` nese `'totalAmountCents'`.
- Rozpor:
  - Při `additionalProperties: false` každé nové pole shodí konzumenta do DLQ.
  - Kapitoly se liší i ve jménu pole (`totalAmount` × `totalAmountCents`).
  - Publikovaný kontrakt je integrační událost, ne doménová (basic_concepts#domain-events).
- Návrh: kanonicky tolerant reader, jak to popisuje i event_sourcing#verzovani-udalosti.
  - Ve schématu kap. 03 povolit `additionalProperties`, nebo jednou větou říct, že konzument validuje jen povinná pole. Verze v2 jen pro rozbíjející změny.
  - Pole sjednotit na `totalAmountCents` (Money = celé číslo v haléřích).
  - `description` → „Integrační událost…“.

## [C-2] Architektonická pravidla zakazují závislosti, které má kanonický kód – vysoká
- lesser_known_patterns.md#mod-phparkitect: „`App\Ordering\Domain` nesmí importovat nic ze `Symfony`“. Pravidlo 1: `App\Ordering` nesmí záviset na `App\Shipping`.
- anti_patterns.md#infra-spravne-heading: „`App\*\Domain` nesmí odkazovat na `Symfony\*`“.
- architectural_styles.md#onion-symfony-heading: „Žádná třída v `Domain/` nepoužívá `use Symfony\…`“.
- Proti tomu:
  - basic_concepts.md#entity-identity: všechny ID mají `use Symfony\Component\Uid\Uuid;` (konvence CLAUDE.md).
  - aggregate_design.md#references-by-id: `use App\Shipping\Domain\ValueObject\ShipmentId;` v kanonickém `Order`.
  - Výjimku pro cizí identitu zná jen implementation_in_symfony#project-structure.
- Návrh: do všech tří pravidel doplnit výjimky `Symfony\Component\Uid` a identifikátory cizích kontextů (`App\*\Domain\ValueObject\*Id`). Kanonická volba (`Uuid::v7()`, reference přes ID) má přednost před pravidlem.

## [C-3] Vrstvy: aplikační a sdílený kód importuje, co pravidla kap. 08/17 zakazují – střední
- Pravidla:
  - lesser_known_patterns.md#mod-phparkitect: „`App\Ordering\Application` nesmí znát `App\Ordering\Infrastructure`“.
  - testing_ddd.md#architektonicke-testy (Deptrac): Application → Domain, Shared; Presentation → Application, Shared; „Shared nezávisí na ničem projektovém“.
- Porušení:
  - authorization_in_ddd.md#voter-handler-heading a #async-is-granted-for-user: handlery mají `use App\Ordering\Infrastructure\Security\OrderVoter;` a `use App\Identity\Infrastructure\Security\SecurityUserProvider;`.
  - authorization_in_ddd.md#field-query-heading: `App\Ordering\Application\ReadModel` importuje `SecurityUser` z Infrastructure a přímo `Doctrine\DBAL\Connection`. Kap. 12 a 24 přitom dávají DBAL read modely do `Infrastructure/ReadModel`.
  - authorization_in_ddd.md#aggregate-403-vs-409-heading: `namespace App\SharedKernel\Infrastructure\Http;` importuje výjimky z `App\Ordering\…`, tedy Shared závisí na kontextu.
  - implementation_in_symfony.md#controller-example-heading, cqrs.md#buses-example-heading: controller (Presentation) importuje `App\UserManagement\Domain\Exception\DuplicateEmailException`.
- Návrh:
  - V handlerech použít řetězec atributu (`'order.cancel'`, jako `OrderController`) nebo konstanty v Application.
  - Listener 403/409 přesunout do `App\Ordering\Infrastructure\Http`.
  - Read model rozdělit na rozhraní a implementaci v Infrastructure/ReadModel.
  - V Deptrac povolit Presentation → Domain (výjimky). Kap. 10.14 to controlleru přímo ukládá.

## [C-4] Kanonický `PlaceOrder` pod jiným FQCN v Praktických příkladech – střední (kód nefunguje)
- practical_examples.md#cart-checkout-to-order: „`use App\Ordering\PlaceOrder\Command\PlaceOrder;`“ a „Command `PlaceOrder` má stejný tvar jako v kapitole Outbox Pattern“. Strom v #e-commerce-structure: `PlaceOrder/{Command, Controller, Listener}/`.
- Kanonicky je to `App\Ordering\Application\Command\PlaceOrder` (outbox_pattern#place-order-handler-heading, cqrs, sagas). Messenger směruje podle FQCN, takže handler z kap. 15 zprávu nedostane (`NoHandlerForMessageException`).
- Příbuzná odchylka: anti_patterns.md#infra-spravne-heading `namespace App\UserManagement\Application\Command;`. UserManagement je přitom kanonicky feature slice (`Registration/Command`).
- Návrh: kanonický namespace. Listener do `App\Ordering\Application\Listener`, strom Ordering na vrstvy Domain/Application/Infrastructure.

## [C-5] Integrační test volá metodu, kterou Doctrine repozitář nemá – střední (kód nefunguje)
- testing_ddd.md#integracni-testy (`DoctrineUserRepositoryTest`): „`$this->assertFalse($this->repository->existsByEmail($email));`“.
- implementation_in_symfony.md#repository-example-heading: `DoctrineUserRepository` má jen `save`, `findById`, `findByEmail`. Metodu `existsByEmail()` má jen InMemory fake (testing_ddd#test-doubles).
- Návrh: `assertNull/assertNotNull($this->repository->findByEmail($email))`.

## [C-6] Transakce přes dva agregáty: A1 v kap. 20 je „přijatelná výjimka“, kap. 07 totéž označí za anti-vzor – střední
- ddd_pain_points.md#a1-transakce: „Jde o **přijatelnou výjimku z pravidla jeden agregát = jedna transakce**, pokud oba agregáty leží ve stejném Bounded Contextu a ve stejné databázi.“ K tomu `ConfirmTransferService` s `wrapInTransaction`.
- aggregate_design.md#transactional-consistency: tentýž tvar má komentář „// ANTI-VZOR: transakce přes dva agregáty“.
- aggregate_design.md#breaking-the-rule: čtyři Vernonovy výjimky, „stejný BC + stejná DB“ mezi nimi není.
- implementation_in_symfony#payment-handler-heading: „žádná z Vernonových výjimek ji nekryje“.
- Návrh: v A1 napsat, že společná DB je technický předpoklad, ne ospravedlnění. Legitimní je jen výjimka z #breaking-the-rule. Jinak jde o signál špatné hranice agregátu a nastupuje sága.

## [C-7] „flush() proběhl, pak pád → ztracená událost“ proti kanonickému pravidlu flush ≠ commit – střední
- ddd_pain_points.md#b1-outbox: „Uložíte agregát (`flush()` proběhne úspěšně), a než stihnete odeslat doménovou událost do Messengeru, server spadne. Událost se ztratí“. Nadpis B1 i FAQ mluví o „doménových událostech“ v outboxu.
- sagas.md#outbox-pattern-heading: „pád procesu mezi commitem a odesláním“.
- outbox_pattern.md#naive-publish-heading: tento scénář „Nastává tam, kde commit proběhne před dispatchem – typicky bez middlewaru `doctrine_transaction`“. Pod kanonickým middlewarem hrozí opak, phantom event. Do outboxu jde integrační událost (K-17).
- Návrh: B1 a warn v 14.07 formulovat podle 15.01 (pod `doctrine_transaction` je `flush()` jen zápis SQL a hrozí phantom event). „Doménové“ u outboxu → „integrační“.

## [C-8] Correlation ID: čtyři různé významy a middleware, který maže kanonický stack – střední
- Glosář `id="term-korelacni-id"`: ID jedné uživatelské akce, přenáší se v HTTP hlavičce a vkládá se do každé události.
- event_sourcing.md#event-store-php-heading: „Correlation ID drží celý request“. Fallback `?? $event->eventId` ale dá každé události vlastní ID.
- sagas.md#korelacni-id-heading: „nese stejné korelační ID, typicky `orderId`“.
- ddd_pain_points.md#b2-code-heading:
  - `?? new CorrelationIdStamp((string) Uuid::v7())` vznikne pro každou zprávu zvlášť a do odvozených událostí se nepropaguje.
  - YAML výřez nastaví `command.bus` jen tento middleware. Podle cqrs#middleware-registrace-heading („Nahrazuje jen seznam middleware“) tím z busu zmizí `validation` i `doctrine_transaction`.
- Návrh:
  - Kanonicky: correlation = request nebo spouštěcí zpráva, propaguje se do odvozených zpráv. Causation = bezprostřední příčina.
  - `orderId` v ságách nazvat byznysovým korelačním klíčem.
  - V B2 doplnit propagaci a plný seznam middleware (`validation`, `doctrine_transaction`).

## [C-9] Projekce: synchronní, nebo asynchronní? – střední
- cqrs.md#denorm-projekce-heading: docblock „Asynchronní projektor“. Hned pod ním „na synchronní sběrnici běží posluchači v pořadí registrace“ a kanonický routing `OrderShipped`/`OrderCancelled` neroutuje.
- cqrs.md (odstavec „Kdo doménové události odešle“ za #idempotence-heading): synchronní cesta jen „Pro vývoj a méně kritické projekce“ a riziko „Když commit selže, projekce dostane událost, která se nestala“.
- basic_concepts.md#aggregate-root-lifecycle: „Synchronním posluchačům ve stejném procesu to nevadí“ (kanonické rozhodnutí).
- performance_aspects.md#projekce-provoz-heading: „projekce běží asynchronně, takže … je vždy nějaké zpoždění“.
- Návrh: podle kanonického rozhodnutí je synchronní projekce pod `doctrine_transaction` plnohodnotná volba. Riziko před commitem se týká jen asynchronního routingu. Opravit docblock, odstavec v cqrs a slovo „vždy“ v kap. 16.

## [C-10] Deduplikace / Inbox: tři schémata a dva podpisy – střední
- outbox_pattern.md#read-model-updater-heading: „UNIQUE je proto kompozitní `(event_id, consumer)`“; `isProcessed(Uuid, string)`, `markProcessed`.
- microservices_and_ddd.md#symfony-handler-heading: „unikátní index nad `eventId`“, `wasProcessed($event->eventId)`, jiný namespace (`Infrastructure\Idempotency`).
- ddd_pain_points.md#b3-code-heading: `processed_messages(idempotency_key)` v middlewaru busu bez consumera. U události s více handlery přeskočí všechny.
- testing_ddd.md#testovani-asynchronnich-toku: `eventId: '0190a5c2-…'` jako string, ale kanonický podpis bere `Uuid`.
- Návrh: kanonické je rozhraní kap. 15. Kap. 19 převzít, nebo jednou větou zdůvodnit jediného konzumenta. B3 vymezit na příkazy (jeden handler) s odkazem na `/outbox-pattern#inbox`. V testu `Uuid::fromString()`.

## [C-11] Relay počítá výpadek brokeru do pokusů, text tvrdí opak (uvnitř kap. 15) – střední
- outbox_pattern.md#dispatch-command-heading: `catch (\Throwable $e) { $this->outbox->markFailed($row->id, $e->getMessage()); }`.
- outbox_pattern.md#backpressure-heading: „Chybu spojení s brokerem proto relay do `attempts` nezapočítává“. FAQ totéž.
- Návrh: v relayi odlišit `TransportException` (přerušit cyklus, backoff) od chyby konkrétní zprávy (`markFailed`).

## [C-12] Repozitáře: `get()` s výjimkou, `find()` s null, `getById()` a dotazy pro obrazovky – střední
- basic_concepts.md#repositories:
  - „proto `get()` vrací `Order` a hází výjimku místo `null`“,
  - „Dotazy typu „všechny objednávky zákazníka“ do něj nepatří“,
  - pravidlo 3 „Dotazy pro obrazovky sem nepatří“.
- Porušení:
  - architectural_styles.md#hexagonal-priklad-heading: port má `public function findByCustomer(CustomerId $customerId): array;`.
  - migration_from_crud.md#repository-interface-heading: `public function findActiveUsers(): array;`.
  - performance_aspects.md#agregat-hranice: `findHeaderById(OrderId $id): ?Order` s docblockem „pro seznam objednávek“ a `findWithItemsById(): ?Order`.
  - performance_aspects.md#n-plus-1-problem: `OrderQueryRepository` („Čtecí strana“) vrací `@return Order[]`. Tatáž kapitola v #eskalace-heading: „Query handlery nepoužívají doménové repozitáře“.
  - Názvy metod: `getById()` (lesser_known_patterns#fac-class), `find() ?? throw` (architectural_styles#onion-priklad-heading), `findById()` + null-check (case_study#assign-task-handler-heading), `get()`/`getWithItems()` (ddd_pain_points#a4-lazy-loading).
  - testing_ddd.md#integracni-testy tvrdí, že „Projekt si vybere jednu variantu a drží ji“.
  - Vrstva výjimky: implementation_in_symfony#exception-types-heading řadí `UserNotFoundException` mezi „Aplikační výjimky“, všechny `*NotFoundException` v knize ale leží v `Domain\Exception`.
- Návrh:
  - Načtení podle identity v command handleru: `get(Id)` s `XNotFoundException::withId()` v `Domain\Exception`.
  - `find…(): ?X` jen tam, kde absence není chyba (`findByEmail`, listenery).
  - Seznamové dotazy do read modelu. `findByCustomer`/`findActiveUsers` vypustit, nebo přiznat jako výjimku.
  - `OrderQueryRepository` vrací DTO. V 10.12 přesunout NotFound mezi doménové výjimky.

## [C-13] Validace formátu a doménová politika: C1 v kap. 20 proti 06.04 a kap. 10 – střední
- ddd_pain_points.md#c1-validace: „**Formátová validace** | API / formulářová vrstva (Symfony Validator) | E-mail má platný formát“. Dále „**Doménová politika** | Domain Service nebo Application Service“. „cena nesmí být záporná“ je tam vedená jako invariant.
- basic_concepts.md#vo-validation: porušení formátu (neplatný e-mail, záporná částka) hlásí konstruktor VO.
- implementation_in_symfony.md#validace-kde-heading: „formát vynucuje hodnotový objekt vždy… Symfony Validator tutéž kontrolu opakuje“. #application-services: „žádná doménová pravidla v ní nežijí“.
- Návrh: v C1 formát → konstruktor VO (Validator jen kvůli hlášce), politika → agregát nebo doménová služba, zápornou částku přesunout do řádku formátu.

## [C-14] Testy handlerů: DB, fake, nebo mock? – střední
- implementation_in_symfony.md#symfony-idiomy-asalias: „Aplikační handlery, které se opírají o repozitář, pokrývá kernel test s testovací databází… in-memory mock je negarantuje.“
- testing_ddd.md#filozofie-testovani: handlery „s fake (InMemory) repozitáři; ověření, že handler volá správné metody repozitáře s očekávanými argumenty“. Proti tomu tatáž kapitola v #test-doubles: „Mock hlídá implementační detail…, fake chování“.
- lesser_known_patterns.md#ds-srovnani: „Unit s mockovanými repozitáři“.
- Návrh: kanonická je kap. 17 (unit test s InMemory fake, překlad unique constraintu integračně proti DB). Kap. 10 a 08 upravit, v kap. 17 vypustit „volá správné metody“.

## [C-15] Kdo generuje `OrderId` v továrnách – střední
- ddd_pain_points.md#a5-identity: „identita a vlastník vznikají mimo agregát a vstupují do továrny“.
- aggregate_design.md#references-by-id: `placeWithFirstItem()` volá `self::place(OrderId::generate(), $customerId)`. Stejně lesser_known_patterns#fac-static (`id: OrderId::generate()`). ddd_pain_points#d2-code-heading: kanonický PlaceOrder identitu „naopak generuje v agregátu“. cqrs#command-navratova-hodnota-heading: „identitu přiděluje továrna agregátu“.
- Návrh: A5 upřesnit: `place()` identitu přijímá, odvozené továrny ji generují přes `OrderId::generate()`. V obou případech v aplikaci před uložením, ne v DB.

## [C-16] Ordering jednou Core, jindy Supporting – střední
- architectural_styles.md#hybrid-priklad-heading: „├── Ordering/ # CORE DOMAIN – plný Hexagonal“.
- subdomains.md#subdomeny-na-bc: „| Order Management | Supporting | Ordering BC |“. V #tri-kategorie: „Klasické příklady: správa objednávek v e-shopu“. Glosář `id="term-podporna-subdomena"`: „sledování objednávek v e-shopu“.
- Návrh: kanonická je kap. 02, protože klasifikaci definuje. V kap. 09 dát jako Core `Pricing/`, nebo jednou větou říct, proč je Ordering v tomto e-shopu Core.

## [C-17] Klasifikace Identity a Notifikací – střední až nízká
- team_topologies.md#scenar-scaleup: „Identity+Billing tým (4 lidi, sdílí 2 supporting BC)“. Tabulka téže kapitoly (#subdomain-mapping): „| **Generic** | Žádný vlastní tým |“.
- subdomains.md#subdomeny-na-bc: „| Identity / Auth | Generic | External IdP (Auth0) |“. Stejně glosář `id="term-genericka-subdomena"` a case_study.
- cqrs.md#cqrs-urovne-heading: „podpůrným kontextům (notifikace, administrace)“. Proti tomu when_not_to_use_ddd#hybrid-subdomain („**Generic** Notifikace“) a architectural_styles („Notifications/ # GENERIC“).
- Návrh: Identity i Notifikace všude Generic. V kap. 12 „podpůrným a generickým kontextům“.

## [C-18] Externí SaaS: Conformist, nebo ACL? – střední až nízká
- Glosář `id="term-konformista"`: „Typické použití je integrace s externím SaaS (Stripe, Auth0)“. context_mapping.md#conformist řadí SaaS mezi „Kdy Conformist zvolit“.
- Proti tomu:
  - subdomains.md#subdomeny-na-bc: Auth0 i Stripe „1:1 přes ACL“.
  - ddd_pain_points.md#c3-acl: Stripe za portem a adaptérem.
  - Warn callout v context_mapping#conformist: „Má-li downstream *jakoukoliv* doménovou logiku závislou na konzumovaných datech…, **patří sem ACL**“.
- Návrh: Conformist zúžit na reporting nebo průchozí kontext a oborové standardy. SaaS s navazující doménovou logikou → ACL (kap. 02, 20).

## [C-19] Případová studie: Partnership a Customer/Supplier popsané v rozporu s definicí v kap. 03 – střední
- case_study.md#architecture: „*Partnership*. Oba kontexty ovlivňují společný model členství v projektu. Změna kontraktu vyžaduje koordinaci obou týmů.“ O kus dál: „tým je jeden“. V #trade-off-shared-kernel-heading: „Tým i deploy jsou jen jeden“.
- context_mapping.md#partnership a glosář `id="term-partnership"`: „Modely zůstávají oddělené“. Jde o vztah týmů.
- case_study.md#architecture u Customer/Supplier: „TaskManagement se přizpůsobuje upstreamu“, „CommentManagement je downstream a přizpůsobuje se“. context_mapping.md#customer-supplier: downstream „**má hlas**“. Bez hlasu „sklouzne Customer/Supplier do Conformistu“.
- Návrh:
  - Partnership popsat jako koordinovaný vývoj dvou oddělených modelů a „týmů“ nahradit „kontextů“. Sdílený model je Shared Kernel.
  - U Customer/Supplier popsat, že downstream požadavky formuluje a upstream rozhoduje o dodání. Jinak vztah přejmenovat na Conformist.

## [C-20] Kolik BC unese tým; případová studie má 5 BC a 1 tým – střední
- team_topologies.md#cognitive-load-rule: „| 5 lidí | 1 BC (max 2 malé) |“ a „Tým s 5+ BC … Signál pro rozdělení“. Tatáž kapitola #scenar-startup: „Startup, 5 lidí … 1 stream-aligned tým, 2–3 malé BC“ (rozpor uvnitř kap. 05).
- case_study.md: pět BC a „tým je jeden“.
- Návrh: v kap. 05 sjednotit tabulku se scénářem A (např. „max 2–3 malé“). Do studie jednu větu, že ukazuje hranice modelu, ne týmovou topologii.

## [C-21] Shared Kernel: text „jen VO“, kód do `App\SharedKernel` dává i infrastrukturu – střední
- microservices_and_ddd.md#modular-monolith-symfony-heading: „Kniha do něj dává jen hodnotové objekty.“
- context_mapping.md#shared-kernel: „Shared Kernel obsahuje výhradně **doménový model**: VO, doménové události, doménové výjimky“. Utility knihovny podle téhož textu „*nejsou* Shared Kernel“. Glosář `id="term-sdilene-jadro"` totéž.
- Kód:
  - architectural_styles#konvence-heading: „`SharedKernel/` – … (abstraktní typy, výjimky, bus rozhraní)“ a `Bus/CommandBus.php`,
  - ddd_pain_points#b2-code-heading: `src/SharedKernel/Infrastructure/Messenger/CorrelationIdMiddleware.php`,
  - cqrs#query-bus-handle-trait-heading: `SharedKernel/Application/Query/QueryBus.php`,
  - event_sourcing: `SharedKernel/Domain/EventSourcedAggregate.php`,
  - authorization#aggregate-403-vs-409-heading (viz C-3).
- Návrh: v kap. 03, 19 a v glosáři říct, že namespace `App\SharedKernel` nese i technické bázové typy. Shared Kernel v Evansově smyslu je jen jeho doménová podmnožina (`Money`, `Currency`).

## [C-22] Práh provozní kapacity pro microservices se nedá splnit podle kap. 05 – střední
- microservices_and_ddd.md#kdy-modular-heading: „do platformy … jde méně než ~30 % vývojové kapacity“ je indikátor pro modulární monolit.
- team_topologies.md#scenare-summary-heading: „≈ 15 % v Platform teamu(ech)“. Poměr 50/30/20 tam slouží jako příklad „enterprise architecture inflation“.
- Vlastní e-shop kap. 19 (#priklad-eshop-heading, 30 inženýrů ve 4 stream-aligned týmech) práh nesplňuje, přesto ho kapitola označí za „obhajitelný“.
- Návrh: práh v kap. 19 formulovat kvalitativně („existuje aspoň malý platformní tým“), nebo ~10–15 % s odkazem na 05.07.

## [C-23] Bounded Context a nasazení – střední (zbytek K-2)
- subdomains.md#proc-subdomeny: „Bounded Context je *implementační* hranice: … typicky jeden tým s jednou nasazovací jednotkou.“
- what_is_ddd.md#benefits: „Bounded Contexty pak dovolí vyvíjet, nasazovat a škálovat části systému nezávisle na sobě.“
- team_topologies.md#summary (FAQ): „Ve zdravém stavu jsou izomorfní: 1 stream-aligned tým = 1 BC = 1 mikroservis (nebo modul v modulárním monolitu).“
- Proti tomu:
  - context_mapping.md#bc-modul-deployment: „Nasazovací jednotka je provozní rozhodnutí.“
  - microservices_and_ddd.md#mytus-pravda-heading: N:1 je modular monolith a 1:1 je jen výchozí hypotéza.
- Návrh:
  - kap. 02: „…jeden Ubiquitous Language, jeden konzistentní model a typicky jeden tým“, bez nasazovací jednotky,
  - kap. 01: „…vyvíjet nezávisle a v případě potřeby i samostatně nasazovat (kap. 19)“,
  - kap. 05 FAQ: „kryje se tým a BC; BC běží jako microservice i jako modul“.

## [C-24] Doménová služba a externí systém – nízká až střední
- basic_concepts.md (FAQ „Kdy použít Doménovou službu…“): „Koordinuje více agregátů, komunikuje s externím systémem nebo počítá nad kolekcí objektů.“
- lesser_known_patterns.md#ds-kdy-ne: „čtení z externího API – to nejsou doménové operace, ale infrastruktura“. #ds-srovnani „Volá perzistenci? Ne“. basic_concepts#domain-services „bez závislosti na repozitáři či databázi“.
- Návrh: FAQ → „…počítá nad daty, která dostane. Vstup z externího systému jí dodá volající nebo doménové rozhraní implementované v infrastruktuře.“

## [C-25] Anemic model, nebo Transaction Script v Supporting subdoméně – nízká až střední
- subdomains.md#tri-kategorie: „lehké DDD (často stačí *anemic* model nad Doctrine ORM)“. FAQ v #summary: „CRUD architektura se servisní vrstvou, anemic model a Doctrine ORM“.
- anti_patterns.md#anemicky-domenovy-model: anemický model = „platíte cenu doménového modelu bez jeho přínosu“, „U Transaction Scriptu žádná anémie nehrozí“. Tabulka v #shrnuti: „přiznaný Transaction Script v jednoduché subdoméně“.
- Návrh: v kap. 02 psát „Transaction Script / CRUD nad Doctrine entitami“, ne „anemic model“ (ten kniha jinde vede jako anti-vzor).

## [C-26] Doctrine custom typ „do dvou sloupců“ a Money jako příklad limitu Embedded – nízká až střední
- ddd_pain_points.md#a3-value-objects: problém Embedded u „VO s vlastní serializační logikou (Money = integer + string)“. Warn callout: „…nebo custom typ zapisující do dvou sloupců“.
- Custom typ DBAL mapuje právě jeden sloupec, dvousloupcový custom typ neexistuje. aggregate_design.md#symfony-doctrine mapuje Money jako `#[ORM\Embeddable]` a v doctrine.yaml píše: „Jednosloupcový custom typ by znemožnil SUM() i ORDER BY.“
- Návrh: vypustit „nebo custom typ zapisující do dvou sloupců“. Money z výčtu limitů Embedded vyjmout (kanonicky je Embedded).

## [C-27] `final` u `Order`: kanonický bez, výřezy s – nízká
- aggregate_design.md#references-by-id, basic_concepts, outbox_pattern, authorization, when_not_to_use_ddd (R-17 tam `final` kvůli shodě odstranil): `class Order extends AggregateRoot`.
- implementation_in_symfony.md#enum-usage-heading, #payment-aggregate-heading, anti_patterns.md#agregat-spravny-heading, ddd_pain_points.md#a5-code-heading, lesser_known_patterns.md#fac-static, event_storming.md#dl-mapping: `final class Order extends AggregateRoot`.
- aggregate_design.md (note „Entita mapovaná Doctrine může být `final`“) i kap. 10 přitom `final` obhajují a `User` je `final` všude.
- Návrh: kanonický `Order` udělat `final` (kap. 06, 07 a výřezy bez `final`). Menší zásah než odebírat `final` na šesti místech.

## [C-28] Holá `\DomainException` bez přiznání – nízká
- basic_concepts.md#money: `Money::add()` i `subtract()` → `throw new \DomainException(`. Stejný `Money` v context_mapping#shared-kernel zkratku komentářem přiznává.
- implementation_in_symfony.md#payment-aggregate-heading (callout „Správně“): `throw new \DomainException('Payment amount does not match order total.');`.
- Návrh: komentář „holá výjimka je zkratka“ jako v kap. 03, nebo `CurrencyMismatchException` / `PaymentAmountMismatchException`.

## [C-29] Kumulativní doctrine.yaml v kap. 07 bez typů z kap. 10 – nízká
- aggregate_design.md#symfony-doctrine: `filename="config/packages/doctrine.yaml (cílový stav po kapitole 15)"` mapuje `UserManagement` i `Identity`, v `types:` má ale jen `order_id`, `customer_id`, `product_id`.
- implementation_in_symfony.md#custom-type-registration-heading: „Klíče se přilévají k `types:` z kapitoly o agregátech“ (`email_vo`, `user_id`). Kdo převezme „cílový stav“, přijde o ně a mapování `User` spadne.
- Návrh: do cílového stavu doplnit `email_vo` a `user_id` s komentářem „z kap. 10“.

## [C-30] Testy a konfigurace používají transport `async`, který kanonická konfigurace nemá – nízká
- testing_ddd.md#testovani-asynchronnich-toku:
  - `async: 'in-memory://'`, `async: 'test://'`, `get('messenger.transport.async')`, `$this->transport('async')`,
  - tatáž sekce ale dál `get('messenger.transport.async_events')`,
  - `SendWelcomeEmail` nikde v knize není routovaný.
- performance_aspects.md (16.08): `messenger:consume async`.
- microservices_and_ddd.md#messenger-publisher-heading / #messenger-subscriber-heading: `async:` (samostatné služby – přijatelné, ale bez věty).
- sagas.md#messenger-yaml-heading, #choreografie-messenger-heading: výřezy `async_events`/`async_commands` bez `queue_name`, `command.bus` bez `validation`. Kanon upozorňuje, že bez `queue_name` „by šlo o jednu a tutéž“ frontu.
- Návrh:
  - v kap. 17 a 16 `async_commands` (SendWelcomeEmail je příkaz) a jedna věta o routingu `SendWelcomeEmail`,
  - v kap. 19 věta „samostatné služby mají vlastní konfiguraci“,
  - ve výřezech kap. 14 doplnit `queue_name` a `validation`.

## [C-31] Počet pokusů Messengeru: „tři pokusy“ vs `max_retries: 3` – nízká
- event_sourcing.md#out-of-order-heading („výchozí: 3 pokusy“), case_study.md#read-model-reconciliation-heading („výchozí tři pokusy“), sagas.md#idempotent-saga-transitions-heading („po třech pokusech v DLQ“).
- cqrs.md#async-example-heading „tři opakování“. `max_retries: 3` = 3 opakování, 4 pokusy.
- context_mapping.md#postup: SLA „dead letter queue po 3 retry“, přitom tatáž kapitola nastavuje `max_retries: 0`.
- Návrh: všude „tři opakování“, SLA v kap. 03 sladit s konfigurací.

## [C-32] Choreografie × orchestrace a „Process Manager (saga aggregate)“ – nízká
- microservices_and_ddd.md#saga-heading: „Vhodné pro jednoduché ságy s 2–3 kroky“ a „Process Manager (saga aggregate)“. Glosář `id="term-saga"`: „Choreografie stačí na krátké procesy, orchestrace … s mnoha kroky“.
- sagas.md#choreografie-stale-validni-heading: „Nerozhoduje počet kroků, ale tvar procesu … autorská heuristika“. #terminologicka-konvence: PM je orchestrační komponenta, ne agregát.
- cqrs.md#saga: „Vzor **Saga**, v orchestrované podobě označovaný **Process Manager**“ (už hlásil g6).
- Návrh: kap. 19, glosář a cqrs sladit s 14.04 a 14.00: tvar procesu místo počtu kroků, PM = komponenta řídící ságu, bez „saga aggregate“.

## [C-33] Kanonický `PlaceOrderHandler` doménové události nedispatchuje – nízká až střední
- outbox_pattern.md#place-order-handler-heading: po `releaseEvents()` jdou `OrderItemAdded`/`OrderConfirmed` na `=> null`. Komentář „Dílčí události zůstávají uvnitř kontextu Ordering“, ve skutečnosti ale zmizí.
- basic_concepts.md#aggregate-root-lifecycle: handler „vyzvedne zaznamenané události přes `releaseEvents()`“ a dispatchne je na `event.bus`.
- Návrh: v téže smyčce dispatch na `event.bus`, nebo přiznat, že výřez posluchače v procesu vynechává.

## [C-34] Drobnosti v kódu – nízká
- **Hranice přes sběrnici.** anti_patterns.md#infra-spravne-heading: „Jakmile obě sdílejí jednu sběrnici, kdokoli si na doménovou událost pověsí handler“. Kanon ale vede doménové i integrační události po jediném `event.bus` (cqrs#messenger-config-heading, relay v outbox#dispatch-command-heading). Hranici formulovat přes třídu a kontrakt, ne přes sběrnici.
- **Detekce zaseklých ság (uvnitř kap. 14).** sagas.md#check-stale-sagas-heading `new \DateTimeImmutable('-30 minutes')` nad všemi ságami × #configurable-timeouts-heading „Potvrzení zásilky může trvat i **24 hodin**“. Práh brát podle stavu.
- **Lhůta storna.** ddd_pain_points.md#d3-voter „do 24 hodin od vytvoření“ × authorization_in_ddd (výřez `Order::cancel`) „Lhůta běží od potvrzení“. → „od potvrzení“.
- **Výřez v 21.04.** anti_patterns.md#agregat-spravny-heading se bez přiznání odchyluje:
  - `private readonly OrderId $id;` (jinde `$order->id` veřejně),
  - `EmptyOrderException::cannotConfirm()` v `totalAmount()`,
  - `Customer` pod FQCN `App\Ordering\Domain\Model\Customer` s jiným tvarem než basic_concepts#domain-services (id, vip).
- **OrderId bez `__toString()`.** ddd_pain_points.md#a5-code-heading: `OrderId` nemá `__toString()`. Kap. 06 (#entity-identity): „bez něj skončí uložení“.
- **Změna chování při migraci.** Délka hesla: migration_from_crud.md#crud-before-heading „Heslo musí mít alespoň 8 znaků“ a #before-after-heading `strlen($password) < 8` (legacy). Cílový stav volá `HashedPassword::fromPlainText()` s minimem 12 (implementation#value-object-example-heading). Kapitola staví na charakterizačních testech, změnu chování ale neříká. Doplnit větu.
- **Dva porty `PaymentGateway`.**
  - ddd_pain_points.md#c3-code-heading: `App\Payment\Domain\Port`, `charge(Money $amount, PaymentToken $token): PaymentId`,
  - sagas.md#step-handlers-heading: `App\Payment\Domain`, `charge(string $customerId, int $amountCents): string`.
  - Stačí věta, že jde o jiný výřez.
- **Jazyk hlášek výjimek.** Mix češtiny a angličtiny i uvnitř kapitoly, např. aggregate_design `ShipmentId` „Neplatné ShipmentId“ × `OrderId` „OrderId must be a valid UUID“, implementation `Email`/`UserId` česky × basic_concepts anglicky. Sjednotit na angličtinu (převažuje).
- **Doménové výjimky v glosáři.** `id="term-domenova-vyjimka"`: „Místo obecných výjimek (InvalidArgumentException, RuntimeException) doménový model vyhazuje…“ × basic_concepts#vo-validation (formát VO → `\InvalidArgumentException` podle pravidla). Doplnit „porušení formátu v konstruktoru VO je `\InvalidArgumentException`“.
- **Jméno serializéru.** outbox_pattern.md#serializer-heading: `DomainEventSerializer` a hláška „Domain event %s…“ serializují integrační události (K-17) → `IntegrationEventSerializer` nebo neutrální `EventSerializer`.

## [C-35] Drobnosti ve strategických tvrzeních – nízká
- context_mapping.md#customer-supplier „(ano, *Stock* patří do Catalogu)“ × subdomains.md#subdomeny-na-bc „| Inventory | Supporting | Warehouse BC | … Stav skladu“.
- context_mapping.md#postup „Víc než 12 BC je varovný signál“ × team_topologies.md#scenar-enterprise „15–25 stream-aligned týmů … každý vlastní 1 BC“. Práh vztáhnout k jednomu produktu.
- case_study.md#discovery-boundaries-heading „Core subdoména se běžně rozpadá do několika kontextů“ × subdomains.md (FAQ) „spíš varovný signál“.
- ddd_pain_points.md#c4-language „čtvrtletní revize“ × event_storming.md#re-cadence „1× za 6 měsíců nebo 1× za rok“.
- team_topologies.md#mody-vs-context-map „patologie jako Big Ball of Mud nebo Conformist“ × context_mapping#conformist (vědomá volba) → „nechtěný Conformist“.
- migration_from_crud.md#strangler-princip-heading: legacy a nový BC „propojuje … Anti-Corruption Layer nebo sdílená databáze“ × context_mapping (ACL a Strangler Fig) „Každý nový BC je od legacy oddělen ACL“. Doplnit, že sdílená DB je jen přechodný stav.
- when_not_to_use_ddd.md#startup „Ubiquitous Language nelze vybudovat, pokud doménový model ještě neexistuje“ × tatáž kapitola (22.01): UL se vyplatí právě v rané fázi.
- Kontext „Shipment BC“ / `src/Shipment` jen v event_storming.md#post-5-repo, jinde vždy „Shipping“.
- implementation_in_symfony.md#dispatcher-vs-messenger-heading doporučuje EventDispatcher pro vnitrokontextové posluchače a Messenger jako jeho náhradu označuje za anti-vzor. Kanonické rozhodnutí (i kód téže kapitoly) přitom používá synchronní `event.bus`. Už hlásili g4 a g11, zde jen potvrzení, že trvá.

---

# B. Terminologie (převládající úzus → odchylky)

## [T-1] microservice(s) × mikroservis(y) × mikroslužby – nízká až střední
- Převládá „microservice(s)“ (~130×, název kapitoly „DDD a microservices“).
- Odchylky:
  - team_topologies.md (14× „mikroservis/y“, např. #summary FAQ, #scenar-startup „6 mikroservisů“),
  - what_is_ddd.md (10×, #history „mikroslužba = Bounded Context“, #ddd-vs-other „DDD vs. Mikroservisy“),
  - outbox_pattern.md#alternativy-heading (3× „mikroslužby“), ddd_ai, when_not_to_use_ddd (po 1×).
- Návrh: „microservice(s)“, v odkazových textech přesný název kapitoly (viz L-1).

## [T-2] modular monolith × modulární monolit – nízká
- Kniha mimo kap. 19 píše česky „modulární monolit“ (15×, 9 kapitol). microservices_and_ddd.md má „modular monolith“ 25× (nadpis 19.03 „Kdy zvolit modular monolith“, kotvu neměnit). testing_ddd 1×.
- Návrh: v próze kap. 19 „modulární monolit“, anglicky jen při prvním výskytu.

## [T-3] „doménový event“ – nízká
- Převládá „doménová událost“ (110×). Zbytky: event_storming.md#bp-vystup „30–100 doménovými eventy“, #post-3-events (nadpis „Seznam doménových eventů“, kotvu zachovat), #post-5-repo, #commit-disclaimer-heading „47 doménových eventů“.

## [T-4] Sága / Process Manager – nízká
- Převládá „sága“ a „Process Manager“ (velké P). Odchylky:
  - cqrs.md#saga: nadpis „Saga / Process Manager“, „Vzor **Saga**“,
  - lesser_known_patterns.md#vztahy „Application Service / Saga“,
  - malé „process manager“: aggregate_design#invariants, #checklist; event_sourcing#pozice-projekce („process managery a ságy“); event_storming#pl-priklad („Ságy a process managery“).

## [T-5] Event Storming × EventStorming × event storming – nízká
- Převládá „Event Storming“. Odchylky:
  - ddd_ai.md#otevrene-otazky, #spectrum-heading: „EventStormingu“ 3×,
  - team_topologies.md#dalsi-cetba,
  - subdomains.md#further-reading „Big Picture EventStorming“ (mimo název zdroje),
  - malými písmeny case_study.md#discovery, #lessons, practical_examples.md#zaver.

## [T-6] Ubiquitous Language malými písmeny – nízká
- Převládá „Ubiquitous Language“ (64×). ddd_ai.md má 10× „ubiquitous language“. Po 1× aggregate_design#checklist, architectural_styles#proc-styl, authorization#anti-symfony-user-domain-heading, case_study#further-reading, context_mapping#conformist a #published-language.

## [T-7] Strangler Fig Pattern × Strangler Fig pattern × strangler fig pattern – nízká
- migration_from_crud a preface: „Strangler Fig Pattern“. architectural_styles, context_mapping#acl a microservices#strangler-fig-heading: „Strangler Fig pattern“. ddd_pain_points.md#e2-strangler: malými písmeny „strangler fig pattern“ (i v nadpisu).
- Návrh: „Strangler Fig“ (bez „Pattern“, jak píše Fowler), nebo jednotně „Strangler Fig Pattern“.

## [T-8] Read model – nízká
- Převládá „read model“. Odchylky:
  - „Read Model(y)“: cqrs#view-models (nadpis „ViewModely a Read Modely“), #read-model-optimalizace, event_storming#notace, #pl-co-pridava, event_sourcing#es-cqrs-tok-heading, architectural_styles#vertical-slice, case_study#get-projects-handler-heading,
  - „read-model“: migration_from_crud#recept-shared-tabulka-heading, case_study, performance_aspects.

## [T-9] Aggregate Root × kořen agregátu × agregátní kořen – nízká
- „kořen agregátu“ převládá. „agregátní kořen“: lesser_known_patterns#vernon-rule-heading, #summary. Malé „aggregate root“: authorization#use-case-voter, #aggregate-level, aggregate_design#why-aggregates (v závorce).

## [T-10] „dual-write“ ve dvou opačných významech – nízká
- outbox_pattern: dual-write = problém. migration_from_crud#datova-migrace-strangler-heading: záměrná strategie (přiznáno „Pozor na termín“).
- microservices_and_ddd.md#summary: „Cesta z monolitu vede přes Strangler Fig: … s fasádou a obdobím dual-write“ bez rozlišení. V kapitole, kde je dual-write problém relevantní, je to nejednoznačné. → „obdobím souběžného zápisu (viz Migrace)“.

---

# C. Odkazy

## [L-1] Odkazové texty se starými nebo nepřesnými názvy kapitol – nízká
Cesty a kotvy jsou v pořádku, nesedí jen text. Aktuální tituly podle Chapters.php:
- `/vykonnostni-aspekty` „Výkonnostní aspekty“ → „Read modely, projekce a výkon“: aggregate_design#reference-strategies, ddd_pain_points#a4-lazy-loading. Také performance_aspects frontmatter `breadcrumb_name: Výkonnostní aspekty`, který se zobrazuje v drobečkové navigaci.
- `/mene-zname-vzory` „Méně známé vzory“ → „Doplňující taktické vzory“: testing_ddd#architektonicke-testy.
- `/testovani-ddd` „Testování DDD aplikací“ (sagas#testovani, #in-memory-repo-heading), „Testování v DDD“ (event_storming#tdd-events) → „Testování DDD“.
- `/navrh-agregatu` „Návrh agregátů“ → „Návrh agregátu“: case_study#trade-off-aggregate-size-heading, ddd_pain_points#a1-transakce.
- `/context-mapping` „Bounded Contexts a Context Mapping“ → „Bounded Context a Context Mapping“: preface#cast-1.
- `/anti-vzory` „Anti-vzory v DDD“ (context_mapping#big-ball-of-mud), „Anti-vzory DDD“ (microservices#proc-distributed-monolith-heading) → „Anti-vzory a typické chyby“.
- `/ddd-a-microservices` „kapitola o mikroservisách“ (what_is_ddd#bounded-context), „mikroservisech a DDD“ (team_topologies#scenar-startup, #antivzor-shared-repo), „architektura mikroslužeb“ (outbox#2pc-heading) → „DDD a microservices“.
- `/sagy-a-process-managery` „Ságy a process managery“ (event_storming#pl-priklad), „process managery a ságy“ (event_sourcing#pozice-projekce) → „Ságy a Process Managery“.
- `/implementace-v-symfony` „Implementace DDD v Symfony“ a `/cqrs` „CQRS v Symfony“ (migration_from_crud#big-bang-warning-heading, #command-extraction-heading) → „Implementace v Symfony 8“, „CQRS“.

## [L-2] Tabulka „Víc v knize“ v kap. 21 – nízká
- anti_patterns.md#shrnuti: řádek „Mutovatelná událost … `readonly` vlastnosti, `occurredAt` i `recordedAt` | [15](/outbox-pattern)“. Kap. 15 neměnnost událostí ani `recordedAt` neřeší. Vhodnější cíl: `/zakladni-koncepty#domain-events` nebo `/event-sourcing`.
- Tatáž tabulka má jako text odkazů čísla kapitol a sekcí: [03], [15], [22], [06.04], [07.04], [20.01], [20.03], [22.09]. Podobně preface#co-dal „[kapitolu 1: Co je DDD]“ a case_study#project-model-heading „[kapitoly 10]“ / „[kapitola 10]“. Podle preface#vnitrni-odkazy má přečíslování nechat odkazy platné, text s číslem by ale zastaral. Nízká priorita, při přečíslování projít.

## [L-3] „kapitola 12“ ve dvou významech v jedné kapitole – nízká
- aggregate_design.md#symfony-doctrine: „Vernon v IDDD probírá agregát v kapitole 10 a jeho perzistenci v kapitole 12 „Repositories““. O kus níž v kódu: „// Read modely jsou samostatné (CQRS, kapitola 12).“ a „(10.12)“ pro kap. 10 této knihy.
- Návrh: u Vernona „kap. 10 a 12 knihy IDDD“, u vlastních kapitol názvy místo čísel.

---

# D. Duplicitní pasáže

Shingle analýza (6-gramy, odstavce ≥ 25 slov, napříč kapitolami) našla po redakci jen málo téměř doslovných shod. Většina opakovaných témat (Strangler Fig, dual-write, `__toString` u ID, doménová × integrační událost) je v druhé kapitole krátká a odkazuje na kanonickou.

## [D-1] DBAL 4 bez `getName()` – nízká
- aggregate_design.md#symfony-doctrine: „Třída záměrně nemá metodu `getName()`, protože ji DBAL 4 odstranil. Jméno typu (`order_id`) určuje výhradně klíč v konfiguraci `doctrine.dbal.types`…“ (+ `requiresSQLCommentHint()`).
- implementation_in_symfony.md#custom-type-registration-heading: „Oba typy záměrně nemají metodu `getName()`, protože ji DBAL 4 odstranil. Jméno typu určuje výhradně klíč v `doctrine.dbal.types`…“
- Návrh: v kap. 10 zkrátit na jednu větu s odkazem na `/navrh-agregatu#symfony-doctrine`.

## [D-2] Vysvětlení Strangler Fig ve třech kapitolách – nízká (spíš ponechat)
- migration_from_crud#strangler-fig (kanonické), microservices_and_ddd#strangler-fig-heading (3 odstavce, odkazuje na kap. 18), ddd_pain_points#e2-strangler (krátký problém/řešení, odkazuje na kap. 18).
- Návrh: v kap. 19 lze první odstavec (definici) zkrátit na jednu větu. Kap. 20 ponechat.

## [D-3] Datum a atribuce Strangler Fig – ověřeno, bez rozporu
- microservices „(Martin Fowler, 2004; pod tímto názvem od roku 2019)“ × migration „29. června 2004 pod názvem *Strangler Application*. Dne 29. dubna 2019…“. Sedí, jen dvojí výklad (viz D-2).

---

# Předané z reportů redaktorů (trvá, needitováno, zde jen potvrzeno)
- g3: implementation_in_symfony `filename="src/Ordering/Application/Command/RecordPaymentHandler.php"` × `namespace App\Ordering\Application\Handler`.
- g4: zastaralé `highlights` v kap. 09 (Order.php, DoctrineOrderRepository.php, CalculateCartPrice.php).
- g5: cqrs #command-handler-heading odmítá kontrolu duplicity přes `findByEmail()` (TOCTOU), kap. 18 ji zavádí jako cílový stav.
- g7: kap. 11 `OrderFactory` „test-data builder“ × kap. 17 taxonomie (Object Mother) a Foundry `OrderFactory`.
- g8: performance_aspects `App\Product\…` × `App\Catalog` jinde. microservices#symfony outbox přes Doctrine transport `events_out` × `OutboxMessage` z kap. 15.
- g10: anti_patterns 21.04 `Customer` importuje `App\UserManagement\Domain\ValueObject\Email`.
- g11: architectural_styles `interface PlaceOrder` (driving port) × command `PlaceOrder`. Cheat sheet cesty čtení × předmluva.

---
<!-- reports/fix-F1.md -->
# Fix F1 – ddd_pain_points, anti_patterns, when_not_to_use_ddd, ddd_ai

Kontroly po úpravách: lint-php-snippets (34 bloků, 0 chyb), check_anchors, check_use_statements,
check_messenger_routing, check_named_arguments, check_static_calls, check_duplicate_listings,
check_faq_yaml, check_property_access, check_toplevel_code, check_tonality – vše OK.
Kotvy proti HEAD beze změny (měnily se jen texty nadpisů). Frontmatter nedotčen.

## Opraveno

### ddd_pain_points.md
- **C-6** #a1-transakce: potřeba atomicky změnit dva agregáty je diagnóza (špatná hranice → posunout ji,
  nebo druhá změna v samostatné transakci / sága). Společný BC a DB = technický předpoklad, ne
  ospravedlnění; legitimní jen výjimky z `/navrh-agregatu#breaking-the-rule`. Komentář v `ConfirmTransferService`
  sladěn („vědomá výjimka“).
- **C-7** #b1-outbox: nadpis a problém přepsány podle 15.01 – pod `doctrine_transaction` je `flush()` jen zápis SQL,
  hrozí phantom event; ztráta události jen bez middlewaru. „Doménové“ → „integrační“ (nadpis, text, FAQ).
  Věta, že doménové události uvnitř kontextu jdou synchronně po `event.bus` bez outboxu. Odkaz na `/outbox-pattern#naive-publish-heading`.
- **C-8** #b2-debugging / #b2-code-heading: middleware nově propaguje correlation ID do odvozených zpráv
  (`$current` + `try/finally`), stamp přidává jen když chybí. Věta o rozdílu correlation × causation.
  YAML výřez: `command.bus` s `validation` a `doctrine_transaction`, plus `event.bus` (s `allow_no_handlers`), věta o tom, že klíč `middleware` přepíše seznam.
- **C-10** #b3-idempotence: middleware vymezen na příkazy s jedním handlerem; události deduplikuje inbox
  `(event_id, consumer)` – odkaz `/outbox-pattern#inbox`. „byznys události“ → „byznys operace“.
- **C-13** #c1-validace: tabulka – formát (vč. záporné ceny) → konstruktor VO, Validator jen opakuje kvůli hlášce;
  invariant → metoda agregátu; politika → agregát nebo doménová služba. „Hlavní pravidlo“ zahrnuje i formát, odkaz na
  `/implementace-v-symfony#validace-kde-heading`.
- **C-15** #a5-identity: `place()` identitu přijímá, odvozené továrny si ji vyrobí přes `OrderId::generate()`;
  v obou případech v PHP kódu před uložením, nikdy v DB.
- **C-26** #a3-value-objects: Money vyjmuto z výčtu limitů Embedded, vypuštěn „custom typ do dvou sloupců“; warn říká,
  že DBAL typ mapuje jediný sloupec a Money je kanonicky Embedded (odkaz `/navrh-agregatu#symfony-doctrine`).
- **C-27** #c2-stavy: výřez `Order` → `final class`.
- **C-34** #d3-voter lhůta „od vytvoření“ → „od potvrzení“; #a5-code-heading `OrderId` doplněn `__toString()`;
  #c3-code-heading hláška `PaymentFailedException` anglicky; za ukázkou věta, že port v kapitole o ságách je jiný, zjednodušený výřez.
- **C-35** #c4-language: „čtvrtletní revize“ → 6 měsíců až rok, v rychle se měnícím produktu častěji; odkaz `/event-storming#re-cadence`.
- **T-7** #e2-strangler (nadpis, text) a FAQ: „Strangler Fig Pattern“. (meta_keywords ve frontmatteru ponechány.)
- **T-9**: odkaz „životní cyklus Aggregate Root“ → „životní cyklus kořene agregátu“.
- **L-1**: „Návrh agregátů“ (nově v A1 jako „Návrh agregátu“), „Výkonnostní aspekty“ → „Read modely, projekce a výkon“,
  „Doplňující vzory“ → „Doplňující taktické vzory“, „Outbox“/„Outbox pattern“ → „Outbox Pattern“, „Anti-vzory“ → „Anti-vzory a typické chyby“.

### anti_patterns.md
- **C-2** #logika-v-infrastrukture (odstavec za #infra-spravne-heading): pravidlo vyjmenovává výjimky
  `Symfony\Component\Uid`, `Doctrine\ORM\Mapping`, `Doctrine\Common\Collections` a při hlídání závislostí mezi kontexty
  `App\*\Domain\ValueObject\*Id`.
- **C-34 (sběrnice)**: odstavec o hranici doménová × integrační událost přeformulován přes třídu a kontrakt;
  výslovně, že kniha vede obě po jediném `event.bus`.
- **C-4 (příbuzná odchylka)** #infra-spravne-heading: `ActivateUserHandler` → `namespace App\UserManagement\Registration\Command`
  (feature slice) s komentářem.
- **C-34 (výřez 21.04)** #agregat-spravny-heading: `Order` má `public readonly OrderId $id` / `CustomerId $customerId`
  v konstruktoru jako kanon; `totalAmount()` hází `EmptyOrderException::cannotBePlaced()`; `Customer` má `public readonly` id
  a komentář, že jde o ilustrační výřez odlišný od `Customer` v Základních konceptech.
- **g10**: tamtéž komentář, že import `Email` z UserManagement je zkratka výřezu.
- **L-2** #shrnuti: řádek „Mutovatelná událost“ → `/zakladni-koncepty#domain-events`. Texty odkazů v tabulce z čísel
  ([22.09], [06.04] …) na názvy sekcí/kapitol. Řádek „Drift jazyka“ navíc přesměrován z `#modelovani` na přesnější
  `/ddd-v-praxi-kde-to-boli#c4-language` (kotva ověřena).

### when_not_to_use_ddd.md
- **C-27** #pseudo-ddd-heading: výřez `Order` → `final class`.
- **C-35** #startup: věta „Ubiquitous Language nelze vybudovat…“ nahrazena („pojmy se teprve hledají a ustálený model,
  který by taktické vzory zachytily, zatím neexistuje“) – už neodporuje 22.01.
- **C-25 (strana kap. 22)** #hybrid-subdomain: „vědomě anemický model“ → „nejde o anemický model ve Fowlerově smyslu…
  Transaction Script“ (v souladu s 21.02).
- **T-1**: „mikroservisy“ → „microservices“ (tabulka 22.10). **T-3**: „Doménové eventy“ → „Doménové události“.
- **L-1**: „[Anti-vzory]“ → „[Anti-vzory a typické chyby]“ v úvodu.

### ddd_ai.md
- **T-6**: „ubiquitous language“ → „Ubiquitous Language“ (10× v těle, vč. nadpisu ai.01 a FAQ odkazu).
- **T-5**: „EventStorming(u)“ → „Event Storming(u)“ (3×).
- **T-1**: „mikroservisy“ → „microservices“.
- **L-1**: „Implementace DDD v Symfony 8“ → „Implementace v Symfony 8“.
- Navíc sjednoceno „Bounded contexts“ → „Bounded Contexts“ (nadpis ai.02, text, FAQ) a „anticorruption layer“
  → „Anti-Corruption Layer“ (3×).

## Vědomě neopraveno
- Frontmatter `meta_keywords` („strangler fig pattern“, „ubiquitous language LLM“) – frontmatter kromě deck/modified neměnit.
- anti_patterns #uvodem „[sekce 03.12](/context-mapping#big-ball-of-mud)“ – číslovaný odkaz míří správně (report ho vede jako OK).
- C-12 v kap. 20 (`get()`/`getWithItems()`) už odpovídá navrženému kanonu; beze změny.
- C-21 (`SharedKernel/Infrastructure/Messenger` v B2) – návrh řeší text kap. 03/19 a glosáře, ne kap. 20.
- Komentáře `// Aggregate Root` v kódu kap. 22 – anglické štítky v kódu, ne próza.
- `ActivateUserHandler` dál používá `findById()` + null-check a ruční `flush()`; C-12 tento výskyt nejmenuje.

## Zůstává na jiných souborech
- C-2: lesser_known_patterns#mod-phparkitect, architectural_styles#onion-symfony-heading.
- C-6: aggregate_design, implementation_in_symfony – bez změny potřeby (kap. 20 se přizpůsobila jim).
- C-10: microservices_and_ddd (jiné schéma inboxu), testing_ddd (`Uuid::fromString()`).
- C-15: aggregate_design / lesser_known_patterns / cqrs – kap. 20 teď popisuje obě cesty, jinde nic nutného.
- C-25: subdomains.md („anemic model“ v kap. 02).
- C-34 PaymentGateway: sagas#step-handlers-heading může nést zrcadlovou větu.
- C-35: event_storming „Shipment BC“ → Shipping; ostatní body C-35 mimo mé soubory.

---
<!-- reports/fix-F2.md -->
# Fix F2 – outbox_pattern.md, sagas.md, event_sourcing.md

Kontroly po úpravách: lint-php-snippets (77 bloků, 0 chyb), check_anchors, check_use_statements,
check_messenger_routing, check_named_arguments, check_static_calls, check_duplicate_listings,
check_property_access, check_faq_yaml, check_toplevel_code, check_tonality: vše OK.
Kotvy beze změny proti HEAD (diff), frontmatter nedotčen (`modified: 2026-09-27` už byl), žádné em dash.

## Opraveno

### outbox_pattern.md
- **C-33** #place-order-handler-heading: `PlaceOrderHandler` dostal `#[Target('event.bus')] MessageBusInterface $eventBus`
  a v cyklu `releaseEvents()` každou doménovou událost dispatchne synchronně (komentář: posluchači v kontextu,
  v téže transakci; outbox jen pro to, co opouští proces). Komentář „Dílčí události zůstávají uvnitř kontextu“ tím platí.
  `highlights` přepočítány (31,32,40–42,48,57,65,92–99), ověřeno hl.php.
- **C-34 serializer** #serializer-heading: `DomainEventSerializer` → `IntegrationEventSerializer` (nadpis, filename,
  třída, use, konstruktor handleru), hláška „Integration event %s…“. Jinde v knize se třída nevyskytuje.
- **C-11** #dispatch-command-heading: relay rozlišuje `Messenger\Exception\TransportException` (bez `markFailed`,
  přeruší dávku `continue 2`, backoff 1→2→…→30 s, reset po úspěšné dávce) od chyby konkrétní zprávy (`\Throwable` → `markFailed`).
  #backpressure-heading: doplněno „(`TransportException`)“ a odkaz na relay. FAQ už to tvrdilo správně.
- **C-27** #order-aggregate-heading: `class Order` → `final class Order`.
- **C-7 (strana 15)** #at-least-once-heading „každá doménová událost“ → „integrační“; #backpressure-heading
  „Doménové události (`OrderPlaced`) zahodit nelze“ → „Integrační události s byznysovým faktem (`OrderPlacedIntegrationEvent`)…“.
- **C-34 hlášky** anglicky: `'Missing integration translation for '`, `'Unknown message_type "%s" in outbox.'`,
  nový výpis relaye anglicky (jako ostatní `[outbox] …`).
- **T-1** FAQ (dual-write v monolitu): 3× „mikroslužb…“ → „microservice(s)“.
- **L-1** #2pc-heading: „[architektura mikroslužeb]“ → věta s „kapitola [DDD a microservices](/ddd-a-microservices)“;
  komentář v Order „Kapitola o méně známých vzorech“ → „Kapitola Doplňující taktické vzory“.
- Vedlejší (předané „zastaralé highlights“ obecně): anti-vzor `PlaceOrderHandlerNaive` měl `highlights="22,25"`
  na `__invoke`/argumentu – přesunuto na 31,35 (save a dispatch); srovnáno odsazení argumentů `placeWithItems`.

### sagas.md
- **C-7** #outbox-pattern-heading: warn přepsán podle 15.01 – pod `doctrine_transaction` je `flush()` jen zápis SQL,
  přímý dispatch jde před commit → phantom event; bez middlewaru ztráta. Odkaz na `/outbox-pattern#dual-write`,
  do outboxu jde „integrační“ událost. Nadpis „Outbox pattern“ → „Outbox Pattern“ (kotva stejná).
- **C-8** #korelacni-id-heading: `orderId` = „byznysový korelační klíč“; odlišeno od technického korelačního ID
  (request/spouštěcí zpráva, propaguje se do odvozených zpráv) s odkazem na glosář.
- **C-30** #messenger-yaml-heading: `command.bus` doplněn o `validation`, oba transporty o `options: { queue_name: … }`
  s komentářem; #choreografie-messenger-heading: `async_events` doplněn `queue_name: events`.
- **C-31** #idempotent-saga-transitions-heading: „po třech pokusech“ → „po třech opakováních“; citovaná česká
  hláška nahrazena jménem výjimky `InvalidOrderStateTransitionException` (přechod `paid` → `paid`), aby text
  nezávisel na jazyku hlášky (C-34).
- **C-34 zaseklé ságy** #check-stale-sagas-heading: práh podle stavu (`maxIdle()`: sklad 5 min, platba 15 min,
  zásilka 26 h, jinak 30 min; = timeout z 14.08 + rezerva), repozitář vrací kandidáty podle nejkratšího prahu,
  popis příkazu upraven. Signatura `findStale()` beze změny.
- **C-34 PaymentGateway** #step-handlers-heading: věta, že jde o zjednodušený výřez s primitivy, a odkaz
  na port s doménovými typy v `/ddd-v-praxi-kde-to-boli#c3-code-heading`.
- **C-34 hlášky** anglicky: `'Payment declined.'`, `'Out of stock.'`, `OrderLockedBySagaException`
  `'Order "%s" is locked by a running process.'` (`reason: 'Zboží není skladem'` na ř. ~724 je doménová data, ponecháno).
- **T-4** „process manager“ → „Process Manager“ (#terminologicka-konvence 2×, #logika-v-process-manageru).
- **T-6** #kdy-saga-nestaci: „[všudypřítomném jazyce]“ → „[Ubiquitous Language]“.
- **L-1** „[Testování DDD aplikací]“ → „[Testování DDD]“ (#testovani, #in-memory-repo-heading).

### event_sourcing.md
- **C-8** #event-store-php-heading: `RequestEventMetadataProvider` bez fallbacku `?? $event->eventId`
  (komentář proč; bez kontextu null); docblock definuje correlation (request/spouštěcí zpráva, propaguje se)
  a causation (bezprostřední příčina) a kdo volá `bind()`.
- **C-31** #out-of-order-heading: „výchozí: 3 pokusy“ → „3 opakování“.
- **T-4 + L-1** #pozice-projekce: „[process managery a ságy]“ → věta se „ságy a Process Managery“ a odkazem
  „[Ságy a Process Managery]“.
- **T-8** tok ES+CQRS: „**Read Models**“ → „**read modely**“.
- **L-1** „[Testování DDD kódu]“ → „[Testování DDD]“; „[Anti-vzory]“ → „[Anti-vzory a typické chyby]“;
  „[Implementace v Symfony]“ → „[Implementace v Symfony 8]“; „[Event Storming]“ → „[Event Storming a Domain Storytelling]“.

## Vědomě neopraveno
- **C-10**: kap. 15 je kanonická (`(event_id, consumer)`, `isProcessed(Uuid, string)`), v mých souborech nic k úpravě.
- **C-1**: v mých souborech už `totalAmountCents` a tolerant reader (#verzovani-udalosti); beze změny.
- **C-4**: kap. 15 a 14 už používají kanonický `App\Ordering\Application\Command\PlaceOrder`.
- Konzolové výpisy v sagas (`CheckStaleSagasCommand`: „Žádné zaseklé ságy“ …) zůstaly česky – nejde o hlášky výjimek.
- event_sourcing 13.08 (#outbox): věta „spadne-li proces mezi nimi, událost je uložená…“ ponechána – event store
  commituje vlastní transakcí v `append()`, scénář tam platí.
- event_sourcing „**Domain Events**“ v číslovaném toku ponecháno (anglický termín, ne „doménový event“ z T-3).

## Zbývá na jiných souborech (strana mimo F2)
- **C-7**: ddd_pain_points#b1-outbox (flush ≠ commit, „doménové“ → „integrační“).
- **C-8**: glosář `term-korelacni-id` (sladit s definicí correlation/causation), ddd_pain_points#b2-code-heading.
- **C-31**: case_study, cqrs, context_mapping (SLA vs `max_retries: 0`).
- **C-34 hlášky**: implementation_in_symfony `InvalidOrderStateTransitionException` má stále českou hlášku
  („Nelze přejít ze stavu…“); sagas ji už necituje.
- **C-34 PaymentGateway**: druhá strana (ddd_pain_points#c3) může volitelně odkázat zpět; nutné není.
- **C-27**: aggregate_design, basic_concepts, authorization, when_not_to_use_ddd – `final class Order`.

---
<!-- reports/fix-F3.md -->
# Oprava rozporů – skupina F3 (kap. 12 CQRS, 16 Read modely a výkon, 19 DDD a microservices)

Soubory: content/chapters/cqrs.md, performance_aspects.md, microservices_and_ddd.md. Necommitováno.
Kontroly: lint-php-snippets (3 soubory) 41 bloků / 0 chyb; check_anchors, check_use_statements,
check_messenger_routing, check_named_arguments, check_static_calls, check_duplicate_listings,
check_faq_yaml OK; check_tonality 0 nálezů. Kotvy beze změny proti HEAD u všech tří souborů.
Frontmatter: měnil jsem jen `deck` kap. 19 (T-2).

## Opraveno

### microservices_and_ddd.md (kap. 19)
- **C-1** #symfony, odrážka „Verzování payloadu“: doplněno, že nový `event_type` (např. `ordering.order_placed.v2`)
  dostane jen rozbíjející změna. Pole `totalAmountCents` už sedělo.
- **C-2** #phparkitect-rules-heading: pravidlo 2 dostalo druhý argument `NotDependsOnTheseNamespaces`
  (výjimky) `App\{bc}\Domain\ValueObject\*Id` a komentář. V próze pod ukázkou jedna věta s odkazem na
  `/navrh-agregatu#references-by-id`. Highlights přepočítány (17–21, 49–53, ověřeno hl.php).
- **C-3 + C-10** #symfony-handler-heading: handler integrační události už neimportuje
  `App\Billing\Infrastructure\Idempotency\InboxRepository`. Používá kanonické rozhraní kap. 15
  `App\Inbox\Application\InboxRepository::isProcessed(Uuid, string)` s konstantou `CONSUMER`.
  Komentáře a odrážka „Idempotence“ mluví o unikátní dvojici `(event_id, consumer)` a odkazují na `/outbox-pattern#inbox`.
- **C-18** tabulka Context Map ↔ mechanismy: Conformist „typicky u externí služby“ → „u reportingu nebo
  průchozího kontextu bez vlastní logiky nad převzatými daty“.
- **C-21** #modular-monolith-symfony-heading: věta „Kniha do něj dává jen hodnotové objekty“ vypuštěna.
  Nově se tam píše, že namespace `App\SharedKernel` nese i technické bázové typy a Shared Kernel v Evansově smyslu tvoří
  jen jeho doménová podmnožina.
- **C-22** #kdy-modular-heading: práh „méně než ~30 %“ nahrazen kvalitativním kritériem
  (neobsadí ani malý platformní tým) a údajem ~15 % s odkazem na `/team-topologies#scenare-summary-heading`.
  #priklad-eshop-heading: „obhajitelné, pokud někdo unese i jejich provoz“ + věta o managed službách / malém
  platformním týmu s odkazem na #ops-pravidlo-heading.
- **C-30** #messenger-subscriber-heading (úvodní odstavec): věta, že `async` patří samostatné službě
  a monolit dělí zprávy na `async_commands`/`async_events`.
- **C-32** #saga-heading: počet kroků nahrazen tvarem procesu, „Process Manager (saga aggregate)“ →
  „Process Manager, komponenta řídící ságu“, odkaz na `/sagy-a-process-managery#choreografie-stale-validni-heading`.
- **C-34**: hláška `OutboundEventSerializer` „nemá dohodnutý wire formát“ → anglicky.
- **T-2**: v próze „modulární monolit“ (vč. deck, popisku diagramu, FAQ, H3/H4 nadpisů, názvu výpisu
  a textu v `because()`). Anglicky zůstává jen první výskyt v úvodu „(modular monolith)“, H2 19.03, citované
  strategie „monolith-first / modular monolith-first“ a meta_description/keywords (frontmatter).
- **T-7** #strangler-fig-heading: „Strangler Fig pattern“ → „Strangler Fig Pattern“.
- **T-10** #summary: „obdobím dual-write“ → „obdobím souběžného zápisu, ve kterém zůstává jedno úložiště
  zdrojem pravdy ([fáze 2](#faze-2-heading))“. Stejně #proc-distributed-monolith-heading (odrážka Refaktoring).
- **D-2** #strangler-fig-heading: definiční odstavec zkrácen na jednu větu. Druhý odstavec (transitional architecture)
  a odkaz na kap. 18 zůstaly.
- **L-1** #proc-distributed-monolith-heading: „Anti-vzory DDD“ → „Anti-vzory a typické chyby“.
- **Předané g8** (outbox přes `events_out`): po konfiguraci publisheru přibyla věta o vztahu k tabulce `outbox`
  a entitě `OutboxMessage` z kap. 15 (tady slouží jako outbox tabulka Doctrine transportu `messenger_messages`,
  fronta `outbox_events`).

### cqrs.md (kap. 12)
- **C-9**: 12.11 „Denormalizované tabulky“: tabulka se aktualizuje synchronně ve stejné transakci, nebo
  asynchronně přes frontu. #denorm-projekce-heading: docblock „Asynchronní projektor“ přepsán (kanonický
  routing = synchronní event.bus, stejná transakce). Odstavec „Kdo doménové události odešle“: synchronní
  projekce pod `doctrine_transaction` je plnohodnotná volba. Riziko se týká jen asynchronního routingu: phantom
  event pod middlewarem, ztráta bez něj (sladěno s C-7 / 15.01). „Asynchronní projekce dovolují rebuild“ →
  „Projekce dovolují rebuild“.
- **C-14** 12.16 „Testování command handlerů“: „unit test s mockem repozitáře“ → „s in-memory fake repozitářem“
  (odkaz `/testovani-ddd#test-doubles`), „Fake ani mock repozitáře žádný index nemá“.
- **C-17** #cqrs-urovne-heading: „podpůrným a generickým kontextům (administrace, notifikace)“.
- **C-32 / T-4** #saga: nadpis „Sága a Process Manager“, text podle 14.00 (sága = zastřešující pojem,
  Process Manager = komponenta s vlastním stavem, která orchestrovanou ságu řídí).
- **T-8**: nadpisy „ViewModely a read modely“, „Optimalizace read modelů“, „ViewModel (nebo read model)“.
- **L-1**: „Testování DDD kódu“ → „Testování DDD“ (2×), „Implementace v Symfony“ → „Implementace v Symfony 8“ (2×).

### performance_aspects.md (kap. 16)
- **C-9** #projekce-provoz-heading: „projekce běží asynchronně, takže … vždy“ → zpoždění jen u asynchronní
  projekce, synchronní pod `doctrine_transaction` ho nemá (platí delší transakcí zápisu).
- **C-12** #agregat-hranice: `findHeaderById(): ?Order` „pro seznam objednávek“ a `findWithItemsById(): ?Order`
  → kanonické `get(OrderId): Order` a `getWithItems(OrderId): Order` s `OrderNotFoundException::withId()`
  (shoda s ddd_pain_points#a4-lazy-loading). Docblocky posílají seznamy a detail pro obrazovku do read modelu.
  #n-plus-1-problem: `OrderQueryRepository` přestal být „čtecí stranou“ pro obrazovky. Docblock ho vymezuje na
  dávkové čtení celých agregátů (export, přepočet, migrace), obrazovky čtou DTO z read modelu. Dál vrací `Order[]`,
  protože ukázka předvádí fetch join. S #eskalace-heading („query handlery nepoužívají doménové repozitáře“)
  už nekoliduje.
- **C-27** výřez `Order` s EXTRA_LAZY: `class Order` → `final class Order`.
- **C-30** 16.08: `messenger:consume async` → `async_commands`. Přibyla věta, že `ImportProductChunk` musí mířit
  v `routing:` na tento transport (odkaz `/cqrs#messenger-config-heading`), jinak běží synchronně.
- **Předané g8**: `App\Product\Domain\…` → `App\Catalog\Domain\Model\Product`, `App\Catalog\Domain\ValueObject\ProductId`.
- **L-1**: „Implementace v Symfony“ → „Implementace v Symfony 8“.

## Vědomě neopraveno
- **L-1 `breadcrumb_name` kap. 16**: frontmatter jsem neměnil. V pracovní kopii už je ale `Read modely a výkon`
  (změnil ho někdo jiný), takže nález je vyřešený.
- **C-3 cqrs#buses-example-heading** (controller importuje `DuplicateEmailException` z Domain): podle rozhodnutí
  to řeší Deptrac v kap. 17 (Presentation → Domain), v kap. 12 není co měnit.
- **C-15 cqrs#command-navratova-hodnota-heading** („identitu přiděluje továrna agregátu“): sedí s kanonickou
  `placeWithItems()`, která volá `OrderId::generate()`. Beze změny.
- **C-31 cqrs#async-example-heading**: „tři opakování“ je správně. Beze změny.
- **C-23** kap. 19 je v tomto nálezu referenční strana. Beze změny.
- **C-2 phparkitect API**: druhý argument `NotDependsOnTheseNamespaces` (exclude) a glob `*Id` odpovídají
  README phparkitectu, jak si ho pamatuji. Proti zdrojovému kódu aktuální verze jsem to neověřil, balíček
  v repu není. **NEJISTÉ.**

## Zbývá na jiných souborech
- C-1: schéma a `description` v context_mapping#published-language (kap. 03).
- C-10: B3 v ddd_pain_points a `Uuid::fromString()` v testing_ddd.
- C-21: kap. 03 a glosář (technické typy v `App\SharedKernel`).
- C-22: pokud se změní poměr 75/15/10 v kap. 05, upravit odkazovanou větu v 19.03.
- C-30: testing_ddd (`async` → `async_commands`, routing `SendWelcomeEmail`), sagas (`queue_name`, `validation`).
- C-32: glosář `term-saga` (počet kroků).
- T-10: migration_from_crud zůstává kanonickým místem pro dual-write jako strategii.

---
<!-- reports/fix-F4.md -->
# fix-F4 – context_mapping, team_topologies, subdomains, what_is_ddd, preface

Kontroly: kotvy `{#…}` beze změny proti HEAD (všech 5 souborů), `lint-php-snippets` 0 chyb,
`scripts/check_anchors.php` OK, JSON schéma OrderPlaced je validní JSON, žádné em dash.
Frontmatter nedotčen (`modified: 2026-09-27` už byl nastaven).

## Opraveno

- **C-1** context_mapping#published-language: `description` → „Integrační událost publikovaná…“,
  pole `totalAmount` → `totalAmountCents` (v `required` i `properties`), odstraněno
  `"additionalProperties": false`. Odstavec pod schématem přepsán: konzument ověřuje povinná pole
  a neznámá ignoruje (tolerant reader), nové nepovinné pole přibude ve v1, `order-placed-v2.json`
  jen pro rozbíjející změnu (odebrání/přejmenování, změna typu či významu).
- **C-17** team_topologies#scenar-scaleup: „Identity+Billing tým (sdílí 2 supporting BC)“ →
  „Billing tým (4 lidi, sdílí 2 supporting BC: Billing a Warehouse). Identita je Generic: běží na
  externím IdP a tenký bridge k němu udržuje Platform team.“ Ve výčtu v #komunikacni-struktura
  (případ 2) „Identity tým“ → „Warehouse tým“, aby kapitola sama sobě neodporovala (Generic = žádný vlastní tým).
- **C-18** context_mapping#conformist („Kdy Conformist zvolit“): odrážka SaaS zúžena na
  „Externí dodavatel bez navazující doménové logiky“ (data se jen zobrazují/přeposílají); jakmile na
  nich stojí vlastní pravidla (platby v objednávce, identita), patří mezi SaaS a doménu ACL. Auth0
  z výčtu vypadl.
- **C-20** team_topologies#cognitive-load-rule: řádek „5 lidí | 1 BC (max 2 malé)“ → „1 BC, nebo 2–3 malé“
  (souhlasí se scénářem A).
- **C-21** context_mapping#shared-kernel (note callout): doplněn odstavec, že namespace
  `App\SharedKernel` v ukázkách nese i technické bázové typy; Shared Kernel v Evansově smyslu je jen
  doménová podmnožina (`Money`, `Currency`), technická část je obyčejná sdílená knihovna.
- **C-23**
  - subdomains#proc-subdomeny: „typicky jeden tým s jednou nasazovací jednotkou“ → „typicky jeden tým.
    Zda běží jako samostatná služba, nebo jako modul monolitu, je provozní rozhodnutí.“
  - subdomains#summary FAQ: „typicky jeden tým a jeden deployment“ → totéž ve formě FAQ.
  - what_is_ddd#benefits: „vyvíjet části systému nezávisle na sobě a v případě potřeby je i samostatně
    nasazovat a škálovat ([DDD a microservices](/ddd-a-microservices))“.
  - team_topologies#summary FAQ: „kryje se tým a BC: 1 stream-aligned tým = 1 BC. Kontext pak běží jako
    samostatná microservice, nebo jako modul v modulárním monolitu; to je provozní rozhodnutí.“
- **C-25** subdomains#tri-kategorie: „*anemic* model nad Doctrine ORM“ → „Transaction Script nebo CRUD
  nad [Doctrine entitami](/implementace-v-symfony)“; FAQ v #summary: „anemic model a Doctrine ORM“ →
  „CRUD architektura se servisní vrstvou (Transaction Script) nad Doctrine entitami“.
- **C-31** context_mapping#postup (fragment `docs/context-map.md`): SLA „dead letter queue po 3 retry“ →
  „nečitelná zpráva jde bez opakování do failure transportu (`max_retries: 0`)“ – sladěno s konfigurací
  `from_catalog` v téže kapitole.
- **C-35**
  - context_mapping#customer-supplier: „(ano, *Stock* patří do Catalogu)“ nahrazeno: stav skladu vlastní
    Warehouse BC (odkaz `/subdomeny#subdomeny-na-bc`), Catalog přebírá jen odvozenou dostupnost, proto
    pole `availableStock` do DTO přidat může (OHS v2 ukázka zůstává platná). Alternativa: Ordering čte
    dostupnost přímo z API Warehouse BC.
  - context_mapping#postup: „Víc než 12 BC v jednom produktu je varovný signál… Celá firma s desítkami
    týmů jich má přirozeně víc, mapa se pak kreslí po produktech.“
  - team_topologies#mody-vs-context-map: „Conformist“ → „nechtěný Conformist“.
- **T-1** „microservice(s)“: team_topologies (případ 2, scénář A, FAQ), what_is_ddd (#history,
  #bounded-context, #kritika, #ddd-vs-other včetně labelu „DDD vs. microservices“, FAQ),
  context_mapping (komentář v messenger.yaml „kapitole DDD a microservices“). V souborech F4 už
  „mikroservis/mikroslužb“ nezbývá.
- **T-5** subdomains#further-reading „(Big Picture EventStorming)“ → „Event Storming“ (odkazový text
  `[EventStorming]` = název webu zdroje, ponechán); team_topologies#dalsi-cetba anotace zdroje → „Event Storming“.
- **T-6** context_mapping#conformist a #published-language: „ubiquitous language“ → „Ubiquitous Language“.
- **T-7** context_mapping#acl: nadpis h3 „ACL a Strangler Fig pattern“ → „… Pattern“ (h3 bez kotvy,
  parser automatické id negeneruje).
- **L-1** preface#cast-1 „Bounded Contexts a Context Mapping“ → „Bounded Context a Context Mapping“;
  context_mapping#big-ball-of-mud „Anti-vzory v DDD“ → „Anti-vzory a typické chyby“;
  what_is_ddd#bounded-context „kapitola o mikroservisách“ → „kapitola [DDD a microservices]“ (a souběžně
  „kapitola [Conway's Law a Team Topologies]“); team_topologies#scenar-startup, #antivzor-shared-repo
  a FAQ „mikroservisech a DDD“ → „DDD a microservices“.
- **L-2** preface#co-dal: „[kapitolu 1: Co je DDD]“ → „kapitolu [Co je Domain-Driven Design]“ (bez čísla).

## Vědomě neopraveno

- **C-16, C-19, C-35 (Core rozpad, ACL + Strangler Fig)**: kanonická strana leží v mých souborech
  (subdomains, context_mapping) a zůstává; opravují se architectural_styles, case_study, migration_from_crud.
- **C-22**: návrh mění práh v kap. 19; kap. 05 (≈ 15 % platform) zůstává.
- **T-4** what_is_ddd#summary „[Saga / Process Manager](/glosar#term-saga)“: položka v anglickém výčtu
  názvů vzorů (Specification, Entity, Value Objects…), ponecháno anglicky.
- Zkrácené odkazové texty v předmluvě („Anti-vzory“, „Základní koncepty“, „Implementace v Symfony“,
  „Subdomény“) jsou prefixy aktuálních titulů; report je nehlásil, ponechány.
- subdomains `Order` (Supporting) s holou `\DomainException`: zkratka je v textu přiznaná, třída
  nedědí z `AggregateRoot` (C-27 se netýká).

## Zbývá v jiných souborech

- C-18: glosář `id="term-konformista"` („Typické použití je integrace s externím SaaS (Stripe, Auth0)“) – templates/ddd/glossary.html.twig.
- C-1: microservices_and_ddd#symfony (tolerant reader už popisuje; ověřit shodu `totalAmountCents`).
- C-17: cqrs#cqrs-urovne-heading („podpůrným kontextům (notifikace…)“).
- C-20: case_study – věta, že studie ukazuje hranice modelu, ne týmovou topologii.
- C-21: microservices_and_ddd#modular-monolith-symfony-heading a glosář `term-sdilene-jadro`.
- C-23: microservices_and_ddd (strana kanonická, bez změny).
- Předané g11: průchody v cheat sheetu × cesty v předmluvě (#cesta-architekt, #cesta-migrace,
  #cesta-techlead) – bez rozhodnutí koordinátora, který zdroj je pravdou; předmluvu jsem neměnil.

---
<!-- reports/fix-F5.md -->
# F5 – implementation_in_symfony.md, testing_ddd.md, migration_from_crud.md

Kontroly po úpravách: `lint-php-snippets` 31/16/13 bloků, 0 chyb; `check_anchors`, `check_use_statements`,
`check_messenger_routing`, `check_named_arguments`, `check_static_calls`, `check_duplicate_listings`,
`check_property_access`, `check_toplevel_code`, `check_faq_yaml`: OK. Diff kotev proti HEAD prázdný u všech tří.
Frontmatter beze změny (`modified: 2026-09-27` už byl nastaven).

## Opravené nálezy

### implementation_in_symfony.md (kap. 10)
- **C-2** #ddd-vs-symfony-boundary: k větě „čisté PHP bez závislosti na frameworku“ doplněno, co kniha doméně povoluje
  (`symfony/uid`, `Doctrine\ORM\Mapping`, `Doctrine\Common\Collections`). Věta pod stromem („nikdy přes přímý import
  doménových tříd cizího kontextu“) doplněna o výjimku pro identifikátory (`ShipmentId`), aby neodporovala
  calloutu „Časté chyby“ ani kanonickému `Order`.
- **C-12** #exception-types-heading: `UserNotFoundException` vyřazen z aplikačních výjimek. `OrderNotFoundException`
  je teď uveden mezi doménovými (leží v `Domain\Exception`, hází ho repozitář z `get()`). Jako aplikační výjimka
  slouží `ValidationFailedException` z middlewaru `validation`.
- **C-14** (odstavec za #symfony-idiomy-asalias): handlery pokrývá unit test s InMemory fake. Unique constraint,
  jeho překlad a transakční chování jdou do integračního testu proti DB.
- **C-34 hlášky**: všechny hlášky výjimek v kódu jsou anglicky: `Email`, `UserName`, `UserId`, `EmptyOrderException`,
  `OrderNotFoundException`, `UserAlreadyActivatedException`, `InvalidVerificationTokenException`, `DuplicateEmailException`.
- **C-35** #dispatcher-vs-messenger-heading: callout přepsán podle rozhodnutí.
  - Posluchači uvnitř kontextu jdou přes synchronní `event.bus`, s odkazem na #command-handler-example-heading.
  - Přes hranici kontextu jde asynchronní transport a Outbox.
  - EventDispatcher zůstává pro háčky frameworku (kernel, Security, formuláře).
  - Odstavec „Anti-vzor“ nahrazuje věcné zdůvodnění jedné sběrnice.
  - V ukázce atributu je `transport: 'async_events'` místo neexistujícího `async`.
- **D-1** #custom-type-registration-heading: výklad o `getName()` / DBAL 4 zkrácen na odkaz
  `/navrh-agregatu#symfony-doctrine`. Zbyly jen informace, které jsou specifické pro kap. 10 (`NAME`, `getSQLDeclaration()`).
- **L-1**: „Anti-vzory“ → „Anti-vzory a typické chyby“ (2×), „Základní koncepty“ → „Základní koncepty DDD“ (3× tam,
  kde text jmenuje kapitolu).
- **C-27, C-28**: už v pořádku. Oba výřezy `Order` jsou `final`, holá `\DomainException` v `recordPayment()` má komentář
  „Přiznaná zkratka“. **Předané RecordPaymentHandler**: `filename` už je `Application/Handler/`, sedí s namespace.
- **C-3** (controller importuje doménovou výjimku): řešeno na straně Deptrac v kap. 17, kontroler zůstává.

### testing_ddd.md (kap. 17)
- **C-3** #architektonicke-testy: Deptrac povoluje `Presentation → Domain` a komentář uvádí důvod (doménové výjimky
  → HTTP status).
- **C-5** #integracni-testy: `testExistsByEmail` nahrazen testem `testFindByEmailSeesUserOnlyAfterFlush`
  (`assertNull` / `assertNotNull` nad `findByEmail()`). Z InMemory fake odebrána metoda `existsByEmail()`,
  kterou rozhraní nemá a kniha ji jinde nepoužívá.
- **C-12** #integracni-testy: místo „Projekt si vybere jednu variantu“ text popisuje kanonické dělení.
  `get()` a `XNotFoundException::withId()` se použijí tam, kde absence je chyba. `find…(): ?X` zůstává tam, kde chybou není.
- **C-14** #filozofie-testovani (callout „Testovací strategie“): vypuštěno „handler volá správné metody repozitáře“.
  Test teď ověřuje stav a odeslané události, překlad unique constraintu jde do integračního testu.
- **C-10** #testovani-asynchronnich-toku: `eventId: Uuid::fromString('…')` a `use Symfony\Component\Uid\Uuid`.
- **C-30** #testovani-asynchronnich-toku:
  - Konfigurace `in-memory://` i `test://` teď definuje `async_commands` a `async_events`.
  - `messenger.transport.async` → `async_commands`, `transport('async')` → `transport('async_commands')` (4×).
  - Komentář v testu outboxu sladěn s touto konfigurací.
  - Doplněna věta o posluchači `UserRegistered`, který odešle `SendWelcomeEmail`, a o routingu `SendWelcomeEmail: async_commands`
    s odkazem na `/cqrs#messenger-config-heading`.
- **T-2**: „modular monolith“ → „modulární monolit“.
- **L-1**: „Méně známé vzory“ → „Doplňující taktické vzory“, „Implementace v Symfony“ → „Implementace v Symfony 8“.
- **Navíc (nový nález, rozpor kap. 17 × kap. 10)**: funkční test registrace (`RegistrationControllerTest`) neodpovídal
  kontroleru z kap. 10.
  - Route `/api/users/register` → `/api/register` (7×, i v testech 17.07).
  - Tělo 201 teď ověřuje `['status' => 'created']` místo neexistujících `userId`/`email`.
  - V testu 409 bylo heslo `Heslo123!` (9 znaků). Validace `Length(min: 12)` by vrátila 422, test by tedy spadl už na první 201.
    Heslo je teď `SilneHeslo123!`.
  - Test chybějících polí očekává 422 místo 400 (ověřeno v `vendor/…/RequestPayloadValueResolver.php`: chybějící argumenty
    se sbírají jako `PartialDenormalizationException` → `$validationFailedCode`).
  - Test 422 posílá `HTTP_ACCEPT: application/json` a čte `violations[0].propertyPath` místo `errors[0].field`
    (viz NEJISTÉ).

### migration_from_crud.md (kap. 18)
- **C-35** #strangler-princip-heading: koexistenci propojuje ACL. Sdílená databáze je jen přechodný stav, s odkazem na
  #datova-migrace-strangler-heading.
- **Předané TOCTOU**: text v 18.08 už TOCTOU přiznával a odkazoval na `/implementace-v-symfony#register-race-heading`.
  Opraven zbývající komentář v legacy kontroleru („kontrola unikátnosti (patří do doménové služby)“). Teď pojmenuje
  TOCTOU race a cíl, kterým je unique constraint.
- **C-12** #repository-interface-heading: `findActiveUsers()` vypuštěn z rozhraní i implementace, spolu s nepoužitými
  importy `UserName` a `UserStatus`. Doplněna věta, že seznamové dotazy obsluhuje read model z kroku 4 (#cqrs-postupne).
- **C-34 délka hesla** (za #char-test-heading): doplněn odstavec o záměrné změně chování (legacy 8 znaků × cílový
  `HashedPassword::fromPlainText()` 12). Charakterizační test se přepisuje vědomě, změnu schvaluje produkt.
- **C-34 hlášky**: výjimky v legacy `UserService` i v cílovém stavu anglicky (`Email`, `VerificationToken`,
  `UnmappableLegacyStatusException`, `ForbiddenEmailDomainException`).
- **T-8**: „read-modelů“ → „read modelů“.
- **L-1**:
  - „Context Mapping“ → „Bounded Context a Context Mapping“, „Event Storming“ → „Event Storming a Domain Storytelling“.
  - „Implementace v Symfony“ / „Implementace DDD v Symfony“ → „Implementace v Symfony 8“.
  - „CQRS v Symfony“ → „CQRS“ (2×), „Testování DDD kódu v Symfony“ → „Testování DDD“.
  - „DDD v praxi: kde to bolí“ → „DDD v praxi – kde to bolí“.
- **T-7**: kapitola používá „Strangler Fig Pattern“ nebo zkrácené „Strangler Fig“. Obojí je v souladu s rozhodnutím,
  beze změny.

## Vědomě neopraveno
- JSON chybové odpovědi legacy kontroleru (`'Neplatný e-mail'`, `'E-mail již existuje'`, `'Heslo musí mít alespoň 8 znaků'`)
  zůstaly česky. Nejsou to hlášky výjimek, ale texty pro uživatele české legacy aplikace.
- Hlášky `fail()` a `assert…()` v testech kap. 17 (`'Očekávána právě jedna událost…'`) zůstaly česky, nejde o výjimky
  doménového kódu.
- Krátké texty odkazů, které nejmenují kapitolu jako celek, ale sekci nebo pojem, zůstaly beze změny. Příklady:
  „Vertical Slice Architecture“, „Specification Pattern“, „Idempotent Inbox“, „Návrhu agregátu“ v 6. pádě.

## NEJISTÉ
- Formát těla 422 v `testReturns422ForInvalidEmail`. Podle znalosti Symfony `ProblemNormalizer` vrací
  `violations[].propertyPath`, a jen když klient pošle `Accept: application/json` (jinak HTML). Serializer ve vendoru
  tohoto repa není, nešlo to ověřit proti kódu. Původní `errors[0].field` ale žádný výchozí formát Symfony nevrací.
  Doporučuji ověřit v repozitáři ukázek.

## Zbývá na jiných souborech
- C-2: pravidla v lesser_known_patterns#mod-phparkitect, anti_patterns#infra-spravne-heading, architectural_styles#onion-symfony-heading.
- C-3: authorization_in_ddd (voter, SecurityUserProvider, listener 403/409, read model s DBAL).
- C-12: architectural_styles `findByCustomer`, performance_aspects `findHeaderById` / `OrderQueryRepository`.
- C-14: lesser_known_patterns#ds-srovnani („Unit s mockovanými repozitáři“).
- C-29: doctrine.yaml „cílový stav“ v aggregate_design má doplnit `email_vo` a `user_id` z kap. 10.
- C-30: performance_aspects (`messenger:consume async`), microservices (věta o vlastní konfiguraci), sagas (`queue_name`, `validation`).
- D-1: aggregate_design zůstává kanonickým místem výkladu, beze změny.

---
<!-- reports/fix-F6.md -->
# Fix F6 – kap. 06 basic_concepts, 07 aggregate_design, 08 lesser_known_patterns, 09 architectural_styles

Kontroly: lint-php-snippets (0 chyb), check_anchors, check_use_statements, check_faq_yaml,
check_static_calls, check_property_access, check_duplicate_listings OK, check_tonality 0.
Kotvy beze změny proti HEAD. Frontmatter nezměněn (`modified: 2026-09-27` už byl).
Highlights ověřeny přes hl.php.

## Opraveno

- **C-2**
  - lesser_known_patterns#mod-phparkitect: pravidlo 1 má výjimku pro identifikátory cizích kontextů (`App\*\Domain\ValueObject\*Id`) s odkazem na /navrh-agregatu#references-by-id. Pravidlo 2 má tři vědomé výjimky: `Symfony\Component\Uid`, `Doctrine\ORM\Mapping` a `doctrine/collections`.
  - architectural_styles#onion-symfony-heading: povolen `Symfony\Component\Uid` a cizí `*Id`.
  - architectural_styles#hexagonal-symfony-heading (odrážka „Adresář Domain/…“): povolen `Symfony\Component\Uid`.
- **C-12**
  - architectural_styles#hexagonal-priklad-heading: `findByCustomer()` vypuštěno z portu i z adaptéru (včetně nepoužitého importu). V portu je komentář, že seznamové dotazy obsluhuje read model.
  - architectural_styles#onion-priklad-heading: `find() ?? throw` → `get()`, s komentářem, že `get()` hází NotFound z `Domain\Exception`.
  - architectural_styles#clean-priklad-heading (`PlaceOrderUseCase`): `find() ?? throw` → `get()`.
  - lesser_known_patterns#fac-class: `getById()` → `get()`.
- **C-14** lesser_known_patterns#ds-srovnani: „Unit s mockovanými repozitáři“ → „Unit s InMemory fake repozitáři“.
- **C-16** architectural_styles#hybrid-priklad-heading: Core ve stromu je teď `Pricing/`: Cart, DiscountPolicy, PriceCalculator, CartRepository, use casy ApplyCouponToCart/CalculateCartPrice. Nad stromem je věta s odkazem na /subdomeny#subdomeny-na-bc: Pricing je Core, správa objednávek Supporting.
- **C-21** architectural_styles#konvence-heading: `SharedKernel/` nese doménové typy i technická rozhraní (bus). Evansův Shared Kernel je jen doménová část, odkaz na /context-mapping#shared-kernel.
- **C-24** basic_concepts (FAQ „Kdy použít Doménovou službu…“): místo „komunikuje s externím systémem“ platí, že služba počítá nad daty, která dostane. Vstup z externího systému jí dodá volající nebo doménové rozhraní implementované v infrastruktuře.
- **C-27** `final class Order` na těchto místech:
  - aggregate_design#references-by-id (kanonický `Order`, výřez s asymetrickou viditelností),
  - aggregate_design#symfony-doctrine (mapování),
  - basic_concepts#aggregates (varianta bez perzistence),
  - basic_concepts#aggregate-root-lifecycle (výřez).
- **C-28** basic_concepts#money: `Money::add()`/`subtract()` mají komentář „vědomá zkratka v Shared Kernelu“ (stejný jako v kap. 03).
- **C-29** aggregate_design#symfony-doctrine: cílový `doctrine.yaml` doplněn o `email_vo` a `user_id` s komentářem, že je přidává kap. 10.
- **C-34 hlášky** (sjednoceno na angličtinu):
  - aggregate_design `ShipmentId`: „Neplatné ShipmentId“ → „ShipmentId must be a valid UUID“,
  - basic_concepts `OrderItem`: „Množství musí být kladné.“ → „Quantity must be positive“,
  - lesser_known_patterns `LogicException` v #spec-query-kombinatory → anglicky,
  - argument operace `notAllowedInState('přidání položky' | 'odebrání položky')` → `'addItem'` / `'removeItem'` v kap. 06 a 07 (stejně jako anti_patterns).
- **T-4**
  - aggregate_design#invariants a #checklist: „process manager“ → „Process Manager“,
  - lesser_known_patterns#vztahy: „Application Service / Saga“ → „/ ságy“.
- **T-6** aggregate_design#checklist, architectural_styles#proc-styl → „Ubiquitous Language“.
- **T-7** architectural_styles FAQ: „Strangler Fig pattern“ a „strangler fig“ → „Strangler Fig Pattern“.
- **T-8** architectural_styles#vertical-slice: „Read Model“ → „read model“.
- **T-9**
  - lesser_known_patterns#vernon-rule-heading a FAQ: „agregátní kořen“ → „kořen agregátu“,
  - aggregate_design#why-aggregates: „(aggregate root)“ → „(Aggregate Root)“.
- **T-1** architectural_styles#kdy-vs: „mikroslužeb“ → „microservices“.
- **L-1**
  - aggregate_design#reference-strategies: „Výkonnostní aspekty“ → „Read modely, projekce a výkon“.
  - architectural_styles: „[Kdy zvolit modular monolith]“ → „[DDD a microservices]“ (řeší i T-2).
  - architectural_styles: „[Architektonické testy]“ (text říká „kapitola“) → „[Testování DDD]“.
  - architectural_styles: „[Implementace v Symfony]“ → „… 8“.
- **L-3** aggregate_design#symfony-doctrine:
  - Vernon: „probírá agregát v kap. 10 knihy IDDD a jeho perzistenci v kap. 12“,
  - komentář v `DoctrineOrderRepository`: „(viz kapitola CQRS)“ místo „kapitola 12“,
  - komentář v `Order::cancel()`: „z kapitoly Implementace v Symfony 8“ místo „z kapitoly 10“.
- **Předané – highlights**
  - kap. 07:
    - TransferMoneyHandler → wrapInTransaction, dvojí `get`, withdraw/deposit,
    - InitiateTransferHandler → komentář, withdraw, save,
    - Order.php → navíc privátní konstruktor, `customerId` a `placeWithFirstItem`,
    - Order.php (mapování): posun o 1 opraven, přidány sloupce ID,
    - DoctrineOrderRepository → persist + komentář o doctrine_transaction + „ŽÁDNÉ findAll“.
  - kap. 09:
    - src/Entity/Order.php → ORM atributy a invariant,
    - DoctrineOrderRepository → mapper/OrmEntity,
    - PlaceOrderController → port,
    - CalculateCartPrice → porty a načtení,
    - PlaceOrderUseCase → `get`, reference přes ID, publikace,
    - services.yaml → exclude.
- **Předané – driving port** v kap. 09: `interface PlaceOrder` → `ForPlacingOrders`. Změna se týká:
  - filename bloku (`ForPlacingOrders.php`),
  - stromu v #hexagonal-symfony-heading,
  - odrážky „inbound port“,
  - importu a typu v `PlaceOrderController`,
  - `PlaceOrderHandler implements ForPlacingOrders`,
  - příkladů driving portů v #hexagonal-typy-portu-heading (`ForPlacingOrders`, `ForCancellingOrders`, `ForGettingOrderHistory`) s větou o Cockburnově konvenci *For* a o tom, že se jméno nekříží s příkazem `PlaceOrder`.

## Vědomě neopraveno

- **C-6, C-9, C-13, C-15, C-26, C-33:** kap. 06/07 jsou v nich kanonickou stranou. Opravy patří do kap. 12, 16, 20 a 15.
- **C-3:** pravidlo 3 v lesser_known_patterns#mod-phparkitect zůstává. Porušení leží v kap. 11, 10 a 12.
- **Filename bloků** „doctrine.yaml (cílový stav po kapitole 15)“ a komentáře „(10.12)“, „(14.06)“ nesou vedle čísla i název kapitoly. Ponechány.
- **Frontmatter** `meta_keywords` v kap. 08 („ubiquitous language“) se podle briefu nemění.
- **Hybridní strom v kap. 09:** `Customer/ # SUPPORTING` a `Notifications/ # GENERIC` ponechány, s kap. 02 nejsou v rozporu.
- **Sufix `…Command`** řeší koordinátor.

## Zbývá na jiných souborech

- `InvalidOrderStateTransitionException` v implementation_in_symfony (kap. 10) má české šablony hlášek („Operaci „%s“ nelze provést…“, „Nelze přejít…“). Podle C-34 je přeloží vlastník kap. 10.
- event_storming.md:~296 volá `notAllowedInState('přidání položky', …)`. Má přejít na `'addItem'` jako kap. 06, 07 a 21.
- C-2 v anti_patterns#infra-spravne-heading (výjimky pro Uid a cizí `*Id`) řeší vlastník kap. 21.
- C-21 text v kap. 03, 19 a v glosáři řeší jejich vlastníci.
- C-27: jediné zbývající `class Order extends AggregateRoot` bez `final` je performance_aspects.md:128.

---
<!-- reports/fix-F7.md -->
# fix-F7 – authorization_in_ddd, event_storming, case_study, practical_examples

Kontroly: `lint-php-snippets` 36 + 4 + 19 + 8 bloků / 0 chyb; kotvy `{#…}` shodné s HEAD u všech
čtyř souborů; `scripts/check_anchors.php` OK; žádné em dash. Frontmatter beze změny (`modified`
už 2026-09-27). Nic necommitováno.

## Opraveno

### authorization_in_ddd.md (kap. 11)
- **C-3** #voter-handler-heading: sync `CancelOrderHandler` už neimportuje `OrderVoter`; volá
  `isGranted('order.cancel', …)` s komentářem proč (jako controller).
- **C-3** #async-is-granted-for-user: `RefundOrderHandler` místo `Identity\Infrastructure\…\SecurityUserProvider`
  závisí na rozhraní `App\Ordering\Application\Security\ActorProvider` (`byCustomerId(): UserInterface`),
  atribut `'order.refund'` řetězcem. Odstavec pod ukázkou přepsán (rozhraní v Application, implementace
  nad `SecurityUser` v infrastruktuře).
- **C-3 / C-21** #aggregate-403-vs-409-heading: `DomainExceptionListener` přesunut do
  `App\Ordering\Infrastructure\Http` (filename + namespace + komentář proč ne SharedKernel).
- **C-3** #field-query-heading, #field-list-filtering: `OrderDetailReadModel` a `OrderListReadModel`
  přesunuty do `App\Ordering\Infrastructure\ReadModel`; detail bere Symfony `UserInterface` místo
  `SecurityUser`, DTO zůstává v `Application\ReadModel` (import doplněn). Věta pod migrací: read model
  sahá na DBAL, proto infrastruktura, DTO patří aplikaci.
- **C-3 (navíc, stejný vzor)** #registration-two-writes-heading: `CreateSecurityUserOnUserRegistered`
  importoval z `Identity\Application` třídu `Identity\Infrastructure\Security\SecurityUser` →
  přesunut do `App\Identity\Infrastructure\Security`, věta proč.
- **C-17** tamtéž: „`Identity` je tu podpůrný kontext“ → generický (sedí s 11.02 i kap. 02).
- **C-27** #aggregate-level výřez `Order` → `final class Order extends AggregateRoot`.
- **C-34** hlášky výjimek anglicky: `TenantId`, `NotFoundHttpException` v `OrderValueResolver`,
  `CancellationWindowExpiredException`. (Důvody ve `Vote::addReason()` nechány česky – nejde
  o výjimky, ale o text pro uživatele.)
- **T-9** #use-case-voter, #aggregate-level: „aggregate root“ → „kořen agregátu“.
- **T-6** #anti-symfony-user-domain-heading: „ubiquitous language“ → „Ubiquitous Language“.
- **Předané g7 (OrderFactory)** #testing-aggregate-heading: popis „test-data builder“ → Object Mother
  s odkazem na `/testovani-ddd#test-doubles` a větou, že nejde o Foundry factory. Docblock
  „Builder jde…“ → „Továrna jde…“. Třída nepřejmenována.
- **Highlights**: všech 16 bloků s `highlights` bylo posunutých (i mimo mnou měněné bloky); přepočteno
  podle `SCR/hl.php` tak, aby ukazovaly na řádky, o kterých mluví text (výřez Voteru s důvody byl
  v pořádku, zůstal).

### event_storming.md (kap. 04)
- **C-35** Shipment BC → Shipping: `## Shipping BC` v events.md, `src/Shipping`, strom `Shipping/`,
  výčet BC v commit message, fotky `03-shipping-area.jpg`. Názvy událostí `Shipment*` a
  work object „Shipment Order“ ponechány (agregát zásilky, ne kontext).
- **T-3** „doménový event“ → „doménová událost“: #bp-vystup, nadpis #post-3-events (kotva beze změny),
  #post-5-repo, #commit-disclaimer-heading.
- **T-4** #pl-priklad, #pl-vystup: „process managery“ → „Process Managery“.
- **T-8** #pl-vystup: „read models“ → „read modelů“.
- **L-1** „Ságy a process managery“ → „Ságy a Process Managery“; „Testování v DDD“ → „Testování DDD“.
- C-27: výřez `Order` už byl `final`.

### case_study.md (kap. 24)
- **C-19** #architecture: Partnership přepsán jako koordinovaný vývoj dvou oddělených modelů
  (bez „týmů“); u obou Customer/Supplier downstream požadavky formuluje, upstream rozhoduje
  o dodání, s větou „bez tohoto hlasu by šlo o Conformist“.
- **C-20** tamtéž: nový odstavec – vztahy popisují spolupráci týmů, studii vyvíjí jeden tým, pět BC
  je nad pravidlem `/team-topologies#cognitive-load-rule`; studie ukazuje hranice modelu, ne topologii.
- **C-35** #discovery-boundaries-heading: „Core subdoména se běžně rozpadá…“ → 1:1 jako výchozí cíl,
  rozdělení jen z provozních důvodů, častější je N:1 u Supporting/Generic (sladěno s FAQ kap. 02).
- **C-12** #assign-task-handler-heading: `findById()` + null-check → `get($taskId)` (hází
  `TaskNotFoundException`), nepoužitý import odstraněn, komentář.
- **C-31** #read-model-reconciliation-heading: „výchozí tři pokusy“ → „výchozí tři opakování“.
- **C-34** `ProjectId`: „Neplatné ProjectId“ → „Invalid ProjectId“.
- **T-5** #discovery, callout, #lessons: „event storming(u)“ → „Event Storming(u)“.
- **T-6** FAQ: „ubiquitous language“ → „Ubiquitous Language“.
- **T-8** nadpis #get-projects-handler-heading „(Read Model)“ → „(read model)“ (kotva beze změny).
- **L-1** „Návrh agregátů“ → „Návrh agregátu“; „CQRS v Symfony 8“ → „CQRS“; „DDD v praxi: kde to bolí“
  → „DDD v praxi – kde to bolí“; „Testování DDD kódu v Symfony“ → „Testování DDD“.
- L-2 („[kapitoly 10]“ v #project-model-heading) už v souboru není.

### practical_examples.md (kap. 23)
- **C-4** #e-commerce-structure: Ordering ve stromu na vrstvy (`Domain`, `Application/Command/PlaceOrder.php`,
  `Application/Handler/PlaceOrderHandler.php`, `Application/Listener/…`, `Infrastructure/Http/PlaceOrderController.php`)
  + věta, že Cart je feature slice a Ordering drží vrstvy kvůli převzetí kódu z kap. 15.
- **C-4** #cart-checkout-to-order: listener `App\Ordering\Application\Listener\PlaceOrderOnCartCheckedOut`,
  import `App\Ordering\Application\Command\PlaceOrder`; text: týž FQCN jako v kap. 15, jinak
  `NoHandlerForMessageException`. FAQ o slices doplněno o výjimku Ordering.
- **T-9** „samostatný Aggregate Root“ → „samostatný kořen agregátu“ (komentáře `# Aggregate Root`
  ve stromech ponechány).
- **T-5** #zaver: „event stormingu“ → „Event Stormingu“.
- **T-8 / L-1** odkaz „CQRS – ViewModely a Read Modely“ → „CQRS“ (odolné vůči přejmenování nadpisu 12.09);
  FAQ „Implementace DDD v Symfony 8“ → „Implementace v Symfony 8“.

## Vědomě neopraveno
- **T-8** event_storming #notace (tabulka „Query Model / Read Model“) a #pl-co-pridava („zelené
  **Read Models**“): jde o anglické názvy typů lepíků v řadě s **Commands**, **Actors**, **Policies**,
  **External Systems** – přepis by rozbil paralelu legendy.
- **T-5** „EventStorming“ v event_storming: jen názvy zdrojů/šablon a historická věta „Jméno
  *EventStorming* jí dal…“.
- case_study naivní `GetProjectsHandler` s `findByMemberId()` (C-12 seznamové dotazy): kapitola ho
  sama označuje za naivní a v 24.06 nahrazuje read modelem – přiznaná výjimka.
- `ExplainedAccessDenied` (Infrastructure\Http) dál používá `OrderVoter::CANCEL` – infrastruktura
  na infrastrukturu, pravidla neporušuje.
- `App\SharedKernel\Infrastructure\…` (TenantFilter, TenantContextListener) a `SharedKernel\Authorization`
  v kap. 11 ponechány: C-21 se řeší větou v kap. 03/19/glosáři.
- Diagram `images/diagrams/7_examples/eshop/diagram.svg` (kap. 23) jsem nekontroloval/neměnil – pokud
  kreslí `PlaceOrder/`-slice, rozchází se s novým stromem.

## Zbývá mimo mé soubory
- Repozitář ukázek (ddd-symfony-examples): kap. 11 – `ActorProvider` místo `SecurityUserProvider`,
  přesuny `DomainExceptionListener`, `Order*ReadModel`, `CreateSecurityUserOnUserRegistered`
  do Infrastructure; kap. 24 – `TaskRepository::get()`. Pokud tam existují, README/kód by měly
  následovat.
- C-3 Deptrac/phparkitect pravidla (kap. 08, 17) a Presentation → Domain – jiní agenti.

---
<!-- reports/fix-F8.md -->
# F8 – templates/ddd/*.html.twig

Změněny jen viditelné texty. HTML struktura, `id`, Twig syntaxe beze změny (id ověřena diffem proti HEAD).
`php bin/console lint:twig templates/ddd/` → OK (13 souborů). `article_modified_time` u glosáře, cheat sheetu a zdrojů už 2026-09-27.

## Opraveno
- **C-8** glossary#term-korelacni-id – korelační ID vzniká jednou pro HTTP požadavek / spouštěcí zprávu, odvozené zprávy ho přebírají (negenerují nové). Přenos: HTTP hlavička mezi službami, stamp na envelope v Messengeru. Doplněno odlišení od byznysového korelačního klíče ságy (`orderId`).
- **C-18** glossary#term-konformista – Conformist zúžen na reportingový/průchozí kontext a oborové či regulatorní formáty. SaaS s navazujícími doménovými pravidly → ACL; výslovně „kniha platby (Stripe) i identitu (Auth0) napojuje přes ACL“ (sladěno s kap. 02 a aktuálním textem kap. 03 #conformist).
- **C-21** glossary#term-sdilene-jadro – doplněno, že namespace `App\SharedKernel` v ukázkách nese i technické bázové typy (rozhraní busů, abstraktní třídy) a Shared Kernel v Evansově smyslu tvoří jen jeho doménová část.
- **C-32** glossary#term-saga – „krátké procesy / mnoho kroků“ → rozhoduje tvar procesu: lineární posloupnost známá předem = choreografie, Process Manager tam, kde se další krok určuje za běhu nebo kroky neběží za sebou (podle sagas#choreografie-stale-validni-heading). PM už je v hesle popsán jako orchestrační komponenta, ne agregát.
- **C-34** glossary#term-domenova-vyjimka – doplněno, že porušení formátu v konstruktoru VO (neplatný e-mail, záporná částka) kniha hlásí jako `\InvalidArgumentException` (basic_concepts#vo-validation).
- **C-9** glossary#term-read-model – dřív „synchronně, nebo asynchronně přes projekce“ (projekce = asynchronní). Nyní: synchronně v téže transakci, nebo asynchronně; v obou případech ho obvykle plní projekce.
- **C-35 (Dispatcher vs Messenger)** resources.html.twig – Symfony Event Dispatcher: „Kniha ho používá pro technické háčky frameworku (kernel eventy, Doctrine lifecycle). Doménové události uvnitř kontextu vede synchronní `event.bus` Messengeru.“ Symfony Messenger: „command, query i event bus“.
- **L-1** názvy kapitol v textech odkazů:
  - glossary: „kapitola CQRS v Symfony 8“ ×2 → „kapitola CQRS“; mapovací tabulka „CQRS v Symfony 8“ → „CQRS“; „Testování DDD kódu“ ×2 → „Testování DDD“; „Migrace z CRUD architektury na DDD“ → „Migrace z CRUD na DDD“.
  - cheat_sheet: „CQRS v Symfony 8“ → „CQRS“; „Implementace DDD v Symfony 8“ → „Implementace v Symfony 8“; „Co je DDD · Subdomény“ → „Co je Domain-Driven Design · Subdomény: Core, Supporting, Generic“.
- **T-7** hub_practice.html.twig – „postupný přechod vzorem Strangler Fig“ → „podle Strangler Fig Pattern“.

## Ověřeno, bez změny
- **C-16/C-17** glossary#term-podporna-subdomena (sledování objednávek = Supporting) a #term-genericka-subdomena (Auth0, Stripe, e-maily = Generic) odpovídají kap. 02 (#subdomeny-na-bc). Beze změny.
- **Sufix `…Command`/`…Query`** – v glosáři ani cheat sheetu žádný (jen Symfony `Console Command` jako název konstruktu). `OrderPlacedIntegrationEvent` odpovídá kódu knihy (33×), pravidlo bez „Event“ platí pro doménové události.
- **T-1…T-9** v šablonách: „microservices“, „modulární monolit“, „doménová událost“, „Sága / Process Manager“, „Event Storming“, „Ubiquitous Language“, „read model“, „kořen agregátu“ – bez odchylek (kromě T-7 výše).
- C-12 (`get()`/`save()` v ff-repository), C-14 (fake jako výchozí dvojník repozitáře), C-23 (BC ≠ nasazení), C-25 (anémický model = anti-vzor), C-34 hranice přes kontrakt (mapovací tabulka) – šablony už odpovídají kanonu.

## Vědomě neopraveno
- Cheat sheet, průchody pro architekta, migraci a tech leada (#path-architekt-heading, #path-migrace-heading, #path-techlead-heading) se liší od cest v předmluvě (g11). Koordinátor nerozhodl, která sada je zdroj pravdy, a persony jsou definované jinak. Juniorskou cestu sladil už g11.
- glossary#term-invariant, příklad „Celková cena objednávky nesmí být záporná“: jde o invariant agregátu (součet), ne o formát VO. S C-13 to nekoliduje.

## Na jiných souborech
- sagas.md#korelacni-id-heading: „nese stejné korelační ID, typicky `orderId`“ – podle C-8 přejmenovat na byznysový korelační klíč. Glosář už ten rozdíl popisuje a kapitola na něj odkazuje.
- implementation_in_symfony.md#dispatcher-vs-messenger-heading: zdroje teď tvrdí kanonickou verzi C-35. Kapitola 10 se musí sladit.
