<script setup>
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { useFilterForm } from '@/composables/useFilterForm';
import { route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            search__func: '',
        }),
    },
});

const form = useFilterForm(() => props.filters);

function submit() {
    router.get(route('device-products.index'), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['products', 'filters'],
    });
}
</script>

<template>
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-3">
        <form class="flex flex-col gap-2 lg:flex-row lg:items-center" @submit.prevent="submit">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground"
                />
                <UiInput
                    v-model="form.search__func"
                    class="h-10 rounded-lg pl-10"
                    placeholder="按产品标识、名称、描述、厂商、协议或分类搜索"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <UiButton type="submit" size="sm" class="rounded-lg">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
