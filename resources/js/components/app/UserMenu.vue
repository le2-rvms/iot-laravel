<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown, KeyRound, LogOut, MoonStar, Palette, SunMedium } from 'lucide-vue-next';
import {
    MODE_LABELS,
    THEME_LABELS,
    getCurrentTheme,
    setThemePreference,
} from '@/theme';
import { route } from '@/lib/routes';

const page = usePage();
const user = computed(() => page.props.auth.user);
const access = computed(() => page.props.auth.access ?? {});
const themeState = ref(getCurrentTheme());

const themeOptions = Object.entries(THEME_LABELS).map(([value, label]) => ({
    value,
    label,
}));

const modeOptions = Object.entries(MODE_LABELS).map(([value, label]) => ({
    value,
    label,
}));

const currentThemeName = computed(() => themeState.value.name);
const currentThemeMode = computed(() => themeState.value.mode);
const currentThemeLabel = computed(() => THEME_LABELS[currentThemeName.value]);
const currentModeLabel = computed(() => MODE_LABELS[currentThemeMode.value]);
// 修改密码页和提交都归到 write 权限，菜单入口保持同一判定口径。
const canUpdatePassword = computed(() => access.value['password.write'] === true);

const themeSwatchClasses = {
    neutral: 'bg-linear-to-br from-slate-500 to-slate-800',
    cool: 'bg-linear-to-br from-sky-400 to-teal-500',
    warm: 'bg-linear-to-br from-amber-300 to-orange-500',
};

function setThemeName(name) {
    themeState.value = setThemePreference({ name });
}

function setThemeMode(mode) {
    themeState.value = setThemePreference({ mode });
}

function logout() {
    router.post(route('logout'));
}

function goToPasswordPage() {
    // 菜单直接跳到独立改密页，和资源路由 / 权限控制保持同一入口。
    router.get(route('security-password.edit'));
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
            <UiButton variant="outline" class="h-11 rounded-xl border-app-panel-border bg-app-panel px-3 shadow-sm">
                <UiAvatar class="mr-3 size-8">
                    <UiAvatarFallback>{{ initials(user?.name) }}</UiAvatarFallback>
                </UiAvatar>
                <div class="hidden text-left sm:block">
                    <div class="text-sm font-medium leading-none">{{ user?.name }}</div>
                    <div class="app-copy-muted mt-1 text-xs">{{ user?.email }}</div>
                </div>
                <ChevronDown class="app-copy-muted ml-3 size-4" />
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
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <Palette class="size-4 text-primary" />
                        <span>界面主题</span>
                    </div>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        当前为 {{ currentThemeLabel }} · {{ currentModeLabel }}
                    </p>

                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <button
                            v-for="theme in themeOptions"
                            :key="theme.value"
                            type="button"
                            :data-active="theme.value === currentThemeName"
                            class="rounded-lg border bg-background px-3 py-2 text-left text-xs font-medium transition hover:border-primary/40 hover:bg-accent data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:text-primary-foreground"
                            @click.stop="setThemeName(theme.value)"
                        >
                            <span
                                class="mb-2 block h-2.5 w-full rounded-full"
                                :class="themeSwatchClasses[theme.value]"
                            />
                            {{ theme.label }}
                        </button>
                    </div>
                </div>

                <div class="rounded-xl border border-app-panel-border bg-background/80 p-3">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <component :is="currentThemeMode === 'dark' ? MoonStar : SunMedium" class="size-4 text-primary" />
                        <span>明暗模式</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <button
                            v-for="mode in modeOptions"
                            :key="mode.value"
                            type="button"
                            :data-active="mode.value === currentThemeMode"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-3 py-2 text-sm font-medium transition hover:border-primary/40 hover:bg-accent data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:text-primary-foreground"
                            @click.stop="setThemeMode(mode.value)"
                        >
                            <SunMedium v-if="mode.value === 'light'" class="size-4" />
                            <MoonStar v-else class="size-4" />
                            <span>{{ mode.label }}</span>
                            <Check v-if="mode.value === currentThemeMode" class="size-4" />
                        </button>
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
