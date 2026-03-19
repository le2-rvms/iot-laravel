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
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.08),_transparent_40%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)]">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-6 py-12">
            <div class="grid w-full gap-10 lg:grid-cols-[1.1fr_460px]">
                <div class="hidden flex-col justify-between rounded-[2rem] border border-white/70 bg-slate-950 px-10 py-12 text-slate-100 shadow-2xl lg:flex">
                    <div class="space-y-6">
                        <div class="inline-flex w-fit items-center rounded-full border border-white/15 px-4 py-1 text-sm text-slate-300">
                            {{ $page.props.app.name }}
                        </div>
                        <div class="space-y-4">
                            <h1 class="max-w-xl text-4xl font-semibold tracking-tight">
                                服务端主导的后台管理骨架
                            </h1>
                            <p class="max-w-lg text-base leading-7 text-slate-300">
                                Laravel 负责路由、鉴权与业务逻辑，Inertia 与 Vue 只负责页面桥接与交互层。
                            </p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-sm leading-6 text-slate-300">
                        认证页统一收敛在单独布局下，保持视觉与交互的一致性。
                    </div>
                </div>

                <div class="flex items-center justify-center">
                    <div class="w-full max-w-md space-y-6 rounded-[2rem] border border-slate-200/80 bg-white/90 p-8 shadow-xl backdrop-blur">
                        <div class="space-y-2">
                            <p class="text-sm font-medium uppercase tracking-[0.24em] text-slate-500">
                                {{ $page.props.app.name }}
                            </p>
                            <h2 class="text-3xl font-semibold tracking-tight text-slate-950">
                                {{ title }}
                            </h2>
                            <p v-if="description" class="text-sm leading-6 text-slate-600">
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
