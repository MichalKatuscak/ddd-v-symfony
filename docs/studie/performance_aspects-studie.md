# Studie: Read modely, projekce a výkon

- **Kapitola:** `content/chapters/performance_aspects.md` (č. 16, kategorie Vzory, 1250 řádků)
- **Cesta:** /vykonnostni-aspekty
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| 16.01 Výkon v kontextu DDD | 21–48 | Tři mýty o výkonu DDD, tři scénáře, kdy výkon začne tlačit, Knuthovo pravidlo | 1× citace (Knuth, DOI) | Jediná citace v celé kapitole |
| 16.02 N+1 problém a lazy loading | 49–217 | Definice N+1, `EXTRA_LAZY`, fetch join v DQL, past fetch joinu s `setMaxResults()`, odkaz na `Paginator` | žádné | Nejlépe zpracovaná sekce, chybí `setEagerFetchBatchSize()` a keyset paging |
| 16.03 Agregát a výkon | 218–309 | Příznaky velkého agregátu, rozdělení `Order`/`OrderItem`, specializované repozitářní metody | žádné | Silný překryv s `aggregate_design.md:127` a `:681` |
| 16.04 Optimalizace read modelu (CQRS) | 310–460 | Query handler mimo doménové repozitáře, DQL `NEW` expression do DTO, `NativeQuery` + `ResultSetMapping` | žádné | Stejný anchor `#read-model-optimalizace` jako `cqrs.md:752` |
| 16.05 UUID vs. integer PK | 461–600 | Identita generovaná v doméně, fragmentace B-tree u UUID v4, UUID v7 / ULID, `symfony/uid` | žádné | Překryv s `aggregate_design.md:750` (strategie referencování) |
| 16.06 Identity Map a Unit of Work | 601–691 | Vzor Identity Map (PoEAA), dirty checking, batch + `clear()`, past detached entit | PoEAA jmenovitě, bez URL | Věcně v pořádku, chybí `toIterable()` a read-only entity |
| 16.07 Caching | 692–827 | Co cachovat, query vs. result cache, cache read modelu v query handleru, invalidace přes doménovou událost | žádné | Používá holé PSR-6, ignoruje Symfony cache tagy a Cache Contracts |
| 16.08 Bulk operace | 828–967 | DQL bulk UPDATE/DELETE obchází UoW, batch import s `flush()`/`clear()`, rozpad přes Messenger | žádné | Duplikuje batch ukázku z 16.06 (dva téměř totožné handlery) |
| 16.09 Provozní výkonové vzory | 968–1093 | Hot aggregates, anti-vzor pessimistic locku, partitioning, read replicy a pooling, snapshotting jako přehled | žádné | Nejnovější a nejhutnější část; hot aggregate duplikuje `aggregate_design.md:681` |
| 16.10 Profiling | 1094–1250 | Symfony Profiler, vlastní DBAL middleware pro počítání dotazů, Blackfire, postup interpretace | žádné | 100řádková ukázka middleware je nepoměrně dlouhá k obsahu |

Pořadí sekcí sleduje vrstvy technologie, ne rozhodovací cestu čtenáře. Kapitola začíná u N+1 (nejnižší úroveň), pokračuje hranicemi agregátu (doménová úroveň), vrátí se k read modelu, odbočí k primárním klíčům, znovu k ORM internals, k cache, k bulk operacím a teprve nakonec k profilingu – tedy k tomu, čím podle vlastního závěru (`:1229`) má čtenář začít. Závěr kapitoly to sám přiznává: „Pořadí, ve kterém je řešit, je opačné.“

Kapitola je z 85 % obecná optimalizace ORM a infrastruktury, ne kapitola o read modelech a projekcích. Titul „Read modely, projekce a výkon“ slibuje téma, které dostane jednu sekci (16.04) a v ní se nemluví o projekcích, ale o dotazech vracejících DTO. Slovo „projekce“ se v celé kapitole objevuje jen v okrajových zmínkách (`:1043` partitioning, `:1247` FAQ). Prostor naopak dostávají témata, která nemají k DDD těsný vztah (UUID vs. integer, Identity Map, DBAL middleware) nebo jsou detailně pokryta jinde (hot aggregate, snapshotting). Provozní sekce 16.09 je nejsilnější, ale zároveň nejméně podložená – opírá se o řadu konkrétních čísel bez jediného odkazu.

## 2. Kanonické zdroje k tématu

**Knuth a „premature optimization“.** Věta pochází z Knuthova článku *Structured Programming with go to Statements* (Computing Surveys, 1974) a kapitola ji cituje se správným DOI `10.1145/356635.356640` [1]. Celý výrok má podmínku, kterou kapitola vynechává: Knuth mluví o „small efficiencies, say about 97 % of the time“ a v témže odstavci trvá na tom, že zbylá 3 % se optimalizovat mají. Zkrácená citace se v komunitě používá jako záminka neměřit vůbec. Nuance stojí za jednu větu.

**CQRS.** Fowler vzor připisuje Gregu Youngovi a odvozuje název od Meyerova Command-Query Separation [2]. Podstatné je jeho varování, které kniha ani v kapitole 12, ani zde nezmiňuje v plné síle: *„for most systems CQRS adds risky complexity“* a *„the majority of cases I've run into have not been so good, with CQRS seen as a significant force for getting a software system into serious difficulties“*. Fowler doporučuje CQRS nasazovat na jednotlivý Bounded Context, nikdy plošně. Kapitola 16.04 naopak podává read model jako „hlavní páku pro výkonnostní problémy v DDD“ (`:311`) bez protiváhy.

**Reporting Database.** Fowlerův starší vzor [3] je přímý předchůdce read modelu: samostatná databáze s kopií provozních dat, přeuspořádaná pro dotazy, bez nutnosti normalizace („You don't need to normalize a reporting database, because it's read-only“). Fowler jej výslovně nabízí jako *levnější* alternativu k plnému CQRS. Pro kapitolu o výkonu je to důležitý mezistupeň, který v ní chybí – mezi „přidej index“ a „zaveď CQRS s projekcemi“ leží read replica a reporting databáze.

**Identity Map a Unit of Work.** Kapitola oba vzory správně připisuje Fowlerovi (*Patterns of Enterprise Application Architecture*, 2003) na `:602`. Bibliografický záznam v kapitole chybí, jen jméno v závorce.

**Agregát jako konzistenční hranice.** Evans (*Domain-Driven Design*, 2003) a Vernon (*Implementing Domain-Driven Design*, 2013, kapitola Effective Aggregate Design) jsou zdroj pravidla, že hranice agregátu vede přes invarianty, ne přes výkon. Kapitola totéž tvrdí na `:306`, ale bez atribuce – přitom kapitola 07 tytéž zdroje cituje.

**Large collection a hot aggregate.** Khononov (*Learning Domain-Driven Design*, 2021) je v knize použit jako zdroj pro tři strategie u large-collection problému (`aggregate_design.md:687`). Kapitola 16 tytéž strategie opakuje bez zdroje.

**Co v kanonických zdrojích k tomuto tématu naopak není.** Evans ani Vernon nepíší o ORM, indexech ani cache. Výkon je u nich vždy důsledek návrhu: Evans mluví o agregátu jako o jednotce, která se načítá a ukládá celá, Vernon přidává pravidlo malých agregátů a odkazuje výkonnostní problémy na eventual consistency mezi agregáty. Kapitola 16 se tedy z podstaty opírá o zdroje mimo DDD literaturu – dokumentaci Doctrine, Symfony a databází. To není chyba, ale znamená to, že bibliografie kapitoly musí být jiná než u ostatních kapitol knihy, a dnes je prakticky prázdná (jediná citace na `:46`).

## 3. Stav praxe a posuny

**Doctrine ORM 3 zrušila několik výkonnostních zkratek.** Podle UPGRADE.md [4] v 3.0 zmizely: klíčové slovo `PARTIAL` v DQL a `Doctrine\ORM\Query\AST\PartialObjectExpression`, metoda `getPartialReference()`, podpora `Query::HYDRATE_SIMPLEOBJECT`, `AbstractQuery::iterate()` (nahrazeno `toIterable()`), `EntityManager::merge()` a `UnitOfWork::merge()`. `EntityManager::clear()` s argumentem nyní vyhodí výjimku – selektivní čištění Identity Map skončilo. Zmizela i možnost předat entitu nebo pole entit do `flush()`. Lazy ghost proxy jsou v ORM 3 povinné a nekonfigurovatelné. Pozor: stránka `reference/partial-objects.html` na doctrine-project.org pod „current“ stále popisuje `PARTIAL` jako živou funkci [5] – dokumentace si v tomto bodě odporuje s UPGRADE.md a s vlastním číslem verze (3.6.8). Pro knihu je závazný UPGRADE.md.

**Eager fetch batching.** Dokumentace ORM [6] uvádí, že eager loading kolekcí se neprovádí JOINem, ale druhým dotazem, který načte kolekce pro několik entit najednou; dávka má výchozí velikost 100 a mění se přes `Configuration::setEagerFetchBatchSize()`. To je od ORM 2.14 reálná páka proti N+1, kterou kapitola nezná.

**Second Level Cache je pořád experimentální.** Dokumentace [7] doslova: *„The second level cache functionality is marked as experimental for now.“* Nabízí režimy `READ_ONLY`, `NONSTRICT_READ_WRITE` a `READ_WRITE`. Kapitola L2C vůbec nezmiňuje. To je obhajitelné rozhodnutí, ale mělo by v knize zaznít explicitně i s důvodem – jinak čtenář narazí na funkci, o které kniha mlčí, a nebude vědět proč.

**Read-only entity.** Doctrine doporučuje `#[ORM\Entity(readOnly: true)]` a `UnitOfWork::markReadOnly()` pro entity, které se v daném běhu nemění – přeskočí se pro ně dirty checking při flush [8]. Pro DDD je to zajímavé právě u referenčních dat (číselníky, katalog) načítaných spolu s agregátem.

**PHP 8.4 nativní lazy objects.** `ReflectionClass::newLazyGhost()` a `newLazyProxy()`, iniciátory, `ReflectionProperty::skipLazyInitialization()` [9]. Dokumentace ORM je uvádí přímo jako určené mimo jiné pro ORM. Praktický důsledek: proxy třídy generované do souborů přestávají být nutné, lazy inicializace přestává být „doctrine magie“ a stává se jazykovou vlastností. Kapitola psaná pro PHP 8.4 to nezmiňuje ani slovem.

**Keyset pagination.** Za poslední dekádu se posunulo doporučení pro stránkování velkých seznamů. `OFFSET` vyžaduje projít všechny předchozí řádky, takže odezva roste s číslem stránky, a při souběžných insertech se stránky posouvají (duplicitní a přeskočené záznamy) [10]. Seek/keyset metoda používá hodnoty poslední položky předchozí stránky jako predikát a potřebuje kompozitní index přes celý `ORDER BY` včetně unikátního sloupce. Pro read modely v DDD je to přímočaré: read model má vlastní tabulku, index se navrhne k ní.

**Projekce se staly provozním, ne návrhovým tématem.** Kniha o projekcích mluví ve dvou kapitolách: `cqrs.md:752` je popisuje jako denormalizované tabulky plněné událostmi a `event_sourcing.md:1021` řeší idempotenci, chybové stavy a rebuild. Obě kapitoly ale téma uzavírají na úrovni „jak to napsat“. Chybí druhá půlka, která patří do kapitoly o výkonu: jak dlouho rebuild trvá a co po tu dobu vidí uživatel, jak se měří odstup projekce od hlavy streamu, kdy se projekce vypne a nahradí dotazem, jak se zabrání tomu, aby rebuild zahltil produkční databázi. Kapitola 16 by tady měla mít vlastní vstup – dnes na projekce jen odkazuje.

**Materializované pohledy.** PostgreSQL je nabízí jako mezistupeň mezi dotazem a projekcí. Data se neaktualizují automaticky, obnovuje je `REFRESH MATERIALIZED VIEW`; varianta `CONCURRENTLY` neblokuje čtení, ale vyžaduje unikátní index nad pohledem [11]. Kniha je zmiňuje v tabulce v `cqrs.md:761`, kapitola 16 vůbec.

**Konec partial objects posunul čtení mimo ORM.** S odstraněním `PARTIAL` a `HYDRATE_SIMPLEOBJECT` [4] zbyly pro „načti jen část dat“ tři cesty: `getArrayResult()` / `getScalarResult()` [5][8], DQL `NEW` expression do DTO, a DBAL mimo ORM. Kapitola používá druhou a třetí (`:369`, `:757`), což je správně, ale nikde neříká, že první cesta – částečně hydratovaná entita – v ORM 3 zanikla. Čtenář, který zná ORM 2, po ní sáhne.

**Lazy loading přestává být záležitost generovaných proxy tříd.** ORM 3 udělala lazy ghost proxy povinné a odstranila jejich konfigurovatelnost [4]; PHP 8.4 zároveň přidal lazy objekty přímo do jazyka [9]. Praktický dopad na DDD kód: důvod, proč entita nesmí být `final` (kapitola jej uvádí komentářem na `:112` a `:576`), přestává s nativními lazy objekty platit obecně. Kdy přesně to Doctrine využije, je otázka pro ověření – ale komentář „ne final – Doctrine proxy z entity dědí“ je tvrzení vázané na konkrétní implementaci a kniha by měla říct, na kterou.

**Change tracking policies.** Doctrine mezi výkonnostními doporučeními uvádí volbu strategie sledování změn [8]. Výchozí `DEFERRED_IMPLICIT` porovnává při flush všechny spravované objekty; alternativy omezují rozsah dirty checkingu. Pro DDD je to relevantní právě u batch scénářů, které kapitola řeší jen přes `clear()`.

**Logging přes middleware.** `setSQLLogger()` je od DBAL 3.2 deprecated a DBAL 4 jej odstranil. Kapitola to na `:895` a `:1148` uvádí správně – a je v tom napřed před oficiální dokumentací batch processingu [12], která `$em->getConnection()->getConfiguration()->setSQLLogger(null)` stále doporučuje.

## 4. Symfony / PHP specifika

**Batch processing.** Oficiální doporučení [12]: dávkovat, experimentovat s velikostí dávky, výchozí návrh je 20. Kapitola používá 100 (`:643`) a 50 (`:892`) bez zdůvodnění. Dokumentace dále upozorňuje, že `toIterable()` **nelze kombinovat s fetch joinem kolekcí** – to je přesně past, do které čtenář po přečtení sekce 16.02 spadne. Klientská knihovna databáze navíc může výsledek bufferovat mimo paměť PHP, takže `memory_get_usage()` problém neukáže.

**Paginator.** `Doctrine\ORM\Tools\Pagination\Paginator` vydá ve výchozím režimu tři dotazy: `COUNT` s `DISTINCT`, poddotaz vybírající ID pro stránku a finální `WHERE IN` [13]. Přepínač `$fetchJoinCollection: false` sníží počet na dva a je namístě, když dotaz joinuje jen to-one asociace. Hint `HINT_ENABLE_DISTINCT` umožní `DISTINCT` vynechat. Agregace v dotazu se s `fetchJoinCollection: true` chovají nepředvídatelně. Kapitola `Paginator` zmiňuje jednou větou na `:214` bez těchto detailů.

**Symfony Cache.** Doporučené API jsou Cache Contracts (`CacheInterface::get()`/`delete()`), ne holé PSR-6 – kvůli kratšímu kódu a vestavěné ochraně proti stampede [14]. Kapitola na `:757` používá `CacheItemPoolInterface` s ručním `isHit()`/`set()`/`save()`, tedy variantu, kterou dokumentace popisuje jako určenou pro interoperabilitu s knihovnami třetích stran. Tagy: `TagAwareCacheInterface`, `$item->tag([...])`, `invalidateTags()`, adaptér `cache.adapter.redis_tag_aware`, konfigurace `tags: true`, CLI `cache:pool:invalidate-tags`. Ochrana proti stampede: zámky plus pravděpodobnostní předčasná expirace přes parametr `$beta` (funguje jen s Cache Contracts). Invalidace přes doménovou událost (16.07) volá `deleteItem()` na jeden klíč – s tagy by šlo zneplatnit celou skupinu odvozených read modelů jedním voláním.

**Více entity managerů.** Symfony generuje pro každý nakonfigurovaný manager službu `doctrine.orm.<název>_entity_manager` a autowiring funguje přes jméno parametru (`EntityManagerInterface $readEntityManager`) [15]. Identifikátor `doctrine.orm.read_entity_manager` v kapitole (`:1055`) je tedy správný, ale platí jen tehdy, když čtenář nakonfiguruje entity manager jménem `read`. Kapitola konfiguraci neukazuje. Dokumentace zároveň upozorňuje, že mezi entitami různých managerů nelze definovat asociace – pro čistý read model je to spíš výhoda.

**Symfony performance.** Oficiální doporučení [16]: OPcache s `opcache.preload` na `config/preload.php`, `opcache.memory_consumption=256`, `max_accelerated_files=32531`, `interned_strings_buffer=32`, `validate_timestamps=0` v produkci; `realpath_cache_size=4096K` a `realpath_cache_ttl=600`; `composer dump-autoload --no-dev --classmap-authoritative`; `.container.dumper.inline_factories: true`; `framework.enabled_locales`. Blackfire a komponenta Stopwatch jsou jmenované nástroje. Kapitola 16 nic z toho nezmiňuje – přitom jde o největší jednorázový výkonnostní zisk, který Symfony aplikace má, a je nezávislý na kvalitě doménového modelu.

**Index pod read modelem.** Read model je vlastní tabulka, takže index se navrhuje k dotazu, ne ke schématu domény. Prakticky to znamená kompozitní index přes celý `ORDER BY` včetně unikátního sloupce, pokud se stránkuje seek metodou [10], a unikátní index nad materializovaným pohledem, pokud se má obnovovat bez zámku (`REFRESH … CONCURRENTLY`) [11]. Obě podmínky jsou konkrétní a ověřitelné a v kapitole nejsou. Kapitola dnes o indexech mluví jen nepřímo, přes doporučení hledat `Seq Scan` ve výstupu `EXPLAIN ANALYZE` (`:1219`).

**Nástroje na měření.** Symfony jako profilovací nástroje jmenuje Blackfire a komponentu Stopwatch [16]. Kapitola 16 zmiňuje Blackfire (`:1209`) a Symfony Profiler, Stopwatch ne – přitom je to jediný z těchto nástrojů, který funguje i v produkci bez rozšíření a hodí se právě na měření doby projekce nebo doby zpracování jedné Messenger zprávy. Vlastní DBAL middleware kapitoly (`:1121`) plní roli, kterou v dev prostředí pokrývá `doctrine.dbal.logging` a panel Doctrine v Profileru; jeho hodnota je v integračních testech (aserce na počet dotazů), což kapitola zmíní jednou větou a pak stráví 80 řádků implementací.

**PgBouncer.** Tvrzení kapitoly na `:1068`–`:1071` sedí: v transaction pooling módu se prepared statements řeší nastavením `max_prepared_statements` (výchozí 200), PgBouncer přiděluje dotazům interní jména `PGBOUNCER_{unique_id}`, a konfigurační volba `prepared_statements` skutečně neexistuje [17]. Verzi 1.21 se v konfigurační referenci ověřit nepodařilo.

## 5. Sporné a chybně podávané body

**`fetch: 'EAGER'` jako řešení N+1.** FAQ na `:1243` uvádí `fetch: 'EAGER'` jako první ze tří úrovní řešení. Dokumentace ORM [6] říká, že eager loading kolekcí neprodukuje JOIN, ale druhý dotaz po dávkách, a explicitně doporučuje místo toho DQL fetch join: *„Make sure to use DQL to fetch-join all the parts of the object-graph that you need.“* U ManyToMany se navíc vydává jeden dotaz na každou kolekci. `EAGER` v mapování platí globálně, tedy i pro dotazy, které asociaci nepotřebují. Doporučení: pořadí v FAQ obrátit a `EAGER` uvést jako poslední volbu s uvedeným mechanismem.

**Napětí mezi 16.03 a 16.09.** Sekce 16.03 uzavírá pravidlem, že hranice agregátu vede přes invarianty a výkon se řeší jinde (`:306`). Předchozí odstavce téže sekce (`:280`–`:284`) přitom doporučují vyčlenit `OrderItem` do samostatného agregátu právě z výkonnostních důvodů, a sekce 16.09 uvádí re-design hranic jako první strategii proti hot aggregate. Obojí je obhajitelné, ale kapitola tu argumentuje proti sobě. Řešení: rozlišit „výkon jako *signál* špatně vedené hranice“ (legitimní) od „výkon jako *důvod* rozbít invariant“ (nelegitimní).

**Čísla bez metodiky.** `5 % konfliktů` a `50–80 % konfliktů` (`:986`–`:987`), `replikační lag typicky 10–100 ms` (`:1059`), `50 mil. řádků nebo 50 GB` jako práh pro partitioning (`:1041`), `snapshot po 50–100 eventech` (`:1082`), `dotazy nad 100 ms` (`:1218`), `výkonový rozdíl UUID vs. integer v řádu jednotek procent` (`:1249`). Žádné z těchto čísel nemá zdroj ani popis měření. U prahů, které jsou konvence (snapshot, 100 ms), stačí je označit jako pracovní heuristiku. U tvrzení o „jednotkách procent“ u UUID to nestačí – rozdíl závisí na enginu, šířce indexu a poměru zápisů; buď doložit, nebo přeformulovat kvalitativně.

**Callout „mýty a realita“ (`:30`–`:32`).** Tři tvrzení podaná jako fakta, žádné podložené. Zejména „správně navržené DDD s CQRS je srovnatelně rychlé“ je tvrzení o výkonu bez měření v kapitole, která hned o 15 řádků níž zakazuje optimalizovat bez měření. Doporučení: buď doložit, nebo přeformulovat na argument („doménové třídy nemají runtime režii navíc; režie pochází z persistence a ta se řeší nezávisle“).

**„Bez `clear()` roste čas superlineárně“ (`:834`).** Směr je správný (dirty checking prochází rostoucí množinu spravovaných objektů), ale „superlineárně“ je silné slovo bez měření. Navíc v ORM 3 `clear()` nepřijímá argument, takže vyčistí *všechno* – u bulk DQL UPDATE (`:842`) je bezpečnější rada spustit bulk operaci dřív, než se do EM cokoli načte, nebo použít samostatný entity manager, ne vyčistit rozdělaný stav.

**Duplicity napříč knihou.** Anchor `#read-model-optimalizace` existuje v `cqrs.md:752` i `performance_aspects.md:310` pod stejným názvem sekce. Hot aggregate má sekci v `aggregate_design.md:681` (čtyři strategie) i v `performance_aspects.md:975` (tři strategie) – seznamy se překrývají, ale nejsou totožné, což čtenáře mate. Large collection a `EXTRA_LAZY` jsou v `aggregate_design.md:684` i `performance_aspects.md:87`. Snapshotting je ve třech kapitolách (`aggregate_design.md:717`, `event_sourcing.md:1334`, `performance_aspects.md:1074`). Batch import s `flush()`/`clear()` je v téže kapitole dvakrát (`:643` a `:892`).

**Chybějící kontrapunkt k CQRS.** Fowler [2] varuje před CQRS jako před rizikem; kapitola 16 jej podává jako hlavní výkonnostní páku. Kapitola 12 varování obsahuje (12.04 Výzvy a omezení), ale kapitola 16 na ni v tomto bodě neodkazuje.

**Rozpory s kanonickým API knihy.** `CLAUDE.md` fixuje `Money` s vlastnostmi `amountInCents` a `currency` (výčtový typ `Currency`). DQL v `:374` a `:382` čte `o.totalAmount.amount` a `o.totalAmount.currency`, tedy jiný název pole. Na `:909` se objevuje továrna `Money::of($row['price'], $row['currency'])`, kterou kánon nezná, a hned dva různé podpisy `Product::create()` v téže kapitole (`:654` se skalární cenou, `:906` s `Money`). Kánon dále určuje továrnu `Order::place()`; kapitola používá veřejné konstruktory (`:127`, `:587`). Jde o ukázky zaměřené na mapování, ale čtenář je čte jako kód knihy.

**Asociace mezi dvěma agregáty v read modelu.** Ukázka na `:246` volá `$order->getCustomer()->getFullName()` a DQL na `:378` joinuje `JOIN o.customer c`, což předpokládá mapovanou Doctrine asociaci mezi agregáty `Order` a `Customer`. Kánon knihy říká, že agregáty se odkazují jen přes ID (`aggregate_design.md:299`). Na read straně je join dvou tabulek naprosto legitimní – ale pak se má dělat přes DBAL nad tabulkami, ne přes namapovanou asociaci doménových entit. Kapitola tento rozdíl nikde nevysloví, přestože je to jeden z důvodů, proč read model existuje.

**Result cache a její invalidace.** Sekce 16.07 na `:709` správně říká, že result cache je nutné invalidovat při změně dat, ale ukázku invalidace nedá – zatímco pro vlastní cache read modelu ukázku má. Result cache v Doctrine se invaliduje přes cache key / region, ne přes doménovou událost, a právě tady se obě cesty rozcházejí. Bez toho zůstává `:709` visící tvrzení.

**Dvě témata bez hranice.** Zadání studie se ptá, zda kapitola nemíchá read modely a obecnou optimalizaci. Míchá, a hranice není vyslovena nikde. Sekce 16.02, 16.05, 16.06, 16.08 a 16.10 se týkají write strany a ORM; sekce 16.04 a částečně 16.07 a 16.09 se týkají read strany. Přechod mezi oběma světy je jediná věta na `:308`: „odpovědí je read model (viz sekci CQRS), ne porušení doménové integrity.“ Čtenář tak nemá jak poznat, které rady platí pro agregát a které pro dotaz. Nejlevnější oprava není přesun textu, ale explicitní rozdělení kapitoly na dvě části s vlastními úvody: „výkon write strany“ (agregát, Unit of Work, batch) a „výkon read strany“ (dotaz, projekce, cache, replica).

**Dva „zlaté rytmy“ na začátku a na konci.** Kapitola otevírá warn calloutem „Nikdy neoptimalizujte naslepo“ (`:40`) a zavírá warn calloutem „neprovádějte předčasnou optimalizaci“ (`:1223`). Obsah obou je téměř identický. Jeden z nich je nadbytečný; pokud má zůstat rámování, závěrečný callout by měl místo opakování shrnout, co konkrétně z kapitoly měřit (počet dotazů na request, doba nejpomalejšího dotazu, odstup projekce, spotřeba paměti workeru).

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | celá kapitola | Titul slibuje „Read modely, projekce a výkon“, projekce nemají vlastní sekci a slovo se v textu prakticky nevyskytuje | Buď doplnit sekci o projekcích (build, rebuild, lag), nebo změnit titul na „Výkonnostní aspekty“ |
| G2 | nepodložené | `:30`–`:32` | Tři „mýty a realita“ bez jediného zdroje | Doložit, nebo přeformulovat na mechanismus místo tvrzení o rychlosti |
| G3 | mělké | `:44`–`:46` | Knuthova citace zkrácená o podmínku „97 % of the time“ | Doplnit druhou půlku výroku – Knuth optimalizaci nezakazuje, podmiňuje ji |
| G4 | chybí | 16.02 | `Configuration::setEagerFetchBatchSize()` a mechanismus eager fetch po dávkách | Doplnit jako třetí řešení N+1 vedle EXTRA_LAZY a fetch joinu |
| G5 | sporné | `:1243` (FAQ) | `fetch: 'EAGER'` uveden jako první řešení N+1 | Přesunout na poslední místo, vysvětlit, že u kolekcí nejde o JOIN |
| G6 | mělké | `:214` | `Paginator` zmíněn jednou větou | Rozvést: tři dotazy, `fetchJoinCollection`, `HINT_ENABLE_DISTINCT`, chování s agregacemi |
| G7 | chybí | 16.02 | Keyset/seek pagination pro velké seznamy; degradace `OFFSET` a drift stránek | Nová podsekce ~25 řádků s indexem pro read model |
| G8 | chybí | 16.06 / 16.08 | `toIterable()` – kapitola o batch zpracování jej vůbec nezmiňuje, včetně jeho nekompatibility s fetch joinem kolekcí | Doplnit do 16.08, provázat s varováním z 16.02 |
| G9 | zastaralé | 16.06 | Kapitola nikde neříká, že v ORM 3 `clear()` nepřijímá argument a `merge()` je pryč | Doplnit jednu větu do warn calloutu na `:684` |
| G10 | chybí | 16.06 / 16.08 | Read-only entity (`#[ORM\Entity(readOnly: true)]`, `markReadOnly()`) | Doplnit ~10 řádků do sekce o Unit of Work |
| G11 | chybí | celá kapitola | Doctrine Second Level Cache není zmíněna ani jako vědomě odmítnutá | Jeden odstavec v 16.07: co to je, že je stále experimentální, proč kniha sází na cache read modelu |
| G12 | chybí | celá kapitola | PHP 8.4 lazy objects (`newLazyGhost`, `newLazyProxy`) a jejich vztah k Doctrine proxy | ~15 řádků v 16.02 nebo 16.06 |
| G13 | zastaralé | `:757`–`:800` | Cache přes holé PSR-6; Symfony doporučuje Cache Contracts a nabízí ochranu proti stampede | Přepsat ukázku na `CacheInterface::get()` s callbackem |
| G14 | chybí | 16.07 | Cache tagy (`TagAwareCacheInterface`, `invalidateTags`, `cache.adapter.redis_tag_aware`) | Doplnit do sekce o invalidaci přes doménové události – tam mají největší přínos |
| G15 | chybí | 16.07 | Cache stampede a parametr `$beta` | Krátký odstavec, ~8 řádků |
| G16 | chybí | celá kapitola | Materializované pohledy jako mezistupeň mezi dotazem a projekcí (`REFRESH … CONCURRENTLY` + unikátní index) | ~15 řádků, provázat s tabulkou v `cqrs.md:761` |
| G17 | chybí | celá kapitola | Návrh indexů pro read model; `EXPLAIN ANALYZE` je zmíněn (`:1219`), ale index jako téma ne | Podsekce v části o read modelech |
| G18 | chybí | celá kapitola | Měření zpoždění projekcí (projection lag) jako provozní metrika – checkpoint vs. hlava streamu, alerting | ~20 řádků; téma sedí k 16.09 |
| G19 | chybí | celá kapitola | Rebuild projekce jako výkonnostní operace (doba rebuildu, blue/green tabulka, throttling) – `cqrs.md:900` ji popisuje jen z hlediska schématu | Doplnit provozní pohled do 16.09 |
| G20 | chybí | 16.10 | Symfony/PHP runtime optimalizace: OPcache preload, realpath cache, `--classmap-authoritative`, `inline_factories` | ~25 řádků; největší zisk s nejmenším zásahem do modelu |
| G21 | nadbytečné | `:1121`–`:1200` | 80řádková ukázka vlastního DBAL middleware s dvěma anonymními třídami | Zkrátit na kostru, nebo nahradit odkazem na `doctrine.dbal.logging` a Profiler |
| G22 | nadbytečné | `:892`–`:920` | Druhá batch-import ukázka téměř totožná s `:640`–`:670` | Sloučit, ponechat jednu |
| G23 | nadbytečné | 16.09 hot aggregate `:975`–`:1010` | Duplikuje `aggregate_design.md:681` jinými slovy a jiným počtem strategií | Ponechat v 07, zde zkrátit na odkaz + to, co 07 nemá (thrash diagram, čísla) |
| G24 | nepodložené | `:986`–`:987` | Míry konfliktů 5 % a 50–80 % bez zdroje | Označit jako ilustrativní řády, nebo doložit |
| G25 | nepodložené | `:1041`, `:1059`, `:1082`, `:1249` | Prahy 50 mil. řádků / 50 GB, lag 10–100 ms, snapshot po 50–100 eventech, rozdíl UUID vs. int „jednotky procent“ | Buď zdroj, nebo explicitní označení za pracovní heuristiku |
| G26 | sporné | `:280`–`:306` | Sekce zároveň doporučuje dělit agregát kvůli výkonu a zakazuje řešit výkon hranicí agregátu | Rozlišit výkon jako signál špatné hranice vs. jako důvod porušit invariant |
| G27 | mělké | `:842` | „Po DQL bulk operaci je nutné zavolat `clear()`“ | Doplnit, že `clear()` v ORM 3 zahodí vše; lepší je bulk pustit před načtením entit nebo v samostatném EM |
| G28 | mělké | `:1055` | `doctrine.orm.read_entity_manager` bez ukázky konfigurace | Doplnit YAML se dvěma entity managery a poznámku o autowiringu podle jména parametru |
| G29 | chybí | 16.04 | Chybí kritérium, kdy stačí read replica nebo reporting databáze a kdy až projekce | Doplnit žebříček eskalace: index → dotaz mimo ORM → replica → materializovaný pohled → projekce |
| G30 | mělké | `:311` | Read model podán jako hlavní páka bez Fowlerova varování o rizikovosti CQRS | Doplnit odkaz na `cqrs.md#challenges` a jednu větu s citací |
| G31 | nepodložené | `:602` | PoEAA zmíněna jménem, bez bibliografického záznamu | Doplnit do zdrojů kapitoly |
| G32 | chybí | 16.09 | Read replica: chybí zmínka o vzoru „read your writes“ na úrovni infrastruktury (sticky routing na primary po zápisu) | 5–8 řádků, doplnit k existujícímu odkazu na `cqrs#eventual-consistency` |
| G33 | sporné | `:374`, `:382`, `:909` | `o.totalAmount.amount` a `Money::of()` neodpovídají kanonickému `Money` (`amountInCents`, `Currency`) z `CLAUDE.md` | Sjednotit s kánonem knihy |
| G34 | sporné | `:654` vs. `:906` | Dva různé podpisy `Product::create()` v téže kapitole | Sjednotit na jeden |
| G35 | sporné | `:246`, `:378` | Mapovaná Doctrine asociace mezi agregáty `Order` a `Customer`, což odporuje pravidlu „reference jen přes ID“ (`aggregate_design.md:299`) | Buď join provést v DBAL nad tabulkami, nebo výslovně vysvětlit, proč read strana pravidlo neváže |
| G36 | sporné | `:127`, `:587` | Veřejné konstruktory `Order` místo kanonické továrny `Order::place()` | Sjednotit, případně v poznámce říct, že ukázka je zúžená na mapování |
| G37 | mělké | `:709` | Result cache: řečeno, že se musí invalidovat, ale nikde jak | Doplnit 5 řádků, nebo tvrzení vypustit |
| G38 | zastaralé | `:112`, `:576` | Komentář „ne final – Doctrine proxy z entity dědí“ bez vazby na verzi; ORM 3 má lazy ghost proxy povinně, PHP 8.4 má lazy objekty v jazyce | Ověřit, za jakých podmínek dnes omezení platí, a doplnit verzi |
| G39 | chybí | 16.06 / 16.08 | Change tracking policies jako výkonnostní volba (doporučuje je dokumentace ORM) | Zmínit jednou větou s odkazem |
| G40 | chybí | 16.08 | Provozní parametry Messenger workeru (limity paměti a času, počet workerů) u dlouhých importů | 5–8 řádků k existující sekci o Messengeru |
| G41 | nadbytečné | `:40` a `:1223` | Dva téměř identické warn callouty o předčasné optimalizaci | Závěrečný přepsat na konkrétní seznam metrik |
| G42 | chybí | celá kapitola | Není vyslovena hranice mezi optimalizací write strany a read strany | Rozdělit kapitolu na dvě části s vlastními úvody |
| G43 | mělké | `:1218` | „Dotazy nad 100 ms jsou kandidáty“ – bez rozlišení mezi jedním pomalým dotazem a stovkou rychlých | Doplnit druhé kritérium: celkový čas strávený v DB a počet dotazů na request |
| G44 | chybí | 16.10 | Symfony Stopwatch jako nástroj měření v produkci (Symfony jej mezi profilovacími nástroji jmenuje) | Jedna věta s odkazem |

## 7. Doporučení k přepisu

**P1-1 — Rozhodnout identitu kapitoly a sjednotit s titulem.** Kapitola se jmenuje „Read modely, projekce a výkon“, obsah je z převážné části obecná optimalizace Doctrine. Buď se doplní chybějící jádro (projekce, jejich rebuild, měření lagu, materializované pohledy, indexy read modelu – G1, G16, G17, G18, G19), nebo se titul, deck i katalogový popis v `src/Catalog/Chapters.php:40` změní na výkonnostní aspekty a projekce zůstanou v kapitolách 12 a 13. Rozsah: buď ~120 nových řádků, nebo úprava metadat a tří odkazujících míst.

**P1-2 — Opravit `fetch: 'EAGER'` ve FAQ.** Doporučení je v rozporu s dokumentací Doctrine: eager loading kolekcí nevydá JOIN, ale dávkovaný druhý dotaz, a v mapování platí globálně. Čtenář, který podle FAQ nastaví `EAGER` na `OneToMany`, si N+1 nevyřeší, jen ho přesune. Rozsah: přepis jedné odpovědi FAQ plus 5 řádků v 16.02 (G5, G4).

**P1-3 — Doplnit stav Doctrine ORM 3 tam, kde kapitola mlčí.** `clear()` bez argumentu, odstraněný `merge()`, `iterate()` nahrazený `toIterable()`, zmizelé partial objects a `HYDRATE_SIMPLEOBJECT`. Kniha cílí na ORM 3 a kapitola o výkonu je jediné místo, kde tyto změny mají praktický dopad. Bez toho kapitola stárne nejrychleji z celé knihy. Rozsah: ~20 řádků rozdělených do 16.06 a 16.08 (G8, G9, G27).

**P1-4 — Označit nebo doložit všechna konkrétní čísla.** Šest míst v kapitole uvádí prahy a procenta bez zdroje a bez metodiky, přičemž kapitola sama v úvodu i závěru zakazuje tvrdit cokoli o výkonu bez měření. Rozpor je viditelný. Buď zdroj, nebo přeformulování na „řádová heuristika“. Rozsah: oprava šesti odstavců (G24, G25, G2).

**P1-5 — Vyslovit hranici mezi write a read stranou.** Kapitola dnes bez varování střídá rady pro agregát a rady pro dotaz. To je pro čtenáře, který se v knize učí právě oddělovat obojí, matoucí. Rozdělení na dvě části s krátkými úvody stojí málo textu a je to zásah, který zároveň vyřeší část problému s titulem (P1-1). Rozsah: dva nové úvodní odstavce plus přesun pořadí sekcí (G42, G1).

**P2-1 — Přepsat sekci 16.07 na současné Symfony Cache API.** Cache Contracts místo ručního PSR-6, tagy pro invalidaci celé skupiny read modelů přes doménovou událost, zmínka o stampede a `$beta`. Invalidace přes tag je přesně to, co sekce potřebuje: jedna doménová událost typicky zneplatňuje víc odvozených pohledů, ne jeden klíč. Rozsah: přepis sekce 16.07, ~60 řádků (G13, G14, G15, G11).

**P2-2 — Doplnit žebříček eskalace před sáhnutím po read modelu.** Index → dotaz mimo ORM přes DBAL → read replica → materializovaný pohled → asynchronní projekce. Kapitola dnes skáče z N+1 rovnou na CQRS. Fowlerova Reporting Database a jeho varování před rizikovostí CQRS dávají tomuto žebříčku oporu. Rozsah: nová podsekce v 16.04, ~30 řádků (G29, G30).

**P2-3 — Vyřešit duplicity s kapitolami 07, 12 a 13.** Hot aggregate, large collection, snapshotting a optimalizace read modelů jsou v knize dvakrát až třikrát. Rozhodnout jedno kanonické místo pro každé téma a ze zbytku udělat odkaz. Zvlášť si všimnout stejného anchoru `#read-model-optimalizace` ve dvou kapitolách. Rozsah: úbytek ~80 řádků v kapitole 16 (G23, G22, G26).

**P2-4 — Doplnit keyset pagination a návrh indexů read modelu.** Read model má vlastní tabulku, takže index se navrhuje přímo k dotazu, který ji čte. Kapitola dnes o indexech mluví jen nepřímo přes `EXPLAIN ANALYZE`. `OFFSET` u hlubokých stránek je nejčastější reálná příčina pomalých seznamů v e-shopových administracích. Rozsah: nová podsekce ~35 řádků (G7, G17).

**P2-5 — Doplnit runtime optimalizace Symfony.** OPcache preload, realpath cache, `--classmap-authoritative`, `inline_factories`. Jediná optimalizace v kapitole, která nezasahuje do doménového modelu a má měřitelný plošný efekt. Patří do 16.10 vedle profilingu. Rozsah: ~25 řádků (G20).

**P2-6 — Sjednotit ukázky s kanonickým API knihy.** `Money`, `Product::create()` a konstruktory `Order` se v kapitole odchylují od konvencí v `CLAUDE.md` a místy si odporují i mezi sebou. Kapitola o výkonu je v knize pozdě, takže čtenář kanonické API už zná a odchylku vnímá jako chybu. Zvlášť ošetřit join `Order`–`Customer`: buď jej přesunout do DBAL, nebo výslovně říct, proč read strana pravidlo „reference přes ID“ neváže. Rozsah: oprava pěti ukázek (G33, G34, G35, G36).

**P3-1 — Zkrátit ukázku DBAL middleware.** 80 řádků infrastrukturního kódu s dvěma anonymními třídami v kapitole o DDD je nepoměr. Stačí kostra middleware a odkaz na `doctrine.dbal.logging`. Rozsah: úbytek ~60 řádků (G21).

**P3-2 — Doplnit PHP 8.4 lazy objects.** Vysvětlení, že lazy inicializace je nově vlastnost jazyka, ne generovaný kód, a co to znamená pro Doctrine proxy. Rozsah: ~15 řádků (G12).

**P3-3 — Doplnit ukázku konfigurace druhého entity manageru** k odkazu na `doctrine.orm.read_entity_manager`, aby identifikátor nevisel ve vzduchu. Rozsah: jeden YAML blok, ~15 řádků (G28).

**P3-4 — Přeuspořádat kapitolu podle rozhodovací cesty.** Dnešní pořadí sekcí jde od nejnižší technické vrstvy nahoru, zatímco závěr kapitoly (`:1229`) doporučuje opačný postup: měřit, oddělit čtení od zápisu, doladit hranice, teprve pak ORM detaily. Přesun profilingu na začátek by z kapitoly udělal postup místo katalogu. Rozsah: přesun dvou sekcí, bez nového textu (G1 částečně).

**P3-5 — Doplnit provozní parametry Messenger workeru** k sekci o rozpadu importu (limit paměti, limit času, restart workeru, počet konzumentů). Dnes sekce končí u odeslání zpráv a mlčí o tom, co se s nimi děje dál. Rozsah: ~8 řádků (G40).

## 8. Otevřené otázky pro autora

1. **Titul versus obsah.** Má kapitola 16 být kapitolou o read modelech a projekcích (a pak se z ní musí odstěhovat UUID, Identity Map a profiling), nebo kapitolou o výkonu (a pak se musí přejmenovat)? Toto rozhodnutí určuje všechno ostatní.
2. **Kde je kanonické místo pro hot aggregate a snapshotting?** Kandidáti: 07 (návrh agregátu), 13 (event sourcing), 16 (výkon). Bez rozhodnutí se duplicity vrátí.
3. **Kolik prostoru dát provozním tématům** (partitioning, connection pooling, read replicy)? Jsou dobře napsané, ale s DDD souvisejí volně a čtenáři knihy o DDD je nemusí od knihy čekat.
4. **Má kniha vůbec zmínit Second Level Cache?** Argument pro: čtenář na ni narazí a bude chtít vědět, proč o ní kniha mlčí. Argument proti: je experimentální a v DDD architektuře s read modely není potřeba.
5. **Nechat příklad s 1000 `OrderItem`?** Funguje didakticky, ale kniha jinde tvrdí, že hranice agregátu se nekreslí podle výkonu. Alternativa je příklad, kde je hranice špatně z doménových důvodů a výkon je jen příznak.
6. **Zavést v knize jednotný způsob značení neměřených čísel?** Například závorka „(řádová heuristika, ne naměřená hodnota)“. Týká se i dalších kapitol, ne jen 16.
7. **Kolik z kapitoly je vůbec o DDD?** Sekce 16.05 (UUID vs. integer) a 16.06 (Identity Map) jsou obecná znalost o Doctrine. Buď se vyostří jejich vazba na DDD (identita generovaná v doméně, agregát jako jednotka konzistence v UoW), nebo se zkrátí ve prospěch témat, která bez DDD nedávají smysl.
8. **Má kapitola obsahovat vlastní ukázku měření?** Například sada čísel z jednoho běhu nad reálným datasetem – s popsanou metodikou a s výslovným upozorněním, že jde o jedno měření na jednom stroji. Zvedlo by to důvěryhodnost kapitoly, ale zavazuje to k údržbě.
9. **Kam s runtime optimalizacemi Symfony** (OPcache preload, autoloader)? Sem, do kapitoly Implementace v Symfony, nebo do samostatného dodatku? Tématu se dnes nevěnuje žádná kapitola.

## 9. Bibliografie

Poznámka k metodě: rozpočet na `WebSearch` byl v této session vyčerpán (200/200), veškeré ověřování proto proběhlo přímým `WebFetch` cílených URL. Datum přístupu u všech webových zdrojů: 2026-09-03.

### Ověřené zdroje

`[1]` Knuth, D. E. — *Structured Programming with go to Statements*, Computing Surveys, 1974. https://dl.acm.org/doi/10.1145/356635.356640 — DOI ověřen jako existující záznam (odpovídá citaci v kapitole na `:46`); plný text nebyl načten, znění citace ověřeno nebylo.
`[2]` Fowler, M. — *CQRS*, 2011. https://martinfowler.com/bliki/CQRS.html — přímý fetch.
`[3]` Fowler, M. — *Reporting Database*. https://martinfowler.com/bliki/ReportingDatabase.html — přímý fetch.
`[4]` Doctrine — *ORM UPGRADE.md, větev 3.6.x*. https://raw.githubusercontent.com/doctrine/orm/3.6.x/UPGRADE.md — přímý fetch.
`[5]` Doctrine — *Partial Objects* (current). https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/partial-objects.html — přímý fetch; **stránka je v rozporu s [4]**, popisuje `PARTIAL` jako živou funkci.
`[6]` Doctrine — *Working with Objects*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/working-with-objects.html — přímý fetch (eager fetch po dávkách, `setEagerFetchBatchSize()`, doporučení fetch joinu).
`[7]` Doctrine — *Second Level Cache*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/second-level-cache.html — přímý fetch.
`[8]` Doctrine — *Improving Performance*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/improving-performance.html — přímý fetch.
`[9]` PHP Manual — *Lazy Objects*. https://www.php.net/manual/en/language.oop5.lazy-objects.php — přímý fetch.
`[10]` Winand, M. — *Use The Index, Luke: Fetching the Next Page*. https://use-the-index-luke.com/sql/partial-results/fetch-next-page — přímý fetch.
`[11]` PostgreSQL — *Materialized Views*. https://www.postgresql.org/docs/current/rules-materializedviews.html — přímý fetch.
`[12]` Doctrine — *Batch Processing*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/batch-processing.html — přímý fetch; poznámka: stránka stále doporučuje `setSQLLogger(null)`, který DBAL 4 odstranil.
`[13]` Doctrine — *Pagination*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html — přímý fetch.
`[14]` Symfony — *Cache*. https://symfony.com/doc/current/cache.html — přímý fetch (dokumentace uvádí verzi 8.1).
`[15]` Symfony — *Multiple Entity Managers*. https://symfony.com/doc/current/doctrine/multiple_entity_managers.html — přímý fetch.
`[16]` Symfony — *Performance*. https://symfony.com/doc/current/performance.html — přímý fetch.
`[17]` PgBouncer — *Configuration*. https://www.pgbouncer.org/config.html — přímý fetch (`max_prepared_statements`, výchozí 200, žádná volba `prepared_statements`).

Knižní zdroje bez URL (uvedeny pro atribuce v sekci 2, ověřeny z knihovny knihy, nikoli fetchem):
`[18]` Fowler, M. — *Patterns of Enterprise Application Architecture*, Addison-Wesley, 2003 (Identity Map, Unit of Work).
`[19]` Evans, E. — *Domain-Driven Design*, Addison-Wesley, 2003 (agregát jako konzistenční hranice).
`[20]` Vernon, V. — *Implementing Domain-Driven Design*, Addison-Wesley, 2013 (Effective Aggregate Design).
`[21]` Khononov, V. — *Learning Domain-Driven Design*, O'Reilly, 2021 (strategie u large-collection problému; kniha jej cituje v `aggregate_design.md:687`).

### Neověřené / nedohledané

- **Znění Knuthovy citace.** DOI odpovídá článku, plný text je za paywallem ACM. Formulaci „premature optimization is the root of all evil“ i podmínku „97 % of the time“ je třeba ověřit z tištěného zdroje nebo z veřejné kopie článku.
- **Rozpor v dokumentaci Doctrine ohledně `PARTIAL`.** UPGRADE.md [4] uvádí odstranění v 3.0, stránka `partial-objects.html` pod „current“ [5] funkci stále popisuje. Chce ověřit v kódu (`doctrine/orm`, grep na `PartialObjectExpression`) nebo v issue trackeru.
- **PgBouncer 1.21 jako verze, od které funguje `max_prepared_statements` v transaction poolingu.** Konfigurační reference [17] verzi neuvádí; ověřit v CHANGELOG projektu.
- **`Doctrine\DBAL\Connections\PrimaryReadReplicaConnection`.** Konfigurační stránka DBAL o wrapperu pro primary/replica nemluví. Existenci a chování (kdy se přepíná na primary, `ensureConnectedToPrimary()`) je třeba ověřit v dedikované dokumentaci nebo ve zdrojovém kódu. Pokud existuje, patří do sekce 16.09 jako alternativa k ručně konfigurovanému druhému entity manageru.
- **Verze ORM, ve které přibyl `setEagerFetchBatchSize()`.** Dokumentace [6] metodu popisuje, verzi neuvádí.
- **Blackfire Builds jako součást CI/CD** (`:1211`). Tvrzení nebylo ověřeno na blackfire.io; pokud zůstane, chce odkaz na dokumentaci produktu.
- **Konkrétní čísla replikačního lagu, konfliktů optimistického zámku a prahů pro partitioning.** Bez měřicí metodiky je nelze doložit; buď najít publikované měření, nebo je v kapitole označit jako řádový odhad.
- **Platnost omezení „entita nesmí být `final`“** v ORM 3 s povinnými lazy ghost proxy a s nativními lazy objekty PHP 8.4. Komentář se v kapitole objevuje dvakrát (`:112`, `:576`) a je vázaný na implementaci proxy, kterou ORM 3 změnila. Ověřit v dokumentaci ORM k proxy objektům nebo ve zdrojovém kódu `ProxyFactory`.
- **DBAL API pro streamované čtení read modelu** (`iterateAssociative` a příbuzné). Nebylo ověřeno fetchem; pokud se do kapitoly doplní protějšek `toIterable()` pro DBAL, je nutné podpis a chování ověřit v referenci DBAL.
- **Chování `Query::HINT_ENABLE_DISTINCT`** v ORM 3 – dokumentace stránkování [13] hint zmiňuje, ale neuvádí verzi ani přesnou konstantu; před použitím v knize ověřit ve zdrojovém kódu.
- **Doctrine ORM 3 a `EXTRA_LAZY`.** Dokumentace [8] strategii stále doporučuje, UPGRADE.md [4] ji mezi odstraněnými prvky neuvádí, takže platí. Přesto stojí za ověření, zda se s povinnými lazy ghost proxy nezměnilo chování `count()` a `slice()`.
