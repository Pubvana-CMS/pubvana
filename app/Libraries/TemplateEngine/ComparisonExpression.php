<?php

namespace App\Libraries\TemplateEngine;

/** left == right, left > right, etc. */
class ComparisonExpression extends Expression
{
    public function __construct(
        public readonly Expression $left,
        public readonly string $operator,
        public readonly Expression $right,
    ) {}
}
