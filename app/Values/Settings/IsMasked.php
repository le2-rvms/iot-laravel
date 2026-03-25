<?php

namespace App\Values\Settings;

use App\Values\Support\EnumLikeBase;

class IsMasked extends EnumLikeBase
{
    public const UNMASKED = 0;

    public const MASKED = 1;

    /**
     * @var array<int, string>
     */
    public const LABELS = [
        self::UNMASKED => '否',
        self::MASKED => '是',
    ];

    public function isMasked(): bool
    {
        // 配置页统一通过这个方法决定是否返回打码展示值。
        return $this->value === self::MASKED;
    }
}
