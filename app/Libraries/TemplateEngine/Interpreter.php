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
