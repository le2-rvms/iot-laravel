<script setup>
import { useForm } from '@inertiajs/vue3';

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
}).withPrecognition('post', '/settings/precognition');

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
    form.post('/settings/precognition');
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <SharedFormsFormSection title="基础信息" description="示例重点是 blur 触发服务端实时预校验。">
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

        <SharedFormsFormSection title="通知目标" description="切换渠道后，渠道目标会重新按服务端规则校验。">
            <div class="grid gap-4 md:grid-cols-2">
                <SharedFormsFormFieldShell label="通知渠道" for-id="precognition-channel" :error="form.errors.channel">
                    <select
                        id="precognition-channel"
                        v-model="form.channel"
                        name="channel"
                        class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-xl border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
                        @change="handleChannelChange"
                    >
                        <option v-for="type in channelTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                </SharedFormsFormFieldShell>

                <SharedFormsFormFieldShell
                    label="渠道目标"
                    for-id="precognition-target"
                    description="失焦后会触发服务端实时校验，并返回当前渠道对应的格式错误。"
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

        <SharedFormsFormSection title="策略控制" description="演示服务端数字范围校验与可选备注。">
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
                    <textarea
                        id="precognition-notes"
                        v-model="form.notes"
                        name="notes"
                        class="border-input focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-28 w-full rounded-xl border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px]"
                        @blur="validateField('notes')"
                    />
                </SharedFormsFormFieldShell>
            </div>
        </SharedFormsFormSection>

        <UiCard class="rounded-[1.75rem] border-slate-200 shadow-sm">
            <UiCardContent class="flex flex-col gap-3 p-6 sm:flex-row sm:justify-end">
                <UiButton type="submit" class="rounded-xl" :disabled="form.processing">
                    {{ form.processing ? '提交中...' : '提交 Precognition 示例' }}
                </UiButton>
            </UiCardContent>
        </UiCard>
    </form>
</template>
