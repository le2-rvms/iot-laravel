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
        only: ['gpsPositionHistories', 'filters'],
    });
}
</script>

<template>
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-4">
        <form class="grid gap-3 xl:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto] xl:items-center" @submit.prevent="submit">
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground" />
                <UiInput
                    v-model="form.search__func"
                    class="h-11 rounded-2xl pl-10"
                    placeholder="按终端ID或设备名称搜索"
                />
            </div>
            <UiInput v-model="form.status__eq" class="h-11 rounded-xl" placeholder="状态" />
            <UiInput v-model="form.alarm__eq" class="h-11 rounded-xl" placeholder="告警" />
            <UiInput v-model="form.gps_time__gte" type="datetime-local" class="h-11 rounded-xl" />
            <UiInput v-model="form.gps_time__lte" type="datetime-local" class="h-11 rounded-xl" />
            <div class="flex items-center gap-2 xl:justify-end">
                <UiButton type="submit" class="rounded-xl">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
