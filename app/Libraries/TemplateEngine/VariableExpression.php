<?php

namespace App\Libraries\TemplateEngine;

/** Variable reference, possibly dotted: post.title */
class VariableExpression extends Expression
{
    /** @param string[] $parts e.g. ['post', 'title'] */
    public function __construct(public readonly array $parts) {}
}
