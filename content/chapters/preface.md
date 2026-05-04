---
route: preface
path: /predmluva
title: Předmluva
page_title: "Předmluva | DDD Symfony"
meta_description: "Jak číst tuto knihu o Domain-Driven Design v Symfony 8 – pro koho je, co pokrývá, doporučené čtecí cesty podle role."
meta_keywords: "předmluva, DDD, Symfony, jak číst, doporučená cesta čtení"
og_type: article
published: "2026-05-04"
modified: "2026-05-04"
breadcrumb_name: Předmluva
schema_type: TechArticle
schema_headline: "Předmluva: Domain-Driven Design v Symfony 8"
chapter_number: "00"
category: Úvod
deck: "Co je tato kniha, pro koho je, jak je strukturovaná a jak ji číst podle role čtenáře."
reading_time: 8
difficulty: 1
---

Tato kniha vznikla z opakované zkušenosti: vývojář otevře *Domain-Driven Design: Tackling Complexity in the Heart of Software* od Erica Evanse, přečte 560 stran teorie a zavře knihu se dvěma otázkami. Kde začít? A jak to konkrétně udělat v Symfony? Mezi originálním textem z roku 2003 a praktickým PHP projektem v roce 2026 leží silná vrstva implementačních detailů, kterou Evans nemohl pokrýt. Vaughn Vernon ji v *Implementing Domain-Driven Design* (2013) zaplnil pro Javu a C#. Pro PHP a Symfony zatím podobně systematická kniha nebyla.

Cílem této knihy je tu mezeru zaplnit. Začínáme tím, kdy DDD vůbec dává smysl, pokračujeme přes strategický a taktický design až po konkrétní Symfony 8 kód s Doctrine ORM, Symfony Messenger a PHP 8.4. Každá kapitola obsahuje funkční ukázky, které můžete převzít do svého projektu, ne jen pseudokód.

## P.01 Pro koho je tato kniha {#pro-koho}

Kniha předpokládá zkušenost s PHP a Symfony, objektově orientovaným programováním a základními designovými vzory. Nepředpokládá zkušenost s DDD. Pokud znáte Symfony Controller, Doctrine entitu, Dependency Injection a chápete rozdíl mezi `interface` a abstraktní třídou, máte vše potřebné.

Kniha je psaná pro pět typických rolí:

- **Senior PHP developer**, který do projektu narazil na limity klasické vrstvené architektury – `OrderService` má 1500 řádků, každá nová feature způsobí regresi v jiné oblasti, onboarding nového kolegy trvá měsíce.
- **Symfony developer**, který si všiml, že větší projekty „rostou“ jinak než malé, a hledá strukturovanější přístup než jen Controller-Service-Repository.
- **Architekt**, který stojí před rozhodnutím, jaký přístup k modelování doménové logiky zvolit – DDD, klasické CRUD, modulární monolit, nebo microservices.
- **Tech lead**, který musí svému týmu vysvětlit, *proč* a *jak* DDD zavést, a hledá argumenty pro management v termínech DORA metrik a obchodní hodnoty.
- **Vývojář migrující z CRUD aplikace na DDD**, který má v produkci spaghetti kód a hledá inkrementální cestu ven.

Pro každou roli kniha nabízí jinou doporučenou cestu čtení – viz [P.03 Jak číst tuto knihu](#jak-cist).

### Co tato kniha není {#co-neni}

- **Ne úvod do PHP nebo Symfony.** Pokud Symfony vidíte poprvé, projděte nejprve [oficiální Symfony dokumentaci](https://symfony.com/doc/current/index.html).
- **Ne kuchařka „kopíruj-vlož“.** Kód v knize ilustruje vzory v kontextu, ne hotová řešení pro váš konkrétní projekt. DDD vyžaduje úsudek nad doménou, ne mechanickou aplikaci šablon.
- **Ne kompletní reference DDD.** Pro hlubší teoretický základ čtěte Evanse (2003), Vernona (2013) a Khononova (2021) – odkazy na konkrétní pasáže najdete na konci každé kapitoly.
- **Ne návod, jak prosadit DDD u nepřesvědčeného managementu.** Argumenty pro DDD jsou v knize, ale rozhodnutí závisí na konkrétním kontextu organizace.

### Předpoklady {#predpoklady}

Kniha předpokládá tyto výchozí znalosti:

- **PHP 8.1+:** atributy (`#[Attribute]`), enums, readonly properties, named arguments, `match`. Některé příklady používají PHP 8.4 (asymmetric visibility, property hooks).
- **Symfony 6+:** Service Container, Dependency Injection, Doctrine ORM, Symfony Messenger, atributy `#[Route]`, `#[AsMessageHandler]`. Většina kódu cílí na Symfony 8.
- **Objektově orientované programování:** dědičnost vs. kompozice, polymorfismus, zapouzdření, SOLID principy.
- **Designové vzory:** Repository, Factory, Strategy, Observer. Není nutné je znát formálně, ale měli byste je v kódu poznat.
- **Relační databáze:** ACID, transakce, indexy, JOIN, optimistický a pesimistický zámek.

Pokud některý z bodů „nesedí“, neznamená to, že knihu nemůžete číst – jen u některých kapitol budete potřebovat víc soustředění. Kapitoly o Event Sourcingu, Sagách a microservices jsou nejnáročnější.

## P.02 Co kniha pokrývá {#co-pokryva}

Kniha je rozdělená do osmi tematických částí. Pořadí kapitol je promyšlené – každá staví na předchozích – ale pro většinu rolí dává smysl číst selektivně podle vlastních potřeb.

### Část 1 – Strategický design (kap. 1–5) {#cast-1}

Strategický design rozhoduje, *kde* DDD vůbec aplikovat. Pokrývá filozofii DDD, Ubiquitous Language, identifikaci subdomén (Core, Supporting, Generic), Bounded Contexts a Context Mapping. Doplňují ho dvě praktické techniky: Event Storming Alberta Brandoliniho a Team Topologies (Skelton & Pais, 2019), bez kterých strategický design nefunguje v reálné organizaci.

Zde se rozhoduje, jestli má smysl pokračovat. Pokud z kapitoly 1 a 2 vyjde, že váš projekt nemá dost komplexní doménu pro DDD, ostatní kapitoly nejsou potřeba.

### Část 2 – Taktický design (kap. 6–9) {#cast-2}

Taktický design pokrývá konkrétní stavební bloky doménového modelu: entity, hodnotové objekty, agregáty, repozitáře, doménové služby, doménové události. Klíčová je **kapitola 7 o návrhu agregátu** – hranice agregátu je nejtěžší rozhodnutí v taktickém DDD a chyba zde stojí násobně víc než chyba v jednotlivé třídě.

Doplňující taktické vzory (Specification Pattern, Factory, Module) a srovnání architektonických stylů (Hexagonal, Onion, Clean Architecture) uzavírají taktickou část.

### Část 3 – Implementace v Symfony (kap. 10–11) {#cast-3}

Konkrétní mapování DDD do Symfony 8: adresářová struktura podle Bounded Contexts, vlastní Doctrine typy pro hodnotové objekty, Symfony Messenger jako Command/Query Bus, Dependency Injection a autowiring.

Kapitola 11 řeší autorizaci ve čtyřech vrstvách – Edge (firewall), Use Case (Voter), Aggregate (doménový invariant), Field (read model filtrace).

### Část 4 – Pokročilé vzory (kap. 12–15) {#cast-4}

CQRS (oddělení čtení a zápisu), Event Sourcing (stav jako sekvence událostí), Ságy a Process Managery (dlouho běžící procesy s kompenzací), Outbox Pattern (spolehlivé doručení doménových událostí).

Tyto vzory nejsou pro každý projekt. Kapitoly začínají rozhodovacím rámcem „kdy ano a kdy ne“.

### Část 5 – Výkon a testování (kap. 16–17) {#cast-5}

Výkonové aspekty (N+1 problém, lazy loading, read modely, snapshoty, hot aggregates) a testovací strategie (unit testy doménové vrstvy, integrační testy s Doctrine, architektonické testy s Deptrac/PHPArkitect).

### Část 6 – Migrace a microservices (kap. 18–19) {#cast-6}

Postupný přechod z CRUD architektury na DDD pomocí Strangler Fig Pattern. Vztah Bounded Context vs. microservice – kdy 1:1 dává smysl, kdy modulární monolit poráží distribuované služby a jak rozeznat distributed monolith včas.

### Část 7 – Provozní problémy a anti-vzory (kap. 20–22) {#cast-7}

Tři kapitoly s odlišným úhlem na to, co se v DDD pokazí. **Kapitola 20** pokrývá konkrétní provozní třenice s Doctrine, Messenger a Symfony Form. **Kapitola 21** je katalog kódových anti-vzorů (anémický model, Primitive Obsession, God Aggregate, sdílená databáze). **Kapitola 22** je rozhodovací rámec, kdy DDD vůbec nepoužívat.

### Část 8 – Praktické příklady (kap. 23–24) {#cast-8}

Tři krátké příklady (e-shop, blog, správa uživatelů) jako shrnující průřez. Závěrečná case study popisuje implementaci systému pro správu projektů krok za krokem – od doménové analýzy přes architekturu, agregáty, CQRS až po read modely s reconciliation.

> **Pozn.:** Mimo hlavní řadu kapitol existuje na webu ještě [DDD a umělá inteligence](/ddd-a-umela-inteligence) – přehled toho, co o vztahu DDD a AI říkají Eric Evans, Martin Fowler, Kent Beck a další. V tištěné a EPUB verzi knihy tato kapitola není, protože téma se v posledních letech intenzivně vyvíjí a aktualizace na webu jsou pružnější.

## P.03 Jak číst tuto knihu {#jak-cist}

Lineární čtení od první do poslední kapitoly funguje, ale málokdo ho potřebuje. Většina čtenářů má konkrétní bolest, kvůli které knihu otevřela. Pět doporučených cest podle role:

### Pro junior PHP developera {#cesta-junior}

Cíl: pochopit, co DDD je, a získat schopnost ho rozeznat v cizím kódu. Implementaci si zatím netroufnete – to přijde až s druhým a třetím projektem. Doporučená cesta v pořadí čtení:

- [Co je DDD](/co-je-ddd) – filozofie, klíčové pojmy, kdy DDD pomůže.
- [Základní koncepty DDD](/zakladni-koncepty) – entity, hodnotové objekty, agregáty, repozitáře. Nejdůležitější mentální model celé knihy.
- [Návrh agregátu](/navrh-agregatu) – jak agregát udělat dobře. Nejtěžší kapitola taktického designu, ale stojí to za to.
- [Implementace v Symfony](/implementace-v-symfony) – konkrétní kód, který můžete dnes použít.
- [Testování DDD](/testovani-ddd) – jak ověřit, že to funguje.

Volitelně po měsíci praxe: [CQRS](/cqrs) a [Anti-vzory](/anti-vzory).

### Pro senior PHP developera {#cesta-senior}

Lineární čtení od kapitoly 1 do 24. Pokud chcete postupovat rychleji, projděte strategickou část (kap. 1–5) a taktickou část (kap. 6–9), pak vyberte pokročilé vzory (kap. 12–15) podle aktuálního projektu.

Rychlá cesta: [Co je DDD](/co-je-ddd) → [Základní koncepty](/zakladni-koncepty) → [Návrh agregátu](/navrh-agregatu) → [Implementace v Symfony](/implementace-v-symfony) → [CQRS](/cqrs) → [Anti-vzory](/anti-vzory) → [Případová studie](/pripadova-studie).

### Pro architekta {#cesta-architekt}

Strategie a velký obraz. Méně kódu, víc rozhodnutí.

- [Co je DDD](/co-je-ddd) – pro kontext.
- [Subdomény: Core, Supporting, Generic](/subdomeny) – první strategický filtr.
- [Bounded Context a Context Mapping](/context-mapping) – jak nakreslit mapu vztahů mezi kontexty.
- [Conway's Law a Team Topologies](/team-topologies) – architektura kopíruje organizaci.
- [Migrace z CRUD](/migrace-z-crud) – Strangler Fig pro postupný přechod.
- [DDD a microservices](/ddd-a-microservices) – fyzické hranice nasazení.
- [Anti-vzory](/anti-vzory) – co dělat, abychom se vyhnuli klasickým chybám.
- [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) – upřímný rozhodovací rámec.
- [Případová studie](/pripadova-studie) – inspirace pro vlastní projekt.

### Pro tech leada {#cesta-techlead}

Kombinace organizační optiky a praktických problémů.

- [Conway's Law a Team Topologies](/team-topologies) – jak týmovou strukturou ovlivnit architekturu.
- [Event Storming a Domain Storytelling](/event-storming) – workshop, který zavedete do týmu.
- [Migrace z CRUD](/migrace-z-crud) – jak postupně přejít bez velké přestávky.
- [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli) – co očekávat a jak s tím pracovat.
- [Anti-vzory](/anti-vzory) – kódové signály, které v code review hledat.
- [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) – kdy říct ne.

### Pro vývojáře migrujícího z CRUD {#cesta-migrace}

Konkrétní cesta, jak existující projekt postupně transformovat.

- [Co je DDD](/co-je-ddd) – ujistit se, že vůbec dává smysl.
- [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd) – upřímná kontrola, jestli to není chyba.
- [Subdomény](/subdomeny) – kde investovat modelovací úsilí.
- [Základní koncepty](/zakladni-koncepty) – výchozí slovník.
- [Návrh agregátu](/navrh-agregatu) – nejdůležitější taktický vzor.
- [Migrace z CRUD na DDD](/migrace-z-crud) – Strangler Fig Pattern v Symfony.
- [DDD v praxi – kde to bolí](/ddd-v-praxi-kde-to-boli) – realistická očekávání.

Po této sekvenci selektivně další kapitoly podle konkrétní bolesti, kterou v aplikaci pociťujete.

## P.04 Konvence v knize {#konvence}

Několik konvencí, které platí napříč knihou.

### Hlas a tón

Kniha používá vykání. Věty jsou krátké a jedna věta říká jednu věc. Žádný marketingový jazyk – místo „mocný framework“ stojí v textu konkrétně, co Symfony Messenger umí a co ne. Žádné osobní komentáře autora, žádné nadsázky.

### Styl kódu

Kód cílí na PHP 8.4 a Symfony 8 s Doctrine ORM 3. Některé příklady používají rysy z PHP 8.4 (asymmetric visibility, property hooks, readonly properties). Pokud váš projekt běží na starší verzi, princip zůstává platný, jen syntaxe je jiná.

Atributy Doctrine (`#[ORM\Entity]`) jsou na doménových třídách jako pragmatická výchozí volba. Pro striktní oddělení doménové vrstvy od ORM existuje [Persisted Object Pattern](/implementace-v-symfony#persisted-object-pattern) – samostatná persistence třída plus mapper. Většina příkladů v knize používá první variantu, protože v reálných Symfony projektech je rozšířenější.

### Callouty

Kniha používá čtyři typy callout boxů:

- **Note** (modrý) – informace navíc, kontext, odkaz na hlubší zdroj.
- **Pattern** (zelený) – doporučený vzor s konkrétním kódem.
- **Warn** (oranžový) – riziko, past nebo častá chyba.
- **Anti** (červený) – anti-vzor, kterému se aktivně vyhnout.

### Diagramy

Diagramy jsou v PlantUML zdrojovém formátu v `templates/diagrams/`, vyrenderované do SVG a vložené do textu. Pokud diagram potřebujete převzít, najdete `.puml` zdroj ve stejné složce jako SVG.

### Vnitřní odkazy

Vnitřní odkazy mezi kapitolami používají *cesty* (`/co-je-ddd`, `/zakladni-koncepty`), ne čísla kapitol. Přečíslování tak odkazy nezneplatní. Externí odkazy na knihy a články používají plný URL.

### Citace

Knihy a referenční články jsou citované přímo v textu (např. „Vernon, *Implementing DDD*, kap. 8“) a souhrnně na konci každé kapitoly v sekci „Další četba“. Hlavní zdroje, na které kniha staví:

- Eric Evans, *Domain-Driven Design: Tackling Complexity in the Heart of Software* (Addison-Wesley, 2003).
- Vaughn Vernon, *Implementing Domain-Driven Design* (Addison-Wesley, 2013) a *Domain-Driven Design Distilled* (2016).
- Vlad Khononov, *Learning Domain-Driven Design* (O'Reilly, 2021).
- Sam Newman, *Building Microservices, 2nd ed.* (O'Reilly, 2021).
- Chris Richardson, *Microservices Patterns* (Manning, 2018).
- Matthew Skelton & Manuel Pais, *Team Topologies* (IT Revolution, 2019).
- Martin Fowler, *Patterns of Enterprise Application Architecture* (Addison-Wesley, 2002).

## P.05 Co dál {#co-dal}

Pokud jste tu poprvé, otevřete [kapitolu 1: Co je DDD](/co-je-ddd). Po přečtení byste měli mít jasno, jestli má smysl pokračovat.

Pokud DDD už znáte a hledáte konkrétní téma, projděte si [Cheat Sheet](/cheat-sheet) – jednostránkový přehled vzorů s odkazy na příslušné kapitoly. Pro definice termínů slouží [Glosář](/glosar).

Kniha je živý dokument. Aktuální verze, errata a komentáře čtenářů najdete na [ddd-v-symfony.cz](https://ddd-v-symfony.cz). Připomínky a opravy vítám na adrese uvedené tamtéž.
