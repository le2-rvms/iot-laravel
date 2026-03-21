import { reactive, watch } from 'vue';

// 统一管理列表筛选输入框和服务端 filters 回填之间的同步。
export function useFilterForm(getFilters) {
    const form = reactive({});

    watch(getFilters, (filters) => {
        // 列表 partial reload 后会回填新的 filters，这里保持输入框和 URL 状态一致。
        Object.keys(form).forEach((key) => {
            delete form[key];
        });

        Object.assign(form, filters ?? {});
    }, { deep: true, immediate: true });

    return form;
}
