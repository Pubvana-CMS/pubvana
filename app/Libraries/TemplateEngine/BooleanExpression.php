<?php

namespace App\Libraries\TemplateEngine;

/** left and right, left or right */
class BooleanExpression extends Expression
{
    public function __construct(
        public readonly Expression $left,
        public readonly string $operator,
        public readonly Expression $right,
    ) {}
}
