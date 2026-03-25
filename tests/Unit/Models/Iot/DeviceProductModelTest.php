<?php

namespace Tests\Unit\Models\Iot;

use App\Models\Iot\IotDeviceGroup;
use App\Models\Iot\IotDeviceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeviceProductModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('device_products', function ($table): void {
            $table->increments('product_id');
            $table->string('product_key', 64)->unique();
            $table->string('product_name', 255);
            $table->text('description')->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('protocol', 64)->nullable();
            $table->string('category', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('devices', function ($table): void {
            $table->string('terminal_id')->primary();
            $table->integer('dev_id')->nullable();
            $table->string('dev_name')->nullable();
            $table->string('company_id')->nullable();
            $table->string('product_key')->nullable();
            $table->string('sim_number')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('device_groups', function ($table): void {
            $table->increments('group_id');
            $table->string('group_name');
            $table->text('description')->nullable();
            $table->string('product_key');
            $table->timestamps();
        });

        config()->set('app.company_id', 'company-1');
    }

    public function test_groups_relation_points_to_device_group_model(): void
    {
        $this->assertInstanceOf(IotDeviceGroup::class, (new IotDeviceProduct)->groups()->getRelated());
    }

    public function test_index_query_includes_device_and_group_counts(): void
    {
        $product = IotDeviceProduct::query()->create([
            'product_key' => 'PK-COUNT',
            'product_name' => 'Count Product',
        ]);

        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-count',
            'dev_id' => 3,
            'dev_name' => 'Count Device',
            'company_id' => 'company-1',
            'product_key' => 'PK-COUNT',
            'created_at' => now(),
        ]);
        \DB::table('device_groups')->insert([
            'group_name' => 'Count Group',
            'product_key' => 'PK-COUNT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = IotDeviceProduct::indexQuery([])->findOrFail($product->product_id);

        $this->assertSame(1, $product->devices_count);
        $this->assertSame(1, $product->groups_count);
    }

}
