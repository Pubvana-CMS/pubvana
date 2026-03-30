<?php

namespace App\Libraries\TemplateEngine;

/** {% block name %}...{% endblock %} */
class BlockNode extends Node
{
    /** @param Node[] $body */
    public function __construct(
        public readonly string $name,
        public readonly array $body,
    ) {}
}
