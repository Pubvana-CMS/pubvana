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
