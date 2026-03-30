<?php

namespace App\Libraries\TemplateEngine;

/** Literal text content — output as-is. */
class TextNode extends Node
{
    public function __construct(public readonly string $text) {}
}
