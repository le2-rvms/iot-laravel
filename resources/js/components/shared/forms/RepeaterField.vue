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
    addLabel: {
        type: String,
        default: '新增',
    },
    error: {
        type: String,
        default: '',
    },
});

defineEmits(['add']);
</script>

<template>
    <UiCard class="app-panel-card">
        <UiCardHeader class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <UiCardTitle>{{ title }}</UiCardTitle>
                <UiCardDescription v-if="description">
                    {{ description }}
                </UiCardDescription>
            </div>
            <UiButton type="button" class="rounded-xl" @click="$emit('add')">
                {{ addLabel }}
            </UiButton>
        </UiCardHeader>
        <UiCardContent class="space-y-4">
            <slot />
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        </UiCardContent>
    </UiCard>
</template>
