<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const props = defineProps({
    channelTypes: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    email: '',
    channel: 'email',
    target: '',
    daily_limit: 5,
    notes: '',
}).withPrecognition('post', route('precognition.store'));

form.setValidationTimeout(350);

function validateField(field) {
    form.validate(field);
}

function handleChannelChange() {
    form.clearErrors('target');

    if (form.target) {
        validateField('target');
    }
}

function submit() {
    form.post(route('precognition.store'));
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <SharedFormsFormSection title="基础信息" description="填写规则的基本信息。">
            <div class="grid gap-4 md:grid-cols-2">
                <SharedFormsFormFieldShell label="规则名称" for-id="precognition-name" :error="form.errors.name">
                    <UiInput
                        id="precognition-name"
                        v-model="form.name"
                        name="name"
                        :aria-invalid="Boolean(form.errors.name)"
                        @blur="validateField('name')"
                    />
                </SharedFormsFormFieldShell>

                <SharedFormsFormFieldShell label="邮箱" for-id="precognition-email" :error="form.errors.email">
                    <UiInput
                        id="precognition-email"
                        v-model="form.email"
                        name="email"
                        type="email"
                        :aria-invalid="Boolean(form.errors.email)"
                        @blur="validateField('email')"
                    />
                </SharedFormsFormFieldShell>
            </div>
        </SharedFormsFormSection>

        <SharedFormsFormSection title="通知目标" description="切换渠道后，按对应要求填写接收地址。">
            <div class="grid gap-4 md:grid-cols-2">
                <SharedFormsFormFieldShell label="通知渠道" for-id="precognition-channel" :error="form.errors.channel">
                    <UiSelect
                        v-model="form.channel"
                        name="channel"
                        @update:model-value="handleChannelChange"
                    >
                        <UiSelectTrigger id="precognition-channel" class="h-11 w-full rounded-xl">
                            <UiSelectValue placeholder="请选择通知渠道" />
                        </UiSelectTrigger>
                        <UiSelectContent>
                            <UiSelectItem v-for="type in channelTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </UiSelectItem>
                        </UiSelectContent>
                    </UiSelect>
                </SharedFormsFormFieldShell>

                <SharedFormsFormFieldShell
                    label="渠道目标"
                    for-id="precognition-target"
                    description="例如邮箱地址、回调地址或手机号。"
                    :error="form.errors.target"
                >
                    <UiInput
                        id="precognition-target"
                        v-model="form.target"
                        name="target"
                        :aria-invalid="Boolean(form.errors.target)"
                        @blur="validateField('target')"
                    />
                </SharedFormsFormFieldShell>
            </div>
        </SharedFormsFormSection>

        <SharedFormsFormSection title="策略控制" description="设置每日发送上限和补充说明。">
            <div class="grid gap-4 md:grid-cols-2">
                <SharedFormsFormFieldShell
                    label="每日上限"
                    for-id="precognition-daily-limit"
                    description="允许范围为 1 到 100。"
                    :error="form.errors.daily_limit"
                >
                    <UiInput
                        id="precognition-daily-limit"
                        v-model="form.daily_limit"
                        name="daily_limit"
                        type="number"
                        min="1"
                        max="100"
                        :aria-invalid="Boolean(form.errors.daily_limit)"
                        @blur="validateField('daily_limit')"
                    />
                </SharedFormsFormFieldShell>

                <SharedFormsFormFieldShell
                    label="备注"
                    for-id="precognition-notes"
                    description="可选，最多 500 个字符。"
                    :error="form.errors.notes"
                >
                    <UiTextarea
                        id="precognition-notes"
                        v-model="form.notes"
                        name="notes"
                        class="min-h-28 rounded-xl"
                        @blur="validateField('notes')"
                    />
                </SharedFormsFormFieldShell>
            </div>
        </SharedFormsFormSection>

        <UiCard class="app-panel-card">
            <UiCardContent class="flex flex-col gap-3 p-6 sm:flex-row sm:justify-end">
                <UiButton type="submit" class="min-w-40 justify-center rounded-lg" :disabled="form.processing">
                    {{ form.processing ? '提交中' : '提交规则' }}
                </UiButton>
            </UiCardContent>
        </UiCard>
    </form>
</template>
