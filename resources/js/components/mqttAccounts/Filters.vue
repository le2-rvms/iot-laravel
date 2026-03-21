<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
});

const state = reactive({
    search: props.filters.search ?? '',
});

watch(
    () => props.filters,
    (filters) => {
        state.search = filters.search ?? '';
    },
    { deep: true },
);

function submit() {
    router.get('/mqtt-accounts', {
        search: state.search || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        // 搜索只刷新列表相关数据，避免整页状态和提示被无关请求覆盖。
        only: ['accounts', 'filters'],
    });
}
</script>

<template>
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-4">
        <form class="flex flex-col gap-3 lg:flex-row lg:items-center" @submit.prevent="submit">
            <div class="relative flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground" />
                <UiInput
                    v-model="state.search"
                    class="h-11 rounded-2xl pl-10"
                    placeholder="按账号名、客户端标识或设备信息搜索"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <UiButton type="submit" class="rounded-xl">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
