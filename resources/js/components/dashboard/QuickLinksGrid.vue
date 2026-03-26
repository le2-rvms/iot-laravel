<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <UiCard class="rounded-[1.5rem] border border-app-panel-border/80 bg-app-panel shadow-sm">
        <UiCardHeader>
            <UiCardTitle class="text-lg">常用入口</UiCardTitle>
            <UiCardDescription>保留高频模块入口，便于从首屏快速进入处理页面。</UiCardDescription>
        </UiCardHeader>

        <UiCardContent>
            <div v-if="links.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="group rounded-2xl border border-app-panel-border/80 bg-app-panel/70 px-4 py-4 transition hover:border-app-panel-border hover:bg-app-subtle/35"
                >
                    <p class="font-medium text-app-panel-foreground">{{ link.title }}</p>
                    <p class="mt-1 text-sm leading-6 text-app-subtle-foreground">
                        {{ link.description }}
                    </p>
                </Link>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-app-panel-border/80 px-5 py-10 text-center">
                <p class="font-medium text-app-panel-foreground">当前没有可用入口</p>
                <p class="mt-2 text-sm leading-6 text-app-subtle-foreground">
                    角色权限放开后，这里会出现常用模块入口。
                </p>
            </div>
        </UiCardContent>
    </UiCard>
</template>
