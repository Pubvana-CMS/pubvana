<?php

namespace App\Libraries\TemplateEngine;

/** not expression */
class NotExpression extends Expression
{
    public function __construct(public readonly Expression $expression) {}
}
