<script setup>
import { Link } from '@inertiajs/vue3';
import { Inbox } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    actionHref: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-[1.75rem] border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <div class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
            <Inbox class="size-6" />
        </div>
        <h3 class="mt-5 text-lg font-semibold text-slate-950">{{ title }}</h3>
        <p v-if="description" class="mt-2 max-w-md text-sm leading-6 text-slate-500">
            {{ description }}
        </p>
        <UiButton v-if="actionHref && actionLabel" as-child class="mt-6 rounded-xl">
            <Link :href="actionHref">{{ actionLabel }}</Link>
        </UiButton>
    </div>
</template>
