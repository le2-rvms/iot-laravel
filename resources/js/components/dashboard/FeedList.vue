<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    items: {
        type: Array,
        default: () => [],
    },
    emptyTitle: {
        type: String,
        default: '暂无数据',
    },
    emptyDescription: {
        type: String,
        default: '',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    actionHref: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <UiCard class="min-w-0 overflow-hidden border border-app-panel-border/80 bg-app-panel">
        <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
            <div class="space-y-2">
                <UiCardTitle class="text-lg">{{ title }}</UiCardTitle>
                <UiCardDescription>{{ description }}</UiCardDescription>
            </div>
            <UiButton v-if="actionHref && actionLabel" as-child variant="outline" size="sm" class="rounded-xl">
                <Link :href="actionHref">{{ actionLabel }}</Link>
            </UiButton>
        </UiCardHeader>

        <UiCardContent class="p-0">
            <div v-if="items.length" class="divide-y divide-app-panel-border/80">
                <slot
                    v-for="(item, index) in items"
                    :key="index"
                    name="item"
                    :item="item"
                    :index="index"
                />
            </div>

            <div v-else class="px-5 py-10 text-center">
                <p class="font-medium text-app-panel-foreground">{{ emptyTitle }}</p>
                <p v-if="emptyDescription" class="mt-2 text-sm leading-6 text-app-subtle-foreground">
                    {{ emptyDescription }}
                </p>
            </div>
        </UiCardContent>
    </UiCard>
</template>
