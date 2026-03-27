# Theme Engine Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace PHP-based theme rendering with a sandboxed `.tpl` template engine, eliminate symlinks in favor of file copies, standardize widget output as framework-agnostic, and bring all 8 themes to full i18n support.

**Architecture:** A custom runtime template interpreter (`TemplateEngine`) replaces `extract() + include`. `ThemeService` becomes the sole owner of the rendering data bag — controllers pass only page-specific data. Theme/widget assets are copied to `FCPATH` instead of symlinked. All theme views become `.tpl` files; PHP is prohibited in themes entirely.

**Tech Stack:** PHP 8.2+, CodeIgniter 4.7, CI4 Cache library, PHPUnit (existing test suite)

---

## IMPLEMENTATION RULES — READ BEFORE EVERY TASK

These rules are non-negotiable. Do not deviate.

1. **Never write code beyond what the task specifies.** No refactoring adjacent code, no "improvements," no extra features.
2. **Never make judgment calls.** If the task doesn't specify something, ask — do not decide.
3. **All user-visible strings in .tpl files must use `{% lang %}` tags.** No hardcoded English strings.
4. **Widget .tpl files use CSS class variables with semantic defaults.** Use `{{ cls_list | default('widget-list') }}` pattern — themes can inject their own CSS classes, semantic defaults apply when they don't. The standard variable names and defaults are defined in Phase 4.
5. **Theme .tpl files CAN use framework-specific classes** (Bootstrap, Bootswatch, etc.) — themes choose their own framework and can pass their classes to widgets.
6. **Zero PHP in themes.** No `<?`, `<%`, or any PHP tags in any file inside `themes/*/`. The only exception was `theme_info.php` which is now `theme_info.json`.
7. **CI4 conventions.** Follow existing CodeIgniter 4 patterns. Use `app/Libraries/` for new classes. Use CI4's cache, not custom caching.
8. **Do not run PHPUnit** unless explicitly asked. Write the tests but do not execute them.
9. **Do not commit** unless explicitly asked.
10. **Spec:** `docs/superpowers/specs/2026-03-26-theme-engine-redesign.md` — refer to it for design decisions.

---

## File Structure

### New Files

```
app/Libraries/TemplateEngine/
    Engine.php              — Public API: render(templatePath, data)
    Lexer.php               — Splits template into segments (text, output, tag, comment)
    Parser.php              — Builds AST from segments, tokenizes expressions
    Interpreter.php         — Walks AST, evaluates against data context
    Nodes.php               — All AST node classes
    FilterRegistry.php      — Whitelisted filter implementations
    TagRegistry.php         — Whitelisted tag function implementations

app/Services/WidgetDataService.php  — Whitelisted data provider registry

widgets/Paywall/
    widget_info.json        — Widget manifest (JSON only, no PHP)
    views/widget.tpl        — Paywall CTA markup (semantic classes)

tests/unit/TemplateEngine/
    LexerTest.php
    ParserTest.php
    InterpreterTest.php
    EngineTest.php
    FilterTest.php

tests/integration/TemplateEngine/
    TagRegistryTest.php

app/Database/Migrations/xxxx_AddPageCacheTtlSetting.php — Page cache TTL setting

WidgetBuilder.md                — New: how to build widgets (repo root, alongside ThemeBuilder.md)
PluginBuilder.md                — New: plugin development stub guide (repo root)
```

### Modified Files

```
app/Services/ThemeService.php       — Major rewrite: owns data bag, uses engine, publishAssets()
app/Services/MediaService.php       — Upload path → FCPATH, stored path format
app/Services/BackupService.php      — Backup path → FCPATH
app/Services/MarketplaceService.php — publishAssets() instead of symlinkAssets()
app/Services/WidgetService.php      — Full rewrite: renderArea() owns flow, renderAdminForm() auto-generates from JSON, discover() reads JSON
app/Libraries/BaseWidget.php        — Deleted in Phase 4 (transitional engine integration in Phase 3)
app/Helpers/cms_helper.php          — Remove theme_view(), theme_layout(); keep theme_url() etc.
app/Controllers/BaseController.php  — Strip common data building (moves to ThemeService)
app/Controllers/Blog.php            — Thin: pass only page data
app/Controllers/Pages.php           — Thin: pass only page data
app/Controllers/Contact.php         — Thin: pass only page data
app/Controllers/Search.php          — Thin: pass only page data
app/Views/admin/themes/index.php    — Show screenshot + validation warning
app/Views/admin/media/index.php     — Drop writable/ prefix
app/Views/admin/users/profile.php   — Drop writable/ prefix
app/Views/admin/backup/index.php    — Update documentation text
app/Views/admin/settings/index.php  — Add page cache TTL field to General tab
app/Controllers/Admin/Themes.php    — Pass validation data, enforce validation on activate
app/Controllers/Admin/Settings.php  — Save pageCacheTtl setting
app/Database/Seeds/SettingsSeeder.php — Seed pageCacheTtl default

themes/default/theme_info.json      — Was .php
themes/ember/theme_info.json        — Was .php
themes/cyborg/theme_info.json       — Was .php
themes/darkly/theme_info.json       — Was .php
themes/flatly/theme_info.json       — Was .php
themes/lux/theme_info.json          — Was .php
themes/sandstone/theme_info.json    — Was .php
themes/slate/theme_info.json        — Was .php

All themes/*/views/*.php            — Become .tpl files
All widgets/*/                       — Renamed to PascalCase, PHP classes + admin_form.php deleted
All widgets/*/widget_info.php       — Become .json (new admin/output format)
All widgets/*/views/widget.php      — Become .tpl files

ThemeBuilder.md                     — Full rewrite
README.md                          — Steps 9-10 rewritten
```

---

## Phase 1: Template Engine

### Task 1: Lexer

**Files:**
- Create: `app/Libraries/TemplateEngine/Lexer.php`
- Create: `tests/unit/TemplateEngine/LexerTest.php`

- [ ] **Step 1: Write the Lexer test**

```php
<?php

namespace Tests\Unit\TemplateEngine;

use App\Libraries\TemplateEngine\Lexer;
use CodeIgniter\Test\CIUnitTestCase;

class LexerTest extends CIUnitTestCase
{
    private Lexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lexer = new Lexer();
    }

    public function testPlainText(): void
    {
        $result = $this->lexer->tokenize('Hello world');
        $this->assertCount(1, $result);
        $this->assertSame('text', $result[0]['type']);
        $this->assertSame('Hello world', $result[0]['content']);
    }

    public function testOutputTag(): void
    {
        $result = $this->lexer->tokenize('Hello {{ name }}!');
        $this->assertCount(3, $result);
        $this->assertSame('text', $result[0]['type']);
        $this->assertSame('output', $result[1]['type']);
        $this->assertSame('name', trim($result[1]['content']));
        $this->assertSame('text', $result[2]['type']);
    }

    public function testRawOutputTag(): void
    {
        $result = $this->lexer->tokenize('{! raw_html !}');
        $this->assertCount(1, $result);
        $this->assertSame('raw_output', $result[0]['type']);
        $this->assertSame('raw_html', trim($result[0]['content']));
    }

    public function testLogicTag(): void
    {
        $result = $this->lexer->tokenize('{% if active %}yes{% endif %}');
        $this->assertCount(3, $result);
        $this->assertSame('tag', $result[0]['type']);
        $this->assertSame('if active', trim($result[0]['content']));
        $this->assertSame('text', $result[1]['type']);
        $this->assertSame('tag', $result[2]['type']);
        $this->assertSame('endif', trim($result[2]['content']));
    }

    public function testCommentIsStripped(): void
    {
        $result = $this->lexer->tokenize('before{# comment #}after');
        $this->assertCount(2, $result);
        $this->assertSame('before', $result[0]['content']);
        $this->assertSame('after', $result[1]['content']);
    }

    public function testMixedContent(): void
    {
        $tpl = '<h1>{{ title }}</h1>{% if show %}{! content !}{% endif %}';
        $result = $this->lexer->tokenize($tpl);
        $this->assertSame('text', $result[0]['type']);     // <h1>
        $this->assertSame('output', $result[1]['type']);    // title
        $this->assertSame('text', $result[2]['type']);      // </h1>
        $this->assertSame('tag', $result[3]['type']);       // if show
        $this->assertSame('raw_output', $result[4]['type']); // content
        $this->assertSame('tag', $result[5]['type']);       // endif
    }
}
```

- [ ] **Step 2: Write the Lexer**

```php
<?php

namespace App\Libraries\TemplateEngine;

class Lexer
{
    /**
     * Tokenize a template string into segments.
     *
     * Each segment is an array with keys:
     *   'type'    => 'text' | 'output' | 'raw_output' | 'tag'
     *   'content' => string (inner content, delimiters stripped)
     *
     * Comments ({# ... #}) are discarded entirely.
     *
     * @return array<array{type: string, content: string}>
     */
    public function tokenize(string $template): array
    {
        // Match all template tags: {{ }}, {! !}, {% %}, {# #}
        $pattern = '/(\{\{.*?\}\}|\{!.*?!\}|\{%.*?%\}|\{#.*?#\})/s';
        $parts = preg_split($pattern, $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $segments = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, '{{') && str_ends_with($part, '}}')) {
                $segments[] = [
                    'type'    => 'output',
                    'content' => substr($part, 2, -2),
                ];
            } elseif (str_starts_with($part, '{!') && str_ends_with($part, '!}')) {
                $segments[] = [
                    'type'    => 'raw_output',
                    'content' => substr($part, 2, -2),
                ];
            } elseif (str_starts_with($part, '{%') && str_ends_with($part, '%}')) {
                $segments[] = [
                    'type'    => 'tag',
                    'content' => substr($part, 2, -2),
                ];
            } elseif (str_starts_with($part, '{#') && str_ends_with($part, '#}')) {
                // Comment — discard
                continue;
            } else {
                $segments[] = [
                    'type'    => 'text',
                    'content' => $part,
                ];
            }
        }

        return $segments;
    }
}
```


---

### Task 2: AST Node Classes

**Files:**
- Create: `app/Libraries/TemplateEngine/Nodes.php`

- [ ] **Step 1: Write all node classes**

```php
<?php

namespace App\Libraries\TemplateEngine;

/** Base class for all AST nodes. */
abstract class Node {}

/** Literal text content — output as-is. */
class TextNode extends Node
{
    public function __construct(public readonly string $text) {}
}

/** {{ expression }} — auto-escaped output. */
class OutputNode extends Node
{
    public function __construct(public readonly Expression $expression) {}
}

/** {! expression !} — raw (unescaped) output. */
class RawOutputNode extends Node
{
    public function __construct(public readonly Expression $expression) {}
}

/** {% if %}...{% elseif %}...{% else %}...{% endif %} */
class IfNode extends Node
{
    /**
     * @param array<array{condition: Expression, body: Node[]}> $branches
     * @param Node[]|null $elseBody
     */
    public function __construct(
        public readonly array $branches,
        public readonly ?array $elseBody = null,
    ) {}
}

/** {% for item in collection %}...{% endfor %} */
class ForNode extends Node
{
    /** @param Node[] $body */
    public function __construct(
        public readonly string $variableName,
        public readonly Expression $iterable,
        public readonly array $body,
    ) {}
}

/** {% block name %}...{% endblock %} */
class BlockNode extends Node
{
    /** @param Node[] $body */
    public function __construct(
        public readonly string $name,
        public readonly array $body,
    ) {}
}

/** {% extends 'layout' %} */
class ExtendsNode extends Node
{
    public function __construct(public readonly string $templateName) {}
}

/** {% include 'partial' with {key: value} %} */
class IncludeNode extends Node
{
    /** @param array<string, Expression>|null $withData */
    public function __construct(
        public readonly string $templateName,
        public readonly ?array $withData = null,
    ) {}
}

/** {% tag_name arg1 arg2 %} — whitelisted tag function call. */
class TagFunctionNode extends Node
{
    /** @param Expression[] $arguments */
    public function __construct(
        public readonly string $name,
        public readonly array $arguments,
    ) {}
}

// ─── Expression types ────────────────────────────────────────

/** Base class for expressions (used inside {{ }}, {% %}, conditions, etc.) */
abstract class Expression {}

/** Variable reference, possibly dotted: post.title */
class VariableExpression extends Expression
{
    /** @param string[] $parts e.g. ['post', 'title'] */
    public function __construct(public readonly array $parts) {}
}

/** String, number, bool, or null literal. */
class LiteralExpression extends Expression
{
    public function __construct(public readonly mixed $value) {}
}

/** left == right, left > right, etc. */
class ComparisonExpression extends Expression
{
    public function __construct(
        public readonly Expression $left,
        public readonly string $operator,
        public readonly Expression $right,
    ) {}
}

/** left and right, left or right */
class BooleanExpression extends Expression
{
    public function __construct(
        public readonly Expression $left,
        public readonly string $operator,
        public readonly Expression $right,
    ) {}
}

/** not expression */
class NotExpression extends Expression
{
    public function __construct(public readonly Expression $expression) {}
}

/** expression | filterName(arg1, arg2) */
class FilterExpression extends Expression
{
    /** @param Expression[] $arguments */
    public function __construct(
        public readonly Expression $expression,
        public readonly string $filterName,
        public readonly array $arguments = [],
    ) {}
}
```


---

### Task 3: Parser

**Files:**
- Create: `app/Libraries/TemplateEngine/Parser.php`
- Create: `tests/unit/TemplateEngine/ParserTest.php`

- [ ] **Step 1: Write Parser tests**

```php
<?php

namespace Tests\Unit\TemplateEngine;

use App\Libraries\TemplateEngine\{
    Lexer, Parser, TextNode, OutputNode, RawOutputNode,
    IfNode, ForNode, BlockNode, ExtendsNode, IncludeNode,
    TagFunctionNode, VariableExpression, LiteralExpression,
    ComparisonExpression, FilterExpression, NotExpression
};
use CodeIgniter\Test\CIUnitTestCase;

class ParserTest extends CIUnitTestCase
{
    private function parse(string $template): array
    {
        $lexer = new Lexer();
        $parser = new Parser();
        return $parser->parse($lexer->tokenize($template));
    }

    public function testTextOnly(): void
    {
        $nodes = $this->parse('Hello');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(TextNode::class, $nodes[0]);
    }

    public function testSimpleOutput(): void
    {
        $nodes = $this->parse('{{ name }}');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(OutputNode::class, $nodes[0]);
        $this->assertInstanceOf(VariableExpression::class, $nodes[0]->expression);
    }

    public function testDottedVariable(): void
    {
        $nodes = $this->parse('{{ post.title }}');
        $expr = $nodes[0]->expression;
        $this->assertInstanceOf(VariableExpression::class, $expr);
        $this->assertSame(['post', 'title'], $expr->parts);
    }

    public function testFilterChain(): void
    {
        $nodes = $this->parse("{{ name | strtolower | default('anon') }}");
        $expr = $nodes[0]->expression;
        // Outermost is the last filter: default
        $this->assertInstanceOf(FilterExpression::class, $expr);
        $this->assertSame('default', $expr->filterName);
        // Inner is strtolower
        $this->assertInstanceOf(FilterExpression::class, $expr->expression);
        $this->assertSame('strtolower', $expr->expression->filterName);
    }

    public function testRawOutput(): void
    {
        $nodes = $this->parse('{! html !}');
        $this->assertInstanceOf(RawOutputNode::class, $nodes[0]);
    }

    public function testIfElseEndif(): void
    {
        $nodes = $this->parse('{% if active %}yes{% else %}no{% endif %}');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(IfNode::class, $nodes[0]);
        $this->assertCount(1, $nodes[0]->branches);
        $this->assertNotNull($nodes[0]->elseBody);
    }

    public function testForLoop(): void
    {
        $nodes = $this->parse('{% for post in posts %}{{ post.title }}{% endfor %}');
        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ForNode::class, $nodes[0]);
        $this->assertSame('post', $nodes[0]->variableName);
    }

    public function testExtends(): void
    {
        $nodes = $this->parse("{% extends 'layout' %}{% block content %}hi{% endblock %}");
        $this->assertInstanceOf(ExtendsNode::class, $nodes[0]);
        $this->assertSame('layout', $nodes[0]->templateName);
        $this->assertInstanceOf(BlockNode::class, $nodes[1]);
    }

    public function testIncludeWithData(): void
    {
        $nodes = $this->parse("{% include 'partials/card' with {post: post} %}");
        $this->assertInstanceOf(IncludeNode::class, $nodes[0]);
        $this->assertSame('partials/card', $nodes[0]->templateName);
        $this->assertArrayHasKey('post', $nodes[0]->withData);
    }

    public function testTagFunction(): void
    {
        $nodes = $this->parse("{% lang 'Blog.readMore' %}");
        $this->assertInstanceOf(TagFunctionNode::class, $nodes[0]);
        $this->assertSame('lang', $nodes[0]->name);
    }

    public function testComparison(): void
    {
        $nodes = $this->parse('{% if count > 0 %}yes{% endif %}');
        $ifNode = $nodes[0];
        $this->assertInstanceOf(ComparisonExpression::class, $ifNode->branches[0]['condition']);
    }

    public function testNotExpression(): void
    {
        $nodes = $this->parse('{% if not active %}hidden{% endif %}');
        $condition = $nodes[0]->branches[0]['condition'];
        $this->assertInstanceOf(NotExpression::class, $condition);
    }

    public function testTagFunctionWithFilteredArgument(): void
    {
        $nodes = $this->parse("{% lang 'Blog.views' post.views|number_format %}");
        $this->assertInstanceOf(TagFunctionNode::class, $nodes[0]);
        $this->assertSame('lang', $nodes[0]->name);
        $this->assertCount(2, $nodes[0]->arguments);
        $this->assertInstanceOf(LiteralExpression::class, $nodes[0]->arguments[0]);
        $this->assertInstanceOf(FilterExpression::class, $nodes[0]->arguments[1]);
        $this->assertSame('number_format', $nodes[0]->arguments[1]->filterName);
    }
}
```

- [ ] **Step 2: Write the Parser**

The Parser is the most complex component. It has two responsibilities:
1. Parse the segment stream from the Lexer into an AST of Node objects
2. Tokenize and parse expressions within output and tag segments

```php
<?php

namespace App\Libraries\TemplateEngine;

class Parser
{
    private array $segments;
    private int $pos;

    /** Tag function names recognized by the engine. */
    private const TAG_FUNCTIONS = [
        'lang', 'theme_url', 'base_url', 'widget_area',
        'post_url', 'category_url', 'tag_url', 'render_content',
    ];

    /**
     * Parse Lexer segments into an AST.
     *
     * @param array<array{type: string, content: string}> $segments
     * @return Node[]
     */
    public function parse(array $segments): array
    {
        $this->segments = $segments;
        $this->pos = 0;
        return $this->parseNodes();
    }

    /** Parse nodes until we hit a stop tag or end of segments. */
    private function parseNodes(array $stopTags = []): array
    {
        $nodes = [];

        while ($this->pos < count($this->segments)) {
            $seg = $this->segments[$this->pos];

            if ($seg['type'] === 'text') {
                $nodes[] = new TextNode($seg['content']);
                $this->pos++;
                continue;
            }

            if ($seg['type'] === 'output') {
                $expr = $this->parseExpression(trim($seg['content']));
                $nodes[] = new OutputNode($expr);
                $this->pos++;
                continue;
            }

            if ($seg['type'] === 'raw_output') {
                $expr = $this->parseExpression(trim($seg['content']));
                $nodes[] = new RawOutputNode($expr);
                $this->pos++;
                continue;
            }

            if ($seg['type'] === 'tag') {
                $tagContent = trim($seg['content']);

                // Check if this is a stop tag
                foreach ($stopTags as $stop) {
                    if ($tagContent === $stop || str_starts_with($tagContent, $stop . ' ')) {
                        return $nodes; // Don't advance — caller handles it
                    }
                }

                $node = $this->parseTag($tagContent);
                if ($node !== null) {
                    $nodes[] = $node;
                }
                continue;
            }

            $this->pos++;
        }

        return $nodes;
    }

    /** Parse a {% ... %} tag. */
    private function parseTag(string $content): ?Node
    {
        $parts = preg_split('/\s+/', $content, 2);
        $keyword = $parts[0];
        $rest = $parts[1] ?? '';

        return match ($keyword) {
            'if'      => $this->parseIf($rest),
            'for'     => $this->parseFor($rest),
            'block'   => $this->parseBlock($rest),
            'extends' => $this->parseExtends($rest),
            'include' => $this->parseInclude($rest),
            default   => $this->parseTagFunctionOrSkip($keyword, $rest),
        };
    }

    private function parseIf(string $conditionStr): IfNode
    {
        $this->pos++; // advance past the {% if %} segment
        $condition = $this->parseExpression($conditionStr);
        $body = $this->parseNodes(['elseif', 'else', 'endif']);

        $branches = [['condition' => $condition, 'body' => $body]];
        $elseBody = null;

        while ($this->pos < count($this->segments)) {
            $tag = trim($this->segments[$this->pos]['content'] ?? '');

            if ($tag === 'endif') {
                $this->pos++;
                break;
            }

            if (str_starts_with($tag, 'elseif ')) {
                $this->pos++;
                $elseifCond = $this->parseExpression(trim(substr($tag, 7)));
                $elseifBody = $this->parseNodes(['elseif', 'else', 'endif']);
                $branches[] = ['condition' => $elseifCond, 'body' => $elseifBody];
                continue;
            }

            if ($tag === 'else') {
                $this->pos++;
                $elseBody = $this->parseNodes(['endif']);
                // Consume endif
                if ($this->pos < count($this->segments)) {
                    $this->pos++;
                }
                break;
            }

            $this->pos++;
        }

        return new IfNode($branches, $elseBody);
    }

    private function parseFor(string $rest): ForNode
    {
        $this->pos++;
        // Expected format: "item in collection"
        if (! preg_match('/^(\w+)\s+in\s+(.+)$/', $rest, $m)) {
            return new ForNode('_item', new LiteralExpression([]), []);
        }
        $varName = $m[1];
        $iterable = $this->parseExpression(trim($m[2]));
        $body = $this->parseNodes(['endfor']);

        // Consume endfor
        if ($this->pos < count($this->segments)) {
            $this->pos++;
        }

        return new ForNode($varName, $iterable, $body);
    }

    private function parseBlock(string $name): BlockNode
    {
        $this->pos++;
        $body = $this->parseNodes(['endblock']);

        if ($this->pos < count($this->segments)) {
            $this->pos++;
        }

        return new BlockNode(trim($name), $body);
    }

    private function parseExtends(string $rest): ExtendsNode
    {
        $this->pos++;
        $name = trim($rest, " \t\n\r\0\x0B'\"");
        return new ExtendsNode($name);
    }

    private function parseInclude(string $rest): IncludeNode
    {
        $this->pos++;
        $withData = null;

        // Split on " with " to get template name and data
        if (preg_match("/^(['\"].*?['\"])\s+with\s+\{(.*)\}$/s", $rest, $m)) {
            $templateName = trim($m[1], "'\"");
            $withData = $this->parseWithClause($m[2]);
        } else {
            $templateName = trim($rest, " '\"");
        }

        return new IncludeNode($templateName, $withData);
    }

    /** Parse the key: value pairs inside a with { } clause. */
    private function parseWithClause(string $content): array
    {
        $data = [];
        $pairs = preg_split('/\s*,\s*/', trim($content));

        foreach ($pairs as $pair) {
            if (preg_match('/^(\w+)\s*:\s*(.+)$/', trim($pair), $m)) {
                $data[$m[1]] = $this->parseExpression(trim($m[2]));
            }
        }

        return $data;
    }

    private function parseTagFunctionOrSkip(string $name, string $argsStr): ?Node
    {
        if (! in_array($name, self::TAG_FUNCTIONS, true)) {
            $this->pos++;
            return null; // Unknown tag — silently skip
        }

        $this->pos++;
        $arguments = [];

        if ($argsStr !== '') {
            // Split arguments by unquoted spaces
            $argTokens = $this->splitTagArguments($argsStr);
            foreach ($argTokens as $argToken) {
                $arguments[] = $this->parseExpression($argToken);
            }
        }

        return new TagFunctionNode($name, $arguments);
    }

    /**
     * Split tag function arguments by spaces, respecting quoted strings.
     * e.g. "'Blog.views' post.views|number_format" → ["'Blog.views'", "post.views|number_format"]
     */
    private function splitTagArguments(string $str): array
    {
        $args = [];
        $current = '';
        $inQuote = null;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $ch = $str[$i];

            if ($inQuote !== null) {
                $current .= $ch;
                if ($ch === $inQuote) {
                    $inQuote = null;
                }
                continue;
            }

            if ($ch === "'" || $ch === '"') {
                $inQuote = $ch;
                $current .= $ch;
                continue;
            }

            if ($ch === ' ' || $ch === "\t") {
                if ($current !== '') {
                    $args[] = $current;
                    $current = '';
                }
                continue;
            }

            $current .= $ch;
        }

        if ($current !== '') {
            $args[] = $current;
        }

        return $args;
    }

    // ─── Expression Parser ───────────────────────────────────

    /** Entry point for parsing an expression string. */
    public function parseExpression(string $expr): Expression
    {
        $tokens = $this->tokenizeExpression($expr);
        $exprParser = new ExpressionParser($tokens);
        return $exprParser->parse();
    }

    /**
     * Tokenize an expression string into tokens for the expression parser.
     *
     * @return array<array{type: string, value: string}>
     */
    private function tokenizeExpression(string $expr): array
    {
        $tokens = [];
        $i = 0;
        $len = strlen($expr);

        while ($i < $len) {
            // Skip whitespace
            if (ctype_space($expr[$i])) {
                $i++;
                continue;
            }

            // String literal
            if ($expr[$i] === "'" || $expr[$i] === '"') {
                $quote = $expr[$i];
                $i++;
                $str = '';
                while ($i < $len && $expr[$i] !== $quote) {
                    if ($expr[$i] === '\\' && $i + 1 < $len) {
                        $i++;
                    }
                    $str .= $expr[$i];
                    $i++;
                }
                if ($i < $len) {
                    $i++; // skip closing quote
                }
                $tokens[] = ['type' => 'STRING', 'value' => $str];
                continue;
            }

            // Number
            if (ctype_digit($expr[$i])) {
                $num = '';
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i];
                    $i++;
                }
                $tokens[] = ['type' => 'NUMBER', 'value' => $num];
                continue;
            }

            // Two-char operators
            if ($i + 1 < $len) {
                $two = $expr[$i] . $expr[$i + 1];
                if (in_array($two, ['==', '!=', '>=', '<='], true)) {
                    $tokens[] = ['type' => 'OPERATOR', 'value' => $two];
                    $i += 2;
                    continue;
                }
            }

            // Single-char tokens
            $charMap = [
                '>' => 'OPERATOR', '<' => 'OPERATOR',
                '|' => 'PIPE', '.' => 'DOT',
                '(' => 'LPAREN', ')' => 'RPAREN',
                '{' => 'LBRACE', '}' => 'RBRACE',
                ',' => 'COMMA', ':' => 'COLON',
            ];
            if (isset($charMap[$expr[$i]])) {
                $tokens[] = ['type' => $charMap[$expr[$i]], 'value' => $expr[$i]];
                $i++;
                continue;
            }

            // Identifier or keyword
            if (ctype_alpha($expr[$i]) || $expr[$i] === '_') {
                $ident = '';
                while ($i < $len && (ctype_alnum($expr[$i]) || $expr[$i] === '_')) {
                    $ident .= $expr[$i];
                    $i++;
                }
                $keywords = ['and', 'or', 'not', 'true', 'false', 'null'];
                $type = in_array($ident, $keywords, true) ? 'KEYWORD' : 'IDENTIFIER';
                $tokens[] = ['type' => $type, 'value' => $ident];
                continue;
            }

            $i++; // Skip unknown characters
        }

        return $tokens;
    }
}

/**
 * Recursive descent parser for expressions.
 *
 * Precedence (low → high):
 *   or → and → not → comparison → filter (pipe) → primary
 */
class ExpressionParser
{
    private array $tokens;
    private int $pos = 0;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    public function parse(): Expression
    {
        $expr = $this->parseOr();

        // If there are leftover tokens, that's fine — caller may not consume all
        return $expr;
    }

    private function current(): ?array
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function advance(): array
    {
        return $this->tokens[$this->pos++];
    }

    private function expect(string $type): array
    {
        $tok = $this->current();
        if ($tok === null || $tok['type'] !== $type) {
            // Return a dummy to avoid crashing — unknown vars silently output nothing
            return ['type' => $type, 'value' => ''];
        }
        return $this->advance();
    }

    private function parseOr(): Expression
    {
        $left = $this->parseAnd();

        while ($this->current() && $this->current()['type'] === 'KEYWORD' && $this->current()['value'] === 'or') {
            $this->advance();
            $right = $this->parseAnd();
            $left = new BooleanExpression($left, 'or', $right);
        }

        return $left;
    }

    private function parseAnd(): Expression
    {
        $left = $this->parseNot();

        while ($this->current() && $this->current()['type'] === 'KEYWORD' && $this->current()['value'] === 'and') {
            $this->advance();
            $right = $this->parseNot();
            $left = new BooleanExpression($left, 'and', $right);
        }

        return $left;
    }

    private function parseNot(): Expression
    {
        if ($this->current() && $this->current()['type'] === 'KEYWORD' && $this->current()['value'] === 'not') {
            $this->advance();
            return new NotExpression($this->parseNot());
        }

        return $this->parseComparison();
    }

    private function parseComparison(): Expression
    {
        $left = $this->parseFilter();

        if ($this->current() && $this->current()['type'] === 'OPERATOR') {
            $op = $this->advance()['value'];
            $right = $this->parseFilter();
            return new ComparisonExpression($left, $op, $right);
        }

        return $left;
    }

    private function parseFilter(): Expression
    {
        $expr = $this->parsePrimary();

        while ($this->current() && $this->current()['type'] === 'PIPE') {
            $this->advance(); // consume |
            $filterName = $this->expect('IDENTIFIER')['value'];
            $args = [];

            // Optional arguments in parentheses
            if ($this->current() && $this->current()['type'] === 'LPAREN') {
                $this->advance(); // consume (
                while ($this->current() && $this->current()['type'] !== 'RPAREN') {
                    $args[] = $this->parsePrimary();
                    if ($this->current() && $this->current()['type'] === 'COMMA') {
                        $this->advance();
                    }
                }
                if ($this->current()) {
                    $this->advance(); // consume )
                }
            }

            $expr = new FilterExpression($expr, $filterName, $args);
        }

        return $expr;
    }

    private function parsePrimary(): Expression
    {
        $tok = $this->current();

        if ($tok === null) {
            return new LiteralExpression('');
        }

        // String literal
        if ($tok['type'] === 'STRING') {
            $this->advance();
            return new LiteralExpression($tok['value']);
        }

        // Number literal
        if ($tok['type'] === 'NUMBER') {
            $this->advance();
            $val = str_contains($tok['value'], '.') ? (float) $tok['value'] : (int) $tok['value'];
            return new LiteralExpression($val);
        }

        // Boolean / null literals
        if ($tok['type'] === 'KEYWORD') {
            if ($tok['value'] === 'true') {
                $this->advance();
                return new LiteralExpression(true);
            }
            if ($tok['value'] === 'false') {
                $this->advance();
                return new LiteralExpression(false);
            }
            if ($tok['value'] === 'null') {
                $this->advance();
                return new LiteralExpression(null);
            }
        }

        // Grouped expression
        if ($tok['type'] === 'LPAREN') {
            $this->advance();
            $expr = $this->parseOr();
            $this->expect('RPAREN');
            return $expr;
        }

        // Variable (possibly dotted)
        if ($tok['type'] === 'IDENTIFIER') {
            $parts = [$this->advance()['value']];
            while ($this->current() && $this->current()['type'] === 'DOT') {
                $this->advance(); // consume .
                $parts[] = $this->expect('IDENTIFIER')['value'];
            }
            return new VariableExpression($parts);
        }

        // Fallback
        $this->advance();
        return new LiteralExpression('');
    }
}
```


---

### Task 4: Filter Registry

**Files:**
- Create: `app/Libraries/TemplateEngine/FilterRegistry.php`
- Create: `tests/unit/TemplateEngine/FilterTest.php`

- [ ] **Step 1: Write Filter tests**

```php
<?php

namespace Tests\Unit\TemplateEngine;

use App\Libraries\TemplateEngine\FilterRegistry;
use CodeIgniter\Test\CIUnitTestCase;

class FilterTest extends CIUnitTestCase
{
    private FilterRegistry $filters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filters = new FilterRegistry();
    }

    public function testDate(): void
    {
        $result = $this->filters->apply('date', '2026-03-26 14:30:00', ['F j, Y']);
        $this->assertSame('March 26, 2026', $result);
    }

    public function testNumberFormat(): void
    {
        $this->assertSame('1,234', $this->filters->apply('number_format', 1234));
    }

    public function testNl2br(): void
    {
        $this->assertStringContainsString('<br', $this->filters->apply('nl2br', "line1\nline2"));
    }

    public function testMd5(): void
    {
        $this->assertSame(md5('test'), $this->filters->apply('md5', 'test'));
    }

    public function testCount(): void
    {
        $this->assertSame(3, $this->filters->apply('count', [1, 2, 3]));
    }

    public function testExcerpt(): void
    {
        $long = str_repeat('word ', 50);
        $result = $this->filters->apply('excerpt', $long, [20]);
        $this->assertLessThanOrEqual(25, strlen($result)); // 20 + possible ellipsis
    }

    public function testDefault(): void
    {
        $this->assertSame('fallback', $this->filters->apply('default', '', ['fallback']));
        $this->assertSame('fallback', $this->filters->apply('default', null, ['fallback']));
        $this->assertSame('value', $this->filters->apply('default', 'value', ['fallback']));
    }

    public function testRaw(): void
    {
        $this->assertSame('<b>bold</b>', $this->filters->apply('raw', '<b>bold</b>'));
    }

    public function testStrtolower(): void
    {
        $this->assertSame('hello', $this->filters->apply('strtolower', 'HELLO'));
    }

    public function testStripTags(): void
    {
        $this->assertSame('text', $this->filters->apply('strip_tags', '<p>text</p>'));
    }

    public function testUnknownFilterReturnsInput(): void
    {
        $this->assertSame('input', $this->filters->apply('nonexistent', 'input'));
    }
}
```

- [ ] **Step 2: Write FilterRegistry**

```php
<?php

namespace App\Libraries\TemplateEngine;

class FilterRegistry
{
    /**
     * Apply a named filter to a value.
     *
     * Unknown filters return the input unchanged (silent fail per spec).
     */
    public function apply(string $name, mixed $value, array $args = []): mixed
    {
        return match ($name) {
            'date'          => $this->filterDate($value, $args),
            'number_format' => $this->filterNumberFormat($value, $args),
            'nl2br'         => nl2br((string) $value),
            'md5'           => md5((string) $value),
            'count'         => is_countable($value) ? count($value) : 0,
            'excerpt'       => $this->filterExcerpt($value, $args),
            'default'       => $this->filterDefault($value, $args),
            'raw'           => $value,  // Marker — Interpreter checks for this
            'strtolower'    => strtolower((string) $value),
            'strip_tags'    => strip_tags((string) $value),
            default         => $value,  // Unknown filter — pass through
        };
    }

    /**
     * Check if a filter name is the 'raw' marker.
     * The Interpreter uses this to skip escaping.
     */
    public function isRawFilter(string $name): bool
    {
        return $name === 'raw';
    }

    private function filterDate(mixed $value, array $args): string
    {
        $format = $args[0] ?? 'Y-m-d';
        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);
        if ($timestamp === false) {
            return (string) $value;
        }
        return date($format, $timestamp);
    }

    private function filterNumberFormat(mixed $value, array $args): string
    {
        $decimals = (int) ($args[0] ?? 0);
        return number_format((float) $value, $decimals);
    }

    private function filterExcerpt(mixed $value, array $args): string
    {
        $length = (int) ($args[0] ?? 150);
        $plain = strip_tags((string) $value);
        if (strlen($plain) <= $length) {
            return $plain;
        }
        return rtrim(substr($plain, 0, $length), ' .,;:') . '…';
    }

    private function filterDefault(mixed $value, array $args): mixed
    {
        if ($value === null || $value === '' || $value === false) {
            return $args[0] ?? '';
        }
        return $value;
    }
}
```


---

### Task 5: Tag Function Registry

**Files:**
- Create: `app/Libraries/TemplateEngine/TagRegistry.php`

- [ ] **Step 1: Write TagRegistry**

This class resolves whitelisted tag function calls. Each function receives an array of resolved argument values and returns a string.

```php
<?php

namespace App\Libraries\TemplateEngine;

class TagRegistry
{
    /**
     * Call a whitelisted tag function by name.
     *
     * @param string $name       Function name (e.g. 'lang', 'base_url')
     * @param array  $args       Resolved argument values
     * @return string            Output string (already HTML-safe where applicable)
     */
    public function call(string $name, array $args): string
    {
        return match ($name) {
            'lang'           => $this->tagLang($args),
            'theme_url'      => $this->tagThemeUrl($args),
            'base_url'       => $this->tagBaseUrl($args),
            'widget_area'    => $this->tagWidgetArea($args),
            'post_url'       => $this->tagPostUrl($args),
            'category_url'   => $this->tagCategoryUrl($args),
            'tag_url'        => $this->tagTagUrl($args),
            'render_content' => $this->tagRenderContent($args),
            default          => '', // Unknown tag function — silent
        };
    }

    private function tagLang(array $args): string
    {
        $key = (string) ($args[0] ?? '');
        $params = array_slice($args, 1);
        return lang($key, $params);
    }

    private function tagThemeUrl(array $args): string
    {
        return theme_url((string) ($args[0] ?? ''));
    }

    private function tagBaseUrl(array $args): string
    {
        return base_url((string) ($args[0] ?? ''));
    }

    private function tagWidgetArea(array $args): string
    {
        return widget_area((string) ($args[0] ?? ''));
    }

    private function tagPostUrl(array $args): string
    {
        return post_url((string) ($args[0] ?? ''));
    }

    private function tagCategoryUrl(array $args): string
    {
        return category_url((string) ($args[0] ?? ''));
    }

    private function tagTagUrl(array $args): string
    {
        return tag_url((string) ($args[0] ?? ''));
    }

    private function tagRenderContent(array $args): string
    {
        $entity = $args[0] ?? null;
        if (is_object($entity)) {
            return render_content($entity);
        }
        return '';
    }
}
```

- [ ] **Step 2: Write TagRegistry integration test**

Create `tests/integration/TemplateEngine/TagRegistryTest.php` — boots CI4 so real helpers are available:

```php
<?php

namespace Tests\Integration\TemplateEngine;

use App\Libraries\TemplateEngine\TagRegistry;
use CodeIgniter\Test\CIUnitTestCase;

class TagRegistryTest extends CIUnitTestCase
{
    private TagRegistry $tags;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tags = new TagRegistry();
    }

    public function testLangReturnsLocalizedString(): void
    {
        $result = $this->tags->call('lang', ['Blog.home']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testBaseUrlReturnsUrl(): void
    {
        $result = $this->tags->call('base_url', ['test/path']);
        $this->assertStringContainsString('test/path', $result);
    }

    public function testThemeUrlReturnsUrl(): void
    {
        $result = $this->tags->call('theme_url', ['css/theme.css']);
        $this->assertStringContainsString('css/theme.css', $result);
    }

    public function testPostUrlReturnsUrl(): void
    {
        $result = $this->tags->call('post_url', ['my-post']);
        $this->assertStringContainsString('my-post', $result);
    }

    public function testCategoryUrlReturnsUrl(): void
    {
        $result = $this->tags->call('category_url', ['tech']);
        $this->assertStringContainsString('tech', $result);
    }

    public function testTagUrlReturnsUrl(): void
    {
        $result = $this->tags->call('tag_url', ['php']);
        $this->assertStringContainsString('php', $result);
    }

    public function testUnknownTagReturnsEmpty(): void
    {
        $this->assertSame('', $this->tags->call('nonexistent', ['arg']));
    }
}
```


---

### Task 6: Interpreter

**Files:**
- Create: `app/Libraries/TemplateEngine/Interpreter.php`
- Create: `tests/unit/TemplateEngine/InterpreterTest.php`

- [ ] **Step 1: Write Interpreter tests**

```php
<?php

namespace Tests\Unit\TemplateEngine;

use App\Libraries\TemplateEngine\{Interpreter, FilterRegistry, TagRegistry};
use App\Libraries\TemplateEngine\{
    TextNode, OutputNode, RawOutputNode, IfNode, ForNode,
    BlockNode, IncludeNode, TagFunctionNode,
    VariableExpression, LiteralExpression, ComparisonExpression,
    FilterExpression, BooleanExpression, NotExpression
};
use CodeIgniter\Test\CIUnitTestCase;

class InterpreterTest extends CIUnitTestCase
{
    private Interpreter $interpreter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interpreter = new Interpreter(
            new FilterRegistry(),
            new TagRegistry(),
        );
    }

    public function testTextNode(): void
    {
        $nodes = [new TextNode('Hello')];
        $this->assertSame('Hello', $this->interpreter->interpret($nodes, []));
    }

    public function testOutputEscaped(): void
    {
        $nodes = [new OutputNode(new VariableExpression(['name']))];
        $result = $this->interpreter->interpret($nodes, ['name' => '<b>Rob</b>']);
        $this->assertSame('&lt;b&gt;Rob&lt;/b&gt;', $result);
    }

    public function testRawOutput(): void
    {
        $nodes = [new RawOutputNode(new VariableExpression(['html']))];
        $result = $this->interpreter->interpret($nodes, ['html' => '<b>bold</b>']);
        $this->assertSame('<b>bold</b>', $result);
    }

    public function testDottedVariable(): void
    {
        $nodes = [new OutputNode(new VariableExpression(['post', 'title']))];
        $result = $this->interpreter->interpret($nodes, [
            'post' => (object) ['title' => 'Hello World'],
        ]);
        $this->assertSame('Hello World', $result);
    }

    public function testUnknownVariableOutputsNothing(): void
    {
        $nodes = [new OutputNode(new VariableExpression(['nonexistent']))];
        $this->assertSame('', $this->interpreter->interpret($nodes, []));
    }

    public function testIfTrue(): void
    {
        $node = new IfNode(
            [['condition' => new VariableExpression(['show']), 'body' => [new TextNode('yes')]]],
            [new TextNode('no')]
        );
        $this->assertSame('yes', $this->interpreter->interpret([$node], ['show' => true]));
    }

    public function testIfFalse(): void
    {
        $node = new IfNode(
            [['condition' => new VariableExpression(['show']), 'body' => [new TextNode('yes')]]],
            [new TextNode('no')]
        );
        $this->assertSame('no', $this->interpreter->interpret([$node], ['show' => false]));
    }

    public function testForLoop(): void
    {
        $node = new ForNode(
            'item',
            new VariableExpression(['items']),
            [new OutputNode(new VariableExpression(['item']))]
        );
        $result = $this->interpreter->interpret([$node], ['items' => ['a', 'b', 'c']]);
        $this->assertSame('abc', $result);
    }

    public function testComparison(): void
    {
        $condition = new ComparisonExpression(
            new VariableExpression(['count']),
            '>',
            new LiteralExpression(0)
        );
        $node = new IfNode(
            [['condition' => $condition, 'body' => [new TextNode('has items')]]],
        );
        $this->assertSame('has items', $this->interpreter->interpret([$node], ['count' => 5]));
        $this->assertSame('', $this->interpreter->interpret([$node], ['count' => 0]));
    }

    public function testFilter(): void
    {
        $expr = new FilterExpression(
            new VariableExpression(['name']),
            'strtolower'
        );
        $nodes = [new OutputNode($expr)];
        $this->assertSame('hello', $this->interpreter->interpret($nodes, ['name' => 'HELLO']));
    }

    public function testNotExpression(): void
    {
        $condition = new NotExpression(new VariableExpression(['hidden']));
        $node = new IfNode(
            [['condition' => $condition, 'body' => [new TextNode('visible')]]],
        );
        $this->assertSame('visible', $this->interpreter->interpret([$node], ['hidden' => false]));
        $this->assertSame('', $this->interpreter->interpret([$node], ['hidden' => true]));
    }

    public function testBooleanAnd(): void
    {
        $condition = new BooleanExpression(
            new VariableExpression(['a']),
            'and',
            new VariableExpression(['b'])
        );
        $node = new IfNode(
            [['condition' => $condition, 'body' => [new TextNode('both')]]],
        );
        $this->assertSame('both', $this->interpreter->interpret([$node], ['a' => true, 'b' => true]));
        $this->assertSame('', $this->interpreter->interpret([$node], ['a' => true, 'b' => false]));
    }
}
```

- [ ] **Step 2: Write the Interpreter**

```php
<?php

namespace App\Libraries\TemplateEngine;

class Interpreter
{
    /** @var array<string, Node[]> Child block overrides from extends. */
    private array $blockOverrides = [];

    /** @var callable|null Callback to resolve includes: fn(string $name, array $data): string */
    private $includeResolver = null;

    public function __construct(
        private readonly FilterRegistry $filters,
        private readonly TagRegistry $tags,
    ) {}

    /**
     * Set the callback used to resolve {% include %} tags.
     * The Engine sets this so includes can load and render other templates.
     */
    public function setIncludeResolver(callable $resolver): void
    {
        $this->includeResolver = $resolver;
    }

    /**
     * Set block overrides from a child template (for extends).
     *
     * @param array<string, Node[]> $blocks
     */
    public function setBlockOverrides(array $blocks): void
    {
        $this->blockOverrides = $blocks;
    }

    /**
     * Interpret an AST with the given data context.
     *
     * @param Node[]  $nodes
     * @param array   $data  Variable context
     * @return string Rendered output
     */
    public function interpret(array $nodes, array $data): string
    {
        $output = '';

        foreach ($nodes as $node) {
            $output .= $this->evaluateNode($node, $data);
        }

        return $output;
    }

    private function evaluateNode(Node $node, array $data): string
    {
        return match (true) {
            $node instanceof TextNode        => $node->text,
            $node instanceof OutputNode      => $this->evaluateOutput($node, $data),
            $node instanceof RawOutputNode   => $this->evaluateRawOutput($node, $data),
            $node instanceof IfNode          => $this->evaluateIf($node, $data),
            $node instanceof ForNode         => $this->evaluateFor($node, $data),
            $node instanceof BlockNode       => $this->evaluateBlock($node, $data),
            $node instanceof IncludeNode     => $this->evaluateInclude($node, $data),
            $node instanceof TagFunctionNode => $this->evaluateTagFunction($node, $data),
            $node instanceof ExtendsNode     => '', // Handled by Engine, not Interpreter
            default                          => '',
        };
    }

    private function evaluateOutput(OutputNode $node, array $data): string
    {
        $value = $this->resolveExpression($node->expression, $data);

        // Check if the outermost filter is 'raw'
        if ($node->expression instanceof FilterExpression && $node->expression->filterName === 'raw') {
            return (string) $value;
        }

        return esc((string) $value);
    }

    private function evaluateRawOutput(RawOutputNode $node, array $data): string
    {
        $value = $this->resolveExpression($node->expression, $data);
        return (string) $value;
    }

    private function evaluateIf(IfNode $node, array $data): string
    {
        foreach ($node->branches as $branch) {
            $conditionValue = $this->resolveExpression($branch['condition'], $data);
            if ($this->isTruthy($conditionValue)) {
                return $this->interpret($branch['body'], $data);
            }
        }

        if ($node->elseBody !== null) {
            return $this->interpret($node->elseBody, $data);
        }

        return '';
    }

    private function evaluateFor(ForNode $node, array $data): string
    {
        $collection = $this->resolveExpression($node->iterable, $data);

        if (! is_iterable($collection)) {
            return '';
        }

        $output = '';
        foreach ($collection as $item) {
            $loopData = array_merge($data, [$node->variableName => $item]);
            $output .= $this->interpret($node->body, $loopData);
        }

        return $output;
    }

    private function evaluateBlock(BlockNode $node, array $data): string
    {
        // If a child template provided an override for this block, use it
        if (isset($this->blockOverrides[$node->name])) {
            return $this->interpret($this->blockOverrides[$node->name], $data);
        }

        // Otherwise render the default block content
        return $this->interpret($node->body, $data);
    }

    private function evaluateInclude(IncludeNode $node, array $data): string
    {
        if ($this->includeResolver === null) {
            return '';
        }

        // Build the data for the included template
        $includeData = $data; // Inherit parent scope

        if ($node->withData !== null) {
            foreach ($node->withData as $key => $expr) {
                $includeData[$key] = $this->resolveExpression($expr, $data);
            }
        }

        return ($this->includeResolver)($node->templateName, $includeData);
    }

    private function evaluateTagFunction(TagFunctionNode $node, array $data): string
    {
        $resolvedArgs = [];
        foreach ($node->arguments as $argExpr) {
            $resolvedArgs[] = $this->resolveExpression($argExpr, $data);
        }

        return $this->tags->call($node->name, $resolvedArgs);
    }

    // ─── Expression Resolution ───────────────────────────────

    public function resolveExpression(Expression $expr, array $data): mixed
    {
        return match (true) {
            $expr instanceof LiteralExpression    => $expr->value,
            $expr instanceof VariableExpression   => $this->resolveVariable($expr, $data),
            $expr instanceof ComparisonExpression => $this->resolveComparison($expr, $data),
            $expr instanceof BooleanExpression    => $this->resolveBoolean($expr, $data),
            $expr instanceof NotExpression        => ! $this->isTruthy($this->resolveExpression($expr->expression, $data)),
            $expr instanceof FilterExpression     => $this->resolveFilter($expr, $data),
            default                               => null,
        };
    }

    private function resolveVariable(VariableExpression $expr, array $data): mixed
    {
        $value = $data;

        foreach ($expr->parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null; // Unknown variable — silent null
            }
        }

        return $value;
    }

    private function resolveComparison(ComparisonExpression $expr, array $data): bool
    {
        $left = $this->resolveExpression($expr->left, $data);
        $right = $this->resolveExpression($expr->right, $data);

        return match ($expr->operator) {
            '=='    => $left == $right,
            '!='    => $left != $right,
            '>'     => $left > $right,
            '<'     => $left < $right,
            '>='    => $left >= $right,
            '<='    => $left <= $right,
            default => false,
        };
    }

    private function resolveBoolean(BooleanExpression $expr, array $data): bool
    {
        $left = $this->isTruthy($this->resolveExpression($expr->left, $data));

        return match ($expr->operator) {
            'and' => $left && $this->isTruthy($this->resolveExpression($expr->right, $data)),
            'or'  => $left || $this->isTruthy($this->resolveExpression($expr->right, $data)),
            default => false,
        };
    }

    private function resolveFilter(FilterExpression $expr, array $data): mixed
    {
        $value = $this->resolveExpression($expr->expression, $data);

        $resolvedArgs = [];
        foreach ($expr->arguments as $arg) {
            $resolvedArgs[] = $this->resolveExpression($arg, $data);
        }

        return $this->filters->apply($expr->filterName, $value, $resolvedArgs);
    }

    private function isTruthy(mixed $value): bool
    {
        if ($value === null || $value === false || $value === '' || $value === 0 || $value === []) {
            return false;
        }
        return true;
    }
}
```


---

### Task 7: Engine Entry Point

**Files:**
- Create: `app/Libraries/TemplateEngine/Engine.php`
- Create: `tests/unit/TemplateEngine/EngineTest.php`

- [ ] **Step 1: Write Engine integration tests**

These tests use actual `.tpl` template strings and verify end-to-end rendering. They do NOT test tag functions that require CI4 services (lang, base_url, etc.) — those are integration-level.

```php
<?php

namespace Tests\Unit\TemplateEngine;

use App\Libraries\TemplateEngine\Engine;
use CodeIgniter\Test\CIUnitTestCase;

class EngineTest extends CIUnitTestCase
{
    private Engine $engine;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new Engine();
        $this->tmpDir = WRITEPATH . 'tests/templates/';
        if (! is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up temp files
        array_map('unlink', glob($this->tmpDir . '*.tpl'));
    }

    private function writeTpl(string $name, string $content): string
    {
        $path = $this->tmpDir . $name . '.tpl';
        file_put_contents($path, $content);
        return $path;
    }

    public function testSimpleVariable(): void
    {
        $path = $this->writeTpl('simple', 'Hello {{ name }}!');
        $result = $this->engine->render($path, ['name' => 'World']);
        $this->assertSame('Hello World!', $result);
    }

    public function testAutoEscaping(): void
    {
        $path = $this->writeTpl('escape', '{{ html }}');
        $result = $this->engine->render($path, ['html' => '<script>alert(1)</script>']);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testRawOutput(): void
    {
        $path = $this->writeTpl('raw', '{! html !}');
        $result = $this->engine->render($path, ['html' => '<b>bold</b>']);
        $this->assertSame('<b>bold</b>', $result);
    }

    public function testIfElse(): void
    {
        $tpl = '{% if show %}visible{% else %}hidden{% endif %}';
        $path = $this->writeTpl('ifelse', $tpl);
        $this->assertSame('visible', $this->engine->render($path, ['show' => true]));
        $this->assertSame('hidden', $this->engine->render($path, ['show' => false]));
    }

    public function testForLoop(): void
    {
        $tpl = '{% for item in items %}[{{ item }}]{% endfor %}';
        $path = $this->writeTpl('for', $tpl);
        $result = $this->engine->render($path, ['items' => ['a', 'b']]);
        $this->assertSame('[a][b]', $result);
    }

    public function testDottedAccess(): void
    {
        $tpl = '{{ user.name }}';
        $path = $this->writeTpl('dotted', $tpl);
        $result = $this->engine->render($path, ['user' => (object) ['name' => 'Rob']]);
        $this->assertSame('Rob', $result);
    }

    public function testFilterChain(): void
    {
        $tpl = "{{ name | strtolower | default('anon') }}";
        $path = $this->writeTpl('filter', $tpl);
        $this->assertSame('rob', $this->engine->render($path, ['name' => 'ROB']));
        $this->assertSame('anon', $this->engine->render($path, ['name' => '']));
    }

    public function testExtendsAndBlock(): void
    {
        $this->writeTpl('base', '<html>{% block content %}default{% endblock %}</html>');
        $this->writeTpl('child', "{% extends 'base' %}{% block content %}override{% endblock %}");

        $childPath = $this->tmpDir . 'child.tpl';
        $result = $this->engine->render($childPath, [], $this->tmpDir);
        $this->assertSame('<html>override</html>', $result);
    }

    public function testInclude(): void
    {
        $this->writeTpl('_partial', 'Hello {{ who }}!');
        $this->writeTpl('main', "{% include '_partial' with {who: name} %}");

        $mainPath = $this->tmpDir . 'main.tpl';
        $result = $this->engine->render($mainPath, ['name' => 'World'], $this->tmpDir);
        $this->assertSame('Hello World!', $result);
    }

    public function testComments(): void
    {
        $path = $this->writeTpl('comments', 'before{# hidden #}after');
        $this->assertSame('beforeafter', $this->engine->render($path, []));
    }

    public function testUnknownVarSilent(): void
    {
        $path = $this->writeTpl('unknown', '[{{ nope }}]');
        $this->assertSame('[]', $this->engine->render($path, []));
    }
}
```

- [ ] **Step 2: Write the Engine**

```php
<?php

namespace App\Libraries\TemplateEngine;

class Engine
{
    private Lexer $lexer;
    private Parser $parser;
    private Interpreter $interpreter;
    private FilterRegistry $filters;
    private TagRegistry $tags;

    public function __construct()
    {
        $this->lexer   = new Lexer();
        $this->parser  = new Parser();
        $this->filters = new FilterRegistry();
        $this->tags    = new TagRegistry();
        $this->interpreter = new Interpreter($this->filters, $this->tags);
    }

    /**
     * Render a .tpl template file with the given data.
     *
     * @param string      $templatePath Absolute path to the .tpl file
     * @param array       $data         Variable context
     * @param string|null $basePath     Base directory for resolving includes/extends
     *                                  (defaults to the template's directory)
     * @return string Rendered HTML
     */
    public function render(string $templatePath, array $data, ?string $basePath = null): string
    {
        if (! is_file($templatePath)) {
            return '';
        }

        if ($basePath === null) {
            $basePath = dirname($templatePath) . '/';
        }

        $content = file_get_contents($templatePath);
        $segments = $this->lexer->tokenize($content);
        $ast = $this->parser->parse($segments);

        // Set up include resolver so {% include %} can load other templates
        $this->interpreter->setIncludeResolver(
            fn(string $name, array $includeData) => $this->renderInclude($name, $includeData, $basePath)
        );

        // Check for {% extends %}
        $extendsNode = null;
        foreach ($ast as $node) {
            if ($node instanceof ExtendsNode) {
                $extendsNode = $node;
                break;
            }
        }

        if ($extendsNode !== null) {
            return $this->renderWithExtends($extendsNode, $ast, $data, $basePath);
        }

        $this->interpreter->setBlockOverrides([]);
        return $this->interpreter->interpret($ast, $data);
    }

    /**
     * Handle extends: collect child blocks, render parent with overrides.
     */
    private function renderWithExtends(ExtendsNode $extendsNode, array $childAst, array $data, string $basePath): string
    {
        // Collect block definitions from child
        $childBlocks = [];
        foreach ($childAst as $node) {
            if ($node instanceof BlockNode) {
                $childBlocks[$node->name] = $node->body;
            }
        }

        // Load and parse parent template
        $parentPath = $this->resolveTemplatePath($extendsNode->templateName, $basePath);
        if (! is_file($parentPath)) {
            return '';
        }

        $parentContent = file_get_contents($parentPath);
        $parentSegments = $this->lexer->tokenize($parentContent);
        $parentAst = $this->parser->parse($parentSegments);

        // Set up include resolver for parent too
        $this->interpreter->setIncludeResolver(
            fn(string $name, array $includeData) => $this->renderInclude($name, $includeData, $basePath)
        );

        // Set child blocks as overrides and render parent
        $this->interpreter->setBlockOverrides($childBlocks);
        return $this->interpreter->interpret($parentAst, $data);
    }

    /**
     * Render an included template.
     */
    private function renderInclude(string $name, array $data, string $basePath): string
    {
        $path = $this->resolveTemplatePath($name, $basePath);
        if (! is_file($path)) {
            return '';
        }

        // Create a fresh interpreter for the include to avoid block override leakage
        $includeInterpreter = new Interpreter($this->filters, $this->tags);
        $includeInterpreter->setIncludeResolver(
            fn(string $n, array $d) => $this->renderInclude($n, $d, $basePath)
        );
        $includeInterpreter->setBlockOverrides([]);

        $content = file_get_contents($path);
        $segments = $this->lexer->tokenize($content);
        $ast = $this->parser->parse($segments);

        return $includeInterpreter->interpret($ast, $data);
    }

    /**
     * Resolve a template name to an absolute path.
     * Names are relative to basePath, with .tpl extension appended if missing.
     */
    private function resolveTemplatePath(string $name, string $basePath): string
    {
        // Security: reject path traversal
        if (str_contains($name, '..') || str_contains($name, "\0")) {
            return '';
        }

        $path = rtrim($basePath, '/') . '/' . $name;
        if (! str_ends_with($path, '.tpl')) {
            $path .= '.tpl';
        }

        return $path;
    }
}
```


---

## Phase 2: Asset Pipeline

### Task 8: Replace symlinkAssets() with publishAssets()

**Files:**
- Modify: `app/Services/ThemeService.php`

- [ ] **Step 1: Replace `symlinkAssets()` with `publishAssets()` in ThemeService**

Open `app/Services/ThemeService.php`. Replace the `symlinkAssets` method (lines 158-183) with:

```php
/**
 * Copy theme assets to the web-accessible directory.
 *
 * Copies themes/{folder}/assets/* → FCPATH/themes/{folder}/
 * Replaces the old symlink approach — real files, no symlinks needed.
 */
public function publishAssets(string $folder): void
{
    if (! preg_match('/^[a-zA-Z0-9_-]+$/', $folder)) {
        throw new \RuntimeException('Invalid theme folder name: ' . $folder);
    }

    $source = THEMES_PATH . $folder . '/assets';
    $dest   = FCPATH . 'themes/' . $folder;

    if (! is_dir($source)) {
        return;
    }

    // Clean replace: remove existing destination
    if (is_dir($dest)) {
        $this->removeDirectory($dest);
    }

    // Also remove any existing symlink from the old system
    if (is_link($dest)) {
        unlink($dest);
    }

    // Ensure parent directory exists
    $parentDir = FCPATH . 'themes';
    if (! is_dir($parentDir)) {
        mkdir($parentDir, 0755, true);
    }

    $this->copyDirectory($source, $dest);
}

/** Recursively copy a directory. */
private function copyDirectory(string $source, string $dest): void
{
    mkdir($dest, 0755, true);

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $targetPath = $dest . '/' . $iterator->getSubPathname();
        if ($item->isDir()) {
            if (! is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            copy($item->getPathname(), $targetPath);
        }
    }
}

/** Recursively remove a directory and all its contents. */
private function removeDirectory(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}
```

- [ ] **Step 2: Update all calls from `symlinkAssets` to `publishAssets`**

In the same file, find and replace the two internal calls:

In `sync()` (around line 45): change `$this->symlinkAssets($folder)` to `$this->publishAssets($folder)`

In `activate()` (around line 114): change `$this->symlinkAssets($theme->folder)` to `$this->publishAssets($theme->folder)`

- [ ] **Step 3: Update MarketplaceService**

Open `app/Services/MarketplaceService.php`. At line 172, change:

```php
// OLD:
(new ThemeService())->symlinkAssets($folder);

// NEW:
(new ThemeService())->publishAssets($folder);
```


---

### Task 9: Media Upload Path Change

**Files:**
- Modify: `app/Services/MediaService.php`
- Modify: `app/Views/admin/media/index.php`
- Modify: `app/Views/admin/users/profile.php`

- [ ] **Step 1: Update MediaService upload path and stored path**

In `app/Services/MediaService.php`, change the `upload()` method:

Replace line 38-39:
```php
// OLD:
$relDir  = 'uploads/' . date('Y/m');
$absDir  = WRITEPATH . $relDir;
```
With:
```php
// NEW:
$relDir  = 'uploads/' . date('Y/m');
$absDir  = FCPATH . $relDir;
```

Replace lines 50-51 (intermediates):
```php
// OLD:
$absIntermediate      = WRITEPATH . $relDir . '/' . $name . '.' . $ext;
$thumbDir             = WRITEPATH . $relDir . '/thumbs';
```
With:
```php
// NEW:
$absIntermediate      = FCPATH . $relDir . '/' . $name . '.' . $ext;
$thumbDir             = FCPATH . $relDir . '/thumbs';
```

Replace lines 69-71 (webp paths):
```php
// OLD:
$relPath   = $relDir . '/' . $name . '.webp';
$absPath   = WRITEPATH . $relPath;
$thumbPath = $thumbDir . '/' . $name . '.webp';
```
With:
```php
// NEW:
$relPath   = '/' . $relDir . '/' . $name . '.webp';
$absPath   = FCPATH . $relDir . '/' . $name . '.webp';
$thumbPath = $thumbDir . '/' . $name . '.webp';
```

Replace line 78 (non-webp path):
```php
// OLD:
$relPath   = $relDir . '/' . $name . '.' . $ext;
$absPath   = $absIntermediate;
```
With:
```php
// NEW:
$relPath   = '/' . $relDir . '/' . $name . '.' . $ext;
$absPath   = $absIntermediate;
```

Replace line 98 (return URL):
```php
// OLD:
'url'  => base_url('writable/' . $relPath),
```
With:
```php
// NEW:
'url'  => base_url($relPath),
```

Update the `delete()` method, line 109:
```php
// OLD:
$abs = WRITEPATH . ltrim($media->path, '/');
```
With:
```php
// NEW:
$abs = FCPATH . ltrim($media->path, '/');
```

**Note:** The tmp file path for initial move stays in `WRITEPATH . 'tmp/'` — that's fine, it's a temp location.

- [ ] **Step 2: Update admin media index view**

In `app/Views/admin/media/index.php`, line 29:
```php
// OLD:
<img src="<?= esc(base_url('writable/' . $item->path)) ?>"
// NEW:
<img src="<?= esc(base_url($item->path)) ?>"
```

- [ ] **Step 3: Update admin user profile view**

In `app/Views/admin/users/profile.php`, line 48:
```php
// OLD:
<img src="<?= esc(base_url('writable/' . $profile->avatar)) ?>"
// NEW:
<img src="<?= esc(base_url($profile->avatar)) ?>"
```

Line 115:
```php
// OLD:
? base_url('writable/' . $profile->avatar)
// NEW:
? base_url($profile->avatar)
```


---

### Task 10: BackupService + Admin Backup View

**Files:**
- Modify: `app/Services/BackupService.php`
- Modify: `app/Views/admin/backup/index.php`

- [ ] **Step 1: Update BackupService**

In `app/Services/BackupService.php`, find the line that references `WRITEPATH . 'uploads/'` (around line 37):

```php
// OLD:
$this->zipDirectory($zip, WRITEPATH . 'uploads/', 'uploads/');
// NEW:
$this->zipDirectory($zip, FCPATH . 'uploads/', 'uploads/');
```

- [ ] **Step 2: Update backup admin view documentation text**

In `app/Views/admin/backup/index.php`, line 47:
```php
// OLD:
navigation, themes, widgets, etc.) and every file in <code>writable/uploads/</code>.
// NEW:
navigation, themes, widgets, etc.) and every file in <code>uploads/</code>.
```

Line 55:
```php
// OLD:
<code>uploads/</code> folder back to <code>writable/uploads/</code>.
// NEW:
<code>uploads/</code> folder back to the <code>uploads/</code> directory next to <code>index.php</code>.
```


---

## Phase 3: Infrastructure

### Task 11: ThemeService — Data Bag + Engine Integration

**Files:**
- Modify: `app/Services/ThemeService.php`

This is the largest single change. ThemeService becomes the sole owner of the rendering data bag and integrates the template engine.

- [ ] **Step 1: Rewrite ThemeService::view() and add data bag assembly**

Add these new `use` statements at the top of ThemeService.php:

```php
use App\Libraries\TemplateEngine\Engine;
use App\Models\NavigationModel;
use App\Models\SocialModel;
use App\Services\PluginManager;
```

Add a new private property:

```php
private ?Engine $engine = null;
```

Add a method to lazily create the engine:

```php
private function getEngine(): Engine
{
    if ($this->engine === null) {
        $this->engine = new Engine();
    }
    return $this->engine;
}
```

Replace the existing `view()` method (lines 59-87) with the following. **Note:** This `view()` is replaced in Task 12 with caching support.

```php
/**
 * Render a theme view with the template engine.
 *
 * ThemeService owns the full data bag: it loads common data (nav, settings,
 * theme options, auth state, etc.) internally, then merges in page-specific
 * data from the controller.
 *
 * @param string $name     View name (e.g. 'home', 'post', 'page')
 * @param array  $pageData Page-specific data from the controller
 * @return string Rendered HTML
 */
public function view(string $name, array $pageData = []): string
{
    $theme = $this->getActive();
    if (! $theme) {
        return '<p>No active theme.</p>';
    }

    $path = THEMES_PATH . $theme->folder . '/views/' . $name . '.tpl';
    if (! is_file($path)) {
        return '<p>Theme view not found: ' . esc($name) . '</p>';
    }

    // Build the complete data bag
    $data = array_merge($this->buildCommonData(), $pageData);

    // Render via template engine
    $basePath = THEMES_PATH . $theme->folder . '/views/';
    return $this->getEngine()->render($path, $data, $basePath);
}

/**
 * Build the common data bag that every theme view receives.
 *
 * This replaces what BaseController::initController() used to build.
 */
private function buildCommonData(): array
{
    $theme = $this->getActive();
    $request = service('request');

    // Navigation
    try {
        $navModel = new NavigationModel();
        $primaryNav = $navModel->where('nav_group', 'primary')->orderBy('sort_order')->findAll();
        $footerNav  = $navModel->where('nav_group', 'footer')->orderBy('sort_order')->findAll();
    } catch (\Throwable $e) {
        $primaryNav = [];
        $footerNav  = [];
    }

    // Social links
    try {
        $socialLinks = (new SocialModel())->where('is_active', 1)->orderBy('sort_order')->findAll();
    } catch (\Throwable $e) {
        $socialLinks = [];
    }

    // Plugin menu items
    try {
        $pm = PluginManager::instance();
        $pm->loadAll();
        $pluginMenuItems = $pm->getMenuItems();
    } catch (\Throwable $e) {
        $pluginMenuItems = [];
    }

    // Theme options — load ALL options for active theme into the bag by key name
    $themeOptions = [];
    if ($theme) {
        $rows = db_connect()->table('theme_options')
            ->where('theme_id', $theme->id)
            ->get()->getResultObject();
        foreach ($rows as $row) {
            $themeOptions[$row->option_key] = $row->option_value;
        }
    }

    // Locale
    $locale = $request->getLocale();
    if (empty($locale)) {
        $locale = config('App')->defaultLocale;
    }

    // Auth state
    $isLoggedIn = $this->isLoggedIn();

    // Flash messages
    $flashSuccess = session()->getFlashdata('success');
    $flashError   = session()->getFlashdata('error');

    // Settings
    $analyticsId       = setting('Seo.googleAnalytics');
    $sitemapEnabled    = (bool) setting('Seo.sitemapEnabled');
    $commentsEnabled   = (bool) setting('App.commentsEnabled');
    $commentModeration = (bool) setting('App.commentModeration');
    $hcaptchaSiteKey   = env('hcaptcha.siteKey') ?: '';

    return array_merge($themeOptions, [
        'theme'              => $theme,
        'site_name'          => site_name(),
        'site_tagline'       => site_tagline(),
        'locale'             => $locale,
        'primary_nav'        => $primaryNav,
        'footer_nav'         => $footerNav,
        'social_links'       => $socialLinks,
        'plugin_menu_items'  => $pluginMenuItems,
        'is_logged_in'       => $isLoggedIn,
        'flash_success'      => $flashSuccess,
        'flash_error'        => $flashError,
        'analytics_id'       => $analyticsId,
        'sitemap_enabled'    => $sitemapEnabled,
        'comments_enabled'   => $commentsEnabled,
        'comment_moderation' => $commentModeration,
        'hcaptcha_site_key'  => $hcaptchaSiteKey,
        'lang_switcher'      => $this->langSwitcherData,
    ]);
}

/**
 * Allow controllers to inject the language switcher data.
 * Called after buildLangSwitcher() in public controllers.
 */
public function setLangSwitcher(array $langSwitcher): void
{
    $this->langSwitcherData = $langSwitcher;
}

private array $langSwitcherData = [];
```


- [ ] **Step 2: Update `discover()` to read `theme_info.json`**

Replace the `discover()` method:

```php
public function discover(): array
{
    $themes = [];
    foreach (glob(THEMES_PATH . '*', GLOB_ONLYDIR) as $dir) {
        $jsonFile = $dir . '/theme_info.json';
        $phpFile  = $dir . '/theme_info.php';

        if (is_file($jsonFile)) {
            $info = json_decode(file_get_contents($jsonFile), true);
        } elseif (is_file($phpFile)) {
            // Legacy fallback during transition
            $info = require $phpFile;
        } else {
            continue;
        }

        if (! is_array($info)) {
            continue;
        }

        $info['folder'] = basename($dir);
        $themes[] = $info;
    }
    return $themes;
}
```

- [ ] **Step 3: Remove parent theme fallback from view()**

The old `view()` had parent theme fallback logic (lines 68-76). The new `view()` written in Step 1 already does NOT include this. Verify no remnants of parent/child logic remain.

Also remove the `syncWidgetAreas` parent lookup if it references `'parent'` key — check the method and remove any parent-related logic.


---

### Task 12: Page Caching

**Files:**
- Modify: `app/Services/ThemeService.php`

- [ ] **Step 1: Add page-level caching to ThemeService::view()**

In the `view()` method, wrap the rendering with CI4's cache:

```php
public function view(string $name, array $pageData = []): string
{
    $theme = $this->getActive();
    if (! $theme) {
        return '<p>No active theme.</p>';
    }

    $path = THEMES_PATH . $theme->folder . '/views/' . $name . '.tpl';
    if (! is_file($path)) {
        return '<p>Theme view not found: ' . esc($name) . '</p>';
    }

    // Check cache (skip for logged-in users — they may see different content)
    $cacheTtl = (int) (setting('App.pageCacheTtl') ?? 120);
    $useCache  = $cacheTtl > 0 && ! $this->isLoggedIn();

    if ($useCache) {
        $cacheKey = $this->buildCacheKey($name, $pageData);
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }

    // Pre-render pager to HTML if present
    if (isset($pageData['pager'])) {
        $pageData['pager_links'] = $pageData['pager']->links();
        unset($pageData['pager']);
    }

    $data = array_merge($this->buildCommonData(), $pageData);
    $basePath = THEMES_PATH . $theme->folder . '/views/';
    $html = $this->getEngine()->render($path, $data, $basePath);

    if ($useCache) {
        cache()->save($cacheKey, $html, $cacheTtl);
    }

    return $html;
}

private function isLoggedIn(): bool
{
    try {
        return auth()->loggedIn();
    } catch (\Throwable $e) {
        return false;
    }
}

private function buildCacheKey(string $viewName, array $pageData): string
{
    $theme = $this->getActive();
    $uri = service('request')->getUri()->getPath();
    $locale = service('request')->getLocale();

    return 'page_cache_' . md5(
        ($theme->folder ?? 'none') . '|' . $viewName . '|' . $uri . '|' . $locale
    );
}
```

- [ ] **Step 2: Create migration for App.pageCacheTtl setting**

Create migration via `php spark make:migration AddPageCacheTtlSetting`:

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPageCacheTtlSetting extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('settings')->insert([
            'class'      => 'App',
            'key'        => 'pageCacheTtl',
            'value'      => '120',
            'type'       => 'integer',
            'context'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $this->db->table('settings')
            ->where('class', 'App')
            ->where('key', 'pageCacheTtl')
            ->delete();
    }
}
```

- [ ] **Step 3: Add to seeder**

In `app/Database/Seeds/SettingsSeeder.php`, add:

```php
service('settings')->set('App.pageCacheTtl', 120);
```

- [ ] **Step 4: Add Page Cache TTL field to Admin Settings → General tab**

In the General settings admin view, add a number input for Page Cache TTL (seconds). Value of `0` disables caching.

In the Settings controller's `saveGeneral()` method, save `App.pageCacheTtl` from the form input.


---

### Task 13: Theme Validation in sync() + Admin UI

**Files:**
- Modify: `app/Services/ThemeService.php`
- Modify: `app/Views/admin/themes/index.php`

- [ ] **Step 1: Add validation scanning to sync()**

Add a new method to ThemeService:

```php
/**
 * Scan all files in a theme directory for PHP tags.
 * Returns true if the theme is clean, false if PHP is found.
 */
public function validateTheme(string $folder): bool
{
    $dir = THEMES_PATH . $folder;
    if (! is_dir($dir)) {
        return false;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        // Only scan text-like files, skip images/fonts/binaries
        $ext = strtolower($file->getExtension());
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'zip'], true)) {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if (str_contains($content, '<?php') || str_contains($content, '<?=') || str_contains($content, '<%')) {
            return false;
        }
    }

    return true;
}
```

Update `sync()` to call validation and store results. Add a `validation_status` tracking array (or use the DB — add a column if needed). For simplicity, use a runtime property:

```php
private array $validationResults = [];

public function sync(): void
{
    $model = new ThemeModel();
    $now = date('Y-m-d H:i:s');

    foreach ($this->discover() as $info) {
        $folder = $info['folder'];
        if (! $model->where('folder', $folder)->first()) {
            $model->insert([
                'name'         => $info['name'] ?? $folder,
                'folder'       => $folder,
                'version'      => $info['version'] ?? 'unknown',
                'is_active'    => 0,
                'installed_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // Validate — check for PHP tags
        $this->validationResults[$folder] = $this->validateTheme($folder);

        // Publish assets for all discovered themes (screenshots, etc.)
        $this->publishAssets($folder);
    }
}

/**
 * Get validation results (populated after sync() runs).
 *
 * @return array<string, bool> folder => isValid
 */
public function getValidationResults(): array
{
    return $this->validationResults;
}
```

- [ ] **Step 2: Update admin themes view — show screenshot + validation warning**

Replace `app/Views/admin/themes/index.php`:

```php
<?php $layout = 'admin/layouts/main'; ob_start(); ?>

<h1 class="h3 mb-4 text-gray-800"><?= lang('Admin.themesTitle') ?></h1>

<div class="row">
<?php foreach ($themes as $theme): ?>
    <?php
    $folder = $theme->folder;
    $jsonFile = THEMES_PATH . $folder . '/theme_info.json';
    $phpFile  = THEMES_PATH . $folder . '/theme_info.php';

    if (is_file($jsonFile)) {
        $info = json_decode(file_get_contents($jsonFile), true) ?? [];
    } elseif (is_file($phpFile)) {
        $info = require $phpFile;
    } else {
        $info = [];
    }

    $isValid = $validation[$folder] ?? true;
    $screenshotUrl = '';
    if (! empty($info['screenshot'])) {
        $screenshotUrl = base_url('themes/' . $folder . '/' . $info['screenshot']);
    }
    ?>
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100 <?= $theme->is_active ? 'border-primary' : '' ?>">
            <?php if ($screenshotUrl): ?>
                <img src="<?= esc($screenshotUrl) ?>" class="card-img-top card-thumb-lg obj-cover" alt="<?= esc($theme->name) ?>">
            <?php else: ?>
                <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center card-thumb-lg">
                    <i class="fas fa-palette fa-3x text-white-50"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title">
                        <?= esc($theme->name) ?>
                        <?php if (!empty($info['premium'])): ?>
                            <span class="badge badge-warning text-dark small"><?= lang('Admin.premium') ?></span>
                        <?php endif; ?>
                    </h5>
                    <?php if ($theme->is_active): ?>
                        <span class="badge badge-primary"><?= lang('Admin.themeActive') ?></span>
                    <?php endif; ?>
                </div>
                <p class="card-text text-muted small"><?= esc($info['description'] ?? '') ?></p>
                <p class="text-muted small"><?= lang('Admin.themeBy') ?> <?= esc($info['author'] ?? 'Unknown') ?> &middot; v<?= esc($theme->version ?? '?') ?></p>
                <?php if (! $isValid): ?>
                    <div class="alert alert-danger small py-1 px-2 mb-0">
                        <i class="fas fa-exclamation-triangle"></i> <?= lang('Admin.themeValidationFailed') ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <?php if (! $theme->is_active && $isValid): ?>
                <form method="POST" action="<?= base_url('admin/themes/' . $theme->id . '/activate') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-primary"><?= lang('Admin.themeActivate') ?></button>
                </form>
                <?php endif; ?>
                <?php if (!empty($info['options'])): ?>
                <a href="<?= base_url('admin/themes/' . $theme->id . '/options') ?>" class="btn btn-sm btn-outline-secondary"><?= lang('Admin.themeOptionsBtn') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($themes)): ?>
    <div class="col-12"><p class="text-muted text-center py-4"><?= lang('Admin.noThemesInstalled') ?></p></div>
<?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?= view($layout, array_merge(get_defined_vars(), ['content' => $content])) ?>
```

- [ ] **Step 3: Update the Admin Themes controller to pass validation data**

In `app/Controllers/Admin/Themes.php`, in the `index()` method, after calling `$this->themeService->sync()`, add:

```php
$this->data['validation'] = $this->themeService->getValidationResults();
```

- [ ] **Step 4: Enforce validation on the activate route**

In `app/Controllers/Admin/Themes.php`, in the `activate()` method, before activating the theme, validate it:

```php
$theme = (new ThemeModel())->find($id);
if (! $theme) {
    return redirect()->to('admin/themes')->with('error', 'Theme not found.');
}

if (! $this->themeService->validateTheme($theme->folder)) {
    return redirect()->to('admin/themes')->with('error', lang('Admin.themeValidationFailed'));
}
```


---

### Task 28: Strip BaseController + Update Public Controllers

**Files:**
- Modify: `app/Controllers/BaseController.php`
- Modify: `app/Controllers/Blog.php`
- Modify: `app/Controllers/Pages.php`
- Modify: `app/Controllers/Contact.php`
- Modify: `app/Controllers/Search.php`

- [ ] **Step 1: Strip common data building from BaseController**

In `BaseController::initController()`, remove the nav, social links, and plugin loading. Keep only:
- `$this->themeService = new ThemeService()`
- Locale detection (still needed for CI4's locale routing)

Remove from `$this->data`:
- `theme`, `site_name`, `site_tagline`, `settings`, `primary_nav`, `footer_nav`, `social_links`, `plugin_menu_items`

Keep `$this->data` as an empty array — controllers will put only page-specific data in it.

Rewrite `buildLangSwitcher()` to pass data to ThemeService instead of `$this->data`:

```php
protected function buildLangSwitcher(): void
{
    try {
        $cache     = service('cache');
        $languages = $cache->get('active_languages_objects');

        if ($languages === null) {
            $model     = new LanguageModel();
            $languages = $model->getActive();
            $cache->save('active_languages_objects', $languages, 3600);
        }

        if (empty($languages)) {
            return;
        }

        $currentUri    = '/' . ltrim($this->request->getUri()->getPath(), '/');
        $currentLocale = $this->request->getLocale() ?: config('App')->defaultLocale;

        $switcher = new LanguageSwitcher($languages, $currentUri, $currentLocale);
        $this->themeService->setLangSwitcher($switcher->build());
    } catch (\Throwable $e) {
        log_message('error', 'buildLangSwitcher failed: ' . $e->getMessage());
    }
}
```

- [ ] **Step 2: Update Blog controller**

Each method changes from `array_merge($this->data, [...])` to just passing the page-specific array. For example, `index()`:

```php
public function index(): string
{
    $this->buildLangSwitcher();

    $frontPageType = setting('App.frontPageType') ?? 'blog';
    if ($frontPageType === 'page') {
        $pageId = setting('App.frontPageId');
        if ($pageId) {
            $page = (new \App\Models\PageModel())->find($pageId);
            if ($page && $page->status === 'published') {
                return $this->themeService->view('page', [
                    'page' => $page,
                    'seo'  => $this->seoService->getMeta($page),
                ]);
            }
        }
    }

    $perPage = (int) (setting('App.postsPerPage') ?? 10);
    $posts = $this->postModel->published()
        ->orderBy('published_at', 'DESC')
        ->paginate($perPage, 'default');

    return $this->themeService->view('home', [
        'posts' => $posts,
        'pager' => $this->postModel->pager,
        'seo'   => $this->seoService->getDefaultMeta(),
    ]);
}
```

Apply the same pattern to all Blog methods: `post()`, `preview()`, `category()`, `tag()`, `archive()`.

In `post()` (and `preview()`), calculate reading time and pass it as page data:

```php
$wordCount = str_word_count(strip_tags($post->content ?? ''));
$readingTime = max(1, (int) ceil($wordCount / 200));
```

Pass `'reading_time' => $readingTime` alongside the other page-specific data.

`post()` and `preview()` must also continue passing `'author_profile' => $authorProfile` — the theme's `partials/author-card.tpl` depends on it.

- [ ] **Step 3: Update Pages, Contact, Search controllers**

Same pattern — pass only page-specific data, remove `array_merge($this->data, ...)`.

---

### Task 29: Remove Obsolete Code + Cleanup

**Files:**
- Modify: `app/Helpers/cms_helper.php`
- Remove: `public/themes/` symlinks
- Remove: `public/writable/` symlink directory

- [ ] **Step 1: Remove theme_view() and theme_layout() from cms_helper.php**

Delete the `theme_view()` function (lines 118-133) and `theme_layout()` function (lines 36-53). These are replaced by the template engine's `{% include %}` and `{% extends %}` directives.

Keep: `active_theme()`, `widget_area()`, `theme_url()`, `site_name()`, `site_tagline()`, `post_url()`, `category_url()`, `tag_url()`, `render_content()`, `excerpt()`, `slug_from_title()`.

- [ ] **Step 2: Remove old symlinks**

```bash
# Remove theme symlinks (they're now real directories from publishAssets)
find public/themes/ -maxdepth 1 -type l -delete 2>/dev/null

# Remove the writable symlink
rm -rf public/writable
```

- [ ] **Step 3: Remove +FollowSymlinks from .htaccess if it's the only option**

In `public/.htaccess`, the `Options +FollowSymlinks` line is no longer required. However, removing it could break other setups, so leave it — it's harmless when no symlinks exist.

- [ ] **Step 4: Remove old app/Views/partials/paywall.php**

```bash
rm app/Views/partials/paywall.php
```

---

### Task 15: BaseWidget + WidgetService — Engine Integration + JSON

**Files:**
- Modify: `app/Libraries/BaseWidget.php`
- Modify: `app/Services/WidgetService.php`

- [ ] **Step 1: Update BaseWidget to use template engine**

Replace the `view()` method in `app/Libraries/BaseWidget.php`:

```php
protected function view(string $name, array $data = []): string
{
    $folder = $this->getFolder();
    $tplPath = WIDGETS_PATH . $folder . '/views/' . $name . '.tpl';
    $phpPath = WIDGETS_PATH . $folder . '/views/' . $name . '.php';

    // Prefer .tpl, fall back to .php during transition
    if (is_file($tplPath)) {
        $engine = new \App\Libraries\TemplateEngine\Engine();
        $basePath = WIDGETS_PATH . $folder . '/views/';
        return $engine->render($tplPath, $data, $basePath);
    }

    // Legacy PHP fallback
    if (is_file($phpPath)) {
        extract($data);
        ob_start();
        include $phpPath;
        return ob_get_clean();
    }

    return '';
}
```

- [ ] **Step 2: Update WidgetService::discover() to read JSON**

In `app/Services/WidgetService.php`, update `discover()`:

```php
public function discover(): array
{
    $widgets = [];
    foreach (glob(WIDGETS_PATH . '*', GLOB_ONLYDIR) as $dir) {
        $jsonFile = $dir . '/widget_info.json';
        $phpFile  = $dir . '/widget_info.php';

        if (is_file($jsonFile)) {
            $info = json_decode(file_get_contents($jsonFile), true);
        } elseif (is_file($phpFile)) {
            $info = require $phpFile;
        } else {
            continue;
        }

        if (! is_array($info)) {
            continue;
        }

        $info['folder'] = basename($dir);
        $widgets[] = $info;
    }
    return $widgets;
}
```

---

- [ ] **Verify all Phase 1-3 code against the plan — check twice**

Read every file created or modified in Phases 1-3. Verify method signatures, variable names, and behavior match the plan. If code doesn't match, fix the code to match the plan. If you can't reconcile, STOP.

- [ ] **Step 6: Commit Phase 1-3**

```bash
git add -A
git commit -m "feat: template engine, asset pipeline, ThemeService rewrite, BaseWidget engine integration, controller updates, cleanup"
```

---

## Phase 3b: WidgetBuilder.md — Write Before Widget Conversion

### Task 14b: Create WidgetBuilder.md

**Files:**
- Create: `WidgetBuilder.md` (repo root)

**PREREQUISITE:** Phase 3 must be complete (includes template engine, infrastructure, BaseWidget engine integration, controller updates, cleanup).

**BEFORE WRITING:** Read the actual built code and verify it matches the plan. If code doesn't match, fix the code to match the plan. If you can't reconcile, STOP — we figure it out.

**Verify against real code:**
- `app/Libraries/TemplateEngine/Engine.php` — `render()` signature
- `app/Libraries/TemplateEngine/FilterRegistry.php` — all filters present
- `app/Libraries/TemplateEngine/TagRegistry.php` — all tag functions present
- `app/Libraries/BaseWidget.php` — `view()` method uses template engine

- [ ] **Step 1: Verify code matches plan (fix if needed), then write WidgetBuilder.md**

Write the complete `WidgetBuilder.md` by reading the actual built code. The document must be complete enough that an implementor can build any widget from scratch by reading only this file. No placeholders. No "see X for details."

**Sections to include** (source the content from the real code, not from this plan):

1. **Widget Directory Structure** — read an existing widget directory
2. **widget_info.json Format** — read existing converted JSON manifests for schema and example
3. **PHP Widget Class** — read `BaseWidget.php` for the base class API, read an existing widget for the convention (`use` statement, `$folder`, `getInfo()` with `__DIR__`, `buildOutput()`, `$this->view()`). Document the full lifecycle.
4. **`.tpl` View Syntax Reference** — read `Lexer.php` and `Parser.php` for all supported constructs
5. **CSS Class Variables — The `cls_` Pattern** — read all converted widget `.tpl` files, compile the complete `cls_` vocabulary with defaults
6. **Available Tag Functions** — read `TagRegistry.php`, document every function with signature and usage
7. **Available Filters** — read `FilterRegistry.php`, document every filter with signature and usage
8. **JavaScript Guidelines** — read the `table_of_contents` widget `.tpl` for the JS + `cls_` pattern
9. **User-Visible Strings** — document the `{% lang %}` requirement, read `app/Language/en/Blog.php` for available keys
10. **Complete Example** — write a full widget from scratch demonstrating all patterns


---

## Phase 4: Widget System

### Architecture Change

The widget system is redesigned to eliminate PHP from widget directories entirely. Widgets become **JSON + .tpl only** — no PHP classes, no PHP view files. Data fetching moves to a whitelisted `WidgetDataService` that calls model methods declared in `widget_info.json`. Admin forms are auto-generated from the JSON manifest. `BaseWidget` is deleted; `WidgetService` handles the full render flow.

**Widget directory structure (new):**
```
widgets/RecentPosts/
    widget_info.json        # Manifest: admin form + data providers + template
    views/
        widget.tpl          # Frontend template (rendered by engine)
```

**widget_info.json format (new):**
```json
{
    "name": "Widget Name",
    "description": "What this widget does.",
    "version": "1.0.0",
    "admin": {
        "notice": "",
        "options": {
            "option_key": {
                "type": "text|number|checkbox|textarea|select",
                "label": "Human Label",
                "default": "default_value",
                "choices": {}
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "template_var": {
                "provider": "ModelName.methodName",
                "params": {
                    "param_name": "option_key"
                }
            }
        }
    }
}
```

- `admin` — drives the admin configure form. `options` auto-generates form fields. `notice` shows an info message.
- `output` — drives the public render. `template` is the .tpl file. `providers` declares data needs.
- `providers` maps template variable names to whitelisted Model.method calls. `params` values reference option keys — saved option values are substituted at runtime.
- Widgets with no data omit `providers` or set it to `{}`.
- Widgets with no admin options set `options` to `{}`.

**Render flow:**
1. Widget instance requested for an area
2. WidgetService reads `widget_info.json`
3. Reads defaults from `admin.options`, loads saved values from `widget_instances.options_json` in the DB, merges saved over defaults
4. Reads `output.providers`, passes to `WidgetDataService::resolve()` with merged options
5. WidgetDataService validates each provider against whitelist, calls Model.method with resolved params, returns data
6. WidgetService combines merged options + provider results into one data array
7. Engine renders `output.template` with that data
8. HTML returned and concatenated

---

### Task 16: Create WidgetDataService

**Files:**
- Create: `app/Services/WidgetDataService.php`

- [ ] **Step 1: Write WidgetDataService**

```php
<?php

namespace App\Services;

class WidgetDataService
{
    /**
     * Whitelisted Model.method providers.
     * Only these can be called from widget_info.json.
     */
    private const PROVIDERS = [
        'CategoryModel.getWithPostCount',
        'PostModel.getRecent',
        'TagModel.getWithPostCount',
        'SocialModel.getActive',
        'PostModel.getArchiveList',
        'CommentModel.getRecentApproved',
        'PostModel.getRelated',
        'AuthorProfileModel.getForCurrentPost',
    ];

    /**
     * Resolve all data providers for a widget.
     *
     * @param array $providers  The output.providers section from widget_info.json
     * @param array $options    Merged widget options (defaults + saved)
     * @return array            Template variable name => data
     */
    public function resolve(array $providers, array $options): array
    {
        $results = [];

        foreach ($providers as $varName => $config) {
            $providerStr = $config['provider'] ?? '';
            $params = $this->resolveParams($config['params'] ?? [], $options);

            if (! in_array($providerStr, self::PROVIDERS, true)) {
                $results[$varName] = null;
                continue;
            }

            [$modelName, $method] = explode('.', $providerStr, 2);
            $fqn = 'App\\Models\\' . $modelName;

            if (! class_exists($fqn) || ! method_exists($fqn, $method)) {
                $results[$varName] = null;
                continue;
            }

            $model = new $fqn();
            $results[$varName] = $model->$method(...array_values($params));
        }

        return $results;
    }

    /**
     * Substitute param definitions with actual option values.
     * Each param value is an option key — look up its saved value.
     */
    private function resolveParams(array $paramDefs, array $options): array
    {
        $resolved = [];
        foreach ($paramDefs as $paramName => $optionKey) {
            $resolved[$paramName] = $options[$optionKey] ?? null;
        }
        return $resolved;
    }
}
```

---

### Task 17: Add Model Methods for Widget Data Providers

**Files:**
- Modify: `app/Models/PostModel.php`
- Modify: `app/Models/CommentModel.php`
- Modify: `app/Models/SocialModel.php`
- Modify: `app/Models/AuthorProfileModel.php`

Some model methods already exist (`CategoryModel::getWithPostCount`, `TagModel::getWithPostCount`). The following need to be created. Each encapsulates what was previously raw query logic inside widget PHP classes.

- [ ] **Step 1: PostModel::getRecent(int $limit): array**

```php
public function getRecent(int $limit = 5): array
{
    return $this->published()
        ->orderBy('published_at', 'DESC')
        ->limit($limit)
        ->findAll();
}
```

- [ ] **Step 2: PostModel::getArchiveList(string $format = 'monthly'): array**

Move the raw query from `ArchiveListWidget::buildOutput()` into a model method. Returns array of objects with `url`, `label`, `count` properties.

- [ ] **Step 3: PostModel::getRelated(int $postId, int $limit = 4): array**

Move the logic from `RelatedPostsWidget::findRelated()` into the model. Accepts a post ID, finds related posts by shared categories and tags, returns scored results.

- [ ] **Step 4: CommentModel::getRecentApproved(int $limit = 5): array**

```php
public function getRecentApproved(int $limit = 5): array
{
    return $this->db->table('comments c')
        ->select('c.author_name, c.content, c.created_at, p.slug as post_slug, p.title as post_title')
        ->join('posts p', 'p.id = c.post_id')
        ->where('c.status', 'approved')
        ->orderBy('c.created_at', 'DESC')
        ->limit($limit)
        ->get()->getResultObject();
}
```

- [ ] **Step 5: SocialModel::getActive(): array**

```php
public function getActive(): array
{
    return $this->where('is_active', 1)
        ->orderBy('sort_order')
        ->findAll();
}
```

- [ ] **Step 6: AuthorProfileModel::getForCurrentPost(): ?object**

Move the URL inspection and multi-step lookup logic from `AuthorBioWidget::buildOutput()` into the model. Checks if the current request is a post page, finds the post author, returns the profile with email/username attached. Returns null if not on a post page or no profile exists.

---

### Task 18: Rework WidgetService + Delete BaseWidget

**Files:**
- Modify: `app/Services/WidgetService.php`
- Delete: `app/Libraries/BaseWidget.php`

- [ ] **Step 1: Rewrite WidgetService**

Replace the entire file. Key changes:
- `discover()` — reads `widget_info.json` only (no PHP fallback). Reads `name`, `description`, `version` from top-level JSON keys.
- `sync()` — stays as-is.
- `renderArea(string $slug): string` — new full render flow (see architecture section above).
- `renderAdminForm(string $folder, array $savedOptions): string` — reads `admin.options` from JSON, auto-generates HTML form fields. Reads `admin.notice` if present and prepends it.
- `getInstance()` — deleted.
- `folderToClass()` — deleted.
- Private helper: `getDefaults(array $manifest): array` — reads defaults from `admin.options`.
- Private helper: `readManifest(string $folder): ?array` — reads and caches `widget_info.json`.

```php
<?php

namespace App\Services;

use App\Libraries\TemplateEngine\Engine;

class WidgetService
{
    private array $manifestCache = [];

    public function discover(): array
    {
        $widgets = [];
        foreach (glob(WIDGETS_PATH . '*', GLOB_ONLYDIR) as $dir) {
            $jsonFile = $dir . '/widget_info.json';
            if (! is_file($jsonFile)) {
                continue;
            }

            $info = json_decode(file_get_contents($jsonFile), true);
            if (! is_array($info)) {
                continue;
            }

            $info['folder'] = basename($dir);
            $widgets[] = $info;
        }
        return $widgets;
    }

    public function sync(): void
    {
        $db = db_connect();
        foreach ($this->discover() as $info) {
            $exists = $db->table('widgets')->where('folder', $info['folder'])->countAllResults();
            if (! $exists) {
                $db->table('widgets')->insert([
                    'name'        => $info['name']        ?? $info['folder'],
                    'folder'      => $info['folder'],
                    'description' => $info['description'] ?? '',
                    'version'     => $info['version']     ?? '1.0.0',
                    'is_active'   => 1,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function renderArea(string $slug): string
    {
        $db = db_connect();
        $instances = $db->table('widget_instances wi')
            ->select('wi.*, w.folder, wa.slug AS area_slug')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wa.slug', $slug)
            ->where('w.is_active', 1)
            ->orderBy('wi.sort_order', 'ASC')
            ->get()->getResultObject();

        $html = '';
        foreach ($instances as $instance) {
            $html .= $this->renderWidget($instance->folder, $instance->options_json);
        }
        return $html;
    }

    public function renderWidget(string $folder, ?string $optionsJson): string
    {
        $manifest = $this->readManifest($folder);
        if (! $manifest) {
            return '';
        }

        // Merge defaults with saved options
        $defaults = $this->getDefaults($manifest);
        $saved = $optionsJson ? json_decode($optionsJson, true) : [];
        $merged = array_merge($defaults, $saved);

        // Resolve data providers
        $providerData = [];
        $providers = $manifest['output']['providers'] ?? [];
        if ($providers) {
            $providerData = (new WidgetDataService())->resolve($providers, $merged);
        }

        // Render template
        $template = $manifest['output']['template'] ?? 'widget.tpl';
        $tplPath = WIDGETS_PATH . $folder . '/views/' . $template;
        $basePath = WIDGETS_PATH . $folder . '/views/';
        $engine = new Engine();

        return $engine->render($tplPath, array_merge($merged, $providerData), $basePath);
    }

    public function renderAdminForm(string $folder, array $savedOptions = []): string
    {
        $manifest = $this->readManifest($folder);
        if (! $manifest) {
            return '';
        }

        $admin = $manifest['admin'] ?? [];
        $options = $admin['options'] ?? [];
        $notice = $admin['notice'] ?? '';
        $defaults = $this->getDefaults($manifest);
        $merged = array_merge($defaults, $savedOptions);

        $html = '';

        // Render notice if present
        if ($notice !== '') {
            $html .= '<div class="alert alert-info mb-3">'
                   . '<i class="fas fa-info-circle mr-2"></i>'
                   . esc($notice)
                   . '</div>';
        }

        // Auto-generate form fields from options
        foreach ($options as $key => $cfg) {
            $type    = $cfg['type'] ?? 'text';
            $label   = $cfg['label'] ?? $key;
            $value   = $merged[$key] ?? '';
            $fieldId = 'widget-opt-' . $key;

            $html .= match ($type) {
                'checkbox' => $this->renderCheckbox($key, $label, $value, $fieldId),
                'textarea' => $this->renderTextarea($key, $label, $value, $fieldId),
                'number'   => $this->renderInput($key, $label, $value, $fieldId, 'number'),
                'select'   => $this->renderSelect($key, $label, $value, $fieldId, $cfg['choices'] ?? []),
                default    => $this->renderInput($key, $label, $value, $fieldId, 'text'),
            };
        }

        return $html;
    }

    private function readManifest(string $folder): ?array
    {
        if (isset($this->manifestCache[$folder])) {
            return $this->manifestCache[$folder];
        }

        $path = WIDGETS_PATH . $folder . '/widget_info.json';
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data)) {
            return null;
        }

        $this->manifestCache[$folder] = $data;
        return $data;
    }

    private function getDefaults(array $manifest): array
    {
        $defaults = [];
        foreach ($manifest['admin']['options'] ?? [] as $key => $cfg) {
            $defaults[$key] = $cfg['default'] ?? '';
        }
        return $defaults;
    }

    private function renderInput(string $key, string $label, string $value, string $id, string $type): string
    {
        $escaped = esc($value);
        return '<div class="mb-3">'
             . '<label class="form-label" for="' . $id . '">' . esc($label) . '</label>'
             . '<input type="' . $type . '" name="options[' . $key . ']" id="' . $id . '" class="form-control" value="' . $escaped . '">'
             . '</div>';
    }

    private function renderTextarea(string $key, string $label, string $value, string $id): string
    {
        return '<div class="mb-3">'
             . '<label class="form-label" for="' . $id . '">' . esc($label) . '</label>'
             . '<textarea name="options[' . $key . ']" id="' . $id . '" class="form-control" rows="6">' . esc($value) . '</textarea>'
             . '</div>';
    }

    private function renderCheckbox(string $key, string $label, string $value, string $id): string
    {
        $checked = ! empty($value) ? ' checked' : '';
        return '<div class="mb-3 form-check">'
             . '<input type="checkbox" name="options[' . $key . ']" id="' . $id . '" class="form-check-input" value="1"' . $checked . '>'
             . '<label class="form-check-label" for="' . $id . '">' . esc($label) . '</label>'
             . '</div>';
    }

    private function renderSelect(string $key, string $label, string $value, string $id, array $choices): string
    {
        $html = '<div class="mb-3">'
              . '<label class="form-label" for="' . $id . '">' . esc($label) . '</label>'
              . '<select name="options[' . $key . ']" id="' . $id . '" class="form-control">';
        foreach ($choices as $val => $text) {
            $selected = ($value === (string) $val) ? ' selected' : '';
            $html .= '<option value="' . esc($val) . '"' . $selected . '>' . esc($text) . '</option>';
        }
        $html .= '</select></div>';
        return $html;
    }
}
```

- [ ] **Step 2: Update Admin Widgets controller**

In `app/Controllers/Admin/Widgets.php`, update `configure()` to call `WidgetService::renderAdminForm()` instead of `$widget->renderAdminForm()`:

```php
$form = (new WidgetService())->renderAdminForm($instance->folder, $options);
```

- [ ] **Step 3: Update admin widgets areas view**

In `app/Views/admin/widgets/areas.php`, replace the `widget_info.php` require with JSON read:

```php
$wJsonFile = WIDGETS_PATH . $w->folder . '/widget_info.json';
$wInfo = is_file($wJsonFile) ? json_decode(file_get_contents($wJsonFile), true) : [];
```

- [ ] **Step 4: Delete BaseWidget**

```bash
rm app/Libraries/BaseWidget.php
```

Remove the `use App\Libraries\BaseWidget;` import from `WidgetService.php` (already removed in Step 1).

---

### Task 19: Convert List Widgets

**Folder renames + full conversion.** For each widget: rename folder to PascalCase, create `widget_info.json` (new format with `admin`/`output` sections), create `views/widget.tpl`, delete old PHP files (`*Widget.php`, `widget_info.php`, `views/widget.php`, `views/admin_form.php`).

- [ ] **Step 1: CategoriesList**

Rename `widgets/categories_list/` → `widgets/CategoriesList/`

`widgets/CategoriesList/widget_info.json`:
```json
{
    "name": "Categories List",
    "description": "Displays a list of post categories with optional post counts.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Categories"
            },
            "show_count": {
                "type": "checkbox",
                "label": "Show Post Count",
                "default": "1"
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "categories": {
                "provider": "CategoryModel.getWithPostCount",
                "params": {}
            }
        }
    }
}
```

`widgets/CategoriesList/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-categories') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for cat in categories %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <a href="{% category_url cat.slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ cat.name }}</a>
            {% if show_count %}
                <span class="{{ cls_badge | default('widget-badge') }}">{{ cat.post_count }}</span>
            {% endif %}
        </li>
        {% endfor %}
        {% if not categories %}
            <li class="{{ cls_list_item | default('widget-list-item') }} {{ cls_empty | default('widget-empty') }}">{% lang 'Blog.noPostsYet' %}</li>
        {% endif %}
    </ul>
</div>
```

Remove old files:
```bash
rm widgets/CategoriesList/CategoriesListWidget.php widgets/CategoriesList/widget_info.php widgets/CategoriesList/views/widget.php widgets/CategoriesList/views/admin_form.php
```

- [ ] **Step 2: TagCloud**

Rename `widgets/tag_cloud/` → `widgets/TagCloud/`

`widgets/TagCloud/widget_info.json`:
```json
{
    "name": "Tag Cloud",
    "description": "Displays tags as a cloud of links.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Tags"
            },
            "max_tags": {
                "type": "number",
                "label": "Maximum Tags",
                "default": "30"
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "tags": {
                "provider": "TagModel.getWithPostCount",
                "params": {
                    "limit": "max_tags"
                }
            }
        }
    }
}
```

`widgets/TagCloud/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-tag-cloud') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_tags | default('widget-tags') }}">
        {% for tag in tags %}
            <a href="{% tag_url tag.slug %}" class="{{ cls_tag | default('widget-tag') }}">{{ tag.name }}</a>
        {% endfor %}
    </div>
</div>
```

Remove old files:
```bash
rm widgets/TagCloud/TagCloudWidget.php widgets/TagCloud/widget_info.php widgets/TagCloud/views/widget.php widgets/TagCloud/views/admin_form.php
```

- [ ] **Step 3: ArchiveList**

Rename `widgets/archive_list/` → `widgets/ArchiveList/`

`widgets/ArchiveList/widget_info.json`:
```json
{
    "name": "Archive List",
    "description": "Displays a list of monthly or yearly archive links.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Archives"
            },
            "format": {
                "type": "select",
                "label": "Format",
                "default": "monthly",
                "choices": {
                    "monthly": "Monthly",
                    "yearly": "Yearly"
                }
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "rows": {
                "provider": "PostModel.getArchiveList",
                "params": {
                    "format": "format"
                }
            }
        }
    }
}
```

`widgets/ArchiveList/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-archive') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for row in rows %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <a href="{% base_url row.url %}" class="{{ cls_link | default('widget-list-link') }}">{{ row.label }}</a>
            {% if row.count %}
                <span class="{{ cls_badge | default('widget-badge') }}">{{ row.count }}</span>
            {% endif %}
        </li>
        {% endfor %}
    </ul>
</div>
```

Remove old files:
```bash
rm widgets/ArchiveList/ArchiveListWidget.php widgets/ArchiveList/widget_info.php widgets/ArchiveList/views/widget.php widgets/ArchiveList/views/admin_form.php
```

- [ ] **Step 4: RecentPosts**

Rename `widgets/recent_posts/` → `widgets/RecentPosts/`

`widgets/RecentPosts/widget_info.json`:
```json
{
    "name": "Recent Posts",
    "description": "Displays a list of the most recent published posts.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Recent Posts"
            },
            "count": {
                "type": "number",
                "label": "Number of Posts",
                "default": "5"
            },
            "show_date": {
                "type": "checkbox",
                "label": "Show Date",
                "default": "1"
            },
            "show_excerpt": {
                "type": "checkbox",
                "label": "Show Excerpt",
                "default": "0"
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "posts": {
                "provider": "PostModel.getRecent",
                "params": {
                    "limit": "count"
                }
            }
        }
    }
}
```

`widgets/RecentPosts/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-recent-posts') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    {% if posts %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for post in posts %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <a href="{% post_url post.slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ post.title }}</a>
            {% if show_date and post.published_at %}
                <span class="{{ cls_meta | default('widget-meta') }}">{{ post.published_at | date('M j, Y') }}</span>
            {% endif %}
            {% if show_excerpt and post.excerpt %}
                <p class="{{ cls_meta | default('widget-meta') }}">{{ post.excerpt | excerpt(80) }}</p>
            {% endif %}
        </li>
        {% endfor %}
    </ul>
    {% else %}
        <p class="{{ cls_empty | default('widget-empty') }}">{% lang 'Blog.noPostsYet' %}</p>
    {% endif %}
</div>
```

Remove old files:
```bash
rm widgets/RecentPosts/RecentPostsWidget.php widgets/RecentPosts/widget_info.php widgets/RecentPosts/views/widget.php widgets/RecentPosts/views/admin_form.php
```

- [ ] **Step 5: RecentComments**

Rename `widgets/recent_comments/` → `widgets/RecentComments/`

`widgets/RecentComments/widget_info.json`:
```json
{
    "name": "Recent Comments",
    "description": "Displays the most recent approved comments.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Recent Comments"
            },
            "count": {
                "type": "number",
                "label": "Number of Comments",
                "default": "5"
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "comments": {
                "provider": "CommentModel.getRecentApproved",
                "params": {
                    "limit": "count"
                }
            }
        }
    }
}
```

`widgets/RecentComments/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-recent-comments') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    {% if comments %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for c in comments %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <span class="{{ cls_meta | default('widget-meta') }}">{{ c.author_name }}</span>
            <a href="{% post_url c.post_slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ c.content | excerpt(60) }}</a>
        </li>
        {% endfor %}
    </ul>
    {% endif %}
</div>
```

Remove old files:
```bash
rm widgets/RecentComments/RecentCommentsWidget.php widgets/RecentComments/widget_info.php widgets/RecentComments/views/widget.php widgets/RecentComments/views/admin_form.php
```


---

### Task 20: Convert Content/Form Widgets

- [ ] **Step 1: TextBlock**

Rename `widgets/text_block/` → `widgets/TextBlock/`

`widgets/TextBlock/widget_info.json`:
```json
{
    "name": "Text Block",
    "description": "Displays custom HTML or text content.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": ""
            },
            "content": {
                "type": "textarea",
                "label": "Content",
                "default": ""
            }
        }
    },
    "output": {
        "template": "widget.tpl"
    }
}
```

`widgets/TextBlock/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-text-block') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_content | default('widget-content') }}">
        {! content !}
    </div>
</div>
```

Remove old files: `rm widgets/TextBlock/TextBlockWidget.php widgets/TextBlock/widget_info.php widgets/TextBlock/views/widget.php widgets/TextBlock/views/admin_form.php`

- [ ] **Step 2: AdUnit**

Rename `widgets/ad_unit/` → `widgets/AdUnit/`

`widgets/AdUnit/widget_info.json`:
```json
{
    "name": "Ad Unit",
    "description": "Displays custom ad code or HTML embed.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": ""
            },
            "code": {
                "type": "textarea",
                "label": "Ad Code",
                "default": ""
            }
        }
    },
    "output": {
        "template": "widget.tpl"
    }
}
```

`widgets/AdUnit/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-ad-unit') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_content | default('widget-content') }}">
        {! code !}
    </div>
</div>
```

Remove old files: `rm widgets/AdUnit/AdUnitWidget.php widgets/AdUnit/widget_info.php widgets/AdUnit/views/widget.php widgets/AdUnit/views/admin_form.php`

- [ ] **Step 3: SearchForm**

Rename `widgets/search_form/` → `widgets/SearchForm/`

`widgets/SearchForm/widget_info.json`:
```json
{
    "name": "Search Form",
    "description": "Displays a search form.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "placeholder": {
                "type": "text",
                "label": "Placeholder Text",
                "default": ""
            }
        }
    },
    "output": {
        "template": "widget.tpl"
    }
}
```

`widgets/SearchForm/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-search') }}">
    <form action="{% base_url 'search' %}" method="GET" class="{{ cls_form | default('widget-form') }}">
        <input type="search" name="q"
               class="{{ cls_input | default('widget-form-input') }}"
               placeholder="{{ placeholder | default('Search…') }}"
               aria-label="{% lang 'Blog.search' %}">
        <button type="submit" class="{{ cls_button | default('widget-form-button') }}">{% lang 'Blog.search' %}</button>
    </form>
</div>
```

Remove old files: `rm widgets/SearchForm/SearchFormWidget.php widgets/SearchForm/widget_info.php widgets/SearchForm/views/widget.php widgets/SearchForm/views/admin_form.php`


---

### Task 21: Convert Complex Widgets

- [ ] **Step 1: AuthorBio**

Rename `widgets/author_bio/` → `widgets/AuthorBio/`

`widgets/AuthorBio/widget_info.json`:
```json
{
    "name": "Author Bio",
    "description": "Displays the post author's profile card on single post pages.",
    "version": "1.0.0",
    "admin": {
        "notice": "No configuration needed. This widget automatically displays the current post author's profile. Set up profiles in Admin → Users → Profile tab.",
        "options": {}
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "profile": {
                "provider": "AuthorProfileModel.getForCurrentPost",
                "params": {}
            }
        }
    }
}
```

`widgets/AuthorBio/views/widget.tpl`:
```
{% if profile %}
{% if profile.bio or profile.avatar or profile.twitter or profile.facebook or profile.linkedin %}
<div class="{{ cls_widget | default('widget widget-author-bio') }}">
    <div class="{{ cls_card | default('widget-card') }}">
        {% if profile.avatar %}
            <img src="{% base_url profile.avatar %}"
                 alt="{{ profile.display_name | default(profile.username) }}"
                 class="{{ cls_card_image | default('widget-card-image') }}">
        {% else %}
            <img src="https://www.gravatar.com/avatar/{{ profile.email | strtolower | md5 }}?s=96&d=mp"
                 alt="{{ profile.display_name | default(profile.username) }}"
                 class="{{ cls_card_image | default('widget-card-image') }}">
        {% endif %}
        <div class="{{ cls_card_body | default('widget-card-body') }}">
            <div class="{{ cls_card_title | default('widget-card-title') }}">{{ profile.display_name | default(profile.username) }}</div>
            {% if profile.bio %}
                <p class="{{ cls_card_text | default('widget-card-text') }}">{{ profile.bio | nl2br | raw }}</p>
            {% endif %}
            {% if profile.website or profile.twitter or profile.facebook or profile.linkedin %}
            <div class="{{ cls_social_links | default('widget-social-links') }}">
                {% if profile.website %}
                    <a href="{{ profile.website }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="Website"><i class="fas fa-globe"></i></a>
                {% endif %}
                {% if profile.twitter %}
                    <a href="https://twitter.com/{{ profile.twitter }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="Twitter"><i class="fab fa-twitter"></i></a>
                {% endif %}
                {% if profile.facebook %}
                    <a href="https://facebook.com/{{ profile.facebook }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook"></i></a>
                {% endif %}
                {% if profile.linkedin %}
                    <a href="https://linkedin.com/in/{{ profile.linkedin }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                {% endif %}
            </div>
            {% endif %}
        </div>
    </div>
</div>
{% endif %}
{% endif %}
```

Remove old files: `rm widgets/AuthorBio/AuthorBioWidget.php widgets/AuthorBio/widget_info.php widgets/AuthorBio/views/widget.php widgets/AuthorBio/views/admin_form.php`

- [ ] **Step 2: SocialLinks**

Rename `widgets/social_links/` → `widgets/SocialLinks/`

`widgets/SocialLinks/widget_info.json`:
```json
{
    "name": "Social Links",
    "description": "Displays social media profile links.",
    "version": "1.0.0",
    "admin": {
        "notice": "Social links are managed centrally. To add or edit your social profiles, go to Admin → Social Links.",
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Follow Us"
            },
            "style": {
                "type": "select",
                "label": "Style",
                "default": "icons",
                "choices": {
                    "icons": "Icons only",
                    "icons+text": "Icons + Text"
                }
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "links": {
                "provider": "SocialModel.getActive",
                "params": {}
            }
        }
    }
}
```

`widgets/SocialLinks/views/widget.tpl`:
```
<div class="{{ cls_widget | default('widget widget-social-links') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_social_links | default('widget-social-links-list') }}">
        {% for link in links %}
            <a href="{{ link.url }}" target="_blank" rel="noopener" class="{{ cls_social_link | default('widget-social-link') }}">
                <i class="{{ link.icon }}"></i>
                {% if style == 'icons+text' %}
                    {{ link.platform }}
                {% endif %}
            </a>
        {% endfor %}
    </div>
</div>
```

Remove old files: `rm widgets/SocialLinks/SocialLinksWidget.php widgets/SocialLinks/widget_info.php widgets/SocialLinks/views/widget.php widgets/SocialLinks/views/admin_form.php`

- [ ] **Step 3: RelatedPosts**

Rename `widgets/related_posts/` → `widgets/RelatedPosts/`

`widgets/RelatedPosts/widget_info.json`:
```json
{
    "name": "Related Posts",
    "description": "Displays posts related to the current post by shared categories and tags.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Related Posts"
            },
            "count": {
                "type": "number",
                "label": "Number of Posts",
                "default": "4"
            },
            "show_thumbnail": {
                "type": "checkbox",
                "label": "Show Thumbnails",
                "default": "1"
            }
        }
    },
    "output": {
        "template": "widget.tpl",
        "providers": {
            "posts": {
                "provider": "PostModel.getRelated",
                "params": {
                    "limit": "count"
                }
            }
        }
    }
}
```

Note: `PostModel.getRelated` needs the current post ID. The provider receives it from the request context — the model method inspects the current URL to determine the post, same as the old `RelatedPostsWidget` did. This logic lives in the model, not in the widget.

`widgets/RelatedPosts/views/widget.tpl`:
```
{% if posts %}
<div class="{{ cls_widget | default('widget widget-related-posts') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for post in posts %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            {% if show_thumbnail and post.featured_image %}
                <img src="{% base_url post.featured_image %}" alt="{{ post.title }}" class="{{ cls_thumbnail | default('widget-thumbnail') }}">
            {% endif %}
            <a href="{% post_url post.slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ post.title }}</a>
            <span class="{{ cls_meta | default('widget-meta') }}">{{ post.published_at | date('M j, Y') }}</span>
        </li>
        {% endfor %}
    </ul>
</div>
{% endif %}
```

Remove old files: `rm widgets/RelatedPosts/RelatedPostsWidget.php widgets/RelatedPosts/widget_info.php widgets/RelatedPosts/views/widget.php widgets/RelatedPosts/views/admin_form.php`

- [ ] **Step 4: TableOfContents**

Rename `widgets/table_of_contents/` → `widgets/TableOfContents/`

`widgets/TableOfContents/widget_info.json`:
```json
{
    "name": "Table of Contents",
    "description": "Auto-generates a table of contents from post headings.",
    "version": "1.0.0",
    "admin": {
        "options": {
            "title": {
                "type": "text",
                "label": "Widget Title",
                "default": "Table of Contents"
            },
            "min_headings": {
                "type": "number",
                "label": "Minimum Headings to Show",
                "default": "2"
            },
            "max_depth": {
                "type": "select",
                "label": "Max Heading Depth",
                "default": "h3",
                "choices": {
                    "h2": "H2 only",
                    "h3": "H2 + H3",
                    "h4": "H2 + H3 + H4"
                }
            }
        }
    },
    "output": {
        "template": "widget.tpl"
    }
}
```

`widgets/TableOfContents/views/widget.tpl`:
```
<div id="pubvana-toc" class="{{ cls_widget | default('widget widget-toc') }}" style="display:none;">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <nav id="pubvana-toc-nav" class="{{ cls_toc_nav | default('widget-toc-nav') }}"></nav>
</div>

<script>
(function () {
    var minHeadings = {{ min_headings | default(2) | raw }};
    var maxDepth = "{{ max_depth | default('h3') }}";
    var clsList = "{{ cls_toc_list | default('widget-toc-list') }}";
    var clsItem = "{{ cls_toc_item | default('widget-toc-item') }}";
    var clsLink = "{{ cls_toc_link | default('widget-toc-link') }}";

    var selectorMap = { h2: 'h2', h3: 'h2, h3', h4: 'h2, h3, h4' };
    var selector = '.post-content ' + (selectorMap[maxDepth] || 'h2, h3').split(', ').join(', .post-content ');

    document.addEventListener('DOMContentLoaded', function () {
        var headings = document.querySelectorAll(selector);
        if (headings.length < minHeadings) return;

        headings.forEach(function (h, i) {
            if (!h.id) {
                h.id = 'toc-' + h.tagName.toLowerCase() + '-' + i + '-' +
                       h.textContent.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            }
        });

        var levels = { H2: 1, H3: 2, H4: 3 };
        var rootUl = document.createElement('ul');
        rootUl.className = clsList;
        var stack = [{ ul: rootUl, level: 0 }];

        headings.forEach(function (h) {
            var level = levels[h.tagName] || 1;
            while (stack.length > 1 && stack[stack.length - 1].level >= level) { stack.pop(); }

            var li = document.createElement('li');
            li.className = clsItem;
            li.style.paddingLeft = ((level - 1) * 12) + 'px';
            var a = document.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent;
            a.className = clsLink;
            li.appendChild(a);

            var parentUl = stack[stack.length - 1].ul;
            parentUl.appendChild(li);

            var subUl = document.createElement('ul');
            subUl.className = clsList;
            li.appendChild(subUl);
            stack.push({ ul: subUl, level: level });
        });

        document.getElementById('pubvana-toc-nav').appendChild(rootUl);
        document.getElementById('pubvana-toc').style.display = '';
    });
}());
</script>
```

Remove old files: `rm widgets/TableOfContents/TableOfContentsWidget.php widgets/TableOfContents/widget_info.php widgets/TableOfContents/views/widget.php widgets/TableOfContents/views/admin_form.php`


---

### Task 22: Create Paywall Widget

**Files:**
- Create: `widgets/Paywall/widget_info.json`
- Create: `widgets/Paywall/views/widget.tpl`

- [ ] **Step 1: Create widget_info.json**

```json
{
    "name": "Paywall",
    "description": "Displays a call-to-action for premium/paywalled content.",
    "version": "1.0.0",
    "admin": {
        "options": {}
    },
    "output": {
        "template": "widget.tpl"
    }
}
```

Note: The paywall widget receives `show_paywall`, `login_url`, and `register_url` from the theme template context via `{% widget_area 'before-content' %}`. The theme's post view passes paywall state into the widget area. No data provider needed — the data comes from the rendering context.

- [ ] **Step 2: Create views/widget.tpl**

```
{% if show_paywall %}
<div class="{{ cls_widget | default('widget widget-paywall') }}">
    <div class="{{ cls_fade | default('widget-paywall-fade') }}"></div>
    <div class="{{ cls_cta | default('widget-paywall-cta') }}">
        <i class="fas fa-lock {{ cls_icon | default('widget-paywall-icon') }}"></i>
        <h4 class="{{ cls_paywall_title | default('widget-paywall-title') }}">{% lang 'Blog.paywallTitle' %}</h4>
        <p class="{{ cls_message | default('widget-paywall-message') }}">{% lang 'Blog.paywallMessage' %}</p>
        <a href="{{ login_url }}" class="{{ cls_btn_primary | default('widget-paywall-button widget-paywall-button-primary') }}">
            <i class="fas fa-right-to-bracket"></i> {% lang 'Blog.paywallSignIn' %}
        </a>
        <a href="{{ register_url }}" class="{{ cls_btn_secondary | default('widget-paywall-button widget-paywall-button-secondary') }}">
            {% lang 'Blog.paywallCreateAccount' %}
        </a>
    </div>
</div>
{% endif %}
```


---

### Task 22b: Convert Plugin Manifests to JSON

**Files:**
- All `plugins/*/plugin_info.php` → `plugins/*/plugin_info.json`

- [ ] **Step 1: Convert each plugin_info.php to plugin_info.json**

For each plugin directory, convert the PHP array to JSON:

```json
{
    "name": "Plugin Name",
    "description": "What it does.",
    "version": "1.0.0",
    "author": "Author"
}
```

Remove old `plugin_info.php` files after conversion.

- [ ] **Step 2: Update PluginManager::discover() to read JSON**

Read `plugin_info.json`, no PHP fallback.


---

### Task 22c: DB Migration — Widget Folder Names

**Files:**
- Create: `app/Database/Migrations/xxxx_RenameWidgetFolders.php`

- [ ] **Step 1: Create migration**

Update `widgets.folder` column values from snake_case to PascalCase:

```php
$map = [
    'ad_unit'           => 'AdUnit',
    'archive_list'      => 'ArchiveList',
    'author_bio'        => 'AuthorBio',
    'categories_list'   => 'CategoriesList',
    'recent_comments'   => 'RecentComments',
    'recent_posts'      => 'RecentPosts',
    'related_posts'     => 'RelatedPosts',
    'search_form'       => 'SearchForm',
    'social_links'      => 'SocialLinks',
    'table_of_contents' => 'TableOfContents',
    'tag_cloud'         => 'TagCloud',
    'text_block'        => 'TextBlock',
];

foreach ($map as $old => $new) {
    $this->db->table('widgets')->where('folder', $old)->update(['folder' => $new]);
}
```

The `down()` method reverses the mapping.


- [ ] **Phase 4 Commit**

```bash
git add -A
git commit -m "feat: widget system redesign — declarative JSON + .tpl, no PHP in widgets, WidgetDataService provider registry"
```


---

## Phase 4b: ThemeBuilder.md — Write Before Theme Conversion

### Task 19c: Rewrite ThemeBuilder.md

**Files:**
- Modify: `ThemeBuilder.md` (repo root)

**PREREQUISITE:** Phases 1-4 must be complete — template engine, asset pipeline, infrastructure, and widget system all built and working.

**BEFORE WRITING:** Read the actual built code and verify it matches the plan. If code doesn't match, fix the code to match the plan. If you can't reconcile, STOP — we figure it out.

**Verify against real code:**
- `app/Libraries/TemplateEngine/Engine.php` — `render()` signature, includes/extends handling
- `app/Libraries/TemplateEngine/FilterRegistry.php` — all filters
- `app/Libraries/TemplateEngine/TagRegistry.php` — all tag functions
- `app/Services/ThemeService.php` — `view()`, `buildCommonData()`, `publishAssets()`, `validateTheme()`, `discover()`
- All widget `.tpl` files converted and using `cls_` pattern
- All `theme_info.json` files created

- [ ] **Step 1: Verify code matches plan (fix if needed), then write ThemeBuilder.md**

Write the complete `ThemeBuilder.md` by reading the actual built code. The document must be complete enough that an implementor can build any theme from scratch by reading only this file. No placeholders. No "see X for details."

**Sections to include** (source the content from the real code, not from this plan):

1. **Theme Directory Structure** — read the spec's Section 6 file tree, verify against a real converted theme
2. **theme_info.json Format** — read the converted `theme_info.json` files (Tasks 14) for schema and full example
3. **Asset Pipeline** — read `ThemeService::publishAssets()` for how assets are copied, document `theme_url()` resolution
4. **Complete `.tpl` Syntax Reference** — read `Lexer.php`, `Parser.php` for every supported construct. Table format with syntax and notes.
5. **Layout Inheritance — extends/block** — read `Engine.php` for how extends/block works, show skeleton layout + child example from the built default theme
6. **Common Data Bag — Variables Available to Every View** — read `ThemeService::buildCommonData()`, document every variable with type and description. Read the models/helpers it calls to get field names.
7. **Page-Specific Variables per View** — read each public controller (`Blog`, `Pages`, `Contact`, `Search`) to see what they pass. Document per view: home, post, page, category, tag, archive, search.
8. **Tag Functions — Complete Reference** — read `TagRegistry.php`, document every function with signature, arguments, and usage examples
9. **Filters — Complete Reference** — read `FilterRegistry.php`, document every filter with signature, arguments, and usage examples. Note `raw` must be last.
10. **Widget Styling from Themes — The `cls_` Pattern** — read `WidgetBuilder.md` (already written), reference the full `cls_` vocabulary, explain how themes inject classes
11. **User-Visible Strings — `{% lang %}` Requirement** — read `app/Language/en/Blog.php` for all available keys, document which keys are used in which views
12. **Complete Example — Building a Theme from Scratch** — write a minimal but complete theme demonstrating all patterns (extends, block, include, for, if, lang, widget_area, filters, cls_ injection)


---

## Phase 5: Theme Conversion

**IMPORTANT:** This phase converts all 8 themes from PHP to .tpl. Every `.tpl` file must use `{% lang %}` for ALL user-visible strings. No hardcoded English. No PHP tags.

### Task 14: JSON Manifests for All Themes

**Files:**
- Create: `themes/default/theme_info.json` (and remove `theme_info.php`)
- Create: `themes/ember/theme_info.json` (and remove `theme_info.php`)
- Create: `themes/cyborg/theme_info.json` (and remove `theme_info.php`)
- Create: `themes/darkly/theme_info.json` (and remove `theme_info.php`)
- Create: `themes/flatly/theme_info.json` (and remove `theme_info.php`)
- Create: `themes/lux/theme_info.json` (and remove `theme_info.php`)
- Create: `themes/sandstone/theme_info.json` (and remove `theme_info.php`)
- Create: `themes/slate/theme_info.json` (and remove `theme_info.php`)

- [ ] **Step 1: Create theme_info.json for default**

```json
{
    "name": "Default",
    "version": "1.0.0",
    "author": "Pubvana",
    "description": "Clean Bootstrap 5 theme.",
    "screenshot": "screenshot.png",
    "widget_areas": {
        "sidebar": "Main Sidebar",
        "footer-1": "Footer Column 1",
        "footer-2": "Footer Column 2",
        "footer-3": "Footer Column 3",
        "before-content": "Before Content"
    },
    "options": {
        "show_sidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": "1"
        },
        "footer_copyright": {
            "type": "text",
            "label": "Footer Copyright Text",
            "default": ""
        }
    }
}
```

- [ ] **Step 2: Create theme_info.json for ember**

```json
{
    "name": "Ember",
    "premium": true,
    "version": "1.0.0",
    "author": "Pubvana Team",
    "description": "Modern, warm, and approachable blog theme. Amber accents, refined typography, and a clean reading experience.",
    "screenshot": "screenshot.png",
    "widget_areas": {
        "sidebar": "Main Sidebar",
        "footer-1": "Footer Column 1",
        "footer-2": "Footer Column 2",
        "footer-3": "Footer Column 3",
        "before-content": "Before Content"
    },
    "options": {
        "show_sidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": "1"
        },
        "hero_tagline": {
            "type": "text",
            "label": "Hero Tagline",
            "default": ""
        },
        "accent_color": {
            "type": "text",
            "label": "Accent Colour (hex)",
            "default": "#f59e0b"
        },
        "footer_copyright": {
            "type": "text",
            "label": "Footer Copyright Text",
            "default": ""
        }
    }
}
```

- [ ] **Step 3: Create theme_info.json for each Bootswatch theme**

Each Bootswatch theme uses the same structure as default (same widget areas, same options — `show_sidebar` and `footer_copyright`). No `parent` key. Create these 6 files:

**cyborg:**
```json
{
    "name": "Cyborg",
    "version": "1.0.0",
    "author": "Pubvana",
    "description": "Dark futuristic theme powered by Bootswatch Cyborg.",
    "screenshot": "screenshot.png",
    "widget_areas": {
        "sidebar": "Main Sidebar",
        "footer-1": "Footer Column 1",
        "footer-2": "Footer Column 2",
        "footer-3": "Footer Column 3",
        "before-content": "Before Content"
    },
    "options": {
        "show_sidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": "1"
        },
        "footer_copyright": {
            "type": "text",
            "label": "Footer Copyright Text",
            "default": ""
        }
    }
}
```

**darkly:** Same structure, `"name": "Darkly"`, `"description": "Dark elegant theme powered by Bootswatch Darkly."`

**flatly:** Same structure, `"name": "Flatly"`, `"description": "Flat and modern theme powered by Bootswatch Flatly."`

**lux:** Same structure, `"name": "Lux"`, `"description": "Luxurious elegant theme powered by Bootswatch Lux."`

**sandstone:** Same structure, `"name": "Sandstone"`, `"description": "Warm earthy theme powered by Bootswatch Sandstone."`

**slate:** Same structure, `"name": "Slate"`, `"description": "Dark professional theme powered by Bootswatch Slate."`

- [ ] **Step 4: Remove old theme_info.php files**

```bash
rm themes/default/theme_info.php
rm themes/ember/theme_info.php
rm themes/cyborg/theme_info.php
rm themes/darkly/theme_info.php
rm themes/flatly/theme_info.php
rm themes/lux/theme_info.php
rm themes/sandstone/theme_info.php
rm themes/slate/theme_info.php
```

---

### Task 20: Default Theme — Complete .tpl Conversion

**Files:**
- Create all `.tpl` files in `themes/default/views/`
- Remove all `.php` files from `themes/default/views/`

This is the reference implementation. All Bootswatch themes will copy these view files.

Due to the large number of files (15 .tpl files), this task shows the complete `layout.tpl`, `home.tpl`, `post.tpl`, and `partials/post-card.tpl` in full. The remaining files follow the same patterns — convert the corresponding PHP file to .tpl syntax using:

- `{{ variable }}` for escaped output
- `{! variable !}` for raw HTML (main_content, rendered content)
- `{% if %}` / `{% for %}` for control flow
- `{% lang 'Blog.key' %}` for ALL user-visible strings
- `{% base_url %}`, `{% theme_url %}`, `{% post_url %}`, `{% category_url %}`, `{% tag_url %}` for URLs
- `{% widget_area 'slug' %}` for widget areas
- `{% include 'partials/name' with {data: data} %}` for partials
- `{% extends 'layout' %}` / `{% block content %}` for layout wrapping

- [ ] **Step 1: Create layout.tpl**

```
<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ seo.title | default(site_name) }}</title>
    {% if seo.description %}
    <meta name="description" content="{{ seo.description }}">
    {% endif %}
    {% if seo.og_title %}
    <meta property="og:title" content="{{ seo.og_title }}">
    <meta property="og:description" content="{{ seo.og_description | default('') }}">
    {% if seo.og_image %}
    <meta property="og:image" content="{{ seo.og_image }}">
    {% endif %}
    {% endif %}
    <link rel="alternate" type="application/rss+xml" title="{{ site_name }} RSS Feed" href="{% base_url 'feed' %}">
    <link rel="alternate" type="application/atom+xml" title="{{ site_name }} Atom Feed" href="{% base_url 'atom' %}">
    {% if lang_switcher.buttons %}
    {% for btn in lang_switcher.buttons %}
    <link rel="alternate" hreflang="{{ btn.code }}" href="{% base_url btn.url %}">
    {% endfor %}
    <link rel="alternate" hreflang="x-default" href="{% base_url %}">
    {% endif %}

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="{% theme_url 'css/theme.css' %}" rel="stylesheet">

    {% block head_extra %}{% endblock %}

    {% if analytics_id %}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ analytics_id }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{! analytics_id !}');</script>
    {% endif %}
    {% if json_ld %}
    <script type="application/ld+json">{! json_ld !}</script>
    {% endif %}
</head>
<body>

{% if preview_mode %}
<div style="background:#f59e0b;color:#000;text-align:center;padding:8px 16px;font-size:14px;font-weight:600;position:sticky;top:0;z-index:9999;">
    &#128065; {% lang 'Blog.previewModeBanner' %}
</div>
{% endif %}

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{% base_url %}">
            {{ site_name }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{% base_url %}">{% lang 'Blog.home' %}</a></li>
                <li class="nav-item"><a class="nav-link" href="{% base_url 'blog' %}">{% lang 'Blog.blog' %}</a></li>
                {% for navItem in primary_nav %}
                <li class="nav-item">
                    <a class="nav-link" href="{{ navItem.url }}" target="{{ navItem.target }}">
                        {{ navItem.label }}
                    </a>
                </li>
                {% endfor %}
            </ul>
            <form class="d-flex" action="{% base_url 'search' %}" method="GET">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="{% lang 'Blog.searchPlaceholder' %}" aria-label="{% lang 'Blog.search' %}">
                <button class="btn btn-outline-light btn-sm" type="submit"><i class="fas fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="py-5">
    <div class="container">
        {% block content %}{% endblock %}
    </div>
</main>

<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-3 mb-4">
                <h5>{{ site_name }}</h5>
                <p class="text-white-50">{{ site_tagline }}</p>
                {% for s in social_links %}
                    <a href="{{ s.url }}" class="text-white-50 me-2" target="_blank" rel="noopener">
                        <i class="{{ s.icon }}"></i>
                    </a>
                {% endfor %}
            </div>
            <div class="col-md-3 mb-4">
                {% widget_area 'footer-1' %}
            </div>
            <div class="col-md-3 mb-4">
                {% widget_area 'footer-2' %}
            </div>
            <div class="col-md-3 mb-4">
                {% widget_area 'footer-3' %}
            </div>
        </div>
        <hr class="border-secondary">
        <div class="row">
            <div class="col text-center text-white-50 small">
                {% if footer_copyright %}
                    {{ footer_copyright }}
                {% else %}
                    &copy; {{ site_name }}. {% lang 'Blog.allRightsReserved' %}
                {% endif %}
                &nbsp;&middot;&nbsp;
                <a href="{% base_url 'feed' %}" class="text-white-50"><i class="fas fa-rss"></i> {% lang 'Blog.rssFeed' %}</a>
                {% if sitemap_enabled %}
                &nbsp;&middot;&nbsp;
                <a href="{% base_url 'sitemap.xml' %}" class="text-white-50">{% lang 'Blog.sitemap' %}</a>
                {% endif %}
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
{% block scripts %}{% endblock %}
</body>
</html>
```

- [ ] **Step 2: Create home.tpl**

```
{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="{% if show_sidebar == '1' %}col-lg-8{% else %}col-12{% endif %}">
        {% widget_area 'before-content' %}
        {% if posts %}
            {% for post in posts %}
                {% include 'partials/post-card' with {post: post} %}
            {% endfor %}
            {% if pager_links %}
                {% include 'partials/pagination' with {pager_links: pager_links} %}
            {% endif %}
        {% else %}
            <p class="text-muted text-center py-4">{% lang 'Blog.noPostsYet' %}</p>
        {% endif %}
    </div>
    {% if show_sidebar == '1' %}
    <div class="col-lg-4">
        {% include 'partials/sidebar' %}
    </div>
    {% endif %}
</div>
{% endblock %}
```

- [ ] **Step 3: Create remaining .tpl files**

Convert each remaining PHP view file to .tpl following the same patterns. Full list:

1. `views/post.tpl` — convert from `views/post.php`. Key changes:
   - `{% extends 'layout' %}` with `{% block content %}`
   - Replace `session()->getFlashdata()` with `{{ flash_success }}` / `{{ flash_error }}`
   - Replace `auth()->loggedIn()` with `{% if is_logged_in %}`
   - Replace `setting('App.commentsEnabled')` with `{% if comments_enabled %}`
   - Paywall is now a widget assigned to a widget area — remove the old `view('partials/paywall')` call
   - Use `{% render_content post %}` for post body
   - Replace DB queries for `show_sidebar` with `{{ show_sidebar }}`
   - Display reading time: `{% if reading_time %}{{ reading_time }} {% lang 'Blog.minRead' %}{% endif %}`
   - All strings use `{% lang %}`

2. `views/page.tpl` — convert from `views/page.php`. Simple: extends layout, renders `{% render_content page %}`.

3. `views/category.tpl` — convert from `views/category.php`. Uses `{% lang 'Blog.categoryHeading' category.name %}`.

4. `views/tag.tpl` — convert from `views/tag.php`. Uses `{% lang 'Blog.tagHeading' tag.name %}`.

5. `views/archive.tpl` — convert from `views/archive.php`. Uses `{% lang 'Blog.archiveHeading' archive.title %}`.

6. `views/search.tpl` — convert from `views/search.php`. Uses search-related lang keys.

7. `views/partials/post-card.tpl`:
```
<div class="card mb-4 shadow-sm">
    {% if post.featured_image %}
    <a href="{% post_url post.slug %}">
        <img src="{% base_url post.featured_image %}" class="card-img-top" alt="{{ post.title }}">
    </a>
    {% endif %}
    <div class="card-body">
        <h5 class="card-title"><a href="{% post_url post.slug %}" class="text-decoration-none">{{ post.title }}</a></h5>
        <p class="card-text text-muted small">
            {{ post.published_at | default(post.created_at) | date('F j, Y') }}
            {% if post.views %}
                &middot; {% lang 'Blog.views' post.views|number_format %}
            {% endif %}
        </p>
        {% if post.excerpt %}
            <p class="card-text">{{ post.excerpt | excerpt(150) }}</p>
        {% endif %}
        <a href="{% post_url post.slug %}" class="btn btn-sm btn-outline-primary">{% lang 'Blog.readMore' %}</a>
    </div>
</div>
```

8. `views/partials/sidebar.tpl`:
```
{% widget_area 'sidebar' %}
```

9. `views/partials/pagination.tpl` — This currently calls `$pager->links()` which returns HTML. In the .tpl, this needs to be a tag function or pre-rendered. Add `pager_links` as a pre-rendered string in the data bag (ThemeService renders it before passing to engine). Then:
```
{% if pager_links %}
<nav aria-label="{% lang 'Blog.pageNavLabel' %}">
    {! pager_links !}
</nav>
{% endif %}
```

10. `views/partials/author-card.tpl` — Convert from PHP. Uses `{% lang 'Blog.authorCardLabel' %}`, `{% lang 'Blog.unknownAuthor' %}`. Gravatar URL uses `{{ author_profile.email | strtolower | md5 }}`.

11. `views/partials/comment-form.tpl` — Convert from PHP. Uses `{% if is_logged_in %}`, `{% if comments_enabled %}`, `{% if comment_moderation %}`, hCaptcha via `{{ hcaptcha_site_key }}`.

12. `views/partials/comments-list.tpl` — Renders comment tree. Includes `_comment.tpl` recursively.

13. `views/partials/_comment.tpl` — Single comment with recursive children.

- [ ] **Step 4: Remove all PHP view files from default theme**

```bash
rm themes/default/views/*.php
rm themes/default/views/partials/*.php
```


---

### Task 21: Ember Theme — Complete .tpl Conversion

**Files:** All views in `themes/ember/views/`

Ember differs significantly from default: accent color CSS vars, hero section on home, Google Fonts (Inter + Lora), `.ember-*` CSS classes, different comment field names.

- [ ] **Step 1: Create ember layout.tpl**

Key differences from default:
- Google Fonts link (Inter + Lora)
- Accent color CSS custom property from `{{ accent_color | default('#f59e0b') }}`
- `.ember-*` CSS classes throughout
- Social links with `{{ s.platform }}` title attributes

Create the full `themes/ember/views/layout.tpl` following the same pattern as default but with ember-specific markup. Convert from `themes/ember/views/layout.php`.

- [ ] **Step 2: Create all ember .tpl files**

Convert each PHP view in `themes/ember/views/` to `.tpl`. Key ember-specific differences:

- `home.tpl`: Has hero section with `{{ hero_tagline }}`
- `post.tpl`: Uses `{{ reading_time }}` (pre-computed by Blog controller, see Task 28)
- `partials/post-card.tpl`: Ember-specific card layout
- `partials/author-card.tpl`: Uses `post.author_email` for Gravatar (not `author_profile.email`)
- `partials/_comment.tpl`: Uses `comment.body` (not `comment.content`) and `comment.email` (not `comment.author_email`)

**IMPORTANT:** The field name differences (`body` vs `content`, `email` vs `author_email`) are inconsistencies in the current PHP views. These MUST be preserved in the .tpl conversion — they reference actual database column names. Do NOT "fix" them.

- [ ] **Step 3: Remove PHP files**

```bash
rm themes/ember/views/*.php themes/ember/views/partials/*.php
```

---

### Tasks 22-27: Bootswatch Themes — Standalone .tpl Conversion

Each Bootswatch theme (cyborg, darkly, flatly, lux, sandstone, slate) becomes a fully standalone theme.

**For each theme, the process is identical:**

1. Copy ALL `.tpl` files from `themes/default/views/` to `themes/{name}/views/` (including the `partials/` subdirectory)
2. Replace `layout.tpl` with the theme-specific version (different CDN link, possibly different navbar/footer classes)
3. Remove ALL `.php` files from `views/` and `views/partials/`: `rm -f themes/{name}/views/*.php themes/{name}/views/partials/*.php`

- [ ] **Task 22: Cyborg**

Copy default views:
```bash
cp -r themes/default/views/*.tpl themes/cyborg/views/
mkdir -p themes/cyborg/views/partials
cp -r themes/default/views/partials/*.tpl themes/cyborg/views/partials/
```

Replace `themes/cyborg/views/layout.tpl` — same structure as default layout.tpl but:
- Replace Bootstrap CSS link with: `https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/cyborg/bootstrap.min.css`
- Keep `navbar-dark bg-dark` (same as default)
- Keep `bg-dark text-light` footer (same as default)

Remove all old PHP files:
```bash
rm -f themes/cyborg/views/*.php themes/cyborg/views/partials/*.php
```


- [ ] **Task 23: Darkly**

Same process. Layout differences:
- CSS: `https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/darkly/bootstrap.min.css`
- Nav: `navbar-dark bg-primary`
- Footer: `bg-primary text-light`

- [ ] **Task 24: Flatly**

Same process. Layout differences:
- CSS: `https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/flatly/bootstrap.min.css`
- Nav: `navbar-dark bg-primary`
- Footer: `bg-primary text-light`

- [ ] **Task 25: Lux**

Same process. Layout differences:
- CSS: `https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/lux/bootstrap.min.css`
- Additional Google Fonts link: `https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&display=swap`
- Nav: `navbar-light bg-white` with custom `.lux-navbar` class
- Footer: custom `.lux-footer` class

- [ ] **Task 26: Sandstone**

Same process. Layout differences:
- CSS: `https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/sandstone/bootstrap.min.css`
- Nav: `navbar-dark bg-primary`
- Footer: `bg-primary text-light`

- [ ] **Task 27: Slate**

Same process. Layout differences:
- CSS: `https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/slate/bootstrap.min.css`
- Nav: `navbar-dark bg-dark`
- Footer: `bg-dark text-light`

- [ ] **Phase 5 Commit**

```bash
git add -A
git commit -m "feat: all themes converted to .tpl with JSON manifests and full lang() support"
```

---

## Phase 6: Documentation

### Task 31b: Create PluginBuilder.md

**Files:**
- Create: `PluginBuilder.md`

Quick synopsis covering:
- Plugin directory structure (`plugins/my_plugin/`)
- `plugin_info.json` format
- Plugin PHP backend (routes, controllers, migrations, models) — stays as PHP
- Plugin frontend uses `.tpl` for any output rendered within the active theme layout
- Same engine, same tag functions and filters as themes/widgets
- Full plugin architecture (route registration, config discovery, lifecycle hooks) deferred to separate spec

- [ ] **Step 1: Write PluginBuilder.md**


---

### Task 32: Update README.md

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Rewrite steps 7-10**

Replace steps 9 (Theme Assets Symlink) and 10 (Media Uploads Symlink) with:

**Step 9: Theme Assets and Media**

Theme assets and media uploads are stored inside the web server's document root automatically. No symlinks are needed. Visit **Admin → Themes** to ensure theme assets are published.

Remove references to `+FollowSymlinks`, `chown`, `ln -s`, and symlink troubleshooting from those sections.

Update the Production Hardening Checklist to remove the symlink-related items.

- [ ] **Step 2: Update Features list**

Change "Theme system with widget areas, theme options, and asset symlinking" to "Theme system with sandboxed .tpl engine, widget areas, theme options, and framework-agnostic widgets"


- [ ] **Phase 6 Commit**

```bash
git add -A
git commit -m "docs: PluginBuilder.md, README updates"
```

---

## Self-Review Checklist

1. **Spec coverage:** All 10 spec sections mapped to tasks. Asset pipeline (Tasks 8-10), Template engine (Tasks 1-7, 11-12), Theme validation (Task 13), Widget standardization (Tasks 15-19), Plugin frontend (Task 19b JSON, spec section noted as deferred), Theme structure (Tasks 14, 20-27), Removals (Task 29), Updates (Tasks 8-13, 15-19, 28), Creations (Tasks 1-7, 19), Documentation (Tasks 14b, 19c, 31b, 32).

2. **Placeholder scan:** Task 20 Step 2 lists remaining .tpl files with conversion rules rather than full file contents — these are explicit instructions, not placeholders. Task 21 Step 2 similarly provides specific guidance for ember-specific differences.

3. **Type consistency:** `Engine::render()` signature consistent across Tasks 7 and 11. `publishAssets()` name consistent across Tasks 8 and 11. `FilterRegistry::apply()` and `TagRegistry::call()` signatures match usage in Interpreter. Node class names match across Parser and Interpreter.

4. **Pagination:** ThemeService `view()` (Task 12) pre-renders `$pageData['pager']` to `pager_links` HTML before passing to the engine.
