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
    <div class="flex flex-col items-center justify-center rounded-[1.5rem] border border-dashed border-app-subtle-border bg-app-panel px-6 py-16 text-center">
        <div class="flex size-14 items-center justify-center rounded-xl bg-app-subtle text-app-subtle-foreground">
            <Inbox class="size-6" />
        </div>
        <h3 class="mt-5 text-lg font-semibold text-app-panel-foreground">{{ title }}</h3>
        <p v-if="description" class="mt-2 max-w-md text-sm leading-6 text-app-subtle-foreground">
            {{ description }}
        </p>
        <UiButton v-if="actionHref && actionLabel" as-child class="mt-6 rounded-xl">
            <Link :href="actionHref">{{ actionLabel }}</Link>
        </UiButton>
    </div>
</template>
