<?php

namespace App\Libraries\TemplateEngine;

/** {{ expression }} — auto-escaped output. */
class OutputNode extends Node
{
    public function __construct(public readonly Expression $expression) {}
}
