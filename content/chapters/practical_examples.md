---
route: practical_examples
path: /prakticke-priklady
title: Praktické příklady
page_title: "Praktické příklady DDD v Symfony 8 | DDD Symfony"
meta_description: "Praktické příklady DDD v Symfony 8: e-commerce, blog a správa uživatelů v PHP 8.4+. Bounded Contexts, agregáty a vertikální slice na třech projektech."
meta_keywords: "DDD příklady, Symfony ukázky, bounded contexts, doménové modely, agregáty, e-commerce DDD, blog DDD, vertikální slice architektura, praktické implementace, ukázky kódu, reálné projekty"
og_type: article
published: "2025-04-24"
modified: "2026-09-05"
breadcrumb_name: Praktické příklady
schema_type: TechArticle
schema_headline: "Praktické příklady Domain-Driven Design v Symfony"
chapter_number: "23"
category: Syntéza
deck: "Praktické příklady implementace Domain-Driven Design v Symfony 8 na třech zjednodušených projektech – e-commerce, blog a správa uživatelů. Ukázka bounded contexts, doménových modelů a vertikální slice architektury."
reading_time: 16
difficulty: 3
---

Tato kapitola je **shrnující průřez** předchozími kapitolami. Tři krátké příklady ukazují,
jak vzory z taktického DDD, CQRS a Implementace v Symfony drží pohromadě jako funkční aplikace.
Každý příklad obsahuje strukturu projektu a kostru hlavních tříd; plné tělo dostávají
jen metody, které nesou doménový invariant. Detailní implementace (Doctrine mapování,
kontrolery, testy, okrajové případy) najdete v předchozích kapitolách.

Plný end-to-end příklad – od doménové analýzy přes kontextovou mapu po read modely –
rozebírá krok za krokem navazující [Případová studie](/pripadova-studie).

Výchozím bodem je prázdný projekt: `composer create-project symfony/skeleton`.
Ukázky v knize cílí na PHP 8.4, Symfony 8 a Doctrine ORM 3 a potřebují tyhle balíčky:

:::code{language="bash" filename="terminál"}
composer require \
    symfony/uid \
    symfony/messenger \
    symfony/doctrine-messenger \
    symfony/serializer symfony/property-access symfony/property-info \
    symfony/validator egulias/email-validator \
    symfony/security-bundle \
    doctrine/orm doctrine/doctrine-bundle doctrine/doctrine-migrations-bundle
:::

Seznam nevypadá minimalisticky, každá položka ale odpovídá jedné kapitole.
`doctrine-messenger` dodává transport `doctrine://default`. Bez `serializer`
a `property-access` neprojde outbox, bez `egulias/email-validator` shodí
`Assert\Email` s `VALIDATION_MODE_STRICT` každý dispatch. A bez
`migrations-bundle` nespustíte ani jednu migraci z kapitoly o Outboxu.

Instalace tím ale nekončí a další dva kroky se přeskakují obzvlášť snadno, protože
nic nespadne. Recept `doctrine/doctrine-bundle` vygeneruje mapování na `src/Entity`
s prefixem `App\Entity` – adresář, který ve vertikálním řezu neexistuje. Doctrine pak
mlčky nevidí žádnou entitu:

:::code{language="bash" filename="terminál"}
php bin/console doctrine:schema:update --dump-sql
# [OK] No Metadata Classes to process.
:::

Hláška vypadá jako úspěch. Znamená opak. Blok `mappings:` je proto potřeba přepsat
podle [kapitoly o agregátech](/navrh-agregatu#symfony-doctrine) dřív, než vznikne první
migrace. Druhý krok je `DATABASE_URL` v `.env` – recept nastaví PostgreSQL, takže
u SQLite nebo MySQL připojení do prázdna:

:::code{language="bash" filename=".env.local"}
# SQLite stačí na všechny ukázky v knize a nepotřebuje běžící server
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
:::

## 23.01 Příklad: E-commerce aplikace {#e-commerce}

E-commerce výřez nad košíkem a objednávkami. Dva Bounded Contexts: **Cart** (rozpracovaný nákup)
a **Order** (potvrzená transakce). Mezi nimi přechází doménová událost `CartCheckedOut`,
na kterou kontext Order reaguje vytvořením agregátu `Order`.

:::diagram{fig="23.1-A" title="E-shop: bounded contexts Cart a Order" src="images/diagrams/7_examples/eshop/diagram.svg"}
:::

### Struktura projektu {#e-commerce-structure}

:::code{language="bash" filename="src/ struktura"}
src/
├── Cart/                      # Bounded Context: Košík
│   ├── Domain/
│   │   ├── Model/Cart.php          # Aggregate Root
│   │   ├── Model/CartItem.php
│   │   ├── ValueObject/CartId.php, ProductId.php, UserId.php
│   │   ├── Event/ItemAddedToCart.php, CartCheckedOut.php, CheckedOutItem.php
│   │   ├── Exception/EmptyCartException.php
│   │   └── Repository/CartRepository.php
│   ├── Infrastructure/Repository/DoctrineCartRepository.php
│   ├── AddItem/{Command, Controller}/  # Feature slice
│   ├── GetCart/{Query, ViewModel}/     # Feature slice
│   └── Checkout/Controller/             # Feature slice
├── Order/                     # Bounded Context: Objednávky
│   ├── Domain/Model/Order.php          # Aggregate Root
│   ├── Domain/ValueObject/OrderId.php, CustomerId.php
│   ├── Domain/Event/OrderPlaced.php
│   └── PlaceOrder/{Command, Controller, Listener}/
└── Shared/Domain/{Money.php, Exception/DomainException.php}
:::

### Agregát Cart {#cart-aggregate}

Agregát `Cart` hlídá pravidlo: u stejného `productId` navyšuje množství stávající
položky místo přidání nové. Obě metody nesoucí invariant mají plné tělo, zbytek
zůstává kostrou:

:::code{language="php" filename="src/Cart/Domain/Model/Cart.php (skeleton)"}
final class Cart extends AggregateRoot
{
    public readonly CartId $id;
    public readonly UserId $userId;
    /** @var Collection<int, CartItem> */
    private Collection $items;
    private bool $checkedOut = false;

    public static function open(CartId $id, UserId $userId): self { /* ... */ }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        // INVARIANT: jedna položka na produkt – množství se sčítá, řádek se neduplikuje.
        $existing = $this->findItem($productId);

        if ($existing !== null) {
            $existing->increaseQuantity($quantity);
        } else {
            $this->items->add(new CartItem($this->id, $productId, $quantity, $unitPrice));
        }

        $this->record(new ItemAddedToCart($this->id, $productId, $quantity));
    }

    public function checkout(): void
    {
        // INVARIANT: z prázdného košíku objednávka nevznikne.
        if ($this->items->isEmpty()) {
            throw EmptyCartException::withId($this->id);
        }

        // INVARIANT: košík se odbaví jednou. Bez toho by dvojklik nebo
        // retry vyrobil dvě události, a tím dvě objednávky.
        if ($this->checkedOut) {
            throw CartAlreadyCheckedOutException::withId($this->id);
        }

        $this->checkedOut = true;

        $this->record(new CartCheckedOut(
            $this->id,
            $this->userId,
            array_map(CheckedOutItem::fromCartItem(...), $this->items->toArray()),
            new \DateTimeImmutable(),
        ));
    }

    public function removeItem(ProductId $productId): void { /* ... */ }
    public function totalAmount(): Money { /* sumace přes items */ }
    private function findItem(ProductId $productId): ?CartItem { /* ... */ }
}
:::

Plnou implementaci včetně Doctrine mappingu (`#[ORM\OneToMany]`, `cascade`, `orphanRemoval`,
optimistický zámek přes `#[ORM\Version]`) ukazuje [Návrh agregátu](/navrh-agregatu) a
[Implementace v Symfony](/implementace-v-symfony).

### Command Handler: AddItemToCart {#add-item-handler}

Tenký aplikační handler: načte agregát, deleguje doménovou logiku, uloží.

:::code{language="php" filename="src/Cart/AddItem/Command/AddItemToCartHandler.php (skeleton)"}
#[AsMessageHandler]
final readonly class AddItemToCartHandler
{
    public function __construct(
        private CartRepository $carts,
        private ProductRepository $products,
    ) {}

    public function __invoke(AddItemToCart $command): void
    {
        $cart = $this->carts->get(new CartId($command->cartId));
        $product = $this->products->get(new ProductId($command->productId));

        $cart->addItem($product->id, $command->quantity, $product->price);

        $this->carts->save($cart);
    }
}
:::

`ProductRepository` ve struktuře projektu výše nefiguruje záměrně: v Cart kontextu existuje
jen jako rozhraní (port), implementaci dodává kontext Catalog, který ukázka vynechává.

Plnou CQRS implementaci s validací, autorizací a outbox patternem najdete v [CQRS](/cqrs) a
[Outbox Pattern](/outbox-pattern).

### Přechod z košíku do objednávky {#cart-checkout-to-order}

Checkout je jediné místo, kde se oba kontexty potkávají. Cart o objednávkách nic neví;
zaznamená událost a tím pro něj práce končí. Payload události nese kopii dat, ne entity
košíku – kontexty se znají jen přes identifikátory a hodnoty.

:::code{language="php" filename="src/Cart/Domain/Event/CartCheckedOut.php"}
final readonly class CartCheckedOut
{
    /** @param list<CheckedOutItem> $items */
    public function __construct(
        public CartId $cartId,
        public UserId $userId,
        public array $items,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
:::

Na druhé straně hranice stojí handler kontextu Order. Ten si cizí slovník překládá na svůj:
`UserId` z košíku se stává `CustomerId` objednávky.

:::code{language="php" filename="src/Order/PlaceOrder/Listener/PlaceOrderOnCartCheckedOut.php"}
#[AsMessageHandler]
final readonly class PlaceOrderOnCartCheckedOut
{
    public function __construct(private OrderRepository $orders) {}

    public function __invoke(CartCheckedOut $event): void
    {
        // Překlad mezi kontexty na hranici: UserId košíku → CustomerId objednávky.
        $order = Order::place(OrderId::generate(), new CustomerId($event->userId->value));

        foreach ($event->items as $item) {
            $order->addItem($item->productId, $item->quantity, $item->unitPrice);
        }

        $this->orders->save($order);
    }
}
:::

V monolitu handler odebírá doménovou událost přímo. Jakmile se kontext Order osamostatní,
potřebuje vlastní integrační DTO naplněné z payloadu zprávy – důvody rozebírá
[DDD a mikroslužby](/ddd-a-microservices). Spolehlivé doručení mezi kontexty přitom
nezajistí sběrnice sama, ale [Outbox Pattern](/outbox-pattern).

## 23.02 Příklad: Blog {#blog}

Blog drží jeden Bounded Context s jediným agregátem `Post` – `Comment` je entita uvnitř něj –
a sekcemi pro vytvoření příspěvku, výpis a detail.

:::diagram{fig="23.2-A" title="Blog: doménový model a feature slices" src="images/diagrams/7_examples/blog/diagram.svg"}
:::

### Struktura projektu {#blog-structure}

:::code{language="bash" filename="src/ struktura"}
src/
└── Blog/                      # Bounded Context: Blog
    ├── Domain/
    │   ├── Model/Post.php           # Aggregate Root
    │   ├── Model/Comment.php
    │   ├── ValueObject/PostId.php, CommentId.php, AuthorId.php
    │   ├── Event/PostCreated.php, CommentAdded.php
    │   ├── Exception/CommentsClosedException.php
    │   └── Repository/PostRepository.php
    ├── Infrastructure/Repository/DoctrinePostRepository.php
    ├── CreatePost/{Command, Controller}/
    ├── AddComment/{Command, Controller}/
    ├── GetPost/{Query, Controller, ViewModel}/
    └── GetPosts/{Query, Controller, ViewModel}/
:::

### Agregát Post {#post-aggregate}

Agregát `Post` se vytváří přes named constructor `create()`. Ten vynucuje invarianty
(titul 3–255 znaků, neprázdný obsah) a nová instance zaznamená `PostCreated`. Konstruktor
zůstává privátní a událost nenahrává – rekonstituce z databáze by jinak emitovala
události znovu.

:::code{language="php" filename="src/Blog/Domain/Model/Post.php (skeleton)"}
final class Post extends AggregateRoot
{
    /** @var Collection<int, Comment> */
    private Collection $comments;
    private bool $commentsClosed = false;

    private function __construct(
        public readonly PostId $id,
        private string $title,
        private string $content,
        public readonly AuthorId $authorId,
        public readonly \DateTimeImmutable $createdAt,
    ) {
        $this->comments = new ArrayCollection();
    }

    public static function create(PostId $id, string $title, string $content, AuthorId $authorId): self
    {
        // Invarianty: title 3–255 znaků, content nesmí být prázdný
        $post = new self($id, $title, $content, $authorId, new \DateTimeImmutable());
        $post->record(new PostCreated($id, $title, $authorId));

        return $post;
    }

    public function addComment(CommentId $id, AuthorId $authorId, string $text): void
    {
        // INVARIANT: do uzavřené diskuse komentář nepřibude.
        if ($this->commentsClosed) {
            throw CommentsClosedException::forPost($this->id);
        }

        $this->comments->add(new Comment($id, $this->id, $authorId, $text));
        $this->record(new CommentAdded($this->id, $id, $authorId));
    }

    public function closeComments(): void { /* ... */ }
    public function updateTitle(string $newTitle): void { /* ... */ }
    public function updateContent(string $newContent): void { /* ... */ }
}
:::

`Comment` je entita uvnitř agregátu, ne samostatný Aggregate Root. Vzniká jen přes
`Post::addComment()`, takže invariant „uzavřená diskuse“ nelze obejít. Hranici agregátu
a důsledky pro souběžné zápisy rozebírá [Návrh agregátu](/navrh-agregatu).

### Command Handler: CreatePost {#create-post-handler}

:::code{language="php" filename="src/Blog/CreatePost/Command/CreatePostHandler.php (skeleton)"}
#[AsMessageHandler]
final readonly class CreatePostHandler
{
    public function __construct(private PostRepository $posts) {}

    public function __invoke(CreatePost $command): void
    {
        $post = Post::create(
            new PostId($command->postId),
            $command->title,
            $command->content,
            new AuthorId($command->authorId),
        );

        $this->posts->save($post);
    }
}
:::

Handler nic nevrací a identifikátor příspěvku přichází v commandu – kontroler ho
vygeneruje přes `PostId::generate()` ještě před dispatchem. Návrat ID z handleru přes
`HandledStamp` je druhá možnost, ale u asynchronního transportu se výsledek k volajícímu
nedostane; srovnání obou variant je v [CQRS](/cqrs#command-navratova-hodnota-heading).

Read model pro výpis příspěvků – paginace, řazení podle data, projekce z událostí –
patří mimo zápisový repozitář. Rozebírá ho [CQRS – ViewModely a Read Modely](/cqrs#view-models)
a [Výkonnostní aspekty](/vykonnostni-aspekty).

## 23.03 Příklad: Správa uživatelů {#user-management}

Bounded Context **UserManagement** drží jediný agregát `User` a tři sub-features: registraci,
autentizaci, profil. Agregát se integruje se Symfony Security (implementuje `UserInterface`).

:::diagram{fig="23.3-A" title="Správa uživatelů: feature slices" src="images/diagrams/7_examples/users/diagram.svg"}
:::

### Struktura projektu {#user-mgmt-structure}

:::code{language="bash" filename="src/ struktura"}
src/
└── UserManagement/            # Bounded Context: Správa uživatelů
    ├── Domain/
    │   ├── Model/User.php           # Aggregate Root
    │   ├── ValueObject/UserId.php, Email.php, HashedPassword.php
    │   ├── Event/UserRegistered.php
    │   ├── Exception/DuplicateEmailException.php
    │   └── Repository/UserRepository.php
    ├── Infrastructure/Repository/DoctrineUserRepository.php
    ├── Registration/{Command, Controller}/
    ├── Authentication/Controller/
    └── Profile/{Query, Controller}/
:::

### Agregát User {#user-aggregate}

Agregát `User` implementuje Symfony `UserInterface` pro Security komponentu. Hodnotový
objekt `Email` validuje formát v konstruktoru, `HashedPassword` zapouzdřuje hash logiku.

:::code{language="php" filename="src/UserManagement/Domain/Model/User.php (skeleton)"}
final class User extends AggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    private function __construct(
        public readonly UserId $id,
        private string $name,
        private Email $email,
        private HashedPassword $password,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function register(UserId $id, string $name, Email $email, HashedPassword $password): self
    {
        $user = new self($id, $name, $email, $password, new \DateTimeImmutable());
        $user->record(new UserRegistered($id, $email, $user->createdAt));

        return $user;
    }

    public function changeEmail(Email $newEmail): void { /* invariant: nový != starý */ }
    public function changeName(string $newName): void { /* ... */ }

    // UserInterface – eraseCredentials() Symfony 8 z rozhraní odstranilo
    public function getRoles(): array { return ['ROLE_USER']; }
    public function getUserIdentifier(): string { return $this->email->value; }
    public function getPassword(): ?string { return $this->password->hash; }
}
:::

Jde o zjednodušenou variantu referenční implementace z kapitoly
[Implementace v Symfony 8](/implementace-v-symfony#entities) – událost `UserRegistered`
se nahrává ve factory `register()`, nikdy v konstruktoru. Dva kompromisy malého
příkladu: `UserInterface` implementuje přímo agregát, zatímco v plné architektuře
patří na security adapter v infrastrukturní vrstvě (viz
[Autorizace v DDD](/autorizace-v-ddd)). A `final` u entit mapovaných Doctrine projde – nativní lazy objekty
z entity nedědí.

Hash hesla nemá putovat do session. `PasswordAuthenticatedUserInterface` k tomu
doporučuje `__serialize()` a `__unserialize()` na entitě, které citlivé pole vynechají.

### Command Handler: RegisterUser {#register-user-handler}

:::code{language="php" filename="src/UserManagement/Registration/Command/RegisterUserHandler.php (skeleton)"}
#[AsMessageHandler]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        // Normalizace vstupu (trim, lowercase) patří do fromUserInput(),
        // konstruktor Email jen validuje.
        $email = Email::fromUserInput($command->email);

        $user = User::register(
            UserId::generate(),
            $command->name,
            $email,
            HashedPassword::fromPlainText($command->password),
        );

        try {
            $this->users->save($user);
            // Flush ručně: unique constraint se vyhodnotí až zde a překlad
            // na doménovou výjimku musí proběhnout uvnitř try bloku.
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw DuplicateEmailException::with($email, $e);
        }
    }
}
:::

Unikátnost e-mailu garantuje databázový constraint, ne kontrola přes `findByEmail()`
před zápisem. Ta je vůči souběžným registracím nedostatečná – rozbor race condition
a obou vrstev ochrany je v
[Implementaci v Symfony](/implementace-v-symfony#register-race-heading).

Autorizaci uživatele po přihlášení – čtyři vrstvy přístupu, Voter, doménové invarianty –
rozebírá [Autorizace v DDD](/autorizace-v-ddd).

## 23.04 Tři projekty vedle sebe {#tri-projekty-vedle-sebe}

Příklady se liší doménovou komplexitou i počtem kontextů. Srovnání ukazuje, co která
varianta vyžaduje a kde zvolená struktura narazí na strop:

| | E-shop | Blog | Správa uživatelů |
|---|---|---|---|
| **Komplexita domény** | Střední: invarianty v košíku, přechod stavu checkout → objednávka | Nízká: validace titulku a obsahu, uzavírání diskuse pod příspěvkem | Nízká až střední: unikátní e-mail, hash hesla, integrace se Security |
| **Počet Bounded Contexts** | 2 (Cart, Order) | 1 | 1 |
| **Použité vzory** | Agregáty, hodnotové objekty, doménová událost mezi kontexty, CQRS, repository | Agregát s entitou uvnitř, named constructor, CQRS slices, repository | Agregát s `UserInterface`, hodnotové objekty `Email` a `HashedPassword`, repository |
| **Co se změní při růstu** | Přibudou kontexty Payment, Inventory, Shipping; checkout se stane procesem přes [ságu](/sagy-a-process-managery); publikace událostí dostane [outbox](/outbox-pattern) | Moderace a verzování obsahu si vyžádají oddělený Comment kontext a read model pro výpisy | Role, oprávnění a SSO oddělí Identity od Profile; autorizační pravidla se přesunou do [voterů](/autorizace-v-ddd) |
| **Kdy struktura přestane stačit** | Když synchronní komunikace mezi kontexty začne vytvářet řetězy závislostí – pak nastupuje plně asynchronní integrace | Jakmile přibude workflow redakce a schvalování, přestane stačit jediný kontext s CRUD jádrem | Když počet pravidel „kdo smí co“ přeroste agregát – pravidla patří do samostatné autorizační vrstvy |

Rozhodnutí o hloubce stacku se odvíjí od domény, ne od technologie. Plný strategický
i taktický DDD (více kontextů, CQRS, doménové události, outbox) se vyplatí tam, kde
doména nese netriviální invarianty, na systému pracuje více týmů a jednotlivé části
se vyvíjejí různým tempem. E-shop z této kapitoly k tomu směřuje: dva kontexty
a událost mezi nimi jsou první krok, zbytek přijde s růstem.

Střední cesta – agregát s repository, bez oddělených read modelů a bez více kontextů –
pokrývá projekty typu blog nebo správa uživatelů. Doménová pravidla existují
a zaslouží si zapouzdření, ale čtení zůstává triviální a tým malý. Vyplatí se hlídat
jeden signál: jakmile výpisy začnou hydratovat agregáty jen kvůli zobrazení,
je čas na oddělený read model.

Kde pravidla nejsou žádná a aplikace jen přesouvá data mezi formulářem a tabulkou,
DDD nepřináší hodnotu a stojí čas. Symfony formuláře, Doctrine entity a generické
CRUD kontrolery takový případ řeší levněji. Hranici mezi oběma světy rozebírá
kapitola [Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

## 23.05 Závěr {#zaver}

Všechny tři příklady sledují stejný řetězec: kontroler → command bus → handler → agregát →
repozitář → událost. Variace v počtu Bounded Contexts, počtu agregátů a integraci se Symfony
Security tu kostru nemění. Doménové invarianty patří do agregátu, aplikační orchestraci nese
handler, infrastrukturu drží repozitář.

Ukázky zabírají střed toho řetězce. Kontrolery, Doctrine mapování a implementace repozitářů
zůstávají v [Implementaci v Symfony](/implementace-v-symfony), kde mají prostor na detail.

Reálný projekt s plnou doménovou analýzou, kontextovou mapou, read modely, reconciliation a
důsledky pro konzistenci rozebírá navazující [Případová studie](/pripadova-studie). Provede
vás systémem pro správu projektů krok za krokem – od event stormingu po hotové read modely.

:::faq{}
- question: Proč všechny tři příklady kombinují vertikální slice a CQRS?
  answer: 'Vertikální slice určuje, jak kód organizovat (podle feature); CQRS odděluje čtení od zápisu. Dohromady se doplňují: každá feature má vlastní command nebo query handler, vlastní model zápisu (agregát) a vlastní read model pro odpověď. Kombinace se v ukázkách opakuje záměrně; stejné členění drží i veřejné referenční projekty, například <code>CodelyTV/php-ddd-example</code>.'
- question: Lze strukturu z těchto příkladů přímo převzít do produkčního projektu?
  answer: 'Ukázky jsou záměrně zjednodušené – chybí jim autentizace, autorizace, transakční koordinace mezi agregáty, retry logika a komplexnější doménová pravidla. Převzít lze principy: oddělení doménové a infrastrukturní vrstvy, vertikální organizaci feature a CQRS sběrnici. Adresářová struktura slouží jako výchozí šablona; rozšiřuje se podle reálných potřeb projektu. Doporučená dlouhodobá architektura v kapitole <a href="/implementace-v-symfony">Implementace DDD v Symfony 8</a>.'
- question: Kde najdu plnou implementaci agregátu se všemi metodami?
  answer: 'V kapitolách <a href="/navrh-agregatu">Návrh agregátu</a> (kompletní agregát Order s invariantami, optimistickým zámkem, doménovými událostmi a Doctrine mappingem) a <a href="/implementace-v-symfony">Implementace v Symfony 8</a> (User agregát s Symfony Security, custom typy pro hodnotové objekty, repozitář s outbox patternem).'
- question: Proč je v každém příkladu jen jeden Bounded Context kromě e-shopu?
  answer: 'Pro shrnující kapitolu fungují srozumitelněji jednodušší případy s jedním kontextem. E-shop má dva kontexty (Cart a Order), aby ilustroval cross-context komunikaci přes doménovou událost <code>CartCheckedOut</code>. V reálném projektu by každý ze tří příkladů měl pravděpodobně více kontextů (Identity, Billing, Notifications), ale to už je doména <a href="/pripadova-studie">Případové studie</a>.'
:::
