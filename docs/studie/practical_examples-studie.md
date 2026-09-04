# Studie: Praktické příklady

- **Kapitola:** `content/chapters/practical_examples.md` (č. 23, kategorie Syntéza, 356 řádků)
- **Cesta:** /prakticke-priklady
- **Typ kapitoly:** narativní
- **Datum studie:** 2026-09-04

Poznámka k metodě: `WebSearch` byl v této session vyčerpaný, rešerše proběhla přímým `WebFetch`
a `curl` dotazy na GitHub API, `repo.packagist.org`, `raw.githubusercontent.com` (zdrojáky
`symfony/symfony` větev 8.0 a `symfony/symfony-docs`), symfony.com/doc a verraes.net.
U každého zdroje v sekci 9 je uvedeno, jak byl získán. Ukázky kapitoly byly proti kanonickému
API knihy porovnány řádek po řádku a `php -l` kontrolou (`scripts/lint-php-snippets.php`).

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| úvod (21–27) | 7 ř. | kapitola je „shrnující průřez“; detaily jsou jinde; plný příklad je v ch. 24 | odkaz na `/pripadova-studie` | poctivě přiznává, že je to rozcestník |
| 23.01 E-commerce (29–120) | 92 ř. | dva Bounded Contexts Cart a Order, mezi nimi `CartCheckedOut`; strom adresářů; skeleton `Cart`; `AddItemToCartHandler` | žádné | kód `Order` ani přechod mezi kontexty se nikde neukáže |
| 23.02 Blog (122–201) | 80 ř. | jeden kontext, agregát `Post` s entitou `Comment`; skeleton `Post`; `CreatePostHandler` | žádné | `Comment` je jen ve stromu, v kódu nikde |
| 23.03 Správa uživatelů (203–304) | 102 ř. | jeden kontext, `User` implementuje `UserInterface`; skeleton `User`; `RegisterUserHandler` | odkaz na ch. 10, 11 | jediná sekce, která přiznává kompromisy (`:262–268`) |
| 23.04 Tři projekty vedle sebe (306–334) | 29 ř. | srovnávací tabulka komplexity, vzorů a stropu struktury; kdy se plný stack vyplatí | žádné | nejsilnější a nejoriginálnější část kapitoly |
| 23.05 Závěr (336–345) | 10 ř. | společný řetězec kontroler → bus → handler → agregát → repozitář → event | odkaz na ch. 24 | řetězec kapitola nikde neukáže celý |
| FAQ (347–356) | 10 ř. | 4 otázky: proč slice + CQRS, převzetí do produkce, kde je plný agregát, proč jeden kontext | odkazy do ch. 07, 10, 24 | tvrzení o „typickém tvaru produkčního projektu“ bez zdroje |

Kapitola je nejkratší v hubu Syntéza a je spíš rozcestníkem než ukázkovou kapitolou. Obsahuje šest
PHP bloků, všechny označené jako `(skeleton)`, tři stromy adresářů a tři diagramy. Prostor dostávají
kostry agregátů a command handlerů, tedy střed řetězce; oba konce – HTTP vrstva a persistence –
chybí úplně. Nejlepší je sekce 23.04: srovnávací tabulka s řádky „co se změní při růstu“ a „kdy
struktura přestane stačit“ je jediné místo, kde kapitola říká něco, co jinde v knize není. Naopak
odbývá vlastní název: „praktický“ příklad, který se nedá spustit, zkompilovat ani otestovat, a který
z každého agregátu ukáže tři metody s tělem `/* ... */`, praktický není. Nejzávažnější zjištění není
o rozsahu, ale o konzistenci: kapitola je v knize jediná, kde se doménová událost nahrává
v konstruktoru (`:162`), a přitom o dvě sekce dál sama píše, že se to nikdy nedělá (`:264`).

## 2. Kanonické zdroje k tématu

Otázka téhle kapitoly není „co je DDD“, ale „jak se DDD učí na příkladu“. Referenční materiály
odpovídají shodně: jednou souvislou doménou plus spustitelným repozitářem.

**Evans, *Domain-Driven Design* (2003)** [1]. Kniha používá jednu doménu – přepravu nákladu (cargo
shipping) – napříč celým výkladem. Čtenář se s `Cargo`, `Itinerary`, `RouteSpecification` a
`HandlingEvent` potkává v kapitolách o entitách, o agregátech, o specifikaci i o refaktoringu.
Kanonická spustitelná verze žije jako DDD Sample App, dnes `citerus/dddsample-core` [2]: aktivní
repozitář (poslední push 2025-06-02, 5 290 hvězd), Spring Boot aplikace s webovým rozhraním, testy
a REST API. Vzor „kniha + jeden běžící referenční projekt“ tedy stojí u zrodu literatury o DDD.

**Vernon, *Implementing Domain-Driven Design* (2013)** [4]. Vernon jde ještě dál: celá kniha sleduje
fiktivní firmu SaaSOvation a její tři Bounded Contexts – Identity and Access, Collaboration a Agile
Project Management. Doprovodný repozitář `VaughnVernon/IDDD_Samples` [3] (3 943 hvězd) obsahuje
všechny tři kontexty jako samostatné Java moduly včetně testů. Pointa je, že kontexty spolu
komunikují – Collaboration si přes ACL sahá do Identity. To, co kapitola 23.01 popisuje jako
`CartCheckedOut` mezi Cart a Order, je přesně ta věc, kterou Vernon považoval za natolik důležitou,
že jí věnoval třetinu vzorového kódu.

**Buenosvinos, Soronellas, Akbary, *Domain-Driven Design in PHP* (2017)**. Kniha má dvě doprovodné
aplikace: `dddshelf/last-wishes` [5] („one of the sample applications where you can check the
concepts explained in the *Domain-Driven Design in PHP* book“) a knihovnu pomocných tříd
`dddshelf/ddd` [11]. Obě jsou dnes nečinné (poslední push 2019 a 2020), ale formát je stejný: text
odkazuje na běžící Symfony aplikaci, ne na útržky.

**CodelyTV, `php-ddd-example`** [6]. Nejcitovanější současný PHP referenční projekt (3 148 hvězd,
1 087 forků, poslední push 2024-08-06). Modeluje MOOC platformu, drží čtyři moduly – Mooc,
Backoffice, Analytics, Retention – plus `Shared`, uvnitř každého hexagonální členění
Application / Domain / Infrastructure. Nabízí `make build`, Docker, tři běžící aplikace na třech
portech, PHPUnit i Behat testy. Podstatné pro naši kapitolu: příklad je jeden a je celý. Není to tři
oddělené mikroukázky, ale jedna doména dovedená od HTTP vrstvy po testy.

**Symfony sám** [8][9]. Oficiální *Best Practices* na konci uvádějí: „Symfony provides a sample
application called Symfony Demo that follows all these best practices, so you can experience them
in practice.“ Repozitář `symfony/demo` je aktivní (push 2026-09-01, 2 619 hvězd) a modeluje – shodou
okolností – blog. Kapitola 23.02 tedy soutěží s referenční aplikací frameworku na stejné doméně,
aniž by ji zmínila.

**Verraes, „Named Constructors in PHP“ (2014)** [15]. Zdroj pro konvenci, kterou kapitola používá:
privátní konstruktor plus statická factory pojmenovaná jazykem domény. Verraes doslova: „Now that
the constructor is no longer public, we can choose to refactor all the internals of Time as much as
we want.“ Doporučuje názvy jako `Customer::fromRegistration()` místo generických. Kapitola tuto
konvenci dodržuje u `User::register()` a `Cart::open()`, ale u blogu volí generické `Post::create()`
– což je přesně ten generický tvar, proti kterému Verraes argumentuje.

## 3. Stav praxe a posuny

**Od „ukázka v knize“ ke „spustitelnému repozitáři“.** Všechny čtyři referenční projekty výše mají
společné, že se dají naklonovat a spustit jedním příkazem (`make build`, `make install`,
`docker compose up`). To je dnes minimum, ne bonus. Kniha to sama uznává: repozitář
`MichalKatuscak/ddd-symfony-examples` [17] existuje, je veřejný, obsahuje `Makefile`, `compose.yaml`,
`phpunit.dist.xml` a v README slibuje „Živé, spustitelné ukázky Domain-Driven Design v Symfony 8“.
Čtrnáct kapitol knihy na něj přes frontmatter `github_examples` odkazuje. Kapitola 23 – ta, jejíž
jediný smysl je praktičnost – klíč `github_examples` nemá vůbec.

**Jedna doména místo sady vinět.** Posun za posledních zhruba deset let je v tom, že učební materiály
opustily samostatné mikropříklady ve prospěch jedné domény vedené napříč. Důvod je pedagogický:
u disjunktních ukázek si čtenář neodnese, jak vzory interagují, což je celý smysl DDD. Evans [1],
Vernon [3][4] i CodelyTV [6] volí jednu doménu. Kapitola 23 volí tři, což jí bere možnost ukázat
cokoli, co přesahuje jednu třídu.

**Kostry s `/* ... */` jsou dnes menšinová forma.** Referenční projekty ukazují plný kód a strukturu
řeší README. Kapitola volí opak: strukturu ukazuje ASCII stromem a kód redukuje na signatury.
Výsledek je, že šest PHP bloků kapitoly obsahuje dohromady jednu skutečnou implementaci
(`RegisterUserHandler`, `:281–299`). Zbytek jsou hlavičky metod.

**Co dnes čtenář v „praktické“ kapitole hledá.** Z formátu referenčních projektů se dá odvodit
čtyřprvkový seznam: (a) kompletní vertikální řez jedním use casem od requestu po databázi,
(b) odkaz na běžící kód, (c) test, který ukazuje, že to funguje, (d) srovnání variant – co by se
stalo, kdyby se tatáž feature udělala jinak. Kapitola dnes nabízí z těchto čtyř věcí nic celého;
nejblíž má tabulka 23.04, která je zárodkem bodu (d).

## 4. Symfony / PHP specifika

Ověřené verze k 2026-09-04 [16]: `symfony/symfony` v8.1.6, `doctrine/orm` 3.6.8. Kniha cílí na
PHP 8.4, Symfony 8, Doctrine ORM 3.

**`eraseCredentials()` – komentář v kapitole je správně.** `UPGRADE-8.0.md` v repozitáři
`symfony/symfony` [10] uvádí: „Remove `UserInterface::eraseCredentials()` and
`TokenInterface::eraseCredentials()`; erase credentials e.g. using `__serialize()` instead“. Zdrojový
kód `UserInterface` ve větvi 8.0 [13] skutečně obsahuje jen `getRoles()` a `getUserIdentifier()`.
Poznámka na `practical_examples.md:255` je tedy fakticky v pořádku. Diagram k téže sekci ale
`eraseCredentials()` pořád zobrazuje (viz G14).

**`getPassword(): ?string`.** `PasswordAuthenticatedUserInterface` ve větvi 8.0 [12] deklaruje
`public function getPassword(): ?string;`. Signatura v kapitole (`:258`) sedí. Docblock rozhraní
navíc doporučuje `__serialize()`/`__unserialize()` k tomu, aby se hash hesla nedostal do session –
to by v ukázce s agregátem jako `UserInterface` stálo za jednu větu.

**`UserPasswordHasherInterface::hashPassword()` – zde je skutečná chyba.** Rozhraní ve větvi 8.0 [11]
zní:

```php
public function hashPassword(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): string;
```

Hasher tedy potřebuje **instanci uživatele**, protože podle ní vybírá nakonfigurovaný algoritmus.
Volání `HashedPassword::fromHasher($this->passwordHasher, $command->password)` na `:295` nemá jak
fungovat: uživatel v tu chvíli ještě neexistuje a vzniká až o čtyři řádky níž. Kapitola 10 stejný
problém obchází jinak (`HashedPassword::fromPlainText($command->password)`,
`implementation_in_symfony.md:1385`), kapitola 12 zase přes doménové rozhraní
`App\UserManagement\Domain\Service\PasswordHasher` (`cqrs.md:422`). Kniha má tedy tři různá řešení
téhož a to třetí je nefunkční.

**Návratová hodnota z command handleru.** `CreatePostHandler::__invoke()` vrací `string`
(`:184`, `:195`). Messenger to umožňuje přes `HandledStamp`; dokumentace [14] ale výslovně říká, že
`HandleTrait` získá výsledek „when processing synchronously“. Kapitola 12 na to sama upozorňuje:
„Nefunguje pro asynchronní transport – handler běží v jiném procesu a výsledek přes HandledStamp do
původního requestu nedoputuje“ (`cqrs.md:321`). V kapitole 23 návratová hodnota stojí bez poznámky.

**`final` u Doctrine entit.** Věta na `:267–268` („`final` se u entit mapovaných Doctrine vynechává,
protože lazy proxy z entity dědí“) je na cílovém stacku zastaralá – doloženo ve studii ke kapitole 10
[18]: PHP 8.4 `newLazyGhost()` dědičnost nepotřebuje, ORM 3.4+ ji používá, DoctrineBundle 3.1
vypnutí odmítá. Navíc si kapitola sama odporuje: všechny tři agregáty jsou v ukázkách deklarované
jako `final class` (`:66`, `:153`, `:233`).

**Symfony Best Practices a odchylka od výchozí struktury** [9]. Doslova: „Unless your project follows
a development practice that imposes a certain directory structure, follow the default Symfony
directory structure.“ Tři stromy v kapitole tuhle výjimku využívají, ale nikde ji nepojmenují –
stejný nález má studie ke kapitole 10 (G12).

**Syntaktická kontrola.** `php scripts/lint-php-snippets.php content/chapters/practical_examples.md`
projde: 6 bloků, 0 chyb. Chyby v této kapitole jsou sémantické a konzistenční, ne syntaktické.

## 5. Sporné a chybně podávané body

**Agregát jako `UserInterface`.** Kapitola to dělá a hned v poznámce (`:264–267`) přiznává, že
v plné architektuře patří implementace na security adapter. To je poctivé řešení sporu a je lepší
než tichá volba. Zůstává otázka, zda se má tenhle kompromis v knize objevit potřetí (ch. 10, ch. 11,
ch. 23) – viz sekce 8.

**Kontrola unikátnosti e-mailu v handleru.** Kapitola ukazuje `if ($this->users->findByEmail($email)
!== null) throw ...` (`:287–289`) a v komentáři odkazuje na `#register-race-heading`. Jenže ta sekce
(`implementation_in_symfony.md:1407`, „Race condition v naivní variantě s `findByEmail()`“) říká,
že tenhle check je vůči souběžným registracím **nedostatečný**, a kapitola 10 proto řeší registraci
přes `catch (UniqueConstraintViolationException)`. Kapitola 23 tedy ukazuje variantu, kterou kniha
o třináct kapitol dřív označila za naivní, a odkaz použije jako by ji potvrzoval. Doporučení:
buď převzít `try/catch` z ch. 10, nebo komentář přeformulovat na „zde záměrně zjednodušeno, správná
varianta je …“.

**Zaznamenání události v konstruktoru.** `Post::__construct()` volá `$this->record(new PostCreated(...))`
(`:162`). `CLAUDE.md` to zakazuje explicitně („Events are recorded in named constructors / domain
methods, never in `__construct`“) a důvod je věcný: rekonstituce agregátu z databáze by emitovala
události. Grep přes všech 25 kapitol potvrdil, že jde o **jediný výskyt v celé knize**. O sto řádků
níž přitom kapitola sama píše: „událost `UserRegistered` se nahrává ve factory `register()`, nikdy
v konstruktoru“ (`:264–265`). Toto je nejzávažnější nález studie.

**Skeleton versus plný kód.** Zde se dá argumentovat oběma směry. Pro kostry: kapitola je průřez,
plné implementace jsou jinde, opakování by nafouklo knihu. Proti: kapitola se jmenuje „Praktické
příklady“ a slibuje, že vzory „drží pohromadě jako funkční aplikace“ (`:22`) – což z hlaviček metod
vidět není. Rozumný kompromis: nechat kostry u agregátů (ty jsou v knize opravdu jinde) a naopak
dotáhnout jeden úplný vertikální řez – jeden request, jeden controller, jeden handler, jeden
repozitář, jedna projekce – protože právě ten v knize nikde celý není.

**Tři domény, nebo jedna.** Referenční materiály volí jednu ([1][3][6]). Kapitola volí tři a získává
tím srovnávací tabulku 23.04, což je hodnota, kterou jedna doména nedá. Spor je reálný a rozhodnutí
patří autorovi (sekce 8). Studie doporučuje střední cestu: dotáhnout e-shop do úplného řezu a blog
i správu uživatelů zredukovat na strukturu plus rozdíly proti e-shopu.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | sporné | `practical_examples.md:162` | `Post::__construct()` nahrává `PostCreated`; `CLAUDE.md` to zakazuje, jde o jediný takový výskyt v knize a kapitola si na `:264` sama odporuje | Přesunout `record()` do `Post::create()` |
| G2 | sporné | `:295` | `HashedPassword::fromHasher($this->passwordHasher, $command->password)` nemůže fungovat – `hashPassword()` vyžaduje `PasswordAuthenticatedUserInterface $user` [11]; navíc třetí varianta hashování v knize (ch. 10 `fromPlainText`, ch. 12 doménový `PasswordHasher`) | Sjednotit s ch. 10 na `HashedPassword::fromPlainText()` |
| G3 | sporné | `:283` | `new Email($command->email)` nad syrovým vstupem; `CLAUDE.md` i ch. 10 (`:1381`) předepisují `Email::fromUserInput()` | Opravit volání |
| G4 | sporné | `:285–289` | Kontrola unikátnosti přes `findByEmail()` je varianta, kterou ch. 10 (`:1407`) označuje za naivní; odkaz v komentáři to zamlčuje | Převzít `try/catch (UniqueConstraintViolationException)`, nebo označit jako vědomé zjednodušení |
| G5 | zastaralé | `:267–268` | „`final` se u entit mapovaných Doctrine vynechává“ – na PHP 8.4 / ORM 3.4+ / DoctrineBundle 3.1 neplatí [18]; kapitola navíc `final` sama používá (`:66`, `:153`, `:233`) | Poznámku odstranit nebo přepsat |
| G6 | sporné | `:75`, `:109` | `addItem(ProductId, Quantity, Money $price)`; kanonická signatura podle `CLAUDE.md` je `addItem(ProductId $productId, int $quantity, Money $unitPrice)`. VO `Quantity` se v knize nikde jinde nevyskytuje | Sjednotit se signaturou z ch. 06/07, nebo `Quantity` zavést už tam |
| G7 | sporné | `:109` | `$product->id()` a `$product->price()` – metodové gettery, zatímco kniha používá `public readonly` vlastnosti a táž kapitola o 86 řádků níž píše `$post->id->value` (`:195`) | Sjednotit na vlastnosti |
| G8 | chybí | sekce 23.01 | `CartCheckedOut` je v úvodu sekce (`:32–33`) označen za hlavní pointu, ale agregát `Order`, `Order::place()` ani listener reagující na událost se nikde neukážou | Doplnit event handler v kontextu Order (~25 ř.) – právě to je věc, kterou zbytek knihy nemá pohromadě |
| G9 | chybí | celá kapitola | Chybí controller, implementace repozitáře, query handler, ViewModel, Doctrine mapping i test – přitom `:338` slibuje řetězec „kontroler → command bus → handler → agregát → repozitář → event“ | Jeden úplný vertikální řez u e-shopu (~80 ř.); zbylé dva příklady nechat jako kostry |
| G10 | chybí | frontmatter (`:1–19`) | Kapitola nemá klíč `github_examples`, ačkoli repozitář `ddd-symfony-examples` existuje a 14 jiných kapitol na něj odkazuje [17] | Založit `src/Chapter23_PracticalExamples` a doplnit klíč; bez toho je „praktická“ kapitola jediná bez běžícího kódu |
| G11 | zastaralé | repozitář ukázek [17] | README mapuje ukázky na staré číslování 1–9; kniha má dnes 25 kapitol (00–24) a `implementation_in_symfony` je č. 10, v repu „4“ | Přečíslovat repozitář, nebo do README doplnit převodní tabulku |
| G12 | zastaralé | `:35` (`7_examples/eshop/plant.uml`) | Diagram člení kód na `Application` / `Presentation`, zatímco strom o 15 řádků níž (`:50–52`) používá feature slices `AddItem/`, `GetCart/`, `Checkout/`. Dále: `Cart` má gettery `id()`, `userId(): string` místo `public readonly`, `AddItemToCart` nese `price: float`, a `CartCheckedOut` v diagramu vůbec není | Překreslit podle současného textu |
| G13 | zastaralé | `:127` (`7_examples/blog/plant.uml`) | `Post` má `- id: string` a zároveň `id(): PostId`, `author(): string` místo `AuthorId`, `- events: array` místo dědičnosti z `AggregateRoot`; `CommentAdded` chybí; `GetPostsHandler` čte přes zápisový `PostRepository`, což ch. 12 (`:407`) zakazuje | Překreslit; read model vést mimo doménový repozitář |
| G14 | zastaralé | `:208` (`7_examples/users/plant.uml`) | Diagram zobrazuje `eraseCredentials()` (v Symfony 8 odstraněno [10], text kapitoly to na `:255` sám říká), `setPassword(string)`, `- password: string` bez `HashedPassword`, a agregát `User` umisťuje do `Shared/Domain/Model` – tedy do složky, kterou ch. 10 (`:170`) označuje za častou chybu | Překreslit |
| G15 | sporné | `:46` vs. `:71`; `:136–139` | Stromy nesouhlasí s kódem: `Cart` používá `UserId`, ale `UserId.php` ve stromu chybí; blog má ve stromu `Model/Comment.php`, `CommentId.php` a `Event/CommentAdded.php`, v kódu ani jednou | Sjednotit stromy s ukázkami, nebo doplnit `Post::addComment()` |
| G16 | sporné | `:57` | `Shared/Domain/Exception/DomainException.php` – čtvrté pojmenování sdíleného prostoru v knize (`Shared/`, `SharedKernel/`, `App\Shared\`); vlastní třída navíc nese jméno SPL `\DomainException` | Sjednotit s ch. 06/10 a třídu přejmenovat |
| G17 | sporné | `:184`, `:195` | Command handler vrací `string`; ch. 12 (`:320–321`) tuto variantu označuje za porušení CQS, které nefunguje asynchronně [14] | Doplnit jednu větu, nebo změnit na `void` a ID generovat v kontroleru |
| G18 | sporné | `:106–107` | `findByIdOrFail()`; ch. 11 (`authorization_in_ddd.md:274`) používá `getOrFail()` | Sjednotit pojmenování napříč knihou |
| G19 | sporné | `:222–224` | Struktura registrace je plochá (`Registration/{RegisterUser, RegisterUserHandler, RegistrationController}.php`), zatímco e-shop i blog v téže kapitole a ch. 10 (`:91–99`) používají `Feature/{Command, Controller}/` | Sjednotit se dvěma zbylými příklady |
| G20 | nepodložené | `:349` | „Tato kombinace se v ukázkách opakuje záměrně – odpovídá typickému tvaru produkčního DDD projektu v Symfony 8“ – bez zdroje; navíc v kapitole není jediný query handler ani read model, takže CQRS část tvrzení není ničím doložená ani ukázaná | Podložit (např. [6][7]), nebo změkčit; ideálně doplnit chybějící query slice |
| G21 | mělké | `:311–317` | Tabulka slibuje „tři různé úrovně doménové komplexity“, ale sama hodnotí blog jako „nízká“ a správu uživatelů jako „nízká až střední“; rozlišení je tedy dvouúrovňové | Buď přeformulovat na dvě úrovně, nebo blog nahradit doménou s netriviálním invariantem |
| G22 | chybí | celá kapitola | Žádné cvičení, žádné zadání „zkuste si“, žádné srovnání téže feature v CRUD a v DDD podobě – to je forma, kterou referenční materiály [2][6] nahrazují běžícím kódem, a kapitola nenabízí ani jedno | Doplnit 3–5 zadání navázaných na repozitář ukázek (~20 ř.) |
| G23 | nadbytečné | sekce 23.03 | Registrace uživatele je v knize popsaná už čtyřikrát (`implementation_in_symfony.md:1370`, `cqrs.md:428`, `migration_from_crud.md:695`, `testing_ddd.md:579`); páté opakování nepřidává nic nového | Nahradit doménou, která v knize ještě nezazněla, nebo sekci zkrátit na rozdíly |
| G24 | sporné | `:97`, `:180`, `:274` | Handlery nejsou `readonly`, ačkoli ch. 10 (`:1370`) a ch. 11 (`:265`) používají `final readonly class` | Sjednotit |
| G25 | mělké | `:65–84` | `Cart` má `private Collection $items` bez inicializace a bez konstruktoru; `open()`, `removeItem()`, `totalAmount()` i `checkout()` mají prázdné tělo. Z bloku se nedá poznat ani to, jak invariant „jedna položka na produkt“ vypadá | Doplnit tělo `addItem()` (~12 ř.); ostatní metody nechat jako kostru |
| G26 | chybí | úvod (`:21–27`) | Kapitola nikde neuvádí výchozí bod: jaká verze PHP/Symfony/Doctrine, jak projekt založit, co doinstalovat (`symfony/uid`, `symfony/messenger`) | Doplnit 5–8 řádků „výchozí bod“ s `composer create-project` a seznamem balíčků |

## 7. Doporučení k přepisu

**P1-1 — Opravit záznam události v konstruktoru `Post`.**
`:162` je jediné místo v knize, kde agregát nahrává událost v konstruktoru. Porušuje kanonickou
konvenci z `CLAUDE.md`, věcně by při rekonstituci z databáze emitoval falešné události, a kapitola
si na `:264` sama odporuje. Bez opravy je kapitola pro čtenáře, který ji čte jako shrnutí,
aktivně matoucí. Rozsah: přesun jednoho řádku, `oprava dvou vět`.

**P1-2 — Opravit `RegisterUserHandler` (`:281–299`).**
Tři chyby v jednom bloku: `fromHasher()` s nefunkční signaturou [11], `new Email()` místo
`Email::fromUserInput()`, a TOCTOU varianta, kterou ch. 10 sama označuje za naivní. Blok je přitom
jediná úplná implementace v kapitole, takže nese největší váhu. Rozsah: `přepis jednoho code bloku
(~20 ř.)`, sladit s `implementation_in_symfony.md:1370–1400`.

**P1-3 — Překreslit všechny tři diagramy.**
Zdrojové `plant.uml` v `templates/diagrams/7_examples/` pocházejí z dubna 2025 a od té doby se text
kapitoly změnil. Diagramy dnes odporují vlastní kapitole ve struktuře (vrstvy vs. slices),
v konvencích (gettery vs. `public readonly`), v typech (`price: float`, `author: string`) i ve
faktech (`eraseCredentials()` odstraněné v Symfony 8 [10]). U diagramu e-shopu navíc chybí
`CartCheckedOut`, tedy hlavní pointa sekce. Rozsah: `tři .puml + regenerace SVG`.

**P1-4 — Doplnit chybějící `github_examples` a založit ukázku v repozitáři.**
Repozitář existuje, běží a 14 kapitol na něj odkazuje [17]. Kapitola, jejíž jediná přidaná hodnota
je praktičnost, je jediná ze Syntézy bez odkazu na běžící kód. Zároveň sjednotit číslování
repozitáře s knihou (G11). Rozsah: `frontmatter + nový adresář v ddd-symfony-examples`.

**P2-1 — Dotáhnout e-shop do jednoho úplného vertikálního řezu.**
Kapitola slibuje řetězec kontroler → bus → handler → agregát → repozitář → event (`:338`), ale
ukazuje z něj jen prostřední třetinu. Úplný řez jednoho use casu (`POST /cart/items` → controller
s `#[MapRequestPayload]` → `AddItemToCart` → handler → `Cart::addItem()` → `DoctrineCartRepository`
→ `ItemAddedToCart`) je věc, kterou kniha nikde celou nemá; ch. 10 ji rozděluje do pěti sekcí,
ch. 24 ji utápí v rozsahu. Zde by dávala smysl. Rozsah: `nová podsekce ~80 řádků`.

**P2-2 — Ukázat přechod `CartCheckedOut` → `Order`.**
Cross-context komunikace přes doménovou událost je jediná věc, kterou e-shop nabízí navíc proti
zbylým dvěma příkladům, a je celá jen v próze. Stačí `Cart::checkout()` s `record()`, listener
v kontextu Order a `Order::place()` – s kanonickou signaturou z `CLAUDE.md`. Bonusem se do kapitoly
konečně dostane agregát `Order`, na který zbytek knihy odkazuje. Rozsah: `~25 řádků kódu + 8 řádků
prózy`.

**P2-3 — Sjednotit ukázky s kanonickým API napříč kapitolou.**
Body G6, G7, G16, G18, G19, G24: signatura `addItem()`, gettery vs. `public readonly`, jméno
sdíleného prostoru, `findByIdOrFail()` vs. `getOrFail()`, plochá vs. vnořená struktura feature,
`final readonly` u handlerů. Jednotlivě drobnosti, dohromady působí, že každý příklad psal někdo
jiný. V kapitole, která má být syntézou, je konzistence hlavní sdělení. Rozsah: `oprava ~15 řádků
napříč šesti bloky`.

**P2-4 — Doplnit stromy o soubory, které kód používá, a odstranit ty, které nikde nejsou.**
`UserId.php` v Cart kontextu chybí, `Comment.php`/`CommentId.php`/`CommentAdded.php` v blogu
naopak přebývají. Buď doplnit `Post::addComment()`, nebo komentáře z blogu vypustit – dnes kapitola
slibuje entitu uvnitř agregátu (`:124`) a nikdy ji neukáže. Rozsah: `oprava dvou stromů + volitelně
~15 řádků kódu`.

**P3-1 — Doplnit „výchozí bod“ na začátek kapitoly.**
Pět až osm řádků: `composer create-project symfony/skeleton`, seznam balíčků (`symfony/uid`,
`symfony/messenger`, `doctrine/orm`), ověřené verze [16]. Praktická kapitola má začínat tím, odkud
se startuje. Rozsah: `nový odstavec ~8 řádků`.

**P3-2 — Doplnit cvičení navázaná na repozitář ukázek.**
Tři až pět zadání typu „přidej do košíku slevový kupon a rozhodni, kam patří invariant“. Dnes
kapitola nenabízí čtenáři nic k udělání. Rozsah: `nová sekce 23.05 ~20 řádků`, závěr posunout na
23.06.

**P3-3 — Zvážit redukci sekce 23.03.**
Registrace uživatele je v knize popsaná počtvrté (G23). Buď zkrátit na rozdíly proti ch. 10, nebo
nahradit doménou s netriviálním invariantem – tím by se také narovnalo tvrzení o „třech úrovních
komplexity“ (G21). Rozsah: `přepis sekce 23.03`.

## 8. Otevřené otázky pro autora

1. **Tři domény, nebo jedna dotažená?** Referenční materiály volí jednu doménu vedenou napříč
   ([1][3][6]); tabulka 23.04 je naopak hodnota, kterou lze získat jen srovnáním tří. Studie
   doporučuje asymetrii – e-shop celý, zbylé dva jako kostru s rozdíly – ale je to rozhodnutí
   o charakteru kapitoly.
2. **Cílový rozsah.** Dnes 356 řádků a 12 minut čtení. Doporučení P2-1 a P2-2 přidají zhruba 120
   řádků. Má kapitola zůstat pod 500 řádky, nebo se smí posunout k rozsahu ch. 10?
3. **Má být kapitola 23 vůbec samostatná?** Vedle ní stojí ch. 24 s 1 579 řádky a plným průřezem.
   Alternativou je sloučit 23 do úvodu ch. 24 jako „rozcvičku“, nebo naopak vyostřit rozdíl:
   23 = jeden úplný technický řez, 24 = strategická analýza a projekce.
4. **Kolik prostoru dát repozitáři ukázek?** Rozhodnutí P1-4 znamená závazek repozitář udržovat.
   Alternativa je odkazovat na cizí referenční projekty [6][7] a vlastní repozitář nechat na
   kapitolách, které ho už mají.
5. **Blog jako doména.** Symfony Demo [8] modeluje blog jako referenční aplikaci frameworku.
   Má kniha ukazovat DDD na doméně, u které sama v ch. 22 tvrdí, že se pro DDD nehodí?
6. **Kompromis „agregát implementuje `UserInterface`“** se v knize objevuje potřetí. Stačí jednou
   s odkazem, nebo ho každá kapitola opakuje?

## 9. Bibliografie

### Ověřené zdroje

- [1] Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*,
  Addison-Wesley, 2003. (Kniha; doména cargo shipping jako průběžný příklad.)
- [2] `citerus/dddsample-core` — spustitelná verze Evansova cargo příkladu. 5 290 hvězd, poslední
  push 2025-06-02. https://github.com/citerus/dddsample-core (získáno GitHub REST API,
  `api.github.com/repos/citerus/dddsample-core`, 2026-09-04)
- [3] `VaughnVernon/IDDD_Samples` — „These are the sample Bounded Contexts from the book
  *Implementing Domain-Driven Design* by Vaughn Vernon“. 3 943 hvězd, poslední push 2023-09-09.
  https://github.com/VaughnVernon/IDDD_Samples (GitHub REST API, 2026-09-04)
- [4] Vaughn Vernon — *Implementing Domain-Driven Design*, Addison-Wesley, 2013. (Kniha; SaaSOvation
  jako průběžný příklad, tři Bounded Contexts.)
- [5] `dddshelf/last-wishes` (dřívější `dddinphp/last-wishes`) — „one of the sample applications where
  you can check the concepts explained in the *Domain-Driven Design in PHP* book“. 655 hvězd,
  poslední push 2019-05-01. https://github.com/dddshelf/last-wishes (GitHub REST API s následováním
  přesměrování, 2026-09-04)
- [6] `CodelyTV/php-ddd-example` — „Hexagonal Architecture + DDD + CQRS in PHP using Symfony 7“.
  3 148 hvězd, 1 087 forků, poslední push 2024-08-06; moduly Mooc, Backoffice, Analytics, Retention,
  Shared. https://github.com/CodelyTV/php-ddd-example (README přes WebFetch
  `raw.githubusercontent.com/.../main/README.md` + GitHub REST API, 2026-09-04)
- [7] `jorge07/symfony-7-es-cqrs-boilerplate` — „Symfony 7 DDD ES CQRS backend boilerplate“.
  1 088 hvězd, poslední push 2026-08-09. https://github.com/jorge07/symfony-7-es-cqrs-boilerplate
  (GitHub REST API, 2026-09-04)
- [8] `symfony/demo` — Symfony Demo Application. 2 619 hvězd, poslední push 2026-09-01.
  https://github.com/symfony/demo (GitHub REST API, 2026-09-04)
- [9] Symfony — *The Symfony Framework Best Practices*.
  https://symfony.com/doc/current/best_practices.html (WebFetch, 2026-09-04). Citováno: „Symfony
  provides a sample application called Symfony Demo that follows all these best practices“;
  „Unless your project follows a development practice that imposes a certain directory structure,
  follow the default Symfony directory structure“.
- [10] Symfony — `UPGRADE-8.0.md`, sekce Security: „Remove `UserInterface::eraseCredentials()` and
  `TokenInterface::eraseCredentials()`; erase credentials e.g. using `__serialize()` instead“.
  https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md (staženo přes
  `raw.githubusercontent.com`, curl, 2026-09-04)
- [11] Symfony — `UserPasswordHasherInterface`, větev 8.0: `hashPassword(PasswordAuthenticatedUserInterface
  $user, string $plainPassword): string`. `src/Symfony/Component/PasswordHasher/Hasher/UserPasswordHasherInterface.php`
  (raw.githubusercontent.com, curl, 2026-09-04)
- [12] Symfony — `PasswordAuthenticatedUserInterface`, větev 8.0: `getPassword(): ?string`.
  `src/Symfony/Component/Security/Core/User/PasswordAuthenticatedUserInterface.php`
  (raw.githubusercontent.com, curl, 2026-09-04)
- [13] Symfony — `UserInterface`, větev 8.0: obsahuje pouze `getRoles()` a `getUserIdentifier()`.
  `src/Symfony/Component/Security/Core/User/UserInterface.php` (raw.githubusercontent.com, curl,
  2026-09-04)
- [14] Symfony — *Messenger: Sync & Queued Message Handling*, sekce „Getting Results from your
  Handlers“ a „Getting Results when Working with Command & Query Buses“: „A `HandleTrait` exists to
  get the result of the handler **when processing synchronously**.“ Zdrojový `messenger.rst`,
  větev 7.4 repozitáře `symfony/symfony-docs` (raw.githubusercontent.com, curl, 2026-09-04);
  publikovaná podoba https://symfony.com/doc/current/messenger.html
- [15] Mathias Verraes — „Named Constructors in PHP“, 12. 6. 2014.
  https://verraes.net/2014/06/named-constructors-in-php/ (WebFetch, 2026-09-04)
- [16] Packagist — `symfony/symfony` v8.1.6, `doctrine/orm` 3.6.8 jako nejnovější vydané verze.
  `https://repo.packagist.org/p2/symfony/symfony.json`, `https://repo.packagist.org/p2/doctrine/orm.json`
  (curl, 2026-09-04)
- [17] `MichalKatuscak/ddd-symfony-examples` — „Ukázky kódu ke knize DDD v Symfony“, veřejný,
  vytvořen 2026-03-26, poslední push 2026-04-29. Obsahuje `src/Chapter01_WhatIsDDD` …
  `src/Chapter12_LesserPatterns`, `Makefile`, `compose.yaml`, `phpunit.dist.xml`; README slibuje
  „Živé, spustitelné ukázky Domain-Driven Design v Symfony 8“ a mapuje ukázky na číslování 1–9.
  https://github.com/MichalKatuscak/ddd-symfony-examples (GitHub REST API + raw README, 2026-09-04)
- [18] Interní: `docs/studie/implementation_in_symfony-studie.md`, nálezy G1, G2, G12, G20
  (nativní lazy objects a `final`, tři jména pro sdílený prostor). Čteno z repozitáře, 2026-09-04.
- [19] Interní: `docs/studie/basic_concepts-studie.md`, nálezy G3, G4, G8 (dvě definice `Money`,
  dvě definice `AggregateRoot`). Čteno z repozitáře, 2026-09-04.
- [20] `dddshelf/ddd` (dřívější `dddinphp/ddd`) — „Domain Driven Design PHP helper classes“,
  658 hvězd, poslední push 2020-10-07. https://github.com/dddshelf/ddd (GitHub REST API, 2026-09-04)

### Neověřené / nedohledané

- **Khononov, *Learning DDD* (2021) – struktura případové studie.** Stránku vydavatele
  (`oreilly.com/library/view/learning-domain-driven-design/9781098100124/`) vrátil server 403,
  obsah knihy se tedy nepodařilo ověřit. Tvrzení o tom, jak Khononov staví závěrečnou případovou
  studii, proto ve studii nefiguruje. Ověřit ručně z tištěného výtisku.
- **Matthias Noback k ukázkovým aplikacím.** Index matthiasnoback.nl byl načten, ale žádný článek
  k tématu „sample application“ / „example project“ v načtené části nefiguruje. Konkrétní URL
  z jiných studií (`/2018/06/hexagonal-architecture-and-ddd/`) vrací 404. Ověřit ručně přes archiv
  blogu, případně z knihy *Advanced Web Application Architecture* (2020).
- **Diagramy `templates/diagrams/7_examples/` – OVĚŘENO 2026-09-04 z historie gitu.** Datum
  27. 4. 2025 sedí a je to jediný obsahový commit (`dc2c6b2` „Diagramy ukázek“). Po něm se
  adresáře dotkly už jen dva technické commity: `a94ff69` publikační audit (28. 4. 2026)
  a `fd050e3` sjednocení PUML→SVG pipeline (3. 5. 2026). Text kapitoly mezitím prošel deseti
  obsahovými commity, poslední 8. 7. 2026. **Nešlo tedy o vědomé rozhodnutí diagramy nechat být** –
  neexistuje commit, který by je při revizích kapitoly otevřel. Je to opomenutí a diagramy je
  před přepisem nutné projít proti aktuálnímu textu.
- **Klíč `github_examples` – OVĚŘENO 2026-09-04: v kapitole nikdy nebyl.** `git log --all -S`
  nad `content/chapters/practical_examples.md` nevrací žádný commit, který by ten řetězec přidal
  nebo odebral. Hypotéza o odstraněném klíči je vyvrácená, otázka uzavřená.
- **Millett & Tune, *Patterns, Principles and Practices of DDD* (2015), doprovodný kód –
  hledáno 2026-09-04, nedohledáno.** Oficiální repozitář ke knize se najít nepodařilo: autoři
  žádný veřejně nevedou a vydavatel (Wrox) svoje download stránky ke knize už neprovozuje.
  Kniha sama je dostupná (O'Reilly, Google Books), doprovodný kód ne. **Doporučení: nepracovat
  s předpokladem, že k této knize existuje veřejné code companion.**
