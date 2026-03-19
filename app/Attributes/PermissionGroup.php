<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PermissionGroup
{
    public function __construct(public string $label) {}
}
