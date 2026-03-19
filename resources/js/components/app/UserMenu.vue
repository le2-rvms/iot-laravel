<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth.user);

function logout() {
    router.post('/logout');
}

function initials(name) {
    return (name || '?')
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
}
</script>

<template>
    <UiDropdownMenu>
        <UiDropdownMenuTrigger as-child>
            <UiButton variant="outline" class="h-11 rounded-2xl px-3">
                <UiAvatar class="mr-3 size-8">
                    <UiAvatarFallback>{{ initials(user?.name) }}</UiAvatarFallback>
                </UiAvatar>
                <div class="hidden text-left sm:block">
                    <div class="text-sm font-medium leading-none">{{ user?.name }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ user?.email }}</div>
                </div>
                <ChevronDown class="ml-3 size-4 text-slate-500" />
            </UiButton>
        </UiDropdownMenuTrigger>

        <UiDropdownMenuContent align="end" class="w-64">
            <UiDropdownMenuLabel>当前登录用户</UiDropdownMenuLabel>
            <UiDropdownMenuSeparator />
            <UiDropdownMenuItem :disabled="true">
                <div class="space-y-1">
                    <div class="font-medium">{{ user?.name }}</div>
                    <div class="text-xs text-slate-500">{{ user?.email }}</div>
                </div>
            </UiDropdownMenuItem>
            <UiDropdownMenuSeparator />
            <UiDropdownMenuItem @select.prevent="logout">
                退出登录
            </UiDropdownMenuItem>
        </UiDropdownMenuContent>
    </UiDropdownMenu>
</template>
