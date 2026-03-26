<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ScanSearch } from 'lucide-vue-next';
import { buildDeviceMonitorOverviewHref } from '@/lib/deviceMonitorLinks';
import { route } from '@/lib/routes';

const props = defineProps({
    device: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const canMonitor = computed(() => page.props.auth?.access?.['client-monitor.read'] ?? false);
const monitorHref = computed(() => buildDeviceMonitorOverviewHref(props.device.terminal_id));

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '设备管理', href: route('devices.index') },
    { label: '编辑设备' },
];
</script>

<template>
    <Head :title="`编辑设备 ${device.dev_name}`" />

    <AppLayout
        title="编辑设备"
        description="更新设备基础资料、状态字段与鉴权信息，终端 ID 创建后不可修改。"
        :breadcrumbs="breadcrumbs"
    >
        <UiCard v-if="canMonitor" class="mb-6 rounded-[1.5rem] border border-app-panel-border/80">
            <UiCardContent class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <div class="text-sm font-semibold">客户端监控</div>
                    <p class="app-copy-muted text-sm">
                        查看终端 {{ device.terminal_id }} 的在线会话、事件流和 GPS 定位记录。
                    </p>
                </div>
                <UiButton as-child class="rounded-xl">
                    <Link :href="monitorHref" class="inline-flex items-center gap-2">
                        <ScanSearch class="size-4" />
                        查看客户端监控
                    </Link>
                </UiButton>
            </UiCardContent>
        </UiCard>

        <DevicesForm mode="edit" :device="device" />
    </AppLayout>
</template>
