<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
});

const hasMeta = computed(() => Boolean(props.title || props.description));
</script>

<template>
    <section class="space-y-4">
        <div
            :class="hasMeta
                ? 'flex flex-col gap-4 border-b border-app-panel-border/80 pb-4 text-app-panel-foreground lg:flex-row lg:items-end lg:justify-between'
                : 'flex items-center justify-end border-b border-app-panel-border/80 pb-3 text-app-panel-foreground'"
        >
            <div v-if="hasMeta" class="space-y-1.5">
                <h2 class="text-xl font-semibold tracking-tight text-app-panel-foreground">
                    {{ title }}
                </h2>
                <p v-if="description" class="text-sm leading-6 text-app-subtle-foreground">
                    {{ description }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <slot name="actions" />
            </div>
        </div>

        <slot />
    </section>
</template>
