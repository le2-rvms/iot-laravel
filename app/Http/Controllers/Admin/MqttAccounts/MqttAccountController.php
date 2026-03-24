<?php

namespace App\Http\Controllers\Admin\MqttAccounts;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\MqttAccounts\StoreMqttAccountRequest;
use App\Http\Requests\MqttAccounts\UpdateMqttAccountRequest;
use App\Models\Iot\MqttAccount;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[PermissionGroup]
class MqttAccountController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = MqttAccount::indexQuery($request->query());
        $filters = $request->except('page');

        $accounts = $query
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MqttAccounts/Index', [
            'accounts' => $accounts,
            'filters' => $filters,
        ]);
    }

    #[PermissionAction('read')]
    public function export(Request $request): StreamedResponse
    {
        $query = MqttAccount::indexQuery($request->query());

        return CsvExporter::download(
            query: $query,
            columns: [
                'models.mqtt_account.act_id' => static fn (MqttAccount $account): int => $account->act_id,
                'models.mqtt_account.user_name' => static fn (MqttAccount $account): string => $account->user_name,
                'models.mqtt_account.clientid' => static fn (MqttAccount $account): string => (string) ($account->clientid ?? ''),
                'models.mqtt_account.product_key' => static fn (MqttAccount $account): string => (string) ($account->product_key ?? ''),
                'models.mqtt_account.device_name' => static fn (MqttAccount $account): string => (string) ($account->device_name ?? ''),
                'models.mqtt_account.is_superuser_label' => static fn (MqttAccount $account): string => $account->is_superuser_label ?? '',
                'models.mqtt_account.enabled_label' => static fn (MqttAccount $account): string => $account->enabled_label ?? '',
                'models.mqtt_account.act_updated_at' => static fn (MqttAccount $account): string => $account->act_updated_at?->format('Y-m-d H:i:s') ?? '',
                'models.mqtt_account.act_updated_by' => static fn (MqttAccount $account): string => (string) ($account->act_updated_by ?? ''),
            ],
            fileName: 'mqtt-accounts-'.now()->format('Ymd-His').'.csv',
        );
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
        MqttAccount::createAccount($validated, $password);

        return redirect()->action([self::class, 'index'])->with('success', 'MQTT账号已创建。');
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

        // 编辑页留空表示不改密码；只有明确填写时才刷新 salt 和 hash。
        $mqttAccount = $mqttAccount->updateAccount($validated, $password);

        return redirect()->action([self::class, 'edit'], $mqttAccount)->with('success', 'MQTT账号已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(MqttAccount $mqttAccount): RedirectResponse
    {
        $mqttAccount->deleteAccount();

        return redirect()->action([self::class, 'index'])->with('success', 'MQTT账号已删除。');
    }
}
