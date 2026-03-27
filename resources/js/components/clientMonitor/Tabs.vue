<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { hrefForRouteTarget } from '@/lib/routes';
import { cn } from '@/lib/utils';

defineProps({
    sections: {
        type: Array,
        required: true,
    },
});

const page = usePage();

function isActive(section) {
    return normalizePath(page.url) === normalizePath(hrefForRouteTarget(section));
}

function normalizePath(url) {
    const value = String(url ?? '');
    const relative = value.startsWith('http')
        ? new URL(value).pathname
        : value.split('?')[0];

    return relative.replace(/\/+$/, '') || '/';
}
</script>

<template>
    <div class="flex flex-wrap gap-2 rounded-xl border border-app-panel-border/80 bg-app-panel/60 p-2">
        <Link
            v-for="section in sections"
            :key="`${section.routeName}:${section.title}`"
            :href="hrefForRouteTarget(section)"
            class="rounded-xl px-4 py-2 text-sm font-medium transition"
            :class="cn(
                isActive(section)
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'text-app-secondary-foreground hover:bg-app-subtle/75',
            )"
        >
            {{ section.title }}
        </Link>
    </div>
</template>
