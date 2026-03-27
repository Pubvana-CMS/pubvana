<?php

namespace App\Libraries\TemplateEngine;

class Parser
{
    private array $segments;
    private int $pos;

    /** Tag function names recognized by the engine. */
    private const TAG_FUNCTIONS = [
        'lang', 'theme_url', 'base_url', 'site_url', 'widget_area',
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
