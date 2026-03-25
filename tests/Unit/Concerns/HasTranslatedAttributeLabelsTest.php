<?php

namespace Tests\Unit\Concerns;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Settings\NotificationChannel;
use App\Models\Settings\NotificationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class HasTranslatedAttributeLabelsTest extends TestCase
{
    public function test_it_reads_labels_from_model_translations(): void
    {
        $this->assertSame('邮箱', AdminUser::attributeLabels()['email']);
        $this->assertSame('管理员角色名称', AdminRole::attributeLabels()['name']);
    }

    public function test_it_reads_labels_from_multiple_model_translations(): void
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

    public function test_it_switches_locales_without_reusing_stale_model_labels(): void
    {
        App::setLocale('en');
        $this->assertSame('Email', AdminUser::attributeLabels()['email']);
        $this->assertSame('Channel Target', NotificationChannel::attributeLabels()['target']);

        App::setLocale('zh_CN');
        $this->assertSame('邮箱', AdminUser::attributeLabels()['email']);
        $this->assertSame('渠道目标', NotificationChannel::attributeLabels()['target']);
    }
}
