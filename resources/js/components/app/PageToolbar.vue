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
        <div class="flex flex-col gap-4 rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <h2 class="text-xl font-semibold tracking-tight text-slate-950">
                    {{ title }}
                </h2>
                <p v-if="description" class="text-sm leading-6 text-slate-500">
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
