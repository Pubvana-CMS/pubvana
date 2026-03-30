<?php

namespace App\Libraries\TemplateEngine;

/** expression | filterName(arg1, arg2) */
class FilterExpression extends Expression
{
    /** @param Expression[] $arguments */
    public function __construct(
        public readonly Expression $expression,
        public readonly string $filterName,
        public readonly array $arguments = [],
    ) {}
}
