<script setup>
import { computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const page = usePage();
const success = computed(() => page.props.flash?.success);
const successKey = computed(() => page.props.flash?.success_key);
const error = computed(() => page.props.flash?.error);
const errorKey = computed(() => page.props.flash?.error_key);
const displayedKeys = new Set();

function showSuccessToast() {
    if (success.value && successKey.value && !displayedKeys.has(successKey.value)) {
        displayedKeys.add(successKey.value);
        toast.success(success.value);
    }
}

function showErrorToast() {
    if (error.value && errorKey.value && !displayedKeys.has(errorKey.value)) {
        displayedKeys.add(errorKey.value);
        toast.error(error.value);
    }
}

onMounted(() => {
    showSuccessToast();
    showErrorToast();
});

watch([success, successKey], () => {
    showSuccessToast();
}, { flush: 'post' });

watch([error, errorKey], () => {
    showErrorToast();
}, { flush: 'post' });
</script>

<template>
    <UiSonner position="top-right" rich-colors close-button />
</template>
