<script setup>
import { computed } from "vue";
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

const ALL_EVENTS_VALUE = "__all_events__";
const ALL_RESOURCES_VALUE = "__all_resources__";

const selectedEvent = computed({
    get: () => form.event__eq || ALL_EVENTS_VALUE,
    set: (value) => {
        form.event__eq = value === ALL_EVENTS_VALUE ? "" : value;
    },
});

const selectedAuditableType = computed({
    get: () => form.auditable_type__eq || ALL_RESOURCES_VALUE,
    set: (value) => {
        form.auditable_type__eq = value === ALL_RESOURCES_VALUE ? "" : value;
    },
});

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
    <div class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-3">
        <form class="flex flex-col gap-2 xl:flex-row xl:items-center" @submit.prevent="submit">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground"
                />
                <UiInput
                    v-model="form.search__func"
                    class="h-10 rounded-lg pl-10"
                    placeholder="按操作者、资源 ID 或路由搜索"
                />
            </div>

            <div class="grid gap-2 md:grid-cols-2 xl:w-[420px]">
                <UiSelect v-model="selectedEvent">
                    <UiSelectTrigger class="h-10 w-full rounded-lg">
                        <UiSelectValue placeholder="全部事件" />
                    </UiSelectTrigger>
                    <UiSelectContent>
                        <UiSelectItem :value="ALL_EVENTS_VALUE">全部事件</UiSelectItem>
                        <UiSelectItem v-for="option in eventOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </UiSelectItem>
                    </UiSelectContent>
                </UiSelect>

                <UiSelect v-model="selectedAuditableType">
                    <UiSelectTrigger class="h-10 w-full rounded-lg">
                        <UiSelectValue placeholder="全部资源" />
                    </UiSelectTrigger>
                    <UiSelectContent>
                        <UiSelectItem :value="ALL_RESOURCES_VALUE">全部资源</UiSelectItem>
                        <UiSelectItem v-for="option in resourceTypeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </UiSelectItem>
                    </UiSelectContent>
                </UiSelect>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <UiButton type="submit" size="sm" class="rounded-lg">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
