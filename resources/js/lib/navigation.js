import {
    LayoutGrid,
    Settings,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';

export const navigationSections = [
    {
        title: '工作台',
        items: [
            {
                title: '仪表盘',
                description: '查看系统入口与首屏统计。',
                href: '/dashboard',
                icon: LayoutGrid,
                can: 'dashboard.read',
            },
        ],
    },
    {
        title: '系统管理',
        items: [
            {
                title: '用户管理',
                description: '维护后台用户、邮箱验证状态与基础资料。',
                href: '/users',
                icon: Users,
                can: 'user.read',
            },
            {
                title: '角色权限',
                description: '维护角色与读写权限集合。',
                href: '/roles',
                icon: ShieldCheck,
                can: 'role.read',
            },
            {
                title: '系统设置',
                description: '查看系统配置分组与后续扩展入口。',
                href: '/settings',
                icon: Settings,
                can: 'settings.read',
            },
        ],
    },
];

export function canAccessNavigationItem(item, access = {}) {
    if (!item.can) {
        return true;
    }

    return access[item.can] === true;
}

export function resolveNavigationSections(access = {}) {
    return navigationSections
        .map((section) => ({
            ...section,
            items: section.items.filter((item) => canAccessNavigationItem(item, access)),
        }))
        .filter((section) => section.items.length > 0);
}
