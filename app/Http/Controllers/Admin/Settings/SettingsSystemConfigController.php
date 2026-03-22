<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Requests\Settings\StoreSettingRequest;
use App\Http\Requests\Settings\UpdateSettingRequest;
use App\Models\Settings\Config;
use App\Values\Settings\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup]
class SettingsSystemConfigController extends AbstractSettingsConfigController
{
    public function __construct()
    {
        parent::__construct(
            Category::SYSTEM,
            '系统配置',
            [self::class, 'index'],
        );
    }

    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        return $this->indexConfigs($request);
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return $this->createConfig();
    }

    #[PermissionAction('write')]
    public function store(StoreSettingRequest $request): RedirectResponse
    {
        return $this->storeConfig($request);
    }

    #[PermissionAction('write')]
    public function edit(Config $config): Response
    {
        return $this->editConfig($config);
    }

    #[PermissionAction('write')]
    public function update(UpdateSettingRequest $request, Config $config): RedirectResponse
    {
        return $this->updateConfig($request, $config);
    }

    #[PermissionAction('write')]
    public function destroy(Config $config): RedirectResponse
    {
        return $this->destroyConfig($config);
    }
}
