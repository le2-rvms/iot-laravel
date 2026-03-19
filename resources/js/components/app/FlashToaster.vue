<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const page = usePage();
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);

watch(success, (value, oldValue) => {
    if (value && value !== oldValue) {
        toast.success(value);
    }
}, { immediate: true });

watch(error, (value, oldValue) => {
    if (value && value !== oldValue) {
        toast.error(value);
    }
}, { immediate: true });
</script>

<template>
    <UiSonner position="top-right" rich-colors close-button />
</template>
