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
