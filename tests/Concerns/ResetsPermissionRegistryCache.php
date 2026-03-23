<?php

namespace Tests\Concerns;

use App\Support\PermissionRegistry;
use ReflectionProperty;

trait ResetsPermissionRegistryCache
{
    protected function resetPermissionRegistryCache(): void
    {
        // PermissionRegistry 在进程内持有静态缓存，测试之间需要做一次硬重置。
        // 这里用反射，是因为缓存重置故意不暴露为生产代码的公开 API。
        new ReflectionProperty(PermissionRegistry::class, 'cache')
            ->setValue(null, null);
    }
}
