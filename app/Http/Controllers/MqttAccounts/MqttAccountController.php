<?php

namespace App\Http\Controllers\MqttAccounts;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\MqttAccounts\StoreMqttAccountRequest;
use App\Http\Requests\MqttAccounts\UpdateMqttAccountRequest;
use App\Models\Iot\MqttAccount;
use App\Support\ListQueryFilters;
use App\Values\Iot\Enabled;
use App\Values\Iot\IsSuperuser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup('MQTT账号管理')]
class MqttAccountController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = MqttAccount::query()
            ->addSelect('mqtt_accounts.*')
            // 列表直接带出 label 字段，前端表格无需再判断 0/1 到文案的映射。
            ->addSelect(DB::raw(IsSuperuser::toCaseSQL()))
            ->addSelect(DB::raw(Enabled::toCaseSQL()))
            ->orderByDesc('act_id');

        $filters = (new ListQueryFilters(
            request: $request,
            fieldDefinitions: [
                // 只有显式声明的字段可以暴露给列表查询 DSL。
                'act_id' => ['integer'],
                'user_name',
                'clientid',
                'product_key',
                'device_name',
                'enabled' => ['boolean'],
                'is_superuser' => ['boolean'],
            ],
            callbacks: [
                // search__func 保留 MQTT 列表原有的多字段模糊搜索入口。
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        // 后续数据库目标是 PostgreSQL，这里统一用大小写不敏感搜索，避免 MQTT 列表前后行为分裂。
                        $builder
                            ->whereRaw('LOWER(user_name) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(clientid) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(product_key) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(device_name) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        $accounts = $query
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MqttAccounts/Index', [
            'accounts' => $accounts,
            'filters' => $filters,
        ]);
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return Inertia::render('MqttAccounts/Create', [
            // 新建页直接返回模型对象，默认值与编辑页保持同一份数据结构。
            'account' => new MqttAccount([
                'is_superuser' => 0,
                'enabled' => 1,
            ]),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreMqttAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $password = $validated['password'];
        unset($validated['password']);

        // 后台只接收明文输入，真正入库时统一转成 salt + hash，避免模型层外泄保存细节。
        MqttAccount::query()->create($validated + MqttAccount::buildPasswordFields($password));

        return to_route('mqtt-accounts.index')->with('success', 'MQTT账号已创建。');
    }

    #[PermissionAction('write')]
    public function edit(MqttAccount $mqttAccount): Response
    {
        return Inertia::render('MqttAccounts/Edit', [
            'account' => $mqttAccount,
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateMqttAccountRequest $request, MqttAccount $mqttAccount): RedirectResponse
    {
        $validated = $request->validated();
        $password = $validated['password'] ?? null;
        unset($validated['password']);

        if (filled($password)) {
            // 编辑页留空表示不改密码；只有明确填写时才刷新 salt 和 hash。
            $validated += MqttAccount::buildPasswordFields($password);
        }

        $mqttAccount->update($validated);

        return to_route('mqtt-accounts.edit', $mqttAccount)->with('success', 'MQTT账号已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(MqttAccount $mqttAccount): RedirectResponse
    {
        $mqttAccount->delete();

        return to_route('mqtt-accounts.index')->with('success', 'MQTT账号已删除。');
    }
}
