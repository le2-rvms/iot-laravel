<?php

namespace App\Values\Settings;

use App\Enum\EnumLikeBase;

class Category extends EnumLikeBase
{
    public const APPLICATION = 'application';

    public const SYSTEM = 'system';

    /**
     * 保留英文 key，便于路由、查询和 toCaseSQL 这类内部约定稳定复用。
     *
     * @var array<string, string>
     */
    public const LABELS = [
        self::APPLICATION => '应用配置',
        self::SYSTEM => '系统配置',
    ];
}
