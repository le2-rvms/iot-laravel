<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

defineProps({
    sections: {
        type: Array,
        required: true,
    },
});

const page = usePage();

function normalizePath(url) {
    return String(url ?? '').split('?')[0];
}

function isActive(href) {
    return normalizePath(page.url) === normalizePath(href);
}
</script>

<template>
    <div class="flex flex-wrap gap-2 rounded-[1.5rem] border border-app-panel-border/80 bg-app-panel/60 p-2">
        <Link
            v-for="section in sections"
            :key="section.href"
            :href="section.href"
            class="rounded-xl px-4 py-2 text-sm font-medium transition"
            :class="cn(
                isActive(section.href)
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'text-app-secondary-foreground hover:bg-app-subtle/75',
            )"
        >
            {{ section.title }}
        </Link>
    </div>
</template>
