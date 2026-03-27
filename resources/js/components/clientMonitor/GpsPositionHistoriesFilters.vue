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
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-3">
        <form class="flex flex-wrap gap-2 2xl:items-center" @submit.prevent="submit">
            <div class="relative min-w-0 flex-[2_1_280px]">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground" />
                <UiInput
                    v-model="form.search__func"
                    class="h-10 rounded-lg pl-10"
                    placeholder="按终端ID或设备名称搜索"
                />
            </div>
            <UiInput v-model="form.status__eq" class="h-10 min-w-[140px] flex-[1_1_140px] rounded-lg" placeholder="状态" />
            <UiInput v-model="form.alarm__eq" class="h-10 min-w-[140px] flex-[1_1_140px] rounded-lg" placeholder="告警" />
            <UiInput v-model="form.gps_time__gte" type="datetime-local" class="h-10 min-w-[200px] flex-[1_1_200px] rounded-lg" />
            <UiInput v-model="form.gps_time__lte" type="datetime-local" class="h-10 min-w-[200px] flex-[1_1_200px] rounded-lg" />
            <div class="flex flex-[0_0_auto] items-center gap-2">
                <UiButton type="submit" size="sm" class="rounded-lg">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
