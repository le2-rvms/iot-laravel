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
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-950">
        <AppFlashToaster />
        <AppConfirmDialog />

        <div class="grid min-h-screen lg:grid-cols-[280px_1fr]">
            <aside class="hidden border-r border-slate-200 bg-white lg:block">
                <AppSidebar />
            </aside>

            <div class="flex min-h-screen flex-col">
                <AppHeader :title="title" :description="description" :breadcrumbs="breadcrumbs">
                    <template #actions>
                        <slot name="header-actions" />
                    </template>
                </AppHeader>

                <main class="flex-1 px-6 py-6 lg:px-10">
                    <div class="mx-auto w-full max-w-7xl">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
