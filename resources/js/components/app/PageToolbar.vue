<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-col gap-4 border-b border-app-panel-border/80 pb-4 text-app-panel-foreground lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-1.5">
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
