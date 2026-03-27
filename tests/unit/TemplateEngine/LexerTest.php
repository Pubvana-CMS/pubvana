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
