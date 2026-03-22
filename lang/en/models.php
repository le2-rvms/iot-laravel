<?php

return [
    'auth' => [
        'admin_permission' => [
            'attributes' => [
                'id' => 'Permission ID',
                'name' => 'Permission Name',
                'guard_name' => 'Guard Name',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
                'roles' => 'Assigned Roles',
            ],
        ],
        'admin_role' => [
            'attributes' => [
                'id' => 'Role ID',
                'name' => 'Role Name',
                'guard_name' => 'Guard Name',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
                'permissions' => 'Permissions',
                'users' => 'Assigned Admin Users',
            ],
        ],
        'admin_user' => [
            'attributes' => [
                'id' => 'User ID',
                'name' => 'Name',
                'email' => 'Email',
                'email_verified_at' => 'Email Verified At',
                'password' => 'Password',
                'remember_token' => 'Remember Token',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
                'roles' => 'Admin Roles',
                'permissions' => 'Permissions',
            ],
        ],
    ],
    'iot' => [
        'mqtt_account' => [
            'attributes' => [
                'act_id' => 'Account ID',
                'clientid' => 'Client ID',
                'user_name' => 'Account Name',
                'password' => 'Password',
                'password_hash' => 'Password Hash',
                'certificate' => 'Certificate Content',
                'salt' => 'Password Salt',
                'is_superuser' => 'Superuser',
                'product_key' => 'Product Key',
                'device_name' => 'Device Name',
                'enabled' => 'Enabled',
                'act_created_at' => 'Created At',
                'act_updated_at' => 'Updated At',
                'act_updated_by' => 'Updated By',
            ],
        ],
    ],
    'settings' => [
        'config' => [
            'attributes' => [
                'id' => 'Config ID',
                'key' => 'Config Key',
                'value' => 'Config Value',
                'category' => 'Config Category',
                'is_masked' => 'Masked',
                'remark' => 'Remark',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
            ],
        ],
        'notification_channel' => [
            'attributes' => [
                'type' => 'Channel Type',
                'target' => 'Channel Target',
                'retries' => 'Retry Count',
                'enabled' => 'Channel Enabled',
            ],
        ],
        'notification_rule' => [
            'attributes' => [
                'name' => 'Rule Name',
                'enabled' => 'Enabled',
                'description' => 'Description',
                'trigger_mode' => 'Trigger Mode',
                'threshold' => 'Threshold',
                'quiet_hours_enabled' => 'Quiet Hours Enabled',
                'quiet_hours_start' => 'Quiet Hours Start',
                'quiet_hours_end' => 'Quiet Hours End',
                'channels' => 'Channels',
            ],
        ],
    ],
];
