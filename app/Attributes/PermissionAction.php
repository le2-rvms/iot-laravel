<?php

namespace App\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_METHOD)]
class PermissionAction
{
    public function __construct(public string $action)
    {
        if (! in_array($action, ['read', 'write'], true)) {
            throw new InvalidArgumentException("Unsupported permission action [{$action}].");
        }
    }
}
