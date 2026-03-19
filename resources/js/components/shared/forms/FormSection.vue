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
    <UiCard class="rounded-[1.75rem] border-slate-200 shadow-sm">
        <UiCardHeader>
            <UiCardTitle>{{ title }}</UiCardTitle>
            <UiCardDescription v-if="description">
                {{ description }}
            </UiCardDescription>
        </UiCardHeader>
        <UiCardContent class="space-y-5">
            <slot />
        </UiCardContent>
    </UiCard>
</template>
