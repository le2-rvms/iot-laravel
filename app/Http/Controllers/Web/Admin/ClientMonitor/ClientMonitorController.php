<?php

namespace App\Http\Controllers\Web\Admin\ClientMonitor;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Web\Admin\Controller;
use App\Models\Iot\IotClientAuthEvent;
use App\Models\Iot\IotClientCmdEvent;
use App\Models\Iot\IotClientConnEvent;
use App\Models\Iot\IotClientSession;
use App\Models\Iot\IotGpsPositionHistory;
use App\Models\Iot\IotGpsPositionLast;
use Illuminate\Http\Request;
use Inertia\Response;
use stdClass;

#[PermissionGroup]
class ClientMonitorController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $filters = [
            'client_id__eq' => (string) $request->query('client_id__eq', ''),
        ];

        if ($filters['client_id__eq'] === '') {
            abort(404);
        }

        $contextQuery = $filters;

        return $this->renderPage([
            'filters' => $filters,
            'sections' => self::sections($contextQuery),
            'deviceContext' => self::routeTarget('client-monitor.device-overview', query: $contextQuery),
            'previews' => [
                'sessions' => IotClientSession::indexQuery($contextQuery)
                    ->limit(5)
                    ->get()
                    ->all(),
                'authEvents' => IotClientAuthEvent::indexQuery($contextQuery)
                    ->limit(5)
                    ->get()
                    ->all(),
                'cmdEvents' => IotClientCmdEvent::indexQuery($contextQuery)
                    ->limit(5)
                    ->get()
                    ->all(),
                'connEvents' => IotClientConnEvent::indexQuery($contextQuery)
                    ->limit(5)
                    ->get()
                    ->all(),
                'gpsPositionLast' => IotGpsPositionLast::indexQuery(self::gpsQuery($contextQuery))
                    ->first(),
                'gpsPositionHistories' => IotGpsPositionHistory::indexQuery(self::gpsQuery($contextQuery))
                    ->limit(5)
                    ->get()
                    ->all(),
            ],
        ]);
    }

    #[PermissionAction('read')]
    public function sessions(Request $request): Response
    {
        $filters = array_replace([
            'search__func' => '',
            'last_event_type__eq' => '',
            'last_protocol__eq' => '',
            'client_id__eq' => '',
        ], $request->only([
            'search__func',
            'last_event_type__eq',
            'last_protocol__eq',
            'client_id__eq',
        ]));
        $contextQuery = $filters['client_id__eq'] === ''
            ? []
            : ['client_id__eq' => $filters['client_id__eq']];

        return $this->renderPage([
            'sessions' => IotClientSession::indexQuery($filters)
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'sections' => self::sections($contextQuery),
            'deviceContext' => $contextQuery === [] ? null : self::routeTarget('client-monitor.device-overview', query: $contextQuery),
            'pageMeta' => [
                'title' => '在线会话',
                'description' => '查看客户端当前在线状态、最近事件和连接上下文。',
                ...self::routeTarget('client-monitor.sessions', query: $contextQuery),
                'monitorRoute' => self::routeTarget('client-monitor.sessions'),
            ],
        ]);
    }

    #[PermissionAction('read')]
    public function authEvents(Request $request): Response
    {
        $filters = array_replace([
            'search__func' => '',
            'result__eq' => '',
            'protocol__eq' => '',
            'client_id__eq' => '',
        ], $request->only([
            'search__func',
            'result__eq',
            'protocol__eq',
            'client_id__eq',
        ]));
        $contextQuery = $filters['client_id__eq'] === ''
            ? []
            : ['client_id__eq' => $filters['client_id__eq']];

        return $this->renderPage([
            'authEvents' => IotClientAuthEvent::indexQuery($filters)
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'sections' => self::sections($contextQuery),
            'deviceContext' => $contextQuery === [] ? null : self::routeTarget('client-monitor.device-overview', query: $contextQuery),
            'pageMeta' => [
                'title' => '鉴权事件',
                'description' => '查看客户端鉴权成功、失败和失败原因。',
                ...self::routeTarget('client-monitor.auth-events', query: $contextQuery),
                'monitorRoute' => self::routeTarget('client-monitor.sessions'),
            ],
        ]);
    }

    #[PermissionAction('read')]
    public function cmdEvents(Request $request): Response
    {
        $filters = array_replace([
            'search__func' => '',
            'event_type__eq' => '',
            'protocol__eq' => '',
            'client_id__eq' => '',
        ], $request->only([
            'search__func',
            'event_type__eq',
            'protocol__eq',
            'client_id__eq',
        ]));
        $contextQuery = $filters['client_id__eq'] === ''
            ? []
            : ['client_id__eq' => $filters['client_id__eq']];

        return $this->renderPage([
            'cmdEvents' => IotClientCmdEvent::indexQuery($filters)
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'sections' => self::sections($contextQuery),
            'deviceContext' => $contextQuery === [] ? null : self::routeTarget('client-monitor.device-overview', query: $contextQuery),
            'pageMeta' => [
                'title' => '命令事件',
                'description' => '查看命令事件流、命令类型和原因码。',
                ...self::routeTarget('client-monitor.cmd-events', query: $contextQuery),
                'monitorRoute' => self::routeTarget('client-monitor.sessions'),
            ],
        ]);
    }

    #[PermissionAction('read')]
    public function connEvents(Request $request): Response
    {
        $filters = array_replace([
            'search__func' => '',
            'event_type__eq' => '',
            'protocol__eq' => '',
            'client_id__eq' => '',
        ], $request->only([
            'search__func',
            'event_type__eq',
            'protocol__eq',
            'client_id__eq',
        ]));
        $contextQuery = $filters['client_id__eq'] === ''
            ? []
            : ['client_id__eq' => $filters['client_id__eq']];

        return $this->renderPage([
            'connEvents' => IotClientConnEvent::indexQuery($filters)
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'sections' => self::sections($contextQuery),
            'deviceContext' => $contextQuery === [] ? null : self::routeTarget('client-monitor.device-overview', query: $contextQuery),
            'pageMeta' => [
                'title' => '连接事件',
                'description' => '查看客户端连接、断开和原因码记录。',
                ...self::routeTarget('client-monitor.conn-events', query: $contextQuery),
                'monitorRoute' => self::routeTarget('client-monitor.sessions'),
            ],
        ]);
    }

    #[PermissionAction('read')]
    public function gpsPositionLast(Request $request): Response
    {
        $filters = array_replace([
            'search__func' => '',
            'status__eq' => '',
            'alarm__eq' => '',
            'gps_time__gte' => '',
            'gps_time__lte' => '',
            'client_id__eq' => '',
        ], $request->only([
            'search__func',
            'status__eq',
            'alarm__eq',
            'gps_time__gte',
            'gps_time__lte',
            'client_id__eq',
        ]));
        $contextQuery = $filters['client_id__eq'] === ''
            ? []
            : ['client_id__eq' => $filters['client_id__eq']];

        return $this->renderPage([
            'gpsPositionLast' => IotGpsPositionLast::indexQuery(self::gpsQuery($filters))
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'sections' => self::sections($contextQuery),
            'deviceContext' => $contextQuery === [] ? null : self::routeTarget('client-monitor.device-overview', query: $contextQuery),
            'pageMeta' => [
                'title' => '当前定位',
                'description' => '查看终端当前最新定位、速度、方向和状态信息。',
                ...self::routeTarget('client-monitor.gps-position-last', query: $contextQuery),
                'monitorRoute' => self::routeTarget('client-monitor.sessions'),
            ],
        ]);
    }

    #[PermissionAction('read')]
    public function gpsPositionHistories(Request $request): Response
    {
        $filters = array_replace([
            'search__func' => '',
            'status__eq' => '',
            'alarm__eq' => '',
            'gps_time__gte' => '',
            'gps_time__lte' => '',
            'client_id__eq' => '',
        ], $request->only([
            'search__func',
            'status__eq',
            'alarm__eq',
            'gps_time__gte',
            'gps_time__lte',
            'client_id__eq',
        ]));
        $contextQuery = $filters['client_id__eq'] === ''
            ? []
            : ['client_id__eq' => $filters['client_id__eq']];

        return $this->renderPage([
            'gpsPositionHistories' => IotGpsPositionHistory::indexQuery(self::gpsQuery($filters))
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'sections' => self::sections($contextQuery),
            'deviceContext' => $contextQuery === [] ? null : self::routeTarget('client-monitor.device-overview', query: $contextQuery),
            'pageMeta' => [
                'title' => '定位历史',
                'description' => '查看终端历史定位记录、速度、方向和状态变化。',
                ...self::routeTarget('client-monitor.gps-position-histories', query: $contextQuery),
                'monitorRoute' => self::routeTarget('client-monitor.sessions'),
            ],
        ]);
    }

    /**
     * @return array<int, array{title: string, description: string, routeName: string, routeParams: stdClass, routeQuery: array<string, string>}>
     */
    protected static function sections(array $query): array
    {
        return [
            [
                'title' => '在线会话',
                'description' => '查看客户端当前在线状态与最近事件。',
                ...self::routeTarget('client-monitor.sessions', query: $query),
            ],
            [
                'title' => '鉴权事件',
                'description' => '查看客户端鉴权结果、原因和上下文。',
                ...self::routeTarget('client-monitor.auth-events', query: $query),
            ],
            [
                'title' => '命令事件',
                'description' => '查看命令事件流、命令类型与原因码。',
                ...self::routeTarget('client-monitor.cmd-events', query: $query),
            ],
            [
                'title' => '连接事件',
                'description' => '查看连接、断开和原因码记录。',
                ...self::routeTarget('client-monitor.conn-events', query: $query),
            ],
            [
                'title' => '当前定位',
                'description' => '查看终端当前最新定位、速度和状态。',
                ...self::routeTarget('client-monitor.gps-position-last', query: $query),
            ],
            [
                'title' => '定位历史',
                'description' => '查看终端历史定位轨迹与状态变化。',
                ...self::routeTarget('client-monitor.gps-position-histories', query: $query),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, string>  $query
     * @return array{routeName: string, routeParams: stdClass|array<string, mixed>, routeQuery: stdClass|array<string, string>}
     */
    protected static function routeTarget(string $routeName, array $params = [], array $query = []): array
    {
        return [
            'routeName' => $routeName,
            'routeParams' => $params === [] ? new stdClass : $params,
            'routeQuery' => $query === [] ? new stdClass : $query,
        ];
    }

    private static function gpsQuery(array $filters): array
    {
        $query = $filters;

        if (array_key_exists('client_id__eq', $query)) {
            $query['terminal_id__eq'] = $query['client_id__eq'];
            unset($query['client_id__eq']);
        }

        foreach (['gps_time__gte', 'gps_time__lte'] as $key) {
            if (($query[$key] ?? '') === '') {
                continue;
            }

            $value = str_replace('T', ' ', trim((string) $query[$key]));

            if (strlen($value) === 16) {
                $value .= $key === 'gps_time__lte' ? ':59' : ':00';
            }

            $query[$key] = $value;
        }

        return $query;
    }

}
