<?php

namespace Tests\Unit\Concerns;

use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Settings\NotificationChannel;
use App\Models\Settings\NotificationRule;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;

class ResolvesAttributeLabelsFromDocBlocksTest extends TestCase
{
    public function test_it_reads_labels_from_model_doc_blocks(): void
    {
        $this->assertSame('邮箱', User::attributeLabels()['email']);
        $this->assertSame('角色名称', Role::attributeLabels()['name']);
    }

    public function test_it_reads_labels_from_multiple_model_doc_blocks(): void
    {
        $labels = NotificationRule::attributeLabels();

        $this->assertSame('规则名称', NotificationRule::attributeLabels()['name']);
        $this->assertSame('渠道目标', NotificationChannel::attributeLabels()['target']);
        $this->assertSame('规则名称', $labels['name']);
        $this->assertSame('通知渠道', $labels['channels']);
    }

    public function test_nested_attributes_can_be_composed_from_models(): void
    {
        $labels = array_merge(
            NotificationRule::attributeLabels(),
            Arr::dot([
                'channels' => [
                    '*' => NotificationChannel::attributeLabels(),
                ],
            ]),
        );

        $this->assertSame('通知渠道', $labels['channels']);
        $this->assertSame('渠道目标', $labels['channels.*.target']);
    }
}
