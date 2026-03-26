# Specifikace: Ságy a Process Managery — nová kapitola

## Přehled

Nová kapitola pro DDD v Symfony průvodce pokrývající ságy a process managery jako vzor koordinace dlouhotrvajících business procesů napříč Bounded Contexts. Kapitola navazuje na CQRS a Event Sourcing, používá e-shop doménu jako průběžný příklad.

## Technické parametry

- **Soubor šablony:** `templates/ddd/sagas.html.twig`
- **Route name:** `sagas`
- **URL slug:** `/sagy-a-process-managery`
- **Pozice v navigaci:** Za Event Sourcing, před Příklady
- **Rozsah:** ~1500–1800 řádků (na úrovni CQRS/ES kapitol)
- **Jazyk:** čeština, formální akademický styl

## Změny v existujících souborech

### 1. `src/Controller/DddController.php`
- Přidat novou route+action `sagas()` vracející `sagas.html.twig`

### 2. `templates/base.html.twig`
- Přidat odkaz "Ságy" do sidebar navigace za Event Sourcing

### 3. `templates/ddd/cqrs.html.twig`
- Zkrátit sekci Saga (řádky 1657–1758) na 2–3 odstavce s definicí choreografie/orchestrace
- Odstranit kódový příklad `OrderProcessSaga` (přesouvá se do nové kapitoly)
- Přidat odkaz: „Podrobně viz kapitola Ságy a Process Managery"

### 4. `templates/ddd/index.html.twig`
- Aktualizovat počet kapitol (14 → 15)
- Přidat do pokročilé čtecí cesty

### 5. `templates/ddd/glossary.html.twig`
- Přidat odkaz na novou kapitolu do záznamu `#term-saga`

### 6. `templates/ddd/event_sourcing.html.twig`
- Upravit forward link na konci kapitoly: zmínit ságy jako navazující téma

## Struktura kapitoly (14 sekcí)

### 1. Proč potřebujeme ságy?
- Problém: objednávka v e-shopu = 4 kroky napříč Bounded Contexts (Ordering, Payment, Warehouse, Shipping)
- Každý kontext má vlastní databázi/agregát — nelze použít jednu DB transakci
- Proč 2PC (Two-Phase Commit) není řešení v DDD: výkonnostní overhead, tight coupling, single point of failure
- Konkrétní scénář selhání: platba proběhla, ale sklad nemá zboží — co teď?
- Citace: Garcia-Molina & Salem (1987), Vaughn Vernon (2013)

### 2. Kompenzační transakce
- Definice: akce, která sémanticky "vrátí" efekt předchozího kroku (ne technický rollback)
- Tabulka kompenzací pro e-shop: `ChargeCustomer` → `RefundCustomer`, `ReserveStock` → `ReleaseStock`, `CreateShipment` → `CancelShipment`
- Princip: kompenzace nemusí být přesný inverse — `RefundCustomer` může zahrnovat notifikaci, záznam do auditu
- Kódový příklad: rozhraní `CompensatableCommand`

### 3. Choreografie
- Princip: žádný centrální koordinátor, kontexty reagují na události ostatních
- PHP příklad: `PaymentContext` naslouchá `OrderPlaced`, `WarehouseContext` naslouchá `PaymentSucceeded`
- Symfony Messenger konfigurace s routing pro events
- Diagram toku: OrderPlaced → PaymentContext → PaymentSucceeded → WarehouseContext → StockReserved → ShippingContext
- Výhoda: loose coupling, jednoduchost pro 2–3 kroky

### 4. Limity choreografie
- Problém 1: při 5+ kontextech nikdo nevidí celý tok — "distributed spaghetti"
- Problém 2: přidání nového kroku = změna v existujícím kontextu (porušení OCP)
- Problém 3: diagnostika selhání — kde se proces zasekl? Který krok selhal?
- Problém 4: timeout — kdo je zodpovědný za detekci, že proces "visí"?

### 5. Orchestrace (Process Manager)
- `OrderProcessManager` jako stavový automat s definovanými stavy a přechody
- PHP enum `OrderSagaStatus`: `AwaitingPayment`, `AwaitingStockReservation`, `AwaitingShipment`, `Completed`, `Compensating`, `Failed`
- Třída `OrderProcessManager` — přijímá události, rozhoduje o dalším kroku, dispatche commands
- Diagram stavového automatu
- Výhoda: celý tok na jednom místě, snadno laditelný a rozšiřitelný

### 6. Perzistence stavu ságy
- Doctrine entita `SagaState`: `id`, `sagaType`, `correlationId`, `status`, `context` (JSON), `startedAt`, `updatedAt`, `failedAt`
- Repository `SagaStateRepository` s `findByCorrelationId()`
- Proč persistence nutná: worker restart, deployment, scaling
- Příklad obnovení po pádu workeru

### 7. Implementace v Symfony Messenger
- Kompletní end-to-end příklad spojující sekce 5 a 6
- `messenger.yaml` konfigurace: routing events a commands, async transport
- Event classes, Command classes, Process Manager handler
- `#[AsMessageHandler]` s union type
- Integrace s `SagaState` repository

### 8. Timeout handling
- Problém: co když `PaymentSucceeded` nikdy nepřijde?
- Delayed message `CheckSagaTimeout` s `DelayStamp`
- `CheckSagaTimeoutHandler` kontroluje stav a iniciuje kompenzaci
- Konfigurovatelné timeouty per krok

### 9. Kompenzační strategie v praxi
- Forward recovery (retry) vs. backward recovery (kompenzace)
- Sémantická kompenzace: nová doménová akce, ne DELETE
- Idempotence kompenzačních akcí
- `OrderProcessManager` s kompletní kompenzační logikou (reverse order)

### 10. Paralelní kroky
- Po platbě: současně rezervovat sklad i připravit fakturu
- Synchronizační bariéra v `SagaState.context`
- PHP příklad: oba handlery kontrolují dokončení druhého
- Warning: paralelní kroky zvyšují složitost kompenzací

### 11. Monitoring a observabilita
- Korelační ID prostupující všemi zprávami
- Structured logging stavu ságy
- Metriky: aktivní ságy, doba dokončení, míra selhání
- Dashboard pro "zaseklé" ságy

### 12. Testování ság
- Unit test stavového automatu: given/when/then
- Integrační test s in-memory Messenger bus
- Test kompenzační cesty
- `OrderProcessManagerTest` s 3–4 test cases

### 13. Co jsme se naučili
- 8–10 bodů shrnutí pokrývajících všechna klíčová témata

### 14. Zkuste sami
- 5 cvičení: rozšíření ságy, implementace timeoutu, paralelní krok, testování kompenzací, choreografická alternativa

## Šablona — povinné prvky

Každá stránka musí obsahovat (dle konvence projektu):
- `{% extends 'base.html.twig' %}`
- `{% block title %}` — meta title
- `{% block meta_description %}` — SEO popis
- `{% block meta_keywords %}` — klíčová slova
- `{% block structured_data %}` — schema.org TechArticle JSON-LD
- `{% block body %}` — `<article itemscope itemtype="https://schema.org/TechArticle">`
- Inline TOC (`<div class="table-of-contents">`)
- Sekce s `id` a `aria-labelledby` atributy
- Callout boxy: `.tip` (kód), `.note` (definice), `.warning` (anti-vzory)
- `{% block toc %}` — auto-TOC placeholder
- Citace s konkrétními zdroji

## Cross-linking strategie

### Nová kapitola odkazuje na:
- `basic_concepts` — doménové události, agregáty
- `cqrs` — CQRS, command/query bus, Symfony Messenger
- `event_sourcing` — event stream, projekce
- `glossary` — term-saga, term-eventual-consistency, term-korelacni-id
- `testing_ddd` — testovací vzory
- `practical_examples` — forward link na konci
- `case_study` — e-shop doména

### Ostatní kapitoly odkazují na novou:
- `cqrs` — zkrácená Saga sekce s odkazem
- `event_sourcing` — forward link
- `glossary` — odkaz v term-saga
- `index` — aktualizace počtu kapitol + čtecí cesta

## Akademický styl

- Formální registr, čeština
- Citace: Garcia-Molina & Salem (1987), Vernon (2013), Evans (2003)
- Progresivní složitost: problém → jednoduché řešení → limity → pokročilé řešení
- Kódové příklady v PHP 8.4+ (readonly, enums, strict types, match)
- Symfony 8 (Messenger, Doctrine ORM)
- Srovnávací tabulky pro kontrastní přístupy
- Callout boxy pro důležité poznámky a varování
