<?php

namespace Tests\Unit\Models\Iot;

use App\Models\Iot\IotGpsCommand;
use App\Models\Iot\IotDevice;
use App\Models\Iot\IotDeviceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeviceModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('device_products', function ($table): void {
            $table->increments('product_id');
            $table->string('product_key', 64)->unique();
            $table->string('product_name', 255);
            $table->timestamps();
        });

        Schema::create('devices', function ($table): void {
            $table->string('terminal_id', 64)->primary();
            $table->integer('dev_id')->nullable();
            $table->string('dev_name', 255);
            $table->string('product_key', 64)->nullable();
            $table->string('auth_code_seed', 255)->nullable();
            $table->integer('city_relation_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('gps_commands', function ($table): void {
            $table->increments('id');
            $table->integer('device_id')->nullable();
            $table->string('terminal_id', 64)->nullable();
            $table->string('cmd_type', 64)->nullable();
            $table->text('payload')->nullable();
            $table->integer('flow_id')->nullable();
            $table->string('status', 64)->nullable();
            $table->integer('retries')->nullable();
            $table->integer('max_retries')->nullable();
            $table->timestamps();
        });
    }

    public function test_gps_commands_relation_points_to_the_gps_command_model(): void
    {
        $this->assertInstanceOf(IotGpsCommand::class, (new IotDevice)->gpsCommands()->getRelated());
    }

    public function test_device_product_relation_points_to_the_device_product_model(): void
    {
        $this->assertInstanceOf(IotDeviceProduct::class, (new IotDevice)->deviceProduct()->getRelated());
    }

    public function test_audit_mask_hides_auth_code_seed(): void
    {
        $this->assertSame(['auth_code_seed'], (new IotDevice)->auditMask());
    }

    public function test_index_query_can_filter_by_city_relation_id(): void
    {
        \DB::table('devices')->insert([
            [
                'terminal_id' => 'terminal-a',
                'dev_id' => 1,
                'dev_name' => 'Device A',
                'city_relation_id' => 1,
                'created_at' => now(),
            ],
            [
                'terminal_id' => 'terminal-b',
                'dev_id' => 2,
                'dev_name' => 'Device B',
                'city_relation_id' => 2,
                'created_at' => now(),
            ],
        ]);

        $results = IotDevice::indexQuery([
            'city_relation_id__eq' => 2,
        ])->get();

        $this->assertCount(1, $results);
        $this->assertSame('terminal-b', $results->first()->terminal_id);
    }
}
