<script setup>
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const confirmDialog = useConfirmDialog();
</script>

<template>
    <UiDialog :open="confirmDialog.state.open" @update:open="(value) => !value && confirmDialog.close()">
        <UiDialogContent class="sm:max-w-md">
            <UiDialogHeader>
                <UiDialogTitle>{{ confirmDialog.state.title }}</UiDialogTitle>
                <UiDialogDescription>
                    {{ confirmDialog.state.description }}
                </UiDialogDescription>
            </UiDialogHeader>

            <UiDialogFooter class="gap-2">
                <UiButton variant="outline" @click="confirmDialog.close">
                    {{ confirmDialog.state.cancelLabel }}
                </UiButton>
                <UiButton
                    :variant="confirmDialog.state.variant === 'destructive' ? 'destructive' : 'default'"
                    @click="confirmDialog.confirm"
                >
                    {{ confirmDialog.state.confirmLabel }}
                </UiButton>
            </UiDialogFooter>
        </UiDialogContent>
    </UiDialog>
</template>
