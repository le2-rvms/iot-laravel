<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '系统设置' },
];
</script>

<template>
    <Head title="系统设置" />

    <AppLayout
        title="系统设置"
        description="首版为受权限保护的配置入口页，先展示配置分组与后续扩展方向。"
        :breadcrumbs="breadcrumbs"
    >
        <UiCard class="overflow-hidden rounded-[1.5rem] border-app-panel-border bg-app-panel shadow-sm">
            <UiCardContent class="divide-y divide-app-panel-border p-0">
                <div
                    v-for="group in groups"
                    :key="group.title"
                    class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="space-y-1">
                        <h2 class="text-lg font-semibold text-app-panel-foreground">{{ group.title }}</h2>
                        <p class="text-sm leading-6 text-app-subtle-foreground">{{ group.description }}</p>
                    </div>
                    <UiButton v-if="group.href && !group.native" as-child variant="outline" class="rounded-xl">
                        <Link :href="group.href">{{ group.action_label }}</Link>
                    </UiButton>
                    <UiButton v-else-if="group.href" as-child variant="outline" class="rounded-xl">
                        <a :href="group.href">{{ group.action_label }}</a>
                    </UiButton>
                </div>
            </UiCardContent>
        </UiCard>
    </AppLayout>
</template>
