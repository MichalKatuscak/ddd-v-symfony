# Studie: DDD v praxi – kde to bolí

- **Kapitola:** `content/chapters/ddd_pain_points.md` (č. 20, kategorie Praxe, 1074 řádků)
- **Cesta:** /ddd-v-praxi-kde-to-boli
- **Typ kapitoly:** narativní (fakticky katalogová – 20 očíslovaných položek se strukturou problém/příčina/řešení)
- **Datum studie:** 2026-09-04

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| Úvod | `:22`–`:37` | Katalog 20 provozních problémů; vymezení vůči kap. 21 a 22 | – | Vymezení je explicitní a funguje |
| 20.01 A – Doctrine (A1–A6) | `:39`–`:337` (300 ř.) | UoW je session-scoped, špinavý EM, mapování VO, lazy loading, identita, polymorfismus | žádné | Nejdelší blok kapitoly, 6 z 20 položek |
| A1 Transakce přes agregáty | `:45`–`:119` | Explicitní `beginTransaction()`/`commit()` v Application Service jako přijatelná výjimka | – | Kód nezmiňuje uzavřený EM po výjimce |
| A2 Špinavý EntityManager | `:121`–`:140` | `detach()`, `HYDRATE_ARRAY`, druhý read-only EM | – | Zmiňuje odstranění `merge()` v ORM 3 |
| A3 Mapping VO | `:142`–`:219` | `#[Embedded]` nestačí; Custom Type `MoneyType` do jednoho `VARCHAR(50)` | – | Formát `"12345_CZK"` je dotazovatelnost ničící volba |
| A4 Lazy loading | `:221`–`:240` | `fetch: 'EAGER'` „načte v jednom JOIN"; výjimka je `ORMInvalidArgumentException` | – | Obě tvrzení jsou fakticky chybná |
| A5 Identity generation | `:242`–`:315` | UUID v konstruktoru agregátu, `GeneratedValue(strategy: 'NONE')` | – | V rozporu s kanonickým `Order::place(OrderId $id, …)` |
| A6 Polymorfismus | `:317`–`:337` | Discriminator map narušuje OCP; VO s type fieldem + JSON | – | Tabulka má tři řádky, nadpis slibuje dvě alternativy |
| 20.02 B – Async (B1–B4) | `:339`–`:675` (338 ř.) | Outbox, debugging Messengeru, idempotence, ordering | – | Nejdelší blok; B1 duplikuje celou kapitolu 15 |
| B1 Outbox | `:345`–`:457` | `onFlush` listener + `computeChangeSet()`; Doctrine Transport jako alternativa | – | Technicky správné, ale patří do kap. 15 |
| B2 Debugging | `:459`–`:540` | `messenger:failed:show`, monolog, Correlation ID middleware | – | Praktické, nikde jinde v knize není |
| B3 Idempotence | `:542`–`:649` | `IdempotencyStamp` s klíčem „vygenerovaným při prvním odeslání"; TOCTOU callout | – | Klíč z dispatch time Symfony dokumentace explicitně zavrhuje |
| B4 Ordering | `:651`–`:675` | Tři strategie; zákaz `UnrecoverableMessageHandlingException` | – | Správně; chybí `RecoverableMessageHandlingException` |
| 20.03 C – Modelování (C1–C4) | `:677`–`:832` (157 ř.) | Validace, stavový automat, ACL, drift jazyka | – | C4 je jediná „netechnická" bolest s prostorem |
| C1 Validace | `:682`–`:700` | Čtyřřádková tabulka umístění validace | – | Bez odkazu na spor always-valid vs. deferred |
| C2 Stavový automat | `:701`–`:744` | Explicitní přechodové metody místo setteru | – | `place()` jako instanční metoda, `\DomainException` s českým textem |
| C3 ACL | `:745`–`:805` | Port & Adapter ke Stripe | – | Duplikuje doporučení studie kap. 18 (tam P1-1) |
| C4 UL drift | `:806`–`:832` | Glosář, ADR, čtvrtletní Event Storming, living documentation | – | Evansův vzor Continuous Integration nezmíněn |
| 20.04 D – Symfony (D1–D3) | `:834`–`:999` (167 ř.) | Form vs. Command, API Platform, Voter | – | D3 duplikuje kapitolu 11 |
| 20.05 E – Tým (E1–E3) | `:1001`–`:1060` (73 ř.) | Business case, strangler fig, bus factor | – | 7 % rozsahu kapitoly na celý organizační blok |
| FAQ | `:1063`–`:1074` | 5 otázek; čísla 3–6 měsíců, 12–24 měsíců | – | Čísla bez zdroje |

Kapitola je ze 60 % katalog třenic s Doctrine a Messengerem a ze 7 % katalog třenic s lidmi.
Titul přitom slibuje „kde to bolí" v celém rozsahu praxe. Devatenáct z dvaceti položek má
formát problém → příčina → řešení, což je čitelné, ale výsledný text je spíš referenční
příručka než narativní kapitola: nemá diagram (`:::diagram` 0×), nemá průběžný příběh
a v části položek jen zopakuje, co už kniha řekla jinde (Outbox v kap. 15, Voter v kap. 11,
Strangler Fig v kap. 18, custom typy v kap. 10). Naopak bolesti, které praktikům působí
nejvíc práce a které kniha nikde jinde neotevírá – nedostupnost doménového experta, učící
křivka, refaktoring špatně vedené hranice Bounded Contextu, dopad eventual consistency na
UI – v kapitole buď chybí, nebo dostanou dvě věty. Kapitola nikde neuvádí zdroje.

## 2. Kanonické zdroje k tématu

**Evans a fragmentace jazyka.** *DDD Reference* [1] popisuje drift Ubiquitous Language jako
očekávanou vlastnost, ne selhání: „When a number of people are working in the same bounded
context, there is a strong tendency for the model to fragment. The bigger the team, the bigger
the problem, but as few as three or four people can encounter serious problems." Evansova
odpověď má jméno – vzor **Continuous Integration**: „Institute a process of merging all code
and other implementation artifacts frequently, with automated tests to flag fragmentation
quickly. Relentlessly exercise the ubiquitous language…" Ve stejném dokumentu je i věta, která
opravňuje celou kapitolu 20: „Many projects do modeling work without getting much real benefit
in the end." A pro ADR praktiku sekce C4 existuje přímá opora: „Recognize that a change in the
language is a change to the model."

**Vernon a agregáty.** *Effective Aggregate Design* [2][3][4] je primární zdroj pro A1 a pro
chybějící téma výkonu agregátu. Část I dokumentuje přesně tu bolest, kterou kapitola neotevírá:
velký agregát znamená „thousands of backlog items would be loaded into memory just to add one
new element to the already large collection". Část II formuluje pravidlo *Use Eventual
Consistency Outside the Boundary* a heuristiku, kterou Vernon výslovně připisuje rozhovoru
s Evansem: „ask whether it's the job of the user executing the use case to make the data
consistent. If it is, try to make it transactionally consistent… If it is another user's job,
or the job of the system, allow it to be eventually consistent." Tamtéž je argument pro práci
s byznysem, ne s technikou: „Domain experts are sometimes far more comfortable with the idea of
delayed consistency than are developers."

**Fowler a ORM.** *OrmHate* [5] (2012) je nejlepší dostupná protiváha k tónu sekce A: Fowler
uznává leaky abstraction, learning curve i výkonnostní pasti, ale uzavírá „A framework that
allows me to avoid 80% of that is worthwhile even if it is only 80%." *StranglerFigApplication*
[6] dává E2 atribuci, kterou kapitola neuvádí (termín vznikl po Fowlerově cestě do Queenslandu
v roce 2001, původně „Strangler Application"). *BoundedContext* [7] a *DomainDrivenDesign* [8]
jsou zdroje pro strategickou část; v [8] je i doložitelná věta o učící křivce – Evansova kniha
„has a reputation for being a hard book to read".

**Dahan.** *Race Conditions Don't Exist* [9] je kanonický zdroj pro B4: „A microsecond
difference in timing shouldn't make a difference to core business behaviors." *Clarified CQRS*
[10] dodává rámec pro eventual consistency v UI: „once data has been shown to a user, that same
data may have been changed by another actor – it is stale."

**Seemann a Noback k identitě.** Seemann [11] ukazuje, že klientem generované GUID je způsob,
jak udržet CQS u `Create`. Noback [12] jde dál a doporučuje generovat ID v **repozitáři**
(`nextIdentity()`), ne v konstruktoru: „There's a natural, conceptual relation: repositories
manage the *entities* and their *identities*." Noback [13] pak katalogizuje přesně ty třenice,
které řeší sekce A – mapování VO, kolekce, identita child entit, více agregátů v jedné
transakci – a u posledního bodu nabízí řešení, které kapitola nezmiňuje: `EntityManager::clear()`
po každém `flush()`.

**Khorikov k validaci.** *Always-Valid Domain Model* [14] je protipól k tabulce v C1:
validace vstupu a invariant podle něj pramení z téhož byznys pravidla a liší se jen perspektivou
vrstvy. Pro doménu doporučuje výjimky, pro externí vstup Result objekty.

**Verraes k hranicím.** *Tensions when Designing Evolvable Bounded Contexts* [15] formuluje
tenzi, kterou kapitola vůbec neotevírá: „choosing many small BCs implies having larger
Interfaces, but choosing many small Interfaces implies having larger BCs", a uzavírá, že „There
are no solutions or hard rules to make these trade-offs."

## 3. Stav praxe a posuny

**Doctrine ORM 3 změnil pravidla, o která se sekce A opírá.** `EntityManager::merge()` je
odstraněn s odůvodněním „Merge semantics was a poor fit for the PHP share-nothing architecture"
[16]. Zmizela i možnost částečného `flush($entity)` a částečného `clear($entityName)` – oba
argumenty jsou ignorovány, resp. vyvolají chybu [16]. To má přímý dopad na A1 a A2: obvyklý
únikový manévr „flushni jen tenhle agregát" už neexistuje a Nobackovo doporučení `clear()` po
`flush()` dnes znamená vyčistit *celý* Unit of Work.

**Nativní lazy objects.** Od ORM 3.5 je vypnutí nativních lazy objektů na PHP 8.4+ deprecated
a v 4.0 nebude možné; `Configuration::enableNativeLazyObjects(true)` je cílový stav [16].
Zdrojový kód `ProxyFactory` v 3.6.x tuto větev implementuje přes `newLazyGhost()` a při
neexistující entitě vyhazuje `EntityNotFoundException` [17]. Studie kapitoly 10 už na tuto
změnu upozorňuje (tam P1-1) – kapitola 20 tvrdí na `:229`–`:231` něco jiného.

**Symfony Messenger dokumentaci k idempotenci mezitím doplnil.** Aktuální dokumentace [18]
nejenže popisuje at-least-once („A message can be delivered more than once under normal
operating conditions"), ale explicitně varuje před přesně tím řešením, které kapitola v B3
navrhuje: „A UUID generated at dispatch time is not suitable as an idempotency key. If the same
business event is dispatched twice… each dispatch generates a different UUID and both executions
will proceed. The key must remain stable across all dispatches of the same logical event."
Přibyla také `RecoverableMessageHandlingException` s parametrem `retryDelay`, což je přímá
odpověď na strategii „optimistický retry" z B4.

**Symfony Security se posunul.** Voter má od Symfony 7 nepovinný parametr `?Vote $vote`
pro zdůvodnění rozhodnutí a od 8.1 řazení přes `#[AsTaggedItem(priority:)]` [19]. Podpis
`voteOnAttribute()` v ukázce D3 je tedy neúplný.

**API Platform DTO cestu dokumentuje sám.** Ve verzi 4.3 je `ProcessorInterface::process()`
s návratovým typem `mixed` a dokumentace přímo popisuje scénář „not publicly expose the internal
model mapped with the database through the API" [20]. Řešení z D2 je tedy dnes mainstream,
ne obcházení frameworku – kapitola to podává jako boj.

**Ekosystém se zúžil.** Podle Packagistu [21] má `doctrine/orm` ~5,5 mil. stažení měsíčně
a `symfony/messenger` ~4,7 mil.; naproti tomu `broadway/broadway` je označen jako **abandoned**
(~18 tis./měsíc), `prooph/event-store` ~17 tis., `ecotone/ecotone` ~14 tis. a
`patchlevel/event-sourcing` ~30 tis. Praktický důsledek pro kapitolu: bolest „boj s Doctrine"
je pro PHP tým bez realistické alternativy, protože specializované DDD frameworky reálnou
uživatelskou základnu nemají.

**Noback změnil pozici.** V roce 2018 [13] popisoval obcházení Doctrine, v roce 2022 [22]
označuje oddělený persistence model za „an expensive and unnecessary form of decoupling"
a doporučuje „80% decoupling is fine". To je relevantní pro FAQ kapitoly, které odkazuje na
Persisted Object Pattern jako na cestu k „striktně oddělené doméně".

**Doložené vs. folklorní bolesti.** Zadání studie žádalo tuto klasifikaci explicitně:

| Bolest | Status | Opora |
|---|---|---|
| Mapování agregátu na Doctrine | **doložená** | Noback [13], Fowler OrmHate [5], ORM UPGRADE [16] |
| Eventual consistency a UI | **doložená** | Vernon [3], Dahan [10]; v knize kap. 12.12 |
| Výkon a načítání velkých agregátů | **doložená** | Vernon [2], Doctrine performance [23], cascade warnings [24] |
| Fragmentace / drift Ubiquitous Language | **doložená** | Evans [1] – včetně prahu „three or four people" |
| Ordering a duplicity zpráv | **doložená** | Symfony Messenger [18] |
| Nejasné hranice Bounded Contextu a jejich změna v čase | **doložená jako tenze**, nedoložená jako postup refaktoringu | Verraes [15], Fowler [7] |
| Učící křivka DDD | **částečně doložená** – existují výroky autorit, ne měření | Fowler [8], Evans [1] |
| Nedostupnost doménového experta | **doložená jako předpoklad metody**, ne jako měřená bolest | Evans [1] – DDD stojí na „creative collaboration of domain practitioners and software practitioners" |
| Konkrétní týmy, které DDD opustily, a proč | **nedoloženo** | Nenalezen žádný citovatelný postmortem; viz sekci 9 |
| „Bug rate po DDD refaktoringu klesá" | **nedoloženo** | Žádná studie; DORA [25] měří dodávku, ne architekturu |
| „Technické selhání DDD je vzácné" (`:1003`) | **nedoloženo** | Tvrzení o rozdělení příčin selhání bez jakéhokoli zdroje |

## 4. Symfony / PHP specifika

- **Transakce (A1).** Doctrine dokumentace [26] doporučuje `EntityManager#wrapInTransaction($func)`
  před ručním `beginTransaction()`/`commit()`, protože „ensure developers never accidentally
  forget rollback logic". Klíčový nedopověděný detail: při výjimce během `flush()` se transakce
  rolluje **a EntityManager se zavře** – „If you intend to start another unit of work after an
  exception has occurred you should do that with a new EntityManager." Ukázka na `:92`–`:112`
  výjimku přehazuje výš a nechává volajícího s nepoužitelným EM.
- **Custom types (A3).** DBAL 4 vyžaduje čtyři metody, `getName()` už mezi nimi není [27].
  Ukázka je v tomto ohledu správná. Sporný je datový formát – viz sekci 5.
- **Embeddables (A3).** Dokumentovaným limitem je, že „Embeddables can only contain properties
  with basic `@Column` mapping" [28]; nullable embeddable dokumentace řeší doporučením
  inicializovat objekt v konstruktoru. Mapované fieldy embeddable jsou v DQL dotazovatelné
  tečkovou notací.
- **Identita (A5).** `NONE` je podle dokumentace [29] doslova ekvivalent vynechání atributu:
  „NONE is the same as leaving off the `#[GeneratedValue]` entirely." Ukázka tedy funguje,
  ale je zbytečně upovídaná. Pozor i na související BC break: `AUTO` u PostgreSQL s DBAL 4
  nově znamená `IDENTITY`, ne `SEQUENCE` [16].
- **Dědičnost (A6).** Dokumentace [30] potvrzuje, že `#[DiscriminatorMap]` musí být na kořenové
  entitě, a u JOINED varuje: „This strategy inherently requires multiple JOIN operations…
  which can have a negative impact on performance." Od ORM 3 navíc nedeklarovaná dědičnost
  entit vyhazuje `MappingException` [16].
- **Outbox (B1).** Chování, na kterém ukázka stojí, dokumentace [31] potvrzuje: v `onFlush`
  je u nově persistované entity nutné volat `$unitOfWork->computeChangeSet(...)`, a v `postFlush`
  „`EntityManager::flush()` can NOT be called safely inside its listeners".
- **Formuláře (D1).** Tvrzení „`FormType` ve Symfony chce mutable objekt" je dnes nepřesné.
  Dokumentace [32] popisuje `empty_data` jako closure vracející objekt s povinnými argumenty
  konstruktoru, a to jako preferovanou variantu. Immutable command lze naplnit přímo z formuláře.
- **Cascade (chybí v kapitole).** Doctrine varuje: „Cascade operations are performed in memory…
  pulling object graphs into memory on cascade can cause considerable performance overhead"
  a „Do not blindly apply `cascade=all`" [24]. Pro agregát s kolekcí dětí je to každodenní past
  a kapitola ji nezmiňuje.
- **EasyAdmin (chybí v kapitole).** EasyAdmin 5 vyžaduje „Doctrine ORM entities (Doctrine ODM
  is not supported)" [33]. To je konkrétní, ověřitelná podoba bolesti „framework chce CRUD
  entity": nad agregátem s privátními settery admin rozhraní nepostavíte bez obcházení.

## 5. Sporné a chybně podávané body

**`fetch: 'EAGER'` „načte v jednom JOIN" (`:238`).** Eager loading kolekce nevydá JOIN, ale
dávkovaný druhý dotaz, a v mapování platí globálně. Stejný nález má studie kapitoly 16 (tam
G5 a P1-2). Doporučení: sjednotit s kapitolou 16 – fetch join v DQL jako první volba, `EAGER`
v mapování až poslední, s vysvětlením mechanismu.

**Výjimka při lazy loadingu (`:229`–`:231`).** Kapitola tvrdí `ORMInvalidArgumentException`
„ve starších verzích". Zdroj [17] ukazuje, že inicializace proxy na neexistující entitu vyhazuje
`EntityNotFoundException` v obou režimech; `ORMInvalidArgumentException` se v `ProxyFactory`
používá pro konfigurační chyby. Doporučení: opravit název výjimky.

**`MoneyType` do jednoho `VARCHAR(50)` ve formátu `"12345_CZK"` (`:173`).** Formát znemožňuje
`SUM()`, `ORDER BY` i index nad částkou a míchá dvě dimenze do jednoho sloupce. Money je
přitom učebnicový případ pro `#[Embedded]` (dvě skalární property, žádná asociace) – tedy pro
mechanismus, který sekce hned v úvodu odmítá jako nedostatečný. Doporučení: buď příklad změnit
na embeddable a custom type demonstrovat na VO, které do dvou sloupců nejde (např. polymorfní
platba), nebo formát rozdělit do dvou sloupců a v textu přiznat, proč.

**Identita v konstruktoru agregátu (`:293`–`:302`).** Zde jde o rozpor uvnitř knihy. Kanonický
tvar je `Order::place(OrderId $id, CustomerId $customerId)` – `basic_concepts.md:574`,
`aggregate_design.md:383`, `event_storming.md:250`, `lesser_known_patterns.md:870`. Kapitola 20
místo toho ukazuje `Order::__construct(CustomerId)`, který si ID vyrábí sám. Navíc `Order` zde
nedědí `AggregateRoot`. Vnější literatura přitom nabízí ještě třetí variantu (Noback [12]:
`nextIdentity()` na repozitáři). Doporučení: sjednotit s kanonickým tvarem a variantu
„repozitář jako zdroj identity" zmínit jako alternativu, ne ji mlčky nahradit.

**Idempotency klíč generovaný při odeslání (`:551`).** V přímém rozporu s dokumentací Symfony
[18]. Klíč musí být odvozen z byznys události (např. `orderId` + typ operace), aby přežil
dvojí odeslání téhož logického příkazu. Kapitola tak řeší duplicitu z retry, ale ne duplicitu
z dvojího kliknutí – a to je ta, kterou uživatel reálně vyrobí. Doporučení: přepsat odstavec
a v ukázce klíč odvodit deterministicky.

**Ruční transakce bez zmínky o zavřeném EM (`:92`–`:112`).** Viz sekci 4. Doporučení: doplnit
`wrapInTransaction()` jako výchozí tvar a warn callout o zavřeném EM. Stejnou pastí se zabývá
studie kapitoly 15 (P2-5), takže řešení má být formulováno stejně.

**„Discriminator map narušuje Open/Closed Principle" (`:325`–`:327`).** Argument je slabý:
nový subtyp znamená novou třídu tak jako tak, a u varianty „enum + JSON" se stejný switch
objeví v doménové metodě. Přiznat by se měl skutečný náklad discriminator map – migrace schématu
a nullable sloupce u single table [30] – ne principiální porušení OCP.

**„Technické selhání DDD je vzácné. Mnohem častější bývá, že tým vzor nepochopí…" (`:1003`).**
Tvrzení o kauzalitě selhání bez zdroje. Kapitola má na jeho podporu 73 řádků a čtyři odstavce
bez jediné citace. Doporučení: buď doložit (Evans [1]: „Many projects do modeling work without
getting much real benefit in the end."), nebo přeformulovat na zkušenostní tvrzení.

**Metriky v E1 (`:1013`–`:1019`).** „Moduly po DDD refaktoringu mají nižší bug rate" je
predikce podaná jako fakt; „bugů na 1000 řádků" je navíc metrika, kterou DDD refaktoring
systematicky zkresluje (mění se jmenovatel). DORA [25] mezitím rozšířil sadu na pět metrik
(change lead time, deployment frequency, failed deployment recovery time, change fail rate,
deployment rework rate). Doporučení: metriky nechat, kauzální slib odstranit a odkázat na DORA
jako na zdroj definic.

**Duplicity napříč knihou.** B1 (Outbox) opakuje kapitolu 15 – přičemž studie kap. 15 doporučuje
listener variantu doplnit *tam* (P2-2). D3 (Voter) opakuje kapitolu 11, která sama tvrdí, že
kap. 20 „autorizaci zmiňuje jen letmo" (`authorization_in_ddd.md:25`); ve skutečnosti tu je
plná ukázka. E2 (Strangler) opakuje kapitolu 18. A3 opakuje 10.07. C3 (ACL) je téma, které
studie kap. 18 chce rozvinout tam. Rozhodnutí o vlastnictví témat je nutné udělat jednou
a napříč všemi pěti kapitolami.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | sporné | `:238` | `fetch: 'EAGER'` popsán jako JOIN | Opravit mechanismus, sjednotit s kap. 16 |
| G2 | zastaralé | `:229`–`:231` | Chybný název výjimky u lazy loadingu | `EntityNotFoundException` [17] |
| G3 | zastaralé | `:229` | Nativní lazy objects podány jako riziko, ne jako cílový stav ORM 3.5+/4.0 | Doplnit `enableNativeLazyObjects` [16] |
| G4 | nepodložené | `:92`–`:112` | Ruční transakce bez zmínky o zavřeném EM | `wrapInTransaction()` + warn callout [26] |
| G5 | chybí | 20.01 A2 | ORM 3 zrušil částečný `flush($entity)` i `clear($name)` | Dvě věty do A2 [16] |
| G6 | sporné | `:173` | Money jako `"12345_CZK"` v jednom sloupci | Embeddable, nebo dva sloupce |
| G7 | sporné | `:293`–`:315` | Identita v `__construct`, mimo kanonický `Order::place()` | Sjednotit; zmínit `nextIdentity()` [12] |
| G8 | chybí | 20.01 | Cascade persist/remove a orphanRemoval jako past agregátu s kolekcí | Nová podsekce A7 [24] |
| G9 | sporné | `:325` | „Discriminator map narušuje OCP" | Nahradit reálným nákladem (migrace, nullable sloupce) |
| G10 | nadbytečné | `:345`–`:457` | B1 duplikuje kapitolu 15 | Zkrátit na odkaz + specifika listeneru |
| G11 | sporné | `:551` | Idempotency klíč z dispatch time | Odvodit z byznys události [18] |
| G12 | mělké | `:651`–`:675` | B4 nezmiňuje `RecoverableMessageHandlingException` ani FIFO garanci transportu | Doplnit [18] |
| G13 | chybí | 20.02 | Chybějící vazba na eventual consistency v UI (kap. 12.12) | Odkaz + tři věty o dopadu na uživatele |
| G14 | mělké | `:682`–`:700` | C1 nezmiňuje spor always-valid vs. deferred validation | Doplnit Khorikova [14] |
| G15 | sporné | `:717`, `:729` | `place()` jako instanční metoda; `\DomainException` s českým textem | Sjednotit s kanonickým API a pojmenovanými výjimkami |
| G16 | nepodložené | `:806`–`:832` | Čtyři praktiky proti driftu bez zdroje | Připsat Evansovi (Continuous Integration) [1] |
| G17 | chybí | 20.03 | Refaktoring špatně vedené hranice Bounded Contextu | Nová sekce; opora Verraes [15] |
| G18 | zastaralé | `:841`–`:846` | „FormType chce mutable objekt" | `empty_data` closure [32] |
| G19 | zastaralé | `:924` | Podpis `process()` a návrat `OrderResponse` mimo API resource | Sjednotit s API Platform 4.3 [20] |
| G20 | zastaralé | `:986` | `voteOnAttribute()` bez `?Vote $vote` | Doplnit Symfony 7+/8.1 [19] |
| G21 | nadbytečné | `:944`–`:999` | D3 duplikuje kapitolu 11 | Zkrátit na odkaz do `/autorizace-v-ddd` |
| G22 | chybí | 20.04 | EasyAdmin a admin rozhraní nad agregátem | Nová podsekce [33] |
| G23 | nepodložené | `:1003` | „Technické selhání DDD je vzácné" | Doložit, nebo přeformulovat |
| G24 | nepodložené | `:1013`–`:1019` | „Moduly po DDD refaktoringu mají nižší bug rate" | Odstranit kauzální slib, odkázat DORA [25] |
| G25 | nepodložené | `:1022` | „Přidání platby trvá 3 týdny" bez rámce | Označit jako *Ilustrativní scénář* |
| G26 | nepodložené | `:613` | Retence dedup tabulky 7–30 dní | Odvodit z konfigurace retry, ne z čísla |
| G27 | nepodložené | `:1069`, `:1073` | 3–6 měsíců, 12–24 měsíců ve FAQ | Označit jako řádový odhad |
| G28 | chybí | 20.05 | Učící křivka a náklad na zaškolení týmu | Nová podsekce E4; opora [8], [1] |
| G29 | chybí | 20.05 | Nedostupnost doménového experta jako provozní realita | Podsekce nebo explicitní odkaz na `/kdy-nepouzivat-ddd#unclear-domain` |
| G30 | chybí | 20.05 | Kdy a proč tým DDD opustí; jak to poznat včas | Sekce psaná jako zkušenost, bez fabrikovaných čísel |
| G31 | mělké | `:1042`–`:1060` | E3 (bus factor) má dvě opatření a odkaz zpět na C4 | Rozšířit o Evansovu Continuous Integration a code review proti glosáři |
| G32 | chybí | celá kapitola | Žádný diagram, žádná sekce zdrojů | Doplnit alespoň jeden diagram a zdroje |
| G33 | nadbytečné | `:745`–`:805` | C3 (ACL) je téma, které studie kap. 18 chce rozvinout tam | Rozhodnout vlastnictví tématu |
| G34 | chybí | úvod | Kapitola neodkazuje na kap. 7, 10, 11, 12, 16 – tedy na ty, které nejvíc doplňuje | Doplnit rozcestník do úvodu |

## 7. Doporučení k přepisu

**P1-1 — Opravit šest technicky chybných tvrzení.**
`EAGER` = JOIN (`:238`), název výjimky u lazy loadingu (`:229`), idempotency klíč z dispatch
time (`:551`), chybějící zmínka o zavřeném EM po výjimce (`:92`–`:112`), podpis
`voteOnAttribute()` (`:986`), „FormType chce mutable objekt" (`:841`). Všech šest je čtenářem
přímo opsatelných do produkce a u dvou z nich (idempotence, transakce) je následkem tichá ztráta
dat. Rozsah: `oprava šesti odstavců a tří code bloků`. (G1, G2, G4, G11, G18, G20)

**P1-2 — Sjednotit kódové ukázky s kanonickým API knihy.**
`Order::place()` jako statická továrna, `extends AggregateRoot`, pojmenované výjimky místo
`\DomainException`, identita předaná zvenčí. Kapitola 20 je jedno ze dvou míst v knize, kde se
`place()` používá jako instanční metoda, a jediné, kde si agregát vyrábí ID v konstruktoru.
Čtenář, který kapitoly čte v pořadí, tu narazí na jiný model než v kapitolách 5, 7 a 10.
Rozsah: `přepis dvou code bloků (A5, C2)`. (G7, G15)

**P1-3 — Rozhodnout vlastnictví duplicitních témat a kapitolu o ně zkrátit.**
Outbox (kap. 15), Voter (kap. 11), Strangler Fig (kap. 18), custom typy (kap. 10), ACL
(kap. 18 podle tamní studie). Dnes je to ~250 řádků textu, který jinde v knize existuje
v lepší podobě. Uvolněný prostor je přesně ten, který potřebuje blok E. Rozsah:
`zkrácení B1, C3, D3 a E2 na odkaz + specifikum, cca −180 řádků`. (G10, G21, G33)

**P1-4 — Doplnit nebo označit všechna nepodložená čísla a kauzální sliby.**
`:613` (7–30 dní), `:1013`–`:1019` (bug rate), `:1022` (3 týdny), `:1069` (3–6 měsíců),
`:1073` (12–24 měsíců), `:1003` („technické selhání je vzácné"). Kniha má konvenci
„Ilustrativní scénář" pro fiktivní případy a kapitola 20 ji nepoužívá ani jednou, přestože
právě v bloku E argumentuje příklady. Rozsah: `oprava šesti míst + jeden nový štítek`.
(G23, G24, G25, G26, G27)

**P2-1 — Přepsat blok E a doplnit tři chybějící organizační bolesti.**
Učící křivka a náklad zaškolení, nedostupnost doménového experta v provozu (ne v rozhodování,
to řeší kap. 22), a signály, že tým DDD tiše opouští. Blok E má dnes 73 z 1074 řádků, přitom
úvodní věta bloku sama tvrdí, že sem patří většina reálných selhání. Rešerše dává oporu pro
formulace (Evans [1], Fowler [8]), ale nikoli tvrdá data – kapitola musí psát ve zkušenostním
registru a nesmí si čísla vymyslet. Rozsah: `nová sekce ~90 řádků`. (G28, G29, G30, G31)

**P2-2 — Nová sekce: refaktoring špatně vedené hranice Bounded Contextu.**
Nejdražší chyba v DDD a v knize pro ni není místo: kapitola 9 (Context Mapping) učí hranice
navrhovat, kapitola 18 migrovat z CRUD, ale nikdo neřeší, co dělat, když hranice byla vedena
špatně a systém už na ní stojí. Verraes [15] dává rámec tenzí, Fowler [7] slovník. Rozsah:
`nová sekce ~60 řádků` do bloku C. (G17)

**P2-3 — Doplnit dvě chybějící technické třenice: Doctrine cascade a EasyAdmin.**
Cascade a `orphanRemoval` jsou nejčastější zdroj překvapení u agregátu s kolekcí dětí a
dokumentace k nim má explicitní varování [24]. EasyAdmin je konkrétní, ověřitelný případ
„framework chce CRUD entity" [33] – a kapitola dnes žádný takový případ nemá, přestože jde
o nejběžnější admin nástroj v Symfony ekosystému. Rozsah: `dvě podsekce ~35 řádků`. (G8, G22)

**P2-4 — Napojit kapitolu na zbytek knihy.**
Doplnit odkazy na `/navrh-agregatu` (A1), `/implementace-v-symfony` (A3, A5), `/cqrs#eventual-consistency`
(dopad na UI), `/autorizace-v-ddd` (D3), `/testovani-ddd` (C4 living documentation),
`/team-topologies` (E3). Kapitola dnes odkazuje jen na šest cílů a tři z nich jsou v úvodu.
Rozsah: `úprava úvodu + šest odkazů v textu`. (G13, G34)

**P2-5 — Přepracovat A3 a A6 na jeden konzistentní příběh o mapování VO.**
Dnes A3 odmítne embeddables a předvede formát, který znemožní SQL agregace; A6 pak nabídne
„enum + JSON" jako výchozí volbu pro polymorfismus, aniž přizná ztrátu dotazovatelnosti.
Obojí je obhajitelné jen s explicitním vyjmenováním, co se za to platí. Rozsah:
`přepis dvou sekcí, ~50 řádků`. (G6, G9)

**P3-1 — Doplnit diagram.** Kapitola je jediná v Praxi bez jediného vizuálu. Nabízí se schéma
„kde v request/worker cyklu která bolest vzniká" – od controlleru přes flush a outbox po
worker. Rozsah: `jeden diagram + 5 řádků`. (G32)

**P3-2 — Zvážit sekci zdrojů.** Kapitola nemá žádné citace; v knize je to sice běžné (zdroje
mají 2 z 26 kapitol), ale právě u kapitoly, která tvrdí, co v praxi bolí, by seznam
odlišil doložené od zkušenostního. Rozsah: `~20 řádků`. (G32)

## 8. Otevřené otázky pro autora

1. **Má kapitola zůstat katalogem 20 položek?** Číslo je v decku, meta description i katalogu
   (`src/Catalog/Chapters.php:46`). Po škrtu duplicit (P1-3) a doplnění bloku E jich bude jiný
   počet. Buď číslo z metadat zmizí, nebo se položky doplní na dvacet nově.
2. **Kdo vlastní Outbox listener?** Studie kapitoly 15 doporučuje `onFlush` variantu doplnit
   tam (její P2-2). Kód už existuje zde. Přesunout, nebo v kap. 15 odkázat sem?
3. **Kolik prostoru dostane blok E?** Zadání i úvodní věta bloku říkají, že sem patří většina
   reálných selhání. Vyrovnat poměr na ~30 % kapitoly znamená nárůst o zhruba 200 řádků
   a posun kapitoly od technické příručky k eseji.
4. **Píše se o opuštění DDD bez dat?** Rešerše nenašla citovatelný postmortem. Buď se sekce
   napíše výslovně jako zkušenost bez nároku na obecnost, nebo se vynechá.
5. **Zůstane Money jako ukázka custom typu?** Pokud ano, formát je nutné rozdělit do dvou
   sloupců; jednodušší je ukázku vyměnit za polymorfní platbu a Money nechat kapitole 10.
6. **Je D2 (API Platform) v roce 2026 ještě „třenice"?** Dokumentace API Platform DTO cestu
   sama doporučuje. Sekce možná patří přerámovat na „jak to udělat", ne „proč to bolí".

## 9. Bibliografie

### Ověřené zdroje
- [1] Evans, E. — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, Domain
Language, Inc., 2015. https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf
— staženo přes `curl`, text extrahován `pdftotext`, ověřeno 2026-09-04. Citace ověřeny přímo
v textu (sekce Bounded Context, Ubiquitous Language, Continuous Integration).
- [2] Vernon, V. — *Effective Aggregate Design, Part I: Modeling a Single Aggregate*, 2011.
https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_1.pdf — `curl` + `pdftotext`, 2026-09-04.
- [3] Vernon, V. — *Effective Aggregate Design, Part II: Making Aggregates Work Together*, 2011.
https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_2.pdf — `curl` + `pdftotext`, 2026-09-04.
- [4] Vernon, V. — *Effective Aggregate Design, Part III: Gaining Insight Through Discovery*, 2011/2012.
https://www.dddcommunity.org/wp-content/uploads/files/pdf_articles/Vernon_2011_3.pdf — `curl` + `pdftotext`, 2026-09-04.
- [5] Fowler, M. — *OrmHate*, 2012-05-08. https://martinfowler.com/bliki/OrmHate.html — WebFetch, 2026-09-04.
- [6] Fowler, M. — *Strangler Fig Application*, revize 2024-08-22. https://martinfowler.com/bliki/StranglerFigApplication.html — WebFetch, 2026-09-04.
- [7] Fowler, M. — *Bounded Context*, 2014-01-15. https://martinfowler.com/bliki/BoundedContext.html — WebFetch, 2026-09-04.
- [8] Fowler, M. — *Domain Driven Design*, 2020-04-22. https://martinfowler.com/bliki/DomainDrivenDesign.html — WebFetch, 2026-09-04.
- [9] Dahan, U. — *Race Conditions Don't Exist*, 2010-08-31. https://udidahan.com/2010/08/31/race-conditions-dont-exist/ — WebFetch, 2026-09-04.
- [10] Dahan, U. — *Clarified CQRS*, 2009-12-09. https://udidahan.com/2009/12/09/clarified-cqrs/ — WebFetch, 2026-09-04.
- [11] Seemann, M. — *CQS versus server generated IDs*, 2014-08-11. https://blog.ploeh.dk/2014/08/11/cqs-versus-server-generated-ids/ — WebFetch, 2026-09-04.
- [12] Noback, M. — *When and where to determine the ID of an entity*, 2018-05-29. https://matthiasnoback.nl/2018/05/when-and-where-to-determine-the-id-of-an-entity/ — WebFetch, 2026-09-04.
- [13] Noback, M. — *Doctrine ORM and DDD aggregates*, 2018-06-19. https://matthiasnoback.nl/2018/06/doctrine-orm-and-ddd-aggregates/ — WebFetch, 2026-09-04.
- [14] Khorikov, V. — *Always-Valid Domain Model*, 2021-01-12. https://enterprisecraftsmanship.com/posts/always-valid-domain-model/ — WebFetch, 2026-09-04.
- [15] Verraes, M. — *Tensions when Designing Evolvable Bounded Contexts*, 2021-04-09. https://verraes.net/2021/04/tensions-when-designing-evolvable-bounded-contexts/ — WebFetch, 2026-09-04.
- [16] Doctrine ORM — `UPGRADE.md`, větev 3.6.x. https://raw.githubusercontent.com/doctrine/orm/3.6.x/UPGRADE.md — `curl`, 2026-09-04. Ověřeny sekce: „Deprecate not using native lazy objects on PHP 8.4+", „BC BREAK: Remove ability to merge detached entities", „Removed ability to partially flush/commit", „Removed ability to partially clear", „AUTO keyword … defaults to IDENTITY for PostgreSQL", „Undeclared entity inheritance now throws a MappingException".
- [17] Doctrine ORM — `src/Proxy/ProxyFactory.php`, větev 3.6.x. https://raw.githubusercontent.com/doctrine/orm/3.6.x/src/Proxy/ProxyFactory.php — `curl`, 2026-09-04. Ověřeno: větev `isNativeLazyObjectsEnabled()` s `newLazyGhost()` a `EntityNotFoundException` v obou initializerech.
- [18] Symfony — *Messenger: Sync & Queued Message Handling*, doc/current. https://symfony.com/doc/current/messenger.html — WebFetch, 2026-09-04. Ověřeno: at-least-once, idempotence a nevhodnost UUID z dispatch time, `RecoverableMessageHandlingException`, `UnrecoverableMessageHandlingException`, FIFO, Doctrine transport.
- [19] Symfony — *How to Use Voters to Check User Permissions*, doc/current. https://symfony.com/doc/current/security/voters.html — WebFetch, 2026-09-04. Ověřeno: `?Vote $vote` (Symfony 7+), `#[AsTaggedItem]` priorita (8.1), `CacheableVoterInterface`.
- [20] API Platform — *State Processors*, verze 4.3. https://api-platform.com/docs/core/state-processors/ — WebFetch, 2026-09-04.
- [21] Packagist API — `https://packagist.org/packages/<vendor>/<package>.json` — `curl`, 2026-09-04. Měsíční stažení: doctrine/orm 5 490 821; symfony/messenger 4 657 660; api-platform/core 963 791; easycorp/easyadmin-bundle 416 490; patchlevel/event-sourcing 30 027; broadway/broadway 17 605 (`abandoned: true`); prooph/event-store 17 017; ecotone/ecotone 13 649.
- [22] Noback, M. — *DDD entities and ORM entities*, 2022-04-21. https://matthiasnoback.nl/2022/04/ddd-entities-and-orm-entities/ — WebFetch, 2026-09-04.
- [23] Doctrine ORM — *Improving Performance*, 3.6.8. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/improving-performance.html — WebFetch, 2026-09-04.
- [24] Doctrine ORM — *Working with Associations* (cascade, orphanRemoval). https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/working-with-associations.html — WebFetch, 2026-09-04.
- [25] DORA (Google Cloud) — *DORA's software delivery metrics: the four keys*, aktualizace 2026-01-05. https://dora.dev/guides/dora-metrics-four-keys/ — WebFetch, 2026-09-04. Pozor: sada je dnes pětiprvková.
- [26] Doctrine ORM — *Transactions and Concurrency*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/transactions-and-concurrency.html — WebFetch, 2026-09-04.
- [27] Doctrine DBAL — *Types*, 4.4.4. https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/types.html — WebFetch, 2026-09-04.
- [28] Doctrine ORM — *Separating Concerns using Embeddables*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/embeddables.html — WebFetch, 2026-09-04.
- [29] Doctrine ORM — *Basic Mapping* (identifier generation strategies). https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/basic-mapping.html — WebFetch, 2026-09-04.
- [30] Doctrine ORM — *Inheritance Mapping*. https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/inheritance-mapping.html — WebFetch, 2026-09-04.
- [31] Doctrine ORM — *Events* (onFlush, postFlush). https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/events.html — WebFetch, 2026-09-04.
- [32] Symfony — *Using empty_data to Configure the Underlying Data*. https://symfony.com/doc/current/form/use_empty_data.html — WebFetch, 2026-09-04.
- [33] EasyAdmin — *Symfony Backends with EasyAdmin*, verze 5.x. https://symfony.com/bundles/EasyAdminBundle/current/index.html — WebFetch, 2026-09-04. Ověřen požadavek na Doctrine ORM entity; požadavky na gettery/settery a práci s ne-Doctrine daty se z indexové stránky ověřit nepodařilo.

### Doověřeno druhým průchodem (2026-09-04)

**Nový nález (fakt. chyba) – `ddd_pain_points.md:238`.** Tabulka tvrdí: „Kolekce vždy potřebná
s agregátem | `fetch: ’EAGER’` na asociaci – načte v jednom JOIN“. Doctrine to u kolekcí takto
nedělá. Dokumentace *Working with Objects* rozlišuje podle typu asociace: „Eager loading for
many-to-one and one-to-one associations is using either a LEFT JOIN or a second query for fetching
the related entity eagerly“, zatímco „Eager loading for many-to-one associations uses a second
query to load the collections for several entities at the same time“ a „For eagerly loaded
Many-To-Many associations one query has to be made for each collection“.

Řádek 238 mluví výslovně o **kolekci**, tedy o to-many asociaci – a tam `EAGER` JOIN negeneruje,
nýbrž pouští druhý dotaz. Slib „v jednom JOIN“ je nesplněný a čtenář, který podle něj řeší N+1,
dostane jiné chování, než čeká. Navazující řádek 239 je naopak v pořádku: `getWithItems()`
s ručním fetch joinem v DQL JOIN skutečně udělá.

**Oprava:** u to-many psát „druhý dotaz pro všechny kolekce najednou (ne N+1, ale ani JOIN)“;
JOIN slibovat jen u to-one asociací, a i tam s výhradou „LEFT JOIN nebo druhý dotaz“.
Zdroj: https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/working-with-objects.html

### Neověřené / nedohledané

- **Případové studie týmů, které DDD opustily.** Nenalezen žádný podepsaný, dohledatelný
  postmortem popisující opuštění DDD a jeho důvody. Sekce G30 se proto nesmí opřít o čísla.
  K ručnímu dohledání: záznamy z DDD Europe (experience report track) a Explore DDD.
- **Kvantifikace učící křivky.** Neexistuje dohledané měření „za jak dlouho tým dosáhne
  produktivity v DDD". Výroky autorit ([8]) jsou kvalitativní.
- **Souvislost DDD refaktoringu a bug rate.** Nenalezena žádná studie. DORA [25] měří výkon
  dodávky, ne architektonický styl; převzetí jeho metrik jako důkazu přínosu DDD by bylo
  nadinterpretací.
- **Vernon — *Implementing Domain-Driven Design* (2013) a Khononov — *Learning Domain-Driven
  Design* (2021).** Obě knihy k tématu kapitoly mají relevantní kapitoly, ale v této rešerši
  nebyly k dispozici v ověřitelné podobě; k ručnímu dohledání jsou zejména Vernonovy pasáže
  o integraci a Khononovovy o „DDD in the real world".
- **EasyAdmin a agregát s privátními settery.** Konkrétní chování EasyAdminu nad entitou bez
  veřejných setterů se z dokumentace ověřit nepodařilo; před psaním podsekce (P2-3) je nutné
  ověřit experimentem, ne odhadem.
- **Retence deduplikační tabulky (`:613`).** Číslo 7–30 dní se nepodařilo navázat na žádnou
  konfigurační výchozí hodnotu Messengeru ani brokeru.
- **Symfony Workflow a doménový model – DOVĚŘENO 2026-09-04: oficiální stanovisko neexistuje,
  ale existuje otevřená diskuse.** Dokumentace komponenty se k DDD přímo nevyjadřuje; nejblíž je
  věta, že definování workflow „will keep your domain logic in one place and not spread all over
  your application“, což tvrzení kapitoly spíš podpírá. Otázka je ale v Symfony vedena jako
  otevřená: issue **symfony/symfony-docs#10819 „[Workflow] How workflows makes sense with DDD“**
  přesně tento rozpor řeší, s argumentem, že v DDD má model vědět všechno sám o sobě, takže místa
  a přechody patří do entity, ne do konfigurace vedle ní.

  **Doporučení: tvrzení na `:739` ponechat, ale neopírat je o oficiální doporučení Symfony,
  které neexistuje.** Odkaz na issue je poctivější a zároveň užitečnější – ukazuje, že napětí
  mezi konfiguračním workflow a doménovým modelem je známé a nedořešené.

  dokumentace Symfony Workflow nebyla v této rešerši ověřena; před přepisem C2 doporučuji
  ověřit, co Workflow vyžaduje od objektu (marking store, property vs. metoda).

### Doověřeno osmým a devátým kolem (2026-09-04 až 05)

**OPRAVENO — `clear($entityName)` chybu nevyvolá.** Kapitola tvrdila, že argument „vyvolá chybu".
PHP přebytečný argument uživatelské metody ignoruje: `$em->clear('Order')` v ORM 3.6.8 **tiše
odpojí celou Identity Map**. To je horší past než chyba. Ověřeno spuštěním (`ormtest/d_clear.php`).

**OPRAVENO — příliš široký catch v IdempotencyMiddleware.** `catch (UniqueConstraintViolationException)`
obepínal i `$stack->next()->handle()`. Unique violation vzniklá **uvnitř handleru** (duplicitní
e-mail při flushi) se tak vydávala za duplicitní zprávu, Messenger ji potvrdil a zpráva se
ztratila — přesně scénář, před kterým callout varuje. Catch nyní kryje jen INSERT.

**OVĚŘENO — Evans v *DDD Reference* o projektech bez výsledku.** Tvrzení sedí.

Doplněny chybějící importy v bloku s plnou `use` sekcí (`OrderId`, `CustomerId`, `OrderResponse`).
