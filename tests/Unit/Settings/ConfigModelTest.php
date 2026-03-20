<?php

namespace Tests\Unit\Settings;

use App\Models\Settings\Config;
use App\Values\Settings\ConfigCategory;
use App\Values\Settings\ConfigMaskState;
use PHPUnit\Framework\TestCase;

class ConfigModelTest extends TestCase
{
    public function test_config_mask_state_normalizes_boolean_and_numeric_values(): void
    {
        $this->assertSame(1, (new ConfigMaskState(true))->value);
        $this->assertSame(0, (new ConfigMaskState(false))->value);
        $this->assertSame(1, (new ConfigMaskState('1'))->value);
        $this->assertSame(0, (new ConfigMaskState('0'))->value);
    }

    public function test_config_accessors_use_value_objects_for_display_fields(): void
    {
        $config = new Config();
        $config->forceFill([
            'key' => 'app.secret',
            'value' => 'token-123',
            'category' => ConfigCategory::APPLICATION,
            'is_masked' => true,
            'remark' => '应用密钥',
        ]);

        $this->assertInstanceOf(ConfigCategory::class, $config->category);
        $this->assertInstanceOf(ConfigMaskState::class, $config->is_masked);
        $this->assertSame('应用配置', $config->category_label);
        $this->assertSame('*****', $config->value_display);
        $this->assertSame('是', $config->is_masked_label);
    }

    public function test_config_casts_raw_database_values_to_value_objects(): void
    {
        $config = new Config();
        $config->setRawAttributes([
            'category' => 'system',
            'is_masked' => '0',
            'value' => 'maintenance',
        ], true);

        $this->assertInstanceOf(ConfigCategory::class, $config->category);
        $this->assertInstanceOf(ConfigMaskState::class, $config->is_masked);
        $this->assertSame('系统配置', $config->category_label);
        $this->assertSame('maintenance', $config->value_display);
        $this->assertSame('否', $config->is_masked_label);
    }
}
