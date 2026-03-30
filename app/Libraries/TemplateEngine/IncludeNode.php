<?php

namespace App\Libraries\TemplateEngine;

/** {% include 'partial' with {key: value} %} */
class IncludeNode extends Node
{
    /** @param array<string, Expression>|null $withData */
    public function __construct(
        public readonly string $templateName,
        public readonly ?array $withData = null,
    ) {}
}
