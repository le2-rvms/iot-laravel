<?php

namespace Tests\Feature\DeviceProducts;

use App\Models\Iot\IotDeviceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeviceProductManagementTest extends TestCase
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

    public function test_users_with_read_permission_can_view_the_device_products_index(): void
    {
        $user = $this->createUserWithPermissions(['device-product.read']);

        IotDeviceProduct::query()->create([
            'product_key' => 'PK-ALPHA',
            'product_name' => 'Alpha Tracker',
            'manufacturer' => 'RVMS',
            'protocol' => 'JT808',
            'category' => 'tracker',
        ]);

        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-001',
            'dev_id' => 1,
            'dev_name' => 'Device 001',
            'company_id' => 'company-1',
            'product_key' => 'PK-ALPHA',
            'created_at' => now(),
        ]);

        \DB::table('device_groups')->insert([
            'group_name' => 'Alpha Group',
            'product_key' => 'PK-ALPHA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('device-products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DeviceProduct/Index')
                ->where('filters', [])
                ->where('products.data.0.product_key', 'PK-ALPHA')
                ->where('products.data.0.devices_count', 1)
                ->where('products.data.0.groups_count', 1));
    }

    public function test_users_with_write_permission_can_create_update_and_delete_device_products(): void
    {
        $user = $this->createUserWithPermissions(['device-product.write']);

        $this->actingAs($user)
            ->post(route('device-products.store'), [
                'product_key' => 'PK-BETA',
                'product_name' => 'Beta Tracker',
                'description' => 'Outdoor unit',
                'manufacturer' => 'RVMS',
                'protocol' => 'JT808',
                'category' => 'tracker',
            ])
            ->assertRedirect(route('device-products.index'));

        $product = IotDeviceProduct::query()->where('product_key', 'PK-BETA')->firstOrFail();

        $this->actingAs($user)
            ->put(route('device-products.update', $product), [
                'product_key' => 'PK-BETA-CHANGED',
                'product_name' => 'Beta Tracker Updated',
                'description' => 'Indoor unit',
                'manufacturer' => 'RVMS 2',
                'protocol' => 'JT809',
                'category' => 'gateway',
            ])
            ->assertRedirect(route('device-products.edit', $product));

        $product->refresh();

        $this->assertSame('PK-BETA', $product->product_key);
        $this->assertSame('Beta Tracker Updated', $product->product_name);
        $this->assertSame('Indoor unit', $product->description);
        $this->assertSame('RVMS 2', $product->manufacturer);
        $this->assertSame('JT809', $product->protocol);
        $this->assertSame('gateway', $product->category);

        $this->actingAs($user)
            ->delete(route('device-products.destroy', $product))
            ->assertRedirect(route('device-products.index'));

        $this->assertDatabaseMissing('device_products', [
            'product_id' => $product->product_id,
        ]);
    }

    public function test_device_products_support_case_insensitive_search(): void
    {
        $user = $this->createUserWithPermissions(['device-product.read']);

        IotDeviceProduct::query()->create([
            'product_key' => 'PK-Gateway-A',
            'product_name' => 'Gateway A',
            'manufacturer' => 'Acme',
        ]);
        IotDeviceProduct::query()->create([
            'product_key' => 'PK-Sensor-B',
            'product_name' => 'Sensor B',
            'manufacturer' => 'Other',
        ]);

        $this->actingAs($user)
            ->get(route('device-products.index', ['search__func' => 'gateway']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DeviceProduct/Index')
                ->where('filters.search__func', 'gateway')
                ->has('products.data', 1)
                ->where('products.data.0.product_key', 'PK-Gateway-A'));
    }

    public function test_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['device-product.write']);

        $this->actingAs($user)
            ->from(route('device-products.create'))
            ->post(route('device-products.store'), [
                'product_key' => '',
                'product_name' => '',
                'manufacturer' => str_repeat('x', 256),
                'protocol' => str_repeat('y', 65),
                'category' => str_repeat('z', 65),
            ])
            ->assertRedirect(route('device-products.create'))
            ->assertSessionHasErrors(['product_key', 'product_name', 'manufacturer', 'protocol', 'category']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('产品标识 不能为空。', $errors->first('product_key'));
        $this->assertSame('产品名称 不能为空。', $errors->first('product_name'));
    }

    public function test_products_cannot_be_deleted_when_devices_or_groups_still_reference_them(): void
    {
        $user = $this->createUserWithPermissions(['device-product.write']);

        $product = IotDeviceProduct::query()->create([
            'product_key' => 'PK-LINKED',
            'product_name' => 'Linked Product',
        ]);

        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-002',
            'dev_id' => 2,
            'dev_name' => 'Device 002',
            'company_id' => 'company-1',
            'product_key' => 'PK-LINKED',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('device-products.destroy', $product))
            ->assertRedirect(route('device-products.index'))
            ->assertSessionHas('error', '该设备产品仍有关联设备，无法删除。');

        \DB::table('devices')->where('terminal_id', 'terminal-002')->delete();
        \DB::table('device_groups')->insert([
            'group_name' => 'Linked Group',
            'product_key' => 'PK-LINKED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('device-products.destroy', $product))
            ->assertRedirect(route('device-products.index'))
            ->assertSessionHas('error', '该设备产品仍有关联分组，无法删除。');
    }
}
