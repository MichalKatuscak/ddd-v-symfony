# Studie: Základní koncepty DDD

- **Kapitola:** `content/chapters/basic_concepts.md` (č. 06, kategorie Taktika, 634 řádků)
- **Cesta:** /zakladni-koncepty
- **Typ kapitoly:** definiční
- **Datum studie:** 2026-09-03

Kapitola má v knize zvláštní postavení: podle `CLAUDE.md` je kanonickým místem, kde se
zavádí API používané ve všech dalších kapitolách (`AggregateRoot` s `record()` /
`releaseEvents()`, `Email` s `public readonly $value`, `Money` s `amountInCents`,
`Order::place()`). Studie proto vedle rešerše ověřuje i to, zda kapitola tuto roli
skutečně plní. Kotva `#aggregate-root-lifecycle`, na kterou odkazují kapitoly 07, 12,
13 a 24, v kapitole existuje (`basic_concepts.md:531`).

---

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| 06.01 Bounded Contexts | ř. 22–36 (15) | Kontext je vymezená oblast s jedním modelem a slovníkem; kapitola s ním dál pracuje jen jako s hranicí | Fowler, BoundedContext | Duplikuje ch01 a ch03; sama přiznává, že téma neotevírá |
| 06.02 Ubiquitous Language | ř. 38–49 (12) | Jednotný slovník mezi vývojáři a experty | Fowler, UbiquitousLanguage | Totéž — 12 řádků odkazů |
| 06.03 Entity | ř. 51–129 (79) | Entitu určuje identifikátor, ne atributy; `equals()` porovnává jen ID; rozbor `==` vs. `===` | domainlanguage.com/ddd/ (rozcestník) | Rozbor rovnosti je nejsilnější část kapitoly |
| 06.04 Value Objects | ř. 131–189 (59) | VO se identifikuje celou hodnotou; neměnnost; `final readonly`; validace formátu vs. byznys pravidla | tentýž rozcestník | Konvence knihy (`fromUserInput`, dvě úrovně výjimek) tu je zavedena správně |
| 06.05 Agregáty | ř. 191–382 (192) | Agregát = transakční hranice konzistence; vstup přes kořen; enum pro stavy | tentýž rozcestník | Nejdelší sekce; 100 řádků kódu `Order` + `OrderItem` + `OrderStatus` |
| 06.06 Repozitáře | ř. 384–415 (32) | Rozhraní vypadající pro doménu jako kolekce; implementace v infrastruktuře | bez zdroje | Nejmělčí sekce kapitoly |
| 06.07 Domain Services | ř. 417–491 (75) | Logika bez vlastníka mezi agregáty; bez stavu; anti-vzor `PaymentService` | bez zdroje | Anti-callout je věcný a užitečný |
| 06.08 Domain Events | ř. 493–529 (37) | Neměnný záznam minulé události; minulý čas; nese data potřebná k popisu změny | bez zdroje | Ukázka `OrderCreated` |
| 06.09 Lifecycle | ř. 531–619 (89) | `AggregateRoot`, `record()` v doménové metodě, `releaseEvents()` po flushi | bez zdroje | Definuje API celé knihy; kotva pro čtyři další kapitoly |
| FAQ | ř. 621–634 | Šest otázek k rozdílům mezi bloky | – | Zmiňuje Primitive Obsession, který tělo kapitoly nezavádí |

Kapitola je nerovnoměrná. Agregátům a lifecyclu dává 281 řádků (44 % rozsahu), zatímco
repozitáře odbývá na 32 řádcích a doménové události na 37. Dvě úvodní sekce o
strategických tématech zabírají 27 řádků a jen odkazují jinam. Ze sedmi taktických
stavebních bloků, které Evans řadí do části „Building Blocks of a Model-Driven
Design", kapitola samostatně otevírá pět — Factories a Modules chybí úplně (Factory se
mihne jako poznámka v anti-calloutu na ř. 478–490). Faktické tvrzení podkládá kapitola
třemi odkazy, z nichž dva jsou na Fowlerovo bliki a třetí (`[[3]]`) je rozcestník
domainlanguage.com použitý shodně pro Entity, Value Object i Agregát.

---

## 2. Kanonické zdroje k tématu

**Evans, *DDD Reference* (2015)** je pro tuto kapitolu důležitější než modrá kniha,
protože obsahuje redigované shrnutí každého vzoru a je pod licencí CC-BY volně
dostupný [1]. Obsah označuje hvězdičkou tři vzory přidané po roce 2004: **Domain
Events**, **Partnership** a **Big Ball of Mud** („* New term introduced since the 2004
book"). To potvrzuje zadání studie: Domain Events v knize z roku 2003/2004 samostatným
stavebním blokem **nejsou** — do Evansova katalogu vstupují až s Reference.

Klíčové formulace z Reference [1]:

- **Entities:** „Many objects represent a thread of continuity and identity, going
  through a lifecycle, though their attributes may change." A dále: „This means of
  identification may come from the outside, or it may be an arbitrary identifier
  created by and for the system." Evans tedy explicitně připouští obě strategie —
  přirozený i umělý identifikátor.
- **Value Objects:** „Many objects have no conceptual identity … These are the objects
  that describe things." Doporučení: „Treat the value object as immutable. Make all
  operations Side-effect-free Functions that don't depend on any mutable state."
- **Aggregates:** „Cluster the entities and value objects into aggregates and define
  boundaries around each. Choose one entity to be the root … Use the same aggregate
  boundaries to govern transactions and distribution. Within an aggregate boundary,
  apply consistency rules synchronously. Across boundaries, handle updates
  asynchronously."
- **Repositories:** „For each type of aggregate that needs global access, create a
  service that can provide the illusion of an in-memory collection of all objects of
  that aggregate's root type … Provide methods to add and remove objects … Provide
  repositories only for aggregate roots that actually need direct access."
- **Domain Events:** „Something happened that domain experts care about … Domain
  events are ordinarily immutable, as they are a record of something in the past. In
  addition to a description of the event, a domain event typically contains a timestamp
  for the time the event occurred and the identity of entities involved in the event.
  Also, a domain event often has a separate timestamp indicating when the event was
  entered into the system … When useful, an identity for the domain event can be based
  on some set of these properties."
- **Services:** „Sometimes, it just isn't a thing. … add an operation to the model as a
  standalone interface declared as a service. Define a service contract … State these
  assertions in the ubiquitous language of a specific bounded context."
- **Factories:** „Shift the responsibility for creating instances of complex objects
  and aggregates to a separate object … Create an entire aggregate as a piece,
  enforcing its invariants."

**Value Object je starší než DDD.** Fowler jej popisuje v *Patterns of Enterprise
Application Architecture* (2002) a jeho bliki záznam [2] (verze z 14. 11. 2016) dělí
objekty na „value objects and reference objects, depending on how I tell them apart" a
uvádí, že „value objects should be immutable". Evansovo „reference object" jako
alternativní jméno pro Entity (Reference to uvádí jako „aka Reference Objects") je
přímý most k Fowlerově terminologii. Primitive Obsession jako code smell pochází z
Fowler, *Refactoring* (1999).

**Repository nepochází od Evanse.** Vzor v katalogu P of EAA napsali Edward Hieatt a
Rob Mee, publikováno 5. 3. 2003, definice zní: „Mediates between the domain and data
mapping layers using a collection-like interface for accessing domain objects" [3].
Fowler sám odkazuje na Evanse jako na rozšířenější zpracování. Obě definice se shodují
na „collection-like" charakteru — na tom, co kapitola říká také.

**Domain Event má dva nezávislé kořeny.** Fowler jej popsal 12. 12. 2005 v rámci
„Further Enterprise Application Architecture development" jako „Captures the memory of
something interesting which affects the domain" [4]; text sám označuje za nedokončený
materiál. Nezávisle na tom Udi Dahan publikoval 14. 6. 2009 „Domain Events – Salvation"
[5], která popularizovala vzor v .NET komunitě. Evans v poděkování Reference výslovně
děkuje Gregu Youngovi a Udimu Dahanovi za CQRS a Event Sourcing a Fowlerovi za to, že
„often providing the definitive documentation of emerging patterns" [1].

**Vernon, *IDDD* (2013)** doplňuje dvě věci, které kapitola nemá: rozdíl mezi
collection-oriented a persistence-oriented repozitářem (kap. 12) a čtyři strategie
vzniku identity entity — uživatel dodá hodnotu, aplikace ji vygeneruje, vygeneruje ji
persistence, nebo přichází z jiného bounded contextu — spolu s rozlišením „early" a
„late" generování [6].

---

## 3. Stav praxe a posuny

**Domain Events zdomácněly a rozštěpily se.** Rozlišení domain event (uvnitř bounded
contextu, jazykem daného kontextu) a integration event (kontrakt na hranici, Published
Language) dnes patří ke standardní výbavě; formalizoval jej mimo jiné Cesar de la Torre
v .NET architektonických příručkách Microsoftu [7]. Kapitola tento rozdíl nezmiňuje,
přestože její vlastní kód posílá `OrderCreated` na `eventBus` — což může znamenat obojí.

**Obsah události: tenká vs. tlustá.** Fowler v roce 2017 rozdělil event-driven přístupy
na Event Notification, Event-Carried State Transfer, Event Sourcing a CQRS [8].
Doporučení „událost nese všechna data" je popisem Event-Carried State Transfer, tedy
jedné ze čtyř variant, ne obecného pravidla. Uvnitř jednoho bounded contextu se dnes
spíš doporučuje tenká událost s identifikátory.

**Kdy událost publikovat.** Existují dva zavedené tábory. Jimmy Bogard („A better
domain events pattern", 13. 5. 2014) doporučuje události zaznamenat na entitě a
odeslat je **před** commitem, uvnitř téže transakce, s odůvodněním, že vedlejší efekty
mají spadnout do stejné logické transakce [9]. Druhý tábor publikuje **po** commitu a
řeší riziko ztráty transakčním outboxem. Kapitola prezentuje druhou variantu jako
„závazné pořadí" a první nezmiňuje.

**Identifikátory generuje aplikace, ne databáze.** Matthias Noback („When and where to
determine the ID of an entity", 2018) argumentuje, že entita bez identity nemůže při
vzniku zaznamenat událost, a proto se ID musí vytvořit dříve — nejlépe metodou
`nextIdentity()` na repozitáři [10]. To je dnes v PHP komunitě většinový postoj a
zapadá do `Uuid::v7()`, které kapitola používá.

**Always-valid domain model.** Diskuse Jeffrey Palermo vs. Vladimir Khorikov o tom, zda
se má objekt bránit vzniku v nevalidním stavu, je stále živá [11]. Kapitola princip
implementuje (validace v konstruktoru `Email`, ř. 150), ale nepojmenovává jej ani
nepřiznává, že jde o volbu, ne o samozřejmost.

**PHP se za posledních pět let posunul právě v tom, co Value Objecty potřebují.**
Enumy a `readonly` properties (8.1), `readonly` třídy (8.2), reinicializace `readonly`
v `__clone()` (8.3), asymetrická viditelnost a property hooks (8.4), `clone with`
(8.5). Doporučení „udělej z VO `final readonly class`" bylo před rokem 2022 nemožné a
dnes je výchozí volba; s ním ale přišel problém wither metod, který vyřešily až 8.3
a 8.5. Detaily v sekci 4.

---

## 4. Symfony / PHP specifika

**Verze.** Symfony 8.0 vyšlo 27. 11. 2025, minimální PHP je 8.4 [12]. Doctrine ORM 3.0
vyšlo 3. 2. 2024 [13]. PHP 8.5 vyšlo 20. 11. 2025 [14]. Kniha cílí na PHP 8.4, takže
8.5 patří do poznámek, ne do hlavních ukázek.

**`readonly` a jeho cena.** `readonly` vlastnost jde přiřadit jen jednou z rozsahu
deklarující třídy. Pro `Email` s jediným polem to nevadí. Pro `Money::withCurrency()`
nebo `DateRange::withEnd()` to znamenalo psát `new self(...)` s výčtem všech polí. PHP
8.3 (RFC readonly amendments) povolil reinicializaci `readonly` vlastnosti uvnitř
`__clone()` [15]. PHP 8.5 přidal `clone with` v podobě `clone($this, ['alpha' =>
$alpha])`, které dokumentace popisuje přímo jako podporu wither vzoru pro `readonly`
třídy [14]. Kapitola `readonly` doporučuje bez zmínky o tom, co to komplikuje.

**Property hooks vs. `readonly` (PHP 8.4).** Manuál je explicitní: „Property hooks are
incompatible with `readonly` properties. If there is a need to restrict access to a
`get` or `set` operation in addition to altering its behavior, use asymmetric property
visibility." [16] To je praktický důsledek: VO s validací v hooku a VO s `readonly` se
navzájem vylučují; kniha volí `readonly` a měla by to říct.

**Asymetrická viditelnost (PHP 8.4).** `public private(set) string $name` dává entitě
veřejné čtení a soukromý zápis bez getteru. Pro entity typu `User` (kapitola 06.03) to
je přímá alternativa k dvojici `private $name` + `name()`. Kapitola PHP 8.4 v deku
zmiňuje, ale žádný jeho prostředek nepoužívá.

**`symfony/uid`.** Dokumentace označuje UUIDv7 za doporučenou verzi („It's recommended
to use this version over UUIDv1 and UUIDv6 because it provides better entropy and a
more strict chronological order") a od Symfony 7.4 ji `UuidFactory` používá jako
výchozí [17][18]. ULID je podporován rovnocenně (`Symfony\Component\Uid\Ulid`,
26 znaků, lexikograficky řaditelné), ale dokumentace jej nestaví jako výchozí volbu —
což přesně odpovídá konvenci knihy. Symfony 7.4 navíc přidalo mikrosekundovou přesnost
UUIDv7 podle RFC 9562 a `MockUuidFactory` pro deterministické testy [18]. Doctrine typy:
`UuidType::NAME` (`'uuid'`) a `UlidType::NAME` (`'ulid'`).

**Messenger a pořadí publikace.** Symfony má pro problém „odeslat až po dokončení
současného handleru" dedikovaný mechanismus — `DispatchAfterCurrentBusStamp` a
middleware `dispatch_after_current_bus` [19]. Kapitola řeší totéž ručně cyklem po
`flush()` a `dispatch_after_current_bus` nezmiňuje ani v odkazu.

**Doctrine a `readonly`.** Hydratace `readonly` vlastností má v Doctrine dokumentovanou
historii problémů, zejména v kombinaci s dědičností a s embeddables [20]. Kapitola
persistenci vědomě odsouvá do ch10, ale jednu větu „takto navržené VO nejde uložit
naivně" by unesla.

---

## 5. Sporné a chybně podávané body

**1. „Agregát je transakční hranice."** Kapitola to podává jako definici (ř. 195–197).
Evans je opatrnější: agregát definuje hranici invariantů a *doporučuje* stejnou hranici
použít i pro transakce a distribuci („Use the same aggregate boundaries to govern
transactions and distribution") [1]. Ztotožnění obou hranic je Vernonovo zostření
(pravidlo „modify one aggregate per transaction"). Kniha to řeší v ch07; v ch06 by
stačila půlvěta, že jde o doporučení, ne o definici.

**2. „Událost nese všechna data potřebná k popisu změny."** (ř. 497–498) Podle Fowlera
[8] jde o jednu ze čtyř variant. Vlastní ukázka `OrderCreated` je přitom tenká — nese
dvě ID a časové razítko. Text a kód si přímo protiřečí.

**3. „Pořadí je závazné."** (ř. 614) Bogardův vzor [9] a implementace v
eShopOnContainers publikují uvnitř transakce; Symfony pro tuto variantu poskytuje
podporu [19]. Kapitola má právo doporučit jednu variantu, ale ne prohlásit druhou za
neexistující.

**4. Repository jako kolekce vs. `save()`.** Evansova formulace mluví o „methods to add
and remove objects" [1]; Vernon tuto podobu nazývá collection-oriented a odlišuje ji od
persistence-oriented rozhraní se `save()` [6]. Kapitola tvrdí „pro doménu vypadá jako
kolekce v paměti" (ř. 387–388) a hned ukazuje `save()`, tedy persistence-oriented
variantu. Doporučení: `save()` ponechat (s Doctrine to je pragmatické), ale volbu
přiznat a rozdíl pojmenovat.

**5. Rozdíl Repository vs. DAO.** Rozšířený mýtus je, že jde o synonyma. Praktický
rozdíl: DAO pracuje v pojmech tabulek a nabízí CRUD nad nimi, repozitář pracuje v
pojmech agregátů a vrací plně sestavené agregáty. Kapitola pojem DAO vůbec nezmiňuje,
takže mýtus nevyvrací.

**6. Domain Events jako „původní" Evansův blok.** Rozšířený omyl, který kniha zatím
nikde neopravuje. Podklad je jednoznačný: v obsahu Reference je Domain Events označeno
hvězdičkou „New term introduced since the 2004 book" [1]. Uvést to je levné a zvyšuje
důvěryhodnost celé kapitoly.

**7. Doménová služba bez rozhraní.** Evans mluví o „standalone interface declared as a
service" [1]. Kapitola ukazuje `final class ShippingFeeService` bez interface (ř. 436).
To je v PHP obhajitelná volba (interface se zavádí, až když je víc implementací), ale
je to odchylka od původní formulace a text ji nekomentuje.

**8. Validace v konstruktoru VO.** Kapitola ji zavádí jako konvenci knihy (ř. 179–189),
což je správně. Chybí ale zmínka, že jde o pozici v doložené diskusi (always-valid
domain model) [11] a že alternativa — validace ve vstupní vrstvě a „hloupý" VO —
existuje a má své zastánce.

---

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | nadbytečné | `basic_concepts.md:22–49` | Sekce 06.01 a 06.02 (27 ř.) opakují ch01 a ch03 a samy přiznávají, že téma neotevírají | Sloučit do jednoho úvodního odstavce s odkazy; ušetřit ~20 ř. |
| G2 | chybí | celá kapitola | Factory není samostatnou sekcí, přestože patří mezi Evansovy building blocks a deck slibuje „sadu stavebních bloků" | Buď krátká sekce 06.xx s odkazem na ch08 `#factories`, nebo explicitní věta „co do této kapitoly nepatří a proč" |
| G3 | chybí | `basic_concepts.md:191–382` | `Money`, `Currency` a `Money::zero()` se používají (ř. 289–292, 447–448), ale definované jsou až v ch03 (`context_mapping.md:141`) a ch21 (`anti_patterns.md:365, 377, 391`) | V ch06 doplnit kanonickou definici `Money` + `Currency` (nebo alespoň odkaz na jedno místo) |
| G4 | sporné | `context_mapping.md:141` vs. `anti_patterns.md:377` | Dvě různé definice `Money`: `final readonly class` vs. `final class`; `zero()` jen v druhé | Sjednotit; kanonickou verzi umístit do ch06 podle `CLAUDE.md` |
| G5 | sporné | `basic_concepts.md:214–228` | `Order` má veřejný konstruktor a nedědí `AggregateRoot`; kanonická `Order::place()` se objeví až na ř. 574 | Sjednotit už v 06.05: `extends AggregateRoot`, privátní konstruktor, `place()` |
| G6 | sporné | `basic_concepts.md:240` vs. `:342` | `addItem(..., Money $price)` vs. getter `unitPrice()`; `CLAUDE.md` předepisuje `Money $unitPrice` | Přejmenovat parametr na `$unitPrice` |
| G7 | nadbytečné | `basic_concepts.md:330` | `OrderItem::$orderId` se ukládá, ale nikde se nečte a nemá getter | Odstranit, nebo zdůvodnit jednou větou |
| G8 | sporné | `basic_concepts.md:545` vs. `implementation_in_symfony.md:194` | Dvě definice `AggregateRoot`: `$recordedEvents` / nefinální metody vs. `$domainEvents` / `final protected record()` | Sjednotit; ch10 má odkazovat na ch06, ne definovat znovu |
| G9 | mělké | `basic_concepts.md:384–415` | Sekce Repozitáře (32 ř.) neuvádí pravidlo „jeden repozitář na kořen agregátu", rozdíl collection- vs. persistence-oriented ani rozdíl proti DAO | Rozšířit o ~25 ř. podle [1] a [6] |
| G10 | nepodložené | `basic_concepts.md:408` | `findByCustomerId(): array` bez `@return list<Order>`, zatímco zbytek kapitoly anotace používá (ř. 216, 298) | Doplnit anotaci |
| G11 | chybí | celá kapitola | Vznik identity: kdo a kdy vytváří `OrderId`. `PaymentId::generate()` (ř. 484) a `OrderId::generate()` (ř. 603) se používají bez definice; první definice je až `aggregate_design.md:333` | Nová podsekce k 06.03: strategie podle Vernona [6], generování mimo entitu [10], `Uuid::v7()` |
| G12 | chybí | `basic_concepts.md:53–56` | Debata přirozený vs. umělý identifikátor. Evans obě možnosti výslovně připouští [1] | 3–5 vět v 06.03 |
| G13 | mělké | `basic_concepts.md:493–529` | Neodlišuje domain event od integration eventu, přestože ukázka posílá událost na `eventBus` | Odstavec + odkaz na `/context-mapping` a `/outbox-pattern` |
| G14 | sporné | `basic_concepts.md:497–498` | „Událost obsahuje všechna data potřebná k popisu změny" popisuje Event-Carried State Transfer [8]; vlastní ukázka `OrderCreated` je tenká | Přeformulovat na „nese identifikátory a to, co příjemce nutně potřebuje", s odkazem na volbu |
| G15 | sporné | `basic_concepts.md:614` | „Pořadí je závazné" ignoruje druhý tábor (dispatch uvnitř transakce) [9] a Symfony podporu [19] | Zmírnit; jednou větou zmínit `dispatch_after_current_bus` |
| G16 | mělké | `basic_concepts.md:510–520` | `OrderCreated` nemá vlastní identitu; Evans doporučuje identitu události pro deduplikaci a druhé časové razítko [1]. Při at-least-once doručení přes Messenger to je praktický problém | Doplnit `eventId` do ukázky nebo poznámku s odkazem na ch15 |
| G17 | nepodložené | `basic_concepts.md:497` | „Název je vždy v minulém čase" bez zdroje | Podložit [1] / [4] |
| G18 | nepodložené | `basic_concepts.md:55, 135, 197` | Odkaz `[[3]] domainlanguage.com/ddd/` je rozcestník, použitý shodně pro tři různé definice | Nahradit odkazem na DDD Reference PDF [1] a citovat konkrétní formulace |
| G19 | mělké | `basic_concepts.md:131–189` | Primitive Obsession se objeví jen ve FAQ (ř. 625), v těle kapitoly chybí | Pojmenovat v 06.04, odkaz na ch21 |
| G20 | zastaralé | `basic_concepts.md:176–177` | „Třída je `final readonly`" bez zmínky o ceně: `readonly` blokuje wither metody, PHP 8.3 to zmírnil, PHP 8.5 vyřešil přes `clone with` [14][15] | Poznámka v 06.04 (~10 ř.) |
| G21 | chybí | `basic_concepts.md:51–129` | PHP 8.4 asymetrická viditelnost (`public private(set)`) jako alternativa k `private` + getter; property hooks jsou s `readonly` neslučitelné [16] | Krátký callout; kniha cílí na PHP 8.4 a tento prostředek nikde nepoužívá |
| G22 | chybí | `basic_concepts.md:531–619` | Pojem reconstitution není zaveden, přestože `CLAUDE.md` z něj dělá závaznou konvenci („reconstitution must not emit events"); vysvětlen je až v `lesser_known_patterns.md:1021` | Dvě věty v 06.09 + odkaz |
| G23 | chybí | `basic_concepts.md:179–189` | Validace v konstruktoru je zavedena jako konvence, ale nepojmenována jako pozice v doložené diskusi (always-valid domain model) [11] | Jedna věta + odkaz |
| G24 | mělké | `basic_concepts.md:35, 48` | Oba diagramy patří strategickým sekcím; pět taktických bloků nemá žádný obrázek | Přidat schéma hranice agregátu a sekvenci `record → save → flush → dispatch` |
| G25 | mělké | `basic_concepts.md:436` | `ShippingFeeService` je `final class` bez rozhraní; Evans mluví o „standalone interface" [1] | Jedna věta, proč v PHP interface nezavádíme předem |

---

## 7. Doporučení k přepisu

**P1-1 — Sjednotit ukázku `Order` v 06.05 s kanonickým API knihy.**
Kapitola je podle `CLAUDE.md` zdrojem pravdy pro `Order::place()`, `AggregateRoot` a
`addItem(ProductId, int, Money $unitPrice)`. Dnes v 06.05 stojí `Order` s veřejným
konstruktorem, bez dědičnosti a s parametrem `$price`; správné API se objeví o 350
řádků dál. Čtenář, který kapitolu čte lineárně, si nejdřív zapamatuje verzi, kterou
konvence knihy zakazuje. *Přepis ukázky v 06.05 + navazujícího odstavce.*

**P1-2 — Doplnit kanonickou definici `Money` a `Currency` do 06.04.**
Kapitola je používá třikrát bez definice; definované jsou v ch03 a ch21, navzájem
odlišně (`final readonly class` vs. `final class`, `zero()` jen v jedné). Definiční
kapitola o hodnotových objektech, která svůj vlastní hlavní příklad VO nedefinuje, roli
neplní. *Nová ukázka ~35 řádků + sjednocení dvou existujících.*

**P1-3 — Sjednotit `AggregateRoot` mezi ch06 a ch10.**
Dvě definice se stejným veřejným API, ale odlišnými vnitřnostmi a modifikátory. Zvolit
jednu (doporučeně variantu z ch10 s `final` metodami a názvem `$domainEvents`), umístit
ji do ch06 a v ch10 na ni odkázat. *Oprava dvou ukázek.*

**P1-4 — Odstranit rozpor mezi tvrzením o obsahu události a ukázkou.**
Text říká „obsahuje všechna data potřebná k popisu změny", kód nese dvě ID. Podle
Fowlera [8] jde o dvě různé strategie a volba mezi nimi je rozhodnutí, ne pravidlo.
Ponechat tenkou událost a text přeformulovat je levnější než přepisovat ukázku.
*Oprava dvou vět + jedna nová věta o volbě.*

**P1-5 — Zmírnit „pořadí je závazné" a doplnit druhý tábor.**
Tvrzení, že jediné správné pořadí je save → flush → dispatch, není podložené; Bogardova
varianta [9] je zavedená a Symfony pro ni má `dispatch_after_current_bus` [19]. Sekce
06.09 je kotvou pro čtyři další kapitoly, takže chyba se šíří. *Přepis závěrečného
odstavce 06.09, ~8 řádků.*

**P1-6 — Nahradit odkaz `[[3]]` konkrétními citacemi z DDD Reference.**
Jeden rozcestník použitý jako zdroj pro tři různé definice není citace. Reference je
volně dostupná pod CC-BY a obsahuje přesné formulace pro každý blok. *Oprava tří
odkazů + volitelně 3 krátké citace.*

**P2-1 — Nová podsekce o vzniku identity entity.**
Kapitola používá `::generate()` na třech místech a nikde nevysvětlí, kdo identitu
vytváří a proč ne databáze. Materiál je: čtyři strategie podle Vernona [6], early vs.
late generování, Nobackův argument o zaznamenání události při vzniku [10], `Uuid::v7()`
a `MockUuidFactory` pro testy [18]. Zároveň to je přirozené místo pro debatu přirozený
vs. umělý identifikátor. *Nová podsekce v 06.03, ~40 řádků.*

**P2-2 — Rozšířit sekci 06.06 Repozitáře.**
Nejmělčí sekce kapitoly. Doplnit: jeden repozitář na kořen agregátu, ne na entitu;
rozdíl collection-oriented a persistence-oriented [6] a přiznání, že `save()` je druhá
varianta; rozdíl proti DAO. *Rozšíření o ~25 řádků.*

**P2-3 — Zkrátit 06.01 a 06.02 na jeden úvodní odstavec.**
27 řádků, které téma neotevírají a odkazují jinam, přitom kapitola má mělká místa
jinde. Uvolněný prostor jde do P2-1 a P2-2. *Sloučení dvou sekcí, −20 řádků.*

**P2-4 — Doplnit prostředky PHP 8.4/8.5 pro hodnotové objekty.**
Kniha cílí na PHP 8.4 a Symfony 8, ale VO staví jen na `readonly`, který je dostupný
od 8.2. Chybí: cena `readonly` u wither metod a její řešení (`__clone()` v 8.3,
`clone with` v 8.5), neslučitelnost property hooks s `readonly` [16], asymetrická
viditelnost pro entity. Toto je jediná kapitola, kde takový přehled dává smysl.
*Callout v 06.04 ~15 řádků + poznámka v 06.03.*

**P2-5 — Odlišit domain event od integration eventu.**
Bez tohoto rozdílu čtenář neví, zda `OrderCreated` smí opustit `OrderManagement`.
Kapitola je vstupní branou k ch12, ch13 a ch15, kde se rozdíl předpokládá. *Odstavec
~10 řádků v 06.08.*

**P2-6 — Pojmenovat Primitive Obsession v těle 06.04.**
FAQ pojem používá, tělo kapitoly ne. Je to zároveň nejsrozumitelnější motivace pro
Value Object u čtenáře přicházejícího z CRUD. *Dvě až tři věty + odkaz na ch21.*

**P3-1 — Přidat dva diagramy k taktickým blokům.**
Schéma hranice agregátu (co je uvnitř, kudy vede přístup) a sekvence `record → save →
flush → dispatch`. Kapitola má dnes dva diagramy, oba ke strategickým sekcím, které se
navrhuje zkrátit. *Dva nové `.puml` + SVG.*

**P3-2 — Zavést pojem reconstitution v 06.09.**
`CLAUDE.md` z něj dělá závaznou konvenci, kapitola ho nepojmenuje. *Dvě věty.*

**P3-3 — Doplnit identitu události a druhé časové razítko.**
Evans obojí zmiňuje [1], Messenger doručuje at-least-once. *Jedno pole v ukázce +
poznámka s odkazem na ch15.*

**P3-4 — Pojmenovat princip always-valid domain model.**
Kapitola jej implementuje, aniž by řekla, že jde o volbu [11]. *Jedna věta + odkaz.*

**P3-5 — Přepočítat `reading_time`.**
18 minut u 634 řádků s deseti ukázkami kódu vypadá podhodnoceně; po rozšíření podle P2
bude odhad ještě dál od skutečnosti. *Frontmatter, jedno číslo.*

---

## 8. Otevřené otázky pro autora

1. **Má být 06.05 kanonickým `Order`, nebo mají kapitoly ukazovat vývojové stupně?**
   Dnes existují nejméně čtyři podoby `Order::place()` (ch04, ch06, ch08, ch09) s
   různými signaturami. Buď jedna kanonická signatura a ostatní jako výřezy, nebo
   explicitní věta „příklad roste napříč knihou" (ch10 takovou větu má na ř. 23).
2. **Kde má definitivně žít `Money`?** `CLAUDE.md` popisuje jeho API, ale kód je v ch03
   a ch21. Ch06 je logické místo; přesun ale znamená zásah do dvou dalších kapitol.
3. **Kolik prostoru dát Factory v ch06?** Ch08 má vlastní sekci `#factories` na 270
   řádcích. Otázka je, zda ch06 potřebuje krátkou definici pro úplnost katalogu, nebo
   stačí jediný odkaz.
4. **Zkrátit 06.01 a 06.02, nebo je nechat jako opakování?** Pro čtenáře, který začne
   knihu u taktiky, mají smysl. Pro čtenáře lineárního jsou to 27 řádků navíc.
5. **Kam s PHP 8.5?** Kniha cílí na 8.4. `clone with` řeší problém, který kapitola má,
   ale zatím je mimo cílovou verzi. Poznámka „od 8.5", nebo mlčet?
6. **Jak daleko jít s alternativními názory?** Sekce 5 nabízí několik míst (pořadí
   dispatche, always-valid model, collection vs. persistence repository), kde kniha
   dnes mlčí. Definiční kapitola snese omezené množství „na jedné straně / na druhé
   straně", než přestane být definiční.

---

## 9. Bibliografie

Datum přístupu u všech webových zdrojů: 2026-09-03.

### Ověřené zdroje

- `[1]` Eric Evans — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, Domain Language, Inc., 2015 (poděkování datováno červen 2014), licence CC-BY 4.0. https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf — PDF staženo a text ověřen; citované formulace pocházejí z částí Acknowledgements, Contents a II. „Building Blocks of a Model-Driven Design".
- `[2]` Martin Fowler — *Value Object* (bliki), 14. 11. 2016. https://martinfowler.com/bliki/ValueObject.html
- `[3]` Edward Hieatt, Rob Mee — *Repository*, katalog *Patterns of Enterprise Application Architecture*, 5. 3. 2003. https://martinfowler.com/eaaCatalog/repository.html
- `[4]` Martin Fowler — *Domain Event*, 12. 12. 2005 (autor jej označuje za nedokončený materiál). https://martinfowler.com/eaaDev/DomainEvent.html
- `[5]` Udi Dahan — *Domain Events – Salvation*, 14. 6. 2009. https://udidahan.com/2009/06/14/domain-events-salvation/
- `[6]` Vaughn Vernon — *Implementing Domain-Driven Design*, Addison-Wesley, 2013 (kap. 5 Entities – strategie vzniku identity; kap. 12 Repositories – collection-oriented vs. persistence-oriented).
- `[7]` Cesar de la Torre — *Domain Events vs. Integration Events in Domain-Driven Design and microservices architectures*, Microsoft DevBlogs. https://devblogs.microsoft.com/cesardelatorre/domain-events-vs-integration-events-in-domain-driven-design-and-microservices-architectures/
- `[8]` Martin Fowler — *What do you mean by „Event-Driven"?*, únor 2017, a přednáška *The Many Meanings of Event-Driven Architecture*, GOTO Chicago 2017. https://martinfowler.com/articles/201701-event-driven.html · https://www.youtube.com/watch?v=STKCRSUsyP0
- `[9]` Jimmy Bogard — *A better domain events pattern*, 13. 5. 2014. https://lostechies.com/jimmybogard/2014/05/13/a-better-domain-events-pattern/
- `[10]` Matthias Noback — *When and where to determine the ID of an entity*, květen 2018. https://matthiasnoback.nl/2018/05/when-and-where-to-determine-the-id-of-an-entity/
- `[11]` Vladimir Khorikov — *Always-Valid Domain Model*, Enterprise Craftsmanship. https://enterprisecraftsmanship.com/posts/always-valid-domain-model/
- `[12]` Symfony — *Symfony 8.0 Release*, vydáno 27. 11. 2025, vyžaduje PHP 8.4+. https://symfony.com/releases/8.0
- `[13]` Doctrine — *Doctrine ORM 3 and DBAL 4 Released*, 3. 2. 2024. https://www.doctrine-project.org/2024/02/03/doctrine-orm-3-and-dbal-4-released.html
- `[14]` PHP — *PHP 8.5 Release Announcement*, 20. 11. 2025 (`clone with` popsané jako podpora wither vzoru pro `readonly` třídy). https://www.php.net/releases/8.5/en.php
- `[15]` PHP RFC — *readonly amendments* (reinicializace `readonly` vlastnosti v `__clone()`, PHP 8.3). https://wiki.php.net/rfc/readonly_amendments
- `[16]` PHP Manual — *Property Hooks*, PHP 8.4 („Property hooks are incompatible with `readonly` properties…"). https://www.php.net/manual/en/language.oop5.property-hooks.php
- `[17]` Symfony — *The UID Component*. https://symfony.com/doc/current/components/uid.html
- `[18]` Symfony — *New in Symfony 7.4: Uid Improvements* (UUIDv7 jako výchozí verze továrny, mikrosekundová přesnost podle RFC 9562, `MockUuidFactory`). https://symfony.com/blog/new-in-symfony-7-4-uid-improvements
- `[19]` Symfony — *Messenger: Transactional Messages* (`DispatchAfterCurrentBusStamp`, middleware `dispatch_after_current_bus`). https://symfony.com/doc/current/messenger/dispatch_after_current_bus.html
- `[20]` Doctrine ORM — hlášené problémy hydratace `readonly` vlastností a embeddables: https://github.com/doctrine/orm/issues/10049 · https://github.com/doctrine/orm/issues/7854
- `[21]` Martin Fowler — *Refactoring: Improving the Design of Existing Code*, Addison-Wesley, 1999 (code smell Primitive Obsession).
- `[22]` Martin Fowler — *Patterns of Enterprise Application Architecture*, Addison-Wesley, 2002 (Value Object, Money).
- `[23]` Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*, Addison-Wesley, 2003.

### Neověřené / nedohledané

- **Přesné znění definic v modré knize (2003).** Studie pracuje s Reference (2015), která je autorem redigovaná a volně dostupná. Formulace v původní knize se místy liší; pro citaci vydání z roku 2003 je nutné ověřit v tištěném textu.
- **Vernon, *IDDD* (2013), kap. 5 a 12 – OVĚŘENO 2026-09-04 z plného textu (vlastní výtisk).
  Obě tvrzení sedí.** Kapitola 5 (*Entities*) vypisuje pod nadpisem *Unique Identity* přesně čtyři
  strategie vzniku identity, a to v tomto pořadí: **User Provides Identity**, **Application
  Generates Identity**, **Persistence Mechanism Generates Identity** a **Another Bounded Context
  Assigns Identity**. Následují podsekce *When the Timing of Identity Generation Matters*,
  *Surrogate Identity* a *Identity Stability* – druhá jmenovaná je pro kapitolu užitečná, protože
  řeší přesně ten rozpor mezi doménovým ID a technickým klíčem ORM.

  Kapitola 12 (*Repositories*) rozdíl collection-oriented vs. persistence-oriented skutečně vede
  a Vernon ho vymezuje takto: *„For times when a collection-oriented style doesn’t work, you will
  need to employ a persistence-oriented, save-based Repository. This will be the case when your
  persistence mechanism doesn’t implicitly track changes.“* Kapitola má i podsekci *Repository
  versus Data Access Object*, která se do knihy hodí.

  **Doporučení: obě citace ponechat a doplnit čísla stran až podle výtisku; obsahově jsou
  doložené.**
- **Khononov, *Learning DDD* (2021).** K definicím Entity/VO/Aggregate byly nalezeny jen sekundární výtahy. Před citací ověřit v originále.
- **`dispatch_after_current_bus` – OVĚŘENO 2026-09-04 ve zdroji.**
  `Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware` existuje ve větvi 8.1
  a jeho vlastní docblock říká: *„Allow to configure messages to be handled after the current bus
  is finished.“* Doplňující detail z CHANGELOGu: od **5.3** už dispatch se stampem
  `DispatchAfterCurrentBusStamp` mimo kontext jiného dispatche nevyhazuje výjimku, takže se stamp
  dá použít i tam, kde si kód není jistý, jestli běží uvnitř handleru.

  Nedoloženo zůstává, že by Symfony tento middleware **doporučovalo** právě pro doménové události –
  dokumentace ho popisuje obecně. **Doporučení: mechanismus popsat jako dostupný nástroj
  s doloženým chováním, ne jako doporučený postup Symfony.**se ze stránky nepodařilo získat. Ověřit ručně.
- **Millett & Tune (2015).** K tématu nebyl v této rešerši použit žádný ověřený úryvek.
