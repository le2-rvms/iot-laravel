<script setup>
import { Link } from "@inertiajs/vue3";
import { Menu } from "lucide-vue-next";

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <header
        class="border-b border-app-panel-border/45 bg-background/90 text-app-panel-foreground backdrop-blur"
    >
        <div
            class="mx-auto flex h-[57px] w-full max-w-[1600px] items-center justify-between gap-5 px-5 py-1.5 lg:px-8 xl:px-6"
        >
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3 lg:hidden">
                    <UiSheet>
                        <UiSheetTrigger as-child>
                            <UiButton variant="outline" size="icon">
                                <Menu class="size-4" />
                            </UiButton>
                        </UiSheetTrigger>
                        <UiSheetContent side="left" class="w-[240px] p-0">
                            <AppSidebar />
                        </UiSheetContent>
                    </UiSheet>
                    <span
                        class="text-sm font-semibold uppercase tracking-[0.2em] text-app-subtle-foreground"
                    >
                        {{ $page.props.app.name }}
                    </span>
                </div>

                <UiBreadcrumb v-if="breadcrumbs.length" class="hidden lg:block">
                    <UiBreadcrumbList>
                        <template
                            v-for="(item, index) in breadcrumbs"
                            :key="item.href ?? item.label"
                        >
                            <UiBreadcrumbItem>
                                <UiBreadcrumbLink v-if="item.href" as-child>
                                    <Link :href="item.href">{{
                                        item.label
                                    }}</Link>
                                </UiBreadcrumbLink>
                                <UiBreadcrumbPage v-else>
                                    {{ item.label }}
                                </UiBreadcrumbPage>
                            </UiBreadcrumbItem>
                            <UiBreadcrumbSeparator
                                v-if="index < breadcrumbs.length - 1"
                            />
                        </template>
                    </UiBreadcrumbList>
                </UiBreadcrumb>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <slot name="actions" />
                <div class="flex items-center gap-3 lg:hidden">
                    <AppThemeMenu />
                    <AppUserMenu />
                </div>
            </div>
        </div>
    </header>
</template>
