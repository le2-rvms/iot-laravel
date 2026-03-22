<script setup>
import { computed } from 'vue';
import { useForm as useVeeForm, useFieldArray } from 'vee-validate';
import * as yup from 'yup';
import { useInertiaFormBridge } from '@/composables/useInertiaFormBridge';

const props = defineProps({
    channelTypes: {
        type: Array,
        default: () => [],
    },
    triggerModes: {
        type: Array,
        default: () => [],
    },
});

const channelSchema = yup.object({
    type: yup.string().required('请选择渠道类型'),
    target: yup
        .string()
        .required('请填写目标')
        .test('channel-target', '目标格式不正确', function (value) {
            const type = this.parent.type;
            if (!value) {
                return false;
            }

            if (type === 'email') {
                return yup.string().email().isValidSync(value);
            }

            if (type === 'webhook') {
                return yup.string().url().isValidSync(value);
            }

            if (type === 'sms') {
                return /^\+?[0-9]{6,20}$/.test(value);
            }

            return true;
        }),
    retries: yup.number().required('请填写重试次数').min(0).max(10),
    enabled: yup.boolean().required(),
});

const validationSchema = yup.object({
    name: yup.string().required('请填写规则名称').max(255),
    enabled: yup.boolean().required(),
    description: yup.string().nullable().max(1000),
    trigger_mode: yup.string().required('请选择触发方式'),
    threshold: yup
        .number()
        .nullable()
        .transform((value, originalValue) => (originalValue === '' || Number.isNaN(value) ? null : value))
        .when('trigger_mode', {
            is: 'threshold',
            then: (schema) => schema.required('阈值触发模式下必须填写阈值').min(1).max(1000),
            otherwise: (schema) => schema.nullable(),
        }),
    quiet_hours_enabled: yup.boolean().required(),
    quiet_hours_start: yup.string().nullable().when('quiet_hours_enabled', {
        is: true,
        then: (schema) => schema.required('启用静默时段后必须填写开始时间'),
    }),
    quiet_hours_end: yup.string().nullable().when('quiet_hours_enabled', {
        is: true,
        then: (schema) => schema.required('启用静默时段后必须填写结束时间'),
    }),
    channels: yup
        .array()
        .of(channelSchema)
        .min(1, '至少需要一个通知渠道')
        .test('enabled-channel', '至少需要一个启用中的通知渠道', (value) => Array.isArray(value) && value.some((channel) => channel.enabled)),
});

const {
    defineField,
    errors,
    values,
    handleSubmit,
    setErrors,
} = useVeeForm({
    validationSchema,
    initialValues: {
        name: '',
        enabled: true,
        description: '',
        trigger_mode: 'threshold',
        threshold: 5,
        quiet_hours_enabled: false,
        quiet_hours_start: '',
        quiet_hours_end: '',
        channels: [
            {
                type: 'email',
                target: '',
                retries: 1,
                enabled: true,
            },
        ],
    },
});

const [name] = defineField('name');
const [enabled] = defineField('enabled');
const [description] = defineField('description');
const [triggerMode] = defineField('trigger_mode');
const [threshold] = defineField('threshold');
const [quietHoursEnabled] = defineField('quiet_hours_enabled');
const [quietHoursStart] = defineField('quiet_hours_start');
const [quietHoursEnd] = defineField('quiet_hours_end');

const channels = useFieldArray('channels');
const bridge = useInertiaFormBridge({ setErrors });

const showThreshold = computed(() => values.trigger_mode === 'threshold');
const showQuietHours = computed(() => values.quiet_hours_enabled);

function addChannel() {
    channels.push({
        type: 'email',
        target: '',
        retries: 0,
        enabled: true,
    });
}

function removeChannel(index) {
    if (channels.fields.value.length === 1) {
        return;
    }

    channels.remove(index);
}

const submit = handleSubmit((formValues) => {
    bridge.submitWithInertia({
        method: 'post',
        url: '/admin/settings/vee-validate',
        data: formValues,
    });
});
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <SharedFormsFormSection title="基础信息" description="填写规则名称、说明和启用状态。">
            <SharedFormsFormFieldShell label="规则名称" for-id="rule-name" :error="errors.name">
                <UiInput id="rule-name" v-model="name" :aria-invalid="Boolean(errors.name)" />
            </SharedFormsFormFieldShell>

            <SharedFormsFormFieldShell label="规则说明" for-id="rule-description" :error="errors.description">
                <textarea
                    id="rule-description"
                    v-model="description"
                    class="border-input focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-28 w-full rounded-xl border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px]"
                />
            </SharedFormsFormFieldShell>

            <label class="app-option-card flex items-center gap-3 rounded-xl border px-4 py-3">
                <UiCheckbox v-model="enabled" />
                <div>
                    <p class="app-copy-strong text-sm font-medium">启用规则</p>
                    <p class="app-copy-muted text-xs">关闭后规则仍会保留，但不会参与触发。</p>
                </div>
            </label>
        </SharedFormsFormSection>

        <SharedFormsFormSection title="触发规则" description="设置规则在什么情况下生效。">
            <SharedFormsFormFieldShell label="触发方式" for-id="trigger-mode" :error="errors.trigger_mode">
                <select
                    id="trigger-mode"
                    v-model="triggerMode"
                    class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-xl border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
                >
                    <option v-for="mode in triggerModes" :key="mode.value" :value="mode.value">
                        {{ mode.label }}
                    </option>
                </select>
            </SharedFormsFormFieldShell>

            <SharedFormsFormFieldShell
                v-if="showThreshold"
                label="触发阈值"
                for-id="threshold"
                description="阈值模式下必须填写。"
                :error="errors.threshold"
            >
                <UiInput id="threshold" v-model="threshold" type="number" min="1" max="1000" />
            </SharedFormsFormFieldShell>
        </SharedFormsFormSection>

        <SharedFormsFormSection title="静默时段" description="需要避开提醒的时间段可在这里设置。">
            <label class="app-option-card flex items-center gap-3 rounded-xl border px-4 py-3">
                <UiCheckbox v-model="quietHoursEnabled" />
                <div>
                    <p class="app-copy-strong text-sm font-medium">启用静默时段</p>
                    <p class="app-copy-muted text-xs">适合非工作时段抑制通知。</p>
                </div>
            </label>

            <div v-if="showQuietHours" class="grid gap-4 md:grid-cols-2">
                <SharedFormsFormFieldShell label="开始时间" for-id="quiet-start" :error="errors.quiet_hours_start">
                    <UiInput id="quiet-start" v-model="quietHoursStart" type="time" />
                </SharedFormsFormFieldShell>
                <SharedFormsFormFieldShell label="结束时间" for-id="quiet-end" :error="errors.quiet_hours_end">
                    <UiInput id="quiet-end" v-model="quietHoursEnd" type="time" />
                </SharedFormsFormFieldShell>
            </div>
        </SharedFormsFormSection>

        <SharedFormsRepeaterField
            title="通知渠道"
            description="为规则添加一个或多个通知接收方式。"
            add-label="新增渠道"
            :error="errors.channels"
            @add="addChannel"
        >
            <UiCard
                v-for="(field, index) in channels.fields.value"
                :key="field.key"
                class="app-panel-card rounded-xl shadow-none"
            >
                <UiCardHeader class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <UiCardTitle class="text-base">渠道 {{ index + 1 }}</UiCardTitle>
                        <UiCardDescription>
                            不同渠道的填写要求会有所不同。
                        </UiCardDescription>
                    </div>
                    <UiButton
                        type="button"
                        variant="outline"
                        class="rounded-xl"
                        :disabled="channels.fields.value.length === 1"
                        @click="removeChannel(index)"
                    >
                        删除
                    </UiButton>
                </UiCardHeader>
                <UiCardContent class="grid gap-4 md:grid-cols-2">
                    <SharedFormsFormFieldShell label="渠道类型" :error="errors[`channels[${index}].type`]">
                        <select
                            v-model="values.channels[index].type"
                            class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-xl border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
                        >
                            <option v-for="type in channelTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </SharedFormsFormFieldShell>

                    <SharedFormsFormFieldShell label="目标" :error="errors[`channels[${index}].target`]">
                        <UiInput v-model="values.channels[index].target" />
                    </SharedFormsFormFieldShell>

                    <SharedFormsFormFieldShell label="重试次数" :error="errors[`channels[${index}].retries`]">
                        <UiInput v-model="values.channels[index].retries" type="number" min="0" max="10" />
                    </SharedFormsFormFieldShell>

                    <div class="flex items-center">
                        <label class="app-option-card flex items-center gap-3 rounded-xl border px-4 py-3">
                            <UiCheckbox v-model="values.channels[index].enabled" />
                            <div>
                                <p class="app-copy-strong text-sm font-medium">启用此渠道</p>
                                <p class="app-copy-muted text-xs">至少保留一个启用中的渠道。</p>
                            </div>
                        </label>
                    </div>
                </UiCardContent>
            </UiCard>
        </SharedFormsRepeaterField>

        <UiCard class="app-panel-card rounded-[1.5rem] shadow-sm">
            <UiCardContent class="flex flex-col gap-3 p-6 sm:flex-row sm:justify-end">
                <UiButton type="submit" class="min-w-40 justify-center rounded-xl" :disabled="bridge.processing.value">
                    {{ bridge.processing.value ? '提交中' : '提交规则' }}
                </UiButton>
            </UiCardContent>
        </UiCard>

        <SharedFormsFieldError :message="errors.channels" />
    </form>
</template>
