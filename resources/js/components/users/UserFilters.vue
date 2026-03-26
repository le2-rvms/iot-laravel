<script setup>
import { router } from "@inertiajs/vue3";
import { Search } from "lucide-vue-next";
import { useFilterForm } from "@/composables/useFilterForm";
import { route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            search__func: "",
        }),
    },
});

// 管理员用户列表的搜索输入和服务端 filters 保持同一份表单结构。
const form = useFilterForm(() => props.filters);

function submit() {
    // form 只包含查询字段，可以直接作为 query 参数提交。
    router.get(route('admin-users.index'), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        // 搜索只刷新列表和 filters，避免页面其余状态被无关请求覆盖。
        only: ["users", "filters"],
    });
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
                    placeholder="按姓名或邮箱搜索"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <UiButton type="submit" class="rounded-xl">搜索</UiButton>
            </div>
        </form>
    </div>
</template>
