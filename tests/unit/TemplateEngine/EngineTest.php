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
