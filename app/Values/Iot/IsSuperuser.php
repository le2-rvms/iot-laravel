<?php

namespace App\Values\Iot;

use App\Enum\EnumLikeBase;

class IsSuperuser extends EnumLikeBase
{
    public const NO = 0;

    public const YES = 1;

    /**
     * @var array<int, string>
     */
    public const LABELS = [
        self::NO => '否',
        self::YES => '是',
    ];

    public function isEnabled(): bool
    {
        // 复用布尔语义方法，调用方无需关心底层仍是 0/1 存储。
        return $this->value === self::YES;
    }
}
