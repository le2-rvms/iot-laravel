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
    <div class="relative min-h-screen overflow-hidden bg-app-shell">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-x-0 top-0 h-72 bg-linear-to-b from-primary/10 via-chart-2/6 to-transparent" />
            <div class="absolute left-[-6rem] top-24 size-56 rounded-full bg-chart-3/10 blur-3xl" />
            <div class="absolute right-[-4rem] top-10 size-72 rounded-full bg-chart-1/9 blur-3xl" />
        </div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-6 py-12">
            <div class="w-full max-w-md space-y-6 rounded-xl border border-app-panel-border bg-app-panel p-8 text-app-panel-foreground shadow-sm backdrop-blur">
                <div class="space-y-2">
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-app-subtle-foreground">
                        {{ $page.props.app.name }}
                    </p>
                    <h2 class="text-3xl font-semibold tracking-tight text-app-panel-foreground">
                        {{ title }}
                    </h2>
                    <p v-if="description" class="text-sm leading-6 text-app-subtle-foreground">
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
