<?php

namespace Tests\Feature\ClientMonitor;

use App\Values\Iot\EventType_CMD;
use App\Values\Iot\EventType_CONN;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientMonitorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('client_sessions', function ($table): void {
            $table->string('client_id')->primary();
            $table->string('username')->nullable();
            $table->dateTime('last_event_ts')->nullable();
            $table->string('last_event_type')->nullable();
            $table->dateTime('last_connect_ts')->nullable();
            $table->dateTime('last_disconnect_ts')->nullable();
            $table->string('last_peer')->nullable();
            $table->string('last_protocol')->nullable();
            $table->integer('last_reason_code')->nullable();
            $table->text('extra')->nullable();
        });

        Schema::create('client_auth_events', function ($table): void {
            $table->increments('id');
            $table->dateTime('ts')->nullable();
            $table->string('result')->nullable();
            $table->string('reason')->nullable();
            $table->string('client_id')->nullable();
            $table->string('username')->nullable();
            $table->string('peer')->nullable();
            $table->string('protocol')->nullable();
        });

        Schema::create('client_cmd_events', function ($table): void {
            $table->increments('id');
            $table->dateTime('ts')->nullable();
            $table->string('event_type')->nullable();
            $table->string('client_id')->nullable();
            $table->string('username')->nullable();
            $table->string('peer')->nullable();
            $table->string('protocol')->nullable();
            $table->integer('reason_code')->nullable();
            $table->text('extra')->nullable();
        });

        Schema::create('client_conn_events', function ($table): void {
            $table->increments('id');
            $table->dateTime('ts')->nullable();
            $table->string('event_type')->nullable();
            $table->string('client_id')->nullable();
            $table->string('username')->nullable();
            $table->string('peer')->nullable();
            $table->string('protocol')->nullable();
            $table->integer('reason_code')->nullable();
            $table->text('extra')->nullable();
        });

        Schema::create('devices', function ($table): void {
            $table->string('terminal_id', 64)->primary();
            $table->string('dev_name', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('gps_position_last', function ($table): void {
            $table->string('terminal_id', 64)->primary();
            $table->dateTime('gps_time')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->decimal('latitude_gcj', 10, 6)->nullable();
            $table->decimal('longitude_gcj', 10, 6)->nullable();
            $table->integer('altitude')->nullable();
            $table->decimal('speed', 10, 2)->nullable();
            $table->integer('direction')->nullable();
            $table->integer('status')->nullable();
            $table->integer('alarm')->nullable();
            $table->text('extra')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('gps_position_histories', function ($table): void {
            $table->string('terminal_id', 64)->nullable();
            $table->dateTime('gps_time')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->decimal('latitude_gcj', 10, 6)->nullable();
            $table->decimal('longitude_gcj', 10, 6)->nullable();
            $table->integer('altitude')->nullable();
            $table->decimal('speed', 10, 2)->nullable();
            $table->integer('direction')->nullable();
            $table->integer('status')->nullable();
            $table->integer('alarm')->nullable();
            $table->text('extra')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    }

    public function test_removed_generic_client_monitor_entry_is_not_accessible(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        $this->actingAs($user)
            ->get('/admin/client-monitor')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/admin/client-monitor/device-overview')
            ->assertNotFound();
    }

    public function test_users_without_client_monitor_read_permission_cannot_view_the_module(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $this->actingAs($user)
            ->get('/admin/client-monitor/device-overview?client_id__eq=terminal-001')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/client-monitor/sessions')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/client-monitor/gps-position-last')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/client-monitor/gps-position-histories')
            ->assertForbidden();
    }

    public function test_sessions_page_supports_search_and_exact_match_filters(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('client_sessions')->insert([
            [
                'client_id' => 'client-alpha',
                'username' => 'alpha-user',
                'last_event_ts' => now(),
                'last_event_type' => 'connected',
                'last_connect_ts' => now(),
                'last_peer' => '10.0.0.1',
                'last_protocol' => 'mqtt',
                'last_reason_code' => 0,
                'extra' => json_encode(['os' => 'ios']),
            ],
            [
                'client_id' => 'client-beta',
                'username' => 'beta-user',
                'last_event_ts' => now(),
                'last_event_type' => 'disconnected',
                'last_disconnect_ts' => now(),
                'last_peer' => '10.0.0.2',
                'last_protocol' => 'ws',
                'last_reason_code' => 5,
                'extra' => json_encode(['os' => 'android']),
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/sessions?search__func=alpha&last_event_type__eq=connected&last_protocol__eq=mqtt')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/Sessions')
                ->where('pageMeta.href', '/admin/client-monitor/sessions')
                ->where('filters.search__func', 'alpha')
                ->where('filters.last_event_type__eq', 'connected')
                ->where('filters.last_protocol__eq', 'mqtt')
                ->has('sessions.data', 1)
                ->where('sessions.data.0.client_id', 'client-alpha'));
    }

    public function test_device_overview_page_only_contains_records_matching_the_terminal_id_context(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('client_sessions')->insert([
            [
                'client_id' => 'terminal-001',
                'username' => 'user-a',
                'last_event_ts' => now()->subMinute(),
                'last_event_type' => 'connected',
                'last_protocol' => 'mqtt',
            ],
            [
                'client_id' => 'terminal-001-secondary',
                'username' => 'another-user',
                'last_event_ts' => now(),
                'last_event_type' => 'disconnected',
                'last_protocol' => 'ws',
            ],
            [
                'client_id' => 'terminal-001-extra-2',
                'username' => 'terminal-001',
                'last_event_ts' => now()->subMinutes(3),
                'last_event_type' => 'connected',
                'last_protocol' => 'mqtt',
            ],
            [
                'client_id' => 'terminal-001-extra-3',
                'username' => 'terminal-001',
                'last_event_ts' => now()->subMinutes(4),
                'last_event_type' => 'connected',
                'last_protocol' => 'mqtt',
            ],
            [
                'client_id' => 'terminal-001-extra-4',
                'username' => 'terminal-001',
                'last_event_ts' => now()->subMinutes(5),
                'last_event_type' => 'connected',
                'last_protocol' => 'mqtt',
            ],
            [
                'client_id' => 'unrelated',
                'username' => 'someone-else',
                'last_event_ts' => now()->addMinute(),
                'last_event_type' => 'connected',
                'last_protocol' => 'mqtt',
            ],
        ]);

        \DB::table('client_auth_events')->insert([
            [
                'ts' => now(),
                'result' => 'success',
                'reason' => 'ok',
                'client_id' => 'terminal-001',
                'username' => 'user-a',
                'peer' => '127.0.0.1',
                'protocol' => 'mqtt',
            ],
            [
                'ts' => now()->addMinute(),
                'result' => 'failed',
                'reason' => 'unrelated',
                'client_id' => 'other-terminal',
                'username' => 'other-user',
                'peer' => '127.0.0.3',
                'protocol' => 'ws',
            ],
        ]);

        \DB::table('client_cmd_events')->insert([
            [
                'ts' => now(),
                'event_type' => EventType_CMD::CMD,
                'client_id' => 'terminal-001',
                'username' => 'user-a',
                'peer' => '127.0.1.1',
                'protocol' => 'mqtt',
                'reason_code' => 0,
                'extra' => json_encode(['step' => 1]),
            ],
            [
                'ts' => now()->addMinute(),
                'event_type' => EventType_CMD::CMD_ACK,
                'client_id' => 'nope',
                'username' => 'other-user',
                'peer' => '127.0.1.2',
                'protocol' => 'mqtt',
                'reason_code' => 1,
                'extra' => json_encode(['step' => 2]),
            ],
        ]);

        \DB::table('client_conn_events')->insert([
            [
                'ts' => now(),
                'event_type' => EventType_CONN::CONNECT,
                'client_id' => 'terminal-001',
                'username' => 'someone',
                'peer' => '127.0.2.1',
                'protocol' => 'mqtt',
                'reason_code' => 0,
                'extra' => json_encode(['node' => 'a']),
            ],
            [
                'ts' => now()->addMinute(),
                'event_type' => EventType_CONN::DISCONNECT,
                'client_id' => 'other',
                'username' => 'other-user',
                'peer' => '127.0.2.2',
                'protocol' => 'tcp',
                'reason_code' => 9,
                'extra' => json_encode(['node' => 'b']),
            ],
        ]);

        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-001',
            'dev_name' => 'Tracker 001',
            'created_at' => now(),
        ]);

        \DB::table('gps_position_last')->insert([
            [
                'terminal_id' => 'terminal-001',
                'gps_time' => now()->subSeconds(30),
                'latitude' => 30.123456,
                'longitude' => 120.123456,
                'latitude_gcj' => 30.223456,
                'longitude_gcj' => 120.223456,
                'altitude' => 20,
                'speed' => 45.50,
                'direction' => 180,
                'status' => 1,
                'alarm' => 0,
                'extra' => json_encode(['source' => 'gps']),
                'updated_at' => now(),
            ],
            [
                'terminal_id' => 'terminal-other',
                'gps_time' => now(),
                'latitude' => 31.123456,
                'longitude' => 121.123456,
                'latitude_gcj' => 31.223456,
                'longitude_gcj' => 121.223456,
                'altitude' => 10,
                'speed' => 25.00,
                'direction' => 90,
                'status' => 2,
                'alarm' => 3,
                'extra' => json_encode(['source' => 'other']),
                'updated_at' => now(),
            ],
        ]);

        \DB::table('gps_position_histories')->insert([
            [
                'terminal_id' => 'terminal-001',
                'gps_time' => now()->subMinutes(1),
                'latitude' => 30.123456,
                'longitude' => 120.123456,
                'latitude_gcj' => 30.223456,
                'longitude_gcj' => 120.223456,
                'altitude' => 21,
                'speed' => 40.10,
                'direction' => 175,
                'status' => 1,
                'alarm' => 0,
                'extra' => json_encode(['index' => 1]),
                'created_at' => now()->subMinutes(1),
            ],
            [
                'terminal_id' => 'terminal-other',
                'gps_time' => now(),
                'latitude' => 31.123456,
                'longitude' => 121.123456,
                'latitude_gcj' => 31.223456,
                'longitude_gcj' => 121.223456,
                'altitude' => 11,
                'speed' => 21.10,
                'direction' => 95,
                'status' => 2,
                'alarm' => 3,
                'extra' => json_encode(['index' => 2]),
                'created_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/device-overview?client_id__eq=terminal-001')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/Index')
                ->where('filters.client_id__eq', 'terminal-001')
                ->where('deviceContext.rootHref', '/admin/client-monitor/device-overview?client_id__eq=terminal-001')
                ->where('sections.0.href', '/admin/client-monitor/sessions?client_id__eq=terminal-001')
                ->where('sections.4.href', '/admin/client-monitor/gps-position-last?client_id__eq=terminal-001')
                ->where('sections.5.href', '/admin/client-monitor/gps-position-histories?client_id__eq=terminal-001')
                ->has('previews.sessions', 1)
                ->where('previews.sessions.0.client_id', 'terminal-001')
                ->where('previews.authEvents.0.client_id', 'terminal-001')
                ->where('previews.cmdEvents.0.client_id', 'terminal-001')
                ->where('previews.connEvents.0.client_id', 'terminal-001')
                ->where('previews.gpsPositionLast.terminal_id', 'terminal-001')
                ->where('previews.gpsPositionHistories.0.terminal_id', 'terminal-001'));
    }

    public function test_auth_events_page_supports_search_and_exact_match_filters(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('client_auth_events')->insert([
            [
                'ts' => now(),
                'result' => 'success',
                'reason' => 'pass',
                'client_id' => 'auth-alpha',
                'username' => 'alpha-user',
                'peer' => '127.0.0.1',
                'protocol' => 'mqtt',
            ],
            [
                'ts' => now(),
                'result' => 'failed',
                'reason' => 'bad password',
                'client_id' => 'auth-beta',
                'username' => 'beta-user',
                'peer' => '127.0.0.2',
                'protocol' => 'ws',
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/auth-events?search__func=password&result__eq=failed&protocol__eq=ws')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/AuthEvents')
                ->where('filters.search__func', 'password')
                ->where('filters.result__eq', 'failed')
                ->where('filters.protocol__eq', 'ws')
                ->has('authEvents.data', 1)
                ->where('authEvents.data.0.client_id', 'auth-beta'));
    }

    public function test_child_pages_keep_device_context_and_apply_exact_match_filtering(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('client_auth_events')->insert([
            [
                'ts' => now(),
                'result' => 'success',
                'reason' => 'pass',
                'client_id' => 'terminal-xyz',
                'username' => 'alpha-user',
                'peer' => '127.0.0.1',
                'protocol' => 'mqtt',
            ],
            [
                'ts' => now()->addMinutes(2),
                'result' => 'success',
                'reason' => 'pass',
                'client_id' => 'other-client',
                'username' => 'other-user',
                'peer' => '127.0.0.3',
                'protocol' => 'mqtt',
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/auth-events?client_id__eq=terminal-xyz&result__eq=success')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/AuthEvents')
                ->where('pageMeta.href', '/admin/client-monitor/auth-events?client_id__eq=terminal-xyz')
                ->where('filters.client_id__eq', 'terminal-xyz')
                ->where('sections.2.href', '/admin/client-monitor/cmd-events?client_id__eq=terminal-xyz')
                ->has('authEvents.data', 1)
                ->where('authEvents.data.0.client_id', 'terminal-xyz'));
    }

    public function test_cmd_events_page_supports_search_and_exact_match_filters(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('client_cmd_events')->insert([
            [
                'ts' => now(),
                'event_type' => EventType_CMD::CMD,
                'client_id' => 'cmd-alpha',
                'username' => 'alpha-user',
                'peer' => '192.168.1.1',
                'protocol' => 'mqtt',
                'reason_code' => 0,
                'extra' => json_encode(['flow' => 1]),
            ],
            [
                'ts' => now(),
                'event_type' => EventType_CMD::CMD_ACK,
                'client_id' => 'cmd-beta',
                'username' => 'beta-user',
                'peer' => '192.168.1.2',
                'protocol' => 'ws',
                'reason_code' => 7,
                'extra' => json_encode(['flow' => 2]),
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/cmd-events?search__func=beta&event_type__eq=cmd-ack&protocol__eq=ws')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/CmdEvents')
                ->where('filters.search__func', 'beta')
                ->where('filters.event_type__eq', 'cmd-ack')
                ->where('filters.protocol__eq', 'ws')
                ->has('cmdEvents.data', 1)
                ->where('cmdEvents.data.0.client_id', 'cmd-beta')
                ->where('cmdEvents.data.0.event_type_label', '命令执行'));
    }

    public function test_conn_events_page_supports_search_and_exact_match_filters(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('client_conn_events')->insert([
            [
                'ts' => now(),
                'event_type' => EventType_CONN::CONNECT,
                'client_id' => 'conn-alpha',
                'username' => 'alpha-user',
                'peer' => '172.16.0.1',
                'protocol' => 'mqtt',
                'reason_code' => 0,
                'extra' => json_encode(['broker' => 'a']),
            ],
            [
                'ts' => now(),
                'event_type' => EventType_CONN::DISCONNECT,
                'client_id' => 'conn-beta',
                'username' => 'beta-user',
                'peer' => '172.16.0.2',
                'protocol' => 'tcp',
                'reason_code' => 9,
                'extra' => json_encode(['broker' => 'b']),
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/conn-events?search__func=beta&event_type__eq=disconnect&protocol__eq=tcp')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/ConnEvents')
                ->where('filters.search__func', 'beta')
                ->where('filters.event_type__eq', 'disconnect')
                ->where('filters.protocol__eq', 'tcp')
                ->has('connEvents.data', 1)
                ->where('connEvents.data.0.client_id', 'conn-beta')
                ->where('connEvents.data.0.event_type_label', '断开'));
    }

    public function test_gps_position_last_page_supports_global_search_and_range_filters(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('devices')->insert([
            [
                'terminal_id' => 'terminal-alpha',
                'dev_name' => 'Alpha Tracker',
                'created_at' => now(),
            ],
            [
                'terminal_id' => 'terminal-beta',
                'dev_name' => 'Beta Sensor',
                'created_at' => now(),
            ],
        ]);

        \DB::table('gps_position_last')->insert([
            [
                'terminal_id' => 'terminal-alpha',
                'gps_time' => '2026-03-26 09:30:00',
                'latitude' => 30.100000,
                'longitude' => 120.100000,
                'latitude_gcj' => 30.200000,
                'longitude_gcj' => 120.200000,
                'altitude' => 15,
                'speed' => 18.00,
                'direction' => 45,
                'status' => 1,
                'alarm' => 0,
                'extra' => json_encode(['tag' => 'alpha']),
                'updated_at' => '2026-03-26 09:31:00',
            ],
            [
                'terminal_id' => 'terminal-beta',
                'gps_time' => '2026-03-26 10:15:00',
                'latitude' => 31.100000,
                'longitude' => 121.100000,
                'latitude_gcj' => 31.200000,
                'longitude_gcj' => 121.200000,
                'altitude' => 22,
                'speed' => 30.00,
                'direction' => 90,
                'status' => 2,
                'alarm' => 9,
                'extra' => json_encode(['tag' => 'beta']),
                'updated_at' => '2026-03-26 10:16:00',
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/gps-position-last?search__func=beta&status__eq=2&alarm__eq=9&gps_time__gte=2026-03-26T10:00&gps_time__lte=2026-03-26T10:15')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/GpsPositionLast')
                ->where('pageMeta.href', '/admin/client-monitor/gps-position-last')
                ->where('filters.search__func', 'beta')
                ->where('filters.status__eq', '2')
                ->where('filters.alarm__eq', '9')
                ->where('filters.gps_time__gte', '2026-03-26T10:00')
                ->where('filters.gps_time__lte', '2026-03-26T10:15')
                ->has('gpsPositionLast.data', 1)
                ->where('gpsPositionLast.data.0.terminal_id', 'terminal-beta')
                ->where('gpsPositionLast.data.0.device.dev_name', 'Beta Sensor'));
    }

    public function test_gps_position_pages_keep_device_context_and_apply_exact_match_filtering(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        \DB::table('devices')->insert([
            [
                'terminal_id' => 'terminal-xyz',
                'dev_name' => 'XYZ Tracker',
                'created_at' => now(),
            ],
            [
                'terminal_id' => 'terminal-other',
                'dev_name' => 'Other Tracker',
                'created_at' => now(),
            ],
        ]);

        \DB::table('gps_position_last')->insert([
            [
                'terminal_id' => 'terminal-xyz',
                'gps_time' => '2026-03-26 08:30:00',
                'latitude' => 30.300000,
                'longitude' => 120.300000,
                'latitude_gcj' => 30.400000,
                'longitude_gcj' => 120.400000,
                'altitude' => 12,
                'speed' => 26.00,
                'direction' => 100,
                'status' => 1,
                'alarm' => 0,
                'extra' => json_encode(['tag' => 'xyz']),
                'updated_at' => '2026-03-26 08:31:00',
            ],
            [
                'terminal_id' => 'terminal-other',
                'gps_time' => '2026-03-26 09:30:00',
                'latitude' => 31.300000,
                'longitude' => 121.300000,
                'latitude_gcj' => 31.400000,
                'longitude_gcj' => 121.400000,
                'altitude' => 18,
                'speed' => 35.00,
                'direction' => 120,
                'status' => 1,
                'alarm' => 0,
                'extra' => json_encode(['tag' => 'other']),
                'updated_at' => '2026-03-26 09:31:00',
            ],
        ]);

        \DB::table('gps_position_histories')->insert([
            [
                'terminal_id' => 'terminal-xyz',
                'gps_time' => '2026-03-26 08:00:00',
                'latitude' => 30.300000,
                'longitude' => 120.300000,
                'latitude_gcj' => 30.400000,
                'longitude_gcj' => 120.400000,
                'altitude' => 11,
                'speed' => 25.00,
                'direction' => 95,
                'status' => 1,
                'alarm' => 0,
                'extra' => json_encode(['tag' => 'xyz-history']),
                'created_at' => '2026-03-26 08:00:05',
            ],
            [
                'terminal_id' => 'terminal-other',
                'gps_time' => '2026-03-26 09:00:00',
                'latitude' => 31.300000,
                'longitude' => 121.300000,
                'latitude_gcj' => 31.400000,
                'longitude_gcj' => 121.400000,
                'altitude' => 20,
                'speed' => 38.00,
                'direction' => 130,
                'status' => 1,
                'alarm' => 0,
                'extra' => json_encode(['tag' => 'other-history']),
                'created_at' => '2026-03-26 09:00:05',
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/client-monitor/gps-position-last?client_id__eq=terminal-xyz')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/GpsPositionLast')
                ->where('pageMeta.href', '/admin/client-monitor/gps-position-last?client_id__eq=terminal-xyz')
                ->where('filters.client_id__eq', 'terminal-xyz')
                ->where('deviceContext.rootHref', '/admin/client-monitor/device-overview?client_id__eq=terminal-xyz')
                ->where('sections.5.href', '/admin/client-monitor/gps-position-histories?client_id__eq=terminal-xyz')
                ->has('gpsPositionLast.data', 1)
                ->where('gpsPositionLast.data.0.terminal_id', 'terminal-xyz'));

        $this->actingAs($user)
            ->get('/admin/client-monitor/gps-position-histories?client_id__eq=terminal-xyz&status__eq=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientMonitor/GpsPositionHistories')
                ->where('pageMeta.href', '/admin/client-monitor/gps-position-histories?client_id__eq=terminal-xyz')
                ->where('filters.client_id__eq', 'terminal-xyz')
                ->where('filters.status__eq', '1')
                ->where('sections.4.href', '/admin/client-monitor/gps-position-last?client_id__eq=terminal-xyz')
                ->has('gpsPositionHistories.data', 1)
                ->where('gpsPositionHistories.data.0.terminal_id', 'terminal-xyz'));
    }
}
