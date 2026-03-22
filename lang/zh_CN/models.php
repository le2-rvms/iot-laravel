<?php

declare(strict_types=1);

return [
    'auth' => [
        'permission' => [
            'attributes' => [
                'id' => '权限ID',
                'name' => '权限名称',
                'guard_name' => 'Guard名称',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
                'roles' => '绑定角色集合',
            ],
        ],
        'role' => [
            'attributes' => [
                'id' => '角色ID',
                'name' => '角色名称',
                'guard_name' => 'Guard名称',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
                'permissions' => '权限集合',
                'users' => '绑定用户集合',
            ],
        ],
        'user' => [
            'attributes' => [
                'id' => '用户ID',
                'name' => '名称',
                'email' => '邮箱',
                'email_verified_at' => '邮箱验证时间',
                'password' => '密码',
                'remember_token' => '记住登录令牌',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
                'roles' => '角色',
                'permissions' => '权限集合',
            ],
        ],
    ],
    'iot' => [
        'mqtt_account' => [
            'attributes' => [
                'act_id' => '账号ID',
                'clientid' => '客户端标识',
                'user_name' => '账号名',
                'password' => '密码',
                'password_hash' => '密码哈希',
                'certificate' => '证书内容',
                'salt' => '密码盐',
                'is_superuser' => '是否超级用户',
                'product_key' => '产品标识',
                'device_name' => '设备名称',
                'enabled' => '启用状态',
                'act_created_at' => '创建时间',
                'act_updated_at' => '更新时间',
                'act_updated_by' => '最近更新人',
            ],
        ],
    ],
    'settings' => [
        'config' => [
            'attributes' => [
                'id' => '配置ID',
                'key' => '配置键',
                'value' => '配置值',
                'category' => '配置分类',
                'is_masked' => '是否打码',
                'remark' => '备注',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'notification_channel' => [
            'attributes' => [
                'type' => '渠道类型',
                'target' => '渠道目标',
                'retries' => '重试次数',
                'enabled' => '渠道启用状态',
            ],
        ],
        'notification_rule' => [
            'attributes' => [
                'name' => '规则名称',
                'enabled' => '启用状态',
                'description' => '说明',
                'trigger_mode' => '触发方式',
                'threshold' => '触发阈值',
                'quiet_hours_enabled' => '静默时段开关',
                'quiet_hours_start' => '静默开始时间',
                'quiet_hours_end' => '静默结束时间',
                'channels' => '通知渠道',
            ],
        ],
    ],
];
