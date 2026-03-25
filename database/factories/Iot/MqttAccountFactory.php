<?php

namespace Database\Factories\Iot;

use App\Models\Iot\IotMqttAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IotMqttAccount>
 */
class MqttAccountFactory extends Factory
{
    protected $model = IotMqttAccount::class;

    public function definition(): array
    {
        return [
            'clientid' => fake()->optional()->bothify('client-####'),
            'user_name' => fake()->unique()->userName(),
            // 工厂统一走模型里的哈希规则，避免测试数据和实际鉴权逻辑脱节。
            ...IotMqttAccount::buildPasswordFields('password'),
            'certificate' => fake()->optional()->sentence(),
            'is_superuser' => false,
            'product_key' => fake()->optional()->bothify('PK-####'),
            'device_name' => fake()->optional()->word(),
            'enabled' => true,
            'act_updated_by' => fake()->safeEmail(),
        ];
    }
}
