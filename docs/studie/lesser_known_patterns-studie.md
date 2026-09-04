# Studie: Doplňující taktické vzory (Specifications, Domain Services, Factories, Modules)

- **Kapitola:** `content/chapters/lesser_known_patterns.md` (č. 08, kategorie Taktika, 1391 řádků)
- **Cesta:** /mene-zname-vzory
- **Typ kapitoly:** definiční
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| úvod + deck | 21–30 | Čtyři vzory tvoří „druhou polovinu“ Evansova taktického katalogu, praktické průvodce je vynechávají | Evans 2003 (kap. 5, 6, 9), Vernon 2013 (kap. 11) | premisa „druhá polovina“ je nepřesná, viz G20 |
| 08.01 Proč přehlížíme | 31–58 | Vzory tvoří provázanou sadu, vynechání jednoho oslabí ostatní | žádný | teze bez opory, ale je to úvodní rámování |
| 08.02 Specification | 59–625 (567 ř., 41 %) | Definice, kdy/kdy ne, kombinátory, kompozitní kostra, tři doménové specifikace, kompozice, double-dispatch do Doctrine, limity DQL | Evans 2003 kap. 9; Evans & Fowler *Specifications* 1997; Vernon 2013 | 14 z 22 code bloků kapitoly; dominuje kapitolu |
| 08.03 Domain Services | 626–793 (168 ř.) | Evansova tři kritéria, kdy/kdy ne, `MoneyTransferService`, srovnávací tabulka tří typů service | Evans 2003 kap. 5; Vernon 2013 kap. 7 | nejkratší ze čtyř sekcí, přitom kapitola sama tvrdí, že jde o nejzneužívanější vzor |
| 08.04 Factories | 794–1065 (272 ř.) | Named constructor jako preferovaná forma, Factory class pro DI, reconstitution | Evans 2003 kap. 6; Vernon 2013 kap. 11 | callout „Pravidlo (Vernon 2013)“ je chybná atribuce, viz G9 |
| 08.05 Modules | 1066–1302 (237 ř.) | Modul = Bounded Context, adresářová struktura, `composer.json`, `services.yaml`, phparkitect/deptrac | Evans 2003 kap. 5; Vernon 2013 kap. 9; phparkitect, deptrac | `composer.json` tvrzení je věcně chybné, viz G12 |
| 08.06 Vztahy | 1303–1321 | Tabulka vztahu čtyř vzorů k Aggregate / Domain Event / BC | žádný | shrnující, bez nároku na zdroj |
| 08.07 Anti-vzory | 1322–1342 | Devět anti-vzorů v tabulce | žádný | duplikuje varování z jednotlivých sekcí |
| 08.08 Shrnutí + FAQ | 1343–1391 | Rekapitulace + 6 FAQ položek | žádný | FAQ opakuje sporná tvrzení ze sekcí 08.03 a 08.04 |

Kapitola je z 51 % kód (704 z 1391 řádků, 22 code bloků). Nejvíc prostoru dostává Specification, kde
čtenář dostane kompletní ručně psaný framework – rozhraní, abstraktní kompozit, tři kombinátory, tři
doménové specifikace, `QuerySpecification`, repozitář a variantu `AndSpecification` s překladem do DQL.
Naproti tomu Domain Services, tedy vzor, o kterém kapitola sama píše, že se „zneužívá nejčastěji“,
dostanou jediný příklad a jednu tabulku. Modules jsou podány skoro výhradně jako adresářová konvence;
modelová rovina Evansova Modulu (kohezní pojmy, jména v Ubiquitous Language, refaktoring modelu
při vysokém coupling) v kapitole chybí. Odbytá je i vazba na zbytek knihy: sekce 08.06 vztahy jen
tabelizuje, nic nevysvětluje. Kapitola drží knižní konvence u eventů a `AggregateRoot`, ale
u `Money` a `Order::place()` se od kánonu odchyluje (G16, G17).

## 2. Kanonické zdroje k tématu

**Specification.** Vzor pochází z pracovního papíru Erica Evanse a Martina Fowlera *Specifications*,
datovaného na Fowlerově webu **září 1997** [1][2]. Papír definuje tři problémy, které Specification
řeší, a pojmenovává je explicitně:

- **Selection** – „You need to select a subset of objects based on some criteria, and to refresh the selection at various times.“
- **Validation** – „You need to check that only suitable objects are used for a certain purpose.“
- **Construction-to-order** – „You need to describe what an object might do, without explaining the details of how the object does it, but in such a way that a candidate might be built to fulfill the requirement.“

Řešení je jediná metoda `isSatisfiedBy(anObject): Boolean` [1]. Papír dále rozlišuje **tři
implementační strategie**: *Hard Coded Specification* (podtřída per pravidlo, Strategy dle GoF),
*Parameterized Specification* (pravidlo se skládá za běhu z parametrů) a *Composite Specification*
(interpreter s listy a uzly `and`/`or`/`not`). Ke každé uvádí důsledky – Composite je „very flexible,
without requiring many specialized classes“, ale „must invest in complex framework“ [1].

Papír obsahuje dva navazující vzory, které kapitola vůbec nezmiňuje. **Subsumption** srovnává
specifikace mezi sebou (`isGeneralizationOf`) místo srovnání specifikace s kandidátem.
**Partially Satisfied Specification** přidává `remainderUnsatisfiedBy(candidate): Specification`, tedy
odpověď na otázku „co ještě zbývá splnit“ – užitečné pro vysvětlení uživateli, proč pravidlo neprošlo [1].

Papír má i vlastní sekci *When Not to Use Specification*, přesnější než kritéria v kapitole: „If you
find that your object is representing an actual entity in the domain, rather than placing constraints
on some other, possibly hypothetical, entity, you should reconsider the use of this pattern.“
Ilustruje to dvojicí *Route* vs. *Route Specification* [1].

Evans vzor později zařadil do *Domain-Driven Design* (2003), kapitola 9 *Making Implicit Concepts
Explicit* [3]. Pozor na jednu věc, kterou kapitola neuvádí: **v *DDD Reference* (2015) Specification
jako vzor vůbec není** [4]. Evansův destilovaný katalog obsahuje v části II Layered Architecture,
Entities, Value Objects, Domain Events, Services, Modules, Aggregates, Repositories, Factories,
v části III Supple Design (Intention-Revealing Interfaces, Side-Effect-Free Functions, Assertions,
Standalone Classes, Closure of Operations, Declarative Design, Drawing on Established Formalisms,
Conceptual Contours). Specification tam nefiguruje.

**Services.** *DDD Reference* (2015) formuluje vzor takto: „When a significant process or
transformation in the domain is not a natural responsibility of an entity or value object, add an
operation to the model as a standalone interface declared as a service. Define a service contract,
a set of assertions about interactions with the service. […] Give the service a name, which also
becomes part of the ubiquitous language.“ Motto sekce zní „Sometimes, it just isn't a thing.“ [4]

Trojici Evansových kritérií z knihy (2003) přesně cituje Bogard [5]: operace se váže k doménovému
konceptu, který není přirozenou součástí Entity ani Value Objektu; rozhraní je definováno pomocí
jiných prvků doménového modelu; operace je stateless. Kapitola je parafrázuje správně.

**Factories.** *DDD Reference* (2015): „Shift the responsibility for creating instances of complex
objects and aggregates to a separate object, **which may itself have no responsibility in the domain
model but is still part of the domain design**. […] Create an entire aggregate as a piece, enforcing
its invariants.“ [4] Ta vyznačená část přímo odpovídá na otázku, kterou kapitola implicitně obchází:
Factory class *patří* do domény, i když sama nic doménového nemodeluje.

Vernon věnuje Factories kapitolu 11 IDDD. Její sekce se jmenují *Factories in the Model*,
*Factory Method on Aggregate Root* a *Factory on Service* [6]. Zásadní upřesnění: *Factory Method on
Aggregate Root* u Vernona **není** statický named constructor téže třídy. Jde o instanční metodu
existujícího agregátu, která vytvoří **jiný** agregát – kanonické příklady jsou `Forum.startDiscussion()`
vracející `Discussion` a `Product.planBacklogItem()` vracející `BacklogItem` [6][7]. Vzor
„privátní konstruktor + statická `::place()`“ je konvence PHP komunity, jejíž nejcitovanější zdroj
je Mathias Verraes, *Named Constructors in PHP* (12. 6. 2014): „PHP allows only a single constructor
per class. That's rather annoying.“ Verraes explicitně doporučuje privátní konstruktor: „once the
constructor is no longer public, we can choose to refactor all the internals […] as much as we want.“ [8]

**Modules.** *DDD Reference* (2015): „Everyone uses modules, but few treat them as a full-fledged part
of the model. […] Choose modules that tell the story of the system and contain a cohesive set of
concepts. Give the modules names that become part of the ubiquitous language. Modules are part of the
model and their names should reflect insight into the domain. This often yields low coupling between
modules, **but if it doesn't look for a way to change the model to disentangle the concepts**“ [4].
Ta poslední věta je nejdůležitější a v kapitole není: když se moduly navzájem proplétají, řešením je
změnit model, ne přepsat pravidla v phparkitectu. Evans uvádí i alias „(aka Packages)“.

## 3. Stav praxe a posuny

**Specification se v PHP praxi rozdělil na dvě větve.** In-memory predikát (`isSatisfiedBy`) a
query objekt jsou dnes chápány jako dva různé vzory se stejným jménem. Benjamin Eberlei, maintainer
Doctrine, popsal v roce 2013 přechod od exponovaného QueryBuilderu přes Criteria ke Specification:
„Composing Conditions using combinations of Not/And/Or is not possible without a tree structure,
however `Criteria` is just a single object.“ Zároveň jmenuje motivaci pro specifikace – „Removing
duplication of code between different repositories“ [9]. Praxe se ustálila na tom, že *query*
specifikace vrací skládatelný výraz, ne že mutuje QueryBuilder.

**Existuje zavedený balíček.** `happyr/doctrine-specification` (v2.2.0, 28. 7. 2026) má přes 950 tisíc
instalací, vyžaduje PHP >= 7.2 a `doctrine/orm ^2.17 || ^3.0`, spravují ho Tobias Nyholm,
Kacper Gunia a Peter Gribanov [10][11]. Repozitář se rozšíří o `EntitySpecificationRepositoryTrait`
a používá se jako `$repository->match(new IsActive())`; skládá se přes `Spec::andX()` / `Spec::orX()`.
Knihovna odděluje filtrování od `ResultModifier` (hydratace, řazení) – právě to oddělení kapitola
nemá a naráží na jeho absenci v sekci o limitech DQL.

**Modules → modulární monolit.** Evansův Module je dnes v praxi diskutován pod jménem *modular
monolith*. Kamil Grzybek definuje modul jako *business-oriented vertical slice*, který má tři
vlastnosti: nezávislost a zaměnitelnost, úplnost (obsahuje vše potřebné k dodání funkce) a
**dobře definované rozhraní / kontrakt** [12]. Ta třetí vlastnost je posun oproti roku 2003:
nestačí, že se modul jmenuje doménově a nekouká do sousedů; musí mít publikované API, přes které se
do něj vstupuje. Kapitola pracuje jen se zákazem cross-importu.

**Vynucování hranic v CI je dnes standard.** Oba nástroje, které kapitola jmenuje, jsou živé:
phparkitect 1.3.0 (31. 7. 2026, PHP >= 8.0, ~4,75 mil. instalací) [13] a deptrac 4.7.1
(23. 7. 2026, PHP ^8.2, ~10,8 mil. instalací, maintaineři xabbuh a dbrumann) [14]. phparkitect přibyl
oproti roku 2020 *baseline* – seznam existujících porušení, který dovolí nasadit nástroj do zavedeného
projektu bez velkého refaktoringu. To je pro čtenáře migrujícího z CRUD důležitější než samotná syntaxe
pravidel a v kapitole to není.

**Domain Service se v komunitě posunul k opatrnější formulaci.** Místo „Domain Service nemá závislosti“
se dnes rozlišuje mezi *pure* a *impure* doménovou službou (Khorikov) [15], respektive se řeší,
kterou vrstvu má služba obsluhovat (Noback) [16]. Podrobněji v sekci 5.

## 4. Symfony / PHP specifika

**Symfony 8.** Symfony 8.0 vyšlo v listopadu 2025, aktuální stabilní řada je 8.1 (květen 2026);
obě vyžadují PHP >= 8.4.0 [17]. Kapitola cíluje správně.

**Doctrine: Criteria a `Selectable` místo ručního QueryBuilder mutátoru.** `doctrine/collections`
definuje rozhraní `Selectable` s metodou `matching(Criteria): Collection`. Výrazy se staví přes
`Criteria::expr()` a podporují `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `in`, `notIn`, `contains`,
`startsWith`, `endsWith`, `isNull`, `isNotNull`, `memberOf` a kombinátory `andX` / `orX` / `not` [18].
`Doctrine\ORM\EntityRepository` implementuje `Selectable`, takže tatáž `Criteria` funguje nad
databází i nad `ArrayCollection` v paměti. Do složitějšího dotazu se `Criteria` vloží přes
`QueryBuilder::addCriteria()` [19]. To je přesně ten „intermediate representation“, který kapitole
chybí – a je to důvod, proč její `AndSpecification` umí do DQL, zatímco `Or`/`Not` ne.

Limity `Criteria` jsou reálné a stojí za zmínku: EQ a NEQ porovnávají striktně, takže srovnání
`DateTime` instancí se chová jinak než v SQL [18]; nelze vyjádřit vlastní DQL funkce ani joiny.
Pro dotazy, které je potřebují, zůstává vlastní repozitářová metoda správnou volbou – závěr kapitoly
je tedy správný, jen ho podpírá špatná technická cesta.

Drobnost k verzím: od `doctrine/collections` 2.2 jsou konstanty `Criteria::ASC` / `Criteria::DESC`
deprecated ve prospěch enumu `Doctrine\Common\Collections\Order` [20]. Pokud kapitola ukázku
s řazením přidá, musí použít enum.

**PSR-4 a `composer.json`.** PSR-4 mapuje *prefix* namespace na *base directory* a zbytek namespace
překládá na podadresáře [21]. Pro třídu `App\Ordering\Domain\Order` při výchozím mapování
`"App\\": "src/"` tedy vzniká cesta `src/Ordering/Domain/Order.php` – **bez jakékoli úpravy
`composer.json`**. Vlastní PSR-4 kořen per modul má smysl teprve tehdy, když moduly nesedí pod `src/`
(např. `modules/ordering/src/`) nebo když se z modulu má stát samostatný composer balíček.

**`config/services.yaml`.** Výchozí Symfony konfigurace registruje `App\` s `resource: '../src/'`,
takže služby v modulech se autoregistrují samy. Explicitní `tags: ['controller.service_arguments']`
je při `autoconfigure: true` u controlleru dědícího `AbstractController` nadbytečné – autoconfigure
tag doplní automaticky [22][23]. Per-modul výčet v `services.yaml` má smysl pouze tehdy, když chcete
každému modulu nastavit jiná `_defaults` nebo vyloučit doménovou vrstvu z containeru; to je
legitimní důvod, ale kapitola ho neuvádí a prezentuje výčet jako nutnost.

**PHP 8.4 a `assert()`.** Kapitola narrowuje typ kandidáta přes `assert($candidate instanceof Order)`.
V produkci se `assert()` s `zend.assertions=-1` vůbec nezkompiluje, takže nejde o pojistku, jen
o instrukci pro statickou analýzu. Stojí za jednu větu, aby si čtenář nemyslel, že má runtime kontrolu.

## 5. Sporné a chybně podávané body

**1. Smí Domain Service přijmout repozitář?** Kapitola tvrdí kategoricky ne (řádky 725–740, FAQ
„Má Domain Service mít stav?“ na ř. 1370): „Vše ostatní (repozitáře, ClockInterface, Mailer) ji
posouvá do Application nebo Infrastructure vrstvy.“ Khorikov souhlasí u repozitářů, ale připouští
*impure* doménovou službu volající externí systém, pokud je to nutné pro doménové rozhodnutí [15].
Noback jde dál a umísťuje rozhraní repozitáře do Domain vrstvy právě proto, že „won't and shouldn't be
used directly from Infrastructure“ – doménový kód s ním pracovat smí [16]. Vernonova výhrada, na kterou
se často odkazuje, se navíc týká injektování repozitáře do **agregátu**, ne do služby.
*Doporučení:* nedávat to jako zákon. Formulovat jako heuristiku („když služba potřebuje repozitář,
zvažte nejdřív, jestli data nemá dodat volající“) a spor pojmenovat.

**2. Chybná atribuce pravidla „static method preferred“ Vernonovi.** Callout na ř. 1005–1020
a FAQ na ř. ~1372 tvrdí, že Vernon (2013) výslovně preferuje statickou factory metodu na agregátu
a samostatnou Factory class doporučuje až při nutnosti DI. Vernonova sekce *Factory Method on
Aggregate Root* je ale o instanční metodě jednoho agregátu, která vyrábí **jiný** agregát
(`Forum.startDiscussion()`) [6][7]; jeho druhá sekce se jmenuje *Factory on Service*, ne „factory
class only when you need DI“. Vzor, který kapitola popisuje, je PHP konvence doložitelná Verraesem
(2014) [8]. *Doporučení:* rozdělit atribuci – Vernon pro factory metodu na agregátu vyrábějící cizí
agregát, Verraes pro named constructor s privátním konstruktorem.

**3. Anti-vzor „Specification pro každé porovnání“ jde proti zdrojovému papíru.** Kapitola
(ř. 122–130) zakazuje třídy typu `OrderTotalGreaterThanSpecification`. Evans a Fowler přitom
u *Composite Specification* přesně takové generické listy předpokládají – jejich vlastní ukázka
je `MaximumTemperatureSpecification` [1]. Rozdíl je v rovině: doménové jméno má nést *výsledná*
specifikace, listy uvnitř parametrizovaného frameworku generické být smí. *Doporučení:* zachovat
varování, ale doplnit rozlišení „doménová specifikace nahoře, generické listy uvnitř kompozitu“.

**4. Kde žije Factory class.** Kapitola umísťuje `OrderFromCartFactory` do
`src/Ordering/Domain/Factory/` a injektuje jí `CartRepository`, `PricingService` a `ClockInterface` –
tedy přesně ty závislosti, které o dvě sekce dřív označila za signál, že třída nepatří do domény.
*DDD Reference* dává argument, který to smiřuje: Factory „may itself have no responsibility in the
domain model but is still part of the domain design“ [4]. *Doporučení:* explicitně to vyříkat,
jinak si čtenář odnese, že kapitola sama sobě odporuje.

**5. „Druhá polovina Evansova taktického katalogu.“** Deck i shrnutí to tvrdí. V *DDD Reference*
(2015) je Specification z části II vyřazená úplně a část III (Supple Design) obsahuje osm dalších
taktických vzorů, které kapitola nezmiňuje [4]. *Doporučení:* buď premisu zmírnit („čtyři vzory
z Evansova katalogu, které praxe přeskakuje“), nebo doplnit odstavec o Supple Design s odkazem dál.

**6. Sdílení specifikace mezi Bounded Contexty.** Tabulka v 08.06 tvrdí u Specification
„obvykle se nesdílí mezi BC“, přitom kostra (`Specification`, `CompositeSpecification`, kombinátory)
je v ukázkách umístěná do `SharedKernel`. To je v pořádku – sdílí se mechanismus, ne pravidlo – ale
kapitola ten rozdíl nikde neříká.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | `lesser_known_patterns.md:78–107` | Tři použití z původního papíru (Selection / Validation / Construction-to-order) nejsou pojmenovaná; „Kdy použít“ je vlastní čtyřbodový seznam | Převzít trojici z papíru jako kostru sekce, vlastní příklady nechat pod ní |
| G2 | chybí | `:78–85`, `:132–141` | Tři implementační strategie (Hard Coded / Parameterized / Composite) nejsou pojmenované; kapitola skočí rovnou ke kompozitu | Doplnit odstavec s taxonomií; bod 3 v „Kdy použít“ (skládání z DB) je Parameterized |
| G3 | chybí | sekce 08.02 | Subsumption a Partially Satisfied Specification (`remainderUnsatisfiedBy`) nejsou zmíněné | Doplnit krátkou podsekci – tematicky sedí přesně do „méně známých vzorů“ |
| G4 | mělké | `:108–121` | „Kdy NE“ je vlastní seznam bez opory; přesnější kritérium z papíru (spec vs. entita, Route vs. Route Specification) chybí | Doplnit citaci a příklad Route / Route Specification |
| G5 | chybí | `:82–85` | Neuvádí, že *DDD Reference* (2015) Specification vůbec neobsahuje | Doplnit jednou větou – je to relevantní pro tezi o „přehlížených vzorech“ |
| G6 | sporné | `:453–563` | `asDoctrineCriteria(QueryBuilder, string): void` není Criteria a mutující void signatura je příčinou toho, že `Or`/`Not` přeložit nejde | Přepsat na návrat skládatelného výrazu (`Criteria` / `Expr`), teprve pak popsat skutečné limity |
| G7 | chybí | sekce 08.02 | Ekosystémové řešení (`happyr/doctrine-specification`, ~950k instalací, ORM ^3.0) není zmíněno | Doplnit odstavec „hotové řešení“ před ruční implementaci |
| G8 | sporné | `:725–740`, `:1370` | Zákaz repozitáře a `ClockInterface` v Domain Service je podán jako pravidlo; zdroje se rozcházejí | Přeformulovat jako heuristiku, spor pojmenovat (Khorikov vs. Noback) |
| G9 | nepodložené | `:1005–1020`, `:1372` | Callout „Pravidlo (Vernon 2013): static method preferred“ – Vernonova sekce je o factory metodě vyrábějící *jiný* agregát | Opravit atribuci; rozdělit na Vernon (cizí agregát) a Verraes (named constructor) |
| G10 | chybí | sekce 08.04 | Verraes, *Named Constructors in PHP* (2014) není citován, přestože je to zdroj doporučeného vzoru | Doplnit citaci |
| G11 | sporné | `:947–1004` | `OrderFromCartFactory` v `Domain/Factory/` injektuje repozitář – kapitola si sama odporuje vůči 08.03 | Doplnit argument z *DDD Reference* („no responsibility in the model, still part of the domain design“) |
| G12 | zastaralé/chybné | `:1183–1215` | Tvrzení, že pro modulovou strukturu je nutné mapovat každý modul jako vlastní PSR-4 kořen | Opravit: `"App\\": "src/"` už `App\Ordering\Domain\Order` → `src/Ordering/Domain/Order.php` řeší; vlastní kořen jen pro moduly mimo `src/` |
| G13 | nadbytečné | `:1216–1244` | `tags: ['controller.service_arguments']` je při `autoconfigure: true` a `AbstractController` zbytečný; výchozí `App\: '../src/'` moduly registruje sám | Zkrátit ukázku a uvést skutečný důvod per-modul konfigurace (jiná `_defaults`, vyloučení Domain vrstvy) |
| G14 | mělké | `:1068–1090` | Modul je podán jako adresářová konvence; Evansova modelová rovina („modules are part of the model“, „if coupling isn't low, change the model“) chybí | Doplnit odstavec s citací z *DDD Reference* |
| G15 | chybí | sekce 08.05 | Kontrakt / publikované API modulu (Grzybek) a vazba na modulární monolit chybí; „Souvisí“ neodkazuje na `/ddd-a-microservices` | Doplnit odstavec o modulovém kontraktu + křížový odkaz na kap. 19 |
| G16 | sporné | `:114`, `:516` | `->amount` na `Money`; kanonicky je `public readonly int $amountInCents`. `Money::czk()` a `isGreaterThanOrEqual()` nejsou v knize nikde definované | Sjednotit s kánonem z `CLAUDE.md`, případně API doplnit v kap. 06 |
| G17 | sporné | `:864–900` | `Order::place(CustomerId, array $items, DateTimeImmutable)` – kánon říká `addItem(ProductId, int, Money)`; signatura `place()` se v knize liší v pěti kapitolách | Sjednotit alespoň v rámci hubu Taktika |
| G18 | nadbytečné | sekce 08.04 vs. `basic_concepts.md:417–492`, `implementation_in_symfony.md:663–780` | Reconstitution i factory metoda jsou vyloženy i jinde bez explicitní dělby | Doplnit jednu větu o dělbě práce na začátek sekcí 08.03 a 08.04 |
| G19 | nadbytečné | poměry sekcí | 08.02 = 567 ř. (41 %), 08.03 = 168 ř. (12 %); kód tvoří 51 % kapitoly | Zkrátit Specification (kombinátory `Or`/`Not` lze shrnout), rozšířit Domain Services |
| G20 | sporné | deck, `:29`, `:1345` | Premisa „druhá polovina Evansova taktického katalogu“ – Supple Design (8 vzorů) není zmíněný, Specification v *DDD Reference* není | Zmírnit premisu nebo doplnit odstavec o Supple Design s odkazem dál |
| G21 | sporné | `:122–130` | Anti-vzor „Specification pro každé porovnání“ jde proti Composite Specification z původního papíru | Doplnit rozlišení rovin: doménové jméno nahoře, generické listy uvnitř kompozitu |
| G22 | mělké | `:132–141` | Jediný diagram na čtyři vzory a 1391 řádků (průměr kapitol knihy jsou 2–3) | Doplnit diagram hranice Domain/Application/Infrastructure Service a diagram modulové struktury |
| G23 | nepodložené | `:1288` | „Stačí 6 měsíců a hot-fix tlak – refaktoring zpět je pak týdenní práce“ | Odhad bez zdroje – buď vypustit číslo, nebo přeformulovat kvalitativně |
| G24 | mělké | `:324`, `:353`, `:386`, `:505` | `assert($candidate instanceof Order)` – v produkci se `assert()` s `zend.assertions=-1` nezkompiluje | Doplnit poznámku, že jde o nástroj pro statickou analýzu, ne o runtime pojistku |

## 7. Doporučení k přepisu

**P1-1 — Opravit atribuci pravidla o Factory metodách.**
Callout „Pravidlo (Vernon 2013): static method preferred“ a odpovídající FAQ přisuzují Vernonovi
pravidlo, které v IDDD v této podobě není. Vernonova *Factory Method on Aggregate Root* popisuje
agregát, který vyrábí jiný agregát; kapitola tím pádem uvádí čtenáře v omyl u jednoho ze svých
hlavních doporučení. Zdroj pro named constructor s privátním konstruktorem je Verraes (2014).
*Rozsah: přepis calloutu (~16 řádků), oprava jedné FAQ odpovědi, doplnění citace.*

**P1-2 — Opravit tvrzení o `composer.json` a zeštíhlit `services.yaml`.**
Tvrzení, že modulová struktura vyžaduje vlastní PSR-4 kořen na modul, je věcně chybné a čtenář podle
něj zbytečně mění funkční konfiguraci. Stejně tak explicitní `controller.service_arguments` je při
`autoconfigure: true` nadbytečný. Obojí je snadno ověřitelné a v knize o Symfony jde o viditelnou chybu.
*Rozsah: přepis sekce 08.05 „composer.json autoload“, ~35 řádků.*

**P1-3 — Přepsat double-dispatch na Doctrine `Criteria` / `Selectable`.**
Metoda `asDoctrineCriteria(QueryBuilder $qb, string $alias): void` se jmenuje po Criteria, ale žádnou
Criteria nepoužívá; mutující void signatura je přímou příčinou toho, že složené specifikace do DQL
přeložit nejde. Doctrine přitom nabízí `Criteria::expr()` s `andX`/`orX`/`not`, `Selectable::matching()`
na `EntityRepository` i `QueryBuilder::addCriteria()`. Po přepisu zůstane závěr kapitoly stejný
(pro joiny a vlastní DQL funkce se vrátíte k repozitářové metodě), ale bude stát na skutečném API.
*Rozsah: přepis sekcí `#spec-doctrine` a `#spec-query-kombinatory`, ~110 řádků.*

**P1-4 — Zmírnit kategorické tvrzení o závislostech Domain Service.**
Věta „Vše ostatní (repozitáře, ClockInterface, Mailer) ji posouvá do Application nebo Infrastructure
vrstvy“ je v knize podaná jako pravidlo, ale seriózní zdroje se rozcházejí (Khorikov připouští
*impure* službu, Noback umísťuje rozhraní repozitáře do Domain vrstvy). Kapitola má spor pojmenovat
a dát heuristiku, ne zákon. Zároveň to odstraní vnitřní rozpor s `OrderFromCartFactory`.
*Rozsah: přepis dvou odstavců v 08.03 + jedné FAQ odpovědi, ~25 řádků.*

**P1-5 — Sjednotit `Money` a `Order::place()` s kánonem knihy.**
`->amount` místo `->amountInCents`, `Money::czk()` a `isGreaterThanOrEqual()` bez definice kdekoli
v knize, a `place()` přebírající pole položek proti kanonickému `addItem()`. Čtenář, který kapitoly
čte po sobě, narazí na tři různá API pro tutéž třídu.
*Rozsah: oprava ~6 řádků kódu + kontrola signatury `place()` napříč hubem Taktika.*

**P2-1 — Postavit sekci Specification na původní trojici použití a třech strategiích.**
Papír Evanse a Fowlera pojmenovává Selection, Validation a Construction-to-order a tři implementační
strategie. Kapitola má vlastní čtyřbodový seznam, který se s tím částečně kryje a částečně míjí
(construction-to-order chybí úplně). Převzetí originální kostry zpevní definiční charakter kapitoly
a zároveň vysvětlí, proč u kombinátorů „musíte investovat do frameworku“.
*Rozsah: přepis sekcí `#spec-kdy` a doplnění ~30 řádků.*

**P2-2 — Vyrovnat proporce: zkrátit Specification, rozšířit Domain Services.**
Poměr 567 : 168 řádků neodpovídá tomu, co kapitola sama tvrdí o četnosti chyb. Zkrátit lze ukázky
`OrSpecification` a `NotSpecification` (jsou triviální variace `And`) a variantu `AndSpecification`
s DQL. Získaný prostor patří rozhodovacímu postupu Domain vs. Application Service na druhém,
netriviálním příkladu (typicky pricing nebo scoring, kde je vstup potřeba načíst).
*Rozsah: −80 řádků v 08.02, +60 řádků v 08.03.*

**P2-3 — Doplnit modelovou rovinu Modules a kontrakt modulu.**
Evansův Module je součást modelu, ne konvence složek – včetně důsledku, že vysoký coupling mezi
moduly je signál k úpravě modelu. K tomu patří dnešní posun: modul má publikované rozhraní, ne jen
zákaz importu. Bez toho kapitola redukuje vzor na `composer.json` a phparkitect.
*Rozsah: nová podsekce ~35 řádků + křížový odkaz na kap. 19.*

**P2-4 — Zpřesnit anti-vzor „Specification pro každé porovnání“.**
V současné podobě zakazuje přesně to, co původní papír u Composite Specification předepisuje.
Stačí rozlišit rovinu: doménové jméno nese výsledná specifikace, generické parametrizované listy
uvnitř frameworku jsou legitimní.
*Rozsah: přepis calloutu, ~10 řádků.*

**P2-5 — Vymezit hranici vůči kapitolám 06 a 10.**
Domain Services jsou vyloženy ve třech kapitolách (06.07, 08.03, 10.09), Factory ve dvou,
Specification ve dvou. Kapitola 10 už odkazuje sem, opačný směr chybí. Jedna věta na začátku
každé sekce („zde detailně, tam v kontextu Symfony implementace“) čtenáři ušetří dohady.
*Rozsah: 4 věty.*

**P3-1 — Doplnit Subsumption a Partially Satisfied Specification.**
Oba vzory jsou z původního papíru, oba jsou v PHP praxi téměř neznámé a `remainderUnsatisfiedBy()`
má konkrétní užitek – vysvětlit uživateli, proč pravidlo neprošlo. Pro kapitolu o „méně známých
vzorech“ je to nejlevnější přidaná hodnota.
*Rozsah: nová podsekce ~40 řádků včetně jedné ukázky.*

**P3-2 — Převzít kritérium „When Not to Use“ z původního papíru.**
Příklad Route vs. Route Specification je konkrétnější než současná tři odrážková kritéria.
*Rozsah: přepis sekce `#spec-kdy-ne`, ~15 řádků.*

**P3-3 — Poznámka o `assert()`.**
Jedna věta, aby čtenář nepovažoval `assert($candidate instanceof Order)` za runtime kontrolu.
*Rozsah: 2 věty.*

**P3-4 — Doplnit dva diagramy.**
Hranice Domain / Application / Infrastructure Service a modulová struktura s povolenými směry
závislostí. Kapitola má jediný diagram na čtyři vzory.
*Rozsah: 2 nové `.puml` + 2 `:::diagram` bloky.*

**P3-5 — Zmírnit premisu „druhá polovina taktického katalogu“ a zmínit Supple Design.**
Buď formulaci opravit, nebo doplnit krátký odstavec, že Evansova část III (Intention-Revealing
Interfaces, Side-Effect-Free Functions, Assertions, Closure of Operations, …) je další přehlížená
vrstva a odkázat, kde se jí kniha věnuje.
*Rozsah: přepis decku + 15 řádků.*

## 8. Otevřené otázky pro autora

1. **Rozdělit kapitolu?** Čtyři vzory bez společného jmenovatele kromě „Evans o nich psal a lidé je
   přeskakují“ v jedné kapitole o 1391 řádcích. Modules jsou navíc tematicky blíž kapitolám 09
   (Vertical Slice), 10 (Struktura projektu) a 19 (modulární monolit) než Specification a Factory.
   Varianta: 08 = Specification + Domain Service + Factory (taktika uvnitř modelu), Modules přesunout
   nebo zredukovat na odkaz. Rozhodnutí ovlivní i P2-2 a P2-3.
2. **Ruční framework, nebo balíček?** Kapitola staví celý Specification framework od nuly.
   Má zůstat (pedagogická hodnota, framework-agnostic doména), nebo se má zkrátit ve prospěch
   `happyr/doctrine-specification`? Kompromis: ponechat kostru, doplnit odstavec „v praxi sáhnete
   po balíčku“.
3. **Jak daleko jít do sporu o závislosti Domain Service?** Kniha může spor buď jen pojmenovat
   (dvě věty), nebo mu dát podsekci s rozhodovacím stromem. To druhé je hodnotnější, ale žere prostor,
   který P2-2 chce dát druhému příkladu.
4. **Sjednotit `Order` API napříč knihou?** `Order::place()` má v knize pět různých signatur.
   Je to úkol pro tuto kapitolu, nebo pro samostatnou průřezovou revizi?
5. **Kolik prostoru dát Subsumption?** Vzor je pro čtenáře pravděpodobně nový a v PHP se prakticky
   nepoužívá. Stojí za 40 řádků, nebo stačí odstavec s odkazem na papír?

## 9. Bibliografie

### Ověřené zdroje

`[1]` Evans, E., Fowler, M. — *Specifications*, září 1997. https://martinfowler.com/apsupp/spec.pdf (přístup 2026-09-03; text extrahován a ověřen lokálně)
`[2]` Fowler, M. — archiv článků 1997 (datace papíru *Specification* na „Sep 1997“). https://martinfowler.com/tags/1997.html (přístup 2026-09-03)
`[3]` Evans, E. — *Domain-Driven Design: Tackling Complexity in the Heart of Software*, Addison-Wesley, 2003. Kap. 5 *A Model Expressed in Software* (Services, Modules), kap. 6 *The Life Cycle of a Domain Object* (Factories), kap. 9 *Making Implicit Concepts Explicit* (Specification)
`[4]` Evans, E. — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, Domain Language, 2015. https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf (přístup 2026-09-03)
`[5]` Bogard, J. — *Services in Domain-Driven Design*, Los Techies, 21. 8. 2008. https://lostechies.com/jimmybogard/2008/08/21/services-in-domain-driven-design/ (přístup 2026-09-03)
`[6]` Vernon, V. — *Implementing Domain-Driven Design*, Addison-Wesley, 2013. Kap. 7 *Services*, kap. 9 *Modules*, kap. 11 *Factories* (sekce *Factories in the Model*, *Factory Method on Aggregate Root*, *Factory on Service*)
`[7]` Obsah kap. 11 IDDD a příklad `Forum.startDiscussion()` → `Discussion`. https://www.oreilly.com/library/view/implementing-domain-driven-design/9780133039900/ch11.html (ověřeno přes výpis obsahu, plný text za paywallem)
`[8]` Verraes, M. — *Named Constructors in PHP*, 12. 6. 2014. https://verraes.net/2014/06/named-constructors-in-php/ (přístup 2026-09-03)
`[9]` Eberlei, B. — *On Taming Repository Classes in Doctrine*, 4. 3. 2013. https://beberlei.de/2013/03/04/doctrine_repositories.html (přístup 2026-09-03)
`[10]` Happyr — *Doctrine-Specification*, dokumentace. https://github.com/Happyr/Doctrine-Specification (přístup 2026-09-03)
`[11]` Packagist — `happyr/doctrine-specification` v2.2.0, 28. 7. 2026, PHP >= 7.2, `doctrine/orm ^2.17 || ^3.0`. https://packagist.org/packages/happyr/doctrine-specification (přístup 2026-09-03)
`[12]` Grzybek, K. — *Modular Monolith: A Primer*, 2. 12. 2019. https://www.kamilgrzybek.com/blog/posts/modular-monolith-primer (přístup 2026-09-03)
`[13]` Packagist — `phparkitect/phparkitect` 1.3.0, 31. 7. 2026, PHP >= 8.0. https://packagist.org/packages/phparkitect/phparkitect (přístup 2026-09-03)
`[14]` Packagist — `deptrac/deptrac` 4.7.1, 23. 7. 2026, PHP ^8.2. https://packagist.org/packages/deptrac/deptrac (přístup 2026-09-03)
`[15]` Khorikov, V. — *Domain services vs Application services*, Enterprise Craftsmanship, 8. 9. 2016. https://enterprisecraftsmanship.com/posts/domain-vs-application-services/ (přístup 2026-09-03)
`[16]` Noback, M. — *Does it belong in the application or domain layer?*, únor 2021. https://matthiasnoback.nl/2021/02/does-it-belong-in-the-application-or-domain-layer/ (přístup 2026-09-03)
`[17]` Symfony — *Releases*: 8.0 (listopad 2025), 8.1 (květen 2026), obě PHP >= 8.4.0. https://symfony.com/releases (přístup 2026-09-03)
`[18]` Doctrine Collections — *Getting Started* (Selectable, Criteria, Expr, seznam operátorů, striktní EQ/NEQ). https://www.doctrine-project.org/projects/doctrine-collections/en/stable/index.html (přístup 2026-09-03)
`[19]` Doctrine ORM 3.6 — *The QueryBuilder*, `addCriteria()`. https://www.doctrine-project.org/projects/doctrine-orm/en/3.6/reference/query-builder.html (přístup 2026-09-03)
`[20]` Rector — pravidlo `CriteriaOrderingConstantsDeprecationRector` (deprecace `Criteria::ASC`/`DESC` ve prospěch enumu `Order` od doctrine/collections 2.2). https://getrector.com/rule-detail/criteria-ordering-constants-deprecation-rector (přístup 2026-09-03)
`[21]` PHP-FIG — *PSR-4: Autoloader*. https://www.php-fig.org/psr/psr-4/ (přístup 2026-09-03)
`[22]` Symfony — *Service Container* (autoconfigure a automatické tagování). https://symfony.com/doc/current/service_container.html (přístup 2026-09-03)
`[23]` Symfony — *How to Define Controllers as Services* (`controller.service_arguments`). https://symfony.com/doc/current/controller/service.html (přístup 2026-09-03)

### Neověřené / nedohledané

- **Publikace papíru *Specifications* v proceedings PLoP '97.** Vyhledávání to tvrdí, ale samotný PDF
  soubor ani Fowlerova stránka to nepotvrzují – Fowler uvádí jen „Sep 1997“. Před citací v knize
  dohledat proceedings ručně.
- **Přesná formulace Vernonova doporučení k Factory class vs. factory metoda.** Ověřen je jen obsah
  kapitoly 11 a příklad `Forum.startDiscussion()`. Plný text je za paywallem; před přepisem calloutu
  ověřit v tištěném vydání.
- **Zpracování Specification a Factory u Khononova (*Learning DDD*, 2021) a Milletta & Tuneho
  (*Patterns, Principles and Practices of DDD*, 2015).** Nekontrolováno; obě knihy jsou v pořadí
  důvěryhodnosti šablony a mohou nabídnout novější formulaci pravidel pro Domain Service.
- **Kévin Gomez – *On Taming Repository Classes in Doctrine… Among other things* – DOHLEDÁNO
  2026-09-04.** Vyšel **7. 2. 2015** na
  `blog.kevingomez.fr/2015/02/07/on-taming-repository-classes-in-doctrine-among-other-things/`.
  Téma sedí na tuto kapitolu přesněji, než první průchod předpokládal: text staví na vzoru
  **Specification**, ukazuje problém repozitáře s příliš mnoha zodpovědnostmi a řeší ho přes
  Doctrine `Criteria` (dostupné od Doctrine 2.4), které fungují jak nad `QueryBuilder`em, tak
  nad kolekcemi. Gomez na to navázal textem *RulerZ, specifications and Symfony are in a boat*
  (14. 3. 2015).

  **Pozor na záměnu:** článek téhož názvu má i Benjamin Eberlei (beberlei.de, 4. 3. 2013).
  Gomezův text je pozdější a je to ten se Specification; při citaci uvádět autora i rok.

  403, obsah se nepodařilo ověřit. Článek bývá citován jako zdroj pro Criteria jako mezireprezentaci
  specifikace.
- **Případná oficiální doporučení Symfony k modulové struktuře projektu.** Symfony *Best Practices*
  nebyly v této rešerši ověřeny; před tvrzením „Symfony skeleton očekává controllery v `App\Controller\`“
  dohledat aktuální znění pro verzi 8.
