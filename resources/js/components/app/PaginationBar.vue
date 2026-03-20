<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});

const normalizedLinks = computed(() => props.links.filter((link) => link.label !== '...'));

function decodeLabel(label) {
    return label
        .replaceAll('&laquo;', '«')
        .replaceAll('&raquo;', '»')
        .replace(/<[^>]*>/g, '')
        .trim();
}
</script>

<template>
    <div v-if="normalizedLinks.length > 3" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-app-subtle-foreground">
            支持翻页查看更多数据。
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <template v-for="link in normalizedLinks" :key="`${link.label}-${link.url}`">
                <UiButton
                    v-if="!link.url"
                    variant="outline"
                    class="rounded-xl"
                    disabled
                >
                    {{ decodeLabel(link.label) }}
                </UiButton>

                <UiButton
                    v-else
                    as-child
                    :variant="link.active ? 'default' : 'outline'"
                    class="rounded-xl"
                >
                    <Link :href="link.url" preserve-scroll>
                        {{ decodeLabel(link.label) }}
                    </Link>
                </UiButton>
            </template>
        </div>
    </div>
</template>
