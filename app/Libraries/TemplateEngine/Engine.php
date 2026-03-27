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
