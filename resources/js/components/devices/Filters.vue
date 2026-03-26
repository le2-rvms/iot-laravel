<script setup>
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { useFilterForm } from '@/composables/useFilterForm';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            search__func: '',
            product_key__eq: '',
            device_status__eq: '',
            review_status__eq: '',
            city_relation_id__eq: '',
        }),
    },
});

const form = useFilterForm(() => props.filters);

function submit() {
    router.get('/admin/devices', form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['devices', 'filters'],
    });
}
</script>

<template>
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-4">
        <form
            class="grid gap-3 lg:grid-cols-5 xl:grid-cols-[minmax(0,2.4fr)_repeat(4,minmax(0,1fr))_auto] xl:items-center"
            @submit.prevent="submit"
        >
            <div class="relative lg:col-span-5 xl:col-span-1 xl:min-w-0">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground"
                />
                <UiInput
                    v-model="form.search__func"
                    class="h-11 rounded-2xl pl-10"
                    placeholder="按终端ID、设备名称、产品标识或SIM号搜索"
                />
            </div>

            <UiInput v-model="form.product_key__eq" class="h-11 rounded-xl" placeholder="产品标识" />
            <UiInput v-model="form.device_status__eq" class="h-11 rounded-xl" placeholder="设备状态" />
            <UiInput v-model="form.review_status__eq" class="h-11 rounded-xl" placeholder="审核状态" />
            <UiInput v-model="form.city_relation_id__eq" class="h-11 rounded-xl" placeholder="城市关联ID" />

            <div class="flex items-center gap-2 lg:justify-end">
                <UiButton type="submit" class="rounded-xl">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
