<?php

namespace App\Libraries\TemplateEngine;

/** {! expression !} — raw (unescaped) output. */
class RawOutputNode extends Node
{
    public function __construct(public readonly Expression $expression) {}
}
