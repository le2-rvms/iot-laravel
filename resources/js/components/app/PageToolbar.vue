<script setup>
import { computed } from "vue";

const props = defineProps({
    title: {
        type: String,
        default: "",
    },
    description: {
        type: String,
        default: "",
    },
});

const hasMeta = computed(() => Boolean(props.title || props.description));
</script>

<template>
    <section class="space-y-4">
        <div
            :class="
                hasMeta
                    ? 'flex flex-col gap-3 border-b border-app-panel-border/80 pb-2 text-app-panel-foreground lg:flex-row lg:items-center lg:justify-between'
                    : 'flex items-center justify-end border-b border-app-panel-border/80 pb-3 text-app-panel-foreground'
            "
        >
            <div
                v-if="hasMeta"
                class="flex min-w-0 flex-col gap-1.5 lg:flex-row lg:items-baseline lg:gap-4"
            >
                <h2
                    class="text-xl font-semibold tracking-tight"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="min-w-0 text-sm leading-6 text-app-subtle-foreground"
                >
                    {{ description }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                <slot name="actions" />
            </div>
        </div>

        <slot />
    </section>
</template>
