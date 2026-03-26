<?php

namespace App\Http\Controllers\Web\Admin\Settings;

use App\Attributes\PermissionGroup;
use App\Values\Settings\Category;

#[PermissionGroup]
class SettingsSystemConfigController extends AbstractSettingsConfigController
{
    public function __construct()
    {
        parent::__construct(
            Category::SYSTEM,
            '系统配置',
            'system-configs.index',
        );
    }
}
