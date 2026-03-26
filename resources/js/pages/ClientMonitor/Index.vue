<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    sections: {
        type: Array,
        required: true,
    },
    deviceContext: {
        type: Object,
        required: true,
    },
    previews: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: '客户端监控', href: props.deviceContext.rootHref },
];
</script>

<template>
    <Head title="客户端监控" />

    <AppLayout
        title="客户端监控"
        description="查看客户端在线会话、鉴权结果、命令事件和连接事件。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar
                title="客户端事件总览"
                :description="`查看 Client ID ${filters.client_id__eq} 的最新在线会话、鉴权、命令和连接事件。`"
            />

            <ClientMonitorDeviceContextBanner :device-context="deviceContext" :client-id="filters.client_id__eq" />

            <div class="grid gap-6">
                <UiCard class="rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">在线会话</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条在线会话记录。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="sections[0].href">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorSessionsTable
                            v-if="previews?.sessions?.length"
                            :sessions="{ data: previews.sessions }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有在线会话"
                            description="客户端产生活跃会话后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">鉴权事件</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条鉴权事件。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="sections[1].href">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorAuthEventsTable
                            v-if="previews?.authEvents?.length"
                            :auth-events="{ data: previews.authEvents }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有鉴权事件"
                            description="客户端发生鉴权成功或失败后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">命令事件</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条命令事件。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="sections[2].href">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorCmdEventsTable
                            v-if="previews?.cmdEvents?.length"
                            :cmd-events="{ data: previews.cmdEvents }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有命令事件"
                            description="客户端发生命令上下行后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">连接事件</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条连接事件。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="sections[3].href">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorConnEventsTable
                            v-if="previews?.connEvents?.length"
                            :conn-events="{ data: previews.connEvents }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有连接事件"
                            description="客户端建立或断开连接后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>
            </div>
        </div>
    </AppLayout>
</template>
