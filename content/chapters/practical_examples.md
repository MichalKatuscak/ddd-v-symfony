---
route: practical_examples
path: /prakticke-priklady
title: Praktické příklady
page_title: "Praktické příklady DDD v Symfony 8 | DDD Symfony"
meta_description: "Praktické příklady DDD v Symfony 8: e-commerce, blog a správa uživatelů v PHP 8.4+. Bounded Contexts, agregáty a vertikální slice na třech minimálních projektech."
meta_keywords: "DDD příklady, Symfony ukázky, bounded contexts, doménové modely, agregáty, e-commerce DDD, blog DDD, vertikální slice architektura, praktické implementace, ukázky kódu, reálné projekty"
og_type: article
published: "2025-04-24"
modified: "2026-04-28"
breadcrumb_name: Praktické příklady
schema_type: TechArticle
schema_headline: "Praktické příklady Domain-Driven Design v Symfony"
chapter_number: "24"
category: Praxe
deck: "Praktické příklady implementace Domain-Driven Design v Symfony 8 na třech zjednodušených projektech – e-commerce, blog a správa uživatelů. Ukázka bounded contexts, doménových modelů a vertikální slice architektury."
reading_time: 30
difficulty: 3
---

Předchozí kapitoly pokryly teorii i implementační detaily –
od [CQRS](/cqrs) přes
[Event Sourcing](/event-sourcing) až po
[ságy a process managery](/sagy-a-process-managery).
Tato kapitola zasazuje tyto vzory do reálnějšího kontextu a ukazuje, jak spolu fungují.

## 24.01 Příklad: E-commerce aplikace {#e-commerce}

Tato část ukazuje implementaci části e-commerce aplikace pomocí vertikální slice architektury
a CQRS v Symfony 8 na funkcionalitě košíku a objednávek.

:::diagram{fig="24.1-A" title="E-shop: bounded contexts Cart a Order" src="images/diagrams/7_examples/eshop/diagram.svg"}
:::

### Struktura projektu {#e-commerce-structure-heading}

```bash
src/
├── Cart/                      # Bounded Context: Košík
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   ├── Cart.php        # Entita košíku (Aggregate Root)
│   │   │   └── CartItem.php    # Entita položky košíku
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── CartId.php      # Identifikátor košíku
│   │   │   ├── ProductId.php   # Identifikátor produktu
│   │   │   ├── Quantity.php    # Množství
│   │   │   └── Money.php       # Peněžní částka
│   │   ├── Event/             # Doménové události
│   │   │   └── ItemAddedToCart.php  # Událost přidání položky
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── CartRepository.php  # Rozhraní pro práci s košíkem
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineCartRepository.php  # Doctrine implementace
│   ├── AddItem/               # Feature: Přidání položky do košíku
│   │   ├── Command/           # Příkazy
│   │   │   ├── AddItemToCart.php  # Příkaz pro přidání položky
│   │   │   └── AddItemToCartHandler.php  # Handler příkazu
│   │   └── Controller/        # Kontrolery
│   │       └── CartController.php  # Kontroler pro přidání do košíku
│   ├── GetCart/               # Feature: Získání košíku
│   │   ├── Query/             # Dotazy
│   │   │   ├── GetCart.php      # Dotaz pro získání košíku
│   │   │   └── GetCartHandler.php  # Handler dotazu
│   │   └── ViewModel/         # View modely
│   │       └── CartViewModel.php  # View model košíku
│   └── Checkout/              # Feature: Pokladna
│       └── Controller/        # Kontrolery
│           └── CheckoutController.php  # Kontroler pro pokladnu
├── Order/                     # Bounded Context: Objednávky
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   ├── Order.php       # Entita objednávky (Aggregate Root)
│   │   │   └── OrderItem.php   # Entita položky objednávky
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   └── OrderId.php     # Identifikátor objednávky
│   │   ├── Event/             # Doménové události
│   │   │   └── OrderCreated.php  # Událost vytvoření objednávky
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── OrderRepository.php  # Rozhraní pro práci s objednávkami
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrineOrderRepository.php  # Doctrine implementace
│   └── CreateOrder/           # Feature: Vytvoření objednávky
│       ├── Command/           # Příkazy
│       │   ├── CreateOrder.php  # Příkaz pro vytvoření objednávky
│       │   └── CreateOrderHandler.php  # Handler příkazu
│       └── Controller/        # Kontrolery
│           └── OrderController.php  # Kontroler pro objednávky
└── Shared/                    # Sdílené komponenty
    ├── Domain/                # Sdílená doménová logika
    │   └── Exception/         # Výjimky
    │       └── DomainException.php  # Základní doménová výjimka
    └── Infrastructure/        # Sdílená infrastruktura
        └── Bus/               # Implementace message bus
            ├── MessengerCommandBus.php  # Implementace command bus
            └── MessengerQueryBus.php  # Implementace query bus
```

### Doménový model: Košík {#cart-model-heading}

```php
<?php

declare(strict_types=1);

namespace App\Cart\Domain\Model;

use App\Cart\Domain\Event\ItemAddedToCart;
use App\Cart\Domain\ValueObject\CartId;
use App\Cart\Domain\ValueObject\ProductId;
use App\Cart\Domain\ValueObject\Quantity;
use App\Cart\Domain\ValueObject\Money;

class Cart
{
    private readonly CartId $id;
    private readonly string $userId;
    private array $items = [];
    private readonly \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private array $domainEvents = [];

    private function __construct(CartId $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public static function create(CartId $id, string $userId): self
    {
        return new self($id, $userId);
    }

    public function id(): CartId
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function addItem(ProductId $productId, Quantity $quantity, Money $price): void
    {
        // Kontrola, zda produkt již v košíku existuje
        foreach ($this->items as $item) {
            if ($item->productId()->equals($productId)) {
                $item->increaseQuantity($quantity);
                $this->updatedAt = new \DateTimeImmutable();

                $this->recordEvent(new ItemAddedToCart(
                    $this->id,
                    $productId,
                    $quantity,
                    $price
                ));

                return;
            }
        }

        // Přidání nové položky do košíku
        $this->items[] = new CartItem(
            $this->id,
            $productId,
            $quantity,
            $price
        );

        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new ItemAddedToCart(
            $this->id,
            $productId,
            $quantity,
            $price
        ));
    }

    public function removeItem(ProductId $productId): void
    {
        $this->items = array_filter($this->items, function (CartItem $item) use ($productId) {
            return !$item->productId()->equals($productId);
        });

        $this->updatedAt = new \DateTimeImmutable();
    }

    public function items(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function totalAmount(): Money
    {
        $total = new Money(0, 'CZK');

        foreach ($this->items as $item) {
            $total = $total->add($item->totalPrice());
        }

        return $total;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
```

### Command: Přidání položky do košíku {#add-to-cart-command-heading}

```php
<?php

declare(strict_types=1);

namespace App\Cart\AddItem\Command;

use Symfony\Component\Validator\Constraints as Assert;

class AddItemToCart
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $cartId,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $productId,

        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public readonly int $quantity,
    ) {
    }
}
```

### Command Handler: Zpracování přidání položky do košíku {#add-to-cart-handler-heading}

```php
<?php

declare(strict_types=1);

namespace App\Cart\AddItem\Command;

use App\Cart\Domain\Repository\CartRepository;
use App\Cart\Domain\Repository\ProductRepository;
use App\Cart\Domain\ValueObject\CartId;
use App\Cart\Domain\ValueObject\ProductId;
use App\Cart\Domain\ValueObject\Quantity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddItemToCartHandler
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly ProductRepository $productRepository
    ) {
    }

    public function __invoke(AddItemToCart $command): void
    {
        $cart = $this->cartRepository->findById(new CartId($command->cartId));

        if (!$cart) {
            throw new \DomainException('Cart not found');
        }

        $productId = new ProductId($command->productId);
        $product = $this->productRepository->findById($productId);

        if (!$product) {
            throw new \DomainException('Product not found');
        }

        $cart->addItem(
            $productId,
            new Quantity($command->quantity),
            $product->price()
        );

        $this->cartRepository->save($cart);
    }
}
```

### Controller: Přidání položky do košíku {#add-to-cart-controller-heading}

```php
<?php

declare(strict_types=1);

namespace App\Cart\AddItem\Controller;

use App\Cart\AddItem\Command\AddItemToCart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        $cartId = $request->getSession()->get('cart_id');

        if (!$cartId) {
            // Vytvoření nového košíku by mělo být implementováno v jiném handleru
            throw new \RuntimeException('Cart not initialized');
        }

        $command = new AddItemToCart(
            $cartId,
            $request->request->get('product_id'),
            (int) $request->request->get('quantity', 1),
        );

        try {
            $this->commandBus->dispatch($command);

            $this->addFlash('success', 'Product added to cart');

            return $this->redirectToRoute('cart_view');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('product_detail', [
                'id' => $request->request->get('product_id')
            ]);
        }
    }
}
```

### Query: Získání košíku {#get-cart-query-heading}

```php
<?php

declare(strict_types=1);

namespace App\Cart\GetCart\Query;

use Symfony\Component\Validator\Constraints as Assert;

class GetCart
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $cartId
    ) {
    }
}
```

### Query Handler: Zpracování získání košíku {#get-cart-handler-heading}

```php
<?php

declare(strict_types=1);

namespace App\Cart\GetCart\Query;

use App\Cart\Domain\Repository\CartRepository;
use App\Cart\Domain\ValueObject\CartId;
use App\Cart\GetCart\ViewModel\CartItemViewModel;
use App\Cart\GetCart\ViewModel\CartViewModel;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetCartHandler
{
    public function __construct(
        private readonly CartRepository $cartRepository
    ) {
    }

    public function __invoke(GetCart $query): ?CartViewModel
    {
        $cart = $this->cartRepository->findById(new CartId($query->cartId));

        if (!$cart) {
            return null;
        }

        $items = [];

        foreach ($cart->items() as $item) {
            $items[] = new CartItemViewModel(
                $item->productId()->value(),
                $item->quantity()->value(),
                $item->price()->value(),
                $item->totalPrice()->value()
            );
        }

        return new CartViewModel(
            $cart->id()->value(),
            $items,
            $cart->totalAmount()->value(),
            $cart->updatedAt()
        );
    }
}
```

## 24.02 Příklad: Blog {#blog}

Tato část ukazuje implementaci blogu pomocí vertikální slice architektury a CQRS v Symfony 8.

:::diagram{fig="24.2-A" title="Blog: doménový model a feature slices" src="images/diagrams/7_examples/blog/diagram.svg"}
:::

### Struktura projektu {#blog-structure-heading}

```bash
src/
├── Blog/                      # Bounded Context: Blog
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   ├── Post.php        # Entita příspěvku (Aggregate Root)
│   │   │   └── Comment.php     # Entita komentáře
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── PostId.php      # Identifikátor příspěvku
│   │   │   └── CommentId.php   # Identifikátor komentáře
│   │   ├── Event/             # Doménové události
│   │   │   └── PostCreated.php  # Událost vytvoření příspěvku
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── PostRepository.php  # Rozhraní pro práci s příspěvky
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/        # Implementace repozitářů
│   │       └── DoctrinePostRepository.php  # Doctrine implementace
│   ├── CreatePost/            # Feature: Vytvoření příspěvku
│   │   ├── Command/           # Příkazy
│   │   │   ├── CreatePost.php   # Příkaz pro vytvoření příspěvku
│   │   │   └── CreatePostHandler.php  # Handler příkazu
│   │   └── Controller/        # Kontrolery
│   │       └── CreatePostController.php  # Kontroler pro vytvoření příspěvku
│   ├── GetPost/               # Feature: Zobrazení příspěvku
│   │   ├── Query/             # Dotazy
│   │   │   ├── GetPost.php      # Dotaz pro získání příspěvku
│   │   │   └── GetPostHandler.php  # Handler dotazu
│   │   ├── Controller/        # Kontrolery
│   │   │   └── PostController.php  # Kontroler pro zobrazení příspěvku
│   │   └── ViewModel/         # View modely
│   │       └── PostViewModel.php  # View model příspěvku
│   └── GetPosts/              # Feature: Seznam příspěvků
│       ├── Query/             # Dotazy
│       │   ├── GetPosts.php     # Dotaz pro získání příspěvků
│       │   └── GetPostsHandler.php  # Handler dotazu
│       ├── Controller/        # Kontrolery
│       │   └── PostsController.php  # Kontroler pro seznam příspěvků
│       └── ViewModel/         # View modely
│           └── PostListViewModel.php  # View model seznamu příspěvků
└── Shared/                    # Sdílené komponenty
    ├── Domain/                # Sdílená doménová logika
    │   └── Exception/         # Výjimky
    │       └── DomainException.php  # Základní doménová výjimka
    └── Infrastructure/        # Sdílená infrastruktura
        └── Bus/               # Implementace message bus
            ├── MessengerCommandBus.php  # Implementace command bus
            └── MessengerQueryBus.php  # Implementace query bus
```

### Doménový model: Příspěvek {#post-model-heading}

```php
<?php

declare(strict_types=1);

namespace App\Blog\Domain\Model;

use App\Blog\Domain\Event\PostCreated;
use App\Blog\Domain\ValueObject\PostId;

class Post
{
    private readonly PostId $id;
    private string $title;
    private string $content;
    private readonly string $author;
    private readonly \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    private array $domainEvents = [];

    private function __construct(PostId $id, string $title, string $content, string $author)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->createdAt = new \DateTimeImmutable();

        $this->recordEvent(new PostCreated($id, $title, $author));
    }

    public static function create(PostId $id, string $title, string $content, string $author): self
    {
        return new self($id, $title, $content, $author);
    }

    public function id(): PostId
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function updateTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateContent(string $content): void
    {
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
```

### Command: Vytvoření příspěvku

```php
<?php

declare(strict_types=1);

namespace App\Blog\CreatePost\Command;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePost
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public readonly string $title,

        #[Assert\NotBlank]
        public readonly string $content,

        #[Assert\NotBlank]
        public readonly string $author
    ) {
    }
}
```

### Command Handler: Zpracování vytvoření příspěvku

```php
<?php

declare(strict_types=1);

namespace App\Blog\CreatePost\Command;

use App\Blog\Domain\Model\Post;
use App\Blog\Domain\Repository\PostRepository;
use App\Blog\Domain\ValueObject\PostId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreatePostHandler
{
    public function __construct(
        private readonly PostRepository $postRepository
    ) {
    }

    public function __invoke(CreatePost $command): string
    {
        $postId = new PostId();

        $post = Post::create(
            $postId,
            $command->title,
            $command->content,
            $command->author
        );

        $this->postRepository->save($post);

        return $postId->value();
    }
}
```

## 24.03 Příklad: Správa uživatelů {#user-management}

Tato sekce ukazuje správu uživatelů pomocí DDD a CQRS v Symfony 8. Uživatelé tvoří společnou doménu;
DDD odděluje jednotlivé funkce (registrace, autentizace, profil).

:::diagram{fig="24.3-A" title="Správa uživatelů: feature slices" src="images/diagrams/7_examples/users/diagram.svg"}
:::

### Struktura projektu

```bash
src/
├── UserManagement/            # Bounded Context: Správa uživatelů
│   ├── Domain/                # Doménová vrstva
│   │   ├── Model/             # Doménové modely
│   │   │   └── User.php       # Entita uživatele (Aggregate Root)
│   │   ├── ValueObject/       # Hodnotové objekty
│   │   │   ├── UserId.php
│   │   │   └── Email.php
│   │   ├── Event/             # Doménové události
│   │   │   └── UserRegistered.php
│   │   └── Repository/        # Repozitáře (rozhraní)
│   │       └── UserRepository.php
│   ├── Infrastructure/        # Infrastrukturní vrstva
│   │   └── Repository/
│   │       └── DoctrineUserRepository.php
│   ├── Registration/          # Sub-feature: Registrace
│   │   ├── RegisterUser.php   # Command
│   │   ├── RegisterUserHandler.php  # Command Handler
│   │   └── RegistrationController.php  # Controller
│   ├── Authentication/        # Sub-feature: Autentizace
│   │   └── SecurityController.php  # Controller
│   └── Profile/               # Sub-feature: Profil
│       ├── GetUserProfile.php  # Query
│       ├── GetUserProfileHandler.php  # Query Handler
│       └── ProfileController.php  # Controller
└── Shared/                    # Sdílené komponenty (pouze cross-cutting concerns)
    ├── Domain/                # Sdílená doménová logika
    │   └── Exception/         # Výjimky
    │       └── DomainException.php  # Základní doménová výjimka
    └── Infrastructure/        # Sdílená infrastruktura
        └── Bus/               # Implementace message bus
            ├── MessengerCommandBus.php  # Implementace command bus
            └── MessengerQueryBus.php  # Implementace query bus
```

### Doménový model: Uživatel

```php
<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Model;

use App\UserManagement\Domain\Event\UserRegistered;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private readonly UserId $id;
    private string $name;
    private Email $email;
    private ?string $password = null;
    private array $roles = [];
    private readonly \DateTimeImmutable $createdAt;

    private array $domainEvents = [];

    private function __construct(UserId $id, string $name, Email $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new \DateTimeImmutable();

        $this->recordEvent(new UserRegistered($id, $email));
    }

    public static function register(UserId $id, string $name, Email $email): self
    {
        return new self($id, $name, $email);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Implementace UserInterface
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // Pokud ukládáte dočasné, citlivé údaje o uživateli, vymažte je zde
    }

    public function getUserIdentifier(): string
    {
        return $this->email->value();
    }

    // Implementace PasswordAuthenticatedUserInterface
    public function getPassword(): ?string
    {
        return $this->password;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
```

### Command: Registrace uživatele

```php
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUser
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $name,

        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public readonly string $password
    ) {
    }
}
```

### Command Handler: Zpracování registrace uživatele

```php
<?php

declare(strict_types=1);

namespace App\UserManagement\Registration\Command;

use App\UserManagement\Domain\Model\User;
use App\UserManagement\Domain\Repository\UserRepository;
use App\UserManagement\Domain\ValueObject\Email;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(RegisterUser $command): void
    {
        $email = new Email($command->email);

        if ($this->userRepository->findByEmail($email)) {
            throw new \DomainException('User with this email already exists');
        }

        $user = User::register(
            new UserId(),
            $command->name,
            $email
        );

        // Set password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
```

Všechny tři příklady ukazují stejný vzor: kontroler pouze dispatchuje command nebo query,
aplikační vrstva koordinuje operaci a doménový model vynucuje doménová pravidla.
Konkrétní strukturu a konfiguraci takového projektu popisuje kapitola
[Implementace DDD v Symfony 8](/implementace-v-symfony).

:::faq{}
- question: Proč všechny tři příklady kombinují vertikální slice a CQRS?
  answer: 'Vertikální slice určuje, jak kód organizovat (podle feature), CQRS určuje, jak oddělit čtení od zápisu. Dohromady se doplňují: každá feature má vlastní command nebo query handler, vlastní model zápisu (agregát) a vlastní read model pro odpověď. Tato kombinace se v ukázkách opakuje záměrně – odpovídá typickému tvaru produkčního DDD projektu v Symfony 8. Rozbor principu v <a href="#e-commerce">sekci E-commerce aplikace</a>.'
- question: Jaký je rozdíl mezi Command a Query handlerem v těchto ukázkách?
  answer: 'Command handler mění stav agregátu a zpravidla nevrací nic (výjimkou je identifikátor nově vytvořeného objektu – viz <code>CreatePostHandler</code>). Query handler čte data a vrací view model optimalizovaný pro konkrétní obrazovku, nikoli doménový agregát – viz <code>GetCartHandler</code> vracející <code>CartViewModel</code>. Oddělení umožňuje každou stranu škálovat a testovat nezávisle. Ukázka obou v <a href="#e-commerce">sekci E-commerce aplikace</a>.'
- question: Proč agregát Cart pracuje s hodnotovými objekty Money a Quantity místo primitivních typů?
  answer: 'Hodnotové objekty nesou doménovou sémantiku a vynucují validaci při vzniku – <code>Quantity</code> nemůže být záporná, <code>Money</code> vždy nese měnu. Signatura metody <code>addItem(ProductId, Quantity, Money)</code> je samopopisná a typový systém brání záměně argumentů. Primitivní typy takovou kontrolu neposkytují a typické chyby (prohozené argumenty, neplatné hodnoty) se projeví až za běhu. Ukázka použití v <a href="#cart-model-heading">sekci Doménový model: Košík</a>.'
- question: Jak kontroler v Symfony 8 předává příkaz handleru?
  answer: 'Kontroler sestaví DTO příkazu (např. <code>AddItemToCart</code>) a předá jej do <code>MessageBusInterface::dispatch()</code>. Messenger podle atributu <code>#[AsMessageHandler]</code> najde příslušný handler a zavolá jej. Kontroler tedy nezná konkrétní handler, pouze sběrnici – což usnadňuje testování, výměnu implementací a asynchronní zpracování. Konfigurace sběrnice je popsána v kapitole <a href="/implementace-v-symfony">Implementace DDD v Symfony 8</a>.'
- question: Lze strukturu z těchto příkladů přímo převzít do produkčního projektu?
  answer: 'Ukázky jsou záměrně zjednodušené – chybí jim autentizace, autorizace, transakční koordinace mezi agregáty, retry logika a komplexnější doménová pravidla. Převzít lze principy: oddělení doménové a infrastrukturní vrstvy, vertikální organizaci feature a CQRS sběrnici. Strukturu adresářů je vhodné použít jako výchozí šablonu a postupně ji rozšiřovat podle reálných potřeb projektu. Doporučená dlouhodobá architektura v kapitole <a href="/implementace-v-symfony">Implementace DDD v Symfony 8</a>.'
:::
