# Markdown Chapters — implementační plán

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Přesunout obsah 35 kapitol z individuálních Twig šablon do Markdown souborů s YAML frontmatter, přičemž výstup pro návštěvníky zůstává bit-for-bit identický.

**Architecture:** Každá kapitola je `.md` soubor s YAML frontmatter (metadata) a Markdown tělem (obsah). `ChapterRouteLoader` prochází soubory a registruje pojmenované Symfony routy. `ChapterController::show()` parsuje soubor přes `ChapterMarkdownParser` a předává data do jedné generické `chapter.html.twig`. Speciální bloky (`:::callout`, `:::diagram`, `:::code`, `:::faq`) se pre-processují před Markdown parserem a nahrazují se HTML generovaným z existujících Twig partials.

**Tech Stack:** PHP 8.4, Symfony 8, `league/commonmark` ^2.6, `symfony/yaml` (již v projektu), Twig 3

---

## Mapování souborů

| Soubor | Akce | Zodpovědnost |
|--------|------|--------------|
| `content/chapters/*.md` | Vytvořit (35 souborů) | YAML frontmatter + Markdown obsah |
| `src/Content/ChapterFrontmatter.php` | Vytvořit | readonly DTO pro data z frontmatter |
| `src/Content/ParsedChapter.php` | Vytvořit | Value object: frontmatter + HTML string |
| `src/Content/Block/CalloutRenderer.php` | Vytvořit | `:::callout` → HTML přes callout.html.twig |
| `src/Content/Block/DiagramRenderer.php` | Vytvořit | `:::diagram` → HTML přes diagram.html.twig |
| `src/Content/Block/CodeBlockRenderer.php` | Vytvořit | `:::code` → HTML přes code_block.html.twig |
| `src/Content/Block/FaqRenderer.php` | Vytvořit | `:::faq` → HTML přes faq.html.twig |
| `src/Content/ChapterHeadingRenderer.php` | Vytvořit | CommonMark custom renderer pro h2 s h-num + section wrapping |
| `src/Content/ChapterMarkdownParser.php` | Vytvořit | Orchestrátor: frontmatter + blok pre-processing + CommonMark |
| `src/Controller/ChapterController.php` | Vytvořit | Jeden action: `show()` |
| `templates/chapter.html.twig` | Vytvořit | Generická šablona (nahrazuje 35 šablon) |
| `src/Content/ChapterRouteLoader.php` | Vytvořit | Registrace Symfony rout z MD souborů |
| `config/routes.yaml` | Upravit | Přidat entry pro chapter loader |
| `src/Controller/DddController.php` | Postupně upravovat | Mazat actions při migraci každé kapitoly |

---

## Task 1: Nainstalovat league/commonmark

**Files:**
- Modify: `composer.json`

- [ ] **Krok 1: Nainstalovat balíček**

```bash
composer require league/commonmark:^2.6
```

Očekávaný výstup: `Package operations: 1 install, ... league/commonmark v2.x.x`

- [ ] **Krok 2: Ověřit instalaci**

```bash
php -r "require 'vendor/autoload.php'; echo (new League\CommonMark\MarkdownConverter(new League\CommonMark\Environment\Environment()))->convert('# test')->getContent();"
```

Očekávaný výstup: `<h1>test</h1>`

- [ ] **Krok 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "feat(content): add league/commonmark"
```

---

## Task 2: ChapterFrontmatter DTO + ParsedChapter

**Files:**
- Create: `src/Content/ChapterFrontmatter.php`
- Create: `src/Content/ParsedChapter.php`

- [ ] **Krok 1: Vytvořit `src/Content/ChapterFrontmatter.php`**

```php
<?php

declare(strict_types=1);

namespace App\Content;

final readonly class ChapterFrontmatter
{
    public function __construct(
        public string $route,
        public string $path,
        public string $title,
        public string $pageTitle,
        public string $metaDescription,
        public string $metaKeywords,
        public string $ogType,
        public ?string $published,
        public ?string $modified,
        public string $breadcrumbName,
        public string $schemaType,
        public string $schemaHeadline,
        public ?string $chapterNumber,
        public ?string $category,
        public ?string $deck,
        public ?int $readingTime,
        public ?int $difficulty,
        public ?string $githubExamples,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            route: $data['route'],
            path: $data['path'],
            title: $data['title'],
            pageTitle: $data['page_title'] ?? ($data['title'] . ' | DDD Symfony'),
            metaDescription: $data['meta_description'],
            metaKeywords: $data['meta_keywords'],
            ogType: $data['og_type'] ?? 'article',
            published: isset($data['published']) ? (string) $data['published'] : null,
            modified: isset($data['modified']) ? (string) $data['modified'] : null,
            breadcrumbName: $data['breadcrumb_name'],
            schemaType: $data['schema_type'] ?? 'TechArticle',
            schemaHeadline: $data['schema_headline'] ?? $data['title'],
            chapterNumber: $data['chapter_number'] ?? null,
            category: $data['category'] ?? null,
            deck: $data['deck'] ?? null,
            readingTime: isset($data['reading_time']) ? (int) $data['reading_time'] : null,
            difficulty: isset($data['difficulty']) ? (int) $data['difficulty'] : null,
            githubExamples: $data['github_examples'] ?? null,
        );
    }
}
```

- [ ] **Krok 2: Vytvořit `src/Content/ParsedChapter.php`**

```php
<?php

declare(strict_types=1);

namespace App\Content;

final readonly class ParsedChapter
{
    public function __construct(
        public ChapterFrontmatter $frontmatter,
        public string $html,
    ) {}
}
```

- [ ] **Krok 3: Commit**

```bash
git add src/Content/
git commit -m "feat(content): ChapterFrontmatter DTO + ParsedChapter value object"
```

---

## Task 3: Block renderers (Callout, Diagram, CodeBlock, FAQ)

Tyto třídy přijímají parsovaná data z `:::type{...}` bloků a generují HTML identické s existujícími Twig partials. Všechny přijímají Twig `Environment` v konstruktoru.

**Files:**
- Create: `src/Content/Block/CalloutRenderer.php`
- Create: `src/Content/Block/DiagramRenderer.php`
- Create: `src/Content/Block/CodeBlockRenderer.php`
- Create: `src/Content/Block/FaqRenderer.php`

- [ ] **Krok 1: Vytvořit `src/Content/Block/CalloutRenderer.php`**

Callout partial (`_partials/callout.html.twig`) přijímá: `type` ('pattern'|'anti'|'note'|'warn'), volitelný `label`, `body` (HTML string).

V Markdown: `:::callout{type="note"}` nebo `:::callout{type="warn" label="Pozor"}`.

```php
<?php

declare(strict_types=1);

namespace App\Content\Block;

use Twig\Environment;

final readonly class CalloutRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs, string $bodyMarkdown, callable $markdownToHtml): string
    {
        $parsed = $this->parseAttrs($attrs);
        $type = $parsed['type'] ?? 'note';
        $label = $parsed['label'] ?? null;
        $bodyHtml = $markdownToHtml($bodyMarkdown);

        $params = ['type' => $type, 'body' => $bodyHtml];
        if ($label !== null) {
            $params['label'] = $label;
        }

        return $this->twig->render('_partials/callout.html.twig', $params);
    }

    private function parseAttrs(string $attrs): array
    {
        preg_match_all('/(\w+)="([^"]*)"/', $attrs, $m, PREG_SET_ORDER);
        $result = [];
        foreach ($m as $match) {
            $result[$match[1]] = $match[2];
        }
        return $result;
    }
}
```

- [ ] **Krok 2: Vytvořit `src/Content/Block/DiagramRenderer.php`**

Diagram partial (`_partials/diagram.html.twig`) přijímá: `fig`, `title`, `src`, volitelně `alt`, `caption`. SVG jsou `<img src="...">` (barvy jsou hardcoded, ne CSS proměnné).

V Markdown: `:::diagram{fig="02.1" title="Základní koncepty" src="images/diagrams/2_basic_concepts/diagram.svg"}`.

```php
<?php

declare(strict_types=1);

namespace App\Content\Block;

use Twig\Environment;

final readonly class DiagramRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs): string
    {
        $parsed = $this->parseAttrs($attrs);

        return $this->twig->render('_partials/diagram.html.twig', $parsed);
    }

    private function parseAttrs(string $attrs): array
    {
        preg_match_all('/(\w+)="([^"]*)"/', $attrs, $m, PREG_SET_ORDER);
        $result = [];
        foreach ($m as $match) {
            $result[$match[1]] = $match[2];
        }
        return $result;
    }
}
```

- [ ] **Krok 3: Vytvořit `src/Content/Block/CodeBlockRenderer.php`**

Code block partial (`_partials/code_block.html.twig`) přijímá: `filename`, `language`, `code`, volitelně `highlights` (pole čísel řádků).

V Markdown:
```
:::code{filename="src/Order/Domain/Order.php" language="php" highlights="3,5"}
<?php
...kód...
:::
```

`highlights` je string "3,5" → převést na pole `[3, 5]`.

```php
<?php

declare(strict_types=1);

namespace App\Content\Block;

use Twig\Environment;

final readonly class CodeBlockRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs, string $body): string
    {
        $parsed = $this->parseAttrs($attrs);
        $params = [
            'filename' => $parsed['filename'] ?? '',
            'language' => $parsed['language'] ?? 'php',
            'code'     => $body,
        ];
        if (!empty($parsed['highlights'])) {
            $params['highlights'] = array_map('intval', explode(',', $parsed['highlights']));
        }

        return $this->twig->render('_partials/code_block.html.twig', $params);
    }

    private function parseAttrs(string $attrs): array
    {
        preg_match_all('/(\w+)="([^"]*)"/', $attrs, $m, PREG_SET_ORDER);
        $result = [];
        foreach ($m as $match) {
            $result[$match[1]] = $match[2];
        }
        return $result;
    }
}
```

- [ ] **Krok 4: Vytvořit `src/Content/Block/FaqRenderer.php`**

FAQ partial (`_partials/faq.html.twig`) přijímá: `items` (pole `{question, answer}`), volitelně `heading`. Generuje i FAQPage JSON-LD schema.

V Markdown:
```
:::faq{heading="Časté otázky"}
- question: Kdy použít DDD?
  answer: Odpověď...
- question: Co je Aggregate?
  answer: Odpověď...
:::
```

```php
<?php

declare(strict_types=1);

namespace App\Content\Block;

use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

final readonly class FaqRenderer
{
    public function __construct(private Environment $twig) {}

    public function render(string $attrs, string $body): string
    {
        $parsed = $this->parseAttrs($attrs);
        $items = Yaml::parse(trim($body));

        $params = ['items' => $items];
        if (!empty($parsed['heading'])) {
            $params['heading'] = $parsed['heading'];
        }

        return $this->twig->render('_partials/faq.html.twig', $params);
    }

    private function parseAttrs(string $attrs): array
    {
        preg_match_all('/(\w+)="([^"]*)"/', $attrs, $m, PREG_SET_ORDER);
        $result = [];
        foreach ($m as $match) {
            $result[$match[1]] = $match[2];
        }
        return $result;
    }
}
```

- [ ] **Krok 5: Commit**

```bash
git add src/Content/Block/
git commit -m "feat(content): block renderers – callout, diagram, code, faq"
```

---

## Task 4: ChapterHeadingRenderer

Nahrazuje výchozí CommonMark renderer pro `<h2>`. Čte `id` atribut nastavený `AttributesExtension` (`{#sectionId}`), přejmenovává ho na `sectionId-heading`, extrahuje `NN.MM` prefix do `<span class="h-num">`.

**Files:**
- Create: `src/Content/ChapterHeadingRenderer.php`

- [ ] **Krok 1: Vytvořit `src/Content/ChapterHeadingRenderer.php`**

```php
<?php

declare(strict_types=1);

namespace App\Content;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class ChapterHeadingRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string
    {
        assert($node instanceof Heading);

        $level = $node->getLevel();
        $attrs = $node->data->get('attributes', []);
        $innerHtml = $childRenderer->renderNodes($node->children());

        if ($level !== 2 || empty($attrs['id'])) {
            $tag = "h{$level}";
            $attrStr = $this->renderAttrs(array_diff_key($attrs, ['id' => '']));
            if (!empty($attrs['id'])) {
                $attrStr = 'id="' . htmlspecialchars($attrs['id']) . '"' . ($attrStr ? ' ' . $attrStr : '');
            }
            return "<{$tag}" . ($attrStr ? " {$attrStr}" : '') . ">{$innerHtml}</{$tag}>\n";
        }

        $sectionId = $attrs['id'];
        $headingId = $sectionId . '-heading';

        // Extract NN.MM prefix (e.g. "01.01" from "01.01 Definice DDD")
        $text = $innerHtml;
        if (preg_match('/^(\d+\.\d+)\s+(.+)$/s', strip_tags($innerHtml), $m)) {
            $text = '<span class="h-num">' . $m[1] . '</span> ' . $m[2];
        }

        return '<h2 id="' . $headingId . '" class="h-section">' . $text . "</h2>\n";
    }

    private function renderAttrs(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $k => $v) {
            $parts[] = htmlspecialchars($k) . '="' . htmlspecialchars((string) $v) . '"';
        }
        return implode(' ', $parts);
    }
}
```

- [ ] **Krok 2: Commit**

```bash
git add src/Content/ChapterHeadingRenderer.php
git commit -m "feat(content): custom CommonMark heading renderer s h-num a section ID"
```

---

## Task 5: ChapterMarkdownParser

Orchestrátor celého pipeline: frontmatter extraction → block pre-processing → CommonMark render → section wrapping post-processing.

**Files:**
- Create: `src/Content/ChapterMarkdownParser.php`

- [ ] **Krok 1: Vytvořit `src/Content/ChapterMarkdownParser.php`**

```php
<?php

declare(strict_types=1);

namespace App\Content;

use App\Content\Block\CalloutRenderer;
use App\Content\Block\CodeBlockRenderer;
use App\Content\Block\DiagramRenderer;
use App\Content\Block\FaqRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Yaml\Yaml;

final class ChapterMarkdownParser
{
    private MarkdownConverter $converter;

    public function __construct(
        private readonly CalloutRenderer $callout,
        private readonly DiagramRenderer $diagram,
        private readonly CodeBlockRenderer $code,
        private readonly FaqRenderer $faq,
    ) {
        $env = new Environment();
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new AttributesExtension());
        $env->addRenderer(Heading::class, new ChapterHeadingRenderer());
        $this->converter = new MarkdownConverter($env);
    }

    public function parse(string $filePath): ParsedChapter
    {
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            throw new \RuntimeException("Cannot read: {$filePath}");
        }

        [$yamlStr, $markdown] = $this->splitFrontmatter($raw);
        $frontmatter = ChapterFrontmatter::fromArray(Yaml::parse($yamlStr));

        $html = $this->renderMarkdown($markdown);
        $html = $this->wrapSections($html);

        return new ParsedChapter($frontmatter, $html);
    }

    private function renderMarkdown(string $markdown): string
    {
        $blocks = [];

        // Pre-process :::type{attrs}\nbody\n::: blocks
        $processed = preg_replace_callback(
            '/^:::(\w+)(?:\{([^}]*)\})?\n(.*?)^:::/ms',
            function (array $matches) use (&$blocks): string {
                $idx = count($blocks);
                $blocks[] = ['type' => $matches[1], 'attrs' => $matches[2] ?? '', 'body' => $matches[3]];
                // Block-level HTML div: CommonMark won't wrap it in <p>
                return "\n\n<div data-block=\"{$idx}\"></div>\n\n";
            },
            $markdown,
        );

        $html = $this->converter->convert($processed)->getContent();

        foreach ($blocks as $idx => $block) {
            $rendered = $this->renderBlock($block);
            $html = str_replace("<div data-block=\"{$idx}\"></div>", $rendered, $html);
        }

        return $html;
    }

    private function renderBlock(array $block): string
    {
        $markdownToHtml = fn(string $md): string => $this->converter->convert($md)->getContent();

        return match ($block['type']) {
            'callout' => $this->callout->render($block['attrs'], $block['body'], $markdownToHtml),
            'diagram' => $this->diagram->render($block['attrs']),
            'code'    => $this->code->render($block['attrs'], trim($block['body'])),
            'faq'     => $this->faq->render($block['attrs'], $block['body']),
            default   => "<!-- unknown block: {$block['type']} -->",
        };
    }

    private function wrapSections(string $html): string
    {
        // Wrap each h2 with id="X-heading" in <section id="X" aria-labelledby="X-heading">
        $result = preg_replace_callback(
            '/<h2 id="([^"]+)-heading"/',
            static fn(array $m): string =>
                '</section><section id="' . $m[1] . '" aria-labelledby="' . $m[1] . '-heading">'
                . '<h2 id="' . $m[1] . '-heading"',
            $html,
        );

        // Remove spurious </section> injected before the first <section>
        $firstSection = strpos($result, '<section ');
        if ($firstSection !== false) {
            $before = substr($result, 0, $firstSection);
            $after  = substr($result, $firstSection);
            $before = str_replace('</section>', '', $before);
            $result = $before . $after;
        }

        return $result . '</section>';
    }

    private function splitFrontmatter(string $content): array
    {
        if (!str_starts_with($content, '---')) {
            return ['', $content];
        }
        $end = strpos($content, "\n---", 3);
        if ($end === false) {
            return ['', $content];
        }
        return [
            substr($content, 4, $end - 4),
            ltrim(substr($content, $end + 4)),
        ];
    }
}
```

- [ ] **Krok 2: Ověřit autowiring**

```bash
php bin/console debug:autowiring ChapterMarkdownParser
```

Očekávaný výstup: `App\Content\ChapterMarkdownParser`

- [ ] **Krok 3: Commit**

```bash
git add src/Content/ChapterMarkdownParser.php
git commit -m "feat(content): ChapterMarkdownParser – frontmatter + blocks + section wrapping"
```

---

## Task 6: ChapterController

**Files:**
- Create: `src/Controller/ChapterController.php`

- [ ] **Krok 1: Vytvořit `src/Controller/ChapterController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Content\ChapterMarkdownParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ChapterController extends AbstractController
{
    public function __construct(private readonly ChapterMarkdownParser $parser) {}

    public function show(string $_file): Response
    {
        $path = $this->getParameter('kernel.project_dir') . '/content/chapters/' . $_file;
        $chapter = $this->parser->parse($path);

        return $this->render('chapter.html.twig', [
            'chapter' => $chapter,
        ]);
    }
}
```

- [ ] **Krok 2: Commit**

```bash
git add src/Controller/ChapterController.php
git commit -m "feat(content): ChapterController"
```

---

## Task 7: chapter.html.twig

Generická šablona, která nahrazuje 35 individuálních šablon. Překrývá stejné Twig bloky jako dnešní šablony.

**Files:**
- Create: `templates/chapter.html.twig`

- [ ] **Krok 1: Vytvořit `templates/chapter.html.twig`**

```twig
{% extends 'base.html.twig' %}
{% set fm = chapter.frontmatter %}

{% block title %}{{ fm.pageTitle }}{% endblock %}
{% block meta_description %}{{ fm.metaDescription }}{% endblock %}
{% block meta_keywords %}{{ fm.metaKeywords }}{% endblock %}
{% block og_type %}{{ fm.ogType }}{% endblock %}
{% block article_published_time %}{{ fm.published }}{% endblock %}
{% block article_modified_time %}{{ fm.modified }}{% endblock %}
{% block breadcrumb_name %}{{ fm.breadcrumbName }}{% endblock %}

{% block structured_data %}
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "{{ fm.schemaType }}",
      "headline": {{ fm.schemaHeadline|json_encode|raw }},
      "description": {{ fm.metaDescription|json_encode|raw }},
      "keywords": {{ fm.metaKeywords|json_encode|raw }},
      "author": {
        "@type": "Person",
        "name": "Michal Katuščák",
        "url": "https://www.katuscak.cz/",
        "sameAs": [
          "https://blog.katuscak.cz/",
          "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"
        ]
      },
      "publisher": {
        "@type": "Person",
        "name": "Michal Katuščák"
      }
      {% if fm.published %},"datePublished": {{ fm.published|json_encode|raw }}{% endif %}
      {% if fm.modified %},"dateModified": {{ fm.modified|json_encode|raw }}{% endif %}
      ,"image": "{{ app.request.schemeAndHttpHost }}{{ asset('images/social.png') }}"
      ,"mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ app.request.schemeAndHttpHost }}{{ app.request.pathInfo }}"
      }
    }
    </script>
{% endblock %}

{% block body %}
<article class="article">
    {% include '_partials/article_head.html.twig' with {
        chapter_number: fm.chapterNumber,
        category:       fm.category,
        title:          fm.title,
        deck:           fm.deck,
        reading_time:   fm.readingTime,
        difficulty:     fm.difficulty,
        published:      fm.published,
        last_updated:   fm.modified,
        author:         'M. Katuščák'
    } %}

    {% include '_partials/article_toc.html.twig' %}

    <div class="art-body"{% if fm.chapterNumber %} data-chapter-number="{{ fm.chapterNumber }}"{% endif %}>
        {% if fm.githubExamples %}
            {% include '_partials/github_examples.html.twig' with { path: fm.githubExamples } %}
        {% endif %}

        {{ chapter.html|raw }}
    </div>

    {% include '_partials/chapter_nav.html.twig' %}
</article>
{% endblock %}
```

- [ ] **Krok 2: Commit**

```bash
git add templates/chapter.html.twig
git commit -m "feat(content): chapter.html.twig – generická šablona pro MD kapitoly"
```

---

## Task 8: ChapterRouteLoader + registrace

**Files:**
- Create: `src/Content/ChapterRouteLoader.php`
- Modify: `config/routes.yaml`

- [ ] **Krok 1: Vytvořit `src/Content/ChapterRouteLoader.php`**

`autoconfigure: true` v `services.yaml` automaticky přidá tag `routing.loader` při implementaci `LoaderInterface`.

```php
<?php

declare(strict_types=1);

namespace App\Content;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

final class ChapterRouteLoader implements LoaderInterface
{
    private bool $loaded = false;

    public function __construct(private readonly string $projectDir) {}

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \RuntimeException('ChapterRouteLoader can only be loaded once.');
        }
        $this->loaded = true;

        $collection = new RouteCollection();
        $dir = $this->projectDir . '/content/chapters';

        if (!is_dir($dir)) {
            return $collection;
        }

        foreach (glob($dir . '/*.md') as $file) {
            $raw = file_get_contents($file);
            [$yaml] = $this->splitFrontmatter($raw);
            $data = Yaml::parse($yaml);

            $route = new Route(
                path: $data['path'],
                defaults: [
                    '_controller' => 'App\Controller\ChapterController::show',
                    '_file' => basename($file),
                ],
            );
            $collection->add($data['route'], $route);
        }

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'chapter_content';
    }

    public function getResolver(): LoaderResolverInterface
    {
        throw new \LogicException('Not used.');
    }

    public function setResolver(LoaderResolverInterface $resolver): void {}

    private function splitFrontmatter(string $content): array
    {
        if (!str_starts_with($content, '---')) {
            return ['', $content];
        }
        $end = strpos($content, "\n---", 3);
        if ($end === false) {
            return ['', $content];
        }
        return [
            substr($content, 4, $end - 4),
            ltrim(substr($content, $end + 4)),
        ];
    }
}
```

- [ ] **Krok 2: Registrovat `projectDir` v services.yaml**

Přidat na konec `config/services.yaml` (před poslední prázdný řádek):

```yaml
    App\Content\ChapterRouteLoader:
        arguments:
            $projectDir: '%kernel.project_dir%'
```

- [ ] **Krok 3: Přidat entry do `config/routes.yaml`**

Přidat za stávající `controllers:` blok:

```yaml
chapters_content:
    resource: .
    type: chapter_content
```

- [ ] **Krok 4: Ověřit, že loader funguje (bez MD souborů zatím 0 rout)**

```bash
php bin/console cache:clear
php bin/console debug:router | grep -c "chapter"
```

Očekávaný výstup: `0` (žádné MD soubory ještě neexistují — je to správně)

- [ ] **Krok 5: Commit**

```bash
git add src/Content/ChapterRouteLoader.php config/routes.yaml config/services.yaml
git commit -m "feat(content): ChapterRouteLoader + registrace v routes.yaml"
```

---

## Task 9: Přesunout SVG diagramy do public/

SVG soubory jsou nyní v `templates/diagrams/` a embedovány jako inline SVG přes `{% include %}`. Pro `:::diagram{src="..."}` (které renderuje `<img src="{{ asset(...) }}">`) musí být soubory v `public/`.

**Files:**
- Move: `templates/diagrams/**/*.svg` → `public/images/diagrams/**/*.svg`

- [ ] **Krok 1: Zkopírovat SVG soubory do public/**

```bash
cp -r templates/diagrams public/images/diagrams
```

Výsledná struktura: `public/images/diagrams/2_basic_concepts/diagram.svg` atd.

- [ ] **Krok 2: Ověřit přítomnost souborů**

```bash
find public/images/diagrams -name "*.svg" | wc -l
```

Očekávaný výstup: `29`

- [ ] **Krok 3: Commit**

```bash
git add public/images/diagrams/
git commit -m "feat(content): SVG diagramy přesunuty do public/images/diagrams"
```

---

## Task 10: Pilot – what_is_ddd.md

Migruje první kapitolu. Ověřuje celý pipeline end-to-end před hromadnou migrací.

**Files:**
- Create: `content/chapters/what_is_ddd.md`
- Modify: `src/Controller/DddController.php` (smazat action `whatIsDdd`)

- [ ] **Krok 1: Vytvořit adresář**

```bash
mkdir -p content/chapters
```

- [ ] **Krok 2: Vytvořit `content/chapters/what_is_ddd.md`**

Přečíst `templates/ddd/what_is_ddd.html.twig` a převést do MD formátu (viz **Konverzní návod** v Task 10).

Frontmatter pochází z Twig bloků šablony:
- `{% block title %}` → `page_title:`
- `{% block meta_description %}` → `meta_description:`
- `{% block meta_keywords %}` → `meta_keywords:`
- `{% block og_type %}` → `og_type:`
- `{% block article_published_time %}` → `published:`
- `{% block article_modified_time %}` → `modified:`
- `{% block breadcrumb_name %}` → `breadcrumb_name:`
- JSON-LD `"@type"` → `schema_type:`, `"headline"` → `schema_headline:`
- `article_head` parametry → `chapter_number:`, `category:`, `deck:`, `reading_time:`, `difficulty:`
- `github_examples` path → `github_examples:`

Příklad frontmatter pro kapitolu 01:

```yaml
---
route: what_is_ddd
path: /co-je-ddd
page_title: "Co je Domain-Driven Design? Vysvětlení DDD | DDD Symfony"
title: Co je Domain-Driven Design?
meta_description: "Domain-Driven Design srozumitelně: filozofie Erica Evanse, Ubiquitous Language, Bounded Context a rozdíl mezi strategickým a taktickým designem."
meta_keywords: "Domain-Driven Design, DDD, Eric Evans, Ubiquitous Language, Bounded Context, doménový model, doménová logika, strategický design, taktický design"
og_type: article
published: "2025-04-24"
modified: "2026-04-28"
breadcrumb_name: Co je DDD
schema_type: TechArticle
schema_headline: "Co je Domain-Driven Design? Podrobné vysvětlení DDD"

chapter_number: "01"
category: Základy
deck: "Domain-Driven Design (DDD), jeho základní principy a způsob, jakým pomáhá řešit složité domény a zlepšuje komunikaci mezi vývojáři a doménovými experty."
reading_time: 12
difficulty: 1
github_examples: Chapter01_WhatIsDDD
---
```

- [ ] **Krok 3: Ověřit registraci routy**

```bash
php bin/console cache:clear
php bin/console debug:router what_is_ddd
```

Očekávaný výstup: řádek s `what_is_ddd | GET | /co-je-ddd`

- [ ] **Krok 4: Ověřit stránku v prohlížeči**

Spustit `symfony server:start` (pokud neběží) a otevřít `http://127.0.0.1:8000/co-je-ddd`.

Zkontrolovat:
- Vizuální výstup identický s původní šablonou
- HTML source: JSON-LD přítomen, breadcrumb JSON-LD přítomen
- Nadpisy mají `<section id="..." aria-labelledby="...">` wrapping
- Callout bloky renderují správně
- Code bloky mají filename header + copy button
- Diagramy se zobrazují (zoom toolbar přítomen)
- `chapter_nav` zobrazuje Předchozí / Další

- [ ] **Krok 5: Smazat starý action z DddController.php**

Smazat metodu `whatIsDdd()` (řádky 93–98 v původním souboru):

```php
// SMAZAT:
#[Route('/co-je-ddd', name: 'what_is_ddd')]
public function whatIsDdd(): Response
{
    return $this->render('ddd/what_is_ddd.html.twig', [
        'title' => 'Co je Domain-Driven Design?',
    ]);
}
```

- [ ] **Krok 6: Ověřit, že stránka stále funguje po smazání action**

```bash
php bin/console cache:clear
```

Znovu otevřít `http://127.0.0.1:8000/co-je-ddd` — musí fungovat přes RouteLoader.

- [ ] **Krok 7: Commit**

```bash
git add content/chapters/what_is_ddd.md src/Controller/DddController.php
git commit -m "feat(content): migrace kapitoly 01 – what_is_ddd do MD"
```

---

## Task 11: Konverzní návod + migrace zbývajících 34 kapitol

### Konverzní návod (použít pro každou kapitolu)

**Frontmatter** se extrahuje z Twig bloků (viz Task 9, Krok 2).

**Twig → Markdown syntaxe:**

| Twig | Markdown |
|------|----------|
| `<section id="X" aria-labelledby="X-heading"><h2 id="X-heading" class="h-section"><span class="h-num">NN.MM</span> Text</h2>` | `## NN.MM Text {#X}` |
| `<h3 id="..." ...>Text</h3>` | `### Text {#id}` nebo `### Text` |
| `{% include '_partials/callout.html.twig' with {type: 'note', body: ...} %}` | `:::callout{type="note"}\nMarkdown obsah\n:::` |
| `{% include '_partials/diagram.html.twig' with {fig: '...', title: '...', src: '...'} %}` | `:::diagram{fig="..." title="..." src="..."}` |
| `{% include '_partials/code_block.html.twig' with {filename: '...', language: '...', code: _code} %}` | `:::code{filename="..." language="..."}\nkód\n:::` |
| `{% include '_partials/faq.html.twig' with {items: [...]} %}` | `:::faq\n- question: ...\n  answer: ...\n:::` |
| `<p>Text</p>` | `Text` (odstavec) |
| `<ul><li>...</li></ul>` | `- ...` |
| `<ol><li>...</li></ol>` | `1. ...` |
| `<strong>...</strong>` | `**...**` |
| `<em>...</em>` | `_..._` |
| `<code>...</code>` | `` `...` `` |
| `<a href="..." target="_blank">text</a>` | `[text](url)` |

**Inline SVG diagramy** (`{% set _d %}{% include 'diagrams/N_topic/file.svg' %}{% endset %}` s `inline:`) → převést na `:::diagram{... src="images/diagrams/N_topic/file.svg"}`. SVG soubory jsou v `public/images/diagrams/` po Task 9.

**Callout s Twig set blokem:**
```twig
{% set _callout_body %}
    <h3>...</h3>
    <ul>...</ul>
{% endset %}
{% include '_partials/callout.html.twig' with {type: 'note', body: _callout_body} %}
```
→
```markdown
:::callout{type="note"}
### Nadpis callout

- položka 1
- položka 2
:::
```

### Pořadí migrace

Migrovat po skupinách, každá skupina = jeden commit. Pro každou kapitolu:
1. Přečíst `.html.twig` soubor
2. Vytvořit `content/chapters/{route_name}.md` podle konverzního návodu
3. `php bin/console cache:clear`
4. Ověřit stránku v prohlížeči (vizuální identita, JSON-LD, navigace)
5. Smazat odpovídající action z `DddController.php`
6. Ověřit znovu
7. Commitnout

**Skupina A — Základy (kapitoly 02–05):**

| MD soubor | Route | Path |
|-----------|-------|------|
| `subdomains.md` | `subdomains` | `/subdomeny` |
| `context_mapping.md` | `context_mapping` | `/context-mapping` |
| `event_storming.md` | `event_storming` | `/event-storming` |
| `team_topologies.md` | `team_topologies` | `/team-topologies` |

```bash
git commit -m "feat(content): migrace skupiny A – Základy (02–05)"
```

**Skupina B — Taktika (kapitoly 06–08):**

| MD soubor | Route | Path |
|-----------|-------|------|
| `basic_concepts.md` | `basic_concepts` | `/zakladni-koncepty` |
| `aggregate_design.md` | `aggregate_design` | `/navrh-agregatu` |
| `lesser_known_patterns.md` | `lesser_known_patterns` | `/mene-zname-vzory` |

```bash
git commit -m "feat(content): migrace skupiny B – Taktika (06–08)"
```

**Skupina C — Architektura (kapitoly 09–12):**

| MD soubor | Route | Path |
|-----------|-------|------|
| `architectural_styles.md` | `architectural_styles` | `/architektonicke-styly` |
| `horizontal_vs_vertical.md` | `horizontal_vs_vertical` | `/vertikalni-slice` |
| `implementation_in_symfony.md` | `implementation_in_symfony` | `/implementace-v-symfony` |
| `authorization_in_ddd.md` | `authorization_in_ddd` | `/autorizace-v-ddd` |

```bash
git commit -m "feat(content): migrace skupiny C – Architektura (09–12)"
```

**Skupina D — Vzory (kapitoly 13–17):**

| MD soubor | Route | Path |
|-----------|-------|------|
| `cqrs.md` | `cqrs` | `/cqrs` |
| `event_sourcing.md` | `event_sourcing` | `/event-sourcing` |
| `sagas.md` | `sagas` | `/sagy-a-process-managery` |
| `outbox_pattern.md` | `outbox_pattern` | `/outbox-pattern` |
| `performance_aspects.md` | `performance_aspects` | `/vykonnostni-aspekty` |

```bash
git commit -m "feat(content): migrace skupiny D – Vzory (13–17)"
```

**Skupina E — Praxe (kapitoly 18–23):**

| MD soubor | Route | Path |
|-----------|-------|------|
| `testing_ddd.md` | `testing_ddd` | `/testovani-ddd` |
| `migration_from_crud.md` | `migration_from_crud` | `/migrace-z-crud` |
| `microservices_and_ddd.md` | `microservices_and_ddd` | `/ddd-a-microservices` |
| `ddd_pain_points.md` | `ddd_pain_points` | `/ddd-v-praxi-kde-to-boli` |
| `anti_patterns.md` | `anti_patterns` | `/anti-vzory` |
| `when_not_to_use_ddd.md` | `when_not_to_use_ddd` | `/kdy-nepouzivat-ddd` |

```bash
git commit -m "feat(content): migrace skupiny E – Praxe (18–23)"
```

**Skupina F — Syntéza + Reference (kapitoly 24–25 + extra):**

| MD soubor | Route | Path |
|-----------|-------|------|
| `practical_examples.md` | `practical_examples` | `/prakticke-priklady` |
| `case_study.md` | `case_study` | `/pripadova-studie` |
| `ddd_ai.md` | `ddd_ai` | `/ddd-a-umela-inteligence` |

```bash
git commit -m "feat(content): migrace skupiny F – Syntéza + ddd_ai (24–25)"
```

---

## Task 12: Závěrečné ověření a cleanup

- [ ] **Krok 1: Ověřit všechny routy**

```bash
php bin/console debug:router | grep -E "(what_is_ddd|subdomains|context_mapping|event_storming|team_topologies|basic_concepts|aggregate_design|lesser_known_patterns|architectural_styles|horizontal_vs_vertical|implementation_in_symfony|authorization_in_ddd|cqrs|event_sourcing|sagas|outbox_pattern|performance_aspects|testing_ddd|migration_from_crud|microservices_and_ddd|ddd_pain_points|anti_patterns|when_not_to_use_ddd|practical_examples|case_study|ddd_ai)"
```

Očekávaný výstup: 28 řádků (25 kapitol + 3 extra)

- [ ] **Krok 2: Ověřit, že v DddController nezůstaly osiřelé actions**

```bash
grep -n "Route(" src/Controller/DddController.php
```

Očekávaný výstup: pouze hub stránky (`hub_basics`, `hub_tactics`, `hub_architecture`, `hub_patterns`, `hub_practice`, `hub_synthesis`, `hub_reference`), `homepage`, `about`, speciální stránky (`security_policy`, `cheat_sheet`, `glossary`, `resources`) a redirecty.

- [ ] **Krok 3: Ověřit, že twig šablony migrovaných kapitol jsou nepoužívané**

```bash
ls templates/ddd/ | grep -v -E "(hub_|index|about|security_policy|cheat_sheet|glossary|resources)"
```

Očekávaný výstup: prázdný (všechny obsahové šablony smazány)

- [ ] **Krok 4: Smazat nepoužívané Twig šablony**

```bash
# Smazat šablony odpovídající migrovaným kapitolám
rm templates/ddd/what_is_ddd.html.twig
rm templates/ddd/subdomains.html.twig
rm templates/ddd/context_mapping.html.twig
# ... (všechny migrované)
```

- [ ] **Krok 5: Commit**

```bash
git add -A
git commit -m "refactor(content): smazání Twig šablon nahrazených MD soubory"
```

- [ ] **Krok 6: Finální smoke test**

Projít v prohlížeči náhodně vybrané kapitoly z každé skupiny (A–F) a ověřit:
- Vizuální výstup identický s původem
- JSON-LD v page source (TechArticle + BreadcrumbList)
- Navigace prev/next funguje
- TOC se plní správně
- Diagramy mají zoom/fullscreen toolbar
- Code bloky mají copy button
- FAQ renderuje jako accordion

- [ ] **Krok 7: Závěrečný commit**

```bash
git commit -m "feat(content): kompletní migrace 35 kapitol do Markdown"
```
