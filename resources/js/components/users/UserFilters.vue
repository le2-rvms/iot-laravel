<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { RotateCw, Search } from 'lucide-vue-next';

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
);

function submit() {
    const params = {};

    if (state.search) {
        params.search = state.search;
    }

    router.get('/users', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users', 'filters'],
    });
}

function resetFilters() {
    state.search = '';
    router.get('/users', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users', 'filters'],
    });
}

function refresh() {
    router.reload({
        only: ['users', 'filters'],
    });
}
</script>

<template>
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm">
        <form class="flex flex-col gap-3 lg:flex-row lg:items-center" @submit.prevent="submit">
            <div class="relative flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <UiInput
                    v-model="state.search"
                    class="h-11 rounded-2xl pl-10"
                    placeholder="按姓名或邮箱搜索"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <UiButton type="submit" class="rounded-xl">搜索</UiButton>
                <UiButton type="button" variant="outline" class="rounded-xl" @click="resetFilters">
                    重置
                </UiButton>
                <UiButton type="button" variant="outline" class="rounded-xl" @click="refresh">
                    <RotateCw class="mr-2 size-4" />
                    局部刷新
                </UiButton>
            </div>
        </form>
    </div>
</template>
