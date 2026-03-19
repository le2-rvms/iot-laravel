<?php

namespace App\Models\Settings;

use App\Concerns\ResolvesAttributeLabelsFromDocBlocks;

/**
 * @property string $name 规则名称
 * @property bool $enabled 启用状态
 * @property string|null $description 说明
 * @property string $trigger_mode 触发方式
 * @property int|null $threshold 触发阈值
 * @property bool $quiet_hours_enabled 静默时段开关
 * @property string|null $quiet_hours_start 静默开始时间
 * @property string|null $quiet_hours_end 静默结束时间
 * @property array<int, array<string, mixed>> $channels 通知渠道
 */
class NotificationRule
{
    use ResolvesAttributeLabelsFromDocBlocks;
}
