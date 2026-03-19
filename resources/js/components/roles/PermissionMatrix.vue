<script setup>
const props = defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
    selectedPermissions: {
        type: Array,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle']);
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <UiCard
            v-for="group in props.groups"
            :key="group.module"
            class="rounded-2xl border-slate-200 shadow-none"
        >
            <UiCardHeader class="pb-4">
                <UiCardTitle class="text-base">{{ group.label }}</UiCardTitle>
                <UiCardDescription>
                    每个模块统一按 read / write 管理权限。
                </UiCardDescription>
            </UiCardHeader>
            <UiCardContent class="space-y-3">
                <label
                    v-for="permission in group.permissions"
                    :key="permission.name"
                    class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3"
                >
                    <UiCheckbox
                        :disabled="disabled"
                        :model-value="selectedPermissions.includes(permission.name)"
                        @update:model-value="(checked) => emit('toggle', permission.name, checked)"
                    />
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ permission.action_label }}</p>
                        <p class="text-xs text-slate-500">{{ permission.name }}</p>
                    </div>
                </label>
            </UiCardContent>
        </UiCard>
    </div>
</template>
