<script setup>
import { Deferred, Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    quickLinks: {
        type: Array,
        default: () => [],
    },
    recentUsers: {
        type: Array,
        default: undefined,
    },
    systemCards: {
        type: Array,
        default: undefined,
    },
});

const breadcrumbs = [
    { label: '仪表盘' },
];
</script>

<template>
    <Head title="仪表盘" />

    <AppLayout
        title="仪表盘"
        description="查看系统概览、常用入口和最近新增的账号。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <section class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <UiCard class="rounded-[1.5rem] border border-app-panel-border bg-app-panel text-app-panel-foreground shadow-sm">
                    <UiCardHeader>
                        <UiCardDescription class="text-app-subtle-foreground">欢迎回来</UiCardDescription>
                        <UiCardTitle class="text-3xl tracking-tight">
                            {{ $page.props.auth.user?.name }}
                        </UiCardTitle>
                    </UiCardHeader>
                    <UiCardContent class="grid gap-6 lg:grid-cols-[200px_1fr]">
                        <div class="rounded-xl border border-app-subtle-border bg-app-subtle/70 p-5">
                            <p class="text-sm text-app-subtle-foreground">当前用户总数</p>
                            <p class="mt-3 text-4xl font-semibold">{{ stats.usersCount }}</p>
                        </div>
                        <div class="grid gap-3">
                            <UiCard
                                v-for="link in quickLinks"
                                :key="link.href"
                                class="rounded-xl border border-app-subtle-border bg-app-subtle/70 text-app-panel-foreground shadow-none"
                            >
                                <UiCardContent class="flex items-center justify-between gap-4 p-5">
                                    <div>
                                        <p class="font-medium">{{ link.title }}</p>
                                        <p class="mt-1 text-sm text-app-subtle-foreground">{{ link.description }}</p>
                                    </div>
                                    <UiButton as-child class="rounded-xl">
                                        <Link :href="link.href">进入</Link>
                                    </UiButton>
                                </UiCardContent>
                            </UiCard>
                        </div>
                    </UiCardContent>
                </UiCard>

                <Deferred data="systemCards">
                    <template #fallback>
                        <AppLoadingState :rows="2" />
                    </template>

                    <div class="grid gap-4">
                        <UiCard
                            v-for="card in systemCards"
                            :key="card.title"
                            class="rounded-[1.5rem] border-app-panel-border bg-app-panel shadow-sm"
                        >
                            <UiCardHeader>
                                <UiCardDescription>{{ card.status }}</UiCardDescription>
                                <UiCardTitle>{{ card.title }}</UiCardTitle>
                            </UiCardHeader>
                            <UiCardContent class="text-sm leading-6 text-app-subtle-foreground">
                                {{ card.description }}
                            </UiCardContent>
                        </UiCard>
                    </div>
                </Deferred>
            </section>

            <section>
                <Deferred data="recentUsers">
                    <template #fallback>
                        <AppLoadingState :rows="4" />
                    </template>

                    <AppDataTableShell v-if="recentUsers?.length">
                        <div class="border-b border-app-panel-border/80 px-5 py-5">
                            <h2 class="text-lg font-semibold text-app-panel-foreground">最近创建的用户</h2>
                            <p class="mt-1 text-sm text-app-subtle-foreground">这里显示最近创建的账号记录。</p>
                        </div>
                        <UiTable>
                            <UiTableHeader>
                                <UiTableRow>
                                    <UiTableHead>姓名</UiTableHead>
                                    <UiTableHead>邮箱</UiTableHead>
                                    <UiTableHead>验证状态</UiTableHead>
                                    <UiTableHead>创建时间</UiTableHead>
                                </UiTableRow>
                            </UiTableHeader>
                            <UiTableBody>
                                <UiTableRow v-for="user in recentUsers" :key="user.id">
                                    <UiTableCell class="font-medium">{{ user.name }}</UiTableCell>
                                    <UiTableCell>{{ user.email }}</UiTableCell>
                                    <UiTableCell>
                                        <UiBadge :variant="user.verified ? 'default' : 'secondary'">
                                            {{ user.verified ? '已验证' : '待验证' }}
                                        </UiBadge>
                                    </UiTableCell>
                                    <UiTableCell>{{ user.created_at?.replace('T', ' ').slice(0, 16) }}</UiTableCell>
                                </UiTableRow>
                            </UiTableBody>
                        </UiTable>
                    </AppDataTableShell>

                    <AppEmptyState
                        v-else
                        title="暂无用户数据"
                        description="创建第一个后台用户后，这里会显示最近的入库记录。"
                        action-label="前往用户管理"
                        action-href="/users"
                    />
                </Deferred>
            </section>
        </div>
    </AppLayout>
</template>
