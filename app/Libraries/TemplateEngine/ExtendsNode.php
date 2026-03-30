<?php

namespace App\Libraries\TemplateEngine;

/** {% extends 'layout' %} */
class ExtendsNode extends Node
{
    public function __construct(public readonly string $templateName) {}
}
