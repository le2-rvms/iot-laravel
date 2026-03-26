<?php

namespace App\Http\Controllers\Web\Admin\Settings;

use App\Attributes\PermissionGroup;
use App\Values\Settings\Category;

#[PermissionGroup]
class SettingsApplicationConfigController extends AbstractSettingsConfigController
{
    public function __construct()
    {
        parent::__construct(
            Category::APPLICATION,
            '应用配置',
            'application-configs.index',
        );
    }
}
