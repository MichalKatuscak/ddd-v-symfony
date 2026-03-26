# Design: Kapitola "DDD v praxi — kde to bolí"

**Datum:** 2026-03-26
**Status:** Schváleno uživatelem

## Přehled

Nová kapitola akademické příručky DDD v Symfony. Katalog 20 reálných bolestivých míst, na která narazíte při implementaci DDD v PHP/Symfony — problémů, na které standardní DDD literatura většinou neupozorňuje.

## Umístění v příručce

- **URL:** `/ddd-v-praxi-kde-to-boli`
- **Route name:** `ddd_pain_points`
- **Pozice:** za Ságami (`/sagy-a-process-managery`), před Praktickými příklady (`/prakticke-priklady`)
- **Soubory:**
  - `templates/ddd/ddd_pain_points.html.twig`
  - `src/Controller/DddController.php` (nová akce)
  - `public/js/modern-script.js` (přidat do CHAPTERS)
  - `templates/ddd/index.html.twig` (přidat feature card + aktualizovat počet kapitol)

## Struktura kapitoly

### Úvod
Krátký preambul: DDD teorie je přehledná, implementace přináší třecí plochy. Tato kapitola je katalog — pro každý problém: popis, příčina, doporučené řešení (+ kód kde je to výmluvné).

### Blok A — Doctrine vs. doménový model (6 témat)

**A1. Transakce přes agregáty a Doctrine Unit of Work**
- Problém: `flush()` commituje vše najednou; jak atomicky uložit změny ve dvou agregátech, aniž porušíme hranice
- Řešení: Application Service jako transakční hranice, `@Transactional` wrapper, kdy je přípustné volat dva repozitáře v jedné transakci
- Kód: ukázka Application Service s `beginTransaction()`/`commit()`

**A2. Unit of Work a "špinavé" agregáty**
- Problém: Doctrine sleduje všechny načtené entity; neúmyslný `flush()` commituje změny, které jsme nechtěli
- Řešení: oddělené `EntityManager` pro read model, `detach()`, clear scope

**A3. Mapping složitých Value Objects**
- Problém: `#[Embedded]` nestačí pro polymorfní VO, nullable kolekce VO, VO s vlastní logikou
- Řešení: Custom Doctrine Type, konverze v repozitáři (mapper pattern), kdy použít which approach
- Kód: ukázka Custom Type pro Money VO

**A4. Lazy loading vs. bohaté agregáty**
- Problém: Doctrine chce proxy objekty, doménový model nechce infrastrukturní závislosti; `__toString()` nebo metody mohou triggerovat lazy load mimo transakci
- Řešení: explicitní `fetch: EAGER` pro části agregátu, inicializace v konstruktoru, repository fetch strategy

**A5. Identity generation — kdy a kde**
- Problém: UUID v konstruktoru vs. Doctrine SEQUENCE, konflikty při persist před flush, testovatelnost
- Řešení: vždy generovat v doméně (konstruktor), Doctrine `CUSTOM` generator, ukázka UUID v PHP 8.4+

**A6. Polymorfismus v doméně a Doctrine discriminator map**
- Problém: discriminator map je těžkopádná pro složitou hierarchii; přidání nového subtypu vyžaduje změnu anotace na rodičovské třídě
- Řešení: Flat table + Custom Type pro type field, Value Object pro varianty místo dědičnosti

### Blok B — Asynchronní infrastruktura (4 témata)

**B1. Outbox pattern — zaručené doručení doménových událostí**
- Problém: `flush()` proběhne, server spadne před `$bus->dispatch()` → událost se ztratí
- Řešení: Outbox tabulka + Doctrine event listener + Messenger worker pro polling
- Kód: OutboxEvent entita, OutboxPublisher, worker command

**B2. Debugging ztracené zprávy v Messengeru**
- Problém: zpráva zmizí v async frontě, `messenger:consume` nic nespotřebovává, nevíme proč
- Řešení: `messenger:failed` queue, `messenger:failed:show`, structured logging s correlation ID, Sentry integration
- Kód: ukázka Middleware pro correlation ID logging

**B3. Idempotence handlerů**
- Problém: zpráva se doručí dvakrát (retry po timeoutu), handler ji zpracuje dvakrát → duplicitní objednávka, dvojitá platba
- Řešení: idempotency key v message envelope, deduplikační tabulka, `UniqueConstraint` jako pojistka
- Kód: IdempotencyMiddleware

**B4. Ordering zpráv — zpráva B dorazí před A**
- Problém: `OrderShipped` dorazí před `OrderPlaced` (různé workery, různé rychlosti); handler čeká na stav, který ještě neexistuje
- Řešení: optimistické čekání s retry (DelayStamp), inbox buffer, přijetí eventual consistency v read modelu

### Blok C — Modelování (4 témata)

**C1. Kde žije validace**
- Problém: validace je na 3 místech (Symfony Validator, Application Service, doménový konstruktor) → duplicita nebo díry
- Řešení: doménová invarianta = v konstruktoru/metodě agregátu (vždy); formátová validace = API/formulářová vrstva; business rule = doména nebo domain service; tabulka kdy co kam

**C2. Modelování stavového automatu bez anémického modelu**
- Problém: `$order->setStatus('shipped')` je anémický; jak modelovat stavy s přechody, guard conditions a side effecty čitelně
- Řešení: explicitní metody (`ship()`, `cancel()`), State pattern pro komplexní automaty, Symfony Workflow jako infrastrukturní helper (ne doménová závislost)

**C3. Anti-Corruption Layer k externím API**
- Problém: Stripe/Fakturoid/Ares objekty prosakují do domény; změna externího API = změna doménového kódu
- Řešení: Port (interface v doméně) + Adapter (infrastruktura); ukázka `PaymentGateway` interface vs. `StripePaymentGateway`
- Kód: ukázka rozhraní + adaptéru

**C4. Ubiquitous Language drift**
- Problém: po 6 měsících kód mluví jinak než doménový expert; `Invoice` v kódu = `Faktura` pro zákazníka = `Bill` v účetnictví
- Řešení: Glossary-driven development, pravidelné Event Storming session, ADR záznamy pro přejmenování

### Blok D — Symfony-specifické třenice (3 témata)

**D1. Symfony Form vs. Command**
- Problém: `FormType` chce mutable objekt, Command má být immutable DTO; jak nepropustit `FormInterface` do aplikační vrstvy
- Řešení: Form mapuje na plain array → Application Service sestaví Command; ukázka oddělení

**D2. API Platform vs. doménové agregáty**
- Problém: API Platform chce přímý přístup k entitám (State Provider/Processor); agregáty nechceme serializovat přímo
- Řešení: oddělené API resource DTO, vlastní StateProvider/Processor jako adapter, `#[ApiResource]` na DTO ne na agregátu
- Kód: ukázka StateProcessor volající Application Service

**D3. Symfony Security Voter vs. doménová oprávnění**
- Problém: business pravidla přístupu (např. "objednávku může zrušit jen zákazník nebo admin do 24h") patří do domény, ale Voter žije v infrastruktuře
- Řešení: Voter jako thin adapter volající doménovou metodu `$order->canBeCancelledBy($user)`; doménová logika zůstává testovatelná bez frameworku

### Blok E — Organizace a tým (3 témata)

**E1. Business case pro DDD refaktoring**
- Problém: management vidí náklady refaktoringu, ne benefity; "přepište to do DDD" zní jako akademická čistota, ne business hodnota
- Řešení: measurable metriky (time-to-feature, bug rate v modifikovaných souborech), strangler fig jako low-risk přístup, case study frameworky (před/po)

**E2. Postupné zavedení — strangler fig pattern**
- Problém: big-bang rewrite selže; jak zavádět DDD inkrementálně v existujícím CRUD projektu
- Řešení: identifikovat highest-churn modul, zavést DDD pouze tam, fasáda přes legacy kód, koexistence CRUD a DDD ve stejné aplikaci

**E3. Knowledge silos a bus factor**
- Problém: pouze jeden vývojář rozumí doménovému modelu → bus factor 1; onboarding nových členů trvá měsíce
- Řešení: living documentation (testy jako dokumentace), Event Storming jako sdílená aktivita, Architecture Decision Records, doménový glosář jako součást repozitáře

### Závěr
- Shrnutí: nejdůležitější poučky z každého bloku
- Cvičení: 3–5 praktických úkolů

## Styl a délka

- Konzistentní s ostatními kapitolami (Twig, česky, akademický ale přístupný tón)
- Kódové bloky: PHP 8.4+, Symfony 8, `<pre><code class="language-php">`
- Note boxy (`div.note`, `div.tip`) pro upozornění a doporučení
- Tabulky pro přehledy (kdy co použít)
- Délka: odhadovaně 2–3× délka kapitoly Ságy — je to záměrné, kapitola je komprehenzivní katalog
- SEO: meta description, keywords, JSON-LD, breadcrumb, ARIA

## Navigace

- JS CHAPTERS array: vložit za `/sagy-a-process-managery`, před `/prakticke-priklady`
- Odkaz "v další kapitole" ze Ság → sem
- Odkaz "v předchozí kapitole" z Praktických příkladů → sem
- Index stránka: přidat feature card, aktualizovat počet kapitol (15 → 16)
