# Studie: Návrh agregátu

- **Kapitola:** `content/chapters/aggregate_design.md` (č. 07, kategorie Taktika, 879 řádků)
- **Cesta:** /navrh-agregatu
- **Typ kapitoly:** definiční
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| úvod | 21–31 | agregát je nejnáročnější taktický vzor, kompromis konzistence/výkon/škálovatelnost | [1][3][7][8] | odkazy pouze na katalogové stránky, ne na text |
| 07.01 Proč existují agregáty | 33–58 | agregát odpovídá na „kdo vymáhá invarianty" a „co se uloží v jedné transakci"; degradace do God Aggregate nebo anemie | [1][10] | dobrý rámec, chybí Evansova vlastní formulace z DDD Reference |
| 07.02 Čtyři pravidla podle Vernona | 60–84 | čtyři pravidla v pořadí: invarianty → malé agregáty → reference přes identitu → eventual consistency; Khononov přidává „páté" | [3][4][5] | pořadí i obsah pravidel **ověřeno správně**; chybí Vernonovy výjimky a „whose job is it" |
| 07.03 Invarianty jako východisko | 86–125 | pět typů invariantů, rozhodovací otázka „okamžitě vs. do sekund", postup objevení z Event Stormingu | Meyer (bez odkazu) | nejsilnější původní sekce kapitoly; Meyer není v bibliografii |
| 07.04 Velikost agregátu | 127–160 | tři důvody proti velkému agregátu (konkurence, paměť/IO, kompozitní invarianty) + anti-vzor God Aggregate | bez odkazů | duplikuje 21.04 a 16.03; chybí Vernonova statistika 70/30 |
| 07.05 Transakční konzistence | 162–256 | jeden agregát na transakci; anti-vzor `wrapInTransaction` přes dva účty vs. sága | [10] | pravidlo podáno jako absolutní; Vernon sám uvádí čtyři legitimní výjimky |
| 07.06 Eventual consistency | 258–297 | čtyřkrokový proces s outboxem; tři UI strategie; checkout příklad | bez odkazů | UI strategie jsou hodnotné a v jiných kapitolách nejsou |
| 07.07 Reference přes identitu | 299–486 | čtyři důvody pro ID reference; `OrderId`, celý agregát `Order`, PHP 8.4 `private(set)` | bez odkazů | kód se rozchází s kanonickým API knihy (viz G12–G14) |
| 07.08 Mapování v Symfony 8 / Doctrine ORM 3 | 488–679 | šest pravidel pro Doctrine; custom type, mapovaný `Order`, repozitář, `doctrine.yaml` | [7] (nepřesně) | technicky nejzastaralejší sekce; tři konkrétní chyby |
| 07.09 Pokročilá témata | 681–748 | large-collection, hot aggregate, snapshoty, partitioning/multi-tenancy | [8] | snapshoty patří do 13/16; čísla bez zdrojů |
| 07.10 Strategie referencování | 750–779 | pět typů ID, doporučení UUID v7 | bez odkazů | překrývá se s 16.05 |
| 07.11 Postup návrhu | 781–808 | sedmikrokový postup, testy velikosti a konkurence | „Vernonova metodika" | postup není Vernonův; jeho vlastní BOTE metoda chybí |
| 07.12 Typické chyby | 810–834 | čtyři chyby + obcházení kořene, anemický agregát, sdílený stav přes službu | bez odkazů | částečný překryv s 21.02 a 21.04 |
| 07.13 Checklist | 836–849 | 12 bodů | – | bod 7 je přísnější než Evans |
| 07.14 Další četba | 851–860 | sedm položek | [1]–[7] | chybí Evans *DDD Reference*, ddd-crew, Noback |
| FAQ | 862–879 | osm otázek | – | dobře navázané na kotvy |

Kapitola je poctivě strukturovaná definiční kapitola s jasnou linkou invarianty → hranice → transakce → Doctrine. Nejvíc prostoru dává vlastní práci autora: taxonomii invariantů (07.03) a UI strategiím pro eventual consistency (07.06). To jsou pasáže, které v českém prostoru nikde jinde nejsou a stojí za zachování.

Odbývá naopak dvě věci. První je samotný primární zdroj: kapitola Vernonovu trilogii shrne do čtyř odrážek, ale nepracuje s tím, co v ní je nejcennější – s explicitním seznamem situací, kdy se pravidla porušují, s Evansovým vodítkem „whose job is it?" a s metodou odhadu velikosti agregátu. Druhá je Doctrine: sekce 07.08 předává pravidla jako hotová, ale tři z nich v Doctrine ORM 3 nefungují tak, jak kapitola tvrdí. Sekce 07.09 a 07.10 pak duplikují kapitoly 13, 16 a 21, aniž by přidaly nový úhel.

## 2. Kanonické zdroje k tématu

**Evans, *DDD* (2003) a *DDD Reference* (2015).** Vzor zavedl Evans v šesté kapitole. Kondenzovaná definice z *DDD Reference* [2] zní: „Cluster the entities and value objects into aggregates and define boundaries around each. Choose one entity to be the root of each aggregate, and allow external objects to hold references to the root only (references to internal members passed out for use within a single operation only)." Následují tři pokyny, které kapitola nezmiňuje vůbec: „Use the same aggregate boundaries to govern transactions **and distribution**", „Within an aggregate boundary, apply consistency rules synchronously. Across boundaries, handle updates asynchronously" a „Keep an aggregate together on one server. Allow different aggregates to be distributed among nodes."

Dva důsledky pro kapitolu. Evans připouští předání reference na vnitřní člen agregátu ven „for use within a single operation only" – checklist bod 7 (řádek 844) je tedy přísnější než primární zdroj. A Evans hranici agregátu vždy spojuje i s **distribucí**, ne jen s transakcí; kapitola distribuci zmiňuje jen okrajově (řádek 169).

**Vernon, *Effective Aggregate Design* Part I–III (2011)** [3][4][5]. Tři eseje, ověřeno přímo z PDF na dddcommunity.org. Vernon je nenazývá „pravidla", ale **„rules of thumb"**. Jejich přesné znění a pořadí:

1. „Rule: Model True Invariants In Consistency Boundaries" (Part I)
2. „Rule: Design Small Aggregates" (Part I)
3. „Rule: Reference Other Aggregates By Identity" (Part II)
4. „Rule: Use Eventual Consistency Outside the Boundary" (Part II)

Kapitola má pořadí i obsah **správně** (řádky 67–79). Shrnutí v Part III [5] ale čtvrté pravidlo cituje s dovětkem: „Use Eventual Consistency Outside the Boundary (**after asking whose job it is**)". Ten dovětek kapitola nemá, přitom je to nejpraktičtější část celé série.

Podrobnosti, které kapitola vynechává:

- **Definice invariantu.** Vernon [3]: „An invariant is a business rule that must always be consistent. There are different kinds of consistency. One is transactional, which is considered immediate and atomic. There is also eventual consistency. When discussing invariants, we are referring to transactional consistency."
- **Co znamená „malý".** [3]: „limit the aggregate to just the root entity and a minimal number of attributes and/or value-typed properties. The correct minimum is the ones necessary, and no more." A kritérium výběru: „those that must be consistent with others, **even if domain experts don't specify them as rules**" – Vernon uvádí příklad `name` a `description` na `Product` jako implicitní invariant.
- **Statistika z reálného projektu.** [3]: „approximately 70% of all aggregates with just a root entity containing some value-typed properties. The remaining 30% had just two to three total entities."
- **„Ask Whose Job It Is".** [4]: Vernon uvádí, že vodítko dostal přímo od Evanse. „When examining the use case (or story), ask whether it's the job of the user executing the use case to make the data consistent. If it is, try to make it transactionally consistent (…). If it is another user's job, or the job of the system, allow it to be eventually consistent." A dodává, proč to funguje: „It exposes the real system invariants: the ones that must be kept transactionally consistent."
- **Tolerance doménových expertů.** [4]: „domain experts are often willing to allow for reasonable delays—a generous number of seconds, minutes, hours, or even days—before consistency occurs." Kapitola na řádku 270 mluví jen o „řádově sekundách".
- **„Reasons To Break the Rules".** Vernon [4] jmenuje čtyři: *Reason One: User Interface Convenience* (dávkové vytvoření více agregátů v jedné transakci – „if creating a batch of aggregate instances all at once is semantically no different than creating one at a time repeatedly, it represents one reason to break the rule of thumb with impunity"), *Reason Two: Lack of Technical Mechanisms* (chybí messaging, timery, vlákna), *Reason Three: Global Transactions* (vynucené 2PC v podnikovém prostředí), *Reason Four: Query Performance* („There may be times when it's best to hold direct object references to other aggregates"). Zavádí k tomu pojem **user-aggregate affinity**: pokud na dané množině instancí pracuje v daném okamžiku jen jeden uživatel, je porušení pravidla bezpečnější.
- **Metoda odhadu velikosti (Part III).** Vernon [5] odhaduje velikost agregátu „back-of-the-envelope" výpočtem: délka sprintu (12 dní) × počet re-estimací na task × počet tasků na backlog item → „one backlog item, 12 tasks, and 12 log entries, or 25 objects maximum total. That's not very many; it's a small aggregate." A celý postup zarámuje: „That entire effort would require 30 minutes, and perhaps as much as 60 minutes at worse case."
- **Verzování per entita.** V Part III [5] Vernon **nedává** verzi jen na kořen: „each entity type has its own optimistic concurrency version attribute. This is workable because the changing status invariant is managed on the BacklogItem root entity." Změny tasků tedy verzi kořene nezvyšují, pokud nezmění status. A dodává poznámku, která je přímo relevantní pro Doctrine i pro dokumentové úložiště: „The following analysis could need to be revisited if using, for example, document-based storage, since the root is effectively modified every time a collected part is modified."
- **Vernon sám pravidlo 3 poruší.** Na konci Part III [5] tým řeší overhead atributu `story` a zvolí: „they realized this could be a good time to break the rule to reference external aggregates only by identity. It seems like a suitable modeling choice to use a direct object reference, and declare its object-relational mapping so as to lazily load it."

**Vernon, *IDDD* (2013)** [7]. Kapitola 10 je „Aggregates", kapitola 12 je **„Repositories"**, ne mapování agregátů v ORM (ověřeno z obsahu knihy). Tvrzení kapitoly na řádku 491–492 („Vernon v IDDD věnuje této otázce celou kapitolu 12") je tedy nepřesné.

**Fowler, *DDD_Aggregate* (bliki, 23. 4. 2013)** [9]. Krátká definice: „a cluster of domain objects that can be treated as a single unit". Dva body, které kapitola nevyužívá: „Aggregates are the basic element of transfer of data storage – you request to load or save whole aggregates" a upozornění, že „DDD Aggregates are sometimes confused with collection classes (lists, maps, etc)".

**Khononov, *Learning DDD* (2021)** [8]. Ověřeno na úrovni obsahu: agregát je probírán v kapitole 6 „Tackling Complex Business Logic", agregát je zde definován jako **transakční hranice** – všechna jeho data se commitují jako jedna atomická transakce, hierarchie entit sdílejících jednu transakční hranici, měnitelná výhradně přes veřejné rozhraní kořene. Konkrétní znění „pátého pravidla" (řádek 82) ani „tří strategií pro large collection" (řádek 687) se ověřit nepodařilo – viz sekce 9.

## 3. Stav praxe a posuny

**Od pravidel k heuristikám a k explicitnímu artefaktu.** Nejvýraznější posun posledních let je **Aggregate Design Canvas** od ddd-crew (Kacper Gunia, Mathew McLoughlin, Nick Tune, Marijn Huizendveld; licence CC BY 4.0) [19]. Devět polí v pořadí: *Name*, *Description*, *State Transitions*, *Enforced Invariants*, *Corrective Policies*, *Handled Commands*, *Created Events*, *Throughput*, *Size*. Dvě z těchto polí nemá žádná z klasických knih:

- **Corrective Policies** – co se stane, když hranici *záměrně* uvolníme; kompenzační logika je součástí návrhu agregátu, ne důsledek selhání.
- **Throughput** a **Size** – odhad frekvence commandů, počtu souběžných klientů, míry růstu a životnosti instance. To je Vernonova BOTE metoda povýšená na standardní pole formuláře.

Kapitola má sedmikrokový postup (07.11), který dělá zhruba totéž, ale je autorský a bez opory. Canvas je dnes de facto standard pro modelovací workshopy a napojuje se přímo na Event Storming z kapitoly 04.

**Agregát se odpojuje od perzistentního modelu.** V PHP komunitě je nejzřetelnějším hlasem Matthias Noback [17]. Jeho výčet konkrétních třecích ploch mezi Doctrine ORM a agregátem: mapování value objectů vyžaduje boilerplate custom typů; kolekce value objectů se do relačního sloupce nevejdou; Doctrine trvá na ID u child entit, které identitu mimo agregát nepotřebují; obousměrné `OneToMany` vynucují změnu návrhu doménového modelu. A klíčový bod: **Unit of Work flushuje všechny změněné entity v session bez ohledu na hranice agregátu**, což přímo podkopává pravidlo „jeden agregát na transakci". Nobackovo doporučení je nakonec kázeň, ne technika – žádný technický mechanismus v Doctrine to nevynutí. Odtud i jeho experiment TalisORM.

To je posun, který kapitola nereflektuje: v roce 2011 se agregát a ORM entita braly jako totéž, dnes je běžné je vědomě oddělit (persisted object pattern – kniha ho má v 10.06, ale kapitola 07 na něj neodkazuje).

**„Jeden agregát na transakci" jako heuristika, ne zákon.** Vernon výjimky formuloval už v roce 2011 [4], komunitní praxe je od té doby posunula dál: běžná pozice dnes je, že v rámci jednoho Bounded Contextu je zápis do dvou agregátů v jedné transakci přijatelný kompromis, který lze později rozdělit, pokud vznikne potřeba shardingu nebo rozpadu do služeb. Kapitola pravidlo podává jako neporušitelné a tím se rozchází i s primárním zdrojem, na který se odvolává.

**Optimistický zámek přestal být triviální.** Diskuse se posunula od „dej `@Version` na kořen" k otázce, co dělat, když ORM verzi kořene při změně child entity nezvýší. V Doctrine je to otevřené od roku 2013 (issue DDC-2864 [14]).

## 4. Symfony / PHP specifika

**Optimistický zámek v Doctrine ORM 3.** Dokumentace [11] popisuje `#[ORM\Version]` na poli typu `integer` nebo `datetime`, s doporučením „Version numbers (not timestamps) should however be preferred as they can not potentially conflict in a highly concurrent environment". Podporované konstanty jsou pouze `LockMode::OPTIMISTIC`, `LockMode::PESSIMISTIC_WRITE` a `LockMode::PESSIMISTIC_READ`. **`OPTIMISTIC_FORCE_INCREMENT` v Doctrine neexistuje** – na rozdíl od JPA. Požadavek je otevřený od 18. 12. 2013 jako issue #3620 (DDC-2864) [14] a je formulován přesně v DDD kontextu: „When optimistic locking is being used, the version field is incremented after the update only if the entity itself has been modified. (…) The lack of this feature can be realized when only the aggregate root has a version field and some other parts of the aggregate is being modified."

Praktický důsledek pro kapitolu: `#[ORM\Version]` na `Order` **nechrání** souběžnou změnu `OrderItem`. Dva požadavky, které mění jen různé položky téže objednávky, projdou oba, verze kořene se nezvýší a invariant „součet položek = celková cena" se rozbije. Obcházení: buď doménová metoda kořene při každé změně potomka zapíše i do vlastního pole kořene (typicky přepočítaná `totalAmount` nebo `updatedAt`), nebo se použije explicitní `$em->lock($order, LockMode::OPTIMISTIC, $expectedVersion)`, nebo pesimistický zámek. Dokumentace [11] navíc zdůrazňuje, že korektní použití optimistického zámku napříč requesty vyžaduje přenášet verzi klientem („you have to add the version as an additional hidden field").

**Nativní lazy objects nahradily proxy.** ORM 3.4.0 (28. 6. 2025) [13] přidal podporu PHP 8.4 lazy objects přes `$config->enableNativeLazyObjects(true)`; ORM 4.0 bude PHP 8.4 vyžadovat a postaví se na nich celý. V aktuální konfigurační referenci DoctrineBundle [15] už **`enable_lazy_ghost_objects` není** a `enable_native_lazy_objects` je uvedeno s výchozí hodnotou `true` a poznámkou „No-op, deprecated, will be removed in the future" – tedy nativní lazy objects jsou jediný režim. `auto_generate_proxy_classes` v referenci rovněž není. `identity_generation_preferences` zůstává.

Důsledek: klíčové omezení, kvůli kterému doménové entity nesměly být `final`, padlo. Nativní lazy object je instancí téže třídy, ne potomkem. Callout na řádcích 599–611 to sice v poslední větě připouští, ale rámuje to jako budoucnost („dokud na nich projekt neběží"); v Symfony 8 s aktuálním DoctrineBundle je to výchozí stav.

**Custom typy a DBAL 4.** `Type::getName()` byl v DBAL označen jako neužitečný a v 4.0 odstraněn, náhradou je `Type::getTypeRegistry()->lookupName($type)` [16]. Kapitola to na řádcích 541–543 uvádí správně. Odstraněno bylo i `requiresSQLCommentHint()`, což u custom typů způsobuje opakované generování migrací (doctrine/dbal#6257, doctrine/migrations#1441) – to je praktická past, kterou kapitola nezmiňuje.

**EXTRA_LAZY v ORM 3.6.** Stále podporováno, bez deprecation [12]. Bez inicializace kolekce fungují: `contains()`, `containsKey()`, `count()`, `first()`, `get()`, `isEmpty()`, `slice()`, `add()`, `offsetSet()`. Pozor: `matching($criteria)` na neinicializované kolekci nekonvertuje hodnoty na databázové typy, takže backed enumy se nenahradí skalární hodnotou (doctrine/orm#11481) – chování se tedy liší podle toho, zda je kolekce načtená. Kapitola `matching()` doporučuje (řádek 696) bez této výhrady.

**Messenger a transakční hranice.** Middleware `doctrine_transaction` otevře transakci, zavolá handler, zavolá `flush()` a commitne; handler tedy `flush()` volat nemá [22]. Kapitola to na řádku 646 uvádí správně. Co ale nezmiňuje: `flush()` commituje **všechno**, co je v Unit of Work špinavé, ne jen agregát, který handler načetl [17]. Repozitář z řádků 643–650 tedy pravidlo „jeden agregát na transakci" nevynucuje – vynucuje ho jen kázeň, případně architektonický test (kapitola 17). Druhá věc: `dispatch_after_current_bus` musí být v řetězci **před** `doctrine_transaction`, aby se odeslané zprávy zpracovaly až po commitu [22].

**Identifikátory.** `Uuid::v7()` přibylo v symfony/uid v Symfony 6.2 [21]; standard je RFC 9562. V Symfony 7.4 [20] je UUID v7 **výchozí verzí** továrny (`UuidFactory::create()`), generování má mikrosekundovou přesnost místo milisekundové (a je asi o 10 % rychlejší) a přibyla `MockUuidFactory` pro determinismus v testech. Doporučení kapitoly (řádky 773–776) je správné, ale zastaralé v detailu: v Symfony 8 už není v7 „doporučená volba", ale výchozí, a testovatelnost generování ID je nová a pro DDD relevantní.

## 5. Sporné a chybně podávané body

**„Jeden agregát na transakci" jako absolutní pravidlo.** Kapitola (162–177, FAQ na 865–866) říká, že porušení je anti-vzor. Vernon [4] má celou sekci „Reasons To Break the Rules" se čtyřmi legitimními důvody a explicitně píše, že zkušený praktik to někdy udělá „but only with good reason". Zároveň uzavírá: „Certainly we don't go in search of excuses to break the aggregate rules of thumb." Doporučení pro knihu: pravidlo držet jako výchozí, ale přidat krátký odstavec s Vernonovými čtyřmi výjimkami a s pojmem user-aggregate affinity. Bez toho kapitola dezinformuje čtenáře, který pak sáhne po sáze i tam, kde stačí dávkové vytvoření agregátů.

**„Nikdy objektová reference mezi agregáty."** Kapitola (301–302) je kategorická. Vernon [4] jako Reason Four uvádí přesný opak pro případ výkonu dotazů a v Part III [5] toto pravidlo sám poruší ve prospěch lazy-loaded reference. Doporučení: držet ID referenci jako výchozí, ale pojmenovat výjimku – a hlavně říct, co ji odlišuje od chyby (reference je *read-only*, nikdy se přes ni nemodifikuje).

**„Optimistický zámek na kořeni chrání agregát."** Kapitola to tvrdí třikrát (171–173, 500–502, checklist bod 4). V Doctrine to neplatí, pokud se mění jen vnitřní entita [11][14]. Toto není spor mezi zdroji, je to faktická chyba. Doporučení: opravit a přidat konkrétní obcházení.

**Přísnost vůči vnitřním entitám.** Checklist bod 7 (844) a odstavec na 826–827 zakazují jakýkoli přístup k vnitřní entitě zvenčí. Evans [2] připouští „references to internal members passed out for use within a single operation only". Doporučení: formulaci zmírnit na „vnitřní entita se ven nepředává k uchování ani k modifikaci", což je věcně totéž a není v rozporu s primárním zdrojem.

**Práh pro snapshoty.** Kapitola (724–725) uvádí „typicky 50–100" eventů. Toto číslo se nepodařilo dohledat v žádném primárním zdroji; komunitní doporučení jsou o řád vyšší. Doporučení: číslo buď podložit, nebo nahradit formulací „práh se měří, ne odhaduje" a odkázat do kapitoly 13.

**Práh pro hot aggregate.** „Více než 5–10 transakcí za sekundu na jednu instanci agregátu" (797) a stejné číslo ve FAQ (872). Nepodložené. Reálný práh závisí na době trvání transakce, ne na absolutním TPS. Doporučení: nahradit vztahem (pravděpodobnost konfliktu roste s délkou transakce × frekvencí zápisů na instanci) a odkázat na pole *Throughput* v Aggregate Design Canvas.

**Meyer a Design by Contract.** Odstavec 90–92 atribuuje pojetí invariantu Meyerovi a *Object-Oriented Software Construction*, ale kniha není v sekci 07.14 ani nikde jinde v kapitole. Podle konvencí knihy musí mít každá atribuce bibliografický záznam.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | nepodložené | `aggregate_design.md:500-502`, `:171-173`, `:841` | `#[ORM\Version]` na kořeni nechrání změny vnitřních entit – Doctrine verzi nezvýší, pokud se nezměnilo pole kořene [11][14] | opravit tvrzení, doplnit obcházení (dotknout se pole kořene / explicitní `$em->lock()`); doplnit, že `OPTIMISTIC_FORCE_INCREMENT` v Doctrine není |
| G2 | chybí | `:162-177`, FAQ `:865-866` | Vernonovy čtyři „Reasons To Break the Rules" a pojem user-aggregate affinity [4] | nová podsekce ~25 řádků v 07.05 |
| G3 | chybí | `:60-84` | Vernonovo/Evansovo vodítko „Ask Whose Job It Is" [4][5] – nejpraktičtější část série | ~10 řádků do 07.02 nebo 07.03 |
| G4 | zastaralé | `:599-611`, `:668-669` | `enable_lazy_ghost_objects` už v DoctrineBundle není; `enable_native_lazy_objects` je výchozí a označené jako no-op; `auto_generate_proxy_classes` v referenci chybí [13][15] | přepsat callout i YAML; entity mapované Doctrine mohou být `final` |
| G5 | nepodložené | `:526` vs. `:335` | `OrderIdType::getSQLDeclaration()` deklaruje `length => 26, fixed => true` (délka ULID), ale `OrderId::generate()` vrací `Uuid::v7()->toRfc4122()` = 36 znaků | opravit na 36 znaků, nebo na `binary(16)` s konverzí |
| G6 | sporné | `:301-302`, `:844` | absolutní zákaz objektové reference a přístupu k vnitřní entitě je přísnější než Evans [2] i Vernon [4][5] | zmírnit formulaci, pojmenovat výjimku (read-only lazy reference kvůli výkonu dotazů) |
| G7 | chybí | `:488-679` | Unit of Work flushuje všechny špinavé entity bez ohledu na hranici agregátu [17] – repozitář na `:643-650` pravidlo nevynucuje | doplnit ~12 řádků: pravidlo je kázeň, ne technika; odkaz na architektonické testy (kap. 17) |
| G8 | chybí | celá kapitola | Aggregate Design Canvas (ddd-crew) [19] – dnes standardní artefakt pro návrh agregátu, včetně polí Corrective Policies, Throughput, Size | nová sekce ~35 řádků, navázat na Event Storming (kap. 04) |
| G9 | chybí | `:781-808` | Vernonova BOTE metoda odhadu velikosti agregátu z Part III [5] (konkrétní výpočet, 30–60 minut) | ~20 řádků, nahradit jimi nepodložené prahy v krocích 4 a 5 |
| G10 | nepodložené | `:724-725` | práh snapshotu „typicky 50–100 eventů" bez zdroje, komunitní doporučení jsou o řád vyšší | číslo odstranit nebo podložit; sekci zkrátit a odkázat na kap. 13 |
| G11 | nepodložené | `:796-798`, `:872` | práh hot aggregate „5–10 transakcí za sekundu" bez zdroje | nahradit vztahem frekvence × délka transakce |
| G12 | sporné | `:383-406`, `:437-448` | `Order::place(CustomerId, ShippingAddress, OrderItemDraft ...)` a `addItem(OrderItemDraft)` se rozchází s kanonickým API v `CLAUDE.md` (`addItem(ProductId, int, Money)`) i s `basic_concepts.md:239` | sjednotit s kanonickým API, nebo `OrderItemDraft` zavést a použít i v kap. 06 |
| G13 | sporné | `:411` | vyhazuje `InvalidStateTransition`; `CLAUDE.md` i `basic_concepts.md:198` mají `InvalidOrderStateTransitionException` | přejmenovat |
| G14 | sporné | `:379` vs. `basic_concepts.md:227` | `OrderStatus::Draft` zde, `OrderStatus::Created` v kap. 06 | sjednotit napříč knihou |
| G15 | nepodložené | `:491-492` | „Vernon v IDDD věnuje této otázce celou kapitolu 12" – kap. 12 je *Repositories*, mapování agregátů tam není | opravit na kap. 10 (Aggregates) + kap. 12 (Repositories) pro repozitáře |
| G16 | nepodložené | `:90-92` | atribuce Meyerovi a *OOSC* bez bibliografického záznamu | doplnit do 07.14 |
| G17 | chybí | `:60-84` | Vernonova statistika 70 % agregátů = kořen + value objects, 30 % = dvě až tři entity [3] | jedna věta, podpírá pravidlo „malé agregáty" konkrétním číslem |
| G18 | mělké | `:696` | `matching($criteria)` na neinicializované EXTRA_LAZY kolekci nekonvertuje typy (backed enumy) – chování se liší podle stavu kolekce [12] | doplnit výhradu, jedna věta |
| G19 | nadbytečné | `:720-734`, `:750-779` | snapshoty duplikují kap. 13, srovnání ID duplikuje `performance_aspects.md:461-500` | zkrátit na odstavec s odkazem; uvolněný prostor dát G2/G3/G8 |
| G20 | chybí | `:488-679` | kdy agregát ≠ entity graph: oddělení doménového a perzistentního modelu [17]; kniha má persisted object pattern v `implementation_in_symfony.md:625`, ale kap. 07 na něj neodkazuje | ~15 řádků + odkaz na 10.06 |
| G21 | mělké | `:736-748` | multi-tenancy bez jediného zdroje, tři odstavce o DB topologii spíš než o agregátu | zkrátit na tvrzení „`tenantId` je součást identity agregátu" a zbytek přesunout/odkázat |
| G22 | chybí | `:33-58` | Evansovo spojení hranice agregátu s **distribucí** („Keep an aggregate together on one server") [2] | jedna až dvě věty, podpírá argument z `:169-170` |
| G23 | mělké | `:270-272` | „většina byznys procesů snese řádově sekundy"; Vernon [4] mluví o „seconds, minutes, hours, or even days" a doporučuje se zeptat doménových expertů | rozšířit rozsah, přidat, že se to zjišťuje otázkou, ne odhadem |
| G24 | chybí | `:541-543` | odstranění `requiresSQLCommentHint()` v DBAL 4 způsobuje opakované generování migrací u custom typů [16] | jedna věta jako varování |

## 7. Doporučení k přepisu

**P1-1 — Opravit tvrzení o optimistickém zámku (G1).**
Kapitola třikrát tvrdí, že `#[ORM\Version]` na kořeni chrání celý agregát. V Doctrine to neplatí a je to jediný nález, který může čtenáře přímo dovést k tiché ztrátě dat v produkci. Chce to opravu tvrzení, jednu odrážku s obcházením a poznámku, že `OPTIMISTIC_FORCE_INCREMENT` z JPA v Doctrine chybí (issue otevřená od 2013). *Oprava tří míst + nový odstavec ~15 řádků v 07.08.*

**P1-2 — Doplnit Vernonovy výjimky z pravidla jedné transakce (G2, G6).**
Kapitola se odvolává na Vernona a zároveň jeho pravidlo podává přísněji, než ho formuloval on sám. Vernon má explicitní sekci se čtyřmi důvody a s pojmem user-aggregate affinity; v Part III pravidlo o referenci přes identitu sám poruší. Bez toho kapitola v nejcitovanější sekci nesouhlasí s primárním zdrojem, který uvádí. *Nová podsekce v 07.05 ~25 řádků + zmírnění dvou formulací v 07.07 a v checklistu.*

**P1-3 — Opravit Doctrine konfiguraci a callout o `final` (G4).**
YAML na `:668-669` konfiguruje volbu, která v DoctrineBundle už není, a callout staví omezení „entity nesmí být final" jako současný stav. Od ORM 3.4 a s aktuálním DoctrineBundle je nativní lazy object výchozí režim a omezení padlo. Kapitola cílí na Symfony 8, takže tohle je přímý faktický rozpor se stackem knihy. *Přepis calloutu (~12 řádků) + oprava YAML bloku.*

**P1-4 — Opravit `OrderIdType` (G5).**
`CHAR(26)` pro 36znakový RFC 4122 řetězec. V produkci to skončí ořezáním nebo chybou. Ukázka je navíc kotvou celé sekce o custom typech. *Oprava dvou řádků.*

**P1-5 — Sjednotit ukázku `Order` s kanonickým API knihy (G12, G13, G14).**
Kapitola 07 je hlavní kapitolou o agregátu, a přesto má jiné jméno factory parametrů, jinou signaturu `addItem()`, jiný název výjimky a jinou hodnotu enumu než kapitola 06 a než `CLAUDE.md`. Čtenář, který čte knihu lineárně, potká tři varianty téže třídy. *Přepis ukázky na `:355-449` a `:545-596`, oprava kap. 06 nebo 07 podle rozhodnutí v sekci 8.*

**P1-6 — Doplnit, že Unit of Work hranici agregátu nevynucuje (G7, G20).**
Sekce 07.08 předává šest pravidel jako by je Doctrine vymáhala. Nevymáhá ani jedno; `flush()` commituje vše špinavé bez ohledu na hranice. Bez tohoto přiznání dostane čtenář falešný pocit bezpečí právě v místě, kde ho kapitola nejvíc ujišťuje. Napojit na persisted object pattern v 10.06 a na architektonické testy v kap. 17. *Nový odstavec ~15 řádků.*

**P2-1 — Přidat sekci o Aggregate Design Canvas (G8).**
Kapitola má vlastní sedmikrokový postup bez opory. Canvas je dnes standardní artefakt, je pod CC BY 4.0, navazuje na Event Storming z kap. 04 a jeho pole *Corrective Policies*, *Throughput* a *Size* pokrývají přesně to, co kapitola dnes řeší nepodloženými prahovými čísly. *Nová sekce ~35 řádků, částečně nahradí 07.11.*

**P2-2 — Nahradit nepodložené prahy Vernonovou BOTE metodou (G9, G10, G11).**
Tři čísla v kapitole („stovky řádků", „5–10 TPS", „50–100 eventů") vypadají autoritativně a nemají zdroj. Vernon v Part III předvádí konkrétní výpočet, jak se k takovému odhadu dojde, včetně toho, že celá analýza zabere 30 až 60 minut. Metoda je hodnotnější než čísla a je citovatelná. *Přepis kroků 4 a 5 v 07.11 + zkrácení dvou pasáží v 07.09, ~20 řádků netto.*

**P2-3 — Doplnit „Ask Whose Job It Is" a rozsah tolerance (G3, G23).**
Otázka „je úkolem tohoto uživatele udělat data konzistentní?" je jednořádkové vodítko, které rozhoduje spor transakční vs. eventual consistency lépe než všechny taxonomie v 07.03. Pochází přímo od Evanse. Ve stejném zásahu opravit „řádově sekundy" na Vernonův skutečný rozsah. *~12 řádků do 07.03, oprava dvou vět v 07.06.*

**P2-4 — Zkrátit duplicitní pasáže (G19, G21).**
Snapshoty patří do kap. 13, srovnání typů ID do 16.05, DB topologie multi-tenancy nikam v této kapitole. Uvolní to ~40 řádků pro P1-2, P2-1 a P2-3, takže rozsah kapitoly zůstane zhruba stejný. *Škrty a odkazy.*

**P3-1 — Doplnit chybějící atribuce a drobné technické výhrady (G15, G16, G17, G18, G22, G24).**
Šest jednovětých oprav: číslo kapitoly IDDD, Meyer do bibliografie, Vernonova statistika 70/30, výhrada k `matching()` na EXTRA_LAZY kolekci, Evansovo spojení hranice s distribucí, past `requiresSQLCommentHint()` v DBAL 4. *Šest lokálních úprav.*

**P3-2 — Doplnit Evanse *DDD Reference* (2015) do sekce 07.14.**
Kapitola cituje Evanse jen přes knihu z roku 2003 a přes katalogovou stránku. *DDD Reference* je volně dostupné PDF s kondenzovanou definicí, na kterou se studie v sekci 2 opírá, a kniha ho podle `CLAUDE.md` už používá jako zdroj jinde. *Jedna položka.*

## 8. Otevřené otázky pro autora

1. **Kanonický `Order`: sjednotit směrem ke kapitole 06, nebo ke kapitole 07?** Varianta z kap. 07 (`place()` s `OrderItemDraft`, privátní konstruktor, `record()`) je doménově čistší a odpovídá `CLAUDE.md` v bodě factory. Varianta z kap. 06 odpovídá `CLAUDE.md` v bodě signatury `addItem()`. Sjednotit je nutné, ale směr je autorské rozhodnutí – a dotkne se i kap. 10, 12 a 24.

2. **Kolik prostoru dát Aggregate Design Canvas?** Je to samostatný nástroj s vlastní metodikou. Patří do kap. 07 jako sekce, do kap. 04 (Event Storming) jako navazující krok, nebo do obou s křížovým odkazem?

3. **Držet v kapitole snapshoty a multi-tenancy?** Obojí je dnes v 07.09 spíš proto, že se to nikam jinam nevešlo. Snapshoty logicky patří do kap. 13, multi-tenancy nemá v knize domov vůbec. Založit pro multi-tenancy vlastní sekci jinde, nebo ji z knihy vypustit?

4. **Jak daleko jít s „agregát ≠ Doctrine entita"?** Kniha má persisted object pattern v 10.06. Má kap. 07 tuto variantu jen zmínit s odkazem, nebo ukázat obě mapování `Order` vedle sebe? Druhá varianta je +40 řádků a zvýší náročnost kapitoly, která už má `difficulty: 4`.

5. **Uvádět v knize identifikované chyby Doctrine jako otevřené issues?** G1 se opírá o issue z roku 2013, které je stále otevřené. Odkaz na GitHub issue je ověřitelný, ale může zestárnout, pokud to Doctrine vyřeší. Alternativa je popsat chování bez odkazu na issue.

6. **Má kapitola aktualizovat doporučení k ID na Symfony 7.4+?** UUID v7 je tam výchozí verzí továrny a přibyla `MockUuidFactory`. Testovatelnost generování ID je pro DDD relevantní, ale patří spíš do kap. 17 než sem.

## 9. Bibliografie

### Ověřené zdroje

`[1]` Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*, Addison-Wesley, 2003. https://www.dddcommunity.org/book/evans_2003/ (katalogová stránka; text knihy neověřován přímo)

`[2]` Eric Evans — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, 2015. https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf (přístup 2026-09-03; sekce Aggregates ověřena přímo z PDF)

`[3]` Vaughn Vernon — *Effective Aggregate Design, Part I: Modeling a Single Aggregate*, 2011. https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_1.pdf (přístup 2026-09-03; ověřeno přímo z PDF)

`[4]` Vaughn Vernon — *Effective Aggregate Design, Part II: Making Aggregates Work Together*, 2011. https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_2.pdf (přístup 2026-09-03; ověřeno přímo z PDF)

`[5]` Vaughn Vernon — *Effective Aggregate Design, Part III: Gaining Insight Through Discovery*, 2011. https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_3.pdf (přístup 2026-09-03; ověřeno přímo z PDF)

`[6]` dddcommunity.org — *Effective Aggregate Design by Vaughn Vernon* (rozcestník série). https://www.dddcommunity.org/library/vernon_2011/ (přístup 2026-09-03)

`[7]` Vaughn Vernon — *Implementing Domain-Driven Design*, Addison-Wesley, 2013. https://www.informit.com/store/implementing-domain-driven-design-9780321834577 (obsah ověřen: kap. 10 Aggregates, kap. 12 Repositories; text knihy neověřován přímo)

`[8]` Vlad Khononov — *Learning Domain-Driven Design*, O'Reilly, 2021. https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/ (ověřeno na úrovni obsahu a popisu kap. 6; text knihy neověřován přímo)

`[9]` Martin Fowler — *DDD_Aggregate* (bliki), 23. 4. 2013. https://martinfowler.com/bliki/DDD_Aggregate.html (přístup 2026-09-03)

`[11]` Doctrine — *Transactions and Concurrency*, Doctrine ORM 3.6 dokumentace. https://www.doctrine-project.org/projects/doctrine-orm/en/3.6/reference/transactions-and-concurrency.html (přístup 2026-09-03)

`[12]` Doctrine — *Extra Lazy Associations*, Doctrine ORM 3.6 dokumentace. https://www.doctrine-project.org/projects/doctrine-orm/en/3.6/tutorials/extra-lazy-associations.html (přístup 2026-09-03)

`[13]` Doctrine — *ORM 3.4.0 released with Native Lazy Objects and Property hooks support*, 28. 6. 2025. https://www.doctrine-project.org/2025/06/28/orm-3.4.0-released.html (přístup 2026-09-03)

`[14]` doctrine/orm — *DDC-2864: New type of lock: OPTIMISTIC_FORCE_INCREMENT*, issue #3620, otevřeno 18. 12. 2013, stále otevřené. https://github.com/doctrine/orm/issues/3620 (přístup 2026-09-03)

`[15]` DoctrineBundle — *Configuration Reference*. https://symfony.com/bundles/DoctrineBundle/current/configuration.html (přístup 2026-09-03; ověřeno: `enable_native_lazy_objects` výchozí `true` s poznámkou „No-op, deprecated, will be removed in the future"; `enable_lazy_ghost_objects` ani `auto_generate_proxy_classes` v referenci nejsou)

`[16]` doctrine/dbal — *UPGRADE.md* + issue #6257 (odstranění `Type::getName()` a `requiresSQLCommentHint()` v DBAL 4). https://github.com/doctrine/dbal/blob/4.4.x/UPGRADE.md, https://github.com/doctrine/dbal/issues/6257 (ověřeno přes výňatky, stránky nefetchovány celé)

`[17]` Matthias Noback — *Doctrine ORM and DDD aggregates*, 19. 6. 2018. https://matthiasnoback.nl/2018/06/doctrine-orm-and-ddd-aggregates/ (přístup 2026-09-03)

`[19]` ddd-crew — *Aggregate Design Canvas* (Kacper Gunia, Mathew McLoughlin, Nick Tune, Marijn Huizendveld), licence CC BY 4.0. https://github.com/ddd-crew/aggregate-design-canvas (přístup 2026-09-03)

`[20]` Symfony — *New in Symfony 7.4: Uid Improvements*. https://symfony.com/blog/new-in-symfony-7-4-uid-improvements (přístup 2026-09-03)

`[21]` Symfony — *New in Symfony 6.2: New Uid Features*. https://symfony.com/blog/new-in-symfony-6-2-new-uid-features (ověřeno přes výňatek: podpora UUID v7 a v8 přidána v 6.2)

`[22]` Symfony — *Messenger: Sync & Queued Message Handling* (middleware `doctrine_transaction`). https://symfony.com/doc/current/messenger.html (ověřeno přes výňatek + zdrojový kód `symfony/doctrine-bridge`, `Messenger/DoctrineTransactionMiddleware.php`, větev 8.1)

### Doověřeno druhým průchodem (2026-09-04) – Doctrine, `final` a odstraněné konfigurační volby

Ověřeno proti primárním zdrojům: `UPGRADE-3.0.md` a `src/DependencyInjection/Configuration.php`
ve větvi 3.3.x repozitáře `doctrine/DoctrineBundle` (výchozí větev, poslední push 17. 8. 2026),
release notes ORM 3.4.0 a dokumentace Symfony k lazy services.

**1. Dvě konfigurační volby, které kniha používá, v DoctrineBundle 3 neexistují.**
`UPGRADE-3.0.md` je vypisuje mezi odstraněnými:

> `doctrine.orm.enable_lazy_ghost_objects`
> Also, the 3 following options were no-ops when enabling native lazy objects and have been
> removed as well: `doctrine.orm.auto_generate_proxy_classes`, `doctrine.orm.proxy_dir`,
> `doctrine.orm.proxy_namespace`

DoctrineBundle 3 přitom vyžaduje PHP 8.4+, ORM 3 a DBAL 4 – tedy přesně cíl knihy. YAML ukázka
v `aggregate_design.md:668–669` obě odstraněné volby nastavuje, takže na Symfony 8 neprojde
kompilací kontejneru. Komentář na ř. 669 („v ORM 3 jediný podporovaný režim; vypnout jde jen
s ORM 2“) popisuje stav, který skončil.

**2. Nativní lazy objekty nelze vypnout, takže entita mapovaná Doctrine `final` být může.**
V `Configuration.php` 3.3.x je `enable_native_lazy_objects` s `defaultTrue()` a validací
`thenInvalid('The setting "enable_native_lazy_objects" can no longer be disabled and should not
be set')`. Od DoctrineBundle 3.1 je volba navíc deprecated s odůvodněním „native lazy objects are
now always enabled“ a v 4.0 zmizí. Nativní lazy objekty nevytvářejí podtřídu (jsou instancí téže
třídy přes `ReflectionClass::newLazyGhost`), takže důvod pro ne-final mizí. Symfony to pro lazy
services formuluje přímo: „when using PHP 8.4 or later, lazy services rely on native lazy objects,
so final and readonly classes are fully supported.“

**3. Rozsah v knize.** Tvrzení „ne final – Doctrine proxy z entity dědí“ je průřezové a stojí
v šesti kapitolách:

| Soubor | Řádky |
|---|---|
| `aggregate_design.md` | 599–610 (callout), 668–669 (YAML) |
| `case_study.md` | 369–370, 391 |
| `implementation_in_symfony.md` | 315 |
| `migration_from_crud.md` | 369 |
| `performance_aspects.md` | 112, 576 |
| `practical_examples.md` | 268 |

**Doporučení.** Opravit jako jeden průřezový zásah, ne po kapitolách. Z YAML ukázky obě odstraněné
volby vyškrtnout a `enable_native_lazy_objects` **nenastavovat** (je deprecated a výchozí).
Callout „Proč entita mapovaná Doctrine není `final`“ přepsat na opačné sdělení: na Symfony 8 jsou
nativní lazy objekty vždy zapnuté, takže entity `final` být mohou; historický důvod zmínit nejvýš
jednou větou pro čtenáře na starším stacku. Komentáře „// ne final – Doctrine proxy z entity dědí“
v pěti ukázkách odstranit spolu s tím.

### Neověřené / nedohledané

- **Pat Helland – *Life beyond Distributed Transactions: an Apostate's Opinion* – DOHLEDÁNO
  2026-09-04.** Původní publikace: **CIDR 2007, s. 132–141** (3. bienální Conference on Innovative
  Data Systems Research, 7.–10. 1. 2007, Asilomar). Volně dostupné PDF je na
  `ics.uci.edu/~cs223/papers/cidr07p15.pdf`, záznam v dblp `conf/cidr/Helland07`.

  **Věta, která patří přímo do této kapitoly:** Helland zavádí abstrakci *entity* a *activities*
  a entity vymezuje takto – jsou to kolekce pojmenovaných dat, která *„may be atomically updated
  within the entity but never atomically updated across entities“*. To je hranice transakce
  formulovaná nezávisle na DDD a o šest let dřív než Vernonovy *Effective Aggregate Design*;
  jako opora pro pravidlo „jedna transakce = jeden agregát“ je silnější než cokoli, co kapitola
  dnes cituje. Helland sám o svém obratu píše, že dřív byl zastáncem globální serializovatelnosti
  a přirovnává tyto platformy k Maginotově linii.

  **Doporučení: citovat CIDR 2007 s odkazem na volné PDF, ne reprint na queue.acm.org.**`. Existenci a autorství eseje potvrzuje Vernonův seznam referencí v Part II [4], který odkazuje na původní CIDR 2007 verzi (`ics.uci.edu/~cs223/papers/cidr07p15.pdf`). Samotný text ale v této rešerši ověřen nebyl, takže tvrzení kapitoly na `:174-177` („jediná životaschopná cesta je one entity per transaction“) zůstává neověřené. **Dohledat ručně** – je to jeden ze dvou pilířů argumentace v 07.05.

- **Khononovo „páté pravidlo" (`:81-84`).** Tvrzení, že Khononov v *Learning DDD* formuluje „jeden command modifikuje právě jeden agregát", se nepodařilo potvrdit z žádného dostupného zdroje. Ověřit v knize, kap. 6.

- **Khononovy „tři strategie" pro large-collection problem (`:687-699`).** Atribuce Khononovovi je v kapitole explicitní, ale zdroj se nepodařilo ověřit. Navíc druhá strategie (Doctrine `EXTRA_LAZY`) je zjevně autorský doplněk, ne Khononovův text – kniha není o PHP. Ověřit v knize a atribuci rozdělit.

- **Práh snapshotu – DOHLEDÁNO 2026-09-04, tradované doporučení je doložené.** Greg Young to
  říká v přednášce **Code on the Beach 2014** (přepis je na blogu Kurrentu): o snapshotu neuvažovat
  dřív než zhruba **u tisíce událostí, možná i víc**.

  Užitečnější než samotné číslo je ale jeho argument o *umístění*: snapshoty patří do samostatné
  tabulky klíčované agregátem a verzí, kterou plní asynchronní proces na pozadí. Snapshot vložený
  přímo do event logu je vždy nucen být na poslední verzi, což u vytížených agregátů vyrábí smyčku
  konfliktů optimistického zámku; oddělená tabulka nechá snapshot platný ve verzi, ve které vznikl.
  **Doporučení: číslo uvést s tímto zdrojem a přidat argument o oddělené tabulce – ten je pro
  čtenáře cennější než práh.**e v této rešerši dohledat nepodařilo (odkazovaný článek na codeopinion.com téma snapshotů neobsahuje). Číslo „50–100“ z kapitoly (`:724-725`) nemá zdroj žádný. **Dohledat ručně** v Youngových materiálech nebo v dokumentaci Kurrent/EventStoreDB.

- **Udi Dahan – *Don't Create Aggregate Roots* – OBSAH OVĚŘEN 2026-09-04, text je čitelný celý.**
  Datum 29. 6. 2009 potvrzeno. Teze: agregát se nemá vytvářet přímo v aplikační vrstvě, ale
  vzniká z jiné existující perzistentní entity. Doslova: *„if your service layer is newing up some
  entity and saving it – that entity isn't an aggregate root **in that use case**.“* a
  *„don't go saving entities in your service layer – let the domain model manage its own state.“*
  Praktické pravidlo, které z toho plyne: *„Always get an entity. At least one.“*

  V příkladu vytváří `Referrer` entitu `Visitor` svou metodou a nová entita se uloží skrz
  *persistence by reachability* při commitu rodičovské transakce. Argument shrnuje větou, že
  *„Customers don't just appear out of thin air“* – vždy existuje obchodní kontext, který vznik
  spouští, a ten je třeba najít.

  **Dopad na sekci o factory metodách (`:452-454`):** text lze citovat. Je ale v napětí s kanonickým
  `Order::place()` z `CLAUDE.md`, což je statická factory na samotném agregátu. Dahanův postoj
  stojí za zmínku jako protihlas, ne jako pravidlo k převzetí – jinak by se rozpadla konvence
  napříč knihou.

- **Matthias Noback — *DDD entities and ORM entities* (2022), https://matthiasnoback.nl/2022/04/ddd-entities-and-orm-entities/.** Nalezeno v listingu, nefetchováno. Pravděpodobně nejrelevantnější existující text k nálezu G20.

- **Mathias Verraes — modelovací heuristiky pro hranice agregátu.** Existence workshopů a přednášek ověřena (verraes.net, dddeurope.academy), ale žádný konkrétní citovatelný text s dohledatelným záznamem se najít nepodařilo. Pokud se má kniha na Verraese v této kapitole odvolat, je potřeba najít konkrétní přednášku se záznamem.

- **`public readonly` u mapovaných entit (`:564`, `:567`) – DOVĚŘENO 2026-09-04. Podpora existuje,
  ale právě u `#[ORM\Id]` jsou hlášené problémy.** `readonly` vlastnosti Doctrine podporuje od
  **ORM 2.11** (leden 2022) bez zvláštních mapovacích voleb. Hlášené výjimky se ale týkají přesně
  toho, co ukázka dělá:

  - `doctrine/orm#10032` – při mazání entity s `readonly` ID a při inicializaci proxy přístupem
    k nenačtené vlastnosti padá `LogicException: Attempting to change readonly property`.
  - `doctrine/orm#10660` – Doctrine nedokáže u `readonly` vlastnosti nahradit `ArrayCollection`
    za `PersistentCollection`, takže `readonly` kolekce nefunguje.

  **Zbývá otevřená otázka, kterou rešerše neuzavře:** obě hlášení pocházejí z éry ORM 2 s proxy
  třídami. S nativními lazy objekty (ORM 3.4+, na Symfony 8 vždy zapnuté) důvod pro první z nich
  odpadá, protože ghost je instancí téže třídy a nemusí přepisovat vlastnost. Zda to platí i pro
  mazání a pro kolekce, je nutné **ověřit spuštěním na ORM 3**, ne dohledáním. Do té doby ukázku
  s `#[ORM\Id] public readonly` nepovažovat za bezpečnou.ctrine ORM 3 spolehlivě podporuje včetně `refresh()` a hydratace, se v této rešerši ověřit nepodařilo. **Ověřit prakticky** proti Doctrine ORM 3.6 před přepisem.
