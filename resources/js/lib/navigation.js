import {
    LayoutGrid,
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
                can: 'dashboard.view',
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
                can: 'users.view',
            },
            {
                title: '角色权限',
                description: '为后续权限判断预留接入位。',
                href: '/roles',
                icon: ShieldCheck,
                can: 'roles.view',
                disabled: true,
            },
        ],
    },
];

export function resolveNavigationSections(access = {}) {
    return navigationSections
        .map((section) => ({
            ...section,
            items: section.items.filter((item) => {
                if (!item.can) {
                    return true;
                }

                return access[item.can] ?? true;
            }),
        }))
        .filter((section) => section.items.length > 0);
}
