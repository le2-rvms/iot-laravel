<?php

namespace Tests\Concerns;

use App\Support\PermissionRegistry;
use ReflectionProperty;

trait ResetsPermissionRegistryCache
{
    protected function resetPermissionRegistryCache(): void
    {
        new ReflectionProperty(PermissionRegistry::class, 'cache')
            ->setValue(null, null);
    }
}
