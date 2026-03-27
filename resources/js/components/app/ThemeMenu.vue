<script setup>
import { computed, ref } from 'vue';
import { Check, MoonStar, Palette, SunMedium } from 'lucide-vue-next';
import {
    MODE_LABELS,
    THEME_LABELS,
    getCurrentTheme,
    setThemePreference,
} from '@/theme';

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
</script>

<template>
    <UiDropdownMenu>
        <UiDropdownMenuTrigger as-child>
            <UiButton variant="outline" class="h-10 rounded-lg border-app-panel-border bg-app-panel px-3 shadow-sm">
                <Palette class="size-4 text-primary" />
                <span class="hidden text-sm font-medium sm:inline">外观</span>
            </UiButton>
        </UiDropdownMenuTrigger>

        <UiDropdownMenuContent align="end" class="w-80 rounded-xl">
            <UiDropdownMenuLabel>界面外观</UiDropdownMenuLabel>
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
                        <UiButton
                            v-for="theme in themeOptions"
                            :key="theme.value"
                            type="button"
                            variant="outline"
                            :data-active="theme.value === currentThemeName"
                            class="h-auto justify-start rounded-lg bg-background px-3 py-2 text-left text-xs font-medium whitespace-normal transition hover:border-primary/40 hover:bg-accent data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:text-primary-foreground"
                            @click.stop="setThemeName(theme.value)"
                        >
                            <span
                                class="mb-2 block h-2.5 w-full rounded-full"
                                :class="themeSwatchClasses[theme.value]"
                            />
                            {{ theme.label }}
                        </UiButton>
                    </div>
                </div>

                <div class="rounded-xl border border-app-panel-border bg-background/80 p-3">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <component :is="currentThemeMode === 'dark' ? MoonStar : SunMedium" class="size-4 text-primary" />
                        <span>明暗模式</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <UiButton
                            v-for="mode in modeOptions"
                            :key="mode.value"
                            type="button"
                            variant="outline"
                            :data-active="mode.value === currentThemeMode"
                            class="h-auto rounded-lg bg-background px-3 py-2 text-sm font-medium transition hover:border-primary/40 hover:bg-accent data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:text-primary-foreground"
                            @click.stop="setThemeMode(mode.value)"
                        >
                            <SunMedium v-if="mode.value === 'light'" class="size-4" />
                            <MoonStar v-else class="size-4" />
                            <span>{{ mode.label }}</span>
                            <Check v-if="mode.value === currentThemeMode" class="size-4" />
                        </UiButton>
                    </div>
                </div>
            </div>
        </UiDropdownMenuContent>
    </UiDropdownMenu>
</template>
