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
