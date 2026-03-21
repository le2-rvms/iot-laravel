<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    account: {
        type: Object,
        required: true,
    },
});

// 新建页也传 account 对象，和编辑页保持同一份表单输入结构与默认值来源。
const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: 'MQTT账号管理', href: '/mqtt-accounts' },
    { label: '新建MQTT账号' },
];
</script>

<template>
    <Head title="新建MQTT账号" />

    <AppLayout
        title="新建MQTT账号"
        description="为设备或服务创建可用的 MQTT 连接账号。"
        :breadcrumbs="breadcrumbs"
    >
        <MqttAccountsForm mode="create" :account="account" />
    </AppLayout>
</template>
