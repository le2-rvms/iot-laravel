<script setup>
defineProps({
    deviceContext: {
        type: Object,
        default: null,
    },
    clientId: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        v-if="deviceContext && clientId"
        class="flex flex-col gap-4 rounded-[1.5rem] border border-app-panel-border/80 bg-app-panel/60 px-5 py-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-app-subtle-foreground">
                客户端上下文
            </p>
            <div class="text-base font-semibold text-app-panel-foreground">
                当前 Client ID：{{ clientId }}
            </div>
            <p class="text-sm leading-6 text-app-subtle-foreground">
                当前页面仅展示 client_id 精确匹配该 Client ID 的客户端记录。
            </p>
        </div>
    </div>
</template>
