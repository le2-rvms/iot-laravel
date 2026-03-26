<?php

namespace Tests\Feature\Devices;

use App\Models\Iot\IotDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
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
            $table->string('terminal_id', 64)->primary();
            $table->integer('dev_id')->nullable();
            $table->string('dev_name', 255);
            $table->string('company_id', 64)->nullable();
            $table->string('manufacturer_id', 64)->nullable();
            $table->string('product_key', 64)->nullable();
            $table->string('sim_number', 64)->nullable();
            $table->string('_vehicle_plate', 64)->nullable();
            $table->string('_vehicle_vin', 64)->nullable();
            $table->string('_bind_status', 64)->nullable();
            $table->string('device_status', 64)->nullable();
            $table->string('review_status', 64)->nullable();
            $table->string('auth_code_seed', 255)->nullable();
            $table->dateTime('auth_code_issued_at')->nullable();
            $table->dateTime('auth_code_expires_at')->nullable();
            $table->integer('auth_failures')->nullable();
            $table->dateTime('auth_block_until')->nullable();
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

    public function test_users_with_read_permission_can_view_the_devices_index(): void
    {
        $user = $this->createUserWithPermissions(['device.read']);

        \DB::table('device_products')->insert([
            'product_key' => 'PK-ALPHA',
            'product_name' => 'Alpha Tracker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-001',
            'dev_id' => 1,
            'dev_name' => 'Device 001',
            'product_key' => 'PK-ALPHA',
            'sim_number' => 'SIM-001',
            '_vehicle_plate' => '沪A00001',
            '_vehicle_vin' => 'VIN-001',
            '_bind_status' => 'bound',
            'device_status' => 'online',
            'review_status' => 'approved',
            'city_relation_id' => 10,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Device/Index')
                ->where('filters', [])
                ->where('devices.data.0.terminal_id', 'terminal-001')
                ->where('devices.data.0.device_product.product_name', 'Alpha Tracker'));
    }

    public function test_users_with_write_permission_can_create_update_and_delete_devices(): void
    {
        $user = $this->createUserWithPermissions(['device.write']);

        $this->actingAs($user)
            ->post(route('devices.store'), [
                'terminal_id' => 'terminal-create',
                'dev_name' => 'Created Device',
                'company_id' => 'company-1',
                'manufacturer_id' => 'maker-1',
                'product_key' => 'PK-CREATE',
                'sim_number' => 'SIM-123',
                '_vehicle_plate' => '粤B12345',
                '_vehicle_vin' => 'VIN-CREATE',
                '_bind_status' => 'bound',
                'device_status' => 'online',
                'review_status' => 'approved',
                'auth_code_seed' => 'seed-created',
                'auth_code_issued_at' => '2026-03-25T10:00',
                'auth_code_expires_at' => '2026-03-25T12:00',
                'auth_failures' => 2,
                'auth_block_until' => '2026-03-26T10:00',
                'city_relation_id' => 99,
            ])
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas('success', '设备已创建。');

        $device = IotDevice::query()->findOrFail('terminal-create');

        $this->assertSame('Created Device', $device->dev_name);
        $this->assertSame('seed-created', $device->auth_code_seed);

        $this->actingAs($user)
            ->put(route('devices.update', 'terminal-create'), [
                'terminal_id' => 'terminal-changed',
                'dev_name' => 'Updated Device',
                'company_id' => 'company-2',
                'manufacturer_id' => 'maker-2',
                'product_key' => 'PK-UPDATED',
                'sim_number' => 'SIM-999',
                '_vehicle_plate' => '粤B99999',
                '_vehicle_vin' => 'VIN-UPDATED',
                '_bind_status' => 'unbound',
                'device_status' => 'offline',
                'review_status' => 'rejected',
                'auth_code_seed' => 'seed-updated',
                'auth_code_issued_at' => '2026-03-26T08:00',
                'auth_code_expires_at' => '2026-03-26T10:00',
                'auth_failures' => 3,
                'auth_block_until' => '2026-03-27T08:00',
                'city_relation_id' => 100,
            ])
            ->assertRedirect(route('devices.edit', 'terminal-create'))
            ->assertSessionHas('success', '设备已更新。');

        $device = IotDevice::query()->findOrFail('terminal-create');

        $this->assertSame('terminal-create', $device->terminal_id);
        $this->assertSame('Updated Device', $device->dev_name);
        $this->assertSame('company-2', $device->company_id);
        $this->assertSame('maker-2', $device->manufacturer_id);
        $this->assertSame('PK-UPDATED', $device->product_key);
        $this->assertSame('SIM-999', $device->sim_number);
        $this->assertSame('offline', $device->device_status);
        $this->assertSame('rejected', $device->review_status);
        $this->assertSame('seed-updated', $device->auth_code_seed);
        $this->assertSame(3, $device->auth_failures);
        $this->assertSame(100, $device->city_relation_id);
        $this->assertDatabaseHas('devices', [
            'terminal_id' => 'terminal-create',
            '_vehicle_plate' => '粤B99999',
            '_vehicle_vin' => 'VIN-UPDATED',
            '_bind_status' => 'unbound',
        ]);

        $this->actingAs($user)
            ->delete(route('devices.destroy', 'terminal-create'))
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas('success', '设备已删除。');

        $this->assertDatabaseMissing('devices', [
            'terminal_id' => 'terminal-create',
        ]);
    }

    public function test_devices_index_exposes_client_monitor_access_for_event_links(): void
    {
        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-monitor',
            'dev_id' => 30,
            'dev_name' => 'Monitor Device',
            'created_at' => now(),
        ]);

        $withMonitorAccess = $this->createUserWithPermissions(['device.read', 'client-monitor.read']);

        $this->actingAs($withMonitorAccess)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Device/Index')
                ->where('auth.access', fn ($access) => ($access['client-monitor.read'] ?? false) === true
                    && ($access['device.write'] ?? false) === false)
                ->where('devices.data.0.terminal_id', 'terminal-monitor'));

        $withoutMonitorAccess = $this->createUserWithPermissions(['device.read']);

        $this->actingAs($withoutMonitorAccess)
            ->get(route('devices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Device/Index')
                ->where('auth.access', fn ($access) => ($access['client-monitor.read'] ?? false) === false)
                ->where('devices.data.0.terminal_id', 'terminal-monitor'));
    }

    public function test_devices_support_search_and_exact_match_filters(): void
    {
        $user = $this->createUserWithPermissions(['device.read']);

        \DB::table('devices')->insert([
            [
                'terminal_id' => 'terminal-alpha',
                'dev_id' => 11,
                'dev_name' => 'Alpha Device',
                'product_key' => 'PK-FILTER',
                'device_status' => 'online',
                'review_status' => 'approved',
                'city_relation_id' => 1,
                'created_at' => now(),
            ],
            [
                'terminal_id' => 'terminal-beta',
                'dev_id' => 12,
                'dev_name' => 'Beta Device',
                'product_key' => 'PK-OTHER',
                'device_status' => 'offline',
                'review_status' => 'pending',
                'city_relation_id' => 2,
                'created_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->get(route('devices.index', [
                'search__func' => 'alpha',
                'product_key__eq' => 'PK-FILTER',
                'device_status__eq' => 'online',
                'review_status__eq' => 'approved',
                'city_relation_id__eq' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Device/Index')
                ->where('filters.search__func', 'alpha')
                ->where('filters.product_key__eq', 'PK-FILTER')
                ->where('filters.device_status__eq', 'online')
                ->where('filters.review_status__eq', 'approved')
                ->where('filters.city_relation_id__eq', '1')
                ->has('devices.data', 1)
                ->where('devices.data.0.terminal_id', 'terminal-alpha'));
    }

    public function test_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['device.write']);

        $this->actingAs($user)
            ->from(route('devices.create'))
            ->post(route('devices.store'), [
                'terminal_id' => '',
                'dev_name' => '',
                'company_id' => str_repeat('c', 65),
                'auth_failures' => -1,
                'city_relation_id' => 'invalid',
            ])
            ->assertRedirect(route('devices.create'))
            ->assertSessionHasErrors(['terminal_id', 'dev_name', 'company_id', 'auth_failures', 'city_relation_id']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('终端ID 不能为空。', $errors->first('terminal_id'));
        $this->assertSame('设备名称 不能为空。', $errors->first('dev_name'));
    }

    public function test_devices_cannot_be_deleted_when_gps_commands_still_reference_them(): void
    {
        $user = $this->createUserWithPermissions(['device.write']);

        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-linked',
            'dev_id' => 55,
            'dev_name' => 'Linked Device',
            'created_at' => now(),
        ]);
        \DB::table('gps_commands')->insert([
            'device_id' => 55,
            'terminal_id' => 'terminal-linked',
            'cmd_type' => 'lock',
            'payload' => '{}',
            'flow_id' => 1,
            'status' => 'queued',
            'retries' => 0,
            'max_retries' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('devices.destroy', 'terminal-linked'))
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas('error', '该设备仍有关联指令记录，无法删除。');

        $this->assertDatabaseHas('devices', [
            'terminal_id' => 'terminal-linked',
        ]);
    }
}
