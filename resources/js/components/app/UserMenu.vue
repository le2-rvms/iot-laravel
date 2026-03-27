<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown, KeyRound, LogOut } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { route } from '@/lib/routes';

const props = defineProps({
    triggerClass: {
        type: [String, Array, Object],
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const access = computed(() => page.props.auth.access ?? {});
// 修改密码页和提交都归到 write 权限，菜单入口保持同一判定口径。
const canUpdatePassword = computed(() => access.value['password.write'] === true);

function logout() {
    router.post(route('logout'));
}

function goToPasswordPage() {
    // 菜单直接跳到独立改密页，和资源路由 / 权限控制保持同一入口。
    router.get(route('security-password.edit'));
}

</script>

<template>
    <UiDropdownMenu>
        <UiDropdownMenuTrigger as-child>
            <UiButton
                variant="outline"
                :class="cn('h-10 rounded-lg border-app-panel-border bg-app-panel px-3 shadow-sm', props.triggerClass)"
            >
                <div class="hidden text-left sm:block">
                    <div class="text-sm font-medium leading-none">{{ user?.name }}</div>
                </div>
                <ChevronDown class="app-copy-muted ml-2 size-4" />
            </UiButton>
        </UiDropdownMenuTrigger>

        <UiDropdownMenuContent align="end" class="w-80 rounded-xl">
            <UiDropdownMenuLabel>我的账号</UiDropdownMenuLabel>
            <UiDropdownMenuSeparator />
            <UiDropdownMenuItem :disabled="true">
                <div class="space-y-1">
                    <div class="font-medium">{{ user?.name }}</div>
                    <div class="app-copy-muted text-xs">{{ user?.email }}</div>
                </div>
            </UiDropdownMenuItem>
            <UiDropdownMenuSeparator />
            <div class="space-y-4 px-2 py-2">
                <div class="rounded-xl border border-app-panel-border bg-background/80 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-medium">认证状态</div>
                            <p class="mt-1 text-xs leading-5 text-muted-foreground">
                                当前账号的邮箱验证状态。
                            </p>
                        </div>
                        <UiBadge :variant="user?.email_verified_at ? 'default' : 'secondary'">
                            {{ user?.email_verified_at ? '已验证' : '待验证' }}
                        </UiBadge>
                    </div>
                </div>
            </div>
            <template v-if="canUpdatePassword">
                <UiDropdownMenuSeparator />
                <UiDropdownMenuItem @select.prevent="goToPasswordPage">
                    <KeyRound class="size-4" />
                    修改密码
                </UiDropdownMenuItem>
            </template>
            <UiDropdownMenuSeparator />
            <UiDropdownMenuItem @select.prevent="logout">
                <LogOut class="size-4" />
                退出登录
            </UiDropdownMenuItem>
        </UiDropdownMenuContent>
    </UiDropdownMenu>
</template>
