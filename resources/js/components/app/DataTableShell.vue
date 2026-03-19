<template>
    <UiCard class="overflow-hidden rounded-[1.75rem] border-slate-200 shadow-sm">
        <UiCardContent class="p-0">
            <slot />
        </UiCardContent>
        <div v-if="$slots.footer" class="border-t border-slate-200 px-6 py-4">
            <slot name="footer" />
        </div>
    </UiCard>
</template>
