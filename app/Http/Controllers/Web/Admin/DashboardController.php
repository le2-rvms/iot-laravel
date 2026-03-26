<?php

namespace App\Http\Controllers\Web\Admin;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Models\Admin\AdminUser;
use App\Models\Iot\IotClientSession;
use App\Models\Iot\IotDevice;
use App\Models\Iot\IotGpsAlarm;
use App\Models\Iot\IotGpsCommand;
use App\Models\Iot\IotGpsPositionLast;
use App\Models\Iot\IotMqttAccount;
use App\Support\NavigationRegistry;
use App\Values\Iot\Enabled;
use Inertia\Response;
use Illuminate\Support\Facades\Schema;
use stdClass;

#[PermissionGroup]
class DashboardController extends Controller
{
    #[PermissionAction('read')]
    public function __invoke(): Response
    {
        $user = request()->user();
        $deviceTableExists = Schema::hasTable('devices');
        $alarmThreshold = now()->subDay();

        $metrics = [
            'devices' => $this->countIfTableExists('devices', fn (): int => IotDevice::query()->count()),
            'sessions' => $this->countIfTableExists('client_sessions', fn (): int => IotClientSession::query()->count()),
            'positions' => $this->countIfTableExists('gps_position_last', fn (): int => IotGpsPositionLast::query()->count()),
            'gps_alarms_24h' => $this->countIfTableExists(
                'gps_alarms',
                fn (): int => IotGpsAlarm::query()->where('gps_time', '>=', $alarmThreshold)->count(),
            ),
            'gps_commands' => $this->countIfTableExists('gps_commands', fn (): int => IotGpsCommand::query()->count()),
            'mqtt_enabled' => $this->countIfTableExists(
                'mqtt_accounts',
                fn (): int => IotMqttAccount::query()->where('enabled', Enabled::ENABLED)->count(),
            ),
        ];

        return $this->renderPage([
            'hero' => [
                'userName' => $user?->name ?? '',
                'primaryActions' => [
                    [
                        'label' => '进入设备管理',
                        'routeName' => 'devices.index',
                        'routeParams' => new stdClass,
                        'routeQuery' => new stdClass,
                    ],
                    [
                        'label' => '进入客户端监控',
                        'routeName' => 'client-monitor.sessions',
                        'routeParams' => new stdClass,
                        'routeQuery' => new stdClass,
                    ],
                ],
                'summary' => [
                    [
                        'label' => '24h GPS告警',
                        'value' => $metrics['gps_alarms_24h'],
                        'tone' => $metrics['gps_alarms_24h'] > 0 ? 'destructive' : 'secondary',
                    ],
                    [
                        'label' => '在线会话',
                        'value' => $metrics['sessions'],
                        'tone' => 'default',
                    ],
                    [
                        'label' => '定位覆盖',
                        'value' => $metrics['positions'],
                        'tone' => 'outline',
                    ],
                ],
            ],
            'kpis' => [
                [
                    'key' => 'devices',
                    'title' => '设备总数',
                    'value' => $metrics['devices'],
                    'description' => '当前系统内登记的设备总量。',
                    'routeName' => 'devices.index',
                    'routeParams' => new stdClass,
                    'routeQuery' => new stdClass,
                ],
                [
                    'key' => 'sessions',
                    'title' => '在线会话',
                    'value' => $metrics['sessions'],
                    'description' => '当前可见的客户端在线会话快照。',
                    'routeName' => 'client-monitor.sessions',
                    'routeParams' => new stdClass,
                    'routeQuery' => new stdClass,
                ],
                [
                    'key' => 'positions',
                    'title' => '定位覆盖',
                    'value' => $metrics['positions'],
                    'description' => '已上报当前定位的终端覆盖数。',
                    'routeName' => 'client-monitor.gps-position-last',
                    'routeParams' => new stdClass,
                    'routeQuery' => new stdClass,
                ],
                [
                    'key' => 'gps_alarms_24h',
                    'title' => '24h GPS告警',
                    'value' => $metrics['gps_alarms_24h'],
                    'description' => '最近 24 小时内累计触发的 GPS 告警。',
                    'routeName' => 'client-monitor.gps-position-histories',
                    'routeParams' => new stdClass,
                    'routeQuery' => new stdClass,
                ],
                [
                    'key' => 'gps_commands',
                    'title' => 'GPS命令总数',
                    'value' => $metrics['gps_commands'],
                    'description' => '累计入库的 GPS 指令记录总量。',
                    'routeName' => 'client-monitor.cmd-events',
                    'routeParams' => new stdClass,
                    'routeQuery' => new stdClass,
                ],
                [
                    'key' => 'mqtt_enabled',
                    'title' => '已启用MQTT账号',
                    'value' => $metrics['mqtt_enabled'],
                    'description' => '当前处于启用状态的 MQTT 连接账号数。',
                    'routeName' => 'mqtt-accounts.index',
                    'routeParams' => new stdClass,
                    'routeQuery' => new stdClass,
                ],
            ],
            'alertFeeds' => [
                'gpsAlarms' => $this->gpsAlarmFeed(),
                'gpsCommands' => $this->gpsCommandFeed($deviceTableExists),
            ],
            'snapshots' => [
                'sessions' => $this->sessionSnapshots(),
                'positions' => $this->positionSnapshots($deviceTableExists),
            ],
            'quickLinks' => $this->quickLinksFor($user),
        ]);
    }

    private function countIfTableExists(string $table, callable $resolver): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) $resolver();
    }

    /**
     * @return array<int, array{terminal_id: string, alarm_type: string, description: string, gps_time: string, created_at: string}>
     */
    private function gpsAlarmFeed(): array
    {
        if (! Schema::hasTable('gps_alarms')) {
            return [];
        }

        return IotGpsAlarm::query()
            ->latest('gps_time')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (IotGpsAlarm $alarm): array => [
                'terminal_id' => $alarm->terminal_id,
                'alarm_type' => $alarm->alarm_type,
                'description' => (string) ($alarm->description ?? ''),
                'gps_time' => $alarm->gps_time?->toDateTimeString() ?? '',
                'created_at' => $alarm->created_at?->toDateTimeString() ?? '',
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, terminal_id: string, device_name: string, cmd_type: string, status: string, updated_at: string, created_at: string}>
     */
    private function gpsCommandFeed(bool $deviceTableExists): array
    {
        if (! Schema::hasTable('gps_commands')) {
            return [];
        }

        $query = IotGpsCommand::query()
            ->latest('updated_at')
            ->latest('id')
            ->limit(5);

        if ($deviceTableExists) {
            $query->with('device');
        }

        return $query->get()
            ->map(fn (IotGpsCommand $command): array => [
                'id' => (int) $command->id,
                'terminal_id' => $command->terminal_id,
                'device_name' => $deviceTableExists ? (string) ($command->device?->dev_name ?? '') : '',
                'cmd_type' => $command->cmd_type,
                'status' => $command->status,
                'updated_at' => $command->updated_at?->toDateTimeString() ?? '',
                'created_at' => $command->created_at?->toDateTimeString() ?? '',
            ])
            ->all();
    }

    /**
     * @return array<int, array{client_id: string, username: string, last_event_type: string, last_event_ts: string, last_protocol: string}>
     */
    private function sessionSnapshots(): array
    {
        if (! Schema::hasTable('client_sessions')) {
            return [];
        }

        return IotClientSession::query()
            ->latest('last_event_ts')
            ->limit(5)
            ->get()
            ->map(fn (IotClientSession $session): array => [
                'client_id' => $session->client_id,
                'username' => (string) ($session->username ?? ''),
                'last_event_type' => (string) ($session->last_event_type ?? ''),
                'last_event_ts' => $session->last_event_ts?->toDateTimeString() ?? '',
                'last_protocol' => (string) ($session->last_protocol ?? ''),
            ])
            ->all();
    }

    /**
     * @return array<int, array{terminal_id: string, device_name: string, gps_time: string, status: int|null, alarm: int|null, speed: float|null, updated_at: string}>
     */
    private function positionSnapshots(bool $deviceTableExists): array
    {
        if (! Schema::hasTable('gps_position_last')) {
            return [];
        }

        $query = IotGpsPositionLast::query()
            ->latest('updated_at')
            ->limit(5);

        if ($deviceTableExists) {
            $query->with('device');
        }

        return $query->get()
            ->map(fn (IotGpsPositionLast $position): array => [
                'terminal_id' => $position->terminal_id,
                'device_name' => $deviceTableExists ? (string) ($position->device?->dev_name ?? '') : '',
                'gps_time' => $position->gps_time?->toDateTimeString() ?? '',
                'status' => $position->status,
                'alarm' => $position->alarm,
                'speed' => $position->speed,
                'updated_at' => $position->updated_at?->toDateTimeString() ?? '',
            ])
            ->all();
    }

    /**
     * @param  AdminUser|null  $user
     * @return array<int, array{title: string, description: string, routeName: string, routeParams: stdClass, routeQuery: stdClass}>
     */
    private function quickLinksFor(?AdminUser $user): array
    {
        $priority = [
            'devices.index' => 0,
            'client-monitor.sessions' => 1,
            'mqtt-accounts.index' => 2,
        ];

        return collect(NavigationRegistry::dashboardQuickLinksFor($user))
            ->values()
            ->sortBy(fn (array $link, int $index): array => [
                $priority[$link['routeName']] ?? 100,
                $index,
            ])
            ->map(fn (array $link): array => [
                'title' => $link['title'],
                'description' => $link['description'],
                'routeName' => $link['routeName'],
                'routeParams' => new stdClass,
                'routeQuery' => new stdClass,
            ])
            ->take(6)
            ->values()
            ->all();
    }
}
