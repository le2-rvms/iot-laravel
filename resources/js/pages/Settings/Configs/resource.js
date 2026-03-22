export function resolveConfigResource(category) {
    return {
        title: category === 'application' ? '应用配置' : '系统配置',
        description: category === 'application'
            ? '维护应用层的可配置键值、打码策略与备注说明。'
            : '维护系统层的公共设定、展示策略与后台说明。',
        index_href: category === 'application' ? '/admin/settings/application-configs' : '/admin/settings/system-configs',
        create_href: category === 'application' ? '/admin/settings/application-configs/create' : '/admin/settings/system-configs/create',
        write_permission: `settings-${category}-config.write`,
    };
}
