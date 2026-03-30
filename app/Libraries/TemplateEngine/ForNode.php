<?php

namespace App\Libraries\TemplateEngine;

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
