<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { resolveNavigationSections } from '@/lib/navigation';

const page = usePage();

const sections = computed(() => resolveNavigationSections(page.props.auth?.access ?? {}));

function isActive(href) {
    if (href === '/dashboard') {
        return page.url === href;
    }

    return page.url === href || page.url.startsWith(`${href}/`);
}
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="border-b border-slate-200 px-6 py-6">
            <div class="space-y-3">
                <div class="inline-flex size-11 items-center justify-center rounded-2xl bg-slate-950 text-sm font-semibold text-white">
                    {{ $page.props.app.name?.slice(0, 1) }}
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">
                        后台系统
                    </p>
                    <p class="mt-1 text-lg font-semibold text-slate-950">
                        {{ $page.props.app.name }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex-1 space-y-8 overflow-y-auto px-4 py-6">
            <section
                v-for="section in sections"
                :key="section.title"
                class="space-y-3"
            >
                <p class="px-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    {{ section.title }}
                </p>

                <div class="space-y-1">
                    <Link
                        v-for="item in section.items"
                        :key="item.href"
                        :href="item.disabled ? page.url : item.href"
                        class="group flex items-start gap-3 rounded-2xl px-3 py-3 transition"
                        :class="[
                            item.disabled
                                ? 'cursor-not-allowed opacity-50'
                                : isActive(item.href)
                                  ? 'bg-slate-950 text-white shadow-lg shadow-slate-950/10'
                                  : 'text-slate-700 hover:bg-slate-100',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            class="mt-0.5 size-5 shrink-0"
                            :class="isActive(item.href) ? 'text-white' : 'text-slate-500'"
                        />
                        <div class="min-w-0">
                            <div class="font-medium">
                                {{ item.title }}
                            </div>
                            <p
                                class="mt-1 text-xs leading-5"
                                :class="isActive(item.href) ? 'text-slate-300' : 'text-slate-500'"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>

        <div class="border-t border-slate-200 px-6 py-5">
            <div class="rounded-2xl bg-slate-100 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    认证状态
                </p>
                <p class="mt-2 text-sm font-medium text-slate-950">
                    {{ $page.props.auth.user?.name }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    邮箱{{ $page.props.auth.user?.email_verified_at ? '已验证' : '待验证' }}
                </p>
            </div>
        </div>
    </div>
</template>
