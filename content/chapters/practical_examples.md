---
route: practical_examples
path: /prakticke-priklady
title: Praktické příklady
page_title: "Praktické příklady DDD v Symfony 8 | DDD Symfony"
meta_description: "Praktické příklady DDD v Symfony 8: e-commerce, blog a správa uživatelů v PHP 8.4+. Bounded Contexts, agregáty a vertikální slice na třech minimálních projektech."
meta_keywords: "DDD příklady, Symfony ukázky, bounded contexts, doménové modely, agregáty, e-commerce DDD, blog DDD, vertikální slice architektura, praktické implementace, ukázky kódu, reálné projekty"
og_type: article
published: "2025-04-24"
modified: "2026-05-04"
breadcrumb_name: Praktické příklady
schema_type: TechArticle
schema_headline: "Praktické příklady Domain-Driven Design v Symfony"
chapter_number: "24"
category: Praxe
deck: "Praktické příklady implementace Domain-Driven Design v Symfony 8 na třech zjednodušených projektech – e-commerce, blog a správa uživatelů. Ukázka bounded contexts, doménových modelů a vertikální slice architektury."
reading_time: 12
difficulty: 3
---

Tato kapitola je **shrnující průžez** předchozími kapitolami. Tři krátké příklady ukazují,
jak se vzory z taktického DDD, CQRS a Implementace v Symfony skládají do funkční aplikace.
Každý příklad obsahuje strukturu projektu a kostru klíčových tříd. Pro detailní implementace
(plné Doctrine mapování, testy, edge cases) odkazuje na předchozí kapitoly.

Pro hluboký ponor do reálného projektu pokračujte v navazující [Případové studii](/pripadova-studie),
která pokrývá systém pro správu projektů krok za krokem.

## 24.01 Příklad: E-commerce aplikace {#e-commerce}

E-commerce výřez nad košíkem a objednávkami. Dva Bounded Contexts: **Cart** (rozpracovaný nákup)
a **Order** (potvrzená transakce). Komunikace mezi nimi probíhá přes doménovou událost
`CartCheckedOut`, která spustí vytvoření `Order` agregátu.

:::diagram{fig="24.1-A" title="E-shop: bounded contexts Cart a Order" src="images/diagrams/7_examples/eshop/diagram.svg"}
:::

### Struktura projektu {#e-commerce-structure}

:::code{language="bash" filename="src/ struktura"}
src/
├── Cart/                      # Bounded Context: Košík
│   ├── Domain/
│   │   ├── Model/Cart.php          # Aggregate Root
│   │   ├── Model/CartItem.php
│   │   ├── ValueObject/CartId.php, ProductId.php, Quantity.php, Money.php
│   │   ├── Event/ItemAddedToCart.php, CartCheckedOut.php
│   │   └── Repository/CartRepository.php
│   ├── Infrastructure/Repository/DoctrineCartRepository.php
│   ├── AddItem/{Command, Controller}/  # Feature slice
│   ├── GetCart/{Query, ViewModel}/     # Feature slice
│   └── Checkout/Controller/             # Feature slice
├── Order/                     # Bounded Context: Objednávky
│   ├── Domain/Model/Order.php          # Aggregate Root
│   ├── Domain/Event/OrderCreated.php
│   └── CreateOrder/{Command, Controller}/
└── Shared/Domain/Exception/DomainException.php
:::

### Klíčový agregát: Cart {#cart-aggregate}

Agregát `Cart` chrání invariant „položka s týmž `productId` se nepřidává duplicitně, ale
zvyšuje se její quantity“. Skeleton:

:::code{language="php" filename="src/Cart/Domain/Model/Cart.php (skeleton)"}
final class Cart extends AggregateRoot
{
    public readonly CartId $id;
    public readonly UserId $userId;
    /** @var Collection<int, CartItem> */
    private Collection $items;

    public static function open(CartId $id, UserId $userId): self { /* ... */ }

    public function addItem(ProductId $productId, Quantity $quantity, Money $price): void
    {
        // Invariant: pokud productId existuje, zvyš quantity; jinak přidej nový item.
        // Vyemituje ItemAddedToCart event.
    }

    public function removeItem(ProductId $productId): void { /* ... */ }
    public function totalAmount(): Money { /* sumace přes items */ }
    public function checkout(): void { /* invariant: cart nesmí být prázdný */ }
}
:::

Plnou implementaci včetně Doctrine mappingu (`#[ORM\OneToMany]`, `cascade`, `orphanRemoval`,
optimistický zámek přes `#[ORM\Version]`) ukazuje [Návrh agregátu](/navrh-agregatu) a
[Implementace v Symfony](/implementace-v-symfony).

### Command Handler: AddItemToCart {#add-item-handler}

Tenký aplikační handler: načte agregát, deleguje doménovou logiku, uloží.

:::code{language="php" filename="src/Cart/AddItem/Command/AddItemToCartHandler.php (skeleton)"}
#[AsMessageHandler]
final class AddItemToCartHandler
{
    public function __construct(
        private CartRepository $cartRepository,
        private ProductRepository $productRepository,
    ) {}

    public function __invoke(AddItemToCart $command): void
    {
        $cart = $this->cartRepository->findByIdOrFail(new CartId($command->cartId));
        $product = $this->productRepository->findByIdOrFail(new ProductId($command->productId));

        $cart->addItem($product->id(), new Quantity($command->quantity), $product->price());

        $this->cartRepository->save($cart);
    }
}
:::

Plné CQRS implementaci s validací, autorizací a outbox patternem ukazuje [CQRS](/cqrs) a
[Outbox Pattern](/outbox-pattern).

## 24.02 Příklad: Blog {#blog}

Blogová aplikace s jedním Bounded Contextem (Blog), dvěma agregáty (`Post`, `Comment`) a
sekcemi pro vytvoření příspěvku, výpis a detail.

:::diagram{fig="24.2-A" title="Blog: doménový model a feature slices" src="images/diagrams/7_examples/blog/diagram.svg"}
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
    │   └── Repository/PostRepository.php
    ├── Infrastructure/Repository/DoctrinePostRepository.php
    ├── CreatePost/{Command, Controller}/
    ├── GetPost/{Query, Controller, ViewModel}/
    └── GetPosts/{Query, Controller, ViewModel}/
:::

### Klíčový agregát: Post {#post-aggregate}

Agregát `Post` se vytváří přes named constructor `create()`, který emituje `PostCreated` event.
Vlastní invarianty (titul má 3–255 znaků, autor není prázdný) jsou vynucené v konstruktoru.

:::code{language="php" filename="src/Blog/Domain/Model/Post.php (skeleton)"}
final class Post extends AggregateRoot
{
    private function __construct(
        public readonly PostId $id,
        private string $title,
        private string $content,
        public readonly AuthorId $authorId,
        public readonly \DateTimeImmutable $createdAt,
    ) {
        $this->record(new PostCreated($id, $title, $authorId));
    }

    public static function create(PostId $id, string $title, string $content, AuthorId $authorId): self
    {
        // Invarianty: title 3–255 znaků, content nesmí být prázdný
        return new self($id, $title, $content, $authorId, new \DateTimeImmutable());
    }

    public function updateTitle(string $newTitle): void { /* ... */ }
    public function updateContent(string $newContent): void { /* ... */ }
}
:::

### Command Handler: CreatePost {#create-post-handler}

:::code{language="php" filename="src/Blog/CreatePost/Command/CreatePostHandler.php (skeleton)"}
#[AsMessageHandler]
final class CreatePostHandler
{
    public function __construct(private PostRepository $posts) {}

    public function __invoke(CreatePost $command): string
    {
        $post = Post::create(
            PostId::generate(),
            $command->title,
            $command->content,
            new AuthorId($command->authorId),
        );

        $this->posts->save($post);

        return $post->id->value();
    }
}
:::

Pro implementaci read modelu pro výpis příspěvků (paginace, řazení podle data, projekce
z eventů) viz [CQRS – ViewModely a Read Modely](/cqrs#view-models) a [Výkonnostní aspekty](/vykonnostni-aspekty).

## 24.03 Příklad: Správa uživatelů {#user-management}

Bounded Context **UserManagement** s jediným agregátem `User` a třemi sub-features: registrace,
autentizace, profil. Integruje se se Symfony Security komponentou (implementuje
`UserInterface`).

:::diagram{fig="24.3-A" title="Správa uživatelů: feature slices" src="images/diagrams/7_examples/users/diagram.svg"}
:::

### Struktura projektu {#user-mgmt-structure}

:::code{language="bash" filename="src/ struktura"}
src/
└── UserManagement/            # Bounded Context: Správa uživatelů
    ├── Domain/
    │   ├── Model/User.php           # Aggregate Root
    │   ├── ValueObject/UserId.php, Email.php, HashedPassword.php
    │   ├── Event/UserRegistered.php
    │   └── Repository/UserRepository.php
    ├── Infrastructure/Repository/DoctrineUserRepository.php
    ├── Registration/{RegisterUser, RegisterUserHandler, RegistrationController}.php
    ├── Authentication/SecurityController.php
    └── Profile/{GetUserProfile, GetUserProfileHandler, ProfileController}.php
:::

### Klíčový agregát: User {#user-aggregate}

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
        $this->record(new UserRegistered($id, $email));
    }

    public static function register(UserId $id, string $name, Email $email, HashedPassword $password): self
    {
        return new self($id, $name, $email, $password, new \DateTimeImmutable());
    }

    public function changeEmail(Email $newEmail): void { /* invariant: nový != starý */ }
    public function changeName(string $newName): void { /* ... */ }

    // UserInterface
    public function getRoles(): array { return ['ROLE_USER']; }
    public function getUserIdentifier(): string { return $this->email->value(); }
    public function getPassword(): ?string { return $this->password->hash(); }
    public function eraseCredentials(): void {}
}
:::

### Command Handler: RegisterUser {#register-user-handler}

:::code{language="php" filename="src/UserManagement/Registration/RegisterUserHandler.php (skeleton)"}
#[AsMessageHandler]
final class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = new Email($command->email);

        // Invariant na úrovni handleru: email musí být unikátní (DB unique constraint
        // je pojistka pro race condition, viz Implementace v Symfony, sekce 11.13).
        if ($this->users->findByEmail($email) !== null) {
            throw new \DomainException('User with this email already exists.');
        }

        $user = User::register(
            UserId::generate(),
            $command->name,
            $email,
            HashedPassword::fromHasher($this->passwordHasher, $command->password),
        );

        $this->users->save($user);
    }
}
:::

Pro autorizaci uživatele po přihlášení (čtyři vrstvy přístupu, Voter, doménové invarianty)
viz [Autorizace v DDD](/autorizace-v-ddd).

## Závěr

Tři příklady ukazují stejný vzor: kontroler → command bus → handler → agregát → repozitář →
event. Drobné variace (počet Bounded Contexts, počet agregátů, integrace se Symfony Security)
nemění základní strukturu. Doménové invarianty jsou v agregátu, aplikační orchestrace v handleru,
infrastruktura v repozitáři.

Pro hluboký ponor do reálného projektu (s plnou doménovou analýzou, kontextovou mapou, read
modely, reconciliation a důsledky pro konzistenci) pokračujte v navazující [Případové
studii](/pripadova-studie). Pokrývá systém pro správu projektů krok za krokem od event stormingu
po deployment.

:::faq{}
- question: Proč všechny tři příklady kombinují vertikální slice a CQRS?
  answer: 'Vertikální slice určuje, jak kód organizovat (podle feature), CQRS určuje, jak oddělit čtení od zápisu. Dohromady se doplňují: každá feature má vlastní command nebo query handler, vlastní model zápisu (agregát) a vlastní read model pro odpověď. Tato kombinace se v ukázkách opakuje záměrně – odpovídá typickému tvaru produkčního DDD projektu v Symfony 8.'
- question: Lze strukturu z těchto příkladů přímo převzít do produkčního projektu?
  answer: 'Ukázky jsou záměrně zjednodušené – chybí jim autentizace, autorizace, transakční koordinace mezi agregáty, retry logika a komplexnější doménová pravidla. Převzít lze principy: oddělení doménové a infrastrukturní vrstvy, vertikální organizaci feature a CQRS sběrnici. Strukturu adresářů je vhodné použít jako výchozí šablonu a postupně ji rozšiřovat podle reálných potřeb projektu. Doporučená dlouhodobá architektura v kapitole <a href="/implementace-v-symfony">Implementace DDD v Symfony 8</a>.'
- question: Kde najdu plnou implementaci agregátu se všemi metodami?
  answer: 'V kapitolách <a href="/navrh-agregatu">Návrh agregátu</a> (kompletní agregát Order s invariantami, optimistickým zámkem, doménovými událostmi a Doctrine mappingem) a <a href="/implementace-v-symfony">Implementace v Symfony 8</a> (User agregát s Symfony Security, custom typy pro hodnotové objekty, repozitář s outbox patternem).'
- question: Proč je v každém příkladu jen jeden Bounded Context kromě e-shopu?
  answer: 'Pro shrnující příklady jsou jednodušší případy s jedním kontextem srozumitelnější. E-shop má dva kontexty (Cart a Order), aby ilustroval cross-context komunikaci přes doménovou událost <code>CartCheckedOut</code>. V reálném projektu by každý ze tří příkladů měl pravděpodobně více kontextů (Identity, Billing, Notifications), ale to už je doména <a href="/pripadova-studie">Případové studie</a>.'
:::
