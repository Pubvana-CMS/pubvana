<?php

namespace App\Libraries\TemplateEngine;

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
