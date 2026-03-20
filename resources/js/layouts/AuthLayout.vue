<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-app-shell">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-x-0 top-0 h-72 bg-linear-to-b from-primary/10 via-chart-2/6 to-transparent" />
            <div class="absolute left-[-6rem] top-24 size-56 rounded-full bg-chart-3/10 blur-3xl" />
            <div class="absolute right-[-4rem] top-10 size-72 rounded-full bg-chart-1/9 blur-3xl" />
        </div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center px-6 py-12">
            <div class="grid w-full gap-10 lg:grid-cols-[1.1fr_460px]">
                <div class="hidden flex-col justify-between rounded-[1.5rem] border border-app-panel-border bg-app-panel px-10 py-12 text-app-panel-foreground shadow-sm lg:flex">
                    <div class="space-y-6">
                        <div class="inline-flex w-fit items-center rounded-full border border-app-subtle-border bg-app-subtle px-4 py-1 text-sm text-app-subtle-foreground">
                            {{ $page.props.app.name }}
                        </div>
                        <div class="space-y-4">
                            <h1 class="max-w-xl text-4xl font-semibold tracking-tight">
                                服务端主导的后台管理骨架
                            </h1>
                            <p class="max-w-lg text-base leading-7 text-app-subtle-foreground">
                                Laravel 负责路由、鉴权与业务逻辑，Inertia 与 Vue 只负责页面桥接与交互层。
                            </p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-app-subtle-border bg-app-subtle/70 p-6 text-sm leading-6 text-app-subtle-foreground">
                        认证页统一收敛在单独布局下，保持视觉与交互的一致性。
                    </div>
                </div>

                <div class="flex items-center justify-center">
                    <div class="w-full max-w-md space-y-6 rounded-[1.5rem] border border-app-panel-border bg-app-panel p-8 text-app-panel-foreground shadow-sm backdrop-blur">
                        <div class="space-y-2">
                            <p class="text-sm font-medium uppercase tracking-[0.24em] text-app-subtle-foreground">
                                {{ $page.props.app.name }}
                            </p>
                            <h2 class="text-3xl font-semibold tracking-tight text-app-panel-foreground">
                                {{ title }}
                            </h2>
                            <p v-if="description" class="text-sm leading-6 text-app-subtle-foreground">
                                {{ description }}
                            </p>
                        </div>

                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
