<script setup>
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
});

// 编辑页和新建页共用同一份表单数据结构，减少前端对两套 payload 的分支判断。
const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: 'MQTT账号管理', href: route('mqtt-accounts.index') },
    { label: '编辑MQTT账号' },
];
</script>

<template>
    <Head :title="`编辑 ${props.account.user_name}`" />

    <AppLayout
        title="编辑MQTT账号"
        description="更新账号资料、设备标识和启用状态。"
        :breadcrumbs="breadcrumbs"
    >
        <MqttAccountsForm mode="edit" :account="account" />
    </AppLayout>
</template>
