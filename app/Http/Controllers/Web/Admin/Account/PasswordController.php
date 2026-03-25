<?php

namespace App\Http\Controllers\Web\Admin\Account;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup]
class PasswordController extends Controller
{
    #[PermissionAction('write')]
    public function edit(): Response
    {
        return Inertia::render('Account/Password/Edit');
    }

    #[PermissionAction('write')]
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        // 修改成功后回到同一页面，交由全局 flash/toast 给出结果提示。
        return redirect()->action([self::class, 'edit'])->with('success', '密码已更新。');
    }
}
