<template>
    <UiCard
        class="min-w-0 overflow-hidden rounded-[0.75rem] border-app-panel-border bg-app-panel text-app-panel-foreground shadow-sm"
    >
        <UiCardContent class="p-0">
            <slot />
        </UiCardContent>
        <div
            v-if="$slots.footer"
            class="border-t border-app-panel-border/80 px-5 py-4"
        >
            <slot name="footer" />
        </div>
    </UiCard>
</template>
