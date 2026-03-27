<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="min-h-screen bg-app-shell text-foreground">
        <AppFlashToaster />
        <AppConfirmDialog />

        <div class="grid min-h-screen lg:grid-cols-[232px_1fr]">
            <aside
                class="hidden border-r border-sidebar-border/45 bg-sidebar text-sidebar-foreground lg:block"
            >
                <AppSidebar />
            </aside>

            <div class="flex min-h-screen min-w-0 flex-col">
                <AppHeader
                    :title="title"
                    :description="description"
                    :breadcrumbs="breadcrumbs"
                >
                    <template #actions>
                        <slot name="header-actions" />
                    </template>
                </AppHeader>

                <main class="min-w-0 flex-1 px-5 py-5 lg:px-8 lg:py-5 xl:px-4">
                    <div class="mx-auto w-full max-w-[1600px] min-w-0">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
