# Studie: Outbox Pattern

- **Kapitola:** `content/chapters/outbox_pattern.md` (č. 15, kategorie Vzory, 1538 řádků)
- **Cesta:** /outbox-pattern
- **Typ kapitoly:** hybridní (definiční jádro 15.01–15.02 + rozsáhlá implementační a provozní část)
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| frontmatter + deck | 1–37 | outbox řeší dual-write, inbox deduplikaci; „Podle Pata Hellanda"; slib „srovnání s alternativami" | Helland 2007 | atribuce Hellandovi je pro outbox nadsazená (viz 2); slíbené srovnání kapitola nedodá |
| 15.01 Dual-write | 39–150 | naivní handler, dva nesymetrické scénáře, callout proti 2PC/XA | Helland 2007, Richardson 2018 kap. 3, microservices.io | nejlepší psaná část kapitoly; argumentace proti XA je věcná |
| 15.02 Princip | 152–198 | čtyři fáze, at-least-once, exactly-once neexistuje | žádný přímý | pořadí zpráv tvrzeno silněji, než zdroje dovolují (ř. 172) |
| 15.03 Schéma tabulky | 200–373 | entita o 8 sloupcích, tabulka významů, povinný index, MySQL migrace | žádný | schéma nemá `aggregate_id` ani sloupec pro odloženy retry |
| 15.04 Handler | 375–596 | agregát → události → outbox v `wrapInTransaction` | žádný | kanonicky správné; chybí varianta bez ručního zápisu |
| 15.05 Relay – 2 varianty | 598–830 | polling worker, Debezium/CDC, Doctrine transport jako outbox | Debezium docs | varianty nepojmenovány kanonicky; Debezium část má faktické chyby |
| 15.06 Idempotent Inbox | 832–1000 | inbox tabulka, UNIQUE `(event_id, consumer)`, handler, HTTP idempotence | Stripe, IETF draft | dobře zpracované; chybí retence inbox tabulky |
| 15.07 Provozní aspekty | 1002–1348 | lag, kompakce, DLQ, monitoring, vacuum, partitioning, leader election, SKIP LOCKED, backpressure | žádný | 346 řádků, 22 % kapitoly; nejvíc nepodložených čísel |
| 15.08 Anti-vzory | 1350–1417 | 6 warn calloutů | žádný | poslední callout obsahuje chybné tvrzení o Symfony (ř. 1415) |
| 15.09 Migrace | 1419–1480 | 6 kroků + varování před nasazením | žádný | užitečné, věcné |
| 15.10 Shrnutí + FAQ | 1482–1538 | 7 bodů, 6 FAQ | Helland, Richardson, Kleppmann, microservices.io | FAQ čísla si odporují s tělem kapitoly |

Kapitola je implementační, ne definiční. Vlastní definice vzoru zabírá zhruba 45 řádků (15.02);
zbytek je kód, SQL a provoz. Nejvíc prostoru dostává provozní vrstva (15.07, 346 řádků) — to je
pro tuto knihu správná volba a odlišuje ji od anglických textů, které outbox obvykle končí u schématu
tabulky. Odbyté jsou naopak tři věci: kanonické názvosloví (kapitola nikde nepoužije jména
*Polling Publisher*, *Transaction Log Tailing*, *Idempotent Consumer*), garance pořadí, kterou
kapitola tvrdí silněji, než zdroje unesou, a alternativa automatického sběru událostí přes Doctrine
listenery — slovo „listener" se v celé kapitole nevyskytuje. Provozní část přitom stojí na sérii
konkrétních čísel (5k/s, 30k/s, 20k/s, 1000/s), která nemají zdroj a navzájem si odporují.

## 2. Kanonické zdroje k tématu

### Chris Richardson a microservices.io

Kanonická definice vzoru je Richardsonova [1]. Formulace *Solution* zní doslova: „The solution is
for the service that sends the message to first store the message in the database as part of the
transaction that updates the business entities. A separate process then sends the messages to the
message broker." Katalog uvádí jako benefity vyhnutí se 2PC, garanci „messages send if and only if
database transaction commits" a zachování pořadí; jako *drawback* jedinou položku — „Developers
might forget to publish messages after database updates"; jako *issue* — „Message relay may publish
duplicates; consumers must be idempotent". Jako alternativu katalog uvádí Event Sourcing. Kapitola
citaci uvádí správně (ř. 108–113).

Zásadní pro strukturu kapitoly: **relay má v katalogu dva pojmenované vzory**, ne dvě anonymní
„varianty".

- **Polling Publisher** [2] — „Publish messages by polling the database's outbox table."
  Benefit: „Works with any SQL database." Drawbacky: „Tricky to publish events in order",
  „Not all NoSQL databases support this pattern."
- **Transaction Log Tailing** [3] — „Tail the database transaction log and publish each
  message/event inserted into the *outbox* to the message broker." Implementace přes MySQL binlog,
  Postgres WAL, DynamoDB streams. Benefity: „No 2PC", „Guaranteed to be accurate". Drawbacky:
  závislost na konkrétní databázi a „Challenging to prevent duplicate message publishing".

Deduplikační protějšek má rovněž kanonické jméno: **Idempotent Consumer** [4]. Katalog doporučuje
tabulku `PROCESSED_MESSAGES` s kompozitním primárním klíčem `(subscriberId, messageID)` — přesně to,
co kapitola implementuje pod jménem „Idempotent Inbox". Starší kanonický název pro tutéž věc je
**Idempotent Receiver** z *Enterprise Integration Patterns* (Hohpe & Woolf, 2003) [5]: „Design a
receiver to be an *Idempotent Receiver* — one that can safely receive the same message multiple times."

### Pat Helland — co v paperu skutečně je

Kapitola v decku i v úvodu staví vzor „podle Pata Hellanda". Text paperu [6] to podpírá jen zčásti.
Slovo *outbox* se v paperu nevyskytuje; žádnou outbox tabulku Helland nepopisuje. Co v paperu je:

- Odmítnutí distribuovaných transakcí. 2PC „can easily block when nodes are unavailable"; závěrem
  paper říká, že „the fragility of these leads to unacceptable pressure on availability". Argument
  je tedy primárně **dostupnostní a křehkostní**, ne nákladový.
- At-least-once jako norma: „I am a big fan of 'exactly-once in-order' messaging but to provide it
  for durable data requires a long-lived programmatic abstraction similar to a TCP connection. […]
  Hence, we are considering cases dealing with 'at-least-once'."
- **Activities** — stav, který si entita pamatuje o svých protějšcích, včetně „knowledge about
  received messages". „To ensure idempotence […] the recipient entity is typically designed to
  remember that the message has been processed."

Helland je tedy ideovým předchůdcem **inboxu** (15.06), nikoli outboxu. Atribuce v `meta_description`
a v úvodu má být přesnější.

### Debezium Outbox Event Router

Aktuální stabilní dokumentace [7] popisuje SMT `io.debezium.transforms.outbox.EventRouter`
(ukázky v dokumentaci nesou `"version": "3.6.2.Final"`). Výchozí očekávané schéma outbox tabulky:

| sloupec | typ | role |
|---|---|---|
| `id` | uuid | ID události, v Kafka zprávě jako header; „You can use this ID, for example, to remove duplicate messages." |
| `aggregatetype` | varchar(255) | routovací klíč; výchozí `route.by.field` |
| `aggregateid` | varchar(255) | klíč Kafka zprávy; „This is important for maintaining correct order in Kafka partitions." |
| `type` | varchar(255) | typ události |
| `payload` | jsonb | tělo události |

Výchozí topic je `outbox.event.${routedByValue}`, kde `routedByValue` je hodnota
`aggregatetype`. Dokumentace uvádí příklad: hodnota `customers` → topic `outbox.event.customers`.
Další zjištění, která kapitola nereflektuje: „The SMT automatically filters out DELETE operations on
an outbox table" — kanonický Debezium model tedy řádek vloží a hned smaže, sloupec `status` v něm
neexistuje; a partici lze řídit přes `transforms.outbox.table.fields.additional.placement=partitionColumn:partition`.

Pro PostgreSQL [8]: výchozí `plugin.name` je `decoderbufs`, nikoli `pgoutput`. `pgoutput` je
„natively supported by PostgreSQL" od verze 10 a nevyžaduje instalaci serverového pluginu — proto
je v praxi častější volbou; vyžaduje `wal_level=logical` a pro uživatele privilegium `CREATE`
kvůli vytvoření publikace.

## 3. Stav praxe a posuny

Vzor se za posledních deset let posunul ze „šikovného triku" do standardního repertoáru. Tři posuny
stojí za zmínku.

**CDC přestalo být exotické.** Debezium vzniklo v roce 2016, Outbox Event Router přibyl v roce 2019
a v roce 2026 je stabilní verze 3.6 [7]. Zároveň to neposunulo hranici doporučení: pro aplikaci
s jednou databází a jedním brokerem zůstává polling levnější variantou a katalog to nijak nezastírá
— Polling Publisher má v [2] jako jediný benefit „Works with any SQL database", což je přesně to,
co typický Symfony projekt potřebuje.

**Kafka „exactly-once semantics" změnila slovník, ne fyziku.** Kafka od verze 0.11 nabízí idempotentní
producer a transakce; marketingově se to prodává jako exactly-once. Jde ale o transakční *zpracování*
uvnitř Kafky, ne o exactly-once doručení do libovolného cizího systému. Kapitola to říká správně
(ř. 976–987), jen si to podpírá nepřesnou zkratkou (viz 5).

**Deduplikace se přesunula do konzumenta.** Rozšíření termínu „inbox pattern" v komunitě je
pozorovatelné, ale kanonické katalogy ho pod tímto jménem nevedou — microservices.io má Idempotent
Consumer [4], EIP má Idempotent Receiver [5]. Kniha, která jinde důsledně používá kanonická jména
vzorů, by měla obě jména uvést a teprve pak zavést vlastní pracovní název.

## 4. Symfony / PHP specifika

### Doctrine transport a otázka, zda sám implementuje outbox

Toto je nejdůležitější technický bod kapitoly (ř. 788–830) a stojí za to ho rozebrat přesně.
Ověřeno čtením zdrojového kódu a dokumentace, ne odvozením:

1. **Symfony dokumentace slovo „outbox" nepoužívá.** V `messenger.rst` pro Symfony 8.0 [9] se řetězec
   „outbox" nevyskytuje ani jednou. Sekce „Transactional Messages: Handle New Messages After Handling
   is Done" je o `DispatchAfterCurrentBusMiddleware`, ne o outboxu. Označení „Doctrine transport jako
   outbox" je tedy čtení knihy, ne dokumentované tvrzení Symfony — a má být takto uvedeno.
2. **Atomicita ale reálně platí.** `Symfony\Bridge\Doctrine\Messenger\DoctrineTransactionMiddleware`
   [10] dělá `$entityManager->getConnection()->beginTransaction()`, pak `$stack->next()->handle(...)`,
   pak `$entityManager->flush()` a `commit()`. `Connection::send()` v `symfony/doctrine-messenger` [11]
   je prostý `INSERT` nad `$this->driverConnection` — vlastní izolovanou transakci neotevírá.
   `executeInsert()` sice volá `beginTransaction()`/`commit()`, ale DBAL v tomto případě jen inkrementuje
   nesting level, takže INSERT zůstane uvnitř vnější transakce. Zápis zprávy i flush agregátu proto
   commitnou společně.
3. **Podmínka je totéž DBAL spojení.** Platí jen pro transport nad stejným connection jako entity
   manager. `doctrine://jina_conn` atomicitu ruší; kapitola to zmiňuje, ale bez důrazu.
4. **`auto_setup` musí být vypnutý.** `Connection::executeStatement()` [11] při `TableNotFoundException`
   auto-setup provede jen tehdy, když `!$this->driverConnection->isTransactionActive()`; jinak výjimku
   přehodí dál. Uvnitř transakce tedy auto-setup nefunguje a tabulku je nutné vytvořit migrací.
   Dokumentace to nezávisle doporučuje pro produkci [9].
5. **`DispatchAfterCurrentBusStamp` atomicitu ruší.** Docblock middlewaru [12] to říká doslova:
   „using this middleware before the DoctrineTransactionMiddleware means sub-dispatched messages with
   a DispatchAfterCurrentBus stamp would be handled after the Doctrine transaction has been committed."
   Dokumentace [9] přitom nabádá `dispatch_after_current_bus` registrovat *před* `doctrine_transaction`.
   Kdo tedy zkombinuje doporučovaný stamp s „doctrine transport jako outbox", vrátí si dual-write.
   Kapitola tuto past nezmiňuje vůbec.

### `wrapInTransaction` a uzavřený EntityManager

Dokumentace ORM 3 [13] popisuje rozdíl proti `Connection#transactional()`: „the latter abstraction
flushes the EntityManager prior to transaction commit and in case of an exception the EntityManager
gets closed in addition to the transaction rollback." Následek pro relay: v `OutboxDispatchCommand`
(ř. 616–706) je `markFailed()` volán v `catch` bloku nad týmž EntityManagerem. Pokud výjimku vyhodila
Doctrine, je EM zavřený a `markFailed()` selže — worker skončí a smyčka `while` se nedokončí.
Dokumentace k tomu dodává: „If you intend to start another unit of work after an exception has occurred
you should do that with a new EntityManager." V dlouho běžícím workeru je to reálný failure mode.

### Doctrine listenery jako alternativa k ručnímu zápisu

Kapitola ukazuje jedinou cestu — handler explicitně čte `releaseEvents()` a zapisuje řádky. Druhá
běžná cesta je automatický sběr v `onFlush`/`postFlush` listeneru. Dokumentace [14][15] pro ni dává
tvrdé mantinely, které v kapitole chybí:

- „Making changes to entities and calling `EntityManager::flush()` from within event handlers
  dispatched by `EntityManager::flush()` itself is strongly discouraged, and might be deprecated and
  eventually prevented in the future."
- `postFlush`: „`EntityManager::flush()` can **NOT** be called safely inside its listeners."
- V `onFlush` nestačí `persist()`; je nutné volat `$unitOfWork->computeChangeSet($classMetadata, $entity)`.
- `postPersist` běží „after database insert, before transaction commit" — pro zápis outbox řádku je
  to tedy použitelné z hlediska transakce, ale „there may still be collection and/or 'extra' updates
  pending. The database may not yet be completely in sync."

Praktický závěr pro kapitolu: automatický sběr přes `onFlush` je proveditelný, ale je to
netriviální kód s `computeChangeSet`, a kniha by ho měla buď ukázat, nebo explicitně odmítnout
s odůvodněním. Mlčení je horší než obojí.

### Drobnost, která je v pořádku

Migrace mapuje `#[ORM\Column(type: 'uuid')]` na `BINARY(16)`. To odpovídá: `AbstractUidType::getSQLDeclaration()`
[16] vrací `getGuidTypeDeclarationSQL()` jen na platformách s nativním GUID typem (PostgreSQL),
jinak `BINARY(16)` fixed. MySQL varianta v kapitole je správná.

## 5. Sporné a chybně podávané body

**Pořadí zpráv.** Kapitola tvrdí (ř. 172), že `ORDER BY occurred_at` „zachová kauzální pořadí uvnitř
jedné DB". microservices.io u Polling Publisheru naopak explicitně uvádí drawback „Tricky to publish
events in order" [2]. Konkrétní důvody, proč je tvrzení kapitoly příliš silné: `occurred_at` vzniká
v PHP (`new \DateTimeImmutable()`), takže napříč aplikačními instancemi podléhá clock skew; při shodné
hodnotě není pořadí definované; a relay publikuje řádek po řádku, takže selhání uprostřed batche
pustí pozdější událost před dřívější. Doporučení: tvrzení oslabit na „best-effort FIFO uvnitř jedné
DB" a připsat, že spolehlivé pořadí per-agregát vyžaduje klíč agregátu (v Kafce partition key,
viz `aggregateid` v [7]).

**Debezium bez změny aplikačního kódu.** Kapitola tvrdí (ř. 779–782), že SMT routuje podle sloupce
`message_type`, že topic je `outbox.event.OrderPlaced` a že „žádný kód na aplikační straně se proti
variantě A nemění". Podle [7] je výchozí routovací sloupec `aggregatetype`, výchozí topic
`outbox.event.<aggregatetype>` a schéma tabulky v kapitole nemá ani `aggregatetype`, ani `aggregateid`.
Debezium navíc DELETE operace filtruje, takže se `status` sloupcem nepracuje. Přechod na variantu B
tedy vyžaduje buď změnu schématu, nebo přemapování všech `table.field.event.*` a `route.by.field`
voleb. Tvrzení „nic se nemění" je nepravdivé v obou směrech.

**„Auto-commit per dispatch".** Ř. 1415: „Výchozí bus chování v Symfony 8 je *auto-commit per dispatch*,
ne per handler". Takový koncept Messenger nemá — bez `doctrine_transaction` middlewaru neexistuje
žádná transakce navíc a platí běžný autocommit režim DBAL spojení, ve kterém `flush()` obalí svoje
zápisy implicitní transakcí. Věta má být přepsána nebo smazána.

**Zdroje u nemožnosti exactly-once.** Ř. 979–981 se odvolávají na „Two Generals' Problem" a „papery
Lamporta a Lynchové". FLP je Fischer–Lynch–Paterson (1985), Lamport je autorem Byzantine Generals
a Paxosu — spojení jmen je zavádějící a bez konkrétní citace. Two Generals se navíc týká shody nad
ztrátovým kanálem, FLP deterministického konsenzu v asynchronním systému; ani jeden přímo nedokazuje
tvrzení o exactly-once doručení. Buď doplnit přesné citace, nebo jména vypustit a nechat věcné tvrzení.

**Helland a 2PC.** Ř. 130–132 shrnují Hellanda jako odmítnutí 2PC „kvůli nákladům a křehkosti".
Paper argumentuje především blokováním a tlakem na dostupnost [6]. Rozdíl je malý, ale u přímé
atribuce se hodí trefit se přesně.

**Kafka a XA.** Ř. 122: „Kafka nemá XA vůbec". Formálně platí, ale čtenář, který zná Kafka transakce
od verze 0.11, může větu vnímat jako chybu. Doporučení: rozlišit „nepodporuje XA / distribuovaný
2PC s cizími resource managery" od „nemá transakce".

**Výkonová čísla.** Kapitola uvádí čtyři navzájem neslučitelné údaje o propustnosti jednoho workeru
a celku: ~5k events/s (ř. 744, 768, 1230), 30k events/s (ř. 745, 768), 20k events/s (ř. 1326)
a 1 000 events/s ve FAQ (ř. 1531). Žádné z nich nemá zdroj ani popis měřicí konfigurace. Pro PHP
proces, který na každou zprávu dělá serializaci, publish s ACK a UPDATE řádku, je 5 000 zpráv/s
optimistické; FAQ hodnota 1 000/s je realističtější řád.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | nepodložené | `outbox_pattern.md:6,33` | atribuce vzoru Hellandovi; v paperu se slovo outbox nevyskytuje a popisuje se jen inbox („activities") | přeformulovat: Helland dodává rámec (odmítnutí 2PC, at-least-once, idempotentní příjemce), definici vzoru dodává Richardson |
| G2 | chybí | `outbox_pattern.md:598,605,749` | dvě varianty relay nejsou pojmenovány kanonicky (Polling Publisher, Transaction Log Tailing) | doplnit jména a citace [2][3] do nadpisů/úvodu sekce |
| G3 | chybí | `outbox_pattern.md:832` | „Idempotent Inbox" je pracovní název; kanonicky Idempotent Consumer [4] a Idempotent Receiver [5] | uvést obě kanonická jména při zavedení pojmu |
| G4 | sporné | `outbox_pattern.md:172` | tvrzení o zachování kauzálního pořadí přes `ORDER BY occurred_at` | oslabit; doplnit clock skew, ties, selhání uprostřed batche |
| G5 | zastaralé / chybné | `outbox_pattern.md:759,779–782` | Debezium routuje podle `aggregatetype`, ne `message_type`; topic je `outbox.event.<aggregatetype>`; „nic se nemění" neplatí | opravit podle [7], doplnit mapování schématu kapitoly na očekávané sloupce |
| G6 | chybí | `outbox_pattern.md:200–295` | schéma nemá `aggregate_id` / `aggregate_type` — přitom ř. 1330 čtenáře posílá „partition outbox na `aggregate_id`" | přidat sloupce do schématu, jinak dvě sekce kapitoly si odporují |
| G7 | chybí | `outbox_pattern.md:788–830` | past `DispatchAfterCurrentBusStamp` + doctrine transport = dual-write zpět | doplnit warn callout s citací docblocku [12] a doporučení dokumentace [9] |
| G8 | mělké | `outbox_pattern.md:788–830` | není řečeno, že `auto_setup` uvnitř transakce nefunguje a tabulku musí vytvořit migrace | doplnit dvě věty s odkazem na chování `executeStatement()` |
| G9 | chybí | celá kapitola | Doctrine listenery (`onFlush`/`postFlush`) jako alternativa k explicitnímu zápisu do outboxu; slovo „listener" v kapitole není | nová podsekce s mantinely z [14][15] a jasným doporučením |
| G10 | chybí | `outbox_pattern.md:1108–1120` | `markFailed()` vrací řádek do `pending` bez odkladu — jedovatá zpráva blokuje čelo batche v těsné smyčce | doplnit sloupec `available_at` / `next_attempt_at` a exponenciální backoff do schématu i do relay |
| G11 | chybí | `outbox_pattern.md:616–706` | po Doctrine výjimce je EM zavřený, `markFailed()` v `catch` selže a worker skončí | doplnit varování + `resetManager()` nebo oddělené spojení pro stavové updaty |
| G12 | chybí | `outbox_pattern.md:832–1000` | inbox tabulka roste bez omezení; kapitola má kompakci pro outbox, pro inbox nic | doplnit retenci inboxu a vazbu na maximální dobu, po kterou broker může zprávu doručit |
| G13 | nepodložené | `outbox_pattern.md:744,745,768,1230,1326,1531` | čtyři neslučitelná výkonová čísla bez zdroje | sjednotit na jeden řád, popsat konfiguraci, nebo čísla nahradit kvalitativním popisem |
| G14 | chybně podané | `outbox_pattern.md:1415` | „auto-commit per dispatch" — v Messengeru takový koncept není | přepsat na popis skutečného chování (bez middlewaru žádná transakce navíc) |
| G15 | nepodložené | `outbox_pattern.md:979–981` | „papery Lamporta a Lynchové" u FLP | doplnit přesné citace, nebo jména vypustit |
| G16 | chybí | `outbox_pattern.md:37,1482–1517` | úvod slibuje „srovnání s alternativami", shrnutí ho nedodá | doplnit krátké srovnání: event sourcing [1], listen-to-yourself, sync volání, 2PC |
| G17 | mělké | `outbox_pattern.md:171–176` | chybí gap problém / ordering hole, který kapitola 13.08 popisuje ve vlastním calloutu a přitom na 15.05 odkazuje jako na kanonický popis relay | doplnit odstavec: filtr `status='pending'` gap problém eliminuje, checkpointová varianta ne |
| G18 | chybně podané | `outbox_pattern.md:122,130–132` | „Kafka nemá XA vůbec"; Helland shrnut jako nákladový argument | zpřesnit obojí |
| G19 | chybí | celá kapitola | žádný test; přitom ř. 366–373 doporučují regresní test na index a kniha má kapitolu o testování | doplnit jeden test outbox atomicity (rollback → nula řádků) s odkazem na /testovani-ddd |
| G20 | mělké | `outbox_pattern.md:749–786` | tabulka srovnání A/B neuvádí provozní cenu CDC: replikační slot, který při zaseknutém konektoru drží WAL a zaplní disk primární databáze | doplnit řádek do tabulky nebo warn callout |

## 7. Doporučení k přepisu

**P1-1 — Opravit Debezium sekci podle aktuální dokumentace.**
Routovací sloupec, název topicu i tvrzení „na aplikační straně se nic nemění" jsou chybné. Čtenář,
který podle kapitoly nasadí Outbox Event Router, dostane konektor, který nenajde `aggregatetype`
a spadne. Zároveň je to jediné místo, kde kapitola říká něco konkrétního o CDC, takže chyba
diskredituje celou variantu B. Rozsah: `přepis ř. 749–786`, cca 40 řádků.

**P1-2 — Doplnit past `DispatchAfterCurrentBusStamp` k sekci o Doctrine transportu.**
Sekce správně tvrdí, že doctrine transport plus `doctrine_transaction` řeší dual-write. Neřekne ale,
že běžně doporučovaný stamp tuto garanci ruší, protože odloží odeslání až za commit. Bez toho
kapitola dává čtenáři konfiguraci, kterou může jedním doporučením z jiného blogu tiše rozbít.
Rozsah: `warn callout ~15 řádků` v sekci 15.05.

**P1-3 — Sjednotit výkonová čísla nebo je odstranit.**
Kapitola tvrdí 5k, 20k, 30k i 1k events/s a čtenář nemá jak poznat, které platí. Pro provozní část,
která na těchto číslech staví doporučení (kdy nasadit SKIP LOCKED, kdy partitioning), je to
strukturální vada. Rozsah: `oprava šesti míst`, případně `přepis dvou odstavců v 15.07`.

**P1-4 — Přidat `aggregate_id`/`aggregate_type` do schématu outbox tabulky.**
Bez nich si sekce 15.03 a 15.07 odporují (ř. 1330 odkazuje na neexistující sloupec), Debezium
varianta nemá čím klíčovat Kafka partitions a per-agregátní pořadí nelze zajistit vůbec.
Rozsah: `úprava entity, tabulky sloupců a migrace v 15.03`, cca 15 řádků.

**P2-1 — Doplnit kanonická jména vzorů.**
Polling Publisher, Transaction Log Tailing, Idempotent Consumer, Idempotent Receiver. Kniha jinde
důsledně jména vzorů uvádí; tady je nahrazuje vlastními popisky („varianta A/B", „Idempotent Inbox").
Čtenář, který si o vzoru bude číst dál, potřebuje ta jména znát. Rozsah: `oprava čtyř nadpisů
a čtyř vět`.

**P2-2 — Nová podsekce: Doctrine listener vs. explicitní zápis.**
Automatický sběr událostí v `onFlush` je druhá běžná realizace vzoru; kapitola ji ignoruje.
Podsekce má ukázat, proč je lákavá (nelze zapomenout — přímá odpověď na jediný drawback
v katalogu [1]), a co ji komplikuje: nutnost `computeChangeSet()`, zákaz `flush()` v listenerech,
horší testovatelnost. Rozsah: `nová sekce ~50 řádků` mezi 15.04 a 15.05.

**P2-3 — Doplnit retry backoff a `available_at` do schématu a relay.**
Současný `markFailed()` vrací řádek rovnou do `pending`, takže jedovatá zpráva se v batchi
opakuje ve smyčce každých 100 ms až do pátého pokusu a blokuje řádky za sebou. Sloupec
`available_at` a exponenciální backoff je standardní řešení a Messenger sám ho v doctrine
transportu používá. Rozsah: `úprava entity a OutboxDispatchCommand`, cca 25 řádků.

**P2-4 — Opravit atribuci Hellandovi a zdroje u exactly-once.**
Deck, úvod a callout na ř. 976–987. Kniha má konvenci ověřených citací; tady jsou tři místa,
kde se atribuce nedá obhájit textem zdroje. Rozsah: `oprava čtyř vět`.

**P2-5 — Doplnit varování o zavřeném EntityManageru v relay smyčce.**
Ukázkový worker po první Doctrine výjimce přestane fungovat a přitom vypadá, že chybu ošetřuje.
Čtenář to nasadí a zjistí až v produkci. Rozsah: `oprava catch bloku + warn callout ~12 řádků`.

**P3-1 — Doplnit retenci inbox tabulky.** Symetrická k 15.07 kompakci; jedna podsekce, cca 15 řádků.

**P3-2 — Doplnit srovnání s alternativami do shrnutí.** Slib z ř. 37 je nesplněný. Tabulka
outbox / event sourcing / listen-to-yourself / synchronní volání / 2PC, cca 15 řádků.

**P3-3 — Doplnit jeden test.** Handler zabalený do transakce, vyhozená výjimka, ověření nuly řádků
v `outbox` i v tabulce agregátu. Vazba na kapitolu 17. Cca 30 řádků.

**P3-4 — Doplnit odstavec o gap problému.** Kapitola 13.08 na 15.05 odkazuje jako na kanonický
popis relay, ale 15.05 gap problém nezná. Stačí vysvětlit, proč filtr `status='pending'` problém
nemá a checkpointová varianta ano. Cca 10 řádků.

## 8. Otevřené otázky pro autora

1. **Kolik prostoru má dostat provozní část?** 15.07 má 346 řádků, tedy víc než definice, schéma
   a handler dohromady. Partitioning, autovacuum tuning a leader election jsou spíš DBA/SRE témata
   než DDD. Otázka je, zda je nechat, zkrátit na polovinu, nebo přesunout část do kapitoly 16
   (Read modely, projekce a výkon).
2. **Má kapitola držet obě realizace — vlastní outbox tabulku i doctrine transport?**
   Sekce o doctrine transportu (43 řádků) říká, že pro menší systémy je to „nejjednodušší správná
   volba", zatímco zbylých 1400 řádků učí vlastní tabulku. Buď to má být otevřeno hned na začátku
   jako rozcestník, nebo má být doctrine transport odsunut do poznámky.
3. **Zůstává Debezium jako plnohodnotná varianta B, nebo se z ní stane odkaz?** Poctivé pokrytí
   CDC (schéma pro SMT, replikační slot, WAL retention, Strimzi) je dalších ~80 řádků. Alternativa:
   dvě strany trade-offů a odkaz na dokumentaci.
4. **Jaký režim relay má kniha doporučit jako výchozí?** Kapitola nabízí singleton, leader election
   i SKIP LOCKED, ale jednoznačné doporučení chybí. SKIP LOCKED je dnes prakticky bez nevýhod
   proti singletonu, kromě ztráty globálního pořadí.
5. **Ponechat `attempts`/`status` model, nebo přejít na insert-and-delete?** Debezium model
   (řádek vloží a smaže) je jednodušší, nepotřebuje kompakci ani UPDATE a je kompatibilní s CDC.
   Kapitola volí stavový model kvůli auditovatelnosti — rozhodnutí je obhajitelné, ale mělo by být
   v textu vyslovené jako volba, ne jako jediná možnost.
6. **Jak hluboko jít do idempotence?** Kapitola má inbox (15.06), HTTP `Idempotency-Key` (15.06)
   a odkaz na kapitolu 20. Hrozí trojí duplicita napříč knihou.

## 9. Bibliografie

### Ověřené zdroje

Všechny níže uvedené webové zdroje byly získány **přímým fetchem URL** (WebFetch nebo curl).
Rozpočet na WebSearch byl v této session vyčerpán, žádný zdroj tedy nebyl nalezen hledáním.

`[1]` Richardson, C. — *Pattern: Transactional outbox*, microservices.io. Fetch 2026-09-03.
https://microservices.io/patterns/data/transactional-outbox.html

`[2]` Richardson, C. — *Pattern: Polling publisher*, microservices.io. Fetch 2026-09-03.
https://microservices.io/patterns/data/polling-publisher.html

`[3]` Richardson, C. — *Pattern: Transaction log tailing*, microservices.io. Fetch 2026-09-03.
https://microservices.io/patterns/data/transaction-log-tailing.html

`[4]` Richardson, C. — *Pattern: Idempotent Consumer*, microservices.io. Fetch 2026-09-03.
https://microservices.io/patterns/communication-style/idempotent-consumer.html

`[5]` Hohpe, G.; Woolf, B. — *Idempotent Receiver*, Enterprise Integration Patterns, 2003.
Fetch 2026-09-03. https://www.enterpriseintegrationpatterns.com/patterns/messaging/IdempotentReceiver.html

`[6]` Helland, P. — *Life beyond Distributed Transactions: an Apostate's Opinion*, CIDR 2007.
PDF stažen a text extrahován 2026-09-03. https://www.cidrdb.org/cidr2007/papers/cidr07p15.pdf

`[7]` Debezium — *Outbox Event Router* (stable, ukázky verze 3.6.2.Final). Fetch přes curl
2026-09-03 (WebFetch vrací 403).
https://debezium.io/documentation/reference/stable/transformations/outbox-event-router.html

`[8]` Debezium — *Debezium connector for PostgreSQL* (stable). Fetch přes curl 2026-09-03.
https://debezium.io/documentation/reference/stable/connectors/postgresql.html

`[9]` Symfony — *Messenger: Sync & Queued Message Handling*, dokumentace pro Symfony 8.0.
Zdrojový `messenger.rst` stažen 2026-09-03; ověřeno, že řetězec „outbox" se v něm nevyskytuje.
https://raw.githubusercontent.com/symfony/symfony-docs/refs/heads/8.0/messenger.rst
(publikovaná verze: https://symfony.com/doc/current/messenger.html)

`[10]` Symfony — `Symfony\Bridge\Doctrine\Messenger\DoctrineTransactionMiddleware`, zdrojový kód.
Fetch 2026-09-03. https://raw.githubusercontent.com/symfony/doctrine-bridge/7.3/Messenger/DoctrineTransactionMiddleware.php

`[11]` Symfony — `Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection`, zdrojový kód
(`send()`, `executeInsert()`, `executeStatement()`). Fetch 2026-09-03.
https://raw.githubusercontent.com/symfony/doctrine-messenger/7.3/Transport/Connection.php

`[12]` Symfony — `Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware`,
zdrojový kód včetně docblocku. Fetch 2026-09-03.
https://raw.githubusercontent.com/symfony/messenger/7.3/Middleware/DispatchAfterCurrentBusMiddleware.php

`[13]` Doctrine ORM — *Transactions and Concurrency* (aktuální dokumentace, ORM 3.6).
Fetch 2026-09-03. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/transactions-and-concurrency.html

`[14]` Doctrine ORM — *Events* (aktuální dokumentace). Fetch 2026-09-03.
https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/events.html

`[15]` Symfony — *Doctrine Events*. Fetch 2026-09-03. https://symfony.com/doc/current/doctrine/events.html

`[16]` Symfony — `Symfony\Bridge\Doctrine\Types\AbstractUidType` a `UuidType`, zdrojový kód.
Fetch 2026-09-03. https://raw.githubusercontent.com/symfony/doctrine-bridge/7.3/Types/AbstractUidType.php

`[17]` Confluent — *EventRouter SMT* (`io.debezium.transforms.outbox.EventRouter`). Fetch 2026-09-03.
https://docs.confluent.io/kafka-connectors/transforms/current/eventrouter.html

### Neověřené / nedohledané

- **Richardson, C. — *Microservices Patterns*, Manning (2018), kapitola 3.** Kapitola citaci uvádí
  na ř. 110–112 a ve shrnutí. Obsah knihy nebyl ověřen; kniha není online dostupná a WebSearch
  nebyl k dispozici. Doporučuji ověřit ručně, zda je transakční messaging skutečně v kapitole 3
  a zda shrnutí správně odkazuje i na kapitolu 4.
- **Kleppmann, M. — *Designing Data-Intensive Applications*, O'Reilly (2017), kapitola 11.**
  Uvedeno v doporučené literatuře (ř. 1520). Neověřeno, zda kapitola 11 outbox skutečně zmiňuje;
  Kleppmann řeší téma spíš pod hlavičkou change data capture a exactly-once semantics.
- **Vernon, V. — *Implementing Domain-Driven Design* (2013), kapitola o Domain Events.**
  IDDD popisuje mechaniku „event store + forwarder", která je funkčně outboxem a je starší než
  Richardsonova formulace. Kapitola ji nezmiňuje. Historickou prioritu se nepodařilo ověřit
  bez přístupu ke knize — stojí za dohledání, protože by šlo o relevantní doplnění sekce 15.01.
- **Původ termínu „inbox pattern".** Kanonické katalogy vedou Idempotent Consumer / Idempotent
  Receiver; kdo poprvé použil „inbox" jako protějšek outboxu, se nepodařilo dohledat.
- **Výkonová čísla relay workeru v PHP.** Žádný veřejný benchmark se nepodařilo najít.
  Doporučuji buď vlastní měření na referenční konfiguraci, nebo čísla z kapitoly odstranit.
- **Debezium PostgreSQL: chování replikačního slotu při zaseknutém konektoru** (růst WAL, riziko
  zaplnění disku). Dokumentace [8] to zmiňuje v jiných sekcích, než které byly stažené; před
  zapracováním doporučení G20 je vhodné ověřit přesnou formulaci.

### Doověřeno osmým a devátým kolem (2026-09-04 až 05)

**Hlas a slovník:** „Naprostou většinu" / „Pro naprostou většinu" (zakázané zesílení) nahrazeno.

**Ověřeno bez nálezu:** konfigurace transportů, retry strategie, `TransportMessageIdStamp`,
schéma outbox tabulky. Imperativ v rollout plánu (sekce 15.11) ponechán záměrně — je to
postup, ne definiční pasáž; viz zpřesněné pravidlo v `CLAUDE.md`.
