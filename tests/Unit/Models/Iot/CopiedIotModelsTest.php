<?php

namespace Tests\Unit\Models\Iot;

use App\Models\Audit;
use App\Models\Auth\AdminUser;
use App\Models\Iot\IotClientAuthEvent;
use App\Models\Iot\IotClientCmdEvent;
use App\Models\Iot\IotClientConnEvent;
use App\Models\Iot\IotClientSession;
use App\Models\Iot\IotDevice;
use App\Models\Iot\IotDeviceGroup;
use App\Models\Iot\IotDeviceGroupMapping;
use App\Models\Iot\IotDeviceProduct;
use App\Models\Iot\IotGpsAlarm;
use App\Models\Iot\IotGpsCommand;
use App\Models\Iot\IotGpsGeofence;
use App\Models\Iot\IotGpsPositionHistory;
use App\Models\Iot\IotGpsPositionLast;
use App\Models\Iot\IotMqttAccount;
use App\Values\Iot\EventType_CMD;
use App\Values\Iot\EventType_CONN;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CopiedIotModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_copied_iot_models_expose_translated_attribute_labels(): void
    {
        $this->assertSame('设备名称', IotDevice::attributeLabels()['dev_name']);
        $this->assertSame('命令类型', IotClientCmdEvent::attributeLabels()['event_type_label']);

        app()->setLocale('en');

        $this->assertSame('Device Name', IotDevice::attributeLabels()['dev_name']);
        $this->assertSame('Command Type', IotClientCmdEvent::attributeLabels()['event_type_label']);

        app()->setLocale('zh_CN');
    }

    public function test_client_event_models_cast_event_type_and_expose_label(): void
    {
        $cmdEvent = new IotClientCmdEvent;
        $cmdEvent->setRawAttributes([
            'event_type' => EventType_CMD::CMD,
        ], true);

        $connEvent = new IotClientConnEvent;
        $connEvent->setRawAttributes([
            'event_type' => EventType_CONN::CONNECT,
        ], true);

        $this->assertInstanceOf(EventType_CMD::class, $cmdEvent->event_type);
        $this->assertSame('命令下发', $cmdEvent->event_type_label);
        $this->assertInstanceOf(EventType_CONN::class, $connEvent->event_type);
        $this->assertSame('连接', $connEvent->event_type_label);
    }

    public function test_copied_iot_models_expose_index_query_builders(): void
    {
        foreach ($this->iotModelClasses() as $modelClass) {
            $this->assertInstanceOf(Builder::class, $modelClass::indexQuery([]));
        }
    }

    public function test_copied_iot_model_relations_point_to_current_project_models(): void
    {
        $this->assertInstanceOf(IotDeviceProduct::class, (new IotDevice)->deviceProduct()->getRelated());
        $this->assertInstanceOf(IotDeviceProduct::class, (new IotDeviceGroup)->product()->getRelated());
        $this->assertInstanceOf(IotDeviceGroupMapping::class, (new IotDeviceGroup)->mappings()->getRelated());
        $this->assertInstanceOf(IotMqttAccount::class, (new IotDeviceGroupMapping)->mqttAccount()->getRelated());
        $this->assertInstanceOf(IotDevice::class, (new IotDeviceProduct)->devices()->getRelated());
        $this->assertInstanceOf(IotDevice::class, (new IotGpsCommand)->device()->getRelated());
    }

    public function test_model_support_can_save_copied_iot_models_without_updated_by_columns(): void
    {
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

        Schema::create('devices', function ($table): void {
            $table->string('terminal_id')->primary();
            $table->integer('dev_id')->nullable();
            $table->string('dev_name')->nullable();
            $table->string('company_id')->nullable();
            $table->string('product_key')->nullable();
            $table->string('sim_number')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        config()->set('app.company_id', 'company-1');

        $this->actingAs(AdminUser::factory()->create());

        $clientCmdEvent = new IotClientCmdEvent([
            'ts' => now(),
            'event_type' => EventType_CMD::CMD,
            'client_id' => 'client-001',
        ]);
        $clientCmdEvent->save();

        $device = new IotDevice([
            'terminal_id' => 'terminal-001',
            'dev_name' => 'Tracker 001',
            'company_id' => 'company-1',
        ]);
        $device->save();

        $this->assertDatabaseHas('client_cmd_events', [
            'client_id' => 'client-001',
        ]);
        $this->assertDatabaseHas('devices', [
            'terminal_id' => 'terminal-001',
        ]);
        $this->assertNotNull(Audit::query()->where('auditable_type', IotClientCmdEvent::class)->first());
        $this->assertNotNull(Audit::query()->where('auditable_type', IotDevice::class)->first());
    }

    /**
     * @return array<int, class-string>
     */
    protected function iotModelClasses(): array
    {
        return [
            IotClientAuthEvent::class,
            IotClientCmdEvent::class,
            IotClientConnEvent::class,
            IotClientSession::class,
            IotDevice::class,
            IotDeviceGroup::class,
            IotDeviceGroupMapping::class,
            IotDeviceProduct::class,
            IotGpsAlarm::class,
            IotGpsCommand::class,
            IotGpsGeofence::class,
            IotGpsPositionHistory::class,
            IotGpsPositionLast::class,
        ];
    }
}
