<?php

namespace App\Http\Controllers\Api\Mqtt;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mqtt\EmqxAuthRequest;
use App\Models\Iot\MqttAccount;
use Illuminate\Http\JsonResponse;

class EmqxAuthController extends Controller
{
    public function authenticate(EmqxAuthRequest $request): JsonResponse
    {
        $input = $request->validated();
        $mqttAccount = MqttAccount::query()
            // 当前认证入口只按用户名校验；clientid 先保留在请求契约里，后续需要绑定时再收紧。
            ->where('user_name', $input['username'])
            ->first();

        if (
            $mqttAccount instanceof MqttAccount
            && $mqttAccount->enabled?->isEnabled()
            && $mqttAccount->checkPassword($input['password'])
        ) {
            // 认证层只返回 allow / deny，不暴露后台账号更多状态，保持 EMQX 对接面尽量稳定。
            return $this->mqttResponse('allow', $mqttAccount->is_superuser?->isEnabled() === true);
        }

        return $this->mqttResponse('deny');
    }

    private function mqttResponse(string $result, bool $isSuperuser = false): JsonResponse
    {
        // 对外保持旧系统返回契约，避免 EMQX 侧联调时再改解析逻辑。
        return response()->json([
            'result' => $result,
            'is_superuser' => $isSuperuser ? 'true' : 'false',
        ]);
    }
}
