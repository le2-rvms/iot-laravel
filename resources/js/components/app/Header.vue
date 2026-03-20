<script setup>
import { Link } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <header class="border-b border-app-panel-border/80 bg-background/90 text-app-panel-foreground backdrop-blur">
        <div class="mx-auto flex w-full max-w-7xl items-start justify-between gap-6 px-6 py-4 lg:px-10 lg:py-5">
            <div class="min-w-0 flex-1">
                <div class="mb-3 flex items-center gap-3 lg:hidden">
                    <UiSheet>
                        <UiSheetTrigger as-child>
                            <UiButton variant="outline" size="icon">
                                <Menu class="size-4" />
                            </UiButton>
                        </UiSheetTrigger>
                        <UiSheetContent side="left" class="w-[300px] p-0">
                            <AppSidebar />
                        </UiSheetContent>
                    </UiSheet>
                    <span class="text-sm font-semibold uppercase tracking-[0.2em] text-app-subtle-foreground">
                        {{ $page.props.app.name }}
                    </span>
                </div>

                <UiBreadcrumb v-if="breadcrumbs.length" class="mb-2">
                    <UiBreadcrumbList>
                        <UiBreadcrumbItem
                            v-for="item in breadcrumbs"
                            :key="item.label"
                        >
                            <UiBreadcrumbLink
                                v-if="item.href"
                                as-child
                            >
                                <Link :href="item.href">{{ item.label }}</Link>
                            </UiBreadcrumbLink>
                            <UiBreadcrumbPage v-else>
                                {{ item.label }}
                            </UiBreadcrumbPage>
                        </UiBreadcrumbItem>
                    </UiBreadcrumbList>
                </UiBreadcrumb>

                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-app-panel-foreground lg:text-[2rem]">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="max-w-3xl text-sm leading-6 text-app-subtle-foreground">
                        {{ description }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <slot name="actions" />
                <AppUserMenu />
            </div>
        </div>
    </header>
</template>
