<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <SharedFormsFormFieldShell :label="label" :description="description" :error="error">
        <div class="grid gap-3 md:grid-cols-2">
            <label
                v-for="option in options"
                :key="option.value"
                class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3"
            >
                <UiCheckbox
                    :model-value="modelValue.includes(option.value)"
                    @update:model-value="
                        (checked) => emit(
                            'update:modelValue',
                            checked
                                ? [...modelValue, option.value]
                                : modelValue.filter((item) => item !== option.value),
                        )
                    "
                />
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ option.label }}</p>
                    <p v-if="option.description" class="text-xs text-slate-500">{{ option.description }}</p>
                </div>
            </label>
        </div>
    </SharedFormsFormFieldShell>
</template>
