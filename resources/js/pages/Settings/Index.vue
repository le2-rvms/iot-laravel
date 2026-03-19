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
        <div class="grid gap-4 lg:grid-cols-2">
            <UiCard
                v-for="group in groups"
                :key="group.title"
                class="rounded-[1.75rem] border-slate-200 shadow-sm"
            >
                <UiCardHeader>
                    <UiCardTitle>{{ group.title }}</UiCardTitle>
                </UiCardHeader>
                <UiCardContent class="space-y-4 text-sm leading-6 text-slate-500">
                    <p>{{ group.description }}</p>
                    <UiButton v-if="group.href" as-child variant="outline" class="rounded-xl">
                        <Link :href="group.href">{{ group.action_label }}</Link>
                    </UiButton>
                </UiCardContent>
            </UiCard>
        </div>
    </AppLayout>
</template>
