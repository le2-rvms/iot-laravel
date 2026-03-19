<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    forId: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="space-y-2">
        <UiLabel v-if="forId" :for="forId">{{ label }}</UiLabel>
        <UiLabel v-else>{{ label }}</UiLabel>
        <slot />
        <p v-if="description" class="text-sm text-slate-500">{{ description }}</p>
        <SharedFormsFieldError :message="error" />
    </div>
</template>
