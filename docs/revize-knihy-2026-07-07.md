# Revize knihy — 7. 7. 2026

> **Stav: uzavřeno 8. 7. 2026.** Všechny nálezy aplikovány v commitech e696294 (nefunkční kód a rozpory text–kód), abc8a46 (sjednocení konvencí), 23ee60e (glosář), 9b7b50b (jazyk), 0f0bcb6 (rytmus), 507cea7 (webová verifikace NEJISTÝCH nálezů + modified data), 3ec4941 (konvence do CLAUDE.md). Čísla řádků níže odpovídají stavu před opravami.

Rozsah: všech 26 kapitol (hluboká revize: voice, AI vzory, jazyk, rytmus, vnitřní smysluplnost, konzistence kódu s konvencemi knihy) + průřezový průchod konzistentnosti + mechanické kontroly celé knihy. Průchod 3 (webová faktická verifikace) neproveden — podezřelá tvrzení označena NEJISTÉ.

## Mechanické kontroly (celá kniha) — čisté

- Em dash (—): 0 výskytů. Anglické uvozovky v próze: 0 (české „…“ správně). „Tady": 0. Tykání: téměř 0 (2 nálezy v performance_aspects, viz níže).
- Zakázaná slova a fráze z CLAUDE.md (marketing, hype, filler): 0 výskytů.
- Interní odkazy: všech 26 rout z frontmatteru existuje, všechny odkazované kotvy `{#...}` existují, včetně 3 kotev glosáře (ověřeno skriptem).

---

## KRITICKÉ NÁLEZY (nefunkční kód / rozpor text–kód / věcný nesmysl)

Seřazeno podle kapitol v pořadí knihy.

### subdomains.md (02)
- ř. 94: skórovací test Core Domain je **logicky vadný** — „tři a více ANO = Core", ale u otázek 1 („můžeme outsourcovat?") a 2 („existuje standard?") indikuje Core odpověď NE; hrubý součet ANO dává špatné výsledky (ANO,ANO,ANO,ANO,NE vyjde Core, přestože Q1+Q2 říkají Generic). Věta o „třech a více NE" je u 5 otázek navíc tautologická.
- ř. 377: `#[ORM\Column(type: "uuid")] private string $id` — Doctrine typ `uuid` hydratuje objekt `Uuid`, ne string; ukázka spadne za běhu.
- ř. 231 + 294: táž pointa o juniorovi a chybějícím `Domain/` adresáři 2× během 60 řádků.
- ř. 466–468: pasáž o relativitě klasifikace potřetí opakuje totéž (callout ř. 121–127, scénář ř. 149).
- ř. 615 vs. 154: FAQ míchá kusy a procenta („1–2 Core, 60 % Supporting…") a neladí s distribucí kapacity na ř. 154.
- ř. 492: „Core mu zůstává stále u plateb" — pleonasmus + imperativ v definiční pasáži; ř. 50: 3× „nejvíce" v jedné větě.

### context_mapping.md (03)
- ř. 66 + FAQ ř. 885: „ACL je technika, kterou downstream aplikuje **v Customer/Supplier nebo Conformist pozici**" — přímý rozpor s vlastní definicí Conformistu (ř. 302: „žádný překlad, žádná validace"). Conformist a ACL jsou alternativní volby téže pozice; ACL uvnitř Conformistu je protimluv.
- ř. 226–247 vs. 250: ukázka messenger.yaml pro downstream obsahuje `routing` blok pro externí eventy — a callout hned pod ní vysvětluje, že routing „konfiguruje odesílání, ne příjem". Příklad demonstruje přesně chybu, před kterou varuje. (Stejná chyba jako microservices ř. 610.)
- ř. 362: „Cena za to je nulová investice do ACL; cena, kterou platíme, je křehkost" — první „cena" označuje přínos, věta si protiřečí.
- ř. 376: „upstream zavře službu → downstream odkázán na vendora" — na vendora, který právě končí, nelze být odkázán.
- ř. 214: „varuje na obě strany" (kalk); ř. 697: „kdy bylo event vyvoláno" (rod); ř. 324: nepoužitý import `Stripe\Charge`; ř. 429: translator dostává `$soap`, ale nepoužívá ho (vs. tvrzení „žádný stav"); ř. 627: Sunset datum 2025 v minulosti vůči publikaci.
- Rytmus: 8 vzorů v identické šabloně (definice → Evans → příklad → kód → anti-vzor → callout) — po třetím předvídatelné; 03.08–03.09 nejsušší.

### event_storming.md (04)
- celá kapitola: rozsahy se **spojovníkem** („2-4 h", „8-12 účastníků"…) — jediná kapitola; zbytek knihy má en pomlčku. Hromadná náhrada.
- ř. 91 vs. 132: frame 6000×3000 px vs. 12 000×4 000 px — sjednotit.
- ř. 309 vs. 588: identifikátor „Order-7" označuje dva různé hot spoty v jednom ilustračním universu.
- ř. 528 vs. 550–551: „3 adresáře zrcadlí 3 fialové stickies" vs. commit „Identifikováno: 5 BC".
- ř. 676 FAQ: odpověď „Vývoj." si o tři věty dál protiřečí („měly platit společně").
- ř. 596–612: `@test` anotace v docblocku — v moderním PHPUnit odstraněná, patří `#[Test]`.
- ř. 27: „přečíst ji jako knihovnu nelze" — zřejmě „jako knihu"; ř. 294: handler volá nedefinované `$order->id()`; ř. 620: `->amount` vs. `amountCents` v context_mapping.
- Rytmus: nejživější ze čtveřice; slabina sekce 04.09 (ř. 434–557) — šest podsekcí výčtů „co uložit kam".

### team_topologies.md (05)
- ř. 94–100: „Jeden BC = jeden tým (**vždy**)" dostává o větu dál výjimku „bez explicitního Shared Kernel vztahu" — a Shared Kernel je přitom vztah dvou BC, ne režim spoluvlastnictví jednoho BC (správně až ř. 627). Pojem se tu rozmazává.
- ř. 105/411/736/743/769: počty BC na tým kolísají (2–3 / 1–2 / 1–3 / ≤2 / „typicky 2, ojediněle 3") — sjednotit.
- ř. 63–64: „8 vývojářů… nevyprodukují" → „nevyprodukuje" (po číslovce 5+); ř. 343: „ztrácejí seniority" → „senioritu"; ř. 25: „zlomí pravidlo" → „poruší"; ř. 411: „limit bližšího vědomí" nesrozumitelné; ř. 486: „count of services, ticket throughput" — syrová angličtina v české větě; ř. 694: „stane gatekeeper" → „gatekeeperem"; „ticket" vs. „tiket" střídavě.
- ř. 330–340 (NEJISTÉ): Bezosův mandát 2002 vč. „budou propuštěni" — tradováno přes Yeggeho rant, ne primární zdroj.
- ř. 351, 663–667 (NEJISTÉ): čísla („v 90 % selže", „lead time −30–80 %") podaná s přesností výzkumu bez zdroje.
- Rytmus: nejlepší jednověté pointy v knize; druhá polovina (ř. 348–719) je ale téměř souvislý sled seznamů — rubrika ř. 437–482 duplikuje pětici otázek z ř. 421–425.

### basic_concepts.md (06)
- ř. 498–518, 591: události s příponou „Event" (`OrderCreatedEvent`) — jediná kapitola se suffixem, ostatní bez (`PostCreated`, `OrderCancelled`). Je to vzorová kapitola, sjednotit na tvar bez suffixu.
- ř. 185–188 vs. 242–285: text zavádí konvenci pojmenovaných výjimek (`InvalidOrderStateTransitionException`), vzorový `Order` hází 6× holou `\DomainException`.

### aggregate_design.md (07)
- ř. 314–352, 767–774, 870: kapitola staví na **ULID** jako výchozí volbě ID; konvence knihy i cqrs/event_sourcing je `Uuid::v7()`. Kapitoly si protiřečí v tom, co je „výchozí volba". (Totéž outbox_pattern ř. 216, 269, 406, 1525; performance ř. 488–527; migration_from_crud ř. 987.)
- ř. 215: „tato třída nelze rozdělit" → „tuto třídu nelze rozdělit" (akuzativ).
- ř. 249: komentář odkazuje na `TransferMoney` z anti-vzoru, správně `InitiateTransfer`.
- ř. 713–714, 868: „deduplikace přes konzistentní hash" — popsaná technika je partitioning/směrování, ne deduplikace.
- ř. 722–723: snapshot „po 100 eventech / práh 10 000" vs. event_sourcing `SNAPSHOT_INTERVAL = 50` a „stovky až tisíce" — čísla nesedí.

### lesser_known_patterns.md (08)
- ř. 1367–1369: „V další kapitole se podíváme na výkonové aspekty" — po kap. 08 následuje 09 Architektonické styly; performance je 16. Nepravdivé tvrzení o pořadí knihy.
- ř. 96–98: kolokace dvou implementací pravidla označena za „double-dispatch" — chybná definice (správně až ř. 453–455).
- ř. 553–557 vs. 1325: tabulka anti-vzorů označuje „dvě nezávislé verze pravidla" za symptom a double-dispatch za nápravu — text sám přiznává, že i QuerySpecification má dvě implementace jištěné kontraktním testem. Zpřesnit.
- ř. 427–441: `ApplyFreeShippingHandler` — `BlacklistRegistry` bez importu/definice; „CommandHandler" přijímá agregát místo command DTO (odporuje kap. 12).
- ř. 363–365: VO přes accessor metody (`->country()->code()`) vs. konvence `public readonly`.

### architectural_styles.md (09)
- ř. 601–675: dvě různé třídy `Money` v jednom příkladu (`App\Pricing\Domain\Model\Money` vs. `App\Shared\Domain\Money`) — typová neslučitelnost; adresářový výpis má Money jen v Shared.
- ř. 299, 831, 1268: `$id->toString()` vs. závazná konvence `$id->value` / `(string) $id`.
- ř. 304–310: `DoctrineOrderRepository::save()` vytváří vždy novou ORM entitu + `persist()` — při update kolize PK; limit nezmíněn. (Totéž implementation ř. 740–750.)
- ř. 566–571 vs. 1161–1171: callout „Anemic Hexagonal" duplikován téměř doslova v 09.09.
- ř. 915: „5–7 souborů … ve všech sedmi" — čísla nesedí (tabulka navíc uvádí 4–6).

### implementation_in_symfony.md (10)
- ř. 1406–1413: catch `UniqueConstraintViolationException` v handleru **nikdy neproběhne** — flush dělá `doctrine_transaction` middleware až po návratu handleru; výjimka navíc nebude zabalena v `HandlerFailedException`, nechytí ji ani kontroler. Vzor je správný záměr, ukázka v deklarovaném transakčním modelu nefunguje.
- ř. 914–990: `Order` v 10.08 porušuje tři pravidla, která kapitola sama ustavila: nedědí z `AggregateRoot` (duplikuje record/releaseEvents), nahrává event v konstruktoru (proti pravidlu o rekonstituci), `id()` re-konstruuje VO v getteru. V 10.09 už `Order` dědí správně.
- ř. 693: `length: 26` pro UUID v7 — kanonický zápis má 36 znaků (26 = ULID).
- ř. 1808: FAQ doporučuje `src/Domain/<BC>` — obráceně proti struktuře celé kapitoly (`src/<BC>/Domain/`).
- ř. 863–864: „neprojde už při kompilaci" — PHP odhalí neexistující enum case až za běhu.
- ř. 407 vs. 1537–1538: „v doménové vrstvě validujeme syntakticky" vs. „Validator = syntaktická, doména = sémantická" — přímý terminologický rozpor.

### authorization_in_ddd.md (11)
- ř. 540–543: `$sql = $base . ', audit_log'` připojí sloupec ZA klauzuli WHERE → syntakticky neplatné SQL.
- ř. 450 vs. 306: `isCancellable(\DateTimeImmutable $now)` s povinným parametrem vs. Twig `order.isCancellable` bez argumentu → fatal error. Navíc ř. 465 tvrdí, že metodu „Voter ani Twig nevolají" — obě ukázky ji volají.
- ř. 838: test volá neexistující getter `$order->status()`.
- ř. 1054 vs. 572: prahy si odporují — externí engine „100+ pravidel" vs. interní ABAC „150+ pravidel".
- ř. 1086: FAQ odkazuje na `#testing` místo `#audit-log-heading`.
- ř. 16 (deck!): „Každá vrstva odpovídá jinou otázku" → „odpovídá na jinou otázku".

### cqrs.md (12)
- ř. 570–577: SQL `SELECT … m.tier … GROUP BY u.id` — na PostgreSQL skončí chybou (tier mimo GROUP BY); `COUNT(o.id)` se při více memberships násobí.
- ř. 1473–1503: testy konstruují `OrderPlaced` bez `occurredAt`, projektor ho čte — ukázky se nezkompilují dohromady.
- ř. 451: `new UserId()` bez hodnoty — proti konvenci `UserId::generate()`.
- ř. 262 + 327: identická syntaktická kostra otevírá sekce Commands a Queries (paralelismus + opakuje definice z ř. 39–40).

### event_sourcing.md (13)
- ř. 1049–1072: `IdempotentOrderProjector` — checkpoint INSERT a projekční INSERT bez společné transakce → tichá ztráta události, přesně ta, před kterou kapitola varuje.
- ř. 1379 + 572–618: snapshotting rozbíjí optimistic locking — `$version` je private bez setteru, po `reconstituteFromSnapshot()` je verze špatně → konflikt/přepis při save.
- ř. 1251–1259: rebuild volá `$projector($event)` pro všechny eventy, ale projektor má handlery v metodách `handle*()` — na cizí event typ spadne na TypeError.
- ř. 1280: warning zmiňuje `--batch-size`, příkaz takovou volbu nemá.
- ř. 750–752: ES `Order` s gettery `orderId()`, `status()` vs. `public private(set)` konvence z aggregate_design.
- ř. 1713–1718: strategie 2 odkazuje na neexistující sloupec `aggregate_version` a verzi v3 bez kontextu.

### sagas.md (14)
- ř. 1075–1076: „Messenger zajistí, že každou zprávu zpracuje právě jeden worker" — popírá at-least-once doručení, na kterém stojí celý argument pro idempotenci (ř. 169–171, 1330–1333).
- ř. 822–824: text odkazuje na neexistující `findOrCreateSaga(orderId)` a sloupec `order_id` — kód má `findByCorrelationId` a UNIQUE na `(saga_type, correlation_id)`.
- ř. 842–861: `applyPaymentSucceeded(string $eventId)` — žádná událost v kapitole `eventId` nenese.
- ř. 794–796: scénář „worker spadne mezi zpracováním dvou zpráv → redelivery" — mezi zprávami žádná nedokončená událost neexistuje; redelivery jen při pádu během zpracování.
- ř. 1421: `OrderSagaStatus::AwaitingStockAndInvoice` v enumu neexistuje.

### outbox_pattern.md (15)
- ř. 1276–1278: logika lease TTL je **obráceně** — „TTL kratší než processing batch" právě způsobí double publish; správně batch musí doběhnout (nebo lease obnovit) před vypršením TTL.
- ř. 733/755 vs. 1217/1311: propustnost jednoho workeru „~30k events/s" vs. „~5k events/s" — 6× rozdíl v téže kapitole.
- ř. 477–481: `DomainEventSerializer` bez use importu — nepřeloží se.
- ř. 1159: `REINDEX … outbox_status_occurred_at_idx` vs. jinde důsledně `idx_outbox_status_time`; ř. 1182: `event_type` vs. kanonické `message_type`.
- ř. 1249–1257 (NEJISTÉ): Predis `set(...)` vrací Status objekt, `$result === 'OK'` selže vždy.

### performance_aspects.md (16)
- ř. 142, 145, 180, 211, 294, 1210: „JOIN FETCH" — Doctrine DQL klíčové slovo `FETCH` nezná (to je JPQL/Hibernate); oba DQL dotazy by skončily QueryException. FAQ ř. 1232 to má správně — vnitřní rozpor. 6 výskytů.
- ř. 999: `#[ORM\Lock(...)]` — takový mapovací atribut Doctrine nemá; pesimistický zámek je runtime volba `$em->find(..., LockMode::PESSIMISTIC_WRITE)`.
- ř. 820, 987–988: jediné tykání v knize („načti, aplikuj, zavolej"; „publikuj") — přepsat deklarativně.
- ř. 1030: „Drop staré dat je vyžadovaný" — rozbitá shoda i registr.
- ř. 678 (NEJISTÉ): `LazyInitializationException` je název z Hibernate, Doctrine ho zřejmě nemá.
- ř. 822–824: „zpracovává celou Identity Map … čas roste lineárně" — pokud se prochází celá rostoucí mapa, roste kvadraticky; věty si odporují.
- ř. 1054–1056: aritmetika connections (100 × 4 × 10 = 4000 vs. max_connections per instance) nesedí.

### testing_ddd.md (17)
- ř. 229–237: test `assertCount(0, $user->releaseEvents())` po registraci — `User::register()` zaznamenává `UserRegistered`, test by spadl. Chybí vyprázdnění eventů před `changeEmail()`.
- ř. 571, 602–625: handler test s `EmailAlreadyTakenException` + check-then-save — implementační kapitola u téhož handleru varuje před TOCTOU race a používá `DuplicateEmailException` + DB constraint; testovací kapitola fixuje testem přesně ten anti-vzor.
- ř. 420–433: `use DomainEventAssertions;` bez importu traitu → nefunkční PHP.
- ř. 796–817 vs. 587/923: tři ukázky téhož registračního endpointu s neshodným payloadem (`name` jednou chybí).
- ř. 1048–1077: Deptrac vrstvy nezachytí handler z `App\...\Command` namespace používaný v téže kapitole.

### migration_from_crud.md (18)
- ř. 714–717: prázdný `foreach ($user->releaseEvents() …)` — vyprázdní buffer a eventy zahodí; infrastruktura už nic nedostane. Odporuje Receptu 7 i mechanismu onFlush listeneru.
- ř. 987: `public Ulid $value` → `Uuid` + `Uuid::v7()`.
- ř. 219–223: pádová neshoda v listu („Odkrytí… Identifikaci…") + 4× nominalizace.

### microservices_and_ddd.md (19)
- ř. 610–615: Messenger `routing` použit pro **konzumaci** — routing mapuje na odesílací transporty; takto by se zpráva odeslala zpět do AMQP. Komentář vykládá sémantiku chybně.
- ř. 676–705: `TYPE_MAP` se dvěma typy, ale `decode()` má jednu pevnou sadu named argumentů — `OrderCancelledReceived` by musel mít identický konstruktor, což popírá smysl oddělených DTO.
- ř. 89–91 vs. 868: heuristika „všech šest bodů" (včetně „vzácného" bodu) vs. shrnutí s jinými prahy — kritéria si odporují a vlastní příklad by jimi neprošel.
- celý soubor: smíšené skloňování „service" — „Servisa A", „původní servis", „N servisů" vs. „deset servis" — dvě paradigmata.
- ř. 274: přímý anglický citát Newmana — dle konvence knihy parafrázovat.
- ř. 285: „rozdíl 4 řády" — µs vs. ms jsou 3 řády.

### anti_patterns.md (21)
- ř. 575 vs. 819–824: `new OrderPlacedEvent(2 argumenty)` vs. konstruktor téže třídy se 4 povinnými parametry.
- ř. 830–834: „správný" event má accessor metody duplikující `public readonly` properties — porušuje konvenci knihy.
- ř. 29: „nabízí strukturu…, s tou strukturou přicházejí" — přesně vzor dvojího slova z CLAUDE.md.
- ř. 1129: „Ubiquitous Language není jen o pojmenování tříd – zahrnuje také…" — klišé „nejen X".
- ř. 1032+1041: `class User` deklarována 2× v jednom bloku (fatal error) bez poznámky.

### when_not_to_use_ddd.md (22)
- ř. 33: „odpovězte si pět otázek" → „na pět otázek". ř. 193: „Až doména stabilizuje" → „se stabilizuje". ř. 247: „tuto návratnost nedosáhnete" → „této návratnosti".
- ř. 384–385: „životnost po migraci > 3× cena migrace" — porovnává čas s penězi, jednotky nesedí.
- ř. 275–276, 289–291: smíšené uvozovky v komentářích kódu (česká otevírací + ASCII zavírací).
- ř. 313: `OrderStatus::CANCELLED` vs. `OrderStatus::Cancelled` v basic_concepts.

### practical_examples.md (23)
- ř. 145–146 vs. 149–166: text/kód nesoulad u `Post` (konstruktor vs. `create()`, „neprázdný autor" vs. „content nesmí být prázdný").
- ř. 99–113: handler injektuje `ProductRepository`, který ve struktuře projektu neexistuje.
- ř. 121: „dva agregáty (Post, Comment)" — struktura má jen `PostRepository`; tabulka mluví v singuláru.
- ř. 323: „## Závěr" jediný nadpis bez čísla a kotvy `{#…}`.

### case_study.md (24)
- ř. 365–366 vs. 388: „Třída je `final`" vs. kód `class Project extends AggregateRoot // ne final`.
- ř. 1043, 1357: `$project->createdAt()` — metoda neexistuje, je to `public readonly` property.
- ř. 607–614: `Task::changeStatus()` ignoruje vlastní `TaskStatus::canTransitionTo()` — deklarovaný stavový automat bez guardu.
- ř. 162–164 aj.: text tvrdí, že ID VO jsou v Shared Kernelu, kód je má v namespacech kontextů a `SharedKernel/` je prázdný — odporuje i vlastnímu výkladu ACL.
- ř. 1091–1092 vs. 1392–1394: „read model lze sestavit z událostí" vs. „události nejsou perzistentní" — rebuild z transientních událostí nejde.
- ř. 1353–1363: reconciler čte neinicializovanou typed property `$view->name` → PHP Error; drift `description` se nikdy neopraví.
- ř. 27–28: `UserService` → o větu dál `TaskService` (změna jména služby bez vysvětlení).
- ř. 1420, 1522: dvě ASCII zavírací uvozovky `"` (jediný typografický nález v knize).

### ddd_pain_points.md
- ř. 90–108: `ConfirmTransferService` — `beginTransaction()`…`commit()` bez `flush()` → commituje prázdnou transakci, nic neuloží.
- ř. 940–941 vs. 961–990: zadání „zrušit může zákazník nebo admin", kód admin větev nemá.
- ř. 42: „doménový model s neměnnými konstruktory" — nesmysl, míněny neměnné objekty / privátní konstruktory.
- ř. 706–728: `Order` volá `$this->record(...)`, ale nedědí z `AggregateRoot`.

### ddd_ai.md
- ř. 22–25: wikipedijní úvod + „éra AI" (blízko zakázaného „nová éra") — škrtnout první větu.
- ř. 305–306 (NEJISTÉ): článek „DHH Is Wrong" citován zřejmě opačně, než argumentuje; chybí ve Zdrojích. „Proslulý" smazat.
- ř. 331–336: dovozování pozice Newmana, který se nevyjádřil — oddělit od referovaných pozic.
- ř. 135–140 vs. 271–286: tatáž pointa o CLAUDE.md/Cursor rules dvakrát.

### preface.md (00) + what_is_ddd.md (01)
- preface ř. 29–35 vs. 106–163: P.01 vyjmenovává 5 rolí (se „Symfony developerem", bez juniora), P.03 dává cesty jiné pětici (s juniorem, bez Symfony developera); what_is_ddd ř. 294 kopíruje P.03. Sjednotit.
- what_is_ddd ř. 230–236: 6 + 2 + 4 měsíce ≠ „po čtrnácti měsících".
- what_is_ddd ř. 203–209: krok 3 pattern calloutu mluví o „CRUD service vrstvě", která v DDD variantě není; event `PaymentMethodAdded` v příkladu nic nedělá.
- what_is_ddd ř. 248–256: warn callout „Nepoužívejte DDD, pokud:" — imperativ mimo pattern + duplikuje 01.08 i kap. 22.

---

## PRŮŘEZOVÁ KONZISTENTNOST (dedikovaný průchod)

### Sdílené ukázkové třídy s neslučitelným API
- **`User`** (vysoká): tentýž soubor `src/UserManagement/Domain/Model/User.php` má tři neslučitelná API — implementation (`register(UserId, UserName, Email, HashedPassword)`, event se 3 argumenty), migration (`register(UserId, Email, HashedPassword)`, event se 2), practical_examples (`register(UserId, string $name, Email, HashedPassword)`, `record()` v konstruktoru místo factory). Sjednotit podle kap. 10, nebo změnit namespacy.
- **`Email`** (vysoká): basic_concepts a implementation výslovně učí „normalizace patří do `fromUserInput()`, ne do konstruktoru" — anti_patterns:347 a migration:458 mají `mb_strtolower(trim())` přímo v konstruktoru téže třídy.
- **`Money`/`Currency`** (střední): `amountCents` (context_mapping) vs. `amountInCents` (anti_patterns, ddd_pain_points); `Currency` jako string-backed enum (anti_patterns, basic_concepts) vs. třída s `new Currency($code)` a `->code` (ddd_pain_points, context_mapping) — enum `->code` nemá a `new` na enumu nejde.
- **`Order`** (střední): `Order::place()` deklarováno jako konvence (implementation:180, lesser_known:932) — basic_concepts:572 a testing:279 mají `Order::create()` nad stejným namespace; `addItem` s jiným 1. argumentem i pořadím parametrů (basic_concepts/anti_patterns vs. testing); UserId vs. CustomerId pro téhož vlastníka.
- **`Order::place($customer)`** (střední): architectural_styles:814 předává do agregátu celý agregát Customer — proti třetímu Vernonovu pravidlu „reference přes ID", které aggregate_design opakovaně zdůrazňuje.
- **`OrderId`** (nízká): veřejný konstruktor s validací (aggregate_design, anti_patterns) vs. privátní + `generate()`/`fromString()` (ddd_pain_points, performance).
- **`practical_examples` `User`** (střední): `final class User implements UserInterface` prezentován jako vzor — authorization:1015 tutéž závislost domény na Symfony Security označuje za anti-vzor; `final` navíc koliduje s pravidlem „ne final kvůli Doctrine proxy" ze 3 kapitol.

### Glosář vs. kapitoly
- **Sága vs. Process Manager** (střední): sagas.md (i cqrs.md) stanoví „PM = orchestrovaná podoba ságy, vždy orchestrátor" — glosář je prezentuje jako synonyma („alternativní název z CQRS komunity"). I atribuce se liší (EIP Hohpe & Woolf vs. „CQRS komunita"). Převzít formulaci z kap. 14.
- **Atribuce subdomén prohozené** (střední): subdomains.md (dle konvence): Evans 2003 = Core + Generic, Vernon 2013 = Supporting. Glosář to má obráceně (Supporting primárně Evans s čísly stran, Generic jen Vernon).
- **Čísla stran** (nízká): konvence knihy „citace bez stran" — glosář má 27× „str. N–M". IDDD kapitola pro ságy: sagas.md „kap. 4" vs. glosář „kap. 8 & 10".

### Sliby a návaznosti
- preface:43 slibuje zdroje „na konci každé kapitoly" — závěrečnou sekci zdrojů nemá 15 z 26 kapitol. Zmírnit formulaci.
- lesser_known:1367 „v další kapitole… výkonové aspekty" — následuje kap. 09, výkon je 16 (viz výše).
- Čísla kapitol, prefixy `## NN.` i `fig="NN.x"` sedí všude — bez nálezu. Roky vydání citovaných knih konzistentní. Definice BC, UL, VO, agregátu, doménové události a subdomén napříč kapitolami konzistentní.

## SYSTÉMOVÉ VZORY (napříč knihou)

1. **ULID vs. Uuid::v7()** — aggregate_design, outbox_pattern, performance_aspects, migration_from_crud používají/doporučují ULID; konvence knihy a ostatní kapitoly `Uuid::v7()`. Rozhodnout a sjednotit (nebo odchylku explicitně zdůvodnit na jednom místě).
2. **Uniformní bullet listy `**Pojem** – věta`** — každá delší kapitola má 5–10 listů se stejným rytmem; nejhorší: aggregate_design 07.08–07.12, architectural_styles ř. 555–720, testing úvod/závěr. Dle pravidla alespoň jeden list na kapitolu rozbít (próza / rozkolísané délky).
3. **Imperativy ve `warn`/`note` calloutech** — CLAUDE.md je vyhrazuje pro `pattern`; výskyty v authorization, testing, performance, what_is_ddd, microservices.
4. **Accessor metody vs. `public readonly`** — event_sourcing (gettery na agregátu), lesser_known_patterns, anti_patterns (accessory na eventu), architectural_styles (`toString()`), basic_concepts (entity s `id()`, `name()` bez komentáře) — vs. kodifikovaná konvence.
5. **Pojmenované vs. holé doménové výjimky** — konvence deklarována v basic_concepts, holá `\DomainException` v basic_concepts, practical_examples, case_study.
6. **Nesjednocený přístup k času** — `new \DateTimeImmutable()` natvrdo (basic_concepts, case_study) vs. injektovaný `Clock` (when_not_to_use_ddd).
7. **Doctrine entity/adaptery v Domain/Application namespacech** — sagas (`OrderSaga` v Application), outbox (`OutboxMessage` v Domain) vs. pravidlo kap. 08/10 „Domain nesmí importovat Doctrine".
8. **Autorský tik** „literatura to nerozvádí / příručky přeskočí" — when_not_to_use_ddd ř. 28–29, ddd_pain_points ř. 26–27; kontextová vsuvka dle metodiky k vyřezání.
9. **Opakování „pořadí kapitol je promyšlené, čtěte selektivně"** — preface 2×, what_is_ddd 1×.

## RYTMUS A ZÁŽIVNOST — celkový obraz

- **Nejsilnější kapitoly:** when_not_to_use_ddd (vzor B v praxi), basic_concepts (nejlepší poměr text/kód), ddd_ai (střídání hlasů, gradace), microservices (názor + konkrétní čísla), implementation_in_symfony (tempo, zapamatovatelné anti-vzory), ddd_pain_points (katalog s pointami).
- **Společný neduh:** druhé poloviny dlouhých kapitol sklouzávají do bullet-katalogu bez příkladů (aggregate_design 07.08+, sagas 14.06, outbox 15.07, event_sourcing 13.01–13.05, architectural_styles ř. 555–720, testing úvod/závěr, performance 16.09 — telegrafický, prošpikovaný anglicismy).
- **case_study:** prostředek (~800 řádků kódu, 24.04–24.05) ztrácí slibovaný příběh týmu; 150řádkový adresářový výpis s generickými komentáři.
- **what_is_ddd:** sekce 01.04–01.08 = bullet katalogy; 01.12 „Jak číst knihu" až za Shrnutím/FAQ působí jako přilepený dovětek.
- **practical_examples:** sekce 23.02 a 23.03 mechanicky stejné — chybí jedna odlišující věta na příklad.
- **anti_patterns:** šablona Špatně/Správně 6× beze změny; callouty parafrázují komentáře v kódu.

## Menší jazykové nálezy (výběr, plný seznam u agentů)

- when_not: 3 gramatické chyby (viz výše) + „jen aby zjistil" (kalk), „3 roky … vyhozeny" (číslice na začátku věty).
- outbox: telegrafické věty ve FAQ a 15.07 („Limit při LIMITu 100", „kompakce ne-mazat pending stará než N dní", „pull-request reverter"), „na účtě", „Marketing materiály".
- microservices: „Skopírujte", „Z venku", „jeden set invariantů", „availabilities", „vlastní attributy".
- case_study: „produkťák" (2×, registr), anglické vs. české zprávy výjimek, „v třídách".
- cqrs: „aby se nedostal mimo" (kalk out-of-sync), „selhalé zprávy", „Poslední cenou je učební křivka".
- event_sourcing: „dokud migrace nedokončí" (chybí se), „Před zavedením stojí úvaha".
- authorization: „načteným aktérem" → „načtením aktéra", „RBAC explodne", „Pokud aplikace je multi-tenant".
- ddd_pain_points: „neměnné konstruktory", „které se vrací" → „vracejí", „doručí první výsledky" (kalk deliver).
- ddd_ai: „rezonuje s tím", „zbyde" → „zbude", „nejvíce aktivních" → „nejaktivnějších".
- sagas: „peníze odešly i přijdou", anakolut „Read-only inspekci – …", „proto" bez příčiny (ř. 802).
- migration: „až po tom, co" → „až poté, co", pádová neshoda v listu ř. 219–223.
- preface: „v termínech DORA metrik" (kalk in terms of), „anti-vzor, kterému se vyhnout".
- what_is_ddd: zeugma „dochází k duplicitě pravidel a těžko udržovatelnému kódu", comma splice ř. 241, „glosář v repu" (slang).
- lesser_known: „Defaultně volte", zájmena bez antecedentu (ř. 25–27, 554–555), „prošel kdysi v minulosti".
- architectural: „A neplatí se" → „nevyplatí se", „Pokud implementací portu existuje víc", pasivum ř. 24.
