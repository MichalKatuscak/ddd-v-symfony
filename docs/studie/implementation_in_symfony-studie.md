# Studie: Implementace DDD v Symfony 8

- **Kapitola:** `content/chapters/implementation_in_symfony.md` (č. 10, kategorie Architektura, 1813 řádků)
- **Cesta:** /implementace-v-symfony
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

Poznámka k metodě: rešerše proběhla přímým čtením primárních zdrojů (symfony.com/doc,
symfony.com/bundles/DoctrineBundle, doctrine-project.org, php.net, `UPGRADE.md` a `CHANGELOG.md`
v repozitářích symfony/symfony, doctrine/orm, doctrine/DoctrineBundle, packagist.org API,
blogy Nobacka a Khorikova). Verze balíčků ověřeny přes packagist metadata k 2026-09-03.

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| úvodní callouty (23–47) | 25 ř. | atributy na doméně jsou výchozí volba; kdo chce čistotu, jde na Persisted Object Pattern, ne XML | žádné | tvrzení „drtivá většina open-source projektů“ bez zdroje |
| 10.01 Hranice DDD/Symfony (49–64) | 16 ř. | směr závislostí Symfony → doména | odkaz na kap. 09 | krátké, funguje jako most |
| 10.02 Struktura projektu (65–175) | 111 ř. | vertikální slice podle Bounded Contextů, `Shared/` | žádné | největší strom v knize; bez odkazu na Symfony best practices |
| 10.03 Entity (176–345) | 170 ř. | `AggregateRoot`, privátní konstruktor + factory, VO jako typ vlastnosti, `#[ORM\Version]`, entita **není `final`** | žádné | jádro kapitoly; tvrzení o `final` je zastaralé (viz 4.) |
| 10.04 Value Objects (346–516) | 171 ř. | `final readonly`, `Email`, `UserName` (embeddable), `UserId` přes `Uuid::v7()` | odkaz na egulias/email-validator | dobře podložené, `FILTER_VALIDATE_EMAIL` callout je nadprůměrný |
| 10.05 Repozitáře (517–624) | 108 ř. | rozhraní v doméně + Doctrine implementace, `persist()` bez `flush()`, transakce vlastní middleware | odkaz na kap. Outbox | dva silné warn callouty (dispatch, dvojí transakce) |
| 10.06 Persisted Object Pattern (625–782) | 158 ř. | POPO + persistence model + ruční mapper; cena varianty | Fowler *PoEAA* 2002, Khorikov, Vernon *IDDD* kap. 12 | atribuce k Vernonovi i Khorikovovi je sporná (viz 5.) |
| 10.07 Doctrine custom types (783–863) | 81 ř. | `EmailType extends StringType`, registrace v `doctrine.dbal.types` | žádné | chybí `getSQLDeclaration`, chybí zmínka o odstranění `getName()` v DBAL 4 |
| 10.08 Enums (864–1002) | 139 ř. | backed enum pro stav, `allowedTransitions()`, `enumType:` v Doctrine, kdy symfony/workflow | žádné | fakticky v pořádku |
| 10.09 Doménové služby (1003–1160) | 158 ř. | anti-vzor `PaymentService`, invariant patří do agregátu, tři legitimní případy domain service | žádné | nejlepší argumentační pasáž kapitoly |
| 10.10 Specification (1161–1173) | 13 ř. | jen odkaz do kap. 08 | Evans *DDD* kap. 9 | vhodně krátké |
| 10.11 Doménové události (1174–1241) | 68 ř. | událost nese primitivy; EventDispatcher vs. Messenger podle hranice | žádné | chybí Doctrine listenery a `#[AsMessage]` |
| 10.12 Chyby (1242–1298) | 57 ř. | tři vrstvy výjimek, `\DomainException`, statické factory | žádné | krátké, konzistentní s CLAUDE.md |
| 10.13 Aplikační služby (1299–1535) | 237 ř. | command/handler/bus, TOCTOU race na e-mailu, explicitní `flush()` v `try`, kde validovat | žádné | nejhodnotnější sekce; race-condition callout je originální |
| 10.14 Kontrolery (1536–1668) | 133 ř. | `#[MapRequestPayload]`, `HandlerFailedException`, Form nad commandem, `#[AsAlias]` | žádné | verze API ověřeny jako správné (viz 4.) |
| 10.15 DI a autowiring (1669–1798) | 130 ř. | alias vs. `class:`, autowiring po kontextech, obsah `Shared/` | žádné | messenger snippet je nefunkční (viz 6., G3) |
| FAQ (1799–1813) | 15 ř. | 5 otázek | opakuje atribuce z 10.06 | dědí chybnou atribuci |

Kapitola je nejdelší v knize a čte se jako referenční příručka, ne jako výklad. Dává největší prostor
kódu: zhruba dvě třetiny řádků jsou PHP nebo YAML bloky. Silné jsou pasáže, kde autor argumentuje proti
naivnímu řešení – TOCTOU race u registrace, dvojí transakce, doménová služba jako anti-vzor. Odbývá
naopak dvě věci: úplně chybí jakákoli bibliografie (kapitola nemá sekci „Další četba“, přestože
kapitoly 07, 08 a 09 ji mají) a chybí konfrontace se skutečnou verzí stacku, který kapitola v titulku
slibuje. Faktografie odpovídá stavu zhruba Symfony 6.4 / Doctrine ORM 3.0, ne Symfony 8 / ORM 3.6.

## 2. Kanonické zdroje k tématu

**Data Mapper a oddělení modelu od persistence.** Vzor pochází z Fowlerovy *Patterns of Enterprise
Application Architecture* (2002) [1]. Fowler jej definuje jako vrstvu, která přesouvá data mezi objekty
a databází a drží obojí vzájemně nezávislé. Doctrine ORM je implementace Data Mapperu; samotné použití
Doctrine tedy vzor už naplňuje. To, co kapitola nazývá „Persisted Object Pattern“, je něco jiného –
druhý objektový model navíc. Označení „Persisted Object Pattern“ se nepodařilo dohledat v žádném
z primárních zdrojů (viz sekce 9, Neověřené).

**Vernon, *IDDD* (2013).** Kapitola 12 nese název Repositories a řeší rozhraní repozitáře, kolekční
vs. persistence-oriented styl a implementace nad Hibernate/TopLink. Samostatný persistence model
s obousměrným mapperem tam jako vzor pojmenován není. Atribuce v kapitole (`:630`, `:1806`) je
proto minimálně nepřesná.

**Khorikov, „Having the domain model separate from the persistence model“ (2016)** [2]. Khorikov téma
skutečně rozebírá – ale je **proti**. Uvádí, že plnohodnotný persistence model je příliš drahý, že se
složitost exponenciálně zvětšuje s asociacemi one-to-many a many-to-many, a že se ztrácí ORM change
tracking, což znesnadňuje spolehlivé doménové události. Doslova: „A fully-fledged persistence model is
too costly to implement, whereas with a partial one, you merely trade one kind of purity for another.“
Za obhajitelné to považuje jen ve velkých organizacích, kde databázi spravuje jiný tým – a i to označuje
za příznak hlubšího organizačního problému.

**Noback, „DDD and your database“ (2020)** [3] a **„DDD entities and ORM entities“ (2022)** [4].
Noback zastává stejnou pozici z druhé strany. Kritériem není nepřítomnost ORM metadat, ale
testovatelnost v izolaci: dokud jde doménový objekt vytvořit a otestovat bez databáze, není to
infrastrukturní kód. Oddělené entity nazývá „expensive and unnecessary form of decoupling“ a doporučuje
místo toho „80% decoupling“ – ORM entita obohacená o intention-revealing metody, invarianty a výjimky.
Jediný scénář, kde separaci uznává, je nezávislý vývoj hranic agregátu na schématu databáze; dodává, že
většina projektů, které se toho dovolávají, ji ve skutečnosti nepotřebuje.

**Noback, „Does it belong in the application or domain layer?“ (2021)** [5]. Heuristika:
„Is it going to be used in the Infrastructure layer? Then it belongs in the Application layer.“
Aplikační služba je veřejné API vrstvy, repozitáře a entity zůstávají v doméně a infrastruktura na ně
nesahá přímo. To je přesně dělba, kterou kapitola v sekci 10.13 předpokládá, ale nikde ji nepojmenuje.

**Khorikov, „A better way to handle domain events“ (2017)** [6]. Události se sbírají v agregátu a
publikují až po commitu. Pro komunikaci uvnitř téže aplikace nad touž databází doporučuje doménové
události spíš nepoužívat a tok udělat explicitní. Nejspolehlivější variantou označuje zápis událostí do
dedikované tabulky a jejich zpracování workerem – tedy Outbox. Kapitola k témuž závěru dochází
samostatně (`:594`, `:607`), jen bez zdroje.

**Symfony Best Practices** [7]. Tři relevantní body doslova: „Don't Create any Bundle to Organize your
Application Logic“, „Use Attributes to Define the Doctrine Entity Mapping“ a „Use the Default Directory
Structure“ s dovětkem „Unless your project follows a development practice that imposes a certain
directory structure“. Poslední věta je pro kapitolu 10.02 důležitá: oficiální doporučení výslovně
připouští odchylku pro projekty s vlastní disciplínou, což je přesně případ DDD.

## 3. Stav praxe a posuny

**Konec sporu o mapovací formát.** Doctrine ORM 3.0 odstranil annotation driver i YAML driver [8].
Zbyly atributy, XML, PHP a staticphp. Argument „XML mapping udrží doménu čistou“ tím zeslábl na
provozní úrovni: XML sice funguje dál, ale je to jediná neatributová cesta, kterou většina nástrojů
(Maker, IDE, statická analýza) obsluhuje hůř. Symfony Best Practices doporučují atributy [7], Noback
i Khorikov nezávisle argumentují proti oddělenému modelu [2][3][4]. Výchozí volba kapitoly je tedy
dobře podložená – jen to kapitola nikde nedokládá.

**Nativní lazy objects mění pravidla o `final`.** PHP 8.4 přineslo `ReflectionClass::newLazyGhost()`
a `newLazyProxy()` [9]. Lazy ghost lze vyrobit z libovolné uživatelské třídy, dědičnost není potřeba.
Doctrine ORM 3.4/3.5 tuto cestu přebírá; `ProxyFactory` volá `reflClass->newLazyGhost(...)` a v celém
zdrojovém stromu ORM 3.7 není jediná kontrola `isFinal()` [10]. Od ORM 3.5 je vypnutí nativních lazy
objektů na PHP 8.4+ deprecated, v ORM 4.0 to nepůjde vůbec [8]. DoctrineBundle 3.0 odstranil
`enable_lazy_ghost_objects`, `auto_generate_proxy_classes`, `proxy_dir` i `proxy_namespace`; od
DoctrineBundle 3.1 je `enable_native_lazy_objects` deprecated a konfigurace explicitně odmítá hodnotu
`false` s hláškou „can no longer be disabled and should not be set“ [11][12]. Praktický důsledek:
na stacku, který kapitola v titulku slibuje, **je entita mapovaná Doctrine `final` bez omezení**.

**Property hooks vstupují do ORM.** ORM 3.0 property hooks explicitně zakázal („Property hooks are not
supported yet by Doctrine ORM… they are explicitly forbidden“ [8]). ORM 3.4 podporu přidal s omezením:
hooked property musí mít backing property a nesmí být virtuální [13]. Zůstává past – DQL a
`findBy()` pracují s raw hodnotou, ne s hodnotou po `get` hooku, takže dotaz musí použít formu uloženou
v databázi [13].

**Symfony ObjectMapper.** Komponenta `symfony/object-mapper` byla přidána v 7.3 jako experimentální
a v 7.4 značku `@experimental` ztratila [14]. Mapuje objekt na objekt přes atribut
`Symfony\Component\ObjectMapper\Attribute\Map` a rozhraní `ObjectMapperInterface`. To je přímá
alternativa k ručně psaným mapperům v sekci 10.06.

**Messenger dozrál na infrastrukturu pro doménové události.** Přírůstky, které kapitola nezná:
`#[AsMessage]` s parametrem `$transport` pro směrování na úrovni třídy zprávy (7.2),
`DeduplicateMiddleware` a `DeduplicateStamp` (7.3), `AddDefaultStampsMiddleware` (7.4),
`DecodeFailedMessageMiddleware` a `MessageExecutionStrategyInterface` (8.1) [15]. Konfigurace v
`framework.messenger.routing` má přednost před atributem, takže per-environment override zůstává možný.

**Referenční open-source projekty zestárly.** `CodelyTV/php-ddd-example` (3 148 hvězd) měl poslední
commit v srpnu 2024 a deklaruje Symfony 7 [16]. Aktivně udržované ukázky na Symfony 8 s DDD strukturou
se nepodařilo dohledat (viz sekce 9).

## 4. Symfony / PHP specifika

**Verze stacku k 2026-09-03.** Symfony 8.0.0 vyšlo 27. 11. 2025 a vyžaduje PHP ≥ 8.4 [17][18]. Podpora
8.0 skončila v červenci 2026; aktuální stabilní řada je 8.1 (vydána květen 2026, PHP ≥ 8.4.1), LTS je
7.4 (listopad 2025, PHP ≥ 8.2, bugfixy do listopadu 2028) [17]. Doctrine ORM je na 3.6.8 (srpen 2026),
vývojové větve jsou 3.7.x a 4.0.x; ORM 3.x stále deklaruje `php: ^8.1` [19]. DoctrineBundle je na 3.3.1
a vyžaduje **PHP ^8.4** a `symfony/framework-bundle ^6.4 || ^7.0 || ^8.0` [20].

**Co ověřeno jako správné v kapitole.** `Uuid::isValid($value)` bez druhého argumentu funguje dál –
`$format` byl v 8.0 přidán jako parametr s výchozí hodnotou `self::FORMAT_RFC_9562`, nikoli jako povinný
[21]. `#[MapRequestPayload]` skutečně přišlo v 6.3 [22]. `acceptFormat: 'form'` je platná hodnota:
`RequestPayloadValueResolver::mapRequestPayload()` má explicitní větev `'form' === $format` a přidává
kontext `filter_bool` [23]. `#[AsAlias]` přišlo v 6.3 [24]. `HandlerFailedException::getWrappedExceptions()`
existuje – třída používá `WrappedExceptionsTrait` a implementuje `WrappedExceptionsInterface`; starší
`getNestedExceptions()` bylo odstraněno v 7.0 [15][25]. `Assert\Email::VALIDATION_MODE_STRICT` v Symfony
8 stále existuje (odstraněn byl jen `VALIDATION_MODE_LOOSE`) [26]. Middleware `validation` a
`doctrine_transaction` jsou aktuální názvy [27]. Doctrine ORM podporuje `readonly` vlastnosti na
entitách: `ReadonlyAccessor` nastaví hodnotu, pokud vlastnost není inicializovaná nebo je lazy, a jinak
vyhodí `LogicException('Attempting to change readonly property …')` [10].

**Co ověřeno jako neaktuální.** Zdůvodnění „entita není `final`, protože z ní dědí lazy ghost proxy“
(`:308–313`) na cílovém stacku neplatí – viz sekce 3. Táž věta stojí i v `content/chapters/aggregate_design.md:610`
a tamní `doctrine.yaml` navíc používá `auto_generate_proxy_classes` a `enable_lazy_ghost_objects`, dvě
konfigurační volby, které DoctrineBundle 3.0 odstranil [11]. Opravou kapitoly 10 vznikne rozpor s
kapitolou 07, pokud se neopraví obě.

**Konfigurace custom typů.** Tvar, který kapitola používá, je platný a je to tvar z oficiální reference:

```yaml
doctrine:
    dbal:
        types:
            some_custom_type:
                class: Acme\HelloBundle\MyCustomType
```

Volba `commented` u typů byla v DoctrineBundle 3.0 odstraněna [11]; kapitola ji nepoužívá, což je
v pořádku. `EmailType` v kapitole ale nedefinuje `getSQLDeclaration()` – dědí ji z `StringType`, takže
délku sloupce nelze ovlivnit. Kapitola 07 (`aggregate_design.md:517`) `getSQLDeclaration()` definuje
a navíc vysvětluje, že DBAL 4 odstranil `Type::getName()`. Kapitola 10 tuto informaci nemá vůbec.

**Doctrine lifecycle události.** `#[AsDoctrineListener]` je v namespace
`Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener`, existuje od DoctrineBundle 2.8 a bere
argumenty `event`, `priority`, `connection` [28]. Omezení, která rozhodují o použitelnosti pro
publikaci doménových událostí: v `onFlush` nestačí `persist()`, je nutné dovolat
`UnitOfWork::computeChangeSet()`, resp. `recomputeSingleEntityChangeSet()`; v `postFlush` **nelze
bezpečně volat `flush()`** [29]. To je věcný argument pro explicitní dispatch v aplikační vrstvě,
který kapitola zastává – ale neuvádí ho.

**PHP 8.4 v zápisu VO a entit.** Property hooks a asymetrická viditelnost [30]:

- `public private(set) string $value` nahrazuje dvojici „private property + getter“ u VO, které se
  nechtějí psát jako `readonly` (například proto, že potřebují wither metody s `clone`).
- Property hooks umožňují validaci v `set` hooku místo v konstruktoru. Pro doménový model je to spíš
  past: hook se spustí i při hydrataci Doctrine, kdežto konstruktor Doctrine obchází.
- ORM podporuje hooked properties od 3.4, jen backed a nevirtuální; DQL pracuje s raw hodnotou [13].
- `readonly` zůstává pro VO správnější volbou než `private(set)`, protože zaručuje neměnnost i uvnitř
  třídy. Kapitola tuto úvahu nikde nevede.

**Symfony Serializer a doménové objekty.** `ObjectNormalizer` umí denormalizovat přes konstruktor,
takže `final readonly` command s promovanými parametry funguje i s `#[MapRequestPayload]` [31].
Doménový agregát se ale serializovat nemá – vnitřní stav není kontrakt. Kapitola to implicitně dodržuje
(query handler vrací `UserProfileViewModel`), ale nikde to nepojmenuje. Z novějších přírůstků jsou
relevantní `UidNormalizer` a `BackedEnumNormalizer` (VO postavené na `Uuid` a `enum` projdou bez
vlastního normalizeru) a named serializers z 8.1 [31].

## 5. Sporné a chybně podávané body

**Atributy na doméně vs. oddělený persistence model.** Kapitola rámuje spor tak, že atributy jsou
„pragmatický kompromis“ a Persisted Object Pattern je „korektní cesta“ pro toho, kdo trvá na čistotě.
Oba dohledatelné primární zdroje ale tvrdí opak: Khorikov [2] i Noback [3][4] považují oddělený model
za over-engineering ve většině případů a doporučují ho jen v úzce vymezených situacích. Doporučení pro
knihu: ponechat sekci 10.06 (má hodnotu jako ukázka toho, co separace stojí), ale přerámovat úvod –
místo „korektní cesta pro puristy“ psát „varianta, kterou dva z hlavních zastánců čisté domény
nedoporučují, a proč“. Cituje se snadno, oba texty jsou veřejné.

**Atribuce Persisted Object Patternu.** Vernon *IDDD* kap. 12 je Repositories a tento vzor tam
pojmenován není. Khorikov o něm píše, ale odmítavě. Samotný název „Persisted Object Pattern“ se
nepodařilo dohledat v žádném primárním zdroji. Doporučení: použít zavedené označení „persistence model“
(Khorikov) nebo „separate persistence model“ a atribuci k Vernonovi vypustit; ponechat jen Fowlerův
Data Mapper jako obecný rodičovský vzor.

**Agregát vytvářející jiný agregát.** `Order::recordPayment()` (`:1064–1099`) vrací nový agregát
`Payment` a handler pak ukládá oba. Vernon v *IDDD* (kap. 11, Factories) pattern „agregát jako factory
jiného agregátu“ výslovně popisuje, takže návrh je obhajitelný. Kolize je jinde: kapitola 07 zavádí
pravidlo „jeden agregát na transakci“ (`aggregate_design.md:162`) a tento příklad ho v jedné transakci
porušuje. Doporučení: buď doplnit odstavec, který ukáže, proč je zde výjimka přijatelná (obě zapsání
jsou součástí jednoho invariantu), nebo příklad překreslit na eventual consistency.

**„Doménové výjimky by měly dědit z `\DomainException`“** (`:1293`). CLAUDE.md říká opak: bare
`\DomainException` jen jako přiznaná zkratka, jinak pojmenované výjimky. Formulace v callout boxu
doporučuje dědit z `\DomainException` bez upřesnění, že jde o SPL třídu, ne o doménovou bázovou třídu
projektu. V praxi se doporučuje vlastní `DomainException` interface nebo bázová třída na kontext, aby
šlo chytat „všechny doménové chyby tohoto kontextu“. Sporné je i to, že `\DomainException` je potomek
`\LogicException`, tedy podle SPL sémantiky chyba programátora, ne očekávaný běhový stav.

**„Kontrolery by měly zachytávat doménové výjimky a překládat je na HTTP odpovědi“** (`:1296`).
Kapitola sama o dvě sekce dál ukazuje, že to v praxi znamená iterovat `getWrappedExceptions()`
v každém kontroleru, a nabízí dekorátor busu jako lepší cestu (`:1613`). Idiomatičtější řešení, které
kapitola vůbec nezmiňuje, je Symfony `ExceptionListener` / `#[WithHttpStatus]` na doménové výjimce.
Doporučení: sjednotit doporučení do jedné věty a zbytek nechat na jednom místě.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | zastaralé | `:308–313` | „entity mapované Doctrine zůstávají ne-final, protože z nich dědí lazy ghost proxy“ – na Symfony 8 / DoctrineBundle 3.1+ / PHP 8.4 to neplatí, ORM používá `newLazyGhost()` bez dědičnosti a vypnout to nelze [10][11][12] | přepsat odrážku: `final class User extends AggregateRoot`; vysvětlit nativní lazy objects; sjednotit s `aggregate_design.md:610` |
| G2 | zastaralé | `:232` vs. `:929`, `:1064` | `User` je `class`, `Order` je `final class` – uvnitř téže kapitoly dva různé režimy | po opravě G1 sjednotit na `final` všude |
| G3 | zastaralé | `:1700–1707` | zakomentovaný messenger snippet definuje dva busy bez `default_bus`; FrameworkBundle to odmítne s „You must specify the "default_bus" if you define more than one bus.“ [32] | doplnit `default_bus: command.bus` |
| G4 | nepodložené | `:630`, `:1806` | Persisted Object Pattern připsán Vernonovi (*IDDD* kap. 12 = Repositories) a Khorikovovi, který je proti | opravit atribuci; ponechat Fowlera, doplnit Khorikova [2] a Nobacka [3][4] jako kritiky |
| G5 | sporné | `:35–46`, `:625–630` | rámování „atributy = kompromis, oddělený model = korektní cesta“ neodpovídá pozici zdrojů | přerámovat: oddělený model je menšinová volba s doloženými náklady |
| G6 | chybí | sekce 10.06 | ruční `UserMapper` bez zmínky o `symfony/object-mapper` (7.3 experimentální, 7.4 stabilní) [14] | doplnit 8–12 řádků: `#[Map]`, `ObjectMapperInterface`, kdy ručně a kdy komponentou |
| G7 | chybí | sekce 10.11 | Doctrine lifecycle listenery (`#[AsDoctrineListener]`) jako alternativa k explicitnímu dispatchi nejsou zmíněny vůbec | doplnit odstavec s omezeními `onFlush`/`postFlush` [29] jako argument pro explicitní dispatch |
| G8 | chybí | sekce 10.11 | `#[AsMessage]` (Messenger 7.2) pro směrování na úrovni třídy zprávy; přednost `framework.messenger.routing` [15] | doplnit do callout boxu EventDispatcher vs. Messenger |
| G9 | chybí | sekce 10.03, 10.04 | PHP 8.4 property hooks a asymetrická viditelnost – co mění v zápisu VO a entit; ORM je podporuje od 3.4 jen jako backed, nevirtuální, s DQL pastí [13][30] | nová podsekce ~30 řádků; nutná, protože kapitola slibuje PHP 8.4 |
| G10 | mělké | `:783–863` | custom type bez `getSQLDeclaration()`; chybí zmínka o odstranění `Type::getName()` v DBAL 4 (kap. 07 to má) | doplnit `getSQLDeclaration()` do `EmailType` a jednu větu o `getName()` |
| G11 | sporné | `:575`, `:581` | `find(User::class, $id->value)` a `findOneBy(['email' => $email->value])` předávají primitiv, ačkoli mapované vlastnosti mají custom typ; kap. 07 předává VO (`aggregate_design.md:608`) | sjednotit napříč knihou na předávání VO a doplnit větu, jak Doctrine typ převede |
| G12 | chybí | sekce 10.02 | strom neuvádí, čím se odchylka od výchozí struktury Symfony ospravedlňuje; Best Practices přitom odchylku výslovně připouštějí [7] | doplnit 3–4 věty s citací „Unless your project follows a development practice that imposes a certain directory structure“ |
| G13 | nepodložené | `:40–42` | „Symfony Maker, oficiální dokumentace i drtivá většina open-source projektů používá atributy“ | doložit Best Practices [7] („Use Attributes to Define the Doctrine Entity Mapping“); tvrzení o open-source projektech buď doložit, nebo změkčit |
| G14 | chybí | `:44`, `:851` | tvrzení „XML mapping je taky znečištěné, jen jiným formátem“ nezmiňuje, že ORM 3 odstranil annotation i YAML driver, takže XML je jediná neatributová varianta [8] | doplnit jednu větu |
| G15 | sporné | `:1293` | „doménové výjimky by měly dědit z `\DomainException`“ – SPL `\DomainException` je potomek `\LogicException` | doporučit vlastní marker interface / bázovou třídu na kontext, `\DomainException` uvést jako zkratku |
| G16 | nadbytečné | `:1291–1297` vs. `:1603–1617` | doporučení „kontroler překládá výjimky na HTTP“ se opakuje a v druhém výskytu se relativizuje | sloučit do jednoho místa, zmínit `#[WithHttpStatus]` / `ExceptionListener` |
| G17 | chybí | celá kapitola | kapitola nemá sekci „Další četba“, přestože 07, 08 i 09 ji mají; nemá jediný bibliografický odkaz | doplnit sekci ~20 řádků (Noback, Khorikov, Fowler, Symfony/Doctrine docs) |
| G18 | mělké | `:1517–1534` | „kde validovat“ nezmiňuje spor always-valid vs. not-always-valid domain model ani `Assert\Email` režimy (`html5` je výchozí, `strict` vyžaduje egulias) [26] | rozšířit o 10 řádků |
| G19 | sporné | `:1064–1099` | `Order::recordPayment()` tvoří druhý agregát a handler ukládá oba v jedné transakci – kolize s pravidlem z kap. 07 | doplnit odstavec s odůvodněním, nebo příklad překreslit |
| G20 | mělké | `:152`, `:214`, `:1780` | strom projektu má `Shared/`, kód importuje `App\SharedKernel\Domain\AggregateRoot`, `services.yaml` registruje `App\Shared\` – tři různá jména pro totéž | sjednotit na jedno jméno napříč kapitolou |
| G21 | chybí | sekce 10.15 | `#[Exclude]` (Symfony 6.3) jako alternativa ke glob `exclude:` pro doménové třídy | jedna odrážka |

## 7. Doporučení k přepisu

**P1-1 — Opravit tvrzení o `final` entitách a nativních lazy objektech.**
Kapitola staví celé zdůvodnění na proxy dědičnosti, kterou cílový stack už nepoužívá. DoctrineBundle 3.1
konfiguraci `enable_native_lazy_objects: false` odmítá a ORM 3.7 nikde netestuje `isFinal()`. Nechat to
tak znamená, že nejdéle citovaná technická poučka kapitoly je nepravdivá. Zásah: přepis odrážky na
`:308–313`, změna `class User` → `final class User` na `:232`, plus synchronizační editace
`aggregate_design.md:610` a tamní `doctrine.yaml` (odstraněné klíče `auto_generate_proxy_classes`
a `enable_lazy_ghost_objects`). `oprava dvou vět + dva code bloky, dvě kapitoly`

**P1-2 — Opravit nefunkční messenger snippet.**
Zakomentovaná konfigurace na `:1700–1707` definuje dva busy bez `default_bus`, což FrameworkBundle
odmítne při kompilaci kontejneru. Čtenář, který ji zkopíruje, dostane `InvalidConfigurationException`.
`oprava jednoho řádku`

**P1-3 — Opravit atribuci Persisted Object Patternu a přerámovat sekci 10.06.**
Vernonova *IDDD* kap. 12 tento vzor nezavádí, Khorikov jej explicitně nedoporučuje. Kapitola je uvádí
jako autority pro opak toho, co tvrdí. Kromě opravy zdrojů je potřeba přerámovat úvod sekce: oddělený
persistence model není „korektní cesta“, ale menšinová volba, jejíž náklady kapitola sama dobře popisuje
v boxu „Cena pure varianty“. Stejná oprava v FAQ na `:1806`. `přepis dvou odstavců + FAQ položky`

**P1-4 — Doplnit podsekci o PHP 8.4 v doménovém modelu.**
Kapitola má v titulku Symfony 8, což znamená PHP ≥ 8.4, a o property hooks ani asymetrické viditelnosti
neříká nic. Přitom jde o dvě jazykové vlastnosti, které přímo mění zápis VO a entit, a ORM je podporuje
s netriviálními omezeními (jen backed a nevirtuální hooky od 3.4, DQL pracuje s raw hodnotou). Bez toho
je kapitola v nejcitlivějším bodě mimo verzi, kterou slibuje. `nová podsekce ~30 řádků do 10.04`

**P2-1 — Doplnit Doctrine lifecycle listenery do sekce o doménových událostech.**
Sekce 10.11 srovnává EventDispatcher a Messenger, ale mlčí o třetí variantě, kterou v Symfony projektech
vidí každý – `#[AsDoctrineListener]` na `postFlush` nebo `onFlush`. Argument proti ní je věcný a
dohledatelný: v `postFlush` nelze bezpečně volat `flush()`, v `onFlush` je nutné ručně přepočítat
changeset. To zdůvodnění kapitole chybí a čtenář si ho jinde nenajde. `nový callout ~20 řádků`

**P2-2 — Doplnit `symfony/object-mapper` do sekce 10.06.**
Ruční mapper je hlavní nákladová položka Persisted Object Patternu a od Symfony 7.4 existuje stabilní
komponenta, která ho z velké části nahradí. Zmínka snižuje váhu jednoho z argumentů proti vzoru a dělá
kapitolu aktuální. `nový odstavec + krátký code blok, ~15 řádků`

**P2-3 — Doplnit sekci „Další četba a citace“.**
Kapitola je jediná v hubu Architektura bez bibliografie, přestože obsahuje tvrzení, která zdroj
potřebují (mapping formát, oddělený persistence model, kam patří aplikační služba). Zdroje jsou
dohledatelné a veřejné. `nová sekce ~25 řádků`

**P2-4 — Sjednotit `Shared` / `SharedKernel` a předávání VO do `find()`.**
Tři různá jména pro tutéž složku a rozpor s kapitolou 07 v tom, zda se do `find()` posílá VO nebo
primitiv, jsou drobnosti, které ale čtenář kopírující kód okamžitě narazí. `oprava ~6 míst`

**P2-5 — Doplnit odůvodnění odchylky od výchozí struktury Symfony do 10.02.**
Sekce ukazuje 111řádkový strom bez jediné věty o tom, proč se odchyluje od `src/Controller`,
`src/Entity`. Symfony Best Practices odchylku výslovně připouštějí pro projekty s vlastní disciplínou;
citace to legitimizuje. `3–4 věty`

**P3-1 — Rozšířit „kde validovat“ o spor always-valid domain model.**
Kapitola dává jasné pravidlo, ale nepřipouští, že jde o dlouhodobě diskutovanou otázku. Doplnit odkaz
na obě strany a jednu větu o tom, že výchozí režim `Assert\Email` je `html5`, ne `strict`.
`~10 řádků`

**P3-2 — Doplnit `getSQLDeclaration()` a poznámku o `Type::getName()` do 10.07.**
Sjednotí custom type s kapitolou 07 a odstraní tichý předpoklad délky sloupce. `oprava code bloku`

**P3-3 — Doplnit `#[AsMessage]` a `#[Exclude]`.**
Dvě idiomatické zkratky, které Symfony přineslo po vzniku kapitoly. `dvě odrážky`

## 8. Otevřené otázky pro autora

1. **Má kapitola 10 zůstat referenční příručkou o 1813 řádcích, nebo se rozdělit?** Sekce 10.06
   (Persisted Object Pattern, 158 ř.) a 10.15 (DI, 130 ř.) jsou svébytná témata; 10.06 by mohla přejít
   do kapitoly 20 (DDD v praxi – kde to bolí), kde už sekce „A – Doctrine vs. doménový model“ existuje.

2. **Jak daleko jít s PHP 8.4?** Property hooks a asymetrická viditelnost mění idiomatický zápis VO.
   Kniha má napříč kapitolami zavedenou konvenci `final readonly` + `public readonly` property. Změna
   konvence by znamenala editaci desítek příkladů; ponechání znamená, že kniha o Symfony 8 nepoužívá
   dvě hlavní novinky PHP 8.4. Třetí cesta: konvenci nechat a v jedné podsekci vysvětlit proč.

3. **Držet příklad `Order::recordPayment()` vracející druhý agregát?** Je pedagogicky silný (ukazuje
   invariant uvnitř agregátu), ale koliduje s pravidlem z kapitoly 07.

4. **Kolik prostoru dát XML mappingu?** Po odstranění annotation a YAML driverů v ORM 3 je XML jediná
   neatributová varianta a část komunity ji pro DDD stále prosazuje. Kapitola ji odbývá jednou větou.

5. **Vertikální slice, nebo Domain/Application/Infrastructure?** Strom v 10.02 míchá obojí (kontext má
   `Domain/` a `Infrastructure/`, ale featury `Registration/` a `Profile/` mají vlastní `Command/`,
   `Controller/`, `Form/`). Kapitola 09 přitom oba styly odděluje. Stojí za rozhodnutí, zda strom
   zjednodušit na jeden styl.

6. **Má kniha doporučit konkrétní referenční repozitář?** `CodelyTV/php-ddd-example` je nejcitovanější,
   ale poslední commit má ze srpna 2024 a běží na Symfony 7. Doporučit zastaralý projekt v knize
   o Symfony 8 je riziko.

## 9. Bibliografie

### Ověřené zdroje

`[1]` Martin Fowler — *Patterns of Enterprise Application Architecture*, Addison-Wesley, 2002 (vzor Data Mapper).
`[2]` Vladimir Khorikov — „Having the domain model separate from the persistence model", 2016-04-05. https://enterprisecraftsmanship.com/posts/having-the-domain-model-separate-from-the-persistence-model/ (přístup 2026-09-03)
`[3]` Matthias Noback — „DDD and your database", 2020-05-13. https://matthiasnoback.nl/2020/05/ddd-and-your-database/ (přístup 2026-09-03)
`[4]` Matthias Noback — „DDD entities and ORM entities", 2022-04-21. https://matthiasnoback.nl/2022/04/ddd-entities-and-orm-entities/ (přístup 2026-09-03)
`[5]` Matthias Noback — „Does it belong in the application or domain layer?", 2021-02-25. https://matthiasnoback.nl/2021/02/does-it-belong-in-the-application-or-domain-layer/ (přístup 2026-09-03)
`[6]` Vladimir Khorikov — „Domain events: simple and reliable solution", 2017-10-03. https://enterprisecraftsmanship.com/posts/domain-events-simple-reliable-solution/ (přístup 2026-09-03)
`[7]` Symfony — *The Symfony Framework Best Practices*. https://symfony.com/doc/current/best_practices.html (přístup 2026-09-03)
`[8]` Doctrine ORM — `UPGRADE.md`, větev 3.7.x. https://github.com/doctrine/orm/blob/3.7.x/UPGRADE.md (přístup 2026-09-03)
`[9]` PHP Manual — *Lazy Objects*. https://www.php.net/manual/en/language.oop5.lazy-objects.php (přístup 2026-09-03)
`[10]` Doctrine ORM 3.7.x, zdrojový kód: `src/Proxy/ProxyFactory.php` (volání `newLazyGhost()`), `src/Mapping/PropertyAccessors/ReadonlyAccessor.php`. https://github.com/doctrine/orm (přístup 2026-09-03)
`[11]` DoctrineBundle — `UPGRADE-3.0.md`. https://github.com/doctrine/DoctrineBundle/blob/3.3.x/UPGRADE-3.0.md (přístup 2026-09-03)
`[12]` DoctrineBundle — `UPGRADE-3.1.md` a `src/DependencyInjection/Configuration.php` (deprecated `enable_native_lazy_objects`, `thenInvalid`). (přístup 2026-09-03)
`[13]` Doctrine ORM — *Basic Mapping*, sekce Property Hooks (versionadded 3.4). https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/basic-mapping.html (přístup 2026-09-03)
`[14]` Symfony ObjectMapper — `CHANGELOG.md` (7.3 experimental, 7.4 stable). https://github.com/symfony/object-mapper/blob/8.1/CHANGELOG.md a https://symfony.com/doc/current/object_mapper.html (přístup 2026-09-03)
`[15]` Symfony Messenger — `CHANGELOG.md`, větev 8.1. https://github.com/symfony/messenger/blob/8.1/CHANGELOG.md (přístup 2026-09-03)
`[16]` CodelyTV/php-ddd-example, GitHub API metadata (3 148 hvězd, poslední push 2024-08-06). https://github.com/CodelyTV/php-ddd-example (přístup 2026-09-03)
`[17]` Symfony — *Releases* (8.0 vydáno 11/2025, EOL 7/2026; 8.1 stabilní; 7.4 LTS). https://symfony.com/releases (přístup 2026-09-03)
`[18]` Symfony — `composer.json` větve 8.0 (`"php": ">=8.4"`) a blog „Symfony 8.0.0 released" (2025-11-27). https://symfony.com/blog/symfony-8-0-0-released (přístup 2026-09-03)
`[19]` Packagist — metadata `doctrine/orm` (3.6.8, 2026-08-05). https://repo.packagist.org/p2/doctrine/orm.json (přístup 2026-09-03)
`[20]` Packagist — metadata `doctrine/doctrine-bundle` (3.3.1, 2026-07-23, `php: ^8.4`). https://repo.packagist.org/p2/doctrine/doctrine-bundle.json (přístup 2026-09-03)
`[21]` symfony/uid — `Uuid.php` a `CHANGELOG.md` větve 8.1 (`isValid(string $uuid, int $format = self::FORMAT_RFC_9562)`). https://github.com/symfony/uid (přístup 2026-09-03)
`[22]` symfony/http-kernel — `CHANGELOG.md`, sekce 6.3 („Add `#[MapRequestPayload]`…"). https://github.com/symfony/http-kernel/blob/8.1/CHANGELOG.md (přístup 2026-09-03)
`[23]` symfony/http-kernel — `Controller/ArgumentResolver/RequestPayloadValueResolver.php`, metoda `mapRequestPayload()`. (přístup 2026-09-03)
`[24]` symfony/dependency-injection — `CHANGELOG.md`, sekce 6.3 („Add `#[AsAlias]` attribute…"). https://github.com/symfony/dependency-injection/blob/8.1/CHANGELOG.md (přístup 2026-09-03)
`[25]` symfony/messenger — `Exception/HandlerFailedException.php` (`WrappedExceptionsTrait`, `WrappedExceptionsInterface`). (přístup 2026-09-03)
`[26]` symfony/validator — `Constraints/Email.php` a `CHANGELOG.md` (odstraněn `VALIDATION_MODE_LOOSE`, `strict` vyžaduje `egulias/email-validator`). (přístup 2026-09-03)
`[27]` Symfony — *Multiple Buses, Command & Event Buses*. https://symfony.com/doc/current/messenger/multiple_buses.html (přístup 2026-09-03)
`[28]` DoctrineBundle — *Event Listeners* (`#[AsDoctrineListener]`, od DoctrineBundle 2.8). https://symfony.com/bundles/DoctrineBundle/current/event-listeners.html (přístup 2026-09-03)
`[29]` Doctrine ORM — *Events* (omezení `onFlush`, `postFlush`, `postLoad`). https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/events.html (přístup 2026-09-03)
`[30]` PHP — *PHP 8.4: New features* (property hooks, asymmetric visibility, lazy objects). https://www.php.net/releases/8.4/en.php (přístup 2026-09-03)
`[31]` Symfony — *Serializer*. https://symfony.com/doc/current/serializer.html (přístup 2026-09-03)
`[32]` symfony/framework-bundle — `DependencyInjection/Configuration.php` („You must specify the \"default_bus\" if you define more than one bus."). (přístup 2026-09-03)
`[33]` DoctrineBundle — *Configuration Reference* (tvar `doctrine.dbal.types.<name>.class`, `enable_native_lazy_objects` jako no-op). https://symfony.com/bundles/DoctrineBundle/current/configuration.html (přístup 2026-09-03)
`[34]` Symfony — *Symfony Messenger*, sekce Routing (`#[AsMessage]`, přednost `framework.messenger.routing`). https://symfony.com/doc/current/messenger.html (přístup 2026-09-03)

### Neověřené / nedohledané

- **Název „Persisted Object Pattern".** Nepodařilo se dohledat v žádném primárním zdroji (Fowler *PoEAA*,
  Vernon *IDDD*, Khorikov, Noback). Khorikov používá „persistence model", Fowler „Data Mapper".
  Vyžaduje ruční ověření, odkud kapitola název převzala.
- **Vernon *IDDD* kap. 12.** Ověřeno pouze nepřímo (název kapitoly Repositories). Fyzická kontrola
  obsahu kapitoly nebyla možná; doporučuje se ověřit v tištěném vydání, zda se vzor odděleného
  persistence modelu skutečně nezmiňuje.
- **Tvrzení „drtivá většina open-source projektů používá atributy" (`:41`).** Neověřitelné bez
  systematického průzkumu. Doložit lze jen Symfony Best Practices a Symfony Maker.
- **Aktivně udržovaný referenční DDD projekt na Symfony 8.** Nenalezen. Kandidáti k ručnímu prověření:
  `jorge07/symfony-ddd-skeleton`, `dddinphp/last-wishes` (oba vrátily 404 přes GitHub API, pravděpodobně
  přejmenované nebo archivované).
- **Chování `EntityManager::find()` s VO jako identifikátorem u custom typu.** Ověřeno jen z kódu
  `ReadonlyAccessor` a dokumentace typů, ne testem. Doporučuje se ověřit lokálně, než se sjednotí
  příklady mezi kapitolami 07 a 10 (nález G11).
- **Kompatibilita `#[ORM\Embedded]` s `readonly` vlastností** (`:244` – `private readonly HashedPassword`).
  Dokumentace embeddables to neřeší; `ReadonlyAccessor` naznačuje, že jednorázová hydratace projde,
  ale kombinace s embeddable nebyla ověřena.
