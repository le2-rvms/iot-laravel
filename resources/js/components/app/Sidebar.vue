<script setup>
import {
    FileCheck2,
    LayoutGrid,
    ScanSearch,
    ShieldCheck,
    SlidersHorizontal,
    SlidersVertical,
    Users,
    Waypoints,
} from "lucide-vue-next";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();

// 保持显式图标映射，避免再次回到整包动态导入导致打包体积放大。
const navigationIcons = {
    LayoutGrid,
    Users,
    ShieldCheck,
    Waypoints,
    SlidersHorizontal,
    SlidersVertical,
    FileCheck2,
    ScanSearch,
};

function normalizePath(url) {
    return String(url).split("?")[0];
}

function isActive(href) {
    const currentPath = normalizePath(page.url);
    const targetPath = normalizePath(href);

    if (href === "/admin/dashboard") {
        return currentPath === targetPath;
    }

    return currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
}

function resolveNavigationIcon(icon) {
    // 后端导航只传图标名，前端在这里统一落到具体组件并提供兜底图标。
    return navigationIcons[icon] ?? LayoutGrid;
}
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="border-b border-sidebar-border px-6 py-6">
            <div class="space-y-3">
                <div
                    class="inline-flex size-11 items-center justify-center rounded-2xl border border-sidebar-border bg-sidebar-accent text-sm font-semibold text-sidebar-primary shadow-sm"
                >
                    {{ $page.props.app.name?.slice(0, 1) }}
                </div>
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.28em] text-sidebar-foreground/60"
                    >
                        后台系统
                    </p>
                    <p
                        class="mt-1 text-lg font-semibold text-sidebar-foreground"
                    >
                        {{ $page.props.app.name }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex-1 space-y-8 overflow-y-auto px-4 py-6">
            <section
                v-for="section in $page.props.navigation?.sections ?? []"
                :key="section.title"
                class="space-y-3"
            >
                <p
                    class="px-2 text-xs font-semibold uppercase tracking-[0.2em] text-sidebar-foreground/60"
                >
                    {{ section.title }}
                </p>

                <div class="space-y-1">
                    <Link
                        v-for="item in section.items"
                        :key="item.href"
                        :href="item.disabled ? page.url : item.href"
                        class="group flex items-start gap-3 rounded-xl border border-transparent px-3 py-3 transition"
                        :class="[
                            item.disabled
                                ? 'cursor-not-allowed opacity-50'
                                : isActive(item.href)
                                  ? 'border-sidebar-border bg-sidebar-accent text-sidebar-accent-foreground'
                                  : 'text-sidebar-foreground hover:border-sidebar-border hover:bg-sidebar-accent/70 hover:text-sidebar-accent-foreground',
                        ]"
                    >
                        <component
                            :is="resolveNavigationIcon(item.icon)"
                            class="mt-0.5 size-5 shrink-0"
                            :class="
                                isActive(item.href)
                                    ? 'text-sidebar-primary'
                                    : 'text-sidebar-foreground/55'
                            "
                        />
                        <div class="min-w-0">
                            <div class="font-medium">
                                {{ item.title }}
                            </div>
                            <p
                                class="mt-1 text-xs leading-5"
                                :class="
                                    isActive(item.href)
                                        ? 'text-sidebar-accent-foreground/75'
                                        : 'text-sidebar-foreground/60'
                                "
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>

        <div class="border-t border-sidebar-border px-6 py-5">
            <div
                class="rounded-xl border border-sidebar-border bg-sidebar-accent/65 px-4 py-4"
            >
                <p
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-sidebar-foreground/60"
                >
                    认证状态
                </p>
                <p
                    class="mt-2 text-sm font-medium text-sidebar-accent-foreground"
                >
                    {{ $page.props.auth.user?.name }}
                </p>
                <p class="mt-1 text-xs text-sidebar-foreground/60">
                    邮箱{{
                        $page.props.auth.user?.email_verified_at
                            ? "已验证"
                            : "待验证"
                    }}
                </p>
            </div>
        </div>
    </div>
</template>
