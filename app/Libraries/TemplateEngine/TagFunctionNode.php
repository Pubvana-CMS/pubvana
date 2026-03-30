<?php

namespace App\Libraries\TemplateEngine;

/** {% tag_name arg1 arg2 %} — whitelisted tag function call. */
class TagFunctionNode extends Node
{
    /** @param Expression[] $arguments */
    public function __construct(
        public readonly string $name,
        public readonly array $arguments,
    ) {}
}
