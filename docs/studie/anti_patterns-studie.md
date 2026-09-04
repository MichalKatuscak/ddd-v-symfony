# Studie: Anti-vzory a typické chyby v DDD

- **Kapitola:** `content/chapters/anti_patterns.md` (č. 21, kategorie Praxe, 1146 řádků)
- **Cesta:** /anti-vzory
- **Typ kapitoly:** definiční
- **Datum studie:** 2026-09-04

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| 21.01 Úvodem | ř. 22–41 | Vymezení vůči ch20 a ch22; definice anti-vzoru; klasifikace chyb na strategické / taktické / implementační | žádný | Klasifikace je autorská, bez atribuce |
| 21.02 Anémický model | ř. 43–258 | Anemic Domain Model je anti-vzor: porušení zapouzdření, ztráta modelu, duplicita, horší testovatelnost. Řešení = bohatá entita `User` s `register/activate/deactivate` | Fowler 2003 (odkaz `[1]`) | 216 řádků, největší sekce. Jednostranná – žádný protiargument |
| 21.03 Primitive Obsession | ř. 260–432 | Primitiva místo VO ruší sémantiku, umožňují záměnu ID, rozsévají validaci. Řešení = `Email`, `Money`, `Currency`, `OrderId`, `UserId` | žádný | Zde je jediná plná definice `Money`/`Currency` v knize vedle ch03 |
| 21.04 God Aggregate | ř. 434–604 | Příliš velký agregát = výkon, konkurence, coupling; symptom špatných hranic BC. Řešení = tři agregáty propojené přes ID | žádný (pravidlo „co nejmenší" bez atribuce) | Silně překrývá 07.04 a 07.12 |
| 21.05 Sdílená DB napříč BC | ř. 606–744 | Přímý JOIN do cizího kontextu je strategický anti-vzor. Řešení = Port + HTTP adapter (ACL), alternativa přes události | žádný | Kód `Connection::query()` s parametry je fakticky chybný |
| 21.06 Mutovatelné události | ř. 746–837 | Událost musí být „striktně immutable"; settery narušují audit i event sourcing. Řešení = `readonly` promoted properties | žádný | „Striktně" je silnější než Evansovo „ordinarily immutable" |
| 21.07 Logika v infrastruktuře | ř. 839–1001 | Repozitář a kontroler mají být tenké; logika patří do entity. Řešení = `DoctrineUserRepository` + `ActivateUserHandler` + tenký kontroler | žádný | Nejlepší praktická sekce kapitoly |
| 21.08 Over-engineering | ř. 1003–1007 | DDD ceremonie bez složité domény; deleguje na ch22 | odkaz na `/kdy-nepouzivat-ddd` | 5 řádků – pouhý ukazatel |
| 21.09 Ubiquitous Language | ř. 1009–1133 | Různá jména pro tentýž koncept; řešení = pojišťovací příklad + živý glosář | Vernon *IDDD* (zmínka bez konkrétna) | Duplikuje 20.03 C4 |
| FAQ | ř. 1135–1146 | 5 otázek ke stávajícím sekcím | – | Konzistentní s tělem |

Kapitola je katalog osmi anti-vzorů, ve kterém dominují ukázky kódu: z 1146 řádků jich zhruba 600 tvoří PHP bloky ve dvojicích „špatně / správně". Prostor dostává anémický model (19 % kapitoly) a Primitive Obsession; naopak strategická rovina je zastoupena jedinou sekcí (sdílená databáze) a over-engineering je jen odkaz. Kapitola u každého anti-vzoru nabízí alternativu v kódu, ale téměř nikde **rozpoznávací znak** – jak poznat, že se problém týká mě, a kde je hranice, za kterou už kritizovaný postup není chyba. Chybí jakákoli protistrana: všechny anti-vzory jsou podány jako uzavřená věc. Pět z osmi anti-vzorů má v knize plnohodnotnější zpracování jinde (07.04, 07.12, 03.12, 20.03, 22.09), zatímco anti-vzory, které jsou vlastní právě této kapitole (repozitář jako dotazovací API, VO s identitou, eventy jako aplikační hooky, BC podle technické vrstvy, kanonický podnikový model), v ní nejsou vůbec.

**Poznámka ke struktuře.** Kapitola nemá závěrečné shrnutí ani checklist, ačkoli jde o typ obsahu, který se čte referenčně (čtenář přijde ověřit jeden anti-vzor, ne přečíst 1146 řádků). Srovnatelná kapitola 07 checklist má (07.13). Kotva `#klasifikace-heading` uvádí trojdílné dělení chyb, které se ale ve zbytku kapitoly nijak nepromítá – sekce nejsou podle něj seřazené ani označené. Diagramy jsou dva (`21.2-A`, `21.4-A`), oba existují včetně light varianty, číslování `fig` odpovídá konvenci knihy.

## 2. Kanonické zdroje k tématu

**Anemic Domain Model.** Fowler článek publikoval 25. listopadu 2003 [1]. Jeho argumentace má dvě odlišné vrstvy, které kapitola slévá do jedné. První je puristická: „The fundamental horror of this anti-pattern is that it's so contrary to the basic idea of object-oriented design; which is to combine data and process together." Druhou Fowler uvádí explicitně jako důležitější, protože si je vědom slabiny té první: *„Now object-oriented purism is all very well, but I realize that I need more fundamental arguments against this anemia. In essence the problem with anemic domain models is that they incur all of the costs of a domain model, without yielding any of the benefits. The primary cost is the awkwardness of mapping to a database…"* Tedy: nákladová, ne estetická úvaha. Ve stejném odstavci Fowler odkazuje na Transaction Script a dodává „As I discussed in *P of EAA*, Domain Models aren't always the best tool."

Fowler rovněž výslovně brání vrstvu služeb: *„many OO experts do recommend putting a layer of procedural services on top of a domain model, to form a Service Layer. But this isn't an argument to make the domain model void of behavior."* Anti-vzorem není existence služeb, ale to, že v nich žije *veškerá* doménová logika.

**Transaction Script** je řádný vzor z *Patterns of Enterprise Application Architecture* (Fowler, 2002), katalogový záznam datován 5. března 2003 [2]: „Organizes business logic by procedures where each procedure handles a single request from the presentation." Není to selhání, ale volba pro doménu, kde je logiky málo.

**Big Ball of Mud.** Foote & Yoder, PLoP '97 / EuroPLoP '97, technická zpráva Washington University #WUCS-97-34, knižně jako kapitola 29 v *Pattern Languages of Program Design 4* (Addison-Wesley, 2000) [3]. Abstrakt textu je záměrně provokativní: BBoM je „the de-facto standard software architecture" a autoři zkoumají „the undeniable effectiveness of this approach". Text je psán v pattern formátu včetně „Therefore" – tedy jako doporučení, ne jako výtka: „Therefore, focus first on features and functionality, then focus on architecture and performance." Ironie je součástí metody, ne popřením obsahu: související vzor PIECEMEAL GROWTH končí „Refactor unrelentingly."

Evans tuto polohu přebírá bez ironie. V *DDD Reference* (2015) [4] má Big Ball of Mud vlastní vzor v části Context Mapping: *„The big ball of mud is actually quite practical for some situations (as described in the original article by Foote and Yoder), but it almost completely prevents the subtlety and precision needed for useful models. Therefore: Draw a boundary around the entire mess and designate it a big ball of mud. Do not try to apply sophisticated modeling within this context."*

**Bounded Context.** Fowler (15. ledna 2014) [5] uvádí kritérium hranice: *„Various factors draw boundaries between contexts. Usually the dominant one is human culture, since models act as Ubiquitous Language, you need a different model when the language changes."* Zároveň připouští i hranice technické: „You also find multiple contexts within the same domain context, such as the separation between in-memory and relational database models in a single application. This boundary is set by the different way we represent models."

**Kanonický podnikový model.** Fowler, *Multiple Canonical Models* (21. července 2003) [6], kritizuje snahu o jediný celopodnikový model: „Any large enterprise needs a model that is either very large, or abstract, or both. And largeness and abstractness both imply comprehension difficulties." Závěr: „You can have several canonical models rather than just one." Doplňkově EIP popisuje Canonical Data Model jako legitimní *integrační* vzor [7] – spor tedy není o existenci kanonického modelu, ale o jeho rozsah.

**Value Object.** Evans, *DDD Reference* [4]: *„When you care only about the attributes and logic of an element of the model, classify it as a value object. … Treat the value object as immutable. … Don't give a value object any identity and avoid the design complexities necessary to maintain entities."* To je přímý zdroj pro anti-vzor „VO s identitou", který kapitola neuvádí.

**Repository.** Evans [4] definuje repozitář jako „Query access to aggregates expressed in the ubiquitous language" a varuje: *„Unconstrained queries may pull specific fields from objects, breaching encapsulation, or instantiate a few specific objects from the interior of an aggregate, blindsiding the aggregate root… Domain logic moves into queries and application layer code, and the entities and value objects become mere data containers."* Preskripce zní „encapsulating the actual storage and query technology" – tím je pokryt anti-vzor repozitáře vracejícího `QueryBuilder` nebo `Criteria`.

**Doménové události.** Evans [4]: *„Domain events are ordinarily immutable, as they are a record of something in the past."* Slovo „ordinarily" je slabší než formulace kapitoly. Evans zároveň rozlišuje doménové události od „system events that reflect activity within the software itself" – to je přesná formulace anti-vzoru „event jako aplikační hook". Udi Dahan, *Domain Events – Salvation* (14. června 2009) [8], k tomu přidává heuristiku: „A domain event is just a simple POCO that represents an interesting occurence in the domain… If you feel the need to split your domain events up, there's a good chance that you should be looking at splitting your domain model too." Verraes v *Messaging Flavours* (9. ledna 2015) [24] třídí zprávy na imperativní (Command), interogativní (Query) a informační (Event); záměna kategorií je jádrem anti-vzoru: „smaž cache, až se objednávka změní" je imperativ převlečený za událost.

**Velikost agregátu.** Kanonický zdroj není Evans, ale Vernonova třídílná série *Effective Aggregate Design* (2011) [26], ze které vychází i kapitola 07 knihy. Klíčová formulace pro anti-vzor „agregát jako entity graph": *„aggregates are chiefly about consistency boundaries and not driven by a desire to design object graphs."* Vernon zároveň pojmenovává obě selhání, ne jen jedno: *„We could fall into the trap of designing for compositional convenience and make them too large. At the other end of the spectrum we could strip all aggregates bare, and as a result fail to protect true invariants."* K velikosti dodává mez, kterou kapitola 21 nemá: „limit the aggregate to just the root entity and a minimal number of attributes and/or value-typed properties… The correct minimum is the ones necessary, and no more." A příčinu vidí v pohodlí kompozice: velký agregát vznikl „because the false invariants and a desire for compositional convenience drove the design".

## 3. Stav praxe a posuny

**Spor o anemický model se za dvacet let nevyřešil, jen zpřesnil.** Nejcitovanější explicitní protipozice je esej *The Anaemic Domain Model is no Anti-Pattern, it's a SOLID design* (2014, blog kurzu SAPM na University of Edinburgh, autor podepsán jen matriklou s1257756) [9]. Argument: anti-vzor jako pojem „might discourage critical thought about the applicability of the pattern", ADM lépe naplňuje SRP a OCP, a refaktoring RDM směrem k SOLID končí u struktury podobné ADM. Zdroj je studentský a v hierarchii šablony nízko; jeho hodnota je v tom, že spor pojmenovává, ne v autoritě.

**Funkcionální linie je vážnější protiargument.** Mark Seemann v *Encapsulation in Functional Programming* (24. října 2022) [10] ukazuje, že zapouzdření není totéž co „metody na objektu": v FP je stejná vlastnost dosažena přes „make illegal states unrepresentable", smart constructors a totální funkce. Seemann sám je ale silně pro zapouzdření – v *Domain Model first* (23. října 2023) [11] píše „I consider encapsulation to be more important than 'easy' persistence". Přesná formulace sporu tedy zní: **odděl data od chování, ale ne validaci od dat.** Záznam s veřejnými poli a bez smart constructoru je anemický v tom škodlivém smyslu; algebraický typ, který nelze zkonstruovat do neplatného stavu, plus modul funkcí nad ním, není. Kapitola tento rozdíl nikde nedělá.

**Katalogizace vzorů podle složitosti domény.** Khononov v *Learning Domain-Driven Design* (2021) staví Transaction Script a Active Record vedle Domain Modelu jako rovnocenné vzory pro obchodní logiku, volba se řídí typem subdomény. Kniha to už reflektuje v 22.09 (tabulka „Supporting Subdomain → lehké DDD nebo Active Record"), ale ch21 to nepřebírá.

**Cargo cult jako dominantní projev.** Praxe posledních let neselhává na tom, že by týmy nevěděly, co je agregát, ale na tom, že DDD ztotožní s adresářovou strukturou. Verraes to formuluje obecně v *Patterns Are Not Defined by Their Implementation* (2. července 2019) [12]: „a design pattern is 'a problem in a context with a reusable solution', not merely 'a reusable solution'." Složky `Domain/Application/Infrastructure` jsou implementace bez problému. Noback v *Lasagna code – too many layers?* (26. února 2018) [13] útočí z druhé strany: problém není počet vrstev, ale nepochopená indirekce – „Most attempts at layering fail horribly… Because mostly, we have no idea what we're doing."

**Hranice kontextů podle jazyka.** Verraes v *Buzzword-free Bounded Contexts* (13. února 2014) [14] dává praktický test: „A product in the sales department is a thing with a weight and dimensions… For the inventory, it's a box that takes space on a shelf… For the shipping department, it's a package you need to deliver." Rozpoznávací znak anti-vzoru je tedy jazykový, ne technický: pokud dva „kontexty" používají identický slovník s identickým významem, nejsou to kontexty, ale vrstvy.

**Sdílená databáze se zjemnila.** Absolutní zákaz z éry microservices se v praxi modulárních monolitů posunul k jemnějšímu pravidlu: jedna fyzická databáze je přijatelná, sdílené *schéma* a cross-context JOIN nikoli. Kapitola tuto nuanci neuvádí a čte se, jako by jedna databáze byla sama o sobě chyba. Praktickým dopadem je, že „správné" řešení kapitoly (synchronní HTTP adapter mezi dvěma kontexty téže aplikace) je pro modulární monolit horší volba než volání přes rozhraní v procesu – síťové volání přidá latenci a režii bez toho, aby cokoli izolovalo navíc.

**Dekuplace pro dekuplaci.** Noback v *DDD entities and ORM entities* (21. dubna 2022) [27] popisuje protilehlé selhání téhož: „while trying to move in the opposite direction, they end up with a lot of code that is written for the sake of decoupling." Ve stejném textu pojmenovává i podobu cargo cultu, o níž kapitola mlčí: „All too often DDD is completely misinterpreted to be 'an elitist, dogmatic approach to programming, where we use DTOs, layers, and CQRS'." Jeho závěr – „full decoupling is usually not the best choice. Rather, 80 % decoupling is fine" – je přesně ten typ hranice, který kapitola u žádného ze svých pravidel nedává.

**Žánr katalogu anti-vzorů zestárl rychleji než vzory samotné.** Seznamy „co nedělat" z let 2010–2015 vznikaly v době, kdy bylo hlavním problémem, že týmy DDD neznaly. Dnešní problém je opačný: týmy DDD znají z blogů, zavedou jeho vnější znaky a přeskočí modelování. Proto se těžiště posunulo od zákazů k heuristikám – Verraes o nich píše soustavně a formuluje je jako napětí, ne jako pravidla. Praktický důsledek pro kapitolu: čtenář v roce 2026 nepotřebuje slyšet „nedělejte anemický model", ale „takhle poznáte, že váš model je anemický, a tady je situace, kdy to nevadí".

**Anti-vzory ORM zůstávají hlavním zdrojem chyb v PHP.** Agregát navržený podle asociací (`OneToMany` jako důvod hranice), repozitář vracející `QueryBuilder`, načtení kolekce jen kvůli přidání jedné položky – to jsou konkrétní podoby God aggregate v Doctrine, které Vernonův obecný popis [26] jen naznačuje. Kniha to má v 07.12 v jedné odrážce („Velký agregát kvůli pohodlí ORM"); ch21 ORM rovinu nemá vůbec, ačkoli právě ona je pro čtenáře nejdosažitelnější.

## 4. Symfony / PHP specifika

**PHP 8.4 mění, co je „getter/setter smell".** Asymetrická viditelnost (RFC *Asymmetric Visibility v2*, Ilija Tovilo a Larry Garfield, 2024-05-09, stav Implemented, hlasování 24:7) [15] zavádí `public private(set) string $bar` – vlastnost čitelnou zvenčí, zapisovatelnou jen zevnitř. Property hooks [16] „renders most boilerplate get/set methods unnecessary, even without using hooks". Důsledek pro kapitolu: přítomnost `getX()` už není spolehlivý příznak anémie, protože v PHP 8.4 se stejná struktura píše bez nich; příznakem zůstává **veřejný zápis stavu bez doménového jména**. Zároveň platí omezení, které se v knize hodí zmínit: „Property hooks are incompatible with readonly properties" [16] – kanonické `readonly` VO knihy tedy hooky použít nemohou.

**Doctrine DBAL 4 odstranil `Connection::query()`.** UPGRADE.md DBAL uvádí `Connection::query()` v seznamu „Remove legacy execute and fetch methods" [17]. Ukázka na ř. 659–664 kapitoly volá `$this->db->query('SELECT …', ['id' => …])` – metoda v DBAL 4 neexistuje a ani v DBAL 3 nepřijímala parametry. Skript `scripts/lint-php-snippets.php` to nezachytí, protože kontroluje jen syntaxi.

**Doctrine ORM 3 a repozitáře.** UPGRADE.md ORM [18]: „it was possible to configure a custom repository class that implements `ObjectRepository` but does not extend the `EntityRepository` base class. Repository classes have to extend `EntityRepository` now." Omezení se týká tříd registrovaných přes `repositoryClass` a získávaných přes `getRepository()`; „správná" ukázka kapitoly (ř. 933, samostatná služba s injektovaným `EntityManagerInterface`, implementující doménové rozhraní) jím dotčena není a je nadále doporučeným postupem. Naopak „špatná" ukázka (ř. 863, `extends EntityRepository`) je v Symfony kontextu neúplná – MakerBundle generuje `ServiceEntityRepository` [19], takže reálný anti-vzor, se kterým se čtenář potká, vypadá jinak.

**Symfony sám žádnou DDD strukturu nepředepisuje.** Best Practices [20] uvádí plochou výchozí strukturu `src/Command/`, `src/Controller/`, `src/Entity/`, `src/Repository/`, `src/Form/`… a doporučení „Don't Create any Bundle to Organize your Application Logic". Skutečnost, že `Domain/Application/Infrastructure` není v žádné oficiální dokumentaci, je dobrý argument pro sekci o cargo cultu: složky jsou volba týmu, ne norma frameworku.

**Messenger a hranice mezi doménovou a integrační událostí.** Ukázka na ř. 979–981 posílá doménové události přímo do `MessageBusInterface`. To je v knize konzistentní (viz ch11 Outbox), ale zároveň to je přesně to místo, kde v praxi vzniká anti-vzor „event jako aplikační hook": jakmile doménová událost skončí na sběrnici, kdokoli si na ni pověsí handler, který s doménou nesouvisí. Kapitola by měla tuto hranici pojmenovat a odkázat na `/outbox-pattern`, kde je oddělení doménové a integrační události rozebráno.

**Anti-vzory vrstvení jdou vynutit nástrojem, ne code review.** Sekce 21.07 popisuje doménovou logiku v infrastruktuře jako věc disciplíny, ale v PHP ekosystému existují nástroje, které porušení hranic zachytí v CI: `deptrac/deptrac` (aktuálně 4.7.1, přes 10,9 mil. stažení) a `phparkitect/phparkitect` (1.3.0, přes 4,7 mil. stažení) [29][30]. Pozor na atribuci balíčku: dřívější `qossmic/deptrac` je označen jako abandoned ve prospěch `deptrac/deptrac` [31]. Kniha PHPArkitect zmiňuje jednou, v jiné souvislosti (`authorization_in_ddd.md:804`); ch21 by z toho měla udělat konkrétní doporučení – pravidlo „`App\*\Domain` nesmí odkazovat na `Doctrine\*` ani `Symfony\*`" je jeden řádek konfigurace a nahradí opakovanou diskusi v code review.

**Drobnost, která je v pořádku.** `$request->query->getString('token')` (ř. 994) je platné API: `ParameterBag::getString()` přibylo v Symfony 6.3 [28]. Při revizi tedy není co opravovat.

**Verze.** Aktuální stav k datu studie: Symfony 8.1.6 (2026-08-30), Doctrine ORM 3.6.8 (2026-08-05), PHP 8.5.10 (2026-08-27) [21][22][23]. Cíl knihy (PHP 8.4, Symfony 8, ORM 3) je platný.

## 5. Sporné a chybně podávané body

**5.1 Je anemický model anti-vzor?** *Pro:* Fowler [1], Evans (citovaný Fowlerem: „the more common mistake is to give up too easily on fitting the behavior into an appropriate object"), většina DDD literatury. *Proti:* Fowler sám připouští „Domain Models aren't always the best tool" a odkazuje na Transaction Script [2]; funkcionální škola odděluje data a chování záměrně [10]; SOLID argument [9]. **Doporučení pro knihu:** držet tvrzení „anemický model je anti-vzor *v kontextu, kde jste se rozhodli pro doménový model*" a doplnit sekci, která spor pojmenuje. Kritérium není tvar kódu, ale to, zda někdo platí cenu doménového modelu (mapování, obalování, více souborů), aniž by za ni něco dostával.

**5.2 Je logika ve službě chyba?** Kapitola to na ř. 55, 60–63 a 101 tvrdí plošně („Doménová logika v servisní třídě"). Kniha si tím protiřečí s 06.07, kde jsou doménové služby legitimní stavební blok, a s Fowlerem, který Service Layer výslovně hájí. **Doporučení:** rozlišit tři věci – aplikační služba (orchestrace, legitimní), doménová služba (logika přes více agregátů, legitimní), „God service" nad bezobsažnými entitami (anti-vzor).

**5.3 Testovatelnost.** Tvrzení na ř. 56 („testování logiky v servisní vrstvě vyžaduje mockování závislostí") je nepodložené a protistrana tvrdí opak [9]: služba s injektovanými závislostmi se testuje snadněji než entita, která k výpočtu potřebuje kolaboranty. Argument je platný jen tehdy, když entita žádné závislosti nemá. **Doporučení:** formulaci zúžit nebo bod vypustit.

**5.4 „Striktně immutable" události.** Evans píše „ordinarily immutable" [4] a připouští, že událost může mít identitu odvozenou z vlastností. Praxe navíc rozlišuje `occurredAt` (kdy se to v doméně stalo) od `recordedAt` (kdy to systém zapsal) – Evans obojí zmiňuje. Kapitola má jen jedno razítko generované v konstruktoru, což pro doménovou událost s historickým datem nestačí. **Doporučení:** ponechat immutabilitu jako pravidlo, ale změkčit „striktně" a doplnit dvojí razítko.

**5.5 Big Ball of Mud.** Kniha jej řeší v 03.12 a tam píše „ne proto, že by ji někdo volil". Foote & Yoder [3] i Evans [4] tvrdí opak: BBoM je pro některé situace praktický a Evans z něj dělá *volbu* v context mapě („draw a boundary around the entire mess and designate it a big ball of mud"). **Doporučení:** ch21 BBoM nepřebírat (patří do 03.12), ale při revizi ch03 tuto větu opravit; ch21 může na 03.12 odkázat v úvodu jako na „anti-vzor, který se dá vědomě ohraničit".

**5.6 Kanonický datový model.** Není to jednoznačný anti-vzor. Jako *integrační* formát je to řádný EIP vzor [7]; anti-vzorem je až nárok, aby jeden model platil uvnitř všech kontextů [6]. **Doporučení:** pokud kapitola tento anti-vzor přidá, musí rozlišit „kanonický model na hranici" od „jednoho modelu pro celou firmu".

**5.7 Sdílená databáze.** Kapitola nepojmenovává legitimní výjimky: Shared Kernel s explicitním vlastnictvím, read-only replika pro reporting, jedna fyzická instance s oddělenými schématy. Bez nich čtenář v modulárním monolitu odejde s nepoužitelným pravidlem. Navíc jediná nabízená alternativa (synchronní HTTP volání mezi kontexty) je pro monolit horší než volání přes rozhraní v procesu; asynchronní varianta je zmíněna až v závěrečném odstavci na ř. 744.

**5.8 Má Primitive Obsession mez?** Kapitola 21.03 ji nemá. Praxe ji potřebuje: hodnotový objekt pro každý řetězec v systému je vlastní forma over-engineeringu a přesně ten druh ceremonie, který kritizuje 21.08. Použitelné kritérium plyne z Evanse [4] („when you care only about the attributes and logic of an element of the model") a z Vernonova rozlišení prostého atributu od value-typed property [26]: VO se vyplatí tam, kde má hodnota vlastní pravidla, vlastní operace nebo hrozí záměna s jinou hodnotou téhož primitivního typu. Pole `note`, `description` nebo `internalComment` žádné z toho nesplňuje. **Doporučení:** doplnit k 21.03 jeden odstavec „kde VO nezavádět", jinak si kapitola protiřečí sama se sebou přes dvě sekce.

**5.9 Anti-vzor podle vrstvy vs. anti-vzor podle jazyka.** U Bounded Contextu podle technické vrstvy je třeba být opatrný: Fowler [5] výslovně připouští, že hranice mohou vzniknout i z rozdílné reprezentace („separation between in-memory and relational database models"). Anti-vzorem tedy není každá technicky motivovaná hranice, ale ta, která nemá jazykový protějšek – „Frontend context" a „Backend context" mluví identickým slovníkem o identických věcech. **Doporučení:** rozpoznávací znak formulovat jazykově (Verraesův test se třemi významy slova „product" [14]), ne strukturálně.

**5.10 Kapitola si protiřečí ve vlastní ukázce.** Sekce 21.04 předvádí „správně rozdělené agregáty" a v nich `Customer` (ř. 526–534) a `Wishlist` (ř. 594–600) – obě třídy mají jen privátní vlastnosti a **žádnou metodu**. Podle definice ze sekce 21.02, vzdálené 70 řádků, jsou to anemické třídy. Autorský záměr je zřejmý (ukázat hranice, ne chování), ale čtenář vidí dva vzájemně se rušící vzory na jedné stránce. **Doporučení:** buď oběma třídám doplnit jednu doménovou metodu, nebo je zredukovat na komentář („`Customer` a `Wishlist` jsou samostatné agregáty; jejich chování zde není podstatné").

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | `anti_patterns.md:43–258` | Sekce o anemickém modelu nezná žádnou protistranu; Fowlerovo vlastní „Domain Models aren't always the best tool" chybí | Nová podsekce „Kdy anemický model není chyba" |
| G2 | nepodložené | `anti_patterns.md:56` | „Obtížná testovatelnost" jako fakt; protistrana [9] argumentuje opačně | Zúžit na „entita bez závislostí je testovatelná bez mocků" nebo bod vypustit |
| G3 | sporné | `anti_patterns.md:55, 60–63, 101` | Plošné „doménová logika v servisní třídě = chyba" koliduje s 06.07 a s Fowlerem | Rozlišit aplikační / doménovou / God službu |
| G4 | chybí | `anti_patterns.md:45` | Chybí Fowlerův hlavní (nákladový) argument: náklady doménového modelu bez jeho přínosů | Doplnit dvě věty a citaci |
| G5 | chybí | celá kapitola | Transaction Script není zmíněn ani jako legitimní alternativa | Odstavec + odkaz na `/co-je-ddd#…` a `/kdy-nepouzivat-ddd` |
| G6 | chybí | všechny sekce | U žádného anti-vzoru není explicitní rozpoznávací znak („podle čeho poznám, že se mě to týká") | Do každé sekce jednořádkový test |
| G7 | sporné | `anti_patterns.md:196–198, 225, 239, 248` | `UserRegisteredEvent`, `UserActivatedEvent`, `UserDeactivatedEvent` porušují konvenci „bez sufixu Event"; jediná další kapitola s tímto problémem je `testing_ddd.md:439` | Přejmenovat na `UserRegistered`, `UserActivated`, `UserDeactivated` |
| G8 | sporné | `anti_patterns.md:377` vs. `context_mapping.md:141` | `final class Money` zde vs. `final readonly class Money` v ch03 | Sjednotit na `final readonly class` |
| G9 | sporné | `anti_patterns.md:391` | `Money::zero()` je definován jen zde, ale používá ho i `basic_concepts.md:447`; navazuje na nález G3/G4 studie k `basic_concepts` | Kanonickou definici umístit do ch06, zde jen použít |
| G10 | sporné | `anti_patterns.md:537–580` | `Order` má veřejný konstruktor a `place()` jako stavový přechod; kanonicky je `Order::place()` factory (`aggregate_design.md:383`, `basic_concepts.md:574`, `outbox_pattern.md:408`) | Sjednotit: `Order::place()` jako factory, přechod pojmenovat `confirm()` nebo `submit()` |
| G11 | sporné | `anti_patterns.md:232, 235, 245, 399, 563, 571` | Šest výskytů holé `\DomainException`; CLAUDE.md ji připouští jen jako přiznanou zkratku | Nahradit pojmenovanými výjimkami alespoň v ukázkách „správně" |
| G12 | zastaralé | `anti_patterns.md:659–664` | `$this->db->query('SELECT …', ['id' => …])` – `Connection::query()` odstraněno v DBAL 4 [17] a nikdy nepřijímalo parametry | Přepsat na `executeQuery()`; ukázka má být špatná modelováním, ne API |
| G13 | mělké | `anti_patterns.md:863` | „Špatný" repozitář dědí `EntityRepository`; reálný Symfony anti-vzor dědí `ServiceEntityRepository` [19] | Změnit ukázku na `ServiceEntityRepository`, doplnit poznámku k ORM 3 [18] |
| G14 | mělké | `anti_patterns.md:588` | `Money::zero(Currency::CZK)` natvrdo uvnitř agregátu v ukázce „správně" | Odvodit měnu z první položky nebo z konstruktoru objednávky |
| G15 | mělké | `anti_patterns.md:384–388` | `Money` zakazuje záporné částky bez vysvětlení; ch03 to řeší poznámkou o `SignedMoney` | Převzít poznámku z ch03 |
| G16 | chybí | – | Anti-vzor „DDD = složky `Domain/Application/Infrastructure`"; dnes jen v 22.09 jako varování | Nová sekce s rozpoznávacím znakem [12][13][20] |
| G17 | chybí | – | Agregát jako entity graph / ORM asociace jako vodítko hranice (07.12 to má jednou větou) | Sekce nebo rozšíření 21.04 o „`OneToMany` není hranice" |
| G18 | chybí | – | Repozitář vracející `QueryBuilder`, `Criteria` nebo pole polí; Evans to popisuje přesně [4], Noback k tomu má i testovací postup [25] | Nová sekce ~50 řádků s ukázkou |
| G19 | chybí | – | Doménové události zneužité jako aplikační hooky (invalidace cache, notifikace vrstev) | Nová sekce; opřít o Evansovo rozlišení domain / system event [4] a Dahana [8] |
| G20 | chybí | – | Value Object s identitou nebo se settery; Evans má přímou formulaci [4] | Krátká sekce; navazuje na 21.03 |
| G21 | chybí | – | Bounded Context vytyčený podle technické vrstvy místo podle jazyka [5][14] | Nová sekce; pozor na Fowlerovu výhradu o hranicích podle reprezentace |
| G22 | chybí | – | „Jeden model pro celou firmu" / kanonický datový model [6][7] | Nová sekce s rozlišením „kanonický na hranici" vs. „jeden model uvnitř" |
| G23 | nadbytečné | `anti_patterns.md:1009–1133` | 21.09 duplikuje 20.03 C4 (`ddd_pain_points.md:806–835`) včetně glosáře | Zkrátit na třetinu, glosář přenechat ch20, ponechat jen pojišťovací ukázku |
| G24 | mělké | `anti_patterns.md:1003–1007` | 21.08 je pětiřádkový odkaz bez vlastního obsahu | Buď doplnit rozpoznávací znak a příklad, nebo přesunout do úvodu jako křížový odkaz |
| G25 | mělké | `anti_patterns.md:748, 822–834` | „Striktně immutable" je silnější než Evansovo „ordinarily" [4]; chybí `occurredAt` vs. `recordedAt` a otázka verzování schématu události | Změkčit formulaci, doplnit dvojí razítko |
| G26 | chybí | `anti_patterns.md:85–98` | Chybí kontext PHP 8.4: `private(set)` a property hooks mění výpovědní hodnotu getterů/setterů [15][16] | Callout u 21.02 |
| G27 | mělké | `anti_patterns.md:606–744` | Sdílená DB podána absolutně, bez legitimních výjimek (shared kernel, read replika, jedno DB s oddělenými schématy) | Doplnit odstavec o hranici pravidla |
| G28 | chybí | `anti_patterns.md:22–31` | Úvod nezmiňuje Big Ball of Mud ani odkaz na 03.12, ačkoli je to nejznámější DDD anti-vzor | Jedna věta s odkazem na `/context-mapping#big-ball-of-mud` |
| G29 | nepodložené | `anti_patterns.md:36–40` | Klasifikace strategické / taktické / implementační chyby je bez zdroje a nikde se dál nepoužívá | Buď o ni opřít strukturu kapitoly, nebo ji vypustit |
| G30 | chybí | konec kapitoly | Chybí souhrnná tabulka „anti-vzor → znak → alternativa" a checklist; ch07 takový checklist má (07.13) | Nová závěrečná sekce ~30 řádků |
| G31 | nadbytečné | `anti_patterns.md:1119–1131` | Callout o živém glosáři je celý obsahově v 20.03 C4 bod 1 | Nahradit odkazem |
| G32 | chybí | `anti_patterns.md:260–432` | Primitive Obsession nemá mez: kapitola nikde neříká, kdy VO nezavádět. Protiřečí si s 21.08 (over-engineering) | Odstavec „kde VO nezavádět" podle kritéria z [4] a [26] |
| G33 | mělké | `anti_patterns.md:436, 604` | Pravidlo „agregát co nejmenší" je bez atribuce; kanonický zdroj je Vernon 2011 [26], kde je i formulace „not driven by a desire to design object graphs" | Doplnit citaci a Vernonovu druhou polovinu pravidla (příliš malý agregát nechrání invarianty) |
| G34 | chybí | `anti_patterns.md:979–981` | Doménové události jdou přímo na `MessageBusInterface` bez zmínky o hranici doménová / integrační událost | Věta + odkaz na `/outbox-pattern` |
| G35 | mělké | `anti_patterns.md:744` | Asynchronní alternativa ke sdílené DB je jedna věta na konci sekce; pro modulární monolit chybí varianta „volání přes rozhraní v procesu" | Rozšířit odstavec o tři varianty s jejich cenou |
| G36 | nadbytečné | `anti_patterns.md:1133` | Věta o Vernonovi jako doporučeném zdroji je bez konkrétní kapitoly a slouží hlavně jako odkaz na `/zdroje` | Nahradit konkrétním odkazem, nebo přesunout do závěrečného shrnutí |
| G38 | chybí | `anti_patterns.md:839–1001` | Sekce o logice v infrastruktuře nenabízí nástroj na vynucení hranic (deptrac, PHPArkitect), ačkoli kniha PHPArkitect jinde zmiňuje | Doplnit odstavec s ukázkou pravidla pro CI |
| G37 | sporné | `anti_patterns.md:526–534, 594–600` | Třídy `Customer` a `Wishlist` v ukázce „správně“ nemají jedinou metodu – podle definice ze sekce 21.02 jsou anemické | Doplnit jednu doménovou metodu, nebo je nahradit komentářem |

## 7. Doporučení k přepisu

**P1-1 — Doplnit sekci, která pojmenuje spor o anemický model.** Kapitola dnes tvrdí něco, co polovina seriózních zdrojů zpochybňuje, a nedá čtenáři nástroj na rozhodnutí. Sekce má obsahovat: Fowlerův nákladový argument [1], jeho vlastní výhradu „Domain Models aren't always the best tool", odkaz na Transaction Script [2] a funkcionální protipozici (odděl chování, ale ne validaci od dat) [10]. Závěr má být kritérium, ne verdikt: anemický model je chyba tehdy, když platíte cenu doménového modelu bez jeho přínosu. *Nová podsekce ~50 řádků v 21.02.*

**P1-2 — Sjednotit kód kapitoly s kanonickým API knihy.** Kapitola je jediné místo (vedle jedné zmínky v `testing_ddd.md`), kde události nesou sufix `Event`, jediné druhé místo s definicí `Money` (a v jiném tvaru než ch03), a jediné místo, kde `Order` vzniká veřejným konstruktorem, zatímco `place()` znamená stavový přechod. Čtenář, který čte knihu po pořádku, narazí na kolizi jmen. *Oprava ~25 míst: G7, G8, G9, G10, G11.*

**P1-3 — Opravit fakticky chybné ukázky infrastruktury.** `Connection::query()` s parametry v DBAL 4 neexistuje (G12) a „špatný" repozitář nereprezentuje to, co Symfony reálně generuje (G13). Anti-vzorová ukázka musí být špatná právě tím, co kritizuje, jinak čtenář diskutuje o API místo o modelu. *Přepis dvou bloků, ~20 řádků.*

**P1-4 — Doplnit chybějící anti-vzory, které nemá jiná kapitola.** Katalog dnes duplikuje ch07, ch20 a ch22 a vynechává to, co je jeho vlastní. Prioritně: repozitář jako dotazovací API (G18), doménová událost jako aplikační hook (G19), Value Object s identitou (G20), Bounded Context podle technické vrstvy (G21), jeden model pro celou firmu (G22), DDD jako adresářová struktura (G16). Všech šest má primární zdroj. *Šest sekcí, ~40–60 řádků každá; kapitola vyroste zhruba o třetinu.*

**P1-5 — Ke každému anti-vzoru doplnit rozpoznávací znak a hranici pravidla.** Kapitola dnes většinou zakazuje a ukazuje alternativu, ale neříká, jak poznat výskyt a kde zákaz končí. Nejviditelnější u sdílené databáze (G27) a u anemického modelu (G1). Formát: jedna věta „Poznáte to podle…" a jedna věta „Neplatí to, když…". *Zásah do všech osmi sekcí, ~2 řádky na sekci.*

**P2-1 — Zkrátit 21.09 a odstranit duplicitu s ch20.** Ubiquitous Language drift má v 20.03 C4 lepší zpracování včetně čtyř nápravných praktik a glosáře. Ch21 si má ponechat pojišťovací ukázku jako ilustraci nekonzistentního pojmenování a zbytek delegovat. *Zkrácení ze 125 na ~45 řádků (G23, G31).*

**P2-2 — Doplnit callout o PHP 8.4 k anemickému modelu.** Přítomnost getterů a setterů byla spolehlivým příznakem v roce 2003; v PHP 8.4 se stejná struktura píše přes `public private(set)` nebo property hooks, a příznakem zůstává veřejný zápis stavu bez doménového jména. Zároveň zmínit, že hooky nejsou slučitelné s `readonly` [16], což se týká všech VO v knize. *Callout ~15 řádků (G26).*

**P2-3 — Zpřesnit sekci o immutabilitě událostí.** Změkčit „striktně" na Evansovo znění, doplnit dvojici `occurredAt` / `recordedAt` a jednu větu o tom, že immutabilita instance neřeší verzování schématu události (to má ch10). *Přepis odstavce a rozšíření ukázky, ~15 řádků (G25).*

**P2-4 — Doplnit závěrečnou souhrnnou tabulku a checklist.** Kapitola typu katalog má končit přehledem, ne odkazem na Vernona. Tabulka „anti-vzor → rozpoznávací znak → realistická alternativa → kde je o tom v knize víc" zároveň zviditelní křížové odkazy na ch03, ch07, ch20 a ch22. *Nová sekce ~30 řádků (G30).*

**P2-5 — Doplnit mez u Primitive Obsession.** Bez ní si kapitola protiřečí přes dvě sekce: 21.03 tlačí na hodnotové objekty, 21.08 varuje před ceremonií. Kritérium (vlastní pravidla / vlastní operace / riziko záměny) je krátké a odstraní nejčastější nedorozumění po přečtení sekce. *Odstavec ~10 řádků (G32).*

**P2-6 — Doplnit atribuci u pravidla o velikosti agregátu.** Věta na ř. 604 je parafráze Vernona bez uvedení zdroje; ch07 přitom Vernonova pravidla cituje. Připojit i druhou polovinu pravidla: příliš malý agregát selhává stejně jako příliš velký, jen tiše. *Dvě věty a citace (G33).*

**P2-7 — Odstranit rozpor mezi 21.02 a ukázkou v 21.04.** Třídy `Customer` a `Wishlist` v ukázce „správně“ nemají žádnou metodu, tedy přesně to, co kapitola o 70 řádků výš označuje za anti-vzor. Buď oběma doplnit jednu doménovou metodu, nebo je nahradit komentářem o hranici agregátu. Zásah je malý, ale odstraní nejviditelnější vnitřní nekonzistenci kapitoly. *Úprava dvou bloků, ~10 řádků (G37).*

**P2-8 — Doplnit k 21.07 vynucení hranic nástrojem.** Sekce dnes končí u „takhle to má vypadat" a spoléhá na disciplínu. Jeden odstavec s pravidlem pro deptrac nebo PHPArkitect [29][30] promění doporučení v kontrolu, která běží v CI. Zároveň to je jediné místo kapitoly, kde se dá anti-vzor detekovat automaticky – u ostatních jde vždy o úsudek. *Odstavec ~12 řádků (G38).*

**P3-1 — Vyjasnit nebo vypustit klasifikaci z 21.01.** Trojdílné rozdělení nemá zdroj a struktura kapitoly se jím neřídí. Buď podle něj sekce uspořádat (strategické → taktické → implementační), nebo klasifikaci nahradit prostým výčtem. *Oprava callloutu, ~6 řádků (G29).*

**P3-2 — Odkaz na Big Ball of Mud v úvodu.** Jedna věta, která čtenáře pošle do 03.12 a zároveň řekne, že BBoM je anti-vzor, který lze vědomě ohraničit. *Jedna věta (G28).*

**P3-3 — Drobné opravy ukázek.** Měna v `totalAmount()` (G14), poznámka o záporných částkách (G15), `final` u tříd `User` a `Customer` v ukázkách „správně", konkretizace odkazu na Vernona (G36). *~8 řádků.*

**P3-4 — Doplnit hranici mezi doménovou a integrační událostí u 21.07.** Handler dnes posílá doménové události rovnou na sběrnici; jedna věta s odkazem na `/outbox-pattern` čtenáři vysvětlí, proč to není totéž jako publikovat je ven ze systému. *Jedna až dvě věty (G34).*

**P3-5 — Rozšířit alternativy ke sdílené databázi.** Dnes je nabídnut jediný postup (synchronní HTTP adapter) a asynchronní varianta je zmíněna jednou větou na konci. Doplnit třetí možnost – volání přes rozhraní v procesu pro modulární monolit – a u každé uvést cenu (latence, eventuální konzistence, sdílený deployment). *Rozšíření odstavce ~12 řádků (G35).*

## 8. Otevřené otázky pro autora

1. **Má ch21 zůstat katalogem, nebo se stát rozhodovací kapitolou?** Dnes je to katalog, ale pět z osmi položek má lepší domov jinde. Varianta A: ch21 zeštíhlí na anti-vzory, které jinde nejsou, a stane se rozcestníkem. Varianta B: ch21 zůstane úplným katalogem a ostatní kapitoly na něj odkazují (dnešní stav je nekonzistentní mix obojího).
2. **Kolik prostoru dát sporu o anemický model?** Poctivé pojmenování sporu stojí ~50 řádků a částečně podkopává nejdelší sekci kapitoly. Alternativa je odstavec s odkazem na ch22, ale to spor spíš zamete.
3. **Kam patří definice `Money`?** Studie k `basic_concepts` navrhuje ch06. Pokud se to tak rozhodne, ch21 má `Money` jen používat – čímž sekce 21.03 přijde o svůj nejnázornější blok kódu.
4. **Přejmenovat `place()` v ch21, nebo `Order::place()` jinde?** Kolize jmen je reálná, ale kanonické `Order::place()` je použito v šesti kapitolách, takže ustoupit má ch21.
5. **Má kniha zavést vlastní anti-vzor pro pseudo-DDD?** Termín „pseudo-DDD" už používá 22.09. Pokud ho ch21 přebere jako plnohodnotný anti-vzor (G16), je třeba rozhodnout, která kapitola je jeho domovem.
6. **Zůstává `reading_time: 35`?** Po navrhovaném rozšíření (P1-4) kapitola naroste zhruba o 300 řádků; údaj bude potřeba přepočítat. Zvážit i `difficulty: 2` – kapitola s protiargumenty a strategickými anti-vzory bude náročnější než dnešní katalog.
7. **Kolik dvojic „špatně / správně" udržet?** Formát je čitelný, ale spotřebuje víc než polovinu kapitoly. U šesti nových anti-vzorů (P1-4) by kapitola při zachování formátu narostla o 400+ řádků. Alternativa: u nových sekcí jen jeden krátký blok „takhle to vypadá" a alternativa popsaná prózou.
8. **Má kapitola zavádět vlastní terminologii?** „God Aggregate" v této podobě není termín z primárních zdrojů – Vernon mluví o „large cluster aggregate" [26]. Rozhodnout, zda držet zavedený komunitní název, nebo se přiklonit k Vernonovu.

9. **Má studie vést k rozdělení kapitoly?** Po doplnění šesti anti-vzorů (P1-4) a rozpoznávacích znaků (P1-5) přesáhne kapitola 1500 řádků. Varianta: oddělit taktické anti-vzory (model, agregát, VO, události) od strategických (hranice kontextů, sdílená data, kanonický model) do dvou kapitol, nebo strategické přesunout do ch03.
10. **Jak naložit s protiargumenty obecně?** Kapitola je dnes normativní. Pokud se přijme P1-1, vznikne precedens pro celou knihu – u dalších kapitol bude čtenář očekávat stejnou poctivost. Rozhodnout, zda je to záměr, nebo výjimka pro tuto kapitolu.

## 9. Bibliografie

### Ověřené zdroje

`[1]` Martin Fowler — *Anemic Domain Model*, bliki, 25. 11. 2003. https://martinfowler.com/bliki/AnemicDomainModel.html — získáno `curl`, plný text extrahován, přístup 2026-09-04
`[2]` Martin Fowler — *Transaction Script*, katalog *Patterns of Enterprise Application Architecture*, 5. 3. 2003. https://martinfowler.com/eaaCatalog/transactionScript.html — získáno `curl`, přístup 2026-09-04
`[3]` Brian Foote, Joseph Yoder — *Big Ball of Mud*. PLoP '97 / EuroPLoP '97, Monticello; TR #WUCS-97-34, Washington University; kap. 29 in *Pattern Languages of Program Design 4*, Addison-Wesley, 2000. http://www.laputan.org/mud/ — získáno `curl`, plný text extrahován, přístup 2026-09-04
`[4]` Eric Evans — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, Domain Language, Inc., 2015 (CC BY 4.0). https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf — PDF staženo `curl`, převedeno `pdftotext`, čteny hesla Value Objects, Domain Events, Repositories, Big Ball of Mud; přístup 2026-09-04
`[5]` Martin Fowler — *Bounded Context*, bliki, 15. 1. 2014. https://martinfowler.com/bliki/BoundedContext.html — získáno `curl`, přístup 2026-09-04
`[6]` Martin Fowler — *Multiple Canonical Models*, bliki, 21. 7. 2003. https://martinfowler.com/bliki/MultipleCanonicalModels.html — získáno `curl`, přístup 2026-09-04
`[7]` Gregor Hohpe, Bobby Woolf — *Canonical Data Model*, Enterprise Integration Patterns. https://www.enterpriseintegrationpatterns.com/patterns/messaging/CanonicalDataModel.html — získáno `curl` (HTTP 200), přístup 2026-09-04
`[8]` Udi Dahan — *Domain Events – Salvation*, 14. 6. 2009. https://udidahan.com/2009/06/14/domain-events-salvation/ — získáno `curl`, přístup 2026-09-04
`[9]` s1257756 — *The Anaemic Domain Model is no Anti-Pattern, it's a SOLID design*, SAPM Course Blog, University of Edinburgh, 4. 2. 2014. https://blog.inf.ed.ac.uk/sapm/2014/02/04/the-anaemic-domain-model-is-no-anti-pattern-its-a-solid-design/ — získáno `curl`, přístup 2026-09-04. *Poznámka: studentský kurzový blog, autor podepsán jen matriklou. V hierarchii zdrojů nízko – použitelný jako doklad, že spor existuje, ne jako autorita.*
`[10]` Mark Seemann — *Encapsulation in Functional Programming*, ploeh blog, 24. 10. 2022. https://blog.ploeh.dk/2022/10/24/encapsulation-in-functional-programming/ — získáno `curl`, přístup 2026-09-04
`[11]` Mark Seemann — *Domain Model first*, ploeh blog, 23. 10. 2023. https://blog.ploeh.dk/2023/10/23/domain-model-first/ — získáno `curl`, přístup 2026-09-04
`[12]` Mathias Verraes — *Patterns Are Not Defined by Their Implementation*, 2. 7. 2019. https://verraes.net/2019/07/patterns-are-not-defined-by-their-implementation/ — získáno `curl`, přístup 2026-09-04
`[13]` Matthias Noback — *Lasagna code – too many layers?*, 26. 2. 2018. https://matthiasnoback.nl/2018/02/lasagna-code-too-many-layers/ — získáno `curl` (URL dohledána přes `sitemap.xml`), přístup 2026-09-04
`[14]` Mathias Verraes — *Buzzword-free Bounded Contexts*, 13. 2. 2014. https://verraes.net/2014/02/buzzword-free-bounded-contexts/ — získáno `curl`, přístup 2026-09-04
`[15]` Ilija Tovilo, Larry Garfield — PHP RFC: *Asymmetric Visibility v2*, 9. 5. 2024, stav Implemented (hlasování 24:7). https://wiki.php.net/rfc/asymmetric-visibility-v2 — získáno `curl`, přístup 2026-09-04
`[16]` PHP Manual — *Property Hooks*. https://www.php.net/manual/en/language.oop5.property-hooks.php — získáno `curl`, přístup 2026-09-04
`[17]` Doctrine DBAL — UPGRADE.md, sekce „BC BREAK: Remove legacy execute and fetch methods". https://raw.githubusercontent.com/doctrine/dbal/4.4.x/UPGRADE.md — získáno `curl` (raw GitHub), přístup 2026-09-04
`[18]` Doctrine ORM — UPGRADE.md, sekce k povinnému dědění `EntityRepository`. https://raw.githubusercontent.com/doctrine/orm/3.6.x/UPGRADE.md — získáno `curl` (raw GitHub), přístup 2026-09-04
`[19]` Symfony Docs — *Databases and the Doctrine ORM*, sekce „Querying for Objects: The Repository". https://symfony.com/doc/current/doctrine.html — získáno `curl`, přístup 2026-09-04
`[20]` Symfony Docs — *Symfony Best Practices*, sekce „Use the Default Directory Structure" a „Business Logic". https://symfony.com/doc/current/best_practices.html — získáno `curl`, přístup 2026-09-04
`[21]` symfony/symfony — GitHub Releases API, poslední tag v8.1.6 (2026-08-30). https://api.github.com/repos/symfony/symfony/releases — dotaz přes `curl` na GitHub API, 2026-09-04
`[22]` doctrine/orm — GitHub Releases API, poslední tag 3.6.8 (2026-08-05). https://api.github.com/repos/doctrine/orm/releases — dotaz přes `curl` na GitHub API, 2026-09-04
`[23]` php.net Releases API — PHP 8.5.10 (27. 8. 2026). https://www.php.net/releases/index.php?json&max=6 — dotaz přes `curl`, 2026-09-04
`[24]` Mathias Verraes — *Messaging Flavours*, 9. 1. 2015. https://verraes.net/2015/01/messaging-flavours/ — získáno `curl`, přístup 2026-09-04
`[25]` Matthias Noback — *Test-driving repository classes – Part 1: Queries*, 25. 9. 2018. https://matthiasnoback.nl/2018/09/test-driving-repository-classes-part-1-queries/ — získáno `curl`, přístup 2026-09-04
`[26]` Vaughn Vernon — *Effective Aggregate Design, Part I: Modeling a Single Aggregate*, 2011. https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_1.pdf (rozcestník: https://www.dddcommunity.org/library/vernon_2011/) — PDF staženo `curl`, převedeno `pdftotext`, čteny sekce „Rule: Model True Invariants In Consistency Boundaries" a „Rule: Design Small Aggregates"; přístup 2026-09-04
`[27]` Matthias Noback — *DDD entities and ORM entities*, 21. 4. 2022. https://matthiasnoback.nl/2022/04/ddd-entities-and-orm-entities/ — získáno `curl` (URL dohledána přes `sitemap.xml`), přístup 2026-09-04
`[28]` symfony/symfony — HttpFoundation CHANGELOG.md, větev 8.1: `ParameterBag::getString()` přidáno v 6.3. https://raw.githubusercontent.com/symfony/symfony/8.1/src/Symfony/Component/HttpFoundation/CHANGELOG.md — získáno `curl` (raw GitHub), přístup 2026-09-04

`[29]` Packagist — `deptrac/deptrac`, verze 4.7.1, 10 912 559 stažení. https://packagist.org/packages/deptrac/deptrac — dotaz na Packagist JSON API přes `curl`, 2026-09-04
`[30]` Packagist — `phparkitect/phparkitect`, verze 1.3.0, 4 756 606 stažení. https://packagist.org/packages/phparkitect/phparkitect — dotaz na Packagist JSON API přes `curl`, 2026-09-04
`[31]` Packagist — `qossmic/deptrac`, označen jako abandoned ve prospěch `deptrac/deptrac`. https://packagist.org/packages/qossmic/deptrac — dotaz na Packagist JSON API přes `curl`, 2026-09-04

### Neověřené / nedohledané

- **Vladimir Khononov, *Learning Domain-Driven Design*, O'Reilly, 2021** — tvrzení v sekci 3, že kniha staví Transaction Script a Active Record vedle Domain Modelu jako rovnocenné vzory podle typu subdomény, se nepodařilo ověřit z primárního zdroje: `oreilly.com` vrací HTTP 403 (Access Denied). Kniha je v repozitáři už citována (`when_not_to_use_ddd.md`), takže autor má patrně přístup k textu; před použitím tvrzení ověřit názvy kapitol.
- **Vernon, *IDDD* (2013) a anti-vzory (`anti_patterns.md:1133`) – OVĚŘENO 2026-09-04 z plného
  textu (vlastní výtisk). Tvrzení sedí.** Anemickému doménovému modelu se Vernon věnuje obšírně
  a ostře: *„Strangely enough, Anemic Domain Models have popped up left and right in our industry.
  The trouble is that most developers seem to think this is quite normal and would not even
  acknowledge that a serious condition exists when employed in their systems.“*

  Zajímavější než samotné odsouzení je jeho **diagnostický test**: dvojice otázek, po nichž
  následuje vyhodnocení – *„If you answered ‚Yes‘ to both questions, your ‚domain model‘ is very,
  very ill. It’s anemic.“* **Doporučení: převzít formu testu, ne citát.** Kapitola dnes anemický
  model popisuje; rozhodovací otázky by z popisu udělaly nástroj, který čtenář použije na vlastní
  kód.
- **Mark Seemann jako zastánce anemického modelu** — zadání studie tuto atribuci předpokládalo. Rešerše ji nepotvrdila: Seemann zapouzdření naopak hájí [10][11]. Jeho příspěvek do sporu je jiný a jemnější (zapouzdření lze dosáhnout typy a smart constructory místo metod na objektu). V kapitole ho nelze uvést jako obhájce anemického modelu.
- **`Money::zero()` „jinde v knize není"** — zadání studie to uvádělo jako hypotézu. Ověřeno jako nepřesné: `Money::zero()` se používá i v `basic_concepts.md:447`, definován je ale jen v `anti_patterns.md:391`. Kanonická definice `Money` chybí úplně; ch03 (`context_mapping.md:141`) má variantu bez `zero()`.
- **Verraes o rozdělování Bounded Contexts** — Fowler v [5] odkazuje na „Verraes and Wirfs-Brock". Původní odkazovaný text se nepodařilo najít na `verraes.net` (URL `/2021/06/split-bounded-contexts/` vrací 404); existuje `/2021/06/split-domain-across-bounded-contexts/`, obsah neověřen.
- **Původní tweet Roberta Waltmana o „lasagna code"** citovaný v [13] — nedohledán na primárním zdroji, uveden pouze v Nobackově přepisu.
