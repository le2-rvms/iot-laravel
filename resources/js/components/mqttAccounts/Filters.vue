<script setup>
import { watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Search } from "lucide-vue-next";
import { useFilterForm } from "@/composables/useFilterForm";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            search__func: "",
        }),
    },
});

console.log("[MqttAccountsFilters] setup run", props.filters);

watch(
    () => props.filters,
    (filters) => {
        console.log("[MqttAccountsFilters] props.filters changed", filters);
    },
    { deep: true },
);

// MQTT 列表的搜索输入和服务端 filters 保持同一份表单结构。
const form = useFilterForm(() => props.filters);

function submit() {
    router.get(
        "/mqtt-accounts",
        // form 只维护查询条件，直接透传给 router.get 即可。
        form,
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            // 搜索只刷新列表相关数据，避免整页状态和提示被无关请求覆盖。
            only: ["accounts", "filters"],
        },
    );
}
</script>

<template>
    <div
        class="rounded-t-[1.5rem] border-b border-app-panel-border/80 px-5 py-4"
    >
        <form
            class="flex flex-col gap-3 lg:flex-row lg:items-center"
            @submit.prevent="submit"
        >
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-app-subtle-foreground"
                />
                <UiInput
                    v-model="form.search__func"
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
