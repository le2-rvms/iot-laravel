<?php

namespace App\Models\Settings;

use App\Concerns\ResolvesAttributeLabelsFromDocBlocks;

/**
 * @property string $type 渠道类型
 * @property string $target 渠道目标
 * @property int $retries 重试次数
 * @property bool $enabled 渠道启用状态
 */
class NotificationChannel
{
    use ResolvesAttributeLabelsFromDocBlocks;
}
