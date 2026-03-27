<script setup>
import { computed } from "vue";
import {
    Cpu,
    FileCheck2,
    History,
    LayoutGrid,
    Package,
    ScanSearch,
    ShieldCheck,
    SlidersHorizontal,
    SlidersVertical,
    Users,
    Waypoints,
} from "lucide-vue-next";
import { Link, usePage } from "@inertiajs/vue3";
import { route } from "@/lib/routes";

const page = usePage();
const buildInfo = window.__APP_BUILD_INFO__ ?? null;

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
    History,
    Cpu,
    Package,
};

function isActive(item) {
    const currentPath = normalizePath(page.url);
    const itemPath = normalizePath(item.href);

    if (item.routeName === "client-monitor.sessions") {
        return currentPath.startsWith(clientMonitorBasePath.value);
    }

    if (item.routeName?.endsWith(".index")) {
        return (
            currentPath === itemPath || currentPath.startsWith(`${itemPath}/`)
        );
    }

    return currentPath === itemPath;
}

function normalizePath(url) {
    const value = String(url ?? "");
    const relative = value.startsWith("http")
        ? new URL(value).pathname
        : value.split("?")[0];

    return relative.replace(/\/+$/, "") || "/";
}

const clientMonitorBasePath = computed(() => {
    const sessionsPath = normalizePath(route("client-monitor.sessions"));

    return sessionsPath.replace(/\/sessions$/, "");
});

function resolveNavigationIcon(icon) {
    // 后端导航只传图标名，前端在这里统一落到具体组件并提供兜底图标。
    return navigationIcons[icon] ?? LayoutGrid;
}

function formatBuildTime(value) {
    if (!value) {
        return "";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    return new Intl.DateTimeFormat("zh-CN", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false,
    }).format(date);
}
</script>

<template>
    <div class="flex h-full flex-col">
        <div
            class="flex h-[58px] items-center border-b border-sidebar-border/45 px-5 py-1.5"
        >
            <div class="min-w-0 w-full">
                <div class="flex items-center gap-2">
                    <p
                        class="min-w-0 flex-1 truncate text-sm font-semibold tracking-tight text-sidebar-foreground"
                    >
                        {{ $page.props.app.name }}
                    </p>
                    <span
                        class="shrink-0 rounded-full border border-sidebar-border/45 bg-sidebar-accent/70 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-[0.14em] text-sidebar-foreground/65"
                    >
                        IoT
                    </span>
                </div>
                <p class="mt-px text-[11px] leading-4 text-sidebar-foreground/55">
                    运营后台
                </p>
            </div>
        </div>

        <div class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
            <section
                v-for="section in $page.props.navigation?.sections ?? []"
                :key="section.title"
                class="space-y-2"
            >
                <p
                    class="px-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-sidebar-foreground/50"
                >
                    {{ section.title }}
                </p>

                <div class="space-y-1.5">
                    <Link
                        v-for="item in section.items"
                        :key="item.href"
                        :href="item.disabled ? page.url : item.href"
                        class="group flex items-center gap-3 rounded-xl border px-3 py-2.5 transition"
                        :class="[
                            item.disabled
                                ? 'cursor-not-allowed border-transparent opacity-50'
                                : isActive(item)
                                  ? 'border-sidebar-border bg-sidebar-accent text-sidebar-accent-foreground shadow-sm'
                                  : 'border-transparent text-sidebar-foreground hover:border-sidebar-border hover:bg-sidebar-accent/70 hover:text-sidebar-accent-foreground',
                        ]"
                    >
                        <component
                            :is="resolveNavigationIcon(item.icon)"
                            class="size-[18px] shrink-0"
                            :class="
                                isActive(item)
                                    ? 'text-sidebar-primary'
                                    : 'text-sidebar-foreground/55'
                            "
                        />
                        <div
                            class="min-w-0 flex-1 text-sm font-medium leading-5"
                        >
                            {{ item.title }}
                        </div>
                    </Link>
                </div>
            </section>
        </div>

        <div class="border-t border-sidebar-border/45 px-5 py-4">
            <div class="space-y-2.5">
                <div class="hidden lg:flex items-center gap-2">
                    <AppThemeMenu />
                    <AppUserMenu />
                </div>

                <div
                    v-if="buildInfo"
                    class="rounded-xl border border-sidebar-border/45 bg-sidebar-accent/65 px-3.5 py-3.5"
                >
                    <p
                        class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sidebar-foreground/55"
                    >
                        Build Info
                    </p>
                    <p
                        class="mt-1.5 text-sm font-medium text-sidebar-accent-foreground"
                    >
                        {{
                            formatBuildTime(buildInfo.builtAt) ||
                            buildInfo.builtAt
                        }}
                    </p>
                    <p class="mt-0.5 text-xs text-sidebar-foreground/60">
                        {{ buildInfo.command }} / {{ buildInfo.mode }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
