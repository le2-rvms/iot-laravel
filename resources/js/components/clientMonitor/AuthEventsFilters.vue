<script setup>
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { useFilterForm } from '@/composables/useFilterForm';
import { hrefForRouteTarget } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    routeTarget: {
        type: Object,
        required: true,
    },
});

const form = useFilterForm(() => props.filters);

function submit() {
    router.get(hrefForRouteTarget(props.routeTarget), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['authEvents', 'filters'],
    });
}
</script>

<template>
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-4">
        <form class="grid gap-3 lg:grid-cols-[minmax(0,2fr)_repeat(2,minmax(0,1fr))_auto] lg:items-center" @submit.prevent="submit">
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground" />
                <UiInput
                    v-model="form.search__func"
                    class="h-11 rounded-2xl pl-10"
                    placeholder="按鉴权结果、原因、客户端ID、用户名或协议搜索"
                />
            </div>
            <UiInput v-model="form.result__eq" class="h-11 rounded-xl" placeholder="鉴权结果" />
            <UiInput v-model="form.protocol__eq" class="h-11 rounded-xl" placeholder="协议" />
            <div class="flex items-center gap-2 lg:justify-end">
                <UiButton type="submit" class="rounded-xl">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
