<?php

namespace App\Libraries\TemplateEngine;

/** String, number, bool, or null literal. */
class LiteralExpression extends Expression
{
    public function __construct(public readonly mixed $value) {}
}
