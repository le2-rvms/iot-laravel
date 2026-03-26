<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { hrefForRouteTarget } from '@/lib/routes';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <component
        :is="props.item.routeName ? Link : 'div'"
        :href="props.item.routeName ? hrefForRouteTarget(props.item) : undefined"
        class="group block min-w-0"
    >
        <UiCard class="h-full min-w-0 rounded-[1.35rem] border border-app-panel-border/80 bg-app-panel/85 shadow-sm transition group-hover:border-app-panel-border group-hover:shadow-md">
            <UiCardContent class="flex h-full flex-col gap-4 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-app-subtle-foreground">
                            {{ props.item.title }}
                        </p>
                        <p class="text-4xl font-semibold tracking-tight text-app-panel-foreground">
                            {{ props.item.value }}
                        </p>
                    </div>
                    <ArrowRight
                        v-if="props.item.routeName"
                        class="mt-1 size-4 shrink-0 text-app-subtle-foreground transition group-hover:translate-x-0.5 group-hover:text-app-panel-foreground"
                    />
                </div>
                <p class="text-sm leading-6 text-app-subtle-foreground">
                    {{ props.item.description }}
                </p>
            </UiCardContent>
        </UiCard>
    </component>
</template>
