Prošel jsem si seznam všech 16 kapitol + glosář a pak jsem přes celý korpus zjistil, kde se klíčová DDD témata vyskytují (jestli mají vlastní kapitolu, nebo jsou jen rozesetá). Pohled v roli autora:

Největší díry (cítím to silně)

1. Strategic DDD: Context Mapping 🚨
Polovina původního Evansova „Modrého draka" — vztahy mezi bounded contexty: Partnership, Customer/Supplier, Conformist, Anticorruption Layer, Open Host Service, Published Language, Shared Kernel, Separate Ways. Termíny jsou
rozesety (ACL 21×, Shared Kernel 16×, Open Host 7×), ale vlastní hloubkovou kapitolu nemají. Bez nich má kniha jen taktický rozměr. Toto je IMHO jednoznačně priorita č. 1.

2. Subdomény: Core / Supporting / Generic 🚨
Jen 5 zmínek, pouze v glosáři. Přitom jde o úplně první strategické rozhodnutí (kam investovat modelovací úsilí). Mohla by být buď samostatná kapitola, nebo silná podkapitola v „Co je DDD" / „Kdy DDD nepoužívat".

3. Discovery techniky: Event Storming + Domain Storytelling
Event Storming je dnes mainstream — bez něj nemá kapitola Event Sourcing pořádnou rampu. Současné zmínky (13×) jsou jen kontextové. Stálo by za to: jak workshop připravit, jak se k němu chovat v Symfony týmu, kde končí
workshop a začíná kód.

4. Hexagonal / Ports & Adapters / Onion / Clean Architecture
Pouze 6 zmínek a všechny v glosáři/zdrojích. Kapitola 03 (Vertikální slice) má skvělé srovnání s tradičním DDD — přirozeně by tam patřil i třetí pohled: hexagonální / cibulová architektura. Čtenář se na to ptá vždycky.

Užitečné, ale druhořadé

5. Outbox Pattern jako vlastní stránka — 39 zmínek, ale rozlité v pain_points + ES + sagách. Kritický produkční pattern; mohl by mít buď vlastní krátkou kapitolu, nebo silnou anchor sekci, na kterou se odkazuje odjinud.

6. DDD a microservices — pouze 3 zmínky. Moderní čtenář Symfony (zvlášť přicházející z Javy/.NET) to očekává: BC jako service boundary, integration events, deploy independence vs. modular monolith.

7. Autorizace/Permissions v DDD na Symfony — 9 zmínek o Voterech, ale jen v pain_points. „Kde má sedět autorizace? V agregátu? Ve voteru? V appce?" — to je dotaz č. 1 v praxi.

8. Specifications + Domain Services + Factories — Specification Pattern má 15 zmínek (hlavně v Symfony kapitole), Domain Services a Factories jsou jen letmé. Buď je dotáhnout v basic_concepts, nebo samostatná kapitola „Méně
známé taktické vzory".

Drobnosti, které „dotáhnou knihu k dokonalosti"

- Cheat sheet / one-pager s vizuálním přehledem všech vzorů a rozhodovacím stromem „když X, použij Y".
rozesety (ACL 21×, Shared Kernel 16×, Open Host 7×), ale vlastní hloubkovou kapitolu nemají. Bez nich má kniha jen taktický rozměr. Toto je IMHO jednoznačně priorita č. 1.

2. Subdomény: Core / Supporting / Generic 🚨
Jen 5 zmínek, pouze v glosáři. Přitom jde o úplně první strategické rozhodnutí (kam investovat modelovací úsilí). Mohla by být buď samostatná kapitola, nebo silná podkapitola v „Co je DDD" / „Kdy DDD nepoužívat".

3. Discovery techniky: Event Storming + Domain Storytelling
Event Storming je dnes mainstream — bez něj nemá kapitola Event Sourcing pořádnou rampu. Současné zmínky (13×) jsou jen kontextové. Stálo by za to: jak workshop připravit, jak se k němu chovat v Symfony týmu, kde končí
workshop a začíná kód.

4. Hexagonal / Ports & Adapters / Onion / Clean Architecture
Pouze 6 zmínek a všechny v glosáři/zdrojích. Kapitola 03 (Vertikální slice) má skvělé srovnání s tradičním DDD — přirozeně by tam patřil i třetí pohled: hexagonální / cibulová architektura. Čtenář se na to ptá vždycky.

Užitečné, ale druhořadé

5. Outbox Pattern jako vlastní stránka — 39 zmínek, ale rozlité v pain_points + ES + sagách. Kritický produkční pattern; mohl by mít buď vlastní krátkou kapitolu, nebo silnou anchor sekci, na kterou se odkazuje odjinud.

6. DDD a microservices — pouze 3 zmínky. Moderní čtenář Symfony (zvlášť přicházející z Javy/.NET) to očekává: BC jako service boundary, integration events, deploy independence vs. modular monolith.

7. Autorizace/Permissions v DDD na Symfony — 9 zmínek o Voterech, ale jen v pain_points. „Kde má sedět autorizace? V agregátu? Ve voteru? V appce?" — to je dotaz č. 1 v praxi.

8. Specifications + Domain Services + Factories — Specification Pattern má 15 zmínek (hlavně v Symfony kapitole), Domain Services a Factories jsou jen letmé. Buď je dotáhnout v basic_concepts, nebo samostatná kapitola „Méně
známé taktické vzory".

Drobnosti, které „dotáhnou knihu k dokonalosti"

- Cheat sheet / one-pager s vizuálním přehledem všech vzorů a rozhodovacím stromem „když X, použij Y".
- „False friends" sekce — ACL jako Access Control List vs. Anti-Corruption Layer, Repository v DDD vs. Doctrine Repository, Service v DDD vs. Symfony service. Lidé se v tom topí.
- Refactoring kuchařka (ne celá migrace z CRUD) — krátké recepty „mám anemic entitu → krok 1, 2, 3".
- Conway's Law / Team Topologies + DDD — strategie ↔ organizační struktura. Krátká kapitola, velký aha-efekt.
- Glossář Symfony↔DDD mappingu — jednostránková tabulka „MessageHandler = Application Service", „Voter = ?", „EventSubscriber vs. Domain Event Handler" atd.

Pokud bych měl vybrat jednu věc, kterou bych jako autor přidal nejdřív, byla by to kapitola Strategic DDD / Context Mapping — protože bez ní zůstává kniha skvělou taktickou příručkou, ale neposkytuje druhou polovinu DDD
myšlení.