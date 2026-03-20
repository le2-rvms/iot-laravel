<script setup>
defineProps({
    rows: {
        type: Number,
        default: 3,
    },
});
</script>

<template>
    <div class="space-y-3 rounded-[1.5rem] border border-app-panel-border bg-app-panel p-6 shadow-sm">
        <UiSkeleton class="h-6 w-48 rounded-xl" />
        <UiSkeleton class="h-4 w-full rounded-xl" />
        <UiSkeleton
            v-for="row in rows"
            :key="row"
            class="h-12 w-full rounded-xl"
        />
    </div>
</template>
