<?php

namespace App\Values\Iot;

use App\Enum\EnumLikeBase;

class Enabled extends EnumLikeBase
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var array<int, string>
     */
    public const LABELS = [
        self::DISABLED => '停用',
        self::ENABLED => '启用',
    ];

    public function isEnabled(): bool
    {
        // 后台、EMQX 鉴权和列表展示都统一通过这个语义方法判断启用状态。
        return $this->value === self::ENABLED;
    }
}
