<script setup>
import { router } from "@inertiajs/vue3";
import { Search } from "lucide-vue-next";
import { useFilterForm } from "@/composables/useFilterForm";
import { route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    eventOptions: {
        type: Array,
        required: true,
    },
    resourceTypeOptions: {
        type: Array,
        required: true,
    },
});

const form = useFilterForm(() => props.filters);

function submit() {
    router.get(route('audits.index'), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["audits", "filters"],
    });
}
</script>

<template>
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-4">
        <form class="flex flex-col gap-3 xl:flex-row xl:items-center" @submit.prevent="submit">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground"
                />
                <UiInput
                    v-model="form.search__func"
                    class="h-11 rounded-2xl pl-10"
                    placeholder="按操作者、资源 ID 或路由搜索"
                />
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:w-[420px]">
                <select
                    v-model="form.event__eq"
                    class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-xl border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
                >
                    <option value="">全部事件</option>
                    <option v-for="option in eventOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>

                <select
                    v-model="form.auditable_type__eq"
                    class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-xl border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
                >
                    <option value="">全部资源</option>
                    <option v-for="option in resourceTypeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <UiButton type="submit" class="rounded-xl">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
